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
}" class="space-y-5">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <span>IMS</span>
        <span>&gt;</span>
        <span class="text-blue-500 font-semibold">Suspend</span>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (EXACT MATCHING SCREENSHOT)                       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/10">
        <form method="GET" action="{{ route('teknik.permintaan.suspend') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- 1. Dropdown Semua Layanan -->
            <div class="lg:col-span-3">
                <select name="layanan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="lg:col-span-2">
                <input type="text" 
                       name="wilayah" 
                       value="{{ request('wilayah') }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="lg:col-span-2">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>(1011) Request</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>(1012) Suspend</option>
                    <option value="18" {{ request('status') === '18' ? 'selected' : '' }}>(1018) Req. Unsuspend</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>(1013) Un Suspend</option>
                    <option value="14" {{ request('status') === '14' ? 'selected' : '' }}>(1014) Cancel Suspend</option>
                </select>
            </div>

            <!-- 5. Action Buttons (Reset & Cari) -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <a href="{{ route('teknik.permintaan.suspend') }}" 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer"
                   title="Reset Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>

                <button type="submit" 
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer"
                        title="Cari / Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Cari</span>
                </button>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
        <a href="{{ route('teknik.permintaan.suspend', ['status' => '11']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Request : {{ $countReq }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('teknik.permintaan.suspend', ['status' => '12']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Suspend : {{ $countSusp }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <a href="{{ route('teknik.permintaan.suspend', ['status' => '18']) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-rose-400 to-pink-500 hover:from-rose-500 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Req. Unsuspend : {{ $countReqUn }} User</span>
            <svg class="w-4 h-4 opacity-70 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>
    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF SUSPENDED CUSTOMERS WITH APPROVE / CANCEL ACTIONS       -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div>
                show <span class="font-bold text-slate-800 dark:text-slate-200">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-800 dark:text-slate-200">{{ $suspends->total() }}</strong> User
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-5">Customer</th>
                        <th class="py-3.5 px-5">Alasan Suspend</th>
                        <th class="py-3.5 px-5">State</th>
                        <th class="py-3.5 px-5 text-center min-w-[140px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($suspends as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            
                            <!-- 1. Customer Column -->
                            <td class="py-4 px-5 align-top">
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline tracking-wide text-xs inline-block"
                                   title="Buka Profile Pelanggan">
                                    {{ $item->nomor_internet }}
                                </a>

                                <div class="font-bold text-slate-800 dark:text-slate-200 uppercase text-xs mt-0.5">
                                    <span>{{ $item->nama_pelanggan }}</span>
                                    <span class="text-slate-500 font-normal">
                                        ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>

                                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium uppercase mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }}
                                    @if($item->nominal_bandwith)
                                        <span>{{ $item->nominal_bandwith }} Mbps</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 2. Alasan Suspend Column -->
                            <td class="py-4 px-5 align-top text-slate-700 dark:text-slate-300 text-xs">
                                {{ $item->desc_suspend ?: ($item->note_suspend ?? 'Melewati batas pembayaran yang telah ditentukan') }}
                            </td>

                            <!-- 3. State Column -->
                            <td class="py-4 px-5 align-top">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold font-mono tracking-wide
                                    @if(in_array($item->status_suspend, ['11']))
                                        bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30
                                    @elseif(in_array($item->status_suspend, ['12']))
                                        bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30
                                    @elseif(in_array($item->status_suspend, ['18']))
                                        bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30
                                    @elseif(in_array($item->status_suspend, ['13']))
                                        bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30
                                    @else
                                        bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                                    @endif">
                                    (10{{ $item->status_suspend }}) {{ $item->desc_status_suspend ?: 'Request' }}
                                </span>
                            </td>

                            <!-- 4. Action Column (Contextual Indicators based on State) -->
                            <td class="py-4 px-5 align-top text-center">
                                @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                                    @if($item->status_suspend == '11')
                                        <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5">
                                            <button type="button" 
                                                    @click="openApproveModal('suspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold shadow-md shadow-blue-500/20 transition cursor-pointer"
                                                    title="Setujui Permintaan Suspend (Eksekusi Isolir)">
                                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Approve</span>
                                            </button>

                                            <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (Cancel) suspend pelanggan {{ $item->nomor_internet }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                        title="Batalkan Permintaan Suspend">
                                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Canceled</span>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($item->status_suspend == '18')
                                        <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5">
                                            <button type="button" 
                                                    @click="openApproveModal('unsuspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer"
                                                    title="Setujui Buka Isolir">
                                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Approve Buka</span>
                                            </button>

                                            <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan unsuspend pelanggan {{ $item->nomor_internet }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[11px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                        title="Tolak Unsuspend">
                                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Canceled</span>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($item->status_suspend == '12')
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[11px] font-bold">
                                            <span>Terisolir Aktif</span>
                                        </div>
                                    @elseif($item->status_suspend == '17')
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] font-bold">
                                            <span>Isolir Dibuka</span>
                                        </div>
                                    @elseif(in_array($item->status_suspend, ['13', '19']))
                                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-500/20 text-[11px] font-medium">
                                            <span>Dibatalkan</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-[11px] font-medium border border-slate-200 dark:border-slate-700/60">
                                        <span>View Only</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-slate-400 text-xs">
                                Tidak ada data suspend layanan yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
