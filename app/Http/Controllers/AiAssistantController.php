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
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $userMessage = trim($request->input('message'));
        $history = $request->input('history', []);
        $currentUser = auth()->user();

        // 1. Gather relevant domain context from IMS database based on user intent
        $contextData = $this->gatherContext($userMessage, $currentUser);

        // 2. Check if Gemini API Key is configured
        $geminiKey = trim((string)(config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '')));
        $geminiModel = trim((string)(config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-1.5-flash')));

        if (!empty($geminiKey)) {
            try {
                $reply = $this->callGeminiApi($geminiKey, $geminiModel, $userMessage, $history, $contextData, $currentUser);
                return response()->json([
                    'status' => 'success',
                    'reply' => $reply,
                    'mode' => 'gemini',
                    'model' => $geminiModel,
                ]);
            } catch (\Throwable $e) {
                $errDetail = $e->getMessage();
                Log::warning('Gemini AI Assistant error: ' . $errDetail);
                // Fallback to local heuristic engine with clear diagnostic reason
                $fallbackReply = $this->generateLocalResponse($userMessage, $contextData, $currentUser, true, $errDetail);
                return response()->json([
                    'status' => 'success',
                    'reply' => $fallbackReply,
                    'mode' => 'local_fallback',
                    'warning' => 'Koneksi Gemini API mengalami kendala: ' . $errDetail,
                ]);
            }
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
        $geminiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        return response()->json([
            'configured' => !empty($geminiKey),
            'model' => config('services.gemini.model', 'gemini-1.5-flash'),
            'user' => [
                'name' => auth()->user()?->nama_lengkap ?? auth()->user()?->username ?? 'Staff',
                'role' => auth()->user()?->role ?? 'staff',
            ],
        ]);
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
            'tagihan', 'billing', 'invoice', 'bayar', 'pembayaran', 'lunas', 'unpaid', 'paid',
            'tolong', 'mohon', 'bantu', 'bisa', 'dong', 'ya', 'min', 'kak', 'admin', 'halo', 'hai', 'selamat', 'pagi', 'siang', 'sore', 'malam',
            'apakah', 'ada', 'berapa', 'siapa', 'kapan', 'kenapa', 'bagaimana', 'apa', 'tentang',
            'terisolir', 'isolir', 'suspend', 'gangguan', 'kendala', 'masalah', 'tiket', 'paket', 'layanan', 'internet', 'milik', 'punya', 'atas',
            'adalah', 'itu', 'ini', 'kah', 'dengan', 'bernama', 'si', 'dari', 'pada', 'yang'
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

        // Context 1: Customer lookup (by ID, username, or name candidates)
        if ($extractedId || !empty($searchCandidates) || !empty($nameTokens) || str_contains($lower, 'pelanggan') || str_contains($lower, 'cust') || str_contains($lower, 'user')) {
            try {
                $hasPppoeCol = Schema::hasColumn('trx_batchjob_register', 'pppoe_username');
                $userCol = $hasPppoeCol ? 'pppoe_username' : 'ont_us';

                $selectCols = [
                    'nomor_internet',
                    'nama_pelanggan',
                    $userCol . ' as pppoe_username',
                    'kode_bandwith',
                    'status_reg',
                    'is_suspend',
                    'is_termin',
                    'alamat_pasang',
                    'olt',
                    'index_olt',
                    'router_id',
                ];

                $matchedCustomers = collect();

                if ($extractedId) {
                    $matchedCustomers = DB::table('trx_batchjob_register')
                        ->where('hide', 0)
                        ->where(function ($q) use ($extractedId, $userCol) {
                            $q->where('nomor_internet', 'like', "%{$extractedId}%")
                              ->orWhere($userCol, 'like', "%{$extractedId}%");
                        })
                        ->select($selectCols)
                        ->limit(5)
                        ->get();
                }

                // Tier 1: Try full phrase match on nama_pelanggan or userCol for all candidates
                if ($matchedCustomers->isEmpty()) {
                    foreach ($searchCandidates as $term) {
                        if (strlen($term) >= 3) {
                            $res = DB::table('trx_batchjob_register')
                                ->where('hide', 0)
                                ->where(function ($q) use ($term, $userCol) {
                                    $q->where('nama_pelanggan', 'like', "%{$term}%")
                                      ->orWhere($userCol, 'like', "%{$term}%")
                                      ->orWhere('nomor_internet', 'like', "%{$term}%");
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
                    $q = DB::table('trx_batchjob_register')->where('hide', 0);
                    foreach ($nameTokens as $tok) {
                        $q->where(function ($sub) use ($tok, $userCol) {
                            $sub->where('nama_pelanggan', 'like', "%{$tok}%")
                                ->orWhere($userCol, 'like', "%{$tok}%");
                        });
                    }
                    $matchedCustomers = $q->select($selectCols)->limit(5)->get();
                }

                // Tier 3: Single token match
                if ($matchedCustomers->isEmpty() && count($nameTokens) === 1) {
                    $matchedCustomers = DB::table('trx_batchjob_register')
                        ->where('hide', 0)
                        ->where(function ($q) use ($nameTokens, $userCol) {
                            $q->where('nama_pelanggan', 'like', "%{$nameTokens[0]}%")
                              ->orWhere($userCol, 'like', "%{$nameTokens[0]}%");
                        })
                        ->select($selectCols)
                        ->limit(5)
                        ->get();
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

        // Context 2: Billing / Tagihan lookup
        if (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar') || str_contains($lower, 'invoice') || str_contains($lower, 'keuangan') || !empty($targetCustomerIds)) {
            try {
                $billQuery = DB::table('trx_billing_layanan')
                    ->where('hide', 0);

                if (!empty($targetCustomerIds)) {
                    $billQuery->where(function ($q) use ($targetCustomerIds) {
                        foreach ($targetCustomerIds as $id) {
                            $q->orWhere('nomor_internet', 'like', "%{$id}%")
                              ->orWhere('kode_billing_layanan', 'like', "%{$id}%");
                        }
                    });
                } else if (str_contains($lower, 'verif') || str_contains($lower, 'pending') || str_contains($lower, 'konfirmasi')) {
                    $billQuery->where('status_bill_lay', 14); // Pending verifikasi
                } else if (str_contains($lower, 'belum') || str_contains($lower, 'unpaid')) {
                    $billQuery->whereNotIn('status_bill_lay', [15]); // Belum bayar
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

        // Context 4: OLT & Network
        if (str_contains($lower, 'olt') || str_contains($lower, 'jaringan') || str_contains($lower, 'noc') || str_contains($lower, 'pon') || str_contains($lower, 'server') || str_contains($lower, 'redaman') || str_contains($lower, 'optik') || !empty($context['customers'])) {
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

        // Context 5: Global Quick Stats (for summary/recap requests)
        if (str_contains($lower, 'rekap') || str_contains($lower, 'ringkasan') || str_contains($lower, 'summary') || str_contains($lower, 'hari ini') || str_contains($lower, 'status sistem')) {
            try {
                $currentMonth = date('m');
                $currentYear = date('Y');

                $context['stats']['active_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('status_reg', 20)->count();
                $context['stats']['isolated_customers'] = DB::table('trx_batchjob_register')->where('hide', 0)->where('is_suspend', 1)->count();
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
     * Call Google Gemini API (v1beta) using Laravel Http client.
     */
    protected function callGeminiApi(string $apiKey, string $model, string $userMessage, array $history, array $contextData, $user): string
    {
        $userName = $user?->nama_lengkap ?? $user?->username ?? 'Staff IMS';
        $userRole = $user?->role ?? 'staff';

        $systemInstruction = "Anda adalah IMS AI Copilot, asisten kecerdasan buatan cerdas untuk sistem internal ISP (PT Media Solusi Network / MSN Bandung).
Tugas Anda membantu staff (NOC, Finance, Teknik, dan Direksi/Admin) dalam operasional harian:
- Menjawab pertanyaan terkait data pelanggan, status registrasi, isolir/suspend, dan OLT.
- Membantu pencarian & ringkasan tagihan / billing, pembayaran pending verifikasi, dan status lunas.
- Menjelaskan status tiket gangguan dan keluhan pelanggan.
- Terkait OLT & ONU Pelanggan:
  1. Jika index_olt pelanggan bernilai NULL / kosong: Jelaskan dengan jelas dan ramah bahwa pengecekan status fisik real-time / redaman laser optik (Rx/Tx) belum bisa dilakukan karena nomor port GPON ONU (format: gpon-onu_1/{slot}/{port}:{onu_id}) belum dipetakan pada database registrasi pelanggan tersebut. Sebutkan nama & IP OLT induk pelanggan (misal: OLT Babakan Tarogong / O2) dan sarankan tim teknisi/NOC melengkapi index_olt pada menu data pelanggan.
  2. Jika index_olt terisi: Tampilkan nama OLT, IP OLT, dan nomor port GPON index_olt pelanggan tersebut.
- Memberikan jawaban yang ringkas, profesional, to the point, dan langsung dapat ditindaklanjuti.
- Gunakan format markdown yang rapi: gunakan tabel untuk data tabular, bullet points, dan kode mono `font-mono` untuk ID internet, IP, kode billing, dan nominal rupiah.
- Pengguna yang sedang berbicara bernama {$userName} dengan hak akses / role: {$userRole}.
- Jangan mengarang data sensitif. Jika data tidak ditemukan di konteks yang disediakan, sampaikan dengan sopan dan sarankan parameter pencarian yang tepat (misal: 'Silakan masukkan nomor internet pelanggan atau nama lengkap').

" . $this->schemaHint();

        // Build contents array for Gemini
        $contents = [];

        // Add relevant past conversation history (up to last 6 messages)
        $recentHistory = array_slice($history, -6);
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

        // 1. Dynamically query Google ListModels API to discover exact supported models for this API key
        $modelsToTry = array_filter([$model, 'gemini-1.5-flash-latest', 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-pro']);
        
        try {
            $listRes = Http::timeout(8)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            if ($listRes->successful()) {
                $remoteModels = $listRes->json('models', []);
                $validModels = [];
                foreach ($remoteModels as $rm) {
                    $methods = $rm['supportedGenerationMethods'] ?? [];
                    if (in_array('generateContent', $methods)) {
                        $validModels[] = str_replace('models/', '', $rm['name'] ?? '');
                    }
                }

                if (!empty($validModels)) {
                    // Sort: prioritize flash, then 2.0, then pro
                    usort($validModels, function ($a, $b) {
                        $scoreA = str_contains($a, 'flash') ? 3 : (str_contains($a, '2.0') ? 2 : 1);
                        $scoreB = str_contains($b, 'flash') ? 3 : (str_contains($b, '2.0') ? 2 : 1);
                        return $scoreB <=> $scoreA;
                    });
                    $modelsToTry = array_values(array_unique(array_merge($validModels, $modelsToTry)));
                } else {
                    $availableNames = array_map(fn($m) => str_replace('models/', '', $m['name'] ?? ''), $remoteModels);
                    $preview = implode(', ', array_slice($availableNames, 0, 4));
                    throw new \Exception("Akun Google Anda hanya memiliki model: [{$preview}], namun tidak ada yang mendukung generateContent.");
                }
            } else {
                $errBody = $listRes->json();
                $listError = $errBody['error']['message'] ?? ('HTTP ' . $listRes->status());
                throw new \Exception("Akses Google API ditolak: " . $listError);
            }
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Google API') || str_contains($e->getMessage(), 'Akun Google Anda')) {
                throw $e;
            }
            // Timeout or network glitch during ListModels: proceed with default candidates
        }

        // 2. Iterate through discovered valid models; each may call DB tools for several rounds
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

        foreach ($modelsToTry as $mName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$mName}:generateContent?key={$apiKey}";
            $convo = $contents;

            try {
                for ($step = 0; $step < 6; $step++) {
                    $response = Http::timeout(30)->post($url, [
                        'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                        'contents' => $convo,
                        'tools' => $tools,
                        'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 2048],
                    ]);

                    if (!$response->successful()) {
                        $lastError = $response->json('error.message') ?? ('HTTP ' . $response->status());
                        continue 2;
                    }

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
                            'response' => ['result' => $this->runAiTool($p['functionCall']['name'], $p['functionCall']['args'] ?? [])],
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

    protected function schemaHint(): string
    {
        $tables = Cache::remember('ai_copilot_tables', 3600, function () {
            try {
                return array_map(fn($r) => array_values((array)$r)[0], DB::select('SHOW TABLES'));
            } catch (\Throwable $e) {
                return [];
            }
        });

        return "KEMAMPUAN DATABASE: Anda punya tool `describe_table` dan `run_select_query` (read-only) untuk menjawab APA SAJA yang ada di database IMS2. Konteks di atas hanya ringkasan awal; jika kurang, atau pertanyaan menyangkut data lain (mitra, router, barang/inventori, penjualan, user, jadwal, dsb), WAJIB gunakan tool, jangan menebak atau menjawab 'tidak ada data' sebelum benar-benar query.\n"
            . "- Pelanggan: `trx_batchjob_register` (hide=0, kunci nomor_internet, status_reg 20=aktif, is_suspend=1 terisolir). Tagihan: `trx_billing_layanan` (status_bill_lay 15=lunas, 14=verifikasi). Tiket: `trx_tiket_gangguan`. OLT: `m_olt`.\n"
            . "- Pencarian nama pakai LIKE '%kata%' per kata; jika 0 hasil coba kata lebih pendek. Gabungkan beberapa query bila perlu, lalu simpulkan dalam bahasa Indonesia.\n"
            . "- Jangan pernah menampilkan password/token/kredensial. Hanya SELECT.\n"
            . "Daftar tabel: " . implode(', ', $tables);
    }

    protected function runAiTool(string $name, array $args): array
    {
        try {
            if ($name === 'describe_table') {
                $table = (string)($args['table'] ?? '');
                if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !Schema::hasTable($table)) {
                    return ['error' => "Tabel '{$table}' tidak ditemukan."];
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
        $userName = $user?->nama_lengkap ?? $user?->username ?? 'Staff';
        $lower = strtolower($query);
        $output = [];

        if ($isFallback) {
            $output[] = "> ⚠️ *Catatan: Menampilkan data internal langsung via Offline Database Heuristic Engine.*";
            if (!empty($errorMessage)) {
                $cleanErr = strlen($errorMessage) > 160 ? substr($errorMessage, 0, 160) . '...' : $errorMessage;
                $output[] = "> 🔍 *Status Google API: " . htmlspecialchars($cleanErr) . "*";
            }
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

        // Case 2: Billing details found
        if (!empty($context['billings'])) {
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
            if (isset($context['stats']['pending_verification_count']) && $context['stats']['pending_verification_count'] === 0) {
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
        $output[] = "\nSaya siap membantu pekerjaan Anda dalam operasional IMS2:";
        $output[] = "1. **Cek Pelanggan**: Ketik nama pelanggan atau nomor internet (contoh: `cek pelanggan 102938` atau `cari nama Budi`).";
        $output[] = "2. **Cek Tagihan & Pembayaran**: Ketik `cek tagihan pending` atau `cek billing 102938`.";
        $output[] = "3. **Cek Tiket Gangguan**: Ketik `cek tiket open` atau `keluhan internet`.";
        $output[] = "4. **Cek Jaringan / OLT**: Ketik `status olt` atau `jaringan`.";
        $output[] = "5. **Ringkasan Operasional**: Ketik `rekap hari ini` atau `summary sistem`.";

        if (!$isFallback) {
            $output[] = "\n---";
            $output[] = "💡 *Tips: Untuk percakapan natural tak terbatas & analisis mendalam, tambahkan `GEMINI_API_KEY=AIzaSy...` pada file `.env`.*";
        }

        return implode("\n", $output);
    }
}
