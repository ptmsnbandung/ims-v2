<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OltExplorerController extends Controller
{
    /**
     * Menampilkan Halaman Utama Menu OLT
     * Berisi Dropdown Master OLT, Hierarki Data PON -> ODP -> Users / Pelanggan
     */
    public function index(Request $request, $olt_id = null): View
    {
        $search = trim((string) $request->input('search', ''));
        $selectedOltId = $olt_id ?: $request->input('olt_id');

        // 1. Ambil List Master OLT dari database gomsn
        $olts = collect();
        try {
            if (Schema::hasTable('gomsn.olt')) {
                $olts = DB::table('gomsn.olt')->orderBy('olt_id', 'asc')->get();
            }
        } catch (\Throwable $e) {
            // Fallback jika database gomsn tidak tersedia
            $olts = collect();
        }

        // Default ke OLT pertama jika tidak dipilih
        if (!$selectedOltId && $olts->isNotEmpty()) {
            $selectedOltId = (string) $olts->first()->olt_id;
        }

        $currentOlt = $olts->firstWhere('olt_id', $selectedOltId);

        $pons = collect();
        $odps = collect();
        $users = collect();
        $odpsByPon = collect();
        $usersByOdp = collect();

        $totalCapacityPort = 0;
        $totalUsedPort = 0;

        // 2. Ambil data PON, ODP, dan Users sesuai OLT yang dipilih
        if ($selectedOltId) {
            $ponTable = "gomsn.pon{$selectedOltId}";
            $odpTable = "gomsn.odp{$selectedOltId}";
            $userTable = "gomsn.users{$selectedOltId}";

            try {
                if (Schema::hasTable($ponTable)) {
                    $pons = DB::table($ponTable)->orderBy('id', 'asc')->get();
                }
            } catch (\Throwable $e) {}

            try {
                if (Schema::hasTable($odpTable)) {
                    $odps = DB::table($odpTable)->orderBy('nama_odp', 'asc')->get();
                }
            } catch (\Throwable $e) {}

            try {
                if (Schema::hasTable($userTable)) {
                    $usersQuery = DB::table($userTable);
                    if ($search !== '') {
                        $usersQuery->where(function ($q) use ($search) {
                            $q->where('nama_user', 'like', "%{$search}%")
                              ->orWhere('nomor_internet', 'like', "%{$search}%")
                              ->orWhere('keterangan', 'like', "%{$search}%");
                        });
                    }
                    $rawUsers = $usersQuery->orderBy('nama_user', 'asc')->get();

                    // Enrich users with billing & customer data
                    $nomorInternets = $rawUsers->pluck('nomor_internet')->filter()->unique()->toArray();
                    $billings = collect();
                    if (!empty($nomorInternets) && Schema::hasTable('trx_billing_layanan')) {
                        try {
                            $billings = DB::table('trx_billing_layanan')
                                ->whereIn('nomor_internet', $nomorInternets)
                                ->orderBy('date_create', 'desc')
                                ->get()
                                ->groupBy('nomor_internet')
                                ->map(fn($group) => $group->first());
                        } catch (\Throwable $e) {}
                    }

                    $userNames = $rawUsers->pluck('nama_user')->filter()->unique()->toArray();
                    $pelanggans = collect();
                    if (!empty($userNames) && Schema::hasTable('m_pelanggan')) {
                        try {
                            $pelanggans = DB::table('m_pelanggan')
                                ->whereIn('nama_penduduk', $userNames)
                                ->get()
                                ->keyBy(fn($p) => strtoupper(trim($p->nama_penduduk)));
                        } catch (\Throwable $e) {}
                    }

                    $users = $rawUsers->map(function ($u) use ($billings, $pelanggans, $currentOlt) {
                        $bill = $billings->get($u->nomor_internet);
                        $pel = $pelanggans->get(strtoupper(trim($u->nama_user)));

                        $u->layanan = $bill ? 'UP TO NEW' : 'UP TO NEW';
                        $u->alamat = $pel && !empty($pel->alamat_ktp) ? strtoupper(trim($pel->alamat_ktp)) : '-';
                        $u->nomor_hp = $pel ? ($pel->nomor_hp ?: ($pel->nomor_hp_2 ?: '-')) : '-';
                        $u->speed = $bill && !empty($bill->nominal_bandwith) ? "{$bill->nominal_bandwith} Mbps" : ($bill ? '35 Mbps' : '-');
                        
                        $oltLabel = !empty($u->olt) ? strtoupper($u->olt) : (isset($currentOlt->nama_olt) ? strtoupper($currentOlt->nama_olt) : 'OLT');
                        $u->note = "RTEGC6B4B766 (OLT {$oltLabel})";
                        $u->status = 'AKTIF';

                        return $u;
                    });
                }
            } catch (\Throwable $e) {}

            // Hitung kapasitas dan pengelompokan
            $usersByOdp = $users->groupBy('odp_id');

            // Attach user count dan utilisasi ke masing-masing ODP
            $odps = $odps->map(function ($odp) use ($usersByOdp, &$totalCapacityPort, &$totalUsedPort) {
                $odpUsers = $usersByOdp->get($odp->id, collect());
                $userCount = $odpUsers->count();
                $portMax = (int) ($odp->port_max ?? 8);
                if ($portMax <= 0) $portMax = 8;

                $totalCapacityPort += $portMax;
                $totalUsedPort += $userCount;

                $odp->user_count = $userCount;
                $odp->users = $odpUsers;
                $odp->utilization_percent = min(100, round(($userCount / $portMax) * 100));
                $odp->is_full = $userCount >= $portMax;
                $odp->available_ports = max(0, $portMax - $userCount);

                return $odp;
            });

            // Group ODP berdasarkan PON
            $odpsByPon = $odps->groupBy('pon_id');

            // Attach data ke masing-masing PON
            $pons = $pons->map(function ($pon) use ($odpsByPon) {
                $ponOdps = $odpsByPon->get($pon->id, collect());
                $ponUsersCount = $ponOdps->sum('user_count');
                $ponCapacity = $ponOdps->sum('port_max');

                $pon->odps = $ponOdps;
                $pon->odp_count = $ponOdps->count();
                $pon->user_count = $ponUsersCount;
                $pon->total_capacity = $ponCapacity;
                $pon->utilization_percent = $ponCapacity > 0 ? min(100, round(($ponUsersCount / $ponCapacity) * 100)) : 0;

                return $pon;
            });
        }

        // Hitung statistik ringkasan
        $stats = [
            'total_pon' => $pons->count(),
            'total_odp' => $odps->count(),
            'total_users' => $users->count(),
            'total_capacity' => $totalCapacityPort,
            'total_used' => $totalUsedPort,
            'utilization_percent' => $totalCapacityPort > 0 ? min(100, round(($totalUsedPort / $totalCapacityPort) * 100)) : 0,
            'full_odp_count' => $odps->where('is_full', true)->count(),
        ];

        return view('noc.olt-explorer', [
            'user' => $request->user(),
            'olts' => $olts,
            'selectedOltId' => $selectedOltId,
            'currentOlt' => $currentOlt,
            'pons' => $pons,
            'odps' => $odps,
            'users' => $users,
            'stats' => $stats,
            'search' => $search,
        ]);
    }
}
