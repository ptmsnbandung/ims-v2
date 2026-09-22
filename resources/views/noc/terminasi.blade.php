@extends('layouts.app')

@section('title', 'Terminasi Layanan - NOC IMS')
@section('page_title', 'Terminasi Layanan')

@section('content')
<div x-data="{
    scheduleModalOpen: false,
    modalKodeTrx: '',
    modalNamaPelanggan: '',
    modalNomorInternet: '',
    openScheduleModal(kode, nama, nomor) {
        this.modalKodeTrx = kode;
        this.modalNamaPelanggan = nama;
        this.modalNomorInternet = nomor;
        this.scheduleModalOpen = true;
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

        <div class="overflow-x-auto">
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

                            <!-- 5. Action Column (Schedule Collect & Cancel Buttons) -->
                            <td class="py-4 px-4 align-top text-center">
                                @if(in_array($item->status_terminasi, ['11', '12', '12.1']))
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        
                                        <!-- Schedule Collect Button (Opens Schedule Collect Modal) -->
                                        <button type="button" 
                                                @click="modalKodeTrx = '{{ $item->kode_trx_terminasi }}'; modalNamaPelanggan = {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}; modalNomorInternet = '{{ $item->nomor_internet }}'; scheduleModalOpen = true;"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-blue-600 dark:bg-slate-800 dark:hover:bg-blue-600 text-blue-600 dark:text-blue-400 hover:text-white dark:hover:text-white text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                title="Jadwalkan Penarikan Perangkat">
                                            <svg class="w-3.5 h-3.5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                            <span>Schedule Collect</span>
                                        </button>

                                        <!-- Cancel Button -->
                                        <form action="{{ route('noc.terminasi.cancel', $item->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan terminasi {{ $item->nomor_internet }}?');">
                                            @csrf
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                    title="Batalkan Permintaan Terminasi">
                                                <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span>Cancel</span>
                                            </button>
                                        </form>

                                    </div>
                                @elseif($item->status_terminasi == '14')
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 text-[11px] font-bold">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Terminasi Selesai</span>
                                    </div>
                                @elseif($item->status_terminasi == '16')
                                    <div class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-200 dark:border-slate-500/20 text-[11px] font-medium">
                                        <span>Dibatalkan</span>
                                    </div>
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

        @if($terminasis->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                {{ $terminasis->links() }}
            </div>
        @endif

    </div>

    <!-- =================================================================== -->
    <!-- 4. MODAL FORM SCHEDULE COLLECT (COMPACT & TIDY DESIGN)              -->
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
                    <span>Form Schedule Collect An/</span>
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
                                           placeholder="Schedule Collect"
                                           class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <!-- Waktu -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        waktu <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <select name="waktu" required class="w-full text-xs px-2.5 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="" disabled selected>Select a State</option>
                                        <option value="09:00 - 12:00 WIB">09:00 - 12:00 (Pagi)</option>
                                        <option value="13:00 - 15:00 WIB">13:00 - 15:00 (Siang)</option>
                                        <option value="15:00 - 18:00 WIB">15:00 - 18:00 (Sore)</option>
                                        <option value="09:00 - 17:00 WIB">09:00 - 17:00 (Full Day)</option>
                                        <option value="Bebas / Fleksibel">Bebas / Fleksibel</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    note <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <textarea name="note" 
                                          rows="4" 
                                          required
                                          placeholder="note collect...." 
                                          class="w-full text-xs px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Right Column: Team Checkboxes (Strictly contained in scrollable box) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Team <span class="text-rose-500 font-bold">*</span>
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

                <!-- Footer Buttons (Cyan [✖ Tutup] & Blue [💾 Update]) -->
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" 
                            @click="scheduleModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#00bcd4] hover:bg-[#00acc1] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span>Tutup</span>
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                        <span>Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
