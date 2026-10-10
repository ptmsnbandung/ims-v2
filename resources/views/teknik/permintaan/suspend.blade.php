@extends('layouts.app')

@section('title', 'Permintaan Suspend Layanan - IMS Router')
@section('page_title', 'Permintaan Suspend')

@section('content')
<div x-data="{
    approveModalOpen: false,
    modalType: 'suspend',
    modalKodeSuspend: '',
    modalNomorInternet: '',
    modalNamaPelanggan: '',
    modalPaket: '',
    modalDate: '{{ date('Y-m-d') }}',
    modalSendWa: '1',
    openApproveModal(type, kode, nomor, nama, paket) {
        this.modalType = type;
        this.modalKodeSuspend = kode;
        this.modalNomorInternet = nomor;
        this.modalNamaPelanggan = nama;
        this.modalPaket = paket;
        this.modalDate = '{{ date('Y-m-d') }}';
        this.modalSendWa = '1';
        this.approveModalOpen = true;
    }
}" class="space-y-3.5">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
        <span>IMS</span>
        <span>&gt;</span>
        <span class="text-blue-500 font-semibold">Suspend</span>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (EXACT MATCHING SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-xl p-3 sm:p-3.5 shadow-xs">
        <form method="GET" action="{{ route('teknik.permintaan.suspend') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2 sm:gap-2.5 items-center">
            
            <!-- 1. Dropdown Semua Layanan -->
            <div class="lg:col-span-3">
                <select name="layanan" onchange="this.form.submit()" class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">SEMUA LAYANAN</option>
                    @if(isset($layananList))
                        @foreach($layananList as $lay)
                            <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ strtoupper($lay) }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- 2. Input Nama / Nomor Layanan -->
            <div class="lg:col-span-3">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="NAMA / NOMOR LAYANAN" 
                       class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="lg:col-span-3">
                <input type="text" 
                       name="wilayah" 
                       value="{{ request('wilayah') }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="lg:col-span-2">
                <select name="status" onchange="this.form.submit()" class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>(1011) Request</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>(1012) Suspend</option>
                    <option value="18" {{ request('status') === '18' ? 'selected' : '' }}>(1018) Req. Unsuspend</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>(1013) Un Suspend</option>
                    <option value="14" {{ request('status') === '14' ? 'selected' : '' }}>(1014) Cancel Suspend</option>
                </select>
            </div>

            <!-- 5. Action Buttons (Reset) -->
            <div class="lg:col-span-1 flex items-center gap-1.5">
                <a href="{{ route('teknik.permintaan.suspend') }}" 
                   class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-xs transition cursor-pointer"
                   title="Reset Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 2. STATUS PILL KPI BANNERS (MATCHING SCREENSHOT)                   -->
    <!-- =================================================================== -->
    @php
        $countReq = \Illuminate\Support\Facades\DB::table('view_suspend')->where('status_suspend', '11')->count();
        $countSusp = \Illuminate\Support\Facades\DB::table('view_suspend')->where('status_suspend', '12')->count();
        $countReqUn = \Illuminate\Support\Facades\DB::table('view_suspend')->where('status_suspend', '18')->count();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
        <a href="{{ route('teknik.permintaan.suspend', ['status' => '11']) }}" 
           class="py-2 px-3.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-xs transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Request : {{ $countReq }} User</span>
            <svg class="w-3.5 h-3.5 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('teknik.permintaan.suspend', ['status' => '12']) }}" 
           class="py-2 px-3.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] shadow-xs transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Suspend : {{ $countSusp }} User</span>
            <svg class="w-3.5 h-3.5 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('teknik.permintaan.suspend', ['status' => '18']) }}" 
           class="py-2 px-3.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] shadow-xs transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Req. Unsuspend : {{ $countReqUn }} User</span>
            <svg class="w-3.5 h-3.5 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>
    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF SUSPENDED CUSTOMERS WITH APPROVE / CANCEL ACTIONS       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-xl shadow-xs overflow-hidden">
        
        <div class="px-3.5 py-2.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
            <div>
                show <span class="font-bold text-slate-800 dark:text-slate-200">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-800 dark:text-slate-200">{{ $suspends->total() }}</strong> User
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="ims-desktop-only hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Alasan Suspend</th>
                        <th class="py-2.5 px-3">State</th>
                        <th class="py-2.5 px-3 text-center min-w-[130px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($suspends as $item)
                        @php
                            $rawReason = trim($item->desc_suspend ?: ($item->note_suspend ?? 'Melewati batas pembayaran yang telah ditentukan'));
                            $isPengajuanSementara = str_contains($rawReason, 'PENGAJUAN SUSPEND') || str_contains($rawReason, 'Tanggal Mulai Suspend');
                            $isDirectIsolir = str_contains($rawReason, 'Isolir langsung') || str_contains($rawReason, 'langsung dari NOC');
                            $isTunggakan = str_contains($rawReason, 'batas pembayaran') || str_contains($rawReason, 'Tunggakan');

                            $tglMulai = null;
                            $tglSelesai = null;
                            $alasan = null;
                            $catatan = null;

                            if ($isPengajuanSementara) {
                                if (preg_match('/Tanggal Mulai Suspend\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                                    $tglMulai = trim($m[1]);
                                }
                                if (preg_match('/Estimasi Aktif Kembali\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                                    $tglSelesai = trim($m[1]);
                                }
                                if (preg_match('/Alasan Suspend\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                                    $alasan = trim($m[1]);
                                }
                                if (preg_match('/Catatan Tambahan\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                                    $catatan = trim($m[1]);
                                }
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            
                            <!-- 1. Customer Column -->
                            <td class="py-2 px-3 align-middle">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if(!empty($item->nomor_internet) && $item->nomor_internet !== '-')
                                            <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                               class="inline-flex items-center gap-0.5 font-mono font-bold text-[11px] text-blue-600 dark:text-blue-400 hover:underline"
                                               title="Lihat Profil Pelanggan ({{ $item->nomor_internet }})">
                                                <span>{{ $item->nomor_internet }}</span>
                                                <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                            </a>
                                        @else
                                            <span class="font-mono text-[11px] text-slate-400">-</span>
                                        @endif

                                        @if($item->jenis_kelamin == 1)
                                            <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full text-[8.5px] font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300" title="Laki-laki">L</span>
                                        @elseif($item->jenis_kelamin == 2)
                                            <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full text-[8.5px] font-bold bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300" title="Perempuan">P</span>
                                        @endif
                                    </div>

                                    <div>
                                        @if(!empty($item->nomor_internet) && $item->nomor_internet !== '-')
                                            <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                               class="font-bold text-slate-800 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400 text-[11px] uppercase tracking-tight transition-colors line-clamp-1">
                                                {{ $item->nama_pelanggan }}
                                            </a>
                                        @else
                                            <span class="font-bold text-slate-800 dark:text-slate-100 text-[11px] uppercase tracking-tight line-clamp-1">
                                                {{ $item->nama_pelanggan }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="inline-flex items-center gap-1 text-[9.5px] text-slate-500 dark:text-slate-400">
                                        <span class="px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[9px] font-semibold uppercase">
                                            {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }}
                                        </span>
                                        @if($item->nominal_bandwith)
                                            <span class="text-slate-600 dark:text-slate-300 font-bold">
                                                {{ $item->nominal_bandwith }} Mbps
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Alasan Suspend Column -->
                            <td class="py-2 px-3 align-middle">
                                @if($isPengajuanSementara)
                                    <div class="p-1.5 rounded-md bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/30 text-[10px] space-y-1 max-w-xl">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                <svg class="w-2.5 h-2.5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                <span>Pengajuan Suspend Sementara</span>
                                            </span>
                                            @if($alasan)
                                                <span class="font-medium text-slate-800 dark:text-slate-100 text-[10.5px]">
                                                    {{ $alasan }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($tglMulai || $tglSelesai)
                                            <div class="flex items-center gap-1 text-[9.5px] text-slate-600 dark:text-slate-400">
                                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                                                <span>
                                                    Periode: <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $tglMulai ?: '-' }}</span> s/d <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $tglSelesai ?: '-' }}</span>
                                                </span>
                                            </div>
                                        @endif

                                        @if($catatan && $catatan !== '-')
                                            <div class="text-[9.5px] text-slate-600 dark:text-slate-400 flex items-start gap-1 bg-white/70 dark:bg-slate-900/60 px-1.5 py-0.5 rounded border border-amber-100 dark:border-amber-900/30">
                                                <span class="text-slate-400 font-semibold shrink-0">Catatan:</span>
                                                <span class="italic text-slate-700 dark:text-slate-300">{{ $catatan }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($isDirectIsolir)
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 border border-blue-200/50 dark:border-blue-800/40">
                                            <svg class="w-3 h-3 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                                            <span>Suspend / Isolir Langsung dari NOC / Teknik</span>
                                        </span>
                                    </div>
                                @elseif($isTunggakan)
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 border border-rose-200/50 dark:border-rose-800/40">
                                            <svg class="w-3 h-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                            <span>{{ $rawReason }}</span>
                                        </span>
                                    </div>
                                @else
                                    <div class="text-[10.5px] text-slate-700 dark:text-slate-300 font-medium leading-snug max-w-md">
                                        {{ $rawReason }}
                                    </div>
                                @endif
                            </td>

                            <!-- 3. State Column -->
                            <td class="py-2 px-3 align-middle whitespace-nowrap">
                                @if(in_array($item->status_suspend, ['11']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        (1011) Request
                                    </span>
                                @elseif(in_array($item->status_suspend, ['12']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        (1012) Suspend
                                    </span>
                                @elseif(in_array($item->status_suspend, ['18']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                        (1018) Req. Unsuspend
                                    </span>
                                @elseif(in_array($item->status_suspend, ['13']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        (1013) Un Suspend
                                    </span>
                                @elseif(in_array($item->status_suspend, ['14']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        (1014) Cancel Suspend
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        (10{{ $item->status_suspend }}) {{ $item->desc_status_suspend ?: 'Unknown' }}
                                    </span>
                                @endif
                                                       <!-- 4. Action Column (Contextual Indicators based on State) -->
                            <td class="py-2 px-3 align-middle text-center whitespace-nowrap">
                                @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                                    @if($item->status_suspend == '11')
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <button type="button" 
                                                    @click="openApproveModal('suspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                                    title="Setujui Permintaan Suspend (Eksekusi Isolir)">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Approve</span>
                                            </button>

                                            <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (Cancel) suspend pelanggan {{ $item->nomor_internet }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[10px] font-bold border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                                                        title="Batalkan Permintaan Suspend">
                                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Canceled</span>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($item->status_suspend == '18')
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <button type="button" 
                                                    @click="openApproveModal('unsuspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                                    title="Setujui Buka Isolir">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Approve Buka</span>
                                            </button>

                                            <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan unsuspend pelanggan {{ $item->nomor_internet }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[10px] font-bold border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                                                        title="Tolak Unsuspend">
                                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Canceled</span>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($item->status_suspend == '12')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-bold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/50">
                                            <svg class="w-3 h-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            <span>Terisolir Aktif</span>
                                        </span>
                                    @elseif($item->status_suspend == '13')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/50">
                                            <svg class="w-3 h-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0 2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            <span>Layanan Normal</span>
                                        </span>
                                    @elseif($item->status_suspend == '14')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            <span>Dibatalkan</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-[10px] font-medium border border-slate-200 dark:border-slate-700/60">
                                        <span>View Only</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data suspend layanan yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (block md:hidden) -->
        <div class="ims-mobile-only block md:hidden p-3 sm:p-4 space-y-3.5 divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($suspends as $item)
                @php
                    $rawReason = trim($item->desc_suspend ?: ($item->note_suspend ?? 'Melewati batas pembayaran yang telah ditentukan'));
                    $isPengajuanSementara = str_contains($rawReason, 'PENGAJUAN SUSPEND') || str_contains($rawReason, 'Tanggal Mulai Suspend');
                    $isDirectIsolir = str_contains($rawReason, 'Isolir langsung') || str_contains($rawReason, 'langsung dari NOC');
                    $isTunggakan = str_contains($rawReason, 'batas pembayaran') || str_contains($rawReason, 'Tunggakan');

                    $tglMulai = null;
                    $tglSelesai = null;
                    $alasan = null;
                    $catatan = null;

                    if ($isPengajuanSementara) {
                        if (preg_match('/Tanggal Mulai Suspend\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                            $tglMulai = trim($m[1]);
                        }
                        if (preg_match('/Estimasi Aktif Kembali\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                            $tglSelesai = trim($m[1]);
                        }
                        if (preg_match('/Alasan Suspend\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                            $alasan = trim($m[1]);
                        }
                        if (preg_match('/Catatan Tambahan\s*:\s*([^•\n\r]+)/i', $rawReason, $m)) {
                            $catatan = trim($m[1]);
                        }
                    }
                @endphp
                <div class="pt-3.5 first:pt-0 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if(!empty($item->nomor_internet) && $item->nomor_internet !== '-')
                                    <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                       class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded border border-blue-200/80 dark:border-blue-800/60 inline-flex items-center gap-1">
                                        <span>{{ $item->nomor_internet }}</span>
                                    </a>
                                @else
                                    <span class="font-mono text-xs text-slate-400">-</span>
                                @endif

                                @if($item->jenis_kelamin == 1)
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[9px] font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300" title="Laki-laki">L</span>
                                @elseif($item->jenis_kelamin == 2)
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[9px] font-bold bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300" title="Perempuan">P</span>
                                @endif
                            </div>
                            <div class="font-bold text-slate-800 dark:text-slate-100 uppercase text-xs mt-1">
                                {{ $item->nama_pelanggan }}
                            </div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium uppercase mt-0.5">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold">{{ $item->nama_kategori_bandwith ?: 'BROADBAND' }}</span>
                                @if($item->nominal_bandwith)
                                    <span class="font-bold text-slate-600 dark:text-slate-400">• {{ $item->nominal_bandwith }} Mbps</span>
                                @endif
                            </div>
                        </div>

                        <!-- State Pill -->
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold font-mono tracking-wide shrink-0 whitespace-nowrap
                            @if(in_array($item->status_suspend, ['11']))
                                bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30
                            @elseif(in_array($item->status_suspend, ['12']))
                                bg-blue-500/15 text-blue-700 dark:text-blue-400 border border-blue-500/30
                            @elseif(in_array($item->status_suspend, ['18']))
                                bg-rose-500/15 text-rose-700 dark:text-rose-400 border border-rose-500/30
                            @elseif(in_array($item->status_suspend, ['13']))
                                bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30
                            @else
                                bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                            @endif">
                            (10{{ $item->status_suspend }}) {{ $item->desc_status_suspend ?: 'Request' }}
                        </span>
                    </div>

                    <!-- Reason Block -->
                    @if($isPengajuanSementara)
                        <div class="p-2.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/70 dark:border-amber-800/40 text-xs space-y-1.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                    <svg class="w-3 h-3 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span>Pengajuan Suspend Sementara</span>
                                </span>
                                @if($alasan)
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-[11px]">
                                        {{ $alasan }}
                                    </span>
                                @endif
                            </div>

                            @if($tglMulai || $tglSelesai)
                                <div class="text-[10.5px] text-slate-600 dark:text-slate-400">
                                    Periode: <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $tglMulai ?: '-' }}</span> s/d <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $tglSelesai ?: '-' }}</span>
                                </div>
                            @endif

                            @if($catatan && $catatan !== '-')
                                <div class="text-[10.5px] text-slate-600 dark:text-slate-400 italic bg-white/70 dark:bg-slate-900/60 px-2 py-1 rounded border border-amber-100 dark:border-amber-900/30">
                                    Catatan: {{ $catatan }}
                                </div>
                            @endif
                        </div>
                    @elseif($isDirectIsolir)
                        <div class="p-2 rounded-xl bg-blue-50/70 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-800/40 text-[11px] font-semibold text-blue-700 dark:text-blue-300 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                            <span>Suspend / Isolir Langsung dari NOC / Teknik</span>
                        </div>
                    @else
                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200/70 dark:border-slate-800/60 text-xs">
                            <p class="text-slate-700 dark:text-slate-300 text-[11px] leading-relaxed">
                                {{ $rawReason }}
                            </p>
                        </div>
                    @endif

                    @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80">
                            @if($item->status_suspend == '11')
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" 
                                            @click="openApproveModal('suspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                            class="py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center justify-center gap-1">
                                        <span>Approve</span>
                                    </button>
                                    <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Batalkan suspend pelanggan {{ $item->nomor_internet }}?');">
                                        @csrf
                                        <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-rose-600 text-slate-600 dark:text-slate-300 hover:text-white text-xs font-bold transition flex items-center justify-center gap-1">
                                            <span>Cancel</span>
                                        </button>
                                    </form>
                                </div>
                            @elseif($item->status_suspend == '18')
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" 
                                            @click="openApproveModal('unsuspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                            class="py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center justify-center gap-1">
                                        <span>Approve Buka</span>
                                    </button>
                                    <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Tolak unsuspend {{ $item->nomor_internet }}?');">
                                        @csrf
                                        <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-rose-600 text-slate-600 dark:text-slate-300 hover:text-white text-xs font-bold transition flex items-center justify-center gap-1">
                                            <span>Cancel</span>
                                        </button>
                                    </form>
                                </div>
                            @elseif($item->status_suspend == '12')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200/70 dark:border-blue-800/50 text-xs font-bold">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    Terisolir Aktif
                                </span>
                            @elseif($item->status_suspend == '13')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/50 text-xs font-bold">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Layanan Normal
                                </span>
                            @else
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Status: {{ $item->desc_status_suspend ?: 'Dibatalkan' }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    Tidak ada data suspend layanan.
                </div>
            @endforelse
        </div>

        @if($suspends->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                {{ $suspends->links() }}
            </div>
        @endif
    </div>

    @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
    <!-- =================================================================== -->
    <!-- 4. MODAL FORM APPROVE SUSPEND / FORM UNSUSPEND (EXACT DESIGN)       -->
    <!-- =================================================================== -->
    <div x-show="approveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="approveModalOpen = false"
             class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col">
            
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white"
                    x-text="modalType === 'unsuspend' ? 'Form UNsuspend' : 'Form Approve suspend'">
                </h3>
                <button type="button" @click="approveModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <form :action="'{{ url('/noc/suspend') }}/' + modalKodeSuspend + '/approve'" method="POST" class="flex flex-col flex-1">
                @csrf
                <div class="p-5 space-y-4">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800">
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-2.5">Data Pelanggan</h4>
                        <ul class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 list-disc list-inside">
                            <li class="font-bold uppercase text-slate-800 dark:text-white" x-text="modalNamaPelanggan"></li>
                            <li>Nomor Layanan <span class="font-mono font-semibold" x-text="modalNomorInternet"></span></li>
                            <li class="uppercase text-blue-600 dark:text-blue-400 font-medium" x-text="modalPaket"></li>
                        </ul>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                <span x-text="modalType === 'unsuspend' ? 'Finish Suspend' : 'Start Suspend'"></span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   :name="modalType === 'unsuspend' ? 'finish_suspend' : 'start_suspend'" 
                                   required
                                   x-model="modalDate"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Kirim whatsapp ke Pelanggan ? <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <div class="flex items-center gap-6">
                                <label class="inline-flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="send_wa" value="1" x-model="modalSendWa" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 transition">YA</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="send_wa" value="0" x-model="modalSendWa" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-600 transition">TIDAK</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" @click="approveModalOpen = false" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#00bcd4] hover:bg-[#00acc1] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <span>Tutup</span>
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <span>Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
