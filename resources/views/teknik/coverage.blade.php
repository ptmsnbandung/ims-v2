@extends('layouts.app')

@section('title', 'Cek Coverage ODP - Modul Teknik IMS')
@section('page_title', 'Cek Coverage ODP Presisi')

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
            height: 540px !important;
            min-height: 420px !important;
            background: #e2e8f0 !important;
            display: block !important;
            border-radius: 0 0 12px 12px;
        }
        @media (max-width: 767.98px) {
            .ims-google-map-canvas {
                height: 380px !important;
                min-height: 320px !important;
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

    <!-- ── 1. HEADER BANNER WITH BREADCRUMBS & KPI METRICS ── -->
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
                        <span class="text-white">GIS Coverage Presisi</span>
                    </nav>
                    <h1 class="text-sm sm:text-base font-bold text-white tracking-tight leading-tight" style="color: #FFFFFF !important;">
                        Cek Coverage Lokasi ke ODP Terdekat (Akurasi Presisi FTTH)
                    </h1>
                    <p class="text-[11px] text-[#c6edf3] mt-0.5 leading-relaxed" style="color: #C6EDF3 !important;">
                        Periksa kelayakan tarikan kabel dropcore fiber optik tiang ke tiang, estimasi redaman optik dBm, dan ketersediaan port ODP.
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
        
        <!-- ── LEFT PANEL (5 COLS ON DESKTOP) ── -->
        <div class="lg:col-span-5 space-y-3">
            
            <!-- Input Card -->
            <div class="ims-coverage-card p-3 sm:p-3.5 space-y-2.5">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-sky-500 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <span>Titik Koordinat / Alamat Target</span>
                        </span>
                        <span class="text-[10px] text-sky-600 dark:text-sky-400 font-normal">💡 Pin dapat digeser di peta</span>
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Masukkan koordinat (Lat, Lng), nama jalan/alamat, atau klik langsung pada peta:
                    </p>
                </div>

                <form @submit.prevent="executeCoverageCheck" class="space-y-2">
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="inputCoordinates"
                            placeholder="-6.936988, 107.5904512 atau Jl. Sukamulya Bandung" 
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
                            <span x-show="!isDetectingGps">📍 GPS Lokasi Saya</span>
                            <span x-show="isDetectingGps" class="animate-pulse">⏳ Mencari GPS...</span>
                        </button>

                        <button 
                            type="submit" 
                            :disabled="isSearchingLocation"
                            class="h-8 px-2.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer shadow-xs disabled:opacity-50"
                        >
                            <svg x-show="!isSearchingLocation" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span x-show="!isSearchingLocation" class="text-white font-bold">Cek Coverage</span>
                            <span x-show="isSearchingLocation" class="text-white font-bold animate-pulse">Menganalisis...</span>
                        </button>
                    </div>

                    <!-- Geolocation Accuracy & Detected Address Status Banner -->
                    <template x-if="detectedAddressName">
                        <div class="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 text-[10.5px] text-sky-800 dark:text-sky-300 flex items-start gap-1.5">
                            <span class="text-sky-500 font-bold">📍</span>
                            <div class="flex-1 min-w-0">
                                <span class="font-bold text-slate-900 dark:text-white block text-[10px] uppercase tracking-wide">Lokasi Titik Terpilih:</span>
                                <span class="truncate block text-[10px] text-slate-700 dark:text-slate-300" x-text="detectedAddressName"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="gpsAccuracyMeters && gpsAccuracyMeters > 30">
                        <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-[10.5px] text-amber-800 dark:text-amber-300 flex items-start gap-1.5">
                            <span class="text-amber-500">⚠️</span>
                            <div>
                                <strong>Akurasi Browser/Wi-Fi: ±<span x-text="gpsAccuracyMeters"></span>m</strong>
                                <span class="block text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">
                                    Browser mendeteksi perkiraan jaringan. <b>Geser pin merah 📍 di peta</b> langsung ke atas atap rumah Anda untuk titik 100% presisi.
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Preset Selector from Master Database -->
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                <span>Pilih ODP dari Database:</span>
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

                <div class="p-2 rounded-lg flex items-center gap-1.5 text-[10px] bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                    <span>💡</span>
                    <span>Format: <b>Latitude, Longitude</b>, atau klik/geser pin langsung pada peta untuk titik presisi.</span>
                </div>
            </div>

            <!-- Coverage Evaluation Result Card -->
            <template x-if="hasChecked && selectedOdpResult">
                <div class="space-y-3">
                    
                    <!-- 1. CASE: COVERED (<= 300m) -->
                    <template x-if="selectedOdpResult.isCovered">
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-3 transition-all shadow-xs bg-white dark:bg-slate-900 border-2"
                             :class="selectedOdpResult.coverageLevel === 'excellent' ? 'border-emerald-500/80 dark:border-emerald-500/80' : (selectedOdpResult.coverageLevel === 'good' ? 'border-sky-500/80 dark:border-sky-500/80' : 'border-amber-500/80 dark:border-amber-500/80')">
                            
                            <!-- Header Status & Quality Badge -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="relative flex h-3 w-3 mt-0.5 shrink-0">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                              :class="selectedOdpResult.coverageLevel === 'excellent' ? 'bg-emerald-400' : (selectedOdpResult.coverageLevel === 'good' ? 'bg-sky-400' : 'bg-amber-400')"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3"
                                              :class="selectedOdpResult.coverageLevel === 'excellent' ? 'bg-emerald-500' : (selectedOdpResult.coverageLevel === 'good' ? 'bg-sky-500' : 'bg-amber-500')"></span>
                                    </span>
                                    <div>
                                        <strong class="text-xs sm:text-sm font-extrabold tracking-tight block leading-tight"
                                                :class="selectedOdpResult.coverageLevel === 'excellent' ? 'text-emerald-700 dark:text-emerald-400' : (selectedOdpResult.coverageLevel === 'good' ? 'text-sky-700 dark:text-sky-400' : 'text-amber-700 dark:text-amber-400')">
                                            Area Tercover Fiber Optic
                                        </strong>
                                        <span class="text-[10.5px] text-slate-500 dark:text-slate-400 block mt-0.5 leading-snug" x-text="selectedOdpResult.coverageNote"></span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-md font-mono uppercase tracking-wider shadow-2xs shrink-0"
                                      :class="selectedOdpResult.coverageLevel === 'excellent' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : (selectedOdpResult.coverageLevel === 'good' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800')">
                                    <span x-text="selectedOdpResult.coverageLabel"></span>
                                </span>
                            </div>

                            <!-- Route Mode Segmented Switcher -->
                            <div class="bg-slate-100 dark:bg-slate-950 p-1 rounded-lg grid grid-cols-2 gap-1 text-[11px] border border-slate-200/70 dark:border-slate-800/70">
                                <button 
                                    type="button" 
                                    @click="setRoutingMode('street')"
                                    :class="routingMode === 'street' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 font-bold shadow-xs border border-slate-200 dark:border-slate-800' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                    class="py-1.5 px-2 rounded-md transition text-center flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <span>🛣️ Rute Jalan</span>
                                    <span class="font-mono text-[10px] font-bold" x-text="'(' + selectedOdpResult.roadDistance + 'm)'"></span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="setRoutingMode('direct')"
                                    :class="routingMode === 'direct' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 font-bold shadow-xs border border-slate-200 dark:border-slate-800' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                    class="py-1.5 px-2 rounded-md transition text-center flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <span>⚡ Span Lurus</span>
                                    <span class="font-mono text-[10px] font-bold" x-text="'(' + selectedOdpResult.distance + 'm)'"></span>
                                </button>
                            </div>

                            <!-- Technical Specs Table -->
                            <div class="bg-slate-50/80 dark:bg-slate-950/70 rounded-xl p-3 border border-slate-200/80 dark:border-slate-800 space-y-2 text-xs">
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Nama ODP:</span>
                                    <strong class="text-slate-900 dark:text-white font-bold text-xs" x-text="selectedOdpResult.odp.name_odp || selectedOdpResult.odp.name"></strong>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Kode ODP:</span>
                                    <span class="text-sky-700 dark:text-sky-300 font-mono font-extrabold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-950/80 border border-sky-200 dark:border-sky-800 text-[10.5px]" x-text="selectedOdpResult.odp.kode_odp || selectedOdpResult.odp.code"></span>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Port PON Induk:</span>
                                    <strong class="text-slate-800 dark:text-slate-200 font-mono text-[11.5px]" x-text="selectedOdpResult.odp.kode_pon || selectedOdpResult.odp.pon_name"></strong>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Kapasitas Core / Port:</span>
                                    <span class="font-bold font-mono text-[11.5px] flex items-center gap-1.5" :class="selectedOdpResult.odp.has_slot ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <span x-text="(selectedOdpResult.odp.used_ports ?? 0) + ' / ' + (selectedOdpResult.odp.capacity_odp || selectedOdpResult.odp.total_ports) + ' Port'"></span>
                                        <span x-show="selectedOdpResult.odp.has_slot" class="text-[9.5px] px-1.5 py-0.2 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800">Ada Slot</span>
                                        <span x-show="!selectedOdpResult.odp.has_slot" class="text-[9.5px] px-1.5 py-0.2 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-bold border border-rose-200 dark:border-rose-800">Penuh</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Jarak Rute Jalan:</span>
                                    <strong class="text-slate-900 dark:text-white font-mono text-[11.5px]" x-text="selectedOdpResult.roadDistance + ' Meter'"></strong>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Jarak Lurus (Span Udara):</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-mono text-[11px]" x-text="selectedOdpResult.distance + ' Meter'"></span>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]" x-text="routingMode === 'street' ? 'Estimasi Kabel (Rute Jalan):' : 'Estimasi Kabel (Span + Slack):'"></span>
                                    <strong class="text-sky-600 dark:text-sky-400 font-mono font-extrabold text-[11.5px]" x-text="'~' + selectedOdpResult.dropcoreDistance + ' Meter'"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Estimasi Redaman Optik:</span>
                                    <span class="font-mono font-bold text-[11.5px] text-emerald-600 dark:text-emerald-400" x-text="selectedOdpResult.opticalPowerEstimate"></span>
                                </div>

                                <!-- Dedicated Clean Note Box -->
                                <div class="pt-2 mt-1 border-t border-slate-200/80 dark:border-slate-800/80" x-show="selectedOdpResult.odp.note_odp && selectedOdpResult.odp.note_odp !== '-'">
                                    <span class="text-slate-400 dark:text-slate-500 block text-[9.5px] uppercase font-bold tracking-wider mb-1">Catatan ODP:</span>
                                    <div class="text-[10.5px] text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800/80 leading-relaxed break-words font-medium" x-text="selectedOdpResult.odp.note_odp"></div>
                                </div>
                            </div>

                            <!-- Action Buttons (Clean 2-Row Layout, No Clipping) -->
                            <div class="space-y-2 pt-1">
                                <div class="grid grid-cols-2 gap-2">
                                    <a 
                                        :href="'https://www.google.com/maps/dir/?api=1&destination=' + selectedOdpResult.odp.lat + ',' + selectedOdpResult.odp.lng"
                                        target="_blank" 
                                        class="h-9 px-3 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer"
                                    >
                                        <span>🧭 Buka Maps</span>
                                    </a>
                                    <a 
                                        href="{{ route('teknik.pendaftaran') }}" 
                                        class="h-9 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                                        title="Lanjut Form Registrasi Pasang Baru"
                                    >
                                        <span>⚡ Pasang Baru</span>
                                    </a>
                                </div>
                                <button 
                                    type="button" 
                                    @click="copyCoordinates(selectedOdpResult.odp.lat + ', ' + selectedOdpResult.odp.lng)" 
                                    class="w-full h-8.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                                    title="Salin Koordinat ODP"
                                >
                                    <span>📋 Salin Koordinat ODP</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- 2. CASE: OUT OF COVERAGE (> 300m) -->
                    <template x-if="!selectedOdpResult.isCovered">
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-3 bg-white dark:bg-slate-900 border-2 border-rose-400 dark:border-rose-600 shadow-xs">
                            <div class="flex items-start gap-2.5">
                                <span class="w-3 h-3 rounded-full bg-rose-500 inline-block shadow-xs mt-0.5 shrink-0"></span>
                                <div>
                                    <strong class="text-xs sm:text-sm font-bold text-rose-600 dark:text-rose-400 block leading-tight">
                                        Di Luar Radius Coverage (&gt; 300m)
                                    </strong>
                                    <span class="text-[10.5px] text-slate-500 dark:text-slate-400 block mt-0.5">
                                        Jarak rute jalan ke ODP terdekat adalah <b class="text-rose-600 dark:text-rose-400 font-mono" x-text="selectedOdpResult.roadDistance + ' meter'"></b> (jarak lurus: <span x-text="selectedOdpResult.distance"></span>m, kabel ~<span x-text="selectedOdpResult.dropcoreDistance"></span>m).
                                    </span>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 leading-relaxed">
                                ⚠️ Lokasi ini melebihi batas standar redaman kabel dropcore FTTH. Memerlukan penarikan kabel feeder tambahan atau instalasi tiang/ODP baru sebelum dapat diaktivasi.
                            </p>
                            <div class="space-y-2 pt-1">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + selectedOdpResult.odp.lat + ',' + selectedOdpResult.odp.lng"
                                    target="_blank" 
                                    class="w-full h-8.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                >
                                    <span>🧭 Buka Google Maps ODP Terdekat</span>
                                </a>
                            </div>
                        </div>
                    </template>

                    <!-- 3. TOP 5 NEAREST ODP CANDIDATES LIST -->
                    <div class="ims-coverage-card p-3 sm:p-3.5 space-y-2.5">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                <span>5 ODP Terdekat di Sekitar Target:</span>
                            </span>
                            <label class="flex items-center gap-1 text-[10.5px] text-slate-600 dark:text-slate-400 cursor-pointer">
                                <input type="checkbox" x-model="filterOnlyAvailable" class="rounded text-sky-600 focus:ring-sky-500 w-3 h-3 cursor-pointer">
                                <span>Hanya Ada Slot</span>
                            </label>
                        </div>

                        <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <template x-for="(cand, idx) in filteredCandidates" :key="cand.odp.kode_odp || cand.odp.code">
                                <div 
                                    @click="selectCandidateOdp(cand)"
                                    :class="selectedOdpResult && (selectedOdpResult.odp.kode_odp === cand.odp.kode_odp || selectedOdpResult.odp.code === cand.odp.code) ? 'border-sky-500 bg-sky-50/60 dark:bg-sky-950/40 ring-1 ring-sky-500' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-950/50'"
                                    class="p-2.5 rounded-lg border text-xs flex items-center justify-between gap-2 cursor-pointer transition"
                                >
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0"
                                              :class="cand.isCovered ? (cand.odp.has_slot ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300') : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                                              x-text="'#' + (idx + 1)"></span>
                                        <div class="truncate">
                                            <div class="font-bold text-slate-900 dark:text-white truncate text-[11px]" x-text="cand.odp.name_odp || cand.odp.name"></div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono" x-text="cand.odp.kode_odp || cand.odp.code"></div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-mono font-bold text-[11px]" :class="cand.isCovered ? 'text-sky-600 dark:text-sky-400' : 'text-slate-500'" x-text="'~' + cand.dropcoreDistance + 'm'"></div>
                                        <div class="text-[9.5px] font-bold" :class="cand.odp.has_slot ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                            <span x-text="(cand.odp.used_ports ?? 0) + '/' + (cand.odp.capacity_odp || cand.odp.total_ports) + (cand.odp.has_slot ? ' Slot' : ' Penuh')"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </template>

        </div>

        <!-- ── RIGHT PANEL (7 COLS ON DESKTOP): GOOGLE MAPS GIS CANVAS ── -->
        <div class="lg:col-span-7">
            <div class="ims-coverage-card overflow-hidden flex flex-col">
                
                <!-- Map Header Controls Bar -->
                <div class="px-3.5 py-2 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5 text-xs bg-slate-50 dark:bg-slate-900">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-500 inline-block animate-pulse"></span>
                        <strong class="font-bold text-slate-900 dark:text-white text-xs">
                            Peta Live Network Fiber FTTH
                        </strong>
                        <span class="text-[9px] px-1.5 py-0.5 rounded-full font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                            Presisi GIS
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
                            @click="toggleRadiusCircles"
                            :class="showRadiusCircles ? 'active' : ''"
                            class="ims-map-type-btn"
                            title="Tampilkan / Sembunyikan Lingkaran Radius 300m"
                        >
                            ⭕ Radius
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
                            <span class="text-slate-700 dark:text-slate-300 font-medium">Titik Target (Bisa Digeser)</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sky-600 dark:text-sky-400 font-semibold text-[10px]">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-0.5 bg-sky-500 inline-block border-t border-dashed border-sky-500"></span>
                            <span>Jalur Dropcore</span>
                        </span>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span class="text-emerald-600 dark:text-emerald-400">Lingkaran Hijau = 150m</span>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span class="text-sky-600 dark:text-sky-400">Lingkaran Biru = 300m</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── 4. CLIENT JAVASCRIPT: LEAFLET GIS MAP, HIGH-PRECISION GEODESIC, DRAGGABLE PIN & MULTI-CANDIDATES ── -->
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
            radiusCirclesLayer: null,
            hasChecked: false,
            isDetectingGps: false,
            isSearchingLocation: false,
            filterOnlyAvailable: false,
            showRadiusCircles: true,
            routingMode: 'street', // 'street' (mengikuti jalur jalan raya/gang akurat) atau 'direct' (garis lurus tiang)
            gpsAccuracyMeters: null,
            detectedAddressName: null,
            gpsAccuracyLayer: null,
            nearestCandidates: [],
            selectedOdpResult: null,
            streetRouteGeometry: null,
            directRouteGeometry: null,
            mapMode: 'roadmap',
            tileLayers: {},

            get filteredCandidates() {
                if (!this.filterOnlyAvailable) {
                    return this.nearestCandidates;
                }
                return this.nearestCandidates.filter(c => c.odp.has_slot);
            },

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
                    zoom: 16,
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
                this.gpsAccuracyLayer = L.layerGroup().addTo(this.mapInstance);
                this.radiusCirclesLayer = L.layerGroup().addTo(this.mapInstance);
                this.connectionLineLayer = L.layerGroup().addTo(this.mapInstance);

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
                    this.gpsAccuracyMeters = null;
                    if (this.gpsAccuracyLayer) this.gpsAccuracyLayer.clearLayers();
                    this.inputCoordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    this.reverseGeocode(lat, lng);
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

            toggleRadiusCircles() {
                this.showRadiusCircles = !this.showRadiusCircles;
                if (!this.radiusCirclesLayer) return;
                if (this.showRadiusCircles) {
                    if (this.selectedOdpResult) {
                        this.drawOdpRadiusCircles(this.selectedOdpResult.odp);
                    }
                } else {
                    this.radiusCirclesLayer.clearLayers();
                }
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
                            <div style="width: 26px; height: 26px; border-radius: 50%; background: ${pinColor}; border: 2.5px solid #ffffff; box-shadow: 0 3px 8px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                <svg style="width: 12px; height: 12px; color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        `,
                        iconSize: [26, 26],
                        iconAnchor: [13, 13]
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
                    this.mapInstance.flyTo([coords.lat, coords.lng], 18, { duration: 0.8 });
                    if (this.odpMarkersMap[odpCode]) {
                        setTimeout(() => {
                            this.odpMarkersMap[odpCode].openPopup();
                        }, 450);
                    }
                }
                this.executeCoverageCheck();
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

            calculateHighPrecisionMeters(lat1, lon1, lat2, lon2) {
                // High-precision Haversine with WGS-84 Mean Radius
                const R = 6371008.8;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return Math.round(R * c);
            },

            async geocodeAddress(query) {
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.length > 0) {
                            return {
                                lat: parseFloat(data[0].lat),
                                lng: parseFloat(data[0].lon)
                            };
                        }
                    }
                } catch (e) {
                    // Fallback
                }
                return null;
            },

            updateCoverageAssessment(result) {
                if (!result) return;
                const roadDist = result.roadDistance !== undefined ? result.roadDistance : Math.round(result.distance * 1.25);
                
                // Evaluasi status coverage BERDASARKAN JARAK RUTE JALAN
                result.isCovered = roadDist <= 300;

                // Hitung estimasi kabel dropcore sesuai mode routing yang dipilih
                if (this.routingMode === 'street') {
                    result.dropcoreDistance = Math.round(roadDist * 1.08 + 10);
                } else {
                    result.dropcoreDistance = Math.round(result.distance * 1.15 + 10);
                }

                // Estimasi redaman optik berdasarkan panjang kabel yang dibutuhkan
                const approxLoss = (0.35 * (result.dropcoreDistance / 1000) + 0.3).toFixed(2);
                result.opticalPowerEstimate = `-${(16.5 + parseFloat(approxLoss)).toFixed(1)} dBm (Loss ~${approxLoss} dB)`;

                // Penentuan level & label kualitas coverage berbasis jarak rute jalan
                if (roadDist > 300) {
                    result.coverageLevel = 'out';
                    result.coverageLabel = 'Di Luar Radius';
                    result.coverageNote = `Jarak rute jalan (${roadDist}m) melebihi batas aman maksimal FTTH (> 300m).`;
                } else if (roadDist > 250) {
                    result.coverageLevel = 'border';
                    result.coverageLabel = 'Batas Maksimal';
                    result.coverageNote = `Jarak rute jalan (${roadDist}m) mendekati batas 300m, disarankan tiang antara.`;
                } else if (roadDist > 150) {
                    result.coverageLevel = 'good';
                    result.coverageLabel = 'Ideal / Aman';
                    result.coverageNote = `Jarak rute jalan (${roadDist}m) dalam radius aman standar tarikan dropcore.`;
                } else {
                    result.coverageLevel = 'excellent';
                    result.coverageLabel = 'Sangat Ideal';
                    result.coverageNote = `Jarak rute jalan (${roadDist}m) sangat dekat, redaman optik sangat prima.`;
                }
            },

            async executeCoverageCheck() {
                let coords = this.parseCoordinates(this.inputCoordinates);
                
                // Jika input bukan angka koordinat, coba geocoding alamat
                if (!coords && this.inputCoordinates && this.inputCoordinates.trim().length > 3) {
                    this.isSearchingLocation = true;
                    coords = await this.geocodeAddress(this.inputCoordinates);
                    this.isSearchingLocation = false;
                    if (coords) {
                        this.inputCoordinates = `${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)}`;
                    }
                }

                if (!coords) {
                    alert('Format tidak valid. Masukkan koordinat Latitude, Longitude (contoh: -6.936988, 107.590451) atau nama alamat.');
                    return;
                }

                const userLat = coords.lat;
                const userLng = coords.lng;

                // Hitung jarak ke seluruh ODP dengan akurasi presisi
                const odpList = this.allOdps.map(odp => {
                    const straightDist = this.calculateHighPrecisionMeters(userLat, userLng, odp.lat, odp.lng);
                    const roadDist = Math.round(straightDist * 1.25);
                    
                    const item = {
                        odp: odp,
                        distance: straightDist,
                        roadDistance: roadDist,
                        dropcoreDistance: Math.round(roadDist * 1.08 + 10),
                        isCovered: roadDist <= 300,
                        coverageLevel: 'excellent',
                        coverageLabel: 'Sangat Ideal',
                        coverageNote: '',
                        opticalPowerEstimate: ''
                    };

                    this.updateCoverageAssessment(item);
                    return item;
                }).sort((a, b) => a.roadDistance - b.roadDistance);

                if (odpList.length > 0) {
                    this.nearestCandidates = odpList.slice(0, 5);
                    this.selectedOdpResult = this.nearestCandidates[0];
                    this.hasChecked = true;
                    await this.drawConnectionToOdp(userLat, userLng, this.selectedOdpResult);
                }
            },

            selectCandidateOdp(candidate) {
                this.selectedOdpResult = candidate;
                this.updateCoverageAssessment(this.selectedOdpResult);
                const coords = this.parseCoordinates(this.inputCoordinates);
                if (coords) {
                    this.drawConnectionToOdp(coords.lat, coords.lng, candidate);
                }
            },

            setRoutingMode(mode) {
                this.routingMode = mode;
                if (this.selectedOdpResult) {
                    this.updateCoverageAssessment(this.selectedOdpResult);
                }
                const coords = this.parseCoordinates(this.inputCoordinates);
                if (coords && this.selectedOdpResult) {
                    this.renderActiveRoute(coords.lat, coords.lng, this.selectedOdpResult);
                }
            },

            drawOdpRadiusCircles(odp) {
                if (!this.radiusCirclesLayer || typeof L === 'undefined') return;
                this.radiusCirclesLayer.clearLayers();

                if (!this.showRadiusCircles) return;

                // Lingkaran Ideal 150 Meter (Hijau)
                L.circle([odp.lat, odp.lng], {
                    radius: 150,
                    color: '#10b981',
                    weight: 1.5,
                    dashArray: '4, 4',
                    fillColor: '#10b981',
                    fillOpacity: 0.08
                }).addTo(this.radiusCirclesLayer);

                // Lingkaran Maksimal 300 Meter (Biru / Sky)
                L.circle([odp.lat, odp.lng], {
                    radius: 300,
                    color: '#0284c7',
                    weight: 1.5,
                    dashArray: '6, 6',
                    fillColor: '#0284c7',
                    fillOpacity: 0.05
                }).addTo(this.radiusCirclesLayer);
            },

            async drawConnectionToOdp(userLat, userLng, result) {
                if (!this.mapInstance || typeof L === 'undefined') return;

                if (this.userMarkerLayer) {
                    this.mapInstance.removeLayer(this.userMarkerLayer);
                }

                // Target User Pin (Google Maps Authentic Red Pin - Draggable for extreme precision)
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

                this.userMarkerLayer = L.marker([userLat, userLng], { 
                    icon: userIcon,
                    draggable: true,
                    title: 'Geser pin ini untuk menyesuaikan titik rumah'
                }).addTo(this.mapInstance);

                this.userMarkerLayer.on('dragend', (e) => {
                    const newPos = e.target.getLatLng();
                    this.gpsAccuracyMeters = null;
                    if (this.gpsAccuracyLayer) this.gpsAccuracyLayer.clearLayers();
                    this.inputCoordinates = `${newPos.lat.toFixed(6)}, ${newPos.lng.toFixed(6)}`;
                    this.reverseGeocode(newPos.lat, newPos.lng);
                    this.executeCoverageCheck();
                });

                const odp = result.odp;

                // Visual Radius Circles
                this.drawOdpRadiusCircles(odp);

                // Direct Line Geometry (Tiang Dropcore Langsung)
                this.directRouteGeometry = [
                    [userLat, userLng],
                    [odp.lat, odp.lng]
                ];

                // High-Precision Real Street Route via OSRM (Foot/Alleyway/Road Network)
                this.streetRouteGeometry = null;
                const routingUrls = [
                    `https://routing.openstreetmap.de/routed-foot/route/v1/foot/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson&continue_straight=true`,
                    `https://routing.openstreetmap.de/routed-bike/route/v1/bicycle/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson&continue_straight=true`,
                    `https://router.project-osrm.org/route/v1/foot/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson&continue_straight=true`,
                    `https://router.project-osrm.org/route/v1/driving/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson&continue_straight=true`,
                    `https://routing.openstreetmap.de/routed-car/route/v1/driving/${userLng},${userLat};${odp.lng},${odp.lat}?overview=full&geometries=geojson&continue_straight=true`
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
                                this.streetRouteGeometry = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                if (data.routes[0].distance) {
                                    result.roadDistance = Math.round(data.routes[0].distance);
                                    // Evaluasi ulang status coverage berdasarkan jarak rute jalan nyata OSRM
                                    this.updateCoverageAssessment(result);
                                }
                                break;
                            }
                        }
                    } catch (e) {
                        // Fallback to next endpoint
                    }
                }

                this.userMarkerLayer.bindPopup(`
                    <div style="font-family: inherit; padding: 4px; min-width: 190px;">
                        <div style="font-size: 10px; font-weight: 800; color: #ea4335; text-transform: uppercase; display: flex; align-items: center; justify-content: space-between;">
                            <span>📍 LOKASI TARGET</span>
                            <span style="font-size: 9px; color: #64748b; font-weight: normal;">(Bisa digeser)</span>
                        </div>
                        <div class="ims-popup-title" style="font-size: 12px; font-weight: 800; margin: 2px 0; font-family: monospace;">${userLat.toFixed(6)}, ${userLng.toFixed(6)}</div>
                        <div style="font-size: 11px; color: ${result.isCovered ? '#0284c7' : '#ea4335'}; font-weight: 700; margin-top: 4px;">
                            ${result.isCovered ? '⚡ Tercover (' + result.coverageLabel + ')' : '✕ Di Luar Radius (> 300m)'}
                        </div>
                        <div class="ims-popup-muted" style="font-size: 10.5px; margin-top: 2px;">
                            Ke <b class="ims-popup-title">${odpName}</b>: Rute Jalan ${result.roadDistance}m (Span: ${result.distance}m)
                        </div>
                    </div>
                `, { offset: [0, -42] }).openPopup();

                // Render Route
                this.renderActiveRoute(userLat, userLng, result);
            },

            renderActiveRoute(userLat, userLng, result) {
                if (!this.connectionLineLayer || !this.mapInstance) return;
                this.connectionLineLayer.clearLayers();

                let routeCoords = this.directRouteGeometry;

                // Jika mode street aktif dan rute jalan tersedia, ikuti setiap lekuk jalan secara presisi
                if (this.routingMode === 'street' && this.streetRouteGeometry && this.streetRouteGeometry.length >= 2) {
                    routeCoords = [...this.streetRouteGeometry];
                    
                    // Sambungkan titik target ke simpul jalan terdekat jika ada jarak drop
                    const firstCoord = routeCoords[0];
                    if (Math.abs(firstCoord[0] - userLat) > 0.00001 || Math.abs(firstCoord[1] - userLng) > 0.00001) {
                        routeCoords.unshift([userLat, userLng]);
                    }
                    
                    // Sambungkan simpul jalan ke tiang ODP
                    const lastCoord = routeCoords[routeCoords.length - 1];
                    if (Math.abs(lastCoord[0] - result.odp.lat) > 0.00001 || Math.abs(lastCoord[1] - result.odp.lng) > 0.00001) {
                        routeCoords.push([result.odp.lat, result.odp.lng]);
                    }
                }

                // 1. Cyan Glow Aura Outer Line
                L.polyline(routeCoords, {
                    color: '#0284c7',
                    weight: 8,
                    opacity: 0.3,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(this.connectionLineLayer);

                // 2. High-Visibility Solid Street Fiber Route Line
                L.polyline(routeCoords, {
                    color: '#0284c7',
                    weight: 4,
                    opacity: 0.95,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(this.connectionLineLayer);

                // 3. Crisp Center Core Tracer Line
                L.polyline(routeCoords, {
                    color: '#e0f2fe',
                    weight: 1.5,
                    dashArray: '6, 8',
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

                const options = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                };

                navigator.geolocation.getCurrentPosition(
                    async (pos) => {
                        this.isDetectingGps = false;
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        const accuracy = Math.round(pos.coords.accuracy || 0);
                        this.gpsAccuracyMeters = accuracy;
                        this.inputCoordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                        // Gambar lingkaran margin error akurasi GPS jika akurasi > 15m
                        if (this.gpsAccuracyLayer && this.mapInstance) {
                            this.gpsAccuracyLayer.clearLayers();
                            if (accuracy > 15) {
                                L.circle([lat, lng], {
                                    radius: accuracy,
                                    color: '#f59e0b',
                                    weight: 1.5,
                                    dashArray: '4, 4',
                                    fillColor: '#f59e0b',
                                    fillOpacity: 0.12
                                }).addTo(this.gpsAccuracyLayer);
                            }
                        }

                        this.reverseGeocode(lat, lng);
                        await this.executeCoverageCheck();
                    },
                    (err) => {
                        this.isDetectingGps = false;
                        let msg = 'Gagal mendeteksi lokasi GPS.';
                        if (err.code === 1) {
                            msg = 'Izin lokasi tidak diberikan. Silakan aktifkan izin lokasi di browser.';
                        } else if (err.code === 2) {
                            msg = 'Lokasi tidak tersedia dari sensor perangkat.';
                        } else if (err.code === 3) {
                            msg = 'Waktu pencarian GPS habis.';
                        }
                        alert(msg + ' Anda dapat mengklik langsung titik lokasi rumah Anda pada peta.');
                    },
                    options
                );
            },

            async reverseGeocode(lat, lng) {
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.display_name) {
                            this.detectedAddressName = data.display_name;
                        }
                    }
                } catch (e) {}
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
