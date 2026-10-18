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

        /* Autofill Styling Fix: Menjaga input tetap bertema dark glass saat browser auto-fill credential */
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
<body class="h-full w-full font-sans antialiased text-slate-100 bg-[#06080f] flex flex-col relative selection:bg-cyan-500 selection:text-white overflow-hidden p-0 sm:p-2 md:p-3">

    <!-- Ambient Glowing Light Sources Behind Window -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -bottom-36 left-1/2 -translate-x-1/2 w-[800px] md:w-[1200px] h-[400px] bg-gradient-to-t from-purple-600/30 via-indigo-600/15 to-transparent rounded-full blur-[100px]"></div>
        <div class="absolute -top-32 right-10 w-[500px] h-[500px] bg-cyan-500/15 rounded-full blur-[120px]"></div>
        <div class="absolute -top-32 left-10 w-[450px] h-[450px] bg-purple-700/15 rounded-full blur-[110px]"></div>
    </div>

    <!-- Application / Browser Window Mockup Frame (Expanded Full-Width & Full-Height) -->
    <div class="w-full h-full flex-1 rounded-none sm:rounded-2xl border-0 sm:border border-white/10 bg-[#0a0d16] shadow-[0_20px_70px_-15px_rgba(0,0,0,0.9)] overflow-hidden relative z-10 flex flex-col ring-1 ring-white/5">
        
        <!-- Window Chrome / Titlebar (macOS Style: Red, Yellow, Green Dots) -->
        <div class="h-10 bg-[#0d121f]/95 border-b border-white/[0.08] px-4 flex items-center justify-between shrink-0 select-none backdrop-blur-md">
            <!-- Traffic Light Buttons & Navigation Controls -->
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#ff5f56] shadow-[0_0_6px_rgba(255,95,86,0.6)] border border-[#e0443e]"></span>
                <span class="w-3 h-3 rounded-full bg-[#ffbd2e] shadow-[0_0_6px_rgba(255,189,46,0.6)] border border-[#dea123]"></span>
                <span class="w-3 h-3 rounded-full bg-[#27c93f] shadow-[0_0_6px_rgba(39,201,63,0.6)] border border-[#1aab29]"></span>

                <div class="hidden sm:flex items-center gap-2.5 ml-3 pl-3 border-l border-white/10 text-slate-400">
                    <svg class="w-3.5 h-3.5 hover:text-slate-200 transition-colors cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <svg class="w-3.5 h-3.5 hover:text-slate-200 transition-colors cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <svg class="w-3 h-3 hover:text-slate-200 transition-colors cursor-pointer ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>

            <!-- Central Mockup URL / Internal Host Pill -->
            <div class="flex items-center gap-2 px-3.5 py-1 rounded-lg bg-white/[0.04] border border-white/[0.07] text-[11px] font-mono text-slate-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>ims.ptmsn.co.id/datacenter</span>
            </div>

            <!-- Right Window Indicators -->
            <div class="flex items-center gap-2.5 text-slate-400 text-xs">
                <span class="hidden sm:inline-block text-[10px] font-mono uppercase tracking-wider text-slate-400/80">IMS Core v2.4</span>
                <div class="w-2 h-2 rounded-full bg-purple-400/80 shadow-[0_0_8px_rgba(168,85,247,0.7)]"></div>
            </div>
        </div>

        <!-- Window Body: Blurred NOC Dashboard Background + Centered Frosted Glass Card -->
        <div class="relative flex-1 w-full overflow-hidden flex">

            <!-- ======================================================== -->
            <!-- BLURRED NOC DASHBOARD BACKGROUND (Full Width & Height)   -->
            <!-- ======================================================== -->
            <div class="absolute inset-0 w-full h-full flex pointer-events-none select-none z-0 overflow-hidden"
                 style="filter: blur(14px) brightness(0.65) saturate(1.3); transform: scale(1.04);">
                
                <!-- Left Dark Sidebar Mockup -->
                <div class="w-16 h-full bg-[#080c16]/95 border-r border-white/[0.06] flex flex-col items-center py-4 gap-5 shrink-0">
                    <!-- Logo Circle in Sidebar -->
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-500 to-purple-600 p-0.5 flex items-center justify-center shadow-lg shadow-purple-950/40">
                        <div class="w-full h-full bg-[#0a0d17] rounded-[10px] flex items-center justify-center p-1">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="MSN Logo" class="w-full h-full object-contain">
                        </div>
                    </div>

                    <!-- Sidebar Nav Icons -->
                    <div class="flex flex-col items-center gap-4 mt-2 text-slate-400">
                        <div class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-300 flex items-center justify-center border border-purple-500/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </div>
                        <div class="w-8 h-8 rounded-xl hover:bg-white/5 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div class="w-8 h-8 rounded-xl hover:bg-white/5 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                        </div>
                        <div class="w-8 h-8 rounded-xl hover:bg-white/5 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div class="w-8 h-8 rounded-xl hover:bg-white/5 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area Mockup (Cards, Wave Chart, Donut & Equalizer) -->
                <div class="flex-1 h-full p-6 flex flex-col gap-5 overflow-hidden bg-[#090d18]">
                    
                    <!-- Top Metric Cards Row (Purple, Emerald, Orange/Amber, Cyan) -->
                    <div class="grid grid-cols-4 gap-4">
                        <!-- Card 1: Purple Glowing Metric -->
                        <div class="h-24 rounded-2xl bg-[#111728] border border-purple-500/30 p-3.5 flex flex-col justify-between relative overflow-hidden shadow-lg shadow-purple-950/20">
                            <div class="w-14 h-6 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 opacity-90 shadow-[0_0_12px_rgba(168,85,247,0.8)]"></div>
                            <div class="flex items-baseline justify-between mt-2">
                                <div class="w-28 h-4 bg-purple-200/20 rounded"></div>
                                <div class="w-14 h-3 bg-purple-400/40 rounded-full"></div>
                            </div>
                        </div>

                        <!-- Card 2: Emerald Glowing Metric -->
                        <div class="h-24 rounded-2xl bg-[#111728] border border-emerald-500/30 p-3.5 flex flex-col justify-between relative overflow-hidden shadow-lg shadow-emerald-950/20">
                            <div class="w-14 h-6 rounded-full bg-gradient-to-r from-emerald-400 to-teal-500 opacity-90 shadow-[0_0_12px_rgba(16,185,129,0.8)]"></div>
                            <div class="flex items-baseline justify-between mt-2">
                                <div class="w-24 h-4 bg-emerald-200/20 rounded"></div>
                                <div class="w-12 h-3 bg-emerald-400/40 rounded-full"></div>
                            </div>
                        </div>

                        <!-- Card 3: Orange Glowing Metric -->
                        <div class="h-24 rounded-2xl bg-[#111728] border border-amber-500/30 p-3.5 flex flex-col justify-between relative overflow-hidden shadow-lg shadow-amber-950/20">
                            <div class="w-14 h-6 rounded-full bg-gradient-to-r from-amber-400 to-orange-500 opacity-90 shadow-[0_0_12px_rgba(245,158,11,0.8)]"></div>
                            <div class="flex items-baseline justify-between mt-2">
                                <div class="w-24 h-4 bg-amber-200/20 rounded"></div>
                                <div class="w-14 h-3 bg-amber-400/40 rounded-full"></div>
                            </div>
                        </div>

                        <!-- Card 4: Cyan Glowing Metric -->
                        <div class="h-24 rounded-2xl bg-[#111728] border border-cyan-500/30 p-3.5 flex flex-col justify-between relative overflow-hidden shadow-lg shadow-cyan-950/20">
                            <div class="w-14 h-6 rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 opacity-90 shadow-[0_0_12px_rgba(6,182,212,0.8)]"></div>
                            <div class="flex items-baseline justify-between mt-2">
                                <div class="w-24 h-4 bg-cyan-200/20 rounded"></div>
                                <div class="w-12 h-3 bg-cyan-400/40 rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle Row: Wave Chart (Left) + Donut Ring & Bar Equalizer (Right) -->
                    <div class="grid grid-cols-12 gap-5 flex-1 min-h-0">
                        
                        <!-- Left: Purple Wave Chart Area -->
                        <div class="col-span-8 rounded-2xl bg-[#111728] border border-white/[0.08] p-5 flex flex-col justify-between relative overflow-hidden">
                            <div class="flex items-center justify-between">
                                <div class="w-32 h-4 bg-white/10 rounded"></div>
                                <div class="w-20 h-3 bg-white/10 rounded-full"></div>
                            </div>

                            <!-- SVG Smooth Purple Glowing Spline Wave -->
                            <div class="w-full h-48 relative my-auto">
                                <svg class="w-full h-full" viewBox="0 0 700 200" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="purpleGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#a855f7" stop-opacity="0.6"/>
                                            <stop offset="50%" stop-color="#6366f1" stop-opacity="0.25"/>
                                            <stop offset="100%" stop-color="#111728" stop-opacity="0"/>
                                        </linearGradient>
                                        <filter id="glowWave">
                                            <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                                            <feMerge>
                                                <feMergeNode in="coloredBlur"/>
                                                <feMergeNode in="SourceGraphic"/>
                                            </feMerge>
                                        </filter>
                                    </defs>
                                    <path d="M 0,160 Q 70,60 160,110 T 320,70 T 480,130 T 620,50 L 700,80 L 700,200 L 0,200 Z" fill="url(#purpleGrad)"/>
                                    <path d="M 0,160 Q 70,60 160,110 T 320,70 T 480,130 T 620,50 L 700,80" fill="none" stroke="#c084fc" stroke-width="4" filter="url(#glowWave)"/>
                                </svg>
                            </div>

                            <!-- Bottom Grid Matrix Mock -->
                            <div class="h-10 border-t border-white/[0.06] flex items-center justify-between text-slate-500 font-mono text-[10px]">
                                <span>00:00</span>
                                <span>04:00</span>
                                <span>08:00</span>
                                <span>12:00</span>
                                <span>16:00</span>
                                <span>20:00</span>
                            </div>
                        </div>

                        <!-- Right: Circular Donut Gauge & Equalizer Bar Chart -->
                        <div class="col-span-4 rounded-2xl bg-[#111728] border border-white/[0.08] p-5 flex flex-col items-center justify-center gap-5 relative overflow-hidden">
                            <!-- Circular Donut Chart SVG (Glowing Cyan & Purple) -->
                            <div class="w-36 h-36 relative flex items-center justify-center">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="38" stroke="#1f293d" stroke-width="12" fill="none"/>
                                    <circle cx="50" cy="50" r="38" stroke="#06b6d4" stroke-width="12" stroke-dasharray="160 240" stroke-linecap="round" fill="none" style="filter: drop-shadow(0 0 8px #06b6d4);"/>
                                    <circle cx="50" cy="50" r="38" stroke="#a855f7" stroke-width="12" stroke-dasharray="70 240" stroke-dashoffset="-165" stroke-linecap="round" fill="none" style="filter: drop-shadow(0 0 8px #a855f7);"/>
                                </svg>
                                <div class="absolute inset-0 m-auto w-14 h-14 rounded-full bg-[#0a0e1a] border border-white/10 flex items-center justify-center">
                                    <span class="w-3.5 h-3.5 rounded-full bg-cyan-400 animate-ping opacity-60"></span>
                                </div>
                            </div>

                            <!-- Glowing Vertical Bar Equalizer Metrics -->
                            <div class="flex items-end justify-center gap-2.5 h-16 w-full px-4">
                                <div class="w-3 h-7 rounded-full bg-gradient-to-t from-blue-600 to-cyan-400 shadow-[0_0_8px_rgba(6,182,212,0.6)]"></div>
                                <div class="w-3 h-11 rounded-full bg-gradient-to-t from-blue-600 to-cyan-400 shadow-[0_0_8px_rgba(6,182,212,0.6)]"></div>
                                <div class="w-3 h-16 rounded-full bg-gradient-to-t from-purple-600 to-indigo-400 shadow-[0_0_8px_rgba(168,85,247,0.6)]"></div>
                                <div class="w-3 h-9 rounded-full bg-gradient-to-t from-blue-600 to-cyan-400 shadow-[0_0_8px_rgba(6,182,212,0.6)]"></div>
                                <div class="w-3 h-14 rounded-full bg-gradient-to-t from-purple-600 to-cyan-400 shadow-[0_0_8px_rgba(168,85,247,0.6)]"></div>
                                <div class="w-3 h-8 rounded-full bg-gradient-to-t from-blue-600 to-cyan-400 shadow-[0_0_8px_rgba(6,182,212,0.6)]"></div>
                            </div>
                        </div>

                    </div>

                    <!-- Bottom Row: Telemetry World Map / Data Matrix -->
                    <div class="grid grid-cols-12 gap-5 h-32 shrink-0">
                        <div class="col-span-8 rounded-2xl bg-[#111728] border border-white/[0.08] p-3.5 flex items-center justify-between relative overflow-hidden">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-emerald-400 shadow-[0_0_10px_#34d399]"></div>
                                <div class="w-36 h-3 bg-white/10 rounded"></div>
                            </div>
                            <div class="flex gap-2">
                                <div class="w-24 h-4 bg-white/5 rounded"></div>
                                <div class="w-18 h-4 bg-purple-500/20 rounded"></div>
                            </div>
                        </div>

                        <div class="col-span-4 rounded-2xl bg-[#111728] border border-white/[0.08] p-3.5 flex flex-col justify-around">
                            <div class="w-full h-3 bg-white/5 rounded"></div>
                            <div class="w-3/4 h-3 bg-white/5 rounded"></div>
                            <div class="w-1/2 h-3 bg-white/5 rounded"></div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Soft Dark Overlay -->
            <div class="absolute inset-0 bg-slate-950/25 pointer-events-none z-[1]"></div>

            <!-- Ambient Halo Glow behind Center Login Card -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-[2]">
                <div class="w-[450px] h-[450px] bg-gradient-to-tr from-purple-600/25 via-indigo-600/20 to-cyan-400/20 rounded-full blur-[90px]"></div>
            </div>

            <!-- ======================================================== -->
            <!-- FOREFRONT LAYER: CENTERED FROSTED GLASS LOGIN CARD       -->
            <!-- ======================================================== -->
            <div class="relative z-10 w-full h-full flex items-center justify-center p-4">
                @yield('content')
            </div>

        </div>

    </div>

</body>
</html>
