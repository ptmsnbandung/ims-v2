@extends('layouts.app')

@section('title', 'Billing Layanan - IMS Router')

@section('content')
<div class="space-y-6"
     x-data="{
         // Filter State
         showAdvancedFilters: false,
         
         // Modal Generate Invoice
         generateModalOpen: false,
         generateBulan: '{{ $selectedBulan ?: date('m') }}',
         generateTahun: '{{ $selectedTahun ?: date('Y') }}',

         // Modal Detail Breakdown
         detailModalOpen: false,
         detailLoading: false,
         detailData: {
             invoice: {},
             items: [],
             logs: []
         },
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
         payCatatan: '',
         openPayModal(kodeBilling, noInternet, nama, nominal) {
             this.payKodeBilling = kodeBilling;
             this.payNomorInternet = noInternet;
             this.payNamaPelanggan = nama;
             this.payNominal = parseFloat(nominal) || 0;
             this.payMetode = 'transfer';
             this.payBank = 'BCA';
             this.payCatatan = 'Pembayaran Transfer Terverifikasi';
             this.payModalOpen = true;
         },
         openPayModalFromEl(el) {
             this.payKodeBilling = el.dataset.kode;
             this.payNomorInternet = el.dataset.internet;
             this.payNamaPelanggan = el.dataset.nama;
             this.payNominal = parseFloat(el.dataset.nominal) || 0;
             this.payMetode = 'transfer';
             this.payBank = 'BCA';
             this.payCatatan = 'Pembayaran Tagihan Bulanan Terverifikasi';
             this.payModalOpen = true;
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
             this.midtransUrl = el.dataset.midtransUrl || '';
             this.midtransExpiry = el.dataset.expiry || '';
             this.midtransIsExpired = el.dataset.isExpired === '1';
             this.midtransWaUrl = el.dataset.waUrl || '';
             this.copied = false;
             this.midtransModalOpen = true;
         },
         copyMidtransLink() {
             if (this.midtransUrl) {
                 if (navigator.clipboard) {
                     navigator.clipboard.writeText(this.midtransUrl);
                 } else {
                     const temp = document.createElement('textarea');
                     temp.value = this.midtransUrl;
                     document.body.appendChild(temp);
                     temp.select();
                     document.execCommand('copy');
                     document.body.removeChild(temp);
                 }
                 this.copied = true;
                 setTimeout(() => { this.copied = false; }, 2500);
             }
         },

         // Format Currency Helper
         formatRupiah(num) {
             return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
         }
     }">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-900/80 backdrop-blur-xl p-5 rounded-2xl border border-slate-800/80 shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-400 uppercase tracking-wider mb-1">
                <span>Finance & Billing</span>
                <span>&bull;</span>
                <span class="text-slate-400">Recurring Invoicing</span>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                Billing Layanan Bulanan
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Penerbitan tagihan berkala, pemantauan pembayaran pelanggan, integrasi payment gateway, dan konfirmasi kas masuk.
            </p>
        </div>

        <!-- Top Right Actions -->
        <div class="flex items-center gap-3 relative z-10 flex-wrap">
            <a href="{{ route('finance.billing-layanan.export', request()->query()) }}"
               class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700/80 text-xs font-semibold flex items-center gap-2 shadow-sm transition">
                <svg class="w-4 h-4 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Export CSV</span>
            </a>

            <button @click="generateModalOpen = true"
                    type="button"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-semibold flex items-center gap-2 shadow-lg shadow-blue-500/25 border border-blue-400/20 transition duration-150">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Generate Invoice Massal</span>
            </button>
        </div>
    </div>

    <!-- Alert Flash Notifications -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3 backdrop-blur-md">
        <svg class="w-5 h-5 flex-shrink-0 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center gap-3 backdrop-blur-md">
        <svg class="w-5 h-5 flex-shrink-0 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- 4 Glowing KPI Financial Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Generating / Draft (Yellow / Amber) -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-500/10 via-slate-900/90 to-slate-900/95 border border-amber-500/20 shadow-lg relative overflow-hidden group hover:border-amber-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-semibold text-amber-400 tracking-wide uppercase">Draft / Auto Publish</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-white tracking-tight">
                    Rp {{ number_format($kpis['generating']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-amber-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                    <span>{{ number_format($kpis['generating']['count']) }} Invoice Menunggu Terbit</span>
                </div>
            </div>
        </div>

        <!-- 2. Publish Billing (Blue) -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500/10 via-slate-900/90 to-slate-900/95 border border-blue-500/20 shadow-lg relative overflow-hidden group hover:border-blue-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-semibold text-blue-400 tracking-wide uppercase">Publish Billing</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-white tracking-tight">
                    Rp {{ number_format($kpis['publish']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-blue-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                    <span>{{ number_format($kpis['publish']['count']) }} Invoice Terbit Aktif</span>
                </div>
            </div>
        </div>

        <!-- 3. Waiting Payment (Teal / Cyan) -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-cyan-500/10 via-slate-900/90 to-slate-900/95 border border-cyan-500/20 shadow-lg relative overflow-hidden group hover:border-cyan-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-cyan-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-semibold text-cyan-400 tracking-wide uppercase">Waiting Payment</span>
                <span class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-white tracking-tight">
                    Rp {{ number_format($kpis['waiting']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-cyan-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    <span>{{ number_format($kpis['waiting']['count']) }} Invoice Menunggu Pelunasan</span>
                </div>
            </div>
        </div>

        <!-- 4. Paid (Coral / Rose / Emerald Inflow) -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-rose-500/10 via-slate-900/90 to-slate-900/95 border border-rose-500/20 shadow-lg relative overflow-hidden group hover:border-rose-500/40 transition">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-rose-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs font-semibold text-rose-400 tracking-wide uppercase">Paid (Kas Masuk)</span>
                <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 relative z-10">
                <div class="text-xl font-black text-white tracking-tight">
                    Rp {{ number_format($kpis['paid']['amount'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-rose-300/80 font-medium mt-1 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    <span>{{ number_format($kpis['paid']['count']) }} Invoice Telah Terbayar</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-slate-900/90 backdrop-blur-xl p-5 rounded-2xl border border-slate-800 shadow-xl space-y-4">
        <form method="GET" action="{{ route('finance.billing-layanan') }}" class="space-y-4">
            <!-- Row 1: Primary Quick Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Bulan -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Semua Bulan</label>
                    <select name="bulan" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanList as $key => $name)
                        <option value="{{ $key }}" {{ ($selectedBulan ?? '') === (string)$key ? 'selected' : '' }}>{{ $key }} - {{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tahun -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Semua Tahun</label>
                    <select name="tahun" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Tahun</option>
                        @foreach($tahunList as $thn)
                        <option value="{{ $thn }}" {{ ($selectedTahun ?? '') === (string)$thn ? 'selected' : '' }}>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Layanan / Kategori -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Kategori Layanan</label>
                    <select name="layanan" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Layanan</option>
                        @foreach($layananList as $lay)
                        <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ $lay }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Bayar -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Status Bayar</label>
                    <select name="status_bayar" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        <option value="">Semua Status Bayar</option>
                        @foreach($statusBillList as $sb)
                        <option value="{{ $sb->status_bill_lay }}" {{ request('status_bayar') === (string)$sb->status_bill_lay ? 'selected' : '' }}>{{ $sb->desc_bill_lay }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Real-time Search Box -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Pencarian</label>
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Nama / No Layanan / Inv..."
                               class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl pl-9 pr-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder-slate-500">
                        <svg class="w-4 h-4 text-slate-500 absolute left-3 top-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Row 2: Secondary / Advanced Filters (Collapsible) -->
            <div x-show="showAdvancedFilters" x-cloak x-collapse class="pt-3 border-t border-slate-800/80">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Wilayah / Kota -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Semua Wilayah</label>
                        <select name="wilayah" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                            <option value="">Semua Wilayah</option>
                            @foreach($wilayahList as $wil)
                            <option value="{{ $wil }}" {{ request('wilayah') === $wil ? 'selected' : '' }}>{{ $wil }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status User -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Status User</label>
                        <select name="status_user" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                            <option value="">Semua Status User</option>
                            @foreach($statusUserList as $code => $label)
                            <option value="{{ $code }}" {{ request('status_user') === (string)$code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Metode Bayar -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Metode Bayar</label>
                        <select name="metode_bayar" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
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
                        class="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4 transition-transform" :class="showAdvancedFilters ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                    <span x-text="showAdvancedFilters ? 'Sembunyikan Filter Lanjutan' : 'Tampilkan Filter Lanjutan (Wilayah, Status User, Metode)'"></span>
                </button>

                <div class="flex items-center gap-2">
                    <a href="{{ route('finance.billing-layanan') }}"
                       class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition">
                        Reset
                    </a>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-md shadow-blue-500/25 transition">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Invoices Data Table -->
    <div class="bg-slate-900/90 backdrop-blur-xl rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <!-- Table Header Info & Per Page -->
        <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-950/40">
            <div class="text-xs text-slate-400 font-medium">
                Menampilkan <span class="text-white font-bold">{{ $invoices->firstItem() ?? 0 }}</span> sampai <span class="text-white font-bold">{{ $invoices->lastItem() ?? 0 }}</span> dari <span class="text-white font-bold">{{ $invoices->total() }}</span> total invoice
            </div>

            <!-- Per page selector form -->
            <form method="GET" action="{{ route('finance.billing-layanan') }}" class="flex items-center gap-2">
                @foreach(request()->except(['per_page', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label class="text-xs text-slate-400">Tampilkan:</label>
                <select name="per_page" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 text-slate-300 text-xs rounded-lg px-2.5 py-1 focus:border-blue-500">
                    <option value="10" {{ $invoices->perPage() == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $invoices->perPage() == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $invoices->perPage() == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $invoices->perPage() == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span class="text-xs text-slate-400">entri</span>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800/80 bg-slate-950/60 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Billing Info</th>
                        <th class="py-3.5 px-4">Tanggal & Jatuh Tempo</th>
                        <th class="py-3.5 px-4">Periode</th>
                        <th class="py-3.5 px-4">Nominal Tagihan</th>
                        <th class="py-3.5 px-4">Status & Wilayah</th>
                        <th class="py-3.5 px-4">Metode Bayar</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-800/30 transition duration-150 group">
                        <!-- 1. Billing Info -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="font-bold text-white tracking-wide text-xs">
                                {{ $inv->kode_billing_layanan }}
                            </div>
                            <div class="font-semibold text-blue-400 hover:text-blue-300 mt-0.5 flex items-center gap-1.5">
                                <span>{{ $inv->nama_pelanggan }}</span>
                                <span class="text-[10px] px-1 py-0.2 rounded bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $inv->jenis_kelamin == 2 ? 'P' : 'L' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                                <span class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-300 border border-blue-500/20 text-[10px] font-semibold">
                                    {{ $inv->nama_kategori_bandwith ?? 'BROADBAND' }} {{ $inv->nominal_bandwith }} Mbps
                                </span>
                                <span class="text-slate-500 font-mono text-[10px]">#{{ $inv->nomor_internet }}</span>
                            </div>
                        </td>

                        <!-- 2. Tanggal & Jatuh Tempo -->
                        <td class="py-3.5 px-4 align-top">
                            @if($inv->payment_publish)
                            <div class="text-slate-300 font-medium">
                                Terbit: <span class="text-white">{{ date('d M Y H:i', strtotime($inv->payment_publish)) }}</span>
                            </div>
                            <div class="text-slate-400 text-[11px] mt-0.5">
                                Jth Tempo: <span class="text-amber-400 font-medium">{{ $inv->expiry ? date('d M Y', strtotime($inv->expiry)) : '-' }}</span>
                            </div>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                Billing Belum di-Publish
                            </span>
                            @endif

                            @if($inv->invoice_file)
                            <div class="mt-1.5">
                                <a href="#" onclick="alert('File PDF: {{ $inv->invoice_file }}')" class="inline-flex items-center gap-1 text-[11px] text-blue-400 hover:underline">
                                    <svg class="w-3.5 h-3.5 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span class="truncate max-w-[140px]">{{ $inv->invoice_file }}</span>
                                </a>
                            </div>
                            @endif
                        </td>

                        <!-- 3. Periode -->
                        <td class="py-3.5 px-4 align-top">
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 border border-slate-700 text-xs font-semibold">
                                {{ $inv->periode_tagihan ?? ($inv->bulan_tagihan . '/' . $inv->tahun_tagihan) }}
                            </span>
                        </td>

                        <!-- 4. Nominal Tagihan -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold text-slate-300">
                                    Tagihan: <span class="text-white font-bold">Rp {{ number_format((float) ($inv->total_layanan ?? ($inv->harga_bandwith ?? 0)), 0, ',', '.') }}</span>
                                </div>
                                
                                @if((float)($inv->potongan ?? 0) > 0)
                                <div class="text-[10px] text-emerald-400">
                                    Diskon: -Rp {{ number_format((float) ($inv->potongan ?? 0), 0, ',', '.') }}
                                </div>
                                @endif

                                @if((float)($inv->denda ?? 0) > 0)
                                <div class="text-[10px] text-rose-400">
                                    Denda: +Rp {{ number_format((float) ($inv->denda ?? 0), 0, ',', '.') }}
                                </div>
                                @endif

                                <div class="text-[11px] font-medium text-slate-400 flex items-center gap-1 pt-0.5">
                                    <span>Dibayar:</span>
                                    <span class="{{ $inv->status_bill_lay == '15' ? 'text-emerald-400 font-bold' : 'text-slate-400' }}">
                                        Rp {{ number_format((float) ($inv->amount_paid ?? 0), 0, ',', '.') }}
                                    </span>
                                    @if($inv->status_bill_lay == '15')
                                    <svg class="w-3.5 h-3.5 text-emerald-400 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
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
                        @endphp

                        <!-- 5. Status & Wilayah -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="space-y-1">
                                <!-- User state + City -->
                                <div class="text-[11px] text-slate-300 font-medium truncate max-w-[170px]">
                                    <span class="text-blue-400 font-semibold">{{ $inv->desc_registrasi ?? 'User Aktif' }}</span>
                                    <span class="text-slate-500">&middot;</span>
                                    <span class="text-slate-400">{{ $inv->nama_kota_pasang ?? '-' }}</span>
                                </div>

                                <!-- Status Tagihan Badge -->
                                <div>
                                    @if($inv->status_bill_lay == '15')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>PAID (Lunas)</span>
                                    </span>
                                    @elseif($inv->status_bill_lay == '13')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                        <span>PUBLISH BILLING</span>
                                    </span>
                                    @elseif($inv->status_bill_lay == '14')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                                        <span>WAITING PAYMENT</span>
                                    </span>
                                    @elseif(in_array($inv->status_bill_lay, ['11', '12']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        <span>{{ $inv->desc_bill_lay ?? 'DRAFT' }}</span>
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
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
                                @if($inv->payment_type == 1)
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[10px] font-semibold">
                                        <svg class="w-3 h-3 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                        </svg>
                                        <span>Midtrans (Online)</span>
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
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[9px] font-medium hover:bg-emerald-500/20 transition cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
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
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[9px] font-medium hover:bg-rose-500/20 transition cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        <span>Link Expired</span>
                                    </button>
                                    @else
                                    <form method="POST" action="{{ route('finance.billing-layanan.generate-midtrans.post') }}">
                                        @csrf
                                        <input type="hidden" name="kode_billing" value="{{ $inv->kode_billing_layanan }}">
                                        <button type="submit"
                                                title="Generate Link Pembayaran Midtrans"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[9px] font-medium hover:bg-amber-500/20 transition cursor-pointer">
                                            <span>+ Buat Link</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                @elseif($inv->payment_type == 2)
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-semibold">
                                        <svg class="w-3 h-3 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5M4.5 21V10.5" />
                                        </svg>
                                        <span>Manual Transfer</span>
                                    </span>
                                </div>
                                @else
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] font-semibold">
                                        <svg class="w-3 h-3 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        <span>Cash To Collector</span>
                                    </span>
                                </div>
                                @endif

                                <!-- Notification Badges -->
                                <div class="flex items-center gap-1 text-[10px]">
                                    <span class="px-1.5 py-0.2 rounded {{ ($inv->notif_wa ?? 0) > 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-500' }}">
                                        WA: {{ ($inv->notif_wa ?? 0) > 0 ? 'Sent' : 'UnSend' }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded {{ ($inv->notif_mail ?? 0) > 0 ? 'bg-blue-500/10 text-blue-400' : 'bg-slate-800 text-slate-500' }}">
                                        Mail: {{ ($inv->notif_mail ?? 0) > 0 ? 'Sent' : 'UnSend' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- 7. Action Buttons (2x2 Stacked Grid: Approve, Change Pay, Detail, Hapus) -->
                        <td class="py-3 px-3 align-middle text-center">
                            <div class="grid grid-cols-2 gap-1.5 w-[210px] mx-auto">
                                <!-- 1. Approve / Konfirmasi Bayar (Top Left) -->
                                @if($inv->status_bill_lay != '15')
                                <button type="button"
                                        @click="openPayModalFromEl($el)"
                                        data-kode="{{ $inv->kode_billing_layanan }}"
                                        data-internet="{{ $inv->nomor_internet }}"
                                        data-nama="{{ $inv->nama_pelanggan }}"
                                        data-nominal="{{ (float)($inv->total_layanan ?? $inv->harga_bandwith ?? 0) }}"
                                        title="Approve Pembayaran Lunas"
                                        class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/30 text-[11px] font-bold transition shadow-sm cursor-pointer whitespace-nowrap">
                                    <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>Approve</span>
                                </button>
                                @else
                                <span class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400/70 border border-emerald-500/20 text-[11px] font-semibold opacity-75 select-none">
                                    <svg class="w-3 h-3 flex-shrink-0 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span>Lunas</span>
                                </span>
                                @endif

                                <!-- 2. Change Payment Method (Top Right) -->
                                <button type="button"
                                        @click="openChangePayModalFromEl($el)"
                                        data-kode="{{ $inv->kode_billing_layanan }}"
                                        data-nama="{{ $inv->nama_pelanggan }}"
                                        data-payment-type="{{ $inv->payment_type ?? 1 }}"
                                        title="Ubah Metode Pembayaran"
                                        class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-blue-500/15 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/30 text-[11px] font-semibold transition shadow-sm cursor-pointer whitespace-nowrap">
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
                                        class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 text-[11px] font-semibold transition shadow-sm cursor-pointer whitespace-nowrap">
                                    <svg class="w-3 h-3 flex-shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
                                            class="w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 text-[11px] font-semibold transition shadow-sm cursor-pointer whitespace-nowrap">
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
                        <td colspan="7" class="py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="text-sm font-semibold text-slate-400">Tidak ada data invoice ditemukan</p>
                            <p class="text-xs text-slate-500 mt-1">Coba sesuaikan filter bulan/tahun atau gunakan tombol Generate Invoice Massal.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($invoices->hasPages())
        <div class="p-4 border-t border-slate-800 bg-slate-950/60">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>

    <!-- ======================================================================= -->
    <!-- MODALS SECTION                                                          -->
    <!-- ======================================================================= -->

    <!-- 1. MODAL GENERATE INVOICE BULANAN -->
    <div x-show="generateModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="generateModalOpen = false"
             class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form method="POST" action="{{ route('finance.billing-layanan.generate') }}">
                @csrf
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Generate Invoice Massal</h3>
                                <p class="text-xs text-slate-400">Terbitkan tagihan berkala untuk seluruh pelanggan aktif</p>
                            </div>
                        </div>
                        <button type="button" @click="generateModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3.5 text-xs text-blue-300">
                        Sistem akan men-generate invoice bulanan baru untuk seluruh pelanggan berstatus aktif pada periode terpilih. Tagihan yang sudah ada sebelumnya tidak akan diduplikasi.
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Bulan Target</label>
                            <select name="bulan" x-model="generateBulan" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                                @foreach($bulanList as $k => $b)
                                <option value="{{ $k }}">{{ $k }} - {{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Tahun Target</label>
                            <input type="number" name="tahun" x-model="generateTahun" min="2020" max="2035" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <div class="bg-slate-950/80 px-6 py-4 border-t border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="generateModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-semibold shadow-lg shadow-blue-500/25">
                        Proses Generate Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. MODAL DETAIL BREAKDOWN INVOICE -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="detailModalOpen = false"
             class="relative w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto max-h-[90vh] flex flex-col">
            
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Detail Invoice & Rincian Item</span>
                        <h3 class="text-lg font-bold text-white" x-text="detailData.invoice?.kode_billing_layanan || 'Loading...'"></h3>
                    </div>
                    <button @click="detailModalOpen = false" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-show="detailLoading" class="py-12 text-center text-slate-400">
                    <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs mt-2">Memuat rincian invoice...</p>
                </div>

                <div x-show="!detailLoading" class="space-y-4">
                    <!-- Customer Summary Card -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Nama Pelanggan:</span>
                            <span class="font-bold text-white" x-text="detailData.invoice?.nama_pelanggan || '-'"></span>
                            <span class="text-slate-400 block text-[10px] mt-0.5" x-text="'No Internet: #' + (detailData.invoice?.nomor_internet || '')"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Paket & Periode:</span>
                            <span class="font-bold text-blue-400" x-text="(detailData.invoice?.nama_kategori_bandwith || '') + ' ' + (detailData.invoice?.nominal_bandwith || '') + ' Mbps'"></span>
                            <span class="text-slate-400 block text-[10px] mt-0.5" x-text="'Periode: ' + (detailData.invoice?.periode_tagihan || '-')"></span>
                        </div>
                    </div>

                    <!-- Item Breakdown Table -->
                    <div>
                        <span class="text-xs font-bold text-white block mb-2">Komponen Tagihan:</span>
                        <div class="rounded-xl border border-slate-800 overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-950 text-slate-400 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Komponen / Item</th>
                                        <th class="py-2.5 px-3 text-center">Qty</th>
                                        <th class="py-2.5 px-3 text-right">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <template x-for="item in detailData.items" :key="item.kode_billing_lay_detail">
                                        <tr>
                                            <td class="py-2.5 px-3 text-slate-200" x-text="item.komponen"></td>
                                            <td class="py-2.5 px-3 text-center text-slate-400" x-text="item.qty"></td>
                                            <td class="py-2.5 px-3 text-right font-semibold text-white" x-text="formatRupiah(item.biaya)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-slate-950/80 text-xs border-t border-slate-800">
                                    <tr>
                                        <td colspan="2" class="py-2 px-3 text-right font-bold text-slate-300">Total Tagihan Pokok:</td>
                                        <td class="py-2 px-3 text-right font-bold text-white" x-text="formatRupiah(detailData.invoice?.total_layanan || detailData.invoice?.harga_bandwith)"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Transaction Logs -->
                    <div>
                        <span class="text-xs font-bold text-white block mb-1.5">Riwayat & Log Transaksi:</span>
                        <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                            <template x-for="log in detailData.logs" :key="log.kode_billing_lay_log">
                                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80 text-[11px] flex items-center justify-between">
                                    <div>
                                        <span class="text-slate-300 font-medium" x-text="log.note_billing_lay"></span>
                                        <span class="text-slate-500 block text-[10px]" x-text="'Oleh: ' + (log.user_create || 'System')"></span>
                                    </div>
                                    <span class="text-slate-500 text-[10px]" x-text="log.date_create"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/80 px-6 py-3.5 border-t border-slate-800 flex justify-end">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- 3. MODAL KONFIRMASI BAYAR MANUAL -->
    <div x-show="payModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="payModalOpen = false"
             class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.konfirmasi-bayar.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="payKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Konfirmasi Pembayaran Manual</h3>
                                <p class="text-xs text-slate-400">Verifikasi pelunasan invoice pelanggan</p>
                            </div>
                        </div>
                        <button type="button" @click="payModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-400">No Invoice:</span>
                            <span class="font-bold text-white font-mono" x-text="payKodeBilling"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Pelanggan:</span>
                            <span class="font-semibold text-blue-400" x-text="payNamaPelanggan"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nominal Diterima (Rp)</label>
                        <input type="number" name="nominal_bayar" x-model="payNominal" required min="1" class="w-full bg-slate-950 border border-slate-800 text-white font-bold text-sm rounded-xl px-3 py-2.5 focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tipe Pembayaran</label>
                            <select name="metode_bayar" x-model="payMetode" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-emerald-500">
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Tunai / Kasir</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Rekening / Kasir</label>
                            <select name="bank_tujuan" x-model="payBank" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-emerald-500">
                                <option value="BCA">BCA (PT Medianet)</option>
                                <option value="Mandiri">Bank Mandiri</option>
                                <option value="BRI">Bank BRI</option>
                                <option value="BNI">Bank BNI</option>
                                <option value="Kasir Kantor">Kasir Kantor (Cash)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan / Ref Pembayaran</label>
                        <input type="text" name="catatan" x-model="payCatatan" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-emerald-500">
                    </div>
                </div>

                <div class="bg-slate-950/80 px-6 py-3.5 border-t border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="payModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-500/25">
                        Konfirmasi Lunas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. MODAL ADJUSTMENT (PENYESUAIAN DISKON / DENDA) -->
    <div x-show="adjustModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="adjustModalOpen = false"
             class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.adjust.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="adjustKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Penyesuaian (Adjustment)</h3>
                                <p class="text-xs text-slate-400">Atur potongan diskon atau denda tagihan</p>
                            </div>
                        </div>
                        <button type="button" @click="adjustModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Invoice:</span>
                            <span class="font-bold text-white font-mono" x-text="adjustKodeBilling"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Subtotal Pokok:</span>
                            <span class="font-bold text-white" x-text="formatRupiah(adjustSubtotal)"></span>
                        </div>
                        <div class="flex justify-between border-t border-slate-800 pt-1.5">
                            <span class="text-amber-400 font-semibold">Total Tagihan Baru:</span>
                            <span class="font-bold text-emerald-400 text-sm" x-text="formatRupiah(adjustTotalBaru)"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Potongan / Diskon Kompensasi (Rp)</label>
                        <input type="number" name="potongan" x-model="adjustPotongan" min="0" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Keterangan Diskon</label>
                        <input type="text" name="desc_potongan" x-model="adjustDescPotongan" placeholder="Kompensasi kendala jaringan..." class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Denda Keterlambatan (Rp)</label>
                        <input type="number" name="denda" x-model="adjustDenda" min="0" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan Adjustment</label>
                        <input type="text" name="note_adjustment" x-model="adjustNote" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:border-amber-500">
                    </div>
                </div>

                <div class="bg-slate-950/80 px-6 py-3.5 border-t border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="adjustModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold shadow-lg shadow-amber-500/25">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. MODAL ROLLBACK TAGIHAN -->
    <div x-show="rollbackModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="rollbackModalOpen = false"
             class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.rollback.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="rollbackKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Rollback Status Tagihan</h3>
                                <p class="text-xs text-slate-400">Kembalikan invoice ke status Draft / Unpublish</p>
                            </div>
                        </div>
                        <button type="button" @click="rollbackModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-300">
                        Apakah Anda yakin ingin me-rollback status invoice <strong class="text-white font-mono" x-text="rollbackKodeBilling"></strong> milik <strong class="text-white" x-text="rollbackNamaPelanggan"></strong>? Riwayat pembayaran yang sudah diverifikasi akan dikosongkan kembali ke status draft.
                    </div>
                </div>

                <div class="bg-slate-950/80 px-6 py-3.5 border-t border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="rollbackModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-lg shadow-rose-500/25">
                        Ya, Rollback Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. MODAL CHANGE PAYMENT METHOD (MATCHING EXACT SCREENSHOT DESIGN) -->
    <div x-show="changePayModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="changePayModalOpen = false"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <form action="{{ route('finance.billing-layanan.change-payment-method.post') }}" method="POST">
                @csrf
                <input type="hidden" name="kode_billing" :value="changePayKodeBilling">
                <div class="p-6 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <!-- Title Label matching screenshot -->
                        <div class="text-sm font-semibold text-slate-500 dark:text-slate-400">
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
                        <span class="text-slate-500">Invoice: <strong class="text-slate-800 dark:text-white font-mono" x-text="changePayKodeBilling"></strong></span>
                        <span class="text-slate-500">Pelanggan: <strong class="text-blue-500" x-text="changePayNamaPelanggan"></strong></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. MODAL LINK PEMBAYARAN MIDTRANS SNAP -->
    <div x-show="midtransModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="midtransModalOpen = false"
             class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto">
            
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Link Pembayaran Midtrans Snap</h3>
                            <p class="text-xs text-slate-400">Tautan pembayaran online instan pelanggan</p>
                        </div>
                    </div>
                    <button type="button" @click="midtransModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Invoice:</span>
                        <span class="font-bold text-white font-mono" x-text="midtransKode"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Pelanggan:</span>
                        <span class="font-semibold text-blue-400" x-text="midtransNama"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-1.5">
                        <span class="text-slate-400">Total Tagihan:</span>
                        <span class="font-bold text-emerald-400 text-sm" x-text="formatRupiah(midtransNominal)"></span>
                    </div>
                </div>

                <!-- Status Masa Berlaku Link -->
                <div class="flex items-center justify-between p-3 rounded-xl border text-xs"
                     :class="midtransIsExpired ? 'bg-rose-500/10 border-rose-500/20 text-rose-300' : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-300'">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :class="midtransIsExpired ? 'bg-rose-500' : 'bg-emerald-400 animate-pulse'"></span>
                        <span class="font-semibold" x-text="midtransIsExpired ? 'Status: Link Kadaluarsa (Expired)' : 'Status: Link Aktif'"></span>
                    </div>
                    <span class="text-[11px]" x-text="'Berlaku s/d: ' + (midtransExpiry || '-')"></span>
                </div>

                <!-- URL Copy Box -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">URL Pembayaran Online</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly :value="midtransUrl" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2.5 font-mono focus:border-indigo-500 select-all">
                        <button type="button"
                                @click="copyMidtransLink()"
                                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shrink-0 shadow-lg shadow-indigo-500/20 flex items-center gap-1.5 transition">
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
                       class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold text-center flex items-center justify-center gap-1.5 transition">
                        <svg class="w-4 h-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        <span>Buka Link Midtrans</span>
                    </a>

                    <a :href="midtransWaUrl" target="_blank"
                       class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold text-center flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-500/20 transition">
                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-1.074-.865 5.25 5.25 0 0 0 1.4-2.84C4.12 15.842 3 14.034 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>
                        <span>Kirim via WhatsApp</span>
                    </a>
                </div>

                <!-- Renew Button if Expired or Re-request -->
                <div class="border-t border-slate-800 pt-3">
                    <form method="POST" action="{{ route('finance.billing-layanan.renew-midtrans.post') }}" onsubmit="return confirm('Buat Order ID dan perbarui link Midtrans baru untuk invoice ini?')">
                        @csrf
                        <input type="hidden" name="kode_billing" :value="midtransKode">
                        <button type="submit"
                                class="w-full px-4 py-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Renew / Buat Ulang Link Pembayaran</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-slate-950/80 px-6 py-3 border-t border-slate-800 flex justify-end">
                <button type="button" @click="midtransModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
