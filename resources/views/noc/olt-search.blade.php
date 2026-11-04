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
            
            <!-- Input Bar & Search Button -->
            <div class="flex flex-col sm:flex-row items-stretch gap-2">
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ $q }}" 
                        placeholder="Ketik Nama ODP (cth: MSN REOG), Nama User (cth: IRMAWATI), atau No. Internet (cth: 3511221)..." 
                        class="w-full h-10 pl-9 pr-10 rounded-lg text-xs font-mono bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/50"
                        autofocus
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                    </svg>
                    @if($q !== '')
                        <a href="{{ route('noc.network-olt.search') }}" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold" title="Hapus pencarian">
                            &times;
                        </a>
                    @endif
                </div>

                <!-- OLT Filter Dropdown -->
                <div class="w-full sm:w-48">
                    <select 
                        name="olt_id" 
                        class="w-full h-10 px-3 rounded-lg text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/50 cursor-pointer font-medium"
                    >
                        <option value="">-- Semua OLT --</option>
                        @foreach($olts as $o)
                            <option value="{{ $o->olt_id }}" {{ (string)$filterOlt === (string)$o->olt_id ? 'selected' : '' }}>
                                OLT {{ $o->olt_id }} - {{ strtoupper($o->nama_olt) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="h-10 px-5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer flex-shrink-0"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                    </svg>
                    <span>Cari Data</span>
                </button>
            </div>

            <!-- Search Type Criteria (Pills) -->
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mr-1">Kategori Cari:</span>
                
                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium border cursor-pointer transition {{ $searchType === 'all' ? 'bg-sky-500/10 border-sky-500 text-sky-600 dark:text-sky-400 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <input type="radio" name="type" value="all" {{ $searchType === 'all' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <span>🌐 Semua Kriteria</span>
                </label>

                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium border cursor-pointer transition {{ $searchType === 'odp' ? 'bg-sky-500/10 border-sky-500 text-sky-600 dark:text-sky-400 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <input type="radio" name="type" value="odp" {{ $searchType === 'odp' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <span>📍 Nama ODP</span>
                </label>

                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium border cursor-pointer transition {{ $searchType === 'user' ? 'bg-sky-500/10 border-sky-500 text-sky-600 dark:text-sky-400 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <input type="radio" name="type" value="user" {{ $searchType === 'user' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <span>👤 Nama User</span>
                </label>

                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium border cursor-pointer transition {{ $searchType === 'internet' ? 'bg-sky-500/10 border-sky-500 text-sky-600 dark:text-sky-400 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <input type="radio" name="type" value="internet" {{ $searchType === 'internet' ? 'checked' : '' }} onchange="this.form.submit()" class="hidden">
                    <span>🔢 Nomor Internet</span>
                </label>

                @if($q !== '' || !empty($filterOlt))
                    <a href="{{ route('noc.network-olt.search') }}" class="text-[11px] text-rose-500 hover:underline ml-auto font-medium">
                        Reset Filter
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
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200">
                    <span class="text-sky-500">📍</span>
                    <span>Titik ODP yang Cocok dengan "{{ $q }}":</span>
                    <span class="text-[10px] text-slate-500 font-mono">({{ $matchedOdps->count() }} ODP)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($matchedOdps as $mOdp)
                        <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <strong class="text-slate-900 dark:text-white font-bold truncate text-[11.5px]">{{ $mOdp->nama_odp }}</strong>
                                <span class="text-[9.5px] px-1.5 py-0.5 rounded bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-mono font-bold">
                                    OLT {{ $mOdp->olt_id }}
                                </span>
                            </div>
                            <div class="text-[10.5px] text-slate-500 dark:text-slate-400 truncate">
                                🔌 {{ $mOdp->nama_pon ?: 'PON -' }}
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-600 dark:text-slate-400 font-mono pt-1 border-t border-slate-100 dark:border-slate-900">
                                <span>Port: {{ $mOdp->port_terpakai ?? 0 }} / {{ $mOdp->port_max ?? 8 }}</span>
                                @if(!empty($mOdp->latitude) && !empty($mOdp->longitude))
                                    <a href="{{ route('teknik.coverage', ['coord' => "{$mOdp->latitude},{$mOdp->longitude}"]) }}" class="text-sky-600 dark:text-sky-400 hover:underline">
                                        Cek Coverage &rarr;
                                    </a>
                                @endif
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
                        <tr class="bg-slate-100/80 dark:bg-slate-950/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="py-3 px-3 w-10 text-center">No</th>
                            <th class="py-3 px-3">Nama User (Pelanggan)</th>
                            <th class="py-3 px-3">Nomor Internet</th>
                            <th class="py-3 px-3">Lokasi OLT</th>
                            <th class="py-3 px-3">Port PON</th>
                            <th class="py-3 px-3">Titik ODP</th>
                            <th class="py-3 px-3">Alamat &amp; Kontak</th>
                            <th class="py-3 px-3 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800/80 text-slate-800 dark:text-slate-200">
                        @foreach($results as $idx => $r)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <!-- No -->
                                <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $idx + 1 }}
                                </td>

                                <!-- Nama User -->
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white leading-tight">
                                        {{ $r->nama_user ?: '-' }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold">
                                            AKTIF
                                        </span>
                                        @if(!empty($r->speed) && $r->speed !== '-')
                                            <span class="text-[9.5px] font-mono text-slate-500 dark:text-slate-400">
                                                ⚡ {{ $r->speed }}
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
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-1.5 font-mono font-bold text-slate-900 dark:text-white">
                                        <span>{{ $r->nomor_internet ?: '-' }}</span>
                                        @if(!empty($r->nomor_internet))
                                            <button 
                                                type="button" 
                                                onclick="navigator.clipboard.writeText('{{ $r->nomor_internet }}'); alert('Nomor Internet disalin: {{ $r->nomor_internet }}')"
                                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition" 
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
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold font-mono bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                        <span>OLT {{ $r->olt_id }}</span>
                                        <span class="text-[10px] font-normal uppercase opacity-80">({{ $r->olt_nama }})</span>
                                    </span>
                                </td>

                                <!-- PON Mana -->
                                <td class="py-2.5 px-3">
                                    <div class="font-mono text-slate-900 dark:text-slate-100 font-semibold text-[11.5px]">
                                        {{ $r->nama_pon ?: 'PON -' }}
                                    </div>
                                    <span class="text-[9.5px] text-slate-400 font-mono">Port ID: #{{ $r->pon_id ?: '-' }}</span>
                                </td>

                                <!-- ODP Mana -->
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-1">
                                        <span class="text-sky-500">📍</span>
                                        <strong class="text-slate-900 dark:text-white font-bold text-[11.5px]">
                                            {{ $r->nama_odp ?: 'ODP -' }}
                                        </strong>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                        Kapasitas: {{ $r->odp_port_terpakai ?? 0 }}/{{ $r->odp_port_max ?? 8 }} Port
                                    </div>
                                </td>

                                <!-- Alamat & Kontak -->
                                <td class="py-2.5 px-3 max-w-xs">
                                    <div class="text-[11px] text-slate-700 dark:text-slate-300 truncate" title="{{ $r->alamat }}">
                                        {{ $r->alamat ?: '-' }}
                                    </div>
                                    <div class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                        📞 {{ $r->nomor_hp ?: '-' }}
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-2.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Buka di Hierarki OLT -->
                                        <a 
                                            href="{{ route('noc.network-olt', ['olt_id' => $r->olt_id, 'search' => $r->nama_user]) }}"
                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[10.5px] font-semibold transition inline-flex items-center gap-1"
                                            title="Buka Hierarki OLT Explorer"
                                        >
                                            <span>Hierarki</span>
                                        </a>

                                        <!-- Buka Profile Pelanggan (jika ada nomor_internet) -->
                                        @if(!empty($r->nomor_internet))
                                            <a 
                                                href="{{ route('teknik.pelanggan.profile', ['nomor_internet' => $r->nomor_internet]) }}"
                                                class="px-2 py-1 rounded bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 dark:hover:bg-sky-900/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 text-[10.5px] font-semibold transition inline-flex items-center gap-1"
                                                title="Lihat Profil Detail Pelanggan"
                                            >
                                                <span>Profil</span>
                                            </a>
                                        @endif

                                        <!-- Cek Coverage ODP -->
                                        @if(!empty($r->odp_lat) && !empty($r->odp_lng))
                                            <a 
                                                href="{{ route('teknik.coverage', ['coord' => "{$r->odp_lat},{$r->odp_lng}"]) }}"
                                                class="px-2 py-1 rounded bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[10.5px] font-semibold transition"
                                                title="Cek Titik GIS Coverage ODP"
                                            >
                                                <span>GIS</span>
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
            
            <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 hover:border-sky-500/50 transition">
                <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-sm">
                    📍
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Cari Berdasarkan Nama ODP
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    Ketik nama ODP (contoh: <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">MSN REOG</code> atau <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">POS RAJA MANTRI</code>) untuk menampilkan seluruh pelanggan yang terhubung pada ODP tersebut beserta OLT dan PON induknya.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 hover:border-emerald-500/50 transition">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                    👤
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Cari Berdasarkan Nama User
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    Ketik nama pelanggan (contoh: <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">IRMAWATI</code> atau <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">Dodi</code>) untuk mengetahui di OLT mana, port PON ke berapa, dan ODP mana perangkat pelanggan berada.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 hover:border-purple-500/50 transition">
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-sm">
                    🔢
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Cari Nomor Internet
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    Ketik nomor internet pelanggan (contoh: <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">3511221</code>) untuk langsung melacak identitas fisik topologi OLT, PON, dan ODP pelanggan saat menangani keluhan atau tiket gangguan.
                </p>
            </div>

        </div>

    @endif

</div>
@endsection
