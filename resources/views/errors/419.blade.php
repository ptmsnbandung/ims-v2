<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5F6FA]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 - Sesi Kedaluwarsa | IMS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased selection:bg-blue-600 selection:text-white bg-[#F5F6FA] flex items-center justify-center p-4">
    <div class="pointer-events-none absolute -top-40 -left-40 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl"></div>
    <div class="pointer-events-none absolute top-1/2 -right-40 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl"></div>

    <div class="max-w-md w-full bg-white border border-[#E2E8F0] shadow-xl shadow-slate-200/80 rounded-2xl p-8 text-center relative z-10">
        <!-- Icon -->
        <div class="w-16 h-16 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] text-[#B45309] mx-auto flex items-center justify-center mb-5 shadow-xs">
            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>

        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FFFBEB] text-[#B45309] border border-[#FDE68A] mb-3">
            Error 419: Page Expired
        </span>

        <h1 class="text-xl font-bold text-[#1E293B] mb-2">Sesi Halaman Berakhir</h1>
        <p class="text-sm text-[#64748B] mb-6 leading-relaxed">
            Halaman login Anda telah dibuka terlalu lama tanpa aktivitas sehingga sesi keamanan (CSRF Token) kedaluwarsa.
        </p>

        <div class="space-y-3">
            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-sm shadow-md shadow-blue-600/25 transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Muat Ulang / Masuk Kembali</span>
            </a>
            <p class="text-[11px] text-[#94A3B8]">
                Atau tekan <kbd class="px-1.5 py-0.5 rounded bg-[#F8FAFC] border border-[#E2E8F0] text-[#334155] font-mono">F5</kbd> pada keyboard Anda.
            </p>
        </div>
    </div>
</body>
</html>
