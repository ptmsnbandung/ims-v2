@extends('layouts.app')

@section('title', 'Tiket Gangguan & Ubah Password - IMS Router')
@section('page_title', 'Tiket Gangguan & Pengaduan')

@section('content')
<div class="space-y-5"
     x-data="{
         detailModalOpen: false,
         scheduleModalOpen: false,
         resolveModalOpen: false,
         cancelModalOpen: false,
         
         selectedTiket: null,
         modalId: '',
         modalKodeTiket: '',
         modalNomorInternet: '',
         modalNamaPelanggan: '',
         modalKeluhan: '',
         modalKatTiket: '',
         modalStatus: '',
         modalDateSchedule: '{{ date('Y-m-d') }}',
         modalTimeSchedule: '09:00 - 12:00 WIB',
         modalTeamTeknisi: '',
         modalSolusi: '',
         modalNoteCancel: '',

         openDetailModal(item) {
             this.selectedTiket = item;
             this.modalId = item.id_tiket || item.id || item.kode_trx_tiket;
             this.modalKodeTiket = item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalKeluhan = item.keluhan || '-';
             this.modalKatTiket = (item.kat_tiket == '12') ? 'Ubah Password' : 'Gangguan Layanan';
             this.modalStatus = item.status || '11';
             this.modalSolusi = item.solusi || '';
             this.modalTeamTeknisi = item.team_teknisi || '';
             this.detailModalOpen = true;
         },

         openScheduleModal(item) {
             this.modalId = item.id_tiket || item.id || item.kode_trx_tiket;
             this.modalKodeTiket = item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalKeluhan = item.keluhan || '';
             this.modalDateSchedule = item.date_schedule ? item.date_schedule.substring(0, 10) : '{{ date('Y-m-d') }}';
             this.modalTimeSchedule = item.time_schedule || '09:00 - 12:00 WIB';
             this.modalTeamTeknisi = item.team_teknisi || '';
             this.scheduleModalOpen = true;
         },

         openResolveModal(item) {
             this.modalId = item.id_tiket || item.id || item.kode_trx_tiket;
             this.modalKodeTiket = item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalSolusi = item.solusi || '';
             this.resolveModalOpen = true;
         },

         openCancelModal(item) {
             this.modalId = item.id_tiket || item.id || item.kode_trx_tiket;
             this.modalKodeTiket = item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalNoteCancel = '';
             this.cancelModalOpen = true;
         }
     }">

    <!-- Breadcrumbs & Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
            <span>IMS</span>
            <span>&gt;</span>
            @if(request('kategori') === 'ubah_password')
                <span class="text-blue-500 font-semibold">Ganti Password</span>
            @else
                <a href="{{ route('teknik.tiket') }}" class="hover:text-blue-400 transition">Tiket</a>
                <span>&gt;</span>
                <span class="text-blue-500 font-semibold">
                    @if(request('kategori') === 'gangguan')
                        Gangguan Layanan
                    @else
                        Tiket Gangguan
                    @endif
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2.5">
            <span class="text-slate-400">Role:</span>
            <span class="px-2.5 py-0.5 rounded-lg font-semibold {{ auth()->user()?->role_badge_classes ?? 'bg-blue-500/10 text-blue-400' }}">
                {{ auth()->user()?->nama_level ?? auth()->user()?->role?->label() ?? 'Staff' }}
            </span>

            @if(auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin']))
                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/25 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Buat Tiket</span>
                </button>
            @endif
        </div>
    </div>

    @if(request('kategori') === 'ubah_password')
        <div class="pt-1">
            <h2 class="text-base font-extrabold text-slate-800 dark:text-white">
                Tiket Permintaan Ganti Password
            </h2>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (EXACT MATCHING SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/10">
        <form method="GET" action="{{ route('teknik.tiket.gangguan') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Hidden Kategori if fixed -->
            @if(request('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
            @else
                <!-- 1. Dropdown Kategori / Layanan -->
                <div class="lg:col-span-3">
                    <select name="kategori" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">SEMUA KATEGORI TIKET</option>
                        <option value="gangguan" {{ request('kategori') === 'gangguan' ? 'selected' : '' }}>GANGGUAN LAYANAN</option>
                        <option value="ubah_password" {{ request('kategori') === 'ubah_password' ? 'selected' : '' }}>UBAH PASSWORD</option>
                        <option value="13" {{ request('kategori') === '13' ? 'selected' : '' }}>GANGGUAN FISIK / KABEL</option>
                        <option value="14" {{ request('kategori') === '14' ? 'selected' : '' }}>LAIN-LAIN</option>
                    </select>
                </div>
            @endif

            <!-- 2. Input Nama / Nomor Layanan / ID Tiket -->
            <div class="{{ request('kategori') ? 'lg:col-span-4' : 'lg:col-span-3' }}">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="nama/internet" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="{{ request('kategori') ? 'lg:col-span-3' : 'lg:col-span-2' }}">
                <input type="text" 
                       name="wilayah" 
                       value="{{ request('wilayah') }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="{{ request('kategori') ? 'lg:col-span-2' : 'lg:col-span-2' }}">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>ANTRIAN / Request</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>KONFIRMASI / On Schedule</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>SELESAI / Success</option>
                    <option value="14" {{ request('status') === '14' ? 'selected' : '' }}>BATAL / Canceled</option>
                </select>
            </div>

            <!-- 5. Action Buttons (Find, Reset & Export) -->
            <div class="lg:col-span-3 flex items-center gap-2">
                <button type="submit" 
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow-md shadow-cyan-600/20 transition cursor-pointer"
                        title="Cari Tiket">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <span>Find</span>
                </button>

                <a href="{{ route('teknik.tiket.gangguan', request('kategori') ? ['kategori' => request('kategori')] : []) }}" 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer"
                   title="Reset Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>

                <a href="{{ route('teknik.tiket.gangguan.export', request()->query()) }}" 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer"
                   title="Export CSV">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export</span>
                </a>
            </div>

        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 2. STATUS PILL 4 KPI BANNERS GRID (EXACT MATCHING SCREENSHOT)       -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- Card 1: (KD11) Request / Antrian : X User (Pink / Rose Gradient) -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '11'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '11' ? 'ring-2 ring-white/50 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD11) Antrian : {{ $count11 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 2: (KD12) On Schedule / Konfirmasi : X User (Gold / Yellow Gradient) -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '12'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '12' ? 'ring-2 ring-slate-900/50 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD12) Konfirmasi : {{ $count12 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 3: (KD13) Success / Selesai : X User (Teal / Emerald Gradient) -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '13'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-emerald-500 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '13' ? 'ring-2 ring-white/50 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD13) Selesai : {{ $count13 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 4: (KD14) Canceled / Batal : X User (Teal / Cyan Gradient) -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '14'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-teal-400 to-cyan-500 hover:from-teal-500 hover:to-cyan-600 text-white font-bold text-xs shadow-md shadow-teal-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '14' ? 'ring-2 ring-white/50 scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD14) Dibatalkan : {{ $count14 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF TIKET (MATCHING EXACT SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div>
                Show <span class="font-bold text-slate-800 dark:text-slate-200">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-800 dark:text-slate-200">{{ $tikets->total() }}</strong> Tiket
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        @if(request('kategori') === 'ubah_password')
                            <th class="py-3.5 px-4 min-w-[150px]">Tiket</th>
                            <th class="py-3.5 px-4 min-w-[280px]">Pelanggan</th>
                            <th class="py-3.5 px-4 min-w-[260px]">Info</th>
                            <th class="py-3.5 px-4 min-w-[240px]">Password</th>
                            <th class="py-3.5 px-4 min-w-[180px]">Status</th>
                        @else
                            <th class="py-3.5 px-4 min-w-[180px]">Customer</th>
                            <th class="py-3.5 px-4 min-w-[260px]">Address</th>
                            <th class="py-3.5 px-4 min-w-[140px]">Kategori</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Keluhan / Solusi</th>
                            <th class="py-3.5 px-4 min-w-[130px]">State</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($tikets as $item)
                        @php
                            $namaPel = ($item->nama_pelanggan ?? null) ?: (($item->batch_nama ?? null) ?: 'Pelanggan');
                            $nomorInternet = $item->nomor_internet ?? '-';
                            $alamat = ($item->alamat_pasang ?? null) ?: (($item->alamat_p ?? null) ?: '-');
                            $katText = (($item->kat_tiket ?? null) == '12') ? 'Ubah Password' : 'Gangguan Layanan';
                            $statusVal = (string) ($item->status ?? '11');
                            $kodeTiket = ($item->kode_trx_tiket ?? null) ?: (($item->id_tiket ?? null) ?: (($item->id ?? null) ?: '-'));
                            $userPppoe = ($item->user_pppoe ?? null) ?: $nomorInternet;
                            $passPppoe = ($item->pass_pppoe ?? null) ?: (($item->password ?? null) ?: '-');
                            $mediaAkses = ($item->media_akses ?? null) ?: 'FTTH';
                            $popName = ($item->nama_pop ?? null) ?: 'MediaNet FTTH (jaringan FTTH Media Solusi Network)';
                            $passLama = ($item->password_lama ?? null) ?: (($item->keluhan ?? null) ?: '-');
                            $passBaru = ($item->password_baru ?? null) ?: (($item->solusi ?? null) ?: 'tim customer care kami akan segera menghubungi anda');
                            $dateCreateFormatted = !empty($item->date_create) ? date('d F Y H:i', strtotime($item->date_create)) . ' WIB' : '-';
                            $dateUpdateFormatted = !empty($item->date_update) ? date('d F Y H:i', strtotime($item->date_update)) . ' WIB' : $dateCreateFormatted;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            
                            @if(request('kategori') === 'ubah_password')
                                <!-- 1. Tiket -->
                                <td class="py-4 px-4 align-top">
                                    <div class="font-mono font-bold text-slate-800 dark:text-slate-100 text-xs">
                                        #{{ $kodeTiket }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 font-sans">
                                        {{ $dateCreateFormatted }}
                                    </div>
                                </td>

                                <!-- 2. Pelanggan -->
                                <td class="py-4 px-4 align-top">
                                    <div class="font-bold text-slate-900 dark:text-white uppercase text-xs">
                                        <span class="font-mono text-blue-500 mr-1">{{ $nomorInternet }}</span>
                                        <span>{{ $namaPel }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 uppercase leading-relaxed font-sans">
                                        {{ $alamat }}
                                    </div>
                                </td>

                                <!-- 3. Info -->
                                <td class="py-4 px-4 align-top text-[11px] space-y-1">
                                    <div class="text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-400">User :</span> <strong class="font-mono text-blue-500">{{ $userPppoe }}</strong> 
                                        <span class="text-slate-400 ml-1.5">Pass :</span> <strong class="font-mono text-amber-500">{{ $passPppoe }}</strong>
                                    </div>
                                    <div class="text-slate-600 dark:text-slate-400">
                                        <span class="text-slate-400">MediaAkses :</span> <strong>{{ $mediaAkses }}</strong>
                                    </div>
                                    <div class="text-slate-600 dark:text-slate-400">
                                        <span class="text-slate-400">POP :</span> {{ $popName }}
                                    </div>
                                </td>

                                <!-- 4. Password -->
                                <td class="py-4 px-4 align-top text-[11px] space-y-1">
                                    <div>
                                        <span class="text-slate-400">Password Lama :</span>
                                        <div class="font-mono text-slate-800 dark:text-slate-200 mt-0.5">{{ $passLama }}</div>
                                    </div>
                                    <div class="pt-1">
                                        <span class="text-slate-400">password Baru :</span>
                                        <div class="font-medium text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $passBaru }}</div>
                                    </div>
                                </td>

                                <!-- 5. Status -->
                                <td class="py-4 px-4 align-top">
                                    <div class="flex flex-col gap-1">
                                        <div class="font-bold text-xs uppercase {{ $statusVal === '11' ? 'text-amber-500' : ($statusVal === '12' ? 'text-blue-400' : ($statusVal === '13' || $statusVal === 'Selesai' ? 'text-emerald-500' : 'text-slate-400')) }}">
                                            {{ $statusVal === '11' ? 'ANTRIAN' : ($statusVal === '12' ? 'KONFIRMASI PENANGANAN' : ($statusVal === '13' || $statusVal === 'Selesai' ? 'KONFIRMASI PENANGANAN' : 'DIBATALKAN')) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $dateUpdateFormatted }}
                                        </div>
                                        @if(!empty($item->user_update))
                                            <div class="text-[10px] text-slate-500 uppercase font-semibold">
                                                {{ $item->user_update }}
                                            </div>
                                        @endif

                                        <div class="pt-1.5 flex items-center gap-1.5">
                                            <button type="button"
                                                    @click="openDetailModal({{ json_encode($item) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-semibold text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                <span>Update</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>

                            @else
                                <!-- Standard Layout: Customer & No Layanan -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <span>{{ $namaPel }}</span>
                                    </div>
                                    <div class="text-[11px] font-mono text-blue-500 font-semibold mt-0.5">
                                        {{ $nomorInternet }}
                                    </div>
                                    @if(!empty($kodeTiket) && $kodeTiket !== '-')
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            #{{ $kodeTiket }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Address & Wilayah -->
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                    <div class="line-clamp-2 text-xs">
                                        {{ $alamat }}
                                    </div>
                                    @if(!empty($item->nama_pop))
                                        <div class="mt-1 text-[10px] inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400">
                                            <span>POP: {{ $item->nama_pop }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Kategori Tiket -->
                                <td class="py-3 px-4">
                                    @if(($item->kat_tiket ?? null) == '12')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                            </svg>
                                            <span>Ubah Password</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                            </svg>
                                            <span>Gangguan Layanan</span>
                                        </span>
                                    @endif
                                    @if(!empty($item->prioritas) && $item->prioritas !== 'Normal')
                                        <div class="mt-1">
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 font-bold">
                                                Prioritas: {{ $item->prioritas }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Keluhan / Catatan -->
                                <td class="py-3 px-4">
                                    <div class="text-xs text-slate-800 dark:text-slate-200 line-clamp-2">
                                        {{ $item->keluhan ?? '-' }}
                                    </div>
                                    @if(!empty($item->solusi))
                                        <div class="mt-1 text-[11px] text-emerald-400 font-medium line-clamp-1">
                                            Solusi: {{ $item->solusi }}
                                        </div>
                                    @endif
                                    @if(!empty($item->team_teknisi))
                                        <div class="mt-0.5 text-[10px] text-slate-400">
                                            Teknisi: {{ $item->team_teknisi }}
                                        </div>
                                    @endif
                                </td>

                                <!-- State / Status -->
                                <td class="py-3 px-4">
                                    @if($statusVal === '11')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                                            <span>(KD11) Request</span>
                                        </span>
                                    @elseif($statusVal === '12')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            <span>(KD12) On Schedule</span>
                                        </span>
                                    @elseif($statusVal === '13' || $statusVal === 'Selesai')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            <span>(KD13) Success</span>
                                        </span>
                                    @elseif($statusVal === '14' || $statusVal === 'Cancel' || $statusVal === 'Dibatalkan')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-500/15 text-slate-400 border border-slate-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>(KD14) Canceled</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-700 text-slate-300">
                                            {{ $statusVal }}
                                        </span>
                                    @endif
                                    <div class="text-[10px] text-slate-500 mt-1">
                                        {{ !empty($item->date_create) ? date('d M Y H:i', strtotime($item->date_create)) : '-' }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- View Detail Button -->
                                        <button type="button"
                                                @click="openDetailModal({{ json_encode($item) }})"
                                                class="p-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500 text-blue-400 hover:text-white transition"
                                                title="Lihat Detail Tiket">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>

                                        @if(auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin']))
                                            @if($statusVal === '11')
                                                <!-- Schedule / On Schedule Button -->
                                                <button type="button"
                                                        @click="openScheduleModal({{ json_encode($item) }})"
                                                        class="p-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500 text-amber-400 hover:text-slate-900 transition"
                                                        title="Jadwalkan Penanganan (KD12)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                                    </svg>
                                                </button>
                                            @endif

                                            @if($statusVal === '11' || $statusVal === '12')
                                                <!-- Resolve / Selesai Button -->
                                                <button type="button"
                                                        @click="openResolveModal({{ json_encode($item) }})"
                                                        class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white transition"
                                                        title="Selesaikan Tiket (KD13)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>

                                                <!-- Cancel Button -->
                                                <button type="button"
                                                        @click="openCancelModal({{ json_encode($item) }})"
                                                        class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white transition"
                                                        title="Batalkan Tiket (KD14)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                    </svg>
                                    <span>No data available in table</span>
                                    <span class="text-xs text-slate-500">Tidak ada tiket gangguan yang sesuai dengan filter pencarian.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
            <div>
                Showing {{ $tikets->firstItem() ?? 0 }} to {{ $tikets->lastItem() ?? 0 }} of {{ $tikets->total() }} entries
            </div>
            <div>
                {{ $tikets->links() }}
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 1: DETAIL TIKET                                               -->
    <!-- =================================================================== -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-white space-y-4"
             @click.away="detailModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <span>Detail Tiket Pengaduan</span>
                </h3>
                <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div class="space-y-3 text-xs">
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                    <span class="text-slate-400">ID / Kode Tiket:</span>
                    <span class="col-span-2 font-mono font-bold text-blue-400" x-text="modalKodeTiket"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                    <span class="text-slate-400">Nomor Internet:</span>
                    <span class="col-span-2 font-mono font-bold text-white" x-text="modalNomorInternet"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                    <span class="text-slate-400">Nama Pelanggan:</span>
                    <span class="col-span-2 font-semibold text-white" x-text="modalNamaPelanggan"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                    <span class="text-slate-400">Kategori Tiket:</span>
                    <span class="col-span-2 font-semibold text-indigo-400" x-text="modalKatTiket"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                    <span class="text-slate-400">Keluhan / Pesan:</span>
                    <span class="col-span-2 text-slate-200" x-text="modalKeluhan"></span>
                </div>
                <template x-if="modalSolusi">
                    <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Solusi / Penanganan:</span>
                        <span class="col-span-2 text-emerald-400" x-text="modalSolusi"></span>
                    </div>
                </template>
                <template x-if="modalTeamTeknisi">
                    <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Tim Teknisi:</span>
                        <span class="col-span-2 text-amber-400 font-semibold" x-text="modalTeamTeknisi"></span>
                    </div>
                </template>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 2: SCHEDULE PENANGANAN (KD12)                                 -->
    <!-- =================================================================== -->
    <div x-show="scheduleModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-white space-y-4"
             @click.away="scheduleModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                    <span>Jadwalkan Penanganan Tiket (KD12)</span>
                </h3>
                <button type="button" @click="scheduleModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <form :action="'{{ url('/teknik/tiket/gangguan') }}/' + modalId + '/schedule'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 bg-slate-800/50 rounded-xl space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Customer:</span>
                        <strong class="text-white" x-text="modalNamaPelanggan"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nomor Internet:</span>
                        <strong class="font-mono text-blue-400" x-text="modalNomorInternet"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Tanggal Jadwal Penanganan:</label>
                    <input type="date" name="date_schedule" x-model="modalDateSchedule" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-medium focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Waktu Penanganan:</label>
                    <input type="text" name="time_schedule" x-model="modalTimeSchedule" placeholder="Contoh: 09:00 - 12:00 WIB" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-medium focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Tim Teknisi / Personil:</label>
                    <input type="text" name="team_teknisi" x-model="modalTeamTeknisi" placeholder="Nama Teknisi / Tim yang ditugaskan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-medium focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Catatan / Detail Keluhan Tambahan:</label>
                    <textarea name="keluhan" x-model="modalKeluhan" rows="2" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="scheduleModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold shadow-lg shadow-amber-500/20">
                        Simpan Jadwal (KD12)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 3: SELESAIKAN TIKET (KD13)                                    -->
    <!-- =================================================================== -->
    <div x-show="resolveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-white space-y-4"
             @click.away="resolveModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                    <span>Selesaikan Tiket Gangguan (KD13)</span>
                </h3>
                <button type="button" @click="resolveModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <form :action="'{{ url('/teknik/tiket/gangguan') }}/' + modalId + '/resolve'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 bg-slate-800/50 rounded-xl space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Customer:</span>
                        <strong class="text-white" x-text="modalNamaPelanggan"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nomor Internet:</span>
                        <strong class="font-mono text-blue-400" x-text="modalNomorInternet"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Catatan Solusi / Tindakan Penyelesaian:</label>
                    <textarea name="solusi" x-model="modalSolusi" rows="3" placeholder="Jelaskan tindakan teknis yang telah dilakukan (contoh: Redaman diperbaiki dari -28dBm menjadi -19dBm / Kabel dropcore disambung ulang)" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" required></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="resolveModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-lg shadow-emerald-600/20">
                        Selesaikan Tiket (KD13)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 4: BATALKAN TIKET (KD14)                                      -->
    <!-- =================================================================== -->
    <div x-show="cancelModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-white space-y-4"
             @click.away="cancelModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    <span>Batalkan Tiket (KD14)</span>
                </h3>
                <button type="button" @click="cancelModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <form :action="'{{ url('/teknik/tiket/gangguan') }}/' + modalId + '/cancel'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 bg-slate-800/50 rounded-xl space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Customer:</span>
                        <strong class="text-white" x-text="modalNamaPelanggan"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nomor Internet:</span>
                        <strong class="font-mono text-blue-400" x-text="modalNomorInternet"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Alasan Pembatalan:</label>
                    <textarea name="note_cancel" x-model="modalNoteCancel" rows="3" placeholder="Tuliskan alasan pembatalan tiket (contoh: Pelanggan konfirmasi koneksi sudah normal / Tiket duplikat)" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-rose-500" required></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="cancelModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-lg shadow-rose-600/20">
                        Batalkan Tiket (KD14)
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
