@extends('layouts.app')

@section('title', 'Manajemen OLT & PON Port - NOC IMS')
@section('page_title', 'Manajemen OLT & PON Port')

@section('content')
<div class="space-y-6"
     x-data="{
         oltModalOpen: false,
         ponModalOpen: false,
         selectedOlt: null
     }">

    <!-- Top Action Bar: Search & Add OLT Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xl shadow-black/10">
        
        <!-- Search Input -->
        <form method="GET" action="{{ route('noc.olt') }}" class="relative flex-1 max-w-md">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}"
                   placeholder="Cari OLT, Kode, atau Keterangan..." 
                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </form>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="ponModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition cursor-pointer">
                <span>+ Tambah PON Port</span>
            </button>

            <button type="button" 
                    @click="oltModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah OLT</span>
            </button>
        </div>
    </div>

    <!-- OLT Table Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                Daftar OLT (Optical Line Terminal)
            </h3>
            <span class="text-xs text-slate-400">Total: {{ $olts->total() }} Unit</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-5">Kode OLT</th>
                        <th class="py-3.5 px-5">Nama OLT</th>
                        <th class="py-3.5 px-5">Wilayah</th>
                        <th class="py-3.5 px-5">Kapasitas</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($olts as $olt)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-4 px-5 font-mono font-bold text-blue-500">
                                {{ $olt->kode_olt }}
                            </td>
                            <td class="py-4 px-5 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $olt->name_olt }}
                            </td>
                            <td class="py-4 px-5 text-slate-600 dark:text-slate-400">
                                {{ $olt->kode_w ?: 'Default' }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $olt->capacity_olt ?: 0 }}</span>
                                <span class="text-[10px] text-slate-400">Port</span>
                            </td>
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>ONLINE</span>
                                </span>
                            </td>
                            <td class="py-4 px-5 text-slate-500 dark:text-slate-400">
                                {{ $olt->note_olt ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data OLT ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($olts->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $olts->links() }}
            </div>
        @endif
    </div>

    <!-- PON Ports Grid & Sub-table -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xl shadow-black/10 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                    Daftar Port PON (GPON / EPON)
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Port transmisi serat optik yang terhubung ke ODP pelanggan.</p>
            </div>
            <span class="text-xs font-mono text-slate-400">{{ $pons->count() }} Port Terdaftar</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @forelse($pons as $pon)
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold text-blue-500">{{ $pon->kode_pon }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 font-semibold font-mono">
                            {{ $pon->kode_gpon }}
                        </span>
                    </div>
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $pon->name_pon }}</div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-800">
                        <span>Kapasitas:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ $pon->capacity_pon ?: 64 }} User</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-4 text-center text-xs text-slate-400">
                    Belum ada port PON yang dikonfigurasi.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal Tambah OLT -->
    <div x-show="oltModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="oltModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tambah Data OLT Baru</h3>
                    <button @click="oltModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form action="{{ route('noc.olt.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode OLT <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_olt" required placeholder="Contoh: OLT-BDG-01" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama OLT <span class="text-rose-500">*</span></label>
                        <input type="text" name="name_olt" required placeholder="Contoh: ZTE C320 Core 1" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kapasitas Port</label>
                        <input type="number" name="capacity_olt" value="16" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keterangan</label>
                        <textarea name="note_olt" rows="2" placeholder="Catatan konfigurasi..." class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="oltModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold">Simpan OLT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah PON Port -->
    <div x-show="ponModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="ponModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tambah Port PON Baru</h3>
                    <button @click="ponModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form action="{{ route('noc.pon.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode PON <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_pon" required placeholder="Contoh: PON-01" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pilih GPON Slot <span class="text-rose-500">*</span></label>
                        <select name="kode_gpon" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                            @forelse($gpons as $gpon)
                                <option value="{{ $gpon->kode_gpon }}">{{ $gpon->name_gpon }} ({{ $gpon->kode_gpon }})</option>
                            @empty
                                <option value="GPON-01">GPON Default Slot 1</option>
                            @endforelse
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Port PON <span class="text-rose-500">*</span></label>
                        <input type="text" name="name_pon" required placeholder="Contoh: PON 1/1/1" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kapasitas User / Splitter Ratio</label>
                        <input type="number" name="capacity_pon" value="64" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan</label>
                        <textarea name="note_pon" rows="2" placeholder="Catatan port PON..." class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="ponModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold">Simpan Port PON</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
