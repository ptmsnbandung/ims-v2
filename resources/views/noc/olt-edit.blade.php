@extends('layouts.app')

@section('title', 'Edit OLT - ' . ($olt->name_olt ?? $olt->kode_olt) . ' - NOC IMS')
@section('page_title', 'Edit OLT')

@section('content')
<div class="space-y-6"
     x-data="{
        testingConnection: false,
        testResult: null,
        protocol: '{{ $olt->protocol ?? 'telnet' }}',
        port: {{ $olt->port ?? 23 }},
        ipAddress: '{{ $olt->ip_address ?? '' }}',
        username: '{{ $olt->username ?? '' }}',
        password: '',
        enablePassword: '',
        snmpPort: {{ $olt->snmp_port ?? 161 }},
        snmpVersion: '{{ $olt->snmp_version ?? 'v2c' }}',
        snmpCommunity: '{{ $olt->snmp_community ?? 'public' }}',

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

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
        <a href="{{ route('noc.olt') }}" class="hover:text-blue-400 transition">Master Input OLT</a>
        <span>&gt;</span>
        <span class="text-white">Edit: {{ $olt->name_olt ?? $olt->kode_olt }}</span>
    </div>

    <!-- Page Title -->
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
            Edit OLT: {{ $olt->name_olt ?? $olt->kode_olt }}
        </h1>
    </div>

    <!-- Main Form Card (Dark Command Center Theme) -->
    <form action="{{ route('noc.olt.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="id" value="{{ $olt->id ?? '' }}">
        <input type="hidden" name="kode_olt" value="{{ $olt->kode_olt }}">

        <div class="bg-slate-900/90 border border-slate-800 backdrop-blur-xl rounded-2xl p-6 sm:p-7 shadow-xl shadow-black/20 space-y-8 text-slate-100">
            
            <!-- =============================================================== -->
            <!-- 1. DEVICE INFORMATION SECTION                                   -->
            <!-- =============================================================== -->
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold text-white text-sm">
                        Device Information
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Konfigurasi spesifikasi perangkat OLT (Optical Line Terminal) dan integrasi POP server FTTH.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    
                    <!-- Row 1: Name * & Hostname -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name_olt" 
                               value="{{ old('name_olt', $olt->name_olt) }}"
                               required
                               placeholder="OLT Jakarta 01" 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        @error('name_olt')
                            <span class="text-[11px] text-rose-500 font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            Hostname
                        </label>
                        <input type="text" 
                               name="hostname" 
                               value="{{ old('hostname', $olt->hostname ?? $olt->kode_olt) }}"
                               placeholder="olt-jkt-01" 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>

                    <!-- Row 2: IP Address * & Vendor * -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            IP Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="ip_address" 
                               x-model="ipAddress"
                               required
                               placeholder="192.168.1.1" 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        @error('ip_address')
                            <span class="text-[11px] text-rose-500 font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            Vendor <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="brand" 
                               value="{{ old('brand', $olt->brand) }}"
                               placeholder="ZTE, Huawei, Fiberhome..." 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>

                    <!-- Row 3: Model & POP Server -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            Model
                        </label>
                        <input type="text" 
                               name="model" 
                               value="{{ old('model', $olt->model) }}"
                               placeholder="C320, MA5608T..." 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            POP Server / Lokasi
                        </label>
                        <select name="kode_pop" 
                                class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            <option value="">Select an option</option>
                            @foreach($pops as $pop)
                                <option value="{{ $pop->kode_pop }}" {{ old('kode_pop', $olt->kode_pop) == $pop->kode_pop ? 'selected' : '' }}>
                                    {{ $pop->nama_pop }} ({{ $pop->kode_pop }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Row 4: Jumlah Port PON -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">
                            Jumlah Port PON <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="capacity_olt" 
                               value="{{ old('capacity_olt', $olt->capacity_olt ?: 8) }}"
                               required
                               placeholder="8" 
                               min="1"
                               max="64"
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </div>

                </div>
            </div>

            <!-- =============================================================== -->
            <!-- 2. SNMP CONFIGURATION SECTION                                   -->
            <!-- =============================================================== -->
            <div class="pt-6 border-t border-slate-800 space-y-4">
                <div>
                    <h3 class="font-bold text-white text-sm">
                        SNMP Configuration
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Digunakan untuk monitoring status OLT secara berkala
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                    
                    <!-- SNMP Port * -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">
                            SNMP Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="snmp_port" 
                               x-model="snmpPort"
                               required
                               placeholder="161" 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <span class="text-[11px] text-slate-500 block mt-1">Default: 161</span>
                    </div>

                    <!-- SNMP Version * -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">
                            SNMP Version <span class="text-rose-500">*</span>
                        </label>
                        <select name="snmp_version" 
                                x-model="snmpVersion"
                                class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            <option value="v1">v1</option>
                            <option value="v2c">v2c</option>
                            <option value="v3">v3</option>
                        </select>
                        <span class="text-[11px] text-slate-500 block mt-1">Rekomendasi: v2c</span>
                    </div>

                    <!-- SNMP Community * -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-300">
                            SNMP Community <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="snmp_community" 
                               x-model="snmpCommunity"
                               required
                               placeholder="public" 
                               class="w-full px-4 py-2.5 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <span class="text-[11px] text-slate-500 block mt-1">Contoh: public, private</span>
                    </div>

                </div>
            </div>

            <!-- =============================================================== -->
            <!-- 3. CLI / TELNET / SSH ACCESS & LIVE TEST                       -->
            <!-- =============================================================== -->
            <div class="pt-6 border-t border-slate-800 space-y-4" x-data="{ expanded: true }">
                <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                    <div>
                        <h3 class="font-bold text-white text-sm">
                            CLI Management & Direct Connection (Telnet / SSH)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Digunakan untuk live sync slot GPON, profiling bandwidth, dan scan ONU unconfigured (Kredensial terenkripsi aman)
                        </p>
                    </div>
                    <span class="text-xs text-blue-400 font-semibold" x-text="expanded ? 'Tutup Pengaturan' : 'Buka Pengaturan'"></span>
                </div>

                <div x-show="expanded" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Protokol -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-400">Protokol Akses</label>
                            <select name="protocol" 
                                    x-model="protocol" 
                                    @change="port = (protocol === 'ssh' ? 22 : 23)"
                                    class="w-full px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white">
                                <option value="telnet">Telnet (Port 23)</option>
                                <option value="ssh">SSH (Port 22)</option>
                            </select>
                        </div>

                        <!-- Port -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-400">Port CLI</label>
                            <input type="number" 
                                   name="port" 
                                   x-model="port"
                                   class="w-full px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white">
                        </div>

                        <!-- Username -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-400">Username Login</label>
                            <input type="text" 
                                   name="username" 
                                   x-model="username"
                                   placeholder="zte / admin" 
                                   class="w-full px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white">
                        </div>

                        <!-- Password -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-400">Password Baru</label>
                            <input type="password" 
                                   name="password" 
                                   x-model="password"
                                   placeholder="(Kosongkan jika tidak diubah)" 
                                   class="w-full px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white">
                        </div>

                        <!-- Enable / Super Password -->
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-400">Enable Password (ZTE/Cisco Privilege)</label>
                            <input type="password" 
                                   name="enable_password" 
                                   x-model="enablePassword"
                                   placeholder="(Kosongkan jika tidak diubah)" 
                                   class="w-full px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white">
                        </div>

                        <!-- Test Connection Button -->
                        <div class="sm:col-span-2 flex flex-col justify-end space-y-1">
                            <button type="button" 
                                    @click="testOltConnection()"
                                    :disabled="testingConnection"
                                    class="w-full px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                                <template x-if="!testingConnection">
                                    <svg class="w-4 h-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                    </svg>
                                </template>
                                <template x-if="testingConnection">
                                    <span class="w-4 h-4 border-2 border-slate-400 border-t-white rounded-full animate-spin"></span>
                                </template>
                                <span x-text="testingConnection ? 'Sedang Menguji Koneksi...' : '⚡ Test Koneksi ke OLT'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Test Result Indicator -->
                    <template x-if="testResult">
                        <div :class="testResult.success ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400'"
                             class="p-3.5 rounded-xl border text-xs font-semibold flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span :class="testResult.success ? 'bg-emerald-400' : 'bg-rose-400'" class="w-2 h-2 rounded-full"></span>
                                <span x-text="testResult.message"></span>
                            </div>
                            <template x-if="testResult.latency">
                                <span class="px-2 py-0.5 rounded-lg bg-black/20 text-[10px] font-mono" x-text="testResult.latency + ' ms'"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- Form Action Buttons (Bottom Bar) -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- 1. Save Changes -->
            <button type="submit" 
                    name="action" 
                    value="update"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/25 transition cursor-pointer">
                Simpan Perubahan
            </button>

            <!-- 2. Cancel -->
            <a href="{{ route('noc.olt') }}" 
               class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition">
                Kembali
            </a>
        </div>
    </form>

</div>
@endsection
