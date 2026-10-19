<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full w-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) && $title ? ($title . ' | IMSv2 | PT MEDIA SOLUSI NETWORK') : 'IMSv2 | PT MEDIA SOLUSI NETWORK' }}</title>

    <!-- Strict 1-Screen Viewport Scale (Guaranteed No Scroll) -->
    <style>
        html, body {
            height: 100% !important;
            width: 100% !important;
            max-height: 100vh !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #F8FAFC !important;
        }
        html {
            font-size: 13px !important;
        }
        @media (max-width: 639.98px) {
            html {
                font-size: 14.5px !important;
            }
        }
        [x-cloak] { display: none !important; }

        /* Autofill Styling Fix */
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
              setTimeout(() => {
                  window.location.href = redirectUrl || '{{ route('dashboard') }}';
              }, 500);
          }
      }">

    <!-- ========================================================================= -->
    <!-- BACKGROUND: EXACT 1:1 CLONE OF REAL IMS-V2 DASHBOARD (GAMBAR 2 ASLI)      -->
    <!-- ========================================================================= -->
    <div class="fixed inset-0 w-full h-full flex pointer-events-none select-none z-0 overflow-hidden bg-[#F8FAFC]">
        
        <!-- 1. Sidebar Kiri (100% Persis Gambar 2 Asli) -->
        <aside class="w-60 h-full bg-[#061d28] border-r border-[#0d2a38] flex flex-col shrink-0 text-slate-300">
            <!-- Sidebar Header / Logo -->
            <div class="h-16 px-3.5 flex items-center gap-3 border-b border-[#0d2a38]">
                <div class="w-9 h-9 rounded-xl bg-[#0c2f42] border border-cyan-400/30 p-1 shadow-md shadow-cyan-500/20 flex items-center justify-center shrink-0">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                </div>
                <div class="whitespace-nowrap">
                    <span class="font-bold text-white tracking-tight text-base">IMS<span class="text-blue-500 font-extrabold">-V2</span></span>
                    <span class="block text-[9.5px] text-[#94A3B8] font-medium leading-tight">Integrated Management System</span>
                </div>
            </div>

            <!-- Sidebar Nav Links -->
            <div class="flex-1 py-3 px-2.5 space-y-1.5 overflow-hidden">
                <!-- Active Dashboard Pill -->
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-cyan-600 text-white font-semibold text-xs shadow-md shadow-cyan-900/30">
                    <svg class="w-5 h-5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    <span>Dashboard</span>
                </div>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-[#9CA3AF] hover:text-white text-xs font-medium">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <span>Data Pelanggan</span>
                </div>

                <div class="pt-2.5 pb-1 px-3 text-[10px] font-bold text-[#9CA3AF] uppercase tracking-wider flex items-center justify-between">
                    <span>Modul NOC</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                        <span>Tiket</span>
                    </div>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                        <span>Permintaan</span>
                    </div>
                    <svg class="w-4 h-4 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a3 3 0 0 1 2.4-1.1h7.7a3 3 0 0 1 2.4 1.1l2.1 3.45c.6.9.9 1.8.9 2.7"/></svg>
                        <span>OLT</span>
                    </div>
                    <svg class="w-4 h-4 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                        <span>Infrastruktur</span>
                    </div>
                    <svg class="w-4 h-4 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </div>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-[#9CA3AF] text-xs font-medium">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    <span>Pusat Notifikasi</span>
                </div>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-rose-400 text-xs font-medium">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    <span>Keluar</span>
                </div>
            </div>

            <!-- Sidebar Bottom -->
            <div class="p-3.5 border-t border-[#0d2a38] text-[11px] font-bold text-[#38bdf8]">
                IMS-v2
            </div>
        </aside>

        <!-- 2. Area Konten Utama Dashboard (Persis Gambar 2 Asli) -->
        <div class="flex-1 h-full bg-[#F8FAFC] flex flex-col overflow-hidden text-slate-800">
            
            <!-- Top Navbar (100% Persis Gambar 2 Asli) -->
            <header class="h-14 bg-white border-b border-slate-200 px-5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 rounded-lg bg-slate-100 text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800 leading-tight">Dashboard Utama</h2>
                        <p class="text-[9.5px] text-slate-400 font-medium">Integrated Management System &bull; Portal</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Notification Bell with Red Badge "13" (Persis Gambar 2 Asli) -->
                    <div class="relative p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        <span class="absolute -top-1 -right-1.5 px-1 py-0.2 rounded-full text-[8.5px] font-bold text-white bg-rose-600 font-mono shadow-xs">13</span>
                        <span class="absolute -bottom-0.5 -right-0.5 w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    </div>

                    <!-- Moon Icon -->
                    <div class="p-1.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                    </div>

                    <!-- Online Status Pill -->
                    <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-50 border border-slate-200 text-[10px] text-slate-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-bold text-emerald-600">Online</span>
                        <span class="font-mono text-slate-900 font-bold">22:46:26 WIB</span>
                        <span class="text-[9px] text-slate-400 hidden lg:inline">&bull; Min, 18 Okt 2026</span>
                    </div>

                    <!-- Avatar NOC -->
                    <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-[#05404f] to-[#0891b2] flex items-center justify-center font-bold text-xs text-white">
                            N
                        </div>
                        <div class="text-left hidden sm:block pr-1">
                            <span class="block text-[10.5px] font-bold text-slate-900 leading-tight">NOC</span>
                            <span class="block text-[8.5px] text-[#0891b2] font-semibold">NOC</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Body (Persis Gambar 2 Asli) -->
            <div class="flex-1 p-3.5 space-y-3 overflow-hidden">
                
                <!-- Welcome Banner (Deep Oceanic Teal & Cyan Gradient - 100% Persis Gambar 2 Asli) -->
                <div class="rounded-2xl py-2.5 px-4 text-white shadow-sm flex items-center justify-between"
                     style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 text-white font-black text-xs flex items-center justify-center shadow-inner">
                            N
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs sm:text-sm font-extrabold text-white">Halo, NOC! 👋</h3>
                                <span class="px-2 py-0.5 rounded-full text-[8.5px] font-bold uppercase bg-white/15 text-white border border-white/20">NOC</span>
                                <span class="px-2 py-0.5 rounded-full text-[8.5px] font-semibold bg-emerald-400/20 text-emerald-200 border border-emerald-400/30">Online</span>
                            </div>
                            <p class="text-[10px] text-[#c6edf3] mt-0.5">Eksekusi Jaringan, Aktivasi, Suspend, &amp; Terminasi</p>
                        </div>
                    </div>

                    <!-- Right Controls in Banner -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-black/30 border border-white/20 rounded-full p-0.5 text-[10.5px] font-bold">
                            <span class="px-3 py-1 bg-white text-[#032b35] rounded-full shadow-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-[#032b35]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                <span>Grafik Utama</span>
                            </span>
                            <span class="px-3 py-1 text-white/80 flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Data Pendaftaran</span>
                            </span>
                        </div>
                        <div class="px-3 py-1 bg-black/30 border border-white/20 rounded-full text-[10.5px] font-bold text-white flex items-center gap-1">
                            <span>Tahun 2026</span>
                            <span class="text-[9px]">&dtrif;</span>
                        </div>
                        <div class="px-3 py-1 bg-emerald-500 text-white rounded-full text-[10.5px] font-bold shadow-xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                            <span>Status Layanan</span>
                        </div>
                    </div>
                </div>

                <!-- Row 1: Tren Bulanan (7 Cols) & Donut Bandwidth (5 Cols) (100% Persis Gambar 2 Asli) -->
                <div class="grid grid-cols-12 gap-3.5">
                    
                    <!-- 1. Grafik Tren User Baru per Bulan (col-span-7) -->
                    <div class="col-span-7 p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                        <span>Tren Pertumbuhan User Baru per Bulan</span>
                                    </h4>
                                    <p class="text-[9.5px] text-slate-400 mt-0.5">
                                        Registrasi vs aktivasi selesai sepanjang 2026.
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="flex items-center gap-1 px-2 py-0.5 rounded-xl bg-blue-50 text-[8px] font-bold uppercase tracking-wider text-blue-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                        <span>REGISTRASI</span>
                                        <span class="text-xs font-black font-mono text-slate-900 ml-0.5">185</span>
                                    </div>
                                    <div class="flex items-center gap-1 px-2 py-0.5 rounded-xl bg-emerald-50 text-[8px] font-bold uppercase tracking-wider text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>AKTIF ONLINE</span>
                                        <span class="text-xs font-black font-mono text-slate-900 ml-0.5">120</span>
                                    </div>
                                </div>
                            </div>

                            <!-- SVG ApexCharts Accurate Clone with Y-axis & Points (100% Persis Gambar 2) -->
                            <div class="w-full h-36 pt-1 flex items-center">
                                <!-- Y Axis Labels -->
                                <div class="w-6 h-full flex flex-col justify-between text-[9px] font-mono font-medium text-slate-400 py-1 text-right pr-1">
                                    <span>30</span>
                                    <span>20</span>
                                    <span>10</span>
                                    <span>0</span>
                                </div>
                                <!-- Chart Area with Grid Lines and Exact Splines -->
                                <div class="flex-1 h-full relative">
                                    <svg class="w-full h-full" viewBox="0 0 540 120" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="chartGreenArea" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.35"/>
                                                <stop offset="90%" stop-color="#10b981" stop-opacity="0.02"/>
                                            </linearGradient>
                                        </defs>

                                        <!-- Horizontal Grid Dashes -->
                                        <line x1="0" y1="8" x2="540" y2="8" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4,4"/>
                                        <line x1="0" y1="42" x2="540" y2="42" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4,4"/>
                                        <line x1="0" y1="76" x2="540" y2="76" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4,4"/>
                                        <line x1="0" y1="110" x2="540" y2="110" stroke="#e2e8f0" stroke-width="1"/>

                                        <!-- Green Area Fill -->
                                        <path d="M 15,70 C 50,68 85,74 125,78 C 165,82 205,92 245,86 C 285,82 325,74 365,60 C 405,48 445,82 485,110 L 530,110 L 530,110 L 15,110 Z" 
                                              fill="url(#chartGreenArea)"/>

                                        <!-- Blue Curve (Registrasi) -->
                                        <path d="M 15,48 C 50,42 85,60 125,64 C 165,74 205,86 245,78 C 285,74 325,58 365,30 C 405,40 445,78 485,110 L 530,110" 
                                              fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round"/>

                                        <!-- Green Curve (Aktif Online) -->
                                        <path d="M 15,70 C 50,68 85,74 125,78 C 165,82 205,92 245,86 C 285,82 325,74 365,60 C 405,48 445,82 485,110 L 530,110" 
                                              fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>

                                        <!-- Marker Points Blue -->
                                        <circle cx="15" cy="48" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="50" cy="42" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="125" cy="64" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="205" cy="86" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="285" cy="74" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="365" cy="30" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="445" cy="78" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="485" cy="110" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="530" cy="110" r="3" fill="#2563eb" stroke="#fff" stroke-width="1.5"/>

                                        <!-- Marker Points Green -->
                                        <circle cx="15" cy="70" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="50" cy="68" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="125" cy="78" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="205" cy="92" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="285" cy="82" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="365" cy="60" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="445" cy="82" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="485" cy="110" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                        <circle cx="530" cy="110" r="3" fill="#10b981" stroke="#fff" stroke-width="1.5"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- X Axis Months -->
                            <div class="flex justify-between text-[9px] font-semibold text-slate-400 pl-7 pr-2 pt-0.5">
                                <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span><span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span><span>Nov</span><span>Des</span>
                            </div>
                        </div>

                        <!-- Footer Note (Persis Gambar 2 Asli) -->
                        <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-500">
                            <div class="flex items-center gap-2">
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

                    <!-- 2. Donut Kategori Bandwidth (col-span-5) (100% Persis Gambar 2 Asli) -->
                    <div class="col-span-5 p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="border-b border-slate-100 pb-1.5">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    <span>Distribusi Kategori Bandwidth</span>
                                </h4>
                                <p class="text-[9.5px] text-slate-400 mt-0.5">
                                    Pangsa paket layanan yang dipilih pelanggan.
                                </p>
                            </div>

                            <!-- Donut + 6 Categorized Items (Side-by-side) -->
                            <div class="pt-2 flex items-center gap-3">
                                <!-- Donut Chart SVG -->
                                <div class="w-36 shrink-0 relative flex items-center justify-center">
                                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="38" stroke="#e2e8f0" stroke-width="13" fill="none"/>
                                        <!-- UP TO NEW (93.5%) -->
                                        <circle cx="50" cy="50" r="38" stroke="#0284c7" stroke-width="13" stroke-dasharray="223 240" fill="none"/>
                                        <!-- CORPORATE (2.7%) -->
                                        <circle cx="50" cy="50" r="38" stroke="#00b074" stroke-width="13" stroke-dasharray="7 240" stroke-dashoffset="-223" fill="none"/>
                                        <!-- SMALL OFFICE (1.6%) -->
                                        <circle cx="50" cy="50" r="38" stroke="#8b5cf6" stroke-width="13" stroke-dasharray="4 240" stroke-dashoffset="-230" fill="none"/>
                                        <!-- BROADBAND FREE (1.1%) -->
                                        <circle cx="50" cy="50" r="38" stroke="#f59e0b" stroke-width="13" stroke-dasharray="3 240" stroke-dashoffset="-234" fill="none"/>
                                        <!-- BROADBAND NEW (0.5%) -->
                                        <circle cx="50" cy="50" r="38" stroke="#ec4899" stroke-width="13" stroke-dasharray="2 240" stroke-dashoffset="-237" fill="none"/>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                        <span class="text-[8.5px] text-slate-400 font-medium">Total User</span>
                                        <span class="text-sm font-black text-slate-900 font-mono">185</span>
                                    </div>
                                </div>

                                <!-- 6 Detailed Items (100% Persis Gambar 2) -->
                                <div class="flex-1 min-w-0 space-y-0.5">
                                    <div class="flex items-center justify-between text-[8px] py-0.5 px-1 rounded">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#0284c7] shrink-0"></span>
                                            <span class="font-semibold text-slate-700 truncate">UP TO NEW</span>
                                        </div>
                                        <span class="font-mono text-slate-900 font-bold shrink-0">173 (93.5%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[8px] py-0.5 px-1 rounded">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#00b074] shrink-0"></span>
                                            <span class="font-semibold text-slate-700 truncate">CORPORATE</span>
                                        </div>
                                        <span class="font-mono text-slate-900 font-bold shrink-0">5 (2.7%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[8px] py-0.5 px-1 rounded">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#8b5cf6] shrink-0"></span>
                                            <span class="font-semibold text-slate-700 truncate">SMALL OFFICE HOME OFFICE</span>
                                        </div>
                                        <span class="font-mono text-slate-900 font-bold shrink-0">3 (1.6%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[8px] py-0.5 px-1 rounded">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#f59e0b] shrink-0"></span>
                                            <span class="font-semibold text-slate-700 truncate">BROADBAND FREE</span>
                                        </div>
                                        <span class="font-mono text-slate-900 font-bold shrink-0">2 (1.1%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[8px] py-0.5 px-1 rounded">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#ec4899] shrink-0"></span>
                                            <span class="font-semibold text-slate-700 truncate">BROADBAND NEW</span>
                                        </div>
                                        <span class="font-mono text-slate-900 font-bold shrink-0">1 (0.5%)</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[8px] py-0.5 px-1 rounded">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#06b6d4] shrink-0"></span>
                                            <span class="font-semibold text-slate-700 truncate">LASTMILE</span>
                                        </div>
                                        <span class="font-mono text-slate-900 font-bold shrink-0">1 (0.5%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Note (Persis Gambar 2 Asli) -->
                        <div class="mt-1 pt-1 border-t border-slate-100 flex items-center justify-between text-[8px] text-slate-400">
                            <span>Total Porsi Layanan</span>
                            <span class="font-bold font-mono text-slate-700">185 User</span>
                        </div>
                    </div>
                </div>

                <!-- Row 2: 3 Cards (Sebaran Wilayah, Status OLT, Data Pelanggan Baru) (100% Persis Gambar 2 Asli) -->
                <div class="grid grid-cols-12 gap-3.5">
                    
                    <!-- 3. Grafik Sebaran User per Wilayah (col-span-4) -->
                    <div class="col-span-4 p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                        <span>Sebaran User per Wilayah</span>
                                    </h4>
                                    <p class="text-[9.5px] text-slate-400 mt-0.5">Distribusi per kota pasang.</p>
                                </div>
                                <span class="px-2 py-0.5 rounded-xl bg-sky-50 text-sky-700 font-bold text-[8.5px] font-mono">&bull; TOTAL 1,744</span>
                            </div>

                            <!-- Bar Graphic (Persis Gambar 2 Asli) -->
                            <div class="flex items-end justify-around h-24 pt-3 border-b border-slate-100">
                                <!-- KOTA BDG -->
                                <div class="flex items-end gap-1">
                                    <div class="flex flex-col items-center">
                                        <span class="text-[8.5px] font-bold text-slate-700 font-mono">960</span>
                                        <div class="w-5 h-16 rounded-t bg-blue-600"></div>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="text-[8.5px] font-bold text-slate-700 font-mono">401</span>
                                        <div class="w-5 h-9 rounded-t bg-emerald-500"></div>
                                    </div>
                                </div>
                                <!-- KAB. BDG -->
                                <div class="flex items-end gap-1">
                                    <div class="flex flex-col items-center">
                                        <span class="text-[8.5px] font-bold text-slate-700 font-mono">744</span>
                                        <div class="w-5 h-14 rounded-t bg-blue-600"></div>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="text-[8.5px] font-bold text-slate-700 font-mono">309</span>
                                        <div class="w-5 h-7 rounded-t bg-emerald-500"></div>
                                    </div>
                                </div>
                                <!-- LAINNYA -->
                                <div class="flex items-end gap-1">
                                    <div class="flex flex-col items-center">
                                        <span class="text-[8.5px] font-bold text-slate-700 font-mono">40</span>
                                        <div class="w-5 h-3 rounded-t bg-blue-600"></div>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="text-[8.5px] font-bold text-slate-700 font-mono">3</span>
                                        <div class="w-5 h-1 rounded-t bg-emerald-500"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-around text-[8.5px] font-bold text-slate-500 mt-1">
                                <span>KOTA BDG</span>
                                <span>KAB. BDG</span>
                                <span>LAINNYA</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span><span>Total</span></span>
                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Aktif</span></span>
                            </div>
                            <span class="font-bold text-blue-600">Data Pelanggan &rarr;</span>
                        </div>
                    </div>

                    <!-- 4. Status OLT Live (col-span-4) (100% Persis Gambar 2 Asli) -->
                    <div class="col-span-4 p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>Status OLT Live</span>
                                    </h4>
                                    <p class="text-[9.5px] text-slate-400 mt-0.5">Status konektivitas OLT real-time.</p>
                                </div>
                                <span class="px-2 py-0.5 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-[8.5px]">&bull; LIVE PING</span>
                            </div>

                            <div class="pt-2 space-y-1.5">
                                <div class="p-2 rounded-xl bg-slate-50/80 border border-slate-200/60 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="font-bold text-[10.5px] text-slate-800">OLT KAYU AGUNG</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[8.5px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">&bull; UP</span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-50/80 border border-slate-200/60 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="font-bold text-[10.5px] text-slate-800">OLT BABAKAN TAROGONG</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[8.5px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">&bull; UP</span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-50/80 border border-slate-200/60 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="font-bold text-[10.5px] text-slate-800">OLT SOREANG</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[8.5px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-300">&bull; UP</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-400">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Total: <strong class="text-slate-700 font-mono">3 OLT</strong></span>
                            </span>
                            <span class="font-bold text-blue-600">Data OLT &rarr;</span>
                        </div>
                    </div>

                    <!-- 5. Data Pelanggan Baru (col-span-4, 5 User) (100% Persis Gambar 2 Asli) -->
                    <div class="col-span-4 p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 tracking-tight flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                        <span>Data Pelanggan Baru</span>
                                    </h4>
                                    <p class="text-[9.5px] text-slate-400 mt-0.5">5 pendaftaran user terkini (2026).</p>
                                </div>
                                <span class="px-2 py-0.5 rounded-xl bg-indigo-50 text-indigo-700 font-bold text-[8.5px]">&bull; TERKINI 5 User</span>
                            </div>

                            <!-- 5 Users List (Persis Gambar 2 Asli) -->
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

                        <!-- Footer -->
                        <div class="mt-1 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-400">
                            <span>5 Pendaftaran Baru</span>
                            <span class="font-bold text-blue-600">Lihat Semua &rarr;</span>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- HARDWARE-ACCELERATED FROSTED GLASS & AMBIENT BLUR OVERLAY                 -->
    <!-- ========================================================================= -->
    <div class="fixed inset-0 pointer-events-none z-[1] backdrop-blur-[7px] bg-[#061d28]/35 transition-opacity duration-500 ease-out will-change-[opacity]"
         :class="isEntering ? 'opacity-0' : 'opacity-100'"></div>

    <!-- Soft Ambient Halo behind Login Card -->
    <div class="fixed inset-0 flex items-center justify-center pointer-events-none z-[2] transition-opacity duration-300 ease-out will-change-[opacity]"
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
