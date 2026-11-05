@extends('layouts.app')

@section('title', 'Pencarian Terpadu OLT, PON & ODP')

@section('content')
<div class="space-y-4">

    <!-- ── 1. HEADER BANNER WITH BREADCRUMB & METRICS ── -->
    <div class="relative overflow-hidden rounded-xl p-4 sm:p-5 shadow-sm border border-slate-700/50"
         style="background: linear-gradient(110deg, #03232c 0%, #053b47 30%, #085263 65%, #0a697e 100%);">
        
        <!-- Glow Accent -->
        <div class="pointer-events-none absolute -right-12 -bottom-12 w-56 h-56 bg-teal-400/10 rounded-full blur-3xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-inner bg-white/10 border border-white/20 text-white mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                    </svg>
                </div>
                <div>
                    <nav class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest mb-1 text-teal-200">
                        <a href="{{ route('dashboard') }}" class="hover:text-white transition">IMS</a>
                        <span class="text-teal-400/60">&gt;</span>
                        <a href="{{ route('noc.network-olt') }}" class="hover:text-white transition">Menu OLT</a>
                        <span class="text-teal-400/60">&gt;</span>
                        <span class="text-white">Pencarian Terpadu</span>
                    </nav>
                    <h1 class="text-base sm:text-lg font-extrabold text-white tracking-tight leading-tight">
                        Pencarian Data OLT, PON &amp; ODP
                    </h1>
                    <p class="text-xs text-teal-100/90 mt-0.5 leading-relaxed">
                        Cari pelanggan berdasarkan <b>Nama ODP</b>, <b>Nama User</b>, atau <b>Nomor Internet</b> untuk memetakan jalur OLT, PON, dan ODP secara presisi.
                    </p>
                </div>
            </div>

            <!-- Global Quick Stats Counters -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full md:w-auto">
                <div class="px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-left backdrop-blur-xs">
                    <span class="text-[9px] font-bold text-teal-200 uppercase tracking-wider block">Master OLT</span>
                    <strong class="text-xs sm:text-sm font-bold text-white font-mono block mt-0.5">
                        {{ $stats['total_olt'] ?? count($olts) }} Node
                    </strong>
                </div>
                <div class="px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-left backdrop-blur-xs">
                    <span class="text-[9px] font-bold text-teal-200 uppercase tracking-wider block">Total PON</span>
                    <strong class="text-xs sm:text-sm font-bold text-white font-mono block mt-0.5">
                        {{ $stats['total_pon'] ?? 0 }} Port
                    </strong>
                </div>
                <div class="px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-left backdrop-blur-xs">
                    <span class="text-[9px] font-bold text-teal-200 uppercase tracking-wider block">Total ODP</span>
                    <strong class="text-xs sm:text-sm font-bold text-white font-mono block mt-0.5">
                        {{ $stats['total_odp'] ?? 0 }} Titik
                    </strong>
                </div>
                <div class="px-3 py-2 rounded-lg bg-teal-500/20 border border-teal-400/30 text-left backdrop-blur-xs">
                    <span class="text-[9px] font-bold text-teal-200 uppercase tracking-wider block">Total Pelanggan</span>
                    <strong class="text-xs sm:text-sm font-bold text-teal-300 font-mono block mt-0.5">
                        {{ number_format($stats['total_user'] ?? 0) }} User
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. SEARCH FILTER BOX ── -->
    <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3.5">
        <form method="GET" action="{{ route('noc.network-olt.search') }}" class="space-y-3">
            
            <!-- Input Bar & Search Button (Solid Grid Layout: Rapi, Presisi & Bebas Bug Collapsing) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 items-center">
                
                <!-- 1. Search Input: Lebar Utama (~67% pada desktop) -->
                <div class="md:col-span-7 lg:col-span-8 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 pl-3.5 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ $q }}" 
                        placeholder="Ketik Nama ODP (cth: ENTIN), Nama User (cth: IRMAWATI), atau No. Internet (cth: 3511221)..." 
                        class="w-full h-11 pl-10 pr-10 rounded-xl text-sm font-mono bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/50 shadow-xs"
                        autofocus
                    />
                    @if($q !== '')
                        <a href="{{ route('noc.network-olt.search') }}" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold" title="Hapus pencarian">
                            &times;
                        </a>
                    @endif
                </div>

                <!-- 2. OLT Filter Dropdown: (~17% pada desktop) -->
                <div class="md:col-span-3 lg:col-span-2">
                    <select 
                        name="olt_id" 
                        class="w-full h-11 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/50 cursor-pointer font-medium shadow-xs"
                    >
                        <option value="">-- Semua OLT --</option>
                        @foreach($olts as $o)
                            <option value="{{ $o->olt_id }}" {{ (string)$filterOlt === (string)$o->olt_id ? 'selected' : '' }}>
                                OLT {{ $o->olt_id }} - {{ strtoupper($o->nama_olt) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Submit Button: (~16% pada desktop) -->
                <div class="md:col-span-2">
                    <button 
                        type="submit" 
                        class="w-full h-11 px-4 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                        </svg>
                        <span>Cari Data</span>
                    </button>
                </div>
            </div>

            <!-- Search Type Criteria (Pills) -->
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mr-1 uppercase tracking-wider">Kategori Cari:</span>
                
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer transition duration-150 {{ $searchType === 'all' ? 'bg-sky-50 dark:bg-sky-950/70 border-sky-500 text-sky-700 dark:text-sky-300 shadow-2xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white' }}">
                    <input type="radio" name="type" value="all" {{ $searchType === 'all' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <svg class="w-3.5 h-3.5 {{ $searchType === 'all' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    <span>Semua Kriteria</span>
                </label>

                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer transition duration-150 {{ $searchType === 'odp' ? 'bg-sky-50 dark:bg-sky-950/70 border-sky-500 text-sky-700 dark:text-sky-300 shadow-2xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white' }}">
                    <input type="radio" name="type" value="odp" {{ $searchType === 'odp' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <svg class="w-3.5 h-3.5 {{ $searchType === 'odp' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span>Nama ODP</span>
                </label>

                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer transition duration-150 {{ $searchType === 'user' ? 'bg-sky-50 dark:bg-sky-950/70 border-sky-500 text-sky-700 dark:text-sky-300 shadow-2xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white' }}">
                    <input type="radio" name="type" value="user" {{ $searchType === 'user' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <svg class="w-3.5 h-3.5 {{ $searchType === 'user' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <span>Nama User</span>
                </label>

                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer transition duration-150 {{ $searchType === 'internet' ? 'bg-sky-50 dark:bg-sky-950/70 border-sky-500 text-sky-700 dark:text-sky-300 shadow-2xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white' }}">
                    <input type="radio" name="type" value="internet" {{ $searchType === 'internet' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <svg class="w-3.5 h-3.5 {{ $searchType === 'internet' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.25 8.25h13.5m-13.5 7.5h13.5m-1.5-12-3 16.5m-6-16.5-3 16.5" />
                    </svg>
                    <span>Nomor Internet</span>
                </label>

                @if($q !== '' || !empty($filterOlt))
                    <a href="{{ route('noc.network-olt.search') }}" class="inline-flex items-center gap-1 text-xs text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 ml-auto font-semibold transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- ── 3. SEARCH RESULTS AREA ── -->
    @if($q !== '')
        
        <!-- Result Summary Bar -->
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $results->count() > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                    Hasil Pencarian untuk: <span class="font-mono text-sky-600 dark:text-sky-400 font-bold">"{{ $q }}"</span>
                </h2>
                <span class="text-[11px] px-2 py-0.5 rounded-md font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ $results->count() }} User Ditemukan
                </span>
            </div>
        </div>

        <!-- Matched ODPs Preview (if any) -->
        @if($matchedOdps->isNotEmpty())
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-2 shadow-2xs">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200">
                    <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span>Titik ODP yang Cocok dengan "{{ $q }}":</span>
                    <span class="text-[10px] text-slate-500 font-mono font-bold">({{ $matchedOdps->count() }} ODP)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($matchedOdps as $mOdp)
                        <div class="p-3 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-1.5 shadow-2xs">
                            <div class="flex items-center justify-between gap-1.5">
                                <strong class="text-slate-900 dark:text-white font-bold truncate text-xs">{{ $mOdp->nama_odp }}</strong>
                                <span class="text-[10px] px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 font-mono font-bold shrink-0">
                                    OLT {{ $mOdp->olt_id }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-600 dark:text-slate-400 truncate flex items-center gap-1.5 font-mono">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                                <span>{{ $mOdp->nama_pon ?: 'PON -' }}</span>
                            </div>
                            <div class="text-[10.5px] text-slate-500 dark:text-slate-400 font-mono pt-1 border-t border-slate-100 dark:border-slate-800/80">
                                Kapasitas: <b class="text-slate-700 dark:text-slate-300">{{ $mOdp->port_max ?? 8 }} Port</b>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main User Table -->
        @if($results->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100/90 dark:bg-slate-950/90 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="py-3 px-3 w-10 text-center">No</th>
                            <th class="py-3 px-3">Nama User (Pelanggan)</th>
                            <th class="py-3 px-3">Nomor Internet</th>
                            <th class="py-3 px-3">Lokasi OLT</th>
                            <th class="py-3 px-3">Port PON</th>
                            <th class="py-3 px-3">Titik ODP</th>
                            <th class="py-3 px-3">Alamat &amp; Kontak</th>
                            <th class="py-3 px-3 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800/80 text-slate-800 dark:text-slate-200">
                        @foreach($results as $idx => $r)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <!-- No -->
                                <td class="py-3 px-3 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $idx + 1 }}
                                </td>

                                <!-- Nama User -->
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white leading-tight text-xs">
                                        {{ $r->nama_user ?: '-' }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold">
                                            AKTIF
                                        </span>
                                        @if(!empty($r->speed) && $r->speed !== '-')
                                            <span class="text-[9.5px] font-mono font-semibold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950 px-1.5 py-0.5 rounded border border-sky-200 dark:border-sky-800 inline-flex items-center gap-1">
                                                <svg class="w-2.5 h-2.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                                </svg>
                                                <span>{{ $r->speed }}</span>
                                            </span>
                                        @endif
                                        @if(!empty($r->keterangan))
                                            <span class="text-[9.5px] text-slate-400 italic truncate max-w-xs" title="{{ $r->keterangan }}">
                                                ({{ $r->keterangan }})
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Nomor Internet -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 font-mono font-bold text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800/70 px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700">
                                        <span>{{ $r->nomor_internet ?: '-' }}</span>
                                        @if(!empty($r->nomor_internet))
                                            <button 
                                                type="button" 
                                                onclick="navigator.clipboard.writeText('{{ $r->nomor_internet }}'); alert('Nomor Internet disalin: {{ $r->nomor_internet }}')"
                                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer" 
                                                title="Salin Nomor Internet"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- OLT Mana -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-mono bg-sky-50 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/80">
                                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                        <span>OLT {{ $r->olt_id }}</span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-normal">({{ strtoupper($r->olt_nama) }})</span>
                                    </span>
                                </td>

                                <!-- PON Mana -->
                                <td class="py-3 px-3">
                                    <div class="font-mono text-slate-900 dark:text-slate-100 font-bold text-xs leading-tight">
                                        {{ $r->nama_pon ?: 'PON -' }}
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono block mt-0.5">Port ID: #{{ $r->pon_id ?: '-' }}</span>
                                </td>

                                <!-- ODP Mana -->
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1 font-bold text-slate-900 dark:text-white text-xs leading-tight">
                                        <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                        </svg>
                                        <span>{{ $r->nama_odp ?: 'ODP -' }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-mono block mt-0.5">
                                        Kapasitas: {{ $r->odp_port_max ?? 8 }} Port
                                    </span>
                                </td>

                                <!-- Alamat & Kontak -->
                                <td class="py-3 px-3 max-w-xs">
                                    <div class="text-[11px] text-slate-700 dark:text-slate-300 truncate font-medium" title="{{ $r->alamat }}">
                                        {{ $r->alamat ?: '-' }}
                                    </div>
                                    <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                                        @if(!empty($r->nomor_hp) && $r->nomor_hp !== '-')
                                            @php
                                                $cleanWa = preg_replace('/[^0-9]/', '', $r->nomor_hp);
                                                if (str_starts_with($cleanWa, '0')) {
                                                    $cleanWa = '62' . substr($cleanWa, 1);
                                                }
                                            @endphp
                                            <a href="https://wa.me/{{ $cleanWa }}" target="_blank" class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 hover:underline font-bold" title="Kirim WhatsApp">
                                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.774-.75 4.5 4.5 0 0 1 1.05-2.73A8.134 8.134 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                                </svg>
                                                <span>{{ $r->nomor_hp }}</span>
                                            </a>
                                            <button 
                                                type="button" 
                                                onclick="navigator.clipboard.writeText('{{ $r->nomor_hp }}'); alert('Nomor HP disalin: {{ $r->nomor_hp }}')"
                                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer" 
                                                title="Salin Nomor HP"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-slate-400">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                                </svg>
                                                <span>-</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Aksi (Hanya Hierarki & Profil Pelanggan) -->
                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Buka di Hierarki OLT -->
                                        <a 
                                            href="{{ route('noc.network-olt', ['olt_id' => $r->olt_id, 'search' => $r->nama_user]) }}"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs"
                                            title="Buka Hierarki OLT Explorer"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                            </svg>
                                            <span>Hierarki</span>
                                        </a>

                                        <!-- Buka Profile Pelanggan (jika ada nomor_internet) -->
                                        @if(!empty($r->nomor_internet))
                                            <a 
                                                href="{{ route('teknik.pelanggan.profile', ['nomor_internet' => $r->nomor_internet]) }}"
                                                class="px-2.5 py-1.5 rounded-lg bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 dark:hover:bg-sky-900/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs"
                                                title="Lihat Profil Detail Pelanggan"
                                            >
                                                <svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span>Profil</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Data Tidak Ditemukan -->
            <div class="p-8 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                    Data Tidak Ditemukan
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    Tidak ditemukan data pelanggan untuk kata kunci <b class="font-mono text-slate-700 dark:text-slate-300">"{{ $q }}"</b>. Pastikan ejaan nama ODP, nama user, atau nomor internet sudah sesuai.
                </p>
                <div class="pt-1">
                    <a href="{{ route('noc.network-olt.search') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                        <span>&larr; Bersihkan Pencarian</span>
                    </a>
                </div>
            </div>
        @endif

    @else
        
        <!-- ── 4. IDLE STATE: PANDUAN PENCARIAN & KARTU FITUR ── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pt-2">
            
            <!-- Card 1: Nama ODP -->
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-xs hover:shadow-md hover:border-sky-500/40 dark:hover:border-sky-500/40 transition duration-200 group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 dark:bg-sky-500/15 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Cari Berdasarkan Nama ODP
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mt-1.5">
                            Ketik nama ODP (contoh: <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10.5px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">MSN REOG</span> atau <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10.5px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">POS RAJA MANTRI</span>) untuk menampilkan seluruh pelanggan yang terhubung pada ODP tersebut beserta OLT dan PON induknya.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Nama User -->
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-xs hover:shadow-md hover:border-emerald-500/40 dark:hover:border-emerald-500/40 transition duration-200 group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Cari Berdasarkan Nama User
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mt-1.5">
                            Ketik nama pelanggan (contoh: <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10.5px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">IRMAWATI</span> atau <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10.5px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">Dodi</span>) untuk mengetahui di OLT mana, port PON ke berapa, dan ODP mana perangkat pelanggan berada.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 3: Nomor Internet -->
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-xs hover:shadow-md hover:border-indigo-500/40 dark:hover:border-indigo-500/40 transition duration-200 group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/15 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.25 8.25h13.5m-13.5 7.5h13.5m-1.5-12-3 16.5m-6-16.5-3 16.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Cari Nomor Internet
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mt-1.5">
                            Ketik nomor internet pelanggan (contoh: <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10.5px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">3511221</span>) untuk langsung melacak identitas fisik topologi OLT, PON, dan ODP pelanggan saat menangani keluhan atau tiket gangguan.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    @endif

</div>
@endsection

