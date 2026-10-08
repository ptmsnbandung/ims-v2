@extends('layouts.app')

@section('title', 'Monitoring & Hierarki OLT')

@section('content')
<div class="space-y-5" x-data="{ 
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
    <!-- 1. HEADER SECTION                              -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-600 dark:bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
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
                            Active
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Hierarki FTTH: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $pons->count() }} Port PON</span> &rarr; <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $odps->count() }} ODP</span> &rarr; <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $users->count() }} Pelanggan</span>
                    </p>
                </div>
            </div>

            <!-- Right Info Source -->
            <div class="text-right">
                <span class="inline-block px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[11px] font-mono font-medium text-slate-600 dark:text-slate-400">
                    Source: gomsn.pon{{ $selectedOltId }} / odp{{ $selectedOltId }} / users{{ $selectedOltId }}
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

        <!-- Card 2: Total ODP (Box Icon, bukan awan) -->
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
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">Users Terhubung</p>
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
    <!-- 3. SEARCH BAR & TAB SWITCHER                   -->
    <!-- ============================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        
        <!-- Search Input -->
        <form method="GET" action="{{ route('noc.network-olt', ['olt_id' => $selectedOltId]) }}" class="flex items-center gap-2 flex-1 max-w-md">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari user, nomor internet, atau catatan..." 
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

        <!-- Tab & Expand/Collapse Controls -->
        <div class="flex items-center gap-2">
            <div x-show="activeTab === 'hierarchy'" class="flex items-center gap-1">
                <button type="button" 
                        @click="expandAll()"
                        class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-medium text-slate-700 dark:text-slate-300 transition">
                    + Buka Semua
                </button>
                <button type="button" 
                        @click="collapseAll()"
                        class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-medium text-slate-700 dark:text-slate-300 transition">
                    - Tutup Semua
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="inline-flex p-0.5 bg-slate-100 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800">
                <button type="button" 
                        @click="activeTab = 'hierarchy'"
                        :class="activeTab === 'hierarchy' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-semibold' : 'text-slate-500 dark:text-slate-400 font-normal'"
                        class="px-3 py-1 rounded text-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    <span>Pohon Hierarki (PON & ODP)</span>
                </button>
                <button type="button" 
                        @click="activeTab = 'table'"
                        :class="activeTab === 'table' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-semibold' : 'text-slate-500 dark:text-slate-400 font-normal'"
                        class="px-3 py-1 rounded text-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                    <span>Daftar User (Tabel)</span>
                    <span class="px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px]">{{ $users->count() }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 4. TAB 1: POHON HIERARKI (PON -> ODP -> USERS) -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'hierarchy'" class="space-y-3">
        @if($pons->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada data PON pada OLT ini.</p>
            </div>
        @else
            @foreach($pons as $pon)
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition">
                    
                    <!-- PON Header Row -->
                    <div @click="togglePon({{ $pon->id }})" 
                         class="px-4 py-3 bg-slate-50/50 hover:bg-slate-100/60 dark:bg-slate-950/30 dark:hover:bg-slate-950/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/80 dark:border-slate-800/80 select-none">
                        
                        <!-- Left PON Info -->
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-md bg-blue-600 text-white flex items-center justify-center shrink-0 font-bold text-[11px]">
                                P{{ $loop->iteration }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                                        {{ $pon->nama_pon }}
                                    </h3>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                                        PON ID #{{ $pon->id }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Maks Port PON: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $pon->port_max ?? 8 }}</span> &bull; 
                                    Total ODP: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $pon->odp_count }} ODP</span> &bull; 
                                    Total Users: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $pon->user_count }} Users</span>
                                </p>
                            </div>
                        </div>

                        <!-- Right Utilization & Expand -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden hidden sm:block">
                                    <div class="h-full rounded-full {{ $pon->utilization_percent >= 90 ? 'bg-rose-500' : 'bg-blue-600' }}"
                                         style="width: {{ $pon->utilization_percent }}%"></div>
                                </div>
                                <span class="text-[11px] font-medium text-slate-600 dark:text-slate-300 font-mono">
                                    {{ $pon->user_count }}/{{ $pon->total_capacity }} Port ({{ $pon->utilization_percent }}%)
                                </span>
                            </div>

                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                 :class="openPons[{{ $pon->id }}] ? 'rotate-180 text-blue-600' : ''"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- PON Body: List ODP -->
                    <div x-show="openPons[{{ $pon->id }}]" x-collapse class="p-3 bg-slate-50/30 dark:bg-slate-950/40 space-y-2.5">
                        @if($pon->odps->isEmpty())
                            <p class="text-xs text-slate-400 italic py-2 text-center">Belum ada ODP terdaftar pada port PON ini.</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2.5">
                                @foreach($pon->odps as $odp)
                                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 shadow-xs">
                                        
                                        <!-- ODP Header -->
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $odp->is_full ? 'bg-amber-500' : ($odp->user_count > 0 ? 'bg-emerald-500' : 'bg-slate-400') }}"></span>
                                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase truncate max-w-[170px]" title="{{ $odp->nama_odp }}">
                                                        {{ $odp->nama_odp }}
                                                    </h4>
                                                </div>
                                                <p class="text-[10px] text-slate-400 mt-0.5">
                                                    ODP ID #{{ $odp->id }} &bull; Max: {{ $odp->port_max ?? 8 }} Port
                                                </p>
                                            </div>

                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $odp->is_full ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : ($odp->user_count > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400') }}">
                                                {{ $odp->user_count }}/{{ $odp->port_max ?? 8 }}
                                            </span>
                                        </div>

                                        <!-- ODP Progress -->
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1 rounded-full overflow-hidden mt-2">
                                            <div class="h-full rounded-full {{ $odp->is_full ? 'bg-amber-500' : 'bg-blue-600' }}"
                                                 style="width: {{ $odp->utilization_percent }}%"></div>
                                        </div>

                                        <!-- ODP Action / Toggle -->
                                        <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                                            @if(!empty($odp->latitude) && !empty($odp->longitude))
                                                <a href="https://maps.google.com/?q={{ $odp->latitude }},{{ $odp->longitude }}" 
                                                   target="_blank" 
                                                   class="inline-flex items-center gap-1 text-[10px] text-blue-600 hover:underline dark:text-blue-400">
                                                    <span>{{ round($odp->latitude, 4) }}, {{ round($odp->longitude, 4) }}</span>
                                                </a>
                                            @else
                                                <span class="text-[10px] text-slate-400 italic">No GPS</span>
                                            @endif

                                            <button type="button" 
                                                    @click="toggleOdp({{ $odp->id }})"
                                                    class="inline-flex items-center gap-1 font-medium text-[10px] text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                                <span x-text="openOdps[{{ $odp->id }}] ? 'Tutup User' : 'User ({{ $odp->user_count }})'"></span>
                                                <svg class="w-3 h-3 transition-transform" :class="openOdps[{{ $odp->id }}] ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Users in ODP -->
                                        <div x-show="openOdps[{{ $odp->id }}]" x-collapse class="mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 space-y-1 max-h-40 overflow-y-auto pr-1">
                                            @forelse($odp->users as $u)
                                                <div class="flex items-center justify-between p-1 rounded bg-slate-50 dark:bg-slate-950 text-[10px]">
                                                    <div class="truncate max-w-[140px]">
                                                        <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $u->nama_user }}</p>
                                                        <p class="font-mono text-blue-600 dark:text-blue-400">{{ $u->nomor_internet }}</p>
                                                    </div>
                                                    <div class="text-right shrink-0">
                                                        <span class="px-1 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[9px]">
                                                            {{ $u->olt ?? 'OLT' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-[10px] text-slate-400 italic py-1 text-center">Belum ada pelanggan terpasang.</p>
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
    <div x-show="activeTab === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
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

</div>
@endsection
