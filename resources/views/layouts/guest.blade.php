<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login' }} - IMS Router Management</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased selection:bg-blue-600 selection:text-white bg-slate-50">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Ambient background glows -->
        <div class="pointer-events-none absolute -top-40 -left-40 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/2 -right-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 left-1/3 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <!-- Brand Logo / Header -->
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 p-2 shadow-lg mb-4 flex items-center justify-center">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                    IMS <span class="text-blue-600">Router</span>
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Internet System Management Portal
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl relative z-10 px-4">
            <div class="bg-white border border-slate-200/90 shadow-xl rounded-2xl p-6 sm:p-8">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} IMS Router Management. Clean Code Architecture.
            </div>
        </div>
    </div>
</body>
</html>
