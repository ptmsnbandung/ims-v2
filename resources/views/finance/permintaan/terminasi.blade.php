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

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 text-xs font-bold">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-400 hover:text-rose-200 text-xs font-bold">&times;</button>
        </div>
    @endif

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <span>Modul Finance</span>
                <span>&rsaquo;</span>
                <span>Permintaan</span>
                <span>&rsaquo;</span>
                <span class="text-slate-200 font-semibold">Terminasi</span>
            </div>
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <span>🔌 Permintaan Terminasi (Putus Berlangganan)</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-200 font-semibold">Finance &rarr; NOC / Lapangan</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Pengajuan penutupan layanan & penarikan perangkat ONT/Modem untuk pelanggan yang sudah berhenti berlangganan.</p>
        </div>

        <div>
            <button type="button"
                    @click="resetForm(); createModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-slate-700 to-slate-800 hover:from-slate-600 hover:to-slate-700 text-white text-xs font-bold shadow-lg shadow-black/25 transition duration-150 cursor-pointer border border-slate-600">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>+ Request Terminasi</span>
            </button>
        </div>
    </div>

    <!-- 4 KPI Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Request Baru -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Menunggu NOC</span>
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count11) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (11) Request Baru</div>
        </div>

        <!-- 2. On Collecting -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500/10 via-slate-900 to-slate-900 border border-blue-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-400 uppercase tracking-wider">Proses Tarik Perangkat</span>
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count12) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (12 / 12.1) Collecting</div>
        </div>

        <!-- 3. Selesai Terminasi -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-500/10 via-slate-900 to-slate-900 border border-emerald-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Selesai Berhenti</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count13) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (13) Selesai Putus</div>
        </div>

        <!-- 4. Canceled -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-rose-500/10 via-slate-900 to-slate-900 border border-rose-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Batal Terminasi</span>
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count16) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (16) Batal</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xl">
        <form method="GET" action="{{ route('finance.permintaan.terminasi') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            <div class="lg:col-span-3">
                <select name="layanan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA LAYANAN</option>
                    @foreach($layananList as $lay)
                        <option value="{{ $lay }}" {{ request('layanan') === $lay ? 'selected' : '' }}>{{ strtoupper($lay) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-4">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari Kode Trx / No. Internet / Nama / Alasan..."
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-200 placeholder-slate-400 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="lg:col-span-3">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>(11) Request Baru</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>(12) On Collecting</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>(13) Selesai Terminasi</option>
                    <option value="16" {{ request('status') === '16' ? 'selected' : '' }}>(16) Batal Terminasi</option>
                </select>
            </div>

            <div class="lg:col-span-2 flex items-center gap-2">
                <a href="{{ route('finance.permintaan.terminasi') }}"
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-200 text-slate-300 text-xs font-bold transition">
                    <span>Reset</span>
                </a>
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition">
                    <span>Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-400 font-semibold">
                        <th class="py-3.5 px-4">KODE / TANGGAL</th>
                        <th class="py-3.5 px-4">PELANGGAN</th>
                        <th class="py-3.5 px-4">PAKET</th>
                        <th class="py-3.5 px-4">ALASAN BERHENTI</th>
                        <th class="py-3.5 px-4 text-center">PENARIKAN PERANGKAT</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/60 text-slate-300">
                    @forelse($terminasis as $t)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Kode / Tgl -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-slate-300">{{ $t->kode_trx_terminasi }}</span>
                                <div class="text-[11px] text-slate-500 mt-0.5">Tgl: {{ $t->date_create ? substr($t->date_create, 0, 10) : '-' }}</div>
                            </td>

                            <!-- Pelanggan -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white">{{ $t->nama_pelanggan ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">
                                    No: <a href="{{ route('teknik.pelanggan.profile', $t->nomor_internet) }}" class="text-blue-400 hover:underline" title="Buka Profile Pelanggan">{{ $t->nomor_internet }}</a>
                                </div>
                                <div class="text-[10px] text-slate-500 max-w-[200px] truncate mt-0.5">{{ $t->alamat_p ?? '-' }}</div>
                            </td>

                            <!-- Paket -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-semibold text-[11px]">
                                    {{ $t->nama_kategori_bandwith ?? 'Broadband' }}
                                </span>
                                <div class="text-[11px] font-mono text-slate-400 mt-0.5">
                                    {{ $t->nominal_bandwith ?? '-' }} Mbps
                                </div>
                            </td>

                            <!-- Alasan -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="text-slate-300 font-medium">{{ $t->note_termin ?? 'Permintaan berhenti berlangganan' }}</div>
                                @if($t->user_create)
                                    <div class="text-[10px] text-slate-500 mt-0.5">Oleh: {{ $t->user_create }}</div>
                                @endif
                            </td>

                            <!-- Penarikan Perangkat -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->collect_perangkat == '1')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        &check; Perangkat Ditarik
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        &bull; Belum Ditarik
                                    </span>
                                @endif
                                @if($t->team_collect)
                                    <div class="text-[10px] text-slate-400 mt-0.5">Tim: {{ $t->team_collect }}</div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->status_terminasi == '11')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        <span>Request Baru</span>
                                    </span>
                                @elseif($t->status_terminasi == '12' || $t->status_terminasi == '12.1')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                        <span>On Collecting</span>
                                    </span>
                                @elseif($t->status_terminasi == '13')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Selesai Putus</span>
                                    </span>
                                @elseif($t->status_terminasi == '16')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        <span>Batal</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-800 text-slate-400">Status {{ $t->status_terminasi }}</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->status_terminasi == '11')
                                    <form action="{{ route('finance.permintaan.terminasi.cancel', $t->kode_trx_terminasi) }}" method="POST" onsubmit="return confirm('Batalkan permintaan terminasi ini?')">
                                        @csrf
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-[11px] font-bold transition">
                                            Batalkan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="text-sm font-medium">Tidak ada data terminasi ditemukan</div>
                                <div class="text-xs mt-1">Klik tombol "+ Request Terminasi" untuk membuat pengajuan baru ke NOC.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($terminasis->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                {{ $terminasis->links() }}
            </div>
        @endif
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL: BUAT REQUEST TERMINASI KE NOC                                    -->
    <!-- ======================================================================= -->
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-100 backdrop-blur-sm">
        <div @click.away="createModalOpen = false"
             class="w-full max-w-xl bg-white border border-slate-200 rounded-2xl shadow-2xl p-6 sm:p-7 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🔌 Ajukan Permintaan Terminasi ke NOC / Lapangan</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Digunakan untuk pelanggan yang sudah berhenti berlangganan (tutup akun & penarikan perangkat).</p>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form action="{{ route('finance.permintaan.terminasi.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- 1. Search Pelanggan -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cari Pelanggan (No. Internet / Nama) <span class="text-rose-400">*</span></label>
                    <input type="text"
                           x-model="searchCustomerQuery"
                           @input.debounce.300ms="searchCustomer()"
                           placeholder="Ketik minimal 2 karakter untuk mencari pelanggan..."
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">

                    <!-- Dropdown Search Results -->
                    <div x-show="customerResults.length > 0"
                         x-cloak
                         class="absolute z-20 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-xl divide-y divide-slate-100">
                        <template x-for="cust in customerResults" :key="cust.nomor_internet">
                            <div @click="selectCustomer(cust)"
                                 class="p-2.5 hover:bg-slate-100 cursor-pointer text-xs transition">
                                <div class="font-bold text-white" x-text="cust.nama_pelanggan"></div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                    <span class="font-mono text-blue-400" x-text="'No: ' + cust.nomor_internet"></span>
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
                    <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-200 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Pelanggan:</span>
                            <span class="font-bold text-white" x-text="selectedCustomer.nama_pelanggan"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Paket:</span>
                            <span class="font-semibold text-amber-400" x-text="selectedCustomer.nama_kategori_bandwith + ' (' + selectedCustomer.nominal_bandwith + ' Mbps)'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Alamat Pasang:</span>
                            <span class="text-slate-300 text-right truncate max-w-[260px]" x-text="selectedCustomer.alamat_p"></span>
                        </div>
                    </div>
                </template>

                <!-- 2. Alasan Berhenti Berlangganan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alasan Berhenti Berlangganan <span class="text-rose-400">*</span></label>
                    <select name="note_termin" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium mb-2">
                        <option value="">-- Pilih Alasan Utama --</option>
                        <option value="Permintaan Pelanggan (Pindah Rumah / Alamat)">Permintaan Pelanggan (Pindah Rumah / Alamat)</option>
                        <option value="Permintaan Pelanggan (Keberatan Biaya Bulanan / Tarif)">Permintaan Pelanggan (Keberatan Biaya Bulanan / Tarif)</option>
                        <option value="Tunggakan Pembayaran Tidak Diselesaikan">Tunggakan Pembayaran Tidak Diselesaikan</option>
                        <option value="Beralih ke Provider Lain">Beralih ke Provider Lain</option>
                        <option value="Lainnya">Lainnya (Tuliskan di Catatan)</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="createModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-200 text-slate-300 text-xs font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="!selectedCustomer"
                            :class="!selectedCustomer ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-5 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold shadow-lg shadow-black/25 transition border border-slate-600">
                        Kirim Request Terminasi ke NOC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
