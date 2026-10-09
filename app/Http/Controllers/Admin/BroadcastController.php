<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MetaWhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    protected MetaWhatsAppService $metaWaService;
    protected static ?array $schemaCache = null;

    public function __construct(MetaWhatsAppService $metaWaService)
    {
        $this->metaWaService = $metaWaService;
    }

    /**
     * Cache schema metadata in memory and Laravel cache for fast remote DB responses
     */
    protected function getSchemaMeta(): array
    {
        if (static::$schemaCache !== null) {
            return static::$schemaCache;
        }

        static::$schemaCache = Cache::remember('ims_broadcast_schema_meta_v4', 3600, function () {
            $tables = [
                'view_batchjob',
                'view_billing_layanan',
                'trx_batchjob_register',
                'm_pelanggan',
                'tb_pendaftaran',
                'trx_billing_layanan',
                'tb_broadcast_wa_template',
                'tb_broadcast_wa_log'
            ];
            $meta = [];
            foreach ($tables as $t) {
                $has = Schema::hasTable($t);
                $meta[$t] = [
                    'exists' => $has,
                    'cols'   => $has ? Schema::getColumnListing($t) : []
                ];
            }
            return $meta;
        });

        return static::$schemaCache;
    }

    protected function hasTableCached(string $table): bool
    {
        $meta = $this->getSchemaMeta();
        return !empty($meta[$table]['exists']);
    }

    protected function getColumnsCached(string $table): array
    {
        $meta = $this->getSchemaMeta();
        return $meta[$table]['cols'] ?? [];
    }

    /**
     * Ensure Broadcast WA Tables exist (Cached to prevent slow DDL on every request)
     */
    protected function ensureSchema(): void
    {
        if (Cache::has('ims_broadcast_schema_ensured_v4')) {
            return;
        }

        try {
            if (!Schema::hasTable('tb_broadcast_wa_template')) {
                Schema::create('tb_broadcast_wa_template', function ($table) {
                    $table->id();
                    $table->string('nama_template', 150);
                    $table->string('meta_template_name', 150)->nullable();
                    $table->string('meta_language', 20)->default('id');
                    $table->text('meta_params_map')->nullable();
                    $table->string('subjek', 255)->nullable();
                    $table->string('kategori', 50)->default('custom');
                    $table->text('pesan');
                    $table->tinyInteger('is_default')->default(0);
                    $table->timestamps();
                });
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
                    $table->string('metode_kirim', 30)->default('meta_api');
                    $table->string('meta_message_id', 150)->nullable();
                    $table->text('meta_error_message')->nullable();
                    $table->timestamps();
                });
            }

            $hasTagihanBulanan = DB::table('tb_broadcast_wa_template')
                ->where('meta_template_name', 'tagihan_bulanan')
                ->exists();

            if (!$hasTagihanBulanan) {
                DB::table('tb_broadcast_wa_template')->insert([
                    'nama_template'      => 'Tagihan Bulanan Resmi (Meta)',
                    'meta_template_name' => 'tagihan_bulanan',
                    'meta_language'      => 'id',
                    'meta_params_map'    => json_encode(['periode', 'bulan_jatuh_tempo', 'bulan_suspend']),
                    'subjek'             => 'Tagihan Bulanan Internet MEDIANET',
                    'kategori'           => 'utility',
                    'pesan'              => "📢* Tagihan Internet Anda Sudah Terbit!*\nHalo, Bapak/Ibu 👋\n\nTagihan internet Anda* SUDAH BISA DIBAYARKAN* untuk periode {periode}.\nJatuh Tempo Pembayaran:* 20 {bulan_jatuh_tempo}*\n⚠️ Apabila sampai dengan 24 {bulan_suspend} belum ada pembayaran, layanan akan kami nonaktifkan sementara (suspend).\n\nPembayaran dapat dilakukan melalui Portal Pelanggan kami. Silakan klik tombol dibawah untuk melakukan pembayaran\n\n🔑 Cara Login:\nSilakan login menggunakan Nomor Telepon atau Nomor Internet yang terdaftar pada layanan MEDIANET Anda.\n\nHiraukan pesan ini apabila sudah melakukan pembayaran",
                    'is_default'         => 1,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            Cache::put('ims_broadcast_schema_ensured_v4', true, 86400);
        } catch (\Exception $e) {
            Log::error('Broadcast WA Schema initialization error: ' . $e->getMessage());
        }
    }

    /**
     * Format Clean WhatsApp Phone Number (e.g. 0812... -> 62812...)
     */
    protected function formatWaPhone(?string $phone): string
    {
        return $this->metaWaService->formatPhone($phone);
    }

    /**
     * Dynamically select customer fields and determine safe order column.
     */
    protected function selectCustomerFields($query, string $baseTable): string
    {
        $cols     = $this->getColumnsCached($baseTable);
        $firstCol = !empty($cols) ? $cols[0] : 'nomor_internet';
        $fallbackSort = in_array('nomor_internet', $cols) ? 'c.nomor_internet'
            : (in_array('id', $cols) ? 'c.id' : "c.{$firstCol}");

        $mpCols  = $this->hasTableCached('m_pelanggan')           ? $this->getColumnsCached('m_pelanggan')           : [];
        $regCols = $this->hasTableCached('trx_batchjob_register') ? $this->getColumnsCached('trx_batchjob_register') : [];
        $invCols = $this->hasTableCached('trx_billing_layanan')   ? $this->getColumnsCached('trx_billing_layanan')   : [];

        // Name column
        if (in_array('nama_pelanggan', $cols)) {
            $nameCol   = "COALESCE(NULLIF(c.nama_pelanggan, ''), NULLIF(c.nama_pelanggan, 'Tanpa Nama'), c.nomor_internet)";
            $sortField = 'c.nama_pelanggan';
        } elseif (in_array('nama_p', $cols)) {
            $nameCol   = "COALESCE(NULLIF(c.nama_p, ''), c.nomor_internet)";
            $sortField = 'c.nama_p';
        } elseif (in_array('nama', $cols)) {
            $nameCol   = "COALESCE(NULLIF(c.nama, ''), c.nomor_internet)";
            $sortField = 'c.nama';
        } elseif (in_array('nama_pelanggan', $mpCols)) {
            $parts = ['mp.nama_pelanggan'];
            if (in_array('nama_p', $mpCols))   $parts[] = 'mp.nama_p';
            if (in_array('nomor_internet', $cols)) $parts[] = 'c.nomor_internet';
            $nameCol   = count($parts) > 1 ? 'COALESCE(' . implode(', ', $parts) . ')' : $parts[0];
            $sortField = $fallbackSort;
        } elseif (in_array('nama_pelanggan', $regCols)) {
            $nameCol   = in_array('nomor_internet', $cols)
                ? "COALESCE(NULLIF(reg.nama_pelanggan, ''), c.nomor_internet)"
                : 'reg.nama_pelanggan';
            $sortField = $fallbackSort;
        } else {
            $nameCol   = in_array('nomor_internet', $cols) ? 'c.nomor_internet' : "''";
            $sortField = $fallbackSort;
        }

        // Phone column
        if (in_array('nomor_hp', $cols)) {
            $phoneCol = 'c.nomor_hp';
        } elseif (in_array('nomor_hp', $mpCols)) {
            $phoneCol = 'mp.nomor_hp';
        } elseif (in_array('nomor_hp', $regCols)) {
            $phoneCol = 'reg.nomor_hp';
        } else {
            $phoneCol = "''";
        }

        // Address
        if (in_array('alamat_pasang', $cols)) {
            $alamatCol = 'c.alamat_pasang';
        } elseif (in_array('alamat_p', $cols)) {
            $alamatCol = 'c.alamat_p';
        } elseif (in_array('alamat', $cols)) {
            $alamatCol = 'c.alamat';
        } elseif (in_array('alamat_pasang', $mpCols)) {
            $alamatCol = 'mp.alamat_pasang';
        } elseif (in_array('alamat_p', $mpCols)) {
            $alamatCol = 'mp.alamat_p';
        } else {
            $alamatCol = "''";
        }

        // City
        if (in_array('nama_kota_pasang', $cols)) {
            $kotaCol = 'c.nama_kota_pasang';
        } elseif (in_array('kota_pasang', $cols)) {
            $kotaCol = 'c.kota_pasang';
        } elseif (in_array('nama_kota_pasang', $mpCols)) {
            $kotaCol = 'mp.nama_kota_pasang';
        } else {
            $kotaCol = "''";
        }

        // Package
        if (in_array('nama_kategori_bandwith', $cols)) {
            $paketCol = 'c.nama_kategori_bandwith';
        } elseif (in_array('nama_bandwith', $cols)) {
            $paketCol = 'c.nama_bandwith';
        } else {
            $paketCol = "''";
        }

        // Status register
        $statusRegCol = in_array('status_reg', $cols) ? 'c.status_reg' : "''";

        // Invoice fields
        $hasInv = !empty($invCols);

        if (in_array('kode_billing_layanan', $cols)) {
            $kodeBillCol = 'c.kode_billing_layanan';
        } elseif ($hasInv && in_array('kode_billing_layanan', $invCols)) {
            $kodeBillCol = "COALESCE(inv.kode_billing_layanan, '')";
        } else {
            $kodeBillCol = "''";
        }

        if (in_array('periode_tagihan', $cols)) {
            $periodeCol = 'c.periode_tagihan';
        } elseif ($hasInv && in_array('periode_tagihan', $invCols)) {
            $periodeCol = "COALESCE(inv.periode_tagihan, '')";
        } else {
            $periodeCol = "''";
        }

        if (in_array('bulan_tagihan', $cols)) {
            $bulanCol = 'c.bulan_tagihan';
        } elseif ($hasInv && in_array('bulan_tagihan', $invCols)) {
            $bulanCol = "COALESCE(inv.bulan_tagihan, '')";
        } else {
            $bulanCol = "''";
        }

        if (in_array('tahun_tagihan', $cols)) {
            $tahunCol = 'c.tahun_tagihan';
        } elseif ($hasInv && in_array('tahun_tagihan', $invCols)) {
            $tahunCol = "COALESCE(inv.tahun_tagihan, '')";
        } else {
            $tahunCol = "''";
        }

        if (in_array('status_bill_lay', $cols)) {
            $statusBillCol = 'c.status_bill_lay';
        } elseif ($hasInv && in_array('status_bill_lay', $invCols)) {
            $statusBillCol = "COALESCE(inv.status_bill_lay, '')";
        } else {
            $statusBillCol = "''";
        }

        if (in_array('total_layanan', $cols)) {
            $nominalCol = 'c.total_layanan';
        } elseif (in_array('harga_bandwith', $cols)) {
            $nominalCol = 'c.harga_bandwith';
        } elseif ($hasInv && in_array('total_layanan', $invCols)) {
            $nominalCol = "COALESCE(inv.total_layanan, 0)";
        } else {
            $nominalCol = '0';
        }

        if (in_array('expiry', $cols)) {
            $expiryCol = 'c.expiry';
        } elseif (in_array('tgl_jatuh_tempo', $cols)) {
            $expiryCol = 'c.tgl_jatuh_tempo';
        } elseif ($hasInv && in_array('expiry', $invCols)) {
            $expiryCol = 'inv.expiry';
        } elseif ($hasInv && in_array('tgl_jatuh_tempo', $invCols)) {
            $expiryCol = 'inv.tgl_jatuh_tempo';
        } else {
            $expiryCol = 'NULL';
        }

        if (in_array('payment_respond_post', $cols)) {
            $snapCol = 'c.payment_respond_post';
        } elseif ($hasInv && in_array('payment_respond_post', $invCols)) {
            $snapCol = 'inv.payment_respond_post';
        } else {
            $snapCol = 'NULL';
        }

        // Last Billing Month & Year
        if (in_array('last_month_billing', $cols)) {
            $lastMonthCol = 'c.last_month_billing';
        } elseif (in_array('last_month_billing', $regCols)) {
            $lastMonthCol = "COALESCE(reg.last_month_billing, '')";
        } else {
            $lastMonthCol = "''";
        }

        if (in_array('last_year_billing', $cols)) {
            $lastYearCol = 'c.last_year_billing';
        } elseif (in_array('last_year_billing', $regCols)) {
            $lastYearCol = "COALESCE(reg.last_year_billing, '')";
        } else {
            $lastYearCol = "''";
        }

        if (in_array('nominal_bandwith', $cols)) {
            $speedCol = 'c.nominal_bandwith';
        } elseif (in_array('nominal_bandwith', $regCols)) {
            $speedCol = "COALESCE(reg.nominal_bandwith, '')";
        } else {
            $speedCol = "''";
        }

        $hasLogTable = Schema::hasTable('tb_broadcast_wa_log');
        $hasInvNotif = $hasInv && in_array('notif_wa', $invCols);

        if ($hasLogTable && $hasInvNotif) {
            $waSentCountCol = "GREATEST(COALESCE(log_sent.total_sent_count, 0), COALESCE(inv.notif_wa, 0))";
        } elseif ($hasLogTable) {
            $waSentCountCol = "COALESCE(log_sent.total_sent_count, 0)";
        } elseif ($hasInvNotif) {
            $waSentCountCol = "COALESCE(inv.notif_wa, 0)";
        } else {
            $waSentCountCol = "0";
        }

        $lastSentAtCol = $hasLogTable ? "log_sent.last_sent_at" : "NULL";

        $selects = [
            in_array('nomor_internet', $cols) ? 'c.nomor_internet' : "'' as nomor_internet",
            "{$nameCol} as nama_pelanggan",
            "{$phoneCol} as nomor_hp",
            "{$alamatCol} as alamat_pasang",
            "{$kotaCol} as nama_kota_pasang",
            "{$paketCol} as nama_kategori_bandwith",
            "{$speedCol} as nominal_bandwith",
            "{$statusRegCol} as status_reg",
            "{$kodeBillCol} as kode_billing_layanan",
            "{$periodeCol} as periode_tagihan",
            "{$bulanCol} as bulan_tagihan",
            "{$tahunCol} as tahun_tagihan",
            "{$statusBillCol} as status_bill_lay",
            "{$nominalCol} as total_layanan",
            "{$expiryCol} as expiry",
            "{$snapCol} as payment_respond_post",
            "{$lastMonthCol} as last_month_billing",
            "{$lastYearCol} as last_year_billing",
            "{$waSentCountCol} as wa_sent_count",
            "{$lastSentAtCol} as last_sent_at",
        ];

        if (in_array('id', $cols)) {
            $selects[] = 'c.id';
        }

        $query->selectRaw(implode(', ', $selects));

        return $sortField;
    }

    /**
     * Build customer base query safely and with high performance.
     */
    protected function buildCustomerQuery(string &$baseTable, array &$cols, ?string $selectedBulan = null, ?string $selectedTahun = null)
    {
        $hasViewBatchjob  = $this->hasTableCached('view_batchjob');
        $hasViewBilling   = $this->hasTableCached('view_billing_layanan');
        $hasTrxBatchReg   = $this->hasTableCached('trx_batchjob_register');
        $hasMPelanggan    = $this->hasTableCached('m_pelanggan');
        $hasTbPendaftaran = $this->hasTableCached('tb_pendaftaran');
        $hasTrxBilling    = $this->hasTableCached('trx_billing_layanan');

        if ($hasViewBatchjob) {
            $baseTable = 'view_batchjob';
            $query = DB::table('view_batchjob as c');
        } elseif ($hasViewBilling) {
            $baseTable = 'view_billing_layanan';
            $query = DB::table('view_billing_layanan as c');
        } elseif ($hasTrxBatchReg) {
            $baseTable = 'trx_batchjob_register';
            $query = DB::table('trx_batchjob_register as c');
            if ($hasMPelanggan && in_array('nik_penduduk', $this->getColumnsCached('trx_batchjob_register'))) {
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
            if ($hasMPelanggan && $hasTrxBatchReg && in_array('nik_penduduk', $this->getColumnsCached('trx_batchjob_register'))) {
                $query->leftJoin('m_pelanggan as mp', 'reg.nik_penduduk', '=', 'mp.nik_penduduk');
            }
        } else {
            $baseTable = 'tb_pengguna';
            $query = DB::table('tb_pengguna as c');
        }

        $cols = $this->getColumnsCached($baseTable);

        // Join Invoice billing
        if ($hasTrxBilling && !$hasViewBilling && $baseTable !== 'trx_billing_layanan') {
            if (!empty($selectedBulan) && $selectedBulan !== 'all' && !empty($selectedTahun) && $selectedTahun !== 'all') {
                // High-performance direct indexed join when month and year are specified
                $query->leftJoin('trx_billing_layanan as inv', function ($join) use ($selectedBulan, $selectedTahun) {
                    $join->on('c.nomor_internet', '=', 'inv.nomor_internet')
                         ->where('inv.bulan_tagihan', '=', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT))
                         ->where('inv.tahun_tagihan', '=', (string)$selectedTahun);
                });
            } else {
                // High performance subquery for latest invoice
                $subLatest = DB::table('trx_billing_layanan as tbl_sub')
                    ->selectRaw("
                        tbl_sub.nomor_internet,
                        COALESCE(
                            MAX(CASE WHEN tbl_sub.status_bill_lay IN ('13', '14') THEN CONCAT(tbl_sub.tahun_tagihan, LPAD(tbl_sub.bulan_tagihan, 2, '0')) END),
                            MAX(CONCAT(tbl_sub.tahun_tagihan, LPAD(tbl_sub.bulan_tagihan, 2, '0')))
                        ) as target_period
                    ")
                    ->groupBy('tbl_sub.nomor_internet');

                $query->leftJoinSub($subLatest, 'sub_inv', function ($join) {
                    $join->on('c.nomor_internet', '=', 'sub_inv.nomor_internet');
                })->leftJoin('trx_billing_layanan as inv', function ($join) {
                    $join->on('c.nomor_internet', '=', 'inv.nomor_internet')
                         ->on(DB::raw("CONCAT(inv.tahun_tagihan, LPAD(inv.bulan_tagihan, 2, '0'))"), '=', 'sub_inv.target_period');
                });
            }
        }

        // Join WA Broadcast sent log count
        if ($this->hasTableCached('tb_broadcast_wa_log')) {
            $subLog = DB::table('tb_broadcast_wa_log as tbl_log')
                ->selectRaw("tbl_log.nomor_internet COLLATE utf8mb4_general_ci as nomor_internet, COUNT(*) as total_sent_count, MAX(tbl_log.created_at) as last_sent_at")
                ->where('tbl_log.status_kirim', 'sent')
                ->whereNotNull('tbl_log.nomor_internet')
                ->where('tbl_log.nomor_internet', '!=', '')
                ->groupBy(DB::raw("tbl_log.nomor_internet COLLATE utf8mb4_general_ci"));

            $query->leftJoinSub($subLog, 'log_sent', function ($join) {
                $join->on('c.nomor_internet', '=', 'log_sent.nomor_internet');
            });
        }

        return $query;
    }

    /**
     * Render Message Template Placeholders for UI Preview & WA Web
     */
    public function renderTemplateMessage(string $template, object $data): string
    {
        $nama = $data->nama_pelanggan ?? 'Pelanggan';
        $noInternet = $data->nomor_internet ?? '-';

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Format bulan & tahun dalam Bahasa Indonesia yang tepat
        if (!empty($data->bulan_tagihan) && !empty($data->tahun_tagihan)) {
            $bNum = (int)$data->bulan_tagihan;
            $namaBulanTahun = ($bulanNames[$bNum] ?? date('F')) . ' ' . $data->tahun_tagihan;
            $periode = $namaBulanTahun;
            $bulanJatuhTempo = $namaBulanTahun;
            $bulanSuspend = $namaBulanTahun;
        } elseif (!empty($data->periode_tagihan)) {
            $enMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $idMonths = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $periode = str_ireplace($enMonths, $idMonths, $data->periode_tagihan);
            $bulanJatuhTempo = $periode;
            $bulanSuspend = $periode;
        } else {
            $curMonth = (int)date('n');
            $curYear = (int)date('Y');
            $namaBulanTahun = ($bulanNames[$curMonth] ?? date('F')) . ' ' . $curYear;
            $periode = $namaBulanTahun;
            $bulanJatuhTempo = $namaBulanTahun;
            $bulanSuspend = $namaBulanTahun;
        }
        
        $nominalVal = $data->total_layanan ?? ($data->harga_bandwith ?? ($data->harga ?? 0));
        $nominal = 'Rp ' . number_format((float) $nominalVal, 0, ',', '.');
        
        $expiryRaw = !empty($data->expiry) ? $data->expiry : (!empty($data->tgl_jatuh_tempo) ? $data->tgl_jatuh_tempo : null);
        $jatuhTempo = $expiryRaw ? Carbon::parse($expiryRaw)->translatedFormat('d F Y') : ('20 ' . $bulanJatuhTempo);

        $linkPembayaran = '';
        if (!empty($data->payment_respond_post)) {
            $snapData = json_decode($data->payment_respond_post, true);
            $linkPembayaran = $snapData['redirect_url'] ?? '';
        }
        if (empty($linkPembayaran)) {
            $linkPembayaran = 'https://ptmsn.co.id/portal/login';
        }

        $paket = $data->nama_kategori_bandwith ?? ($data->nama_bandwith ?? 'Internet Fiber');
        $alamat = $data->alamat_pasang ?? ($data->alamat_p ?? ($data->nama_kota_pasang ?? 'Area IMS'));

        $replacements = [
            '{nama}'              => $nama,
            '{nomor_internet}'    => $noInternet,
            '{periode}'           => $periode,
            '{bulan_jatuh_tempo}' => $bulanJatuhTempo,
            '{bulan_suspend}'     => $bulanSuspend,
            '{nominal}'           => $nominal,
            '{jatuh_tempo}'       => $jatuhTempo,
            '{link_pembayaran}'   => $linkPembayaran,
            '{paket}'             => $paket,
            '{alamat}'            => $alamat,
            '{{1}}'               => $periode,
            '{{2}}'               => $bulanJatuhTempo,
            '{{3}}'               => $bulanSuspend,
        ];

        return strtr($template, $replacements);
    }

    /**
     * Build Meta Body Parameters (Array for {{1}}, {{2}}, {{3}}, ...)
     */
    public function buildMetaParameters(object $data, ?array $paramsMap = null): array
    {
        $nama = $data->nama_pelanggan ?? 'Pelanggan';
        $noInternet = $data->nomor_internet ?? '-';

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if (!empty($data->bulan_tagihan) && !empty($data->tahun_tagihan)) {
            $bNum = (int)$data->bulan_tagihan;
            $namaBulanTahun = ($bulanNames[$bNum] ?? date('F')) . ' ' . $data->tahun_tagihan;
            $periode = $namaBulanTahun;
            $bulanJatuhTempo = $namaBulanTahun;
            $bulanSuspend = $namaBulanTahun;
        } elseif (!empty($data->periode_tagihan)) {
            $enMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $idMonths = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $periode = str_ireplace($enMonths, $idMonths, $data->periode_tagihan);
            $bulanJatuhTempo = $periode;
            $bulanSuspend = $periode;
        } else {
            $curMonth = (int)date('n');
            $curYear = (int)date('Y');
            $namaBulanTahun = ($bulanNames[$curMonth] ?? date('F')) . ' ' . $curYear;
            $periode = $namaBulanTahun;
            $bulanJatuhTempo = $namaBulanTahun;
            $bulanSuspend = $namaBulanTahun;
        }
        
        $nominalVal = $data->total_layanan ?? ($data->harga_bandwith ?? ($data->harga ?? 0));
        $nominal = 'Rp ' . number_format((float) $nominalVal, 0, ',', '.');
        
        $expiryRaw = !empty($data->expiry) ? $data->expiry : (!empty($data->tgl_jatuh_tempo) ? $data->tgl_jatuh_tempo : null);
        $jatuhTempo = $expiryRaw ? Carbon::parse($expiryRaw)->translatedFormat('d F Y') : ('20 ' . $bulanJatuhTempo);

        $linkPembayaran = '';
        if (!empty($data->payment_respond_post)) {
            $snapData = json_decode($data->payment_respond_post, true);
            $linkPembayaran = $snapData['redirect_url'] ?? '';
        }
        if (empty($linkPembayaran)) {
            $linkPembayaran = 'https://ptmsn.co.id/portal/login';
        }

        $paket = $data->nama_kategori_bandwith ?? ($data->nama_bandwith ?? 'Internet Fiber');
        $alamat = $data->alamat_pasang ?? ($data->alamat_p ?? ($data->nama_kota_pasang ?? 'Area IMS'));

        $dict = [
            'nama'              => $nama,
            'nomor_internet'    => $noInternet,
            'periode'           => $periode,
            'bulan_jatuh_tempo' => $bulanJatuhTempo,
            'bulan_suspend'     => $bulanSuspend,
            'nominal'           => $nominal,
            'jatuh_tempo'       => $jatuhTempo,
            'link_pembayaran'   => $linkPembayaran,
            'paket'             => $paket,
            'alamat'            => $alamat,
        ];

        if (empty($paramsMap)) {
            // Default 3-parameter standard for tagihan_bulanan (periode, bulan_jatuh_tempo, bulan_suspend)
            return [$periode, $bulanJatuhTempo, $bulanSuspend];
        }

        $result = [];
        foreach ($paramsMap as $key) {
            $cleanKey = trim(str_replace(['{', '}'], '', $key));
            $result[] = (string) ($dict[$cleanKey] ?? $cleanKey);
        }
        return $result;
    }

    /**
     * Halaman Utama Broadcast WA (Direktur & Admin)
     */
    public function index(Request $request): View
    {
        $this->ensureSchema();

        $search = trim($request->input('search', ''));
        $selectedStatusTagihan = $request->input('status_tagihan', 'all');
        $selectedStatusKirim = $request->input('status_kirim', 'all');

        $currentMonth = date('m');
        $selectedBulan = $request->filled('bulan') ? str_pad((string)(int)$request->input('bulan'), 2, '0', STR_PAD_LEFT) : $currentMonth;

        $currentYear = (string)date('Y');
        $selectedTahun = $request->filled('tahun') ? (string)$request->input('tahun') : $currentYear;

        $selectedWilayah = $request->input('wilayah', 'all');
        $perPage = (int) $request->input('per_page', 15);

        $baseTable = '';
        $cols = [];
        $query = $this->buildCustomerQuery($baseTable, $cols, $selectedBulan, $selectedTahun);
        $sortField = $this->selectCustomerFields($query, $baseTable);

        // Filter out dummy/unregistered records
        if (in_array('status_reg', $cols) || in_array('nik_penduduk', $cols)) {
            $query->where(function ($q) use ($cols) {
                if (in_array('status_reg', $cols)) {
                    $q->whereNotNull('c.status_reg');
                }
                if (in_array('nik_penduduk', $cols)) {
                    $q->whereNotNull('c.nik_penduduk');
                }
            });
        }

        // Apply filters
        if (!empty($selectedBulan) && $selectedBulan !== 'all') {
            if ($baseTable === 'trx_billing_layanan' || $baseTable === 'view_billing_layanan' || in_array('bulan_tagihan', $cols)) {
                $query->where('c.bulan_tagihan', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT));
            }
        }
        if ($selectedTahun !== 'all' && !empty($selectedTahun)) {
            if ($baseTable === 'trx_billing_layanan' || $baseTable === 'view_billing_layanan' || in_array('tahun_tagihan', $cols)) {
                $query->where('c.tahun_tagihan', $selectedTahun);
            }
        }

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

        // Filter Status Pengiriman WA (Sudah / Belum)
        if ($selectedStatusKirim === 'sent') {
            $query->where(function ($q) {
                if ($this->hasTableCached('tb_broadcast_wa_log')) {
                    $q->where('log_sent.total_sent_count', '>', 0)
                      ->orWhere('inv.notif_wa', '>', 0);
                } else {
                    $q->where('inv.notif_wa', '>', 0);
                }
            });
        } elseif ($selectedStatusKirim === 'unsent') {
            $query->where(function ($q) {
                if ($this->hasTableCached('tb_broadcast_wa_log')) {
                    $q->where(function ($sq) {
                        $sq->whereNull('log_sent.total_sent_count')
                           ->orWhere('log_sent.total_sent_count', '<=', 0);
                    })->where(function ($sq) {
                        $sq->whereNull('inv.notif_wa')
                           ->orWhere('inv.notif_wa', '<=', 0);
                    });
                } else {
                    $q->where(function ($sq) {
                        $sq->whereNull('inv.notif_wa')
                           ->orWhere('inv.notif_wa', '<=', 0);
                    });
                }
            });
        }

        if ($selectedWilayah !== 'all' && !empty($selectedWilayah)) {
            if (in_array('nama_kota_pasang', $cols)) {
                $query->where('c.nama_kota_pasang', $selectedWilayah);
            }
        }

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

        foreach ($pelangganList as $p) {
            $p->clean_hp = $this->formatWaPhone($p->nomor_hp ?? '');
            $p->is_due_soon = in_array($p->status_bill_lay ?? '', ['13', '14']);
        }

        // Check Meta API status
        $isMetaConfigured = $this->metaWaService->isConfigured();
        $metaPhoneId = config('services.meta_whatsapp.phone_number_id');

        // Auto-sync templates from Meta if configured and no approved meta templates synced yet
        if ($isMetaConfigured) {
            $hasMetaTemplates = DB::table('tb_broadcast_wa_template')
                ->whereIn('meta_template_name', ['tagihan_bulanan', 'work_report'])
                ->exists();
            if (!$hasMetaTemplates) {
                try {
                    $this->metaWaService->syncTemplatesToDatabase();
                } catch (\Throwable $ex) {
                    Log::warning('Auto sync templates notice: ' . $ex->getMessage());
                }
            }
        }

        $templates = DB::table('tb_broadcast_wa_template')
            ->whereNotNull('meta_template_name')
            ->where('meta_template_name', '!=', '')
            ->whereNotIn('meta_template_name', ['pengingat_jatuh_tempo_v1', 'pengumuman_maintenance', 'peringatan_isolir_layanan', 'pengumuman_umum'])
            ->orderBy('is_default', 'desc')
            ->orderBy('nama_template', 'asc')
            ->get();
        $defaultTemplate = $templates->where('is_default', 1)->first() ?? $templates->first();

        $totalTargetCount = $pelangganList->total();
        
        $hasViewBilling   = $this->hasTableCached('view_billing_layanan');
        $hasViewBatchjob  = $this->hasTableCached('view_batchjob');
        $hasTrxBilling    = $this->hasTableCached('trx_billing_layanan');
        $unpaidTable = $hasViewBilling ? 'view_billing_layanan' : ($hasTrxBilling ? 'trx_billing_layanan' : ($hasViewBatchjob ? 'view_batchjob' : 'trx_batchjob_register'));
        $unpaidCols = $this->getColumnsCached($unpaidTable);
        
        $totalUnpaidCount = Cache::remember("broadcast_unpaid_cnt_{$selectedBulan}_{$selectedTahun}", 60, function () use ($unpaidTable, $unpaidCols, $selectedBulan, $selectedTahun) {
            $unpaidQuery = DB::table($unpaidTable);
            if (in_array('status_bill_lay', $unpaidCols)) {
                $unpaidQuery->whereIn('status_bill_lay', ['13', '14']);
            } elseif (in_array('status_reg', $unpaidCols)) {
                $unpaidQuery->whereIn('status_reg', ['23', '23.1']);
            }
            if (in_array('status_reg', $unpaidCols)) {
                $unpaidQuery->whereNotNull('status_reg');
            }
            if ($selectedBulan !== 'all' && in_array('bulan_tagihan', $unpaidCols)) {
                $unpaidQuery->where('bulan_tagihan', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT));
            }
            if ($selectedTahun !== 'all' && in_array('tahun_tagihan', $unpaidCols)) {
                $unpaidQuery->where('tahun_tagihan', $selectedTahun);
            }
            return $unpaidQuery->count();
        });

        $totalSentLog = $this->hasTableCached('tb_broadcast_wa_log') 
            ? Cache::remember('broadcast_total_sent_log', 60, fn() => DB::table('tb_broadcast_wa_log')->count()) 
            : 0;

        $sentTodayCount = $this->hasTableCached('tb_broadcast_wa_log')
            ? Cache::remember('broadcast_sent_today_count', 60, fn() => DB::table('tb_broadcast_wa_log')->whereDate('created_at', Carbon::today())->count())
            : 0;

        $wilayahList = Cache::remember('broadcast_wilayah_list', 3600, function () use ($hasViewBatchjob, $hasViewBilling) {
            if ($hasViewBatchjob) {
                return DB::table('view_batchjob')
                    ->whereNotNull('nama_kota_pasang')
                    ->where('nama_kota_pasang', '!=', '')
                    ->distinct()
                    ->pluck('nama_kota_pasang')
                    ->toArray();
            } elseif ($hasViewBilling && in_array('nama_kota_pasang', $this->getColumnsCached('view_billing_layanan'))) {
                return DB::table('view_billing_layanan')
                    ->whereNotNull('nama_kota_pasang')
                    ->where('nama_kota_pasang', '!=', '')
                    ->distinct()
                    ->pluck('nama_kota_pasang')
                    ->toArray();
            }
            return [];
        });

        return view('admin.broadcast.index', compact(
            'pelangganList',
            'templates',
            'defaultTemplate',
            'isMetaConfigured',
            'metaPhoneId',
            'totalTargetCount',
            'totalUnpaidCount',
            'totalSentLog',
            'sentTodayCount',
            'selectedStatusTagihan',
            'selectedStatusKirim',
            'selectedBulan',
            'selectedTahun',
            'selectedWilayah',
            'wilayahList',
            'search',
            'perPage'
        ));
    }

    /**
     * Test Meta WhatsApp API Connection
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->metaWaService->testConnection();
        return response()->json($result);
    }

    /**
     * Sync Message Templates from Meta WhatsApp Cloud API
     */
    public function syncTemplates(Request $request): JsonResponse
    {
        $result = $this->metaWaService->syncTemplatesToDatabase();
        return response()->json($result);
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

        $templateRecord = null;
        if (!empty($templateId)) {
            $templateRecord = DB::table('tb_broadcast_wa_template')->where('id', $templateId)->first();
            if ($templateRecord && empty($rawMessage)) {
                $rawMessage = $templateRecord->pesan;
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

        $paramsMap = null;
        if ($templateRecord && !empty($templateRecord->meta_params_map)) {
            $paramsMap = json_decode($templateRecord->meta_params_map, true);
        }
        $metaParams = $this->buildMetaParameters($data, $paramsMap);

        return response()->json([
            'success'            => true,
            'nama_pelanggan'     => $data->nama_pelanggan ?? 'Pelanggan',
            'nomor_internet'     => $data->nomor_internet ?? '-',
            'nomor_hp'           => $data->nomor_hp ?? '-',
            'clean_hp'           => $cleanHp,
            'rendered_message'   => $renderedMessage,
            'meta_template_name' => $templateRecord->meta_template_name ?? 'tagihan_bulanan',
            'meta_parameters'    => $metaParams,
            'wa_url'             => $waUrl,
        ]);
    }

    /**
     * Send Single WhatsApp Broadcast (Supports Meta Cloud API & Fallback WA Web)
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
            'metode_kirim'    => 'nullable|string|in:meta_api,wa_web',
            'template_id'     => 'nullable|integer',
        ]);

        $user = Auth::user();
        $senderName = $user->nama_karyawan ?? ($user->username ?? 'Direktur');
        $cleanHp = $this->formatWaPhone($request->input('nomor_hp'));
        $metodeKirim = $request->input('metode_kirim', 'meta_api');
        $templateId = $request->input('template_id');

        if (empty($cleanHp)) {
            $msg = 'Nomor HP WhatsApp tidak valid atau kosong!';
            if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 200);
            }
            return redirect()->back()->with('error', $msg);
        }

        $kodeBroadcast = 'BC-SGL-' . date('YmdHis') . '-' . rand(100, 999);
        $statusKirim = 'sent';
        $metaMessageId = null;
        $metaErrorMessage = null;
        $apiSuccess = true;

        // Execute Meta Cloud API send if configured and requested
        if ($metodeKirim === 'meta_api' && $this->metaWaService->isConfigured()) {
            $tpl = null;
            if ($templateId) {
                $tpl = DB::table('tb_broadcast_wa_template')->where('id', $templateId)->first();
            }

            // Retrieve customer data for meta parameter mapping
            $baseTable = '';
            $cols = [];
            $custQuery = $this->buildCustomerQuery($baseTable, $cols);
            if ($request->filled('nomor_internet')) {
                $custQuery->where('c.nomor_internet', $request->input('nomor_internet'));
            }
            $this->selectCustomerFields($custQuery, $baseTable);
            $custData = $custQuery->first();

            $metaTemplateName = $tpl->meta_template_name ?? 'tagihan_bulanan';
            $metaLanguage = $tpl->meta_language ?? 'id';
            $paramsMap = ($tpl && !empty($tpl->meta_params_map)) ? json_decode($tpl->meta_params_map, true) : null;
            
            $metaParams = $custData 
                ? $this->buildMetaParameters($custData, $paramsMap)
                : [$request->input('nama_penerima')];

            $apiResult = $this->metaWaService->sendTemplateMessage(
                to: $cleanHp,
                templateName: $metaTemplateName,
                languageCode: $metaLanguage,
                bodyParameters: $metaParams
            );

            if ($apiResult['success']) {
                $statusKirim = 'sent';
                $metaMessageId = $apiResult['message_id'] ?? null;
            } else {
                $statusKirim = 'failed';
                $apiSuccess = false;
                $metaErrorMessage = $apiResult['message'] ?? 'Gagal kirim via Meta API';
            }
        } elseif ($metodeKirim === 'meta_api' && !$this->metaWaService->isConfigured()) {
            $metodeKirim = 'wa_web';
        }

        DB::table('tb_broadcast_wa_log')->insert([
            'kode_broadcast'     => $kodeBroadcast,
            'jenis'              => 'single',
            'kode_pengguna'      => $user->kode_pengguna ?? null,
            'nama_pengirim'      => $senderName,
            'nomor_internet'     => $request->input('nomor_internet'),
            'nama_penerima'      => $request->input('nama_penerima'),
            'nomor_hp'           => $cleanHp,
            'pesan_terkirim'     => $request->input('pesan'),
            'kategori'           => $request->input('kategori', 'jatuh_tempo'),
            'status_kirim'       => $statusKirim,
            'metode_kirim'       => $metodeKirim,
            'meta_message_id'    => $metaMessageId,
            'meta_error_message' => $metaErrorMessage,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $waUrl = 'https://wa.me/' . $cleanHp . '?text=' . urlencode($request->input('pesan'));

        if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success'         => $apiSuccess,
                'message'         => $apiSuccess 
                    ? ($metodeKirim === 'meta_api' ? 'Pesan WhatsApp resmi berhasil dikirim via Meta API!' : 'Pesan siap dibuka di WhatsApp Web.') 
                    : ('Gagal kirim via Meta API: ' . ($metaErrorMessage ?: 'Periksa pengaturan nomor/koneksi')),
                'metode_kirim'    => $metodeKirim,
                'status_kirim'    => $statusKirim,
                'wa_url'          => $waUrl,
                'meta_message_id' => $metaMessageId,
                'kode_broadcast'  => $kodeBroadcast,
            ], 200);
        }

        return redirect()->away($waUrl);
    }

    /**
     * Send Bulk WhatsApp Broadcast (Batch Render & Prepare Queue)
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $this->ensureSchema();

        $request->validate([
            'targets'      => 'required|array|min:1',
            'pesan'        => 'required|string',
            'template_id'  => 'nullable|integer',
            'kategori'     => 'nullable|string',
            'metode_kirim' => 'nullable|string|in:meta_api,wa_web',
        ]);

        $targets = $request->input('targets');
        $rawMessage = $request->input('pesan');
        $kategori = $request->input('kategori', 'jatuh_tempo');
        $metodeKirim = $request->input('metode_kirim', 'meta_api');
        $templateId = $request->input('template_id');

        $tpl = null;
        if ($templateId) {
            $tpl = DB::table('tb_broadcast_wa_template')->where('id', $templateId)->first();
        }

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
        $paramsMap = ($tpl && !empty($tpl->meta_params_map)) ? json_decode($tpl->meta_params_map, true) : null;

        foreach ($customerRecords as $cust) {
            $cleanHp = $this->formatWaPhone($cust->nomor_hp ?? '');
            if (empty($cleanHp)) {
                continue;
            }

            $renderedMessage = $this->renderTemplateMessage($rawMessage, $cust);
            $waUrl = 'https://wa.me/' . $cleanHp . '?text=' . urlencode($renderedMessage);
            $metaParams = $this->buildMetaParameters($cust, $paramsMap);

            $dispatchQueue[] = [
                'kode_broadcast'     => $kodeBatch,
                'nomor_internet'     => $cust->nomor_internet ?? '-',
                'nama_penerima'      => $cust->nama_pelanggan ?? 'Pelanggan',
                'nomor_hp'           => $cleanHp,
                'pesan'              => $renderedMessage,
                'meta_template_name' => $tpl->meta_template_name ?? 'tagihan_bulanan',
                'meta_language'      => $tpl->meta_language ?? 'id',
                'meta_parameters'    => $metaParams,
                'wa_url'             => $waUrl,
                'kategori'           => $kategori,
                'template_id'        => $templateId,
            ];
        }

        return response()->json([
            'success'            => true,
            'message'            => 'Antrean broadcast massal untuk ' . count($dispatchQueue) . ' pelanggan berhasil disiapkan.',
            'kode_broadcast'     => $kodeBatch,
            'total_count'        => count($dispatchQueue),
            'metode_kirim'       => $metodeKirim,
            'is_meta_configured' => $this->metaWaService->isConfigured(),
            'queue'              => $dispatchQueue,
        ]);
    }

    /**
     * Dispatch Single Meta Cloud API Item from Bulk Queue
     */
    public function sendApiItem(Request $request): JsonResponse
    {
        $this->ensureSchema();

        $request->validate([
            'kode_broadcast'     => 'required|string',
            'nomor_internet'     => 'nullable|string',
            'nama_penerima'      => 'required|string',
            'nomor_hp'           => 'required|string',
            'pesan'              => 'required|string',
            'meta_template_name' => 'nullable|string',
            'meta_language'      => 'nullable|string',
            'meta_parameters'    => 'nullable|array',
            'kategori'           => 'nullable|string',
        ]);

        $user = Auth::user();
        $senderName = $user->nama_karyawan ?? ($user->username ?? 'Direktur');
        $cleanHp = $this->formatWaPhone($request->input('nomor_hp'));
        
        $metaTemplateName = $request->input('meta_template_name', 'tagihan_bulanan');
        $metaLanguage = $request->input('meta_language', 'id');
        $metaParams = $request->input('meta_parameters', [$request->input('nama_penerima')]);

        $apiResult = $this->metaWaService->sendTemplateMessage(
            to: $cleanHp,
            templateName: $metaTemplateName,
            languageCode: $metaLanguage,
            bodyParameters: $metaParams
        );

        $statusKirim = $apiResult['success'] ? 'sent' : 'failed';
        $metaMessageId = $apiResult['message_id'] ?? null;
        $metaErrorMessage = $apiResult['success'] ? null : ($apiResult['message'] ?? 'Gagal kirim via Meta API');

        // Insert log record
        DB::table('tb_broadcast_wa_log')->insert([
            'kode_broadcast'     => $request->input('kode_broadcast'),
            'jenis'              => 'massal',
            'kode_pengguna'      => $user->kode_pengguna ?? null,
            'nama_pengirim'      => $senderName,
            'nomor_internet'     => $request->input('nomor_internet'),
            'nama_penerima'      => $request->input('nama_penerima'),
            'nomor_hp'           => $cleanHp,
            'pesan_terkirim'     => $request->input('pesan'),
            'kategori'           => $request->input('kategori', 'jatuh_tempo'),
            'status_kirim'       => $statusKirim,
            'metode_kirim'       => 'meta_api',
            'meta_message_id'    => $metaMessageId,
            'meta_error_message' => $metaErrorMessage,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return response()->json([
            'success'            => $apiResult['success'],
            'message'            => $apiResult['message'],
            'meta_message_id'    => $metaMessageId,
            'status_kirim'       => $statusKirim,
            'meta_error_message' => $metaErrorMessage,
        ]);
    }

    /**
     * Save / Update Custom Template
     */
    public function saveTemplate(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureSchema();

        $request->validate([
            'nama_template'      => 'required|string|max:150',
            'meta_template_name' => 'nullable|string|max:150',
            'meta_language'      => 'nullable|string|max:20',
            'pesan'              => 'required|string',
            'kategori'           => 'nullable|string',
        ]);

        $templateId = $request->input('id');

        $data = [
            'nama_template'      => $request->input('nama_template'),
            'meta_template_name' => $request->input('meta_template_name'),
            'meta_language'      => $request->input('meta_language', 'id'),
            'subjek'             => $request->input('subjek', $request->input('nama_template')),
            'kategori'           => $request->input('kategori', 'custom'),
            'pesan'              => $request->input('pesan'),
            'is_default'         => $request->has('is_default') ? 1 : 0,
            'updated_at'         => now(),
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
        $selectedStatus = $request->input('status', 'all');
        $selectedMetode = $request->input('metode', 'all');
        $perPage = (int) $request->input('per_page', 15);

        $query = DB::table('tb_broadcast_wa_log');

        if ($selectedKategori !== 'all' && !empty($selectedKategori)) {
            $query->where('kategori', $selectedKategori);
        }
        if ($selectedJenis !== 'all' && !empty($selectedJenis)) {
            $query->where('jenis', $selectedJenis);
        }
        if ($selectedStatus !== 'all' && !empty($selectedStatus)) {
            $query->where('status_kirim', $selectedStatus);
        }
        if ($selectedMetode !== 'all' && !empty($selectedMetode)) {
            $query->where('metode_kirim', $selectedMetode);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_penerima', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhere('kode_broadcast', 'like', "%{$search}%")
                  ->orWhere('meta_message_id', 'like', "%{$search}%")
                  ->orWhere('pesan_terkirim', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.broadcast.history', compact('logs', 'search', 'selectedKategori', 'selectedJenis', 'selectedStatus', 'selectedMetode', 'perPage'));
    }

    /**
     * Webhook Verification (GET hub.challenge from Meta)
     */
    public function webhookVerify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.meta_whatsapp.webhook_verify_token') ?? env('META_WA_WEBHOOK_VERIFY_TOKEN', 'ims_secret_token_2026');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Meta WhatsApp Webhook Verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta WhatsApp Webhook verification failed. Token mismatch.');
        return response('Forbidden', 403);
    }

    /**
     * Webhook Event Receiver (POST status & messages from Meta)
     */
    public function webhookReceive(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Meta WhatsApp Webhook Payload: ' . json_encode($payload));

        try {
            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    
                    // Handle Message Statuses (sent, delivered, read, failed)
                    if (!empty($value['statuses'])) {
                        foreach ($value['statuses'] as $st) {
                            $msgId = $st['id'] ?? null;
                            $status = $st['status'] ?? null; // delivered, read, failed, sent
                            
                            if ($msgId && $status && Schema::hasTable('tb_broadcast_wa_log')) {
                                DB::table('tb_broadcast_wa_log')
                                    ->where('meta_message_id', $msgId)
                                    ->update([
                                        'status_kirim' => $status,
                                        'updated_at'   => now(),
                                    ]);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp Webhook Receive error: ' . $e->getMessage());
        }

        return response()->json(['status' => 'EVENT_RECEIVED']);
    }
}
