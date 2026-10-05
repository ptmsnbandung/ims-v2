@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 mb-2">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Audit & Log System</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Riwayat Broadcast WhatsApp</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar seluruh pesan broadcast yang telah dikirim oleh Direktur & Admin ke WhatsApp pelanggan.</p>
        </div>

        <div>
            <a href="{{ route('admin.broadcast') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali ke Broadcast</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('admin.broadcast.history') }}" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full sm:w-auto flex-1">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-400 mb-1">Kategori Broadcast</label>
                    <select name="kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ $selectedKategori == 'all' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="jatuh_tempo" {{ $selectedKategori == 'jatuh_tempo' ? 'selected' : '' }}>Peringatan Jatuh Tempo</option>
                        <option value="pengumuman" {{ $selectedKategori == 'pengumuman' ? 'selected' : '' }}>Pengumuman Jaringan</option>
                        <option value="custom" {{ $selectedKategori == 'custom' ? 'selected' : '' }}>Pesan Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-400 mb-1">Jenis Pengiriman</label>
                    <select name="jenis" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ $selectedJenis == 'all' ? 'selected' : '' }}>Semua Jenis</option>
                        <option value="single" {{ $selectedJenis == 'single' ? 'selected' : '' }}>Per Orangan (Single)</option>
                        <option value="massal" {{ $selectedJenis == 'massal' ? 'selected' : '' }}>Broadcast Massal (Bulk)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-400 mb-1">Pencarian Keyword</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Penerima, HP, ID, Kode..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="flex items-center gap-2 self-end w-full sm:w-auto">
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition shadow-xs">
                    Filter Log
                </button>
                <a href="{{ route('admin.broadcast.history') }}" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium transition border border-slate-200 dark:border-slate-700">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- History Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-100 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3.5">Waktu & Kode</th>
                        <th class="p-3.5">Pengirim</th>
                        <th class="p-3.5">Penerima & HP</th>
                        <th class="p-3.5">Jenis & Kategori</th>
                        <th class="p-3.5">Isi Pesan Terkirim</th>
                        <th class="p-3.5 text-center">Aksi WA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($logs as $log)
                        @php
                            $cleanHp = preg_replace('/[^0-9]/', '', $log->nomor_hp ?? '');
                            $waUrl = !empty($cleanHp) ? 'https://wa.me/' . $cleanHp . '?text=' . urlencode($log->pesan_terkirim) : '#';
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3.5">
                                <div class="font-bold text-slate-900 dark:text-white text-xs">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y H:i') }}</div>
                                <div class="font-mono text-[10px] text-slate-500 dark:text-slate-400">{{ $log->kode_broadcast }}</div>
                            </td>

                            <td class="p-3.5">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $log->nama_pengirim ?? 'Direktur' }}</div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500">Master User</div>
                            </td>

                            <td class="p-3.5">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $log->nama_penerima ?? 'Pelanggan' }}</div>
                                <div class="font-mono text-emerald-600 dark:text-emerald-400 text-[11px]">{{ $log->nomor_hp }}</div>
                                @if($log->nomor_internet)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400">ID: {{ $log->nomor_internet }}</div>
                                @endif
                            </td>

                            <td class="p-3.5">
                                <div class="flex flex-col gap-1 items-start">
                                    @if($log->jenis == 'massal')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20">
                                            📢 Broadcast Massal
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">
                                            👤 Per Orangan
                                        </span>
                                    @endif

                                    <div class="flex items-center gap-1 flex-wrap">
                                        @if(($log->metode_kirim ?? '') === 'meta_api')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                                🚀 Meta API
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                🌐 WA Web
                                            </span>
                                        @endif

                                        @if(($log->status_kirim ?? '') === 'sent')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Sent
                                            </span>
                                        @elseif(($log->status_kirim ?? '') === 'delivered')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                Delivered
                                            </span>
                                        @elseif(($log->status_kirim ?? '') === 'read')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                Read
                                            </span>
                                        @elseif(($log->status_kirim ?? '') === 'failed')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $log->meta_error_message ?? 'Gagal' }}">
                                                Failed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="p-3.5 max-w-xs">
                                <div class="line-clamp-2 text-slate-800 dark:text-slate-300 text-[11px] bg-slate-50 dark:bg-slate-950 p-2 rounded-lg border border-slate-200 dark:border-slate-800 font-sans leading-relaxed">
                                    {{ $log->pesan_terkirim }}
                                </div>
                                @if(!empty($log->meta_message_id))
                                    <div class="text-[9px] font-mono text-slate-400 mt-1 truncate" title="{{ $log->meta_message_id }}">
                                        ID: {{ $log->meta_message_id }}
                                    </div>
                                @endif
                            </td>

                            <td class="p-3.5 text-center">
                                @if(!empty($cleanHp))
                                    <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30 text-xs font-semibold transition">
                                        <span>Kirim Lagi</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[10px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                Belum ada riwayat log broadcast WhatsApp yang tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
