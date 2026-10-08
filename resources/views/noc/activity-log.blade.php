@extends('layouts.app')

@section('title', 'Activity Log & Audit Trail Router - NOC IMS')
@section('page_title', 'Activity Log Router')

@section('content')
<div class="space-y-3.5" x-data="activityLogManager()">

    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER                                           -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-xl p-3.5 sm:p-4 shadow-xs border border-indigo-500/20"
         style="background: linear-gradient(108deg, #0f172a 0%, #1e1b4b 30%, #312e81 65%, #2563eb 90%, #0284c7 100%);">
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 relative z-10">
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">
                            Activity Log & Audit Trail Router MikroTik
                        </h2>
                        <span class="px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 uppercase tracking-wide">
                            Live Audit
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-300 mt-0.5">
                        Rekam jejak eksekusi otomatis & manual MikroTik RouterOS: aktivasi, suspend/isolir, kick session, sync, reboot ONU, dan konfigurasi master.
                    </p>
                    
                    <!-- Stats Badges Bar -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-900/80 text-slate-200 border border-slate-700/60 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            <span>Total Log: {{ number_format($totalLogs) }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-900/80 text-emerald-300 border border-emerald-500/30 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Sukses: {{ number_format($successLogs) }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-900/80 text-rose-300 border border-rose-500/30 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            <span>Gagal: {{ number_format($failedLogs) }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-900/80 text-sky-300 border border-sky-500/30 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                            <span>Hari Ini: {{ number_format($todayLogs) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('noc.router') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-white font-semibold text-xs border border-slate-600 transition shadow-xs">
                    <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                    </svg>
                    <span>Master Router</span>
                </a>

                <button type="button" 
                        @click="clearModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600/30 hover:bg-rose-600 text-rose-200 hover:text-white font-semibold text-xs border border-rose-500/40 transition">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>Bersihkan Log</span>
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. QUICK METRIC CARDS                                               -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <!-- Aktivasi Layanan -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Aktivasi / PPPoE</span>
                <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div class="text-lg font-bold text-slate-800 dark:text-white mt-1">
                {{ number_format($activateLogs) }}
            </div>
            <p class="text-[10px] text-slate-400 mt-0.5">Total eksekusi aktivasi akun</p>
        </div>

        <!-- Suspend / Isolir -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Suspend / Isolir</span>
                <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </span>
            </div>
            <div class="text-lg font-bold text-slate-800 dark:text-white mt-1">
                {{ number_format($suspendLogs) }}
            </div>
            <p class="text-[10px] text-slate-400 mt-0.5">Total pemutusan layanan MikroTik</p>
        </div>

        <!-- Sukses Eksekusi -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Tingkat Keberhasilan</span>
                <span class="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                </span>
            </div>
            <div class="text-lg font-bold text-cyan-500 dark:text-cyan-400 mt-1">
                {{ $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 1) : 0 }}%
            </div>
            <p class="text-[10px] text-slate-400 mt-0.5">{{ number_format($successLogs) }} sukses dari {{ number_format($totalLogs) }}</p>
        </div>

        <!-- Gagal Eksekusi -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Gagal / Error</span>
                <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </span>
            </div>
            <div class="text-lg font-bold text-amber-500 dark:text-amber-400 mt-1">
                {{ number_format($failedLogs) }}
            </div>
            <p class="text-[10px] text-slate-400 mt-0.5">Memerlukan perhatian NOC</p>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. FILTER & SEARCH TOOLBAR                                          -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs">
        <form method="GET" action="{{ route('noc.activity-log') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 sm:gap-2.5 items-center">
            
            <!-- Search Text (Col 4) -->
            <div class="md:col-span-4 relative">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Cari ID Internet, Pelanggan, Operator, Respons..." 
                       class="w-full text-xs px-2.5 py-1.5 pl-8 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Filter Action (Col 2) -->
            <div class="md:col-span-2">
                <select name="action" 
                        class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Aksi --</option>
                    <option value="activate" {{ $action === 'activate' ? 'selected' : '' }}>Aktivasi Layanan</option>
                    <option value="suspend" {{ $action === 'suspend' ? 'selected' : '' }}>Suspend / Isolir</option>
                    <option value="pppoe_activate" {{ $action === 'pppoe_activate' ? 'selected' : '' }}>PPPoE Secret Creation</option>
                    <option value="test_connection" {{ $action === 'test_connection' ? 'selected' : '' }}>Tes Koneksi</option>
                    <option value="sync_router" {{ $action === 'sync_router' ? 'selected' : '' }}>Sinkronisasi Pelanggan</option>
                    <option value="create_router" {{ $action === 'create_router' ? 'selected' : '' }}>Tambah Router</option>
                    <option value="update_router" {{ $action === 'update_router' ? 'selected' : '' }}>Update Router</option>
                    <option value="delete_router" {{ $action === 'delete_router' ? 'selected' : '' }}>Hapus Router</option>
                </select>
            </div>

            <!-- Filter Status (Col 2) -->
            <div class="md:col-span-2">
                <select name="status" 
                        class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Status Router --</option>
                    <option value="1" {{ $status === '1' ? 'selected' : '' }}>✓ Berhasil (Sukses)</option>
                    <option value="0" {{ $status === '0' ? 'selected' : '' }}>✕ Gagal (Error)</option>
                </select>
            </div>

            <!-- Tanggal Mulai (Col 2) -->
            <div class="md:col-span-2">
                <input type="date" 
                       name="start_date" 
                       value="{{ $startDate }}"
                       title="Tanggal Mulai"
                       class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Actions Submit & Reset (Col 2) -->
            <div class="md:col-span-2 flex items-center gap-1.5">
                <button type="submit" 
                        class="flex-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <span>Filter</span>
                </button>
                @if($search || $action || $status !== null && $status !== '' || $startDate || $endDate)
                    <a href="{{ route('noc.activity-log') }}" 
                       title="Reset Filter"
                       class="px-2 py-1.5 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-rose-500 hover:text-white text-xs font-bold transition">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 4. ACTIVITY LOG DATA TABLE                                          -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-2.5 px-3 w-10 text-center">#</th>
                        <th class="py-2.5 px-3">Waktu Eksekusi</th>
                        <th class="py-2.5 px-3">Aksi / Event</th>
                        <th class="py-2.5 px-3">Pelanggan / Target</th>
                        <th class="py-2.5 px-3">Deskripsi & Respons Router</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3">Operator</th>
                        <th class="py-2.5 px-3 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($logs as $index => $log)
                        @php
                            $actionName = strtolower((string)$log->action);
                            $badgeClass = match($actionName) {
                                'activate', 'aktivasi', 'pppoe_activate' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                'suspend', 'isolir' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                'kick', 'kick_session' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                'reboot_onu' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                                'test_connection' => 'bg-sky-500/10 text-sky-400 border-sky-500/30',
                                'sync_router' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                'create_router' => 'bg-teal-500/10 text-teal-400 border-teal-500/30',
                                'update_router' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                'delete_router' => 'bg-red-500/10 text-red-400 border-red-500/30',
                                default => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                            };

                            $actionLabel = match($actionName) {
                                'activate', 'aktivasi' => 'Aktivasi Layanan',
                                'pppoe_activate' => 'PPPoE Provisioning',
                                'suspend', 'isolir' => 'Suspend / Isolir',
                                'kick', 'kick_session' => 'Kick Session',
                                'reboot_onu' => 'Reboot ONU',
                                'test_connection' => 'Tes Koneksi',
                                'sync_router' => 'Sinkronisasi Router',
                                'create_router' => 'Tambah Router',
                                'update_router' => 'Update Router',
                                'delete_router' => 'Hapus Router',
                                default => ucfirst($log->action ?: 'Log Event'),
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <!-- Index -->
                            <td class="py-2.5 px-3 text-center text-slate-400 text-[10px]">
                                {{ $logs->firstItem() + $index }}
                            </td>

                            <!-- Timestamp -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}
                                </div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</span>
                                    <span class="text-slate-500">({{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }})</span>
                                </div>
                            </td>

                            <!-- Action Badge -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $badgeClass }}">
                                    @if(in_array($actionName, ['activate', 'aktivasi', 'pppoe_activate']))
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    @elseif(in_array($actionName, ['suspend', 'isolir']))
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    @elseif(in_array($actionName, ['kick', 'kick_session']))
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    @else
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                    @endif
                                    <span>{{ $actionLabel }}</span>
                                </span>
                            </td>

                            <!-- Customer / Target -->
                            <td class="py-2.5 px-3">
                                @if($log->customer_id && $log->customer_id !== '-')
                                    <div class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1 text-xs">
                                        <span class="font-mono text-cyan-500 dark:text-cyan-400">{{ $log->display_customer_id ?? $log->customer_id }}</span>
                                    </div>
                                    @if(!empty($log->nama_pelanggan))
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-xs font-semibold">
                                            {{ $log->nama_pelanggan }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[10px]">System / Master Router</span>
                                @endif
                            </td>

                            <!-- Description & Router Response -->
                            <td class="py-2.5 px-3">
                                @if($log->description)
                                    <div class="text-slate-700 dark:text-slate-300 font-medium line-clamp-2 text-xs">
                                        {{ $log->description }}
                                    </div>
                                @endif
                                @if($log->router_response)
                                    <div class="mt-0.5 text-[10px] font-mono {{ $log->router_success ? 'text-emerald-500 dark:text-emerald-400' : 'text-rose-400' }} bg-slate-100 dark:bg-slate-950/70 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-800 truncate max-w-md">
                                        {{ $log->router_response }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                @if($log->router_success)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span>Sukses</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                        <span>Gagal</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Operator / User -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                    <div class="w-4 h-4 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-[9px] font-bold text-slate-400">
                                        {{ substr($log->user_id ?: 'S', 0, 1) }}
                                    </div>
                                    <span class="font-semibold text-[11px]">{{ $log->user_id ?: 'System' }}</span>
                                </div>
                            </td>

                            <!-- Detail Modal Button -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="openDetailModal({{ json_encode($log) }})"
                                        class="p-1 rounded-md bg-slate-100 hover:bg-indigo-600 hover:text-white dark:bg-slate-800 dark:hover:bg-indigo-600 text-slate-500 dark:text-slate-300 transition shadow-xs cursor-pointer"
                                        title="Lihat Detail Log">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 px-3 text-center text-slate-400">
                                <div class="w-10 h-10 mx-auto rounded-xl bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center text-slate-400 mb-2">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="text-xs font-bold text-slate-700 dark:text-slate-200">Belum Ada Log Aktivitas Router</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5 max-w-sm mx-auto">
                                    Aktivitas konfigurasi router, aktivasi PPPoE, dan suspend otomatis akan tercatat di sini.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="px-3.5 py-2.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- =================================================================== -->
    <!-- 5. MODAL DETAIL LOG (Alpine.js)                                     -->
    <!-- =================================================================== -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="detailModalOpen = false"
         @keydown.escape.window="detailModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 text-left space-y-4"
             x-transition>
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">Detail Log Audit Router</h3>
                        <p class="text-[11px] text-slate-400 font-mono" x-text="'Log ID #' + (selectedLog?.id || '-')"></p>
                    </div>
                </div>
                <button type="button" 
                        @click="detailModalOpen = false"
                        class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Detail Grid Info -->
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400 font-medium block text-[11px]">Aksi / Event</span>
                    <span class="font-bold text-slate-800 dark:text-white text-xs mt-0.5 uppercase tracking-wide" x-text="selectedLog?.action || '-'"></span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400 font-medium block text-[11px]">Status Eksekusi</span>
                    <span class="font-bold text-xs mt-0.5" 
                          :class="selectedLog?.router_success ? 'text-emerald-400' : 'text-rose-400'"
                          x-text="selectedLog?.router_success ? '✓ Sukses (Berhasil)' : '✕ Gagal (Error)'"></span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400 font-medium block text-[11px]">Target Nomor Internet</span>
                    <span class="font-mono font-bold text-cyan-400 text-xs mt-0.5" x-text="selectedLog?.display_customer_id || selectedLog?.customer_id || '-'"></span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400 font-medium block text-[11px]">User / Operator Pelaksana</span>
                    <span class="font-bold text-slate-800 dark:text-white text-xs mt-0.5" x-text="selectedLog?.user_id || 'System'"></span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400 font-medium block text-[11px]">Status Lama</span>
                    <span class="font-semibold text-slate-600 dark:text-slate-300 text-xs mt-0.5" x-text="selectedLog?.old_status || '-'"></span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-400 font-medium block text-[11px]">Status Baru</span>
                    <span class="font-semibold text-slate-600 dark:text-slate-300 text-xs mt-0.5" x-text="selectedLog?.new_status || '-'"></span>
                </div>
            </div>

            <!-- Description Box -->
            <div class="space-y-1.5 text-xs">
                <span class="text-slate-400 font-semibold block text-[11px]">Deskripsi Lengkap</span>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 leading-relaxed font-mono whitespace-pre-wrap"
                     x-text="selectedLog?.description || 'Tidak ada deskripsi.'">
                </div>
            </div>

            <!-- Router Response Payload Box -->
            <div class="space-y-1.5 text-xs">
                <span class="text-slate-400 font-semibold block text-[11px]">Respons / Output MikroTik</span>
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-emerald-400 overflow-x-auto max-h-48 whitespace-pre-wrap"
                     x-text="selectedLog?.router_response || 'Tidak ada respons balikan.'">
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                <button type="button" 
                        @click="detailModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. MODAL BERSIHKAN LOG                                              -->
    <!-- =================================================================== -->
    <div x-show="clearModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="clearModalOpen = false"
         @keydown.escape.window="clearModalOpen = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 text-left space-y-4"
             x-transition>
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 dark:text-white">Bersihkan Log Lama</h3>
                    <p class="text-xs text-slate-400">Hapus log aktivitas router untuk menghemat ruang database.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('noc.activity-log.clear') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Rentang Waktu Pembersihan</label>
                    <select name="days" class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="90">Hapus log yang lebih lama dari 90 Hari (3 Bulan)</option>
                        <option value="60">Hapus log yang lebih lama dari 60 Hari (2 Bulan)</option>
                        <option value="30" selected>Hapus log yang lebih lama dari 30 Hari (1 Bulan)</option>
                        <option value="7">Hapus log yang lebih lama dari 7 Hari (1 Minggu)</option>
                    </select>
                </div>

                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px]">
                    ⚠️ Tindakan ini permanen. Log aktivitas yang terhapus tidak dapat dikembalikan.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" 
                            @click="clearModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-600/20 transition">
                        Hapus Log Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function activityLogManager() {
    return {
        detailModalOpen: false,
        clearModalOpen: false,
        selectedLog: null,

        openDetailModal(log) {
            this.selectedLog = log;
            this.detailModalOpen = true;
        }
    };
}
</script>
@endpush
@endsection
