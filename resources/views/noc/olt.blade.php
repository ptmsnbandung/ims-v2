@extends('layouts.app')

@section('title', 'Master Data Optical Line Terminal (OLT) - NOC IMS')
@section('page_title', 'Master Data OLT')

@section('content')
<div class="space-y-6"
     x-data="{
        deleteModalOpen: false,
        oltToDelete: null,
        deleteActionUrl: '',
        
        confirmDelete(kodeOlt, nameOlt) {
            this.oltToDelete = { kode: kodeOlt, name: nameOlt };
            this.deleteActionUrl = '{{ url('/noc/olt') }}/' + encodeURIComponent(kodeOlt) + '/delete';
            this.deleteModalOpen = true;
        }
     }">

    <!-- Top Alert Notification if any -->
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-semibold shadow-lg shadow-emerald-500/5">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 text-xs font-semibold shadow-lg shadow-rose-500/5">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER (EXACT MOCKUP DESIGN) -->
    <!-- =================================================================== -->
    <div class="relative overflow-hidden rounded-2xl bg-[#091e42] border border-blue-900/40 p-5 sm:p-6 shadow-2xl backdrop-blur-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 relative z-10">
            <div class="flex items-start sm:items-center gap-3.5">
                <!-- Icon OLT Server -->
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 border border-blue-400/30 text-blue-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a3 3 0 0 1 2.4-1.35h7.7a3 3 0 0 1 2.4 1.35l2.1 3.15a4.5 4.5 0 0 1 .9 2.7m-13.5 0h13.5" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-white tracking-tight">
                        Master Data Optical Line Terminal (OLT)
                    </h2>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Manajemen perangkat OLT pusat, IP Host, alokasi port PON, dan status distribusi jaringan FTTH.
                    </p>
                    
                    <!-- Stats Badges Bar -->
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6" />
                            </svg>
                            <span>Total OLT: {{ $totalOlt }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                            <span>Total PON Port: {{ $totalPonPort }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Status: Online</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Action Button -->
            <div class="flex items-center gap-2">
                <a href="{{ route('noc.olt.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>+ Tambah OLT Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. TABLE MASTER DATA OLT -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/5 overflow-hidden">
        
        <!-- Search Bar Top Right -->
        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-b border-slate-100 dark:border-slate-800">
            <form method="GET" action="{{ route('noc.olt') }}" class="relative w-full sm:w-72">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Search..." 
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            </form>
        </div>

        <!-- Table Responsive Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#0b192e] text-slate-200 text-[11px] font-bold uppercase tracking-wider border-b border-slate-800">
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>KODE OLT</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>NAMA OLT</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">POP SERVER</th>
                        <th class="py-3.5 px-5">IP ADDRESS</th>
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>KAPASITAS</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">STATUS PON AKTIF</th>
                        <th class="py-3.5 px-5 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900/40">
                    @forelse($olts as $olt)
                        @php
                            $activeCount = $registeredPonCounts[$olt->kode_olt] ?? 0;
                            $popName = $olt->nama_pop ?? ($olt->kode_pop ?? 'POP Utama MSN');
                            $ipAddr = !empty($olt->ip_address) ? $olt->ip_address : '10.10.10.1';
                            $brandName = !empty($olt->brand) ? $olt->brand : 'ZTE C320';
                            $capacity = !empty($olt->capacity_olt) ? $olt->capacity_olt : 8;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <!-- 1. Kode OLT -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6" />
                                        </svg>
                                    </div>
                                    <span class="font-mono font-bold text-slate-900 dark:text-white text-xs">
                                        {{ $olt->kode_olt }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Nama OLT & Merk -->
                            <td class="py-4 px-5">
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                        {{ $olt->name_olt }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Merk: {{ $brandName }}
                                    </div>
                                </div>
                            </td>

                            <!-- 3. POP Server -->
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    <svg class="w-3 h-3 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span>{{ $popName }}</span>
                                </span>
                            </td>

                            <!-- 4. IP Address -->
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900/60">
                                    <svg class="w-3 h-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                                    </svg>
                                    <span>{{ $ipAddr }}</span>
                                </span>
                            </td>

                            <!-- 5. Kapasitas Port PON -->
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/60">
                                    <svg class="w-3 h-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                    </svg>
                                    <span>{{ $capacity }} Port PON</span>
                                </span>
                            </td>

                            <!-- 6. Status PON Aktif -->
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/60">
                                    <svg class="w-3 h-3 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>{{ $activeCount }} Terdaftar</span>
                                </span>
                            </td>

                            <!-- 7. Action Buttons -->
                            <td class="py-4 px-5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Buka OLT (GPON Topology) -->
                                    <a href="{{ route('noc.gpon', ['olt' => $olt->kode_olt]) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold shadow-sm transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        <span>Buka OLT</span>
                                    </a>

                                    <!-- Edit OLT -->
                                    <a href="{{ route('noc.olt.edit', $olt->kode_olt) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold shadow-sm transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    <!-- Delete OLT -->
                                    <button type="button" 
                                            @click="confirmDelete('{{ $olt->kode_olt }}', '{{ $olt->name_olt }}')"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-[11px] font-bold shadow-sm transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6" />
                                    </svg>
                                    <span class="font-bold">Tidak ada data OLT ditemukan.</span>
                                    <span class="text-[11px] text-slate-400 mt-1">Klik tombol "+ Tambah OLT Baru" untuk menambahkan perangkat OLT.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
            <div>
                Showing {{ $olts->firstItem() ?? 0 }} to {{ $olts->lastItem() ?? 0 }} of {{ $olts->total() }} results
            </div>
            <div>
                {{ $olts->links() }}
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. MODAL CONFIRM DELETE OLT -->
    <!-- =================================================================== -->
    <div x-show="deleteModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4"
             @click.away="deleteModalOpen = false">
            <div class="flex items-center gap-3 text-rose-500">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center flex-shrink-0">
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
                Apakah Anda yakin ingin menghapus data OLT <span class="font-bold text-slate-900 dark:text-white" x-text="oltToDelete?.name"></span> (<span class="font-mono text-blue-500" x-text="oltToDelete?.kode"></span>)?
            </p>

            <form :action="deleteActionUrl" method="POST" class="flex items-center justify-end gap-2.5 pt-2">
                @csrf
                <button type="button" 
                        @click="deleteModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-lg shadow-rose-500/25 transition">
                    Ya, Hapus OLT
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
