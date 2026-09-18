@extends('layouts.app')

@section('title', 'Manajemen ODP (Optical Distribution Point) - NOC IMS')
@section('page_title', 'Manajemen ODP (FTTH)')

@section('content')
<div class="space-y-6"
     x-data="{
         odpModalOpen: false
     }">

    <!-- Top Action Bar: Search & Add ODP Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white backdrop-blur-xl border border-slate-200 rounded-2xl p-4 shadow-xl shadow-slate-200/40">
        
        <!-- Search Input -->
        <form method="GET" action="{{ route('noc.odp') }}" class="relative flex-1 max-w-md">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}"
                   placeholder="Cari ODP, Kode, atau Keterangan..." 
                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </form>

        <!-- Action Buttons -->
        <div>
            <button type="button" 
                    @click="odpModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah ODP Baru</span>
            </button>
        </div>
    </div>

    <!-- ODP Table Card -->
    <div class="bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Daftar Titik ODP FTTH (Optical Distribution Point)
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Titik distribusi dropcore ke pelanggan di tiang/lapangan.</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">Total: {{ $odps->total() }} Titik ODP</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-700">
                        <th class="py-3.5 px-5">Kode ODP</th>
                        <th class="py-3.5 px-5">Nama ODP</th>
                        <th class="py-3.5 px-5">Port PON Induk</th>
                        <th class="py-3.5 px-5">Kapasitas Core</th>
                        <th class="py-3.5 px-5">Status Port</th>
                        <th class="py-3.5 px-5">Keterangan / Lokasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($odps as $odp)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-4 px-5 font-mono font-bold text-blue-500">
                                {{ $odp->kode_odp }}
                            </td>
                            <td class="py-4 px-5 font-semibold text-slate-800">
                                {{ $odp->name_odp }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-500/10 text-blue-400 font-bold">
                                    {{ $odp->kode_pon ?: 'Default PON' }}
                                </span>
                            </td>
                            <td class="py-4 px-5 font-bold text-slate-900 font-mono">
                                {{ $odp->capacity_odp ?: 8 }} <span class="text-[10px] text-slate-400 font-normal">Port</span>
                            </td>
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>TERHUBUNG</span>
                                </span>
                            </td>
                            <td class="py-4 px-5 text-slate-600">
                                {{ $odp->note_odp ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data ODP ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($odps->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $odps->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah ODP -->
    <div x-show="odpModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="fixed inset-0 bg-slate-100 backdrop-blur-sm" @click="odpModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Tambah Titik ODP Baru</h3>
                    <button @click="odpModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form action="{{ route('noc.odp.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kode ODP <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_odp" required placeholder="Contoh: ODP-SRG-01" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama ODP <span class="text-rose-500">*</span></label>
                        <input type="text" name="name_odp" required placeholder="Contoh: ODP Tiang Depan Masjid Cincin" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Hubungkan ke Port PON</label>
                        <select name="kode_pon" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900">
                            <option value="">-- Pilih PON Port --</option>
                            @foreach($pons as $pon)
                                <option value="{{ $pon->kode_pon }}">{{ $pon->name_pon }} ({{ $pon->kode_pon }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kapasitas Port Splitter</label>
                        <select name="capacity_odp" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900">
                            <option value="8">8 Port (1:8)</option>
                            <option value="16">16 Port (1:16)</option>
                            <option value="24">24 Port (1:24)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Keterangan / Titik Tiang</label>
                        <textarea name="note_odp" rows="2" placeholder="Detail posisi tiang / koordinat..." class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-slate-900"></textarea>
                    </div>
                    <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                        <button type="button" @click="odpModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold">Simpan ODP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
