@extends('layouts.app', ['title' => 'Tiket - IMS Router'])

@section('page_title', 'Tiket')

@section('content')
<style>
    /* Pulsing notification badge */
    @keyframes imsPingPulse {
        0% { transform: scale(1); opacity: 0.9; }
        70% { transform: scale(2.4); opacity: 0; }
        100% { transform: scale(2.4); opacity: 0; }
    }

    .ims-ticket-card {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-radius: 1rem;
        padding: 1.15rem 1.25rem;
        text-decoration: none !important;
        color: #ffffff !important;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
        min-height: 125px;
    }

    .ims-ticket-card:hover {
        transform: translateY(-3px);
        color: #ffffff !important;
    }

    /* Ambient shine inside card */
    .ims-card-shine {
        position: absolute;
        top: 0;
        right: 0;
        width: 130px;
        height: 130px;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.28), transparent 70%);
        pointer-events: none;
    }

    /* Icon Bubble */
    .ims-icon-bubble {
        width: 2.85rem;
        height: 2.85rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid rgba(255, 255, 255, 0.32);
        box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.25), 0 4px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, background 0.3s ease;
    }

    .ims-ticket-card:hover .ims-icon-bubble {
        transform: scale(1.1) rotate(3deg);
        background: rgba(255, 255, 255, 0.28);
    }

    /* Pill Action Button */
    .ims-pill-button {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #ffffff !important;
        padding: 0.28rem 0.65rem;
        border-radius: 9999px;
        margin-top: 0.65rem;
        border: 1px solid rgba(255, 255, 255, 0.32);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        transition: all 0.2s ease;
    }

    .ims-ticket-card:hover .ims-pill-button {
        background: #ffffff !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    }

    /* Card Specific Hover Text Colors for the Pill Button */
    .ims-card-gangguan:hover .ims-pill-button { color: #1d4ed8 !important; }
    .ims-card-password:hover .ims-pill-button { color: #be185d !important; }
    .ims-card-relokasi:hover .ims-pill-button { color: #6d28d9 !important; }
    .ims-card-coverage:hover .ims-pill-button { color: #b45309 !important; }
    .ims-card-terminasi:hover .ims-pill-button { color: #be123c !important; }
    .ims-card-suspend:hover .ims-pill-button { color: #c2410c !important; }
    .ims-card-pasang:hover .ims-pill-button { color: #047857 !important; }
    .ims-card-ubah:hover .ims-pill-button { color: #0284c7 !important; }

    /* Notification Alert Ping in Top Right */
    .ims-alert-badge {
        position: absolute;
        top: 11px;
        right: 11px;
        width: 12px;
        height: 12px;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ims-alert-ping {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 9999px;
        background-color: #fecdd3;
        animation: imsPingPulse 1.6s cubic-bezier(0, 0, 0.2, 1) infinite;
    }

    .ims-alert-core {
        position: relative;
        width: 9px;
        height: 9px;
        border-radius: 9999px;
        background-color: #ef4444;
        border: 2px solid #ffffff;
        box-shadow: 0 0 6px rgba(239, 68, 68, 0.9), 0 1px 3px rgba(0,0,0,0.3);
    }
</style>

<div class="space-y-4">
    <!-- Header with Breadcrumbs, KPI Summary, & Role Indicator -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pb-3 border-b border-slate-200/90 dark:border-slate-800/90">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white border border-blue-400/30 flex items-center justify-center shadow-md shadow-blue-500/20">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        Pusat Tiket & Permintaan
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pilih kategori tiket di bawah untuk membuka dashboard masing-masing modul sesuai hak akses role Anda.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
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
            <nav class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500 px-1">
                <span class="hover:text-slate-700 dark:hover:text-slate-300 transition">IMS</span>
                <span>&gt;</span>
                <span class="text-blue-600 dark:text-blue-400 font-semibold">Tiket</span>
            </nav>
        </div>
    </div>

    <!-- Ticket Category Cards Grid (4 Columns Desktop = 2 Balanced Rows of 4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Gangguan Layanan (Royal Blue Gradient) -->
        <a href="{{ $destinations['gangguan'] ?? route('teknik.tiket.gangguan', ['kategori' => 'gangguan']) }}"
           class="ims-ticket-card ims-card-gangguan"
           style="background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 52%, #3b82f6 100%); box-shadow: 0 6px 18px -3px rgba(37, 99, 235, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['gangguan'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['gangguan'] }} tiket gangguan aktif">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Gangguan Layanan
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['gangguan'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 2. Ubah Password (Vibrant Rose / Magenta Gradient) -->
        <a href="{{ $destinations['ubah_password'] ?? route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']) }}"
           class="ims-ticket-card ims-card-password"
           style="background: linear-gradient(135deg, #9d174d 0%, #be185d 52%, #db2777 100%); box-shadow: 0 6px 18px -3px rgba(219, 39, 119, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['ubah_password'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['ubah_password'] }} permintaan ubah sandi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Ubah Password
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['ubah_password'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 3. Relokasi Layanan (Royal Purple / Violet Gradient) -->
        <a href="{{ $destinations['relokasi'] ?? route('teknik.tiket.gangguan', ['kategori' => 'relokasi']) }}"
           class="ims-ticket-card ims-card-relokasi"
           style="background: linear-gradient(135deg, #5b21b6 0%, #6d28d9 52%, #8b5cf6 100%); box-shadow: 0 6px 18px -3px rgba(124, 58, 237, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['relokasi'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['relokasi'] }} tiket relokasi aktif">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Relokasi Layanan
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['relokasi'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 4. Cek Coverage Area (Warm Radiant Amber / Gold Gradient) -->
        <a href="{{ $destinations['coverage'] ?? route('teknik.coverage') }}"
           class="ims-ticket-card ims-card-coverage"
           style="background: linear-gradient(135deg, #b45309 0%, #d97706 52%, #f59e0b 100%); box-shadow: 0 6px 18px -3px rgba(217, 119, 6, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['coverage'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['coverage'] }} data coverage menunggu validasi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Cek Coverage Area
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['coverage'] ?? 0 }} Data
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 5. Terminasi (Crimson Scarlet / Red Gradient) -->
        <a href="{{ $destinations['terminasi'] ?? route('teknik.permintaan.terminasi') }}"
           class="ims-ticket-card ims-card-terminasi"
           style="background: linear-gradient(135deg, #991b1b 0%, #dc2626 52%, #ef4444 100%); box-shadow: 0 6px 18px -3px rgba(220, 38, 38, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['terminasi'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['terminasi'] }} permohonan terminasi aktif">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Terminasi
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['terminasi'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 6. Suspend Layanan (Vibrant Sunset Orange Gradient) -->
        <a href="{{ $destinations['suspend'] ?? route('teknik.permintaan.suspend') }}"
           class="ims-ticket-card ims-card-suspend"
           style="background: linear-gradient(135deg, #c2410c 0%, #ea580c 52%, #f97316 100%); box-shadow: 0 6px 18px -3px rgba(234, 88, 12, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['suspend'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['suspend'] }} permohonan suspend / buka isolir">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Suspend Layanan
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['suspend'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 7. Pemasangan Baru (Emerald Forest Green Gradient) -->
        <a href="{{ $destinations['pemasangan_baru'] ?? route('teknik.pendaftaran') }}"
           class="ims-ticket-card ims-card-pasang"
           style="background: linear-gradient(135deg, #047857 0%, #059669 52%, #10b981 100%); box-shadow: 0 6px 18px -3px rgba(5, 150, 105, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['pemasangan_baru'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['pemasangan_baru'] }} {{ (isset($user) && $user?->isNoc()) ? 'antrean aktivasi perlu diproses' : 'pendaftaran perlu diproses' }}">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        {{ (isset($user) && $user?->isNoc()) ? 'Aktivasi Pasang Baru' : 'Pemasangan Baru' }}
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['pemasangan_baru'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 8. Ubah Layanan (Deep Cyan / Oceanic Teal Gradient) -->
        <a href="{{ $destinations['ubah_layanan'] ?? route('teknik.permintaan.up-downgrade') }}"
           class="ims-ticket-card ims-card-ubah"
           style="background: linear-gradient(135deg, #0284c7 0%, #0891b2 52%, #06b6d4 100%); box-shadow: 0 6px 18px -3px rgba(8, 145, 178, 0.42);">
            <div class="ims-card-shine"></div>

            @if(($counts['ubah_layanan'] ?? 0) > 0)
                <div class="ims-alert-badge" title="{{ $counts['ubah_layanan'] }} permintaan ubah layanan perlu dieksekusi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-bold tracking-tight text-white m-0 drop-shadow-xs">
                        Ubah Layanan
                    </h3>
                    <p class="mt-0.5 text-xs font-semibold text-white/95">
                        {{ $counts['ubah_layanan'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                </div>
            </div>
        </a>

    </div>
</div>
@endsection
