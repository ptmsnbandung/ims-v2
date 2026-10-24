<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCustomMailJob;
use App\Services\HostingerMailService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EmailController extends Controller
{
    protected HostingerMailService $hostingerService;

    public function __construct(HostingerMailService $hostingerService)
    {
        $this->hostingerService = $hostingerService;
    }

    /**
     * Memastikan tabel logging broadcast email tersedia
     */
    protected function ensureSchema(): void
    {
        if (Cache::has('ims_broadcast_email_schema_v1')) {
            return;
        }

        try {
            if (!Schema::hasTable('tb_broadcast_email_log')) {
                Schema::create('tb_broadcast_email_log', function (Blueprint $table) {
                    $table->id();
                    $table->string('nomor_internet', 50)->nullable()->index();
                    $table->string('nama_pelanggan', 255)->nullable();
                    $table->string('email_tujuan', 255)->index();
                    $table->string('subjek', 255);
                    $table->string('badge_text', 100)->nullable();
                    $table->longText('pesan_html');
                    $table->string('status', 20)->default('queued')->index(); // queued, sent, failed
                    $table->text('error_message')->nullable();
                    $table->string('sender_user', 100)->nullable();
                    $table->timestamps();
                });
            }
            Cache::put('ims_broadcast_email_schema_v1', true, 86400);
        } catch (\Throwable $e) {
            Log::warning("ensureSchema tb_broadcast_email_log note: " . $e->getMessage());
        }
    }

    /**
     * Halaman Utama: Kelola & Kirim Custom Email
     */
    public function index(Request $request): View
    {
        $this->ensureSchema();

        $search = trim((string) $request->input('search', ''));
        $statusFilter = (string) $request->input('status', 'all'); // all, with_email, without_email, active
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 250])) {
            $perPage = 25;
        }

        // Query Pelanggan
        $query = $this->getBaseCustomerQuery();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('c.nomor_internet', 'like', "%{$search}%")
                  ->orWhere('c.nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('c.email', 'like', "%{$search}%")
                  ->orWhere('c.nomor_hp', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'with_email') {
            $query->whereNotNull('c.email')
                  ->where('c.email', '!=', '')
                  ->where('c.email', 'like', '%@%');
        } elseif ($statusFilter === 'without_email') {
            $query->where(function ($q) {
                $q->whereNull('c.email')
                  ->orWhere('c.email', '=', '')
                  ->orWhere('c.email', 'not like', '%@%');
            });
        } elseif ($statusFilter === 'active') {
            $query->where(function ($q) {
                $q->where('c.status_user', '1')
                  ->orWhere('c.status_user', 'like', '%aktif%');
            });
        }

        $customers = $query->paginate($perPage)->withQueryString();

        // Hitung KPI
        $kpi = $this->calculateKpi();

        // Template Presets
        $presets = $this->getEmailPresets();

        // Riwayat Pengiriman Terkini (10 Terakhir)
        $recentLogs = collect();
        if (Schema::hasTable('tb_broadcast_email_log')) {
            $recentLogs = DB::table('tb_broadcast_email_log')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
        }

        $isHostingerReady = $this->hostingerService->isConfigured();

        return view('admin.email', compact(
            'customers',
            'kpi',
            'presets',
            'recentLogs',
            'isHostingerReady',
            'search',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * AJAX / POST: Kirim Email (Single, Bulk, atau Test)
     */
    public function send(Request $request): JsonResponse
    {
        $this->ensureSchema();

        $request->validate([
            'subject'     => 'required|string|max:255',
            'message'     => 'required|string',
            'badge_text'  => 'nullable|string|max:100',
            'action_url'  => 'nullable|url|max:255',
            'action_text' => 'nullable|string|max:100',
        ]);

        $subject    = trim($request->input('subject'));
        $rawMessage = trim($request->input('message'));
        $badgeText  = trim((string)$request->input('badge_text', 'PEMBERITAHUAN RESMI'));
        $actionUrl  = $request->filled('action_url') ? trim($request->input('action_url')) : null;
        $actionText = $request->filled('action_text') ? trim($request->input('action_text')) : null;
        $senderUser = Auth::user()?->nama ?? Auth::user()?->username ?? 'ADMIN';

        // 1. Opsi Test Send ke Email Tertentu (Misal email Admin/Penguji)
        if ($request->boolean('is_test')) {
            $testEmail = trim((string)$request->input('test_email'));
            if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alamat email uji coba tidak valid.'
                ], 422);
            }

            $testCustomer = (object) [
                'nama_pelanggan' => 'User Pengujian Admin',
                'nomor_internet' => '100000',
                'nama_bandwith'  => 'PAKET 50 Mbps',
                'email'          => $testEmail,
            ];

            $formattedHtml = $this->formatMessageHtml($rawMessage, $testCustomer);

            $logId = null;
            if (Schema::hasTable('tb_broadcast_email_log')) {
                $logId = DB::table('tb_broadcast_email_log')->insertGetId([
                    'nomor_internet' => 'TEST',
                    'nama_pelanggan' => 'Admin Test (' . $senderUser . ')',
                    'email_tujuan'   => $testEmail,
                    'subjek'         => '[TEST] ' . $subject,
                    'badge_text'     => $badgeText . ' (TEST)',
                    'pesan_html'     => $formattedHtml,
                    'status'         => 'queued',
                    'sender_user'    => $senderUser,
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'updated_at'     => Carbon::now()->toDateTimeString(),
                ]);
            }

            SendCustomMailJob::dispatch(
                $testEmail,
                '[TEST] ' . $subject,
                $formattedHtml,
                $testCustomer->nama_pelanggan,
                $testCustomer->nomor_internet,
                $badgeText . ' (TEST)',
                $actionUrl,
                $actionText,
                $senderUser,
                $logId
            );

            return response()->json([
                'success' => true,
                'message' => "Email pengujian berhasil dimasukkan ke antrean pengiriman ke {$testEmail}!"
            ]);
        }

        // 2. Pengiriman ke Pelanggan Terpilih
        $targetIds = $request->input('targets', []);
        if (is_string($targetIds)) {
            $targetIds = explode(',', $targetIds);
        }
        $targetIds = array_values(array_unique(array_filter(array_map('trim', (array)$targetIds))));

        if (empty($targetIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan pilih minimal 1 pelanggan penerima email.'
            ], 422);
        }

        // Ambil data pelanggan dari database
        $customers = $this->getBaseCustomerQuery()
            ->whereIn('c.nomor_internet', $targetIds)
            ->get();

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan terpilih tidak ditemukan di database.'
            ], 404);
        }

        $queuedCount = 0;
        $skippedCount = 0;

        foreach ($customers as $cust) {
            $email = trim((string)($cust->email ?? ''));

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                continue;
            }

            // Ganti tag variabel template
            $formattedHtml = $this->formatMessageHtml($rawMessage, $cust);

            $logId = null;
            if (Schema::hasTable('tb_broadcast_email_log')) {
                $logId = DB::table('tb_broadcast_email_log')->insertGetId([
                    'nomor_internet' => $cust->nomor_internet,
                    'nama_pelanggan' => $cust->nama_pelanggan,
                    'email_tujuan'   => $email,
                    'subjek'         => $subject,
                    'badge_text'     => $badgeText,
                    'pesan_html'     => $formattedHtml,
                    'status'         => 'queued',
                    'sender_user'    => $senderUser,
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'updated_at'     => Carbon::now()->toDateTimeString(),
                ]);
            }

            SendCustomMailJob::dispatch(
                $email,
                $subject,
                $formattedHtml,
                $cust->nama_pelanggan,
                $cust->nomor_internet,
                $badgeText,
                $actionUrl,
                $actionText,
                $senderUser,
                $logId
            );

            $queuedCount++;
        }

        $msg = "Berhasil mendaftarkan {$queuedCount} email ke background antrean!";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} pelanggan dilewati karena belum memiliki alamat email yang valid).";
        }

        return response()->json([
            'success' => true,
            'queued'  => $queuedCount,
            'skipped' => $skippedCount,
            'message' => $msg,
        ]);
    }

    /**
     * AJAX: Live Preview Email HTML
     */
    public function preview(Request $request): JsonResponse
    {
        $subject    = trim((string)$request->input('subject', 'Pemberitahuan Resmi - PT Media Solusi Network'));
        $rawMessage = (string)$request->input('message', 'Halo pelanggan yang terhormat, ini adalah pesan pemberitahuan.');
        $badgeText  = trim((string)$request->input('badge_text', 'PEMBERITAHUAN RESMI'));
        $actionUrl  = $request->filled('action_url') ? trim($request->input('action_url')) : null;
        $actionText = $request->filled('action_text') ? trim($request->input('action_text')) : null;

        $sampleCustomer = (object) [
            'nama_pelanggan' => 'Ahmad Suhendar',
            'nomor_internet' => '200019',
            'nama_bandwith'  => 'CORPORATE 50 Mbps',
            'email'          => 'pelanggan@example.com',
        ];

        $htmlBody = $this->formatMessageHtml($rawMessage, $sampleCustomer);

        $rendered = view('emails.custom_template', [
            'subject'       => $subject,
            'htmlBody'      => $htmlBody,
            'recipientName' => $sampleCustomer->nama_pelanggan,
            'nomorInternet' => $sampleCustomer->nomor_internet,
            'badgeText'     => $badgeText,
            'actionUrl'     => $actionUrl,
            'actionText'    => $actionText,
        ])->render();

        return response()->json([
            'success' => true,
            'html'    => $rendered,
        ]);
    }

    /**
     * Halaman Riwayat Pengiriman Email
     */
    public function history(Request $request): View
    {
        $this->ensureSchema();

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');

        $query = DB::table('tb_broadcast_email_log')->orderBy('id', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('email_tujuan', 'like', "%{$search}%")
                  ->orWhere('subjek', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all' && in_array($status, ['queued', 'sent', 'failed'])) {
            $query->where('status', $status);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.email_history', compact('logs', 'search', 'status'));
    }

    /**
     * AJAX: Detail Pesan Riwayat Email
     */
    public function getHistoryDetail(Request $request, $id): JsonResponse
    {
        $this->ensureSchema();

        $log = DB::table('tb_broadcast_email_log')->where('id', $id)->first();
        if (!$log) {
            return response()->json(['success' => false, 'message' => 'Log email tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $log,
        ]);
    }

    /**
     * Query Builder Dasar untuk Pelanggan (dengan Email dari m_pelanggan)
     */
    protected function getBaseCustomerQuery()
    {
        if (Schema::hasTable('view_batchjob')) {
            return DB::table('view_batchjob as c')
                ->select(
                    'c.nomor_internet',
                    'c.nama_pelanggan',
                    'c.email',
                    'c.nomor_hp',
                    'c.nama_bandwith',
                    'c.status_user',
                    'c.alamat_pasang'
                )
                ->orderBy('c.nomor_internet', 'desc');
        }

        // Fallback jika view_batchjob belum ada
        $query = DB::table('trx_batchjob_register as c');
        if (Schema::hasTable('m_pelanggan')) {
            $query->leftJoin('m_pelanggan as p', 'c.nik_penduduk', '=', 'p.nik_penduduk');
        }
        if (Schema::hasTable('m_bandwith')) {
            $query->leftJoin('m_bandwith as b', 'c.kode_bandwith', '=', 'b.kode_bandwith');
        }

        return $query->select(
            'c.nomor_internet',
            DB::raw('COALESCE(c.nama_pelanggan, p.nama_penduduk, "Pelanggan") as nama_pelanggan'),
            'p.email',
            'p.nomor_hp',
            'b.nama_bandwith',
            'c.status_user',
            'c.alamat_pasang'
        )->orderBy('c.nomor_internet', 'desc');
    }

    /**
     * Format teks pesan ke HTML dan gantikan placeholder variabel
     */
    protected function formatMessageHtml(string $text, object $cust): string
    {
        $replacements = [
            '{nama_pelanggan}' => e($cust->nama_pelanggan ?? 'Pelanggan'),
            '{nomor_internet}' => e($cust->nomor_internet ?? '-'),
            '{paket}'          => e($cust->nama_bandwith ?? 'Internet Fiber'),
            '{email}'          => e($cust->email ?? '-'),
            '{portal_url}'     => config('company.portal_url', 'https://portal.ptmsn.co.id'),
            '{perusahaan}'     => config('company.name', 'PT Media Solusi Network'),
        ];

        $replaced = strtr($text, $replacements);

        // Jika teks sudah berisi tag HTML dasar (seperti <p> atau <br>), jangan di-nl2br ganda
        if (str_contains($replaced, '<p>') || str_contains($replaced, '<br>')) {
            return $replaced;
        }

        // Convert baris baru ke paragraf HTML rapi
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $replaced)));
        if (empty($paragraphs)) {
            return '<p>' . nl2br(e($replaced)) . '</p>';
        }

        $html = '';
        foreach ($paragraphs as $para) {
            $html .= '<p>' . nl2br(e($para)) . '</p>';
        }

        return $html;
    }

    /**
     * Hitung KPI untuk Header Card
     */
    protected function calculateKpi(): array
    {
        $totalCustomers = 0;
        $withEmail = 0;
        $totalSent = 0;

        try {
            if (Schema::hasTable('view_batchjob')) {
                $totalCustomers = DB::table('view_batchjob')->count();
                $withEmail = DB::table('view_batchjob')
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->where('email', 'like', '%@%')
                    ->count();
            } elseif (Schema::hasTable('trx_batchjob_register')) {
                $totalCustomers = DB::table('trx_batchjob_register')->count();
                if (Schema::hasTable('m_pelanggan')) {
                    $withEmail = DB::table('m_pelanggan')
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->where('email', 'like', '%@%')
                        ->count();
                }
            }

            if (Schema::hasTable('tb_broadcast_email_log')) {
                $totalSent = DB::table('tb_broadcast_email_log')
                    ->where('status', 'sent')
                    ->count();
            }
        } catch (\Throwable $e) {}

        return [
            'total_customers' => $totalCustomers,
            'with_email'      => $withEmail,
            'without_email'   => max(0, $totalCustomers - $withEmail),
            'total_sent'      => $totalSent,
        ];
    }

    /**
     * Daftar Preset Template Siap Pakai
     */
    protected function getEmailPresets(): array
    {
        return [
            [
                'id'         => 'maintenance',
                'name'       => 'Pemberitahuan Pemeliharaan Jaringan (Maintenance)',
                'badge'      => 'PEMELIHARAAN JARINGAN',
                'subject'    => 'Pemberitahuan Pemeliharaan Jaringan Berkala - PT Media Solusi Network',
                'body'       => "Kepada Pelanggan Yang Terhormat,\n\nDalam rangka meningkatkan kualitas dan keandalan layanan internet, kami akan melakukan pemeliharaan jaringan berkala (scheduled maintenance).\n\nSelama proses ini berlangsung, kemungkinan akan terjadi penurunan kualitas atau pemutusan koneksi internet sesaat. Kami memohon maaf yang sebesar-besarnya atas ketidaknyamanan yang ditimbulkan.\n\nTerima kasih atas pengertian dan kerja sama Anda.",
                'action_url' => config('company.portal_url', 'https://portal.ptmsn.co.id'),
                'action_text'=> 'Cek Status Jaringan di Portal',
            ],
            [
                'id'         => 'gangguan',
                'name'       => 'Informasi Gangguan Jaringan (Incident)',
                'badge'      => 'GANGGUAN JARINGAN',
                'subject'    => 'Informasi Penanganan Gangguan Jaringan - PT Media Solusi Network',
                'body'       => "Kepada Pelanggan Yang Terhormat,\n\nKami menginformasikan bahwa saat ini sedang terjadi kendala teknis pada jalur utama jaringan internet di wilayah Anda, yang berdampak pada koneksi internet #{nomor_internet}.\n\nTim teknis kami sedang berada di lokasi untuk melakukan penanganan dan perbaikan sesegera mungkin. Estimasi perbaikan akan kami informasikan lebih lanjut.\n\nMohon maaf atas ketidaknyamanan yang terjadi.",
                'action_url' => 'https://wa.me/' . config('company.billing_whatsapp', '6285188358385'),
                'action_text'=> 'Hubungi WhatsApp Support',
            ],
            [
                'id'         => 'portal_feature',
                'name'       => 'Pemberitahuan Fitur Baru Portal Pelanggan',
                'badge'      => 'FITUR TERBARU',
                'subject'    => 'Kini Lebih Mudah Kelola Tagihan & Tiket di MyMSN Portal',
                'body'       => "Halo {nama_pelanggan},\n\nKami dengan senang hati memperkenalkan fitur terbaru pada Portal Pelanggan MyMSN! Kini Anda dapat mengecek status tagihan bulanan, melihat riwayat pembayaran, mendownload invoice resmi, serta mengajukan tiket bantuan kendala dengan lebih cepat dan mudah langsung dari gadget Anda.\n\nSilakan login ke portal menggunakan nomor internet #{nomor_internet} Anda.",
                'action_url' => config('company.portal_url', 'https://portal.ptmsn.co.id'),
                'action_text'=> 'Buka MyMSN Portal Sekarang',
            ],
            [
                'id'         => 'promo',
                'name'       => 'Penawaran Khusus / Promo Upgrade Kecepatan',
                'badge'      => 'PENAWARAN SPESIAL',
                'subject'    => 'Penawaran Spesial Upgrade Kecepatan Internet untuk Anda!',
                'body'       => "Halo {nama_pelanggan},\n\nTerima kasih telah setia menggunakan layanan internet {paket} dari PT Media Solusi Network.\n\nSebagai bentuk apresiasi kami, nikmati penawaran khusus upgrade kecepatan internet dengan harga spesial untuk mendukung kebutuhan streaming, kerja, dan aktivitas keluarga tanpa hambatan.\n\nHubungi tim customer service kami untuk informasi detail promo ini.",
                'action_url' => 'https://wa.me/' . config('company.billing_whatsapp', '6285188358385'),
                'action_text'=> 'Klaim Promo via WhatsApp',
            ],
            [
                'id'         => 'custom',
                'name'       => 'Pesan Bebas / Custom',
                'badge'      => 'PEMBERITAHUAN RESMI',
                'subject'    => 'Pemberitahuan Resmi - PT Media Solusi Network',
                'body'       => "Kepada Yth. {nama_pelanggan},\n\n(Tulis pesan pengumuman atau informasi Anda di sini...)\n\nTerima kasih atas perhatian dan kerja sama Anda.",
                'action_url' => config('company.portal_url', 'https://portal.ptmsn.co.id'),
                'action_text'=> 'Kunjungi Portal Pelanggan',
            ],
        ];
    }
}
