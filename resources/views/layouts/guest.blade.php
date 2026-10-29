<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full w-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="view-transition" content="same-origin">

    <title>{{ isset($title) && $title ? ($title . ' | IMSv2 | PT MEDIA SOLUSI NETWORK') : 'IMSv2 | PT MEDIA SOLUSI NETWORK' }}</title>

    <!-- Strict 1-Screen Viewport Scale & Layout Synchronization with app.blade.php -->
    <style>
        @view-transition {
            navigation: auto;
        }
        html {
            font-size: 13px !important;
        }
        @media (max-width: 639.98px) {
            html {
                font-size: 14.5px !important;
            }
        }

        /* Custom Elegant Blue Scrollbar menempel di ujung kanan */
        .ims-main-scroll::-webkit-scrollbar,
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        .ims-main-scroll::-webkit-scrollbar-button,
        ::-webkit-scrollbar-button {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        .ims-main-scroll::-webkit-scrollbar-track,
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        .ims-main-scroll::-webkit-scrollbar-thumb,
        ::-webkit-scrollbar-thumb {
            background: #0284c7;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .ims-main-scroll::-webkit-scrollbar-thumb:hover,
        ::-webkit-scrollbar-thumb:hover {
            background: #0369a1;
        }
        * {
            scrollbar-width: thin;
            scrollbar-color: #0284c7 transparent;
        }

        /* Custom Ultra-Thin Scrollbar for Sidebar (1px) */
        .ims-sidebar-nav {
            scrollbar-width: thin !important;
            scrollbar-color: rgba(56, 189, 248, 0.4) transparent !important;
        }
        .ims-sidebar-nav::-webkit-scrollbar {
            width: 1px !important;
            height: 1px !important;
        }
        .ims-sidebar-nav::-webkit-scrollbar-button {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        .ims-sidebar-nav::-webkit-scrollbar-track {
            background: transparent !important;
        }
        .ims-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(56, 189, 248, 0.45) !important;
            border-radius: 9999px !important;
            border: none !important;
        }
        .ims-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(56, 189, 248, 0.9) !important;
        }
        html, body {
            height: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #F8FAFC !important;
            overflow: hidden !important;
        }
        [x-cloak] { display: none !important; }

        /* Sidebar Width Matching app.blade.php exactly */
        .ims-sidebar {
            width: 16rem !important;
            background-color: #061d28 !important;
            border-right: 1px solid #0d2a38 !important;
        }

        /* Tutup/Sembunyikan sidebar di background layar mobile (< 1024px) */
        @media (max-width: 1023.98px) {
            .ims-sidebar {
                display: none !important;
            }
        }

        /* Autofill Styling */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #0b1322 inset !important;
            -webkit-text-fill-color: #f1f5f9 !important;
            caret-color: #38bdf8 !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full w-full font-sans antialiased text-slate-800 bg-[#F8FAFC] relative selection:bg-cyan-500 selection:text-white overflow-hidden"
      x-data="{
          isEntering: false,
          triggerEnter(redirectUrl) {
              if (this.isEntering) return;
              this.isEntering = true;
              const target = redirectUrl || '{{ route('dashboard') }}';
              setTimeout(() => {
                  window.location.href = target;
              }, 280);
          }
      }">

    <!-- ========================================================================= -->
    <!-- EXACT PIXEL-PERFECT REPLICA OF REAL IMS-V2 DASHBOARD (GAMBAR 2)           -->
    <!-- Menggunakan dimensi, class, dan struktur 1:1 dengan layouts/app.blade.php -->
    <!-- ========================================================================= -->
    <div class="fixed inset-0 w-full h-full pointer-events-none select-none z-0 overflow-hidden bg-[#F8FAFC]">
        <div class="min-h-full h-screen flex flex-col overflow-hidden ims-layout-root w-full">
            <div class="flex-1 flex overflow-hidden min-h-0 h-full w-full">

                <!-- Sidebar Kiri (Hanya tampil di desktop, tersembunyi/tertutup di mobile) -->
                <aside class="ims-sidebar hidden lg:flex fixed inset-y-0 left-0 z-[60] lg:z-40 bg-[#061d28] border-r border-[#0d2a38] flex-col lg:sticky lg:top-0 lg:h-screen shrink-0 overflow-visible text-slate-300">
                    <!-- Sidebar Header / Logo (h-16 px-3.5) -->
                    <div class="h-16 px-3.5 flex items-center gap-3 border-b border-[#0d2a38] shrink-0">
                        <div class="w-9 h-9 rounded-xl bg-[#0c2f42] border border-cyan-400/30 p-1 shadow-md shadow-cyan-500/20 flex items-center justify-center shrink-0">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                        </div>
                        <div class="whitespace-nowrap">
                            <span class="font-bold text-white tracking-tight text-base">IMS<span class="text-blue-500 font-extrabold">-V2</span></span>
                            <span class="block text-[9.5px] text-[#94A3B8] font-medium leading-tight">Integrated Management System</span>
                        </div>
                    </div>

                    <!-- Sidebar Navigation Menu -->
                    <nav class="flex-1 min-h-0 px-2.5 py-3 space-y-1.5 overflow-hidden">
                        <!-- 1. Dashboard (Active) -->
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-cyan-600 text-white font-semibold text-xs shadow-md shadow-cyan-900/30">
                            <svg class="w-5 h-5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                            <span>Dashboard</span>
                        </div>

                        <!-- 2. Data Pelanggan -->
                        <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                            <span>Data Pelanggan</span>
                        </div>

                        <!-- Modul NOC Header -->
                        <div class="pt-2.5 pb-1 px-3 text-[10px] font-bold text-[#9CA3AF] uppercase tracking-wider flex items-center justify-between">
                            <span>Modul NOC</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        </div>

                        <!-- Tiket -->
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                                <span>Tiket</span>
                            </div>
                        </div>

                        <!-- Permintaan -->
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                                <span>Permintaan</span>
                            </div>
                            <svg class="w-4 h-4 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </div>

                        <!-- OLT -->
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a3 3 0 0 1 2.4-1.1h7.7a3 3 0 0 1 2.4 1.1l2.1 3.45c.6.9.9 1.8.9 2.7"/></svg>
                                <span>OLT</span>
                            </div>
                            <svg class="w-4 h-4 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </div>

                        <!-- Infrastruktur -->
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                                <span>Infrastruktur</span>
                            </div>
                            <svg class="w-4 h-4 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </div>

                        <!-- Pusat Notifikasi -->
                        <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            <span>Pusat Notifikasi</span>
                        </div>

                        <!-- Keluar -->
                        <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-rose-400 text-xs font-medium">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            <span>Keluar</span>
                        </div>
                    </nav>

                    <!-- Sidebar Footer Info -->
                    <div class="p-3 border-t border-[#0d2a38] text-center shrink-0">
                        <span class="text-[11px] text-blue-400/90 font-medium">IMS-v2</span>
                    </div>
                </aside>

                <!-- Area Konten Utama Dashboard (Struktur Identik app.blade.php) -->
                <div class="flex-1 flex flex-col min-w-0 overflow-y-auto ims-main-scroll">
                    
                    <!-- Top Navbar (Persis layouts/app.blade.php: min-h-14 sm:min-h-16 px-3 sm:px-6 lg:px-8) -->
                    <header class="ims-top-navbar min-h-14 sm:min-h-16 shrink-0 flex items-center justify-between px-3 sm:px-6 lg:px-8 bg-white border-b border-slate-200 sticky top-0 z-50 py-2 shadow-xs transition-colors duration-200">
                        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                            <button type="button" class="ims-nav-btn p-2 rounded-xl transition flex items-center justify-center flex-shrink-0 cursor-pointer shadow-xs bg-slate-50 border border-slate-200">
                                <svg class="w-4 h-4 text-slate-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>
                            <div class="min-w-0">
                                <h1 class="ims-nav-title text-sm sm:text-base font-bold leading-tight truncate text-slate-900">Dashboard Utama</h1>
                                <p class="ims-nav-sub text-xs hidden sm:block text-slate-500">Integrated Management System &middot; Portal</p>
                            </div>
                        </div>

                        <!-- Right Side Navbar (Persis Gambar 2 Asli) -->
                        <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                            <!-- Bell Icon with Red Badge "13" -->
                            <div class="relative p-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                <span class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full text-[8.5px] font-bold text-white bg-rose-600 font-mono shadow-xs leading-none">13</span>
                                <span class="absolute bottom-1 right-1 w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            </div>

                            <!-- Moon Icon -->
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                </svg>
                            </div>

                            <!-- Realtime 2-Baris Clock WIB & Status Online (100% Persis Gambar 2 Asli) -->
                            <div class="hidden lg:flex flex-col items-end gap-0.5"
                                 x-data="{
                                     timeWib: '',
                                     dateWib: '',
                                     updateClock() {
                                         const now = new Date();
                                         this.timeWib = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(now).replace(/\./g, ':') + ' WIB';
                                         this.dateWib = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }).format(now);
                                     }
                                 }"
                                 x-init="updateClock(); setInterval(() => updateClock(), 1000)">
                                <!-- Baris 1: Status Gateway Online -->
                                <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-50 border border-slate-200 text-[11px]">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-emerald-700 font-semibold text-[11px]">Online</span>
                                </div>
                                <!-- Baris 2: Jam Realtime WIB -->
                                <div class="flex items-center gap-1 text-[11px] text-slate-600 font-medium tracking-wide">
                                    <svg class="w-3.5 h-3.5 text-[#0891b2] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="font-mono text-slate-900 font-bold" x-text="timeWib + ' &middot; ' + dateWib"></span>
                                </div>
                            </div>

                            <!-- User Profile Dropdown Button (100% Persis Gambar 2 Asli) -->
                            <div class="ims-nav-btn flex items-center gap-2 p-1 sm:p-1.5 rounded-xl border border-slate-200 bg-slate-50 shadow-xs">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#05404f] to-[#0891b2] flex items-center justify-center font-bold text-xs text-white shadow-xs">
                                    N
                                </div>
                                <div class="text-left hidden md:block pr-1">
                                    <span class="block text-xs font-bold leading-tight truncate text-slate-900">NOC</span>
                                    <span class="block text-[10px] text-[#0891b2] font-semibold">NOC</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>
                    </header>

                    <!-- Main Page Content (Persis layouts/app.blade.php: px-3 pt-1.5 pb-2 sm:px-4 sm:pt-2 sm:pb-2.5 lg:px-5 lg:pt-2 lg:pb-2.5) -->
                    <main class="flex-1 px-3 pt-1.5 pb-2 sm:px-4 sm:pt-2 sm:pb-2.5 lg:px-5 lg:pt-2 lg:pb-2.5 bg-[#F8FAFC]">
                        
                        <div class="space-y-3 sm:space-y-3.5">
                            
                            <!-- Role Welcome Banner (100% Persis Gambar 2 Asli) -->
                            <div class="ims-banner relative overflow-hidden rounded-2xl py-3 px-3.5 sm:py-3 sm:px-5 shadow-sm text-white border border-teal-500/20"
                                 style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
                                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <!-- Left: Profile & Welcome -->
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 text-white font-black text-xs flex items-center justify-center shadow-inner shrink-0 ring-2 ring-white/10">
                                            N
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h2 class="text-xs sm:text-sm font-extrabold text-white tracking-tight flex items-center gap-1.5">
                                                    <span>Halo, NOC!</span>
                                                    <span>👋</span>
                                                </h2>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-white/15 text-white/95 border border-white/20">
                                                    NOC
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-400/20 text-emerald-200 border border-emerald-400/30">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                                    Online
                                                </span>
                                            </div>
                                            <p class="text-[10.5px] sm:text-xs text-[#c6edf3] line-clamp-1 mt-1 leading-snug">
                                                Eksekusi Jaringan, Aktivasi, Suspend, &amp; Terminasi
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Right: View Tabs & Controls (100% Persis Gambar 2 Asli) -->
                                    <div class="flex items-center gap-2 shrink-0">
                                        <!-- Unified Switcher Pill -->
                                        <div class="flex items-center" style="height: 34px; padding: 3px; border-radius: 9999px; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.22); box-sizing: border-box;">
                                            <button type="button" class="inline-flex items-center gap-1.5 px-3.5 h-7 rounded-full bg-white text-[#032b35] text-[11px] font-bold shadow-xs">
                                                <svg class="w-3.5 h-3.5 text-[#032b35]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                                <span>Grafik Utama</span>
                                            </button>
                                            <button type="button" class="inline-flex items-center gap-1.5 px-3.5 h-7 rounded-full text-white/85 text-[11px] font-semibold">
                                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                <span>Data Pendaftaran</span>
                                            </button>
                                        </div>

                                        <!-- Year Filter Pill -->
                                        <div class="relative flex items-center h-[34px] px-3.5 rounded-full text-[11px] font-bold text-white bg-black/30 border border-white/20">
                                            <span>Tahun 2026</span>
                                            <svg class="w-3 h-3 ml-2 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                        </div>

                                        <!-- Status Layanan Button -->
                                        <div class="h-[34px] px-3.5 rounded-full text-[11px] font-bold text-white bg-[#10b981] border border-white/25 shadow-xs flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            <span>Status Layanan</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 1: Tren Bulanan (7 Cols) & Donut Bandwidth (5 Cols) (100% Persis Gambar 2 Asli) -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-3.5">
                                
                                <!-- 1. Grafik Tren User Baru per Bulan -->
                                <div class="md:col-span-7 lg:col-span-7 p-3 sm:p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2 sm:pb-1.5">
                                            <div class="min-w-0">
                                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2 leading-tight">
                                                    <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                                                    <span>Tren Pertumbuhan User Baru per Bulan</span>
                                                </h4>
                                                <p class="text-[10px] sm:text-[9.5px] text-slate-400 mt-0.5 leading-snug">
                                                    Registrasi vs aktivasi selesai sepanjang 2026.
                                                </p>
                                            </div>

                                            <!-- Mini KPI Badges -->
                                            <div class="flex items-center gap-2 sm:gap-1.5 shrink-0">
                                                <div class="flex items-center gap-1.5 px-2.5 py-1 sm:py-0.5 rounded-xl bg-blue-50 shadow-2xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                                    <span class="text-[8.5px] sm:text-[8px] uppercase font-bold tracking-wider text-blue-600 block leading-none">Registrasi</span>
                                                    <span class="text-xs font-black font-mono text-slate-900 leading-tight ml-0.5">185</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 px-2.5 py-1 sm:py-0.5 rounded-xl bg-emerald-50 shadow-2xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span class="text-[8.5px] sm:text-[8px] uppercase font-bold tracking-wider text-emerald-600 block leading-none">Aktif Online</span>
                                                    <span class="text-xs font-black font-mono text-slate-900 leading-tight ml-0.5">120</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Spline Chart SVG (Persis Curve ApexChart di Gambar 2 Asli) -->
                                        <div class="pt-2 px-1">
                                            <svg class="w-full h-[155px]" viewBox="0 0 620 155" preserveAspectRatio="none">
                                                <defs>
                                                    <linearGradient id="areaBlueGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#2563eb" stop-opacity="0.22" />
                                                        <stop offset="100%" stop-color="#2563eb" stop-opacity="0.0" />
                                                    </linearGradient>
                                                    <linearGradient id="areaGreenGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.25" />
                                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                                                    </linearGradient>
                                                </defs>

                                                <!-- Grid Lines (0, 10, 20, 30) -->
                                                <line x1="25" y1="20" x2="615" y2="20" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3,3" />
                                                <line x1="25" y1="58" x2="615" y2="58" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3,3" />
                                                <line x1="25" y1="96" x2="615" y2="96" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3,3" />
                                                <line x1="25" y1="134" x2="615" y2="134" stroke="#f1f5f9" stroke-width="1" />

                                                <!-- Y-Axis Scale Text -->
                                                <text x="14" y="24" fill="#94a3b8" font-size="9" text-anchor="end" font-family="monospace">30</text>
                                                <text x="14" y="62" fill="#94a3b8" font-size="9" text-anchor="end" font-family="monospace">20</text>
                                                <text x="14" y="100" fill="#94a3b8" font-size="9" text-anchor="end" font-family="monospace">10</text>
                                                <text x="14" y="137" fill="#94a3b8" font-size="9" text-anchor="end" font-family="monospace">0</text>

                                                <!-- Area Green (Aktif Online) -->
                                                <path d="M 35 95 C 80 95, 120 98, 160 102 C 200 106, 230 115, 270 102 C 310 90, 360 88, 400 48 C 430 75, 470 120, 510 134 C 550 134, 580 134, 610 134 L 610 134 L 35 134 Z" fill="url(#areaGreenGrad)" />
                                                <!-- Line Green -->
                                                <path d="M 35 95 C 80 95, 120 98, 160 102 C 200 106, 230 115, 270 102 C 310 90, 360 88, 400 48 C 430 75, 470 120, 510 134 C 550 134, 580 134, 610 134" fill="none" stroke="#10b981" stroke-width="2.2" stroke-linecap="round" />

                                                <!-- Area Blue (Registrasi) -->
                                                <path d="M 35 60 C 80 50, 120 80, 160 75 C 200 90, 230 85, 270 76 C 310 76, 360 62, 400 42 C 430 68, 470 118, 510 134 C 550 134, 580 134, 610 134 L 610 134 L 35 134 Z" fill="url(#areaBlueGrad)" />
                                                <!-- Line Blue -->
                                                <path d="M 35 60 C 80 50, 120 80, 160 75 C 200 90, 230 85, 270 76 C 310 76, 360 62, 400 42 C 430 68, 470 118, 510 134 C 550 134, 580 134, 610 134" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" />

                                                <!-- Key Points on Spline Curves (Dots) -->
                                                <circle cx="35" cy="60" r="3" fill="#2563eb" />
                                                <circle cx="85" cy="53" r="3" fill="#2563eb" />
                                                <circle cx="160" cy="75" r="3" fill="#2563eb" />
                                                <circle cx="270" cy="76" r="3" fill="#2563eb" />
                                                <circle cx="400" cy="42" r="3.5" fill="#2563eb" stroke="#ffffff" stroke-width="1.5" />
                                                <circle cx="445" cy="85" r="3" fill="#2563eb" />

                                                <circle cx="35" cy="95" r="3" fill="#10b981" />
                                                <circle cx="85" cy="95" r="3" fill="#10b981" />
                                                <circle cx="160" cy="102" r="3" fill="#10b981" />
                                                <circle cx="270" cy="102" r="3" fill="#10b981" />
                                                <circle cx="400" cy="48" r="3.5" fill="#10b981" stroke="#ffffff" stroke-width="1.5" />
                                            </svg>

                                            <!-- Month Labels (Jan - Des) -->
                                            <div class="flex justify-between text-[9px] text-slate-400 font-medium px-4 pt-1">
                                                <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span>
                                                <span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span><span>Nov</span><span>Des</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer Note -->
                                    <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500">
                                        <div class="flex items-center gap-3">
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                <span class="font-medium text-slate-700">Pendaftaran Baru</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span class="font-medium text-slate-700">Aktivasi Selesai (Aktif)</span>
                                            </span>
                                        </div>
                                        <span class="font-mono text-slate-400">Jan - Des 2026</span>
                                    </div>
                                </div>

                                <!-- 2. Donut Kategori Bandwidth (100% Persis Gambar 2 Asli) -->
                                <div class="md:col-span-5 lg:col-span-5 p-3 sm:p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div class="border-b border-slate-100 pb-2 sm:pb-1.5">
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2 leading-tight">
                                                <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                                                <span>Distribusi Kategori Bandwidth</span>
                                            </h4>
                                            <p class="text-[10px] sm:text-[9.5px] text-slate-400 mt-0.5 leading-snug">
                                                Pangsa paket layanan yang dipilih pelanggan.
                                            </p>
                                        </div>

                                        <!-- Donut & Detailed List (100% Persis Gambar 2 Asli) -->
                                        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                                            <!-- Donut Chart Canvas / SVG Container -->
                                            <div class="w-full sm:w-44 shrink-0 flex items-center justify-center relative">
                                                <svg class="w-32 h-32" viewBox="0 0 120 120">
                                                    <!-- Base Ring -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#f1f5f9" stroke-width="18" />
                                                    <!-- Cyan slice (LASTMILE 0.5%) -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#06b6d4" stroke-width="18" stroke-dasharray="2 281" stroke-dashoffset="0" />
                                                    <!-- Pink slice (BROADBAND NEW 0.5%) -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#ec4899" stroke-width="18" stroke-dasharray="2 281" stroke-dashoffset="-2" />
                                                    <!-- Orange slice (BROADBAND FREE 1.1%) -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#f59e0b" stroke-width="18" stroke-dasharray="4 279" stroke-dashoffset="-4" />
                                                    <!-- Purple slice (SMALL OFFICE 1.6%) -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#8b5cf6" stroke-width="18" stroke-dasharray="5 278" stroke-dashoffset="-8" />
                                                    <!-- Green slice (CORPORATE 2.7%) -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#00b074" stroke-width="18" stroke-dasharray="8 275" stroke-dashoffset="-13" />
                                                    <!-- Blue slice (UP TO NEW 93.5%) -->
                                                    <circle cx="60" cy="60" r="45" fill="none" stroke="#0284c7" stroke-width="18" stroke-dasharray="260 23" stroke-dashoffset="-21" />
                                                </svg>
                                                <!-- Inner Donut Pill Text -->
                                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                                    <span class="text-[9px] text-slate-400 font-medium">Total User</span>
                                                    <span class="text-base font-black font-mono text-slate-900 leading-tight">185</span>
                                                </div>
                                            </div>

                                            <!-- Detailed List (100% Persis Gambar 2 Asli) -->
                                            <div class="w-full flex-1 min-w-0 space-y-1">
                                                <div class="flex items-center justify-between text-[10px] py-0.5">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#0284c7] shrink-0"></span>
                                                        <span class="font-semibold text-slate-700 truncate">UP TO NEW</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 font-mono shrink-0">
                                                        <span class="font-bold text-slate-900">173</span>
                                                        <span class="text-[9px] text-slate-400">(93.5%)</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between text-[10px] py-0.5">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#00b074] shrink-0"></span>
                                                        <span class="font-semibold text-slate-700 truncate">CORPORATE</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 font-mono shrink-0">
                                                        <span class="font-bold text-slate-900">5</span>
                                                        <span class="text-[9px] text-slate-400">(2.7%)</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between text-[10px] py-0.5">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#8b5cf6] shrink-0"></span>
                                                        <span class="font-semibold text-slate-700 truncate">SMALL OFFICE HOME OFFICE</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 font-mono shrink-0">
                                                        <span class="font-bold text-slate-900">3</span>
                                                        <span class="text-[9px] text-slate-400">(1.6%)</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between text-[10px] py-0.5">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#f59e0b] shrink-0"></span>
                                                        <span class="font-semibold text-slate-700 truncate">BROADBAND FREE</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 font-mono shrink-0">
                                                        <span class="font-bold text-slate-900">2</span>
                                                        <span class="text-[9px] text-slate-400">(1.1%)</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between text-[10px] py-0.5">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#ec4899] shrink-0"></span>
                                                        <span class="font-semibold text-slate-700 truncate">BROADBAND NEW</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 font-mono shrink-0">
                                                        <span class="font-bold text-slate-900">1</span>
                                                        <span class="text-[9px] text-slate-400">(0.5%)</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between text-[10px] py-0.5">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#06b6d4] shrink-0"></span>
                                                        <span class="font-semibold text-slate-700 truncate">LASTMILE</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 font-mono shrink-0">
                                                        <span class="font-bold text-slate-900">1</span>
                                                        <span class="text-[9px] text-slate-400">(0.5%)</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-400">
                                        <span>Total Porsi Layanan</span>
                                        <span class="font-bold font-mono text-slate-700">185 User</span>
                                    </div>
                                </div>

                            </div>

                            <!-- Row 2: 3 Kotak Data (Sebaran Wilayah, Status OLT Live, Data Pelanggan Baru) -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-3.5">
                                
                                <!-- 3. Sebaran User per Wilayah (md:col-span-4) (100% Persis Gambar 2 Asli) -->
                                <div class="md:col-span-4 lg:col-span-4 p-3 sm:p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2 sm:pb-1.5">
                                            <div class="min-w-0">
                                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2 leading-tight">
                                                    <span class="w-2 h-2 rounded-full bg-cyan-500 shrink-0"></span>
                                                    <span>Sebaran User per Wilayah</span>
                                                </h4>
                                                <p class="text-[10px] sm:text-[9.5px] text-slate-400 mt-0.5 leading-snug">
                                                    Distribusi per kota pasang.
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-1 px-2 py-0.5 rounded-xl bg-sky-50 shadow-2xs">
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                                <span class="text-[8.5px] uppercase font-bold text-sky-600">&bull; TOTAL</span>
                                                <span class="text-xs font-black font-mono text-slate-900">1,744</span>
                                            </div>
                                        </div>

                                        <!-- Bar Chart SVG (Persis Gambar 2 Asli) -->
                                        <div class="pt-2 px-2">
                                            <svg class="w-full h-[140px]" viewBox="0 0 340 140">
                                                <!-- Kota BDG (960, 401) -->
                                                <text x="50" y="22" font-size="9" font-family="monospace" font-weight="bold" fill="#64748b" text-anchor="middle">960</text>
                                                <rect x="38" y="27" width="24" height="90" rx="3" fill="#2563eb" />
                                                <text x="78" y="62" font-size="9" font-family="monospace" font-weight="bold" fill="#64748b" text-anchor="middle">401</text>
                                                <rect x="66" y="67" width="24" height="50" rx="3" fill="#10b981" />

                                                <!-- Kab BDG (744, 189) -->
                                                <text x="170" y="40" font-size="9" font-family="monospace" font-weight="bold" fill="#64748b" text-anchor="middle">744</text>
                                                <rect x="158" y="45" width="24" height="72" rx="3" fill="#2563eb" />
                                                <text x="198" y="85" font-size="9" font-family="monospace" font-weight="bold" fill="#64748b" text-anchor="middle">189</text>
                                                <rect x="186" y="90" width="24" height="27" rx="3" fill="#10b981" />

                                                <!-- Lainnya (40, 3) -->
                                                <text x="270" y="105" font-size="9" font-family="monospace" font-weight="bold" fill="#64748b" text-anchor="middle">40</text>
                                                <rect x="258" y="110" width="24" height="7" rx="2" fill="#2563eb" />
                                                <text x="298" y="112" font-size="9" font-family="monospace" font-weight="bold" fill="#64748b" text-anchor="middle">3</text>
                                                <rect x="286" y="114" width="24" height="3" rx="1" fill="#10b981" />

                                                <!-- Baseline -->
                                                <line x1="15" y1="117" x2="325" y2="117" stroke="#e2e8f0" stroke-width="1" />

                                                <!-- Labels -->
                                                <text x="64" y="132" font-size="9" font-weight="bold" fill="#475569" text-anchor="middle">KOTA BDG</text>
                                                <text x="184" y="132" font-size="9" font-weight="bold" fill="#475569" text-anchor="middle">KAB. BDG</text>
                                                <text x="284" y="132" font-size="9" font-weight="bold" fill="#475569" text-anchor="middle">LAINNYA</text>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                                <span class="font-medium text-slate-700">Total</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span class="font-medium text-slate-700">Aktif</span>
                                            </span>
                                        </div>
                                        <span class="font-bold text-blue-600">Data Pelanggan &rarr;</span>
                                    </div>
                                </div>

                                <!-- 4. Status OLT Live (md:col-span-4) (100% Persis Gambar 2 Asli) -->
                                <div class="md:col-span-4 lg:col-span-4 p-3 sm:p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2 sm:pb-1.5">
                                            <div class="min-w-0">
                                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2 leading-tight">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                                    <span>Status OLT Live</span>
                                                </h4>
                                                <p class="text-[10px] sm:text-[9.5px] text-slate-400 mt-0.5 leading-snug">
                                                    Status konektivitas OLT real-time.
                                                </p>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-[8.5px]">&bull; LIVE PING</span>
                                        </div>

                                        <!-- OLT Status List (3 OLT Up) -->
                                        <div class="pt-2 space-y-2">
                                            <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span class="font-bold text-[10.5px] text-slate-800">OLT KAYU AGUNG</span>
                                                </div>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>UP</span>
                                                </span>
                                            </div>

                                            <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span class="font-bold text-[10.5px] text-slate-800">OLT BABAKAN TAROGONG</span>
                                                </div>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>UP</span>
                                                </span>
                                            </div>

                                            <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span class="font-bold text-[10.5px] text-slate-800">OLT SOREANG</span>
                                                </div>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>UP</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-400">
                                        <span class="flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Total: <strong class="text-slate-700 font-mono">3 OLT</strong></span>
                                        </span>
                                        <span class="font-bold text-blue-600">Data OLT &rarr;</span>
                                    </div>
                                </div>

                                <!-- 5. Data Pelanggan Baru (md:col-span-4, 5 User) (100% Persis Gambar 2 Asli) -->
                                <div class="md:col-span-4 lg:col-span-4 p-3 sm:p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2 sm:pb-1.5">
                                            <div class="min-w-0">
                                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2 leading-tight">
                                                    <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                                                    <span>Data Pelanggan Baru</span>
                                                </h4>
                                                <p class="text-[10px] sm:text-[9.5px] text-slate-400 mt-0.5 leading-snug">5 pendaftaran user terkini (2026).</p>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-xl bg-indigo-50 text-indigo-700 font-bold text-[8.5px]">&bull; TERKINI 5 User</span>
                                        </div>

                                        <div class="pt-1.5 space-y-1 text-xs">
                                            <div class="flex justify-between p-1 rounded-lg bg-slate-50/80 text-[10px]">
                                                <span class="font-semibold text-slate-800">1 &nbsp; SIDIK MUTAKIN</span>
                                                <span class="text-blue-600 font-mono font-bold">260831056</span>
                                            </div>
                                            <div class="flex justify-between p-1 rounded-lg bg-slate-50/80 text-[10px]">
                                                <span class="font-semibold text-slate-800">2 &nbsp; SARAH FAUZI</span>
                                                <span class="text-blue-600 font-mono font-bold">260831055</span>
                                            </div>
                                            <div class="flex justify-between p-1 rounded-lg bg-slate-50/80 text-[10px]">
                                                <span class="font-semibold text-slate-800">3 &nbsp; LINAWATI</span>
                                                <span class="text-blue-600 font-mono font-bold">260831054</span>
                                            </div>
                                            <div class="flex justify-between p-1 rounded-lg bg-slate-50/80 text-[10px]">
                                                <span class="font-semibold text-slate-800">4 &nbsp; LINAWATI</span>
                                                <span class="text-blue-600 font-mono font-bold">260831053</span>
                                            </div>
                                            <div class="flex justify-between p-1 rounded-lg bg-slate-50/80 text-[10px]">
                                                <span class="font-semibold text-slate-800">5 &nbsp; SITI SAODAH</span>
                                                <span class="text-blue-600 font-mono font-bold">260831052</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-400">
                                        <span>5 Pendaftaran Baru</span>
                                        <span class="font-bold text-blue-600">Lihat Semua &rarr;</span>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </main>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- HARDWARE-ACCELERATED FROSTED GLASS & AMBIENT BLUR OVERLAY                 -->
    <!-- Menggunakan GPU opacity transition untuk menghilangkan beban CPU raster    -->
    <!-- ========================================================================= -->
    <div class="fixed inset-0 pointer-events-none z-[1] backdrop-blur-[7px] bg-[#061d28]/35 transition-opacity duration-250 ease-out will-change-[opacity]"
         :class="isEntering ? 'opacity-0' : 'opacity-100'"></div>

    <!-- Soft Ambient Halo behind Login Card -->
    <div class="fixed inset-0 flex items-center justify-center pointer-events-none z-[2] transition-opacity duration-200 ease-out will-change-[opacity]"
         :class="isEntering ? 'opacity-0' : 'opacity-100'">
        <div class="w-[500px] h-[500px] bg-gradient-to-tr from-cyan-500/20 via-teal-400/15 to-sky-600/15 rounded-full blur-[110px]"></div>
    </div>

    <!-- ========================================================================= -->
    <!-- FOREFRONT: CENTERED FROSTED GLASS LOGIN CARD                             -->
    <!-- ========================================================================= -->
    <div class="relative z-10 w-full h-full flex items-center justify-center p-4">
        @yield('content')
    </div>

</body>
</html>
