<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - IMS Router Management</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-blue-500 selection:text-white bg-slate-950"
      x-data="{ 
          sidebarOpen: window.innerWidth >= 1024,
          toggleSidebar() {
              this.sidebarOpen = !this.sidebarOpen;
          }
      }">
    <div class="min-h-full flex flex-col">
        <!-- Ambient background glows -->
        <div class="pointer-events-none fixed -top-40 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="pointer-events-none fixed top-1/2 -right-40 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>

        <div class="flex-1 flex overflow-hidden">
            <!-- Mobile Sidebar Backdrop -->
            <div x-show="sidebarOpen"
                 x-cloak
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm lg:hidden transition-opacity"></div>

            <!-- Sidebar (Dapat Dibuka & Ditutup Responsif) -->
            <aside :class="sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-0 lg:w-0 border-none overflow-hidden'"
                   class="fixed inset-y-0 left-0 z-50 bg-slate-900/95 border-r border-slate-800/80 flex flex-col transition-all duration-300 ease-in-out lg:static backdrop-blur-xl shrink-0">
                
                <!-- Sidebar Header / Logo -->
                <div class="h-16 px-4 flex items-center justify-between border-b border-slate-800/80">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-900 border border-blue-500/30 p-1 shadow-md shadow-blue-500/20 flex items-center justify-center flex-shrink-0">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                        </div>
                        <div class="whitespace-nowrap">
                            <span class="font-bold text-white tracking-wide text-base">IMS <span class="text-blue-400">ROUTER</span></span>
                            <span class="block text-[10px] text-slate-400 tracking-wider uppercase font-semibold">System Manager</span>
                        </div>
                    </a>
                    
                    <!-- Single Toggle Button '<<' (Saat Sidebar Terbuka) -->
                    <button @click="toggleSidebar()"
                            title="Tutup Sidebar (<<)"
                            class="p-2 text-blue-400 hover:text-white rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/80 transition flex items-center justify-center flex-shrink-0 shadow-sm">
                        <!-- Double Chevron Left '<<' -->
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                        </svg>
                    </button>
                </div>

                <!-- Active User Summary Card -->
                <div class="p-3 mx-3 my-3 rounded-xl bg-slate-950/70 border border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-sm text-blue-400 flex-shrink-0">
                            {{ substr(auth()->user()->nama, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->nama }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border mt-0.5 {{ auth()->user()->role_badge_classes }}">
                                {{ auth()->user()->nama_level }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Navigation Menu -->
                <nav class="flex-1 px-3 py-2 space-y-1.5 overflow-y-auto"
                     x-data="{
                         permintaanOpen: {{ request()->routeIs('teknik.permintaan.*') ? 'true' : 'false' }}
                     }">
                    
                    <!-- 1. Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Bar Chart Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    @if(auth()->user()?->hasRole(['teknik', 'direktur', 'admin']))
                    <!-- ============================================== -->
                    <!-- TEKNIK (DRAFER & LAPANGAN) SECTION             -->
                    <!-- ============================================== -->
                    <div class="pt-3 pb-1">
                        <div class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                            <span>Modul Teknik</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- 2. Tiket -->
                    <a href="{{ route('teknik.tiket') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.tiket*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Ticket Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.tiket*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                        <span>Tiket</span>
                    </a>

                    @if(!auth()->user() || !auth()->user()->isNoc())
                        <!-- 3. Pendaftaran -->
                        <a href="{{ route('teknik.pendaftaran') }}"
                           class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.pendaftaran*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                            <!-- Registration / User with Globe Icon -->
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.pendaftaran*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            <span>Pendaftaran</span>
                        </a>
                    @endif

                    <!-- 4. Permintaan (Accordion Dropdown) -->
                    <div>
                        <button type="button"
                                @click="permintaanOpen = !permintaanOpen"
                                :class="permintaanOpen || {{ request()->routeIs('teknik.permintaan.*', 'noc.aktivasi*') ? 'true' : 'false' }} ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-150 group">
                            <span class="flex items-center gap-3.5">
                                <!-- Layered Database / Storage Icon -->
                                <svg class="w-5 h-5 flex-shrink-0" :class="permintaanOpen || {{ request()->routeIs('teknik.permintaan.*', 'noc.aktivasi*') ? 'true' : 'false' }} ? 'text-white' : 'text-slate-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                </svg>
                                <span>Permintaan</span>
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-200"
                                 :class="permintaanOpen ? 'rotate-180 text-white' : 'text-slate-400 group-hover:text-white'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <!-- Dropdown Sub-menu Items -->
                        <div x-show="permintaanOpen"
                             x-cloak
                             x-collapse
                             class="mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            
                            @if(auth()->user()?->hasRole(['noc', 'direktur']))
                            <!-- Aktivasi Jaringan (Khusus NOC / Direktur) -->
                            <a href="{{ route('noc.aktivasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.aktivasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.aktivasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Aktivasi Jaringan</span>
                            </a>
                            @endif

                            <!-- UP/Downgrade -->
                            <a href="{{ route('teknik.permintaan.up-downgrade') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.up-downgrade') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.up-downgrade') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>UP/Downgrade</span>
                            </a>

                            <!-- Terminasi -->
                            <a href="{{ route('teknik.permintaan.terminasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.terminasi') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.terminasi') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Terminasi</span>
                            </a>

                            <!-- Suspend -->
                            <a href="{{ route('teknik.permintaan.suspend') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.suspend') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.suspend') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Suspend</span>
                            </a>
                        </div>
                    </div>

                    <!-- 5. Pelanggan -->
                    <a href="{{ route('teknik.pelanggan') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.pelanggan*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Customers / Group Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.pelanggan*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <span>Pelanggan</span>
                    </a>

                    <!-- 6. Peta Jaringan & Jalur FTTH (GIS) -->
                    <a href="{{ route('teknik.peta-jaringan') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.peta-jaringan*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Map / GIS Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.peta-jaringan*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689A1.125 1.125 0 0 0 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                        </svg>
                        <span>Peta Jaringan FTTH</span>
                    </a>

                    <!-- 7. Cek Coverage ODP -->
                    <a href="{{ route('teknik.coverage') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.coverage*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Target Map Marker Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.coverage*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        <span>Cek Coverage</span>
                    </a>
                    @endif

                    @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                    <!-- ============================================== -->
                    <!-- NOC (NETWORK OPERATIONS CENTER) SECTION        -->
                    <!-- ============================================== -->
                    <div class="pt-3 pb-1">
                        <div class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                            <span>Modul NOC</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- 1. NOC Dashboard -->
                    <a href="{{ route('noc.dashboard') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('noc.dashboard*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.dashboard*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                        </svg>
                        <span>NOC Command</span>
                    </a>

                    <!-- 2. Permintaan (Eksekusi Jaringan) Dropdown for NOC -->
                    <div x-data="{ permintaanNocOpen: {{ request()->routeIs('noc.aktivasi*', 'noc.suspend*', 'noc.terminasi*', 'teknik.permintaan.*') ? 'true' : 'false' }} }">
                        <button type="button"
                                @click="permintaanNocOpen = !permintaanNocOpen"
                                :class="permintaanNocOpen || {{ request()->routeIs('noc.aktivasi*', 'noc.suspend*', 'noc.terminasi*', 'teknik.permintaan.*') ? 'true' : 'false' }} ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-150 group">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0" :class="permintaanNocOpen || {{ request()->routeIs('noc.aktivasi*', 'noc.suspend*', 'noc.terminasi*', 'teknik.permintaan.*') ? 'true' : 'false' }} ? 'text-white' : 'text-slate-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                </svg>
                                <span>Permintaan</span>
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-200"
                                 :class="permintaanNocOpen ? 'rotate-180 text-white' : 'text-slate-400 group-hover:text-white'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="permintaanNocOpen"
                             x-cloak
                             x-collapse
                             class="mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            <!-- Aktivasi Jaringan -->
                            <a href="{{ route('noc.aktivasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.aktivasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.aktivasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Aktivasi Jaringan</span>
                            </a>

                            <!-- UP/Downgrade -->
                            <a href="{{ route('teknik.permintaan.up-downgrade') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.up-downgrade*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.up-downgrade*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>UP / Downgrade</span>
                            </a>

                            <!-- Suspend (Isolir) -->
                            <a href="{{ route('noc.suspend') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.suspend*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.suspend*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Suspend (Isolir)</span>
                            </a>

                            <!-- Terminasi -->
                            <a href="{{ route('noc.terminasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.terminasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.terminasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Terminasi</span>
                            </a>
                        </div>
                    </div>

                    <!-- 3. Infrastruktur (OLT, GPON, ODP, POP, Wilayah) Dropdown -->
                    <div x-data="{ infraOpen: {{ request()->routeIs('noc.olt*', 'noc.gpon*', 'noc.odp*', 'noc.pop*', 'noc.wilayah*') ? 'true' : 'false' }} }">
                        <button type="button"
                                @click="infraOpen = !infraOpen"
                                :class="infraOpen || {{ request()->routeIs('noc.olt*', 'noc.gpon*', 'noc.odp*', 'noc.pop*', 'noc.wilayah*') ? 'true' : 'false' }} ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-150 group">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0" :class="infraOpen || {{ request()->routeIs('noc.olt*', 'noc.gpon*', 'noc.odp*', 'noc.pop*', 'noc.wilayah*') ? 'true' : 'false' }} ? 'text-white' : 'text-slate-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                                </svg>
                                <span>Infrastruktur</span>
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-200"
                                 :class="infraOpen ? 'rotate-180 text-white' : 'text-slate-400 group-hover:text-white'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="infraOpen"
                             x-cloak
                             x-collapse
                             class="mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            <a href="{{ route('noc.gpon') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.gpon*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.gpon*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Topologi & GPON Port</span>
                            </a>
                            <a href="{{ route('noc.olt') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.olt*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.olt*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>OLT & Master Node</span>
                            </a>
                            <a href="{{ route('noc.odp') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.odp*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.odp*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>ODP (Distribution)</span>
                            </a>
                            <a href="{{ route('noc.pop') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.pop*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.pop*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>POP (Point of Presence)</span>
                            </a>
                            <a href="{{ route('noc.wilayah') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.wilayah*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.wilayah*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Wilayah Perangkat</span>
                            </a>
                        </div>
                    </div>

                    <!-- 4. Inventaris Perangkat -->
                    <a href="{{ route('noc.perangkat') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('noc.perangkat*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.perangkat*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                        <span>Inventaris Perangkat</span>
                    </a>

                    <!-- 5. Tiket Gangguan -->
                    <a href="{{ route('teknik.tiket') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.tiket*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.tiket*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                        <span>Tiket Gangguan</span>
                    </a>

                    <!-- 6. Data Pelanggan -->
                    <a href="{{ route('teknik.pelanggan') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('teknik.pelanggan*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.pelanggan*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <span>Data Pelanggan</span>
                    </a>
                    @endif

                    @if(auth()->user()?->hasRole(['finance', 'direktur', 'admin']))
                    <!-- ============================================== -->
                    <!-- FINANCE & BILLING SECTION                      -->
                    <!-- ============================================== -->
                    <div class="pt-3 pb-1">
                        <div class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                            <span>Modul Finance</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- Billing Layanan -->
                    <a href="{{ route('finance.billing-layanan') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('finance.billing-layanan*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Banknotes / Credit Card Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('finance.billing-layanan*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span>Billing Layanan</span>
                    </a>

                    <!-- Billing Registrasi -->
                    <a href="{{ route('finance.billing-registrasi') }}"
                       class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('finance.billing-registrasi*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <!-- Receipt / Clipboard Document List Icon -->
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('finance.billing-registrasi*') ? 'text-white' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span>Billing Registrasi</span>
                    </a>

                    <!-- Permintaan ke NOC (Accordion) -->
                    <div x-data="{ permintaanFinanceOpen: {{ request()->routeIs('finance.permintaan.*') ? 'true' : 'false' }} }">
                        <button type="button"
                                @click="permintaanFinanceOpen = !permintaanFinanceOpen"
                                :class="permintaanFinanceOpen || {{ request()->routeIs('finance.permintaan.*') ? 'true' : 'false' }} ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-150 group">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0" :class="permintaanFinanceOpen || {{ request()->routeIs('finance.permintaan.*') ? 'true' : 'false' }} ? 'text-white' : 'text-slate-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                                <span>Permintaan ke NOC</span>
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-200"
                                 :class="permintaanFinanceOpen ? 'rotate-180 text-white' : 'text-slate-400 group-hover:text-white'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="permintaanFinanceOpen"
                             x-cloak
                             x-collapse
                             class="mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            <a href="{{ route('finance.permintaan.up-downgrade') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('finance.permintaan.up-downgrade*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('finance.permintaan.up-downgrade*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>UP / Downgrade</span>
                            </a>
                            <a href="{{ route('finance.permintaan.suspend') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('finance.permintaan.suspend*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('finance.permintaan.suspend*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Suspend (Isolir)</span>
                            </a>
                            <a href="{{ route('finance.permintaan.terminasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('finance.permintaan.terminasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('finance.permintaan.terminasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-500' }}"></span>
                                <span>Terminasi</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- 6. Keluar -->
                    <div class="pt-2">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-500/10 hover:text-rose-400 transition duration-150">
                                <!-- Exit / Logout Door Icon -->
                                <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </nav>

                <!-- Sidebar Footer Info -->
                <div class="p-3 border-t border-slate-800/80 text-center">
                    @if(auth()->user()?->isFinance())
                        <span class="text-[11px] text-amber-400/90 font-medium">Modul Finance &middot; v1.0</span>
                    @elseif(auth()->user()?->isNoc())
                        <span class="text-[11px] text-indigo-400/90 font-medium">Modul NOC &middot; v1.0</span>
                    @elseif(auth()->user()?->isTeknik())
                        <span class="text-[11px] text-sky-400/90 font-medium">Modul Teknik &middot; v1.0</span>
                    @else
                        <span class="text-[11px] text-slate-500 font-medium">IMS Router &middot; v1.0</span>
                    @endif
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
                <!-- Top Navbar -->
                <header class="min-h-16 bg-slate-900/60 backdrop-blur-xl border-b border-slate-800/80 flex items-center justify-between px-4 sm:px-6 lg:px-8 sticky top-0 z-30 py-2">
                    <div class="flex items-center gap-3">
                        <!-- Single Toggle Button '>>' (HANYA MUNCUL saat Sidebar Tertutup, mencegah tombol dobel) -->
                        <button x-show="!sidebarOpen"
                                x-cloak
                                @click="toggleSidebar()" 
                                title="Buka Sidebar (>>)"
                                class="p-2 text-blue-400 hover:text-white rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/80 transition flex items-center justify-center flex-shrink-0 shadow-sm">
                            <!-- Double Chevron Right '>>' -->
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>

                        <div>
                            <h1 class="text-base font-semibold text-white leading-tight">@yield('page_title', 'Dashboard')</h1>
                            <p class="text-xs text-slate-400 hidden sm:block">Internet Management System &middot; Teknik Portal</p>
                        </div>
                    </div>

                    <!-- Right Side Navbar -->
                    <div class="flex items-center gap-3 sm:gap-4">
                        
                        <!-- Status Gateway & Jam Realtime WIB (Waktu Indonesia Barat) -->
                        <div class="flex flex-col items-end gap-1"
                             x-data="{
                                 timeWib: '',
                                 dateWib: '',
                                 updateClock() {
                                     const now = new Date();
                                     
                                     // Format Waktu WIB (Asia/Jakarta)
                                     const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                                         timeZone: 'Asia/Jakarta',
                                         hour12: false,
                                         hour: '2-digit',
                                         minute: '2-digit',
                                         second: '2-digit',
                                     });

                                     // Format Tanggal
                                     const dateFormatter = new Intl.DateTimeFormat('id-ID', {
                                         timeZone: 'Asia/Jakarta',
                                         weekday: 'short',
                                         day: 'numeric',
                                         month: 'short',
                                         year: 'numeric',
                                     });

                                     this.timeWib = timeFormatter.format(now).replace(/\./g, ':') + ' WIB';
                                     this.dateWib = dateFormatter.format(now);
                                 }
                             }"
                             x-init="updateClock(); setInterval(() => updateClock(), 1000)">
                            
                            <!-- 1. Status Gateway -->
                            <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-950/80 border border-slate-800 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-slate-400">Status Gateway: <strong class="text-emerald-400 font-semibold">Online</strong></span>
                            </div>

                            <!-- 2. Jam Realtime WIB (Di Bawah Status Gateway) -->
                            <div class="flex items-center gap-1 text-[11px] text-slate-300 font-medium tracking-wide">
                                <svg class="w-3 h-3 text-blue-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span class="font-mono text-white font-bold" x-text="timeWib">--:--:-- WIB</span>
                                <span class="text-[10px] text-slate-400 hidden sm:inline" x-text="'&middot; ' + dateWib"></span>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-800/60 border border-transparent hover:border-slate-700 transition">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 p-0.5 flex items-center justify-center font-bold text-xs text-white flex-shrink-0">
                                    <div class="w-full h-full bg-slate-900 rounded-[6px] flex items-center justify-center">
                                        {{ substr(auth()->user()->nama, 0, 1) }}
                                    </div>
                                </div>
                                <div class="text-left hidden md:block">
                                    <span class="block text-xs font-semibold text-white leading-tight">{{ auth()->user()->nama }}</span>
                                    <span class="block text-[10px] text-blue-400">{{ auth()->user()->nama_level }}</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-cloak
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-56 rounded-xl bg-slate-900 border border-slate-800 shadow-xl shadow-black/50 py-1.5 z-50">
                                <div class="px-4 py-2 border-b border-slate-800 text-xs">
                                    <p class="font-medium text-white">{{ auth()->user()->nama }}</p>
                                    <p class="text-slate-400 truncate">{{ auth()->user()->username }}</p>
                                </div>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-xs text-rose-400 hover:bg-slate-800/60 flex items-center gap-2">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                        </svg>
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <!-- Flash Message -->
                    @if(session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </div>
</body>
</html>
