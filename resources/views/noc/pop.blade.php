@extends('layouts.app')

@section('title', 'Manajemen POP (Point of Presence) - NOC IMS')
@section('page_title', 'Manajemen POP (Point of Presence)')

@section('content')
<div class="space-y-6"
     x-data="{
         popModalOpen: false
     }">

    <!-- Top Action Bar: Search & Add POP Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xl shadow-black/10">
        
        <!-- Search Input -->
        <form method="GET" action="{{ route('noc.pop') }}" class="relative flex-1 max-w-md">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}"
                   placeholder="Cari POP, Kode, atau Keterangan..." 
                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </form>

        <!-- Action Buttons -->
        <div>
            <button type="button" 
                    @click="popModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah POP Baru</span>
            </button>
        </div>
    </div>

    <!-- POP Table Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                    Daftar Node / Point of Presence (POP)
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Distribusi server router gateway per wilayah jaringan.</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">Total: {{ $pops->total() }} Node</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-5">Kode POP</th>
                        <th class="py-3.5 px-5">Nama POP</th>
                        <th class="py-3.5 px-5">Pelanggan Terhubung</th>
                        <th class="py-3.5 px-5">Deskripsi / Lokasi</th>
                        <th class="py-3.5 px-5">Tanggal Dibuat</th>
                        <th class="py-3.5 px-5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($pops as $pop)
                        @php $cCount = $popCustomerCounts[$pop->kode_pop] ?? 0; @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-4 px-5 font-mono font-bold text-blue-500">
                                {{ $pop->kode_pop }}
                            </td>
                            <td class="py-4 px-5 font-semibold text-slate-800 dark:text-slate-200">
                                📍 {{ $pop->nama_pop }}
                            </td>
                            <td class="py-4 px-5 font-mono">
                                @if($cCount > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-500/15 text-blue-400 border border-blue-500/20">
                                        👥 {{ number_format($cCount) }} Client
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs font-medium">0 Client</span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-slate-600 dark:text-slate-400">
                                {{ $pop->desc_pop ?: '-' }}
                            </td>
                            <td class="py-4 px-5 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                {{ $pop->date_create ? \Carbon\Carbon::parse($pop->date_create)->translatedFormat('d M Y H:i') : '-' }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>AKTIF</span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data POP ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pops->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $pops->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah POP -->
    <div x-show="popModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="popModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tambah Node POP Baru</h3>
                    <button @click="popModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form action="{{ route('noc.pop.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode POP <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_pop" required placeholder="Contoh: POP-SRG" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama POP <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_pop" required placeholder="Contoh: POP SOREANG UTAMA" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Alamat Server Node</label>
                        <textarea name="desc_pop" rows="2" placeholder="Detail server / lokasi rak POP..." class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="popModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold">Simpan POP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
