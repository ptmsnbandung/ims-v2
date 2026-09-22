<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Masuk' }} - IMS Router Management</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.35; transform: scale(1); }
            50% { opacity: 0.65; transform: scale(1.08); }
        }
        @keyframes floatMesh {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }
        .anim-glow {
            animation: pulseGlow 7s ease-in-out infinite;
        }
        .anim-float {
            animation: floatMesh 12s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-full font-sans text-slate-800 antialiased selection:bg-cyan-500 selection:text-white relative overflow-x-hidden flex flex-col justify-center bg-[#072d42]">
    
    <!-- Vibrant Teal & Cyan Network Gradient Background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <!-- Base Multi-stop Vibrant Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#062638] via-[#085a75] via-[#0284c7] to-[#04334c]"></div>

        <!-- Ambient Glow Orbs -->
        <div class="absolute -top-40 -left-40 w-[38rem] h-[38rem] bg-cyan-400/25 rounded-full blur-3xl anim-glow"></div>
        <div class="absolute top-1/3 -right-40 w-[36rem] h-[36rem] bg-teal-300/20 rounded-full blur-3xl anim-glow" style="animation-delay: 2.5s;"></div>
        <div class="absolute -bottom-40 left-1/4 w-[42rem] h-[42rem] bg-sky-400/25 rounded-full blur-3xl anim-glow" style="animation-delay: 4.5s;"></div>

        <!-- SVG Network Constellation Mesh & Fiber Optics Accents -->
        <svg class="absolute inset-0 w-full h-full opacity-35 mix-blend-screen anim-float" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" viewBox="0 0 1440 900">
            <defs>
                <linearGradient id="netGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.8"/>
                    <stop offset="50%" stop-color="#2dd4bf" stop-opacity="0.6"/>
                    <stop offset="100%" stop-color="#0284c7" stop-opacity="0.2"/>
                </linearGradient>
                <radialGradient id="nodeGlow" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#67e8f9" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#06b6d4" stop-opacity="0"/>
                </radialGradient>
            </defs>
            
            <!-- Connection Lines -->
            <path d="M 120,180 L 320,120 L 520,240 L 740,160 L 980,220 L 1220,140 L 1380,260" stroke="url(#netGrad1)" stroke-width="1.5" fill="none" stroke-dasharray="6,4"/>
            <path d="M 80,480 L 260,420 L 480,560 L 720,440 L 960,540 L 1200,430 L 1400,520" stroke="url(#netGrad1)" stroke-width="1.5" fill="none"/>
            <path d="M 160,780 L 380,720 L 620,810 L 860,700 L 1100,790 L 1340,680" stroke="url(#netGrad1)" stroke-width="1.5" fill="none" stroke-dasharray="8,5"/>
            
            <!-- Diagonal Interlinks -->
            <line x1="320" y1="120" x2="480" y2="560" stroke="url(#netGrad1)" stroke-width="1" stroke-opacity="0.5"/>
            <line x1="740" y1="160" x2="720" y2="440" stroke="url(#netGrad1)" stroke-width="1.2" stroke-opacity="0.6"/>
            <line x1="980" y1="220" x2="960" y2="540" stroke="url(#netGrad1)" stroke-width="1" stroke-opacity="0.5"/>
            <line x1="260" y1="420" x2="380" y2="720" stroke="url(#netGrad1)" stroke-width="1" stroke-opacity="0.4"/>
            <line x1="720" y1="440" x2="860" y2="700" stroke="url(#netGrad1)" stroke-width="1.2" stroke-opacity="0.6"/>
            <line x1="1200" y1="430" x2="1100" y2="790" stroke="url(#netGrad1)" stroke-width="1" stroke-opacity="0.4"/>

            <!-- Glowing Nodes (Circles) -->
            <circle cx="120" cy="180" r="5" fill="#38bdf8" filter="drop-shadow(0 0 8px #38bdf8)"/>
            <circle cx="320" cy="120" r="7" fill="#2dd4bf" filter="drop-shadow(0 0 10px #2dd4bf)"/>
            <circle cx="520" cy="240" r="4" fill="#67e8f9"/>
            <circle cx="740" cy="160" r="8" fill="#38bdf8" filter="drop-shadow(0 0 12px #38bdf8)"/>
            <circle cx="980" cy="220" r="6" fill="#2dd4bf"/>
            <circle cx="1220" cy="140" r="7" fill="#67e8f9" filter="drop-shadow(0 0 10px #67e8f9)"/>
            
            <circle cx="260" cy="420" r="6" fill="#38bdf8"/>
            <circle cx="480" cy="560" r="8" fill="#2dd4bf" filter="drop-shadow(0 0 12px #2dd4bf)"/>
            <circle cx="720" cy="440" r="9" fill="#a5f3fc" filter="drop-shadow(0 0 14px #38bdf8)"/>
            <circle cx="960" cy="540" r="7" fill="#38bdf8"/>
            <circle cx="1200" cy="430" r="8" fill="#2dd4bf" filter="drop-shadow(0 0 10px #2dd4bf)"/>
            
            <circle cx="380" cy="720" r="7" fill="#67e8f9"/>
            <circle cx="620" cy="810" r="5" fill="#2dd4bf"/>
            <circle cx="860" cy="700" r="8" fill="#38bdf8" filter="drop-shadow(0 0 12px #38bdf8)"/>
            <circle cx="1100" cy="790" r="6" fill="#2dd4bf"/>
        </svg>

        <!-- Subtle Geometric Dot Matrix -->
        <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,0.18)_1px,transparent_1px)] [background-size:28px_28px] opacity-40"></div>
    </div>

    <!-- Main Container -->
    <div class="min-h-full flex flex-col justify-center py-10 sm:px-6 lg:px-8 relative z-10">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Brand Logo / Header -->
            <div class="flex flex-col items-center text-center">
                <div class="w-20 h-20 rounded-3xl bg-white/95 backdrop-blur-md p-3 shadow-2xl shadow-cyan-950/50 border border-white/80 mb-3.5 flex items-center justify-center transition duration-300 hover:scale-105">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                </div>
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white drop-shadow-[0_4px_16px_rgba(0,0,0,0.4)] flex items-center gap-2.5">
                    IMS <span class="bg-gradient-to-r from-cyan-300 via-teal-200 to-sky-300 bg-clip-text text-transparent font-black">Router</span>
                </h1>
                <p class="mt-1 text-sm font-semibold text-cyan-100/90 tracking-wide drop-shadow-md">
                    Internet System Management Portal
                </p>
            </div>
        </div>

        <div class="mt-7 sm:mx-auto sm:w-full sm:max-w-xl px-4">
            <!-- Elevated Glassmorphism White Card -->
            <div class="bg-white/95 backdrop-blur-2xl border border-white/80 shadow-[0_25px_65px_rgba(3,43,67,0.45)] rounded-3xl p-6 sm:p-9 text-slate-800 transition duration-200">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-xs font-semibold text-cyan-100/80 drop-shadow">
                &copy; {{ date('Y') }} PT. Mega Sarana Nusantara &bull; IMS Router Management
            </div>
        </div>
    </div>
</body>
</html>
