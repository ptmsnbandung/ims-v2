<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Context 1: Customer lookup (by ID, username, or name)
        if (str_contains($lower, 'pelanggan') || str_contains($lower, 'cust') || str_contains($lower, 'user') || str_contains($lower, 'client') || $extractedId) {
            $custQuery = DB::table('trx_batchjob_register')
                ->where('hide', 0);

            if ($extractedId) {
                $custQuery->where(function ($q) use ($extractedId) {
                    $q->where('nomor_internet', 'like', "%{$extractedId}%")
                      ->orWhere('pppoe_username', 'like', "%{$extractedId}%");
                });
            } else {
                // Extract search terms (strip common keywords)
                $searchTerm = trim(preg_replace('/\b(cari|cek|lihat|info|data|pelanggan|nama|user|status)\b/i', '', $query));
                if (strlen($searchTerm) >= 3) {
                    $custQuery->where(function ($q) use ($searchTerm) {
                        $q->where('nama_pelanggan', 'like', "%{$searchTerm}%")
                          ->orWhere('pppoe_username', 'like', "%{$searchTerm}%")
                          ->orWhere('nomor_internet', 'like', "%{$searchTerm}%");
                    });
                }
            }

            $context['customers'] = $custQuery->select([
                'nomor_internet',
                'nama_pelanggan',
                'pppoe_username',
                'kode_bandwith',
                'status_reg',
                'is_suspend',
                'is_termin',
                'alamat_pasang',
                'olt',
                'router_id',
            ])->limit(5)->get()->toArray();
        }

        // Context 2: Billing / Tagihan lookup
        if (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'bayar') || str_contains($lower, 'invoice') || str_contains($lower, 'keuangan') || $extractedId) {
            $billQuery = DB::table('trx_billing_layanan')
                ->where('hide', 0);

            if ($extractedId) {
                $billQuery->where(function ($q) use ($extractedId) {
                    $q->where('kode_billing_layanan', 'like', "%{$extractedId}%")
                      ->orWhere('nomor_internet', 'like', "%{$extractedId}%");
                });
            } else if (str_contains($lower, 'verif') || str_contains($lower, 'pending') || str_contains($lower, 'konfirmasi')) {
                $billQuery->where('status_bill_lay', 14); // Pending verifikasi
            } else if (str_contains($lower, 'belum') || str_contains($lower, 'unpaid')) {
                $billQuery->whereNotIn('status_bill_lay', [15]); // Belum bayar
            }

            $context['billings'] = $billQuery->select([
                'kode_billing_layanan',
                'nomor_internet',
                'bulan_tagihan',
                'tahun_tagihan',
                'total_layanan',
                'status_bill_lay',
                'payment_type',
                'payment_paid',
                'amount_paid',
            ])->orderByDesc('kode_billing_layanan')->limit(5)->get()->toArray();

            // Total pending verification count
            $context['stats']['pending_verification_count'] = DB::table('trx_billing_layanan')
                ->where('hide', 0)
                ->where('status_bill_lay', 14)
                ->count();
        }

        // Context 3: Tiket Gangguan
        if (str_contains($lower, 'tiket') || str_contains($lower, 'gangguan') || str_contains($lower, 'keluhan') || str_contains($lower, 'los') || str_contains($lower, 'komplain')) {
            $context['tickets'] = DB::table('trx_tiket_gangguan')
                ->where('hide', 0)
                ->when($extractedId, function ($q) use ($extractedId) {
                    $q->where(function ($sub) use ($extractedId) {
                        $sub->where('tiket', 'like', "%{$extractedId}%")
                            ->orWhere('nomor_internet', 'like', "%{$extractedId}%");
                    });
                })
                ->when(!$extractedId, function ($q) {
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
        }

        // Context 4: OLT & Network
        if (str_contains($lower, 'olt') || str_contains($lower, 'jaringan') || str_contains($lower, 'noc') || str_contains($lower, 'pon') || str_contains($lower, 'server')) {
            $context['olts'] = DB::table('m_olt')
                ->select(['kode_olt', 'name_olt', 'ip_address', 'brand', 'model', 'is_active', 'last_status'])
                ->orderBy('name_olt')
                ->limit(8)
                ->get()
                ->toArray();
        }

        // Context 5: Global Quick Stats (for summary/recap requests)
        if (str_contains($lower, 'rekap') || str_contains($lower, 'ringkasan') || str_contains($lower, 'summary') || str_contains($lower, 'hari ini') || str_contains($lower, 'status sistem')) {
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
- Memberikan jawaban yang ringkas, profesional, to the point, dan langsung dapat ditindaklanjuti.
- Gunakan format markdown yang rapi: gunakan tabel untuk data tabular, bullet points, dan kode mono `font-mono` untuk ID internet, IP, kode billing, dan nominal rupiah.
- Pengguna yang sedang berbicara bernama {$userName} dengan hak akses / role: {$userRole}.
- Jangan mengarang data sensitif. Jika data tidak ditemukan di konteks yang disediakan, sampaikan dengan sopan dan sarankan parameter pencarian yang tepat (misal: 'Silakan masukkan nomor internet pelanggan atau nama lengkap').";

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

        // 2. Iterate through discovered valid models
        $lastError = 'Semua model Gemini yang dicoba gagal merespon.';
        foreach ($modelsToTry as $mName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$mName}:generateContent?key={$apiKey}";
            $payload = [
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 1200,
                ],
            ];

            try {
                $response = Http::timeout(15)->post($url, $payload);
                if ($response->successful()) {
                    $resJson = $response->json();
                    $replyText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (!empty($replyText)) {
                        return trim($replyText);
                    }
                } else {
                    $errBody = $response->json();
                    $lastError = $errBody['error']['message'] ?? ('HTTP ' . $response->status());
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \Exception($lastError);
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
                // Shorten if too long
                $cleanErr = strlen($errorMessage) > 160 ? substr($errorMessage, 0, 160) . '...' : $errorMessage;
                $output[] = "> 🔍 *Status Google API: " . htmlspecialchars($cleanErr) . "*";
            }
        }

        // Case 1: Customer details found or searched
        if (!empty($context['customers'])) {
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
                $output[] = "- **{$c->nama_pelanggan}** (`{$c->nomor_internet}`)";
                $output[] = "  - Paket: `{$c->kode_bandwith}` | Status: {$statusText} | Isolir: {$suspendText}";
                $output[] = "  - PPPoE: `{$c->pppoe_username}` | OLT: `{$c->olt}`";
                $output[] = "  - Alamat: *{$c->alamat_pasang}*";
            }
            return implode("\n", $output);
        } else if (str_contains($lower, 'pelanggan') || str_contains($lower, 'cust')) {
            $output[] = "### 👤 Pencarian Pelanggan";
            $output[] = "🔍 Tidak ditemukan pelanggan dengan kata kunci pencarian tersebut.";
            $output[] = "👉 *Tips: Masukkan nomor internet (contoh: `102938`) atau nama pelanggan lengkap.*";
            return implode("\n", $output);
        }

        // Case 2: Billing details found or searched
        if (!empty($context['billings'])) {
            $output[] = "### 💳 Data Tagihan & Pembayaran (" . count($context['billings']) . "):";
            $output[] = "| No. Billing | No. Internet | Periode | Total | Status |";
            $output[] = "|---|---|---|---|---|";
            foreach ($context['billings'] as $b) {
                $st = match((int)$b->status_bill_lay) {
                    15 => '🟢 Lunas',
                    14 => '🟡 Menunggu Verifikasi',
                    default => '🔴 Belum Bayar'
                };
                $totalRp = 'Rp ' . number_format($b->total_layanan ?? 0, 0, ',', '.');
                $output[] = "| `{$b->kode_billing_layanan}` | `{$b->nomor_internet}` | {$b->bulan_tagihan}/{$b->tahun_tagihan} | {$totalRp} | {$st} |";
            }

            if (isset($context['stats']['pending_verification_count'])) {
                $output[] = "\n📌 *Terdapat **{$context['stats']['pending_verification_count']}** tagihan menunggu konfirmasi/verifikasi finance.*";
            }
            return implode("\n", $output);
        } else if (str_contains($lower, 'tagih') || str_contains($lower, 'bill') || str_contains($lower, 'pending') || str_contains($lower, 'verif')) {
            $output[] = "### 💳 Informasi Tagihan & Verifikasi";
            if (isset($context['stats']['pending_verification_count']) && $context['stats']['pending_verification_count'] === 0) {
                $output[] = "✅ **Tidak ada tagihan yang menunggu verifikasi saat ini.**";
                $output[] = "Seluruh konfirmasi pembayaran dari pelanggan maupun portal sudah diproses oleh tim finance.";
            } else {
                $output[] = "🔍 Data tagihan spesifik tidak ditemukan. Silakan masukkan nomor billing atau nomor internet pelanggan.";
            }
            return implode("\n", $output);
        }

        // Case 3: Tiket gangguan found or searched
        if (!empty($context['tickets'])) {
            $output[] = "### 🎫 Tiket Gangguan Ditemukan (" . count($context['tickets']) . "):";
            foreach ($context['tickets'] as $t) {
                $output[] = "- **Tiket `{$t->tiket}`** (Internet: `{$t->nomor_internet}`)";
                $output[] = "  - Keluhan: *{$t->keluhan}*";
                $output[] = "  - Status: **" . strtoupper($t->status) . "** | Penanganan: " . ($t->penanganan ?: 'Menunggu Teknisi');
            }
            return implode("\n", $output);
        } else if (str_contains($lower, 'tiket') || str_contains($lower, 'gangguan') || str_contains($lower, 'keluhan')) {
            $output[] = "### 🎫 Status Tiket Gangguan";
            $output[] = "🎉 **Tidak ada tiket gangguan yang berstatus open / aktif saat ini.**";
            $output[] = "Layanan internet pelanggan terpantau aman tanpa laporan kendala.";
            return implode("\n", $output);
        }

        // Case 4: OLTs found
        if (!empty($context['olts'])) {
            $output[] = "### 🌐 Status OLT / Jaringan Utama:";
            $output[] = "| Nama OLT | Brand / Model | IP Address | Status |";
            $output[] = "|---|---|---|---|";
            foreach ($context['olts'] as $o) {
                $st = ($o->is_active == 1) ? '🟢 Aktif' : '🔴 Non-Aktif';
                $output[] = "| **{$o->name_olt}** | {$o->brand} {$o->model} | `{$o->ip_address}` | {$st} |";
            }
            return implode("\n", $output);
        }

        // Case 5: Summary stats
        if (!empty($context['stats']) && (isset($context['stats']['active_customers']) || isset($context['stats']['isolated_customers']))) {
            $output[] = "### 📊 Ringkasan Operasional IMS2:";
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
