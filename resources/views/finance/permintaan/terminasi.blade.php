@extends('layouts.app', ['title' => 'Request Terminasi Layanan - Finance'])

@section('page_title', 'Permintaan Terminasi (Putus Berlangganan)')

@section('content')
<div class="space-y-6"
     x-data="{
         createModalOpen: false,
         searchCustomerQuery: '',
         customerResults: [],
         isSearchingCustomer: false,
         selectedCustomer: null,

         async searchCustomer() {
             if (this.searchCustomerQuery.length < 2) {
                 this.customerResults = [];
                 return;
             }
             this.isSearchingCustomer = true;
             try {
                 let res = await fetch('{{ route('finance.api.pelanggan-search') }}?q=' + encodeURIComponent(this.searchCustomerQuery));
                 this.customerResults = await res.json();
             } catch (e) {
                 console.error(e);
             } finally {
                 this.isSearchingCustomer = false;
             }
         },

         selectCustomer(cust) {
             this.selectedCustomer = cust;
             this.searchCustomerQuery = cust.nomor_internet + ' - ' + cust.nama_pelanggan;
             this.customerResults = [];
         },

         resetForm() {
             this.selectedCustomer = null;
             this.searchCustomerQuery = '';
             this.customerResults = [];
         }
     }">


    <!-- Header & Action Bar (Deep Oceanic Teal & Cyan Gradient Matching Dashboard) -->
    <div class="ims-banner relative overflow-hidden rounded-2xl p-5 sm:p-6 shadow-md border border-teal-500/20"
         style="background: linear-gradient(108deg, #032b35 0%, #043f4e 28%, #065b70 60%, #087d94 85%, #009aa9 100%);">
        
        <!-- Subtle Glow Effect -->
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <!-- Top Mini Badges -->
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold bg-white text-slate-800 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-[#00a8b5]"></span>
                        <span>Modul Finance</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-semibold bg-[#04333e]/85 text-emerald-300 border border-teal-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Finance &rarr; NOC / Lapangan</span>
                    </div>
                </div>

                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2" style="color: #FFFFFF !important;">
                    <span>🔌 Permintaan Terminasi (Putus Berlangganan)</span>
                </h2>
                <p class="text-xs sm:text-sm text-[#c6edf3] mt-1 max-w-2xl leading-relaxed" style="color: #C6EDF3 !important;">
                    Pengajuan penutupan layanan &amp; penarikan perangkat ONT/Modem untuk pelanggan yang sudah berhenti berlangganan.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <button type="button"
                        @click="resetForm(); createModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#00b074] hover:bg-[#009b66] text-white text-xs font-bold shadow-md shadow-emerald-950/20 transition cursor-pointer" style="color: #FFFFFF !important;">
                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Request Terminasi</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Request Baru -->
        <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-slate-900/90 border border-amber-200 dark:border-amber-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Menunggu NOC</span>
                <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400 animate-ping"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($count11) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Status (11) Request Baru</div>
        </div>

        <!-- 2. On Collecting -->
        <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-slate-900/90 border border-blue-200 dark:border-blue-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Proses Tarik Perangkat</span>
                <span class="w-2 h-2 rounded-full bg-blue-500 dark:bg-blue-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($count12) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Status (12 / 12.1) Collecting</div>
        </div>

        <!-- 3. Selesai Terminasi -->
        <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-slate-900/90 border border-emerald-200 dark:border-emerald-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Selesai Berhenti</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($count13) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Status (13) Selesai Putus</div>
        </div>

        <!-- 4. Canceled -->
        <div class="p-4 rounded-2xl bg-rose-50/70 dark:bg-slate-900/90 border border-rose-200 dark:border-rose-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider">Batal Terminasi</span>
                <span class="w-2 h-2 rounded-full bg-rose-500 dark:bg-rose-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($count16) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Status (16) Batal</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl shadow-black/5">
        <form method="GET" action="{{ route('finance.permintaan.terminasi') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            <div class="lg:col-span-3">
                <select name="layanan" onchange="this.form.submit()" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">SEMUA LAYANAN</option>
                    @foreach($layananList as $lay)
                        <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ strtoupper($lay) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-5">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari Kode Trx / No. Internet / Nama / Alasan... (Enter)"
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="lg:col-span-3">
                <select name="status" onchange="this.form.submit()" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>(11) Request Baru</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>(12) On Collecting</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>(13) Selesai Terminasi</option>
                    <option value="16" {{ request('status') === '16' ? 'selected' : '' }}>(16) Batal Terminasi</option>
                </select>
            </div>

            <div class="lg:col-span-1 flex items-center">
                <a href="{{ route('finance.permintaan.terminasi') }}"
                   class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-300 dark:border-slate-700 transition">
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xl shadow-black/5">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold">
                        <th class="py-3.5 px-4">KODE / TANGGAL</th>
                        <th class="py-3.5 px-4">PELANGGAN</th>
                        <th class="py-3.5 px-4">PAKET</th>
                        <th class="py-3.5 px-4">ALASAN BERHENTI</th>
                        <th class="py-3.5 px-4 text-center">PENARIKAN PERANGKAT</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                    @forelse($terminasis as $t)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- Kode / Tgl -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-slate-900 dark:text-slate-300">{{ $t->kode_trx_terminasi }}</span>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Tgl: {{ $t->date_create ? substr($t->date_create, 0, 10) : '-' }}</div>
                            </td>

                            <!-- Pelanggan -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $t->nama_pelanggan ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                    No: <a href="{{ route('teknik.pelanggan.profile', $t->nomor_internet) }}" class="text-blue-600 dark:text-blue-400 hover:underline" title="Buka Profile Pelanggan">{{ $t->nomor_internet }}</a>
                                </div>
                                <div class="text-[10px] text-slate-500 max-w-[200px] truncate mt-0.5">{{ $t->alamat_p ?? '-' }}</div>
                            </td>

                            <!-- Paket -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-[11px] border border-slate-200 dark:border-slate-700">
                                    {{ $t->nama_kategori_bandwith ?? 'Broadband' }}
                                </span>
                                <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $t->nominal_bandwith ?? '-' }} Mbps
                                </div>
                            </td>

                            <!-- Alasan -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="text-slate-700 dark:text-slate-300 font-medium">{{ $t->note_termin ?? 'Permintaan berhenti berlangganan' }}</div>
                                @if($t->user_create)
                                    <div class="text-[10px] text-slate-500 mt-0.5">Oleh: {{ $t->user_create }}</div>
                                @endif
                            </td>

                            <!-- Penarikan Perangkat -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->collect_perangkat == '1')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                        &check; Perangkat Ditarik
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                        &bull; Belum Ditarik
                                    </span>
                                @endif
                                @if($t->team_collect)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Tim: {{ $t->team_collect }}</div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->status_terminasi == '11')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                                        <span>Request Baru</span>
                                    </span>
                                @elseif($t->status_terminasi == '12' || $t->status_terminasi == '12.1')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                                        <span>On Collecting</span>
                                    </span>
                                @elseif($t->status_terminasi == '13')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                        <span>Selesai Putus</span>
                                    </span>
                                @elseif($t->status_terminasi == '16')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400"></span>
                                        <span>Batal</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Status {{ $t->status_terminasi }}</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->status_terminasi == '11')
                                    <form action="{{ route('finance.permintaan.terminasi.cancel', $t->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Batalkan permintaan terminasi ini?')">
                                        @csrf
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 dark:text-rose-400 dark:border-rose-500/30 text-[11px] font-bold transition cursor-pointer">
                                            Batalkan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 dark:text-slate-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="text-sm font-medium">Tidak ada data terminasi ditemukan</div>
                                <div class="text-xs mt-1">Klik tombol "+ Request Terminasi" untuk membuat pengajuan baru ke NOC.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($terminasis->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 flex justify-end">
                {{ $terminasis->links() }}
            </div>
        @endif
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL: BUAT REQUEST TERMINASI KE NOC                                    -->
    <!-- ======================================================================= -->
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="createModalOpen = false"
             class="w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-7 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🔌 Ajukan Permintaan Terminasi ke NOC / Lapangan</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Digunakan untuk pelanggan yang sudah berhenti berlangganan (tutup akun & penarikan perangkat).</p>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <form action="{{ route('finance.permintaan.terminasi.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- 1. Search Pelanggan -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Cari Pelanggan (No. Internet / Nama) <span class="text-rose-500 dark:text-rose-400">*</span></label>
                    <input type="text"
                           x-model="searchCustomerQuery"
                           @input.debounce.300ms="searchCustomer()"
                           placeholder="Ketik minimal 2 karakter untuk mencari pelanggan..."
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">

                    <!-- Dropdown Search Results -->
                    <div x-show="customerResults.length > 0"
                         x-cloak
                         class="absolute z-20 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="cust in customerResults" :key="cust.nomor_internet">
                            <div @click="selectCustomer(cust)"
                                 class="p-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer text-xs transition">
                                <div class="font-bold text-slate-900 dark:text-white" x-text="cust.nama_pelanggan"></div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    <span class="font-mono text-blue-600 dark:text-blue-400" x-text="'No: ' + cust.nomor_internet"></span>
                                    <span>&bull;</span>
                                    <span x-text="cust.nama_kategori_bandwith + ' (' + cust.nominal_bandwith + ' Mbps)'"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <input type="hidden" name="nomor_internet" :value="selectedCustomer ? selectedCustomer.nomor_internet : ''" required>
                </div>

                <!-- Info Pelanggan Terpilih -->
                <template x-if="selectedCustomer">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Pelanggan:</span>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="selectedCustomer.nama_pelanggan"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Paket:</span>
                            <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="selectedCustomer.nama_kategori_bandwith + ' (' + selectedCustomer.nominal_bandwith + ' Mbps)'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Alamat Pasang:</span>
                            <span class="text-slate-700 dark:text-slate-300 text-right truncate max-w-[260px]" x-text="selectedCustomer.alamat_p"></span>
                        </div>
                    </div>
                </template>

                <!-- 2. Alasan Berhenti Berlangganan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alasan Berhenti Berlangganan <span class="text-rose-500 dark:text-rose-400">*</span></label>
                    <select name="note_termin" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium mb-2">
                        <option value="">-- Pilih Alasan Utama --</option>
                        <option value="Permintaan Pelanggan (Pindah Rumah / Alamat)">Permintaan Pelanggan (Pindah Rumah / Alamat)</option>
                        <option value="Permintaan Pelanggan (Keberatan Biaya Bulanan / Tarif)">Permintaan Pelanggan (Keberatan Biaya Bulanan / Tarif)</option>
                        <option value="Tunggakan Pembayaran Tidak Diselesaikan">Tunggakan Pembayaran Tidak Diselesaikan</option>
                        <option value="Beralih ke Provider Lain">Beralih ke Provider Lain</option>
                        <option value="Lainnya">Lainnya (Tuliskan di Catatan)</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="createModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="!selectedCustomer"
                            :class="!selectedCustomer ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold shadow-lg shadow-black/25 transition border border-slate-700 cursor-pointer">
                        Kirim Request Terminasi ke NOC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
