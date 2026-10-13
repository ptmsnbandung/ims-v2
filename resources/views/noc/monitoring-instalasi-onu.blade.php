@extends('layouts.app')

@section('title', 'Monitoring Instalasi ONU Real-Time - NOC IMS')
@section('page_title', 'Monitoring Instalasi ONU')

@section('content')
<div class="space-y-4"
     x-data="monitoringInstalasiManager({{ Js::from($items) }}, {{ Js::from($stats) }}, '{{ $selectedTanggal }}', '{{ $selectedTeknisi }}', '{{ $selectedStatus }}')"
     x-init="init()"
     x-destroy="destroy()">

    <!-- =================================================================== -->
    <!-- 1. BREADCRUMBS & TOP NAVIGATION                                     -->
    <!-- =================================================================== -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
        <nav class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
            <a href="{{ route('noc.dashboard') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>NOC</span>
            </a>
            <span>/</span>
            <a href="{{ route('noc.aktivasi') }}" class="hover:text-blue-600 transition">Aktivasi Jaringan</a>
            <span>/</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">Monitoring Instalasi ONU</span>
        </nav>

        <!-- Shortcut Tombol -->
        <div class="flex items-center gap-2">
            <a href="{{ route('noc.aktivasi') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-semibold shadow-2xs transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Antrean Aktivasi</span>
            </a>
            <a href="{{ route('noc.olt') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span>Master OLT</span>
            </a>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. HERO COMMAND CENTER BANNER (HIGH-CONTRAST DARK SLATE)            -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4 shadow-sm border border-teal-500/20 text-white"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        <!-- Glow Decorative Elements -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
        <div class="pointer-events-none absolute left-1/3 -top-12 w-48 h-48 bg-teal-400/10 rounded-full blur-xl"></div>
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 relative z-10">
            <!-- Info Kiri: Title & Deskripsi -->
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-500/20 border border-blue-400/30 text-cyan-300 flex items-center justify-center shrink-0 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-extrabold bg-white text-slate-800 shadow-2xs tracking-wide">
                            NOC LIVE MONITOR
                        </span>
                        <h2 class="text-sm sm:text-base font-bold text-white tracking-tight" style="color: #FFFFFF !important;">
                            Monitoring Instalasi ONU Baru
                        </h2>
                    </div>
                    <p class="text-[11.5px] text-[#c6edf3] mt-0.5 leading-relaxed" style="color: #c6edf3 !important;">
                        Pelacakan real-time progres pemasangan modem ONU teknisi di lapangan & validasi handshake sinyal optik hardware OLT ZTE C320.
                    </p>

                    <!-- Status Badges Bar -->
                    <div class="flex flex-wrap items-center gap-2 mt-2 text-[10.5px] font-bold">
                        <!-- Live Clock Badge -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-emerald-300 border border-teal-400/30 shadow-2xs font-mono">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Live: <span class="text-white font-bold" x-text="currentTime">--:--:--</span> WIB</span>
                        </div>

                        <!-- Auto-Refresh Button & Sync State -->
                        <button @click="refreshData(true)" 
                                :disabled="isFetching"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-cyan-200 border border-cyan-400/30 shadow-2xs font-semibold hover:bg-[#064757] transition cursor-pointer"
                                title="Klik untuk sinkronisasi paksa pembacaan hardware OLT sekarang">
                            <svg class="w-3 h-3 text-cyan-400" :class="isFetching ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span x-text="isFetching ? 'Membaca Hardware OLT...' : ('Sync Live: ' + lastSynced)"></span>
                        </button>

                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#04333e]/85 text-teal-200 border border-teal-400/30 shadow-2xs font-mono text-[10px]">
                            <span>Auto-Poll: 45s</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Kanan: Quick Summary KPI (Glassmorphism Cards) -->
            <div class="flex items-center gap-2.5 self-start lg:self-center">
                <div class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-md border border-white/15 text-right shadow-sm">
                    <span class="text-[10px] text-teal-100/90 block font-medium">Tingkat Keberhasilan UP</span>
                    <span class="text-base sm:text-lg font-mono font-black text-emerald-300" x-text="stats.up_percentage + '%'"></span>
                </div>
                <div class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-md border border-white/15 text-right shadow-sm">
                    <span class="text-[10px] text-teal-100/90 block font-medium">Rata-rata Rx Power</span>
                    <span class="text-base sm:text-lg font-mono font-black text-cyan-200" x-text="stats.avg_rx_power"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. EXECUTIVE KPI CARDS (3 STATUS UTAMA DENGAN INDIKATOR WARNA)       -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Total Instalasi -->
        <div @click="statusFilter = 'all'" 
             class="p-3 sm:p-3.5 rounded-xl border bg-white dark:bg-slate-900 cursor-pointer transition shadow-2xs flex items-center justify-between"
             :class="statusFilter === 'all' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'">
            <div>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Total Instalasi</span>
                <span class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-0.5 block" x-text="stats.total"></span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Semua target instalasi</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Sudah UP (HIJAU EMERALD) -->
        <div @click="statusFilter = 'sudah_up'" 
             class="p-3 sm:p-3.5 rounded-xl border bg-white dark:bg-slate-900 cursor-pointer transition shadow-2xs flex items-center justify-between"
             :class="statusFilter === 'sudah_up' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-emerald-300'">
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">Sudah UP</span>
                </div>
                <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-0.5 block" x-text="stats.sudah_up"></span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Online normal di OLT</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-200/50">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Terdeteksi (KUNING / AMBER) -->
        <div @click="statusFilter = 'terdeteksi'" 
             class="p-3 sm:p-3.5 rounded-xl border bg-white dark:bg-slate-900 cursor-pointer transition shadow-2xs flex items-center justify-between"
             :class="statusFilter === 'terdeteksi' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/20 dark:bg-amber-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-amber-300'">
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">Terdeteksi</span>
                </div>
                <span class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5 block" x-text="stats.terdeteksi"></span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Sinyal masuk / negosiasi</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-200/50">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Belum Terhubung (MERAH ROSE) -->
        <div @click="statusFilter = 'belum_terhubung'" 
             class="p-3 sm:p-3.5 rounded-xl border bg-white dark:bg-slate-900 cursor-pointer transition shadow-2xs flex items-center justify-between"
             :class="statusFilter === 'belum_terhubung' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/20 dark:bg-rose-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-rose-300'">
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span class="text-[11px] font-bold text-rose-700 dark:text-rose-400">Belum Terhubung</span>
                </div>
                <span class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400 mt-0.5 block" x-text="stats.belum_terhubung"></span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Kabel belum colok / LOS</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-200/50">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. ALERT UNCONFIGURED ONU REAL-TIME DARI OLT (JIKA ADA TERDETEKSI) -->
    <!-- =================================================================== -->
    @if(!empty($unconfiguredOnus) && count($unconfiguredOnus) > 0)
    <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-2xs">
        <div class="flex items-center gap-3">
            <span class="w-3 h-3 rounded-full bg-amber-500 animate-ping shrink-0"></span>
            <div>
                <div class="font-bold text-xs sm:text-sm text-amber-900 dark:text-amber-100 flex items-center gap-2">
                    <span>Terdeteksi {{ count($unconfiguredOnus) }} Modem ONU Baru di Hardware OLT ZTE C320!</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-slate-900">UNCFG LIVE</span>
                </div>
                <p class="text-[11px] text-amber-800/90 dark:text-amber-300/90 mt-0.5">
                    Modem baru menyala dan sinyal optik terdeteksi di splitter port OLT, menunggu assign slot nomor ONU.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @foreach($unconfiguredOnus as $uOnu)
                <span class="px-2.5 py-1 rounded-md font-mono text-xs font-bold bg-amber-100 dark:bg-amber-950/80 border border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-200">
                    {{ $uOnu['port'] ?? 'PON' }} : {{ $uOnu['sn'] ?? 'SN' }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- =================================================================== -->
    <!-- 5. FILTER BAR LENGKAP: TANGGAL, TEKNISI, STATUS & LIVE SEARCH       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs space-y-3">
        <form method="GET" action="{{ route('noc.monitoring-instalasi-onu') }}" class="grid grid-cols-1 md:grid-cols-12 gap-2.5">
            <!-- 1. Filter Tanggal Instalasi -->
            <div class="md:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                    </svg>
                    <span>Tanggal Instalasi:</span>
                </label>
                <div class="flex items-center gap-1.5">
                    <input type="date" 
                           name="tanggal" 
                           value="{{ $selectedTanggal }}"
                           class="w-full px-2.5 py-1.5 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    @if($selectedTanggal)
                    <a href="{{ route('noc.monitoring-instalasi-onu', ['teknisi' => $selectedTeknisi, 'status' => $selectedStatus]) }}" 
                       class="px-2 py-1 text-[11px] text-slate-400 hover:text-rose-500 transition" title="Clear Tanggal">
                        &times;
                    </a>
                    @endif
                </div>
            </div>

            <!-- 2. Filter Nama Teknisi -->
            <div class="md:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span>Nama Teknisi:</span>
                </label>
                <select name="teknisi" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                    <option value="all">-- Semua Teknisi ({{ count($teknisiList) }}) --</option>
                    @foreach($teknisiList as $tek)
                        <option value="{{ $tek }}" {{ $selectedTeknisi === $tek ? 'selected' : '' }}>
                            {{ $tek }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Pencarian Kata Kunci (Pelanggan, SN, Slot) -->
            <div class="md:col-span-4">
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <span>Pencarian Cepat:</span>
                </label>
                <input type="text" 
                       name="search" 
                       x-model="searchQuery"
                       placeholder="Cari Pelanggan, No Internet, SN, Slot..." 
                       class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-slate-400" />
            </div>

            <!-- 4. Tombol Aksi Submit & Reset -->
            <div class="md:col-span-2 flex items-end gap-1.5">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('noc.monitoring-instalasi-onu') }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition"
                   title="Reset Semua Filter">
                    Reset
                </a>
            </div>
        </form>

        <!-- Baris Filter Status Tabs (Semua, Sudah UP, Terdeteksi, Belum Terhubung) & Switch View Mode -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mr-1">Status ONU:</span>
                
                <!-- Tab: Semua -->
                <button type="button" 
                        @click="statusFilter = 'all'" 
                        class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer text-[11px]"
                        :class="statusFilter === 'all' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'">
                    Semua (<span x-text="stats.total"></span>)
                </button>

                <!-- Tab: Sudah UP (Hijau) -->
                <button type="button" 
                        @click="statusFilter = 'sudah_up'" 
                        class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer text-[11px] inline-flex items-center gap-1.5"
                        :class="statusFilter === 'sudah_up' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100'">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Sudah UP (<span x-text="stats.sudah_up"></span>)</span>
                </button>

                <!-- Tab: Terdeteksi (Kuning/Amber) -->
                <button type="button" 
                        @click="statusFilter = 'terdeteksi'" 
                        class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer text-[11px] inline-flex items-center gap-1.5"
                        :class="statusFilter === 'terdeteksi' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 hover:bg-amber-100'">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span>Terdeteksi (<span x-text="stats.terdeteksi"></span>)</span>
                </button>

                <!-- Tab: Belum Terhubung (Merah) -->
                <button type="button" 
                        @click="statusFilter = 'belum_terhubung'" 
                        class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer text-[11px] inline-flex items-center gap-1.5"
                        :class="statusFilter === 'belum_terhubung' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 hover:bg-rose-100'">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    <span>Belum Terhubung (<span x-text="stats.belum_terhubung"></span>)</span>
                </button>
            </div>

            <!-- View Switcher (Grid Cards vs Table) -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                <button @click="viewMode = 'grid'" 
                        class="p-1 rounded-md transition"
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-blue-600 shadow-2xs' : 'text-slate-400 hover:text-slate-200'"
                        title="Tampilan Kartu (Grid)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button @click="viewMode = 'table'" 
                        class="p-1 rounded-md transition"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-blue-600 shadow-2xs' : 'text-slate-400 hover:text-slate-200'"
                        title="Tampilan Tabel">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. TAMPILAN 1: GRID KARTU MONITORING (RICH VISUAL AESTHETICS)        -->
    <!-- =================================================================== -->
    <div x-show="viewMode === 'grid'">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
            <template x-for="item in filteredItems" :key="item.nomor_internet">
                <div class="bg-white dark:bg-slate-900 border rounded-xl p-3.5 shadow-xs relative overflow-hidden flex flex-col justify-between transition hover:shadow-md"
                     :class="{
                         'border-emerald-500/50 dark:border-emerald-500/30': item.status_key === 'sudah_up',
                         'border-amber-500/50 dark:border-amber-500/30': item.status_key === 'terdeteksi',
                         'border-rose-400/50 dark:border-rose-500/30': item.status_key === 'belum_terhubung'
                     }">
                    
                    <!-- Top Indicator Solid Accent Bar (Hijau, Kuning, atau Merah) -->
                    <div class="absolute top-0 left-0 right-0 h-1"
                         :class="{
                             'bg-emerald-500': item.status_key === 'sudah_up',
                             'bg-amber-500': item.status_key === 'terdeteksi',
                             'bg-rose-500': item.status_key === 'belum_terhubung'
                         }">
                    </div>

                    <!-- Header Card: Nama Pelanggan, No Internet, & Status Badge Visual -->
                    <div>
                        <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" x-text="item.nama_pelanggan"></h3>
                                <div class="flex items-center gap-1.5 text-[11px] font-mono mt-0.5">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold" x-text="item.nomor_internet"></span>
                                    <span class="text-slate-400">&middot;</span>
                                    <span class="text-slate-500 dark:text-slate-400 font-sans" x-text="item.paket_layanan"></span>
                                </div>
                            </div>

                            <!-- Status Badge Visual -->
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-mono font-bold uppercase tracking-wider border shadow-2xs shrink-0"
                                 :class="item.status_badge">
                                <span class="w-2 h-2 rounded-full" :class="item.dot_color"></span>
                                <span x-text="item.status_label"></span>
                            </div>
                        </div>

                        <!-- Info Grid: Slot Hardware, Redaman Rx, Teknisi, & Tanggal -->
                        <div class="grid grid-cols-2 gap-2 my-2.5 text-xs font-mono">
                            <!-- Slot OLT -->
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9.5px] font-sans font-semibold text-slate-500 block">Slot Hardware:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 text-[11px] truncate block mt-0.5" 
                                      x-text="item.index_olt"></span>
                            </div>

                            <!-- Redaman Optik (Power) -->
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9.5px] font-sans font-semibold text-slate-500 block">Redaman Optik (Rx):</span>
                                <span class="font-bold text-[11.5px] block mt-0.5"
                                      :class="{
                                          'text-emerald-600 dark:text-emerald-400': item.status_key === 'sudah_up',
                                          'text-amber-600 dark:text-amber-400': item.status_key === 'terdeteksi',
                                          'text-rose-600 dark:text-rose-400': item.status_key === 'belum_terhubung'
                                      }"
                                      x-text="item.rx_power"></span>
                            </div>

                            <!-- Teknisi Pelaksana -->
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9.5px] font-sans font-semibold text-slate-500 block">Teknisi Lapangan:</span>
                                <span class="font-bold font-sans text-slate-800 dark:text-slate-200 text-[11px] truncate block mt-0.5" 
                                      x-text="item.teknisi"></span>
                            </div>

                            <!-- Tanggal Instalasi -->
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9.5px] font-sans font-semibold text-slate-500 block">Tgl Instalasi:</span>
                                <span class="text-slate-700 dark:text-slate-300 text-[10.5px] block mt-0.5" 
                                      x-text="item.tanggal_instalasi"></span>
                            </div>
                        </div>

                        <!-- Serial Number & Alamat -->
                        <div class="space-y-1 text-[11px] text-slate-600 dark:text-slate-400">
                            <!-- SN Modem -->
                            <div class="flex items-center justify-between p-1.5 rounded-lg bg-slate-100/70 dark:bg-slate-800/50">
                                <span class="text-[10px] font-medium text-slate-500">Serial Number (SN):</span>
                                <div class="flex items-center gap-1 font-mono font-bold text-slate-800 dark:text-slate-200 text-[11px]">
                                    <span x-text="item.serial_number"></span>
                                    <button @click="copyText(item.serial_number)" class="text-slate-400 hover:text-blue-600 transition" title="Salin SN">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Alamat Pasang -->
                            <div class="flex items-start gap-1.5 pt-0.5 text-[10.5px]">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate block text-slate-600 dark:text-slate-300" x-text="item.alamat_pasang"></span>
                            </div>

                            <!-- Keterangan Status Teknis -->
                            <div class="text-[10px] italic text-slate-500 dark:text-slate-400 pt-0.5" x-text="item.detail_keterangan"></div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 text-xs">
                        <span class="text-[10px] text-slate-400 font-mono" x-text="'OLT: ' + item.olt"></span>
                        <div class="flex items-center gap-1.5">
                            <template x-if="item.port_num">
                                <a :href="'{{ url('/noc/olt') }}/' + item.olt + '/pon/' + item.port_num + '/onu'"
                                   class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400 hover:bg-blue-100 transition">
                                    Lihat Port PON
                                </a>
                            </template>
                            <a :href="'{{ url('/noc/network-olt') }}?search=' + item.nomor_internet"
                               class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                                Explorer
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="filteredItems.length === 0" 
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center mt-3">
            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-bold text-slate-700 dark:text-slate-200">Tidak ada data instalasi yang cocok</h3>
            <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter tanggal, teknisi, atau status instalasi di atas.</p>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 7. TAMPILAN 2: TABEL LIST VIEW                                      -->
    <!-- =================================================================== -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="py-3 px-3">Status ONU</th>
                        <th class="py-3 px-3">Pelanggan & No Internet</th>
                        <th class="py-3 px-3">Teknisi Lapangan</th>
                        <th class="py-3 px-3">Tgl Instalasi</th>
                        <th class="py-3 px-3">Slot Hardware OLT</th>
                        <th class="py-3 px-3">Serial Number (SN)</th>
                        <th class="py-3 px-3">Redaman (Rx)</th>
                        <th class="py-3 px-3">Paket Layanan</th>
                        <th class="py-3 px-3">Alamat</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                    <template x-for="item in filteredItems" :key="item.nomor_internet">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <!-- Status Badge Visual -->
                            <td class="py-2.5 px-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border shadow-2xs"
                                      :class="item.status_badge">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="item.dot_color"></span>
                                    <span x-text="item.status_label"></span>
                                </span>
                            </td>

                            <!-- Pelanggan & No Internet -->
                            <td class="py-2.5 px-3 font-sans">
                                <div class="font-bold text-slate-900 dark:text-white" x-text="item.nama_pelanggan"></div>
                                <div class="text-[11px] font-mono font-bold text-blue-600 dark:text-blue-400" x-text="item.nomor_internet"></div>
                            </td>

                            <!-- Teknisi Lapangan -->
                            <td class="py-2.5 px-3 font-sans font-semibold text-slate-800 dark:text-slate-200" x-text="item.teknisi"></td>

                            <!-- Tanggal Instalasi -->
                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300 text-[11px]" x-text="item.tanggal_instalasi"></td>

                            <!-- Slot Hardware OLT -->
                            <td class="py-2.5 px-3 text-indigo-600 dark:text-indigo-400 font-bold" x-text="item.index_olt"></td>

                            <!-- Serial Number (SN) -->
                            <td class="py-2.5 px-3 text-[11px]">
                                <div class="flex items-center gap-1 font-bold text-slate-800 dark:text-slate-200">
                                    <span x-text="item.serial_number"></span>
                                    <button @click="copyText(item.serial_number)" class="text-slate-400 hover:text-blue-600 transition" title="Salin SN">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>

                            <!-- Redaman Rx -->
                            <td class="py-2.5 px-3 font-bold"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400': item.status_key === 'sudah_up',
                                    'text-amber-600 dark:text-amber-400': item.status_key === 'terdeteksi',
                                    'text-rose-600 dark:text-rose-400': item.status_key === 'belum_terhubung'
                                }"
                                x-text="item.rx_power"></td>

                            <!-- Paket Layanan -->
                            <td class="py-2.5 px-3 font-sans text-slate-700 dark:text-slate-300" x-text="item.paket_layanan"></td>

                            <!-- Alamat -->
                            <td class="py-2.5 px-3 font-sans text-[11px] text-slate-500 dark:text-slate-400 max-w-[200px] truncate" x-text="item.alamat_pasang"></td>

                            <!-- Aksi -->
                            <td class="py-2.5 px-3 text-right font-sans">
                                <a :href="'{{ url('/noc/network-olt') }}?search=' + item.nomor_internet"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-semibold bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 hover:bg-blue-100 transition">
                                    Explorer
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="px-3.5 py-2 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Status dievaluasi secara real-time langsung terhadap hardware OLT ZTE C320 (Working = Sudah UP, Sinyal masuk = Terdeteksi, LOS = Belum Terhubung).</span>
            </span>
            <span class="font-mono text-[10.5px] text-slate-400" x-text="'Sinkronisasi hardware: ' + lastSynced"></span>
        </div>
    </div>

