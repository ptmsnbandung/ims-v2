@extends('layouts.guest', ['title' => 'Masuk ke Sistem'])

@section('content')
<div class="w-full max-w-[360px] sm:max-w-[380px] relative"
     x-data="{
        username: '{{ old('username', '') }}',
        password: '',
        showPassword: false,
        isSubmitting: false
     }">

    <!-- Frosted Glass Login Card (Matches Reference Image) -->
    <div class="relative rounded-[28px] p-6 sm:p-7 backdrop-blur-2xl border border-white/[0.18] transition-all duration-300 overflow-hidden"
         style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 100%);
                box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.8),
                            inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.3),
                            inset 0 -1px 1px 0 rgba(255, 255, 255, 0.06);">
        
        <!-- Subtle Top Specular Glass Reflection Highlight -->
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

        <!-- Brand Logo / Emblem (Glowing Ribbon 'A' / Infinity Emblem like Reference Image) -->
        <div class="flex flex-col items-center text-center mb-6 relative z-10">
            <div class="relative group cursor-pointer mb-2">
                <!-- Ambient Glow behind Logo -->
                <div class="absolute -inset-2 bg-gradient-to-r from-purple-500 to-cyan-400 rounded-2xl blur-lg opacity-60 group-hover:opacity-90 transition-opacity"></div>
                
                <!-- Ribbon 'A' / Infinity SVG Emblem -->
                <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-b from-white/10 to-white/5 border border-white/20 p-2.5 flex items-center justify-center backdrop-blur-md shadow-inner">
                    <svg class="w-full h-full drop-shadow-[0_4px_10px_rgba(139,92,246,0.6)]" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="logoGrad1" x1="15%" y1="10%" x2="85%" y2="90%">
                                <stop offset="0%" stop-color="#c084fc"/>
                                <stop offset="50%" stop-color="#818cf8"/>
                                <stop offset="100%" stop-color="#38bdf8"/>
                            </linearGradient>
                            <linearGradient id="logoGrad2" x1="100%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#38bdf8"/>
                                <stop offset="100%" stop-color="#a855f7"/>
                            </linearGradient>
                        </defs>
                        <!-- Stylized Ribbon 'A' Shape -->
                        <path d="M 50 18 C 55 18 64 28 72 44 C 79 58 83 72 77 78 C 72 83 58 76 50 67 C 42 76 28 83 23 78 C 17 72 21 58 28 44 C 36 28 45 18 50 18 Z" 
                              fill="url(#logoGrad1)" />
                        <!-- Inner Ribbon Fold Highlight -->
                        <path d="M 50 28 C 45 38 35 55 32 64 C 40 60 48 50 50 44 C 52 50 60 60 68 64 C 65 55 55 38 50 28 Z" 
                              fill="url(#logoGrad2)" opacity="0.85" />
                    </svg>
                </div>
            </div>

            <!-- Title & Branding -->
            <h1 class="text-sm sm:text-base font-bold text-white tracking-wide font-sans drop-shadow-sm">
                ahmadkhancodes
            </h1>
            <p class="text-[10px] font-medium text-slate-400 tracking-wider uppercase mt-0.5">
                IMS Core Network
            </p>
        </div>

        <!-- Alerts / Notification Messages (Compact Glass Pills) -->
        @if(session('success'))
            <div class="mb-3.5 p-2.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-[11px] flex items-center gap-2 backdrop-blur-md">
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-3.5 p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] flex items-center gap-2 backdrop-blur-md">
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-rose-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-3.5 p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] backdrop-blur-md">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('login.submit') }}" method="POST" class="space-y-3 sm:space-y-3.5" @submit="isSubmitting = true">
            @csrf

            <!-- Username / Email Input Container -->
            <div class="relative group">
                <div class="flex items-center rounded-2xl bg-white/[0.07] border border-white/[0.12] transition-all duration-200 group-focus-within:border-cyan-400/60 group-focus-within:ring-2 group-focus-within:ring-cyan-400/20 group-hover:border-white/20">
                    <!-- User Icon -->
                    <div class="pl-3.5 pr-1 text-slate-400 group-focus-within:text-cyan-300 transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>

                    <!-- Input Field -->
                    <input id="username"
                           type="text"
                           name="username"
                           x-model="username"
                           required
                           autofocus
                           placeholder="Username / Akun"
                           class="w-full py-2.5 sm:py-3 pr-3.5 bg-transparent text-xs text-white placeholder-slate-400/80 outline-none font-medium" />
                </div>
            </div>

            <!-- Password Input Container -->
            <div class="relative group">
                <div class="flex items-center rounded-2xl bg-white/[0.07] border border-white/[0.12] transition-all duration-200 group-focus-within:border-cyan-400/60 group-focus-within:ring-2 group-focus-within:ring-cyan-400/20 group-hover:border-white/20">
                    <!-- Lock Icon -->
                    <div class="pl-3.5 pr-1 text-slate-400 group-focus-within:text-cyan-300 transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>

                    <!-- Input Field -->
                    <input id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           x-model="password"
                           required
                           placeholder="••••••••"
                           class="w-full py-2.5 sm:py-3 pr-2 bg-transparent text-xs text-white placeholder-slate-400/80 outline-none font-medium tracking-wider" />

                    <!-- Eye Password Toggle Button -->
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="pr-3.5 text-slate-400 hover:text-white transition-colors focus:outline-none">
                        <!-- Eye Slash -->
                        <svg x-show="!showPassword" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <!-- Eye Open -->
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Arrow Submit Button (Matches Reference Image) -->
            <div class="pt-1">
                <button type="submit"
                        :disabled="isSubmitting"
                        class="w-full py-3 px-4 rounded-2xl text-white font-medium flex items-center justify-center transition-all duration-200 cursor-pointer shadow-lg active:scale-[0.98] group"
                        style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 45%, #38bdf8 100%);
                               box-shadow: 0 10px 25px -4px rgba(99, 102, 241, 0.55),
                                           inset 0 1px 1px 0 rgba(255, 255, 255, 0.35);">
                    
                    <!-- Loading Spinner -->
                    <template x-if="isSubmitting">
                        <svg class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>

                    <!-- Right Arrow Icon '→' -->
                    <template x-if="!isSubmitting">
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </template>
                </button>
            </div>
        </form>

    </div>

    <!-- Subtle Footer Note -->
    <div class="mt-3 text-center">
        <span class="text-[10px] font-mono text-slate-500/80">PT Media Solusi Network &bull; IMS v2.4</span>
    </div>

</div>
@endsection
