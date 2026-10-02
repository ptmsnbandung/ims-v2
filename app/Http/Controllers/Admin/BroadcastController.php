<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    /**
     * Ensure Broadcast WA Tables exist (Auto-migration fallback for maximum reliability)
     */
    protected function ensureSchema(): void
    {
        try {
            if (!Schema::hasTable('tb_broadcast_wa_template')) {
                Schema::create('tb_broadcast_wa_template', function ($table) {
                    $table->id();
                    $table->string('nama_template', 150);
                    $table->string('subjek', 255)->nullable();
                    $table->string('kategori', 50)->default('custom');
                    $table->text('pesan');
                    $table->tinyInteger('is_default')->default(0);
                    $table->timestamps();
                });

                DB::table('tb_broadcast_wa_template')->insert([
                    [
                        'nama_template' => 'Peringatan Jatuh Tempo Tagihan',
                        'subjek'        => 'Pengingat Tagihan Internet IMS',
                        'kategori'      => 'jatuh_tempo',
                        'pesan'         => "Halo Pelanggan Yth. *{nama}*,\n\nKami menginformasikan bahwa tagihan layanan internet IMS Anda untuk periode *{periode}* sejumlah *{nominal}* akan memasuki jatuh tempo pada *{jatuh_tempo}*.\n\nNomor Internet: *{nomor_internet}*\nPaket: *{paket}*\n\nSilakan melakukan pembayaran melalui link resmi berikut:\n{link_pembayaran}\n\nAbaikan pesan ini jika Anda sudah melakukan pembayaran.\nTerima kasih atas kepercayaan Anda menggunakan layanan IMS.",
                        'is_default'    => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ],
                    [
                        'nama_template' => 'Pengumuman Pemeliharaan Jaringan',
                        'subjek'        => 'Maintenance Network IMS',
                        'kategori'      => 'pengumuman',
                        'pesan'         => "Pemberitahuan Pemeliharaan Jaringan IMS 🔧\n\nKepada Pelanggan Yth. *{nama}*,\n\nDisampaikan bahwa akan dilakukan perbaikan/pemeliharaan jaringan internet di area *{alamat}* pada tanggal *{jatuh_tempo}*.\n\nSelama proses pemeliharaan berlangsung, akses internet mungkin mengalami penyesuaian atau disrupsi sementara. Tim teknis kami akan bekerja secepat mungkin agar layanan kembali optimal.\n\nMohon maaf atas ketidaknyamanan ini. Terima kasih atas pengertian Anda.",
                        'is_default'    => 0,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ],
                    [
                        'nama_template' => 'Peringatan Isolir Layanan (Tunggakan)',
                        'subjek'        => 'Peringatan Isolir Internet IMS',
                        'kategori'      => 'jatuh_tempo',
                        'pesan'         => "Pemberitahuan Layanan Internet IMS ⚠️\n\nHalo *{nama}* (ID: *{nomor_internet}*),\n\nDiberitahukan bahwa tagihan internet Anda periode *{periode}* sebesar *{nominal}* telah melewati tanggal jatuh tempo (*{jatuh_tempo}*).\n\nUntuk menghindari pembatasan/isolir layanan secara otomatis, mohon segera melakukan pelunasan tagihan melalui link berikut:\n{link_pembayaran}\n\nBila ada kendala pembayaran, silakan hubungi tim Support IMS. Terima kasih.",
                        'is_default'    => 0,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ],
                    [
                        'nama_template' => 'Pengumuman Informasi Umum / Custom',
                        'subjek'        => 'Pengumuman IMS',
                        'kategori'      => 'custom',
                        'pesan'         => "Halo *{nama}*,\n\n[Tuliskan pesan pengumuman atau informasi khusus di sini]\n\nTerima kasih,\nIMS Management",
                        'is_default'    => 0,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ],
                ]);
            }

            if (!Schema::hasTable('tb_broadcast_wa_log')) {
                Schema::create('tb_broadcast_wa_log', function ($table) {
                    $table->id();
                    $table->string('kode_broadcast', 50)->index();
                    $table->string('jenis', 20)->default('single');
                    $table->string('kode_pengguna', 50)->nullable();
                    $table->string('nama_pengirim', 100)->nullable();
                    $table->string('nomor_internet', 50)->nullable()->index();
                    $table->string('nama_penerima', 150)->nullable();
                    $table->string('nomor_hp', 30)->nullable();
                    $table->text('pesan_terkirim');
                    $table->string('kategori', 50)->default('custom');
                    $table->string('status_kirim', 30)->default('sent');
                    $table->string('metode_kirim', 30)->default('wa_web');
                    $table->timestamps();
                });
            }
        } catch (\Exception $e) {
            Log::error('Broadcast WA Schema initialization error: ' . $e->getMessage());
        }
    }

    /**
     * Format Clean WhatsApp Phone Number (e.g. 0812... -> 62812...)
     */
    protected function formatWaPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }

    /**
     * Dynamically select customer fields and determine safe order column to prevent SQL 1054 Unknown column errors
     */
    protected function selectCustomerFields($query, string $baseTable): string
    {
        $cols = Schema::getColumnListing($baseTable);
        $firstCol = !empty($cols) ? $cols[0] : 'nomor_internet';
        $fallbackSort = in_array('nomor_internet', $cols) ? "c.nomor_internet" : (in_array('id', $cols) ? "c.id" : "c.{$firstCol}");

        $hasNameCol    = in_array('nama_pelanggan', $cols);
        $hasNamePCol   = in_array('nama_p', $cols);
        $hasNameSimple = in_array('nama', $cols);

        // Name column resolution
        if ($hasNameCol) {
            $nameCol = "c.nama_pelanggan";
            $sortField = "c.nama_pelanggan";
        } elseif ($hasNamePCol) {
            $nameCol = "c.nama_p";
            $sortField = "c.nama_p";
        } elseif ($hasNameSimple) {
            $nameCol = "c.nama";
            $sortField = "c.nama";
        } elseif (Schema::hasTable('m_pelanggan') && Schema::hasColumn('m_pelanggan', 'nama_pelanggan')) {
            $nameCol = "COALESCE(mp.nama_pelanggan, mp.nama_p, c.nomor_internet)";
            $sortField = $fallbackSort;
        } elseif (Schema::hasTable('trx_batchjob_register') && Schema::hasColumn('trx_batchjob_register', 'nama_pelanggan')) {
            $nameCol = "COALESCE(reg.nama_pelanggan, c.nomor_internet)";
            $sortField = $fallbackSort;
        } else {
            $nameCol = "c.nomor_internet";
            $sortField = $fallbackSort;
        }

        // Phone column resolution
        $phoneCol = "''";
        if (in_array('nomor_hp', $cols)) {
            $phoneCol = "c.nomor_hp";
        } elseif (in_array('hp_pelanggan', $cols)) {
            $phoneCol = "c.hp_pelanggan";
        } elseif (in_array('hp_p', $cols)) {
            $phoneCol = "c.hp_p";
        } elseif (in_array('hp', $cols)) {
            $phoneCol = "c.hp";
        } elseif (Schema::hasTable('m_pelanggan') && Schema::hasColumn('m_pelanggan', 'nomor_hp')) {
            $phoneCol = "COALESCE(mp.nomor_hp, mp.hp_p, '')";
        } elseif (Schema::hasTable('trx_batchjob_register') && Schema::hasColumn('trx_batchjob_register', 'nomor_hp')) {
            $phoneCol = "COALESCE(reg.nomor_hp, '')";
        }

        // No internet column resolution
        $noCol = in_array('nomor_internet', $cols) ? "c.nomor_internet" : (in_array('id_pelanggan', $cols) ? "c.id_pelanggan" : (in_array('id', $cols) ? "c.id" : "''"));

        // Address resolution
        $alamatCol = in_array('alamat_pasang', $cols) ? "c.alamat_pasang" : (in_array('alamat_p', $cols) ? "c.alamat_p" : (in_array('alamat', $cols) ? "c.alamat" : "''"));

        // City resolution
        $kotaCol = in_array('nama_kota_pasang', $cols) ? "c.nama_kota_pasang" : (in_array('kota_pasang', $cols) ? "c.kota_pasang" : "''");

        // Bandwidth resolution
        $paketCol = in_array('nama_kategori_bandwith', $cols) ? "c.nama_kategori_bandwith" : (in_array('nama_bandwith', $cols) ? "c.nama_bandwith" : "''");

        // Status reg resolution
        $statusRegCol = in_array('status_reg', $cols) ? "c.status_reg" : "''";

        // Invoice fields (from inv if joined or c if base is billing table)
        $hasInv = Schema::hasTable('trx_billing_layanan');
        $kodeBillCol = in_array('kode_billing_layanan', $cols) ? "c.kode_billing_layanan" : ($hasInv ? "COALESCE(inv.kode_billing_layanan, '')" : "''");
        $periodeCol  = in_array('periode_tagihan', $cols) ? "c.periode_tagihan" : ($hasInv ? "COALESCE(inv.periode_tagihan, '')" : "''");
        $bulanCol    = in_array('bulan_tagihan', $cols) ? "c.bulan_tagihan" : ($hasInv ? "COALESCE(inv.bulan_tagihan, '')" : "''");
        $tahunCol    = in_array('tahun_tagihan', $cols) ? "c.tahun_tagihan" : ($hasInv ? "COALESCE(inv.tahun_tagihan, '')" : "''");
        $totalCol    = in_array('total_layanan', $cols) ? "c.total_layanan" : (in_array('harga_bandwith', $cols) ? "c.harga_bandwith" : ($hasInv ? "COALESCE(inv.total_layanan, inv.harga_bandwith, 0)" : "0"));
        $statusBillCol = in_array('status_bill_lay', $cols) ? "c.status_bill_lay" : ($hasInv ? "COALESCE(inv.status_bill_lay, '')" : "''");
        $expiryCol   = in_array('expiry', $cols) ? "c.expiry" : ($hasInv ? "COALESCE(inv.expiry, '')" : "''");
        $snapCol     = in_array('payment_respond_post', $cols) ? "c.payment_respond_post" : ($hasInv ? "COALESCE(inv.payment_respond_post, '')" : "''");

        $query->select(
            DB::raw("{$noCol} as nomor_internet"),
            DB::raw("{$nameCol} as nama_pelanggan"),
            DB::raw("{$phoneCol} as nomor_hp"),
            DB::raw("{$alamatCol} as alamat_pasang"),
            DB::raw("{$alamatCol} as alamat_p"),
            DB::raw("{$kotaCol} as nama_kota_pasang"),
            DB::raw("{$paketCol} as nama_kategori_bandwith"),
            DB::raw("{$statusRegCol} as status_reg"),
            DB::raw("{$kodeBillCol} as kode_billing_layanan"),
            DB::raw("{$periodeCol} as periode_tagihan"),
            DB::raw("{$bulanCol} as bulan_tagihan"),
            DB::raw("{$tahunCol} as tahun_tagihan"),
            DB::raw("{$totalCol} as total_layanan"),
            DB::raw("{$statusBillCol} as status_bill_lay"),
            DB::raw("{$expiryCol} as expiry"),
            DB::raw("{$snapCol} as payment_respond_post")
        );

        return $sortField;
    }

    /**
     * Build base customer query dynamically according to available tables & columns
     */
    protected function buildCustomerQuery(string &$baseTable, array &$cols): \Illuminate\Database\Query\Builder
    {
        $hasViewBatchjob  = Schema::hasTable('view_batchjob');
        $hasViewBilling   = Schema::hasTable('view_billing_layanan');
        $hasTrxBatchReg   = Schema::hasTable('trx_batchjob_register');
        $hasMPelanggan    = Schema::hasTable('m_pelanggan');
        $hasTbPendaftaran = Schema::hasTable('tb_pendaftaran');
        $hasTrxBilling    = Schema::hasTable('trx_billing_layanan');

        if ($hasViewBatchjob) {
            $baseTable = 'view_batchjob';
            $query = DB::table('view_batchjob as c');
        } elseif ($hasViewBilling) {
            $baseTable = 'view_billing_layanan';
            $query = DB::table('view_billing_layanan as c');
        } elseif ($hasTrxBatchReg) {
            $baseTable = 'trx_batchjob_register';
            $query = DB::table('trx_batchjob_register as c');
            if ($hasMPelanggan && Schema::hasColumn('trx_batchjob_register', 'nik_penduduk')) {
                $query->leftJoin('m_pelanggan as mp', 'c.nik_penduduk', '=', 'mp.nik_penduduk');
            }
        } elseif ($hasTbPendaftaran) {
            $baseTable = 'tb_pendaftaran';
            $query = DB::table('tb_pendaftaran as c');
        } elseif ($hasTrxBilling) {
            $baseTable = 'trx_billing_layanan';
            $query = DB::table('trx_billing_layanan as c');
            if ($hasTrxBatchReg) {
                $query->leftJoin('trx_batchjob_register as reg', 'c.nomor_internet', '=', 'reg.nomor_internet');
            }
            if ($hasMPelanggan && $hasTrxBatchReg && Schema::hasColumn('trx_batchjob_register', 'nik_penduduk')) {
                $query->leftJoin('m_pelanggan as mp', 'reg.nik_penduduk', '=', 'mp.nik_penduduk');
            }
        } else {
            $baseTable = 'tb_pengguna';
            $query = DB::table('tb_pengguna as c');
        }

        $cols = Schema::getColumnListing($baseTable);

        // Join latest invoice if using customer table and invoice table exists
        if ($hasTrxBilling && !$hasViewBilling && $baseTable !== 'trx_billing_layanan') {
            if (Schema::hasColumn('trx_billing_layanan', 'kode_billing_layanan')) {
                $invPk = 'kode_billing_layanan';
            } elseif (Schema::hasColumn('trx_billing_layanan', 'date_create')) {
                $invPk = 'date_create';
            } elseif (Schema::hasColumn('trx_billing_layanan', 'created_at')) {
                $invPk = 'created_at';
            } elseif (Schema::hasColumn('trx_billing_layanan', 'id')) {
                $invPk = 'id';
            } else {
                $invPk = 'nomor_internet';
            }

            $latestSub = DB::table('trx_billing_layanan')
                ->select('nomor_internet', DB::raw("MAX({$invPk}) as max_key"))
                ->groupBy('nomor_internet');

            $query->leftJoinSub($latestSub, 'sub_inv', function ($join) {
                $join->on('c.nomor_internet', '=', 'sub_inv.nomor_internet');
            })->leftJoin('trx_billing_layanan as inv', function ($join) use ($invPk) {
                $join->on('sub_inv.max_key', '=', "inv.{$invPk}");
            });
        }

        return $query;
    }

    /**
     * Render Message Template Placeholders
     */
    public function renderTemplateMessage(string $template, object $data): string
    {
        $nama = $data->nama_pelanggan ?? 'Pelanggan';
        $noInternet = $data->nomor_internet ?? '-';
        $periode = !empty($data->periode_tagihan) ? $data->periode_tagihan : (!empty($data->bulan_tagihan) ? $data->bulan_tagihan . '/' . $data->tahun_tagihan : date('m/Y'));
        
        $nominalVal = $data->total_layanan ?? ($data->harga_bandwith ?? ($data->harga ?? 0));
        $nominal = 'Rp ' . number_format((float) $nominalVal, 0, ',', '.');
        
        $expiryRaw = !empty($data->expiry) ? $data->expiry : (!empty($data->tgl_jatuh_tempo) ? $data->tgl_jatuh_tempo : null);
        $jatuhTempo = $expiryRaw ? Carbon::parse($expiryRaw)->translatedFormat('d F Y') : date('d F Y', strtotime('+5 days'));

        $linkPembayaran = '';
        if (!empty($data->payment_respond_post)) {
            $snapData = json_decode($data->payment_respond_post, true);
            $linkPembayaran = $snapData['redirect_url'] ?? '';
        }
        if (empty($linkPembayaran)) {
            $linkPembayaran = config('app.url') . '/finance/billing-layanan';
        }

        $paket = $data->nama_kategori_bandwith ?? ($data->nama_bandwith ?? 'Internet Fiber');
        $alamat = $data->alamat_pasang ?? ($data->alamat_p ?? ($data->nama_kota_pasang ?? 'Area IMS'));

        $replacements = [
            '{nama}'            => $nama,
            '{nomor_internet}'  => $noInternet,
            '{periode}'         => $periode,
            '{nominal}'         => $nominal,
            '{jatuh_tempo}'     => $jatuhTempo,
            '{link_pembayaran}'  => $linkPembayaran,
            '{paket}'           => $paket,
            '{alamat}'          => $alamat,
        ];

        return strtr($template, $replacements);
    }

    /**
     * Halaman Utama Broadcast WA (Direktur & Admin)
     */
    public function index(Request $request): View
    {
        $this->ensureSchema();

        $search = trim($request->input('search', ''));
        $selectedStatusTagihan = $request->input('status_tagihan', 'all');
        $selectedBulan = $request->input('bulan', 'all');
        $selectedTahun = $request->input('tahun', 'all');
        $selectedWilayah = $request->input('wilayah', 'all');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100, 250])) {
            $perPage = 10;
        }

        $baseTable = '';
        $cols = [];
        $query = $this->buildCustomerQuery($baseTable, $cols);

        $sortField = $this->selectCustomerFields($query, $baseTable);

        // Apply Month & Year Filter
        if ($selectedBulan !== 'all' && !empty($selectedBulan)) {
            $query->where(function($q) use ($selectedBulan, $baseTable, $cols) {
                $col = ($baseTable === 'trx_billing_layanan' || $baseTable === 'view_billing_layanan' || in_array('bulan_tagihan', $cols)) ? 'c.bulan_tagihan' : 'inv.bulan_tagihan';
                $q->where($col, str_pad($selectedBulan, 2, '0', STR_PAD_LEFT))
                  ->orWhereNull($col);
            });
        }
        if ($selectedTahun !== 'all' && !empty($selectedTahun)) {
            $query->where(function($q) use ($selectedTahun, $baseTable, $cols) {
                $col = ($baseTable === 'trx_billing_layanan' || $baseTable === 'view_billing_layanan' || in_array('tahun_tagihan', $cols)) ? 'c.tahun_tagihan' : 'inv.tahun_tagihan';
                $q->where($col, $selectedTahun)
                  ->orWhereNull($col);
            });
        }

        // Apply Status Tagihan Filter
        if ($selectedStatusTagihan === 'unpaid' || $selectedStatusTagihan === 'near_due') {
            $col = ($baseTable === 'trx_billing_layanan' || $baseTable === 'view_billing_layanan' || in_array('status_bill_lay', $cols)) ? 'c.status_bill_lay' : 'inv.status_bill_lay';
            $query->whereIn($col, ['13', '14']);
        } elseif ($selectedStatusTagihan === 'paid') {
            $col = ($baseTable === 'trx_billing_layanan' || $baseTable === 'view_billing_layanan' || in_array('status_bill_lay', $cols)) ? 'c.status_bill_lay' : 'inv.status_bill_lay';
            $query->where($col, '15');
        } elseif ($selectedStatusTagihan === 'isolir') {
            if (in_array('status_reg', $cols)) {
                $query->whereIn('c.status_reg', ['23', '23.1']);
            }
        }

        // Apply Wilayah Filter
        if ($selectedWilayah !== 'all' && !empty($selectedWilayah)) {
            if (in_array('nama_kota_pasang', $cols)) {
                $query->where('c.nama_kota_pasang', $selectedWilayah);
            }
        }

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search, $cols) {
                if (in_array('nama_pelanggan', $cols)) {
                    $q->where('c.nama_pelanggan', 'like', "%{$search}%");
                } elseif (in_array('nama_p', $cols)) {
                    $q->where('c.nama_p', 'like', "%{$search}%");
                } elseif (in_array('nama', $cols)) {
                    $q->where('c.nama', 'like', "%{$search}%");
                }

                if (in_array('nomor_internet', $cols)) {
                    $q->orWhere('c.nomor_internet', 'like', "%{$search}%");
                }
                if (in_array('nomor_hp', $cols)) {
                    $q->orWhere('c.nomor_hp', 'like', "%{$search}%");
                }
            });
        }

        $pelangganList = $query->orderBy($sortField, 'asc')
            ->paginate($perPage)
            ->withQueryString();

        // Process WA URL & Formatted HP for each row
        foreach ($pelangganList as $p) {
            $p->clean_hp = $this->formatWaPhone($p->nomor_hp ?? '');
            $p->is_due_soon = in_array($p->status_bill_lay ?? '', ['13', '14']);
        }

        // Templates List
        $templates = DB::table('tb_broadcast_wa_template')->orderBy('is_default', 'desc')->orderBy('nama_template', 'asc')->get();
        $defaultTemplate = $templates->where('is_default', 1)->first() ?? $templates->first();

        // Counter Statistics
        $totalTargetCount = $pelangganList->total();
        
        $hasViewBilling   = Schema::hasTable('view_billing_layanan');
        $hasViewBatchjob  = Schema::hasTable('view_batchjob');
        $hasTrxBilling    = Schema::hasTable('trx_billing_layanan');
        $unpaidTable = $hasViewBilling ? 'view_billing_layanan' : ($hasTrxBilling ? 'trx_billing_layanan' : ($hasViewBatchjob ? 'view_batchjob' : 'trx_batchjob_register'));
        $unpaidCols = Schema::getColumnListing($unpaidTable);
        
        $unpaidQuery = DB::table($unpaidTable);
        if (in_array('status_bill_lay', $unpaidCols)) {
            $unpaidQuery->whereIn('status_bill_lay', ['13', '14']);
        } elseif (in_array('status_reg', $unpaidCols)) {
            $unpaidQuery->whereIn('status_reg', ['23', '23.1']);
        }
        if ($selectedBulan !== 'all' && in_array('bulan_tagihan', $unpaidCols)) {
            $unpaidQuery->where('bulan_tagihan', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT));
        }
        if ($selectedTahun !== 'all' && in_array('tahun_tagihan', $unpaidCols)) {
            $unpaidQuery->where('tahun_tagihan', $selectedTahun);
        }
        $totalUnpaidCount = $unpaidQuery->count();

        $totalSentLog = Schema::hasTable('tb_broadcast_wa_log') ? DB::table('tb_broadcast_wa_log')->count() : 0;
        $sentTodayCount = Schema::hasTable('tb_broadcast_wa_log')
            ? DB::table('tb_broadcast_wa_log')->whereDate('created_at', Carbon::today())->count()
            : 0;

        // Wilayah Dropdown List
        $wilayahList = [];
        if ($hasViewBatchjob) {
            $wilayahList = DB::table('view_batchjob')
                ->whereNotNull('nama_kota_pasang')
                ->where('nama_kota_pasang', '!=', '')
                ->distinct()
                ->pluck('nama_kota_pasang')
                ->toArray();
        } elseif ($hasViewBilling && Schema::hasColumn('view_billing_layanan', 'nama_kota_pasang')) {
            $wilayahList = DB::table('view_billing_layanan')
                ->whereNotNull('nama_kota_pasang')
                ->where('nama_kota_pasang', '!=', '')
                ->distinct()
                ->pluck('nama_kota_pasang')
                ->toArray();
        }

        return view('admin.broadcast.index', compact(
            'pelangganList',
            'templates',
            'defaultTemplate',
            'totalTargetCount',
            'totalUnpaidCount',
            'totalSentLog',
            'sentTodayCount',
            'selectedStatusTagihan',
            'selectedBulan',
            'selectedTahun',
            'selectedWilayah',
            'wilayahList',
            'search',
            'perPage'
        ));
    }

    /**
     * AJAX Preview Broadcast Message per Customer
     */
    public function preview(Request $request): JsonResponse
    {
        $this->ensureSchema();

        $nomorInternet = $request->input('nomor_internet');
        $rawMessage = $request->input('pesan', '');
        $templateId = $request->input('template_id');

        if (!empty($templateId) && empty($rawMessage)) {
            $tpl = DB::table('tb_broadcast_wa_template')->where('id', $templateId)->first();
            if ($tpl) {
                $rawMessage = $tpl->pesan;
            }
        }

        $baseTable = '';
        $cols = [];
        $query = $this->buildCustomerQuery($baseTable, $cols);
        $query->where('c.nomor_internet', $nomorInternet);

        $this->selectCustomerFields($query, $baseTable);
        $data = $query->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan tidak ditemukan'
            ], 404);
        }

        $renderedMessage = $this->renderTemplateMessage($rawMessage, $data);
        $cleanHp = $this->formatWaPhone($data->nomor_hp ?? '');
        $waUrl = !empty($cleanHp) ? 'https://wa.me/' . $cleanHp . '?text=' . urlencode($renderedMessage) : '';

        return response()->json([
            'success'          => true,
            'nama_pelanggan'   => $data->nama_pelanggan ?? 'Pelanggan',
            'nomor_internet'   => $data->nomor_internet ?? '-',
            'nomor_hp'         => $data->nomor_hp ?? '-',
            'clean_hp'         => $cleanHp,
            'rendered_message' => $renderedMessage,
            'wa_url'           => $waUrl,
        ]);
    }

    /**
     * Send Single WhatsApp Broadcast (Catat Log & Direct Open WA)
     */
    public function sendSingle(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureSchema();

        $request->validate([
            'nomor_hp'        => 'required|string',
            'nama_penerima'   => 'required|string',
            'pesan'           => 'required|string',
            'nomor_internet'  => 'nullable|string',
            'kategori'        => 'nullable|string',
        ]);

        $user = Auth::user();
        $senderName = $user->nama_karyawan ?? ($user->username ?? 'Direktur');
        $cleanHp = $this->formatWaPhone($request->input('nomor_hp'));

        if (empty($cleanHp)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nomor HP WhatsApp tidak valid atau kosong!'], 422);
            }
            return redirect()->back()->with('error', 'Nomor HP WhatsApp tidak valid atau kosong!');
        }

        $kodeBroadcast = 'BC-SGL-' . date('YmdHis') . '-' . rand(100, 999);

        DB::table('tb_broadcast_wa_log')->insert([
            'kode_broadcast'  => $kodeBroadcast,
            'jenis'           => 'single',
            'kode_pengguna'   => $user->kode_pengguna ?? null,
            'nama_pengirim'   => $senderName,
            'nomor_internet'  => $request->input('nomor_internet'),
            'nama_penerima'   => $request->input('nama_penerima'),
            'nomor_hp'        => $cleanHp,
            'pesan_terkirim'  => $request->input('pesan'),
            'kategori'        => $request->input('kategori', 'jatuh_tempo'),
            'status_kirim'    => 'sent',
            'metode_kirim'    => 'wa_web',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $waUrl = 'https://wa.me/' . $cleanHp . '?text=' . urlencode($request->input('pesan'));

        if ($request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Broadcast berhasil disiapkan & dicatat di log.',
                'wa_url'         => $waUrl,
                'kode_broadcast' => $kodeBroadcast,
            ]);
        }

        return redirect()->away($waUrl);
    }

    /**
     * Send Bulk WhatsApp Broadcast (Batch Render & Dispatcher Log)
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $this->ensureSchema();

        $request->validate([
            'targets'   => 'required|array|min:1',
            'pesan'     => 'required|string',
            'kategori'  => 'nullable|string',
        ]);

        $targets = $request->input('targets');
        $rawMessage = $request->input('pesan');
        $kategori = $request->input('kategori', 'jatuh_tempo');

        $user = Auth::user();
        $senderName = $user->nama_karyawan ?? ($user->username ?? 'Direktur');
        $kodeBatch = 'BC-MASSAL-' . date('YmdHis') . '-' . rand(100, 999);

        $baseTable = '';
        $cols = [];
        $query = $this->buildCustomerQuery($baseTable, $cols);
        $query->whereIn('c.nomor_internet', $targets);

        $this->selectCustomerFields($query, $baseTable);
        $customerRecords = $query->get();

        $dispatchQueue = [];
        $logInserts = [];

        foreach ($customerRecords as $cust) {
            $cleanHp = $this->formatWaPhone($cust->nomor_hp ?? '');
            if (empty($cleanHp)) {
                continue;
            }

            $renderedMessage = $this->renderTemplateMessage($rawMessage, $cust);
            $waUrl = 'https://wa.me/' . $cleanHp . '?text=' . urlencode($renderedMessage);

            $dispatchQueue[] = [
                'nomor_internet' => $cust->nomor_internet ?? '-',
                'nama_penerima'  => $cust->nama_pelanggan ?? 'Pelanggan',
                'nomor_hp'       => $cleanHp,
                'pesan'          => $renderedMessage,
                'wa_url'         => $waUrl,
            ];

            $logInserts[] = [
                'kode_broadcast'  => $kodeBatch,
                'jenis'           => 'massal',
                'kode_pengguna'   => $user->kode_pengguna ?? null,
                'nama_pengirim'   => $senderName,
                'nomor_internet'  => $cust->nomor_internet ?? null,
                'nama_penerima'   => $cust->nama_pelanggan ?? 'Pelanggan',
                'nomor_hp'        => $cleanHp,
                'pesan_terkirim'  => $renderedMessage,
                'kategori'        => $kategori,
                'status_kirim'    => 'sent',
                'metode_kirim'    => 'wa_web',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        if (!empty($logInserts)) {
            DB::table('tb_broadcast_wa_log')->insert($logInserts);
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Broadcast massal untuk ' . count($dispatchQueue) . ' pelanggan berhasil dibuat dan dicatat.',
            'kode_broadcast' => $kodeBatch,
            'total_count'    => count($dispatchQueue),
            'queue'          => $dispatchQueue,
        ]);
    }

    /**
     * Save / Update Custom Template
     */
    public function saveTemplate(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureSchema();

        $request->validate([
            'nama_template' => 'required|string|max:150',
            'pesan'         => 'required|string',
            'kategori'      => 'nullable|string',
        ]);

        $templateId = $request->input('id');

        $data = [
            'nama_template' => $request->input('nama_template'),
            'subjek'        => $request->input('subjek', $request->input('nama_template')),
            'kategori'      => $request->input('kategori', 'custom'),
            'pesan'         => $request->input('pesan'),
            'is_default'    => $request->has('is_default') ? 1 : 0,
            'updated_at'    => now(),
        ];

        if ($request->has('is_default') && $request->input('is_default') == 1) {
            DB::table('tb_broadcast_wa_template')->update(['is_default' => 0]);
        }

        if ($templateId) {
            DB::table('tb_broadcast_wa_template')->where('id', $templateId)->update($data);
            $msg = 'Template broadcast berhasil diperbarui.';
        } else {
            $data['created_at'] = now();
            DB::table('tb_broadcast_wa_template')->insert($data);
            $msg = 'Template broadcast baru berhasil disimpan.';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Delete Custom Template
     */
    public function deleteTemplate(int $id): JsonResponse|RedirectResponse
    {
        $this->ensureSchema();

        DB::table('tb_broadcast_wa_template')->where('id', $id)->delete();
        $msg = 'Template broadcast berhasil dihapus.';

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Broadcast Log / History Page
     */
    public function history(Request $request): View
    {
        $this->ensureSchema();

        $search = trim($request->input('search', ''));
        $selectedKategori = $request->input('kategori', 'all');
        $selectedJenis = $request->input('jenis', 'all');
        $perPage = (int) $request->input('per_page', 15);

        $query = DB::table('tb_broadcast_wa_log');

        if ($selectedKategori !== 'all' && !empty($selectedKategori)) {
            $query->where('kategori', $selectedKategori);
        }
        if ($selectedJenis !== 'all' && !empty($selectedJenis)) {
            $query->where('jenis', $selectedJenis);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_penerima', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhere('kode_broadcast', 'like', "%{$search}%")
                  ->orWhere('pesan_terkirim', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.broadcast.history', compact('logs', 'search', 'selectedKategori', 'selectedJenis', 'perPage'));
    }
}