</div>

<!-- =================================================================== -->
<!-- JAVASCRIPT / ALPINE LOGIC DENGAN REAL-TIME AUTO REFRESH             -->
<!-- =================================================================== -->
<script>
function monitoringInstalasiManager(initialItems, initialStats, selectedTanggal, selectedTeknisi, selectedStatus) {
    return {
        items: initialItems || [],
        stats: initialStats || { total: 0, sudah_up: 0, terdeteksi: 0, belum_terhubung: 0, up_percentage: 0, avg_rx_power: '-21.4 dBm' },
        
        // Filter states
        selectedTanggal: selectedTanggal || '',
        selectedTeknisi: selectedTeknisi || 'all',
        statusFilter: selectedStatus || 'all',
        searchQuery: '',
        viewMode: 'grid',

        // Timer & Polling Real-time
        currentTime: '',
        lastSynced: '{{ now()->format("H:i:s") }} WIB',
        isFetching: false,
        timer: null,
        pollTimer: null,
        isPageActive: true,
        visibilityHandler: null,

        init() {
            this.updateClock();

            // 1. Live Running Clock per detik
            this.timer = setInterval(() => {
                this.updateClock();
            }, 1000);

            // 2. Background Auto-refresh setiap 45 detik (ramah beban worker & sinkron dengan cache)
            this.pollTimer = setInterval(() => {
                if (this.isPageActive && !this.isFetching) {
                    this.refreshData(false);
                }
            }, 45000);

            // 3. Page Visibility Listener (Pause polling saat tab tidak dibuka)
            this.visibilityHandler = () => {
                this.isPageActive = !document.hidden;
                if (this.isPageActive) {
                    this.updateClock();
                    this.refreshData(false);
                }
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
            if (this.visibilityHandler) {
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
        },

        updateClock() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            this.currentTime = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        },

        // Background / Manual Polling Data Real-time
        refreshData(isManual = false) {
            if (this.isFetching) return;
            this.isFetching = true;

            const url = new URL(window.location.href);
            url.searchParams.set('format', 'json');
            if (isManual) {
                url.searchParams.set('refresh', '1');
            }

            fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    this.items = data.items || [];
                    this.stats = data.stats || this.stats;
                    this.lastSynced = data.updated_at || (new Date().toLocaleTimeString() + ' WIB');
                }
            })
            .catch(err => console.debug('Polling monitoring instalasi notice:', err))
            .finally(() => {
                this.isFetching = false;
            });
        },

        get filteredItems() {
            let list = [...this.items];

            // 1. Status Filter
            if (this.statusFilter !== 'all') {
                list = list.filter(i => i.status_key === this.statusFilter);
            }

            // 2. Search Text
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase().trim();
                list = list.filter(i => 
                    (i.nama_pelanggan && i.nama_pelanggan.toLowerCase().includes(q)) ||
                    (i.nomor_internet && i.nomor_internet.toLowerCase().includes(q)) ||
                    (i.serial_number && i.serial_number.toLowerCase().includes(q)) ||
                    (i.index_olt && i.index_olt.toLowerCase().includes(q)) ||
                    (i.teknisi && i.teknisi.toLowerCase().includes(q)) ||
                    (i.alamat_pasang && i.alamat_pasang.toLowerCase().includes(q))
                );
            }

            return list;
        },

        copyText(text) {
            if (!text || text === '-') return;
            navigator.clipboard.writeText(text).then(() => {
                alert('Serial Number ' + text + ' berhasil disalin ke clipboard!');
            }).catch(() => {});
        }
    };
}
</script>
@endsection
