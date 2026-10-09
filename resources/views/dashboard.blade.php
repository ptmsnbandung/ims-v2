@extends('layouts.app', ['title' => 'Dashboard Utama'])

@section('page_title', 'Dashboard Utama')

@section('content')
<div class="space-y-3.5">
    <!-- Role Welcome Banner (Deep Oceanic Teal & Cyan Gradient Matching Reference) -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4 lg:p-4.5 shadow-xs"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-44 h-44 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 sm:gap-4">
            <!-- Left Info Area -->
            <div>
                <!-- Top Mini Badges -->
                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                    <!-- Pill 1: White Pill -->
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-slate-800 shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#00a8b5]"></span>
                        <span>ID: &middot; {{ $user->nama_level }}</span>
                    </div>
                    <!-- Pill 2: Dark Teal Pill -->
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-[#04333e]/85 text-emerald-300 border border-teal-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Sistem Aktif (Online)</span>
                    </div>
                </div>

                <!-- Main Greeting -->
                <h2 class="text-base sm:text-lg font-black text-white tracking-tight flex items-center flex-wrap gap-1.5" style="color: #FFFFFF !important;">
                    <span>Halo, {{ $user->nama }}!</span>
                    <span class="text-base sm:text-lg">👋</span>
                </h2>
                <p class="text-[11px] sm:text-xs text-[#c6edf3] mt-0.5 max-w-2xl leading-relaxed" style="color: #C6EDF3 !important;">
                    {{ $user->role_description }} &middot; Pantau performa jaringan, pertumbuhan pelanggan baru, dan kelola operasional secara real-time.
                </p>
            </div>

            <!-- Right Action Buttons / Badges -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2.5 w-full lg:w-auto pt-1 sm:pt-0">
                <!-- Button 1: Outlined Clock Server Pill -->
                <div class="w-full sm:w-auto justify-center px-3 py-1 rounded-lg sm:rounded-full border border-white/40 bg-white/10 backdrop-blur-xs text-white text-[11px] font-semibold flex items-center gap-1.5 shadow-xs transition" style="color: #FFFFFF !important;">
                    <svg class="w-3 h-3 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span style="color: #FFFFFF !important;">{{ now()->format('d M Y, H:i') }} WIB</span>
                </div>

                <!-- Button 2: Solid Emerald Status Layanan Pill -->
                <a href="{{ route('teknik.tiket') }}" 
                   class="w-full sm:w-auto justify-center px-3 py-1 rounded-lg sm:rounded-full bg-[#00b074] hover:bg-[#009b66] text-white text-[11px] font-bold flex items-center gap-1.5 shadow-xs shadow-emerald-950/20 transition cursor-pointer" style="color: #FFFFFF !important;">
                    <svg class="w-3 h-3 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span style="color: #FFFFFF !important;">Status Layanan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION: VISUALISASI GRAFIK & ANALITIK (DIBAWAH HEADER HALO)             -->
    <!-- ========================================================================= -->
    <div class="space-y-3.5">
        
        <!-- Header & Quick Filter Bar -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-start sm:items-center gap-2.5">
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white flex-shrink-0 mt-0.5 sm:mt-0">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                            Analitik & Grafik Pertumbuhan
                        </h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-cyan-400 border border-blue-200 dark:border-blue-500/20">
                            Tahun {{ $selectedTahun }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Monitoring tren user baru bulanan, sebaran kategori bandwidth, dan pipeline instalasi.
                    </p>
                </div>
            </div>

            <!-- Filter Tahun Saja -->
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-initial">
                    <select name="tahun" class="w-full sm:w-auto appearance-none pl-2.5 pr-7 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedTahun == (string)$year ? 'selected' : '' }}>
                                Tahun {{ $year }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition cursor-pointer flex-shrink-0">
                    Terapkan
                </button>
            </form>
        </div>

        <!-- Row 1: Area Line Chart (Tren Bulanan) & Donut Chart (Kategori Bandwidth) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">
            
            <!-- 1. Grafik Tren User Baru dari Bulan ke Bulan (8 Cols) -->
            <div class="lg:col-span-8 p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 border-b border-slate-100 dark:border-slate-800/80 pb-2.5">
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5">
                                <span>Tren Pertumbuhan User Baru per Bulan</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Perbandingan jumlah registrasi baru vs aktivasi selesai sepanjang tahun {{ $selectedTahun }}.
                            </p>
                        </div>
                        
                        <!-- Mini KPI Badges -->
                        <div class="flex items-center gap-1.5">
                            <div class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/50 text-right">
                                <span class="text-[9px] uppercase font-bold text-blue-600 dark:text-cyan-400 block">Total Registrasi</span>
                                <span class="text-xs font-black font-mono text-slate-900 dark:text-white">{{ number_format($chartData['totalTahunRegistrasi']) }}</span>
                            </div>
                            <div class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-right">
                                <span class="text-[9px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block">Total Aktif Online</span>
                                <span class="text-xs font-black font-mono text-slate-900 dark:text-white">{{ number_format($chartData['totalTahunAktif']) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ApexChart Container -->
                    <div class="pt-2">
                        <div id="chart-user-monthly-trend" class="w-full" style="min-height: 220px;"></div>
                    </div>
                </div>

                <!-- Footer Metric Note -->
                <div class="mt-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between text-[10px] text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Pendaftaran Baru</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Aktivasi Selesai (Aktif)</span>
                        </span>
                    </div>
                    <span class="font-mono text-slate-400">Periode: Jan - Des {{ $selectedTahun }}</span>
                </div>
            </div>

            <!-- 2. Grafik Donut Kategori Bandwidth (4 Cols) -->
            <div class="lg:col-span-4 p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="border-b border-slate-100 dark:border-slate-800/80 pb-2.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                            Distribusi Kategori Bandwidth
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Pangsa paket layanan yang dipilih pelanggan.
                        </p>
                    </div>

                    <!-- ApexChart Donut Container -->
                    <div class="pt-2 flex items-center justify-center">
                        <div id="chart-bandwidth-distribution" class="w-full" style="min-height: 200px;"></div>
                    </div>
                </div>

                <!-- Custom Badges Breakdown -->
                <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 space-y-1.5">
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
                        <div class="flex items-center justify-between text-[11px]">
                            <div class="flex items-center gap-1.5">
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

        <!-- Row: Distribusi User per Nama Kota Pasang (view_batchjob) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">
            
            <!-- 5. Grafik Sebaran User per Kota / Wilayah Pasang (8 Cols) -->
            <div class="lg:col-span-8 p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 border-b border-slate-100 dark:border-slate-800/80 pb-2.5">
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <span>Sebaran User per Wilayah / Kota Pasang</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Jumlah persebaran pelanggan berdasarkan <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-blue-600 dark:text-cyan-400 font-bold">nama_kota_pasang</code> dari <span class="font-semibold">view_batchjob</span>.
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-1.5">
                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/50 text-[11px] font-bold text-blue-700 dark:text-cyan-300 font-mono">
                                {{ number_format($chartData['totalCityUsers']) }} Total User
                            </span>
                        </div>
                    </div>

                    <!-- ApexChart Container -->
                    <div class="pt-2">
                        <div id="chart-city-distribution" class="w-full" style="min-height: 220px;"></div>
                    </div>
                </div>

                <div class="mt-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between text-[10px] text-slate-500 dark:text-slate-400">
                    <span>Sumber Data: Master Data Registrasi Pelanggan (<code class="font-mono text-[9px]">view_batchjob</code>)</span>
                    <a href="{{ route('teknik.pelanggan') }}" class="font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        Lihat Data Pelanggan &rarr;
                    </a>
                </div>
            </div>

            <!-- Breakdown Tabel Sebaran Kota / Wilayah (4 Cols) -->
            <div class="lg:col-span-4 p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="border-b border-slate-100 dark:border-slate-800/80 pb-2.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                            Peringkat Wilayah / Kota
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Rincian jumlah total user & pelanggan aktif.
                        </p>
                    </div>

                    <div class="pt-2 space-y-1.5 max-h-[220px] overflow-y-auto pr-1">
                        @php
                            $maxCityTotal = $chartData['totalCityUsers'] > 0 ? $chartData['totalCityUsers'] : 1;
                            $cityColors = ['#0284c7', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#64748b'];
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
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/80 space-y-1">
                                <div class="flex items-center justify-between text-[11px]">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-4 h-4 rounded-md flex items-center justify-center text-[9px] font-black {{ $loop->first ? 'bg-amber-500 text-white' : ($loop->iteration == 2 ? 'bg-slate-300 dark:bg-slate-700 text-slate-800 dark:text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                                            {{ $loop->iteration }}
                                        </span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate" title="{{ $cName }}">{{ $cName }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 font-mono flex-shrink-0">
                                        <span class="font-black text-slate-900 dark:text-white text-xs">{{ number_format($cTot) }}</span>
                                        <span class="text-[9px] text-slate-400">({{ $cPct }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full h-1 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" style="width: {{ $cPct }}%; background-color: {{ $cBarColor }};"></div>
                                </div>
                                <div class="flex items-center justify-between text-[9px] text-slate-500 dark:text-slate-400">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Aktif: {{ number_format($cAktif) }} User</span>
                                    <a href="{{ route('teknik.pelanggan', ['wilayah' => $cName]) }}" class="text-blue-600 dark:text-cyan-400 hover:underline font-semibold">Filter &rarr;</a>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 text-center text-xs text-slate-400">
                                Belum ada data wilayah pelanggan
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>



    <!-- ========================================================================= -->
    <!-- SECTION: STATISTIK USER & PELANGGAN BARU (DENGAN FILTER BULAN & TAHUN)   -->
    <!-- ========================================================================= -->
    <div class="space-y-3.5">
        
        <!-- Header & Filter Bar Section -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                            Statistik User & Pelanggan Baru
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Periode: <span class="font-bold text-blue-600 dark:text-cyan-400">Tahun {{ $selectedTahun }}</span> &middot; Total {{ number_format($newUserStats['totalBaru']) }} pendaftaran baru tercatat.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Single Year Indicator Badge -->
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-cyan-400 border border-blue-200 dark:border-blue-800/60 font-mono">
                    Tahun {{ $selectedTahun }}
                </span>
            </div>
        </div>

        <!-- 4 KPI Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- 1. Total Pelanggan Baru Terdaftar -->
            <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-blue-400 dark:hover:border-cyan-500 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">User / Pelanggan Baru</span>
                    <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                    {{ number_format($newUserStats['totalBaru']) }}
                    <span class="text-[10px] font-normal text-slate-400">User</span>
                </div>
                <div class="mt-2 flex items-center gap-1.5">
                    @if($newUserStats['growthCount'] >= 0)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-1.5 py-0.5 rounded-full">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            +{{ $newUserStats['growthCount'] }} ({{ $newUserStats['growthPercent'] }}%)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 px-1.5 py-0.5 rounded-full">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            {{ $newUserStats['growthCount'] }} ({{ $newUserStats['growthPercent'] }}%)
                        </span>
                    @endif
                    <span class="text-[10px] text-slate-400">vs thn lalu</span>
                </div>
            </div>

            <!-- 2. Pelanggan Baru Aktif (Online / Live) -->
            <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-emerald-500 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aktivasi Selesai (Aktif)</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                    {{ number_format($newUserStats['aktifBaru']) }}
                    <span class="text-[10px] font-normal text-slate-400">Aktif (#20)</span>
                </div>
                <div class="mt-2 flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>
                        {{ $newUserStats['totalBaru'] > 0 ? round(($newUserStats['aktifBaru'] / $newUserStats['totalBaru']) * 100, 1) : 0 }}% tingkat konversi aktif
                    </span>
                </div>
            </div>

            <!-- 3. Dalam Proses / Antrean Pemasangan -->
            <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-amber-400 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Proses & Antrean</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-amber-500 mt-1 font-mono">
                    {{ number_format($newUserStats['prosesBaru']) }}
                    <span class="text-[10px] font-normal text-slate-400">On-going</span>
                </div>
                <div class="mt-2 text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span>Survey: <b>{{ $newUserStats['statusBreakdown']['12'] }}</b></span>
                    <span>&middot;</span>
                    <span>Instalasi: <b>{{ $newUserStats['statusBreakdown']['16'] }}</b></span>
                    <span>&middot;</span>
                    <span>Aktivasi: <b>{{ $newUserStats['statusBreakdown']['18_19'] }}</b></span>
                </div>
            </div>

            <!-- 4. Total Pelanggan Aktif Sistem -->
            <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-cyan-500 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pelanggan Aktif</span>
                    <div class="w-7 h-7 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                    {{ number_format($newUserStats['totalSemuaPelangganAktif']) }}
                    <span class="text-[10px] font-normal text-slate-400">Subscriber</span>
                </div>
                <div class="mt-2 text-[10px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Batal pasang bln ini: <b>{{ $newUserStats['batalBaru'] }}</b></span>
                    @if($newUserStats['totalPenggunaSistemBaru'] > 0)
                        <span class="text-blue-500 font-semibold">+{{ $newUserStats['totalPenggunaSistemBaru'] }} Akun Tim</span>
                    @endif
                </div>
            </div>

        </div>

        <!-- 2-Column Analytics Details: Recent New Customers & Package Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">
            
            <!-- Left Column (7 cols): Daftar Pendaftaran Pelanggan Baru Terbaru -->
            <div class="lg:col-span-7 space-y-2.5">
                <div class="flex items-center justify-between">
                    <h3 class="text-[11px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                        <span>Pendaftaran Pelanggan Baru (Tahun {{ $selectedTahun }})</span>
                    </h3>
                    <a href="{{ route('teknik.pendaftaran') }}" class="text-[11px] font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        Buka Semua &rarr;
                    </a>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                    <th class="py-2 px-3">No. Internet / Nama</th>
                                    <th class="py-2 px-3">Paket Bandwidth</th>
                                    <th class="py-2 px-3">Tanggal Daftar</th>
                                    <th class="py-2 px-3">Status</th>
                                    <th class="py-2 px-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                                @forelse($newUserStats['recentNewUsers'] as $nu)
                                    @php
                                        $nuNoInternet = (string) (is_array($nu) ? ($nu['nomor_internet'] ?? '') : ($nu->nomor_internet ?? ''));
                                        $nuNama = (string) (is_array($nu) ? ($nu['nama_pelanggan'] ?? 'Pelanggan') : ($nu->nama_pelanggan ?? 'Pelanggan'));
                                        $nuBw = (string) (is_array($nu) ? ($nu['nama_kategori_bandwith'] ?? ($nu['alias_nama_kategori'] ?? ($nu['nama_paket'] ?? 'INTERNET'))) : ($nu->nama_kategori_bandwith ?? ($nu->alias_nama_kategori ?? ($nu->nama_paket ?? 'INTERNET'))));
                                        $nuNominalBw = (string) (is_array($nu) ? ($nu['nominal_bandwith'] ?? '10') : ($nu->nominal_bandwith ?? '10'));
                                        $nuDate = is_array($nu) ? ($nu['date_create'] ?? null) : ($nu->date_create ?? null);
                                        $nuStatus = (string) (is_array($nu) ? ($nu['status_reg'] ?? '') : ($nu->status_reg ?? ''));
                                        $profileUrl = !empty($nuNoInternet) ? route('teknik.pelanggan.profile', $nuNoInternet) : '#';
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                        <td class="py-2 px-3">
                                            @if(!empty($nuNoInternet))
                                                <a href="{{ $profileUrl }}" 
                                                   class="text-xs font-bold text-blue-600 dark:text-cyan-400 font-mono hover:underline">
                                                    {{ $nuNoInternet }}
                                                </a>
                                            @else
                                                <span class="text-xs font-bold text-slate-500 font-mono">-</span>
                                            @endif
                                            <div class="text-[11px] font-semibold text-slate-800 dark:text-slate-200 uppercase mt-0.5">{{ $nuNama }}</div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="text-[11px] font-medium text-slate-700 dark:text-slate-300">
                                                {{ $nuBw }}
                                            </div>
                                            <div class="text-[10px] font-bold font-mono text-blue-600 dark:text-cyan-400">
                                                {{ $nuNominalBw }} Mbps
                                            </div>
                                        </td>
                                        <td class="py-2 px-3 text-[11px] text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                            {{ !empty($nuDate) ? \Carbon\Carbon::parse($nuDate)->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="py-2 px-3">
                                            @if($nuStatus == '20')
                                                <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30">
                                                    AKTIF (#20)
                                                </span>
                                            @elseif(in_array($nuStatus, ['18', '18.1', '19', '19.1']))
                                                <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/15 dark:text-cyan-400 dark:border-cyan-500/30">
                                                    AKTIVASI (#{{ $nuStatus }})
                                                </span>
                                            @elseif(in_array($nuStatus, ['16', '17', '17.1']))
                                                <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-400 dark:border-indigo-500/30">
                                                    INSTALASI (#{{ $nuStatus }})
                                                </span>
                                            @elseif(in_array($nuStatus, ['12', '13', '13.1']))
                                                <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30">
                                                    SURVEY (#{{ $nuStatus }})
                                                </span>
                                            @elseif(in_array($nuStatus, ['14', '15']))
                                                <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30">
                                                    BATAL (#{{ $nuStatus }})
                                                </span>
                                            @else
                                                <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                                    DRAFT (#{{ $nuStatus ?: '11' }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            @if(!empty($nuNoInternet))
                                                <a href="{{ $profileUrl }}" 
                                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-semibold transition"
                                                   title="Lihat Profil Pelanggan">
                                                    <span>Profil</span>
                                                    <span class="text-xs">&rarr;</span>
                                                </a>
                                            @else
                                                <span class="text-slate-400 text-[10px]">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-slate-400 text-xs">
                                            Tidak ada data pendaftaran pelanggan baru pada tahun {{ $selectedTahun }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column (5 cols): Breakdown Paket & Funnel Tahapan -->
            <div class="lg:col-span-5 space-y-3.5">
                
                <!-- Paket Terfavorit Pelanggan Baru -->
                <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <h4 class="text-[11px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Paket Populer (Tahun {{ $selectedTahun }})
                        </h4>
                        <span class="text-[10px] text-slate-400">Total User</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($newUserStats['paketBreakdown'] as $pkg)
                            @php
                                $pkgNama = (string) (is_array($pkg) ? ($pkg['nama_paket'] ?? 'INTERNET') : ($pkg->nama_paket ?? 'INTERNET'));
                                $pkgNominal = (string) (is_array($pkg) ? ($pkg['nominal_bandwith'] ?? '10') : ($pkg->nominal_bandwith ?? '10'));
                                $pkgTotal = (int) (is_array($pkg) ? ($pkg['total'] ?? 0) : ($pkg->total ?? 0));
                                $percent = $newUserStats['totalBaru'] > 0 ? round(($pkgTotal / $newUserStats['totalBaru']) * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-800 dark:text-slate-200 mb-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        <span>{{ $pkgNama }} ({{ $pkgNominal }} Mbps)</span>
                                    </span>
                                    <span class="font-mono font-bold text-blue-600 dark:text-cyan-400">{{ $pkgTotal }} User <span class="text-[9px] text-slate-400 font-normal">({{ $percent }}%)</span></span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 transition-all duration-500"
                                         style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-3">Belum ada data paket untuk bulan ini.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Funnel / Progres Pendaftaran Baru -->
                <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-2">
                    <h4 class="text-[11px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        Status Alur Pendaftaran Baru
                    </h4>

                    <div class="grid grid-cols-2 gap-2 text-[11px] pt-1">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                            <span class="text-[9px] text-slate-400 uppercase font-bold">1. Draft Reg</span>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['11'] }}
                            </div>
                        </div>

                        <div class="p-2 rounded-lg bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/50">
                            <span class="text-[9px] text-amber-700 dark:text-amber-400 uppercase font-bold">2. Survey</span>
                            <div class="text-sm font-bold text-amber-600 dark:text-amber-400 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['12'] }}
                            </div>
                        </div>

                        <div class="p-2 rounded-lg bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/50">
                            <span class="text-[9px] text-indigo-700 dark:text-indigo-400 uppercase font-bold">3. Instalasi</span>
                            <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['16'] }}
                            </div>
                        </div>

                        <div class="p-2 rounded-lg bg-cyan-50/70 dark:bg-cyan-950/30 border border-cyan-200/80 dark:border-cyan-800/50">
                            <span class="text-[9px] text-cyan-700 dark:text-cyan-400 uppercase font-bold">4. Aktivasi NOC</span>
                            <div class="text-sm font-bold text-cyan-600 dark:text-cyan-400 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['18_19'] }}
                            </div>
                        </div>
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
                height: 220,
                fontFamily: 'inherit',
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 600
                },
                background: 'transparent'
            },
            colors: ['#0284c7', '#10b981'],
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
            colors: ['#0284c7', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#64748b'],
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
                height: 220,
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
            colors: ['#0284c7', '#10b981'],
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
