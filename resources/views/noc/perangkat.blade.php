@extends('layouts.app')

@section('title', 'Inventaris Perangkat & Asset Jaringan - NOC IMS')
@section('page_title', 'Inventaris Perangkat & Asset Jaringan')

@section('content')
<div class="space-y-6">

    <!-- Top Hardware Installed Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($installedSummary->take(4) as $summary)
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xl shadow-black/10 backdrop-blur-xl flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block truncate max-w-[150px]">
                        {{ $summary->nama_barang }}
                    </span>
                    <div class="text-xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                        {{ number_format((float) ($summary->total_terpasang ?? 0), 0, ',', '.') }}
                    </div>
                    <span class="text-[10px] text-emerald-500 font-medium">Unit Terpasang di Pelanggan</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xl shadow-black/10">
        <form method="GET" action="{{ route('noc.perangkat') }}" class="relative flex-1 max-w-md">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}"
                   placeholder="Cari Nama Barang, Kode, atau Tipe..." 
                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </form>

        <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">
            <span>Total Item: <strong class="text-slate-900 dark:text-white">{{ $barangs->total() }}</strong></span>
        </div>
    </div>

    <!-- Barang / Hardware Table Card -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                    Master Data Perangkat & Asset Jaringan
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Daftar katalog perangkat ONT, Switch, Router, Dropcore FO, dan material instalasi.</p>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">Total: {{ $barangs->total() }} Item</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <th class="py-3.5 px-5">Kode Barang</th>
                        <th class="py-3.5 px-5">Nama Perangkat / Material</th>
                        <th class="py-3.5 px-5">Tipe / Kategori</th>
                        <th class="py-3.5 px-5">Biaya / Biaya Kelebihan</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($barangs as $b)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-4 px-5 font-mono font-bold text-blue-500">
                                {{ $b->kode_barang }}
                            </td>
                            <td class="py-4 px-5 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $b->nama_barang }}
                            </td>
                            <td class="py-4 px-5 text-slate-600 dark:text-slate-400">
                                {{ $b->tipe_barang ?: ($b->kode_jns_barang ?: 'Perangkat FTTH') }}
                            </td>
                            <td class="py-4 px-5 font-mono font-semibold text-slate-900 dark:text-white">
                                Rp {{ number_format((float) ($b->biaya_kelebihan ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>TERSEDIA</span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data perangkat yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($barangs->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $barangs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
