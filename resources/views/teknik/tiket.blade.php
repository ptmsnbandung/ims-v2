@extends('layouts.app', ['title' => 'Tiket - IMS Router'])

@section('page_title', 'Tiket')

@section('content')
<div class="space-y-5">
    <!-- Header with Breadcrumbs, KPI Summary, & Role Indicator -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-slate-200/90 dark:border-slate-800/90">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200/70 dark:border-blue-800/60 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        Pusat Tiket & Permintaan
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pilih kategori tiket di bawah untuk membuka dashboard masing-masing modul sesuai hak akses role Anda.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            @php
                $totalActiveTickets = array_sum($counts ?? []);
                $activeCategoriesCount = count(array_filter($counts ?? [], fn($c) => $c > 0));
            @endphp

            <!-- KPI Pill: Total Tiket Aktif -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-xs shadow-2xs">
                <span class="flex h-2 w-2 relative">
                    @if($totalActiveTickets > 0)
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    @endif
                </span>
                <span class="text-slate-500 dark:text-slate-400">Total Antrean:</span>
                <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $totalActiveTickets }} Tiket</span>
            </div>

            <!-- Role Badge -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-xs shadow-2xs">
                <span class="text-slate-500 dark:text-slate-400">Hak Akses:</span>
                <span class="font-bold uppercase tracking-wider text-[11px] px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                    {{ auth()->user()?->nama_level ?? auth()->user()?->role?->label() ?? 'Staff' }}
                </span>
            </div>

            <!-- Breadcrumbs -->
            <nav class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500 px-2">
                <span class="hover:text-slate-700 dark:hover:text-slate-300 transition">IMS</span>
                <span>&gt;</span>
                <span class="text-blue-600 dark:text-blue-400 font-semibold">Tiket</span>
            </nav>
        </div>
    </div>

    <!-- Ticket Category Cards Grid (4 Columns on Desktop, 2 on Tablet, 1 on Mobile) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Gangguan Layanan (Blue Accent) -->
        <a href="{{ $destinations['gangguan'] ?? route('teknik.tiket.gangguan', ['kategori' => 'gangguan']) }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/5 dark:hover:shadow-blue-500/10 flex flex-col justify-between">
            
            <!-- Top Gradient Accent Bar -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-400 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <!-- Ambient Glow Effect -->
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-blue-500/10 dark:bg-blue-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <!-- Top Row: Icon & Status Badge -->
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>

                    @if(($counts['gangguan'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['gangguan'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <!-- Title & Context Description -->
                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    Gangguan Layanan
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Laporan kendala koneksi & penanganan teknis pelanggan.
                </p>
            </div>

            <!-- Bottom Row: Count Metric & Action Button -->
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['gangguan'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['gangguan'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-blue-600 group-hover:text-white dark:group-hover:bg-blue-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 2. Ubah Password (Pink / Rose Accent) -->
        <a href="{{ $destinations['ubah_password'] ?? route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']) }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-pink-500/50 hover:shadow-xl hover:shadow-pink-500/5 dark:hover:shadow-pink-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-pink-600 via-rose-500 to-pink-400 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-pink-500/10 dark:bg-pink-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-pink-50 dark:bg-pink-950/50 text-pink-600 dark:text-pink-400 border border-pink-100 dark:border-pink-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-pink-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                        </svg>
                    </div>

                    @if(($counts['ubah_password'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['ubah_password'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">
                    Ubah Password
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Reset & pembaruan kredensial sandi WiFi / PPPoE.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['ubah_password'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['ubah_password'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-pink-600 group-hover:text-white dark:group-hover:bg-pink-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 3. Relokasi Layanan (Purple / Violet Accent) -->
        <a href="{{ $destinations['relokasi'] ?? route('teknik.tiket.gangguan', ['kategori' => 'relokasi']) }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-purple-500/50 hover:shadow-xl hover:shadow-purple-500/5 dark:hover:shadow-purple-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-500 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-purple-500/10 dark:bg-purple-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-purple-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </div>

                    @if(($counts['relokasi'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['relokasi'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                    Relokasi Layanan
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Permintaan pindah titik lokasi instalasi pelanggan.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['relokasi'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['relokasi'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-purple-600 group-hover:text-white dark:group-hover:bg-purple-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 4. Cek Coverage Area (Amber / Gold Accent) -->
        <a href="{{ $destinations['coverage'] ?? route('teknik.coverage') }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-amber-500/50 hover:shadow-xl hover:shadow-amber-500/5 dark:hover:shadow-amber-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-400 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-amber-500/10 dark:bg-amber-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-amber-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>

                    @if(($counts['coverage'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['coverage'] }} Data
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Data
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                    Cek Coverage Area
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Validasi jangkauan jaringan & ketersediaan ODP.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['coverage'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['coverage'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Permintaan</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-amber-600 group-hover:text-white dark:group-hover:bg-amber-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 5. Terminasi (Crimson Red Accent) -->
        <a href="{{ $destinations['terminasi'] ?? route('teknik.permintaan.terminasi') }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-rose-500/50 hover:shadow-xl hover:shadow-rose-500/5 dark:hover:shadow-rose-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-600 via-rose-600 to-red-400 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-rose-500/10 dark:bg-rose-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-rose-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>

                    @if(($counts['terminasi'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['terminasi'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                    Terminasi
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Pencabutan & permohonan berhenti berlangganan.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['terminasi'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['terminasi'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-rose-600 group-hover:text-white dark:group-hover:bg-rose-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 6. Suspend Layanan (Orange Accent) -->
        <a href="{{ $destinations['suspend'] ?? route('teknik.permintaan.suspend') }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-orange-500/50 hover:shadow-xl hover:shadow-orange-500/5 dark:hover:shadow-orange-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-orange-500/10 dark:bg-orange-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-orange-50 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400 border border-orange-100 dark:border-orange-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-orange-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    @if(($counts['suspend'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['suspend'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                    Suspend Layanan
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Pengelolaan isolir sementara & buka isolir pelanggan.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['suspend'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['suspend'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-orange-600 group-hover:text-white dark:group-hover:bg-orange-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 7. Pemasangan Baru (Emerald Green Accent) -->
        <a href="{{ $destinations['pemasangan_baru'] ?? route('teknik.pendaftaran') }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-emerald-500/50 hover:shadow-xl hover:shadow-emerald-500/5 dark:hover:shadow-emerald-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-400 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>

                    @if(($counts['pemasangan_baru'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['pemasangan_baru'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                    {{ (isset($user) && $user?->isNoc()) ? 'Aktivasi Pasang Baru' : 'Pemasangan Baru' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Pendaftaran, instalasi, dan aktivasi pelanggan baru.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['pemasangan_baru'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['pemasangan_baru'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-emerald-600 group-hover:text-white dark:group-hover:bg-emerald-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 8. Ubah Layanan (Cyan / Teal Accent) -->
        <a href="{{ $destinations['ubah_layanan'] ?? route('teknik.permintaan.up-downgrade') }}"
           class="group relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all duration-200 hover:-translate-y-1 hover:border-cyan-500/50 hover:shadow-xl hover:shadow-cyan-500/5 dark:hover:shadow-cyan-500/10 flex flex-col justify-between">
            
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-cyan-600 via-teal-500 to-blue-500 opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-300"></div>

            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <div class="w-11 h-11 rounded-xl bg-cyan-50 dark:bg-cyan-950/50 text-cyan-600 dark:text-cyan-400 border border-cyan-100 dark:border-cyan-900/60 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-cyan-600 group-hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                    </div>

                    @if(($counts['ubah_layanan'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $counts['ubah_layanan'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium font-mono text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                    Ubah Layanan
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    Permohonan upgrade / downgrade paket bandwidth.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold font-mono {{ ($counts['ubah_layanan'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $counts['ubah_layanan'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Antrean</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 group-hover:bg-cyan-600 group-hover:text-white dark:group-hover:bg-cyan-600 transition-all duration-200 shadow-2xs">
                    <span>Buka</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

    </div>
</div>
@endsection
