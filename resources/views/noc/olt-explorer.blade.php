@extends('layouts.app')

@section('title', 'Monitoring & Data OLT')

@section('content')
<div class="space-y-5" x-data="{ 
    activeTab: 'odp',
    selectedPonFilter: '',
    searchQuery: '',
    
    // Modal ODP Detail
    showOdpModal: false,
    modalOdp: null,
    modalOdpUsers: [],

    openDetailOdp(odp) {
        this.modalOdp = odp;
        this.modalOdpUsers = odp.users || [];
        this.showOdpModal = true;
    },

    // Modal PON Detail
    showPonModal: false,
    modalPon: null,
    modalPonOdps: [],

    openDetailPon(pon) {
        this.modalPon = pon;
        this.modalPonOdps = pon.odps || [];
        this.showPonModal = true;
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
    <!-- 3. CONTROLS, FILTER, & TAB SWITCHER            -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        
        <!-- Search & Filter Input -->
        <form method="GET" action="{{ route('noc.network-olt', ['olt_id' => $selectedOltId]) }}" class="flex flex-wrap items-center gap-2 flex-1">
            
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari user, nomor internet, catatan..." 
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-8 py-1.5 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                @if($search)
                    <a href="{{ route('noc.network-olt', ['olt_id' => $selectedOltId]) }}" class="absolute inset-y-0 right-2 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                        &times;
                    </a>
                @endif
            </div>

            <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition shrink-0">
                Cari
            </button>
        </form>

        <!-- Tab Switcher (ODP, PON, Users) -->
        <div class="inline-flex p-0.5 bg-slate-100 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800 shrink-0">
            <button type="button" 
                    @click="activeTab = 'odp'"
                    :class="activeTab === 'odp' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-semibold' : 'text-slate-500 dark:text-slate-400 font-normal'"
                    class="px-3 py-1 rounded text-xs transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <span>Tabel ODP</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px]">{{ $odps->count() }}</span>
            </button>

            <button type="button" 
                    @click="activeTab = 'pon'"
                    :class="activeTab === 'pon' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-semibold' : 'text-slate-500 dark:text-slate-400 font-normal'"
                    class="px-3 py-1 rounded text-xs transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
                <span>Tabel PON</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px]">{{ $pons->count() }}</span>
            </button>

            <button type="button" 
                    @click="activeTab = 'users'"
                    :class="activeTab === 'users' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-semibold' : 'text-slate-500 dark:text-slate-400 font-normal'"
                    class="px-3 py-1 rounded text-xs transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>Tabel Pelanggan</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px]">{{ $users->count() }}</span>
            </button>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 4. TAB 1: TABEL DATA ODP                       -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'odp'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                Daftar ODP pada OLT {{ strtoupper($currentOlt->nama_olt ?? '') }}
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Total <span class="font-bold text-slate-800 dark:text-slate-200">{{ $odps->count() }}</span> Titik ODP
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-3.5 py-2.5 w-10 text-center">No</th>
                        <th class="px-3.5 py-2.5">Nama ODP</th>
                        <th class="px-3.5 py-2.5">Port PON Asal</th>
                        <th class="px-3.5 py-2.5 text-center">Kapasitas Port</th>
                        <th class="px-3.5 py-2.5 text-center">Total Pelanggan</th>
                        <th class="px-3.5 py-2.5">Utilisasi Port</th>
                        <th class="px-3.5 py-2.5">Koordinat GPS</th>
                        <th class="px-3.5 py-2.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($odps as $odpItem)
                        @php
                            $parentPon = $pons->firstWhere('id', $odpItem->pon_id);
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40 transition">
                            <td class="px-3.5 py-2.5 text-center text-slate-400 font-mono">{{ $loop->iteration }}</td>
                            
                            <!-- Nama ODP -->
                            <td class="px-3.5 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $odpItem->is_full ? 'bg-amber-500' : ($odpItem->user_count > 0 ? 'bg-emerald-500' : 'bg-slate-400') }}"></span>
                                    <span class="font-bold text-slate-900 dark:text-white uppercase">{{ $odpItem->nama_odp }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">#{{ $odpItem->id }}</span>
                                </div>
                            </td>

                            <!-- PON Asal -->
                            <td class="px-3.5 py-2.5">
                                @if($parentPon)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        {{ $parentPon->nama_pon }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">PON #{{ $odpItem->pon_id }}</span>
                                @endif
                            </td>

                            <!-- Kapasitas Port -->
                            <td class="px-3.5 py-2.5 text-center font-mono">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $odpItem->port_max ?? 8 }}</span> Port
                            </td>

                            <!-- Total Pelanggan -->
                            <td class="px-3.5 py-2.5 text-center font-mono">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $odpItem->is_full ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : ($odpItem->user_count > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400') }}">
                                    {{ $odpItem->user_count }} Users
                                </span>
                            </td>

                            <!-- Utilisasi Bar -->
                            <td class="px-3.5 py-2.5">
                                <div class="flex items-center gap-2 max-w-[140px]">
                                    <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $odpItem->is_full ? 'bg-amber-500' : 'bg-blue-600' }}"
                                             style="width: {{ $odpItem->utilization_percent }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-600 dark:text-slate-400">{{ $odpItem->utilization_percent }}%</span>
                                </div>
                            </td>

                            <!-- Koordinat GPS -->
                            <td class="px-3.5 py-2.5">
                                @if(!empty($odpItem->latitude) && !empty($odpItem->longitude))
                                    <a href="https://maps.google.com/?q={{ $odpItem->latitude }},{{ $odpItem->longitude }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-[11px] font-mono text-blue-600 dark:text-blue-400 hover:underline">
                                        <span>{{ round($odpItem->latitude, 4) }}, {{ round($odpItem->longitude, 4) }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Tidak ada</span>
                                @endif
                            </td>

                            <!-- Aksi Detail Button -->
                            <td class="px-3.5 py-2.5 text-center">
                                <button type="button" 
                                        @click="openDetailOdp({{ json_encode($odpItem) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950 dark:hover:bg-blue-900 dark:text-blue-300 font-semibold text-xs transition">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Detail</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3.5 py-6 text-center text-slate-400 italic">
                                Belum ada data ODP pada OLT ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 5. TAB 2: TABEL DATA PON                       -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'pon'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                Daftar Port PON pada OLT {{ strtoupper($currentOlt->nama_olt ?? '') }}
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Total <span class="font-bold text-slate-800 dark:text-slate-200">{{ $pons->count() }}</span> Port PON
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-3.5 py-2.5 w-10 text-center">No</th>
                        <th class="px-3.5 py-2.5">Nama Port PON</th>
                        <th class="px-3.5 py-2.5">PON ID</th>
                        <th class="px-3.5 py-2.5 text-center">Maks Port</th>
                        <th class="px-3.5 py-2.5 text-center">Total ODP Terhubung</th>
                        <th class="px-3.5 py-2.5 text-center">Total Pelanggan</th>
                        <th class="px-3.5 py-2.5">Utilisasi Port</th>
                        <th class="px-3.5 py-2.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($pons as $ponItem)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40 transition">
                            <td class="px-3.5 py-2.5 text-center text-slate-400 font-mono">{{ $loop->iteration }}</td>
                            <td class="px-3.5 py-2.5">
                                <span class="font-bold text-slate-900 dark:text-white uppercase">{{ $ponItem->nama_pon }}</span>
                            </td>
                            <td class="px-3.5 py-2.5 font-mono text-slate-500">
                                #{{ $ponItem->id }}
                            </td>
                            <td class="px-3.5 py-2.5 text-center font-mono">
                                {{ $ponItem->port_max ?? 8 }}
                            </td>
                            <td class="px-3.5 py-2.5 text-center font-mono font-bold text-slate-800 dark:text-slate-200">
                                {{ $ponItem->odp_count }} ODP
                            </td>
                            <td class="px-3.5 py-2.5 text-center font-mono">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    {{ $ponItem->user_count }} Users
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5">
                                <div class="flex items-center gap-2 max-w-[140px]">
                                    <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $ponItem->utilization_percent >= 90 ? 'bg-rose-500' : 'bg-blue-600' }}"
                                             style="width: {{ $ponItem->utilization_percent }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-600 dark:text-slate-400">{{ $ponItem->utilization_percent }}%</span>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <button type="button" 
                                        @click="openDetailPon({{ json_encode($ponItem) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950 dark:hover:bg-blue-900 dark:text-blue-300 font-semibold text-xs transition">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Detail ODP</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3.5 py-6 text-center text-slate-400 italic">
                                Belum ada data PON pada OLT ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 6. TAB 3: TABEL DATA PELANGGAN (USERS)         -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'users'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                Daftar Pelanggan pada OLT {{ strtoupper($currentOlt->nama_olt ?? '') }}
            </h3>
            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                Total {{ $users->count() }} Pelanggan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-3.5 py-2.5 w-10 text-center">No</th>
                        <th class="px-3.5 py-2.5">Nama Pelanggan</th>
                        <th class="px-3.5 py-2.5">Nomor Internet</th>
                        <th class="px-3.5 py-2.5">ODP Asal</th>
                        <th class="px-3.5 py-2.5">Port PON</th>
                        <th class="px-3.5 py-2.5">OLT Label</th>
                        <th class="px-3.5 py-2.5">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($users as $userItem)
                        @php
                            $userOdp = $odps->firstWhere('id', $userItem->odp_id);
                            $userPon = $userOdp ? $pons->firstWhere('id', $userOdp->pon_id) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40 transition">
                            <td class="px-3.5 py-2 text-center text-slate-400 font-mono">{{ $loop->iteration }}</td>
                            <td class="px-3.5 py-2 font-semibold text-slate-900 dark:text-white">
                                {{ $userItem->nama_user }}
                            </td>
                            <td class="px-3.5 py-2 font-mono font-semibold text-blue-600 dark:text-blue-400">
                                {{ $userItem->nomor_internet }}
                            </td>
                            <td class="px-3.5 py-2">
                                @if($userOdp)
                                    <span class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ $userOdp->nama_odp }}
                                    </span>
                                @else
                                    <span class="text-slate-400">ODP #{{ $userItem->odp_id }}</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2 text-slate-700 dark:text-slate-300">
                                {{ $userPon->nama_pon ?? '-' }}
                            </td>
                            <td class="px-3.5 py-2 text-slate-600 dark:text-slate-400 font-mono">
                                {{ $userItem->olt ?? '-' }}
                            </td>
                            <td class="px-3.5 py-2 text-slate-500 dark:text-slate-400">
                                {{ $userItem->keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3.5 py-6 text-center text-slate-400 italic">
                                Tidak ada data user yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 7. MODAL DETAIL ODP & DAFTAR PELANGGAN         -->
    <!-- ============================================== -->
    <div x-show="showOdpModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showOdpModal = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl shadow-xl overflow-hidden"
             @click.outside="showOdpModal = false">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight" x-text="'Detail ODP: ' + (modalOdp?.nama_odp || '')"></h3>
                        <span class="px-2 py-0.2 rounded text-[10px] font-mono text-slate-500 bg-slate-200 dark:bg-slate-800" x-text="'ID #' + (modalOdp?.id || '')"></span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Kapasitas Port: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(modalOdpUsers.length || 0) + ' / ' + (modalOdp?.port_max || 8) + ' Port'"></span>
                    </p>
                </div>
                <button type="button" @click="showOdpModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none p-1">
                    &times;
                </button>
            </div>

            <!-- Modal Body: Table Users -->
            <div class="p-5 max-h-[60vh] overflow-y-auto space-y-3">
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-semibold">Daftar Pelanggan Terpasang:</span>
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-bold" x-text="modalOdpUsers.length + ' Pelanggan'"></span>
                </div>

                <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-3 py-2 w-10 text-center">No</th>
                                <th class="px-3 py-2">Nama Pelanggan</th>
                                <th class="px-3 py-2">Nomor Internet</th>
                                <th class="px-3 py-2">OLT</th>
                                <th class="px-3 py-2">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(user, index) in modalOdpUsers" :key="user.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40">
                                    <td class="px-3 py-2 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                    <td class="px-3 py-2 font-semibold text-slate-900 dark:text-white" x-text="user.nama_user"></td>
                                    <td class="px-3 py-2 font-mono font-semibold text-blue-600 dark:text-blue-400" x-text="user.nomor_internet"></td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-slate-400 font-mono text-[11px]" x-text="user.olt || '-'"></td>
                                    <td class="px-3 py-2 text-slate-500 italic text-[11px]" x-text="user.keterangan || '-'"></td>
                                </tr>
                            </template>
                            <tr x-show="modalOdpUsers.length === 0">
                                <td colspan="5" class="px-3 py-6 text-center text-slate-400 italic">
                                    Belum ada pelanggan terpasang pada ODP ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200 dark:border-slate-800 text-right">
                <button type="button" @click="showOdpModal = false" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-semibold transition">
                    Tutup
                </button>
            </div>

        </div>
    </div>

    <!-- ============================================== -->
    <!-- 8. MODAL DETAIL PON & DAFTAR ODP               -->
    <!-- ============================================== -->
    <div x-show="showPonModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showPonModal = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl shadow-xl overflow-hidden"
             @click.outside="showPonModal = false">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight" x-text="'Detail PON: ' + (modalPon?.nama_pon || '')"></h3>
                        <span class="px-2 py-0.2 rounded text-[10px] font-mono text-slate-500 bg-slate-200 dark:bg-slate-800" x-text="'ID #' + (modalPon?.id || '')"></span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Total ODP: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="modalPonOdps.length + ' ODP Terdaftar'"></span>
                    </p>
                </div>
                <button type="button" @click="showPonModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none p-1">
                    &times;
                </button>
            </div>

            <!-- Modal Body: Table ODPs inside this PON -->
            <div class="p-5 max-h-[60vh] overflow-y-auto space-y-3">
                <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-3 py-2 w-10 text-center">No</th>
                                <th class="px-3 py-2">Nama ODP</th>
                                <th class="px-3 py-2 text-center">Kapasitas</th>
                                <th class="px-3 py-2 text-center">Pelanggan</th>
                                <th class="px-3 py-2 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(odp, index) in modalPonOdps" :key="odp.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-950/40">
                                    <td class="px-3 py-2 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                    <td class="px-3 py-2 font-bold text-slate-900 dark:text-white uppercase" x-text="odp.nama_odp"></td>
                                    <td class="px-3 py-2 text-center font-mono" x-text="(odp.port_max || 8) + ' Port'"></td>
                                    <td class="px-3 py-2 text-center font-mono font-bold text-emerald-600" x-text="(odp.user_count || 0) + ' Users'"></td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" 
                                                @click="showPonModal = false; openDetailOdp(odp)"
                                                class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-semibold text-[11px] transition">
                                            Lihat User
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="modalPonOdps.length === 0">
                                <td colspan="5" class="px-3 py-6 text-center text-slate-400 italic">
                                    Belum ada ODP terdaftar pada PON ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200 dark:border-slate-800 text-right">
                <button type="button" @click="showPonModal = false" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-semibold transition">
                    Tutup
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
