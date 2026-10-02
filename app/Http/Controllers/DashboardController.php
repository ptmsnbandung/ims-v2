<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

            $startDate = Carbon::createFromDate($selectedTahunInt, $selectedBulanInt, 1)->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = Carbon::createFromDate($selectedTahunInt, $selectedBulanInt, 1)->endOfMonth()->format('Y-m-d 23:59:59');
            $prefixYm = "{$selectedTahunInt}-" . str_pad((string)$selectedBulanInt, 2, '0', STR_PAD_LEFT);

            $prevMonthDate = Carbon::createFromDate($selectedTahunInt, $selectedBulanInt, 1)->subMonth();
            $prevYear = (int) $prevMonthDate->format('Y');
            $prevMonth = (int) $prevMonthDate->format('m');
            $prevStartDate = $prevMonthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
            $prevEndDate = $prevMonthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');
            $prevPrefixYm = "{$prevYear}-" . str_pad((string)$prevMonth, 2, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $selectedBulan = date('m');
            $selectedTahun = (string) date('Y');
            $selectedBulanInt = (int) $selectedBulan;
            $selectedTahunInt = (int) $selectedTahun;
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d 23:59:59');
            $prefixYm = date('Y-m');
            $prevYear = (int) Carbon::now()->subMonth()->format('Y');
            $prevMonth = (int) Carbon::now()->subMonth()->format('m');
            $prevStartDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d 00:00:00');
            $prevEndDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d 23:59:59');
            $prevPrefixYm = Carbon::now()->subMonth()->format('Y-m');
        }

        // Helper closures for versatile date matching across MySQL/SQLite/String formats
        $applyDateFilter = function ($query, $col = 'date_create') use ($startDate, $endDate, $prefixYm, $selectedBulanInt, $selectedTahunInt) {
            $query->where(function ($q) use ($col, $startDate, $endDate, $prefixYm, $selectedBulanInt, $selectedTahunInt) {
                $q->whereBetween($col, [$startDate, $endDate])
                  ->orWhere($col, 'like', "{$prefixYm}%")
                  ->orWhere(function ($sub) use ($col, $selectedBulanInt, $selectedTahunInt) {
                      $sub->whereMonth($col, $selectedBulanInt)
                          ->whereYear($col, $selectedTahunInt);
                  });
            });
        };

        $prevApplyDateFilter = function ($query, $col = 'date_create') use ($prevStartDate, $prevEndDate, $prevPrefixYm, $prevMonth, $prevYear) {
            $query->where(function ($q) use ($col, $prevStartDate, $prevEndDate, $prevPrefixYm, $prevMonth, $prevYear) {
                $q->whereBetween($col, [$prevStartDate, $prevEndDate])
                  ->orWhere($col, 'like', "{$prevPrefixYm}%")
                  ->orWhere(function ($sub) use ($col, $prevMonth, $prevYear) {
                      $sub->whereMonth($col, $prevMonth)
                          ->whereYear($col, $prevYear);
                  });
            });
        };

        // 1. STATISTIK USER & PELANGGAN BARU (Bulan & Tahun Terpilih)
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
            $sourceTable = Schema::hasTable('view_batchjob') 
                ? 'view_batchjob' 
                : (Schema::hasTable('trx_batchjob_register') ? 'trx_batchjob_register' : null);

            if ($sourceTable) {
                // Aggregated counts for the selected month
                $statsQuery = DB::table($sourceTable);
                $applyDateFilter($statsQuery, 'date_create');

                $monthStats = $statsQuery->selectRaw("
                    COUNT(*) as total_baru,
                    COUNT(CASE WHEN status_reg IN ('20', '20.0', '20.1') THEN 1 END) as aktif_baru,
                    COUNT(CASE WHEN status_reg IN ('11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '18.1', '19', '19.1') THEN 1 END) as proses_baru,
                    COUNT(CASE WHEN status_reg IN ('14', '15') THEN 1 END) as batal_baru,
                    COUNT(CASE WHEN status_reg IN ('11', '11.1') THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status_reg IN ('12', '13', '13.1') THEN 1 END) as survey_count,
                    COUNT(CASE WHEN status_reg IN ('16', '17', '17.1') THEN 1 END) as instalasi_count,
                    COUNT(CASE WHEN status_reg IN ('18', '18.1', '19', '19.1') THEN 1 END) as aktivasi_count
                ")->first();

                $totalBaru = (int) ($monthStats->total_baru ?? 0);
                $aktifBaru = (int) ($monthStats->aktif_baru ?? 0);
                $prosesBaru = (int) ($monthStats->proses_baru ?? 0);
                $batalBaru = (int) ($monthStats->batal_baru ?? 0);

                // Previous month count for trend comparison
                $prevQuery = DB::table($sourceTable);
                $prevApplyDateFilter($prevQuery, 'date_create');
                $prevTotalBaru = $prevQuery->count();

                $growthCount = $totalBaru - $prevTotalBaru;
                $growthPercent = $prevTotalBaru > 0 
                    ? round((($totalBaru - $prevTotalBaru) / $prevTotalBaru) * 100, 1) 
                    : ($totalBaru > 0 ? 100 : 0);

                // Total all-time active customers
                $totalSemuaPelangganAktif = DB::table($sourceTable)
                    ->whereIn('status_reg', ['20', '20.0', '20.1'])
                    ->count();

                // Detailed Recent New Users and Package Breakdown
                if ($sourceTable === 'view_batchjob') {
                    $recentQuery = DB::table('view_batchjob');
                    $applyDateFilter($recentQuery, 'date_create');

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
                    $applyDateFilter($paketQuery, 'date_create');

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
                } else {
                    $baseQuery = DB::table('trx_batchjob_register as r');
                    $applyDateFilter($baseQuery, 'r.date_create');

                    $hasPelanggan = Schema::hasTable('m_pelanggan');
                    $hasBandwith = Schema::hasTable('m_bandwith');
                    $hasBandwithKat = Schema::hasTable('m_bandwith_kategori');

                    $hasTrxNamaPelanggan = Schema::hasColumn('trx_batchjob_register', 'nama_pelanggan');
                    $hasTrxNamaKat = Schema::hasColumn('trx_batchjob_register', 'nama_kategori_bandwith');
                    $hasTrxNominalBw = Schema::hasColumn('trx_batchjob_register', 'nominal_bandwith');

                    if ($hasPelanggan) {
                        $baseQuery->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk');
                    }
                    if ($hasBandwith) {
                        $baseQuery->leftJoin('m_bandwith as bw', 'r.kode_bandwith', '=', 'bw.kode_bandwith');
                    }
                    if ($hasBandwith && $hasBandwithKat) {
                        $baseQuery->leftJoin('m_bandwith_kategori as bwk', 'bw.kode_kategori_bandwith', '=', 'bwk.kode_kategori_bandwith');
                    }

                    $namaPelParts = [];
                    if ($hasPelanggan) {
                        $namaPelParts[] = 'p.nama_penduduk';
                    }
                    if ($hasTrxNamaPelanggan) {
                        $namaPelParts[] = 'r.nama_pelanggan';
                    }
                    $namaPelParts[] = 'r.nomor_internet';
                    $namaPelangganCol = 'COALESCE(' . implode(', ', $namaPelParts) . ')';

                    $namaPaketParts = [];
                    if ($hasBandwith && $hasBandwithKat) {
                        $namaPaketParts[] = 'bwk.nama_kategori_bandwith';
                        $namaPaketParts[] = 'bwk.alias_nama_kategori';
                    }
                    if ($hasBandwith) {
                        $namaPaketParts[] = 'bw.nama_bandwith';
                    }
                    if ($hasTrxNamaKat) {
                        $namaPaketParts[] = 'r.nama_kategori_bandwith';
                    }
                    $namaPaketParts[] = 'r.kode_bandwith';
                    $namaPaketParts[] = "'INTERNET'";
                    $namaPaketCol = 'COALESCE(' . implode(', ', $namaPaketParts) . ')';

                    $nominalBwParts = [];
                    if ($hasBandwith) {
                        $nominalBwParts[] = 'bw.nominal_bandwith';
                    }
                    if ($hasTrxNominalBw) {
                        $nominalBwParts[] = 'r.nominal_bandwith';
                    }
                    $nominalBwParts[] = "'0'";
                    $nominalBwCol = 'COALESCE(' . implode(', ', $nominalBwParts) . ')';

                    $recentNewUsers = (clone $baseQuery)->select(
                        'r.nomor_internet',
                        DB::raw("{$namaPelangganCol} as nama_pelanggan"),
                        DB::raw("{$namaPaketCol} as nama_kategori_bandwith"),
                        DB::raw("{$nominalBwCol} as nominal_bandwith"),
                        'r.status_reg',
                        'r.date_create'
                    )
                    ->orderBy('r.date_create', 'desc')
                    ->limit(10)
                    ->get();

                    $paketBreakdown = (clone $baseQuery)->select(
                        DB::raw("{$namaPaketCol} as nama_paket"),
                        DB::raw("{$nominalBwCol} as nominal_bandwith"),
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('nama_paket', 'nominal_bandwith')
                    ->orderByDesc('total')
                    ->limit(6)
                    ->get();
                }

                // New system users (tb_pengguna)
                $totalPenggunaSistemBaru = 0;
                if (Schema::hasTable('tb_pengguna')) {
                    $userDateCol = Schema::hasColumn('tb_pengguna', 'date_create') 
                        ? 'date_create' 
                        : (Schema::hasColumn('tb_pengguna', 'created_at') ? 'created_at' : null);
                    
                    if ($userDateCol) {
                        $penggunaQuery = DB::table('tb_pengguna');
                        $applyDateFilter($penggunaQuery, $userDateCol);
                        $totalPenggunaSistemBaru = $penggunaQuery->count();
                    }
                }

                $newUserStats = [
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
                        '11' => (int) ($monthStats->draft_count ?? 0),
                        '12' => (int) ($monthStats->survey_count ?? 0),
                        '16' => (int) ($monthStats->instalasi_count ?? 0),
                        '18_19' => (int) ($monthStats->aktivasi_count ?? 0),
                        '20' => $aktifBaru,
                        'batal' => $batalBaru,
                    ],
                ];
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
            'monthsList' => $monthsList,
            'availableYears' => $availableYears,
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
        ]);
    }
}
