@extends('layouts.app')

@section('title', 'Topologi & Sistem Informasi Port GPON - NOC IMS')
@section('page_title', 'Topologi & Pemetaan Port GPON')

@section('content')
<div class="space-y-6"
     x-data="{
        slotDetailModalOpen: false,
        selectedSlotData: null,
        syncingLive: false,
        liveSyncStatus: null,
        uncfgModalOpen: false,
        scanningUncfg: false,
        uncfgList: [],

        openSlotDetail(slot) {
            if (slot.customer) {
                this.selectedSlotData = slot;
                this.slotDetailModalOpen = true;
            }
        },

        async syncLiveOlt() {
            this.syncingLive = true;
            this.liveSyncStatus = null;
            try {
                const res = await fetch('{{ route('noc.olt.sync-live') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        kode_olt: '{{ $selectedOlt }}',
                        port: '{{ $selectedPort }}'
                    })
                });
                const data = await res.json();
                this.liveSyncStatus = data;
            } catch (e) {
                this.liveSyncStatus = { success: false, message: 'Koneksi error: ' + e.message };
            } finally {
                this.syncingLive = false;
            }
        },

        async scanUncfg() {
            this.uncfgModalOpen = true;
            this.scanningUncfg = true;
            this.uncfgList = [];
            try {
                const res = await fetch('{{ route('noc.olt.scan-uncfg') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        kode_olt: '{{ $selectedOlt }}'
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.uncfgList = data.data || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.scanningUncfg = false;
            }
        }
     }">

    <!-- Top Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-950 via-slate-900 to-indigo-950 border border-blue-500/20 p-6 shadow-2xl backdrop-blur-xl">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 relative z-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 mb-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
                    <span>GPON TOPOLOGY & ONU SLOT ALLOCATION SYSTEM</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    Sistem Informasi Topologi & Pemetaan Port GPON
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-3xl">
                    Visualisasi kapasitas 128 slot ONU per port, monitoring utilisasi pelanggan aktif/suspend, dan alokasi index OLT real-time.
                </p>
            </div>

            <!-- Quick Links & Live Actions -->
            <div class="flex flex-wrap items-center gap-2">
                @if($selectedOlt !== 'all')
                    <button type="button" 
                            @click="syncLiveOlt()"
                            :disabled="syncingLive"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-500/25 transition cursor-pointer disabled:opacity-50">
                        <template x-if="!syncingLive">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                        </template>
                        <template x-if="syncingLive">
                            <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        </template>
                        <span x-text="syncingLive ? 'Syncing...' : '⚡ Sync Live OLT'"></span>
                    </button>

                    <button type="button" 
                            @click="scanUncfg()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-500/25 transition cursor-pointer">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span>🔍 Scan ONU Baru</span>
                    </button>
                @endif

                <a href="{{ route('noc.olt') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-200 text-slate-200 text-xs font-bold border border-slate-200 transition">
                    <span>Master OLT</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Live Sync Alert Feedback -->
    <template x-if="liveSyncStatus">
        <div :class="liveSyncStatus.success ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400'"
             class="p-4 rounded-2xl border text-xs font-semibold flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2.5">
                <span :class="liveSyncStatus.success ? 'bg-emerald-400' : 'bg-rose-400'" class="w-2.5 h-2.5 rounded-full animate-ping"></span>
                <span x-text="liveSyncStatus.message"></span>
            </div>
            <button type="button" @click="liveSyncStatus = null" class="text-slate-400 hover:text-white font-bold text-sm">&times;</button>
        </div>
    </template>

    <!-- =================================================================== -->
    <!-- STEP 1 & 2: HIERARCHICAL DRILL-DOWN (OLT -> SLOT CARD -> PON PORT) -->
    <!-- =================================================================== -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl p-5 shadow-xl shadow-slate-200/40 space-y-5">
        
        <!-- Drilldown Top Bar: OLT Selector & Slot Tabs -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-slate-200">
            
            <!-- 1. OLT Gateway Selector (Dropdown) -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                    </svg>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">1. Pilih Node OLT Gateway</label>
                    <select onchange="window.location.href='{{ url('/noc/gpon') }}?olt=' + this.value"
                            class="mt-0.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" {{ $selectedOlt === 'all' ? 'selected' : '' }}>
                            Semua OLT Gateway ({{ $oltStats['all'] ?? 793 }} Pelanggan)
                        </option>
                        @foreach($olts as $olt)
                            @php $cCount = $oltStats[$olt->kode_olt] ?? 0; @endphp
                            <option value="{{ $olt->kode_olt }}" {{ $selectedOlt === $olt->kode_olt ? 'selected' : '' }}>
                                {{ $olt->name_olt }} ({{ $olt->kode_olt }}) &middot; {{ $cCount }} Pelanggan
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 2. Slot / Line Card Tabs (Slot 1 vs Slot 2) -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 hidden sm:inline">2. Pilih Card / Slot:</span>
                <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 border border-slate-200 text-xs">
                    <a href="{{ route('noc.gpon', ['olt' => $selectedOlt, 'slot' => '1', 'port' => 'gpon-onu_1/1/1']) }}"
                       class="px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 {{ $selectedSlot == '1' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 hover:text-white' }}">
                        <span>Slot 1 (PON 1/1/1 - 1/1/16)</span>
                    </a>
                    <a href="{{ route('noc.gpon', ['olt' => $selectedOlt, 'slot' => '2', 'port' => 'gpon-onu_1/2/1']) }}"
                       class="px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 {{ $selectedSlot == '2' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 hover:text-white' }}">
                        <span>Slot 2 (PON 1/2/1 - 1/2/16)</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- 3. Port GPON Grid Selector (16 Ports on Selected Slot) -->
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px]">
                    3. Pilih Port GPON (Slot {{ $selectedSlot }}):
                </span>
                <span class="text-slate-400 text-[11px]">Masing-masing port berkapasitas max 128 ONU</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2.5">
                @foreach($currentSlotPorts as $pName)
                    @php 
                        $pStat = $portStats[$pName] ?? ['used' => 0, 'free' => 128, 'utilization' => 0]; 
                        $isSelected = ($selectedPort === $pName);
                    @endphp
                    <a href="{{ route('noc.gpon', ['olt' => $selectedOlt, 'slot' => $selectedSlot, 'port' => $pName]) }}"
                       class="p-2.5 rounded-xl border text-center transition relative overflow-hidden group {{ $isSelected ? 'bg-blue-600 text-white border-blue-400 shadow-lg shadow-blue-500/30 ring-2 ring-blue-400/50' : 'bg-slate-50 border-slate-200 hover:border-blue-500/50 text-slate-800 ' }}">
                        
                        <div class="font-mono text-xs font-bold truncate">
                            {{ str_replace('gpon-onu_', '', $pName) }}
                        </div>
                        <div class="text-[10px] mt-1 font-semibold {{ $isSelected ? 'text-blue-100' : ($pStat['used'] > 0 ? 'text-emerald-500' : 'text-slate-400') }}">
                            {{ $pStat['used'] }} / 128
                        </div>

                        <!-- Mini Progress Bar -->
                        <div class="w-full h-1 bg-black/20 rounded-full mt-1.5 overflow-hidden">
                            <div class="h-full {{ $isSelected ? 'bg-white' : 'bg-blue-500' }}" style="width: {{ $pStat['utilization'] }}%"></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- ACTIVE PORT STATUS & LIVE METRICS KPI CARDS                         -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Total Terhubung & Utilisasi -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 backdrop-blur-xl shadow-xl shadow-slate-200/40">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Port Aktif</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/15 text-blue-400 font-mono">
                    {{ $activePortStats['port'] }}
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-1 font-mono">
                {{ $activePortStats['used'] }} <span class="text-xs font-normal text-slate-400">/ 128 ONU</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full mt-2 overflow-hidden">
                <div class="h-full bg-blue-500 rounded-full" style="width: {{ $activePortStats['utilization'] }}%"></div>
            </div>
            <div class="text-[10px] text-slate-400 mt-1 flex justify-between">
                <span>Utilisasi Kapasitas</span>
                <span class="font-bold text-blue-400">{{ $activePortStats['utilization'] }}%</span>
            </div>
        </div>

        <!-- 2. Pelanggan Online / Terhubung -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 backdrop-blur-xl shadow-xl shadow-slate-200/40 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pelanggan Aktif</span>
                <div class="text-2xl font-black text-emerald-500 mt-1 font-mono">
                    {{ $activePortStats['used'] }} <span class="text-xs font-normal text-slate-400">Online</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Status Koneksi Normal</div>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center flex-shrink-0">
                <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
        </div>

        <!-- 3. Slot Kosong (Available) -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 backdrop-blur-xl shadow-xl shadow-slate-200/40 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Slot Kosong (Bebas)</span>
                <div class="text-2xl font-black text-cyan-500 mt-1 font-mono">
                    {{ $activePortStats['free'] }} <span class="text-xs font-normal text-slate-400">Slot</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Siap untuk Pelanggan Baru</div>
            </div>
            <div class="w-11 h-11 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center flex-shrink-0 font-bold font-mono text-sm">
                FREE
            </div>
        </div>

        <!-- 4. Total Bandwidth Terdistribusi -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 backdrop-blur-xl shadow-xl shadow-slate-200/40 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Bandwidth</span>
                <div class="text-2xl font-black text-indigo-400 mt-1 font-mono">
                    {{ number_format($activePortStats['total_bandwidth'], 0) }} <span class="text-xs font-normal text-slate-400">Mbps</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Aggregated Traffic Port</div>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- VISUAL MATRIX GRID: 128 ONU SLOTS PER PORT                          -->
    <!-- =================================================================== -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl p-5 shadow-xl shadow-slate-200/40 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <span>Matriks 128 Slot ONU - {{ $selectedPort }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-500/15 text-blue-400">Klik slot untuk detail</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Pemetaan visual slot 1 hingga 128 pada port optik terpilih.</p>
            </div>

            <!-- Legend Indicator -->
            <div class="flex flex-wrap items-center gap-3 text-[11px]">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-emerald-500"></span>
                    <span class="text-slate-300">Aktif (#20)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-amber-500"></span>
                    <span class="text-slate-300">Suspend (#21)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-blue-500"></span>
                    <span class="text-slate-300">Siap Aktivasi (#18)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-slate-200 border border-slate-300"></span>
                    <span class="text-slate-400">Kosong / Bebas</span>
                </div>
            </div>
        </div>

        <!-- 128-Slot Visual Grid (16 columns on desktop) -->
        <div class="grid grid-cols-4 sm:grid-cols-8 md:grid-cols-12 lg:grid-cols-16 gap-1.5">
            @foreach($matrixSlots as $slot)
                @php
                    $isOccupied = ($slot['status'] !== 'available');
                    $bgClass = 'bg-slate-100  border-slate-200  text-slate-400 hover:border-slate-500 cursor-default';
                    if ($slot['status'] === 'active') {
                        $bgClass = 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/25 hover:border-emerald-400 cursor-pointer shadow-sm';
                    } elseif ($slot['status'] === 'suspend') {
                        $bgClass = 'bg-amber-500/15 border-amber-500/30 text-amber-400 hover:bg-amber-500/25 hover:border-amber-400 cursor-pointer shadow-sm';
                    } elseif ($slot['status'] === 'pending') {
                        $bgClass = 'bg-blue-500/15 border-blue-500/30 text-blue-400 hover:bg-blue-500/25 hover:border-blue-400 cursor-pointer shadow-sm';
                    }
                @endphp

                <div @if($isOccupied) @click="openSlotDetail({{ json_encode($slot) }})" @endif
                     title="{{ $isOccupied ? $slot['key'] . ' : ' . ($slot['customer']->nama_pelanggan ?? '') : $slot['key'] . ' (Kosong / Bebas)' }}"
                     class="p-2 rounded-lg border text-center transition duration-150 relative group {{ $bgClass }}">
                    
                    <div class="text-[10px] font-mono font-bold">
                        :{{ $slot['num'] }}
                    </div>

                    @if($isOccupied)
                        <div class="w-1.5 h-1.5 rounded-full mx-auto mt-1 {{ $slot['status'] === 'active' ? 'bg-emerald-400 animate-pulse' : ($slot['status'] === 'suspend' ? 'bg-amber-400' : 'bg-blue-400') }}"></div>
                    @else
                        <div class="text-[9px] text-slate-500 mt-1">&middot;</div>
                    @endif
                </div>
            @endforeach
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- DETAILED TABLE OF CONNECTED CUSTOMERS ON SELECTED GPON PORT         -->
    <!-- =================================================================== -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Daftar Pelanggan Terhubung pada Port {{ $selectedPort }}
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Total {{ count($connectedCustomersOnPort) }} Pelanggan terhubung ke port ini.</p>
            </div>
            <span class="text-xs text-blue-400 font-mono font-bold">{{ count($connectedCustomersOnPort) }} / 128 ONU</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-700">
                        <th class="py-3.5 px-4 w-24">Slot ONU</th>
                        <th class="py-3.5 px-4">No Internet / Pelanggan</th>
                        <th class="py-3.5 px-4">Paket & Bandwidth</th>
                        <th class="py-3.5 px-4">Alamat Pemasangan</th>
                        <th class="py-3.5 px-4">POP Gateway</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($connectedCustomersOnPort as $item)
                        @php $c = $item['customer']; @endphp
                        <tr class="hover:bg-slate-50 transition">
                            
                            <!-- 1. Slot ONU -->
                            <td class="py-3.5 px-4 font-mono font-bold text-blue-500">
                                <span class="px-2 py-1 rounded bg-blue-500/10 border border-blue-500/20">
                                    :{{ $item['slot'] }}
                                </span>
                            </td>

                            <!-- 2. Pelanggan -->
                            <td class="py-3.5 px-4">
                                <a href="{{ route('teknik.pelanggan.profile', $c->nomor_internet) }}" 
                                   class="font-mono font-bold text-blue-500 hover:underline">
                                    {{ $c->nomor_internet }}
                                </a>
                                <div class="font-semibold text-slate-800 uppercase mt-0.5">
                                    {{ $c->nama_pelanggan }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    📱 {{ $c->nomor_hp ?: '-' }}
                                </div>
                            </td>

                            <!-- 3. Paket Bandwidth -->
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800">
                                    {{ $c->nama_kategori_bandwith ?: ($c->alias_nama_kategori ?: 'INTERNET') }}
                                </div>
                                <div class="text-[11px] text-blue-400 font-bold font-mono mt-0.5">
                                    {{ $c->nominal_bandwith ?: '10' }} Mbps
                                </div>
                            </td>

                            <!-- 4. Alamat Pemasangan -->
                            <td class="py-3.5 px-4 text-slate-600 max-w-[220px] truncate">
                                <div>{{ $c->alamat_p ?: ($c->alamat_pasang ?: '-') }}</div>
                            </td>

                            <!-- 5. POP Gateway -->
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800">
                                    {{ $c->nama_pop ?: '-' }}
                                </span>
                            </td>

                            <!-- 6. Status -->
                            <td class="py-3.5 px-4">
                                @if(in_array($c->status_reg, ['20']))
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                        ONLINE / AKTIF
                                    </span>
                                @elseif(in_array($c->status_reg, ['21', '21.1']))
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-500 border border-amber-500/20">
                                        SUSPEND
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/15 text-slate-400 border border-slate-500/20">
                                        {{ $c->desc_registrasi ?: 'Status #' . $c->status_reg }}
                                    </span>
                                @endif
                            </td>

                            <!-- 7. Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('teknik.pelanggan.profile', $c->nomor_internet) }}" 
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold border border-slate-200 transition">
                                    <span>Detail Pelanggan</span>
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                Belum ada pelanggan yang terhubung pada port {{ $selectedPort }}. Semua 128 slot tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: DETAIL PELANGGAN TERHUBUNG PADA SLOT                          -->
    <!-- =================================================================== -->
    <div x-show="slotDetailModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <div class="fixed inset-0 bg-slate-100 backdrop-blur-sm transition-opacity" 
             @click="slotDetailModalOpen = false"></div>
        
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-2xl p-6 space-y-4 text-xs"
                 @click.away="slotDetailModalOpen = false">
                
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>Detail ONU Slot</span>
                            <span class="px-2 py-0.5 rounded bg-blue-500/15 text-blue-400 font-mono" x-text="selectedSlotData ? selectedSlotData.key : ''"></span>
                        </h3>
                    </div>
                    <button type="button" @click="slotDetailModalOpen = false" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <template x-if="selectedSlotData && selectedSlotData.customer">
                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] text-slate-400">ID Pelanggan / Nomor Internet</div>
                            <div class="text-base font-black text-blue-500 font-mono mt-0.5" x-text="selectedSlotData.customer.nomor_internet"></div>
                            <div class="text-xs font-bold text-slate-800 uppercase mt-1" x-text="selectedSlotData.customer.nama_pelanggan"></div>
                            <div class="text-[11px] text-slate-400 mt-0.5" x-text="'📱 ' + (selectedSlotData.customer.nomor_hp || '-')"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                                <span class="text-[10px] text-slate-400 block">Paket Bandwidth</span>
                                <span class="font-bold text-slate-800" x-text="selectedSlotData.customer.nama_kategori_bandwith || 'INTERNET'"></span>
                                <span class="text-blue-400 font-mono block text-[11px]" x-text="(selectedSlotData.customer.nominal_bandwith || '') + ' Mbps'"></span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                                <span class="text-[10px] text-slate-400 block">Node POP Gateway</span>
                                <span class="font-bold text-slate-800" x-text="selectedSlotData.customer.nama_pop || '-'"></span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-slate-400 block">Alamat Pemasangan</span>
                            <span class="text-slate-700" x-text="selectedSlotData.customer.alamat_p || selectedSlotData.customer.alamat_pasang || '-'"></span>
                        </div>

                        <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                            <button type="button" @click="slotDetailModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-semibold">Tutup</button>
                            <a :href="'{{ url('/teknik/pelanggan') }}/' + selectedSlotData.customer.nomor_internet" 
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold shadow-md shadow-blue-500/25">
                                <span>Lihat Profil Lengkap</span>
                            </a>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: SCAN UNCONFIGURED / NEW ONU                                  -->
    <!-- =================================================================== -->
    <div x-show="uncfgModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <div class="fixed inset-0 bg-slate-100 backdrop-blur-sm transition-opacity" 
             @click="uncfgModalOpen = false"></div>
        
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white border border-slate-200 rounded-2xl shadow-2xl p-6 space-y-4 text-xs"
                 @click.away="uncfgModalOpen = false">
                
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500 animate-ping"></span>
                        <h3 class="text-sm font-bold text-slate-900">
                            Hasil Scan ONU Baru (Unconfigured) di OLT
                        </h3>
                    </div>
                    <button type="button" @click="uncfgModalOpen = false" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <div class="space-y-3">
                    <template x-if="scanningUncfg">
                        <div class="py-8 text-center space-y-2">
                            <span class="inline-block w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></span>
                            <div class="text-xs text-slate-400 font-semibold">Sedang mengeksekusi perintah scan ke OLT...</div>
                        </div>
                    </template>

                    <template x-if="!scanningUncfg && uncfgList.length === 0">
                        <div class="py-8 text-center text-slate-400">
                            <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <span class="font-bold block">Tidak ada ONU baru (Unconfigured) yang ditemukan.</span>
                            <span class="text-[11px] text-slate-500">Semua ONU yang tertancap di OLT sudah terkonfigurasi.</span>
                        </div>
                    </template>

                    <template x-if="!scanningUncfg && uncfgList.length > 0">
                        <div class="space-y-2">
                            <div class="text-[11px] text-purple-400 font-semibold" x-text="'Ditemukan ' + uncfgList.length + ' ONU baru yang belum didaftarkan:'"></div>
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-400">
                                        <tr>
                                            <th class="py-2.5 px-3">Port PON</th>
                                            <th class="py-2.5 px-3">Serial Number (SN)</th>
                                            <th class="py-2.5 px-3 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-mono">
                                        <template x-for="(item, idx) in uncfgList" :key="idx">
                                            <tr>
                                                <td class="py-2.5 px-3 font-bold text-blue-500" x-text="item.port"></td>
                                                <td class="py-2.5 px-3 font-bold text-slate-900" x-text="item.sn"></td>
                                                <td class="py-2.5 px-3 text-right">
                                                    <a :href="'{{ url('/noc/aktivasi') }}?sn=' + encodeURIComponent(item.sn)" 
                                                       class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-sans text-[10px] font-bold">
                                                        Aktivasi
                                                    </a>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                        <button type="button" @click="scanUncfg()" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 cursor-pointer">
                            <span>🔄 Scan Ulang</span>
                        </button>
                        <button type="button" @click="uncfgModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-semibold">
                            Tutup
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
