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
        @set_time_limit(120);

        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $userMessage = trim($request->input('message'));
        $history = $request->input('history', []);
        $currentUser = auth()->user();

        // 1. Gather relevant domain context from IMS database based on user intent & past history
        $contextData = $this->gatherContext($userMessage, $currentUser, $history);

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
        @set_time_limit(120);
        $geminiModel = trim((string)(Cache::get('ims_ai_working_model') ?: config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.8-flash')));
        if (empty($geminiModel) || str_contains($geminiModel, 'transcribe') || str_contains($geminiModel, '2.0-flash')) {
            $geminiModel = 'gemini-3.8-flash';
            Cache::forget('ims_ai_working_model');
        }
        Cache::forget('google_api_network_unreachable');

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
                        Log::warning("[AiAssistant] Gemini Key #{$keyIndex} mengalami latensi jaringan / timeout. Menjalankan auto-ping pemulihan otomatis...");
                        $this->autoPingRecoverKey($key, $geminiModel);
                    }
                }
            }

            // Fallback to local heuristic engine when all keys are exhausted or error
            $totalKeys = count($geminiKeys);
            $errDetail = $lastException?->getMessage() ?? 'Google API sedang cooldown atau batas limit tercapai.';
            $fallbackReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, true, $errDetail, $history);

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

            return response()->json([
                'status' => 'success',
                'reply' => $fallbackReply,
                'mode' => 'local_fallback',
                'fallback_reason' => $errDetail,
                'keys_tried' => $totalKeys,
            ]);
        }

        // 3. Fallback when API key is not yet set
        $localReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, false, null, $history);
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

            // Auto-ping pemulihan otomatis jika akun terdeteksi timeout atau error tanpa harus klik manual
            $isTimedOutError = !empty($keyStats['last_error']) && (
                str_contains(strtolower($keyStats['last_error']), 'timed out') ||
                str_contains(strtolower($keyStats['last_error']), 'timeout') ||
                str_contains(strtolower($keyStats['last_error']), 'curl error 28') ||
                str_contains(strtolower($keyStats['last_error']), 'could not resolve')
            );
            if ($cooldownSec === 0 && ($isTimedOutError || ($keyStats['status'] ?? '') === 'error')) {
                if ($this->autoPingRecoverKey($k, $geminiModel)) {
                    $keyStats['last_error'] = null;
                    $keyStats['status'] = 'active';
                    $usageData['keys'][$keyId] = $keyStats;
                    Cache::put('ims_ai_usage_' . $today, $usageData, now()->addDays(14));
                    $totalActiveKeys++;
                }
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
                $timeoutSec = (int)config('services.gemini.timeout', 60);
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$selectedKey}";
                $response = Http::withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CONNECTTIMEOUT => 15,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0,
                    ],
                ])->timeout($timeoutSec)->post($url, [
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
     * Automatic ping test to immediately recover and re-activate a key after network timeout.
     */
    public function autoPingRecoverKey(string $apiKey, string $geminiModel = 'gemini-3.8-flash'): bool
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$apiKey}";
            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ],
            ])->timeout(6)->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'ping']]],
                ],
                'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 5],
            ]);

            if ($response->successful()) {
                $usage = $response->json('usageMetadata');
                $this->recordUsage($apiKey, $usage ?: []);
                Cache::forget('gemini_key_cooldown_' . md5($apiKey));
                Log::info("[AiAssistant] Auto-ping berhasil mengaktifkan kembali Gemini Key setelah timeout.");
                return true;
            }
        } catch (\Throwable $e) {
            Log::debug("[AiAssistant] Auto-ping probe gagal: " . $e->getMessage());
        }

        return false;
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

        // Jika terjadi timeout / kendala latensi, langsung jalankan auto-ping pemulihan
        $isTimeout = str_contains($errLower, 'timed out') || str_contains($errLower, 'timeout') || str_contains($errLower, 'curl error 28') || str_contains($errLower, 'could not resolve');
        if ($isTimeout) {
            if ($this->autoPingRecoverKey($apiKey)) {
                return; // Auto-ping berhasil mengaktifkan akun kembali, batalkan pencatatan error!
            }
        }

        $isQuota = $statusCode === 429 || str_contains($errLower, 'quota') || str_contains($errLower, 'rate limit');
        $data['keys'][$keyId]['status'] = $isQuota ? 'exhausted' : 'error';
        $data['keys'][$keyId]['last_error'] = ($isQuota ? 'Limit kuota harian/menit tercapai (429)' : substr($errorMessage, 0, 120)) . ' (' . now()->format('H:i:s') . ')';

        Cache::put($cacheKey, $data, now()->addDays(14));
    }

    /**
     * Retrieve relevant database context matching user query.
     */
    protected function gatherContext(string $query, $user, array $history = []): array
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

        // Extract numbers or potential IDs (supports MS prefix, e.g. MS60870920200926 or pure digits 5-18 digits, e.g. 11711224)
        if (preg_match('/\b(?:MS|ms)?\d{5,18}\b/i', $query, $idMatches)) {
            $extractedId = $idMatches[0];
        } elseif (preg_match('/(?:nomor\s+internet|no\s+internet|id\s+pelanggan|pppoe)\s+[\'"]?([A-Za-z0-9_-]+)[\'"]?/i', $query, $explicitIdMatch)) {
            $extractedId = $explicitIdMatch[1];
        } else {
            $extractedId = null;
        }

        // Contextual follow-up resolution: ONLY if no ID found in current query, check previous history turns
        if (!$extractedId && !empty($history)) {
            $isFollowUp = preg_match('/\b(dia|beliau|tersebut|itu|tadi|tagihannya|koneksinya|statusnya|paketnya|redamannya|solusinya|penanganannya|komplainnya|miliknya)\b/i', $lower)
                || str_contains($lower, 'apakah sudah bayar') || str_contains($lower, 'berapa tagihan') || str_contains($lower, 'status bill');
            if ($isFollowUp && !str_contains($lower, 'nomor internet') && !str_contains($lower, 'pelanggan')) {
                $revHistory = array_reverse($history);
                foreach ($revHistory as $past) {
                    $pastText = $past['text'] ?? ($past['content'] ?? '');
                    if (preg_match('/\b(?:MS|ms)?\d{5,18}\b/i', $pastText, $pastMatch)) {
                        $extractedId = $pastMatch[0];
                        break;
                    }
                }
            }
        }

        // 1. Comprehensive stopword removal for Indonesian natural language queries
        $stopwords = [
            'cari', 'cek', 'lihat', 'info', 'data', 'detail', 'status', 'keaktifan', 'aktif', 'nonaktif', 'masih',
            'pelanggan', 'nama', 'user', 'client', 'cust', 'customer', 'member',
            'alamat', 'lokasi', 'tempat', 'dimana', 'rumah',
            'tagihan', 'billing', 'invoice', 'bayar', 'pembayaran', 'lunas', 'unpaid', 'paid', 'belum', 'sudah', 'telah', 'sedang',
            'tolong', 'mohon', 'bantu', 'bisa', 'dong', 'ya', 'min', 'kak', 'admin', 'halo', 'hai', 'selamat', 'pagi', 'siang', 'sore', 'malam',
            'apakah', 'ada', 'berapa', 'siapa', 'kapan', 'kenapa', 'bagaimana', 'apa', 'tentang',
            'terisolir', 'diisolir', 'isolir', 'suspend', 'disuspend', 'tersuspend', 'blokir', 'diblokir', 'terblokir', 'gangguan', 'kendala', 'masalah', 'tiket', 'paket', 'layanan', 'internet', 'milik', 'punya', 'atas',
            'adalah', 'itu', 'ini', 'kah', 'dengan', 'bernama', 'si', 'dari', 'pada', 'yang', 'tidak', 'bukan', 'akan',
            'selain', 'total', 'jumlah', 'rekap', 'semua', 'seluruh', 'sisa', 'tunggakan', 'piutang',
            'oleh', 'dilakukan', 'melakukan', 'role', 'fungsi', 'tugas', 'wewenang', 'sistem', 'aplikasi', 'cara', 'aturan', 'menu', 'fitur', 'panduan', 'bantuan', 'contoh', 'seperti', 'tersebut', 'saja',
            'saat', 'sekarang', 'kini', 'hari', 'bulan', 'tahun', 'terpasang', 'berada', 'mana', 'profil', 'informasi', 'terbayar', 'tercatat', 'banyak'
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
            || str_contains($lower, 'customer')
            || str_contains($lower, 'member')
            || str_contains($lower, 'atas nama') 
            || str_contains($lower, 'cari nama') 
            || str_contains($lower, 'cek nama')
            || str_contains($lower, 'no internet')
            || str_contains($lower, 'nomor internet')
            || str_contains($lower, 'id pelanggan')
            || str_contains($lower, 'pppoe');

        $isGeneralDomainQuery = preg_match('/\b(total|jumlah|berapa|daftar|list|tampilkan|semua|rekap|fitur|router|mikrotik|olt|tiket|gangguan|komplain)\b/i', $lower)
            && empty($extractedId)
            && empty($searchCandidates);

        // Context 1: Customer lookup (by ID, username, or name candidates across batchjob & m_pelanggan)
        if (($hasExplicitCustomerIntent || !empty($searchCandidates)) && !$isAskingCustomerStats && !$isAskingRoleOrHelp && !$isGeneralDomainQuery) {
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

                        $isSusp = in_array((string)$c->status_reg, ['21', '21.1'], true) || ($c->is_suspend == 1 && (string)$c->status_reg !== '23');
                        $c->status_reg_nama = match((string)$c->status_reg) {
                            '20' => 'Aktif (Normal)',
                            '21', '21.1' => 'Suspend (Terisolir)',
                            '23', '23.1' => 'Terminasi (Putus/Dismantle)',
                            '18', '19' => 'Aktivasi NOC',
                            '16' => 'Instalasi Lapangan',
                            '12' => 'Survey',
                            default => 'Registrasi (' . $c->status_reg . ')'
                        };
                        $c->status_isolir_lapangan = $isSusp ? 'TERISOLIR (SUSPEND)' : 'NORMAL (TIDAK TERISOLIR)';
                        $c->status_layanan_resmi = $isSusp ? '🔴 Terisolir (Suspend)' : '🟢 Online Aktif';
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
                $context['stats']['active_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('status_reg', '20')->count();
                $context['stats']['isolated_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->whereIn('status_reg', ['21', '21.1'])->count();
                $context['stats']['terminated_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->whereIn('status_reg', ['23', '23.1'])->count();
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

        $context['extracted_id'] = $extractedId;
        $context['clean_search'] = $cleanSearch;
        $context['has_customer_intent'] = $hasExplicitCustomerIntent || !empty($extractedId);
        $context['search_candidates'] = $searchCandidates;

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
            . "5. INTEGRITAS DATA STATUS PELANGGAN (SUMBER RESMI: trx_batchjob_register):\n"
            . "   - STATUS LAYANAN PELANGGAN (Aktif vs Suspend) HARUS DICEK HANYA DARI properti `status_layanan_resmi` atau `status_isolir_lapangan` di objek [customers] (yang bersumber mutlak dari `trx_batchjob_register`).\n"
            . "   - DILARANG KERAS menyimpulkan sendiri bahwa pelanggan berstatus terisolir/suspend hanya karena ada invoice belum bayar di [billings]!\n"
            . "   - Jika di [customers] `status_layanan_resmi` adalah '🟢 Online Aktif' atau `status_isolir_lapangan` adalah 'NORMAL (TIDAK TERISOLIR)', maka pelanggan tersebut MUTLAK NORMAL AKTIF (TIDAK TERISOLIR)!\n"
            . "   - Pelanggan HANYA berstatus Terisolir jika `status_isolir_lapangan` bernilai 'TERISOLIR (SUSPEND)'.\n"
            . "   - JIKA DI RIWAYAT CHAT SEBELUMNYA (HISTORY) PERNAH DISEBUT PELANGGAN TERISOLIR/SUSPEND, ANDA WAJIB MENGABAIKAN KESALAHAN TERSEBUT DAN MENGIKUTI DATA TERKINI DARI DATABASE (Online Aktif)!\n"
            . "   - DILARANG KERAS menghubungkan, mengaitkan, atau menampilkan invoice milik orang lain kepada pelanggan yang ditanyakan.\n"
            . "   - Dilarang keras berasumsi atau mengarang nomor internet dan invoice.\n\n"
            . "6. PRINSIP JAWABAN SINGKAT & TEPAT SASARAN (DIRECT ANSWER):\n"
            . "   - Jika pengguna menanyakan status / cek status / isolir / suspend / aktif pelanggan (misal: 'cek status 11711224', 'Cek apakah Dian Haerani masih terisolir?', 'apakah pelanggan 11711224 aktif?', 'status pelanggan 11711224'), JAWAB LANGSUNG STATUSNYA SECARA SINGKAT (1 kalimat): 'Pelanggan [Nama] ([NoInternet]) saat ini berstatus [Normal / Terisolir]'. DILARANG KERAS menampilkan seluruh kartu profil (alamat, OLT, router, tagihan 3 bulan) jika tidak diminta!\n"
            . "   - Jika pengguna HANYA menanyakan aktif atau tidak, jawab langsung status keaktifannya saja secara singkat.\n"
            . "   - Jika pengguna menanyakan di router mana, jawab nama router dan PPPoE-nya saja.\n"
            . "   - Jika pengguna menanyakan alamat, jawab alamatnya saja.\n"
            . "   - Hanya tampilkan kartu profil lengkap jika pengguna memang meminta 'data', 'profil', atau 'info lengkap' pelanggan.\n\n"
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

                    $timeoutSec = (int)config('services.gemini.timeout', 60);
                    $response = Http::withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CONNECTTIMEOUT => 15,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => 0,
                        ],
                    ])->timeout($timeoutSec)->post($url, $payload);

                    // Automatic fallback if specific model does not support thinkingConfig
                    if (!$response->successful() && str_contains($response->body(), 'thinkingConfig')) {
                        unset($payload['generationConfig']['thinkingConfig']);
                        $response = Http::withOptions([
                            'curl' => [
                                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CONNECTTIMEOUT => 15,
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_SSL_VERIFYHOST => 0,
                            ],
                        ])->timeout($timeoutSec)->post($url, $payload);
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
                                    CURLOPT_CONNECTTIMEOUT => 15,
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_SSL_VERIFYHOST => 0,
                                ],
                            ])->timeout($timeoutSec)->post($url, $payload);
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
            $sess = strtolower((string)(session('role') ?? session('level') ?? session('nama_level') ?? 'admin'));
            if (in_array($sess, ['admin', 'superadmin', 'administrator', '1'], true)) {
                return 'Admin';
            }
            if (in_array($sess, ['finance', 'keuangan'], true)) {
                return 'Finance';
            }
            if (in_array($sess, ['noc', 'teknik', 'teknisi'], true)) {
                return 'NOC';
            }
            if (in_array($sess, ['direktur', 'director', 'ceo'], true)) {
                return 'Direktur';
            }
            return 'Admin';
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

        $rawRole = strtolower((string)($user->role?->value ?? $user->role ?? ''));
        if (in_array($rawRole, ['admin', 'superadmin', 'administrator'], true)) {
            return 'Admin';
        }
        if (in_array($rawRole, ['direktur', 'director'], true)) {
            return 'Direktur';
        }
        if ($rawRole === 'ceo') {
            return 'CEO';
        }
        if (in_array($rawRole, ['finance', 'keuangan'], true)) {
            return 'Finance';
        }
        if ($rawRole === 'noc') {
            return 'NOC';
        }
        if (in_array($rawRole, ['teknik', 'teknisi', 'drafter'], true)) {
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

        // 0. Pertanyaan Singkat Status / Keaktifan / Isolir Pelanggan Tunggal (Instant & Deterministik)
        if (!empty($contextData['customers'])) {
            $isAskingStatus = preg_match('/\b(status|aktif|online|keaktifan|isolir|terisolir|diisolir|suspend|disuspend|tersuspend|blokir|diblokir|terblokir|kondisi|keadaan)\b/i', $lower)
                && !preg_match('/\b(semua|seluruh|lengkap|detail|profil|data pelanggan|tagihan|invoice|riwayat|berapa|total|jumlah|daftar|list)\b/i', $lower);

            if ($isAskingStatus) {
                $lines = [];
                foreach ($contextData['customers'] as $c) {
                    $isCustSuspended = in_array((string)$c->status_reg, ['21', '21.1'], true) || ($c->is_suspend == 1 && (string)$c->status_reg !== '23');
                    if ($isCustSuspended) {
                        $lines[] = "🔴 Pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`) saat ini berstatus **Terisolir (Suspend)**.";
                    } else {
                        $statusDesc = match((string)$c->status_reg) {
                            '20' => '🟢 Online Aktif (Layanan normal / Tidak Terisolir)',
                            '23', '23.1' => '⚫ Terminasi / Dismantle (Putus Berlangganan)',
                            '18', '19' => '🔵 Tahap Aktivasi NOC',
                            '16' => '🟡 Tahap Instalasi Lapangan',
                            '12' => '⚪ Tahap Survey Lokasi',
                            default => 'Registrasi (' . $c->status_reg . ')'
                        };
                        $lines[] = "Pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`) saat ini berstatus **{$statusDesc}**.";
                    }
                }
                return implode("\n", $lines);
            }

            $isAskingOnlyRouter = preg_match('/\b(router|mikrotik)\b/i', $lower)
                && !preg_match('/\b(semua|seluruh|lengkap|detail|profil|data pelanggan|tagihan|daftar|list)\b/i', $lower);
            if ($isAskingOnlyRouter) {
                $lines = [];
                foreach ($contextData['customers'] as $c) {
                    $routerLabel = '-';
                    if (!empty($c->router_id) && Schema::hasTable('routers')) {
                        $rObj = DB::table('routers')->where('id', $c->router_id)->first();
                        if ($rObj) {
                            $routerLabel = "{$rObj->name} (" . ($rObj->host ?? '-') . ")";
                        }
                    }
                    $pppoe = $c->pppoe_username ?: '-';
                    $lines[] = "Pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`) terdaftar di **{$routerLabel}** dengan PPPoE `{$pppoe}`.";
                }
                return implode("\n", $lines);
            }

            $isAskingOnlyAddress = preg_match('/\b(alamat|lokasi|tempat|rumah|dimana)\b/i', $lower)
                && !preg_match('/\b(semua|seluruh|lengkap|detail|profil|data pelanggan)\b/i', $lower);
            if ($isAskingOnlyAddress) {
                $lines = [];
                foreach ($contextData['customers'] as $c) {
                    $lines[] = "Alamat pemasangan pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`): *{$c->alamat_pasang}*.";
                }
                return implode("\n", $lines);
            }
        }

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

        // 6. Prosedur Buka Isolir (Unsuspend)
        $isAskingUnsuspend = preg_match('/\b(buka isolir|cara buka isolir|unsuspend|un-suspend|buka suspend|prosedur buka isolir)\b/i', $lower);
        if ($isAskingUnsuspend && empty($contextData['customers'])) {
            return "### 🔄 Prosedur Buka Isolir (Unsuspend) Pelanggan IMS2:\n\n"
                . "1. **Pelunasan Tagihan:** Pelanggan menyelesaikan pembayaran tagihan tertunggak melalui Portal Pelanggan (QRIS/Payment Gateway) atau transfer bank ke rekening perusahaan.\n"
                . "2. **Verifikasi Finance:** Tim Finance memverifikasi bukti pembayaran di menu **Billing Layanan > Verifikasi** (status tagihan berubah dari `#14` menjadi `#15 Lunas`).\n"
                . "3. **Otomasi Unsuspend:** Sistem IMS2 secara otomatis menghapus antrean isolir dan mengirimkan perintah buka blokir (*unsuspend*) ke router MikroTik/OLT tim NOC.\n"
                . "4. **Pengajuan Manual:** Jika dibutuhkan pembukaan isolir darurat atas persetujuan pimpinan, admin dapat membuka isolir melalui menu **Data Pelanggan > Buka Isolir**.";
        }

        // 7. Prosedur Permintaan Terminasi (Tutup Akun & Putus Berlangganan)
        $isAskingTerminasi = preg_match('/\b(terminasi|putus langganan|berhenti langganan|cabut perangkat|tutup akun|dismantle|cara terminasi|prosedur terminasi)\b/i', $lower);
        if ($isAskingTerminasi && empty($contextData['customers'])) {
            return "### 🔌 Prosedur Permintaan Terminasi (Tutup Akun) IMS2:\n\n"
                . "1. **Pengajuan Request:** Petugas (Finance/Teknik/CS) membuka menu **Data Pelanggan**, klik tombol **Request Terminasi** pada pelanggan terkait.\n"
                . "2. **Pilih Alasan Berhenti:** Pilih alasan utama (Pindah rumah, tarif, tunggakan, atau *Lainnya* dengan menuliskan alasan spesifik di luar pilihan dropdown).\n"
                . "3. **Antrean NOC & Lapangan:** Permintaan masuk ke menu **Permintaan Terminasi** berstatus `#11 (Request Baru)`.\n"
                . "4. **Eksekusi Penarikan:** Tim NOC menonaktifkan konfigurasi PPPoE/port OLT dan teknisi lapangan menarik perangkat modem ONT/ONU dari lokasi pelanggan.\n"
                . "5. **Status Akhir:** Status pelanggan resmi berubah menjadi `#23 Dismantle (Putus)` dan penerbitan invoice bulanan dihentikan.";
        }

        // 8. Jadwal Suspend / Isolir / Cron
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

        // 9. Jadwal Terbit Invoice Bulanan
        $isAskingInvoiceSchedule = (str_contains($lower, 'tagihan') || str_contains($lower, 'invoice') || str_contains($lower, 'billing')) 
            && (str_contains($lower, 'kapan') || str_contains($lower, 'jadwal') || str_contains($lower, 'tanggal berapa') || str_contains($lower, 'jam berapa') || str_contains($lower, 'terbit') || str_contains($lower, 'generate'));

        if ($isAskingInvoiceSchedule && empty($contextData['customers']) && !str_contains($lower, 'cek') && !str_contains($lower, 'total') && !str_contains($lower, 'berapa')) {
            return "### 🧾 Jadwal Penerbitan Invoice Bulanan IMS2:\n\n"
                . "1. **📅 Jadwal Terbit:** Setiap **tanggal 1 setiap bulan pukul 08:00 WIB pagi** secara otomatis melalui command scheduler `finance:auto-generate-invoices`.\n"
                . "2. **👥 Target:** Seluruh pelanggan berstatus **Online Aktif** (`status_reg = 20`). Pelanggan berstatus survey, instalasi, atau terminasi dilewati.\n"
                . "3. **⚠️ Jatuh Tempo:** **Tanggal 25 setiap bulan pukul 06:00 WIB** (masuk antrean auto-suspend jika belum lunas).";
        }

        // 10. Pertanyaan Matrix Hak Akses & Wewenang Antar Divisi
        if ($roleAccessReply = $this->handleRoleAccessQuestion($lower, $currentUser)) {
            return $roleAccessReply;
        }

        // 11. Alur Pembayaran & Verifikasi Finance
        if ($paymentWorkflowReply = $this->handlePaymentWorkflowQuestion($lower)) {
            return $paymentWorkflowReply;
        }

        // 12. Struktur Database & Skema Tabel IMS2
        if ($databaseReply = $this->handleDatabaseQuestion($lower)) {
            return $databaseReply;
        }

        // 13. Alur Pasang Baru & Siklus Registrasi Pelanggan
        if ($regWorkflowReply = $this->handleRegistrationWorkflowQuestion($lower)) {
            return $regWorkflowReply;
        }

        // 14. Alur Penanganan Tiket Gangguan & Helpdesk
        if ($ticketWorkflowReply = $this->handleTicketWorkflowQuestion($lower)) {
            return $ticketWorkflowReply;
        }

        // 15. Glosarium & Definisi Istilah Jaringan ISP
        if ($glossaryReply = $this->handleNetworkGlossaryQuestion($lower)) {
            return $glossaryReply;
        }

        // 16. Fitur Aplikasi IMS2
        if ($featuresReply = $this->handleFeaturesQuestion($lower)) {
            return $featuresReply;
        }

        // 17. Perbedaan Kick MikroTik vs Reboot ONU di OLT
        if ($diffReply = $this->handleMikrotikOltDifference($lower)) {
            return $diffReply;
        }

        // 18. Status ONU LOS / Offline
        if ($onuReply = $this->handleOnuStatusQuestion($lower)) {
            return $onuReply;
        }

        // 19. Port / ONU Index OLT Mapping
        if ($portReply = $this->handlePortOltMappingQuestion($userMessage, $lower)) {
            return $portReply;
        }

        // 20. Daftar Router MikroTik Aktif
        if ($routersReply = $this->handleRoutersListQuestion($lower)) {
            return $routersReply;
        }

        // 21. Total Pelanggan Aktif
        if ($countReply = $this->handleCustomerCountQuestion($lower)) {
            return $countReply;
        }

        // 22. Daftar Pelanggan Sedang Suspend / Terisolir
        if ($suspListReply = $this->handleSuspendedCustomersList($lower)) {
            return $suspListReply;
        }

        // 23. Tagihan Belum Terbayar (Spesifik Bulan / Umum)
        if ($unpaidReply = $this->handleUnpaidBillsQuestion($lower, $currentUser)) {
            return $unpaidReply;
        }

        // 24. Tiket Gangguan / Komplain Hari ini / Open
        if ($ticketsReply = $this->handleTicketsDirectQuestion($lower)) {
            return $ticketsReply;
        }

        // 25. SOP Router vs Finance Approval
        if ($sopRouterReply = $this->handleFinanceRouterSopQuestion($lower)) {
            return $sopRouterReply;
        }

        return null;
    }

    /**
     * Resolves queries regarding role permissions, access boundaries, and duty matrices across IMS2 divisions.
     */
    protected function handleRoleAccessQuestion(string $lower, $currentUser = null): ?string
    {
        $isRoleQuery = preg_match('/\b(bisa melihat|bisa akses|bisa buka|bisa cek|bisa liat|boleh melihat|boleh akses|hak akses|wewenang|tugas|fungsi role|peran|matrix role|matriks role)\b/i', $lower)
            || preg_match('/\b(apakah\s+(finance|keuangan|noc|teknik|teknisi|cs|customer service|hrd|sales|mitra|gudang|admin|direktur|ceo|staff))\b/i', $lower)
            || preg_match('/\b(role apa|daftar role|semua role|siapa yang bisa)\b/i', $lower);

        if (!$isRoleQuery) {
            return null;
        }

        // 1. Finance vs OLT / Jaringan / Router / Redaman
        if ((str_contains($lower, 'finance') || str_contains($lower, 'keuangan'))
            && (str_contains($lower, 'olt') || str_contains($lower, 'router') || str_contains($lower, 'jaringan') || str_contains($lower, 'pon') || str_contains($lower, 'redaman') || str_contains($lower, 'gpon'))) {
            return "### 🔒 Hak Akses Role: Finance vs Data OLT Jaringan\n\n"
                . "❌ **Tidak, divisi Finance tidak dapat melihat maupun mengakses data perangkat OLT dan infrastruktur jaringan.**\n\n"
                . "📋 **Pembagian Wewenang Resmi di IMS2:**\n"
                . "1. **Divisi Finance:** Khusus mengelola modul penagihan (`trx_billing_layanan`), verifikasi bukti pembayaran bank, pencatatan mutasi kas, dan rekapitulasi omset/piutang.\n"
                . "2. **Divisi NOC & Teknik:** Bertanggung jawab penuh atas konfigurasi core OLT (`m_olt`), manajemen port GPON (`index_olt`), level redaman optik modem (`Rx/Tx dBm`), status link ONU (LOS/DOWN), dan router MikroTik.\n"
                . "3. **Superadmin (Direktur / CEO / Admin):** Memiliki wewenang penuh (*Full Access*) untuk memantau modul Finance sekaligus infrastruktur OLT.";
        }

        // 2. NOC / Teknik vs Keuangan / Tagihan / Omset / Gaji
        if ((str_contains($lower, 'noc') || str_contains($lower, 'teknik') || str_contains($lower, 'teknisi'))
            && (str_contains($lower, 'keuangan') || str_contains($lower, 'tagihan') || str_contains($lower, 'omset') || str_contains($lower, 'omzet') || str_contains($lower, 'gaji') || str_contains($lower, 'pembukuan') || str_contains($lower, 'rekening') || str_contains($lower, 'verifikasi'))) {
            return "### 🔒 Hak Akses Role: NOC & Teknik vs Data Keuangan\n\n"
                . "❌ **Tidak, divisi NOC dan Teknik tidak memiliki hak akses untuk melihat laporan keuangan, pembukuan bank, omset, maupun verifikasi tagihan.**\n\n"
                . "📋 **Pembagian Wewenang Resmi di IMS2:**\n"
                . "1. **Divisi NOC & Teknik:** Dikhususkan untuk pemeliharaan fisik jaringan, telemetri OLT, status link modem ONT, router MikroTik, dan perbaikan gangguan lapangan.\n"
                . "2. **Divisi Finance:** Memegang wewenang eksklusif untuk verifikasi pembayaran pelanggan, penagihan invoice, dan pembukuan kas/bank.\n"
                . "3. **Superadmin / Direksi:** Memiliki hak akses penuh (*Full Access*) ke seluruh data operasional dan keuangan.";
        }

        // 3. Customer Service (CS) vs OLT / Keuangan
        if ((str_contains($lower, 'cs') || str_contains($lower, 'customer service'))
            && (str_contains($lower, 'olt') || str_contains($lower, 'jaringan') || str_contains($lower, 'router') || str_contains($lower, 'kas') || str_contains($lower, 'rekening') || str_contains($lower, 'pembukuan'))) {
            return "### 🔒 Hak Akses Role: Customer Service (CS)\n\n"
                . "📋 **Cakupan Wewenang CS di IMS2:**\n"
                . "- 🟢 **Yang Diizinkan:** Melihat profil data pelanggan, mengecek status pembayaran invoice individual (untuk klarifikasi pelanggan), dan membuka tiket gangguan helpdesk.\n"
                . "- 🔴 **Yang Dilarang:** CS tidak memiliki akses ke konfigurasi core OLT/Router jaringan dan tidak dapat melihat buku kas/rekening internal bank.";
        }

        // 4. Superadmin (Admin / Direktur / CEO)
        if ((str_contains($lower, 'admin') || str_contains($lower, 'direktur') || str_contains($lower, 'ceo'))
            && (str_contains($lower, 'bisa apa') || str_contains($lower, 'wewenang') || str_contains($lower, 'akses') || str_contains($lower, 'tugas'))) {
            return "### 👑 Hak Akses Superadmin (Direktur, CEO, Admin) IMS2:\n\n"
                . "Role **Admin**, **Direktur**, dan **CEO** memiliki hak akses penuh (**Superadmin / Full Access**) ke seluruh sistem IMS2:\n"
                . "1. **Manajemen Pelanggan:** Akses penuh data registrasi, perubahan paket, mutasi alamat, isolir/suspend, hingga penarikan terminasi.\n"
                . "2. **Keuangan & Billing:** Melihat seluruh omset, rekap piutang, mutasi bank, dan verifikasi tagihan.\n"
                . "3. **Infrastruktur Jaringan:** Monitoring master OLT, telemetri port GPON, router MikroTik, dan tiket komplain.\n"
                . "4. **Kepegawaian (HRD) & AI Copilot:** Manajemen akun karyawan, audit log, dan monitoring token multi-kunci AI.";
        }

        // 5. Wewenang Spesifik: Finance
        if ((str_contains($lower, 'finance') || str_contains($lower, 'keuangan')) && (str_contains($lower, 'wewenang') || str_contains($lower, 'tugas') || str_contains($lower, 'fungsi') || str_contains($lower, 'bisa apa'))) {
            return "### 💼 Wewenang & Tanggung Jawab Divisi Finance IMS2:\n\n"
                . "1. **Verifikasi Pembayaran:** Memvalidasi bukti transfer pelanggan di menu *Billing Layanan > Verifikasi* (mengubah status `#14` ke `#15 Lunas`).\n"
                . "2. **Penerbitan Tagihan Bulanan:** Mengawasi jalannya cron auto-generate tagihan setiap tanggal 1 bulan berjalan.\n"
                . "3. **Laporan Keuangan:** Memantau realisasi omset, penerimaan kas harian, dan total piutang/tunggakan tertunggak.\n"
                . "4. **Permintaan Terminasi:** Mengajukan permohonan terminasi pelanggan yang menunggak lama ke antrean teknis.";
        }

        // 6. Wewenang Spesifik: NOC & Teknik
        if ((str_contains($lower, 'noc') || str_contains($lower, 'teknik')) && (str_contains($lower, 'wewenang') || str_contains($lower, 'tugas') || str_contains($lower, 'fungsi') || str_contains($lower, 'bisa apa'))) {
            return "### 📡 Wewenang & Tanggung Jawab Divisi NOC & Teknik IMS2:\n\n"
                . "1. **Konfigurasi OLT & GPON:** Mengatur master OLT, binding port ONU (`index_olt`), dan alokasi VLAN/PPPoE.\n"
                . "2. **Monitoring Telemetri Jaringan:** Memantau modem pelanggan berstatus LOS (kabel putus) atau DOWN (perangkat padam) secara realtime.\n"
                . "3. **Eksekusi Suspend & Isolir:** Melakukan pemutusan layanan di router MikroTik untuk pelanggan tertunggak setiap tanggal 25.\n"
                . "4. **Helpdesk & Gangguan:** Melakukan troubleshooting dan penugasan teknisi lapangan untuk perbaikan kabel optik/ODP.";
        }

        // 7. Matriks Seluruh Role di IMS2
        return "### 👥 Matriks Hak Akses Role Sistem IMS2:\n\n"
            . "| Divisi / Role | Akses Data Pelanggan | Akses Billing / Keuangan | Akses OLT / Jaringan | Tiket Gangguan |\n"
            . "|:---|:---:|:---:|:---:|:---:|\n"
            . "| **Direktur / CEO / Admin** | 🟢 Penuh | 🟢 Penuh (Omset, Mutasi) | 🟢 Penuh (OLT, MikroTik) | 🟢 Penuh |\n"
            . "| **Finance** | 🟡 Terkait Billing | 🟢 Penuh (Verifikasi, Rekap) | 🔴 Tidak Ada | ⚪ Baca Saja |\n"
            . "| **NOC / Teknik** | 🟢 Teknis Lapangan | 🔴 Tidak Ada (Dibatasi) | 🟢 Penuh (Master OLT & GPON) | 🟢 Eksekusi |\n"
            . "| **Customer Service** | 🟢 Registrasi & Profil | 🟡 Cek Status Tagihan Saja | 🔴 Tidak Ada | 🟢 Buat Tiket |\n"
            . "| **HRD** | ⚪ Internal Karyawan | 🔴 Tidak Ada | 🔴 Tidak Ada | 🔴 Tidak Ada |\n\n"
            . "> 🔒 *Setiap role dibatasi secara ketat oleh middleware keamanan IMS2 untuk menjaga privasi keuangan dan integritas jaringan.*";
    }

    /**
     * Resolves queries regarding customer billing, payment workflows, and finance approval procedures.
     */
    protected function handlePaymentWorkflowQuestion(string $lower): ?string
    {
        $isPaymentWorkflow = preg_match('/\b(alur|proses|tahapan|cara|mekanisme|sop|bagaimana|gimana)\b/i', $lower)
            && preg_match('/\b(bayar|pembayaran|tagihan|billing|finance|verifikasi)\b/i', $lower)
            && !preg_match('/\b(buka isolir|terminasi|suspend|kue|bolu)\b/i', $lower);

        if (!$isPaymentWorkflow) {
            return null;
        }

        return "### 💳 Alur Pembayaran Tagihan & Verifikasi Finance IMS2:\n\n"
            . "Berikut siklus lengkap alur pembayaran tagihan internet di sistem IMS2:\n\n"
            . "1. **📅 Penerbitan Tagihan (Invoice Bulanan):**\n"
            . "   - Setiap **tanggal 1 pukul 08:00 WIB**, sistem secara otomatis men-generate tagihan berstatus `#12 (Publish)` untuk seluruh pelanggan aktif.\n"
            . "   - Pelanggan menerima notifikasi rincian tagihan secara otomatis melalui pesan WhatsApp.\n\n"
            . "2. **📲 Proses Pembayaran Pelanggan:**\n"
            . "   - **Opsi A: Portal Pelanggan (*Self-Service* - Otomatis):**\n"
            . "     - Pelanggan login mandiri ke Portal Pelanggan (`ptmsn`).\n"
            . "     - Memilih pembayaran instan via **QRIS, Virtual Account, ShopeePay, atau Gopay**.\n"
            . "     - **Hasil:** Status tagihan seketika berubah menjadi `#15 Lunas` secara otomatis (*realtime*) tanpa verifikasi manual.\n"
            . "   - **Opsi B: Transfer Manual Rekening Perusahaan / Kasir Loket:**\n"
            . "     - Pelanggan mentransfer ke rekening bank PT MSN atau membayar cash ke petugas kasir/kolektor.\n"
            . "     - Pelanggan mengunggah foto bukti transfer melalui menu konfirmasi pembayaran portal.\n"
            . "     - **Hasil:** Status tagihan berubah menjadi `#14 (Menunggu Verifikasi)`.\n\n"
            . "3. **🔍 Verifikasi oleh Tim Finance:**\n"
            . "   - Staf Finance membuka menu **Billing Layanan > Verifikasi Pembayaran**.\n"
            . "   - Mencocokkan nominal transfer dengan mutasi rekening bank perusahaan.\n"
            . "   - Klik tombol **Setujui Verifikasi** $\\rightarrow$ Status tagihan sah berubah menjadi `#15 Lunas`.\n\n"
            . "4. **🔄 Dampak Otomatis Sistem (*Unsuspend*):**\n"
            . "   - Jika pelanggan sebelumnya dalam status isolir (`#11`), pelunasan tagihan seketika memicu otomasi pembukaan isolir (*unsuspend*) ke router MikroTik/OLT tim NOC tanpa intervensi manual.";
    }

    /**
     * Resolves queries regarding database architecture, tables, and system data models.
     */
    protected function handleDatabaseQuestion(string $lower): ?string
    {
        $isDatabaseQuery = preg_match('/\b(database|basis data|tabel|table|schema|skema|kolom|struktur db)\b/i', $lower)
            && (preg_match('/\b(apa saja|ada apa|daftar|sebutkan|struktur|arsitektur|pakai apa|menggunakan apa|penjelasan|hubungan)\b/i', $lower) || str_contains($lower, 'database'));

        if (!$isDatabaseQuery) {
            return null;
        }

        return "### 🗄️ Struktur Database & Skema Tabel Sistem IMS2:\n\n"
            . "Sistem IMS2 menggunakan database **MySQL / MariaDB** dengan ORM Laravel Eloquent & Query Builder berkinerja tinggi. Berikut tabel-tabel utama:\n\n"
            . "1. **👥 Pelanggan & Registrasi:**\n"
            . "   - `trx_batchjob_register` / `view_batchjob`: Tabel inti data pelanggan, alamat pasang, nomor telepon, PPPoE username, paket layanan, dan siklus status registrasi (`#10` s/d `#23`).\n"
            . "   - `m_pelanggan`: Master data kependudukan (NIK, nama lengkap, kontak darurat).\n\n"
            . "2. **💳 Keuangan & Billing:**\n"
            . "   - `trx_billing_layanan`: Invoice bulanan pelanggan (`kode_billing_layanan`, `nomor_internet`, `total_layanan`, `status_bill_lay` #11-#15, `bulan_tagihan`, `tahun_tagihan`, `payment_paid`).\n"
            . "   - `payment_confirmations`: Antrean konfirmasi unggahan bukti transfer manual dari pelanggan.\n\n"
            . "3. **📡 Infrastruktur Jaringan & OLT:**\n"
            . "   - `m_olt`: Master perangkat OLT (`kode_olt`, `name_olt`, `ip_address`, `brand`, `model`, `is_active`).\n"
            . "   - `m_port_olt`: Pemetaan port fisik GPON OLT.\n"
            . "   - `tb_router`: Master router MikroTik gateway pelanggan.\n\n"
            . "4. **⏱️ Otomasi & Gangguan:**\n"
            . "   - `trx_suspend`: Antrean auto-suspend tgl 25 dan perintah buka isolir.\n"
            . "   - `trx_tiket_gangguan`: Tiket komplain pelanggan helpdesk (`tiket`, `keluhan`, `penanganan`, `status`).\n"
            . "   - `tb_pengguna` & `tb_level`: Otentikasi staf, password hash, dan wewenang role user.";
    }

    /**
     * Resolves queries regarding new customer installation and lifecycle registration.
     */
    protected function handleRegistrationWorkflowQuestion(string $lower): ?string
    {
        $isRegFlow = preg_match('/\b(alur|proses|tahapan|cara|mekanisme|sop|bagaimana|gimana)\b/i', $lower)
            && preg_match('/\b(pasang baru|registrasi|daftar pelanggan|pendaftaran|instalasi baru|pemasangan)\b/i', $lower);

        if (!$isRegFlow) {
            return null;
        }

        return "### 📋 Alur Pendaftaran & Pasang Baru Pelanggan IMS2:\n\n"
            . "Berikut tahapan dari pendaftaran awal hingga internet pelanggan online aktif:\n\n"
            . "1. **📝 Registrasi Masuk (`#10`):** CS / Sales menginput data identitas pemohon, nomor telepon, dan paket internet yang dipilih.\n"
            . "2. **🔍 Survey Lokasi (`#12`):** Tim Teknik melakukan survey ke titik lokasi untuk mengecek ketersediaan port ODP dan pengukuran jarak tarikan kabel optik.\n"
            . "3. **🛠️ Penarikan Dropcore & Pasang ONT (`#16`):** Teknisi menarik kabel dropcore optik dari ODP ke rumah pelanggan serta memasang modem ONT/ONU.\n"
            . "4. **💻 Aktivasi & Binding NOC (`#18` / `#19`):** Tim NOC mengalokasikan akun PPPoE, binding VLAN, dan mendaftarkan port GPON OLT (`index_olt`).\n"
            . "5. **🟢 Online Aktif (`#20`):** Layanan internet resmi aktif, sinyal optik stabil, dan pelanggan masuk ke siklus penagihan bulanan reguler.";
    }

    /**
     * Resolves queries regarding network tickets, helpdesk complaints, and field technician dispatch.
     */
    protected function handleTicketWorkflowQuestion(string $lower): ?string
    {
        $isTicketFlow = preg_match('/\b(alur|proses|tahapan|cara|mekanisme|sop|bagaimana|gimana)\b/i', $lower)
            && preg_match('/\b(tiket|gangguan|komplain|keluhan|kendala|helpdesk|laporan rusak)\b/i', $lower);

        if (!$isTicketFlow) {
            return null;
        }

        return "### 🎫 Alur Penanganan Tiket Gangguan & Helpdesk IMS2:\n\n"
            . "Berikut prosedur penanganan keluhan internet pelanggan:\n\n"
            . "1. **Laporan Masuk & Pembuatan Tiket (`OPEN`):** CS atau pelanggan membuat tiket komplain di sistem dengan mencantumkan nomor internet dan rincian keluhan.\n"
            . "2. **Diagnosa Telemetri NOC:** Tim NOC mengecek status modem secara live di sistem telemetri OLT (mendeteksi apakah status `LOS` / kabel putus, `DOWN` / modem mati lampu, atau redaman tinggi).\n"
            . "3. **Penugasan Teknisi Lapangan:** Jika ditemukan kendala fisik jalur optik, tiket dieskalasi ke teknisi lapangan terdekat beserta titik ODP target.\n"
            . "4. **Perbaikan Jalur & Splicing:** Teknisi melakukan perbaikan kabel, splicing ulang, dan kalibrasi redaman hingga normal (`-15.00` s/d `-24.00 dBm`).\n"
            . "5. **Penyelesaian & Tutup Tiket (`CLOSED`):** Setelah koneksi diverifikasi online dan dikonfirmasi ke pelanggan, status tiket diubah menjadi `Selesai / Closed`.";
    }

    /**
     * Resolves queries regarding ISP and telecommunication terms (OLT, ONT, GPON, LOS, PPPoE, ODP).
     */
    protected function handleNetworkGlossaryQuestion(string $lower): ?string
    {
        $isGlossary = preg_match('/\b(apa itu|apa artinya|pengertian|definisi|singkatan dari|apa maksud)\b/i', $lower)
            && preg_match('/\b(olt|ont|onu|gpon|los|pppoe|odp|odc|dropcore|splicing|vlan|dbm|snmp)\b/i', $lower);

        if (!$isGlossary) {
            return null;
        }

        if (str_contains($lower, 'olt')) {
            return "### 📡 Apa itu OLT (Optical Line Terminal)?\n\n"
                . "**OLT** adalah perangkat induk fiber optik yang berada di kantor pusat (*Data Center / Central Office*) ISP PT Media Solusi Network.\n\n"
                . "Fungsi utamanya adalah memancarkan laser optik GPON ke ratusan modem pelanggan melalui splitter pasif. Di IMS2, master OLT tersimpan di tabel `m_olt` (misal: OLT Kayu Agung, OLT Babakan Tarogong, OLT Soreang).";
        }

        if (str_contains($lower, 'ont') || str_contains($lower, 'onu')) {
            return "### 📟 Apa itu ONT / ONU (Optical Network Terminal)?\n\n"
                . "**ONT/ONU** adalah perangkat modem pelanggan di lokasi rumah/kantor yang mengubah sinyal cahaya optik dari kabel dropcore menjadi sinyal elektrik ethernet (LAN) dan WiFi untuk dipakai pengguna.";
        }

        if (str_contains($lower, 'gpon')) {
            return "### 💡 Apa itu GPON (Gigabit Passive Optical Network)?\n\n"
                . "**GPON** adalah teknologi jaringan akses optik pasif berkecepatan tinggi yang digunakan oleh PT MSN. GPON mampu mendistribusikan bandwidth hingga 2.5 Gbps downstream ke pelanggan melalui satu core kabel menggunakan pembagi optik (*splitter*).";
        }

        if (str_contains($lower, 'los')) {
            return "### 🔴 Apa itu Status LOS (Loss of Signal)?\n\n"
                . "**LOS** menandakan bahwa modem ONT pelanggan tidak menerima daya cahaya laser optik sama sekali (biasanya redaman di atas `-40 dBm`).\n\n"
                . "Penyebab paling umum: kabel serat optik dropcore putus, konektor biru/hijau kendor, atau kabel utama distribusi terkena pohon/kendaraan.";
        }

        if (str_contains($lower, 'pppoe')) {
            return "### 🔐 Apa itu PPPoE (Point-to-Point Protocol over Ethernet)?\n\n"
                . "**PPPoE** adalah protokol otentikasi jaringan yang digunakan router MikroTik IMS2 untuk mengidentifikasi akun pelanggan via username & password unik, sekaligus membatasi kecepatan bandwidth paket internet sesuai paket yang dibeli.";
        }

        if (str_contains($lower, 'odp') || str_contains($lower, 'odc')) {
            return "### 📦 Apa itu ODP & ODC?\n\n"
                . "- **ODP (Optical Distribution Point):** Kotak panel putih/hitam di tiang listrik jalan tempat teknisi mencolokkan kabel dropcore pelanggan (biasanya kapasitas 8 atau 16 port).\n"
                . "- **ODC (Optical Distribution Cabinet):** Lemari kabinet optik kapasitas besar (96-288 core) yang menghubungkan kabel feeder dari kantor pusat ke beberapa titik ODP di lapangan.";
        }

        return null;
    }

    /**
     * Local heuristic response generator when API key is missing or service unreachable.
     */
    protected function generateLocalResponse(string $query, array $context, $user, bool $isFallback = false, ?string $errorMessage = null, array $history = []): string
    {
        $userName = $user?->nama ?? $user?->nama_lengkap ?? $user?->username ?? 'Rekan IMS';
        $userRole = $this->getUserRole($user);
        $lower = strtolower(trim($query));
        $output = [];

        // Context-awareness: Extract recent AI and User context from conversation history
        $lastAiTopic = '';
        if (!empty($history)) {
            $rev = array_reverse($history);
            foreach ($rev as $item) {
                $role = $item['role'] ?? ($item['sender'] ?? '');
                if (in_array($role, ['model', 'assistant', 'ai'], true)) {
                    $lastAiTopic = strtolower($item['text'] ?? ($item['content'] ?? ''));
                    break;
                }
            }
        }

        // Handle follow-up solutions / "bagaimana solusinya" / "kenapa begitu" / "cara mengatasinya"
        $isAskingSolution = preg_match('/\b(solusi|solusinya|bagaimana caranya|gimana caranya|langkahnya|cara mengatasi|kenapa begitu|penyebabnya)\b/i', $lower);
        if ($isAskingSolution && !empty($lastAiTopic)) {
            if (str_contains($lastAiTopic, 'redaman') || str_contains($lastAiTopic, 'index_olt') || str_contains($lastAiTopic, 'gpon')) {
                return "### 🛠️ Solusi Teknis Redaman Optik Tidak Terbaca:\n\n"
                    . "1. **Buka Menu Pelanggan:** Masuk ke menu **Data Pelanggan > Edit Data Registrasi** pada pelanggan bersangkutan.\n"
                    . "2. **Cek Port GPON Fisik:** Hubungi teknisi lapangan atau cek splitter ODP untuk mengetahui port PON pelanggan di OLT.\n"
                    . "3. **Isi Kolom Index OLT:** Masukkan identitas port dengan format resmi: `gpon-onu_1/{slot}/{port}:{onu_id}` (contoh: `gpon-onu_1/1/2:8`).\n"
                    . "4. **Simpan:** Setelah disimpan, sensor OLT via SNMP/Telnet akan otomatis dapat membaca level redaman modem dan status fisik (UP/LOS/DOWN) secara realtime.";
            }
            if (str_contains($lastAiTopic, 'suspend') || str_contains($lastAiTopic, 'isolir')) {
                return "### 🛠️ Solusi & Prosedur Buka Isolir (Unsuspend):\n\n"
                    . "1. **Penyelesaian Tagihan:** Pelanggan menyelesaikan tagihan tertunggak melalui transfer bank atau kasir.\n"
                    . "2. **Verifikasi Finance:** Tim Finance memvalidasi bukti transfer pada menu **Billing Layanan > Verifikasi** sehingga status menjadi `#15 Lunas`.\n"
                    . "3. **Otomasi Unsuspend:** Sistem IMS2 secara otomatis menerbitkan antrean buka isolir ke router NOC/MikroTik tanpa perlu input manual.";
            }
            if (str_contains($lastAiTopic, 'pending') || str_contains($lastAiTopic, 'verifikasi')) {
                return "### 🛠️ Solusi Verifikasi Pembayaran Finance:\n\n"
                    . "1. Buka menu **Billing Layanan > Verifikasi Pembayaran**.\n"
                    . "2. Klik nomor invoice terkait untuk melihat bukti transfer atau mutasi bank.\n"
                    . "3. Cocokkan nominal `total_layanan` dengan mutasi rekening perusahaan.\n"
                    . "4. Klik tombol **Setujui Verifikasi** untuk mengubah status ke `#15 Lunas`.";
            }
        }

        // Domain Knowledge: Role Matrix, Workflows & Architecture
        if ($roleAccessReply = $this->handleRoleAccessQuestion($lower, $user)) {
            return $roleAccessReply;
        }
        if ($paymentWorkflowReply = $this->handlePaymentWorkflowQuestion($lower)) {
            return $paymentWorkflowReply;
        }
        if ($databaseReply = $this->handleDatabaseQuestion($lower)) {
            return $databaseReply;
        }
        if ($regWorkflowReply = $this->handleRegistrationWorkflowQuestion($lower)) {
            return $regWorkflowReply;
        }
        if ($ticketWorkflowReply = $this->handleTicketWorkflowQuestion($lower)) {
            return $ticketWorkflowReply;
        }
        if ($glossaryReply = $this->handleNetworkGlossaryQuestion($lower)) {
            return $glossaryReply;
        }
        if ($featuresReply = $this->handleFeaturesQuestion($lower)) {
            return $featuresReply;
        }
        if ($diffReply = $this->handleMikrotikOltDifference($lower)) {
            return $diffReply;
        }
        if ($onuReply = $this->handleOnuStatusQuestion($lower)) {
            return $onuReply;
        }
        if ($portReply = $this->handlePortOltMappingQuestion($query, $lower)) {
            return $portReply;
        }
        if ($routersReply = $this->handleRoutersListQuestion($lower)) {
            return $routersReply;
        }
        if ($countReply = $this->handleCustomerCountQuestion($lower)) {
            return $countReply;
        }
        if ($suspListReply = $this->handleSuspendedCustomersList($lower)) {
            return $suspListReply;
        }
        if ($unpaidReply = $this->handleUnpaidBillsQuestion($lower, $user)) {
            return $unpaidReply;
        }
        if ($ticketsReply = $this->handleTicketsDirectQuestion($lower)) {
            return $ticketsReply;
        }
        if ($sopRouterReply = $this->handleFinanceRouterSopQuestion($lower)) {
            return $sopRouterReply;
        }

        // 1. Pre-checks: Role permissions
        $isAskingFinance = str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar') 
            || str_contains($lower, 'omset') || str_contains($lower, 'omzet') || str_contains($lower, 'keuangan') 
            || str_contains($lower, 'invoice') || str_contains($lower, 'pending') || str_contains($lower, 'verif') 
            || str_contains($lower, 'lunas') || str_contains($lower, 'piutang') || str_contains($lower, 'tunggak');

        if ($isAskingFinance && !$this->canAccessFinance($user)) {
            return "### 🔒 Akses Ditolak (Role: {$userRole})\n"
                . "Akun Anda terdaftar sebagai **{$userRole}**. Akses ke informasi tagihan, verifikasi pembayaran, omset, dan data keuangan dibatasi khusus untuk divisi **Finance** dan **Direksi**.";
        }

        $isAskingOlt = (str_contains($lower, 'olt') || str_contains($lower, 'router') || str_contains($lower, 'redaman') 
            || str_contains($lower, 'gpon') || str_contains($lower, 'optik') || str_contains($lower, 'pon')) 
            && !str_contains($lower, 'pelanggan') && !str_contains($lower, 'cust');

        if ($isAskingOlt && !$this->canAccessNetwork($user)) {
            return "### 🔒 Akses Ditolak (Role: {$userRole})\n"
                . "Akun Anda terdaftar sebagai **{$userRole}**. Akses ke konfigurasi teknis perangkat OLT dan infrastruktur jaringan dibatasi khusus untuk divisi **NOC**, **Teknik**, dan **Direksi**.";
        }

        $isAskingHr = str_contains($lower, 'gaji') || str_contains($lower, 'salary') || str_contains($lower, 'karyawan') 
            || str_contains($lower, 'absensi') || str_contains($lower, 'hrd');

        if ($isAskingHr && !$this->canAccessHr($user)) {
            return "### 🔒 Akses Ditolak (Role: {$userRole})\n"
                . "Akun Anda terdaftar sebagai **{$userRole}**. Data kepegawaian dan penggajian karyawan bersifat rahasia dan dibatasi khusus untuk divisi **HRD** dan **Direksi**.";
        }

        // =========================================================================
        // DOMAIN KNOWLEDGE & APPLICATION SOP (APLIKASI & ALUR KERJA IMS2)
        // =========================================================================

        // A. Penjelasan "Portal Pelanggan" vs "Bukan Portal / Manual Finance"
        $isAskingPortal = str_contains($lower, 'portal') || str_contains($lower, 'selfservice') 
            || str_contains($lower, 'self service') || str_contains($lower, 'self-service')
            || (str_contains($lower, 'bukan portal') || str_contains($lower, 'non portal'));

        if ($isAskingPortal) {
            $output[] = "### 🌐 Penjelasan: Portal Pelanggan vs Bukan Portal di IMS2";
            $output[] = "Di sistem operasional IMS2 dan notifikasi pembayaran, terdapat dua kategori asal transaksi:\n";
            $output[] = "1. **🌐 Portal Pelanggan (*Self-Service*):**";
            $output[] = "   - **Definisi:** Transaksi yang dilakukan secara mandiri oleh pelanggan sendiri melalui website **Portal Pelanggan** PT MSN (`ptmsn`) tanpa keterlibatan staf finance.";
            $output[] = "   - **Metode Pembayaran:**";
            $output[] = "     - Pelanggan login mandiri dengan Nomor Internet / Telepon.";
            $output[] = "     - Pembayaran otomatis melalui *Payment Gateway* (**QRIS, Midtrans Snap, Gopay, ShopeePay, Virtual Account**).";
            $output[] = "     - Pelanggan mengunggah sendiri foto bukti transfer melalui menu konfirmasi pembayaran di portal.";
            $output[] = "   - **Status di Sistem:** Sistem otomatis mendeteksi transaksi ini sebagai sumber *Portal Pelanggan*.\n";
            $output[] = "2. **🏢 Bukan Portal (Internal Finance / Kasir / Kolektor):**";
            $output[] = "   - **Definisi:** Transaksi yang diproses atau diverifikasi langsung oleh pihak internal PT MSN (bukan pelanggan yang menginput sendiri).";
            $output[] = "   - **Metode Pembayaran:**";
            $output[] = "     - **Cash to Collector:** Petugas lapangan/kolektor menerima uang tunai langsung dari pelanggan.";
            $output[] = "     - **Kasir Kantor:** Pelanggan datang membayar langsung ke loket kantor PT MSN.";
            $output[] = "     - **Input / Verifikasi Manual Finance:** Staf Finance menerima mutasi rekening bank perusahaan lalu melakukan verifikasi langsung di menu Billing Layanan.\n";
            $output[] = "💡 **Catatan Rekonsiliasi:**";
            $output[] = "Jika ada pembayaran transfer bank yang diproses manual oleh tim Finance di IMS, transaksi tersebut masuk ke kategori **Bukan Portal**.";
            return implode("\n", $output);
        }

        // B. Kamus Kode Status Registrasi Pelanggan (#10 s/d #23)
        $isAskingRegStatus = (str_contains($lower, 'status reg') || str_contains($lower, 'status registrasi') 
            || str_contains($lower, 'siklus pelanggan') || str_contains($lower, 'alur pelanggan') 
            || str_contains($lower, 'alur pasang baru') || str_contains($lower, 'tahapan registrasi')
            || (str_contains($lower, 'status') && preg_match('/\b(10|12|14|15|16|18|19|20|21|23)\b/', $lower)));

        if ($isAskingRegStatus && empty($context['customers'])) {
            $output[] = "### 📋 Kamus & Alur Kode Status Registrasi Pelanggan IMS2:";
            $output[] = "Berikut tahapan siklus hidup (*lifecycle*) pelanggan di sistem IMS2:\n";
            $output[] = "| Kode Status | Tahapan Operasional | Divisi Penanggung Jawab | Keterangan & Tindak Lanjut |";
            $output[] = "|:---:|:---|:---|:---|";
            $output[] = "| `#10` | **Registrasi Masuk** | CS / Sales | Formulir pendaftaran baru diterima, menunggu jadwal survey. |";
            $output[] = "| `#12` | **Survey Lokasi** | Teknik | Pengecekan ketersediaan ODP, jalur kabel, dan redaman titik pasang. |";
            $output[] = "| `#16` | **Instalasi Dropcore** | Teknik | Penarikan kabel serat optik dan pemasangan modem ONT/ONU di lokasi. |";
            $output[] = "| `#18` / `#19` | **Aktivasi NOC** | NOC | Konfigurasi port OLT, alokasi IP/PPPoE, dan binding VLAN internet. |";
            $output[] = "| `#20` | 🟢 **Online Aktif** | Sistem / NOC | Layanan internet aktif, pelanggan online, dan terbit jadwal billing. |";
            $output[] = "| `#11` | 🔴 **Isolir (Suspend)** | Sistem / NOC | Layanan diputus sementara otomatis setiap tanggal 25 akibat tagihan tertunggak. |";
            $output[] = "| `#21` | 🟡 **Reaktivasi / Mutasi** | NOC / CS | Proses buka isolir kembali atau pemindahan alamat pelanggan. |";
            $output[] = "| `#14` / `#15` | ⚪ **Batal Pasang** | CS / Admin | Permintaan pemasangan dibatalkan oleh pelanggan atau kendala teknis jalur. |";
            $output[] = "| `#23` | ⚫ **Dismantle (Putus)** | Teknik | Penonaktifan permanen dan penarikan perangkat modem dari pelanggan. |";
            return implode("\n", $output);
        }

        // C. Kamus Kode Status Billing & Tagihan (#11 s/d #15)
        $isAskingBillStatus = (str_contains($lower, 'status bill') || str_contains($lower, 'status tagihan') 
            || str_contains($lower, 'arti status bill') || str_contains($lower, 'alur tagihan')
            || (str_contains($lower, 'tagihan') && str_contains($lower, 'status') && preg_match('/\b(11|12|13|14|15)\b/', $lower)));

        if ($isAskingBillStatus && empty($context['billings'])) {
            $output[] = "### 💳 Kamus Kode Status Tagihan & Billing IMS2:";
            $output[] = "Tagihan layanan internet bulanan (`trx_billing_layanan`) memiliki 5 tahapan status:\n";
            $output[] = "| Kode Status | Status Tagihan | Arti & Konsekuensi Sistem |";
            $output[] = "|:---:|:---|:---|";
            $output[] = "| `#11` | ⚪ **Draft** | Tagihan baru di-generate oleh sistem/finance, belum dipublish ke pelanggan. |";
            $output[] = "| `#12` | 🔵 **Publish** | Tagihan resmi diterbitkan (tgl 1 setiap bulan), pelanggan menerima notifikasi WhatsApp. |";
            $output[] = "| `#13` | 🔴 **Unpaid (Belum Bayar)** | Tagihan aktif belum terbayar, menunggu pelunasan sebelum batas tanggal 24. |";
            $output[] = "| `#14` | 🟡 **Menunggu Verifikasi** | Bukti transfer telah masuk (via portal/upload), memerlukan persetujuan finance. |";
            $output[] = "| `#15` | 🟢 **Lunas (Paid)** | Pembayaran sah diverifikasi. Jika pelanggan terisolir, otomatis terbuka (*unsuspend*). |";
            return implode("\n", $output);
        }

        // D. Prosedur Buka Isolir (Unsuspend)
        $isAskingUnsuspend = preg_match('/\b(buka isolir|cara buka isolir|unsuspend|un-suspend|buka suspend|prosedur buka isolir)\b/i', $lower);
        if ($isAskingUnsuspend && empty($context['customers'])) {
            $output[] = "### 🔄 Prosedur Buka Isolir (Unsuspend) Pelanggan IMS2:";
            $output[] = "1. **Pelunasan Tagihan:** Pelanggan menyelesaikan pembayaran tagihan tertunggak melalui Portal Pelanggan (QRIS/Payment Gateway) atau transfer bank ke rekening perusahaan.";
            $output[] = "2. **Verifikasi Finance:** Tim Finance memverifikasi bukti pembayaran di menu **Billing Layanan > Verifikasi** (status tagihan berubah dari `#14` menjadi `#15 Lunas`).";
            $output[] = "3. **Otomasi Unsuspend:** Sistem IMS2 secara otomatis menghapus antrean isolir dan mengirimkan perintah buka blokir (*unsuspend*) ke router MikroTik/OLT tim NOC.";
            $output[] = "4. **Pengajuan Manual:** Jika dibutuhkan pembukaan isolir darurat atas persetujuan pimpinan, admin dapat membuka isolir melalui menu **Data Pelanggan > Buka Isolir**.";
            return implode("\n", $output);
        }

        // E. Prosedur Permintaan Terminasi (Tutup Akun & Putus Berlangganan)
        $isAskingTerminasi = preg_match('/\b(terminasi|putus langganan|berhenti langganan|cabut perangkat|tutup akun|dismantle|cara terminasi|prosedur terminasi)\b/i', $lower);
        if ($isAskingTerminasi && empty($context['customers'])) {
            $output[] = "### 🔌 Prosedur Permintaan Terminasi (Tutup Akun) IMS2:";
            $output[] = "1. **Pengajuan Request:** Petugas (Finance/Teknik/CS) membuka menu **Data Pelanggan**, klik tombol **Request Terminasi** pada pelanggan terkait.";
            $output[] = "2. **Pilih Alasan Berhenti:** Pilih alasan utama (Pindah rumah, tarif, tunggakan, atau *Lainnya* dengan menuliskan alasan spesifik di luar pilihan dropdown).";
            $output[] = "3. **Antrean NOC & Lapangan:** Permintaan masuk ke menu **Permintaan Terminasi** berstatus `#11 (Request Baru)`.";
            $output[] = "4. **Eksekusi Penarikan:** Tim NOC menonaktifkan konfigurasi PPPoE/port OLT dan teknisi lapangan menarik perangkat modem ONT/ONU dari lokasi pelanggan.";
            $output[] = "5. **Status Akhir:** Status pelanggan resmi berubah menjadi `#23 Dismantle (Putus)` dan penerbitan invoice bulanan dihentikan.";
            return implode("\n", $output);
        }

        // F. Jadwal Otomatis Cron (Suspend & Invoice)
        $isAskingSchedule = (str_contains($lower, 'suspend') || str_contains($lower, 'isolir') || str_contains($lower, 'tagihan') || str_contains($lower, 'invoice')) 
            && (str_contains($lower, 'jadwal') || str_contains($lower, 'kapan') || str_contains($lower, 'tanggal') || str_contains($lower, 'jam berapa') || str_contains($lower, 'jatuh tempo') || str_contains($lower, 'cron') || str_contains($lower, 'otomatis') || str_contains($lower, 'auto'));

        if ($isAskingSchedule && empty($context['customers'])) {
            $output[] = "### ⏱️ Jadwal Otomatis & Prosedur Suspend (Isolir) IMS2:";
            $output[] = "Berdasarkan sistem penjadwalan resmi (*Scheduler Cron*) IMS2:\n";
            $output[] = "1. **🧾 Jadwal Terbit Tagihan (Invoice Bulanan):**";
            $output[] = "   - Invoice massal bulanan diterbitkan otomatis setiap **tanggal 1 setiap bulan pukul 08:00 WIB** (`finance:auto-generate-invoices`).";
            $output[] = "   - Menghasilkan tagihan berstatus `#12 Publish` untuk seluruh pelanggan aktif (`status_reg = 20`).\n";
            $output[] = "2. **⚠️ Rentang Pembayaran & Jatuh Tempo:**";
            $output[] = "   - Pelanggan diberikan rentang pembayaran mulai **tanggal 1 hingga tanggal 24 pukul 23:59 WIB**.\n";
            $output[] = "3. **📅 Terbit Request Suspend Otomatis:**";
            $output[] = "   - Request suspend otomatis diterbitkan setiap **tanggal 25 setiap bulan pada pukul 06:00 WIB pagi**.";
            $output[] = "   - Sistem menjalankan cron `suspend:unpaid-customers` untuk menyaring seluruh pelanggan aktif yang tagihannya belum berstatus lunas (`status_bill_lay != 15`).\n";
            $output[] = "4. **🔄 Buka Isolir (Unsuspend):**";
            $output[] = "   - Buka isolir terbit otomatis seketika tim Finance memverifikasi dan menyetujui pembayaran pelanggan (status `#14` $\\rightarrow$ `#15`), atau diajukan manual oleh admin.";
            return implode("\n", $output);
        }

        // G. Troubleshooting Redaman Optik & Panduan Index OLT
        $isAskingWhyOlt = (str_contains($lower, 'kenapa') || str_contains($lower, 'mengapa') || str_contains($lower, 'cara') || str_contains($lower, 'panduan') || str_contains($lower, 'standar'))
            && (str_contains($lower, 'olt') || str_contains($lower, 'redaman') || str_contains($lower, 'index') || str_contains($lower, 'optik'));

        if ($isAskingWhyOlt && empty($context['customers'])) {
            $output[] = "### 📡 Panduan Teknis Redaman Optik & Index Port OLT IMS2:";
            $output[] = "1. **🔍 Mengapa Redaman Pelanggan Tidak Terbaca?**";
            $output[] = "   - OLT membaca redaman modem fisik pelanggan menggunakan perintah SNMP/Telnet yang membutuhkan identitas port fisik pada kolom **Index OLT** (`index_olt`).";
            $output[] = "   - Format resmi port GPON ZTE/Huawei: `gpon-onu_1/{slot}/{port}:{onu_id}` (contoh: `gpon-onu_1/1/2:8`).";
            $output[] = "   - Jika kolom `index_olt` pada data pelanggan masih **NULL/kosong**, laser OLT tidak mengetahui target port pelanggan di splitter mana.\n";
            $output[] = "2. **🛠️ Cara Mengisi Index OLT Pelanggan:**";
            $output[] = "   - Buka menu **Data Pelanggan > Edit Data Registrasi**.";
            $output[] = "   - Masukkan OLT induk dan format port GPON yang sesuai di kolom **Index OLT** lalu simpan.\n";
            $output[] = "3. **📊 Standar Ukuran Redaman Optik GPON:**";
            $output[] = "   - 🟢 **Sangat Baik / Normal:** `-15.00 dBm` s/d `-24.00 dBm`";
            $output[] = "   - 🟡 **Redaman Tinggi (Warning):** `-25.00 dBm` s/d `-27.00 dBm` (perlu pembersihan konektor/splicing ulang)";
            $output[] = "   - 🔴 **Kritis / LOS (Loss of Signal):** Di atas `-28.00 dBm` atau `-40.00 dBm` (kabel dropcore putus / power optik hilang).";
            return implode("\n", $output);
        }

        // =========================================================================
        // REALTIME DATABASE QUERIES & LIVE DATA RESOLUTION
        // =========================================================================

        // 1. Tagihan Pending / Menunggu Verifikasi Finance Realtime
        $isAskingPending = str_contains($lower, 'pending') || str_contains($lower, 'verif') 
            || str_contains($lower, 'approval') || str_contains($lower, 'menunggu verifikasi')
            || str_contains($lower, 'menunggu approval') || str_contains($lower, 'konfirmasi bayar');

        if ($isAskingPending && $this->canAccessFinance($user)) {
            $tableCustomer = Schema::hasTable('view_batchjob') ? 'view_batchjob' : 'trx_batchjob_register';
            $pendingBills = DB::table('trx_billing_layanan')
                ->leftJoin($tableCustomer, 'trx_billing_layanan.nomor_internet', '=', "{$tableCustomer}.nomor_internet")
                ->where('trx_billing_layanan.status_bill_lay', '14')
                ->where('trx_billing_layanan.hide', 0)
                ->select([
                    'trx_billing_layanan.kode_billing_layanan',
                    'trx_billing_layanan.nomor_internet',
                    'trx_billing_layanan.total_layanan',
                    'trx_billing_layanan.merchant_type',
                    'trx_billing_layanan.date_update',
                    "{$tableCustomer}.nama_pelanggan",
                ])
                ->limit(10)
                ->get();

            $pendingCount = count($pendingBills);
            if ($pendingCount === 0) {
                // Periksa juga payment_confirmations yang belum approved
                $pcPendingCount = 0;
                if (Schema::hasTable('payment_confirmations')) {
                    $pcPendingCount = DB::table('payment_confirmations')
                        ->where('status', '!=', 'approved')
                        ->where('status', '!=', 'rejected')
                        ->count();
                }
                $pendingCount = $pcPendingCount;
            }

            $output[] = "### 💳 Status Tagihan Menunggu Verifikasi (Pending Approval)";
            if ($pendingCount === 0) {
                $output[] = "✅ **Saat ini tidak ada tagihan yang menunggu verifikasi.**";
                $output[] = "Seluruh konfirmasi pembayaran pelanggan sudah berstatus disetujui (lunas).";
            } else {
                $output[] = "📌 Terdapat **{$pendingCount} tagihan** yang sedang menunggu verifikasi Finance:\n";
                if ($pendingBills->isNotEmpty()) {
                    $output[] = "| No. Invoice | No. Internet | Nama Pelanggan | Nominal | Metode / Bank |";
                    $output[] = "|---|---|---|---|---|";
                    foreach ($pendingBills as $pb) {
                        $nom = 'Rp ' . number_format((float)($pb->total_layanan ?? 0), 0, ',', '.');
                        $cust = $pb->nama_pelanggan ?: 'Pelanggan';
                        $bank = $pb->merchant_type ?: 'Transfer Manual';
                        $output[] = "| `{$pb->kode_billing_layanan}` | `{$pb->nomor_internet}` | **{$cust}** | `{$nom}` | {$bank} |";
                    }
                }
                $output[] = "\n👉 *Verifikasi dapat dilakukan langsung pada menu **Billing & Keuangan > Notifikasi**.*";
            }
            return implode("\n", $output);
        }

        // 2. Omset, Pendapatan & Tagihan Lunas Realtime
        $isAskingRevenue = (str_contains($lower, 'omset') || str_contains($lower, 'omzet') 
            || str_contains($lower, 'pendapatan') || str_contains($lower, 'pemasukan') 
            || str_contains($lower, 'uang masuk') || str_contains($lower, 'lunas bulan ini')
            || (str_contains($lower, 'lunas') && (str_contains($lower, 'berapa') || str_contains($lower, 'total'))))
            && $this->canAccessFinance($user);

        if ($isAskingRevenue) {
            $nowJkt = Carbon::now('Asia/Jakarta');
            $currentMonth = $nowJkt->format('m');
            $currentYear = $nowJkt->format('Y');

            $monthPaidAmount = (float) DB::table('trx_billing_layanan')
                ->where('status_bill_lay', '15')
                ->where('tahun_tagihan', $currentYear)
                ->where('bulan_tagihan', $currentMonth)
                ->sum(DB::raw('CAST(COALESCE(amount_paid, total_layanan, 0) AS DECIMAL(15,2))'));

            $monthPaidCount = DB::table('trx_billing_layanan')
                ->where('status_bill_lay', '15')
                ->where('tahun_tagihan', $currentYear)
                ->where('bulan_tagihan', $currentMonth)
                ->count();

            $todayPaidCount = DB::table('trx_billing_layanan')
                ->where('status_bill_lay', '15')
                ->whereDate(DB::raw('COALESCE(payment_paid, date_update)'), Carbon::today('Asia/Jakarta'))
                ->count();

            $output[] = "### 💰 Rekap Omset & Tagihan Lunas IMS2 ({$nowJkt->format('F Y')}):";
            $output[] = "- 💵 **Total Lunas Masuk Bulan Ini:** `Rp " . number_format($monthPaidAmount, 0, ',', '.') . "`";
            $output[] = "- 🧾 **Jumlah Tagihan Terbayar Bulan Ini:** **" . number_format($monthPaidCount, 0, ',', '.') . " Transaksi**";
            $output[] = "- 📅 **Pembayaran Lunas Hari Ini:** **{$todayPaidCount} Transaksi**\n";
            $output[] = "> ℹ️ *Data dihitung realtime dari seluruh transaksi pelunasan transfer, payment gateway, dan kasir/kolektor.*";
            return implode("\n", $output);
        }

        // 3. Tagihan Tertunggak / Piutang (Unpaid Total) Realtime
        $isAskingUnpaid = (str_contains($lower, 'piutang') || str_contains($lower, 'tunggak') 
            || str_contains($lower, 'belum bayar') || str_contains($lower, 'unpaid')
            || str_contains($lower, 'selain lunas') || str_contains($lower, 'kurang bayar'))
            && $this->canAccessFinance($user);

        if ($isAskingUnpaid && empty($context['customers'])) {
            $totalUnpaidCount = DB::table('trx_billing_layanan')
                ->whereIn('status_bill_lay', ['12', '13', '14'])
                ->where('hide', 0)
                ->count();

            $totalUnpaidAmount = (float) DB::table('trx_billing_layanan')
                ->whereIn('status_bill_lay', ['12', '13', '14'])
                ->where('hide', 0)
                ->sum(DB::raw('CAST(COALESCE(total_layanan, 0) AS DECIMAL(15,2))'));

            $tableCustomer = Schema::hasTable('view_batchjob') ? 'view_batchjob' : 'trx_batchjob_register';
            $sampleUnpaid = DB::table('trx_billing_layanan')
                ->leftJoin($tableCustomer, 'trx_billing_layanan.nomor_internet', '=', "{$tableCustomer}.nomor_internet")
                ->whereIn('trx_billing_layanan.status_bill_lay', ['12', '13'])
                ->where('trx_billing_layanan.hide', 0)
                ->select([
                    'trx_billing_layanan.kode_billing_layanan',
                    'trx_billing_layanan.nomor_internet',
                    'trx_billing_layanan.bulan_tagihan',
                    'trx_billing_layanan.tahun_tagihan',
                    'trx_billing_layanan.total_layanan',
                    "{$tableCustomer}.nama_pelanggan",
                ])
                ->orderByDesc('trx_billing_layanan.tahun_tagihan')
                ->orderByDesc('trx_billing_layanan.bulan_tagihan')
                ->limit(6)
                ->get();

            $output[] = "### 💳 Rekapitulasi Piutang & Tagihan Tertunggak:";
            $output[] = "- 📊 **Total Tagihan Belum Terbayar:** **" . number_format($totalUnpaidCount, 0, ',', '.') . " Invoice**";
            $output[] = "- 💰 **Total Nominal Piutang Tertunggak:** `Rp " . number_format($totalUnpaidAmount, 0, ',', '.') . "`\n";

            if ($sampleUnpaid->isNotEmpty()) {
                $output[] = "#### 📋 Contoh Tagihan Belum Lunas Terbaru:";
                $output[] = "| No. Invoice | No. Internet | Nama Pelanggan | Periode | Nominal |";
                $output[] = "|---|---|---|---|---|";
                foreach ($sampleUnpaid as $su) {
                    $nom = 'Rp ' . number_format((float)($su->total_layanan ?? 0), 0, ',', '.');
                    $cust = $su->nama_pelanggan ?: 'Pelanggan';
                    $output[] = "| `{$su->kode_billing_layanan}` | `{$su->nomor_internet}` | **{$cust}** | {$su->bulan_tagihan}/{$su->tahun_tagihan} | `{$nom}` |";
                }
            }
            return implode("\n", $output);
        }

        // 4. Data Pelanggan Baru Bulan Ini
        $isAskingNewCustomers = str_contains($lower, 'pelanggan baru') || str_contains($lower, 'user baru') 
            || str_contains($lower, 'pertumbuhan user') || str_contains($lower, 'daftar user baru');

        if ($isAskingNewCustomers) {
            $nowJkt = Carbon::now('Asia/Jakarta');
            $newCustQuery = DB::table('trx_batchjob_register')
                ->whereYear('date_create', $nowJkt->year)
                ->whereMonth('date_create', $nowJkt->month);

            $newCustCount = $newCustQuery->count();
            $recentNew = $newCustQuery->select(['nomor_internet', 'nama_pelanggan', 'kode_bandwith', 'status_reg', 'date_create'])
                ->orderByDesc('date_create')
                ->limit(6)
                ->get();

            $output[] = "### 🆕 Pertumbuhan Pelanggan Baru ({$nowJkt->format('F Y')}):";
            $output[] = "- 📈 **Total Pelanggan Baru Terdaftar:** **{$newCustCount} Pelanggan**\n";

            if ($recentNew->isNotEmpty()) {
                $output[] = "#### 📋 Daftar Pelanggan Baru Terbaru:";
                $output[] = "| No. Internet | Nama Pelanggan | Paket | Status | Tgl Daftar |";
                $output[] = "|---|---|---|---|---|";
                foreach ($recentNew as $rn) {
                    $st = match((string)$rn->status_reg) {
                        '20' => '🟢 Aktif',
                        '16' => '🟡 Instalasi',
                        '18', '19' => '🔵 Aktivasi NOC',
                        '12' => '⚪ Survey',
                        default => 'Registrasi (' . $rn->status_reg . ')'
                    };
                    $tgl = Carbon::parse($rn->date_create)->format('d M Y');
                    $output[] = "| `{$rn->nomor_internet}` | **{$rn->nama_pelanggan}** | `{$rn->kode_bandwith}` | {$st} | {$tgl} |";
                }
            }
            return implode("\n", $output);
        }

        // 5. Status OLT & Master Jaringan Realtime
        $isAskingOltList = str_contains($lower, 'status olt') || str_contains($lower, 'daftar olt') 
            || str_contains($lower, 'ada olt apa') || str_contains($lower, 'master olt') 
            || str_contains($lower, 'olt aktif');

        if ($isAskingOltList && $this->canAccessNetwork($user)) {
            $olts = DB::table('m_olt')->orderByDesc('is_active')->get();
            $output[] = "### 🌐 Master Perangkat OLT Jaringan IMS2:";
            $output[] = "| Nama OLT | Brand / Model | IP Address | Status |";
            $output[] = "|---|---|---|---|";
            foreach ($olts as $o) {
                $st = ($o->is_active == 1) ? '🟢 Aktif' : '🔴 Non-Aktif';
                $brand = trim(($o->brand ?? '') . ' ' . ($o->model ?? '')) ?: 'ZTE C300/C320';
                $output[] = "| **{$o->name_olt}** | {$brand} | `{$o->ip_address}` | {$st} |";
            }
            $output[] = "\n> 📡 *Konektivitas live OLT dipantau via SNMP/Telnet pada port manajemen NOC.*";
            return implode("\n", $output);
        }

        // 6. Tiket Gangguan Realtime
        $isAskingTickets = str_contains($lower, 'tiket') || str_contains($lower, 'gangguan') 
            || str_contains($lower, 'komplain') || str_contains($lower, 'keluhan');

        if ($isAskingTickets) {
            $openTickets = DB::table('trx_tiket_gangguan')
                ->where('hide', 0)
                ->whereNotIn('status', ['selesai', 'closed', 'batal'])
                ->limit(8)
                ->get();

            $output[] = "### 🎫 Status Tiket Gangguan & Helpdesk:";
            if ($openTickets->isEmpty()) {
                $output[] = "🎉 **Tidak ada tiket gangguan yang berstatus OPEN / aktif saat ini.**";
                $output[] = "Jaringan internet pelanggan terpantau stabil tanpa laporan kendala.";
            } else {
                $output[] = "⚠️ Terdapat **" . count($openTickets) . " tiket gangguan aktif** yang sedang ditangani:\n";
                $output[] = "| No. Tiket | No. Internet | Keluhan | Status | Penanganan |";
                $output[] = "|---|---|---|---|---|";
                foreach ($openTickets as $ot) {
                    $st = strtoupper($ot->status ?: 'OPEN');
                    $pen = $ot->penanganan ?: 'Menunggu Teknisi';
                    $output[] = "| `{$ot->tiket}` | `{$ot->nomor_internet}` | {$ot->keluhan} | **{$st}** | {$pen} |";
                }
            }
            return implode("\n", $output);
        }

        // 7. Data Pelanggan Spesifik (Profil & Tagihan Terpadu)
        if (!empty($context['customers'])) {
            $isAskingStatus = preg_match('/\b(status|aktif|online|keaktifan|isolir|terisolir|diisolir|suspend|disuspend|tersuspend|blokir|diblokir|terblokir|kondisi|keadaan)\b/i', $lower)
                && !preg_match('/\b(semua|seluruh|lengkap|detail|profil|data pelanggan|tagihan|invoice|riwayat|berapa|total|jumlah|daftar|list)\b/i', $lower);

            $isAskingOnlyRouter = preg_match('/\b(router|mikrotik)\b/i', $lower)
                && !preg_match('/\b(semua|seluruh|lengkap|detail|profil|data pelanggan|tagihan|daftar|list)\b/i', $lower);

            $isAskingOnlyAddress = preg_match('/\b(alamat|lokasi|tempat|rumah|dimana)\b/i', $lower)
                && !preg_match('/\b(semua|seluruh|lengkap|detail|profil|data pelanggan)\b/i', $lower);

            // A. Jawaban langsung jika menanyakan status / isolir / suspend / aktif
            if ($isAskingStatus) {
                $lines = [];
                foreach ($context['customers'] as $c) {
                    $isCustSuspended = in_array((string)$c->status_reg, ['21', '21.1'], true) || ($c->is_suspend == 1 && (string)$c->status_reg !== '23');
                    if ($isCustSuspended) {
                        $lines[] = "🔴 Pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`) saat ini berstatus **Terisolir (Suspend)**.";
                    } else {
                        $statusDesc = match((string)$c->status_reg) {
                            '20' => '🟢 Online Aktif (Layanan normal / Tidak Terisolir)',
                            '23', '23.1' => '⚫ Terminasi / Dismantle (Putus Berlangganan)',
                            '18', '19' => '🔵 Tahap Aktivasi NOC',
                            '16' => '🟡 Tahap Instalasi Lapangan',
                            '12' => '⚪ Tahap Survey Lokasi',
                            default => 'Registrasi (' . $c->status_reg . ')'
                        };
                        $lines[] = "Pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`) saat ini berstatus **{$statusDesc}**.";
                    }
                }
                return implode("\n", $lines);
            }

            // C. Jawaban langsung jika HANYA menanyakan router
            if ($isAskingOnlyRouter) {
                $lines = [];
                foreach ($context['customers'] as $c) {
                    $routerLabel = '-';
                    if (!empty($c->router_id) && Schema::hasTable('routers')) {
                        $rObj = DB::table('routers')->where('id', $c->router_id)->first();
                        if ($rObj) {
                            $routerLabel = "{$rObj->name} (" . ($rObj->host ?? '-') . ")";
                        }
                    }
                    $pppoe = $c->pppoe_username ?: '-';
                    $lines[] = "Pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`) terdaftar di **{$routerLabel}** dengan PPPoE `{$pppoe}`.";
                }
                return implode("\n", $lines);
            }

            // D. Jawaban langsung jika HANYA menanyakan alamat
            if ($isAskingOnlyAddress) {
                $lines = [];
                foreach ($context['customers'] as $c) {
                    $lines[] = "Alamat pemasangan pelanggan **{$c->nama_pelanggan}** (`{$c->nomor_internet}`): *{$c->alamat_pasang}*.";
                }
                return implode("\n", $lines);
            }

            $output[] = "### 👤 Data Pelanggan Ditemukan (" . count($context['customers']) . "):";
            foreach ($context['customers'] as $c) {
                $isCustSuspended = in_array((string)$c->status_reg, ['21', '21.1'], true) || ($c->is_suspend == 1 && (string)$c->status_reg !== '23');
                $unifiedStatus = $isCustSuspended 
                    ? '🔴 Terisolir (Suspend)' 
                    : match((string)$c->status_reg) {
                        '20' => '🟢 Online Aktif',
                        '18', '19' => '🔵 Aktivasi NOC',
                        '16' => '🟡 Tahap Instalasi',
                        '12' => '⚪ Tahap Survey',
                        '23', '23.1' => '⚫ Terminasi (Putus)',
                        '14', '15' => '🔴 Dibatalkan',
                        default => 'Registrasi (' . $c->status_reg . ')'
                    };

                $oltLabel = !empty($c->olt_name) ? "{$c->olt_name} (" . ($c->olt_ip ?? '-') . ")" : ($c->olt ?: '-');
                $indexLabel = !empty($c->index_olt) ? "`{$c->index_olt}`" : "⚠️ *Belum diatur (NULL)*";

                $routerLabel = '-';
                if (!empty($c->router_id) && Schema::hasTable('routers')) {
                    $rObj = DB::table('routers')->where('id', $c->router_id)->first();
                    if ($rObj) {
                        $routerLabel = "{$rObj->name} (" . ($rObj->host ?? '-') . ")";
                    }
                }

                $output[] = "- **{$c->nama_pelanggan}** (`{$c->nomor_internet}`)";
                $output[] = "  - Paket: `{$c->kode_bandwith}` | Status Layanan: **{$unifiedStatus}**";
                $output[] = "  - PPPoE: `" . ($c->pppoe_username ?: '-') . "`";
                $output[] = "  - Router MikroTik: **{$routerLabel}**";
                $output[] = "  - OLT: **{$oltLabel}** | Index Port: {$indexLabel}";
                $output[] = "  - Alamat: *{$c->alamat_pasang}*";

                // Lampirkan tagihan pelanggan ini secara terpadu jika ada
                if (!empty($context['billings'])) {
                    $output[] = "\n  **💳 Riwayat Tagihan Terbaru:**";
                    foreach (array_slice($context['billings'], 0, 3) as $cb) {
                        $stb = match((string)$cb->status_bill_lay) {
                            '15' => '🟢 Lunas',
                            '14' => '🟡 Menunggu Verifikasi',
                            default => '🔴 Belum Bayar'
                        };
                        $nom = 'Rp ' . number_format((float)($cb->total_layanan ?? 0), 0, ',', '.');
                        $output[] = "  - Invoice `{$cb->kode_billing_layanan}` ({$cb->bulan_tagihan}/{$cb->tahun_tagihan}): {$nom} | {$stb}";
                    }
                } elseif (preg_match('/\b(tagih|invoice|bill|bayar|tunggak)\b/i', $lower)) {
                    $output[] = "\n  **💳 Riwayat Tagihan:**";
                    $output[] = "  ℹ️ *Pelanggan ini belum memiliki catatan riwayat invoice di sistem billing.*";
                }
            }
            return implode("\n", $output);
        }

        // 8. Deteksi Pencarian Pelanggan tapi TIDAK Ditemukan
        $hasCustomerIntent = (!empty($context['has_customer_intent']) || !empty($context['extracted_id']))
            || (
                preg_match('/\b(pelanggan|cust|customer|member|atas nama|no internet|nomor internet|id pelanggan|pppoe)\b/i', $lower)
                || (preg_match('/\b(cari|cek|siapa yang punya|pemilik|punya siapa)\b/i', $lower) && preg_match('/\b(nama|pelanggan|user|cust|member|nomor|no)\b/i', $lower))
            );

        if ($hasCustomerIntent && empty($context['customers'])) {
            $targetQuery = !empty($context['extracted_id']) 
                ? $context['extracted_id'] 
                : (!empty($context['clean_search']) ? $context['clean_search'] : $query);

            return "### 🔍 Data Pelanggan Tidak Ditemukan\n\n"
                . "Maaf **{$userName}**, data pelanggan dengan nomor internet atau nama **\"{$targetQuery}\"** tidak ditemukan di database IMS2.\n\n"
                . "💡 **Saran Pengecekan:**\n"
                . "- **Nomor Internet:** Pastikan nomor internet resmi terdiri dari 6-12 digit angka (contoh: `260831056`).\n"
                . "- **Nama Pelanggan:** Jika mencari dengan nama, coba gunakan 1 kata saja (misal: nama depan atau nama belakang).\n"
                . "- **Pendaftaran:** Pastikan data registrasi pelanggan sudah terdaftar di menu **Data Pelanggan**.";
        }

        // 9. Deteksi Pencarian Tagihan Spesifik tapi TIDAK Ditemukan
        $isAskingSpecificBill = (str_contains($lower, 'billing') || str_contains($lower, 'tagihan') || str_contains($lower, 'invoice'))
            && (!empty($context['extracted_id']) || preg_match('/\b(inv|202\d)\b/i', $lower));

        if ($isAskingSpecificBill && empty($context['billings'])) {
            $billTarget = !empty($context['extracted_id']) ? $context['extracted_id'] : $query;
            return "### 🧾 Tagihan Tidak Ditemukan\n\n"
                . "Data tagihan atau invoice dengan kata kunci **\"{$billTarget}\"** tidak ditemukan di sistem.\n\n"
                . "💡 Pelanggan mungkin belum memiliki tagihan aktif pada periode ini, atau seluruh kewajiban tagihannya sudah berstatus lunas.";
        }

        // 10. Deteksi Pencarian Tiket Spesifik tapi TIDAK Ditemukan
        $isAskingSpecificTicket = (str_contains($lower, 'tiket') || str_contains($lower, 'gangguan') || str_contains($lower, 'komplain'))
            && !empty($context['extracted_id']);

        if ($isAskingSpecificTicket && empty($context['tickets'])) {
            return "### 🎫 Tiket Gangguan Tidak Ditemukan\n\n"
                . "Tidak ditemukan laporan tiket gangguan yang tercatat untuk nomor **\"{$context['extracted_id']}\"**.\n\n"
                . "💡 Layanan internet pelanggan terkait terpantau normal atau tiket komplain sebelumnya telah diselesaikan (*Closed*).";
        }

        // 11. Rekapitulasi Operasional Sistem (Hari Ini)
        $isAskingSummary = str_contains($lower, 'rekap') || str_contains($lower, 'summary') 
            || str_contains($lower, 'ringkasan') || str_contains($lower, 'hari ini') 
            || str_contains($lower, 'status operasional');

        if ($isAskingSummary) {
            $totalCust = DB::table('trx_batchjob_register')->count();
            $activeCust = DB::table('trx_batchjob_register')->where('status_reg', '20')->count();
            $suspendCust = DB::table('trx_batchjob_register')->where('is_suspend', 1)->count();
            $todayPaid = DB::table('trx_billing_layanan')
                ->where('status_bill_lay', '15')
                ->whereDate(DB::raw('COALESCE(payment_paid, date_update)'), Carbon::today('Asia/Jakarta'))
                ->count();
            $openTicketsCount = DB::table('trx_tiket_gangguan')
                ->where('hide', 0)
                ->whereNotIn('status', ['selesai', 'closed', 'batal'])
                ->count();

            $output[] = "### 📊 Ringkasan Operasional IMS2 Hari Ini (" . Carbon::now('Asia/Jakarta')->format('d M Y') . "):";
            $output[] = "- 👥 **Total Pelanggan Terdaftar:** **" . number_format($totalCust, 0, ',', '.') . " User**";
            $output[] = "- 🟢 **Pelanggan Online Aktif (#20):** **" . number_format($activeCust, 0, ',', '.') . " User**";
            $output[] = "- 🔴 **Pelanggan Terisolir (Suspend):** **{$suspendCust} User**";
            $output[] = "- 💰 **Pembayaran Lunas Hari Ini:** **{$todayPaid} Transaksi**";
            $output[] = "- 🎫 **Tiket Gangguan Open:** **{$openTicketsCount} Tiket**\n";
            $output[] = "Sistem dan database internal IMS2 beroperasi normal.";
            return implode("\n", $output);
        }

        // =========================================================================
        // PERTANYAAN PERCAKAPAN UMUM (CONVERSATIONAL INTELLIGENCE)
        // =========================================================================

        if (preg_match('/\b(cowo|cewe|cowok|cewek|pria|wanita|gender|kelamin)\b/i', $lower)) {
            return "Saya adalah asisten virtual berbasis kecerdasan buatan (AI) sistem IMS2, jadi saya **tidak memiliki jenis kelamin (bukan cowok maupun cewek)**. 🤖✨\n\nSaya bertugas mendampingi operasional seluruh tim IMS2 (NOC, Finance, Teknik, dan Manajemen). Ada yang bisa saya bantu hari ini, **{$userName}**?";
        }

        if (preg_match('/\b(kamu siapa|siapa kamu|siapa anda|nama kamu|siapa namamu)\b/i', $lower)) {
            return "Halo **{$userName}**! 👋 Saya adalah **IMS AI Copilot**, asisten pintar resmi PT Media Solusi Network (IMS2).\n\nSaya dapat membantu Anda mencari data pelanggan, memeriksa tagihan pending, menghitung omset & piutang, memantau OLT & redaman optik, hingga memantau tiket komplain jaringan.";
        }

        if (preg_match('/\b(pembuatmu|penciptamu|yang buat kamu|yang bikin kamu|dibuat oleh)\b/i', $lower)) {
            return "Saya dikembangkan oleh **Tim Developer PT Media Solusi Network** khusus untuk mempercepat otomasi dan pendampingan kerja seluruh divisi di sistem IMS2. 🚀";
        }

        if (preg_match('/\b(apa kabar|gimana kabarnya|sehat)\b/i', $lower)) {
            return "Kabar saya sangat baik dan seluruh data operasional IMS2 siap diakses! 🟢 Bagaimana dengan Anda hari ini, **{$userName}**?";
        }

        if (preg_match('/^(terima kasih|makasih|thanks|thank you|tengkyu|nuhun)\b/i', $lower)) {
            return "Sama-sama, **{$userName}**! Senang bisa membantu Anda. Jika ada data operasional lain yang ingin ditanyakan, tanyakan saja ya! 😊";
        }

        // =========================================================================
        // SAPAAN MURNI (GREETING & QUICK MENU)
        // =========================================================================

        $isPureGreeting = preg_match('/^(halo|hai|hi|hei|pagi|siang|sore|malam|assalamualaikum|ping|p|tes|test|help|bantuan|menu)\b/i', $lower);
        if ($isPureGreeting) {
            $output[] = "Halo **{$userName}**! 👋 Saya adalah **IMS AI Copilot**.";
            $output[] = "Akun Anda aktif dengan role: **{$userRole}**.\n";
            $output[] = "Berikut beberapa panduan operasional cerdas yang bisa langsung saya jawab dari database:";

            if (in_array($userRole, ['Direktur', 'CEO', 'Admin'], true)) {
                $output[] = "1. **Keuangan & Omset:** *'Berapa omset bulan ini?'* atau *'Cek tagihan pending verifikasi'*";
                $output[] = "2. **Piutang & Tunggakan:** *'Berapa total tagihan belum bayar?'*";
                $output[] = "3. **Cari Pelanggan:** *'Cari pelanggan Gunawan'* atau *'Cek nomor internet 11011023'*";
                $output[] = "4. **Jaringan & OLT:** *'Status OLT'* atau *'Jelaskan standar redaman optik'*";
                $output[] = "5. **SOP & Aplikasi:** *'Apa perbedaan Portal Pelanggan dan Bukan Portal?'* atau *'Jelaskan siklus kode status registrasi'*";
            } elseif ($userRole === 'Finance') {
                $output[] = "1. **Verifikasi:** *'Cek tagihan pending verifikasi'*";
                $output[] = "2. **Laporan Omset:** *'Berapa total lunas bulan ini?'*";
                $output[] = "3. **Piutang:** *'Berapa total tagihan tertunggak?'*";
                $output[] = "4. **Cek Tagihan Pelanggan:** *'Cek billing 11011023'*";
                $output[] = "5. **Alur:** *'Kapan jadwal suspend otomatis?'* atau *'Apa arti status bill 14?'*";
            } elseif ($userRole === 'NOC' || $userRole === 'Teknik') {
                $output[] = "1. **Status OLT:** *'Status master OLT'*";
                $output[] = "2. **Redaman & Telemetri:** *'Kenapa redaman pelanggan tidak terbaca?'* atau *'Berapa standar redaman GPON?'*";
                $output[] = "3. **Cari Pelanggan:** *'Cek pelanggan 11011023'*";
                $output[] = "4. **Tiket Gangguan:** *'Cek tiket open'*";
                $output[] = "5. **Siklus:** *'Jelaskan kode status registrasi #10 sampai #20'*";
            } else {
                $output[] = "1. **Cari Pelanggan:** Ketik nama atau nomor internet pelanggan.";
                $output[] = "2. **Status Tiket:** *'Cek tiket gangguan'*";
                $output[] = "3. **Informasi:** *'Jelaskan alur pasang baru'*";
            }

            return implode("\n", $output);
        }

        // =========================================================================
        // JAWABAN PASTI JIKA PERTANYAAN DI LUAR DOMAIN / TIDAK DIMENGERTI
        // =========================================================================

        return "### ❓ Pertanyaan Tidak Dipahami\n\n"
            . "Maaf **{$userName}**, asisten lokal belum memahami maksud atau pertanyaan Anda: *\"{$query}\"*.\n\n"
            . "Sebagai asisten operasional sistem IMS2, saya dapat membantu Anda menjawab:\n"
            . "- 👤 **Cari Pelanggan:** Cari berdasarkan nama, nomor internet, atau PPPoE (*'Cari pelanggan Gunawan'* atau *'Cek nomor 260831056'*).\n"
            . "- 💳 **Keuangan & Omset:** Cek omset bulan ini, tagihan pending verifikasi, atau total piutang (*khusus Finance/Admin*).\n"
            . "- 🌐 **Infrastruktur OLT:** Cek master OLT aktif dan panduan teknis redaman optik / index GPON (*khusus NOC/Admin*).\n"
            . "- 🎫 **Tiket Komplain:** Pantau tiket gangguan yang sedang berstatus open.\n"
            . "- 📋 **SOP & Prosedur:** SOP Portal Pelanggan, jadwal auto-suspend, prosedur terminasi, atau kode status registrasi (#10 s/d #20).\n\n"
            . "Silakan ajukan pertanyaan yang berkaitan dengan data atau operasional di atas.";
    }

    /**
     * Resolves queries regarding IMS2 application features and system modules.
     */
    protected function handleFeaturesQuestion(string $lower): ?string
    {
        if (preg_match('/\b(fitur|fitur-fitur|apa saja fitur|ada fitur apa|menu apa saja|fungsi aplikasi|tentang aplikasi|kemampuan aplikasi|fitur aplikasi)\b/i', $lower)) {
            return "### 🚀 Fitur Utama & Modul Operasional Sistem IMS2:\n\n"
                . "Sistem **IMS2 (ISP Management System)** adalah platform operasional terintegrasi untuk penyedia layanan internet (ISP) PT Media Solusi Network:\n\n"
                . "1. **👥 Manajemen Pelanggan (Customer Lifecycle):**\n"
                . "   - Registrasi & Pendaftaran baru (tahapan survey `#12`, instalasi `#16`, aktivasi `#18`/`#19`, hingga online aktif `#20`).\n"
                . "   - Isolir / Suspend otomatis bagi penunggak tagihan serta buka isolir (*unsuspend*).\n"
                . "   - Permintaan terminasi / pelepasan perangkat (*dismantle* `#23`).\n\n"
                . "2. **💳 Billing, Penagihan & Keuangan:**\n"
                . "   - Penerbitan massal invoice bulanan otomatis setiap tanggal 1 via cron scheduler.\n"
                . "   - Verifikasi bukti bayar transfer bank dan integrasi payment gateway Midtrans (QRIS, VA, E-Wallet).\n"
                . "   - Rekap omset harian/bulanan, pelacakan piutang tertunggak, dan broadcast tagihan via WhatsApp.\n\n"
                . "3. **🌐 NOC, OLT & Jaringan Fiber Optic:**\n"
                . "   - Monitoring master OLT (OLT Kayu Agung, OLT Babakan Tarogong, OLT Soreang).\n"
                . "   - Diagnosa telemetri redaman optik live, status link ONU (LOS / DOWN), dan index port GPON.\n"
                . "   - Integrasi router MikroTik multi-gateway (enable/disable user PPPoE, kick session, dan remote reboot ONU).\n\n"
                . "4. **🎫 Helpdesk & Tiket Gangguan:**\n"
                . "   - Pencatatan keluhan pelanggan, eskalasi penugasan teknisi lapangan, dan tracking tiket open/closed.\n\n"
                . "5. **🤖 IMS AI Copilot:**\n"
                . "   - Asisten cerdas operasional untuk audit data realtime, konsultasi SOP, dan failover lokal offline.";
        }
        return null;
    }

    /**
     * Explains the technical difference between MikroTik Kick Session and OLT Remote Reboot ONU.
     */
    protected function handleMikrotikOltDifference(string $lower): ?string
    {
        if (preg_match('/\b(perbedaan|beda|bedanya)\b/i', $lower) 
            && preg_match('/\b(kick|reboot)\b/i', $lower) 
            && (preg_match('/\b(mikrotik|olt|onu|router)\b/i', $lower))) {
            return "### ⚙️ Perbedaan Kick Koneksi MikroTik vs Reboot ONU di OLT:\n\n"
                . "| Parameter | 🔌 Kick Koneksi MikroTik (`/ppp/active/remove`) | 🔄 Reboot ONU di OLT (`pon-onu-mng reboot`) |\n"
                . "|:---|:---|:---|\n"
                . "| **Lapisan (Layer)** | **Sesi Jaringan (Software / PPP Session)** | **Perangkat Keras (Physical Hardware Modem)** |\n"
                . "| **Perangkat Target** | Router MikroTik Gateway | Modem ONT/ONU di rumah pelanggan |\n"
                . "| **Cara Kerja** | Memutus sesi login PPPoE aktif agar modem dipaksa melakukan *re-authentication* ulang. | Mengirim sinyal remote restart daya/chipset modem ONT melalui protokol OMCI/OAM OLT. |\n"
                . "| **Kondisi Modem** | Modem **TIDAK mati/restart**, lampu modem tetap menyala normal. | Modem **mati sejenak, lampu indikator padam**, lalu booting ulang dari awal. |\n"
                . "| **Durasi Pemulihan** | Sangat cepat (**1 - 3 detik**) langsung tersambung kembali. | Membutuhkan waktu **1 - 2 menit** (booting modem & sinkronisasi laser GPON). |\n"
                . "| **Tujuan / Kapan Dipakai** | - Saat proses unsuspend / buka isolir.<br>- Saat pergantian profil paket bandwidth.<br>- Saat ingin mereload alokasi IP dinamis. | - Saat modem pelanggan hang / memori penuh.<br>- Saat redaman optik tidak stabil / unregister.<br>- Saat perubahan konfigurasi port fisik GPON. |";
        }
        return null;
    }

    /**
     * Resolves queries about ONU LOS / Offline status count and indications.
     */
    protected function handleOnuStatusQuestion(string $lower): ?string
    {
        if (preg_match('/\b(berapa|jumlah|total|cek)\b/i', $lower) 
            && preg_match('/\b(onu|ont|modem)\b/i', $lower) 
            && preg_match('/\b(los|offline|down|padam|mati)\b/i', $lower)) {
            return "### 📡 Informasi Status Live Modem ONU (LOS / Offline):\n\n"
                . "Status fisik seluruh modem ONT/ONU pelanggan dipantau secara realtime melalui sensor telemetri OLT (SNMP/Telnet) pada menu **NOC > Telemetri Jaringan / OLT**.\n\n"
                . "1. **🔴 Status LOS (Loss of Signal):**\n"
                . "   - Terjadi jika kabel optik dropcore putus, konektor terlepas, atau power optik hilang (redaman > `-28 dBm` s/d `-40 dBm`).\n"
                . "   - Memerlukan penugasan teknisi lapangan untuk penyambungan (*splicing*) ulang.\n\n"
                . "2. **🟡 Status Dying Gasp / DOWN:**\n"
                . "   - Terjadi saat modem pelanggan padam listrik (mati lampu) di rumah pelanggan, namun kabel optik masih utuh.\n\n"
                . "3. **🟢 Status Online (UP):**\n"
                . "   - Seluruh pelanggan dengan status `#20` aktif yang modemnya terhubung ke OLT Kayu Agung, Babakan Tarogong, atau Soreang dapat dicek status individualnya dengan mengetik: *'Cek nomor [No Internet]'*.";
        }
        return null;
    }

    /**
     * Resolves which OLT an ONU port index is mounted on.
     */
    protected function handlePortOltMappingQuestion(string $userMessage, string $lower): ?string
    {
        if (preg_match('/(gpon-onu_\d+\/\d+\/\d+:\d+|\d+\/\d+\/\d+:\d+)/i', $userMessage, $onuMatch)) {
            $targetPort = $onuMatch[1];
            $custOnu = DB::table('trx_batchjob_register')
                ->where('index_olt', 'like', "%{$targetPort}%")
                ->select(['nomor_internet', 'nama_pelanggan', 'ont_us', 'olt', 'index_olt', 'router_id'])
                ->first();

            if ($custOnu) {
                $oltName = $custOnu->olt ?: 'OLT Jaringan IMS2';
                $routerName = '-';
                if (!empty($custOnu->router_id) && Schema::hasTable('routers')) {
                    $rObj = DB::table('routers')->where('id', $custOnu->router_id)->first();
                    if ($rObj) $routerName = $rObj->name;
                }
                return "### 📍 Hasil Pengecekan Port OLT:\n\n"
                    . "Port ONU **`{$targetPort}`** terpasang pada **{$oltName}**:\n"
                    . "- 👤 **Pelanggan:** **{$custOnu->nama_pelanggan}** (`{$custOnu->nomor_internet}`)\n"
                    . "- 🔐 **PPPoE Username:** `{$custOnu->ont_us}`\n"
                    . "- 🌐 **Induk OLT:** **{$oltName}**\n"
                    . "- 🖥️ **Router:** **{$routerName}**\n"
                    . "- 🔌 **Index Port:** `{$custOnu->index_olt}`";
            } else {
                $mappedOlt = 'OLT Jaringan IMS2';
                if (str_contains($targetPort, '1/1/')) {
                    $mappedOlt = 'OLT Kayu Agung / Babakan Tarogong (Slot 1/1)';
                } elseif (str_contains($targetPort, '1/2/')) {
                    $mappedOlt = 'OLT Soreang (Slot 1/2)';
                }
                return "### 📍 Informasi Alokasi Port OLT:\n\n"
                    . "Format port **`{$targetPort}`** teralokasi pada **{$mappedOlt}**.\n\n"
                    . "Port ini belum terikat pada pelanggan aktif tertentu di tabel `trx_batchjob_register` dan siap digunakan untuk registrasi aktivasi modem ONT baru.";
            }
        }
        return null;
    }

    /**
     * Resolves list of active MikroTik routers in IMS2.
     */
    protected function handleRoutersListQuestion(string $lower): ?string
    {
        if (preg_match('/\b(router|mikrotik)\b/i', $lower) && preg_match('/\b(daftar|list|aktif|semua|apa saja|tampilkan)\b/i', $lower)) {
            if (Schema::hasTable('routers')) {
                $routers = DB::table('routers')->get();
                $reply = "### 🖥️ Daftar Router MikroTik Terdaftar di IMS2:\n\n";
                $reply .= "| ID | Nama Router | Host / IP Address | Port API | Wilayah | Status |\n";
                $reply .= "|:---:|:---|:---|:---:|:---|:---:|\n";
                foreach ($routers as $r) {
                    $st = ($r->is_active ?? 1) ? '🟢 Aktif' : '🔴 Nonaktif';
                    $kota = $r->kota ?: '-';
                    $reply .= "| `{$r->id}` | **{$r->name}** | `{$r->host}` | `{$r->port}` | {$kota} | {$st} |\n";
                }
                $reply .= "\n> 📡 *Router-router ini terhubung langsung untuk manajemen session PPPoE, isolir otomatis, dan auto-discovery pelanggan.*";
                return $reply;
            }
        }
        return null;
    }

    /**
     * Resolves total active customers query.
     */
    protected function handleCustomerCountQuestion(string $lower): ?string
    {
        if (preg_match('/\b(total|jumlah|berapa)\b/i', $lower) 
            && preg_match('/\b(pelanggan|user|member)\b/i', $lower) 
            && (preg_match('/\b(aktif|online|suspend|isolir|terdaftar|keseluruhan|semua)\b/i', $lower) || str_contains($lower, 'sekarang') || str_contains($lower, 'saat ini'))
            && !preg_match('/\b(tunggak|piutang|belum bayar|belum terbayar)\b/i', $lower)) {
            
            $tableCustomer = Schema::hasTable('view_batchjob') ? 'view_batchjob' : 'trx_batchjob_register';
            $totalActive = DB::table($tableCustomer)->where('status_reg', '20')->count();
            $totalSuspend = DB::table($tableCustomer)->whereIn('status_reg', ['21', '21.1'])->count();
            $totalTermin = DB::table($tableCustomer)->whereIn('status_reg', ['23', '23.1'])->count();
            $totalAllReg = DB::table('trx_batchjob_register')->count();

            return "### 👥 Rekap Total Pelanggan IMS2 Saat Ini:\n\n"
                . "- 🟢 **Pelanggan Aktif (#20):** **" . number_format($totalActive, 0, ',', '.') . " User**\n"
                . "- 🔴 **Pelanggan Suspend (#21):** **" . number_format($totalSuspend, 0, ',', '.') . " User**\n"
                . "- ⚪ **Pelanggan Terminasi (#23):** **" . number_format($totalTermin, 0, ',', '.') . " User**\n"
                . "- 📊 **Total Keseluruhan Registrasi Terdaftar:** **" . number_format($totalAllReg, 0, ',', '.') . " User**\n\n"
                . "💡 *Data dihitung realtime sinkron dengan menu Data Pelanggan (`view_batchjob`).*";
        }
        return null;
    }

    /**
     * Resolves list of currently suspended customers.
     */
    protected function handleSuspendedCustomersList(string $lower): ?string
    {
        // JANGAN proses jika kueri menargetkan ID atau pelanggan spesifik
        if (preg_match('/\b(?:MS|ms)?\d{5,18}\b/i', $lower)) {
            return null;
        }

        // Cek apakah prompt menyebutkan nama orang spesifik (misal: "apakah pelanggan Dian Haerani masih terisolir?")
        $cleanWords = array_values(array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $lower)), function($w) {
            $stops = ['siapa', 'saja', 'user', 'yang', 'saat', 'ini', 'sedang', 'disuspend', 'suspend', 'terisolir', 'diisolir', 'isolir', 'terblokir', 'diblokir', 'blokir', 'karena', 'telat', 'bayar', 'tampilkan', 'daftar', 'list', 'pelanggan', 'tolong', 'carikan', 'cek', 'apakah', 'masih', 'statusnya', 'bisa', 'dong'];
            return !in_array($w, $stops) && strlen($w) >= 3;
        }));

        if (!empty($cleanWords)) {
            $firstWord = reset($cleanWords);
            if (DB::table('trx_batchjob_register')->where('nama_pelanggan', 'like', "%{$firstWord}%")->exists()) {
                return null; // Biarkan jatuh ke pencarian data pelanggan individu
            }
        }

        $isSuspendKeyword = preg_match('/(suspend|disuspend|tersuspend|isolir|diisolir|terisolir|blokir|diblokir|terblokir)/i', $lower);
        $isListKeyword = preg_match('/\b(pelanggan|user|daftar|siapa|tampilkan|list|ada siapa|data|orang)\b/i', $lower)
            || str_contains($lower, 'sedang') || str_contains($lower, 'saat ini');
        $isSopExclude = str_contains($lower, 'cara') || str_contains($lower, 'prosedur') || str_contains($lower, 'jadwal') 
            || str_contains($lower, 'alur') || str_contains($lower, 'unsuspend') || str_contains($lower, 'buka');

        if ($isSuspendKeyword && $isListKeyword && !$isSopExclude) {
            $tableCustomer = Schema::hasTable('view_batchjob') ? 'view_batchjob' : 'trx_batchjob_register';
            $suspended = DB::table($tableCustomer)
                ->whereIn('status_reg', ['21', '21.1'])
                ->limit(10)
                ->get();

            $totalSusp = DB::table($tableCustomer)
                ->whereIn('status_reg', ['21', '21.1'])
                ->count();

            $reply = "### 🔴 Daftar Pelanggan Sedang Terisolir (Suspend):\n\n"
                . "Terdapat total **{$totalSusp} pelanggan** yang sedang berstatus suspend/terisolir:\n\n";

            if ($suspended->isNotEmpty()) {
                $reply .= "| No. Internet | Nama Pelanggan | Paket | PPPoE | OLT |\n";
                $reply .= "|:---|:---|:---|:---|:---|\n";
                foreach ($suspended as $s) {
                    $pppoe = $s->pppoe_username ?? ($s->ont_us ?? '-');
                    $olt = $s->olt ?: '-';
                    $reply .= "| `{$s->nomor_internet}` | **{$s->nama_pelanggan}** | `{$s->kode_bandwith}` | `{$pppoe}` | {$olt} |\n";
                }
                $reply .= "\n> 🔄 *Pelanggan akan otomatis dibuka isolirnya (*unsuspend*) seketika pembayaran tagihan diverifikasi lunas.*";
            } else {
                $reply .= "🎉 **Saat ini tidak ada pelanggan yang berstatus suspend / terisolir.** Seluruh pelanggan berstatus aktif normal.";
            }
            return $reply;
        }
        return null;
    }

    /**
     * Resolves unpaid bills with specific month/year filtering or general overview.
     */
    protected function handleUnpaidBillsQuestion(string $lower, $currentUser): ?string
    {
        if (preg_match('/\b(piutang|tunggak|tunggakan|belum bayar|belum terbayar|unpaid|kurang bayar)\b/i', $lower)
            && preg_match('/\b(tagihan|nominal|invoice|total|berapa|rekap)\b/i', $lower)
            && $this->canAccessFinance($currentUser)) {
            
            $monthMap = [
                'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
                'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
            ];
            $targetMonth = null;
            $targetYear = null;
            foreach ($monthMap as $mName => $mNum) {
                if (str_contains($lower, $mName)) {
                    $targetMonth = $mNum;
                    break;
                }
            }
            if (preg_match('/\b(202\d)\b/', $lower, $yrMatch)) {
                $targetYear = (int)$yrMatch[1];
            }

            $unpaidQuery = DB::table('trx_billing_layanan')
                ->where('hide', 0)
                ->whereNotIn('status_bill_lay', ['15', '2']);

            if ($targetMonth) {
                $unpaidQuery->where(DB::raw('CAST(bulan_tagihan AS UNSIGNED)'), $targetMonth);
            }
            if ($targetYear) {
                $unpaidQuery->where(DB::raw('CAST(tahun_tagihan AS UNSIGNED)'), $targetYear);
            }

            $totalUnpaidAmount = (float)$unpaidQuery->sum(DB::raw('CAST(COALESCE(total_layanan, 0) AS DECIMAL(15,2))'));
            $totalUnpaidCount = $unpaidQuery->count();

            $monthName = $targetMonth ? ucfirst(array_search($targetMonth, $monthMap)) : null;
            $periodLabel = ($targetMonth && $targetYear) ? "Bulan {$monthName} {$targetYear}" : ($targetYear ? "Tahun {$targetYear}" : "Seluruh Periode");

            return "### 💳 Rekap Tagihan Belum Terbayar ({$periodLabel}):\n\n"
                . "- 💰 **Total Nominal Belum Terbayar:** `Rp " . number_format($totalUnpaidAmount, 0, ',', '.') . "`\n"
                . "- 🧾 **Jumlah Invoice Tertunggak:** **" . number_format($totalUnpaidCount, 0, ',', '.') . " Tagihan**\n\n"
                . "> ⚠️ *Pelanggan yang belum melunasi tagihannya sebelum tanggal 25 akan otomatis masuk antrean suspend.*";
        }
        return null;
    }

    /**
     * Resolves helpdesk tickets and complaints questions.
     */
    protected function handleTicketsDirectQuestion(string $lower): ?string
    {
        if (preg_match('/\b(tiket|gangguan|komplain|keluhan)\b/i', $lower)
            && (preg_match('/\b(hari ini|open|buka|sedang ditangani|berapa|ada berapa|status)\b/i', $lower))
            && !preg_match('/\b(cara|alur|sop|prosedur)\b/i', $lower)) {
            
            $isToday = str_contains($lower, 'hari ini');
            $ticketQuery = DB::table('trx_tiket_gangguan')->where('hide', 0);
            
            if ($isToday) {
                $todayDate = Carbon::today('Asia/Jakarta')->toDateString();
                $ticketQuery->where(function($q) use ($todayDate) {
                    $q->whereDate('date_create', $todayDate)
                      ->orWhereDate('date_update', $todayDate);
                });
            } else {
                $ticketQuery->where(function($q) {
                    $q->whereIn('status', ['11', '12', 'open', 'baru', 'proses', 'diproses'])
                      ->orWhere(function($sub) {
                          $sub->whereNotIn('status', ['13', '14', 'selesai', 'closed', 'close', 'done', 'batal', 'cancel', 'dibatalkan'])
                              ->where(function($p) {
                                  $p->whereNull('penanganan')
                                    ->orWhere('penanganan', 'not like', '%batal%');
                              });
                      });
                });
            }

            $ticketCount = $ticketQuery->count();
            $sampleTickets = $ticketQuery->limit(6)->get();

            $title = $isToday ? "Komplain / Tiket Gangguan Hari Ini (" . Carbon::now('Asia/Jakarta')->format('d M Y') . ")" : "Tiket Gangguan Status OPEN / Sedang Ditangani";

            $reply = "### 🎫 {$title}:\n\n"
                . "- 📊 **Total Tiket:** **{$ticketCount} Tiket**\n\n";

            if ($sampleTickets->isNotEmpty()) {
                $reply .= "| No. Tiket | No. Internet | Keluhan | Status | Penanganan |\n";
                $reply .= "|:---|:---|:---|:---:|:---|\n";
                foreach ($sampleTickets as $t) {
                    $stRaw = strtolower(trim((string)$t->status));
                    $stBadge = match($stRaw) {
                        '11', 'open', 'baru' => '🟡 Baru (OPEN)',
                        '12', 'proses', 'diproses' => '🔵 Sedang Ditangani',
                        '13', 'selesai', 'closed', 'done' => '🟢 Selesai',
                        '14', 'batal', 'cancel', 'dibatalkan' => '⚪ Dibatalkan',
                        default => strtoupper($t->status ?: 'OPEN')
                    };
                    $pen = $t->penanganan ?: 'Menunggu Teknisi';
                    $reply .= "| `{$t->tiket}` | `{$t->nomor_internet}` | {$t->keluhan} | **{$stBadge}** | {$pen} |\n";
                }
            } else {
                $reply .= "🎉 **Tidak ada tiket gangguan yang berstatus OPEN / sedang ditangani saat ini.** Jaringan pelanggan terpantau aman dan stabil.";
            }
            return $reply;
        }
        return null;
    }

    /**
     * Resolves SOP for Finance payment approval vs Router execution rule.
     */
    protected function handleFinanceRouterSopQuestion(string $lower): ?string
    {
        if (preg_match('/\b(approve|verifikasi)\b/i', $lower) 
            && preg_match('/\b(router|mikrotik|olt)\b/i', $lower) 
            && preg_match('/\b(aktif|sebelumnya)\b/i', $lower)) {
            return "### 💡 Aturan Bisnis IMS2: Approval Pembayaran vs Eksekusi Router:\n\n"
                . "1. **Pelanggan yang Sebelumnya AKTIF (`status_reg = 20`):**\n"
                . "   - Saat Finance menyetujui pembayaran, sistem **HANYA mengupdate database saja** (status invoice berubah menjadi `#15 Lunas`).\n"
                . "   - **SAMA SEKALI TIDAK ADA interaksi ke MikroTik maupun OLT** (tidak melakukan *kick*, tidak *enable secret* ulang, dan tidak mereboot ONU).\n"
                . "   - Hal ini bertujuan agar koneksi internet pelanggan yang sedang aktif **tidak terputus tiba-tiba**.\n\n"
                . "2. **Pelanggan yang Sebelumnya SUSPEND / ISOLIR (`status_reg = 21`):**\n"
                . "   - Sistem akan menjalankan proses **UNSUSPEND** ke router MikroTik (enable secret & kick) serta OLT (remote reboot ONU) untuk memulihkan kembali koneksi internetnya.";
        }
        return null;
    }
}
