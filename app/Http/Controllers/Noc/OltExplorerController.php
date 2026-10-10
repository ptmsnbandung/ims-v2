<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // 1. Ambil List Master OLT dari database gomsn (di-cache 10 menit)
        $olts = Cache::remember('olt_explorer_master_olts', 600, function () {
            try {
                if (Schema::hasTable('gomsn.olt')) {
                    return DB::table('gomsn.olt')->orderBy('olt_id', 'asc')->get();
                }
            } catch (\Throwable $e) {}
            return collect();
        });

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
                $pons = DB::table($ponTable)->orderBy('id', 'asc')->get();
            } catch (\Throwable $e) {
                $pons = collect();
            }

            try {
                $odps = DB::table($odpTable)->orderBy('nama_odp', 'asc')->get();
            } catch (\Throwable $e) {
                $odps = collect();
            }

            try {
                $usersQuery = DB::table($userTable);
                if ($search !== '') {
                    $usersQuery->where(function ($q) use ($search) {
                        $q->where('nama_user', 'like', "%{$search}%")
                          ->orWhere('nomor_internet', 'like', "%{$search}%")
                          ->orWhere('keterangan', 'like', "%{$search}%");
                    });
                }
                $rawUsers = $usersQuery->orderBy('nama_user', 'asc')->get();

                // Enrich users with billing & customer data (Optimized with lean columns & cache)
                $nomorInternets = $rawUsers->pluck('nomor_internet')->filter()->unique()->toArray();
                $billingsMap = [];
                if (!empty($nomorInternets)) {
                    $cacheKeyBill = "olt_speed_map_{$selectedOltId}_" . md5(implode(',', array_slice($nomorInternets, 0, 50)));
                    $billingsMap = Cache::remember($cacheKeyBill, 300, function () use ($nomorInternets) {
                        try {
                            return DB::table('trx_billing_layanan')
                                ->select('nomor_internet', 'nominal_bandwith')
                                ->whereIn('nomor_internet', $nomorInternets)
                                ->where('tahun_tagihan', '>=', date('Y') - 1)
                                ->orderBy('tahun_tagihan', 'desc')
                                ->orderBy('bulan_tagihan', 'desc')
                                ->get()
                                ->unique('nomor_internet')
                                ->pluck('nominal_bandwith', 'nomor_internet')
                                ->toArray();
                        } catch (\Throwable $e) {
                            return [];
                        }
                    });
                }

                $userNames = $rawUsers->pluck('nama_user')->filter()->unique()->toArray();
                $pelanggansMap = [];
                if (!empty($userNames)) {
                    $cacheKeyPel = "olt_pelanggan_map_{$selectedOltId}_" . md5(implode(',', array_slice($userNames, 0, 50)));
                    $pelanggansMap = Cache::remember($cacheKeyPel, 300, function () use ($userNames) {
                        try {
                            return DB::table('m_pelanggan')
                                ->select('nama_penduduk', 'alamat_ktp', 'nomor_hp', 'nomor_hp_2')
                                ->whereIn('nama_penduduk', $userNames)
                                ->get()
                                ->keyBy(fn($p) => strtoupper(trim($p->nama_penduduk)))
                                ->map(fn($p) => [
                                    'alamat' => !empty($p->alamat_ktp) ? strtoupper(trim($p->alamat_ktp)) : '-',
                                    'hp' => $p->nomor_hp ?: ($p->nomor_hp_2 ?: '-')
                                ])
                                ->toArray();
                        } catch (\Throwable $e) {
                            return [];
                        }
                    });
                }

                $oltLabel = isset($currentOlt->nama_olt) ? strtoupper($currentOlt->nama_olt) : 'OLT';

                $users = $rawUsers->map(function ($u) use ($billingsMap, $pelanggansMap, $oltLabel) {
                    $speedVal = $billingsMap[$u->nomor_internet] ?? null;
                    $pel = $pelanggansMap[strtoupper(trim($u->nama_user))] ?? null;

                    $u->layanan = 'UP TO NEW';
                    $u->alamat = $pel['alamat'] ?? '-';
                    $u->nomor_hp = $pel['hp'] ?? '-';
                    $u->speed = !empty($speedVal) ? "{$speedVal} Mbps" : ($speedVal ? '35 Mbps' : '-');
                    
                    $userOlt = !empty($u->olt) ? strtoupper($u->olt) : $oltLabel;
                    $u->note = "RTEGC6B4B766 (OLT {$userOlt})";
                    $u->status = 'AKTIF';

                    return $u;
                });
            } catch (\Throwable $e) {
                $users = collect();
            }

            // Hitung kapasitas dan pengelompokan
            $usersByOdp = $users->groupBy('odp_id');

            // Attach user count dan utilisasi ke masing-masing ODP (Tanpa menduplikasi seluruh objek users ke dalam ODP)
            $odps = $odps->map(function ($odp) use ($usersByOdp, &$totalCapacityPort, &$totalUsedPort) {
                $userCount = $usersByOdp->get($odp->id, collect())->count();
                $portMax = (int) ($odp->port_max ?? 8);
                if ($portMax <= 0) $portMax = 8;

                $totalCapacityPort += $portMax;
                $totalUsedPort += $userCount;

                $odp->user_count = $userCount;
                $odp->utilization_percent = min(100, round(($userCount / $portMax) * 100));
                $odp->is_full = $userCount >= $portMax;
                $odp->available_ports = max(0, $portMax - $userCount);

                return $odp;
            });

            // Group ODP berdasarkan PON
            $odpsByPon = $odps->groupBy('pon_id');

            // Attach data ringkasan ke masing-masing PON (Tanpa menduplikasi seluruh objek ODP ke dalam PON)
            $pons = $pons->map(function ($pon) use ($odpsByPon) {
                $ponOdps = $odpsByPon->get($pon->id, collect());
                $ponUsersCount = $ponOdps->sum('user_count');
                $ponCapacity = $ponOdps->sum('port_max');

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
