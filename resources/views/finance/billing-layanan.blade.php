@extends('layouts.app')

@section('title', 'Billing Layanan - IMS Router')

@php
    $pageInvoices = [];
    if (isset($invoices)) {
        foreach ($invoices as $inv) {
            $statusDesc = $inv->desc_bill_lay ?? '';
            if (empty($statusDesc)) {
                if (($inv->status_bill_lay ?? '') == '15') $statusDesc = 'Lunas';
                elseif (($inv->status_bill_lay ?? '') == '13') $statusDesc = 'Published';
                elseif (($inv->status_bill_lay ?? '') == '14') $statusDesc = 'Menunggu Verifikasi';
                else $statusDesc = 'Draft';
            }
            $pageInvoices[] = [
                'kode_billing_layanan' => $inv->kode_billing_layanan ?? '',
                'nomor_internet' => $inv->nomor_internet ?? '-',
                'nama_pelanggan' => $inv->nama_pelanggan ?? 'Pelanggan',
                'status_bill_lay' => (string)($inv->status_bill_lay ?? ''),
                'status_desc' => $statusDesc,
            ];
        }
    }
@endphp

@section('content')
<div class="space-y-6" x-data="billingLayananPage()">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/80 backdrop-blur-xl p-5 rounded-2xl border border-slate-200 dark:border-slate-800/80 shadow-xl shadow-black/5 relative z-10">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl pointer-events-none overflow-hidden"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <span>Finance &amp; Billing</span>
                <span>&bull;</span>
                <span class="text-slate-500 dark:text-slate-400">Recurring Invoicing</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                Billing Layanan Bulanan
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                Penerbitan tagihan berkala, pemantauan pembayaran pelanggan, integrasi payment gateway, dan konfirmasi kas masuk.
            </p>
        </div>

        <!-- Top Right Actions -->
        <div class="flex items-center gap-2.5 relative z-10 shrink-0">
            <!-- Dropdown Menu Aksi -->
            <div class="relative" @click.outside="actionDropdownOpen = false">
                <button @click="actionDropdownOpen = !actionDropdownOpen"
                        type="button"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 text-xs font-semibold flex items-center gap-2 shadow-sm transition cursor-pointer relative"
                        :class="{ 'ring-2 ring-blue-500/30 border-blue-400': actionDropdownOpen }">
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <span>Menu Aksi</span>
                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': actionDropdownOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>

                    <!-- Pulse indicator dot if requestCount > 0 -->
                    <span x-show="requestCount > 0" class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-violet-600 text-[9px] font-bold text-white items-center justify-center" x-text="requestCount"></span>
                    </span>
                </button>

                <!-- Dropdown Menu items -->
                <div x-show="actionDropdownOpen"
                     x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-60 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl shadow-black/10 py-1.5 z-50 divide-y divide-slate-100 dark:divide-slate-800/60 focus:outline-none">
                    
                    <div class="py-1">
                        <!-- Request Pelanggan Item -->
                        <button type="button"
                                @click="openRequestModal()"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-violet-50 dark:hover:bg-violet-950/40 hover:text-violet-600 dark:hover:text-violet-400 transition cursor-pointer text-left group">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <span>Request Pelanggan</span>
                            </div>
                            <span x-show="requestCount > 0"
                                  class="px-2 py-0.5 rounded-full bg-violet-600 text-white font-bold text-[10px] animate-pulse"
                                  x-text="requestCount"></span>
                        </button>

                        <!-- Cetak Massal (Print Invoices) -->
                        <button type="button"
                                @click="openBatchPrintModal()"
                                class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-cyan-50 dark:hover:bg-cyan-950/40 hover:text-cyan-600 dark:hover:text-cyan-400 transition cursor-pointer text-left group">
                            <div class="w-7 h-7 rounded-lg bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                                <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <rect x="6" y="14" width="12" height="8"></rect>
                                </svg>
                            </div>
                            <span>Print Invoices</span>
                        </button>
                    </div>

                    <div class="py-1">
                        <!-- Export CSV -->
                        <a href="{{ route('finance.billing-layanan.export', request()->query()) }}"
                           @click="actionDropdownOpen = false"
                           class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 dark:hover:text-emerald-400 transition group">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                            <span>Export CSV</span>
                        </a>

                        @if(auth()->user()?->isAdmin() || auth()->user()?->isDirektur())
                        <!-- Broadcast WA -->
                        <a href="{{ route('admin.broadcast') }}"
                           @click="actionDropdownOpen = false"
                           class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 dark:hover:text-emerald-400 transition group">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                            </div>
                            <span>Broadcast WA (Jatuh Tempo)</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Primary Action: Generate Invoice -->
            <button @click="openGenerateModal()"
                    type="button"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-semibold flex items-center gap-2 shadow-lg shadow-blue-500/25 border border-blue-400/20 transition duration-150 cursor-pointer shrink-0">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Generate Invoice</span>
            </button>
        </div>
    </div>

    <!-- Quick Alert Banner Request Pelanggan -->
    <template x-if="requestCount > 0">
        <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-gradient-to-r from-violet-600/15 via-purple-600/10 to-indigo-600/15 border border-violet-500/30 text-xs shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 animate-bounce">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                        Terdapat <span class="text-violet-600 dark:text-violet-400 font-extrabold" x-text="requestCount"></span> Permintaan Tagihan dari Portal Pelanggan
                    </h4>
                    <p class="text-slate-600 dark:text-slate-400 text-[11px] mt-0.5">
                        Pelanggan telah mengajukan permintaan penerbitan invoice bulan berikutnya secara mandiri.
                    </p>
                </div>
            </div>
            <button type="button"
                    @click="openRequestModal()"
                    class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shrink-0 shadow-md shadow-violet-600/20 transition cursor-pointer flex items-center gap-1.5">
                <span>Review &amp; Proses</span>
                <span>&rarr;</span>
            </button>
        </div>
    </template>

    <!-- 4 KPI Financial Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Generating / Draft (Yellow / Amber) -->
        <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-slate-900/90 border border-amber-200 dark:border-amber-500/20 shadow-lg relative overflow-hidden group hover:border-amber-400 dark:hover:border-amber-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-amber-700 dark:text-amber-400 tracking-wide uppercase">Draft / Auto Publish</span>
                <span class="p-2 rounded-xl bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Rp {{ number_format($kpis['generating']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-amber-700/90 dark:text-amber-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                    <span>{{ number_format($kpis['generating']['count']) }} Invoice Menunggu Terbit</span>
                </div>
            </div>
        </div>

        <!-- 2. Publish Billing (Blue) -->
        <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-slate-900/90 border border-blue-200 dark:border-blue-500/20 shadow-lg relative overflow-hidden group hover:border-blue-400 dark:hover:border-blue-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-blue-700 dark:text-blue-400 tracking-wide uppercase">Publish Billing</span>
                <span class="p-2 rounded-xl bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-300 dark:border-blue-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Rp {{ number_format($kpis['publish']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-blue-700/90 dark:text-blue-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span>{{ number_format($kpis['publish']['count']) }} Invoice Terbit Aktif</span>
                </div>
            </div>
        </div>

        <!-- 3. Waiting Payment (Teal / Cyan) -->
        <div class="p-4 rounded-2xl bg-cyan-50/70 dark:bg-slate-900/90 border border-cyan-200 dark:border-cyan-500/20 shadow-lg relative overflow-hidden group hover:border-cyan-400 dark:hover:border-cyan-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-cyan-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-cyan-700 dark:text-cyan-400 tracking-wide uppercase">Waiting Payment</span>
                <span class="p-2 rounded-xl bg-cyan-100 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border border-cyan-300 dark:border-cyan-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Rp {{ number_format($kpis['waiting']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-cyan-700/90 dark:text-cyan-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                    <span>{{ number_format($kpis['waiting']['count']) }} Invoice Menunggu Pelunasan</span>
                </div>
            </div>
        </div>

        <!-- 4. Paid (Kas Masuk) -->
        <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-slate-900/90 border border-emerald-200 dark:border-emerald-500/20 shadow-lg relative overflow-hidden group hover:border-emerald-400 dark:hover:border-emerald-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 tracking-wide uppercase">Paid (Kas Masuk)</span>
                <span class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043A3.746 3.746 0 0 1 21 12Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Rp {{ number_format($kpis['paid']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-700/90 dark:text-emerald-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>{{ number_format($kpis['paid']['count']) }} Invoice Telah Terbayar</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-black/5 space-y-4">
        <form method="GET" action="{{ route('finance.billing-layanan') }}" class="space-y-4">
            <!-- Row 1: Primary Quick Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Bulan -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Semua Bulan</label>
                    <select name="bulan" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanList as $key => $name)
                        <option value="{{ $key }}" {{ ($selectedBulan ?? '') === (string)$key ? 'selected' : '' }}>{{ $key }} - {{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tahun -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Semua Tahun</label>
                    <select name="tahun" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Tahun</option>
                        @foreach($tahunList as $thn)
                        <option value="{{ $thn }}" {{ ($selectedTahun ?? '') === (string)$thn ? 'selected' : '' }}>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Layanan / Kategori -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Kategori Layanan</label>
                    <select name="layanan" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Layanan</option>
                        @foreach($layananList as $lay)
                        <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ $lay }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Bayar -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Status Bayar</label>
                    <select name="status_bayar" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Status Bayar</option>
                        <option value="menunggu_verifikasi" {{ request('status_bayar') === 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        @foreach($statusBillList as $sb)
                            @php
                                $descLower = strtolower($sb->desc_bill_lay ?? '');
                            @endphp
                            @if(!str_contains($descLower, 'cancel midtrans') && !str_contains($descLower, 'expire midtrans') && !in_array((string)$sb->status_bill_lay, ['17', '18']))
                            <option value="{{ $sb->status_bill_lay }}" {{ request('status_bayar') === (string)$sb->status_bill_lay ? 'selected' : '' }}>{{ $sb->desc_bill_lay }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Real-time Search Box -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Pencarian</label>
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Nama / No Layanan / Inv..."
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder-slate-400 dark:placeholder-slate-500">
                        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Row 2: Secondary / Advanced Filters (Collapsible) -->
            <div x-show="showAdvancedFilters" x-cloak x-collapse class="pt-3 border-t border-slate-200 dark:border-slate-800/80">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Wilayah / Kota -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Semua Wilayah</label>
                        <select name="wilayah" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                            <option value="">Semua Wilayah</option>
                            @foreach($wilayahList as $wil)
                            <option value="{{ $wil }}" {{ request('wilayah') === $wil ? 'selected' : '' }}>{{ $wil }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status User -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Status User</label>
                        <select name="status_user" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                            <option value="">Semua Status User</option>
                            @foreach($statusUserList as $code => $label)
                            <option value="{{ $code }}" {{ request('status_user') === (string)$code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Metode Bayar -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Metode Bayar</label>
                        <select name="metode_bayar" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                            <option value="">Semua Metode Bayar</option>
                            <option value="1" {{ request('metode_bayar') === '1' ? 'selected' : '' }}>Midtrans Payment Gateway</option>
                            <option value="2" {{ request('metode_bayar') === '2' ? 'selected' : '' }}>Manual Bank Transfer</option>
                            <option value="3" {{ request('metode_bayar') === '3' ? 'selected' : '' }}>Cash to Collector / Kasir</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons Actions -->
            <div class="flex items-center justify-between pt-2">
                <button type="button"
                        @click="showAdvancedFilters = !showAdvancedFilters"
                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4 transition-transform" :class="showAdvancedFilters ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                    <span x-text="showAdvancedFilters ? 'Sembunyikan Filter Lanjutan' : 'Tampilkan Filter Lanjutan (Wilayah, Status User, Metode)'"></span>
                </button>

                <div class="flex items-center gap-2">
                    <a href="{{ route('finance.billing-layanan') }}"
                       class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold border border-slate-300 dark:border-slate-700 transition">
                        Reset
                    </a>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-md shadow-blue-500/25 transition cursor-pointer">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Invoices Data Table -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-black/5 overflow-hidden">
        <!-- Table Header Info & Per Page -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50 dark:bg-slate-950/40">
            <div class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                Menampilkan <span class="text-slate-900 dark:text-white font-bold">{{ $invoices->firstItem() ?? 0 }}</span> sampai <span class="text-slate-900 dark:text-white font-bold">{{ $invoices->lastItem() ?? 0 }}</span> dari <span class="text-slate-900 dark:text-white font-bold">{{ $invoices->total() }}</span> total invoice
            </div>

            <!-- Per page selector form -->
            <form method="GET" action="{{ route('finance.billing-layanan') }}" class="flex items-center gap-2">
                @foreach(request()->except(['per_page', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label class="text-xs text-slate-600 dark:text-slate-400">Tampilkan:</label>
                <select name="per_page" onchange="this.form.submit()" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-300 text-xs rounded-lg px-2.5 py-1 focus:border-blue-500">
                    <option value="10" {{ $invoices->perPage() == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $invoices->perPage() == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $invoices->perPage() == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $invoices->perPage() == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span class="text-xs text-slate-600 dark:text-slate-400">entri</span>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Billing Info</th>
                        <th class="py-3.5 px-4">Tanggal & Jatuh Tempo</th>
                        <th class="py-3.5 px-4">Periode</th>
                        <th class="py-3.5 px-4">Nominal Tagihan</th>
                        <th class="py-3.5 px-4">Status & Wilayah</th>
                        <th class="py-3.5 px-4">Metode Bayar</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition duration-150 group">
                        <!-- 1. Billing Info -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="font-bold text-slate-900 dark:text-white tracking-wide text-xs">
                                {{ $inv->kode_billing_layanan }}
                            </div>
                            <div class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mt-0.5 flex items-center gap-1.5">
                                <span>{{ $inv->nama_pelanggan }}</span>
                                <span class="text-[10px] px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ $inv->jenis_kelamin == 2 ? 'P' : 'L' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/20 text-[10px] font-semibold">
                                    {{ $inv->nama_kategori_bandwith ?? 'BROADBAND' }} {{ $inv->nominal_bandwith }} Mbps
                                </span>
                                <span class="text-slate-500 font-mono text-[10px]">#{{ $inv->nomor_internet }}</span>
                            </div>
                        </td>

                        <!-- 2. Tanggal & Jatuh Tempo -->
                        <td class="py-3.5 px-4 align-top">
                            @if($inv->payment_publish)
                            <div class="text-slate-700 dark:text-slate-300 font-medium">
                                Terbit: <span class="text-slate-900 dark:text-white">{{ date('d M Y H:i', strtotime($inv->payment_publish)) }}</span>
                            </div>
                            <div class="text-slate-500 dark:text-slate-400 text-[11px] mt-0.5">
                                Jth Tempo: <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $inv->expiry ? date('d M Y', strtotime($inv->expiry)) : '-' }}</span>
                            </div>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                Billing Belum di-Publish
                            </span>
                            @endif

                            <div class="mt-1.5">
                                <a href="{{ route('finance.dokumen.invoice', urlencode($inv->kode_billing_layanan)) }}" target="_blank" class="group inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20 dark:hover:bg-rose-600 dark:hover:text-white text-[10px] font-semibold transition shadow-xs" title="Buka & Cetak PDF Invoice ({{ $inv->kode_billing_layanan }})">
                                    <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 group-hover:text-white shrink-0 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span>PDF Invoice</span>
                                </a>
                            </div>
                        </td>

                        <!-- 3. Periode -->
                        <td class="py-3.5 px-4 align-top">
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
                                {{ $inv->periode_tagihan ?? ($inv->bulan_tagihan . '/' . $inv->tahun_tagihan) }}
                            </span>
                        </td>

                        <!-- 4. Nominal Tagihan -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Tagihan: <span class="text-slate-900 dark:text-white font-bold">Rp {{ number_format((float) ($inv->total_layanan ?? ($inv->harga_bandwith ?? 0)), 0, ',', '.') }}</span>
                                </div>
                                
                                @if((float)($inv->potongan ?? 0) > 0)
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">
                                    Diskon: -Rp {{ number_format((float) ($inv->potongan ?? 0), 0, ',', '.') }}
                                </div>
                                @endif

                                @if((float)($inv->denda ?? 0) > 0)
                                <div class="text-[10px] text-rose-600 dark:text-rose-400 font-medium">
                                    Denda: +Rp {{ number_format((float) ($inv->denda ?? 0), 0, ',', '.') }}
                                </div>
                                @endif

                                <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1 pt-0.5">
                                    <span>Dibayar:</span>
                                    <span class="{{ $inv->status_bill_lay == '15' ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-600 dark:text-slate-400' }}">
                                        Rp {{ number_format((float) ($inv->amount_paid ?? 0), 0, ',', '.') }}
                                    </span>
                                    @if($inv->status_bill_lay == '15')
                                     <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    @endif
                                </div>
                            </div>
                        </td>

                        @php
                            $snapData = null;
                            $snapUrl = null;
                            $isSnapExpired = false;
                            if (!empty($inv->payment_respond_post)) {
                                $snapData = json_decode($inv->payment_respond_post, true);
                                $snapUrl = $snapData['redirect_url'] ?? null;
                            }
                            if (!empty($inv->expiry)) {
                                $isSnapExpired = \Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($inv->expiry));
                            }
                            
                            $cleanHp = preg_replace('/[^0-9]/', '', $inv->nomor_hp ?? '');
                            if (str_starts_with($cleanHp, '0')) {
                                $cleanHp = '62' . substr($cleanHp, 1);
                            }
                            $nominalFormatted = number_format((float) ($inv->total_layanan ?? ($inv->harga_bandwith ?? 0)), 0, ',', '.');
                            $expiryFormatted = $inv->expiry ? \Carbon\Carbon::parse($inv->expiry)->translatedFormat('d M Y H:i') : 'Jatuh Tempo';
                            
                            $waText = "Halo Pelanggan Yth. {$inv->nama_pelanggan},\nTagihan Internet IMS Periode {$inv->periode_tagihan} sebesar Rp {$nominalFormatted} telah terbit (No Inv: {$inv->kode_billing_layanan}).";
                            if ($snapUrl) {
                                $waText .= "\n\nSilakan lakukan pembayaran melalui link Midtrans resmi berikut:\n{$snapUrl}\n(Berlaku s/d {$expiryFormatted})";
                            }
                            $waText .= "\n\nTerima kasih telah berlangganan bersama IMS.";
                            $waUrl = !empty($cleanHp) ? "https://wa.me/{$cleanHp}?text=" . urlencode($waText) : '';

                            $confirmation = $inv->payment_confirmation ?? null;
                            $hasUploadedProof = ($confirmation && !empty($confirmation->proof_file)) || !empty($inv->has_manual_transfer_proof);
                        @endphp

                        <!-- 5. Status & Wilayah -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-1">
                                <!-- User state + City -->
                                <div class="text-[11px] text-slate-600 dark:text-slate-300 font-medium truncate max-w-[170px]">
                                    <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $inv->desc_registrasi ?? 'User Aktif' }}</span>
                                    <span class="text-slate-400 dark:text-slate-500">&middot;</span>
                                    <span class="text-slate-500 dark:text-slate-400">{{ $inv->nama_kota_pasang ?? '-' }}</span>
                                </div>

                                <!-- Status Tagihan Badge -->
                                <div>
                                    @if($inv->status_bill_lay == '15')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                        <span>PAID (Lunas)</span>
                                    </span>
                                    @elseif($confirmation && $confirmation->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400"></span>
                                        <span>Transfer Ditolak</span>
                                    </span>
                                    @elseif($hasUploadedProof || ($confirmation && !empty($confirmation->proof_file)))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                                        <span>Menunggu Verifikasi</span>
                                    </span>
                                    @elseif($inv->status_bill_lay == '13')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                                        <span>PUBLISH BILLING</span>
                                    </span>
                                    @elseif($inv->status_bill_lay == '14')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                                        <span>WAITING PAYMENT</span>
                                    </span>
                                    @elseif(in_array($inv->status_bill_lay, ['11', '12']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                                        <span>{{ $inv->desc_bill_lay ?? 'DRAFT' }}</span>
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        <span>{{ $inv->desc_bill_lay ?? 'Status ' . $inv->status_bill_lay }}</span>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- 6. Metode Bayar & Notifikasi -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-1.5">
                                <!-- Payment Method Badge & Quick Actions -->
                                @php
                                    $merchantRaw = trim((string)($inv->merchant_type ?? ''));
                                    $merchantLower = strtolower($merchantRaw);
                                    $pType = (string)($inv->payment_type ?? '');

                                    // Cek apakah Transfer Bank (dari merchant_type atau payment_type)
                                    $isTransfer = ($pType === '2')
                                        || !empty($inv->has_manual_transfer_proof)
                                        || !empty($confirmation)
                                        || (
                                            $merchantRaw !== '' && (
                                                str_contains($merchantLower, 'bca') ||
                                                str_contains($merchantLower, 'mandiri') ||
                                                str_contains($merchantLower, 'bri') ||
                                                str_contains($merchantLower, 'bni') ||
                                                str_contains($merchantLower, 'bsi') ||
                                                str_contains($merchantLower, 'permata') ||
                                                str_contains($merchantLower, 'cimb') ||
                                                str_contains($merchantLower, 'transfer') ||
                                                str_contains($merchantLower, 'bank')
                                            )
                                        );

                                    // Cek apakah Tunai / Cash To Collector
                                    $isCash = ($pType === '3')
                                        || (
                                            $merchantRaw !== '' && (
                                                str_contains($merchantLower, 'cash') ||
                                                str_contains($merchantLower, 'kolektor') ||
                                                str_contains($merchantLower, 'collector') ||
                                                str_contains($merchantLower, 'kasir') ||
                                                str_contains($merchantLower, 'tunai')
                                            )
                                        );

                                    $isMidtrans = !$isTransfer && !$isCash;

                                    // Format Label Metode Pembayaran Sesuai Kolom merchant_type
                                    if (!empty($merchantRaw)) {
                                        $methodLabel = $merchantRaw;
                                        if (in_array(strtoupper($merchantRaw), ['BCA', 'MANDIRI', 'BRI', 'BNI', 'BSI', 'PERMATA', 'CIMB'])) {
                                            $methodLabel = 'Transfer ' . strtoupper($merchantRaw);
                                        }
                                    } elseif ($isTransfer) {
                                        $methodLabel = 'Manual Transfer';
                                    } elseif ($isCash) {
                                        $methodLabel = 'Cash To Collector';
                                    } else {
                                        $methodLabel = 'Midtrans';
                                    }
                                @endphp

                                @if($isTransfer)
                                <div class="flex flex-col gap-1.5 items-start">
                                    <!-- Badge Metode Transfer dengan Nama Bank dari merchant_type -->
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 text-[10px] font-semibold" title="Metode: {{ $merchantRaw ?: 'Manual Transfer' }}">
                                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5M4.5 21V10.5" />
                                        </svg>
                                        <span>{{ $methodLabel }}</span>
                                    </span>

                                    <!-- Bukti Transfer Modal Trigger -->
                                    @if($confirmation && !empty($confirmation->proof_file))
                                        <button type="button"
                                                @click="openProofModalFromEl($el)"
                                                data-kode="{{ $inv->kode_billing_layanan }}"
                                                data-nama="{{ $inv->nama_pelanggan }}"
                                                data-internet="{{ $inv->nomor_internet }}"
                                                data-nominal="{{ (float)($inv->total_layanan ?? $inv->harga_bandwith ?? 0) }}"
                                                data-proof-url="{{ $confirmation->proof_file }}"
                                                data-notes="{{ $confirmation->notes ?? '-' }}"
                                                data-proof-date="{{ !empty($confirmation->created_at) ? \Carbon\Carbon::parse($confirmation->created_at)->translatedFormat('d M Y H:i') : '-' }}"
                                                data-status="{{ $confirmation->status ?? 'pending' }}"
                                                data-destination-bank="{{ $inv->destination_bank ?? $confirmation->destination_bank ?? $confirmation->bank_name ?? $inv->merchant_type ?? '' }}"
                                                title="Klik untuk melihat bukti transfer pelanggan"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 dark:hover:bg-indigo-500/20 text-[9px] font-semibold transition cursor-pointer shadow-xs">
                                            <svg class="w-3 h-3 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                            <span>Lihat Bukti Transfer</span>
                                        </button>
                                    @endif
                                </div>
                                @elseif($isCash)
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20 text-[10px] font-semibold" title="Metode: {{ $merchantRaw ?: 'Cash To Collector' }}">
                                        <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        <span>{{ $methodLabel }}</span>
                                    </span>
                                </div>
                                @else
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 text-[10px] font-semibold" title="Metode: {{ $merchantRaw ?: 'Midtrans' }}">
                                        <svg class="w-3 h-3 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                        </svg>
                                        <span>{{ $methodLabel }}</span>
                                    </span>

                                    @if($snapUrl && !$isSnapExpired)
                                    <button type="button"
                                            @click="openMidtransModalFromEl($el)"
                                            data-kode="{{ $inv->kode_billing_layanan }}"
                                            data-nama="{{ $inv->nama_pelanggan }}"
                                            data-nominal="{{ (float)($inv->total_layanan ?? $inv->harga_bandwith ?? 0) }}"
                                            data-midtrans-url="{{ $snapUrl }}"
                                            data-expiry="{{ $expiryFormatted }}"
                                            data-is-expired="0"
                                            data-wa-url="{{ $waUrl }}"
                                            title="Klik untuk Lihat & Salin Link Pembayaran"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 text-[9px] font-medium hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                                        <span>Link Aktif</span>
                                        <svg class="w-2.5 h-2.5 ml-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </button>
                                    @elseif($snapUrl && $isSnapExpired)
                                    <button type="button"
                                            @click="openMidtransModalFromEl($el)"
                                            data-kode="{{ $inv->kode_billing_layanan }}"
                                            data-nama="{{ $inv->nama_pelanggan }}"
                                            data-nominal="{{ (float)($inv->total_layanan ?? $inv->harga_bandwith ?? 0) }}"
                                            data-midtrans-url="{{ $snapUrl }}"
                                            data-expiry="{{ $expiryFormatted }}"
                                            data-is-expired="1"
                                            data-wa-url="{{ $waUrl }}"
                                            title="Link Kadaluarsa! Klik untuk melihat & renew link"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20 text-[9px] font-medium hover:bg-rose-100 dark:hover:bg-rose-500/20 transition cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400"></span>
                                        <span>Link Expired</span>
                                    </button>
                                    @elseif($inv->status_bill_lay != '15')
                                    <form method="POST" action="{{ route('finance.billing-layanan.generate-midtrans.post') }}">
                                        @csrf
                                        <input type="hidden" name="kode_billing" value="{{ $inv->kode_billing_layanan }}">
                                        <button type="submit"
                                                title="Generate Link Pembayaran Midtrans"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20 text-[9px] font-medium hover:bg-amber-100 dark:hover:bg-amber-500/20 transition cursor-pointer">
                                            <span>+ Buat Link</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                @endif

                                <!-- Notification Badges -->
                                <div class="flex items-center gap-1 text-[10px]">
                                    <span class="px-1.5 py-0.2 rounded {{ ($inv->notif_wa ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20' : 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700' }}">
                                        WA: {{ ($inv->notif_wa ?? 0) > 0 ? 'Sent' : 'UnSend' }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded {{ ($inv->notif_mail ?? 0) > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20' : 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700' }}">
                                        Mail: {{ ($inv->notif_mail ?? 0) > 0 ? 'Sent' : 'UnSend' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- 7. Action Buttons (2x2 Stacked Grid: Approve, Change Pay, Detail, Hapus) -->
                        <td class="py-3 px-3 align-middle text-center">
                            <div class="grid grid-cols-2 gap-1.5 w-[210px] mx-auto">
                                <!-- 1. Status Bayar untuk Midtrans, atau Approve untuk Transfer/Cash (Top Left) -->
                                @if($isMidtrans)
                                    {{-- METODE MIDTRANS: Tidak ada tombol Approve, langsung tampilkan status bayar --}}
                                    @if($inv->status_bill_lay == '15')
                                    <span class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30 text-[11px] font-bold select-none" title="Tagihan Midtrans Lunas">
                                        <svg class="w-3 h-3 flex-shrink-0 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Lunas</span>
                                    </span>
                                    @elseif($isSnapExpired)
                                    <span class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30 text-[11px] font-bold select-none" title="Link Pembayaran Midtrans Expired">
                                        <svg class="w-3 h-3 flex-shrink-0 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        <span>Expired</span>
                                    </span>
                                    @elseif($inv->status_bill_lay == '14')
                                    <span class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/15 dark:text-cyan-400 dark:border-cyan-500/30 text-[11px] font-bold select-none" title="Menunggu Pembayaran Pelanggan">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                        <span>Pending</span>
                                    </span>
                                    @else
                                    <span class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 text-[11px] font-bold select-none" title="Tagihan Belum Dibayar (Menunggu Pembayaran Midtrans)">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Belum Bayar</span>
                                    </span>
                                    @endif
                                @else
                                    {{-- METODE TRANSFER / CASH: Memerlukan Konfirmasi & Tombol Approve Manual --}}
                                    @if($inv->status_bill_lay != '15')
                                    <button type="button"
                                            @click="openPayModalFromEl($el)"
                                            data-kode="{{ $inv->kode_billing_layanan }}"
                                            data-internet="{{ $inv->nomor_internet }}"
                                            data-nama="{{ $inv->nama_pelanggan }}"
                                            data-nominal="{{ (float)($inv->total_layanan ?? $inv->harga_bandwith ?? 0) }}"
                                            data-payment-type="{{ $inv->payment_type ?? 2 }}"
                                            data-destination-bank="{{ $inv->destination_bank ?? $inv->merchant_type ?? '' }}"
                                            title="Approve Pembayaran Lunas"
                                            class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 dark:bg-emerald-500/15 dark:hover:bg-emerald-600 dark:text-emerald-400 dark:hover:text-white dark:border-emerald-500/30 text-[11px] font-bold transition shadow-sm cursor-pointer whitespace-nowrap">
                                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <span>Approve</span>
                                    </button>
                                    @else
                                    <span class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-emerald-50 text-emerald-600/70 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400/70 dark:border-emerald-500/20 text-[11px] font-semibold opacity-75 select-none">
                                        <svg class="w-3 h-3 flex-shrink-0 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Lunas</span>
                                    </span>
                                    @endif
                                @endif

                                <!-- 2. Change Payment Method (Top Right) -->
                                <button type="button"
                                        @click="openChangePayModalFromEl($el)"
                                        data-kode="{{ $inv->kode_billing_layanan }}"
                                        data-nama="{{ $inv->nama_pelanggan }}"
                                        data-payment-type="{{ $inv->payment_type ?? 1 }}"
                                        title="Ubah Metode Pembayaran"
                                        class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 dark:bg-blue-500/15 dark:hover:bg-blue-600 dark:text-blue-400 dark:hover:text-white dark:border-blue-500/30 text-[11px] font-semibold transition shadow-sm cursor-pointer whitespace-nowrap">
                                    <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                    <span>Change Pay</span>
                                </button>

                                <!-- 3. Lihat Detail (Bottom Left) -->
                                <button type="button"
                                        @click="openDetailModalFromEl($el)"
                                        data-kode="{{ $inv->kode_billing_layanan }}"
                                        title="Lihat Detail Tagihan"
                                        class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white border border-slate-200 dark:border-slate-700 text-[11px] font-semibold transition shadow-sm cursor-pointer whitespace-nowrap">
                                    <svg class="w-3 h-3 flex-shrink-0 text-slate-500 dark:text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Detail</span>
                                </button>

                                <!-- 4. Hapus Tagihan (Bottom Right) -->
                                <form action="{{ route('finance.billing-layanan.delete.post') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tagihan {{ $inv->kode_billing_layanan }} ({{ $inv->nama_pelanggan }})?')" class="w-full m-0 p-0">
                                    @csrf
                                    <input type="hidden" name="kode_billing" value="{{ $inv->kode_billing_layanan }}">
                                    <button type="submit"
                                            title="Hapus Tagihan Ini"
                                            class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200 dark:bg-rose-500/15 dark:hover:bg-rose-600 dark:text-rose-400 dark:hover:text-white dark:border-rose-500/30 text-[11px] font-semibold transition shadow-sm cursor-pointer whitespace-nowrap">
                                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-400 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada data invoice ditemukan</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Coba sesuaikan filter bulan/tahun atau gunakan tombol Generate Invoice Massal.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($invoices->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>

    <!-- ======================================================================= -->
    <!-- MODALS SECTION                                                          -->
    <!-- ======================================================================= -->

    <!-- 1. MODAL GENERATE INVOICE (MODERN & RAPI) -->
    <div x-show="generateModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="generateModalOpen = false"
             class="relative w-full max-w-4xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto max-h-[95vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Form Generate Invoice</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Penerbitan tagihan bulanan pelanggan Single User atau Multi User</p>
                    </div>
                </div>
                <button type="button" @click="generateModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <!-- Modal Body Scrollable -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1">
                <form method="POST" action="{{ route('finance.billing-layanan.generate') }}" id="generateInvoiceForm">
                    @csrf

                    <!-- Upper Card: Parameters & Options -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6">
                        <!-- Row 1: Jenis Generate & Jenis Layanan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                            <div>
                                <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200 mb-2">
                                    Jenis Generate Invoice ?<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="flex items-center gap-5 pt-1">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="jenis_generate" value="single" x-model="generateJenis" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Single User</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="jenis_generate" value="multi" x-model="generateJenis" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Multi User</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    JENIS LAYANAN<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="relative">
                                    <select name="layanan" x-model="generateLayanan" @change="fetchGenerateCandidates()" class="w-full bg-slate-50/60 dark:bg-slate-800/60 hover:bg-white focus:bg-white dark:hover:bg-slate-800 dark:focus:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 shadow-2xs appearance-none transition pr-9 cursor-pointer">
                                        <option value="">PILIH LAYANAN</option>
                                        <option value="Semua Layanan">Semua Layanan</option>
                                        @if(isset($bandwithKategoriList) && count($bandwithKategoriList) > 0)
                                            @foreach($bandwithKategoriList as $bk)
                                                <option value="{{ $bk->nama_kategori_bandwith }}">{{ $bk->nama_kategori_bandwith }}{{ !empty($bk->alias_nama_kategori) && $bk->alias_nama_kategori !== $bk->nama_kategori_bandwith ? ' (' . $bk->alias_nama_kategori . ')' : '' }}</option>
                                            @endforeach
                                        @elseif(isset($layananList) && count($layananList) > 0)
                                            @foreach($layananList as $lay)
                                                @if(is_object($lay))
                                                    <option value="{{ $lay->nama_kategori_bandwith }}">{{ $lay->nama_kategori_bandwith }}</option>
                                                @else
                                                    <option value="{{ $lay }}">{{ $lay }}</option>
                                                @endif
                                            @endforeach
                                        @else
                                            @php
                                                $dbKategori = \Illuminate\Support\Facades\Schema::hasTable('m_bandwith_kategori')
                                                    ? \Illuminate\Support\Facades\DB::table('m_bandwith_kategori')->where('disable', 0)->orderBy('nama_kategori_bandwith', 'asc')->get()
                                                    : collect();
                                            @endphp
                                            @foreach($dbKategori as $bk)
                                                <option value="{{ $bk->nama_kategori_bandwith }}">{{ $bk->nama_kategori_bandwith }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Periode, Tahun, Kirim Invoice -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    PERIODE TAGIHAN<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="relative">
                                    <select name="bulan" x-model="generateBulan" @change="fetchGenerateCandidates()" class="w-full bg-slate-50/60 dark:bg-slate-800/60 hover:bg-white focus:bg-white dark:hover:bg-slate-800 dark:focus:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 shadow-2xs appearance-none transition pr-9 cursor-pointer">
                                        <option value="">PILIH PERIODE</option>
                                        @foreach($bulanList as $k => $b)
                                        <option value="{{ $k }}">{{ $k }} - {{ $b }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    TAHUN TAGIHAN<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="relative">
                                    <select name="tahun" x-model="generateTahun" @change="fetchGenerateCandidates()" class="w-full bg-slate-50/60 dark:bg-slate-800/60 hover:bg-white focus:bg-white dark:hover:bg-slate-800 dark:focus:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 shadow-2xs appearance-none transition pr-9 cursor-pointer">
                                        <option value="">PILIH TAHUN</option>
                                        @for($y = 2024; $y <= 2030; $y++)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200 mb-2">
                                    Kirim Invoice ?<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="flex items-center gap-4 pt-1.5">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="checkbox" name="kirim_wa" x-model="generateKirimWa" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>WhatsApp</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="checkbox" name="kirim_email" x-model="generateKirimEmail" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Email</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: PPN, Auto Publish, and Action Buttons -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end pt-2 border-t border-slate-100 dark:border-slate-800">
                            <!-- PPN Options -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200 mb-2">
                                    PPN ?<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="flex items-center gap-3.5 flex-wrap">
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="ppn" value="include" x-model="generatePpn" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Include</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="ppn" value="exclude" x-model="generatePpn" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Exclude</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="ppn" value="default" x-model="generatePpn" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Default</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Auto Publish Options -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200 mb-2">
                                    Auto Publish ?<span class="text-red-500 font-bold ml-0.5">*</span>
                                </label>
                                <div class="flex items-center gap-4">
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="auto_publish" value="yes" x-model="generateAutoPublish" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>Yes</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                        <input type="radio" name="auto_publish" value="no" x-model="generateAutoPublish" class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Actions (Multi Generate + Close) -->
                            <div class="flex justify-end items-center gap-2.5">
                                <template x-if="generateJenis === 'multi'">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span>Generate All Invoices</span>
                                    </button>
                                </template>
                                <button type="button"
                                        @click="generateModalOpen = false"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-xs shadow-md shadow-amber-500/25 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 9 6 6m0-6-6 6"></path>
                                    </svg>
                                    <span>Close</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Data Table Area -->
                    <div class="mt-6 space-y-3">
                        <!-- Table Top Controls Toolbar -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                <span>Show</span>
                                <select x-model.number="generatePerPage" class="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500 shadow-2xs cursor-pointer">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                                <span>entries</span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="inline-flex rounded-lg shadow-2xs border border-slate-200 dark:border-slate-700 overflow-hidden text-xs">
                                    <button type="button" @click="candidateFirstPage()" :disabled="generatePage === 1" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">First</button>
                                    <button type="button" @click="candidatePrevPage()" :disabled="generatePage === 1" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">Previous</button>
                                    <button type="button" @click="candidateNextPage()" :disabled="generatePage === totalCandidatePages" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">Next</button>
                                    <button type="button" @click="candidateLastPage()" :disabled="generatePage === totalCandidatePages" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">Last</button>
                                </div>
                            </div>
                        </div>

                        <!-- Search Bar -->
                        <div class="flex items-center justify-end">
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-slate-600 dark:text-slate-400 font-medium">Search:</label>
                                <div class="relative w-64">
                                    <input type="text"
                                           x-model="generateSearch"
                                           @input.debounce.300ms="fetchGenerateCandidates()"
                                           placeholder=""
                                           class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                                </div>
                            </div>
                        </div>

                        <!-- Candidate Table -->
                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="py-3 px-4 font-bold text-slate-700 dark:text-slate-200 tracking-wider text-xs">
                                            Customer <span class="text-slate-400 text-xs ml-0.5">⇅</span>
                                        </th>
                                        <th class="py-3 px-4 font-bold text-slate-700 dark:text-slate-200 tracking-wider text-xs border-l border-slate-200 dark:border-slate-700">
                                            Periode
                                        </th>
                                        <th class="py-3 px-4 font-bold text-slate-700 dark:text-slate-200 tracking-wider text-xs border-l border-slate-200 dark:border-slate-700">
                                            Pending
                                        </th>
                                        <th class="py-3 px-4 font-bold text-slate-700 dark:text-slate-200 tracking-wider text-xs border-l border-slate-200 dark:border-slate-700 text-center">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900/60">
                                    <!-- Jika Belum Memilih Jenis Layanan -->
                                    <template x-if="!generateLayanan">
                                        <tr>
                                            <td colspan="4" class="py-14 px-6 text-center align-middle">
                                                <div class="flex flex-col items-center justify-center max-w-md mx-auto py-2">
                                                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800/50 flex items-center justify-center text-blue-500 mb-3.5 shadow-sm">
                                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-200">Silakan Pilih Jenis Layanan Terlebih Dahulu</div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Pilih opsi pada dropdown <strong class="text-slate-700 dark:text-slate-300">JENIS LAYANAN</strong> di atas untuk menampilkan daftar pelanggan.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Loading State saat memilih layanan / fetch -->
                                    <template x-if="generateLayanan && generateLoading">
                                        <tr>
                                            <td colspan="4" class="py-14 px-6 text-center align-middle text-slate-400">
                                                <div class="flex flex-col items-center justify-center py-2">
                                                    <div class="w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                                                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300">Memuat data pelanggan...</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Kosong setelah fetch dengan layanan terpilih -->
                                    <template x-if="generateLayanan && !generateLoading && generateCandidates.length === 0">
                                        <tr>
                                            <td colspan="4" class="py-14 px-6 text-center align-middle text-slate-400">
                                                <div class="flex flex-col items-center justify-center max-w-md mx-auto py-2">
                                                    <div class="w-12 h-12 shrink-0 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                    </div>
                                                    <div class="font-bold text-sm text-slate-700 dark:text-slate-300">Tidak ada data invoice yang sesuai</div>
                                                    <div class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter periode/tahun.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Ada data dengan layanan terpilih -->
                                    <template x-if="generateLayanan && !generateLoading && generateCandidates.length > 0">
                                        <template x-for="c in paginatedCandidates" :key="c.nomor_internet">
                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-3 px-4">
                                                    <div class="font-bold text-slate-900 dark:text-white text-xs uppercase" x-text="c.nama_pelanggan"></div>
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                                        <span x-text="c.nomor_internet"></span>
                                                        <span x-text="' (' + (c.layanan || 'BROADBAND').toUpperCase() + ')'"></span>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 border-l border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                                    <span x-text="c.periode"></span>
                                                </td>
                                                <td class="py-3 px-4 border-l border-slate-100 dark:border-slate-800 font-bold"
                                                    :class="c.pending_nominal > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-900 dark:text-white'"
                                                    x-text="c.pending_formatted">
                                                </td>
                                                <td class="py-3 px-4 border-l border-slate-100 dark:border-slate-800 text-center">
                                                    <template x-if="c.is_generated">
                                                        <span class="inline-block px-3 py-1 rounded-md text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800">
                                                            Generated
                                                        </span>
                                                    </template>
                                                    <template x-if="!c.is_generated">
                                                        <button type="submit"
                                                                name="nomor_internet"
                                                                :value="c.nomor_internet"
                                                                class="inline-flex items-center justify-center px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                                                            Generate
                                                        </button>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Table Bottom Controls -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-500 dark:text-slate-400">
                            <div>
                                <span x-text="!generateLayanan ? 'Pilih jenis layanan untuk melihat data' : (generateCandidates.length === 0 ? 'Showing 0 to 0 of 0 entries' : 'Showing ' + (((generatePage - 1) * generatePerPage) + 1) + ' to ' + Math.min(generatePage * generatePerPage, generateCandidates.length) + ' of ' + generateCandidates.length + ' entries')"></span>
                            </div>
                            <div class="inline-flex rounded-lg shadow-2xs border border-slate-200 dark:border-slate-700 overflow-hidden text-xs">
                                <button type="button" @click="candidateFirstPage()" :disabled="generatePage === 1" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">First</button>
                                <button type="button" @click="candidatePrevPage()" :disabled="generatePage === 1" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">Previous</button>
                                <button type="button" @click="candidateNextPage()" :disabled="generatePage === totalCandidatePages" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">Next</button>
                                <button type="button" @click="candidateLastPage()" :disabled="generatePage === totalCandidatePages" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 disabled:opacity-40 cursor-pointer font-medium">Last</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. MODAL DETAIL BREAKDOWN INVOICE -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="detailModalOpen = false"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto max-h-[90vh] flex flex-col">
            
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Detail Invoice & Rincian Item</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white" x-text="detailData.invoice?.kode_billing_layanan || 'Loading...'"></h3>
                    </div>
                    <button @click="detailModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-show="detailLoading" class="py-12 text-center text-slate-500 dark:text-slate-400">
                    <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs mt-2">Memuat rincian invoice...</p>
                </div>

                <div x-show="!detailLoading" class="space-y-4">
                    <!-- Customer Summary Card -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Nama Pelanggan:</span>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="detailData.invoice?.nama_pelanggan || '-'"></span>
                            <span class="text-slate-500 dark:text-slate-400 block text-[10px] mt-0.5" x-text="'No Internet: #' + (detailData.invoice?.nomor_internet || '')"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Paket & Periode:</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400" x-text="(detailData.invoice?.nama_kategori_bandwith || '') + ' ' + (detailData.invoice?.nominal_bandwith || '') + ' Mbps'"></span>
                            <span class="text-slate-500 dark:text-slate-400 block text-[10px] mt-0.5" x-text="'Periode: ' + (detailData.invoice?.periode_tagihan || '-')"></span>
                            <span class="text-emerald-600 dark:text-emerald-400 block text-[10px] font-semibold mt-0.5" x-text="'Metode: ' + (detailData.invoice?.merchant_type || (detailData.invoice?.payment_type == 1 ? 'Midtrans' : (detailData.invoice?.payment_type == 3 ? 'Cash To Collector' : 'Manual Transfer')))"></span>
                        </div>
                    </div>

                    <!-- Item Breakdown Table -->
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block mb-2">Komponen Tagihan:</span>
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Komponen / Item</th>
                                        <th class="py-2.5 px-3 text-center">Qty</th>
                                        <th class="py-2.5 px-3 text-right">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="item in detailData.items" :key="item.kode_billing_lay_detail">
                                        <tr>
                                            <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200" x-text="item.komponen"></td>
                                            <td class="py-2.5 px-3 text-center text-slate-500 dark:text-slate-400" x-text="item.qty"></td>
                                            <td class="py-2.5 px-3 text-right font-semibold text-slate-900 dark:text-white" x-text="formatRupiah(item.biaya)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-slate-50/80 dark:bg-slate-950/80 text-xs border-t border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <td colspan="2" class="py-2 px-3 text-right font-bold text-slate-700 dark:text-slate-300">Total Tagihan Pokok:</td>
                                        <td class="py-2 px-3 text-right font-bold text-slate-900 dark:text-white" x-text="formatRupiah(detailData.invoice?.total_layanan || detailData.invoice?.harga_bandwith)"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Transaction Logs -->
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block mb-1.5">Riwayat & Log Transaksi:</span>
                        <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                            <template x-for="log in detailData.logs" :key="log.kode_billing_lay_log">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 text-[11px] flex items-center justify-between">
                                    <div>
                                        <span class="text-slate-700 dark:text-slate-300 font-medium" x-text="log.note_billing_lay"></span>
                                        <span class="text-slate-500 block text-[10px]" x-text="'Oleh: ' + (log.user_create || 'System')"></span>
                                    </div>
                                    <span class="text-slate-500 text-[10px]" x-text="log.date_create"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a :href="'{{ url('/finance/dokumen/invoice') }}/' + encodeURIComponent(detailData.invoice?.kode_billing_layanan || '')" target="_blank" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm shadow-blue-500/20">
                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span>Buka & Cetak Form Invoice (PDF)</span>
                </a>
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- 3. MODAL KONFIRMASI BAYAR MANUAL / CASH TO COLLECT -->
    <div x-show="payModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="payModalOpen = false"
             class="relative w-full max-w-lg md:max-w-2xl lg:max-w-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto transition-all duration-200">
            
            <form action="{{ route('finance.billing-layanan.konfirmasi-bayar.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="kode_billing" :value="payKodeBilling">

                <!-- Modal Header (Sidebar Blue Theme) -->
                <div class="px-6 py-4 bg-[#061d28] border-b border-[#0d2a38] flex items-center justify-between text-white">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-cyan-500/15 text-cyan-400 border border-cyan-500/30">
                            <template x-if="payMetode === 'cash'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </template>
                            <template x-if="payMetode !== 'cash'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </template>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white tracking-wide" x-text="payMetode === 'cash' ? 'Konfirmasi Bayar: Cash To Collector' : 'Konfirmasi Bayar: Manual Transfer'"></h3>
                            <p class="text-xs text-slate-300">Verifikasi pelunasan tagihan invoice pelanggan</p>
                        </div>
                    </div>
                    <button type="button" @click="payModalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 md:p-7 space-y-4">
                    <!-- Info Ringkasan & Nominal Diterima (2-Column Grid on Desktop) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <!-- Info Ringkasan Invoice -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-2 flex flex-col justify-center">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 dark:text-slate-400">No. Invoice:</span>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm" x-text="payKodeBilling"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 dark:text-slate-400">Nama Pelanggan:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400 text-sm" x-text="payNamaPelanggan"></span>
                            </div>
                        </div>

                        <!-- Nominal Diterima -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nominal Diterima (Rp)</label>
                            <input type="number" name="nominal_bayar" x-model="payNominal" required min="1" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-emerald-600 dark:text-emerald-400 font-bold text-lg rounded-xl px-3.5 py-2.5 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Pilihan Metode Bayar (Radio Tabs) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="payMetode === 'cash' ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-300 dark:border-amber-500/50 text-amber-700 dark:text-amber-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'">
                                <input type="radio" name="metode_bayar" value="cash" x-model="payMetode"
                                       @change="payBank = 'Cash To Collector'; payCatatan = 'Pembayaran Cash to Collector Terverifikasi'"
                                       class="w-4 h-4 text-amber-500 focus:ring-amber-400">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Cash To Collector</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Tunai / Kasir Lapangan</div>
                                </div>
                            </label>

                            <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="payMetode === 'transfer' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-300 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'">
                                <input type="radio" name="metode_bayar" value="transfer" x-model="payMetode"
                                       @change="payBank = (payDestinationBank || 'BCA'); payCatatan = 'Pembayaran Transfer Terverifikasi'"
                                       class="w-4 h-4 text-emerald-500 focus:ring-emerald-400">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Manual Transfer</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Transfer Bank Perusahaan</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dynamic Fields: Jika CASH TO COLLECT -->
                    <div x-show="payMetode === 'cash'" class="space-y-3 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-500/5 border border-amber-200 dark:border-amber-500/15">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tipe Kasir / Kolektor</label>
                                <select name="bank_tujuan" x-model="payBank" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                                    <option value="Cash To Collector">Cash To Collector</option>
                                    <option value="Kasir Kantor Pusat">Kasir Kantor Pusat</option>
                                    <option value="Kasir Cabang">Kasir Cabang</option>
                                    <option value="Kolektor Lapangan">Kolektor Lapangan</option>
                                    <option value="Kasir / Tunai">Kasir / Tunai</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Petugas Kolektor <span class="text-slate-500 font-normal">(Opsional)</span></label>
                                <input type="text" name="nama_kolektor" x-model="payNamaKolektor" placeholder="Nama Petugas..." class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">No. Kwitansi / Tanda Terima <span class="text-slate-500 font-normal">(Opsional)</span></label>
                                <input type="text" name="no_kwitansi" x-model="payNoKwitansi" placeholder="Contoh: KWT-00123" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields: Jika MANUAL TRANSFER -->
                    <div x-show="payMetode === 'transfer'" class="space-y-3 p-3.5 rounded-xl bg-blue-50 dark:bg-blue-500/5 border border-blue-200 dark:border-blue-500/15">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between">
                                    <span>Rekening Bank Tujuan</span>
                                    <template x-if="payDestinationBank">
                                        <span class="text-[10px] text-blue-600 dark:text-cyan-400 font-normal">(Terisi Otomatis)</span>
                                    </template>
                                </label>
                                <select name="bank_tujuan" x-model="payBank" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                                    <template x-if="payBank && !['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'Permata', 'Cash To Collector', ''].includes(payBank)">
                                        <option :value="payBank" x-text="payBank" selected></option>
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
                                <input type="text" name="no_kwitansi" x-model="payNoKwitansi" placeholder="Contoh: REF123456 / A.N Budi" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Verifikasi & Foto Bukti Transfer (2-Column Grid on Desktop) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <!-- Catatan Verifikasi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Catatan Verifikasi</label>
                            <input type="text" name="catatan" x-model="payCatatan" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3.5 py-2.5 focus:border-emerald-500">
                        </div>

                        <!-- Foto Bukti Transfer (Upload Bukti Transfer) -->
                        <div class="space-y-1.5" x-data="{ fotoBuktiPreview: null }">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Upload Bukti Transfer
                            </label>
                            <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-center hover:border-cyan-500 transition cursor-pointer bg-slate-50 dark:bg-slate-950/40 min-h-[42px] flex items-center justify-center">
                                <input type="file"
                                       name="foto_bukti"
                                       accept="image/*"
                                       capture="environment"
                                       @change="const file = $event.target.files[0]; if(file) { fotoBuktiPreview = URL.createObjectURL(file); }"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <template x-if="!fotoBuktiPreview">
                                    <div class="flex items-center justify-center gap-1.5 pointer-events-none text-slate-500 dark:text-slate-400">
                                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                        </svg>
                                        <span class="text-[11px] font-medium">Upload Bukti Transfer</span>
                                    </div>
                                </template>

                                <template x-if="fotoBuktiPreview">
                                    <div class="relative rounded-lg overflow-hidden max-h-16 flex items-center justify-center">
                                        <img :src="fotoBuktiPreview" class="object-contain max-h-16 rounded shadow-sm">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="payModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">
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

    <!-- 4. MODAL ADJUSTMENT (PENYESUAIAN DISKON / DENDA) -->
    <div x-show="adjustModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="adjustModalOpen = false"
             class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.adjust.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="adjustKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Penyesuaian (Adjustment)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Atur potongan diskon atau denda tagihan</p>
                            </div>
                        </div>
                        <button type="button" @click="adjustModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Invoice:</span>
                            <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="adjustKodeBilling"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Subtotal Pokok:</span>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="formatRupiah(adjustSubtotal)"></span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 dark:border-slate-800 pt-1.5">
                            <span class="text-amber-600 dark:text-amber-400 font-semibold">Total Tagihan Baru:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm" x-text="formatRupiah(adjustTotalBaru)"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Potongan / Diskon Kompensasi (Rp)</label>
                        <input type="number" name="potongan" x-model="adjustPotongan" min="0" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Keterangan Diskon</label>
                        <input type="text" name="desc_potongan" x-model="adjustDescPotongan" placeholder="Kompensasi kendala jaringan..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Denda Keterlambatan (Rp)</label>
                        <input type="number" name="denda" x-model="adjustDenda" min="0" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Catatan Adjustment</label>
                        <input type="text" name="note_adjustment" x-model="adjustNote" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="adjustModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold shadow-lg shadow-amber-500/25 cursor-pointer">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. MODAL ROLLBACK TAGIHAN -->
    <div x-show="rollbackModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="rollbackModalOpen = false"
             class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.rollback.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="rollbackKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Rollback Status Tagihan</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kembalikan invoice ke status Draft / Unpublish</p>
                            </div>
                        </div>
                        <button type="button" @click="rollbackModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
                    </div>

                    <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-xs text-rose-800 dark:text-rose-300">
                        Apakah Anda yakin ingin me-rollback status invoice <strong class="text-slate-900 dark:text-white font-mono" x-text="rollbackKodeBilling"></strong> milik <strong class="text-slate-900 dark:text-white" x-text="rollbackNamaPelanggan"></strong>? Riwayat pembayaran yang sudah diverifikasi akan dikosongkan kembali ke status draft.
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="rollbackModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-lg shadow-rose-500/25 cursor-pointer">
                        Ya, Rollback Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. MODAL CHANGE PAYMENT METHOD (MATCHING EXACT SCREENSHOT DESIGN) -->
    <div x-show="changePayModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="changePayModalOpen = false"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.change-payment-method.post') }}" method="POST">
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
                        <span class="text-slate-500 dark:text-slate-400">Invoice: <strong class="text-slate-800 dark:text-white font-mono" x-text="changePayKodeBilling"></strong></span>
                        <span class="text-slate-500 dark:text-slate-400">Pelanggan: <strong class="text-blue-600 dark:text-blue-400" x-text="changePayNamaPelanggan"></strong></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. MODAL LINK PEMBAYARAN MIDTRANS SNAP -->
    <div x-show="midtransModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="midtransModalOpen = false"
             class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Link Pembayaran Midtrans Snap</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Tautan pembayaran online instan pelanggan</p>
                        </div>
                    </div>
                    <button type="button" @click="midtransModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Invoice:</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="midtransKode"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Pelanggan:</span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400" x-text="midtransNama"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 dark:border-slate-800 pt-1.5">
                        <span class="text-slate-500 dark:text-slate-400">Total Tagihan:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm" x-text="formatRupiah(midtransNominal)"></span>
                    </div>
                </div>

                <!-- Status Masa Berlaku Link -->
                <div class="flex items-center justify-between p-3 rounded-xl border text-xs"
                     :class="midtransIsExpired ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-300'">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :class="midtransIsExpired ? 'bg-rose-500' : 'bg-emerald-500 dark:bg-emerald-400 animate-pulse'"></span>
                        <span class="font-semibold" x-text="midtransIsExpired ? 'Status: Link Kadaluarsa (Expired)' : 'Status: Link Aktif'"></span>
                    </div>
                    <span class="text-[11px]" x-text="'Berlaku s/d: ' + (midtransExpiry || '-')"></span>
                </div>

                <!-- URL Copy Box -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">URL Pembayaran Online</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly :value="midtransUrl" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-slate-200 text-xs rounded-xl px-3 py-2.5 font-mono focus:border-indigo-500 select-all">
                        <button type="button"
                                @click="copyMidtransLink()"
                                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shrink-0 shadow-lg shadow-indigo-500/20 flex items-center gap-1.5 transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                            </svg>
                            <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                </div>

                <!-- Actions Grid -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <a :href="midtransUrl" target="_blank"
                       class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-white text-xs font-semibold text-center flex items-center justify-center gap-1.5 transition border border-slate-200 dark:border-slate-700">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        <span>Buka Link Midtrans</span>
                    </a>

                    <a :href="midtransWaUrl" target="_blank"
                       class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold text-center flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-500/20 transition cursor-pointer">
                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-1.074-.865 5.25 5.25 0 0 0 1.4-2.84C4.12 15.842 3 14.034 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>
                        <span>Kirim via WhatsApp</span>
                    </a>
                </div>

                <!-- Renew Button if Expired or Re-request -->
                <div class="border-t border-slate-100 dark:border-slate-800 pt-3">
                    <form method="POST" action="{{ route('finance.billing-layanan.renew-midtrans.post') }}" onsubmit="return confirm('Buat Order ID dan perbarui link Midtrans baru untuk invoice ini?')">
                        @csrf
                        <input type="hidden" name="kode_billing" :value="midtransKode">
                        <button type="submit"
                                class="w-full px-4 py-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/10 dark:hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20 text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Renew / Buat Ulang Link Pembayaran</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button type="button" @click="midtransModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- 6. MODAL LIHAT BUKTI TRANSFER MANUAL -->
    <div x-show="proofModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="proofModalOpen = false"
             x-show="proofModalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 overflow-hidden my-8 flex flex-col max-h-[90vh]">
            
            <!-- Modal Header (Sidebar Blue Theme) -->
            <div class="px-6 py-4 border-b border-[#0d2a38] flex items-center justify-between bg-[#061d28] text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide">Bukti Transfer Pembayaran</h3>
                        <p class="text-xs text-slate-300" x-text="proofNamaPelanggan + ' • #' + proofInternet"></p>
                    </div>
                </div>
                <button type="button" @click="proofModalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer text-2xl leading-none">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- Info Header Box -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-950/60 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">No. Invoice:</span>
                        <span class="font-bold font-mono text-slate-800 dark:text-slate-200" x-text="proofKodeBilling"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Nominal Tagihan:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="formatRupiah(proofNominal)"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Waktu Upload:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300" x-text="proofDate || '-'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Status Verifikasi:</span>
                        <span class="inline-flex items-center gap-1 font-semibold capitalize"
                              :class="proofStatus === 'approved' ? 'text-emerald-600 dark:text-emerald-400' : (proofStatus === 'rejected' ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400')">
                            <span class="w-2 h-2 rounded-full" :class="proofStatus === 'approved' ? 'bg-emerald-500' : (proofStatus === 'rejected' ? 'bg-rose-500' : 'bg-amber-500 animate-pulse')"></span>
                            <span x-text="proofStatus === 'approved' ? 'Lunas / Disetujui' : (proofStatus === 'rejected' ? 'Ditolak' : 'Menunggu Approval')"></span>
                        </span>
                    </div>
                    <div class="sm:col-span-2 border-t border-slate-200 dark:border-slate-800 pt-2" x-show="proofNotes && proofNotes !== '-'">
                        <span class="text-slate-400 block text-[11px]">Catatan Pengirim:</span>
                        <p class="text-slate-700 dark:text-slate-300 italic text-xs mt-0.5" x-text="proofNotes"></p>
                    </div>
                </div>

                <!-- Preview Gambar Bukti Transfer -->
                <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-slate-900/5 dark:bg-slate-950 flex flex-col items-center justify-center min-h-[220px] p-2 relative group">
                    <template x-if="proofUrl && (proofUrl.endsWith('.pdf') || proofUrl.includes('/pdf'))">
                        <div class="py-12 px-4 text-center space-y-3">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">Dokumen Bukti Transfer berformat PDF</p>
                            <a :href="proofUrl" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-lg shadow-rose-500/20 transition">
                                <span>Buka File PDF</span>
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        </div>
                    </template>

                    <template x-if="proofUrl && !(proofUrl.endsWith('.pdf') || proofUrl.includes('/pdf'))">
                        <div class="w-full flex flex-col items-center">
                            <img :src="proofUrl" alt="Bukti Transfer" class="max-h-[380px] w-auto rounded-lg object-contain shadow-sm hover:scale-[1.01] transition duration-200 cursor-pointer" @click="window.open(proofUrl, '_blank')">
                            <div class="mt-2 text-center">
                                <a :href="proofUrl" target="_blank" class="text-[11px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 inline-flex items-center gap-1 font-medium">
                                    <span>Buka Gambar Ukuran Penuh</span>
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-50 dark:bg-slate-950/80 px-6 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <button type="button" @click="proofModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer transition">
                    Tutup
                </button>

                <div class="flex items-center gap-2">
                    <button type="button"
                            x-show="proofStatus !== 'approved'"
                            @click="proofModalOpen = false; openPayModal(proofKodeBilling, proofInternet, proofNamaPelanggan, proofNominal, '2', proofDestinationBank)"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition cursor-pointer">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Konfirmasi & Approve Pembayaran</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL BATCH PRINT INVOICES (CETAK MASSAL)
         ========================================== -->
    <style>
        .batch-modal-scroll {
            scrollbar-width: thin !important;
            scrollbar-color: #0891b2 rgba(0, 0, 0, 0.08) !important;
        }
        .batch-modal-scroll::-webkit-scrollbar {
            width: 8px !important;
            height: 8px !important;
            display: block !important;
        }
        .batch-modal-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05) !important;
            border-radius: 4px !important;
        }
        .batch-modal-scroll::-webkit-scrollbar-thumb {
            background-color: #0891b2 !important;
            border-radius: 4px !important;
        }
        .batch-modal-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #0e7490 !important;
        }
    </style>
    <div x-show="batchPrintModalOpen"
         x-cloak
         @keydown.escape.window="batchPrintModalOpen = false"
         class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm overflow-hidden"
         style="position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(2, 6, 23, 0.85); overflow: hidden;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="batchPrintModalOpen = false"
             x-show="batchPrintModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="width: 100%; max-width: 68rem; height: 82vh; max-height: calc(100vh - 2.5rem); display: flex; flex-direction: column; overflow: hidden; margin: auto;"
             class="relative w-full max-w-5xl lg:max-w-6xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col">
            <!-- Modal Header (Fixed) -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Cetak Massal Invoice Tagihan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pilih periode bulan &amp; tahun, cari pelanggan, centang tagihan yang ingin dicetak, lalu cetak serentak.</p>
                    </div>
                </div>
                <button type="button" @click="batchPrintModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-2xl leading-none">&times;</button>
            </div>

            <!-- Filter Controls Bar (Fixed) -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/30 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <!-- Bulan -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Bulan</label>
                        <select x-model="batchBulan" @change="fetchBatchInvoices()" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                            <option value="">Semua Bulan</option>
                            @foreach($bulanList as $k => $nm)
                            <option value="{{ $k }}">{{ $k }} - {{ $nm }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tahun -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Tahun</label>
                        <select x-model="batchTahun" @change="fetchBatchInvoices()" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                            <option value="">Semua Tahun</option>
                            @foreach($tahunList as $th)
                            <option value="{{ $th }}">{{ $th }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Bayar -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Status Tagihan</label>
                        <select x-model="batchStatus" @change="fetchBatchInvoices()" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs rounded-xl px-3 py-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                            <option value="">Semua Status Tagihan</option>
                            @foreach($statusBillList as $sb)
                                @php
                                    $descLower = strtolower($sb->desc_bill_lay ?? '');
                                @endphp
                                @if(!str_contains($descLower, 'cancel midtrans') && !str_contains($descLower, 'expire midtrans') && !in_array((string)$sb->status_bill_lay, ['17', '18']))
                                <option value="{{ $sb->status_bill_lay }}">{{ $sb->desc_bill_lay }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Search Input & Cari Button -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Cari Pelanggan</label>
                        <div class="flex gap-1.5">
                            <div class="relative flex-1">
                                <input type="text"
                                       x-model="batchSearch"
                                       @keydown.enter.prevent="fetchBatchInvoices()"
                                       placeholder="Nama / No Internet / Inv..."
                                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs rounded-xl pl-3 pr-7 py-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                                <button type="button"
                                        x-show="batchSearch.length > 0"
                                        @click="batchSearch = ''; fetchBatchInvoices()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer text-sm leading-none">&times;</button>
                            </div>
                            <button type="button"
                                    @click="fetchBatchInvoices()"
                                    class="px-3.5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold shadow-sm transition shrink-0 cursor-pointer flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <span>Cari</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Invoices List Table (Strictly Scrollable with Inline Styles) -->
            <div style="flex: 1 1 auto; height: 0; min-height: 150px; max-height: calc(82vh - 180px); overflow-y: scroll; overflow-x: auto; -webkit-overflow-scrolling: touch; display: block;" class="p-0 relative batch-modal-scroll border-b border-slate-200 dark:border-slate-800">
                <!-- Loading Overlay -->
                <div x-show="batchLoading" class="absolute inset-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xs flex items-center justify-center z-20">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-8 h-8 animate-spin text-cyan-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Memuat data invoice tagihan...</span>
                    </div>
                </div>

                <!-- Empty State -->
                <template x-if="!batchLoading && batchInvoices.length === 0">
                    <div class="py-16 text-center space-y-2">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada data invoice ditemukan</p>
                        <p class="text-xs text-slate-500">Coba ubah filter bulan, tahun, atau kata kunci pencarian.</p>
                    </div>
                </template>

                <!-- Table Content -->
                <table x-show="batchInvoices.length > 0" class="w-full text-left border-collapse text-xs">
                    <thead style="position: sticky; top: 0; z-index: 10;" class="bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">
                                <input type="checkbox"
                                       x-model="batchSelectAll"
                                       @change="toggleBatchSelectAll()"
                                       class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-4 h-4 cursor-pointer">
                            </th>
                            <th class="py-3 px-4">Nama Pelanggan</th>
                            <th class="py-3 px-4">Nomor Internet</th>
                            <th class="py-3 px-4 text-center">Status Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="inv in batchInvoices" :key="inv.kode_billing_layanan">
                            <tr class="hover:bg-cyan-50/40 dark:hover:bg-slate-800/40 transition cursor-pointer"
                                :class="isBatchSelected(inv.kode_billing_layanan) ? 'bg-cyan-50/60 dark:bg-cyan-500/10' : ''"
                                @click="toggleBatchItem(inv)">
                                <td class="py-3 px-4 text-center" @click.stop>
                                    <input type="checkbox"
                                           :checked="isBatchSelected(inv.kode_billing_layanan)"
                                           @change="toggleBatchItem(inv)"
                                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-4 h-4 cursor-pointer">
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="inv.nama_pelanggan"></div>
                                    <div class="text-[11px] text-slate-400 font-mono" x-text="inv.kode_billing_layanan"></div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400" x-text="inv.nomor_internet"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold inline-block"
                                          :class="inv.status_bill_lay == '15' || inv.status_bill_lay == '2' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400' : (inv.status_bill_lay == '13' || inv.status_bill_lay == '1' ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400' : (inv.status_bill_lay == '14' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300'))"
                                          x-text="inv.status_desc"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Modal Footer (Fixed) -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="px-6 py-3.5 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-bold border border-cyan-500/20">
                        <span x-text="selectedBatchCount"></span> tagihan dipilih
                    </span>
                    <template x-if="selectedBatchCount > 0">
                        <button type="button" @click="resetBatchSelection()" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 font-semibold cursor-pointer underline ml-1">
                            Reset Pilihan
                        </button>
                    </template>
                    <span x-show="selectedBatchCount > 0" class="text-slate-400 hidden sm:inline">&bull; Siap dicetak</span>
                </div>

                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <button type="button"
                            @click="batchPrintModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer transition">
                        Batal
                    </button>

                    <button type="button"
                            x-show="batchInvoices.length > 0"
                            @click="printAllFilteredInvoices()"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
                            title="Cetak seluruh data invoice tagihan yang tampil pada periode ini">
                        <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Cetak Semua (<span x-text="batchInvoices.length"></span>)</span>
                    </button>

                    <button type="button"
                            @click="openSelectedPreviewModal()"
                            :disabled="selectedBatchCount === 0"
                            :class="selectedBatchCount === 0 ? 'opacity-50 cursor-not-allowed bg-slate-300 dark:bg-slate-800 text-slate-500' : 'bg-cyan-600 hover:bg-cyan-700 text-white cursor-pointer'"
                            class="px-5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Cetak Terpilih (<span x-text="selectedBatchCount"></span>)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         8. MODAL KONFIRMASI / PREVIEW TAGIHAN TERPILIH
         ========================================== -->
    <div x-show="selectedPreviewModalOpen"
         x-cloak
         @keydown.escape.window="backToBatchSearchModal()"
         class="fixed inset-0 z-[100000] flex items-center justify-center p-3 sm:p-4 bg-slate-950/85 backdrop-blur-sm overflow-hidden"
         style="position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(2, 6, 23, 0.85); overflow: hidden;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="backToBatchSearchModal()"
             x-show="selectedPreviewModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="width: 100%; max-width: 52rem; height: 80vh; max-height: calc(100vh - 3rem); display: flex; flex-direction: column; overflow: hidden; margin: auto;"
             class="relative w-full max-w-4xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col">
            
            <!-- Header Modal Preview -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043A3.746 3.746 0 0 1 21 12Z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Daftar Tagihan Terpilih Siap Cetak</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total <span class="font-bold text-cyan-600 dark:text-cyan-400" x-text="selectedBatchCount"></span> invoice tagihan pelanggan siap dicetak serentak.</p>
                    </div>
                </div>
                <button type="button" @click="backToBatchSearchModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-2xl leading-none">&times;</button>
            </div>

            <!-- Table List of Selected Invoices -->
            <div style="flex: 1 1 auto; height: 0; min-height: 150px; overflow-y: scroll; overflow-x: auto; -webkit-overflow-scrolling: touch; display: block;" class="p-0 relative batch-modal-scroll border-b border-slate-200 dark:border-slate-800">
                <template x-if="selectedBatchCount === 0">
                    <div class="py-16 text-center space-y-2">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Belum ada tagihan yang dipilih</p>
                        <p class="text-xs text-slate-500">Kembali ke jendela pencarian untuk memilih tagihan pelanggan.</p>
                    </div>
                </template>

                <table x-show="selectedBatchCount > 0" class="w-full text-left border-collapse text-xs">
                    <thead style="position: sticky; top: 0; z-index: 10;" class="bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Nama Pelanggan</th>
                            <th class="py-3 px-4">Nomor Internet</th>
                            <th class="py-3 px-4 text-center">Status Bayar</th>
                            <th class="py-3 px-4 w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="(item, idx) in selectedBatchList" :key="item.kode_billing_layanan">
                            <tr class="hover:bg-cyan-50/40 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-xs" x-text="idx + 1"></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="item.nama_pelanggan"></div>
                                    <div class="text-[11px] text-slate-400 font-mono" x-text="item.kode_billing_layanan"></div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400" x-text="item.nomor_internet"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold inline-block"
                                          :class="item.status_bill_lay == '15' || item.status_bill_lay == '2' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400' : (item.status_bill_lay == '13' || item.status_bill_lay == '1' ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400' : (item.status_bill_lay == '14' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300'))"
                                          x-text="item.status_desc"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button"
                                            @click="removeSelectedItem(item.kode_billing_layanan)"
                                            class="px-2 py-1 rounded-lg text-[11px] text-rose-500 hover:text-white hover:bg-rose-500 border border-rose-200 dark:border-rose-900/60 transition cursor-pointer font-medium"
                                            title="Batalkan pilihan ini">
                                        Batal
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Footer Modal Preview -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="px-6 py-3.5 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-bold border border-cyan-500/20">
                        <span x-text="selectedBatchCount"></span> invoice siap cetak
                    </span>
                    <button type="button"
                            x-show="selectedBatchCount > 0"
                            @click="resetBatchSelection(); backToBatchSearchModal()"
                            class="text-xs text-rose-500 hover:text-rose-700 dark:hover:text-red-400 font-semibold cursor-pointer underline ml-1">
                        Hapus Semua
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <button type="button"
                            @click="backToBatchSearchModal()"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer transition">
                        Kembali / Tambah Pilihan
                    </button>

                    <button type="button"
                            @click="executeBatchPrint()"
                            :disabled="selectedBatchCount === 0"
                            :class="selectedBatchCount === 0 ? 'opacity-50 cursor-not-allowed bg-slate-300 dark:bg-slate-800 text-slate-500' : 'bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 text-white cursor-pointer'"
                            class="px-5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Cetak Sekarang (<span x-text="selectedBatchCount"></span> Invoice)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         9. MODAL DAFTAR REQUEST INVOICE DARI PORTAL PELANGGAN
         ========================================== -->
    <div x-show="requestModalOpen"
         x-cloak
         @keydown.escape.window="requestModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/85 backdrop-blur-sm overflow-hidden"
         style="z-index: 999999;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="requestModalOpen = false"
             x-show="requestModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="width: 100%; max-width: 58rem; height: 85vh; max-height: calc(100vh - 3rem); display: flex; flex-direction: column; overflow: hidden; margin: auto;"
             class="relative w-full max-w-5xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col">
            
            <!-- Header Modal Request -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-500/20">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Permintaan Tagihan dari Portal Pelanggan</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300" x-text="requestCount + ' Pending'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Daftar pelanggan yang mengajukan penerbitan tagihan bulan berikutnya secara mandiri.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Filter Tabs -->
                    <div class="flex items-center bg-slate-200/70 dark:bg-slate-800 p-1 rounded-xl text-xs font-semibold">
                        <button type="button"
                                @click="requestStatusFilter = 'pending'; fetchBillingRequests()"
                                :class="requestStatusFilter === 'pending' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                                class="px-3 py-1 rounded-lg transition cursor-pointer">
                            Pending
                        </button>
                        <button type="button"
                                @click="requestStatusFilter = 'all'; fetchBillingRequests()"
                                :class="requestStatusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                                class="px-3 py-1 rounded-lg transition cursor-pointer">
                            Semua Riwayat
                        </button>
                    </div>

                    <button type="button" @click="requestModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-2xl leading-none">&times;</button>
                </div>
            </div>

            <!-- Table List of Requests -->
            <div style="flex: 1 1 auto; height: 0; min-height: 150px; overflow-y: scroll; overflow-x: auto; -webkit-overflow-scrolling: touch; display: block;" class="p-0 relative batch-modal-scroll border-b border-slate-200 dark:border-slate-800">
                <!-- Loading State -->
                <template x-if="requestLoading">
                    <div class="py-20 text-center text-slate-400">
                        <div class="inline-block w-7 h-7 border-2 border-violet-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                        <div class="text-xs font-medium text-slate-600 dark:text-slate-300">Memuat permintaan tagihan...</div>
                    </div>
                </template>

                <!-- Empty State -->
                <template x-if="!requestLoading && requestList.length === 0">
                    <div class="py-20 text-center">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/40 flex items-center justify-center text-violet-500 mb-3.5 shadow-sm">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div class="font-bold text-sm text-slate-800 dark:text-slate-200">Tidak Ada Permintaan Tagihan Pending</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">Saat ini belum ada pengajuan invoice mandiri dari pelanggan portal yang menunggu persetujuan.</div>
                    </div>
                </template>

                <!-- Request Records Table -->
                <table x-show="!requestLoading && requestList.length > 0" class="w-full text-left border-collapse text-xs">
                    <thead style="position: sticky; top: 0; z-index: 10;" class="bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Pelanggan</th>
                            <th class="py-3 px-4">Layanan</th>
                            <th class="py-3 px-4">Periode Diminta</th>
                            <th class="py-3 px-4">Nominal</th>
                            <th class="py-3 px-4">Waktu Request</th>
                            <th class="py-3 px-4 text-center">Status / Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="(req, idx) in requestList" :key="req.id">
                            <tr class="hover:bg-violet-50/40 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-xs" x-text="idx + 1"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="req.nama_pelanggan"></div>
                                    <div class="text-[11px] text-blue-600 dark:text-blue-400 font-mono font-semibold" x-text="req.nomor_internet"></div>
                                    <template x-if="req.catatan_pelanggan && req.catatan_pelanggan !== '-'">
                                        <div class="text-[10px] text-slate-500 italic mt-0.5" x-text="'Catatan: ' + req.catatan_pelanggan"></div>
                                    </template>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="req.layanan"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-violet-700 dark:text-violet-400 font-mono text-xs" x-text="req.periode_tagihan"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'Bulan ' + req.bulan_tagihan + '/' + req.tahun_tagihan"></div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-emerald-600 dark:text-emerald-400" x-text="req.nominal_formatted"></td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-800 dark:text-slate-200 text-xs" x-text="req.created_at_formatted"></div>
                                    <div class="text-[10px] text-slate-400" x-text="req.created_at_diff"></div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <template x-if="req.status_request === 'pending'">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button"
                                                    @click="approveRequest(req.id)"
                                                    :disabled="requestActionLoadingId === req.id"
                                                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Setujui</span>
                                            </button>
                                            <button type="button"
                                                    @click="rejectRequest(req.id)"
                                                    :disabled="requestActionLoadingId === req.id"
                                                    class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 border border-rose-200 dark:border-rose-800 font-semibold text-xs transition cursor-pointer">
                                                Tolak
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="req.status_request === 'approved'">
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>Disetujui</span>
                                            </span>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="req.kode_billing_layanan"></div>
                                        </div>
                                    </template>

                                    <template x-if="req.status_request === 'rejected'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                            <span>Ditolak</span>
                                        </span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Footer Modal Request -->
            <div style="flex-shrink: 0; flex-grow: 0;" class="px-6 py-3.5 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400 font-bold border border-violet-500/20">
                        <span x-text="requestCount"></span> permintaan menunggu persetujuan
                    </span>
                </div>

                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <button type="button"
                            @click="requestModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer transition">
                        Tutup
                    </button>

                    <button type="button"
                            x-show="requestCount > 0"
                            @click="approveAllRequests()"
                            class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer shadow-none">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        <span>Setujui Semua Permintaan (<span x-text="requestCount"></span>)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function billingLayananPage() {
    return {
        actionDropdownOpen: false,
        // Modal Request Invoice Portal Pelanggan
        requestModalOpen: false,
        requestCount: {{ $pendingRequestCount ?? 0 }},
        requestStatusFilter: 'pending',
        requestList: [],
        requestLoading: false,
        requestActionLoadingId: null,

        async openRequestModal() {
            this.actionDropdownOpen = false;
            this.requestModalOpen = true;
            await this.fetchBillingRequests();
        },
        async fetchBillingRequests() {
            this.requestLoading = true;
            try {
                const res = await fetch('/finance/billing-layanan/requests?status=' + encodeURIComponent(this.requestStatusFilter));
                const data = await res.json();
                if (data && data.success) {
                    this.requestList = data.data || [];
                    if (this.requestStatusFilter === 'pending') {
                        this.requestCount = data.count || 0;
                    }
                } else {
                    this.requestList = [];
                }
            } catch (e) {
                console.error('Error fetching billing requests:', e);
                this.requestList = [];
            } finally {
                this.requestLoading = false;
            }
        },
        async approveRequest(id) {
            if (!confirm('Setujui permintaan invoice ini dan terbitkan tagihan ke sistem?')) return;
            this.requestActionLoadingId = id;
            try {
                const res = await fetch(`/finance/billing-layanan/requests/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data && data.success) {
                    alert(data.message || 'Invoice berhasil diterbitkan.');
                    await this.fetchBillingRequests();
                    window.location.reload();
                } else {
                    alert(data.message || 'Gagal menyetujui permintaan invoice.');
                }
            } catch (e) {
                console.error('Approve request error:', e);
                alert('Terjadi kesalahan sistem.');
            } finally {
                this.requestActionLoadingId = null;
            }
        },
        async approveAllRequests() {
            if (!confirm(`Apakah Anda yakin ingin menyetujui dan menerbitkan seluruh (${this.requestCount}) invoice yang diminta pelanggan?`)) return;
            this.requestLoading = true;
            try {
                const res = await fetch('/finance/billing-layanan/requests/approve-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data && data.success) {
                    alert(data.message || 'Seluruh invoice berhasil diterbitkan.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Gagal memproses persetujuan massal.');
                }
            } catch (e) {
                console.error('Approve all error:', e);
                alert('Terjadi kesalahan sistem.');
            } finally {
                this.requestLoading = false;
            }
        },
        async rejectRequest(id) {
            const note = prompt('Masukkan alasan penolakan permintaan invoice:');
            if (note === null) return;
            this.requestActionLoadingId = id;
            try {
                const res = await fetch(`/finance/billing-layanan/requests/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ note: note })
                });
                const data = await res.json();
                if (data && data.success) {
                    alert(data.message || 'Permintaan invoice telah ditolak.');
                    await this.fetchBillingRequests();
                } else {
                    alert(data.message || 'Gagal menolak permintaan invoice.');
                }
            } catch (e) {
                console.error('Reject request error:', e);
                alert('Terjadi kesalahan sistem.');
            } finally {
                this.requestActionLoadingId = null;
            }
        },

        // Filter State
        showAdvancedFilters: false,
        // Modal Generate Invoice
        generateModalOpen: false,
        generateJenis: 'single',
        generateLayanan: '',
        generateBulan: '{{ $selectedBulan ?: date('m') }}',
        generateTahun: '{{ $selectedTahun ?: date('Y') }}',
        generateKirimWa: true,
        generateKirimEmail: true,
        generatePpn: 'default',
        generateAutoPublish: 'yes',
        generateSearch: '',
        generatePerPage: 10,
        generatePage: 1,
        generateCandidates: [],
        generateLoading: false,
        async openGenerateModal() {
            this.generateModalOpen = true;
            this.generatePage = 1;
            this.generateSearch = '';
            if (this.generateLayanan) {
                await this.fetchGenerateCandidates();
            } else {
                this.generateCandidates = [];
                this.generateLoading = false;
            }
        },
        async fetchGenerateCandidates() {
            if (!this.generateLayanan) {
                this.generateCandidates = [];
                this.generateLoading = false;
                return;
            }
            this.generateLoading = true;
            this.generatePage = 1;
            try {
                const params = new URLSearchParams({
                    bulan: this.generateBulan || '',
                    tahun: this.generateTahun || '',
                    layanan: this.generateLayanan || '',
                    search: this.generateSearch || '',
                });
                const res = await fetch('/finance/billing-layanan/generate-candidates?' + params.toString());
                if (res.ok) {
                    const data = await res.json();
                    this.generateCandidates = (data && data.data) ? data.data : (data.candidates || []);
                } else {
                    this.generateCandidates = [];
                }
            } catch (e) {
                console.error('Error fetching candidates:', e);
                this.generateCandidates = [];
            } finally {
                this.generateLoading = false;
            }
        },
        get paginatedCandidates() {
            const start = (this.generatePage - 1) * this.generatePerPage;
            return this.generateCandidates.slice(start, start + this.generatePerPage);
        },
        get totalCandidatePages() {
            return Math.ceil(this.generateCandidates.length / this.generatePerPage) || 1;
        },
        candidateFirstPage() { this.generatePage = 1; },
        candidatePrevPage() { if (this.generatePage > 1) this.generatePage--; },
        candidateNextPage() { if (this.generatePage < this.totalCandidatePages) this.generatePage++; },
        candidateLastPage() { this.generatePage = this.totalCandidatePages; },

        // Modal Batch Print Invoices
        batchPrintModalOpen: false,
        selectedPreviewModalOpen: false,
        batchBulan: '{{ request('bulan') ?: ($selectedBulan ?: date('m')) }}',
        batchTahun: '{{ request('tahun') ?: ($selectedTahun ?: date('Y')) }}',
        batchSearch: '',
        batchStatus: '{{ request('status_bayar', '') }}',
        batchTab: 'all', // 'all' or 'selected'
        batchInvoices: [],
        pageInvoices: @json($pageInvoices),
        selectedBatchMap: {},
        batchLoading: false,
        batchSelectAll: false,

        get displayedBatchInvoices() {
            if (this.batchTab === 'selected') {
                return Object.values(this.selectedBatchMap);
            }
            return this.batchInvoices || [];
        },
        get selectedBatchCount() {
            return Object.keys(this.selectedBatchMap).length;
        },
        get selectedBatchList() {
            return Object.values(this.selectedBatchMap);
        },
        get selectedBatchKodes() {
            return Object.keys(this.selectedBatchMap);
        },

        async openBatchPrintModal() {
            this.actionDropdownOpen = false;
            this.batchPrintModalOpen = true;
            this.batchTab = 'all';
            this.batchSearch = '';
            if (!this.batchBulan) {
                this.batchBulan = '{{ request('bulan') ?: ($selectedBulan ?: date('m')) }}';
            }
            if (!this.batchTahun) {
                this.batchTahun = '{{ request('tahun') ?: ($selectedTahun ?: date('Y')) }}';
            }
            await this.fetchBatchInvoices();
        },
        async fetchBatchInvoices() {
            this.batchLoading = true;
            try {
                const params = new URLSearchParams({
                    bulan: this.batchBulan || '',
                    tahun: this.batchTahun || '',
                    search: this.batchSearch || '',
                    status_bayar: this.batchStatus || ''
                });
                const res = await fetch('/finance/dokumen/batch-invoice/search?' + params.toString());
                const data = await res.json();
                if (data && data.success && Array.isArray(data.data)) {
                    this.batchInvoices = data.data;
                } else if (this.pageInvoices && this.pageInvoices.length > 0 && !this.batchSearch && !this.batchStatus) {
                    this.batchInvoices = [...this.pageInvoices];
                } else {
                    this.batchInvoices = (data && data.data) ? data.data : [];
                }
            } catch (e) {
                console.error('Error fetching batch invoices:', e);
                if (this.pageInvoices && this.pageInvoices.length > 0) {
                    this.batchInvoices = [...this.pageInvoices];
                }
            } finally {
                this.batchLoading = false;
                this.updateBatchSelectAllState();
            }
        },
        isBatchSelected(kode) {
            return Boolean(this.selectedBatchMap[kode]);
        },
        toggleBatchItem(inv) {
            if (!inv || !inv.kode_billing_layanan) return;
            if (this.selectedBatchMap[inv.kode_billing_layanan]) {
                delete this.selectedBatchMap[inv.kode_billing_layanan];
            } else {
                this.selectedBatchMap[inv.kode_billing_layanan] = {
                    kode_billing_layanan: inv.kode_billing_layanan,
                    nama_pelanggan: inv.nama_pelanggan || 'Pelanggan',
                    nomor_internet: inv.nomor_internet || '-',
                    status_desc: inv.status_desc || 'Tagihan',
                    status_bill_lay: inv.status_bill_lay || ''
                };
            }
            this.selectedBatchMap = { ...this.selectedBatchMap };
            this.updateBatchSelectAllState();
        },
        removeSelectedItem(kode) {
            if (this.selectedBatchMap[kode]) {
                delete this.selectedBatchMap[kode];
                this.selectedBatchMap = { ...this.selectedBatchMap };
                this.updateBatchSelectAllState();
            }
        },
        toggleBatchSelectAll() {
            const list = this.displayedBatchInvoices;
            if (this.batchSelectAll) {
                list.forEach(inv => {
                    if (inv && inv.kode_billing_layanan) {
                        this.selectedBatchMap[inv.kode_billing_layanan] = {
                            kode_billing_layanan: inv.kode_billing_layanan,
                            nama_pelanggan: inv.nama_pelanggan || 'Pelanggan',
                            nomor_internet: inv.nomor_internet || '-',
                            status_desc: inv.status_desc || 'Tagihan',
                            status_bill_lay: inv.status_bill_lay || ''
                        };
                    }
                });
            } else {
                list.forEach(inv => {
                    if (inv && inv.kode_billing_layanan) {
                        delete this.selectedBatchMap[inv.kode_billing_layanan];
                    }
                });
            }
            this.selectedBatchMap = { ...this.selectedBatchMap };
            this.updateBatchSelectAllState();
        },
        updateBatchSelectAllState() {
            const list = this.displayedBatchInvoices;
            if (!list || list.length === 0) {
                this.batchSelectAll = false;
                return;
            }
            this.batchSelectAll = list.every(inv => Boolean(this.selectedBatchMap[inv.kode_billing_layanan]));
        },
        resetBatchSelection() {
            this.selectedBatchMap = {};
            this.selectedBatchMap = { ...this.selectedBatchMap };
            this.updateBatchSelectAllState();
        },
        openSelectedPreviewModal() {
            if (this.selectedBatchCount === 0) {
                alert('Silakan pilih minimal 1 tagihan pelanggan untuk dicetak.');
                return;
            }
            this.batchPrintModalOpen = false;
            this.selectedPreviewModalOpen = true;
        },
        backToBatchSearchModal() {
            this.selectedPreviewModalOpen = false;
            this.batchPrintModalOpen = true;
        },
        executeBatchPrint() {
            const kodes = Object.keys(this.selectedBatchMap);
            if (kodes.length === 0) {
                alert('Silakan pilih minimal 1 tagihan pelanggan untuk dicetak.');
                return;
            }
            const url = '/finance/dokumen/batch-invoice?kodes=' + encodeURIComponent(kodes.join(','));
            window.open(url, '_blank');
        },
        printSelectedInvoices() {
            this.openSelectedPreviewModal();
        },
        printAllFilteredInvoices() {
            const params = new URLSearchParams({
                bulan: this.batchBulan || '',
                tahun: this.batchTahun || '',
                search: this.batchSearch || '',
                status_bayar: this.batchStatus || ''
            });
            const url = '/finance/dokumen/batch-invoice?' + params.toString();
            window.open(url, '_blank');
        },

        // Modal Detail Breakdown
        detailModalOpen: false,
        detailLoading: false,
        detailData: { invoice: {}, items: [], logs: [] },
        async openDetailModal(kodeBilling) {
            this.detailLoading = true;
            this.detailModalOpen = true;
            try {
                const res = await fetch('/finance/api/billing-layanan-detail?kode_billing=' + encodeURIComponent(kodeBilling));
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

        // Modal Konfirmasi Bayar
        payModalOpen: false,
        payKodeBilling: '',
        payNomorInternet: '',
        payNamaPelanggan: '',
        payNominal: 0,
        payMetode: 'transfer',
        payBank: 'BCA',
        payNamaKolektor: '',
        payNoKwitansi: '',
        payCatatan: '',
        openPayModal(kodeBilling, noInternet, nama, nominal, paymentType = '2', destinationBank = '') {
            this.payKodeBilling = kodeBilling;
            this.payNomorInternet = noInternet;
            this.payNamaPelanggan = nama;
            this.payNominal = parseFloat(nominal) || 0;
            this.payNamaKolektor = '';
            this.payNoKwitansi = '';
            this.payDestinationBank = destinationBank ? String(destinationBank).trim() : '';
            const pType = String(paymentType || '2');
            if (pType === '3' || pType === 'cash') {
                this.payMetode = 'cash';
                this.payBank = this.payDestinationBank || 'Cash To Collector';
                this.payCatatan = 'Pembayaran Cash to Collector Terverifikasi';
            } else {
                this.payMetode = 'transfer';
                this.payBank = this.payDestinationBank || 'BCA';
                this.payCatatan = 'Pembayaran Transfer Terverifikasi';
            }
            this.payModalOpen = true;
        },
        openPayModalFromEl(el) {
            this.openPayModal(
                el.dataset.kode,
                el.dataset.internet,
                el.dataset.nama,
                el.dataset.nominal,
                el.dataset.paymentType || '2',
                el.dataset.destinationBank || ''
            );
        },

        // Modal Adjustment
        adjustModalOpen: false,
        adjustKodeBilling: '',
        adjustNomorInternet: '',
        adjustNamaPelanggan: '',
        adjustSubtotal: 0,
        adjustPotongan: 0,
        adjustDescPotongan: '',
        adjustDenda: 0,
        adjustNote: '',
        openAdjustModal(kodeBilling, noInternet, nama, subtotal, potongan, descPotongan, denda) {
            this.adjustKodeBilling = kodeBilling;
            this.adjustNomorInternet = noInternet;
            this.adjustNamaPelanggan = nama;
            this.adjustSubtotal = parseFloat(subtotal) || 0;
            this.adjustPotongan = parseFloat(potongan) || 0;
            this.adjustDescPotongan = descPotongan || '';
            this.adjustDenda = parseFloat(denda) || 0;
            this.adjustNote = 'Penyesuaian tagihan pelanggan';
            this.adjustModalOpen = true;
        },
        openAdjustModalFromEl(el) {
            this.adjustKodeBilling = el.dataset.kode;
            this.adjustNomorInternet = el.dataset.internet;
            this.adjustNamaPelanggan = el.dataset.nama;
            this.adjustSubtotal = parseFloat(el.dataset.subtotal) || 0;
            this.adjustPotongan = parseFloat(el.dataset.potongan) || 0;
            this.adjustDescPotongan = el.dataset.descPotongan || '';
            this.adjustDenda = parseFloat(el.dataset.denda) || 0;
            this.adjustNote = 'Penyesuaian tagihan pelanggan';
            this.adjustModalOpen = true;
        },
        get adjustTotalBaru() {
            return Math.max(0, this.adjustSubtotal - (parseFloat(this.adjustPotongan) || 0) + (parseFloat(this.adjustDenda) || 0));
        },

        // Modal Rollback
        rollbackModalOpen: false,
        rollbackKodeBilling: '',
        rollbackNamaPelanggan: '',
        openRollbackModal(kodeBilling, nama) {
            this.rollbackKodeBilling = kodeBilling;
            this.rollbackNamaPelanggan = nama;
            this.rollbackModalOpen = true;
        },
        openRollbackModalFromEl(el) {
            this.rollbackKodeBilling = el.dataset.kode;
            this.rollbackNamaPelanggan = el.dataset.nama;
            this.rollbackModalOpen = true;
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

        // Modal Midtrans Payment Link
        midtransModalOpen: false,
        midtransKode: '',
        midtransNama: '',
        midtransNominal: 0,
        midtransUrl: '',
        midtransExpiry: '',
        midtransIsExpired: false,
        midtransWaUrl: '',
        copied: false,
        openMidtransModalFromEl(el) {
            this.midtransKode = el.dataset.kode;
            this.midtransNama = el.dataset.nama;
            this.midtransNominal = parseFloat(el.dataset.nominal) || 0;
            this.midtransUrl = el.dataset.url || '';
            this.midtransExpiry = el.dataset.expiry || '';
            this.midtransIsExpired = el.dataset.expired === '1';
            this.midtransWaUrl = el.dataset.waUrl || '';
            this.copied = false;
            this.midtransModalOpen = true;
        },
        async copyMidtransUrl() {
            if (!this.midtransUrl) return;
            try {
                await navigator.clipboard.writeText(this.midtransUrl);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            } catch (err) {
                const ta = document.createElement('textarea');
                ta.value = this.midtransUrl;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            }
        },

        // Modal Bukti Transfer Pelanggan
        proofModalOpen: false,
        proofKodeBilling: '',
        proofNamaPelanggan: '',
        proofInternet: '',
        proofNominal: 0,
        proofUrl: '',
        proofNotes: '',
        proofDate: '',
        proofStatus: '',
        proofDestinationBank: '',
        openProofModalFromEl(el) {
            this.proofKodeBilling = el.dataset.kode || '';
            this.proofNamaPelanggan = el.dataset.nama || '';
            this.proofInternet = el.dataset.internet || '';
            this.proofNominal = parseFloat(el.dataset.nominal) || 0;
            this.proofUrl = el.dataset.proofUrl || '';
            this.proofNotes = el.dataset.notes || '';
            this.proofDate = el.dataset.proofDate || '';
            this.proofStatus = el.dataset.status || '';
            this.proofDestinationBank = el.dataset.destinationBank || '';
            this.proofModalOpen = true;
        },

        // Format Currency Helper
        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
        }
    };
}
</script>
@endpush
