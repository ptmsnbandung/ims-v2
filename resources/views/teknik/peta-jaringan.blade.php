@extends('layouts.app')

@section('title', 'Peta Jaringan & Jalur FTTH (GIS Network Builder) - Teknik IMS')
@section('page_title', 'Peta Jaringan & Jalur FTTH (GIS)')

@section('content')
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div 
    x-data="imsFtthNetworkMapComponent({{ json_encode($allProjects) }}, {{ json_encode($currentProject) }}, {{ json_encode($customElements) }}, {{ json_encode($allOdps) }})"
    class="ims-ftth-map-root space-y-5 w-full font-sans"
>
    <style>
        .ims-ftth-map-root * { box-sizing: border-box; }
        .ims-map-card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(8, 120, 229, 0.06);
            overflow: visible !important;
            position: relative;
        }
        /* Dark mode overrides for Peta Jaringan */
        .dark .ims-map-card {
            background: #0b2233 !important;
            border-color: #163d58 !important;
        }
        /* ── SIDEBAR DRAWER STYLES ── */
        .ims-drawer-root {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            width: 360px !important;
            max-width: 90vw !important;
            height: 100% !important;
            background: #ffffff !important;
            z-index: 1000 !important;
            border-right: 1.5px solid #CBD5E1 !important;
            box-shadow: 10px 0 32px rgba(15,23,42,0.18) !important;
            border-radius: 0 16px 16px 0 !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            pointer-events: auto !important;
        }
        .dark .ims-drawer-root {
            background: #0b2233 !important;
            border-right-color: #163d58 !important;
            box-shadow: 10px 0 32px rgba(0,0,0,0.5) !important;
        }
        .ims-drawer-header {
            height: 58px !important;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%) !important;
            color: #ffffff !important;
            padding: 0 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            box-sizing: border-box !important;
            border-bottom: 1px solid #334155 !important;
            flex-shrink: 0 !important;
        }
        .ims-drawer-tabs-row {
            height: 50px !important;
            padding: 8px 14px !important;
            background: #ffffff !important;
            box-sizing: border-box !important;
            border-bottom: 1px solid #F1F5F9 !important;
            flex-shrink: 0 !important;
        }
        .ims-drawer-tab-objects {
            height: calc(100% - 108px) !important;
            width: 100% !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        .ims-drawer-search-row {
            height: 94px !important;
            padding: 8px 14px 6px 14px !important;
            background: #ffffff !important;
            border-bottom: 1px solid #F1F5F9 !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            flex-shrink: 0 !important;
        }
        .ims-drawer-object-scroll {
            height: calc(100% - 94px) !important;
            width: 100% !important;
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            box-sizing: border-box !important;
            padding: 10px 14px 40px 14px !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
            scrollbar-width: thin !important;
            scrollbar-color: #94A3B8 #F1F5F9 !important;
        }
        .ims-drawer-layer-scroll {
            height: calc(100% - 108px) !important;
            width: 100% !important;
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            box-sizing: border-box !important;
            padding: 12px 14px 40px 14px !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
            scrollbar-width: thin !important;
            scrollbar-color: #94A3B8 #F1F5F9 !important;
        }
        .ims-sidebar-tab-btn {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            padding: 7px 10px !important;
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            border: none !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
            flex: 1 1 0 !important;
            line-height: 1 !important;
        }
        .ims-sidebar-filter-btn {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            padding: 5px 10px !important;
            border-radius: 8px !important;
            font-size: 11.5px !important;
            font-weight: 800 !important;
            white-space: nowrap !important;
            border: 1.5px solid !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            line-height: 1.2 !important;
            flex-shrink: 0 !important;
        }
        /* Leaflet Marker Optimization */
        .leaflet-zoom-animated,
        .leaflet-marker-icon,
        .leaflet-marker-icon *,
        .leaflet-tile-container,
        .leaflet-tile,
        .leaflet-pane,
        .leaflet-overlay-pane,
        .leaflet-overlay-pane svg,
        .leaflet-overlay-pane path {
            transition: none !important;
        }
        .leaflet-marker-icon.custom-ftth-node,
        .leaflet-marker-icon.ims-drag-edit-marker,
        .leaflet-marker-icon.odp-pin {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .ims-map-container-wrap {
            position: relative !important;
            width: 100% !important;
            height: 650px !important;
            min-height: 650px !important;
            overflow: hidden !important;
        }
        .ims-map-canvas {
            width: 100% !important;
            height: 650px !important;
            min-height: 650px !important;
            background: #f8fafc !important;
            display: block !important;
            position: relative !important;
        }
        #ims-ftth-map-card-root:fullscreen,
        #ims-ftth-map-card-root:-webkit-full-screen,
        #ims-ftth-map-card-root.is-fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            max-width: 100vw !important;
            max-height: 100vh !important;
            z-index: 99999999 !important;
            border-radius: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            display: flex !important;
            flex-direction: column !important;
            background: #ffffff !important;
        }
        #ims-ftth-map-card-root:fullscreen .ims-map-container-wrap,
        #ims-ftth-map-card-root.is-fullscreen .ims-map-container-wrap {
            flex: 1 1 auto !important;
            height: 100% !important;
            min-height: 0 !important;
        }
        #ims-ftth-map-card-root:fullscreen .ims-map-canvas,
        #ims-ftth-map-card-root.is-fullscreen .ims-map-canvas {
            flex: 1 1 auto !important;
            height: 100% !important;
            min-height: 0 !important;
        }
        .ims-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 10px;
            font-size: 11.5px;
            font-weight: 800;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .ims-tool-btn:hover {
            background: #F4FAFF;
            border-color: #0878E5;
            color: #0878E5;
        }
        .ims-tool-btn.active {
            background: #0878E5 !important;
            color: #ffffff !important;
            border-color: #0878E5 !important;
            box-shadow: 0 2px 8px rgba(8,120,229,0.35);
        }
        .ims-floating-layer-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            background: rgba(255, 255, 255, 0.96) !important;
            color: #0878E5 !important;
            border: 1.5px solid #CBD5E1 !important;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.14) !important;
            backdrop-filter: blur(8px) !important;
            cursor: pointer !important;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .ims-floating-layer-btn:hover {
            transform: scale(1.05) !important;
            box-shadow: 0 6px 20px rgba(8, 120, 229, 0.25) !important;
        }
        .ims-floating-layer-btn-active {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            background: #0F172A !important;
            color: #38BDF8 !important;
            border: 1.5px solid #0F172A !important;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.3) !important;
            cursor: pointer !important;
        }
        .ims-badge-stat {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        /* Search Box */
        .ims-search-box-container {
            position: relative !important;
            width: 100% !important;
            height: 34px !important;
            display: flex !important;
            align-items: center !important;
        }
        .ims-search-box-icon {
            position: absolute !important;
            left: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #0878E5 !important;
            pointer-events: none !important;
            z-index: 10 !important;
        }
        .ims-search-box-input {
            width: 100% !important;
            height: 34px !important;
            padding-left: 36px !important;
            padding-right: 30px !important;
            background: #F8FAFC !important;
            border: 1.5px solid #CBD5E1 !important;
            border-radius: 10px !important;
            font-size: 11.5px !important;
            font-weight: 700 !important;
            color: #0F172A !important;
            outline: none !important;
        }
        .ims-search-box-input:focus {
            background: #ffffff !important;
            border-color: #0878E5 !important;
            box-shadow: 0 0 0 3px rgba(8, 120, 229, 0.15) !important;
        }
        .ims-search-box-clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            border: none !important;
            background: transparent !important;
            color: #94A3B8 !important;
            cursor: pointer !important;
            font-size: 13px !important;
            font-weight: 900 !important;
        }
        /* Fullscreen Modal Overlays */
        .ims-modal-overlay-root {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(15, 23, 42, 0.78) !important;
            backdrop-filter: blur(10px) !important;
            z-index: 999999999 !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            box-sizing: border-box !important;
            overflow-y: auto !important;
        }
        .ims-modal-card-dialog {
            position: relative !important;
            margin: auto !important;
            width: 100% !important;
            max-width: 660px !important;
            max-height: 90vh !important;
            background: #ffffff !important;
            border-radius: 20px !important;
            box-shadow: 0 30px 80px -15px rgba(15, 23, 42, 0.5) !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        .ims-modal-tab-nav {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            background: #F1F5F9 !important;
            border: 1.5px solid #E2E8F0 !important;
            border-radius: 12px !important;
            padding: 4px !important;
            gap: 6px !important;
            width: 100% !important;
        }
        .ims-modal-tab-btn {
            flex: 1 1 0% !important;
            height: 38px !important;
            border-radius: 9px !important;
            padding: 0 10px !important;
            background: transparent !important;
            color: #475569 !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            white-space: nowrap !important;
            transition: all 0.15s ease !important;
            border: 1.5px solid transparent !important;
        }
        .ims-modal-tab-btn.is-active {
            background: #0878E5 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            border-color: #0878E5 !important;
        }
    </style>

    {{-- ── 1. HEADER BANNER & TELEMETRY STATS ── --}}
    <div style="background: linear-gradient(135deg, #0B1F33 0%, #0878E5 100%); border-radius: 16px; padding: 1.25rem 1.5rem; color: #ffffff; box-shadow: 0 8px 24px rgba(8, 120, 229, 0.2); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.25); flex-shrink: 0;">
                <svg style="width: 24px; height: 24px; color: #55C7FF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 900; margin: 0; color: #ffffff; letter-spacing: -0.01em;">
                    Peta Jaringan & Jalur FTTH (GIS Network Builder)
                </h2>
                <p style="font-size: 0.78rem; color: #EAF5FF; margin: 2px 0 0 0; opacity: 0.9;">
                    Pemetaan interaktif rute kabel fiber optik, tiang (*pole*), ODC, dan kotak sambung (*joint box*).
                </p>
            </div>
        </div>

        {{-- Quick Summary Badges --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
            <div class="ims-badge-stat" style="background: rgba(255,255,255,0.18); border: 1.5px solid rgba(255,255,255,0.35); color: #ffffff;">
                <span>📁 Proyek:</span>
                <strong style="color: #55C7FF;" x-text="currentProject ? currentProject.name : 'Utama'"></strong>
            </div>
            <div class="ims-badge-stat" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #ffffff;">
                <span>⚡ ODP:</span>
                <strong style="color: #55C7FF;" x-text="allOdps.length"></strong>
            </div>
            <div class="ims-badge-stat" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #ffffff;">
                <span>📍 Tiang & Node:</span>
                <strong style="color: #55C7FF;" x-text="customElements.filter(e => e.category === 'marker').length"></strong>
            </div>
            <div class="ims-badge-stat" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #ffffff;">
                <span>〰️ Jalur Kabel:</span>
                <strong style="color: #55C7FF;" x-text="customElements.filter(e => e.category === 'line').length"></strong>
            </div>
            <div class="ims-badge-stat" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #ffffff;">
                <span>📏 Total:</span>
                <strong style="color: #55C7FF;" x-text="calculateTotalCableKm() + ' Km'"></strong>
            </div>
        </div>
    </div>

    {{-- ── 2. UNIFIED GIS TOOLBAR & MAP CONTAINER ── --}}
    <div 
        id="ims-ftth-map-card-root"
        class="ims-map-card" 
        :class="isFullscreen ? 'is-fullscreen' : ''"
        style="overflow: visible !important; position: relative; z-index: 20;"
    >
        {{-- Toolbar Top Header --}}
        <div style="padding: 0.55rem 0.85rem; background: #ffffff; border-bottom: 1px solid #e2e8f0; border-radius: 16px 16px 0 0; position: relative; z-index: 10000; overflow: visible !important;">
            <div style="display: flex; flex-wrap: nowrap; align-items: center; justify-content: space-between; gap: 8px; width: 100%;">
                
                {{-- 1. Project Selector --}}
                <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                    <div style="position: relative;" @click.outside="openProjectMenu = false">
                        <button 
                            type="button" 
                            @click="openProjectMenu = !openProjectMenu; openMarkerMenu = false; openLineMenu = false;" 
                            class="ims-tool-btn"
                            style="background: #F0FDF4; border-color: #BBF7D0; color: #166534; font-weight: 900;"
                            title="Pilih atau kelola proyek GIS FTTH"
                        >
                            <svg style="width: 14px; height: 14px; color: #16A34A; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            <span><span x-text="currentProject ? currentProject.name : 'Pilih Proyek'"></span> ▾</span>
                        </button>
                        
                        <div 
                            x-show="openProjectMenu" 
                            x-cloak
                            style="position: absolute; top: calc(100% + 8px); left: 0; z-index: 999999; background: #ffffff; border: 1px solid #E2E8F0; border-radius: 18px; box-shadow: 0 20px 48px rgba(15,23,42,0.18); min-width: 340px; padding: 14px; display: flex; flex-direction: column; gap: 8px;"
                        >
                            <div style="padding: 2px 4px 10px 4px; font-size: 0.72rem; font-weight: 900; color: #64748B; text-transform: uppercase; border-bottom: 1.5px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;">
                                <span>Pilih Proyek (<span x-text="allProjects.length"></span>)</span>
                                <button 
                                    type="button" 
                                    @click="openNewProjectModal = true; openProjectMenu = false;"
                                    style="border: none; background: #0878E5; color: #ffffff; padding: 5px 12px; border-radius: 8px; font-size: 0.72rem; font-weight: 800; cursor: pointer;"
                                >
                                    + Proyek Baru
                                </button>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px; max-height: 280px; overflow-y: auto;">
                                <template x-for="p in allProjects" :key="p.id">
                                    <div 
                                        @click="switchProject(p.id); openProjectMenu = false;"
                                        style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-radius: 10px; border: 1.5px solid #E2E8F0; cursor: pointer; transition: all 0.15s ease;"
                                        :style="currentProject && currentProject.id === p.id ? 'background: #F0FDF4; border-color: #86EFAC;' : 'background: #FFFFFF;'"
                                    >
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #F1F5F9; display: flex; align-items: center; justify-content: center; color: #475569;">
                                                📁
                                            </div>
                                            <div>
                                                <div style="font-size: 0.82rem; font-weight: 800; color: #0F172A;" x-text="p.name"></div>
                                                <div style="font-size: 0.68rem; color: #64748B;" x-text="(p.elements_count || 0) + ' Objek Tersimpan'"></div>
                                            </div>
                                        </div>

                                        <template x-if="currentProject && currentProject.id === p.id">
                                            <span style="background: #16A34A; color: #ffffff; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 800;">Aktif</span>
                                        </template>
                                        <template x-if="(!currentProject || currentProject.id !== p.id) && allProjects.length > 1 && p.code !== 'PRJ-DEFAULT'">
                                            <button type="button" @click.stop="deleteProject(p.id, p.name)" style="background: none; border: none; color: #EF4444; cursor: pointer; padding: 4px;" title="Hapus Proyek">🗑️</button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vertical Divider --}}
                <div style="width: 1px; height: 24px; background: #E2E8F0; flex-shrink: 0;"></div>

                {{-- 2. Creation & Mode Tools --}}
                <div style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                    {{-- Undo / Redo --}}
                    <div style="display: flex; align-items: center; gap: 2px;">
                        <button type="button" @click="undo()" :disabled="historyIndex < 0" class="ims-tool-btn" :style="historyIndex < 0 ? 'opacity: 0.4; cursor: not-allowed;' : ''" title="Undo (Ctrl+Z)">
                            ↩️
                        </button>
                        <button type="button" @click="redo()" :disabled="historyIndex >= historyStack.length - 1" class="ims-tool-btn" :style="historyIndex >= historyStack.length - 1 ? 'opacity: 0.4; cursor: not-allowed;' : ''" title="Redo (Ctrl+Y)">
                            ↪️
                        </button>
                    </div>

                    <button type="button" @click="setMode('select')" :class="currentMode === 'select' ? 'active' : ''" class="ims-tool-btn" title="Mode Jelajah">
                        <span>🔍 Jelajah</span>
                    </button>

                    <button type="button" @click="startMeasure()" :class="currentMode === 'measure' ? 'active' : ''" class="ims-tool-btn" title="Ukur Jarak Kabel Bebas">
                        <span>📏 Ukur Jarak</span>
                    </button>
                    
                    {{-- Dropdown Add Node --}}
                    <div style="position: relative;">
                        <button type="button" @click="openMarkerMenu = !openMarkerMenu; openLineMenu = false; openProjectMenu = false;" :class="(currentMode === 'add_marker' || openMarkerMenu) ? 'active' : ''" class="ims-tool-btn">
                            <span>📍 Tambah Titik ▾</span>
                        </button>
                        <div x-show="openMarkerMenu" @click.outside="openMarkerMenu = false" style="position: absolute; top: calc(100% + 6px); left: 0; z-index: 999999; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: 0 16px 36px rgba(15,23,42,0.18); min-width: 260px; padding: 6px; display: flex; flex-direction: column; gap: 4px;">
                            <button type="button" @click="startAddMarker('pole')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                                <span>🗼</span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #1E293B;">Tiang Fiber (*Pole*)</div>
                                    <div style="font-size: 0.68rem; color: #64748B;">Tiang distribusi 7m / 9m PLN</div>
                                </div>
                            </button>
                            <button type="button" @click="startAddMarker('joint_box')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#ECFDF5'" onmouseout="this.style.background='transparent'">
                                <span>📦</span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #065F46;">Joint Box / Closure</div>
                                    <div style="font-size: 0.68rem; color: #059669;">Sambungan Splicing Kabel FO</div>
                                </div>
                            </button>
                            <button type="button" @click="startAddMarker('odc')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#FFFBEB'" onmouseout="this.style.background='transparent'">
                                <span>🗄️</span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #92400E;">ODC / FDT Kabinet</div>
                                    <div style="font-size: 0.68rem; color: #B45309;">Optical Distribution Cabinet</div>
                                </div>
                            </button>
                            <button type="button" @click="startAddMarker('olt')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background='transparent'">
                                <span>🖥️</span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #5B21B6;">Server OLT / POP</div>
                                    <div style="font-size: 0.68rem; color: #7C3AED;">Pusat Distribusi Utama GPON</div>
                                </div>
                            </button>
                            <button type="button" @click="startAddMarker('customer')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#FDF2F8'" onmouseout="this.style.background='transparent'">
                                <span>🏠</span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #9D174D;">Rumah Pelanggan</div>
                                    <div style="font-size: 0.68rem; color: #DB2777;">Titik Lokasi ONT / Rumah</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Dropdown Add Line --}}
                    <div style="position: relative;">
                        <button type="button" @click="openLineMenu = !openLineMenu; openMarkerMenu = false; openProjectMenu = false;" :class="(currentMode === 'draw_line' || openLineMenu) ? 'active' : ''" class="ims-tool-btn">
                            <span>〰️ Tarik Kabel ▾</span>
                        </button>
                        <div x-show="openLineMenu" @click.outside="openLineMenu = false" style="position: absolute; top: calc(100% + 6px); left: 0; z-index: 999999; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: 0 16px 36px rgba(15,23,42,0.18); min-width: 260px; padding: 6px; display: flex; flex-direction: column; gap: 4px;">
                            <button type="button" @click="startDrawLine('feeder')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background='transparent'">
                                <span style="width: 14px; height: 4px; background: #EF4444; border-radius: 2px;"></span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #991B1B;">Kabel Feeder Utama</div>
                                    <div style="font-size: 0.68rem; color: #DC2626;">Backbone 48/96/144 Core</div>
                                </div>
                            </button>
                            <button type="button" @click="startDrawLine('distribution')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#EFF6FF'" onmouseout="this.style.background='transparent'">
                                <span style="width: 14px; height: 4px; background: #0878E5; border-radius: 2px;"></span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #1E40AF;">Kabel Distribusi PON</div>
                                    <div style="font-size: 0.68rem; color: #2563EB;">Distribusi 12/24 Core ke ODP</div>
                                </div>
                            </button>
                            <button type="button" @click="startDrawLine('dropcore')" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#FFFBEB'" onmouseout="this.style.background='transparent'">
                                <span style="width: 14px; height: 4px; background: #F59E0B; border-radius: 2px; border-bottom: 2px dashed #D97706;"></span>
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #92400E;">Kabel Dropcore Pelanggan</div>
                                    <div style="font-size: 0.68rem; color: #B45309;">1/2 Core G.657A ke ONT</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 3. Live GIS Universal Search Bar --}}
                <div style="position: relative; flex: 1 1 170px; min-width: 130px; max-width: 240px;">
                    <div class="ims-search-box-container">
                        <div class="ims-search-box-icon">
                            <template x-if="!isGeocodingLoading">
                                <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </template>
                            <template x-if="isGeocodingLoading">
                                <span class="animate-spin text-xs">⏳</span>
                            </template>
                        </div>
                        <input 
                            type="text" 
                            class="ims-search-box-input"
                            x-model="searchQuery" 
                            @input="performGeocoding(searchQuery)"
                            @focus="searchFocused = true"
                            @click.outside="searchFocused = false"
                            placeholder="Cari Tiang, ODP, atau Alamat..." 
                        >
                        <button type="button" class="ims-search-box-clear" x-show="searchQuery" @click="searchQuery = ''; geocodingResults = [];">✕</button>
                    </div>

                    {{-- Search Results Dropdown --}}
                    <div 
                        x-show="searchFocused && searchResults.length > 0"
                        x-cloak
                        style="position: absolute; top: calc(100% + 6px); left: 0; right: 0; min-width: 280px; max-height: 320px; overflow-y: auto; background: #ffffff; border: 1px solid #CBD5E1; border-radius: 14px; box-shadow: 0 18px 40px rgba(15,23,42,0.24); z-index: 999999; padding: 6px;"
                    >
                        <template x-for="item in searchResults" :key="item.uniqueId">
                            <button 
                                type="button" 
                                @mousedown.prevent="flyToItem(item)"
                                style="text-align: left; width: 100%; padding: 7px 9px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.background='#F1F5F9'" 
                                onmouseout="this.style.background='transparent'"
                            >
                                <span x-text="item.iconHtml"></span>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.78rem; font-weight: 800; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="item.title"></div>
                                    <div style="font-size: 0.68rem; color: #64748B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="item.subtitle"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Vertical Divider --}}
                <div style="width: 1px; height: 24px; background: #E2E8F0; flex-shrink: 0;"></div>

                {{-- 4. Right Tool Group --}}
                <div style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                    {{-- Hidden KMZ/KML File Input --}}
                    <input type="file" id="ims-kmz-file-input" accept=".kmz,.kml" style="display: none;" @change="handleFileUpload($event)">

                    <div style="position: relative;" @click.outside="openExtraMenu = false">
                        <button type="button" @click="openExtraMenu = !openExtraMenu; openProjectMenu = false; openMarkerMenu = false; openLineMenu = false;" class="ims-tool-btn">
                            <span>⚙️ Menu & Alat ▾</span>
                        </button>

                        <div x-show="openExtraMenu" x-cloak style="position: absolute; top: calc(100% + 6px); right: 0; z-index: 999999; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 20px 48px rgba(15,23,42,0.22); min-width: 260px; padding: 10px; display: flex; flex-direction: column; gap: 6px;">
                            <div style="font-size: 0.7rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Data & Spreadsheet</div>
                            <button type="button" @click="openDataTableModal = true; openExtraMenu = false;" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#EFF6FF'" onmouseout="this.style.background='transparent'">
                                <span>📊</span>
                                <div style="font-size: 0.8rem; font-weight: 800; color: #1E40AF;">Tabel Data Jaringan</div>
                            </button>

                            <div style="height: 1px; background: #F1F5F9; margin: 4px 0;"></div>
                            <div style="font-size: 0.7rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Mode Lapisan Peta</div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                <button type="button" @click="setMapMode('roadmap'); openExtraMenu = false;" style="padding: 6px; border-radius: 8px; font-size: 11px; font-weight: 800; cursor: pointer; border: 1px solid #CBD5E1;" :style="mapMode === 'roadmap' ? 'background: #EFF6FF; color: #0878E5; border-color: #0878E5;' : 'background: #F8FAFC; color: #475569;'">🗺️ Roadmap</button>
                                <button type="button" @click="setMapMode('hybrid'); openExtraMenu = false;" style="padding: 6px; border-radius: 8px; font-size: 11px; font-weight: 800; cursor: pointer; border: 1px solid #CBD5E1;" :style="mapMode === 'hybrid' ? 'background: #EFF6FF; color: #0878E5; border-color: #0878E5;' : 'background: #F8FAFC; color: #475569;'">🛰️ Satelit</button>
                            </div>

                            <div style="height: 1px; background: #F1F5F9; margin: 4px 0;"></div>
                            <div style="font-size: 0.7rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Impor & Ekspor</div>
                            <button type="button" @click="openExtraMenu = false; document.getElementById('ims-kmz-file-input').click();" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#ECFDF5'" onmouseout="this.style.background='transparent'">
                                <span>📥</span>
                                <div style="font-size: 0.8rem; font-weight: 800; color: #065F46;">Import KMZ / KML</div>
                            </button>
                            <button type="button" @click="openExtraMenu = false; exportKml();" style="text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background='transparent'">
                                <span>📤</span>
                                <div style="font-size: 0.8rem; font-weight: 800; color: #92400E;">Export File KML</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Drawing Sub-bar --}}
            <div x-show="currentMode === 'draw_line'" x-cloak style="margin-top: 8px; padding: 8px 12px; border-radius: 10px; background: #FEF3C7; border: 1.5px dashed #F59E0B; display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 0.76rem; color: #92400E;">
                <div>
                    <strong>⚡ Menarik Kabel <span x-text="activeElementType"></span>:</strong> Klik titik di peta untuk menanam kabel. Panjang: <strong><span x-text="currentLineDistance"></span> meter</strong>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button type="button" @click="undoLastPoint()" :disabled="currentLinePoints.length === 0" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #ffffff; border: 1px solid #cbd5e1; cursor: pointer;">↩️ Undo Titik</button>
                    <button type="button" @click="finishDrawLine()" :disabled="currentLinePoints.length < 2" style="padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #059669; color: #ffffff; border: none; cursor: pointer;">✓ Selesai & Simpan</button>
                    <button type="button" @click="cancelDrawing()" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #ffffff; color: #DC2626; border: 1px solid #DC2626; cursor: pointer;">✕ Batal</button>
                </div>
            </div>

            {{-- Measurement Sub-bar --}}
            <div x-show="currentMode === 'measure'" x-cloak style="margin-top: 8px; padding: 8px 12px; border-radius: 10px; background: #F5F3FF; border: 1.5px dashed #7C3AED; display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 0.76rem; color: #5B21B6;">
                <div>
                    <strong>📏 Penggaris Jarak:</strong> Total: <strong><span x-text="measureDistance"></span> m (<span x-text="(measureDistance / 1000).toFixed(2)"></span> Km)</strong>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button type="button" @click="undoMeasurePoint()" :disabled="measurePoints.length === 0" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #ffffff; border: 1px solid #cbd5e1; cursor: pointer;">↩️ Undo</button>
                    <button type="button" @click="clearMeasure()" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #ffffff; color: #DC2626; border: 1px solid #FECACA; cursor: pointer;">🗑️ Reset</button>
                    <button type="button" @click="setMode('select')" style="padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #7C3AED; color: #ffffff; border: none; cursor: pointer;">✕ Tutup</button>
                </div>
            </div>

            {{-- Add Marker Sub-bar --}}
            <div x-show="currentMode === 'add_marker'" x-cloak style="margin-top: 8px; padding: 8px 12px; border-radius: 10px; background: #EAF5FF; border: 1.5px dashed #0878E5; display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 0.76rem; color: #0757B8;">
                <div>
                    <b>📍 Penempatan Marker:</b> Klik pada peta untuk menempatkan <b><span x-text="activeElementType"></span></b>.
                </div>
                <button type="button" @click="cancelDrawing()" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; background: #ffffff; color: #DC2626; border: 1px solid #DC2626; cursor: pointer;">✕ Batal</button>
            </div>
        </div>

        {{-- Map Container & Floating Sidebar --}}
        <div class="ims-map-container-wrap">
            {{-- Floating Sidebar Drawer --}}
            <div id="ims-ftth-sidebar-drawer" x-show="openSidebarDrawer" x-cloak class="ims-drawer-root">
                <div class="ims-drawer-header">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font-size: 18px;">🗺️</div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 900; line-height: 1.2;">Objek & Layer GIS</div>
                            <div style="font-size: 0.68rem; color: #94A3B8;" x-text="currentProject ? currentProject.name : 'Utama'"></div>
                        </div>
                    </div>
                    <button type="button" @click="openSidebarDrawer = false" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #94A3B8; cursor: pointer; padding: 4px 8px; border-radius: 6px;">✕</button>
                </div>

                {{-- Drawer Tabs --}}
                <div class="ims-drawer-tabs-row">
                    <div style="display: flex; background: #F1F5F9; padding: 3px; border-radius: 10px; border: 1px solid #E2E8F0; gap: 4px; height: 100%; align-items: center;">
                        <button type="button" @click="sidebarTab = 'objects'" class="ims-sidebar-tab-btn" :style="sidebarTab === 'objects' ? 'background: #ffffff; color: #0878E5; font-weight: 900;' : 'background: transparent; color: #64748B;'">
                            <span>Objek</span> (<span x-text="customElements.length"></span>)
                        </button>
                        <button type="button" @click="sidebarTab = 'layers'" class="ims-sidebar-tab-btn" :style="sidebarTab === 'layers' ? 'background: #ffffff; color: #0878E5; font-weight: 900;' : 'background: transparent; color: #64748B;'">
                            <span>Layer</span>
                        </button>
                        <button type="button" @click="sidebarTab = 'metrics'" class="ims-sidebar-tab-btn" :style="sidebarTab === 'metrics' ? 'background: #ffffff; color: #0878E5; font-weight: 900;' : 'background: transparent; color: #64748B;'">
                            <span>Ringkasan</span>
                        </button>
                    </div>
                </div>

                {{-- Tab 1: Objek --}}
                <div x-show="sidebarTab === 'objects'" x-cloak class="ims-drawer-tab-objects">
                    <div class="ims-drawer-search-row">
                        <input type="text" x-model="sidebarSearch" placeholder="Saring nama kabel, tiang, node..." style="width: 100%; height: 34px; font-size: 0.76rem; border-radius: 8px; border: 1.5px solid #CBD5E1; padding: 0 10px; background: #F8FAFC; outline: none;">
                        <div style="display: flex; gap: 6px; overflow-x: auto;">
                            <button type="button" @click="sidebarCategoryFilter = 'all'" class="ims-sidebar-filter-btn" :style="sidebarCategoryFilter === 'all' ? 'background: #0878E5; color: #ffffff; border-color: #0878E5;' : 'background: #F8FAFC; color: #475569; border-color: #E2E8F0;'">Semua</button>
                            <button type="button" @click="sidebarCategoryFilter = 'line'" class="ims-sidebar-filter-btn" :style="sidebarCategoryFilter === 'line' ? 'background: #0878E5; color: #ffffff; border-color: #0878E5;' : 'background: #EFF6FF; color: #0878E5; border-color: #BFDBFE;'">Kabel</button>
                            <button type="button" @click="sidebarCategoryFilter = 'marker'" class="ims-sidebar-filter-btn" :style="sidebarCategoryFilter === 'marker' ? 'background: #16A34A; color: #ffffff; border-color: #16A34A;' : 'background: #F0FDF4; color: #16A34A; border-color: #BBF7D0;'">Titik</button>
                        </div>
                    </div>

                    <div class="ims-drawer-object-scroll">
                        <template x-for="item in filteredSidebarElements" :key="item.id">
                            <div style="margin-bottom: 8px; padding: 10px; border-radius: 10px; background: #ffffff; border: 1.5px solid #E2E8F0; display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #0F172A;" x-text="item.name"></div>
                                    <span style="font-size: 10px; padding: 1px 6px; border-radius: 4px; background: #F1F5F9; font-weight: 700;" x-text="item.element_type"></span>
                                </div>
                                <div style="font-size: 0.68rem; color: #64748B;" x-text="item.category === 'line' ? ('Panjang: ~' + (item.length_meters || 0) + ' m') : ('GPS: ' + (item.latitude ? item.latitude.toFixed(5) : '-') + ', ' + (item.longitude ? item.longitude.toFixed(5) : '-'))"></div>
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px; padding-top: 6px; border-top: 1px solid #F1F5F9;">
                                    <button type="button" @click="openDetail(item)" style="padding: 3px 8px; border-radius: 6px; background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; font-size: 11px; font-weight: 800; cursor: pointer;">Detail</button>
                                    <button type="button" @click="flyToCustomElement(item)" style="padding: 3px 8px; border-radius: 6px; background: #EFF6FF; color: #0878E5; border: 1px solid #BFDBFE; font-size: 11px; font-weight: 800; cursor: pointer;">Fokus</button>
                                    <button type="button" @click="deleteCustomElementDirect(item.id, item.name)" style="padding: 3px 8px; border-radius: 6px; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; font-size: 11px; font-weight: 800; cursor: pointer;">Hapus</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Tab 2: Layer --}}
                <div x-show="sidebarTab === 'layers'" x-cloak class="ims-drawer-layer-scroll">
                    <div style="font-size: 0.74rem; font-weight: 800; color: #334155; margin-bottom: 8px;">Visibilitas Lapisan</div>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>📍 ODP Database</span>
                        <input type="checkbox" :checked="layerVisibility.odp" @change="toggleLayer('odp')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🗼 Tiang Fiber</span>
                        <input type="checkbox" :checked="layerVisibility.pole" @change="toggleLayer('pole')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>📦 Joint Box / Closure</span>
                        <input type="checkbox" :checked="layerVisibility.joint_box" @change="toggleLayer('joint_box')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🗄️ ODC / FDT</span>
                        <input type="checkbox" :checked="layerVisibility.odc" @change="toggleLayer('odc')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🖥️ Server OLT</span>
                        <input type="checkbox" :checked="layerVisibility.olt" @change="toggleLayer('olt')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🏠 Rumah Pelanggan</span>
                        <input type="checkbox" :checked="layerVisibility.customer" @change="toggleLayer('customer')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🔴 Kabel Feeder</span>
                        <input type="checkbox" :checked="layerVisibility.feeder" @change="toggleLayer('feeder')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🔵 Kabel Distribusi</span>
                        <input type="checkbox" :checked="layerVisibility.distribution" @change="toggleLayer('distribution')">
                    </label>
                    <label style="margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                        <span>🟡 Kabel Dropcore</span>
                        <input type="checkbox" :checked="layerVisibility.dropcore" @change="toggleLayer('dropcore')">
                    </label>
                </div>

                {{-- Tab 3: Ringkasan --}}
                <div x-show="sidebarTab === 'metrics'" x-cloak class="ims-drawer-layer-scroll">
                    <div style="background: linear-gradient(135deg, #0878E5, #02509D); border-radius: 12px; padding: 12px; color: #ffffff;">
                        <div style="font-size: 0.68rem; text-transform: uppercase;">Total Panjang Kabel Fiber</div>
                        <div style="font-size: 1.5rem; font-weight: 900; margin-top: 2px;">
                            <span x-text="networkMetrics.totalCableKm"></span> km
                        </div>
                        <div style="font-size: 0.68rem; opacity: 0.8;" x-text="networkMetrics.totalCableCount + ' segmen rute kabel'"></div>
                    </div>
                </div>
            </div>

            {{-- Floating Toggle Buttons --}}
            <div style="position: absolute; top: 12px; left: 12px; z-index: 500;">
                <button type="button" @click="openSidebarDrawer = !openSidebarDrawer" :class="openSidebarDrawer ? 'ims-floating-layer-btn-active' : 'ims-floating-layer-btn'" title="Panel Objek & Filter">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M4 6h16M4 12h16M4 18h7"/></svg>
                </button>
            </div>
            <div style="position: absolute; top: 12px; right: 12px; z-index: 500;">
                <button type="button" @click="toggleFullscreen()" :class="isFullscreen ? 'ims-floating-layer-btn-active' : 'ims-floating-layer-btn'" title="Fullscreen">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
                </button>
            </div>

            {{-- Map Canvas --}}
            <div id="ims-ftth-builder-canvas" class="ims-map-canvas"></div>
        </div>

        {{-- Legend Footer --}}
        <div style="padding: 0.55rem 1.25rem; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; font-size: 0.72rem; color: #475569;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px;">
                <span>📍 ODP Database</span>
                <span>🗼 Tiang Fiber</span>
                <span>📦 Joint Box</span>
                <span>🗄️ ODC / FDT</span>
                <span>🖥️ Server OLT</span>
                <span>🏠 Rumah Pelanggan</span>
                <span>🔴 Feeder</span>
                <span>🔵 Distribusi</span>
                <span>🟡 Dropcore</span>
            </div>
            <span>💡 Klik pada titik atau garis untuk melihat detail spesifikasi.</span>
        </div>
    </div>

    {{-- ── 3. MODAL TAMBAH PROYEK BARU ── --}}
    <div x-show="openNewProjectModal" x-cloak class="ims-modal-overlay-root" style="display: flex;">
        <div @click.outside="openNewProjectModal = false" class="ims-modal-card-dialog" style="max-width: 440px; padding: 1.5rem;">
            <h3 style="font-size: 1.05rem; font-weight: 900; color: #0F172A; margin-bottom: 12px;">Tambah Proyek GIS Baru</h3>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 0.74rem; font-weight: 800; margin-bottom: 4px;">Nama Proyek *</label>
                <input type="text" x-model="newProjectName" placeholder="Contoh: Area Arcamanik..." style="width: 100%; height: 38px; padding: 0 10px; border: 1.5px solid #CBD5E1; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.74rem; font-weight: 800; margin-bottom: 4px;">Deskripsi</label>
                <textarea x-model="newProjectDescription" rows="2" style="width: 100%; padding: 8px; border: 1.5px solid #CBD5E1; border-radius: 8px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" @click="openNewProjectModal = false" style="padding: 6px 12px; border: 1px solid #CBD5E1; background: #ffffff; border-radius: 8px; font-weight: 800; font-size: 11px;">Batal</button>
                <button type="button" @click="submitNewProject()" style="padding: 6px 14px; background: #0878E5; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; font-size: 11px;">Simpan</button>
            </div>
        </div>
    </div>

    {{-- ── 4. MODAL DETAIL & SPESIFIKASI TEKNIS ── --}}
    <div x-show="openDetailModal" x-cloak class="ims-modal-overlay-root" style="display: flex;">
        <div @click.outside="openDetailModal = false" class="ims-modal-card-dialog">
            <div style="padding: 14px 18px; background: #0F172A; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <div style="font-size: 0.95rem; font-weight: 800;" x-text="detailElement ? detailElement.name : 'Detail'"></div>
                <button type="button" @click="openDetailModal = false" style="background: none; border: none; color: #ffffff; cursor: pointer; font-size: 16px;">✕</button>
            </div>
            <div style="padding: 10px 18px; background: #ffffff;">
                <div class="ims-modal-tab-nav">
                    <button type="button" class="ims-modal-tab-btn" :class="detailTab === 'specs' && 'is-active'" @click="detailTab = 'specs'">Spesifikasi</button>
                    <button type="button" class="ims-modal-tab-btn" :class="detailTab === 'photos' && 'is-active'" @click="detailTab = 'photos'">Foto Lapangan</button>
                    <button type="button" class="ims-modal-tab-btn" :class="detailTab === 'notes' && 'is-active'" @click="detailTab = 'notes'">Catatan</button>
                </div>
            </div>
            <div style="padding: 16px 18px; overflow-y: auto; max-height: 400px;">
                <div x-show="detailTab === 'specs'" style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 0.74rem; font-weight: 800; margin-bottom: 4px;">Nama Elemen *</label>
                        <input type="text" x-model="detailForm.name" style="width: 100%; height: 38px; padding: 0 10px; border: 1.5px solid #CBD5E1; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.74rem; font-weight: 800; margin-bottom: 4px;">Deskripsi / Kode</label>
                        <input type="text" x-model="detailForm.code" placeholder="Kode identitas..." style="width: 100%; height: 38px; padding: 0 10px; border: 1.5px solid #CBD5E1; border-radius: 8px;">
                    </div>
                </div>

                <div x-show="detailTab === 'photos'" style="display: flex; flex-direction: column; gap: 12px;">
                    <input type="file" @change="uploadPhoto($event)" accept="image/*" style="font-size: 11px;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        <template x-for="(img, idx) in (detailForm.photos || [])" :key="idx">
                            <div style="position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #CBD5E1;">
                                <img :src="img.url" style="width: 100%; height: 80px; object-fit: cover;">
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="detailTab === 'notes'">
                    <textarea x-model="detailForm.notes" rows="4" placeholder="Catatan teknis..." style="width: 100%; padding: 8px; border: 1.5px solid #CBD5E1; border-radius: 8px;"></textarea>
                </div>
            </div>
            <div style="padding: 12px 18px; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" @click="openDetailModal = false" style="padding: 6px 12px; border: 1px solid #CBD5E1; background: #ffffff; border-radius: 8px; font-weight: 800; font-size: 11px;">Batal</button>
                <button type="button" @click="saveElementDetail()" style="padding: 6px 14px; background: #0878E5; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; font-size: 11px;">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<script>
