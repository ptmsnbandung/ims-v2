@extends('layouts.app')

@section('title', 'Ubah Layanan (UP / Downgrade) - IMS Router')
@section('page_title', 'Ubah Layanan (UP / Downgrade)')

@section('content')
<div class="space-y-5"
     x-data="{
         scheduleModalOpen: false,
         modalKodeTrx: '',
         modalNomorInternet: '',
         modalNamaPelanggan: '',
         modalPaketBaru: '',
         modalDateSchedule: '{{ date('Y-m-d') }}',
         modalNoteSchedule: '',

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
         }
     }">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <span>IMS</span>
        <span>&gt;</span>
        <span class="text-blue-500 font-semibold">Ubah Layanan</span>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (EXACT MATCHING SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/10">
        <form method="GET" action="{{ route('teknik.permintaan.up-downgrade') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- 1. Dropdown Semua Layanan -->
            <div class="lg:col-span-3">
                <select name="layanan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="lg:col-span-2">
                <input type="text" 
                       name="wilayah" 
                       value="{{ request('wilayah') }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="lg:col-span-2">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
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
    <!-- 2. STATUS PILL 4 KPI BANNERS GRID (MATCHING SCREENSHOT)             -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- Card 1: (KD11) Request : X User (Pink / Rose Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '11']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD11) Request : {{ $count11 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 2: (KD12) On Schedule : X User (Gold / Yellow Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '12']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD12) On Schedule : {{ $count12 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 3: (KD13) Success : X User (Teal / Emerald Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '13']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-emerald-500 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD13) Success : {{ $count13 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 4: (KD14) Canceled : X User (Teal / Cyan Gradient) -->
        <a href="{{ route('teknik.permintaan.up-downgrade', ['status' => '14']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-teal-400 to-cyan-500 hover:from-teal-500 hover:to-cyan-600 text-white font-bold text-xs shadow-md shadow-teal-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide text-[11px]">(KD14) Canceled : {{ $count14 ?? 0 }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF UP / DOWNGRADE CUSTOMERS                                -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div>
                show <span class="font-bold text-slate-800 dark:text-slate-200">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-800 dark:text-slate-200">{{ $ubahLayanans->total() }}</strong> Data
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-4 min-w-[180px]">Customer</th>
                        <th class="py-3.5 px-4 min-w-[280px]">Address</th>
                        <th class="py-3.5 px-4 min-w-[120px]">Old</th>
                        <th class="py-3.5 px-4 min-w-[140px]">New</th>
                        <th class="py-3.5 px-4 min-w-[130px]">State</th>
                        <th class="py-3.5 px-4 text-center min-w-[130px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($ubahLayanans as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            
                            <!-- 1. Customer Column -->
                            <td class="py-4 px-4 align-top">
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline tracking-wide text-xs block"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>

                                <div class="font-bold text-slate-800 dark:text-slate-200 uppercase text-xs mt-0.5">
                                    <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" class="hover:text-blue-500 transition">
                                        {{ $item->nama_pelanggan }}
                                    </a>
                                    <span class="text-slate-500 font-normal">
                                        ( {{ ($item->jenis_kelamin ?? null) == 1 ? 'L' : (($item->jenis_kelamin ?? null) == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Address Column -->
                            <td class="py-4 px-4 align-top text-xs space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 uppercase text-[11px]">
                                        {{ $item->jenis_bangunan ?? 'RUMAH-PRIBADI' }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                        Aktif
                                    </span>
                                </div>
                                <div class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
                                    {{ $item->alamat_p ?? ($item->alamat_pasang ?? '-') }}
                                </div>
                            </td>

                            <!-- 3. Old Package Column -->
                            <td class="py-4 px-4 align-top">
                                <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ $item->nama_kategori_bandwith_lama ?: 'BROADBAND' }}
                                </div>
                                <div class="text-blue-600 dark:text-blue-400 font-bold underline font-mono text-[11px] mt-0.5">
                                    {{ $item->nominal_bandwith_lama ?: '10' }} Mbps
                                </div>
                            </td>

                            <!-- 4. New Package Column -->
                            <td class="py-4 px-4 align-top">
                                <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ $item->nama_kategori_bandwith_baru ?: 'BROADBAND FREE' }}
                                </div>
                                <div class="text-slate-600 dark:text-slate-300 font-medium font-mono text-[11px] mt-0.5">
                                    {{ $item->nominal_bandwith_baru ?: '10' }} Mbps
                                </div>
                            </td>

                            <!-- 5. State Column -->
                            <td class="py-4 px-4 align-top">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono tracking-wide
                                    @if(in_array($item->status_ubah_layanan, ['11']))
                                        bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30
                                    @elseif(in_array($item->status_ubah_layanan, ['12']))
                                        bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30
                                    @elseif(in_array($item->status_ubah_layanan, ['13']))
                                        bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30
                                    @elseif(in_array($item->status_ubah_layanan, ['14']))
                                        bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                                    @else
                                        bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                                    @endif">
                                    {{ $item->desc_ubah_layanan ?: 'Request' }}
                                </span>
                                <div class="font-mono text-[10px] text-slate-400 mt-1">
                                    {{ $item->date_create ? \Carbon\Carbon::parse($item->date_create)->translatedFormat('d F Y') : ($item->date_request ? \Carbon\Carbon::parse($item->date_request)->translatedFormat('d F Y') : '-') }}
                                </div>
                            </td>

                            <!-- 6. Action Column (Schedule & Canceled Buttons) -->
                            <td class="py-4 px-4 align-top text-center">
                                @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                                    @if(in_array($item->status_ubah_layanan, ['11', '12']))
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            
                                            <!-- Open Schedule Modal Button -->
                                            <button type="button" 
                                                    @click="openScheduleModal({{ json_encode($item) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-blue-600 dark:bg-slate-800 dark:hover:bg-blue-600 text-blue-600 dark:text-blue-400 hover:text-white dark:hover:text-white text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                    title="Jadwalkan Ubah Layanan">
                                                <svg class="w-3.5 h-3.5 text-blue-500 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                </svg>
                                                <span>Schedule</span>
                                            </button>

                                            <!-- Cancel Button -->
                                            <form action="{{ route('teknik.permintaan.up-downgrade.cancel', $item->kode_trx_ubah_layanan) }}" method="POST" onsubmit="return confirm('Batalkan permohonan ubah layanan {{ $item->nomor_internet }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                        title="Batalkan Permintaan">
                                                    <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Canceled</span>
                                                </button>
                                            </form>

                                        </div>
                                    @elseif($item->status_ubah_layanan == '13')
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] font-bold">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>Berhasil Diubah</span>
                                        </div>
                                    @elseif($item->status_ubah_layanan == '14')
                                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-500/20 text-[11px] font-medium">
                                            <span>Dibatalkan</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                @else
                                    <!-- Role Read-only mode -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-[11px] font-medium border border-slate-200 dark:border-slate-700/60">
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
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        
        <div @click.away="scheduleModalOpen = false"
             class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col">
            
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-950/80 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5 truncate">
                    <span>Form Schedule Ubah Layanan An/</span>
                    <span class="text-blue-600 dark:text-blue-400 uppercase font-extrabold" x-text="modalNamaPelanggan"></span>
                </h3>
                <button type="button" 
                        @click="scheduleModalOpen = false" 
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
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
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Permintaan Layanan Baru</span>
                        </div>

                        <!-- Big Card Center: BROADBAND FREE 10 Mbps -->
                        <div class="p-6 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1e293b] dark:text-white tracking-wide uppercase font-sans" 
                                x-text="modalPaketBaru">
                                BROADBAND FREE 10 Mbps
                            </h2>
                        </div>
                    </div>

                    <!-- Input Fields Card Container -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-3.5">
                        
                        <!-- 1. Schedule Update (Tanggal Reschedule) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Schedule Update <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   name="date_schedule" 
                                   x-model="modalDateSchedule" 
                                   required 
                                   placeholder="Tanggal Reschedule"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- 2. Note (Catatan Schedule) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                note <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <textarea name="note_schedule" 
                                      x-model="modalNoteSchedule" 
                                      rows="3" 
                                      required
                                      placeholder="catatan schedule"
                                      class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>

                    </div>
                </div>

                <!-- Modal Actions: Tutup (Cyan) & Update (Blue) -->
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    
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
                        <span>Update</span>
                    </button>

                </div>
            </form>

        </div>
    </div>
    @endif

</div>
@endsection
