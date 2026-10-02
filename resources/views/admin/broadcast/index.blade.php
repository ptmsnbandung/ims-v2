@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="broadcastApp()">
    
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-1.5">
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
            
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                Broadcast WhatsApp Pelanggan
            </h1>
            
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Kirim pengingat jatuh tempo tagihan atau pengumuman resmi ke WhatsApp pelanggan (Per Orangan maupun Massal).
            </p>
        </div>

        <div class="relative z-10 flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('admin.broadcast.history') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Riwayat Broadcast Log</span>
            </a>
        </div>
    </div>

    <!-- Quick User Guide (Langkah Penggunaan Sederhana) -->
    <div class="bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-blue-500/10 border border-emerald-500/20 rounded-2xl p-4 text-slate-800 dark:text-slate-200 text-xs">
        <div class="font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5 mb-2 text-sm">
            <span>💡 Petunjuk Mudah Penggunaan Fitur Broadcast WA:</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="flex items-start gap-2.5 bg-white dark:bg-slate-900 p-3 rounded-xl border border-emerald-200 dark:border-slate-800 shadow-xs">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">1</span>
                <div>
                    <div class="font-bold text-slate-900 dark:text-white">Pilih Template Pesan</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Pilih template Jatuh Tempo, Pengumuman, atau ketik pesan kustom.</div>
                </div>
            </div>

            <div class="flex items-start gap-2.5 bg-white dark:bg-slate-900 p-3 rounded-xl border border-emerald-200 dark:border-slate-800 shadow-xs">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">2</span>
                <div>
                    <div class="font-bold text-slate-900 dark:text-white">Pilih Target Pelanggan</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Gunakan filter status tagihan &amp; centang pelanggan yang ingin dikirimkan.</div>
                </div>
            </div>

            <div class="flex items-start gap-2.5 bg-white dark:bg-slate-900 p-3 rounded-xl border border-emerald-200 dark:border-slate-800 shadow-xs">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">3</span>
                <div>
                    <div class="font-bold text-slate-900 dark:text-white">Kirim WhatsApp</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Klik tombol <b>Kirim WA</b> (per orang) atau <b>Broadcast Massal</b>.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Mendekati Jatuh Tempo / Belum Lunas -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Belum Bayar / Jatuh Tempo</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($totalUnpaidCount) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pelanggan perlu penagihan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Total Target Pelanggan -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total Pelanggan Terfilter</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($totalTargetCount) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sesuai kriteria di bawah</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Broadcast Terkirim Hari Ini -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Terkirim Hari Ini</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($sentTodayCount) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Status dikirim ke WA</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Total Log Broadcast -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">Total Akumulasi Log</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($totalSentLog) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Total riwayat broadcast</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- LANGKAH 1: PENGATURAN PESAN BROADCAST (Template & Editor) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-bold text-sm flex items-center justify-center">1</span>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Pilih Template &amp; Isi Pesan Broadcast</h2>
            </div>
            <button @click="showPreview = !showPreview" type="button" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 shrink-0">
                <span x-text="showPreview ? '🙈 Sembunyikan Pratinjau' : '👁️ Tampilkan Pratinjau WA'"></span>
            </button>
        </div>

        <!-- Row 1: Template selector + var buttons + textarea -->
        <div class="space-y-4">
            <!-- Template Dropdown -->
            <div>
                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Pilih Template Pesan Siap Pakai:</label>
                <select x-model="selectedTemplateId" @change="loadSelectedTemplate()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-semibold">
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" data-pesan="{{ addslashes($tpl->pesan) }}" data-kategori="{{ $tpl->kategori }}">
                            📌 {{ $tpl->nama_template }} {{ $tpl->is_default ? '(Default)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Variable Quick Insert Buttons -->
            <div>
                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-2">Sisipkan Data Pelanggan ke Pesan:</label>
                <div class="flex flex-wrap gap-2 text-[11px]">
                    @foreach(['{nama}', '{nomor_internet}', '{periode}', '{nominal}', '{jatuh_tempo}', '{link_pembayaran}', '{paket}', '{alamat}'] as $var)
                    <button type="button" @click="insertVariable('{{ $var }}')"
                        class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 font-bold transition">
                        + {{ $var }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Textarea Message Editor -->
            <div>
                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Isi Pesan WhatsApp yang Akan Dikirim:</label>
                <textarea x-model="customPesan" id="broadcastPesanTextarea" rows="7"
                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-sans leading-relaxed resize-y"></textarea>
            </div>
        </div>

        <!-- Row 2: WhatsApp Preview (collapsible) -->
        <div x-show="showPreview" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-2">
                📱 Pratinjau Tampilan Pesan di WhatsApp:
            </label>
            <div class="border border-slate-300 dark:border-slate-800 rounded-2xl overflow-hidden bg-[#efeae2] dark:bg-[#0b141a] shadow-inner max-w-xl">
                <!-- WA Header Bar -->
                <div class="bg-[#075e54] dark:bg-[#202c33] px-4 py-2.5 flex items-center gap-3 border-b border-emerald-800 dark:border-slate-800 text-white">
                    <div class="w-8 h-8 rounded-full bg-emerald-700 dark:bg-emerald-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                        IMS
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">IMS Support (Sample Preview)</div>
                        <div class="text-[10px] text-emerald-200 dark:text-emerald-400">Online &bull; WhatsApp Broadcast</div>
                    </div>
                </div>
                <!-- WA Chat Bubble -->
                <div class="p-4 min-h-[120px] max-h-[280px] overflow-y-auto" style="background-color:#efeae2; background-image:radial-gradient(rgba(0,0,0,0.06) 1px, transparent 0); background-size:16px 16px;">
                    <div class="bg-[#d9fdd3] text-slate-900 dark:bg-[#005c4b] dark:text-slate-100 p-3 rounded-xl rounded-tl-none max-w-[85%] text-xs shadow-xs leading-relaxed whitespace-pre-wrap font-sans"
                        x-html="formatWaPreview(customPesan)">
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- LANGKAH 2 & 3: DAFTAR PELANGGAN & PENGIRIMAN -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-bold text-sm flex items-center justify-center">2</span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Pilih Target Pelanggan &amp; Eksekusi Kirim</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Centang pelanggan pada tabel, lalu klik tombol Broadcast Massal di sebelah kanan.</p>
                </div>
            </div>

            <!-- LANGKAH 3 ACTION BUTTON -->
            <button @click="triggerBulkBroadcast()" type="button" :disabled="selectedTargets.length === 0" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-40 text-white font-extrabold text-xs shadow-lg shadow-emerald-600/20 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span>3. KIRIM BROADCAST MASSAL (<span x-text="selectedTargets.length">0</span> PELANGGAN DIPILIH)</span>
            </button>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="{{ route('admin.broadcast') }}" class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-xl border border-slate-200 dark:border-slate-700/60 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                
                <!-- Filter Status Tagihan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Filter Status Pelanggan:</label>
                    <select name="status_tagihan" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ $selectedStatusTagihan == 'all' ? 'selected' : '' }}>🌐 Semua Pelanggan (Default)</option>
                        <option value="unpaid" {{ $selectedStatusTagihan == 'unpaid' ? 'selected' : '' }}>⚠️ Belum Lunas / Mendekati Jatuh Tempo</option>
                        <option value="paid" {{ $selectedStatusTagihan == 'paid' ? 'selected' : '' }}>✅ Lunas (PAID)</option>
                        <option value="isolir" {{ $selectedStatusTagihan == 'isolir' ? 'selected' : '' }}>⛔ Isolir / Suspend</option>
                    </select>
                </div>

                <!-- Filter Periode Bulan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Filter Bulan Tagihan:</label>
                    <select name="bulan" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
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
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Filter Tahun Tagihan:</label>
                    <select name="tahun" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ $selectedTahun == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pencarian Pelanggan:</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, ID internet, HP..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="inline-flex items-center gap-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 cursor-pointer">
                    <input type="checkbox" @change="toggleSelectAll($event)" class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                    <span>Centang / Pilih Semua Pelanggan di Halaman Ini</span>
                </label>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition shadow-xs">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.broadcast') }}" class="px-3 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition">
                        Reset Filter
                    </a>
                </div>
            </div>
        </form>

        <!-- Customer Table -->
        <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-bold">
                        <tr>
                            <th class="p-3.5 w-10 text-center">Pilih</th>
                            <th class="p-3.5">Nama Pelanggan</th>
                            <th class="p-3.5">Nomor Internet &amp; HP</th>
                            <th class="p-3.5">Wilayah &amp; Alamat</th>
                            <th class="p-3.5">Status Tagihan</th>
                            <th class="p-3.5 text-right">Nominal</th>
                            <th class="p-3.5 text-center">Aksi Kirim Per Orang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($pelangganList as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="p-3.5 text-center">
                                    <input type="checkbox" value="{{ $item->nomor_internet }}" x-model="selectedTargets" class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                </td>

                                <td class="p-3.5 font-bold text-slate-900 dark:text-white text-xs">
                                    {{ $item->nama_pelanggan }}
                                </td>

                                <td class="p-3.5">
                                    <div class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">{{ $item->nomor_internet }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">📱 {{ $item->nomor_hp ?? '-' }}</div>
                                </td>

                                <td class="p-3.5 text-[11px] text-slate-600 dark:text-slate-400">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $item->nama_kota_pasang ?? 'Area IMS' }}</div>
                                    <div class="truncate max-w-[200px]">{{ $item->alamat_pasang ?? ($item->alamat_p ?? '-') }}</div>
                                </td>

                                <td class="p-3.5">
                                    @if($item->status_bill_lay == '15')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                            <span>✅ LUNAS</span>
                                        </span>
                                    @elseif(in_array($item->status_bill_lay, ['13', '14']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                            <span>⚠️ JATUH TEMPO</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            <span>{{ $item->status_bill_lay ?: 'Aktif' }}</span>
                                        </span>
                                    @endif
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
                                <td colspan="7" class="p-8 text-center text-slate-400 dark:text-slate-500">
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
</div>

<script>
function broadcastApp() {
    return {
        selectedTargets: [],
        selectedTemplateId: "{{ $defaultTemplate->id ?? '' }}",
        customPesan: `{!! addslashes($defaultTemplate->pesan ?? '') !!}`,
        showSingleModal: false,
        showBulkModal: false,
        showPreview: true,
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
                alert('Pilih setidaknya 1 pelanggan pada tabel untuk broadcast massal!');
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
        }
    };
}
</script>
@endsection
