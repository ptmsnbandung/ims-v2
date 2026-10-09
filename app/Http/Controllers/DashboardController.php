<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the universal dashboard view with new users statistics and date filters.
     */
    public function index(Request $request): View
    {
        /** @var Pengguna|null $user */
        $user = $request->user();
        if ($user && $user instanceof Pengguna) {
            $user->loadMissing(['level', 'karyawan']);
        }

        $selectedBulan = $request->filled('bulan') ? str_pad((string) (int) $request->input('bulan'), 2, '0', STR_PAD_LEFT) : date('m');
        $selectedTahun = $request->filled('tahun') ? (string) $request->input('tahun') : (string) date('Y');

        $monthsList = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        $currentYearInt = (int) date('Y');
        $availableYears = range($currentYearInt - 4, $currentYearInt + 1);

        try {
            $selectedBulanInt = (int) $selectedBulan;
            $selectedTahunInt = (int) $selectedTahun;
            $startOfYear = Carbon::createFromDate($selectedTahunInt, 1, 1)->startOfYear()->format('Y-m-d 00:00:00');
            $endOfYear = Carbon::createFromDate($selectedTahunInt, 12, 31)->endOfYear()->format('Y-m-d 23:59:59');

            $prevYearInt = $selectedTahunInt - 1;
            $prevStartOfYear = Carbon::createFromDate($prevYearInt, 1, 1)->startOfYear()->format('Y-m-d 00:00:00');
            $prevEndOfYear = Carbon::createFromDate($prevYearInt, 12, 31)->endOfYear()->format('Y-m-d 23:59:59');
        } catch (\Throwable $e) {
            $selectedTahun = (string) date('Y');
            $selectedTahunInt = (int) $selectedTahun;
            $startOfYear = Carbon::createFromDate($selectedTahunInt, 1, 1)->startOfYear()->format('Y-m-d 00:00:00');
            $endOfYear = Carbon::createFromDate($selectedTahunInt, 12, 31)->endOfYear()->format('Y-m-d 23:59:59');
            $prevYearInt = $selectedTahunInt - 1;
            $prevStartOfYear = Carbon::createFromDate($prevYearInt, 1, 1)->startOfYear()->format('Y-m-d 00:00:00');
            $prevEndOfYear = Carbon::createFromDate($prevYearInt, 12, 31)->endOfYear()->format('Y-m-d 23:59:59');
        }

        // Helper closures for yearly date matching across MySQL/SQLite/String formats
        $applyYearFilter = function ($query, $col = 'date_create') use ($startOfYear, $endOfYear, $selectedTahunInt) {
            $query->where(function ($q) use ($col, $startOfYear, $endOfYear, $selectedTahunInt) {
                $q->whereBetween($col, [$startOfYear, $endOfYear])
                  ->orWhere($col, 'like', "{$selectedTahunInt}%")
                  ->orWhereYear($col, $selectedTahunInt);
            });
        };

        $prevApplyYearFilter = function ($query, $col = 'date_create') use ($prevStartOfYear, $prevEndOfYear, $prevYearInt) {
            $query->where(function ($q) use ($col, $prevStartOfYear, $prevEndOfYear, $prevYearInt) {
                $q->whereBetween($col, [$prevStartOfYear, $prevEndOfYear])
                  ->orWhere($col, 'like', "{$prevYearInt}%")
                  ->orWhereYear($col, $prevYearInt);
            });
        };

        // 1. STATISTIK USER & PELANGGAN BARU (Tahunan)
        $chartData = [
            'monthlyLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'monthlyRegistrasi' => array_fill(0, 12, 0),
            'monthlyAktif' => array_fill(0, 12, 0),
            'totalTahunRegistrasi' => 0,
            'totalTahunAktif' => 0,
            'bandwidthLabels' => ['BROADBAND HOME', 'DEDICATED SOHO', 'CORPORATE', 'HOTSPOT VOUCHER'],
            'bandwidthSeries' => [0, 0, 0, 0],
            'pipelineLabels' => ['Draft Pendaftaran', 'Survey Lokasi', 'Instalasi & Pasang', 'Aktivasi NOC', 'Aktif (Online)', 'Batal'],
            'pipelineSeries' => [0, 0, 0, 0, 0, 0],
            'cityLabels' => [],
            'citySeries' => [],
            'cityAktifSeries' => [],
            'cityBreakdown' => collect([]),
            'totalCityUsers' => 0,
        ];

        $newUserStats = [
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
            'selectedBulanNama' => $monthsList[$selectedBulan] ?? 'Bulan Terpilih',
            'totalBaru' => 0,
            'aktifBaru' => 0,
            'prosesBaru' => 0,
            'batalBaru' => 0,
            'prevTotalBaru' => 0,
            'growthPercent' => 0,
            'growthCount' => 0,
            'totalPenggunaSistemBaru' => 0,
            'totalSemuaPelangganAktif' => 0,
            'paketBreakdown' => collect([]),
            'recentNewUsers' => collect([]),
            'statusBreakdown' => [
                '11' => 0,    // Draft Registrasi
                '12' => 0,    // Survey
                '16' => 0,    // Instalasi
                '18_19' => 0, // Aktivasi NOC
                '20' => 0,    // Aktif Online
                'batal' => 0, // Batal (#14, #15)
            ],
        ];

        try {
            $statsCacheKey = "dashboard_stats_v2_{$selectedBulan}_{$selectedTahun}";
            $cachedDashboardData = Cache::remember($statsCacheKey, 60, function () use ($applyYearFilter, $prevApplyYearFilter, $selectedBulan, $selectedTahun, $monthsList, $selectedTahunInt, $startOfYear, $endOfYear) {
                $sourceTable = 'view_batchjob';
                $localNewUserStats = [
                    'selectedBulan' => $selectedBulan,
                    'selectedTahun' => $selectedTahun,
                    'selectedBulanNama' => $monthsList[$selectedBulan] ?? 'Bulan Terpilih',
                    'totalBaru' => 0,
                    'aktifBaru' => 0,
                    'prosesBaru' => 0,
                    'batalBaru' => 0,
                    'prevTotalBaru' => 0,
                    'growthPercent' => 0,
                    'growthCount' => 0,
                    'totalPenggunaSistemBaru' => 0,
                    'totalSemuaPelangganAktif' => 0,
                    'paketBreakdown' => collect([]),
                    'recentNewUsers' => collect([]),
                    'statusBreakdown' => [
                        '11' => 0,
                        '12' => 0,
                        '16' => 0,
                        '18_19' => 0,
                        '20' => 0,
                        'batal' => 0,
                    ],
                ];

                // Aggregated counts for the selected year
                $statsQuery = DB::table($sourceTable);
                $applyYearFilter($statsQuery, 'date_create');

                $yearStats = $statsQuery->selectRaw("
                    COUNT(*) as total_baru,
                    COUNT(CASE WHEN status_reg IN ('20', '20.0', '20.1') THEN 1 END) as aktif_baru,
                    COUNT(CASE WHEN status_reg IN ('11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '18.1', '19', '19.1') THEN 1 END) as proses_baru,
                    COUNT(CASE WHEN status_reg IN ('14', '15') THEN 1 END) as batal_baru,
                    COUNT(CASE WHEN status_reg IN ('11', '11.1') THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status_reg IN ('12', '13', '13.1') THEN 1 END) as survey_count,
                    COUNT(CASE WHEN status_reg IN ('16', '17', '17.1') THEN 1 END) as instalasi_count,
                    COUNT(CASE WHEN status_reg IN ('18', '18.1', '19', '19.1') THEN 1 END) as aktivasi_count
                ")->first();

                $totalBaru = (int) ($yearStats->total_baru ?? 0);
                $aktifBaru = (int) ($yearStats->aktif_baru ?? 0);
                $prosesBaru = (int) ($yearStats->proses_baru ?? 0);
                $batalBaru = (int) ($yearStats->batal_baru ?? 0);

                // Previous year count for trend comparison
                $prevQuery = DB::table($sourceTable);
                $prevApplyYearFilter($prevQuery, 'date_create');
                $prevTotalBaru = $prevQuery->count();

                $growthCount = $totalBaru - $prevTotalBaru;
                $growthPercent = $prevTotalBaru > 0 
                    ? round((($totalBaru - $prevTotalBaru) / $prevTotalBaru) * 100, 1) 
                    : ($totalBaru > 0 ? 100 : 0);

                // Total all-time active customers
                $totalSemuaPelangganAktif = DB::table($sourceTable)
                    ->whereIn('status_reg', ['20', '20.0', '20.1'])
                    ->count();

                // Detailed Recent New Users and Package Breakdown for the Year
                $recentQuery = DB::table('view_batchjob');
                $applyYearFilter($recentQuery, 'date_create');

                $recentNewUsers = $recentQuery
                    ->select(
                        'nomor_internet',
                        'nama_pelanggan',
                        DB::raw("COALESCE(NULLIF(nama_kategori_bandwith, ''), NULLIF(alias_nama_kategori, ''), 'INTERNET') as nama_kategori_bandwith"),
                        DB::raw("COALESCE(nominal_bandwith, '0') as nominal_bandwith"),
                        'status_reg',
                        'date_create'
                    )
                    ->orderBy('date_create', 'desc')
                    ->limit(10)
                    ->get();

                $paketQuery = DB::table('view_batchjob');
                $applyYearFilter($paketQuery, 'date_create');

                $paketBreakdown = $paketQuery
                    ->select(
                        DB::raw("COALESCE(NULLIF(nama_kategori_bandwith, ''), NULLIF(alias_nama_kategori, ''), 'INTERNET') as nama_paket"),
                        DB::raw("COALESCE(nominal_bandwith, '0') as nominal_bandwith"),
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('nama_paket', 'nominal_bandwith')
                    ->orderByDesc('total')
                    ->limit(6)
                    ->get();

                // New system users (tb_pengguna) in the year
                $totalPenggunaSistemBaru = 0;
                if (Schema::hasTable('tb_pengguna')) {
                    $userDateCol = Schema::hasColumn('tb_pengguna', 'date_create') 
                        ? 'date_create' 
                        : (Schema::hasColumn('tb_pengguna', 'created_at') ? 'created_at' : null);
                    
                    if ($userDateCol) {
                        $penggunaQuery = DB::table('tb_pengguna');
                        $applyYearFilter($penggunaQuery, $userDateCol);
                        $totalPenggunaSistemBaru = $penggunaQuery->count();
                    }
                }

                $localNewUserStats = [
                    'selectedBulan' => $selectedBulan,
                    'selectedTahun' => $selectedTahun,
                    'selectedBulanNama' => $monthsList[$selectedBulan] ?? 'Bulan Terpilih',
                    'totalBaru' => $totalBaru,
                    'aktifBaru' => $aktifBaru,
                    'prosesBaru' => $prosesBaru,
                    'batalBaru' => $batalBaru,
                    'prevTotalBaru' => $prevTotalBaru,
                    'growthPercent' => $growthPercent,
                    'growthCount' => $growthCount,
                    'totalPenggunaSistemBaru' => $totalPenggunaSistemBaru,
                    'totalSemuaPelangganAktif' => $totalSemuaPelangganAktif,
                    'paketBreakdown' => $paketBreakdown,
                    'recentNewUsers' => $recentNewUsers,
                    'statusBreakdown' => [
                        '11' => (int) ($yearStats->draft_count ?? 0),
                        '12' => (int) ($yearStats->survey_count ?? 0),
                        '16' => (int) ($yearStats->instalasi_count ?? 0),
                        '18_19' => (int) ($yearStats->aktivasi_count ?? 0),
                        '20' => $aktifBaru,
                        'batal' => $batalBaru,
                    ],
                ];

                // 1.B DATA GRAFIK: TREN PERTUMBUHAN USER DARI BULAN KE BULAN
                $chartMonthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                $chartMonthlyRegistrasi = array_fill(0, 12, 0);
                $chartMonthlyAktif = array_fill(0, 12, 0);

                try {
                    $monthlyRows = DB::table($sourceTable)
                        ->where(function($q) use ($startOfYear, $endOfYear, $selectedTahunInt) {
                            $q->whereBetween('date_create', [$startOfYear, $endOfYear])
                              ->orWhere('date_create', 'like', "{$selectedTahunInt}%");
                        })
                        ->selectRaw("
                            MONTH(date_create) as month_num,
                            COUNT(*) as total,
                            COUNT(CASE WHEN status_reg IN ('20', '20.0', '20.1') THEN 1 END) as aktif
                        ")
                        ->groupBy(DB::raw('MONTH(date_create)'))
                        ->get();

                    foreach ($monthlyRows as $mRow) {
                        $mNum = (int) ($mRow->month_num ?? 0);
                        if ($mNum >= 1 && $mNum <= 12) {
                            $chartMonthlyRegistrasi[$mNum - 1] = (int) ($mRow->total ?? 0);
                            $chartMonthlyAktif[$mNum - 1] = (int) ($mRow->aktif ?? 0);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Dashboard monthly stats query fallback: ' . $e->getMessage());
                }

                // 1.C DATA GRAFIK: DISTRIBUSI KATEGORI BANDWIDTH
                $chartBandwidthLabels = [];
                $chartBandwidthSeries = [];

                $bwDistQuery = DB::table('view_batchjob')
                    ->where(function($q) use ($selectedTahunInt) {
                        $q->where('date_create', 'like', "{$selectedTahunInt}%")
                          ->orWhereYear('date_create', $selectedTahunInt);
                    })
                    ->select(
                        DB::raw("COALESCE(NULLIF(nama_kategori_bandwith, ''), NULLIF(alias_nama_kategori, ''), 'BROADBAND') as kategori_name"),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('kategori_name')
                    ->orderByDesc('total')
                    ->get();

                if ($bwDistQuery->isEmpty()) {
                    $bwDistQuery = DB::table('view_batchjob')
                        ->select(
                            DB::raw("COALESCE(NULLIF(nama_kategori_bandwith, ''), NULLIF(alias_nama_kategori, ''), 'BROADBAND') as kategori_name"),
                            DB::raw('COUNT(*) as total')
                        )
                        ->groupBy('kategori_name')
                        ->orderByDesc('total')
                        ->limit(6)
                        ->get();
                }

                foreach ($bwDistQuery as $bwItem) {
                    $labelName = trim((string)$bwItem->kategori_name);
                    if (!empty($labelName)) {
                        $chartBandwidthLabels[] = $labelName;
                        $chartBandwidthSeries[] = (int) $bwItem->total;
                    }
                }

                if (empty($chartBandwidthLabels)) {
                    $chartBandwidthLabels = ['BROADBAND HOME', 'DEDICATED SOHO', 'CORPORATE', 'HOTSPOT VOUCHER'];
                    $chartBandwidthSeries = [0, 0, 0, 0];
                }

                // 1.D DATA GRAFIK: PIPELINE STATUS REGISTRASI
                $chartPipelineLabels = ['Draft Pendaftaran', 'Survey Lokasi', 'Instalasi & Pasang', 'Aktivasi NOC', 'Aktif (Online)', 'Batal'];
                $chartPipelineSeries = [
                    (int) ($yearStats->draft_count ?? 0),
                    (int) ($yearStats->survey_count ?? 0),
                    (int) ($yearStats->instalasi_count ?? 0),
                    (int) ($yearStats->aktivasi_count ?? 0),
                    $aktifBaru,
                    $batalBaru,
                ];

                // 1.E DATA GRAFIK: DISTRIBUSI USER BERDASARKAN NAMA_KOTA_PASANG
                $chartCityLabels = [];
                $chartCitySeries = [];
                $chartCityAktifSeries = [];
                $cityBreakdown = collect([]);

                try {
                    $cityDistQuery = DB::table('view_batchjob')
                        ->select(
                            DB::raw("
                                CASE 
                                    WHEN UPPER(TRIM(nama_kota_pasang)) = 'KOTA BANDUNG' THEN 'KOTA BANDUNG'
                                    WHEN UPPER(TRIM(nama_kota_pasang)) IN ('KABUPATEN BANDUNG', 'KAB. BANDUNG') THEN 'KABUPATEN BANDUNG'
                                    ELSE 'LAINNYA'
                                END as kota_name
                            "),
                            DB::raw('COUNT(*) as total'),
                            DB::raw("COUNT(CASE WHEN status_reg IN ('20', '20.0', '20.1') THEN 1 END) as total_aktif")
                        )
                        ->groupBy('kota_name')
                        ->orderByRaw("
                            CASE kota_name 
                                WHEN 'KOTA BANDUNG' THEN 1 
                                WHEN 'KABUPATEN BANDUNG' THEN 2 
                                ELSE 3 
                            END
                        ")
                        ->get();

                    foreach ($cityDistQuery as $cityItem) {
                        $cityName = trim((string)$cityItem->kota_name);
                        if (!empty($cityName)) {
                            $chartCityLabels[] = $cityName;
                            $chartCitySeries[] = (int) $cityItem->total;
                            $chartCityAktifSeries[] = (int) $cityItem->total_aktif;
                        }
                    }
                    $cityBreakdown = $cityDistQuery->map(function($item) {
                        return [
                            'kota_name' => is_array($item) ? ($item['kota_name'] ?? '') : ($item->kota_name ?? ''),
                            'total' => (int) (is_array($item) ? ($item['total'] ?? 0) : ($item->total ?? 0)),
                            'total_aktif' => (int) (is_array($item) ? ($item['total_aktif'] ?? 0) : ($item->total_aktif ?? 0)),
                        ];
                    });
                } catch (\Throwable $e) {
                    Log::warning('City dist query fallback: ' . $e->getMessage());
                }

                if (empty($chartCityLabels)) {
                    $chartCityLabels = ['KOTA BANDUNG', 'KABUPATEN BANDUNG', 'LAINNYA'];
                    $chartCitySeries = [0, 0, 0];
                    $chartCityAktifSeries = [0, 0, 0];
                    $cityBreakdown = collect([
                        ['kota_name' => 'KOTA BANDUNG', 'total' => 0, 'total_aktif' => 0],
                        ['kota_name' => 'KABUPATEN BANDUNG', 'total' => 0, 'total_aktif' => 0],
                        ['kota_name' => 'LAINNYA', 'total' => 0, 'total_aktif' => 0],
                    ]);
                }

                $localChartData = [
                    'monthlyLabels' => $chartMonthlyLabels,
                    'monthlyRegistrasi' => $chartMonthlyRegistrasi,
                    'monthlyAktif' => $chartMonthlyAktif,
                    'totalTahunRegistrasi' => array_sum($chartMonthlyRegistrasi),
                    'totalTahunAktif' => array_sum($chartMonthlyAktif),
                    'bandwidthLabels' => $chartBandwidthLabels,
                    'bandwidthSeries' => $chartBandwidthSeries,
                    'pipelineLabels' => $chartPipelineLabels,
                    'pipelineSeries' => $chartPipelineSeries,
                    'cityLabels' => $chartCityLabels,
                    'citySeries' => $chartCitySeries,
                    'cityAktifSeries' => $chartCityAktifSeries,
                    'cityBreakdown' => $cityBreakdown,
                    'totalCityUsers' => array_sum($chartCitySeries),
                ];

                return [
                    'newUserStats' => $localNewUserStats,
                    'chartData' => $localChartData,
                ];
            });

            if (!empty($cachedDashboardData['newUserStats'])) {
                $newUserStats = $cachedDashboardData['newUserStats'];
            }
            if (!empty($cachedDashboardData['chartData'])) {
                $chartData = $cachedDashboardData['chartData'];
            }
        } catch (\Throwable $e) {
            Log::error('Dashboard New User Stats Error: ' . $e->getMessage());
        }

        // 2. STATISTIK FINANCE (SINKRON DENGAN BULAN & TAHUN TERPILIH)
        $financeStats = [
            'currentMonth' => $selectedBulan,
            'currentYear' => $selectedTahun,
            'draftCount' => 0,
            'draftAmount' => 0,
            'publishCount' => 0,
            'publishAmount' => 0,
            'paidCount' => 0,
            'paidAmount' => 0,
            'totalRegPending' => 0,
            'recentInvoices' => collect([]),
        ];

        if ($user->isFinance() || $user->isDirektur()) {
            try {
                if (Schema::hasTable('trx_billing_layanan')) {
                    $kpi = DB::table('trx_billing_layanan')
                        ->where('bulan_tagihan', $selectedBulan)
                        ->where('tahun_tagihan', $selectedTahun)
                        ->selectRaw("
                            COUNT(CASE WHEN status_bill_lay IN ('11', '12') THEN 1 END) as draft_count,
                            COALESCE(SUM(CASE WHEN status_bill_lay IN ('11', '12') THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as draft_amount,
                            COUNT(CASE WHEN status_bill_lay = '13' THEN 1 END) as publish_count,
                            COALESCE(SUM(CASE WHEN status_bill_lay = '13' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as publish_amount,
                            COUNT(CASE WHEN status_bill_lay = '15' THEN 1 END) as paid_count,
                            COALESCE(SUM(CASE WHEN status_bill_lay = '15' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as paid_amount
                        ")
                        ->first();

                    $draftCount = (int) ($kpi->draft_count ?? 0);
                    $draftAmount = (float) ($kpi->draft_amount ?? 0);
                    $publishCount = (int) ($kpi->publish_count ?? 0);
                    $publishAmount = (float) ($kpi->publish_amount ?? 0);
                    $paidCount = (int) ($kpi->paid_count ?? 0);
                    $paidAmount = (float) ($kpi->paid_amount ?? 0);

                    $viewTable = Schema::hasTable('view_billing_layanan') ? 'view_billing_layanan' : 'trx_billing_layanan';
                    $recentInvoices = DB::table($viewTable)
                        ->where('bulan_tagihan', $selectedBulan)
                        ->where('tahun_tagihan', $selectedTahun)
                        ->orderBy('date_create', 'desc')
                        ->limit(5)
                        ->get();

                    if ($recentInvoices->isEmpty()) {
                        $recentInvoices = DB::table($viewTable)
                            ->orderBy('date_create', 'desc')
                            ->limit(5)
                            ->get();
                    }

                    $regTable = Schema::hasTable('view_billing_reg') ? 'view_billing_reg' : (Schema::hasTable('trx_billing_reg') ? 'trx_billing_reg' : null);
                    $totalRegPending = $regTable ? DB::table($regTable)->whereIn('status_bill_reg', ['11', '12', '13'])->count() : 0;

                    $financeStats = [
                        'currentMonth' => $selectedBulan,
                        'currentYear' => $selectedTahun,
                        'draftCount' => $draftCount,
                        'draftAmount' => $draftAmount,
                        'publishCount' => $publishCount,
                        'publishAmount' => $publishAmount,
                        'paidCount' => $paidCount,
                        'paidAmount' => $paidAmount,
                        'totalRegPending' => $totalRegPending,
                        'recentInvoices' => $recentInvoices,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Dashboard Finance Stats Error: ' . $e->getMessage());
            }
        }

        return view('dashboard', [
            'user' => $user,
            'newUserStats' => $newUserStats,
            'financeStats' => $financeStats,
            'chartData' => $chartData,
            'monthsList' => $monthsList,
            'availableYears' => $availableYears,
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
        ]);
    }
}
