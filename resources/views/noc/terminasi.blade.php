@extends('layouts.app')

@section('title', 'Terminasi Layanan - NOC IMS')
@section('page_title', 'Terminasi Layanan')

@section('content')
<div x-data="{
    scheduleModalOpen: false,
    reportModalOpen: false,
    closeModalOpen: false,
    modalKodeTrx: '',
    modalNamaPelanggan: '',
    modalNomorInternet: '',
    modalCollectPerangkat: 1,
    modalCollectPayment: 0,
    modalReportStatus: 'done',
    openScheduleModal(kode, nama, nomor) {
        this.modalKodeTrx = kode;
        this.modalNamaPelanggan = nama;
        this.modalNomorInternet = nomor;
        this.scheduleModalOpen = true;
    },
    openReportModal(kode, nama, nomor, collectP = 1, collectPay = 0) {
        this.modalKodeTrx = kode;
        this.modalNamaPelanggan = nama;
        this.modalNomorInternet = nomor;
        this.modalCollectPerangkat = collectP;
        this.modalCollectPayment = collectPay;
        this.modalReportStatus = 'done';
        this.reportModalOpen = true;
    },
    openCloseModal(kode, nama, nomor) {
        this.modalKodeTrx = kode;
        this.modalNamaPelanggan = nama;
        this.modalNomorInternet = nomor;
        this.closeModalOpen = true;
    }
}" class="space-y-5">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <span>IMS</span>
        <span>&gt;</span>
        <span class="text-blue-500 font-semibold">Terminasi</span>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (EXACT MATCHING SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/10">
        <form method="GET" action="{{ route('noc.terminasi') }}" class="space-y-3">
            
            <!-- Row 1: Layanan, Search, Wilayah, Status, Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                
                <!-- 1. Dropdown Semua Layanan -->
                <div class="lg:col-span-3">
                    <select name="layanan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">SEMUA LAYANAN</option>
                        @foreach($layananList as $lay)
                            <option value="{{ $lay }}" {{ $layanan === $lay ? 'selected' : '' }}>{{ strtoupper($lay) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Input Nama / Nomor Layanan -->
                <div class="lg:col-span-3">
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}"
                           placeholder="NAMA / NOMOR LAYANAN" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- 3. Dropdown / Input Semua Wilayah -->
                <div class="lg:col-span-2">
                    <input type="text" 
                           name="wilayah" 
                           value="{{ $wilayah }}"
                           placeholder="SEMUA WILAYAH" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- 4. Dropdown Semua Status -->
                <div class="lg:col-span-2">
                    <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">SEMUA STATUS</option>
                        <option value="11" {{ $status === '11' ? 'selected' : '' }}>(KD11) Req. Terminasi</option>
                        <option value="12" {{ $status === '12' ? 'selected' : '' }}>(KD12) Collecting</option>
                        <option value="12.1" {{ $status === '12.1' ? 'selected' : '' }}>(KD12.1) Reschedule Collecting</option>
                        <option value="13" {{ $status === '13' ? 'selected' : '' }}>(KD13) Collect Perangkat Done</option>
                        <option value="14" {{ $status === '14' ? 'selected' : '' }}>(KD14) Terminasi</option>
                        <option value="15" {{ $status === '15' ? 'selected' : '' }}>(KD15) Pending Terminasi</option>
                        <option value="16" {{ $status === '16' ? 'selected' : '' }}>(KD16) Cancel Terminasi</option>
                        <option value="17" {{ $status === '17' ? 'selected' : '' }}>(KD17) Req. Cancel Terminasi</option>
                    </select>
                </div>

                <!-- 5. Action Buttons (Reset & Export) -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <a href="{{ route('noc.terminasi') }}" 
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
                        <span>Cari</span>
                    </button>
                </div>

            </div>

            <!-- Row 2: Bulan & Tahun Filter (Matching Screenshot) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center pt-1 border-t border-slate-100 dark:border-slate-800/60">
                <div class="lg:col-span-3">
                    <select name="bulan" class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">SEMUA BULAN TERMINASI</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ strtoupper(\Carbon\Carbon::create(null, $m)->translatedFormat('F')) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="lg:col-span-3">
                    <select name="tahun" class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">SEMUA TAHUN</option>
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 2. STATUS PILL 8 KPI BANNERS GRID (2 ROWS X 4 COLS)                 -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        
        <!-- Row 1: KD11, KD12, KD12.1, KD13 -->
        <a href="{{ route('noc.terminasi', ['status' => '11']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD11) Req. Terminasi : {{ $count11 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('noc.terminasi', ['status' => '12']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD12) Collecting : {{ $count12 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('noc.terminasi', ['status' => '12.1']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD12.1) Reschedule Collecting : {{ $count12_1 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('noc.terminasi', ['status' => '13']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD13) Collect Perangkat Done : {{ $count13 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Row 2: KD14, KD15, KD16, KD17 -->
        <a href="{{ route('noc.terminasi', ['status' => '14']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-emerald-500 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD14) Terminasi : {{ $count14 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('noc.terminasi', ['status' => '15']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD15) Pending Terminasi : {{ $count15 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('noc.terminasi', ['status' => '16']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-teal-400 to-cyan-500 hover:from-teal-500 hover:to-cyan-600 text-white font-bold text-xs shadow-md shadow-teal-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD16) Cancel Terminasi : {{ $count16 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('noc.terminasi', ['status' => '17']) }}" 
           class="p-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD17) Req. Cancel Terminasi : {{ $count17 }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF TERMINATED CUSTOMERS WITH SCHEDULE / CANCEL ACTIONS     -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div>
                show <span class="font-bold text-slate-800 dark:text-slate-200">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-800 dark:text-slate-200">{{ $terminasis->total() }}</strong> Data
            </div>
        </div>

        <div class="ims-desktop-only hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-4 min-w-[200px]">Customer</th>
                        <th class="py-3.5 px-4 min-w-[280px]">Info</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Detail</th>
                        <th class="py-3.5 px-4 min-w-[150px]">State</th>
                        <th class="py-3.5 px-4 text-center min-w-[140px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($terminasis as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            
                            <!-- 1. Customer Column -->
                            <td class="py-4 px-4 align-top">
                                <!-- Kode TRX -->
                                <div class="font-mono text-[10px] text-slate-400 font-semibold">
                                    {{ $item->kode_trx_terminasi }}
                                </div>

                                <!-- ID Pelanggan Link -->
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline tracking-wide text-xs inline-block mt-0.5"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>

                                <!-- Nama & Gender -->
                                <div class="font-bold text-slate-800 dark:text-slate-200 uppercase text-xs mt-0.5">
                                    <span>{{ $item->nama_pelanggan }}</span>
                                    <span class="text-slate-500 font-normal">
                                        ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>

                                <!-- Bandwidth -->
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium uppercase mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }}
                                    @if($item->nominal_bandwith)
                                        <span>{{ $item->nominal_bandwith }} Mbps</span>
                                    @endif
                                </div>

                                <!-- Timestamp -->
                                <div class="font-mono text-[10px] text-slate-400 mt-1">
                                    {{ $item->date_create ? \Carbon\Carbon::parse($item->date_create)->format('Y-m-d H:i:s') : '-' }}
                                </div>
                            </td>

                            <!-- 2. Info Column (Building, Address, Contact) -->
                            <td class="py-4 px-4 align-top text-xs space-y-1">
                                <div class="font-bold text-slate-800 dark:text-slate-200 uppercase text-[11px]">
                                    {{ $item->jenis_bangunan ?: 'RUMAH-PRIBADI' }}
                                </div>
                                <div class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
                                    {{ $item->alamat_p ?: '-' }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                                    <span>HP : <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ $item->nomor_hp ?: '-' }}</strong></span>
                                    @if($item->email)
                                        <span class="block text-[10px] text-slate-400 truncate max-w-[240px]">Email : {{ $item->email }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. Detail Column (Collect Perangkat & Pending Tagihan Badges) -->
                            <td class="py-4 px-4 align-top text-[11px] space-y-1.5 font-medium">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-slate-600 dark:text-slate-400">Collect Perangkat :</span>
                                    @if($item->collect_perangkat == 1)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/20">Done &#10004;</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/20">Undone &#128274;</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-slate-600 dark:text-slate-400">Pending Tagihan :</span>
                                    @if($item->collect_payment == 1)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/20">Done &#10004;</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/20">Undone &#128274;</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 4. State Column -->
                            <td class="py-4 px-4 align-top">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold font-mono tracking-wide
                                    @if(in_array($item->status_terminasi, ['11', '12', '12.1']))
                                        bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30
                                    @elseif(in_array($item->status_terminasi, ['13', '15', '17']))
                                        bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30
                                    @elseif(in_array($item->status_terminasi, ['14', '16']))
                                        bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30
                                    @else
                                        bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-500/15 dark:text-slate-400 dark:border-slate-500/30
                                    @endif">
                                    (KD{{ $item->status_terminasi }}) {{ $item->desc_terminasi ?: 'Req. Terminasi' }}
                                </span>
                                <div class="font-mono text-[10px] text-slate-400 mt-1.5">
                                    {{ $item->date_create ? \Carbon\Carbon::parse($item->date_create)->format('Y-m-d H:i:s') : '-' }}
                                </div>
                            </td>

                            <!-- 5. Action Column (Status-based Actions) -->
                            <td class="py-3 px-3 align-top text-center min-w-[150px] w-[150px]">
                                @if(in_array($item->status_terminasi, ['11']))
                                    {{-- Status KD11: Req. Terminasi -> Schedule Collect --}}
                                    <div class="flex flex-col items-center justify-center gap-1.5 w-full max-w-[140px] mx-auto">
                                        <button type="button" 
                                                @click="openScheduleModal('{{ $item->kode_trx_terminasi }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, '{{ $item->nomor_internet }}')"
                                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 active:scale-98 text-white text-[11px] font-bold shadow-xs transition cursor-pointer"
                                                title="Jadwalkan Penarikan Perangkat (KD12)">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                            <span>Schedule Collect</span>
                                        </button>

                                        <form action="{{ route('noc.terminasi.cancel', $item->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan terminasi {{ $item->nomor_internet }}?');" class="w-full">
                                            @csrf
                                            <button type="submit" 
                                                    class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 dark:bg-slate-800/80 dark:hover:bg-rose-950/40 dark:text-slate-400 dark:hover:text-rose-400 text-[10px] font-semibold border border-slate-200 dark:border-slate-700/80 transition cursor-pointer"
                                                    title="Batalkan Permintaan Terminasi">
                                                <svg class="w-3 h-3 text-slate-400 hover:text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span>Cancel</span>
                                            </button>
                                        </form>
                                    </div>

                                @elseif(in_array($item->status_terminasi, ['12', '12.1']))
                                    {{-- Status KD12 / KD12.1: Collecting -> Report Collecting --}}
                                    <div class="flex flex-col items-center justify-center gap-1.5 w-full max-w-[140px] mx-auto">
                                        <button type="button" 
                                                @click="openReportModal('{{ $item->kode_trx_terminasi }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, '{{ $item->nomor_internet }}', {{ $item->collect_perangkat ? 1 : 0 }}, {{ $item->collect_payment ? 1 : 0 }})"
                                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 active:scale-98 text-white text-[11px] font-bold shadow-xs transition cursor-pointer"
                                                title="Laporkan Hasil Penarikan Perangkat (KD13)">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                            </svg>
                                            <span>Report Collecting</span>
                                        </button>

                                        <form action="{{ route('noc.terminasi.cancel', $item->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan terminasi {{ $item->nomor_internet }}?');" class="w-full">
                                            @csrf
                                            <button type="submit" 
                                                    class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 dark:bg-slate-800/80 dark:hover:bg-rose-950/40 dark:text-slate-400 dark:hover:text-rose-400 text-[10px] font-semibold border border-slate-200 dark:border-slate-700/80 transition cursor-pointer"
                                                    title="Batalkan Permintaan Terminasi">
                                                <svg class="w-3 h-3 text-slate-400 hover:text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span>Cancel</span>
                                            </button>
                                        </form>
                                    </div>

                                @elseif($item->status_terminasi == '13')
                                    {{-- Status KD13: Collect Perangkat Done -> Closing Terminasi --}}
                                    <div class="flex flex-col items-center justify-center gap-1.5 w-full max-w-[140px] mx-auto">
                                        <button type="button" 
                                                @click="openCloseModal('{{ $item->kode_trx_terminasi }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, '{{ $item->nomor_internet }}')"
                                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:scale-98 text-white text-[11px] font-bold shadow-xs transition cursor-pointer"
                                                title="Closing / Selesaikan Terminasi Layanan (KD14)">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            <span>Closing Terminasi</span>
                                        </button>

                                        <form action="{{ route('noc.terminasi.cancel', $item->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan terminasi {{ $item->nomor_internet }}?');" class="w-full">
                                            @csrf
                                            <button type="submit" 
                                                    class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 dark:bg-slate-800/80 dark:hover:bg-rose-950/40 dark:text-slate-400 dark:hover:text-rose-400 text-[10px] font-semibold border border-slate-200 dark:border-slate-700/80 transition cursor-pointer"
                                                    title="Batalkan Permintaan Terminasi">
                                                <svg class="w-3 h-3 text-slate-400 hover:text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span>Cancel</span>
                                            </button>
                                        </form>
                                    </div>

                                @elseif($item->status_terminasi == '14')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] font-bold">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        <span>Terminasi Selesai</span>
                                    </span>

                                @elseif($item->status_terminasi == '16')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 text-[11px] font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 18 6M6 6l12 12"/></svg>
                                        <span>Dibatalkan</span>
                                    </span>

                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400 text-xs">
                                Tidak ada data permintaan terminasi yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List View -->
        <div class="ims-mobile-only block md:hidden p-3 sm:p-4 space-y-3.5 divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($terminasis as $item)
                <div class="pt-3.5 first:pt-0 space-y-3">
                    <!-- Top Info: Kode & Status -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-[10px] text-slate-400 font-semibold block">{{ $item->kode_trx_terminasi }}</span>
                            <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                               class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline text-sm inline-block">
                                {{ $item->nomor_internet }}
                            </a>
                            <div class="font-bold text-slate-800 dark:text-slate-100 uppercase text-xs mt-0.5">
                                {{ $item->nama_pelanggan }} 
                                <span class="text-slate-500 font-normal">({{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }})</span>
                            </div>
                        </div>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold font-mono tracking-wide shrink-0
                            @if(in_array($item->status_terminasi, ['11', '12', '12.1']))
                                bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30
                            @elseif(in_array($item->status_terminasi, ['13', '15', '17']))
                                bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30
                            @elseif(in_array($item->status_terminasi, ['14', '16']))
                                bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30
                            @else
                                bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                            @endif">
                            (KD{{ $item->status_terminasi }}) {{ $item->desc_terminasi ?: 'Req. Terminasi' }}
                        </span>
                    </div>

                    <!-- Meta Details -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200/70 dark:border-slate-800/60 text-xs space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Bandwidth:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }} {{ $item->nominal_bandwith ? $item->nominal_bandwith . ' Mbps' : '' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Collect Perangkat:</span>
                            @if($item->collect_perangkat == 1)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Done &#10004;</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400">Undone &#128274;</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Pending Tagihan:</span>
                            @if($item->collect_payment == 1)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Done &#10004;</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400">Undone &#128274;</span>
                            @endif
                        </div>
                        <div class="pt-1 border-t border-slate-200/50 dark:border-slate-800/50 text-[11px]">
                            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ $item->alamat_p ?: '-' }}</p>
                            @if($item->nomor_hp)
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="text-slate-500">HP:</span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $item->nomor_hp) }}" target="_blank" class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">
                                        {{ $item->nomor_hp }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Mobile Actions -->
                    <div class="flex items-center gap-2 pt-1">
                        @if($item->status_terminasi == '11')
                            <button type="button" 
                                    @click="openScheduleModal('{{ $item->kode_trx_terminasi }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, '{{ $item->nomor_internet }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition active:scale-98">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <span>Schedule Collect</span>
                            </button>
                            <form action="{{ route('noc.terminasi.cancel', $item->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan terminasi {{ $item->nomor_internet }}?');">
                                @csrf
                                <button type="submit" 
                                        class="py-2 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-xs font-semibold transition active:scale-98">
                                    Cancel
                                </button>
                            </form>
                        @elseif(in_array($item->status_terminasi, ['12', '12.1']))
                            <button type="button" 
                                    @click="openReportModal('{{ $item->kode_trx_terminasi }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, '{{ $item->nomor_internet }}', {{ $item->collect_perangkat ? 1 : 0 }}, {{ $item->collect_payment ? 1 : 0 }})"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-sm transition active:scale-98">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                <span>Report Collecting</span>
                            </button>
                            <form action="{{ route('noc.terminasi.cancel', $item->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan terminasi {{ $item->nomor_internet }}?');">
                                @csrf
                                <button type="submit" 
                                        class="py-2 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-xs font-semibold transition active:scale-98">
                                    Cancel
                                </button>
                            </form>
                        @elseif($item->status_terminasi == '13')
                            <button type="button" 
                                    @click="openCloseModal('{{ $item->kode_trx_terminasi }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, '{{ $item->nomor_internet }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition active:scale-98">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <span>Closing Terminasi</span>
                            </button>
                        @elseif($item->status_terminasi == '14')
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Terminasi Selesai
                            </span>
                        @elseif($item->status_terminasi == '16')
                            <span class="text-xs font-medium text-rose-500 dark:text-rose-400">
                                Dibatalkan
                            </span>
                        @else
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Status: {{ $item->desc_terminasi ?: 'Dalam Proses' }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    Tidak ada data permintaan terminasi.
                </div>
            @endforelse
        </div>

        @if($terminasis->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                {{ $terminasis->links() }}
            </div>
        @endif

    </div>

    <!-- =================================================================== -->
    <!-- 4. MODAL 1: SCHEDULE COLLECT (STATUS KD11 -> KD12)                  -->
    <!-- =================================================================== -->
    <div x-show="scheduleModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="scheduleModalOpen = false"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col"
             style="max-height: 90vh;">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    <span>Schedule Collect An/</span>
                    <span class="text-blue-600 dark:text-blue-400 font-extrabold uppercase" x-text="modalNamaPelanggan"></span>
                </h3>
                <button type="button" @click="scheduleModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="'{{ url('/noc/terminasi') }}/' + modalKodeTrx + '/schedule'" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="p-5 space-y-4 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                        
                        <!-- Left Column: Date Schedule, Waktu, Note -->
                        <div class="space-y-3.5">
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Date Schedule -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Date Schedule <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <input type="date" 
                                           name="date_schedule" 
                                           required
                                           value="{{ date('Y-m-d') }}"
                                           class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <!-- Waktu -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Waktu <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <select name="waktu" required class="w-full text-xs px-2.5 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="09:00 - 12:00 WIB">09:00 - 12:00 (Pagi)</option>
                                        <option value="13:00 - 15:00 WIB">13:00 - 15:00 (Siang)</option>
                                        <option value="15:00 - 18:00 WIB">15:00 - 18:00 (Sore)</option>
                                        <option value="09:00 - 17:00 WIB" selected>09:00 - 17:00 (Full Day)</option>
                                        <option value="Bebas / Fleksibel">Bebas / Fleksibel</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Catatan / Instruksi <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <textarea name="note" 
                                          rows="4" 
                                          required
                                          placeholder="Catatan penugasan collect perangkat..." 
                                          class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Right Column: Team Checkboxes -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Team Teknisi <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-y-auto space-y-1"
                                 style="max-height: 180px;">
                                @if(isset($karyawans) && count($karyawans) > 0)
                                    @foreach($karyawans as $k)
                                    <label class="flex items-center gap-2 p-1 rounded hover:bg-slate-200/50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                                        <input type="checkbox" 
                                               name="team[]" 
                                               value="{{ $k->nama_karyawan }}" 
                                               class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 cursor-pointer shrink-0">
                                        <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300 uppercase leading-tight truncate">
                                            {{ $k->nama_karyawan }}
                                        </span>
                                    </label>
                                    @endforeach
                                @else
                                    <span class="text-xs text-slate-400">Tidak ada data teknisi aktif.</span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" 
                            @click="scheduleModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer">
                        <span>Tutup</span>
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span>Simpan Jadwal Collect</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. MODAL 2: REPORT COLLECTING (STATUS KD12 / KD12.1 -> KD13)       -->
    <!-- =================================================================== -->
    <div x-show="reportModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="reportModalOpen = false"
             class="relative w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col"
             style="max-height: 90vh;">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                    <span>Report Collecting An/</span>
                    <span class="text-purple-600 dark:text-purple-400 font-extrabold uppercase" x-text="modalNamaPelanggan"></span>
                </h3>
                <button type="button" @click="reportModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="'{{ url('/noc/terminasi') }}/' + modalKodeTrx + '/report'" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="p-5 space-y-4 overflow-y-auto flex-1">
                    
                    <!-- 1. Pilihan Hasil Collecting -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Hasil Penarikan (Collecting Result) <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition"
                                   :class="modalReportStatus === 'done' ? 'bg-purple-50 dark:bg-purple-950/40 border-purple-500 ring-1 ring-purple-500' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700'">
                                <input type="radio" name="status_result" value="done" x-model="modalReportStatus" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                         :class="modalReportStatus === 'done' ? 'border-purple-600 bg-purple-600' : 'border-slate-400'">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="modalReportStatus === 'done'"></div>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Selesai Ditarik (Done)</span>
                                        <span class="block text-[10px] text-slate-500">Status lanjut ke KD13</span>
                                    </div>
                                </div>
                            </label>

                            <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition"
                                   :class="modalReportStatus === 'reschedule' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-500 ring-1 ring-amber-500' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700'">
                                <input type="radio" name="status_result" value="reschedule" x-model="modalReportStatus" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                         :class="modalReportStatus === 'reschedule' ? 'border-amber-600 bg-amber-600' : 'border-slate-400'">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="modalReportStatus === 'reschedule'"></div>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Reschedule Ulang</span>
                                        <span class="block text-[10px] text-slate-500">Status KD12.1</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Tanggal & Jam Penarikan (Grid 2 Kolom) -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                <span x-text="modalReportStatus === 'done' ? 'Tanggal Penarikan' : 'Tanggal Reschedule Baru'"></span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   name="date_collect" 
                                   required
                                   value="{{ date('Y-m-d') }}"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Jam / Waktu -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                <span x-text="modalReportStatus === 'done' ? 'Jam Penarikan' : 'Jam Reschedule'"></span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="time" 
                                   name="time_collect" 
                                   required
                                   value="{{ date('H:i') }}"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>

                    <!-- 3. Keterangan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Keterangan <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <textarea name="note" 
                                  rows="3" 
                                  required
                                  placeholder="Contoh: Perangkat ONT ZTE & adaptor berhasil ditarik lengkap..." 
                                  class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"></textarea>
                    </div>

                </div>

                <!-- Footer Buttons -->
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" 
                            @click="reportModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer">
                        <span>Tutup</span>
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span>Simpan Laporan Collecting</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. MODAL 3: CLOSING TERMINASI (STATUS KD13 -> KD14)                 -->
    <!-- =================================================================== -->
    <div x-show="closeModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="closeModalOpen = false"
             class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col"
             style="max-height: 90vh;">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-emerald-50/80 dark:bg-emerald-950/40 shrink-0">
                <h3 class="text-sm font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <span>Closing Terminasi An/</span>
                    <span class="text-emerald-700 dark:text-emerald-400 font-extrabold uppercase" x-text="modalNamaPelanggan"></span>
                </h3>
                <button type="button" @click="closeModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="'{{ url('/noc/terminasi') }}/' + modalKodeTrx + '/close'" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="p-5 space-y-4 overflow-y-auto flex-1">
                    
                    <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 text-xs text-emerald-800 dark:text-emerald-300 space-y-1.5">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Konfirmasi Closing Terminasi & Hapus Router
                        </p>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                            Proses ini akan <strong>menghapus user PPPoE secara permanen dari router MikroTik</strong>, memutus sesi koneksi aktif seketika, mengubah status pelanggan menjadi <strong>Nonaktif (#23)</strong>, serta menyelesaikan tiket terminasi <strong>(KD14 Terminasi Selesai)</strong>.
                        </p>
                    </div>

                    <!-- Catatan Closing -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Catatan Closing Terminasi (Opsional)
                        </label>
                        <textarea name="note" 
                                  rows="3" 
                                  placeholder="Catatan penutupan layanan..." 
                                  class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                    </div>

                </div>

                <!-- Footer Buttons -->
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" 
                            @click="closeModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer">
                        <span>Batal</span>
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        <span>Closing Terminasi Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
