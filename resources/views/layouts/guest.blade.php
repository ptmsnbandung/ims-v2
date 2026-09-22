<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login' }} - IMS Router Management</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Anti-flicker Theme Init Script -->
    <script>
        (function() {
            try {
                const stored = localStorage.getItem('theme');
                if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-100 antialiased selection:bg-cyan-500 selection:text-white bg-slate-950">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Ambient background glows -->
        <div class="pointer-events-none absolute -top-40 -left-40 w-96 h-96 bg-cyan-600/15 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/2 -right-40 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 left-1/3 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <!-- Brand Logo / Header -->
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-cyan-500/30 p-2 shadow-lg shadow-cyan-500/20 mb-4 flex items-center justify-center">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                    IMS <span class="text-cyan-400">Router</span>
                </h2>
                <p class="mt-1 text-sm text-slate-400">
                    Internet System Management Portal
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl relative z-10 px-4">
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 shadow-2xl shadow-black/60 rounded-2xl p-6 sm:p-8">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} IMS Router Management. Clean Code Architecture.
            </div>
        </div>
    </div>
</body>
</html>
