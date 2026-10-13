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
    <!-- 2. TOP HERO HEADER BANNER (HIGH-CONTRAST COMMAND CENTER DARK SLATE) -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4 shadow-sm border border-teal-500/20 text-white"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <!-- Glow Decorative Elements -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
        <div class="pointer-events-none absolute left-1/3 -top-12 w-48 h-48 bg-teal-400/10 rounded-full blur-xl"></div>
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 relative z-10">
            <!-- Info Kiri: OLT & Port Identitas -->
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-500/20 border border-blue-400/30 text-cyan-300 flex items-center justify-center shrink-0 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-extrabold bg-white text-slate-800 shadow-2xs tracking-wide">
                            {{ $olt->kode_olt }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#04333e]/90 text-cyan-300 border border-teal-400/30 shadow-2xs">
                            {{ $onuData['interface_name'] ?? ('gpon-olt_1/1/' . $ponIndex) }}
                        </span>
                        <h2 class="text-sm sm:text-base font-bold text-white tracking-tight" style="color: #FFFFFF !important;">
                            Monitoring Daftar ONU: {{ $onuData['port_name'] ?? ('PON ' . $ponIndex) }}
                        </h2>
                    </div>
                    <p class="text-[11.5px] text-[#c6edf3] mt-0.5 leading-relaxed" style="color: #c6edf3 !important;">
                        {{ $olt->name_olt ?? 'OLT Server' }} &middot; Host: <span class="font-mono text-cyan-200 font-bold">{{ $olt->ip_address ?? '103.161.206.214' }}</span> &middot; POP: <span class="text-white font-semibold">{{ $olt->nama_pop ?? 'POP MSN' }}</span>
                    </p>

                    <!-- Status Bar & Live Clock (Semua Kontras Jelas) -->
                    <div class="flex flex-wrap items-center gap-2 mt-2 text-[10.5px] font-bold">
                        <!-- Live Clock Badge -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-emerald-300 border border-teal-400/30 shadow-2xs font-mono">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Live: <span class="text-white font-bold" x-text="currentTime">--:--:--</span> WIB</span>
                        </div>
                        <!-- Live Hardware Physical PON Discovery Badge -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-teal-200 border border-teal-400/30 shadow-2xs font-mono">
                            <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                            <span>Fisik Port PON OLT (Direct Hardware ZTE C320)</span>
                        </div>
                        <!-- Auto-Refresh & Real Sync Status Badge -->
                        <button @click="refreshRealData()" 
                                :disabled="isFetching"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-cyan-200 border border-cyan-400/30 shadow-2xs font-semibold hover:bg-[#064757] transition cursor-pointer"
                                title="Klik untuk refresh data ONU langsung dari hardware ZTE C320 sekarang">
                            <span class="w-2 h-2 rounded-full bg-cyan-400" :class="isFetching ? 'animate-spin' : (isPageActive ? 'animate-pulse' : '')"></span>
                            <span x-text="isFetching ? 'Membaca OLT Fisik...' : ('Sync OLT: ' + lastSynced)"></span>
                        </button>
                        <!-- Total Terhubung Badge -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-white border border-teal-400/30 shadow-2xs font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                            <span x-text="stats.total + ' ONU Terhubung'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Switcher Port PON Lain di OLT ini (Glassmorphism Capsule) -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2.5 self-start lg:self-center">
                <span class="text-[11px] font-semibold text-teal-100/90 whitespace-nowrap">Pilih Port PON:</span>
                <div class="flex items-center gap-1 bg-black/25 backdrop-blur-md p-1.5 rounded-xl border border-white/15 shadow-inner">
                    @for($p = 1; $p <= ($onuData['max_port'] ?? 8); $p++)
                        <a href="{{ route('noc.olt.pon.onu-list', ['kode_olt' => $olt->kode_olt, 'pon_index' => $p]) }}"
                           class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold transition {{ $p == $ponIndex ? 'bg-cyan-500 text-white shadow-sm ring-1 ring-white/30' : 'text-teal-100/80 hover:text-white hover:bg-white/15' }}">
                            P{{ $p }}
                        </a>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. KPI STATS SUMMARY CARDS (ISTILAH NOC: UP, DOWN, LOS & TRAFFIC)    -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Card 1: Total Terdaftar / Kapasitas Slot -->
        <div @click="statusFilter = 'all'; redamanFilter = 'all'"
             :class="statusFilter === 'all' && redamanFilter === 'all' 
                ? 'ring-2 ring-blue-500/80 shadow-md border-blue-400 dark:border-blue-500 bg-gradient-to-br from-blue-50/70 via-white to-sky-50/40 dark:from-blue-950/40 dark:via-slate-900 dark:to-slate-900' 
                : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-sm hover:-translate-y-0.5'"
             class="border rounded-2xl p-4 shadow-2xs cursor-pointer transition-all duration-200 select-none flex flex-col justify-between group relative overflow-hidden"
             title="Klik untuk tampilkan semua ONU">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total ONU</span>
                </div>
                <span x-show="statusFilter === 'all' && redamanFilter === 'all'" 
                      class="text-[9px] font-bold font-mono text-blue-700 dark:text-blue-300 bg-blue-100/80 dark:bg-blue-900/60 px-2 py-0.5 rounded-full uppercase tracking-wider">
                    Aktif
                </span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-slate-900 dark:text-white tracking-tight" x-text="stats.total"></span>
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Unit / Slot</span>
            </div>

            <!-- Mini Progress Bar Kapasitas Slot (128 max) -->
            <div class="mt-2.5">
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full transition-all duration-500"
                         :style="'width: ' + Math.min(100, Math.round((stats.total / 128) * 100)) + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-mono">
                    <span>Terisi: <strong class="text-slate-700 dark:text-slate-300" x-text="stats.total + ' / 128'"></strong></span>
                    <span class="font-bold text-blue-600 dark:text-blue-400" x-text="Math.round((stats.total / 128) * 100) + '%'"></span>
                </div>
            </div>
        </div>

        <!-- Card 2: ONU UP (HIJAU / ONLINE) -->
        <div @click="statusFilter = (statusFilter === 'UP' ? 'all' : 'UP')"
             :class="statusFilter === 'UP' 
                ? 'ring-2 ring-emerald-500/80 shadow-md border-emerald-400 bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/40 dark:from-emerald-950/40 dark:via-slate-900 dark:to-teal-950/20' 
                : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-800 hover:shadow-sm hover:-translate-y-0.5'"
             class="border rounded-2xl p-4 shadow-2xs cursor-pointer transition-all duration-200 select-none flex flex-col justify-between group relative overflow-hidden"
             title="Klik untuk filter ONU UP saja">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider">ONU UP</span>
                </div>
                <span class="text-[10px] font-bold font-mono text-emerald-700 dark:text-emerald-300 bg-emerald-100/80 dark:bg-emerald-950/70 px-2 py-0.5 rounded-full"
                      x-text="Math.round((stats.up / Math.max(1, stats.total)) * 100) + '%'"></span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400 tracking-tight" x-text="stats.up"></span>
                <span class="text-xs font-bold text-emerald-600/70 dark:text-emerald-400/70 uppercase tracking-wider">Online</span>
            </div>

            <div class="mt-2.5">
                <div class="w-full bg-emerald-100/70 dark:bg-emerald-950/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                         :style="'width: ' + Math.min(100, Math.round((stats.up / Math.max(1, stats.total)) * 100)) + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-emerald-700/80 dark:text-emerald-400/80 mt-1">
                    <span class="font-medium">Online &amp; Aktif Normal</span>
                    <span x-show="statusFilter === 'UP'" class="text-[9px] font-bold uppercase tracking-wider text-emerald-600">Terfilter</span>
                </div>
            </div>
        </div>

        <!-- Card 3: ONU DOWN (MERAH / POWER OFF) -->
        <div @click="statusFilter = (statusFilter === 'DOWN' ? 'all' : 'DOWN')"
             :class="statusFilter === 'DOWN' 
                ? 'ring-2 ring-red-500/80 shadow-md border-red-400 bg-gradient-to-br from-red-50/80 via-white to-rose-50/40 dark:from-red-950/40 dark:via-slate-900 dark:to-rose-950/20' 
                : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-red-300 dark:hover:border-red-800 hover:shadow-sm hover:-translate-y-0.5'"
             class="border rounded-2xl p-4 shadow-2xs cursor-pointer transition-all duration-200 select-none flex flex-col justify-between group relative overflow-hidden"
             title="Klik untuk filter ONU DOWN saja">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    </div>
                    <span class="text-[11px] font-bold text-red-800 dark:text-red-400 uppercase tracking-wider">ONU DOWN</span>
                </div>
                <span class="text-[10px] font-bold font-mono text-red-700 dark:text-red-300 bg-red-100/80 dark:bg-red-950/70 px-2 py-0.5 rounded-full"
                      x-text="Math.round((stats.down / Math.max(1, stats.total)) * 100) + '%'"></span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-red-600 dark:text-red-400 tracking-tight" x-text="stats.down"></span>
                <span class="text-xs font-bold text-red-500/70 dark:text-red-400/70 uppercase tracking-wider">Offline</span>
            </div>

            <div class="mt-2.5">
                <div class="w-full bg-red-100/70 dark:bg-red-950/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-red-500 h-full rounded-full transition-all duration-500"
                         :style="'width: ' + Math.min(100, Math.round((stats.down / Math.max(1, stats.total)) * 100)) + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-red-700/80 dark:text-red-400/80 mt-1">
                    <span class="font-medium">Power Off / Mati Listrik</span>
                    <span x-show="statusFilter === 'DOWN'" class="text-[9px] font-bold uppercase tracking-wider text-red-600">Terfilter</span>
                </div>
            </div>
        </div>

        <!-- Card 4: ONU LOS (LOSS OF SIGNAL - MERAH ROSE / PING) -->
        <div @click="statusFilter = (statusFilter === 'LOS' ? 'all' : 'LOS')"
             :class="statusFilter === 'LOS' 
                ? 'ring-2 ring-rose-500/80 shadow-md border-rose-400 bg-gradient-to-br from-rose-50/80 via-white to-pink-50/40 dark:from-rose-950/40 dark:via-slate-900 dark:to-pink-950/20' 
                : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-800 hover:shadow-sm hover:-translate-y-0.5'"
             class="border rounded-2xl p-4 shadow-2xs cursor-pointer transition-all duration-200 select-none flex flex-col justify-between group relative overflow-hidden"
             title="Klik untuk filter ONU LOS saja">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    </div>
                    <span class="text-[11px] font-bold text-rose-800 dark:text-rose-400 uppercase tracking-wider">ONU LOS</span>
                </div>
                <span class="text-[10px] font-bold font-mono text-rose-700 dark:text-rose-300 bg-rose-100/80 dark:bg-rose-950/70 px-2 py-0.5 rounded-full"
                      x-text="Math.round((stats.los / Math.max(1, stats.total)) * 100) + '%'"></span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-rose-600 dark:text-rose-400 tracking-tight" x-text="stats.los"></span>
                <span class="text-xs font-bold text-rose-500/70 dark:text-rose-400/70 uppercase tracking-wider">Putus FO</span>
            </div>

            <div class="mt-2.5">
                <div class="w-full bg-rose-100/70 dark:bg-rose-950/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-rose-500 h-full rounded-full transition-all duration-500"
                         :style="'width: ' + Math.min(100, Math.round((stats.los / Math.max(1, stats.total)) * 100)) + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-rose-700/80 dark:text-rose-400/80 mt-1">
                    <span class="font-medium">Loss of Signal / Putus</span>
                    <span x-show="statusFilter === 'LOS'" class="text-[9px] font-bold uppercase tracking-wider text-rose-600">Terfilter</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Total Traffic Rx (Download Agregat) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-2xs hover:shadow-sm hover:border-cyan-300 dark:hover:border-cyan-800 transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-cyan-800 dark:text-cyan-300 uppercase tracking-wider">Total Rx (Down)</span>
                </div>
                <span class="text-[10px] font-bold font-mono text-cyan-700 dark:text-cyan-300 bg-cyan-50 dark:bg-cyan-950/60 px-2 py-0.5 rounded-full">
                    Unduh
                </span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-cyan-600 dark:text-cyan-400 tracking-tight" x-text="stats.totalRx.toFixed(1)"></span>
                <span class="text-xs font-bold text-cyan-600/80 dark:text-cyan-400/80 uppercase font-mono">Mbps</span>
            </div>

            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500">
                <span>Agregat Bandwidth Unduh</span>
                <span class="font-mono text-slate-600 dark:text-slate-400">Total Port</span>
            </div>
        </div>

        <!-- Card 6: Total Traffic Tx (Upload Agregat) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-2xs hover:shadow-sm hover:border-blue-300 dark:hover:border-blue-800 transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider">Total Tx (Up)</span>
                </div>
                <span class="text-[10px] font-bold font-mono text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-full">
                    Unggah
                </span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-blue-600 dark:text-blue-400 tracking-tight" x-text="stats.totalTx.toFixed(1)"></span>
                <span class="text-xs font-bold text-blue-600/80 dark:text-blue-400/80 uppercase font-mono">Mbps</span>
            </div>

            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500">
                <span>Agregat Bandwidth Unggah</span>
                <span class="font-mono text-slate-600 dark:text-slate-400">Total Port</span>
            </div>
        </div>

        <!-- Card 7: Rata-Rata Traffic per ONU -->
        <div class="bg-gradient-to-br from-indigo-50/50 via-white to-violet-50/30 dark:from-indigo-950/30 dark:via-slate-900 dark:to-slate-900 border border-indigo-200/80 dark:border-indigo-900/60 rounded-2xl p-4 shadow-2xs hover:shadow-sm hover:border-indigo-400 dark:hover:border-indigo-700 transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100/70 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider">Rata-Rata / ONU</span>
                </div>
                <span class="text-[10px] font-bold font-mono text-indigo-700 dark:text-indigo-300 bg-indigo-100/80 dark:bg-indigo-950/70 px-2 py-0.5 rounded-full"
                      x-text="'÷ ' + stats.up + ' UP'"></span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-indigo-700 dark:text-indigo-300 tracking-tight" x-text="stats.avgRxPerOnu.toFixed(1)"></span>
                <span class="text-xs font-bold text-indigo-600/80 dark:text-indigo-400/80 uppercase font-mono">Mbps Rx</span>
            </div>

            <div class="mt-2.5 pt-2 border-t border-indigo-100/80 dark:border-indigo-950 flex items-center justify-between text-[10px] font-mono">
                <span class="text-cyan-700 dark:text-cyan-400 font-bold" x-text="'↓ ' + stats.avgRxPerOnu.toFixed(1) + ' M'"></span>
                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                <span class="text-blue-700 dark:text-blue-400 font-bold" x-text="'↑ ' + stats.avgTxPerOnu.toFixed(1) + ' M'"></span>
                <span class="text-slate-400 dark:text-slate-500 font-sans text-[9.5px]">Rata-rata Rx / Tx</span>
            </div>
        </div>

        <!-- Card 8: Avg Redaman Rx -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-2xs hover:shadow-sm hover:border-amber-300 dark:hover:border-amber-800 transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider">Avg Redaman</span>
                </div>
                <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">
                    Optimal
                </span>
            </div>

            <div class="mt-2.5 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black font-mono text-amber-600 dark:text-amber-400 tracking-tight" x-text="stats.avgRxPower"></span>
                <span class="text-xs font-bold text-amber-600/80 dark:text-amber-400/80 uppercase font-mono">dBm</span>
            </div>

            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500">
                <span>Ideal: -15 s/d -24 dBm</span>
                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">Rx Optical</span>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. CONTROLS BAR: SEARCH & COMPACT FILTER CONTROLS                   -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-3 sm:p-3.5 shadow-2xs space-y-2.5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5">
            
            <!-- Kiri: Search Input & Dropdown Filters (Status, Redaman, Sort) -->
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[210px] max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari Pelanggan, No Internet, SN, Slot..."
                           class="w-full pl-9.5 pr-8 py-2 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <button x-show="searchQuery" 
                            @click="searchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Filter Status NOC (Dropdown) -->
                <div class="relative">
                    <select x-model="statusFilter"
                            class="pl-8.5 pr-7 py-2 rounded-xl text-xs font-semibold bg-slate-50/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer appearance-none transition"
                            :class="statusFilter !== 'all' ? 'border-blue-500 bg-blue-50/70 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 font-bold' : ''">
                        <option value="all">Status: Semua Status</option>
                        <option value="UP">Status: UP (Online)</option>
                        <option value="DOWN">Status: DOWN (Offline)</option>
                        <option value="LOS">Status: LOS (Kabel Putus)</option>
                        <option value="SUSPEND">Status: SUSPEND (Isolir)</option>
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400"
                         :class="statusFilter !== 'all' ? 'text-blue-600 dark:text-blue-400' : ''">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Filter Redaman Optik (Dropdown) -->
                <div class="relative">
                    <select x-model="redamanFilter"
                            class="pl-8.5 pr-7 py-2 rounded-xl text-xs font-semibold bg-slate-50/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer appearance-none transition"
                            :class="redamanFilter !== 'all' ? 'border-amber-500 bg-amber-50/70 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 font-bold' : ''">
                        <option value="all">Redaman: Semua</option>
                        <option value="optimal">Redaman: Optimal (&gt; -24 dBm)</option>
                        <option value="normal">Redaman: Normal (-24 s/d -26.5 dBm)</option>
                        <option value="kritis">Redaman: Kritis (&lt; -26.5 dBm)</option>
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400"
                         :class="redamanFilter !== 'all' ? 'text-amber-500' : ''">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Sort Dropdown -->
                <div class="relative">
                    <select x-model="sortBy"
                            class="pl-8 pr-7 py-2 rounded-xl text-xs font-medium bg-slate-50/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer appearance-none transition">
                        <option value="onu_id_asc">Urutkan: Slot (1 &rarr; 128)</option>
                        <option value="onu_id_desc">Urutkan: Slot (128 &rarr; 1)</option>
                        <option value="traffic_desc">Urutkan: Traffic Rx Terbesar</option>
                        <option value="name_asc">Urutkan: Nama (A - Z)</option>
                        <option value="power_asc">Urutkan: Redaman Terbaik</option>
                        <option value="power_desc">Urutkan: Redaman Kritis</option>
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Tombol Reset Filter -->
                <button x-show="statusFilter !== 'all' || redamanFilter !== 'all' || searchQuery !== ''"
                        @click="resetFilters()"
                        class="px-2.5 py-2 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white border border-rose-200 dark:border-rose-900/50 flex items-center gap-1 transition"
                        title="Reset Filter ke Default">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Kanan: Counter Hasil & View Switcher -->
            <div class="flex items-center justify-between lg:justify-end gap-3 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800">
                <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                    Menampilkan <strong class="text-slate-900 dark:text-white font-mono" x-text="filteredOnus.length"></strong> dari <strong class="text-slate-900 dark:text-white font-mono" x-text="onus.length"></strong> ONU
                </span>

                <!-- Grid / Table View Switcher -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                    <button @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                            class="px-2.5 py-1.5 rounded-lg text-xs flex items-center gap-1 transition"
                            title="Tampilan Grid Card">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                            class="px-2.5 py-1.5 rounded-lg text-xs flex items-center gap-1 transition"
                            title="Tampilan Tabel Detail">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden sm:inline">Tabel</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Active Filter Badges Strip (Muncul otomatis saat filter aktif) -->
        <div x-show="statusFilter !== 'all' || redamanFilter !== 'all' || searchQuery"
             class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-[11px]">
            <span class="text-slate-400 font-medium">Filter Aktif:</span>
            
            <template x-if="searchQuery">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 font-medium">
                    <span>Cari: "<span x-text="searchQuery"></span>"</span>
                    <button @click="searchQuery = ''" class="hover:text-blue-900 font-bold">&times;</button>
                </span>
            </template>

            <template x-if="statusFilter !== 'all'">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-semibold">
                    <span>Status: <strong x-text="statusFilter"></strong></span>
                    <button @click="statusFilter = 'all'" class="hover:text-indigo-900 font-black">&times;</button>
                </span>
            </template>

            <template x-if="redamanFilter !== 'all'">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-semibold">
                    <span>Redaman: <strong x-text="redamanFilter"></strong></span>
                    <button @click="redamanFilter = 'all'" class="hover:text-amber-900 font-black">&times;</button>
                </span>
            </template>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. TAMPILAN 1: GRID CARD VIEW (TRAFFIC PERSIS SEPERTI DI CARD PON)  -->
    <!-- =================================================================== -->
    <div x-show="viewMode === 'grid'" class="space-y-4">
        <!-- Grid Items: 3 Kolom Responsif dengan Spacing Sempurna -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
            <template x-for="onu in filteredOnus" :key="onu.onu_id">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 shadow-2xs hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-700 hover:-translate-y-0.5 transition-all duration-200 relative overflow-hidden flex flex-col justify-between group">
                    <!-- Accent Bar di Top Edge -->
                    <div class="absolute top-0 inset-x-0 h-1.5 transition-all duration-300" :class="getStatusInfo(onu).bgBar"></div>

                    <!-- Bagian Konten Atas Card -->
                    <div class="space-y-3 pt-1">
                        <!-- 1. Header Card: Nomor Slot, Nama ONU, Identitas Pelanggan & Status Badge -->
                        <div class="flex items-start justify-between gap-2.5 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <!-- Badge Nomor Slot -->
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-mono font-black text-xs shrink-0 text-white shadow-xs ring-1 ring-black/5"
                                     :class="getStatusInfo(onu).bgSlot"
                                     x-text="'#' + onu.onu_id">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <!-- Nama ONU & Pelanggan -->
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[11px] font-black text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 px-1.5 py-0.5 rounded-md shrink-0" 
                                              x-text="onu.onu_name || ('ONU ' + onu.onu_id)"></span>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate" 
                                            x-text="onu.nama_pelanggan" 
                                            :title="onu.nama_pelanggan"></h4>
                                    </div>
                                    <!-- ID Pelanggan, Interface & ODP -->
                                    <div class="flex items-center flex-wrap gap-1 text-[10px] text-slate-500 mt-1">
                                        <span class="font-mono text-blue-600 dark:text-blue-400 font-bold" x-text="'ID: ' + onu.nomor_internet"></span>
                                        <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                        <span class="font-mono text-slate-500 dark:text-slate-400 truncate" x-text="onu.interface_onu"></span>
                                        <template x-if="onu.odp_name && onu.odp_name !== '-'">
                                            <span class="inline-flex items-center gap-1 font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.2 rounded text-[9.5px]">
                                                <span x-text="'ODP: ' + onu.odp_name"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge NOC (UP = Hijau, DOWN = Merah, LOS = Rose/Ping, SUSPEND = Amber) -->
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-black uppercase tracking-wider shrink-0 shadow-2xs"
                                  :class="getStatusInfo(onu).colorBadge">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="getStatusInfo(onu).dotClass"></span>
                                <span x-text="getStatusInfo(onu).label"></span>
                            </span>
                        </div>

                        <!-- 2. TAMPILAN TRAFFIC SEPERTI DI PON (MRTG CACTI MONITOR) -->
                        <div class="space-y-2">
                            <!-- Dual Throughput Badges -->
                            <div class="grid grid-cols-2 gap-2 text-[10.5px]">
                                <div class="px-2.5 py-1.5 rounded-xl bg-cyan-50/70 dark:bg-cyan-950/30 border border-cyan-100 dark:border-cyan-900/40 flex items-center justify-between">
                                    <div class="flex items-center gap-1 text-slate-600 dark:text-slate-400 font-medium text-[10px]">
                                        <span class="text-cyan-600 dark:text-cyan-400 font-black text-xs">&darr;</span>
                                        <span>Download (Rx):</span>
                                    </div>
                                    <span class="font-mono font-black text-xs text-cyan-700 dark:text-cyan-300" x-text="onu.download_formatted"></span>
                                </div>
                                <div class="px-2.5 py-1.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-between">
                                    <div class="flex items-center gap-1 text-slate-600 dark:text-slate-400 font-medium text-[10px]">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-black text-xs">&uarr;</span>
                                        <span>Upload (Tx):</span>
                                    </div>
                                    <span class="font-mono font-black text-xs text-emerald-700 dark:text-emerald-300" x-text="onu.upload_formatted"></span>
                                </div>
                            </div>

                            <!-- Box Frame Grafik MRTG (Sumbu Y, Plot Area SVG & Sumbu X) -->
                            <div class="p-2 rounded-xl bg-slate-50/70 dark:bg-slate-950/60 border border-slate-200/90 dark:border-slate-800 shadow-inner">
                                <div class="flex items-stretch gap-1.5 h-[95px] sm:h-[105px]">
                                    <!-- Sumbu Y Kiri -->
                                    <div class="flex items-stretch gap-1 select-none shrink-0 h-full">
                                        <span class="text-[7px] font-bold text-slate-400 self-center" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                                            Bits/Sec
                                        </span>
                                        <div class="flex flex-col justify-between items-end h-full text-[7.5px] font-mono font-bold text-slate-500 dark:text-slate-400 leading-none py-0.5">
                                            <span x-text="onu.mrtg?.y_labels?.[0] ?? '20.0 M'"></span>
                                            <span x-text="onu.mrtg?.y_labels?.[1] ?? '15.0 M'"></span>
                                            <span x-text="onu.mrtg?.y_labels?.[2] ?? '10.0 M'"></span>
                                            <span x-text="onu.mrtg?.y_labels?.[3] ?? '5.0 M'"></span>
                                            <span x-text="onu.mrtg?.y_labels?.[4] ?? '0.0 M'"></span>
                                        </div>
                                    </div>

                                    <!-- Plot Area SVG MRTG -->
                                    <div class="flex-1 min-w-0 h-full bg-[#f8fafc] dark:bg-[#060c18] border border-slate-700/80 rounded-xs overflow-hidden relative">
                                        <svg viewBox="0 0 720 105" preserveAspectRatio="none" class="w-full h-full block">
                                            <!-- Grid Lines Horizontal (5 Baris) -->
                                            <line x1="0" y1="0" x2="720" y2="0" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="0" y1="26.25" x2="720" y2="26.25" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="0" y1="52.5" x2="720" y2="52.5" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="0" y1="78.75" x2="720" y2="78.75" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="0" y1="105" x2="720" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                            <!-- Grid Lines Vertical (8 Pembagi) -->
                                            <line x1="90" y1="0" x2="90" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="180" y1="0" x2="180" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="270" y1="0" x2="270" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="360" y1="0" x2="360" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="450" y1="0" x2="450" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="540" y1="0" x2="540" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                            <line x1="630" y1="0" x2="630" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                            <!-- Red Boundary Markers -->
                                            <line x1="115.2" y1="0" x2="115.2" y2="105" stroke="#ef4444" stroke-width="1.3"/>
                                            <line x1="633.6" y1="0" x2="633.6" y2="105" stroke="#ef4444" stroke-width="1.3"/>

                                            <!-- In Traffic Polygon (Solid Green Area) -->
                                            <polygon :points="onu.mrtg?.in_polygon ?? ''" fill="#00cc00" fill-opacity="0.95"/>

                                            <!-- Out Traffic Polyline (Solid Blue Line) -->
                                            <polyline :points="onu.mrtg?.out_polyline ?? ''" fill="none" stroke="#0000ff" stroke-width="1.6"/>

                                            <!-- Indicator Arrow at 0.0M -->
                                            <polygon points="0,101 6,105 0,109" fill="#ef4444"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Sumbu X (Presisi Sejajar di Bawah SVG) -->
                                <div class="flex items-center mt-1">
                                    <div class="w-[38px] shrink-0"></div>
                                    <div class="flex-1 flex justify-between items-center text-[7px] font-bold font-mono text-slate-400 px-0.5 select-none">
                                        <template x-for="(xLbl, xIdx) in (onu.mrtg?.x_labels ?? ['Mon','Sun','Sat','Fri','Thu','Wed','Tue','Mon','Sun'])" :key="xIdx">
                                            <span x-text="xLbl"></span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Legend Stats In & Out (Sama Persis seperti di Card PON) -->
                                <div class="mt-1.5 pt-1.5 border-t border-slate-200/90 dark:border-slate-800 flex flex-wrap items-center justify-between gap-1 text-[8px] font-mono">
                                    <div class="flex items-center gap-1 bg-white/70 dark:bg-slate-900/70 px-1.5 py-0.5 rounded border border-slate-200/70 dark:border-slate-800">
                                        <span class="w-2 h-2 rounded-xs bg-[#00cc00] border border-emerald-600 shrink-0"></span>
                                        <span class="text-slate-500 font-bold">In:</span>
                                        <span class="text-slate-700 dark:text-slate-300">
                                            Cur: <strong class="text-emerald-600 font-bold" x-text="onu.mrtg?.current_in ?? (onu.download_mbps + ' M')"></strong>
                                            &middot; Max: <span x-text="onu.mrtg?.max_in ?? '20.0 M'"></span>
                                            &middot; Avg: <span x-text="onu.mrtg?.avg_in ?? '8.5 M'"></span>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1 bg-white/70 dark:bg-slate-900/70 px-1.5 py-0.5 rounded border border-slate-200/70 dark:border-slate-800">
                                        <span class="w-2 h-1 rounded-xs bg-[#0000ff] shrink-0"></span>
                                        <span class="text-slate-500 font-bold">Out:</span>
                                        <span class="text-slate-700 dark:text-slate-300">
                                            Cur: <strong class="text-blue-600 font-bold" x-text="onu.mrtg?.current_out ?? (onu.upload_mbps + ' M')"></strong>
                                            &middot; Max: <span x-text="onu.mrtg?.max_out ?? '7.0 M'"></span>
                                            &middot; Avg: <span x-text="onu.mrtg?.avg_out ?? '3.2 M'"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Section Redaman Optik (Power) & Deteksi LOS -->
                        <div class="p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>Redaman Optik (Power):</span>
                                </span>
                                <!-- Status Redaman -->
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400': onu.redaman_status === 'optimal',
                                          'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400': onu.redaman_status === 'normal',
                                          'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400': onu.redaman_status === 'kritis'
                                      }"
                                      x-text="getStatusInfo(onu).label === 'LOS' ? 'LOS (Sinyal Hilang)' : (getStatusInfo(onu).label === 'DOWN' ? 'Offline' : onu.redaman_status)">
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                <div class="p-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-sans font-medium shrink-0">Rx Power:</span>
                                    <span class="font-bold text-xs whitespace-nowrap"
                                          :class="{
                                              'text-emerald-600 dark:text-emerald-400': getStatusInfo(onu).label === 'UP' && onu.redaman_status === 'optimal',
                                              'text-amber-600 dark:text-amber-400': getStatusInfo(onu).label === 'UP' && onu.redaman_status === 'normal',
                                              'text-rose-600 dark:text-rose-400': getStatusInfo(onu).label === 'LOS' || onu.redaman_status === 'kritis',
                                              'text-slate-400': getStatusInfo(onu).label === 'DOWN'
                                          }"
                                          x-text="onu.rx_power">
                                    </span>
                                </div>
                                <div class="p-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-sans font-medium shrink-0">Tx Laser:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300 text-xs whitespace-nowrap" x-text="onu.tx_power"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Section Perangkat & Layanan (Serial Number, Paket & Alamat) -->
                        <div class="grid grid-cols-2 gap-2 text-[10.5px]">
                            <!-- SN Modem -->
                            <div class="p-2 rounded-xl bg-slate-50/70 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-wider block">Serial Number:</span>
                                <div class="flex items-center justify-between mt-0.5">
                                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs truncate" x-text="onu.sn"></span>
                                    <button @click="copyText(onu.sn)" class="text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition shrink-0 ml-1 p-0.5" title="Salin Serial Number">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Paket Layanan -->
                            <div class="p-2 rounded-xl bg-slate-50/70 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-wider block">Paket Internet:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 truncate block mt-0.5 text-xs" x-text="onu.package_name"></span>
                            </div>
                        </div>

                        <!-- Alamat & Uptime Info -->
                        <div class="space-y-1 text-[10.5px] text-slate-500 dark:text-slate-400 pt-0.5">
                            <div class="flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate" x-text="onu.address" :title="onu.address"></span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="flex items-center gap-1">Status / Uptime: <strong class="font-mono text-slate-700 dark:text-slate-300" x-text="onu.uptime"></strong></span>
                                <span class="font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded" x-text="'POP: ' + onu.pop_name"></span>
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
                           class="px-2.5 py-1 rounded-lg text-[10.5px] font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                            GPON Slot
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State jika tidak ada yang cocok -->
        <div x-show="filteredOnus.length === 0" 
             class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-10 text-center shadow-2xs">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800/80 mx-auto flex items-center justify-center text-slate-400">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200">Tidak ada ONU yang sesuai kriteria</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Coba ubah kata kunci pencarian atau sesuaikan filter status/redaman.</p>
            <button @click="resetFilters()" class="mt-4 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition">
                Reset Semua Filter
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. TAMPILAN 2: TABEL LIST VIEW                                      -->
    <!-- =================================================================== -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl overflow-hidden shadow-2xs">
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
                                <div class="flex items-center flex-wrap gap-1.5 text-[10.5px]">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="onu.onu_name"></span>
                                    <span class="text-slate-400">&middot;</span>
                                    <span class="font-mono text-blue-600 dark:text-blue-400" x-text="onu.nomor_internet"></span>
                                    <template x-if="onu.odp_name && onu.odp_name !== '-'">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="text-slate-400">&middot;</span>
                                            <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="'ODP: ' + onu.odp_name"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>

                            <!-- Status Badge NOC -->
                            <td class="py-2.5 px-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-black uppercase tracking-wider"
                                      :class="getStatusInfo(onu).colorBadge">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="getStatusInfo(onu).dotClass"></span>
                                    <span x-text="getStatusInfo(onu).label"></span>
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
                                              'text-emerald-600 dark:text-emerald-400': getStatusInfo(onu).label === 'UP' && onu.redaman_status === 'optimal',
                                              'text-amber-600 dark:text-amber-400': getStatusInfo(onu).label === 'UP' && onu.redaman_status === 'normal',
                                              'text-rose-600 dark:text-rose-400': getStatusInfo(onu).label === 'LOS' || onu.redaman_status === 'kritis',
                                              'text-slate-400': getStatusInfo(onu).label === 'DOWN'
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
        <div class="px-3.5 py-2 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>* Traffic per ONU didistribusikan proporsional sesuai paket bandwidth pelanggan dari total counter byte hardware real port PON ZTE C320.</span>
            </span>
            <span class="font-mono text-[10.5px] text-slate-400" x-text="'Sinkronisasi hardware: ' + lastSynced"></span>
        </div>
    </div>

