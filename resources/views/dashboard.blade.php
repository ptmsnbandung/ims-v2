@extends('layouts.app', ['title' => 'Dashboard Utama'])

@section('page_title', 'Dashboard Utama')

@section('content')
<style>
    /* ------------------------------------------------------------- */
    /* GLASSMORPHISM DASHBOARD THEME STYLING                         */
    /* ------------------------------------------------------------- */
    .ims-glass-canvas {
        background: radial-gradient(at 0% 0%, rgba(219, 234, 254, 0.85) 0px, transparent 50%),
                    radial-gradient(at 100% 0%, rgba(243, 232, 255, 0.85) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(209, 250, 229, 0.75) 0px, transparent 50%),
                    radial-gradient(at 0% 100%, rgba(224, 242, 254, 0.85) 0px, transparent 50%),
                    radial-gradient(at 50% 50%, rgba(245, 243, 255, 0.65) 0px, transparent 50%),
                    linear-gradient(135deg, #eef2ff 0%, #e0f2fe 30%, #f0fdf4 65%, #fdf2f8 100%) !important;
        min-height: calc(100vh - 70px);
        margin: -0.375rem -0.75rem -0.625rem -0.75rem;
        padding: 0.85rem 1rem 2.5rem 1rem;
        transition: all 0.3s ease;
    }
    @media (min-width: 640px) {
        .ims-glass-canvas {
            margin: -0.5rem -1rem -0.625rem -1rem;
            padding: 1rem 1.25rem 3rem 1.25rem;
        }
    }
    @media (min-width: 1024px) {
        .ims-glass-canvas {
            margin: -0.5rem -1.25rem -0.625rem -1.25rem;
            padding: 1.15rem 1.5rem 3.5rem 1.5rem;
        }
    }

    /* Ambient main container background override for dashboard */
    body:has(.ims-glass-canvas) main {
        background: transparent !important;
    }

    /* Translucent Frosted Glass Card */
    .ims-glass-card {
        background: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(20px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.9) !important;
        border-radius: 22px !important;
        box-shadow: 0 10px 30px -5px rgba(31, 38, 135, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    /* Inner Row Items (OLT list, Customer rows) */
    .ims-glass-item {
        background: rgba(255, 255, 255, 0.62) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(255, 255, 255, 0.95) !important;
        border-radius: 14px !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02) !important;
        transition: all 0.15s ease;
    }
    .ims-glass-item:hover {
        background: rgba(255, 255, 255, 0.9) !important;
        border-color: rgba(255, 255, 255, 1) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
    }

    /* Switcher Pill Capsule */
    .ims-glass-pill-container {
        background: rgba(255, 255, 255, 0.55) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(255, 255, 255, 0.85) !important;
        border-radius: 9999px !important;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03) !important;
    }

    /* Year Select Frosted Pill */
    .ims-glass-select {
        background: rgba(255, 255, 255, 0.65) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(255, 255, 255, 0.9) !important;
        border-radius: 9999px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 11px !important;
    }

    /* Green Halo Banner - Exact Deep Ocean Teal Gradient (#043b49 -> #054b5c -> #065b70) */
    .ims-glass-halo-banner {
        background: linear-gradient(135deg, #043b49 0%, #054b5c 50%, #065b70 100%) !important;
        position: relative;
        backdrop-filter: blur(20px) saturate(190%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(190%) !important;
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
        border-radius: 22px !important;
        box-shadow: 0 10px 30px -5px rgba(4, 59, 73, 0.45), 0 2px 6px -1px rgba(0, 0, 0, 0.1) !important;
        color: #ffffff !important;
    }
    .ims-glass-halo-banner::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.16) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(6, 91, 112, 0.35) 0%, transparent 45%);
        pointer-events: none;
        border-radius: 22px;
    }

    /* Halo Switcher Pill Capsule */
    .ims-halo-pill-container {
        background: rgba(0, 0, 0, 0.22) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        border-radius: 9999px !important;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.15) !important;
    }

    /* Halo Year Select Frosted Pill */
    .ims-halo-select {
        background: rgba(255, 255, 255, 0.16) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        border-radius: 9999px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 11px !important;
    }
    .ims-halo-select option {
        color: #0f172a !important;
        background: #ffffff !important;
    }

    /* Halo Status Layanan Button Pill (Crisp White Pill with Deep Teal Text) */
    .ims-halo-btn-status {
        background: #ffffff !important;
        color: #043b49 !important;
        border: 1px solid rgba(255, 255, 255, 0.95) !important;
        border-radius: 9999px !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18) !important;
        font-weight: 800 !important;
        font-size: 11px !important;
        transition: all 0.2s ease;
    }
    .ims-halo-btn-status:hover {
        background: #e6f3f5 !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22) !important;
        transform: translateY(-0.5px);
    }
</style>

