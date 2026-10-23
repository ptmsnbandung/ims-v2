@extends('layouts.app')

@section('page_title', 'Notifikasi & Approval Pembayaran')

@section('content')
<div class="space-y-3.5 sm:space-y-4" x-data="{
    approveModalOpen: false,
    rejectModalOpen: false,
    previewModalOpen: false,
    previewImageUrl: '',
    previewTitle: '',
    previewItem: null,
    previewImgError: false,
    fallbackAttempted: false,

    // Read state synchronization
    readIds: [],

    init() {
        this.loadReadIds();
        window.addEventListener('ims-notifications-read-updated', () => {
            this.loadReadIds();
        });
    },

    loadReadIds() {
        try {
            this.readIds = JSON.parse(localStorage.getItem('ims_read_payment_ids') || '[]');
        } catch (e) {
            this.readIds = [];
        }
    },

    isUnread(id, kodeBilling = null) {
        if (!this.readIds || this.readIds.length === 0) return true;
        const sId = String(id || '');
        const sKode = String(kodeBilling || '');
        if (sId && this.readIds.includes(sId)) return false;
        if (sKode && this.readIds.includes(sKode)) return false;
        return true;
    },

    toggleRead(id, kodeBilling = null) {
        const sId = String(id || '');
        const sKode = String(kodeBilling || '');
        const isCurrentlyUnread = this.isUnread(sId, sKode);

        if (isCurrentlyUnread) {
            // Mark as read
            if (sId && !this.readIds.includes(sId)) this.readIds.push(sId);
            if (sKode && !this.readIds.includes(sKode)) this.readIds.push(sKode);
        } else {
            // Mark as unread
            this.readIds = this.readIds.filter(x => x !== sId && x !== sKode);
        }

        if (this.readIds.length > 400) {
            this.readIds = this.readIds.slice(-400);
        }

        localStorage.setItem('ims_read_payment_ids', JSON.stringify(this.readIds));
        window.dispatchEvent(new CustomEvent('ims-notifications-read-updated'));
    },

    markRead(id, kodeBilling = null) {
        const sId = String(id || '');
        const sKode = String(kodeBilling || '');
        let changed = false;
        if (sId && !this.readIds.includes(sId)) {
            this.readIds.push(sId);
            changed = true;
        }
        if (sKode && !this.readIds.includes(sKode)) {
            this.readIds.push(sKode);
            changed = true;
        }
        if (changed) {
            if (this.readIds.length > 400) {
                this.readIds = this.readIds.slice(-400);
            }
            localStorage.setItem('ims_read_payment_ids', JSON.stringify(this.readIds));
            window.dispatchEvent(new CustomEvent('ims-notifications-read-updated'));
        }
    },

    markAllAsReadPage() {
        const pageItems = [
            @foreach($notifications as $item)
                { id: '{{ $item->id }}', kode: '{{ $item->kode_billing }}' },
            @endforeach
        ];

        let changed = false;
        pageItems.forEach(item => {
            const sId = String(item.id || '');
            const sKode = String(item.kode || '');
            if (sId && !this.readIds.includes(sId)) {
                this.readIds.push(sId);
                changed = true;
            }
            if (sKode && !this.readIds.includes(sKode)) {
                this.readIds.push(sKode);
                changed = true;
            }
        });

        if (this.readIds.length > 400) {
            this.readIds = this.readIds.slice(-400);
        }

        localStorage.setItem('ims_read_payment_ids', JSON.stringify(this.readIds));
        window.dispatchEvent(new CustomEvent('ims-notifications-read-updated'));

        if (window.Toast) {
            window.Toast.fire({
                icon: 'success',
                title: 'Semua notifikasi di halaman ini berhasil ditandai telah dibaca'
            });
        }
    },
    
    previewItem: null,

    // Data form approval
    approveData: {
        kode_billing: '',
        nama_pelanggan: '',
        nomor_internet: '',
        nominal_bayar: 0,
        nominal_display: '',
        metode_bayar: 'transfer',
        bank_tujuan: 'BCA',
        nama_kolektor: '',
        no_kwitansi: '',
        catatan: '',
        destination_bank: ''
    },
    
    // Data form reject
    rejectData: {
        kode_billing: '',
        nama_pelanggan: '',
        alasan_tolak: 'Bukti transfer tidak valid atau dana belum masuk rekening.'
    },

    openApproveModal(item) {
        this.markRead(item.id || item.kode_billing, item.kode_billing);
        this.approveData.kode_billing = item.kode_billing || '';
        this.approveData.nama_pelanggan = item.nama_pelanggan || '';
        this.approveData.nomor_internet = item.nomor_internet || '';
        this.approveData.nominal_bayar = item.nominal > 0 ? item.nominal : '';
        this.approveData.nominal_display = item.nominal_formatted || '';
        this.approveData.nama_kolektor = '';
        this.approveData.no_kwitansi = '';

        const pType = String(item.payment_type || '2');
        const bankStr = String(item.bank_tujuan || '').trim();
        this.approveData.destination_bank = (bankStr && bankStr !== 'Transfer' && bankStr !== 'Transfer Manual') ? bankStr : '';

        if (pType === '3' || pType === 'cash' || bankStr.toLowerCase().includes('cash') || bankStr.toLowerCase().includes('kolektor') || bankStr.toLowerCase().includes('kasir')) {
            this.approveData.metode_bayar = 'cash';
            this.approveData.bank_tujuan = (this.approveData.destination_bank && !['cash to collector', 'transfer', 'transfer manual'].includes(this.approveData.destination_bank.toLowerCase())) ? this.approveData.destination_bank : 'CASH';
            this.approveData.catatan = 'Pembayaran Cash to Collector Terverifikasi';
        } else {
            this.approveData.metode_bayar = 'transfer';
            this.approveData.bank_tujuan = this.approveData.destination_bank || 'BCA';
            this.approveData.catatan = 'Pembayaran Transfer Terverifikasi';
        }

        this.approveModalOpen = true;
    },

    openRejectModal(item) {
        this.markRead(item.id || item.kode_billing, item.kode_billing);
        this.rejectData.kode_billing = item.kode_billing;
        this.rejectData.nama_pelanggan = item.nama_pelanggan;
        this.rejectData.alasan_tolak = 'Bukti transfer tidak valid atau dana belum masuk ke rekening perusahaan.';
        this.rejectModalOpen = true;
    },

    openPreviewModal(url, title, id = null, kode = null, item = null) {
        if (id || kode) {
            this.markRead(id, kode);
        }
        this.previewImageUrl = url;
        this.previewTitle = title;
        this.previewItem = item;
        this.previewImgError = false;
        this.fallbackAttempted = false;
        this.previewModalOpen = true;
    },

    handlePreviewError(event) {
        if (this.fallbackAttempted) {
            this.previewImgError = true;
            return;
        }
        this.fallbackAttempted = true;
        const src = this.previewImageUrl || '';
        if (src.includes('portal.ptmsn.co.id')) {
            this.previewImageUrl = src.replace('portal.ptmsn.co.id', 'ptmsn.co.id');
        } else if (src.includes('ptmsn.co.id')) {
            this.previewImageUrl = src.replace(/https?:\/\/(www\.)?ptmsn\.co\.id/, 'https://portal.ptmsn.co.id');
        } else {
            this.previewImgError = true;
        }
    }
}">

    <!-- Page Header & Action Bar (Deep Oceanic Teal & Cyan Gradient Matching Billing Layanan & Dashboard) -->
    <div class="ims-banner relative rounded-xl p-3.5 sm:p-4 shadow-sm border border-teal-500/20 z-30 overflow-hidden"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-xl">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        </div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold bg-white text-slate-800 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#00a8b5]"></span>
                        <span>Finance &amp; Approval</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-[#04333e]/85 text-emerald-300 border border-teal-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Payment Verification</span>
                    </div>
                </div>
                <h1 class="text-base sm:text-lg font-bold text-white tracking-tight flex items-center gap-2" style="color: #FFFFFF !important;">
                    Notifikasi Pembayaran Masuk
                </h1>
                <p class="text-xs text-[#c6edf3] mt-0.5 max-w-2xl leading-normal" style="color: #C6EDF3 !important;">
                    Pusat verifikasi dan approval transaksi pembayaran serta bukti transfer pelanggan.
                </p>
            </div>

            <!-- Top Right Actions -->
            <div class="flex flex-wrap items-center gap-2 relative z-10 w-full lg:w-auto pt-1 lg:pt-0">
                <!-- Tombol Tandai Semua Dibaca -->
                <button type="button"
                        @click="markAllAsReadPage()"
                        class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white border border-white/25 text-xs font-semibold flex items-center gap-1.5 shadow-2xs backdrop-blur-xs transition cursor-pointer"
                        title="Tandai semua notifikasi di halaman ini sebagai sudah dibaca">
                    <svg class="w-3.5 h-3.5 text-cyan-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5m-15 0 6 6 9-13.5" />
                    </svg>
                    <span>Tandai Semua Dibaca</span>
                </button>

                <!-- Tombol Link Billing Layanan -->
                <a href="{{ route('finance.billing-layanan') }}"
                   class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-semibold flex items-center gap-1.5 shadow-2xs backdrop-blur-xs transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-cyan-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25" />
                    </svg>
                    <span>Billing Layanan</span>
                </a>

                <!-- Tombol Refresh -->
                <a href="{{ route('finance.notifikasi') }}"
                   class="p-1.5 rounded-lg text-white/90 bg-white/10 hover:bg-white/20 border border-white/20 shadow-2xs backdrop-blur-xs transition cursor-pointer"
                   title="Refresh Data">
                    <svg class="w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </a>
            </div>
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
            <!-- Desktop Table View (Muncul hanya di Tablet & Desktop >= md) -->
            <div class="hidden md:block w-full overflow-x-auto">
                <table class="w-full text-left border-collapse table-fixed min-w-[700px] xl:min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-2.5 px-2.5 sm:px-3 w-[17%]">Status & Waktu</th>
                            <th class="py-2.5 px-2.5 sm:px-3 w-[17%]">Pelanggan</th>
                            <th class="py-2.5 px-2.5 sm:px-3 w-[17%]">Invoice / Tagihan</th>
                            <th class="py-2.5 px-2.5 sm:px-3 w-[11%]">Nominal</th>
                            <th class="py-2.5 px-2.5 sm:px-3 w-[14%]">Metode / Bank</th>
                            <th class="py-2.5 px-2 w-[8%] text-center">Bukti</th>
                            <th class="py-2.5 px-2.5 sm:px-3 w-[16%] text-right pr-3.5 sm:pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 text-xs">
                        @foreach($notifications as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition {{ !$item->is_approved ? 'bg-amber-50/30 dark:bg-amber-950/20' : '' }}"
                            :class="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}') ? 'bg-cyan-50/25 dark:bg-cyan-950/20' : ''">
                            
                            <!-- 1. Status & Waktu -->
                            <td class="py-2.5 px-2.5 sm:px-3 align-middle overflow-hidden">
                                <div class="flex flex-col gap-1 min-w-0 max-w-full">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        @if($item->is_approved)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9.5px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 w-fit truncate">
                                                <svg class="w-2.5 h-2.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span class="truncate">Lunas / Disetujui</span>
                                            </span>
                                        @elseif($item->status === 'rejected')
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9.5px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 w-fit truncate">
                                                <svg class="w-2.5 h-2.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span class="truncate">Ditolak</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9.5px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 w-fit animate-pulse truncate">
                                                <svg class="w-2.5 h-2.5 text-amber-600 dark:text-amber-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                <span class="truncate">Menunggu Approval</span>
                                            </span>
                                        @endif

                                        <!-- Badge Tanda Dibaca Interaktif Per Notif -->
                                        <button type="button"
                                                @click.stop="toggleRead('{{ $item->id }}', '{{ $item->kode_billing }}')"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold transition cursor-pointer max-w-fit"
                                                :class="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}')
                                                    ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/80 dark:text-cyan-300 ring-1 ring-cyan-400/40'
                                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800/80 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                                :title="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}') ? 'Status: Belum dibaca. Klik untuk tandai sudah dibaca' : 'Status: Sudah dibaca. Klik untuk tandai belum dibaca'">
                                            <template x-if="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}')">
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                                    <span>Baru</span>
                                                </span>
                                            </template>
                                            <template x-if="!isUnread('{{ $item->id }}', '{{ $item->kode_billing }}')">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-2.5 h-2.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5m-15 0 6 6 9-13.5" />
                                                    </svg>
                                                    <span>Dibaca</span>
                                                </span>
                                            </template>
                                        </button>
                                    </div>

                                    <span class="text-[9.5px] text-slate-500 dark:text-slate-400 font-medium truncate block" title="{{ $item->time_ago }} &middot; {{ $item->date }}">
                                        {{ $item->time_ago }} &middot; {{ $item->date }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Pelanggan -->
                            <td class="py-2.5 px-2.5 sm:px-3 align-middle overflow-hidden">
                                <div class="font-semibold text-xs text-slate-900 dark:text-white truncate max-w-full" title="{{ $item->nama_pelanggan }}">
                                    {{ $item->nama_pelanggan }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5 truncate max-w-full" title="{{ $item->nomor_internet }}">
                                    <svg class="w-2.5 h-2.5 text-[#0891b2] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <span class="truncate">{{ $item->nomor_internet }}</span>
                                </div>
                            </td>

                            <!-- 3. Invoice / Tagihan -->
                            <td class="py-2.5 px-2.5 sm:px-3 align-middle overflow-hidden">
                                <div class="flex items-center gap-1.5 flex-wrap max-w-full">
                                    <a href="{{ route('finance.billing-layanan', ['search' => $item->kode_billing]) }}"
                                       target="_blank"
                                       class="font-mono text-xs font-semibold text-[#0891b2] dark:text-cyan-400 hover:underline inline-flex items-center gap-0.5 truncate max-w-full"
                                       title="{{ $item->kode_billing }}">
                                       <span class="truncate">{{ $item->kode_billing }}</span>
                                        <svg class="w-2.5 h-2.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>

                                    @if(!empty($item->is_portal))
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60 shrink-0" title="Pembayaran melalui Portal Pelanggan">
                                            <svg class="w-2.5 h-2.5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                            </svg>
                                            <span>Portal Pelanggan</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700 shrink-0" title="Pembayaran di luar portal (Manual / Kasir / Kolektor)">
                                            <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Bukan Portal</span>
                                        </span>
                                    @endif
                                </div>
                                @if($item->notes)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 italic truncate block max-w-full mt-0.5" title="{{ $item->notes }}">
                                        "{{ $item->notes }}"
                                    </p>
                                @endif
                            </td>

                            <!-- 4. Nominal -->
                            <td class="py-2.5 px-2.5 sm:px-3 align-middle overflow-hidden">
                                <div class="font-bold text-xs truncate whitespace-nowrap {{ $item->is_approved ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}" title="{{ $item->nominal_formatted }}">
                                    {{ $item->nominal_formatted }}
                                </div>
                            </td>

                            <!-- 5. Metode / Bank -->
                            <td class="py-2.5 px-2.5 sm:px-3 align-middle overflow-hidden">
                                <div class="min-w-0 max-w-full">
                                    @php
                                        $rawBank = $item->bank_tujuan ?? 'Transfer';
                                        // Hapus teks atas nama (contoh: "(a.n. Media Solusi Network)", "(an. ...)", "a.n. ...", "atas nama ...")
                                        $cleanBank = preg_replace('/\s*\(\s*(?:a\.?n\.?|a\/n|atas\s+nama)\b[^)]*\)/i', '', $rawBank);
                                        $cleanBank = preg_replace('/\s*\b(?:a\.?n\.?|a\/n|atas\s+nama)\b.*$/i', '', $cleanBank);
                                        $cleanBank = trim($cleanBank);
                                        $cleanBank = preg_replace('/\s*-\s*$/', '', $cleanBank);

                                        // Normalisasi jika tipe bayar cash atau terdapat keterangan kolektor
                                        if (($item->payment_type ?? '') == '3' || stripos($cleanBank, 'collector') !== false || stripos($cleanBank, 'kasir') !== false) {
                                            $cleanBank = preg_replace('/^(?:bca|mandiri|bri|bni|bsi|permata|cimb|bjb|danamon|cash\s+to\s+collector)\s*\(/i', 'CASH (', $cleanBank);
                                            if (in_array(strtolower($cleanBank), ['cash to collector', 'kasir / tunai', 'tunai'])) {
                                                $cleanBank = 'CASH';
                                            }
                                        }

                                        $bankShort = Str::before($cleanBank, ' - ');
                                        $bankDetail = Str::contains($cleanBank, ' - ') ? trim(Str::after($cleanBank, ' - ')) : null;
                                        if ($bankDetail !== null && ($bankDetail === '' || $bankDetail === '-')) {
                                            $bankDetail = null;
                                        }
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-[10px] max-w-full truncate" title="{{ $cleanBank }}">
                                        <svg class="w-2.5 h-2.5 text-[#0891b2] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                        </svg>
                                        <span class="truncate">{{ $bankShort }}</span>
                                    </span>
                                    @if($bankDetail)
                                        <div class="text-[9px] text-slate-400 dark:text-slate-500 font-mono truncate block max-w-full mt-0.5" title="{{ $bankDetail }}">
                                            {{ $bankDetail }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- 6. Bukti Transfer -->
                            <td class="py-2.5 px-2 align-middle text-center overflow-hidden whitespace-nowrap">
                                @if($item->proof_file)
                                    @php
                                        $rawPf = (string)$item->proof_file;
                                        if (str_starts_with($rawPf, 'http://') || str_starts_with($rawPf, 'https://') || str_starts_with($rawPf, 'data:')) {
                                            $pfUrl = $rawPf;
                                        } else {
                                            $pfUrl = 'https://portal.ptmsn.co.id/' . ltrim($rawPf, '/');
                                        }
                                    @endphp
                                    <button type="button"
                                            @click="openPreviewModal('{{ $pfUrl }}', 'Bukti Transfer: {{ $item->kode_billing }}', '{{ $item->id }}', '{{ $item->kode_billing }}', {
                                                kode_billing: '{{ $item->kode_billing }}',
                                                nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}',
                                                nomor_internet: '{{ $item->nomor_internet }}',
                                                nominal: {{ $item->nominal }},
                                                nominal_formatted: '{{ $item->nominal_formatted }}',
                                                bank_tujuan: '{{ $bankShort }}',
                                                payment_type: '{{ $item->payment_type ?? '2' }}',
                                                is_approved: {{ $item->is_approved ? 'true' : 'false' }}
                                            })"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-[#0891b2] dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/40 hover:bg-cyan-100 dark:hover:bg-cyan-900/60 border border-cyan-200/60 dark:border-cyan-800/60 transition cursor-pointer shrink-0">
                                        <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        <span>Lihat</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 text-[10px] italic">Tanpa bukti</span>
                                @endif
                            </td>

                            <!-- 7. Aksi Approval -->
                            <td class="py-2.5 px-2.5 sm:px-3 pr-3.5 sm:pr-4 align-middle text-right overflow-hidden whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5 max-w-full">
                                    @if(auth()->user()?->isCeo())
                                        @if(!$item->is_approved && $item->status !== 'rejected')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                                                Menunggu Approval
                                            </span>
                                        @endif
                                    @elseif(!$item->is_approved)
                                        <!-- Tombol Approve -->
                                        <button type="button"
                                                @click="openApproveModal({
                                                    kode_billing: '{{ $item->kode_billing }}',
                                                    nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}',
                                                    nomor_internet: '{{ $item->nomor_internet }}',
                                                    nominal: {{ $item->nominal }},
                                                    nominal_formatted: '{{ $item->nominal_formatted }}',
                                                    bank_tujuan: '{{ $bankShort }}',
                                                    payment_type: '{{ $item->payment_type ?? '2' }}'
                                                })"
                                                class="px-2 py-0.5 rounded-md text-[11px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-2xs transition flex items-center gap-0.5 cursor-pointer shrink-0"
                                                title="Setujui Pembayaran">
                                            <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
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
                                                class="px-1.5 py-0.5 rounded-md text-[11px] font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition flex items-center gap-0.5 cursor-pointer shrink-0"
                                                title="Tolak Pembayaran">
                                            <svg class="w-2.5 h-2.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            <span>Tolak</span>
                                        </button>
                                    @else
                                        <!-- Status Disetujui / Terverifikasi -->
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 shrink-0"
                                              title="{{ $item->admin_notes ? $item->admin_notes : 'Telah diverifikasi oleh finance' }}">
                                            <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                            </svg>
                                            <span>Terverifikasi</span>
                                        </span>
                                    @endif
                                </div>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (Muncul hanya di Mobile < md) -->
            <div class="block md:hidden p-2.5 sm:p-3 space-y-2.5 bg-slate-50/50 dark:bg-slate-950/40">
                @foreach($notifications as $item)
                    @php
                        $rawBank = $item->bank_tujuan ?? 'Transfer';
                        $cleanBank = preg_replace('/\s*\(\s*(?:a\.?n\.?|a\/n|atas\s+nama)\b[^)]*\)/i', '', $rawBank);
                        $cleanBank = preg_replace('/\s*\b(?:a\.?n\.?|a\/n|atas\s+nama)\b.*$/i', '', $cleanBank);
                        $cleanBank = trim($cleanBank);
                        $cleanBank = preg_replace('/\s*-\s*$/', '', $cleanBank);

                        $bankShort = Str::before($cleanBank, ' - ');
                        $bankDetail = Str::contains($cleanBank, ' - ') ? trim(Str::after($cleanBank, ' - ')) : null;
                        if ($bankDetail !== null && ($bankDetail === '' || $bankDetail === '-')) {
                            $bankDetail = null;
                        }
                    @endphp
                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-2.5 transition {{ !$item->is_approved ? 'border-l-4 border-l-amber-500 bg-amber-50/15 dark:bg-amber-950/10' : 'border-l-4 border-l-emerald-500' }}"
                         :class="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}') ? 'ring-1 ring-cyan-400/30' : ''">

                        <!-- Card Header: Status & Time & Unread Badge -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($item->is_approved)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <svg class="w-2.5 h-2.5 text-emerald-600 dark:text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Lunas / Disetujui</span>
                                    </span>
                                @elseif($item->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        <svg class="w-2.5 h-2.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                        <span>Ditolak</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 animate-pulse">
                                        <svg class="w-2.5 h-2.5 text-amber-600 dark:text-amber-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <span>Menunggu Approval</span>
                                    </span>
                                @endif

                                <button type="button"
                                        @click.stop="toggleRead('{{ $item->id }}', '{{ $item->kode_billing }}')"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold transition cursor-pointer"
                                        :class="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}')
                                            ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/80 dark:text-cyan-300 ring-1 ring-cyan-400/40'
                                            : 'bg-slate-100 text-slate-500 dark:bg-slate-800/80 dark:text-slate-400'">
                                    <template x-if="isUnread('{{ $item->id }}', '{{ $item->kode_billing }}')">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                            <span>Baru</span>
                                        </span>
                                    </template>
                                    <template x-if="!isUnread('{{ $item->id }}', '{{ $item->kode_billing }}')">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5m-15 0 6 6 9-13.5" />
                                            </svg>
                                            <span>Dibaca</span>
                                        </span>
                                    </template>
                                </button>
                            </div>

                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono whitespace-nowrap">
                                {{ $item->time_ago }}
                            </span>
                        </div>

                        <!-- Card Main Info: Invoice & Nominal -->
                        <div class="flex items-start justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800/60">
                            <div class="min-w-0">
                                <span class="text-[9.5px] text-slate-400 dark:text-slate-500 block uppercase font-bold tracking-wider">Invoice / Tagihan</span>
                                <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                    <a href="{{ route('finance.billing-layanan', ['search' => $item->kode_billing]) }}"
                                       target="_blank"
                                       class="font-mono text-xs font-bold text-[#0891b2] dark:text-cyan-400 hover:underline inline-flex items-center gap-1">
                                        <span>{{ $item->kode_billing }}</span>
                                        <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>

                                    @if(!empty($item->is_portal))
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60 shrink-0">
                                            <svg class="w-2.5 h-2.5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                            </svg>
                                            <span>Portal Pelanggan</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700 shrink-0">
                                            <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Bukan Portal</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[9.5px] text-slate-400 dark:text-slate-500 block uppercase font-bold tracking-wider">Nominal</span>
                                <span class="font-bold text-sm font-mono mt-0.5 block {{ $item->is_approved ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ $item->nominal_formatted }}
                                </span>
                            </div>
                        </div>

                        <!-- Pelanggan & Metode Info -->
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-800 dark:text-slate-200 truncate">
                                    {{ $item->nama_pelanggan }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-[#0891b2] shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <span>{{ $item->nomor_internet }}</span>
                                </div>
                            </div>
                            
                            <!-- Metode / Bank -->
                            <div class="text-right shrink-0">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-semibold text-[10px] border border-slate-200 dark:border-slate-700">
                                    <svg class="w-3 h-3 text-[#0891b2] shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                    </svg>
                                    <span>{{ $bankShort }}</span>
                                </span>
                                @if($bankDetail)
                                    <div class="text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                        {{ $bankDetail }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($item->notes)
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 italic px-1">
                                "{{ $item->notes }}"
                            </p>
                        @endif

                        <!-- Card Action Footer: Bukti & Approval Buttons -->
                        <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-slate-100 dark:border-slate-800/60">
                            <!-- Bukti Button -->
                            <div>
                                @if($item->proof_file)
                                    @php
                                        $rawPf = (string)$item->proof_file;
                                        if (str_starts_with($rawPf, 'http://') || str_starts_with($rawPf, 'https://') || str_starts_with($rawPf, 'data:')) {
                                            $pfUrl = $rawPf;
                                        } else {
                                            $pfUrl = 'https://portal.ptmsn.co.id/' . ltrim($rawPf, '/');
                                        }
                                    @endphp
                                    <button type="button"
                                            @click="openPreviewModal('{{ $pfUrl }}', 'Bukti: {{ $item->kode_billing }}', '{{ $item->id }}', '{{ $item->kode_billing }}', {
                                                kode_billing: '{{ $item->kode_billing }}',
                                                nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}',
                                                nomor_internet: '{{ $item->nomor_internet }}',
                                                nominal: {{ $item->nominal }},
                                                nominal_formatted: '{{ $item->nominal_formatted }}',
                                                bank_tujuan: '{{ $bankShort }}',
                                                payment_type: '{{ $item->payment_type ?? '2' }}',
                                                is_approved: {{ $item->is_approved ? 'true' : 'false' }}
                                            })"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-[#0891b2] dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/40 hover:bg-cyan-100 dark:hover:bg-cyan-900/60 border border-cyan-200/60 dark:border-cyan-800/60 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        <span>Lihat Bukti</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 text-[10px] italic">Tanpa bukti</span>
                                @endif
                            </div>

                            <!-- Approval Buttons -->
                            <div class="flex items-center gap-1.5">
                                @if(auth()->user()?->isCeo())
                                    @if(!$item->is_approved && $item->status !== 'rejected')
                                        <span class="px-2 py-1 rounded-lg text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                                            Menunggu Approval
                                        </span>
                                    @endif
                                @elseif(!$item->is_approved)
                                    <button type="button"
                                            @click="openApproveModal({
                                                kode_billing: '{{ $item->kode_billing }}',
                                                nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}',
                                                nomor_internet: '{{ $item->nomor_internet }}',
                                                nominal: {{ $item->nominal }},
                                                nominal_formatted: '{{ $item->nominal_formatted }}',
                                                bank_tujuan: '{{ $bankShort }}',
                                                payment_type: '{{ $item->payment_type ?? '2' }}'
                                            })"
                                            class="px-3 py-1 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition flex items-center gap-1 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Approve</span>
                                    </button>

                                    <button type="button"
                                            @click="openRejectModal({
                                                kode_billing: '{{ $item->kode_billing }}',
                                                nama_pelanggan: '{{ addslashes($item->nama_pelanggan) }}'
                                            })"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition flex items-center gap-1 cursor-pointer">
                                        <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                        <span>Tolak</span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Terverifikasi</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
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

    <!-- MODAL 1: APPROVE PEMBAYARAN (MIRROR BILLING LAYANAN) -->
    <div x-show="approveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="approveModalOpen = false"
             class="relative w-full max-w-lg md:max-w-2xl lg:max-w-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto transition-all duration-200">
            
            <form action="{{ route('finance.notifikasi.approve') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="kode_billing" :value="approveData.kode_billing">
                <input type="hidden" name="nama_pelanggan" :value="approveData.nama_pelanggan">
                <input type="hidden" name="nomor_internet" :value="approveData.nomor_internet">

                <!-- Modal Header (Sidebar Blue Theme) -->
                <div class="px-6 py-4 bg-[#061d28] border-b border-[#0d2a38] flex items-center justify-between text-white">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-cyan-500/15 text-cyan-400 border border-cyan-500/30">
                            <template x-if="approveData.metode_bayar === 'cash'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </template>
                            <template x-if="approveData.metode_bayar !== 'cash'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </template>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white tracking-wide" x-text="approveData.metode_bayar === 'cash' ? 'Konfirmasi Bayar: Cash To Collector' : 'Konfirmasi Bayar: Manual Transfer'"></h3>
                            <p class="text-xs text-slate-300">Verifikasi pelunasan tagihan invoice pelanggan</p>
                        </div>
                    </div>
                    <button type="button" @click="approveModalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 md:p-7 space-y-4">
                    <!-- Info Ringkasan & Nominal Diterima (2-Column Grid on Desktop) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <!-- Info Ringkasan Invoice -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-2 flex flex-col justify-center">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 dark:text-slate-400">No. Invoice:</span>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm" x-text="approveData.kode_billing"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 dark:text-slate-400">Pelanggan:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400 text-xs truncate max-w-[180px]" x-text="approveData.nama_pelanggan + (approveData.nomor_internet ? ' (' + approveData.nomor_internet + ')' : '')"></span>
                            </div>
                        </div>

                        <!-- Nominal Diterima -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nominal Diterima (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="nominal_bayar" x-model="approveData.nominal_bayar" required min="1" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-emerald-600 dark:text-emerald-400 font-bold text-lg rounded-xl px-3.5 py-2.5 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Pilihan Metode Bayar (Radio Tabs) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="approveData.metode_bayar === 'cash' ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-300 dark:border-amber-500/50 text-amber-700 dark:text-amber-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'">
                                 <input type="radio" name="metode_bayar" value="cash" x-model="approveData.metode_bayar"
                                        @change="approveData.bank_tujuan = 'CASH'; approveData.catatan = 'Pembayaran Cash to Collector Terverifikasi'"
                                        class="w-4 h-4 text-amber-500 focus:ring-amber-400">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Cash To Collector</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Tunai / Kasir Lapangan</div>
                                </div>
                            </label>

                            <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="approveData.metode_bayar === 'transfer' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-300 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'">
                                <input type="radio" name="metode_bayar" value="transfer" x-model="approveData.metode_bayar"
                                        @change="approveData.bank_tujuan = (approveData.destination_bank || 'BCA'); approveData.catatan = 'Pembayaran Transfer Terverifikasi'"
                                        class="w-4 h-4 text-emerald-500 focus:ring-emerald-400">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Manual Transfer</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Transfer Bank Perusahaan</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dynamic Fields: Jika CASH TO COLLECT -->
                    <div x-show="approveData.metode_bayar === 'cash'" class="space-y-3 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-500/5 border border-amber-200 dark:border-amber-500/15">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tipe Kasir / Kolektor</label>
                                <select name="bank_tujuan" :disabled="approveData.metode_bayar !== 'cash'" x-model="approveData.bank_tujuan" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                                    <option value="CASH">Cash To Collector</option>
                                    <option value="Kasir Kantor Pusat">Kasir Kantor Pusat</option>
                                    <option value="Kasir Cabang">Kasir Cabang</option>
                                    <option value="Kolektor Lapangan">Kolektor Lapangan</option>
                                    <option value="Kasir / Tunai">Kasir / Tunai</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Petugas Kolektor <span class="text-slate-500 font-normal">(Opsional)</span></label>
                                <input type="text" name="nama_kolektor" :disabled="approveData.metode_bayar !== 'cash'" x-model="approveData.nama_kolektor" placeholder="Nama Petugas..." class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">No. Kwitansi / Tanda Terima <span class="text-slate-500 font-normal">(Opsional)</span></label>
                                <input type="text" name="no_kwitansi" :disabled="approveData.metode_bayar !== 'cash'" x-model="approveData.no_kwitansi" placeholder="Contoh: KWT-00123" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields: Jika MANUAL TRANSFER -->
                    <div x-show="approveData.metode_bayar === 'transfer'" class="space-y-3 p-3.5 rounded-xl bg-blue-50 dark:bg-blue-500/5 border border-blue-200 dark:border-blue-500/15">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between">
                                    <span>Rekening Bank Tujuan</span>
                                    <template x-if="approveData.destination_bank">
                                        <span class="text-[10px] text-blue-600 dark:text-cyan-400 font-normal">(Terisi Otomatis)</span>
                                    </template>
                                </label>
                                <select name="bank_tujuan" :disabled="approveData.metode_bayar !== 'transfer'" x-model="approveData.bank_tujuan" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                                    <template x-if="approveData.bank_tujuan && !['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'Permata', 'Cash To Collector', 'CASH', ''].includes(approveData.bank_tujuan)">
                                        <option :value="approveData.bank_tujuan" x-text="approveData.bank_tujuan" selected></option>
                                    </template>
                                    <option value="BCA">BCA (PT Medianet)</option>
                                    <option value="Mandiri">Bank Mandiri</option>
                                    <option value="BRI">Bank BRI</option>
                                    <option value="BNI">Bank BNI</option>
                                    <option value="BSI">Bank Syariah Indonesia (BSI)</option>
                                    <option value="Permata">Bank Permata</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">No. Ref Transfer / Rek Pengirim <span class="text-slate-500 font-normal">(Opsional)</span></label>
                                <input type="text" name="no_kwitansi" :disabled="approveData.metode_bayar !== 'transfer'" x-model="approveData.no_kwitansi" placeholder="Contoh: REF123456 / A.N Budi" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Verifikasi & Foto Bukti Transfer (2-Column Grid on Desktop) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <!-- Catatan Verifikasi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Catatan Verifikasi</label>
                            <input type="text" name="catatan" x-model="approveData.catatan" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3.5 py-2.5 focus:border-emerald-500">
                        </div>

                        <!-- Foto Bukti Transfer (Upload Bukti Transfer) -->
                        <div class="space-y-1.5" x-data="{
                            isDragging: false,
                            fotoBuktiPreview: null,
                            fileName: '',
                            fileSize: '',

                            handleFile(file) {
                                if (!file) return;
                                if (!file.type.match('image.*') && file.type !== 'application/pdf') {
                                    alert('Silakan upload file foto (JPG, PNG, WebP) atau PDF bukti transfer.');
                                    return;
                                }
                                this.fileName = file.name;
                                this.fileSize = (file.size / 1024 > 1024) 
                                    ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' 
                                    : Math.round(file.size / 1024) + ' KB';

                                if (file.type.match('image.*')) {
                                    this.fotoBuktiPreview = URL.createObjectURL(file);
                                } else {
                                    this.fotoBuktiPreview = 'pdf';
                                }
                            },

                            handleDrop(e) {
                                this.isDragging = false;
                                const files = e.dataTransfer.files;
                                if (files && files.length > 0) {
                                    const file = files[0];
                                    const input = this.$refs.notifApproveFileInput;
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    input.files = dataTransfer.files;
                                    this.handleFile(file);
                                }
                            },

                            clearFile() {
                                this.fotoBuktiPreview = null;
                                this.fileName = '';
                                this.fileSize = '';
                                if (this.$refs.notifApproveFileInput) {
                                    this.$refs.notifApproveFileInput.value = '';
                                }
                            }
                        }">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Upload Bukti Transfer <span class="text-slate-400 font-normal text-[11px]">(Drag &amp; Drop / Pilih File)</span>
                                </label>
                                <template x-if="fotoBuktiPreview">
                                    <button type="button" @click="clearFile()" class="text-[11px] text-rose-500 hover:text-rose-600 font-semibold flex items-center gap-0.5 cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span>Hapus File</span>
                                    </button>
                                </template>
                            </div>

                            <div class="relative border-2 border-dashed rounded-xl p-2.5 text-center transition-all duration-200 cursor-pointer overflow-hidden min-h-[58px] flex items-center justify-center"
                                 :class="isDragging 
                                     ? 'border-[#0891b2] bg-cyan-50/80 dark:bg-cyan-950/40 ring-2 ring-cyan-400/30 scale-[1.01]' 
                                     : (fotoBuktiPreview ? 'border-emerald-400 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 hover:border-[#0891b2] dark:hover:border-cyan-400')"
                                 @dragover.prevent="isDragging = true"
                                 @dragenter.prevent="isDragging = true"
                                 @dragleave.prevent="isDragging = false"
                                 @drop.prevent="handleDrop($event)">

                                <input type="file"
                                       x-ref="notifApproveFileInput"
                                       name="foto_bukti"
                                       accept="image/*,application/pdf"
                                       capture="environment"
                                       @change="handleFile($event.target.files[0])"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">

                                <!-- State Tanpa File -->
                                <template x-if="!fotoBuktiPreview">
                                    <div class="flex items-center justify-center gap-2 pointer-events-none text-slate-500 dark:text-slate-400 py-1">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center transition-transform duration-200 flex-shrink-0"
                                             :class="isDragging ? 'bg-[#0891b2] text-white scale-110' : 'bg-cyan-500/10 text-[#0891b2] dark:text-cyan-400'">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200"
                                               x-text="isDragging ? 'Lepaskan file foto di sini...' : 'Tarik & lepas foto di sini'"></p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500">atau <span class="text-[#0891b2] dark:text-cyan-400 font-semibold underline">klik browse file</span></p>
                                        </div>
                                    </div>
                                </template>

                                <!-- State Ada File -->
                                <template x-if="fotoBuktiPreview">
                                    <div class="py-0.5 flex items-center justify-center gap-2.5 w-full">
                                        <template x-if="fotoBuktiPreview !== 'pdf'">
                                            <div class="relative rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 max-h-16 max-w-[80px] bg-slate-900/5 flex items-center justify-center flex-shrink-0">
                                                <img :src="fotoBuktiPreview" class="object-contain max-h-16 rounded shadow-xs">
                                            </div>
                                        </template>
                                        <template x-if="fotoBuktiPreview === 'pdf'">
                                            <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center border border-rose-200 flex-shrink-0">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            </div>
                                        </template>
                                        <div class="text-left min-w-0 flex-1">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate block max-w-[170px]" x-text="fileName"></span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="fileSize"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="approveModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-500/25 flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Konfirmasi & Approve Lunas</span>
                    </button>
                </div>
            </form>
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

                <div class="p-3 flex items-center justify-center bg-slate-950 min-h-[160px] max-h-[70vh] overflow-auto">
                    <img :src="previewImageUrl" 
                         alt="Bukti Transfer" 
                         class="max-w-full max-h-[65vh] object-contain rounded-lg shadow-sm"
                         x-show="!previewImgError"
                         @load="previewImgError = false"
                         @error="handlePreviewError($event)">

                    <div x-show="previewImgError" class="p-6 text-center space-y-2">
                        <div class="w-10 h-10 mx-auto rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-200">File bukti transfer tidak ditemukan</p>
                        <p class="text-[11px] text-slate-400 font-mono truncate max-w-sm mx-auto" x-text="previewImageUrl"></p>
                    </div>
                </div>

                <!-- Footer with direct Approve button if not yet approved -->
                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <button type="button" @click="previewModalOpen = false" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer transition">
                        Tutup
                    </button>
                    <template x-if="previewItem && !previewItem.is_approved">
                        <button type="button"
                                @click="const itm = previewItem; previewModalOpen = false; openApproveModal(itm)"
                                class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span>Konfirmasi & Approve Pembayaran</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
