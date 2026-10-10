@extends('layouts.app')

@section('title', 'Suspend Layanan - NOC IMS')
@section('page_title', 'Suspend Layanan')

@section('content')
<div x-data="{
    // Single Modal
    approveModalOpen: false,
    modalType: 'suspend',
    modalKodeSuspend: '',
    modalNomorInternet: '',
    modalNamaPelanggan: '',
    modalPaket: '',
    modalDate: '{{ date('Y-m-d') }}',
    modalSendWa: '1',
    openApproveModal(type, kode, nomor, nama, paket) {
        this.modalType = type;
        this.modalKodeSuspend = kode;
        this.modalNomorInternet = nomor;
        this.modalNamaPelanggan = nama;
        this.modalPaket = paket;
        this.modalDate = '{{ date('Y-m-d') }}';
        this.modalSendWa = '1';
        this.approveModalOpen = true;
    },

    // Bulk Suspend State
    selectedKodes: [],
    selectedItems: [],
    selectAll: false,
    bulkModalOpen: false,
    isSubmittingBulk: false,
    bulkDate: '{{ date('Y-m-d') }}',
    bulkSendWa: '1',

    // Queue Monitor Modal State
    queueModalOpen: false,
    currentBatchId: '',
    queueSummary: {
        total: 0,
        pending: 0,
        processing: 0,
        success: 0,
        failed: 0,
        progress_percentage: 0,
        is_completed: true
    },
    queueItems: [],
    isPolling: false,
    pollTimer: null,
    isProcessingAjax: false,

    // Methods for Multi-Select
    toggleSelectItem(item) {
        const idx = this.selectedKodes.indexOf(item.kode_suspend);
        if (idx > -1) {
            this.selectedKodes.splice(idx, 1);
            this.selectedItems = this.selectedItems.filter(i => i.kode_suspend !== item.kode_suspend);
        } else {
            this.selectedKodes.push(item.kode_suspend);
            this.selectedItems.push({
                kode_suspend: item.kode_suspend,
                nomor_internet: item.nomor_internet,
                nama_pelanggan: item.nama_pelanggan,
                paket: item.paket,
                status_suspend: item.status_suspend
            });
        }
        this.checkIfAllSelected();
    },

    isItemSelected(kode) {
        return this.selectedKodes.includes(kode);
    },

    toggleSelectAll(availableItems) {
        if (this.selectAll) {
            this.selectedKodes = [];
            this.selectedItems = [];
            this.selectAll = false;
        } else {
            const selectable = availableItems.filter(i => i.status_suspend === '11' || i.status_suspend === '18');
            this.selectedKodes = selectable.map(i => i.kode_suspend);
            this.selectedItems = selectable.map(i => ({
                kode_suspend: i.kode_suspend,
                nomor_internet: i.nomor_internet,
                nama_pelanggan: i.nama_pelanggan,
                paket: i.paket,
                status_suspend: i.status_suspend
            }));
            this.selectAll = selectable.length > 0;
        }
    },

    checkIfAllSelected() {
        const checkBoxes = document.querySelectorAll('.item-checkbox');
        if (checkBoxes.length === 0) {
            this.selectAll = false;
            return;
        }
        const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
        this.selectAll = (checkBoxes.length === checkedBoxes.length);
    },

    clearSelection() {
        this.selectedKodes = [];
        this.selectedItems = [];
        this.selectAll = false;
    },

    // Submit Bulk Suspend
    async executeBulkSuspend() {
        if (this.selectedKodes.length === 0) return;
        this.isSubmittingBulk = true;

        try {
            const response = await fetch('{{ route('noc.suspend.bulk-approve') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    selected_kodes: this.selectedKodes,
                    date: this.bulkDate,
                    send_wa: this.bulkSendWa
                })
            });

            const data = await response.json();
            this.isSubmittingBulk = false;
            this.bulkModalOpen = false;

            if (data.success) {
                const batchId = data.batch_id || '';
                this.clearSelection();
                
                // Buka Queue Monitor Modal langsung & mulai polling
                this.openQueueModal(batchId);
            } else {
                alert(data.message || 'Gagal mengeksekusi suspend massal.');
            }
        } catch (err) {
            this.isSubmittingBulk = false;
            alert('Terjadi kesalahan koneksi server: ' + err.message);
        }
    },

    // Queue Monitor Methods
    openQueueModal(batchId = '') {
        this.currentBatchId = batchId;
        this.queueModalOpen = true;
        this.fetchQueueStatus();
        this.startQueuePolling();
    },

    closeQueueModal() {
        this.queueModalOpen = false;
        this.stopQueuePolling();
    },

    startQueuePolling() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        this.isPolling = true;
        this.pollTimer = setInterval(() => {
            this.fetchQueueStatus();
            this.triggerAjaxQueueWorker();
        }, 2000);
    },

    stopQueuePolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
        this.isPolling = false;
    },

    async fetchQueueStatus() {
        try {
            const url = '{{ route('noc.suspend.queue-status') }}' + (this.currentBatchId ? ('?batch_id=' + encodeURIComponent(this.currentBatchId)) : '');
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                this.queueSummary = data.summary;
                this.queueItems = data.items || [];
                if (data.summary.is_completed && data.summary.total > 0) {
                    // Berhenti setelah selesai
                }
            }
        } catch (e) {
            console.warn('Gagal fetch status queue:', e);
        }
    },

    async triggerAjaxQueueWorker() {
        if (this.isProcessingAjax) return;
        this.isProcessingAjax = true;

        try {
            const res = await fetch('{{ route('noc.suspend.process-queue') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    batch_id: this.currentBatchId
                })
            });
            const data = await res.json();
            if (data.processed) {
                this.fetchQueueStatus();
            }
        } catch (e) {
            // silent ignore
        } finally {
            this.isProcessingAjax = false;
        }
    }
}" 
x-init="
    // Listen for custom open-queue event if needed
