<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRebootOnuJob;
use App\Models\ActivityLog;
use App\Services\Network\CustomerProvisioningService;
use App\Services\Network\MikrotikService;
use App\Services\Olt\OltConnectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NocController extends Controller
{
    /**
     * Dashboard Utama NOC Command Center
     */
    public function dashboard(Request $request): View
    {
        $totalOlt = Schema::hasTable('m_olt') ? DB::table('m_olt')->count() : 0;
        $totalRouter = Schema::hasTable('routers') ? DB::table('routers')->count() : (Schema::hasTable('m_odp') ? DB::table('m_odp')->count() : 0);
        $totalOdp = $totalRouter;
        $totalPop = Schema::hasTable('m_pop') ? DB::table('m_pop')->where('hide', '!=', '1')->count() : 0;
        $totalPerangkat = Schema::hasTable('m_barang') ? DB::table('m_barang')->where('hide', '!=', '1')->count() : 0;

        // Antrean Aktivasi (Status #18 = Siap Aktivasi, #18.1, #19 = Terjadwal Aktivasi, #19.1 = Reschedule)
        $antreanAktivasi = DB::table('trx_batchjob_register')->whereIn('status_reg', ['18', '18.1', '19', '19.1'])->count();

        // Pelanggan Aktif (Status #20 = Aktif Berlangganan)
        $pelangganAktif = DB::table('trx_batchjob_register')->where('status_reg', '20')->count();

        // Pelanggan Suspend (Status #12 = Suspend Aktif / Terisolir)
        $totalSuspend = DB::table('trx_suspend')->where('status_suspend', '12')->count();

        // Pelanggan Terminasi (Status #14 = Selesai Terminasi)
        $totalTerminasi = DB::table('trx_terminasi')->where('status_terminasi', '14')->count();

        // Tiket Gangguan Aktif (Belum Selesai)
        $tiketGangguan = DB::table('trx_tiket_gangguan')->where('status', '!=', 'Selesai')->count();

        // Recent Aktivasi Queue (Status #18, #18.1, #19, #19.1 yang Siap Diaktivasi/Terjadwal)
        $recentAktivasi = DB::table('view_batchjob')
            ->whereIn('status_reg', ['18', '18.1', '19', '19.1'])
            ->orderBy('date_create', 'desc')
            ->limit(6)
            ->get();

        if ($recentAktivasi->isNotEmpty()) {
            $oltMap = DB::table('trx_batchjob_register')
                ->whereIn('nomor_internet', $recentAktivasi->pluck('nomor_internet'))
                ->pluck('olt', 'nomor_internet');
            foreach ($recentAktivasi as $item) {
                $item->olt = $oltMap[$item->nomor_internet] ?? 'O1';
            }
        }

        // OLT Summary List
        $olts = DB::table('m_olt')->get();

        // POP Summary List
        $pops = DB::table('m_pop')->where('hide', '!=', '1')->get();

        // Master Karyawan / Tim Aktivasi (divisi NOC & role karyawan dari gomsn.users_unified)
        $karyawans = $this->getTeamAktivasi();

        // Master Barang / Perangkat
        $barangs = DB::table('m_barang')->where('hide', '!=', '1')->get();

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

        // Date filter closure for flexible string/datetime matching
        $applyDateFilter = function ($query, $column = 'date_create') use ($startDate, $endDate, $selectedTahun, $selectedBulan) {
            return $query->where(function ($q) use ($column, $startDate, $endDate, $selectedTahun, $selectedBulan) {
                $q->whereBetween($column, [$startDate, $endDate])
                  ->orWhere($column, 'like', "{$selectedTahun}-{$selectedBulan}%")
                  ->orWhere(function ($sub) use ($column, $selectedTahun, $selectedBulan) {
                      $sub->whereMonth($column, (int) $selectedBulan)
                          ->whereYear($column, (int) $selectedTahun);
                  });
            });
        };

        $prevApplyDateFilter = function ($query, $column = 'date_create') use ($prevStartDate, $prevEndDate, $prevMonthDate) {
            $prevYear = $prevMonthDate->format('Y');
            $prevMonth = $prevMonthDate->format('m');
            return $query->where(function ($q) use ($column, $prevStartDate, $prevEndDate, $prevYear, $prevMonth) {
                $q->whereBetween($column, [$prevStartDate, $prevEndDate])
                  ->orWhere($column, 'like', "{$prevYear}-{$prevMonth}%")
                  ->orWhere(function ($sub) use ($column, $prevYear, $prevMonth) {
                      $sub->whereMonth($column, (int) $prevMonth)
                          ->whereYear($column, (int) $prevYear);
                  });
            });
        };

        // New Users KPI for selected month in NOC
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
            'paketBreakdown' => collect([]),
            'recentNewUsers' => collect([]),
        ];

        try {
            $hasTrx = Schema::hasTable('trx_batchjob_register');
            $hasView = Schema::hasTable('view_batchjob');
            $sourceTable = $hasView ? 'view_batchjob' : ($hasTrx ? 'trx_batchjob_register' : null);

            if ($sourceTable) {
                $monthStatsQuery = DB::table($sourceTable);
                $applyDateFilter($monthStatsQuery, 'date_create');
                $monthStats = $monthStatsQuery
                    ->selectRaw("
                        COUNT(*) as total_baru,
                        COUNT(CASE WHEN status_reg = '20' OR status_reg = '20.0' OR status_reg = '20.1' THEN 1 END) as aktif_baru,
                        COUNT(CASE WHEN status_reg IN ('11', '11.1', '12', '13', '13.1', '16', '17', '17.1', '18', '18.1', '19', '19.1') THEN 1 END) as proses_baru,
                        COUNT(CASE WHEN status_reg IN ('14', '15', '23', '23.1') THEN 1 END) as batal_baru
                    ")
                    ->first();

                $totalBaru = (int) ($monthStats->total_baru ?? 0);
                $aktifBaru = (int) ($monthStats->aktif_baru ?? 0);
                $prosesBaru = (int) ($monthStats->proses_baru ?? 0);
                $batalBaru = (int) ($monthStats->batal_baru ?? 0);

                $prevQuery = DB::table($sourceTable);
                $prevApplyDateFilter($prevQuery, 'date_create');
                $prevTotalBaru = $prevQuery->count();

                $growthCount = $totalBaru - $prevTotalBaru;
                $growthPercent = $prevTotalBaru > 0 
                    ? round((($totalBaru - $prevTotalBaru) / $prevTotalBaru) * 100, 1) 
                    : ($totalBaru > 0 ? 100 : 0);

                if ($sourceTable === 'view_batchjob') {
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
                        ->limit(5)
                        ->get();
                } else {
                    $baseQuery = DB::table('trx_batchjob_register as r');
                    $applyDateFilter($baseQuery, 'r.date_create');

                    $hasBandwith = Schema::hasTable('m_bandwith');
                    $hasBandwithKat = Schema::hasTable('m_bandwith_kategori');

                    $hasTrxNamaKat = Schema::hasColumn('trx_batchjob_register', 'nama_kategori_bandwith');
                    $hasTrxNominalBw = Schema::hasColumn('trx_batchjob_register', 'nominal_bandwith');

                    if ($hasBandwith) {
                        $baseQuery->leftJoin('m_bandwith as bw', 'r.kode_bandwith', '=', 'bw.kode_bandwith');
                    }
                    if ($hasBandwith && $hasBandwithKat) {
                        $baseQuery->leftJoin('m_bandwith_kategori as bwk', 'bw.kode_kategori_bandwith', '=', 'bwk.kode_kategori_bandwith');
                    }

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

                    $paketBreakdown = (clone $baseQuery)->select(
                        DB::raw("{$namaPaketCol} as nama_paket"),
                        DB::raw("{$nominalBwCol} as nominal_bandwith"),
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('nama_paket', 'nominal_bandwith')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get();
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
                    'paketBreakdown' => $paketBreakdown,
                ];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching NOC new user stats: ' . $e->getMessage());
        }

        // Index OLT Availability (1..128, occupied vs available)
        $indexOltData = $this->getIndexOltSlots();

        return view('noc.dashboard', [
            'user' => $request->user(),
            'totalOlt' => $totalOlt,
            'totalRouter' => $totalRouter,
            'totalOdp' => $totalOdp,
            'totalPop' => $totalPop,
            'totalPerangkat' => $totalPerangkat,
            'antreanAktivasi' => $antreanAktivasi,
            'pelangganAktif' => $pelangganAktif,
            'totalSuspend' => $totalSuspend,
            'totalTerminasi' => $totalTerminasi,
            'tiketGangguan' => $tiketGangguan,
            'recentAktivasi' => $recentAktivasi,
            'olts' => $olts,
            'pops' => $pops,
            'karyawans' => $karyawans,
            'barangs' => $barangs,
            'indexOltSlots' => $indexOltData['slots'],
            'occupiedIndexOlts' => $indexOltData['occupied'],
            'occupiedMap' => $indexOltData['occupiedMap'],
            'allPorts' => $indexOltData['allPorts'],
            'portStats' => $indexOltData['portStats'],
            'newUserStats' => $newUserStats,
            'monthsList' => $monthsList,
            'availableYears' => $availableYears,
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
        ]);
    }

    /**
     * Helper: Generate & check available Index OLT slots across all GPON ports efficiently
     */
    private function getIndexOltSlots(): array
    {
        // 1. Get occupied index_olt from active/non-terminated customers
        $occupiedRaw = DB::table('trx_batchjob_register')
            ->whereNotNull('index_olt')
            ->where('index_olt', '!=', '')
            ->whereNotIn('status_reg', ['23', '23.1', '15']) // 23 = Terminasi, 15 = Batal Pasang
            ->pluck('index_olt');

        $occupiedMap = [];
        $usedPerPort = [];
        foreach ($occupiedRaw as $raw) {
            $clean = trim($raw);
            if (str_starts_with($clean, 'gpon-olt_')) {
                $clean = str_replace('gpon-olt_', 'gpon-onu_', $clean);
            } elseif (!str_starts_with($clean, 'gpon-onu_') && str_starts_with($clean, '1/')) {
                $clean = 'gpon-onu_' . $clean;
            }
            $occupiedMap[$clean] = true;

            $parts = explode(':', $clean);
            if (isset($parts[0])) {
                $portName = $parts[0];
                $usedPerPort[$portName] = ($usedPerPort[$portName] ?? 0) + 1;
            }
        }

        // 2. All 32 GPON Ports (Slot 1: 1/1/1 s/d 1/1/16, Slot 2: 1/2/1 s/d 1/2/16)
        $allPorts = [
            'gpon-onu_1/1/1', 'gpon-onu_1/1/2', 'gpon-onu_1/1/3', 'gpon-onu_1/1/4',
            'gpon-onu_1/1/5', 'gpon-onu_1/1/6', 'gpon-onu_1/1/7', 'gpon-onu_1/1/8',
            'gpon-onu_1/1/9', 'gpon-onu_1/1/10', 'gpon-onu_1/1/11', 'gpon-onu_1/1/12',
            'gpon-onu_1/1/13', 'gpon-onu_1/1/14', 'gpon-onu_1/1/15', 'gpon-onu_1/1/16',
            'gpon-onu_1/2/1', 'gpon-onu_1/2/2', 'gpon-onu_1/2/3', 'gpon-onu_1/2/4',
            'gpon-onu_1/2/5', 'gpon-onu_1/2/6', 'gpon-onu_1/2/7', 'gpon-onu_1/2/8',
            'gpon-onu_1/2/9', 'gpon-onu_1/2/10', 'gpon-onu_1/2/11', 'gpon-onu_1/2/12',
            'gpon-onu_1/2/13', 'gpon-onu_1/2/14', 'gpon-onu_1/2/15', 'gpon-onu_1/2/16',
        ];

        $portStats = [];
        foreach ($allPorts as $port) {
            $usedCount = $usedPerPort[$port] ?? 0;
            $portStats[$port] = [
                'name' => $port,
                'used' => $usedCount,
                'free' => max(0, 128 - $usedCount),
                'available' => max(0, 128 - $usedCount),
                'total' => 128,
            ];
        }

        return [
            'slots' => [],
            'portStats' => $portStats,
            'allPorts' => $allPorts,
            'occupiedMap' => $occupiedMap,
            'occupied' => array_keys($occupiedMap),
        ];
    }

    /**
     * Topologi & Sistem Informasi Pemetaan Port GPON Pelanggan
     */
    /**
     * Topologi & Sistem Informasi Pemetaan Port GPON Pelanggan
     */
    public function gponTopology(Request $request): View
    {
        $selectedOlt = $request->query('olt', 'all');

        // Smart default slot & port based on selected OLT
        if (!$request->has('slot')) {
            if ($selectedOlt === 'O2') { // Babakan Tarogong
                $selectedSlot = '1';
                $defaultPort = 'gpon-onu_1/1/2';
            } elseif ($selectedOlt === 'O3') { // BBU / Soreang
                $selectedSlot = '2';
                $defaultPort = 'gpon-onu_1/2/1';
            } elseif ($selectedOlt === 'O1') { // Kayu Agung / MSN
                $selectedSlot = '2';
                $defaultPort = 'gpon-onu_1/2/4';
            } else {
                $selectedSlot = '1';
                $defaultPort = 'gpon-onu_1/1/1';
            }
        } else {
            $selectedSlot = $request->query('slot', '1');
            $defaultPort = $selectedSlot == '2' ? 'gpon-onu_1/2/1' : 'gpon-onu_1/1/1';
        }

        $selectedPort = $request->query('port', $defaultPort);

        $olts = DB::table('m_olt')->get();

        // 1. Definisikan Ports per Slot
        $slot1Ports = [];
        for ($i = 1; $i <= 16; $i++) {
            $slot1Ports[] = "gpon-onu_1/1/{$i}";
        }

        $slot2Ports = [];
        for ($i = 1; $i <= 16; $i++) {
            $slot2Ports[] = "gpon-onu_1/2/{$i}";
        }

        $currentSlotPorts = $selectedSlot == '2' ? $slot2Ports : $slot1Ports;

        // 2. Query Pelanggan dengan Filter OLT
        $query = DB::table('view_batchjob')
            ->leftJoin('trx_batchjob_register', 'view_batchjob.nomor_internet', '=', 'trx_batchjob_register.nomor_internet')
            ->whereNotNull('view_batchjob.index_olt')
            ->where('view_batchjob.index_olt', '!=', '')
            ->select(
                'view_batchjob.nomor_internet',
                'view_batchjob.nama_pelanggan',
                'view_batchjob.index_olt',
                'view_batchjob.nama_kategori_bandwith',
                'view_batchjob.nominal_bandwith',
                'view_batchjob.alamat_p',
                'view_batchjob.alamat_pasang',
                'view_batchjob.nomor_hp',
                'view_batchjob.status_reg',
                'view_batchjob.desc_registrasi',
                'view_batchjob.nama_pop',
                'view_batchjob.kode_pop',
                'trx_batchjob_register.olt'
            );

        if ($selectedOlt === 'O1') { // OLT KAYU AGUNG / MSN
            $query->where(function($q) {
                $q->where('trx_batchjob_register.olt', 'MSN')
                  ->orWhere('view_batchjob.kode_pop', 'pop73117')
                  ->orWhere('view_batchjob.nama_pop', 'like', '%Kayu Agung%');
            });
        } elseif ($selectedOlt === 'O2') { // OLT BABAKAN TAROGONG
            $query->where(function($q) {
                $q->where('trx_batchjob_register.olt', 'Bagong')
                  ->orWhere('view_batchjob.kode_pop', 'pop9348')
                  ->orWhere('view_batchjob.nama_pop', 'like', '%Babakan Tarogong%');
            });
        } elseif ($selectedOlt === 'O3') { // OLT BBU / SOREANG
            $query->where(function($q) {
                $q->where('trx_batchjob_register.olt', 'Soreang')
                  ->orWhere('view_batchjob.kode_pop', 'pop26685')
                  ->orWhere('view_batchjob.nama_pop', 'like', '%MediaNet%')
                  ->orWhere('view_batchjob.nama_pop', 'like', '%BBU%');
            });
        }

        $allCustomers = $query->get();

        // 3. Kelompokkan pelanggan berdasarkan Port & Slot
        $portCustomerMap = []; // [port => [slot_num => customer]]
        $portStats = [];       // [port => [used => x, free => y, total => 128, bw => z]]

        foreach ($allCustomers as $cust) {
            $rawIndex = trim($cust->index_olt);
            if (str_starts_with($rawIndex, 'gpon-olt_')) {
                $rawIndex = str_replace('gpon-olt_', 'gpon-onu_', $rawIndex);
            } elseif (!str_starts_with($rawIndex, 'gpon-onu_') && str_starts_with($rawIndex, '1/')) {
                $rawIndex = 'gpon-onu_' . $rawIndex;
            }

            if (str_contains($rawIndex, ':')) {
                $parts = explode(':', $rawIndex);
                $port = trim($parts[0]);
                $slotNum = (int)trim($parts[1]);

                if ($slotNum >= 1 && $slotNum <= 128) {
                    // Cek status pelanggan (exclude terminasi & batal)
                    if (!in_array($cust->status_reg, ['23', '23.1', '15'])) {
                        $portCustomerMap[$port][$slotNum] = $cust;
                    }
                }
            }
        }

        // 4. Hitung statistik untuk semua port di slot 1 & slot 2
        $allPossiblePorts = array_merge($slot1Ports, $slot2Ports);
        foreach ($allPossiblePorts as $port) {
            $connectedList = $portCustomerMap[$port] ?? [];
            $usedCount = count($connectedList);
            $totalBw = 0;
            foreach ($connectedList as $c) {
                $totalBw += (float)($c->nominal_bandwith ?: 0);
            }

            $portStats[$port] = [
                'port' => $port,
                'used' => $usedCount,
                'free' => 128 - $usedCount,
                'total' => 128,
                'utilization' => round(($usedCount / 128) * 100, 1),
                'total_bandwidth' => $totalBw,
            ];
        }

        // 5. Bangun visual matriks 1 s/d 128 untuk $selectedPort yang dipilih
        $matrixSlots = [];
        $connectedCustomersOnPort = [];
        $selectedPortConnected = $portCustomerMap[$selectedPort] ?? [];

        for ($i = 1; $i <= 128; $i++) {
            $cust = $selectedPortConnected[$i] ?? null;
            $slotStatus = 'available';

            if ($cust) {
                if ($cust->status_reg == '20') {
                    $slotStatus = 'active';
                } elseif (in_array($cust->status_reg, ['21', '21.1'])) {
                    $slotStatus = 'suspend';
                } elseif (in_array($cust->status_reg, ['17', '18', '18.1'])) {
                    $slotStatus = 'pending';
                }
                $connectedCustomersOnPort[] = [
                    'slot' => $i,
                    'key' => "{$selectedPort}:{$i}",
                    'customer' => $cust,
                ];
            }

            $matrixSlots[] = [
                'num' => $i,
                'key' => "{$selectedPort}:{$i}",
                'status' => $slotStatus,
                'customer' => $cust,
            ];
        }

        $activePortStats = $portStats[$selectedPort] ?? [
            'port' => $selectedPort,
            'used' => 0,
            'free' => 128,
            'total' => 128,
            'utilization' => 0,
            'total_bandwidth' => 0,
        ];

        // Total Pelanggan per OLT untuk label dropdown
        $oltStats = [
            'O1' => DB::table('trx_batchjob_register')->where('olt', 'MSN')->orWhere('kode_pop', 'pop73117')->count(),
            'O2' => DB::table('trx_batchjob_register')->where('olt', 'Bagong')->orWhere('kode_pop', 'pop9348')->count(),
            'O3' => DB::table('trx_batchjob_register')->where('olt', 'Soreang')->orWhere('kode_pop', 'pop26685')->count(),
            'all' => DB::table('trx_batchjob_register')->whereNotNull('index_olt')->where('index_olt', '!=', '')->count(),
        ];

        return view('noc.gpon', [
            'user' => $request->user(),
            'olts' => $olts,
            'oltStats' => $oltStats,
            'selectedOlt' => $selectedOlt,
            'selectedSlot' => $selectedSlot,
            'selectedPort' => $selectedPort,
            'slot1Ports' => $slot1Ports,
            'slot2Ports' => $slot2Ports,
            'currentSlotPorts' => $currentSlotPorts,
            'portStats' => $portStats,
            'activePortStats' => $activePortStats,
            'matrixSlots' => $matrixSlots,
            'connectedCustomersOnPort' => $connectedCustomersOnPort,
        ]);
    }

    /**
     * Memastikan semua kolom tabel m_olt sudah tersedia di database secara otomatis
     */
    protected function ensureOltTableColumns(): void
    {
        if (Cache::has('schema_ensured_olt_tbl')) {
            return;
        }

        try {
            if (!Schema::hasTable('m_olt')) {
                return;
            }

            $columnsToAdd = [
                'hostname' => ['type' => 'string', 'length' => 150, 'nullable' => true],
                'ip_address' => ['type' => 'string', 'length' => 50, 'nullable' => true],
                'brand' => ['type' => 'string', 'length' => 100, 'nullable' => true],
                'model' => ['type' => 'string', 'length' => 100, 'nullable' => true],
                'kode_pop' => ['type' => 'string', 'length' => 50, 'nullable' => true],
                'protocol' => ['type' => 'string', 'length' => 20, 'nullable' => true, 'default' => 'telnet'],
                'port' => ['type' => 'integer', 'nullable' => true, 'default' => 23],
                'username' => ['type' => 'string', 'length' => 100, 'nullable' => true],
                'password' => ['type' => 'text', 'nullable' => true],
                'enable_password' => ['type' => 'text', 'nullable' => true],
                'snmp_port' => ['type' => 'integer', 'nullable' => true, 'default' => 161],
                'snmp_version' => ['type' => 'string', 'length' => 20, 'nullable' => true, 'default' => 'v2c'],
                'snmp_community' => ['type' => 'string', 'length' => 50, 'nullable' => true, 'default' => 'public'],
                'is_active' => ['type' => 'boolean', 'nullable' => true, 'default' => true],
                'last_sync_at' => ['type' => 'timestamp', 'nullable' => true],
                'last_status' => ['type' => 'string', 'length' => 50, 'nullable' => true],
            ];

            $missing = [];
            foreach ($columnsToAdd as $col => $spec) {
                if (!Schema::hasColumn('m_olt', $col)) {
                    $missing[$col] = $spec;
                }
            }

            if (!empty($missing)) {
                Schema::table('m_olt', function (\Illuminate\Database\Schema\Blueprint $table) use ($missing) {
                    foreach ($missing as $col => $spec) {
                        if ($spec['type'] === 'string') {
                            $column = $table->string($col, $spec['length'] ?? 150)->nullable();
                        } elseif ($spec['type'] === 'text') {
                            $column = $table->text($col)->nullable();
                        } elseif ($spec['type'] === 'integer') {
                            $column = $table->integer($col)->nullable();
                        } elseif ($spec['type'] === 'boolean') {
                            $column = $table->boolean($col)->nullable();
                        } elseif ($spec['type'] === 'timestamp') {
                            $column = $table->timestamp($col)->nullable();
                        }

                        if (isset($spec['default']) && isset($column)) {
                            $column->default($spec['default']);
                        }
                    }
                });
            }

            Cache::forever('schema_ensured_olt_tbl', true);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Auto-ensure m_olt columns notice: " . $e->getMessage());
        }
    }

    /**
     * Manajemen Master OLT, GPON, dan Port PON
     */
    public function olt(Request $request, OltConnectionService $oltService): View
    {
        $this->ensureOltTableColumns();

        $search = $request->query('search');

        $query = DB::table('m_olt');

        // Pengecekan kolom secara aman agar tidak error jika tabel di hosting belum dimigrasi
        $hasKodePop = Schema::hasTable('m_olt') && Schema::hasColumn('m_olt', 'kode_pop');
        $hasBrand = Schema::hasTable('m_olt') && Schema::hasColumn('m_olt', 'brand');
        $hasIpAddress = Schema::hasTable('m_olt') && Schema::hasColumn('m_olt', 'ip_address');

        if ($hasKodePop) {
            $query->leftJoin('m_pop', 'm_olt.kode_pop', '=', 'm_pop.kode_pop')
                  ->select('m_olt.*', 'm_pop.nama_pop');
        } else {
            $query->select('m_olt.*');
        }

        if ($search) {
            $query->where(function($q) use ($search, $hasBrand, $hasIpAddress, $hasKodePop) {
                $q->where('m_olt.name_olt', 'like', "%{$search}%")
                  ->orWhere('m_olt.kode_olt', 'like', "%{$search}%");
                if ($hasBrand) {
                    $q->orWhere('m_olt.brand', 'like', "%{$search}%");
                }
                if ($hasIpAddress) {
                    $q->orWhere('m_olt.ip_address', 'like', "%{$search}%");
                }
                if ($hasKodePop) {
                    $q->orWhere('m_pop.nama_pop', 'like', "%{$search}%");
                }
            });
        }

        $olts = $query->paginate(10)->withQueryString();

        // Attach default values and nama_pop jika belum ada dari query join
        $pops = Schema::hasTable('m_pop') ? DB::table('m_pop')->where('hide', '!=', '1')->get() : collect();
        $popByKodePop = $pops->keyBy('kode_pop');
        $popByKodeOlt = $pops->keyBy('kode_olt');

        foreach ($olts as $olt) {
            $olt->kode_pop = $olt->kode_pop ?? null;
            $olt->ip_address = $olt->ip_address ?? '10.10.10.1';
            $olt->brand = $olt->brand ?? 'ZTE C320';
            $olt->model = $olt->model ?? 'C320';
            $olt->hostname = $olt->hostname ?? $olt->kode_olt;
            $olt->capacity_olt = is_numeric($olt->capacity_olt ?? null) ? (int)$olt->capacity_olt : 8;
            $olt->protocol = $olt->protocol ?? 'telnet';
            $olt->port = $olt->port ?? 23;
            $olt->username = $olt->username ?? 'admin';
            $olt->snmp_port = $olt->snmp_port ?? 161;
            $olt->snmp_version = $olt->snmp_version ?? 'v2c';
            $olt->snmp_community = $olt->snmp_community ?? 'public';

            // Sinkronkan kapasitas port PON dengan master topologi gomsn
            $gomsnOltId = $oltService->resolveGomsnOltId($olt);
            if ($gomsnOltId && Schema::hasTable("gomsn.pon{$gomsnOltId}")) {
                $gPonCount = DB::table("gomsn.pon{$gomsnOltId}")->count();
                if ($gPonCount > 0) {
                    $olt->capacity_olt = $gPonCount;
                }
            }

            if (!isset($olt->nama_pop) || empty($olt->nama_pop)) {
                if (!empty($olt->kode_pop) && isset($popByKodePop[$olt->kode_pop])) {
                    $olt->nama_pop = $popByKodePop[$olt->kode_pop]->nama_pop;
                } elseif (isset($popByKodeOlt[$olt->kode_olt])) {
                    $olt->nama_pop = $popByKodeOlt[$olt->kode_olt]->nama_pop;
                } else {
                    $olt->nama_pop = 'POP Utama MSN';
                }
            }
        }

        // Hitung total OLT & total PON Port & Registered PON
        $totalOlt = DB::table('m_olt')->count();
        $totalPonPort = $olts->sum(fn($o) => is_numeric($o->capacity_olt) ? (int)$o->capacity_olt : 8) ?: 0;
        if ($totalPonPort == 0 && $totalOlt > 0) {
            $totalPonPort = $totalOlt * 8; // fallback default 8 port/olt
        }

        // Hitung status PON aktif / terdaftar per OLT secara agregat (Eliminasi N+1 query)
        $oltCodes = $olts->pluck('kode_olt')->toArray();
        $popCodes = $olts->pluck('kode_pop')->filter()->toArray();

        $registeredCountsRaw = DB::table('trx_batchjob_register')
            ->where(function($q) use ($oltCodes, $popCodes) {
                $q->whereIn('olt', $oltCodes);
                if (!empty($popCodes)) {
                    $q->orWhereIn('kode_pop', $popCodes);
                }
            })
            ->whereNotNull('index_olt')
            ->where('index_olt', '!=', '')
            ->whereNotIn('status_reg', ['23', '23.1', '15'])
            ->select('olt', DB::raw('COUNT(*) as total'))
            ->groupBy('olt')
            ->pluck('total', 'olt')
            ->toArray();

        $registeredPonCounts = [];
        foreach ($olts as $olt) {
            $gomsnOltId = $oltService->resolveGomsnOltId($olt);
            if ($gomsnOltId && Schema::hasTable("gomsn.users{$gomsnOltId}")) {
                $registeredPonCounts[$olt->kode_olt] = (int)DB::table("gomsn.users{$gomsnOltId}")->count();
            } else {
                $registeredPonCounts[$olt->kode_olt] = (int)($registeredCountsRaw[$olt->kode_olt] ?? 0);
            }
        }

        $gpons = Schema::hasTable('m_gpon') ? DB::table('m_gpon')->get() : collect();
        $pons = Schema::hasTable('m_pon') ? DB::table('m_pon')->get() : collect();

        // Ambil data telemetry OLT (menggunakan cached snapshot / baseline instan agar halaman dimuat < 100ms)
        $telemetryMap = [];
        foreach ($olts as $olt) {
            $telemetry = $oltService->getCachedOrBaselineTelemetry($olt);
            if (empty($telemetry['latency']) || (int)$telemetry['latency'] === 8) {
                $telemetry['latency'] = $oltService->measureOltPing($olt);
                $telemetry['message'] = "Online ({$telemetry['latency']} ms)";
            }
            $telemetryMap[$olt->kode_olt] = $telemetry;
        }

        return view('noc.olt', [
            'user' => $request->user(),
            'olts' => $olts,
            'totalOlt' => $totalOlt,
            'totalPonPort' => $totalPonPort,
            'registeredPonCounts' => $registeredPonCounts,
            'telemetryMap' => $telemetryMap,
            'gpons' => $gpons,
            'pons' => $pons,
            'pops' => $pops,
            'search' => $search,
        ]);
    }

    /**
     * Form Tambah OLT Baru
     */
    public function oltCreate(Request $request): View
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk menambah OLT.');
        }

        $this->ensureOltTableColumns();

        $pops = DB::table('m_pop')->where('hide', '!=', '1')->orderBy('nama_pop')->get();

        return view('noc.olt-create', [
            'user' => $request->user(),
            'pops' => $pops,
        ]);
    }

    /**
     * Form Edit OLT
     */
    public function oltEdit(Request $request, string $kode_olt): View
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk mengedit OLT.');
        }

        $this->ensureOltTableColumns();

        $olt = DB::table('m_olt')->where('kode_olt', $kode_olt)->first();

        if (!$olt) {
            abort(404, 'Data OLT tidak ditemukan.');
        }

        $olt->kode_pop = $olt->kode_pop ?? null;
        $olt->ip_address = $olt->ip_address ?? '';
        $olt->brand = $olt->brand ?? '';
        $olt->model = $olt->model ?? '';
        $olt->hostname = $olt->hostname ?? $olt->kode_olt;
        $olt->capacity_olt = $olt->capacity_olt ?? 8;
        $olt->protocol = $olt->protocol ?? 'telnet';
        $olt->port = $olt->port ?? 23;
        $olt->username = $olt->username ?? '';
        $olt->snmp_port = $olt->snmp_port ?? 161;
        $olt->snmp_version = $olt->snmp_version ?? 'v2c';
        $olt->snmp_community = $olt->snmp_community ?? 'public';

        $pops = DB::table('m_pop')->where('hide', '!=', '1')->orderBy('nama_pop')->get();

        return view('noc.olt-edit', [
            'user' => $request->user(),
            'olt' => $olt,
            'pops' => $pops,
        ]);
    }

    /**
     * Detail Traffic per Port PON (Setiap port memiliki grafik traffic sendiri)
     */
    public function oltPonTraffic(Request $request, string $kode_olt, OltConnectionService $oltService): View|JsonResponse
    {
        $this->ensureOltTableColumns();

        $olt = DB::table('m_olt')->where('kode_olt', $kode_olt)->first();
        if (!$olt && is_numeric($kode_olt)) {
            $olt = DB::table('m_olt')->where('kode_olt', 'O' . $kode_olt)->first();
        }
        if (!$olt) {
            abort(404, "Data OLT {$kode_olt} tidak ditemukan.");
        }

        if (!empty($olt->kode_pop)) {
            $pop = DB::table('m_pop')->where('kode_pop', $olt->kode_pop)->first();
            $olt->nama_pop = $pop->nama_pop ?? $olt->kode_pop;
        } else {
            $olt->nama_pop = 'POP Utama MSN';
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $isForce = $request->boolean('refresh') || $request->query('sync') === '1' || $request->query('force') === '1';

        $sKey = $startDate ? md5($startDate) : 'def';
        $eKey = $endDate ? md5($endDate) : 'def';
        $cacheKey = "noc_pon_traffic_view_{$olt->kode_olt}_{$sKey}_{$eKey}";
        if ($isForce) {
            Cache::forget($cacheKey);
        }

        $ponTelemetry = Cache::remember($cacheKey, 120, function () use ($oltService, $olt, $startDate, $endDate) {
            return $oltService->getOltPonPortsTelemetry($olt, $startDate, $endDate);
        });

        if ($request->expectsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success' => true,
                'ponTelemetry' => $ponTelemetry,
                'oltTelemetry' => $oltService->getOltRealTelemetry($olt, $startDate, $endDate),
                'updated_at' => now()->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
            ]);
        }

        $allOlts = DB::table('m_olt')->orderBy('kode_olt')->get();

        return view('noc.olt-pon-traffic', [
            'user' => $request->user(),
            'olt' => $olt,
            'allOlts' => $allOlts,
            'ponTelemetry' => $ponTelemetry,
            'startDate' => $startDate ? Carbon::parse($startDate)->format('Y-m-d\TH:i') : now()->subDays(7)->startOfDay()->format('Y-m-d\TH:i'),
            'endDate' => $endDate ? Carbon::parse($endDate)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i'),
        ]);
    }

    /**
     * Menampilkan Daftar ONU (Optical Network Unit) yang terhubung ke Port PON tertentu
     */
    public function oltPonOnuList(Request $request, string $kode_olt, int $pon_index, OltConnectionService $oltService): View|JsonResponse
    {
        $this->ensureOltTableColumns();

        $olt = DB::table('m_olt')->where('kode_olt', $kode_olt)->first();
        if (!$olt && is_numeric($kode_olt)) {
            $olt = DB::table('m_olt')->where('kode_olt', 'O' . $kode_olt)->first();
        }
        if (!$olt) {
            abort(404, "Data OLT {$kode_olt} tidak ditemukan.");
        }

        if (!empty($olt->kode_pop)) {
            $pop = DB::table('m_pop')->where('kode_pop', $olt->kode_pop)->first();
            $olt->nama_pop = $pop->nama_pop ?? $olt->kode_pop;
        } else {
            $olt->nama_pop = 'POP Utama MSN';
        }

        $isForce = $request->boolean('refresh') || $request->query('sync') === '1' || $request->query('force') === '1';
        $cacheKey = "noc_pon_onu_list_view_{$olt->kode_olt}_{$pon_index}";
        if ($isForce) {
            Cache::forget($cacheKey);
        }

        $onuData = Cache::remember($cacheKey, 120, function () use ($oltService, $olt, $pon_index, $isForce) {
            return $oltService->getPonOnuList($olt, $pon_index, $isForce);
        });

        if ($request->expectsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success' => true,
                'onuData' => $onuData,
                'updated_at' => now()->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
            ]);
        }

        $allOlts = DB::table('m_olt')->orderBy('kode_olt')->get();

        return view('noc.olt-pon-onu-list', [
            'user' => $request->user(),
            'olt' => $olt,
            'allOlts' => $allOlts,
            'ponIndex' => $pon_index,
            'onuData' => $onuData,
        ]);
    }

    /**
     * Jalankan perintah CLI OLT terminal langsung:
     * show pon power attenuation gpon-onu_1/{slot}/{port}:{onuId}
     */
    public function checkOnuAttenuation(Request $request, string $kode_olt, OltConnectionService $oltService): JsonResponse
    {
        $olt = DB::table('m_olt')->where('kode_olt', $kode_olt)->first();
        if (!$olt) {
            return response()->json(['success' => false, 'message' => 'OLT tidak ditemukan.'], 404);
        }

        $interface = $request->query('interface') ?? $request->input('interface');
        if (!$interface) {
            return response()->json(['success' => false, 'message' => 'Interface ONU wajib disertakan (contoh: gpon-onu_1/1/1:1).'], 400);
        }

        $result = $oltService->getOnuAttenuation($olt, $interface);
        return response()->json($result);
    }

    /**
     * Store / Update OLT
     */
    public function storeOlt(Request $request): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk menambah atau mengedit OLT.');
        }

        $this->ensureOltTableColumns();

        $request->validate([
            'name_olt' => 'required|string|max:150',
            'kode_olt' => 'nullable|string|max:50',
            'hostname' => 'nullable|string|max:100',
            'ip_address' => 'required|string|max:50',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'kode_pop' => 'nullable|string|max:50',
            'capacity_olt' => 'nullable|integer',
            'snmp_port' => 'nullable|integer',
            'snmp_version' => 'nullable|string|max:10',
            'snmp_community' => 'nullable|string|max:50',
            'protocol' => 'nullable|string|in:telnet,ssh',
            'port' => 'nullable|integer',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'note_olt' => 'nullable|string',
        ]);

        // Generate kode_olt jika kosong berdasarkan hostname atau name
        $kodeOlt = $request->kode_olt;
        if (empty($kodeOlt)) {
            $kodeOlt = !empty($request->hostname) ? strtoupper(str_replace(' ', '-', $request->hostname)) : 'OLT-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $request->name_olt), 0, 10));
        }

        $allData = [
            'name_olt' => $request->name_olt,
            'hostname' => $request->hostname ?: $kodeOlt,
            'ip_address' => $request->ip_address,
            'brand' => $request->brand,
            'model' => $request->model,
            'kode_pop' => $request->kode_pop,
            'capacity_olt' => $request->capacity_olt ?? 8,
            'snmp_port' => $request->snmp_port ?? 161,
            'snmp_version' => $request->snmp_version ?? 'v2c',
            'snmp_community' => $request->snmp_community ?? 'public',
            'protocol' => $request->protocol ?? 'telnet',
            'port' => $request->port ?? ($request->protocol === 'ssh' ? 22 : 23),
            'username' => $request->username,
            'note_olt' => $request->note_olt,
            'kode_w' => $request->kode_w ?? 'W1',
        ];

        // Enkripsi password jika diisi
        if ($request->filled('password')) {
            $allData['password'] = Crypt::encryptString($request->password);
        }

        if ($request->filled('enable_password')) {
            $allData['enable_password'] = Crypt::encryptString($request->enable_password);
        }

        // Filter hanya kolom yang benar-benar ada di tabel m_olt database saat ini
        $existingColumns = Schema::hasTable('m_olt') ? Schema::getColumnListing('m_olt') : [];
        $data = [];
        foreach ($allData as $col => $val) {
            if (empty($existingColumns) || in_array($col, $existingColumns)) {
                $data[$col] = $val;
            }
        }

        // Cek apakah data OLT sudah ada (berdasarkan kode_olt atau id)
        $existing = null;
        if ($request->filled('id') && Schema::hasColumn('m_olt', 'id')) {
            $existing = DB::table('m_olt')->where('id', $request->id)->first();
        }
        if (!$existing && !empty($kodeOlt)) {
            $existing = DB::table('m_olt')->where('kode_olt', $kodeOlt)->first();
        }

        if ($existing) {
            DB::table('m_olt')->where('kode_olt', $existing->kode_olt)->update($data);
        } else {
            $data['kode_olt'] = $kodeOlt;
            DB::table('m_olt')->insert($data);
        }

        if ($request->input('action') === 'create_and_another') {
            return redirect()->route('noc.olt.create')->with('success', "OLT {$request->name_olt} berhasil disimpan! Silakan input OLT berikutnya.");
        }

        return redirect()->route('noc.olt')->with('success', "Data OLT {$request->name_olt} berhasil disimpan!");
    }

    /**
     * Hapus OLT
     */
    public function deleteOlt(Request $request, string $kode_olt): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk menghapus OLT.');
        }

        // Cek jika ada pelanggan yang terhubung
        $attachedCount = DB::table('trx_batchjob_register')
            ->where('olt', $kode_olt)
            ->whereNotNull('index_olt')
            ->where('index_olt', '!=', '')
            ->whereNotIn('status_reg', ['23', '23.1', '15'])
            ->count();

        if ($attachedCount > 0) {
            return redirect()->route('noc.olt')->with('error', "Gagal menghapus OLT {$kode_olt}. Terdapat {$attachedCount} pelanggan aktif yang masih terhubung pada OLT ini.");
        }

        DB::table('m_olt')->where('kode_olt', $kode_olt)->delete();

        return redirect()->route('noc.olt')->with('success', "Data OLT {$kode_olt} berhasil dihapus.");
    }

    /**
     * AJAX: Test Koneksi ke OLT (Ping / Socket Handshake)
     */
    public function testOltConnection(Request $request, OltConnectionService $oltService): JsonResponse
    {
        $request->validate([
            'ip_address' => 'required|string',
            'port' => 'nullable|integer',
            'protocol' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
        ]);

        $ip = trim($request->ip_address);
        $port = (int)($request->port ?: ($request->protocol === 'ssh' ? 22 : 23));
        $protocol = $request->protocol ?: 'telnet';

        $res = $oltService->testConnection([
            'ip_address' => $ip,
            'port' => $port,
            'protocol' => $protocol,
            'username' => $request->username,
            'password' => $request->password,
            'enable_password' => $request->enable_password,
        ]);

        return response()->json($res, 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * AJAX: Ambil Real-Time Telemetry & Traffic dari OLT Fisik
     */
    public function getOltTelemetry(Request $request, OltConnectionService $oltService): JsonResponse
    {
        $request->validate([
            'kode_olt' => 'required|string',
        ]);

        $olt = DB::table('m_olt')->where('kode_olt', $request->kode_olt)->first();
        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => 'OLT tidak ditemukan.',
            ], 404);
        }

        $startDate = $request->input('start_date') ?: $request->query('start_date');
        $endDate = $request->input('end_date') ?: $request->query('end_date');

        $data = $oltService->getOltRealTelemetry($olt, $startDate, $endDate, true);

        return response()->json($data, 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * AJAX: Live Sync Status Port GPON dari OLT Fisik
     */
    public function syncLiveGpon(Request $request, OltConnectionService $oltService): JsonResponse
    {
        $request->validate([
            'kode_olt' => 'required|string',
            'port' => 'required|string',
        ]);

        $olt = DB::table('m_olt')->where('kode_olt', $request->kode_olt)->first();

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => 'Data OLT tidak ditemukan di database.',
            ], 404);
        }

        $res = $oltService->getLivePortSlots($olt, $request->port);

        // Update timestamp sync pada tabel m_olt jika berhasil
        if ($res['success']) {
            DB::table('m_olt')->where('kode_olt', $request->kode_olt)->update([
                'last_sync_at' => now(),
                'last_status' => 'online',
            ]);
        }

        return response()->json($res);
    }

    /**
     * AJAX: Scan ONU Unconfigured (Modem Baru) dari OLT
     */
    public function scanUncfgOnu(Request $request, OltConnectionService $oltService): JsonResponse
    {
        $request->validate([
            'kode_olt' => 'required|string',
        ]);

        $olt = DB::table('m_olt')->where('kode_olt', $request->kode_olt)->first();

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => 'Data OLT tidak ditemukan.',
            ], 404);
        }

        $res = $oltService->scanUnconfiguredOnu($olt);

        return response()->json($res);
    }

    /**
     * Store PON Port Baru
     */
    public function storePon(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_pon' => 'required|string|max:50',
            'kode_gpon' => 'required|string|max:50',
            'name_pon' => 'required|string|max:150',
            'capacity_pon' => 'nullable|integer',
            'note_pon' => 'nullable|string',
        ]);

        DB::table('m_pon')->updateOrInsert(
            ['kode_pon' => $request->kode_pon],
            [
                'kode_gpon' => $request->kode_gpon,
                'name_pon' => $request->name_pon,
                'capacity_pon' => $request->capacity_pon ?? 64,
                'note_pon' => $request->note_pon,
            ]
        );

        return redirect()->route('noc.olt')->with('success', 'Data PON Port berhasil disimpan!');
    }

    /**
     * Manajemen Master ODP (Optical Distribution Point)
     */
    public function odp(Request $request): View
    {
        $search = $request->query('search');

        $query = DB::table('m_odp');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name_odp', 'like', "%{$search}%")
                  ->orWhere('kode_odp', 'like', "%{$search}%")
                  ->orWhere('note_odp', 'like', "%{$search}%");
            });
        }
        $odps = $query->paginate(15)->withQueryString();
        $pons = Schema::hasTable('m_pon') ? DB::table('m_pon')->get() : collect();

        return view('noc.odp', [
            'user' => $request->user(),
            'odps' => $odps,
            'pons' => $pons,
            'search' => $search,
        ]);
    }

    /**
     * Store ODP Baru
     */
    public function storeOdp(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_odp' => 'required|string|max:50',
            'name_odp' => 'required|string|max:150',
            'kode_pon' => 'nullable|string|max:50',
            'capacity_odp' => 'nullable|integer',
            'note_odp' => 'nullable|string',
        ]);

        DB::table('m_odp')->updateOrInsert(
            ['kode_odp' => $request->kode_odp],
            [
                'name_odp' => $request->name_odp,
                'kode_pon' => $request->kode_pon,
                'capacity_odp' => $request->capacity_odp ?? 8,
                'note_odp' => $request->note_odp,
            ]
        );

        return redirect()->route('noc.odp')->with('success', 'Data ODP berhasil disimpan!');
    }

    /**
     * Manajemen Point of Presence (POP)
     */
    public function pop(Request $request): View
    {
        $search = $request->query('search');

        $query = DB::table('m_pop')->where('hide', '!=', '1');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pop', 'like', "%{$search}%")
                  ->orWhere('kode_pop', 'like', "%{$search}%")
                  ->orWhere('desc_pop', 'like', "%{$search}%");
            });
        }
        $pops = $query->paginate(15)->withQueryString();

        // Hitung statistik pelanggan per POP
        $popCustomerCounts = DB::table('trx_batchjob_register')
            ->whereNotNull('kode_pop')
            ->select('kode_pop', DB::raw('count(*) as total'))
            ->groupBy('kode_pop')
            ->pluck('total', 'kode_pop')
            ->toArray();

        return view('noc.pop', [
            'user' => $request->user(),
            'pops' => $pops,
            'popCustomerCounts' => $popCustomerCounts,
            'search' => $search,
        ]);
    }

    /**
     * Store POP Baru
     */
    public function storePop(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_pop' => 'required|string|max:50',
            'nama_pop' => 'required|string|max:150',
            'desc_pop' => 'nullable|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        DB::table('m_pop')->updateOrInsert(
            ['kode_pop' => $request->kode_pop],
            [
                'nama_pop' => $request->nama_pop,
                'desc_pop' => $request->desc_pop,
                'date_create' => $now,
                'user_create' => $currentUser,
                'hide' => '0',
            ]
        );

        return redirect()->route('noc.pop')->with('success', 'Data POP berhasil disimpan!');
    }

    /**
     * Manajemen Master Wilayah Perangkat Jaringan (m_wilayah_perangkat)
     */
    public function wilayahPerangkat(Request $request): View
    {
        $search = $request->query('search');

        $query = DB::table('m_wilayah_perangkat');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name_w', 'like', "%{$search}%")
                  ->orWhere('kode_w', 'like', "%{$search}%")
                  ->orWhere('note_w', 'like', "%{$search}%");
            });
        }
        $wilayahs = $query->paginate(10)->withQueryString();

        // Hitung total OLT per wilayah
        $oltCounts = DB::table('m_olt')
            ->select('kode_w', DB::raw('count(*) as total'))
            ->groupBy('kode_w')
            ->pluck('total', 'kode_w')
            ->toArray();

        return view('noc.wilayah', [
            'user' => $request->user(),
            'wilayahs' => $wilayahs,
            'oltCounts' => $oltCounts,
            'search' => $search,
        ]);
    }

    /**
     * Store Wilayah Perangkat Baru
     */
    public function storeWilayahPerangkat(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_w' => 'required|string|max:50',
            'name_w' => 'required|string|max:150',
            'capacity_w' => 'nullable|string',
            'note_w' => 'nullable|string',
        ]);

        DB::table('m_wilayah_perangkat')->updateOrInsert(
            ['kode_w' => $request->kode_w],
            [
                'kode_h' => 'M',
                'name_w' => $request->name_w,
                'capacity_w' => $request->capacity_w ?: '-',
                'note_w' => $request->note_w ?: '-',
            ]
        );

        return redirect()->route('noc.wilayah')->with('success', 'Data Wilayah Perangkat berhasil disimpan!');
    }

    /**
     * Provisioning & Permintaan Aktivasi Jaringan Pelanggan
     */
    public function aktivasi(Request $request): View
    {
        $search = $request->query('search');
        $statusTab = $request->query('status', 'siap_aktivasi'); // 'siap_aktivasi' atau 'riwayat_aktif'

        $query = DB::table('view_batchjob');

        if ($statusTab === 'riwayat_aktif') {
            $query->where('status_reg', '20');
        } else {
            // Default Permintaan Antrean Aktivasi (#18 Selesai Instalasi & #19 Jadwal Aktivasi Terbit)
            $statusTab = 'siap_aktivasi';
            $query->whereIn('status_reg', ['18', '18.1', '19', '19.1']);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhere('ont_us', 'like', "%{$search}%");
            });
        }

        // Hitung count per tab
        $countSiapAktivasi = DB::table('view_batchjob')->whereIn('status_reg', ['18', '18.1', '19', '19.1'])->count();
        $countRiwayatAktif = DB::table('view_batchjob')->where('status_reg', '20')->count();
        $pelanggans = $query->orderBy('date_create', 'desc')->paginate(15)->withQueryString();

        if ($pelanggans->isNotEmpty()) {
            $oltMap = DB::table('trx_batchjob_register')
                ->whereIn('nomor_internet', $pelanggans->pluck('nomor_internet'))
                ->pluck('olt', 'nomor_internet');
            foreach ($pelanggans as $p) {
                $p->olt = $oltMap[$p->nomor_internet] ?? 'O1';
            }
        }

        $olts = DB::table('m_olt')->get();
        $odps = DB::table('m_odp')->get();
        $pops = DB::table('m_pop')->where('hide', '!=', '1')->get();
        $karyawans = $this->getTeamAktivasi();
        $barangs = DB::table('m_barang')->where('hide', '!=', '1')->get();
        $routers = Schema::hasTable('routers') ? DB::table('routers')->where('is_active', 1)->get() : collect();
        $indexOltData = $this->getIndexOltSlots();

        return view('noc.aktivasi', [
            'user' => $request->user(),
            'pelanggans' => $pelanggans,
            'olts' => $olts,
            'odps' => $odps,
            'pops' => $pops,
            'karyawans' => $karyawans,
            'barangs' => $barangs,
            'routers' => $routers,
            'indexOltSlots' => $indexOltData['slots'],
            'occupiedIndexOlts' => $indexOltData['occupied'],
            'occupiedMap' => $indexOltData['occupiedMap'],
            'allPorts' => $indexOltData['allPorts'],
            'portStats' => $indexOltData['portStats'],
            'countSiapAktivasi' => $countSiapAktivasi,
            'countRiwayatAktif' => $countRiwayatAktif,
            'search' => $search,
            'statusTab' => $statusTab,
        ]);
    }

    /**
     * Jadwalkan Aktivasi Layanan (Status #18 -> #19 / #19.1)
     */
    public function storeScheduleAktivasi(Request $request, string $nomorInternet): RedirectResponse
    {
        $request->validate([
            'jadwal_aktivasi' => 'required|date',
            'waktu_aktivasi' => 'nullable|string',
            'team_aktivasi' => 'nullable',
            'kode_pop' => 'nullable|string',
            'media_akses' => 'nullable|string',
            'olt' => 'nullable|string',
            'index_olt' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        $teamAktivasi = is_array($request->team_aktivasi) ? implode(', ', $request->team_aktivasi) : ($request->team_aktivasi ?? '');

        // Check if reschedule
        $currentCust = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first();
        $isReschedule = $currentCust && in_array((string)$currentCust->status_reg, ['19', '19.1']);
        $newStatus = $isReschedule ? '19.1' : '19';
        $statusLabel = $isReschedule ? 'Reschedule Aktivasi' : 'Jadwal Aktivasi Terbit';

        $updateData = [
            'status_reg' => $newStatus,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

        if ($request->filled('kode_pop')) {
            $updateData['kode_pop'] = $request->kode_pop;
        }
        if ($request->filled('media_akses')) {
            $updateData['media_akses'] = $request->media_akses;
            if ($request->media_akses === 'PTP FO') {
                $updateData['index_olt'] = '';
            } elseif ($request->filled('index_olt')) {
                $idx = trim($request->index_olt);
                if (str_starts_with($idx, 'gpon-olt_')) {
                    $idx = str_replace('gpon-olt_', 'gpon-onu_', $idx);
                } elseif (!str_starts_with($idx, 'gpon-onu_') && str_starts_with($idx, '1/')) {
                    $idx = 'gpon-onu_' . $idx;
                }
                $updateData['index_olt'] = $idx;
            }
        }
        if ($request->filled('olt')) {
            $updateData['olt'] = $request->olt;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($updateData);

        // Add log
        if (Schema::hasTable('trx_batchjob_register_log')) {
            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => $newStatus,
                'kat_log' => '19',
                'note_schedule' => $request->catatan ? "JADWAL AKTIVASI: {$request->catatan}" : "Penjadwalan aktivasi pada {$request->jadwal_aktivasi} jam {$request->waktu_aktivasi} (Team: {$teamAktivasi})",
                'date_schedule' => $request->jadwal_aktivasi,
                'time_schedule' => $request->waktu_aktivasi ?: now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);
        }

        // Update trx_instalasi
        if (Schema::hasTable('trx_instalasi')) {
            DB::table('trx_instalasi')
                ->updateOrInsert(
                    ['nomor_internet' => $nomorInternet],
                    [
                        'kode_instalasi' => 'INS-' . $nomorInternet,
                        'aktivasi_date_start' => $request->jadwal_aktivasi,
                        'aktivasi_time' => $request->waktu_aktivasi ?: now()->format('H:i:s'),
                        'aktivasi_team' => $teamAktivasi,
                        'aktivasi_note' => $request->catatan,
                        'date_update' => $now,
                        'user_update' => $currentUser,
                    ]
                );
        }

        return redirect()->back()->with('success', "Jadwal aktivasi untuk pelanggan {$nomorInternet} berhasil disimpan ({$statusLabel})!");
    }

    /**
     * Report Hasil Aktivasi / Pemasangan ONT oleh NOC & Otomatis Aktivasi/Reboot di Background
     */
    public function storeReportAktivasi(Request $request, string $nomorInternet): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        $teamAktivasi = is_array($request->team_aktivasi) ? implode(', ', $request->team_aktivasi) : ($request->team_aktivasi ?? '');

        // Upload Bukti Aktivasi Foto
        $fileName = null;
        if ($request->hasFile('foto_aktivasi')) {
            $file = $request->file('foto_aktivasi');
            if ($file && $file->isValid()) {
                $rawExt = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $ext = in_array($rawExt, ['jpg', 'jpeg', 'png', 'webp', 'pdf']) ? $rawExt : 'jpg';
                $fileName = 'AKTIVASI_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nomorInternet) . '_' . time() . '.' . $ext;
                $destinationPath = public_path('uploads/registrasi');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $fileName);
            }
        }

        // Ambil data pelanggan saat ini untuk kredensial PPPoE
        $currentCust = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first();
        $ontUs = $request->filled('pppoe_username') ? $request->pppoe_username : ((!empty($currentCust->ont_us)) ? $currentCust->ont_us : substr($nomorInternet, 0, 10));
        $ontPs = $request->filled('pppoe_password') ? $request->pppoe_password : ((!empty($currentCust->ont_ps)) ? $currentCust->ont_ps : (string)rand(100000, 999999));

        // 1. Update trx_batchjob_register -> Langsung Aktif (#20) karena aktivasi & reboot dieksekusi otomatis di background
        $normalizedIndexOlt = '';
        if ($request->media_akses !== 'PTP FO' && $request->filled('index_olt')) {
            $normalizedIndexOlt = trim($request->index_olt);
            if (str_starts_with($normalizedIndexOlt, 'gpon-olt_')) {
                $normalizedIndexOlt = str_replace('gpon-olt_', 'gpon-onu_', $normalizedIndexOlt);
            } elseif (!str_starts_with($normalizedIndexOlt, 'gpon-onu_') && str_starts_with($normalizedIndexOlt, '1/')) {
                $normalizedIndexOlt = 'gpon-onu_' . $normalizedIndexOlt;
            }
        }

        $updateData = [
            'status_reg' => '20', // Pelanggan Aktif Online
            'kode_pop' => $request->kode_pop,
            'media_akses' => $request->media_akses,
            'index_olt' => $normalizedIndexOlt,
            'note_request' => $request->sn_modem ?: ($request->catatan_aktivasi ?: $request->catatan),
            'ont_us' => $ontUs,
            'ont_ps' => $ontPs,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

        if (Schema::hasColumn('trx_batchjob_register', 'pppoe_username')) {
            $updateData['pppoe_username'] = $ontUs;
        }
        if (Schema::hasColumn('trx_batchjob_register', 'pppoe_password')) {
            $updateData['pppoe_password'] = $ontPs;
        }

        if ($request->filled('router_id')) {
            $updateData['router_id'] = $request->router_id;
        }
        if ($request->filled('olt')) {
            $updateData['olt'] = $request->olt;
        }
        if ($request->filled('sn_modem')) {
            $updateData['note_request'] = substr($request->sn_modem, 0, 50);
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($updateData);

        // 2. Update trx_instalasi (doc_aktivasi & tanggal selesai aktivasi)
        $instalasiData = [
            'kode_instalasi' => 'INS-' . $nomorInternet,
            'aktivasi_date_start' => $request->jadwal_aktivasi ?: now()->format('Y-m-d'),
            'aktivasi_date_finish' => now()->format('Y-m-d'),
            'aktivasi_time' => $request->waktu_aktivasi ?: now()->format('H:i:s'),
            'aktivasi_team' => $teamAktivasi,
            'aktivasi_note_finish' => $request->catatan_aktivasi ?: ($request->catatan ?: $request->sn_modem),
            'date_create' => $now,
            'user_create' => $currentUser,
            'date_update' => $now,
            'user_update' => $currentUser,
            'hide' => '0',
        ];
        if ($fileName) {
            $instalasiData['doc_aktivasi'] = $fileName;
        }

        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                $instalasiData
            );

        // 3. Simpan Perangkat / Peralatan yang digunakan
        if ($request->filled('perangkat_json') && Schema::hasTable('trx_instalasi_barang')) {
            $perangkatList = json_decode($request->perangkat_json, true);
            if (is_array($perangkatList) && count($perangkatList) > 0) {
                foreach ($perangkatList as $p) {
                    $kodeBarang = is_array($p) ? ($p['kode_barang'] ?? ($p['nama'] ?? '')) : (string)$p;
                    $jumlah = is_array($p) ? ($p['jumlah'] ?? 1) : 1;
                    if ($kodeBarang) {
                        // Check if valid kode_barang or lookup by name
                        $validBarang = DB::table('m_barang')->where('kode_barang', $kodeBarang)->first();
                        if (!$validBarang) {
                            $validBarang = DB::table('m_barang')->where('nama_barang', 'like', "%{$kodeBarang}%")->first();
                        }
                        if ($validBarang) {
                            DB::table('trx_instalasi_barang')->insert([
                                'kode_inst_barang' => $nomorInternet . '-' . substr(md5($validBarang->kode_barang . microtime()), 0, 8),
                                'nomor_internet' => $nomorInternet,
                                'kode_barang' => $validBarang->kode_barang,
                                'jumlah_barang' => (int)$jumlah,
                                'status_instalasi_barang' => '11',
                                'note_instalasi_barang' => 'Aktivasi NOC',
                                'date_create' => $now,
                                'user_create' => $currentUser,
                                'date_update' => $now,
                                'user_update' => $currentUser,
                                'hide' => '0',
                            ]);
                        }
                    }
                }
            }
        }

        // 4. Log: Report Aktivasi & Otomatis Reboot/Aktivasi Background
        // 4. Eksekusi Live Sinkronisasi ke MikroTik Router (Buat / Update PPPoE Secret)
        $mikrotikResult = ['success' => false, 'message' => 'Router MikroTik tidak dipilih'];
        $routerName = '';
        $mikrotikService = new MikrotikService();

        if ($request->filled('router_id')) {
            $rObj = DB::table('routers')->where('id', $request->router_id)->first();
            if ($rObj) {
                $routerName = $rObj->name;
                $pass = $rObj->password ?? '';
                try {
                    $pass = Crypt::decryptString($pass);
                } catch (\Exception $e) {}
                $mikrotikService->setRouter([
                    'name' => $rObj->name,
                    'host' => $rObj->host,
                    'port' => (int)($rObj->port ?: 18735),
                    'username' => $rObj->username,
                    'password' => $pass,
                ]);
            }
        }
        if (empty($routerName)) {
            $routerName = $mikrotikService->getRouterDisplayName();
        }

        if (!empty($ontUs)) {
            $customerName = $currentCust->nama_pelanggan ?? $nomorInternet;
            $secretComment = $request->filled('pppoe_comment') 
                ? $request->pppoe_comment 
                : "Aktivasi - {$customerName} ({$nomorInternet})";

            $mikrotikResult = $mikrotikService->createOrUpdateSecret([
                'name' => $ontUs,
                'password' => $ontPs,
                'profile' => $request->ppp_profile ?: 'default',
                'service' => 'pppoe',
                'local_address' => $request->local_address ?: '',
                'remote_address' => $request->remote_address ?: '',
                'comment' => $secretComment,
                'disabled' => 'no',
            ]);

            if ($mikrotikResult['success']) {
                $mikrotikService->kickActiveConnection($ontUs);
            }
        }

        // 4a. Eksekusi Remote Reboot OLT ONU jika port dan OLT diisi
        if (!empty($normalizedIndexOlt) && in_array($request->media_akses, ['FTTH', 'FTTH MSN', 'GPON'])) {
            try {
                $oltService = app(OltConnectionService::class);
                $oltService->rebootOnu($normalizedIndexOlt, $request->olt);
            } catch (\Throwable $oltErr) {
                \Illuminate\Support\Facades\Log::warning("OLT Reboot on aktivasi failed for {$nomorInternet}: " . $oltErr->getMessage());
            }
        }

        // 5. Log: Report Aktivasi & Otomatis Reboot/Aktivasi Background
        if (Schema::hasTable('trx_batchjob_register_log')) {
            // Log Report
            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => '19',
                'kat_log' => '19',
                'note_schedule' => "REPORT AKTIVASI SELESAI: OLT: {$request->olt}, Index: {$normalizedIndexOlt}, SN: {$request->sn_modem} (Team: {$teamAktivasi})",
                'date_schedule' => $request->jadwal_aktivasi ?: now()->format('Y-m-d'),
                'time_schedule' => $request->waktu_aktivasi ?: now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);

            // Log Otomatis Reboot & Aktivasi PPPoE (#20)
            $routerInfo = $routerName ? ", Router: {$routerName}" : '';
            $profileInfo = $request->filled('ppp_profile') ? ", Profile: {$request->ppp_profile}" : '';
            $remoteInfo = $request->filled('remote_address') ? ", IP: {$request->remote_address}" : '';
            $commentInfo = $request->filled('pppoe_comment') ? " ({$request->pppoe_comment})" : '';
            $statusMikrotik = $mikrotikResult['success'] ? 'PPPoE Terkonfigurasi' : 'MikroTik Gagal: ' . $mikrotikResult['message'];

            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => '20',
                'kat_log' => '20',
                'note_schedule' => "REBOOT & AKTIVASI OTOMATIS: OLT: {$request->olt}, Index: {$normalizedIndexOlt}, SN Modem: {$request->sn_modem}, User PPPoE: {$ontUs}{$routerInfo}{$profileInfo}{$remoteInfo}{$commentInfo} - [{$statusMikrotik}] Layanan AKTIF (#20)",
                'date_schedule' => now()->format('Y-m-d'),
                'time_schedule' => now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);
        }

        // 5b. Activity Log Router (tabel activity_logs)
        if (Schema::hasTable('activity_logs')) {
            try {
                \App\Models\ActivityLog::record([
                    'user_id' => $currentUser,
                    'customer_id' => $nomorInternet,
                    'action' => 'activate',
                    'old_status' => $currentCust->status_reg ?? '19',
                    'new_status' => '20',
                    'description' => "Aktivasi Layanan: PPPoE '{$ontUs}', Router: " . ($routerName ?: ($request->router_id ?: '-')) . ", Profile: " . ($request->ppp_profile ?: '-') . ($request->remote_address ? ", Remote IP: {$request->remote_address}" : '') . ($request->pppoe_comment ? ", Comment: {$request->pppoe_comment}" : '') . ", OLT: {$request->olt}, Index: {$normalizedIndexOlt}",
                    'router_response' => $mikrotikResult['message'] ?? 'PPPoE Secret dikonfigurasi',
                    'router_success' => (bool)($mikrotikResult['success'] ?? false),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal log activity_logs aktivasi: ' . $e->getMessage());
            }
        }

        if ($mikrotikResult['success']) {
            return redirect()->back()->with('success', "Report Aktivasi untuk pelanggan {$nomorInternet} berhasil disimpan! PPPoE Secret '{$ontUs}' sukses dibuat/diperbarui di MikroTik ({$routerName}) & layanan online (#20).");
        } else {
            return redirect()->back()->with('warning', "Report Aktivasi untuk pelanggan {$nomorInternet} tersimpan (Status #20), namun ada kendala di MikroTik ({$routerName}): " . $mikrotikResult['message']);
        }
    }

    /**
     * Konfigurasi PPPoE Secret MikroTik & Mulai Aktivasi / Reboot ke OLT
     * Status #18 / #19 -> #20 (Pelanggan Aktif Online)
     */
    public function doAktivasiPPPoE(Request $request, string $nomorInternet): RedirectResponse
    {
        $request->validate([
            'pppoe_username' => 'required|string',
            'pppoe_password' => 'required|string',
            'router_mikrotik' => 'nullable|string',
            'local_address' => 'nullable|string',
            'ppp_profile' => 'required|string',
            'remote_address' => 'nullable|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        // 1. Eksekusi Live ke MikroTik Router
        $mikrotikService = new MikrotikService();
        $routerName = $request->router_mikrotik ?: '';
        if ($request->filled('router_id')) {
            $rObj = DB::table('routers')->where('id', $request->router_id)->first();
            if ($rObj) {
                $routerName = $rObj->name;
                $pass = $rObj->password ?? '';
                try {
                    $pass = Crypt::decryptString($pass);
                } catch (\Exception $e) {}
                $mikrotikService->setRouter([
                    'name' => $rObj->name,
                    'host' => $rObj->host,
                    'port' => (int)($rObj->port ?: 18735),
                    'username' => $rObj->username,
                    'password' => $pass,
                ]);
            }
        }
        if (empty($routerName)) {
            $routerName = $mikrotikService->getRouterDisplayName();
        }

        $secretResult = $mikrotikService->createOrUpdateSecret([
            'name' => $request->pppoe_username,
            'password' => $request->pppoe_password,
            'profile' => $request->ppp_profile,
            'service' => 'pppoe',
            'local_address' => $request->local_address ?: '',
            'remote_address' => $request->remote_address ?: '',
            'comment' => $request->pppoe_comment ?: "Aktivasi - {$nomorInternet}",
            'disabled' => 'no',
        ]);

        if ($secretResult['success']) {
            $mikrotikService->kickActiveConnection($request->pppoe_username);
        }

        // 2. Update trx_batchjob_register to status 20 (Aktif)
        $updateData = [
            'status_reg' => '20', // Pelanggan Aktif
            'ont_us' => substr($request->pppoe_username, 0, 10),
            'ont_ps' => substr($request->pppoe_password, 0, 10),
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if ($request->filled('router_id')) {
            $updateData['router_id'] = $request->router_id;
        }

        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update($updateData);

        // 3. Update trx_instalasi
        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                [
                    'kode_instalasi' => 'INS-' . $nomorInternet,
                    'aktivasi_date_finish' => now()->format('Y-m-d'),
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                    'hide' => '0',
                ]
            );

        // 4. Log PPPoE Secret Creation & OLT Online
        if (Schema::hasTable('trx_batchjob_register_log')) {
            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => '20',
                'kat_log' => '20',
                'note_schedule' => "AKTIVASI PPPoE: User: {$request->pppoe_username}, Router: {$routerName}, Profile: {$request->ppp_profile}, Remote IP: " . ($request->remote_address ?: 'Dynamic/Pool') . " (Gateway: {$request->local_address}) - [{$secretResult['message']}]",
                'date_schedule' => now()->format('Y-m-d'),
                'time_schedule' => now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);
        }

        // 4b. Activity Log Router (tabel activity_logs)
        if (Schema::hasTable('activity_logs')) {
            try {
                ActivityLog::record([
                    'user_id' => $currentUser,
                    'customer_id' => $nomorInternet,
                    'action' => 'pppoe_activate',
                    'old_status' => null,
                    'new_status' => '20',
                    'description' => "Aktivasi PPPoE Secret: User '{$request->pppoe_username}', Router: '{$routerName}', Profile: '{$request->ppp_profile}', Remote IP: " . ($request->remote_address ?: 'Dynamic/Pool'),
                    'router_response' => $secretResult['message'],
                    'router_success' => (bool)$secretResult['success'],
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal log activity_logs pppoe: ' . $e->getMessage());
            }
        }

        if ($secretResult['success']) {
            return redirect()->back()->with('success', "PPPoE Secret '{$request->pppoe_username}' berhasil dibuat di {$routerName} dan layanan pelanggan {$nomorInternet} resmi AKTIF!");
        } else {
            return redirect()->back()->with('warning', "Status pelanggan {$nomorInternet} diubah ke AKTIF (#20), namun sinkronisasi MikroTik gagal: " . $secretResult['message']);
        }
    }

    /**
     * Backward-compatible handler for legacy doAktivasi
     */
    public function doAktivasi(Request $request, string $nomorInternet): RedirectResponse
    {
        return $this->storeReportAktivasi($request, $nomorInternet);
    }

    /**
     * Isolir & Suspend Layanan Jaringan
     */
    public function suspend(Request $request): View
    {
        // Sinkronisasi otomatis baris Request Suspend (11) yang pelanggannya sudah berhasil terisolir (status_reg = 21)
        CustomerProvisioningService::reconcilePendingSuspendRequests();

        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $groupLayanan = $request->query('group_layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        // Otomatis proses antrean Req. Unsuspend (status 18) tanpa perlu approve manual
        if (Schema::hasTable('trx_suspend')) {
            try {
                $hasReqUnsuspend = DB::table('trx_suspend')
                    ->where('status_suspend', '18')
                    ->where(function ($q) {
                        $q->whereNull('hide')->orWhere('hide', '0');
                    })
                    ->exists();
                if ($hasReqUnsuspend) {
                    app(\App\Services\Network\CustomerProvisioningService::class)->processAutoUnsuspendQueue();
                }
            } catch (\Throwable $th) {}
        }

        $query = DB::table('view_suspend');

        if ($layanan) {
            $query->where('nama_kategori_bandwith', $layanan);
        }

        if ($groupLayanan) {
            $custTable = Schema::hasTable('trx_batchjob_register') ? 'trx_batchjob_register' : (Schema::hasTable('view_batchjob') ? 'view_batchjob' : null);
            if ($custTable && Schema::hasColumn($custTable, 'group_layanan')) {
                $query->whereIn('nomor_internet', function ($sub) use ($custTable, $groupLayanan) {
                    $sub->select('nomor_internet')->from($custTable);
                    if (strtoupper($groupLayanan) === 'MEDIANET') {
                        $sub->where(function ($q) {
                            $q->where('group_layanan', 'MEDIANET')
                              ->orWhereNull('group_layanan')
                              ->orWhere('group_layanan', '');
                        });
                    } else {
                        $sub->where('group_layanan', $groupLayanan);
                    }
                });
            }
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('desc_suspend', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where(function ($q) use ($wilayah) {
                $q->where('alamat_p', 'like', "%{$wilayah}%");
                if (Schema::hasTable('view_batchjob')) {
                    $q->orWhereIn('nomor_internet', function ($sub) use ($wilayah) {
                        $sub->select('nomor_internet')
                            ->from('view_batchjob')
                            ->where('nama_kota_pasang', 'like', "%{$wilayah}%")
                            ->orWhere('kode_wilayah_kota_pasang', 'like', "%{$wilayah}%");
                    });
                }
            });
        }

        if ($status) {
            $query->where('status_suspend', $status);
        }

        // Ambil seluruh item yang bisa dipilih (status 11 / 18) sesuai filter untuk tombol "Pilih Semua Request (Semua Halaman)"
        $allSelectableItems = (clone $query)
            ->whereIn('status_suspend', ['11', '18'])
            ->orderBy('date_create', 'desc')
            ->get([
                'kode_suspend',
                'nomor_internet',
                'nama_pelanggan',
                'nama_kategori_bandwith',
                'nominal_bandwith',
                'status_suspend',
            ]);

        $suspends = $query->orderBy('date_create', 'desc')->paginate($perPage)->withQueryString();

        // 3 Summary Pill Counts (matching screenshot)
        $countRequest = DB::table('view_suspend')->where('status_suspend', '11')->count();
        $countSuspend = DB::table('view_suspend')->where('status_suspend', '12')->count();
        $countReqUnsuspend = DB::table('view_suspend')->where('status_suspend', '18')->count();

        // Distinct Layanan List, Group Layanan List, & Wilayah List
        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();
        $groupSourceTable = Schema::hasTable('view_batchjob') ? 'view_batchjob' : (Schema::hasTable('trx_batchjob_register') ? 'trx_batchjob_register' : null);
        $groupLayananList = ($groupSourceTable && Schema::hasColumn($groupSourceTable, 'group_layanan'))
            ? DB::table($groupSourceTable)
                ->whereNotNull('group_layanan')
                ->where('group_layanan', '!=', '')
                ->distinct()
                ->orderBy('group_layanan')
                ->pluck('group_layanan')
            : collect(['MEDIANET']);
        if (!$groupLayananList->contains('MEDIANET')) {
            $groupLayananList->prepend('MEDIANET');
        }

        $wilayahList = Schema::hasTable('m_wilayah_perangkat')
            ? DB::table('m_wilayah_perangkat')->pluck('name_w')->filter()->unique()->values()
            : collect(['KOTA BANDUNG', 'KAB. BANDUNG', 'KAB. BANDUNG BARAT']);
        if ($wilayahList->isEmpty()) {
            $wilayahList = collect(['KOTA BANDUNG', 'KAB. BANDUNG', 'KAB. BANDUNG BARAT']);
        }
        $statusList = Schema::hasTable('m_status_suspend') ? DB::table('m_status_suspend')->get() : collect();

        return view('noc.suspend', [
            'user' => $request->user(),
            'suspends' => $suspends,
            'allSelectableItems' => $allSelectableItems,
            'search' => $search,
            'layanan' => $layanan,
            'groupLayanan' => $groupLayanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'perPage' => $perPage,
            'countRequest' => $countRequest,
            'countSuspend' => $countSuspend,
            'countReqUnsuspend' => $countReqUnsuspend,
            'layananList' => $layananList,
            'groupLayananList' => $groupLayananList,
            'wilayahList' => $wilayahList,
            'statusList' => $statusList,
        ]);
    }

    /**
     * Approve Suspend / Unsuspend Request (Sekaligus Otomatis MikroTik + OLT Reboot + Kick)
     */
    public function approveSuspend(Request $request, string $kodeSuspend, CustomerProvisioningService $provisioning): RedirectResponse
    {
        $suspend = DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->first();
        if (!$suspend) {
            return redirect()->back()->with('error', 'Data suspend tidak ditemukan.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';
        $nomorInternet = $suspend->nomor_internet;

        if ($suspend->status_suspend == '18') {
            // UNsuspend / Buka Isolir -> Aktifkan
            $res = $provisioning->activateCustomer($nomorInternet, $currentUser, 'Approve Unsuspend dari NOC');
            if (!($res['success'] ?? false)) {
                return redirect()->back()->with('error', "Gagal buka isolir {$nomorInternet}: " . ($res['summary'] ?? 'Gagal pada router MikroTik'));
            }

            DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->update([
                'status_suspend' => '13',
                'suspend_end'    => now()->format('Y-m-d'),
                'date_update'    => $now,
                'user_update'    => mb_substr($currentUser, 0, 20),
            ]);

            return redirect()->back()->with('success', "Buka isolir {$nomorInternet} berhasil! PPPoE di-enable, koneksi di-kick, dan ONU OLT berhasil direboot.");
        } else {
            // Suspend / Isolir
            $res = $provisioning->suspendCustomer($nomorInternet, $currentUser, $suspend->desc_suspend ?: 'Approve Suspend dari NOC');
            if (!($res['success'] ?? false)) {
                return redirect()->back()->with('error', "Gagal suspend {$nomorInternet}: " . ($res['summary'] ?? 'Gagal pada router MikroTik'));
            }

            // Pastikan baris kode_suspend yang di-approve langsung berubah menjadi '12' (Terisolir Aktif)
            DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->update([
                'status_suspend' => '12',
                'suspend_start'  => $suspend->suspend_start ?: ($request->input('date') ?: now()->format('Y-m-d')),
                'date_update'    => $now,
                'user_update'    => mb_substr($currentUser, 0, 20),
            ]);

            // Bersihkan jika ada sisa baris '11' lain untuk nomor_internet yang sama
            DB::table('trx_suspend')
                ->where('nomor_internet', $nomorInternet)
                ->where('status_suspend', '11')
                ->where('kode_suspend', '!=', $kodeSuspend)
                ->delete();

            return redirect()->back()->with('success', "Suspend {$nomorInternet} berhasil! PPPoE di-disable, sesi aktif seketika diputus (kicked), dan ONU OLT berhasil direboot.");
        }
    }

    /**
     * Bulk Approve Suspend (Eksekusi Cepat MikroTik + Antrean Background OLT Reboot)
     */
    public function bulkApproveSuspend(Request $request, CustomerProvisioningService $provisioning): JsonResponse|RedirectResponse
    {
        $kodes = $request->input('selected_kodes', []);
        if (empty($kodes) && $request->filled('kode_suspend')) {
            $kodes = [$request->input('kode_suspend')];
        }

        if (empty($kodes)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data suspend yang dipilih.'], 400);
            }
            return redirect()->back()->with('error', 'Tidak ada data suspend yang dipilih.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';
        $batchId = 'BATCH-' . date('YmdHis') . '-' . rand(100, 999);
        $suspendDate = $request->input('date') ?: now()->format('Y-m-d');

        $suspendList = DB::table('trx_suspend')
            ->whereIn('kode_suspend', $kodes)
            ->get();

        $successCount = 0;
        $failedCount = 0;
        $queuedRebootCount = 0;
        $messages = [];

        foreach ($suspendList as $suspend) {
            $nomorInternet = $suspend->nomor_internet;

            if ($suspend->status_suspend == '18') {
                // UNsuspend / Buka Isolir
                $res = $provisioning->activateCustomer($nomorInternet, $currentUser, 'Bulk Approve Unsuspend dari NOC');
                if ($res['success'] ?? false) {
                    DB::table('trx_suspend')->where('kode_suspend', $suspend->kode_suspend)->update([
                        'status_suspend' => '13',
                        'suspend_end'    => now()->format('Y-m-d'),
                        'date_update'    => $now,
                        'user_update'    => mb_substr($currentUser, 0, 20),
                    ]);
                    $successCount++;
                } else {
                    $failedCount++;
                    $messages[] = "Gagal buka isolir {$nomorInternet}: " . ($res['summary'] ?? '');
                }
            } else {
                // Suspend -> fast MikroTik disable + DB update (skip synchronous OLT reboot)
                $res = $provisioning->suspendCustomer($nomorInternet, $currentUser, $suspend->desc_suspend ?: 'Bulk Suspend Massal NOC', true);

                if ($res['success'] ?? false) {
                    // Pastikan baris kode_suspend yang dipilih langsung berubah statusnya menjadi '12' (Terisolir Aktif)
                    DB::table('trx_suspend')->where('kode_suspend', $suspend->kode_suspend)->update([
                        'status_suspend' => '12',
                        'suspend_start'  => $suspend->suspend_start ?: $suspendDate,
                        'date_update'    => $now,
                        'user_update'    => mb_substr($currentUser, 0, 20),
                    ]);

                    // Hapus sisa baris '11' lain atau duplikat 'SUS-%' pada nomor_internet yang sama
                    DB::table('trx_suspend')
                        ->where('nomor_internet', $nomorInternet)
                        ->where('kode_suspend', '!=', $suspend->kode_suspend)
                        ->where(function ($q) {
                            $q->where('status_suspend', '11')
                              ->orWhere('kode_suspend', 'like', 'SUS-%');
                        })
                        ->delete();

                    $successCount++;

                    // Ambil detail pelanggan untuk antrean OLT reboot
                    $cust = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first();
                    $indexOlt = trim($cust->index_olt ?? '');
                    $kodeOlt = trim($cust->olt ?? '');
                    $namaPelanggan = $cust->nama_pelanggan ?? 'Pelanggan';

                    if (!empty($indexOlt) && Schema::hasTable('reboot_onu_queues')) {
                        $queueId = DB::table('reboot_onu_queues')->insertGetId([
                            'batch_id' => $batchId,
                            'nomor_internet' => $nomorInternet,
                            'nama_pelanggan' => $namaPelanggan,
                            'index_olt' => $indexOlt,
                            'kode_olt' => $kodeOlt,
                            'action_type' => 'suspend_reboot',
                            'status' => 'pending',
                            'operator' => $currentUser,
                            'attempts' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $queuedRebootCount++;

                        // Dispatch background job
                        try {
                            ProcessRebootOnuJob::dispatch($queueId);
                        } catch (\Throwable $e) {
                            Log::warning("Dispatch queue job notice: " . $e->getMessage());
                        }
                    }
                } else {
                    $failedCount++;
                    $messages[] = "Gagal suspend {$nomorInternet}: " . ($res['summary'] ?? '');
                }
            }
        }

        $summaryText = "Berhasil memproses {$successCount} pelanggan.";
        if ($queuedRebootCount > 0) {
            $summaryText .= " {$queuedRebootCount} ONT/ONU masuk antrean background reboot (Batch: {$batchId}).";
        }
        if ($failedCount > 0) {
            $summaryText .= " ({$failedCount} gagal).";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $successCount > 0,
                'batch_id' => $batchId,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'queued_reboot_count' => $queuedRebootCount,
                'message' => $summaryText,
                'errors' => $messages,
            ]);
        }

        return redirect()->back()->with($successCount > 0 ? 'success' : 'error', $summaryText);
    }

    /**
     * Get Status of Reboot ONU Queues (for live progress drawer / monitor)
     */
    public function getQueueStatus(Request $request): JsonResponse
    {
        if (!Schema::hasTable('reboot_onu_queues')) {
            return response()->json(['success' => false, 'items' => [], 'summary' => []]);
        }

        $batchId = $request->query('batch_id');

        $query = DB::table('reboot_onu_queues')->orderBy('id', 'desc');
        if ($batchId) {
            $query->where('batch_id', $batchId);
        } else {
            // Ambil antrean 24 jam terakhir
            $query->where('created_at', '>=', now()->subHours(24))->limit(50);
        }

        $items = $query->get();

        $total = $items->count();
        $pending = $items->where('status', 'pending')->count();
        $processing = $items->where('status', 'processing')->count();
        $success = $items->where('status', 'success')->count();
        $failed = $items->where('status', 'failed')->count();
        $progress = $total > 0 ? round((($success + $failed) / $total) * 100) : 100;

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'summary' => [
                'total' => $total,
                'pending' => $pending,
                'processing' => $processing,
                'success' => $success,
                'failed' => $failed,
                'progress_percentage' => $progress,
                'is_completed' => ($pending === 0 && $processing === 0),
            ],
            'items' => $items,
        ]);
    }

    /**
     * Process Next Pending Queue Item (AJAX fallback executor for background queue)
     */
    public function processNextQueueItem(Request $request, OltConnectionService $oltService): JsonResponse
    {
        if (!Schema::hasTable('reboot_onu_queues')) {
            return response()->json(['processed' => false, 'message' => 'Tabel queue tidak ditemukan']);
        }

        $batchId = $request->input('batch_id');

        $query = DB::table('reboot_onu_queues')
            ->where('status', 'pending')
            ->orderBy('id', 'asc');

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        $item = $query->first();

        if (!$item) {
            return response()->json([
                'processed' => false,
                'message' => 'Tidak ada antrean pending.',
                'is_empty' => true,
            ]);
        }

        // Tandai processing
        DB::table('reboot_onu_queues')->where('id', $item->id)->update([
            'status' => 'processing',
            'started_at' => now(),
            'attempts' => $item->attempts + 1,
            'updated_at' => now(),
        ]);

        $indexOlt = trim($item->index_olt ?? '');
        $kodeOlt = trim($item->kode_olt ?? '');

        if (empty($indexOlt)) {
            DB::table('reboot_onu_queues')->where('id', $item->id)->update([
                'status' => 'failed',
                'response_message' => 'Index OLT kosong',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json([
                'processed' => true,
                'id' => $item->id,
                'nomor_internet' => $item->nomor_internet,
                'status' => 'failed',
                'message' => 'Index OLT kosong',
            ]);
        }

        try {
            $rebootResult = $oltService->rebootOnu($indexOlt, $kodeOlt);
            $isSuccess = (bool)($rebootResult['success'] ?? false);
            $msg = $rebootResult['message'] ?? ($isSuccess ? 'Reboot ONU berhasil' : 'Gagal reboot ONU');

            DB::table('reboot_onu_queues')->where('id', $item->id)->update([
                'status' => $isSuccess ? 'success' : 'failed',
                'response_message' => $msg,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'processed' => true,
                'id' => $item->id,
                'nomor_internet' => $item->nomor_internet,
                'status' => $isSuccess ? 'success' : 'failed',
                'message' => $msg,
            ]);
        } catch (\Throwable $e) {
            DB::table('reboot_onu_queues')->where('id', $item->id)->update([
                'status' => 'failed',
                'response_message' => 'Error: ' . $e->getMessage(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'processed' => true,
                'id' => $item->id,
                'nomor_internet' => $item->nomor_internet,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * UNIFIED ACTION: Aktifkan Layanan Pelanggan (DB 20 + MikroTik Enable + Kick + OLT Reboot)
     */
    public function unifiedActivate(Request $request, string $nomorInternet, CustomerProvisioningService $provisioning): JsonResponse|RedirectResponse
    {
        // Proteksi Hak Akses: Role Teknik tidak diizinkan melakukan aktivasi pelanggan
        if (auth()->user()?->hasRole('teknik') && !auth()->user()?->hasRole(['noc', 'admin', 'direktur'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'title' => 'Akses Ditolak',
                    'summary' => 'Role Teknik tidak memiliki izin untuk mengaktifkan pelanggan.',
                    'message' => 'Akses ditolak. Role Teknik tidak memiliki izin untuk mengaktifkan pelanggan.'
                ], 403);
            }
            return redirect()->back()->with('error', 'Akses ditolak. Role Teknik tidak memiliki izin untuk mengaktifkan pelanggan.');
        }

        $currentUser = auth()->user()->nama ?? 'NOC';
        $note = $request->input('note', 'Aktivasi langsung dari NOC');

        $result = $provisioning->activateCustomer($nomorInternet, $currentUser, $note);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->back()->with($result['success'] ? 'success' : 'warning', $result['summary']);
    }

    /**
     * UNIFIED ACTION: Suspend / Isolir Layanan Pelanggan (DB 21 + MikroTik Disable + Kick + OLT Reboot)
     */
    public function unifiedSuspend(Request $request, string $nomorInternet, CustomerProvisioningService $provisioning): JsonResponse|RedirectResponse
    {
        // Proteksi Hak Akses: Role Teknik tidak diizinkan melakukan suspend pelanggan
        if (auth()->user()?->hasRole('teknik') && !auth()->user()?->hasRole(['noc', 'admin', 'direktur'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'title' => 'Akses Ditolak',
                    'summary' => 'Role Teknik tidak memiliki izin untuk melakukan suspend pelanggan.',
                    'message' => 'Akses ditolak. Role Teknik tidak memiliki izin untuk melakukan suspend pelanggan.'
                ], 403);
            }
            return redirect()->back()->with('error', 'Akses ditolak. Role Teknik tidak memiliki izin untuk melakukan suspend pelanggan.');
        }

        $currentUser = auth()->user()->nama ?? 'NOC';
        $reason = $request->input('reason', 'Suspend / Isolir langsung dari NOC');

        $result = $provisioning->suspendCustomer($nomorInternet, $currentUser, $reason);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->back()->with($result['success'] ? 'success' : 'warning', $result['summary']);
    }

    /**
     * Cancel Suspend Request
     */
    public function cancelSuspend(Request $request, string $kodeSuspend): RedirectResponse
    {
        $suspend = DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->first();
        if (!$suspend) {
            return redirect()->back()->with('error', 'Data suspend tidak ditemukan.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->update([
            'status_suspend' => '14',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan suspend {$suspend->nomor_internet} telah dibatalkan (Canceled).");
    }

    /**
     * Terminasi Layanan Jaringan & Pelepasan Port
     */
    public function terminasi(Request $request): View
    {
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');

        $query = DB::table('view_terminasi');

        if ($layanan) {
            $query->where('nama_kategori_bandwith', $layanan);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode_trx_terminasi', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('status_terminasi', $status);
        }

        if ($bulan) {
            $query->whereMonth('date_create', $bulan);
        }

        if ($tahun) {
            $query->whereYear('date_create', $tahun);
        }

        $terminasis = $query->orderByRaw("
            CASE 
                WHEN status_terminasi = '11' THEN 1
                WHEN status_terminasi IN ('12', '12.1') THEN 2
                WHEN status_terminasi = '13' THEN 3
                WHEN status_terminasi = '14' THEN 4
                ELSE 5
            END ASC
        ")->orderBy('date_create', 'desc')->paginate(10)->withQueryString();

        // 8 KPI Counters (matching screenshot)
        $count11 = DB::table('view_terminasi')->where('status_terminasi', '11')->count();
        $count12 = DB::table('view_terminasi')->where('status_terminasi', '12')->count();
        $count12_1 = DB::table('view_terminasi')->where('status_terminasi', '12.1')->count();
        $count13 = DB::table('view_terminasi')->where('status_terminasi', '13')->count();
        $count14 = DB::table('view_terminasi')->where('status_terminasi', '14')->count();
        $count15 = DB::table('view_terminasi')->where('status_terminasi', '15')->count();
        $count16 = DB::table('view_terminasi')->where('status_terminasi', '16')->count();
        $count17 = DB::table('view_terminasi')->where('status_terminasi', '17')->count();

        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();
        $karyawans = $this->getTeamAktivasi();

        return view('noc.terminasi', [
            'user' => $request->user(),
            'terminasis' => $terminasis,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'count11' => $count11,
            'count12' => $count12,
            'count12_1' => $count12_1,
            'count13' => $count13,
            'count14' => $count14,
            'count15' => $count15,
            'count16' => $count16,
            'count17' => $count17,
            'layananList' => $layananList,
            'karyawans' => $karyawans,
        ]);
    }

    /**
     * Schedule Collect Perangkat Terminasi
     */
    public function scheduleCollect(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        $team = is_array($request->team) ? implode(', ', $request->team) : ($request->team ?? $request->team_collect ?? '');
        $dateSchedule = $request->date_schedule ?: $request->date_collect_start ?: now()->format('Y-m-d');
        $waktu = $request->waktu ?: $request->time_collect_start ?: '09:00 - 12:00 WIB';
        $note = $request->note ?: $request->note_collect_start;

        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
            'status_terminasi' => '12', // (KD12) Collecting
            'date_collect_start' => $dateSchedule,
            'time_collect_start' => $waktu,
            'team_collect' => $team,
            'note_collect_start' => $note,
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Jadwal penarikan perangkat (Schedule Collect) {$kodeTrx} berhasil disimpan!");
    }

    /**
     * Report Collecting Perangkat Terminasi (KD12 / KD12.1 -> KD13 atau KD12.1)
     */
    public function reportCollect(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        $statusResult = $request->status_result ?? 'done'; // 'done' or 'reschedule'
        $collectPerangkat = $request->collect_perangkat == '1' ? '1' : '0';
        $collectPayment = $request->collect_payment == '1' ? '1' : '0';
        $dateCollect = $request->date_collect ?: ($request->date_collect_end ?: ($request->date_schedule ?: now()->format('Y-m-d')));
        $timeCollect = $request->time_collect ?: ($request->time_collect_end ?: ($request->time_schedule ?: now()->format('H:i:s')));
        $note = $request->note ?: ($request->keterangan ?: ($request->note_collect_end ?: ($request->note_collect_start ?? '')));

        if ($statusResult === 'reschedule') {
            DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
                'status_terminasi' => '12.1', // (KD12.1) Reschedule Collecting
                'date_collect_start' => $dateCollect,
                'time_collect_start' => $timeCollect,
                'note_collect_start' => $note ?: 'Reschedule penarikan perangkat',
                'date_update' => $now,
                'user_update' => $currentUser,
            ]);

            return redirect()->back()->with('success', "Penarikan perangkat {$kodeTrx} dijadwalkan ulang (Reschedule Collecting)!");
        }

        // Done: Status 13 (Collect Perangkat Done)
        $updateData = [
            'status_terminasi' => '13', // (KD13) Collect Perangkat Done
            'date_collect_finish' => $dateCollect,
            'note_collect_finish' => $note ?: 'Perangkat berhasil ditarik oleh teknisi',
            'date_update' => $now,
            'user_update' => $currentUser,
        ];
        if (Schema::hasColumn('trx_terminasi', 'collect_perangkat')) {
            $updateData['collect_perangkat'] = $collectPerangkat ?: '1';
        }
        if (Schema::hasColumn('trx_terminasi', 'collect_payment')) {
            $updateData['collect_payment'] = $collectPayment;
        }

        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update($updateData);

        return redirect()->back()->with('success', "Laporan penarikan perangkat (Report Collecting) {$kodeTrx} berhasil disimpan! Status kini siap untuk Closing Terminasi.");
    }

    /**
     * Closing / Selesaikan Terminasi (KD13 -> KD14 Terminasi Selesai)
     * Langsung terhubung ke MikroTik Router untuk KICK koneksi dan HAPUS user PPPoE
     */
    public function closeTerminasi(Request $request, string $kodeTrx, CustomerProvisioningService $provisioning): RedirectResponse
    {
        $trx = DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->first();
        if (!$trx) {
            return redirect()->back()->with('error', "Data transaksi terminasi {$kodeTrx} tidak ditemukan.");
        }

        $currentUser = auth()->user()->nama ?? 'NOC';
        $dateDone = $request->date_termin_done ?: ($request->date ?: now()->format('Y-m-d H:i:s'));
        $note = 'Proses closing terminasi selesai & user dihapus dari router MikroTik';

        $nomorInternet = $trx->nomor_internet;
        $res = $provisioning->terminateCustomer($nomorInternet, $currentUser, $note, $kodeTrx, $dateDone);

        // Explicit fallback update to ensure status_terminasi is 14
        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
            'status_terminasi' => '14',
            'date_update' => now()->format('Y-m-d H:i:s'),
            'user_update' => $currentUser,
        ]);

        if ($res['success']) {
            return redirect()->back()->with('success', "Closing terminasi {$kodeTrx} berhasil! {$res['summary']}");
        } else {
            return redirect()->back()->with('warning', "Closing terminasi {$kodeTrx} diproses. Info router: {$res['summary']}");
        }
    }

    /**
     * Cancel Permintaan Terminasi
     */
    public function cancelTerminasi(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
            'status_terminasi' => '16', // Cancel Terminasi
            'note_termin_cancel' => $request->note_cancel ?? 'Dibatalkan oleh operator',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan terminasi {$kodeTrx} berhasil dibatalkan!");
    }

    /**
     * Inventaris Perangkat & Asset Jaringan
     */
    public function perangkat(Request $request): View
    {
        $search = $request->query('search');

        $query = DB::table('m_barang')->where('hide', '!=', '1');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('tipe_barang', 'like', "%{$search}%");
            });
        }
        $barangs = $query->paginate(15)->withQueryString();

        // Installed devices count summary
        $installedSummary = Schema::hasTable('trx_instalasi_barang')
            ? DB::table('trx_instalasi_barang')
                ->join('m_barang', 'trx_instalasi_barang.kode_barang', '=', 'm_barang.kode_barang')
                ->select('m_barang.nama_barang', DB::raw('SUM(trx_instalasi_barang.jumlah_barang) as total_terpasang'))
                ->groupBy('m_barang.nama_barang')
                ->get()
            : collect();

        return view('noc.perangkat', [
            'user' => $request->user(),
            'barangs' => $barangs,
            'installedSummary' => $installedSummary,
            'search' => $search,
        ]);
    }

    /**
     * Master Router MikroTik: List & Monitoring
     */
    public function router(Request $request): View
    {
        $search = $request->query('search');

        $query = DB::table('routers');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('host', 'like', "%{$search}%")
                  ->orWhere('kota', 'like', "%{$search}%");
            });
        }

        $routers = $query->orderBy('id', 'asc')->get();

        // Customer counts per router from trx_batchjob_register
        $customerCounts = Schema::hasTable('trx_batchjob_register')
            ? DB::table('trx_batchjob_register')
                ->whereNotNull('router_id')
                ->select('router_id', DB::raw('COUNT(*) as total_customers'))
                ->groupBy('router_id')
                ->pluck('total_customers', 'router_id')
                ->all()
            : [];

        $totalCustomersMapped = Schema::hasTable('trx_batchjob_register') ? DB::table('trx_batchjob_register')->whereNotNull('router_id')->count() : 0;
        $totalCustomersUnmapped = Schema::hasTable('trx_batchjob_register') ? DB::table('trx_batchjob_register')->whereNull('router_id')->count() : 0;
        $totalCustomers = Schema::hasTable('trx_batchjob_register') ? DB::table('trx_batchjob_register')->count() : 0;

        // Auto-test live status setiap router (server-side, cached 60s per router agar tidak hanging)
        $liveStatus = [];
        foreach ($routers as $r) {
            $r->customer_count = $customerCounts[$r->id] ?? 0;
            
            if ($r->is_active) {
                $liveStatus[$r->id] = Cache::remember('mikrotik_status_' . $r->id, 180, function () use ($r) {
                    try {
                        $pass = $r->password ?: config('mikrotik.pass', 'kayuagung2-9');
                        try {
                            $pass = Crypt::decryptString($pass);
                        } catch (\Throwable $e) {
                            if (is_string($pass) && str_starts_with($pass, 'eyJ')) {
                                $pass = config('mikrotik.pass', 'kayuagung2-9');
                            }
                        }

                        $service = new \App\Services\Network\MikrotikService([
                            'host' => $r->host,
                            'port' => (int)($r->port ?: 18735),
                            'username' => $r->username,
                            'password' => $pass,
                            'timeout' => 2,
                        ]);

                        $result = $service->testConnection();
                        return [
                            'status' => $result['success'] ? 'online' : 'offline',
                            'identity' => $result['identity'] ?? null,
                            'message' => $result['message'] ?? '',
                        ];
                    } catch (\Throwable $e) {
                        return [
                            'status' => 'offline',
                            'identity' => null,
                            'message' => $e->getMessage(),
                        ];
                    }
                });
            } else {
                $liveStatus[$r->id] = [
                    'status' => 'inactive',
                    'identity' => null,
                    'message' => 'Router nonaktif',
                ];
            }
        }

        return view('noc.router', [
            'user' => $request->user(),
            'routers' => $routers,
            'search' => $search,
            'totalRouters' => $routers->count(),
            'activeRouters' => $routers->where('is_active', 1)->count(),
            'totalCustomersMapped' => $totalCustomersMapped,
            'totalCustomersUnmapped' => $totalCustomersUnmapped,
            'totalCustomers' => $totalCustomers,
            'liveStatus' => $liveStatus,
        ]);
    }

    /**
     * Store New Router MikroTik
     */
    public function storeRouter(Request $request): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk menambah router MikroTik.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'kota' => 'nullable|string|max:100',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $encryptedPass = Crypt::encryptString($request->password);

        $routerId = DB::table('routers')->insertGetId([
            'name' => $request->name,
            'host' => $request->host,
            'port' => (int)$request->port,
            'username' => $request->username,
            'password' => $encryptedPass,
            'kota' => $request->kota,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (Schema::hasTable('activity_logs')) {
            try {
                ActivityLog::record([
                    'user_id' => auth()->user()?->nama ?? auth()->user()?->username ?? 'System',
                    'customer_id' => null,
                    'action' => 'create_router',
                    'description' => "Menambahkan router baru: {$request->name} ({$request->host}:{$request->port}) di wilayah " . ($request->kota ?: 'Umum'),
                    'router_response' => "Router ID #{$routerId} berhasil dibuat",
                    'router_success' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore log error
            }
        }

        return redirect()->route('noc.router')->with('success', "Router '{$request->name}' berhasil ditambahkan ke sistem.");
    }

    /**
     * Update Existing Router MikroTik
     */
    public function updateRouter(Request $request, int $id): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk mengedit router MikroTik.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'kota' => 'nullable|string|max:100',
        ]);

        $router = DB::table('routers')->where('id', $id)->first();
        if (!$router) {
            return redirect()->route('noc.router')->with('error', 'Router tidak ditemukan.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $updateData = [
            'name' => $request->name,
            'host' => $request->host,
            'port' => (int)$request->port,
            'username' => $request->username,
            'kota' => $request->kota,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Crypt::encryptString($request->password);
        }

        DB::table('routers')->where('id', $id)->update($updateData);

        if (Schema::hasTable('activity_logs')) {
            try {
                ActivityLog::record([
                    'user_id' => auth()->user()?->nama ?? auth()->user()?->username ?? 'System',
                    'customer_id' => null,
                    'action' => 'update_router',
                    'description' => "Memperbarui konfigurasi router [ID #{$id}]: {$request->name} ({$request->host}:{$request->port})",
                    'router_response' => "Router ID #{$id} berhasil diperbarui",
                    'router_success' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore log error
            }
        }

        return redirect()->route('noc.router')->with('success', "Konfigurasi Router '{$request->name}' berhasil diperbarui.");
    }

    /**
     * Delete Router MikroTik
     */
    public function deleteRouter(Request $request, int $id): RedirectResponse
    {
        if (!auth()->user()?->hasRole(['admin', 'direktur'])) {
            abort(403, 'Role NOC tidak memiliki izin untuk menghapus router MikroTik.');
        }

        $router = DB::table('routers')->where('id', $id)->first();
        if (!$router) {
            return redirect()->route('noc.router')->with('error', 'Router tidak ditemukan.');
        }

        // Unlink customers in trx_batchjob_register mapped to this router
        if (Schema::hasTable('trx_batchjob_register')) {
            DB::table('trx_batchjob_register')->where('router_id', $id)->update(['router_id' => null, 'date_update' => now()]);
        }

        DB::table('routers')->where('id', $id)->delete();

        if (Schema::hasTable('activity_logs')) {
            try {
                ActivityLog::record([
                    'user_id' => auth()->user()?->nama ?? auth()->user()?->username ?? 'System',
                    'customer_id' => null,
                    'action' => 'delete_router',
                    'description' => "Menghapus router MikroTik: {$router->name} ({$router->host})",
                    'router_response' => "Router ID #{$id} dihapus dari master data",
                    'router_success' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore log error
            }
        }

        return redirect()->route('noc.router')->with('success', "Router '{$router->name}' berhasil dihapus.");
    }

    /**
     * Test Connection to Router (AJAX / Live)
     */
    public function testRouterConnection(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $host = $request->input('host');
        $port = (int)$request->input('port', 18735);
        $username = $request->input('username');
        $password = $request->input('password');
        $routerName = 'Custom Host';

        if ($id) {
            $router = DB::table('routers')->where('id', $id)->first();
            if ($router) {
                $routerName = $router->name;
                $host = $router->host;
                $port = (int)($router->port ?: 18735);
                $username = $router->username;
                $pass = $router->password ?: config('mikrotik.pass', 'kayuagung2-9');
                try {
                    $password = Crypt::decryptString($pass);
                } catch (\Throwable $e) {
                    if (is_string($pass) && str_starts_with($pass, 'eyJ')) {
                        $password = config('mikrotik.pass', 'kayuagung2-9');
                    } else {
                        $password = $pass;
                    }
                }
            }
        }

        if (empty($host) || empty($username)) {
            return response()->json([
                'success' => false,
                'message' => 'Host dan Username router wajib diisi untuk tes koneksi.',
            ], 422);
        }

        $service = new \App\Services\Network\MikrotikService([
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'timeout' => 4,
        ]);

        $result = $service->testConnection();

        if (Schema::hasTable('activity_logs')) {
            try {
                ActivityLog::record([
                    'user_id' => auth()->user()?->nama ?? auth()->user()?->username ?? 'System',
                    'customer_id' => null,
                    'action' => 'test_connection',
                    'description' => "Tes koneksi live ke router: {$routerName} ({$host}:{$port})",
                    'router_response' => ($result['success'] ? 'Sukses terhubung' : 'Gagal terhubung') . ' - ' . ($result['message'] ?? ($result['identity'] ?? '')),
                    'router_success' => (bool)$result['success'],
                ]);
            } catch (\Exception $e) {
                // Ignore log error
            }
        }

        return response()->json($result);
    }

    /**
     * AJAX: Dapatkan list PPP Profiles langsung dari router MikroTik
     */
    public function getRouterPppProfiles(int|string $id): JsonResponse
    {
        $router = DB::table('routers')->where('id', $id)->first();
        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'Router tidak ditemukan.',
                'profiles' => [],
            ], 404);
        }

        $isSoreang = ((int)$router->id === 2) || str_contains(strtolower($router->name . ' ' . ($router->kota ?? '')), 'soreang');

        $fallbackProfiles = $isSoreang ? [
            ['name' => 'default', 'rate_limit' => '', 'local_address' => ''],
            ['name' => 'SOHO-100M', 'rate_limit' => '110M/110M', 'local_address' => '172.21.0.1'],
            ['name' => '777-Management-ONT', 'rate_limit' => '', 'local_address' => '172.16.160.1'],
            ['name' => 'Management-ONT', 'rate_limit' => '', 'local_address' => '172.16.32.1'],
            ['name' => 'Broadband-5M', 'rate_limit' => '', 'local_address' => '10.1.48.1'],
            ['name' => 'Broadband-10M', 'rate_limit' => '', 'local_address' => '10.1.50.1'],
            ['name' => 'Broadband-15M', 'rate_limit' => '', 'local_address' => '10.1.0.1'],
            ['name' => 'Broadband-20M', 'rate_limit' => '', 'local_address' => '10.1.8.1'],
            ['name' => 'Broadband-25M', 'rate_limit' => '', 'local_address' => '10.1.16.1'],
            ['name' => 'Broadband-30M', 'rate_limit' => '', 'local_address' => '10.1.24.1'],
            ['name' => 'Broadband-35M', 'rate_limit' => '', 'local_address' => '10.1.32.1'],
            ['name' => 'Broadband-Free', 'rate_limit' => '', 'local_address' => '10.1.40.1'],
            ['name' => 'default-encryption', 'rate_limit' => '', 'local_address' => ''],
        ] : [
            ['name' => 'default', 'rate_limit' => '', 'local_address' => ''],
            ['name' => '779-Management-ONT', 'rate_limit' => '', 'local_address' => '172.16.150.1'],
            ['name' => 'Broadband-20M', 'rate_limit' => '22M/22M', 'local_address' => '10.0.0.1'],
            ['name' => 'SOHO', 'rate_limit' => '75M/75M', 'local_address' => '172.19.0.1'],
            ['name' => 'Broadband-25M', 'rate_limit' => '27M/27M', 'local_address' => '10.0.8.1'],
            ['name' => 'Broadband-30M', 'rate_limit' => '32M/32M', 'local_address' => '10.0.16.1'],
            ['name' => 'Broadband-35M', 'rate_limit' => '37M/37M', 'local_address' => '10.0.24.1'],
            ['name' => 'Broadband-Free', 'rate_limit' => '11M/11M', 'local_address' => '10.0.64.1'],
            ['name' => 'default-encryption', 'rate_limit' => '', 'local_address' => ''],
        ];

        try {
            $pass = $router->password ?: config('mikrotik.pass', 'kayuagung2-9');
            try {
                $pass = Crypt::decryptString($pass);
            } catch (\Throwable $e) {
                if (is_string($pass) && str_starts_with($pass, 'eyJ')) {
                    $pass = config('mikrotik.pass', 'kayuagung2-9');
                }
            }

            $service = new MikrotikService([
                'host' => $router->host,
                'port' => (int)($router->port ?: 18735),
                'username' => $router->username ?: 'aplikasi',
                'password' => $pass,
                'timeout' => 6,
            ]);

            $profiles = $service->getPppProfiles();
            if (empty($profiles)) {
                $profiles = $fallbackProfiles;
            }

            return response()->json([
                'success' => true,
                'router_name' => $router->name,
                'profiles' => $profiles,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'router_name' => $router->name,
                'profiles' => $fallbackProfiles,
                'message' => 'Info: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX: Dapatkan daftar seluruh user PPPoE Secret langsung dari router MikroTik
     */
    public function getRouterSecrets(Request $request, int|string $id): JsonResponse
    {
        $router = DB::table('routers')->where('id', $id)->first();
        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'Router tidak ditemukan di database.',
                'secrets' => [],
            ], 404);
        }

        try {
            $pass = $router->password ?: config('mikrotik.pass', 'kayuagung2-9');
            try {
                $pass = Crypt::decryptString($pass);
            } catch (\Throwable $e) {
                if (is_string($pass) && str_starts_with($pass, 'eyJ')) {
                    $pass = config('mikrotik.pass', 'kayuagung2-9');
                }
            }

            $service = new \App\Services\Network\MikrotikService([
                'host' => $router->host,
                'port' => (int)($router->port ?: 18735),
                'username' => $router->username ?: 'aplikasi',
                'password' => $pass,
                'timeout' => 8,
            ]);

            $secrets = $service->getUsers();
            $search = trim((string)($request->query('q') ?? $request->query('search', '')));

            if ($search !== '') {
                $secrets = array_values(array_filter($secrets, function ($s) use ($search) {
                    $name = $s['name'] ?? '';
                    $comment = $s['comment'] ?? '';
                    $profile = $s['profile'] ?? '';
                    $caller = $s['last-caller-id'] ?? $s['remote-address'] ?? $s['caller-id'] ?? '';
                    return stripos($name, $search) !== false 
                        || stripos($comment, $search) !== false 
                        || stripos($profile, $search) !== false
                        || stripos($caller, $search) !== false;
                }));
            }

            return response()->json([
                'success' => true,
                'router_name' => $router->name,
                'router_host' => $router->host,
                'total' => count($secrets),
                'secrets' => $secrets,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil user dari router: ' . $e->getMessage(),
                'secrets' => [],
            ], 500);
        }
    }

    /**
     * Sync / Map Customers to Router by City or Default in trx_batchjob_register
     */
    public function syncRouterCustomers(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $router = DB::table('routers')->where('id', $id)->first();
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $mappedCount = 0;
        if (Schema::hasTable('trx_batchjob_register')) {
            if (!empty($router->kota)) {
                // Map customers with matching city / address keywords
                $mappedCount = DB::table('trx_batchjob_register')
                    ->where(function ($q) use ($router) {
                        $q->whereNull('router_id')
                          ->orWhere('router_id', 0);
                    })
                    ->where(function ($q) use ($router) {
                        $q->where('alamat_pasang', 'like', "%{$router->kota}%")
                          ->orWhere('nama_pelanggan', 'like', "%{$router->kota}%");
                    })
                    ->update(['router_id' => $router->id, 'date_update' => now()]);
            } else {
                // Assign unmapped customers to this default router
                $mappedCount = DB::table('trx_batchjob_register')
                    ->whereNull('router_id')
                    ->update(['router_id' => $router->id, 'date_update' => now()]);
            }
        }

        $msg = "Sinkronisasi selesai! {$mappedCount} pelanggan di trx_batchjob_register berhasil dipetakan ke router '{$router->name}'.";

        if (Schema::hasTable('activity_logs')) {
            try {
                ActivityLog::record([
                    'user_id' => auth()->user()?->nama ?? auth()->user()?->username ?? 'System',
                    'customer_id' => null,
                    'action' => 'sync_router',
                    'description' => "Sinkronisasi mapping pelanggan ke router [ID #{$id}] '{$router->name}' (Wilayah: " . ($router->kota ?: 'Default / Semua') . ")",
                    'router_response' => "{$mappedCount} pelanggan berhasil dipetakan",
                    'router_success' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore log error
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'mapped_count' => $mappedCount, 'message' => $msg]);
        }

        return redirect()->route('noc.router')->with('success', $msg);
    }

    /**
     * Audit Trail & Activity Log Router MikroTik & Jaringan
     */
    public function activityLog(Request $request): View
    {
        $search = $request->query('search');
        $action = $request->query('action');
        $status = $request->query('status'); // '1' (sukses), '0' (gagal)
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = DB::table('activity_logs');

        if ($search) {
            // Find any customer numbers matching customer name search
            $matchingCustIds = [];
            if (Schema::hasTable('trx_batchjob_register')) {
                $matchingCustIds = DB::table('trx_batchjob_register')
                    ->where('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('nomor_internet', 'like', "%{$search}%")
                    ->pluck('nomor_internet')
                    ->all();
            }

            $query->where(function ($q) use ($search, $matchingCustIds) {
                $q->where('activity_logs.customer_id', 'like', "%{$search}%")
                  ->orWhere('activity_logs.user_id', 'like', "%{$search}%")
                  ->orWhere('activity_logs.action', 'like', "%{$search}%")
                  ->orWhere('activity_logs.description', 'like', "%{$search}%")
                  ->orWhere('activity_logs.router_response', 'like', "%{$search}%");

                if (!empty($matchingCustIds)) {
                    $q->orWhereIn('activity_logs.customer_id', $matchingCustIds);
                }
            });
        }

        if ($action) {
            $query->where('activity_logs.action', $action);
        }

        if ($status !== null && $status !== '') {
            $query->where('activity_logs.router_success', (int)$status);
        }

        if ($startDate) {
            $query->where('activity_logs.created_at', '>=', "{$startDate} 00:00:00");
        }

        if ($endDate) {
            $query->where('activity_logs.created_at', '<=', "{$endDate} 23:59:59");
        }

        $logs = $query->orderBy('activity_logs.created_at', 'desc')->paginate(25)->withQueryString();

        // Attach customer details safely without cross-collation SQL JOIN
        $customerIds = $logs->pluck('customer_id')->filter()->unique()->values()->all();

        // Primary lookup: match by nomor_internet (standard IMS v2 format)
        $customersByNomor = !empty($customerIds) && Schema::hasTable('trx_batchjob_register')
            ? DB::table('trx_batchjob_register')
                ->whereIn('nomor_internet', array_map('strval', $customerIds))
                ->get(['nomor_internet', 'nama_pelanggan', 'alamat_pasang', 'kode_bandwith', 'ont_us'])
                ->keyBy(fn($c) => (string) $c->nomor_internet)
            : collect();

        // Secondary lookup: for data imported from isp_manager, customer_id may store
        // the old isp_manager customers.customer_id value which equals ont_us in our DB
        $unmatchedIds = collect($customerIds)
            ->filter(fn($id) => !$customersByNomor->has((string) $id))
            ->values()
            ->all();

        $customersByOntUs = !empty($unmatchedIds) && Schema::hasTable('trx_batchjob_register')
            ? DB::table('trx_batchjob_register')
                ->whereIn('ont_us', array_map('strval', $unmatchedIds))
                ->get(['nomor_internet', 'nama_pelanggan', 'alamat_pasang', 'kode_bandwith', 'ont_us'])
                ->keyBy(fn($c) => (string) $c->ont_us)
            : collect();

        foreach ($logs as $log) {
            $custId = (string) $log->customer_id;
            // Try nomor_internet match first, then fallback to ont_us match
            $cust = $customersByNomor->get($custId) ?? $customersByOntUs->get($custId);
            $log->nama_pelanggan = $cust->nama_pelanggan ?? null;
            $log->alamat_pasang  = $cust->alamat_pasang ?? null;
            $log->paket          = $cust->kode_bandwith ?? null;
            $log->ont_us         = $cust->ont_us ?? null;
            // If matched via ont_us, override customer_id display with the real nomor_internet
            if (!$customersByNomor->has($custId) && $cust) {
                $log->display_customer_id = (string) $cust->nomor_internet;
            } else {
                $log->display_customer_id = $custId;
            }
        }

        // Summary KPI Metrics
        $totalLogs = DB::table('activity_logs')->count();
        $successLogs = DB::table('activity_logs')->where('router_success', 1)->count();
        $failedLogs = DB::table('activity_logs')->where('router_success', 0)->count();
        $todayLogs = DB::table('activity_logs')->whereDate('created_at', Carbon::today())->count();
        $activateLogs = DB::table('activity_logs')->whereIn('action', ['activate', 'pppoe_activate'])->count();
        $suspendLogs = DB::table('activity_logs')->where('action', 'suspend')->count();

        // Unique Actions list for filter dropdown
        $availableActions = DB::table('activity_logs')
            ->select('action')
            ->distinct()
            ->pluck('action')
            ->all();

        return view('noc.activity-log', [
            'user' => $request->user(),
            'logs' => $logs,
            'search' => $search,
            'action' => $action,
            'status' => $status,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalLogs' => $totalLogs,
            'successLogs' => $successLogs,
            'failedLogs' => $failedLogs,
            'todayLogs' => $todayLogs,
            'activateLogs' => $activateLogs,
            'suspendLogs' => $suspendLogs,
            'availableActions' => $availableActions,
        ]);
    }

    /**
     * Hapus Single Activity Log (Hanya untuk Admin)
     */
    public function deleteSingleActivityLog(Request $request, int|string $id): RedirectResponse
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403, 'Hanya Administrator yang memiliki hak akses untuk menghapus log aktivitas.');
        }

        $deleted = DB::table('activity_logs')->where('id', $id)->delete();

        if ($deleted) {
            return redirect()->back()->with('success', 'Log aktivitas router berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Log aktivitas tidak ditemukan.');
    }

    /**
     * Halaman Monitoring Instalasi ONU Real-time
     * Memantau ONU baru yang sedang diinstalasi teknisi dengan 3 status:
     * - 'Belum Terhubung' (Merah)
     * - 'Terdeteksi' (Kuning/Amber)
     * - 'Sudah UP' (Hijau)
     */
    public function monitoringInstalasiOnu(Request $request, OltConnectionService $oltService): View|JsonResponse
    {
        // Default filter tanggal: hari ini (date('Y-m-d')) jika tidak ada parameter tanggal
        $selectedTanggal = $request->has('tanggal') ? $request->query('tanggal') : date('Y-m-d');
        $selectedStatus = $request->query('status', 'all');
        $search = trim($request->query('search', ''));
        $teknisiList = [];
        $selectedTeknisi = 'all';

        // 1. Query data instalasi pelanggan dari trx_batchjob_register
        $query = DB::table('trx_batchjob_register')
            ->leftJoin('m_pelanggan', 'trx_batchjob_register.nik_penduduk', '=', 'm_pelanggan.nik_penduduk')
            ->leftJoin('m_bandwith', 'trx_batchjob_register.kode_bandwith', '=', 'm_bandwith.kode_bandwith')
            ->select([
                'trx_batchjob_register.*',
                DB::raw('COALESCE(trx_batchjob_register.nama_pelanggan, m_pelanggan.nama_penduduk) as nama_pelanggan'),
                DB::raw('COALESCE(m_pelanggan.nomor_hp, m_pelanggan.nomor_hp_2) as nomor_hp'),
                'm_bandwith.nominal_bandwith',
                'm_bandwith.nama_bandwith',
            ])
            ->where(function($q) {
                $q->whereIn('trx_batchjob_register.status_reg', ['18', '18.1', '19', '19.1', '20'])
                  ->orWhereNotNull('trx_batchjob_register.index_olt');
            });

        // Filter berdasarkan tanggal jika ada (default hari ini)
        if (!empty($selectedTanggal)) {
            $query->where(function($q) use ($selectedTanggal) {
                $q->whereDate('trx_batchjob_register.date_create', $selectedTanggal)
                  ->orWhereDate('trx_batchjob_register.date_update', $selectedTanggal);
            });
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('trx_batchjob_register.nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('trx_batchjob_register.nomor_internet', 'like', "%{$search}%")
                  ->orWhere('trx_batchjob_register.note_request', 'like', "%{$search}%")
                  ->orWhere('trx_batchjob_register.alamat_pasang', 'like', "%{$search}%")
                  ->orWhere('trx_batchjob_register.index_olt', 'like', "%{$search}%");
            });
        }

        // Ambil data instalasi (batasi 100 terbaru)
        $rawRecords = $query->orderBy('trx_batchjob_register.date_create', 'desc')->limit(100)->get();

        // 3. Status Hardware OLT ZTE C320 (Optimasi: Gunakan Cache agar navigasi halaman instan <100ms)
        $defaultOlt = DB::table('m_olt')->first();
        $uncfgData = [];
        $hwPons = [];
        $isForceRefresh = $request->boolean('refresh') || $request->query('sync') === '1';

        if ($defaultOlt) {
            $kodeOlt = $defaultOlt->kode_olt ?? 'O1';
            $uncfgCacheKey = "olt_live_uncfg_onus_{$kodeOlt}";

            // Scan unconfigured ONU dari OLT
            if ($isForceRefresh) {
                try {
                    $uncfgRes = $oltService->scanUnconfiguredOnu($defaultOlt, true);
                    $uncfgData = $uncfgRes['data'] ?? [];
                } catch (\Throwable $e) {}
            } elseif (Cache::has($uncfgCacheKey)) {
                $uncfgRes = Cache::get($uncfgCacheKey);
                $uncfgData = $uncfgRes['data'] ?? [];
            }

            // Kumpulkan port PON yang relevan dengan instalasi
            $portsNeeded = [4 => true, 5 => true];
            foreach ($rawRecords as $r) {
                $idx = trim($r->index_olt ?? '');
                if (preg_match('/(?:1\/[12]\/(\d+):|(\d+):)(\d+)/', $idx, $m)) {
                    $p = (int)($m[1] ?: $m[2]);
                    if ($p >= 1 && $p <= 16) {
                        $portsNeeded[$p] = true;
                    }
                }
            }

            foreach (array_keys($portsNeeded) as $pNum) {
                $hwCacheKey = "olt_live_hw_pon_physical_onus_{$kodeOlt}_{$pNum}";
                if ($isForceRefresh) {
                    try {
                        $hwPons[$pNum] = $oltService->fetchRealOltPhysicalOnus($defaultOlt, $pNum, true);
                    } catch (\Throwable $e) {}
                } elseif (Cache::has($hwCacheKey)) {
                    $hwPons[$pNum] = Cache::get($hwCacheKey);
                }
            }
        }

        // Siapkan unconfigured map berdasarkan SN & port
        $uncfgBySn = [];
        foreach ($uncfgData as $uc) {
            $snU = strtoupper(trim($uc['sn'] ?? ''));
            if (!empty($snU)) {
                $uncfgBySn[$snU] = $uc;
            }
        }

        // 4. Korelasi dan evaluasi status ke 3 kategori
        $items = [];
        $totalUp = 0;
        $totalTerdeteksi = 0;
        $totalBelumTerhubung = 0;
        $rxPowerSum = 0;
        $rxPowerCount = 0;

        foreach ($rawRecords as $r) {
            $idx = trim($r->index_olt ?? '');
            $sn = strtoupper(trim($r->note_request ?? ''));
            $portNum = null;
            $slotNum = null;

            if (preg_match('/(?:1\/[12]\/(\d+):|(\d+):)(\d+)/', $idx, $m)) {
                $portNum = (int)($m[1] ?: $m[2]);
                $slotNum = (int)$m[3];
            }

            $statusKey = 'belum_terhubung';
            $statusLabel = 'Belum Terhubung';
            $statusBadge = 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border-rose-300 dark:border-rose-800';
            $dotColor = 'bg-rose-500';
            $rxPower = 'N/A';
            $ontType = 'ZTE ONT';
            $detailKeterangan = 'Modem offline / kabel fiber belum tersambung ke port OLT.';

            $isHwFound = false;

            // Cek di unconfigured ONU dulu
            if (!empty($sn) && isset($uncfgBySn[$sn])) {
                $statusKey = 'terdeteksi';
                $statusLabel = 'Terdeteksi';
                $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800';
                $dotColor = 'bg-amber-500 animate-ping';
                $rxPower = '-21.8 dBm (Negosiasi)';
                $detailKeterangan = 'Sinyal optik masuk ke OLT (' . ($uncfgBySn[$sn]['port'] ?? '') . '). Menunggu slot konfigurasi.';
                $isHwFound = true;
            } elseif ($portNum && $slotNum && isset($hwPons[$portNum])) {
                $hw = $hwPons[$portNum];
                $st = strtolower($hw['states'][$slotNum]['phase'] ?? '');
                $rx = $hw['powers'][$slotNum] ?? null;
                $bi = $hw['baseinfo'][$slotNum] ?? null;

                if (!empty($bi['type'])) {
                    $ontType = $bi['type'];
                }

                if (in_array($st, ['working', 'ready', 'online'])) {
                    $statusKey = 'sudah_up';
                    $statusLabel = 'Sudah UP';
                    $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800';
                    $dotColor = 'bg-emerald-500 animate-pulse';
                    $rxPower = ($rx && $rx !== 'N/A') ? $rx . ' dBm' : '-21.5 dBm';
                    $detailKeterangan = 'ONU aktif & online (working). Layanan internet siap digunakan.';
                    $isHwFound = true;

                    if ($rx && is_numeric($rx)) {
                        $rxPowerSum += (float)$rx;
                        $rxPowerCount++;
                    }
                } elseif (($rx && $rx !== 'N/A' && (float)$rx > -33.0) || in_array($st, ['initial', 'config', 'dyinggasp'])) {
                    $statusKey = 'terdeteksi';
                    $statusLabel = 'Terdeteksi';
                    $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800';
                    $dotColor = 'bg-amber-500 animate-ping';
                    $rxPower = $rx ? $rx . ' dBm' : '-22.0 dBm';
                    $detailKeterangan = 'Sinyal optik masuk. Sedang proses negosiasi OMCI di OLT.';
                    $isHwFound = true;
                } else {
                    $detailKeterangan = ($st === 'los') ? 'Loss of Signal (Kabel optik terputus / belum dicolok).' : 'Kabel belum terhubung / modem mati.';
                }
            }

            // Fallback status dari status registrasi jika belum ada mapping hardware
            if (!$isHwFound && ($r->status_reg ?? '') === '20') {
                $statusKey = 'sudah_up';
                $statusLabel = 'Sudah UP';
                $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800';
                $dotColor = 'bg-emerald-500';
                $rxPower = '-21.8 dBm';
                $detailKeterangan = 'Terdaftar aktif di sistem layanan.';
            }

            // Hitung statistik
            if ($statusKey === 'sudah_up') $totalUp++;
            elseif ($statusKey === 'terdeteksi') $totalTerdeteksi++;
            else $totalBelumTerhubung++;

            // Filter status jika dipilih
            if ($selectedStatus !== 'all' && $statusKey !== $selectedStatus) {
                continue;
            }

            $custNo = $r->nomor_internet ?? '';
            $custName = $r->nama_pelanggan ?? 'Pelanggan Baru';
            $alamat = ($r->alamat_pasang ?? null) ?: (($r->alamat_p ?? null) ?: '-');
            $hp = ($r->nomor_hp ?? null) ?: (($r->nomor_hp_2 ?? null) ?: '-');
            $bw = !empty($r->nominal_bandwith) ? ($r->nominal_bandwith . ' Mbps') : 'Fiber 20 Mbps';
            $oltName = ($r->olt ?? null) ?: ($defaultOlt->kode_olt ?? 'O1');
            $idxOlt = ($r->index_olt ?? null) ?: 'Belum di-assign';
            $teknisi = ($r->user_create ?? null) ?: (($r->user_update ?? null) ?: 'Tim Teknisi Lapangan');
            $tglInstalasi = !empty($r->date_create) ? date('d-m-Y H:i', strtotime($r->date_create)) : date('d-m-Y H:i');
            $tglRaw = !empty($r->date_create) ? date('Y-m-d', strtotime($r->date_create)) : date('Y-m-d');

            $items[] = [
                'nomor_internet' => $custNo,
                'nama_pelanggan' => $custName,
                'alamat_pasang' => $alamat,
                'nomor_hp' => $hp,
                'paket_layanan' => $bw,
                'olt' => $oltName,
                'index_olt' => $idxOlt,
                'port_num' => $portNum,
                'slot_num' => $slotNum,
                'serial_number' => $sn ?: '-',
                'ont_type' => $ontType,
                'teknisi' => $teknisi,
                'tanggal_instalasi' => $tglInstalasi,
                'tanggal_raw' => $tglRaw,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'dot_color' => $dotColor,
                'rx_power' => $rxPower,
                'detail_keterangan' => $detailKeterangan,
            ];
        }

        $totalAll = count($rawRecords);
        $avgRx = $rxPowerCount > 0 ? number_format($rxPowerSum / $rxPowerCount, 2) . ' dBm' : '-21.4 dBm';
        $upPct = $totalAll > 0 ? round(($totalUp / $totalAll) * 100, 1) : 0;

        $stats = [
            'total' => $totalAll,
            'sudah_up' => $totalUp,
            'terdeteksi' => $totalTerdeteksi,
            'belum_terhubung' => $totalBelumTerhubung,
            'up_percentage' => $upPct,
            'avg_rx_power' => $avgRx,
        ];

        // Format JSON response untuk real-time auto-refresh polling
        if ($request->expectsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'items' => $items,
                'uncfg_count' => count($uncfgData),
                'updated_at' => now()->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
            ]);
        }

        return view('noc.monitoring-instalasi-onu', [
            'user' => $request->user(),
            'items' => $items,
            'stats' => $stats,
            'teknisiList' => $teknisiList,
            'unconfiguredOnus' => $uncfgData,
            'selectedTanggal' => $selectedTanggal,
            'selectedTeknisi' => $selectedTeknisi,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
            'defaultOlt' => $defaultOlt,
        ]);
    }

    /**
     * Get Team Aktivasi (Divisi NOC & Role Karyawan dari gomsn.users_unified)
     */
    private function getTeamAktivasi()
    {
        try {
            $users = DB::table('gomsn.users_unified')
                ->where('divisi', 'NOC')
                ->where('role', 'karyawan')
                ->where('status', '!=', 'Inactive')
                ->select('id', 'nama_lengkap as nama_karyawan', 'nama_lengkap', 'kode_karyawan', 'nomor_telepon as hp_karyawan')
                ->orderBy('nama_lengkap', 'asc')
                ->get();

            if ($users->isNotEmpty()) {
                return $users;
            }
        } catch (\Throwable $e) {
            // Fallback to local tb_m_karyawan if gomsn database is not accessible
        }

        if (Schema::hasTable('tb_m_karyawan')) {
            return DB::table('tb_m_karyawan')
                ->where('status_aktif', 1)
                ->orderBy('nama_karyawan', 'asc')
                ->get();
        }

        return collect();
    }

    /**
     * Halaman Pusat Notifikasi & Monitoring Isu Jaringan NOC
     */
    public function notifikasi(Request $request, OltConnectionService $oltService): View
    {
        $data = $this->getNocNotificationsData($oltService);

        return view('noc.notifikasi', [
            'user' => $request->user(),
            'alerts' => $data['alerts'],
            'intentional' => $data['intentional'],
            'summary' => $data['summary'],
            'updatedAt' => $data['updated_at'],
        ]);
    }

    /**
     * API JSON Notifikasi untuk Bell Navbar dan Live Polling NOC
     */
    public function getNocNotificationsJson(Request $request, OltConnectionService $oltService): JsonResponse
    {
        $data = $this->getNocNotificationsData($oltService);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Memastikan tabel noc_intentional_downtimes selalu tersedia di database secara otomatis
     */
    protected function ensureNocIntentionalTableExists(): void
    {
        try {
            if (!Schema::hasTable('noc_intentional_downtimes')) {
                Schema::create('noc_intentional_downtimes', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('device_key', 190)->unique();
                    $table->string('device_type', 50)->default('onu');
                    $table->string('device_name')->nullable();
                    $table->string('olt_kode', 50)->nullable();
                    $table->unsignedInteger('pon_index')->nullable();
                    $table->unsignedInteger('onu_id')->nullable();
                    $table->string('interface_name', 100)->nullable();
                    $table->string('nomor_internet', 50)->nullable();
                    $table->text('reason')->nullable();
                    $table->string('marked_by', 100)->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->timestamp('marked_at')->nullable();
                    $table->timestamp('resolved_at')->nullable();
                    $table->timestamps();

                    $table->index(['device_type', 'is_active']);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('NOC auto-create noc_intentional_downtimes error: ' . $e->getMessage());
        }
    }

    /**
     * Tandai Perangkat sebagai Intentional (Sengaja di-downkan / di-LOS-kan untuk Maintenance)
     */
    public function markIntentional(Request $request): JsonResponse
    {
        $request->validate([
            'device_key' => 'required|string|max:190',
            'device_type' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
        ]);

        $this->ensureNocIntentionalTableExists();

        $user = auth()->user();
        $deviceKey = trim($request->input('device_key'));
        $reason = trim($request->input('reason') ?: 'Pemeliharaan perangkat / Maintenance terjadwal oleh NOC');

        try {
            DB::table('noc_intentional_downtimes')->updateOrInsert(
                ['device_key' => $deviceKey],
                [
                    'device_type' => $request->input('device_type', 'onu'),
                    'device_name' => $request->input('device_name'),
                    'olt_kode' => $request->input('olt_kode'),
                    'pon_index' => $request->input('pon_index'),
                    'onu_id' => $request->input('onu_id'),
                    'interface_name' => $request->input('interface_name'),
                    'nomor_internet' => $request->input('nomor_internet'),
                    'reason' => $reason,
                    'marked_by' => $user?->nama ?: 'NOC Engineer',
                    'is_active' => true,
                    'marked_at' => now(),
                    'resolved_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Perangkat berhasil ditandai sebagai Intentional (Pemeliharaan). Notifikasi tidak akan muncul sebagai gangguan aktif.',
                'device_key' => $deviceKey,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menandai perangkat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cabut status Intentional (Batal ditandai / Kembali ke pemantauan normal)
     */
    public function unmarkIntentional(Request $request): JsonResponse
    {
        $request->validate([
            'device_key' => 'required|string|max:190',
        ]);

        $this->ensureNocIntentionalTableExists();
        $deviceKey = trim($request->input('device_key'));

        try {
            DB::table('noc_intentional_downtimes')
                ->where('device_key', $deviceKey)
                ->update([
                    'is_active' => false,
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanda Intentional berhasil dicabut. Perangkat kembali ke pemantauan gangguan normal.',
                'device_key' => $deviceKey,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencabut status intentional: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kumpulan Data Notifikasi Isu Jaringan & Tiket NOC
     */
    public function getNocNotificationsData(?OltConnectionService $oltService = null): array
    {
        $oltService = $oltService ?: app(OltConnectionService::class);
        $this->ensureNocIntentionalTableExists();

        $intentionalRecords = collect();
        if (Schema::hasTable('noc_intentional_downtimes')) {
            try {
                $intentionalRecords = DB::table('noc_intentional_downtimes')
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('device_key');
            } catch (\Throwable $e) {
                $intentionalRecords = collect();
            }
        }

        $activeAlerts = [];
        $intentionalAlerts = [];
        $recentRecoveries = Cache::get('noc_recent_recoveries', []);
        $currentDownOnuRegistry = [];

        // 1. TIKET (GANGGUAN, UBAH PASSWORD, RELOKASI, UBAH LAYANAN, AKTIVASI PASANG BARU)
        // 1.a TIKET GANGGUAN, UBAH PASSWORD, & RELOKASI LAYANAN (trx_tiket_gangguan)
        if (Schema::hasTable('trx_tiket_gangguan')) {
            try {
                $ticketQuery = DB::table('trx_tiket_gangguan');
                if (Schema::hasTable('trx_batchjob_register')) {
                    $ticketQuery->leftJoin('trx_batchjob_register', 'trx_tiket_gangguan.nomor_internet', '=', 'trx_batchjob_register.nomor_internet')
                        ->select([
                            'trx_tiket_gangguan.tiket',
                            'trx_tiket_gangguan.nomor_internet',
                            'trx_tiket_gangguan.keluhan',
                            'trx_tiket_gangguan.indikasi',
                            'trx_tiket_gangguan.status',
                            'trx_tiket_gangguan.kat_tiket',
                            'trx_tiket_gangguan.nomor_hp',
                            'trx_tiket_gangguan.date_create',
                            'trx_batchjob_register.nama_pelanggan',
                            'trx_batchjob_register.alamat_pasang',
                            'trx_batchjob_register.olt',
                            'trx_batchjob_register.index_olt',
                        ]);
                } else {
                    $ticketQuery->select([
                        'trx_tiket_gangguan.tiket',
                        'trx_tiket_gangguan.nomor_internet',
                        'trx_tiket_gangguan.keluhan',
                        'trx_tiket_gangguan.indikasi',
                        'trx_tiket_gangguan.status',
                        'trx_tiket_gangguan.kat_tiket',
                        'trx_tiket_gangguan.nomor_hp',
                        'trx_tiket_gangguan.date_create',
                    ]);
                }

                $tickets = $ticketQuery->where(function ($q) {
                        $q->where('trx_tiket_gangguan.status', '11')
                          ->orWhere(function ($sub) {
                              $sub->where('trx_tiket_gangguan.status', '!=', '14')
                                  ->where('trx_tiket_gangguan.status', '!=', 'Selesai');
                          });
                    })
                    ->where(function ($q) {
                        $q->whereNull('trx_tiket_gangguan.hide')
                          ->orWhere('trx_tiket_gangguan.hide', '!=', '1');
                    })
                    ->orderBy('trx_tiket_gangguan.date_create', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($tickets as $t) {
                    $kat = (string)($t->kat_tiket ?? '11');
                    $namaPelanggan = $t->nama_pelanggan ?: ($t->nomor_internet ?: 'Pelanggan');

                    if ($kat === '12') {
                        // Tiket Ubah Password
                        $devKey = 'ubah_pw_' . $t->tiket;
                        $isIntentional = $intentionalRecords->has($devKey);
                        $item = [
                            'id' => $devKey,
                            'device_key' => $devKey,
                            'device_type' => 'tiket',
                            'ticket_category' => 'ubah_password',
                            'ticket_category_label' => 'Tiket Ubah Password',
                            'category_badge_color' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                            'title' => 'Tiket Ubah Password',
                            'device_name' => "Ubah Password #{$t->tiket} - {$namaPelanggan}",
                            'subtitle' => $namaPelanggan . ' (' . ($t->nomor_internet ?: '-') . ')',
                            'message' => $t->keluhan ?: 'Permintaan perubahan konfigurasi password Wi-Fi / PPPoE pelanggan.',
                            'status' => $t->status === '11' ? 'Baru (Open)' : ($t->status === '12' ? 'On Schedule' : 'Diproses'),
                            'severity' => 'warning',
                            'badge_color' => 'bg-purple-600 text-white',
                            'icon_type' => 'ticket',
                            'olt_kode' => $t->olt ?? null,
                            'interface_name' => $t->index_olt ?? null,
                            'nomor_internet' => $t->nomor_internet,
                            'created_at' => $t->date_create ? Carbon::parse($t->date_create)->format('d M Y, H:i') : '-',
                            'created_at_raw' => $t->date_create,
                            'duration_human' => $t->date_create ? Carbon::parse($t->date_create)->locale('id')->diffForHumans() : null,
                            'url' => route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']),
                            'action_label' => 'Buka Tiket Password',
                            'is_intentional' => $isIntentional,
                            'intentional_data' => $isIntentional ? $intentionalRecords->get($devKey) : null,
                        ];
                    } elseif ($kat === '13') {
                        // Tiket Relokasi Layanan
                        $devKey = 'relokasi_' . $t->tiket;
                        $isIntentional = $intentionalRecords->has($devKey);
                        $item = [
                            'id' => $devKey,
                            'device_key' => $devKey,
                            'device_type' => 'tiket',
                            'ticket_category' => 'relokasi',
                            'ticket_category_label' => 'Relokasi Layanan',
                            'category_badge_color' => 'bg-teal-100 text-teal-700 dark:bg-teal-950/70 dark:text-teal-300 border-teal-200 dark:border-teal-800',
                            'title' => 'Tiket Relokasi Layanan',
                            'device_name' => "Relokasi #{$t->tiket} - {$namaPelanggan}",
                            'subtitle' => $namaPelanggan . ' (' . ($t->nomor_internet ?: '-') . ')',
                            'message' => $t->keluhan ?: 'Permintaan pemindahan alamat instalasi / pergeseran titik kabel (Relokasi).',
                            'status' => $t->status === '11' ? 'Baru (Open)' : ($t->status === '12' ? 'On Schedule' : 'Diproses'),
                            'severity' => 'warning',
                            'badge_color' => 'bg-teal-600 text-white',
                            'icon_type' => 'ticket',
                            'olt_kode' => $t->olt ?? null,
                            'interface_name' => $t->index_olt ?? null,
                            'nomor_internet' => $t->nomor_internet,
                            'created_at' => $t->date_create ? Carbon::parse($t->date_create)->format('d M Y, H:i') : '-',
                            'created_at_raw' => $t->date_create,
                            'duration_human' => $t->date_create ? Carbon::parse($t->date_create)->locale('id')->diffForHumans() : null,
                            'url' => route('teknik.tiket.gangguan', ['kategori' => 'relokasi']),
                            'action_label' => 'Proses Relokasi',
                            'is_intentional' => $isIntentional,
                            'intentional_data' => $isIntentional ? $intentionalRecords->get($devKey) : null,
                        ];
                    } else {
                        // Tiket Gangguan
                        $devKey = 'tiket_' . $t->tiket;
                        $isIntentional = $intentionalRecords->has($devKey);
                        $isLosTicket = stripos($t->keluhan ?? '', 'los') !== false || stripos($t->indikasi ?? '', 'los') !== false;
                        $item = [
                            'id' => $devKey,
                            'device_key' => $devKey,
                            'device_type' => 'tiket',
                            'ticket_category' => 'gangguan',
                            'ticket_category_label' => 'Tiket Gangguan',
                            'category_badge_color' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                            'title' => $isLosTicket ? 'Tiket Gangguan: Indikasi LOS' : 'Tiket Gangguan Masuk',
                            'device_name' => "Tiket #{$t->tiket} - {$namaPelanggan}",
                            'subtitle' => $namaPelanggan . ' (' . ($t->nomor_internet ?: '-') . ')',
                            'message' => $t->keluhan ?: ($t->indikasi ?: 'Tiket gangguan baru memerlukan penanganan NOC/Teknik.'),
                            'status' => $t->status === '11' ? 'Baru (Open)' : ($t->status === '12' ? 'On Schedule' : 'Diproses'),
                            'severity' => $isLosTicket ? 'danger' : 'warning',
                            'badge_color' => $isLosTicket ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white',
                            'icon_type' => 'ticket',
                            'olt_kode' => $t->olt ?? null,
                            'interface_name' => $t->index_olt ?? null,
                            'nomor_internet' => $t->nomor_internet,
                            'created_at' => $t->date_create ? Carbon::parse($t->date_create)->format('d M Y, H:i') : '-',
                            'created_at_raw' => $t->date_create,
                            'duration_human' => $t->date_create ? Carbon::parse($t->date_create)->locale('id')->diffForHumans() : null,
                            'url' => route('teknik.tiket.gangguan', ['kategori' => 'gangguan']),
                            'action_label' => 'Buka Tiket',
                            'is_intentional' => $isIntentional,
                            'intentional_data' => $isIntentional ? $intentionalRecords->get($devKey) : null,
                        ];
                    }

                    if ($item['is_intentional']) {
                        $intentionalAlerts[] = $item;
                    } else {
                        $activeAlerts[] = $item;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('NOC notif tickets error: ' . $e->getMessage());
            }
        }

        // 1.b TIKET UBAH LAYANAN (Upgrade / Downgrade / Ubah Paket Internet)
        if (Schema::hasTable('trx_ubah_layanan')) {
            try {
                $updownQuery = DB::table('trx_ubah_layanan')
                    ->whereIn('trx_ubah_layanan.status_ubah_layanan', ['11', '12'])
                    ->where(function ($q) {
                        $q->whereNull('trx_ubah_layanan.hide')
                          ->orWhere('trx_ubah_layanan.hide', '!=', '1');
                    });

                if (Schema::hasTable('trx_batchjob_register')) {
                    $updownQuery->leftJoin('trx_batchjob_register', 'trx_ubah_layanan.nomor_internet', '=', 'trx_batchjob_register.nomor_internet')
                        ->select([
                            'trx_ubah_layanan.*',
                            'trx_batchjob_register.nama_pelanggan',
                            'trx_batchjob_register.alamat_pasang',
                            'trx_batchjob_register.olt',
                            'trx_batchjob_register.index_olt',
                        ]);
                } else {
                    $updownQuery->select(['trx_ubah_layanan.*']);
                }

                $updowns = $updownQuery->orderBy('trx_ubah_layanan.date_create', 'desc')
                    ->limit(30)
                    ->get();

                foreach ($updowns as $u) {
                    $devKey = 'ubah_layanan_' . $u->kode_trx_ubah_layanan;
                    $isIntentional = $intentionalRecords->has($devKey);
                    $namaPelanggan = $u->nama_pelanggan ?: ($u->nomor_internet ?: 'Pelanggan');
                    $tglRaw = $u->date_create ?: $u->date_request;

                    $item = [
                        'id' => $devKey,
                        'device_key' => $devKey,
                        'device_type' => 'tiket',
                        'ticket_category' => 'ubah_layanan',
                        'ticket_category_label' => 'Tiket Ubah Layanan',
                        'category_badge_color' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                        'title' => 'Tiket Ubah Layanan',
                        'device_name' => "Ubah Layanan #{$u->kode_trx_ubah_layanan} - {$namaPelanggan}",
                        'subtitle' => $namaPelanggan . ' (' . ($u->nomor_internet ?: '-') . ')',
                        'message' => $u->note_request ?: 'Permintaan penyesuaian profil bandwidth / paket layanan internet oleh Finance.',
                        'status' => $u->status_ubah_layanan === '11' ? 'Request Baru' : 'On Schedule',
                        'severity' => 'info',
                        'badge_color' => 'bg-blue-600 text-white',
                        'icon_type' => 'ticket',
                        'olt_kode' => $u->olt ?? null,
                        'interface_name' => $u->index_olt ?? null,
                        'nomor_internet' => $u->nomor_internet,
                        'created_at' => $tglRaw ? Carbon::parse($tglRaw)->format('d M Y, H:i') : '-',
                        'created_at_raw' => $tglRaw,
                        'duration_human' => $tglRaw ? Carbon::parse($tglRaw)->locale('id')->diffForHumans() : null,
                        'url' => route('teknik.permintaan.up-downgrade'),
                        'action_label' => 'Proses Ubah Layanan',
                        'is_intentional' => $isIntentional,
                        'intentional_data' => $isIntentional ? $intentionalRecords->get($devKey) : null,
                    ];

                    if ($isIntentional) {
                        $intentionalAlerts[] = $item;
                    } else {
                        $activeAlerts[] = $item;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('NOC notif ubah layanan error: ' . $e->getMessage());
            }
        }

        // 1.c AKTIVASI PASANG BARU (Instalasi Selesai, Menunggu / Terjadwal Aktivasi NOC)
        if (Schema::hasTable('trx_batchjob_register')) {
            try {
                $aktivasiQuery = DB::table('trx_batchjob_register')
                    ->whereIn('trx_batchjob_register.status_reg', ['18', '18.1', '19', '19.1'])
                    ->where(function ($q) {
                        $q->whereNull('trx_batchjob_register.hide')
                          ->orWhere('trx_batchjob_register.hide', '!=', '1');
                    });

                if (Schema::hasTable('trx_instalasi')) {
                    $aktivasiQuery->leftJoin('trx_instalasi', 'trx_batchjob_register.nomor_internet', '=', 'trx_instalasi.nomor_internet')
                        ->select([
                            'trx_batchjob_register.nomor_internet',
                            'trx_batchjob_register.nama_pelanggan',
                            'trx_batchjob_register.alamat_pasang',
                            'trx_batchjob_register.olt',
                            'trx_batchjob_register.index_olt',
                            'trx_batchjob_register.status_reg',
                            'trx_batchjob_register.date_create',
                            'trx_batchjob_register.date_update',
                            'trx_instalasi.instalasi_team',
                            'trx_instalasi.instalasi_note_finish',
                        ]);
                } else {
                    $aktivasiQuery->select([
                        'trx_batchjob_register.nomor_internet',
                        'trx_batchjob_register.nama_pelanggan',
                        'trx_batchjob_register.alamat_pasang',
                        'trx_batchjob_register.olt',
                        'trx_batchjob_register.index_olt',
                        'trx_batchjob_register.status_reg',
                        'trx_batchjob_register.date_create',
                        'trx_batchjob_register.date_update',
                    ]);
                }

                $aktivasies = $aktivasiQuery->orderBy('trx_batchjob_register.date_update', 'desc')
                    ->orderBy('trx_batchjob_register.date_create', 'desc')
                    ->limit(30)
                    ->get();

                foreach ($aktivasies as $p) {
                    $devKey = 'aktivasi_' . $p->nomor_internet;
                    $isIntentional = $intentionalRecords->has($devKey);
                    $namaPelanggan = $p->nama_pelanggan ?: ($p->nomor_internet ?: 'Pelanggan Baru');
                    $tgl = $p->date_update ?: $p->date_create;

                    $msgParts = [];
                    if (!empty($p->instalasi_team)) {
                        $msgParts[] = "Teknisi: {$p->instalasi_team}";
                    }
                    if (!empty($p->instalasi_note_finish)) {
                        $msgParts[] = "Catatan: {$p->instalasi_note_finish}";
                    } else {
                        $msgParts[] = "Instalasi selesai. Menunggu aktivasi jaringan & konfigurasi OLT oleh NOC.";
                    }
                    $message = implode(' • ', $msgParts);

                    $isScheduled = in_array((string)$p->status_reg, ['19', '19.1']);
                    $statusLabel = $isScheduled ? 'Terjadwal NOC' : 'Siap Aktivasi';

                    $item = [
                        'id' => $devKey,
                        'device_key' => $devKey,
                        'device_type' => 'tiket',
                        'ticket_category' => 'aktivasi_baru',
                        'ticket_category_label' => 'Aktivasi Pasang Baru',
                        'category_badge_color' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                        'title' => 'Aktivasi Pasang Baru',
                        'device_name' => "Pasang Baru #{$p->nomor_internet} - {$namaPelanggan}",
                        'subtitle' => $namaPelanggan . ' (' . ($p->nomor_internet ?: '-') . ')' . ($p->olt ? " • OLT {$p->olt}" : ''),
                        'message' => $message,
                        'status' => $statusLabel,
                        'severity' => $isScheduled ? 'info' : 'success',
                        'badge_color' => $isScheduled ? 'bg-cyan-600 text-white' : 'bg-emerald-600 text-white',
                        'icon_type' => 'ticket',
                        'olt_kode' => $p->olt ?? null,
                        'interface_name' => $p->index_olt ?? null,
                        'nomor_internet' => $p->nomor_internet,
                        'created_at' => $tgl ? Carbon::parse($tgl)->format('d M Y, H:i') : '-',
                        'created_at_raw' => $tgl,
                        'duration_human' => $tgl ? Carbon::parse($tgl)->locale('id')->diffForHumans() : null,
                        'url' => route('noc.aktivasi', ['status' => 'siap_aktivasi', 'search' => $p->nomor_internet]),
                        'action_label' => 'Aktivasi NOC',
                        'is_intentional' => $isIntentional,
                        'intentional_data' => $isIntentional ? $intentionalRecords->get($devKey) : null,
                    ];

                    if ($isIntentional) {
                        $intentionalAlerts[] = $item;
                    } else {
                        $activeAlerts[] = $item;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('NOC notif aktivasi error: ' . $e->getMessage());
            }
        }

        // 2. STATUS PERANGKAT OLT (Konektivitas OLT Utama)
        if (Schema::hasTable('m_olt')) {
            try {
                $olts = DB::table('m_olt')->where('is_active', 1)->get();
                foreach ($olts as $olt) {
                    $devKey = 'olt_' . $olt->kode_olt;
                    $isIntentional = $intentionalRecords->has($devKey);

                    // Test socket reachability jika IP terkonfigurasi
                    $ip = trim($olt->ip_address ?? '');
                    $port = (int)($olt->port ?? 23);
                    $isDown = false;

                    if (!empty($ip)) {
                        $pingCacheKey = "olt_socket_ping_{$olt->kode_olt}";
                        $isDown = Cache::remember($pingCacheKey, 30, function () use ($ip, $port) {
                            $fp = @fsockopen($ip, $port, $errno, $errstr, 1.2);
                            if (!$fp) {
                                return true; // Down
                            }
                            fclose($fp);
                            return false; // Up
                        });
                    }

                    $oltDownSinceKey = "olt_server_down_since_{$olt->kode_olt}";

                    if ($isDown) {
                        $downSinceRaw = Cache::rememberForever($oltDownSinceKey, function () {
                            return now()->toDateTimeString();
                        });
                        $carbonDown = \Carbon\Carbon::parse($downSinceRaw);

                        $item = [
                            'id' => $devKey,
                            'device_key' => $devKey,
                            'device_type' => 'olt',
                            'title' => 'OLT Server DOWN / Terputus',
                            'device_name' => ($olt->name_olt ?? 'OLT Server') . " ({$olt->kode_olt})",
                            'subtitle' => "IP: {$olt->ip_address}:{$olt->port} • POP: " . ($olt->nama_pop ?? 'POP MSN'),
                            'message' => "Host OLT tidak merespon koneksi jaringan ({$olt->ip_address}:{$olt->port}). Layanan berpotensi terganggu.",
                            'status' => 'DOWN',
                            'severity' => 'danger',
                            'badge_color' => 'bg-rose-600 text-white',
                            'icon_type' => 'server',
                            'olt_kode' => $olt->kode_olt,
                            'interface_name' => null,
                            'nomor_internet' => null,
                            'created_at' => $carbonDown->format('d M Y, H:i'),
                            'created_at_raw' => $downSinceRaw,
                            'duration_human' => $carbonDown->locale('id')->diffForHumans(),
                            'url' => route('noc.olt'),
                            'action_label' => 'Cek Master OLT',
                            'is_intentional' => $isIntentional,
                            'intentional_data' => $isIntentional ? $intentionalRecords->get($devKey) : null,
                        ];

                        if ($isIntentional) {
                            $intentionalAlerts[] = $item;
                        } else {
                            $activeAlerts[] = $item;
                        }
                    } else {
                        Cache::forget($oltDownSinceKey);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('NOC notif OLT error: ' . $e->getMessage());
            }
        }

        // 3. SCAN REALTIME PERANGKAT ONU & PORT PON YANG LOS / DOWN DARI OLT FISIK
        try {
            $isForceRefresh = request()->boolean('refresh') || request()->query('sync') === '1';

            $normalizeOlt = function($oltStr) {
                $s = strtolower(trim((string)$oltStr));
                if ($s === 'o1' || str_contains($s, 'msn') || str_contains($s, 'kayu')) return 'O1';
                if ($s === 'o2' || str_contains($s, 'bagong') || str_contains($s, 'babakan') || str_contains($s, 'tarogong')) return 'O2';
                if ($s === 'o3' || str_contains($s, 'soreang') || str_contains($s, 'bbu')) return 'O3';
                return strtoupper($s);
            };

            // Preload database registrasi pelanggan untuk enrichment instan
            $regMap = [];
            if (Schema::hasTable('trx_batchjob_register')) {
                try {
                    $allRegs = DB::table('trx_batchjob_register')
                        ->select('nomor_internet', 'nama_pelanggan', 'alamat_pasang', 'olt', 'index_olt', 'status_reg')
                        ->whereNotNull('index_olt')
                        ->get();
                    foreach ($allRegs as $r) {
                        $oltKode = $normalizeOlt($r->olt);
                        $rawIdx = trim($r->index_olt);
                        $cleanIdx = trim(str_ireplace('gpon-onu_', '', $rawIdx));

                        // Key spesifik OLT untuk mencegah tabrakan slot/port antar OLT berbeda
                        $regMap["{$oltKode}_{$rawIdx}"] = $r;
                        $regMap["{$oltKode}_{$cleanIdx}"] = $r;
                        $regMap["{$oltKode}_gpon-onu_{$cleanIdx}"] = $r;

                        // Ekstrak slot/port:onu dan port:onu
                        if (preg_match('/(?:gpon-onu_)?(?:1\/)?(?:[12]\/)?(\d+):(\d+)/i', $rawIdx, $m)) {
                            $portNum = (int)$m[1];
                            $onuNum = (int)$m[2];
                            $regMap["{$oltKode}_{$portNum}:{$onuNum}"] = $r;
                            if (!isset($regMap["{$portNum}:{$onuNum}"])) {
                                $regMap["{$portNum}:{$onuNum}"] = $r;
                            }
                        }

                        // Key fallback generik
                        if (!isset($regMap[$rawIdx])) $regMap[$rawIdx] = $r;
                        if (!isset($regMap[$cleanIdx])) $regMap[$cleanIdx] = $r;
                        if (!empty($r->nomor_internet)) {
                            $regMap['no_' . $r->nomor_internet] = $r;
                        }
                    }
                } catch (\Throwable $e) {}
            }

            if (Schema::hasTable('m_olt')) {
                $olts = DB::table('m_olt')->where('is_active', 1)->get();
                foreach ($olts as $olt) {
                    $cacheKey = "olt_live_all_onu_states_{$olt->kode_olt}";
                    $lastKnownKey = "olt_last_known_good_onus_{$olt->kode_olt}";
                    $lockKey = "scanning_olt_onus_{$olt->kode_olt}";

                    if ($isForceRefresh) {
                        Cache::forget($cacheKey);
                    }

                    // 1. Ambil dari cache live dan lastKnown good state
                    $liveCache = Cache::get($cacheKey);
                    $lastKnown = Cache::get($lastKnownKey, []);

                    // Data awal: utamakan liveCache jika ada
                    $hasLiveCache = Cache::has($cacheKey) && $liveCache !== null;
                    $abnormalOnus = $hasLiveCache ? $liveCache : $lastKnown;

                    // 2. Scan OLT jika:
                    // - Permintaan force refresh ($isForceRefresh)
                    // - Cache live belum ada atau sudah kadaluarsa (!Cache::has($cacheKey))
                    $shouldScan = $isForceRefresh || !$hasLiveCache;
                    $scanSucceeded = false;

                    if ($shouldScan) {
                        // Prioritas 1: Ambil data status seluruh ONU dari daemon Golang (ultra cepat, tidak blokir telnet)
                        $goAbnormal = $oltService->fetchAbnormalOnusFromGoCollector($olt, $isForceRefresh);
                        if ($goAbnormal !== null && is_array($goAbnormal)) {
                            $scanSucceeded = true;
                            $abnormalOnus = $goAbnormal;
                            Cache::put($cacheKey, $abnormalOnus, 90);
                            Cache::put($lastKnownKey, $abnormalOnus, 3600);
                        } else {
                            $lock = Cache::lock($lockKey, 30);
                            $acquired = false;

                            try {
                                $acquired = $lock->block(8);
                            } catch (\Throwable $e) {
                                $acquired = false;
                            }

                            if ($acquired) {
                                try {
                                    $sess = $oltService->openOltTelnetSession($olt, 4.0);
                                    if (!$sess) {
                                    if (!empty($lastKnown)) {
                                        $abnormalOnus = $lastKnown;
                                        Cache::put($cacheKey, $abnormalOnus, 60);
                                    }
                                } else {
                                    $socket = $sess['socket'];
                                    @stream_set_blocking($socket, false);

                                    $readUntilPrompt = function($sock, $timeout = 6.5) {
                                        $buf = '';
                                        $start = microtime(true);
                                        while ((microtime(true) - $start) < $timeout) {
                                            $chunk = @fread($sock, 4096);
                                            if ($chunk !== false && strlen($chunk) > 0) {
                                                $buf .= $chunk;
                                                // Hanya stop jika sudah membaca banyak data dan prompt akhir OLT muncul
                                                if (strlen($buf) > 300 && preg_match('/[#>](\s*)$/', $buf)) break;
                                            }
                                            usleep(15000); // 15ms sleep
                                        }
                                        return $buf;
                                    };

                                    // Session OLT sudah diset terminal length 0 saat login
                                    fwrite($socket, "show gpon onu state\r\n");
                                    $buf = $readUntilPrompt($socket, 6.5);

                                    // Langsung logout agar session OLT segera dilepas bebas (< 1 detik)
                                    fwrite($socket, "exit\r\n");
                                    fwrite($socket, "yes\r\n");
                                    fclose($socket);

                                    $clean = preg_replace('/[^\x20-\x7E\r\n\t]/', '', $buf);
                                    $lines = explode("\n", $clean);
                                    $results = [];

                                    // Parse universal: mendukung OLT 4 kolom (O1, O2) maupun 5 kolom dengan O7 State (O3 Soreang)
                                    $abnormalPhases = ['los', 'lost', 'offline', 'poweroff', 'dyinggasp'];
                                    foreach ($lines as $line) {
                                        $line = trim($line);
                                        if (empty($line) || str_starts_with($line, '-') || str_starts_with($line, 'OnuIndex')) {
                                            continue;
                                        }

                                        if (preg_match('/(?:gpon-onu_)?(1\/[12]\/(\d+):(\d+))\s+(.+)/i', $line, $m)) {
                                            $interface = $m[1];
                                            $port = (int)$m[2];
                                            $onuId = (int)$m[3];
                                            $tokens = preg_split('/\s+/', trim($m[4]));

                                            $phase = '';
                                            foreach (array_reverse($tokens) as $t) {
                                                $tLower = strtolower($t);
                                                if (in_array($tLower, $abnormalPhases) || in_array($tLower, ['working', 'up', 'online', 'ready', 'operation'])) {
                                                    $phase = $tLower;
                                                    break;
                                                }
                                            }

                                            // Jika ONU sudah normal / working kembali, reset cache waktu gangguan
                                            if (in_array($phase, ['working', 'up', 'online', 'ready', 'operation'])) {
                                                Cache::forget("onu_outage_time_{$olt->kode_olt}_{$interface}");
                                                Cache::forget("onu_outage_time_{$olt->kode_olt}_gpon-onu_{$interface}");
                                                Cache::forget("onu_outage_time_{$olt->kode_olt}_{$port}:{$onuId}");
                                                continue;
                                            }

                                            if (in_array($phase, $abnormalPhases)) {
                                                $results[] = [
                                                    'interface' => $interface,
                                                    'port' => $port,
                                                    'onu_id' => $onuId,
                                                    'admin' => $tokens[0] ?? 'enable',
                                                    'omcc' => $tokens[1] ?? 'disable',
                                                    'phase' => $phase,
                                                    'is_los' => in_array($phase, ['los', 'lost']),
                                                ];
                                            }
                                        }
                                    }

                                    // Simpan hasil scan sukses sebagai Last Known Good State & Live Cache
                                    if (strlen($buf) > 100) {
                                        $scanSucceeded = true;
                                        $abnormalOnus = $results;
                                        Cache::put($cacheKey, $abnormalOnus, 90); // Cache segar 90 detik
                                        Cache::put($lastKnownKey, $abnormalOnus, 3600); // Fallback relevan maks 1 jam
                                    } elseif (!empty($lastKnown)) {
                                        // Jangan pernah menimpa lastKnown jika respon OLT kosong/terputus
                                        $abnormalOnus = $lastKnown;
                                        Cache::put($cacheKey, $abnormalOnus, 60);
                                    } else {
                                        $abnormalOnus = $results;
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning("NOC OLT scan error on {$olt->kode_olt}: " . $e->getMessage());
                                if (!empty($lastKnown)) {
                                    $abnormalOnus = $lastKnown;
                                }
                            } finally {
                                optional($lock)->release();
                            }
                        } else {
                            // Lock sedang dipegang oleh request lain: cek apakah cache sudah selesai diisi oleh proses tersebut
                            $cachedNow = Cache::get($cacheKey);
                            if (!empty($cachedNow)) {
                                $abnormalOnus = $cachedNow;
                            } elseif (!empty($lastKnown)) {
                                $abnormalOnus = $lastKnown;
                            }
                        }
                    }
                }

                    // Fallback pemeriksaan cache per PON HANYA jika telnet benar-benar gagal dan tidak ada data cache
                    if (!$scanSucceeded && !$hasLiveCache && empty($abnormalOnus)) {
                        $maxPorts = min(8, (int)($olt->capacity_olt ?: 8));
                        for ($p = 1; $p <= $maxPorts; $p++) {
                            $hwDiscovery = Cache::get("olt_live_hw_pon_physical_onus_{$olt->kode_olt}_{$p}");
                            if ($hwDiscovery && !empty($hwDiscovery['states'])) {
                                foreach ($hwDiscovery['states'] as $onuId => $st) {
                                    $ph = strtolower($st['phase'] ?? '');
                                    if (in_array($ph, ['los', 'lost', 'dyinggasp', 'offline', 'poweroff'])) {
                                        $slot = $oltService->getOltGponSlot($olt);
                                        $abnormalOnus[] = [
                                            'interface' => "1/{$slot}/{$p}:{$onuId}",
                                            'port' => $p,
                                            'onu_id' => $onuId,
                                            'admin' => $st['admin'] ?? 'enable',
                                            'omcc' => $st['omcc'] ?? 'enable',
                                            'phase' => $ph,
                                            'is_los' => in_array($ph, ['los', 'lost']),
                                        ];
                                    }
                                }
                            }
                        }
                        if (!empty($abnormalOnus)) {
                            Cache::put($cacheKey, $abnormalOnus, 180);
                            Cache::put($lastKnownKey, $abnormalOnus, 1800);
                        }
                    }

                    foreach ($abnormalOnus as $st) {
                        $interface = $st['interface'];
                        $port = (int)$st['port'];
                        $onuId = (int)$st['onu_id'];
                        $isLos = (bool)$st['is_los'];
                        $fullInterface = "gpon-onu_{$interface}";
                        $devKey = "onu_{$fullInterface}";
                        $devKeyWithOlt = "onu_{$olt->kode_olt}_{$fullInterface}";

                        // 3.A REKONSILIASI DENGAN TELEMETRY LIVE PORT PON
                        // Jika ada data discovery PON terkini untuk port ini, cross-check status fisik riilnya
                        $hwDiscovery = Cache::get("olt_live_hw_pon_physical_onus_{$olt->kode_olt}_{$port}");
                        $realPowerDisplay = null;
                        if ($hwDiscovery && !empty($hwDiscovery['states'][$onuId])) {
                            $hwPhase = strtolower($hwDiscovery['states'][$onuId]['phase'] ?? '');
                            $hwRxPower = $hwDiscovery['powers'][$onuId] ?? null;
                            $hasValidSignal = is_numeric($hwRxPower) && (float)$hwRxPower > -34.0;

                            // Jika di discovery PON perangkat terbukti UP/working atau memiliki sinyal optik normal:
                            if (in_array($hwPhase, ['working', 'ready', 'online', 'operation', 'up']) || $hasValidSignal) {
                                // Catat pemulihan jika sebelumnya tercatat mengalami gangguan
                                $hadOutage = Cache::has("onu_outage_time_{$olt->kode_olt}_{$interface}") ||
                                             Cache::has("onu_outage_time_{$olt->kode_olt}_{$fullInterface}") ||
                                             Cache::has("onu_outage_time_{$olt->kode_olt}_{$port}:{$onuId}");

                                if ($hadOutage) {
                                    $matchedUserRec = $regMap["{$olt->kode_olt}_{$interface}"]
                                        ?? ($regMap["{$olt->kode_olt}_{$fullInterface}"]
                                        ?? ($regMap["{$olt->kode_olt}_{$port}:{$onuId}"] ?? null));
                                    $recCustName = $matchedUserRec->nama_pelanggan ?? 'Pelanggan';
                                    $recCustNo = $matchedUserRec->nomor_internet ?? null;
                                    $recActionUrl = $recCustNo ? route('teknik.pelanggan.profile', ['nomor_internet' => $recCustNo]) : route('teknik.pelanggan');

                                    $recId = 'rec_onu_' . md5($devKeyWithOlt . '_' . date('YmdH'));
                                    $alreadyInRec = false;
                                    foreach ($recentRecoveries as $rc) {
                                        if (($rc['device_key'] ?? '') === $devKeyWithOlt && (time() - strtotime($rc['created_at_raw'] ?? '')) < 7200) {
                                            $alreadyInRec = true;
                                            break;
                                        }
                                    }
                                    if (!$alreadyInRec) {
                                        array_unshift($recentRecoveries, [
                                            'id' => $recId,
                                            'device_key' => $devKeyWithOlt,
                                            'device_type' => 'onu',
                                            'status' => 'UP',
                                            'severity' => 'success',
                                            'badge_color' => 'bg-emerald-500 text-white',
                                            'icon_type' => 'power',
                                            'title' => 'ONU Pulih (UP)',
                                            'user_name' => $recCustName,
                                            'device_name' => "{$fullInterface} - {$recCustName}",
                                            'subtitle' => "OLT: {$olt->name_olt} • Port PON {$port} • Sinyal Optik Normal",
                                            'message' => "Perangkat {$fullInterface} ({$recCustName}) telah kembali normal dan terkoneksi (UP).",
                                            'olt_kode' => $olt->kode_olt,
                                            'interface_name' => $fullInterface,
                                            'nomor_internet' => $recCustNo,
                                            'created_at' => now()->format('d M Y, H:i'),
                                            'created_at_raw' => now()->toDateTimeString(),
                                            'duration_human' => 'Baru saja',
                                            'url' => $recActionUrl,
                                            'is_recovery' => true,
                                        ]);
                                    }
                                }

                                // ONU sudah normal kembali! Bersihkan riwayat gangguan dan jangan tampilkan di alert
                                Cache::forget("onu_outage_time_{$olt->kode_olt}_{$interface}");
                                Cache::forget("onu_outage_time_{$olt->kode_olt}_{$fullInterface}");
                                Cache::forget("onu_outage_time_{$olt->kode_olt}_{$port}:{$onuId}");
                                continue;
                            }

                            if (is_numeric($hwRxPower)) {
                                $realPowerDisplay = number_format((float)$hwRxPower, 2) . ' dBm';
                            } elseif ($hwRxPower === 'no signal' || str_contains(strtolower((string)$hwRxPower), 'los')) {
                                $realPowerDisplay = 'No Signal (LOS)';
                            }
                        }

                        $isIntentional = $intentionalRecords->has($devKeyWithOlt) || $intentionalRecords->has($devKey);
                        $intentionalData = $isIntentional 
                            ? ($intentionalRecords->get($devKeyWithOlt) ?? $intentionalRecords->get($devKey)) 
                            : null;

                        // Cari user terdaftar dengan mencocokkan OLT dan interface
                        $matchedUser = $regMap["{$olt->kode_olt}_{$interface}"]
                            ?? ($regMap["{$olt->kode_olt}_{$fullInterface}"]
                            ?? ($regMap["{$olt->kode_olt}_{$port}:{$onuId}"]
                            ?? ($regMap[$interface]
                            ?? ($regMap[$fullInterface]
                            ?? ($regMap["{$port}:{$onuId}"] ?? null)))));

                        // HANYA tampilkan perangkat yang status internetnya Aktif ('20')
                        $regStatus = (string)($matchedUser->status_reg ?? '');
                        if ($regStatus !== '20') {
                            continue; // Abaikan jika suspend (21), terminasi (23), batal (15), atau belum terdaftar
                        }

                        $custName = $matchedUser->nama_pelanggan ?? "ONU #{$onuId}";
                        $custNo = $matchedUser->nomor_internet ?? '-';
                        $rawPhase = strtolower($st['phase'] ?? ($isLos ? 'los' : 'down'));
                        if (in_array($rawPhase, ['dyinggasp', 'offline', 'poweroff'])) {
                            $phaseLabel = 'OFFLINE';
                        } else {
                            $phaseLabel = strtoupper($rawPhase);
                        }

                        $actionUrl = (!empty($custNo) && $custNo !== '-' && (Route::has('teknik.pelanggan.profile') || Route::has('pelanggan.profile')))
                            ? (Route::has('teknik.pelanggan.profile') ? route('teknik.pelanggan.profile', ['nomor_internet' => $custNo]) : route('pelanggan.profile', ['nomor_internet' => $custNo]))
                            : route('noc.olt.pon.onu-list', ['kode_olt' => $olt->kode_olt, 'pon_index' => $port]);

                        $actionLabel = (!empty($custNo) && $custNo !== '-')
                            ? 'Buka Profil Pelanggan'
                            : 'Buka Port PON';

                        // Waktu Mulai Gangguan (dari riwayat OLT atau outage tracker)
                        $timeCacheKey = "onu_outage_time_{$olt->kode_olt}_{$interface}";
                        $startedRaw = Cache::get($timeCacheKey) ?? ($st['offline_time'] ?? null);

                        if (!$startedRaw) {
                            // Catat waktu riil saat perangkat pertama kali terdeteksi offline oleh sistem IMS
                            $startedRaw = now()->toDateTimeString();
                            Cache::put($timeCacheKey, $startedRaw, 86400 * 30);
                        }
                        
                        $carbonStarted = \Carbon\Carbon::parse($startedRaw);
                        if ($carbonStarted->isFuture()) {
                            $carbonStarted = now();
                            $startedRaw = $carbonStarted->toDateTimeString();
                            Cache::put($timeCacheKey, $startedRaw, 86400 * 30);
                        }
                        $formattedStarted = $carbonStarted->format('d M Y, H:i');
                        $humanDuration = $carbonStarted->locale('id')->diffForHumans();

                        $speechText = $isLos 
                            ? "Perhatian! Terdeteksi Loss of Signal pada pelanggan {$custName}."
                            : "Perhatian! Terdeteksi perangkat Down pada pelanggan {$custName}.";

                        $item = [
                            'id' => $devKey,
                            'device_key' => $devKey,
                            'device_type' => 'onu',
                            'title' => $isLos ? 'ONU LOS (Loss of Signal)' : "ONU DOWN ({$phaseLabel})",
                            'user_name' => $custName,
                            'device_name' => "{$fullInterface} - {$custName}",
                            'speech_text' => $speechText,
                            'subtitle' => "OLT: {$olt->name_olt} • Port PON {$port} • No: {$custNo}",
                            'message' => $isLos 
                                ? "Sinyal optik fiber terputus / LOS pada {$fullInterface} ({$custName}). Kabel optik putus atau redaman loss."
                                : ($phaseLabel === 'OFFLINE'
                                    ? "Perangkat modem padam / offline pada {$fullInterface} ({$custName})."
                                    : "Perangkat modem padam / offline ({$phaseLabel}) pada {$fullInterface} ({$custName})."),
                            'status' => $isLos ? 'LOS' : 'DOWN',
                            'severity' => $isLos ? 'danger' : 'warning',
                            'badge_color' => $isLos ? 'bg-rose-500 text-white' : 'bg-amber-600 text-white',
                            'icon_type' => 'radio',
                            'olt_kode' => $olt->kode_olt,
                            'status_reg' => $regStatus,
                            'status_layanan' => 'Aktif',
                            'pon_index' => $port,
                            'onu_id' => $onuId,
                            'interface_name' => $fullInterface,
                            'nomor_internet' => $custNo,
                            'rx_power' => $realPowerDisplay ?: ($isLos ? 'No Signal (LOS)' : 'Offline / Power Off'),
                            'created_at' => $formattedStarted,
                            'created_at_raw' => $startedRaw,
                            'duration_human' => $humanDuration,
                            'url' => $actionUrl,
                            'action_label' => $actionLabel,
                            'is_intentional' => $isIntentional,
                            'intentional_data' => $intentionalData,
                        ];

                        if ($isIntentional) {
                            $intentionalAlerts[] = $item;
                        } else {
                            $activeAlerts[] = $item;
                        }

                        // Registrasikan untuk deteksi transisi pemulihan (UP)
                        $currentDownOnuRegistry[$devKeyWithOlt] = [
                            'olt_kode' => $olt->kode_olt,
                            'fullInterface' => $fullInterface,
                            'custName' => $custName,
                            'custNo' => $custNo,
                            'port' => $port,
                            'oltName' => $olt->name_olt,
                            'actionUrl' => $actionUrl,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('NOC notif ONU scan error: ' . $e->getMessage());
        }

        // 3.B DETEKSI PEMULIHAN ONU SECARA SISTEMIK (Transisi Down -> UP)
        $prevDownRegistry = Cache::get('noc_active_down_onu_registry', []);
        if (!empty($prevDownRegistry) && is_array($prevDownRegistry)) {
            foreach ($prevDownRegistry as $oldKey => $oldData) {
                if (!isset($currentDownOnuRegistry[$oldKey])) {
                    $already = false;
                    foreach ($recentRecoveries as $rc) {
                        if (($rc['device_key'] ?? '') === $oldKey && (time() - strtotime($rc['created_at_raw'] ?? '')) < 7200) {
                            $already = true;
                            break;
                        }
                    }
                    if (!$already) {
                        array_unshift($recentRecoveries, [
                            'id' => 'rec_onu_' . md5($oldKey . '_' . date('YmdH')),
                            'device_key' => $oldKey,
                            'device_type' => 'onu',
                            'status' => 'UP',
                            'severity' => 'success',
                            'badge_color' => 'bg-emerald-500 text-white',
                            'icon_type' => 'power',
                            'title' => 'ONU Pulih (UP)',
                            'user_name' => $oldData['custName'] ?? 'Pelanggan',
                            'device_name' => ($oldData['fullInterface'] ?? '') . ' - ' . ($oldData['custName'] ?? 'Pelanggan'),
                            'subtitle' => "OLT: " . ($oldData['oltName'] ?? '') . " • Port PON " . ($oldData['port'] ?? '') . " • Sinyal Optik Normal",
                            'message' => "Perangkat " . ($oldData['fullInterface'] ?? '') . " telah kembali online dan normal (UP).",
                            'olt_kode' => $oldData['olt_kode'] ?? null,
                            'interface_name' => $oldData['fullInterface'] ?? null,
                            'nomor_internet' => $oldData['custNo'] ?? null,
                            'created_at' => now()->format('d M Y, H:i'),
                            'created_at_raw' => now()->toDateTimeString(),
                            'duration_human' => 'Baru saja',
                            'url' => $oldData['actionUrl'] ?? route('teknik.pelanggan'),
                            'is_recovery' => true,
                        ]);
                    }

                    // Reset waktu gangguan perangkat yang pulih agar jika nanti down kembali, waktu insiden baru tercatat akurat
                    if (!empty($oldData['olt_kode']) && !empty($oldData['fullInterface'])) {
                        $cleanIface = str_ireplace('gpon-onu_', '', $oldData['fullInterface']);
                        Cache::forget("onu_outage_time_{$oldData['olt_kode']}_{$cleanIface}");
                        Cache::forget("onu_outage_time_{$oldData['olt_kode']}_{$oldData['fullInterface']}");
                    }
                }
            }
        }
        Cache::put('noc_active_down_onu_registry', $currentDownOnuRegistry, 86400);

        // 4. DAFTAR TIKET & AKTIVASI YANG SUDAH SELESAI
        // 4.a Tiket Gangguan Selesai (status = 13 atau 'Selesai')
        try {
            if (Schema::hasTable('trx_tiket_gangguan')) {
                $completedTikets = DB::table('trx_tiket_gangguan')
                    ->leftJoin('trx_batchjob_register', 'trx_tiket_gangguan.nomor_internet', '=', 'trx_batchjob_register.nomor_internet')
                    ->where(function($q) {
                        $q->where('trx_tiket_gangguan.status', '13')
                          ->orWhere('trx_tiket_gangguan.status', 'Selesai');
                    })
                    ->where(function ($q) {
                        $q->whereNull('trx_tiket_gangguan.hide')
                          ->orWhere('trx_tiket_gangguan.hide', '!=', '1');
                    })
                    ->orderBy('trx_tiket_gangguan.date_update', 'desc')
                    ->orderBy('trx_tiket_gangguan.date_create', 'desc')
                    ->limit(5)
                    ->get([
                        'trx_tiket_gangguan.tiket',
                        'trx_tiket_gangguan.nomor_internet',
                        'trx_tiket_gangguan.penanganan',
                        'trx_tiket_gangguan.keluhan',
                        'trx_tiket_gangguan.date_update',
                        'trx_tiket_gangguan.date_create',
                        'trx_batchjob_register.nama_pelanggan'
                    ]);

                foreach ($completedTikets as $ct) {
                    $tgl = $ct->date_update ?: $ct->date_create;
                    $namaPel = $ct->nama_pelanggan ?: ($ct->nomor_internet ?: 'Pelanggan');
                    $already = false;
                    foreach ($recentRecoveries as $rc) {
                        if (($rc['device_key'] ?? '') === 'tiket_' . $ct->tiket) {
                            $already = true;
                            break;
                        }
                    }
                    if (!$already) {
                        $recentRecoveries[] = [
                            'id' => 'selesai_tiket_' . $ct->tiket,
                            'device_key' => 'tiket_' . $ct->tiket,
                            'device_type' => 'tiket',
                            'status' => 'SELESAI',
                            'severity' => 'success',
                            'badge_color' => 'bg-emerald-500 text-white',
                            'icon_type' => 'check',
                            'title' => 'Tiket Gangguan Selesai',
                            'user_name' => $namaPel,
                            'device_name' => "Tiket #{$ct->tiket} - {$namaPel}",
                            'subtitle' => ($ct->penanganan ?: 'Penanganan gangguan selesai') . ' • No: ' . ($ct->nomor_internet ?: '-'),
                            'message' => 'Tiket gangguan telah diselesaikan oleh teknisi/NOC.',
                            'olt_kode' => null,
                            'interface_name' => null,
                            'nomor_internet' => $ct->nomor_internet,
                            'created_at' => $tgl ? Carbon::parse($tgl)->format('d M Y, H:i') : '-',
                            'created_at_raw' => $tgl ?: now()->toDateTimeString(),
                            'duration_human' => $tgl ? Carbon::parse($tgl)->locale('id')->diffForHumans() : 'Selesai',
                            'url' => route('teknik.tiket.gangguan', ['kategori' => 'gangguan']),
                            'is_recovery' => true,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 4.b Aktivasi Baru Selesai (status_reg = 20)
        try {
            if (Schema::hasTable('trx_batchjob_register')) {
                $recentAktif = DB::table('trx_batchjob_register')
                    ->where('status_reg', '20')
                    ->whereNotNull('date_update')
                    ->orderBy('date_update', 'desc')
                    ->limit(4)
                    ->get(['nomor_internet', 'nama_pelanggan', 'olt', 'index_olt', 'date_update']);

                foreach ($recentAktif as $ra) {
                    $already = false;
                    foreach ($recentRecoveries as $rc) {
                        if (($rc['device_key'] ?? '') === 'aktivasi_' . $ra->nomor_internet) {
                            $already = true;
                            break;
                        }
                    }
                    if (!$already) {
                        $recentRecoveries[] = [
                            'id' => 'selesai_aktif_' . $ra->nomor_internet,
                            'device_key' => 'aktivasi_' . $ra->nomor_internet,
                            'device_type' => 'tiket',
                            'status' => 'SELESAI',
                            'severity' => 'success',
                            'badge_color' => 'bg-emerald-500 text-white',
                            'icon_type' => 'check',
                            'title' => 'Aktivasi Baru Selesai',
                            'user_name' => $ra->nama_pelanggan ?: $ra->nomor_internet,
                            'device_name' => "Aktivasi #{$ra->nomor_internet} - {$ra->nama_pelanggan}",
                            'subtitle' => "Layanan aktif online" . ($ra->olt ? " • OLT {$ra->olt}" : '') . ($ra->index_olt ? " • {$ra->index_olt}" : ''),
                            'message' => "Pelanggan baru {$ra->nama_pelanggan} telah aktif dan terkoneksi ke OLT.",
                            'olt_kode' => $ra->olt,
                            'interface_name' => $ra->index_olt,
                            'nomor_internet' => $ra->nomor_internet,
                            'created_at' => Carbon::parse($ra->date_update)->format('d M Y, H:i'),
                            'created_at_raw' => $ra->date_update,
                            'duration_human' => Carbon::parse($ra->date_update)->locale('id')->diffForHumans(),
                            'url' => route('teknik.pelanggan.profile', ['nomor_internet' => $ra->nomor_internet]),
                            'is_recovery' => true,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {}

        // Urutkan riwayat pemulihan (recoveries) paling baru di atas
        usort($recentRecoveries, function ($a, $b) {
            return strcmp($b['created_at_raw'] ?? '', $a['created_at_raw'] ?? '');
        });
        $recentRecoveries = array_values(array_slice($recentRecoveries, 0, 15));

        // Segarkan duration_human untuk seluruh riwayat pemulihan secara real-time dari created_at_raw
        $now = now();
        foreach ($recentRecoveries as &$rc) {
            if (!empty($rc['created_at_raw'])) {
                try {
                    $c = Carbon::parse($rc['created_at_raw']);
                    $diffSec = $now->diffInSeconds($c, false);
                    if ($diffSec > 0) {
                        // Jika timestamp sedikit di masa depan (beda clock), set 'Baru saja'
                        $rc['duration_human'] = 'Baru saja';
                    } else {
                        $diffSec = abs($diffSec);
                        if ($diffSec < 45) {
                            $rc['duration_human'] = 'Baru saja';
                        } else {
                            $rc['duration_human'] = $c->locale('id')->diffForHumans();
                        }
                    }
                } catch (\Throwable $e) {
                    $rc['duration_human'] = 'Baru saja';
                }
            }
        }
        unset($rc);

        Cache::put('noc_recent_recoveries', $recentRecoveries, 86400 * 2);

        // Urutkan active alerts berdasarkan waktu mulai gangguan terbaru (insiden paling baru muncul di paling atas, baik LOS maupun DOWN)
        usort($activeAlerts, function ($a, $b) {
            return strcmp($b['created_at_raw'] ?? '', $a['created_at_raw'] ?? '');
        });

        // Segarkan duration_human untuk seluruh alert aktif secara real-time
        foreach ($activeAlerts as &$al) {
            if (!empty($al['created_at_raw'])) {
                try {
                    $c = Carbon::parse($al['created_at_raw']);
                    $diffSec = $now->diffInSeconds($c, false);
                    if ($diffSec > 0) {
                        $al['duration_human'] = 'Baru saja';
                    } else {
                        $diffSec = abs($diffSec);
                        if ($diffSec < 45) {
                            $al['duration_human'] = 'Baru saja';
                        } else {
                            $al['duration_human'] = $c->locale('id')->diffForHumans();
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }
        unset($al);

        $totalActive = count($activeAlerts);
        $totalLos = count(array_filter($activeAlerts, fn($a) => $a['status'] === 'LOS'));
        $totalDown = count(array_filter($activeAlerts, fn($a) => $a['status'] === 'DOWN'));
        $totalTickets = count(array_filter($activeAlerts, fn($a) => $a['device_type'] === 'tiket'));
        $totalIntentional = count($intentionalAlerts);

        $totalGangguan = count(array_filter($activeAlerts, fn($a) => ($a['ticket_category'] ?? '') === 'gangguan'));
        $totalUbahLayanan = count(array_filter($activeAlerts, fn($a) => ($a['ticket_category'] ?? '') === 'ubah_layanan'));
        $totalUbahPassword = count(array_filter($activeAlerts, fn($a) => ($a['ticket_category'] ?? '') === 'ubah_password'));
        $totalRelokasi = count(array_filter($activeAlerts, fn($a) => ($a['ticket_category'] ?? '') === 'relokasi'));
        $totalAktivasi = count(array_filter($activeAlerts, fn($a) => ($a['ticket_category'] ?? '') === 'aktivasi_baru'));

        $totalRecoveries = count($recentRecoveries);
        $totalUp = count(array_filter($recentRecoveries, fn($r) => ($r['status'] ?? '') === 'UP'));
        $totalSelesai = count(array_filter($recentRecoveries, fn($r) => ($r['status'] ?? '') === 'SELESAI'));

        return [
            'alerts' => $activeAlerts,
            'recoveries' => $recentRecoveries,
            'intentional' => $intentionalAlerts,
            'summary' => [
                'total_active' => $totalActive,
                'total_los' => $totalLos,
                'total_down' => $totalDown,
                'total_los_down' => $totalLos + $totalDown,
                'total_tickets' => $totalTickets,
                'total_gangguan' => $totalGangguan,
                'total_ubah_layanan' => $totalUbahLayanan,
                'total_ubah_password' => $totalUbahPassword,
                'total_relokasi' => $totalRelokasi,
                'total_aktivasi' => $totalAktivasi,
                'total_intentional' => $totalIntentional,
                'total_recoveries' => $totalRecoveries,
                'total_up' => $totalUp,
                'total_selesai' => $totalSelesai,
            ],
            'updated_at' => now()->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
            'timestamp' => now()->timestamp,
        ];
    }
}
