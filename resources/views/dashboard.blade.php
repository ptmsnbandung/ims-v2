@extends('layouts.app', ['title' => 'Dashboard'])

@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    
    <!-- Top Greeting Header -->
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Dashboard</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
            Selamat datang kembali 👋 Berikut ringkasan bulan ini.
        </p>
    </div>

    <!-- ========================================================================= -->
    <!-- 4 KPI METRIC STAT CARDS                                                   -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- 1. PENDAFTARAN BARU -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition flex items-start justify-between">
            <div class="space-y-1">
                <span class="block text-[11px] font-black uppercase tracking-wider text-slate-400">
                    PENDAFTARAN BARU
                </span>
                <span class="block text-3xl font-black text-slate-900 tracking-tight">
                    {{ number_format($pendaftaranBaruCount ?? 1) }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-500 mt-1">
                    {{ $pendaftaranTrendText ?? '↓ 98% vs bulan lalu' }}
                </span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                </svg>
            </div>
        </div>

        <!-- 2. TIKET GANGGUAN -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition flex items-start justify-between">
            <div class="space-y-1">
                <span class="block text-[11px] font-black uppercase tracking-wider text-slate-400">
                    TIKET GANGGUAN
                </span>
                <span class="block text-3xl font-black text-slate-900 tracking-tight">
                    {{ number_format($tiketGangguanCount ?? 0) }}
                </span>
                <span class="block text-xs text-slate-400 font-medium mt-1">
                    Belum ada data
                </span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-rose-500 text-white flex items-center justify-center shadow-lg shadow-rose-500/25 flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                </svg>
            </div>
        </div>

        <!-- 3. SUSPEND -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition flex items-start justify-between">
            <div class="space-y-1">
                <span class="block text-[11px] font-black uppercase tracking-wider text-slate-400">
                    SUSPEND
                </span>
                <span class="block text-3xl font-black text-slate-900 tracking-tight">
                    {{ number_format($suspendCount ?? 0) }}
                </span>
                <span class="block text-xs text-slate-400 font-medium mt-1">
                    Belum ada data
                </span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/25 flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                </svg>
            </div>
        </div>

        <!-- 4. TERMINASI -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition flex items-start justify-between">
            <div class="space-y-1">
                <span class="block text-[11px] font-black uppercase tracking-wider text-slate-400">
                    TERMINASI
                </span>
                <span class="block text-3xl font-black text-slate-900 tracking-tight">
                    {{ number_format($terminasiCount ?? 0) }}
                </span>
                <span class="block text-xs text-slate-400 font-medium mt-1">
                    Belum ada data
                </span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-purple-600 text-white flex items-center justify-center shadow-lg shadow-purple-500/25 flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                </svg>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- PENDAFTARAN 7 BULAN TERAKHIR (CHART CARD)                                 -->
    <!-- ========================================================================= -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">
                    Pendaftaran 7 Bulan Terakhir
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Jumlah registrasi baru per bulan
                </p>
            </div>

            <div>
                <span class="text-[10px] font-mono font-medium text-slate-400 bg-slate-50 border border-slate-200/80 px-2.5 py-1 rounded-lg">
                    trx_batchjob_register
                </span>
            </div>
        </div>

        <!-- Monthly Bar Visualizer -->
        @php
            $maxCount = 0;
            foreach($chartData as $cd) {
                if ($cd['count'] > $maxCount) $maxCount = $cd['count'];
            }
            if ($maxCount <= 0) $maxCount = 50;
        @endphp

        <div class="pt-8 pb-4">
            <div class="grid grid-cols-7 gap-3 sm:gap-6 items-end h-56 border-b border-slate-100 pb-3">
                @foreach($chartData as $bar)
                    @php
                        $heightPercent = $bar['count'] > 0 ? max(10, min(100, round(($bar['count'] / $maxCount) * 100))) : 0;
                    @endphp
                    <div class="flex flex-col items-center justify-end h-full group">
                        <!-- Value Label -->
                        <span class="text-[11px] font-bold text-slate-600 mb-2 transition group-hover:text-blue-600 group-hover:scale-110">
                            {{ $bar['count'] }}
                        </span>

                        <!-- Bar Container -->
                        <div class="w-full max-w-[42px] bg-slate-50 rounded-t-xl overflow-hidden flex flex-col justify-end h-full">
                            @if($bar['count'] > 0)
                                <div class="w-full bg-blue-500 group-hover:bg-blue-600 transition-all rounded-t-xl shadow-xs"
                                     style="height: {{ $heightPercent }}%;"></div>
                            @else
                                <div class="w-full h-1 bg-slate-200 rounded-full"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Month Labels Under Chart -->
            <div class="grid grid-cols-7 gap-3 sm:gap-6 text-center pt-3">
                @foreach($chartData as $bar)
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        {{ $bar['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- DISTRIBUSI PERUSAHAAN & ID PELANGGAN                                      -->
    <!-- ========================================================================= -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-900">
                        Distribusi Perusahaan &amp; ID Pelanggan
                    </h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-200">
                        {{ $perusahaanCount }} Perusahaan
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">
                    Pemetaan relasi nama perusahaan terhadap seluruh ID Pelanggan (Nomor Internet / Layanan) yang terdaftar.
                </p>
            </div>
        </div>

        @if($perusahaanList->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-2">
                @foreach($perusahaanList as $p)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 truncate mr-2">{{ $p->nama_perusahaan }}</span>
                        <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-white text-blue-600 border border-slate-200 shadow-xs">
                            {{ $p->total }} ID
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- FOOTER INFO BAR                                                           -->
    <!-- ========================================================================= -->
    <div class="pt-4 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-2 font-medium">
        <div>
            &copy; 2026 Connecti Jelajah Priangan
        </div>
        <div class="flex items-center gap-1">
            <span>Connecti Jelajah Priangan</span>
            <span class="text-rose-500">&hearts;</span>
            <span>v3.0.1</span>
        </div>
    </div>

</div>
@endsection
