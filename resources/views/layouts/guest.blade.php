<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login' }} - IMS Router Management</title>

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
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-cyan-500 to-indigo-600 p-0.5 shadow-lg shadow-cyan-500/20 mb-4 flex items-center justify-center">
                    <div class="w-full h-full bg-slate-900 rounded-[14px] flex items-center justify-center">
                        <svg class="w-7 h-7 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                        </svg>
                    </div>
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
