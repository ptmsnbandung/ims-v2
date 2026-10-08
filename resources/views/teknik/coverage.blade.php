@extends('layouts.app')

@section('title', 'Cek Coverage ODP - Modul Teknik IMS')
@section('page_title', 'Cek Coverage ODP')

@section('content')
<div 
    x-data="imsTeknikCoverageComponent()" 
    class="space-y-3.5"
>
    <!-- Scoped Dual-Theme CSS -->
    <style>
        .ims-coverage-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 10px -2px rgba(0, 0, 0, 0.05);
            color: #0f172a;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        html.dark .ims-coverage-card {
            background-color: #0f172a !important;
            border-color: #1e293b !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
            color: #f8fafc !important;
        }
        .ims-inner-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        html.dark .ims-inner-box {
            background-color: #020617 !important;
            border-color: #1e293b !important;
        }
        .ims-google-map-canvas {
            width: 100% !important;
            height: 480px !important;
            min-height: 380px !important;
            background: #e2e8f0 !important;
            display: block !important;
            border-radius: 0 0 12px 12px;
        }
        @media (max-width: 767.98px) {
            .ims-google-map-canvas {
                height: 340px !important;
                min-height: 280px !important;
            }
        }
        html.dark .ims-google-map-canvas {
            background: #0b1329 !important;
        }
        .ims-map-type-btn {
            padding: 4px 10px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            color: #475569;
            transition: all 0.15s ease;
        }
        .ims-map-type-btn:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        .ims-map-type-btn.active {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            border-color: #0284c7 !important;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35) !important;
        }
        html.dark .ims-map-type-btn {
            border-color: #334155;
            background-color: #1e293b;
            color: #94a3b8;
        }
        html.dark .ims-map-type-btn:hover {
            background-color: #334155;
            color: #ffffff;
        }
        html.dark .ims-map-type-btn.active {
            background-color: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }
        @keyframes pulseGroundRipple {
            0% { transform: translateX(-50%) scale(0.85); opacity: 0.85; }
            50% { transform: translateX(-50%) scale(1.4); opacity: 0.2; }
            100% { transform: translateX(-50%) scale(0.85); opacity: 0.85; }
        }
        .target-ground-ripple {
            animation: pulseGroundRipple 2s infinite ease-in-out;
        }
        /* Dynamic Dual Theme for Leaflet Popups */
        .leaflet-popup-content-wrapper {
            background: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
        }
        .leaflet-popup-tip {
            background: #ffffff !important;
        }
        .leaflet-container a.leaflet-popup-close-button {
            color: #64748b !important;
        }
        .leaflet-container a.leaflet-popup-close-button:hover {
            color: #0f172a !important;
        }
        html.dark .leaflet-popup-content-wrapper {
            background: #0f172a !important;
            color: #f8fafc !important;
            border: 1px solid #334155 !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6) !important;
        }
        html.dark .leaflet-popup-tip {
            background: #0f172a !important;
        }
        html.dark .leaflet-container a.leaflet-popup-close-button {
            color: #94a3b8 !important;
        }
        html.dark .leaflet-container a.leaflet-popup-close-button:hover {
            color: #ffffff !important;
        }
        .ims-popup-title {
            color: #0f172a !important;
        }
        html.dark .ims-popup-title {
            color: #f8fafc !important;
        }
        .ims-popup-muted {
            color: #64748b !important;
        }
        html.dark .ims-popup-muted {
            color: #94a3b8 !important;
        }
        .ims-popup-badge {
            background: #e0f2fe !important;
            color: #0284c7 !important;
            border: 1px solid #bae6fd !important;
        }
        html.dark .ims-popup-badge {
            background: #082f49 !important;
            color: #38bdf8 !important;
            border-color: #0369a1 !important;
        }
        .ims-popup-divider {
            border-top: 1px solid #e2e8f0 !important;
        }
        html.dark .ims-popup-divider {
            border-top: 1px solid #334155 !important;
        }
        .ims-popup-text {
            color: #334155 !important;
        }
        html.dark .ims-popup-text {
            color: #e2e8f0 !important;
        }
    </style>

    <!-- ── 1. HEADER BANNER WITH BREADCRUMBS & KPI METRICS (OCEANIC TEAL & GREEN GRADIENT) ── -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3 sm:p-3.5 shadow-sm border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 shadow-inner bg-white/10 border border-white/20 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <nav class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest mb-0.5 text-[#c6edf3]">
                        <a href="{{ route('dashboard') }}" class="hover:text-white transition">IMS</a>
                        <span class="text-teal-300/70">&gt;</span>
                        <span>Modul Teknik</span>
                        <span class="text-teal-300/70">&gt;</span>
                        <span class="text-white">GIS Coverage</span>
                    </nav>
                    <h1 class="text-sm sm:text-base font-bold text-white tracking-tight leading-tight" style="color: #FFFFFF !important;">
                        Cek Coverage Lokasi ke ODP Terdekat
                    </h1>
                    <p class="text-[11px] text-[#c6edf3] mt-0.5 leading-relaxed" style="color: #C6EDF3 !important;">
                        Gunakan peta Google Maps untuk memeriksa kelayakan tarikan kabel dropcore fiber optik ke titik ODP terdekat secara presisi.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full md:w-auto">
                <div class="px-3 py-1.5 rounded-lg bg-white/10 border border-white/20 text-left backdrop-blur-xs">
                    <span class="text-[9px] font-bold text-[#c6edf3] uppercase tracking-wider block">Total ODP Terdata</span>
                    <strong class="text-xs sm:text-sm font-bold text-white font-mono block mt-0.5" style="color: #FFFFFF !important;">
                        {{ count($odps) }} Node
                    </strong>
                </div>
                <div class="px-3 py-1.5 rounded-lg bg-emerald-500/20 border border-emerald-400/30 text-left backdrop-blur-xs">
                    <span class="text-[9px] font-bold text-emerald-200 uppercase tracking-wider block">Max Radius Tercover</span>
                    <strong class="text-xs sm:text-sm font-bold text-emerald-300 font-mono block mt-0.5">
                        &le; 300 Meter
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. MAIN 2-COLUMN LAYOUT: SEARCH/RESULTS (LEFT) & MAP CANVAS (RIGHT) ── -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-3.5 items-start">
        
        <!-- ── LEFT PANEL (4 COLS ON DESKTOP) ── -->
        <div class="lg:col-span-4 space-y-3">
            
            <!-- Input Card -->
            <div class="ims-coverage-card p-3 sm:p-3.5 space-y-2.5">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-sky-500 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span>Input Titik Koordinat Target</span>
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Gunakan tombol GPS otomatis atau masukkan titik koordinat (Latitude, Longitude):
                    </p>
                </div>

                <form @submit.prevent="executeCoverageCheck" class="space-y-2">
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="inputCoordinates"
                            placeholder="-6.936988, 107.5904512" 
                            class="w-full h-8.5 pl-8 pr-2.5 rounded-lg text-xs font-mono bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/50"
                            required
                        />
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>

                    <div class="grid grid-cols-2 gap-1.5">
                        <button 
                            type="button" 
                            @click="getCurrentLocation" 
                            :disabled="isDetectingGps"
                            class="h-8 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <span x-show="!isDetectingGps">📍 Gunakan GPS</span>
                            <span x-show="isDetectingGps" class="animate-pulse">⏳ Mencari GPS...</span>
                        </button>

                        <button 
                            type="submit" 
                            class="h-8 px-2.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="text-white font-bold">Periksa Koordinat</span>
                        </button>
                    </div>

                    <!-- Preset Selector from Master Database -->
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                <span>Pilih ODP dari Database OLT:</span>
                            </span>
                            <span class="text-[9px] text-sky-600 dark:text-sky-400 font-mono font-bold">{{ count($odps) }} ODP Aktif</span>
                        </label>
                        <select 
                            @change="selectOdpPreset($event.target.value)" 
                            class="w-full h-8.5 px-2.5 rounded-lg text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/50 cursor-pointer"
                        >
                            <option value="">-- Pilih ODP Master Database ({{ count($odps) }} Titik ODP) --</option>
                            @php
                                $odpsGrouped = collect($odps)->groupBy('olt_name');
                            @endphp
                            @foreach($odpsGrouped as $oltName => $group)
                                <optgroup label="🏢 {{ $oltName }} ({{ count($group) }} Titik ODP)">
                                    @foreach($group as $o)
                                        <option value="{{ $o['lat'] }},{{ $o['lng'] }}|{{ $o['kode_odp'] }}">
                                            {{ $o['name_odp'] }} ({{ $o['kode_odp'] }}) - {{ $o['kode_pon'] }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="p-2 rounded-lg flex items-center gap-1.5 text-[10px] bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 text-sky-800 dark:text-sky-300">
                    <span>💡</span>
                    <span>Format: <b>Latitude, Longitude</b>, pilih ODP database di atas, atau klik pada peta.</span>
                </div>
            </div>

            <!-- Coverage Evaluation Result Card -->
            <template x-if="hasChecked && nearestResult">
                <div class="space-y-2.5">
                    
                    <!-- 1. CASE: COVERED (<= 300m) -->
                    <template x-if="nearestResult.isCovered">
                        <div class="p-3 rounded-xl space-y-2.5 transition-all shadow-xs bg-white dark:bg-slate-900 border-2 border-sky-500 dark:border-sky-500">
                            
                            <!-- Status Headline -->
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 inline-block shadow-xs animate-pulse"></span>
                                <div>
                                    <strong class="text-xs font-bold text-sky-600 dark:text-sky-400 block">
                                        ● Area Tercover Fiber Optic
                                    </strong>
                                    <span class="text-[10px] text-slate-600 dark:text-slate-300 block mt-0.5">
                                        Jaringan kabel distribusi IMS terdeteksi aktif pada radius aman instalasi.
                                    </span>
                                </div>
                            </div>

                            <!-- ODP Node & Calculated Road Dropcore Length -->
                            <div class="ims-inner-box p-2 text-xs flex items-center justify-between font-mono font-bold text-slate-800 dark:text-white">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">⚡ ODP Terdekat:</span>
                                    <strong class="text-slate-900 dark:text-white truncate text-xs" x-text="nearestResult.odp.name_odp || nearestResult.odp.name"></strong>
                                </div>
                                <span class="text-sky-600 dark:text-sky-300 font-bold shrink-0 text-xs">
                                    ~<span x-text="nearestResult.roadDistance"></span>m dropcore
                                </span>
                            </div>

                            <!-- ODP Technical Specifications (matching Database m_odp) -->
                            <div class="ims-inner-box p-2 space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                                <div class="flex justify-between items-center pb-1 border-b border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Nama ODP:</span>
                                    <strong class="text-slate-900 dark:text-white font-bold text-xs" x-text="nearestResult.odp.name_odp || nearestResult.odp.name"></strong>
                                </div>
                                <div class="flex justify-between items-center pb-1 border-b border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Kode ODP:</span>
                                    <span class="text-sky-700 dark:text-sky-300 font-mono font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800/60 text-[10px]" x-text="nearestResult.odp.kode_odp || nearestResult.odp.code"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Port PON Induk:</span>
                                    <strong class="text-slate-800 dark:text-slate-200 font-mono text-xs" x-text="nearestResult.odp.kode_pon || nearestResult.odp.pon_name"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Kapasitas Core / Port:</span>
                                    <span class="font-bold font-mono text-xs" :class="nearestResult.odp.has_slot ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <span x-text="(nearestResult.odp.used_ports ?? 0) + ' / ' + (nearestResult.odp.capacity_odp || nearestResult.odp.total_ports) + ' Port'"></span>
                                        <span x-show="nearestResult.odp.has_slot" class="text-[9px] ml-1 text-emerald-600 dark:text-emerald-400 font-bold">(Ada Slot)</span>
                                        <span x-show="!nearestResult.odp.has_slot" class="text-[9px] ml-1 text-rose-600 dark:text-rose-400 font-bold">(Penuh)</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Jarak Lurus Udara:</span>
                                    <strong class="text-slate-900 dark:text-white font-mono text-xs" x-text="nearestResult.distance + ' Meter'"></strong>
                                </div>
                                <div class="flex justify-between items-center" x-show="nearestResult.odp.note_odp && nearestResult.odp.note_odp !== '-'">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Keterangan:</span>
                                    <span class="text-slate-600 dark:text-slate-300 italic text-[10px]" x-text="nearestResult.odp.note_odp"></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1.5 pt-0.5">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + nearestResult.odp.lat + ',' + nearestResult.odp.lng"
                                    target="_blank" 
                                    class="flex-1 h-8 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center justify-center gap-1 shadow-xs cursor-pointer"
                                >
                                    <span>🧭 Buka Maps</span>
                                </a>
                                <button 
                                    type="button" 
                                    @click="copyCoordinates(nearestResult.odp.lat + ', ' + nearestResult.odp.lng)" 
                                    class="h-8 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition cursor-pointer"
                                    title="Salin Koordinat ODP"
                                >
                                    <span>📋 Salin</span>
                                </button>
                                <a 
                                    href="{{ route('teknik.pendaftaran') }}" 
                                    class="h-8 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer shadow-xs"
                                    title="Lanjut Form Registrasi Pasang Baru"
                                >
                                    <span>⚡ Pasang Baru</span>
                                </a>
                            </div>
                        </div>
                    </template>

                    <!-- 2. CASE: OUT OF COVERAGE (> 300m) -->
                    <template x-if="!nearestResult.isCovered">
                        <div class="p-3 rounded-xl space-y-2 bg-white dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 shadow-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 inline-block"></span>
                                <div>
                                    <strong class="text-xs font-bold text-slate-900 dark:text-white block">
                                        Di Luar Radius Coverage (&gt; 300m)
                                    </strong>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">
                                        Jarak ODP terdekat adalah <b class="text-rose-600 dark:text-rose-400 font-mono" x-text="nearestResult.distance + ' meter'"></b>.
                                    </span>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 p-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                Lokasi ini membutuhkan penarikan kabel feeder tambahan atau pemasangan tiang/ODP baru sebelum dapat dilakukan aktivasi layanan.
                            </p>
                            <div class="flex gap-1.5">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + nearestResult.odp.lat + ',' + nearestResult.odp.lng"
                                    target="_blank" 
                                    class="flex-1 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1"
                                >
                                    <span>Lihat Lokasi ODP Terdekat (<span x-text="(nearestResult.odp.name_odp || nearestResult.odp.name) + ' (' + (nearestResult.odp.kode_odp || nearestResult.odp.code) + ')'"></span>)</span>
                                </a>
                            </div>
                        </div>
                    </template>

                </div>
            </template>

        </div>

        <!-- ── RIGHT PANEL (8 COLS ON DESKTOP): GOOGLE MAPS GIS CANVAS ── -->
        <div class="lg:col-span-8">
            <div class="ims-coverage-card overflow-hidden flex flex-col">
                
                <!-- Map Header Controls Bar -->
                <div class="px-3.5 py-2 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5 text-xs bg-slate-50 dark:bg-slate-900">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-500 inline-block animate-pulse"></span>
                        <strong class="font-bold text-slate-900 dark:text-white text-xs">
                            Peta Live Network Fiber FTTH
                        </strong>
                        <span class="text-[9px] px-1.5 py-0.5 rounded-full font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                            Google GIS
                        </span>
                    </div>

                    <!-- Map Layers & View Controls -->
                    <div class="flex items-center gap-1 flex-wrap">
                        <button 
                            type="button" 
                            @click="setMapMode('roadmap')" 
                            :class="mapMode === 'roadmap' ? 'active' : ''"
                            class="ims-map-type-btn"
                        >
                            🗺️ Peta Jalan
                        </button>
                        <button 
                            type="button" 
                            @click="setMapMode('hybrid')" 
                            :class="mapMode === 'hybrid' ? 'active' : ''"
                            class="ims-map-type-btn"
                        >
                            🛰️ Satelit
                        </button>
                        <button 
                            type="button" 
                            @click="setMapMode('terrain')" 
                            :class="mapMode === 'terrain' ? 'active' : ''"
                            class="ims-map-type-btn"
                        >
                            ⛰️ Medan
                        </button>
                        <button 
                            type="button" 
                            @click="resetMapView" 
                            class="px-2 py-1 rounded-md text-sky-600 dark:text-sky-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-[10px] font-bold transition flex items-center gap-1 cursor-pointer"
                        >
                            <span>🔄 Fit All</span>
                        </button>
                    </div>
                </div>

                <!-- GIS Map Canvas Div -->
                <div id="ims-google-map-canvas" class="ims-google-map-canvas"></div>

                <!-- Map Footer Legend -->
                <div class="px-3.5 py-2 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5 text-[10px] text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 border border-white dark:border-slate-900 inline-block shadow-xs"></span>
                            <span class="text-slate-700 dark:text-slate-300 font-medium">ODP Ada Slot</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 border border-white dark:border-slate-900 inline-block shadow-xs"></span>
                            <span class="text-slate-700 dark:text-slate-300 font-medium">ODP Port Penuh</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-red-600 border border-white inline-block text-[8px] text-white flex items-center justify-center shadow-xs">📍</span>
                            <span class="text-slate-700 dark:text-slate-300 font-medium">Titik Target</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-1 text-sky-600 dark:text-sky-400 font-semibold">
                        <span class="w-3 h-0.5 bg-sky-500 inline-block border-t border-dashed border-sky-500"></span>
                        <span>Garis Biru = Jalur kabel dropcore fiber optik</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── 4. CLIENT JAVASCRIPT: LEAFLET GIS MAP, HAVERSINE & OSRM ROUTING ── -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('imsTeknikCoverageComponent', () => ({
            allOdps: @json($odps),
            inputCoordinates: @json($initialCoord),
            mapInstance: null,
            odpMarkersLayer: null,
            odpMarkersMap: {},
            userMarkerLayer: null,
            connectionLineLayer: null,
            hasChecked: false,
            isDetectingGps: false,
            nearestResult: null,
            mapMode: 'roadmap',
            tileLayers: {},

            init() {
                this.loadLeafletAssets();
            },

            loadLeafletAssets() {
                if (typeof L === 'undefined') {
                    if (!document.getElementById('leaflet-css-ims')) {
                        const link = document.createElement('link');
                        link.id = 'leaflet-css-ims';
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(link);
                    }

                    if (!document.getElementById('leaflet-js-ims')) {
                        const script = document.createElement('script');
                        script.id = 'leaflet-js-ims';
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.onload = () => {
                            setTimeout(() => {
                                this.initMap();
                                if (this.inputCoordinates) {
                                    this.executeCoverageCheck();
                                }
                            }, 150);
                        };
                        document.head.appendChild(script);
                    }
                } else {
                    setTimeout(() => {
                        this.initMap();
                        if (this.inputCoordinates) {
                            this.executeCoverageCheck();
                        }
                    }, 150);
                }
            },

            initMap() {
                const mapEl = document.getElementById('ims-google-map-canvas');
                if (!mapEl || typeof L === 'undefined') return;

                if (this.mapInstance) {
                    this.mapInstance.remove();
                    this.mapInstance = null;
                }

                let defaultLat = -6.936988;
                let defaultLng = 107.5904512;

                if (this.allOdps && this.allOdps.length > 0) {
                    defaultLat = this.allOdps[0].lat;
                    defaultLng = this.allOdps[0].lng;
                }

                this.mapInstance = L.map('ims-google-map-canvas', {
                    center: [defaultLat, defaultLng],
                    zoom: 15,
                    preferCanvas: true,
                    zoomControl: true,
                    attributionControl: false
                });

                // Google Maps Roadmap
                this.tileLayers['roadmap'] = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                    tileSize: 256
                });

                // Google Maps Hybrid Satellite
                this.tileLayers['hybrid'] = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                    tileSize: 256
                });

                // Google Maps Terrain
                this.tileLayers['terrain'] = L.tileLayer('https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                    tileSize: 256
                });

                this.tileLayers['roadmap'].addTo(this.mapInstance);
                this.mapMode = 'roadmap';

                this.odpMarkersLayer = L.layerGroup().addTo(this.mapInstance);
                this.renderAllOdpMarkers();

                setTimeout(() => {
                    if (this.mapInstance) {
                        this.mapInstance.invalidateSize();
                    }
                }, 350);

                // Map Click Listener to pick coordinates interactively
                this.mapInstance.on('click', (e) => {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    this.inputCoordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    this.executeCoverageCheck();
                });
            },

            setMapMode(mode) {
                if (!this.mapInstance || !this.tileLayers[mode]) return;
                if (this.tileLayers[this.mapMode]) {
                    this.mapInstance.removeLayer(this.tileLayers[this.mapMode]);
                }
                this.mapMode = mode;
                this.tileLayers[mode].addTo(this.mapInstance);
            },

            renderAllOdpMarkers() {
                if (!this.odpMarkersLayer || typeof L === 'undefined') return;
                this.odpMarkersLayer.clearLayers();
                this.odpMarkersMap = {};

                const markers = [];
                this.allOdps.forEach((odp) => {
                    const isAvailable = odp.has_slot;
                    const pinColor = isAvailable ? '#0284c7' : '#ef4444';

                    const customIcon = L.divIcon({
                        className: 'custom-odp-pin',
                        html: `
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: ${pinColor}; border: 2.5px solid #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                <svg style="width: 13px; height: 13px; color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        `,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });

                    const marker = L.marker([odp.lat, odp.lng], { icon: customIcon });

                    marker.bindPopup(`
                        <div style="font-family: inherit; padding: 4px; min-width: 210px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span class="ims-popup-badge" style="font-size: 11px; font-weight: 800; font-family: monospace; padding: 2px 6px; border-radius: 4px;">${odp.kode_odp || odp.code}</span>
                                <span style="font-size: 10px; font-weight: 800; color: ${isAvailable ? '#059669' : '#dc2626'};">${isAvailable ? '● ADA SLOT' : '● PENUH'}</span>
                            </div>
                            <div class="ims-popup-title" style="font-size: 13px; font-weight: 800; margin: 4px 0 6px;">${odp.name_odp || odp.name}</div>
                            <div class="ims-popup-divider ims-popup-muted" style="font-size: 11px; line-height: 1.5; padding-top: 5px;">
                                <div class="ims-popup-text">Port: <b class="ims-popup-title">${odp.used_ports}/${odp.capacity_odp || odp.total_ports} Port</b> • PON: <b class="ims-popup-title">${odp.kode_pon || odp.pon_name}</b></div>
                                ${odp.note_odp && odp.note_odp !== '-' ? `<div class="ims-popup-muted" style="font-size: 10px; margin-top: 2px;">Ket: ${odp.note_odp}</div>` : ''}
                            </div>
                            <div class="ims-popup-divider" style="margin-top: 8px; padding-top: 6px;">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=${odp.lat},${odp.lng}" target="_blank" style="display: block; text-align: center; text-decoration: none; background: #0284c7; color: #fff; padding: 5px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">🧭 Rute Google Maps &rarr;</a>
                            </div>
                        </div>
                    `);

                    this.odpMarkersLayer.addLayer(marker);
                    this.odpMarkersMap[odp.kode_odp || odp.code] = marker;
                    markers.push(marker);
                });

                if (markers.length > 0 && this.mapInstance && !this.hasChecked) {
                    this.mapInstance.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
                }
            },

            selectOdpPreset(val) {
                if (!val) return;
                const parts = val.split('|');
                const coordStr = parts[0];
                const odpCode = parts[1];
                this.inputCoordinates = coordStr;
                const coords = this.parseCoordinates(coordStr);
                if (coords && this.mapInstance) {
                    this.mapInstance.flyTo([coords.lat, coords.lng], 17, { duration: 0.8 });
                    if (this.odpMarkersMap[odpCode]) {
                        setTimeout(() => {
                            this.odpMarkersMap[odpCode].openPopup();
                        }, 450);
                    }
                }
                this.executeCoverageCheck();
            },

            focusOdpFromDb(odpCode, lat, lng) {
                const latNum = parseFloat(lat);
                const lngNum = parseFloat(lng);
                this.inputCoordinates = `${latNum.toFixed(6)}, ${lngNum.toFixed(6)}`;
                if (this.mapInstance) {
                    this.mapInstance.flyTo([latNum, lngNum], 17, { duration: 0.8 });
                    if (this.odpMarkersMap[odpCode]) {
                        setTimeout(() => {
                            this.odpMarkersMap[odpCode].openPopup();
                        }, 450);
                    }
                }
                this.executeCoverageCheck();
                window.scrollTo({ top: 80, behavior: 'smooth' });
            },

            parseCoordinates(input) {
                if (!input) return null;
                const matches = input.match(/[-+]?([0-9]*\.[0-9]+|[0-9]+)/g);
                if (matches && matches.length >= 2) {
                    const lat = parseFloat(matches[0]);
                    const lng = parseFloat(matches[1]);
                    if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                        return { lat, lng };
                    }
                }
                return null;
            },

            calculateDistanceMeters(lat1, lon1, lat2, lon2) {
                const R = 6371000;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon/2) * Math.sin(dLon/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return Math.round(R * c);
            },

            async executeCoverageCheck() {
                const coords = this.parseCoordinates(this.inputCoordinates);
                if (!coords) {
                    alert('Format koordinat tidak valid. Silakan masukkan format: Latitude, Longitude (contoh: -6.936988, 107.5904512)');
                    return;
                }

                const userLat = coords.lat;
                const userLng = coords.lng;

                const odpList = this.allOdps.map(odp => {
                    const dist = this.calculateDistanceMeters(userLat, userLng, odp.lat, odp.lng);
                    return {
                        odp: odp,
                        distance: dist,
                        isCovered: dist <= 300,
                        roadDistance: Math.round(dist * 1.25)
                    };
                }).sort((a, b) => a.distance - b.distance);

                if (odpList.length > 0) {
                    this.nearestResult = odpList[0];
                    this.hasChecked = true;
                    await this.drawConnectionToOdp(userLat, userLng, this.nearestResult);
                }
            },

            async drawConnectionToOdp(userLat, userLng, result) {
                if (!this.mapInstance || typeof L === 'undefined') return;

                if (this.userMarkerLayer) {
                    this.mapInstance.removeLayer(this.userMarkerLayer);
                }
                if (this.connectionLineLayer) {
                    this.mapInstance.removeLayer(this.connectionLineLayer);
                }

                // Target User Pin (Google Maps Authentic Red Pin)
                const userIcon = L.divIcon({
                    className: 'google-maps-target-pin',
                    html: `
                        <div style="position: relative; width: 34px; height: 46px; display: flex; justify-content: center;">
                            <!-- Ground Ripple Effect -->
                            <div class="target-ground-ripple" style="position: absolute; bottom: -2px; left: 50%; width: 22px; height: 10px; border-radius: 50%; background: rgba(234, 67, 53, 0.35); border: 1.5px solid #EA4335;"></div>
                            
                            <!-- Google Maps Red Pin SVG -->
                            <svg width="34" height="46" viewBox="0 0 34 46" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.45)); z-index: 10; position: relative;">
                                <path d="M17 0C7.611 0 0 7.611 0 17C0 29.75 17 46 17 46C17 46 34 29.75 34 17C34 7.611 26.389 0 17 0Z" fill="#EA4335"/>
                                <path d="M17 1C8.163 1 1 8.163 1 17C1 28.5 17 44.5 17 44.5C17 44.5 33 28.5 33 17C33 8.163 25.837 1 17 1Z" stroke="#B31412" stroke-width="1.2"/>
                                <circle cx="17" cy="16" r="6" fill="#7A0000"/>
                                <circle cx="17" cy="16" r="2.5" fill="#FFFFFF"/>
                            </svg>
                        </div>
                    `,
                    iconSize: [34, 46],
                    iconAnchor: [17, 46],
                    popupAnchor: [0, -46]
                });

                const odpName = result.odp.name_odp || result.odp.name;
                const odpCode = result.odp.kode_odp || result.odp.code;

                this.userMarkerLayer = L.marker([userLat, userLng], { icon: userIcon }).addTo(this.mapInstance);
                this.userMarkerLayer.bindPopup(`
                    <div style="font-family: inherit; padding: 4px; min-width: 180px;">
                        <div style="font-size: 10px; font-weight: 800; color: #ea4335; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
                            <span>📍 LOKASI TARGET</span>
                        </div>
                        <div class="ims-popup-title" style="font-size: 12px; font-weight: 800; margin: 2px 0; font-family: monospace;">${userLat.toFixed(6)}, ${userLng.toFixed(6)}</div>
                        <div style="font-size: 11px; color: ${result.isCovered ? '#0284c7' : '#ea4335'}; font-weight: 700; margin-top: 4px;">
                            ${result.isCovered ? '⚡ Tercover Fiber Optic' : '✕ Di Luar Radius (> 300m)'}
                        </div>
                        <div class="ims-popup-muted" style="font-size: 10.5px; margin-top: 2px;">Terhubung ke <b class="ims-popup-title">${odpName}</b> (${odpCode}) ~${result.distance}m</div>
                    </div>
                `, { offset: [0, -42] }).openPopup();

                const odp = result.odp;

                // Real Street Route via OSRM
                let routeCoords = [];
                const routingUrls = [
                    `https://routing.openstreetmap.de/routed-foot/route/v1/foot/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson`,
                    `https://router.project-osrm.org/route/v1/driving/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson`
                ];

                for (const url of routingUrls) {
                    try {
                        const ctrl = new AbortController();
                        const timeoutId = setTimeout(() => ctrl.abort(), 3500);
                        const res = await fetch(url, { signal: ctrl.signal });
                        clearTimeout(timeoutId);
                        if (res.ok) {
                            const data = await res.json();
                            if (data.routes && data.routes[0] && data.routes[0].geometry && data.routes[0].geometry.coordinates && data.routes[0].geometry.coordinates.length >= 2) {
                                routeCoords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                if (data.routes[0].distance) {
                                    result.roadDistance = Math.round(data.routes[0].distance);
                                }
                                break;
                            }
                        }
                    } catch (e) {
                        // Fallback next URL
                    }
                }

                if (routeCoords && routeCoords.length >= 2) {
                    routeCoords.unshift([userLat, userLng]);
                    routeCoords.push([odp.lat, odp.lng]);
                } else {
                    routeCoords = [
                        [userLat, userLng],
                        [odp.lat, odp.lng]
                    ];
                }

                this.connectionLineLayer = L.layerGroup().addTo(this.mapInstance);

                // Cyan glow line
                L.polyline(routeCoords, {
                    color: '#38bdf8',
                    weight: 6,
                    opacity: 0.5,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(this.connectionLineLayer);

                // Blue dashed dropcore line
                L.polyline(routeCoords, {
                    color: '#0284c7',
                    weight: 3.5,
                    dashArray: '10, 8',
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(this.connectionLineLayer);

                const bounds = L.latLngBounds(routeCoords);
                this.mapInstance.fitBounds(bounds.pad(0.35), { animate: true, duration: 0.8 });
            },

            getCurrentLocation() {
                if (!navigator.geolocation) {
                    alert('Geolokasi GPS tidak didukung di browser ini.');
                    return;
                }
                this.isDetectingGps = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.isDetectingGps = false;
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        this.inputCoordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        this.executeCoverageCheck();
                    },
                    (err) => {
                        this.isDetectingGps = false;
                        alert('Gagal mendeteksi lokasi GPS. Silakan masukkan koordinat secara manual.');
                    },
                    { timeout: 8000, enableHighAccuracy: true }
                );
            },

            focusTicketCoordinate(lat, lng) {
                this.inputCoordinates = `${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
                this.executeCoverageCheck();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            resetMapView() {
                if (this.allOdps && this.allOdps.length > 0 && this.mapInstance && typeof L !== 'undefined') {
                    const bounds = L.latLngBounds(this.allOdps.map(o => [o.lat, o.lng]));
                    this.mapInstance.fitBounds(bounds.pad(0.15), { animate: true });
                }
            },

            copyCoordinates(text) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Koordinat disalin ke clipboard: ' + text);
                });
            }
        }));
    });
</script>
@endsection
