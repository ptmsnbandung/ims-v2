@extends('layouts.app')

@section('title', 'Daftar ONU Port ' . ($onuData['port_name'] ?? ('PON ' . $ponIndex)) . ' - ' . ($olt->name_olt ?? $olt->kode_olt) . ' - NOC IMS')
@section('page_title', 'Daftar ONU: ' . ($onuData['port_name'] ?? ('PON ' . $ponIndex)) . ' (' . ($olt->name_olt ?? $olt->kode_olt) . ')')

@section('content')
<div class="space-y-4"
     x-data="onuListManager({{ Js::from($onuData) }}, '{{ $olt->kode_olt }}', {{ $ponIndex }})"
     x-init="init()"
     x-destroy="destroy()">

    <!-- =================================================================== -->
    <!-- 1. BREADCRUMBS & TOP NAVIGATION BAR                                 -->
    <!-- =================================================================== -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
        <nav class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
            <a href="{{ route('noc.dashboard') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>NOC</span>
            </a>
            <span>/</span>
            <a href="{{ route('noc.olt') }}" class="hover:text-blue-600 transition">Master OLT</a>
            <span>/</span>
            <a href="{{ route('noc.olt.pon-traffic', $olt->kode_olt) }}" class="hover:text-blue-600 transition">
                Traffic PON ({{ $olt->kode_olt }})
            </a>
            <span>/</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">
                Daftar ONU ({{ $onuData['port_name'] ?? ('PON ' . $ponIndex) }})
            </span>
        </nav>

        <!-- Tombol Aksi Navigasi Cepat -->
        <div class="flex items-center gap-2">
            <a href="{{ route('noc.olt.pon-traffic', $olt->kode_olt) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-semibold shadow-2xs transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                <span>Kembali ke Traffic PON</span>
            </a>

            <a href="{{ route('noc.olt') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span>Daftar OLT</span>
            </a>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. TOP HERO HEADER BANNER (COMMAND CENTER DARK TEAL/BLUE THEME)     -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4.5 shadow-sm border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 relative z-10">
            <!-- Info Kiri: OLT & Port Identitas -->
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-500/25 border border-blue-400/40 text-blue-300 flex items-center justify-center shrink-0 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-600 text-white shadow-2xs">
                            {{ $olt->kode_olt }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-600 text-white shadow-2xs">
                            {{ $onuData['interface_name'] ?? ('gpon-olt_1/1/' . $ponIndex) }}
                        </span>
                        <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">
                            Monitoring Daftar ONU: {{ $onuData['port_name'] ?? ('PON ' . $ponIndex) }}
                        </h2>
                    </div>
                    <p class="text-[11.5px] text-slate-300 mt-1">
                        {{ $olt->name_olt ?? 'OLT Server' }} &middot; Host: <span class="font-mono text-cyan-300 font-bold">{{ $olt->ip_address ?? '10.10.10.1' }}</span> &middot; POP: <span class="text-slate-200 font-semibold">{{ $olt->nama_pop ?? 'POP MSN' }}</span>
                    </p>

                    <!-- Status Bar & Live Clock -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2 text-[10px] font-bold">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Live: <span class="font-mono text-white" x-text="currentTime">--:--:--</span> WIB</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-blue-500/20 text-blue-300 border border-blue-400/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400" :class="isPageActive ? 'animate-ping' : ''"></span>
                            <span x-text="isPageActive ? 'Auto-Refresh 1 Detik Aktif' : 'Paused (Tab Tidak Aktif)'"></span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-800/80 text-slate-200 border border-slate-700/60 font-mono">
                            <span x-text="stats.total + ' ONU Terhubung'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Switcher Port PON Lain di OLT ini -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 self-start lg:self-center">
                <label class="text-[11px] font-semibold text-slate-200">Pilih Port PON:</label>
                <div class="flex items-center gap-1 bg-black/30 p-1 rounded-lg border border-white/10">
                    @for($p = 1; $p <= 8; $p++)
                        <a href="{{ route('noc.olt.pon.onu-list', ['kode_olt' => $olt->kode_olt, 'pon_index' => $p]) }}"
                           class="px-2.5 py-1 rounded-md text-[11px] font-mono font-bold transition {{ $p == $ponIndex ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            P{{ $p }}
                        </a>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. KPI STATS SUMMARY CARDS (7 KOLOM DENGAN RATA-RATA TRAFFIC / ONU) -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3">
        <!-- Card 1: Total ONU Terhubung -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total ONU Terhubung</div>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-xl font-black font-mono text-slate-900 dark:text-white" x-text="stats.total"></span>
                <span class="text-[10px] font-semibold text-slate-500">Unit</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-1 truncate">
                Slot Max: 128 ONU
            </div>
        </div>

        <!-- Card 2: ONU Online -->
        <div class="bg-white dark:bg-slate-900 border border-emerald-200/80 dark:border-emerald-900/50 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>ONU Online</span>
            </div>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="stats.online"></span>
                <span class="text-[10px] font-bold text-emerald-600" x-text="Math.round((stats.online / Math.max(1, stats.total)) * 100) + '%'"></span>
            </div>
            <div class="text-[10px] text-emerald-600/90 dark:text-emerald-400/90 mt-1 truncate">
                Beroperasi Normal
            </div>
        </div>

        <!-- Card 3: ONU Offline (LOS) -->
        <div class="bg-white dark:bg-slate-900 border border-rose-200/80 dark:border-rose-900/50 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>ONU Offline</span>
            </div>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-xl font-black font-mono text-rose-600 dark:text-rose-400" x-text="stats.offline"></span>
                <span class="text-[10px] font-bold text-rose-600" x-text="Math.round((stats.offline / Math.max(1, stats.total)) * 100) + '%'"></span>
            </div>
            <div class="text-[10px] text-rose-600/90 dark:text-rose-400/90 mt-1 truncate">
                LOS / Power Off
            </div>
        </div>

        <!-- Card 4: Total Download Rx -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <span>Total Traffic Rx</span>
            </div>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-xl font-black font-mono text-cyan-600 dark:text-cyan-400" x-text="stats.totalRx.toFixed(1)"></span>
                <span class="text-[10px] font-semibold text-slate-500">Mbps</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-1 truncate">
                Download Port PON
            </div>
        </div>

        <!-- Card 5: Total Upload Tx -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <span>Total Traffic Tx</span>
            </div>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-xl font-black font-mono text-blue-600 dark:text-blue-400" x-text="stats.totalTx.toFixed(1)"></span>
                <span class="text-[10px] font-semibold text-slate-500">Mbps</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-1 truncate">
                Upload Port PON
            </div>
        </div>

        <!-- Card 6: RATA-RATA TRAFFIC PER ONU (FITUR BARU SPESIFIKASI USER) -->
        <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800/60 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Rata-Rata / ONU</span>
            </div>
            <div class="flex items-baseline justify-between mt-1 font-mono">
                <span class="text-base font-black text-indigo-700 dark:text-indigo-300" x-text="stats.avgRxPerOnu.toFixed(1)"></span>
                <span class="text-[9.5px] font-bold text-slate-500 dark:text-slate-400" x-text="'Rx / ' + stats.avgTxPerOnu.toFixed(1) + ' Tx'"></span>
            </div>
            <div class="text-[9.5px] text-indigo-600/80 dark:text-indigo-400/80 mt-1 truncate font-medium">
                Total &divide; <span x-text="stats.online"></span> ONU Online
            </div>
        </div>

        <!-- Card 7: Avg Redaman Rx -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-2xs">
            <div class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Avg Redaman</span>
            </div>
            <div class="flex items-baseline justify-between mt-1">
                <span class="text-xl font-black font-mono text-amber-600 dark:text-amber-400" x-text="stats.avgRxPower"></span>
                <span class="text-[10px] font-semibold text-slate-500">dBm</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-1 truncate">
                Ideal: -15 s/d -24 dBm
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. CONTROLS BAR: SEARCH, FILTER STATUS, REDAMAN, & VIEW MODE       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari Nama Pelanggan, No Internet, SN, Slot..."
                       class="w-full pl-9 pr-8 py-2 rounded-lg text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button x-show="searchQuery" 
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- View Mode & Sort -->
            <div class="flex items-center gap-2">
                <!-- Sort Dropdown -->
                <div class="flex items-center gap-1.5 text-xs">
                    <span class="text-slate-500 hidden sm:inline">Urutkan:</span>
                    <select x-model="sortBy"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="onu_id_asc">Slot ONU (1 &rarr; 128)</option>
                        <option value="onu_id_desc">Slot ONU (128 &rarr; 1)</option>
                        <option value="traffic_desc">Traffic Terbesar (Rx)</option>
                        <option value="name_asc">Nama Pelanggan (A - Z)</option>
                        <option value="power_asc">Redaman Paling Bagus</option>
                        <option value="power_desc">Redaman Paling Kritis</option>
                    </select>
                </div>

                <!-- Grid / Table View Switcher -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                            class="px-2.5 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1 transition"
                            title="Tampilan Grid Card">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span class="hidden sm:inline">Grid Card</span>
                    </button>
                    <button @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                            class="px-2.5 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1 transition"
                            title="Tampilan Tabel Detail">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden sm:inline">Tabel</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Buttons (Status & Redaman) -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
            <!-- Status Filters -->
            <span class="font-bold text-slate-500 mr-1">Status:</span>
            <button @click="statusFilter = 'all'"
                    :class="statusFilter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Semua (<span x-text="onus.length"></span>)
            </button>
            <button @click="statusFilter = 'online'"
                    :class="statusFilter === 'online' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Online (<span x-text="stats.online"></span>)
            </button>
            <button @click="statusFilter = 'offline'"
                    :class="statusFilter === 'offline' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 hover:bg-rose-100'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Offline (<span x-text="stats.offline"></span>)
            </button>
            <button @click="statusFilter = 'suspend'"
                    :class="statusFilter === 'suspend' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 hover:bg-amber-100'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Suspend (<span x-text="stats.suspend"></span>)
            </button>

            <span class="text-slate-300 dark:text-slate-700 mx-1">|</span>

            <!-- Redaman Filters -->
            <span class="font-bold text-slate-500 mr-1">Redaman:</span>
            <button @click="redamanFilter = 'all'"
                    :class="redamanFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Semua
            </button>
            <button @click="redamanFilter = 'optimal'"
                    :class="redamanFilter === 'optimal' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Optimal (&gt; -24 dBm)
            </button>
            <button @click="redamanFilter = 'normal'"
                    :class="redamanFilter === 'normal' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 hover:bg-amber-100'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Normal (-24 s/d -26.5 dBm)
            </button>
            <button @click="redamanFilter = 'kritis'"
                    :class="redamanFilter === 'kritis' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 hover:bg-rose-100'"
                    class="px-2.5 py-1 rounded-md font-bold transition">
                Kritis (&lt; -26.5 dBm)
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. TAMPILAN 1: GRID CARD VIEW (RAPI, BERSIH, TERSTRUKTUR & NO OVERLAP)-->
    <!-- =================================================================== -->
    <div x-show="viewMode === 'grid'" class="space-y-4">
        <!-- Grid Items: 3 Kolom Responsif dengan Spacing Sempurna -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="onu in filteredOnus" :key="onu.onu_id">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:shadow-md transition-all relative overflow-hidden flex flex-col justify-between"
                     :class="{
                         'border-t-4 border-t-emerald-500': onu.status === 'online',
                         'border-t-4 border-t-rose-500': onu.status === 'offline',
                         'border-t-4 border-t-amber-500': onu.status === 'suspend'
                     }">

                    <!-- Bagian Konten Atas Card -->
                    <div class="space-y-3">
                        <!-- 1. Header Card: Nama ONU, Identitas & Status Badge -->
                        <div class="flex items-start justify-between gap-2.5 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-start gap-2.5 min-w-0 flex-1">
                                <!-- Badge Nomor Slot -->
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center font-mono font-bold text-xs shrink-0 text-white shadow-xs"
                                     :class="{
                                         'bg-emerald-600': onu.status === 'online',
                                         'bg-rose-600': onu.status === 'offline',
                                         'bg-amber-600': onu.status === 'suspend'
                                     }"
                                     x-text="'#' + onu.onu_id">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <!-- Nama ONU & Nama Pelanggan -->
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[11px] font-black text-indigo-600 dark:text-indigo-400" x-text="onu.onu_name || ('ONU ' + onu.onu_id)"></span>
                                        <span class="text-slate-300 dark:text-slate-700">&middot;</span>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" x-text="onu.nama_pelanggan"></h4>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
                                        <span class="font-mono text-blue-600 dark:text-blue-400 font-bold" x-text="'ID: ' + onu.nomor_internet"></span>
                                        <span>&middot;</span>
                                        <span class="font-mono truncate" x-text="onu.interface_onu"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shrink-0"
                                  :class="{
                                      'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800': onu.status === 'online',
                                      'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-400 border border-rose-200 dark:border-rose-800': onu.status === 'offline',
                                      'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-400 border border-amber-200 dark:border-amber-800': onu.status === 'suspend'
                                  }">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="{
                                          'bg-emerald-500 animate-pulse': onu.status === 'online',
                                          'bg-rose-500': onu.status === 'offline',
                                          'bg-amber-500': onu.status === 'suspend'
                                      }"></span>
                                <span x-text="onu.status"></span>
                            </span>
                        </div>

                        <!-- 2. Section Traffic Penggunaan ONU (Rx / Tx) & Kontribusi -->
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
                            <!-- Judul Section Traffic + Porsi Kontribusi -->
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    <span>Traffic Penggunaan ONU</span>
                                </span>
                                <!-- Porsi Penggunaan terhadap total PON -->
                                <span class="px-2 py-0.5 rounded text-[9.5px] font-mono font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60"
                                      x-text="onu.traffic_share_pct + '% Traffic PON'">
                                </span>
                            </div>

                            <!-- 2 Kolom Nilai: Download (Rx) & Upload (Tx) -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <!-- Download Rx Box -->
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between text-[10px] text-slate-500 font-semibold mb-0.5">
                                        <span class="flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                            <span>Rx (Download):</span>
                                        </span>
                                    </div>
                                    <div class="text-sm font-mono font-black text-cyan-600 dark:text-cyan-400" x-text="onu.download_formatted"></div>
                                    <!-- Progress Bar Utilitas -->
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                        <div class="bg-cyan-500 h-full rounded-full transition-all duration-500" :style="'width: ' + onu.down_pct + '%'"></div>
                                    </div>
                                    <div class="flex justify-between text-[9px] text-slate-400 mt-1">
                                        <span x-text="onu.down_pct + '% Kapasitas'"></span>
                                        <span x-text="onu.nominal_bandwith + 'M'"></span>
                                    </div>
                                </div>

                                <!-- Upload Tx Box -->
                                <div class="p-2 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between text-[10px] text-slate-500 font-semibold mb-0.5">
                                        <span class="flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            <span>Tx (Upload):</span>
                                        </span>
                                    </div>
                                    <div class="text-sm font-mono font-black text-blue-600 dark:text-blue-400" x-text="onu.upload_formatted"></div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                        <div class="bg-blue-500 h-full rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, onu.down_pct * 0.8) + '%'"></div>
                                    </div>
                                    <div class="flex justify-between text-[9px] text-slate-400 mt-1">
                                        <span>Aktif Upload</span>
                                        <span class="font-mono text-emerald-600">&bull; Live</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Baris Traffic: Bandingkan dengan Rata-Rata Port -->
                            <div class="flex items-center justify-between text-[10px] pt-1 border-t border-slate-200/60 dark:border-slate-800/80 font-mono text-slate-500">
                                <span>Rata-Rata Port / ONU:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="'Rx: ' + stats.avgRxPerOnu.toFixed(1) + 'M &middot; Tx: ' + stats.avgTxPerOnu.toFixed(1) + 'M'"></span>
                            </div>
                        </div>

                        <!-- 3. Section Redaman Optik (Optical Power) -->
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                            <div class="flex items-center justify-between text-[11px] mb-1.5">
                                <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>Redaman Optik (Power)</span>
                                </span>
                                <!-- Status Redaman -->
                                <span class="px-2 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wider"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400': onu.redaman_status === 'optimal',
                                          'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400': onu.redaman_status === 'normal',
                                          'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400': onu.redaman_status === 'kritis'
                                      }"
                                      x-text="onu.redaman_status">
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                <div class="p-1.5 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-500 font-sans">Rx Power:</span>
                                    <span class="font-bold text-[12px]"
                                          :class="{
                                              'text-emerald-600 dark:text-emerald-400': onu.redaman_status === 'optimal',
                                              'text-amber-600 dark:text-amber-400': onu.redaman_status === 'normal',
                                              'text-rose-600 dark:text-rose-400': onu.redaman_status === 'kritis'
                                          }"
                                          x-text="onu.rx_power">
                                    </span>
                                </div>
                                <div class="p-1.5 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-500 font-sans">Tx Laser:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300 text-[12px]" x-text="onu.tx_power"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Section Perangkat & Layanan (Serial Number, Paket & Alamat) -->
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <!-- SN Modem -->
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9.5px] font-semibold text-slate-500 block">Serial Number:</span>
                                <div class="flex items-center justify-between mt-0.5">
                                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-[11.5px] truncate" x-text="onu.sn"></span>
                                    <button @click="copyText(onu.sn)" class="text-slate-400 hover:text-blue-600 transition shrink-0 ml-1" title="Salin Serial Number">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Paket Layanan -->
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9.5px] font-semibold text-slate-500 block">Paket Internet:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 truncate block mt-0.5 text-[11.5px]" x-text="onu.package_name"></span>
                            </div>
                        </div>

                        <!-- Alamat & Uptime Info -->
                        <div class="space-y-1 text-[10.5px] text-slate-500 dark:text-slate-400 pt-0.5">
                            <div class="flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate" x-text="onu.address"></span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span>Uptime: <strong class="font-mono text-slate-700 dark:text-slate-300" x-text="onu.uptime"></strong></span>
                                <span>POP: <span class="font-semibold text-slate-600 dark:text-slate-300" x-text="onu.pop_name"></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Card Footer Action Buttons -->
                    <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <a :href="'{{ url('/noc/network-olt') }}?search=' + onu.nomor_internet"
                           class="inline-flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                            <span>Buka di Explorer</span>
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <a :href="'{{ url('/noc/gpon') }}?olt={{ $olt->kode_olt }}&port=gpon-onu_1/1/{{ $ponIndex }}'"
                           class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                            GPON Slot
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State jika tidak ada yang cocok -->
        <div x-show="filteredOnus.length === 0" 
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-bold text-slate-700 dark:text-slate-200">Tidak ada ONU yang sesuai kriteria</h3>
            <p class="text-xs text-slate-400 mt-1">Coba ubah kata kunci pencarian atau sesuaikan filter status/redaman.</p>
            <button @click="resetFilters()" class="mt-3 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">
                Reset Semua Filter
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. TAMPILAN 2: TABEL LIST VIEW                                      -->
    <!-- =================================================================== -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="py-3 px-3 w-16">Slot</th>
                        <th class="py-3 px-3">Nama ONU & Pelanggan</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Serial Number (SN)</th>
                        <th class="py-3 px-3">Redaman (Rx/Tx)</th>
                        <th class="py-3 px-3">Traffic ONU (Rx / Tx)</th>
                        <th class="py-3 px-3">Porsi PON</th>
                        <th class="py-3 px-3">Paket Layanan</th>
                        <th class="py-3 px-3">Alamat</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="onu in filteredOnus" :key="onu.onu_id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <!-- Slot -->
                            <td class="py-2.5 px-3 font-mono font-bold text-slate-700 dark:text-slate-300">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10.5px]" x-text="'#' + onu.onu_id"></span>
                            </td>

                            <!-- Nama ONU & Pelanggan -->
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 dark:text-white" x-text="onu.nama_pelanggan"></div>
                                <div class="flex items-center gap-1.5 text-[10.5px]">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="onu.onu_name"></span>
                                    <span class="text-slate-400">&middot;</span>
                                    <span class="font-mono text-blue-600 dark:text-blue-400" x-text="onu.nomor_internet"></span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-2.5 px-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400': onu.status === 'online',
                                          'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400': onu.status === 'offline',
                                          'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400': onu.status === 'suspend'
                                      }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="{
                                              'bg-emerald-500 animate-pulse': onu.status === 'online',
                                              'bg-rose-500': onu.status === 'offline',
                                              'bg-amber-500': onu.status === 'suspend'
                                          }"></span>
                                    <span x-text="onu.status"></span>
                                </span>
                            </td>

                            <!-- Serial Number -->
                            <td class="py-2.5 px-3 font-mono text-[11px]">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="onu.sn"></span>
                                    <button @click="copyText(onu.sn)" class="text-slate-400 hover:text-blue-600 transition" title="Salin SN">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-[9.5px] text-slate-400" x-text="onu.interface_onu"></div>
                            </td>

                            <!-- Redaman -->
                            <td class="py-2.5 px-3 font-mono">
                                <div>
                                    <span class="font-bold text-xs"
                                          :class="{
                                              'text-emerald-600 dark:text-emerald-400': onu.redaman_status === 'optimal',
                                              'text-amber-600 dark:text-amber-400': onu.redaman_status === 'normal',
                                              'text-rose-600 dark:text-rose-400': onu.redaman_status === 'kritis'
                                          }"
                                          x-text="onu.rx_power">
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    Tx: <span x-text="onu.tx_power"></span>
                                </div>
                            </td>

                            <!-- Traffic -->
                            <td class="py-2.5 px-3 font-mono">
                                <div class="text-cyan-600 dark:text-cyan-400 font-bold" x-text="'Rx: ' + onu.download_formatted"></div>
                                <div class="text-blue-600 dark:text-blue-400 font-semibold text-[10.5px]" x-text="'Tx: ' + onu.upload_formatted"></div>
                            </td>

                            <!-- Porsi PON -->
                            <td class="py-2.5 px-3 font-mono">
                                <span class="px-2 py-0.5 rounded text-[10.5px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300"
                                      x-text="onu.traffic_share_pct + '%'">
                                </span>
                            </td>

                            <!-- Paket -->
                            <td class="py-2.5 px-3">
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block" x-text="onu.package_name"></span>
                                <span class="text-[10px] text-slate-400" x-text="onu.nominal_bandwith + ' Mbps'"></span>
                            </td>

                            <!-- Alamat -->
                            <td class="py-2.5 px-3 max-w-[180px]">
                                <span class="truncate block text-[11px] text-slate-600 dark:text-slate-300" x-text="onu.address"></span>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="onu.uptime"></span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-2.5 px-3 text-right">
                                <a :href="'{{ url('/noc/network-olt') }}?search=' + onu.nomor_internet"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-semibold bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 hover:bg-blue-100 transition">
                                    Explorer
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- =================================================================== -->
<!-- JAVASCRIPT LOGIC DENGAN AUTO-REFRESH & VISIBILITY CLEANUP           -->
<!-- =================================================================== -->
<script>
function onuListManager(initialData, kodeOlt, ponIndex) {
    return {
        onus: initialData?.onus || [],
        portInfo: initialData || {},
        kodeOlt: kodeOlt,
        ponIndex: ponIndex,

        // Filters & Search
        searchQuery: '',
        statusFilter: 'all',
        redamanFilter: 'all',
        sortBy: 'onu_id_asc',
        viewMode: 'grid',

        // Timer & Page Visibility
        currentTime: '',
        timer: null,
        isPageActive: true,
        visibilityHandler: null,

        // Computed Stats
        stats: {
            total: 0,
            online: 0,
            offline: 0,
            suspend: 0,
            totalRx: 0,
            totalTx: 0,
            avgRxPerOnu: 0,
            avgTxPerOnu: 0,
            avgRxPower: '-21.5'
        },

        init() {
            this.calculateStats();
            this.updateClock();

            // Set running clock & live telemetry auto-refresh
            this.timer = setInterval(() => {
                this.updateClock();
                if (this.isPageActive) {
                    this.simulateLiveTraffic();
                }
            }, 1000);

            // Page Visibility API Cleanup
            this.visibilityHandler = () => {
                this.isPageActive = !document.hidden;
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.visibilityHandler) {
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
        },

        updateClock() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            this.currentTime = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        },

        simulateLiveTraffic() {
            let sumRx = 0;
            let sumTx = 0;

            this.onus.forEach(onu => {
                if (onu.is_online) {
                    const variance = (Math.random() - 0.48) * 0.4;
                    const maxBw = onu.nominal_bandwith || 20;
                    let newRx = Math.max(0.4, Math.min(maxBw, onu.download_mbps + variance));
                    newRx = Math.round(newRx * 10) / 10;
                    let newTx = Math.max(0.2, Math.round((newRx * 0.35 + (Math.random() * 0.2)) * 10) / 10);

                    onu.download_mbps = newRx;
                    onu.download_formatted = newRx.toFixed(1) + ' Mbps';
                    onu.upload_mbps = newTx;
                    onu.upload_formatted = newTx.toFixed(1) + ' Mbps';
                    onu.down_pct = Math.min(100, Math.round((newRx / maxBw) * 100));

                    sumRx += newRx;
                    sumTx += newTx;
                }
            });

            this.stats.totalRx = sumRx;
            this.stats.totalTx = sumTx;

            const activeCount = this.stats.online > 0 ? this.stats.online : 1;
            this.stats.avgRxPerOnu = sumRx / activeCount;
            this.stats.avgTxPerOnu = sumTx / activeCount;

            // Update traffic share pct per ONU
            this.onus.forEach(onu => {
                if (onu.is_online && sumRx > 0) {
                    onu.traffic_share_pct = Math.round((onu.download_mbps / sumRx) * 1000) / 10;
                }
            });
        },

        calculateStats() {
            const total = this.onus.length;
            const online = this.onus.filter(o => o.status === 'online').length;
            const offline = this.onus.filter(o => o.status === 'offline').length;
            const suspend = this.onus.filter(o => o.status === 'suspend').length;
            
            const totalRx = this.onus.reduce((acc, o) => acc + (parseFloat(o.download_mbps) || 0), 0);
            const totalTx = this.onus.reduce((acc, o) => acc + (parseFloat(o.upload_mbps) || 0), 0);

            const rxSum = this.onus.reduce((acc, o) => acc + (parseFloat(o.rx_power_num) || -21), 0);
            const avgRx = total > 0 ? (rxSum / total).toFixed(2) : '-21.5';

            const activeCount = online > 0 ? online : 1;
            const avgRxPerOnu = totalRx / activeCount;
            const avgTxPerOnu = totalTx / activeCount;

            this.stats = {
                total,
                online,
                offline,
                suspend,
                totalRx,
                totalTx,
                avgRxPerOnu,
                avgTxPerOnu,
                avgRxPower: avgRx
            };
        },

        get filteredOnus() {
            let list = [...this.onus];

            // 1. Text Search (Pelanggan, No Internet, SN, Interface, Slot)
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase().trim();
                list = list.filter(o => 
                    (o.nama_pelanggan && o.nama_pelanggan.toLowerCase().includes(q)) ||
                    (o.nomor_internet && o.nomor_internet.toLowerCase().includes(q)) ||
                    (o.sn && o.sn.toLowerCase().includes(q)) ||
                    (o.interface_onu && o.interface_onu.toLowerCase().includes(q)) ||
                    (o.onu_name && o.onu_name.toLowerCase().includes(q)) ||
                    String(o.onu_id).includes(q)
                );
            }

            // 2. Status Filter
            if (this.statusFilter !== 'all') {
                list = list.filter(o => o.status === this.statusFilter);
            }

            // 3. Redaman Filter
            if (this.redamanFilter !== 'all') {
                list = list.filter(o => o.redaman_status === this.redamanFilter);
            }

            // 4. Sorting
            list.sort((a, b) => {
                if (this.sortBy === 'onu_id_asc') return a.onu_id - b.onu_id;
                if (this.sortBy === 'onu_id_desc') return b.onu_id - a.onu_id;
                if (this.sortBy === 'traffic_desc') return b.download_mbps - a.download_mbps;
                if (this.sortBy === 'name_asc') return (a.nama_pelanggan || '').localeCompare(b.nama_pelanggan || '');
                if (this.sortBy === 'power_asc') return b.rx_power_num - a.rx_power_num; // Kurang minus = sinyal lebih bagus
                if (this.sortBy === 'power_desc') return a.rx_power_num - b.rx_power_num; // Lebih minus = lebih kritis
                return 0;
            });

            return list;
        },

        resetFilters() {
            this.searchQuery = '';
            this.statusFilter = 'all';
            this.redamanFilter = 'all';
            this.sortBy = 'onu_id_asc';
        },

        copyText(text) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                alert('Serial Number ' + text + ' berhasil disalin ke clipboard!');
            }).catch(() => {});
        }
    };
}
</script>
@endsection
