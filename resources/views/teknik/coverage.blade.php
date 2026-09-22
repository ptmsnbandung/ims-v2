@extends('layouts.app')

@section('title', 'Cek Coverage ODP - Modul Teknik IMS')
@section('page_title', 'Cek Coverage ODP')

@section('content')
<div 
    x-data="imsTeknikCoverageComponent()" 
    class="space-y-6"
    style="font-family: 'Plus Jakarta Sans', sans-serif;"
>
    <!-- Scoped Dark Theme CSS -->
    <style>
        .ims-coverage-card {
            background-color: #0f172a !important; /* Slate 900 */
            border: 1px solid #1e293b !important; /* Slate 800 */
            border-radius: 16px !important;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.45) !important;
            color: #f8fafc !important;
        }
        .ims-inner-box {
            background-color: #020617 !important; /* Slate 950 */
            border: 1px solid #1e293b !important;
            border-radius: 12px !important;
        }
        .ims-google-map-canvas {
            width: 100% !important;
            height: 580px !important;
            min-height: 480px !important;
            background: #0b1329 !important;
            display: block !important;
            border-radius: 0 0 16px 16px;
        }
        .ims-map-type-btn {
            padding: 6px 13px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid #334155 !important;
            background-color: #1e293b !important;
            color: #94a3b8 !important;
            transition: all 0.15s ease;
        }
        .ims-map-type-btn:hover {
            background-color: #334155 !important;
            color: #ffffff !important;
        }
        .ims-map-type-btn.active {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            border-color: #0284c7 !important;
            box-shadow: 0 2px 10px rgba(2, 132, 199, 0.45) !important;
        }
        @keyframes pulseTarget {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .user-pulse-pin {
            animation: pulseTarget 1.8s infinite;
        }
        /* Dark Theme for Leaflet Popups */
        .leaflet-popup-content-wrapper {
            background: #0f172a !important;
            color: #f8fafc !important;
            border: 1px solid #334155 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6) !important;
        }
        .leaflet-popup-tip {
            background: #0f172a !important;
        }
        .leaflet-container a.leaflet-popup-close-button {
            color: #94a3b8 !important;
        }
        .leaflet-container a.leaflet-popup-close-button:hover {
            color: #ffffff !important;
        }
    </style>

    <!-- ── 1. HEADER BANNER WITH BREADCRUMBS & KPI METRICS (DARK THEME) ── -->
    <div class="ims-coverage-card p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-inner"
                 style="background: rgba(2, 132, 199, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); color: #38bdf8;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <nav class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-widest mb-0.5" style="color: #38bdf8;">
                    <span>IMS</span>
                    <span style="color: #64748b;">&gt;</span>
                    <span>Modul Teknik</span>
                    <span style="color: #64748b;">&gt;</span>
                    <span style="color: #94a3b8;">GIS Coverage</span>
                </nav>
                <h1 class="text-lg md:text-xl font-black text-white tracking-tight leading-none">
                    Cek Coverage Lokasi ke ODP Terdekat
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Gunakan peta Google Maps untuk memeriksa kelayakan tarikan kabel dropcore fiber optik ke titik ODP terdekat secara presisi.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
            <div class="ims-inner-box px-4 py-2.5 text-left">
                <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider block">Total ODP Terdata</span>
                <strong class="text-sm font-black text-sky-400 font-mono block mt-0.5">
                    {{ count($odps) }} Node
                </strong>
            </div>
            <div class="ims-inner-box px-4 py-2.5 text-left">
                <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider block">Max Radius Tercover</span>
                <strong class="text-sm font-black text-emerald-400 font-mono block mt-0.5">
                    &le; 300 Meter
                </strong>
            </div>
        </div>
    </div>

    <!-- ── 2. MAIN 2-COLUMN LAYOUT: SEARCH/RESULTS (LEFT) & MAP CANVAS (RIGHT) ── -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- ── LEFT PANEL (4 COLS ON DESKTOP) ── -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Input Card -->
            <div class="ims-coverage-card p-4 space-y-3.5">
                <div>
                    <label class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span>Input Titik Koordinat Target</span>
                    </label>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Gunakan tombol GPS otomatis atau masukkan titik koordinat (Latitude, Longitude):
                    </p>
                </div>

                <form @submit.prevent="executeCoverageCheck" class="space-y-2.5">
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="inputCoordinates"
                            placeholder="-6.936988, 107.5904512" 
                            class="w-full h-11 pl-10 pr-3 rounded-xl text-white text-xs font-mono focus:outline-none focus:ring-2 focus:ring-sky-500/50"
                            style="background-color: #020617 !important; border: 1px solid #334155 !important; color: #ffffff !important;"
                            required
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            type="button" 
                            @click="getCurrentLocation" 
                            :disabled="isDetectingGps"
                            class="h-10 px-3 rounded-xl text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                            style="background-color: #1e293b; border: 1px solid #334155; color: #f1f5f9;"
                        >
                            <span x-show="!isDetectingGps">📍 Gunakan GPS</span>
                            <span x-show="isDetectingGps" class="animate-pulse">⏳ Mencari GPS...</span>
                        </button>

                        <button 
                            type="submit" 
                            class="h-10 px-3 rounded-xl text-white text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-md"
                            style="background-color: #0284c7 !important; border: 1px solid #0284c7 !important; color: #ffffff !important;"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="text-white font-bold">Periksa Koordinat</span>
                        </button>
                    </div>

                    <!-- Preset Selector from Master Database m_odp -->
                    <div class="pt-2.5 border-t border-slate-800/80 space-y-1">
                        <label class="text-[11px] font-bold text-slate-300 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                                <span>Pilih ODP dari Database:</span>
                            </span>
                            <span class="text-[10px] text-sky-400 font-mono">{{ count($odps) }} ODP Aktif</span>
                        </label>
                        <select 
                            @change="selectOdpPreset($event.target.value)" 
                            class="w-full h-10 px-3 rounded-xl text-white text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/50 cursor-pointer"
                            style="background-color: #020617 !important; border: 1px solid #334155 !important; color: #ffffff !important;"
                        >
                            <option value="">-- Pilih ODP Master Database --</option>
                            @foreach($odps as $o)
                                <option value="{{ $o['lat'] }},{{ $o['lng'] }}|{{ $o['kode_odp'] }}">
                                    {{ $o['name_odp'] }} ({{ $o['kode_odp'] }}) - PON: {{ $o['kode_pon'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="p-2.5 rounded-xl flex items-center gap-2 text-[11px]"
                     style="background: rgba(2, 132, 199, 0.12); border: 1px solid rgba(56, 189, 248, 0.25); color: #7dd3fc;">
                    <span class="text-xs">💡</span>
                    <span>Format: <b>Latitude, Longitude</b>, pilih ODP database di atas, atau klik langsung pada peta.</span>
                </div>
            </div>

            <!-- Coverage Evaluation Result Card -->
            <template x-if="hasChecked && nearestResult">
                <div class="space-y-3">
                    
                    <!-- 1. CASE: COVERED (<= 300m) -->
                    <template x-if="nearestResult.isCovered">
                        <div class="p-4 rounded-2xl space-y-3.5 transition-all shadow-xl"
                             style="background-color: #0f172a; border: 2px solid #0284c7; box-shadow: 0 8px 30px rgba(2, 132, 199, 0.25);">
                            
                            <!-- Status Headline -->
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full bg-sky-400 inline-block shadow-sm shadow-sky-400 animate-pulse"></span>
                                <div>
                                    <strong class="text-sm font-black text-sky-400 block">
                                        ● Area Tercover Fiber Optic
                                    </strong>
                                    <span class="text-[11px] text-slate-300 block mt-0.5">
                                        Jaringan kabel distribusi IMS terdeteksi aktif pada radius aman instalasi.
                                    </span>
                                </div>
                            </div>

                            <!-- ODP Node & Calculated Road Dropcore Length -->
                            <div class="ims-inner-box p-3 text-xs text-sky-400 flex items-center justify-between font-mono font-bold">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="text-slate-400">⚡ ODP Terdekat:</span>
                                    <strong class="text-white truncate" x-text="nearestResult.odp.name_odp || nearestResult.odp.name"></strong>
                                </div>
                                <span class="text-sky-300 font-black shrink-0">
                                    ~<span x-text="nearestResult.roadDistance"></span>m dropcore
                                </span>
                            </div>

                            <!-- ODP Technical Specifications (matching Database m_odp) -->
                            <div class="ims-inner-box p-3 space-y-2 text-xs text-slate-300">
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-800/80">
                                    <span class="text-slate-400">Nama ODP (Database):</span>
                                    <strong class="text-white font-bold" x-text="nearestResult.odp.name_odp || nearestResult.odp.name"></strong>
                                </div>
                                <div class="flex justify-between items-center pb-1.5 border-b border-slate-800/80">
                                    <span class="text-slate-400">Kode ODP (Database):</span>
                                    <span class="text-sky-400 font-mono font-bold px-2 py-0.5 rounded bg-sky-950/60 border border-sky-800/60 text-[11px]" x-text="nearestResult.odp.kode_odp || nearestResult.odp.code"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400">Port PON Induk:</span>
                                    <strong class="text-slate-200 font-mono" x-text="nearestResult.odp.kode_pon || nearestResult.odp.pon_name"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400">Kapasitas Core / Port:</span>
                                    <span class="font-bold font-mono" :class="nearestResult.odp.has_slot ? 'text-emerald-400' : 'text-rose-400'">
                                        <span x-text="(nearestResult.odp.used_ports ?? 0) + ' / ' + (nearestResult.odp.capacity_odp || nearestResult.odp.total_ports) + ' Port'"></span>
                                        <span x-show="nearestResult.odp.has_slot" class="text-[10px] ml-1 text-emerald-400 font-bold">(Ada Slot)</span>
                                        <span x-show="!nearestResult.odp.has_slot" class="text-[10px] ml-1 text-rose-400 font-bold">(Penuh)</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400">Jarak Lurus Udara:</span>
                                    <strong class="text-white font-mono" x-text="nearestResult.distance + ' Meter'"></strong>
                                </div>
                                <div class="flex justify-between items-center" x-show="nearestResult.odp.note_odp && nearestResult.odp.note_odp !== '-'">
                                    <span class="text-slate-400">Keterangan / Lokasi:</span>
                                    <span class="text-slate-300 italic text-[11px]" x-text="nearestResult.odp.note_odp"></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 pt-1">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + nearestResult.odp.lat + ',' + nearestResult.odp.lng"
                                    target="_blank" 
                                    class="flex-1 h-9 rounded-xl text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md cursor-pointer"
                                    style="background-color: #0284c7 !important; color: #ffffff !important;"
                                >
                                    <span>🧭 Buka Navigasi Maps</span>
                                </a>
                                <button 
                                    type="button" 
                                    @click="copyCoordinates(nearestResult.odp.lat + ', ' + nearestResult.odp.lng)" 
                                    class="h-9 px-3 rounded-xl text-slate-200 text-xs font-bold transition cursor-pointer"
                                    style="background-color: #1e293b; border: 1px solid #334155;"
                                    title="Salin Koordinat ODP"
                                >
                                    <span>📋 Salin</span>
                                </button>
                                <a 
                                    href="{{ route('teknik.pendaftaran') }}" 
                                    class="h-9 px-3 rounded-xl text-white text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer"
                                    style="background-color: #059669 !important; color: #ffffff !important;"
                                    title="Lanjut Form Registrasi Pasang Baru"
                                >
                                    <span>⚡ Pasang Baru</span>
                                </a>
                            </div>
                        </div>
                    </template>

                    <!-- 2. CASE: OUT OF COVERAGE (> 300m) -->
                    <template x-if="!nearestResult.isCovered">
                        <div class="p-4 rounded-2xl space-y-3"
                             style="background-color: #0f172a; border: 2px solid #334155;">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full bg-slate-400 inline-block"></span>
                                <div>
                                    <strong class="text-sm font-bold text-white block">
                                        Di Luar Radius Coverage (&gt; 300m)
                                    </strong>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">
                                        Jarak ODP terdekat adalah <b class="text-rose-400 font-mono" x-text="nearestResult.distance + ' meter'"></b>.
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-300 p-2.5 rounded-xl border border-slate-800"
                               style="background-color: #020617;">
                                Lokasi ini membutuhkan penarikan kabel feeder tambahan atau pemasangan tiang/ODP baru sebelum dapat dilakukan aktivasi layanan.
                            </p>
                            <div class="flex gap-2">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + nearestResult.odp.lat + ',' + nearestResult.odp.lng"
                                    target="_blank" 
                                    class="flex-1 h-9 rounded-xl text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1"
                                    style="background-color: #1e293b; border: 1px solid #334155;"
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
                <div class="px-4 py-3 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs"
                     style="background-color: #0f172a;">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-400 inline-block animate-pulse"></span>
                        <strong class="font-bold text-white">
                            Peta Live Network Fiber FTTH
                        </strong>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold"
                              style="background: rgba(2, 132, 199, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">
                            Google GIS
                        </span>
                    </div>

                    <!-- Map Layers & View Controls -->
                    <div class="flex items-center gap-1.5 flex-wrap">
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
                            class="px-2.5 py-1.5 rounded-lg text-sky-400 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                            style="background-color: #1e293b; border: 1px solid #334155;"
                        >
                            <span>🔄 Fit All</span>
                        </button>
                    </div>
                </div>

                <!-- GIS Map Canvas Div -->
                <div id="ims-google-map-canvas" class="ims-google-map-canvas"></div>

                <!-- Map Footer Legend -->
                <div class="px-4 py-2.5 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-400"
                     style="background-color: #020617;">
                    <div class="flex items-center gap-3.5 flex-wrap">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-sky-500 border border-slate-900 inline-block"></span>
                            <span class="text-slate-300">ODP Ada Slot</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-rose-500 border border-slate-900 inline-block"></span>
                            <span class="text-slate-300">ODP Port Penuh</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 rounded-full bg-red-600 border border-white inline-block text-[9px] text-white flex items-center justify-center">📍</span>
                            <span class="text-slate-300">Titik Target</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-sky-400 font-medium">
                        <span class="w-4 h-0.5 bg-sky-400 inline-block border-t border-dashed border-sky-400"></span>
                        <span>Garis Biru = Jalur kabel dropcore fiber optik (OSRM Street Route)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 3. BOTTOM SECTION: DATABASE MASTER ODP (m_odp) & TIKET WHATSAPP (trx_coverage_area) ── -->
    <div class="ims-coverage-card p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-800">
            <div class="flex items-center gap-2 flex-wrap">
                <button 
                    type="button" 
                    @click="activeBottomTab = 'odp_master'" 
                    :class="activeBottomTab === 'odp_master' ? 'bg-sky-600 text-white font-bold shadow-md shadow-sky-600/30' : 'bg-slate-800 text-slate-300 hover:text-white'"
                    class="px-3.5 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>📍 Data Master ODP Database (m_odp)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-900/80 font-mono font-bold text-sky-300">{{ count($odps) }} Node</span>
                </button>
                <button 
                    type="button" 
                    @click="activeBottomTab = 'tickets'" 
                    :class="activeBottomTab === 'tickets' ? 'bg-sky-600 text-white font-bold shadow-md shadow-sky-600/30' : 'bg-slate-800 text-slate-300 hover:text-white'"
                    class="px-3.5 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>💬 Antrean Tiket WhatsApp (trx_coverage_area)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-900/80 font-mono font-bold text-amber-300">{{ count($tickets) }} Tiket</span>
                </button>
            </div>
            <span class="text-[11px] font-mono text-slate-400">
                Database: <strong class="text-sky-400">m_odp</strong> &amp; <strong class="text-amber-400">trx_coverage_area</strong>
            </span>
        </div>

        <!-- TAB 1: DAFTAR MASTER ODP DARI DATABASE m_odp -->
        <div x-show="activeBottomTab === 'odp_master'" class="space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400 pb-1">
                <span>Daftar seluruh titik ODP yang tersimpan aktif pada tabel database <code>m_odp</code>.</span>
                <span class="font-mono text-sky-400 font-bold">Total: {{ count($odps) }} Titik ODP</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-bold text-slate-400"
                            style="background-color: #020617;">
                            <th class="py-3 px-4">Kode ODP</th>
                            <th class="py-3 px-4">Nama ODP (Database)</th>
                            <th class="py-3 px-4">Port PON Induk</th>
                            <th class="py-3 px-4">Kapasitas Core</th>
                            <th class="py-3 px-4">Titik Koordinat GPS</th>
                            <th class="py-3 px-4">Keterangan / Lokasi</th>
                            <th class="py-3 px-4 text-center">Aksi Peta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @forelse ($odps as $o)
                            <tr class="hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 align-top font-mono font-bold text-sky-400">
                                    {{ $o['kode_odp'] }}
                                </td>
                                <td class="py-3.5 px-4 align-top font-bold text-white">
                                    {{ $o['name_odp'] }}
                                </td>
                                <td class="py-3.5 px-4 align-top font-mono">
                                    <span class="px-2 py-0.5 rounded text-[10.5px] bg-sky-950/70 border border-sky-800/50 text-sky-300 font-bold">
                                        {{ $o['kode_pon'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 align-top font-mono">
                                    <span class="font-bold text-emerald-400">{{ $o['used_ports'] }}</span> / {{ $o['capacity_odp'] }} Port
                                </td>
                                <td class="py-3.5 px-4 align-top font-mono text-sky-400 text-[11px]">
                                    {{ number_format($o['lat'], 6) }}, {{ number_format($o['lng'], 6) }}
                                </td>
                                <td class="py-3.5 px-4 align-top text-slate-400">
                                    {{ $o['note_odp'] ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 align-top text-center">
                                    <button 
                                        type="button" 
                                        @click="focusOdpFromDb('{{ $o['kode_odp'] }}', '{{ $o['lat'] }}', '{{ $o['lng'] }}')" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-white text-[11px] font-bold shadow-sm transition cursor-pointer"
                                        style="background-color: #0284c7; border: 1px solid #0284c7;"
                                        title="Sorot ODP ini di peta dan cek coverage"
                                    >
                                        <span>⚡ Sorot di Peta</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500 text-xs">
                                    Tidak ada data ODP di database <code>m_odp</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: ANTREAN TIKET WHATSAPP (trx_coverage_area) -->
        <div x-show="activeBottomTab === 'tickets'" class="space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400 pb-1">
                <span>Data calon pelanggan yang masuk via WhatsApp Gateway untuk cek coverage.</span>
                <span class="font-mono text-amber-400 font-bold">Total: {{ count($tickets) }} Tiket</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-bold text-slate-400"
                            style="background-color: #020617;">
                            <th class="py-3 px-4">ID Tiket / Waktu</th>
                            <th class="py-3 px-4">Kontak / Pengirim</th>
                            <th class="py-3 px-4">Titik Koordinat</th>
                            <th class="py-3 px-4">Jarak &amp; Catatan</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-center">Aksi Peta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @forelse ($tickets as $t)
                            @php
                                $hasCoord = !empty($t->latitude) && !empty($t->longitude);
                                $coordStr = $hasCoord ? "{$t->latitude}, {$t->longitude}" : '';
                            @endphp
                            <tr class="hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 align-top">
                                    <strong class="text-sky-400 font-mono block break-all">
                                        {{ $t->id_message }}
                                    </strong>
                                    <span class="text-[10.5px] text-slate-400 block mt-0.5">
                                        {{ $t->date_create ?? $t->created_at ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 align-top">
                                    <strong class="text-white block">
                                        {{ $t->pushname ?: 'Tanpa Nama' }}
                                    </strong>
                                    @if(!empty($t->nomor_hp))
                                        <span class="text-slate-400 font-mono block text-[11px] mt-0.5">
                                            📱 {{ $t->nomor_hp }}
                                        </span>
                                    @endif
                                    @if(!empty($t->group_wa))
                                        <span class="text-[10px] text-slate-500 block">
                                            Group: {{ $t->group_wa }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 align-top">
                                    @if($hasCoord)
                                        <span class="font-mono text-xs text-sky-400 px-2 py-0.5 rounded block w-fit"
                                              style="background-color: #020617; border: 1px solid #1e293b;">
                                            {{ $t->latitude }}, {{ $t->longitude }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-xs italic">Koordinat belum diisi</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 align-top">
                                    @if(!empty($t->jarak))
                                        <span class="text-[11px] font-bold text-sky-400 block">Jarak: {{ $t->jarak }}m</span>
                                    @endif
                                    <span class="text-slate-400 text-[11px] block mt-0.5">{{ $t->note ?: '-' }}</span>
                                </td>
                                <td class="py-3.5 px-4 align-top">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase"
                                          style="@if(in_array($t->status, ['TERCOVER', 'SELESAI', '20', '13'])) background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);
                                                 @elseif(in_array($t->status, ['TIDAK_TERCOVER', 'CANCEL', '15'])) background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.4);
                                                 @else background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); @endif">
                                        {{ $t->status ?: 'ANTRIAN' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 align-top text-center">
                                    @if($hasCoord)
                                        <button 
                                            type="button" 
                                            @click="focusTicketCoordinate('{{ $t->latitude }}', '{{ $t->longitude }}')" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-white text-[11px] font-bold shadow-sm transition cursor-pointer"
                                            style="background-color: #0284c7; border: 1px solid #0284c7;"
                                        >
                                            <span>⚡ Cek di Peta</span>
                                        </button>
                                    @else
                                        <span class="text-slate-500 text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                                    Belum ada antrean tiket permintaan coverage dari WhatsApp (tabel <code>trx_coverage_area</code>).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
            activeBottomTab: 'odp_master',
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
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: ${pinColor}; border: 2.5px solid #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                <svg style="width: 13px; height: 13px; color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        `,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });

                    const marker = L.marker([odp.lat, odp.lng], { icon: customIcon });

                    marker.bindPopup(`
                        <div style="font-family: inherit; padding: 6px; color: #f8fafc; min-width: 200px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 3px;">
                                <span style="font-size: 11px; font-weight: 800; color: #38bdf8; font-family: monospace; background: rgba(2,132,199,0.2); padding: 1px 6px; border-radius: 4px;">${odp.kode_odp || odp.code}</span>
                                <span style="font-size: 10px; font-weight: 700; color: ${isAvailable ? '#34d399' : '#f87171'};">${isAvailable ? '● ADA SLOT' : '● PENUH'}</span>
                            </div>
                            <div style="font-size: 13.5px; font-weight: 900; margin: 4px 0 6px; color: #ffffff;">${odp.name_odp || odp.name}</div>
                            <div style="font-size: 11px; color: #cbd5e1; line-height: 1.5; border-top: 1px solid #334155; padding-top: 5px;">
                                <div>Port: <b style="color: #ffffff;">${odp.used_ports}/${odp.capacity_odp || odp.total_ports} Port</b> • PON: <b style="color: #ffffff;">${odp.kode_pon || odp.pon_name}</b></div>
                                ${odp.note_odp && odp.note_odp !== '-' ? `<div style="color: #94a3b8; font-size: 10px; margin-top: 2px;">Ket: ${odp.note_odp}</div>` : ''}
                            </div>
                            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #334155;">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=${odp.lat},${odp.lng}" target="_blank" style="display: block; text-align: center; text-decoration: none; background: #0284c7; color: #fff; padding: 5px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 800;">🧭 Rute Google Maps &rarr;</a>
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

                // Target User Pin
                const userIcon = L.divIcon({
                    className: 'user-pulse-pin',
                    html: `
                        <div style="position: relative; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #ef4444; border: 2.5px solid #ffffff; box-shadow: 0 4px 14px rgba(239,68,68,0.5); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px;">
                                📍
                            </div>
                        </div>
                    `,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17]
                });

                const odpName = result.odp.name_odp || result.odp.name;
                const odpCode = result.odp.kode_odp || result.odp.code;

                this.userMarkerLayer = L.marker([userLat, userLng], { icon: userIcon }).addTo(this.mapInstance);
                this.userMarkerLayer.bindPopup(`
                    <div style="font-family: inherit; padding: 4px; color: #f8fafc; min-width: 170px;">
                        <div style="font-size: 10px; font-weight: 800; color: #f87171; text-transform: uppercase;">📍 LOKASI TARGET</div>
                        <div style="font-size: 12px; font-weight: 800; margin: 2px 0; color: #ffffff;">${userLat.toFixed(6)}, ${userLng.toFixed(6)}</div>
                        <div style="font-size: 11px; color: ${result.isCovered ? '#38bdf8' : '#f87171'}; font-weight: 700; margin-top: 4px;">
                            ${result.isCovered ? '⚡ Tercover Fiber Optic' : '✕ Di Luar Radius (> 300m)'}
                        </div>
                        <div style="font-size: 10.5px; color: #94a3b8; margin-top: 2px;">Terhubung ke <b style="color: #ffffff;">${odpName}</b> (${odpCode}) ~${result.distance}m</div>
                    </div>
                `).openPopup();

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
