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

        // 1. Ambil List Master OLT langsung dari database (query sangat cepat & bebas isu cache serialization)
        Cache::forget('olt_explorer_master_olts');
        $olts = collect();
        try {
            if (Schema::hasTable('gomsn.olt')) {
                $olts = DB::table('gomsn.olt')->orderBy('olt_id', 'asc')->get();
            }
        } catch (\Throwable $e) {
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

    /**
     * Halaman Pencarian Terpadu OLT, PON, ODP, dan User
     * Mencari berdasarkan nama ODP, nama user, atau nomor internet di seluruh OLT
     */
    public function search(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $searchType = $request->input('type', 'all'); // 'all', 'odp', 'user', 'internet'
        $filterOlt = $request->input('olt_id', '');

        // 1. Ambil list master OLT
        $olts = collect();
        try {
            if (Schema::hasTable('gomsn.olt')) {
                $olts = DB::table('gomsn.olt')->orderBy('olt_id', 'asc')->get();
            }
        } catch (\Throwable $e) {
            $olts = collect();
        }

        // Tentukan OLT target pencarian
        $targetOlts = $olts;
        if (!empty($filterOlt)) {
            $targetOlts = $olts->filter(fn($o) => (string)$o->olt_id === (string)$filterOlt);
        }

        $results = collect();
        $matchedOdps = collect();

        // 2. Jika ada query pencarian, eksekusi query gabungan ke seluruh tabel OLT aktif
        if ($q !== '') {
            $searchPattern = "%{$q}%";

            foreach ($targetOlts as $olt) {
                $userTable = "gomsn.users{$olt->olt_id}";
                $odpTable = "gomsn.odp{$olt->olt_id}";
                $ponTable = "gomsn.pon{$olt->olt_id}";

                try {
                    $query = DB::table("{$userTable} as u")
                        ->leftJoin("{$odpTable} as odp", 'u.odp_id', '=', 'odp.id')
                        ->leftJoin("{$ponTable} as pon", 'odp.pon_id', '=', 'pon.id')
                        ->select([
                            'u.id as user_id',
                            'u.nama_user',
                            'u.nomor_internet',
                            'u.keterangan',
                            'u.olt as user_olt_tag',
                            'odp.id as odp_id',
                            'odp.nama_odp',
                            'odp.port_max as odp_port_max',
                            'odp.latitude as odp_lat',
                            'odp.longitude as odp_lng',
                            'pon.id as pon_id',
                            'pon.nama_pon',
                            DB::raw("{$olt->olt_id} as olt_id"),
                            DB::raw("'" . addslashes($olt->nama_olt) . "' as olt_nama")
                        ]);

                    if ($searchType === 'odp') {
                        $query->where(function ($sub) use ($searchPattern) {
                            $sub->where('odp.nama_odp', 'like', $searchPattern)
                                ->orWhere('pon.nama_pon', 'like', $searchPattern);
                        });
                    } elseif ($searchType === 'user') {
                        $query->where('u.nama_user', 'like', $searchPattern);
                    } elseif ($searchType === 'internet') {
                        $query->where('u.nomor_internet', 'like', $searchPattern);
                    } else {
                        $query->where(function ($sub) use ($searchPattern) {
                            $sub->where('u.nama_user', 'like', $searchPattern)
                                ->orWhere('u.nomor_internet', 'like', $searchPattern)
                                ->orWhere('odp.nama_odp', 'like', $searchPattern)
                                ->orWhere('pon.nama_pon', 'like', $searchPattern)
                                ->orWhere('u.keterangan', 'like', $searchPattern);
                        });
                    }

                    $rows = $query->limit(200)->get();
                    $results = $results->concat($rows);

                    // Ambil ODP yang cocok untuk ODP matching overview
                    if (in_array($searchType, ['all', 'odp'])) {
                        $odpRows = DB::table("{$odpTable} as odp")
                            ->leftJoin("{$ponTable} as pon", 'odp.pon_id', '=', 'pon.id')
                            ->select([
                                'odp.id as odp_id',
                                'odp.nama_odp',
                                'odp.port_max',
                                'odp.latitude',
                                'odp.longitude',
                                'pon.id as pon_id',
                                'pon.nama_pon',
                                DB::raw("{$olt->olt_id} as olt_id"),
                                DB::raw("'" . addslashes($olt->nama_olt) . "' as olt_nama")
                            ])
                            ->where(function ($sub) use ($searchPattern) {
                                $sub->where('odp.nama_odp', 'like', $searchPattern)
                                    ->orWhere('pon.nama_pon', 'like', $searchPattern);
                            })
                            ->limit(30)
                            ->get();
                        $matchedOdps = $matchedOdps->concat($odpRows);
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // 3. Enrich data user dengan nomor HP, alamat, dan bandwidth speed
            if ($results->isNotEmpty()) {
                $nomorInternets = $results->pluck('nomor_internet')->filter()->unique()->toArray();
                $userNames = $results->pluck('nama_user')->filter()->unique()->toArray();

                // 3a. Ambil data dari view_batchjob (sumber data utama profil pelanggan & no hp aktif)
                $batchMap = [];
                if (!empty($nomorInternets)) {
                    try {
                        if (Schema::hasTable('view_batchjob')) {
                            $batchMap = DB::table('view_batchjob')
                                ->select('nomor_internet', 'nama_pelanggan', 'nomor_hp', 'nomor_hp_2', 'alamat_p', 'nominal_bandwith')
                                ->whereIn('nomor_internet', $nomorInternets)
                                ->get()
                                ->keyBy('nomor_internet')
                                ->toArray();
                        }
                    } catch (\Throwable $e) {}
                }

                // 3b. Fallback ke m_pelanggan berdasarkan nama penduduk jika nomor internet belum terdata di batchjob
                $pelanggansMap = [];
                if (!empty($userNames)) {
                    try {
                        if (Schema::hasTable('m_pelanggan')) {
                            $pelanggansMap = DB::table('m_pelanggan')
                                ->select('nama_penduduk', 'alamat_ktp', 'nomor_hp', 'nomor_hp_2')
                                ->whereIn('nama_penduduk', $userNames)
                                ->get()
                                ->keyBy(fn($p) => strtoupper(trim($p->nama_penduduk)))
                                ->toArray();
                        }
                    } catch (\Throwable $e) {}
                }

                // 3c. Speed bandwidth dari trx_billing_layanan jika di batchjob belum ada
                $billingsMap = [];
                if (!empty($nomorInternets)) {
                    try {
                        if (Schema::hasTable('trx_billing_layanan')) {
                            $billingsMap = DB::table('trx_billing_layanan')
                                ->select('nomor_internet', 'nominal_bandwith')
                                ->whereIn('nomor_internet', $nomorInternets)
                                ->where('tahun_tagihan', '>=', date('Y') - 1)
                                ->orderBy('tahun_tagihan', 'desc')
                                ->orderBy('bulan_tagihan', 'desc')
                                ->get()
                                ->unique('nomor_internet')
                                ->pluck('nominal_bandwith', 'nomor_internet')
                                ->toArray();
                        }
                    } catch (\Throwable $e) {}
                }

                $results = $results->map(function ($item) use ($batchMap, $pelanggansMap, $billingsMap) {
                    $uName = strtoupper(trim($item->nama_user ?? ''));
                    $bInfo = $batchMap[$item->nomor_internet] ?? null;
                    $pInfo = $pelanggansMap[$uName] ?? null;

                    // Ambil no hp dari batchjob atau m_pelanggan
                    $hp = null;
                    if ($bInfo && !empty($bInfo->nomor_hp)) {
                        $hp = trim($bInfo->nomor_hp);
                    } elseif ($bInfo && !empty($bInfo->nomor_hp_2)) {
                        $hp = trim($bInfo->nomor_hp_2);
                    } elseif ($pInfo && !empty($pInfo->nomor_hp)) {
                        $hp = trim($pInfo->nomor_hp);
                    } elseif ($pInfo && !empty($pInfo->nomor_hp_2)) {
                        $hp = trim($pInfo->nomor_hp_2);
                    }

                    // Ambil alamat dari batchjob (alamat pasang) atau m_pelanggan (alamat ktp)
                    $alamat = null;
                    if ($bInfo && !empty($bInfo->alamat_p)) {
                        $alamat = trim($bInfo->alamat_p);
                    } elseif ($pInfo && !empty($pInfo->alamat_ktp)) {
                        $alamat = trim($pInfo->alamat_ktp);
                    }

                    // Speed
                    $speedVal = $bInfo->nominal_bandwith ?? ($billingsMap[$item->nomor_internet] ?? null);

                    $item->alamat = !empty($alamat) ? $alamat : '-';
                    $item->nomor_hp = !empty($hp) ? $hp : '-';
                    $item->speed = !empty($speedVal) ? "{$speedVal} Mbps" : '-';

                    return $item;
                });
            }
        }

        // 4. Ringkasan statistik cepat
        $stats = Cache::remember('olt_search_global_stats', 300, function () use ($olts) {
            $totalUser = 0;
            $totalOdp = 0;
            $totalPon = 0;

            foreach ($olts as $o) {
                if (Schema::hasTable("gomsn.users{$o->olt_id}")) {
                    $totalUser += DB::table("gomsn.users{$o->olt_id}")->count();
                }
                if (Schema::hasTable("gomsn.odp{$o->olt_id}")) {
                    $totalOdp += DB::table("gomsn.odp{$o->olt_id}")->count();
                }
                if (Schema::hasTable("gomsn.pon{$o->olt_id}")) {
                    $totalPon += DB::table("gomsn.pon{$o->olt_id}")->count();
                }
            }

            return [
                'total_olt' => $olts->count(),
                'total_pon' => $totalPon,
                'total_odp' => $totalOdp,
                'total_user' => $totalUser,
            ];
        });

        return view('noc.olt-search', [
            'user' => $request->user(),
            'olts' => $olts,
            'q' => $q,
            'searchType' => $searchType,
            'filterOlt' => $filterOlt,
            'results' => $results,
            'matchedOdps' => $matchedOdps,
            'stats' => $stats,
        ]);
    }
}
