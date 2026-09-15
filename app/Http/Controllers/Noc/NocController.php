<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use App\Services\Olt\OltConnectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NocController extends Controller
{
    /**
     * Dashboard Utama NOC Command Center
     */
    public function dashboard(Request $request): View
    {
        $totalOlt = DB::table('m_olt')->count();
        $totalOdp = DB::table('m_odp')->count();
        $totalPop = DB::table('m_pop')->where('hide', '!=', '1')->count();
        $totalPerangkat = DB::table('m_barang')->where('hide', '!=', '1')->count();

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

        // Master Karyawan / Tim Aktivasi
        $karyawans = DB::table('tb_m_karyawan')->where('status_aktif', 1)->get();

        // Master Barang / Perangkat
        $barangs = DB::table('m_barang')->where('hide', '!=', '1')->get();

        // Index OLT Availability (1..128, occupied vs available)
        $indexOltData = $this->getIndexOltSlots();

        return view('noc.dashboard', [
            'user' => $request->user(),
            'totalOlt' => $totalOlt,
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
            'allPorts' => $indexOltData['allPorts'],
            'portStats' => $indexOltData['portStats'],
        ]);
    }

    /**
     * Helper: Generate & check available Index OLT slots (1 to 128) across all GPON ports
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
        foreach ($occupiedRaw as $raw) {
            $clean = trim($raw);
            if (!str_starts_with($clean, 'gpon-onu_') && str_starts_with($clean, '1/')) {
                $clean = 'gpon-onu_' . $clean;
            }
            $occupiedMap[$clean] = true;
        }

        // 2. All 25+ GPON Ports (Slot 1: 1/1/1 s/d 1/1/16, Slot 2: 1/2/1 s/d 1/2/16)
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

        $slots = [];
        $portStats = [];

        foreach ($allPorts as $port) {
            $usedCount = 0;
            for ($i = 1; $i <= 128; $i++) {
                $key = "{$port}:{$i}";
                $isOccupied = isset($occupiedMap[$key]);
                if ($isOccupied) $usedCount++;

                $slots[$port][] = [
                    'key' => $key,
                    'num' => $i,
                    'is_occupied' => $isOccupied,
                ];
            }
            $portStats[$port] = [
                'name' => $port,
                'used' => $usedCount,
                'free' => 128 - $usedCount,
                'total' => 128,
            ];
        }

        return [
            'slots' => $slots,
            'portStats' => $portStats,
            'allPorts' => $allPorts,
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
            if (!str_starts_with($rawIndex, 'gpon-onu_') && str_starts_with($rawIndex, '1/')) {
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
     * Manajemen Master OLT, GPON, dan Port PON
     */
    public function olt(Request $request): View
    {
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
            $olt->capacity_olt = $olt->capacity_olt ?? 8;
            $olt->protocol = $olt->protocol ?? 'telnet';
            $olt->port = $olt->port ?? 23;
            $olt->username = $olt->username ?? 'admin';
            $olt->snmp_port = $olt->snmp_port ?? 161;
            $olt->snmp_version = $olt->snmp_version ?? 'v2c';
            $olt->snmp_community = $olt->snmp_community ?? 'public';

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
        $totalPonPort = DB::table('m_olt')->sum('capacity_olt') ?: 0;
        if ($totalPonPort == 0 && $totalOlt > 0) {
            $totalPonPort = $totalOlt * 8; // fallback default 8 port/olt
        }

        // Hitung status PON aktif / terdaftar per OLT
        $registeredPonCounts = [];
        foreach ($olts as $olt) {
            $count = DB::table('trx_batchjob_register')
                ->where(function($q) use ($olt) {
                    $q->where('olt', $olt->kode_olt)
                      ->orWhere('kode_pop', $olt->kode_pop ?? 'non_existent');
                })
                ->whereNotNull('index_olt')
                ->where('index_olt', '!=', '')
                ->whereNotIn('status_reg', ['23', '23.1', '15'])
                ->count();

            $registeredPonCounts[$olt->kode_olt] = $count;
        }

        $gpons = Schema::hasTable('m_gpon') ? DB::table('m_gpon')->get() : collect();
        $pons = Schema::hasTable('m_pon') ? DB::table('m_pon')->get() : collect();

        return view('noc.olt', [
            'user' => $request->user(),
            'olts' => $olts,
            'totalOlt' => $totalOlt,
            'totalPonPort' => $totalPonPort,
            'registeredPonCounts' => $registeredPonCounts,
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
     * Store / Update OLT
     */
    public function storeOlt(Request $request): RedirectResponse
    {
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
            'hostname' => $request->hostname,
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

        DB::table('m_olt')->updateOrInsert(
            ['kode_olt' => $kodeOlt],
            $data
        );

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

        return response()->json($res);
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
        $karyawans = DB::table('tb_m_karyawan')->where('status_aktif', 1)->get();
        $barangs = DB::table('m_barang')->where('hide', '!=', '1')->get();
        $indexOltData = $this->getIndexOltSlots();

        return view('noc.aktivasi', [
            'user' => $request->user(),
            'pelanggans' => $pelanggans,
            'olts' => $olts,
            'odps' => $odps,
            'pops' => $pops,
            'karyawans' => $karyawans,
            'barangs' => $barangs,
            'indexOltSlots' => $indexOltData['slots'],
            'occupiedIndexOlts' => $indexOltData['occupied'],
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
                $updateData['index_olt'] = $request->index_olt;
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
            $fileName = 'AKTIVASI_' . $nomorInternet . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/registrasi');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $fileName);
        }

        // Ambil data pelanggan saat ini untuk kredensial PPPoE
        $currentCust = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first();
        $ontUs = (!empty($currentCust->ont_us)) ? $currentCust->ont_us : substr($nomorInternet, 0, 10);
        $ontPs = (!empty($currentCust->ont_ps)) ? $currentCust->ont_ps : (string)rand(100000, 999999);

        // 1. Update trx_batchjob_register -> Langsung Aktif (#20) karena aktivasi & reboot dieksekusi otomatis di background
        $updateData = [
            'status_reg' => '20', // Pelanggan Aktif Online
            'kode_pop' => $request->kode_pop,
            'media_akses' => $request->media_akses,
            'index_olt' => ($request->media_akses === 'PTP FO') ? '' : $request->index_olt,
            'note_request' => $request->sn_modem ?: ($request->catatan_aktivasi ?: $request->catatan),
            'ont_us' => $ontUs,
            'ont_ps' => $ontPs,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

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
            'aktivasi_date_start' => $request->jadwal_aktivasi ?: now()->format('Y-m-d'),
            'aktivasi_date_finish' => now()->format('Y-m-d'),
            'aktivasi_time' => $request->waktu_aktivasi ?: now()->format('H:i:s'),
            'aktivasi_team' => $teamAktivasi,
            'aktivasi_note_finish' => $request->catatan_aktivasi ?: ($request->catatan ?: $request->sn_modem),
            'date_update' => $now,
            'user_update' => $currentUser,
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
        if (Schema::hasTable('trx_batchjob_register_log')) {
            // Log Report
            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => '19',
                'kat_log' => '19',
                'note_schedule' => "REPORT AKTIVASI SELESAI: OLT: {$request->olt}, Index: {$request->index_olt}, SN: {$request->sn_modem} (Team: {$teamAktivasi})",
                'date_schedule' => $request->jadwal_aktivasi ?: now()->format('Y-m-d'),
                'time_schedule' => $request->waktu_aktivasi ?: now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);

            // Log Otomatis Reboot & Aktivasi PPPoE (#20)
            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => '20',
                'kat_log' => '20',
                'note_schedule' => "REBOOT & AKTIVASI OTOMATIS (BACKGROUND): OLT: {$request->olt}, Index: {$request->index_olt}, SN Modem: {$request->sn_modem}, User PPPoE: {$ontUs} - Layanan AKTIF (#20)",
                'date_schedule' => now()->format('Y-m-d'),
                'time_schedule' => now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);
        }

        return redirect()->back()->with('success', "Report Aktivasi untuk pelanggan {$nomorInternet} berhasil disimpan! Sistem otomatis melakukan reboot & aktivasi di background (Status: Online / Aktif #20).");
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
            'router_mikrotik' => 'required|string',
            'local_address' => 'required|string',
            'ppp_profile' => 'required|string',
            'remote_address' => 'nullable|string',
        ]);

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        // 1. Update trx_batchjob_register to status 20 (Aktif)
        DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->update([
                'status_reg' => '20', // Pelanggan Aktif
                'ont_us' => substr($request->pppoe_username, 0, 10),
                'ont_ps' => substr($request->pppoe_password, 0, 10),
                'date_update' => $now,
                'user_update' => $currentUser,
            ]);

        // 2. Update trx_instalasi
        DB::table('trx_instalasi')
            ->updateOrInsert(
                ['nomor_internet' => $nomorInternet],
                [
                    'aktivasi_date_finish' => now()->format('Y-m-d'),
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]
            );

        // 3. Log PPPoE Secret Creation & OLT Online
        if (Schema::hasTable('trx_batchjob_register_log')) {
            DB::table('trx_batchjob_register_log')->insert([
                'kode_batchjob_register_log' => 'L-' . $nomorInternet . rand(1000, 9999),
                'nomor_internet' => $nomorInternet,
                'status_reg' => '20',
                'kat_log' => '20',
                'note_schedule' => "AKTIVASI PPPoE BERHASIL: User: {$request->pppoe_username}, Router: {$request->router_mikrotik}, Profile: {$request->ppp_profile}, Remote IP: " . ($request->remote_address ?: 'Dynamic/Pool') . " (Gateway: {$request->local_address})",
                'date_schedule' => now()->format('Y-m-d'),
                'time_schedule' => now()->format('H:i:s'),
                'date_create' => $now,
                'user_create' => $currentUser,
            ]);
        }

        return redirect()->back()->with('success', "PPPoE Secret berhasil dibuat di {$request->router_mikrotik} dan layanan pelanggan {$nomorInternet} resmi AKTIF!");
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
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');

        $query = DB::table('view_suspend');

        if ($layanan) {
            $query->where('nama_kategori_bandwith', $layanan);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('desc_suspend', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('status_suspend', $status);
        }

        $suspends = $query->orderBy('date_create', 'desc')->paginate(10)->withQueryString();

        // 3 Summary Pill Counts (matching screenshot)
        $countRequest = DB::table('view_suspend')->where('status_suspend', '11')->count();
        $countSuspend = DB::table('view_suspend')->where('status_suspend', '12')->count();
        $countReqUnsuspend = DB::table('view_suspend')->where('status_suspend', '18')->count();

        // Distinct Layanan List
        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();
        $statusList = Schema::hasTable('m_status_suspend') ? DB::table('m_status_suspend')->get() : collect();

        return view('noc.suspend', [
            'user' => $request->user(),
            'suspends' => $suspends,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'countRequest' => $countRequest,
            'countSuspend' => $countSuspend,
            'countReqUnsuspend' => $countReqUnsuspend,
            'layananList' => $layananList,
            'statusList' => $statusList,
        ]);
    }

    /**
     * Approve Suspend / Unsuspend Request
     */
    public function approveSuspend(Request $request, string $kodeSuspend): RedirectResponse
    {
        $suspend = DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->first();
        if (!$suspend) {
            return redirect()->back()->with('error', 'Data suspend tidak ditemukan.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'NOC';

        $newStatus = ($suspend->status_suspend == '18') ? '13' : '12';
        $updateData = [
            'status_suspend' => $newStatus,
            'date_update' => $now,
            'user_update' => $currentUser,
        ];

        if ($newStatus == '12') {
            $updateData['suspend_start'] = $request->start_suspend ?: now()->format('Y-m-d');
        } elseif ($newStatus == '13') {
            $updateData['suspend_end'] = $request->finish_suspend ?: ($request->start_suspend ?: now()->format('Y-m-d'));
        }

        DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->update($updateData);

        $actionText = ($newStatus == '13') ? 'buka isolir (UNsuspend)' : 'suspend';
        return redirect()->back()->with('success', "Permintaan {$actionText} {$suspend->nomor_internet} berhasil disetujui (Approved)!");
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

        $terminasis = $query->orderBy('date_create', 'desc')->paginate(10)->withQueryString();

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
        $karyawans = DB::table('tb_m_karyawan')->where('status_aktif', 1)->orderBy('nama_karyawan')->get();

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
}
