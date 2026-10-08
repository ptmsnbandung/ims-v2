@extends('layouts.app')

@section('title', 'Monitoring & Data OLT')

@section('content')
<div class="space-y-5" x-data="{ 
    // Navigation Level: 'pon' -> 'odp' -> 'users'
    currentLevel: 'pon',
    selectedPon: null,
    selectedOdp: null,

    // Data dari backend
    ponsData: @js($pons),
    odpsData: @js($odps),
    usersData: @js($users),

    // Filter & Search
    searchQuery: '',

    // Actions
    goToPonList() {
        this.currentLevel = 'pon';
        this.selectedPon = null;
        this.selectedOdp = null;
        this.searchQuery = '';
    },

    goToOdpList(pon) {
        this.selectedPon = pon;
        this.selectedOdp = null;
        this.currentLevel = 'odp';
        this.searchQuery = '';
    },

    goToUsersList(odp) {
        this.selectedOdp = odp;
        // Pastikan selectedPon terisi jika langsung dari ODP
        if (!this.selectedPon && odp.pon_id) {
            this.selectedPon = this.ponsData.find(p => p.id == odp.pon_id) || null;
        }
        this.currentLevel = 'users';
        this.searchQuery = '';
    },

    // Getters / Computed
    get filteredPons() {
        if (!this.searchQuery.trim()) return this.ponsData;
        const q = this.searchQuery.toLowerCase();
        return this.ponsData.filter(p => 
            (p.nama_pon && p.nama_pon.toLowerCase().includes(q)) ||
            String(p.id).includes(q)
        );
    },

    get filteredOdps() {
        let list = this.odpsData;
        if (this.selectedPon) {
            list = list.filter(o => o.pon_id == this.selectedPon.id);
        }
        if (!this.searchQuery.trim()) return list;
        const q = this.searchQuery.toLowerCase();
        return list.filter(o => 
            (o.nama_odp && o.nama_odp.toLowerCase().includes(q)) ||
            String(o.id).includes(q)
        );
    },

    get filteredUsers() {
        let list = [];
        if (this.selectedOdp) {
            // Users spesifik ODP
            list = this.usersData.filter(u => u.odp_id == this.selectedOdp.id);
        } else if (this.selectedPon) {
            // Users di semua ODP milik PON ini
            const odpIds = this.odpsData.filter(o => o.pon_id == this.selectedPon.id).map(o => o.id);
            list = this.usersData.filter(u => odpIds.includes(u.odp_id));
        } else {
            list = this.usersData;
        }

        if (!this.searchQuery.trim()) return list;
        const q = this.searchQuery.toLowerCase();
        return list.filter(u => 
            (u.nama_user && u.nama_user.toLowerCase().includes(q)) ||
            (u.nomor_internet && u.nomor_internet.toLowerCase().includes(q)) ||
            (u.keterangan && u.keterangan.toLowerCase().includes(q))
        );
    }
}">

    <!-- ============================================== -->
    <!-- 1. HEADER SECTION                              -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a2.25 2.25 0 0 1 1.8-.85h8.9a2.25 2.25 0 0 1 1.8.85l2.1 3.15a4.5 4.5 0 0 1 .9 2.7" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight uppercase">
                            OLT {{ $selectedOltId }}: {{ $currentOlt->nama_olt ?? 'OLT' }}
                        </h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Active Node
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Ringkasan: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $pons->count() }} Port PON</span> &bull; <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $odps->count() }} ODP</span> &bull; <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $users->count() }} Pelanggan Terdaftar</span>
                    </p>
                </div>
            </div>

            <!-- Right Info Source -->
            <div class="text-right">
                <span class="inline-block px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[11px] font-mono font-medium text-slate-600 dark:text-slate-400">
                    Database: gomsn.pon{{ $selectedOltId }} / odp{{ $selectedOltId }} / users{{ $selectedOltId }}
                </span>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. STATISTIC CARDS (KPIs)                      -->
    <!-- ============================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Card 1: Port PON -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Port PON</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ number_format($stats['total_pon']) }}</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Port Utama OLT</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Total ODP -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total ODP</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ number_format($stats['total_odp']) }}</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Titik Distribusi</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Total Pelanggan -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Pelanggan</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ number_format($stats['total_users']) }}</h3>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">Users Aktif</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Utilisasi Kapasitas Port -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Utilisasi Port</p>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ $stats['utilization_percent'] }}%</h3>
                </div>
                <div class="text-right text-[11px] text-slate-500 dark:text-slate-400">
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ number_format($stats['total_used']) }}</span> / {{ number_format($stats['total_capacity']) }} Port
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden mt-2.5">
                <div class="h-full rounded-full transition-all duration-300 {{ $stats['utilization_percent'] >= 90 ? 'bg-rose-500' : ($stats['utilization_percent'] >= 75 ? 'bg-amber-500' : 'bg-blue-600') }}"
                     style="width: {{ $stats['utilization_percent'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 3. BREADCRUMB & DRILL-DOWN NAVIGATION BAR      -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        
        <!-- Breadcrumb Steps -->
        <div class="flex items-center flex-wrap gap-2 text-xs">
            <!-- Step 1: OLT (PON List) -->
            <button type="button" 
                    @click="goToPonList()"
                    :class="currentLevel === 'pon' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium'"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
                <span>1. Tabel PON</span>
                <span class="px-1.5 py-0.2 rounded text-[10px]" :class="currentLevel === 'pon' ? 'bg-blue-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'" x-text="ponsData.length"></span>
            </button>

            <!-- Separator -->
            <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>

            <!-- Step 2: ODP List -->
            <button type="button" 
                    @click="if (selectedPon) { goToOdpList(selectedPon); } else if (ponsData.length > 0) { goToOdpList(ponsData[0]); }"
                    :class="currentLevel === 'odp' ? 'bg-blue-600 text-white font-bold' : (selectedPon ? 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium' : 'bg-slate-50 dark:bg-slate-950 text-slate-400 cursor-not-allowed')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <span>2. Tabel ODP</span>
                <template x-if="selectedPon">
                    <span class="font-bold text-xs" x-text="'(' + selectedPon.nama_pon + ')'"></span>
                </template>
            </button>

            <!-- Separator -->
            <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>

            <!-- Step 3: Users List -->
            <button type="button" 
                    :class="currentLevel === 'users' ? 'bg-blue-600 text-white font-bold' : (selectedOdp ? 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium' : 'bg-slate-50 dark:bg-slate-950 text-slate-400 cursor-not-allowed')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>3. Tabel Users</span>
                <template x-if="selectedOdp">
                    <span class="font-bold text-xs" x-text="'(' + selectedOdp.nama_odp + ')'"></span>
                </template>
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative min-w-[240px]">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <input type="text" 
                   x-model="searchQuery" 
                   :placeholder="currentLevel === 'pon' ? 'Cari port PON...' : (currentLevel === 'odp' ? 'Cari ODP...' : 'Cari user / nomor internet...')" 
                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-8 py-1.5 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
            <button type="button" 
                    x-show="searchQuery" 
                    @click="searchQuery = ''" 
                    class="absolute inset-y-0 right-2 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                &times;
            </button>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 4. LEVEL 1: TABEL UTAMA - TABEL PON            -->
    <!-- ============================================== -->
    <div x-show="currentLevel === 'pon'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    Tabel Port PON &mdash; OLT {{ strtoupper($currentOlt->nama_olt ?? '') }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Pilih port PON dan klik tombol <span class="font-semibold text-blue-600 dark:text-blue-400">Detail ODP</span> untuk melihat daftar ODP di bawahnya.
                </p>
            </div>
            <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 text-xs font-bold">
                <span x-text="filteredPons.length"></span> Port PON
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Nama Port PON</th>
                        <th class="px-4 py-3">PON ID</th>
                        <th class="px-4 py-3 text-center">Maks Port</th>
                        <th class="px-4 py-3 text-center">Total ODP Terhubung</th>
                        <th class="px-4 py-3 text-center">Total Pelanggan</th>
                        <th class="px-4 py-3">Utilisasi Port</th>
                        <th class="px-4 py-3 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="(pon, index) in filteredPons" :key="pon.id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                            <td class="px-4 py-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                            
                            <!-- Nama Port PON -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-md bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center text-xs">
                                        <span x-text="'P' + pon.id"></span>
                                    </div>
                                    <span class="font-bold text-slate-900 dark:text-white uppercase" x-text="pon.nama_pon"></span>
                                </div>
                            </td>

                            <!-- PON ID -->
                            <td class="px-4 py-3 font-mono text-slate-500" x-text="'#' + pon.id"></td>

                            <!-- Maks Port -->
                            <td class="px-4 py-3 text-center font-mono">
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(pon.port_max || 8) + ' Port'"></span>
                            </td>

                            <!-- Total ODP -->
                            <td class="px-4 py-3 text-center font-mono">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                      x-text="(pon.odp_count || 0) + ' ODP'"></span>
                            </td>

                            <!-- Total Pelanggan -->
                            <td class="px-4 py-3 text-center font-mono">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                                      x-text="(pon.user_count || 0) + ' Users'"></span>
                            </td>

                            <!-- Utilisasi -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 max-w-[150px]">
                                    <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all"
                                             :class="pon.utilization_percent >= 90 ? 'bg-rose-500' : (pon.utilization_percent >= 75 ? 'bg-amber-500' : 'bg-blue-600')"
                                             :style="'width: ' + pon.utilization_percent + '%'"></div>
                                    </div>
                                    <span class="text-xs font-mono font-semibold text-slate-700 dark:text-slate-300" x-text="pon.utilization_percent + '%'"></span>
                                </div>
                            </td>

                            <!-- Tombol Aksi Detail -> Masuk ke Tabel ODP -->
                            <td class="px-4 py-3 text-center">
                                <button type="button" 
                                        @click="goToOdpList(pon)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition transform hover:scale-[1.02] active:scale-[0.98]">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                    </svg>
                                    <span>Detail ODP</span>
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredPons.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400 italic">
                            Tidak ada data Port PON yang ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 5. LEVEL 2: TABEL ODP (MILIK PON TERPILIH)     -->
    <!-- ============================================== -->
    <div x-show="currentLevel === 'odp'" class="space-y-3">
        
        <!-- Bar Navigasi Kembali & Banner Info PON -->
        <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="goToPonList()"
                        class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Kembali ke Daftar PON</span>
                </button>
                
                <div>
                    <h3 class="text-xs font-bold text-blue-950 dark:text-blue-200 uppercase tracking-tight"
                        x-text="'Daftar ODP di ' + (selectedPon?.nama_pon || 'Port PON')"></h3>
                    <p class="text-[11px] text-blue-700 dark:text-blue-300 mt-0.2">
                        Klik tombol <span class="font-semibold text-blue-900 dark:text-white">Detail Users</span> pada baris ODP untuk melihat pelanggan terpasang.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded bg-blue-100 dark:bg-blue-900/60 text-blue-900 dark:text-blue-200 text-xs font-bold font-mono"
                      x-text="'PON ID #' + (selectedPon?.id || '')"></span>
                <span class="px-2.5 py-1 rounded bg-blue-600 text-white text-xs font-bold font-mono"
                      x-text="filteredOdps.length + ' ODP Terdaftar'"></span>
            </div>
        </div>

        <!-- Tabel ODP -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3">Nama ODP</th>
                            <th class="px-4 py-3">ODP ID</th>
                            <th class="px-4 py-3 text-center">Kapasitas Port</th>
                            <th class="px-4 py-3 text-center">Total Pelanggan</th>
                            <th class="px-4 py-3">Utilisasi Port</th>
                            <th class="px-4 py-3">Koordinat GPS</th>
                            <th class="px-4 py-3 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(odp, index) in filteredOdps" :key="odp.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                                <td class="px-4 py-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                
                                <!-- Nama ODP -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full"
                                              :class="odp.is_full ? 'bg-amber-500 ring-2 ring-amber-200 dark:ring-amber-900' : (odp.user_count > 0 ? 'bg-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-900' : 'bg-slate-300')"></span>
                                        <span class="font-bold text-slate-900 dark:text-white uppercase" x-text="odp.nama_odp"></span>
                                    </div>
                                </td>

                                <!-- ODP ID -->
                                <td class="px-4 py-3 font-mono text-slate-500" x-text="'#' + odp.id"></td>

                                <!-- Kapasitas Port -->
                                <td class="px-4 py-3 text-center font-mono">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(odp.port_max || 8) + ' Port'"></span>
                                </td>

                                <!-- Total Pelanggan -->
                                <td class="px-4 py-3 text-center font-mono">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                          :class="odp.is_full ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : (odp.user_count > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400')"
                                          x-text="(odp.user_count || 0) + ' Users'"></span>
                                </td>

                                <!-- Utilisasi Bar -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 max-w-[150px]">
                                        <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                 :class="odp.is_full ? 'bg-amber-500' : (odp.utilization_percent >= 75 ? 'bg-amber-500' : 'bg-blue-600')"
                                                 :style="'width: ' + (odp.utilization_percent || 0) + '%'"></div>
                                        </div>
                                        <span class="text-xs font-mono font-semibold text-slate-700 dark:text-slate-300" x-text="(odp.utilization_percent || 0) + '%'"></span>
                                    </div>
                                </td>

                                <!-- Koordinat GPS -->
                                <td class="px-4 py-3">
                                    <template x-if="odp.latitude && odp.longitude">
                                        <a :href="'https://maps.google.com/?q=' + odp.latitude + ',' + odp.longitude" 
                                           target="_blank" 
                                           class="inline-flex items-center gap-1 font-mono text-[11px] text-blue-600 dark:text-blue-400 hover:underline">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                            <span x-text="Number(odp.latitude).toFixed(4) + ', ' + Number(odp.longitude).toFixed(4)"></span>
                                        </a>
                                    </template>
                                    <template x-if="!odp.latitude || !odp.longitude">
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    </template>
                                </td>

                                <!-- Tombol Aksi Detail -> Masuk ke Tabel Users -->
                                <td class="px-4 py-3 text-center">
                                    <button type="button" 
                                            @click="goToUsersList(odp)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition transform hover:scale-[1.02] active:scale-[0.98]">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                        <span>Detail Users</span>
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredOdps.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400 italic">
                                Belum ada data ODP terdaftar pada Port PON ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 6. LEVEL 3: TABEL USERS (MILIK ODP TERPILIH)   -->
    <!-- ============================================== -->
    <div x-show="currentLevel === 'users'" class="space-y-3">
        
        <!-- Bar Navigasi Kembali & Banner Info ODP -->
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="if (selectedPon) { goToOdpList(selectedPon); } else { goToPonList(); }"
                        class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Kembali ke Tabel ODP</span>
                </button>
                
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-emerald-950 dark:text-emerald-200 uppercase tracking-tight"
                            x-text="'Daftar Pelanggan pada ODP: ' + (selectedOdp?.nama_odp || 'ODP')"></h3>
                        <template x-if="selectedPon">
                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400" x-text="'(' + selectedPon.nama_pon + ')'"></span>
                        </template>
                    </div>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-300 mt-0.2">
                        Kapasitas Port: <span class="font-bold text-emerald-950 dark:text-white" x-text="(filteredUsers.length || 0) + ' / ' + (selectedOdp?.port_max || 8) + ' Port'"></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded bg-emerald-100 dark:bg-emerald-900/60 text-emerald-900 dark:text-emerald-200 text-xs font-bold font-mono"
                      x-text="'ODP ID #' + (selectedOdp?.id || '')"></span>
                <span class="px-2.5 py-1 rounded bg-emerald-600 text-white text-xs font-bold font-mono"
                      x-text="filteredUsers.length + ' Pelanggan'"></span>
            </div>
        </div>

        <!-- Tabel Users -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3">Nama Pelanggan</th>
                            <th class="px-4 py-3">Nomor Internet</th>
                            <th class="px-4 py-3">ODP Asal</th>
                            <th class="px-4 py-3">Port PON</th>
                            <th class="px-4 py-3">OLT Label</th>
                            <th class="px-4 py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(user, index) in filteredUsers" :key="user.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                                <td class="px-4 py-2.5 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                
                                <!-- Nama User -->
                                <td class="px-4 py-2.5">
                                    <span class="font-bold text-slate-900 dark:text-white" x-text="user.nama_user"></span>
                                </td>

                                <!-- Nomor Internet -->
                                <td class="px-4 py-2.5 font-mono font-semibold text-blue-600 dark:text-blue-400" x-text="user.nomor_internet"></td>

                                <!-- ODP Asal -->
                                <td class="px-4 py-2.5">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedOdp?.nama_odp || ('ODP #' + user.odp_id)"></span>
                                </td>

                                <!-- PON -->
                                <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300">
                                    <span x-text="selectedPon?.nama_pon || '-'"></span>
                                </td>

                                <!-- OLT Label -->
                                <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400 font-mono text-[11px]" x-text="user.olt || '-'"></td>

                                <!-- Keterangan -->
                                <td class="px-4 py-2.5 text-slate-500 dark:text-slate-400 italic" x-text="user.keterangan || '-'"></td>
                            </tr>
                        </template>

                        <tr x-show="filteredUsers.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">
                                Belum ada data pelanggan yang terpasang pada ODP ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
