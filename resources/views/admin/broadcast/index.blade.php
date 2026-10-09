@extends('layouts.app')

@section('content')
<div class="space-y-4" x-data="broadcastApp()">
    
    <!-- Header Banner WhatsApp Green -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-[#075E54] via-[#128C7E] to-[#25D366] p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden text-white">
        <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute left-1/3 -top-10 w-36 h-36 bg-black/10 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/15 text-white border border-white/20 backdrop-blur-xs">
                    <svg class="w-3 h-3 text-emerald-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span>Modul Direktur &amp; Master Admin</span>
                </span>
                
                @if($isMetaConfigured)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-900/40 text-emerald-100 border border-emerald-300/30 backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        <span>Meta WhatsApp Cloud API Aktif</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-900/40 text-amber-100 border border-amber-300/30 backdrop-blur-xs" title="Isi META_WA_TOKEN & META_WA_PHONE_NUMBER_ID di .env untuk mengaktifkan Cloud API">
                        <span class="w-2 h-2 rounded-full bg-amber-300"></span>
                        <span>Mode WhatsApp Web (Kredensial Belum Diatur)</span>
                    </span>
                @endif
            </div>
            
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white shrink-0 drop-shadow-xs" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                <span>Broadcast WhatsApp Meta Business</span>
            </h1>
            
            <p class="text-emerald-50 text-[11px] sm:text-xs mt-1 max-w-2xl leading-relaxed">
                Kirim pengingat tagihan jatuh tempo atau pengumuman resmi ke WhatsApp pelanggan menggunakan <b>Meta WhatsApp Cloud API</b> resmi.
            </p>
        </div>

        <div class="relative z-10 flex items-center gap-2 flex-wrap shrink-0">
            <button @click="testMetaApi()" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-semibold border border-white/25 transition backdrop-blur-xs shadow-2xs cursor-pointer">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.122a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.98 0 13.789" />
                </svg>
                <span>Test Koneksi Meta API</span>
            </button>

            <a href="{{ route('admin.broadcast.history') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-emerald-50 text-[#075E54] text-xs font-bold shadow-xs transition">
                <svg class="w-3.5 h-3.5 text-[#128C7E]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Riwayat Broadcast Log</span>
            </a>
        </div>
    </div>

    <!-- Stat KPI Cards (Compact) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Mendekati Jatuh Tempo / Belum Lunas -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Belum Bayar / Jatuh Tempo</p>
                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ number_format($totalUnpaidCount) }}</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Pelanggan perlu penagihan</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Total Target Pelanggan -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total Pelanggan Terfilter</p>
                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ number_format($totalTargetCount) }}</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Sesuai kriteria filter</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Broadcast Terkirim Hari Ini -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Terkirim Hari Ini</p>
                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ number_format($sentTodayCount) }}</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Status dikirim ke WA</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Total Log Broadcast -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Total Akumulasi Log</p>
                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ number_format($totalSentLog) }}</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Total riwayat broadcast</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                <svg class="w-4.5 h-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- LANGKAH 1: PENGATURAN TEMPLATE META WHATSAPP -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs p-4 sm:p-5 space-y-3.5">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-[#128C7E] text-white font-bold text-xs flex items-center justify-center shrink-0">1</span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Pilih Template Meta WhatsApp &amp; Konfigurasi Pengiriman</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Template pesan dikelola sesuai standar Meta Cloud API untuk pengiriman terverifikasi.</p>
                </div>
            </div>
            <button @click="showPreview = !showPreview" type="button" class="text-[11px] font-semibold text-[#128C7E] dark:text-emerald-400 hover:underline flex items-center gap-1 shrink-0 cursor-pointer">
                <span x-text="showPreview ? '🙈 Sembunyikan Pratinjau' : '👁️ Tampilkan Pratinjau WA'"></span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">
            <!-- Left Column: Template Selection & Meta Info (7 Cols) -->
            <div class="lg:col-span-7 space-y-3">
                <!-- Template Selector -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200">
                            Pilih Template Resmi Meta yang Digunakan:
                        </label>
                        <button type="button" 
                                @click="syncMetaTemplates()" 
                                :disabled="isSyncingTemplates"
                                class="inline-flex items-center gap-1 text-[10.5px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-md border border-blue-200 dark:border-blue-800 transition cursor-pointer"
                                title="Ambil template yang sudah didaftarkan dan disetujui di Meta Dashboard">
                            <svg class="w-3 h-3" :class="isSyncingTemplates ? 'animate-spin text-blue-500' : 'text-blue-600 dark:text-blue-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span x-text="isSyncingTemplates ? 'Menarik...' : 'Tarik Template dari Meta'"></span>
                        </button>
                    </div>
                    <select x-model="selectedTemplateId" @change="loadSelectedTemplate()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" 
                                    data-pesan="{{ addslashes($tpl->pesan) }}" 
                                    data-meta-name="{{ $tpl->meta_template_name ?? 'tagihan_bulanan' }}"
                                    data-meta-lang="{{ $tpl->meta_language ?? 'id' }}"
                                    data-meta-params="{{ addslashes($tpl->meta_params_map ?? '[]') }}"
                                    data-kategori="{{ $tpl->kategori }}">
                                📌 {{ $tpl->nama_template }} ({{ $tpl->meta_template_name }}) {{ $tpl->is_default ? '⭐ [Default]' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Meta Template Details Badge -->
                <div class="bg-slate-50 dark:bg-slate-800/80 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700/60 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400 text-[11px]">Nama Template di Meta:</span>
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/20 px-1.5 py-0.2 rounded text-[10.5px]" x-text="currentMetaName"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400 text-[11px]">Bahasa Template:</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold text-[11px]" x-text="currentMetaLang + ' (Indonesian)'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400 text-[11px]">Status Registrasi:</span>
                        <span class="inline-flex items-center gap-1 text-[10.5px] font-bold text-emerald-600 dark:text-emerald-400">
                            <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            <span>Meta Cloud API Approved</span>
                        </span>
                    </div>
                </div>

                <!-- Parameter Mapping Information -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200 mb-1">
                        Mapping Parameter Otomatis ke Template Meta:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1.5 text-[10.5px]">
                        <template x-for="card in currentParamCards" :key="card.num">
                            <div class="p-2 rounded-md bg-emerald-50/70 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
                                <div class="font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                    <span x-text="card.num"></span> &rarr; <span x-text="card.label"></span>
                                </div>
                                <div class="text-[9.5px] text-slate-500 dark:text-slate-400" x-text="card.desc"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Pengaturan Mode Pengiriman -->
                <div class="p-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">Metode Pengiriman WhatsApp:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition" :class="metodeKirim === 'meta_api' ? 'bg-emerald-50 border-emerald-500 dark:bg-emerald-500/10 dark:border-emerald-500' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'">
                            <input type="radio" value="meta_api" x-model="metodeKirim" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="text-[11px] font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                    <span>🚀 Meta Cloud API</span>
                                    <span class="text-[8.5px] bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 font-bold px-1 rounded">Otomatis</span>
                                </div>
                                <div class="text-[9.5px] text-slate-500 dark:text-slate-400">Kirim massal otomatis langsung via server Meta resmi.</div>
                            </div>
                        </label>

                        <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition" :class="metodeKirim === 'wa_web' ? 'bg-emerald-50 border-emerald-500 dark:bg-emerald-500/10 dark:border-emerald-500' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'">
                            <input type="radio" value="wa_web" x-model="metodeKirim" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="text-[11px] font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                    <span>🌐 WhatsApp Web</span>
                                    <span class="text-[8.5px] bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 font-bold px-1 rounded">Manual</span>
                                </div>
                                <div class="text-[9.5px] text-slate-500 dark:text-slate-400">Buka tautan wa.me ke browser untuk konfirmasi manual.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Column: WhatsApp Live Message Preview (5 Cols) -->
            <div class="lg:col-span-5" x-show="showPreview" x-transition>
                <label class="block text-[11px] font-bold text-slate-800 dark:text-slate-200 mb-1">
                    📱 Pratinjau Tampilan Pesan WhatsApp di Ponsel Pelanggan:
                </label>
                
                <div class="border border-slate-300 dark:border-slate-800 rounded-xl overflow-hidden bg-[#efeae2] dark:bg-[#0b141a] shadow-xs">
                    <!-- WA Header Bar -->
                    <div class="bg-[#075e54] dark:bg-[#202c33] px-3.5 py-2 flex items-center gap-2.5 border-b border-emerald-800 dark:border-slate-800 text-white">
                        <div class="w-7 h-7 rounded-full bg-emerald-700 dark:bg-emerald-600 flex items-center justify-center text-white font-bold text-[11px] flex-shrink-0">
                            IMS
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-white truncate">IMS WhatsApp Business Official</div>
                            <div class="text-[9.5px] text-emerald-200 dark:text-emerald-400 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Centang Hijau &bull; Meta Verified</span>
                            </div>
                        </div>
                    </div>

                    <!-- WA Chat Bubble Body -->
                    <div class="p-3 min-h-[140px] max-h-[300px] overflow-y-auto" style="background-color:#efeae2; background-image:radial-gradient(rgba(0,0,0,0.06) 1px, transparent 0); background-size:16px 16px;">
                        <div class="bg-white dark:bg-[#1f2c34] text-slate-900 dark:text-slate-100 p-3 rounded-xl rounded-tl-none max-w-[96%] text-[11.5px] shadow-xs leading-relaxed font-sans space-y-2 border border-slate-200/60 dark:border-slate-700/60">
                            <!-- Main Text -->
                            <div class="whitespace-pre-wrap leading-relaxed" x-html="formatWaPreview(customPesan)"></div>
                            
                            <!-- WhatsApp Template Footer -->
                            <div class="text-[9.5px] text-slate-400 dark:text-slate-400 flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                                <span>By PT. Media Solusi Network</span>
                                <span class="text-[8.5px] text-slate-400">06:01</span>
                            </div>

                            <!-- WhatsApp Action Button (CTA) -->
                            <div class="pt-0.5">
                                <div class="w-full bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md py-1.5 px-2.5 text-center transition flex items-center justify-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold text-[11px]">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                    <span>Bayar Sekarang</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WA Preview Footer Notice -->
                    <div class="px-2.5 py-1.5 bg-slate-200/80 dark:bg-slate-800/80 border-t border-slate-300 dark:border-slate-700 text-[9.5px] text-slate-600 dark:text-slate-400 text-center">
                        🔒 Pesan template resmi terenkripsi end-to-end melalui Meta Cloud API
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- LANGKAH 2 & 3: DAFTAR PELANGGAN & PENGIRIMAN -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs p-4 sm:p-5 space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-[#128C7E] text-white font-bold text-xs flex items-center justify-center shrink-0">2</span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Pilih Target Pelanggan &amp; Eksekusi Kirim</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Centang pelanggan pada tabel, lalu klik tombol Kirim Broadcast.</p>
                </div>
            </div>

            <!-- LANGKAH 3 ACTION BUTTON -->
            <button @click="triggerBulkBroadcast()" type="button" :disabled="selectedTargets.length === 0" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-40 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition cursor-pointer">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span>3. KIRIM BROADCAST (<span x-text="selectedTargets.length">0</span> DIPILIH)</span>
            </button>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="{{ route('admin.broadcast') }}" class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700/60 space-y-2.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
                
                <!-- Filter Status Tagihan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Status Pelanggan:</label>
                    <select name="status_tagihan" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ $selectedStatusTagihan == 'all' ? 'selected' : '' }}>🌐 Semua Pelanggan</option>
                        <option value="unpaid" {{ $selectedStatusTagihan == 'unpaid' ? 'selected' : '' }}>⚠️ Belum Lunas / Jatuh Tempo</option>
                        <option value="paid" {{ $selectedStatusTagihan == 'paid' ? 'selected' : '' }}>✅ Lunas (PAID)</option>
                        <option value="isolir" {{ $selectedStatusTagihan == 'isolir' ? 'selected' : '' }}>⛔ Suspend</option>
                    </select>
                </div>

                <!-- Filter Status Pengiriman WA -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Status Kirim WA:</label>
                    <select name="status_kirim" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ ($selectedStatusKirim ?? 'all') == 'all' ? 'selected' : '' }}>Semua Status Kirim</option>
                        <option value="sent" {{ ($selectedStatusKirim ?? '') == 'sent' ? 'selected' : '' }}>✅ Sudah Terkirim</option>
                        <option value="unsent" {{ ($selectedStatusKirim ?? '') == 'unsent' ? 'selected' : '' }}>⏳ Belum Terkirim</option>
                    </select>
                </div>

                <!-- Filter Periode Bulan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Bulan Tagihan:</label>
                    <select name="bulan" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @for($m = 1; $m <= 12; $m++)
                            @php $monthVal = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                            <option value="{{ $monthVal }}" {{ $selectedBulan == $monthVal ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Filter Periode Tahun -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Tahun Tagihan:</label>
                    <select name="tahun" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ $selectedTahun == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Pencarian Pelanggan:</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama, ID, No HP..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

            </div>

            <div class="flex items-center justify-between pt-0.5 flex-wrap gap-2">
                <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 cursor-pointer">
                    <input type="checkbox" @change="toggleSelectAll($event)" class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5">
                    <span>Centang / Pilih Semua Pelanggan di Halaman Ini</span>
                </label>

                <div class="flex items-center gap-1.5">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition shadow-2xs cursor-pointer">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.broadcast') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition">
                        Reset Filter
                    </a>
                </div>
            </div>
        </form>

        <!-- Customer Table (Compact) -->
        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs bg-white dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300 border-collapse">
                    <thead class="bg-slate-100/90 dark:bg-slate-800/90 text-slate-600 dark:text-slate-300 uppercase text-[9.5px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-bold">
                        <tr>
                            <th class="py-2.5 px-2.5 w-9 text-center">Pilih</th>
                            <th class="py-2.5 px-2.5">Nama Pelanggan</th>
                            <th class="py-2.5 px-2.5">Nomor Internet &amp; HP</th>
                            <th class="py-2.5 px-2.5">Billing Terakhir</th>
                            <th class="py-2.5 px-2.5">Status Tagihan</th>
                            <th class="py-2.5 px-2.5 text-right">Nominal</th>
                            <th class="py-2.5 px-2.5 text-center">Aksi Kirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @php
                            $monthNamesIndo = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                        @endphp
                        @forelse($pelangganList as $item)
                            @php
                                $lastMInt = (int) ($item->last_month_billing ?? 0);
                                $lastYVal = trim((string)($item->last_year_billing ?? ''));
                                $hasLastBilling = ($lastMInt > 0 || !empty($lastYVal));
                                $lastMonthName = $monthNamesIndo[$lastMInt] ?? ($lastMInt > 0 ? 'Bulan ' . $lastMInt : '');
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors"
                                :class="selectedTargets.includes('{{ $item->nomor_internet }}') ? 'bg-emerald-50/60 dark:bg-emerald-950/30' : ''">
                                
                                <!-- 1. Checkbox Pilih -->
                                <td class="py-2 px-2.5 text-center align-middle">
                                    <input type="checkbox" 
                                           value="{{ $item->nomor_internet }}" 
                                           x-model="selectedTargets" 
                                           class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5 cursor-pointer">
                                </td>

                                <!-- 2. Nama Pelanggan & Paket -->
                                <td class="py-2 px-2.5 align-middle">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-tight">
                                        {{ $item->nama_pelanggan }}
                                    </div>
                                    @if($item->nama_kategori_bandwith || $item->nominal_bandwith)
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 uppercase">
                                                {{ $item->nama_kategori_bandwith ?: 'BROADBAND' }}
                                                @if($item->nominal_bandwith)
                                                    • {{ $item->nominal_bandwith }} Mbps
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- 3. Nomor Internet & HP -->
                                <td class="py-2 px-2.5 align-middle">
                                    <div class="font-mono text-[#128C7E] dark:text-emerald-400 font-bold text-xs tracking-wide">
                                        {{ $item->nomor_internet }}
                                    </div>
                                    <div class="text-[10.5px] text-slate-500 dark:text-slate-400 font-mono flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M10.5 18.75a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Z" />
                                            <path fill-rule="evenodd" d="M8.625.75A3.375 3.375 0 0 0 5.25 4.125v15.75a3.375 3.375 0 0 0 3.375 3.375h6.75a3.375 3.375 0 0 0 3.375-3.375V4.125A3.375 3.375 0 0 0 15.375.75h-6.75ZM6.75 4.125C6.75 3.09 7.59 2.25 8.625 2.25h6.75c1.035 0 1.875.84 1.875 1.875v15.75c0 1.035-.84 1.875-1.875 1.875h-6.75a1.875 1.875 0 0 1-1.875-1.875V4.125Z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $item->nomor_hp ?: '-' }}</span>
                                    </div>
                                </td>

                                <!-- 4. Last Month & Last Year Billing -->
                                <td class="py-2 px-2.5 align-middle">
                                    @if($hasLastBilling)
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[10px] font-bold font-mono shadow-2xs">
                                            <svg class="w-3 h-3 text-indigo-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                            </svg>
                                            <span>{{ $lastMonthName }} {{ $lastYVal }}</span>
                                        </div>
                                    @else
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[9.5px] font-medium bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                            -
                                        </span>
                                    @endif
                                </td>

                                <!-- 5. Status Tagihan -->
                                <td class="py-2 px-2.5 align-middle">
                                    @if($item->status_bill_lay == '15')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30">
                                            <svg class="w-2.5 h-2.5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>LUNAS</span>
                                        </span>
                                    @elseif(in_array($item->status_bill_lay, ['13', '14']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30">
                                            <svg class="w-2.5 h-2.5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                            </svg>
                                            <span>JATUH TEMPO</span>
                                        </span>
                                    @elseif(in_array($item->status_bill_lay, ['11', '12']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/15 dark:text-blue-400 dark:border-blue-500/30">
                                            <span>DRAFT</span>
                                        </span>
                                    @elseif(in_array($item->status_reg, ['23', '23.1']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30">
                                            <svg class="w-2.5 h-2.5 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <span>SUSPEND</span>
                                        </span>
                                    @elseif(in_array($item->status_reg, ['14', '15', '17', '18', '19', '19.1']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-400 dark:border-indigo-500/30">
                                            <span>PROSES PASANG</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30">
                                            <svg class="w-2.5 h-2.5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span>LUNAS</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- 6. Nominal -->
                                <td class="py-2 px-2.5 text-right font-bold text-slate-900 dark:text-slate-100 font-mono text-xs align-middle">
                                    Rp {{ number_format((float) ($item->total_layanan ?? ($item->harga_bandwith ?? 0)), 0, ',', '.') }}
                                </td>

                                <!-- 7. Aksi Kirim -->
                                <td class="py-2 px-2.5 text-center align-middle">
                                    <div class="inline-flex flex-col items-center justify-center gap-1">
                                        @if(($item->wa_sent_count ?? 0) > 0)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30 shadow-2xs"
                                                  title="Pernah dikirim WhatsApp sebanyak {{ $item->wa_sent_count }} kali{{ $item->last_sent_at ? ' (Terakhir: ' . \Carbon\Carbon::parse($item->last_sent_at)->translatedFormat('d M Y H:i') . ')' : '' }}">
                                                <svg class="w-2.5 h-2.5 text-blue-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $item->wa_sent_count }}x Kirim</span>
                                            </span>
                                        @endif
                                        <button @click="openSingleSendModal('{{ $item->nomor_internet }}', '{{ addslashes($item->nama_pelanggan) }}', '{{ $item->nomor_hp }}')" 
                                                type="button" 
                                                title="Kirim WA ke Pelanggan Ini" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-[#128C7E] text-[#075E54] hover:text-white border border-emerald-200 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30 text-[11px] font-bold transition shadow-2xs cursor-pointer">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                            </svg>
                                            <span>Kirim WA</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                                    Tidak ada data pelanggan yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Compact) -->
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-xs">
                {{ $pelangganList->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Single Send Confirmation -->
    <div x-show="showSingleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="if(!isSingleSending) showSingleModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">📱</span>
                    <span>Kirim Broadcast WA Per Orangan</span>
                </h3>
                <button @click="if(!isSingleSending) showSingleModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <div class="space-y-3 text-xs text-slate-700 dark:text-slate-300">
                <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-xl border border-slate-200 dark:border-slate-700/60">
                    <div class="text-slate-500 dark:text-slate-400">Penerima Pesan:</div>
                    <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="singleTarget.nama"></div>
                    <div class="text-emerald-600 dark:text-emerald-400 font-mono text-[11px]" x-text="'ID: ' + singleTarget.noInternet + ' | HP: ' + singleTarget.hp"></div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pratinjau Pesan Template:</label>
                    <div class="bg-[#efeae2] dark:bg-[#0b141a] p-3 rounded-xl border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-slate-100 whitespace-pre-wrap max-h-40 overflow-y-auto font-sans leading-relaxed text-[11px]" x-text="singleTarget.renderedPesan"></div>
                </div>

                <!-- Pilihan Metode Pengiriman Single -->
                <div class="pt-1">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Metode Pengiriman:</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer" :class="singleTarget.metode === 'meta_api' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 font-bold dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700'">
                            <input type="radio" value="meta_api" x-model="singleTarget.metode">
                            <span>🚀 Meta Cloud API</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer" :class="singleTarget.metode === 'wa_web' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 font-bold dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700'">
                            <input type="radio" value="wa_web" x-model="singleTarget.metode">
                            <span>🌐 WhatsApp Web</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button @click="showSingleModal = false" :disabled="isSingleSending" type="button" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold disabled:opacity-50">
                    Batal
                </button>
                <button @click="submitSingleSend()" :disabled="isSingleSending" type="button" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                    <span x-show="isSingleSending" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span x-text="isSingleSending ? 'Mengirim...' : (singleTarget.metode === 'meta_api' ? 'Kirim via Meta API Sekarang' : 'Buka WhatsApp Web')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Queue Dispatcher (Meta Cloud API & WhatsApp Web) -->
    <div x-show="showBulkModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-5" @click.away="if(!isSendingBulk) showBulkModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">🚀</span>
                    <span>Dispatcher Broadcast Massal WhatsApp</span>
                </h3>
                <button @click="if(!isSendingBulk) showBulkModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg">&times;</button>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-slate-700 dark:text-slate-300">Kemajuan Pengiriman:</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-mono" x-text="bulkProgressPercent + '% (' + bulkSentCount + '/' + bulkQueue.length + ' Selesai)'"></span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full transition-all duration-300" :style="'width: ' + bulkProgressPercent + '%'"></div>
                </div>
            </div>

            <!-- Active Queue Table -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] uppercase font-bold sticky top-0">
                        <tr>
                            <th class="p-2.5">No</th>
                            <th class="p-2.5">Pelanggan</th>
                            <th class="p-2.5">No HP</th>
                            <th class="p-2.5 text-center">Status</th>
                            <th class="p-2.5 text-right">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="(item, idx) in bulkQueue" :key="idx">
                            <tr :class="idx === currentBulkIndex ? 'bg-emerald-50/60 dark:bg-emerald-500/10 font-semibold' : ''">
                                <td class="p-2.5 text-slate-500 dark:text-slate-400 font-mono text-[11px]" x-text="idx + 1"></td>
                                <td class="p-2.5 font-bold text-slate-900 dark:text-white" x-text="item.nama_penerima"></td>
                                <td class="p-2.5 font-mono text-emerald-600 dark:text-emerald-400 text-[11px]" x-text="item.nomor_hp"></td>
                                <td class="p-2.5 text-center">
                                    <span x-show="item.status === 'sent'" class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold">✅ Terkirim</span>
                                    <span x-show="item.status === 'failed'" class="px-2 py-0.5 rounded text-[10px] bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 font-bold">❌ Gagal</span>
                                    <span x-show="item.status === 'pending'" class="px-2 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">Antrean</span>
                                    <span x-show="item.status === 'sending'" class="px-2 py-0.5 rounded text-[10px] bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 font-bold animate-pulse">Mengirim...</span>
                                </td>
                                <td class="p-2.5 text-right text-[11px] text-slate-500 dark:text-slate-400">
                                    <span x-text="item.meta_msg || (item.status === 'sent' ? 'Meta API OK' : '-')"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Modal Action Footer -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800 flex-wrap gap-2">
                <span class="text-[11px] text-slate-500 dark:text-slate-400" x-text="metodeKirim === 'meta_api' ? 'Pengiriman otomatis via Meta WhatsApp Cloud API.' : 'Pengiriman manual via WhatsApp Web.'"></span>
                
                <div class="flex items-center gap-2">
                    <button @click="showBulkModal = false" :disabled="isSendingBulk" type="button" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold disabled:opacity-50">
                        Tutup
                    </button>
                    
                    <!-- Auto Start Button for Meta API -->
                    <template x-if="metodeKirim === 'meta_api'">
                        <button @click="startAutoBulkApi()" :disabled="isSendingBulk || bulkSentCount === bulkQueue.length" type="button" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 shadow-lg shadow-emerald-600/30 disabled:opacity-50 cursor-pointer">
                            <span x-show="isSendingBulk" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span x-text="isSendingBulk ? 'Sedang Mengirim Otomatis...' : (bulkSentCount === 0 ? '🚀 Mulai Kirim Massal (Meta API)' : 'Lanjutkan Pengiriman')"></span>
                        </button>
                    </template>

                    <!-- Manual Step Button for WA Web -->
                    <template x-if="metodeKirim === 'wa_web'">
                        <button @click="openNextBulkItem()" type="button" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 shadow">
                            <span>Buka WA Berikutnya &raquo;</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function broadcastApp() {
    return {
        selectedTargets: [],
        selectedTemplateId: "{{ $defaultTemplate->id ?? '' }}",
        currentMetaName: "{{ $defaultTemplate->meta_template_name ?? 'tagihan_bulanan' }}",
        currentMetaLang: "{{ $defaultTemplate->meta_language ?? 'id' }}",
        currentMetaParamsMap: "{{ addslashes($defaultTemplate->meta_params_map ?? '[]') }}",
        customPesan: `{!! addslashes($defaultTemplate->pesan ?? '') !!}`,
        metodeKirim: "{{ $isMetaConfigured ? 'meta_api' : 'wa_web' }}",
        showSingleModal: false,
        showBulkModal: false,
        showPreview: true,
        isSingleSending: false,
        singleTarget: {
            noInternet: '',
            nama: '',
            hp: '',
            renderedPesan: '',
            metode: 'meta_api'
        },
        bulkQueue: [],
        currentBulkIndex: 0,
        bulkSentCount: 0,
        bulkProgressPercent: 0,
        isSendingBulk: false,

        get currentParamCards() {
            try {
                let params = JSON.parse(this.currentMetaParamsMap || '[]');
                if (!Array.isArray(params) || params.length === 0) {
                    if (this.currentMetaName === 'tagihan_bulanan') {
                        params = ['periode', 'bulan_jatuh_tempo', 'bulan_suspend'];
                    } else if (this.currentMetaName === 'work_report') {
                        params = ['nama', 'nomor_internet', 'alamat', 'paket'];
                    } else {
                        params = [];
                    }
                }
                const dictLabels = {
                    'periode': { label: 'Periode Tagihan', desc: 'Bulan & tahun tagihan (contoh: Oktober 2026)' },
                    'bulan_jatuh_tempo': { label: 'Bulan Jatuh Tempo', desc: 'Menjadi: 20 Oktober 2026' },
                    'bulan_suspend': { label: 'Bulan Suspend', desc: 'Menjadi: 24 Oktober 2026' },
                    'nama': { label: 'Nama Pelanggan', desc: 'Diambil dari data pelanggan' },
                    'nomor_internet': { label: 'Nomor Internet / ID', desc: 'Nomor internet pelanggan' },
                    'alamat': { label: 'Alamat Pasang', desc: 'Alamat domisili/pemasangan' },
                    'paket': { label: 'Paket Layanan', desc: 'Nama kategori bandwith' },
                    'nominal': { label: 'Nominal Tagihan', desc: 'Total tagihan layanan (Rp)' },
                    'jatuh_tempo': { label: 'Tgl Jatuh Tempo', desc: 'Format tanggal lengkap' },
                    'link_pembayaran': { label: 'Link Pembayaran', desc: 'Portal login / link snap' }
                };
                return params.map((p, idx) => {
                    const cleanP = String(p).replace(/[{}]/g, '').trim();
                    const paramNum = '{' + '{' + (idx + 1) + '}' + '}';
                    const meta = dictLabels[cleanP] || { label: cleanP, desc: 'Parameter ' + paramNum };
                    return {
                        num: paramNum,
                        label: meta.label,
                        desc: meta.desc
                    };
                });
            } catch (e) {
                return [];
            }
        },

        init() {
            this.loadSelectedTemplate();
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedTargets = [
                    @foreach($pelangganList as $item)
                        "{{ $item->nomor_internet }}",
                    @endforeach
                ];
            } else {
                this.selectedTargets = [];
            }
        },

        loadSelectedTemplate() {
            const selectEl = document.querySelector('select[x-model="selectedTemplateId"]');
            if (selectEl && selectEl.selectedIndex >= 0) {
                const opt = selectEl.options[selectEl.selectedIndex];
                const rawPesan = opt.getAttribute('data-pesan');
                const metaName = opt.getAttribute('data-meta-name');
                const metaLang = opt.getAttribute('data-meta-lang');
                const metaParams = opt.getAttribute('data-meta-params');
                if (rawPesan) this.customPesan = rawPesan;
                if (metaName) this.currentMetaName = metaName;
                if (metaLang) this.currentMetaLang = metaLang;
                if (metaParams) this.currentMetaParamsMap = metaParams;
            }
        },

        formatWaPreview(text) {
            if (!text) return '<i>Pratinjau pesan WhatsApp akan muncul di sini...</i>';
            let formatted = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
                .replace(/_(.*?)_/g, '<em>$1</em>')
                .replace(/~(.*?)~/g, '<del>$1</del>')
                .replace(/\n/g, '<br>');
            return formatted;
        },

        isSyncingTemplates: false,

        syncMetaTemplates() {
            this.isSyncingTemplates = true;
            fetch("{{ route('admin.broadcast.sync-templates') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(async res => {
                this.isSyncingTemplates = false;
                const data = await res.json().catch(() => ({}));
                if (data.success) {
                    alert("✅ " + data.message + "\n\nHalaman akan dimuat ulang untuk memperbarui daftar template.");
                    window.location.reload();
                } else {
                    alert("⚠️ Gagal sinkronisasi template Meta:\n" + (data.message || "Pastikan kredensial META_WA_TOKEN dan META_WA_BUSINESS_ACCOUNT_ID di .env sudah benar."));
                }
            })
            .catch(err => {
                this.isSyncingTemplates = false;
                alert("Gagal menghubungi server untuk sinkronisasi template Meta: " + (err.message || ''));
            });
        },

        testMetaApi() {
            fetch("{{ route('admin.broadcast.test-connection') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (data.success) {
                    alert("✅ " + data.message + "\n\nNomor: " + (data.data?.display_phone_number || '-') + "\nNama Bisnis: " + (data.data?.verified_name || '-'));
                } else {
                    alert("⚠️ Status Meta API:\n" + (data.message || 'Gagal tersambung ke Meta API'));
                }
            })
            .catch(err => {
                alert("Gagal melakukan tes koneksi Meta API: " + (err.message || ''));
            });
        },

        openSingleSendModal(noInternet, nama, hp) {
            this.singleTarget.noInternet = noInternet;
            this.singleTarget.nama = nama;
            this.singleTarget.hp = hp;
            this.singleTarget.metode = this.metodeKirim;
            
            fetch("{{ route('admin.broadcast.preview') }}?nomor_internet=" + encodeURIComponent(noInternet) + "&template_id=" + this.selectedTemplateId + "&pesan=" + encodeURIComponent(this.customPesan), {
                headers: {
                    "Accept": "application/json"
                }
            })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (data.success) {
                        this.singleTarget.renderedPesan = data.rendered_message;
                    } else {
                        this.singleTarget.renderedPesan = this.customPesan;
                    }
                    this.showSingleModal = true;
                })
                .catch(() => {
                    this.singleTarget.renderedPesan = this.customPesan;
                    this.showSingleModal = true;
                });
        },

        submitSingleSend() {
            this.isSingleSending = true;
            
            fetch("{{ route('admin.broadcast.send-single') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    nomor_internet: this.singleTarget.noInternet,
                    nama_penerima: this.singleTarget.nama,
                    nomor_hp: this.singleTarget.hp,
                    pesan: this.singleTarget.renderedPesan,
                    template_id: this.selectedTemplateId,
                    metode_kirim: this.singleTarget.metode,
                    kategori: "jatuh_tempo"
                })
            })
            .then(async res => {
                this.isSingleSending = false;
                const data = await res.json().catch(() => ({}));
                this.showSingleModal = false;

                if (data.success) {
                    if (data.metode_kirim === 'wa_web' && data.wa_url) {
                        window.open(data.wa_url, '_blank');
                    } else {
                        alert("✅ " + (data.message || 'Pesan berhasil dikirim!'));
                    }
                } else {
                    alert("⚠️ " + (data.message || "Gagal mengirim pesan!"));
                }
            })
            .catch(err => {
                this.isSingleSending = false;
                alert('Terjadi kesalahan koneksi saat mengirim pesan WhatsApp: ' + (err.message || ''));
            });
        },

        triggerBulkBroadcast() {
            if (this.selectedTargets.length === 0) {
                alert('Pilih setidaknya 1 pelanggan pada tabel untuk broadcast!');
                return;
            }

            fetch("{{ route('admin.broadcast.send-bulk') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    targets: this.selectedTargets,
                    pesan: this.customPesan,
                    template_id: this.selectedTemplateId,
                    metode_kirim: this.metodeKirim,
                    kategori: "jatuh_tempo"
                })
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (data.success && data.queue) {
                    this.bulkQueue = data.queue.map(q => ({
                        ...q,
                        status: 'pending',
                        meta_msg: ''
                    }));
                    this.currentBulkIndex = 0;
                    this.bulkSentCount = 0;
                    this.bulkProgressPercent = 0;
                    this.showBulkModal = true;
                } else {
                    alert("⚠️ " + (data.message || 'Gagal menyiapkan antrean broadcast!'));
                }
            })
            .catch(err => {
                alert('Terjadi kesalahan saat memproses antrean broadcast massal: ' + (err.message || ''));
            });
        },

        async startAutoBulkApi() {
            if (this.isSendingBulk) return;
            this.isSendingBulk = true;

            for (let i = 0; i < this.bulkQueue.length; i++) {
                if (this.bulkQueue[i].status === 'sent') continue;

                this.currentBulkIndex = i;
                this.bulkQueue[i].status = 'sending';

                try {
                    const item = this.bulkQueue[i];
                    const response = await fetch("{{ route('admin.broadcast.send-api-item') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            kode_broadcast: item.kode_broadcast,
                            nomor_internet: item.nomor_internet,
                            nama_penerima: item.nama_penerima,
                            nomor_hp: item.nomor_hp,
                            pesan: item.pesan,
                            meta_template_name: item.meta_template_name,
                            meta_language: item.meta_language,
                            meta_parameters: item.meta_parameters,
                            kategori: item.kategori
                        })
                    });

                    const resData = await response.json();
                    if (resData.success) {
                        this.bulkQueue[i].status = 'sent';
                        this.bulkQueue[i].meta_msg = 'Meta Message ID: ' + (resData.meta_message_id?.substring(0, 12) || 'OK') + '...';
                    } else {
                        this.bulkQueue[i].status = 'failed';
                        this.bulkQueue[i].meta_msg = resData.message || 'Gagal API';
                    }
                } catch (e) {
                    this.bulkQueue[i].status = 'failed';
                    this.bulkQueue[i].meta_msg = 'Error koneksi server';
                }

                this.updateBulkProgress();
                // Delay 500ms between requests for smooth dispatching
                await new Promise(r => setTimeout(r, 500));
            }

            this.isSendingBulk = false;
        },

        updateBulkProgress() {
            const completed = this.bulkQueue.filter(q => q.status === 'sent' || q.status === 'failed').length;
            this.bulkSentCount = completed;
            this.bulkProgressPercent = Math.round((completed / this.bulkQueue.length) * 100);
        },

        openNextBulkItem() {
            if (this.currentBulkIndex < this.bulkQueue.length) {
                const item = this.bulkQueue[this.currentBulkIndex];
                item.status = 'sent';
                window.open(item.wa_url, '_blank');
                
                this.currentBulkIndex++;
                this.updateBulkProgress();
            }
        }
    };
}
</script>
@endsection
