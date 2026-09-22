@extends('layouts.app')

@section('title', 'Tiket Gangguan & Ubah Password - IMS Router')
@section('page_title', 'Tiket Gangguan & Pengaduan')

@section('content')
<div class="space-y-5"
     x-data="{
         items: {{ Js::from($tikets->items()) }},
         detailModalOpen: false,
         scheduleModalOpen: false,
         resolveModalOpen: false,
         cancelModalOpen: false,
         createModalOpen: false,
         
         selectedTiket: null,
         modalId: '',
         modalKodeTiket: '',
         modalNomorInternet: '',
         modalNamaPelanggan: '',
         modalKeluhan: '',
         modalKatTiket: '',
         modalStatus: '',
         modalDateSchedule: '{{ date('Y-m-d') }}',
         modalTimeSchedule: '09:00 - 12:00 WIB',
         modalTeamTeknisi: '',
         modalSolusi: '',
         modalNoteCancel: '',

         // Create Modal State
         createNomorInternet: '',
         createKatTiket: '{{ request('kategori') === 'ubah_password' ? '12' : (request('kategori') === 'relokasi' ? '13' : '11') }}',
         createPerubahan: 'Password Lama :\n[ketikdisini]',
         createJenisRelokasi: 'Eksternal',
         createAlamatBaru: '',
         createPicBaru: '',
         createCatatanRelokasi: '',
         createCustomerData: null,
         createLoading: false,
         createError: '',

         openCreateModal(defaultKat = null) {
             this.createNomorInternet = '';
             this.createKatTiket = defaultKat || '{{ request('kategori') === 'ubah_password' ? '12' : (request('kategori') === 'relokasi' ? '13' : '11') }}';
             this.createPerubahan = 'Password Lama :\n[ketikdisini]';
             this.createJenisRelokasi = 'Eksternal';
             this.createAlamatBaru = '';
             this.createPicBaru = '';
             this.createCatatanRelokasi = '';
             this.createCustomerData = null;
             this.createError = '';
             this.createLoading = false;
             this.createModalOpen = true;
         },

         async checkCustomer() {
             const noInt = (this.createNomorInternet || '').trim();
             if (!noInt) {
                 this.createError = 'Silakan masukkan nomor internet.';
                 return;
             }
             this.createLoading = true;
             this.createError = '';
             try {
                 const res = await fetch(`{{ route('teknik.tiket.gangguan.check-customer') }}?nomor_internet=` + encodeURIComponent(noInt));
                 const json = await res.json();
                 if (json.success && json.data) {
                     this.createCustomerData = json.data;
                     if (json.data.pass_pppoe) {
                         this.createPerubahan = 'Password Lama :\n' + json.data.pass_pppoe;
                     }
                 } else {
                     this.createCustomerData = null;
                     this.createError = json.message || 'Nomor internet tidak ditemukan.';
                 }
             } catch (e) {
                 this.createCustomerData = null;
                 this.createError = 'Gagal menghubungi server untuk cek data.';
             } finally {
                 this.createLoading = false;
             }
         },

         openDetailModal(idx) {
             const item = this.items[idx];
             if (!item) return;
             this.selectedTiket = item;
             this.modalId = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id;
             this.modalKodeTiket = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalKeluhan = item.keluhan || '-';
             this.modalKatTiket = (item.kat_tiket == '12') ? 'Ubah Password' : ((item.kat_tiket == '13') ? 'Relokasi Layanan' : 'Gangguan Layanan');
             this.modalStatus = item.status || '11';
             this.modalSolusi = item.solusi || item.penanganan || '';
             this.modalTeamTeknisi = item.team_teknisi || '';
             this.detailModalOpen = true;
         },

         openScheduleModal(idx) {
             const item = this.items[idx];
             if (!item) return;
             this.modalId = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id;
             this.modalKodeTiket = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalKeluhan = item.keluhan || '';
             this.modalDateSchedule = item.date_schedule ? item.date_schedule.substring(0, 10) : '{{ date('Y-m-d') }}';
             this.modalTimeSchedule = item.time_schedule || '09:00 - 12:00 WIB';
             this.modalTeamTeknisi = item.team_teknisi || '';
             this.scheduleModalOpen = true;
         },

         openResolveModal(idx) {
             const item = this.items[idx];
             if (!item) return;
             this.modalId = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id;
             this.modalKodeTiket = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalSolusi = item.solusi || item.penanganan || '';
             this.resolveModalOpen = true;
         },

         openCancelModal(idx) {
             const item = this.items[idx];
             if (!item) return;
             this.modalId = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id;
             this.modalKodeTiket = item.tiket || item.kode_trx_tiket || item.id_tiket || item.id || '-';
             this.modalNomorInternet = item.nomor_internet || '-';
             this.modalNamaPelanggan = item.nama_pelanggan || item.batch_nama || 'Pelanggan';
             this.modalNoteCancel = '';
             this.cancelModalOpen = true;
         },

         playNotificationSound() {
             try {
                 const AudioContext = window.AudioContext || window.webkitAudioContext;
                 if (!AudioContext) return;
                 const ctx = new AudioContext();
                 
                 const playTone = (freq, startTime, duration, gainValue = 0.25) => {
                     const osc = ctx.createOscillator();
                     const gain = ctx.createGain();
                     
                     osc.type = 'sine';
                     osc.frequency.setValueAtTime(freq, startTime);
                     
                     gain.gain.setValueAtTime(0.001, startTime);
                     gain.gain.exponentialRampToValueAtTime(gainValue, startTime + 0.03);
                     gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);
                     
                     osc.connect(gain);
                     gain.connect(ctx.destination);
                     
                     osc.start(startTime);
                     osc.stop(startTime + duration);
                 };

                 const now = ctx.currentTime;
                 // Harmonic 4-tone pleasant bell chime
                 playTone(523.25, now, 0.35, 0.22);         // C5
                 playTone(659.25, now + 0.11, 0.35, 0.22);  // E5
                 playTone(783.99, now + 0.22, 0.45, 0.25);  // G5
                 playTone(1046.50, now + 0.33, 0.8, 0.3);   // C6
             } catch (e) {
                 console.warn('Audio notification error:', e);
             }
         }
     }"
     x-init="@if(session('tiket_created')) $nextTick(() => playNotificationSound()); @endif">

    <!-- Breadcrumbs & Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
            <span>IMS</span>
            <span>&gt;</span>
            @if(request('kategori') === 'ubah_password')
                <span class="text-blue-600 dark:text-blue-400 font-semibold">Ganti Password</span>
            @elseif(request('kategori') === 'relokasi')
                <a href="{{ route('teknik.tiket') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Tiket</a>
                <span>&gt;</span>
                <span class="text-purple-600 dark:text-purple-400 font-semibold">Relokasi Layanan</span>
            @else
                <a href="{{ route('teknik.tiket') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Tiket</a>
                <span>&gt;</span>
                <span class="text-blue-600 dark:text-blue-400 font-semibold">
                    @if(request('kategori') === 'gangguan')
                        Gangguan Layanan
                    @else
                        Tiket Gangguan & Relokasi
                    @endif
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Sound Notification Test Button -->
            <button type="button"
                    @click="playNotificationSound()"
                    title="Tes Notifikasi Suara Tiket"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400 border border-slate-300 dark:border-slate-700 text-xs font-semibold transition shadow-sm cursor-pointer">
                <svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <span>Tes Suara</span>
            </button>

            <span class="text-slate-500 dark:text-slate-400">Role:</span>
            <span class="px-2.5 py-0.5 rounded-lg font-semibold {{ auth()->user()?->role_badge_classes ?? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20' }}">
                {{ auth()->user()?->nama_level ?? auth()->user()?->role?->label() ?? 'Staff' }}
            </span>

            <button type="button"
                    @click="openCreateModal()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/25 transition cursor-pointer">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Buat Tiket</span>
            </button>
        </div>
    </div>

    @if(request('kategori') === 'ubah_password')
        <div class="pt-1">
            <h2 class="text-base font-extrabold text-slate-900 dark:text-white">
                Tiket Permintaan Ganti Password
            </h2>
        </div>
    @elseif(request('kategori') === 'relokasi')
        <div class="pt-1">
            <h2 class="text-base font-extrabold text-purple-600 dark:text-purple-400 flex items-center gap-2">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <span>Tiket Permintaan Relokasi Layanan</span>
            </h2>
        </div>
    @endif

    <!-- Alert Flash Notifications -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold flex items-center gap-3 backdrop-blur-md">
        <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-center gap-3 backdrop-blur-md">
        <svg class="w-5 h-5 flex-shrink-0 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR                                                   -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('teknik.tiket.gangguan') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Hidden Kategori if fixed -->
            @if(request('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
            @else
                <!-- 1. Dropdown Kategori / Layanan -->
                <div class="lg:col-span-3">
                    <select name="kategori" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
                        <option value="">SEMUA KATEGORI TIKET</option>
                        <option value="gangguan" {{ request('kategori') === 'gangguan' ? 'selected' : '' }}>GANGGUAN LAYANAN</option>
                        <option value="ubah_password" {{ request('kategori') === 'ubah_password' ? 'selected' : '' }}>UBAH PASSWORD</option>
                        <option value="relokasi" {{ request('kategori') === 'relokasi' || request('kategori') === '13' ? 'selected' : '' }}>RELOKASI LAYANAN</option>
                        <option value="14" {{ request('kategori') === '14' ? 'selected' : '' }}>LAIN-LAIN</option>
                    </select>
                </div>
            @endif

            <!-- 2. Input Nama / Nomor Layanan / ID Tiket -->
            <div class="{{ request('kategori') ? 'lg:col-span-4' : 'lg:col-span-3' }}">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Cari nama / nomor internet..." 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="{{ request('kategori') ? 'lg:col-span-3' : 'lg:col-span-2' }}">
                <input type="text" 
                       name="wilayah" 
                       value="{{ request('wilayah') }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 uppercase font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="{{ request('kategori') ? 'lg:col-span-2' : 'lg:col-span-2' }}">
                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ request('status') === '11' ? 'selected' : '' }}>ANTRIAN / Request</option>
                    <option value="12" {{ request('status') === '12' ? 'selected' : '' }}>KONFIRMASI / On Schedule</option>
                    <option value="13" {{ request('status') === '13' ? 'selected' : '' }}>SELESAI / Success</option>
                    <option value="14" {{ request('status') === '14' ? 'selected' : '' }}>BATAL / Canceled</option>
                </select>
            </div>

            <!-- 5. Action Buttons (Find, Reset & Export) -->
            <div class="lg:col-span-3 flex items-center gap-2">
                <button type="submit" 
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold shadow-md shadow-cyan-600/20 transition cursor-pointer"
                        title="Cari Tiket">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <span>Find</span>
                </button>

                <a href="{{ route('teknik.tiket.gangguan', request('kategori') ? ['kategori' => request('kategori')] : []) }}" 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer"
                   title="Reset Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>

                <a href="{{ route('teknik.tiket.gangguan.export', request()->query()) }}" 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer"
                   title="Export CSV">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export</span>
                </a>
            </div>

        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 2. STATUS PILL 4 KPI BANNERS GRID                                   -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- Card 1: (KD11) Request / Antrian -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '11'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '11' ? 'ring-2 ring-slate-900 dark:ring-white scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD11) Antrian : {{ $count11 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 2: (KD12) On Schedule / Konfirmasi -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '12'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 text-slate-900 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '12' ? 'ring-2 ring-slate-900 dark:ring-white scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD12) Konfirmasi : {{ $count12 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 3: (KD13) Success / Selesai -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '13'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '13' ? 'ring-2 ring-slate-900 dark:ring-white scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD13) Selesai : {{ $count13 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Card 4: (KD14) Canceled / Batal -->
        <a href="{{ route('teknik.tiket.gangguan', array_merge(request()->except('page'), ['status' => '14'])) }}" 
           class="p-3.5 rounded-xl bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white font-bold text-xs shadow-md shadow-slate-600/20 transition flex items-center justify-between cursor-pointer group {{ request('status') === '14' ? 'ring-2 ring-slate-900 dark:ring-white scale-[1.02]' : '' }}">
            <span class="tracking-wide text-[11px]">(KD14) Dibatalkan : {{ $count14 ?? 0 }} Tiket</span>
            <svg class="w-4 h-4 opacity-80 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

    </div>

    <!-- =================================================================== -->
    <!-- 3. TABLE OF TIKET                                                   -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950/60">
            <div>
                Show <span class="font-bold text-slate-900 dark:text-white">10</span> entries
            </div>
            <div class="font-mono">
                Total: <strong class="text-slate-900 dark:text-white">{{ $tikets->total() }}</strong> Tiket
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        @if(request('kategori') === 'ubah_password')
                            <th class="py-3.5 px-4 min-w-[150px]">Tiket</th>
                            <th class="py-3.5 px-4 min-w-[280px]">Pelanggan</th>
                            <th class="py-3.5 px-4 min-w-[260px]">Info</th>
                            <th class="py-3.5 px-4 min-w-[240px]">Password</th>
                            <th class="py-3.5 px-4 min-w-[180px]">Status</th>
                        @else
                            <th class="py-3.5 px-4 min-w-[180px]">Customer</th>
                            <th class="py-3.5 px-4 min-w-[260px]">Address</th>
                            <th class="py-3.5 px-4 min-w-[140px]">Kategori</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Keluhan / Solusi</th>
                            <th class="py-3.5 px-4 min-w-[130px]">State</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/90 text-slate-700 dark:text-slate-300">
                    @forelse($tikets as $item)
                        @php
                            $namaPel = ($item->nama_pelanggan ?? null) ?: (($item->batch_nama ?? null) ?: 'Pelanggan');
                            $nomorInternet = $item->nomor_internet ?? '-';
                            $alamat = ($item->alamat_pasang ?? null) ?: (($item->alamat_p ?? null) ?: '-');
                            $katText = match((string)($item->kat_tiket ?? '')) { '12' => 'Ubah Password', '13' => 'Relokasi Layanan', default => 'Gangguan Layanan' };
                            $statusVal = (string) ($item->status ?? '11');
                            $kodeTiket = ($item->tiket ?? null) ?: (($item->kode_trx_tiket ?? null) ?: (($item->id_tiket ?? null) ?: (($item->id ?? null) ?: '-')));
                            $userPppoe = ($item->user_pppoe ?? null) ?: $nomorInternet;
                            $passPppoe = ($item->pass_pppoe ?? null) ?: (($item->password ?? null) ?: '-');
                            $mediaAkses = ($item->media_akses ?? null) ?: 'FTTH';
                            $popName = ($item->nama_pop ?? null) ?: 'MediaNet FTTH';

                            $keluhanClean = str_replace(["\r\n", "\\r\\n", "\r", "\\r", "\\n"], "\n", $item->keluhan ?? '');
                            $passLama = '-';
                            $passBaru = ($item->solusi ?? null) ?: (($item->penanganan ?? null) ?: 'tim customer care kami akan segera menghubungi anda');

                            if (stripos($keluhanClean, 'Password Lama :') !== false || stripos($keluhanClean, 'password Baru :') !== false) {
                                $parts = preg_split('/password\s*baru\s*:\s*/i', $keluhanClean);
                                if (isset($parts[0])) {
                                    $passLama = trim(preg_replace('/^password\s*lama\s*:\s*/i', '', trim($parts[0])));
                                }
                                if (isset($parts[1]) && !empty(trim($parts[1]))) {
                                    $passBaru = trim($parts[1]);
                                }
                            } else {
                                $passLama = $keluhanClean ?: ($item->password_lama ?? '-');
                            }

                            $dateCreateFormatted = !empty($item->date_create) ? date('d F Y H:i', strtotime($item->date_create)) . ' WIB' : '-';
                            $dateUpdateFormatted = !empty($item->date_update) ? date('d F Y H:i', strtotime($item->date_update)) . ' WIB' : $dateCreateFormatted;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            
                            @if(request('kategori') === 'ubah_password')
                                <!-- 1. Tiket -->
                                <td class="py-4 px-4 align-top">
                                    <div class="font-mono font-bold text-slate-900 dark:text-white text-xs">
                                        #{{ $kodeTiket }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-sans">
                                        {{ $dateCreateFormatted }}
                                    </div>
                                </td>

                                <!-- 2. Pelanggan -->
                                <td class="py-4 px-4 align-top">
                                    <div class="font-bold text-slate-900 dark:text-white uppercase text-xs">
                                        @if($nomorInternet && $nomorInternet !== '-')
                                            <a href="{{ route('teknik.pelanggan.profile', $nomorInternet) }}" 
                                               class="font-mono text-cyan-700 dark:text-cyan-400 hover:text-cyan-800 dark:hover:text-cyan-300 hover:underline mr-1 font-bold"
                                               title="Buka Profile Pelanggan">
                                                {{ $nomorInternet }}
                                            </a>
                                        @else
                                            <span class="font-mono text-slate-400 mr-1">{{ $nomorInternet }}</span>
                                        @endif
                                        <span>{{ $namaPel }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 uppercase leading-relaxed font-sans">
                                        {{ $alamat }}
                                    </div>
                                </td>

                                <!-- 3. Info -->
                                <td class="py-4 px-4 align-top text-[11px] space-y-1">
                                    <div class="text-slate-800 dark:text-slate-200">
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">User :</span> <strong class="font-mono text-cyan-700 dark:text-cyan-400">{{ $userPppoe }}</strong> 
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold ml-1.5">Pass :</span> <strong class="font-mono text-amber-700 dark:text-amber-400">{{ $passPppoe }}</strong>
                                    </div>
                                    <div class="text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">MediaAkses :</span> <strong>{{ $mediaAkses }}</strong>
                                    </div>
                                    <div class="text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">POP :</span> {{ $popName }}
                                    </div>
                                </td>

                                <!-- 4. Password -->
                                <td class="py-4 px-4 align-top text-[11px] space-y-1">
                                    <div>
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">Password Lama :</span>
                                        <div class="font-mono text-slate-900 dark:text-white font-semibold mt-0.5">{{ $passLama }}</div>
                                    </div>
                                    <div class="pt-1">
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">password Baru :</span>
                                        <div class="font-semibold text-slate-900 dark:text-white mt-0.5 leading-relaxed">{{ $passBaru }}</div>
                                    </div>
                                </td>

                                <!-- 5. Status -->
                                <td class="py-4 px-4 align-top">
                                    <div class="flex flex-col gap-1">
                                        <div class="font-extrabold text-xs uppercase {{ $statusVal === '11' ? 'text-amber-600 dark:text-amber-400' : ($statusVal === '12' ? 'text-blue-600 dark:text-blue-400' : ($statusVal === '13' || $statusVal === 'Selesai' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400')) }}">
                                            {{ $statusVal === '11' ? 'ANTRIAN' : ($statusVal === '12' ? 'KONFIRMASI PENANGANAN' : ($statusVal === '13' || $statusVal === 'Selesai' ? 'KONFIRMASI PENANGANAN' : 'DIBATALKAN')) }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                            {{ $dateUpdateFormatted }}
                                        </div>
                                        @if(!empty($item->user_update))
                                            <div class="text-[10px] text-slate-600 dark:text-slate-400 uppercase font-bold">
                                                {{ $item->user_update }}
                                            </div>
                                        @endif

                                        <div class="pt-1.5 flex items-center gap-1.5">
                                            <button type="button"
                                                    @click="openDetailModal({{ $loop->index }})"
                                                    class="p-1.5 rounded-lg bg-cyan-50 hover:bg-cyan-600 dark:bg-cyan-500/10 dark:hover:bg-cyan-600 text-cyan-700 dark:text-cyan-400 hover:text-white transition cursor-pointer border border-cyan-200 dark:border-cyan-500/20"
                                                    title="Lihat Detail Tiket">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </button>

                                            @if(auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin']))
                                                @if($statusVal === '11')
                                                    <!-- KD11: Jadwalkan Penanganan (KD12) & Batalkan (KD14) -->
                                                    <button type="button"
                                                            @click="openScheduleModal({{ $loop->index }})"
                                                            class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-500 dark:bg-amber-500/10 dark:hover:bg-amber-500 text-amber-700 dark:text-amber-400 hover:text-white transition cursor-pointer border border-amber-200 dark:border-amber-500/20"
                                                            title="Jadwalkan Penanganan (KD12)">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                                        </svg>
                                                    </button>
                                                    <button type="button"
                                                            @click="openCancelModal({{ $loop->index }})"
                                                            class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 dark:bg-rose-500/10 dark:hover:bg-rose-600 text-rose-700 dark:text-rose-400 hover:text-white transition cursor-pointer border border-rose-200 dark:border-rose-500/20"
                                                            title="Batalkan (KD14)">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                @elseif($statusVal === '12')
                                                    <!-- KD12: ACC / Selesaikan (KD13) & Batalkan (KD14) -->
                                                    <button type="button"
                                                            @click="openResolveModal({{ $loop->index }})"
                                                            class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 dark:bg-emerald-500/10 dark:hover:bg-emerald-600 text-emerald-700 dark:text-emerald-400 hover:text-white transition cursor-pointer border border-emerald-200 dark:border-emerald-500/20"
                                                            title="ACC / Selesaikan (KD13)">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                    <button type="button"
                                                            @click="openCancelModal({{ $loop->index }})"
                                                            class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 dark:bg-rose-500/10 dark:hover:bg-rose-600 text-rose-700 dark:text-rose-400 hover:text-white transition cursor-pointer border border-rose-200 dark:border-rose-500/20"
                                                            title="Batalkan (KD14)">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>

                            @else
                                <!-- Standard Layout: Customer & No Layanan -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <span>{{ $namaPel }}</span>
                                    </div>
                                    <div class="text-[11px] font-mono mt-0.5">
                                        @if($nomorInternet && $nomorInternet !== '-')
                                            <a href="{{ route('teknik.pelanggan.profile', $nomorInternet) }}" 
                                               class="text-cyan-700 dark:text-cyan-400 hover:text-cyan-800 dark:hover:text-cyan-300 hover:underline font-bold"
                                               title="Buka Profile Pelanggan">
                                                {{ $nomorInternet }}
                                            </a>
                                        @else
                                            <span class="text-slate-400 font-medium">{{ $nomorInternet }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($kodeTiket) && $kodeTiket !== '-')
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-semibold">
                                            #{{ $kodeTiket }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Address & Wilayah -->
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 font-medium">
                                    <div class="line-clamp-2 text-xs">
                                        {{ $alamat }}
                                    </div>
                                    @if(!empty($item->nama_pop))
                                        <div class="mt-1 text-[10px] inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-semibold">
                                            <span>POP: {{ $item->nama_pop }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Kategori Tiket -->
                                <td class="py-3.5 px-4">
                                    @if(($item->kat_tiket ?? null) == '12')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                            </svg>
                                            <span>Ubah Password</span>
                                        </span>
                                    @elseif(($item->kat_tiket ?? null) == '13')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                            <span>Relokasi Layanan</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-cyan-50 dark:bg-cyan-500/10 text-cyan-800 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-500/20">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                            </svg>
                                            <span>Gangguan Layanan</span>
                                        </span>
                                    @endif
                                    @if(!empty($item->prioritas) && $item->prioritas !== 'Normal')
                                        <div class="mt-1">
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 font-extrabold">
                                                Prioritas: {{ $item->prioritas }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Keluhan / Catatan -->
                                <td class="py-3.5 px-4">
                                    <div class="text-xs text-slate-800 dark:text-slate-200 font-medium line-clamp-2">
                                        {{ $item->keluhan ?? '-' }}
                                    </div>
                                    @if(!empty($item->solusi) || !empty($item->penanganan))
                                        <div class="mt-1 text-[11px] text-emerald-700 dark:text-emerald-400 font-bold line-clamp-1">
                                            Solusi: {{ $item->solusi ?? $item->penanganan }}
                                        </div>
                                    @endif
                                    @if(!empty($item->team_teknisi))
                                        <div class="mt-0.5 text-[10px] text-slate-600 dark:text-slate-400 font-semibold">
                                            Teknisi: {{ $item->team_teknisi }}
                                        </div>
                                    @endif
                                    @if(!empty($item->date_schedule))
                                        <div class="mt-0.5 text-[10px] text-amber-700 dark:text-amber-400 font-bold">
                                            Jadwal: {{ date('d M Y', strtotime($item->date_schedule)) }} ({{ $item->time_schedule ?? 'WIB' }})
                                        </div>
                                    @endif
                                </td>

                                <!-- State / Status -->
                                <td class="py-3.5 px-4">
                                    @if($statusVal === '11')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400 animate-ping"></span>
                                            <span>(KD11) Request</span>
                                        </span>
                                    @elseif($statusVal === '12')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>
                                            <span>(KD12) On Schedule</span>
                                        </span>
                                    @elseif($statusVal === '13' || $statusVal === 'Selesai')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                                            <span>(KD13) Success</span>
                                        </span>
                                    @elseif($statusVal === '14' || $statusVal === 'Cancel' || $statusVal === 'Dibatalkan')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 dark:bg-slate-400"></span>
                                            <span>(KD14) Canceled</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                            {{ $statusVal }}
                                        </span>
                                    @endif
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                                        {{ !empty($item->date_create) ? date('d M Y H:i', strtotime($item->date_create)) : '-' }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- View Detail Button -->
                                        <button type="button"
                                                @click="openDetailModal({{ $loop->index }})"
                                                class="p-1.5 rounded-lg bg-cyan-50 hover:bg-cyan-600 dark:bg-cyan-500/10 dark:hover:bg-cyan-600 text-cyan-700 dark:text-cyan-400 hover:text-white transition cursor-pointer border border-cyan-200 dark:border-cyan-500/20"
                                                title="Lihat Detail Tiket">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>

                                        @if(auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin']))
                                            @if($statusVal === '11')
                                                <!-- KD11: Jadwalkan Penanganan (KD12) & Batalkan (KD14) -->
                                                <button type="button"
                                                        @click="openScheduleModal({{ $loop->index }})"
                                                        class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-500 dark:bg-amber-500/10 dark:hover:bg-amber-500 text-amber-700 dark:text-amber-400 hover:text-white transition cursor-pointer border border-amber-200 dark:border-amber-500/20"
                                                        title="Jadwalkan Penanganan (KD12)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        @click="openCancelModal({{ $loop->index }})"
                                                        class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 dark:bg-rose-500/10 dark:hover:bg-rose-600 text-rose-700 dark:text-rose-400 hover:text-white transition cursor-pointer border border-rose-200 dark:border-rose-500/20"
                                                        title="Batalkan Tiket (KD14)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>
                                            @elseif($statusVal === '12')
                                                <!-- KD12: ACC / Selesaikan Tiket (KD13) & Batalkan (KD14) -->
                                                <button type="button"
                                                        @click="openResolveModal({{ $loop->index }})"
                                                        class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 dark:bg-emerald-500/10 dark:hover:bg-emerald-600 text-emerald-700 dark:text-emerald-400 hover:text-white transition cursor-pointer border border-emerald-200 dark:border-emerald-500/20"
                                                        title="ACC / Selesaikan Tiket (KD13)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        @click="openCancelModal({{ $loop->index }})"
                                                        class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 dark:bg-rose-500/10 dark:hover:bg-rose-600 text-rose-700 dark:text-rose-400 hover:text-white transition cursor-pointer border border-rose-200 dark:border-rose-500/20"
                                                        title="Batalkan Tiket (KD14)">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                    </svg>
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">No data available in table</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Tidak ada tiket gangguan yang sesuai dengan filter pencarian.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950/40">
            <div>
                Showing {{ $tikets->firstItem() ?? 0 }} to {{ $tikets->lastItem() ?? 0 }} of {{ $tikets->total() }} entries
            </div>
            <div>
                {{ $tikets->links() }}
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 1: DETAIL TIKET                                               -->
    <!-- =================================================================== -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-slate-900 dark:text-white space-y-4"
             @click.away="detailModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <span>Detail Tiket Pengaduan</span>
                </h3>
                <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                    <span class="text-slate-500 dark:text-slate-400 font-semibold">ID / Kode Tiket:</span>
                    <span class="col-span-2 font-mono font-extrabold text-cyan-600 dark:text-cyan-400" x-text="modalKodeTiket"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                    <span class="text-slate-500 dark:text-slate-400 font-semibold">Nomor Internet:</span>
                    <span class="col-span-2 font-mono font-bold text-slate-900 dark:text-white" x-text="modalNomorInternet"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                    <span class="text-slate-500 dark:text-slate-400 font-semibold">Nama Pelanggan:</span>
                    <span class="col-span-2 font-bold text-slate-900 dark:text-white uppercase" x-text="modalNamaPelanggan"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                    <span class="text-slate-500 dark:text-slate-400 font-semibold">Kategori Tiket:</span>
                    <span class="col-span-2 font-bold text-indigo-600 dark:text-indigo-400" x-text="modalKatTiket"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                    <span class="text-slate-500 dark:text-slate-400 font-semibold">Keluhan / Pesan:</span>
                    <span class="col-span-2 text-slate-800 dark:text-slate-200 font-medium" x-text="modalKeluhan"></span>
                </div>
                <template x-if="modalSolusi">
                    <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold">Solusi / Penanganan:</span>
                        <span class="col-span-2 text-emerald-600 dark:text-emerald-400 font-bold" x-text="modalSolusi"></span>
                    </div>
                </template>
                <template x-if="modalTeamTeknisi">
                    <div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold">Tim Teknisi:</span>
                        <span class="col-span-2 text-amber-600 dark:text-amber-400 font-bold" x-text="modalTeamTeknisi"></span>
                    </div>
                </template>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 text-xs font-bold transition cursor-pointer">
                    Tutup
                </button>

                @if(auth()->user()?->hasRole(['teknik', 'noc', 'direktur', 'admin']))
                    <div class="flex items-center gap-2">
                        <!-- If KD11: Show Schedule button -->
                        <template x-if="modalStatus === '11'">
                            <button type="button"
                                    @click="detailModalOpen = false; scheduleModalOpen = true;"
                                    class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-md shadow-amber-500/20 cursor-pointer">
                                Jadwalkan Penanganan (KD12)
                            </button>
                        </template>

                        <!-- If KD12: Show ACC/Selesaikan button -->
                        <template x-if="modalStatus === '12'">
                            <button type="button"
                                    @click="detailModalOpen = false; resolveModalOpen = true;"
                                    class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 cursor-pointer">
                                ACC / Selesaikan (KD13)
                            </button>
                        </template>

                        <!-- If KD11 or KD12: Show Batalkan button -->
                        <template x-if="modalStatus === '11' || modalStatus === '12'">
                            <button type="button"
                                    @click="detailModalOpen = false; cancelModalOpen = true;"
                                    class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-600 dark:bg-rose-500/10 dark:hover:bg-rose-600 text-rose-700 dark:text-rose-400 hover:text-white border border-rose-200 dark:border-rose-500/20 text-xs font-bold transition cursor-pointer">
                                Batalkan (KD14)
                            </button>
                        </template>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 2: SCHEDULE PENANGANAN (KD12)                                 -->
    <!-- =================================================================== -->
    <div x-show="scheduleModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-slate-900 dark:text-white space-y-4"
             @click.away="scheduleModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span>Jadwalkan Penanganan Tiket (KD12)</span>
                </h3>
                <button type="button" @click="scheduleModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form :action="'{{ url('/teknik/tiket/gangguan') }}/' + modalId + '/schedule'" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="nomor_internet" :value="modalNomorInternet">
                <input type="hidden" name="kode_trx_tiket" :value="modalKodeTiket">
                <div class="p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Customer:</span>
                        <strong class="text-slate-900 dark:text-white uppercase" x-text="modalNamaPelanggan"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Nomor Internet:</span>
                        <strong class="font-mono text-cyan-600 dark:text-cyan-400 font-bold" x-text="modalNomorInternet"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Tanggal Jadwal Penanganan:<span class="text-rose-500 font-bold">*</span></label>
                    <input type="date" name="date_schedule" x-model="modalDateSchedule" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm" required>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Waktu Penanganan:<span class="text-rose-500 font-bold">*</span></label>
                    <input type="text" name="time_schedule" x-model="modalTimeSchedule" placeholder="Contoh: 09:00 - 12:00 WIB" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm" required>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Tim Teknisi / Personil:</label>
                    <input type="text" name="team_teknisi" x-model="modalTeamTeknisi" placeholder="Nama Teknisi / Tim yang ditugaskan" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Catatan / Detail Keluhan Tambahan:</label>
                    <textarea name="keluhan" x-model="modalKeluhan" rows="2" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="scheduleModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 font-bold cursor-pointer transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold shadow-lg shadow-amber-500/20 cursor-pointer transition">
                        Simpan Jadwal (KD12)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 3: SELESAIKAN TIKET (KD13)                                    -->
    <!-- =================================================================== -->
    <div x-show="resolveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-slate-900 dark:text-white space-y-4"
             @click.away="resolveModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                    <span>Selesaikan Tiket Gangguan (KD13)</span>
                </h3>
                <button type="button" @click="resolveModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form :action="'{{ url('/teknik/tiket/gangguan') }}/' + modalId + '/resolve'" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="nomor_internet" :value="modalNomorInternet">
                <input type="hidden" name="kode_trx_tiket" :value="modalKodeTiket">
                <div class="p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Customer:</span>
                        <strong class="text-slate-900 dark:text-white uppercase" x-text="modalNamaPelanggan"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Nomor Internet:</span>
                        <strong class="font-mono text-cyan-600 dark:text-cyan-400 font-bold" x-text="modalNomorInternet"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Catatan Solusi / Tindakan Penyelesaian:<span class="text-rose-500 font-bold">*</span></label>
                    <textarea name="solusi" x-model="modalSolusi" rows="3" placeholder="Jelaskan tindakan teknis yang telah dilakukan..." class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm" required></textarea>
                </div>

                <!-- Technical Report Fields -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2.5">
                    <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.67 2.67 0 0 0 21 17.25l-5.87-5.87m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span>Laporan Teknis Lapangan (Technical Report)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-[10px] font-bold mb-1">ODP Baru / Port:</label>
                            <input type="text" name="odp_baru" placeholder="Contoh: ODP-BBR-01 / Port 4" class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-emerald-500 font-semibold shadow-sm">
                        </div>
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-[10px] font-bold mb-1">Redaman Rx (dBm):</label>
                            <input type="text" name="redaman" placeholder="Contoh: -18.50" class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-emerald-500 font-semibold shadow-sm">
                        </div>
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-[10px] font-bold mb-1">Panjang Kabel Dropcore (Meter):</label>
                            <input type="number" name="panjang_kabel" placeholder="Contoh: 75" class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-emerald-500 font-semibold shadow-sm">
                        </div>
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-[10px] font-bold mb-1">SN / MAC ONT:</label>
                            <input type="text" name="sn_ont" placeholder="Contoh: ZTEGC1234567" class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-emerald-500 font-mono font-bold shadow-sm">
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="resolveModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 font-bold cursor-pointer transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-lg shadow-emerald-600/20 cursor-pointer transition">
                        Selesaikan Tiket (KD13)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 4: BATALKAN TIKET (KD14)                                      -->
    <!-- =================================================================== -->
    <div x-show="cancelModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl p-6 text-slate-900 dark:text-white space-y-4"
             @click.away="cancelModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-600"></span>
                    <span>Batalkan Tiket (KD14)</span>
                </h3>
                <button type="button" @click="cancelModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form :action="'{{ url('/teknik/tiket/gangguan') }}/' + modalId + '/cancel'" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="nomor_internet" :value="modalNomorInternet">
                <input type="hidden" name="kode_trx_tiket" :value="modalKodeTiket">
                <div class="p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Customer:</span>
                        <strong class="text-slate-900 dark:text-white uppercase" x-text="modalNamaPelanggan"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">Nomor Internet:</span>
                        <strong class="font-mono text-cyan-600 dark:text-cyan-400 font-bold" x-text="modalNomorInternet"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Alasan Pembatalan:<span class="text-rose-500 font-bold">*</span></label>
                    <textarea name="note_cancel" x-model="modalNoteCancel" rows="3" placeholder="Tuliskan alasan pembatalan tiket..." class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-rose-500 shadow-sm" required></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="cancelModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 font-bold cursor-pointer transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-lg shadow-rose-600/20 cursor-pointer transition">
                        Batalkan Tiket (KD14)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODAL 5: BUAT TIKET GANTI PASSWORD / GANGGUAN                       -->
    <!-- =================================================================== -->
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl p-6 text-slate-900 dark:text-white space-y-5"
             @click.away="createModalOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                    @if(request('kategori') === 'ubah_password')
                        Tiket Ganti Password
                    @else
                        Buat Tiket Gangguan & Pengaduan
                    @endif
                </h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- Form Body -->
            <form action="{{ route('teknik.tiket.gangguan.store') }}" method="POST" class="space-y-5 text-xs">
                @csrf
                
                <!-- Pilihan Kategori Tiket -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-2">
                        Pilih Kategori Tiket<span class="text-rose-500 font-bold">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="p-2.5 rounded-xl border cursor-pointer transition flex flex-col items-center gap-1 text-center"
                               :class="createKatTiket === '11' ? 'bg-cyan-50 dark:bg-cyan-500/15 border-cyan-500 text-cyan-800 dark:text-cyan-300 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                            <input type="radio" name="kat_tiket" value="11" x-model="createKatTiket" class="hidden">
                            <span class="text-xs">🔧 Gangguan</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer transition flex flex-col items-center gap-1 text-center"
                               :class="createKatTiket === '12' ? 'bg-rose-50 dark:bg-rose-500/15 border-rose-500 text-rose-800 dark:text-rose-300 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                            <input type="radio" name="kat_tiket" value="12" x-model="createKatTiket" class="hidden">
                            <span class="text-xs">🔑 Ganti Password</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer transition flex flex-col items-center gap-1 text-center"
                               :class="createKatTiket === '13' ? 'bg-purple-50 dark:bg-purple-500/15 border-purple-500 text-purple-800 dark:text-purple-300 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                            <input type="radio" name="kat_tiket" value="13" x-model="createKatTiket" class="hidden">
                            <span class="text-xs">📍 Relokasi</span>
                        </label>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-4">
                    
                    <!-- 1. Nomor Internet + CEK Button -->
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">
                            Nomor Internet<span class="text-rose-500 font-bold">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <input type="text" 
                                       name="nomor_internet" 
                                       x-model="createNomorInternet" 
                                       @keydown.enter.prevent="checkCustomer()"
                                       placeholder="Cari nomor internet..." 
                                       class="w-full text-xs px-3.5 py-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 font-medium shadow-sm" 
                                       required>
                            </div>
                            <button type="button" 
                                    @click="checkCustomer()"
                                    :disabled="createLoading"
                                    class="px-5 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 active:scale-95 text-white font-bold text-xs tracking-wider shadow-md shadow-cyan-600/30 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                                <template x-if="createLoading">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <span>CEK</span>
                            </button>
                        </div>

                        <!-- Customer Info Alert / Chip -->
                        <template x-if="createCustomerData">
                            <div class="mt-2.5 p-3 rounded-lg bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 text-[11px] text-cyan-900 dark:text-cyan-200 space-y-1">
                                <div class="font-bold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-cyan-700 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span x-text="createCustomerData.nama_pelanggan"></span>
                                    <span class="text-slate-500 dark:text-slate-400 font-mono" x-text="'(' + createCustomerData.nomor_internet + ')'"></span>
                                </div>
                                <div class="text-slate-700 dark:text-slate-300 font-medium" x-text="'Alamat Asal: ' + createCustomerData.alamat"></div>
                                <div class="text-[10px] text-slate-600 dark:text-slate-400 font-semibold" x-text="'POP: ' + (createCustomerData.nama_pop || '-') + ' | Media: ' + (createCustomerData.media_akses || 'FTTH')"></div>
                            </div>
                        </template>

                        <!-- Error alert -->
                        <template x-if="createError">
                            <div class="mt-2 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                <span x-text="createError"></span>
                            </div>
                        </template>
                    </div>

                    <!-- 2A. Field Khusus Relokasi (Kat 13) -->
                    <template x-if="createKatTiket === '13'">
                        <div class="space-y-3.5 pt-1">
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">
                                    Jenis Relokasi<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="p-2 rounded-lg border text-center cursor-pointer text-xs transition"
                                           :class="createJenisRelokasi === 'Eksternal' ? 'bg-purple-50 dark:bg-purple-500/15 border-purple-500 text-purple-800 dark:text-purple-300 font-bold' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                                        <input type="radio" name="jenis_relokasi" value="Eksternal" x-model="createJenisRelokasi" class="hidden">
                                        <span>Pindah Alamat (Eksternal)</span>
                                    </label>
                                    <label class="p-2 rounded-lg border text-center cursor-pointer text-xs transition"
                                           :class="createJenisRelokasi === 'Internal' ? 'bg-purple-50 dark:bg-purple-500/15 border-purple-500 text-purple-800 dark:text-purple-300 font-bold' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400'">
                                        <input type="radio" name="jenis_relokasi" value="Internal" x-model="createJenisRelokasi" class="hidden">
                                        <span>Pindah Titik Ruangan (Internal)</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">
                                    Alamat Tujuan Baru<span class="text-rose-500 font-bold">*</span>
                                </label>
                                <textarea name="alamat_baru" 
                                          x-model="createAlamatBaru" 
                                          rows="2" 
                                          placeholder="Tuliskan alamat lengkap baru (Jalan, RT/RW, No. Rumah, Kelurahan, Kecamatan)" 
                                          class="w-full text-xs p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-sm"
                                          :required="createKatTiket === '13'"></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">
                                        Kontak / PIC di Lokasi Baru:
                                    </label>
                                    <input type="text" 
                                           name="pic_baru" 
                                           x-model="createPicBaru" 
                                           placeholder="Contoh: Bpk. Ahmad (08123456789)" 
                                           class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">
                                        Keterangan Relokasi:
                                    </label>
                                    <input type="text" 
                                           name="catatan_relokasi" 
                                           x-model="createCatatanRelokasi" 
                                           placeholder="Alasan pindah / catatan waktu" 
                                           class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-sm">
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- 2B. Field Umum / Ubah Password / Gangguan -->
                    <template x-if="createKatTiket !== '13'">
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1.5">
                                <span x-text="createKatTiket === '12' ? 'Perubahan Password' : 'Keluhan / Gangguan'"></span><span class="text-rose-500 font-bold">*</span>
                            </label>
                            <textarea name="perubahan" 
                                      x-model="createPerubahan" 
                                      rows="4" 
                                      class="w-full text-xs p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-mono font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 leading-relaxed shadow-sm" 
                                      :required="createKatTiket !== '13'"></textarea>
                        </div>
                    </template>

                </div>

                <!-- Modal Footer Buttons -->
                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <!-- Tutup Button -->
                    <button type="button" 
                            @click="createModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 font-bold text-xs transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span>Tutup</span>
                    </button>

                    <!-- Simpan Button -->
                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md shadow-cyan-600/30 transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                        <span>Simpan Tiket</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
