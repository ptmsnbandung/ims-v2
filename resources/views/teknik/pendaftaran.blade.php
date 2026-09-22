@extends('layouts.app')

@section('title', 'Pendaftaran Pelanggan Baru')
@section('page_title', 'Pendaftaran')

@section('content')
<div class="space-y-6"
     x-data="pendaftaranWorkflowComponent()">
    
    <!-- Top Header: Breadcrumbs & Registrasi Baru Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs">
        <!-- Breadcrumb Navigation -->
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">IMS</a>
                <svg class="w-3.5 h-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <span class="text-slate-800 font-semibold">Pendaftaran Pelanggan</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Antrean Pendaftaran Baru</h2>
        </div>

        <!-- Button + Registrasi Baru (Buka Modal Form) -->
        <div>
            <button type="button" 
                    @click="openNewModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition duration-150 transform hover:-translate-y-0.5 cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                </svg>
                <span>Registrasi Baru</span>
            </button>
        </div>
    </div>

    {{-- Banner Notifikasi Registrasi Baru dengan Tombol Cetak Form Berlangganan Langsung --}}
    @if(session('nomor_internet_baru'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-700 shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>Registrasi Baru Berhasil Disimpan!</span>
                        <span class="px-2 py-0.5 rounded text-[11px] bg-emerald-100 text-emerald-800 font-mono font-bold">No. Internet: {{ session('nomor_internet_baru') }}</span>
                    </h4>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Dokumen Form Berlangganan untuk <strong class="text-slate-900">{{ session('nama_pelanggan_baru') }}</strong> telah terbuat secara otomatis dan siap dicetak.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <a href="{{ route('teknik.dokumen.langganan', session('nomor_internet_baru')) }}?download=pdf" target="_blank" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Unduh PDF</span>
                </a>
                <a href="{{ route('teknik.dokumen.langganan', session('nomor_internet_baru')) }}?download=word" target="_blank" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span>Unduh Word (.doc)</span>
                </a>
                <a href="{{ route('teknik.dokumen.langganan', session('nomor_internet_baru')) }}" target="_blank" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    <span>Cetak Form</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Filter Card Container -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <form method="GET" action="{{ route('teknik.pendaftaran') }}" id="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5 items-end">
                
                <!-- 1. Dropdown Semua Layanan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Layanan</label>
                    <select name="layanan" 
                            class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-3 py-2 text-xs text-slate-800 font-medium outline-none transition shadow-xs">
                        <option value="">SEMUA LAYANAN</option>
                        @foreach($layananList as $layanan)
                            <option value="{{ $layanan->kode_kategori_bandwith }}" {{ ($filters['layanan'] ?? '') == $layanan->kode_kategori_bandwith ? 'selected' : '' }}>
                                {{ $layanan->nama_kategori_bandwith }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Input Nama -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama</label>
                    <input type="text" 
                           name="nama" 
                           value="{{ $filters['nama'] ?? '' }}" 
                           placeholder="Cari Nama..." 
                           class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-3 py-2 text-xs text-slate-800 font-medium outline-none transition placeholder:text-slate-400 shadow-xs">
                </div>

                <!-- 3. Input Alamat -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat</label>
                    <input type="text" 
                           name="alamat" 
                           value="{{ $filters['alamat'] ?? '' }}" 
                           placeholder="Cari Alamat..." 
                           class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-3 py-2 text-xs text-slate-800 font-medium outline-none transition placeholder:text-slate-400 shadow-xs">
                </div>

                <!-- 4. Dropdown Semua Status -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" 
                            class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-3 py-2 text-xs text-slate-800 font-medium outline-none transition shadow-xs">
                        <option value="">SEMUA STATUS (PENDAFTARAN)</option>
                        @foreach($statusList as $status)
                            <option value="{{ $status->status_reg }}" {{ ($filters['status'] ?? '') == $status->status_reg ? 'selected' : '' }}>
                                {{ $status->desc_registrasi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Dropdown Semua Wilayah -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Wilayah</label>
                    <select name="wilayah" 
                            class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-3 py-2 text-xs text-slate-800 font-medium outline-none transition shadow-xs">
                        <option value="">SEMUA WILAYAH</option>
                        @foreach($wilayahList as $wilayah)
                            <option value="{{ $wilayah->name_w }}" {{ ($filters['wilayah'] ?? '') == $wilayah->name_w ? 'selected' : '' }}>
                                {{ $wilayah->name_w }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Action Buttons: Reset & Export Excel -->
                <div class="flex items-center gap-2">
                    <a href="{{ route('teknik.pendaftaran') }}" 
                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 transition duration-150">
                        <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset</span>
                    </a>
                    <!-- Tombol Export Excel -->
                    <a href="{{ route('teknik.pendaftaran.export', request()->query()) }}" 
                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition duration-150 shadow-xs cursor-pointer"
                       title="Download data pendaftaran baru ke format Excel">
                        <svg class="w-4 h-4 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <span>Export Excel</span>
                    </a>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-3">
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span>Show</span>
                    <select name="per_page" 
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs text-slate-800 font-medium focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="10" {{ ($filters['per_page'] ?? '10') == '10' ? 'selected' : '' }}>10</option>
                        <option value="25" {{ ($filters['per_page'] ?? '') == '25' ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($filters['per_page'] ?? '') == '50' ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['per_page'] ?? '') == '100' ? 'selected' : '' }}>100</option>
                    </select>
                    <span>entries</span>
                </div>

                <div>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-xs text-blue-700 font-bold border border-blue-200 transition cursor-pointer">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table Card Container -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-900 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Pelanggan</th>
                        <th class="py-3.5 px-4">Group Layanan</th>
                        <th class="py-3.5 px-4">Lokasi Pemasangan</th>
                        <th class="py-3.5 px-4 min-w-[200px]">Status</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Tanggal SO</th>
                        <th class="py-3.5 px-4 text-center min-w-[120px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($registrasi as $item)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            
                            <!-- 1. Pelanggan -->
                            <td class="py-4 px-4 align-top">
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-bold font-mono text-blue-600 hover:text-blue-800 underline tracking-wide text-xs inline-block"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>
                                <div class="mt-1 font-bold text-slate-900 uppercase text-xs">
                                    <span>{{ $item->nama_pelanggan }}</span>
                                    <span class="text-slate-500 font-normal">
                                        ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 font-medium mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?? ($item->alias_nama_kategori ?? 'LAYANAN') }} 
                                    @if($item->nominal_bandwith)
                                        <span class="text-slate-900 font-bold">{{ $item->nominal_bandwith }} Mbps</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 2. Group Layanan -->
                            <td class="py-4 px-4 align-top font-semibold text-slate-700 uppercase tracking-wider text-[11px]">
                                {{ $item->group_layanan ?: 'MEDIANET' }}
                            </td>

                            <!-- 3. Lokasi Pemasangan -->
                            <td class="py-4 px-4 align-top max-w-xs">
                                <span class="font-bold text-slate-900 uppercase text-[11px] tracking-wide block mb-1">
                                    {{ $item->jenis_bangunan ?: 'RUMAH-PRIBADI' }}
                                </span>
                                <p class="text-[11px] text-slate-600 leading-relaxed uppercase">
                                    {{ $item->alamat_p ?: ($item->alamat_pasang ?: '-') }}
                                </p>
                            </td>

                            <!-- 4. Status & Tombol Billing -->
                            <td class="py-4 px-4 align-top space-y-2">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold border shadow-xs
                                        @if(in_array($item->status_reg, ['17', '17.1']))
                                            bg-sky-50 text-sky-800 border-sky-300
                                        @elseif($item->status_reg == '18')
                                            bg-indigo-50 text-indigo-800 border-indigo-300
                                        @else
                                            bg-amber-50 text-amber-900 border-amber-300
                                        @endif">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <span>{{ $item->desc_registrasi ?: 'Pendaftaran #' . $item->status_reg }}</span>
                                    </span>
                                </div>

                                @if($item->instalasi_date_start)
                                    <div class="text-[10px] text-slate-600 font-medium">
                                        Jadwal: <strong class="text-slate-800">{{ \Carbon\Carbon::parse($item->instalasi_date_start)->translatedFormat('d M Y') }}</strong>
                                        {{ $item->instalasi_time ?: '' }}
                                    </div>
                                @endif

                                @if($item->date_update)
                                    <div class="text-[10px] text-slate-500">
                                        Updated <span class="underline text-slate-700 font-medium">{{ \Carbon\Carbon::parse($item->date_update)->translatedFormat('d M Y H:i') }} WIB</span>
                                    </div>
                                @endif

                                <!-- Tombol Billing Popup -->
                                <div>
                                    <button type="button" 
                                            @click="openBillingModal('{{ $item->nomor_internet }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold transition shadow-xs cursor-pointer"
                                            title="Lihat Rincian Billing">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                        </svg>
                                        <span>Billing</span>
                                    </button>
                                </div>
                            </td>

                            <!-- 5. Tanggal SO -->
                            <td class="py-4 px-4 align-top text-xs space-y-1">
                                <div class="text-slate-900 font-bold text-[11px]">
                                    {{ \Carbon\Carbon::parse($item->date_create)->translatedFormat('d M Y H:i') }} WIB
                                </div>
                                <div class="text-[11px] text-slate-600 uppercase font-semibold">
                                    {{ $item->user_create ?: 'DRAFFTER' }}
                                </div>
                                <div class="text-[10px] text-blue-700 font-mono font-bold">
                                    SALES: {{ $item->nama_sales ?: '-' }}
                                </div>
                            </td>

                            <!-- 6. Aksi Workflow -->
                            <td class="py-4 px-4 align-top text-xs">
                                <div class="flex flex-col gap-1.5 min-w-[130px]">
                                    
                                    {{-- Tahap 1: Pendaftaran Baru / Menunggu Verifikasi (#11, #11.1, #12) --}}
                                    @if(in_array($item->status_reg, ['11', '11.1', '12']))
                                        <button type="button" 
                                                @click="openScheduleSurveyModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold transition shadow-xs cursor-pointer"
                                                title="Buat Jadwal Survey Lapangan">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                            <span>Schedule Survey</span>
                                        </button>
                                    @endif

                                    {{-- Tahap 2: Jadwal Survey Terbit (#13, #13.1) -> Siap Input Report Hasil Survey --}}
                                    @if(in_array($item->status_reg, ['13', '13.1']))
                                        <button type="button" 
                                                @click="openReportSurveyModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition shadow-xs cursor-pointer"
                                                title="Input Laporan Hasil Survey Lokasi">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            <span>Report Survey</span>
                                        </button>
                                        <a href="{{ route('teknik.dokumen.survey', $item->nomor_internet) }}" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-amber-700 hover:text-amber-800 text-[11px] transition font-bold"
                                            title="Cetak Surat Tugas Survey untuk Tim Teknisi">
                                            <svg class="w-3.5 h-3.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Surat Tugas</span>
                                        </a>
                                        <button type="button" 
                                                @click="openScheduleSurveyModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 text-sky-700 hover:text-sky-800 text-[11px] transition font-semibold">
                                            <svg class="w-3.5 h-3.5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                            <span>Ubah Survey</span>
                                        </button>
                                    @endif

                                    {{-- Tahap 3: Selesai Survey (#16) -> Siap Dijadwalkan Instalasi --}}
                                    @if($item->status_reg == '16')
                                        <button type="button" 
                                                @click="openScheduleInstalasiModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold transition shadow-xs cursor-pointer"
                                                title="Buat Jadwal Instalasi & Pemasangan">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 9.75v1.409l4.26 4.26" />
                                            </svg>
                                            <span>Schedule Instalasi</span>
                                        </button>
                                        <a href="{{ route('teknik.dokumen.survey', $item->nomor_internet) }}" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-amber-700 hover:text-amber-800 text-[11px] transition font-bold"
                                            title="Cetak Surat Tugas Survey">
                                            <svg class="w-3.5 h-3.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Surat Tugas</span>
                                        </a>
                                        <button type="button" 
                                                @click="openReportSurveyModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 text-emerald-700 hover:text-emerald-800 text-[11px] transition font-semibold">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                            </svg>
                                            <span>Edit Hasil Survey</span>
                                        </button>
                                    @endif

                                    {{-- Tahap 4: Jadwal Instalasi Terbit (#17, #17.1) --}}
                                    @if(in_array($item->status_reg, ['17', '17.1']))
                                        <button type="button" 
                                                @click="openReportInstalasiModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-bold transition shadow-xs cursor-pointer"
                                                title="Input Laporan Hasil Instalasi & Barang">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            <span>Report Instalasi</span>
                                        </button>
                                        <a href="{{ route('teknik.dokumen.instalasi', $item->nomor_internet) }}" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-teal-700 hover:text-teal-800 text-[11px] transition font-bold"
                                            title="Cetak Surat Tugas Instalasi untuk Tim Teknisi">
                                            <svg class="w-3.5 h-3.5 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Surat Tugas</span>
                                        </a>
                                        <button type="button" 
                                                @click="openScheduleInstalasiModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 text-sky-700 hover:text-sky-800 text-[11px] transition font-semibold">
                                            <svg class="w-3.5 h-3.5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                            <span>Ubah Instalasi</span>
                                        </button>
                                    @endif

                                    {{-- Tahap 5: Selesai Instalasi (#18) -> Kirim Request Aktivasi ke NOC --}}
                                    @if($item->status_reg == '18')
                                        <button type="button" 
                                                @click="openRequestAktivasiModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-[11px] font-bold transition shadow-xs cursor-pointer"
                                                title="Kirim Permintaan Aktivasi Layanan ke Tim NOC">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a5 5 0 0 1-5.84 7.38v-4.8m5.84-2.58a5 5 0 0 0-7.38-5.84l3.4 3.4M12 2.25a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM6.166 5.106a.75.75 0 0 1 1.06 0l1.592 1.592a.75.75 0 0 1-1.06 1.06L6.166 6.166a.75.75 0 0 1 0-1.06Zm11.668 0a.75.75 0 0 1 0 1.06l-1.592 1.592a.75.75 0 1 1-1.06-1.06l1.592-1.592a.75.75 0 0 1 1.06 0Z" />
                                            </svg>
                                            <span>Request Aktivasi NOC</span>
                                        </button>
                                        <a href="{{ route('teknik.dokumen.instalasi', $item->nomor_internet) }}" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-teal-700 hover:text-teal-800 text-[11px] transition font-bold"
                                            title="Cetak Surat Tugas Instalasi">
                                            <svg class="w-3.5 h-3.5 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Surat Tugas</span>
                                        </a>
                                        <button type="button" 
                                                @click="openReportInstalasiModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 text-teal-700 hover:text-teal-800 text-[11px] transition font-semibold">
                                            <svg class="w-3.5 h-3.5 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                            </svg>
                                            <span>Edit Hasil Instalasi</span>
                                        </button>
                                    @endif

                                    {{-- Tahap 6: Jadwal Aktivasi Terbit (#19, #19.1) --}}
                                    @if(in_array($item->status_reg, ['19', '19.1']))
                                        <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-purple-50 border border-purple-200 text-purple-700 text-[10px] font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                                            <span>Menunggu NOC</span>
                                        </div>
                                        <button type="button" 
                                                @click="openReportInstalasiModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 text-teal-700 hover:text-teal-800 text-[11px] transition font-semibold">
                                            <svg class="w-3.5 h-3.5 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <span>Lihat Instalasi</span>
                                        </button>
                                    @endif

                                    {{-- Tombol Batal Pasang (jika belum selesai/batal) --}}
                                    @if(!in_array($item->status_reg, ['14', '15', '20']))
                                        <button type="button" 
                                                @click="openBatalModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}')"
                                                class="inline-flex items-center gap-1.5 text-rose-600 hover:text-rose-700 text-[11px] transition font-semibold cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            <span>Batal Pasang</span>
                                        </button>
                                    @endif

                                    {{-- Tombol Dokumen Form Berlangganan & Download --}}
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('teknik.dokumen.langganan', $item->nomor_internet) }}?download=pdf" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 text-[11px] transition font-bold"
                                           title="Download PDF Form Berlangganan">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            <span>Unduh PDF</span>
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <a href="{{ route('teknik.dokumen.langganan', $item->nomor_internet) }}" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-sky-700 hover:text-sky-800 text-[11px] transition font-bold"
                                            title="Buka & Cetak Form Berlangganan Pelanggan Ini">
                                            <svg class="w-3.5 h-3.5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>Form</span>
                                        </a>
                                    </div>

                                    {{-- Tombol Edit Pendaftaran Pelanggan --}}
                                    <button type="button" 
                                            @click="openEditModal('{{ $item->nomor_internet }}')"
                                            class="inline-flex items-center gap-1.5 text-blue-700 hover:text-blue-800 text-[11px] transition font-bold cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        <span>Edit Data</span>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-10 h-10 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                    <span>Tidak ada antrean pendaftaran baru yang sedang berlangsung.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-600">
                Showing <span class="text-slate-900 font-bold">{{ $registrasi->firstItem() ?? 0 }}</span> 
                to <span class="text-slate-900 font-bold">{{ $registrasi->lastItem() ?? 0 }}</span> 
                of <span class="text-slate-900 font-bold">{{ $registrasi->total() }}</span> entries
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ $registrasi->url(1) }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $registrasi->onFirstPage() ? 'border-slate-200 text-slate-400 pointer-events-none' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">First</a>
                <a href="{{ $registrasi->previousPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $registrasi->onFirstPage() ? 'border-slate-200 text-slate-400 pointer-events-none' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">Previous</a>
                <span class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold shadow-xs">{{ $registrasi->currentPage() }}</span>
                <a href="{{ $registrasi->nextPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $registrasi->hasMorePages() ? 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900' : 'border-slate-200 text-slate-400 pointer-events-none' }}">Next</a>
                <a href="{{ $registrasi->url($registrasi->lastPage()) }}" class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition {{ $registrasi->hasMorePages() ? 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900' : 'border-slate-200 text-slate-400 pointer-events-none' }}">Last</a>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL POPUP BILLING DETAIL (EXACT MATCH REFERENCE IMAGE)            -->
    <!-- =================================================================== -->
    <div x-show="billingModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="billing-modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="billingModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-2xl">
                
                <!-- Loading Indicator -->
                <div x-show="billingLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
                    <h3 class="text-sm font-bold text-slate-800 tracking-wide" id="billing-modal-title">
                        Billing Detail
                    </h3>
                    
                    <!-- Close button -->
                    <button type="button" 
                            @click="billingModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (Matching Layout) -->
                <div class="p-6 space-y-6">
                    
                    <!-- Top Customer Info & Billing Totals -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <!-- Left Info -->
                        <div class="space-y-1 text-xs">
                            <div class="text-sm font-semibold text-slate-800">
                                Pelanggan : <span class="font-bold uppercase text-slate-900" x-text="billingData.nama_pelanggan || '-'"></span>
                            </div>
                            <div class="text-slate-500">
                                Status : <span class="font-medium text-slate-700" x-text="billingData.status || 'Draft Billing'"></span>
                            </div>
                            <div class="text-slate-500">
                                Method : <span class="font-medium text-slate-700" x-text="billingData.method || 'Midtrans'"></span>
                            </div>
                        </div>

                        <!-- Right Summary Totals -->
                        <div class="text-left sm:text-right space-y-0.5">
                            <div class="text-xs text-slate-500 italic">
                                Sub Total : <span class="font-bold text-slate-700" x-text="'Rp ' + Number(billingData.subtotal || 0).toLocaleString('id-ID') + ',00'"></span>
                            </div>
                            <div class="text-[11px] text-slate-400 italic">
                                Tax : <span x-text="'Rp ' + Number(billingData.tax || 0).toLocaleString('id-ID') + ',00(Include)'"></span>
                            </div>
                            <div class="text-base font-extrabold text-slate-800 italic pt-1">
                                Total : <span class="text-blue-600" x-text="'Rp ' + Number(billingData.total || 0).toLocaleString('id-ID') + ',00'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Components Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200">
                                    <th class="py-2.5 px-4">
                                        <div class="flex items-center gap-1">
                                            <span>Component</span>
                                            <svg class="w-3 h-3 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="py-2.5 px-4 w-20 border-l border-slate-200">Qty</th>
                                    <th class="py-2.5 px-4 w-40 border-l border-slate-200">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-slate-700">
                                <template x-for="item in billingData.items" :key="item.komponen">
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3 px-4 font-medium uppercase text-[11px]" x-text="item.komponen"></td>
                                        <td class="py-3 px-4 border-l border-slate-200" x-text="item.qty"></td>
                                        <td class="py-3 px-4 border-l border-slate-200 font-mono text-[11px]" x-text="'Rp ' + Number(item.biaya).toLocaleString('id-ID') + ',00'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                    <button type="button" 
                            @click="billingModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL FORM BATAL PEMASANGAN                                         -->
    <!-- =================================================================== -->
    <div x-show="batalModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="batal-modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
             @click="batalModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 tracking-wide">
                            Batal Pemasangan An/ <span class="text-blue-600 uppercase font-mono" x-text="batalNamaPelanggan"></span>
                        </h3>
                    </div>
                    
                    <!-- Close button -->
                    <button type="button" 
                            @click="batalModalOpen = false" 
                            class="text-slate-400 hover:text-slate-700 text-2xl font-bold p-1 leading-none transition cursor-pointer">
                        &times;
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form action="{{ route('teknik.pendaftaran.batal-pasang') }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="nomor_internet" :value="batalNomorInternet">
                    <input type="hidden" name="nama_pelanggan" :value="batalNamaPelanggan">

                    <!-- Inner Card -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                        
                        <!-- Radio Options -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 text-xs text-slate-800 cursor-pointer font-bold p-2.5 rounded-xl bg-white border border-slate-300 hover:border-blue-500 shadow-xs">
                                <input type="radio" 
                                       name="kategori_batal" 
                                       value="14" 
                                       x-model="batalKategori"
                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                <span class="text-[11px] uppercase tracking-wide">TIDAK TERJANGKAU</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs text-slate-800 cursor-pointer font-bold p-2.5 rounded-xl bg-white border border-slate-300 hover:border-blue-500 shadow-xs">
                                <input type="radio" 
                                       name="kategori_batal" 
                                       value="15" 
                                       x-model="batalKategori"
                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                <span class="text-[11px] uppercase tracking-wide">PERMINTAAN USER</span>
                            </label>
                        </div>

                        <!-- Textarea Alasan Batal Pasang -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                Alasan Batal Pasang <span class="text-rose-600 font-bold">*</span>
                            </label>
                            <textarea name="alasan_batal" 
                                      x-model="batalAlasan"
                                      required
                                      rows="3" 
                                      placeholder="Masukkan alasan pembatalan pemasangan secara rinci..." 
                                      class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-medium outline-none transition placeholder:text-slate-400 shadow-xs"></textarea>
                        </div>
                    </div>

                    <!-- Modal Footer Action Buttons -->
                    <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-end gap-2.5">
                        <button type="button" 
                                @click="batalModalOpen = false"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Batal</span>
                        </button>

                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md shadow-blue-500/20 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span>Simpan Pembatalan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL FORM REGISTRATION / EDIT (2-STEP WIZARD)                      -->
    <!-- =================================================================== -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="modalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl">
                
                <!-- Loading State Indicator -->
                <div x-show="isLoadingEdit" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex flex-col items-center justify-center gap-3">
                    <svg class="w-8 h-8 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs text-slate-700 font-semibold">Memuat Data Pendaftaran...</span>
                </div>

                <!-- Modal Header & 2-Step Indicator -->
                <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/80">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 tracking-wide" id="modal-title" x-text="isEditMode ? 'Edit Pendaftaran: ' + editNomorInternet : 'Form Registration'"></h3>
                                <p class="text-xs text-slate-500" x-text="isEditMode ? 'Perbarui data pendaftaran dan instalasi pelanggan' : 'Pendaftaran pelanggan baru Internet System Management'"></p>
                            </div>
                        </div>
                        
                        <!-- Close button -->
                        <button type="button" 
                                @click="modalOpen = false"
                                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- 2-Step Progress Indicator -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <!-- Step 1 Tab -->
                        <div class="flex items-center gap-3 p-2.5 rounded-xl border transition duration-150"
                             :class="currentStep === 1 ? 'bg-blue-50/80 border-blue-300 text-blue-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-400'">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs"
                                 :class="currentStep === 1 ? 'bg-blue-600 text-white shadow' : 'bg-slate-200 text-slate-500'">
                                1
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-bold" :class="currentStep === 1 ? 'text-slate-900' : 'text-slate-500'">Langkah 1</span>
                                <span class="block text-[10px]">Data Pelanggan & KTP</span>
                            </div>
                        </div>

                        <!-- Step 2 Tab -->
                        <div class="flex items-center gap-3 p-2.5 rounded-xl border transition duration-150"
                             :class="currentStep === 2 ? 'bg-blue-50/80 border-blue-300 text-blue-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-400'">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs"
                                 :class="currentStep === 2 ? 'bg-blue-600 text-white shadow' : 'bg-slate-200 text-slate-500'">
                                2
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-bold" :class="currentStep === 2 ? 'text-slate-900' : 'text-slate-500'">Langkah 2</span>
                                <span class="block text-[10px]">Layanan & Lokasi Pasang</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validation Error Alert -->
                <div x-show="stepError" x-cloak class="mx-6 mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span x-text="stepError"></span>
                </div>

                <!-- Modal Body Form (Action Dynamic: Store or Update) -->
                <form :action="isEditMode ? '{{ route('teknik.pendaftaran.update') }}' : '{{ route('teknik.pendaftaran.store') }}'" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      class="p-6">
                    @csrf
                    
                    <!-- Hidden input for edit nomor internet -->
                    <input type="hidden" name="nomor_internet" :value="editNomorInternet">
                    
                    <!-- STEP 1: DATA PELANGGAN & KTP -->
                    <div x-show="currentStep === 1" class="space-y-4">
                        
                        <!-- NIK Penduduk & Nama Pelanggan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    NIK Penduduk <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="nikPenduduk"
                                       name="nik_penduduk" 
                                       required
                                       placeholder="nik penduduk" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="namaPelanggan"
                                       name="nama_pelanggan" 
                                       required
                                       placeholder="NAMA PELANGGAN" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none uppercase transition placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Jenis Kelamin & Tanggal Lahir -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-2">
                                    Jenis Kelamin <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-6 pt-1">
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer font-medium">
                                        <input type="radio" x-model="jenisKelamin" name="jenis_kelamin" value="1" class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 focus:ring-blue-500">
                                        <span>LAKI-LAKI</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer font-medium">
                                        <input type="radio" x-model="jenisKelamin" name="jenis_kelamin" value="2" class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 focus:ring-blue-500">
                                        <span>PEREMPUAN</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Tanggal Lahir <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" 
                                       x-model="tanggalLahir"
                                       name="tanggal_lahir" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                            </div>
                        </div>

                        <!-- Instansi / Corporate & Nama PIC -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-2">
                                    Pelanggan Instansi / Corporate ?
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer font-medium">
                                    <input type="checkbox" 
                                           x-model="isCorporate" 
                                           name="is_corporate" 
                                           value="1" 
                                           class="w-4 h-4 rounded text-blue-600 bg-slate-100 border-slate-300 focus:ring-blue-500">
                                    <span>Ya, Corporate / Badan Usaha</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama PIC (Penanggung Jawab)
                                </label>
                                <input type="text" 
                                       x-model="pic"
                                       name="pic" 
                                       :disabled="!isCorporate"
                                       placeholder="NAMA PENANGGUNG JAWAB" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none uppercase transition disabled:bg-slate-100 disabled:opacity-60 disabled:cursor-not-allowed placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Email & Nomor Handphone -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <input type="email" 
                                       x-model="email"
                                       name="email" 
                                       placeholder="email pelanggan" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nomor Handphone <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="nomorHp"
                                       name="nomor_hp" 
                                       required
                                       placeholder="0812xxxx" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nomor HP Keluarga
                                </label>
                                <input type="text" 
                                       x-model="nomorHp2"
                                       name="nomor_hp_2" 
                                       placeholder="0813xxxx" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Wilayah KTP (Provinsi & Kota) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Provinsi KTP <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="provinsiKtp" 
                                        @change="fetchKotaKtp()"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->kode_wilayah_provinsi }}">{{ $prov->nama_provinsi }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kota/Kabupaten KTP <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="kotaKtp" 
                                        @change="fetchKecamatanKtp()"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Kota/Kabupaten</option>
                                    <template x-for="item in kotaKtpList" :key="item.kode_wilayah_kota">
                                        <option :value="item.kode_wilayah_kota" x-text="item.nama_kota"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Wilayah KTP (Kecamatan & Kelurahan) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kecamatan KTP <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="kecamatanKtp" 
                                        @change="fetchKelurahanKtp()"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Kecamatan</option>
                                    <template x-for="item in kecamatanKtpList" :key="item.kode_wilayah_kecamatan">
                                        <option :value="item.kode_wilayah_kecamatan" x-text="item.nama_kecamatan"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kelurahan KTP <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="kelurahanKtp" 
                                        name="kode_wilayah_kelurahan_ktp"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Kelurahan</option>
                                    <template x-for="item in kelurahanKtpList" :key="item.kode_wilayah_kelurahan">
                                        <option :value="item.kode_wilayah_kelurahan" x-text="item.nama_kelurahan"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- RT KTP, RW KTP & Alamat KTP -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    RT KTP <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="rtKtp"
                                       name="rt_ktp" 
                                       placeholder="RT" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    RW KTP <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="rwKtp"
                                       name="rw_ktp" 
                                       placeholder="RW" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Alamat Sesuai KTP <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="alamatKtp"
                                       name="alamat_ktp" 
                                       placeholder="Jalan / Nomor Bangunan..."
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Upload Foto KTP & Foto Rumah -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <!-- Foto KTP -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Foto KTP <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-xl p-3 text-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer flex flex-col items-center justify-center min-h-[110px]">
                                    <input type="file" 
                                           name="foto_ktp" 
                                           accept="image/*"
                                           @change="handleFilePreview($event, 'ktp')"
                                           class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                    <template x-if="!fotoKtpPreview">
                                        <div class="space-y-1 flex flex-col items-center">
                                            <svg class="w-6 h-6 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                            </svg>
                                            <span class="text-[11px] text-slate-600 font-medium">Upload Foto KTP</span>
                                        </div>
                                    </template>
                                    <template x-if="fotoKtpPreview">
                                        <img :src="fotoKtpPreview" class="max-h-20 rounded-lg object-contain">
                                    </template>
                                </div>
                            </div>

                            <!-- Foto Rumah -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Foto Rumah / Lokasi <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-xl p-3 text-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer flex flex-col items-center justify-center min-h-[110px]">
                                    <input type="file" 
                                           name="foto_rumah" 
                                           accept="image/*"
                                           @change="handleFilePreview($event, 'rumah')"
                                           class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                    <template x-if="!fotoRumahPreview">
                                        <div class="space-y-1 flex flex-col items-center">
                                            <svg class="w-6 h-6 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                            </svg>
                                            <span class="text-[11px] text-slate-600 font-medium">Upload Foto Rumah</span>
                                        </div>
                                    </template>
                                    <template x-if="fotoRumahPreview">
                                        <img :src="fotoRumahPreview" class="max-h-20 rounded-lg object-contain">
                                    </template>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- STEP 2: DATA LAYANAN, LOKASI PASANG & SALES ORDER -->
                    <div x-show="currentStep === 2" class="space-y-4">
                        
                        <!-- Jenis Bangunan, No Bangunan & Group Layanan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Jenis Bangunan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="jenisBangunan"
                                        name="jenis_bangunan" 
                                        required
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Jenis Bangunan</option>
                                    @foreach($bangunanList as $b)
                                        <option value="{{ $b->jenis_bangunan }}">{{ $b->jenis_bangunan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    No Bangunan <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="nomorBangunan"
                                       name="nomor_bangunan" 
                                       placeholder="contoh: LT2/15, BLOK C/22" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Group Layanan
                                </label>
                                <select x-model="groupLayanan"
                                        name="group_layanan" 
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="MEDIANET">MEDIANET</option>
                                    <option value="DNET">DNET</option>
                                    <option value="CORPORATE">CORPORATE</option>
                                </select>
                            </div>
                        </div>

                        <!-- Layanan & Paket Bandwidth -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Layanan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="selectedLayanan"
                                        @change="fetchPaket()"
                                        name="kode_kategori_bandwith"
                                        required
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Layanan</option>
                                    @foreach($layananList as $l)
                                        <option value="{{ $l->kode_kategori_bandwith }}">{{ $l->nama_kategori_bandwith }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Paket Bandwidth <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="selectedPaket"
                                        name="kode_bandwith" 
                                        required
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Paket Layanan</option>
                                    <template x-for="p in paketList" :key="p.kode_bandwith">
                                        <option :value="p.kode_bandwith" x-text="p.nominal_bandwith + ' Mbps - Rp ' + Number(p.harga_bandwith).toLocaleString('id-ID')"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Checkbox Sync KTP to Pemasangan -->
                        <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 flex items-center justify-between">
                            <span class="text-xs font-semibold text-blue-800">Data Pemasangan Sama dengan KTP ?</span>
                            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer font-semibold">
                                <input type="checkbox" 
                                       x-model="sameAsKtp"
                                       @change="syncKtpToPasang()"
                                       class="w-4 h-4 rounded text-blue-600 bg-white border-slate-300 focus:ring-blue-500">
                                <span>Ya, Sama dengan KTP</span>
                            </label>
                        </div>

                        <!-- Wilayah Pemasangan (Provinsi & Kota) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Provinsi Pemasangan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="provinsiPasang" 
                                        @change="fetchKotaPasang()"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->kode_wilayah_provinsi }}">{{ $prov->nama_provinsi }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kota/Kabupaten Pemasangan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="kotaPasang" 
                                        @change="fetchKecamatanPasang()"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Kota/Kabupaten</option>
                                    <template x-for="item in kotaPasangList" :key="item.kode_wilayah_kota">
                                        <option :value="item.kode_wilayah_kota" x-text="item.nama_kota"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Wilayah Pemasangan (Kecamatan & Kelurahan) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kecamatan Pemasangan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="kecamatanPasang" 
                                        @change="fetchKelurahanPasang()"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Kecamatan</option>
                                    <template x-for="item in kecamatanPasangList" :key="item.kode_wilayah_kecamatan">
                                        <option :value="item.kode_wilayah_kecamatan" x-text="item.nama_kecamatan"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kelurahan Pemasangan <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="kelurahanPasang" 
                                        name="kode_wilayah_kelurahan_pasang"
                                        class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition">
                                    <option value="">Pilih Kelurahan</option>
                                    <template x-for="item in kelurahanPasangList" :key="item.kode_wilayah_kelurahan">
                                        <option :value="item.kode_wilayah_kelurahan" x-text="item.nama_kelurahan"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- RT, RW & Alamat Pasang -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    RT Pasang <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="rtPasang"
                                       name="rt_pasang" 
                                       placeholder="RT" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    RW Pasang <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="rwPasang"
                                       name="rw_pasang" 
                                       placeholder="RW" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Alamat Pemasangan Lengkap <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="alamatPasang"
                                       name="alamat_pasang" 
                                       required
                                       placeholder="Jalan, Blok, Patokan lokasi..."
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Titik Koordinat & Sharelock Lokasi -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Titik Koordinat (Lat, Long)
                                </label>
                                <input type="text" 
                                       x-model="lonLat"
                                       name="lon_lat" 
                                       placeholder="-6.914744, 107.609810" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Sharelock Lokasi Maps <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="locMaps"
                                       name="loc_maps" 
                                       placeholder="https://maps.app.goo.gl/..." 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Permintaan Khusus & Sales -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Permintaan Khusus Pelanggan
                                </label>
                                <input type="text" 
                                       x-model="noteRequest"
                                       name="note_request" 
                                       placeholder="Catatan instalasi, misal: pasang jam 2 siang..." 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Sales <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="namaSales"
                                       name="nama_sales" 
                                       required
                                       placeholder="nama sales" 
                                       class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none transition placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Info Nomor Pelanggan Otomatis / Edit -->
                        <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-between">
                            <div class="text-xs text-slate-700 font-medium">
                                Nomor Pelanggan: <strong class="text-blue-600 font-mono text-sm tracking-wide" x-text="editNomorInternet"></strong>
                            </div>
                            <span class="text-[11px] text-slate-500" x-text="isEditMode ? 'Nomor Pelanggan Tetap' : 'Dibuat otomatis oleh sistem'"></span>
                        </div>

                    </div>

                    <!-- Modal Wizard Footer Action Buttons -->
                    <div class="mt-8 pt-5 border-t border-slate-200 flex items-center justify-between">
                        
                        <!-- Step 1 Controls -->
                        <template x-if="currentStep === 1">
                            <div class="w-full flex items-center justify-between">
                                <button type="button" 
                                        @click="modalOpen = false"
                                        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer border border-slate-200">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    <span>Batal</span>
                                </button>

                                <button type="button" 
                                        @click="nextToStep2()"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-500/25 cursor-pointer">
                                    <span>Lanjut ke Langkah 2</span>
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <!-- Step 2 Controls -->
                        <template x-if="currentStep === 2">
                            <div class="w-full flex items-center justify-between">
                                <button type="button" 
                                        @click="currentStep = 1"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer border border-slate-200">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                                    </svg>
                                    <span>Kembali ke Langkah 1</span>
                                </button>

                                <button type="submit" 
                                        class="inline-flex items-center gap-2 px-7 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-lg shadow-emerald-500/25 cursor-pointer">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                    </svg>
                                    <span x-text="isEditMode ? 'Simpan Perubahan' : 'Simpan Registrasi'"></span>
                                </button>
                            </div>
                        </template>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- =================================================================== -->
    <!-- 1. MODAL FORM SURVEY (SCHEDULE SURVEY)                              -->
    <!-- =================================================================== -->
    <div x-show="scheduleSurveyModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="scheduleSurveyModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
                    <h3 class="text-base font-bold text-slate-800 tracking-wide">
                        Form Survey An/<span x-text="surveyForm.nama_pelanggan || 'PELANGGAN'"></span>
                    </h3>
                    <button type="button" 
                            @click="scheduleSurveyModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form method="POST" action="{{ route('teknik.pendaftaran.schedule-survey') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="nomor_internet" :value="surveyForm.nomor_internet">
                    <input type="hidden" name="nama_pelanggan" :value="surveyForm.nama_pelanggan">

                    <div class="p-6 space-y-6 max-h-[78vh] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Tanggal Survey <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" 
                                           name="survey_date_start" 
                                           x-model="surveyForm.survey_date_start" 
                                           required
                                           class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Waktu Survey <span class="text-rose-500">*</span>
                                    </label>
                                    <select name="survey_time" 
                                            x-model="surveyForm.survey_time" 
                                            required
                                            class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                        <option value="">Pilih waktu survey</option>
                                        @foreach($timeJobs as $time)
                                            <option value="{{ $time->time_job }}">{{ $time->time_job }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Catatan Survey <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea name="survey_note" 
                                              x-model="surveyForm.survey_note" 
                                              rows="2" 
                                              required
                                              placeholder="masukan catatan untuk teknisi lapangan saat proses instalasi."
                                              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                                </div>

                                <!-- Foto Mapping Upload Area -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Foto Mapping <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative border-2 border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-blue-500 transition cursor-pointer bg-slate-50 hover:bg-slate-100">
                                        <input type="file" 
                                               name="foto_mapping" 
                                               accept="image/*"
                                               @change="const file = $event.target.files[0]; if(file) { surveyForm.foto_mapping_preview = URL.createObjectURL(file); }"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        
                                        <template x-if="!surveyForm.foto_mapping_preview">
                                            <div class="flex flex-col items-center justify-center py-4">
                                                <svg class="w-10 h-10 text-slate-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                                </svg>
                                                <p class="text-xs text-slate-500 font-medium">Drag and drop a file here or click</p>
                                            </div>
                                        </template>

                                        <template x-if="surveyForm.foto_mapping_preview">
                                            <div class="relative rounded-xl overflow-hidden max-h-40 flex items-center justify-center">
                                                <img :src="surveyForm.foto_mapping_preview" class="object-cover max-h-36 rounded-lg shadow-sm">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Team Survey -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-2">
                                    Team Survey
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-xl max-h-80 overflow-y-auto">
                                    @foreach($karyawanTeknisi as $k)
                                        <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-200 cursor-pointer text-xs transition">
                                            <input type="checkbox" 
                                                   name="team_survey[]" 
                                                   value="{{ $k->kode_karyawan }}" 
                                                   :checked="surveyForm.team_survey.includes('{{ $k->kode_karyawan }}')"
                                                   class="w-4 h-4 rounded text-blue-600 bg-white border-slate-300 focus:ring-blue-500">
                                            <span class="text-slate-800 uppercase font-medium text-[11px]">
                                                {{ $k->nama_karyawan }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="scheduleSurveyModalOpen = false"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md shadow-blue-500/20 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span>Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. MODAL REPORT SURVEY (HASIL SURVEY & INPUT PERANGKAT)             -->
    <!-- =================================================================== -->
    <div x-show="reportSurveyModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="reportSurveyModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-5xl">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
                    <h3 class="text-base font-bold text-slate-800 tracking-wide">
                        Report Survey An/<span x-text="reportSurveyForm.nama_pelanggan || 'PELANGGAN'"></span>
                    </h3>
                    <button type="button" 
                            @click="reportSurveyModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form method="POST" action="{{ route('teknik.pendaftaran.report-survey') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="nomor_internet" :value="reportSurveyForm.nomor_internet">
                    <input type="hidden" name="nama_pelanggan" :value="reportSurveyForm.nama_pelanggan">

                    <div class="p-6 space-y-6 max-h-[78vh] overflow-y-auto">
                        
                        <!-- Top Checkbox: Jadwal Ulang Survey -->
                        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 flex items-center gap-3">
                            <span class="text-sm font-bold text-rose-600">Jadwal Ulang Survey ?</span>
                            <label class="inline-flex items-center gap-2 cursor-pointer font-semibold">
                                <input type="checkbox" 
                                       name="is_reschedule" 
                                       value="1" 
                                       x-model="reportSurveyForm.is_reschedule"
                                       class="w-4 h-4 rounded text-rose-600 bg-white border-slate-300 focus:ring-rose-500">
                                <span class="text-xs text-slate-700">Ya, Jadwal Ulang</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            <span x-text="reportSurveyForm.is_reschedule ? 'Tanggal Reschedule Survey' : 'Tanggal Selesai Survey'"></span> <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="date" 
                                               name="survey_date_finish" 
                                               x-model="reportSurveyForm.survey_date_finish" 
                                               required
                                               class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            <span x-text="reportSurveyForm.is_reschedule ? 'Catatan Jadwal Ulang' : 'Catatan Selesai Survey'"></span> <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" 
                                               name="survey_note_finish" 
                                               x-model="reportSurveyForm.survey_note_finish" 
                                               required
                                               placeholder="CATATAN SELESAI"
                                               class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>
                                </div>

                                <!-- Radio: Bisa Dilakukan Pemasangan -->
                                <template x-if="!reportSurveyForm.is_reschedule">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-slate-700">
                                            Bisa Dilakukan Pemasangan <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="flex items-center gap-6 pt-1">
                                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                                                <input type="radio" 
                                                       name="bisa_pasang" 
                                                       value="YA" 
                                                       x-model="reportSurveyForm.bisa_pasang"
                                                       class="w-4 h-4 text-emerald-600 bg-white border-slate-300 focus:ring-emerald-500">
                                                <span>YA</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                                                <input type="radio" 
                                                       name="bisa_pasang" 
                                                       value="TIDAK" 
                                                       x-model="reportSurveyForm.bisa_pasang"
                                                       class="w-4 h-4 text-rose-600 bg-white border-slate-300 focus:ring-rose-500">
                                                <span>Tidak</span>
                                            </label>
                                        </div>
                                    </div>
                                </template>

                                <!-- Update Foto Mapping -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Update Foto Mapping <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative border-2 border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-blue-500 transition cursor-pointer bg-slate-50 hover:bg-slate-100">
                                        <input type="file" 
                                               name="update_foto_mapping" 
                                               accept="image/*"
                                               @change="const file = $event.target.files[0]; if(file) { reportSurveyForm.foto_mapping_preview = URL.createObjectURL(file); }"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        
                                        <template x-if="!reportSurveyForm.foto_mapping_preview">
                                            <div class="flex flex-col items-center justify-center py-4">
                                                <svg class="w-8 h-8 text-slate-400 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                                </svg>
                                                <p class="text-xs text-slate-500 font-medium">Drag and drop a file here or click</p>
                                            </div>
                                        </template>

                                        <template x-if="reportSurveyForm.foto_mapping_preview">
                                            <div class="relative rounded-xl overflow-hidden max-h-36 flex items-center justify-center">
                                                <img :src="reportSurveyForm.foto_mapping_preview" class="object-cover max-h-32 rounded-lg shadow-sm">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Team Survey & Perangkat -->
                            <div class="space-y-5">
                                
                                <!-- Team Survey -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Team Survey
                                    </label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl max-h-40 overflow-y-auto">
                                        @foreach($karyawanTeknisi as $k)
                                            <label class="flex items-center gap-1.5 p-1 rounded hover:bg-slate-200 cursor-pointer text-xs transition">
                                                <input type="checkbox" 
                                                       name="team_survey[]" 
                                                       value="{{ $k->kode_karyawan }}" 
                                                       :checked="reportSurveyForm.team_survey.includes('{{ $k->kode_karyawan }}')"
                                                       class="w-3.5 h-3.5 rounded text-blue-600 bg-white border-slate-300">
                                                <span class="text-slate-800 uppercase font-medium text-[10px] truncate">
                                                    {{ $k->nama_karyawan }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Perangkat / Peralatan yang Digunakan -->
                                <div class="space-y-3 pt-2 border-t border-slate-200">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-4 bg-teal-500 rounded-full"></div>
                                        <span class="text-xs font-bold text-slate-800">
                                            Perangkat/ Peralatan Yang Digunakan
                                        </span>
                                    </div>

                                    <!-- Add Perangkat Controls -->
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <select x-model="surveySelectedBarang" 
                                                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-teal-500">
                                                <option value="">Pilih Perangkat</option>
                                                @foreach($barangList as $b)
                                                    <option value="{{ $b->kode_barang }}">
                                                        {{ trim(((isset($b->nama_jns_barang) && $b->nama_jns_barang) ? $b->nama_jns_barang . ' ' : '') . ($b->nama_barang ?? '') . ((isset($b->tipe_barang) && $b->tipe_barang) ? ' ' . $b->tipe_barang : '')) }} ({{ $b->satuan ?? 'UNIT' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="w-24">
                                            <input type="number" 
                                                   min="1" 
                                                   x-model="surveySelectedJumlah" 
                                                   placeholder="Jumlah" 
                                                   class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-teal-500 text-center">
                                        </div>
                                        <button type="button" 
                                                @click="addDeviceToSurvey()"
                                                class="px-4 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-sm cursor-pointer">
                                            Add
                                        </button>
                                    </div>

                                    <!-- Devices Table -->
                                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-inner">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-100 text-[11px] font-bold text-slate-600">
                                                <tr>
                                                    <th class="py-2 px-3">Barang</th>
                                                    <th class="py-2 px-3 text-center">Jumlah</th>
                                                    <th class="py-2 px-3 text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-if="reportSurveyForm.perangkat_list.length === 0">
                                                    <tr>
                                                        <td colspan="3" class="py-4 text-center text-slate-400 italic text-[11px]">
                                                            No data available in table
                                                        </td>
                                                    </tr>
                                                </template>
                                                <template x-for="(item, index) in reportSurveyForm.perangkat_list" :key="index">
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="py-2 px-3 font-semibold text-slate-800 uppercase" x-text="item.nama_barang"></td>
                                                        <td class="py-2 px-3 text-center font-mono font-bold text-slate-700" x-text="item.jumlah + ' ' + (item.satuan || 'UNIT')"></td>
                                                        <td class="py-2 px-3 text-center">
                                                            <!-- Hidden Form Inputs -->
                                                            <input type="hidden" :name="'perangkat[' + index + '][kode_barang]'" :value="item.kode_barang">
                                                            <input type="hidden" :name="'perangkat[' + index + '][jumlah]'" :value="item.jumlah">
                                                            <button type="button" 
                                                                    @click="removeDeviceFromSurvey(index)"
                                                                    class="text-rose-500 hover:text-rose-700 p-1 rounded transition">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="reportSurveyModalOpen = false"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md shadow-blue-500/20 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span>Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. MODAL FORM INSTALASI (SCHEDULE INSTALASI)                        -->
    <!-- =================================================================== -->
    <div x-show="scheduleInstalasiModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="scheduleInstalasiModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-5xl">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
                    <h3 class="text-base font-bold text-slate-800 tracking-wide">
                        Form Instalasi An/<span x-text="instalasiForm.nama_pelanggan || 'PELANGGAN'"></span>
                    </h3>
                    <button type="button" 
                            @click="scheduleInstalasiModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form method="POST" action="{{ route('teknik.pendaftaran.schedule-instalasi') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="nomor_internet" :value="instalasiForm.nomor_internet">
                    <input type="hidden" name="nama_pelanggan" :value="instalasiForm.nama_pelanggan">

                    <div class="p-6 space-y-6 max-h-[78vh] overflow-y-auto">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Left Column -->
                            <div class="space-y-4">
                                
                                <!-- Permintaan dari Pelanggan (Orange Box) -->
                                <div>
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <div class="w-1 h-4 bg-amber-500 rounded-full"></div>
                                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                            PERMINTAAN DARI PELANGGAN
                                        </span>
                                    </div>
                                    <div class="p-3 bg-amber-500/90 text-white rounded-xl font-bold text-xs shadow-sm">
                                        <span x-text="instalasiForm.note_request || 'Catatan pendaftaran standar'"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Tanggal Instalasi <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="date" 
                                               name="instalasi_date_start" 
                                               x-model="instalasiForm.instalasi_date_start" 
                                               required
                                               class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Waktu Instalasi <span class="text-rose-500">*</span>
                                        </label>
                                        <select name="instalasi_time" 
                                                x-model="instalasiForm.instalasi_time" 
                                                required
                                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                            <option value="">Pilih waktu instalasi</option>
                                            @foreach($timeJobs as $time)
                                                <option value="{{ $time->time_job }}">{{ $time->time_job }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Team Instalasi -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Team Instalasi
                                    </label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl max-h-40 overflow-y-auto">
                                        @foreach($karyawanTeknisi as $k)
                                            <label class="flex items-center gap-1.5 p-1 rounded hover:bg-slate-200 cursor-pointer text-xs transition">
                                                <input type="checkbox" 
                                                       name="team_instalasi[]" 
                                                       value="{{ $k->kode_karyawan }}" 
                                                       :checked="instalasiForm.team_instalasi.includes('{{ $k->kode_karyawan }}')"
                                                       class="w-3.5 h-3.5 rounded text-blue-600 bg-white border-slate-300">
                                                <span class="text-slate-800 uppercase font-medium text-[10px] truncate">
                                                    {{ $k->nama_karyawan }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Catatan Pemasangan <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea name="instalasi_note" 
                                              x-model="instalasiForm.instalasi_note" 
                                              rows="2" 
                                              required
                                              placeholder="Catatan untuk teknisi lapangan saat proses pemasangan..."
                                              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                                </div>

                            </div>

                            <!-- Right Column: Perangkat & Foto Mapping -->
                            <div class="space-y-5">
                                
                                <!-- Perangkat Yang Digunakan -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-4 bg-teal-500 rounded-full"></div>
                                        <span class="text-xs font-bold text-slate-800">
                                            Perangkat/ Peralatan Yang Digunakan
                                        </span>
                                    </div>

                                    <!-- Add Perangkat Controls -->
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <select x-model="instalasiSelectedBarang" 
                                                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-teal-500">
                                                <option value="">Pilih Perangkat</option>
                                                @foreach($barangList as $b)
                                                    <option value="{{ $b->kode_barang }}">
                                                        {{ trim(((isset($b->nama_jns_barang) && $b->nama_jns_barang) ? $b->nama_jns_barang . ' ' : '') . ($b->nama_barang ?? '') . ((isset($b->tipe_barang) && $b->tipe_barang) ? ' ' . $b->tipe_barang : '')) }} ({{ $b->satuan ?? 'UNIT' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="w-24">
                                            <input type="number" 
                                                   min="1" 
                                                   x-model="instalasiSelectedJumlah" 
                                                   placeholder="Jumlah" 
                                                   class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-teal-500 text-center">
                                        </div>
                                        <button type="button" 
                                                @click="addDeviceToInstalasi()"
                                                class="px-4 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-sm cursor-pointer">
                                            Add
                                        </button>
                                    </div>

                                    <!-- Devices Table -->
                                    <div class="border border-slate-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto shadow-inner">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-100 text-[11px] font-bold text-slate-600">
                                                <tr>
                                                    <th class="py-2 px-3">Barang</th>
                                                    <th class="py-2 px-3 text-center">Jumlah</th>
                                                    <th class="py-2 px-3 text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-if="instalasiForm.perangkat_list.length === 0">
                                                    <tr>
                                                        <td colspan="3" class="py-4 text-center text-slate-400 italic text-[11px]">
                                                            No data available in table
                                                        </td>
                                                    </tr>
                                                </template>
                                                <template x-for="(item, index) in instalasiForm.perangkat_list" :key="index">
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="py-2 px-3 font-semibold text-slate-800 uppercase" x-text="item.nama_barang"></td>
                                                        <td class="py-2 px-3 text-center font-mono font-bold text-slate-700" x-text="item.jumlah + ' ' + (item.satuan || 'UNIT')"></td>
                                                        <td class="py-2 px-3 text-center">
                                                            <input type="hidden" :name="'perangkat[' + index + '][kode_barang]'" :value="item.kode_barang">
                                                            <input type="hidden" :name="'perangkat[' + index + '][jumlah]'" :value="item.jumlah">
                                                            <button type="button" 
                                                                    @click="removeDeviceFromInstalasi(index)"
                                                                    class="text-rose-500 hover:text-rose-700 p-1 rounded transition">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Update Foto Mapping -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Update Foto Mapping <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative border-2 border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-blue-500 transition cursor-pointer bg-slate-50 hover:bg-slate-100">
                                        <input type="file" 
                                               name="update_foto_mapping" 
                                               accept="image/*"
                                               @change="const file = $event.target.files[0]; if(file) { instalasiForm.foto_mapping_preview = URL.createObjectURL(file); }"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        
                                        <template x-if="!instalasiForm.foto_mapping_preview">
                                            <div class="flex flex-col items-center justify-center py-3">
                                                <svg class="w-8 h-8 text-slate-400 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                                </svg>
                                                <p class="text-xs text-slate-500 font-medium">Drag and drop a file here or click</p>
                                            </div>
                                        </template>

                                        <template x-if="instalasiForm.foto_mapping_preview">
                                            <div class="relative rounded-xl overflow-hidden max-h-32 flex items-center justify-center">
                                                <img :src="instalasiForm.foto_mapping_preview" class="object-cover max-h-28 rounded-lg shadow-sm">
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="scheduleInstalasiModalOpen = false"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md shadow-blue-500/20 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span>Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. MODAL REPORT INSTALASI (HASIL PEMASANGAN & FINALISASI PERANGKAT) -->
    <!-- =================================================================== -->
    <div x-show="reportInstalasiModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="reportInstalasiModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-5xl">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
                    <h3 class="text-base font-bold text-slate-800 tracking-wide">
                        Report Instalasi An/<span x-text="reportInstalasiForm.nama_pelanggan || 'PELANGGAN'"></span>
                    </h3>
                    <button type="button" 
                            @click="reportInstalasiModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form method="POST" action="{{ route('teknik.pendaftaran.report-instalasi') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="nomor_internet" :value="reportInstalasiForm.nomor_internet">
                    <input type="hidden" name="nama_pelanggan" :value="reportInstalasiForm.nama_pelanggan">

                    <div class="p-6 space-y-6 max-h-[78vh] overflow-y-auto">
                        
                        <!-- Top Checkbox: Jadwal Ulang Pemasangan -->
                        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 flex items-center gap-3">
                            <span class="text-sm font-bold text-rose-600">Jadwal Ulang Pemasangan ?</span>
                            <label class="inline-flex items-center gap-2 cursor-pointer font-semibold">
                                <input type="checkbox" 
                                       name="is_reschedule" 
                                       value="1" 
                                       x-model="reportInstalasiForm.is_reschedule"
                                       class="w-4 h-4 rounded text-rose-600 bg-white border-slate-300 focus:ring-rose-500">
                                <span class="text-xs text-slate-700">Ya, Jadwal Ulang</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Left Column -->
                            <div class="space-y-4">
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            <span x-text="reportInstalasiForm.is_reschedule ? 'Tanggal Reschedule Instalasi' : 'Selesai Instalasi'"></span> <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="date" 
                                               name="instalasi_date_finish" 
                                               x-model="reportInstalasiForm.instalasi_date_finish" 
                                               required
                                               class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            <span x-text="reportInstalasiForm.is_reschedule ? 'Catatan Jadwal Ulang' : 'Catatan Selesai Instalasi'"></span> <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" 
                                               name="instalasi_note_finish" 
                                               x-model="reportInstalasiForm.instalasi_note_finish" 
                                               required
                                               placeholder="catatan Instalasi"
                                               class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>
                                </div>

                                <!-- Team Instalasi -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Team Instalasi <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl max-h-48 overflow-y-auto">
                                        @foreach($karyawanTeknisi as $k)
                                            <label class="flex items-center gap-1.5 p-1 rounded hover:bg-slate-200 cursor-pointer text-xs transition">
                                                <input type="checkbox" 
                                                       name="team_instalasi[]" 
                                                       value="{{ $k->kode_karyawan }}" 
                                                       :checked="reportInstalasiForm.team_instalasi.includes('{{ $k->kode_karyawan }}')"
                                                       class="w-3.5 h-3.5 rounded text-blue-600 bg-white border-slate-300">
                                                <span class="text-slate-800 uppercase font-medium text-[10px] truncate">
                                                    {{ $k->nama_karyawan }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Update Foto Mapping -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Update Foto Mapping <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative border-2 border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-blue-500 transition cursor-pointer bg-slate-50 hover:bg-slate-100">
                                        <input type="file" 
                                               name="update_foto_mapping" 
                                               accept="image/*"
                                               @change="const file = $event.target.files[0]; if(file) { reportInstalasiForm.foto_mapping_preview = URL.createObjectURL(file); }"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        
                                        <template x-if="!reportInstalasiForm.foto_mapping_preview">
                                            <div class="flex flex-col items-center justify-center py-3">
                                                <svg class="w-8 h-8 text-slate-400 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                                </svg>
                                                <p class="text-xs text-slate-500 font-medium">Drag and drop a file here or click</p>
                                            </div>
                                        </template>

                                        <template x-if="reportInstalasiForm.foto_mapping_preview">
                                            <div class="relative rounded-xl overflow-hidden max-h-32 flex items-center justify-center">
                                                <img :src="reportInstalasiForm.foto_mapping_preview" class="object-cover max-h-28 rounded-lg shadow-sm">
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>

                            <!-- Right Column: Perangkat / Peralatan Terpasang -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-1 h-4 bg-teal-500 rounded-full"></div>
                                    <span class="text-xs font-bold text-slate-800">
                                        Perangkat/ Peralatan Yang Digunakan
                                    </span>
                                </div>

                                <!-- Add Perangkat Controls -->
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <select x-model="reportInstalasiSelectedBarang" 
                                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-teal-500">
                                            <option value="">Pilih Perangkat</option>
                                            @foreach($barangList as $b)
                                                <option value="{{ $b->kode_barang }}">
                                                    {{ trim(((isset($b->nama_jns_barang) && $b->nama_jns_barang) ? $b->nama_jns_barang . ' ' : '') . ($b->nama_barang ?? '') . ((isset($b->tipe_barang) && $b->tipe_barang) ? ' ' . $b->tipe_barang : '')) }} ({{ $b->satuan ?? 'UNIT' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-24">
                                        <input type="number" 
                                               min="1" 
                                               x-model="reportInstalasiSelectedJumlah" 
                                               placeholder="Jumlah" 
                                               class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-teal-500 text-center">
                                    </div>
                                    <button type="button" 
                                            @click="addDeviceToReportInstalasi()"
                                            class="px-4 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-sm cursor-pointer">
                                        Add
                                    </button>
                                </div>

                                <!-- Devices Table (Matching screenshot layout) -->
                                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto shadow-inner">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-100 text-[11px] font-bold text-slate-600">
                                            <tr>
                                                <th class="py-2.5 px-3">Barang</th>
                                                <th class="py-2.5 px-3 text-center">Jumlah</th>
                                                <th class="py-2.5 px-3 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            <template x-if="reportInstalasiForm.perangkat_list.length === 0">
                                                <tr>
                                                    <td colspan="3" class="py-6 text-center text-slate-400 italic text-[11px]">
                                                        No data available in table
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-for="(item, index) in reportInstalasiForm.perangkat_list" :key="index">
                                                <tr class="hover:bg-slate-50">
                                                    <td class="py-2.5 px-3 font-semibold text-slate-800 uppercase" x-text="item.nama_barang"></td>
                                                    <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700" x-text="item.jumlah + ' ' + (item.satuan || 'UNIT')"></td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <input type="hidden" :name="'perangkat[' + index + '][kode_barang]'" :value="item.kode_barang">
                                                        <input type="hidden" :name="'perangkat[' + index + '][jumlah]'" :value="item.jumlah">
                                                        <button type="button" 
                                                                @click="removeDeviceFromReportInstalasi(index)"
                                                                title="Hapus Barang"
                                                                class="text-rose-500 hover:text-rose-700 p-1 rounded transition hover:scale-110">
                                                            <svg class="w-4 h-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="reportInstalasiModalOpen = false"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md shadow-blue-500/20 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span>Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. MODAL REQUEST AKTIVASI KE NOC                                    -->
    <!-- =================================================================== -->
    <div x-show="requestAktivasiModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="requestAktivasiModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-slate-200 text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-amber-50/80">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 border border-amber-200 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a5 5 0 0 1-5.84 7.38v-4.8m5.84-2.58a5 5 0 0 0-7.38-5.84l3.4 3.4M12 2.25a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM6.166 5.106a.75.75 0 0 1 1.06 0l1.592 1.592a.75.75 0 0 1-1.06 1.06L6.166 6.166a.75.75 0 0 1 0-1.06Zm11.668 0a.75.75 0 0 1 0 1.06l-1.592 1.592a.75.75 0 1 1-1.06-1.06l1.592-1.592a.75.75 0 0 1 1.06 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                Kirim Permintaan Aktivasi NOC
                            </h3>
                            <p class="text-[11px] text-slate-500">Serahkan pendaftaran selesai instalasi ke tim NOC</p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="requestAktivasiModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form method="POST" action="{{ route('teknik.pendaftaran.request-aktivasi') }}">
                    @csrf
                    <input type="hidden" name="nomor_internet" :value="aktivasiForm.nomor_internet">
                    <input type="hidden" name="nama_pelanggan" :value="aktivasiForm.nama_pelanggan">

                    <div class="p-6 space-y-4">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nomor Internet:</span>
                                <span class="font-mono font-bold text-blue-600" x-text="aktivasiForm.nomor_internet"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nama Pelanggan:</span>
                                <span class="font-bold text-slate-800 uppercase" x-text="aktivasiForm.nama_pelanggan"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Catatan Serah Terima ke NOC
                            </label>
                            <textarea name="catatan_aktivasi" 
                                      x-model="aktivasiForm.catatan_aktivasi" 
                                      rows="3" 
                                      placeholder="Tambahkan catatan khusus untuk tim NOC..."
                                      class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition"></textarea>
                        </div>

                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Setelah dikirim, status pelanggan akan berubah menjadi <strong class="text-amber-600">Jadwal Aktivasi Terbit (#19)</strong> dan akan langsung muncul pada antrean aktivasi Tim NOC.
                        </p>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="requestAktivasiModalOpen = false"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 cursor-pointer">
                            <span>Batal</span>
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-xs font-bold transition shadow-md shadow-amber-500/25 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a5 5 0 0 1-5.84 7.38v-4.8m5.84-2.58a5 5 0 0 0-7.38-5.84l3.4 3.4" />
                            </svg>
                            <span>Kirim ke NOC</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function pendaftaranWorkflowComponent() {
    return {
        // Master Data Arrays
        allBarangList: @json($barangList ?? []),
        allKaryawanList: @json($karyawanTeknisi ?? []),
        allTimeJobs: @json($timeJobs ?? []),

        // Workflow Modals Visibility
        scheduleSurveyModalOpen: false,
        reportSurveyModalOpen: false,
        scheduleInstalasiModalOpen: false,
        reportInstalasiModalOpen: false,
        requestAktivasiModalOpen: false,
        isLoadingWorkflow: false,

        // Form 1: Schedule Survey
        surveyForm: {
            nomor_internet: '',
            nama_pelanggan: '',
            survey_date_start: '{{ date('Y-m-d') }}',
            survey_time: '',
            survey_note: '',
            team_survey: [],
            foto_mapping_preview: null
        },

        // Form 2: Report Survey
        reportSurveyForm: {
            nomor_internet: '',
            nama_pelanggan: '',
            is_reschedule: false,
            survey_date_finish: '{{ date('Y-m-d') }}',
            survey_note_finish: '',
            bisa_pasang: 'YA',
            team_survey: [],
            perangkat_list: [],
            foto_mapping_preview: null
        },
        surveySelectedBarang: '',
        surveySelectedJumlah: 1,

        // Form 3: Schedule Instalasi
        instalasiForm: {
            nomor_internet: '',
            nama_pelanggan: '',
            note_request: '',
            instalasi_date_start: '{{ date('Y-m-d') }}',
            instalasi_time: '',
            instalasi_note: '',
            team_instalasi: [],
            perangkat_list: [],
            foto_mapping_preview: null
        },
        instalasiSelectedBarang: '',
        instalasiSelectedJumlah: 1,

        // Form 4: Report Instalasi
        reportInstalasiForm: {
            nomor_internet: '',
            nama_pelanggan: '',
            is_reschedule: false,
            instalasi_date_finish: '{{ date('Y-m-d') }}',
            instalasi_note_finish: '',
            team_instalasi: [],
            perangkat_list: [],
            foto_mapping_preview: null
        },
        reportInstalasiSelectedBarang: '',
        reportInstalasiSelectedJumlah: 1,

        // Form 5: Request Aktivasi ke NOC
        aktivasiForm: {
            nomor_internet: '',
            nama_pelanggan: '',
            catatan_aktivasi: 'Request aktivasi layanan pelanggan baru ke tim NOC'
        },

        // Helpers for adding & removing devices
        addDeviceToSurvey() {
            if (!this.surveySelectedBarang) return;
            const found = this.allBarangList.find(b => b.kode_barang === this.surveySelectedBarang);
            const nama = found ? (((found.nama_jns_barang ? found.nama_jns_barang + ' ' : '') + (found.nama_barang ? found.nama_barang + ' ' : '') + (found.tipe_barang || '')).trim()) : this.surveySelectedBarang;
            const satuan = found && found.satuan ? found.satuan : 'UNIT';
            
            const existing = this.reportSurveyForm.perangkat_list.find(p => p.kode_barang === this.surveySelectedBarang);
            if (existing) {
                existing.jumlah = (parseInt(existing.jumlah) || 0) + (parseInt(this.surveySelectedJumlah) || 1);
            } else {
                this.reportSurveyForm.perangkat_list.push({
                    kode_barang: this.surveySelectedBarang,
                    nama_barang: nama,
                    jumlah: parseInt(this.surveySelectedJumlah) || 1,
                    satuan: satuan
                });
            }
            this.surveySelectedBarang = '';
            this.surveySelectedJumlah = 1;
        },
        removeDeviceFromSurvey(index) {
            this.reportSurveyForm.perangkat_list.splice(index, 1);
        },

        addDeviceToInstalasi() {
            if (!this.instalasiSelectedBarang) return;
            const found = this.allBarangList.find(b => b.kode_barang === this.instalasiSelectedBarang);
            const nama = found ? (((found.nama_jns_barang ? found.nama_jns_barang + ' ' : '') + (found.nama_barang ? found.nama_barang + ' ' : '') + (found.tipe_barang || '')).trim()) : this.instalasiSelectedBarang;
            const satuan = found && found.satuan ? found.satuan : 'UNIT';
            
            const existing = this.instalasiForm.perangkat_list.find(p => p.kode_barang === this.instalasiSelectedBarang);
            if (existing) {
                existing.jumlah = (parseInt(existing.jumlah) || 0) + (parseInt(this.instalasiSelectedJumlah) || 1);
            } else {
                this.instalasiForm.perangkat_list.push({
                    kode_barang: this.instalasiSelectedBarang,
                    nama_barang: nama,
                    jumlah: parseInt(this.instalasiSelectedJumlah) || 1,
                    satuan: satuan
                });
            }
            this.instalasiSelectedBarang = '';
            this.instalasiSelectedJumlah = 1;
        },
        removeDeviceFromInstalasi(index) {
            this.instalasiForm.perangkat_list.splice(index, 1);
        },

        addDeviceToReportInstalasi() {
            if (!this.reportInstalasiSelectedBarang) return;
            const found = this.allBarangList.find(b => b.kode_barang === this.reportInstalasiSelectedBarang);
            const nama = found ? (((found.nama_jns_barang ? found.nama_jns_barang + ' ' : '') + (found.nama_barang ? found.nama_barang + ' ' : '') + (found.tipe_barang || '')).trim()) : this.reportInstalasiSelectedBarang;
            const satuan = found && found.satuan ? found.satuan : 'UNIT';
            
            const existing = this.reportInstalasiForm.perangkat_list.find(p => p.kode_barang === this.reportInstalasiSelectedBarang);
            if (existing) {
                existing.jumlah = (parseInt(existing.jumlah) || 0) + (parseInt(this.reportInstalasiSelectedJumlah) || 1);
            } else {
                this.reportInstalasiForm.perangkat_list.push({
                    kode_barang: this.reportInstalasiSelectedBarang,
                    nama_barang: nama,
                    jumlah: parseInt(this.reportInstalasiSelectedJumlah) || 1,
                    satuan: satuan
                });
            }
            this.reportInstalasiSelectedBarang = '';
            this.reportInstalasiSelectedJumlah = 1;
        },
        removeDeviceFromReportInstalasi(index) {
            this.reportInstalasiForm.perangkat_list.splice(index, 1);
        },

        // Modal Openers
        async openScheduleSurveyModal(nomorInternet, namaPelanggan) {
            this.isLoadingWorkflow = true;
            this.surveyForm.nomor_internet = nomorInternet;
            this.surveyForm.nama_pelanggan = namaPelanggan || '';
            this.surveyForm.survey_date_start = '{{ date('Y-m-d') }}';
            this.surveyForm.survey_time = this.allTimeJobs[0] ? this.allTimeJobs[0].time_job : '';
            this.surveyForm.survey_note = '';
            this.surveyForm.team_survey = [];
            this.surveyForm.foto_mapping_preview = null;
            this.scheduleSurveyModalOpen = true;

            try {
                const res = await fetch(`/teknik/api/survey-instalasi/${nomorInternet}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data.team_survey && data.team_survey.length > 0) {
                        this.surveyForm.team_survey = data.team_survey;
                    }
                    if (data.register) {
                        if (data.register.nama_pelanggan) this.surveyForm.nama_pelanggan = data.register.nama_pelanggan;
                    }
                    if (data.instalasi) {
                        if (data.instalasi.survey_date_start) this.surveyForm.survey_date_start = data.instalasi.survey_date_start.substring(0, 10);
                        if (data.instalasi.survey_time) this.surveyForm.survey_time = data.instalasi.survey_time;
                        if (data.instalasi.survey_note) this.surveyForm.survey_note = data.instalasi.survey_note;
                        if (data.instalasi.foto_peta || data.instalasi.doc_survey) {
                            this.surveyForm.foto_mapping_preview = `/uploads/registrasi/${data.instalasi.foto_peta || data.instalasi.doc_survey}`;
                        }
                    }
                }
            } catch(e) {
                console.error(e);
            } finally {
                this.isLoadingWorkflow = false;
            }
        },

        async openReportSurveyModal(nomorInternet, namaPelanggan) {
            this.isLoadingWorkflow = true;
            this.reportSurveyForm.nomor_internet = nomorInternet;
            this.reportSurveyForm.nama_pelanggan = namaPelanggan || '';
            this.reportSurveyForm.is_reschedule = false;
            this.reportSurveyForm.survey_date_finish = '{{ date('Y-m-d') }}';
            this.reportSurveyForm.survey_note_finish = '';
            this.reportSurveyForm.bisa_pasang = 'YA';
            this.reportSurveyForm.team_survey = [];
            this.reportSurveyForm.perangkat_list = [];
            this.reportSurveyForm.foto_mapping_preview = null;
            this.reportSurveyModalOpen = true;

            try {
                const res = await fetch(`/teknik/api/survey-instalasi/${nomorInternet}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data.team_survey && data.team_survey.length > 0) {
                        this.reportSurveyForm.team_survey = data.team_survey;
                    }
                    if (data.perangkat && data.perangkat.length > 0) {
                        this.reportSurveyForm.perangkat_list = data.perangkat;
                    }
                    if (data.register) {
                        if (data.register.nama_pelanggan) this.reportSurveyForm.nama_pelanggan = data.register.nama_pelanggan;
                    }
                    if (data.instalasi) {
                        if (data.instalasi.survey_date_finish) this.reportSurveyForm.survey_date_finish = data.instalasi.survey_date_finish.substring(0, 10);
                        if (data.instalasi.survey_note_finish) this.reportSurveyForm.survey_note_finish = data.instalasi.survey_note_finish;
                        if (data.instalasi.foto_peta || data.instalasi.doc_survey) {
                            this.reportSurveyForm.foto_mapping_preview = `/uploads/registrasi/${data.instalasi.foto_peta || data.instalasi.doc_survey}`;
                        }
                    }
                }
            } catch(e) {
                console.error(e);
            } finally {
                this.isLoadingWorkflow = false;
            }
        },

        async openScheduleInstalasiModal(nomorInternet, namaPelanggan) {
            this.isLoadingWorkflow = true;
            this.instalasiForm.nomor_internet = nomorInternet;
            this.instalasiForm.nama_pelanggan = namaPelanggan || '';
            this.instalasiForm.note_request = 'Permintaan instalasi standar';
            this.instalasiForm.instalasi_date_start = '{{ date('Y-m-d') }}';
            this.instalasiForm.instalasi_time = this.allTimeJobs[1] ? this.allTimeJobs[1].time_job : (this.allTimeJobs[0] ? this.allTimeJobs[0].time_job : '');
            this.instalasiForm.instalasi_note = '';
            this.instalasiForm.team_instalasi = [];
            this.instalasiForm.perangkat_list = [];
            this.instalasiForm.foto_mapping_preview = null;
            this.scheduleInstalasiModalOpen = true;

            try {
                const res = await fetch(`/teknik/api/survey-instalasi/${nomorInternet}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data.team_instalasi && data.team_instalasi.length > 0) {
                        this.instalasiForm.team_instalasi = data.team_instalasi;
                    } else if (data.team_survey && data.team_survey.length > 0) {
                        this.instalasiForm.team_instalasi = data.team_survey;
                    }
                    if (data.perangkat && data.perangkat.length > 0) {
                        this.instalasiForm.perangkat_list = data.perangkat;
                    }
                    if (data.register) {
                        if (data.register.nama_pelanggan) this.instalasiForm.nama_pelanggan = data.register.nama_pelanggan;
                    }
                    if (data.instalasi) {
                        if (data.instalasi.instalasi_date_start) this.instalasiForm.instalasi_date_start = data.instalasi.instalasi_date_start.substring(0, 10);
                        if (data.instalasi.instalasi_time) this.instalasiForm.instalasi_time = data.instalasi.instalasi_time;
                        if (data.instalasi.instalasi_note) this.instalasiForm.instalasi_note = data.instalasi.instalasi_note;
                        if (data.instalasi.foto_peta || data.instalasi.doc_instalasi) {
                            this.instalasiForm.foto_mapping_preview = `/uploads/registrasi/${data.instalasi.foto_peta || data.instalasi.doc_instalasi}`;
                        }
                    }
                }
            } catch(e) {
                console.error(e);
            } finally {
                this.isLoadingWorkflow = false;
            }
        },

        async openReportInstalasiModal(nomorInternet, namaPelanggan) {
            this.isLoadingWorkflow = true;
            this.reportInstalasiForm.nomor_internet = nomorInternet;
            this.reportInstalasiForm.nama_pelanggan = namaPelanggan || '';
            this.reportInstalasiForm.is_reschedule = false;
            this.reportInstalasiForm.instalasi_date_finish = '{{ date('Y-m-d') }}';
            this.reportInstalasiForm.instalasi_note_finish = '';
            this.reportInstalasiForm.team_instalasi = [];
            this.reportInstalasiForm.perangkat_list = [];
            this.reportInstalasiForm.foto_mapping_preview = null;
            this.reportInstalasiModalOpen = true;

            try {
                const res = await fetch(`/teknik/api/survey-instalasi/${nomorInternet}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data.team_instalasi && data.team_instalasi.length > 0) {
                        this.reportInstalasiForm.team_instalasi = data.team_instalasi;
                    } else if (data.team_survey && data.team_survey.length > 0) {
                        this.reportInstalasiForm.team_instalasi = data.team_survey;
                    }
                    if (data.perangkat && data.perangkat.length > 0) {
                        this.reportInstalasiForm.perangkat_list = data.perangkat;
                    }
                    if (data.register) {
                        if (data.register.nama_pelanggan) this.reportInstalasiForm.nama_pelanggan = data.register.nama_pelanggan;
                    }
                    if (data.instalasi) {
                        if (data.instalasi.instalasi_date_finish) this.reportInstalasiForm.instalasi_date_finish = data.instalasi.instalasi_date_finish.substring(0, 10);
                        if (data.instalasi.instalasi_note_finish) this.reportInstalasiForm.instalasi_note_finish = data.instalasi.instalasi_note_finish;
                        if (data.instalasi.foto_peta || data.instalasi.doc_instalasi) {
                            this.reportInstalasiForm.foto_mapping_preview = `/uploads/registrasi/${data.instalasi.foto_peta || data.instalasi.doc_instalasi}`;
                        }
                    }
                }
            } catch(e) {
                console.error(e);
            } finally {
                this.isLoadingWorkflow = false;
            }
        },

        openRequestAktivasiModal(nomorInternet, namaPelanggan) {
            this.aktivasiForm.nomor_internet = nomorInternet;
            this.aktivasiForm.nama_pelanggan = namaPelanggan || '';
            this.aktivasiForm.catatan_aktivasi = 'Request aktivasi layanan pelanggan baru An/ ' + (namaPelanggan || '') + ' (' + nomorInternet + ') ke tim NOC';
            this.requestAktivasiModalOpen = true;
        },

        // Modal State & Step Wizard
        modalOpen: false,
        currentStep: 1,
        isEditMode: false,
        editNomorInternet: '',
        isLoadingEdit: false,
        
        // Batal Pasang Modal State
        batalModalOpen: false,
        batalNomorInternet: '',
        batalNamaPelanggan: '',
        batalKategori: '14',
        batalAlasan: '',

        // Billing Detail Modal State
        billingModalOpen: false,
        billingLoading: false,
        billingData: {
            nama_pelanggan: '',
            nomor_internet: '',
            status: '',
            method: '',
            subtotal: 0,
            tax: 0,
            total: 0,
            items: []
        },

        async openBillingModal(nomorInternet) {
            this.billingLoading = true;
            this.billingModalOpen = true;
            try {
                const res = await fetch('/teknik/api/billing/' + nomorInternet);
                this.billingData = await res.json();
            } catch (e) {
                console.error('Error fetching billing:', e);
            } finally {
                this.billingLoading = false;
            }
        },

        openBatalModal(nomor, nama) {
            this.batalNomorInternet = nomor;
            this.batalNamaPelanggan = nama;
            this.batalKategori = '14';
            this.batalAlasan = '';
            this.batalModalOpen = true;
        },
        
        // Form Reactive State
        isCorporate: false,
        sameAsKtp: false,
        
        // Form Fields for Step 1 Validation
        nikPenduduk: '',
        namaPelanggan: '',
        jenisKelamin: '1',
        tanggalLahir: '',
        pic: '',
        email: '',
        nomorHp: '',
        nomorHp2: '',
        
        // Bandwidth packages
        selectedLayanan: '',
        selectedPaket: '',
        paketList: [],
        loadingPaket: false,
        
        // Wilayah KTP
        provinsiKtp: '',
        kotaKtpList: [],
        kotaKtp: '',
        kecamatanKtpList: [],
        kecamatanKtp: '',
        kelurahanKtpList: [],
        kelurahanKtp: '',
        rtKtp: '',
        rwKtp: '',
        alamatKtp: '',
        
        // Wilayah Pemasangan
        jenisBangunan: '',
        nomorBangunan: '',
        groupLayanan: 'MEDIANET',
        provinsiPasang: '',
        kotaPasangList: [],
        kotaPasang: '',
        kecamatanPasangList: [],
        kecamatanPasang: '',
        kelurahanPasangList: [],
        kelurahanPasang: '',
        rtPasang: '',
        rwPasang: '',
        alamatPasang: '',
        lonLat: '',
        locMaps: '',
        noteRequest: '',
        namaSales: '',
        
        // Image Previews
        fotoKtpPreview: null,
        fotoRumahPreview: null,

        // Validation error message
        stepError: '',

        openNewModal() {
            this.isEditMode = false;
            this.editNomorInternet = '{{ $suggestedNomorInternet }}';
            this.resetForm();
            this.modalOpen = true;
        },

        async openEditModal(nomorInternet) {
            this.isLoadingEdit = true;
            this.isEditMode = true;
            this.editNomorInternet = nomorInternet;
            this.currentStep = 1;
            this.stepError = '';
            this.modalOpen = true;

            try {
                const res = await fetch(`/teknik/api/pendaftaran/${nomorInternet}`);
                const data = await res.json();
                if (data.register) {
                    const r = data.register;
                    this.nikPenduduk = r.nik_penduduk || '';
                    this.namaPelanggan = r.nama_pelanggan || '';
                    this.jenisKelamin = r.jenis_kelamin ? String(r.jenis_kelamin) : '1';
                    this.tanggalLahir = r.tanggal_lahir || '';
                    this.isCorporate = Boolean(r.pic);
                    this.pic = r.pic || '';
                    this.email = r.email === '-' ? '' : (r.email || '');
                    this.nomorHp = r.nomor_hp || '';
                    this.nomorHp2 = r.nomor_hp_2 || '';
                    this.rtKtp = r.rt_ktp || '';
                    this.rwKtp = r.rw_ktp || '';
                    this.alamatKtp = r.alamat_ktp || '';

                    // Set Wilayah KTP hierarchy
                    if (data.wilayah_ktp) {
                        this.provinsiKtp = data.wilayah_ktp.kode_wilayah_provinsi;
                        await this.fetchKotaKtp();
                        this.kotaKtp = data.wilayah_ktp.kode_wilayah_kota;
                        await this.fetchKecamatanKtp();
                        this.kecamatanKtp = data.wilayah_ktp.kode_wilayah_kecamatan;
                        await this.fetchKelurahanKtp();
                        this.kelurahanKtp = data.wilayah_ktp.kode_wilayah_kelurahan;
                    }

                    this.fotoKtpPreview = r.foto_ktp ? `/uploads/registrasi/${r.foto_ktp}` : null;
                    this.fotoRumahPreview = r.foto_rumah ? `/uploads/registrasi/${r.foto_rumah}` : null;

                    // Step 2 Fields
                    this.jenisBangunan = r.jenis_bangunan || '';
                    this.nomorBangunan = r.nomor_bangunan || '';
                    this.groupLayanan = r.group_layanan || 'MEDIANET';
                    this.selectedLayanan = r.kode_kategori_bandwith || '';
                    if (this.selectedLayanan) {
                        await this.fetchPaket();
                        this.selectedPaket = r.kode_bandwith || '';
                    }

                    // Set Wilayah Pasang hierarchy
                    if (data.wilayah_pasang) {
                        this.provinsiPasang = data.wilayah_pasang.kode_wilayah_provinsi;
                        await this.fetchKotaPasang();
                        this.kotaPasang = data.wilayah_pasang.kode_wilayah_kota;
                        await this.fetchKecamatanPasang();
                        this.kecamatanPasang = data.wilayah_pasang.kode_wilayah_kecamatan;
                        await this.fetchKelurahanPasang();
                        this.kelurahanPasang = data.wilayah_pasang.kode_wilayah_kelurahan;
                    }

                    this.rtPasang = r.rt_pasang || '';
                    this.rwPasang = r.rw_pasang || '';
                    this.alamatPasang = r.alamat_pasang || r.alamat_p || '';
                    this.lonLat = r.lon_lat || '';
                    this.locMaps = r.loc_maps || '';
                    this.noteRequest = r.note_request || '';
                    this.namaSales = r.nama_sales || '';
                }
            } catch (e) {
                console.error('Error fetching detail:', e);
            } finally {
                this.isLoadingEdit = false;
            }
        },

        // Step 1 Validation
        nextToStep2() {
            this.stepError = '';
            if (!this.nikPenduduk.trim()) {
                this.stepError = 'NIK Penduduk wajib diisi!';
                return;
            }
            if (!this.namaPelanggan.trim()) {
                this.stepError = 'Nama Pelanggan wajib diisi!';
                return;
            }
            if (!this.nomorHp.trim()) {
                this.stepError = 'Nomor Handphone wajib diisi!';
                return;
            }
            this.currentStep = 2;
        },

        // Method: Fetch Paket
        async fetchPaket() {
            if (!this.selectedLayanan) {
                this.paketList = [];
                return;
            }
            this.loadingPaket = true;
            try {
                const res = await fetch(`/teknik/api/paket/${this.selectedLayanan}`);
                this.paketList = await res.json();
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingPaket = false;
            }
        },

        // Method: Fetch Kota (KTP)
        async fetchKotaKtp() {
            this.kotaKtpList = [];
            this.kecamatanKtpList = [];
            this.kelurahanKtpList = [];
            if (!this.provinsiKtp) return;
            const res = await fetch(`/teknik/api/wilayah/kota/${this.provinsiKtp}`);
            this.kotaKtpList = await res.json();
        },

        // Method: Fetch Kecamatan (KTP)
        async fetchKecamatanKtp() {
            this.kecamatanKtpList = [];
            this.kelurahanKtpList = [];
            if (!this.kotaKtp) return;
            const res = await fetch(`/teknik/api/wilayah/kecamatan/${this.kotaKtp}`);
            this.kecamatanKtpList = await res.json();
        },

        // Method: Fetch Kelurahan (KTP)
        async fetchKelurahanKtp() {
            this.kelurahanKtpList = [];
            if (!this.kecamatanKtp) return;
            const res = await fetch(`/teknik/api/wilayah/kelurahan/${this.kecamatanKtp}`);
            this.kelurahanKtpList = await res.json();
        },

        // Method: Fetch Kota (Pasang)
        async fetchKotaPasang() {
            this.kotaPasangList = [];
            this.kecamatanPasangList = [];
            this.kelurahanPasangList = [];
            if (!this.provinsiPasang) return;
            const res = await fetch(`/teknik/api/wilayah/kota/${this.provinsiPasang}`);
            this.kotaPasangList = await res.json();
        },

        // Method: Fetch Kecamatan (Pasang)
        async fetchKecamatanPasang() {
            this.kecamatanPasangList = [];
            this.kelurahanPasangList = [];
            if (!this.kotaPasang) return;
            const res = await fetch(`/teknik/api/wilayah/kecamatan/${this.kotaPasang}`);
            this.kecamatanPasangList = await res.json();
        },

        // Method: Fetch Kelurahan (Pasang)
        async fetchKelurahanPasang() {
            this.kelurahanPasangList = [];
            if (!this.kecamatanPasang) return;
            const res = await fetch(`/teknik/api/wilayah/kelurahan/${this.kecamatanPasang}`);
            this.kelurahanPasangList = await res.json();
        },

        // Sync KTP to Pemasangan
        syncKtpToPasang() {
            if (this.sameAsKtp) {
                this.provinsiPasang = this.provinsiKtp;
                this.kotaPasangList = [...this.kotaKtpList];
                this.kotaPasang = this.kotaKtp;
                this.kecamatanPasangList = [...this.kecamatanKtpList];
                this.kecamatanPasang = this.kecamatanKtp;
                this.kelurahanPasangList = [...this.kelurahanKtpList];
                this.kelurahanPasang = this.kelurahanKtp;
                this.rtPasang = this.rtKtp;
                this.rwPasang = this.rwKtp;
                this.alamatPasang = this.alamatKtp;
            }
        },

        // Preview Image helper
        handleFilePreview(e, type) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    if (type === 'ktp') this.fotoKtpPreview = event.target.result;
                    if (type === 'rumah') this.fotoRumahPreview = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        // Reset Form
        resetForm() {
            this.currentStep = 1;
            this.stepError = '';
            this.nikPenduduk = '';
            this.namaPelanggan = '';
            this.jenisKelamin = '1';
            this.tanggalLahir = '';
            this.isCorporate = false;
            this.pic = '';
            this.email = '';
            this.nomorHp = '';
            this.nomorHp2 = '';
            this.provinsiKtp = '';
            this.kotaKtp = '';
            this.kecamatanKtp = '';
            this.kelurahanKtp = '';
            this.rtKtp = '';
            this.rwKtp = '';
            this.alamatKtp = '';
            this.fotoKtpPreview = null;
            this.fotoRumahPreview = null;
            this.jenisBangunan = '';
            this.nomorBangunan = '';
            this.groupLayanan = 'MEDIANET';
            this.selectedLayanan = '';
            this.selectedPaket = '';
            this.sameAsKtp = false;
            this.provinsiPasang = '';
            this.kotaPasang = '';
            this.kecamatanPasang = '';
            this.kelurahanPasang = '';
            this.rtPasang = '';
            this.rwPasang = '';
            this.alamatPasang = '';
            this.lonLat = '';
            this.locMaps = '';
            this.noteRequest = '';
            this.namaSales = '';
        }
    };
}
</script>
@endsection
