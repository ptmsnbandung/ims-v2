@extends('layouts.app')

@section('title', 'Pusat Notifikasi & Monitoring Isu Jaringan - NOC IMS')
@section('page_title', 'Pusat Notifikasi NOC')

@section('content')
<div class="space-y-5" x-data="nocNotifikasiPage()">
    <!-- =================================================================== -->
    <!-- 1. HEADER BANNER KONTROL NOC (TEAL/CYAN HIGH CONTRAST)              -->
    <!-- =================================================================== -->
    <div class="rounded-2xl p-4 sm:p-5 shadow-lg border border-teal-500/30 relative overflow-hidden"
         style="background: linear-gradient(135deg, #05404f 0%, #04333e 50%, #0a2330 100%) !important;">
        <!-- Glow Ambient Effect -->
        <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-cyan-400/15 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 rounded-full bg-rose-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#04333e]/90 text-cyan-300 border border-teal-400/30 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                        NOC REALTIME RADAR
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 font-mono"
                          x-show="summary.total_active > 0" x-cloak>
                        <span x-text="summary.total_active"></span> Isu Darurat Aktif
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono"
                          x-show="summary.total_active === 0" x-cloak>
                        Semua Sistem Normal
                    </span>
                </div>
                <h2 class="text-base sm:text-xl font-bold text-white tracking-tight mt-1.5" style="color: #FFFFFF !important;">
                    Pusat Notifikasi & Pemantauan Gangguan Jaringan
                </h2>
                <p class="text-xs text-[#c6edf3] mt-0.5 leading-relaxed max-w-2xl" style="color: #c6edf3 !important;">
                    Mendeteksi otomatis OLT, PON, atau ONU yang mengalami LOS/DOWN serta tiket gangguan masuk. Masalah otomatis terhapus ketika pulih (UP) atau tiket selesai ditangani.
                </p>
            </div>

            <!-- Kontrol Realtime & Auto-Refresh -->
            <div class="flex items-center flex-wrap gap-2 shrink-0">
                <!-- Clock -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#04333e]/90 text-emerald-300 border border-teal-400/30 text-xs font-mono font-bold shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Live: <span class="text-white" x-text="currentTime">--:--:--</span> WIB</span>
                </div>

                <!-- Tombol Refresh -->
                <button @click="refreshData()"
                        :disabled="isLoading"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-cyan-600 hover:bg-cyan-500 border border-cyan-400/40 shadow-xs transition cursor-pointer disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" :class="isLoading ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Refresh Radar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. KPI STATS SUMMARY CARDS                                          -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Isu Aktif -->
        <div @click="activeTab = 'all'"
             class="p-4 rounded-2xl border transition cursor-pointer relative overflow-hidden group"
             :class="activeTab === 'all' ? 'bg-rose-50/80 dark:bg-rose-950/30 border-rose-500 shadow-md ring-2 ring-rose-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Isu Aktif</span>
                <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black font-mono text-rose-600 dark:text-rose-400" x-text="summary.total_active"></span>
                <span class="text-xs font-semibold text-slate-400">Kejadian</span>
            </div>
            <p class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-1 truncate">Memerlukan atensi teknis NOC</p>
        </div>

        <!-- 2. Perangkat LOS / DOWN -->
        <div @click="activeTab = 'perangkat'"
             class="p-4 rounded-2xl border transition cursor-pointer relative overflow-hidden group"
             :class="activeTab === 'perangkat' ? 'bg-amber-50/80 dark:bg-amber-950/30 border-amber-500 shadow-md ring-2 ring-amber-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">LOS & DOWN</span>
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.122a6.75 6.75 0 0 1 0-9.548m9.546 0a6.75 6.75 0 0 1 0 9.548M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black font-mono text-amber-600 dark:text-amber-400" x-text="summary.total_los_down"></span>
                <span class="text-xs font-semibold text-slate-400">Node / ONU</span>
            </div>
            <p class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-1 truncate">Sinyal loss optik atau offline</p>
        </div>

        <!-- 3. Tiket Gangguan Baru -->
        <div @click="activeTab = 'tiket'"
             class="p-4 rounded-2xl border transition cursor-pointer relative overflow-hidden group"
             :class="activeTab === 'tiket' ? 'bg-indigo-50/80 dark:bg-indigo-950/30 border-indigo-500 shadow-md ring-2 ring-indigo-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tiket Gangguan</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black font-mono text-indigo-600 dark:text-indigo-400" x-text="summary.total_tickets"></span>
                <span class="text-xs font-semibold text-slate-400">Tiket Open</span>
            </div>
            <p class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-1 truncate">Laporan masuk dari user / teknisi</p>
        </div>

        <!-- 4. Ditandai Mute Alert / Maintenance -->
        <div @click="activeTab = 'intentional'"
             class="p-4 rounded-2xl border transition cursor-pointer relative overflow-hidden group"
             :class="activeTab === 'intentional' ? 'bg-cyan-50/80 dark:bg-cyan-950/30 border-cyan-500 shadow-md ring-2 ring-cyan-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mute Alert (Pemeliharaan)</span>
                <div class="w-8 h-8 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.32l-3.276 3.277a1.5 1.5 0 0 1-2.122-2.122l3.277-3.276a4.5 4.5 0 0 0-6.32 4.486c.048.58.024 1.193-.14 1.743" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black font-mono text-cyan-600 dark:text-cyan-400" x-text="summary.total_intentional"></span>
                <span class="text-xs font-semibold text-slate-400">Perangkat</span>
            </div>
            <p class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-1 truncate">Notifikasi dibisukan / maintenance</p>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. TABS FILTER & PENCARIAN                                          -->
    <!-- =================================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center flex-wrap gap-1">
            <button @click="activeTab = 'all'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                    :class="activeTab === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span>Semua Isu Aktif</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                      :class="activeTab === 'all' ? 'bg-white/20 dark:bg-black/20 text-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                      x-text="alerts.length"></span>
            </button>

            <button @click="activeTab = 'perangkat'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                    :class="activeTab === 'perangkat' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span>Perangkat (LOS / DOWN)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                      :class="activeTab === 'perangkat' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                      x-text="summary.total_los_down"></span>
            </button>

            <button @click="activeTab = 'tiket'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                    :class="activeTab === 'tiket' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span>Tiket Gangguan</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                      :class="activeTab === 'tiket' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                      x-text="summary.total_tickets"></span>
            </button>

            <button @click="activeTab = 'intentional'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                    :class="activeTab === 'intentional' ? 'bg-cyan-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span>Mute Alert</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                      :class="activeTab === 'intentional' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                      x-text="intentional.length"></span>
            </button>
        </div>

        <!-- Search Box -->
        <div class="relative w-full sm:w-64">
            <input type="text"
                   x-model="searchKeyword"
                   placeholder="Cari OLT, ONU, Pelanggan, Tiket..."
                   class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500">
            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. DAFTAR NOTIFIKASI KARTU ISU                                      -->
    <!-- =================================================================== -->
    <div class="space-y-3">
        <!-- Loading State -->
        <template x-if="isLoading && currentList.length === 0">
            <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                <svg class="w-8 h-8 mx-auto animate-spin text-cyan-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Memeriksa radar gangguan jaringan & tiket...</p>
                <p class="text-xs text-slate-400 mt-1">Mengambil status OLT, PON, ONU, dan tiket terkini.</p>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!isLoading && currentList.length === 0">
            <div class="py-16 px-4 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3 shadow-inner">
                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Tidak Ada Isu Gangguan Aktif</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                    Semua perangkat OLT, PON, dan ONU termonitor berjalan normal (UP), dan seluruh tiket gangguan telah diselesaikan.
                </p>
                <template x-if="activeTab === 'intentional'">
                    <p class="text-xs text-cyan-600 dark:text-cyan-400 mt-2 font-semibold">
                        Belum ada perangkat yang dibisukan (Mute Alert).
                    </p>
                </template>
            </div>
        </template>

        <!-- Item Cards List -->
        <template x-for="item in currentList" :key="item.id">
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border transition hover:shadow-md relative overflow-hidden"
                 :class="{
                     'border-rose-300 dark:border-rose-900/60 bg-gradient-to-r from-rose-50/20 via-white to-white dark:from-rose-950/15 dark:via-slate-900 dark:to-slate-900': item.severity === 'danger' && !item.is_intentional,
                     'border-amber-300 dark:border-amber-900/60 bg-gradient-to-r from-amber-50/20 via-white to-white dark:from-amber-950/15 dark:via-slate-900 dark:to-slate-900': item.severity === 'warning' && !item.is_intentional,
                     'border-cyan-300 dark:border-cyan-900/60 bg-gradient-to-r from-cyan-50/30 via-white to-white dark:from-cyan-950/20 dark:via-slate-900 dark:to-slate-900': item.is_intentional
                 }">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Sisi Kiri: Detail Isu & Deskripsi -->
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <!-- Icon Avatar -->
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 shadow-xs"
                             :class="{
                                 'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 ring-1 ring-rose-200 dark:ring-rose-800': item.severity === 'danger' && !item.is_intentional,
                                 'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800': item.severity === 'warning' && !item.is_intentional,
                                 'bg-cyan-100 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400 ring-1 ring-cyan-200 dark:ring-cyan-800': item.is_intentional
                             }">
                            <template x-if="item.icon_type === 'ticket'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                </svg>
                            </template>
                            <template x-if="item.icon_type === 'server'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a2.25 2.25 0 0 1 1.8-.85h8.9a2.25 2.25 0 0 1 1.8.85l2.1 3.15a4.5 4.5 0 0 1 .9 2.7" />
                                </svg>
                            </template>
                            <template x-if="item.icon_type === 'radio'">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.122a6.75 6.75 0 0 1 0-9.548m9.546 0a6.75 6.75 0 0 1 0 9.548M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </template>
                        </div>

                        <!-- Info Detail -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center flex-wrap gap-2">
                                <!-- Title -->
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white" x-text="item.title"></h4>
                                
                                <!-- Status Badge -->
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider"
                                      :class="item.badge_color"
                                      x-text="item.status"></span>

                                <!-- Intentional Badge (Jika ditandai pemeliharaan) -->
                                <template x-if="item.is_intentional">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800">
                                        <svg class="w-3 h-3 text-cyan-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Maintenance (Intentional)</span>
                                    </span>
                                </template>

                                <!-- Time -->
                                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono" x-text="item.created_at"></span>
                            </div>

                            <!-- Device Identifier & Subtitle -->
                            <div class="flex items-center flex-wrap gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 mt-1">
                                <span class="font-bold text-slate-900 dark:text-white" x-text="item.device_name"></span>
                                <span class="text-slate-400">&middot;</span>
                                <span class="font-normal text-slate-500 dark:text-slate-400" x-text="item.subtitle"></span>
                                <template x-if="item.rx_power && item.rx_power !== 'N/A'">
                                    <span class="inline-flex items-center gap-1 font-mono text-[11px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        Redaman: <strong x-text="item.rx_power"></strong>
                                    </span>
                                </template>
                            </div>

                            <!-- Message Content -->
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1.5 leading-relaxed bg-slate-50/80 dark:bg-slate-800/40 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 whitespace-pre-line"
                               x-text="item.message"></p>

                            <!-- Intentional Reason Info jika ditandai -->
                            <template x-if="item.is_intentional && item.intentional_data">
                                <div class="mt-2 text-xs text-cyan-800 dark:text-cyan-300 bg-cyan-50/80 dark:bg-cyan-950/30 p-2 rounded-xl border border-cyan-200 dark:border-cyan-800/60 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="font-bold">Alasan Maintenance:</span>
                                        <span class="truncate" x-text="item.intentional_data.reason || 'Pemeliharaan perangkat'"></span>
                                    </div>
                                    <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-mono shrink-0" x-text="'Oleh: ' + (item.intentional_data.marked_by || 'NOC')"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Action Buttons -->
                    <div class="flex items-center flex-wrap gap-2 lg:flex-col lg:items-end justify-end shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800">
                        <!-- Direct Action Link -->
                        <a :href="item.url"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 shadow-xs transition">
                            <span x-text="item.action_label || 'Buka Rincian'"></span>
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>

                        <!-- Tombol Mute Alert -->
                        <template x-if="!item.is_intentional">
                            <button @click="openIntentionalModal(item)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-cyan-700 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-950/60 dark:text-cyan-300 dark:hover:bg-cyan-900/60 border border-cyan-200 dark:border-cyan-800 transition cursor-pointer"
                                    title="Bisukan notifikasi perangkat ini selama pemeliharaan/perbaikan">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.32l-3.276 3.277a1.5 1.5 0 0 1-2.122-2.122l3.277-3.276a4.5 4.5 0 0 0-6.32 4.486c.048.58.024 1.193-.14 1.743" />
                                </svg>
                                <span>Mute Alert</span>
                            </button>
                        </template>

                        <!-- Tombol Batal Mute (Unmute) -->
                        <template x-if="item.is_intentional">
                            <button @click="unmarkIntentional(item)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition cursor-pointer"
                                    title="Nyalakan kembali notifikasi (Unmute Alert)">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                                <span>Unmute Alert</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- =================================================================== -->
    <!-- 5. MODAL MUTE ALERT PERANGKAT                                       -->
    <!-- =================================================================== -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop with Blur -->
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"
             @click="modalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800 p-5 sm:p-6"
                 @click.away="modalOpen = false">
                
                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold flex-shrink-0"
                             style="background-color: #ecfeff !important; color: #0891b2 !important; border: 1px solid #cffafe !important;">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.32l-3.276 3.277a1.5 1.5 0 0 1-2.122-2.122l3.277-3.276a4.5 4.5 0 0 0-6.32 4.486c.048.58.024 1.193-.14 1.743" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white" id="modal-title">
                                Mute Alert (Bisukan Peringatan)
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Bisukan alarm & notifikasi perangkat ini selama masa pemeliharaan/perbaikan.</p>
                        </div>
                    </div>
                    <button @click="modalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="mt-4 space-y-4">
                    <!-- Info Perangkat Terpilih -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700 text-xs">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Target Perangkat:</span>
                        <div class="font-bold text-slate-900 dark:text-white text-sm mt-0.5" x-text="targetItem?.device_name"></div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="targetItem?.subtitle"></div>
                    </div>

                    <!-- Preset Alasan Cepat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Alasan Pemeliharaan:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="reasonInput = 'Perapihan jalur kabel optik ODP'"
                                    type="button"
                                    class="p-2.5 rounded-xl text-left text-xs border transition cursor-pointer flex items-center justify-between"
                                    :class="reasonInput === 'Perapihan jalur kabel optik ODP' ? 'border-cyan-500 font-bold dark:text-cyan-300 ring-2 ring-cyan-500/20' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                    :style="reasonInput === 'Perapihan jalur kabel optik ODP' ? 'background-color: #ecfeff !important; color: #0e7490 !important; border-color: #06b6d4 !important;' : ''">
                                <span>🔧 Perapihan Kabel ODP</span>
                            </button>
                            <button @click="reasonInput = 'Maintenance terjadwal perangkat / server'"
                                    type="button"
                                    class="p-2.5 rounded-xl text-left text-xs border transition cursor-pointer flex items-center justify-between"
                                    :class="reasonInput === 'Maintenance terjadwal perangkat / server' ? 'border-cyan-500 font-bold dark:text-cyan-300 ring-2 ring-cyan-500/20' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                    :style="reasonInput === 'Maintenance terjadwal perangkat / server' ? 'background-color: #ecfeff !important; color: #0e7490 !important; border-color: #06b6d4 !important;' : ''">
                                <span>🕒 Maintenance Terjadwal</span>
                            </button>
                            <button @click="reasonInput = 'Isolasi gangguan segmen jaringan'"
                                    type="button"
                                    class="p-2.5 rounded-xl text-left text-xs border transition cursor-pointer flex items-center justify-between"
                                    :class="reasonInput === 'Isolasi gangguan segmen jaringan' ? 'border-cyan-500 font-bold dark:text-cyan-300 ring-2 ring-cyan-500/20' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                    :style="reasonInput === 'Isolasi gangguan segmen jaringan' ? 'background-color: #ecfeff !important; color: #0e7490 !important; border-color: #06b6d4 !important;' : ''">
                                <span>🛡️ Isolasi Gangguan</span>
                            </button>
                            <button @click="reasonInput = 'Penggantian modem / ONT pelanggan'"
                                    type="button"
                                    class="p-2.5 rounded-xl text-left text-xs border transition cursor-pointer flex items-center justify-between"
                                    :class="reasonInput === 'Penggantian modem / ONT pelanggan' ? 'border-cyan-500 font-bold dark:text-cyan-300 ring-2 ring-cyan-500/20' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                    :style="reasonInput === 'Penggantian modem / ONT pelanggan' ? 'background-color: #ecfeff !important; color: #0e7490 !important; border-color: #06b6d4 !important;' : ''">
                                <span>🔄 Penggantian Modem</span>
                            </button>
                        </div>
                    </div>

                    <!-- Custom Reason Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan Tambahan / Custom Note:</label>
                        <textarea x-model="reasonInput"
                                  rows="2"
                                  placeholder="Tuliskan catatan teknis maintenance..."
                                  class="w-full p-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500"></textarea>
                    </div>

                    <!-- Warning Alert -->
                    <div class="p-3 rounded-xl border text-[11px] leading-relaxed"
                         style="background-color: #f0fdfa !important; color: #0f766e !important; border-color: #99f6e4 !important;">
                        <strong class="font-bold">Info:</strong> Setelah di-Mute Alert, status perangkat tidak akan memicu alarm dan tidak dihitung sebagai insiden darurat aktif pada dashboard maupun tombol lonceng navbar.
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="mt-5 flex items-center justify-end gap-2.5 pt-3.5 border-t border-slate-100 dark:border-slate-800">
                    <button @click="modalOpen = false"
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        Batal
                    </button>
                    <button @click="submitIntentional()"
                            :disabled="isSubmitting"
                            type="button"
                            style="background: #0891b2 !important; color: #ffffff !important; box-shadow: 0 2px 8px rgba(8, 145, 178, 0.3) !important;"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer hover:brightness-110 active:scale-95 disabled:opacity-50">
                        <svg class="w-4 h-4" :class="isSubmitting ? 'animate-spin' : ''" style="color: #ffffff !important;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span style="color: #ffffff !important;">Mute Alert Sekarang</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function nocNotifikasiPage() {
    return {
        activeTab: 'all', // 'all', 'perangkat', 'tiket', 'intentional'
        searchKeyword: '',
        isLoading: false,
        isSubmitting: false,
        currentTime: '--:--:--',
        clockTimer: null,
        pollTimer: null,
        modalOpen: false,
        targetItem: null,
        reasonInput: 'Perapihan jalur kabel optik ODP',
        
        alerts: {!! json_encode($alerts ?? []) !!},
        intentional: {!! json_encode($intentional ?? []) !!},
        summary: {!! json_encode($summary ?? ['total_active' => 0, 'total_los_down' => 0, 'total_tickets' => 0, 'total_intentional' => 0]) !!},

        init() {
            this.updateClock();
            this.clockTimer = setInterval(() => this.updateClock(), 1000);

            // Auto-refresh setiap 20 detik
            this.pollTimer = setInterval(() => {
                this.refreshData(true);
            }, 20000);

            // Reaktif terhadap event global
            window.addEventListener('noc-notifications-updated', () => {
                this.refreshData(true);
            });
        },

        updateClock() {
            const now = new Date();
            this.currentTime = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }).format(now).replace(/\./g, ':');
        },

        get currentList() {
            let list = [];
            if (this.activeTab === 'all') {
                list = this.alerts;
            } else if (this.activeTab === 'perangkat') {
                list = this.alerts.filter(item => item.status === 'LOS' || item.status === 'DOWN');
            } else if (this.activeTab === 'tiket') {
                list = this.alerts.filter(item => item.device_type === 'tiket');
            } else if (this.activeTab === 'intentional') {
                list = this.intentional;
            }

            if (this.searchKeyword.trim() !== '') {
                const kw = this.searchKeyword.toLowerCase();
                list = list.filter(item => 
                    (item.title && item.title.toLowerCase().includes(kw)) ||
                    (item.device_name && item.device_name.toLowerCase().includes(kw)) ||
                    (item.subtitle && item.subtitle.toLowerCase().includes(kw)) ||
                    (item.message && item.message.toLowerCase().includes(kw)) ||
                    (item.status && item.status.toLowerCase().includes(kw))
                );
            }

            return list;
        },

        async refreshData(silent = false) {
            if (!silent) this.isLoading = true;
            try {
                const url = !silent ? '{{ route("noc.api.notifications") }}?refresh=1' : '{{ route("noc.api.notifications") }}';
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.status === 'success' && data.data) {
                        this.alerts = data.data.alerts || [];
                        this.intentional = data.data.intentional || [];
                        this.summary = data.data.summary || {
                            total_active: this.alerts.length,
                            total_los_down: 0,
                            total_tickets: 0,
                            total_intentional: this.intentional.length
                        };
                    }
                }
            } catch (e) {
                console.warn('Gagal memuat notifikasi:', e);
            } finally {
                if (!silent) this.isLoading = false;
            }
        },

        openIntentionalModal(item) {
            this.targetItem = item;
            this.reasonInput = 'Perapihan jalur kabel optik ODP';
            this.modalOpen = true;
        },

        async submitIntentional() {
            if (!this.targetItem) return;
            this.isSubmitting = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('{{ route("noc.api.notifications.intentional") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        device_key: this.targetItem.device_key,
                        device_type: this.targetItem.device_type,
                        device_name: this.targetItem.device_name,
                        olt_kode: this.targetItem.olt_kode,
                        pon_index: this.targetItem.pon_index,
                        onu_id: this.targetItem.onu_id,
                        interface_name: this.targetItem.interface_name,
                        nomor_internet: this.targetItem.nomor_internet,
                        reason: this.reasonInput
                    })
                });

                if (res.ok) {
                    this.modalOpen = false;
                    await this.refreshData(true);
                    window.dispatchEvent(new CustomEvent('noc-notifications-updated'));
                } else {
                    alert('Gagal menandai perangkat sebagai intentional.');
                }
            } catch (e) {
                alert('Terjadi kesalahan: ' + e.message);
            } finally {
                this.isSubmitting = false;
            }
        },

        async unmarkIntentional(item) {
            if (!confirm(`Cabut status Intentional untuk ${item.device_name}? Perangkat akan kembali dipantau normal.`)) {
                return;
            }

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('{{ route("noc.api.notifications.unmark-intentional") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        device_key: item.device_key
                    })
                });

                if (res.ok) {
                    await this.refreshData(true);
                    window.dispatchEvent(new CustomEvent('noc-notifications-updated'));
                } else {
                    alert('Gagal mencabut status intentional.');
                }
            } catch (e) {
                alert('Terjadi kesalahan: ' + e.message);
            }
        }
    };
}
</script>
@endsection
