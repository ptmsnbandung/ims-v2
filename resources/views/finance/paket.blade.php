@extends('layouts.app')

@section('title', 'Master Paket Internet & Layanan Bandwidth - Finance IMS')
@section('page_title', 'Master Paket Internet')

@section('content')
<div class="space-y-6"
     x-data="{
        modalOpen: false,
        isEdit: false,
        deleteModalOpen: false,
        paketToDelete: null,
        deleteActionUrl: '',

        form: {
            kode_bandwith: '',
            nama_bandwith: '',
            kode_kategori_bandwith: 'KB01',
            nominal_bandwith: 50,
            harga_bandwith: 0,
            peruntukan: ['RUMAH-KANTOR'],
            status_aktif: 1
        },

        openCreateModal() {
            this.isEdit = false;
            this.form = {
                kode_bandwith: '',
                nama_bandwith: '',
                kode_kategori_bandwith: '{{ $kategoriList->first()->kode_kategori_bandwith ?? 'KB01' }}',
                nominal_bandwith: 50,
                harga_bandwith: 0,
                peruntukan: ['RUMAH-KANTOR'],
                status_aktif: 1
            };
            this.modalOpen = true;
        },

        openEditModal(paket) {
            this.isEdit = true;
            let peruntukanArr = [];
            if (paket.peruntukan_bangunan) {
                peruntukanArr = paket.peruntukan_bangunan.split(',').map(s => s.trim());
            } else if (paket.kategori_bangunan) {
                peruntukanArr = [paket.kategori_bangunan];
            }

            this.form = {
                kode_bandwith: paket.kode_bandwith,
                nama_bandwith: paket.nama_bandwith || ('Paket ' + paket.nominal_bandwith + ' Mbps'),
                kode_kategori_bandwith: paket.kode_kategori_bandwith || 'KB01',
                nominal_bandwith: paket.nominal_bandwith,
                harga_bandwith: parseFloat(paket.harga_bandwith) || 0,
                peruntukan: peruntukanArr.length > 0 ? peruntukanArr : ['RUMAH-KANTOR'],
                status_aktif: (paket.disable == 0) ? 1 : 0
            };
            this.modalOpen = true;
        },

        togglePeruntukan(item) {
            const index = this.form.peruntukan.indexOf(item);
            if (index > -1) {
                if (this.form.peruntukan.length > 1) {
                    this.form.peruntukan.splice(index, 1);
                }
            } else {
                this.form.peruntukan.push(item);
            }
        },

        confirmDelete(kode, name) {
            this.paketToDelete = { kode: kode, name: name };
            this.deleteActionUrl = '{{ url('/finance/paket') }}/' + encodeURIComponent(kode) + '/delete';
            this.deleteModalOpen = true;
        }
     }">

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold shadow-lg shadow-emerald-500/5">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold shadow-lg shadow-rose-500/5">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER (MATCHING USER SCREENSHOT)                -->
    <!-- =================================================================== -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-950 via-slate-900 to-indigo-950 border border-blue-500/20 p-5 sm:p-6 shadow-2xl backdrop-blur-xl">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 relative z-10">
            <div class="flex items-start sm:items-center gap-3.5">
                <!-- Icon Wifi Signal -->
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 border border-blue-400/30 text-blue-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-white tracking-tight">
                        Master Paket Internet & Layanan Bandwidth
                    </h2>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Manajemen struktur paket internet, profil kecepatan (speed tier), alokasi kategori, dan tarif langganan bulanan.
                    </p>
                    
                    <!-- Stats Badges Bar -->
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                            </svg>
                            <span>Kategori: {{ $totalKategori }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <span class="text-amber-400">✦</span>
                            <span>Total Paket: {{ $totalPaket }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Aktif: {{ $totalAktif }}</span>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <span class="text-rose-400">🚀</span>
                            <span>Speed: {{ $minSpeed }} - {{ $maxSpeed }} Mbps</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Action Button -->
            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5 cursor-pointer">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>+ Tambah Paket Baru</span>
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. FILTER PILL TABS PER PERUNTUKAN BANGUNAN (MATCHING MOCKUP)      -->
    <!-- =================================================================== -->
    <div class="flex items-center justify-center overflow-x-auto py-1">
        <div class="inline-flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-xl backdrop-blur-xl max-w-full">
            
            <!-- All -->
            <a href="{{ route('finance.paket', array_merge(request()->query(), ['bangunan' => 'all'])) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition {{ $selectedBangunan === 'all' || empty($selectedBangunan) ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                <span>Semua Bangunan</span>
            </a>

            <!-- Each Building Category -->
            @foreach($buildingTypes as $bKey => $bLabel)
                @php $cnt = $buildingCounts[$bKey] ?? 0; @endphp
                <a href="{{ route('finance.paket', array_merge(request()->query(), ['bangunan' => $bKey])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $selectedBangunan === $bKey ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <span>{{ $bLabel }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $selectedBangunan === $bKey ? 'bg-white/20 text-white' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' }}">
                        {{ $cnt }}
                    </span>
                </a>
            @endforeach

        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE MASTER PAKET (DARK COMMAND CENTER THEME)                   -->
    <!-- =================================================================== -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl shadow-black/20 overflow-hidden backdrop-blur-xl">
        
        <!-- Search Bar Top Right -->
        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-b border-slate-800 bg-slate-900/60">
            <form method="GET" action="{{ route('finance.paket') }}" class="relative w-full sm:w-80">
                @if($selectedBangunan !== 'all')
                    <input type="hidden" name="bangunan" value="{{ $selectedBangunan }}">
                @endif
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Search paket / kode / kecepatan..." 
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            </form>
        </div>

        <!-- Table Responsive Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-950/80 text-slate-300 text-[11px] font-bold uppercase tracking-wider border-b border-slate-800">
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>KODE PAKET</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">NAMA PAKET</th>
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>KATEGORI BANDWIDTH</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>KECEPATAN</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">
                            <div class="flex items-center gap-1.5 cursor-pointer">
                                <span>HARGA BULANAN</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3.5 px-5">PERUNTUKAN BANGUNAN</th>
                        <th class="py-3.5 px-5 text-center">AKTIF</th>
                        <th class="py-3.5 px-5 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 bg-slate-900/40 text-slate-200">
                    @forelse($pakets as $paket)
                        @php
                            $katName = $paket->nama_kategori_bandwith ?: ($paket->alias_nama_kategori ?: 'BROADBAND');
                            $peruntukanList = explode(',', $paket->peruntukan_bangunan ?: 'RUMAH-KANTOR');
                        @endphp
                        <tr class="hover:bg-slate-800/50 transition">
                            <!-- 1. Kode Paket -->
                            <td class="py-4 px-5">
                                <span class="font-mono font-bold text-white text-xs tracking-wide">
                                    {{ $paket->kode_bandwith }}
                                </span>
                            </td>

                            <!-- 2. Nama Paket -->
                            <td class="py-4 px-5 font-bold text-white text-xs">
                                {{ $paket->nama_bandwith }}
                            </td>

                            <!-- 3. Kategori Bandwidth -->
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    {{ $katName }}
                                </span>
                            </td>

                            <!-- 4. Kecepatan -->
                            <td class="py-4 px-5 font-mono font-bold text-slate-200">
                                {{ $paket->nominal_bandwith }} Mbps
                            </td>

                            <!-- 5. Harga Bulanan -->
                            <td class="py-4 px-5 font-mono font-bold text-white">
                                Rp {{ number_format((float) ($paket->harga_bandwith ?? 0), 0, ',', '.') }}
                            </td>

                            <!-- 6. Peruntukan Bangunan -->
                            <td class="py-4 px-5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach($peruntukanList as $pTag)
                                        @php $pTag = trim($pTag); @endphp
                                        @if(!empty($pTag))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                {{ $pTag }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>

                            <!-- 7. Status Aktif -->
                            <td class="py-4 px-5 text-center">
                                @if($paket->disable == 0)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 shadow-sm" title="Paket Aktif">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-800 border border-slate-700 text-slate-500 shadow-sm" title="Paket Non-Aktif">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                @endif
                            </td>

                            <!-- 8. Actions -->
                            <td class="py-4 px-5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            @click="openEditModal({{ json_encode($paket) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold shadow-md shadow-blue-500/20 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        <span>Edit</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400 text-xs">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                                    </svg>
                                    <span class="font-bold text-slate-300">Tidak ada data paket ditemukan.</span>
                                    <span class="text-[11px] text-slate-500 mt-1">Klik "+ Tambah Paket Baru" untuk membuat struktur paket internet baru.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="px-5 py-4 border-t border-slate-800 bg-slate-950/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-400">
            <div>
                Showing {{ $pakets->firstItem() ?? 0 }} to {{ $pakets->lastItem() ?? 0 }} of {{ $pakets->total() }} results
            </div>
            <div>
                {{ $pakets->links() }}
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. MODAL CREATE & EDIT PAKET (AUTO-UPDATE BILLING NOTICE)           -->
    <!-- =================================================================== -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 sm:p-7 shadow-2xl space-y-6 text-slate-100 my-8"
             @click.away="modalOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h3 class="font-bold text-white text-base" x-text="isEdit ? 'Edit Paket Internet & Tarif' : 'Tambah Paket Internet Baru'"></h3>
                    <p class="text-xs text-slate-400 mt-0.5">Konfigurasi spesifikasi kecepatan, kategori, dan tarif bulanan.</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white transition">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Auto-Update Alert Banner -->
            <div class="p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs flex items-start gap-2.5">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <div>
                    <span class="font-bold block">Sinkronisasi Otomatis ke Pelanggan & Billing:</span>
                    <span class="text-[11px] text-slate-300">Jika harga diubah, seluruh data pelanggan aktif dan invoice tagihan berjalan yang belum lunas akan **otomatis diperbarui** mengikuti harga baru ini.</span>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('finance.paket.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kode Paket -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">Kode Paket <span class="text-rose-500">*</span></label>
                        <input type="text" 
                               name="kode_bandwith" 
                               x-model="form.kode_bandwith" 
                               required
                               placeholder="Contoh: AG0001" 
                               class="w-full px-3.5 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>

                    <!-- Kategori Bandwidth -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">Kategori Bandwidth <span class="text-rose-500">*</span></label>
                        <select name="kode_kategori_bandwith" 
                                x-model="form.kode_kategori_bandwith"
                                required
                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            @foreach($kategoriList as $kat)
                                <option value="{{ $kat->kode_kategori_bandwith }}">
                                    {{ $kat->nama_kategori_bandwith }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Nama Paket -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-300">Nama Paket <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="nama_bandwith" 
                           x-model="form.nama_bandwith" 
                           required
                           placeholder="Contoh: Paket 75 Mbps" 
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kecepatan (Mbps) -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">Kecepatan (Mbps) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" 
                                   name="nominal_bandwith" 
                                   x-model="form.nominal_bandwith" 
                                   required
                                   min="1"
                                   placeholder="75" 
                                   class="w-full pl-3.5 pr-12 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            <span class="absolute right-3.5 top-2 text-xs text-slate-400 font-bold">Mbps</span>
                        </div>
                    </div>

                    <!-- Harga Bulanan (Rp) -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">Harga Bulanan (Rp) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2 text-xs text-slate-400 font-bold">Rp</span>
                            <input type="number" 
                                   name="harga_bandwith" 
                                   x-model="form.harga_bandwith" 
                                   required
                                   min="0"
                                   step="1"
                                   placeholder="3500000" 
                                   class="w-full pl-10 pr-3.5 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white font-mono font-bold text-amber-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        </div>
                    </div>
                </div>

                <!-- Peruntukan Bangunan (Multi-selection tags) -->
                <div class="space-y-2 pt-1">
                    <label class="block text-xs font-bold text-slate-300">Peruntukan Bangunan</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($buildingTypes as $bKey => $bLabel)
                            <button type="button" 
                                    @click="togglePeruntukan('{{ $bKey }}')"
                                    :class="form.peruntukan.includes('{{ $bKey }}') ? 'bg-blue-600 text-white border-blue-500 shadow-md shadow-blue-500/20' : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-slate-200'"
                                    class="px-3 py-1.5 rounded-xl text-[11px] font-bold border transition cursor-pointer flex items-center gap-1.5">
                                <span x-show="form.peruntukan.includes('{{ $bKey }}')">✓</span>
                                <span>{{ $bLabel }}</span>
                            </button>
                        @endforeach
                    </div>
                    <!-- Hidden inputs for peruntukan array -->
                    <template x-for="p in form.peruntukan" :key="p">
                        <input type="hidden" name="peruntukan_bangunan[]" :value="p">
                    </template>
                </div>

                <!-- Status Aktif -->
                <div class="pt-2 flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div>
                        <span class="block text-xs font-bold text-white">Status Paket Aktif</span>
                        <span class="block text-[11px] text-slate-400">Paket aktif dapat dipilih untuk registrasi pelanggan baru.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="status_aktif" value="1" x-model="form.status_aktif" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-800">
                    <button type="button" 
                            @click="modalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/25 transition cursor-pointer">
                        <span x-text="isEdit ? 'Simpan & Update Billing' : 'Tambah Paket Baru'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
