@extends('layouts.app', ['title' => 'Request UP / Downgrade Bandwidth - Finance'])

@section('page_title', 'Permintaan UP / Downgrade Bandwidth')

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
                <span class="text-blue-400 font-semibold">UP / Downgrade</span>
            </div>
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <span>⚡ Permintaan Ubah Layanan (UP / Downgrade)</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-semibold">Finance &rarr; NOC</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Ajukan perubahan kecepatan/paket bandwidth sesuai permintaan pelanggan untuk dieksekusi oleh tim NOC.</p>
        </div>

        <div>
            <button type="button"
                    @click="resetForm(); createModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 transition duration-150 cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>+ Request Ubah Bandwidth</span>
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
            <div class="text-[11px] text-slate-400 mt-0.5">Status (KD11) Request Baru</div>
        </div>

        <!-- 2. On Schedule -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500/10 via-slate-900 to-slate-900 border border-blue-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-400 uppercase tracking-wider">Dijadwalkan NOC</span>
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count12) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (KD12) On Schedule</div>
        </div>

        <!-- 3. Success -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-500/10 via-slate-900 to-slate-900 border border-emerald-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Selesai Eksekusi</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count13) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (KD13) Berhasil Aktif</div>
        </div>

        <!-- 4. Canceled -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-rose-500/10 via-slate-900 to-slate-900 border border-rose-500/20 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Dibatalkan</span>
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
            </div>
            <div class="mt-2 text-2xl font-black text-white">{{ number_format($count14) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Status (KD14) Batal</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl">
        <form method="GET" action="{{ route('finance.permintaan.up-downgrade') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            <div class="lg:col-span-3">
                <select name="layanan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                       placeholder="Cari Kode Trx / No. Internet / Nama Pelanggan..."
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 placeholder-slate-500 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="lg:col-span-3">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>(KD11) Request Baru</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>(KD12) On Schedule</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>(KD13) Success</option>
                    <option value="14" {{ request('status') === '14' ? 'selected' : '' }}>(KD14) Canceled</option>
                </select>
            </div>

            <div class="lg:col-span-2 flex items-center gap-2">
                <a href="{{ route('finance.permintaan.up-downgrade') }}"
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
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
    <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-slate-400 font-semibold">
                        <th class="py-3.5 px-4">KODE / TANGGAL</th>
                        <th class="py-3.5 px-4">PELANGGAN</th>
                        <th class="py-3.5 px-4">PAKET SAAT INI</th>
                        <th class="py-3.5 px-4 text-center">&rarr;</th>
                        <th class="py-3.5 px-4">PAKET PENGAJUAN BARU</th>
                        <th class="py-3.5 px-4 text-center">JADWAL / EKSEKUSI</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @forelse($ubahLayanans as $u)
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Kode / Tanggal -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-blue-400">{{ $u->kode_trx_ubah_layanan }}</span>
                                <div class="text-[11px] text-slate-500 mt-0.5">Tgl: {{ $u->date_request ?? '-' }}</div>
                            </td>

                            <!-- Pelanggan -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white">{{ $u->nama_pelanggan ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">
                                    No: <a href="{{ route('teknik.pelanggan.profile', $u->nomor_internet) }}" class="text-blue-400 hover:underline" title="Buka Profile Pelanggan">{{ $u->nomor_internet }}</a>
                                </div>
                                <div class="text-[10px] text-slate-500 max-w-[200px] truncate mt-0.5">{{ $u->alamat_p ?? '-' }}</div>
                            </td>

                            <!-- Paket Lama -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-semibold text-[11px]">
                                    {{ $u->nama_kategori_bandwith_lama ?? 'Broadband' }}
                                </span>
                                <div class="text-[11px] font-mono text-slate-400 mt-0.5">
                                    {{ $u->nominal_bandwith_lama ?? '-' }} Mbps
                                </div>
                            </td>

                            <!-- Arrow Indicator -->
                            <td class="py-3.5 px-4 text-center font-bold text-slate-500">
                                &rarr;
                            </td>

                            <!-- Paket Baru -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold text-[11px]">
                                    {{ $u->nama_kategori_bandwith_baru ?? $u->alias_nama_kategori_baru ?? 'Broadband' }}
                                </span>
                                <div class="text-[11px] font-mono font-bold text-emerald-400 mt-0.5">
                                    {{ $u->nominal_bandwith_baru ?? '-' }} Mbps
                                </div>
                            </td>

                            <!-- Jadwal / Eksekusi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="text-[11px] text-slate-300 font-medium">{{ $u->date_schedule ?? '-' }}</div>
                                @if($u->note_schedule)
                                    <div class="text-[10px] text-slate-500 max-w-[150px] truncate mx-auto">{{ $u->note_schedule }}</div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($u->status_ubah_layanan == '11')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        <span>Request Baru</span>
                                    </span>
                                @elseif($u->status_ubah_layanan == '12')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                        <span>On Schedule</span>
                                    </span>
                                @elseif($u->status_ubah_layanan == '13')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Success</span>
                                    </span>
                                @elseif($u->status_ubah_layanan == '14')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        <span>Canceled</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-800 text-slate-400">Status {{ $u->status_ubah_layanan }}</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                @if($u->status_ubah_layanan == '11')
                                    <form action="{{ route('finance.permintaan.up-downgrade.cancel', $u->kode_trx_ubah_layanan) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan ini?')">
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
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="text-sm font-medium">Tidak ada data permohonan UP / Downgrade ditemukan</div>
                                <div class="text-xs mt-1">Klik tombol "+ Request Ubah Bandwidth" untuk membuat pengajuan baru ke NOC.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ubahLayanans->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/40 flex justify-end">
                {{ $ubahLayanans->links() }}
            </div>
        @endif
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL: BUAT REQUEST UP / DOWNGRADE KE NOC                               -->
    <!-- ======================================================================= -->
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="createModalOpen = false"
             class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-7 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>⚡ Ajukan UP / Downgrade Bandwidth ke NOC</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Permintaan akan otomatis masuk ke antrean kerja tim NOC untuk dieksekusi.</p>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form action="{{ route('finance.permintaan.up-downgrade.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- 1. Search Pelanggan -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cari Pelanggan (No. Internet / Nama) <span class="text-rose-400">*</span></label>
                    <input type="text"
                           x-model="searchCustomerQuery"
                           @input.debounce.300ms="searchCustomer()"
                           placeholder="Ketik minimal 2 karakter untuk mencari pelanggan..."
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">

                    <!-- Dropdown Search Results -->
                    <div x-show="customerResults.length > 0"
                         x-cloak
                         class="absolute z-20 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-slate-950 border border-slate-700 rounded-xl shadow-xl divide-y divide-slate-800">
                        <template x-for="cust in customerResults" :key="cust.nomor_internet">
                            <div @click="selectCustomer(cust)"
                                 class="p-2.5 hover:bg-blue-600/20 cursor-pointer text-xs transition">
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
                    <div class="p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Pelanggan:</span>
                            <span class="font-bold text-white" x-text="selectedCustomer.nama_pelanggan"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Paket Saat Ini:</span>
                            <span class="font-semibold text-amber-400" x-text="selectedCustomer.nama_kategori_bandwith + ' (' + selectedCustomer.nominal_bandwith + ' Mbps) - Rp ' + Number(selectedCustomer.harga_bandwith).toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Alamat Pasang:</span>
                            <span class="text-slate-300 text-right truncate max-w-[260px]" x-text="selectedCustomer.alamat_p"></span>
                        </div>
                    </div>
                </template>

                <!-- 2. Pilih Paket Baru -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Paket / Bandwidth Baru <span class="text-rose-400">*</span></label>
                    <select name="kode_bandwith_baru" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                        <option value="">-- Pilih Paket Baru --</option>
                        @foreach($paketList as $p)
                            <option value="{{ $p->kode_bandwith }}">
                                {{ $p->nama_kategori_bandwith }} - {{ $p->nominal_bandwith }} Mbps (Rp {{ number_format((float)($p->harga_bandwith ?? 0), 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Tanggal Efektif / Jadwal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal Jadwal Eksekusi <span class="text-rose-400">*</span></label>
                        <input type="date"
                               name="date_schedule"
                               value="{{ date('Y-m-d') }}"
                               required
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kategori Permintaan</label>
                        <select class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                            <option>Upgrade Bandwidth (Tambah Kecepatan)</option>
                            <option>Downgrade Bandwidth (Turun Kecepatan)</option>
                        </select>
                    </div>
                </div>

                <!-- 4. Catatan / Alasan Permintaan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan Tambahan untuk NOC</label>
                    <textarea name="note_request"
                              rows="2"
                              placeholder="Contoh: Pelanggan minta upgrade mulai tagihan bulan depan..."
                              class="w-full text-xs px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="createModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="!selectedCustomer"
                            :class="!selectedCustomer ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/25 transition">
                        Kirim Request ke NOC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
