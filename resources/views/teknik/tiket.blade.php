@extends('layouts.app', ['title' => 'Tiket - IMS Router'])

@section('page_title', 'Tiket')

@section('content')
<style>
    @keyframes imsGlowPulse {
        0%, 100% {
            opacity: 0.85;
            transform: scale(1);
        }
        50% {
            opacity: 1;
            transform: scale(1.08);
        }
    }
</style>

<div class="space-y-5">
    <!-- Header with Breadcrumbs, KPI Summary, & Role Indicator -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-slate-200/90 dark:border-slate-800/90">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white border border-blue-400/30 flex items-center justify-center shadow-md shadow-blue-500/20">
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

    <!-- Ticket Category Cards Grid (4 Columns on Desktop = 2 Balanced Rows) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Gangguan Layanan (Royal Sapphire & Indigo) -->
        <a href="{{ $destinations['gangguan'] ?? route('teknik.tiket.gangguan', ['kategori' => 'gangguan']) }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1e40af] via-[#2563eb] to-[#3b82f6] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-600/30 border border-white/20 flex flex-col justify-between">
            
            <!-- Glass Shimmer & Light Glows -->
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>

                    @if(($counts['gangguan'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['gangguan'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Gangguan Layanan
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Laporan kendala koneksi & problem pelanggan.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['gangguan'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-blue-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 2. Ubah Password (Rose Magenta & Fuchsia) -->
        <a href="{{ $destinations['ubah_password'] ?? route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']) }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#9d174d] via-[#be185d] to-[#db2777] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-pink-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                        </svg>
                    </div>

                    @if(($counts['ubah_password'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['ubah_password'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Ubah Password
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Reset & update sandi kredensial WiFi / PPPoE.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['ubah_password'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-pink-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 3. Relokasi Layanan (Royal Purple & Deep Violet) -->
        <a href="{{ $destinations['relokasi'] ?? route('teknik.tiket.gangguan', ['kategori' => 'relokasi']) }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#5b21b6] via-[#6d28d9] to-[#8b5cf6] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </div>

                    @if(($counts['relokasi'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['relokasi'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Relokasi Layanan
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Permintaan pindah titik lokasi instalasi.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['relokasi'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-purple-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 4. Cek Coverage Area (Warm Radiant Amber & Gold) -->
        <a href="{{ $destinations['coverage'] ?? route('teknik.coverage') }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#b45309] via-[#d97706] to-[#f59e0b] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>

                    @if(($counts['coverage'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['coverage'] }} Data
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Data
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Cek Coverage Area
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Validasi jangkauan sinyal & ketersediaan ODP.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['coverage'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Data</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-amber-800 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 5. Terminasi (Crimson Scarlet & Blood Red) -->
        <a href="{{ $destinations['terminasi'] ?? route('teknik.permintaan.terminasi') }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#881337] via-[#be123c] to-[#e11d48] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-rose-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>

                    @if(($counts['terminasi'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-300 animate-ping"></span>
                            {{ $counts['terminasi'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Terminasi
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Pencabutan & berhenti berlangganan.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['terminasi'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-rose-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 6. Suspend Layanan (Vibrant Sunset & Flame Orange) -->
        <a href="{{ $destinations['suspend'] ?? route('teknik.permintaan.suspend') }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#c2410c] via-[#ea580c] to-[#f97316] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-orange-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    @if(($counts['suspend'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['suspend'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Suspend Layanan
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Pengelolaan isolir sementara & buka isolir.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['suspend'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-orange-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 7. Pemasangan Baru (Emerald Forest & Mint Jade) -->
        <a href="{{ $destinations['pemasangan_baru'] ?? route('teknik.pendaftaran') }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#065f46] via-[#059669] to-[#10b981] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>

                    @if(($counts['pemasangan_baru'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['pemasangan_baru'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    {{ (isset($user) && $user?->isNoc()) ? 'Aktivasi Pasang Baru' : 'Pemasangan Baru' }}
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Pendaftaran, instalasi, dan aktivasi baru.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['pemasangan_baru'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-emerald-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 8. Ubah Layanan (Oceanic Deep Teal & Cyan) -->
        <a href="{{ $destinations['ubah_layanan'] ?? route('teknik.permintaan.up-downgrade') }}"
           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0e7490] via-[#0891b2] to-[#06b6d4] p-4 sm:p-5 text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-cyan-600/30 border border-white/20 flex flex-col justify-between">
            
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-white/20 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-white shadow-inner group-hover:scale-110 group-hover:bg-white/25 transition-all duration-300">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                    </div>

                    @if(($counts['ubah_layanan'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                            {{ $counts['ubah_layanan'] }} Tiket
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono text-white/70 bg-black/15 border border-white/10">
                            0 Tiket
                        </span>
                    @endif
                </div>

                <h3 class="text-[15px] font-bold text-white tracking-tight drop-shadow-xs">
                    Ubah Layanan
                </h3>
                <p class="text-xs text-white/80 mt-1 line-clamp-1 font-normal">
                    Permohonan upgrade / downgrade paket bandwidth.
                </p>
            </div>

            <div class="relative z-10 mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black font-mono text-white drop-shadow-sm">
                        {{ $counts['ubah_layanan'] ?? 0 }}
                    </span>
                    <span class="text-[11px] text-white/75 font-medium">Tiket</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/20 backdrop-blur-md border border-white/30 text-white group-hover:bg-white group-hover:text-cyan-700 transition-all duration-200 shadow-sm">
                    <span>Buka Dashboard</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </a>

    </div>
</div>
@endsection
