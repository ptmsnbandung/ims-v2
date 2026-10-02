@extends('layouts.app', ['title' => 'Dashboard Utama'])

@section('page_title', 'Dashboard Utama')

@section('content')
<div class="space-y-6">
    <!-- Role Welcome Banner (Deep Oceanic Teal & Cyan Gradient Matching Reference) -->
    <div class="ims-banner relative overflow-hidden rounded-2xl p-6 sm:p-7 shadow-md"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <!-- Left Info Area -->
            <div>
                <!-- Top Mini Badges -->
                <div class="flex flex-wrap items-center gap-2.5 mb-3">
                    <!-- Pill 1: White Pill -->
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[11px] font-bold bg-white text-slate-800 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-[#00a8b5]"></span>
                        <span>ID: &middot; {{ $user->nama_level }}</span>
                    </div>
                    <!-- Pill 2: Dark Teal Pill -->
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[11px] font-semibold bg-[#04333e]/85 text-emerald-300 border border-teal-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Sistem Aktif (Online)</span>
                    </div>
                </div>

                <!-- Main Greeting -->
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2" style="color: #FFFFFF !important;">
                    <span>Halo, {{ $user->nama }}!</span>
                    <span class="text-2xl">👋</span>
                </h2>
                <p class="text-xs sm:text-sm text-[#c6edf3] mt-1 max-w-2xl leading-relaxed" style="color: #C6EDF3 !important;">
                    {{ $user->role_description }} &middot; Pantau performa jaringan, pertumbuhan pelanggan baru, dan kelola operasional secara real-time.
                </p>
            </div>

            <!-- Right Action Buttons / Badges -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Button 1: Outlined Clock Server Pill -->
                <div class="px-4 py-2 rounded-full border border-white/50 bg-white/5 backdrop-blur-xs text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition" style="color: #FFFFFF !important;">
                    <svg class="w-4 h-4 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span style="color: #FFFFFF !important;">{{ now()->format('d M Y, H:i') }} WIB</span>
                </div>

                <!-- Button 2: Solid Emerald Status Layanan Pill -->
                <a href="{{ route('teknik.tiket') }}" 
                   class="px-4 py-2 rounded-full bg-[#00b074] hover:bg-[#009b66] text-white text-xs font-bold flex items-center gap-2 shadow-md shadow-emerald-950/20 transition cursor-pointer" style="color: #FFFFFF !important;">
                    <svg class="w-4 h-4 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span style="color: #FFFFFF !important;">Status Layanan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Role-Specific Feature Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @if($user->isTeknik() || $user->isDirektur())
            <!-- Card 1: Pendaftaran Pelanggan Baru -->
            <a href="{{ route('teknik.pendaftaran') }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 dark:hover:border-cyan-500 shadow-xs hover:shadow-md transition block group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-cyan-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">Drafter</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition">Draft Registrasi Pelanggan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Input data calon pelanggan baru, lokasi ODP/koordinat, dan paket internet.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-blue-600 dark:text-cyan-400 font-semibold">
                    <span>Menu Pendaftaran</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 2: Calon Pelanggan Terdaftar -->
            <a href="{{ route('teknik.pelanggan') }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 dark:hover:border-cyan-500 shadow-xs hover:shadow-md transition block group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-cyan-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-blue-700 dark:text-cyan-300 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/60 px-2 py-0.5 rounded">Pelanggan</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition">Data Pelanggan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Lihat status pelanggan aktif, suspend, terminasi, dan rincian profil layanan.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-blue-600 dark:text-cyan-400 font-semibold">
                    <span>Buka Data Pelanggan</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 3: Tiket & Permintaan -->
            <a href="{{ route('teknik.tiket') }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 dark:hover:border-cyan-500 shadow-xs hover:shadow-md transition block group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 px-2 py-0.5 rounded">Tiket</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition">Tiket & Gangguan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Monitoring penanganan tiket gangguan jaringan dan permintaan teknis pelanggan.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-amber-600 dark:text-amber-400 font-semibold">
                    <span>Buka Tiket Gangguan</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>
        @endif

        @if($user->isNoc() || $user->isDirektur())
            <!-- Card 1: Router & Perangkat Jaringan -->
            <a href="{{ route('noc.olt') }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-500 shadow-xs hover:shadow-md transition block group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a1.5 1.5 0 0 1 1.2-.6h10.1a1.5 1.5 0 0 1 1.2.6l2.1 3.45a4.5 4.5 0 0 1 .9 2.7" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">Core Network</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">Manajemen Router & OLT</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Monitoring status IP, ping router, traffic load, dan koneksi API RouterOS.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-indigo-600 dark:text-indigo-400 font-semibold">
                    <span>Menu NOC</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 2: Antrean Aktivasi -->
            <a href="{{ route('noc.aktivasi') }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-500 shadow-xs hover:shadow-md transition block group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">Eksekusi</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">Antrean Aktivasi Pelanggan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Eksekusi pembuatan PPPoE Secret, assign IP statis, dan binding profile.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-indigo-600 dark:text-indigo-400 font-semibold">
                    <span>Menu Aktivasi</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 3: Suspend & Terminasi -->
            <a href="{{ route('noc.suspend') }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-amber-400 dark:hover:border-amber-500 shadow-xs hover:shadow-md transition block group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">Suspend / Isolir</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">Eksekusi Suspend & Terminasi</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Daftar permintaan isolir dari Finance untuk pelanggan yang jatuh tempo.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-amber-600 dark:text-amber-400 font-semibold">
                    <span>Menu Isolir</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>
        @endif

        @if($user->isFinance() || $user->isDirektur())
            <!-- Card 1: Billing Layanan Bulanan -->
            <a href="{{ route('finance.billing-layanan') }}"
               class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-500 dark:hover:border-cyan-500 shadow-xs hover:shadow-md transition group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-cyan-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-blue-700 dark:text-cyan-300 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/60 px-2 py-0.5 rounded-full">Bulanan</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition">Billing Layanan Bulanan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Generate tagihan bulanan, monitoring status pembayaran, dan penyesuaian invoice.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-blue-600 dark:text-cyan-400 font-semibold">
                    <span>Buka Billing Layanan</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 2: Billing Registrasi Baru -->
            <a href="{{ route('finance.billing-registrasi') }}"
               class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-amber-500 dark:hover:border-amber-400 shadow-xs hover:shadow-md transition group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 px-2 py-0.5 rounded-full">Pasang Baru</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">Billing Registrasi Baru</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola tagihan biaya pasang baru, penerbitan link bayar, dan verifikasi pelunasan.</p>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-amber-600 dark:text-amber-400 font-semibold">
                    <span>Buka Billing Registrasi</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 3: Realisasi Kas Masuk -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-emerald-400 shadow-xs hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 px-2 py-0.5 rounded-full">Kas Masuk</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Realisasi Pendapatan ({{ $newUserStats['selectedBulanNama'] }} {{ $newUserStats['selectedTahun'] }})</h3>
                <div class="mt-2">
                    <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        Rp {{ number_format($financeStats['paidAmount'] ?? 0, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ number_format($financeStats['paidCount'] ?? 0) }} invoice telah terverifikasi lunas.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION: STATISTIK USER & PELANGGAN BARU (DENGAN FILTER BULAN & TAHUN)   -->
    <!-- ========================================================================= -->
    <div class="space-y-5">
        
        <!-- Header & Filter Bar Section -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-cyan-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">
                            Statistik User & Pelanggan Baru
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Periode Aktif: <span class="font-bold text-blue-600 dark:text-cyan-400">{{ $newUserStats['selectedBulanNama'] }} {{ $newUserStats['selectedTahun'] }}</span> &middot; Total {{ number_format($newUserStats['totalBaru']) }} pendaftaran baru tercatat.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter Month & Year Form -->
            <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2.5">
                <!-- Dropdown Bulan -->
                <div class="relative">
                    <select name="bulan" 
                            class="appearance-none pl-3.5 pr-8 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs cursor-pointer">
                        @foreach($monthsList as $num => $namaBulan)
                            <option value="{{ $num }}" {{ $selectedBulan == $num ? 'selected' : '' }}>
                                {{ $namaBulan }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Dropdown Tahun -->
                <div class="relative">
                    <select name="tahun" 
                            class="appearance-none pl-3.5 pr-8 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs cursor-pointer">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedTahun == (string)$year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white text-xs font-bold shadow-md shadow-blue-500/20 flex items-center gap-1.5 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                    </svg>
                    <span>Filter</span>
                </button>

                <!-- Reset to Current Month Button -->
                @if($selectedBulan != date('m') || $selectedTahun != date('Y'))
                    <a href="{{ route('dashboard') }}" 
                       class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition"
                       title="Kembali ke Bulan & Tahun Saat Ini">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- 4 KPI Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            
            <!-- 1. Total Pelanggan Baru Terdaftar -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-blue-400 dark:hover:border-cyan-500 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">User / Pelanggan Baru</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white mt-2 font-mono">
                    {{ number_format($newUserStats['totalBaru']) }}
                    <span class="text-xs font-normal text-slate-400">User</span>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    @if($newUserStats['growthCount'] >= 0)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            +{{ $newUserStats['growthCount'] }} ({{ $newUserStats['growthPercent'] }}%)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            {{ $newUserStats['growthCount'] }} ({{ $newUserStats['growthPercent'] }}%)
                        </span>
                    @endif
                    <span class="text-[11px] text-slate-400">vs bln lalu</span>
                </div>
            </div>

            <!-- 2. Pelanggan Baru Aktif (Online / Live) -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-emerald-500 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aktivasi Selesai (Aktif)</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2 font-mono">
                    {{ number_format($newUserStats['aktifBaru']) }}
                    <span class="text-xs font-normal text-slate-400">Aktif (#20)</span>
                </div>
                <div class="mt-3 flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>
                        {{ $newUserStats['totalBaru'] > 0 ? round(($newUserStats['aktifBaru'] / $newUserStats['totalBaru']) * 100, 1) : 0 }}% tingkat konversi aktif
                    </span>
                </div>
            </div>

            <!-- 3. Dalam Proses / Antrean Pemasangan -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-amber-400 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Proses & Antrean</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-amber-500 mt-2 font-mono">
                    {{ number_format($newUserStats['prosesBaru']) }}
                    <span class="text-xs font-normal text-slate-400">On-going</span>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span>Survey: <b>{{ $newUserStats['statusBreakdown']['12'] }}</b></span>
                    <span>&middot;</span>
                    <span>Instalasi: <b>{{ $newUserStats['statusBreakdown']['16'] }}</b></span>
                    <span>&middot;</span>
                    <span>Aktivasi: <b>{{ $newUserStats['statusBreakdown']['18_19'] }}</b></span>
                </div>
            </div>

            <!-- 4. Total Pelanggan Aktif Sistem -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-cyan-500 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pelanggan Aktif</span>
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white mt-2 font-mono">
                    {{ number_format($newUserStats['totalSemuaPelangganAktif']) }}
                    <span class="text-xs font-normal text-slate-400">Subscriber</span>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Batal pasang bln ini: <b>{{ $newUserStats['batalBaru'] }}</b></span>
                    @if($newUserStats['totalPenggunaSistemBaru'] > 0)
                        <span class="text-blue-500 font-semibold">+{{ $newUserStats['totalPenggunaSistemBaru'] }} Akun Tim</span>
                    @endif
                </div>
            </div>

        </div>

        <!-- 2-Column Analytics Details: Recent New Customers & Package Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left Column (7 cols): Daftar Pendaftaran Pelanggan Baru Terbaru -->
            <div class="lg:col-span-7 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                        <span>Pendaftaran Pelanggan Baru ({{ $newUserStats['selectedBulanNama'] }} {{ $newUserStats['selectedTahun'] }})</span>
                    </h3>
                    <a href="{{ route('teknik.pendaftaran') }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        Buka Semua &rarr;
                    </a>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                    <th class="py-3 px-4">No. Internet / Nama</th>
                                    <th class="py-3 px-4">Paket Bandwidth</th>
                                    <th class="py-3 px-4">Tanggal Daftar</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                                @forelse($newUserStats['recentNewUsers'] as $nu)
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                        <td class="py-3 px-4">
                                            <a href="{{ route('teknik.pelanggan.profile', $nu->nomor_internet ?? '') }}" 
                                               class="font-bold text-blue-600 dark:text-cyan-400 font-mono hover:underline">
                                                {{ $nu->nomor_internet ?? '-' }}
                                            </a>
                                            <div class="font-semibold text-slate-800 dark:text-slate-200 uppercase mt-0.5">{{ $nu->nama_pelanggan ?? 'Pelanggan' }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-slate-700 dark:text-slate-300">
                                                {{ $nu->nama_kategori_bandwith ?? ($nu->alias_nama_kategori ?? ($nu->nama_paket ?? 'INTERNET')) }}
                                            </div>
                                            <div class="text-[11px] font-bold font-mono text-blue-600 dark:text-cyan-400">
                                                {{ $nu->nominal_bandwith ?? '10' }} Mbps
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                            {{ !empty($nu->date_create) ? \Carbon\Carbon::parse($nu->date_create)->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            @if(($nu->status_reg ?? null) == '20')
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30">
                                                    AKTIF (#20)
                                                </span>
                                            @elseif(in_array(($nu->status_reg ?? null), ['18', '18.1', '19', '19.1']))
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/15 dark:text-cyan-400 dark:border-cyan-500/30">
                                                    AKTIVASI (#{{ $nu->status_reg ?? '' }})
                                                </span>
                                            @elseif(in_array(($nu->status_reg ?? null), ['16', '17', '17.1']))
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-400 dark:border-indigo-500/30">
                                                    INSTALASI (#{{ $nu->status_reg ?? '' }})
                                                </span>
                                            @elseif(in_array(($nu->status_reg ?? null), ['12', '13', '13.1']))
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30">
                                                    SURVEY (#{{ $nu->status_reg ?? '' }})
                                                </span>
                                            @elseif(in_array(($nu->status_reg ?? null), ['14', '15']))
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30">
                                                    BATAL (#{{ $nu->status_reg ?? '' }})
                                                </span>
                                            @else
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                                    DRAFT (#{{ $nu->status_reg ?? '11' }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="{{ route('teknik.pelanggan.profile', $nu->nomor_internet ?? '') }}" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-semibold transition"
                                               title="Lihat Profil Pelanggan">
                                                <span>Profil</span>
                                                <span class="text-xs">&rarr;</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                            Tidak ada data pendaftaran pelanggan baru pada bulan {{ $newUserStats['selectedBulanNama'] }} {{ $newUserStats['selectedTahun'] }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column (5 cols): Breakdown Paket & Funnel Tahapan -->
            <div class="lg:col-span-5 space-y-4">
                
                <!-- Paket Terfavorit Pelanggan Baru -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Paket Populer ({{ $newUserStats['selectedBulanNama'] }} {{ $newUserStats['selectedTahun'] }})
                        </h4>
                        <span class="text-[11px] text-slate-400">Total User</span>
                    </div>

                    <div class="space-y-3">
                        @forelse($newUserStats['paketBreakdown'] as $pkg)
                            @php
                                $percent = $newUserStats['totalBaru'] > 0 ? round(($pkg->total / $newUserStats['totalBaru']) * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-800 dark:text-slate-200 mb-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>{{ $pkg->nama_paket }} ({{ $pkg->nominal_bandwith }} Mbps)</span>
                                    </span>
                                    <span class="font-mono font-bold text-blue-600 dark:text-cyan-400">{{ $pkg->total }} User <span class="text-[10px] text-slate-400 font-normal">({{ $percent }}%)</span></span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 transition-all duration-500"
                                         style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-4">Belum ada data paket untuk bulan ini.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Funnel / Progres Pendaftaran Baru -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                        Status Alur Pendaftaran Baru
                    </h4>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold">1. Draft Reg</span>
                            <div class="text-base font-bold text-slate-800 dark:text-slate-200 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['11'] }}
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/50">
                            <span class="text-[10px] text-amber-700 dark:text-amber-400 uppercase font-bold">2. Survey</span>
                            <div class="text-base font-bold text-amber-600 dark:text-amber-400 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['12'] }}
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/50">
                            <span class="text-[10px] text-indigo-700 dark:text-indigo-400 uppercase font-bold">3. Instalasi</span>
                            <div class="text-base font-bold text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">
                                {{ $newUserStats['statusBreakdown']['16'] }}
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-cyan-50/70 dark:bg-cyan-950/30 border border-cyan-200/80 dark:border-cyan-800/50">
                            <span class="text-[10px] text-cyan-700 dark:text-cyan-400 uppercase font-bold">4. Aktivasi NOC</span>
                            <div class="text-base font-bold text-cyan-600 dark:text-cyan-400 font-mono mt-0.5">
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
