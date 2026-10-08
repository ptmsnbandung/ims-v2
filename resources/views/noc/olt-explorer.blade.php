@extends('layouts.app')

@section('title', 'Monitoring & Hierarki OLT')

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: 'hierarchy', 
    openPons: {}, 
    openOdps: {},
    togglePon(id) { this.openPons[id] = !this.openPons[id]; },
    toggleOdp(id) { this.openOdps[id] = !this.openOdps[id]; },
    expandAll() {
        @foreach($pons as $p)
            this.openPons[{{ $p->id }}] = true;
            @foreach($p->odps as $o)
                this.openOdps[{{ $o->id }}] = true;
            @endforeach
        @endforeach
    },
    collapseAll() {
        this.openPons = {};
        this.openOdps = {};
    }
}">

    <!-- ============================================== -->
    <!-- 1. HEADER & OLT SELECTOR DROPDOWN             -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-cyan-500/10 dark:bg-cyan-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Left Title -->
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-600 flex items-center justify-center text-white shadow-md shadow-cyan-500/20 shrink-0">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a2.25 2.25 0 0 1 1.8-.85h8.9a2.25 2.25 0 0 1 1.8.85l2.1 3.15a4.5 4.5 0 0 1 .9 2.7" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Monitoring & Hierarki Jaringan OLT</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border border-cyan-500/20">
                            FTTH Topology
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Struktur data pohon hierarki: <span class="text-slate-700 dark:text-slate-200 font-semibold">Master OLT</span> &rarr; <span class="text-cyan-600 dark:text-cyan-400 font-semibold">Port PON</span> &rarr; <span class="text-blue-600 dark:text-blue-400 font-semibold">ODP</span> &rarr; <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Pelanggan (Users)</span>
                    </p>
                </div>
            </div>

            <!-- Right: OLT Dropdown Selector -->
            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" action="{{ route('noc.olt-explorer') }}" id="oltSelectForm" class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        Pilih OLT:
                    </label>
                    <div class="relative min-w-[200px]">
                        <select name="olt_id" 
                                onchange="document.getElementById('oltSelectForm').submit()"
                                class="w-full appearance-none bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 pr-9 text-xs font-bold text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 shadow-xs cursor-pointer">
                            @forelse($olts as $o)
                                <option value="{{ $o->olt_id }}" {{ (string)$selectedOltId === (string)$o->olt_id ? 'selected' : '' }}>
                                    OLT {{ $o->olt_id }} &mdash; {{ strtoupper($o->nama_olt) }}
                                </option>
                            @empty
                                <option value="">Tidak ada OLT di Master</option>
                            @endforelse
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>
                </form>

                @if($currentOlt)
                    <div class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px]">{{ $currentOlt->nama_olt }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. STATISTIC CARDS (KPIs)                      -->
    <!-- ============================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total PON -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Port PON</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($stats['total_pon']) }}</h3>
                <p class="text-[10px] text-cyan-600 dark:text-cyan-400 mt-0.5 font-medium">Port Utama Terkonfigurasi</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Total ODP -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total ODP</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($stats['total_odp']) }}</h3>
                <p class="text-[10px] text-blue-600 dark:text-blue-400 mt-0.5 font-medium">
                    {{ $stats['full_odp_count'] > 0 ? $stats['full_odp_count'] . ' ODP Penuh' : 'Titik Distribusi' }}
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Total Users / Pelanggan -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pelanggan</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($stats['total_users']) }}</h3>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">Users Aktif Terhubung</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Port Utilization -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Utilisasi ODP Port</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['utilization_percent'] }}%</h3>
                </div>
                <div class="text-right text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ number_format($stats['total_used']) }}</span> / {{ number_format($stats['total_capacity']) }} Port
                </div>
            </div>
            <!-- Progress bar -->
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden mt-3">
                <div class="h-full rounded-full transition-all duration-500 {{ $stats['utilization_percent'] >= 90 ? 'bg-rose-500' : ($stats['utilization_percent'] >= 75 ? 'bg-amber-500' : 'bg-gradient-to-r from-cyan-500 to-emerald-500') }}"
                     style="width: {{ $stats['utilization_percent'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 3. SEARCH, FILTER & TAB SWITCHER               -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search input -->
        <form method="GET" action="{{ route('noc.olt-explorer') }}" class="relative flex-1 max-w-md">
            <input type="hidden" name="olt_id" value="{{ $selectedOltId }}">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <input type="text" 
                   name="search" 
                   value="{{ $search }}" 
                   placeholder="Cari nama user, nomor internet, catatan..." 
                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-20 py-2 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition">
            @if($search)
                <a href="{{ route('noc.olt-explorer', ['olt_id' => $selectedOltId]) }}" class="absolute inset-y-0 right-10 flex items-center px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                    &times;
                </a>
            @endif
            <button type="submit" class="absolute inset-y-1 right-1 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold transition flex items-center gap-1">
                Cari
            </button>
        </form>

        <!-- Right Controls: Expand/Collapse & Tab Switcher -->
        <div class="flex flex-wrap items-center gap-2">
            <div x-show="activeTab === 'hierarchy'" class="flex items-center gap-1.5 mr-2">
                <button type="button" 
                        @click="expandAll()"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-semibold text-slate-700 dark:text-slate-300 transition">
                    + Buka Semua
                </button>
                <button type="button" 
                        @click="collapseAll()"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-semibold text-slate-700 dark:text-slate-300 transition">
                    - Tutup Semua
                </button>
            </div>

            <!-- Tab Buttons -->
            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
                <button type="button" 
                        @click="activeTab = 'hierarchy'"
                        :class="activeTab === 'hierarchy' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    <span>Pohon Hierarki (PON & ODP)</span>
                </button>
                <button type="button" 
                        @click="activeTab = 'table'"
                        :class="activeTab === 'table' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                    <span>Daftar User (Tabel)</span>
                    <span class="px-1.5 py-0.2 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-bold text-[10px]">{{ $users->count() }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 4. TAB 1: POHON HIERARKI (PON -> ODP -> USERS) -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'hierarchy'" class="space-y-4">
        @if($pons->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak ada data PON pada OLT ini</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Pastikan tabel database <code class="font-mono text-cyan-500">gomsn.pon{{ $selectedOltId }}</code> sudah terisi data port PON.
                </p>
            </div>
        @else
            @foreach($pons as $pon)
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden transition">
                    
                    <!-- PON Header (Click to Expand) -->
                    <div @click="togglePon({{ $pon->id }})" 
                         class="p-4 bg-slate-50/70 hover:bg-slate-100/80 dark:bg-slate-950/40 dark:hover:bg-slate-950/80 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800/80">
                        
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-600 text-white flex items-center justify-center shrink-0 font-bold text-xs shadow-xs">
                                P{{ $loop->iteration }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                                        {{ $pon->nama_pon }}
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                        PON ID #{{ $pon->id }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Maks Port PON: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $pon->port_max ?? 8 }}</span> &bull; 
                                    Total ODP: <span class="font-bold text-blue-600 dark:text-blue-400">{{ $pon->odp_count }} ODP</span> &bull; 
                                    Total Users: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $pon->user_count }} Users</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <!-- Utilization badge -->
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden hidden sm:block">
                                    <div class="h-full rounded-full {{ $pon->utilization_percent >= 90 ? 'bg-rose-500' : ($pon->utilization_percent >= 75 ? 'bg-amber-500' : 'bg-cyan-500') }}"
                                         style="width: {{ $pon->utilization_percent }}%"></div>
                                </div>
                                <span class="text-xs font-bold {{ $pon->utilization_percent >= 90 ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $pon->user_count }}/{{ $pon->total_capacity }} Port ({{ $pon->utilization_percent }}%)
                                </span>
                            </div>

                            <!-- Expand arrow icon -->
                            <div class="w-7 h-7 rounded-lg bg-slate-200/60 dark:bg-slate-800 flex items-center justify-center text-slate-500 transition-transform duration-200"
                                 :class="openPons[{{ $pon->id }}] ? 'rotate-180 text-cyan-500' : ''">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- PON Body: List of ODPs inside this PON -->
                    <div x-show="openPons[{{ $pon->id }}]" x-collapse class="p-4 bg-slate-100/40 dark:bg-slate-900/60 space-y-3">
                        @if($pon->odps->isEmpty())
                            <div class="p-4 text-center text-xs text-slate-400 dark:text-slate-500 italic bg-white dark:bg-slate-900/80 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                                Belum ada ODP yang terdaftar di bawah {{ $pon->nama_pon }}.
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                @foreach($pon->odps as $odp)
                                    <div class="bg-white dark:bg-slate-900 border {{ $odp->is_full ? 'border-amber-400/60 dark:border-amber-500/40' : 'border-slate-200 dark:border-slate-800' }} rounded-xl p-3.5 shadow-xs transition hover:shadow-md">
                                        
                                        <!-- ODP Header -->
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full {{ $odp->is_full ? 'bg-amber-500' : ($odp->user_count > 0 ? 'bg-emerald-500' : 'bg-slate-400') }}"></span>
                                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase truncate max-w-[180px]" title="{{ $odp->nama_odp }}">
                                                        {{ $odp->nama_odp }}
                                                    </h4>
                                                </div>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                    ODP ID #{{ $odp->id }} &bull; Max: {{ $odp->port_max ?? 8 }} Port
                                                </p>
                                            </div>

                                            <!-- ODP Badge Utilization -->
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold shrink-0 {{ $odp->is_full ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : ($odp->user_count > 0 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400') }}">
                                                {{ $odp->user_count }}/{{ $odp->port_max ?? 8 }} Port
                                            </span>
                                        </div>

                                        <!-- ODP Progress Bar -->
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden mt-2.5">
                                            <div class="h-full rounded-full {{ $odp->is_full ? 'bg-amber-500' : 'bg-gradient-to-r from-cyan-500 to-emerald-500' }}"
                                                 style="width: {{ $odp->utilization_percent }}%"></div>
                                        </div>

                                        <!-- ODP Footer: Lat/Lng & Toggle Users -->
                                        <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px]">
                                            @if(!empty($odp->latitude) && !empty($odp->longitude))
                                                <a href="https://maps.google.com/?q={{ $odp->latitude }},{{ $odp->longitude }}" 
                                                   target="_blank" 
                                                   class="inline-flex items-center gap-1 text-[10px] font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 hover:underline">
                                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                    </svg>
                                                    <span>{{ round($odp->latitude, 4) }}, {{ round($odp->longitude, 4) }}</span>
                                                </a>
                                            @else
                                                <span class="text-[10px] text-slate-400 italic">No GPS</span>
                                            @endif

                                            <button type="button" 
                                                    @click="toggleOdp({{ $odp->id }})"
                                                    class="inline-flex items-center gap-1 font-bold text-[10px] text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                                <span x-text="openOdps[{{ $odp->id }}] ? 'Sembunyikan User' : 'Lihat User ({{ $odp->user_count }})'"></span>
                                                <svg class="w-3 h-3 transition-transform" :class="openOdps[{{ $odp->id }}] ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Nested List of Users under this ODP -->
                                        <div x-show="openOdps[{{ $odp->id }}]" x-collapse class="mt-2.5 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                            @forelse($odp->users as $u)
                                                <div class="flex items-center justify-between p-1.5 rounded-lg bg-slate-50 dark:bg-slate-950/80 border border-slate-200/60 dark:border-slate-800/60 text-[11px]">
                                                    <div class="truncate max-w-[150px]">
                                                        <p class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ $u->nama_user }}</p>
                                                        <p class="font-mono text-[10px] text-cyan-600 dark:text-cyan-400">{{ $u->nomor_internet }}</p>
                                                    </div>
                                                    <div class="text-right shrink-0">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                            {{ $u->olt ?? 'OLT' }}
                                                        </span>
                                                        @if(!empty($u->keterangan))
                                                            <p class="text-[9px] text-slate-400 italic truncate max-w-[80px]" title="{{ $u->keterangan }}">{{ $u->keterangan }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-[10px] text-slate-400 italic py-1 text-center">Belum ada pelanggan terpasang di ODP ini.</p>
                                            @endforelse
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            @endforeach
        @endif
    </div>

    <!-- ============================================== -->
    <!-- 5. TAB 2: FLAT TABLE VIEW OF USERS             -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>Daftar Pelanggan pada OLT {{ strtoupper($currentOlt->nama_olt ?? '') }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                    {{ $users->count() }} Users
                </span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Nama Pelanggan / User</th>
                        <th class="px-4 py-3">Nomor Internet</th>
                        <th class="px-4 py-3">ODP Asal</th>
                        <th class="px-4 py-3">Port PON Asal</th>
                        <th class="px-4 py-3">OLT Label</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($users as $userItem)
                        @php
                            $userOdp = $odps->firstWhere('id', $userItem->odp_id);
                            $userPon = $userOdp ? $pons->firstWhere('id', $userOdp->pon_id) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-950/40 transition">
                            <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                                {{ $userItem->nama_user }}
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                {{ $userItem->nomor_internet }}
                            </td>
                            <td class="px-4 py-3">
                                @if($userOdp)
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                                        {{ $userOdp->nama_odp }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">ODP #{{ $userItem->odp_id }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($userPon)
                                    <span class="font-medium text-slate-700 dark:text-slate-300">
                                        {{ $userPon->nama_pon }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-600 dark:text-slate-300">
                                {{ $userItem->olt ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 italic">
                                {{ $userItem->keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">
                                Tidak ada data user yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
