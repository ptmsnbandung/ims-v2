@extends('layouts.guest', ['title' => 'Masuk ke Sistem'])

@section('content')
<div x-data="{
    username: '{{ old('username', '') }}',
    password: '',
    showPassword: false,
    setDemo(demoUsername) {
        this.username = demoUsername;
        this.password = '123456';
    }
}">
    <!-- Header Form -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-[#1E293B]">Masuk ke Akun Pengguna</h3>
        <p class="text-sm text-[#64748B]">Gunakan akun dari data pengguna database router_ims</p>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="mb-5 p-4 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0] text-[#047857] text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 text-[#059669]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 p-4 rounded-xl bg-[#FFF1F2] border border-[#FECDD3] text-[#BE123C] text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 text-[#E11D48]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="mb-5 p-4 rounded-xl bg-[#F0F9FF] border border-[#BAE6FD] text-[#0369A1] text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 text-[#0284C7]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-4 rounded-xl bg-[#FFF1F2] border border-[#FECDD3] text-[#BE123C] text-sm">
            <div class="font-medium mb-1">Gagal Masuk:</div>
            <ul class="list-disc list-inside space-y-1 text-xs text-[#E11D48]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Demo Role Selector Buttons (Akun Asli Database router_ims) -->
    <div class="mb-6 bg-[#F8FAFC] p-3.5 rounded-xl border border-[#E2E8F0]">
        <label class="block text-xs font-semibold text-[#64748B] uppercase tracking-wider mb-2.5">
            ⚡ Quick Demo Accounts (Akun Database router_ims)
        </label>
        <div class="grid grid-cols-3 gap-2">
            <!-- Teknik -->
            <button type="button"
                    @click="setDemo('nunu@ptmsn.co.id')"
                    :class="username === 'nunu@ptmsn.co.id' ? 'ring-2 ring-blue-500 bg-[#EFF6FF] border-[#BFDBFE]' : 'bg-white hover:bg-[#F8FAFC] border-[#E2E8F0]'"
                    class="p-2.5 rounded-lg border text-left transition duration-150 flex flex-col justify-between group cursor-pointer shadow-xs">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#EFF6FF] text-[#2563EB] border border-[#BFDBFE]">
                        Teknik
                    </span>
                </div>
                <span class="text-[11px] text-[#64748B] mt-1.5">Drafter Pelanggan</span>
            </button>

            <!-- NOC -->
            <button type="button"
                    @click="setDemo('kelvin@ptmsn.co.id')"
                    :class="username === 'kelvin@ptmsn.co.id' ? 'ring-2 ring-blue-500 bg-[#EFF6FF] border-[#BFDBFE]' : 'bg-white hover:bg-[#F8FAFC] border-[#E2E8F0]'"
                    class="p-2.5 rounded-lg border text-left transition duration-150 flex flex-col justify-between group cursor-pointer shadow-xs">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#EFF6FF] text-[#2563EB] border border-[#BFDBFE]">
                        NOC
                    </span>
                </div>
                <span class="text-[11px] text-[#64748B] mt-1.5">Aktivasi & Router</span>
            </button>

            <!-- Finance -->
            <button type="button"
                    @click="setDemo('karmelia@ptmsn.co.id')"
                    :class="username === 'karmelia@ptmsn.co.id' ? 'ring-2 ring-blue-500 bg-[#EFF6FF] border-[#BFDBFE]' : 'bg-white hover:bg-[#F8FAFC] border-[#E2E8F0]'"
                    class="p-2.5 rounded-lg border text-left transition duration-150 flex flex-col justify-between group cursor-pointer shadow-xs">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#EFF6FF] text-[#2563EB] border border-[#BFDBFE]">
                        Finance
                    </span>
                </div>
                <span class="text-[11px] text-[#64748B] mt-1.5">Billing & Suspend</span>
            </button>
        </div>
    </div>

    <!-- Login Form -->
    <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
        @csrf

        <!-- Username / Email Field -->
        <div>
            <label for="username" class="block text-xs font-medium text-[#1E293B] mb-1.5">
                Username / Email Pengguna
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <input id="username"
                       type="text"
                       name="username"
                       x-model="username"
                       required
                       autofocus
                       placeholder="Masukkan username pengguna..."
                       class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-[#1E293B] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-blue-500/15 focus:border-[#3B82F6] text-sm transition" />
            </div>
        </div>

        <!-- Password Field -->
        <div>
            <label for="password" class="block text-xs font-medium text-[#1E293B] mb-1.5">
                Kata Sandi
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <input id="password"
                       :type="showPassword ? 'text' : 'password'"
                       name="password"
                       x-model="password"
                       required
                       placeholder="••••••••"
                       class="w-full pl-10 pr-10 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-[#1E293B] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-blue-500/15 focus:border-[#3B82F6] text-sm transition" />

                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#94A3B8] hover:text-[#1E293B]">
                    <svg x-show="!showPassword" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox"
                       name="remember"
                       value="1"
                       class="w-4 h-4 rounded bg-white border-[#E2E8F0] text-blue-600 focus:ring-blue-500/30">
                <span class="text-xs text-[#334155]">Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit"
                    class="w-full py-2.5 px-4 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-md shadow-blue-600/25 transition duration-150 flex items-center justify-center gap-2 cursor-pointer">
                <span>Masuk Sekarang</span>
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection
