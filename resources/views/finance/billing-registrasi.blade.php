@extends('layouts.app')

@section('title', 'Billing Registrasi - IMS Router')

@section('content')
<div class="space-y-6"
     x-data="{
         // Modal Detail Breakdown
         detailModalOpen: false,
         detailLoading: false,
         detailData: {
             billing: {},
             items: [],
             logs: []
         },
         async openDetailModal(kodeBilling) {
             this.detailLoading = true;
             this.detailModalOpen = true;
             try {
                 const res = await fetch('/finance/api/billing-registrasi-detail?kode_billing=' + encodeURIComponent(kodeBilling));
                 const data = await res.json();
                 this.detailData = data;
             } catch (e) {
                 console.error('Error fetch billing detail:', e);
             } finally {
                 this.detailLoading = false;
             }
         },
         openDetailModalFromEl(el) {
             this.openDetailModal(el.dataset.kode);
         },

         // Modal Change Payment Method
         changePayModalOpen: false,
         changePayKodeBilling: '',
         changePayNamaPelanggan: '',
         changePayType: '1',
         openChangePayModal(kodeBilling, nama, currentType) {
             this.changePayKodeBilling = kodeBilling;
             this.changePayNamaPelanggan = nama;
             this.changePayType = String(currentType || '1');
             this.changePayModalOpen = true;
         },
         openChangePayModalFromEl(el) {
             this.changePayKodeBilling = el.dataset.kode;
             this.changePayNamaPelanggan = el.dataset.nama;
             this.changePayType = String(el.dataset.paymentType || '1');
             this.changePayModalOpen = true;
         },

         // Format Currency Helper
         formatRupiah(num) {
             return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
         }
     }">

    <!-- Page Header & Action Bar (Deep Oceanic Teal & Cyan Gradient Matching Dashboard) -->
    <div class="ims-banner relative overflow-hidden rounded-2xl p-4 sm:p-6 shadow-md border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold bg-white text-slate-800 shadow-xs">
                        <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#00a8b5]"></span>
                        <span>Finance &amp; Billing</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-[#04333e]/85 text-emerald-300 border border-teal-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Registration Billing</span>
                    </div>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2" style="color: #FFFFFF !important;">
                    Billing Registrasi (Pasang Baru)
                </h1>
                <p class="text-xs sm:text-sm text-[#c6edf3] mt-1 max-w-2xl leading-relaxed" style="color: #C6EDF3 !important;">
                    Kelola tagihan biaya pendaftaran &amp; instalasi pelanggan baru, tentukan metode pembayaran, dan terbitkan (publish) tagihan ke Billing Layanan.
                </p>
            </div>

            <div class="flex items-center gap-2 relative z-10 w-full sm:w-auto pt-2 sm:pt-0">
                <a href="{{ route('finance.billing-registrasi.export', request()->query()) }}"
                   class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-semibold shadow-xs backdrop-blur-xs transition cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-300 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>
    </div>


    <!-- 4 KPI Summary Cards for Registration (2x2 on Mobile, 4 Cols on Desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        <!-- 1. Total Registrasi -->
        <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-md flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Registrasi</span>
                <div class="p-1.5 sm:p-2.5 rounded-lg sm:rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 flex-shrink-0">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 sm:mt-2.5">
                <div class="text-base sm:text-2xl font-black text-slate-900 dark:text-white leading-tight">{{ number_format($kpis['total'] ?? 0) }}</div>
                <span class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate block mt-0.5">Total pendaftaran baru</span>
            </div>
        </div>

        <!-- 2. Draft -->
        <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-md flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Draft Registrasi</span>
                <div class="p-1.5 sm:p-2.5 rounded-lg sm:rounded-xl bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20 flex-shrink-0">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 sm:mt-2.5">
                <div class="text-base sm:text-2xl font-black text-amber-600 dark:text-amber-400 leading-tight">{{ number_format($kpis['draft'] ?? 0) }}</div>
                <span class="text-[10px] sm:text-[11px] text-amber-700 dark:text-amber-400 font-medium truncate block mt-0.5">Belum di-publish</span>
            </div>
        </div>

        <!-- 3. Published -->
        <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-md flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Published</span>
                <div class="p-1.5 sm:p-2.5 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 flex-shrink-0">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 sm:mt-2.5">
                <div class="text-base sm:text-2xl font-black text-blue-600 dark:text-blue-400 leading-tight">{{ number_format($kpis['published'] ?? 0) }}</div>
                <span class="text-[10px] sm:text-[11px] text-blue-600 dark:text-blue-300 font-medium truncate block mt-0.5">Billing Layanan</span>
            </div>
        </div>

        <!-- 4. Metode Pembayaran -->
        <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-md flex flex-col justify-between">
            <div class="flex items-center justify-between gap-1">
                <span class="text-[10px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Metode Bayar</span>
                <div class="p-1.5 sm:p-2.5 rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 flex-shrink-0">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 sm:mt-2.5">
                <div class="text-xs sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-tight flex items-center gap-1 flex-wrap">
                    <span>{{ number_format($kpis['midtrans'] ?? 0) }}</span>
                    <span class="text-[10px] sm:text-xs font-normal text-slate-500 dark:text-slate-400">Online /</span>
                    <span>{{ number_format($kpis['manual'] ?? 0) }}</span>
                    <span class="text-[10px] sm:text-xs font-normal text-slate-500 dark:text-slate-400">Manual</span>
                </div>
                <span class="text-[10px] sm:text-[11px] text-emerald-700 dark:text-emerald-400 font-medium truncate block mt-0.5">Midtrans &bull; Manual</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-black/5 space-y-4">
        <form method="GET" action="{{ route('finance.billing-registrasi') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Layanan -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Kategori Layanan</label>
                <select name="layanan" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                    <option value="">Semua Layanan</option>
                    @foreach($layananList as $lay)
                    <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ $lay }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Tagihan -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Status Tagihan</label>
                <select name="status_bayar" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                    <option value="">Semua Status Bayar</option>
                    <option value="menunggu_verifikasi" {{ request('status_bayar') === 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    @foreach($statusBillRegList as $sb)
                        @php
                            $descLower = strtolower($sb->desc_bill_reg ?? '');
                        @endphp
                        @if(!str_contains($descLower, 'cancel midtrans') && !str_contains($descLower, 'expire midtrans') && !in_array((string)$sb->status_bill_reg, ['17', '18']))
                        <option value="{{ $sb->status_bill_reg }}" {{ request('status_bayar') === (string)$sb->status_bill_reg ? 'selected' : '' }}>{{ $sb->desc_bill_reg }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <!-- Wilayah -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Wilayah</label>
                <select name="wilayah" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                    <option value="">Semua Wilayah</option>
                    @foreach($wilayahList as $wil)
                    <option value="{{ $wil }}" {{ request('wilayah') === $wil ? 'selected' : '' }}>{{ $wil }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Pencarian -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Pencarian</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Nama / Kode REG..."
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 placeholder-slate-400 dark:placeholder-slate-500">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-md shadow-blue-500/25 transition cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('finance.billing-registrasi') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold border border-slate-300 dark:border-slate-700 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Registration Table -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-black/5 overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
            <div class="text-xs text-slate-600 dark:text-slate-400">
                Menampilkan <span class="text-slate-900 dark:text-white font-bold">{{ $registrations->firstItem() ?? 0 }}</span> - <span class="text-slate-900 dark:text-white font-bold">{{ $registrations->lastItem() ?? 0 }}</span> dari <span class="text-slate-900 dark:text-white font-bold">{{ $registrations->total() }}</span> pendaftaran
            </div>
        </div>

        <!-- Desktop View Table -->
        <div class="ims-desktop-only hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Billing Info</th>
                        <th class="py-3.5 px-4">Billing Date</th>
                        <th class="py-3.5 px-4">Amount</th>
                        <th class="py-3.5 px-4">Billing State</th>
                        <th class="py-3.5 px-4">Payment Method</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($registrations as $r)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                        <!-- 1. Billing Info -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="font-bold text-slate-900 dark:text-white tracking-wide">
                                {{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}
                            </div>
                            @if(!empty($r->nomor_internet))
                                <div class="font-mono text-[11px] mt-0.5">
                                    <a href="{{ route('teknik.pelanggan.profile', $r->nomor_internet) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline font-bold" title="Buka Profile Pelanggan">
                                        {{ $r->nomor_internet }}
                                    </a>
                                </div>
                            @endif
                            <div class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                {{ $r->nama_pelanggan }}
                                <span class="text-[10px] px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 ml-1">
                                    {{ $r->jenis_kelamin == 2 ? 'P' : 'L' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/20 text-[10px] font-semibold">
                                    {{ $r->nama_kategori_bandwith ?? 'BROADBAND' }} {{ $r->nominal_bandwith }} Mbps
                                </span>
                            </div>
                        </td>

                        <!-- 2. Tanggal Terbit -->
                        <td class="py-3.5 px-4 align-top">
                            @if($r->payment_publish)
                            <div class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                                {{ date('d M Y H:i', strtotime($r->payment_publish)) }}
                            </div>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                Billing Belum di Publish
                            </span>
                            @endif
                        </td>

                        <!-- 3. Amount -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-sm">
                                <span>Rp {{ number_format((float) ($r->total_reg ?? ($r->biaya_reg ?? 0)), 0, ',', '.') }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                        </td>

                        <!-- 4. Billing State -->
                        <td class="py-3.5 px-4 align-top">
                            @if($r->status_bill_reg == '12' || $r->payment_publish)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                                <span>PUBLISHED</span>
                            </span>
                            @elseif($r->status_bill_reg == '14')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                <span>PAID</span>
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                                <span>Draft Billing</span>
                            </span>
                            @endif
                        </td>

                        <!-- 5. Payment Method (Clickable to Change Payment Method) -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-1">
                                <button type="button"
                                        @click="openChangePayModalFromEl($el)"
                                        data-kode="{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}"
                                        data-nama="{{ $r->nama_pelanggan }}"
                                        data-payment-type="{{ $r->payment_type ?? 1 }}"
                                        title="Klik untuk memilih / mengubah metode pembayaran"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold shadow-sm transition cursor-pointer {{ ($r->payment_type ?? 1) == 1 ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/20' : (($r->payment_type ?? 1) == 2 ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20' : 'bg-amber-600 hover:bg-amber-500 text-white shadow-amber-600/20') }}">
                                    @if(($r->payment_type ?? 1) == 1)
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                    </svg>
                                    <span>Midtrans</span>
                                    @elseif($r->payment_type == 2)
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5M4.5 21V10.5" />
                                    </svg>
                                    <span>Manual Transfer</span>
                                    @elseif($r->payment_type == 3)
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <span>Cash To Collector</span>
                                    @else
                                    <span>Pilih Metode</span>
                                    @endif
                                </button>
                                
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 pl-1">
                                    <span class="{{ ($r->notif_wa ?? 0) > 0 ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ ($r->notif_wa ?? 0) > 0 ? 'WA: Sent' : 'UnSend' }}
                                    </span>
                                    <span>&bull;</span>
                                    <span class="{{ ($r->notif_mail ?? 0) > 0 ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ ($r->notif_mail ?? 0) > 0 ? 'Mail: Sent' : 'UnSend' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- 6. Action (Only Publish & Rincian) -->
                        <td class="py-3.5 px-4 align-middle text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if(in_array($r->status_bill_reg, ['11', '11.1']) || !$r->payment_publish)
                                <!-- Publish ke Billing Layanan -->
                                <form method="POST" action="{{ route('finance.billing-registrasi.publish.post') }}" onsubmit="return confirm('Publish tagihan registrasi ini ke Billing Layanan?')">
                                    @csrf
                                    <input type="hidden" name="kode_billing" value="{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}">
                                    <button type="submit"
                                            title="Publish ke Billing Layanan"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 dark:bg-emerald-600/20 dark:hover:bg-emerald-600 dark:text-emerald-400 dark:hover:text-white dark:border-emerald-500/30 text-xs font-bold transition shadow-sm cursor-pointer">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                        <span>Publish</span>
                                    </button>
                                </form>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 text-xs font-semibold">
                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>Published</span>
                                </span>
                                @endif

                                <!-- Rincian / Detail -->
                                <button type="button"
                                        @click="openDetailModalFromEl($el)"
                                        data-kode="{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}"
                                        title="Rincian Komponen Biaya Pasang Baru"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 hover:text-slate-900 dark:text-blue-400 dark:hover:text-blue-300 border border-slate-200 dark:border-slate-700 text-xs font-semibold transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Rincian</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">
                            Tidak ada data tagihan registrasi ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="ims-mobile-only block md:hidden p-3 sm:p-4 space-y-3.5">
            @forelse($registrations as $r)
            @php
                $custInitial = mb_substr($r->nama_pelanggan ?? 'P', 0, 1);
                $isPublished = $r->status_bill_reg == '12' || $r->payment_publish;
                $isPaid = $r->status_bill_reg == '14';
            @endphp
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md transition duration-200 space-y-3.5">
                <!-- Top Row: Avatar + Name + Status -->
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/20 uppercase">
                            {{ $custInitial }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm tracking-tight truncate uppercase leading-tight">
                                    {{ $r->nama_pelanggan }}
                                </h4>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $r->jenis_kelamin == 2 ? 'bg-pink-50 text-pink-700 border border-pink-200 dark:bg-pink-500/10 dark:text-pink-400 dark:border-pink-500/20' : 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20' }}">
                                    {{ $r->jenis_kelamin == 2 ? 'P' : ($r->jenis_kelamin == 1 ? 'L' : '-') }}
                                </span>
                            </div>
                            <div class="font-mono text-xs font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}</span>
                                @if(!empty($r->nomor_internet))
                                    <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                    <a href="{{ route('teknik.pelanggan.profile', $r->nomor_internet) }}" class="text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-300 hover:underline">
                                        #{{ $r->nomor_internet }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Status State Badge -->
                    <div class="shrink-0">
                        @if($isPublished)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/15 dark:text-blue-400 dark:border-blue-500/30 uppercase tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>PUBLISHED</span>
                        </span>
                        @elseif($isPaid)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 uppercase tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>PAID</span>
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 uppercase tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Draft</span>
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Middle Info Tiles -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <!-- Tile 1: Paket Layanan -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-1">
                        <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" /></svg>
                            <span>Paket Registrasi</span>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white truncate">
                            {{ $r->nama_kategori_bandwith ?? 'BROADBAND' }}
                        </div>
                        <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">
                            {{ $r->nominal_bandwith }} Mbps
                        </span>
                    </div>

                    <!-- Tile 2: Tanggal Terbit -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-1">
                        <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.253 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                            <span>Tanggal Terbit</span>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-[11px]">
                            {{ $r->payment_publish ? date('d M Y H:i', strtotime($r->payment_publish)) : 'Belum Terbit' }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">
                            Biaya Pemasangan Baru
                        </div>
                    </div>

                    <!-- Tile 3: Total Biaya Registrasi (Full Width) -->
                    <div class="col-span-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Biaya Registrasi</span>
                            <span class="text-base font-black text-slate-900 dark:text-white">
                                Rp {{ number_format((float) ($r->total_reg ?? ($r->biaya_reg ?? 0)), 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ (float)($r->total_reg ?? $r->biaya_reg ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800' }}">
                                {{ (float)($r->total_reg ?? $r->biaya_reg ?? 0) > 0 ? 'Berbayar' : 'Gratis / Promo' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Button & Notif Badges -->
                <div class="flex flex-wrap items-center justify-between gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80">
                    <button type="button"
                            @click="openChangePayModalFromEl($el)"
                            data-kode="{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}"
                            data-nama="{{ $r->nama_pelanggan }}"
                            data-payment-type="{{ $r->payment_type ?? 1 }}"
                            title="Klik untuk memilih / mengubah metode pembayaran"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer {{ ($r->payment_type ?? 1) == 1 ? 'bg-indigo-600 text-white' : (($r->payment_type ?? 1) == 2 ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white') }}">
                        <span>{{ ($r->payment_type ?? 1) == 1 ? 'Midtrans' : (($r->payment_type ?? 1) == 2 ? 'Manual Transfer' : 'Cash To Collector') }}</span>
                        <svg class="w-3 h-3 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                    </button>

                    <div class="flex items-center gap-1 text-[10px]">
                        <span class="px-2 py-0.5 rounded font-medium {{ ($r->notif_wa ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-200/70 text-slate-500 dark:bg-slate-800' }}">
                            WA: {{ ($r->notif_wa ?? 0) > 0 ? '✓' : '✗' }}
                        </span>
                        <span class="px-2 py-0.5 rounded font-medium {{ ($r->notif_mail ?? 0) > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-slate-200/70 text-slate-500 dark:bg-slate-800' }}">
                            Mail: {{ ($r->notif_mail ?? 0) > 0 ? '✓' : '✗' }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons: Publish & Rincian -->
                <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    @if(in_array($r->status_bill_reg, ['11', '11.1']) || !$r->payment_publish)
                    <form method="POST" action="{{ route('finance.billing-registrasi.publish.post') }}" class="flex-1 m-0 p-0" onsubmit="return confirm('Publish tagihan registrasi ini ke Billing Layanan?')">
                        @csrf
                        <input type="hidden" name="kode_billing" value="{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}">
                        <button type="submit"
                                class="w-full justify-center inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold transition shadow-sm cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                            <span>Publish</span>
                        </button>
                    </form>
                    @endif

                    <button type="button"
                            @click="openDetailModalFromEl($el)"
                            data-kode="{{ $r->kode_billing_registrasi ?? ('REG-' . $r->nomor_internet) }}"
                            class="flex-1 justify-center inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition border border-slate-200 dark:border-slate-700 shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <span>Rincian</span>
                    </button>
                </div>
            </div>
            @empty
            <div class="py-10 text-center text-slate-500 dark:text-slate-400 text-xs">
                Tidak ada data tagihan registrasi ditemukan.
            </div>
            @endforelse
        </div>

        @if($registrations->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40">
            {{ $registrations->links() }}
        </div>
        @endif
    </div>

    <!-- ======================================================================= -->
    <!-- MODALS SECTION                                                          -->
    <!-- ======================================================================= -->

    <!-- 1. MODAL DETAIL BREAKDOWN REGISTRASI -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="detailModalOpen = false"
             class="relative w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto max-h-[90vh] flex flex-col">
            
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Detail Tagihan Registrasi</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white font-mono" x-text="detailData.billing?.kode_billing_registrasi || 'Detail'"></h3>
                    </div>
                    <button @click="detailModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-show="detailLoading" class="py-10 text-center text-slate-500 dark:text-slate-400">
                    <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs mt-2">Memuat rincian registrasi...</p>
                </div>

                <div x-show="!detailLoading" class="space-y-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                        <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="detailData.billing?.nama_pelanggan"></div>
                        <div class="text-slate-500 dark:text-slate-400 mt-1" x-text="'Alamat Pasang: ' + (detailData.billing?.alamat_pasang || '-')"></div>
                        <div class="text-blue-600 dark:text-blue-400 font-medium mt-1" x-text="'Paket: ' + (detailData.billing?.nama_kategori_bandwith || '') + ' ' + (detailData.billing?.nominal_bandwith || '') + ' Mbps'"></div>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block mb-2">Komponen Biaya Registrasi:</span>
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Komponen</th>
                                        <th class="py-2.5 px-3 text-center">Qty</th>
                                        <th class="py-2.5 px-3 text-right">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="item in detailData.items" :key="item.kode_billing_detail">
                                        <tr>
                                            <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200" x-text="item.komponen"></td>
                                            <td class="py-2.5 px-3 text-center text-slate-500 dark:text-slate-400" x-text="item.qty"></td>
                                            <td class="py-2.5 px-3 text-right font-semibold text-slate-900 dark:text-white" x-text="formatRupiah(item.biaya)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- 2. MODAL CHANGE PAYMENT METHOD (MATCHING EXACT SCREENSHOT DESIGN) -->
    <div x-show="changePayModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="changePayModalOpen = false"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-registrasi.change-payment-method.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="changePayKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <!-- Title Label matching screenshot -->
                        <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">
                            Change Payment Method
                        </div>

                        <!-- 3 Radio Options matching screenshot -->
                        <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-700 dark:text-slate-200">
                            <!-- Option 1: Midtrans -->
                            <label class="inline-flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="payment_type" value="1" x-model="changePayType" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 cursor-pointer">
                                <span class="group-hover:text-blue-500 transition">Midtrans</span>
                            </label>

                            <!-- Option 2: Manual Transfer -->
                            <label class="inline-flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="payment_type" value="2" x-model="changePayType" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 cursor-pointer">
                                <span class="group-hover:text-blue-500 transition">Manual Transfer</span>
                            </label>

                            <!-- Option 3: Cash To Collector -->
                            <label class="inline-flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="payment_type" value="3" x-model="changePayType" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 cursor-pointer">
                                <span class="group-hover:text-blue-500 transition">Cash To Collector</span>
                            </label>
                        </div>

                        <!-- Buttons matching screenshot: Blue [Update] & Amber [Batal] -->
                        <div class="flex items-center gap-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-600 text-white text-xs font-bold shadow-md shadow-blue-700/20 transition cursor-pointer">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                </svg>
                                <span>Update</span>
                            </button>
                            <button type="button"
                                    @click="changePayModalOpen = false"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                                <span>Batal</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs flex flex-wrap items-center justify-between gap-2">
                        <span class="text-slate-500 dark:text-slate-400">Kode Registrasi: <strong class="text-slate-800 dark:text-white font-mono" x-text="changePayKodeBilling"></strong></span>
                        <span class="text-slate-500 dark:text-slate-400">Pelanggan: <strong class="text-blue-600 dark:text-blue-400" x-text="changePayNamaPelanggan"></strong></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