function imsFtthNetworkMapComponent(initialProjects, initialCurrentProject, initialElements, initialOdps) {
    return {
        allProjects: initialProjects || [],
        currentProject: initialCurrentProject || null,
        customElements: initialElements || [],
        allOdps: initialOdps || [],

        map: null,
        roadmapTileLayer: null,
        hybridTileLayer: null,
        mapMode: 'roadmap',
        isFullscreen: false,

        // Modes: 'select', 'measure', 'add_marker', 'draw_line'
        currentMode: 'select',
        activeElementType: 'pole',

        // Draw Line state
        currentLinePoints: [],
        currentLineDistance: 0,
        tempDrawPolyline: null,
        tempVertexMarkers: [],

        // Measure state
        measurePoints: [],
        measureDistance: 0,
        measurePolyline: null,
        measureMarkers: [],

        // Sidebar state
        openSidebarDrawer: true,
        sidebarTab: 'objects',
        sidebarSearch: '',
        sidebarCategoryFilter: 'all',

        // Menus
        openProjectMenu: false,
        openMarkerMenu: false,
        openLineMenu: false,
        openExtraMenu: false,
        openNewProjectModal: false,
        newProjectName: '',
        newProjectDescription: '',

        // Detail Modal
        openDetailModal: false,
        detailTab: 'specs',
        detailElement: null,
        detailForm: { name: '', code: '', notes: '', photos: [] },

        // Search Geocoding
        searchQuery: '',
        searchFocused: false,
        isGeocodingLoading: false,
        geocodingResults: [],

        // Layer Visibility
        layerVisibility: {
            odp: true, pole: true, joint_box: true, odc: true, olt: true,
            customer: true, feeder: true, distribution: true, dropcore: true
        },

        // Leaflet layers storage
        leafletMarkers: {},
        leafletLines: {},
        odpMarkers: [],

        // History
        historyStack: [],
        historyIndex: -1,

        get networkMetrics() {
            let totalMeters = 0;
            let totalCount = 0;
            this.customElements.filter(e => e.category === 'line').forEach(line => {
                totalMeters += (line.length_meters || 0);
                totalCount++;
            });
            return {
                totalCableKm: (totalMeters / 1000).toFixed(2),
                totalCableMeters: Math.round(totalMeters),
                totalCableCount: totalCount
            };
        },

        get filteredSidebarElements() {
            let list = this.customElements;
            if (this.sidebarCategoryFilter !== 'all') {
                list = list.filter(e => e.category === this.sidebarCategoryFilter);
            }
            if (this.sidebarSearch) {
                let q = this.sidebarSearch.toLowerCase();
                list = list.filter(e => e.name.toLowerCase().includes(q));
            }
            return list;
        },

        get searchResults() {
            let results = [];
            if (this.searchQuery) {
                let q = this.searchQuery.toLowerCase();
                this.customElements.forEach(e => {
                    if (e.name.toLowerCase().includes(q)) {
                        results.push({
                            uniqueId: 'elem-' + e.id,
                            title: e.name,
                            subtitle: e.element_type + (e.length_meters ? ' (' + e.length_meters + 'm)' : ''),
                            iconHtml: e.category === 'line' ? '〰️' : '📍',
                            element: e
                        });
                    }
                });
                this.allOdps.forEach(o => {
                    if (o.name_odp && o.name_odp.toLowerCase().includes(q)) {
                        results.push({
                            uniqueId: 'odp-' + o.kode_odp,
                            title: o.name_odp,
                            subtitle: 'ODP: ' + o.kode_odp,
                            iconHtml: '⚡',
                            odp: o
                        });
                    }
                });
                this.geocodingResults.forEach(g => {
                    results.push(g);
                });
            }
            return results;
        },

        init() {
            this.$nextTick(() => {
                this.initMap();
            });
        },

        initMap() {
            if (this.map) return;

            // Default center (Bandung / Indonesia region)
            let center = [-6.9175, 107.6191];
            if (this.customElements.length > 0) {
                let firstMarker = this.customElements.find(e => e.latitude && e.longitude);
                if (firstMarker) {
                    center = [firstMarker.latitude, firstMarker.longitude];
                }
            }

            this.map = L.map('ims-ftth-builder-canvas', {
                center: center,
                zoom: 14,
                zoomControl: false
            });

            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            this.roadmapTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.map);

            this.hybridTileLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                attribution: '&copy; Google Maps'
            });

            this.map.on('click', (e) => this.handleMapClick(e));

            this.renderAllElements();
            this.renderAllOdps();
        },

        setMapMode(mode) {
            this.mapMode = mode;
            if (mode === 'hybrid') {
                this.map.removeLayer(this.roadmapTileLayer);
                this.map.addLayer(this.hybridTileLayer);
            } else {
                this.map.removeLayer(this.hybridTileLayer);
                this.map.addLayer(this.roadmapTileLayer);
            }
        },

        toggleFullscreen() {
            let el = document.getElementById('ims-ftth-map-card-root');
            if (!this.isFullscreen) {
                if (el.requestFullscreen) { el.requestFullscreen(); }
                this.isFullscreen = true;
            } else {
                if (document.exitFullscreen) { document.exitFullscreen(); }
                this.isFullscreen = false;
            }
            setTimeout(() => { this.map.invalidateSize(); }, 200);
        },

        setMode(mode) {
            this.currentMode = mode;
            if (mode === 'select') {
                this.cancelDrawing();
                this.clearMeasure();
            }
        },

        startAddMarker(type) {
            this.currentMode = 'add_marker';
            this.activeElementType = type;
            this.openMarkerMenu = false;
        },

        startDrawLine(type) {
            this.currentMode = 'draw_line';
            this.activeElementType = type;
            this.openLineMenu = false;
            this.currentLinePoints = [];
            this.currentLineDistance = 0;
            this.clearTempLine();
        },

        startMeasure() {
            this.currentMode = 'measure';
            this.measurePoints = [];
            this.measureDistance = 0;
            this.clearMeasure();
        },

        handleMapClick(e) {
            let lat = e.latlng.lat;
            let lng = e.latlng.lng;

            if (this.currentMode === 'add_marker') {
                this.addMarkerElement(lat, lng, this.activeElementType);
                this.setMode('select');
            } else if (this.currentMode === 'draw_line') {
                this.currentLinePoints.push([lat, lng]);
                this.updateTempLine();
            } else if (this.currentMode === 'measure') {
                this.measurePoints.push([lat, lng]);
                this.updateMeasureLine();
            }
        },

        updateTempLine() {
            if (this.tempDrawPolyline) {
                this.map.removeLayer(this.tempDrawPolyline);
            }
            let color = this.activeElementType === 'feeder' ? '#EF4444' : (this.activeElementType === 'dropcore' ? '#F59E0B' : '#0878E5');
            this.tempDrawPolyline = L.polyline(this.currentLinePoints, {
                color: color,
                weight: 4,
                dashArray: this.activeElementType === 'dropcore' ? '5, 5' : null
            }).addTo(this.map);

            let dist = 0;
            for (let i = 0; i < this.currentLinePoints.length - 1; i++) {
                dist += this.map.distance(this.currentLinePoints[i], this.currentLinePoints[i+1]);
            }
            this.currentLineDistance = Math.round(dist);
        },

        undoLastPoint() {
            if (this.currentLinePoints.length > 0) {
                this.currentLinePoints.pop();
                this.updateTempLine();
            }
        },

        finishDrawLine() {
            if (this.currentLinePoints.length < 2) return;
            let name = 'Jalur ' + this.activeElementType.toUpperCase() + ' #' + (this.customElements.filter(e => e.category === 'line').length + 1);
            let color = this.activeElementType === 'feeder' ? '#EF4444' : (this.activeElementType === 'dropcore' ? '#F59E0B' : '#0878E5');
            
            let data = {
                project_id: this.currentProject ? this.currentProject.id : 1,
                category: 'line',
                element_type: this.activeElementType,
                name: name,
                color: color,
                coordinates: this.currentLinePoints,
                length_meters: this.currentLineDistance,
                line_width: 3.5,
                line_dash: this.activeElementType === 'dropcore' ? 'dashed' : 'solid'
            };

            this.persistGisElement(data);
            this.cancelDrawing();
        },

        cancelDrawing() {
            this.clearTempLine();
            this.currentLinePoints = [];
            this.currentLineDistance = 0;
            this.currentMode = 'select';
        },

        clearTempLine() {
            if (this.tempDrawPolyline) {
                this.map.removeLayer(this.tempDrawPolyline);
                this.tempDrawPolyline = null;
            }
        },

        updateMeasureLine() {
            if (this.measurePolyline) {
                this.map.removeLayer(this.measurePolyline);
            }
            this.measurePolyline = L.polyline(this.measurePoints, { color: '#7C3AED', weight: 4, dashArray: '6, 6' }).addTo(this.map);
            let dist = 0;
            for (let i = 0; i < this.measurePoints.length - 1; i++) {
                dist += this.map.distance(this.measurePoints[i], this.measurePoints[i+1]);
            }
            this.measureDistance = Math.round(dist);
        },

        undoMeasurePoint() {
            if (this.measurePoints.length > 0) {
                this.measurePoints.pop();
                this.updateMeasureLine();
            }
        },

        clearMeasure() {
            if (this.measurePolyline) {
                this.map.removeLayer(this.measurePolyline);
                this.measurePolyline = null;
            }
            this.measurePoints = [];
            this.measureDistance = 0;
        },

        addMarkerElement(lat, lng, type) {
            let name = type.toUpperCase() + ' #' + (this.customElements.filter(e => e.element_type === type).length + 1);
            let color = type === 'pole' ? '#334155' : (type === 'joint_box' ? '#059669' : (type === 'odc' ? '#D97706' : (type === 'olt' ? '#7C3AED' : '#DB2777')));
            let data = {
                project_id: this.currentProject ? this.currentProject.id : 1,
                category: 'marker',
                element_type: type,
                name: name,
                color: color,
                latitude: lat,
                longitude: lng
            };
            this.persistGisElement(data);
        },

        persistGisElement(data) {
            fetch("{{ route('teknik.peta-jaringan.element.save') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.element) {
                    let idx = this.customElements.findIndex(e => e.id === res.element.id);
                    if (idx >= 0) {
                        this.customElements[idx] = res.element;
                    } else {
                        this.customElements.push(res.element);
                    }
                    this.renderElementOnMap(res.element);
                }
            });
        },

        renderAllElements() {
            this.customElements.forEach(el => this.renderElementOnMap(el));
        },

        renderElementOnMap(el) {
            if (el.category === 'marker' && el.latitude && el.longitude) {
                if (this.leafletMarkers[el.id]) {
                    this.map.removeLayer(this.leafletMarkers[el.id]);
                }
                let iconHtml = '<div style="width: 28px; height: 28px; border-radius: 50%; background: ' + (el.color || '#0878E5') + '; border: 2px solid #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 13px;">' + (el.element_type === 'pole' ? '🗼' : (el.element_type === 'joint_box' ? '📦' : (el.element_type === 'odc' ? '🗄️' : (el.element_type === 'olt' ? '🖥️' : '🏠')))) + '</div>';
                let customIcon = L.divIcon({
                    html: iconHtml,
                    className: 'custom-ftth-node',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                let marker = L.marker([el.latitude, el.longitude], { icon: customIcon }).addTo(this.map);
                marker.bindPopup('<b>' + el.name + '</b><br>Tipe: ' + el.element_type);
                this.leafletMarkers[el.id] = marker;
            } else if (el.category === 'line' && el.coordinates && el.coordinates.length > 1) {
                if (this.leafletLines[el.id]) {
                    this.map.removeLayer(this.leafletLines[el.id]);
                }
                let line = L.polyline(el.coordinates, {
                    color: el.color || '#0878E5',
                    weight: el.line_width || 3.5,
                    dashArray: el.line_dash === 'dashed' ? '5, 5' : null
                }).addTo(this.map);
                line.bindPopup('<b>' + el.name + '</b><br>Panjang: ~' + (el.length_meters || 0) + ' m');
                this.leafletLines[el.id] = line;
            }
        },

        renderAllOdps() {
            this.allOdps.forEach(odp => {
                if (odp.latitude && odp.longitude) {
                    let iconHtml = '<div style="width: 24px; height: 24px; border-radius: 50%; background: #0878E5; border: 2px solid #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 11px;">⚡</div>';
                    let customIcon = L.divIcon({
                        html: iconHtml,
                        className: 'odp-pin',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });
                    let marker = L.marker([odp.latitude, odp.longitude], { icon: customIcon }).addTo(this.map);
                    marker.bindPopup('<b>' + odp.name_odp + '</b><br>Kode: ' + odp.kode_odp + '<br>Kapasitas: ' + odp.capacity_odp + ' Port');
                    this.odpMarkers.push(marker);
                }
            });
        },

        toggleLayer(type) {
            this.layerVisibility[type] = !this.layerVisibility[type];
            let isVisible = this.layerVisibility[type];
            if (type === 'odp') {
                this.odpMarkers.forEach(m => isVisible ? this.map.addLayer(m) : this.map.removeLayer(m));
            } else {
                this.customElements.filter(e => e.element_type === type).forEach(e => {
                    if (e.category === 'marker' && this.leafletMarkers[e.id]) {
                        isVisible ? this.map.addLayer(this.leafletMarkers[e.id]) : this.map.removeLayer(this.leafletMarkers[e.id]);
                    } else if (e.category === 'line' && this.leafletLines[e.id]) {
                        isVisible ? this.map.addLayer(this.leafletLines[e.id]) : this.map.removeLayer(this.leafletLines[e.id]);
                    }
                });
            }
        },

        flyToCustomElement(item) {
            if (item.latitude && item.longitude) {
                this.map.flyTo([item.latitude, item.longitude], 17);
                if (this.leafletMarkers[item.id]) {
                    this.leafletMarkers[item.id].openPopup();
                }
            } else if (item.coordinates && item.coordinates.length > 0) {
                this.map.flyTo(item.coordinates[0], 17);
                if (this.leafletLines[item.id]) {
                    this.leafletLines[item.id].openPopup();
                }
            }
        },

        flyToItem(item) {
            if (item.element) {
                this.flyToCustomElement(item.element);
            } else if (item.odp && item.odp.latitude && item.odp.longitude) {
                this.map.flyTo([item.odp.latitude, item.odp.longitude], 17);
            }
            this.searchFocused = false;
        },

        openDetail(item) {
            this.detailElement = item;
            this.detailForm.name = item.name;
            this.detailForm.code = (item.metadata && item.metadata.code) || '';
            this.detailForm.notes = (item.metadata && item.metadata.notes) || '';
            this.detailForm.photos = (item.metadata && item.metadata.photos) || [];
            this.openDetailModal = true;
        },

        saveElementDetail() {
            if (!this.detailElement) return;
            let meta = this.detailElement.metadata || {};
            meta.code = this.detailForm.code;
            meta.notes = this.detailForm.notes;
            meta.photos = this.detailForm.photos;

            let data = {
                id: this.detailElement.id,
                project_id: this.detailElement.project_id,
                category: this.detailElement.category,
                element_type: this.detailElement.element_type,
                name: this.detailForm.name,
                color: this.detailElement.color,
                latitude: this.detailElement.latitude,
                longitude: this.detailElement.longitude,
                coordinates: this.detailElement.coordinates,
                length_meters: this.detailElement.length_meters,
                metadata: meta
            };
            this.persistGisElement(data);
            this.openDetailModal = false;
        },

        uploadPhoto(e) {
            if (e.target.files.length === 0) return;
            let fd = new FormData();
            fd.append('photo', e.target.files[0]);
            fetch("{{ route('teknik.peta-jaringan.upload-photo') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (!this.detailForm.photos) this.detailForm.photos = [];
                    this.detailForm.photos.push({ url: res.url, name: res.originalName });
                }
            });
        },

        deleteCustomElementDirect(id, name) {
            if (!confirm('Apakah Anda yakin ingin menghapus ' + name + '?')) return;
            fetch("{{ url('/teknik/peta-jaringan/element') }}/" + id + "/delete", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (this.leafletMarkers[id]) {
                        this.map.removeLayer(this.leafletMarkers[id]);
                        delete this.leafletMarkers[id];
                    }
                    if (this.leafletLines[id]) {
                        this.map.removeLayer(this.leafletLines[id]);
                        delete this.leafletLines[id];
                    }
                    this.customElements = this.customElements.filter(e => e.id !== id);
                }
            });
        },

        submitNewProject() {
            if (!this.newProjectName) return;
            fetch("{{ route('teknik.peta-jaringan.project.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: this.newProjectName,
                    description: this.newProjectDescription
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.project) {
                    window.location.href = "{{ route('teknik.peta-jaringan') }}?project_id=" + res.project.id;
                }
            });
        },

        switchProject(id) {
            window.location.href = "{{ route('teknik.peta-jaringan') }}?project_id=" + id;
        },

        deleteProject(id, name) {
            if (!confirm('Hapus proyek "' + name + '" beserta seluruh objek di dalamnya?')) return;
            fetch("{{ url('/teknik/peta-jaringan/project') }}/" + id + "/delete", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    window.location.href = "{{ route('teknik.peta-jaringan') }}";
                }
            });
        },

        performGeocoding(query) {
            if (!query || query.length < 3) {
                this.geocodingResults = [];
                return;
            }
            this.isGeocodingLoading = true;
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    this.isGeocodingLoading = false;
                    this.geocodingResults = (data || []).slice(0, 4).map(item => ({
                        uniqueId: 'geo-' + item.place_id,
                        title: item.display_name.split(',')[0],
                        subtitle: item.display_name,
                        iconHtml: '📍',
                        element: {
                            latitude: parseFloat(item.lat),
                            longitude: parseFloat(item.lon),
                            name: item.display_name
                        }
                    }));
                })
                .catch(() => { this.isGeocodingLoading = false; });
        },

        handleFileUpload(e) {
            if (e.target.files.length === 0) return;
            let fd = new FormData();
            fd.append('file', e.target.files[0]);
            fd.append('project_id', this.currentProject ? this.currentProject.id : 1);
            fetch("{{ route('teknik.peta-jaringan.import-kmz') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                alert(res.message);
                if (res.success) {
                    window.location.reload();
                }
            });
        },

        exportKml() {
            let prjId = this.currentProject ? this.currentProject.id : 1;
            window.location.href = "{{ url('/teknik/peta-jaringan/export-kml') }}/" + prjId;
        },

        calculateTotalCableKm() {
            let total = 0;
            this.customElements.filter(e => e.category === 'line').forEach(l => {
                total += (l.length_meters || 0);
            });
            return (total / 1000).toFixed(2);
        },

        undo() {},
        redo() {}
    };
}
</script>
@endsection
