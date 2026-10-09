@extends('layouts.app')

@section('page_title', 'Profil Saya')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition">Dashboard</a>
                <span>&rsaquo;</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Profil Pengguna</span>
            </div>
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <span>Profil Pengguna & Keamanan</span>
            </h2>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-xs transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

        <!-- Left Column: User Card & Details (5 Cols) -->
        <div class="md:col-span-5 space-y-6">
            
            <!-- User Profile Summary Card -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col items-center text-center">
                <!-- Avatar -->
                <div class="relative mb-4">
                    @if($user->foto_url)
                        <div class="w-24 h-24 rounded-2xl overflow-hidden ring-4 ring-slate-100 dark:ring-slate-800 shadow-md bg-slate-100 dark:bg-slate-800">
                            <img src="{{ $user->foto_url }}" alt="{{ $user->nama }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full hidden items-center justify-center font-bold text-3xl text-white bg-gradient-to-tr from-[#05404f] to-[#0891b2]">
                                {{ strtoupper(substr($user->nama ?? 'U', 0, 1)) }}
                            </div>
                        </div>
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-gradient-to-tr from-[#05404f] to-[#0891b2] flex items-center justify-center font-bold text-3xl text-white shadow-md">
                            {{ strtoupper(substr($user->nama ?? 'U', 0, 1)) }}
                        </div>
                    @endif

                    <div class="absolute -bottom-2 inset-x-0 flex justify-center">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500 text-white shadow-xs">
                            Aktif
                        </span>
                    </div>
                </div>

                <!-- Info -->
                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1">{{ $user->nama }}</h3>
                <p class="text-xs text-blue-600 dark:text-cyan-400 font-semibold mt-0.5 uppercase tracking-wide">{{ $user->nama_level }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-1">{{ $user->username }}</p>

                <!-- Status Badge -->
                <div class="w-full mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 text-left space-y-2.5 text-xs">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Kode Pengguna</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $user->kode_pengguna }}</span>
                    </div>
                    @if($user->kode_karyawan)
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Kode Karyawan</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $user->kode_karyawan }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Status Login</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Online
                        </span>
                    </div>
                    @if($user->las_login)
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Terakhir Login</span>
                        <span class="font-mono text-[11px] text-slate-700 dark:text-slate-300">{{ $user->las_login }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Security Notice -->
            <div class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 text-slate-600 dark:text-slate-400 text-xs flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <div class="space-y-1">
                    <p class="font-bold text-slate-800 dark:text-slate-200">Informasi Keamanan Akun</p>
                    <p class="text-[11px] leading-relaxed">
                        Data identitas dan foto profil terhubung langsung dengan sistem kepegawaian internal PT MSN. Anda dapat memperbarui kata sandi akun secara mandiri melalui form di sebelah kanan.
                    </p>
                </div>
            </div>

        </div>

        <!-- Right Column: Form Ubah Password (7 Cols) -->
        <div class="md:col-span-7">
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
                
                <div class="border-b border-slate-100 dark:border-slate-800 pb-4 mb-6">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span>Ubah Kata Sandi</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pastikan menggunakan kombinasi kata sandi yang aman dan tidak dibagikan kepada orang lain.
                    </p>
                </div>

                <form action="{{ route('profile.password') }}" method="POST" class="space-y-4" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                    @csrf

                    <!-- 1. Kata Sandi Saat Ini -->
                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'"
                                   id="current_password"
                                   name="current_password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="Masukkan kata sandi lama Anda"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-white transition pr-10 @error('current_password') border-rose-500 ring-1 ring-rose-500 @enderror">
                            <button type="button"
                                    @click="showCurrent = !showCurrent"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none">
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
                            <input :type="showNew ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   required
                                   minlength="6"
                                   autocomplete="new-password"
                                   placeholder="Minimal 6 karakter"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-white transition pr-10 @error('password') border-rose-500 ring-1 ring-rose-500 @enderror">
                            <button type="button"
                                    @click="showNew = !showNew"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none">
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
                            Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   minlength="6"
                                   autocomplete="new-password"
                                   placeholder="Ketik ulang kata sandi baru Anda"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-white transition pr-10">
                            <button type="button"
                                    @click="showConfirm = !showConfirm"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button type="submit"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-blue-500/20 hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
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
@endsection
