@extends('layouts.app')

@section('page_title', 'Kelola & Kirim Custom Email')

@section('content')
<div class="space-y-6" x-data="emailManagement()">
    <!-- Top Breadcrumbs & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                <span>IMS</span>
                <span>&rsaquo;</span>
                <span>Admin</span>
                <span>&rsaquo;</span>
                <span class="text-sky-600 dark:text-sky-400 font-semibold">Kelola Email</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 flex items-center justify-center text-sky-600 dark:text-sky-400 shadow-xs">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Kelola &amp; Kirim Custom Email</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Kirim email pengumuman, pemeliharaan, atau pesan khusus ke pelanggan via Hostinger Mail API.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center gap-2">
            <button @click="openTestModal()"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                <svg class="w-4 h-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span>Kirim Uji Coba</span>
            </button>
            <a href="{{ route('admin.email.history') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow-md shadow-sky-600/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Semua Riwayat Log</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Stat Cards & Sisa Limit Kirim -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Pelanggan -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pelanggan</p>
                <p class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ number_format($kpi['total_customers'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($kpi['with_email'], 0, ',', '.') }}</span> memiliki email
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- 2. Sisa Limit Kirim Hari Ini (Hostinger Mail API Quota) -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sisa Limit Hari Ini</p>
                    <p class="text-2xl font-extrabold {{ $kpi['remaining_today'] > 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500' }}">
                        {{ number_format($kpi['remaining_today'], 0, ',', '.') }}
                        <span class="text-xs font-medium text-slate-400">/ {{ number_format($kpi['daily_limit'], 0, ',', '.') }}</span>
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $kpi['remaining_today'] > 100 ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-500/20' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 border border-amber-200 dark:border-amber-500/20' }} flex items-center justify-center">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="mt-2.5">
                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $kpi['remaining_today'] > 100 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                         style="width: {{ 100 - $kpi['usage_percent'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                    <span>{{ 100 - $kpi['usage_percent'] }}% kuota tersedia</span>
                    <span>Reset 24 Jam</span>
                </div>
            </div>
        </div>

        <!-- 3. Email Terkirim Hari Ini & Kumulatif -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Terkirim Hari Ini</p>
                <p class="text-2xl font-extrabold text-sky-600 dark:text-sky-400">{{ number_format($kpi['sent_today'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                    Total kumulatif: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ number_format($kpi['total_sent'], 0, ',', '.') }}</span>
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 flex items-center justify-center text-sky-600 dark:text-sky-400">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </div>
        </div>

        <!-- 4. Gateway Status -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hostinger Mail API</p>
                @if($isHostingerReady)
                    <p class="text-base font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Terhubung (Aktif)
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[140px]">{{ config('services.hostinger_mail.from', 'info@ptmsn.co.id') }}</p>
                @else
                    <p class="text-base font-bold text-amber-500 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        Fallback SMTP
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Token API belum disetel di .env</p>
                @endif
            </div>
            <div class="w-11 h-11 rounded-xl {{ $isHostingerReady ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-500/20' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 border border-amber-200 dark:border-amber-500/20' }} flex items-center justify-center">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Workspace: Stacked Full-Width (1. Formulir Pesan Email di Atas, 2. Pilih Pelanggan di Bawah) -->
    <div class="space-y-6">
        <!-- 1. TOP: Email Composer (Formulir Pesan Email) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Formulir Pesan Email</h2>
                    <p class="text-xs text-slate-500">Tulis isi pengumuman atau pilih preset template resmi sebelum memilih pelanggan penerima.</p>
                </div>

                <!-- Template Preset Selector -->
                <select x-model="selectedPresetId"
                        @change="applyPreset()"
                        class="text-xs py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-medium text-slate-800 dark:text-slate-200 sm:min-w-[280px]">
                    <option value="">-- Pilih Template Preset --</option>
                    @foreach($presets as $p)
                        <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="p-5 space-y-4">
                <!-- Sisa Limit Kirim Alert Banner -->
                <div class="p-3 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $kpi['remaining_today'] > 50 ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <span class="text-slate-700 dark:text-slate-300">
                            Sisa limit Hostinger hari ini: <strong class="text-sky-900 dark:text-sky-200 font-bold">{{ number_format($kpi['remaining_today'], 0, ',', '.') }}</strong> / {{ number_format($kpi['daily_limit'], 0, ',', '.') }} email
                        </span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400"
                          :class="selectedTargets.length > {{ $kpi['remaining_today'] }} ? 'text-rose-600 dark:text-rose-400 font-bold' : ''"
                          x-text="selectedTargets.length > 0 ? ('Terpilih: ' + selectedTargets.length + ' pelanggan') : 'Maks 100/batch'"></span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Left Inputs (Subjek, Badge, Tombol Aksi) -->
                    <div class="lg:col-span-5 space-y-3.5">
                        <!-- Subjek Email -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Subjek Email <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="form.subject"
                                   placeholder="Contoh: Pemberitahuan Pemeliharaan Jaringan - PT Media Solusi Network"
                                   class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>

                        <!-- Badge Title Pill (Header) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Label / Badge Header
                                </label>
                                <input type="text"
                                       x-model="form.badge_text"
                                       placeholder="Contoh: PEMBERITAHUAN RESMI"
                                       class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Teks Tombol Aksi (Opsional)
                                </label>
                                <input type="text"
                                       x-model="form.action_text"
                                       placeholder="Contoh: Buka Portal Tagihan"
                                       class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                        </div>

                        <!-- URL Tombol Aksi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                URL Tombol Aksi (Opsional)
                            </label>
                            <input type="url"
                                   x-model="form.action_url"
                                   placeholder="https://portal.ptmsn.co.id"
                                   class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>

                    <!-- Right Textarea (Isi Pesan Email) -->
                    <div class="lg:col-span-7 flex flex-col">
                        <div class="flex flex-wrap items-center justify-between gap-1 mb-1.5">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Isi Pesan Email <span class="text-rose-500">*</span>
                            </label>
                            <!-- Variabel Tags Helper -->
                            <div class="flex flex-wrap items-center gap-1 text-[11px] text-slate-400">
                                <span>Variabel:</span>
                                <button type="button" @click="insertTag('{nama_pelanggan}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{nama_pelanggan}</button>
                                <button type="button" @click="insertTag('{nomor_internet}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{nomor_internet}</button>
                                <button type="button" @click="insertTag('{paket}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{paket}</button>
                                <button type="button" @click="insertTag('{periode}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{periode}</button>
                                <button type="button" @click="insertTag('{nominal}')" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-sky-100 dark:hover:bg-sky-950 hover:text-sky-600 font-mono cursor-pointer">{nominal}</button>
                            </div>
                        </div>
                        <textarea x-model="form.message"
                                  rows="6"
                                  placeholder="Tuliskan isi pesan pengumuman di sini..."
                                  class="w-full flex-1 p-3.5 rounded-xl text-xs leading-relaxed bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 font-sans"></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Gunakan baris baru (Enter) ganda untuk memisahkan paragraf. Variabel akan otomatis digantikan sesuai profil pelanggan.</p>
                    </div>
                </div>
            </div>

            <!-- Composer Footer Actions -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-wrap items-center justify-between gap-3">
                <button type="button"
                        @click="openPreviewModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold transition cursor-pointer">
                    <svg class="w-4 h-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>Pratinjau (Preview)</span>
                </button>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="sendBroadcast()"
                            :disabled="isSending || selectedTargets.length === 0"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-md shadow-sky-600/25 transition cursor-pointer">
                        <template x-if="!isSending">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </template>
                        <template x-if="isSending">
                            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <span x-text="isSending ? 'Memproses...' : 'Kirim ke ' + selectedTargets.length + ' Pelanggan'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. BOTTOM: Customer Selector (Pilih Pelanggan Penerima) -->
        <div id="daftar-pelanggan" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Pilih Pelanggan Penerima</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300"
                              x-text="selectedTargets.length + ' dipilih (Tersimpan Lintas Halaman)'"></span>
                    </div>

                    <!-- Quick Selection Actions -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button @click="toggleSelectAll()"
                                type="button"
                                class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline cursor-pointer">
                            <span x-text="isAllSelected() ? 'Batal Pilih Halaman Ini' : 'Pilih Semua di Halaman Ini'"></span>
                        </button>

                        @if(count($allFilteredTargetIds ?? []) > 0)
                            <span class="text-slate-300 dark:text-slate-700">|</span>
                            <button @click="selectAllFiltered()"
                                    type="button"
                                    class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer">
                                Pilih Semua Hasil Filter ({{ count($allFilteredTargetIds) }} punya email)
                            </button>
                        @endif

                        <template x-if="selectedTargets.length > 0">
                            <div class="inline-flex items-center gap-2">
                                <span class="text-slate-300 dark:text-slate-700">|</span>
                                <button @click="clearSelection()"
                                        type="button"
                                        class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer">
                                    Reset Pilihan
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Filter Toolbar (Seragam dengan Broadcast WA) -->
                @php
                    $bulanList = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];
                    $curYear = (int) date('Y');
                @endphp
                <form method="GET" action="{{ route('admin.email.index') }}#daftar-pelanggan" class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-200 dark:border-slate-700/60 space-y-2.5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-2.5">
                        <!-- 1. Filter Status Tagihan / Layanan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Status Pelanggan:</label>
                            <select name="status"
                                    onchange="this.form.submit()"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Pelanggan</option>
                                <option value="jatuh_tempo" {{ $statusFilter === 'jatuh_tempo' ? 'selected' : '' }}>&#9888; Jatuh Tempo (Belum Lunas)</option>
                                <option value="lunas" {{ $statusFilter === 'lunas' ? 'selected' : '' }}>&#10003; Lunas (Sudah Bayar)</option>
                                <option value="suspend" {{ $statusFilter === 'suspend' ? 'selected' : '' }}>&#9940; Suspend / Isolir</option>
                                <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Pelanggan Aktif</option>
                            </select>
                        </div>

                        <!-- 2. Filter Kepemilikan Email -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Kepemilikan Email:</label>
                            <select name="email_status"
                                    onchange="this.form.submit()"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                <option value="all" {{ ($emailFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua (Punya &amp; Tanpa)</option>
                                <option value="with_email" {{ ($emailFilter ?? 'all') === 'with_email' ? 'selected' : '' }}>&#9993; Punya Email</option>
                                <option value="without_email" {{ ($emailFilter ?? 'all') === 'without_email' ? 'selected' : '' }}>Tanpa Email</option>
                            </select>
                        </div>

                        <!-- 3. Filter Status Terima Email (Sudah / Belum) -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Status Kirim Email:</label>
                            <select name="status_kirim"
                                    onchange="this.form.submit()"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                <option value="all" {{ ($statusKirimFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status Kirim</option>
                                <option value="sent" {{ ($statusKirimFilter ?? '') === 'sent' ? 'selected' : '' }}>&#10003; Sudah Menerima Email</option>
                                <option value="unsent" {{ ($statusKirimFilter ?? '') === 'unsent' ? 'selected' : '' }}>&#9203; Belum Pernah Dikirim</option>
                            </select>
                        </div>

                        <!-- 4. Filter Jumlah / Berapa Kali Menerima Email -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Jumlah Kirim Email:</label>
                            <select name="jumlah_kirim"
                                    onchange="this.form.submit()"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                <option value="all" {{ ($jumlahKirimFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Frekuensi</option>
                                <option value="0" {{ ($jumlahKirimFilter ?? '') === '0' ? 'selected' : '' }}>Belum Pernah (0x)</option>
                                <option value="1" {{ ($jumlahKirimFilter ?? '') === '1' ? 'selected' : '' }}>1x Kirim</option>
                                <option value="2" {{ ($jumlahKirimFilter ?? '') === '2' ? 'selected' : '' }}>2x Kirim</option>
                                <option value="3" {{ ($jumlahKirimFilter ?? '') === '3' ? 'selected' : '' }}>3x Kirim</option>
                                <option value="4" {{ ($jumlahKirimFilter ?? '') === '4' ? 'selected' : '' }}>4x Kirim</option>
                                <option value="5plus" {{ ($jumlahKirimFilter ?? '') === '5plus' ? 'selected' : '' }}>&ge; 5x Kirim (5x ke atas)</option>
                            </select>
                        </div>

                        <!-- 5. Filter Bulan Tagihan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Bulan Tagihan:</label>
                            <select name="bulan"
                                    onchange="this.form.submit()"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                <option value="all" {{ (string)($bulanFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                                @foreach($bulanList as $bKey => $bLabel)
                                    @php $bVal = str_pad((string)$bKey, 2, '0', STR_PAD_LEFT); @endphp
                                    <option value="{{ $bVal }}" {{ (string)($bulanFilter ?? 'all') === $bVal ? 'selected' : '' }}>
                                        {{ $bLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 6. Filter Tahun Tagihan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Tahun Tagihan:</label>
                            <select name="tahun"
                                    onchange="this.form.submit()"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                @for($y = $curYear + 1; $y >= $curYear - 2; $y--)
                                    <option value="{{ $y }}" {{ (string)($tahunFilter ?? $curYear) === (string)$y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- 7. Pencarian Pelanggan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Pencarian Pelanggan:</label>
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="Nama, ID, Email..."
                                   class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 flex-wrap gap-2 border-t border-slate-200/70 dark:border-slate-700/50">
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Menampilkan <strong class="text-slate-700 dark:text-slate-200">{{ $customers->count() }}</strong> dari <strong class="text-slate-700 dark:text-slate-200">{{ number_format($customers->total(), 0, ',', '.') }}</strong> pelanggan sesuai filter
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="submit" class="px-3 py-1 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold transition cursor-pointer">
                                Terapkan
                            </button>
                            @if($search !== '' || $statusFilter !== 'all' || ($emailFilter ?? 'all') !== 'all' || ($statusKirimFilter ?? 'all') !== 'all' || ($jumlahKirimFilter ?? 'all') !== 'all' || ($bulanFilter ?? 'all') !== 'all')
                                <a href="{{ route('admin.email.index') }}#daftar-pelanggan"
                                   class="px-2.5 py-1 rounded-lg bg-rose-100 hover:bg-rose-200 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 text-xs font-semibold transition">
                                    Reset Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Customer List Table -->
            <div class="flex-1 overflow-x-auto divide-y divide-slate-100 dark:divide-slate-800 max-h-[540px] overflow-y-auto">
                @forelse($customers as $c)
                @php
                    $hasEmail = !empty($c->email) && filter_var($c->email, FILTER_VALIDATE_EMAIL);
                    $isSuspend = in_array((string)($c->status_reg ?? ''), ['23', '23.1']);
                    $billStatus = (string)($c->status_bill_lay ?? '');
                    $sentCount = (int)($c->email_sent_count ?? 0);
                    $bulanShort = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
                    ];
                    $periodeLabel = (!empty($c->bulan_tagihan) && !empty($c->tahun_tagihan))
                        ? ($bulanShort[(int)$c->bulan_tagihan] ?? $c->bulan_tagihan) . ' ' . $c->tahun_tagihan
                        : null;
                @endphp
                <label class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition select-none">
                    <input type="checkbox"
                           value="{{ (string) $c->nomor_internet }}"
                           x-model="selectedTargets"
                           @if(!$hasEmail) disabled @endif
                           class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 dark:border-slate-700 {{ !$hasEmail ? 'opacity-30 cursor-not-allowed' : '' }}">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ $c->nama_pelanggan }}
                                </p>
                                <span class="text-[10px] font-mono text-slate-400 shrink-0">#{{ $c->nomor_internet }}</span>
                            </div>

                            <!-- Status Badges + Tombol Pratinjau per Pelanggan -->
                            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                <button type="button"
                                        @click.stop.prevent="openPreviewModal('{{ (string) $c->nomor_internet }}')"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 hover:bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:hover:bg-sky-900/80 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800/60 transition cursor-pointer"
                                        title="Pratinjau tampilan email sesuai data {{ $c->nama_pelanggan }}">
                                    <svg class="w-3 h-3 text-sky-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span>Pratinjau</span>
                                </button>

                                @if($sentCount > 0)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/50"
                                          title="Terakhir dikirim: {{ $c->last_email_sent_at ?? '-' }}">
                                        &#9993; {{ $sentCount }}x Terkirim
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        0x Kirim
                                    </span>
                                @endif

                                @if($isSuspend)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/50">
                                        Suspend
                                    </span>
                                @endif

                                @if($billStatus === '15')
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/50">
                                        Lunas
                                    </span>
                                @elseif(in_array($billStatus, ['13', '14']))
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/50">
                                        Jatuh Tempo
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5">
                            @if($hasEmail)
                                <span class="text-[11px] text-sky-600 dark:text-sky-400 truncate flex items-center gap-1 font-mono">
                                    <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    {{ $c->email }}
                                </span>
                            @else
                                <span class="text-[10px] font-medium text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-1.5 py-0.5 rounded">
                                    Belum ada email
                                </span>
                            @endif

                            @if(!empty($c->nama_kategori_bandwith) || !empty($c->nominal_bandwith))
                                <span class="text-[11px] text-slate-600 dark:text-slate-400 font-medium truncate">
                                    &bull; {{ $c->nama_kategori_bandwith ?? 'LAYANAN' }}
                                    @if(!empty($c->nominal_bandwith))
                                        <strong class="text-slate-900 dark:text-slate-200 font-bold">{{ $c->nominal_bandwith }} Mbps</strong>
                                    @endif
                                </span>
                            @elseif(!empty($c->nama_bandwith))
                                <span class="text-[11px] text-slate-600 dark:text-slate-400 font-medium truncate">&bull; {{ $c->nama_bandwith }}</span>
                            @endif

                            @if($periodeLabel)
                                <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">
                                    &bull; {{ $periodeLabel }}
                                    @if(!empty($c->total_layanan) && (float)$c->total_layanan > 0)
                                        (Rp {{ number_format((float)$c->total_layanan, 0, ',', '.') }})
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                </label>
                @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    Tidak ada data pelanggan yang sesuai dengan filter.
                </div>
                @endforelse
            </div>

            <!-- Pagination & Bottom Action Bar -->
            <div class="p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex-1">
                    {{ $customers->links() }}
                </div>

                <button type="button"
                        @click="sendBroadcast()"
                        :disabled="isSending || selectedTargets.length === 0"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-md shadow-sky-600/25 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span x-text="isSending ? 'Memproses...' : 'Kirim Email ke ' + selectedTargets.length + ' Pelanggan'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Riwayat Pengiriman Terkini (Preview Tabel Bawah) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Riwayat Pengiriman Email Terkini</h3>
                <p class="text-xs text-slate-500">10 aktivitas pengiriman email terakhir ke pelanggan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                @if($recentLogs->where('status', 'failed')->count() > 0)
                    <button type="button"
                            @click="retryAllFailedLogs()"
                            :disabled="isRetryingAll"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" :class="isRetryingAll ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span x-text="isRetryingAll ? 'Memproses Antrean...' : 'Kirim Ulang Semua yang Gagal'"></span>
                    </button>
                @endif
                <a href="{{ route('admin.email.history') }}" class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                    Lihat Semua Riwayat &rarr;
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Penerima</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Subjek</th>
                        <th class="p-3">Pengirim</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3 whitespace-nowrap text-slate-400">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}
                        </td>
                        <td class="p-3 font-semibold text-slate-900 dark:text-white">
                            {{ $log->nama_pelanggan ?? '-' }}
                            @if($log->nomor_internet)
                                <span class="text-[10px] text-slate-400 font-mono">(#{{ $log->nomor_internet }})</span>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-[11px] text-sky-600 dark:text-sky-400">
                            {{ $log->email_tujuan }}
                        </td>
                        <td class="p-3 max-w-xs truncate font-medium text-slate-800 dark:text-slate-200">
                            {{ $log->subjek }}
                        </td>
                        <td class="p-3 text-slate-500">
                            {{ $log->sender_user ?? 'SYSTEM' }}
                        </td>
                        <td class="p-3">
                            @if($log->status === 'sent')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sent
                                </span>
                            @elseif($log->status === 'queued')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span> Queued
                                </span>
                            @else
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal
                                    </span>
                                    <div class="text-[10.5px] text-rose-600 dark:text-rose-400 bg-rose-50/90 dark:bg-rose-950/40 border border-rose-200/70 dark:border-rose-800/50 rounded-lg px-2 py-1 max-w-xs leading-snug break-words">
                                        <strong class="font-bold">Alasan:</strong>
                                        {{ $log->error_message ?: 'Terkena batas kecepatan kirim (Rate Limit) atau koneksi ke server email terputus.' }}
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-2">
                                @if($log->status === 'failed')
                                    <button type="button"
                                            @click="retryFailedLog({{ $log->id }})"
                                            :disabled="retryingLogId === {{ $log->id }} || isRetryingAll"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:hover:bg-amber-900/70 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60 text-[11px] font-bold disabled:opacity-50 transition cursor-pointer"
                                            title="Kirim ulang email yang gagal ini">
                                        <svg class="w-3 h-3" :class="retryingLogId === {{ $log->id }} ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        <span x-text="retryingLogId === {{ $log->id }} ? 'Mengantrekan...' : 'Kirim Ulang'"></span>
                                    </button>
                                @endif
                                <button @click="showLogDetail({{ $log->id }})"
                                        class="text-xs font-semibold text-slate-500 hover:text-sky-600 cursor-pointer">
                                    Lihat Pesan
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-400 text-xs">
                            Belum ada riwayat pengiriman custom email.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Live Preview Modal (Teleport ke body agar selalu tepat di tengah layar penuh) -->
    <template x-teleport="body">
        <div x-show="previewModalOpen"
             x-cloak
             @keydown.escape.window="previewModalOpen = false"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 14px; box-sizing: border-box;">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl"
                 style="width: 100%; max-width: 720px; height: 88vh; max-height: 780px; display: flex; flex-direction: column; overflow: hidden; margin: auto;"
                 @click.away="previewModalOpen = false">

                <!-- Modal Header (Pinned) -->
                <div class="px-4 py-2.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 bg-white dark:bg-slate-900"
                     style="flex-shrink: 0;">
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-sky-50 dark:bg-sky-950/70 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </span>
                        <span x-text="previewMode === 'log' ? 'Detail Riwayat Pesan Email' : 'Pratinjau Email Pelanggan'"></span>
                    </h3>
                    <button type="button"
                            @click="previewModalOpen = false"
                            class="w-7 h-7 rounded-lg flex items-center justify-center bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/70 dark:text-slate-300 dark:hover:text-rose-400 font-bold text-base transition cursor-pointer"
                            title="Tutup">&times;</button>
                </div>

                <!-- Bar Info Amplop Email & Pemilih Pelanggan (Pinned & Compact) -->
                <template x-if="previewMode === 'composer'">
                    <div class="px-4 py-2.5 bg-slate-50/90 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 space-y-1.5 text-xs"
                         style="flex-shrink: 0;">
                        <!-- Baris 1: Pilih Pelanggan + Badge Rincian -->
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 shrink-0 w-12">Kepada:</span>
                                <select x-model="previewCustomerId"
                                        @change="openPreviewModal(previewCustomerId)"
                                        class="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                    <template x-for="cust in pageCustomers" :key="cust.nomor_internet">
                                        <option :value="cust.nomor_internet" x-text="cust.nama_pelanggan + ' (#' + cust.nomor_internet + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <template x-if="previewCustomer">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200/70 dark:border-sky-800/60">
                                        &#9993; <span x-text="previewCustomer.email || 'Belum ada email'"></span>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-200/70 text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                          x-text="previewCustomer.paket"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/60"
                                          x-text="previewCustomer.periode + ' • ' + previewCustomer.nominal"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Baris 2: Subjek Email -->
                        <template x-if="previewCustomer">
                            <div class="flex items-center gap-2 pt-1 border-t border-slate-200/70 dark:border-slate-700/60">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 shrink-0 w-12">Subjek:</span>
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="previewSubject"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Area Konten Email (Single Clean Scrollbar) -->
                <div class="bg-slate-100 dark:bg-slate-950"
                     style="flex: 1 1 auto; min-height: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <template x-if="previewMode === 'composer'">
                        <iframe :srcdoc="previewHtml"
                                title="Pratinjau Email"
                                style="width: 100%; flex: 1 1 auto; height: 100%; border: 0; display: block; background-color: #f1f5f9;"></iframe>
                    </template>
                    <template x-if="previewMode === 'log'">
                        <div class="p-4 space-y-3" style="flex: 1 1 auto; overflow-y: auto;">
                            <template x-if="previewLog">
                                <div class="p-3.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <span class="text-slate-400">Penerima:</span>
                                            <strong class="text-slate-900 dark:text-white" x-text="previewLog.nama_pelanggan || '-'"></strong>
                                            <span class="font-mono text-sky-600 dark:text-sky-400 ml-1" x-text="'(' + (previewLog.email_tujuan || '-') + ')'"></span>
                                        </div>
                                        <div>
                                            <template x-if="previewLog.status === 'sent'">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Terkirim (Sent)
                                                </span>
                                            </template>
                                            <template x-if="previewLog.status === 'queued'">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span> Dalam Antrean (Queued)
                                                </span>
                                            </template>
                                            <template x-if="previewLog.status === 'failed'">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal Terkirim
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
                                        <span class="text-slate-400">Subjek:</span>
                                        <strong class="text-slate-900 dark:text-white" x-text="previewLog.subjek || '-'"></strong>
                                    </div>

                                    <!-- Kotak Alasan Kegagalan -->
                                    <template x-if="previewLog.status === 'failed' || previewLog.error_message">
                                        <div class="mt-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/70 text-rose-700 dark:text-rose-300 text-xs">
                                            <div class="font-bold flex items-center gap-1.5 mb-1 text-rose-800 dark:text-rose-200">
                                                <svg class="w-4 h-4 shrink-0 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                <span>Alasan Kegagalan Pengiriman:</span>
                                            </div>
                                            <p class="leading-relaxed break-words font-medium"
                                               x-text="previewLog.error_message || 'Terkena batas kecepatan kirim (Rate Limit) atau koneksi ke server email terputus saat pengiriman massal.'"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-xs leading-relaxed text-slate-700 dark:text-slate-200"
                                 x-html="previewHtml"></div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer (Pinned) -->
                <div class="px-4 py-2.5 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-wrap items-center justify-between gap-2"
                     style="flex-shrink: 0;">
                    <div class="flex items-center gap-2">
                        <template x-if="previewMode === 'composer' && previewCustomer && previewCustomer.email && previewCustomer.email.includes('@')">
                            <button type="button"
                                    @click="sendSingleFromPreview()"
                                    :disabled="isSending"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                                <span x-text="isSending ? 'Mengirim...' : 'Kirim ke ' + previewCustomer.nama_pelanggan"></span>
                            </button>
                        </template>

                        <template x-if="previewMode === 'log' && previewLog && previewLog.status === 'failed'">
                            <button type="button"
                                    @click="retryFailedLog(previewLog.id)"
                                    :disabled="retryingLogId === previewLog.id || isRetryingAll"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" :class="retryingLogId === previewLog.id ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span x-text="retryingLogId === previewLog.id ? 'Mengantrekan...' : 'Kirim Ulang Email Ini'"></span>
                            </button>
                        </template>
                    </div>

                    <button type="button"
                            @click="previewModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold cursor-pointer">
                        Tutup Pratinjau
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal 2: Test Email Send Modal (Teleport ke body) -->
    <template x-teleport="body">
        <div x-show="testModalOpen"
             x-cloak
             @keydown.escape.window="testModalOpen = false"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden"
                 style="margin: auto;"
                 @click.away="testModalOpen = false">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kirim Uji Coba Email</h3>
                    <button @click="testModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg cursor-pointer">&times;</button>
                </div>

                <div class="p-5 space-y-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Kirim template email yang sedang Anda susun ke alamat email Anda sendiri terlebih dahulu untuk melihat hasil tampilannya di inbox asli.
                    </p>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Alamat Email Tujuan Uji Coba <span class="text-rose-500">*</span>
                        </label>
                        <input type="email"
                               x-model="testEmail"
                               placeholder="nama@gmail.com"
                               class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-end gap-2">
                    <button @click="testModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button @click="submitTestSend()"
                            :disabled="isTestSending || !testEmail"
                            class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold transition cursor-pointer">
                        <span x-text="isTestSending ? 'Mengirim...' : 'Kirim Sekarang'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal 3: Konfirmasi Pengiriman Email (Menggantikan Browser confirm() Bawaan) -->
    <template x-teleport="body">
        <div x-show="confirmModalOpen"
             x-cloak
             @keydown.escape.window="if (!isSending) confirmModalOpen = false"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 100000; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden"
                 style="margin: auto;"
                 @click.away="if (!isSending) confirmModalOpen = false">

                <!-- Top Header Banner -->
                <div class="p-5 pb-4 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/80 border border-sky-200/70 dark:border-sky-800/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mx-auto mb-3 shadow-xs">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                        Konfirmasi Pengiriman Email
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pastikan detail pesan dan jumlah penerima di bawah ini sudah sesuai sebelum dikirim.
                    </p>
                </div>

                <!-- Summary Detail Box -->
                <div class="px-5 pb-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700/60 space-y-2 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Target Penerima:</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60"
                                  x-text="confirmPayload.label"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Label Header:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate max-w-[220px]"
                                  x-text="form.badge_text || 'PEMBERITAHUAN RESMI'"></span>
                        </div>
                        <div class="pt-1.5 border-t border-slate-200/70 dark:border-slate-700/60">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Subjek Email:</span>
                            <p class="font-bold text-slate-900 dark:text-white leading-snug line-clamp-2"
                               x-text="form.subject"></p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-800/40 text-[11px] text-amber-700 dark:text-amber-300">
                        <svg class="w-4 h-4 shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Tugas pengiriman akan langsung diproses otomatis di latar belakang.</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/50 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="confirmModalOpen = false"
                            :disabled="isSending"
                            class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 text-slate-700 dark:text-slate-300 text-xs font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="executeSendEmail()"
                            :disabled="isSending"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold shadow-md shadow-sky-600/25 transition cursor-pointer">
                        <template x-if="!isSending">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </template>
                        <template x-if="isSending">
                            <svg class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <span x-text="isSending ? 'Memproses Antrean...' : 'Ya, Kirim Sekarang'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

@php
    $pageCustomersJson = $customers->map(function ($c) {
        $kat = trim((string) ($c->nama_kategori_bandwith ?? ''));
        $nom = trim((string) ($c->nominal_bandwith ?? ''));
        if ($kat !== '' || $nom !== '') {
            $paketLabel = trim(($kat !== '' ? $kat : 'LAYANAN') . ($nom !== '' && $nom !== '0' ? ' ' . $nom . ' Mbps' : ''));
        } else {
            $paketLabel = (string) ($c->nama_bandwith ?? 'LAYANAN');
        }

        return [
            'nomor_internet' => (string) ($c->nomor_internet ?? ''),
            'nama_pelanggan' => (string) ($c->nama_pelanggan ?? 'Pelanggan'),
            'email'          => (string) ($c->email ?? ''),
            'nama_bandwith'  => $paketLabel,
            'bulan_tagihan'  => (string) ($c->bulan_tagihan ?? ''),
            'tahun_tagihan'  => (string) ($c->tahun_tagihan ?? ''),
            'total_layanan'  => (float) ($c->total_layanan ?? 0),
        ];
    })->values()->all();
@endphp

<script>
function emailManagement() {
    return {
        selectedTargets: [],
        selectedPresetId: '',
        presets: @json($presets),
        pageCustomers: @json($pageCustomersJson),
        pageTargetIds: @json($customers->whereNotNull('email')->where('email', '!=', '')->pluck('nomor_internet')->map(fn($v) => (string)$v)->values()->toArray()),
        allFilteredTargetIds: @json($allFilteredTargetIds ?? []),

        form: {
            subject: 'Pemberitahuan Resmi - PT Media Solusi Network',
            badge_text: 'PEMBERITAHUAN RESMI',
            action_text: 'Kunjungi Portal Pelanggan',
            action_url: '{{ config("company.portal_url", "https://portal.ptmsn.co.id") }}',
            message: "Kepada Yth. {nama_pelanggan},\n\nKami menginformasikan terkait layanan internet Anda #{nomor_internet}.\n\nTerima kasih atas perhatian dan kerja sama Anda."
        },

        previewModalOpen: false,
        previewMode: 'composer',
        previewCustomerId: '',
        previewCustomer: null,
        previewSubject: '',
        previewHtml: '',
        previewLog: null,

        testModalOpen: false,
        testEmail: '{{ auth()->user()->email ?? "m.azmi.f12345@gmail.com" }}',
        isTestSending: false,
        isSending: false,
        retryingLogId: null,
        isRetryingAll: false,

        confirmModalOpen: false,
        confirmPayload: {
            mode: 'bulk', // 'bulk' | 'single'
            targets: [],
            label: ''
        },

        init() {
            // Pulihkan daftar pelanggan yang sudah dicentang lintas halaman / filter
            try {
                const savedTargets = sessionStorage.getItem('ims_email_selected_targets_v1');
                if (savedTargets) {
                    const parsed = JSON.parse(savedTargets);
                    if (Array.isArray(parsed)) {
                        this.selectedTargets = Array.from(new Set(parsed.map(String)));
                    }
                }

                const savedPreset = sessionStorage.getItem('ims_email_preset_v1');
                if (savedPreset !== null) {
                    this.selectedPresetId = savedPreset;
                }

                const savedForm = sessionStorage.getItem('ims_email_form_v1');
                if (savedForm) {
                    const parsedForm = JSON.parse(savedForm);
                    if (parsedForm && typeof parsedForm === 'object') {
                        this.form = { ...this.form, ...parsedForm };
                    }
                }
            } catch (e) {}

            // Simpan perubahan otomatis ke sessionStorage saat berpindah halaman / filter
            this.$watch('selectedTargets', (val) => {
                try {
                    sessionStorage.setItem('ims_email_selected_targets_v1', JSON.stringify(val.map(String)));
                } catch (e) {}
            });

            this.$watch('selectedPresetId', (val) => {
                try {
                    sessionStorage.setItem('ims_email_preset_v1', val || '');
                } catch (e) {}
            });

            this.$watch('form', (val) => {
                try {
                    sessionStorage.setItem('ims_email_form_v1', JSON.stringify(val));
                } catch (e) {}
            }, { deep: true });
        },

        isAllSelected() {
            if (this.pageTargetIds.length === 0) return false;
            return this.pageTargetIds.every(id => this.selectedTargets.includes(String(id)));
        },

        toggleSelectAll() {
            if (this.isAllSelected()) {
                this.selectedTargets = this.selectedTargets.filter(id => !this.pageTargetIds.includes(String(id)));
            } else {
                const combined = new Set([...this.selectedTargets.map(String), ...this.pageTargetIds.map(String)]);
                this.selectedTargets = Array.from(combined);
            }
        },

        selectAllFiltered() {
            const combined = new Set([...this.selectedTargets.map(String), ...this.allFilteredTargetIds.map(String)]);
            this.selectedTargets = Array.from(combined);
        },

        clearSelection() {
            this.selectedTargets = [];
            try {
                sessionStorage.removeItem('ims_email_selected_targets_v1');
            } catch (e) {}
        },

        applyPreset() {
            if (!this.selectedPresetId) return;
            const p = this.presets.find(item => item.id === this.selectedPresetId);
            if (p) {
                this.form.subject = p.subject;
                this.form.badge_text = p.badge;
                this.form.message = p.body;
                this.form.action_text = p.action_text;
                this.form.action_url = p.action_url;
            }
        },

        insertTag(tag) {
            this.form.message += ' ' + tag;
        },

        showNotice(icon, title, text = '', reloadAfter = false) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: icon,
                    title: title,
                    text: text,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#0284c7',
                    customClass: {
                        popup: 'rounded-2xl'
                    }
                }).then(() => {
                    if (reloadAfter) {
                        window.location.reload();
                    }
                });
            } else {
                alert((title ? title + '\n' : '') + text);
                if (reloadAfter) {
                    window.location.reload();
                }
            }
        },

        async openPreviewModal(nomorInternet = null) {
            this.previewMode = 'composer';

            if (nomorInternet) {
                this.previewCustomerId = String(nomorInternet);
            } else if (!this.previewCustomerId) {
                const firstSelectedOnPage = this.pageCustomers.find(c => this.selectedTargets.includes(String(c.nomor_internet)));
                if (firstSelectedOnPage) {
                    this.previewCustomerId = String(firstSelectedOnPage.nomor_internet);
                } else if (this.pageCustomers.length > 0) {
                    this.previewCustomerId = String(this.pageCustomers[0].nomor_internet);
                }
            }

            const matchedCust = this.pageCustomers.find(c => String(c.nomor_internet) === String(this.previewCustomerId)) || null;

            try {
                const res = await fetch('{{ route("admin.email.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ...this.form,
                        nomor_internet: this.previewCustomerId || '',
                        customer_data: matchedCust,
                        bulan: '{{ $bulanFilter ?? "all" }}',
                        tahun: '{{ $tahunFilter ?? date("Y") }}'
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.previewHtml = data.html;
                    this.previewSubject = data.subject || this.form.subject;
                    this.previewCustomer = data.customer || null;
                    this.previewModalOpen = true;
                }
            } catch (e) {
                this.showNotice('error', 'Gagal Memuat Pratinjau', e.message);
            }
        },

        sendSingleFromPreview() {
            if (!this.previewCustomer || !this.previewCustomer.nomor_internet) return;
            this.confirmPayload = {
                mode: 'single',
                targets: [String(this.previewCustomer.nomor_internet)],
                label: `${this.previewCustomer.nama_pelanggan} (${this.previewCustomer.email})`
            };
            this.confirmModalOpen = true;
        },

        sendBroadcast() {
            if (this.selectedTargets.length === 0) {
                this.showNotice('warning', 'Belum Ada Penerima', 'Silakan pilih minimal 1 pelanggan penerima email terlebih dahulu.');
                return;
            }

            this.confirmPayload = {
                mode: 'bulk',
                targets: [...this.selectedTargets],
                label: `${this.selectedTargets.length} Pelanggan Terpilih`
            };
            this.confirmModalOpen = true;
        },

        async executeSendEmail() {
            if (!this.confirmPayload.targets || this.confirmPayload.targets.length === 0) return;

            this.isSending = true;
            try {
                const payload = {
                    ...this.form,
                    targets: this.confirmPayload.targets,
                    bulan: '{{ $bulanFilter ?? "all" }}',
                    tahun: '{{ $tahunFilter ?? date("Y") }}'
                };
                const res = await fetch('{{ route("admin.email.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.confirmModalOpen = false;
                    this.previewModalOpen = false;
                    if (this.confirmPayload.mode === 'bulk') {
                        this.clearSelection();
                    }
                    this.showNotice('success', 'Berhasil Masuk Antrean!', data.message, true);
                } else {
                    this.showNotice('error', 'Pengiriman Gagal', data.message || 'Gagal memproses pengiriman email.');
                }
            } catch (e) {
                this.showNotice('error', 'Terjadi Kesalahan', e.message);
            } finally {
                this.isSending = false;
            }
        },

        openTestModal() {
            this.testModalOpen = true;
        },

        async submitTestSend() {
            if (!this.testEmail) {
                this.showNotice('warning', 'Email Kosong', 'Masukkan alamat email tujuan pengujian.');
                return;
            }
            this.isTestSending = true;
            try {
                const payload = {
                    ...this.form,
                    is_test: true,
                    test_email: this.testEmail
                };
                const res = await fetch('{{ route("admin.email.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.testModalOpen = false;
                    this.showNotice('success', 'Email Uji Coba Dikirim!', data.message);
                } else {
                    this.showNotice('error', 'Gagal Mengirim Uji Coba', data.message);
                }
            } catch (e) {
                this.showNotice('error', 'Terjadi Kesalahan', e.message);
            } finally {
                this.isTestSending = false;
            }
        },

        async showLogDetail(id) {
            try {
                const res = await fetch(`/admin/email/history/${id}`);
                const data = await res.json();
                if (data.success && data.data) {
                    this.previewMode = 'log';
                    this.previewLog = data.data;
                    this.previewHtml = data.data.pesan_html;
                    this.previewModalOpen = true;
                }
            } catch (e) {
                this.showNotice('error', 'Gagal Memuat Detail Log', e.message);
            }
        },

        async retryFailedLog(id) {
            if (!id || this.retryingLogId === id) return;
            this.retryingLogId = id;
            try {
                const res = await fetch(`/admin/email/history/${id}/retry`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.previewModalOpen = false;
                    this.showNotice('success', 'Berhasil Dijadwalkan Ulang!', data.message, true);
                } else {
                    this.showNotice('error', 'Gagal Kirim Ulang', data.message || 'Tidak dapat mengirim ulang email.');
                }
            } catch (e) {
                this.showNotice('error', 'Terjadi Kesalahan', e.message);
            } finally {
                this.retryingLogId = null;
            }
        },

        async retryAllFailedLogs() {
            if (this.isRetryingAll) return;
            this.isRetryingAll = true;
            try {
                const res = await fetch('{{ route("admin.email.history.retry-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showNotice('success', 'Antrean Kirim Ulang Aktif!', data.message, true);
                } else {
                    this.showNotice('warning', 'Info Pengiriman Ulang', data.message || 'Tidak ada email gagal untuk dikirim ulang.');
                }
            } catch (e) {
                this.showNotice('error', 'Terjadi Kesalahan', e.message);
            } finally {
                this.isRetryingAll = false;
            }
        }
    };
}
</script>
@endsection

