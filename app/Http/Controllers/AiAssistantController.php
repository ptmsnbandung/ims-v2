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

        // 2. Check if Gemini API Key is configured (supports multiple comma-separated keys for failover & quota pooling)
        $rawGeminiKeys = config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '');
        $geminiKeys = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$rawGeminiKeys))));
        $geminiModel = trim((string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash')));

        if (!empty($geminiKeys)) {
            $lastException = null;
            foreach ($geminiKeys as $keyIndex => $key) {
                try {
                    $reply = $this->callGeminiApi($key, $geminiModel, $userMessage, $history, $contextData, $currentUser);
                    return response()->json([
                        'status' => 'success',
                        'reply' => $reply,
                        'mode' => 'gemini',
                        'model' => $geminiModel,
                        'key_index' => $keyIndex + 1,
                    ]);
                } catch (\Throwable $e) {
                    $lastException = $e;
                    $this->recordKeyError($key, $e->getMessage(), 500);
                    Log::warning("[AiAssistant] Gemini Key #" . ($keyIndex + 1) . " gagal/kuota habis: " . $e->getMessage() . ". Beralih ke kunci berikutnya...");
                }
            }

            // Fallback to local heuristic engine when all keys are exhausted or error
            $fallbackReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, true);
            return response()->json([
                'status' => 'success',
                'reply' => $fallbackReply,
                'mode' => 'local_fallback',
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
        $configuredModel = (string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash'));
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
        $geminiModel = trim((string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash')));

        $today = now()->format('Y-m-d');
        $usageData = Cache::get('ims_ai_usage_' . $today, [
            'date' => $today,
            'total_requests' => 0,
            'prompt_tokens' => 0,
            'candidates_tokens' => 0,
            'total_tokens' => 0,
            'keys' => [],
        ]);

        $rpdPerKey = 1500; // Free Tier standard Google Gemini 2.0 Flash (1,500 RPD)
        $totalPoolRpd = count($geminiKeys) * $rpdPerKey;
        $totalRequestsUsed = (int)($usageData['total_requests'] ?? 0);
        $totalRequestsRemaining = max(0, $totalPoolRpd - $totalRequestsUsed);
        $percentUsed = $totalPoolRpd > 0 ? min(100, round(($totalRequestsUsed / $totalPoolRpd) * 100, 1)) : 0;

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

            // Auto-clear stale error from previously deprecated gemini-2.0-flash
            if (!empty($keyStats['last_error']) && str_contains($keyStats['last_error'], 'gemini-2.0-flash')) {
                $keyStats['last_error'] = null;
                $keyStats['status'] = $kRequests > 0 ? 'active' : 'standby';
                $usageData['keys'][$keyId] = $keyStats;
                Cache::put('ims_ai_usage_' . $today, $usageData, now()->addDays(14));
            }

            $status = $keyStats['status'] ?? 'standby';
            if ($status === 'standby' && $kRequests > 0) {
                $status = 'active';
            }

            $keyList[] = [
                'index' => $index + 1,
                'account_name' => 'Akun Google #' . ($index + 1),
                'key_id' => $keyId,
                'masked' => $masked,
                'status' => $status,
                'requests_used' => $kRequests,
                'requests_remaining' => $kRemaining,
                'percent_used' => $kPercent,
                'limit_rpd' => $rpdPerKey,
                'prompt_tokens' => (int)($keyStats['prompt_tokens'] ?? 0),
                'candidates_tokens' => (int)($keyStats['candidates_tokens'] ?? 0),
                'total_tokens' => (int)($keyStats['total_tokens'] ?? 0),
                'last_used_at' => $keyStats['last_used_at'] ?? 'Belum ada aktivitas hari ini',
                'last_error' => $keyStats['last_error'] ?? null,
            ];
        }

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
        $configuredModel = trim((string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash')));

        if (empty($geminiKeys)) {
            return response()->json(['success' => false, 'message' => 'Belum ada GEMINI_API_KEY terkonfigurasi di sistem.'], 400);
        }

        $keyIndex = (int)$request->input('key_index', 1) - 1;
        $selectedKey = $geminiKeys[$keyIndex] ?? $geminiKeys[0];
        $actualIndex = isset($geminiKeys[$keyIndex]) ? ($keyIndex + 1) : 1;

        $prompt = trim($request->input('prompt', 'Konfirmasi tes koneksi live AI Gemini IMS2 aktif. Berikan respon 1 kalimat padat.'));

        $modelsToTry = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-2.5-flash',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ])));

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
                        if (!str_contains($mName, '2.0-flash')) {
                            $discovered[] = $mName;
                        }
                    }
                }
                if (!empty($discovered)) {
                    usort($discovered, function ($a, $b) {
                        $scoreA = str_contains($a, '2.5-flash') ? 6 : (str_contains($a, '1.5-flash') ? 5 : (str_contains($a, 'flash') ? 3 : 1));
                        $scoreB = str_contains($b, '2.5-flash') ? 6 : (str_contains($b, '1.5-flash') ? 5 : (str_contains($b, 'flash') ? 3 : 1));
                        return $scoreB <=> $scoreA;
                    });
                    $modelsToTry = array_values(array_unique(array_merge($discovered, $modelsToTry)));
                }
            } else {
                $probeError = $listRes->json('error.message') ?? ('HTTP ' . $listRes->status());
            }
        } catch (\Throwable $e) {
            $probeError = $e->getMessage();
        }

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

                    // If error suggests another model, add and try it immediately
                    if (preg_match('/models\/([a-zA-Z0-9._-]+)/i', $err, $mMatches)) {
                        $suggestedModel = $mMatches[1];
                        if (!in_array($suggestedModel, $modelsToTry, true) && !str_contains($suggestedModel, '2.0-flash')) {
                            $modelsToTry[] = $suggestedModel;
                        }
                    }
                    continue;
                }

                $usage = $response->json('usageMetadata');
                $this->recordUsage($selectedKey, $usage);

                $parts = $response->json('candidates.0.content.parts') ?? [];
                $reply = trim(implode('', array_map(fn($p) => empty($p['thought']) ? ($p['text'] ?? '') : '', $parts)));

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
        $this->recordKeyError($selectedKey, $lastError, $lastStatus);
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
        return response()->json(['success' => true, 'message' => 'Cache monitoring token AI hari ini berhasil direset.']);
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

        $isQuota = $statusCode === 429 || str_contains(strtolower($errorMessage), 'quota') || str_contains(strtolower($errorMessage), 'rate limit');
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
            'selain', 'total', 'jumlah', 'rekap', 'semua', 'seluruh', 'sisa', 'tunggakan', 'piutang'
        ];
        $pattern = '/\b(' . implode('|', $stopwords) . ')\b/i';

        $cleanSearch = trim(preg_replace($pattern, '', $query));
        $cleanSearch = trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleanSearch));
        $cleanSearch = preg_replace('/\s+/', ' ', $cleanSearch);

        // Build list of high-priority search candidates
        $searchCandidates = [];
        if (!empty($cleanSearch) && strlen($cleanSearch) >= 3) {
            $searchCandidates[] = $cleanSearch;
        }

        // Pattern A: Match 'atas nama [Name]' or 'nama [Name]' or 'pelanggan [Name]'
        if (preg_match('/(?:atas\s+nama|nama\s+pelanggan|nama|pelanggan|user)\s+([A-Za-z\s]{3,35}?)(?=\s+(?:tidak|belum|bukan|adalah|yaitu|di|pada|ke|yang|dari|dengan|\.|\,|\n|\r|$))/i', $query, $nm)) {
            $extractedName = trim(preg_replace($pattern, '', $nm[1]));
            if (!empty($extractedName) && strlen($extractedName) >= 3) {
                array_unshift($searchCandidates, $extractedName);
            }
        }

        // Pattern B: Match consecutive UPPERCASE words (e.g. 'MUHAMMAD RAMA PUTRA')
        if (preg_match_all('/\b([A-Z]{3,}(?:\s+[A-Z]{2,}){1,4})\b/', $query, $capsMatches)) {
            foreach ($capsMatches[1] as $capName) {
                $capClean = trim(preg_replace($pattern, '', $capName));
                if (!empty($capClean) && strlen($capClean) >= 3 && !in_array(strtoupper($capClean), ['IMS2', 'IMS', 'NOC', 'OLT', 'GPON', 'NULL'])) {
                    array_unshift($searchCandidates, $capClean);
                }
            }
        }

        // Pattern C: Quoted name string e.g. "MUHAMMAD RAMA PUTRA"
        if (preg_match('/["\']([a-zA-Z0-9\s]{3,35})["\']/i', $query, $qm)) {
            $qClean = trim(preg_replace($pattern, '', $qm[1]));
            if (!empty($qClean) && strlen($qClean) >= 3) {
                array_unshift($searchCandidates, $qClean);
            }
        }

        $searchCandidates = array_values(array_unique($searchCandidates));
        $nameTokens = array_values(array_filter(explode(' ', $cleanSearch), fn($t) => strlen($t) >= 3));

        // Context 1: Customer lookup (by ID, username, or name candidates across batchjob & m_pelanggan)
        if ($extractedId || !empty($searchCandidates) || !empty($nameTokens) || str_contains($lower, 'pelanggan') || str_contains($lower, 'cust') || str_contains($lower, 'user')) {
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

                // Tier 3: Token-by-token fallback (e.g. match first distinctive token >= 4 chars)
                if ($matchedCustomers->isEmpty() && !empty($nameTokens)) {
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

        // Context 2: Billing / Tagihan lookup (Khusus role berhak: Finance, Direksi, CEO, Admin, CS)
        $isGeneralFinanceQuery = str_contains($lower, 'total') || str_contains($lower, 'rekap')
            || str_contains($lower, 'semua') || str_contains($lower, 'selain')
            || str_contains($lower, 'seluruh') || str_contains($lower, 'daftar tagihan')
            || str_contains($lower, 'piutang') || str_contains($lower, 'tunggakan');

        // CRITICAL: Jika pertanyaan menargetkan nama pelanggan spesifik, JANGAN PERNAH menampilkan invoice orang lain!
        $isSpecificCustomerQuery = !$isGeneralFinanceQuery && (!empty($extractedId) || !empty($searchCandidates) || !empty($nameTokens));

        if ($this->canAccessFinance($user) && (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar') || str_contains($lower, 'invoice') || str_contains($lower, 'keuangan') || !empty($targetCustomerIds))) {
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
                    // Query tagihan umum (rekap tagihan global, pending verifikasi, selain lunas, atau belum bayar massal)
                    $isAskingUnpaid = str_contains($lower, 'belum') || str_contains($lower, 'unpaid')
                        || str_contains($lower, 'selain') || str_contains($lower, 'tunggak')
                        || str_contains($lower, 'piutang') || str_contains($lower, 'kurang bayar');
                    $isAskingVerification = str_contains($lower, 'verif') || str_contains($lower, 'pending') || str_contains($lower, 'konfirmasi');
                    $isAskingTotal = str_contains($lower, 'total') || str_contains($lower, 'rekap')
                        || str_contains($lower, 'summary') || str_contains($lower, 'berapa')
                        || str_contains($lower, 'jumlah') || str_contains($lower, 'semua');

                    if ($isAskingUnpaid || ($isAskingTotal && (str_contains($lower, 'tagih') || str_contains($lower, 'bill')))) {
                        $unpaidStats = DB::table('trx_billing_layanan')
                            ->where('hide', 0)
                            ->whereNotIn('status_bill_lay', [15])
                            ->selectRaw('count(*) as count, coalesce(sum(total_layanan), 0) as total_rp')
                            ->first();

                        $breakdownRows = DB::table('trx_billing_layanan')
                            ->where('hide', 0)
                            ->whereNotIn('status_bill_lay', [15])
                            ->selectRaw('status_bill_lay, count(*) as count, coalesce(sum(total_layanan), 0) as total_rp')
                            ->groupBy('status_bill_lay')
                            ->get();

                        $context['billing_summary'] = [
                            'judul' => 'Rekapitulasi Tagihan Belum Terbayar (Selain Lunas)',
                            'total_unpaid_invoices' => (int)($unpaidStats->count ?? 0),
                            'total_unpaid_amount' => (float)($unpaidStats->total_rp ?? 0),
                            'total_unpaid_amount_formatted' => 'Rp ' . number_format((float)($unpaidStats->total_rp ?? 0), 0, ',', '.'),
                            'breakdown' => $breakdownRows->map(function ($r) {
                                $r->status_name = match((string)$r->status_bill_lay) {
                                    '14' => 'Menunggu Verifikasi (Pending)',
                                    '13' => 'Belum Bayar (Unpaid)',
                                    '12' => 'Publish Tagihan',
                                    '11' => 'Draft',
                                    default => 'Status #' . $r->status_bill_lay
                                };
                                $r->total_formatted = 'Rp ' . number_format((float)$r->total_rp, 0, ',', '.');
                                return $r;
                            })->toArray(),
                        ];
                    }

                    $billQuery = DB::table('trx_billing_layanan')->where('hide', 0);
                    if ($isAskingVerification) {
                        $billQuery->where('status_bill_lay', 14); // Pending verifikasi
                    } else if ($isAskingUnpaid) {
                        $billQuery->whereNotIn('status_bill_lay', [15]); // Belum bayar / selain lunas
                    } else if (!$isAskingTotal && !str_contains($lower, 'daftar')) {
                        $billQuery->whereRaw('1 = 0'); // Jangan ambil jika tidak jelas tujuannya
                    }

                    $bills = $billQuery->select([
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

        // Context 5: Global Quick Stats (for summary/recap requests)
        if (str_contains($lower, 'rekap') || str_contains($lower, 'ringkasan') || str_contains($lower, 'summary') || str_contains($lower, 'hari ini') || str_contains($lower, 'status sistem')) {
            try {
                $currentMonth = date('m');
                $currentYear = date('Y');

                $context['stats']['active_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('status_reg', 20)->count();
                $context['stats']['isolated_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('is_suspend', 1)->count();
                
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
            . "PEMAHAMAN ALUR KERJA SISTEM IMS2 (ISP END-TO-END LIFECYCLE):\n"
            . "A. SIKLUS PELANGGAN:\n"
            . "   - Registrasi -> Survey Lokasi (#12) -> Instalasi Lapangan (#16) -> Aktivasi NOC (#18/#19) -> Online Aktif (#20) -> Billing Bulanan -> Terisolir/Suspend (is_suspend=1 jika nunggak) -> Reaktivasi Otomatis/Manual jika lunas -> Terminasi (#23/is_termin=1 jika berhenti langganan).\n"
            . "B. SIKLUS PERANGKAT & JARINGAN FTTH (GPON):\n"
            . "   - Infrastruktur: Router POP/BGP -> Master OLT (m_olt: O1 Kayu Agung, O2 Babakan Tarogong, O3 Soreang) -> Port PON -> Spliter -> Dropcore -> Modem ONU pelanggan (index_olt: format gpon-onu_1/{slot}/{port}:{onu_id}).\n"
            . "   - Status Fisik: UP (Online Normal), LOS (Loss of Signal / Kabel optik putus fisik / redaman hilang), DOWN (Modem padam / mati lampu / dying gasp).\n"
            . "   - Jika index_olt kosong (NULL): Jelaskan langsung singkat bahwa port GPON fisik belum diisi di registrasi pelanggan sehingga sensor optik belum bisa membaca redaman.\n"
            . "   - Monitoring Realtime: Data [noc_telemetry] adalah sumber data telemetri fisik OLT terkini. Jika ditanya 'user yang loss/down', laporkan dari telemetri ini.\n"
            . "C. SIKLUS BILLING & KEUANGAN:\n"
            . "   - Tagihan bulanan dibuat di trx_billing_layanan -> Status: Draft (#11), Publish (#12), Belum Bayar (#13), Pending Verifikasi Bukti (#14), Lunas (#15).\n"
            . "   - Pelanggan bayar -> upload bukti/gateway -> diverifikasi Finance -> Lunas.\n"
            . "D. SIKLUS TIKET GANGGUAN & HELPDESK:\n"
            . "   - Tiket masuk ke trx_tiket_gangguan -> Status: Baru Open (#11) -> Terjadwal/On Schedule (#12) -> Diproses -> Selesai (#13/Selesai).\n"
            . "   - Kategori: Gangguan Sinyal/Kabel Putus, Internet Lambat, Ubah Password, Relokasi Rumah, Ubah Paket Layanan.\n\n"
            . "KEBIJAKAN HAK AKSES ROLE (SANGAT KETAT):\n"
            . "{$roleRules}\n\n"
            . "5. INTEGRITAS DATA PELANGGAN & TAGIHAN (SANGAT KRUSIAL):\n"
            . "   - DILARANG KERAS menghubungkan, mengaitkan, atau menampilkan invoice/tagihan milik orang lain kepada pelanggan yang sedang ditanyakan.\n"
            . "   - Periksa selalu 'nomor_internet' pada setiap baris di [billings]. Jika tidak cocok dengan pelanggan yang ditanyakan, atau jika [billings] kosong/berisi tagihan orang lain, nyatakan dengan jujur dan jelas: Pelanggan tersebut TIDAK MEMILIKI TAGIHAN TERTUNGGAK.\n"
            . "   - Dilarang keras berasumsi atau mengarang nomor internet dan invoice.\n\n"
            . "Jika pengguna meminta data di luar wewenangnya, tolak langsung secara singkat, tegas, dan sopan tanpa berputar-putar.\n"
            . "Hari ini: {$today}.\n\n"
            . $this->schemaHint($userRole);

        // Build contents array for Gemini
        $contents = [];

        // Add relevant past conversation history (up to last 4 messages to save tokens)
        $recentHistory = array_slice($history, -4);
        foreach ($recentHistory as $msg) {
            $role = ($msg['role'] ?? 'user') === 'user' ? 'user' : 'model';
            $text = $msg['text'] ?? '';
            if (!empty($text)) {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]],
                ];
            }
        }

        // Prepare context injection for the current prompt
        $contextPrompt = "Data internal terkini dari sistem IMS2 terkait pertanyaan pengguna:\n" . json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $currentUserPrompt = "Pertanyaan User: {$userMessage}\n\n[Konteks Database IMS2]:\n{$contextPrompt}";

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $currentUserPrompt]],
        ];

        // 1. Discover or use cached valid models for this API key
        $cacheKey = 'gemini_valid_models_' . substr(md5($apiKey), 0, 8);
        $discovered = Cache::get($cacheKey);
        if (!$discovered) {
            try {
                $listRes = Http::timeout(4)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
                if ($listRes->successful()) {
                    $remoteModels = $listRes->json('models', []);
                    $validModels = [];
                    foreach ($remoteModels as $rm) {
                        $methods = $rm['supportedGenerationMethods'] ?? [];
                        if (in_array('generateContent', $methods)) {
                            $mName = str_replace('models/', '', $rm['name'] ?? '');
                            $validModels[] = $mName;
                        }
                    }
                    if (!empty($validModels)) {
                        usort($validModels, function ($a, $b) {
                            $scoreA = str_contains($a, '2.5-flash') ? 6 : (str_contains($a, '2.0-flash') ? 5 : (str_contains($a, 'flash') ? 3 : 1));
                            $scoreB = str_contains($b, '2.5-flash') ? 6 : (str_contains($b, '2.0-flash') ? 5 : (str_contains($b, 'flash') ? 3 : 1));
                            return $scoreB <=> $scoreA;
                        });
                        $discovered = $validModels;
                        Cache::put($cacheKey, $discovered, 3600);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback silently if Google ListModels endpoint is slow or unreachable
            }
        }

        $defaultCandidates = ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-2.0-flash'];
        $modelsToTry = array_values(array_filter(array_unique(array_merge([$model], (array)($discovered ?: $defaultCandidates)))));
        $modelsToTry = array_slice($modelsToTry, 0, 3);

        // 2. Iterate through candidate models with fast per-step timeout
        $triedModels = [];
        $lastError = 'Semua model Gemini yang dicoba gagal merespon.';
        $tools = [['functionDeclarations' => [
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

        for ($mIdx = 0; $mIdx < count($modelsToTry) && $mIdx < 2; $mIdx++) {
            $mName = $modelsToTry[$mIdx];
            $triedModels[] = $mName;
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$mName}:generateContent?key={$apiKey}";
            $convo = $contents;

            try {
                for ($step = 0; $step < 3; $step++) {
                    $response = Http::timeout(8)->post($url, [
                        'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                        'contents' => $convo,
                        'tools' => $tools,
                        'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 1024],
                    ]);

                    if (!$response->successful()) {
                        $errMessage = $response->json('error.message') ?? ('HTTP ' . $response->status());
                        $lastError = $errMessage;
                        $this->recordKeyError($apiKey, $errMessage, $response->status());

                        // Self-healing: if Google API error recommends a specific model, extract and retry with it immediately!
                        if (preg_match('/models\/([a-zA-Z0-9._-]+)/i', $errMessage, $mMatches)) {
                            $suggestedModel = $mMatches[1];
                            if ($suggestedModel !== $mName && !in_array($suggestedModel, $triedModels, true) && !in_array($suggestedModel, $modelsToTry, true)) {
                                $modelsToTry[] = $suggestedModel;
                                Cache::put($cacheKey, array_values(array_unique(array_merge([$suggestedModel], (array)($discovered ?: [])))), 3600);
                            }
                        }

                        continue 2;
                    }

                    $usage = $response->json('usageMetadata');
                    $this->recordUsage($apiKey, $usage);

                    $parts = $response->json('candidates.0.content.parts') ?? [];
                    $calls = array_values(array_filter($parts, fn($p) => isset($p['functionCall'])));

                    if (empty($calls)) {
                        $text = trim(implode('', array_map(fn($p) => empty($p['thought']) ? ($p['text'] ?? '') : '', $parts)));
                        if ($text !== '') {
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
                }
                $lastError = 'Terlalu banyak langkah query database.';
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \Exception($lastError);
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

        return "KEMAMPUAN DATABASE: Anda memiliki tool `describe_table` dan `run_select_query` (read-only) untuk menjawab data di database IMS2 sesuai wewenang role {$userRole}.\n"
            . "- Pelanggan: `trx_batchjob_register` (hide=0, kunci nomor_internet, status_reg 20=aktif, is_suspend=1 terisolir).\n"
            . "- Tiket: `trx_tiket_gangguan`.\n"
            . ($this->canAccessFinanceRole($userRole) ? "- Tagihan: `trx_billing_layanan` (status_bill_lay 15=lunas, 14=verifikasi).\n" : "")
            . ($this->canAccessNetworkRole($userRole) ? "- OLT & Jaringan: `m_olt`.\n" : "")
            . "Daftar tabel yang diizinkan untuk role {$userRole}: " . implode(', ', $tables);
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
        if (!empty($context['stats']) && (isset($context['stats']['active_customers']) || isset($context['stats']['isolated_customers']))) {
            $hasData = true;
            $output[] = "\n### 📊 Ringkasan Operasional IMS2:";
            if (isset($context['stats']['active_customers'])) {
                $output[] = "- 👥 **Total Pelanggan Aktif Online (#20):** `{$context['stats']['active_customers']}` pelanggan";
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
