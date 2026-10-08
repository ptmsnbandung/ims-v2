@extends('layouts.app')

@section('title', 'Master Data Router MikroTik (Core NAS) - NOC IMS')
@section('page_title', 'Master Data Router')

@section('content')
<div class="space-y-6" x-data="routerManagement()">

    <!-- =================================================================== -->
    <!-- 1. TOP HERO HEADER BANNER                                           -->
    <!-- =================================================================== -->
    <div class="ims-banner relative overflow-hidden rounded-2xl p-5 sm:p-6 shadow-md border border-cyan-500/20"
         style="background: linear-gradient(108deg, #061d28 0%, #0c3349 28%, #0f4c6e 60%, #0369a1 85%, #0284c7 100%);">
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 relative z-10">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/20 border border-cyan-400/30 text-cyan-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-white tracking-tight">
                        Master Data Router MikroTik (Core & Edge NAS)
                    </h2>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Manajemen router MikroTik, provisioning PPPoE secret, pemutusan instan (kick), dan pemetaan pelanggan.
                    </p>
                    
                    <!-- Stats Badges Bar -->
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                            <span>Total Router: {{ $totalRouters }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>Aktif: {{ $activeRouters }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold bg-slate-800/80 text-slate-200 border border-slate-700/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            <span>Pelanggan Terpetakan: {{ $totalCustomersMapped }} / {{ $totalCustomers }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('noc.activity-log') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-indigo-300 hover:text-white font-bold text-xs border border-indigo-500/30 transition shadow">
                    <svg class="w-4 h-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>Activity Log Router</span>
                </a>

                <button type="button" 
                        @click="addModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/25 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Router MikroTik</span>
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. FILTER & SEARCH BAR                                              -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl p-4 shadow-xl shadow-black/10 flex flex-col sm:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('noc.router') }}" class="flex-1 w-full flex items-center gap-2.5">
            <div class="relative flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Cari router berdasarkan nama, IP Host, atau wilayah/kota..." 
                       class="w-full text-xs px-3.5 py-2.5 pl-9 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition">
                Cari
            </button>
            @if($search)
                <a href="{{ route('noc.router') }}" class="px-3 py-2.5 rounded-xl bg-rose-600/20 text-rose-400 hover:bg-rose-600 hover:text-white text-xs font-bold transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 3. ROUTER DATA TABLE                                                -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-black/10 overflow-hidden">
        <div class="ims-desktop-only hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-100 dark:bg-slate-950/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4"># ID</th>
                        <th class="py-3.5 px-4">Nama Router</th>
                        <th class="py-3.5 px-4">IP Host & Port</th>
                        <th class="py-3.5 px-4">Username</th>
                        <th class="py-3.5 px-4">Wilayah / Kota</th>
                        <th class="py-3.5 px-4 text-center">Pelanggan Terpetakan</th>
                        <th class="py-3.5 px-4 text-center">Status Router</th>
                        <th class="py-3.5 px-4 text-center">Live Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-medium">
                    @forelse($routers as $router)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-500">
                                #{{ $router->id }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $router->is_active ? 'bg-emerald-400' : 'bg-rose-500' }}"></span>
                                    <span>{{ $router->name }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-cyan-500 dark:text-cyan-400 font-semibold">
                                {{ $router->host }}:{{ $router->port ?: 18735 }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400">
                                {{ $router->username }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $router->kota ?: '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $router->customer_count > 0 ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800 text-slate-400' }}">
                                    <span>{{ $router->customer_count }} User</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($router->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-700 text-slate-400">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <template x-if="statusMap[{{ $router->id }}]">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-2" style="max-width: 240px;">
                                        <template x-if="statusMap[{{ $router->id }}].loading">
                                            <span class="inline-flex items-center gap-1.5 text-[11px] text-cyan-400 font-semibold animate-pulse">
                                                <svg class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                                Testing...
                                            </span>
                                        </template>
                                        <template x-if="!statusMap[{{ $router->id }}].loading && statusMap[{{ $router->id }}].status === 'online'">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-500/15 border border-emerald-500/30 text-[11px] text-emerald-400 font-bold" :title="statusMap[{{ $router->id }}].message">
                                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                                <span x-text="statusMap[{{ $router->id }}].identity || 'Online'"></span>
                                            </span>
                                        </template>
                                        <template x-if="!statusMap[{{ $router->id }}].loading && statusMap[{{ $router->id }}].status === 'offline'">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-rose-500/15 border border-rose-500/30 text-[11px] text-rose-400 font-semibold cursor-help" :title="statusMap[{{ $router->id }}].message">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                <span>Offline</span>
                                            </span>
                                        </template>
                                        <template x-if="!statusMap[{{ $router->id }}].loading && statusMap[{{ $router->id }}].status === 'offline'">
                                            <div style="flex-basis: 100%;" class="text-[10px] leading-snug text-rose-400 text-center break-words" x-text="'Penyebab: ' + (statusMap[{{ $router->id }}].message || 'Tidak merespon')"></div>
                                        </template>
                                        <button type="button" 
                                                x-show="!statusMap[{{ $router->id }}].loading"
                                                @click="testPingRouter({{ $router->id }}, '{{ $router->host }}', {{ $router->port ?: 18735 }}, '{{ $router->username }}')"
                                                title="Tes Ulang Koneksi"
                                                class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!statusMap[{{ $router->id }}]">
                                    <button type="button" 
                                            @click="testPingRouter({{ $router->id }}, '{{ $router->host }}', {{ $router->port ?: 18735 }}, '{{ $router->username }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-cyan-500/20 transition">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                        </svg>
                                        <span>Tes Live</span>
                                    </button>
                                </template>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Lihat User PPPoE -->
                                    <button type="button"
                                            @click="openSecretsModal({{ $router->id }}, '{{ addslashes($router->name) }}', '{{ $router->host }}')"
                                            title="Lihat Daftar User PPPoE di Router ini"
                                            class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500 hover:text-white text-emerald-400 transition">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </button>

                                    <!-- Sync Pelanggan -->
                                    <button type="button"
                                            @click="syncCustomers({{ $router->id }}, '{{ addslashes($router->name) }}')"
                                            title="Sinkronkan data pelanggan ke router ini"
                                            class="p-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500 hover:text-white text-blue-400 transition">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>

                                    <!-- Edit -->
                                    <button type="button"
                                            @click="openEditModal({{ $router->id }}, '{{ addslashes($router->name) }}', '{{ $router->host }}', {{ $router->port ?: 18735 }}, '{{ addslashes($router->username) }}', '{{ addslashes($router->kota ?? '') }}', {{ $router->is_active ? 1 : 0 }})"
                                            title="Edit Router"
                                            class="p-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500 hover:text-white text-amber-400 transition">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    <button type="button"
                                            @click="confirmDelete({{ $router->id }}, '{{ addslashes($router->name) }}')"
                                            title="Hapus Router"
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 hover:text-white text-rose-400 transition">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500">
                                Belum ada router MikroTik yang terdaftar. Klik tombol <strong>Tambah Router</strong> di atas untuk menambahkan router pertama Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List View -->
        <div class="ims-mobile-only block md:hidden p-3.5 sm:p-4 space-y-3.5 divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($routers as $router)
                <div class="pt-3.5 first:pt-0 space-y-3">
                    <!-- Router Header: Name & Status -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-[10px] text-slate-400 font-semibold block">#{{ $router->id }}</span>
                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                                <span class="w-2.5 h-2.5 rounded-full {{ $router->is_active ? 'bg-emerald-400 ring-2 ring-emerald-500/30' : 'bg-rose-500 ring-2 ring-rose-500/30' }}"></span>
                                <span>{{ $router->name }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Wilayah: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $router->kota ?: '-' }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            @if($router->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-700 text-slate-400">
                                    Nonaktif
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $router->customer_count > 0 ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800 text-slate-400' }}">
                                {{ $router->customer_count }} User
                            </span>
                        </div>
                    </div>

                    <!-- Connection & Live Ping Status Box -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200/70 dark:border-slate-800/70 text-xs space-y-2">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Host & Port:</span>
                            <span class="font-mono text-cyan-500 dark:text-cyan-400 font-bold">{{ $router->host }}:{{ $router->port ?: 18735 }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Username:</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{{ $router->username }}</span>
                        </div>
                        
                        <!-- Live Ping Status Row -->
                        <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between gap-2">
                            <span class="text-slate-500 text-[11px]">Live Status:</span>
                            <div>
                                <template x-if="statusMap[{{ $router->id }}]">
                                    <div class="inline-flex items-center gap-2">
                                        <template x-if="statusMap[{{ $router->id }}].loading">
                                            <span class="inline-flex items-center gap-1 text-[11px] text-cyan-400 font-semibold animate-pulse">
                                                <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                Testing...
                                            </span>
                                        </template>
                                        <template x-if="!statusMap[{{ $router->id }}].loading && statusMap[{{ $router->id }}].status === 'online'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/15 border border-emerald-500/30 text-[10px] text-emerald-400 font-bold">
                                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                                <span x-text="statusMap[{{ $router->id }}].identity || 'Online'"></span>
                                            </span>
                                        </template>
                                        <template x-if="!statusMap[{{ $router->id }}].loading && statusMap[{{ $router->id }}].status === 'offline'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-500/15 border border-rose-500/30 text-[10px] text-rose-400 font-semibold">
                                                Offline
                                            </span>
                                        </template>
                                        <button type="button" 
                                                x-show="!statusMap[{{ $router->id }}].loading"
                                                @click="testPingRouter({{ $router->id }}, '{{ $router->host }}', {{ $router->port ?: 18735 }}, '{{ $router->username }}')"
                                                class="p-1 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!statusMap[{{ $router->id }}]">
                                    <button type="button" 
                                            @click="testPingRouter({{ $router->id }}, '{{ $router->host }}', {{ $router->port ?: 18735 }}, '{{ $router->username }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-cyan-500/10 hover:bg-cyan-500 text-cyan-600 dark:text-cyan-400 hover:text-white border border-cyan-500/20 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                                        <span>Tes Live</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Toolbar -->
                    <div class="grid grid-cols-4 gap-2 pt-1">
                        <button type="button"
                                @click="openSecretsModal({{ $router->id }}, '{{ addslashes($router->name) }}', '{{ $router->host }}')"
                                title="PPPoE Secrets"
                                class="flex flex-col items-center justify-center gap-1 py-2 px-1 rounded-xl bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 dark:text-emerald-400 hover:text-white text-[10px] font-bold border border-emerald-500/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                            <span>PPPoE</span>
                        </button>
                        <button type="button"
                                @click="syncCustomers({{ $router->id }}, '{{ addslashes($router->name) }}')"
                                title="Sinkron Pelanggan"
                                class="flex flex-col items-center justify-center gap-1 py-2 px-1 rounded-xl bg-blue-500/10 hover:bg-blue-500 text-blue-600 dark:text-blue-400 hover:text-white text-[10px] font-bold border border-blue-500/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            <span>Sync</span>
                        </button>
                        <button type="button"
                                @click="openEditModal({{ $router->id }}, '{{ addslashes($router->name) }}', '{{ $router->host }}', {{ $router->port ?: 18735 }}, '{{ addslashes($router->username) }}', '{{ addslashes($router->kota ?? '') }}', {{ $router->is_active ? 1 : 0 }})"
                                title="Edit Router"
                                class="flex flex-col items-center justify-center gap-1 py-2 px-1 rounded-xl bg-amber-500/10 hover:bg-amber-500 text-amber-600 dark:text-amber-400 hover:text-white text-[10px] font-bold border border-amber-500/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                            <span>Edit</span>
                        </button>
                        <button type="button"
                                @click="confirmDelete({{ $router->id }}, '{{ addslashes($router->name) }}')"
                                title="Hapus Router"
                                class="flex flex-col items-center justify-center gap-1 py-2 px-1 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-600 dark:text-rose-400 hover:text-white text-[10px] font-bold border border-rose-500/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-500 text-xs">
                    Belum ada router MikroTik yang terdaftar.
                </div>
            @endforelse
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 4. MODAL TAMBAH ROUTER                                              -->
    <!-- =================================================================== -->
    <div x-show="addModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4"
             @click.outside="addModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                    Tambah Router MikroTik Baru
                </h3>
                <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form method="POST" action="{{ route('noc.router.store') }}" class="space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Router *</label>
                    <input type="text" name="name" required placeholder="Contoh: Router Core Bandung / Router Kayuagung"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">IP Host / Address *</label>
                        <input type="text" name="host" required placeholder="103.161.207.34"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Port Router *</label>
                        <input type="number" name="port" required value="18735"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-cyan-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Username Router *</label>
                        <input type="text" name="username" required placeholder="aplikasi"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Password Router *</label>
                        <input type="password" name="password" required placeholder="Password Router"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Wilayah / Kota</label>
                    <input type="text" name="kota" placeholder="Contoh: Bandung, Soreang, Babakan Tarogong"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500">
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="is_active_add" name="is_active" value="1" checked
                           class="rounded border-slate-700 text-cyan-500 focus:ring-cyan-500">
                    <label for="is_active_add" class="text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                        Aktifkan router ini untuk provisioning otomatis
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="addModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold hover:bg-slate-700">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-extrabold shadow-md">
                        Simpan Router
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. MODAL EDIT ROUTER                                                -->
    <!-- =================================================================== -->
    <div x-show="editModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4"
             @click.outside="editModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    Edit Router MikroTik (<span x-text="currentRouter.name"></span>)
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form method="POST" :action="`{{ url('/noc/router') }}/${currentRouter.id}/update`" class="space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Router *</label>
                    <input type="text" name="name" required x-model="currentRouter.name"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-amber-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">IP Host / Address *</label>
                        <input type="text" name="host" required x-model="currentRouter.host"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Port Router *</label>
                        <input type="number" name="port" required x-model="currentRouter.port"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Username Router *</label>
                        <input type="text" name="username" required x-model="currentRouter.username"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Password Baru (Opsional)</label>
                        <input type="password" name="password" placeholder="Kosongkan jika tidak diubah"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Wilayah / Kota</label>
                    <input type="text" name="kota" x-model="currentRouter.kota"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-amber-500">
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="is_active_edit" name="is_active" value="1" :checked="currentRouter.is_active == 1"
                           class="rounded border-slate-700 text-amber-500 focus:ring-amber-500">
                    <label for="is_active_edit" class="text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                        Aktifkan router ini
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold hover:bg-slate-700">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold shadow-md">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. MODAL KONFIRMASI HAPUS ROUTER                                    -->
    <!-- =================================================================== -->
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4"
             @click.outside="deleteModalOpen = false">
            <div class="flex items-center gap-3 text-rose-400">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Hapus Router MikroTik</h3>
                    <p class="text-xs text-slate-400">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300">
                Apakah Anda yakin ingin menghapus router <strong class="text-white" x-text="deleteRouterName"></strong>? Pelanggan yang terpetakan ke router ini akan dialihkan ke status unmapped.
            </p>

            <form method="POST" :action="`{{ url('/noc/router') }}/${deleteRouterId}/delete`" class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                @csrf
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold hover:bg-slate-700 text-xs">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-extrabold shadow-md text-xs">
                    Ya, Hapus Router
                </button>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 7. MODAL LIHAT USER PPPOE DI MIKROTIK                                -->
    <!-- =================================================================== -->
    <div x-show="secretsModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="secretsModalOpen = false" 
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base">User PPPoE Secret di MikroTik</h3>
                        <p class="text-xs text-slate-500">Router: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="secretsRouter.name"></span> (<span class="font-mono text-cyan-500" x-text="secretsRouter.host"></span>)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="fetchSecrets(secretsRouter.id, secretsSearch)" :disabled="secretsLoading" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" :class="{'animate-spin': secretsLoading}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                    <button type="button" @click="secretsModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-200 rounded-lg hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-wrap items-center justify-between gap-3">
                <div class="relative flex-1 min-w-[240px]">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" 
                           x-model="secretsSearch" 
                           placeholder="Cari username PPPoE, profile, comment..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-cyan-500">
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    Total: <span class="font-bold text-cyan-400" x-text="filteredSecrets.length"></span> / <span x-text="secretsData.length"></span> User
                </div>
            </div>

            <!-- Content Area: Table of Secrets -->
            <div class="p-4 flex-1 overflow-y-auto">
                <!-- Loading State -->
                <template x-if="secretsLoading">
                    <div class="py-12 flex flex-col items-center justify-center gap-3 text-cyan-400">
                        <svg class="w-8 h-8 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-xs font-semibold text-slate-400">Mengambil data user PPPoE dari router MikroTik...</p>
                    </div>
                </template>

                <!-- Error State -->
                <template x-if="!secretsLoading && secretsError">
                    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <div class="font-bold mb-1">Gagal Terhubung ke Router</div>
                            <div x-text="secretsError"></div>
                        </div>
                    </div>
                </template>

                <!-- Table of Secrets -->
                <template x-if="!secretsLoading && !secretsError">
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                            <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-2.5 px-3">#</th>
                                    <th class="py-2.5 px-3">Username PPPoE</th>
                                    <th class="py-2.5 px-3 text-center">Status</th>
                                    <th class="py-2.5 px-3">Profile</th>
                                    <th class="py-2.5 px-3">Caller ID / Remote</th>
                                    <th class="py-2.5 px-3">Comment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-medium">
                                <template x-for="(sec, idx) in filteredSecrets" :key="sec.name || idx">
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500" x-text="idx + 1"></td>
                                        <td class="py-2.5 px-3">
                                            <div class="font-bold font-mono text-cyan-400" x-text="sec.name || '-'"></div>
                                            <div class="text-[10px] text-slate-500 font-mono" x-show="sec.service" x-text="'Service: ' + sec.service"></div>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <template x-if="sec.disabled === 'true' || sec.disabled === true">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                                    Disabled / Isolir
                                                </span>
                                            </template>
                                            <template x-if="sec.disabled !== 'true' && sec.disabled !== true">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                                    Aktif
                                                </span>
                                            </template>
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[11px]" x-text="sec.profile || 'default'"></span>
                                        </td>
                                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-400">
                                            <span x-text="sec['remote-address'] || sec['last-caller-id'] || sec['caller-id'] || '-'"></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-[11px] text-slate-400 max-w-[200px] truncate" :title="sec.comment || ''" x-text="sec.comment || '-'"></td>
                                    </tr>
                                </template>
                                <template x-if="filteredSecrets.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                                            <template x-if="secretsData.length === 0">
                                                <span>Tidak ada user PPPoE secret yang ditemukan di router ini. Klik tombol Refresh atau periksa koneksi.</span>
                                            </template>
                                            <template x-if="secretsData.length > 0">
                                                <span>Tidak ada user PPPoE secret yang cocok dengan kata kunci pencarian.</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-between text-xs text-slate-500">
                <span>Data dibaca langsung via koneksi API/Telnet MikroTik Router.</span>
                <button type="button" @click="secretsModalOpen = false" class="px-4 py-1.5 rounded-xl bg-slate-800 text-slate-300 font-bold hover:bg-slate-700">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function routerManagement() {
    return {
        addModalOpen: false,
        editModalOpen: false,
        deleteModalOpen: false,
        secretsModalOpen: false,
        secretsLoading: false,
        secretsError: '',
        secretsData: [],
        secretsSearch: '',
        secretsRouter: { id: null, name: '', host: '' },
        currentRouter: { id: '', name: '', host: '', port: 18735, username: '', password: '', kota: '', is_active: 1 },
        deleteRouterId: null,
        deleteRouterName: '',
        statusMap: {!! json_encode($liveStatus ?? (object)[]) !!},

        openEditModal(id, name, host, port, username, kota, isActive) {
            this.currentRouter = {
                id: id,
                name: name,
                host: host,
                port: port || 18735,
                username: username,
                password: '',
                kota: kota || '',
                is_active: isActive ? 1 : 0
            };
            this.editModalOpen = true;
        },

        confirmDelete(id, name) {
            this.deleteRouterId = id;
            this.deleteRouterName = name;
            this.deleteModalOpen = true;
        },

        openSecretsModal(id, name, host) {
            this.secretsRouter = { id: id, name: name, host: host };
            this.secretsSearch = '';
            this.secretsData = [];
            this.secretsError = '';
            this.secretsModalOpen = true;
            this.fetchSecrets(id);
        },

        async fetchSecrets(id, query = '') {
            if (!id) return;
            this.secretsLoading = true;
            this.secretsError = '';
            try {
                const url = new URL(`{{ url('/noc/router') }}/${id}/secrets`, window.location.origin);
                if (query) {
                    url.searchParams.set('q', query);
                }
                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const raw = await res.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    data = { success: false, message: 'Server membalas HTTP ' + res.status + ' (bukan JSON): ' + raw.substring(0, 150) };
                }

                if (data.success) {
                    this.secretsData = data.secrets || [];
                } else {
                    this.secretsError = data.message || ('Gagal mengambil user dari router (HTTP ' + res.status + ')');
                }
            } catch (err) {
                this.secretsError = 'Koneksi error: ' + err.message;
            } finally {
                this.secretsLoading = false;
            }
        },

        get filteredSecrets() {
            if (!this.secretsSearch) {
                return this.secretsData;
            }
            const q = this.secretsSearch.toLowerCase();
            return this.secretsData.filter(item => {
                const name = (item.name || '').toLowerCase();
                const profile = (item.profile || '').toLowerCase();
                const comment = (item.comment || '').toLowerCase();
                const caller = (item['last-caller-id'] || item['remote-address'] || '').toLowerCase();
                return name.includes(q) || profile.includes(q) || comment.includes(q) || caller.includes(q);
            });
        },

        async testPingRouter(id, host, port, username) {
            this.statusMap = Object.assign({}, this.statusMap, {
                [id]: { status: 'untested', identity: '', loading: true, message: 'Menghubungkan...' }
            });
            try {
                const res = await fetch('{{ route('noc.router.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id: id, host: host, port: port, username: username })
                });
                const raw = await res.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (parseErr) {
                    data = { success: false, message: 'Server membalas HTTP ' + res.status + ' (bukan JSON): ' + raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 200) };
                }
                if (!res.ok && data.success !== true && !data.message) {
                    data.message = 'HTTP ' + res.status + ' ' + res.statusText;
                }
                if (data.success) {
                    this.statusMap = Object.assign({}, this.statusMap, {
                        [id]: {
                            status: 'online',
                            identity: data.identity || 'MikroTik',
                            message: 'Online: ' + (data.identity || 'Connected'),
                            loading: false
                        }
                    });
                } else {
                    this.statusMap = Object.assign({}, this.statusMap, {
                        [id]: {
                            status: 'offline',
                            identity: null,
                            message: data.message || 'Offline / Host tidak merespon',
                            loading: false
                        }
                    });
                }
            } catch(e) {
                this.statusMap = Object.assign({}, this.statusMap, {
                    [id]: {
                        status: 'offline',
                        identity: null,
                        message: 'Koneksi error: ' + e.message,
                        loading: false
                    }
                });
            }
        },

        async syncCustomers(id, name) {
            if (!confirm(`Sinkronkan pelanggan ke router '${name}'?`)) return;
            try {
                const res = await fetch(`{{ url('/noc/router') }}/${id}/sync-customers`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                alert(data.message || 'Sinkronisasi berhasil!');
                window.location.reload();
            } catch(e) {
                alert('Gagal sinkronisasi: ' + e.message);
            }
        }
    };
}
</script>
@endpush

