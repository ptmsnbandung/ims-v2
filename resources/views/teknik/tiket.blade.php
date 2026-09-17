@extends('layouts.app', ['title' => 'Tiket - IMS Router'])

@section('page_title', 'Tiket')

@section('content')
<style>
    @keyframes imsPingAnimation {
        0% {
            transform: scale(1);
            opacity: 0.9;
        }
        70% {
            transform: scale(2.3);
            opacity: 0;
        }
        100% {
            transform: scale(2.3);
            opacity: 0;
        }
    }

    .ims-card-wrapper {
        position: relative;
        display: block;
        border-radius: 1rem;
        padding: 1.35rem 1.5rem;
        text-decoration: none;
        color: #ffffff !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        overflow: hidden;
        cursor: pointer;
    }

    .ims-card-wrapper:hover {
        transform: translateY(-4px) scale(1.015);
        color: #ffffff !important;
    }

    /* Notification Alert Red Dot */
    .ims-alert-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 15px;
        height: 15px;
        z-index: 30;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ims-alert-ping {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 9999px;
        background-color: #ff3838;
        animation: imsPingAnimation 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
    }

    .ims-alert-core {
        position: relative;
        width: 13px;
        height: 13px;
        border-radius: 9999px;
        background-color: #ef4444;
        border: 2px solid #ffffff;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.9), 0 2px 4px rgba(0,0,0,0.3);
    }

    /* Button Pill */
    .ims-pill-button {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #ffffff;
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        margin-top: 0.85rem;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .ims-card-wrapper:hover .ims-pill-button {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .ims-icon-bubble {
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease;
    }

    .ims-card-wrapper:hover .ims-icon-bubble {
        transform: scale(1.12) rotate(6deg);
    }
</style>

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
        
        <!-- 1. Gangguan Layanan (Royal Blue) -->
        <a href="{{ $destinations['gangguan'] ?? route('teknik.tiket.gangguan', ['kategori' => 'gangguan']) }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #3b82f6 100%); box-shadow: 0 10px 25px -4px rgba(37, 99, 235, 0.45);">
            
            @if(($counts['gangguan'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['gangguan'] }} tiket perlu dieksekusi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Gangguan Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['gangguan'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- Users / Headset Group Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 2. Ubah Password (Rose Magenta Pink) -->
        <a href="{{ $destinations['ubah_password'] ?? route('teknik.tiket.gangguan', ['kategori' => 'ubah_password']) }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #be185d 0%, #db2777 50%, #ec4899 100%); box-shadow: 0 10px 25px -4px rgba(219, 39, 119, 0.45);">
            
            @if(($counts['ubah_password'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['ubah_password'] }} tiket perlu dieksekusi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Ubah Password
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['ubah_password'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- Key / Password Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 3. Relokasi Layanan (Deep Violet / Royal Purple) -->
        <a href="{{ $destinations['relokasi'] ?? route('teknik.tiket.gangguan', ['kategori' => 'relokasi']) }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 50%, #8b5cf6 100%); box-shadow: 0 10px 25px -4px rgba(124, 58, 237, 0.45);">
            
            @if(($counts['relokasi'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['relokasi'] }} tiket perlu dieksekusi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Relokasi Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['relokasi'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- Map Pin / Move Location Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 4. Cek Coverage Area (Vibrant Amber / Gold) -->
        <a href="{{ $destinations['coverage'] ?? route('teknik.coverage') }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #fbbf24 100%); box-shadow: 0 10px 25px -4px rgba(245, 158, 11, 0.45);">
            
            @if(($counts['coverage'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['coverage'] }} data coverage perlu diproses">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Cek Coverage Area
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['coverage'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- Target Map Marker Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 5. Terminasi (Crimson Blood Ruby Red) -->
        <a href="{{ $destinations['terminasi'] ?? route('teknik.permintaan.terminasi') }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #991b1b 0%, #dc2626 50%, #ef4444 100%); box-shadow: 0 10px 25px -4px rgba(220, 38, 38, 0.45);">
            
            @if(($counts['terminasi'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['terminasi'] }} tiket terminasi perlu dieksekusi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Terminasi
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['terminasi'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- User Terminate Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 6. Suspend Layanan (Flame Sunset Orange) -->
        <a href="{{ $destinations['suspend'] ?? route('teknik.permintaan.suspend') }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #c2410c 0%, #ea580c 50%, #f97316 100%); box-shadow: 0 10px 25px -4px rgba(234, 88, 12, 0.45);">
            
            @if(($counts['suspend'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['suspend'] }} tiket suspend perlu diproses">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Suspend Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['suspend'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- Pause / Lock Clock Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 7. Pemasangan Baru (Emerald Forest Green) -->
        <a href="{{ $destinations['pemasangan_baru'] ?? route('teknik.pendaftaran') }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%); box-shadow: 0 10px 25px -4px rgba(5, 150, 105, 0.45);">
            
            @if(($counts['pemasangan_baru'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['pemasangan_baru'] }} pendaftaran perlu diproses">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Pemasangan Baru
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['pemasangan_baru'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- User Plus / Installation Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 8. Ubah Layanan (Deep Cyan / Teal Oceanic) -->
        <a href="{{ $destinations['ubah_layanan'] ?? route('teknik.permintaan.up-downgrade') }}"
           class="ims-card-wrapper"
           style="background: linear-gradient(135deg, #0284c7 0%, #0891b2 50%, #0d9488 100%); box-shadow: 0 10px 25px -4px rgba(8, 145, 178, 0.45);">
            
            @if(($counts['ubah_layanan'] ?? 0) > 0)
                <!-- Red Alert Dot (Top Right) -->
                <div class="ims-alert-badge" title="{{ $counts['ubah_layanan'] }} permintaan ubah layanan perlu dieksekusi">
                    <span class="ims-alert-ping"></span>
                    <span class="ims-alert-core"></span>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white m-0">
                        Ubah Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/95">
                        {{ $counts['ubah_layanan'] ?? 0 }} Tiket
                    </p>
                    <span class="ims-pill-button">
                        <span>Buka Dashboard</span>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </div>
                <div class="ims-icon-bubble text-white">
                    <!-- Flash / Speedometer Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
            </div>
        </a>

    </div>
</div>
@endsection
