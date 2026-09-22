@extends('layouts.app', ['title' => 'Dashboard Utama'])

@section('page_title', 'Dashboard Utama')

@section('content')
<div class="space-y-6">
    <!-- Role Welcome Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-800 border border-slate-800 p-6 sm:p-8">
        <div class="pointer-events-none absolute -right-10 -bottom-10 w-60 h-60 bg-cyan-500/10 rounded-full blur-2xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold border mb-3 {{ $user->role_badge_classes }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    <span>Level Pengguna: {{ $user->nama_level }}</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Selamat Datang, {{ $user->nama }}!
                </h2>
                <p class="text-sm text-slate-400 mt-1 max-w-2xl">
                    {{ $user->role_description }} &middot; Akun: <strong class="text-slate-300">{{ $user->username }}</strong>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="px-4 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs">
                    <span class="text-slate-400">Waktu Server:</span>
                    <span class="font-medium text-slate-200 ml-1">{{ now()->format('d M Y, H:i') }} WIB</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Role-Specific Feature Overview Placeholders -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @if($user->isTeknik() || $user->isDirektur())
            <!-- Card 1: Pendaftaran Pelanggan Baru -->
            <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-sky-500/40 transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded">Drafter</span>
                </div>
                <h3 class="text-base font-semibold text-white">Draft Registrasi Pelanggan</h3>
                <p class="text-xs text-slate-400 mt-1">Input data calon pelanggan baru, lokasi ODP/koordinat, dan paket internet.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-sky-400 font-medium">
                    <span>Menunggu Desain Referensi</span>
                    <span>&rarr;</span>
                </div>
            </div>

            <!-- Card 2: Calon Pelanggan Terdaftar -->
            <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-sky-500/40 transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded">Pipeline</span>
                </div>
                <h3 class="text-base font-semibold text-white">Status Pengajuan</h3>
                <p class="text-xs text-slate-400 mt-1">Pantau status pendaftaran yang telah diajukan ke tim NOC untuk aktivasi.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-sky-400 font-medium">
                    <span>Menunggu Desain Referensi</span>
                    <span>&rarr;</span>
                </div>
            </div>

            <!-- Card 3: Riwayat Registrasi -->
            <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-sky-500/40 transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded">Log</span>
                </div>
                <h3 class="text-base font-semibold text-white">Riwayat Input Drafter</h3>
                <p class="text-xs text-slate-400 mt-1">Laporan data pelanggan yang berhasil didaftarkan oleh akun Anda.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-sky-400 font-medium">
                    <span>Menunggu Desain Referensi</span>
                    <span>&rarr;</span>
                </div>
            </div>
        @endif

        @if($user->isNoc() || $user->isDirektur())
            <!-- Card 1: Router & Perangkat Jaringan -->
            <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-indigo-500/40 transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a1.5 1.5 0 0 1 1.2-.6h10.1a1.5 1.5 0 0 1 1.2.6l2.1 3.45a4.5 4.5 0 0 1 .9 2.7" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded">Core Network</span>
                </div>
                <h3 class="text-base font-semibold text-white">Manajemen Router & OLT</h3>
                <p class="text-xs text-slate-400 mt-1">Monitoring status IP, ping router, traffic load, dan koneksi API RouterOS.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-indigo-400 font-medium">
                    <span>Menunggu Desain Referensi</span>
                    <span>&rarr;</span>
                </div>
            </div>

            <!-- Card 2: Antrean Aktivasi -->
            <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-indigo-500/40 transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded">Eksekusi</span>
                </div>
                <h3 class="text-base font-semibold text-white">Antrean Aktivasi Pelanggan</h3>
                <p class="text-xs text-slate-400 mt-1">Eksekusi pembuatan PPPoE Secret, assign IP statis, dan binding profile.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-indigo-400 font-medium">
                    <span>Menunggu Desain Referensi</span>
                    <span>&rarr;</span>
                </div>
            </div>

            <!-- Card 3: Suspend & Terminasi -->
            <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-amber-500/40 transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded">Suspend / Isolir</span>
                </div>
                <h3 class="text-base font-semibold text-white">Eksekusi Suspend & Terminasi</h3>
                <p class="text-xs text-slate-400 mt-1">Daftar permintaan isolir dari Finance untuk pelanggan yang jatuh tempo.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-amber-400 font-medium">
                    <span>Menunggu Desain Referensi</span>
                    <span>&rarr;</span>
                </div>
            </div>
        @endif

        @if($user->isFinance() || $user->isDirektur())
            <!-- Card 1: Billing Layanan Bulanan -->
            <a href="{{ route('finance.billing-layanan') }}"
               class="p-5 rounded-2xl bg-gradient-to-br from-blue-500/10 via-slate-900/80 to-slate-900 border border-blue-500/20 hover:border-blue-500/50 shadow-lg hover:shadow-blue-500/10 transition group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-blue-400 bg-blue-500/10 border border-blue-500/20 px-2 py-0.5 rounded-full">Bulanan</span>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-blue-300 transition">Billing Layanan Bulanan</h3>
                <p class="text-xs text-slate-400 mt-1">Generate tagihan bulanan, monitoring status pembayaran, dan penyesuaian invoice.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-blue-400 font-semibold">
                    <span>Buka Billing Layanan</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 2: Billing Registrasi Baru -->
            <a href="{{ route('finance.billing-registrasi') }}"
               class="p-5 rounded-2xl bg-gradient-to-br from-amber-500/10 via-slate-900/80 to-slate-900 border border-amber-500/20 hover:border-amber-500/50 shadow-lg hover:shadow-amber-500/10 transition group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Pasang Baru</span>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-amber-300 transition">Billing Registrasi Baru</h3>
                <p class="text-xs text-slate-400 mt-1">Kelola tagihan biaya pasang baru, penerbitan link bayar, dan verifikasi pelunasan.</p>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-amber-400 font-semibold">
                    <span>Buka Billing Registrasi</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </div>
            </a>

            <!-- Card 3: Realisasi Kas Masuk -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-500/10 via-slate-900/80 to-slate-900 border border-emerald-500/20 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full">Kas Masuk</span>
                </div>
                <h3 class="text-base font-bold text-white">Realisasi Pendapatan Bulan Ini</h3>
                <div class="mt-2">
                    <div class="text-xl font-black text-emerald-400">
                        Rp {{ number_format($financeStats['paidAmount'] ?? 0, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ number_format($financeStats['paidCount'] ?? 0) }} invoice telah terverifikasi lunas.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Ready for Reference Image / Next Feature Placeholder -->
    <div class="rounded-2xl border-2 border-dashed border-slate-800 bg-slate-900/30 p-8 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-200">Area Konten Siap untuk Tampilan Referensi</h3>
        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
            Sistem autentikasi telah disesuaikan langsung dengan tabel <code>tb_pengguna</code> & <code>tb_m_level_pengguna</code>. Silakan kirimkan gambar/desain referensi untuk fitur yang ingin dibuat selanjutnya!
        </p>
    </div>
</div>
@endsection