<div class="ims-glass-canvas space-y-3 sm:space-y-3.5"
     x-data="{ activeTab: '{{ ($canSeeFinanceTraffic ?? false) && ($user->isCeo() || $user->isFinance()) ? 'finance' : 'charts' }}' }" 
     x-effect="if (activeTab === 'finance') { $nextTick(() => window.dispatchEvent(new Event('resize'))); }">
    
    <!-- Top Green Halo Glass Welcome Bar -->
    <div class="ims-glass-halo-banner relative overflow-hidden py-3 px-3.5 sm:py-3.5 sm:px-5">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <!-- Left: Profile & Welcome -->
            <div class="flex items-center gap-3 min-w-0">
                <!-- Avatar Initial Pill (Pristine White Circle with Deep Teal Text) -->
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white text-[#043b49] font-black text-xs sm:text-sm flex items-center justify-center shadow-md border border-white/90 shrink-0">
                    {{ strtoupper(substr($user->nama, 0, 1)) }}
                </div>
                
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xs sm:text-sm md:text-base font-extrabold text-white tracking-tight flex items-center gap-1.5">
                            <span>Halo, {{ $user->nama }}!</span>
                            <span>👋</span>
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-white/20 text-white border border-white/30 backdrop-blur-md">
                            {{ $user->nama_level }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-semibold bg-[#022832]/60 text-teal-100 border border-teal-400/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-300 animate-pulse"></span>
                            Online
                        </span>
                    </div>
                    <p class="text-[10.5px] sm:text-xs text-teal-100/90 line-clamp-1 sm:line-clamp-none mt-0.5 leading-snug font-medium">
                        {{ $user->role_description }}
                    </p>
                </div>
            </div>

            <!-- Right: View Tabs, Year Filter & Status Layanan -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto shrink-0 mt-1 sm:mt-0">
                <!-- View Tabs Switcher Pill Container -->
                <div class="ims-halo-pill-container w-full sm:w-auto flex items-center justify-between sm:justify-start"
                     style="height: 34px; padding: 3px; box-sizing: border-box;">
                    @if($canSeeFinanceTraffic ?? false)
                        <button type="button" 
                                @click="activeTab = 'finance'" 
                                :style="activeTab === 'finance' 
                                    ? 'display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 28px; border-radius: 9999px; background: #ffffff !important; color: #043b49 !important; font-size: 11px; font-weight: 800; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.22); transition: all 0.15s ease;' 
                                    : 'display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 28px; border-radius: 9999px; background: transparent; color: rgba(255,255,255,0.85) !important; font-size: 11px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease;'"
                                class="flex-1 sm:flex-initial px-3 sm:px-3.5 hover:text-white">
                            <svg class="w-3.5 h-3.5 shrink-0" :style="activeTab === 'finance' ? 'color: #043b49 !important;' : 'color: rgba(255,255,255,0.85) !important;'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span :style="activeTab === 'finance' ? 'color: #043b49 !important;' : 'color: rgba(255,255,255,0.85) !important;'">Traffic Keuangan</span>
                        </button>
                    @endif
                    <button type="button" 
                            @click="activeTab = 'charts'" 
                            :style="activeTab === 'charts' 
                                ? 'display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 28px; border-radius: 9999px; background: #ffffff !important; color: #043b49 !important; font-size: 11px; font-weight: 800; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.22); transition: all 0.15s ease;' 
                                : 'display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 28px; border-radius: 9999px; background: transparent; color: rgba(255,255,255,0.85) !important; font-size: 11px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease;'"
                            class="flex-1 sm:flex-initial px-3 sm:px-3.5 hover:text-white">
                        <svg class="w-3.5 h-3.5 shrink-0" :style="activeTab === 'charts' ? 'color: #043b49 !important;' : 'color: rgba(255,255,255,0.85) !important;'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <span :style="activeTab === 'charts' ? 'color: #043b49 !important;' : 'color: rgba(255,255,255,0.85) !important;'">Grafik Utama</span>
                    </button>
                    <button type="button" 
                            @click="activeTab = 'tables'" 
                            :style="activeTab === 'tables' 
                                ? 'display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 28px; border-radius: 9999px; background: #ffffff !important; color: #043b49 !important; font-size: 11px; font-weight: 800; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.22); transition: all 0.15s ease;' 
                                : 'display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 28px; border-radius: 9999px; background: transparent; color: rgba(255,255,255,0.85) !important; font-size: 11px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease;'"
                            class="flex-1 sm:flex-initial px-3 sm:px-3.5 hover:text-white">
                        <svg class="w-3.5 h-3.5 shrink-0" :style="activeTab === 'tables' ? 'color: #043b49 !important;' : 'color: rgba(255,255,255,0.85) !important;'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span :style="activeTab === 'tables' ? 'color: #043b49 !important;' : 'color: rgba(255,255,255,0.85) !important;'">Data Pendaftaran</span>
                    </button>
                </div>

                <!-- Secondary Actions: Year Selector & Status Layanan -->
                <div class="grid grid-cols-2 sm:flex items-center gap-2 w-full sm:w-auto">
                    <!-- Year Selector Form in Matching Glass Pill -->
                    <form action="{{ route('dashboard') }}" method="GET" class="m-0 p-0 w-full sm:w-auto flex">
                        <div class="relative w-full sm:w-auto flex items-center h-[34px]">
                            <select name="tahun" onchange="this.form.submit()" 
                                    class="ims-halo-select focus:ring-2 focus:ring-white/40"
                                    style="height: 34px; padding-left: 14px; padding-right: 28px; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; cursor: pointer; outline: none; width: 100%; text-align: center; text-align-last: center;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedTahun == (string)$year ? 'selected' : '' }} style="color: #0f172a; background: #ffffff;">
                                        Tahun {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-white/90 flex items-center justify-center">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </span>
                        </div>
                    </form>

                    <!-- Status Layanan Button (Pristine White Pill with Deep Teal Text) -->
                    <a href="{{ route('teknik.tiket') }}" 
                       class="ims-halo-btn-status px-3.5 flex items-center justify-center gap-1.5 transition no-underline h-[34px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#055366] animate-pulse"></span>
                        <span class="truncate">Status Layanan</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Charts Container (Gambar 1 & Gambar 2 - 1 Layar Penuh) -->
    <div x-show="activeTab === 'charts' || activeTab === 'all'" class="space-y-3 sm:space-y-3.5">
        @if($canSeeFinanceTraffic ?? false)
        <!-- Mini Financial Traffic Quick-Strip (Direktur, CEO, Finance) -->
        <div class="ims-glass-card p-2.5 sm:p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-slate-800">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Ringkasan Traffic Keuangan {{ $selectedTahun }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold font-mono bg-emerald-100 text-emerald-700 border border-emerald-200">
                            {{ $financeTrafficData['collectionRateYear'] ?? 0 }}% Terkumpul
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-600 truncate mt-0.5">
                        Realisasi <span class="font-mono font-bold text-emerald-700">Rp {{ number_format($financeTrafficData['totalYearPaid'] ?? 0, 0, ',', '.') }}</span> dari total tagihan <span class="font-mono font-bold text-slate-800">Rp {{ number_format($financeTrafficData['totalYearInvoiced'] ?? 0, 0, ',', '.') }}</span> &middot; Piutang: <span class="font-mono font-bold text-amber-700">Rp {{ number_format($financeTrafficData['totalYearUnpaid'] ?? 0, 0, ',', '.') }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <button type="button" @click="activeTab = 'finance'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10.5px] font-bold bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                    <span>Lihat Rincian & Grafik</span>
                    <svg class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
        @endif

        <!-- Row 1: Tren Bulanan (7 Cols) & Donut Bandwidth (5 Cols) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-3.5">
            <!-- 1. Grafik Tren User Baru per Bulan -->
            <div class="ims-glass-card md:col-span-7 lg:col-span-7 p-3 sm:p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-2 sm:pb-1.5">
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2 leading-tight">
                                <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0" style="display: inline-block;"></span>
                                <span>Tren Pertumbuhan User Baru per Bulan</span>
                            </h4>
                            <p class="text-[10px] sm:text-[9.5px] text-slate-500 mt-0.5 leading-snug">
                                Registrasi vs aktivasi selesai sepanjang {{ $selectedTahun }}.
                            </p>
                        </div>

                        <!-- Mini KPI Badges: 2-column grid on mobile, inline on desktop -->
                        <div class="grid grid-cols-2 sm:flex items-center gap-2 sm:gap-1.5 w-full sm:w-auto shrink-0">
                            <div class="flex items-center justify-between sm:justify-start gap-2 sm:gap-1.5 px-3 sm:px-2.5 py-1 sm:py-0.5 rounded-full bg-blue-50/80 border border-blue-200/60 shadow-2xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                    <span class="text-[8.5px] sm:text-[8px] uppercase font-bold tracking-wider text-blue-600 block leading-none">Registrasi</span>
                                </div>
                                <span class="text-xs font-black font-mono text-slate-800 leading-tight">{{ number_format($chartData['totalTahunRegistrasi']) }}</span>
                            </div>
                            <div class="flex items-center justify-between sm:justify-start gap-2 sm:gap-1.5 px-3 sm:px-2.5 py-1 sm:py-0.5 rounded-full bg-emerald-50/80 border border-emerald-200/60 shadow-2xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[8.5px] sm:text-[8px] uppercase font-bold tracking-wider text-emerald-600 block leading-none">Aktif Online</span>
                                </div>
                                <span class="text-xs font-black font-mono text-slate-800 leading-tight">{{ number_format($chartData['totalTahunAktif']) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ApexChart Container (Tinggi: 155px) -->
                    <div class="pt-1">
                        <div id="chart-user-monthly-trend" class="w-full" style="min-height: 155px;"></div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="mt-1 pt-1.5 border-t border-slate-200/60 flex items-center justify-between text-[9px] sm:text-[8.5px] text-slate-500 flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span class="font-medium text-slate-700">Pendaftaran Baru</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-slate-700">Aktivasi Selesai (Aktif)</span>
                        </span>
                    </div>
                    <span class="font-mono text-slate-400">Jan - Des {{ $selectedTahun }}</span>
                </div>
            </div>

            <!-- 2. Donut Kategori Bandwidth & Itemized List Side-by-Side -->
            <div class="ims-glass-card md:col-span-5 lg:col-span-5 p-3 sm:p-3.5 flex flex-col justify-between">
                <div>
                    <div class="border-b border-slate-200/60 pb-2 sm:pb-1.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2 leading-tight">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0" style="display: inline-block;"></span>
                            <span>Distribusi Kategori Bandwidth</span>
                        </h4>
                        <p class="text-[10px] sm:text-[9.5px] text-slate-500 mt-0.5 leading-snug">
                            Pangsa paket layanan yang dipilih pelanggan.
                        </p>
                    </div>

                    <!-- Donut & Detailed List (Stacked on mobile, side-by-side on desktop) -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center gap-3 sm:gap-2.5">
                        <!-- Donut Chart -->
                        <div class="w-full sm:w-44 md:w-48 shrink-0 flex items-center justify-center">
                            <div id="chart-bandwidth-distribution" class="w-full max-w-[200px] sm:max-w-none mx-auto" style="min-height: 155px;"></div>
                        </div>

                        <!-- Detailed List -->
                        <div class="w-full flex-1 min-w-0 max-h-[175px] sm:max-h-[155px] overflow-y-auto pr-0.5">
                            @php
                                $paletteColors = ['#0284c7', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'];
                                $totalBwAll = array_sum($chartData['bandwidthSeries']);
                            @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-1 gap-1 sm:gap-0.5">
                                @foreach($chartData['bandwidthLabels'] as $idx => $label)
                                    @php
                                        $val = $chartData['bandwidthSeries'][$idx] ?? 0;
                                        $pct = $totalBwAll > 0 ? round(($val / $totalBwAll) * 100, 1) : 0;
                                        $dotColor = $paletteColors[$idx % count($paletteColors)];
                                    @endphp
                                    <div class="flex items-center justify-between text-[10.5px] sm:text-[9px] py-1 px-2.5 rounded-lg bg-white/55 border border-white/80 hover:bg-white/90 transition shadow-2xs">
                                        <div class="flex items-center gap-2 min-w-0 pr-1">
                                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $dotColor }};"></span>
                                            <span class="font-bold text-slate-700 truncate uppercase text-[9.5px]" title="{{ $label }}">{{ $label }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 font-mono shrink-0 ml-1">
                                            <span class="font-extrabold text-slate-800">{{ number_format($val) }}</span>
                                            <span class="text-[8.5px] text-slate-500 font-semibold">({{ $pct }}%)</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 sm:mt-0.5 pt-1.5 sm:pt-1 border-t border-slate-200/60 flex items-center justify-between text-[9px] sm:text-[8px] text-slate-500">
                    <span>Total Porsi Layanan</span>
                    <span class="font-bold font-mono text-slate-700">{{ number_format($totalBwAll) }} User</span>
                </div>
            </div>
        </div>

        <!-- Row 2: 3 Kotak Data (Sebaran Wilayah, Status OLT, & Data Pelanggan Baru 5 User) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-3.5">
            <!-- 3. Grafik Sebaran User per Kota / Wilayah Pasang -->
            <div class="ims-glass-card md:col-span-4 lg:col-span-4 p-3 sm:p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-2 sm:pb-1.5">
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2 leading-tight">
                                <span class="w-2 h-2 rounded-full bg-cyan-500 shrink-0" style="display: inline-block;"></span>
                                <span>Sebaran User per Wilayah</span>
                            </h4>
                            <p class="text-[10px] sm:text-[9.5px] text-slate-500 mt-0.5 leading-snug">
                                Distribusi per pola pasang.
                            </p>
                        </div>

                        <!-- Total City User Badge -->
                        <div class="flex items-center justify-between sm:justify-start gap-2 sm:gap-1.5 px-3 sm:px-2.5 py-1 sm:py-0.5 rounded-full bg-sky-50/80 border border-sky-200/60 shadow-2xs w-full sm:w-auto shrink-0">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                <span class="text-[8.5px] sm:text-[7.5px] uppercase font-bold tracking-wider text-sky-600 block leading-none">Total</span>
                            </div>
                            <span class="text-xs sm:text-[11px] font-black font-mono text-slate-800 leading-tight">{{ number_format($chartData['totalCityUsers']) }}</span>
                        </div>
                    </div>

                    <!-- ApexChart Container (Memenuhi Card: 155px) -->
                    <div class="pt-1">
                        <div id="chart-city-distribution" class="w-full" style="min-height: 155px;"></div>
                    </div>
                </div>

                <div class="mt-1 pt-1.5 border-t border-slate-200/60 flex items-center justify-between text-[9px] sm:text-[8.5px] text-slate-500">
                    <div class="flex items-center gap-1.5">
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            <span class="font-medium text-slate-700">Total</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-slate-700">Aktif</span>
                        </span>
                    </div>
                    <a href="{{ route('teknik.pelanggan') }}" class="font-bold text-blue-600 hover:underline flex items-center gap-0.5">
                        <span>Data Pelanggan</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 4. Status OLT Live (Cukup Nama OLT & Statusnya Saja) -->
            <div class="ims-glass-card md:col-span-4 lg:col-span-4 p-3 sm:p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-2 sm:pb-1.5">
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2 leading-tight">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" style="display: inline-block;"></span>
                                <span>Status OLT Live</span>
                            </h4>
                            <p class="text-[10px] sm:text-[9.5px] text-slate-500 mt-0.5 leading-snug">
                                Status konektivitas OLT real-time.
                            </p>
                        </div>

                        <!-- Live Pulse Indicator Pill -->
                        <div class="flex items-center justify-between sm:justify-start gap-2 sm:gap-1.5 px-3 sm:px-2 py-1 sm:py-0.5 rounded-full bg-emerald-50/80 border border-emerald-200/60 shadow-2xs w-full sm:w-auto shrink-0">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-[8.5px] sm:text-[8px] uppercase font-bold tracking-wider text-emerald-600 leading-none">Live Ping</span>
                            </div>
                            <span class="text-[10px] sm:text-[8px] font-mono text-emerald-600 font-bold sm:hidden">Online</span>
                        </div>
                    </div>

                    <div class="pt-2 space-y-1.5">
                        @forelse($oltLiveStatus ?? [] as $oltItem)
                            @php
                                $isUp = ($oltItem['status'] ?? 'UP') === 'UP' || ($oltItem['is_online'] ?? false);
                            @endphp
                            <div class="ims-glass-item p-2 flex items-center justify-between gap-2 transition">
                                <!-- Nama OLT -->
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2 h-2 rounded-full {{ $isUp ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }} shrink-0"></span>
                                    <span class="font-bold text-[11px] text-slate-800 truncate" title="{{ $oltItem['name_olt'] ?? 'OLT' }}">
                                        {{ $oltItem['name_olt'] ?? 'OLT' }}
                                    </span>
                                </div>
                                
                                <!-- Status OLT -->
                                <div class="flex-shrink-0">
                                    @if($isUp)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100/90 text-emerald-700 border border-emerald-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>UP</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-100/90 text-rose-700 border border-rose-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>DOWN</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-3 text-center text-[10px] text-slate-400">
                                Belum ada data OLT terdaftar
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-1 pt-1.5 border-t border-slate-200/60 flex items-center justify-between text-[9px] sm:text-[8.5px] text-slate-500">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Total: <strong class="text-slate-700 font-mono">{{ count($oltLiveStatus ?? []) }} OLT</strong></span>
                    </span>
                    <a href="{{ route('noc.olt') }}" class="font-bold text-blue-600 hover:underline flex items-center gap-0.5">
                        <span>Data OLT</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 5. Data Pelanggan Baru (5 User Terkini) -->
            <div class="ims-glass-card md:col-span-4 lg:col-span-4 p-3 sm:p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-2 sm:pb-1.5">
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2 leading-tight">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0" style="display: inline-block;"></span>
                                <span>Data Pelanggan Baru</span>
                            </h4>
                            <p class="text-[10px] sm:text-[9.5px] text-slate-500 mt-0.5 leading-snug">
                                5 pendaftaran user terkini ({{ $selectedTahun }}).
                            </p>
                        </div>

                        <div class="flex items-center justify-between sm:justify-start gap-2 sm:gap-1.5 px-3 sm:px-2.5 py-1 sm:py-0.5 rounded-full bg-indigo-50/80 border border-indigo-200/60 shadow-2xs w-full sm:w-auto shrink-0">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                <span class="text-[8.5px] sm:text-[8px] uppercase font-bold tracking-wider text-indigo-700">Terkini</span>
                            </div>
                            <span class="text-xs sm:text-[8.5px] font-bold font-mono text-indigo-700">5 User</span>
                        </div>
                    </div>

                    <!-- List 5 User (Nomor Internet & Nama Saja - Rapi & Compact) -->
                    @php
                        $recent5Users = $newUserStats['recentNewUsers'] ?? [];
                        if (is_object($recent5Users) && get_class($recent5Users) === '__PHP_Incomplete_Class') {
                            $recent5Users = [];
                        }
                        if (is_object($recent5Users) && method_exists($recent5Users, 'take')) {
                            $recent5Users = $recent5Users->take(5);
                        } elseif (is_array($recent5Users)) {
                            $recent5Users = array_slice($recent5Users, 0, 5);
                        } elseif (!is_iterable($recent5Users)) {
                            $recent5Users = [];
                        }
                    @endphp
                    <div class="pt-2 space-y-1">
                        @forelse($recent5Users as $nu)
                            @php
                                $nuNoInternet = (string) (is_array($nu) ? ($nu['nomor_internet'] ?? '') : ($nu->nomor_internet ?? ''));
                                $nuNama = (string) (is_array($nu) ? ($nu['nama_pelanggan'] ?? 'Pelanggan') : ($nu->nama_pelanggan ?? 'Pelanggan'));
                                $profileUrl = !empty($nuNoInternet) ? route('teknik.pelanggan.profile', $nuNoInternet) : '#';
                            @endphp
                            <div class="ims-glass-item flex items-center justify-between px-2.5 py-1.5 transition group">
                                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100/90 border border-slate-200/80 text-slate-700 text-[9px] font-black flex items-center justify-center font-mono shrink-0 shadow-2xs">
                                        {{ $loop->iteration }}
                                    </span>
                                    <span class="text-[10.5px] font-bold text-slate-800 uppercase tracking-tight truncate group-hover:text-blue-600 transition" title="{{ $nuNama }}">
                                        {{ $nuNama }}
                                    </span>
                                </div>
                                <div class="shrink-0">
                                    @if(!empty($nuNoInternet))
                                        <a href="{{ $profileUrl }}" class="px-2.5 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 font-mono text-[9.5px] font-bold tracking-wider hover:bg-blue-600 hover:text-white hover:border-blue-600 shadow-2xs transition inline-block">
                                            {{ $nuNoInternet }}
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[9px] font-mono">-</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-2 text-center text-slate-400 text-[9px]">
                                Belum ada data pendaftaran tahun {{ $selectedTahun }}.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-1 pt-1.5 border-t border-slate-200/60 flex items-center justify-between text-[8.5px] text-slate-500">
                    <span>Total <b>{{ number_format($newUserStats['totalBaru']) }}</b> User</span>
                    <a href="{{ route('teknik.pendaftaran') }}" class="font-bold text-blue-600 hover:underline flex items-center gap-0.5">
                        <span>Semua Data</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($canSeeFinanceTraffic ?? false)
    <!-- ========================================================================= -->
    <!-- SECTION: TRAFFIC KEUANGAN & CASHFLOW (CEO, DIREKTUR & FINANCE)           -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'finance'" x-cloak class="space-y-3 sm:space-y-3.5">
        
        <!-- Row 1: 4 KPI Cards (High Impact, Premium FinTech Console) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-3.5">
            <!-- 1. Total Tagihan Terbit -->
            <div class="ims-glass-card p-3.5 sm:p-4 flex flex-col justify-between hover:border-sky-500/40 transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Tagihan {{ $selectedTahun }}</span>
                    <div class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 border border-sky-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5L19 7.5V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black font-mono tracking-tight text-slate-900 dark:text-white">
                        Rp {{ number_format($financeTrafficData['totalYearInvoiced'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-[10.5px] text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ number_format(($financeTrafficData['paidCountYear'] ?? 0) + ($financeTrafficData['unpaidCountYear'] ?? 0)) }}</span>
                        <span>invoice billing terbit</span>
                    </div>
                </div>
            </div>

            <!-- 2. Realisasi Pembayaran Lunas (Cash In) -->
            <div class="ims-glass-card p-3.5 sm:p-4 flex flex-col justify-between hover:border-emerald-500/50 transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Realisasi Lunas (Cash In)</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($financeTrafficData['totalYearPaid'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-[10.5px] text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($financeTrafficData['paidCountYear'] ?? 0) }}</span>
                        <span>invoice lunas terverifikasi</span>
                    </div>
                </div>
            </div>

            <!-- 3. Piutang Berjalan (Belum Lunas) -->
            <div class="ims-glass-card p-3.5 sm:p-4 flex flex-col justify-between hover:border-amber-500/50 transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Piutang Berjalan</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black font-mono tracking-tight text-amber-600 dark:text-amber-400">
                        Rp {{ number_format($financeTrafficData['totalYearUnpaid'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-[10.5px] text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-amber-600 dark:text-amber-400 font-mono">{{ number_format($financeTrafficData['unpaidCountYear'] ?? 0) }}</span>
                        <span>invoice belum diselesaikan</span>
                    </div>
                </div>
            </div>

            <!-- 4. Efektivitas Collection Rate -->
            <div class="ims-glass-card p-3.5 sm:p-4 flex flex-col justify-between hover:border-indigo-500/50 transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Collection Rate</span>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ ($financeTrafficData['collectionRateYear'] ?? 0) >= 80 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' }}">
                        {{ ($financeTrafficData['collectionRateYear'] ?? 0) >= 80 ? 'Optimal' : 'Perhatian' }}
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-lg sm:text-xl font-black font-mono tracking-tight text-indigo-600 dark:text-indigo-400">
                            {{ $financeTrafficData['collectionRateYear'] ?? 0 }}%
                        </span>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Target: 85%</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ min(100, $financeTrafficData['collectionRateYear'] ?? 0) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Charts (Flex Container - 67% Monthly Trend & 33% Donut Status) -->
        <div class="flex flex-col lg:flex-row gap-3 sm:gap-3.5 items-stretch w-full">
            <!-- 1. Grafik Tren Tagihan & Realisasi Bulanan (67% Width) -->
            <div class="ims-glass-card w-full lg:w-[67%] min-w-0 p-3.5 sm:p-4 flex flex-col justify-between"
                 style="flex: 2 1 0%; min-width: 0;">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-2.5">
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2 leading-tight">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span>Traffic Arus Kas Keuangan Bulanan</span>
                            </h4>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 leading-snug">
                                Perbandingan realisasi kas masuk (lunas) vs tagihan terbit & piutang per bulan {{ $selectedTahun }}.
                            </p>
                        </div>

                        <!-- Mini Legend Indicators -->
                        <div class="flex items-center gap-2.5 text-[9.5px] font-semibold text-slate-600 dark:text-slate-300">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#10b981]"></span> Realisasi Lunas
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#f59e0b]"></span> Piutang
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#0284c7]"></span> Invoiced
                            </span>
                        </div>
                    </div>

                    <!-- ApexChart Container -->
                    <div class="pt-2 w-full overflow-hidden">
                        <div id="chart-finance-monthly-traffic" class="w-full" style="min-height: 250px;"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Donut Rasio Koleksi & Ringkasan (33% Width) -->
            <div class="ims-glass-card w-full lg:w-[33%] min-w-0 p-3.5 sm:p-4 flex flex-col justify-between"
                 style="flex: 1 1 0%; min-width: 0;">
                <div>
                    <div class="border-b border-slate-200/60 pb-2.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2 leading-tight">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span>
                            <span>Rasio Status Penagihan</span>
                        </h4>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 leading-snug">
                            Proporsi pelunasan billing tahun {{ $selectedTahun }}.
                        </p>
                    </div>

                    <!-- Donut ApexChart -->
                    <div class="pt-1 flex items-center justify-center">
                        <div id="chart-finance-collection-donut" class="w-full" style="min-height: 195px;"></div>
                    </div>
                </div>

                <!-- Structured Mini KPI Stats Breakdown -->
                <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between text-[11px] p-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                        <span class="text-slate-500 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Realisasi Lunas
                        </span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($financeTrafficData['totalYearPaid'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] p-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                        <span class="text-slate-500 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Piutang Berjalan
                        </span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400">
                            Rp {{ number_format($financeTrafficData['totalYearUnpaid'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] pt-1 px-1 text-slate-400">
                        <span>Total: {{ number_format(($financeTrafficData['paidCountYear'] ?? 0) + ($financeTrafficData['unpaidCountYear'] ?? 0)) }} Transaksi</span>
                        @php
                            $totalInvCount = ($financeTrafficData['paidCountYear'] ?? 0) + ($financeTrafficData['unpaidCountYear'] ?? 0);
                            $avgNominal = $totalInvCount > 0 ? round(($financeTrafficData['totalYearInvoiced'] ?? 0) / $totalInvCount) : 0;
                        @endphp
                        <span>Rata-rata: <b class="font-mono text-slate-600 dark:text-slate-300">Rp {{ number_format($avgNominal, 0, ',', '.') }}</b></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Monthly Breakdown Table (67% Width) & Recent Invoices (33% Width) -->
        <div class="flex flex-col lg:flex-row gap-3 sm:gap-3.5 items-stretch w-full">
            <!-- 1. Tabel Rincian Traffic Keuangan per Bulan -->
            <div class="ims-glass-card w-full lg:w-[67%] min-w-0 p-3.5 sm:p-4 flex flex-col justify-between"
                 style="flex: 2 1 0%; min-width: 0;">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Rincian Traffic Tagihan Bulanan (12 Bulan)</span>
                            </h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Arus kas billing per bulan kalender tahun {{ $selectedTahun }}.</p>
                        </div>
                        <span class="text-[9.5px] px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 font-bold font-mono text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Tahun {{ $selectedTahun }}
                        </span>
                    </div>

                    <div class="overflow-x-auto mt-2.5">
                        <table class="w-full text-left text-[11px] whitespace-nowrap">
                            <thead>
                                <tr class="text-[9.5px] uppercase font-bold text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                                    <th class="py-2 px-2.5">Bulan</th>
                                    <th class="py-2 px-2.5 text-right">Tagihan Terbit</th>
                                    <th class="py-2 px-2.5 text-right">Realisasi Lunas</th>
                                    <th class="py-2 px-2.5 text-right">Piutang</th>
                                    <th class="py-2 px-2.5 text-center">Collection Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100/70 dark:divide-slate-800/60 font-mono">
                                @forelse($financeTrafficData['monthlyBreakdown'] ?? [] as $mb)
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                        <td class="py-2 px-2.5 font-sans font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $mb['total_nominal'] > 0 ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700' }}"></span>
                                            <span>{{ $mb['month_name'] }}</span>
                                        </td>
                                        <td class="py-2 px-2.5 text-right font-semibold text-slate-700 dark:text-slate-300">
                                            Rp {{ number_format($mb['total_nominal'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                            Rp {{ number_format($mb['paid_nominal'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-2.5 text-right font-medium {{ $mb['unpaid_nominal'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                            Rp {{ number_format($mb['unpaid_nominal'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-2.5 text-center">
                                            @if($mb['total_nominal'] > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9.5px] font-bold {{ $mb['collection_rate'] >= 80 ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800' }}">
                                                    {{ $mb['collection_rate'] }}%
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-[10px] font-sans">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-slate-400 font-sans">
                                            Belum ada data traffic keuangan di tahun {{ $selectedTahun }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="border-t-2 border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-900 dark:text-white bg-slate-50/60 dark:bg-slate-800/40">
                                <tr>
                                    <td class="py-2.5 px-2.5 font-sans uppercase text-[10px] tracking-wider">Total {{ $selectedTahun }}</td>
                                    <td class="py-2.5 px-2.5 text-right text-sky-600 dark:text-sky-400">
                                        Rp {{ number_format($financeTrafficData['totalYearInvoiced'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right text-emerald-600 dark:text-emerald-400">
                                        Rp {{ number_format($financeTrafficData['totalYearPaid'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right text-amber-600 dark:text-amber-400">
                                        Rp {{ number_format($financeTrafficData['totalYearUnpaid'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-2.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            {{ $financeTrafficData['collectionRateYear'] ?? 0 }}%
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Recent Invoices List -->
            <div class="ims-glass-card w-full lg:w-[33%] min-w-0 p-3.5 sm:p-4 flex flex-col justify-between"
                 style="flex: 1 1 0%; min-width: 0;">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 shrink-0"></span>
                            <span>Invoice Billing Terbaru</span>
                        </h4>
                        <a href="{{ route('finance.billing-layanan') }}" class="text-[10px] font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                            Semua Data &rarr;
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800 mt-1">
                        @forelse($financeTrafficData['recentInvoices'] ?? [] as $inv)
                            @php
                                $invArr = is_array($inv) ? $inv : (array)$inv;
                                $kodeInv = $invArr['kode_billing_layanan'] ?? $invArr['kode_billing'] ?? '-';
                                $nominalInv = (float)($invArr['total_layanan'] ?? $invArr['total_biaya'] ?? 0);
                                $periodeInv = $invArr['periode_tagihan'] ?? (($invArr['bulan_tagihan'] ?? $invArr['bulan_ke'] ?? '') . '/' . ($invArr['tahun_tagihan'] ?? $invArr['tahun_ke'] ?? ''));
                                $pelangganInv = $invArr['nama_pelanggan'] ?? $invArr['nama_lengkap'] ?? $invArr['nomor_internet'] ?? '-';
                                $statusInv = (string)($invArr['status_bill_lay'] ?? '13');
                            @endphp
                            <div class="py-2.5 flex items-center justify-between gap-2.5">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-[10.5px] font-bold font-mono text-slate-900 dark:text-white truncate">
                                            {{ $kodeInv }}
                                        </span>
                                        @if($statusInv === '15')
                                            <span class="text-[8px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">Lunas</span>
                                        @elseif(in_array($statusInv, ['13', '14']))
                                            <span class="text-[8px] font-bold px-1.5 py-0.2 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">Menunggu</span>
                                        @else
                                            <span class="text-[8px] font-bold px-1.5 py-0.2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Draft</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                        {{ $pelangganInv }} &middot; Periode {{ $periodeInv }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-[11px] font-extrabold font-mono text-slate-900 dark:text-white">
                                        Rp {{ number_format($nominalInv, 0, ',', '.') }}
                                    </div>
                                    <a href="{{ route('finance.dokumen.invoice', $kodeInv) }}" target="_blank" class="text-[9px] font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                                        Lihat Invoice &rarr;
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400 text-xs">
                                Belum ada riwayat transaksi billing.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[9.5px] text-slate-500">
                    <span>Modul Keuangan Terintegrasi</span>
                    <a href="{{ route('finance.billing-layanan') }}" class="font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        Buka Billing Layanan &rarr;
                    </a>
                </div>
            </div>
        </div>

    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECTION: STATISTIK USER & PELANGGAN BARU (DENGAN FILTER BULAN & TAHUN)   -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'tables' || activeTab === 'all'" x-cloak class="space-y-3.5 pt-1">
        
        <!-- Header & Filter Bar Section -->
        <div class="ims-glass-card p-3 sm:p-3.5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
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
            <div class="ims-glass-card p-3 sm:p-3.5 hover:border-blue-400 transition">
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
            <div class="ims-glass-card p-3 sm:p-3.5 hover:border-emerald-500 transition">
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
            <div class="ims-glass-card p-3 sm:p-3.5 hover:border-amber-400 transition">
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
                <div class="mt-2 text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 flex-wrap">
                    <span>Survey: <b>{{ $newUserStats['statusBreakdown']['12'] }}</b></span>
                    <span>&middot;</span>
                    <span>Instalasi: <b>{{ $newUserStats['statusBreakdown']['16'] }}</b></span>
                    <span>&middot;</span>
                    <span>Aktivasi: <b>{{ $newUserStats['statusBreakdown']['18_19'] }}</b></span>
                </div>
            </div>

            <!-- 4. Total Pelanggan Aktif Sistem -->
            <div class="ims-glass-card p-3 sm:p-3.5 hover:border-cyan-500 transition">
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

                <div class="ims-glass-card rounded-xl overflow-hidden">
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
                                @php
                                    $modalRecentList = $newUserStats['recentNewUsers'] ?? [];
                                    if (!is_iterable($modalRecentList) || (is_object($modalRecentList) && get_class($modalRecentList) === '__PHP_Incomplete_Class')) {
                                        $modalRecentList = [];
                                    }
                                @endphp
                                @forelse($modalRecentList as $nu)
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
                <div class="ims-glass-card p-3 sm:p-3.5 space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <h4 class="text-[11px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Paket Populer (Tahun {{ $selectedTahun }})
                        </h4>
                        <span class="text-[10px] text-slate-400">Total User</span>
                    </div>

                    <div class="space-y-2">
                        @php
                            $modalPaketList = $newUserStats['paketBreakdown'] ?? [];
                            if (!is_iterable($modalPaketList) || (is_object($modalPaketList) && get_class($modalPaketList) === '__PHP_Incomplete_Class')) {
                                $modalPaketList = [];
                            }
                        @endphp
                        @forelse($modalPaketList as $pkg)
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
                <div class="ims-glass-card p-3 sm:p-3.5 space-y-2">
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
                height: 155,
                fontFamily: 'inherit',
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 600
                },
                background: 'transparent'
            },
            colors: ['#2563eb', '#10b981'],
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
                        fontSize: '9px',
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
                        fontSize: '9px',
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
                padding: { top: 2, right: 8, bottom: -6, left: 6 }
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
                height: 155,
                fontFamily: 'inherit',
                background: 'transparent'
            },
            colors: ['#0284c7', '#00b074', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#64748b'],
            stroke: {
                width: 2.5,
                colors: isDark ? ['#0f172a'] : ['#ffffff']
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '8px',
                                fontWeight: 700,
                                color: textColor,
                                offsetY: -3
                            },
                            value: {
                                show: true,
                                fontSize: '15px',
                                fontWeight: 900,
                                color: isDark ? '#ffffff' : '#0f172a',
                                offsetY: 3,
                                formatter: (val) => val
                            },
                            total: {
                                show: true,
                                label: 'Total User',
                                fontSize: '8px',
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
                height: 155,
                fontFamily: 'inherit',
                toolbar: { show: false },
                background: 'transparent'
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '52%',
                    borderRadius: 4,
                    borderRadiusApplication: 'end',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: ['#2563eb', '#10b981'],
            dataLabels: {
                enabled: true,
                style: {
                    colors: isDark ? ['#e2e8f0'] : ['#1e293b'],
                    fontSize: '9px',
                    fontWeight: 700
                },
                offsetY: -14,
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
                        fontSize: '9px',
                        fontWeight: 700
                    },
                    rotate: 0,
                    trim: false,
                    formatter: function(val) {
                        if (!val) return '';
                        if (val === 'KABUPATEN BANDUNG') return 'KAB. BDG';
                        if (val === 'KOTA BANDUNG') return 'KOTA BDG';
                        return val;
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                show: false
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
                padding: { top: 16, right: 6, bottom: -6, left: 6 },
                yaxis: { lines: { show: false } },
                xaxis: { lines: { show: false } }
            },
            legend: {
                show: false
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

    @if($canSeeFinanceTraffic ?? false)
    // -------------------------------------------------------------
    // 5. CHART: TRAFFIC & ARUS KAS KEUANGAN BULANAN
    // -------------------------------------------------------------
    const financeMonthlyLabels = @json($financeTrafficData['monthlyLabels'] ?? []);
    const financePaidNominals = @json($financeTrafficData['paidNominals'] ?? []);
    const financeUnpaidNominals = @json($financeTrafficData['unpaidNominals'] ?? []);
    const financeTotalNominals = @json($financeTrafficData['totalNominals'] ?? []);

    const formatRupiah = (val) => {
        return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
    };

    const finTrendEl = document.querySelector('#chart-finance-monthly-traffic');
    if (finTrendEl) {
        const optionsFinTrend = {
            series: [
                {
                    name: 'Realisasi Lunas',
                    data: financePaidNominals
                },
                {
                    name: 'Piutang Berjalan',
                    data: financeUnpaidNominals
                },
                {
                    name: 'Total Tagihan',
                    data: financeTotalNominals
                }
            ],
            chart: {
                type: 'area',
                height: 250,
                fontFamily: 'inherit',
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 600
                },
                background: 'transparent'
            },
            colors: ['#10b981', '#f59e0b', '#0284c7'],
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
                width: [2.5, 2, 1.5],
                dashArray: [0, 0, 4]
            },
            markers: {
                size: 3,
                strokeWidth: 1.5,
                hover: { size: 5 }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: financeMonthlyLabels,
                labels: {
                    style: {
                        colors: textColor,
                        fontSize: '9.5px',
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
                        fontSize: '9.5px',
                        fontWeight: 500
                    },
                    formatter: (val) => {
                        if (val >= 1000000000) return (val / 1000000000).toFixed(1) + ' M';
                        if (val >= 1000000) return (val / 1000000).toFixed(0) + ' Jt';
                        if (val >= 1000) return (val / 1000).toFixed(0) + ' Rb';
                        return val;
                    }
                },
                min: 0,
                forceNiceScale: true
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
                padding: { top: 6, right: 12, bottom: -6, left: 6 }
            },
            legend: {
                show: false
            },
            tooltip: {
                theme: tooltipTheme,
                y: {
                    formatter: function (val) {
                        return formatRupiah(val);
                    }
                }
            }
        };

        const chartFinTrend = new ApexCharts(finTrendEl, optionsFinTrend);
        chartFinTrend.render();
        window.chartFinTrend = chartFinTrend;
    }

    // -------------------------------------------------------------
    // 6. CHART: STATUS KOLEKSI / DONUT (LUNAS VS PIUTANG)
    // -------------------------------------------------------------
    const finDonutEl = document.querySelector('#chart-finance-collection-donut');
    if (finDonutEl) {
        const totalPaid = {{ (float) ($financeTrafficData['totalYearPaid'] ?? 0) }};
        const totalUnpaid = {{ (float) ($financeTrafficData['totalYearUnpaid'] ?? 0) }};
        const hasData = (totalPaid + totalUnpaid) > 0;

        const optionsFinDonut = {
            series: hasData ? [totalPaid, totalUnpaid] : [1],
            labels: hasData ? ['Realisasi Lunas', 'Piutang Berjalan'] : ['Belum Ada Tagihan'],
            chart: {
                type: 'donut',
                height: 195,
                fontFamily: 'inherit',
                background: 'transparent'
            },
            colors: hasData ? ['#10b981', '#f59e0b'] : ['#64748b'],
            stroke: {
                width: 2.5,
                colors: isDark ? ['#0f172a'] : ['#ffffff']
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '10px',
                                fontWeight: 700,
                                color: textColor,
                                offsetY: -4
                            },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 900,
                                fontFamily: 'monospace',
                                color: isDark ? '#ffffff' : '#0f172a',
                                offsetY: 4,
                                formatter: () => '{{ $financeTrafficData['collectionRateYear'] ?? 0 }}%'
                            },
                            total: {
                                show: true,
                                label: 'Terkumpul',
                                fontSize: '9px',
                                fontWeight: 700,
                                color: textColor,
                                formatter: () => '{{ $financeTrafficData['collectionRateYear'] ?? 0 }}%'
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: {
                show: false
            },
            tooltip: {
                theme: tooltipTheme,
                y: {
                    formatter: (val) => formatRupiah(val)
                }
            }
        };

        const chartFinDonut = new ApexCharts(finDonutEl, optionsFinDonut);
        chartFinDonut.render();
        window.chartFinDonut = chartFinDonut;
    }
    @endif
});
</script>
@endpush
