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
        }
        [x-cloak] { display: none !important; }

        /* Autofill Styling: Menjaga input tetap dark glass saat browser auto-fill */
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
<body class="h-full w-full font-sans antialiased text-slate-100 bg-[#061d28] relative selection:bg-cyan-500 selection:text-white overflow-hidden">

    <!-- ======================================================== -->
    <!-- BACKGROUND: REAL IMS-V2 DASHBOARD (Gambar 2) DIBLUR     -->
    <!-- ======================================================== -->
    <div class="fixed inset-0 w-full h-full flex pointer-events-none select-none z-0 overflow-hidden"
         style="filter: blur(6px) brightness(0.75) saturate(1.15); transform: scale(1.02);">
        
        <!-- Sidebar Kiri (Persis Gambar 2 IMS-V2) -->
        <aside class="w-60 h-full bg-[#061d28] border-r border-[#0d2a38] flex flex-col shrink-0 text-slate-300">
            <!-- Sidebar Header / Logo -->
            <div class="h-16 px-4 flex items-center gap-3 border-b border-[#0d2a38]">
                <div class="w-9 h-9 rounded-xl bg-[#0c2f42] border border-cyan-400/30 p-1 shadow-md shadow-cyan-500/20 flex items-center justify-center shrink-0">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="font-bold text-white tracking-tight text-base">IMS<span class="text-cyan-400 font-extrabold">-V2</span></span>
                    <span class="block text-[9px] text-slate-400 font-medium">Integrated Management System</span>
                </div>
            </div>

            <!-- Sidebar Nav Links -->
            <div class="flex-1 py-4 px-3 space-y-1 overflow-hidden">
                <!-- Active Dashboard Pill -->
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-cyan-600 text-white font-semibold text-xs shadow-md shadow-cyan-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Dashboard</span>
                </div>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-white text-xs font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Data Pelanggan</span>
                </div>

                <div class="pt-3 pb-1 px-3 text-[10px] font-bold text-cyan-400/80 uppercase tracking-wider">
                    Modul NOC
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-400 text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        <span>Tiket</span>
                    </div>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-400 text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Permintaan</span>
                    </div>
                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-400 text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                        <span>OLT</span>
                    </div>
                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl text-slate-400 text-xs font-medium">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                        <span>Infrastruktur</span>
                    </div>
                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 text-xs font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>Pusat Notifikasi</span>
                </div>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 text-xs font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar</span>
                </div>
            </div>

            <!-- Sidebar Bottom -->
            <div class="p-4 border-t border-[#0d2a38] text-[11px] font-bold text-cyan-400">
                IMS-v2
            </div>
        </aside>

        <!-- Area Konten Dashboard (Kanan) -->
        <main class="flex-1 h-full bg-[#f8fafc] flex flex-col overflow-hidden text-slate-800">
            
            <!-- Top Navbar (Persis Gambar 2) -->
            <header class="h-14 bg-white border-b border-slate-200 px-5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 rounded-lg bg-slate-100 text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 leading-tight">Dashboard Utama</h2>
                        <p class="text-[10px] text-slate-400 font-medium">Integrated Management System &bull; Portal</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </div>
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Online 22:06:28 WIB</span>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-cyan-700 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                        NOC
                    </div>
                </div>
            </header>

            <!-- Dashboard Body -->
            <div class="flex-1 p-5 space-y-4 overflow-hidden">
                
                <!-- Welcome Banner (Deep Oceanic Teal & Cyan Gradient - Persis Gambar 2) -->
                <div class="rounded-2xl p-4 text-white shadow-sm flex items-center justify-between"
                     style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 text-white font-black text-sm flex items-center justify-center">
                            N
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-extrabold text-white">Halo, NOC! 👋</h3>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-white/20 text-white">NOC</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-400/25 text-emerald-200 border border-emerald-400/30">Online</span>
                            </div>
                            <p class="text-[11px] text-cyan-100/90 mt-0.5">Eksekusi Jaringan, Aktivasi, Suspend, & Terminasi</p>
                        </div>
                    </div>

                    <!-- Right Controls in Banner -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-black/30 border border-white/20 rounded-full p-1 text-[11px] font-bold">
                            <span class="px-3 py-1 bg-white text-[#032b35] rounded-full shadow-xs">Grafik Utama</span>
                            <span class="px-3 py-1 text-white/80">Data Pendaftaran</span>
                        </div>
                        <div class="px-3 py-1.5 bg-black/30 border border-white/20 rounded-full text-[11px] font-bold text-white">
                            Tahun 2026 &dtrif;
                        </div>
                        <div class="px-3 py-1.5 bg-emerald-500 text-white rounded-full text-[11px] font-bold shadow-xs">
                            &bull; Status Layanan
                        </div>
                    </div>
                </div>

                <!-- Row 1: Line Chart & Donut Chart (Persis Gambar 2) -->
                <div class="grid grid-cols-12 gap-4">
                    <!-- Tren Pertumbuhan User Baru per Bulan -->
                    <div class="col-span-8 bg-white border border-slate-200/90 rounded-2xl p-4 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    <span>Tren Pertumbuhan User Baru per Bulan</span>
                                </h4>
                                <p class="text-[10px] text-slate-400">Registrasi vs aktivasi selesai sepanjang 2026</p>
                            </div>
                            <div class="flex items-center gap-3 text-[10px] font-bold">
                                <span class="text-blue-600">&bull; REGISTRASI 185</span>
                                <span class="text-emerald-500">&bull; AKTIF ONLINE 120</span>
                            </div>
                        </div>

                        <!-- SVG Line Graph Curves (Persis lekukan kurva Gambar 2) -->
                        <div class="w-full h-36 my-2 relative">
                            <svg class="w-full h-full" viewBox="0 0 600 120" preserveAspectRatio="none">
                                <!-- Blue Line (Registrasi) -->
                                <path d="M 10,75 C 60,60 100,70 150,85 C 200,98 250,90 300,75 C 350,60 400,30 450,70 C 500,105 550,110 590,110" 
                                      fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
                                <!-- Green Line (Aktif Online) -->
                                <path d="M 10,90 C 60,88 100,85 150,95 C 200,108 250,98 300,90 C 350,90 400,60 450,95 C 500,115 550,115 590,115" 
                                      fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <div class="flex justify-between text-[9px] font-semibold text-slate-400 border-t border-slate-100 pt-1">
                            <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span><span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span><span>Nov</span><span>Des</span>
                        </div>
                    </div>

                    <!-- Distribusi Kategori Bandwidth (Donut Chart) -->
                    <div class="col-span-4 bg-white border border-slate-200/90 rounded-2xl p-4 shadow-2xs flex items-center justify-between">
                        <!-- Donut Graphic -->
                        <div class="relative w-28 h-28 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="38" stroke="#e2e8f0" stroke-width="14" fill="none"/>
                                <circle cx="50" cy="50" r="38" stroke="#0284c7" stroke-width="14" stroke-dasharray="190 240" fill="none"/>
                                <circle cx="50" cy="50" r="38" stroke="#10b981" stroke-width="14" stroke-dasharray="25 240" stroke-dashoffset="-192" fill="none"/>
                                <circle cx="50" cy="50" r="38" stroke="#8b5cf6" stroke-width="14" stroke-dasharray="12 240" stroke-dashoffset="-218" fill="none"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-[9px] text-slate-400 font-medium">Total User</span>
                                <span class="text-sm font-black text-slate-800">185</span>
                            </div>
                        </div>

                        <!-- Legend Items -->
                        <div class="space-y-1 text-[9.5px]">
                            <div class="flex items-center gap-1.5 font-bold text-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-600"></span>
                                <span>UP TO NEW (93.5%)</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>CORPORATE (2.7%)</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                <span>SMALL OFFICE (1.6%)</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                <span>BROADBAND FREE (1.1%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Sebaran User per Wilayah, Status OLT Live, Data Pelanggan Baru -->
                <div class="grid grid-cols-12 gap-4">
                    <!-- Sebaran User per Wilayah (Bar Chart) -->
                    <div class="col-span-4 bg-white border border-slate-200/90 rounded-2xl p-4 shadow-2xs">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                <span>Sebaran User per Wilayah</span>
                            </h4>
                            <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-bold text-[9px]">TOTAL 1,744</span>
                        </div>
                        <div class="flex items-end justify-around h-24 pt-4 border-b border-slate-100">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] font-bold text-slate-600">960</span>
                                <div class="w-6 h-16 rounded-t bg-blue-600"></div>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] font-bold text-slate-600">401</span>
                                <div class="w-6 h-8 rounded-t bg-cyan-500"></div>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] font-bold text-slate-600">744</span>
                                <div class="w-6 h-14 rounded-t bg-blue-600"></div>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] font-bold text-slate-600">309</span>
                                <div class="w-6 h-6 rounded-t bg-emerald-500"></div>
                            </div>
                        </div>
                        <div class="flex justify-around text-[9px] font-bold text-slate-500 mt-1">
                            <span>KOTA BDG</span>
                            <span>KAB. BDG</span>
                            <span>LAINNYA</span>
                        </div>
                    </div>

                    <!-- Status OLT Live -->
                    <div class="col-span-4 bg-white border border-slate-200/90 rounded-2xl p-4 shadow-2xs">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Status OLT Live</span>
                            </h4>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[9px]">&bull; LIVE PING</span>
                        </div>
                        <div class="space-y-2">
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs font-semibold text-slate-700">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>OLT KAYU AGUNG</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">&bull; UP</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs font-semibold text-slate-700">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>OLT BABAKAN TAROGONG</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">&bull; UP</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs font-semibold text-slate-700">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>OLT SOREANG</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">&bull; UP</span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Pelanggan Baru -->
                    <div class="col-span-4 bg-white border border-slate-200/90 rounded-2xl p-4 shadow-2xs">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                <span>Data Pelanggan Baru</span>
                            </h4>
                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold text-[9px]">&bull; TERKINI 5 User</span>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between p-1.5 rounded-lg bg-slate-50 text-slate-700 font-semibold text-[11px]">
                                <span>1. SIDIK MUTAKIN</span>
                                <span class="text-blue-600 font-mono text-[10px]">260831056</span>
                            </div>
                            <div class="flex justify-between p-1.5 rounded-lg bg-slate-50 text-slate-700 font-semibold text-[11px]">
                                <span>2. SARAH FAUZI</span>
                                <span class="text-blue-600 font-mono text-[10px]">260831055</span>
                            </div>
                            <div class="flex justify-between p-1.5 rounded-lg bg-slate-50 text-slate-700 font-semibold text-[11px]">
                                <span>3. LINAWATI</span>
                                <span class="text-blue-600 font-mono text-[10px]">260831054</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

    </div>

    <!-- Dark Translucent Overlay to ensure perfect contrast for the Glass Login Card -->
    <div class="fixed inset-0 bg-[#061d28]/40 pointer-events-none z-[1]"></div>

    <!-- Soft Ambient Halo behind Login Card -->
    <div class="fixed inset-0 flex items-center justify-center pointer-events-none z-[2]">
        <div class="w-[500px] h-[500px] bg-gradient-to-tr from-cyan-500/25 via-teal-400/20 to-sky-600/20 rounded-full blur-[110px]"></div>
    </div>

    <!-- ======================================================== -->
    <!-- FOREFRONT: CENTERED FROSTED GLASS LOGIN CARD             -->
    <!-- ======================================================== -->
    <div class="relative z-10 w-full h-full flex items-center justify-center p-4">
        @yield('content')
    </div>

</body>
</html>
