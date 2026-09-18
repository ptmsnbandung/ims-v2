@extends('layouts.app')

@section('title', 'Profile Pelanggan - ' . $customer->nomor_internet)
@section('page_title', 'Profile Pelanggan')

@section('content')
@php
    // Helper Format WhatsApp
    $formatWa = function($phone) {
        if (!$phone) return null;
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        return $clean;
    };

    $waHp1 = $formatWa($customer->nomor_hp);
    $waHp2 = $formatWa($customer->nomor_hp_2);

    // Clean Maps & Koordinat
    $cleanLonLat = trim(str_replace(['Â', 'â', '€', '™'], '', (string)$customer->lon_lat));
    $mapsUrl = $customer->loc_maps;
    if (!$mapsUrl && $cleanLonLat) {
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($cleanLonLat);
    }
@endphp

<div class="space-y-6"
     x-data="{
         // Active Tab: 'log', 'arsip', 'layanan', 'suspend', 'tagihan', 'pengaduan', 'perangkat'
         activeTab: 'perangkat',

         // Upload Modal State for Arsip Scan Dokumen
         uploadModalOpen: false,
         uploadDocType: '',
         uploadDocLabel: '',

         openUploadModal(docType, docLabel) {
             this.uploadDocType = docType;
             this.uploadDocLabel = docLabel;
             this.uploadModalOpen = true;
         },

         // Ubah Data Config Modal State
         pppoeModalOpen: false,
         modalOntUs: '{{ addslashes($customer->ont_us ?: $customer->nomor_internet) }}',
         modalOntPs: '{{ addslashes($customer->ont_ps ?: '') }}',
         modalKodePop: '{{ addslashes($customer->kode_pop ?: '') }}',
         modalMediaAkses: '{{ addslashes($customer->media_akses ?: 'FTTH') }}',
         modalOlt: '{{ addslashes($customer->olt ?? 'O1') }}',
         modalCatatan: '{{ addslashes($instalasi->aktivasi_note_finish ?? ($instalasi->aktivasi_note ?? ($customer->note_request ?? ''))) }}',

         // Index OLT Dynamic Selection
         selectedGponPort: 'gpon-onu_1/1/1',
         selectedOnuSlot: '',
         modalIndexOlt: '{{ addslashes($customer->index_olt ?: '') }}',
         allSlots: {{ json_encode($indexOltSlots ?? []) }},

         init() {
             this.parseIndexOlt('{{ addslashes($customer->index_olt ?: '') }}');
         },

         parseIndexOlt(val) {
             if (val && val.includes(':')) {
                 let parts = val.trim().split(':');
                 let portPart = parts[0];
                 if (!portPart.startsWith('gpon-onu_') && portPart.startsWith('1/')) {
                     portPart = 'gpon-onu_' + portPart;
                 }
                 this.selectedGponPort = portPart;
                 this.selectedOnuSlot = parts[1];
                 this.modalIndexOlt = portPart + ':' + parts[1];
             } else {
                 this.selectedGponPort = 'gpon-onu_1/1/1';
                 this.selectedOnuSlot = '';
                 this.modalIndexOlt = val || '';
             }
         },

         updateIndexOlt() {
             if (this.selectedGponPort && this.selectedOnuSlot) {
                 this.modalIndexOlt = this.selectedGponPort + ':' + this.selectedOnuSlot;
             } else {
                 this.modalIndexOlt = '';
             }
         },

         openPppoeModal() {
             this.modalOntUs = '{{ addslashes($customer->ont_us ?: $customer->nomor_internet) }}';
             this.modalOntPs = '{{ addslashes($customer->ont_ps ?: '') }}';
             this.modalKodePop = '{{ addslashes($customer->kode_pop ?: '') }}';
             this.modalMediaAkses = '{{ addslashes($customer->media_akses ?: 'FTTH') }}';
             this.modalCatatan = '{{ addslashes($instalasi->aktivasi_note_finish ?? ($instalasi->aktivasi_note ?? ($customer->note_request ?? ''))) }}';
             this.parseIndexOlt('{{ addslashes($customer->index_olt ?: '') }}');
             this.pppoeModalOpen = true;
         },

         // Tambah Perangkat Modal State
         tambahPerangkatModalOpen: false,
         modalKodeBarang: '',
         modalJumlahBarang: 1,
         modalNoteBarang: ''
     }">

    <!-- Main Grid: Left Profile Card + Right Tabbed Detail Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- =============================================================== -->
        <!-- LEFT COLUMN: PROFILE PELANGGAN CARD                            -->
        <!-- =============================================================== -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Profile Title Header -->
            <div class="text-center">
                <h2 class="text-base font-bold text-slate-800 dark:text-white tracking-wide">
                    Profile Pelanggan
                </h2>
            </div>

            <!-- Profile Info Main Card -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-5 shadow-xl shadow-black/10 dark:shadow-black/30 space-y-6">
                
                <!-- Center Hero Box -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 text-center space-y-1.5">
                    <!-- Nomor Internet / Customer ID -->
                    <div class="text-xl font-black tracking-wider text-slate-900 dark:text-white font-mono">
                        {{ $customer->nomor_internet }}
                    </div>

                    <!-- Nama Pelanggan -->
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase">
                        {{ $customer->nama_pelanggan }}
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                        ( {{ $customer->jenis_kelamin == 1 ? 'L' : ($customer->jenis_kelamin == 2 ? 'P' : '-') }} )
                    </div>

                    <!-- Bandwidth & Package -->
                    <div class="text-xs text-slate-600 dark:text-slate-400 font-medium uppercase pt-1">
                        {{ $customer->nama_kategori_bandwith ?: ($customer->alias_nama_kategori ?: 'LAYANAN INTERNET') }}
                        @if($customer->nominal_bandwith)
                            <span>{{ $customer->nominal_bandwith }} Mbps</span>
                        @endif
                    </div>

                    <!-- Sales / PIC -->
                    <div class="text-[11px] text-cyan-500 dark:text-cyan-400 font-semibold uppercase">
                        PIC SALES : {{ $customer->nama_sales ?: '-' }}
                    </div>
                </div>

                <!-- Field Detail Rows with Stylish Underlines -->
                <div class="space-y-4 text-xs">
                    
                    <!-- 1. Tanggal Lahir -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Tanggal lahir</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ $customer->tanggal_lahir && $customer->tanggal_lahir != '0000-00-00' ? \Carbon\Carbon::parse($customer->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                            </span>
                        </div>
                        <div class="h-0.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-400 w-1/3"></div>
                        </div>
                    </div>

                    <!-- 2. Nomor HP (Klik Langsung ke WhatsApp) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Nomor HP</span>
                            @if($waHp1)
                                <a href="https://wa.me/{{ $waHp1 }}" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1.5 font-bold text-cyan-400 hover:text-cyan-300 font-mono hover:underline transition"
                                   title="Klik untuk Chat via WhatsApp">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                    <span>{{ $customer->nomor_hp }}</span>
                                </a>
                            @else
                                <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono">-</span>
                            @endif
                        </div>
                        <div class="h-0.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-400 w-full"></div>
                        </div>
                    </div>

                    <!-- 3. Nomor HP Keluarga (Klik Langsung ke WhatsApp) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Nomor HP keluarga</span>
                            @if($waHp2)
                                <a href="https://wa.me/{{ $waHp2 }}" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1.5 font-bold text-cyan-400 hover:text-cyan-300 font-mono hover:underline transition"
                                   title="Klik untuk Chat via WhatsApp">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                    <span>{{ $customer->nomor_hp_2 }}</span>
                                </a>
                            @else
                                <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono">-</span>
                            @endif
                        </div>
                        <div class="h-0.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-400 w-2/3"></div>
                        </div>
                    </div>

                    <!-- 4. Email (Klik Langsung ke Mailto) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Email</span>
                            @if($customer->email && $customer->email != '-')
                                <a href="mailto:{{ $customer->email }}" 
                                   class="font-semibold text-cyan-400 hover:text-cyan-300 hover:underline truncate max-w-[200px]"
                                   title="Klik untuk kirim Email">
                                    {{ $customer->email }}
                                </a>
                            @else
                                <span class="font-semibold text-slate-800 dark:text-slate-200">-</span>
                            @endif
                        </div>
                        <div class="h-0.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-400 w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Address Sections -->
                <div class="space-y-4 pt-2 text-xs border-t border-slate-200 dark:border-slate-800">
                    
                    <!-- ALAMAT KTP -->
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                            ALAMAT KTP
                        </span>
                        <p class="text-slate-700 dark:text-slate-300 uppercase leading-relaxed font-medium">
                            {{ trim(str_replace(['Â', 'â', '€', '™'], '', (string)($customer->alamat_k ?: ($customer->alamat_ktp ?: '-')))) }}
                        </p>
                    </div>

                    <!-- ALAMAT PEMASANGAN -->
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                            ALAMAT PEMASANGAN
                        </span>
                        <p class="text-slate-700 dark:text-slate-300 uppercase leading-relaxed font-medium">
                            {{ trim(str_replace(['Â', 'â', '€', '™'], '', (string)($customer->alamat_p ?: ($customer->alamat_pasang ?: '-')))) }}
                        </p>
                    </div>

                    <!-- LOKASI / KOORDINAT / MAPS -->
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            LOKASI
                        </span>
                        <div class="space-y-1 font-mono text-slate-700 dark:text-slate-300 text-xs">
                            @if($cleanLonLat)
                                <div>{{ $cleanLonLat }}</div>
                            @endif
                            @if($mapsUrl)
                                <div class="pt-0.5">
                                    <a href="{{ $mapsUrl }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1.5 text-cyan-400 hover:text-cyan-300 hover:underline break-all"
                                       title="Buka Lokasi di Google Maps">
                                        <svg class="w-3.5 h-3.5 text-rose-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                        </svg>
                                        <span class="truncate max-w-[250px]">{{ $customer->loc_maps ?: 'Buka di Google Maps' }}</span>
                                    </a>
                                </div>
                            @else
                                <div class="text-slate-500">-</div>
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- =============================================================== -->
        <!-- RIGHT COLUMN: TABBED DETAIL PANEL                              -->
        <!-- =============================================================== -->
        <div class="lg:col-span-8 space-y-4">
            
            <!-- Top Tab Switcher Bar & Button Kembali -->
            <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-3 sm:p-4 shadow-xl shadow-black/10 dark:shadow-black/20">
                
                <!-- Segmented Tabs -->
                <div class="flex flex-wrap items-center gap-1.5">
                    
                    <!-- 1. Tab Log -->
                    <button type="button" 
                            @click="activeTab = 'log'"
                            :class="activeTab === 'log' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Log
                    </button>

                    <!-- 2. Tab Arsip -->
                    <button type="button" 
                            @click="activeTab = 'arsip'"
                            :class="activeTab === 'arsip' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Arsip
                    </button>

                    <!-- 3. Tab Layanan -->
                    <button type="button" 
                            @click="activeTab = 'layanan'"
                            :class="activeTab === 'layanan' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Layanan
                    </button>

                    <!-- 4. Tab Suspend -->
                    <button type="button" 
                            @click="activeTab = 'suspend'"
                            :class="activeTab === 'suspend' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Suspend
                    </button>

                    <!-- 5. Tab Tagihan -->
                    <button type="button" 
                            @click="activeTab = 'tagihan'"
                            :class="activeTab === 'tagihan' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Tagihan
                    </button>

                    <!-- 6. Tab Pengaduan -->
                    <button type="button" 
                            @click="activeTab = 'pengaduan'"
                            :class="activeTab === 'pengaduan' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Pengaduan
                    </button>

                    <!-- 7. Tab Perangkat dsb. -->
                    <button type="button" 
                            @click="activeTab = 'perangkat'"
                            :class="activeTab === 'perangkat' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs transition duration-150 cursor-pointer">
                        Perangkat dsb.
                    </button>
                </div>

                <!-- Tombol Kembali -->
                <div>
                    <a href="{{ url()->previous() ?: route('teknik.pelanggan') }}" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition duration-150 cursor-pointer">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>

            <!-- Tab Content Container -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-black/10 dark:shadow-black/20 overflow-hidden">
                
                <!-- ======================================================= -->
                <!-- 1. TAB CONTENT: LOG (MATCHING SCREENSHOT)              -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'log'" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                <th class="py-3.5 px-5">Status Order</th>
                                <th class="py-3.5 px-5">Keterangan</th>
                                <th class="py-3.5 px-5 min-w-[160px]">Tanggal Update</th>
                                <th class="py-3.5 px-5 min-w-[140px]">User Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                            @forelse($logs as $log)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    
                                    <!-- 1. Status Order -->
                                    <td class="py-4 px-5 align-top space-y-1.5">
                                        <div>
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide
                                                @if(in_array($log->status_reg, ['20']))
                                                    bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30
                                                @elseif(in_array($log->status_reg, ['17', '17.1']))
                                                    bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30
                                                @elseif(in_array($log->status_reg, ['13', '13.1']))
                                                    bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30
                                                @elseif(in_array($log->status_reg, ['23', '23.1']))
                                                    bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30
                                                @else
                                                    bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                                                @endif">
                                                {{ $log->desc_registrasi ?: 'Status #' . $log->status_reg }}
                                            </span>
                                        </div>
                                        @if($log->date_schedule || $log->time_schedule)
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                                {{ $log->date_schedule ? \Carbon\Carbon::parse($log->date_schedule)->translatedFormat('d F Y') : '' }}
                                                {{ $log->time_schedule ?: '' }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 2. Keterangan -->
                                    <td class="py-4 px-5 align-top space-y-1 text-slate-700 dark:text-slate-300">
                                        <div class="font-semibold text-slate-800 dark:text-slate-100 uppercase text-[11px]">
                                            {{ $log->note_schedule ? 'POSTING ' . strtoupper($log->desc_registrasi) : ($log->kat_log ? 'LOG AKTIVITAS #' . $log->kat_log : 'UPDATE STATUS') }}
                                        </div>
                                        @if($log->note_schedule)
                                            <div class="text-[11px] text-slate-600 dark:text-slate-400">
                                                Catatan : <span class="text-slate-800 dark:text-slate-200">{{ $log->note_schedule }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 3. Tanggal Update -->
                                    <td class="py-4 px-5 align-top text-slate-600 dark:text-slate-400 font-medium text-[11px]">
                                        {{ \Carbon\Carbon::parse($log->date_create)->translatedFormat('d F Y H:i') }} WIB
                                    </td>

                                    <!-- 4. User Update -->
                                    <td class="py-4 px-5 align-top font-semibold text-slate-800 dark:text-slate-200 uppercase text-[11px]">
                                        {{ $log->user_create ?: 'SYSTEM' }}
                                    </td>
                                </tr>
                            @empty
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-4 px-5 align-top space-y-1.5">
                                        <div>
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30">
                                                {{ $customer->desc_registrasi ?: 'Registrasi' }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                            {{ $customer->date_create ? \Carbon\Carbon::parse($customer->date_create)->translatedFormat('d F Y') : '-' }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-5 align-top space-y-1 text-slate-700 dark:text-slate-300">
                                        <div class="font-semibold text-slate-800 dark:text-slate-100 uppercase text-[11px]">
                                            REGISTRASI PELANGGAN BARU
                                        </div>
                                        <div class="text-[11px] text-slate-600 dark:text-slate-400">
                                            Sales : {{ $customer->nama_sales ?: '-' }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-5 align-top text-slate-600 dark:text-slate-400 font-medium text-[11px]">
                                        {{ $customer->date_create ? \Carbon\Carbon::parse($customer->date_create)->translatedFormat('d F Y H:i') . ' WIB' : '-' }}
                                    </td>
                                    <td class="py-4 px-5 align-top font-semibold text-slate-800 dark:text-slate-200 uppercase text-[11px]">
                                        {{ $customer->user_create ?: 'DRAFFTER' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ======================================================= -->
                <!-- 2. TAB CONTENT: ARSIP DOKUMEN & FOTO (EXACT MATCH)     -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'arsip'" x-cloak class="p-6 space-y-8">
                    
                    <!-- ================= SECTION 1: FOTO ================= -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Dokumen Persyaratan (Foto)
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Foto identitas pelanggan, denah lokasi, dan dokumentasi tempat tinggal.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            
                            <!-- 1. KTP.jpeg -->
                            @php
                                $ktpFile = $customer->foto_ktp;
                                $ktpUrl = $ktpFile ? asset('uploads/registrasi/' . $ktpFile) : null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $ktpFile ? 'border-blue-200 bg-blue-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $ktpFile ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $ktpFile ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 border border-blue-200">JPG</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.364a4.125 4.125 0 0 0-6.338 0 .375.375 0 0 0 .28.611h5.778a.375.375 0 0 0 .28-.611Z" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Foto KTP</span>
                                    <span class="block text-[10px] text-slate-500">KTP.jpeg</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($ktpUrl)
                                        <a href="{{ $ktpUrl }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh KTP">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('foto_ktp', 'Foto KTP')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Foto KTP">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('foto_ktp', 'Foto KTP')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Foto KTP">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Foto KTP</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- 2. Rumah.jpeg -->
                            @php
                                $rumahFile = $customer->foto_rumah;
                                $rumahUrl = $rumahFile ? asset('uploads/registrasi/' . $rumahFile) : null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $rumahFile ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $rumahFile ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $rumahFile ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200">JPG</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Foto Rumah / Lokasi</span>
                                    <span class="block text-[10px] text-slate-500">Rumah.jpeg</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($rumahUrl)
                                        <a href="{{ $rumahUrl }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh Foto Rumah">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('foto_rumah', 'Foto Rumah')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Foto Rumah">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('foto_rumah', 'Foto Rumah')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Foto Rumah">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Foto Rumah</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- 3. PETA.jpeg -->
                            @php
                                $petaFile = $customer->foto_peta;
                                $petaUrl = $petaFile ? asset('uploads/registrasi/' . $petaFile) : null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $petaFile ? 'border-teal-200 bg-teal-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $petaFile ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $petaFile ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-teal-100 text-teal-700 border border-teal-200">JPG</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-teal-100 border border-teal-200 flex items-center justify-center text-teal-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Denah Peta Lokasi</span>
                                    <span class="block text-[10px] text-slate-500">PETA.jpeg</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($petaUrl)
                                        <a href="{{ $petaUrl }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh Denah Peta">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('foto_peta', 'Foto Denah Peta')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Foto Denah Peta">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('foto_peta', 'Foto Denah Peta')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Foto Peta">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Denah Peta</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ================= SECTION 2: SCAN DOKUMEN (UPLOAD MASTER DOKUMEN DILEGALISIR) ================= -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                    Scan Dokumen Legalisir
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Dokumen fisik/digital resmi yang sudah ditandatangani dan dilegalisir basah.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                            
                            <!-- 1. Berlangganan -->
                            @php
                                $docLangganan = $customer->doc_berlangganan ?? null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $docLangganan ? 'border-blue-200 bg-blue-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $docLangganan ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $docLangganan ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 border border-blue-200">SCAN</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Form Berlangganan</span>
                                    <span class="block text-[10px] text-slate-500">Scan Legalisir</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($docLangganan)
                                        <a href="{{ asset('uploads/registrasi/' . $docLangganan) }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('doc_berlangganan', 'Scan Dokumen Berlangganan Legalisir')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Berkas">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('doc_berlangganan', 'Scan Dokumen Berlangganan Legalisir')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Scan Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Scan</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- 2. Survey -->
                            @php
                                $docSurvey = $customer->doc_survey ?? null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $docSurvey ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $docSurvey ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $docSurvey ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200">SCAN</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Surat Tugas Survey</span>
                                    <span class="block text-[10px] text-slate-500">Scan Legalisir</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($docSurvey)
                                        <a href="{{ asset('uploads/registrasi/' . $docSurvey) }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('doc_survey', 'Scan Dokumen Survey Legalisir')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Berkas">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('doc_survey', 'Scan Dokumen Survey Legalisir')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Scan Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Scan</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- 3. Instalasi -->
                            @php
                                $docInstalasi = $customer->doc_instalasi ?? null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $docInstalasi ? 'border-teal-200 bg-teal-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $docInstalasi ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $docInstalasi ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-teal-100 text-teal-700 border border-teal-200">SCAN</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-teal-100 border border-teal-200 flex items-center justify-center text-teal-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Surat Instalasi</span>
                                    <span class="block text-[10px] text-slate-500">Scan Legalisir</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($docInstalasi)
                                        <a href="{{ asset('uploads/registrasi/' . $docInstalasi) }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('doc_instalasi', 'Scan Dokumen Instalasi Legalisir')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Berkas">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('doc_instalasi', 'Scan Dokumen Instalasi Legalisir')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Scan Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Scan</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- 4. Aktivasi -->
                            @php
                                $docAktivasi = $customer->doc_aktivasi ?? null;
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $docAktivasi ? 'border-purple-200 bg-purple-50/50' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-md transition flex flex-col items-center justify-between text-center min-h-[175px] group">
                                <div class="w-full flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $docAktivasi ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $docAktivasi ? 'Tersedia' : 'Belum Ada' }}
                                    </span>
                                    <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 border border-purple-200">SCAN</span>
                                </div>
                                
                                <div class="w-11 h-11 rounded-xl bg-purple-100 border border-purple-200 flex items-center justify-center text-purple-600 my-1 group-hover:scale-105 transition">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>

                                <div class="my-1 space-y-0.5">
                                    <span class="block text-xs font-bold text-slate-900">Berita Acara Aktivasi</span>
                                    <span class="block text-[10px] text-slate-500">Scan Legalisir</span>
                                </div>

                                <div class="w-full pt-2 border-t border-slate-100 flex items-center justify-center gap-2 mt-1">
                                    @if($docAktivasi)
                                        <a href="{{ asset('uploads/registrasi/' . $docAktivasi) }}" download target="_blank" class="flex-1 py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-[10px] font-bold transition flex items-center justify-center gap-1 border border-blue-200" title="Unduh Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        <button type="button" @click="openUploadModal('doc_aktivasi', 'Scan Dokumen Aktivasi Legalisir')" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center gap-1 border border-slate-200" title="Ganti Berkas">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>Ganti</span>
                                        </button>
                                    @else
                                        <button type="button" @click="openUploadModal('doc_aktivasi', 'Scan Dokumen Aktivasi Legalisir')" class="w-full py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition flex items-center justify-center gap-1 border border-slate-200" title="Upload Scan Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            <span>Upload Scan</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ================= SECTION 3: MASTER DOKUMEN (DOCX TEMPLATES) ================= -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                    Master Dokumen
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Dokumen resmi otomatis per-user siap cetak & ekspor ke format PDF atau Word (.doc).
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            
                            @php
                                $masterTemplates = [
                                    [
                                        'name' => 'langganan.docx', 
                                        'label' => 'langganan.docx',
                                        'desc' => 'Form Berlangganan',
                                        'url' => route('teknik.dokumen.langganan', $customer->nomor_internet),
                                        'active' => true,
                                    ],
                                    [
                                        'name' => 'Survey.docx', 
                                        'label' => 'Survey.docx', 
                                        'desc' => 'Surat Tugas Survey', 
                                        'url' => route('teknik.dokumen.survey', $customer->nomor_internet), 
                                        'active' => true,
                                    ],
                                    [
                                        'name' => 'instalasi.docx', 
                                        'label' => 'instalasi.docx', 
                                        'desc' => 'Surat Tugas Instalasi', 
                                        'url' => route('teknik.dokumen.instalasi', $customer->nomor_internet), 
                                        'active' => true,
                                    ],
                                    ['name' => 'aktivasi.docx', 'label' => 'aktivasi.docx', 'desc' => 'Form Aktivasi', 'url' => 'javascript:void(0)', 'active' => false],
                                    ['name' => 'terminasi.docx', 'label' => 'terminasi.docx', 'desc' => 'Form Terminasi', 'url' => 'javascript:void(0)', 'active' => false],
                                    ['name' => 'ubah_layanan.docx', 'label' => 'ubah_layanan.docx', 'desc' => 'Form Ubah Layanan', 'url' => 'javascript:void(0)', 'active' => false],
                                    ['name' => 'mapping.docx', 'label' => 'mapping.docx', 'desc' => 'Form Mapping', 'url' => 'javascript:void(0)', 'active' => false],
                                ];
                            @endphp

                            @foreach($masterTemplates as $tmpl)
                                <div class="p-4 rounded-2xl border {{ $tmpl['active'] ? 'border-blue-200 bg-blue-50/40 hover:border-blue-300 shadow-xs' : 'border-slate-200 bg-white shadow-xs' }} transition flex flex-col items-center justify-between text-center min-h-[210px] group">
                                    
                                    <!-- Top Status & Format Badges -->
                                    <div class="w-full flex items-center justify-between gap-2 mb-2">
                                        @if($tmpl['active'])
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span>Siap Cetak</span>
                                            </span>
                                            <span class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 shrink-0">DOCX</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                                                Template
                                            </span>
                                            <span class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 border border-slate-200 shrink-0">DOCX</span>
                                        @endif
                                    </div>

                                    <!-- Clean Document Icon -->
                                    <div class="my-2">
                                        @if($tmpl['active'])
                                            <div class="w-12 h-12 rounded-xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-600 shadow-xs group-hover:scale-105 group-hover:bg-blue-200/60 transition duration-200">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Label & Desc -->
                                    <div class="my-1 space-y-0.5 w-full px-1">
                                        <span class="block text-xs font-bold text-slate-900 truncate" title="{{ $tmpl['desc'] }}">
                                            {{ $tmpl['desc'] }}
                                        </span>
                                        <span class="block text-[11px] text-slate-500 font-mono truncate" title="{{ $tmpl['label'] }}">
                                            {{ $tmpl['label'] }}
                                        </span>
                                    </div>

                                    <!-- Bottom Action Buttons (Clean & Structured) -->
                                    <div class="w-full pt-3 border-t border-slate-100 mt-2">
                                        @if($tmpl['active'])
                                            <div class="space-y-1.5 w-full">
                                                <!-- Primary View / Print Button -->
                                                <a href="{{ $tmpl['url'] }}" target="_blank" class="w-full py-2 px-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow-xs" title="Buka & Cetak Dokumen">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                                    <span>Buka / Cetak</span>
                                                </a>

                                                <!-- Secondary Format Download Buttons -->
                                                <div class="grid grid-cols-2 gap-1.5 w-full">
                                                    <a href="{{ $tmpl['url'] }}?download=pdf" target="_blank" class="py-1.5 px-2 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white text-[11px] font-semibold transition flex items-center justify-center gap-1 border border-emerald-200" title="Download PDF">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                                        <span>PDF</span>
                                                    </a>
                                                    <a href="{{ $tmpl['url'] }}?download=word" target="_blank" class="py-1.5 px-2 rounded-lg bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white text-[11px] font-semibold transition flex items-center justify-center gap-1 border border-indigo-200" title="Download Word (.doc)">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                                        <span>Word</span>
                                                    </a>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-full py-3 flex items-center justify-center">
                                                <span class="text-[11px] text-slate-500 font-medium bg-slate-100 border border-slate-200 px-3 py-1 rounded-lg">
                                                    Draft Master
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            @endforeach

                        </div>
                    </div>

                </div>

                <!-- ======================================================= -->
                <!-- 3. TAB CONTENT: LAYANAN                                -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'layanan'" x-cloak class="p-6 space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-2">
                            <span class="text-slate-500 font-semibold uppercase text-[10px]">Paket & Bandwidth</span>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ $customer->nama_kategori_bandwith ?: $customer->alias_nama_kategori }}
                                @if($customer->nominal_bandwith)
                                    <span class="text-blue-500">({{ $customer->nominal_bandwith }} Mbps)</span>
                                @endif
                            </div>
                            <div class="text-slate-600 dark:text-slate-400">
                                Harga Bulanan: <strong class="text-slate-800 dark:text-slate-200 font-mono">Rp {{ number_format((float) ($customer->harga_bandwith ?? 0), 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-2">
                            <span class="text-slate-500 font-semibold uppercase text-[10px]">Group & Akses</span>
                            <div class="text-sm font-bold text-slate-900 dark:text-white uppercase">
                                {{ $customer->group_layanan ?: 'MEDIANET' }}
                            </div>
                            <div class="text-slate-600 dark:text-slate-400">
                                POP: <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $customer->nama_pop ?: ($customer->kode_pop ?: '-') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 4. TAB CONTENT: SUSPEND                                -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'suspend'" x-cloak class="p-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        Riwayat Suspend Layanan
                    </h3>

                    @if($suspendRecords->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-950/60 text-slate-700 dark:text-slate-300">
                                        <th class="py-2.5 px-4">Tanggal Suspend</th>
                                        <th class="py-2.5 px-4">Status</th>
                                        <th class="py-2.5 px-4">Alasan</th>
                                        <th class="py-2.5 px-4">User</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @foreach($suspendRecords as $s)
                                        <tr>
                                            <td class="py-3 px-4">{{ $s->date_create }}</td>
                                            <td class="py-3 px-4">{{ $s->status_suspend }}</td>
                                            <td class="py-3 px-4">{{ $s->desc_suspend ?? '-' }}</td>
                                            <td class="py-3 px-4">{{ $s->user_create }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-slate-500">Tidak ada riwayat suspend untuk pelanggan ini.</p>
                    @endif
                </div>

                <!-- ======================================================= -->
                <!-- 5. TAB CONTENT: TAGIHAN / BILLINGS                     -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'tagihan'" x-cloak class="p-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        Daftar Tagihan & Pembayaran
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-950/60 text-slate-700 dark:text-slate-300">
                                    <th class="py-2.5 px-4">Kode Billing</th>
                                    <th class="py-2.5 px-4">Jenis</th>
                                    <th class="py-2.5 px-4">Total</th>
                                    <th class="py-2.5 px-4">Status</th>
                                    <th class="py-2.5 px-4">Metode</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                @forelse($billingReg as $b)
                                    <tr>
                                        <td class="py-3 px-4 font-mono font-bold">{{ $b->kode_billing_registrasi }}</td>
                                        <td class="py-3 px-4">Registrasi</td>
                                        <td class="py-3 px-4 font-mono font-bold">Rp {{ number_format((float) ($b->total_reg ?? 0), 0, ',', '.') }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $b->status_bill_reg == '14' ? 'bg-emerald-500/15 text-emerald-500' : 'bg-amber-500/15 text-amber-500' }}">
                                                {{ $b->status_bill_reg == '14' ? 'LUNAS' : 'DRAFT' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">{{ $b->merchant_type ?: 'Midtrans' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-slate-500">Tidak ada data tagihan registrasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 6. TAB CONTENT: PENGADUAN                              -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'pengaduan'" x-cloak class="p-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        Riwayat Pengaduan & Tiket Gangguan
                    </h3>

                    @if($tickets->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-950/60 text-slate-700 dark:text-slate-300">
                                        <th class="py-2.5 px-4">No Tiket</th>
                                        <th class="py-2.5 px-4">Keluhan</th>
                                        <th class="py-2.5 px-4">Status</th>
                                        <th class="py-2.5 px-4">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @foreach($tickets as $t)
                                        <tr>
                                            <td class="py-3 px-4 font-mono font-bold">{{ $t->tiket ?? '-' }}</td>
                                            <td class="py-3 px-4">{{ $t->keluhan ?? '-' }}</td>
                                            <td class="py-3 px-4">{{ $t->status ?? '-' }}</td>
                                            <td class="py-3 px-4">{{ $t->date_create ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-slate-500">Tidak ada riwayat pengaduan tiket gangguan.</p>
                    @endif
                </div>

                <!-- ======================================================= -->
                <!-- 7. TAB CONTENT: PERANGKAT DSB. (MATCHING SCREENSHOT)   -->
                <!-- ======================================================= -->
                <div x-show="activeTab === 'perangkat'" x-cloak class="p-6 space-y-6">
                    
                    <!-- Header with Title & [Tambah] Button -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-[#1e293b] dark:text-white tracking-tight">
                            Perangkat .dsb
                        </h2>

                        <button type="button" 
                                @click="tambahPerangkatModalOpen = true"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-md shadow-blue-500/20 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Tambah</span>
                        </button>
                    </div>

                    <!-- Main Grid: Left Config Column & Right Perangkat Table Column -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        
                        <!-- Left Column: Config Cards (ID PPOE, POP/ODN, Media Akses, Index OLT, Catatan) -->
                        <div class="lg:col-span-5 space-y-6 text-xs text-slate-700 dark:text-slate-300">
                            
                            <!-- 1. ID PPOE (with small [Ubah] button) -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                        ID PPOE
                                    </h4>
                                    <button type="button" 
                                            @click="openPppoeModal()"
                                            class="px-3 py-1 rounded-lg bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                                        Ubah
                                    </button>
                                </div>
                                <ul class="space-y-1 text-slate-600 dark:text-slate-400 font-sans">
                                    <li>&mdash; Username : <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $customer->ont_us ?: $customer->nomor_internet }}</span></li>
                                    <li>&mdash; Password : <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $customer->ont_ps ?: '-' }}</span></li>
                                </ul>
                            </div>

                            <!-- 2. POP/ODN -->
                            <div class="space-y-1.5">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    POP/ODN
                                </h4>
                                <ul class="space-y-1 text-slate-600 dark:text-slate-400 font-sans">
                                    <li>&mdash; Nama : <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $customer->nama_pop ?: '-' }}</span></li>
                                    <li>&mdash; Desc : <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $customer->desc_pop ?: '-' }}</span></li>
                                </ul>
                            </div>

                            <!-- 3. Media Akses -->
                            <div class="space-y-1.5">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Media Akses
                                </h4>
                                <ul class="space-y-1 text-slate-600 dark:text-slate-400 font-sans">
                                    <li>&mdash; Nama : <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $customer->media_akses ?: 'FTTH' }}</span></li>
                                    @if(!empty($customer->olt ?? null))
                                        @php
                                            $oltObj = isset($olts) ? $olts->firstWhere('kode_olt', $customer->olt) : null;
                                            $oltName = $oltObj ? $oltObj->name_olt : $customer->olt;
                                        @endphp
                                        <li>&mdash; Server OLT : <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $oltName }} ({{ $customer->olt }})</span></li>
                                    @endif
                                </ul>
                            </div>

                            <!-- 4. Index OLT (Sembunyikan jika Media Akses PTP FO) -->
                            @if(($customer->media_akses ?? '') !== 'PTP FO')
                            <div class="space-y-1.5">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Index OLT
                                </h4>
                                <ul class="space-y-1 text-slate-600 dark:text-slate-400 font-sans">
                                    <li>&mdash; <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $customer->index_olt ?: '-' }}</span></li>
                                </ul>
                            </div>
                            @endif

                            <!-- 5. SN / Serial Nomor Modem -->
                            <div class="space-y-1.5">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    SN / Serial Nomor Modem
                                </h4>
                                <ul class="space-y-1 text-slate-600 dark:text-slate-400 font-sans">
                                    <li>&mdash; <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $instalasi->aktivasi_note_finish ?? ($instalasi->aktivasi_note ?? ($customer->note_request ?? '-')) }}</span></li>
                                </ul>
                            </div>

                        </div>

                        <!-- Right Column: Table of Perangkat & Material -->
                        <div class="lg:col-span-7 overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                        <th class="py-3 px-3 font-bold">Nama Perangkat</th>
                                        <th class="py-3 px-3 font-bold">Quantity</th>
                                        <th class="py-3 px-3 font-bold">Status</th>
                                        <th class="py-3 px-3 font-bold text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    @forelse($perangkats as $p)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                            
                                            <!-- Nama Perangkat & Kode / Model sub-badge -->
                                            <td class="py-3 px-3 align-middle">
                                                <div class="font-bold text-slate-800 dark:text-slate-200 uppercase text-xs">
                                                    {{ $p->nama_jns_barang ?: ($p->nama_barang ?: 'PERANGKAT') }}
                                                </div>
                                                <div class="mt-0.5">
                                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-mono text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 font-semibold">
                                                        {{ $p->kode_barang }}{{ $p->nama_barang ? ', ' . $p->nama_barang : '' }}{{ $p->tipe_barang ? ' ' . $p->tipe_barang : '' }}
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Quantity & Satuan -->
                                            <td class="py-3 px-3 align-middle font-semibold text-slate-700 dark:text-slate-300 uppercase text-xs">
                                                {{ $p->jumlah_barang }} {{ $p->satuan ?: 'UNIT' }}
                                            </td>

                                            <!-- Status / Petugas Pemasangan -->
                                            <td class="py-3 px-3 align-middle text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase">
                                                {{ $p->user_create ?: ($instalasi->aktivasi_team ?? 'NUNU NUGRAHA') }}
                                            </td>

                                            <!-- Action: Hapus -->
                                            <td class="py-3 px-3 align-middle text-center">
                                                <form action="{{ route('teknik.pelanggan.perangkat.delete', [$customer->nomor_internet, $p->kode_inst_barang]) }}" 
                                                      method="POST" 
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus perangkat ini dari data pelanggan?');">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="inline-flex items-center gap-1 text-rose-500 hover:text-rose-700 text-xs font-semibold transition cursor-pointer"
                                                            title="Hapus Perangkat">
                                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                        <span>Hapus</span>
                                                    </button>
                                                </form>
                                            </td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                                Belum ada data perangkat yang terpasang untuk pelanggan ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- =================================================================== -->
    <!-- MODAL FORM UPLOAD BERKAS SCAN DOKUMEN LEGALISIR                     -->
    <!-- =================================================================== -->
    <div x-show="uploadModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="upload-modal-title" role="dialog" aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-md transition-opacity" 
             @click="uploadModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left shadow-2xl transition-all sm:my-8 w-full max-w-md">
                
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white tracking-wide" id="upload-modal-title" x-text="'Upload ' + uploadDocLabel"></h3>
                    </div>
                    
                    <button type="button" 
                            @click="uploadModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('teknik.pelanggan.upload-doc', $customer->nomor_internet) }}" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="tipe_dokumen" :value="uploadDocType">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Pilih Berkas Scan (PDF / JPG / PNG / DOCX) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" 
                               name="file_dokumen" 
                               required
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                               class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-2 bg-slate-50 dark:bg-slate-950">
                        <p class="text-[10px] text-slate-400 mt-1">Maksimal ukuran file: 10 MB</p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
                        <button type="button" 
                                @click="uploadModalOpen = false"
                                class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <span>Upload Dokumen</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL FORM UBAH DATA (MATCHING USER SCREENSHOT & NOC AKTIVASI)       -->
    <!-- =================================================================== -->
    <div x-show="pppoeModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" 
             @click="pppoeModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-[#f4f6f9] dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-2xl overflow-hidden my-auto flex flex-col"
                 @click.away="pppoeModalOpen = false">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shrink-0">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                        Form Ubah Data
                    </h3>
                    <button type="button" 
                            @click="pppoeModalOpen = false" 
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                        &times;
                    </button>
                </div>

                <!-- Form Content with White Inner Card matching Screenshot 2 -->
                <form action="{{ route('teknik.pelanggan.update-pppoe', $customer->nomor_internet) }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    
                    <div class="bg-white dark:bg-slate-950 p-6 rounded border border-slate-200 dark:border-slate-800 shadow-sm space-y-6 text-xs">
                        
                        <!-- Row 1: ID PPOE Username & ID PPOE Password -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    ID PPOE Username<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="ont_us" 
                                       x-model="modalOntUs" 
                                       required 
                                       class="w-full px-3 py-2 text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    ID PPOE Password<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="ont_ps" 
                                       x-model="modalOntPs" 
                                       required 
                                       class="w-full px-3 py-2 text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Row 2: POP/ODN & Media Akses -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    POP/ODN<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <select name="kode_pop" 
                                        x-model="modalKodePop" 
                                        required 
                                        class="w-full px-3 py-2 text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="">Pilih POP</option>
                                    @foreach($pops as $pop)
                                        <option value="{{ $pop->kode_pop }}">{{ $pop->nama_pop }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    Media Akses<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <select name="media_akses" 
                                        x-model="modalMediaAkses" 
                                        required 
                                        class="w-full px-3 py-2 text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="">Pilih Media Akses</option>
                                    <option value="FTTH">FTTH</option>
                                    <option value="FTTH MSN">FTTH MSN</option>
                                    <option value="PTP FO">PTP FO</option>
                                    <option value="WIRELESS">WIRELESS</option>
                                    <option value="GPON">GPON</option>
                                    <option value="CITRATEL">CITRATEL</option>
                                    <option value="MEDIANET">MEDIANET</option>
                                    </select>
                            </div>
                        </div>

                        <!-- Dropdown Pilihan 3 OLT Server (Muncul Jika Media Akses FTTH) -->
                        <div x-show="modalMediaAkses === 'FTTH' || modalMediaAkses === 'FTTH MSN'" 
                             x-transition
                             class="p-3 rounded bg-blue-50/60 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/70">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-[11px] text-blue-700 dark:text-blue-300">
                                    Pilihan Server OLT (FTTH) <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[10px] font-mono text-blue-500 font-semibold">3 OLT Tersedia</span>
                            </div>
                            <select name="olt" 
                                    x-model="modalOlt"
                                    :required="modalMediaAkses === 'FTTH' || modalMediaAkses === 'FTTH MSN'"
                                    class="w-full px-3 py-2 text-xs rounded border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">-- Pilih OLT Server --</option>
                                @if(isset($olts) && count($olts) > 0)
                                    @foreach($olts as $oltItem)
                                        <option value="{{ $oltItem->kode_olt }}">
                                            {{ $oltItem->name_olt }} ({{ $oltItem->kode_olt }})
                                        </option>
                                    @endforeach
                                @else
                                    <option value="O1">OLT KAYU AGUNG (O1)</option>
                                    <option value="O2">OLT BABAKAN TAROGONG (O2)</option>
                                    <option value="O3">OLT BBU (O3)</option>
                                @endif
                            </select>
                        </div>

                        <!-- Row 3: catatan & Index OLT (Dropdown matching NOC Aktivasi) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                            <div :class="modalMediaAkses === 'PTP FO' ? 'sm:col-span-2' : ''">
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    SN / Serial Nomor Modem<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="catatan" 
                                       x-model="modalCatatan" 
                                       placeholder="Masukkan nomor seri / SN modem ONT pelanggan..."
                                       class="w-full px-3 py-2 text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div x-show="modalMediaAkses !== 'PTP FO'" x-transition>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block font-medium text-slate-700 dark:text-slate-300">
                                        Index OLT<span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <span class="text-[10px] text-blue-500 font-mono font-bold" x-text="modalIndexOlt || 'Belum dipilih'"></span>
                                </div>
                                
                                <!-- Hidden input storing actual index_olt value -->
                                <input type="hidden" name="index_olt" :value="modalMediaAkses === 'PTP FO' ? '' : modalIndexOlt">

                                <!-- Dynamic GPON Port & ONU Slot selector matching NOC Aktivasi -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200 mb-1">
                                            Pilih Port GPON:
                                        </label>
                                        <select x-model="selectedGponPort" 
                                                @change="selectedOnuSlot = ''; updateIndexOlt();"
                                                class="w-full px-2 py-2 text-[11px] rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-mono">
                                            @if(isset($allPorts))
                                                @foreach($allPorts as $port)
                                                    @php $stat = $portStats[$port] ?? null; @endphp
                                                    <option value="{{ $port }}">
                                                        {{ $port }} ({{ $stat ? $stat['free'] : 128 }} Slot Sisa)
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200 mb-1">
                                            Pilih Index:
                                        </label>
                                        <select x-model="selectedOnuSlot" 
                                                @change="updateIndexOlt()" 
                                                class="w-full px-2 py-2 text-[11px] rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-mono">
                                            <option value="">-- Pilih Index (1..128) --</option>
                                            <template x-for="slot in (allSlots[selectedGponPort] || [])" :key="slot.key">
                                                <option :value="slot.num" 
                                                        :disabled="slot.is_occupied && modalIndexOlt !== slot.key"
                                                        :class="slot.is_occupied ? 'text-rose-500 bg-rose-50/10' : 'text-emerald-500 font-bold'"
                                                        :style="slot.is_occupied ? 'color: #f43f5e;' : 'color: #10b981; font-weight: 600;'"
                                                        x-text="(slot.is_occupied ? '🔴 ' : '🟢 ') + slot.key">
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Action Buttons matching Screenshot 2: [✖ Batal] (Cyan) & [💾 Simpan] (Blue) -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" 
                                @click="pppoeModalOpen = false" 
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded bg-[#00c0ef] hover:bg-[#00acd6] text-white text-xs font-bold shadow transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded bg-[#0073b7] hover:bg-[#005a91] text-white text-xs font-bold shadow transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V6.75A2.25 2.25 0 0 1 14.25 9H9.75A2.25 2.25 0 0 1 7.5 6.75V3.75m13.5 0v16.5A2.25 2.25 0 0 1 18.75 22.5H5.25A2.25 2.25 0 0 1 3 20.25V3.75A2.25 2.25 0 0 1 5.25 1.5h10.5a2.25 2.25 0 0 1 1.591.659l2.49 2.49A2.25 2.25 0 0 1 21 6.241Z" />
                            </svg>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL FORM TAMBAH PERANGKAT / MATERIAL PELANGGAN                    -->
    <!-- =================================================================== -->
    <div x-show="tambahPerangkatModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog">
        
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
             @click="tambahPerangkatModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col"
                 @click.away="tambahPerangkatModalOpen = false">
                
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80 shrink-0">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                        Tambah Perangkat / Material
                    </h3>
                    <button type="button" @click="tambahPerangkatModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                        &times;
                    </button>
                </div>

                <form action="{{ route('teknik.pelanggan.perangkat.store', $customer->nomor_internet) }}" method="POST" class="flex flex-col flex-1">
                    @csrf
                    <div class="p-5 space-y-4 text-xs">
                        
                        <!-- Pilihan Barang -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Pilih Jenis Barang / Perangkat <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <select name="kode_barang" 
                                    x-model="modalKodeBarang" 
                                    required 
                                    class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Barang / Perangkat --</option>
                                @foreach($masterBarang as $mb)
                                    <option value="{{ $mb->kode_barang }}">
                                        [{{ $mb->kode_barang }}] {{ $mb->nama_jns_barang }} - {{ $mb->nama_barang }} {{ $mb->tipe_barang }} ({{ $mb->satuan }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Jumlah Barang -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Jumlah (Qty) <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="number" 
                                   name="jumlah_barang" 
                                   x-model="modalJumlahBarang" 
                                   min="1" 
                                   required 
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Catatan / Note -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Catatan Pemasangan
                            </label>
                            <input type="text" 
                                   name="note_instalasi_barang" 
                                   x-model="modalNoteBarang" 
                                   placeholder="Contoh: Terpasang di ruang tamu"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                    </div>

                    <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                        <button type="button" 
                                @click="tambahPerangkatModalOpen = false" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#00bcd4] hover:bg-[#00acc1] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                            <span>Tutup</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
