@extends('layouts.guest', ['title' => 'Masuk ke Sistem'])

@section('content')
<div class="w-full max-w-[400px] sm:max-w-[420px] relative"
     x-data="{
        username: '{{ old('username', '') }}',
        password: '',
        showPassword: false,
        isSubmitting: false
     }">

    <!-- Frosted Glass Login Card -->
    <div class="relative rounded-[32px] p-7 sm:p-8 backdrop-blur-2xl border border-white/[0.18] transition-all duration-300 overflow-hidden"
         style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.09) 0%, rgba(255, 255, 255, 0.03) 100%);
                box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.8),
                            inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.35),
                            inset 0 -1px 1px 0 rgba(255, 255, 255, 0.06);">
        
        <!-- Subtle Top Specular Glass Reflection Highlight -->
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-52 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

        <!-- Brand Header: Logo MSN + Official Branding -->
        <div class="flex flex-col items-center text-center mb-6 relative z-10">
            <div class="relative group cursor-pointer mb-3">
                <!-- Ambient Glow behind MSN Logo -->
                <div class="absolute -inset-2 bg-gradient-to-r from-cyan-400 via-sky-500 to-purple-600 rounded-2xl blur-lg opacity-50 group-hover:opacity-80 transition-opacity"></div>
                
                <!-- MSN Logo Container (Glass Squircle) -->
                <div class="relative w-16 h-16 rounded-2xl bg-white/95 border border-white/90 p-2.5 flex items-center justify-center shadow-xl shadow-cyan-950/40 ring-4 ring-white/10 transition-transform duration-300 group-hover:scale-105">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Logo MSN" class="w-full h-full object-contain">
                </div>
            </div>

            <!-- Title & Subtitle -->
            <h1 class="text-base sm:text-lg font-bold text-white tracking-tight drop-shadow-sm">
                PT MEDIA SOLUSI NETWORK
            </h1>
            <p class="text-[10.5px] font-semibold tracking-wider text-cyan-300/90 uppercase mt-0.5">
                IMS Core Network Portal
            </p>
        </div>

        <!-- Alert Notifications (Errors / Success) -->
        @if(session('success'))
            <div class="mb-4 p-2.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-[11px] flex items-center gap-2 backdrop-blur-md">
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] flex items-center gap-2 backdrop-blur-md">
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-rose-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] backdrop-blur-md">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('login.submit') }}" method="POST" class="space-y-3.5" @submit="isSubmitting = true">
            @csrf

            <!-- Username Input (Clean Dark Glass Pill) -->
            <div class="relative group">
                <div class="flex items-center h-12 rounded-2xl bg-[#0b1322]/85 hover:bg-[#0e192c]/95 focus-within:bg-[#0e192c] border border-white/[0.14] focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-400/25 transition-all shadow-inner overflow-hidden">
                    <!-- Icon -->
                    <div class="pl-4 pr-2 text-slate-400 group-focus-within:text-cyan-400 transition-colors shrink-0">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>

                    <!-- Input -->
                    <input id="username"
                           type="text"
                           name="username"
                           x-model="username"
                           required
                           autofocus
                           placeholder="Username / Email"
                           class="w-full h-full bg-transparent pr-4 text-xs font-medium text-white placeholder-slate-400/80 outline-none border-none focus:ring-0" />
                </div>
            </div>

            <!-- Password Input (Clean Dark Glass Pill + Eye Toggle) -->
            <div class="relative group">
                <div class="flex items-center h-12 rounded-2xl bg-[#0b1322]/85 hover:bg-[#0e192c]/95 focus-within:bg-[#0e192c] border border-white/[0.14] focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-400/25 transition-all shadow-inner overflow-hidden">
                    <!-- Icon -->
                    <div class="pl-4 pr-2 text-slate-400 group-focus-within:text-cyan-400 transition-colors shrink-0">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>

                    <!-- Input -->
                    <input id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           x-model="password"
                           required
                           placeholder="••••••••"
                           class="w-full h-full bg-transparent pr-2 text-xs font-medium text-white placeholder-slate-400/80 outline-none border-none focus:ring-0 tracking-wider" />

                    <!-- Eye Toggle Button -->
                    <button type="button"
                            @click="showPassword = !showPassword"
                            tabindex="-1"
                            class="pr-4 pl-1 text-slate-400 hover:text-white transition-colors focus:outline-none shrink-0 cursor-pointer">
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

            <!-- Submit Button (Oceanic Teal & Cyan Gradient with "Login" text) -->
            <div class="pt-1.5">
                <button type="submit"
                        :disabled="isSubmitting"
                        class="w-full h-12 rounded-2xl text-white font-medium flex items-center justify-center transition-all duration-200 cursor-pointer shadow-lg active:scale-[0.98] hover:brightness-105 border border-teal-400/30 group"
                        style="background: linear-gradient(108deg, #032b35 0%, #043f4e 25%, #065b70 55%, #087d94 80%, #009aa9 100%);
                               box-shadow: 0 10px 25px -4px rgba(6, 91, 112, 0.6),
                                           inset 0 1px 1px 0 rgba(255, 255, 255, 0.35);">
                    
                    <!-- Loading State -->
                    <template x-if="isSubmitting">
                        <div class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-semibold tracking-wider text-white">Memproses...</span>
                        </div>
                    </template>

                    <!-- Login Text Label -->
                    <template x-if="!isSubmitting">
                        <span class="text-sm font-bold tracking-wider uppercase text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                            Login
                        </span>
                    </template>
                </button>
            </div>
        </form>

    </div>

    <!-- Subtle Footer Note -->
    <div class="mt-3.5 text-center">
        <span class="text-[11px] font-mono text-slate-400/80">&copy; {{ date('Y') }} PT Media Solusi Network &bull; IMS-V2</span>
    </div>

</div>
@endsection
