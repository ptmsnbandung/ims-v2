@extends('layouts.app')

@section('title', 'Edit OLT - ' . ($olt->name_olt ?? $olt->kode_olt) . ' - NOC IMS')
@section('page_title', 'Edit OLT')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto pb-12"
     x-data="{
        testingConnection: false,
        testResult: null,
        protocol: '{{ old('protocol', $olt->protocol ?? 'telnet') }}',
        port: {{ old('port', $olt->port ?? 23) }},
        ipAddress: '{{ old('ip_address', $olt->ip_address ?? '') }}',
        username: '{{ old('username', $olt->username ?? '') }}',
        password: '',
        enablePassword: '',
        showPassword: false,
        showEnablePassword: false,
        snmpPort: {{ old('snmp_port', $olt->snmp_port ?? 161) }},
        snmpVersion: '{{ old('snmp_version', $olt->snmp_version ?? 'v2c') }}',
        snmpCommunity: '{{ old('snmp_community', $olt->snmp_community ?? 'public') }}',

        setProtocol(proto) {
            this.protocol = proto;
            if (proto === 'ssh' && (this.port === 23 || !this.port)) {
                this.port = 22;
            } else if (proto === 'telnet' && (this.port === 22 || !this.port)) {
                this.port = 23;
            }
        },

        async testOltConnection() {
            if (!this.ipAddress) {
                alert('Silakan masukkan IP Address OLT terlebih dahulu.');
                return;
            }

            this.testingConnection = true;
            this.testResult = null;

            try {
                const res = await fetch('{{ route('noc.olt.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ip_address: this.ipAddress,
                        port: this.port,
                        protocol: this.protocol,
                        username: this.username,
                        password: this.password,
                        enable_password: this.enablePassword
                    })
                });

                if (!res.ok) {
                    const errorText = await res.text();
                    let parsedMsg = 'HTTP ' + res.status + ' (' + res.statusText + ')';
                    try {
                        const parsedJson = JSON.parse(errorText);
                        parsedMsg = parsedJson.message || parsedMsg;
                    } catch(e) {}
                    
                    this.testResult = {
                        success: false,
                        message: 'Gagal menguji koneksi: ' + parsedMsg
                    };
                    return;
                }

                const data = await res.json();
                this.testResult = data;
            } catch (err) {
                this.testResult = {
                    success: false,
                    message: 'Gagal melakukan request pengujian koneksi: ' + err.message
                };
            } finally {
                this.testingConnection = false;
            }
        }
     }">

    <!-- =================================================================== -->
    <!-- 1. TOP HEADER BANNER                                                -->
    <!-- =================================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">IMS</a>
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <a href="{{ route('noc.olt') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Master OLT</a>
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Edit OLT</span>
            </div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                <span>Edit OLT: {{ $olt->name_olt ?? $olt->kode_olt }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">{{ $olt->kode_olt }}</span>
            </h1>
        </div>

        <div>
            <a href="{{ route('noc.olt') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Main Form -->
    <form action="{{ route('noc.olt.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="id" value="{{ $olt->id ?? '' }}">
        <input type="hidden" name="kode_olt" value="{{ $olt->kode_olt }}">

        <!-- =============================================================== -->
        <!-- CARD 1: INFORMASI SPESIFIKASI & PERANGKAT OLT                   -->
        <!-- =============================================================== -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
            
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Spesifikasi & Identitas Perangkat OLT
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Konfigurasi dasar perangkat OLT, penamaan gateway, dan integrasi titik POP distribusi.
                    </p>
                </div>
            </div>

            <!-- Balanced 2-Column Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                
                <!-- Row 1: Nama OLT -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Nama OLT <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a3 3 0 0 1 2.4-1.35h7.7a3 3 0 0 1 2.4 1.35l2.1 3.15a4.5 4.5 0 0 1 .9 2.7m-13.5 0h13.5" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="name_olt" 
                               value="{{ old('name_olt', $olt->name_olt) }}"
                               required
                               placeholder="Contoh: OLT KAYU AGUNG" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                    @error('name_olt')
                        <span class="text-[11px] text-rose-500 font-semibold">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Row 1: Hostname -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Hostname
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="hostname" 
                               value="{{ old('hostname', $olt->hostname ?? $olt->kode_olt) }}"
                               placeholder="olt-kayuagung" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                </div>

                <!-- Row 2: IP Address -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        IP Address / Host <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="ip_address" 
                               x-model="ipAddress"
                               required
                               placeholder="103.161.206.211" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                    @error('ip_address')
                        <span class="text-[11px] text-rose-500 font-semibold">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Row 2: Vendor / Brand -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Vendor / Brand <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="brand" 
                               value="{{ old('brand', $olt->brand ?: 'ZTE') }}"
                               placeholder="ZTE / Huawei / Fiberhome" 
                               list="brandList"
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                        <datalist id="brandList">
                            <option value="ZTE">
                            <option value="Huawei">
                            <option value="Fiberhome">
                            <option value="VSOL">
                            <option value="BDCOM">
                            <option value="Hioso">
                        </datalist>
                    </div>
                </div>

                <!-- Row 3: Model Hardware -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Model Hardware
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="model" 
                               value="{{ old('model', $olt->model ?: 'C320') }}"
                               placeholder="C320 / C300 / MA5608T" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                </div>

                <!-- Row 3: POP Server / Lokasi -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        POP Server / Lokasi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </div>
                        <select name="kode_pop" 
                                class="w-full pl-10 pr-8 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                            <option value="">-- Pilih POP Server --</option>
                            @foreach($pops as $pop)
                                <option value="{{ $pop->kode_pop }}" {{ old('kode_pop', $olt->kode_pop) == $pop->kode_pop ? 'selected' : '' }}>
                                    {{ $pop->nama_pop }} ({{ $pop->kode_pop }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 4: Jumlah Port PON -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Jumlah Port PON <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <input type="number" 
                               name="capacity_olt" 
                               value="{{ old('capacity_olt', $olt->capacity_olt ?: 8) }}"
                               required
                               placeholder="8" 
                               min="1"
                               max="64" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                </div>

                <!-- Row 4: Catatan / Deskripsi OLT -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Catatan / Deskripsi OLT
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="note_olt" 
                               value="{{ old('note_olt', $olt->note_olt ?? '') }}"
                               placeholder="Keterangan tambahan OLT (opsional)..." 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                </div>

            </div>
        </div>

        <!-- =============================================================== -->
        <!-- CARD 2: SNMP MONITORING CONFIGURATION                           -->
        <!-- =============================================================== -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
            
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Konfigurasi SNMP (Simple Network Management Protocol)
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Protokol polling untuk monitoring bandwidth port PON, CPU, temperatur, dan uptime OLT.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                
                <!-- SNMP Port -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        SNMP Port <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           name="snmp_port" 
                           x-model="snmpPort"
                           required
                           placeholder="161" 
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    <span class="text-[11px] text-slate-400 block">Default: 161</span>
                </div>

                <!-- SNMP Version -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        SNMP Version <span class="text-rose-500">*</span>
                    </label>
                    <select name="snmp_version" 
                            x-model="snmpVersion"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                        <option value="v1">v1 (Legacy)</option>
                        <option value="v2c">v2c (Rekomendasi)</option>
                        <option value="v3">v3 (Encrypted Auth)</option>
                    </select>
                    <span class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold block">Rekomendasi: v2c</span>
                </div>

                <!-- SNMP Community -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        SNMP Community <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="snmp_community" 
                               x-model="snmpCommunity"
                               required
                               placeholder="public" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                    <span class="text-[11px] text-slate-400 block">Contoh: public, private</span>
                </div>

            </div>
        </div>

        <!-- =============================================================== -->
        <!-- CARD 3: CLI DIRECT MANAGEMENT & LIVE SOCKET CONNECTION TEST      -->
        <!-- =============================================================== -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            Kredensial CLI & Uji Koneksi Langsung (Telnet / SSH)
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Digunakan untuk scan live ONU unconfigured, registrasi massal, dan sinkronisasi GPON.
                        </p>
                    </div>
                </div>

                <!-- Protocol Selector Buttons -->
                <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <button type="button" 
                            @click="setProtocol('telnet')"
                            :class="protocol === 'telnet' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>🔌 Telnet</span>
                    </button>
                    <button type="button" 
                            @click="setProtocol('ssh')"
                            :class="protocol === 'ssh' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>🔒 SSH</span>
                    </button>
                    <input type="hidden" name="protocol" :value="protocol">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                
                <!-- 1. Port CLI -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Port CLI <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           name="port" 
                           x-model="port"
                           required
                           placeholder="23" 
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    <span class="text-[11px] text-slate-400 block" x-text="protocol === 'ssh' ? 'Default SSH: 22' : 'Default Telnet: 23 / 42223'"></span>
                </div>

                <!-- 2. Username Login -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Username Login
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="username" 
                               x-model="username"
                               placeholder="aplikasi / zte / admin" 
                               class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                    </div>
                </div>

                <!-- 3. Password Baru -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Password Baru
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="password" 
                               x-model="password"
                               placeholder="Kosongkan jika tidak diubah" 
                               class="w-full pl-10 pr-10 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                        <button type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span x-text="showPassword ? '🙈' : '👁️'" class="text-xs"></span>
                        </button>
                    </div>
                </div>

                <!-- 4. Enable / Super Password -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Enable Password (Privilege)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </div>
                        <input :type="showEnablePassword ? 'text' : 'password'" 
                               name="enable_password" 
                               x-model="enablePassword"
                               placeholder="Kosongkan jika tidak diubah" 
                               class="w-full pl-10 pr-10 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                        <button type="button" 
                                @click="showEnablePassword = !showEnablePassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span x-text="showEnablePassword ? '🙈' : '👁️'" class="text-xs"></span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Action Uji Koneksi Button & Result Box -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Uji handshake socket dan respons banner dari IP & Port OLT di atas sebelum menyimpan.
                    </p>
                    <button type="button" 
                            @click="testOltConnection()"
                            :disabled="testingConnection"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold shadow-xs transition duration-150 transform hover:-translate-y-0.5 cursor-pointer disabled:opacity-50 disabled:pointer-events-none">
                        <template x-if="!testingConnection">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                        </template>
                        <template x-if="testingConnection">
                            <span class="w-4 h-4 border-2 border-slate-900 border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        <span x-text="testingConnection ? 'Sedang Menguji Koneksi OLT...' : '⚡ Uji Koneksi Live ke OLT'"></span>
                    </button>
                </div>

                <!-- Test Result Container -->
                <template x-if="testResult">
                    <div :class="testResult.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/80 text-emerald-900 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/80 text-rose-900 dark:text-rose-200'"
                         class="p-4 rounded-2xl border text-xs shadow-xs space-y-2.5 transition">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 font-bold">
                                <span :class="testResult.success ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'" class="w-2.5 h-2.5 rounded-full shrink-0"></span>
                                <span x-text="testResult.success ? '✅ Koneksi Sukses & Terhubung' : '❌ Pengujian Koneksi Gagal'"></span>
                            </div>
                            <template x-if="testResult.latency">
                                <span class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-300 text-[11px] font-mono font-bold" x-text="'Latency: ' + testResult.latency + ' ms'"></span>
                            </template>
                        </div>
                        <p class="text-xs opacity-90 leading-relaxed" x-text="testResult.message"></p>
                    </div>
                </template>
            </div>

        </div>

        <!-- =============================================================== -->
        <!-- BOTTOM ACTION BUTTONS                                           -->
        <!-- =============================================================== -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-xs text-slate-500 dark:text-slate-400">
                Pastikan data yang dimasukkan sudah sesuai sebelum menyimpan perubahan.
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('noc.olt') }}" 
                   class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 text-center transition">
                    Batal
                </a>
                <button type="submit" 
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition duration-150 transform hover:-translate-y-0.5 cursor-pointer">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection
