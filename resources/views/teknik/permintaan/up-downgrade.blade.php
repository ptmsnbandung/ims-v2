@extends('layouts.app')

@section('title', 'Ubah Layanan (UP / Downgrade) - IMS Router')
@section('page_title', 'Ubah Layanan (UP / Downgrade)')

@section('content')
<div class="space-y-5"
     x-data="{
         scheduleModalOpen: false,
         executeModalOpen: false,
         modalKodeTrx: '',
         modalNomorInternet: '',
         modalNamaPelanggan: '',
         modalPaketLama: '',
         modalPaketBaru: '',
         modalKodeKategori: '',
         modalKodeBandwithBaru: '',
         modalGroupLayanan: 'MEDIANET',
         modalDateSchedule: '{{ date('Y-m-d') }}',
         modalNoteSchedule: '',
         modalDateEksekusi: '{{ date('Y-m-d') }}',
         modalNoteEksekusi: '',
         allPaketList: {{ Js::from($paketList ?? []) }},
         filteredPaketList: [],

         onLayananChange() {
             this.modalKodeBandwithBaru = '';
             if (!this.modalKodeKategori) {
                 this.filteredPaketList = [];
                 return;
             }
             this.filteredPaketList = this.allPaketList.filter(p => 
                 p.kode_kategori_bandwith === this.modalKodeKategori || 
                 p.nama_kategori_bandwith === this.modalKodeKategori ||
                 p.alias_nama_kategori === this.modalKodeKategori
             );
             if (this.filteredPaketList.length === 0) {
                 fetch(`/teknik/api/paket/${encodeURIComponent(this.modalKodeKategori)}`)
                     .then(res => res.json())
                     .then(data => {
                         if (Array.isArray(data) && data.length > 0) {
                             this.filteredPaketList = data;
                         }
                     })
                     .catch(() => {});
             }
         },

         openScheduleModal(item) {
             this.modalKodeTrx = item.kode_trx_ubah_layanan;
             this.modalNomorInternet = item.nomor_internet;
             this.modalNamaPelanggan = item.nama_pelanggan || '';
             
             let paket = (item.nama_kategori_bandwith_baru || item.alias_nama_kategori_baru || 'BROADBAND');
             let speed = item.nominal_bandwith_baru ? (' ' + item.nominal_bandwith_baru + ' Mbps') : '';
             this.modalPaketBaru = paket + speed;

             this.modalDateSchedule = item.date_schedule ? item.date_schedule.substring(0, 10) : '{{ date('Y-m-d') }}';
             this.modalNoteSchedule = item.note_schedule || '';
             this.scheduleModalOpen = true;
         },

         openExecuteModal(item) {
             this.modalKodeTrx = item.kode_trx_ubah_layanan;
             this.modalNomorInternet = item.nomor_internet;
             this.modalNamaPelanggan = item.nama_pelanggan || '';
             
             let paketLama = (item.nama_kategori_bandwith_lama || item.alias_nama_kategori_lama || 'BROADBAND');
             let speedLama = item.nominal_bandwith_lama ? (' ' + item.nominal_bandwith_lama + ' Mbps') : '';
             this.modalPaketLama = paketLama + speedLama;

             let paketBaru = (item.nama_kategori_bandwith_baru || item.alias_nama_kategori_baru || 'BROADBAND');
             let speedBaru = item.nominal_bandwith_baru ? (' ' + item.nominal_bandwith_baru + ' Mbps') : '';
             this.modalPaketBaru = paketBaru + speedBaru;

             this.modalGroupLayanan = item.group_layanan || 'MEDIANET';
             this.modalDateEksekusi = '{{ date('Y-m-d') }}';
             this.modalNoteEksekusi = 'Eksekusi UP/Downgrade bandwidth profil pelanggan berhasil diselesaikan.';
             this.fotoPreview = null;
             this.fotoFileName = '';

             // Pre-select matching category
             this.modalKodeKategori = item.kode_kategori_bandwith_baru || '';
             if (!this.modalKodeKategori && (item.nama_kategori_bandwith_baru || item.alias_nama_kategori_baru)) {
                 let catName = item.nama_kategori_bandwith_baru || item.alias_nama_kategori_baru;
                 let found = this.allPaketList.find(p => p.nama_kategori_bandwith === catName || p.kode_kategori_bandwith === catName || p.alias_nama_kategori === catName);
                 if (found) {
                     this.modalKodeKategori = found.kode_kategori_bandwith;
                 } else {
                     this.modalKodeKategori = catName;
                 }
             }

             if (this.modalKodeKategori) {
                 this.onLayananChange();
                 this.modalKodeBandwithBaru = item.kode_bandwith_baru || '';
             } else {
                 this.filteredPaketList = [];
                 this.modalKodeBandwithBaru = '';
             }

             this.executeModalOpen = true;
         },

         onFotoChange(event) {
             const file = event.target.files ? event.target.files[0] : null;
             this.handleFile(file);
         },

         handleDrop(event) {
             const file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
             if (file && this.$refs.fileInput) {
                 try {
                     const dataTransfer = new DataTransfer();
                     dataTransfer.items.add(file);
                     this.$refs.fileInput.files = dataTransfer.files;
                 } catch (e) {}
             }
             this.handleFile(file);
         },

         handleFile(file) {
             if (file) {
                 this.fotoFileName = file.name;
                 const reader = new FileReader();
                 reader.onload = (e) => {
                     this.fotoPreview = e.target.result;
                 };
                 reader.readAsDataURL(file);
             } else {
                 this.fotoPreview = null;
                 this.fotoFileName = '';
             }
         }
     }">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <span>IMS</span>
        <span>&gt;</span>
        <a href="{{ route('teknik.tiket') }}" class="hover:text-blue-500 transition">Tiket</a>
        <span>&gt;</span>
        <span class="text-blue-500 font-semibold">Ubah Layanan</span>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (EXACT MATCHING SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xl shadow-slate-200/40">
        <form method="GET" action="{{ route('teknik.permintaan.up-downgrade') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- 1. Dropdown Semua Layanan -->
            <div class="lg:col-span-3">
                <select name="layanan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA LAYANAN</option>
                    @if(isset($layananList))
                        @foreach($layananList as $lay)
                            <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ strtoupper($lay) }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- 2. Input Nama / Nomor Layanan -->
            <div class="lg:col-span-3">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="NAMA / NOMOR LAYANAN" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="lg:col-span-2">
                <input type="text" 
                       name="wilayah" 
                       value="{{ request('wilayah') }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="lg:col-span-2">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>(KD11) Request</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>(KD12) On Schedule</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>(KD13) Success</option>
                    <option value="14" {{ request('status') === '14' ? 'selected' : '' }}>(KD14) Canceled</option>
                </select>
            </div>

            <!-- 5. Action Buttons (Reset & Export) -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <a href="{{ route('teknik.permintaan.up-downgrade') }}" 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer"
                   title="Reset Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>

                <button type="submit" 
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer"
                        title="Filter / Export">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export</span>
                </button>
            </div>

        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 2. STATUS PILL 4 KPI BANNERS GRID (EXACT MATCHING SCREENSHOT)       -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- Card 1: (KD11) Request : X User (Pink / Rose Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '11']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '11' ? 'ring-2 ring-white/60 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD11) Request : {{ $count11 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 2: (KD12) On Schedule : X User (Gold / Yellow Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '12']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '12' ? 'ring-2 ring-slate-900/60 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD12) On Schedule : {{ $count12 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 3: (KD13) Success : X User (Teal / Emerald Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '13']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-emerald-500 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '13' ? 'ring-2 ring-white/60 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD13) Success : {{ $count13 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 4: (KD14) Canceled : X User (Teal / Cyan Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '14']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-teal-400 to-cyan-500 hover:from-teal-500 hover:to-cyan-600 text-white font-bold text-xs shadow-md shadow-teal-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '14' ? 'ring-2 ring-white/60 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD14) Canceled : {{ $count14 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF UP / DOWNGRADE CUSTOMERS                                -->
    <!-- =================================================================== -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/40 overflow-hidden">
        
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between text-xs text-slate-500">
            <div>
                Show <span class="font-bold text-slate-800">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-800">{{ $ubahLayanans->total() }}</strong> Data
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-700">
                        <th class="py-3.5 px-4 min-w-[180px]">Customer</th>
                        <th class="py-3.5 px-4 min-w-[280px]">Address</th>
                        <th class="py-3.5 px-4 min-w-[120px]">Old</th>
                        <th class="py-3.5 px-4 min-w-[140px]">New</th>
                        <th class="py-3.5 px-4 min-w-[130px]">State</th>
                        <th class="py-3.5 px-4 text-center min-w-[140px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($ubahLayanans as $item)
                        <tr class="hover:bg-slate-50 transition">
                            
                            <!-- 1. Customer Column -->
                            <td class="py-4 px-4 align-top">
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-mono font-bold text-blue-600 hover:underline tracking-wide text-xs inline-block"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>

                                <div class="font-bold text-slate-800 uppercase text-xs mt-0.5">
                                    <span>{{ $item->nama_pelanggan }}</span>
                                    <span class="text-slate-500 font-normal">
                                        ( {{ ($item->jenis_kelamin ?? null) == 1 ? 'L' : (($item->jenis_kelamin ?? null) == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Address Column -->
                            <td class="py-4 px-4 align-top text-xs space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-800 uppercase text-[11px]">
                                        {{ $item->jenis_bangunan ?? 'RUMAH-PRIBADI' }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                        Aktif
                                    </span>
                                </div>
                                <div class="text-slate-600 text-[11px] leading-relaxed">
                                    {{ $item->alamat_p ?? ($item->alamat_pasang ?? '-') }}
                                </div>
                            </td>

                            <!-- 3. Old Package Column -->
                            <td class="py-4 px-4 align-top">
                                <div class="font-semibold text-slate-800 text-xs">
                                    {{ $item->nama_kategori_bandwith_lama ?: 'BROADBAND' }}
                                </div>
                                <div class="text-blue-600 font-bold underline font-mono text-[11px] mt-0.5">
                                    {{ $item->nominal_bandwith_lama ?: '10' }} Mbps
                                </div>
                            </td>

                            <!-- 4. New Package Column -->
                            <td class="py-4 px-4 align-top">
                                <div class="font-semibold text-slate-800 text-xs">
                                    {{ $item->nama_kategori_bandwith_baru ?: 'BROADBAND FREE' }}
                                </div>
                                <div class="text-slate-600 font-medium font-mono text-[11px] mt-0.5">
                                    {{ $item->nominal_bandwith_baru ?: '10' }} Mbps
                                </div>
                            </td>

                            <!-- 5. State Column -->
                            <td class="py-4 px-4 align-top">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono tracking-wide @if(in_array($item->status_ubah_layanan, ['11'])) bg-rose-500/15 text-rose-600 border border-rose-500/30 @elseif(in_array($item->status_ubah_layanan, ['12'])) bg-amber-500/15 text-amber-600 border border-amber-500/30 @elseif(in_array($item->status_ubah_layanan, ['13'])) bg-emerald-500/15 text-emerald-600 border border-emerald-500/30 @elseif(in_array($item->status_ubah_layanan, ['14'])) bg-slate-500/15 text-slate-600 border border-slate-500/30 @else bg-slate-500/15 text-slate-600 border border-slate-500/30 @endif">
                                    {{ $item->desc_ubah_layanan ?: 'Request' }}
                                </span>
                                <div class="font-mono text-[10px] text-slate-400 mt-1">
                                    {{ $item->date_create ? \Carbon\Carbon::parse($item->date_create)->translatedFormat('d F Y') : ($item->date_request ? \Carbon\Carbon::parse($item->date_request)->translatedFormat('d F Y') : '-') }}
                                </div>
                            </td>

                            <!-- 6. Action Column (Schedule, UP/Downgrade & Cancel Buttons) -->
                            <td class="py-4 px-4 align-top text-center">
                                @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                                    @if($item->status_ubah_layanan === '11')
                                        <!-- Status 11 (KD11 Request): Tombol Schedule & Cancel -->
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <button type="button" 
                                                    @click="openScheduleModal({{ json_encode($item) }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold shadow-sm shadow-blue-500/20 transition cursor-pointer"
                                                    title="Jadwalkan Ubah Layanan">
                                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                                </svg>
                                                <span>Schedule</span>
                                            </button>

                                            <!-- Cancel Button -->
                                            <form action="{{ route('teknik.permintaan.up-downgrade.cancel', $item->kode_trx_ubah_layanan) }}" method="POST" onsubmit="return confirm('Batalkan permohonan ubah layanan {{ $item->nomor_internet }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-600 text-slate-600 hover:text-white text-[10px] font-bold border border-slate-300 transition cursor-pointer"
                                                        title="Batalkan Permintaan">
                                                    <svg class="w-3 h-3 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Cancel</span>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($item->status_ubah_layanan === '12')
                                        <!-- Status 12 (KD12 On Schedule): Tombol berubah menjadi UP/Downgrade dan opsi Reschedule -->
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            
                                            <!-- Button UP / Downgrade (Emerald Gradient) -->
                                            <button type="button" 
                                                    @click="openExecuteModal({{ json_encode($item) }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-[11px] font-bold shadow-md shadow-emerald-500/25 transition cursor-pointer hover:scale-[1.02]"
                                                    title="Eksekusi UP / Downgrade Paket">
                                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                                                </svg>
                                                <span>UP/Downgrade</span>
                                            </button>

                                            <div class="flex items-center gap-1">
                                                <!-- Reschedule Option -->
                                                <button type="button" 
                                                        @click="openScheduleModal({{ json_encode($item) }})"
                                                        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] text-amber-500 hover:text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition cursor-pointer"
                                                        title="Ubah Jadwal Schedule">
                                                    <span>Reschedule</span>
                                                </button>

                                                <!-- Cancel Option -->
                                                <form action="{{ route('teknik.permintaan.up-downgrade.cancel', $item->kode_trx_ubah_layanan) }}" method="POST" onsubmit="return confirm('Batalkan permohonan ubah layanan {{ $item->nomor_internet }}?');">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] text-rose-400 hover:text-white hover:bg-rose-600 transition cursor-pointer"
                                                            title="Batalkan Permintaan">
                                                        &times;
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @elseif($item->status_ubah_layanan == '13')
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 text-[11px] font-bold">
                                                <svg class="w-3.5 h-3.5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Berhasil Diubah</span>
                                            </div>
                                            @if(!empty($item->foto_ss))
                                                <a href="{{ asset('uploads/up_downgrade/' . $item->foto_ss) }}" 
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-500 hover:text-blue-400 hover:underline mt-0.5">
                                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>
                                                    <span>Lihat Foto Bukti</span>
                                                </a>
                                            @endif
                                        </div>
                                    @elseif($item->status_ubah_layanan == '14')
                                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-500/10 text-slate-500 border border-slate-500/20 text-[11px] font-medium">
                                            <span>Dibatalkan</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs font-mono">-</span>
                                    @endif
                                @else
                                    <!-- Role Read-only mode -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-[11px] font-medium border border-slate-200">
                                        <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <span>View Only</span>
                                    </span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400 text-xs">
                                Tidak ada data permintaan ubah layanan yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ubahLayanans->hasPages())
            <div class="p-4 border-t border-slate-200 flex items-center justify-between">
                {{ $ubahLayanans->links() }}
            </div>
        @endif

    </div>

    @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
    <!-- =================================================================== -->
    <!-- 4. MODAL: FORM SCHEDULE UBAH LAYANAN (EXACT MATCHING SCREENSHOT)    -->
    <!-- =================================================================== -->
    <div x-show="scheduleModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-100 backdrop-blur-sm overflow-y-auto">
        
        <div @click.away="scheduleModalOpen = false"
             class="relative w-full max-w-lg bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col">
            
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-1.5 truncate">
                    <span>Form Schedule Ubah Layanan An/</span>
                    <span class="text-blue-600 uppercase font-extrabold" x-text="modalNamaPelanggan"></span>
                </h3>
                <button type="button" 
                        @click="scheduleModalOpen = false" 
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <form :action="'{{ url('/teknik/permintaan/up-downgrade') }}/' + modalKodeTrx + '/schedule'" 
                  method="POST" 
                  class="flex flex-col flex-1">
                @csrf

                <div class="p-5 space-y-4">
                    <!-- Subheading: Permintaan Layanan Baru (with cyan vertical accent bar) -->
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-0.5 h-4 rounded bg-[#00bcd4]"></span>
                            <span class="text-xs font-semibold text-slate-700">Permintaan Layanan Baru</span>
                        </div>

                        <!-- Big Card Center: BROADBAND FREE 10 Mbps -->
                        <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 shadow-sm text-center">
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1e293b] tracking-wide uppercase font-sans" 
                                x-text="modalPaketBaru">
                                BROADBAND FREE 10 Mbps
                            </h2>
                        </div>
                    </div>

                    <!-- Input Fields Card Container -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3.5">
                        
                        <!-- 1. Schedule Update (Tanggal Reschedule) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Schedule Update <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   name="date_schedule" 
                                   x-model="modalDateSchedule" 
                                   required 
                                   placeholder="Tanggal Reschedule"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- 2. Note (Catatan Schedule) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                note <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <textarea name="note_schedule" 
                                      x-model="modalNoteSchedule" 
                                      rows="3" 
                                      required
                                      placeholder="catatan schedule"
                                      class="w-full text-xs px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>

                    </div>
                </div>

                <!-- Modal Actions: Tutup (Cyan) & Update (Blue) -->
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0">
                    
                    <!-- Button Tutup (Cyan) -->
                    <button type="button" 
                            @click="scheduleModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#00bcd4] hover:bg-[#00acc1] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span>Tutup</span>
                    </button>

                    <!-- Button Update (Blue) -->
                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                        <span>Update Schedule</span>
                    </button>

                </div>
            </form>

        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. MODAL: EKSEKUSI UP / DOWNGRADE LAYANAN & PILIHAN PAKET BARU      -->
    <!-- =================================================================== -->
    <div x-show="executeModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-100 backdrop-blur-sm overflow-y-auto">
        
        <div @click.away="executeModalOpen = false"
             class="relative w-full max-w-xl bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col text-slate-800">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-slate-50 to-emerald-50/30 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 truncate">
                    <span class="p-1.5 rounded-lg bg-emerald-500/15 text-emerald-500">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                        </svg>
                    </span>
                    <span>Eksekusi UP / Downgrade An/</span>
                    <span class="text-emerald-500 uppercase font-extrabold" x-text="modalNamaPelanggan"></span>
                </h3>
                <button type="button" 
                        @click="executeModalOpen = false" 
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <form :action="'{{ url('/teknik/permintaan/up-downgrade') }}/' + modalKodeTrx + '/execute'" 
                  method="POST" 
                  enctype="multipart/form-data"
                  class="flex flex-col flex-1">
                @csrf

                <div class="p-5 space-y-4 text-xs">
                    
                    <!-- Customer Summary Info Card -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Nomor Internet:</span>
                            <span class="font-mono font-bold text-blue-500" x-text="modalNomorInternet"></span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block">Paket Saat Ini (Lama):</span>
                            <span class="font-bold text-slate-700" x-text="modalPaketLama"></span>
                        </div>
                    </div>

                    <!-- Dropdown Pilihan Layanan, Paket & Group Layanan (Exact Matching User Spec) -->
                    <div class="space-y-3.5 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        
                        <!-- Row 1: Layanan * & Paket * -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- 1. Layanan * -->
                            <div>
                                <label class="block font-medium text-slate-700 text-xs mb-1">
                                    Layanan <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <select x-model="modalKodeKategori"
                                        @change="onLayananChange()"
                                        name="kode_kategori_bandwith"
                                        required
                                        class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="">Pilih Layanan</option>
                                    @if(isset($layananKategoriList))
                                        @foreach($layananKategoriList as $kat)
                                            <option value="{{ $kat->kode_kategori_bandwith }}">
                                                 {{ $kat->nama_kategori_bandwith ?: ($kat->alias_nama_kategori ?: $kat->kode_kategori_bandwith) }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- 2. Paket * -->
                            <div>
                                <label class="block font-medium text-slate-700 text-xs mb-1">
                                    Paket <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <select x-model="modalKodeBandwithBaru"
                                        name="kode_bandwith_baru"
                                        required
                                        class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="">Pilih Paket Layanan</option>
                                    <template x-for="p in (filteredPaketList.length > 0 ? filteredPaketList : allPaketList)" :key="p.kode_bandwith">
                                        <option :value="p.kode_bandwith" 
                                                x-text="(p.nama_bandwith ? (p.nama_bandwith + ' - ') : '') + (p.nominal_bandwith ? (p.nominal_bandwith + ' Mbps') : '') + (p.harga_bandwith ? (' - Rp ' + Number(p.harga_bandwith).toLocaleString('id-ID')) : '')">
                                        </option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Row 2: Group Layanan -->
                        <div>
                            <label class="block font-medium text-slate-700 text-xs mb-1">
                                Group Layanan
                            </label>
                            <select x-model="modalGroupLayanan"
                                    name="group_layanan"
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">-- Pilih Group Layanan --</option>
                                <option value="MEDIANET">MEDIANET</option>
                                <option value="DNET">DNET</option>
                                <option value="CORPORATE">CORPORATE</option>
                            </select>
                        </div>

                    </div>

                    <!-- Tanggal Eksekusi -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Tanggal Eksekusi Perubahan Profil: <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="date_eksekusi" 
                               x-model="modalDateEksekusi" 
                               required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <!-- Catatan / Note Eksekusi -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Catatan Eksekusi Teknis:
                        </label>
                        <textarea name="note_eksekusi" 
                                  x-model="modalNoteEksekusi" 
                                  rows="2" 
                                  placeholder="Contoh: Profil paket pada MikroTik / OLT berhasil diubah ke 50 Mbps."
                                  class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                    </div>

                    <!-- Upload Foto Bukti Eksekusi (Screenshot Speedtest / Config / OLT) -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Foto Bukti Eksekusi / Screenshot:
                        </label>
                        <input type="file" 
                               name="foto_ss" 
                               x-ref="fileInput" 
                               accept="image/*" 
                               @change="onFotoChange($event)" 
                               class="hidden">

                        <!-- Dropzone Container (Clicking anywhere opens file picker) -->
                        <div @click="$refs.fileInput.click()"
                             @dragover.prevent
                             @drop.prevent="handleDrop($event)"
                             class="mt-1 flex flex-col items-center justify-center p-4 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl transition bg-slate-50/50 cursor-pointer group">
                            
                            <template x-if="!fotoPreview">
                                <div class="flex flex-col items-center text-center space-y-1.5 pointer-events-none">
                                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 text-emerald-500 flex items-center justify-center group-hover:scale-110 transition">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-emerald-500 group-hover:underline">
                                        Klik untuk memilih foto / screenshot bukti
                                    </span>
                                    <p class="text-[10px] text-slate-400">PNG, JPG, JPEG, WEBP (Maksimal 5MB)</p>
                                </div>
                            </template>

                            <template x-if="fotoPreview">
                                <div class="flex flex-col items-center gap-2 text-center w-full" @click.stop>
                                    <div class="relative rounded-lg overflow-hidden border border-slate-200 bg-white shadow-md">
                                        <img :src="fotoPreview" class="h-36 max-w-full object-contain rounded" alt="Preview Bukti">
                                        <button type="button" 
                                                @click.stop="fotoPreview = null; fotoFileName = ''; $refs.fileInput.value = ''"
                                                class="absolute top-1.5 right-1.5 p-1 bg-rose-600 hover:bg-rose-500 text-white rounded-full shadow transition"
                                                title="Hapus Foto">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-mono text-emerald-400 truncate max-w-xs" x-text="fotoFileName"></span>
                                        <button type="button" 
                                                @click.stop="$refs.fileInput.click()" 
                                                class="text-[11px] font-semibold text-blue-400 hover:underline">
                                            Ganti Foto
                                        </button>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>

                </div>

                <!-- Modal Actions -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" 
                            @click="executeModalOpen = false" 
                            class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition cursor-pointer">
                        Batal
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/25 transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>Eksekusi UP/Downgrade (KD13 Success)</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
    @endif

</div>
@endsection
