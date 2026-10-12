@extends('layouts.app')

@section('page_title', 'Notifikasi & Approval Pembayaran')

@section('content')
<div class="space-y-3.5 sm:space-y-4" x-data="{
    approveModalOpen: false,
    rejectModalOpen: false,
    previewModalOpen: false,
    previewImageUrl: '',
    previewTitle: '',
    
    // Data form approval
    approveData: {
        kode_billing: '',
        nama_pelanggan: '',
        nomor_internet: '',
        nominal_bayar: 0,
        nominal_display: '',
        bank_tujuan: 'BCA',
        metode_bayar: 'transfer',
        catatan: ''
    },
    
    // Data form reject
    rejectData: {
        kode_billing: '',
        nama_pelanggan: '',
        alasan_tolak: 'Bukti transfer tidak valid atau dana belum masuk rekening.'
    },

    openApproveModal(item) {
        this.approveData.kode_billing = item.kode_billing;
        this.approveData.nama_pelanggan = item.nama_pelanggan;
        this.approveData.nomor_internet = item.nomor_internet;
        this.approveData.nominal_bayar = item.nominal > 0 ? item.nominal : '';
        this.approveData.nominal_display = item.nominal_formatted;
        this.approveData.bank_tujuan = (item.bank_tujuan && item.bank_tujuan !== 'Transfer Manual') ? item.bank_tujuan : 'BCA';
        this.approveData.metode_bayar = 'transfer';
        this.approveData.catatan = 'Diverifikasi & disetujui via Halaman Notifikasi';
        this.approveModalOpen = true;
    },

    openRejectModal(item) {
        this.rejectData.kode_billing = item.kode_billing;
        this.rejectData.nama_pelanggan = item.nama_pelanggan;
        this.rejectData.alasan_tolak = 'Bukti transfer tidak valid atau dana belum masuk ke rekening perusahaan.';
        this.rejectModalOpen = true;
    },

    openPreviewModal(url, title) {
        this.previewImageUrl = url;
        this.previewTitle = title;
        this.previewModalOpen = true;
    }
}">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 mb-0.5">
                <span>Finance</span>
                <span>&rsaquo;</span>
                <span class="text-[#0891b2] dark:text-cyan-400 font-semibold">Notifikasi & Approval</span>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Notifikasi Pembayaran Masuk
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Pusat verifikasi dan approval transaksi pembayaran serta bukti transfer pelanggan.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('finance.billing-layanan') }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-[#0891b2]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25" />
                </svg>
                <span>Billing Layanan</span>
            </a>
            <a href="{{ route('finance.notifikasi') }}"
               class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition"
               title="Refresh Data">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Compact KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- KPI 1: Menunggu Approval -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Menunggu Approval</span>
                    <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                        {{ number_format($kpiPendingCount, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center ring-1 ring-amber-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-1.5 text-[10px] text-amber-700 dark:text-amber-300 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Memerlukan verifikasi finance</span>
            </div>
        </div>

        <!-- KPI 2: Pembayaran Masuk Hari Ini -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Pembayaran Hari Ini</span>
                    <h3 class="text-xl font-bold text-[#0891b2] dark:text-cyan-400 mt-0.5">
                        {{ number_format($kpiTodayCount, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-[#0891b2] dark:text-cyan-400 flex items-center justify-center ring-1 ring-cyan-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                <span>Tanggal: <strong>{{ \Carbon\Carbon::now('Asia/Jakarta')->format('d M Y') }}</strong></span>
            </div>
        </div>

        <!-- KPI 3: Total Lunas Bulan Ini -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Lunas Bulan Ini</span>
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                        Rp {{ number_format($kpiMonthPaidAmount, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center ring-1 ring-emerald-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v9.75c0 .414.336.75.75.75h.75m0-11.25H12m6.75 0H21m0 0v9.75a.75.75 0 0 1-.75.75h-.75m0-10.5v-.75A.75.75 0 0 0 19.5 4.5H18" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-1.5 text-[10px] text-emerald-700 dark:text-emerald-300 font-medium">
                <span>Periode: <strong>{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('F Y') }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Filter & Tab Navigation -->
    <div class="p-2.5 sm:p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <form method="GET" action="{{ route('finance.notifikasi') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-2.5">
            <!-- Tab Buttons -->
            <div class="flex items-center gap-1 p-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg max-w-full overflow-x-auto">
                <a href="{{ route('finance.notifikasi', ['tab' => 'all', 'search' => $search, 'per_page' => $perPage]) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold transition whitespace-nowrap {{ $tab === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    Semua Notifikasi
                </a>
                <a href="{{ route('finance.notifikasi', ['tab' => 'pending', 'search' => $search, 'per_page' => $perPage]) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold transition whitespace-nowrap flex items-center gap-1.5 {{ $tab === 'pending' ? 'bg-amber-500 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    <span>Menunggu Approval</span>
                    @if($kpiPendingCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $tab === 'pending' ? 'bg-white text-amber-600' : 'bg-amber-500 text-white' }}">{{ $kpiPendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('finance.notifikasi', ['tab' => 'approved', 'search' => $search, 'per_page' => $perPage]) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold transition whitespace-nowrap {{ $tab === 'approved' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    Sudah Disetujui (Lunas)
                </a>
            </div>

            <input type="hidden" name="tab" value="{{ $tab }}">

            <!-- Search Bar & Controls -->
            <div class="flex items-center gap-2 flex-1 md:max-w-md justify-end">
                <div class="relative w-full">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari invoice, internet, pelanggan..."
                           class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0891b2] text-slate-900 dark:text-white">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="text-xs py-1.5 px-2 rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 cursor-pointer">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 / hal</option>
                    <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / hal</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 / hal</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / hal</option>
                </select>
                <button type="submit"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-[#0891b2] to-[#0284c7] hover:from-[#0e7490] hover:to-[#0369a1] shadow-2xs transition flex-shrink-0 cursor-pointer">
                    Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Main Table Container -->
    <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        @if($notifications->count() > 0)
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse table-fixed">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-2.5 px-3 w-[17%]">Status & Waktu</th>
                            <th class="py-2.5 px-3 w-[18%]">Pelanggan</th>
                            <th class="py-2.5 px-3 w-[17%]">Invoice / Tagihan</th>
                            <th class="py-2.5 px-3 w-[11%]">Nominal</th>
                            <th class="py-2.5 px-3 w-[13%]">Metode / Bank</th>
                            <th class="py-2.5 px-3 w-[10%] text-center">Bukti</th>
                            <th class="py-2.5 px-3 w-[14%] text-right pr-3.5">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 text-xs">
                        @foreach($notifications as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition {{ !$item->is_approved ? 'bg-amber-50/30 dark:bg-amber-950/20' : '' }}">
                            
                            <!-- 1. Status & Waktu -->
                            <td class="py-2.5 px-3 align-middle">
                                <div class="flex flex-col gap-0.5 min-w-0">
                                    @if($item->is_approved)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 w-fit whitespace-nowrap">
                                            <svg class="w-2.5 h-2.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>Lunas / Disetujui</span>
                                        </span>
                                    @elseif($item->status === 'rejected')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 w-fit whitespace-nowrap">
                                            <svg class="w-2.5 h-2.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            <span>Ditolak</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 w-fit animate-pulse whitespace-nowrap">
                                            <svg class="w-2.5 h-2.5 text-amber-600 dark:text-amber-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            <span>Menunggu Approval</span>
                                        </span>
                                    @endif
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate block" title="{{ $item->time_ago }} &middot; {{ $item->date }}">
                                        {{ $item->time_ago }} &middot; {{ $item->date }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Pelanggan -->
                            <td class="py-2.5 px-3 align-middle">
                                <div class="font-semibold text-xs text-slate-900 dark:text-white truncate" title="{{ $item->nama_pelanggan }}">
                                    {{ $item->nama_pelanggan }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5 truncate" title="{{ $item->nomor_internet }}">
                                    <svg class="w-2.5 h-2.5 text-[#0891b2] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <span class="truncate">{{ $item->nomor_internet }}</span>
                                </div>
                            </td>

                            <!-- 3. Invoice / Tagihan -->
                            <td class="py-2.5 px-3 align-middle">
                                <a href="{{ route('finance.billing-layanan', ['search' => $item->kode_billing]) }}"
                                   target="_blank"
                                   class="font-mono text-xs font-semibold text-[#0891b2] dark:text-cyan-400 hover:underline inline-flex items-center gap-1 max-w-full"
                                   title="{{ $item->kode_billing }}">
                                    <span class="truncate">{{ $item->kode_billing }}</span>
                                    <svg class="w-2.5 h-2.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                                @if($item->notes)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 italic truncate mt-0.5" title="{{ $item->notes }}">
                                        "{{ $item->notes }}"
                                    </p>
                                @endif
                            </td>

                            <!-- 4. Nominal -->
                            <td class="py-2.5 px-3 align-middle">
                                <div class="font-bold text-xs truncate {{ $item->is_approved ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}" title="{{ $item->nominal_formatted }}">
                                    {{ $item->nominal_formatted }}
                                </div>
                            </td>

                            <!-- 5. Metode / Bank -->
                            <td class="py-2.5 px-3 align-middle">
                                <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium text-[10px] max-w-full" title="{{ $item->bank_tujuan }}">
                                    <svg class="w-2.5 h-2.5 text-[#0891b2] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                    </svg>
                                    <span class="truncate">{{ $item->bank_tujuan }}</span>
                                </div>
                            </td>

                            <!-- 6. Bukti Transfer -->
                            <td class="py-2.5 px-3 align-middle text-center">
                                @if($item->proof_file)
                                    <button type="button"
                                            @click="openPreviewModal('{{ asset($item->proof_file) }}', 'Bukti Transfer: {{ $item->kode_billing }}')"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-[#0891b2] dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/40 hover:bg-cyan-100 dark:hover:bg-cyan-900/60 border border-cyan-200/60 dark:border-cyan-800/60 transition cursor-pointer">
                                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        <span>Lihat Bukti</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 text-[10px] italic">Tanpa bukti</span>
                                @endif
                            </td>

                            <!-- 7. Aksi Approval -->
                            <td class="py-2.5 px-3 pr-3.5 align-middle text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if(!$item->is_approved)
                                        <!-- Tombol Approve -->
                                        <button type="button"
                                                @click="openApproveModal({
                                                    kode_billing: '{{ $item->kode_billing }}',
                                                    nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}',
                                                    nomor_internet: '{{ $item->nomor_internet }}',
                                                    nominal: {{ $item->nominal }},
                                                    nominal_formatted: '{{ $item->nominal_formatted }}',
                                                    bank_tujuan: '{{ $item->bank_tujuan }}'
                                                })"
                                                class="px-2.5 py-1 rounded-md text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-2xs transition flex items-center gap-1 cursor-pointer flex-shrink-0">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>Approve</span>
                                        </button>

                                        <!-- Tombol Tolak -->
                                        <button type="button"
                                                @click="openRejectModal({
                                                    kode_billing: '{{ $item->kode_billing }}',
                                                    nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}'
                                                })"
                                                class="px-2 py-1 rounded-md text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition flex items-center gap-1 cursor-pointer flex-shrink-0"
                                                title="Tolak Pembayaran">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            <span>Tolak</span>
                                        </button>
                                    @else
                                        <!-- Status Disetujui -->
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono italic truncate block text-right" title="{{ $item->admin_notes ? $item->admin_notes : 'Telah diverifikasi' }}">
                                            {{ $item->admin_notes ? Str::limit($item->admin_notes, 20) : 'Telah diverifikasi' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-3 py-2.5 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                {{ $notifications->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="py-12 px-4 text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center mb-2.5">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">Tidak ada notifikasi pembayaran ditemukan</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 max-w-sm mx-auto">
                    @if($search !== '')
                        Tidak ada transaksi yang cocok dengan kata kunci "{{ $search }}". Silakan coba filter lain.
                    @else
                        Saat ini belum ada data notifikasi pembayaran pada kategori ini.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <!-- MODAL 1: APPROVE PEMBAYARAN -->
    <div x-show="approveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="approveModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="approveModalOpen = false"
                 class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Body -->
            <div x-show="approveModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-200 dark:border-slate-800">
                
                <form action="{{ route('finance.notifikasi.approve') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kode_billing" :value="approveData.kode_billing">

                    <!-- Header Modal -->
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-50 via-emerald-50/50 to-white dark:from-emerald-950/30 dark:via-slate-900 dark:to-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center ring-1 ring-emerald-500/20">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Approve Pembayaran</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Verifikasi penerimaan dana invoice bulanan</p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="approveModalOpen = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content Fields -->
                    <div class="p-4 sm:p-5 space-y-3">
                        <!-- Info Tagihan Card -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Kode Invoice</span>
                                <p class="font-mono text-xs font-bold text-[#0891b2] dark:text-cyan-400 truncate" x-text="approveData.kode_billing"></p>
                                <p class="text-[11px] font-semibold text-slate-800 dark:text-slate-200 truncate mt-0.5" x-text="approveData.nama_pelanggan + ' (' + approveData.nomor_internet + ')'"></p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Nominal Tagihan</span>
                                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="approveData.nominal_display"></p>
                            </div>
                        </div>

                        <!-- Nominal Diterima -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nominal Pembayaran Diterima (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number"
                                   name="nominal_bayar"
                                   x-model="approveData.nominal_bayar"
                                   required
                                   class="w-full px-3 py-1.5 text-xs font-bold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#0891b2]">
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Pastikan nominal sesuai dengan mutasi rekening bank.</p>
                        </div>

                        <!-- Bank Tujuan / Metode Pembayaran -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Bank / Penerima
                                </label>
                                <select name="bank_tujuan"
                                        x-model="approveData.bank_tujuan"
                                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#0891b2]">
                                    <option value="BCA">BCA (Bank Central Asia)</option>
                                    <option value="Mandiri">Mandiri</option>
                                    <option value="BRI">BRI</option>
                                    <option value="BNI">BNI</option>
                                    <option value="BSI">BSI (Bank Syariah)</option>
                                    <option value="QRIS">QRIS / Online</option>
                                    <option value="Cash To Collector">Cash to Collector</option>
                                    <option value="Transfer Bank">Transfer Bank Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Metode Pembayaran
                                </label>
                                <select name="metode_bayar"
                                        x-model="approveData.metode_bayar"
                                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#0891b2]">
                                    <option value="transfer">Transfer Bank (Tipe 2)</option>
                                    <option value="cash">Tunai / Cash (Tipe 3)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Catatan Verifikasi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Catatan Verifikasi (Opsional)
                            </label>
                            <input type="text"
                                   name="catatan"
                                   x-model="approveData.catatan"
                                   placeholder="Contoh: Mutasi BCA tgl 12/10 jam 14.05 verified"
                                   class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#0891b2]">
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="px-4 sm:px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button"
                                @click="approveModalOpen = false"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-2xs transition flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span>Setujui (LUNAS)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: REJECT / TOLAK PEMBAYARAN -->
    <div x-show="rejectModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="rejectModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="rejectModalOpen = false"
                 class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Body -->
            <div x-show="rejectModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full border border-slate-200 dark:border-slate-800">
                
                <form action="{{ route('finance.notifikasi.reject') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kode_billing" :value="rejectData.kode_billing">

                    <!-- Header Modal -->
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-rose-50 via-rose-50/50 to-white dark:from-rose-950/30 dark:via-slate-900 dark:to-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center ring-1 ring-rose-500/20">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tolak Pembayaran</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Bukti tidak valid / dana tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Fields -->
                    <div class="p-4 sm:p-5 space-y-3">
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                            <span class="text-slate-400 block font-bold text-[10px] uppercase">Kode Billing</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="rejectData.kode_billing"></span>
                            <p class="text-slate-600 dark:text-slate-300 mt-0.5 text-[11px]" x-text="'Pelanggan: ' + rejectData.nama_pelanggan"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Alasan Penolakan <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="alasan_tolak"
                                      rows="3"
                                      x-model="rejectData.alasan_tolak"
                                      required
                                      class="w-full px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="px-4 sm:px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button"
                                @click="rejectModalOpen = false"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-2xs transition cursor-pointer">
                            Tolak Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: LIGHTBOX PREVIEW BUKTI TRANSFER -->
    <div x-show="previewModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 p-4 text-center">
            <!-- Backdrop -->
            <div x-show="previewModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="previewModalOpen = false"
                 class="fixed inset-0 bg-slate-950/85 backdrop-blur-md transition-opacity"></div>

            <!-- Modal Content -->
            <div x-show="previewModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all max-w-xl w-full border border-slate-200 dark:border-slate-800 z-10">
                
                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="previewTitle"></h3>
                    <div class="flex items-center gap-1.5">
                        <a :href="previewImageUrl"
                           target="_blank"
                           download
                           class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                           title="Buka Gambar Asli">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                        <button type="button"
                                @click="previewModalOpen = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-3 flex items-center justify-center bg-slate-950 max-h-[70vh] overflow-auto">
                    <img :src="previewImageUrl" alt="Bukti Transfer" class="max-w-full max-h-[65vh] object-contain rounded-lg">
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
