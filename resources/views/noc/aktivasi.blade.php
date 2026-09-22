@extends('layouts.app')

@section('title', 'Provisioning & Aktivasi Jaringan - NOC IMS')
@section('page_title', 'Provisioning & Aktivasi Jaringan')

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

    <!-- Top Action & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/10">
        
        <!-- Segmented Tab Switcher: Permintaan Siap Aktivasi (#18) vs Riwayat Selesai (#20) -->
        <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
            <a href="{{ route('noc.aktivasi', ['status' => 'siap_aktivasi']) }}"
               class="px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 {{ $statusTab === 'siap_aktivasi' ? 'bg-[#0891b2] text-white shadow-md shadow-cyan-600/25' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-semibold' }}">
                <span>Antrean Siap Aktivasi (#18)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold {{ $statusTab === 'siap_aktivasi' ? 'bg-white/25 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200' }}">
                    {{ $countSiapAktivasi }}
                </span>
            </a>
            <a href="{{ route('noc.aktivasi', ['status' => 'riwayat_aktif']) }}"
               class="px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 {{ $statusTab === 'riwayat_aktif' ? 'bg-[#0891b2] text-white shadow-md shadow-cyan-600/25' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-semibold' }}">
                <span>Riwayat Selesai Aktivasi (#20)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold {{ $statusTab === 'riwayat_aktif' ? 'bg-white/25 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200' }}">
                    {{ $countRiwayatAktif }}
                </span>
            </a>
        </div>

        <!-- Search Input -->
        <form method="GET" action="{{ route('noc.aktivasi') }}" class="relative flex-1 max-w-xs">
            <input type="hidden" name="status" value="{{ $statusTab }}">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}"
                   placeholder="Cari ID, Nama, ONT, HP..." 
                   class="w-full pl-9 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-medium focus:outline-none focus:ring-2 focus:ring-[#0891b2]/30 focus:border-[#0891b2]">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </form>
    </div>

    <!-- Aktivasi Table Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    {{ $statusTab === 'siap_aktivasi' ? 'Daftar Antrean Permintaan Aktivasi Jaringan' : 'Daftar Riwayat Pelanggan Aktif' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $statusTab === 'siap_aktivasi' ? 'Order pelanggan baru yang telah selesai instalasi kabel dan siap diaktivasi / provisioning oleh NOC.' : 'Daftar pelanggan yang sudah berhasil diaktivasi dan berstatus aktif di jaringan.' }}
                </p>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono font-bold">Total: {{ $pelanggans->total() }} Data</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-xs font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-4">No Internet / Pelanggan</th>
                        <th class="py-3.5 px-4">Paket & Bandwidth</th>
                        <th class="py-3.5 px-4">Alamat & Lokasi</th>
                        <th class="py-3.5 px-4">Node POP / OLT</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi NOC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($pelanggans as $p)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            
                            <!-- 1. Pelanggan -->
                            <td class="py-4 px-4 align-top">
                                <a href="{{ route('teknik.pelanggan.profile', $p->nomor_internet) }}" 
                                   class="font-mono font-bold text-cyan-600 dark:text-cyan-400 hover:underline text-xs">
                                    {{ $p->nomor_internet }}
                                </a>
                                <div class="font-bold text-slate-900 dark:text-white uppercase text-xs mt-0.5 leading-snug">
                                    {{ $p->nama_pelanggan }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 font-mono font-medium mt-0.5">
                                    📱 {{ $p->nomor_hp ?: '-' }}
                                </div>
                            </td>

                            <!-- 2. Paket Bandwidth -->
                            <td class="py-4 px-4 align-top">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ $p->nama_kategori_bandwith ?: ($p->alias_nama_kategori ?: 'INTERNET') }}
                                </div>
                                <div class="text-xs text-cyan-600 dark:text-cyan-400 font-extrabold font-mono mt-0.5">
                                    {{ $p->nominal_bandwith ?: '10' }} Mbps
                                </div>
                            </td>

                            <!-- 3. Alamat & Lokasi -->
                            <td class="py-4 px-4 align-top text-slate-600 dark:text-slate-400 font-medium max-w-[220px]">
                                <div class="leading-relaxed">{{ $p->alamat_p ?: ($p->alamat_pasang ?: '-') }}</div>
                                @if($p->loc_maps)
                                    <a href="{{ $p->loc_maps }}" target="_blank" class="text-xs text-cyan-600 dark:text-cyan-400 hover:underline font-semibold block mt-1">
                                        📍 Lihat Maps
                                    </a>
                                @endif
                            </td>

                            <!-- 4. Node POP / OLT -->
                            <td class="py-4 px-4 align-top space-y-1">
                                <div>
                                    <span class="text-xs text-slate-400 font-medium">POP:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $p->nama_pop ?: ($p->kode_pop ?: '-') }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-400 font-medium">OLT:</span>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $p->index_olt ?: '-' }}</span>
                                </div>
                            </td>

                            <!-- 5. Status -->
                            <td class="py-4 px-4 align-top">
                                @if(in_array($p->status_reg, ['20']))
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 shadow-xs">
                                        ONLINE / AKTIF (#20)
                                    </span>
                                @elseif(in_array($p->status_reg, ['19', '19.1']))
                                    <div class="space-y-1">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $p->status_reg == '19.1' ? 'bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-500/15 dark:text-sky-400 dark:border-sky-500/30' : 'bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/15 dark:text-cyan-400 dark:border-cyan-500/30' }} shadow-xs">
                                            {{ $p->status_reg == '19.1' ? 'RESCHEDULE (#19.1)' : 'TERJADWAL (#19)' }}
                                        </span>
                                        @if($p->aktivasi_date_start)
                                            <div class="text-xs text-slate-600 dark:text-slate-400 font-mono font-semibold">
                                                📅 {{ \Carbon\Carbon::parse($p->aktivasi_date_start)->translatedFormat('d M Y') }}
                                                @if($p->aktivasi_time)
                                                    • {{ substr($p->aktivasi_time, 0, 5) }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @elseif(in_array($p->status_reg, ['18', '18.1']))
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 shadow-xs">
                                        SIAP JADWAL (#18)
                                    </span>
                                @else
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-500/15 dark:text-slate-400 dark:border-slate-500/30">
                                        {{ $p->desc_registrasi ?: 'Status #' . $p->status_reg }}
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Aksi NOC -->
                            <td class="py-4 px-4 align-top text-center">
                                @if(in_array($p->status_reg, ['18', '18.1']))
                                    <div class="flex items-center justify-center">
                                        <button type="button"
                                                @click="openScheduleModal({{ json_encode($p) }})"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow-md shadow-cyan-600/20 transition cursor-pointer"
                                                title="Jadwalkan Aktivasi Layanan (NOC)">
                                            <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                            <span>Schedule Aktivasi</span>
                                        </button>
                                    </div>
                                @elseif(in_array($p->status_reg, ['19', '19.1']))
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <button type="button"
                                                @click="openReportModal({{ json_encode($p) }})"
                                                class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition cursor-pointer"
                                                title="Lanjut ke Form Eksekusi Aktivasi Layanan (Otomatis Reboot OLT/MikroTik di Background)">
                                            <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                            </svg>
                                            <span>Aktivasi</span>
                                        </button>
                                        <button type="button"
                                                @click="openScheduleModal({{ json_encode($p) }})"
                                                class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition cursor-pointer border border-slate-200 dark:border-slate-700"
                                                title="Reschedule / Jadwalkan Ulang jika aktivasi tertunda atau berhalangan">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                            <span>Reschedule</span>
                                        </button>
                                    </div>
                                @else
                                    <a href="{{ route('teknik.pelanggan.profile', $p->nomor_internet) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                                        <span>Lihat Profil</span>
                                    </a>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-medium text-xs">
                                {{ $statusTab === 'siap_aktivasi' ? 'Tidak ada antrean aktivasi yang pending. Semua pelanggan baru telah diaktivasi.' : 'Tidak ada data pelanggan yang cocok.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pelanggans->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $pelanggans->links() }}
            </div>
        @endif
    </div>

    <!-- =================================================================== -->
    <!-- MODAL: SCHEDULE AKTIVASI LAYANAN (PENJADWALAN NOC)                 -->
    <!-- =================================================================== -->
    <div x-show="scheduleModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs transition-opacity" 
             @click="scheduleModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col"
                 @click.away="scheduleModalOpen = false">
                
                <!-- Modal Header with Cyan Accent -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-cyan-50 dark:bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-500/30">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white" x-text="isModalReschedule ? 'Reschedule / Ubah Jadwal Aktivasi (NOC)' : 'Jadwalkan Aktivasi Layanan (NOC)'">
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium" x-text="isModalReschedule ? 'Perbarui tanggal, waktu, atau tim aktivasi jika jadwal sebelumnya berhalangan / tertunda.' : 'Tetapkan tanggal, jam, tim NOC & node jaringan sebelum proses aktivasi layanan.'">
                            </p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="scheduleModalOpen = false" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-1 leading-none transition cursor-pointer">
                        &times;
                    </button>
                </div>

                <!-- Form Content -->
                <form :action="'{{ url('noc/aktivasi') }}/' + modalNomorInternet + '/schedule'" 
                      method="POST" 
                      class="p-6 space-y-4 text-xs">
                    @csrf

                    <!-- Customer Info Header Banner -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider font-mono">ID Pelanggan</span>
                            <div class="font-mono font-extrabold text-cyan-600 dark:text-cyan-400 text-sm" x-text="modalNomorInternet"></div>
                            <div class="font-bold text-slate-900 dark:text-white uppercase" x-text="modalNamaPelanggan"></div>
                        </div>
                        <div class="text-right space-y-1">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider font-mono">Paket Berlangganan</span>
                            <div class="font-extrabold text-cyan-700 dark:text-cyan-400 text-sm" x-text="modalPaket"></div>
                        </div>
                    </div>

                    <!-- 1. Jadwal & Waktu Aktivasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                <span x-text="isModalReschedule ? 'Tanggal Jadwal Baru' : 'Tanggal Rencana Aktivasi'"></span> <span class="text-rose-600 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   name="jadwal_aktivasi" 
                                   x-model="modalJadwalAktivasi" 
                                   required 
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Waktu / Jam Aktivasi <span class="text-rose-600 font-bold">*</span>
                            </label>
                            <input type="time" 
                                   name="waktu_aktivasi" 
                                   x-model="modalWaktuAktivasi" 
                                   required 
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                        </div>
                    </div>

                    <!-- 2. Petugas / Tim NOC -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Petugas / Tim NOC Eksekutor <span class="text-rose-600 font-bold">*</span>
                        </label>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 max-h-32 overflow-y-auto space-y-2">
                            @foreach($karyawans as $karyawan)
                                <label class="flex items-center gap-2 cursor-pointer text-slate-800 dark:text-slate-200 hover:text-cyan-600 dark:hover:text-cyan-400 font-medium">
                                    <input type="checkbox" 
                                           name="team_aktivasi[]" 
                                           value="{{ $karyawan->nama_karyawan }}" 
                                           x-model="modalTeamAktivasi" 
                                           class="rounded border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500">
                                    <span class="text-xs">{{ $karyawan->nama_karyawan }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 5. Catatan / Rencana Aktivasi -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            <span x-text="isModalReschedule ? 'Alasan Reschedule / Catatan Baru' : 'Catatan / Instruksi Rencana Aktivasi'"></span>
                        </label>
                        <textarea name="catatan" 
                                  x-model="modalCatatanSchedule" 
                                  rows="2" 
                                  placeholder="Catatan persiapan aktivasi atau alasan penundaan/reschedule..." 
                                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs"></textarea>
                    </div>

                    <!-- Modal Actions: Batal & Simpan Jadwal -->
                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" 
                                @click="scheduleModalOpen = false" 
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer">
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-md shadow-cyan-600/25 transition cursor-pointer">
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
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs transition-opacity" 
             @click="reportModalOpen = false; aktivasiModalOpen = false;"></div>
        
        <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
            <div class="relative w-full max-w-5xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden transition-all"
                 @click.away="reportModalOpen = false; aktivasiModalOpen = false;">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/50">
                    <div class="space-y-0.5">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                            <span>Aktivasi Layanan An/</span>
                            <span class="text-cyan-600 dark:text-cyan-400 uppercase font-mono" x-text="modalNamaPelanggan"></span>
                        </h3>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            <span>Setelah data aktivasi disimpan, sistem otomatis mengeksekusi reboot OLT/MikroTik & beralih ke status Aktif (#20).</span>
                        </p>
                    </div>
                    <button type="button" 
                            @click="reportModalOpen = false; aktivasiModalOpen = false;" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold transition cursor-pointer">
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
                        <div class="p-3.5 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/30 text-xs space-y-1">
                            <div class="flex items-center gap-1 text-[11px] uppercase font-bold text-cyan-800 dark:text-cyan-400">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span>Catatan / Instruksi dari Schedule Aktivasi:</span>
                            </div>
                            <p class="text-slate-800 dark:text-slate-200 font-semibold whitespace-pre-line pl-4.5" x-text="modalCatatanSchedule"></p>
                        </div>
                    </template>

                    <!-- 2-Column Grid Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                        
                        <!-- LEFT COLUMN: Form Konfigurasi Report Aktivasi -->
                        <div class="lg:col-span-6 space-y-4">
                            
                            <!-- 1. Tanggal Selesai & Waktu Aktivasi -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        Tanggal Aktivasi <span class="text-rose-600 font-bold">*</span>
                                    </label>
                                    <input type="date" 
                                           name="jadwal_aktivasi" 
                                           x-model="modalJadwalAktivasi" 
                                           required 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        Waktu Aktivasi <span class="text-rose-600 font-bold">*</span>
                                    </label>
                                    <input type="time" 
                                           name="waktu_aktivasi" 
                                           x-model="modalWaktuAktivasi" 
                                           required 
                                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                </div>
                            </div>

                            <!-- 2. Team Aktivasi (Checkbox Grid) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Petugas / Team Aktivasi
                                </label>
                                <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-[11px] max-h-32 overflow-y-auto">
                                    @foreach($karyawans as $tech)
                                        <label class="flex items-center gap-2 cursor-pointer text-slate-800 dark:text-slate-200 hover:text-cyan-600 dark:hover:text-cyan-400 font-medium">
                                            <input type="checkbox" 
                                                   name="team_aktivasi[]" 
                                                   value="{{ $tech->nama_karyawan }}" 
                                                   :checked="modalTeamAktivasi.includes('{{ $tech->nama_karyawan }}')"
                                                   class="rounded border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500 w-3.5 h-3.5">
                                            <span class="truncate">{{ $tech->nama_karyawan }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- 3. POP/ODN & Media Akses -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        POP/ODN <span class="text-rose-600 font-bold">*</span>
                                    </label>
                                    <select name="kode_pop" 
                                            x-model="modalKodePop" 
                                            required
                                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                        <option value="">-- Pilih POP --</option>
                                        @foreach($pops as $pop)
                                            <option value="{{ $pop->kode_pop }}">{{ $pop->nama_pop }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        Media Akses <span class="text-rose-600 font-bold">*</span>
                                    </label>
                                    <select name="media_akses" 
                                            x-model="modalMediaAkses" 
                                            required
                                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                        <option value="FTTH">FTTH</option>
                                        <option value="FTTH MSN">FTTH MSN</option>
                                        <option value="PTP FO">PTP FO</option>
                                        <option value="WIRELESS">WIRELESS</option>
                                        <option value="GPON">GPON</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 3b. Dropdown Pilihan 3 OLT Server (Muncul Jika Media Akses FTTH) -->
                            <div x-show="modalMediaAkses === 'FTTH' || modalMediaAkses === 'FTTH MSN'" 
                                 x-transition
                                 class="p-3.5 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/30">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-[11px] font-bold text-cyan-900 dark:text-cyan-300">
                                        Pilih Server OLT (FTTH) <span class="text-rose-600">*</span>
                                    </label>
                                    <span class="text-[10px] font-mono text-cyan-700 dark:text-cyan-400 font-bold">{{ count($olts) }} OLT Tersedia</span>
                                </div>
                                <select name="olt" 
                                        x-model="modalOlt" 
                                        :required="modalMediaAkses === 'FTTH' || modalMediaAkses === 'FTTH MSN'"
                                        class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-cyan-300 dark:border-cyan-700 text-slate-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
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
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                        Index OLT <span class="text-rose-600 font-bold">*</span>
                                    </label>
                                    <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-mono font-extrabold" x-text="modalIndexOlt || 'Belum dipilih'"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- Select GPON Port -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                            Pilih Port GPON:
                                        </label>
                                        <select x-model="selectedGponPort" 
                                                @change="selectedOnuSlot = ''; updateIndexOlt();"
                                                class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono text-[11px] font-bold shadow-xs">
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
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                            Pilih Index:
                                        </label>
                                        <select x-model="selectedOnuSlot" 
                                                @change="updateIndexOlt()" 
                                                class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono text-[11px] font-bold shadow-xs">
                                            <option value="">-- Pilih Index (1..128) --</option>
                                            <template x-for="slot in (allSlots[selectedGponPort] || [])" :key="slot.key">
                                                <option :value="slot.num" 
                                                        :disabled="slot.is_occupied && modalIndexOlt !== slot.key"
                                                        :class="slot.is_occupied ? 'text-rose-600 bg-rose-50 dark:bg-rose-950/40 font-bold' : 'text-emerald-700 dark:text-emerald-400 font-bold'"
                                                        x-text="(slot.is_occupied ? '🔴 ' : '🟢 ') + slot.key">
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. SN / Serial Nomor Modem -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    SN / Serial Nomor Modem ONT <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="sn_modem" 
                                       x-model="modalSnModem" 
                                       required
                                       placeholder="Contoh: ZTEGC1234567 atau HUAWEI1234..." 
                                       class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- 6. Catatan Tambahan Eksekusi Aktivasi -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Catatan Eksekusi Aktivasi
                                </label>
                                <textarea name="catatan_aktivasi" 
                                          x-model="modalCatatanAktivasi" 
                                          rows="2" 
                                          placeholder="Catatan hasil aktivasi teknis atau redaman optik..." 
                                          class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs"></textarea>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN: Perangkat & Upload Foto Bukti Aktivasi -->
                        <div class="lg:col-span-6 space-y-4">
                            
                            <!-- Subheading with cyan vertical accent bar -->
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-5 rounded bg-cyan-600"></span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Perangkat / Peralatan & Bukti Foto</span>
                            </div>

                            <!-- Dynamic Device / Item Adder Row -->
                            <div class="grid grid-cols-12 gap-2 items-end">
                                <div class="col-span-6">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Perangkat</label>
                                    <select x-model="selectedBarang" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                        <option value="">Pilih Perangkat</option>
                                        @foreach($barangs as $b)
                                            <option value="{{ $b->kode_barang }}">{{ $b->nama_barang }} {{ $b->tipe_barang }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Jumlah</label>
                                    <input type="number" min="1" x-model="selectedJumlah" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[11px] font-bold text-transparent mb-1">action</label>
                                    <button type="button" 
                                            @click="addPerangkat()" 
                                            class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-md shadow-teal-600/20 transition cursor-pointer">
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Table of Added Perangkat Items -->
                            <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-extrabold text-slate-700 dark:text-slate-300">
                                            <th class="py-2.5 px-3">Barang</th>
                                            <th class="py-2.5 px-3 text-center w-20">Jumlah</th>
                                            <th class="py-2.5 px-3 text-center w-16">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900">
                                        <template x-if="perangkatList.length === 0">
                                            <tr>
                                                <td colspan="3" class="py-4 text-center text-slate-500 font-medium text-xs">
                                                    Belum ada perangkat yang ditambahkan.
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-for="(item, index) in perangkatList" :key="index">
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-2 px-3 font-semibold text-slate-900 dark:text-white" x-text="item.nama"></td>
                                                <td class="py-2 px-3 text-center font-mono font-bold text-cyan-600 dark:text-cyan-400" x-text="item.jumlah"></td>
                                                <td class="py-2 px-3 text-center">
                                                    <button type="button" @click="removePerangkat(index)" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 text-sm font-bold transition cursor-pointer">
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
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Foto Bukti Aktivasi / Hasil Redaman ONT <span class="text-rose-600">*</span>
                                </label>
                                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-4 text-center hover:border-cyan-500 transition cursor-pointer bg-slate-50 dark:bg-slate-950/40 hover:bg-cyan-50/30">
                                    <input type="file" 
                                           name="foto_aktivasi" 
                                           accept="image/*" 
                                           @change="const file = $event.target.files[0]; if(file) { fotoAktivasiPreview = URL.createObjectURL(file); }"
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    
                                    <template x-if="!fotoAktivasiPreview">
                                        <div class="flex flex-col items-center justify-center py-4">
                                            <svg class="w-8 h-8 text-slate-400 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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
                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" 
                                @click="reportModalOpen = false; aktivasiModalOpen = false;" 
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer">
                            <span>Batal</span>
                        </button>

                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/25 transition cursor-pointer">
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
    <!-- =================================================================== -->
    <div x-show="pppoeModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs transition-opacity" 
             @click="pppoeModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden transition-all my-auto"
                 @click.away="pppoeModalOpen = false">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/50">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-wide flex items-center gap-1.5">
                        <span>Konfigurasi PPPoE & Mulai Aktivasi An/</span>
                        <span class="uppercase text-cyan-600 dark:text-cyan-400 font-mono" x-text="modalNamaPelanggan"></span>
                    </h3>
                    <button type="button" 
                            @click="pppoeModalOpen = false" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-1 leading-none transition cursor-pointer">
                        &times;
                    </button>
                </div>

                <!-- Form Body -->
                <form :action="'{{ url('/noc/aktivasi') }}/' + modalNomorInternet + '/pppoe-activate'" 
                      method="POST" 
                      class="p-6 space-y-5 text-xs">
                    @csrf

                    <!-- 1. Card: Kredensial PPPoE Pelanggan -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-600"></span>
                            <span>Kredensial PPPoE Pelanggan</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- PPPoE Username -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    PPPoE Username <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="pppoe_username" 
                                       x-model="pppoeUsername" 
                                       required
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- PPPoE Password with Eye Toggle -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    PPPoE Password <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" 
                                           name="pppoe_password" 
                                           x-model="pppoePassword" 
                                           required
                                           class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                    <button type="button" 
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white cursor-pointer">
                                        <template x-if="!showPassword">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
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
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-4">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-600"></span>
                            <span>Konfigurasi Router & IP Address</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Pilih Router MikroTik -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Pilih Router MikroTik <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <select name="router_mikrotik" 
                                        x-model="selectedRouter" 
                                        required
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                                    <option value="Router Core Utama (CCR1036)">Router Core Utama (CCR1036)</option>
                                    <option value="Router Core BBU (CCR1072)">Router Core BBU (CCR1072)</option>
                                    <option value="Router POP Kayu Agung (CCR2004)">Router POP Kayu Agung (CCR2004)</option>
                                    <option value="Router POP Babakan (RB4011)">Router POP Babakan (RB4011)</option>
                                    <option value="Router Distribusi 01 (CCR1009)">Router Distribusi 01 (CCR1009)</option>
                                </select>
                            </div>

                            <!-- Local Address (Gateway) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Local Address (Gateway) <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="local_address" 
                                       x-model="localAddress" 
                                       required
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- Profile PPP -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Profile PPP <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="ppp_profile" 
                                       x-model="pppProfile" 
                                       required
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>

                            <!-- Remote Address (IP Pelanggan) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Remote Address (IP Pelanggan) <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <input type="text" 
                                       name="remote_address" 
                                       x-model="remoteAddress" 
                                       placeholder="Contoh: 10.10.10.25 atau pool name" 
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-xs shadow-xs">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" 
                                @click="pppoeModalOpen = false" 
                                class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer">
                            <span>Batal</span>
                        </button>
                        
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/25 transition cursor-pointer">
                            <span>Buat PPPoE Secret & Mulai Aktivasi</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</div>
@endsection/div>
@endsection
