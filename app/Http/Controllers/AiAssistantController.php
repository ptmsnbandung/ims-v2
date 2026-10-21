<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiAssistantController extends Controller
{
    /**
     * Handle incoming chat requests for IMS2 AI Copilot.
     */
    public function chat(Request $request): JsonResponse
    {
        @set_time_limit(60);

        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $userMessage = trim($request->input('message'));
        $history = $request->input('history', []);
        $currentUser = auth()->user();

        // 1. Gather relevant domain context from IMS database based on user intent
        $contextData = $this->gatherContext($userMessage, $currentUser);

        // 1. Check if user query matches direct internal SOP, schedules, or casual conversational chit-chat
        // Answer INSTANTLY from internal intelligence without external network latency
        $directReply = $this->checkDirectAnswerResponse($userMessage, $contextData, $currentUser);
        if ($directReply !== null) {
            return response()->json([
                'status' => 'success',
                'reply' => $directReply,
                'mode' => 'local_instant',
            ]);
        }

        // 2. Check if Gemini API Key is configured (supports multiple comma-separated keys for failover & quota pooling)
        $rawGeminiKeys = config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '');
        $geminiKeys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$rawGeminiKeys))));
        @set_time_limit(60);
        $geminiModel = trim((string)(Cache::get('ims_ai_working_model') ?: config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.8-flash')));
        if (empty($geminiModel) || str_contains($geminiModel, 'transcribe') || str_contains($geminiModel, '2.0-flash')) {
            $geminiModel = 'gemini-3.8-flash';
            Cache::forget('ims_ai_working_model');
        }

        // Circuit Breaker: If outbound connection to Google API timed out recently, answer instantly (0.02s) via Local Engine
        $isGeminiNetworkUnreachable = Cache::get('google_api_network_unreachable', false);
        if ($isGeminiNetworkUnreachable) {
            $fallbackReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, true);
            $note = "\n\n---\n> ℹ️ *Info Failover: Koneksi ke Google AI sedang mengalami timeout/latensi tinggi jaringan. Pertanyaan dijawab seketika via Asisten Internal IMS2.*";
            $fallbackReply .= $note;

            return response()->json([
                'status' => 'success',
                'reply' => $fallbackReply,
                'mode' => 'local_fallback',
                'fallback_reason' => 'Google API Network Timeout Circuit Breaker Active',
            ]);
        }

        if (!empty($geminiKeys)) {
            $now = now()->timestamp;
            $freshKeys = [];
            $coolingKeys = [];

            foreach ($geminiKeys as $idx => $key) {
                $cooldownUntil = (int)Cache::get('gemini_key_cooldown_' . md5($key), 0);
                if ($cooldownUntil > $now) {
                    $coolingKeys[] = [
                        'key' => $key,
                        'original_index' => $idx + 1,
                        'cooldown_until' => $cooldownUntil,
                    ];
                } else {
                    $freshKeys[] = [
                        'key' => $key,
                        'original_index' => $idx + 1,
                        'cooldown_until' => 0,
                    ];
                }
            }

            // Fresh keys are prioritized first; cooling keys are tried only if fresh keys fail
            usort($coolingKeys, fn($a, $b) => $a['cooldown_until'] <=> $b['cooldown_until']);
            $orderedKeys = array_merge($freshKeys, $coolingKeys);

            $lastException = null;
            $keysAttempted = 0;
            $keysStartTime = microtime(true);

            foreach ($orderedKeys as $item) {
                $key = $item['key'];
                $keyIndex = $item['original_index'];
                $keysAttempted++;

                // Track live RPM usage for current minute
                $keyId = substr(md5($key), 0, 10);
                $minuteKey = 'ims_ai_rpm_' . $keyId . '_' . date('YmdHi');
                if (!Cache::has($minuteKey)) {
                    Cache::put($minuteKey, 1, 120);
                } else {
                    Cache::increment($minuteKey);
                }

                try {
                    $reply = $this->callGeminiApi($key, $geminiModel, $userMessage, $history, $contextData, $currentUser);
                    return response()->json([
                        'status' => 'success',
                        'reply' => $reply,
                        'mode' => 'gemini',
                        'model' => $geminiModel,
                        'key_index' => $keyIndex,
                    ]);
                } catch (\Throwable $e) {
                    $lastException = $e;
                    $msg = $e->getMessage();
                    $msgLower = strtolower($msg);
                    $isTemporaryOrInternal = str_contains($msgLower, 'langkah query') 
                        || str_contains($msgLower, 'respons kosong') 
                        || str_contains($msgLower, 'high demand') 
                        || str_contains($msgLower, 'temporary')
                        || str_contains($msgLower, 'not found')
                        || str_contains($msgLower, '1.5-pro')
                        || str_contains($msgLower, '2.0-flash')
                        || str_contains($msgLower, 'is no longer available');
                    if (!$isTemporaryOrInternal) {
                        $this->recordKeyError($key, $msg, str_contains($msgLower, '429') ? 429 : 500);
                    }
                    Log::warning("[AiAssistant] Gemini Key #{$keyIndex} gagal: " . $msg . ". Beralih ke kunci berikutnya...");
                    if (str_contains($msgLower, 'high demand')) {
                        usleep(600000); // 600ms pacing before trying next key to allow Google load spike to clear
                    }
                    if (str_contains($msgLower, 'timed out') || str_contains($msgLower, 'could not resolve') || str_contains($msgLower, 'connection') || str_contains($msgLower, 'curl error 28')) {
                        // Server outbound connection to Google API is blocked or unreachable; cache for 120s and fail fast to local engine
                        Cache::put('google_api_network_unreachable', true, 120);
                        break;
                    }
                }
            }

            // Fallback to local heuristic engine when all keys are exhausted or error
            $totalKeys = count($geminiKeys);
            $errDetail = $lastException?->getMessage() ?? 'Google API sedang cooldown atau batas limit tercapai.';
            $fallbackReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, true);

            // Calculate minimum cooldown seconds remaining among all keys
            $minCoolSec = 60;
            foreach ($geminiKeys as $k) {
                $cSec = max(0, ((int)Cache::get('gemini_key_cooldown_' . md5($k), 0)) - now()->timestamp);
                if ($cSec > 0 && $cSec < $minCoolSec) {
                    $minCoolSec = $cSec;
                }
            }

            if (str_contains($errDetail, 'timed out') || str_contains($errDetail, 'curl error 28') || str_contains($errDetail, 'operation timed out')) {
                $shortErr = "Koneksi ke server Google mengalami timeout/latensi tinggi (ISP/Firewall)";
            } elseif (preg_match('/retry in ([0-9]+[a-z0-9.]+)/i', $errDetail, $retryMatch)) {
                $retryDuration = $retryMatch[1];
                $shortErr = "Batas kuota harian akun Google (Free Tier) habis. Google meminta menunggu: {$retryDuration} atau gunakan API Key dari project Google baru";
            } elseif (str_contains($errDetail, 'plan and billing details') || str_contains($errDetail, 'limit: 0')) {
                $shortErr = "Batas kuota gratis Google Project habis (Perlu API Key dari akun/project Google baru atau aktivasi billing)";
            } elseif (str_contains(strtolower($errDetail), 'high demand')) {
                $shortErr = "Server Google AI (Gemini 3.8) sedang mengalami lonjakan antrean global (High Demand). Silakan ulangi dalam beberapa detik";
            } elseif (str_contains($errDetail, '429')) {
                $shortErr = "Limit frekuensi per menit Google (15 RPM) tercapai. Kuota pulih otomatis dalam ~{$minCoolSec} detik";
            } else {
                $shortErr = substr($errDetail, 0, 95);
            }

            $attemptCountText = ($keysAttempted >= $totalKeys) ? "ke-{$totalKeys}" : "{$keysAttempted} dari {$totalKeys}";
            $note = "\n\n---\n> ℹ️ *Info Failover: Sistem telah mencoba {$attemptCountText} API Key Google terdaftar. Seluruhnya merespon '{$shortErr}'. Dijawab otomatis via Asisten Internal IMS2.*";
            $fallbackReply .= $note;

            return response()->json([
                'status' => 'success',
                'reply' => $fallbackReply,
                'mode' => 'local_fallback',
                'fallback_reason' => $errDetail,
                'keys_tried' => $totalKeys,
            ]);
        }

        // 3. Fallback when API key is not yet set
        $localReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, false);
        return response()->json([
            'status' => 'success',
            'reply' => $localReply,
            'mode' => 'local',
        ]);
    }

    /**
     * Return status of AI assistant integration.
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();
        $rawGeminiKeys = config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '');
        $geminiKeys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$rawGeminiKeys))));
        $configuredModel = (string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.8-flash'));
        if (empty($configuredModel) || str_contains($configuredModel, 'transcribe') || str_contains($configuredModel, '2.0-flash')) {
            $configuredModel = 'gemini-3.8-flash';
        }
        return response()->json([
            'configured' => !empty($geminiKeys),
            'key_count' => count($geminiKeys),
            'model' => $configuredModel,
            'user' => [
                'name' => $user?->nama ?? $user?->nama_lengkap ?? $user?->username ?? 'Staff',
                'role' => $this->getUserRole($user),
            ],
        ]);
    }

    /**
     * Admin monitoring dashboard for Gemini Multi-Key pooling and token usage.
     */
    public function monitorView(Request $request)
    {
        $rawGeminiKeys = config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '');
        $geminiKeys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$rawGeminiKeys))));
        $geminiModel = trim((string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.8-flash')));
        if (empty($geminiModel) || str_contains($geminiModel, 'transcribe') || str_contains($geminiModel, '2.0-flash')) {
            $geminiModel = 'gemini-3.8-flash';
        }

        $today = now()->format('Y-m-d');
        $usageData = Cache::get('ims_ai_usage_' . $today, [
            'date' => $today,
            'total_requests' => 0,
            'prompt_tokens' => 0,
            'candidates_tokens' => 0,
            'total_tokens' => 0,
            'keys' => [],
        ]);

        $rpdPerKey = 1500; // Free Tier standard Google Gemini 2.5 Flash (1,500 RPD)
        $rpmPerKey = 15;   // Free Tier standard Google Gemini (15 RPM)
        $totalPoolRpd = count($geminiKeys) * $rpdPerKey;
        $totalPoolRpm = count($geminiKeys) * $rpmPerKey;
        $totalRequestsUsed = (int)($usageData['total_requests'] ?? 0);
        $totalRequestsRemaining = max(0, $totalPoolRpd - $totalRequestsUsed);
        $percentUsed = $totalPoolRpd > 0 ? min(100, round(($totalRequestsUsed / $totalPoolRpd) * 100, 1)) : 0;

        $totalRpmUsedCurrentMinute = 0;
        $totalActiveKeys = 0;
        $totalCoolingKeys = 0;
        $activeCooldowns = [];

        // Build key details
        $keyList = [];
        foreach ($geminiKeys as $index => $k) {
            $keyId = substr(md5($k), 0, 10);
            $keyStats = $usageData['keys'][$keyId] ?? [
                'requests' => 0,
                'prompt_tokens' => 0,
                'candidates_tokens' => 0,
                'total_tokens' => 0,
                'status' => 'standby',
                'last_used_at' => null,
                'last_error' => null,
            ];

            $kRequests = (int)($keyStats['requests'] ?? 0);
            $kRemaining = max(0, $rpdPerKey - $kRequests);
            $kPercent = min(100, round(($kRequests / $rpdPerKey) * 100, 1));
            $masked = strlen($k) > 10 ? substr($k, 0, 6) . '...' . substr($k, -4) : 'AIzaSy...';

            $cooldownSec = max(0, ((int)Cache::get('gemini_key_cooldown_' . md5($k), 0)) - now()->timestamp);
            if ($cooldownSec > 0) {
                $totalCoolingKeys++;
                $activeCooldowns[] = $cooldownSec;
            } else {
                $totalActiveKeys++;
            }

            // Real-time requests in current minute (RPM)
            $kRpmUsed = (int)Cache::get('ims_ai_rpm_' . $keyId . '_' . date('YmdHi'), 0);
            $kRpmRemaining = max(0, $rpmPerKey - $kRpmUsed);
            $kRpmPercent = min(100, round(($kRpmUsed / $rpmPerKey) * 100, 1));
            $totalRpmUsedCurrentMinute += $kRpmUsed;

            // Auto-clear stale errors from previously deprecated models, internal step limits, temporary spikes, or expired minute-rate-limits (RPM)
            if ($cooldownSec === 0 && !empty($keyStats['last_error']) && (
                str_contains($keyStats['last_error'], 'gemini-2.0-flash') ||
                str_contains($keyStats['last_error'], 'flash-lite') ||
                str_contains($keyStats['last_error'], '1.5-pro') ||
                str_contains($keyStats['last_error'], 'not found') ||
                str_contains($keyStats['last_error'], 'is no longer available') ||
                str_contains($keyStats['last_error'], 'langkah query') ||
                str_contains(strtolower($keyStats['last_error']), 'high demand') ||
                str_contains(strtolower($keyStats['last_error']), 'temporary') ||
                ($kRequests < 1450 && (str_contains($keyStats['last_error'], '429') || str_contains($keyStats['last_error'], 'kuota harian/menit') || str_contains($keyStats['last_error'], 'frekuensi')))
            )) {
                $keyStats['last_error'] = null;
                $keyStats['status'] = $kRequests > 0 ? 'active' : 'standby';
                $usageData['keys'][$keyId] = $keyStats;
                Cache::put('ims_ai_usage_' . $today, $usageData, now()->addDays(14));
            }

            $status = $keyStats['status'] ?? 'standby';
            if ($cooldownSec > 0) {
                $status = 'exhausted';
                if (empty($keyStats['last_error']) || !str_contains($keyStats['last_error'], 'Cooldown')) {
                    $keyStats['last_error'] = "Cooldown limit frekuensi (RPM): sisa {$cooldownSec} detik";
                }
            } elseif ($status === 'standby' && $kRequests > 0) {
                $status = 'active';
            }

            $keyList[] = [
                'index' => $index + 1,
                'account_name' => 'Akun Google #' . ($index + 1),
                'key_id' => $keyId,
                'masked' => $masked,
                'status' => $status,
                'cooldown_sec' => $cooldownSec,
                'requests_used' => $kRequests,
                'requests_remaining' => $kRemaining,
                'percent_used' => $kPercent,
                'limit_rpd' => $rpdPerKey,
                'rpm_limit' => $rpmPerKey,
                'rpm_used' => $kRpmUsed,
                'rpm_remaining' => $kRpmRemaining,
                'rpm_percent' => $kRpmPercent,
                'prompt_tokens' => (int)($keyStats['prompt_tokens'] ?? 0),
                'candidates_tokens' => (int)($keyStats['candidates_tokens'] ?? 0),
                'total_tokens' => (int)($keyStats['total_tokens'] ?? 0),
                'last_used_at' => $keyStats['last_used_at'] ?? 'Belum ada aktivitas hari ini',
                'last_error' => $keyStats['last_error'] ?? null,
            ];
        }

        $minCooldownRemaining = !empty($activeCooldowns) ? min($activeCooldowns) : 0;

        // Calculate reset countdown (07:00 WIB = 00:00 UTC)
        $now = now();
        $resetTime = now()->setTime(7, 0, 0);
        if ($now->greaterThanOrEqualTo($resetTime)) {
            $resetTime = $resetTime->addDay();
        }
        $diffSeconds = $now->diffInSeconds($resetTime);
        $resetCountdownHours = floor($diffSeconds / 3600);
        $resetCountdownMinutes = floor(($diffSeconds % 3600) / 60);

        $metrics = [
            'total_keys' => count($geminiKeys),
            'model' => $geminiModel,
            'total_pool_rpd' => $totalPoolRpd,
            'total_pool_rpm' => $totalPoolRpm,
            'rpm_per_key' => $rpmPerKey,
            'total_rpm_used_current_minute' => $totalRpmUsedCurrentMinute,
            'total_active_keys' => $totalActiveKeys,
            'total_cooling_keys' => $totalCoolingKeys,
            'min_cooldown_remaining' => $minCooldownRemaining,
            'total_requests_used' => $totalRequestsUsed,
            'total_requests_remaining' => $totalRequestsRemaining,
            'percent_used' => $percentUsed,
            'prompt_tokens' => (int)($usageData['prompt_tokens'] ?? 0),
            'candidates_tokens' => (int)($usageData['candidates_tokens'] ?? 0),
            'total_tokens' => (int)($usageData['total_tokens'] ?? 0),
            'reset_countdown' => sprintf('%02d jam %02d menit', $resetCountdownHours, $resetCountdownMinutes),
            'keys' => $keyList,
            'is_configured' => count($geminiKeys) > 0,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($metrics);
        }

        return view('admin.ai-monitor', compact('metrics'));
    }

    /**
     * Test ping Gemini key live to check latency and response.
     */
    public function monitorPing(Request $request): JsonResponse
    {
        $rawGeminiKeys = config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '');
        $geminiKeys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$rawGeminiKeys))));
        $configuredModel = trim((string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.8-flash')));
        if (empty($configuredModel) || str_contains($configuredModel, 'transcribe') || str_contains($configuredModel, '2.0-flash')) {
            $configuredModel = 'gemini-3.8-flash';
        }

        if (empty($geminiKeys)) {
            return response()->json(['success' => false, 'message' => 'Belum ada GEMINI_API_KEY terkonfigurasi di sistem.'], 400);
        }

        $keyIndex = (int)$request->input('key_index', 1) - 1;
        $selectedKey = $geminiKeys[$keyIndex] ?? $geminiKeys[0];
        $actualIndex = isset($geminiKeys[$keyIndex]) ? ($keyIndex + 1) : 1;

        $prompt = trim($request->input('prompt', 'Konfirmasi tes koneksi live AI Gemini IMS2 aktif. Berikan respon 1 kalimat padat.'));

        $defaultCandidates = ['gemini-3.8-flash', 'gemini-3.8-pro', 'gemini-3.8'];
        $modelsToTry = array_values(array_unique(array_filter(array_merge([$configuredModel], $defaultCandidates))));

        // Probe active models from Google API for this specific key
        $probeError = null;
        try {
            $listRes = Http::timeout(4)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$selectedKey}");
            if ($listRes->successful()) {
                $remoteModels = $listRes->json('models', []);
                $discovered = [];
                foreach ($remoteModels as $rm) {
                    if (in_array('generateContent', $rm['supportedGenerationMethods'] ?? [])) {
                        $mName = str_replace('models/', '', $rm['name'] ?? '');
                        $isExcluded = str_contains($mName, 'tts') 
                            || str_contains($mName, 'image') 
                            || str_contains($mName, 'computer-use') 
                            || str_contains($mName, 'flash-lite')
                            || str_contains($mName, '1.5-')
                            || str_contains($mName, '2.0-')
                            || str_contains($mName, '2.5-');
                        if (!$isExcluded && (str_contains($mName, '3.') || str_contains($mName, 'flash') || str_contains($mName, 'pro'))) {
                            $discovered[] = $mName;
                        }
                    }
                }
                if (!empty($discovered)) {
                    $modelsToTry = array_values(array_unique(array_merge([$configuredModel], $discovered, $modelsToTry)));
                }
            } else {
                $probeError = $listRes->json('error.message') ?? ('HTTP ' . $listRes->status());
            }
        } catch (\Throwable $e) {
            $probeError = $e->getMessage();
        }
        $modelsToTry = array_values(array_filter($modelsToTry, fn($m) => 
            !str_contains($m, '1.5-') && 
            !str_contains($m, '2.0-') && 
            !str_contains($m, '2.5-') && 
            !str_contains($m, 'tts') && 
            !str_contains($m, 'image') && 
            !str_contains($m, 'computer-use')
        ));

        $startTime = microtime(true);
        $lastError = 'Gagal menghubungi Gemini API';
        $lastStatus = 500;
        $modelErrors = [];

        foreach ($modelsToTry as $geminiModel) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$selectedKey}";
                $response = Http::timeout(10)->post($url, [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 150],
                ]);

                $latency = round((microtime(true) - $startTime) * 1000);

                if (!$response->successful()) {
                    $err = $response->json('error.message') ?? ('HTTP ' . $response->status());
                    $lastError = $err;
                    $lastStatus = $response->status();
                    $modelErrors[$geminiModel] = "[{$lastStatus}] {$err}";

                    // If candidate model fails, proceed to next candidate
                    continue;
                }

                $usage = $response->json('usageMetadata');
                $this->recordUsage($selectedKey, $usage);
                Cache::forget('gemini_key_cooldown_' . md5($selectedKey));
                Cache::put('ims_ai_working_model', $geminiModel, 86400);

                $parts = $response->json('candidates.0.content.parts') ?? [];
                $reply = trim(implode('', array_map(fn($p) => empty($p['thought']) ? ($p['text'] ?? '') : '', $parts)));
                if ($reply === '') {
                    $reply = trim(implode('', array_map(fn($p) => $p['text'] ?? '', $parts)));
                }

                return response()->json([
                    'success' => true,
                    'key_index' => $actualIndex,
                    'model' => $geminiModel,
                    'latency_ms' => $latency,
                    'reply' => $reply,
                    'tokens' => [
                        'prompt' => (int)($usage['promptTokenCount'] ?? 0),
                        'candidates' => (int)($usage['candidatesTokenCount'] ?? 0),
                        'total' => (int)($usage['totalTokenCount'] ?? 0),
                    ],
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $modelErrors[$geminiModel] = $e->getMessage();
            }
        }

        $latency = round((microtime(true) - $startTime) * 1000);
        if ($lastStatus === 429 || $lastStatus === 403 || str_contains(strtolower($lastError), 'api key')) {
            $this->recordKeyError($selectedKey, $lastError, $lastStatus);
        }
        return response()->json([
            'success' => false,
            'key_index' => $actualIndex,
            'latency_ms' => $latency,
            'error' => $lastError,
            'probe_error' => $probeError,
            'models_tried' => $modelsToTry,
            'model_errors' => $modelErrors,
            'status_code' => $lastStatus,
        ], $lastStatus >= 400 && $lastStatus < 600 ? $lastStatus : 500);
    }

    /**
     * Reset today's monitor metrics cache.
     */
    public function resetMetrics(): JsonResponse
    {
        $today = now()->format('Y-m-d');
        Cache::forget('ims_ai_usage_' . $today);

        // Clear any cooldown locks and live RPM counters on configured keys
        $rawGeminiKeys = config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '');
        $geminiKeys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$rawGeminiKeys))));
        foreach ($geminiKeys as $k) {
            $kid = substr(md5($k), 0, 10);
            Cache::forget('gemini_key_cooldown_' . md5($k));
            Cache::forget('ims_ai_rpm_' . $kid . '_' . date('YmdHi'));
        }

        return response()->json(['success' => true, 'message' => 'Cache monitoring token, RPM, dan cooldown API key AI berhasil direset.']);
    }

    /**
     * Record API key token and request usage in daily cache.
     */
    protected function recordUsage(string $apiKey, ?array $usage): void
    {
        $today = now()->format('Y-m-d');
        $cacheKey = 'ims_ai_usage_' . $today;
        $data = Cache::get($cacheKey, [
            'date' => $today,
            'total_requests' => 0,
            'prompt_tokens' => 0,
            'candidates_tokens' => 0,
            'total_tokens' => 0,
            'keys' => [],
        ]);

        $keyId = substr(md5($apiKey), 0, 10);
        $data['total_requests']++;

        $pTokens = (int)($usage['promptTokenCount'] ?? 0);
        $cTokens = (int)($usage['candidatesTokenCount'] ?? 0);
        $tTokens = (int)($usage['totalTokenCount'] ?? ($pTokens + $cTokens));

        $data['prompt_tokens'] += $pTokens;
        $data['candidates_tokens'] += $cTokens;
        $data['total_tokens'] += $tTokens;

        if (!isset($data['keys'][$keyId])) {
            $data['keys'][$keyId] = [
                'masked' => strlen($apiKey) > 10 ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : 'AIzaSy...',
                'requests' => 0,
                'prompt_tokens' => 0,
                'candidates_tokens' => 0,
                'total_tokens' => 0,
                'status' => 'active',
                'last_used_at' => null,
                'last_error' => null,
            ];
        }

        $data['keys'][$keyId]['requests']++;
        $data['keys'][$keyId]['prompt_tokens'] += $pTokens;
        $data['keys'][$keyId]['candidates_tokens'] += $cTokens;
        $data['keys'][$keyId]['total_tokens'] += $tTokens;
        $data['keys'][$keyId]['status'] = 'active';
        $data['keys'][$keyId]['last_used_at'] = now()->format('H:i:s d/m/Y');
        $data['keys'][$keyId]['last_error'] = null;

        Cache::put($cacheKey, $data, now()->addDays(14));
    }

    /**
     * Record API key error / rate limit exhaustion.
     */
    protected function recordKeyError(string $apiKey, string $errorMessage, int $statusCode = 0): void
    {
        $today = now()->format('Y-m-d');
        $cacheKey = 'ims_ai_usage_' . $today;
        $data = Cache::get($cacheKey, [
            'date' => $today,
            'total_requests' => 0,
            'prompt_tokens' => 0,
            'candidates_tokens' => 0,
            'total_tokens' => 0,
            'keys' => [],
        ]);

        $keyId = substr(md5($apiKey), 0, 10);
        if (!isset($data['keys'][$keyId])) {
            $data['keys'][$keyId] = [
                'masked' => strlen($apiKey) > 10 ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : 'AIzaSy...',
                'requests' => 0,
                'prompt_tokens' => 0,
                'candidates_tokens' => 0,
                'total_tokens' => 0,
                'status' => 'standby',
                'last_used_at' => null,
                'last_error' => null,
            ];
        }

        $errLower = strtolower($errorMessage);
        if (str_contains($errLower, 'high demand') 
            || str_contains($errLower, 'temporary') 
            || str_contains($errLower, 'langkah query') 
            || str_contains($errLower, 'respons kosong')
            || str_contains($errLower, 'not found')
            || str_contains($errLower, '1.5-pro')
            || str_contains($errLower, '2.0-flash')
            || str_contains($errLower, 'is no longer available')) {
            return;
        }

        $isQuota = $statusCode === 429 || str_contains($errLower, 'quota') || str_contains($errLower, 'rate limit');
        $data['keys'][$keyId]['status'] = $isQuota ? 'exhausted' : 'error';
        $data['keys'][$keyId]['last_error'] = ($isQuota ? 'Limit kuota harian/menit tercapai (429)' : substr($errorMessage, 0, 120)) . ' (' . now()->format('H:i:s') . ')';

        Cache::put($cacheKey, $data, now()->addDays(14));
    }

    /**
     * Retrieve relevant database context matching user query.
     */
    protected function gatherContext(string $query, $user): array
    {
        $context = [
            'type' => 'general',
            'stats' => [],
            'customers' => [],
            'billings' => [],
            'tickets' => [],
            'olts' => [],
        ];

        $lower = strtolower($query);

        // Extract numbers or potential IDs (e.g., internet ID 6-12 digits, billing code, ticket code)
        preg_match('/\b\d{5,14}\b/', $query, $idMatches);
        $extractedId = $idMatches[0] ?? null;

        // 1. Comprehensive stopword removal for Indonesian natural language queries
        $stopwords = [
            'cari', 'cek', 'lihat', 'info', 'data', 'detail', 'status', 'keaktifan', 'aktif', 'nonaktif', 'masih',
            'pelanggan', 'nama', 'user', 'client', 'cust', 'customer', 'member',
            'alamat', 'lokasi', 'tempat', 'dimana', 'rumah',
            'tagihan', 'billing', 'invoice', 'bayar', 'pembayaran', 'lunas', 'unpaid', 'paid', 'belum', 'sudah', 'telah', 'sedang',
            'tolong', 'mohon', 'bantu', 'bisa', 'dong', 'ya', 'min', 'kak', 'admin', 'halo', 'hai', 'selamat', 'pagi', 'siang', 'sore', 'malam',
            'apakah', 'ada', 'berapa', 'siapa', 'kapan', 'kenapa', 'bagaimana', 'apa', 'tentang',
            'terisolir', 'isolir', 'suspend', 'gangguan', 'kendala', 'masalah', 'tiket', 'paket', 'layanan', 'internet', 'milik', 'punya', 'atas',
            'adalah', 'itu', 'ini', 'kah', 'dengan', 'bernama', 'si', 'dari', 'pada', 'yang', 'tidak', 'bukan', 'akan',
            'selain', 'total', 'jumlah', 'rekap', 'semua', 'seluruh', 'sisa', 'tunggakan', 'piutang',
            'oleh', 'dilakukan', 'melakukan', 'role', 'fungsi', 'tugas', 'wewenang', 'sistem', 'aplikasi', 'cara', 'aturan', 'menu', 'fitur', 'panduan', 'bantuan', 'contoh', 'seperti', 'tersebut', 'saja'
        ];
        $pattern = '/\b(' . implode('|', $stopwords) . ')\b/i';

        $cleanSearch = trim(preg_replace($pattern, '', $query));
        $cleanSearch = trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleanSearch));
        $cleanSearch = preg_replace('/\s+/', ' ', $cleanSearch);

        // Check if query is asking about role capabilities or general system help
        $isAskingRoleOrHelp = str_contains($lower, 'role') || str_contains($lower, 'wewenang') 
            || str_contains($lower, 'bisa dilakukan') || str_contains($lower, 'fungsi admin') 
            || str_contains($lower, 'tugas admin') || str_contains($lower, 'admin bisa apa') 
            || str_contains($lower, 'tugas') || str_contains($lower, 'bisa apa') 
            || str_contains($lower, 'panduan') || str_contains($lower, 'bantuan')
            || str_contains($lower, 'cara pakai') || str_contains($lower, 'fitur');

        if ($isAskingRoleOrHelp) {
            $context['type'] = 'role_guide';
            $context['role_guide'] = [
                'Admin' => [
                    'deskripsi' => 'Administrator Sistem Internal IMS2',
                    'wewenang' => [
                        'Manajemen Pelanggan: Akses penuh data pelanggan, status registrasi (#12-#23), isolir/suspend, dan terminasi.',
                        'Billing & Keuangan: Mengakses seluruh invoice tagihan, cek status pending verifikasi, dan memantau status lunas/unpaid.',
                        'Jaringan & OLT: Memantau master OLT (O1, O2, O3), IP address, port GPON, dan kondisi telemetri jaringan.',
                        'Helpdesk & Gangguan: Memantau dan mengelola seluruh tiket gangguan pelanggan.',
                        'Sistem & AI: Mengelola pengguna, memantau token & rotasi multi-kunci Google Gemini pada menu Monitoring AI.',
                    ],
                ],
                'Direktur' => [
                    'deskripsi' => 'Eksekutif / Manajemen Puncak',
                    'wewenang' => [
                        'Melihat ringkasan eksekutif performa harian (omzet, total tagihan terbayar/tertunggak, pertumbuhan pelanggan baru).',
                        'Monitoring kesehatan jaringan dan status operasional OLT.',
                        'Akses komprehensif ke semua data operasional dan keuangan IMS2.',
                    ],
                ],
                'Finance' => [
                    'deskripsi' => 'Divisi Keuangan & Billing',
                    'wewenang' => [
                        'Verifikasi bukti transfer / pembayaran dari pelanggan (#14 -> #15).',
                        'Penerbitan tagihan bulanan (draft, publish, unpaid).',
                        'Rekap pendapatan harian, bulanan, dan audit piutang.',
                    ],
                ],
                'NOC' => [
                    'deskripsi' => 'Network Operations Center',
                    'wewenang' => [
                        'Monitoring realtime OLT, redaman optik port GPON (LOS / DOWN), dan router backbone.',
                        'Diagnosa modem ONU pelanggan dan status koneksi PPPoE.',
                        'Eskalasi dan penanganan tiket gangguan jaringan.',
                    ],
                ],
                'Teknik' => [
                    'deskripsi' => 'Tim Teknisi Lapangan',
                    'wewenang' => [
                        'Eksekusi instalasi pelanggan baru (#16), perbaikan dropcore, dan pemeliharaan lapangan.',
                        'Update status penanganan tiket gangguan.',
                    ],
                ],
                'Customer Service' => [
                    'deskripsi' => 'Layanan Pelanggan & Helpdesk',
                    'wewenang' => [
                        'Pencarian data pelanggan berdasarkan nama atau nomor internet.',
                        'Pengecekan status tagihan dan informasi paket.',
                        'Pembuatan tiket gangguan pelanggan.',
                    ],
                ],
            ];
        }

        // Build list of high-priority search candidates
        $searchCandidates = [];
        if (!empty($cleanSearch) && strlen($cleanSearch) >= 3 && !$isAskingRoleOrHelp) {
            $searchCandidates[] = $cleanSearch;
        }

        // Pattern A: Match 'atas nama [Name]' or 'nama [Name]' or 'pelanggan [Name]'
        if (!$isAskingRoleOrHelp && preg_match('/(?:atas\s+nama|nama\s+pelanggan|nama|pelanggan|user)\s+([A-Za-z\s]{3,35}?)(?=\s+(?:tidak|belum|bukan|adalah|yaitu|di|pada|ke|yang|dari|dengan|\.|\,|\n|\r|$))/i', $query, $nm)) {
            $extractedName = trim(preg_replace($pattern, '', $nm[1]));
            if (!empty($extractedName) && strlen($extractedName) >= 3) {
                array_unshift($searchCandidates, $extractedName);
            }
        }

        // Pattern B: Match consecutive UPPERCASE words (e.g. 'MUHAMMAD RAMA PUTRA')
        if (!$isAskingRoleOrHelp && preg_match_all('/\b([A-Z]{3,}(?:\s+[A-Z]{2,}){1,4})\b/', $query, $capsMatches)) {
            foreach ($capsMatches[1] as $capName) {
                $capClean = trim(preg_replace($pattern, '', $capName));
                if (!empty($capClean) && strlen($capClean) >= 3 && !in_array(strtoupper($capClean), ['IMS2', 'IMS', 'NOC', 'OLT', 'GPON', 'NULL', 'ROLE', 'ADMIN'])) {
                    array_unshift($searchCandidates, $capClean);
                }
            }
        }

        // Pattern C: Quoted name string e.g. "MUHAMMAD RAMA PUTRA"
        if (!$isAskingRoleOrHelp && preg_match('/["\']([a-zA-Z0-9\s]{3,35})["\']/i', $query, $qm)) {
            $qClean = trim(preg_replace($pattern, '', $qm[1]));
            if (!empty($qClean) && strlen($qClean) >= 3) {
                array_unshift($searchCandidates, $qClean);
            }
        }

        $searchCandidates = array_values(array_unique($searchCandidates));
        $nameTokens = array_values(array_filter(explode(' ', $cleanSearch), fn($t) => strlen($t) >= 3));

        // Check if query is asking for aggregate stats / new users rather than a specific customer profile
        $isAskingCustomerStats = str_contains($lower, 'rekap') || str_contains($lower, 'ringkasan') || str_contains($lower, 'summary') 
            || str_contains($lower, 'user baru') || str_contains($lower, 'usser baru') 
            || str_contains($lower, 'pelanggan baru') || str_contains($lower, 'bulan ini') 
            || str_contains($lower, 'total user') || str_contains($lower, 'total pelanggan') 
            || str_contains($lower, 'berapa user') || str_contains($lower, 'berapa usser') || str_contains($lower, 'berapa pelanggan')
            || (str_contains($lower, 'baru') && (str_contains($lower, 'user') || str_contains($lower, 'usser') || str_contains($lower, 'pelanggan')));

        $hasExplicitCustomerIntent = !empty($extractedId) 
            || str_contains($lower, 'pelanggan') 
            || str_contains($lower, 'cust') 
            || str_contains($lower, 'atas nama') 
            || str_contains($lower, 'cari nama') 
            || str_contains($lower, 'cek nama');

        // Context 1: Customer lookup (by ID, username, or name candidates across batchjob & m_pelanggan)
        if (($hasExplicitCustomerIntent || !empty($searchCandidates)) && !$isAskingCustomerStats && !$isAskingRoleOrHelp) {
            try {
                $hasPppoeCol = Schema::hasColumn('trx_batchjob_register', 'pppoe_username');
                $userCol = $hasPppoeCol ? 'pppoe_username' : 'ont_us';

                $selectCols = [
                    'r.nomor_internet',
                    DB::raw("coalesce(nullif(r.nama_pelanggan, ''), p.nama_penduduk, 'Tanpa Nama') as nama_pelanggan"),
                    'r.' . $userCol . ' as pppoe_username',
                    'r.kode_bandwith',
                    'r.status_reg',
                    'r.is_suspend',
                    'r.is_termin',
                    'r.alamat_pasang',
                    'r.olt',
                    'r.index_olt',
                    'r.router_id',
                    'p.nomor_hp',
                ];

                $matchedCustomers = collect();

                if ($extractedId) {
                    $matchedCustomers = DB::table('trx_batchjob_register as r')
                        ->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk')
                        ->where('r.hide', 0)
                        ->where(function ($q) use ($extractedId, $userCol) {
                            $q->where('r.nomor_internet', 'like', "%{$extractedId}%")
                              ->orWhere('r.' . $userCol, 'like', "%{$extractedId}%")
                              ->orWhere('p.nomor_hp', 'like', "%{$extractedId}%");
                        })
                        ->select($selectCols)
                        ->limit(5)
                        ->get();
                }

                // Tier 1: Try full phrase match on nama_pelanggan, nama_penduduk, or userCol
                if ($matchedCustomers->isEmpty()) {
                    foreach ($searchCandidates as $term) {
                        if (strlen($term) >= 3) {
                            $res = DB::table('trx_batchjob_register as r')
                                ->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk')
                                ->where('r.hide', 0)
                                ->where(function ($q) use ($term, $userCol) {
                                    $q->where('r.nama_pelanggan', 'like', "%{$term}%")
                                      ->orWhere('p.nama_penduduk', 'like', "%{$term}%")
                                      ->orWhere('r.' . $userCol, 'like', "%{$term}%")
                                      ->orWhere('p.nomor_hp', 'like', "%{$term}%")
                                      ->orWhere('r.nomor_internet', 'like', "%{$term}%");
                                })
                                ->select($selectCols)
                                ->limit(5)
                                ->get();
                            if ($res->isNotEmpty()) {
                                $matchedCustomers = $res;
                                break;
                            }
                        }
                    }
                }

                // Tier 2: If multi-word, match ALL tokens with AND (e.g. 'rama' AND 'putra')
                if ($matchedCustomers->isEmpty() && count($nameTokens) > 1) {
                    $q = DB::table('trx_batchjob_register as r')
                        ->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk')
                        ->where('r.hide', 0);
                    foreach ($nameTokens as $tok) {
                        $q->where(function ($sub) use ($tok, $userCol) {
                            $sub->where('r.nama_pelanggan', 'like', "%{$tok}%")
                                ->orWhere('p.nama_penduduk', 'like', "%{$tok}%")
                                ->orWhere('r.' . $userCol, 'like', "%{$tok}%");
                        });
                    }
                    $matchedCustomers = $q->select($selectCols)->limit(5)->get();
                }

                // Tier 3: Token-by-token fallback (e.g. match first distinctive token >= 4 chars) ONLY if explicit customer intent
                if ($matchedCustomers->isEmpty() && !empty($nameTokens) && $hasExplicitCustomerIntent) {
                    foreach ($nameTokens as $tok) {
                        if (strlen($tok) >= 4) {
                            $res = DB::table('trx_batchjob_register as r')
                                ->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk')
                                ->where('r.hide', 0)
                                ->where(function ($q) use ($tok, $userCol) {
                                    $q->where('r.nama_pelanggan', 'like', "%{$tok}%")
                                      ->orWhere('p.nama_penduduk', 'like', "%{$tok}%")
                                      ->orWhere('r.' . $userCol, 'like', "%{$tok}%");
                                })
                                ->select($selectCols)
                                ->limit(5)
                                ->get();
                            if ($res->isNotEmpty()) {
                                $matchedCustomers = $res;
                                break;
                            }
                        }
                    }
                }

                $context['customers'] = $matchedCustomers->toArray();

                // Context 1.1: Enrich customers with OLT master data and ONU port diagnosis
                if (!empty($context['customers'])) {
                    $oltAliasMap = [
                        'bagong' => 'O2',
                        'babakan tarogong' => 'O2',
                        'o2' => 'O2',
                        'kayu agung' => 'O1',
                        'kayuagung' => 'O1',
                        'msn' => 'O1',
                        'o1' => 'O1',
                        'soreang' => 'O3',
                        'bbu' => 'O3',
                        'o3' => 'O3',
                    ];

                    $allOlts = DB::table('m_olt')
                        ->select(['kode_olt', 'name_olt', 'ip_address', 'brand', 'model', 'is_active'])
                        ->get()
                        ->keyBy('kode_olt');

                    foreach ($context['customers'] as $c) {
                        $rawOlt = strtolower(trim((string)($c->olt ?? '')));
                        $resolvedCode = $oltAliasMap[$rawOlt] ?? null;
                        if (!$resolvedCode) {
                            foreach ($allOlts as $code => $oltObj) {
                                if (str_contains(strtolower($oltObj->name_olt), $rawOlt) || strtolower($code) === $rawOlt) {
                                    $resolvedCode = $code;
                                    break;
                                }
                            }
                        }

                        if ($resolvedCode && isset($allOlts[$resolvedCode])) {
                            $matchedOlt = $allOlts[$resolvedCode];
                            $c->olt_code = $matchedOlt->kode_olt;
                            $c->olt_name = $matchedOlt->name_olt;
                            $c->olt_ip = $matchedOlt->ip_address;
                            $c->olt_status = $matchedOlt->is_active == 1 ? 'Aktif' : 'Non-Aktif';
                        } else {
                            $c->olt_code = null;
                            $c->olt_name = $c->olt ?: 'Belum ditentukan';
                            $c->olt_ip = null;
                            $c->olt_status = 'Unknown';
                        }

                        $cleanIndex = trim((string)($c->index_olt ?? ''));
                        $c->index_olt = $cleanIndex ?: null;
                        $c->has_onu_port = !empty($cleanIndex);
                        if (!$c->has_onu_port) {
                            $c->onu_telemetry_status = 'Port GPON Belum Dikonfigurasi (index_olt NULL)';
                            $c->onu_telemetry_note = 'Data live redaman optik (Rx/Tx dBm) dan fisik link ONU belum dapat dicek karena nomor port GPON ONU belum diisi pada registrasi pelanggan.';
                        } else {
                            $c->onu_telemetry_status = "Port GPON Terdaftar: {$cleanIndex}";
                            $c->onu_telemetry_note = 'Port GPON terdaftar. Pengecekan status fisik dapat dilakukan via OLT management.';
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("[AiAssistant] Gagal mengambil data pelanggan: " . $e->getMessage());
            }
        }

        // Context 1.5: Collect target customer IDs from extractedId or discovered customer records
        $targetCustomerIds = [];
        if ($extractedId) {
            $targetCustomerIds[] = (string)$extractedId;
        }
        if (!empty($context['customers'])) {
            foreach ($context['customers'] as $cust) {
                if (!empty($cust->nomor_internet)) {
                    $targetCustomerIds[] = (string)$cust->nomor_internet;
                }
                if (!empty($cust->pppoe_username)) {
                    $targetCustomerIds[] = (string)$cust->pppoe_username;
                }
            }
        }
        $targetCustomerIds = array_values(array_unique(array_filter($targetCustomerIds)));

        // Context 2: Billing / Tagihan / Keuangan (Khusus role berhak: Finance, Direksi, CEO, Admin, CS)
        $isGeneralFinanceQuery = str_contains($lower, 'total') || str_contains($lower, 'rekap')
            || str_contains($lower, 'semua') || str_contains($lower, 'selain')
            || str_contains($lower, 'seluruh') || str_contains($lower, 'daftar tagihan')
            || str_contains($lower, 'piutang') || str_contains($lower, 'tunggakan')
            || str_contains($lower, 'pemasukan') || str_contains($lower, 'pendapatan')
            || str_contains($lower, 'omset') || str_contains($lower, 'paid')
            || str_contains($lower, 'lunas') || str_contains($lower, 'rupiah')
            || str_contains($lower, 'uang') || str_contains($lower, 'berapa');

        $isFinancialTopic = str_contains($lower, 'tagih') || str_contains($lower, 'bill')
            || str_contains($lower, 'bayar') || str_contains($lower, 'invoice')
            || str_contains($lower, 'keuangan') || str_contains($lower, 'pemasukan')
            || str_contains($lower, 'pendapatan') || str_contains($lower, 'omset')
            || str_contains($lower, 'lunas') || str_contains($lower, 'paid')
            || str_contains($lower, 'unpaid') || !empty($targetCustomerIds);

        // CRITICAL: Jika pertanyaan menargetkan nama pelanggan spesifik, JANGAN PERNAH menampilkan invoice orang lain!
        $isSpecificCustomerQuery = !$isGeneralFinanceQuery && (!empty($extractedId) || !empty($searchCandidates) || !empty($nameTokens));

        if ($this->canAccessFinance($user) && $isFinancialTopic) {
            try {
                if ($isSpecificCustomerQuery && empty($targetCustomerIds)) {
                    // User bertanya tentang pelanggan tertentu, namun pelanggan tidak ada di database: JANGAN kembalikan tagihan random orang lain!
                    $context['billings'] = [];
                } else if (!empty($targetCustomerIds)) {
                    // Tampilkan hanya tagihan milik target pelanggan yang dicari
                    $bills = DB::table('trx_billing_layanan')
                        ->where('hide', 0)
                        ->where(function ($q) use ($targetCustomerIds) {
                            foreach ($targetCustomerIds as $id) {
                                $q->orWhere('nomor_internet', 'like', "%{$id}%")
                                  ->orWhere('kode_billing_layanan', 'like', "%{$id}%");
                            }
                        })
                        ->select([
                            'kode_billing_layanan',
                            'nomor_internet',
                            'bulan_tagihan',
                            'tahun_tagihan',
                            'total_layanan',
                            'status_bill_lay',
                            'payment_type',
                            'payment_paid',
                            'amount_paid',
                        ])
                        ->orderByDesc('tahun_tagihan')
                        ->orderByDesc('bulan_tagihan')
                        ->limit(6)
                        ->get()
                        ->map(function ($b) {
                            $b->status_desc = match((string)$b->status_bill_lay) {
                                '15' => 'Lunas (Paid)',
                                '14' => 'Menunggu Verifikasi',
                                '13' => 'Belum Bayar (Unpaid)',
                                '12' => 'Publish Tagihan',
                                '11' => 'Draft',
                                default => 'Status #' . $b->status_bill_lay
                            };
                            return $b;
                        })
                        ->toArray();

                    $context['billings'] = $bills;
                } else {
                    // Deteksi filter periode bulan dan tahun dari pesan pengguna
                    $monthMap = [
                        'januari' => '01', 'jan' => '01',
                        'februari' => '02', 'feb' => '02', 'pebruari' => '02',
                        'maret' => '03', 'mar' => '03',
                        'april' => '04', 'apr' => '04',
                        'mei' => '05',
                        'juni' => '06', 'jun' => '06',
                        'juli' => '07', 'jul' => '07',
                        'agustus' => '08', 'agt' => '08', 'agust' => '08',
                        'september' => '09', 'sep' => '09', 'sept' => '09',
                        'oktober' => '10', 'okt' => '10',
                        'november' => '11', 'nov' => '11',
                        'desember' => '12', 'des' => '12',
                    ];
                    $targetMonth = null;
                    $targetMonthName = null;
                    foreach ($monthMap as $mName => $mCode) {
                        if (preg_match('/\b' . $mName . '\b/i', $lower)) {
                            $targetMonth = $mCode;
                            $targetMonthName = ucfirst($mName);
                            break;
                        }
                    }
                    $targetYear = null;
                    if (preg_match('/\b(202[0-9])\b/', $lower, $yMatches)) {
                        $targetYear = $yMatches[1];
                    } else if ($targetMonth !== null) {
                        $currentYear = date('Y');
                        $hasCurrentYear = DB::table('trx_billing_layanan')->where('hide', 0)->where('bulan_tagihan', $targetMonth)->where('tahun_tagihan', $currentYear)->exists();
                        if ($hasCurrentYear) {
                            $targetYear = $currentYear;
                        } else {
                            $latestYear = DB::table('trx_billing_layanan')->where('hide', 0)->where('bulan_tagihan', $targetMonth)->max('tahun_tagihan');
                            $targetYear = $latestYear ?: $currentYear;
                        }
                    }

                    // Query Agregasi Resmi (Akumulasi Total Database untuk periode yang relevan)
                    $sumQ = DB::table('trx_billing_layanan')->where('hide', 0);
                    if ($targetMonth) {
                        $sumQ->where('bulan_tagihan', $targetMonth);
                    }
                    if ($targetYear) {
                        $sumQ->where('tahun_tagihan', $targetYear);
                    }
                    $statRow = $sumQ->selectRaw("
                        count(*) as total_invoices,
                        coalesce(sum(total_layanan), 0) as total_all_rp,
                        count(case when status_bill_lay = 15 then 1 end) as paid_count,
                        coalesce(sum(case when status_bill_lay = 15 then total_layanan else 0 end), 0) as paid_rp,
                        count(case when status_bill_lay = 14 then 1 end) as pending_count,
                        coalesce(sum(case when status_bill_lay = 14 then total_layanan else 0 end), 0) as pending_rp,
                        count(case when status_bill_lay in (11, 12, 13) then 1 end) as unpaid_count,
                        coalesce(sum(case when status_bill_lay in (11, 12, 13) then total_layanan else 0 end), 0) as unpaid_rp
                    ")->first();

                    $periodeLabel = $targetMonth ? ("Bulan " . ($targetMonthName ?? $targetMonth) . " " . ($targetYear ?? date('Y'))) : "Keseluruhan Database";
                    $context['rekap_keuangan_billing'] = [
                        'periode' => $periodeLabel,
                        'total_seluruh_tagihan_diterbitkan' => [
                            'keterangan' => 'Total seluruh invoice (semua status lunas, pending, unpaid) yang diterbitkan pada periode ini',
                            'jumlah_invoice' => (int)($statRow->total_invoices ?? 0),
                            'total_rupiah' => (float)($statRow->total_all_rp ?? 0),
                            'total_rupiah_formatted' => 'Rp ' . number_format((float)($statRow->total_all_rp ?? 0), 0, ',', '.'),
                        ],
                        'total_pemasukan_lunas_paid' => [
                            'keterangan' => 'Total uang masuk yang sudah lunas dibayar pelanggan (status_bill_lay = 15)',
                            'jumlah_invoice' => (int)($statRow->paid_count ?? 0),
                            'total_rupiah' => (float)($statRow->paid_rp ?? 0),
                            'total_rupiah_formatted' => 'Rp ' . number_format((float)($statRow->paid_rp ?? 0), 0, ',', '.'),
                        ],
                        'total_tertunggak_belum_bayar' => [
                            'keterangan' => 'Total tagihan belum bayar / piutang (status_bill_lay = 13/12/11)',
                            'jumlah_invoice' => (int)($statRow->unpaid_count ?? 0),
                            'total_rupiah' => (float)($statRow->unpaid_rp ?? 0),
                            'total_rupiah_formatted' => 'Rp ' . number_format((float)($statRow->unpaid_rp ?? 0), 0, ',', '.'),
                        ],
                        'total_menunggu_verifikasi_pending' => [
                            'keterangan' => 'Total bukti bayar menunggu verifikasi finance (status_bill_lay = 14)',
                            'jumlah_invoice' => (int)($statRow->pending_count ?? 0),
                            'total_rupiah' => (float)($statRow->pending_rp ?? 0),
                            'total_rupiah_formatted' => 'Rp ' . number_format((float)($statRow->pending_rp ?? 0), 0, ',', '.'),
                        ],
                    ];

                    // Sample invoice rows for reference
                    $sampleQ = DB::table('trx_billing_layanan')->where('hide', 0);
                    if ($targetMonth) {
                        $sampleQ->where('bulan_tagihan', $targetMonth);
                    }
                    if ($targetYear) {
                        $sampleQ->where('tahun_tagihan', $targetYear);
                    }
                    if (str_contains($lower, 'lunas') || str_contains($lower, 'paid') || str_contains($lower, 'pemasukan')) {
                        $sampleQ->where('status_bill_lay', 15);
                    } else if (str_contains($lower, 'pending') || str_contains($lower, 'verif')) {
                        $sampleQ->where('status_bill_lay', 14);
                    } else if (str_contains($lower, 'belum') || str_contains($lower, 'unpaid') || str_contains($lower, 'tunggak')) {
                        $sampleQ->whereNotIn('status_bill_lay', [15]);
                    }

                    $bills = $sampleQ->select([
                        'kode_billing_layanan',
                        'nomor_internet',
                        'bulan_tagihan',
                        'tahun_tagihan',
                        'total_layanan',
                        'status_bill_lay',
                        'payment_type',
                        'payment_paid',
                        'amount_paid',
                    ])
                    ->orderByDesc('tahun_tagihan')
                    ->orderByDesc('bulan_tagihan')
                    ->limit(5)
                    ->get()
                    ->map(function ($b) {
                        $b->status_desc = match((string)$b->status_bill_lay) {
                            '15' => 'Lunas (Paid)',
                            '14' => 'Menunggu Verifikasi',
                            '13' => 'Belum Bayar (Unpaid)',
                            '12' => 'Publish Tagihan',
                            '11' => 'Draft',
                            default => 'Status #' . $b->status_bill_lay
                        };
                        return $b;
                    })
                    ->toArray();

                    $context['billings_sample'] = $bills;
                    $context['billings_sample_note'] = 'PERHATIAN: [billings_sample] HANYA cuplikan 5 sampel baris untuk melihat format kode invoice. Rujukan resmi total nominal dan jumlah transaksi harus mengambil dari [rekap_keuangan_billing]!';
                }

                // Total pending verification count
                $context['stats']['pending_verification_count'] = DB::table('trx_billing_layanan')
                    ->where('hide', 0)
                    ->where('status_bill_lay', 14)
                    ->count();
            } catch (\Throwable $e) {
                Log::warning("[AiAssistant] Gagal mengambil data tagihan: " . $e->getMessage());
            }
        }

        // Context 3: Tiket Gangguan
        if (str_contains($lower, 'tiket') || str_contains($lower, 'gangguan') || str_contains($lower, 'keluhan') || str_contains($lower, 'los') || str_contains($lower, 'komplain') || (!empty($targetCustomerIds) && (str_contains($lower, 'kendala') || str_contains($lower, 'masalah') || str_contains($lower, 'rusak')))) {
            try {
                $context['tickets'] = DB::table('trx_tiket_gangguan')
                    ->where('hide', 0)
                    ->when(!empty($targetCustomerIds), function ($q) use ($targetCustomerIds) {
                        $q->where(function ($sub) use ($targetCustomerIds) {
                            foreach ($targetCustomerIds as $id) {
                                $sub->orWhere('tiket', 'like', "%{$id}%")
                                    ->orWhere('nomor_internet', 'like', "%{$id}%");
                            }
                        });
                    })
                    ->when(empty($targetCustomerIds), function ($q) {
                        $q->whereNotIn('status', ['close', 'selesai', 'done']);
                    })
                    ->select(['tiket', 'nomor_internet', 'keluhan', 'status', 'kat_tiket', 'penanganan', 'date_create'])
                    ->orderByDesc('date_create')
                    ->limit(5)
                    ->get()
                    ->toArray();

                $context['stats']['open_tickets_count'] = DB::table('trx_tiket_gangguan')
                    ->where('hide', 0)
                    ->whereNotIn('status', ['close', 'selesai', 'done'])
                    ->count();
            } catch (\Throwable $e) {
                Log::warning("[AiAssistant] Gagal mengambil data tiket: " . $e->getMessage());
            }
        }

        // Context 4: OLT & Network (Khusus role teknis: NOC, Teknik, Direksi, CEO, Admin)
        if ($this->canAccessNetwork($user) && (str_contains($lower, 'olt') || str_contains($lower, 'jaringan') || str_contains($lower, 'noc') || str_contains($lower, 'pon') || str_contains($lower, 'server') || str_contains($lower, 'redaman') || str_contains($lower, 'optik') || !empty($context['customers']))) {
            try {
                $context['olts'] = DB::table('m_olt')
                    ->select(['kode_olt', 'name_olt', 'ip_address', 'brand', 'model', 'is_active', 'last_status'])
                    ->orderBy('name_olt')
                    ->limit(8)
                    ->get()
                    ->toArray();
            } catch (\Throwable $e) {
                Log::warning("[AiAssistant] Gagal mengambil data OLT: " . $e->getMessage());
            }
        }

        // Context 4.5: Real-time NOC Telemetry & Alerts (ONU LOS / DOWN / Isu Aktif - Ultra Cepat Non-Blocking)
        $isAskingNocAlerts = str_contains($lower, 'loss') || str_contains($lower, 'los') || str_contains($lower, 'down') 
            || str_contains($lower, 'offline') || str_contains($lower, 'kabel putus') || str_contains($lower, 'isu aktif') 
            || str_contains($lower, 'isu') || str_contains($lower, 'node') || str_contains($lower, 'alert') || str_contains($lower, 'notifikasi');

        if ($this->canAccessNetwork($user) && $isAskingNocAlerts) {
            try {
                $context['noc_telemetry'] = $this->getFastNocTelemetryData();
            } catch (\Throwable $e) {
                Log::warning("[AiAssistant] Gagal mengambil data NOC Telemetry: " . $e->getMessage());
            }
        }

        // Context 5: Global Quick Stats (for summary/recap requests & customer growth metrics)
        $isAskingStats = str_contains($lower, 'rekap') || str_contains($lower, 'ringkasan') || str_contains($lower, 'summary') 
            || str_contains($lower, 'hari ini') || str_contains($lower, 'status sistem') 
            || $isAskingCustomerStats;

        if ($isAskingStats) {
            try {
                $currentMonth = date('m');
                $currentYear = date('Y');
                $monthNames = [
                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                ];
                $monthName = $monthNames[$currentMonth] ?? $currentMonth;

                $context['stats']['current_period'] = "{$monthName} {$currentYear}";
                $context['stats']['total_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->count();
                $context['stats']['active_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('status_reg', 20)->count();
                $context['stats']['isolated_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('is_suspend', 1)->count();
                $context['stats']['new_customers_this_month'] = DB::table('trx_batchjob_register')
                    ->where('hide', 0)
                    ->whereYear('date_create', $currentYear)
                    ->whereMonth('date_create', $currentMonth)
                    ->count();
                $context['stats']['new_customers_today'] = DB::table('trx_batchjob_register')
                    ->where('hide', 0)
                    ->whereDate('date_create', date('Y-m-d'))
                    ->count();
                
                // Financial summary stats only for Finance & Direksi
                if ($this->canAccessFinance($user)) {
                    $context['stats']['bills_paid_this_month'] = DB::table('trx_billing_layanan')
                        ->where('hide', 0)
                        ->where('bulan_tagihan', $currentMonth)
                        ->where('tahun_tagihan', $currentYear)
                        ->where('status_bill_lay', 15)
                        ->count();
                    $context['stats']['bills_unpaid_this_month'] = DB::table('trx_billing_layanan')
                        ->where('hide', 0)
                        ->where('bulan_tagihan', $currentMonth)
                        ->where('tahun_tagihan', $currentYear)
                        ->whereNotIn('status_bill_lay', [15])
                        ->count();
                }

                $context['stats']['open_tickets'] = DB::table('trx_tiket_gangguan')
                    ->where('hide', 0)
                    ->whereNotIn('status', ['close', 'selesai', 'done'])
                    ->count();
            } catch (\Throwable $e) {
                Log::warning("[AiAssistant] Gagal mengambil rekap statistik: " . $e->getMessage());
            }
        }

        return $context;
    }

    /**
     * Non-blocking fast NOC telemetry reader using existing Redis/Database cache keys.
     * Completes in < 40ms without ever triggering blocking OLT telnet scans.
     */
    protected function getFastNocTelemetryData(): array
    {
        $olts = DB::table('m_olt')->where('is_active', 1)->get()->keyBy('kode_olt');
        
        $allRegs = DB::table('trx_batchjob_register')
            ->select('nomor_internet', 'nama_pelanggan', 'alamat_pasang', 'olt', 'index_olt')
            ->whereNotNull('index_olt')
            ->where('hide', 0)
            ->get();

        $regMap = [];
        foreach ($allRegs as $r) {
            $rawIdx = trim((string)$r->index_olt);
            $cleanIdx = trim(str_ireplace('gpon-onu_', '', $rawIdx));
            $oltKey = strtolower(trim((string)$r->olt));
            $regMap["{$oltKey}_{$cleanIdx}"] = $r;
            $regMap[$cleanIdx] = $r;
        }

        $losNodes = [];
        $downNodes = [];

        foreach ($olts as $kode => $olt) {
            $onus = Cache::get("olt_live_all_onu_states_{$kode}") ?: Cache::get("olt_last_known_good_onus_{$kode}", []);
            if (!is_array($onus)) continue;

            foreach ($onus as $item) {
                $iface = $item['interface'] ?? '';
                $cleanIface = trim(str_ireplace('gpon-onu_', '', $iface));
                $phase = strtolower((string)($item['phase'] ?? ''));
                $isLos = (!empty($item['is_los'])) || $phase === 'los';

                $oltKey = strtolower($kode);
                $matchedReg = $regMap["{$oltKey}_{$cleanIface}"] ?? ($regMap[$cleanIface] ?? null);

                $node = [
                    'interface' => "gpon-onu_{$cleanIface}",
                    'nama_pelanggan' => $matchedReg?->nama_pelanggan ?? 'Belum Terdata di Registrasi',
                    'nomor_internet' => $matchedReg?->nomor_internet ?? '-',
                    'olt' => "{$olt->name_olt} ({$kode})",
                    'port_pon' => $item['port'] ?? '-',
                    'redaman' => $isLos ? 'No Signal (LOS)' : ($item['rx_power'] ?? '-'),
                    'deskripsi' => $isLos ? 'Kabel optik putus / redaman loss' : 'Perangkat modem padam / power off',
                    'waktu_mulai' => $item['waktu_mulai'] ?? ($item['since'] ?? 'Live realtime'),
                    'durasi' => $item['durasi'] ?? ($item['duration'] ?? '-'),
                    'status' => $isLos ? 'LOS' : 'DOWN',
                ];

                if ($isLos) {
                    $losNodes[] = $node;
                } else {
                    $downNodes[] = $node;
                }
            }
        }

        $openTicketsCount = DB::table('trx_tiket_gangguan')
            ->where('hide', 0)
            ->whereNotIn('status', ['close', 'selesai', 'done', '14'])
            ->count();

        return [
            'keterangan' => 'Data real-time telemetri fisik link OLT & ONU IMS2 (cache non-blocking)',
            'summary' => [
                'total_active' => count($losNodes) + count($downNodes),
                'total_los' => count($losNodes),
                'total_down' => count($downNodes),
                'total_tickets' => $openTicketsCount,
            ],
            'onu_los_nodes' => $losNodes,
            'onu_down_nodes' => array_slice($downNodes, 0, 15),
        ];
    }

    /**
     * Call Google Gemini API (v1beta) using Laravel Http client.
     */
    protected function callGeminiApi(string $apiKey, string $model, string $userMessage, array $history, array $contextData, $user): string
    {
        $userName = $user?->nama ?? $user?->nama_lengkap ?? $user?->username ?? 'Staff IMS';
        $userRole = $this->getUserRole($user);
        $today = now()->format('d-m-Y');
        $roleRules = $this->getRoleInstructionRules($userRole);

        $systemInstruction = "Anda adalah IMS AI Copilot, asisten resmi sistem internal ISP PT Media Solusi Network (IMS2).\n"
            . "User yang sedang berbicara: {$userName} (Role/Divisi: {$userRole}).\n\n"
            . "PRINSIP UTAMA: JAWABAN TO THE POINT, PADAT, DAN LANGSUNG PADA INTI:\n"
            . "1. DILARANG menggunakan basa-basi, pengantar klise ('Tentu saya bantu', 'Halo...', 'Berdasarkan data yang ada...') atau penutup basa-basi ('Semoga membantu...', 'Ada yang bisa dibantu lagi?').\n"
            . "2. LANGSUNG sajikan data, kesimpulan, atau tabel fakta pada kalimat pertama.\n"
            . "3. Bahasa harus ringkas, jelas, mudah dipahami, dan langsung dapat dieksekusi oleh staf/teknisi lapangan.\n"
            . "4. Gunakan bullet points atau tabel markdown yang rapi dengan `font-mono` untuk ID internet, IP, port OLT, dan kode billing.\n\n"
            . "PEMAHAMAN ALUR KERJA SISTEM & DATABASE IMS2:\n"
            . "A. SIKLUS PELANGGAN (trx_batchjob_register):\n"
            . "   - Tabel pelanggan: `trx_batchjob_register` (hide=0). Status reg (status_reg): 20=Aktif/Online, 12=Survey, 16=Instalasi, 23=Terminasi/Berhenti. is_suspend=1 (Terisolir).\n"
            . "   - Kolom penting: `nomor_internet`, `nama_pelanggan`, `alamat_pasang`, `paket_layanan`, `date_create`.\n"
            . "B. SIKLUS PERANGKAT & JARINGAN FTTH (GPON):\n"
            . "   - Infrastruktur: Router POP/BGP -> Master OLT (m_olt: O1 Kayu Agung, O2 Babakan Tarogong, O3 Soreang) -> Port PON -> Spliter -> Dropcore -> Modem ONU pelanggan (index_olt: format gpon-onu_1/{slot}/{port}:{onu_id}).\n"
            . "   - Status Fisik: UP (Online Normal), LOS (Loss of Signal / Kabel optik putus fisik / redaman hilang), DOWN (Modem padam / mati lampu / dying gasp).\n"
            . "   - Jika index_olt kosong (NULL): Jelaskan langsung singkat bahwa port GPON fisik belum diisi di registrasi pelanggan sehingga sensor optik belum bisa membaca redaman.\n"
            . "   - Monitoring Realtime: Data [noc_telemetry] adalah sumber data telemetri fisik OLT terkini. Jika ditanya 'user yang loss/down', laporkan dari telemetri ini.\n"
            . "C. SIKLUS BILLING & KEUANGAN (trx_billing_layanan):\n"
            . "   - Tagihan bulanan dicatat di trx_billing_layanan (hide=0).\n"
            . "   - Periode: `bulan_tagihan` ('01' s/d '12'), `tahun_tagihan` (misal '2026').\n"
            . "   - Status Tagihan (status_bill_lay):\n"
            . "     * 15 = LUNAS / PAID (Pemasukan uang kas masuk / pembayaran selesai).\n"
            . "     * 14 = PENDING VERIFIKASI (Bukti bayar diupload, menunggu diverifikasi tim finance).\n"
            . "     * 13 = BELUM BAYAR / UNPAID (Tagihan terbit belum terbayar / piutang).\n"
            . "     * 12 = PUBLISH (Tagihan sudah diterbitkan ke pelanggan).\n"
            . "     * 11 = DRAFT.\n"
            . "   - ATURAN MENGHITUNG KEUANGAN & PEMASUKAN:\n"
            . "     * 'Total uang pemasukan / pembayaran lunas / status paid': Hitung HANYA tagihan dengan `status_bill_lay = 15` (SUM total_layanan).\n"
            . "     * 'Total seluruh tagihan / total billing / seluruh pembayaran terbit': Hitung SEMUA invoice (semua status) yang terbit pada periode tersebut.\n"
            . "     * 'Total tunggakan / belum bayar': Hitung status_bill_lay in (11, 12, 13).\n"
            . "     * Rujukan utama ada di context `[rekap_keuangan_billing]`. Jika data agregat untuk bulan/tahun yang ditanyakan sudah ada di context, gunakan data resmi tersebut. Jika belum ada atau butuh query khusus, panggil tool `run_select_query` dengan `SUM(total_layanan)` dan `COUNT(*)`. DILARANG KERAS menyimpulkan total perusahaan dari cuplikan sampel baris invoice!\n"
            . "D. SIKLUS TIKET GANGGUAN & HELPDESK (trx_tiket_gangguan):\n"
            . "   - Tiket masuk ke trx_tiket_gangguan -> Status: Baru Open (#11) -> Terjadwal/On Schedule (#12) -> Diproses -> Selesai (#13/Selesai).\n"
            . "   - Kategori: Gangguan Sinyal/Kabel Putus, Internet Lambat, Ubah Password, Relokasi Rumah, Ubah Paket Layanan.\n"
            . "E. JADWAL OTOMATIS & PROSEDUR OPERASIONAL (SOP) IMS2:\n"
            . "   - Terbit Invoice Tagihan Bulanan Massal: Setiap tanggal 1 pukul 08:00 WIB pagi (cron `finance:auto-generate-invoices` otomatis membuat invoice pelanggan aktif).\n"
            . "   - Jatuh Tempo Tagihan: Tanggal 25 setiap bulan.\n"
            . "   - Terbit Request Suspend / Isolir Otomatis: Setiap tanggal 25 pukul 06:00 WIB pagi (cron `suspend:unpaid-customers` otomatis menyaring dan membuat antrean request suspend ke tim NOC untuk semua pelanggan yang belum lunas/status_bill_lay != 15).\n"
            . "   - Eksekusi Suspend: Diteruskan ke tim NOC/MikroTik berdasarkan antrean tabel `trx_suspend` (status 11=Req Suspend, 12=Suspend Aktif).\n"
            . "   - Unsuspend / Buka Isolir: Otomatis diajukan seketika verifikasi bukti bayar disetujui tim Finance (#14 -> #15) atau manual oleh admin/finance.\n\n"
            . "KEBIJAKAN HAK AKSES ROLE (SANGAT KETAT):\n"
            . "{$roleRules}\n\n"
            . "5. INTEGRITAS DATA PELANGGAN & TAGIHAN (SANGAT KRUSIAL):\n"
            . "   - DILARANG KERAS menghubungkan, mengaitkan, atau menampilkan invoice/tagihan milik orang lain kepada pelanggan yang sedang ditanyakan.\n"
            . "   - Periksa selalu 'nomor_internet' pada setiap baris di [billings] atau [billings_sample]. Jika tidak cocok dengan pelanggan yang ditanyakan, atau jika kosong/berisi tagihan orang lain, nyatakan dengan jujur dan jelas: Pelanggan tersebut TIDAK MEMILIKI TAGIHAN TERTUNGGAK.\n"
            . "   - Dilarang keras berasumsi atau mengarang nomor internet dan invoice.\n\n"
            . "Jika pengguna meminta data di luar wewenangnya, tolak langsung secara singkat, tegas, dan sopan tanpa berputar-putar.\n"
            . "Hari ini: {$today}.";

        // Build contents array for Gemini ensuring strict user -> model -> user alternating turns
        $contents = [];

        // Exclude the last message from history if it is the current user message
        if (!empty($history)) {
            $lastHistory = end($history);
            if (($lastHistory['role'] ?? '') === 'user' && trim($lastHistory['text'] ?? '') === trim($userMessage)) {
                array_pop($history);
            }
        }

        // Add relevant past conversation history (up to last 4 messages to save tokens)
        $recentHistory = array_slice($history, -4);
        foreach ($recentHistory as $msg) {
            $role = ($msg['role'] ?? 'user') === 'user' ? 'user' : 'model';
            $text = trim($msg['text'] ?? '');
            if (!empty($text)) {
                // Strip failover notes from past assistant responses so they don't pollute Gemini context
                $cleanText = preg_replace('/\n*---\n*> ℹ️ \*Info Failover:[\s\S]*$/i', '', $text);
                if (!empty($cleanText)) {
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $cleanText]],
                    ];
                }
            }
        }

        // Prepare context injection for the current prompt
        $contextPrompt = "Data internal terkini dari sistem IMS2 terkait pertanyaan pengguna:\n" . json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $currentUserPrompt = "Pertanyaan User: {$userMessage}\n\n[Konteks Database IMS2]:\n{$contextPrompt}";

        // Ensure alternating turns: if the last element in $contents is 'user', remove it to avoid Google HTTP 400
        while (!empty($contents) && end($contents)['role'] === 'user') {
            array_pop($contents);
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $currentUserPrompt]],
        ];

        // 1. Candidate models to try (Using user-specified gemini-3.8-flash)
        if (empty($model) || str_contains($model, 'transcribe') || str_contains($model, '2.0-flash')) {
            $model = 'gemini-3.8-flash';
        }
        $defaultCandidates = ['gemini-3.8-flash', 'gemini-3.8-pro', 'gemini-3.8'];
        $modelsToTry = array_values(array_unique(array_filter(array_merge([$model], $defaultCandidates))));
        $modelsToTry = array_slice($modelsToTry, 0, 2);

        // 2. Iterate through candidate models with fast per-step timeout
        $triedModels = [];
        $lastError = 'Semua model Gemini yang dicoba gagal merespon.';

        // Detect if query is purely conversational greeting/thanks (no database data lookup needed)
        $lowerMsg = strtolower($userMessage);
        $isPurelyConversational = preg_match('/^(halo|hai|hi|hey|selamat (pagi|siang|sore|malam)|assalamu|terima kasih|makasih|thanks|kamu siapa|bisa apa|menu|help)\b/i', trim($lowerMsg)) 
            && !preg_match('/\b(berapa|total|cari|cek|data|pelanggan|tagihan|billing|tiket|olt|bayar|lunas|unpaid|pemasukan|pendapatan|omset|oktober|september|agustus|juli|juni|mei|april|maret|februari|januari|select|query|tabel|kolom)\b/i', $lowerMsg);

        // Detect if query is asking about SOP/policies/schedules/knowledge where database SQL query tools are NOT needed
        $isSopOrPolicy = preg_match('/\b(kapan|jadwal|tanggal berapa|jam berapa|aturan|sop|prosedur|kebijakan|syarat|jatuh tempo|terbit|suspend terbit|auto suspend|generate invoice|invoice terbit|request suspend|bisa apa|wewenang|tugas role|panduan|cara kerja|arti status)\b/i', $lowerMsg)
            && !preg_match('/\b(cari|cek pelanggan|atas nama|nomor internet|id internet|select|query|tabel|kolom|\d{5,})\b/i', $lowerMsg);

        $tools = ($isPurelyConversational || $isSopOrPolicy) ? [] : [['functionDeclarations' => [
            [
                'name' => 'describe_table',
                'description' => 'Lihat daftar kolom sebuah tabel database IMS2 beserta 1 contoh baris. Panggil sebelum menulis query pada tabel yang belum dikenal.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['table' => ['type' => 'STRING', 'description' => 'Nama tabel']], 'required' => ['table']],
            ],
            [
                'name' => 'run_select_query',
                'description' => 'Jalankan SATU query SELECT read-only (MySQL) ke database IMS2 dan kembalikan barisnya (maks 50). Gunakan LIKE untuk pencarian nama, JOIN bila perlu, COUNT/SUM untuk agregasi.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['sql' => ['type' => 'STRING', 'description' => 'Query SELECT tunggal tanpa titik koma']], 'required' => ['sql']],
            ],
        ]]];

        if (empty($tools)) {
            if ($isPurelyConversational) {
                $systemInstruction .= "\n\nCATATAN PERMINTAAN INI: Percakapan sapaan. DILARANG menulis query SQL. Balas sapaan singkat 1-2 kalimat dan tawarkan bantuan sesuai role.";
            } else {
                $systemInstruction .= "\n\nCATATAN PERMINTAAN INI: Pertanyaan SOP/Jadwal/Konseptual. Jawab langsung secara presisi dan padat dari pengetahuan sistem di atas tanpa memanggil database.";
            }
        } else {
            $systemInstruction .= "\n\n" . $this->schemaHint($userRole);
        }

        $firstError = null;
        for ($mIdx = 0; $mIdx < count($modelsToTry) && $mIdx < 3; $mIdx++) {
            $mName = $modelsToTry[$mIdx];
            $triedModels[] = $mName;
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$mName}:generateContent?key={$apiKey}";
            $convo = $contents;

            try {
                for ($step = 0; $step < 5; $step++) {
                    $stepTools = ($step >= 4) ? [] : $tools;
                    $genConfig = [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1200,
                        'thinkingConfig' => [
                            'thinkingBudget' => 0,
                        ],
                    ];
                    $payload = [
                        'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                        'contents' => $convo,
                        'generationConfig' => $genConfig,
                    ];
                    if (!empty($stepTools)) {
                        $payload['tools'] = $stepTools;
                    }

                    $response = Http::withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CONNECTTIMEOUT => 4,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => 0,
                        ],
                    ])->timeout(12)->post($url, $payload);

                    // Automatic fallback if specific model does not support thinkingConfig
                    if (!$response->successful() && str_contains($response->body(), 'thinkingConfig')) {
                        unset($payload['generationConfig']['thinkingConfig']);
                        $response = Http::withOptions([
                            'curl' => [
                                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CONNECTTIMEOUT => 4,
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_SSL_VERIFYHOST => 0,
                            ],
                        ])->timeout(12)->post($url, $payload);
                    }

                    if (!$response->successful()) {
                        $errMessage = $response->json('error.message') ?? ('HTTP ' . $response->status());
                        $lastError = $errMessage;
                        $statusCode = $response->status();
                        Log::error("[AiAssistant] Gemini call to {$mName} failed [HTTP {$statusCode}]: {$errMessage}");
                        $firstError ??= "[{$mName}] {$errMessage}";

                        // 1. Quota / Rate Limit (HTTP 429 or RESOURCE_EXHAUSTED):
                        // Google Cloud quotas apply at the API key / GCP project level.
                        // Continuing to try other models on the SAME throttled key will fail and delay the response.
                        // Mark key cooldown (60s) and immediately throw so chat() switches to the next API key.
                        $is429 = $statusCode === 429 
                            || str_contains($errMessage, '429') 
                            || str_contains($errMessage, 'RESOURCE_EXHAUSTED') 
                            || str_contains(strtolower($errMessage), 'quota');

                        if ($is429) {
                            if ($mIdx === 0) {
                                Cache::put('gemini_key_cooldown_' . md5($apiKey), now()->addSeconds(60)->timestamp, 60);
                            }
                            throw new \Exception("Limit kuota/frekuensi Google (429) [{$mName}]: " . $errMessage);
                        }

                        // 2. Auth error (HTTP 400 invalid key / 403 permission denied):
                        // The key itself is invalid or has restricted permissions; other models will also fail.
                        $isAuthError = ($statusCode === 400 && str_contains(strtolower($errMessage), 'api_key')) || $statusCode === 403;
                        if ($isAuthError) {
                            throw new \Exception("Autentikasi API Key gagal ({$statusCode}): " . $errMessage);
                        }

                        // 3. High demand spike: pause 800ms and retry once on step 0
                        if (str_contains(strtolower($errMessage), 'high demand') && $step === 0) {
                            usleep(800000);
                            $retryRes = Http::withOptions([
                                'curl' => [
                                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CONNECTTIMEOUT => 4,
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_SSL_VERIFYHOST => 0,
                                ],
                            ])->timeout(10)->post($url, $payload);
                            if ($retryRes->successful()) {
                                $response = $retryRes;
                                goto process_step_response;
                            }
                        }

                        // 4. Model not found (404) or unsupported: proceed to next verified candidate model
                        continue 2;
                    }
                    process_step_response:

                    $usage = $response->json('usageMetadata');
                    $this->recordUsage($apiKey, $usage);

                    $parts = $response->json('candidates.0.content.parts') ?? [];
                    $calls = array_values(array_filter($parts, fn($p) => isset($p['functionCall'])));

                    if (empty($calls)) {
                        $text = trim(implode('', array_map(fn($p) => empty($p['thought']) ? ($p['text'] ?? '') : '', $parts)));
                        if ($text === '') {
                            $text = trim(implode('', array_map(fn($p) => $p['text'] ?? '', $parts)));
                        }
                        if ($text !== '') {
                            Cache::forget('gemini_key_cooldown_' . md5($apiKey));
                            Cache::put('ims_ai_working_model', $mName, 86400);
                            return $text;
                        }
                        $lastError = 'Model mengembalikan respons kosong.';
                        continue 2;
                    }

                    $convo[] = ['role' => 'model', 'parts' => $parts];
                    $results = [];
                    foreach ($calls as $p) {
                        $results[] = ['functionResponse' => [
                            'name' => $p['functionCall']['name'],
                            'response' => ['result' => $this->runAiTool($p['functionCall']['name'], $p['functionCall']['args'] ?? [], $user)],
                        ]];
                    }
                    $convo[] = ['role' => 'user', 'parts' => $results];
                    usleep(300000); // 300ms pacing between function call steps to prevent Google 15 RPM burst limit
                }
                $lastError = 'Terlalu banyak langkah query database.';
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $firstError ??= "[{$mName}] " . $lastError;
                $isNetworkIssue = str_contains($lastError, 'timed out') || str_contains($lastError, 'cURL error 28') || str_contains($lastError, 'could not resolve');
                if ($isNetworkIssue || ($mIdx === 0 && (str_contains($lastError, 'Limit kuota') || str_contains($lastError, 'Autentikasi')))) {
                    throw $e;
                }
            }
        }

        throw new \Exception($firstError ?? $lastError);
    }

    public function getUserRole($user): string
    {
        if (!$user) {
            return 'Staff';
        }

        if (method_exists($user, 'isDirektur') && $user->isDirektur()) {
            return 'Direktur';
        }
        if (method_exists($user, 'isCeo') && $user->isCeo()) {
            return 'CEO';
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 'Admin';
        }
        if (method_exists($user, 'isFinance') && $user->isFinance()) {
            return 'Finance';
        }
        if (method_exists($user, 'isNoc') && $user->isNoc()) {
            return 'NOC';
        }
        if (method_exists($user, 'isTeknik') && $user->isTeknik()) {
            return 'Teknik';
        }

        $namaLevel = strtolower($user->level?->nama_level ?? $user->nama_level ?? '');
        if (str_contains($namaLevel, 'customer')) {
            return 'Customer Service';
        }
        if (str_contains($namaLevel, 'sales') || str_contains($namaLevel, 'salses') || str_contains($namaLevel, 'mitra')) {
            return 'Sales & Marketing';
        }
        if (str_contains($namaLevel, 'gudang')) {
            return 'Gudang';
        }
        if (str_contains($namaLevel, 'human') || str_contains($namaLevel, 'hrd')) {
            return 'HRD';
        }
        if (str_contains($namaLevel, 'legal')) {
            return 'Legal';
        }

        return $user->level?->nama_level ?? $user->nama_level ?? 'Staff';
    }

    public function canAccessFinance($user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['Direktur', 'CEO', 'Admin', 'Finance', 'Customer Service'], true);
    }

    public function canAccessNetwork($user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['Direktur', 'CEO', 'Admin', 'NOC', 'Teknik'], true);
    }

    public function canAccessHr($user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['Direktur', 'CEO', 'Admin', 'HRD'], true);
    }

    protected function isTableForbiddenForRole(string $tableOrSql, $user): ?string
    {
        $role = $this->getUserRole($user);
        if (in_array($role, ['Direktur', 'CEO', 'Admin'], true)) {
            return null; // Full Access
        }

        $lower = strtolower($tableOrSql);

        // 1. Finance tables
        $financeTables = ['trx_billing_layanan', 'payment_confirmations', 'tb_rekening', 'tb_bank', 'tb_kas', 'tb_jurnal', 'tb_keuangan', 'tb_pembukuan', 'rekap_billing'];
        foreach ($financeTables as $ft) {
            if (preg_match('/\b' . preg_quote($ft, '/') . '\b/i', $lower)) {
                if ($role === 'Customer Service') {
                    if ($ft !== 'trx_billing_layanan') {
                        return "Akses Ditolak: Role 'Customer Service' hanya diizinkan melihat status tagihan pelanggan, bukan buku kas/rekening internal perusahaan.";
                    }
                } elseif ($role !== 'Finance') {
                    return "Akses Ditolak: Role Anda ({$role}) tidak memiliki izin mengakses data keuangan/tagihan. Domain ini khusus untuk divisi Finance & Direksi.";
                }
            }
        }

        // 2. Core Network & OLT tables
        $networkTables = ['m_olt', 'm_router', 'm_port_olt', 'tb_router', 'trx_device_olt'];
        foreach ($networkTables as $nt) {
            if (preg_match('/\b' . preg_quote($nt, '/') . '\b/i', $lower)) {
                if (!in_array($role, ['NOC', 'Teknik'], true)) {
                    return "Akses Ditolak: Role Anda ({$role}) tidak memiliki izin mengakses data infrastruktur perangkat OLT/Router. Domain ini khusus untuk divisi NOC & Teknik.";
                }
            }
        }

        // 3. HR / Payroll tables
        $hrTables = ['tb_karyawan', 'tb_gaji', 'tb_payroll', 'tb_absensi', 'tb_pengguna'];
        foreach ($hrTables as $ht) {
            if (preg_match('/\b' . preg_quote($ht, '/') . '\b/i', $lower) && $role !== 'HRD') {
                return "Akses Ditolak: Role Anda ({$role}) tidak memiliki izin mengakses data kepegawaian/gaji.";
            }
        }

        return null;
    }

    protected function getRoleInstructionRules(string $role): string
    {
        switch ($role) {
            case 'Direktur':
            case 'CEO':
            case 'Admin':
                return "- Hak Akses: PENUH (Superadmin). Anda boleh mengakses seluruh domain: Finance, Pelanggan, OLT/Jaringan, Tiket, Karyawan, dan Rekapitulasi Global.";

            case 'Finance':
                return "- Hak Akses: Divisi FINANCE.\n"
                    . "- DIIZINKAN: Data tagihan (trx_billing_layanan), verifikasi pembayaran (status 14), invoice lunas (status 15), pencarian pelanggan terkait billing, dan rekap keuangan.\n"
                    . "- DILARANG KERAS: Konfigurasi teknis internal core OLT/Router, redaman optik teknis, dan data rahasia HR personal. Tolak permintaan teknis secara sopan.";

            case 'NOC':
            case 'Teknik':
                return "- Hak Akses: Divisi TEKNIK & NOC (Infrastruktur Jaringan & Lapangan - Setara & Sama).\n"
                    . "- DIIZINKAN (SAMA): Status OLT, ONU, port GPON (index_olt), redaman optik (Rx/Tx dBm), status PPPoE online/offline, traffic, router, IP address, data pelanggan teknis/lapangan (alamat pasang, paket, nomor internet, status instalasi/aktif/suspend), tiket perbaikan & gangguan teknis, serta inventori perangkat teknis.\n"
                    . "- SUMBER DATA LOSS / DOWN: Untuk pertanyaan seputar 'user yang loss', 'siapa saja yang loss', atau 'perangkat down', gunakan data [noc_telemetry] (Monitoring Realtime OLT) sebagai sumber utama telemetri fisik jaringan.\n"
                    . "- DILARANG KERAS: Data keuangan perusahaan, tagihan billing pembukuan, rekening bank perusahaan, omset/pendapatan, laba-rugi, dan verifikasi pembayaran. Jika ditanya hal terkait keuangan/tagihan/omset, tolak secara sopan: jelaskan bahwa data keuangan hanya dapat diakses oleh divisi Finance.";

            case 'Customer Service':
                return "- Hak Akses: Divisi CUSTOMER SERVICE.\n"
                    . "- DIIZINKAN: Data profil pelanggan, paket internet, status tiket komplain/gangguan, dan status pembayaran umum tagihan pelanggan (untuk klarifikasi pelanggan).\n"
                    . "- DILARANG: Laporan keuangan global perusahaan, mutasi rekening bank internal, pembukuan finance, dan konfigurasi OLT internal.";

            case 'Sales & Marketing':
            case 'MITRA':
                return "- Hak Akses: Divisi SALES & MARKETING / MITRA.\n"
                    . "- DIIZINKAN: Calon pelanggan baru, status registrasi baru (draft, survey, instalasi, aktivasi), brosur paket.\n"
                    . "- DILARANG: Data pembukuan keuangan perusahaan dan konfigurasi jaringan OLT/NOC.";

            case 'Gudang':
                return "- Hak Akses: Divisi GUDANG / LOGISTIK.\n"
                    . "- DIIZINKAN: Data inventori perangkat, stok modem ONT, kabel dropcore, dan mutasi barang.\n"
                    . "- DILARANG: Data keuangan pembukuan dan konfigurasi jaringan.";

            case 'HRD':
                return "- Hak Akses: Divisi HUMAN RESOURCE (HRD).\n"
                    . "- DIIZINKAN: Data karyawan, absensi, dan data personal kepegawaian.\n"
                    . "- DILARANG: Konfigurasi teknis OLT dan mutasi bank operasional.";

            default:
                return "- Hak Akses: STAFF UMUM.\n"
                    . "- Hanya diizinkan melihat informasi operasional dasar umum sesuai tugas.";
        }
    }

    protected function schemaHint(string $userRole = 'Staff'): string
    {
        $tables = Cache::remember('ai_copilot_tables', 3600, function () {
            try {
                return array_map(fn($r) => array_values((array)$r)[0], DB::select('SHOW TABLES'));
            } catch (\Throwable $e) {
                return [];
            }
        });

        // Filter out tables that are completely forbidden for this role
        if (!in_array($userRole, ['Direktur', 'CEO', 'Admin'], true)) {
            $tables = array_values(array_filter($tables, function ($t) use ($userRole) {
                if (($userRole === 'NOC' || $userRole === 'Teknik') && in_array($t, ['trx_billing_layanan', 'payment_confirmations', 'tb_rekening', 'tb_bank', 'tb_kas', 'tb_jurnal'])) {
                    return false;
                }
                if ($userRole === 'Finance' && in_array($t, ['m_olt', 'm_router', 'm_port_olt', 'tb_router'])) {
                    return false;
                }
                if ($userRole !== 'HRD' && in_array($t, ['tb_gaji', 'tb_payroll'])) {
                    return false;
                }
                return true;
            }));
        }

        $tableSample = count($tables) > 35 ? (implode(', ', array_slice($tables, 0, 35)) . ' (dan tabel terkait lainnya)') : implode(', ', $tables);
        return "KEMAMPUAN DATABASE & TOOLS (`describe_table`, `run_select_query`):\n"
            . "Anda memiliki hak akses read-only untuk mengeksekusi query database IMS2 sesuai wewenang role {$userRole}.\n"
            . "- Tagihan & Keuangan (`trx_billing_layanan`): kolom `bulan_tagihan` ('01'-'12'), `tahun_tagihan` ('2026'), `total_layanan` (rupiah), `status_bill_lay` (15=LUNAS/PEMASUKAN, 14=PENDING VERIFIKASI, 13=BELUM BAYAR/UNPAID, 12=PUBLISH, 11=DRAFT), `payment_paid`, `amount_paid`.\n"
            . "- Pelanggan (`trx_batchjob_register`): kolom `nomor_internet`, `nama_pelanggan`, `alamat_pasang`, `paket_layanan`, `status_reg` (20=online/aktif, 12=survey, 16=instalasi, 23=terminasi), `is_suspend` (1=terisolir), `date_create`.\n"
            . "- Tiket Gangguan (`trx_tiket_gangguan`): kolom `tiket`, `nomor_internet`, `keluhan`, `status`, `kat_tiket`, `penanganan`, `date_create`.\n"
            . ($this->canAccessNetworkRole($userRole) ? "- OLT & Jaringan (`m_olt`): kolom `kode_olt`, `name_olt`, `ip_address`, `brand`, `model`, `is_active`.\n" : "")
            . "Daftar tabel: " . $tableSample . ".\n"
            . "ATURAN UTAMA: Jika user menanyakan data spesifik, jumlah, atau agregasi (COUNT, SUM) yang belum tersedia di context, gunakan tool `run_select_query` untuk mengecek database riil secara langsung!";
    }

    protected function canAccessFinanceRole(string $role): bool
    {
        return in_array($role, ['Direktur', 'CEO', 'Admin', 'Finance', 'Customer Service'], true);
    }

    protected function canAccessNetworkRole(string $role): bool
    {
        return in_array($role, ['Direktur', 'CEO', 'Admin', 'NOC', 'Teknik'], true);
    }

    protected function runAiTool(string $name, array $args, $user = null): array
    {
        try {
            if ($name === 'describe_table') {
                $table = (string)($args['table'] ?? '');
                if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !Schema::hasTable($table)) {
                    return ['error' => "Tabel '{$table}' tidak ditemukan."];
                }
                if ($forbidden = $this->isTableForbiddenForRole($table, $user)) {
                    return ['error' => $forbidden];
                }
                $sample = DB::table($table)->limit(1)->get()->map(fn($r) => $this->maskSensitive((array)$r))->first();
                return [
                    'columns' => array_values(array_filter(Schema::getColumnListing($table), fn($c) => !$this->isSensitive($c))),
                    'sample_row' => $sample,
                ];
            }

            if ($name === 'run_select_query') {
                $sql = trim(rtrim(trim((string)($args['sql'] ?? '')), ';'));
                $bare = preg_replace("/'(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\"/", "''", $sql);

                if (!preg_match('/^select\b/i', $sql)
                    || preg_match('/;|--|#|\/\*/', $bare)
                    || preg_match('/\b(insert|update|delete|drop|alter|truncate|create|grant|revoke|replace|call|exec|handler|load_file|outfile|dumpfile|sleep|benchmark|information_schema|mysql\.|for\s+update)\b/i', $bare)) {
                    return ['error' => 'Hanya satu query SELECT read-only yang diizinkan.'];
                }
                if ($forbidden = $this->isTableForbiddenForRole($bare, $user)) {
                    return ['error' => $forbidden];
                }
                if (preg_match('/' . $this->sensitivePattern() . '/i', $bare)) {
                    return ['error' => 'Query menyentuh kolom sensitif (password/token/kredensial) dan ditolak.'];
                }
                if (!preg_match('/\blimit\s+\d+/i', $bare)) {
                    $sql .= ' LIMIT 50';
                }

                $rows = array_slice(DB::select($sql), 0, 50);
                $rows = array_map(fn($r) => $this->maskSensitive((array)$r), $rows);
                $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (strlen($json) > 15000) {
                    return ['row_count' => count($rows), 'note' => 'Hasil terlalu besar, dipotong. Persempit kolom/filter.', 'rows' => array_slice($rows, 0, 10)];
                }
                return ['row_count' => count($rows), 'rows' => $rows];
            }

            return ['error' => "Tool '{$name}' tidak dikenal."];
        } catch (\Throwable $e) {
            return ['error' => 'Query gagal: ' . $e->getMessage()];
        }
    }

    protected function sensitivePattern(): string
    {
        return '\b\w*(pass|passwd|secret|token|api_key|ont_ps|pppoe_password|enable_password|remember)\w*\b';
    }

    protected function isSensitive(string $column): bool
    {
        return (bool)preg_match('/' . $this->sensitivePattern() . '/i', $column);
    }

    protected function maskSensitive(array $row): array
    {
        return array_filter($row, fn($v, $k) => !$this->isSensitive((string)$k), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Instantly answer deterministic operational SOP questions and conversational chit-chat
     * without external network delay or timeout risks.
     */
    protected function checkDirectAnswerResponse(string $userMessage, array $contextData, $currentUser): ?string
    {
        $lower = strtolower(trim($userMessage));
        $userName = $currentUser?->nama ?? $currentUser?->nama_lengkap ?? $currentUser?->username ?? 'Rekan IMS';

        // 1. Gender / Identitas Pribadi AI (e.g. "kamu cowo atau cewe?", "gender kamu apa?")
        if (preg_match('/\b(cowo|cewe|cowok|cewek|pria|wanita|laki|perempuan|gender|jenis kelamin|kelamin)\b/i', $lower)
            && preg_match('/\b(kamu|lu|anda|lu tuh|kamu tuh|dirimu|kau)\b/i', $lower)) {
            return "Saya adalah asisten virtual berbasis kecerdasan buatan (AI) sistem IMS2, jadi saya **tidak memiliki jenis kelamin (bukan cowok maupun cewek)**. 🤖✨\n\n"
                . "Saya diprogram khusus untuk mendampingi rekan-rekan PT Media Solusi Network (NOC, Finance, Teknik, dan Manajemen) dalam mengelola operasional ISP dengan cepat dan efisien.\n\n"
                . "Ada data pelanggan, tagihan, atau perangkat jaringan yang ingin Anda cek hari ini, **{$userName}**? 😊";
        }

        // 2. Siapa Kamu / Identitas Sistem
        if (preg_match('/\b(siapa kamu|kamu siapa|siapa anda|anda siapa|nama kamu|siapa namamu|kamu itu apa|identitasmu)\b/i', $lower)) {
            return "Halo **{$userName}**! 👋 Saya adalah **IMS AI Copilot**, asisten pintar resmi untuk sistem operasional PT Media Solusi Network (IMS2).\n\n"
                . "Saya dirancang untuk membantu Anda memantau dan mengaudit data operasional seperti:\n"
                . "- 👤 Pencarian profil pelanggan, status registrasi, dan port OLT\n"
                . "- 💳 Audit tagihan, piutang, dan verifikasi bukti bayar Finance\n"
                . "- 🌐 Monitoring telemetri fisik OLT, modem LOS/DOWN, dan port GPON\n"
                . "- 🎫 Pemantauan tiket komplain dan eskalasi gangguan helpdesk";
        }

        // 3. Siapa Pembuat / Pencipta
        if (preg_match('/\b(pembuatmu|penciptamu|yang buat kamu|yang bikin kamu|dibuat oleh|diciptakan oleh)\b/i', $lower)) {
            return "Saya dikembangkan secara internal oleh **Tim Developer PT Media Solusi Network** untuk mempercepat otomasi sistem, integrasi telemetri OLT, dan pendampingan kerja seluruh divisi di IMS2. 🚀";
        }

        // 4. Tanya Kabar
        if (preg_match('/\b(apa kabar|gimana kabarnya|gimana kabar|sehat gak|sehat|kabarmu)\b/i', $lower)
            && preg_match('/\b(kamu|anda|copilot|ims)\b/i', $lower)) {
            return "Kabar saya sangat baik dan seluruh sensor sistem IMS2 siap siaga membantu Anda! 🟢\n\nBagaimana kabar Anda hari ini, **{$userName}**? Ada yang bisa saya bantu periksa di sistem?";
        }

        // 5. Ucapan Terima Kasih
        if (preg_match('/^(terima kasih|makasih|thanks|thank you|tengkyu|arigato|nuhun)\b/i', $lower)) {
            return "Sama-sama, **{$userName}**! Senang bisa membantu Anda. Jika ada data atau kendala operasional lain yang perlu ditelusuri, silakan tanyakan kapan saja! 😊";
        }

        // 6. Jadwal Suspend / Isolir / Cron
        $isAskingSuspendSchedule = (str_contains($lower, 'suspend') || str_contains($lower, 'isolir')) 
            && (str_contains($lower, 'kapan') || str_contains($lower, 'jadwal') || str_contains($lower, 'tanggal berapa') || str_contains($lower, 'jam berapa') || str_contains($lower, 'terbit') || str_contains($lower, 'otomatis') || str_contains($lower, 'auto'));

        if ($isAskingSuspendSchedule && empty($contextData['customers'])) {
            return "### ⏱️ Jadwal Otomatis & Prosedur Suspend (Isolir) IMS2:\n\n"
                . "Berdasarkan sistem penjadwalan resmi (*Scheduler Cron*) IMS2:\n\n"
                . "1. **📅 Terbit Request Suspend Otomatis:**\n"
                . "   - Request suspend otomatis diterbitkan setiap **tanggal 25 setiap bulan pada pukul 06:00 WIB pagi**.\n"
                . "   - Sistem menjalankan perintah cron `suspend:unpaid-customers` untuk menyaring seluruh pelanggan aktif yang tagihannya belum berstatus lunas (`status_bill_lay != 15`).\n\n"
                . "2. **🧾 Jadwal Terbit Tagihan (Invoice Bulanan):**\n"
                . "   - Invoice massal bulanan diterbitkan otomatis setiap **tanggal 1 setiap bulan pada pukul 08:00 WIB pagi** (`finance:auto-generate-invoices`).\n\n"
                . "3. **⚠️ Batas Waktu Jatuh Tempo:**\n"
                . "   - Pelanggan diberikan rentang pembayaran mulai **tanggal 1 hingga tanggal 24**.\n\n"
                . "4. **🔄 Alur Eksekusi & Buka Isolir (Unsuspend):**\n"
                . "   - Request suspend masuk ke antrean tabel `trx_suspend` (status `#11`) untuk dieksekusi pemutusan/isolir oleh tim NOC pada MikroTik/OLT.\n"
                . "   - **Buka Isolir (Unsuspend):** Terbit otomatis seketika tim Finance memverifikasi dan menyetujui bukti pembayaran pelanggan (status `#14` $\\rightarrow$ `#15`), atau diajukan manual oleh admin/finance.";
        }

        // 7. Jadwal Terbit Invoice Bulanan
        $isAskingInvoiceSchedule = (str_contains($lower, 'tagihan') || str_contains($lower, 'invoice') || str_contains($lower, 'billing')) 
            && (str_contains($lower, 'kapan') || str_contains($lower, 'jadwal') || str_contains($lower, 'tanggal berapa') || str_contains($lower, 'jam berapa') || str_contains($lower, 'terbit') || str_contains($lower, 'generate'));

        if ($isAskingInvoiceSchedule && empty($contextData['customers']) && !str_contains($lower, 'cek') && !str_contains($lower, 'total') && !str_contains($lower, 'berapa')) {
            return "### 🧾 Jadwal Penerbitan Invoice Bulanan IMS2:\n\n"
                . "1. **📅 Jadwal Terbit:** Setiap **tanggal 1 setiap bulan pukul 08:00 WIB pagi** secara otomatis melalui command scheduler `finance:auto-generate-invoices`.\n"
                . "2. **👥 Target:** Seluruh pelanggan berstatus **Online Aktif** (`status_reg = 20`). Pelanggan berstatus survey, instalasi, atau terminasi dilewati.\n"
                . "3. **⚠️ Jatuh Tempo:** **Tanggal 25 setiap bulan pukul 06:00 WIB** (masuk antrean auto-suspend jika belum lunas).";
        }

        return null;
    }

    /**
     * Local heuristic response generator when API key is missing or service unreachable.
     */
    protected function generateLocalResponse(string $query, array $context, $user, bool $isFallback = false, ?string $errorMessage = null): string
    {
        $userName = $user?->nama ?? $user?->nama_lengkap ?? $user?->username ?? 'Staff';
        $userRole = $this->getUserRole($user);
        $lower = strtolower($query);
        $output = [];

        // Pre-check 1: Restrict Finance data
        $isAskingFinance = str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar') 
            || str_contains($lower, 'omset') || str_contains($lower, 'keuangan') || str_contains($lower, 'invoice') 
            || str_contains($lower, 'pending') || str_contains($lower, 'verif') || str_contains($lower, 'lunas');

        if ($isAskingFinance && !$this->canAccessFinance($user)) {
            $output[] = "### 🔒 Akses Ditolak (Role: {$userRole})";
            $output[] = "Akun Anda terdaftar sebagai **{$userRole}**. Akses ke informasi tagihan, verifikasi pembayaran, dan data keuangan dibatasi khusus untuk divisi **Finance** dan **Direksi**.";
            return implode("\n", $output);
        }

        // Pre-check 2: Restrict Core Network & OLT data
        $isAskingOlt = (str_contains($lower, 'olt') || str_contains($lower, 'router') || str_contains($lower, 'redaman') 
            || str_contains($lower, 'gpon') || str_contains($lower, 'optik') || str_contains($lower, 'pon')) 
            && !str_contains($lower, 'pelanggan');

        if ($isAskingOlt && !$this->canAccessNetwork($user)) {
            $output[] = "### 🔒 Akses Ditolak (Role: {$userRole})";
            $output[] = "Akun Anda terdaftar sebagai **{$userRole}**. Akses ke konfigurasi teknis perangkat OLT dan infrastruktur jaringan dibatasi khusus untuk divisi **NOC**, **Teknik**, dan **Direksi**.";
            return implode("\n", $output);
        }

        // Pre-check 3: Restrict HR data
        $isAskingHr = str_contains($lower, 'gaji') || str_contains($lower, 'salary') || str_contains($lower, 'karyawan') 
            || str_contains($lower, 'absensi') || str_contains($lower, 'hrd');

        if ($isAskingHr && !$this->canAccessHr($user)) {
            $output[] = "### 🔒 Akses Ditolak (Role: {$userRole})";
            $output[] = "Akun Anda terdaftar sebagai **{$userRole}**. Data kepegawaian dan penggajian karyawan bersifat rahasia dan dibatasi khusus untuk divisi **HRD** dan **Direksi**.";
            return implode("\n", $output);
        }

        $hasData = false;

        // Case 0: Explicit check if user asks why OLT / ONU status cannot be checked
        $isAskingWhyOlt = (str_contains($lower, 'kenapa') || str_contains($lower, 'mengapa') || str_contains($lower, 'tidak bisa') || str_contains($lower, 'gagal') || str_contains($lower, 'alasan'))
            && (str_contains($lower, 'olt') || str_contains($lower, 'redaman'));

        if ($isAskingWhyOlt && !empty($context['customers'])) {
            $c = $context['customers'][0];
            if (!$c->has_onu_port) {
                $oltName = $c->olt_name ?? ($c->olt ?: 'OLT');
                $oltIp = $c->olt_ip ?? '103.161.206.214';
                $oltStatus = $c->olt_status ?? 'Aktif';

                $output[] = "### 🔍 Penjelasan: Mengapa Status Live OLT / ONU Belum Bisa Dicek";
                $output[] = "Pengecekan fisik/telemetri *real-time* perangkat OLT untuk user `{$c->nomor_internet}` (**{$c->nama_pelanggan}**) belum dapat dilakukan secara otomatis karena:\n";
                $output[] = "1. **Index Port GPON Kosong (`index_olt = NULL`)**";
                $output[] = "   - Di arsitektur jaringan GPON (ZTE/Huawei), OLT memerlukan identitas port fisik dalam format `gpon-onu_1/{slot}/{port}:{onu_id}` (misalnya `gpon-onu_1/1/2:8`) untuk menembakkan laser telemetri dan membaca redaman sinyal optik (Rx/Tx dBm) ONU pelanggan.";
                $output[] = "   - Pada data registrasi pelanggan ini di tabel `trx_batchjob_register`, kolom **Index OLT** masih kosong (`NULL`), sehingga sistem belum mengetahui di port splitter mana kabel serat optik pelanggan ini terpasang.";
                $output[] = "2. **Status Perangkat OLT Induk**";
                $output[] = "   - Pelanggan ini terdaftar pada OLT **{$oltName}** (Host IP: `{$oltIp}`).";
                $output[] = "   - Server OLT induk terpantau **{$oltStatus}**, namun tanpa port `index_olt`, perintah telnet/SNMP pembacaan optik belum memiliki target port yang valid.\n";
                $output[] = "🛠️ **Langkah Solusi / Rekomendasi:**";
                $output[] = "- Tim Teknisi / NOC dapat melengkapi data **Index OLT** pelanggan melalui menu **Data Pelanggan > Edit Registrasi** sesuai port GPON tempat pelanggan teraktivasi di lapangan.";
                $output[] = "- Setelah `index_olt` terisi, sistem dan AI Copilot dapat menampilkan status redaman laser (optical power) dan link connection secara real-time.";
                return implode("\n", $output);
            }
        }

        // Case 0.5: NOC Telemetry & Active Alerts (ONU LOS / DOWN / Isu Aktif)
        $isAskingLoss = str_contains($lower, 'loss') || str_contains($lower, 'los') || str_contains($lower, 'kabel putus');
        $isAskingDown = str_contains($lower, 'down') || str_contains($lower, 'padam') || str_contains($lower, 'mati') || str_contains($lower, 'offline');
        $isAskingNoc = $isAskingLoss || $isAskingDown || str_contains($lower, 'isu') || str_contains($lower, 'alert') || str_contains($lower, 'node');

        if (!empty($context['noc_telemetry']) && $isAskingNoc) {
            $hasData = true;
            $telemetry = $context['noc_telemetry'];
            $summary = $telemetry['summary'] ?? [];
            $losNodes = $telemetry['onu_los_nodes'] ?? [];
            $downNodes = $telemetry['onu_down_nodes'] ?? [];

            $totalActive = $summary['total_active'] ?? (count($losNodes) + count($downNodes));
            $totalLos = $summary['total_los'] ?? count($losNodes);
            $totalDown = $summary['total_down'] ?? count($downNodes);
            $totalTickets = $summary['total_tickets'] ?? 0;

            $output[] = "### 📡 Status Monitoring Realtime OLT (Isu Aktif)";
            $output[] = "- 🚨 **Semua Isu Aktif:** **{$totalActive} Kejadian**";
            $output[] = "- 🔴 **ONU LOS (LOSS):** **{$totalLos} Node** (Kabel putus / redaman loss)";
            $output[] = "- 🟡 **ONU DOWN (OFFLINE):** **{$totalDown} Node** (Modem padam / power off)";
            $output[] = "- 🎫 **Tiket Open:** **{$totalTickets} Tiket**\n";

            if ($isAskingLoss || !$isAskingDown) {
                $output[] = "#### 🔴 Daftar Perangkat ONU LOS ({$totalLos} Node):";
                if (empty($losNodes)) {
                    $output[] = "✅ *Tidak ada node ONU yang mengalami Loss of Signal (LOS) saat ini. Seluruh redaman optik terpantau normal.*";
                } else {
                    $output[] = "| Interface GPON | Nama Pelanggan | No. Internet | OLT / Port | Redaman | Mulai / Durasi |";
                    $output[] = "|---|---|---|---|---|---|";
                    foreach ($losNodes as $ln) {
                        $wm = $ln['waktu_mulai'] ?? '-';
                        $dur = !empty($ln['durasi']) && $ln['durasi'] !== '-' ? " ({$ln['durasi']})" : '';
                        $output[] = "| `{$ln['interface']}` | **{$ln['nama_pelanggan']}** | `{$ln['nomor_internet']}` | {$ln['olt']} | <span class=\"text-rose-500 font-bold font-mono\">{$ln['redaman']}</span> | {$wm}{$dur} |";
                    }
                }
            }

            if ($isAskingDown && !$isAskingLoss) {
                $output[] = "\n#### 🟡 Daftar Perangkat ONU DOWN ({$totalDown} Node):";
                if (empty($downNodes)) {
                    $output[] = "✅ *Tidak ada node ONU yang DOWN saat ini.*";
                } else {
                    $output[] = "| Interface GPON | Nama Pelanggan | No. Internet | OLT / Port | Mulai / Durasi |";
                    $output[] = "|---|---|---|---|---|";
                    foreach ($downNodes as $dn) {
                        $wm = $dn['waktu_mulai'] ?? '-';
                        $dur = !empty($dn['durasi']) && $dn['durasi'] !== '-' ? " ({$dn['durasi']})" : '';
                        $output[] = "| `{$dn['interface']}` | **{$dn['nama_pelanggan']}** | `{$dn['nomor_internet']}` | {$dn['olt']} | {$wm}{$dur} |";
                    }
                }
            }

            if (!empty($context['tickets']) && !$isAskingDown) {
                $output[] = "\n> ℹ️ *Catatan: Data di atas adalah data fisik real-time kabel optik / OLT. Selain itu tercatat " . count($context['tickets']) . " tiket keluhan terkait gangguan di helpdesk.*";
            }
        }

        // Case 0.1: Jadwal Operasional, Auto Suspend, dan Billing SOP
        $isAskingScheduleOrSuspend = str_contains($lower, 'suspend') 
            || str_contains($lower, 'isolir') 
            || str_contains($lower, 'jatuh tempo') 
            || str_contains($lower, 'kapan tagihan') 
            || str_contains($lower, 'invoice terbit');

        if ($isAskingScheduleOrSuspend && empty($context['customers'])) {
            $hasData = true;
            $output[] = "### ⏱️ Jadwal Otomatis & Prosedur Suspend (Isolir) IMS2:";
            $output[] = "Berdasarkan sistem penjadwalan resmi (*Scheduler Cron*) IMS2:\n";
            $output[] = "1. **📅 Terbit Request Suspend Otomatis:**";
            $output[] = "   - Request suspend otomatis diterbitkan setiap **tanggal 25 setiap bulan pada pukul 06:00 WIB pagi**.";
            $output[] = "   - Sistem menjalankan perintah `suspend:unpaid-customers` untuk menyaring seluruh pelanggan aktif yang tagihannya belum berstatus lunas (`status_bill_lay != 15`).";
            $output[] = "2. **🧾 Jadwal Terbit Invoice Tagihan Bulanan:**";
            $output[] = "   - Invoice massal bulanan diterbitkan otomatis setiap **tanggal 1 setiap bulan pada pukul 08:00 WIB pagi** (`finance:auto-generate-invoices`).";
            $output[] = "3. **⚠️ Batas Waktu Jatuh Tempo:**";
            $output[] = "   - Pelanggan diberikan rentang pembayaran mulai **tanggal 1 hingga tanggal 24**.";
            $output[] = "4. **🔄 Alur Eksekusi & Buka Isolir (Unsuspend):**";
            $output[] = "   - Request suspend masuk ke antrean tabel `trx_suspend` (status #11) untuk dieksekusi pemutusan/isolir oleh tim NOC pada MikroTik/OLT.";
            $output[] = "   - **Buka Isolir (Unsuspend):** Terbit otomatis seketika tim Finance memverifikasi dan menyetujui bukti pembayaran pelanggan (status #14 -> #15).";
            return implode("\n", $output);
        }

        // Case 0.2: Role & System Guidance
        $isAskingRole = !empty($context['role_guide']) 
            || str_contains($lower, 'role') 
            || str_contains($lower, 'wewenang') 
            || str_contains($lower, 'bisa dilakukan') 
            || str_contains($lower, 'fungsi admin')
            || str_contains($lower, 'tugas admin')
            || str_contains($lower, 'admin bisa apa');

        if ($isAskingRole) {
            $hasData = true;
            $targetRole = 'Admin';
            if (str_contains($lower, 'direktur') || str_contains($lower, 'ceo')) {
                $targetRole = 'Direktur';
            } elseif (str_contains($lower, 'finance') || str_contains($lower, 'keuangan') || str_contains($lower, 'billing')) {
                $targetRole = 'Finance';
            } elseif (str_contains($lower, 'noc')) {
                $targetRole = 'NOC';
            } elseif (str_contains($lower, 'teknik') || str_contains($lower, 'teknisi')) {
                $targetRole = 'Teknik';
            } elseif (str_contains($lower, 'cs') || str_contains($lower, 'customer service')) {
                $targetRole = 'Customer Service';
            }

            if ($targetRole === 'Admin') {
                $output[] = "### 🛡️ Wewenang & Hak Akses Role Admin di IMS2:";
                $output[] = "Sebagai **Administrator Sistem**, Role Admin memiliki wewenang operasional penuh di IMS2:\n";
                $output[] = "1. **👥 Manajemen Pelanggan (Lifecycle Penuh):**";
                $output[] = "   - Memantau registrasi baru, status instalasi, aktivasi NOC, hingga pelanggan aktif (#20).";
                $output[] = "   - Mengelola isolir pelanggan (suspend) dan reaktivasi.";
                $output[] = "2. **💳 Billing & Keuangan:**";
                $output[] = "   - Mengakses seluruh tagihan bulanan (draft, publish, unpaid, lunas).";
                $output[] = "   - Mengetahui tagihan yang sedang menunggu verifikasi pembayaran oleh tim finance.";
                $output[] = "   - Mengaudit total piutang dan pembayaran pelanggan.";
                $output[] = "3. **🌐 Jaringan & OLT:**";
                $output[] = "   - Memantau Master OLT (Kayu Agung, Babakan Tarogong, Soreang), IP address, dan port GPON.";
                $output[] = "   - Memeriksa telemetri modem pelanggan (UP, LOS, DOWN).";
                $output[] = "4. **🎫 Helpdesk & Tiket Gangguan:**";
                $output[] = "   - Memantau seluruh tiket gangguan teknis dan jadwal penanganan tim lapangan.";
                $output[] = "5. **⚙️ Manajemen AI & Pengguna:**";
                $output[] = "   - Mengelola akun staf dan memantau rotasi kuota multi-kunci Google Gemini di menu **Monitoring AI**.";
            } elseif ($targetRole === 'Direktur') {
                $output[] = "### 👑 Wewenang & Hak Akses Role Direktur / CEO:";
                $output[] = "- 📊 **Ringkasan Eksekutif:** Akses rekap performa omzet, total piutang, dan tren pertumbuhan user baru.";
                $output[] = "- 💰 **Monitoring Keuangan:** Memantau tagihan terbayar dan sisa kas masuk.";
                $output[] = "- 🌐 **Kesehatan Jaringan:** Memantau ketersediaan OLT dan SLA jaringan ISP.";
            } elseif ($targetRole === 'Finance') {
                $output[] = "### 💼 Wewenang & Hak Akses Role Finance:";
                $output[] = "- 💳 **Verifikasi Pembayaran:** Konfirmasi bukti bayar dan pelunasan invoice (#14 -> #15).";
                $output[] = "- 🧾 **Manajemen Billing:** Penerbitan dan audit tagihan bulanan pelanggan.";
                $output[] = "- 📈 **Laporan Keuangan:** Rekap pendapatan harian, bulanan, dan audit piutang.";
            } elseif ($targetRole === 'NOC') {
                $output[] = "### 🖥️ Wewenang & Hak Akses Role NOC:";
                $output[] = "- 🌐 **Monitoring OLT & GPON:** Pantau redaman optik (LOS/DOWN) dan port GPON.";
                $output[] = "- 📡 **Konfigurasi Jaringan:** Aktivasi layanan pelanggan baru (#18/#19) dan manajemen IP/PPPoE.";
                $output[] = "- 🎫 **Eskalasi Tiket:** Diagnosa teknis gangguan sebelum diteruskan ke tim lapangan.";
            } elseif ($targetRole === 'Teknik') {
                $output[] = "### 🔧 Wewenang & Hak Akses Role Teknik:";
                $output[] = "- 🏠 **Instalasi Lapangan:** Pemasangan kabel dropcore dan aktivasi modem pelanggan baru (#16).";
                $output[] = "- 🛠️ **Perbaikan Gangguan:** Penanganan fisik kabel putus, redaman tinggi, atau pergantian perangkat.";
            } else {
                $output[] = "### 🎧 Wewenang & Hak Akses Role Customer Service:";
                $output[] = "- 👤 **Informasi Pelanggan:** Cek status langganan, paket, dan nomor internet.";
                $output[] = "- 🎫 **Helpdesk:** Pembuatan tiket komplain dan eskalasi kendala ke tim teknis.";
                $output[] = "- 💳 **Cek Tagihan:** Membantu konfirmasi status pembayaran kepada pelanggan.";
            }
            return implode("\n", $output);
        }

        // Case 1: Customer details found
        if (!empty($context['customers'])) {
            $hasData = true;
            $output[] = "### 👤 Data Pelanggan Ditemukan (" . count($context['customers']) . "):";
            foreach ($context['customers'] as $c) {
                $statusText = match((string)$c->status_reg) {
                    '20' => '🟢 Aktif Online',
                    '18', '19' => '🔵 Aktivasi NOC',
                    '16' => '🟡 Instalasi',
                    '12' => '⚪ Survey',
                    '14', '15' => '🔴 Batal',
                    default => '⚪ Registrasi (' . $c->status_reg . ')'
                };
                $suspendText = $c->is_suspend == 1 ? "🔴 Terisolir (Suspend)" : "🟢 Normal";
                $oltLabel = !empty($c->olt_name) ? "{$c->olt_name} (" . ($c->olt_ip ?? '-') . ")" : ($c->olt ?: '-');
                $indexLabel = !empty($c->index_olt) ? "`{$c->index_olt}`" : "⚠️ *Belum terdaftar (NULL)*";

                $output[] = "- **{$c->nama_pelanggan}** (`{$c->nomor_internet}`)";
                $output[] = "  - Paket: `{$c->kode_bandwith}` | Status: {$statusText} | Isolir: {$suspendText}";
                $output[] = "  - PPPoE: `" . ($c->pppoe_username ?: '-') . "`";
                $output[] = "  - OLT: **{$oltLabel}** | Index Port: {$indexLabel}";
                if (empty($c->index_olt)) {
                    $output[] = "    *(Catatan: Pengecekan redaman live butuh input port `index_olt` di menu Pelanggan)*";
                }
                $output[] = "  - Alamat: *{$c->alamat_pasang}*";
            }
        }

        // Case 2.0: Billing Summary / Unpaid Total requested (selain lunas / belum bayar / piutang)
        $isAskingUnpaidTotal = str_contains($lower, 'selain') || str_contains($lower, 'belum')
            || str_contains($lower, 'tunggak') || str_contains($lower, 'piutang')
            || str_contains($lower, 'kurang bayar')
            || (str_contains($lower, 'total') && (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar')));

        if (!empty($context['billing_summary']) && $isAskingUnpaidTotal) {
            $hasData = true;
            $bs = $context['billing_summary'];
            $output[] = "### 💳 " . ($bs['judul'] ?? 'Rekapitulasi Tagihan Belum Terbayar');
            $output[] = "- 📊 **Total Tagihan Tertunggak:** **" . number_format($bs['total_unpaid_invoices'], 0, ',', '.') . " Invoice**";
            $output[] = "- 💰 **Total Nominal Piutang:** **{$bs['total_unpaid_amount_formatted']}**\n";

            if (!empty($bs['breakdown'])) {
                $output[] = "#### 📋 Rincian Berdasarkan Status:";
                $output[] = "| Status Tagihan | Jumlah Invoice | Total Nominal |";
                $output[] = "|---|---|---|";
                foreach ($bs['breakdown'] as $br) {
                    $bName = is_array($br) ? ($br['status_name'] ?? '-') : ($br->status_name ?? '-');
                    $bCount = is_array($br) ? ($br['count'] ?? 0) : ($br->count ?? 0);
                    $bTotal = is_array($br) ? ($br['total_formatted'] ?? '-') : ($br->total_formatted ?? '-');
                    $output[] = "| **{$bName}** | " . number_format($bCount, 0, ',', '.') . " invoice | `{$bTotal}` |";
                }
            }

            if (!empty($context['billings'])) {
                $output[] = "\n#### 🔍 Contoh Tagihan Belum Bayar Terbaru:";
                $output[] = "| No. Billing | No. Internet | Periode | Total | Status |";
                $output[] = "|---|---|---|---|---|";
                foreach ($context['billings'] as $b) {
                    $totalRp = 'Rp ' . number_format((float)($b->total_layanan ?? 0), 0, ',', '.');
                    $output[] = "| `{$b->kode_billing_layanan}` | `{$b->nomor_internet}` | {$b->bulan_tagihan}/{$b->tahun_tagihan} | {$totalRp} | 🔴 " . ($b->status_desc ?? 'Belum Bayar') . " |";
                }
            }
        }

        // Case 2: Billing details found
        if (!empty($context['billings']) && empty($context['billing_summary'])) {
            $hasData = true;
            $output[] = "\n### 💳 Data Tagihan & Pembayaran (" . count($context['billings']) . "):";
            $output[] = "| No. Billing | No. Internet | Periode | Total | Status |";
            $output[] = "|---|---|---|---|---|";
            foreach ($context['billings'] as $b) {
                $st = match((string)$b->status_bill_lay) {
                    '15' => '🟢 Lunas',
                    '14' => '🟡 Menunggu Verifikasi',
                    default => '🔴 Belum Bayar'
                };
                $totalRp = 'Rp ' . number_format((float)($b->total_layanan ?? 0), 0, ',', '.');
                $output[] = "| `{$b->kode_billing_layanan}` | `{$b->nomor_internet}` | {$b->bulan_tagihan}/{$b->tahun_tagihan} | {$totalRp} | {$st} |";
            }

            if (isset($context['stats']['pending_verification_count']) && $context['stats']['pending_verification_count'] > 0) {
                $output[] = "\n📌 *Terdapat **{$context['stats']['pending_verification_count']}** tagihan menunggu konfirmasi/verifikasi finance.*";
            }
        } elseif (!empty($context['customers']) && (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar') || str_contains($lower, 'invoice') || str_contains($lower, 'tunggak') || str_contains($lower, 'linawati'))) {
            $hasData = true;
            $cust = $context['customers'][0];
            $output[] = "\n### 💳 Status Tagihan Pelanggan";
            $output[] = "✅ Pelanggan **{$cust->nama_pelanggan}** (No. Internet: `{$cust->nomor_internet}`) saat ini **tidak memiliki tagihan tertunggak**.";
        }

        // Case 3: Tiket gangguan found
        if (!empty($context['tickets'])) {
            $hasData = true;
            $output[] = "\n### 🎫 Tiket Gangguan Ditemukan (" . count($context['tickets']) . "):";
            foreach ($context['tickets'] as $t) {
                $output[] = "- **Tiket `{$t->tiket}`** (Internet: `{$t->nomor_internet}`)";
                $output[] = "  - Keluhan: *{$t->keluhan}*";
                $output[] = "  - Status: **" . strtoupper($t->status) . "** | Penanganan: " . ($t->penanganan ?: 'Menunggu Teknisi');
            }
        }

        // Case 4: OLTs found
        if (!empty($context['olts'])) {
            $hasData = true;
            $output[] = "\n### 🌐 Status OLT / Jaringan Utama:";
            $output[] = "| Nama OLT | Brand / Model | IP Address | Status |";
            $output[] = "|---|---|---|---|";
            foreach ($context['olts'] as $o) {
                $st = ($o->is_active == 1) ? '🟢 Aktif' : '🔴 Non-Aktif';
                $output[] = "| **{$o->name_olt}** | {$o->brand} {$o->model} | `{$o->ip_address}` | {$st} |";
            }
        }

        // Case 5: Summary stats
        if (!empty($context['stats']) && (isset($context['stats']['active_customers']) || isset($context['stats']['new_customers_this_month']) || isset($context['stats']['total_customers']))) {
            $hasData = true;
            $output[] = "\n### 📊 Ringkasan Operasional & Pelanggan IMS2:";
            if (isset($context['stats']['new_customers_this_month'])) {
                $period = $context['stats']['current_period'] ?? 'Bulan Ini';
                $output[] = "- 🆕 **Pelanggan / User Baru ({$period}):** `{$context['stats']['new_customers_this_month']}` user terdaftar";
            }
            if (isset($context['stats']['total_customers'])) {
                $output[] = "- 👥 **Total Seluruh Pelanggan Terdaftar:** `{$context['stats']['total_customers']}` pelanggan";
            }
            if (isset($context['stats']['active_customers'])) {
                $output[] = "- 🟢 **Total Pelanggan Aktif Online (#20):** `{$context['stats']['active_customers']}` pelanggan";
            }
            if (isset($context['stats']['isolated_customers'])) {
                $output[] = "- 🚫 **Pelanggan Terisolir (Suspend):** `{$context['stats']['isolated_customers']}` pelanggan";
            }
            if (isset($context['stats']['bills_paid_this_month'])) {
                $output[] = "- 💰 **Tagihan Terbayar (Bulan Ini):** `{$context['stats']['bills_paid_this_month']}` transaksi";
            }
            if (isset($context['stats']['bills_unpaid_this_month'])) {
                $output[] = "- ⏳ **Tagihan Belum Bayar (Bulan Ini):** `{$context['stats']['bills_unpaid_this_month']}` invoice";
            }
            if (isset($context['stats']['open_tickets'])) {
                $output[] = "- 🛠️ **Tiket Gangguan Open:** `{$context['stats']['open_tickets']}` tiket";
            }
        }

        if ($hasData) {
            return implode("\n", $output);
        }

        // If specific keyword was used but no data returned
        if (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'pending') || str_contains($lower, 'verif')) {
            $output[] = "### 💳 Informasi Tagihan & Verifikasi";
            if ((str_contains($lower, 'verif') || str_contains($lower, 'pending') || str_contains($lower, 'konfirmasi')) && isset($context['stats']['pending_verification_count']) && $context['stats']['pending_verification_count'] === 0) {
                $output[] = "✅ **Tidak ada tagihan yang menunggu verifikasi saat ini.**\nSeluruh konfirmasi pembayaran dari pelanggan maupun portal sudah diproses oleh tim finance.";
            } else {
                $output[] = "🔍 Data tagihan spesifik tidak ditemukan. Silakan masukkan nomor billing atau nomor internet pelanggan.";
            }
            return implode("\n", $output);
        }

        if (str_contains($lower, 'pelanggan') || str_contains($lower, 'cust') || str_contains($lower, 'user')) {
            $output[] = "### 👤 Pencarian Pelanggan\n🔍 Tidak ditemukan pelanggan dengan kata kunci pencarian tersebut.\n👉 *Tips: Masukkan nomor internet (contoh: `102938`) atau nama pelanggan lengkap.*";
            return implode("\n", $output);
        }

        if (str_contains($lower, 'tiket') || str_contains($lower, 'gangguan') || str_contains($lower, 'keluhan')) {
            $output[] = "### 🎫 Status Tiket Gangguan";
            $output[] = "🎉 **Tidak ada tiket gangguan yang berstatus open / aktif saat ini.**\nLayanan internet pelanggan terpantau aman tanpa laporan kendala.";
            return implode("\n", $output);
        }

        // Case 6: Conversational / Chit-chat questions (e.g. "kamu cowo atau cewe", "kamu siapa", "apa kabar")
        if (preg_match('/\b(cowo|cewe|cowok|cewek|pria|wanita|gender|kelamin)\b/i', $lower)) {
            return "Saya adalah asisten virtual berbasis kecerdasan buatan (AI) sistem IMS2, jadi saya **tidak memiliki jenis kelamin (bukan cowok maupun cewek)**. 🤖✨\n\nSaya bertugas mendampingi operasional seluruh tim IMS2 (NOC, Finance, Teknik, dan Manajemen). Ada yang bisa saya bantu hari ini, **{$userName}**?";
        }

        if (preg_match('/\b(kamu siapa|siapa kamu|siapa anda|nama kamu|siapa namamu)\b/i', $lower)) {
            return "Halo **{$userName}**! 👋 Saya adalah **IMS AI Copilot**, asisten cerdas sistem internal PT Media Solusi Network (IMS2). Saya siap membantu Anda mengecek pelanggan, status OLT & redaman, keuangan, dan tiket kendala jaringan.";
        }

        if (preg_match('/\b(pembuatmu|penciptamu|yang buat kamu|yang bikin kamu|dibuat oleh)\b/i', $lower)) {
            return "Saya dikembangkan oleh **Tim Developer PT Media Solusi Network** khusus untuk mempermudah dan mempercepat operasional sistem IMS2. 🚀";
        }

        if (preg_match('/\b(apa kabar|gimana kabarnya|sehat)\b/i', $lower)) {
            return "Kabar saya luar biasa dan siap membantu operasional sistem IMS2 kapan saja! 🟢 Bagaimana dengan Anda hari ini, **{$userName}**?";
        }

        if (preg_match('/^(terima kasih|makasih|thanks|thank you|tengkyu)\b/i', $lower)) {
            return "Sama-sama, **{$userName}**! Senang bisa membantu Anda. Jika ada data lain yang ingin Anda cari, tanyakan saja ya! 😊";
        }

        // Default greeting / help response
        $output[] = "Halo **{$userName}**! 👋 Saya adalah **IMS AI Copilot**.";
        $output[] = "Akun Anda aktif dengan role: **{$userRole}**.\n";
        $output[] = "Bantuan operasional yang tersedia untuk divisi Anda:";

        if (in_array($userRole, ['Direktur', 'CEO', 'Admin'], true)) {
            $output[] = "1. **Cek Pelanggan**: Cari nama atau nomor internet pelanggan.";
            $output[] = "2. **Cek Tagihan & Pembayaran**: Ketik `cek tagihan pending` atau `cek billing 102938`.";
            $output[] = "3. **Status OLT & Jaringan**: Pantau status OLT, IP, dan port GPON.";
            $output[] = "4. **Tiket Gangguan**: Pantau tiket open dan keluhan pelanggan.";
            $output[] = "5. **Rekap Sistem**: Ringkasan performa operasional hari ini.";
        } elseif ($userRole === 'Finance') {
            $output[] = "1. **Cek Tagihan Pending**: Ketik `cek tagihan pending` atau `verifikasi tagihan`.";
            $output[] = "2. **Cek Pembayaran Pelanggan**: Ketik `cek billing 102938` atau nama pelanggan.";
            $output[] = "3. **Rekap Keuangan**: Ketik `rekap tagihan bulan ini`.";
        } elseif ($userRole === 'NOC' || $userRole === 'Teknik') {
            $output[] = "1. **Status OLT & Jaringan**: Ketik `status olt` atau `cek olt O2`.";
            $output[] = "2. **Cek Pelanggan, Redaman & GPON**: Ketik `cek pelanggan 102938` untuk melihat status koneksi, paket, redaman optik, dan port OLT.";
            $output[] = "3. **Tiket Gangguan / Lapangan**: Ketik `cek tiket open` atau keluhan jaringan.";
            $output[] = "4. **Rekap Jaringan & Operasional**: Ketik `rekap hari ini` untuk total user online & terisolir.";
        } elseif ($userRole === 'Customer Service') {
            $output[] = "1. **Cari Data Pelanggan**: Ketik nama atau nomor internet pelanggan.";
            $output[] = "2. **Cek Status Tiket / Komplain**: Ketik `cek tiket pelanggan`.";
            $output[] = "3. **Cek Status Pembayaran**: Konfirmasi apakah tagihan pelanggan sudah terverifikasi.";
        } else {
            $output[] = "1. **Cari Data Pelanggan**: Cari nama atau nomor internet pelanggan.";
            $output[] = "2. **Status Tiket**: Cek tiket terkait.";
        }

        return implode("\n", $output);
    }
}
