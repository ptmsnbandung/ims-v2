@extends('layouts.app')

@section('title', 'Data Pelanggan IMS')
@section('page_title', 'Data Pelanggan')

@section('content')
@php
    $isFinance = request()->routeIs('finance.*') || auth()->user()?->hasRole(['finance', 'admin', 'direktur']);
    $pelangganRoute = request()->routeIs('finance.*') ? 'finance.pelanggan' : 'teknik.pelanggan';
    $profileRoute = request()->routeIs('finance.*') ? 'finance.pelanggan.profile' : 'teknik.pelanggan.profile';
    $exportRoute = request()->routeIs('finance.*') ? 'finance.pelanggan.export' : 'teknik.pelanggan.export';
@endphp

<div class="space-y-6"
     x-data="{
         // Tab Status Active: 'aktif', 'terminasi', 'suspend', 'all'
         activeTab: '{{ ($filters['status'] ?? '') == '23' ? 'terminasi' : (($filters['status'] ?? '') == '21' ? 'suspend' : 'aktif') }}',
         
         // Quick Search
         searchQuery: '{{ $filters['search'] ?? '' }}',

         // Modal states for Permintaan ke NOC (Finance)
         upDowngradeModalOpen: false,
         suspendModalOpen: false,
         terminasiModalOpen: false,
         activeCustomer: {
             nomor_internet: '',
             nama_pelanggan: '',
             nama_kategori_bandwith: '',
             nominal_bandwith: '',
             harga_bandwith: 0,
             alamat: '',
             kode_bandwith: ''
         },

         openUpDowngrade(cust) {
             this.activeCustomer = cust;
             this.upDowngradeModalOpen = true;
         },
         openSuspend(cust) {
             this.activeCustomer = cust;
             this.suspendModalOpen = true;
         },
         openTerminasi(cust) {
             this.activeCustomer = cust;
             this.terminasiModalOpen = true;
         }
     }">
    
    <!-- Flash Messages (Notifikasi Sukses / Gagal Permintaan) -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 text-lg font-bold cursor-pointer">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-400 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-200 text-lg font-bold cursor-pointer">&times;</button>
        </div>
    @endif

    <!-- Top Header: Breadcrumbs & Total KPI Badges (Deep Oceanic Teal & Cyan Gradient Matching Dashboard) -->
    <div class="ims-banner relative overflow-hidden rounded-2xl p-5 sm:p-6 shadow-md border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Breadcrumb & Title -->
            <div>
                <div class="flex items-center gap-2 text-xs text-[#c6edf3] mb-1.5 font-medium">
                    <a href="{{ route('dashboard') }}" class="hover:text-white transition">IMS</a>
                    <svg class="w-3.5 h-3.5 text-teal-300/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="text-[#c6edf3] font-semibold">{{ $isFinance ? 'Finance' : 'Teknik' }}</span>
                    <svg class="w-3.5 h-3.5 text-teal-300/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="text-white font-semibold">Pelanggan</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2" style="color: #FFFFFF !important;">
                    <span>Data Pelanggan Terdaftar</span>
                </h2>
                <p class="text-xs sm:text-sm text-[#c6edf3] mt-1 max-w-2xl leading-relaxed" style="color: #C6EDF3 !important;">
                    Database master pelanggan aktif, profil layanan bandwidth, status suspend, dan arsip terminasi.
                </p>
            </div>

            <!-- Overall KPI Metric Pills -->
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 sm:gap-2.5 w-full md:w-auto">
                <a href="{{ route($pelangganRoute, ['status' => 'all']) }}" 
                   class="justify-center px-3.5 py-2 rounded-xl sm:rounded-full shadow-sm flex items-center gap-2 text-xs font-bold hover:opacity-95 transition cursor-pointer"
                   style="background-color: #ffffff; color: #1e293b; border: 1px solid rgba(255,255,255,0.8);"
                   title="Lihat Semua Pelanggan">
                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: #0ea5e9;"></span>
                    <span style="color: #475569; font-weight: 600;">Total:</span>
                    <strong class="font-mono font-black text-sm" style="color: #0f172a;">{{ number_format($bwCounts['total_aktif'] + $bwCounts['total_terminasi'] + $bwCounts['total_suspend']) }}</strong>
                </a>
                <a href="{{ route($pelangganRoute, ['status' => '20']) }}" 
                   class="justify-center px-3.5 py-2 rounded-xl sm:rounded-full shadow-sm flex items-center gap-2 text-xs hover:opacity-95 transition cursor-pointer"
                   style="background-color: rgba(4, 51, 62, 0.9); color: #6ee7b7; border: 1px solid rgba(45, 212, 191, 0.5);"
                   title="Filter Pelanggan Aktif">
                    <span class="w-2.5 h-2.5 rounded-full animate-pulse" style="background-color: #34d399;"></span>
                    <span style="color: #6ee7b7; font-weight: 600;">Aktif:</span>
                    <strong class="font-mono font-bold text-sm" style="color: #d1fae5;">{{ number_format($bwCounts['total_aktif']) }}</strong>
                </a>
                <a href="{{ route($pelangganRoute, ['status' => '23']) }}" 
                   class="justify-center px-3.5 py-2 rounded-xl sm:rounded-full shadow-sm flex items-center gap-2 text-xs hover:opacity-95 transition cursor-pointer"
                   style="background-color: rgba(62, 4, 19, 0.9); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.7);"
                   title="Filter Pelanggan Terminasi">
                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: #fb7185;"></span>
                    <span style="color: #fda4af; font-weight: 600;">Terminasi:</span>
                    <strong class="font-mono font-bold text-sm" style="color: #ffe4e6;">{{ number_format($bwCounts['total_terminasi']) }}</strong>
                </a>
                <a href="{{ route($pelangganRoute, ['status' => '21']) }}" 
                   class="justify-center px-3.5 py-2 rounded-xl sm:rounded-full shadow-sm flex items-center gap-2 text-xs hover:opacity-95 transition cursor-pointer"
                   style="background-color: rgba(62, 46, 4, 0.9); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.6);"
                   title="Filter Pelanggan Suspend">
                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: #fbbf24;"></span>
                    <span style="color: #fcd34d; font-weight: 600;">Suspend:</span>
                    <strong class="font-mono font-bold text-sm" style="color: #fef3c7;">{{ number_format($bwCounts['total_suspend']) }}</strong>
                </a>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB CONTROLLER: BERSIH & RAPI                                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4 sm:space-y-5">
        
        <!-- Segmented Tab Navigation -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl overflow-x-auto max-w-full scrollbar-none">
                <!-- Tab Aktif -->
                <button type="button" 
                        @click="activeTab = 'aktif'"
                        :class="activeTab === 'aktif' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer flex-shrink-0">
                    <span class="w-2 h-2 rounded-full" :class="activeTab === 'aktif' ? 'bg-white' : 'bg-blue-500'"></span>
                    <span>Pelanggan Aktif</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="activeTab === 'aktif' ? 'bg-white/20 text-white font-extrabold' : 'bg-white dark:bg-slate-800 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'">
                        {{ number_format($bwCounts['total_aktif']) }}
                    </span>
                </button>

                <!-- Tab Terminasi -->
                <button type="button" 
                        @click="activeTab = 'terminasi'"
                        :class="activeTab === 'terminasi' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer flex-shrink-0">
                    <span class="w-2 h-2 rounded-full" :class="activeTab === 'terminasi' ? 'bg-white' : 'bg-rose-500'"></span>
                    <span>Terminasi</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="activeTab === 'terminasi' ? 'bg-white/20 text-white font-extrabold' : 'bg-white dark:bg-slate-800 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'">
                        {{ number_format($bwCounts['total_terminasi']) }}
                    </span>
                </button>

                <!-- Tab Suspend -->
                <button type="button" 
                        @click="activeTab = 'suspend'"
                        :class="activeTab === 'suspend' ? 'bg-amber-500 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer flex-shrink-0">
                    <span class="w-2 h-2 rounded-full" :class="activeTab === 'suspend' ? 'bg-white' : 'bg-amber-500'"></span>
                    <span>Suspend</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px]" :class="activeTab === 'suspend' ? 'bg-white/20 text-white font-extrabold' : 'bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30'">
                        {{ number_format($bwCounts['total_suspend']) }}
                    </span>
                </button>

                <!-- Tab Semua Overview -->
                <button type="button" 
                        @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'bg-slate-800 dark:bg-slate-700 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition duration-150 cursor-pointer flex-shrink-0">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    <span>Semua Status</span>
                </button>
            </div>

            <!-- Hint text -->
            <span class="text-[11px] text-slate-500 dark:text-slate-400 hidden lg:inline-block">
                💡 Klik kartu bandwidth di bawah untuk filter cepat ke tabel.
            </span>
        </div>

        <!-- ==================== 1. PANEL AKTIF ==================== -->
        <div x-show="activeTab === 'aktif' || activeTab === 'all'" x-cloak class="space-y-3">
            <div class="flex items-center justify-between" x-show="activeTab === 'all'">
                <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                    <span>Pelanggan Aktif (Total: {{ number_format($bwCounts['total_aktif']) }} User)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-2.5">
                @foreach($layananList as $layanan)
                    @php
                        $count = $bwCounts['aktif'][$layanan->kode_kategori_bandwith] ?? 0;
                        $isActiveFilter = ($filters['status'] ?? '') == '20' && ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith;
                    @endphp
                    <a href="{{ route($pelangganRoute, ['status' => '20', 'layanan' => $layanan->kode_kategori_bandwith]) }}" 
                       class="group p-2.5 rounded-xl border transition-all duration-150 shadow-xs hover:shadow-sm {{ $isActiveFilter ? 'bg-blue-50/90 dark:bg-blue-500/15 border-blue-500 ring-2 ring-blue-500/30' : 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/70 border-slate-200 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700' }} flex flex-col justify-between min-h-[74px]">
                        <div class="flex items-center justify-between gap-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 group-hover:border-blue-300 dark:group-hover:border-blue-600 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition truncate" title="{{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}">
                                {{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $count > 0 ? 'bg-blue-600 dark:bg-blue-400' : 'bg-slate-300 dark:bg-slate-700' }}"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-base font-extrabold tracking-tight {{ $count > 0 ? 'text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400' : 'text-slate-400 dark:text-slate-600' }}">
                                {{ number_format($count) }}
                            </span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">User</span>
                        </div>
                    </a>
                @endforeach

                <!-- Card Total Aktif (Solid Clean Color, No Gradient/Fog) -->
                <a href="{{ route($pelangganRoute, ['status' => '20']) }}" 
                   class="col-span-2 sm:col-span-2 lg:col-span-1 group p-2.5 rounded-xl border transition-all duration-150 shadow-xs hover:shadow-sm bg-blue-600 hover:bg-blue-700 text-white border-blue-600 flex flex-col justify-between min-h-[74px]">
                    <div class="flex items-center justify-between gap-1">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-blue-800/70 text-white border border-blue-400/30 truncate">
                            TOTAL AKTIF
                        </span>
                        <svg class="w-3.5 h-3.5 text-blue-200 group-hover:text-white group-hover:translate-x-0.5 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-base font-black tracking-tight text-white">
                            {{ number_format($bwCounts['total_aktif']) }}
                        </span>
                        <span class="text-[10px] text-blue-100 font-semibold">User</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- ==================== 2. PANEL TERMINASI ==================== -->
        <div x-show="activeTab === 'terminasi' || activeTab === 'all'" x-cloak class="space-y-3" :class="activeTab === 'all' ? 'pt-4 border-t border-slate-200 dark:border-slate-800' : ''">
            <div class="flex items-center justify-between" x-show="activeTab === 'all'">
                <h4 class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                    <span>Pelanggan Terminasi (Total: {{ number_format($bwCounts['total_terminasi']) }} User)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-2.5">
                @foreach($layananList as $layanan)
                    @php
                        $count = $bwCounts['terminasi'][$layanan->kode_kategori_bandwith] ?? 0;
                        $isActiveFilter = ($filters['status'] ?? '') == '23' && ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith;
                    @endphp
                    <a href="{{ route($pelangganRoute, ['status' => '23', 'layanan' => $layanan->kode_kategori_bandwith]) }}" 
                       class="group p-2.5 rounded-xl border transition-all duration-150 shadow-xs hover:shadow-sm {{ $isActiveFilter ? 'bg-rose-50/90 dark:bg-rose-500/15 border-rose-500 ring-2 ring-rose-500/30' : 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/70 border-slate-200 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700' }} flex flex-col justify-between min-h-[74px]">
                        <div class="flex items-center justify-between gap-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 group-hover:border-rose-300 dark:group-hover:border-rose-600 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition truncate" title="{{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}">
                                {{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $count > 0 ? 'bg-rose-600 dark:bg-rose-400' : 'bg-slate-300 dark:bg-slate-700' }}"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-base font-extrabold tracking-tight {{ $count > 0 ? 'text-slate-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400' : 'text-slate-400 dark:text-slate-600' }}">
                                {{ number_format($count) }}
                            </span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">User</span>
                        </div>
                    </a>
                @endforeach

                <!-- Card Total Terminasi (Solid Clean Color, No Gradient/Fog) -->
                <a href="{{ route($pelangganRoute, ['status' => '23']) }}" 
                   class="col-span-2 sm:col-span-2 lg:col-span-1 group p-2.5 rounded-xl border transition-all duration-150 shadow-xs hover:shadow-sm bg-rose-600 hover:bg-rose-700 text-white border-rose-600 flex flex-col justify-between min-h-[74px]">
                    <div class="flex items-center justify-between gap-1">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-800/70 text-white border border-rose-400/30 truncate">
                            TOTAL TERMINASI
                        </span>
                        <svg class="w-3.5 h-3.5 text-rose-200 group-hover:text-white group-hover:translate-x-0.5 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-base font-black tracking-tight text-white">
                            {{ number_format($bwCounts['total_terminasi']) }}
                        </span>
                        <span class="text-[10px] text-rose-100 font-semibold">User</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- ==================== 3. PANEL SUSPEND ==================== -->
        <div x-show="activeTab === 'suspend' || activeTab === 'all'" x-cloak class="space-y-3" :class="activeTab === 'all' ? 'pt-4 border-t border-slate-200 dark:border-slate-800' : ''">
            <div class="flex items-center justify-between" x-show="activeTab === 'all'">
                <h4 class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Pelanggan Suspend (Total: {{ number_format($bwCounts['total_suspend']) }} User)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-2.5">
                @foreach($layananList as $layanan)
                    @php
                        $count = $bwCounts['suspend'][$layanan->kode_kategori_bandwith] ?? 0;
                        $isActiveFilter = ($filters['status'] ?? '') == '21' && ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith;
                    @endphp
                    <a href="{{ route($pelangganRoute, ['status' => '21', 'layanan' => $layanan->kode_kategori_bandwith]) }}" 
                       class="group p-2.5 rounded-xl border transition-all duration-150 shadow-xs hover:shadow-sm {{ $isActiveFilter ? 'bg-amber-50/90 dark:bg-amber-500/15 border-amber-500 ring-2 ring-amber-500/30' : 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/70 border-slate-200 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-700' }} flex flex-col justify-between min-h-[74px]">
                        <div class="flex items-center justify-between gap-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 group-hover:border-amber-300 dark:group-hover:border-amber-600 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition truncate" title="{{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}">
                                {{ $layanan->alias_nama_kategori ?: $layanan->nama_kategori_bandwith }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $count > 0 ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700' }}"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-base font-extrabold tracking-tight {{ $count > 0 ? 'text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400' : 'text-slate-400 dark:text-slate-600' }}">
                                {{ number_format($count) }}
                            </span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">User</span>
                        </div>
                    </a>
                @endforeach

                <!-- Card Total Suspend (Solid Clean Color, No Gradient/Fog) -->
                <a href="{{ route($pelangganRoute, ['status' => '21']) }}" 
                   class="col-span-2 sm:col-span-2 lg:col-span-1 group p-2.5 rounded-xl border transition-all duration-150 shadow-xs hover:shadow-sm bg-amber-500 hover:bg-amber-600 text-white border-amber-500 flex flex-col justify-between min-h-[74px]">
                    <div class="flex items-center justify-between gap-1">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-700/70 text-white border border-amber-300/30 truncate">
                            TOTAL SUSPEND
                        </span>
                        <svg class="w-3.5 h-3.5 text-amber-100 group-hover:text-white group-hover:translate-x-0.5 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-base font-black tracking-tight text-white">
                            {{ number_format($bwCounts['total_suspend']) }}
                        </span>
                        <span class="text-[10px] text-amber-100 font-semibold">User</span>
                    </div>
                </a>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- FILTER BAR CONTAINER                                                -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
        <form method="GET" action="{{ route($pelangganRoute) }}" id="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5 items-end">
                
                <!-- 1. Dropdown Semua Layanan -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Layanan</label>
                    <select name="layanan" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-600 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 outline-none transition">
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
                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Cari Pelanggan</label>
                    <input type="text" 
                           name="search" 
                           value="{{ $filters['search'] ?? '' }}" 
                           placeholder="No Internet, Nama, NIK..." 
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-600 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 outline-none transition">
                </div>

                <!-- 3. Input Alamat -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Alamat</label>
                    <input type="text" 
                           name="alamat" 
                           value="{{ $filters['alamat'] ?? '' }}" 
                           placeholder="Cari Alamat..." 
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-600 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 outline-none transition">
                </div>

                <!-- 4. Dropdown Semua Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-600 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 outline-none transition">
                        <option value="20" {{ ($filters['status'] ?? '20') == '20' ? 'selected' : '' }}>Aktif (Default)</option>
                        <option value="21" {{ in_array(($filters['status'] ?? ''), ['21', '21.1']) ? 'selected' : '' }}>Suspend</option>
                        <option value="23" {{ in_array(($filters['status'] ?? ''), ['23', '23.1']) ? 'selected' : '' }}>Terminasi</option>
                        <option value="all" {{ ($filters['status'] ?? '') == 'all' ? 'selected' : '' }}>SEMUA STATUS (EKSISTING)</option>
                    </select>
                </div>

                <!-- 5. Dropdown Semua Wilayah -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Wilayah</label>
                    <select name="wilayah" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-600 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 outline-none transition">
                        <option value="">SEMUA WILAYAH</option>
                        @foreach($wilayahList as $wilayah)
                            <option value="{{ $wilayah->name_w }}" {{ ($filters['wilayah'] ?? '') == $wilayah->name_w ? 'selected' : '' }}>
                                {{ $wilayah->name_w }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Action Buttons: Reset & Export Excel -->
                <div class="flex items-center gap-2">
                    <a href="{{ route($pelangganRoute) }}" 
                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-xs font-semibold transition duration-150 shadow-sm">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset</span>
                    </a>
                    
                    <a href="{{ route($exportRoute, request()->query()) }}" 
                       class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-xl bg-[#00a65a] hover:bg-[#008d4c] text-white transition duration-150 shadow-sm cursor-pointer"
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

            <div class="mt-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-3">
                <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                    <span>Show</span>
                    <select name="per_page" 
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1 text-xs text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-blue-600 outline-none">
                        <option value="10" {{ ($filters['per_page'] ?? '10') == '10' ? 'selected' : '' }}>10</option>
                        <option value="25" {{ ($filters['per_page'] ?? '') == '25' ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($filters['per_page'] ?? '') == '50' ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['per_page'] ?? '') == '100' ? 'selected' : '' }}>100</option>
                    </select>
                    <span>entries</span>
                </div>

                <div>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs text-white font-semibold shadow-sm transition">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- =================================================================== -->
    <!-- CUSTOMER DATA TABLE CONTAINER                                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Pelanggan</th>
                        <th class="py-3.5 px-4">Group Layanan</th>
                        <th class="py-3.5 px-4">Lokasi Pemasangan</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Status</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Tanggal SO / Registrasi</th>
                        <th class="py-3.5 px-4 text-center min-w-[240px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($pelanggan as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition duration-150">
                            
                            <!-- 1. Pelanggan -->
                            <td class="py-4 px-4 align-top">
                                <a href="{{ route($profileRoute, $item->nomor_internet) }}" 
                                   class="font-bold font-mono text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline tracking-wide text-xs inline-block"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>
                                <div class="mt-1 font-bold text-slate-900 dark:text-white uppercase text-xs">
                                    <span>{{ $item->nama_pelanggan }}</span>
                                    <span class="text-slate-500 dark:text-slate-400 font-normal">
                                        ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 dark:text-slate-400 font-medium mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?? ($item->alias_nama_kategori ?? 'LAYANAN') }} 
                                    @if($item->nominal_bandwith)
                                        <span class="text-slate-800 dark:text-slate-200 font-bold">{{ $item->nominal_bandwith }} Mbps</span>
                                    @endif
                                </div>
                                @if($item->nomor_hp)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                        📱 {{ $item->nomor_hp }}
                                    </div>
                                @endif
                            </td>

                            <!-- 2. Group Layanan -->
                            <td class="py-4 px-4 align-top font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px]">
                                {{ $item->group_layanan ?: 'MEDIANET' }}
                            </td>

                            <!-- 3. Lokasi Pemasangan -->
                            <td class="py-4 px-4 align-top max-w-xs">
                                <span class="font-bold text-slate-900 dark:text-white uppercase text-[11px] tracking-wide block mb-1">
                                    {{ $item->jenis_bangunan ?: 'RUMAH-PRIBADI' }}
                                </span>
                                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed uppercase">
                                    {{ $item->alamat_p ?: ($item->alamat_pasang ?: '-') }}
                                </p>
                            </td>

                            <!-- 4. Status -->
                            <td class="py-4 px-4 align-top space-y-2">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold border
                                        @if($item->status_reg == '20')
                                             bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20
                                        @elseif(in_array($item->status_reg, ['21', '21.1']))
                                             bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20
                                        @elseif(in_array($item->status_reg, ['23', '23.1']))
                                             bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20
                                        @else
                                             bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700
                                        @endif">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <span>{{ $item->desc_registrasi ?: 'Status #' . $item->status_reg }}</span>
                                    </span>
                                </div>

                                @if($item->date_update)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                        Updated: <span class="text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($item->date_update)->translatedFormat('d M Y H:i') }} WIB</span>
                                    </div>
                                @endif
                            </td>

                            <!-- 5. Tanggal SO / Registrasi -->
                            <td class="py-4 px-4 align-top text-xs space-y-1">
                                <div class="text-slate-900 dark:text-white font-semibold text-[11px]">
                                    {{ \Carbon\Carbon::parse($item->date_create)->translatedFormat('d M Y H:i') }} WIB
                                </div>
                                <div class="text-[11px] text-slate-600 dark:text-slate-400 uppercase font-medium">
                                    {{ $item->user_create ?: 'SYSTEM' }}
                                </div>
                                <div class="text-[10px] text-blue-600 dark:text-blue-400 font-mono font-semibold">
                                    SALES: {{ $item->nama_sales ?: '-' }}
                                </div>
                            </td>

                            <!-- 6. Aksi -->
                            <td class="py-4 px-4 align-top text-center text-xs">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    @if(in_array($item->status_reg, ['21', '21.1']) && !$isFinance)
                                        <!-- 1-Click UNIFIED AKTIFKAN (Khusus Non-Finance) -->
                                        <button type="button" 
                                                onclick="triggerUnifiedAction('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}', 'activate')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs transition cursor-pointer whitespace-nowrap"
                                                title="Aktifkan PPPoE MikroTik, Kick Koneksi, dan Reboot ONT OLT">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                            </svg>
                                            <span>Aktifkan</span>
                                        </button>
                                    @elseif($item->status_reg == '20' && !$isFinance)
                                        <!-- 1-Click UNIFIED SUSPEND (Khusus Non-Finance) -->
                                        <button type="button" 
                                                onclick="triggerUnifiedAction('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}', 'suspend')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-xs transition cursor-pointer whitespace-nowrap"
                                                title="Suspend PPPoE MikroTik, Kick Koneksi, dan Reboot ONT OLT">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            <span>Suspend</span>
                                        </button>
                                    @endif

                                    @if($isFinance)
                                        @php
                                            $custJson = json_encode([
                                                'nomor_internet' => $item->nomor_internet,
                                                'nama_pelanggan' => $item->nama_pelanggan,
                                                'nama_kategori_bandwith' => $item->nama_kategori_bandwith ?? ($item->alias_nama_kategori ?? 'LAYANAN'),
                                                'nominal_bandwith' => $item->nominal_bandwith ?? '',
                                                'harga_bandwith' => (float)($item->harga_bandwith ?? 0),
                                                'alamat' => $item->alamat_p ?: ($item->alamat_pasang ?: '-'),
                                                'kode_bandwith' => $item->kode_bandwith ?? '',
                                                'status_reg' => $item->status_reg,
                                            ]);
                                        @endphp
                                        <!-- 1. Req UP / Downgrade Bandwidth -->
                                        <button type="button" 
                                                @click="openUpDowngrade({{ $custJson }})" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 text-[11px] font-semibold transition cursor-pointer shadow-2xs whitespace-nowrap"
                                                title="Ajukan Req UP / Downgrade Bandwidth ke NOC">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                                            </svg>
                                            <span>Up/Down</span>
                                        </button>

                                        <!-- 2. Req Suspend -->
                                        <button type="button" 
                                                @click="openSuspend({{ $custJson }})" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/10 dark:hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20 text-[11px] font-semibold transition cursor-pointer shadow-2xs whitespace-nowrap"
                                                title="Ajukan Req Suspend ke NOC">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            <span>Suspend</span>
                                        </button>

                                        <!-- 3. Req Terminasi -->
                                        <button type="button" 
                                                @click="openTerminasi({{ $custJson }})" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 text-[11px] font-semibold transition cursor-pointer shadow-2xs whitespace-nowrap"
                                                title="Ajukan Req Terminasi (Cabut) ke NOC">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                                            </svg>
                                            <span>Terminasi</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400 text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-10 h-10 text-slate-400 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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

        <!-- Mobile Cards View (Hidden on Desktop) -->
        <div class="block md:hidden p-3 sm:p-4 space-y-3.5">
            @forelse($pelanggan as $item)
                @php
                    $isStatusAktif = $item->status_reg == '20';
                    $isStatusSuspend = in_array($item->status_reg, ['21', '21.1']);
                    $isStatusTerminasi = in_array($item->status_reg, ['23', '23.1']);
                    $cleanHp = preg_replace('/[^0-9]/', '', $item->nomor_hp ?? '');
                    if (str_starts_with($cleanHp, '0')) {
                        $cleanHp = '62' . substr($cleanHp, 1);
                    }
                    $custInitial = mb_substr($item->nama_pelanggan ?? 'P', 0, 1);
                @endphp
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md transition duration-200 space-y-3.5">
                    
                    <!-- Card Top Header: Avatar + Customer Name + Status Badge -->
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/20 uppercase">
                                {{ $custInitial }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm tracking-tight truncate uppercase leading-tight">
                                        {{ $item->nama_pelanggan }}
                                    </h4>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $item->jenis_kelamin == 2 ? 'bg-pink-50 text-pink-700 border border-pink-200 dark:bg-pink-500/10 dark:text-pink-400 dark:border-pink-500/20' : 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20' }}">
                                        {{ $item->jenis_kelamin == 2 ? 'P' : ($item->jenis_kelamin == 1 ? 'L' : '-') }}
                                    </span>
                                </div>
                                <a href="{{ route($profileRoute, $item->nomor_internet) }}" 
                                   class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 mt-0.5"
                                   title="Buka Profile Pelanggan">
                                    <span>#{{ $item->nomor_internet }}</span>
                                    <svg class="w-3 h-3 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border tracking-wide uppercase shadow-2xs
                                @if($isStatusAktif)
                                     bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30
                                @elseif($isStatusSuspend)
                                     bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30
                                @elseif($isStatusTerminasi)
                                     bg-rose-50 dark:bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/30
                                @else
                                     bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700
                                @endif">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isStatusAktif ? 'bg-emerald-500' : ($isStatusSuspend ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                <span>{{ $item->desc_registrasi ?: 'Status #' . $item->status_reg }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Middle Info Cards (Tiles) -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <!-- Tile 1: Paket Layanan -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" /></svg>
                                <span>Paket Layanan</span>
                            </div>
                            <div class="font-bold text-slate-900 dark:text-white truncate">
                                {{ $item->nama_kategori_bandwith ?? ($item->alias_nama_kategori ?? 'BROADBAND') }}
                            </div>
                            @if($item->nominal_bandwith)
                                <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">
                                    {{ $item->nominal_bandwith }} Mbps
                                </span>
                            @endif
                        </div>

                        <!-- Tile 2: Group & Bangunan -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" /></svg>
                                <span>Group & Bangunan</span>
                            </div>
                            <div class="font-bold text-slate-900 dark:text-white truncate uppercase">
                                {{ $item->group_layanan ?: 'MEDIANET' }}
                            </div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase truncate">
                                {{ $item->jenis_bangunan ?: 'RUMAH-PRIBADI' }}
                            </div>
                        </div>

                        <!-- Tile 3: Alamat Pemasangan (Full Width) -->
                        <div class="col-span-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                <span>Alamat Pemasangan</span>
                            </div>
                            <p class="text-[11px] text-slate-700 dark:text-slate-300 leading-snug">
                                {{ $item->alamat_p ?: ($item->alamat_pasang ?: '-') }}
                            </p>
                        </div>

                        <!-- Tile 4: Tanggal Registrasi & User Create -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-0.5">
                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.253 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                                <span>Registrasi SO</span>
                            </div>
                            <div class="font-semibold text-slate-800 dark:text-slate-200 text-[11px]">
                                {{ \Carbon\Carbon::parse($item->date_create)->translatedFormat('d M Y H:i') }}
                            </div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase truncate">
                                By: {{ $item->user_create ?: 'SYSTEM' }}
                            </div>
                        </div>

                        <!-- Tile 5: Kontak & Sales -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                <span>Kontak / Sales</span>
                            </div>
                            @if(!empty($cleanHp))
                                <a href="https://wa.me/{{ $cleanHp }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-mono font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                    <span>📱 {{ $item->nomor_hp }}</span>
                                </a>
                            @else
                                <div class="text-[11px] text-slate-400">-</div>
                            @endif
                            <div class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase truncate">
                                Sales: {{ $item->nama_sales ?: '-' }}
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        @if($isStatusSuspend && !$isFinance)
                            <button type="button" 
                                    onclick="triggerUnifiedAction('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}', 'activate')"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition cursor-pointer">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                </svg>
                                <span>Aktifkan Pelanggan</span>
                            </button>
                        @elseif($isStatusAktif && !$isFinance)
                            <button type="button" 
                                    onclick="triggerUnifiedAction('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}', 'suspend')"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white font-bold text-xs shadow-md shadow-amber-500/20 transition cursor-pointer">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <span>Suspend Pelanggan</span>
                            </button>
                        @endif

                        @if($isFinance)
                            @php
                                $custJsonMobile = json_encode([
                                    'nomor_internet' => $item->nomor_internet,
                                    'nama_pelanggan' => $item->nama_pelanggan,
                                    'nama_kategori_bandwith' => $item->nama_kategori_bandwith ?? ($item->alias_nama_kategori ?? 'LAYANAN'),
                                    'nominal_bandwith' => $item->nominal_bandwith ?? '',
                                    'harga_bandwith' => (float)($item->harga_bandwith ?? 0),
                                    'alamat' => $item->alamat_p ?: ($item->alamat_pasang ?: '-'),
                                    'kode_bandwith' => $item->kode_bandwith ?? '',
                                    'status_reg' => $item->status_reg,
                                ]);
                            @endphp
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" 
                                        @click="openUpDowngrade({{ $custJsonMobile }})" 
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/15 dark:hover:bg-indigo-500/25 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 text-xs font-bold transition shadow-xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" /></svg>
                                    <span>Up/Down</span>
                                </button>
                                <button type="button" 
                                        @click="openSuspend({{ $custJsonMobile }})" 
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/15 dark:hover:bg-amber-500/25 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30 text-xs font-bold transition shadow-xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    <span>Suspend</span>
                                </button>
                                <button type="button" 
                                        @click="openTerminasi({{ $custJsonMobile }})" 
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/15 dark:hover:bg-rose-500/25 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30 text-xs font-bold transition shadow-xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>
                                    <span>Terminasi</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-500 dark:text-slate-400">
                    <p class="text-sm font-semibold">Tidak ada data pelanggan yang sesuai dengan filter pencarian.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination Controls -->
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-600 dark:text-slate-400">
                Showing <span class="text-slate-900 dark:text-white font-semibold">{{ $pelanggan->firstItem() ?? 0 }}</span> 
                to <span class="text-slate-900 dark:text-white font-semibold">{{ $pelanggan->lastItem() ?? 0 }}</span> 
                of <span class="text-slate-900 dark:text-white font-semibold">{{ $pelanggan->total() }}</span> entries
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ $pelanggan->url(1) }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->onFirstPage() ? 'border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 pointer-events-none' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">First</a>
                <a href="{{ $pelanggan->previousPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->onFirstPage() ? 'border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 pointer-events-none' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">Previous</a>
                <span class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold shadow-xs">{{ $pelanggan->currentPage() }}</span>
                <a href="{{ $pelanggan->nextPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->hasMorePages() ? 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' : 'border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 pointer-events-none' }}">Next</a>
                <a href="{{ $pelanggan->url($pelanggan->lastPage()) }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $pelanggan->hasMorePages() ? 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' : 'border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 pointer-events-none' }}">Last</a>
            </div>
        </div>
    <!-- ======================================================================= -->
    <!-- 1. MODAL: REQUEST UP / DOWNGRADE BANDWIDTH KE NOC                       -->
    <!-- ======================================================================= -->
    <div x-show="upDowngradeModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="upDowngradeModalOpen = false"
             class="w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-7 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>⚡ Ajukan UP / Downgrade Bandwidth ke NOC</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Permintaan akan otomatis diteruskan ke antrean kerja tim NOC.</p>
                </div>
                <button @click="upDowngradeModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('finance.permintaan.up-downgrade.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="nomor_internet" :value="activeCustomer.nomor_internet" required>

                <!-- Info Pelanggan Terpilih -->
                <div class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-xs space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Pelanggan:</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="activeCustomer.nomor_internet + ' - ' + activeCustomer.nama_pelanggan"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Paket Saat Ini:</span>
                        <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="activeCustomer.nama_kategori_bandwith + (activeCustomer.nominal_bandwith ? ' (' + activeCustomer.nominal_bandwith + ' Mbps)' : '') + (activeCustomer.harga_bandwith > 0 ? ' - Rp ' + Number(activeCustomer.harga_bandwith).toLocaleString('id-ID') : '')"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Alamat Pasang:</span>
                        <span class="text-slate-700 dark:text-slate-300 text-right truncate max-w-[280px]" x-text="activeCustomer.alamat"></span>
                    </div>
                </div>

                <!-- 2. Pilih Paket Baru -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Paket / Bandwidth Baru <span class="text-rose-500">*</span></label>
                    <select name="kode_bandwith_baru" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                        <option value="">-- Pilih Paket Baru --</option>
                        @if(isset($paketList))
                            @foreach($paketList as $p)
                                <option value="{{ $p->kode_bandwith }}">
                                    {{ $p->nama_kategori_bandwith }} - {{ $p->nominal_bandwith }} Mbps (Rp {{ number_format((float)($p->harga_bandwith ?? 0), 0, ',', '.') }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- 3. Tanggal Jadwal Eksekusi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tanggal Jadwal Eksekusi <span class="text-rose-500">*</span></label>
                    <input type="date"
                           name="date_schedule"
                           value="{{ date('Y-m-d') }}"
                           required
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                </div>

                <!-- 4. Catatan / Alasan Permintaan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Catatan Tambahan untuk Tim NOC</label>
                    <textarea name="note_request"
                              rows="2"
                              placeholder="Contoh: Pengajuan ubah paket oleh Finance..."
                              class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">Pengajuan ubah kecepatan/paket oleh Finance</textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="upDowngradeModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 text-xs font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/25 transition cursor-pointer">
                        Kirim Request UP/Downgrade ke NOC
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- 2. MODAL: REQUEST SUSPEND (ISOLIR) KE NOC                               -->
    <!-- ======================================================================= -->
    <div x-show="suspendModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="suspendModalOpen = false"
             class="w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-7 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🛑 Ajukan Permintaan Suspend ke NOC</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Digunakan untuk pelanggan yang menunggak atau belum membayar tagihan.</p>
                </div>
                <button @click="suspendModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('finance.permintaan.suspend.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="nomor_internet" :value="activeCustomer.nomor_internet" required>

                <!-- Info Pelanggan Terpilih -->
                <div class="p-3.5 rounded-xl bg-amber-50/70 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-xs space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Pelanggan:</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="activeCustomer.nomor_internet + ' - ' + activeCustomer.nama_pelanggan"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Paket:</span>
                        <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="activeCustomer.nama_kategori_bandwith + (activeCustomer.nominal_bandwith ? ' (' + activeCustomer.nominal_bandwith + ' Mbps)' : '')"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Alamat Pasang:</span>
                        <span class="text-slate-700 dark:text-slate-300 text-right truncate max-w-[280px]" x-text="activeCustomer.alamat"></span>
                    </div>
                </div>

                <!-- Tanggal Mulai Suspend -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tanggal Mulai Suspend <span class="text-rose-500">*</span></label>
                    <input type="date"
                           name="suspend_start"
                           value="{{ date('Y-m-d') }}"
                           required
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500 font-medium">
                </div>

                <!-- Alasan / Keterangan Suspend -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Keterangan / Alasan Suspend <span class="text-rose-500">*</span></label>
                    <textarea name="desc_suspend"
                              rows="3"
                              required
                              placeholder="Contoh: Melewati batas pembayaran tanggal jatuh tempo..."
                              class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500">Melewati batas pembayaran yang telah ditentukan</textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="suspendModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-500/25 transition cursor-pointer">
                        Kirim Request Suspend ke NOC
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- 3. MODAL: REQUEST TERMINASI KE NOC                                      -->
    <!-- ======================================================================= -->
    <div x-show="terminasiModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="terminasiModalOpen = false"
             class="w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-7 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🔌 Ajukan Permintaan Terminasi ke NOC / Lapangan</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Digunakan untuk pelanggan yang berhenti berlangganan (tutup akun & penarikan perangkat).</p>
                </div>
                <button @click="terminasiModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('finance.permintaan.terminasi.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="nomor_internet" :value="activeCustomer.nomor_internet" required>

                <!-- Info Pelanggan Terpilih -->
                <div class="p-3.5 rounded-xl bg-rose-50/70 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-xs space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Pelanggan:</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="activeCustomer.nomor_internet + ' - ' + activeCustomer.nama_pelanggan"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Paket:</span>
                        <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="activeCustomer.nama_kategori_bandwith + (activeCustomer.nominal_bandwith ? ' (' + activeCustomer.nominal_bandwith + ' Mbps)' : '')"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Alamat Pasang:</span>
                        <span class="text-slate-700 dark:text-slate-300 text-right truncate max-w-[280px]" x-text="activeCustomer.alamat"></span>
                    </div>
                </div>

                <!-- Alasan Berhenti Berlangganan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alasan Berhenti Berlangganan <span class="text-rose-500">*</span></label>
                    <select name="note_termin" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500 font-medium">
                        <option value="">-- Pilih Alasan Utama --</option>
                        <option value="Permintaan Pelanggan (Pindah Rumah / Alamat)">Permintaan Pelanggan (Pindah Rumah / Alamat)</option>
                        <option value="Permintaan Pelanggan (Keberatan Biaya Bulanan / Tarif)">Permintaan Pelanggan (Keberatan Biaya Bulanan / Tarif)</option>
                        <option value="Tunggakan Pembayaran Tidak Diselesaikan">Tunggakan Pembayaran Tidak Diselesaikan</option>
                        <option value="Beralih ke Provider Lain">Beralih ke Provider Lain</option>
                        <option value="Lainnya">Lainnya (Tutup Akun)</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="terminasiModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-lg shadow-rose-500/25 transition cursor-pointer">
                        Kirim Request Terminasi ke NOC
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- SweetAlert Script for 1-Click Unified Activate/Suspend -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function triggerUnifiedAction(nomorInternet, namaPelanggan, action) {
    const isActivate = (action === 'activate');
    const title = isActivate ? 'Aktifkan Layanan & Reboot ONT?' : 'Suspend / Isolir Layanan & Reboot ONT?';
    const text = isActivate 
        ? `Sistem akan otomatis: (1) Mengaktifkan PPPoE di MikroTik, (2) Kick koneksi agar re-auth, dan (3) Remote Reboot ONT OLT untuk ${namaPelanggan} (${nomorInternet}).`
        : `Sistem akan otomatis: (1) Mendisable PPPoE di MikroTik, (2) Kick sesi aktif seketika, dan (3) Remote Reboot ONT OLT untuk ${namaPelanggan} (${nomorInternet}).`;
    const confirmButtonColor = isActivate ? '#10b981' : '#f59e0b';
    const confirmButtonText = isActivate ? 'Ya, Aktifkan Sekarang' : 'Ya, Suspend Sekarang';

    Swal.fire({
        title: title,
        html: `<div class="text-left text-xs text-slate-600 dark:text-slate-300 space-y-2 mt-2">
            <p>${text}</p>
            <div class="p-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-[11px]">
                Target: <b>${nomorInternet}</b> - ${namaPelanggan}
            </div>
        </div>`,
        icon: isActivate ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const url = isActivate 
                ? `/teknik/pelanggan/${nomorInternet}/unified-activate` 
                : `/teknik/pelanggan/${nomorInternet}/unified-suspend`;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    note: isActivate ? 'Aktivasi 1-Click dari Data Pelanggan' : 'Suspend 1-Click dari Data Pelanggan'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request gagal: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const res = result.value;
            Swal.fire({
                icon: res.success ? 'success' : 'warning',
                title: res.title || (isActivate ? 'Berhasil Diaktifkan' : 'Berhasil Disuspend'),
                text: res.summary || 'Proses sinkronisasi MikroTik, Kick Session, dan Reboot ONT selesai.',
                confirmButtonColor: '#3b82f6'
            }).then(() => {
                window.location.reload();
            });
        }
    });
}
</script>
@endsection