"
class="space-y-3.5 relative">

    <!-- Breadcrumbs & Quick Action -->
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
            <span>IMS</span>
            <span>&gt;</span>
            <span class="text-blue-500 font-semibold">Suspend Layanan</span>
        </div>

        <!-- Tombol Monitor Queue -->
        <button type="button" 
                @click="openQueueModal('')" 
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold shadow-xs transition cursor-pointer group">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
            </span>
            <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
            <span>Monitor Antrean Reboot ONT</span>
        </button>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOP FILTER BAR (MATCHING SCREENSHOT)                              -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-xl p-3 sm:p-3.5 shadow-xs">
        <form method="GET" action="{{ route('noc.suspend') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 items-center">
            
            <!-- 1. Dropdown Semua Layanan -->
            <div class="lg:col-span-3">
                <select name="layanan" onchange="this.form.submit()" class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">SEMUA LAYANAN</option>
                    @foreach($layananList as $lay)
                        <option value="{{ $lay }}" {{ $layanan === $lay ? 'selected' : '' }}>{{ strtoupper($lay) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Input Nama / Nomor Layanan -->
            <div class="lg:col-span-3">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="NAMA / NOMOR LAYANAN" 
                       class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 3. Dropdown / Input Semua Wilayah -->
            <div class="lg:col-span-3">
                <input type="text" 
                       name="wilayah" 
                       value="{{ $wilayah }}"
                       placeholder="SEMUA WILAYAH" 
                       class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 uppercase font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- 4. Dropdown Semua Status -->
            <div class="lg:col-span-2">
                <select name="status" onchange="this.form.submit()" class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">SEMUA STATUS</option>
                    <option value="11" {{ $status === '11' ? 'selected' : '' }}>(1011) Request</option>
                    <option value="12" {{ $status === '12' ? 'selected' : '' }}>(1012) Suspend</option>
                    <option value="18" {{ $status === '18' ? 'selected' : '' }}>(1018) Req. Unsuspend</option>
                    <option value="13" {{ $status === '13' ? 'selected' : '' }}>(1013) Un Suspend</option>
                    <option value="14" {{ $status === '14' ? 'selected' : '' }}>(1014) Cancel Suspend</option>
                </select>
            </div>

            <!-- 5. Action Buttons (Reset) -->
            <div class="lg:col-span-1 flex items-center gap-2">
                <!-- Tombol Reset (Red/Rose) -->
                <a href="{{ route('noc.suspend') }}" 
                   class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-xs transition cursor-pointer"
                   title="Reset Filter">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    <!-- =================================================================== -->
    <!-- 2. STATUS PILL KPI BANNERS                                         -->
    <!-- =================================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 sm:gap-3">
        
        <!-- Banner 1: Request : X User (Yellow / Amber Gradient) -->
        <a href="{{ route('noc.suspend', ['status' => '11']) }}" 
           class="py-2 px-3.5 rounded-lg bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-500 hover:to-yellow-600 text-slate-900 font-bold text-[11px] shadow-xs transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Request : {{ $countRequest }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Banner 2: Suspend : X User (Blue Gradient) -->
        <a href="{{ route('noc.suspend', ['status' => '12']) }}" 
           class="py-2 px-3.5 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold text-[11px] shadow-xs transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Suspend : {{ $countSuspend }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- Banner 3: Req. Unsuspend : X User (Rose / Pink Gradient) -->
        <a href="{{ route('noc.suspend', ['status' => '18']) }}" 
           class="py-2 px-3.5 rounded-lg bg-gradient-to-r from-rose-400 to-pink-500 hover:from-rose-500 hover:to-pink-600 text-white font-bold text-[11px] shadow-xs transition flex items-center justify-between cursor-pointer group">
            <span class="tracking-wide">Req. Unsuspend : {{ $countReqUnsuspend }} User</span>
            <svg class="w-3.5 h-3.5 opacity-70 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

    </div>

    <!-- Data Items for Alpine Select All Helper -->
    @php
        $jsAvailableItems = $suspends->map(function($i) {
            return [
                'kode_suspend' => (string)$i->kode_suspend,
                'nomor_internet' => (string)$i->nomor_internet,
                'nama_pelanggan' => (string)$i->nama_pelanggan,
                'paket' => (string)(($i->nama_kategori_bandwith ?: 'BROADBAND') . ($i->nominal_bandwith ? ' ' . $i->nominal_bandwith . ' Mbps' : '')),
                'status_suspend' => (string)$i->status_suspend,
            ];
        });
    @endphp

    <!-- =================================================================== -->
    <!-- 3. TABLE OF SUSPENDED CUSTOMERS WITH MULTI-SELECT & ACTIONS         -->
    <!-- =================================================================== -->
    <div class="bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800/90 rounded-xl shadow-xs overflow-hidden">
        
        <div class="px-3.5 py-2 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <span>show <strong class="font-bold text-slate-800 dark:text-slate-200">10</strong> entries</span>
                
                <!-- Multi-select Count Indicator in Table Header -->
                <span x-show="selectedKodes.length > 0" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold">
                    <span x-text="selectedKodes.length"></span> terpilih
                </span>
            </div>

            <div class="flex items-center gap-3 font-mono">
                <!-- Action Bulk Suspend Button when selected -->
                <button type="button"
                        x-show="selectedKodes.length > 0"
                        x-cloak
                        @click="bulkModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition cursor-pointer animate-pulse">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                    <span>Suspend Massal (<span x-text="selectedKodes.length"></span>)</span>
                </button>

                <div>
                    Total: <strong class="text-slate-800 dark:text-slate-200">{{ $suspends->total() }}</strong> User
                </div>
            </div>
        </div>

        <div class="ims-desktop-only hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                        <!-- Checkbox Select All -->
                        <th class="w-10 py-2.5 px-3 text-center">
                            <input type="checkbox" 
                                   :checked="selectAll"
                                   @change="toggleSelectAll({{ json_encode($jsAvailableItems) }})"
                                   class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                   title="Pilih Semua Request di Halaman Ini">
                        </th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Alasan Suspend</th>
                        <th class="py-2.5 px-3">State</th>
                        <th class="py-2.5 px-3 text-center min-w-[120px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($suspends as $item)
                        @php
                            $isSelectable = in_array($item->status_suspend, ['11', '18']);
                            $itemData = [
                                'kode_suspend' => (string)$item->kode_suspend,
                                'nomor_internet' => (string)$item->nomor_internet,
                                'nama_pelanggan' => (string)$item->nama_pelanggan,
                                'paket' => (string)(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')),
                                'status_suspend' => (string)$item->status_suspend,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition"
                            :class="isItemSelected('{{ $item->kode_suspend }}') ? 'bg-blue-50/60 dark:bg-blue-950/30' : ''">
                            
                            <!-- Checkbox Row -->
                            <td class="w-10 py-2.5 px-3 text-center align-top">
                                @if($isSelectable)
                                    <input type="checkbox" 
                                           class="item-checkbox w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                           :checked="isItemSelected('{{ $item->kode_suspend }}')"
                                           @change="toggleSelectItem({{ json_encode($itemData) }})">
                                @else
                                    <span class="text-slate-300 dark:text-slate-700">-</span>
                                @endif
                            </td>

                            <!-- 1. Customer Column -->
                            <td class="py-2.5 px-3 align-top">
                                <!-- ID Pelanggan (Click to Profile) & Nama -->
                                @if(!empty($item->nomor_internet) && $item->nomor_internet !== '-')
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="group block"
                                   title="Buka Profile Pelanggan ({{ $item->nomor_internet }})">
                                    <div class="font-mono font-bold text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 group-hover:underline tracking-wide text-xs">
                                        {{ $item->nomor_internet }}
                                    </div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:underline uppercase text-[11px] mt-0.5 transition-colors">
                                        <span>{{ $item->nama_pelanggan }}</span>
                                        <span class="text-slate-500 font-normal no-underline">
                                            ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                        </span>
                                    </div>
                                </a>
                                @else
                                <div class="font-mono font-bold text-slate-400 text-xs tracking-wide">
                                    -
                                </div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 uppercase text-[11px] mt-0.5">
                                    <span>{{ $item->nama_pelanggan }}</span>
                                    <span class="text-slate-500 font-normal">
                                        ( {{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }} )
                                    </span>
                                </div>
                                @endif

                                <!-- Bandwidth -->
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium uppercase mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }}
                                    @if($item->nominal_bandwith)
                                        <span>{{ $item->nominal_bandwith }} Mbps</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 2. Alasan Suspend Column -->
                            <td class="py-2.5 px-3 align-top text-slate-700 dark:text-slate-300 text-[11px]">
                                {{ $item->desc_suspend ?: ($item->note_suspend ?? 'Melewati batas pembayaran yang telah ditentukan') }}
                            </td>

                            <!-- 3. State Column -->
                            <td class="py-2.5 px-3 align-top">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold font-mono tracking-wide
                                    @if(in_array($item->status_suspend, ['11']))
                                        bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30
                                    @elseif(in_array($item->status_suspend, ['12']))
                                        bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30
                                    @elseif(in_array($item->status_suspend, ['18']))
                                        bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30
                                    @elseif(in_array($item->status_suspend, ['13']))
                                        bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30
                                    @else
                                        bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                                    @endif">
                                    (10{{ $item->status_suspend }}) {{ $item->desc_status_suspend ?: 'Request' }}
                                </span>
                            </td>

                            <!-- 4. Action Column -->
                            <td class="py-2.5 px-3 align-top text-center">
                                @if($item->status_suspend == '11')
                                    <!-- State: Request Suspend -> Show Approve Suspend (Modal) & Cancel -->
                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-1">
                                        <button type="button" 
                                                @click="openApproveModal('suspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold shadow-xs transition cursor-pointer"
                                                title="Setujui Permintaan Suspend (Eksekusi Isolir)">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>Approve</span>
                                        </button>

                                        <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (Cancel) suspend pelanggan {{ $item->nomor_internet }}?');">
                                            @csrf
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[10px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                    title="Batalkan Permintaan Suspend">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span>Canceled</span>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($item->status_suspend == '18')
                                    <!-- State: Req. Unsuspend -> Show Approve Unsuspend (Modal) & Cancel -->
                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-1">
                                        <button type="button" 
                                                @click="openApproveModal('unsuspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold shadow-xs transition cursor-pointer"
                                                title="Setujui Buka Isolir">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>Approve Buka</span>
                                        </button>

                                        <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan unsuspend pelanggan {{ $item->nomor_internet }}?');">
                                            @csrf
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-rose-600 dark:bg-slate-800 dark:hover:bg-rose-600 text-slate-600 dark:text-slate-400 hover:text-white dark:hover:text-white text-[10px] font-bold border border-slate-300 dark:border-slate-700 transition cursor-pointer"
                                                    title="Tolak Unsuspend">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                <span>Canceled</span>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($item->status_suspend == '12')
                                    <!-- State: Suspend (Sudah Terisolir) -->
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[10px] font-bold">
                                        <svg class="w-3 h-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                        <span>Terisolir Aktif</span>
                                    </div>
                                @elseif($item->status_suspend == '13')
                                    <!-- State: Un Suspend -->
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                                        <svg class="w-3 h-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                        <span>Layanan Normal</span>
                                    </div>
                                @elseif($item->status_suspend == '14')
                                    <!-- State: Cancel Suspend -->
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-500/20 text-[10px] font-medium">
                                        <span>Dibatalkan</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400 text-xs">
                                Tidak ada data suspend layanan yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List View -->
        <div class="ims-mobile-only block md:hidden p-3 sm:p-4 space-y-3.5 divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($suspends as $item)
                @php
                    $isSelectable = in_array($item->status_suspend, ['11', '18']);
                    $itemData = [
                        'kode_suspend' => (string)$item->kode_suspend,
                        'nomor_internet' => (string)$item->nomor_internet,
                        'nama_pelanggan' => (string)$item->nama_pelanggan,
                        'paket' => (string)(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')),
                        'status_suspend' => (string)$item->status_suspend,
                    ];
                @endphp
                <div class="pt-3.5 first:pt-0 space-y-3"
                     :class="isItemSelected('{{ $item->kode_suspend }}') ? 'p-2 rounded-xl bg-blue-50/60 dark:bg-blue-950/30' : ''">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-2.5">
                            @if($isSelectable)
                                <input type="checkbox" 
                                       class="item-checkbox mt-1 w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                       :checked="isItemSelected('{{ $item->kode_suspend }}')"
                                       @change="toggleSelectItem({{ json_encode($itemData) }})">
                            @endif
                            <div>
                                <a href="{{ route('teknik.pelanggan.profile', $item->nomor_internet) }}" 
                                   class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline text-sm inline-block">
                                    {{ $item->nomor_internet }}
                                </a>
                                <div class="font-bold text-slate-800 dark:text-slate-100 uppercase text-xs mt-0.5">
                                    {{ $item->nama_pelanggan }}
                                    <span class="text-slate-500 font-normal">({{ $item->jenis_kelamin == 1 ? 'L' : ($item->jenis_kelamin == 2 ? 'P' : '-') }})</span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium uppercase mt-0.5">
                                    {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }} {{ $item->nominal_bandwith ? $item->nominal_bandwith . ' Mbps' : '' }}
                                </div>
                            </div>
                        </div>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold font-mono tracking-wide shrink-0
                            @if(in_array($item->status_suspend, ['11']))
                                bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30
                            @elseif(in_array($item->status_suspend, ['12']))
                                bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30
                            @elseif(in_array($item->status_suspend, ['18']))
                                bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30
                            @elseif(in_array($item->status_suspend, ['13']))
                                bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30
                            @else
                                bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30
                            @endif">
                            (10{{ $item->status_suspend }}) {{ $item->desc_status_suspend ?: 'Request' }}
                        </span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200/70 dark:border-slate-800/60 text-xs">
                        <span class="text-slate-500 text-[11px] block font-medium">Alasan:</span>
                        <p class="text-slate-700 dark:text-slate-300 text-[11px] mt-0.5 leading-relaxed">
                            {{ $item->desc_suspend ?: ($item->note_suspend ?? 'Melewati batas pembayaran yang telah ditentukan') }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-1">
                        @if($item->status_suspend == '11')
                            <button type="button" 
                                    @click="openApproveModal('suspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition active:scale-98">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span>Approve Suspend</span>
                            </button>
                            <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (Cancel) suspend pelanggan {{ $item->nomor_internet }}?');">
                                @csrf
                                <button type="submit" 
                                        class="py-2 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-xs font-semibold transition active:scale-98">
                                    Cancel
                                </button>
                            </form>
                        @elseif($item->status_suspend == '18')
                            <button type="button" 
                                    @click="openApproveModal('unsuspend', '{{ $item->kode_suspend }}', '{{ $item->nomor_internet }}', {{ json_encode($item->nama_pelanggan ?? 'Pelanggan') }}, {{ json_encode(($item->nama_kategori_bandwith ?: 'BROADBAND') . ($item->nominal_bandwith ? ' ' . $item->nominal_bandwith . ' Mbps' : '')) }})"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition active:scale-98">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span>Approve Buka</span>
                            </button>
                            <form action="{{ route('noc.suspend.cancel', $item->kode_suspend) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan unsuspend pelanggan {{ $item->nomor_internet }}?');">
                                @csrf
                                <button type="submit" 
                                        class="py-2 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-xs font-semibold transition active:scale-98">
                                    Cancel
                                </button>
                            </form>
                        @elseif($item->status_suspend == '12')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                Terisolir Aktif
                            </span>
                        @elseif($item->status_suspend == '13')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Layanan Normal
                            </span>
                        @else
                            <span class="text-xs text-slate-500 dark:text-slate-400">Status: {{ $item->desc_status_suspend ?: 'Dibatalkan' }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    Tidak ada data suspend layanan yang cocok.
                </div>
            @endforelse
        </div>

        <!-- Pagination Bar -->
        @if($suspends->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                {{ $suspends->links() }}
            </div>
        @endif

    </div>

    <!-- =================================================================== -->
    <!-- 4. FLOATING STICKY ACTION BAR (WHEN ROWS SELECTED)                  -->
    <!-- =================================================================== -->
    <div x-show="selectedKodes.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-10"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-10"
         class="fixed bottom-6 inset-x-4 max-w-xl mx-auto z-40 bg-slate-900/95 dark:bg-slate-950/95 text-white backdrop-blur-xl border border-slate-700/80 rounded-2xl shadow-2xl p-3 flex items-center justify-between gap-3">
        
        <div class="flex items-center gap-2.5 pl-2">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
            </span>
            <span class="text-xs font-bold">
                <strong class="text-cyan-400 font-mono text-sm" x-text="selectedKodes.length"></strong> Pelanggan Dipilih
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="clearSelection()" 
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition cursor-pointer">
                Batal
            </button>

            <button type="button" 
                    @click="bulkModalOpen = true" 
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white text-xs font-bold shadow-lg shadow-blue-500/25 transition cursor-pointer">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                </svg>
                <span>Eksekusi Suspend Massal</span>
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. SINGLE APPROVE SUSPEND / UNSUSPEND MODAL                         -->
    <!-- =================================================================== -->
    <div x-show="approveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="approveModalOpen = false"
             class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white"
                    x-text="modalType === 'unsuspend' ? 'Form UNsuspend' : 'Form Approve Suspend'">
                </h3>
                <button type="button" @click="approveModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="'{{ url('/noc/suspend') }}/' + modalKodeSuspend + '/approve'" method="POST" class="flex flex-col flex-1">
                @csrf
                <div class="p-5 space-y-4">
                    
                    <!-- Card 1: Data Pelanggan -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800">
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-2.5">
                            Data Pelanggan
                        </h4>
                        <ul class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 list-disc list-inside">
                            <li class="font-bold uppercase text-slate-800 dark:text-white" x-text="modalNamaPelanggan"></li>
                            <li>Nomor Layanan <span class="font-mono font-semibold" x-text="modalNomorInternet"></span></li>
                            <li class="uppercase text-blue-600 dark:text-blue-400 font-medium" x-text="modalPaket"></li>
                        </ul>
                    </div>

                    <!-- Card 2: Date Input & WhatsApp Confirmation -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-4">
                        <!-- Date Input -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                <span x-text="modalType === 'unsuspend' ? 'Finish Suspend' : 'Start Suspend'"></span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   :name="modalType === 'unsuspend' ? 'finish_suspend' : 'start_suspend'" 
                                   required
                                   x-model="modalDate"
                                   class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Kirim WhatsApp Radio -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Kirim whatsapp ke Pelanggan ? <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <div class="flex items-center gap-6">
                                <label class="inline-flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" 
                                           name="send_wa" 
                                           value="1" 
                                           x-model="modalSendWa"
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 transition">YA</span>
                                </label>

                                <label class="inline-flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" 
                                           name="send_wa" 
                                           value="0" 
                                           x-model="modalSendWa"
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-600 transition">TIDAK</span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Buttons -->
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" 
                            @click="approveModalOpen = false" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#00bcd4] hover:bg-[#00acc1] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span>Tutup</span>
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0d6efd] hover:bg-[#0b5ed7] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                        <span>Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 6. MODAL KONFIRMASI SUSPEND MASSAL                                  -->
    <!-- =================================================================== -->
    <div x-show="bulkModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="!isSubmittingBulk && (bulkModalOpen = false)"
             class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-r from-blue-900/40 to-indigo-900/40 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-400">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                            Konfirmasi Suspend Massal
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Eksekusi <span class="font-bold text-blue-500" x-text="selectedKodes.length"></span> pelanggan sekaligus
                        </p>
                    </div>
                </div>
                <button type="button" 
                        :disabled="isSubmittingBulk"
                        @click="bulkModalOpen = false" 
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1 leading-none transition">
                    &times;
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-5 space-y-4 overflow-y-auto max-h-[65vh]">
                
                <!-- Notice Info Banner -->
                <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/80 flex items-start gap-2.5 text-xs text-blue-800 dark:text-blue-300">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div class="space-y-1">
                        <p class="font-semibold">Proses Cepat & Latar Belakang (Non-blocking)</p>
                        <p class="text-[11px] text-blue-700 dark:text-blue-400 leading-relaxed">
                            MikroTik PPPoE secret disable & kick sesi koneksi akan dieksekusi seketika. Proses Remote Reboot ONT/ONU akan dialihkan ke <strong>Background Queue</strong> secara otomatis tanpa memperlambat browser Anda.
                        </p>
                    </div>
                </div>

                <!-- Customer Selected List -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Daftar Pelanggan Terpilih (<span x-text="selectedItems.length"></span>):
                    </label>
                    <div class="max-h-44 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <template x-for="c in selectedItems" :key="c.kode_suspend">
                            <div class="p-2.5 flex items-center justify-between text-xs hover:bg-slate-100/50 dark:hover:bg-slate-800/40">
                                <div>
                                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400" x-text="c.nomor_internet"></span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 uppercase ml-1.5" x-text="c.nama_pelanggan"></span>
                                </div>
                                <span class="text-[10px] text-slate-500 uppercase font-mono" x-text="c.paket"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Start Date & WA Options -->
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Suspend
                        </label>
                        <input type="date" 
                               x-model="bulkDate"
                               class="w-full text-xs px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Kirim Notifikasi WhatsApp ke Semua Pelanggan?
                        </label>
                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" value="1" x-model="bulkSendWa" class="w-4 h-4 text-blue-600">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">YA</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" value="0" x-model="bulkSendWa" class="w-4 h-4 text-blue-600">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">TIDAK</span>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                <button type="button" 
                        :disabled="isSubmittingBulk"
                        @click="bulkModalOpen = false" 
                        class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition cursor-pointer">
                    Batal
                </button>

                <button type="button" 
                        :disabled="isSubmittingBulk"
                        @click="executeBulkSuspend()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold shadow-md transition cursor-pointer disabled:opacity-50">
                    <template x-if="isSubmittingBulk">
                        <svg class="animate-spin w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!isSubmittingBulk">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                    </template>
                    <span x-text="isSubmittingBulk ? 'Memproses MikroTik...' : 'Ya, Eksekusi Suspend Massal'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 7. LIVE QUEUE MONITOR MODAL / DRAWER (TAMPILAN QUEUE BACKGROUND)   -->
    <!-- =================================================================== -->
    <div x-show="queueModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="closeQueueModal()"
             class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col max-h-[88vh]">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="p-1.5 rounded-lg bg-cyan-500/20 text-cyan-400">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold tracking-wide">
                                Antrean Remote Reboot ONT (OLT)
                            </h3>
                            <!-- Pulsing State -->
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="queueSummary.is_completed ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 animate-pulse'">
                                <span class="h-1.5 w-1.5 rounded-full" :class="queueSummary.is_completed ? 'bg-emerald-400' : 'bg-cyan-400'"></span>
                                <span x-text="queueSummary.is_completed ? 'Selesai' : 'Sedang Berjalan'"></span>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5" x-text="currentBatchId ? ('Batch ID: ' + currentBatchId) : 'Menampilkan Seluruh Antrean 24 Jam'"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="fetchQueueStatus()" 
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                            title="Segarkan Data">
                        <svg class="w-4 h-4" :class="isPolling ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </button>
                    <button type="button" @click="closeQueueModal()" class="text-slate-400 hover:text-white text-xl font-bold p-1 leading-none transition">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                
                <!-- KPI Status Counters -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-center">
                        <span class="text-[10px] text-slate-500 font-bold block uppercase">Total</span>
                        <span class="text-base font-bold font-mono text-slate-800 dark:text-slate-100" x-text="queueSummary.total"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center">
                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold block uppercase">Pending</span>
                        <span class="text-base font-bold font-mono text-amber-600 dark:text-amber-400" x-text="queueSummary.pending"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-center">
                        <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold block uppercase">Processing</span>
                        <span class="text-base font-bold font-mono text-blue-600 dark:text-blue-400" x-text="queueSummary.processing"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-center">
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block uppercase">Sukses</span>
                        <span class="text-base font-bold font-mono text-emerald-600 dark:text-emerald-400" x-text="queueSummary.success"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-center">
                        <span class="text-[10px] text-rose-600 dark:text-rose-400 font-bold block uppercase">Gagal</span>
                        <span class="text-base font-bold font-mono text-rose-600 dark:text-rose-400" x-text="queueSummary.failed"></span>
                    </div>
                </div>

                <!-- Animated Progress Bar -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">Kemajuan Eksekusi Reboot ONT</span>
                        <span class="font-mono text-blue-600 dark:text-cyan-400 font-bold" x-text="queueSummary.progress_percentage + '%'"></span>
                    </div>
                    <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden p-0.5">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 transition-all duration-500"
                             :style="'width: ' + queueSummary.progress_percentage + '%'"></div>
                    </div>
                </div>

                <!-- Queue Items Table -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
                    <div class="max-h-64 overflow-y-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 dark:bg-slate-950/80 sticky top-0 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                                <tr>
                                    <th class="py-2 px-3">Pelanggan</th>
                                    <th class="py-2 px-3">Index OLT</th>
                                    <th class="py-2 px-3">Status</th>
                                    <th class="py-2 px-3">Respon OLT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-mono text-[11px]">
                                <template x-for="item in queueItems" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                        
                                        <!-- Customer -->
                                        <td class="py-2 px-3 align-top font-sans">
                                            <div class="font-mono font-bold text-blue-600 dark:text-blue-400" x-text="item.nomor_internet"></div>
                                            <div class="text-[10px] text-slate-700 dark:text-slate-300 uppercase font-semibold mt-0.5" x-text="item.nama_pelanggan || '-'"></div>
                                        </td>

                                        <!-- Index OLT -->
                                        <td class="py-2 px-3 align-top">
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-mono" x-text="item.index_olt || '-'"></span>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-2 px-3 align-top font-sans">
                                            <template x-if="item.status === 'pending'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                    <span>Menunggu</span>
                                                </span>
                                            </template>
                                            <template x-if="item.status === 'processing'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 animate-pulse">
                                                    <svg class="animate-spin w-2.5 h-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span>Rebooting...</span>
                                                </span>
                                            </template>
                                            <template x-if="item.status === 'success'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    <span>Sukses</span>
                                                </span>
                                            </template>
                                            <template x-if="item.status === 'failed'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18 18 6M6 6l12 12"/></svg>
                                                    <span>Gagal</span>
                                                </span>
                                            </template>
                                        </td>

                                        <!-- Response Message -->
                                        <td class="py-2 px-3 align-top font-sans text-[10px] text-slate-600 dark:text-slate-400">
                                            <span x-text="item.response_message || '-'"></span>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="queueItems.length === 0">
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-slate-400 text-xs font-sans">
                                            Tidak ada antrean reboot yang aktif saat ini.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>Auto-update setiap 2 detik</span>
                </div>

                <button type="button" 
                        @click="closeQueueModal()" 
                        class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
