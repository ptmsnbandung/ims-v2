@extends('layouts.app')

@section('title', 'Master Data Optical Line Terminal (OLT) - NOC IMS')
@section('page_title', 'Master Data OLT')

@section('content')
<div class="space-y-4"
     x-data="{
        deleteModalOpen: false,
        oltToDelete: null,
        deleteActionUrl: '',
        viewMode: 'grid',
        telemetryMap: {{ Js::from($telemetryMap ?? []) }},
        telemetryLoading: {},
        copiedIp: null,
        dateFilters: {
            @foreach($olts as $o)
                '{{ $o->kode_olt }}': {
                    start_date: '{{ $telemetryMap[$o->kode_olt]['mrtg']['start_date_input'] ?? now()->subDays(7)->format('Y-m-d') }}',
                    end_date: '{{ $telemetryMap[$o->kode_olt]['mrtg']['end_date_input'] ?? now()->format('Y-m-d') }}',
                },
            @endforeach
        },
        oltStatusMap: {
            @foreach($olts as $o)
                '{{ $o->kode_olt }}': {
                    status: '{{ $telemetryMap[$o->kode_olt]['status'] ?? ($o->last_status ?? 'untested') }}',
                    latency: {{ $telemetryMap[$o->kode_olt]['latency'] ?? 'null' }},
                    loading: false,
                    message: '{{ $telemetryMap[$o->kode_olt]['message'] ?? ($o->last_sync_at ? 'Terakhir sync: ' . \Carbon\Carbon::parse($o->last_sync_at)->diffForHumans() : '') }}'
                },
            @endforeach
        },
        
        init() {
            // Latar belakang async sync untuk OLT yang belum memiliki cache telemetry live tanpa memblokir render halaman
            const olts = [
                @foreach($olts as $o)
                    '{{ $o->kode_olt }}',
                @endforeach
            ];
            olts.forEach((k, idx) => {
                if (this.telemetryMap[k]?.is_baseline) {
                    setTimeout(() => {
                        this.refreshTelemetry(k);
                    }, 400 + (idx * 800));
                }
            });
        },

        confirmDelete(kodeOlt, nameOlt) {
            this.oltToDelete = { kode: kodeOlt, name: nameOlt };
            this.deleteActionUrl = '{{ url('/noc/olt') }}/' + encodeURIComponent(kodeOlt) + '/delete';
            this.deleteModalOpen = true;
        },

        copyIp(ip) {
            if (!ip) return;
            navigator.clipboard.writeText(ip);
            this.copiedIp = ip;
            setTimeout(() => { this.copiedIp = null; }, 2000);
        },

        async filterTrafficByDate(kodeOlt) {
            this.telemetryLoading[kodeOlt] = true;
            try {
                const filter = this.dateFilters[kodeOlt] || {};
                const res = await fetch('{{ route('noc.olt.telemetry') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        kode_olt: kodeOlt,
                        start_date: filter.start_date,
                        end_date: filter.end_date
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.telemetryMap[kodeOlt] = data;
                    if (this.oltStatusMap[kodeOlt]) {
                        this.oltStatusMap[kodeOlt].status = data.status;
                        this.oltStatusMap[kodeOlt].latency = data.latency;
                        this.oltStatusMap[kodeOlt].message = data.message;
                    }
                }
            } catch(e) {
                console.error('Filter traffic error:', e);
            } finally {
                this.telemetryLoading[kodeOlt] = false;
            }
        },

        async refreshTelemetry(kodeOlt) {
            this.telemetryLoading[kodeOlt] = true;
            if (!this.oltStatusMap[kodeOlt]) {
                this.oltStatusMap[kodeOlt] = { status: 'untested', latency: null, loading: false, message: '' };
            }
            this.oltStatusMap[kodeOlt].loading = true;
            try {
                const filter = this.dateFilters[kodeOlt] || {};
                const res = await fetch('{{ route('noc.olt.telemetry') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        kode_olt: kodeOlt,
                        start_date: filter.start_date,
                        end_date: filter.end_date
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.telemetryMap[kodeOlt] = data;
                    this.oltStatusMap[kodeOlt].status = data.status;
                    this.oltStatusMap[kodeOlt].latency = data.latency;
                    this.oltStatusMap[kodeOlt].message = data.message;
                }
            } catch(e) {
                console.error('Telemetry refresh error:', e);
            } finally {
                this.telemetryLoading[kodeOlt] = false;
                this.oltStatusMap[kodeOlt].loading = false;
            }
        },

        async testPingOlt(kodeOlt, ip, port, protocol, user) {
            if (!this.oltStatusMap[kodeOlt]) {
                this.oltStatusMap[kodeOlt] = { status: 'untested', latency: null, loading: false, message: '' };
            }
            this.oltStatusMap[kodeOlt].loading = true;
            try {
                const res = await fetch('{{ route('noc.olt.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ip_address: ip,
                        port: port,
                        protocol: protocol,
                        username: user
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.oltStatusMap[kodeOlt].status = 'online';
                    this.oltStatusMap[kodeOlt].latency = data.latency;
                    this.oltStatusMap[kodeOlt].message = 'Online (' + data.latency + ' ms)';
                } else {
                    this.oltStatusMap[kodeOlt].status = 'offline';
                    this.oltStatusMap[kodeOlt].message = data.message || 'Offline / Host tidak merespon';
                }
            } catch(e) {
                this.oltStatusMap[kodeOlt].status = 'offline';
                this.oltStatusMap[kodeOlt].message = 'Koneksi error: ' + e.message;
            } finally {
                this.oltStatusMap[kodeOlt].loading = false;
            }
        }
     }">


    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER (COMMAND CENTER DARK BLUE THEME)          -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4 shadow-sm border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3.5 relative z-10">
            <div class="flex items-start sm:items-center gap-3">
                <!-- Icon OLT Server -->
                <div class="w-9 h-9 rounded-lg bg-blue-500/20 border border-blue-400/30 text-blue-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a3 3 0 0 1 2.4-1.35h7.7a3 3 0 0 1 2.4 1.35l2.1 3.15a4.5 4.5 0 0 1 .9 2.7m-13.5 0h13.5" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">
                        Master Data Optical Line Terminal (OLT)
                    </h2>
                    <p class="text-[11px] text-slate-300 mt-0.5">
                        Manajemen perangkat OLT pusat, IP Host, alokasi port PON, dan status distribusi jaringan FTTH.
                    </p>
                    
                    <!-- Stats Badges Bar -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-xs">
                            <svg class="w-3 h-3 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6" />
                            </svg>
                            <span>Total OLT: {{ $totalOlt }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-xs">
                            <svg class="w-3 h-3 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                            <span>Total PON Port: {{ $totalPonPort }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Status: Online</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Action Button -->
            @if(auth()->user()?->hasRole(['admin', 'direktur']))
            <div class="flex items-center gap-2">
                <a href="{{ route('noc.olt.create') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition transform hover:-translate-y-0.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah OLT Baru</span>
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. DATA OLT: CARD VIEW (3 KOLOM) & TABEL LIST                      -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        
        <!-- Top Toolbar: Search Bar & View Mode Switcher -->
        <div class="p-3 sm:p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60">
            <!-- Left: Info & Counter -->
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    <span>{{ $olts->total() }} Perangkat OLT</span>
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                    &middot; Tampilan 3 Baris Kartu Luas (Horizontal Row View)
                </span>
            </div>

            <!-- Right: Search Bar & Toggle View Switcher -->
            <div class="flex items-center gap-2.5 w-full sm:w-auto">
                <!-- Search Form -->
                <form method="GET" action="{{ route('noc.olt') }}" class="relative flex-1 sm:w-60">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}"
                           placeholder="Search OLT / IP..." 
                           class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </form>

                <!-- View Switcher (Card vs Table) -->
                <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0">
                    <button type="button" 
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-2xs font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                            class="px-2.5 py-1 text-xs rounded-md flex items-center gap-1.5 transition cursor-pointer"
                            title="Tampilan 3 Baris Kartu Luas">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <span class="hidden md:inline">Row Card View</span>
                    </button>
                    <button type="button" 
                            @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-2xs font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                            class="px-2.5 py-1 text-xs rounded-md flex items-center gap-1.5 transition cursor-pointer"
                            title="Tampilan Tabel Standar">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                        <span class="hidden md:inline">Tabel</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- A. ROW CARD VIEW (3 BARIS KARTU HORIZONTAL MEMBENTANG LUAS)     -->
        <!-- =============================================================== -->
        <div x-show="viewMode === 'grid'" class="p-3.5 sm:p-5">
            <div class="space-y-5">
                @forelse($olts as $olt)
                    @php
                        $popName = $olt->nama_pop ?? ($olt->kode_pop ?? 'POP Utama MSN');
                        $ipAddr = !empty($olt->ip_address) ? $olt->ip_address : '10.10.10.1';
                        $brandName = !empty($olt->brand) ? $olt->brand : 'ZTE C320';
                        $capacity = !empty($olt->capacity_olt) ? $olt->capacity_olt : 8;
                        $kodeDisplay = !empty($olt->kode_olt) ? $olt->kode_olt : ($olt->hostname ?? 'OLT-MSN');
                        $userCount = $registeredPonCounts[$olt->kode_olt] ?? 0;
                        $tData = $telemetryMap[$olt->kode_olt] ?? [];
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-blue-500/50 dark:hover:border-blue-500/50 rounded-2xl p-4 sm:p-5 shadow-xs hover:shadow-xl transition-all duration-300 relative group overflow-hidden">
                        
                        <!-- Top Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-blue-600 group-hover:h-2 transition-all"></div>

                        <!-- 2-Column Split: Kiri (Data OLT, Status, Aksi) & Kanan (MRTG Traffic Graph Luas) -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 items-stretch">
                            
                            <!-- KOLOM KIRI (lg:col-span-5): Profil OLT, Metrik Sinyal, Throughput, Aksi -->
                            <div class="lg:col-span-5 flex flex-col justify-between space-y-3.5">
                                <div class="space-y-3">
                                    <!-- 1. Header OLT: Kode, Nama, Brand, POP, Status Online & Ping -->
                                    <div class="flex items-start justify-between gap-3 pt-1">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center font-mono font-extrabold text-base shadow-sm shrink-0 border border-blue-500">
                                                {{ $kodeDisplay }}
                                            </div>
                                            <div class="min-w-0">
                                                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white leading-tight truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" title="{{ $olt->name_olt ?? $olt->nama_olt ?? 'OLT Gateway' }}">
                                                    {{ $olt->name_olt ?? $olt->nama_olt ?? 'OLT Gateway' }}
                                                </h3>
                                                <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                                    <span class="font-medium">Merk: {{ $brandName }}</span>
                                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                                        <svg class="w-2.5 h-2.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <span class="truncate max-w-[130px]">{{ $popName }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Badge & Live Refresh Button -->
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <template x-if="oltStatusMap['{{ $olt->kode_olt }}']?.loading">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                                    <span class="w-2 h-2 border-2 border-amber-500 border-t-transparent rounded-full animate-spin"></span>
                                                    <span>Ping...</span>
                                                </span>
                                            </template>
                                            <template x-if="!oltStatusMap['{{ $olt->kode_olt }}']?.loading">
                                                <div>
                                                    <template x-if="oltStatusMap['{{ $olt->kode_olt }}']?.status === 'online'">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 shadow-2xs" :title="oltStatusMap['{{ $olt->kode_olt }}']?.message">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                            <span x-text="oltStatusMap['{{ $olt->kode_olt }}']?.latency ? oltStatusMap['{{ $olt->kode_olt }}']?.latency + ' ms' : 'Online'"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="oltStatusMap['{{ $olt->kode_olt }}']?.status === 'offline'">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 shadow-2xs" :title="oltStatusMap['{{ $olt->kode_olt }}']?.message">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                            <span>Offline</span>
                                                        </span>
                                                    </template>
                                                    <template x-if="!oltStatusMap['{{ $olt->kode_olt }}']?.status || oltStatusMap['{{ $olt->kode_olt }}']?.status === 'untested'">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                            <span>Belum Teruji</span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- Refresh Telemetry Button -->
                                            <button type="button" 
                                                    @click="refreshTelemetry('{{ $olt->kode_olt }}')"
                                                    :disabled="telemetryLoading['{{ $olt->kode_olt }}']"
                                                    title="Ambil Data Real & Refresh Traffic OLT" 
                                                    class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer disabled:opacity-50">
                                                <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" :class="telemetryLoading['{{ $olt->kode_olt }}'] ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 2. Grid Spesifikasi & Optical Laser (2x2) -->
                                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                                        <!-- IP Host OLT with Copy -->
                                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80 flex items-center justify-between">
                                            <div class="min-w-0">
                                                <span class="block text-[9px] uppercase tracking-wider font-bold text-slate-400 dark:text-slate-500">IP Host OLT</span>
                                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400 truncate block text-[11px]" title="{{ $ipAddr }}">
                                                    {{ $ipAddr }}
                                                </span>
                                            </div>
                                            <button type="button" 
                                                    @click="copyIp('{{ $ipAddr }}')"
                                                    class="text-slate-400 hover:text-blue-500 transition cursor-pointer p-1"
                                                    :title="copiedIp === '{{ $ipAddr }}' ? 'Tersalin!' : 'Salin IP'">
                                                <svg x-show="copiedIp !== '{{ $ipAddr }}'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <svg x-show="copiedIp === '{{ $ipAddr }}'" class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Kapasitas & Pelanggan -->
                                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80">
                                            <span class="block text-[9px] uppercase tracking-wider font-bold text-slate-400 dark:text-slate-500">Kapasitas PON</span>
                                            <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200 text-[11px]">
                                                <span class="text-emerald-600 dark:text-emerald-400 font-extrabold">{{ $capacity }} Port</span>
                                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ $userCount }} Users</span>
                                            </div>
                                        </div>

                                        <!-- Laser Tx Power -->
                                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80">
                                            <span class="block text-[9px] uppercase tracking-wider font-bold text-slate-400 dark:text-slate-500">Laser Tx Power</span>
                                            <div class="flex items-center justify-between mt-0.5">
                                                <span class="font-mono font-bold text-amber-600 dark:text-amber-400 text-xs" x-text="telemetryMap['{{ $olt->kode_olt }}']?.tx_power ?? '{{ $tData['tx_power'] ?? '+4.25 dBm' }}'">
                                                    {{ $tData['tx_power'] ?? '+4.25 dBm' }}
                                                </span>
                                                <span class="text-[9px] px-1 py-0.2 rounded font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                                    Normal
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Avg Rx Signal -->
                                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80">
                                            <span class="block text-[9px] uppercase tracking-wider font-bold text-slate-400 dark:text-slate-500">Avg Rx Signal</span>
                                            <div class="flex items-center justify-between mt-0.5">
                                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs" x-text="telemetryMap['{{ $olt->kode_olt }}']?.rx_power ?? '{{ $tData['rx_power'] ?? '-19.80 dBm' }}'">
                                                    {{ $tData['rx_power'] ?? '-19.80 dBm' }}
                                                </span>
                                                <span class="text-[9px] px-1 py-0.2 rounded font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                                    Optimal
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Traffic Speed Progress Bars -->
                                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/70 dark:border-slate-800/80 space-y-2">
                                        <!-- Download Bar -->
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400 font-medium">
                                                    <svg class="w-3 h-3 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                                    </svg>
                                                    <span>Download (Rx)</span>
                                                </span>
                                                <span class="font-mono font-bold text-cyan-600 dark:text-cyan-400" x-text="telemetryMap['{{ $olt->kode_olt }}']?.download_formatted ?? '{{ $tData['download_formatted'] ?? '0 Mbps' }}'">
                                                    {{ $tData['download_formatted'] ?? '0 Mbps' }}
                                                </span>
                                            </div>
                                            <div class="h-1.5 w-full rounded-full bg-slate-200/80 dark:bg-slate-800 overflow-hidden shadow-inner">
                                                <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full transition-all duration-700 ease-out"
                                                     :style="'width: ' + (telemetryMap['{{ $olt->kode_olt }}']?.download_pct ?? {{ $tData['download_pct'] ?? 15 }}) + '%'"></div>
                                            </div>
                                        </div>

                                        <!-- Upload Bar -->
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400 font-medium">
                                                    <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                                                    </svg>
                                                    <span>Upload (Tx)</span>
                                                </span>
                                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="telemetryMap['{{ $olt->kode_olt }}']?.upload_formatted ?? '{{ $tData['upload_formatted'] ?? '0 Mbps' }}'">
                                                    {{ $tData['upload_formatted'] ?? '0 Mbps' }}
                                                </span>
                                            </div>
                                            <div class="h-1.5 w-full rounded-full bg-slate-200/80 dark:bg-slate-800 overflow-hidden shadow-inner">
                                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-700 ease-out"
                                                     :style="'width: ' + (telemetryMap['{{ $olt->kode_olt }}']?.upload_pct ?? {{ $tData['upload_pct'] ?? 10 }}) + '%'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. Action Buttons (Kiri Bawah) - Tombol Solid Blue & Indigo Tanpa Efek Awan Putih -->
                                <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2">
                                    <a href="{{ route('noc.gpon', ['olt' => $olt->kode_olt]) }}" 
                                       class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-all transform hover:-translate-y-0.5 cursor-pointer whitespace-nowrap"
                                       title="Buka GPON Topologi OLT">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        <span>Buka OLT (GPON)</span>
                                    </a>

                                    <a href="{{ route('noc.olt.pon-traffic', ['kode_olt' => $olt->kode_olt]) }}" 
                                       class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all transform hover:-translate-y-0.5 cursor-pointer whitespace-nowrap"
                                       title="Lihat Grafik Traffic per Port PON">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                        <span>Lihat Traffic per PON</span>
                                    </a>

                                    @if(auth()->user()?->hasRole(['admin', 'direktur']))
                                    <a href="{{ route('noc.olt.edit', $olt->kode_olt) }}" 
                                       class="inline-flex items-center justify-center p-2 rounded-xl bg-slate-100 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:hover:bg-amber-950/40 dark:hover:text-amber-400 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition"
                                       title="Edit Konfigurasi OLT">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </a>

                                    <button type="button" 
                                            @click="confirmDelete('{{ $olt->kode_olt }}', '{{ $olt->name_olt }}')"
                                            class="inline-flex items-center justify-center p-2 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                                            title="Hapus Data OLT">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <!-- KOLOM KANAN (lg:col-span-7): PANEL GRAFIK MRTG LIVE TIME MEMBENTANG LUAS -->
                            <div class="lg:col-span-7 flex flex-col justify-between p-3.5 sm:p-4 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-2.5">
                                <!-- Header & Date Filter Bar -->
                                <div class="space-y-2 pb-1 border-b border-slate-200/80 dark:border-slate-800">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                                Traffic Live Time (MRTG / Cacti Monitor)
                                            </h4>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20 text-[9px] font-mono">
                                                Live Feed
                                            </span>
                                        </div>
                                        <div class="text-[9px] font-mono text-slate-400 dark:text-slate-500">
                                            <span x-text="'Sync: ' + (telemetryMap['{{ $olt->kode_olt }}']?.updated_at ?? '{{ $tData['updated_at'] ?? 'Real-time' }}')">
                                                Sync: {{ $tData['updated_at'] ?? 'Real-time' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Date Range Picker: Dari Tanggal & Sampai Tanggal (DD-MM-YYYY) -->
                                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[11px]">
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                            <!-- Dari Tanggal -->
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">Dari Tanggal:</span>
                                                <div class="relative">
                                                    <input type="date" 
                                                           x-model="dateFilters['{{ $olt->kode_olt }}'].start_date" 
                                                           class="px-2 py-0.5 text-[11px] font-mono rounded bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500" 
                                                           title="Format: DD-MM-YYYY" />
                                                </div>
                                                <span class="text-[9px] text-slate-400 font-mono hidden xl:inline" 
                                                      x-text="dateFilters['{{ $olt->kode_olt }}'].start_date ? '(' + dateFilters['{{ $olt->kode_olt }}'].start_date.split('-').reverse().join('-') + ')' : ''">
                                                </span>
                                            </div>

                                            <!-- Sampai Tanggal -->
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">Sampai Tanggal:</span>
                                                <div class="relative">
                                                    <input type="date" 
                                                           x-model="dateFilters['{{ $olt->kode_olt }}'].end_date" 
                                                           class="px-2 py-0.5 text-[11px] font-mono rounded bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500" 
                                                           title="Format: DD-MM-YYYY" />
                                                </div>
                                                <span class="text-[9px] text-slate-400 font-mono hidden xl:inline" 
                                                      x-text="dateFilters['{{ $olt->kode_olt }}'].end_date ? '(' + dateFilters['{{ $olt->kode_olt }}'].end_date.split('-').reverse().join('-') + ')' : ''">
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Tombol Terapkan Filter -->
                                        <button type="button" 
                                                @click="filterTrafficByDate('{{ $olt->kode_olt }}')"
                                                :disabled="telemetryLoading['{{ $olt->kode_olt }}']"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-600 hover:bg-blue-700 text-white font-semibold text-[10px] shadow-xs transition cursor-pointer disabled:opacity-50">
                                            <svg x-show="!telemetryLoading['{{ $olt->kode_olt }}']" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                            </svg>
                                            <span x-show="telemetryLoading['{{ $olt->kode_olt }}']" class="w-2.5 h-2.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                            <span>Filter Rentang</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Box Frame Grafik MRTG Full Vertical (Mengisi Seluruh Ruang Vertikal Kartu) -->
                                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 shadow-inner flex-1 flex flex-col justify-between min-h-[220px]">
                                    <div class="flex items-stretch gap-2 flex-1 min-h-[170px] sm:min-h-[190px]">
                                        <!-- Sumbu Y Kiri: Label "Bits per Second" + Angka Skala Y -->
                                        <div class="flex items-center gap-1.5 select-none shrink-0">
                                            <span class="text-[8.5px] font-bold tracking-tight text-slate-600 dark:text-slate-400" 
                                                  style="writing-mode: vertical-rl; transform: rotate(180deg);">
                                                Bits per Second
                                            </span>

                                            <div class="flex flex-col justify-between items-end h-full text-[9px] font-mono font-bold text-slate-700 dark:text-slate-300 leading-none py-1">
                                                <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.y_labels?.[0] ?? '{{ $tData['mrtg']['y_labels'][0] ?? '13.2 M' }}'">{{ $tData['mrtg']['y_labels'][0] ?? '13.2 M' }}</span>
                                                <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.y_labels?.[1] ?? '{{ $tData['mrtg']['y_labels'][1] ?? '9.9 M' }}'">{{ $tData['mrtg']['y_labels'][1] ?? '9.9 M' }}</span>
                                                <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.y_labels?.[2] ?? '{{ $tData['mrtg']['y_labels'][2] ?? '6.6 M' }}'">{{ $tData['mrtg']['y_labels'][2] ?? '6.6 M' }}</span>
                                                <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.y_labels?.[3] ?? '{{ $tData['mrtg']['y_labels'][3] ?? '3.3 M' }}'">{{ $tData['mrtg']['y_labels'][3] ?? '3.3 M' }}</span>
                                                <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.y_labels?.[4] ?? '{{ $tData['mrtg']['y_labels'][4] ?? '0.0 M' }}'">{{ $tData['mrtg']['y_labels'][4] ?? '0.0 M' }}</span>
                                            </div>
                                        </div>

                                        <!-- Plot Area (SVG Grafik MRTG Memenuhi Seluruh Ruang Vertikal + Sumbu X Bawah) -->
                                        <div class="flex-1 min-w-0 flex flex-col h-full">
                                            <div class="relative w-full flex-1 min-h-[145px] sm:min-h-[165px] bg-[#f8fafc] dark:bg-[#060c18] border border-slate-700/80 dark:border-slate-600 rounded-xs overflow-hidden">
                                                <svg viewBox="0 0 720 105" preserveAspectRatio="none" class="w-full h-full block">
                                                    <!-- Horizontal Dotted Grid Lines (5 Baris) -->
                                                    <line x1="0" y1="0" x2="720" y2="0" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="0" y1="26.25" x2="720" y2="26.25" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="0" y1="52.5" x2="720" y2="52.5" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="0" y1="78.75" x2="720" y2="78.75" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="0" y1="105" x2="720" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                                    <!-- Vertical Dotted Grid Lines (8 Pembatas Hari) -->
                                                    <line x1="90" y1="0" x2="90" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="180" y1="0" x2="180" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="270" y1="0" x2="270" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="360" y1="0" x2="360" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="450" y1="0" x2="450" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="540" y1="0" x2="540" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>
                                                    <line x1="630" y1="0" x2="630" y2="105" stroke="#94a3b8" stroke-dasharray="2,2" stroke-width="0.8" opacity="0.6"/>

                                                    <!-- Red Vertical Markers (Batas Hari Penting) -->
                                                    <line x1="115.2" y1="0" x2="115.2" y2="105" stroke="#ef4444" stroke-width="1.3"/>
                                                    <line x1="633.6" y1="0" x2="633.6" y2="105" stroke="#ef4444" stroke-width="1.3"/>

                                                    <!-- Inbound Traffic: Solid Green Area (Traffic Memenuhi Ruang Vertikal) -->
                                                    <polygon :points="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.in_polygon ?? '{{ $tData['mrtg']['in_polygon'] ?? '' }}'" 
                                                             fill="#00cc00" 
                                                             fill-opacity="0.95"/>

                                                    <!-- Outbound Traffic: Solid Blue Line -->
                                                    <polyline :points="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.out_polyline ?? '{{ $tData['mrtg']['out_polyline'] ?? '' }}'" 
                                                              fill="none" 
                                                              stroke="#0000ff" 
                                                              stroke-width="1.6"/>

                                                    <!-- Red Arrow Indicator at 0.0M -->
                                                    <polygon points="0,101 6,105 0,109" fill="#ef4444"/>
                                                </svg>
                                            </div>

                                            <!-- Sumbu X: Timeline Hari / Tanggal Dinamis -->
                                            <div class="flex justify-between items-center text-[8px] sm:text-[9px] font-bold font-mono text-slate-600 dark:text-slate-400 mt-1.5 px-1 select-none shrink-0">
                                                <template x-for="(lbl, idx) in (telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.x_labels ?? ['Mon','Sun','Sat','Fri','Thu','Wed','Tue','Mon','Sun'])" :key="idx">
                                                    <span x-text="lbl"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MRTG Summary Legend (In & Out Stats) Menempel Rapi di Bawah Grafik -->
                                    <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[9.5px] font-mono shrink-0">
                                        <!-- Inbound Stats -->
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-xs bg-[#00cc00] border border-emerald-600 shrink-0"></span>
                                            <span class="text-slate-600 dark:text-slate-400 font-bold">In Traffic:</span>
                                            <span class="text-slate-700 dark:text-slate-200">
                                                Current: <strong class="text-emerald-600 dark:text-emerald-400" x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.current_in ?? '{{ $tData['mrtg']['current_in'] ?? '0.0 M' }}'">{{ $tData['mrtg']['current_in'] ?? '0.0 M' }}</strong>
                                                &middot; Max: <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.max_in ?? '{{ $tData['mrtg']['max_in'] ?? '0.0 M' }}'">{{ $tData['mrtg']['max_in'] ?? '0.0 M' }}</span>
                                                &middot; Avg: <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.avg_in ?? '{{ $tData['mrtg']['avg_in'] ?? '0.0 M' }}'">{{ $tData['mrtg']['avg_in'] ?? '0.0 M' }}</span>
                                            </span>
                                        </div>

                                        <!-- Outbound Stats -->
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-1.5 rounded-xs bg-[#0000ff] shrink-0"></span>
                                            <span class="text-slate-600 dark:text-slate-400 font-bold">Out Traffic:</span>
                                            <span class="text-slate-700 dark:text-slate-200">
                                                Current: <strong class="text-blue-600 dark:text-blue-400" x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.current_out ?? '{{ $tData['mrtg']['current_out'] ?? '0.0 M' }}'">{{ $tData['mrtg']['current_out'] ?? '0.0 M' }}</strong>
                                                &middot; Max: <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.max_out ?? '{{ $tData['mrtg']['max_out'] ?? '0.0 M' }}'">{{ $tData['mrtg']['max_out'] ?? '0.0 M' }}</span>
                                                &middot; Avg: <span x-text="telemetryMap['{{ $olt->kode_olt }}']?.mrtg?.avg_out ?? '{{ $tData['mrtg']['avg_out'] ?? '0.0 M' }}'">{{ $tData['mrtg']['avg_out'] ?? '0.0 M' }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6" />
                        </svg>
                        <h4 class="font-bold text-slate-700 dark:text-slate-300">Tidak ada data OLT</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Tambahkan OLT baru atau sesuaikan kata kunci pencarian.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- B. TABEL LIST VIEW CONTAINER (JIKA SWITCH KE TABEL)             -->
        <!-- =============================================================== -->
        <div x-show="viewMode === 'table'" class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/80 text-slate-700 dark:text-slate-300 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-2.5 px-3.5">KODE OLT</th>
                        <th class="py-2.5 px-3.5">NAMA OLT</th>
                        <th class="py-2.5 px-3.5">POP SERVER</th>
                        <th class="py-2.5 px-3.5">IP ADDRESS</th>
                        <th class="py-2.5 px-3.5">KAPASITAS</th>
                        <th class="py-2.5 px-3.5">STATUS KONEKSI</th>
                        <th class="py-2.5 px-3.5 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800/80 bg-white dark:bg-slate-900/40 text-slate-700 dark:text-slate-200">
                    @forelse($olts as $olt)
                        @php
                            $popName = $olt->nama_pop ?? ($olt->kode_pop ?? 'POP Utama MSN');
                            $ipAddr = !empty($olt->ip_address) ? $olt->ip_address : '10.10.10.1';
                            $brandName = !empty($olt->brand) ? $olt->brand : 'ZTE C320';
                            $capacity = !empty($olt->capacity_olt) ? $olt->capacity_olt : 8;
                            $kodeDisplay = !empty($olt->kode_olt) ? $olt->kode_olt : ($olt->hostname ?? 'OLT-MSN');
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <!-- 1. Kode OLT -->
                            <td class="py-2.5 px-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6.5 h-6.5 rounded-md bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0 shadow-2xs font-mono font-bold text-xs">
                                        {{ $kodeDisplay }}
                                    </div>
                                    <span class="font-mono font-bold text-slate-900 dark:text-white text-xs tracking-wide">
                                        {{ $kodeDisplay }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Nama OLT & Merk -->
                            <td class="py-2.5 px-3.5">
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ $olt->name_olt ?? $olt->nama_olt ?? 'OLT Gateway' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                        Merk: {{ $brandName }}
                                    </div>
                                </div>
                            </td>

                            <!-- 3. POP Server -->
                            <td class="py-2.5 px-3.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs">
                                    <svg class="w-2.5 h-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    <span>{{ $popName }}</span>
                                </span>
                            </td>

                            <!-- 4. IP Address -->
                            <td class="py-2.5 px-3.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 shadow-2xs">
                                    <span>{{ $ipAddr }}</span>
                                </span>
                            </td>

                            <!-- 5. Kapasitas Port PON -->
                            <td class="py-2.5 px-3.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 shadow-2xs">
                                    <span>{{ $capacity }} Port PON</span>
                                </span>
                            </td>

                            <!-- 6. Status Koneksi OLT -->
                            <td class="py-2.5 px-3.5">
                                <div class="flex items-center gap-1.5">
                                    <template x-if="oltStatusMap['{{ $olt->kode_olt }}']?.loading">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                            <span class="w-2.5 h-2.5 border-2 border-amber-500 border-t-transparent rounded-full animate-spin"></span>
                                            <span>Ping...</span>
                                        </span>
                                    </template>
                                    <template x-if="!oltStatusMap['{{ $olt->kode_olt }}']?.loading">
                                        <div>
                                            <template x-if="oltStatusMap['{{ $olt->kode_olt }}']?.status === 'online'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 shadow-2xs" :title="oltStatusMap['{{ $olt->kode_olt }}']?.message">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span x-text="oltStatusMap['{{ $olt->kode_olt }}']?.latency ? 'Terhubung (' + oltStatusMap['{{ $olt->kode_olt }}']?.latency + ' ms)' : 'Terhubung (Online)'"></span>
                                                </span>
                                            </template>
                                            <template x-if="oltStatusMap['{{ $olt->kode_olt }}']?.status === 'offline'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 shadow-2xs" :title="oltStatusMap['{{ $olt->kode_olt }}']?.message">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    <span>Terputus (Offline)</span>
                                                </span>
                                            </template>
                                            <template x-if="!oltStatusMap['{{ $olt->kode_olt }}']?.status || oltStatusMap['{{ $olt->kode_olt }}']?.status === 'untested'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    <span>Belum Teruji</span>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                    
                                    <button type="button" 
                                            @click="testPingOlt('{{ $olt->kode_olt }}', '{{ $ipAddr }}', {{ $olt->port ?? 23 }}, '{{ $olt->protocol ?? 'telnet' }}', '{{ $olt->username ?? '' }}')"
                                            :disabled="oltStatusMap['{{ $olt->kode_olt }}']?.loading"
                                            title="Klik untuk Test Ping Koneksi OLT" 
                                            class="p-1 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer disabled:opacity-50">
                                        <svg class="w-3 h-3 text-blue-500 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>
                                </div>
                            </td>

                            <!-- 7. Action Buttons -->
                            <td class="py-2.5 px-3.5 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('noc.gpon', ['olt' => $olt->kode_olt]) }}" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-semibold shadow-2xs transition">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        <span>Buka OLT</span>
                                    </a>

                                    <a href="{{ route('noc.olt.pon-traffic', ['kode_olt' => $olt->kode_olt]) }}" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-semibold shadow-2xs transition"
                                       title="Lihat Detail Traffic per PON">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                        <span>Traffic PON</span>
                                    </a>

                                    @if(auth()->user()?->hasRole(['admin', 'direktur']))
                                    <a href="{{ route('noc.olt.edit', $olt->kode_olt) }}" 
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-600 hover:bg-amber-500 text-white text-[10px] font-semibold shadow-2xs shadow-amber-500/20 transition">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    <button type="button" 
                                            @click="confirmDelete('{{ $olt->kode_olt }}', '{{ $olt->name_olt }}')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-rose-600 hover:bg-rose-500 text-white text-[10px] font-semibold shadow-2xs shadow-rose-500/20 transition cursor-pointer">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 dark:text-slate-400 text-xs">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-600 mb-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6" />
                                    </svg>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">Tidak ada data OLT ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="px-3.5 py-2.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-[11px] text-slate-600 dark:text-slate-400">
            <div>
                Showing {{ $olts->firstItem() ?? 0 }} to {{ $olts->lastItem() ?? 0 }} of {{ $olts->total() }} results
            </div>
            <div>
                {{ $olts->links() }}
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. MODAL CONFIRM DELETE OLT                                         -->
    <!-- =================================================================== -->
    <div x-show="deleteModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-slate-800 dark:text-slate-100"
             @click.away="deleteModalOpen = false">
            <div class="flex items-center gap-3 text-rose-600 dark:text-rose-500">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Hapus Perangkat OLT?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300">
                Apakah Anda yakin ingin menghapus data OLT <span class="font-bold text-slate-900 dark:text-white" x-text="oltToDelete?.name"></span> (<span class="font-mono text-blue-600 dark:text-blue-400 font-bold" x-text="oltToDelete?.kode"></span>)?
            </p>

            <form :action="deleteActionUrl" method="POST" class="flex items-center justify-end gap-2.5 pt-2">
                @csrf
                <button type="button" 
                        @click="deleteModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-lg shadow-rose-500/25 transition cursor-pointer">
                    Ya, Hapus OLT
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
