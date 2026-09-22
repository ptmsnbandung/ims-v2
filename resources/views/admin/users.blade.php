@extends('layouts.app')

@section('page_title', 'Manajemen User & Hak Akses')

@section('content')
<div class="space-y-6" x-data="userManagement()">
    <!-- Top Breadcrumbs & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                <span>IMS</span>
                <span>&rsaquo;</span>
                <span>Pengaturan</span>
                <span>&rsaquo;</span>
                <span class="text-blue-600 dark:text-blue-400 font-semibold">Manajemen User</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-xs">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen User &amp; Hak Akses</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Kelola data pengguna, peran akses (role), dan status akun sistem.</p>
                </div>
            </div>
        </div>

        <!-- Tambah User Baru Trigger -->
        <button @click="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-600/25 transition-all active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
            </svg>
            <span>Tambah User Baru</span>
        </button>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Pengguna -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between relative overflow-hidden group hover:border-blue-400 dark:hover:border-slate-700 transition">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pengguna</p>
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $totalUsers }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Seluruh akun terdaftar</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-inner">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-blue-500 to-transparent"></div>
        </div>

        <!-- 2. Pengguna Aktif -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between relative overflow-hidden group hover:border-emerald-400 dark:hover:border-slate-700 transition">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pengguna Aktif</p>
                <p class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $activeUsers }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Dapat login ke sistem</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-inner">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-emerald-500 to-transparent"></div>
        </div>

        <!-- 3. Pengguna Nonaktif -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between relative overflow-hidden group hover:border-rose-400 dark:hover:border-slate-700 transition">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pengguna Nonaktif</p>
                <p class="text-3xl font-extrabold text-rose-600 dark:text-rose-400">{{ $inactiveUsers }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Akses login terkunci</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400 shadow-inner">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-rose-500 to-transparent"></div>
        </div>

        <!-- 4. Level & Role -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs flex items-center justify-between relative overflow-hidden group hover:border-purple-400 dark:hover:border-slate-700 transition">
            <div class="space-y-1">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Level &amp; Role</p>
                <p class="text-3xl font-extrabold text-purple-600 dark:text-purple-400">{{ $totalRoles }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Hak akses dikonfigurasi</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400 shadow-inner">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-purple-500 to-transparent"></div>
        </div>
    </div>

    <!-- Filter Bar & Search Box -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs">
        <form method="GET" action="{{ route('admin.users') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="CARI USERNAME, NAMA, ROLE, ATAU JABATAN..."
                           class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 uppercase tracking-wide">
                </div>

                <!-- Role Filter -->
                <div class="w-full sm:w-44">
                    <select name="role" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="all" {{ $selectedRole === 'all' ? 'selected' : '' }}>Semua Role</option>
                        @foreach($levelList as $lvl)
                            <option value="{{ $lvl->kode_level }}" {{ $selectedRole === $lvl->kode_level ? 'selected' : '' }}>
                                {{ $lvl->nama_level }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-40">
                    <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="1" {{ $selectedStatus === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="2" {{ $selectedStatus === '2' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Filter Submit Button -->
                <button type="submit" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <span>Filter</span>
                </button>
            </div>

            <!-- Items per Page -->
            <div class="flex items-center justify-end gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span>Tampilkan</span>
                <select name="per_page" onchange="this.form.submit()" class="px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-800 dark:text-white focus:outline-none focus:border-blue-500">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span>baris</span>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 text-[11px] uppercase tracking-wider font-semibold text-slate-600 dark:text-slate-400">
                        <th class="px-5 py-4">PENGGUNA</th>
                        <th class="px-5 py-4">USERNAME</th>
                        <th class="px-5 py-4">ROLE / LEVEL</th>
                        <th class="px-5 py-4">STATUS</th>
                        <th class="px-5 py-4">AKTIVITAS TERAKHIR</th>
                        <th class="px-5 py-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs">
                    @forelse($users as $u)
                        @php
                            $isCurrentUser = auth()->user()->kode_pengguna === $u->kode_pengguna;
                            $namaLvl = strtolower($u->nama_level);
                            
                            // Role Badge Styling (Dual Theme Compatible)
                            if (str_contains($namaLvl, 'direktur') || str_contains($namaLvl, 'admin')) {
                                $roleBadge = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/30';
                                $roleLabel = 'Admin';
                            } elseif (str_contains($namaLvl, 'finance')) {
                                $roleBadge = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/30';
                                $roleLabel = 'Finance';
                            } elseif (str_contains($namaLvl, 'noc')) {
                                $roleBadge = 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/30';
                                $roleLabel = 'NOC';
                            } elseif (str_contains($namaLvl, 'teknik')) {
                                $roleBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/30';
                                $roleLabel = 'Teknik';
                            } elseif (str_contains($namaLvl, 'sales') || str_contains($namaLvl, 'salses')) {
                                $roleBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30';
                                $roleLabel = 'Sales';
                            } else {
                                $roleBadge = 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/30';
                                $roleLabel = $u->nama_level;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- PENGGUNA -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 p-0.5 flex items-center justify-center font-bold text-xs text-white flex-shrink-0 shadow-xs">
                                        <div class="w-full h-full bg-blue-50 dark:bg-slate-900 rounded-full flex items-center justify-center font-bold text-sm text-blue-700 dark:text-blue-400">
                                            {{ strtoupper(substr($u->nama_karyawan, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white text-sm tracking-wide">{{ $u->nama_karyawan }}</span>
                                            @if($isCurrentUser)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/40">
                                                    Akun Anda
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            {{ $u->nama_jabatan }} &bull; <span class="font-mono text-slate-400 dark:text-slate-500">{{ $u->kode_karyawan ?: '-' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- USERNAME -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 font-mono text-slate-700 dark:text-slate-300 text-xs">
                                    {{ $u->username }}
                                </span>
                            </td>

                            <!-- ROLE / LEVEL -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $roleBadge }}">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                    </svg>
                                    <span>{{ $roleLabel }}</span>
                                </span>
                            </td>

                            <!-- STATUS -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($u->status_aktif == '1')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Aktif</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Nonaktif</span>
                                    </span>
                                @endif
                            </td>

                            <!-- AKTIVITAS TERAKHIR -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($u->las_login)
                                    <p class="text-slate-800 dark:text-slate-300 font-medium">{{ \Carbon\Carbon::parse($u->las_login)->format('d M Y H:i') }} WIB</p>
                                    <p class="text-[10px] text-slate-500 font-mono">IP: {{ $u->last_ip ?: '127.0.0.1' }}</p>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 italic">Belum pernah login</span>
                                @endif
                            </td>

                            <!-- AKSI -->
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-1.5">
                                    <!-- Edit Button -->
                                    <button @click="openEditModal({{ json_encode($u) }})"
                                            title="Edit Data Pengguna"
                                            class="p-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:hover:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30 transition cursor-pointer">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>

                                    <!-- Toggle Lock / Unlock Status -->
                                    @if(!$isCurrentUser)
                                        <form action="{{ route('admin.users.toggle-status', $u->kode_pengguna) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif pengguna {{ $u->username }}?');">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ $u->status_aktif == '1' ? 'Kunci / Nonaktifkan Akun' : 'Aktifkan Akun' }}"
                                                    class="p-2 rounded-xl {{ $u->status_aktif == '1' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:hover:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30' }} transition cursor-pointer">
                                                @if($u->status_aktif == '1')
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>

                                        <!-- Delete Button -->
                                        <form action="{{ route('admin.users.delete', $u->kode_pengguna) }}" method="POST" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus user {{ $u->username }} secara permanen?');">
                                            @csrf
                                            <button type="submit"
                                                    title="Hapus Pengguna"
                                                    class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 dark:text-rose-400 dark:border-rose-500/30 transition cursor-pointer">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-400 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <p class="text-sm font-medium">Tidak ada data pengguna yang sesuai dengan pencarian atau filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 flex items-center justify-between">
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Menampilkan <strong class="text-slate-900 dark:text-white">{{ $users->firstItem() ?? 0 }}</strong> - <strong class="text-slate-900 dark:text-white">{{ $users->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-900 dark:text-white">{{ $users->total() }}</strong> pengguna
                </p>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 1: TAMBAH USER BARU                                         -->
    <!-- ================================================================= -->
    <div x-show="isCreateOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="isCreateOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="isCreateOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Panel -->
            <div x-show="isCreateOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full">
                
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">Tambah User Baru</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Buat akun pengguna baru dan tentukan hak akses peran.</p>
                        </div>
                    </div>
                    <button @click="isCreateOpen = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <!-- Nama Lengkap / Karyawan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap / Karyawan <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   name="nama_lengkap"
                                   required
                                   placeholder="Contoh: FARICH AGUSTIAN"
                                   class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500">
                        </div>

                        <!-- Username & Role -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username / Login ID <span class="text-rose-500">*</span></label>
                                <input type="text"
                                       name="username"
                                       required
                                       placeholder="farich@ptmsn.co.id"
                                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Role / Hak Akses <span class="text-rose-500">*</span></label>
                                <select name="kode_level" required class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                                    <option value="" disabled selected>Pilih Role</option>
                                    @foreach($levelList as $lvl)
                                        <option value="{{ $lvl->kode_level }}">{{ $lvl->nama_level }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Jabatan & Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jabatan</label>
                                <input type="text"
                                       name="jabatan"
                                       placeholder="STAF / IT SUPPORT"
                                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Status Akun <span class="text-rose-500">*</span></label>
                                <select name="status_aktif" required class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                                    <option value="1" selected>Aktif (Dapat Login)</option>
                                    <option value="2">Nonaktif (Akses Terkunci)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Card Password -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                </svg>
                                <span>Password Akun Baru</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">Password Baru <span class="text-rose-500">*</span></label>
                                    <input type="password"
                                           name="password"
                                           required
                                           minlength="6"
                                           placeholder="Minimal 6 karakter"
                                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">Ulangi Password Baru <span class="text-rose-500">*</span></label>
                                    <input type="password"
                                           name="password_confirmation"
                                           required
                                           minlength="6"
                                           placeholder="Ulangi password"
                                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 flex items-center justify-end gap-3">
                        <button type="button" @click="isCreateOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-semibold shadow-md shadow-blue-600/25 transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H7.5m9 0a2.25 2.25 0 0 1 2.25 2.25v14.25a2.25 2.25 0 0 1-2.25 2.25H7.5A2.25 2.25 0 0 1 5.25 20.25V6A2.25 2.25 0 0 1 7.5 3.75h9Z" />
                            </svg>
                            <span>Simpan User Baru</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 2: EDIT DATA PENGGUNA                                       -->
    <!-- ================================================================= -->
    <div x-show="isEditOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="isEditOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="isEditOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Panel -->
            <div x-show="isEditOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full">
                
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">Edit Data Pengguna</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5" x-text="editForm.kode_pengguna + ' &bull; ' + (editForm.kode_karyawan || '-')"></p>
                        </div>
                    </div>
                    <button @click="isEditOpen = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Form -->
                <form :action="'{{ url('admin/users') }}/' + editForm.kode_pengguna + '/update'" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <!-- Nama Lengkap / Karyawan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap / Karyawan <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   name="nama_lengkap"
                                   x-model="editForm.nama_karyawan"
                                   required
                                   class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                        </div>

                        <!-- Username & Role -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username / Login ID <span class="text-rose-500">*</span></label>
                                <input type="text"
                                       name="username"
                                       x-model="editForm.username"
                                       required
                                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-mono text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Role / Hak Akses <span class="text-rose-500">*</span></label>
                                <select name="kode_level" x-model="editForm.kode_level" required class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                                    @foreach($levelList as $lvl)
                                        <option value="{{ $lvl->kode_level }}">{{ $lvl->nama_level }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Jabatan & Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jabatan</label>
                                <input type="text"
                                       name="jabatan"
                                       x-model="editForm.nama_jabatan"
                                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Status Akun <span class="text-rose-500">*</span></label>
                                <select name="status_aktif" x-model="editForm.status_aktif" required class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                                    <option value="1">Aktif (Dapat Login)</option>
                                    <option value="2">Nonaktif (Akses Terkunci)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Card Ubah Password -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                </svg>
                                <span>Ubah Password (Kosongkan jika tidak diubah)</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">Password Baru</label>
                                    <input type="password"
                                           name="password"
                                           minlength="6"
                                           placeholder="Minimal 6 karakter"
                                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">Ulangi Password Baru</label>
                                    <input type="password"
                                           name="password_confirmation"
                                           minlength="6"
                                           placeholder="Ulangi password"
                                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 flex items-center justify-end gap-3">
                        <button type="button" @click="isEditOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-semibold shadow-md shadow-blue-600/25 transition cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H7.5m9 0a2.25 2.25 0 0 1 2.25 2.25v14.25a2.25 2.25 0 0 1-2.25 2.25H7.5A2.25 2.25 0 0 1 5.25 20.25V6A2.25 2.25 0 0 1 7.5 3.75h9Z" />
                            </svg>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function userManagement() {
    return {
        isCreateOpen: false,
        isEditOpen: false,
        editForm: {
            kode_pengguna: '',
            kode_karyawan: '',
            nama_karyawan: '',
            username: '',
            kode_level: '',
            nama_jabatan: '',
            status_aktif: '1',
        },
        openCreateModal() {
            this.isCreateOpen = true;
        },
        openEditModal(userData) {
            this.editForm = {
                kode_pengguna: userData.kode_pengguna,
                kode_karyawan: userData.kode_karyawan || '',
                nama_karyawan: userData.nama_karyawan,
                username: userData.username,
                kode_level: userData.kode_level,
                nama_jabatan: userData.nama_jabatan || 'Staff',
                status_aktif: userData.status_aktif || '1',
            };
            this.isEditOpen = true;
        }
    }
}
</script>
@endsection