</div>

<!-- =================================================================== -->
<!-- JAVASCRIPT LOGIC DENGAN MRTG LIVE UPDATE & RELIABLE FILTERING       -->
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

        // Timer, Polling & Page Visibility
        currentTime: '',
        lastSynced: '{{ now()->timezone("Asia/Jakarta")->format("H:i:s") }} WIB',
        isFetching: false,
        timer: null,
        pollTimer: null,
        isPageActive: true,
        visibilityHandler: null,

        // Computed Stats Standar NOC & Redaman
        stats: {
            total: 0,
            up: 0,
            down: 0,
            los: 0,
            suspend: 0,
            redamanOptimal: 0,
            redamanNormal: 0,
            redamanKritis: 0,
            totalRx: 0,
            totalTx: 0,
            avgRxPerOnu: 0,
            avgTxPerOnu: 0,
            avgRxPower: '-21.5'
        },

        init() {
            this.calculateStats();
            this.updateClock();

            // 1. Set running clock
            this.timer = setInterval(() => {
                this.updateClock();
            }, 1000);

            // 2. Polling data real dari hardware OLT ZTE C320 setiap 30 detik (sinkron dengan cache 30s)
            this.pollTimer = setInterval(() => {
                if (this.isPageActive) {
                    this.refreshRealData();
                }
            }, 30000);

            // Page Visibility API Cleanup
            this.visibilityHandler = () => {
                this.isPageActive = !document.hidden;
                if (this.isPageActive) {
                    this.updateClock();
                    this.refreshRealData();
                }
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
            if (this.visibilityHandler) {
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
        },

        formatWibTime(date = new Date()) {
            try {
                const formatter = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                return formatter.format(date).replace(/\./g, ':');
            } catch (e) {
                const pad = (n) => String(n).padStart(2, '0');
                return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
            }
        },

        updateClock() {
            this.currentTime = this.formatWibTime(new Date());
        },

        // Helper Status Standar NOC/Teknisi: UP (Hijau), DOWN (Merah), LOS (Rose / Ping)
        getStatusInfo(onu) {
            const raw = (onu.status || onu.status_raw || '').toString().trim().toUpperCase();
            const isSignalLost = (onu.is_los || raw === 'LOS' || raw === 'LOSSOFSIGNAL' || (onu.rx_power && onu.rx_power.toString().toUpperCase().includes('LOS')));

            // 1. Kondisi LOS (Loss of Signal) jika sinyal optik hilang / kabel putus
            if (isSignalLost) {
                return {
                    label: 'LOS',
                    colorBadge: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-800',
                    borderTop: 'border-t-rose-600',
                    bgBar: 'bg-gradient-to-r from-rose-500 to-pink-500',
                    bgSlot: 'bg-gradient-to-br from-rose-500 to-rose-700',
                    dotClass: 'bg-rose-500 animate-ping',
                    desc: 'Loss of Signal'
                };
            }

            // 2. Kondisi UP (Online / Working / Ready) -> Badge HIJAU
            if (raw === 'UP' || raw === 'ONLINE' || raw === 'WORKING' || raw === 'READY' || onu.is_online) {
                return {
                    label: 'UP',
                    colorBadge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800',
                    borderTop: 'border-t-emerald-500',
                    bgBar: 'bg-gradient-to-r from-emerald-500 to-teal-500',
                    bgSlot: 'bg-gradient-to-br from-emerald-500 to-teal-700',
                    dotClass: 'bg-emerald-500 animate-pulse',
                    desc: 'Online Normal'
                };
            }

            // 3. Kondisi SUSPEND (Terisolir) -> Badge AMBER
            if (raw === 'SUSPEND' || raw === 'ISOLIR' || onu.is_suspend) {
                return {
                    label: 'SUSPEND',
                    colorBadge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-400 border border-amber-300 dark:border-amber-800',
                    borderTop: 'border-t-amber-500',
                    bgBar: 'bg-gradient-to-r from-amber-500 to-orange-500',
                    bgSlot: 'bg-gradient-to-br from-amber-500 to-orange-600',
                    dotClass: 'bg-amber-500',
                    desc: 'Terisolir'
                };
            }

            // 4. Kondisi DOWN (Offline / Power Off / Mati) -> Badge MERAH
            return {
                label: 'DOWN',
                colorBadge: 'bg-red-100 text-red-800 dark:bg-red-950/70 dark:text-red-400 border border-red-300 dark:border-red-800',
                borderTop: 'border-t-red-600',
                bgBar: 'bg-gradient-to-r from-red-500 to-rose-600',
                bgSlot: 'bg-gradient-to-br from-red-500 to-red-700',
                dotClass: 'bg-red-500',
                desc: 'Power Off / Offline'
            };
        },

        // Polling background data ONU asli dari hardware OLT ZTE C320
        refreshRealData() {
            if (this.isFetching) return;
            this.isFetching = true;
            fetch(window.location.pathname + '?format=json', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success && data.onuData) {
                    this.onus = data.onuData.onus || [];
                    this.portInfo = data.onuData || {};
                    this.calculateStats();
                    this.lastSynced = data.updated_at || (this.formatWibTime(new Date()) + ' WIB');
                }
            })
            .catch(err => console.debug('Polling real ONU list notice:', err))
            .finally(() => {
                this.isFetching = false;
            });
        },

        calculateStats() {
            const total = this.onus.length;
            let up = 0;
            let down = 0;
            let los = 0;
            let suspend = 0;

            let optimal = 0;
            let normal = 0;
            let kritis = 0;

            let totalRx = 0;
            let totalTx = 0;
            let rxSum = 0;
            let rxCount = 0;

            this.onus.forEach(o => {
                const info = this.getStatusInfo(o);
                if (info.label === 'UP') {
                    up++;
                    totalRx += (parseFloat(o.download_mbps) || 0);
                    totalTx += (parseFloat(o.upload_mbps) || 0);

                    const rxPowerNum = parseFloat(o.rx_power_num || -25);
                    rxSum += rxPowerNum;
                    rxCount++;

                    // Hitung klasifikasi redaman HANYA untuk ONU UP / aktif
                    if (rxPowerNum < -26.5) {
                        kritis++;
                    } else if (rxPowerNum >= -24.0) {
                        optimal++;
                    } else {
                        normal++;
                    }
                } else if (info.label === 'LOS') {
                    los++;
                    kritis++;
                } else if (info.label === 'SUSPEND') {
                    suspend++;
                } else {
                    down++;
                }
            });

            const activeCount = up > 0 ? up : 1;
            const avgRx = rxCount > 0 ? (rxSum / rxCount).toFixed(2) : (total > 0 ? '-21.5' : '0.0');

            this.stats = {
                total,
                up,
                down,
                los,
                suspend,
                redamanOptimal: optimal,
                redamanNormal: normal,
                redamanKritis: kritis,
                totalRx,
                totalTx,
                avgRxPerOnu: totalRx / activeCount,
                avgTxPerOnu: totalTx / activeCount,
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

            // 2. Status Filter Standar NOC: UP, DOWN, LOS, SUSPEND (Fungsi Terverifikasi Benar)
            if (this.statusFilter !== 'all') {
                list = list.filter(o => this.getStatusInfo(o).label === this.statusFilter);
            }

            // 3. Redaman Filter (Fungsi Akurat Sesuai Nilai Rx Power & Status LOS)
            if (this.redamanFilter !== 'all') {
                if (this.redamanFilter === 'optimal') {
                    list = list.filter(o => parseFloat(o.rx_power_num || -25) >= -24.0 && this.getStatusInfo(o).label !== 'LOS');
                } else if (this.redamanFilter === 'normal') {
                    list = list.filter(o => {
                        const p = parseFloat(o.rx_power_num || -25);
                        return p < -24.0 && p >= -26.5 && this.getStatusInfo(o).label !== 'LOS';
                    });
                } else if (this.redamanFilter === 'kritis') {
                    list = list.filter(o => parseFloat(o.rx_power_num || -25) < -26.5 || this.getStatusInfo(o).label === 'LOS');
                }
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
