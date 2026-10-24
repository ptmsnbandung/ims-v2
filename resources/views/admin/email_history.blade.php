@extends('layouts.app')

@section('page_title', 'Riwayat Pengiriman Email')

@section('content')
<div class="space-y-6" x-data="{ modalOpen: false, modalContent: null }">
    <!-- Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                <span>IMS</span>
                <span>&rsaquo;</span>
                <a href="{{ route('admin.email.index') }}" class="hover:underline">Kelola Email</a>
                <span>&rsaquo;</span>
                <span class="text-sky-600 dark:text-sky-400 font-semibold">Riwayat Log</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Riwayat Pengiriman Email</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Catatan lengkap pengiriman email broadcast dan custom ke pelanggan.</p>
        </div>

        <a href="{{ route('admin.email.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold hover:bg-slate-300 transition">
            &larr; Kembali ke Form Kirim Email
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.email.history') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px] relative">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari penerima, email, atau subjek..."
                       class="w-full pl-9 pr-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <div class="w-44">
                <select name="status"
                        onchange="this.form.submit()"
                        class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Terkirim (Sent)</option>
                    <option value="queued" {{ $status === 'queued' ? 'selected' : '' }}>Antrean (Queued)</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Gagal (Failed)</option>
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold">
                Filter
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3.5">Waktu</th>
                        <th class="p-3.5">Penerima</th>
                        <th class="p-3.5">Email</th>
                        <th class="p-3.5">Subjek</th>
                        <th class="p-3.5">Pengirim</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3.5 whitespace-nowrap text-slate-400 font-mono">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                            {{ $log->nama_pelanggan ?? '-' }}
                            @if($log->nomor_internet)
                                <span class="text-[10px] text-slate-400 font-mono block">#{{ $log->nomor_internet }}</span>
                            @endif
                        </td>
                        <td class="p-3.5 font-mono text-sky-600 dark:text-sky-400">
                            {{ $log->email_tujuan }}
                        </td>
                        <td class="p-3.5 max-w-sm truncate font-medium text-slate-800 dark:text-slate-200">
                            {{ $log->subjek }}
                        </td>
                        <td class="p-3.5 text-slate-500 font-medium">
                            {{ $log->sender_user ?? 'SYSTEM' }}
                        </td>
                        <td class="p-3.5">
                            @if($log->status === 'sent')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sent
                                </span>
                            @elseif($log->status === 'queued')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span> Queued
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" title="{{ $log->error_message }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal
                                </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-right">
                            <button @click="modalContent = @json($log); modalOpen = true"
                                    class="text-xs font-semibold text-sky-600 hover:underline cursor-pointer">
                                Lihat Pesan
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                            Tidak ada riwayat pengiriman email yang sesuai dengan kriteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Modal Lihat Pesan -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.away="modalOpen = false">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white" x-text="modalContent?.subjek"></h3>
                    <p class="text-xs text-slate-500 font-mono" x-text="'Penerima: ' + modalContent?.email_tujuan"></p>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <div class="p-4 overflow-y-auto flex-1 bg-slate-100 dark:bg-slate-950">
                <div x-html="modalContent?.pesan_html"></div>
            </div>

            <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex justify-end">
                <button @click="modalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
