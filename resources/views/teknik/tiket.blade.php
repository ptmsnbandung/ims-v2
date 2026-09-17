@extends('layouts.app', ['title' => 'Tiket - IMS Router'])

@section('page_title', 'Tiket')

@section('content')
<div class="space-y-6">
    <!-- Header with Breadcrumbs & Role Indicator -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-800/80">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                </svg>
                <span>Pusat Tiket & Permintaan</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
                Pilih kategori tiket di bawah untuk membuka dashboard masing-masing modul sesuai hak akses role Anda.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400">Hak Akses:</span>
            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ auth()->user()?->role_badge_classes ?? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' }}">
                {{ auth()->user()?->nama_level ?? auth()->user()?->role?->label() ?? 'Staff' }}
            </span>
            <nav class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400 ml-2">
                <span class="hover:text-slate-200 transition">IMS</span>
                <span class="text-slate-600">&gt;</span>
                <span class="text-blue-400 font-medium">Tiket</span>
            </nav>
        </div>
    </div>

    <!-- Ticket Category Cards Grid (Semua dapat ditekan dan menuju ke dashboard masing-masing) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
        
        <!-- 1. Gangguan Layanan (Royal Blue Card -> Dashboard Tiket Gangguan) -->
        <a href="{{ $destinations['gangguan'] ?? route('teknik.tiket.gangguan', ['kategori' => 'gangguan']) }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white shadow-lg shadow-blue-500/20 hover:shadow-2xl hover:shadow-blue-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['gangguan'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['gangguan'] }} tiket perlu dieksekusi">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Gangguan Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['gangguan'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-blue-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Users / Headset Group Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 2. Ubah Password (Rose Pink Card -> Dashboard Ubah Password) -->
        <a href="{{ $destinations['ubah_password'] ?? route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']) }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-rose-500 via-pink-500 to-rose-600 text-white shadow-lg shadow-rose-500/20 hover:shadow-2xl hover:shadow-rose-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['ubah_password'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['ubah_password'] }} tiket perlu dieksekusi">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Ubah Password
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['ubah_password'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-rose-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Key / Shield Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 3. Relokasi Layanan (Deep Violet / Purple Card -> Dashboard Relokasi) -->
        <a href="{{ $destinations['relokasi'] ?? route('teknik.tiket.gangguan', ['kategori' => 'relokasi']) }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-700 text-white shadow-lg shadow-purple-500/20 hover:shadow-2xl hover:shadow-purple-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['relokasi'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['relokasi'] }} tiket perlu dieksekusi">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Relokasi Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['relokasi'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-purple-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Map Pin / Move Location Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 4. Cek Coverage Area (Amber / Gold Sun Card -> Dashboard Coverage) -->
        <a href="{{ $destinations['coverage'] ?? route('teknik.coverage') }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 text-white shadow-lg shadow-amber-500/20 hover:shadow-2xl hover:shadow-amber-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['coverage'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['coverage'] }} data coverage perlu diproses">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Cek Coverage Area
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['coverage'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-amber-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Target Map Marker Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 5. Terminasi (Crimson Ruby Red Card -> Dashboard Terminasi Role-Aware) -->
        <a href="{{ $destinations['terminasi'] ?? route('teknik.permintaan.terminasi') }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-red-600 via-rose-700 to-red-800 text-white shadow-lg shadow-red-600/25 hover:shadow-2xl hover:shadow-red-600/40 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['terminasi'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['terminasi'] }} tiket terminasi perlu dieksekusi">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Terminasi
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['terminasi'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-red-700 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Users Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 6. Suspend Layanan (Tangerine Sunset Coral Card -> Dashboard Suspend Role-Aware) -->
        <a href="{{ $destinations['suspend'] ?? route('teknik.permintaan.suspend') }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-orange-500 via-orange-600 to-red-500 text-white shadow-lg shadow-orange-500/20 hover:shadow-2xl hover:shadow-orange-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['suspend'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['suspend'] }} tiket suspend perlu diproses">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Suspend Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['suspend'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-orange-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Pause / Lock Clock Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 7. Pemasangan Baru (Emerald Mint Green Card -> Dashboard Pendaftaran / Aktivasi Role-Aware) -->
        <a href="{{ $destinations['pemasangan_baru'] ?? route('teknik.pendaftaran') }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/20 hover:shadow-2xl hover:shadow-emerald-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['pemasangan_baru'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['pemasangan_baru'] }} pendaftaran perlu diproses">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Pemasangan Baru
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['pemasangan_baru'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-emerald-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- User Plus / Installation Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 8. Ubah Layanan (Sky Cyan Card -> Dashboard Ubah Layanan / UP-Downgrade) -->
        <a href="{{ $destinations['ubah_layanan'] ?? route('teknik.permintaan.up-downgrade') }}"
           class="group block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-sky-500 via-cyan-500 to-teal-500 text-white shadow-lg shadow-cyan-500/20 hover:shadow-2xl hover:shadow-cyan-500/35 hover:-translate-y-0.5 hover:scale-[1.015] transition-all duration-200 cursor-pointer">
            
            @if(($counts['ubah_layanan'] ?? 0) > 0)
                <!-- Red Notification Badge (Top Right) -->
                <span class="absolute top-3.5 right-3.5 flex h-3.5 w-3.5 z-10" title="{{ $counts['ubah_layanan'] }} permintaan ubah layanan perlu dieksekusi">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-80"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 border-2 border-white shadow-md"></span>
                </span>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-1 transition-transform">
                        Ubah Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/90">
                        {{ $counts['ubah_layanan'] ?? 0 }} Tiket
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full mt-3 font-medium text-white group-hover:bg-white group-hover:text-cyan-600 transition-colors">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <!-- Speedometer / Upgrade Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
            </div>
        </a>

    </div>
</div>
@endsection
