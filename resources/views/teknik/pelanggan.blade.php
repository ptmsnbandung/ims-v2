@extends('layouts.app')

@section('title', 'Data Pelanggan IMS')
@section('page_title', 'Data Pelanggan')

@section('content')
<div class="space-y-6"
     x-data="{
         // Tab Status Active: 'aktif', 'terminasi', 'suspend', 'all'
         activeTab: '{{ ($filters['status'] ?? '') == '23' ? 'terminasi' : (($filters['status'] ?? '') == '21' ? 'suspend' : 'aktif') }}',
         
         // Quick Search
         searchQuery: '{{ $filters['search'] ?? '' }}',
     }">
    
    <!-- Top Header: Breadcrumbs & Total KPI Badges -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/20">
        <!-- Breadcrumb & Title -->
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-white transition">IMS</a>
                <svg class="w-3.5 h-3.5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <span class="text-slate-200 font-semibold">Pelanggan</span>
            </div>
            <h2 class="text-lg font-bold text-white tracking-wide">Data Pelanggan Terdaftar</h2>
        </div>

        <!-- Overall KPI Metric Pills -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="px-3.5 py-1.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center gap-2 text-xs">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="text-slate-400">Total:</span>
                <strong class="text-white font-mono font-bold">{{ number_format($bwCounts['total_aktif'] + $bwCounts['total_terminasi'] + $bwCounts['total_suspend']) }}</strong>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-2 text-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-emerald-300 font-medium">Aktif:</span>
                <strong class="text-emerald-300 font-mono font-bold">{{ number_format($bwCounts['total_aktif']) }}</strong>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center gap-2 text-xs">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                <span class="text-rose-300 font-medium">Terminasi:</span>
                <strong class="text-rose-300 font-mono font-bold">{{ number_format($bwCounts['total_terminasi']) }}</strong>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center gap-2 text-xs">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="text-amber-300 font-medium">Suspend:</span>
                <strong class="text-amber-300 font-mono font-bold">{{ number_format($bwCounts['total_suspend']) }}</strong>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB CONTROLLER: BERSIH & RAPI                                       -->
    <!-- =================================================================== -->
    <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800/90 rounded-2xl p-5 shadow-xl shadow-black/20 space-y-5">
        
        <!-- Segmented Tab Navigation -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800/90 pb-4">
            <div class="flex items-center gap-1.5 p-1 bg-slate-950/80 border border-slate-800/80 rounded-xl">
                <!-- Tab Aktif -->
                <button type="button" 
                        @click="activeTab = 'aktif'"
                        :class="activeTab === 'aktif' ? 'bg-cyan-500 text-slate-950 shadow-md shadow-cyan-500/25 font-bold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer">
                    <span class="w-2 h-2 rounded-full" :class="activeTab === 'aktif' ? 'bg-slate-950' : 'bg-cyan-400'"></span>
                    <span>Pelanggan Aktif</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="activeTab === 'aktif' ? 'bg-slate-950/20 text-slate-950 font-extrabold' : 'bg-slate-800 text-cyan-400'">
                        {{ number_format($bwCounts['total_aktif']) }}
                    </span>
                </button>

                <!-- Tab Terminasi -->
                <button type="button" 
                        @click="activeTab = 'terminasi'"
                        :class="activeTab === 'terminasi' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/25 font-bold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer">
                    <span class="w-2 h-2 rounded-full" :class="activeTab === 'terminasi' ? 'bg-white' : 'bg-rose-400'"></span>
                    <span>Terminasi</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="activeTab === 'terminasi' ? 'bg-white/20 text-white font-extrabold' : 'bg-slate-800 text-rose-400'">
                        {{ number_format($bwCounts['total_terminasi']) }}
                    </span>
                </button>

                <!-- Tab Suspend -->
                <button type="button" 
                        @click="activeTab = 'suspend'"
                        :class="activeTab === 'suspend' ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/25 font-bold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer">
                    <span class="w-2 h-2 rounded-full" :class="activeTab === 'suspend' ? 'bg-slate-950' : 'bg-amber-400'"></span>
                    <span>Suspend</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="activeTab === 'suspend' ? 'bg-slate-950/20 text-slate-950 font-extrabold' : 'bg-slate-800 text-amber-400'">
                        {{ number_format($bwCounts['total_suspend']) }}
                    </span>
                </button>

                <!-- Tab Semua Overview -->
                <button type="button" 
                        @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25 font-bold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    <span>Semua Status</span>
                </button>
            </div>

            <!-- Hint text -->
            <span class="text-[11px] text-slate-400 hidden lg:inline-block">
                💡 Klik kartu bandwidth di bawah untuk filter cepat ke tabel.
            </span>
        </div>

        <!-- ==================== 1. PANEL AKTIF ==================== -->
        <div x-show="activeTab === 'aktif' || activeTab === 'all'" x-cloak class="space-y-3">
            <div class="flex items-center justify-between" x-show="activeTab === 'all'">
                <h4 class="text-xs font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <span>Pelanggan Aktif (Total: {{ number_format($bwCounts['total_aktif']) }} User)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($layananList as $layanan)
                    @php
                        $count = $bwCounts['aktif'][$layanan->kode_kategori_bandwith] ?? 0;
                        $isActiveFilter = ($filters['status'] ?? '') == '20' && ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith;
                    @endphp
                    <a href="{{ route('teknik.pelanggan', ['status' => '20', 'layanan' => $layanan->kode_kategori_bandwith]) }}" 
                       class="group p-3.5 rounded-xl border transition-all duration-150 shadow-sm {{ $isActiveFilter ? 'bg-cyan-950/50 border-cyan-500 ring-1 ring-cyan-500/50 shadow-cyan-500/20' : 'bg-slate-950/60 hover:bg-slate-800/80 border-slate-800/80 hover:border-slate-700' }} flex flex-col justify-between min-h-[82px]">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 group-hover:border-cyan-400 transition truncate max-w-[120px]">
                                {{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full {{ $count > 0 ? 'bg-cyan-400' : 'bg-slate-600' }}"></span>
                        </div>
                        <div class="mt-2.5 flex items-baseline justify-between">
                            <span class="text-base font-bold {{ $count > 0 ? 'text-white group-hover:text-cyan-300' : 'text-slate-400' }}">
                                {{ number_format($count) }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">User</span>
                        </div>
                    </a>
                @endforeach

                <!-- Card Total Aktif -->
                <a href="{{ route('teknik.pelanggan', ['status' => '20']) }}" 
                   class="group p-3.5 rounded-xl border transition-all duration-150 shadow-sm {{ (($filters['status'] ?? '') == '20' && empty($filters['layanan'])) ? 'bg-cyan-950/50 border-cyan-500 ring-1 ring-cyan-500/50' : 'bg-slate-950/60 hover:bg-slate-800/80 border-slate-800/80 hover:border-slate-700' }} flex flex-col justify-between min-h-[82px]">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-cyan-500 text-slate-950 shadow-sm">
                            Total Aktif
                        </span>
                        <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <div class="mt-2.5 flex items-baseline justify-between">
                        <span class="text-base font-extrabold text-cyan-400 group-hover:text-cyan-300">
                            {{ number_format($bwCounts['total_aktif']) }}
                        </span>
                        <span class="text-[11px] text-cyan-300/70 font-semibold">User</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- ==================== 2. PANEL TERMINASI ==================== -->
        <div x-show="activeTab === 'terminasi' || activeTab === 'all'" x-cloak class="space-y-3" :class="activeTab === 'all' ? 'pt-4 border-t border-slate-800/70' : ''">
            <div class="flex items-center justify-between" x-show="activeTab === 'all'">
                <h4 class="text-xs font-bold text-rose-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    <span>Pelanggan Terminasi (Total: {{ number_format($bwCounts['total_terminasi']) }} User)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($layananList as $layanan)
                    @php
                        $count = $bwCounts['terminasi'][$layanan->kode_kategori_bandwith] ?? 0;
                        $isActiveFilter = ($filters['status'] ?? '') == '23' && ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith;
                    @endphp
                    <a href="{{ route('teknik.pelanggan', ['status' => '23', 'layanan' => $layanan->kode_kategori_bandwith]) }}" 
                       class="group p-3.5 rounded-xl border transition-all duration-150 shadow-sm {{ $isActiveFilter ? 'bg-rose-950/50 border-rose-500 ring-1 ring-rose-500/50 shadow-rose-500/20' : 'bg-slate-950/60 hover:bg-slate-800/80 border-slate-800/80 hover:border-slate-700' }} flex flex-col justify-between min-h-[82px]">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/15 text-rose-300 border border-rose-500/30 group-hover:border-rose-400 transition truncate max-w-[120px]">
                                {{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full {{ $count > 0 ? 'bg-rose-400' : 'bg-slate-600' }}"></span>
                        </div>
                        <div class="mt-2.5 flex items-baseline justify-between">
                            <span class="text-base font-bold {{ $count > 0 ? 'text-white group-hover:text-rose-300' : 'text-slate-400' }}">
                                {{ number_format($count) }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">User</span>
                        </div>
                    </a>
                @endforeach

                <!-- Card Total Terminasi -->
                <a href="{{ route('teknik.pelanggan', ['status' => '23']) }}" 
                   class="group p-3.5 rounded-xl border transition-all duration-150 shadow-sm {{ (($filters['status'] ?? '') == '23' && empty($filters['layanan'])) ? 'bg-rose-950/50 border-rose-500 ring-1 ring-rose-500/50' : 'bg-slate-950/60 hover:bg-slate-800/80 border-slate-800/80 hover:border-slate-700' }} flex flex-col justify-between min-h-[82px]">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500 text-white shadow-sm">
                            Total Terminasi
                        </span>
                        <svg class="w-3.5 h-3.5 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <div class="mt-2.5 flex items-baseline justify-between">
                        <span class="text-base font-extrabold text-rose-400 group-hover:text-rose-300">
                            {{ number_format($bwCounts['total_terminasi']) }}
                        </span>
                        <span class="text-[11px] text-rose-300/70 font-semibold">User</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- ==================== 3. PANEL SUSPEND ==================== -->
        <div x-show="activeTab === 'suspend' || activeTab === 'all'" x-cloak class="space-y-3" :class="activeTab === 'all' ? 'pt-4 border-t border-slate-800/70' : ''">
            <div class="flex items-center justify-between" x-show="activeTab === 'all'">
                <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span>Pelanggan Suspend (Total: {{ number_format($bwCounts['total_suspend']) }} User)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($layananList as $layanan)
                    @php
                        $count = $bwCounts['suspend'][$layanan->kode_kategori_bandwith] ?? 0;
                        $isActiveFilter = ($filters['status'] ?? '') == '21' && ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith;
                    @endphp
                    <a href="{{ route('teknik.pelanggan', ['status' => '21', 'layanan' => $layanan->kode_kategori_bandwith]) }}" 
                       class="group p-3.5 rounded-xl border transition-all duration-150 shadow-sm {{ $isActiveFilter ? 'bg-amber-950/50 border-amber-500 ring-1 ring-amber-500/50 shadow-amber-500/20' : 'bg-slate-950/60 hover:bg-slate-800/80 border-slate-800/80 hover:border-slate-700' }} flex flex-col justify-between min-h-[82px]">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-300 border border-amber-500/30 group-hover:border-amber-400 transition truncate max-w-[120px]">
                                {{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full {{ $count > 0 ? 'bg-amber-400' : 'bg-slate-600' }}"></span>
                        </div>
                        <div class="mt-2.5 flex items-baseline justify-between">
                            <span class="text-base font-bold {{ $count > 0 ? 'text-white group-hover:text-amber-300' : 'text-slate-400' }}">
                                {{ number_format($count) }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">User</span>
                        </div>
                    </a>
                @endforeach

                <!-- Card Total Suspend -->
                <a href="{{ route('teknik.pelanggan', ['status' => '21']) }}" 
                   class="group p-3.5 rounded-xl border transition-all duration-150 shadow-sm {{ (($filters['status'] ?? '') == '21' && empty($filters['layanan'])) ? 'bg-amber-950/50 border-amber-500 ring-1 ring-amber-500/50' : 'bg-slate-950/60 hover:bg-slate-800/80 border-slate-800/80 hover:border-slate-700' }} flex flex-col justify-between min-h-[82px]">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500 text-slate-950 shadow-sm">
                            Total Suspend
                        </span>
                        <svg class="w-3.5 h-3.5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <div class="mt-2.5 flex items-baseline justify-between">
                        <span class="text-base font-extrabold text-amber-400 group-hover:text-amber-300">
                            {{ number_format($bwCounts['total_suspend']) }}
                        </span>
                        <span class="text-[11px] text-amber-300/70 font-semibold">User</span>
                    </div>
                </a>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- FILTER BAR CONTAINER                                                -->
    <!-- =================================================================== -->
    <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800/90 rounded-2xl p-5 shadow-xl shadow-black/20">
        <form method="GET" action="{{ route('teknik.pelanggan') }}" id="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5 items-end">
                
                <!-- 1. Dropdown Semua Layanan -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Layanan</label>
                    <select name="layanan" 
                            class="w-full bg-slate-950/80 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none transition">
                        <option value="">SEMUA LAYANAN</option>
                        @foreach($layananList as $layanan)
                            <option value="{{ $layanan->kode_kategori_bandwith }}" {{ ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith ? 'selected' : '' }}>
                                {{ $layanan->nama_kategori_bandwith }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Search No Internet / Nama / NIK / HP -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Cari Pelanggan</label>
                    <input type="text" 
                           name="search" 
                           value="{{ $filters['search'] ?? '' }}" 
                           placeholder="No Internet, Nama, NIK..." 
                           class="w-full bg-slate-950/80 border-b-2 border-slate-700 focus:border-teal-400 rounded-t-xl px-3 py-2 text-xs text-slate-200 outline-none transition focus:bg-slate-950">
                </div>

                <!-- 3. Input Alamat -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Alamat</label>
                    <input type="text" 
                           name="alamat" 
                           value="{{ $filters['alamat'] ?? '' }}" 
                           placeholder="Cari Alamat..." 
                           class="w-full bg-slate-950/80 border-b-2 border-slate-700 focus:border-teal-400 rounded-t-xl px-3 py-2 text-xs text-slate-200 outline-none transition focus:bg-slate-950">
                </div>

                <!-- 4. Dropdown Semua Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" 
                            class="w-full bg-slate-950/80 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none transition">
                        <option value="">SEMUA STATUS (EKSISTING)</option>
                        <option value="20" {{ ($filters['status'] ?? '') == '20' ? 'selected' : '' }}>Aktif</option>
                        <option value="21" {{ in_array(($filters['status'] ?? ''), ['21', '21.1']) ? 'selected' : '' }}>Suspend</option>
                        <option value="23" {{ in_array(($filters['status'] ?? ''), ['23', '23.1']) ? 'selected' : '' }}>Terminasi</option>
                    </select>
                </div>

                <!-- 5. Dropdown Semua Wilayah -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Wilayah</label>
                    <select name="wilayah" 
                            class="w-full bg-slate-950/80 border border-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none transition">
                        <option value="">SEMUA WILAYAH</option>
                        @foreach($wilayahList as $wilayah)
                            <option value="{{ $wilayah->name_w }}" {{ ($filters['wilayah'] ?? '') == $wilayah->name_w ? 'selected' : '' }}>
                                {{ $wilayah->name_w }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Action Buttons: Reset & Export Excel (Matching Reference Image) -->
                <div class="flex items-center gap-2">
                    <a href="{{ route('teknik.pelanggan') }}" 
                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-rose-500/90 hover:bg-rose-600 text-white text-xs font-semibold transition duration-150 shadow-md shadow-rose-500/20">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset</span>
                    </a>
                    
                    <!-- Tombol Export Excel Persis Sesuai Gambar Referensi -->
                    <a href="{{ route('teknik.pelanggan.export', request()->query()) }}" 
                       class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-xl bg-[#00a65a] hover:bg-[#008d4c] text-white transition duration-150 shadow-md shadow-emerald-700/30 cursor-pointer"
                       title="Download data pelanggan ke format Excel">
                        <svg class="w-4 h-4 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <div class="text-left leading-tight">
                            <span class="block text-[11px] font-bold">Export</span>
                            <span class="block text-[11px] font-bold">Excel</span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-slate-800/80 pt-3">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span>Show</span>
                    <select name="per_page" 
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-xs text-slate-200 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="10" {{ ($filters['per_page'] ?? '10') == '10' ? 'selected' : '' }}>10</option>
                        <option value="25" {{ ($filters['per_page'] ?? '') == '25' ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($filters['per_page'] ?? '') == '50' ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['per_page'] ?? '') == '100' ? 'selected' : '' }}>100</option>
                    </select>
                    <span>entries</span>
                </div>

                <div>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-blue-400 font-semibold border border-slate-700/80 transition">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- =================================================================== -->
    <!-- CUSTOMER DATA TABLE CONTAINER                                       -->
    <!-- =================================================================== -->
    <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800/90 rounded-2xl shadow-xl shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-[11px] font-bold text-slate-300 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Pelanggan</th>
                        <th class="py-3.5 px-4">Group Layanan</th>
                        <th class="py-3.5 px-4">Lokasi Pemasangan</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Status</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Tanggal SO / Registrasi</th>
                        <th class="py-3.5 px-4 text-center min-w-[100px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-xs">
                    @forelse($pelanggan as $item)
                        <tr class="hover:bg-slate-800/40 transition duration-150">
                            
                            <!-- 1. Pelanggan -->
                            <td class="py-4 px-4 align-top">
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-bold text-blue-400 hover:text-blue-300 underline tracking-wide text-sm"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>
                                <div class="mt-1 font-semibold text-white uppercase text-xs">
                                    <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                       class="hover:text-blue-300 transition"
                                       title="Buka Profile Pelanggan">
                                        {{ $item->nama_pelanggan }} 
                                    </a>
                                    <span class="text-slate-400 font-normal">
                                        ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?? ($item->alias_nama_kategori ?? 'LAYANAN') }} 
                                    @if($item->nominal_bandwith)
                                        <span class="text-slate-300 font-bold">{{ $item->nominal_bandwith }} Mbps</span>
                                    @endif
                                </div>
                                @if($item->nomor_hp)
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                        📱 {{ $item->nomor_hp }}
                                    </div>
                                @endif
                            </td>

                            <!-- 2. Group Layanan -->
                            <td class="py-4 px-4 align-top font-semibold text-slate-300 uppercase tracking-wider text-[11px]">
                                {{ $item->group_layanan ?: 'MEDIANET' }}
                            </td>

                            <!-- 3. Lokasi Pemasangan -->
                            <td class="py-4 px-4 align-top max-w-xs">
                                <span class="font-bold text-white uppercase text-[11px] tracking-wide block mb-1">
                                    {{ $item->jenis_bangunan ?: 'RUMAH-PRIBADI' }}
                                </span>
                                <p class="text-[11px] text-slate-300 leading-relaxed uppercase">
                                    {{ $item->alamat_p ?: ($item->alamat_pasang ?: '-') }}
                                </p>
                            </td>

                            <!-- 4. Status -->
                            <td class="py-4 px-4 align-top space-y-2">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold border
                                        @if($item->status_reg == '20')
                                            bg-emerald-500/15 text-emerald-300 border-emerald-500/30
                                        @elseif(in_array($item->status_reg, ['21', '21.1']))
                                            bg-amber-500/15 text-amber-300 border-amber-500/30
                                        @elseif(in_array($item->status_reg, ['23', '23.1']))
                                            bg-rose-500/15 text-rose-300 border-rose-500/30
                                        @else
                                            bg-slate-500/15 text-slate-300 border-slate-500/30
                                        @endif">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <span>{{ $item->desc_registrasi ?: 'Status #' . $item->status_reg }}</span>
                                    </span>
                                </div>

                                @if($item->date_update)
                                    <div class="text-[10px] text-slate-400">
                                        Updated: <span class="text-slate-300">{{ \Carbon\Carbon::parse($item->date_update)->translatedFormat('d M Y H:i') }} WIB</span>
                                    </div>
                                @endif
                            </td>

                            <!-- 5. Tanggal SO / Registrasi -->
                            <td class="py-4 px-4 align-top text-xs space-y-1">
                                <div class="text-slate-200 font-semibold text-[11px]">
                                    {{ \Carbon\Carbon::parse($item->date_create)->translatedFormat('d M Y H:i') }} WIB
                                </div>
                                <div class="text-[11px] text-slate-300 uppercase font-medium">
                                    {{ $item->user_create ?: 'SYSTEM' }}
                                </div>
                                <div class="text-[10px] text-blue-400 font-mono font-semibold">
                                    SALES: {{ $item->nama_sales ?: '-' }}
                                </div>
                            </td>

                            <!-- 6. Aksi -->
                            <td class="py-4 px-4 align-top text-center text-xs">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                       class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-blue-400 hover:text-blue-300 transition" 
                                       title="Lihat Profile Pelanggan">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-10 h-10 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                    <span>Tidak ada data pelanggan yang sesuai dengan filter pencarian.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="px-5 py-4 border-t border-slate-800 bg-slate-950/40 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-400">
                Showing <span class="text-slate-200 font-semibold">{{ $pelanggan->firstItem() ?? 0 }}</span> 
                to <span class="text-slate-200 font-semibold">{{ $pelanggan->lastItem() ?? 0 }}</span> 
                of <span class="text-slate-200 font-semibold">{{ $pelanggan->total() }}</span> entries
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ $pelanggan->url(1) }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->onFirstPage() ? 'border-slate-800 text-slate-600 pointer-events-none' : 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">First</a>
                <a href="{{ $pelanggan->previousPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->onFirstPage() ? 'border-slate-800 text-slate-600 pointer-events-none' : 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">Previous</a>
                <span class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold shadow-sm">{{ $pelanggan->currentPage() }}</span>
                <a href="{{ $pelanggan->nextPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->hasMorePages() ? 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' : 'border-slate-800 text-slate-600 pointer-events-none' }}">Next</a>
                <a href="{{ $pelanggan->url($pelanggan->lastPage()) }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->hasMorePages() ? 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' : 'border-slate-800 text-slate-600 pointer-events-none' }}">Last</a>
            </div>
        </div>
    </div>

</div>
@endsection
