<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $selectedBulan = $request->filled('bulan') ? str_pad($request->input('bulan'), 2, '0', STR_PAD_LEFT) : date('m');
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
            $startDate = Carbon::createFromDate((int) $selectedTahun, (int) $selectedBulan, 1)->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = Carbon::createFromDate((int) $selectedTahun, (int) $selectedBulan, 1)->endOfMonth()->format('Y-m-d 23:59:59');

            $prevMonthDate = Carbon::createFromDate((int) $selectedTahun, (int) $selectedBulan, 1)->subMonth();
            $prevStartDate = $prevMonthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
            $prevEndDate = $prevMonthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');
        } catch (\Throwable $e) {
            $selectedBulan = date('m');
            $selectedTahun = (string) date('Y');
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d 23:59:59');
            $prevStartDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d 00:00:00');
            $prevEndDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d 23:59:59');
        }

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
            if (Schema::hasTable('trx_batchjob_register')) {
                $monthStats = DB::table('trx_batchjob_register')
                    ->whereBetween('date_create', [$startDate, $endDate])
                    ->selectRaw("
                        COUNT(*) as total_baru,
                        COUNT(CASE WHEN status_reg = '20' THEN 1 END) as aktif_baru,
                        COUNT(CASE WHEN status_reg IN ('11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '18.1', '19', '19.1') THEN 1 END) as proses_baru,
                        COUNT(CASE WHEN status_reg IN ('14', '15') THEN 1 END) as batal_baru,
                        COUNT(CASE WHEN status_reg IN ('11', '11.1') THEN 1 END) as draft_count,
                        COUNT(CASE WHEN status_reg IN ('12', '13', '13.1') THEN 1 END) as survey_count,
                        COUNT(CASE WHEN status_reg IN ('16', '17', '17.1') THEN 1 END) as instalasi_count,
                        COUNT(CASE WHEN status_reg IN ('18', '18.1', '19', '19.1') THEN 1 END) as aktivasi_count
                    ")
                    ->first();

                $totalBaru = (int) ($monthStats->total_baru ?? 0);
                $aktifBaru = (int) ($monthStats->aktif_baru ?? 0);
                $prosesBaru = (int) ($monthStats->proses_baru ?? 0);
                $batalBaru = (int) ($monthStats->batal_baru ?? 0);

                // Previous month count for trend comparison
                $prevTotalBaru = DB::table('trx_batchjob_register')
                    ->whereBetween('date_create', [$prevStartDate, $prevEndDate])
                    ->count();

                $growthCount = $totalBaru - $prevTotalBaru;
                $growthPercent = $prevTotalBaru > 0 
                    ? round((($totalBaru - $prevTotalBaru) / $prevTotalBaru) * 100, 1) 
                    : ($totalBaru > 0 ? 100 : 0);

                // Total all-time active customers
                $totalSemuaPelangganAktif = DB::table('trx_batchjob_register')
                    ->where('status_reg', '20')
                    ->count();

                // Query langsung dari tabel trx_batchjob_register berdasarkan kolom date_create
                $baseQuery = DB::table('trx_batchjob_register as r')
                    ->where(function ($q) use ($startDate, $endDate, $selectedTahun, $selectedBulan) {
                        $q->whereBetween('r.date_create', [$startDate, $endDate])
                          ->orWhere('r.date_create', 'like', "{$selectedTahun}-{$selectedBulan}%");
                    });

                $hasPelanggan = Schema::hasTable('m_pelanggan');
                $hasBandwith = Schema::hasTable('m_bandwith');
                $hasBandwithKat = Schema::hasTable('m_bandwith_kategori');

                if ($hasPelanggan) {
                    $baseQuery->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk');
                }
                if ($hasBandwith) {
                    $baseQuery->leftJoin('m_bandwith as bw', 'r.kode_bandwith', '=', 'bw.kode_bandwith');
                }
                if ($hasBandwith && $hasBandwithKat) {
                    $baseQuery->leftJoin('m_bandwith_kategori as bwk', 'bw.kode_kategori_bandwith', '=', 'bwk.kode_kategori_bandwith');
                }

                $namaPelangganCol = $hasPelanggan ? "COALESCE(p.nama_penduduk, r.nama_pelanggan, r.nomor_internet)" : "COALESCE(r.nama_pelanggan, r.nomor_internet)";
                $namaPaketCol = ($hasBandwith && $hasBandwithKat)
                    ? "COALESCE(bwk.nama_kategori_bandwith, bwk.alias_nama_kategori, bw.nama_bandwith, r.nama_kategori_bandwith, r.kode_bandwith, 'INTERNET')"
                    : ($hasBandwith ? "COALESCE(bw.nama_bandwith, r.nama_kategori_bandwith, r.kode_bandwith, 'INTERNET')" : "COALESCE(r.nama_kategori_bandwith, r.kode_bandwith, 'INTERNET')");
                $nominalBwCol = $hasBandwith ? "COALESCE(bw.nominal_bandwith, r.nominal_bandwith, '0')" : "COALESCE(r.nominal_bandwith, '0')";

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

                // New system users (tb_pengguna)
                $totalPenggunaSistemBaru = 0;
                if (Schema::hasTable('tb_pengguna')) {
                    $userDateCol = Schema::hasColumn('tb_pengguna', 'date_create') 
                        ? 'date_create' 
                        : (Schema::hasColumn('tb_pengguna', 'created_at') ? 'created_at' : null);
                    
                    if ($userDateCol) {
                        $totalPenggunaSistemBaru = DB::table('tb_pengguna')
                            ->whereBetween($userDateCol, [$startDate, $endDate])
                            ->count();
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
            // Fail-safe default
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
                // Fail-safe default
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
