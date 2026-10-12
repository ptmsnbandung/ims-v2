@extends('layouts.guest', ['title' => 'Masuk ke Sistem'])

@section('content')
<div x-data="{
    username: '{{ old('username', '') }}',
    password: '',
    showPassword: false,
    isSubmitting: false
}">
    <!-- Header Form -->
    <div class="mb-6 sm:mb-7">
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Masuk ke Akun Pengguna</h2>
        <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">Silakan masukkan akun Anda untuk mengakses portal sistem</p>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="mb-4.5 p-3 sm:p-3.5 rounded-2xl bg-emerald-50/90 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <svg class="w-4.5 h-4.5 flex-shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4.5 p-3 sm:p-3.5 rounded-2xl bg-rose-50/90 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <svg class="w-4.5 h-4.5 flex-shrink-0 text-rose-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="mb-4.5 p-3 sm:p-3.5 rounded-2xl bg-sky-50/90 border border-sky-200 text-sky-800 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
            <svg class="w-4.5 h-4.5 flex-shrink-0 text-sky-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">{{ session('info') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4.5 p-3.5 sm:p-4 rounded-2xl bg-rose-50/90 border border-rose-200 text-rose-800 text-xs sm:text-sm shadow-xs">
            <div class="font-bold mb-1.5 flex items-center gap-2 text-rose-900">
                <svg class="w-4.5 h-4.5 text-rose-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span>Gagal Masuk:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-rose-700 ml-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Login Form -->
    <form action="{{ route('login.submit') }}" method="POST" class="space-y-4 sm:space-y-5" @submit="isSubmitting = true">
        @csrf

        <!-- Username / Email Field -->
        <div>
            <label for="username" class="block text-xs sm:text-[13px] font-semibold text-slate-700 mb-1.5 sm:mb-2">
                Username / Email Pengguna
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 sm:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-cyan-600 transition-colors">
                    <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <input id="username"
                       type="text"
                       name="username"
                       x-model="username"
                       required
                       autofocus
                       placeholder="Masukkan username atau email..."
                       class="w-full pl-11 sm:pl-12 pr-4 py-3 sm:py-3.5 bg-slate-50/80 border border-slate-200/90 rounded-2xl text-slate-900 placeholder:text-slate-400 hover:border-slate-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-cyan-500/15 focus:border-cyan-500 text-xs sm:text-sm font-medium transition-all duration-200 shadow-2xs" />
            </div>
        </div>

        <!-- Password Field -->
        <div>
            <label for="password" class="block text-xs sm:text-[13px] font-semibold text-slate-700 mb-1.5 sm:mb-2">
                Kata Sandi
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 sm:pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-cyan-600 transition-colors">
                    <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <input id="password"
                       :type="showPassword ? 'text' : 'password'"
                       name="password"
                       x-model="password"
                       required
                       placeholder="••••••••"
                       class="w-full pl-11 sm:pl-12 pr-12 py-3 sm:py-3.5 bg-slate-50/80 border border-slate-200/90 rounded-2xl text-slate-900 placeholder:text-slate-400 hover:border-slate-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-cyan-500/15 focus:border-cyan-500 text-xs sm:text-sm font-medium transition-all duration-200 shadow-2xs tracking-wide" />

                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 sm:pr-3.5 flex items-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 p-1.5 rounded-xl transition cursor-pointer mr-1"
                        tabindex="-1"
                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
                    <svg x-show="!showPassword" class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-0.5">
            <label class="flex items-center gap-2.5 cursor-pointer group select-none">
                <input type="checkbox"
                       name="remember"
                       value="1"
                       class="w-4 h-4 rounded-md bg-white border-slate-300 text-cyan-600 focus:ring-cyan-500/20 focus:ring-offset-0 cursor-pointer transition">
                <span class="text-xs sm:text-[13px] font-medium text-slate-600 group-hover:text-slate-800 transition">Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit"
                    :disabled="isSubmitting"
                    class="group relative w-full py-3.5 sm:py-4 px-6 rounded-2xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-cyan-600 via-teal-600 to-sky-600 hover:from-cyan-500 hover:via-teal-500 hover:to-sky-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/25 shadow-lg shadow-cyan-950/20 hover:shadow-cyan-950/35 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99] disabled:opacity-80 disabled:cursor-wait">
                <template x-if="!isSubmitting">
                    <span class="inline-flex items-center gap-2">
                        <span>Masuk Sekarang</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1.5 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </template>
                <template x-if="isSubmitting">
                    <span class="inline-flex items-center gap-2" x-cloak>
                        <svg class="animate-spin -ml-0.5 mr-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memverifikasi akun &amp; memuat...</span>
                    </span>
                </template>
            </button>
        </div>

        <!-- Security / System Note -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-slate-400 text-[11px] font-medium">
            <svg class="w-3.5 h-3.5 text-teal-500/90 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
            </svg>
            <span>Koneksi aman terenkripsi &bull; Portal Resmi IMS-V2</span>
        </div>
    </form>
</div>
@endsection
