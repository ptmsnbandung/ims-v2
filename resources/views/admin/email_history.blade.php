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
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal
                                    </span>
                                    <div class="text-[10.5px] text-rose-600 dark:text-rose-400 bg-rose-50/90 dark:bg-rose-950/40 border border-rose-200/70 dark:border-rose-800/50 rounded-lg px-2 py-1 max-w-xs leading-snug break-words">
                                        <strong class="font-bold">Alasan:</strong>
                                        {{ $log->error_message ?: 'Terkena batas kecepatan kirim (Rate Limit) atau koneksi ke server email terputus.' }}
                                    </div>
                                </div>
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

    <!-- Modal Lihat Pesan (Teleport ke body agar selalu di tengah layar penuh) -->
    <template x-teleport="body">
        <div x-show="modalOpen"
             x-cloak
             @keydown.escape.window="modalOpen = false"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col"
                 style="max-height: 88vh; margin: auto;"
                 @click.away="modalOpen = false">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3" style="flex-shrink: 0;">
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" x-text="modalContent?.subjek"></h3>
                        <p class="text-xs text-slate-500 font-mono truncate" x-text="'Penerima: ' + (modalContent?.nama_pelanggan || '-') + ' (' + (modalContent?.email_tujuan || '-') + ')'"></p>
                    </div>
                    <button @click="modalOpen = false" class="w-7 h-7 rounded-lg flex items-center justify-center bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-600 dark:bg-slate-800 dark:text-slate-300 font-bold text-base cursor-pointer shrink-0">&times;</button>
                </div>

                <div class="p-4 overflow-y-auto flex-1 bg-slate-100 dark:bg-slate-950 space-y-3" style="min-height: 0;">
                    <!-- Kotak Alasan Kegagalan -->
                    <template x-if="modalContent && (modalContent.status === 'failed' || modalContent.error_message)">
                        <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
                            <div class="font-bold flex items-center gap-1.5 mb-1 text-rose-800 dark:text-rose-200">
                                <svg class="w-4 h-4 shrink-0 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                <span>Alasan Kegagalan Pengiriman:</span>
                            </div>
                            <p class="leading-relaxed break-words font-medium"
                               x-text="modalContent.error_message || 'Terkena batas kecepatan kirim (Rate Limit) atau koneksi ke server email terputus saat pengiriman massal.'"></p>
                        </div>
                    </template>

                    <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-xs leading-relaxed text-slate-700 dark:text-slate-200"
                         x-html="modalContent?.pesan_html"></div>
                </div>

                <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex justify-end" style="flex-shrink: 0;">
                    <button @click="modalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

