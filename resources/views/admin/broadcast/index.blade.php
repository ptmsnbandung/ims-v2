@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="broadcastApp()">
    <!-- Top Header Banner (Fully Adaptive Light & Dark Mode) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 sm:p-7 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span>Modul Direktur &amp; Master Admin</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">
                    Direktur Yudiana
                </span>
            </div>
            
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                <span>Broadcast WhatsApp Pelanggan</span>
            </h1>
            
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mt-1 max-w-2xl leading-relaxed">
                Kirim pengingat jatuh tempo tagihan, pengumuman pemeliharaan jaringan, atau pesan custom secara massal maupun per orangan dengan template terpersonalisasi.
            </p>
        </div>

        <div class="relative z-10 flex items-center gap-3 flex-wrap">
            <a href="{{ route('admin.broadcast.history') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Riwayat Log Broadcast</span>
            </a>

            <button @click="openModalTemplate()" type="button" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-600/20 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Kelola Template</span>
            </button>
        </div>
    </div>

    <!-- Stat KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Mendekati Jatuh Tempo / Belum Lunas -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 relative overflow-hidden shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Tagihan Jatuh Tempo</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($totalUnpaidCount) }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Perlu pengingat WhatsApp</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Target Terfilter -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 relative overflow-hidden shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Target Pelanggan</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($totalTargetCount) }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Sesuai filter pencarian</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 3: Broadcast Terkirim Hari Ini -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 relative overflow-hidden shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Terkirim Hari Ini</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($sentTodayCount) }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Status dikirim ke WA</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Log Riwayat -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 relative overflow-hidden shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">Total Log Broadcast</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($totalSentLog) }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Akumulasi seluruh pesan</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace Grid: Left (Filters & Customers) + Right (Message Builder & Preview) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Target Pelanggan Table & Selectors (7 Cols) -->
        <div class="lg:col-span-7 space-y-4">
            
            <!-- Filters & Search Toolbar -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
                <form method="GET" action="{{ route('admin.broadcast') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        
                        <!-- Filter Status Tagihan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Tagihan</label>
                            <select name="status_tagihan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="unpaid" {{ $selectedStatusTagihan == 'unpaid' ? 'selected' : '' }}>⚠️ Mendekati Jatuh Tempo / Belum Lunas</option>
                                <option value="paid" {{ $selectedStatusTagihan == 'paid' ? 'selected' : '' }}>✅ Lunas (PAID)</option>
                                <option value="isolir" {{ $selectedStatusTagihan == 'isolir' ? 'selected' : '' }}>⛔ Isolir / Suspend</option>
                                <option value="all" {{ $selectedStatusTagihan == 'all' ? 'selected' : '' }}>🌐 Semua Pelanggan</option>
                            </select>
                        </div>

                        <!-- Filter Periode Bulan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Bulan Tagihan</label>
                            <select name="bulan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="all" {{ $selectedBulan == 'all' ? 'selected' : '' }}>Semua Bulan</option>
                                @for($m = 1; $m <= 12; $m++)
                                    @php $monthVal = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                                    <option value="{{ $monthVal }}" {{ $selectedBulan == $monthVal ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Filter Periode Tahun -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Tagihan</label>
                            <select name="tahun" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="all" {{ $selectedTahun == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                                @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                                    <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-72">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, ID internet, HP..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>

                        <!-- Buttons & Per Page -->
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition shadow-xs">
                                Filter Data
                            </button>
                            <a href="{{ route('admin.broadcast') }}" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium transition border border-slate-200 dark:border-slate-700">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Customer Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
                <!-- Action Bar above table -->
                <div class="p-4 bg-slate-50 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span>Pilih Semua Halaman Ini</span>
                        </label>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium" x-show="selectedTargets.length > 0">
                            (<span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="selectedTargets.length"></span> dipilih)
                        </span>
                    </div>

                    <button @click="triggerBulkBroadcast()" type="button" :disabled="selectedTargets.length === 0" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white text-xs font-semibold shadow transition cursor-pointer">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <span>Kirim Broadcast Massal (<span x-text="selectedTargets.length">0</span>)</span>
                    </button>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-100 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5 w-10 text-center">#</th>
                                <th class="p-3.5">Pelanggan</th>
                                <th class="p-3.5">No. Internet & HP</th>
                                <th class="p-3.5">Status Tagihan</th>
                                <th class="p-3.5 text-right">Nominal</th>
                                <th class="p-3.5 text-center">Aksi WA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($pelangganList as $item)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-3.5 text-center">
                                        <input type="checkbox" value="{{ $item->nomor_internet }}" x-model="selectedTargets" class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>

                                    <td class="p-3.5">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs">{{ $item->nama_pelanggan }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[180px]">{{ $item->alamat_pasang ?? ($item->nama_kota_pasang ?? 'Area IMS') }}</div>
                                    </td>

                                    <td class="p-3.5">
                                        <div class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">{{ $item->nomor_internet }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <span>📱 {{ $item->nomor_hp ?? '-' }}</span>
                                        </div>
                                    </td>

                                    <td class="p-3.5">
                                        @if($item->status_bill_lay == '15')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                                <span>LUNAS</span>
                                            </span>
                                        @elseif(in_array($item->status_bill_lay, ['13', '14']))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                                                <span>JATUH TEMPO</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                <span>{{ $item->status_bill_lay ?? 'Aktif' }}</span>
                                            </span>
                                        @endif
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Periode {{ $item->periode_tagihan ?? ($item->bulan_tagihan . '/' . $item->tahun_tagihan) }}</div>
                                    </td>

                                    <td class="p-3.5 text-right font-bold text-slate-900 dark:text-slate-100">
                                        Rp {{ number_format((float) ($item->total_layanan ?? ($item->harga_bandwith ?? 0)), 0, ',', '.') }}
                                    </td>

                                    <td class="p-3.5 text-center">
                                        <button @click="openSingleSendModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}', '{{ $item->nomor_hp }}')" type="button" title="Kirim WA ke Pelanggan Ini" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30 text-xs font-semibold transition cursor-pointer">
                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                            </svg>
                                            <span>Kirim WA</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                        Tidak ada data pelanggan yang sesuai dengan filter pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                    {{ $pelangganList->links() }}
                </div>
            </div>

        </div>

        <!-- Right Column: Interactive WhatsApp Message Builder & Live Smartphone Preview (5 Cols) -->
        <div class="lg:col-span-5 space-y-4 sticky top-6">
            
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        <span>Custom Editor Broadcast</span>
                    </h3>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-500/20">Live Sync</span>
                </div>

                <!-- Template Selector Preset -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Template Broadcast</label>
                    <select x-model="selectedTemplateId" @change="loadSelectedTemplate()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" data-pesan="{{ addslashes($tpl->pesan) }}" data-kategori="{{ $tpl->kategori }}">
                                {{ $tpl->nama_template }} {{ $tpl->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Placeholder Variable Chips (Click to insert) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Sisipkan Variabel Dinamis (Klik untuk tambah):</label>
                    <div class="flex flex-wrap gap-1.5 text-[11px]">
                        <button type="button" @click="insertVariable('{nama}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-600/30 dark:text-emerald-300 border border-slate-200 dark:border-slate-700 transition font-medium">
                            +{nama}
                        </button>
                        <button type="button" @click="insertVariable('{nomor_internet}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-600/30 dark:text-emerald-300 border border-slate-200 dark:border-slate-700 transition font-medium">
                            +{nomor_internet}
                        </button>
                        <button type="button" @click="insertVariable('{periode}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-600/30 dark:text-emerald-300 border border-slate-200 dark:border-slate-700 transition font-medium">
                            +{periode}
                        </button>
                        <button type="button" @click="insertVariable('{nominal}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-600/30 dark:text-emerald-300 border border-slate-200 dark:border-slate-700 transition font-medium">
                            +{nominal}
                        </button>
                        <button type="button" @click="insertVariable('{jatuh_tempo}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-600/30 dark:text-emerald-300 border border-slate-200 dark:border-slate-700 transition font-medium">
                            +{jatuh_tempo}
                        </button>
                        <button type="button" @click="insertVariable('{link_pembayaran}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-600/30 dark:text-emerald-300 border border-slate-200 dark:border-slate-700 transition font-medium">
                            +{link_pembayaran}
                        </button>
                    </div>
                </div>

                <!-- Textarea Editor -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Isi Pesan Broadcast (Customizable)</label>
                    <textarea x-model="customPesan" id="broadcastPesanTextarea" rows="8" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-sans leading-relaxed"></textarea>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Gunakan format markdown WA: *teks cetak tebal*, _teks miring_, ~teks dicoret~.</p>
                </div>

                <!-- WhatsApp Live Smartphone Mockup Preview -->
                <div class="border border-slate-300 dark:border-slate-800 rounded-2xl overflow-hidden bg-[#efeae2] dark:bg-[#0b141a] shadow-inner">
                    <!-- WhatsApp Header -->
                    <div class="bg-[#075e54] dark:bg-[#202c33] px-4 py-2.5 flex items-center gap-3 border-b border-emerald-800 dark:border-slate-800 text-white">
                        <div class="w-8 h-8 rounded-full bg-emerald-700 dark:bg-emerald-600 flex items-center justify-center text-white font-bold text-xs">
                            IMS
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Preview WhatsApp (Sample)</div>
                            <div class="text-[10px] text-emerald-200 dark:text-emerald-400">Online &bull; IMS Official Gateway</div>
                        </div>
                    </div>

                    <!-- WhatsApp Message Body -->
                    <div class="p-4 bg-repeat min-h-[140px] max-h-[220px] overflow-y-auto" style="background-color: #efeae2; background-image: radial-gradient(rgba(0,0,0,0.06) 1px, transparent 0); background-size: 16px 16px;">
                        <div class="bg-[#d9fdd3] text-slate-900 dark:bg-[#005c4b] dark:text-slate-100 p-3 rounded-xl rounded-tl-none max-w-[90%] text-xs shadow-xs leading-relaxed whitespace-pre-wrap font-sans" x-html="formatWaPreview(customPesan)"></div>
                    </div>
                </div>

                <!-- Mass Broadcast Trigger Action Button -->
                <button @click="triggerBulkBroadcast()" type="button" :disabled="selectedTargets.length === 0" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-40 text-white font-bold text-xs shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span>KIRIM BROADCAST MASSAL KE <span x-text="selectedTargets.length">0</span> PELANGGAN</span>
                </button>
            </div>

        </div>

    </div>

    <!-- Modal Single Send Confirmation -->
    <div x-show="showSingleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="showSingleModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">📱</span>
                    <span>Kirim Broadcast WA Per Orangan</span>
                </h3>
                <button @click="showSingleModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <div class="space-y-3 text-xs text-slate-700 dark:text-slate-300">
                <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-xl border border-slate-200 dark:border-slate-700/60">
                    <div class="text-slate-500 dark:text-slate-400">Penerima:</div>
                    <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="singleTarget.nama"></div>
                    <div class="text-emerald-600 dark:text-emerald-400 font-mono" x-text="'ID: ' + singleTarget.noInternet + ' | HP: ' + singleTarget.hp"></div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pratinjau Pesan yang Akan Dikirim:</label>
                    <div class="bg-[#efeae2] dark:bg-[#0b141a] p-3 rounded-xl border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-slate-100 whitespace-pre-wrap max-h-48 overflow-y-auto" x-text="singleTarget.renderedPesan"></div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button @click="showSingleModal = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                    Batal
                </button>
                <button @click="submitSingleSend()" type="button" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 flex items-center gap-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span>Buka WhatsApp & Kirim</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Queue Dispatcher -->
    <div x-show="showBulkModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-5" @click.away="if(!isSendingBulk) showBulkModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">🚀</span>
                    <span>Dispatcher Broadcast Massal WhatsApp</span>
                </h3>
                <button @click="if(!isSendingBulk) showBulkModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-slate-700 dark:text-slate-300">Kemajuan Pengiriman Queue:</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-mono" x-text="bulkProgressPercent + '% (' + bulkSentCount + '/' + bulkQueue.length + ')'"></span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full transition-all duration-300" :style="'width: ' + bulkProgressPercent + '%'"></div>
                </div>
            </div>

            <!-- Active Queue Table -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] uppercase">
                        <tr>
                            <th class="p-2.5">No</th>
                            <th class="p-2.5">Pelanggan</th>
                            <th class="p-2.5">No HP</th>
                            <th class="p-2.5 text-center">Status</th>
                            <th class="p-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="(item, idx) in bulkQueue" :key="idx">
                            <tr :class="idx === currentBulkIndex ? 'bg-emerald-50 dark:bg-emerald-500/10' : ''">
                                <td class="p-2.5 text-slate-500 dark:text-slate-400 font-mono" x-text="idx + 1"></td>
                                <td class="p-2.5 font-bold text-slate-900 dark:text-white" x-text="item.nama_penerima"></td>
                                <td class="p-2.5 font-mono text-emerald-600 dark:text-emerald-400" x-text="item.nomor_hp"></td>
                                <td class="p-2.5 text-center">
                                    <span x-show="item.status === 'sent'" class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold">Terkirim WA</span>
                                    <span x-show="item.status === 'pending'" class="px-2 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">Pending</span>
                                    <span x-show="item.status === 'active'" class="px-2 py-0.5 rounded text-[10px] bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 font-bold animate-pulse">Siap Kirim</span>
                                </td>
                                <td class="p-2.5 text-right">
                                    <a :href="item.wa_url" target="_blank" @click="item.status = 'sent'; updateBulkProgress();" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-semibold inline-block">
                                        Buka WA
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Modal Action Footer -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800">
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Klik "Buka WA" pada tiap baris atau gunakan tombol Otomatis.</span>
                <div class="flex items-center gap-2">
                    <button @click="showBulkModal = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                        Selesai / Tutup
                    </button>
                    <button @click="openNextBulkItem()" type="button" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 shadow">
                        <span>Buka WA Berikutnya &raquo;</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kelola Template Broadcast -->
    <div x-show="showTemplateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4" @click.away="showTemplateModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">⚙️</span>
                    <span>Kelola Template Broadcast Custom</span>
                </h3>
                <button @click="showTemplateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <form action="{{ route('admin.broadcast.template.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Template</label>
                    <input type="text" name="nama_template" required placeholder="cth: Pengumuman Promo Internet" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                    <select name="kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="jatuh_tempo">Peringatan Jatuh Tempo</option>
                        <option value="pengumuman">Pengumuman Jaringan</option>
                        <option value="custom">Pesan Custom / Umum</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pesan Template</label>
                    <textarea name="pesan" rows="6" required placeholder="Gunakan placeholder {nama}, {nomor_internet}, {periode}, {nominal}, {jatuh_tempo}, {link_pembayaran}..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button @click="showTemplateModal = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30">
                        Simpan Template Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function broadcastApp() {
    return {
        selectedTargets: [],
        selectedTemplateId: "{{ $defaultTemplate->id ?? '' }}",
        customPesan: `{!! addslashes($defaultTemplate->pesan ?? '') !!}`,
        showSingleModal: false,
        showBulkModal: false,
        showTemplateModal: false,
        singleTarget: {
            noInternet: '',
            nama: '',
            hp: '',
            renderedPesan: ''
        },
        bulkQueue: [],
        currentBulkIndex: 0,
        bulkSentCount: 0,
        bulkProgressPercent: 0,
        isSendingBulk: false,

        init() {
            // Auto init
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedTargets = [
                    @foreach($pelangganList as $item)
                        "{{ $item->nomor_internet }}",
                    @endforeach
                ];
            } else {
                this.selectedTargets = [];
            }
        },

        loadSelectedTemplate() {
            const selectEl = document.querySelector('select[x-model="selectedTemplateId"]');
            if (selectEl && selectEl.selectedIndex >= 0) {
                const opt = selectEl.options[selectEl.selectedIndex];
                const rawPesan = opt.getAttribute('data-pesan');
                if (rawPesan) {
                    this.customPesan = rawPesan;
                }
            }
        },

        insertVariable(varTag) {
            const textarea = document.getElementById('broadcastPesanTextarea');
            if (textarea) {
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || 0;
                const text = this.customPesan;
                this.customPesan = text.substring(0, start) + varTag + text.substring(end);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + varTag.length, start + varTag.length);
                });
            } else {
                this.customPesan += ' ' + varTag;
            }
        },

        formatWaPreview(text) {
            if (!text) return '<i>Pratinjau pesan WhatsApp akan muncul di sini...</i>';
            let formatted = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
                .replace(/_(.*?)_/g, '<em>$1</em>')
                .replace(/~(.*?)~/g, '<del>$1</del>')
                .replace(/\n/g, '<br>');
            return formatted;
        },

        openSingleSendModal(noInternet, nama, hp) {
            this.singleTarget.noInternet = noInternet;
            this.singleTarget.nama = nama;
            this.singleTarget.hp = hp;
            
            fetch("{{ route('admin.broadcast.preview') }}?nomor_internet=" + encodeURIComponent(noInternet) + "&pesan=" + encodeURIComponent(this.customPesan))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.singleTarget.renderedPesan = data.rendered_message;
                    } else {
                        this.singleTarget.renderedPesan = this.customPesan;
                    }
                    this.showSingleModal = true;
                })
                .catch(() => {
                    this.singleTarget.renderedPesan = this.customPesan;
                    this.showSingleModal = true;
                });
        },

        submitSingleSend() {
            fetch("{{ route('admin.broadcast.send-single') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    nomor_internet: this.singleTarget.noInternet,
                    nama_penerima: this.singleTarget.nama,
                    nomor_hp: this.singleTarget.hp,
                    pesan: this.singleTarget.renderedPesan,
                    kategori: "jatuh_tempo"
                })
            })
            .then(res => res.json())
            .then(data => {
                this.showSingleModal = false;
                if (data.success && data.wa_url) {
                    window.open(data.wa_url, '_blank');
                }
            })
            .catch(err => {
                alert('Gagal memproses kirim WhatsApp!');
            });
        },

        triggerBulkBroadcast() {
            if (this.selectedTargets.length === 0) {
                alert('Pilih setidaknya 1 pelanggan untuk broadcast massal!');
                return;
            }

            fetch("{{ route('admin.broadcast.send-bulk') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    targets: this.selectedTargets,
                    pesan: this.customPesan,
                    kategori: "jatuh_tempo"
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.queue) {
                    this.bulkQueue = data.queue.map(q => ({
                        ...q,
                        status: 'pending'
                    }));
                    if (this.bulkQueue.length > 0) {
                        this.bulkQueue[0].status = 'active';
                    }
                    this.currentBulkIndex = 0;
                    this.bulkSentCount = 0;
                    this.bulkProgressPercent = 0;
                    this.showBulkModal = true;
                }
            })
            .catch(err => {
                alert('Terjadi kesalahan saat memproses data broadcast massal!');
            });
        },

        updateBulkProgress() {
            const sent = this.bulkQueue.filter(q => q.status === 'sent').length;
            this.bulkSentCount = sent;
            this.bulkProgressPercent = Math.round((sent / this.bulkQueue.length) * 100);
        },

        openNextBulkItem() {
            if (this.currentBulkIndex < this.bulkQueue.length) {
                const item = this.bulkQueue[this.currentBulkIndex];
                item.status = 'sent';
                window.open(item.wa_url, '_blank');
                
                this.currentBulkIndex++;
                if (this.currentBulkIndex < this.bulkQueue.length) {
                    this.bulkQueue[this.currentBulkIndex].status = 'active';
                }
                this.updateBulkProgress();
            }
        },

        openModalTemplate() {
            this.showTemplateModal = true;
        }
    };
}
</script>
@endsection
