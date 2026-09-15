<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Dashboard / Halaman Utama Manajemen User & Hak Akses
     */
    public function users(Request $request): View
    {
        $search = trim($request->input('search', ''));
        $selectedRole = $request->input('role', 'all');
        $selectedStatus = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        // 1. KPI Stat Counters
        $totalUsers = DB::table('tb_pengguna')->count();
        $activeUsers = DB::table('tb_pengguna')->where('status_aktif', '1')->count();
        $inactiveUsers = DB::table('tb_pengguna')->where('status_aktif', '!=', '1')->count();
        $totalRoles = Schema::hasTable('tb_m_level_pengguna')
            ? DB::table('tb_m_level_pengguna')->count()
            : DB::table('tb_pengguna')->distinct('kode_level')->count();

        // 2. Base Query
        $query = DB::table('tb_pengguna as p')
            ->leftJoin('tb_m_karyawan as k', 'p.kode_karyawan', '=', 'k.kode_karyawan')
            ->leftJoin('tb_m_jabatan as j', 'k.kode_jabatan', '=', 'j.kode_jabatan')
            ->leftJoin('tb_m_level_pengguna as l', 'p.kode_level', '=', 'l.kode_level')
            ->select(
                'p.kode_pengguna',
                'p.kode_karyawan',
                'p.kode_level',
                'p.username',
                'p.status_aktif',
                'p.last_ip',
                'p.las_login',
                'p.date_create',
                'p.date_update',
                'k.nama_karyawan',
                'k.nip',
                'k.hp_karyawan',
                'k.kode_jabatan',
                'j.nama_jabatan',
                'l.nama_level',
                'l.level as level_num'
            );

        // 3. Search Filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.username', 'like', "%{$search}%")
                  ->orWhere('k.nama_karyawan', 'like', "%{$search}%")
                  ->orWhere('l.nama_level', 'like', "%{$search}%")
                  ->orWhere('j.nama_jabatan', 'like', "%{$search}%")
                  ->orWhere('p.kode_pengguna', 'like', "%{$search}%")
                  ->orWhere('k.kode_karyawan', 'like', "%{$search}%");
            });
        }

        // 4. Role Filter
        if ($selectedRole !== 'all' && !empty($selectedRole)) {
            $query->where(function ($q) use ($selectedRole) {
                $q->where('p.kode_level', $selectedRole)
                  ->orWhere('l.nama_level', $selectedRole);
            });
        }

        // 5. Status Filter
        if ($selectedStatus !== 'all' && !empty($selectedStatus)) {
            $query->where('p.status_aktif', $selectedStatus);
        }

        $users = $query->orderBy('p.status_aktif', 'asc')
            ->orderBy('l.level', 'asc')
            ->orderBy('p.username', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        // Attach fallback and formatting
        foreach ($users as $u) {
            $u->nama_karyawan = $u->nama_karyawan ?: ($u->username ?: $u->kode_pengguna);
            $u->nama_level = $u->nama_level ?: 'Pengguna';
            $u->nama_jabatan = $u->nama_jabatan ?: 'Staff';
        }

        // 6. Master Levels for Dropdown
        $levelList = Schema::hasTable('tb_m_level_pengguna')
            ? DB::table('tb_m_level_pengguna')->orderBy('level', 'asc')->get()
            : collect();

        // 7. Master Jabatan for Quick Pick / Dropdown
        $jabatanList = Schema::hasTable('tb_m_jabatan')
            ? DB::table('tb_m_jabatan')->orderBy('nama_jabatan', 'asc')->get()
            : collect();

        // 8. Master Karyawan for Quick Pick
        $karyawanList = Schema::hasTable('tb_m_karyawan')
            ? DB::table('tb_m_karyawan')->where('status_aktif', '1')->orderBy('nama_karyawan', 'asc')->get()
            : collect();

        return view('admin.users', [
            'user' => $request->user(),
            'users' => $users,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'totalRoles' => $totalRoles,
            'levelList' => $levelList,
            'jabatanList' => $jabatanList,
            'karyawanList' => $karyawanList,
            'search' => $search,
            'selectedRole' => $selectedRole,
            'selectedStatus' => $selectedStatus,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Tambah Pengguna Baru ke Sistem
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:150',
            'username' => 'required|string|max:100|unique:tb_pengguna,username',
            'kode_level' => 'required|string|max:50',
            'jabatan' => 'nullable|string|max:100',
            'status_aktif' => 'required|in:1,2',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|same:password',
        ]);

        $now = Carbon::now()->toDateTimeString();
        $currentUser = substr(Auth::user()?->username ?? 'admin', 0, 20);

        // Generate unique kode_pengguna & kode_karyawan
        $kodePengguna = 'pg' . rand(10000, 99999);
        while (DB::table('tb_pengguna')->where('kode_pengguna', $kodePengguna)->exists()) {
            $kodePengguna = 'pg' . rand(10000, 99999);
        }

        $kodeKaryawan = 'KR' . rand(10000, 99999);
        while (Schema::hasTable('tb_m_karyawan') && DB::table('tb_m_karyawan')->where('kode_karyawan', $kodeKaryawan)->exists()) {
            $kodeKaryawan = 'KR' . rand(10000, 99999);
        }

        DB::beginTransaction();
        try {
            // 1. Simpan ke tb_m_karyawan jika ada
            if (Schema::hasTable('tb_m_karyawan')) {
                $kodeJabatan = 'jabatan11644';
                if ($request->filled('jabatan')) {
                    $foundJabatan = DB::table('tb_m_jabatan')->where('nama_jabatan', 'like', '%' . trim($request->jabatan) . '%')->first();
                    if ($foundJabatan) {
                        $kodeJabatan = $foundJabatan->kode_jabatan;
                    }
                }

                DB::table('tb_m_karyawan')->insert([
                    'kode_karyawan' => $kodeKaryawan,
                    'nik' => '',
                    'nip' => '',
                    'nama_karyawan' => trim($request->nama_lengkap),
                    'cuti' => 12,
                    'kode_jabatan' => $kodeJabatan,
                    'jenis_kelamin' => '1',
                    'hp_karyawan' => '',
                    'kode_agama' => 'ag56e3fc23c84ee',
                    'email_karyawan' => '',
                    'email_msn' => trim($request->username),
                    'tempat_lahir' => '',
                    'tanggal_lahir' => null,
                    'tempat_pendidikan_terakhir' => '-',
                    'kode_pendidikan' => 'jp5902e923eceb8',
                    'jmlh_tanggungan' => '0',
                    'desc_tanggungan' => '-',
                    'kode_golongan_darah' => 'gd56e3fdd542aaa',
                    'kode_wilayah_kelurahan' => '32.73.13.1005',
                    'ktp' => '',
                    'foto' => '',
                    'cv' => '',
                    'ijazah_pendidikan_terakhir' => '',
                    'alamat_asal' => '',
                    'domisili' => '',
                    'kode_status_kawin' => 'kw56e3ff91bc3f0',
                    'npwp' => '',
                    'bpjs' => '',
                    'bank_rek' => '',
                    'no_rek' => '',
                    'tanggal_masuk' => date('Y-m-d'),
                    'tanggal_keluar' => null,
                    'status_aktif' => (string) $request->status_aktif,
                    'uid' => '',
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $now,
                    'tinggi' => '0',
                    'berat' => '0',
                    'status_kontrak' => 1,
                    'tanggal_kontrak_akhir' => null,
                    'kendaraan' => '',
                    'sim' => '',
                    'status_rumah' => '',
                    'kantor' => 'Bandung',
                    'kota_kerja' => '32.73'
                ]);
            }

            // 2. Simpan ke tb_pengguna (Hash MD5 & Bcrypt compatible)
            DB::table('tb_pengguna')->insert([
                'kode_pengguna' => $kodePengguna,
                'kode_karyawan' => $kodeKaryawan,
                'kode_level' => $request->kode_level,
                'username' => trim($request->username),
                'password' => md5($request->password),
                'status_aktif' => (string) $request->status_aktif,
                'date_create' => $now,
                'user_create' => $currentUser,
                'date_update' => $now,
                'user_update' => $currentUser,
            ]);

            DB::commit();

            return redirect()->route('admin.users')->with('success', "Pengguna baru '{$request->nama_lengkap}' ({$request->username}) berhasil ditambahkan!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', "Gagal menambahkan pengguna: " . $e->getMessage());
        }
    }

    /**
     * Update Data Pengguna
     */
    public function updateUser(Request $request, string $kode_pengguna): RedirectResponse
    {
        $user = DB::table('tb_pengguna')->where('kode_pengguna', $kode_pengguna)->first();
        if (!$user) {
            return redirect()->route('admin.users')->with('error', 'Data pengguna tidak ditemukan.');
        }

        $request->validate([
            'nama_lengkap' => 'required|string|max:150',
            'username' => 'required|string|max:100|unique:tb_pengguna,username,' . $kode_pengguna . ',kode_pengguna',
            'kode_level' => 'required|string|max:50',
            'jabatan' => 'nullable|string|max:100',
            'status_aktif' => 'required|in:1,2',
            'password' => 'nullable|string|min:6',
            'password_confirmation' => 'nullable|same:password',
        ]);

        $now = Carbon::now()->toDateTimeString();
        $currentUser = substr(Auth::user()?->username ?? 'admin', 0, 20);

        DB::beginTransaction();
        try {
            $userPayload = [
                'username' => trim($request->username),
                'kode_level' => $request->kode_level,
                'status_aktif' => (string) $request->status_aktif,
                'date_update' => $now,
                'user_update' => $currentUser,
            ];

            if ($request->filled('password')) {
                $userPayload['password'] = md5($request->password);
            }

            DB::table('tb_pengguna')->where('kode_pengguna', $kode_pengguna)->update($userPayload);

            // Update tb_m_karyawan jika ada
            if ($user->kode_karyawan && Schema::hasTable('tb_m_karyawan')) {
                $karyawanPayload = [
                    'nama_karyawan' => trim($request->nama_lengkap),
                    'status_aktif' => (string) $request->status_aktif,
                    'date_update' => $now,
                ];
                if ($request->filled('jabatan')) {
                    $foundJabatan = DB::table('tb_m_jabatan')->where('nama_jabatan', 'like', '%' . trim($request->jabatan) . '%')->first();
                    if ($foundJabatan) {
                        $karyawanPayload['kode_jabatan'] = $foundJabatan->kode_jabatan;
                    }
                }
                DB::table('tb_m_karyawan')->where('kode_karyawan', $user->kode_karyawan)->update($karyawanPayload);
            }

            DB::commit();

            return redirect()->route('admin.users')->with('success', "Data pengguna '{$request->nama_lengkap}' berhasil diperbarui!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', "Gagal memperbarui pengguna: " . $e->getMessage());
        }
    }

    /**
     * Toggle Kunci / Aktifkan Akun Pengguna
     */
    public function toggleUserStatus(Request $request, string $kode_pengguna): RedirectResponse
    {
        $user = DB::table('tb_pengguna')->where('kode_pengguna', $kode_pengguna)->first();
        if (!$user) {
            return redirect()->route('admin.users')->with('error', 'Data pengguna tidak ditemukan.');
        }

        // Cegah mengunci akun yang sedang aktif login
        if (Auth::user()?->kode_pengguna === $kode_pengguna) {
            return redirect()->route('admin.users')->with('error', 'Anda tidak dapat mengunci akun yang sedang digunakan saat ini.');
        }

        $newStatus = ($user->status_aktif == '1') ? '2' : '1';
        $statusText = ($newStatus == '1') ? 'diaktifkan kembali (Dapat Login)' : 'dinonaktifkan (Akses Terkunci)';

        $now = Carbon::now()->toDateTimeString();
        $currentUser = substr(Auth::user()?->username ?? 'admin', 0, 20);

        DB::table('tb_pengguna')->where('kode_pengguna', $kode_pengguna)->update([
            'status_aktif' => $newStatus,
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        if ($user->kode_karyawan && Schema::hasTable('tb_m_karyawan')) {
            DB::table('tb_m_karyawan')->where('kode_karyawan', $user->kode_karyawan)->update([
                'status_aktif' => $newStatus,
                'date_update' => $now,
            ]);
        }

        return redirect()->route('admin.users')->with('success', "Status akun '{$user->username}' berhasil {$statusText}!");
    }

    /**
     * Hapus Pengguna
     */
    public function deleteUser(Request $request, string $kode_pengguna): RedirectResponse
    {
        $user = DB::table('tb_pengguna')->where('kode_pengguna', $kode_pengguna)->first();
        if (!$user) {
            return redirect()->route('admin.users')->with('error', 'Data pengguna tidak ditemukan.');
        }

        // Cegah menghapus akun yang sedang aktif login
        if (Auth::user()?->kode_pengguna === $kode_pengguna) {
            return redirect()->route('admin.users')->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan saat ini.');
        }

        DB::table('tb_pengguna')->where('kode_pengguna', $kode_pengguna)->delete();

        return redirect()->route('admin.users')->with('success', "Akun pengguna '{$user->username}' berhasil dihapus dari sistem.");
    }

    /**
     * Master Data Paket Internet & Layanan Bandwidth (Master Admin)
     */
    public function paket(Request $request): View
    {
        $this->ensurePaketTableColumns();

        $search = $request->query('search');
        $selectedBangunan = $request->query('bangunan', 'all');
        $selectedKategori = $request->query('kategori', 'all');

        $query = DB::table('m_bandwith as b')
            ->leftJoin('m_bandwith_kategori as k', 'b.kode_kategori_bandwith', '=', 'k.kode_kategori_bandwith')
            ->select(
                'b.*',
                'k.nama_kategori_bandwith',
                'k.alias_nama_kategori'
            )
            ->where('b.hide', '0');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('b.kode_bandwith', 'like', "%{$search}%")
                  ->orWhere('b.nama_bandwith', 'like', "%{$search}%")
                  ->orWhere('k.nama_kategori_bandwith', 'like', "%{$search}%")
                  ->orWhere('b.peruntukan_bangunan', 'like', "%{$search}%")
                  ->orWhere('b.nominal_bandwith', 'like', "%{$search}%");
            });
        }

        if ($selectedBangunan !== 'all' && !empty($selectedBangunan)) {
            $query->where(function ($q) use ($selectedBangunan) {
                $q->where('b.peruntukan_bangunan', 'like', "%{$selectedBangunan}%")
                  ->orWhere('b.kategori_bangunan', 'like', "%{$selectedBangunan}%");
            });
        }

        if ($selectedKategori !== 'all' && !empty($selectedKategori)) {
            $query->where(function ($q) use ($selectedKategori) {
                $q->where('b.kode_kategori_bandwith', $selectedKategori)
                  ->orWhere('k.nama_kategori_bandwith', $selectedKategori);
            });
        }

        $pakets = $query->orderBy('b.nominal_bandwith', 'asc')
            ->orderBy('b.kode_bandwith', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Attach default name_bandwith jika masih kosong & cast data types
        foreach ($pakets as $p) {
            $p->harga_bandwith = (float) ($p->harga_bandwith ?? 0);
            $p->nominal_bandwith = (int) ($p->nominal_bandwith ?? 0);
            if (empty($p->nama_bandwith)) {
                $p->nama_bandwith = "Paket {$p->nominal_bandwith} Mbps";
            }
        }

        // List Kategori untuk dropdown
        $kategoriList = DB::table('m_bandwith_kategori')->where('disable', 0)->get();

        // 1. KPI Counters
        $totalPaket = DB::table('m_bandwith')->where('hide', '0')->count();
        $totalAktif = DB::table('m_bandwith')->where('hide', '0')->where('disable', 0)->count();
        $totalKategori = DB::table('m_bandwith_kategori')->where('disable', 0)->count();
        $minSpeed = DB::table('m_bandwith')->where('hide', '0')->min('nominal_bandwith') ?: 1;
        $maxSpeed = DB::table('m_bandwith')->where('hide', '0')->max('nominal_bandwith') ?: 1000;

        // 2. Count per Building Types
        $buildingTypes = [
            'KOS-KOSAN' => 'KOS-KOSAN',
            'RUMAH-PRIBADI' => 'RUMAH-PRIBADI',
            'RUMAH-KANTOR' => 'RUMAH-KANTOR',
            'RUKO' => 'RUKO',
            'APARTEMEN' => 'APARTEMEN',
            'GEDUNG' => 'GEDUNG',
            'OUTDOOR/EVENT' => 'OUTDOOR/EVENT',
        ];

        // Merge extra types from database if exists
        if (Schema::hasTable('m_jns_bangunan')) {
            $dbBangunan = DB::table('m_jns_bangunan')->where('hide', '0')->pluck('jenis_bangunan')->filter();
            foreach ($dbBangunan as $b) {
                $bUpper = strtoupper(trim($b));
                if (!empty($bUpper) && !isset($buildingTypes[$bUpper])) {
                    $buildingTypes[$bUpper] = $bUpper;
                }
            }
        }

        $buildingCounts = [];
        foreach ($buildingTypes as $key => $label) {
            $buildingCounts[$key] = DB::table('m_bandwith')
                ->where('hide', '0')
                ->where(function ($q) use ($key) {
                    $q->where('peruntukan_bangunan', 'like', "%{$key}%")
                      ->orWhere('kategori_bangunan', 'like', "%{$key}%");
                })
                ->count();
        }

        return view('admin.paket', [
            'user' => $request->user(),
            'pakets' => $pakets,
            'kategoriList' => $kategoriList,
            'totalPaket' => $totalPaket,
            'totalAktif' => $totalAktif,
            'totalKategori' => $totalKategori,
            'minSpeed' => $minSpeed,
            'maxSpeed' => $maxSpeed,
            'buildingTypes' => $buildingTypes,
            'buildingCounts' => $buildingCounts,
            'selectedBangunan' => $selectedBangunan,
            'selectedKategori' => $selectedKategori,
            'search' => $search,
        ]);
    }

    /**
     * Store / Update Master Paket Bandwidth
     * Otomatis mengupdate data tarif pelanggan aktif dan tagihan billing berjalan
     */
    public function storePaket(Request $request): RedirectResponse
    {
        $this->ensurePaketTableColumns();

        $request->validate([
            'kode_bandwith' => 'required|string|max:50',
            'nama_bandwith' => 'required|string|max:150',
            'kode_kategori_bandwith' => 'required|string|max:50',
            'nominal_bandwith' => 'required|numeric|min:1',
            'harga_bandwith' => 'required|numeric|min:0',
            'peruntukan_bangunan' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $now = Carbon::now()->toDateTimeString();
        $currentUser = substr(Auth::user()?->username ?? (Auth::user()?->nama ?? 'ADMIN'), 0, 20);
        $kodeBandwith = trim($request->kode_bandwith);

        $peruntukanString = 'RUMAH-KANTOR';
        if ($request->has('peruntukan_bangunan') && is_array($request->peruntukan_bangunan)) {
            $peruntukanString = implode(', ', $request->peruntukan_bangunan);
        } elseif ($request->filled('peruntukan_bangunan_text')) {
            $peruntukanString = $request->peruntukan_bangunan_text;
        }

        $disable = $request->has('is_active') && $request->is_active ? 0 : 0;
        if ($request->has('status_aktif')) {
            $disable = $request->status_aktif == '1' ? 0 : 1;
        }

        $newHarga = (float) $request->harga_bandwith;
        $newSpeed = (int) $request->nominal_bandwith;

        $existing = DB::table('m_bandwith')->where('kode_bandwith', $kodeBandwith)->first();

        DB::beginTransaction();
        try {
            if ($existing) {
                // UPDATE Paket
                DB::table('m_bandwith')->where('kode_bandwith', $kodeBandwith)->update([
                    'nama_bandwith' => $request->nama_bandwith,
                    'kode_kategori_bandwith' => $request->kode_kategori_bandwith,
                    'nominal_bandwith' => $newSpeed,
                    'harga_bandwith' => $newHarga,
                    'peruntukan_bangunan' => $peruntukanString,
                    'kategori_bangunan' => is_array($request->peruntukan_bangunan) ? ($request->peruntukan_bangunan[0] ?? 'RUMAH-KANTOR') : $peruntukanString,
                    'disable' => $disable,
                    'hide' => '0',
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]);
            } else {
                // INSERT Paket Baru
                DB::table('m_bandwith')->insert([
                    'kode_bandwith' => $kodeBandwith,
                    'nama_bandwith' => $request->nama_bandwith,
                    'kode_kategori_bandwith' => $request->kode_kategori_bandwith,
                    'nominal_bandwith' => $newSpeed,
                    'harga_bandwith' => $newHarga,
                    'peruntukan_bangunan' => $peruntukanString,
                    'kategori_bangunan' => is_array($request->peruntukan_bangunan) ? ($request->peruntukan_bangunan[0] ?? 'RUMAH-KANTOR') : $peruntukanString,
                    'disable' => $disable,
                    'hide' => '0',
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]);
            }

            // ===================================================================
            // CASCADING AUTO-UPDATE: Update Pelanggan & Billing Terkait
            // ===================================================================
            $updatedCustomerCount = 0;
            $updatedBillingCount = 0;

            // 1. Update data master tarif di trx_batchjob_register (Pelanggan)
            if (Schema::hasTable('trx_batchjob_register')) {
                $custUpdate = [
                    'user_update' => $currentUser,
                    'date_update' => $now,
                ];
                if (Schema::hasColumn('trx_batchjob_register', 'harga_bandwith')) {
                    $custUpdate['harga_bandwith'] = (string) $newHarga;
                }
                if (Schema::hasColumn('trx_batchjob_register', 'nominal_bandwith')) {
                    $custUpdate['nominal_bandwith'] = (string) $newSpeed;
                }
                $updatedCustomerCount = DB::table('trx_batchjob_register')
                    ->where('kode_bandwith', $kodeBandwith)
                    ->update($custUpdate);
            }

            // 2. Update tagihan billing berjalan yang belum lunas (status 11, 12, 13, 14)
            if (Schema::hasTable('trx_billing_layanan')) {
                $unpaidBillings = DB::table('trx_billing_layanan')
                    ->where('kode_bandwith', $kodeBandwith)
                    ->whereIn('status_bill_lay', ['11', '12', '13', '14']) // Draft, Published Unpaid, Overdue, Partial
                    ->get();

                foreach ($unpaidBillings as $bill) {
                    $potongan = (float) ($bill->potongan ?? 0);
                    $totalLayanan = max(0, $newHarga - $potongan);

                    DB::table('trx_billing_layanan')
                        ->where('kode_billing_layanan', $bill->kode_billing_layanan)
                        ->update([
                            'nominal_bandwith' => (string) $newSpeed,
                            'total_layanan' => (string) $totalLayanan,
                            'user_update' => $currentUser,
                            'date_update' => $now,
                        ]);

                    // Update detail invoice item T11
                    if (Schema::hasTable('trx_billing_layanan_detail')) {
                        DB::table('trx_billing_layanan_detail')
                            ->where('kode_billing_layanan', $bill->kode_billing_layanan)
                            ->where('kode_item', 'T11')
                            ->update([
                                'biaya' => (string) $newHarga,
                                'user_update' => $currentUser,
                            ]);
                    }

                    // Log audit trail
                    if (Schema::hasTable('trx_billing_layanan_log')) {
                        DB::table('trx_billing_layanan_log')->insert([
                            'kode_billing_lay_log' => "LOG-TARIFF-" . substr(md5($bill->kode_billing_layanan . microtime()), 0, 16),
                            'kode_billing_layanan' => $bill->kode_billing_layanan,
                            'status_bill_lay' => $bill->status_bill_lay,
                            'note_billing_lay' => "Tarif paket diubah otomatis dari Master Admin menjadi Rp " . number_format($newHarga, 0, ',', '.') . " oleh {$currentUser}",
                            'date_create' => $now,
                            'user_create' => $currentUser,
                            'hide' => '0',
                        ]);
                    }

                    $updatedBillingCount++;
                }
            }

            DB::commit();

            $msg = "Paket {$request->nama_bandwith} ({$kodeBandwith}) berhasil disimpan!";
            if ($updatedCustomerCount > 0 || $updatedBillingCount > 0) {
                $msg .= " Otomatis memperbarui {$updatedCustomerCount} data pelanggan aktif dan {$updatedBillingCount} invoice tagihan berjalan.";
            }

            return redirect()->route('admin.paket')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', "Gagal menyimpan paket: " . $e->getMessage());
        }
    }

    /**
     * Hapus Master Paket Bandwidth
     */
    public function deletePaket(Request|string $request, ?string $kode_bandwith = null): RedirectResponse
    {
        $kodeBandwith = ($request instanceof Request) ? ($kode_bandwith ?? $request->route('kode_bandwith') ?? '') : (string) $request;
        $kodeBandwith = urldecode(trim($kodeBandwith));

        // Cek jika ada pelanggan aktif yang masih berlangganan paket ini
        $activeCustomerCount = 0;
        if (Schema::hasTable('trx_batchjob_register')) {
            $activeCustomerCount = DB::table('trx_batchjob_register')
                ->where('kode_bandwith', $kodeBandwith)
                ->count();
        }

        if ($activeCustomerCount > 0) {
            return redirect()->back()->with('error', "Gagal menghapus: Masih ada {$activeCustomerCount} pelanggan aktif yang menggunakan paket {$kodeBandwith}.");
        }

        DB::table('m_bandwith')->where('kode_bandwith', $kodeBandwith)->delete();

        return redirect()->route('admin.paket')->with('success', "Paket {$kodeBandwith} berhasil dihapus dari sistem.");
    }

    /**
     * Memastikan struktur tabel m_bandwith dan m_bandwith_kategori tersedia di database
     */
    protected function ensurePaketTableColumns(): void
    {
        try {
            if (!Schema::hasTable('m_bandwith_kategori')) {
                Schema::create('m_bandwith_kategori', function (Blueprint $table) {
                    $table->string('kode_kategori_bandwith', 50)->primary();
                    $table->string('nama_kategori_bandwith', 100);
                    $table->string('alias_nama_kategori', 100)->nullable();
                    $table->decimal('biaya_reg', 15, 2)->default(0);
                    $table->tinyInteger('disable')->default(0);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('m_bandwith')) {
                Schema::create('m_bandwith', function (Blueprint $table) {
                    $table->string('kode_bandwith', 50)->primary();
                    $table->string('nama_bandwith', 150)->nullable();
                    $table->string('kode_kategori_bandwith', 50)->nullable();
                    $table->integer('nominal_bandwith')->default(0);
                    $table->decimal('harga_bandwith', 15, 2)->default(0);
                    $table->string('peruntukan_bangunan', 255)->nullable();
                    $table->string('kategori_bangunan', 100)->nullable();
                    $table->tinyInteger('disable')->default(0);
                    $table->char('hide', 1)->default('0');
                    $table->dateTime('date_create')->nullable();
                    $table->string('user_create', 100)->nullable();
                    $table->dateTime('date_update')->nullable();
                    $table->string('user_update', 100)->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            Log::warning("Auto-ensure m_bandwith columns: " . $e->getMessage());
        }
    }
}
