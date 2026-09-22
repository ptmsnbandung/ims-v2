@extends('layouts.app')

@section('title', 'NOC Command Center - IMS Router')
@section('page_title', 'NOC Command Center')

@section('content')
<div class="space-y-6"
     x-data="{
         scheduleModalOpen: false,
         reportModalOpen: false,
         pppoeModalOpen: false,
         aktivasiModalOpen: false, // alias
         isModalReschedule: false,

         modalNomorInternet: '',
         modalNamaPelanggan: '',
         modalPaket: '',
         modalJadwalAktivasi: '{{ date('Y-m-d') }}',
         modalWaktuAktivasi: '09:00',
         modalTeamAktivasi: [],
         modalKodePop: '',
         modalMediaAkses: 'FTTH',
         modalCatatanSchedule: '',
         modalCatatanAktivasi: '',
         modalSnModem: '',
         fotoAktivasiPreview: null,

         // Index OLT Dynamic Selection
         selectedGponPort: 'gpon-onu_1/1/1',
         selectedOnuSlot: '',
         modalIndexOlt: '',
         modalOlt: 'O1',

         // Perangkat Added Items
         selectedBarang: '',
         selectedJumlah: 1,
         perangkatList: [],

         // PPPoE Secret & MikroTik Configuration
         pppoeUsername: '',
         pppoePassword: '',
         showPassword: false,
         selectedRouter: 'Router Core Utama (CCR1036)',
         localAddress: '10.10.10.1',
         pppProfile: 'PROFILE 30 Mbps',
         remoteAddress: '',

         allSlots: {{ json_encode($indexOltSlots ?? []) }},

         openScheduleModal(item) {
             this.isModalReschedule = ['19', '19.1'].includes(String(item.status_reg));
             this.modalNomorInternet = item.nomor_internet;
             this.modalNamaPelanggan = item.nama_pelanggan;
             this.modalPaket = (item.nama_kategori_bandwith || item.alias_nama_kategori || 'INTERNET') + ' (' + (item.nominal_bandwith || '') + ' Mbps)';
             this.modalJadwalAktivasi = item.aktivasi_date_start ? item.aktivasi_date_start.substring(0, 10) : '{{ date('Y-m-d') }}';
             this.modalWaktuAktivasi = item.aktivasi_time ? item.aktivasi_time.substring(0, 5) : '09:00';
             this.modalKodePop = item.kode_pop || '';
             this.modalMediaAkses = item.media_akses || 'FTTH';
             this.modalOlt = item.olt || (item.kode_olt || 'O1');
             this.modalCatatanSchedule = item.aktivasi_note || '';

             if (item.aktivasi_team) {
                 this.modalTeamAktivasi = item.aktivasi_team.split(',').map(s => s.trim());
             } else {
                 this.modalTeamAktivasi = [];
             }

             this.parseIndexOlt(item.index_olt);
             this.scheduleModalOpen = true;
         },

         openReportModal(item) {
             this.modalNomorInternet = item.nomor_internet;
             this.modalNamaPelanggan = item.nama_pelanggan;
             this.modalPaket = (item.nama_kategori_bandwith || item.alias_nama_kategori || 'INTERNET') + ' (' + (item.nominal_bandwith || '') + ' Mbps)';
             this.modalJadwalAktivasi = item.aktivasi_date_start ? item.aktivasi_date_start.substring(0, 10) : '{{ date('Y-m-d') }}';
             this.modalWaktuAktivasi = item.aktivasi_time ? item.aktivasi_time.substring(0, 5) : '09:00';
             this.modalKodePop = item.kode_pop || '';
             this.modalMediaAkses = item.media_akses || 'FTTH';
             this.modalOlt = item.olt || (item.kode_olt || 'O1');
             this.modalCatatanSchedule = item.aktivasi_note || ''; // Catatan dari schedule aktivasi otomatis masuk ke modal aktivasi
             this.modalCatatanAktivasi = item.aktivasi_note_finish || '';
             this.modalSnModem = (String(item.status_reg) === '20' && item.note_request) ? item.note_request : '';
             this.fotoAktivasiPreview = item.doc_aktivasi ? `/uploads/registrasi/${item.doc_aktivasi}` : null;

             if (item.aktivasi_team) {
                 this.modalTeamAktivasi = item.aktivasi_team.split(',').map(s => s.trim());
             } else {
                 this.modalTeamAktivasi = [];
             }

             this.parseIndexOlt(item.index_olt);
             this.perangkatList = [];
             this.reportModalOpen = true;
         },

         openAktivasiModal(item) {
             this.openReportModal(item);
         },

         openPppoeModal(item) {
             this.modalNomorInternet = item.nomor_internet;
             this.modalNamaPelanggan = item.nama_pelanggan;
             this.modalPaket = (item.nama_kategori_bandwith || item.alias_nama_kategori || 'INTERNET') + ' (' + (item.nominal_bandwith || '') + ' Mbps)';
             this.pppoeUsername = item.ont_us || item.nomor_internet;
             this.pppoePassword = item.ont_ps || (Math.floor(100000 + Math.random() * 900000).toString());
             this.showPassword = false;
             this.selectedRouter = 'Router Core Utama (CCR1036)';
             this.localAddress = '10.10.10.1';
             this.pppProfile = 'PROFILE ' + (item.nominal_bandwith ? item.nominal_bandwith + ' Mbps' : (item.nama_kategori_bandwith || '30 Mbps'));
             this.remoteAddress = '';
             this.pppoeModalOpen = true;
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
                 this.modalIndexOlt = '';
             }
         },

         updateIndexOlt() {
             if (this.selectedGponPort && this.selectedOnuSlot) {
                 this.modalIndexOlt = this.selectedGponPort + ':' + this.selectedOnuSlot;
             } else {
                 this.modalIndexOlt = '';
             }
         },

         barangsMap: {
             @foreach($barangs as $b)
                 '{{ $b->kode_barang }}': '{{ addslashes($b->nama_barang . " " . $b->tipe_barang) }}',
             @endforeach
         },

         addPerangkat() {
             if (!this.selectedBarang) return;
             let nama = this.barangsMap[this.selectedBarang] || this.selectedBarang;
             this.perangkatList.push({
                 kode_barang: this.selectedBarang,
                 nama: nama,
                 jumlah: this.selectedJumlah || 1
             });
             this.selectedBarang = '';
             this.selectedJumlah = 1;
         },

         removePerangkat(index) {
             this.perangkatList.splice(index, 1);
         }
     }">

    <!-- Top Hero Banner: Network Operations Center Live Monitoring (Oceanic Teal/Cyan Gradient) -->
    <div class="ims-banner relative overflow-hidden rounded-2xl p-6 sm:p-7 shadow-md"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 relative z-10">
            <div>
                <div class="flex flex-wrap items-center gap-2.5 mb-3">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[11px] font-bold bg-white text-slate-800 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-[#00a8b5]"></span>
                        <span>ID: &middot; NOC Command Center</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[11px] font-semibold bg-[#04333e]/85 text-emerald-300 border border-teal-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Core Network Stable</span>
                    </div>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight" style="color: #FFFFFF !important;">
                    Network Operations Center (NOC)
                </h2>
                <p class="text-xs sm:text-sm text-[#c6edf3] mt-1 max-w-2xl leading-relaxed" style="color: #C6EDF3 !important;">
                    Monitoring infrastruktur FTTH, OLT, utilisasi port ODP, aktivasi provisioning pelanggan, dan manajemen traffic jaringan real-time.
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('noc.aktivasi') }}" class="px-4 py-2 rounded-full bg-[#00b074] hover:bg-[#009b66] text-white text-xs font-bold flex items-center gap-2 shadow-md shadow-emerald-950/20 transition cursor-pointer" style="color: #FFFFFF !important;">
                    <svg class="w-4 h-4 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span>Antrean Aktivasi ({{ $antreanAktivasi }})</span>
                </a>
                <a href="{{ route('noc.olt') }}" class="px-4 py-2 rounded-full border border-white/50 bg-white/5 backdrop-blur-xs text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition" style="color: #FFFFFF !important;">
                    <span>Manajemen OLT</span>
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        
        <!-- 1. Total OLT -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-xl shadow-black/10 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total OLT Aktif</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalOlt }} <span class="text-xs font-normal text-slate-400">Node</span></div>
                <div class="text-[11px] text-blue-500 font-medium mt-1">GPON & EPON Core</div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                </svg>
            </div>
        </div>

        <!-- 2. Total ODP -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-xl shadow-black/10 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ODP Terpasang</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalOdp }} <span class="text-xs font-normal text-slate-400">Titik</span></div>
                <div class="text-[11px] text-emerald-500 font-medium mt-1">FTTH Distribution Box</div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                </svg>
            </div>
        </div>

        <!-- 3. Pelanggan Aktif Online -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-xl shadow-black/10 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan Aktif</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($pelangganAktif, 0, ',', '.') }}</div>
                <div class="text-[11px] text-cyan-500 font-medium mt-1">Koneksi Layanan Berjalan</div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- 4. Antrean Aktivasi -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-xl shadow-black/10 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Antrean Aktivasi</span>
                <div class="text-2xl font-black text-amber-500 mt-1">{{ $antreanAktivasi }} <span class="text-xs font-normal text-slate-400">Order</span></div>
                <div class="text-[11px] text-amber-400 font-medium mt-1">Perlu Provisioning NOC</div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>

    </div>

    <!-- Main 2-Column Section: Antrean Aktivasi & Master Infrastruktur Live -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column: Antrean Aktivasi Terbaru -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Antrean Aktivasi Layanan Terbaru
                </h3>
                <a href="{{ route('noc.aktivasi') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                <th class="py-3.5 px-4">No Internet / Nama</th>
                                <th class="py-3.5 px-4">Paket & Bandwidth</th>
                                <th class="py-3.5 px-4">Alamat Pemasangan</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-center">Aksi NOC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                            @forelse($recentAktivasi as $item)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3.5 px-4">
                                        <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                           class="font-bold text-blue-600 dark:text-blue-400 font-mono hover:underline">
                                            {{ $item->nomor_internet }}
                                        </a>
                                        <div class="font-semibold text-slate-800 dark:text-slate-200 uppercase mt-0.5">{{ $item->nama_pelanggan }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-slate-700 dark:text-slate-300">{{ $item->nama_kategori_bandwith ?: ($item->alias_nama_kategori ?: 'INTERNET') }}</div>
                                        <div class="text-[11px] text-blue-600 dark:text-blue-400 font-bold font-mono">{{ $item->nominal_bandwith ?: '10' }} Mbps</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 truncate max-w-[200px]">
                                        {{ $item->alamat_p ?: ($item->alamat_pasang ?: '-') }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if(in_array($item->status_reg, ['19', '19.1']))
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/15 dark:text-cyan-400 dark:border-cyan-500/30">
                                                TERJADWAL (#19)
                                            </span>
                                        @elseif(in_array($item->status_reg, ['18', '18.1']))
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30">
                                                SIAP JADWAL (#18)
                                            </span>
                                        @else
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-500/15 dark:text-slate-400 dark:border-slate-500/30">
                                                #{{ $item->status_reg }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        @if(in_array($item->status_reg, ['18', '18.1']))
                                            <div class="flex items-center justify-center">
                                                <button type="button" 
                                                        @click="openScheduleModal({{ json_encode($item) }})" 
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-[11px] font-bold shadow-md shadow-cyan-500/20 transition cursor-pointer"
                                                        title="Jadwalkan Aktivasi Layanan (NOC)">
                                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                    </svg>
                                                    <span>Schedule Aktivasi</span>
                                                </button>
                                            </div>
                                        @elseif(in_array($item->status_reg, ['19', '19.1']))
                                            <div class="flex flex-col items-center justify-center gap-1.5">
                                                <button type="button" 
                                                        @click="openReportModal({{ json_encode($item) }})" 
                                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer"
                                                        title="Lanjut ke Form Eksekusi Aktivasi Layanan (Otomatis Reboot OLT/MikroTik di Background)">
                                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                                    </svg>
                                                    <span>Aktivasi</span>
                                                </button>
                                                <button type="button" 
                                                        @click="openScheduleModal({{ json_encode($item) }})" 
                                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-semibold transition cursor-pointer border border-slate-200 dark:border-slate-700"
                                                        title="Reschedule / Jadwalkan Ulang jika aktivasi tertunda atau berhalangan">
                                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                    </svg>
                                                    <span>Reschedule</span>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                        Tidak ada antrean aktivasi yang pending.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: OLT & POP Network Status -->
        <div class="lg:col-span-5 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                Status OLT & POP Jaringan
            </h3>

            <!-- OLT Cards -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xl shadow-black/10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Daftar OLT Gateway</span>
                    <a href="{{ route('noc.olt') }}" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline">Kelola &rarr;</a>
                </div>

                <div class="space-y-2.5">
                    @forelse($olts as $olt)
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white font-mono">{{ $olt->name_olt }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Kode: {{ $olt->kode_olt }} &middot; Wilayah: {{ $olt->kode_w ?: 'Default' }}</div>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30">
                                    ONLINE
                                </span>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Kapasitas: {{ $olt->capacity_olt ?: 0 }} Port</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-3">Belum ada data OLT.</p>
                    @endforelse
                </div>

                <!-- POP Overview -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-2">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Point of Presence (POP)</span>
                        <a href="{{ route('noc.pop') }}" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline">Lihat Semua &rarr;</a>
                    </div>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($pops->take(6) as $pop)
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                📍 {{ $pop->nama_pop }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- MODAL: SCHEDULE AKTIVASI LAYANAN (PENJADWALAN NOC)                 -->
    <!-- =================================================================== -->
    <div x-show="scheduleModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
             @click="scheduleModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col"
                 @click.away="scheduleModalOpen = false">
                
                <!-- Modal Header with Cyan Accent -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-cyan-500/10 via-transparent to-transparent">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-cyan-500/15 text-cyan-600 dark:text-cyan-400">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-white" x-text="isModalReschedule ? 'Reschedule / Ubah Jadwal Aktivasi (NOC)' : 'Jadwalkan Aktivasi Layanan (NOC)'">
                            </h3>
                            <p class="text-[11px] text-slate-400" x-text="isModalReschedule ? 'Perbarui tanggal, waktu, atau tim aktivasi jika jadwal sebelumnya berhalangan / tertunda.' : 'Tetapkan tanggal, jam, tim NOC & node jaringan sebelum proses aktivasi layanan.'">
                            </p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="scheduleModalOpen = false" 
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                        &times;
                    </button>
                </div>

                <!-- Form Content -->
                <form :action="'{{ url('noc/aktivasi') }}/' + modalNomorInternet + '/schedule'" 
                      method="POST" 
                      class="p-6 space-y-4 text-xs">
                    @csrf

                    <!-- Customer Info Header Banner -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div class="space-y-0.5">
                            <span class="text-[10px] text-slate-400 uppercase font-mono">ID Pelanggan</span>
                            <div class="font-mono font-bold text-blue-500 text-sm" x-text="modalNomorInternet"></div>
                            <div class="font-semibold text-slate-800 dark:text-slate-200 uppercase" x-text="modalNamaPelanggan"></div>
                        </div>
                        <div class="text-right space-y-0.5">
                            <span class="text-[10px] text-slate-400 uppercase font-mono">Paket Berlangganan</span>
                            <div class="font-bold text-blue-600 dark:text-blue-400" x-text="modalPaket"></div>
                        </div>
                    </div>

                    <!-- 1. Jadwal & Waktu Aktivasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                <span x-text="isModalReschedule ? 'Tanggal Jadwal Baru' : 'Tanggal Rencana Aktivasi'"></span> <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   name="jadwal_aktivasi" 
                                   x-model="modalJadwalAktivasi" 
                                   required 
                                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Waktu / Jam Aktivasi <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="time" 
                                   name="waktu_aktivasi" 
                                   x-model="modalWaktuAktivasi" 
                                   required 
                                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs">
                        </div>
                    </div>

                    <!-- 2. Petugas / Tim NOC -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Petugas / Tim NOC Eksekutor <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 max-h-32 overflow-y-auto space-y-1.5">
                            @foreach($karyawans as $karyawan)
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700 dark:text-slate-300 hover:text-cyan-500">
                                    <input type="checkbox" 
                                           name="team_aktivasi[]" 
                                           value="{{ $karyawan->nama_karyawan }}" 
                                           x-model="modalTeamAktivasi" 
                                           class="rounded border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500">
                                    <span class="text-xs">{{ $karyawan->nama_karyawan }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 5. Catatan / Rencana Aktivasi -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            <span x-text="isModalReschedule ? 'Alasan Reschedule / Catatan Baru' : 'Catatan / Instruksi Rencana Aktivasi'"></span>
                        </label>
                        <textarea name="catatan" 
                                  x-model="modalCatatanSchedule" 
                                  rows="2" 
                                  placeholder="Catatan persiapan aktivasi atau alasan penundaan/reschedule..." 
                                  class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs"></textarea>
                    </div>

                    <!-- Modal Actions: Batal & Simpan Jadwal -->
                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" 
                                @click="scheduleModalOpen = false" 
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer">
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-md shadow-cyan-500/25 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <span x-text="isModalReschedule ? 'Simpan Reschedule Aktivasi' : 'Simpan Jadwal Aktivasi'"></span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: REPORT AKTIVASI LAYANAN (DILENGKAPI BUKTI FOTO & SN MODEM)   -->
    <!-- =================================================================== -->
    <div x-show="reportModalOpen || aktivasiModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
             @click="reportModalOpen = false; aktivasiModalOpen = false;"></div>
        
        <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
            <div class="relative w-full max-w-5xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden transition-all"
                 @click.away="reportModalOpen = false; aktivasiModalOpen = false;">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
                    <div class="space-y-0.5">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                            <span>Aktivasi Layanan An/</span>
                            <span class="text-blue-600 dark:text-blue-400 uppercase font-mono" x-text="modalNamaPelanggan"></span>
                        </h3>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            <span>Setelah data aktivasi disimpan, sistem otomatis mengeksekusi reboot OLT/MikroTik & beralih ke status Aktif (#20).</span>
                        </p>
                    </div>
                    <button type="button" 
                            @click="reportModalOpen = false; aktivasiModalOpen = false;" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-xl font-bold transition">
                        &times;
                    </button>
                </div>

                <form :action="'{{ url('/noc/aktivasi') }}/' + modalNomorInternet + '/report'" 
                      method="POST" 
                      enctype="multipart/form-data"
                      class="p-6 space-y-6 text-xs">
                    @csrf

                    <!-- Hidden input to submit full combined Index OLT string & Perangkat List -->
                    <input type="hidden" name="index_olt" :value="modalIndexOlt">
                    <input type="hidden" name="perangkat_json" :value="JSON.stringify(perangkatList)">

                    <!-- Info Catatan Schedule Aktivasi (Otomatis Masuk dari Tahap Schedule) -->
                    <template x-if="modalCatatanSchedule">
                        <div class="p-3.5 rounded-xl bg-cyan-50/70 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/70 text-xs space-y-1">
                            <div class="flex items-center gap-1 text-[11px] uppercase font-bold text-cyan-700 dark:text-cyan-400">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span>Catatan / Instruksi dari Schedule Aktivasi:</span>
                            </div>
                            <p class="text-slate-800 dark:text-slate-200 font-medium whitespace-pre-line pl-4.5" x-text="modalCatatanSchedule"></p>
                        </div>
                    </template>

                    <!-- 2-Column Grid Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                        
                        <!-- LEFT COLUMN: Form Konfigurasi Report Aktivasi -->
                        <div class="lg:col-span-6 space-y-4">
                            
                            <!-- 1. Tanggal Selesai & Waktu Aktivasi -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Tanggal Aktivasi <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <input type="date" 
                                           name="jadwal_aktivasi" 
                                           x-model="modalJadwalAktivasi" 
                                           required 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Waktu Aktivasi <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <input type="time" 
                                           name="waktu_aktivasi" 
                                           x-model="modalWaktuAktivasi" 
                                           required 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                                </div>
                            </div>

                            <!-- 2. Team Aktivasi (Checkbox Grid) -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Petugas / Team Aktivasi
                                </label>
                                <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-[11px] max-h-32 overflow-y-auto">
                                    @foreach($karyawans as $tech)
                                        <label class="flex items-center gap-2 cursor-pointer text-slate-700 dark:text-slate-300 hover:text-blue-500">
                                            <input type="checkbox" 
                                                   name="team_aktivasi[]" 
                                                   value="{{ $tech->nama_karyawan }}" 
                                                   :checked="modalTeamAktivasi.includes('{{ $tech->nama_karyawan }}')"
                                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                            <span class="truncate">{{ $tech->nama_karyawan }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- 3. POP/ODN & Media Akses -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        POP/ODN <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <select name="kode_pop" 
                                            x-model="modalKodePop" 
                                            required
                                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                                        <option value="">-- Pilih POP --</option>
                                        @foreach($pops as $pop)
                                            <option value="{{ $pop->kode_pop }}">{{ $pop->nama_pop }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Media Akses <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <select name="media_akses" 
                                            x-model="modalMediaAkses" 
                                            required
                                            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
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

                            <!-- 3b. Dropdown Pilihan 3 OLT Server (Muncul Jika Media Akses FTTH) -->
                            <div x-show="modalMediaAkses === 'FTTH' || modalMediaAkses === 'FTTH MSN'" 
                                 x-transition
                                 class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/70">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-[11px] font-bold text-blue-700 dark:text-blue-300">
                                        Pilih Server OLT (FTTH) <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-mono text-blue-500 font-semibold">{{ count($olts) }} OLT Tersedia</span>
                                </div>
                                <select name="olt" 
                                        x-model="modalOlt"
                                        :required="modalMediaAkses === 'FTTH' || modalMediaAkses === 'FTTH MSN'"
                                        class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-blue-300 dark:border-blue-700 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                                    @foreach($olts as $oltItem)
                                        <option value="{{ $oltItem->kode_olt }}">
                                            {{ $oltItem->name_olt ?? ($oltItem->nama_olt ?? $oltItem->kode_olt) }} ({{ $oltItem->kode_olt }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 4. Index OLT (Port Selector & Slot 1..128 Selector) - Hidden if PTP FO -->
                            <div x-show="modalMediaAkses !== 'PTP FO'" 
                                 x-transition
                                 class="space-y-2 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                        Index OLT <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <span class="text-[10px] text-blue-500 font-mono font-bold" x-text="modalIndexOlt || 'Belum dipilih'"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- Select GPON Port -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                            Pilih Port GPON:
                                        </label>
                                        <select x-model="selectedGponPort" 
                                                @change="selectedOnuSlot = ''; updateIndexOlt();"
                                                class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono text-[11px] font-semibold">
                                            @if(isset($allPorts))
                                                @foreach($allPorts as $port)
                                                    @php $stat = $portStats[$port] ?? null; @endphp
                                                    <option value="{{ $port }}">
                                                        {{ $port }} ({{ $stat ? ($stat['free'] ?? ($stat['available'] ?? 128)) : 128 }} Slot Sisa)
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <!-- Select Index (1..128) -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                            Pilih Index:
                                        </label>
                                        <select x-model="selectedOnuSlot" 
                                                @change="updateIndexOlt()" 
                                                class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono text-[11px] font-semibold">
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

                            <!-- 5. SN / Serial Nomor Modem -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    SN / Serial Nomor Modem ONT <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="sn_modem" 
                                       x-model="modalSnModem" 
                                       required
                                       placeholder="Contoh: ZTEGC1234567 atau HUAWEI1234..." 
                                       class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                            </div>

                            <!-- 6. Catatan Tambahan Eksekusi Aktivasi -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Catatan Eksekusi Aktivasi
                                </label>
                                <textarea name="catatan_aktivasi" 
                                          x-model="modalCatatanAktivasi" 
                                          rows="2" 
                                          placeholder="Catatan hasil aktivasi teknis atau redaman optik..." 
                                          class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs"></textarea>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN: Perangkat & Upload Foto Bukti Aktivasi -->
                        <div class="lg:col-span-6 space-y-4">
                            
                            <!-- Subheading with blue vertical accent bar -->
                            <div class="flex items-center gap-2">
                                <span class="w-1 h-5 rounded bg-blue-600 dark:bg-blue-500"></span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Perangkat/ Peralatan & Bukti Foto</span>
                            </div>

                            <!-- Dynamic Device / Item Adder Row -->
                            <div class="grid grid-cols-12 gap-2 items-end">
                                <div class="col-span-6">
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Perangkat</label>
                                    <select x-model="selectedBarang" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                                        <option value="">Pilih Perangkat</option>
                                        @foreach($barangs as $b)
                                            <option value="{{ $b->kode_barang }}">{{ $b->nama_barang }} {{ $b->tipe_barang }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Jumlah</label>
                                    <input type="number" min="1" x-model="selectedJumlah" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[11px] font-semibold text-transparent mb-1">action</label>
                                    <button type="button" 
                                            @click="addPerangkat()" 
                                            class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 rounded-xl bg-teal-500 hover:bg-teal-400 text-white font-bold text-xs shadow-md shadow-teal-500/20 transition cursor-pointer">
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Table of Added Perangkat Items -->
                            <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                            <th class="py-2 px-3">Barang</th>
                                            <th class="py-2 px-3 text-center w-20">Jumlah</th>
                                            <th class="py-2 px-3 text-center w-16">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                                        <template x-if="perangkatList.length === 0">
                                            <tr>
                                                <td colspan="3" class="py-4 text-center text-slate-400 text-xs">
                                                    Belum ada perangkat yang ditambahkan.
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-for="(item, index) in perangkatList" :key="index">
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-1.5 px-3 font-medium text-slate-800 dark:text-slate-200" x-text="item.nama"></td>
                                                <td class="py-1.5 px-3 text-center font-mono font-bold" x-text="item.jumlah"></td>
                                                <td class="py-1.5 px-3 text-center">
                                                    <button type="button" @click="removePerangkat(index)" class="text-rose-500 hover:text-rose-700 text-xs font-bold transition">
                                                        &times;
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Foto Bukti Aktivasi Upload Area -->
                            <div class="space-y-1.5 pt-2">
                                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                    Foto Bukti Aktivasi / Hasil Redaman ONT <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-4 text-center hover:border-blue-500 dark:hover:border-blue-400 transition cursor-pointer bg-slate-50/50 dark:bg-slate-950/40">
                                    <input type="file" 
                                           name="foto_aktivasi" 
                                           accept="image/*"
                                           @change="const file = $event.target.files[0]; if(file) { fotoAktivasiPreview = URL.createObjectURL(file); }"
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    
                                    <template x-if="!fotoAktivasiPreview">
                                        <div class="flex flex-col items-center justify-center py-4">
                                            <svg class="w-8 h-8 text-slate-400 dark:text-slate-500 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                            </svg>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Klik atau drag foto bukti aktivasi / redaman optik disini</p>
                                        </div>
                                    </template>

                                    <template x-if="fotoAktivasiPreview">
                                        <div class="relative rounded-xl overflow-hidden max-h-36 flex items-center justify-center">
                                            <img :src="fotoAktivasiPreview" class="object-cover max-h-32 rounded-lg shadow-sm">
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Modal Actions: Batal & Simpan Report Aktivasi -->
                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" 
                                @click="reportModalOpen = false; aktivasiModalOpen = false;" 
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer">
                            <span>Batal</span>
                        </button>

                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-500/25 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            <span>Simpan & Eksekusi Aktivasi</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: KONFIGURASI PPPOE & MULAI AKTIVASI (REBOOT OLT & MIKROTIK)   -->
    <!-- HIGH CONTRAST PURE-WHITE CLEAN THEME                                -->
    <!-- =================================================================== -->
    <div x-show="pppoeModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
             @click="pppoeModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
            <div class="relative w-full max-w-2xl bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden transition-all my-auto"
                 @click.away="pppoeModalOpen = false">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-wide flex items-center gap-1.5">
                        <span>Konfigurasi PPPoE & Mulai Aktivasi An/</span>
                        <span class="uppercase text-cyan-700 font-mono" x-text="modalNamaPelanggan"></span>
                    </h3>
                    <button type="button" 
                            @click="pppoeModalOpen = false" 
                            class="text-slate-400 hover:text-slate-700 text-2xl font-bold p-1 leading-none transition cursor-pointer">
                        &times;
                    </button>
                </div>

                <!-- Form Body -->
                <form :action="'{{ url('/noc/aktivasi') }}/' + modalNomorInternet + '/pppoe-activate'" 
                      method="POST" 
                      class="p-6 space-y-5 text-xs">
                    @csrf

                    <!-- 1. Card: Kredensial PPPoE Pelanggan -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-600"></span>
                            <span>Kredensial PPPoE Pelanggan</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- PPPoE Username -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-800 mb-1.5">
                                    PPPoE Username <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="pppoe_username" 
                                       x-model="pppoeUsername" 
                                       required
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- PPPoE Password with Eye Toggle -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-800 mb-1.5">
                                    PPPoE Password <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" 
                                           name="pppoe_password" 
                                           x-model="pppoePassword" 
                                           required
                                           class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                    <button type="button" 
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-800 cursor-pointer">
                                        <template x-if="!showPassword">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            </svg>
                                        </template>
                                        <template x-if="showPassword">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Card: Konfigurasi Router & IP Address -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                        <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-600"></span>
                            <span>Konfigurasi Router & IP Address</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Pilih Router MikroTik -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-800 mb-1.5">
                                    Pilih Router MikroTik <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <select name="router_mikrotik" 
                                        x-model="selectedRouter" 
                                        required
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                    <option value="Router Core Utama (CCR1036)">Router Core Utama (CCR1036)</option>
                                    <option value="Router Core BBU (CCR1072)">Router Core BBU (CCR1072)</option>
                                    <option value="Router POP Kayu Agung (CCR2004)">Router POP Kayu Agung (CCR2004)</option>
                                    <option value="Router POP Babakan (RB4011)">Router POP Babakan (RB4011)</option>
                                    <option value="Router Distribusi 01 (CCR1009)">Router Distribusi 01 (CCR1009)</option>
                                </select>
                            </div>

                            <!-- Local Address (Gateway) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-800 mb-1.5">
                                    Local Address (Gateway) <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="local_address" 
                                       x-model="localAddress" 
                                       required
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- Profile PPP -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-800 mb-1.5">
                                    Profile PPP <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="ppp_profile" 
                                       x-model="pppProfile" 
                                       required
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- Remote Address (IP Pelanggan) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-800 mb-1.5">
                                    Remote Address (IP Pelanggan) <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="remote_address" 
                                       x-model="remoteAddress" 
                                       placeholder="Contoh: 10.10.10.25 atau pool name"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-mono placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" 
                                @click="pppoeModalOpen = false" 
                                class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
                            <span>Batal</span>
                        </button>
                        
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-lg shadow-cyan-600/25 transition cursor-pointer">
                            <span>Buat PPPoE Secret & Mulai Aktivasi</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</div>
@endsection
