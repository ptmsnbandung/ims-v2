@extends('layouts.app')

@section('page_title', 'Kelola & Kirim Custom Email')

@section('content')
<div class="space-y-6" x-data="emailManagement()">
    <!-- Top Breadcrumbs & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                <span>IMS</span>
                <span>&rsaquo;</span>
                <span>Admin</span>
                <span>&rsaquo;</span>
                <span class="text-sky-600 dark:text-sky-400 font-semibold">Kelola Email</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 flex items-center justify-center text-sky-600 dark:text-sky-400 shadow-xs">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Kelola &amp; Kirim Custom Email</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Kirim email pengumuman, pemeliharaan, atau pesan khusus ke pelanggan via Hostinger Mail API.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center gap-2">
            <button @click="openTestModal()"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                <svg class="w-4 h-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span>Kirim Uji Coba</span>
            </button>
            <a href="{{ route('admin.email.history') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow-md shadow-sky-600/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Semua Riwayat Log</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Stat Cards & Sisa Limit Kirim -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Pelanggan -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pelanggan</p>
                <p class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ number_format($kpi['total_customers'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($kpi['with_email'], 0, ',', '.') }}</span> memiliki email
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- 2. Sisa Limit Kirim Hari Ini (Hostinger Mail API Quota) -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sisa Limit Hari Ini</p>
                    <p class="text-2xl font-extrabold {{ $kpi['remaining_today'] > 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500' }}">
                        {{ number_format($kpi['remaining_today'], 0, ',', '.') }}
                        <span class="text-xs font-medium text-slate-400">/ {{ number_format($kpi['daily_limit'], 0, ',', '.') }}</span>
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $kpi['remaining_today'] > 100 ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-500/20' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 border border-amber-200 dark:border-amber-500/20' }} flex items-center justify-center">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="mt-2.5">
                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $kpi['remaining_today'] > 100 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                         style="width: {{ 100 - $kpi['usage_percent'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                    <span>{{ 100 - $kpi['usage_percent'] }}% kuota tersedia</span>
                    <span>Reset 24 Jam</span>
                </div>
            </div>
        </div>

        <!-- 3. Email Terkirim Hari Ini & Kumulatif -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Terkirim Hari Ini</p>
                <p class="text-2xl font-extrabold text-sky-600 dark:text-sky-400">{{ number_format($kpi['sent_today'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                    Total kumulatif: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ number_format($kpi['total_sent'], 0, ',', '.') }}</span>
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 flex items-center justify-center text-sky-600 dark:text-sky-400">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </div>
        </div>

        <!-- 4. Gateway Status -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hostinger Mail API</p>
                @if($isHostingerReady)
                    <p class="text-base font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Terhubung (Aktif)
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[140px]">{{ config('services.hostinger_mail.from', 'info@ptmsn.co.id') }}</p>
                @else
                    <p class="text-base font-bold text-amber-500 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        Fallback SMTP
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Token API belum disetel di .env</p>
                @endif
            </div>
            <div class="w-11 h-11 rounded-xl {{ $isHostingerReady ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-500/20' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 border border-amber-200 dark:border-amber-500/20' }} flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Workspace: 2 Columns (Pelanggan Selector + Email Composer) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Customer Selector (7 Columns) -->
        <div class="lg:col-span-6 xl:col-span-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Pilih Pelanggan Penerima</span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300"
                              x-text="selectedTargets.length + ' dipilih'"></span>
                    </h2>

                    <!-- Select / Unselect all on current page -->
                    <button @click="toggleSelectAll()"
                            type="button"
                            class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline cursor-pointer">
                        <span x-text="isAllSelected() ? 'Batal Pilih Semua' : 'Pilih Semua di Halaman Ini'"></span>
                    </button>
                </div>

                <!-- Search & Filters -->
                @php
                    $bulanList = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];
                    $curYear = (int) date('Y');
                @endphp
                <form method="GET" action="{{ route('admin.email.index') }}" class="space-y-2">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Cari nama, nomor internet, email..."
                               class="w-full pl-9 pr-16 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        @if($search !== '' || $statusFilter !== 'all' || ($bulanFilter ?? 'all') !== 'all')
                            <a href="{{ route('admin.email.index') }}"
                               class="absolute right-2.5 top-2 px-2 py-0.5 rounded-lg bg-slate-200/80 dark:bg-slate-700 text-[10px] font-semibold text-slate-600 dark:text-slate-300 hover:bg-rose-100 hover:text-rose-600 transition">
                                Reset
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                        <!-- Filter Status (Lunas, Jatuh Tempo, Suspend, dll) -->
                        <div class="sm:col-span-5">
                            <select name="status"
                                    onchange="this.form.submit()"
                                    class="w-full py-1.5 px-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Pelanggan</option>
                                <option value="lunas" {{ $statusFilter === 'lunas' ? 'selected' : '' }}>&#10003; Lunas (Sudah Bayar)</option>
                                <option value="jatuh_tempo" {{ $statusFilter === 'jatuh_tempo' ? 'selected' : '' }}>&#9888; Jatuh Tempo (Belum Lunas)</option>
                                <option value="suspend" {{ $statusFilter === 'suspend' ? 'selected' : '' }}>&#128683; Suspend / Isolir</option>
                                <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Pelanggan Aktif</option>
                                <option value="with_email" {{ $statusFilter === 'with_email' ? 'selected' : '' }}>Hanya Punya Email</option>
                                <option value="without_email" {{ $statusFilter === 'without_email' ? 'selected' : '' }}>Tanpa Email</option>
                            </select>
                        </div>

                        <!-- Filter Bulan Tagihan -->
                        <div class="sm:col-span-4">
                            <select name="bulan"
                                    onchange="this.form.submit()"
                                    class="w-full py-1.5 px-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                <option value="all" {{ ($bulanFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Bulan Tagihan</option>
                                @foreach($bulanList as $bKey => $bLabel)
                                    <option value="{{ $bKey }}" {{ ($bulanFilter ?? 'all') === $bKey ? 'selected' : '' }}>
                                        Bulan {{ $bLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Tahun Tagihan -->
                        <div class="sm:col-span-3">
                            <select name="tahun"
                                    onchange="this.form.submit()"
                                    class="w-full py-1.5 px-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                @for($y = $curYear + 1; $y >= $curYear - 2; $y--)
                                    <option value="{{ $y }}" {{ (string)($tahunFilter ?? $curYear) === (string)$y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Customer List Table -->
            <div class="flex-1 overflow-x-auto divide-y divide-slate-100 dark:divide-slate-800 max-h-[560px] overflow-y-auto">
                @forelse($customers as $c)
                @php
                    $hasEmail = !empty($c->email) && filter_var($c->email, FILTER_VALIDATE_EMAIL);
                    $isSuspend = in_array((string)($c->status_reg ?? ''), ['23', '23.1']);
                    $billStatus = (string)($c->status_bill_lay ?? '');
                    $bulanShort = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
                    ];
                    $periodeLabel = (!empty($c->bulan_tagihan) && !empty($c->tahun_tagihan))
                        ? ($bulanShort[(int)$c->bulan_tagihan] ?? $c->bulan_tagihan) . ' ' . $c->tahun_tagihan
                        : null;
                @endphp
                <label class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition select-none">
                    <input type="checkbox"
                           value="{{ $c->nomor_internet }}"
                           x-model="selectedTargets"
                           @if(!$hasEmail) disabled @endif
                           class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 dark:border-slate-700 {{ !$hasEmail ? 'opacity-30 cursor-not-allowed' : '' }}">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ $c->nama_pelanggan }}
                                </p>
                                <span class="text-[10px] font-mono text-slate-400 shrink-0">#{{ $c->nomor_internet }}</span>
                            </div>

                            <!-- Status Badges (Lunas / Jatuh Tempo / Suspend) -->
                            <div class="flex items-center gap-1 shrink-0">
                                @if($isSuspend)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/50">
                                        Suspend
                                    </span>
                                @endif

                                @if($billStatus === '15')
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/50">
                                        Lunas
                                    </span>
                                @elseif(in_array($billStatus, ['13', '14']))
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/50">
                                        Jatuh Tempo
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5">
                            @if($hasEmail)
                                <span class="text-[11px] text-sky-600 dark:text-sky-400 truncate flex items-center gap-1 font-mono">
                                    <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    {{ $c->email }}
                                </span>
                            @else
                                <span class="text-[10px] font-medium text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-1.5 py-0.5 rounded">
                                    Belum ada email
                                </span>
                            @endif

                            @if(!empty($c->nama_bandwith))
                                <span class="text-[10px] text-slate-400 truncate">&bull; {{ $c->nama_bandwith }}</span>
                            @endif

                            @if($periodeLabel)
                                <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">
                                    &bull; {{ $periodeLabel }}
                                    @if(!empty($c->total_layanan) && (float)$c->total_layanan > 0)
                                        (Rp {{ number_format((float)$c->total_layanan, 0, ',', '.') }})
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                </label>
                @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    Tidak ada data pelanggan yang sesuai dengan filter.
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-xs">
                {{ $customers->links() }}
            </div>
        </div>

        <!-- Right: Email Composer (6 Columns) -->
        <div class="lg:col-span-6 xl:col-span-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Formulir Pesan Email</h2>
                    <p class="text-xs text-slate-500">Tulis isi pengumuman atau pilih preset template resmi.</p>
                </div>

                <!-- Template Preset Selector -->
                <select x-model="selectedPresetId"
                        @change="applyPreset()"
                        class="text-xs py-1.5 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-medium text-slate-800 dark:text-slate-200">
                    <option value="">-- Pilih Template Preset --</option>
                    @foreach($presets as $p)
                        <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="p-5 space-y-4 flex-1">
                <!-- Sisa Limit Kirim Alert Banner -->
                <div class="p-3 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $kpi['remaining_today'] > 50 ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <span class="text-slate-700 dark:text-slate-300">
                            Sisa limit Hostinger hari ini: <strong class="text-sky-900 dark:text-sky-200 font-bold">{{ number_format($kpi['remaining_today'], 0, ',', '.') }}</strong> / {{ number_format($kpi['daily_limit'], 0, ',', '.') }} email
                        </span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400"
                          :class="selectedTargets.length > {{ $kpi['remaining_today'] }} ? 'text-rose-600 dark:text-rose-400 font-bold' : ''"
                          x-text="selectedTargets.length > 0 ? ('Pakai: ' + selectedTargets.length + ' kuota') : 'Maks 100/batch'"></span>
                </div>

                <!-- Subjek Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Subjek Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           x-model="form.subject"
                           placeholder="Contoh: Pemberitahuan Pemeliharaan Jaringan - PT Media Solusi Network"
                           class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Badge Title Pill (Header) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Label / Badge Header
                        </label>
                        <input type="text"
                               x-model="form.badge_text"
                               placeholder="Contoh: PEMBERITAHUAN RESMI"
                               class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Teks Tombol Aksi (Opsional)
                        </label>
                        <input type="text"
                               x-model="form.action_text"
                               placeholder="Contoh: Buka Portal Tagihan"
                               class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                </div>

                <!-- URL Tombol Aksi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        URL Tombol Aksi (Opsional)
                    </label>
                    <input type="url"
                           x-model="form.action_url"
                           placeholder="https://portal.ptmsn.co.id"
                           class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Isi Pesan -->
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-1 mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Isi Pesan Email <span class="text-rose-500">*</span>
                        </label>
                        <!-- Variabel Tags Helper -->
                        <div class="flex flex-wrap items-center gap-1 text-[11px] text-slate-400">
                            <span>Variabel:</span>
                            <button type="button" @click="insertTag('{nama_pelanggan}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{nama_pelanggan}</button>
                            <button type="button" @click="insertTag('{nomor_internet}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{nomor_internet}</button>
                            <button type="button" @click="insertTag('{paket}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{paket}</button>
                            <button type="button" @click="insertTag('{periode}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{periode}</button>
                            <button type="button" @click="insertTag('{nominal}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{nominal}</button>
                        </div>
                    </div>
                    <textarea x-model="form.message"
                              rows="9"
                              placeholder="Tuliskan isi pesan pengumuman di sini..."
                              class="w-full p-3.5 rounded-xl text-xs leading-relaxed bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 font-sans"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Gunakan baris baru (Enter) ganda untuk memisahkan paragraf. Variabel akan otomatis digantikan sesuai profil pelanggan.</p>
                </div>
            </div>

            <!-- Composer Footer Actions -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-wrap items-center justify-between gap-3">
                <button type="button"
                        @click="openPreviewModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold transition cursor-pointer">
                    <svg class="w-4 h-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>Pratinjau (Preview)</span>
                </button>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="sendBroadcast()"
                            :disabled="isSending || selectedTargets.length === 0"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-md shadow-sky-600/25 transition cursor-pointer">
                        <template x-if="!isSending">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </template>
                        <template x-if="isSending">
                            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <span x-text="isSending ? 'Memproses...' : 'Kirim ke ' + selectedTargets.length + ' Pelanggan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Pengiriman Terkini (Preview Tabel Bawah) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Riwayat Pengiriman Email Terkini</h3>
                <p class="text-xs text-slate-500">10 aktivitas pengiriman email terakhir ke pelanggan.</p>
            </div>
            <a href="{{ route('admin.email.history') }}" class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                Lihat Semua Riwayat &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Penerima</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Subjek</th>
                        <th class="p-3">Pengirim</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3 whitespace-nowrap text-slate-400">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}
                        </td>
                        <td class="p-3 font-semibold text-slate-900 dark:text-white">
                            {{ $log->nama_pelanggan ?? '-' }}
                            @if($log->nomor_internet)
                                <span class="text-[10px] text-slate-400 font-mono">(#{{ $log->nomor_internet }})</span>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-[11px] text-sky-600 dark:text-sky-400">
                            {{ $log->email_tujuan }}
                        </td>
                        <td class="p-3 max-w-xs truncate font-medium text-slate-800 dark:text-slate-200">
                            {{ $log->subjek }}
                        </td>
                        <td class="p-3 text-slate-500">
                            {{ $log->sender_user ?? 'SYSTEM' }}
                        </td>
                        <td class="p-3">
                            @if($log->status === 'sent')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sent
                                </span>
                            @elseif($log->status === 'queued')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span> Queued
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" title="{{ $log->error_message }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <button @click="showLogDetail({{ $log->id }})"
                                    class="text-xs font-semibold text-slate-500 hover:text-sky-600 cursor-pointer">
                                Lihat Pesan
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-400 text-xs">
                            Belum ada riwayat pengiriman custom email.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Live Preview Modal -->
    <div x-show="previewModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.away="previewModalOpen = false">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    </svg>
                    <span>Pratinjau Tampilan Email Resmi PT MSN</span>
                </h3>
                <button @click="previewModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <div class="p-4 overflow-y-auto flex-1 bg-slate-100 dark:bg-slate-950">
                <div x-html="previewHtml" class="w-full"></div>
            </div>

            <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex justify-end">
                <button @click="previewModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Test Email Send Modal -->
    <div x-show="testModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden"
             @click.away="testModalOpen = false">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kirim Uji Coba Email</h3>
                <button @click="testModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <div class="p-5 space-y-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Kirim template email yang sedang Anda susun ke alamat email Anda sendiri terlebih dahulu untuk melihat hasil tampilannya di inbox asli.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Alamat Email Tujuan Uji Coba <span class="text-rose-500">*</span>
                    </label>
                    <input type="email"
                           x-model="testEmail"
                           placeholder="nama@gmail.com"
                           class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-end gap-2">
                <button @click="testModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                    Batal
                </button>
                <button @click="submitTestSend()"
                        :disabled="isTestSending || !testEmail"
                        class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold transition">
                    <span x-text="isTestSending ? 'Mengirim...' : 'Kirim Sekarang'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function emailManagement() {
    return {
        selectedTargets: [],
        selectedPresetId: '',
        presets: @json($presets),
        pageTargetIds: @json($customers->whereNotNull('email')->where('email', '!=', '')->pluck('nomor_internet')->toArray()),

        form: {
            subject: 'Pemberitahuan Resmi - PT Media Solusi Network',
            badge_text: 'PEMBERITAHUAN RESMI',
            action_text: 'Kunjungi Portal Pelanggan',
            action_url: '{{ config("company.portal_url", "https://portal.ptmsn.co.id") }}',
            message: "Kepada Yth. {nama_pelanggan},\n\nKami menginformasikan terkait layanan internet Anda #{nomor_internet}.\n\nTerima kasih atas perhatian dan kerja sama Anda."
        },

        previewModalOpen: false,
        previewHtml: '',

        testModalOpen: false,
        testEmail: '{{ auth()->user()->email ?? "m.azmi.f12345@gmail.com" }}',
        isTestSending: false,
        isSending: false,

        isAllSelected() {
            if (this.pageTargetIds.length === 0) return false;
            return this.pageTargetIds.every(id => this.selectedTargets.includes(String(id)));
        },

        toggleSelectAll() {
            if (this.isAllSelected()) {
                this.selectedTargets = this.selectedTargets.filter(id => !this.pageTargetIds.includes(String(id)) && !this.pageTargetIds.includes(Number(id)));
            } else {
                const combined = new Set([...this.selectedTargets, ...this.pageTargetIds.map(String)]);
                this.selectedTargets = Array.from(combined);
            }
        },

        applyPreset() {
            if (!this.selectedPresetId) return;
            const p = this.presets.find(item => item.id === this.selectedPresetId);
            if (p) {
                this.form.subject = p.subject;
                this.form.badge_text = p.badge;
                this.form.message = p.body;
                this.form.action_text = p.action_text;
                this.form.action_url = p.action_url;
            }
        },

        insertTag(tag) {
            this.form.message += ' ' + tag;
        },

        async openPreviewModal() {
            try {
                const res = await fetch('{{ route("admin.email.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    this.previewHtml = data.html;
                    this.previewModalOpen = true;
                }
            } catch (e) {
                alert('Gagal memuat pratinjau: ' + e.message);
            }
        },

        openTestModal() {
            this.testModalOpen = true;
        },

        async submitTestSend() {
            if (!this.testEmail) {
                alert('Masukkan alamat email pengujian.');
                return;
            }
            this.isTestSending = true;
            try {
                const payload = {
                    ...this.form,
                    is_test: true,
                    test_email: this.testEmail
                };
                const res = await fetch('{{ route("admin.email.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.testModalOpen = false;
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan: ' + e.message);
            } finally {
                this.isTestSending = false;
            }
        },

        async sendBroadcast() {
            if (this.selectedTargets.length === 0) {
                alert('Silakan pilih minimal 1 pelanggan penerima email.');
                return;
            }

            if (!confirm(`Apakah Anda yakin ingin mengirim email ini ke ${this.selectedTargets.length} pelanggan terpilih? Tugas pengiriman akan langsung diproses di latar belakang.`)) {
                return;
            }

            this.isSending = true;
            try {
                const payload = {
                    ...this.form,
                    targets: this.selectedTargets,
                    bulan: '{{ $bulanFilter ?? "all" }}',
                    tahun: '{{ $tahunFilter ?? date("Y") }}'
                };
                const res = await fetch('{{ route("admin.email.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.selectedTargets = [];
                    window.location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan pengiriman: ' + e.message);
            } finally {
                this.isSending = false;
            }
        },

        async showLogDetail(id) {
            try {
                const res = await fetch(`/admin/email/history/${id}`);
                const data = await res.json();
                if (data.success && data.data) {
                    this.previewHtml = data.data.pesan_html;
                    this.previewModalOpen = true;
                }
            } catch (e) {
                alert('Gagal memuat detail log: ' + e.message);
            }
        }
    };
}
</script>
@endsection

