@extends('layouts.app', ['title' => 'Dashboard Utama'])

@section('page_title', 'Dashboard Utama')

@section('content')
<div class="space-y-5" x-data="dashboardInvoicesView(@json($newUserStats['recentNewUsers'] ?? []))">

    <!-- ========================================================================= -->
    <!-- 1. TOP HEADER SECTION (Style Reference: Invoices Header with Back & Action) -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Title & Subtitle -->
        <div class="flex items-start sm:items-center gap-3.5">
            <!-- Back / Icon Pill -->
            <a href="{{ route('dashboard') }}" 
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-center text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex-shrink-0 group">
                <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    Dashboard Utama
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Monitoring performa jaringan, pertumbuhan pelanggan baru, dan operasional PT MSN secara real-time.
                </p>
            </div>
        </div>

        <!-- Right Quick Action Buttons -->
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <!-- Filter Option Button -->
            <button type="button" 
                    @click="filterDrawerOpen = !filterDrawerOpen"
                    class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-center text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
                    title="Filter Tampilan">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
            </button>

            <!-- Primary Action Pill Button (+ Pendaftaran Baru) -->
            <a href="{{ route('teknik.pendaftaran') }}" 
               class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-indigo-600/20 transition cursor-pointer active:scale-[0.98]">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Pelanggan Baru</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TOP METRIC CARDS ROW (4 CARDS MATCHING REFERENCE DESIGN)               -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- CARD 1: User / Pelanggan Baru Terdaftar (Style: Overdue with illustration) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between group hover:border-indigo-400/60 dark:hover:border-indigo-500/40 transition duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">User Baru Terdaftar</span>
                <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>

            <div class="mt-2.5 z-10 relative">
                <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    {{ number_format($newUserStats['totalBaru']) }}
                    <span class="text-xs font-normal text-slate-400 font-sans">User</span>
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs font-semibold">
                    @if($newUserStats['growthCount'] >= 0)
                        <span class="inline-flex items-center gap-0.5 text-rose-500 font-bold">
                            <span class="text-xs">&uarr;</span> {{ $newUserStats['growthPercent'] }}%
                        </span>
                        <span class="text-slate-400 font-normal">dari tahun lalu</span>
                    @else
                        <span class="inline-flex items-center gap-0.5 text-emerald-500 font-bold">
                            <span class="text-xs">&darr;</span> {{ $newUserStats['growthPercent'] }}%
                        </span>
                        <span class="text-slate-400 font-normal">dari tahun lalu</span>
                    @endif
                </div>
            </div>

            <!-- Decorative Desk / Network Workspace Illustration -->
            <div class="mt-4 pt-1 flex items-end justify-between relative z-10">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        Thn {{ $selectedTahun }}
                    </span>
                    <span class="text-[10px] text-slate-400">Total terdata</span>
                </div>
                <!-- Clean Modern Plant & Workspace SVG -->
                <svg class="w-16 h-12 text-slate-300 dark:text-slate-700 opacity-80" viewBox="0 0 80 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 45H75" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <rect x="25" y="16" width="30" height="22" rx="2" stroke="currentColor" stroke-width="2"/>
                    <path d="M30" y="38" stroke="currentColor" stroke-width="2"/>
                    <circle cx="15" cy="36" r="6" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M15 30C15 24 10 20 8 18C13 22 17 24 17 30" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M15 30C15 23 20 18 22 16C17 20 15 25 15 30" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M68 28L63 45H73L68 28Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </div>
        </div>

        <!-- CARD 2: Aktivasi Selesai (Style: Due next month with mini Bar Chart) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between group hover:border-indigo-400/60 dark:hover:border-indigo-500/40 transition duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Aktivasi Selesai (Aktif)</span>
                <span class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-500 dark:bg-indigo-500/10 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </span>
            </div>

            <div class="mt-2.5 z-10 relative">
                <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    {{ number_format($newUserStats['aktifBaru']) }}
                    <span class="text-xs font-normal text-indigo-500 font-sans">#20 Aktif</span>
                </div>
                <div class="mt-1 flex items-center gap-1 text-xs">
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">
                        &uarr; {{ $newUserStats['totalBaru'] > 0 ? round(($newUserStats['aktifBaru'] / $newUserStats['totalBaru']) * 100, 1) : 0 }}%
                    </span>
                    <span class="text-slate-400 font-normal">tingkat konversi</span>
                </div>
            </div>

            <!-- Mini Purple Bar Chart Visual (Matching reference image) -->
            <div class="mt-4 flex items-end justify-between gap-1.5 pt-1">
                <div class="flex items-end gap-1.5 w-full">
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-200 dark:bg-indigo-950/60 rounded-t-sm h-6"></div>
                        <span class="text-[9px] text-slate-400">Jul</span>
                    </div>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-300 dark:bg-indigo-900/60 rounded-t-sm h-9"></div>
                        <span class="text-[9px] text-slate-400">Agu</span>
                    </div>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-400 dark:bg-indigo-800/80 rounded-t-sm h-12"></div>
                        <span class="text-[9px] text-slate-400">Sep</span>
                    </div>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-500 dark:bg-indigo-700 rounded-t-sm h-8"></div>
                        <span class="text-[9px] text-slate-400">Okt</span>
                    </div>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-600 dark:bg-indigo-600 rounded-t-sm h-14"></div>
                        <span class="text-[9px] text-slate-400 font-bold text-indigo-600 dark:text-indigo-400">Nov</span>
                    </div>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-700 dark:bg-indigo-500 rounded-t-sm h-16"></div>
                        <span class="text-[9px] text-slate-400">Des</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: Proses & Antrean (Style: Average time with Line Chart) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between group hover:border-indigo-400/60 dark:hover:border-indigo-500/40 transition duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Proses & Antrean</span>
                <span class="w-6 h-6 rounded-full bg-teal-50 text-teal-500 dark:bg-teal-500/10 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>

            <div class="mt-2.5 z-10 relative">
                <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    {{ number_format($newUserStats['prosesBaru']) }}
                    <span class="text-xs font-normal text-amber-500 font-sans">On-going</span>
                </div>
                <div class="mt-1 flex items-center gap-1 text-xs">
                    <span class="text-teal-600 dark:text-teal-400 font-bold">
                        &darr; Survey: {{ $newUserStats['statusBreakdown']['12'] }}
                    </span>
                    <span class="text-slate-400 font-normal">&middot; Inst: {{ $newUserStats['statusBreakdown']['16'] }}</span>
                </div>
            </div>

            <!-- Mini Spline Line Graph with Points (Matching reference image) -->
            <div class="mt-4 pt-1 flex items-end">
                <svg class="w-full h-14 overflow-visible" viewBox="0 0 160 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 0,38 C 25,36 40,25 65,30 C 90,35 110,18 135,12 C 145,10 155,5 160,2" 
                          stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Circles on the graph line -->
                    <circle cx="20" cy="37" r="3" fill="#ffffff" stroke="#6366f1" stroke-width="2"/>
                    <circle cx="65" cy="30" r="3.5" fill="#ffffff" stroke="#6366f1" stroke-width="2"/>
                    <circle cx="100" cy="27" r="3" fill="#ffffff" stroke="#6366f1" stroke-width="2"/>
                    <circle cx="135" cy="12" r="3.5" fill="#ffffff" stroke="#6366f1" stroke-width="2"/>
                    <circle cx="160" cy="2" r="4" fill="#6366f1"/>
                </svg>
            </div>
        </div>

        <!-- CARD 4: Total Pelanggan Aktif (Style: Instant Payout with mini method chips) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between group hover:border-indigo-400/60 dark:hover:border-indigo-500/40 transition duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Pelanggan Aktif</span>
                <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                    </span>
                    <a href="{{ route('teknik.pelanggan') }}" 
                       class="w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center transition"
                       title="Lihat Pelanggan">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="mt-2.5 z-10 relative">
                <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    {{ number_format($newUserStats['totalSemuaPelangganAktif']) }}
                    <span class="text-xs font-normal text-slate-400 font-sans">Subscribers</span>
                </div>
                <div class="mt-1 text-xs text-slate-400">
                    Live running network connection
                </div>
            </div>

            <!-- Mini Method Cards / Status Chips (Matching reference image) -->
            <div class="mt-4 flex items-center justify-between gap-1.5 pt-1">
                <div class="flex items-center gap-1.5 flex-1">
                    <!-- Chip 1: Draft -->
                    <div class="flex-1 py-1 px-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 text-center">
                        <div class="text-[9px] text-slate-400">Draft</div>
                        <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 font-mono">{{ $newUserStats['statusBreakdown']['11'] }}</div>
                    </div>
                    <!-- Chip 2: Aktif (Highlighted Blue Card like Stripe in image) -->
                    <div class="flex-1 py-1 px-1.5 rounded-xl bg-indigo-600 text-white shadow-xs text-center">
                        <div class="text-[9px] text-indigo-200">Aktif</div>
                        <div class="text-[11px] font-bold font-mono">{{ $newUserStats['statusBreakdown']['20'] }}</div>
                    </div>
                    <!-- Chip 3: Batal -->
                    <div class="flex-1 py-1 px-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 text-center">
                        <div class="text-[9px] text-slate-400">Batal</div>
                        <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 font-mono">{{ $newUserStats['batalBaru'] }}</div>
                    </div>
                </div>
                <!-- Action button like 'Payout now' in image -->
                <a href="{{ route('teknik.pelanggan') }}" 
                   class="px-2.5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 text-[10px] font-bold transition flex-shrink-0">
                    Semua
                </a>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. MIDDLE FILTER & TOOLBAR (Matching Reference Filter Toolbar)            -->
    <!-- ========================================================================= -->
    <div class="p-3 sm:p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs flex flex-wrap items-center justify-between gap-3">
        
        <!-- Left: Active Filters Tag & Dropdown Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Active Filters Badge -->
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                <span>Active filters</span>
                <span class="w-4 h-4 rounded-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-bold flex items-center justify-center">
                    2
                </span>
            </div>

            <!-- Customer / Wilayah Filter Pill -->
            <div class="relative">
                <a href="{{ route('teknik.pelanggan') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium transition cursor-pointer">
                    <span>Semua Wilayah</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </a>
            </div>

            <!-- Status Filter Pill -->
            <div class="relative">
                <button type="button" 
                        @click="filterTab = filterTab === 'aktif' ? 'all' : 'aktif'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition cursor-pointer"
                        :class="filterTab === 'aktif' ? 'bg-indigo-50 border-indigo-300 text-indigo-700 dark:bg-indigo-950/60 dark:border-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    <span>Semua Status</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Right: Year & Search Inputs -->
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <!-- Year Selector Filter Form -->
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium">
                    <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <select name="tahun" onchange="this.form.submit()" class="bg-transparent border-none text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedTahun == (string)$year ? 'selected' : '' }}>
                                Tahun {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <!-- Search Input Box -->
            <div class="relative flex-1 sm:flex-initial">
                <input type="text" 
                       x-model="searchKeyword"
                       placeholder="Cari no internet / nama..."
                       class="w-full sm:w-56 pl-8 pr-3 py-1.5 rounded-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. BOTTOM SIGNATURE SECTION: DARK SPLIT-PANEL MASTER-DETAIL VIEW         -->
    <!-- (Aplikasi Persis dengan Gambar Referensi: Unpaid Invoices vs Details)     -->
    <!-- ========================================================================= -->
    <div class="rounded-3xl bg-[#1b213b] dark:bg-slate-900 border border-slate-800/80 shadow-2xl p-4 sm:p-5 text-white">
        
        <!-- Header of the Dark Container: Title & Segmented Pills Tabs -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-800/70 pb-4">
            <!-- Left: Title -->
            <div class="flex items-center gap-2.5">
                <h3 class="text-sm sm:text-base font-bold tracking-tight text-white">
                    Pendaftaran Pelanggan Terbaru
                </h3>
                <span class="text-[11px] text-slate-400 font-mono">
                    (Thn {{ $selectedTahun }})
                </span>
            </div>

            <!-- Center: Segmented Tabs (All Invoices, Draft, Unpaid) -->
            <div class="inline-flex items-center p-1 rounded-full bg-slate-900/90 border border-slate-800/90 self-start md:self-auto">
                <button type="button" 
                        @click="filterTab = 'all'"
                        class="px-3.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer"
                        :class="filterTab === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-white'">
                    Semua Data
                </button>
                <button type="button" 
                        @click="filterTab = 'proses'"
                        class="px-3.5 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
                        :class="filterTab === 'proses' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-white'">
                    <span>Proses</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                          :class="filterTab === 'proses' ? 'bg-slate-200 text-slate-900' : 'bg-slate-800 text-slate-300'">
                        {{ $newUserStats['prosesBaru'] }}
                    </span>
                </button>
                <button type="button" 
                        @click="filterTab = 'aktif'"
                        class="px-3.5 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 transition cursor-pointer"
                        :class="filterTab === 'aktif' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'">
                    <span>Aktif (#20)</span>
                    <span class="w-4 h-4 rounded-full bg-indigo-500/40 text-white text-[10px] flex items-center justify-center font-mono">
                        {{ $newUserStats['statusBreakdown']['20'] }}
                    </span>
                </button>
            </div>

            <!-- Right: Actions Icons -->
            <div class="flex items-center gap-2 text-slate-400">
                <a href="{{ route('teknik.pendaftaran') }}" 
                   class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:underline flex items-center gap-1">
                    <span>Kelola Semua</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Main 2-Column Split Master Detail View -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 pt-4">
            
            <!-- ======================================================== -->
            <!-- LEFT COLUMN: MASTER LIST (5 Cols)                         -->
            <!-- ======================================================== -->
            <div class="lg:col-span-5 space-y-2 max-h-[460px] overflow-y-auto pr-1">
                <template x-for="(user, idx) in filteredUsers" :key="user.nomor_internet || idx">
                    <div @click="selectUser(user)"
                         class="p-3 rounded-2xl transition duration-200 cursor-pointer flex items-center justify-between gap-3 select-none"
                         :class="isSelected(user) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30 ring-1 ring-indigo-400/50' : 'bg-slate-900/50 hover:bg-slate-800/80 text-slate-200 border border-slate-800/60'">
                        
                        <!-- Left: Avatar & Customer Info -->
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Avatar Circle with Initial -->
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-inner"
                                 :class="isSelected(user) ? 'bg-white/20 text-white border border-white/30' : 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30'">
                                <span x-text="getInitials(user.nama_pelanggan)"></span>
                            </div>
                            
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-xs font-bold tracking-tight"
                                          :class="isSelected(user) ? 'text-white' : 'text-indigo-400'"
                                          x-text="'#' + (user.nomor_internet || 'REG-' + idx)"></span>
                                </div>
                                <h4 class="text-xs font-bold truncate uppercase mt-0.5"
                                    :class="isSelected(user) ? 'text-white' : 'text-slate-100'"
                                    x-text="user.nama_pelanggan || 'Pelanggan'"></h4>
                                <p class="text-[10px] mt-0.5 truncate"
                                   :class="isSelected(user) ? 'text-indigo-100' : 'text-slate-400'"
                                   x-text="formatDate(user.date_create)"></p>
                            </div>
                        </div>

                        <!-- Right: Status Badge & Bandwidth -->
                        <div class="text-right flex-shrink-0 flex flex-col items-end gap-1">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                  :class="getStatusBadgeClass(user.status_reg, isSelected(user))"
                                  x-text="getStatusLabel(user.status_reg)"></span>
                            
                            <span class="text-xs font-extrabold font-mono"
                                  :class="isSelected(user) ? 'text-white' : 'text-slate-200'"
                                  x-text="(user.nominal_bandwith || '10') + ' Mbps'"></span>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="filteredUsers.length === 0" class="py-12 text-center text-slate-400 text-xs">
                    Tidak ada pendaftaran pelanggan pada filter ini.
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- RIGHT COLUMN: DETAIL VIEW PANEL (7 Cols)                  -->
            <!-- ======================================================== -->
            <div class="lg:col-span-7 bg-[#14182b] rounded-2xl border border-slate-800/80 p-4 sm:p-5 flex flex-col justify-between">
                <template x-if="selectedUser">
                    <div class="space-y-4">
                        
                        <!-- Top Detail Header: Number, Status, Company, Customer -->
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-slate-800/70 pb-4">
                            <!-- Left: Invoice Details -->
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold tracking-wider">Detail Pendaftaran</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <h2 class="text-xl sm:text-2xl font-black font-mono text-white tracking-tight"
                                        x-text="'# ' + (selectedUser.nomor_internet || '-')"></h2>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                          :class="getStatusBadgeClass(selectedUser.status_reg, false)"
                                          x-text="getStatusLabel(selectedUser.status_reg)"></span>
                                </div>
                            </div>

                            <!-- Center: Company -->
                            <div class="hidden md:block">
                                <span class="text-[10px] text-slate-400 uppercase font-semibold tracking-wider">Penyedia Jaringan</span>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <div class="w-4 h-4 rounded-full bg-cyan-400 flex items-center justify-center text-[9px] font-bold text-slate-900">M</div>
                                    <span class="text-xs font-bold text-slate-200">PT MSN Network</span>
                                </div>
                            </div>

                            <!-- Right: Customer Contact Person -->
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold tracking-wider">Nama Pelanggan</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold"
                                         x-text="getInitials(selectedUser.nama_pelanggan)"></div>
                                    <div>
                                        <div class="text-xs font-bold text-white uppercase" x-text="selectedUser.nama_pelanggan"></div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="formatDate(selectedUser.date_create)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3 Mini Breakdown Cards inside (Matching $15,990 UI/UX, $21,250 Dev, $10,740 QA in image) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            
                            <!-- Card 1: Paket Bandwidth -->
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 relative group hover:border-indigo-500/50 transition">
                                <div class="flex items-center justify-between text-slate-400">
                                    <span class="text-[10px] font-semibold uppercase">Layanan</span>
                                    <svg class="w-3.5 h-3.5 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                                    </svg>
                                </div>
                                <div class="text-base sm:text-lg font-black font-mono text-white mt-2"
                                     x-text="(selectedUser.nominal_bandwith || '10') + ' Mbps'"></div>
                                <div class="text-[10px] text-indigo-400 font-medium truncate mt-0.5"
                                     x-text="selectedUser.nama_kategori_bandwith || selectedUser.alias_nama_kategori || 'INTERNET'"></div>
                            </div>

                            <!-- Card 2: Tahapan Alur / Status -->
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 relative group hover:border-indigo-500/50 transition">
                                <div class="flex items-center justify-between text-slate-400">
                                    <span class="text-[10px] font-semibold uppercase">Tahapan Saat Ini</span>
                                    <svg class="w-3.5 h-3.5 text-teal-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                                    </svg>
                                </div>
                                <div class="text-base sm:text-lg font-black text-white mt-2 truncate"
                                     x-text="getStatusLabel(selectedUser.status_reg)"></div>
                                <div class="text-[10px] text-slate-400 font-medium mt-0.5"
                                     x-text="'Kode Status: #' + (selectedUser.status_reg || '11')"></div>
                            </div>

                            <!-- Card 3: Wilayah / Node -->
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 relative group hover:border-indigo-500/50 transition">
                                <div class="flex items-center justify-between text-slate-400">
                                    <span class="text-[10px] font-semibold uppercase">Wilayah Pasang</span>
                                    <svg class="w-3.5 h-3.5 text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                                    </svg>
                                </div>
                                <div class="text-base sm:text-lg font-black text-white mt-2 truncate">
                                    Bandung Area
                                </div>
                                <div class="text-[10px] text-emerald-400 font-medium mt-0.5">
                                    FTTH Coverage Live
                                </div>
                            </div>

                        </div>

                        <!-- Extra Context Detail Box -->
                        <div class="p-3 rounded-xl bg-slate-900/40 border border-slate-800/80 text-xs space-y-1">
                            <div class="text-slate-400 text-[10px] uppercase font-bold">Catatan Layanan Pelanggan:</div>
                            <div class="text-slate-200 leading-relaxed text-[11px]">
                                Pelanggan terdaftar dengan paket internet broadband kecepatan <span class="font-bold text-white" x-text="(selectedUser.nominal_bandwith || '10') + ' Mbps'"></span>. Akses profil untuk melihat kelengkapan berkas instalasi, titik koordinat peta, dan status billing.
                            </div>
                        </div>

                    </div>
                </template>

                <!-- Bottom Footer Bar (Sub Total, Total, Balance Due, Action Button like Payout Now) -->
                <div class="mt-4 pt-4 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-4 text-xs font-mono">
                        <div>
                            <span class="text-[9px] uppercase text-slate-400 block font-sans">No. Internet</span>
                            <span class="font-bold text-white" x-text="selectedUser ? selectedUser.nomor_internet : '-'"></span>
                        </div>
                        <div class="h-6 w-px bg-slate-800"></div>
                        <div>
                            <span class="text-[9px] uppercase text-slate-400 block font-sans">Bandwidth</span>
                            <span class="font-bold text-indigo-400" x-text="selectedUser ? (selectedUser.nominal_bandwith || '10') + ' Mbps' : '-'"></span>
                        </div>
                        <div class="h-6 w-px bg-slate-800"></div>
                        <div>
                            <span class="text-[9px] uppercase text-slate-400 block font-sans">Status</span>
                            <span class="font-bold text-emerald-400" x-text="selectedUser ? getStatusLabel(selectedUser.status_reg) : '-'"></span>
                        </div>
                    </div>

                    <!-- Action Buttons on bottom right -->
                    <div class="flex items-center gap-2">
                        <template x-if="selectedUser && selectedUser.nomor_internet">
                            <a :href="'{{ url('teknik/pelanggan') }}/' + encodeURIComponent(selectedUser.nomor_internet)"
                               class="px-4 py-2 rounded-full bg-white text-slate-900 hover:bg-slate-100 font-bold text-xs shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                <span>Buka Profil Lengkap</span>
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 5. ANALYTICS & CHARTS SECTION (Retaining All Existing Charts & Data)      -->
    <!-- ========================================================================= -->
    <div class="space-y-4 pt-2">
        
        <!-- Row 1: Area Spline Line Chart (Tren Bulanan) & Donut Chart (Kategori Bandwidth) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            <!-- 1. Grafik Tren User Baru per Bulan (8 Cols) -->
            <div class="lg:col-span-8 p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight">
                                Tren Pertumbuhan User Baru per Bulan
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Perbandingan pendaftaran baru vs aktivasi selesai sepanjang tahun {{ $selectedTahun }}.
                            </p>
                        </div>
                        
                        <!-- Mini Badges -->
                        <div class="flex items-center gap-2">
                            <div class="px-3 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200/60 dark:border-blue-800/50 text-right">
                                <span class="text-[9px] uppercase font-bold text-blue-600 dark:text-cyan-400 block">Registrasi</span>
                                <span class="text-xs font-black font-mono text-slate-900 dark:text-white">{{ number_format($chartData['totalTahunRegistrasi']) }}</span>
                            </div>
                            <div class="px-3 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/60 dark:border-emerald-800/50 text-right">
                                <span class="text-[9px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block">Aktif</span>
                                <span class="text-xs font-black font-mono text-slate-900 dark:text-white">{{ number_format($chartData['totalTahunAktif']) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ApexChart Container -->
                    <div class="pt-3">
                        <div id="chart-user-monthly-trend" class="w-full" style="min-height: 230px;"></div>
                    </div>
                </div>

                <!-- Footer Legend -->
                <div class="mt-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Pendaftaran Baru</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Aktivasi Selesai (Aktif)</span>
                        </span>
                    </div>
                    <span class="font-mono text-[11px] text-slate-400">Periode: Jan - Des {{ $selectedTahun }}</span>
                </div>
            </div>

            <!-- 2. Grafik Donut Kategori Bandwidth (4 Cols) -->
            <div class="lg:col-span-4 p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight">
                            Distribusi Kategori Bandwidth
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Pangsa paket layanan yang dipilih pelanggan.
                        </p>
                    </div>

                    <!-- ApexChart Donut Container -->
                    <div class="pt-3 flex items-center justify-center">
                        <div id="chart-bandwidth-distribution" class="w-full" style="min-height: 200px;"></div>
                    </div>
                </div>

                <!-- Custom Badges Breakdown -->
                <div class="mt-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 space-y-1.5">
                    @php
                        $paletteColors = ['#0284c7', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'];
                        $totalBwAll = array_sum($chartData['bandwidthSeries']);
                    @endphp
                    @foreach($chartData['bandwidthLabels'] as $idx => $label)
                        @php
                            $val = $chartData['bandwidthSeries'][$idx] ?? 0;
                            $pct = $totalBwAll > 0 ? round(($val / $totalBwAll) * 100, 1) : 0;
                            $dotColor = $paletteColors[$idx % count($paletteColors)];
                        @endphp
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $dotColor }};"></span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[140px]" title="{{ $label }}">{{ $label }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-mono">
                                <span class="font-bold text-slate-900 dark:text-white">{{ number_format($val) }}</span>
                                <span class="text-[10px] text-slate-400">({{ $pct }}%)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Row 2: Sebaran User per Wilayah Pasang (Bar Chart & Ranking List) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            <!-- 3. Grafik Sebaran User per Wilayah / Kota Pasang (8 Cols) -->
            <div class="lg:col-span-8 p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <span>Sebaran User per Wilayah Pasang</span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Persebaran data registrasi pelanggan berdasarkan kota pasang.
                            </p>
                        </div>
                        
                        <span class="px-3 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200/60 dark:border-blue-800/50 text-xs font-bold text-blue-700 dark:text-cyan-300 font-mono self-start sm:self-auto">
                            {{ number_format($chartData['totalCityUsers']) }} Total User
                        </span>
                    </div>

                    <!-- ApexChart Container -->
                    <div class="pt-3">
                        <div id="chart-city-distribution" class="w-full" style="min-height: 230px;"></div>
                    </div>
                </div>

                <div class="mt-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Sumber Data: Master Data Registrasi Pelanggan (<code class="font-mono text-[10px]">view_batchjob</code>)</span>
                    <a href="{{ route('teknik.pelanggan') }}" class="font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        Lihat Data Pelanggan &rarr;
                    </a>
                </div>
            </div>

            <!-- 4. Peringkat Wilayah / Kota (4 Cols) -->
            <div class="lg:col-span-4 p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight">
                            Peringkat Wilayah
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Rincian total user & pelanggan aktif.
                        </p>
                    </div>

                    <div class="pt-3 space-y-2 max-h-[240px] overflow-y-auto pr-1">
                        @php
                            $maxCityTotal = $chartData['totalCityUsers'] > 0 ? $chartData['totalCityUsers'] : 1;
                            $cityColors = ['#6366f1', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#64748b'];
                        @endphp
                        @forelse($chartData['cityBreakdown'] ?? [] as $cIdx => $cItem)
                            @php
                                if (is_string($cItem)) {
                                    $cName = $cItem;
                                    $cTot = (int) ($chartData['citySeries'][$cIdx] ?? 0);
                                    $cAktif = (int) ($chartData['cityAktifSeries'][$cIdx] ?? 0);
                                } elseif (is_array($cItem)) {
                                    $cName = (string) ($cItem['kota_name'] ?? '');
                                    $cTot = (int) ($cItem['total'] ?? 0);
                                    $cAktif = (int) ($cItem['total_aktif'] ?? 0);
                                } else {
                                    $cName = (string) ($cItem->kota_name ?? '');
                                    $cTot = (int) ($cItem->total ?? 0);
                                    $cAktif = (int) ($cItem->total_aktif ?? 0);
                                }
                                $cName = trim($cName);
                                $cPct = round(($cTot / $maxCityTotal) * 100, 1);
                                $cBarColor = $cityColors[$loop->index % count($cityColors)];
                            @endphp
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/80 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-5 h-5 rounded-lg flex items-center justify-center text-[10px] font-black {{ $loop->first ? 'bg-amber-500 text-white' : ($loop->iteration == 2 ? 'bg-slate-300 dark:bg-slate-700 text-slate-800 dark:text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                                            {{ $loop->iteration }}
                                        </span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate" title="{{ $cName }}">{{ $cName }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 font-mono flex-shrink-0">
                                        <span class="font-black text-slate-900 dark:text-white">{{ number_format($cTot) }}</span>
                                        <span class="text-[10px] text-slate-400">({{ $cPct }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" style="width: {{ $cPct }}%; background-color: {{ $cBarColor }};"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Aktif: {{ number_format($cAktif) }} User</span>
                                    <a href="{{ route('teknik.pelanggan', ['wilayah' => $cName]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold">Filter &rarr;</a>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-slate-400">
                                Belum ada data wilayah pelanggan
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Alpine.js Master-Detail Component
function dashboardInvoicesView(usersList) {
    return {
        users: Array.isArray(usersList) ? usersList : [],
        selectedUser: null,
        filterTab: 'all',
        searchKeyword: '',
        filterDrawerOpen: false,

        init() {
            if (this.users.length > 0) {
                this.selectedUser = this.users[0];
            }
        },

        get filteredUsers() {
            let list = this.users;

            // Tab filter
            if (this.filterTab === 'aktif') {
                list = list.filter(u => String(u.status_reg) === '20');
            } else if (this.filterTab === 'proses') {
                list = list.filter(u => ['12', '13', '16', '17', '18', '19'].includes(String(u.status_reg)));
            }

            // Keyword filter
            if (this.searchKeyword.trim() !== '') {
                const kw = this.searchKeyword.toLowerCase().trim();
                list = list.filter(u => {
                    const no = (u.nomor_internet || '').toLowerCase();
                    const name = (u.nama_pelanggan || '').toLowerCase();
                    return no.includes(kw) || name.includes(kw);
                });
            }

            return list;
        },

        selectUser(user) {
            this.selectedUser = user;
        },

        isSelected(user) {
            if (!this.selectedUser) return false;
            return this.selectedUser.nomor_internet === user.nomor_internet;
        },

        getInitials(name) {
            if (!name) return 'PL';
            const parts = name.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        },

        getStatusLabel(status) {
            const st = String(status || '');
            if (st === '20') return 'Aktif';
            if (['18', '18.1', '19', '19.1'].includes(st)) return 'Aktivasi';
            if (['16', '17', '17.1'].includes(st)) return 'Instalasi';
            if (['12', '13', '13.1'].includes(st)) return 'Survey';
            if (['14', '15'].includes(st)) return 'Batal';
            return 'Draft';
        },

        getStatusBadgeClass(status, selected) {
            const st = String(status || '');
            if (selected) {
                return 'bg-white/20 text-white border border-white/30';
            }
            if (st === '20') {
                return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
            }
            if (['18', '18.1', '19', '19.1'].includes(st)) {
                return 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30';
            }
            if (['16', '17'].includes(st)) {
                return 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30';
            }
            if (['12', '13'].includes(st)) {
                return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
            }
            if (['14', '15'].includes(st)) {
                return 'bg-rose-500/20 text-rose-300 border border-rose-500/30';
            }
            return 'bg-slate-700/50 text-slate-300 border border-slate-600/50';
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? '#1e293b' : '#f1f5f9';
    const tooltipTheme = isDark ? 'dark' : 'light';

    // -------------------------------------------------------------
    // 1. CHART: TREN PERTUMBUHAN USER BULANAN (AREA SPLINE GRADIENT)
    // -------------------------------------------------------------
    const monthlyLabels = @json($chartData['monthlyLabels']);
    const monthlyRegistrasi = @json($chartData['monthlyRegistrasi']);
    const monthlyAktif = @json($chartData['monthlyAktif']);

    const trendEl = document.querySelector('#chart-user-monthly-trend');
    if (trendEl) {
        const optionsTrend = {
            series: [
                {
                    name: 'Pendaftaran Baru',
                    data: monthlyRegistrasi
                },
                {
                    name: 'Aktivasi Selesai (Aktif)',
                    data: monthlyAktif
                }
            ],
            chart: {
                type: 'area',
                height: 230,
                fontFamily: 'inherit',
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 600
                },
                background: 'transparent'
            },
            colors: ['#6366f1', '#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: isDark ? 0.35 : 0.45,
                    opacityTo: 0.05,
                    stops: [0, 95, 100]
                }
            },
            stroke: {
                curve: 'smooth',
                width: [2.5, 2],
                dashArray: [0, 0]
            },
            markers: {
                size: 3,
                strokeWidth: 1.5,
                hover: { size: 5 }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: monthlyLabels,
                labels: {
                    style: {
                        colors: textColor,
                        fontSize: '10px',
                        fontWeight: 600
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: textColor,
                        fontSize: '10px',
                        fontWeight: 500
                    },
                    formatter: (val) => Math.round(val)
                },
                min: 0,
                forceNiceScale: true
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
                padding: { top: 0, right: 10, bottom: 0, left: 10 }
            },
            legend: {
                show: false
            },
            tooltip: {
                theme: tooltipTheme,
                y: {
                    formatter: function (val) {
                        return val + " Pelanggan";
                    }
                }
            }
        };

        const chartTrend = new ApexCharts(trendEl, optionsTrend);
        chartTrend.render();
    }

    // -------------------------------------------------------------
    // 2. CHART: DISTRIBUSI KATEGORI BANDWIDTH (DONUT PIE)
    // -------------------------------------------------------------
    const bwLabels = @json($chartData['bandwidthLabels']);
    const bwSeries = @json($chartData['bandwidthSeries']);

    const bwEl = document.querySelector('#chart-bandwidth-distribution');
    if (bwEl) {
        const optionsBw = {
            series: bwSeries.length > 0 && bwSeries.some(v => v > 0) ? bwSeries : [1],
            labels: bwSeries.length > 0 && bwSeries.some(v => v > 0) ? bwLabels : ['Belum Ada Data'],
            chart: {
                type: 'donut',
                height: 200,
                fontFamily: 'inherit',
                background: 'transparent'
            },
            colors: ['#6366f1', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#64748b'],
            stroke: {
                width: 2,
                colors: isDark ? ['#0f172a'] : ['#ffffff']
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '10px',
                                fontWeight: 700,
                                color: textColor,
                                offsetY: -2
                            },
                            value: {
                                show: true,
                                fontSize: '16px',
                                fontWeight: 900,
                                color: isDark ? '#ffffff' : '#0f172a',
                                offsetY: 2,
                                formatter: (val) => val
                            },
                            total: {
                                show: true,
                                label: 'Total User',
                                fontSize: '10px',
                                fontWeight: 700,
                                color: textColor,
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: { show: false },
            tooltip: {
                theme: tooltipTheme,
                y: {
                    formatter: (val) => val + " User"
                }
            }
        };

        const chartBw = new ApexCharts(bwEl, optionsBw);
        chartBw.render();
    }

    // -------------------------------------------------------------
    // 3. CHART: SEBARAN USER PER NAMA KOTA PASANG (APEXCHARTS BAR)
    // -------------------------------------------------------------
    const cityLabels = @json($chartData['cityLabels']);
    const citySeries = @json($chartData['citySeries']);
    const cityAktifSeries = @json($chartData['cityAktifSeries'] ?? []);

    const cityEl = document.querySelector('#chart-city-distribution');
    if (cityEl) {
        const optionsCity = {
            series: [
                {
                    name: 'Total Pelanggan',
                    data: citySeries
                },
                {
                    name: 'Pelanggan Aktif Online',
                    data: cityAktifSeries
                }
            ],
            chart: {
                type: 'bar',
                height: 230,
                fontFamily: 'inherit',
                toolbar: { show: false },
                background: 'transparent'
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '22%',
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: ['#6366f1', '#10b981'],
            dataLabels: {
                enabled: true,
                style: {
                    colors: isDark ? ['#e2e8f0'] : ['#1e293b'],
                    fontSize: '10px',
                    fontWeight: 700
                },
                offsetY: -16,
                formatter: (val) => val > 0 ? val : ""
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: cityLabels,
                labels: {
                    style: {
                        colors: textColor,
                        fontSize: '10px',
                        fontWeight: 700
                    },
                    rotate: 0,
                    trim: false
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: textColor,
                        fontSize: '10px'
                    },
                    formatter: (val) => Math.round(val)
                }
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
                yaxis: { lines: { show: true } },
                xaxis: { lines: { show: false } }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: {
                    colors: textColor
                },
                fontSize: '10px'
            },
            tooltip: {
                theme: tooltipTheme,
                y: {
                    formatter: (val) => val + " User"
                }
            }
        };

        const chartCity = new ApexCharts(cityEl, optionsCity);
        chartCity.render();
    }
});
</script>
@endpush
