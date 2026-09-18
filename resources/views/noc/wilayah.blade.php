@extends('layouts.app')

@section('title', 'Master Wilayah Perangkat - NOC IMS')
@section('page_title', 'Wilayah Perangkat Jaringan')

@section('content')
<div class="space-y-6"
     x-data="{
         wilayahModalOpen: false
     }">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xl shadow-black/10">
        
        <!-- Search Input -->
        <form method="GET" action="{{ route('noc.wilayah') }}" class="relative flex-1 max-w-md">
            <input type="text" 
                    name="search" 
                    value="{{ $search }}"
                    placeholder="Cari Wilayah, Kode, atau Keterangan..." 
                    class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </form>

        <!-- Action Buttons -->
        <div>
            <button type="button" 
                    @click="wilayahModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Wilayah Baru</span>
            </button>
        </div>
    </div>

    <!-- Wilayah Table Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                    Daftar Master Wilayah Perangkat Jaringan (m_wilayah_perangkat)
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Pemetaan regional router core, OLT gateway, dan distribusi node.</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">Total: {{ $wilayahs->total() }} Wilayah</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-5">Kode Wilayah</th>
                        <th class="py-3.5 px-5">Nama Wilayah Perangkat</th>
                        <th class="py-3.5 px-5">Total OLT Terpasang</th>
                        <th class="py-3.5 px-5">Kapasitas Slot</th>
                        <th class="py-3.5 px-5">Status Coverage</th>
                        <th class="py-3.5 px-5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($wilayahs as $w)
                        @php $oltTotal = $oltCounts[$w->kode_w] ?? 0; @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-4 px-5 font-mono font-bold text-blue-500">
                                {{ $w->kode_w }}
                            </td>
                            <td class="py-4 px-5 font-bold text-slate-800 dark:text-slate-200">
                                🗺️ {{ $w->name_w }}
                            </td>
                            <td class="py-4 px-5 font-mono">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-500/15 text-blue-400 border border-blue-500/20">
                                    🖥️ {{ $oltTotal }} Unit OLT
                                </span>
                            </td>
                            <td class="py-4 px-5 font-bold text-slate-900 dark:text-white font-mono">
                                {{ $w->capacity_w ?: '-' }} <span class="text-[10px] text-slate-400 font-normal">Card</span>
                            </td>
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>AKTIF / COVERED</span>
                                </span>
                            </td>
                            <td class="py-4 px-5 text-slate-500 dark:text-slate-400">
                                {{ $w->note_w ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data wilayah perangkat ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($wilayahs->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $wilayahs->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah Wilayah -->
    <div x-show="wilayahModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="wilayahModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tambah Wilayah Perangkat Baru</h3>
                    <button @click="wilayahModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form action="{{ route('noc.wilayah.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Wilayah <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_w" placeholder="Contoh: W4" required
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Wilayah Perangkat <span class="text-rose-500">*</span></label>
                        <input type="text" name="name_w" placeholder="Contoh: KOTA CIMAHI" required
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kapasitas Slot</label>
                        <input type="text" name="capacity_w" placeholder="Contoh: 2"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan</label>
                        <textarea name="note_w" rows="2" placeholder="Catatan opsional..."
                                  class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="wilayahModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold shadow-md shadow-blue-500/25">Simpan Wilayah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
