@extends('layouts.app')

@section('title', 'Traffic per Port PON - ' . ($olt->name_olt ?? $olt->kode_olt) . ' - NOC IMS')
@section('page_title', 'Traffic per PON: ' . ($olt->name_olt ?? $olt->kode_olt))

@section('content')
<div class="space-y-4"
     x-data="ponLiveManager({{ Js::from($ponTelemetry) }})"
     x-init="init()"
     x-destroy="destroy()">

    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER (COMMAND CENTER DARK BLUE THEME)          -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3 sm:p-4 shadow-sm border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 relative z-10">
            <div class="flex items-start sm:items-center gap-3">
                <!-- Icon OLT / PON -->
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 border border-blue-400/30 text-blue-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-600 text-white">
                            {{ $olt->kode_olt }}
                        </span>
                        <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">
                            Detail Monitoring Traffic per Port PON
                        </h2>
                    </div>
                    <p class="text-[11px] text-slate-300 mt-0.5">
                        {{ $olt->name_olt ?? 'OLT Server' }} &middot; IP Host: <span class="font-mono text-cyan-300 font-bold">{{ $olt->ip_address ?? '103.161.206.214' }}</span> &middot; POP: <span class="text-slate-200">{{ $olt->nama_pop ?? 'POP MSN' }}</span>
                    </p>
                    
                    <!-- Stats Badges Bar & Live Clock -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2 text-[10px] font-bold">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-800/80 text-slate-200 border border-slate-700/60">
                            <span>Total PON: <span x-text="ports.length"></span> Port</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Live: <span class="font-mono text-white" x-text="currentTime">--:--:--</span> WIB</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 font-mono shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                            <span>Fisik Port PON OLT (Direct Hardware ZTE C320)</span>
                        </div>
                        <button @click="fetchFreshTraffic()" 
                                :disabled="isFetching"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/25 transition cursor-pointer shadow-xs"
                                title="Klik untuk membaca counter byte terbaru dari OLT sekarang">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" :class="isFetching ? 'animate-ping' : (isPageActive ? 'animate-pulse' : '')"></span>
                            <span x-text="isFetching ? 'Membaca Hardware OLT...' : ('Sync Counter: ' + lastSynced)"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Header Action: Back to OLT & Switcher -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Dropdown Switch OLT -->
                <div class="relative">
                    <select onchange="window.location.href='{{ url('/noc/olt') }}/' + this.value + '/traffic-pon'" 
                            class="text-xs bg-slate-900/90 text-white border border-slate-700 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                        @foreach($allOlts as $o)
                            <option value="{{ $o->kode_olt }}" {{ $o->kode_olt === $olt->kode_olt ? 'selected' : '' }}>
                                {{ $o->kode_olt }} - {{ $o->name_olt ?? $o->nama_olt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('noc.olt') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800/90 hover:bg-slate-700 text-white text-xs font-semibold border border-slate-700 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span>Kembali ke Master OLT</span>
                </a>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. DATE RANGE PICKER FILTER BAR (DARI TANGGAL & SAMPAI TANGGAL)      -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
        <form method="GET" action="{{ route('noc.olt.pon-traffic', ['kode_olt' => $olt->kode_olt]) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                    </svg>
                    <span>Filter Periode:</span>
                </span>

                <!-- Dari Tanggal -->
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Dari Tanggal:</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ $startDate }}"
                           class="px-2 py-0.5 text-xs font-mono rounded bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    <span class="text-[10px] text-slate-400 font-mono hidden md:inline">
                        ({{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }})
                    </span>
                </div>

                <!-- Sampai Tanggal -->
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Sampai Tanggal:</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ $endDate }}"
                           class="px-2 py-0.5 text-xs font-mono rounded bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    <span class="text-[10px] text-slate-400 font-mono hidden md:inline">
                        ({{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }})
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" 
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter Data Traffic</span>
                </button>

                <a href="{{ route('noc.olt.pon-traffic', ['kode_olt' => $olt->kode_olt]) }}" 
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition"
                   title="Reset Filter ke 7 Hari Terakhir">
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 3. GRID KARTU TRAFFIC PER PORT PON (RAPAT, PADAT & FULL HEIGHT)     -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <template x-for="(port, pIdx) in ports" :key="port.port_index">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs relative overflow-hidden flex flex-col justify-between">
                <!-- Top Accent Line Solid (No White Gradient) -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-indigo-600"></div>

                <!-- 1. Port Header: Badge, Nama, User Count, Laser & Live Clock -->
                <div>
                    <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                                 x-text="'P' + port.port_index">
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" x-text="port.port_name"></h3>
                                    <span class="font-mono text-[9.5px] text-slate-500 dark:text-slate-400 truncate" x-text="'(' + port.interface_name + ')'"></span>
                                </div>
                                <div class="flex items-center flex-wrap gap-1.5 text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    <span class="inline-flex items-center gap-1 font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 px-1.5 py-0.5 rounded border border-blue-200/80 dark:border-blue-800/80 text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span x-text="port.user_count + ' ONU Terhubung'"></span>
                                    </span>
                                    <template x-if="port.is_hw_counter">
                                        <span class="inline-flex items-center gap-1 font-bold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/60 px-1.5 py-0.5 rounded border border-teal-200/80 dark:border-teal-800/80 text-[10px]" title="Counter Byte Asli dari Hardware OLT ZTE C320">
                                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                            <span>Hardware ZTE</span>
                                        </span>
                                    </template>
                                    <template x-if="port.odp_count !== undefined">
                                        <span class="inline-flex items-center gap-1 font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 rounded border border-amber-200/80 dark:border-amber-800/80 text-[10px]">
                                            <span x-text="port.odp_count + '/' + (port.port_max || 8) + ' ODP'"></span>
                                        </span>
                                    </template>
                                    <span>&middot;</span>
                                    <span class="text-slate-600 dark:text-slate-300">
                                        Slot: <span x-text="port.user_count"></span>/<span x-text="port.max_capacity"></span> (<span x-text="port.utilization_pct"></span>%)
                                    </span>
                                    <span>&middot;</span>
                                    <span>Laser: <strong class="text-amber-600 font-mono" x-text="port.tx_power"></strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Live Badge, Time & GPON Action Link -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <!-- Live Running Time Badge -->
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-mono font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                                  title="Live Time Auto-Refresh 1 Detik">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" :class="isPageActive ? 'animate-pulse' : ''"></span>
                                <span x-text="currentTime"></span>
                            </span>

                            <a :href="'{{ url('/noc/gpon') }}?olt={{ $olt->kode_olt }}&port=gpon-onu_1/1/' + port.port_index" 
                               class="p-1 rounded-md bg-slate-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-blue-950/40 text-slate-500 dark:text-slate-400 transition"
                               title="Lihat Pemetaan Pelanggan GPON di Port ini">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- 2. Bandwidth Throughput Badges (Compact) -->
                    <div class="grid grid-cols-2 gap-2 my-2 text-[10.5px]">
                        <div class="px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-500 text-[10px] font-semibold">Download (Rx):</span>
                            <span class="font-mono font-bold text-cyan-600 dark:text-cyan-400" x-text="port.download_formatted"></span>
                        </div>
                        <div class="px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-500 text-[10px] font-semibold">Upload (Tx):</span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="port.upload_formatted"></span>
                        </div>
                    </div>

                    <!-- 3. Box Frame Grafik MRTG (Sumbu Y & SVG 100% Selaras Tanpa Ruang Kosong) -->
                    <div class="p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 shadow-inner">
                        <!-- Plot Row: Sumbu Y & SVG Plot Area EXACT SAME HEIGHT -->
                        <div class="flex items-stretch gap-2 h-[155px] sm:h-[175px]">
                            <!-- Sumbu Y Kiri -->
                            <div class="flex items-stretch gap-1.5 select-none shrink-0 h-full">
                                <span class="text-[8px] font-bold text-slate-500 self-center" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                                    Bits/Sec
                                </span>
                                <div class="flex flex-col justify-between items-end h-full text-[8.5px] font-mono font-bold text-slate-600 dark:text-slate-300 leading-none py-0.5">
                                    <span x-text="port.mrtg?.y_labels?.[0] ?? '13.2 M'"></span>
                                    <span x-text="port.mrtg?.y_labels?.[1] ?? '9.9 M'"></span>
                                    <span x-text="port.mrtg?.y_labels?.[2] ?? '6.6 M'"></span>
                                    <span x-text="port.mrtg?.y_labels?.[3] ?? '3.3 M'"></span>
                                    <span x-text="port.mrtg?.y_labels?.[4] ?? '0.0 M'"></span>
                                </div>
                            </div>

                            <!-- Plot Area SVG (Memenuhi Penuh Tinggi Baris Sumbu Y) -->
                            <div class="flex-1 min-w-0 h-full bg-[#f8fafc] dark:bg-[#060c18] border border-slate-700/80 rounded-xs overflow-hidden relative">
                                <svg viewBox="0 0 720 105" preserveAspectRatio="none" class="w-full h-full block">
                                    <!-- Horizontal Grid Lines (5 Baris) -->
                                    <line x1="0" y1="0" x2="720" y2="0" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                    <line x1="0" y1="26.25" x2="720" y2="26.25" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                    <line x1="0" y1="52.5" x2="720" y2="52.5" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                    <line x1="0" y1="78.75" x2="720" y2="78.75" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                    <line x1="0" y1="105" x2="720" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                    <!-- Vertical Grid Lines (8 Pembagi) -->
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
                                    <polygon :points="port.mrtg?.in_polygon ?? ''" fill="#00cc00" fill-opacity="0.95"/>

                                    <!-- Out Traffic Polyline (Solid Blue Line) -->
                                    <polyline :points="port.mrtg?.out_polyline ?? ''" fill="none" stroke="#0000ff" stroke-width="1.6"/>

                                    <!-- Indicator Arrow at 0.0M -->
                                    <polygon points="0,101 6,105 0,109" fill="#ef4444"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Sumbu X: Presisi Sejajar di Bawah SVG -->
                        <div class="flex items-center mt-1">
                            <!-- Spacer Selebar Sumbu Y Kiri -->
                            <div class="w-[50px] shrink-0"></div>
                            <div class="flex-1 flex justify-between items-center text-[8px] sm:text-[8.5px] font-bold font-mono text-slate-500 px-1 select-none">
                                <template x-for="(xLbl, xIdx) in (port.mrtg?.x_labels ?? ['Mon','Sun','Sat','Fri','Thu','Wed','Tue','Mon','Sun'])" :key="xIdx">
                                    <span x-text="xLbl"></span>
                                </template>
                            </div>
                        </div>

                        <!-- 4. Legend Stats In & Out (Menempel Padat di Bawah Grafik) -->
                        <div class="mt-1.5 pt-1.5 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-1.5 text-[9px] font-mono">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-xs bg-[#00cc00] border border-emerald-600 shrink-0"></span>
                                <span class="text-slate-500 font-bold">In:</span>
                                <span class="text-slate-700 dark:text-slate-300">
                                    Cur: <strong class="text-emerald-600 font-bold" x-text="port.mrtg?.current_in"></strong>
                                    &middot; Max: <span x-text="port.mrtg?.max_in"></span>
                                    &middot; Avg: <span x-text="port.mrtg?.avg_in"></span>
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-1 rounded-xs bg-[#0000ff] shrink-0"></span>
                                <span class="text-slate-500 font-bold">Out:</span>
                                <span class="text-slate-700 dark:text-slate-300">
                                    Cur: <strong class="text-blue-600 font-bold" x-text="port.mrtg?.current_out"></strong>
                                    &middot; Max: <span x-text="port.mrtg?.max_out"></span>
                                    &middot; Avg: <span x-text="port.mrtg?.avg_out"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Tombol Aksi Bawah Card PON: Lihat Daftar ONU -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 text-[11px]">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 font-bold border border-blue-200/80 dark:border-blue-800/80">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span x-text="port.user_count + ' ONU Terhubung'"></span>
                        </span>
                    </div>

                    <a :href="'{{ url('/noc/olt/' . $olt->kode_olt . '/pon') }}/' + port.port_index + '/onu'"
                       class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-xs hover:shadow-sm transition-all duration-150 group">
                        <span>Lihat Daftar ONU</span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </template>
    </div>

</div>

<!-- Script Manager Live Time & Auto-Refresh dengan Cleanup & Visibility API -->
<script>
function ponLiveManager(initialPorts) {
    return {
        ports: initialPorts || [],
        currentTime: '',
        lastSynced: '{{ now()->format("H:i:s") }} WIB',
        isFetching: false,
        timer: null,
        pollTimer: null,
        isPageActive: true,
        visibilityHandler: null,
        unloadHandler: null,

        init() {
            this.updateClock();
            this.startLiveEngine();

            // 1. Page Visibility API: Stop proses otomatis saat tab tidak aktif / diminimize
            this.visibilityHandler = () => {
                if (document.hidden) {
                    this.isPageActive = false;
                    this.stopLiveEngine();
                } else {
                    this.isPageActive = true;
                    this.updateClock();
                    this.startLiveEngine();
                    this.fetchFreshTraffic();
                }
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);

            // 2. Cleanup Function saat komponen unmount atau saat pengguna berpindah halaman
            this.unloadHandler = () => {
                this.stopLiveEngine();
            };
            window.addEventListener('beforeunload', this.unloadHandler);
            window.addEventListener('pagehide', this.unloadHandler);
        },

        destroy() {
            this.stopLiveEngine();
            if (this.visibilityHandler) {
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
            if (this.unloadHandler) {
                window.removeEventListener('beforeunload', this.unloadHandler);
                window.removeEventListener('pagehide', this.unloadHandler);
            }
        },

        startLiveEngine() {
            if (this.timer) return;
            // Running clock 1 detik
            this.timer = setInterval(() => {
                if (!document.hidden && this.isPageActive) {
                    this.updateClock();
                }
            }, 1000);

            // Polling hardware ZTE C320 real setiap 30 detik (sesuai cache TTL)
            if (!this.pollTimer) {
                this.pollTimer = setInterval(() => {
                    if (!document.hidden && this.isPageActive) {
                        this.fetchFreshTraffic();
                    }
                }, 30000);
            }
        },

        stopLiveEngine() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        updateClock() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            this.currentTime = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        },

        fetchFreshTraffic() {
            if (this.isFetching) return;
            this.isFetching = true;
            fetch(window.location.pathname + '?format=json', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.ponTelemetry)) {
                    this.ports = data.ponTelemetry;
                    this.lastSynced = data.updated_at || (new Date().toLocaleTimeString() + ' WIB');
                }
            })
            .catch(err => console.debug('Polling OLT real traffic notice:', err))
            .finally(() => {
                this.isFetching = false;
            });
        }
    };
}
</script>
@endsection
