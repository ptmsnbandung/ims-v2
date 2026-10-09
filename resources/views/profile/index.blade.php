@extends('layouts.app')

@section('page_title', 'Profil Saya')

@section('content')
<div class="w-full max-w-5xl mx-auto space-y-6" style="width: 100%; max-width: 1060px; margin-left: auto; margin-right: auto;">

    <!-- Top Breadcrumb & Action -->
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition font-medium flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span>Dashboard</span>
            </a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 dark:text-slate-200 font-bold">Profil Pengguna</span>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-xs transition cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="flex-1 font-medium">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-xl bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="flex-1 font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Hero Profile Executive Card -->
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <!-- Top Oceanic Gradient Banner (No overlapping elements on bottom-left) -->
        <div class="h-28 sm:h-32 relative overflow-hidden bg-gradient-to-r from-[#032838] via-[#05445e] to-[#087f9c] flex items-start justify-end p-4 sm:p-5">
            <!-- Background Decorative Circles -->
            <div class="absolute -right-8 -top-8 w-48 h-48 rounded-full bg-cyan-400/15 blur-2xl pointer-events-none"></div>
            <div class="absolute left-1/4 -bottom-10 w-40 h-40 rounded-full bg-blue-500/15 blur-xl pointer-events-none"></div>
            
            <div class="relative z-10 flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-bold tracking-wide uppercase bg-black/30 backdrop-blur-md text-cyan-200 border border-white/15 shadow-xs flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    PT Media Solusi Network &middot; IMS v2
                </span>
            </div>
        </div>

        <!-- User Information Row with Cleanly Placed Avatar -->
        <div class="px-6 pb-6 pt-0 relative">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 -mt-12 sm:-mt-14 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800/80">
                
                <!-- Avatar & Identity Info -->
                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
                    <!-- Photo Frame -->
                    <div class="relative flex-shrink-0">
                        @if($user->foto_url)
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-900 shadow-xl bg-slate-100 dark:bg-slate-800" style="width: 104px; height: 104px;">
                                <img src="{{ $user->foto_url }}" alt="{{ $user->nama }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-full h-full hidden items-center justify-center font-bold text-3xl text-white bg-gradient-to-tr from-[#05404f] to-[#0891b2]">
                                    {{ strtoupper(substr($user->nama ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                        @else
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gradient-to-tr from-[#05404f] to-[#0891b2] flex items-center justify-center font-bold text-3xl text-white shadow-xl ring-4 ring-white dark:ring-slate-900" style="width: 104px; height: 104px;">
                                {{ strtoupper(substr($user->nama ?? 'U', 0, 1)) }}
                            </div>
                        @endif

                        <!-- Online Status Indicator -->
                        <div class="absolute bottom-1 right-1 p-0.5 bg-white dark:bg-slate-900 rounded-full shadow-sm" title="Status: Online">
                            <span class="block w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- Name & Role Tags -->
                    <div class="space-y-1.5 pb-1">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-snug">{{ $user->nama }}</h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Akun Terverifikasi
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs">
                            <span class="px-2.5 py-0.5 rounded-md font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800/60">
                                {{ $user->nama_level }}
                            </span>
                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                            <span class="font-mono text-slate-600 dark:text-slate-400 font-medium">{{ $user->username }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Meta Badges -->
                <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2 text-xs font-mono pb-1">
                    <div class="px-3.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-center">
                        <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">ID User</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $user->kode_pengguna }}</span>
                    </div>
                    @if($user->kode_karyawan)
                    <div class="px-3.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-center">
                        <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Kode Karyawan</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $user->kode_karyawan }}</span>
                    </div>
                    @endif
                </div>

            </div>

            <!-- Two Columns: Account Details & Change Password Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start" style="display: grid; gap: 1.5rem;">

                <!-- Column 1: Account Specifications (5 Cols) -->
                <div class="lg:col-span-5 space-y-4" style="grid-column: span 5 / span 5;">
                    
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200/70 dark:border-slate-700/60">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                                </svg>
                                <span>Informasi Kepegawaian</span>
                            </h3>
                            <span class="text-[10px] font-bold text-slate-400 font-mono">Sistem IMS</span>
                        </div>

                        <!-- Info Rows -->
                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Nama Lengkap</span>
                                <span class="font-bold text-slate-900 dark:text-white text-right">{{ $user->nama }}</span>
                            </div>

                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Jabatan / Role</span>
                                <span class="font-bold text-blue-600 dark:text-cyan-400 text-right">{{ $user->nama_level }}</span>
                            </div>

                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Username / Email</span>
                                <span class="font-mono font-semibold text-slate-900 dark:text-white text-right">{{ $user->username }}</span>
                            </div>

                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Status Keaktifan</span>
                                <span class="inline-flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            </div>

                            @if($user->las_login)
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Sesi Terakhir</span>
                                <span class="font-mono text-[11px] text-slate-700 dark:text-slate-300 text-right">{{ $user->las_login }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Policy Info Badge -->
                    <div class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 text-slate-600 dark:text-slate-400 text-xs flex items-start gap-3">
                        <div class="w-6 h-6 rounded-lg bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </div>
                        <div class="space-y-0.5">
                            <p class="font-bold text-slate-800 dark:text-slate-200">Pengelolaan Data Profil</p>
                            <p class="text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
                                Foto profil & identitas resmi disinkronkan langsung oleh Administrator & HRD. Pengguna dapat memperbarui kata sandi secara mandiri.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Column 2: Form Ganti Password (7 Cols) -->
                <div class="lg:col-span-7" style="grid-column: span 7 / span 7;">
                    <div class="p-6 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800">
                        
                        <div class="border-b border-slate-200/70 dark:border-slate-700/60 pb-4 mb-5">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="block">Ubah Kata Sandi Akun</span>
                                    <span class="block text-[11px] font-normal text-slate-500 dark:text-slate-400">Perbarui kata sandi secara berkala demi menjaga keamanan akun IMS Anda.</span>
                                </div>
                            </h3>
                        </div>

                        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                            @csrf

                            <!-- 1. Kata Sandi Saat Ini -->
                            <div>
                                <label for="current_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                        </svg>
                                    </div>
                                    <input :type="showCurrent ? 'text' : 'password'"
                                           id="current_password"
                                           name="current_password"
                                           required
                                           autocomplete="current-password"
                                           placeholder="Ketik kata sandi Anda saat ini"
                                           class="w-full pl-10 pr-10 py-2.5 rounded-xl text-xs sm:text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-white transition @error('current_password') border-rose-500 ring-1 ring-rose-500 @enderror">
                                    <button type="button"
                                            @click="showCurrent = !showCurrent"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer">
                                        <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showCurrent" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 2. Kata Sandi Baru -->
                            <div>
                                <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Kata Sandi Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                    </div>
                                    <input :type="showNew ? 'text' : 'password'"
                                           id="password"
                                           name="password"
                                           required
                                           minlength="6"
                                           autocomplete="new-password"
                                           placeholder="Minimal 6 karakter kombinasi"
                                           class="w-full pl-10 pr-10 py-2.5 rounded-xl text-xs sm:text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-white transition @error('password') border-rose-500 ring-1 ring-rose-500 @enderror">
                                    <button type="button"
                                            @click="showNew = !showNew"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer">
                                        <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 3. Konfirmasi Kata Sandi Baru -->
                            <div>
                                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <input :type="showConfirm ? 'text' : 'password'"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           required
                                           minlength="6"
                                           autocomplete="new-password"
                                           placeholder="Ketik ulang kata sandi baru Anda"
                                           class="w-full pl-10 pr-10 py-2.5 rounded-xl text-xs sm:text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-white transition">
                                    <button type="button"
                                            @click="showConfirm = !showConfirm"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer">
                                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button type="submit"
                                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#05445e] to-[#087f9c] hover:from-[#043549] hover:to-[#076b84] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#05445e]/20 hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span>Simpan Kata Sandi Baru</span>
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection
