@extends('layouts.app')

@section('title', 'Data OLT')

@section('content')
<style>
    /* Styling tombol OLT agar selalu memiliki warna solid & cursor pointer */
    button, [type='button'], [type='submit'] {
        cursor: pointer !important;
    }
    .btn-olt-lihat {
        background-color: #0c4d58 !important;
        color: #ffffff !important;
        border: 1px solid #083e47 !important;
        cursor: pointer !important;
    }
    .btn-olt-lihat:hover {
        background-color: #083e47 !important;
    }
    .btn-olt-edit {
        background-color: #f59e0b !important;
        color: #ffffff !important;
        border: 1px solid #d97706 !important;
        cursor: pointer !important;
    }
    .btn-olt-edit:hover {
        background-color: #d97706 !important;
    }
    .btn-olt-hapus {
        background-color: #ef4444 !important;
        color: #ffffff !important;
        border: 1px solid #dc2626 !important;
        cursor: pointer !important;
    }
    .btn-olt-hapus:hover {
        background-color: #dc2626 !important;
    }
    .badge-olt-active {
        background-color: #0c4d58 !important;
        color: #ffffff !important;
        cursor: pointer !important;
    }
    .badge-olt-inactive {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #e2e8f0 !important;
        cursor: pointer !important;
    }
    .badge-olt-inactive:hover {
        background-color: #e2e8f0 !important;
    }
    .olt-table-header {
        background: linear-gradient(90deg, #094b54 0%, #086b75 35%, #057d89 70%, #009688 100%) !important;
    }
</style>

<div class="space-y-4" x-data="{ 
    // Hierarki level: 'pon' -> 'odp' -> 'users'
    currentLevel: 'pon',
    selectedPon: null,
    selectedOdp: null,

    // Data dari backend
    ponsData: @js($pons),
    odpsData: @js($odps),
    usersData: @js($users),

    // Filter pencarian
    searchQuery: '',

    // Navigasi
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
        if (!this.selectedPon && odp.pon_id) {
            this.selectedPon = this.ponsData.find(p => p.id == odp.pon_id) || null;
        }
        this.currentLevel = 'users';
        this.searchQuery = '';
    },

    // Helper warna ratio port/user
    getRatioTextColor(used, total) {
        if (!total || total <= 0) return 'color: #64748b;';
        const percent = (used / total) * 100;
        if (percent >= 100) return 'color: #e11d48; font-weight: 700;';
        if (percent >= 75) return 'color: #d97706; font-weight: 700;';
        return 'color: #059669; font-weight: 700;';
    },

    getRatioBarColor(used, total) {
        if (!total || total <= 0) return 'background-color: #cbd5e1;';
        const percent = (used / total) * 100;
        if (percent >= 100) return 'background-color: #e11d48;';
        if (percent >= 75) return 'background-color: #d97706;';
        return 'background-color: #059669;';
    },

    // Filter data
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
            list = this.usersData.filter(u => u.odp_id == this.selectedOdp.id);
        } else if (this.selectedPon) {
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
            (u.alamat && u.alamat.toLowerCase().includes(q)) ||
            (u.nomor_hp && u.nomor_hp.toLowerCase().includes(q)) ||
            (u.keterangan && u.keterangan.toLowerCase().includes(q))
        );
    }
}">

    <!-- ============================================== -->
    <!-- TOP BAR: BREADCRUMB & SEARCH                   -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        
        <!-- Breadcrumb & Back Navigation -->
        <div class="flex items-center flex-wrap gap-2 text-xs">
            <!-- Level 1: OLT / PON Button -->
            <button type="button" 
                    @click="goToPonList()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg font-semibold transition"
                    :class="currentLevel === 'pon' ? 'badge-olt-active shadow-xs' : 'badge-olt-inactive'">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color: inherit;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a2.25 2.25 0 0 1 1.8-.85h8.9a2.25 2.25 0 0 1 1.8.85l2.1 3.15a4.5 4.5 0 0 1 .9 2.7" />
                </svg>
                <span>OLT: {{ strtoupper($currentOlt->nama_olt ?? 'OLT') }}</span>
            </button>

            <!-- Separator & Level 2: ODP -->
            <template x-if="selectedPon || currentLevel === 'odp' || currentLevel === 'users'">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 font-bold">&gt;</span>
                    <button type="button" 
                            @click="if (selectedPon) goToOdpList(selectedPon)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg font-semibold transition"
                            :class="currentLevel === 'odp' ? 'badge-olt-active shadow-xs' : 'badge-olt-inactive'">
                        <span x-text="selectedPon ? selectedPon.nama_pon : 'ODP'"></span>
                    </button>
                </div>
            </template>

            <!-- Separator & Level 3: Users -->
            <template x-if="selectedOdp || currentLevel === 'users'">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 font-bold">&gt;</span>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg font-bold badge-olt-active shadow-xs"
                          x-text="'ODP: ' + (selectedOdp ? selectedOdp.nama_odp : 'Users')">
                    </span>
                </div>
            </template>
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
                   :placeholder="currentLevel === 'pon' ? 'Cari PON...' : (currentLevel === 'odp' ? 'Cari ODP...' : 'Cari User / No Internet...')" 
                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-8 py-1.5 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600 transition">
            <button type="button" 
                    x-show="searchQuery" 
                    @click="searchQuery = ''" 
                    class="absolute inset-y-0 right-2 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                &times;
            </button>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 1. LEVEL 1: TABEL PON                          -->
    <!-- ============================================== -->
    <div x-show="currentLevel === 'pon'" class="rounded-xl overflow-hidden shadow-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-left border-collapse">
            <!-- Gradient Header -->
            <thead>
                <tr class="olt-table-header">
                    <th class="py-3 px-6 text-white font-bold text-xs uppercase tracking-wider w-1/2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z" />
                            </svg>
                            <span>NAMA PON</span>
                        </div>
                    </th>
                    <th class="py-3 px-6 text-white font-bold text-xs uppercase tracking-wider text-center w-1/4">
                        <div class="flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                            <span>PORT</span>
                        </div>
                    </th>
                    <th class="py-3 px-6 text-white font-bold text-xs uppercase tracking-wider text-right w-1/4">
                        <div class="flex items-center justify-end gap-1.5">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <span>AKSI</span>
                        </div>
                    </th>
                </tr>
            </thead>

            <!-- Table Body -->
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <template x-for="pon in filteredPons" :key="pon.id">
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40 transition-colors">
                        <!-- Nama PON -->
                        <td class="py-3.5 px-6 font-bold text-slate-900 dark:text-white uppercase tracking-tight"
                            x-text="pon.nama_pon">
                        </td>

                        <!-- Port Ratio + Mini Bar -->
                        <td class="py-3.5 px-6 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="font-bold text-xs font-mono"
                                      :style="getRatioTextColor(pon.odp_count || 0, pon.port_max || 8)"
                                      x-text="(pon.odp_count || 0) + '/' + (pon.port_max || 8)">
                                </span>
                                <!-- Mini Bar Underneath -->
                                <div class="w-8 h-1 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden mt-1">
                                    <div class="h-full rounded-full transition-all"
                                         :style="getRatioBarColor(pon.odp_count || 0, pon.port_max || 8) + ' width: ' + Math.min(100, Math.round(((pon.odp_count || 0) / (pon.port_max || 8)) * 100)) + '%;'">
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Tombol Aksi: Lihat, Edit, Hapus -->
                        <td class="py-3.5 px-6 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <!-- Tombol Lihat (Membuka Tabel ODP) -->
                                <button type="button" 
                                        @click="goToOdpList(pon)"
                                        class="btn-olt-lihat px-3.5 py-1.5 rounded-md font-semibold text-xs inline-flex items-center gap-1.5 shadow-sm transition transform hover:scale-[1.02] active:scale-[0.98]">
                                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Lihat</span>
                                </button>

                                <!-- Tombol Edit -->
                                <button type="button" 
                                        class="btn-olt-edit px-3 py-1.5 rounded-md font-semibold text-xs inline-flex items-center gap-1.5 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                    <span>Edit</span>
                                </button>

                                <!-- Tombol Hapus -->
                                <button type="button" 
                                        class="btn-olt-hapus px-3 py-1.5 rounded-md font-semibold text-xs inline-flex items-center gap-1.5 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <tr x-show="filteredPons.length === 0">
                    <td colspan="3" class="py-8 text-center text-slate-400 italic">
                        Tidak ada data Port PON yang ditemukan.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ============================================== -->
    <!-- 2. LEVEL 2: TABEL ODP                          -->
    <!-- ============================================== -->
    <div x-show="currentLevel === 'odp'" class="rounded-xl overflow-hidden shadow-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-left border-collapse">
            <!-- Gradient Header -->
            <thead>
                <tr class="olt-table-header">
                    <th class="py-3 px-6 text-white font-bold text-xs uppercase tracking-wider w-1/2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                            </svg>
                            <span>NAMA ODP</span>
                        </div>
                    </th>
                    <th class="py-3 px-6 text-white font-bold text-xs uppercase tracking-wider text-center w-1/4">
                        <div class="flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span>USER</span>
                        </div>
                    </th>
                    <th class="py-3 px-6 text-white font-bold text-xs uppercase tracking-wider text-right w-1/4">
                        <div class="flex items-center justify-end gap-1.5">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <span>AKSI</span>
                        </div>
                    </th>
                </tr>
            </thead>

            <!-- Table Body -->
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <template x-for="odp in filteredOdps" :key="odp.id">
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40 transition-colors">
                        <!-- Nama ODP -->
                        <td class="py-3.5 px-6 font-bold text-slate-900 dark:text-white uppercase tracking-tight"
                            x-text="odp.nama_odp">
                        </td>

                        <!-- User Ratio + Mini Bar -->
                        <td class="py-3.5 px-6 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="font-bold text-xs font-mono"
                                      :style="getRatioTextColor(odp.user_count || 0, odp.port_max || 8)"
                                      x-text="(odp.user_count || 0) + '/' + (odp.port_max || 8)">
                                </span>
                                <!-- Mini Bar Underneath -->
                                <div class="w-8 h-1 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden mt-1">
                                    <div class="h-full rounded-full transition-all"
                                         :style="getRatioBarColor(odp.user_count || 0, odp.port_max || 8) + ' width: ' + Math.min(100, Math.round(((odp.user_count || 0) / (odp.port_max || 8)) * 100)) + '%;'">
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Tombol Aksi: Lihat, Edit, Hapus -->
                        <td class="py-3.5 px-6 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <!-- Tombol Lihat (Membuka Tabel Users) -->
                                <button type="button" 
                                        @click="goToUsersList(odp)"
                                        class="btn-olt-lihat px-3.5 py-1.5 rounded-md font-semibold text-xs inline-flex items-center gap-1.5 shadow-sm transition transform hover:scale-[1.02] active:scale-[0.98]">
                                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Lihat</span>
                                </button>

                                <!-- Tombol Edit -->
                                <button type="button" 
                                        class="btn-olt-edit px-3 py-1.5 rounded-md font-semibold text-xs inline-flex items-center gap-1.5 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                    <span>Edit</span>
                                </button>

                                <!-- Tombol Hapus -->
                                <button type="button" 
                                        class="btn-olt-hapus px-3 py-1.5 rounded-md font-semibold text-xs inline-flex items-center gap-1.5 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <tr x-show="filteredOdps.length === 0">
                    <td colspan="3" class="py-8 text-center text-slate-400 italic">
                        Belum ada data ODP pada Port PON ini.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ============================================== -->
    <!-- 3. LEVEL 3: TABEL USERS / PELANGGAN            -->
    <!-- ============================================== -->
    <div x-show="currentLevel === 'users'" class="rounded-xl overflow-hidden shadow-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-left border-collapse">
            <!-- Gradient Header -->
            <thead>
                <tr class="olt-table-header">
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider">PELANGGAN</th>
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider">LAYANAN</th>
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider">ALAMAT</th>
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider">NOMOR</th>
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider">NOTE</th>
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider">KETERANGAN</th>
                    <th class="py-3 px-4 text-white font-bold text-xs uppercase tracking-wider text-right">AKSI</th>
                </tr>
            </thead>

            <!-- Table Body -->
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <template x-for="user in filteredUsers" :key="user.id">
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40 transition-colors">
                        <!-- Pelanggan: ID, Nama, Badge Aktif -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-1">
                                <span class="text-[11px] font-mono text-slate-400 block" x-text="user.nomor_internet"></span>
                                <span class="font-bold text-slate-900 dark:text-white block text-xs capitalize" x-text="user.nama_user"></span>
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 uppercase tracking-wider">
                                    AKTIF
                                </span>
                            </div>
                        </td>

                        <!-- Layanan -->
                        <td class="py-3.5 px-4 align-top text-slate-700 dark:text-slate-300 font-semibold uppercase text-xs"
                            x-text="user.layanan || 'UP TO NEW'">
                        </td>

                        <!-- Alamat -->
                        <td class="py-3.5 px-4 align-top text-slate-600 dark:text-slate-400 uppercase text-[11px] leading-relaxed max-w-xs"
                            x-text="user.alamat || '-'">
                        </td>

                        <!-- Nomor & Bandwidth Speed -->
                        <td class="py-3.5 px-4 align-top font-mono text-xs">
                            <div class="space-y-0.5">
                                <span class="block font-semibold text-slate-800 dark:text-slate-200" x-text="user.nomor_hp || '-'"></span>
                                <span class="block text-slate-500 text-[11px]" x-text="user.speed || '-'"></span>
                            </div>
                        </td>

                        <!-- Note -->
                        <td class="py-3.5 px-4 align-top font-mono text-[11px] text-rose-600 dark:text-rose-400 font-medium"
                            x-text="user.note || '-'">
                        </td>

                        <!-- Keterangan -->
                        <td class="py-3.5 px-4 align-top text-slate-500 dark:text-slate-400 text-xs"
                            x-text="user.keterangan || ''">
                        </td>

                        <!-- Aksi: Edit & Hapus (Square Buttons) -->
                        <td class="py-3.5 px-4 align-top text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <!-- Tombol Edit -->
                                <button type="button" 
                                        class="btn-olt-edit w-8 h-8 rounded-lg flex items-center justify-center shadow-xs transition">
                                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>

                                <!-- Tombol Hapus -->
                                <button type="button" 
                                        class="btn-olt-hapus w-8 h-8 rounded-lg flex items-center justify-center shadow-xs transition">
                                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <tr x-show="filteredUsers.length === 0">
                    <td colspan="7" class="py-8 text-center text-slate-400 italic">
                        Belum ada pelanggan terpasang pada ODP ini.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
