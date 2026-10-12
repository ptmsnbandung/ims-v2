@extends('layouts.app')

@section('title', 'Traffic per Port PON - ' . ($olt->name_olt ?? $olt->kode_olt) . ' - NOC IMS')
@section('page_title', 'Traffic per PON: ' . ($olt->name_olt ?? $olt->kode_olt))

@section('content')
<div class="space-y-4">

    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER (COMMAND CENTER DARK BLUE THEME)          -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4 shadow-sm border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3.5 relative z-10">
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
                        {{ $olt->name_olt ?? 'OLT Server' }} &middot; IP Host: <span class="font-mono text-cyan-300 font-bold">{{ $olt->ip_address ?? '10.10.10.1' }}</span> &middot; POP: <span class="text-slate-200">{{ $olt->nama_pop ?? 'POP MSN' }}</span>
                    </p>
                    
                    <!-- Stats Badges Bar -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2 text-[10px] font-bold">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-800/80 text-slate-200 border border-slate-700/60">
                            <span>Total Port PON: {{ count($ponTelemetry) }} Port</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Status OLT: Online</span>
                        </div>
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
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
        <form method="GET" action="{{ route('noc.olt.pon-traffic', ['kode_olt' => $olt->kode_olt]) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                    </svg>
                    <span>Filter Rentang Traffic:</span>
                </span>

                <!-- Dari Tanggal -->
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Dari Tanggal:</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ $startDate }}"
                           class="px-2.5 py-1 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
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
                           class="px-2.5 py-1 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                    <span class="text-[10px] text-slate-400 font-mono hidden md:inline">
                        ({{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }})
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter Data Traffic</span>
                </button>

                <a href="{{ route('noc.olt.pon-traffic', ['kode_olt' => $olt->kode_olt]) }}" 
                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition"
                   title="Reset Filter ke 7 Hari Terakhir">
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 3. GRID KARTU TRAFFIC PER PORT PON (SETIAP PORT MEMILIKI GRAFIK)    -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
        @foreach($ponTelemetry as $port)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500/40 rounded-xl p-4 shadow-xs transition-all flex flex-col justify-between relative overflow-hidden">
                <!-- Top Accent Line Solid (No White Gradient) -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-indigo-600"></div>

                <div>
                    <!-- Port Header -->
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white font-mono font-bold text-sm flex items-center justify-center shadow-xs shrink-0">
                                P{{ $port['port_index'] }}
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">
                                        {{ $port['port_name'] }}
                                    </h3>
                                    <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">
                                        ({{ $port['interface_name'] }})
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $port['user_count'] }} / {{ $port['max_capacity'] }} ONUs ({{ $port['utilization_pct'] }}%)
                                    </span>
                                    <span>&middot;</span>
                                    <span>Laser: <strong class="text-amber-600 font-mono">{{ $port['tx_power'] }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge & Link to GPON -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Active</span>
                            </span>

                            <a href="{{ route('noc.gpon', ['olt' => $olt->kode_olt, 'port' => 'gpon-onu_1/1/' . $port['port_index']]) }}" 
                               class="p-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-blue-950/40 text-slate-500 dark:text-slate-400 transition"
                               title="Lihat Pemetaan Pelanggan GPON di Port ini">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Current Bandwidth Badges -->
                    <div class="grid grid-cols-2 gap-2 my-2.5 text-[11px]">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-500 text-[10px] font-semibold">Download (Rx):</span>
                            <span class="font-mono font-bold text-cyan-600 dark:text-cyan-400">{{ $port['download_formatted'] }}</span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-slate-500 text-[10px] font-semibold">Upload (Tx):</span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $port['upload_formatted'] }}</span>
                        </div>
                    </div>

                    <!-- Box Frame Grafik MRTG Khusus Port PON Ini -->
                    <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 shadow-inner">
                        <div class="flex items-stretch gap-2 min-h-[140px] sm:min-h-[160px]">
                            <!-- Sumbu Y -->
                            <div class="flex items-center gap-1.5 select-none shrink-0">
                                <span class="text-[8px] font-bold text-slate-500" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                                    Bits/Sec
                                </span>
                                <div class="flex flex-col justify-between items-end h-full text-[8.5px] font-mono font-bold text-slate-600 dark:text-slate-300 leading-none py-0.5">
                                    <span>{{ $port['mrtg']['y_labels'][0] ?? '13.2 M' }}</span>
                                    <span>{{ $port['mrtg']['y_labels'][1] ?? '9.9 M' }}</span>
                                    <span>{{ $port['mrtg']['y_labels'][2] ?? '6.6 M' }}</span>
                                    <span>{{ $port['mrtg']['y_labels'][3] ?? '3.3 M' }}</span>
                                    <span>{{ $port['mrtg']['y_labels'][4] ?? '0.0 M' }}</span>
                                </div>
                            </div>

                            <!-- Plot Area SVG -->
                            <div class="flex-1 min-w-0 flex flex-col h-full">
                                <div class="relative w-full flex-1 min-h-[120px] sm:min-h-[140px] bg-[#f8fafc] dark:bg-[#060c18] border border-slate-700/80 rounded-xs overflow-hidden">
                                    <svg viewBox="0 0 720 105" preserveAspectRatio="none" class="w-full h-full block">
                                        <!-- Grid Lines -->
                                        <line x1="0" y1="0" x2="720" y2="0" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="0" y1="26.25" x2="720" y2="26.25" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="0" y1="52.5" x2="720" y2="52.5" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="0" y1="78.75" x2="720" y2="78.75" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="0" y1="105" x2="720" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                        <!-- Verticals -->
                                        <line x1="90" y1="0" x2="90" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="180" y1="0" x2="180" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="270" y1="0" x2="270" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="360" y1="0" x2="360" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="450" y1="0" x2="450" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="540" y1="0" x2="540" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                        <line x1="630" y1="0" x2="630" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                        <!-- Red Markers -->
                                        <line x1="115.2" y1="0" x2="115.2" y2="105" stroke="#ef4444" stroke-width="1.3"/>
                                        <line x1="633.6" y1="0" x2="633.6" y2="105" stroke="#ef4444" stroke-width="1.3"/>

                                        <!-- In Traffic (Solid Green) -->
                                        <polygon points="{{ $port['mrtg']['in_polygon'] }}" fill="#00cc00" fill-opacity="0.95"/>

                                        <!-- Out Traffic (Solid Blue Line) -->
                                        <polyline points="{{ $port['mrtg']['out_polyline'] }}" fill="none" stroke="#0000ff" stroke-width="1.6"/>

                                        <!-- Indicator Arrow -->
                                        <polygon points="0,101 6,105 0,109" fill="#ef4444"/>
                                    </svg>
                                </div>

                                <!-- Sumbu X -->
                                <div class="flex justify-between items-center text-[8px] sm:text-[8.5px] font-bold font-mono text-slate-500 mt-1 px-1 select-none">
                                    @foreach($port['mrtg']['x_labels'] as $xLbl)
                                        <span>{{ $xLbl }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Legend Stats In & Out -->
                        <div class="mt-2 pt-1.5 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[9px] font-mono">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-xs bg-[#00cc00] border border-emerald-600 shrink-0"></span>
                                <span class="text-slate-500 font-bold">In:</span>
                                <span class="text-slate-700 dark:text-slate-300">
                                    Cur: <strong class="text-emerald-600">{{ $port['mrtg']['current_in'] }}</strong>
                                    &middot; Max: {{ $port['mrtg']['max_in'] }}
                                    &middot; Avg: {{ $port['mrtg']['avg_in'] }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-1 rounded-xs bg-[#0000ff] shrink-0"></span>
                                <span class="text-slate-500 font-bold">Out:</span>
                                <span class="text-slate-700 dark:text-slate-300">
                                    Cur: <strong class="text-blue-600">{{ $port['mrtg']['current_out'] }}</strong>
                                    &middot; Max: {{ $port['mrtg']['max_out'] }}
                                    &middot; Avg: {{ $port['mrtg']['avg_out'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
