@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-cyan-50 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-500/20 mb-2">
                <span class="w-2 h-2 rounded-full bg-cyan-500 animate-ping"></span>
                <span class="font-mono uppercase tracking-wider font-bold">Multi-Key Telemetry Engine</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Monitoring AI & Kuota Multi-Akun</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-mono">
                    {{ $metrics['total_keys'] }} AKUN GEMINI
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Pantau sisa kuota harian (RPD), konsumsi token input/output, rotasi failover multi-akun, dan latensi Google AI Studio.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="window.location.reload()" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition border border-slate-300 dark:border-slate-700">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Segarkan</span>
            </button>
            <button type="button" onclick="resetMetrics()" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-400 text-xs font-semibold transition border border-rose-200 dark:border-rose-800/40">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3" />
                </svg>
                <span>Reset Cache Hari Ini</span>
            </button>
        </div>
    </div>

    <!-- Top Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Pool Kuota Request (RPD) -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Pool Kuota Request</span>
                <span class="p-2 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                    {{ number_format($metrics['total_requests_remaining']) }}
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                    / {{ number_format($metrics['total_pool_rpd']) }} RPD
                </span>
            </div>
            <div class="mt-3">
                <div class="flex justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400 mb-1.5">
                    <span>Terpakai: {{ number_format($metrics['total_requests_used']) }}</span>
                    <span>{{ $metrics['percent_used'] }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-500 to-emerald-500 h-2 rounded-full transition-all duration-500" 
                         style="width: {{ $metrics['percent_used'] }}%"></div>
                </div>
            </div>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-2.5 font-medium flex items-center gap-1">
                <span>●</span> <span>Kapasitas pool gabungan {{ $metrics['total_keys'] }} akun</span>
            </p>
        </div>

        <!-- 2. Konsumsi Token Hari Ini -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Token Hari Ini</span>
                <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                    {{ number_format($metrics['total_tokens']) }}
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">tokens</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between text-[11px] font-mono">
                <div class="text-slate-500 dark:text-slate-400">
                    <span>Input:</span> <span class="text-cyan-600 dark:text-cyan-400 font-bold">{{ number_format($metrics['prompt_tokens']) }}</span>
                </div>
                <div class="text-slate-500 dark:text-slate-400">
                    <span>Output:</span> <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ number_format($metrics['candidates_tokens']) }}</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">Dihitung otomatis via Gemini usageMetadata</p>
        </div>

        <!-- 3. Model & Kunci Terhubung -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Model AI Terpasang</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                    </svg>
                </span>
            </div>
            <div>
                <span class="text-xl font-bold font-mono text-cyan-600 dark:text-cyan-400">
                    {{ $metrics['model'] }}
                </span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span class="text-slate-500 dark:text-slate-400">Rotasi Multi-Akun:</span>
                <span class="px-2 py-0.5 rounded-full font-bold font-mono text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    ON (AUTO-FAILOVER)
                </span>
            </div>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">Beralih otomatis jika akun terkena limit 429</p>
        </div>

        <!-- 4. Reset Kuota Google Harian -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Jadwal Reset Kuota Google</span>
                <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div>
                <span class="text-2xl font-bold font-mono text-slate-900 dark:text-white">
                    {{ $metrics['reset_countdown'] }}
                </span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                <span>Reset Berikutnya:</span>
                <span class="text-amber-500 font-bold">07:00 WIB (00:00 UTC)</span>
            </div>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">Google me-refresh 1.500 RPD setiap tengah malam UTC</p>
        </div>
    </div>

    <!-- Individual Key Cards Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
                <span>Daftar Akun & Status Kunci Gemini (Pool Breakdown)</span>
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                Total: {{ count($metrics['keys']) }} Akun Terkonfigurasi
            </span>
        </div>

        @if(empty($metrics['keys']))
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 p-6 rounded-2xl text-center">
                <svg class="w-12 h-12 text-amber-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <h3 class="text-base font-bold text-amber-900 dark:text-amber-200 mb-1">Belum Ada GEMINI_API_KEY Terpasang</h3>
                <p class="text-xs text-amber-700 dark:text-amber-300 max-w-md mx-auto">
                    Tambahkan satu atau beberapa API key dipisahkan koma di file <code class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded font-mono">.env</code>: <code class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded font-mono">GEMINI_API_KEY=key1,key2,key3,key4</code>
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($metrics['keys'] as $key)
                <div class="bg-white dark:bg-slate-900 rounded-2xl border {{ $key['status'] === 'active' ? 'border-cyan-500/50 shadow-md shadow-cyan-500/5' : ($key['status'] === 'exhausted' ? 'border-rose-500/50' : 'border-slate-200 dark:border-slate-800') }} p-5 flex flex-col justify-between transition-all hover:border-cyan-500/40">
                    <div>
                        <!-- Header Key -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono {{ $key['index'] === 1 ? 'bg-cyan-500/10 text-cyan-500 border border-cyan-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                {{ $key['account_name'] }}
                            </span>
                            
                            @if($key['status'] === 'active')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>AKTIF</span>
                                </span>
                            @elseif($key['status'] === 'exhausted')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    <span>KUOTA HABIS</span>
                                </span>
                            @elseif($key['status'] === 'error')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                    <span>TERKENDALA</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/20 text-slate-400 border border-slate-500/30">
                                    <span>STANDBY</span>
                                </span>
                            @endif
                        </div>

                        <!-- Masked Key -->
                        <div class="bg-slate-50 dark:bg-slate-950/70 rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-800/80 mb-3 flex items-center justify-between">
                            <span class="text-xs font-mono text-slate-700 dark:text-slate-300 font-semibold tracking-wider">
                                {{ $key['masked'] }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">ID: {{ $key['key_id'] }}</span>
                        </div>

                        <!-- Usage Progress Bar -->
                        <div class="space-y-1.5 mb-4">
                            <div class="flex justify-between text-xs font-mono">
                                <span class="text-slate-500 dark:text-slate-400">Request:</span>
                                <span class="font-bold text-slate-900 dark:text-white">
                                    {{ number_format($key['requests_used']) }} / {{ number_format($key['limit_rpd']) }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all duration-500 {{ $key['percent_used'] > 90 ? 'bg-rose-500' : 'bg-cyan-500' }}" 
                                     style="width: {{ $key['percent_used'] }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-mono text-slate-400">
                                <span>Sisa: {{ number_format($key['requests_remaining']) }} RPD</span>
                                <span>{{ $key['percent_used'] }}%</span>
                            </div>
                        </div>

                        <!-- Token Breakdown -->
                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl p-2.5 border border-slate-100 dark:border-slate-800 space-y-1 text-[11px] font-mono">
                            <div class="flex justify-between text-slate-500 dark:text-slate-400">
                                <span>Input Tokens:</span>
                                <span class="text-cyan-600 dark:text-cyan-400 font-bold">{{ number_format($key['prompt_tokens']) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-500 dark:text-slate-400">
                                <span>Output Tokens:</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ number_format($key['candidates_tokens']) }}</span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700/60 font-bold text-slate-900 dark:text-white">
                                <span>Total Tokens:</span>
                                <span>{{ number_format($key['total_tokens']) }}</span>
                            </div>
                        </div>

                        @if(!empty($key['last_error']))
                            <div class="mt-2.5 p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-[10px] font-mono text-rose-400">
                                ⚠️ {{ $key['last_error'] }}
                            </div>
                        @endif

                        <div class="mt-2 text-[10px] text-slate-400 font-mono">
                            Aktivitas: {{ $key['last_used_at'] }}
                        </div>
                    </div>

                    <!-- Action Ping -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" onclick="testSingleKey({{ $key['index'] }})" 
                                class="w-full py-2 rounded-xl bg-cyan-600/10 hover:bg-cyan-600/20 text-cyan-600 dark:text-cyan-400 text-xs font-bold font-mono transition border border-cyan-500/20 flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                            </svg>
                            <span>Ping Tes Akun #{{ $key['index'] }}</span>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Live AI Diagnostic & Ping Console -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                    <span>Konsol Diagnostik & Tes Latensi Live</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Kirim prompt langsung untuk menguji respon Gemini API, latensi milidetik (ms), dan konsumsi token secara instan.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pilih Kunci:</span>
                <select id="pingKeySelect" class="bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 cursor-pointer">
                    @foreach($metrics['keys'] as $key)
                        <option value="{{ $key['index'] }}">{{ $key['account_name'] }} ({{ $key['masked'] }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row gap-2.5">
                <input type="text" id="pingPromptInput" 
                       value="Konfirmasi koneksi AI Gemini IMS2 aktif. Berikan respon 1 kalimat padat."
                       placeholder="Masukkan teks prompt uji coba..."
                       class="flex-1 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 font-mono">
                <button type="button" id="btnSendPing" onclick="executePing()" 
                        class="px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2 cursor-pointer">
                    <svg id="pingSpinner" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="pingBtnText">Kirim Tes Ping</span>
                </button>
            </div>

            <!-- Quick Preset Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-medium text-slate-400">Preset Cepat:</span>
                <button type="button" onclick="setPreset('Ping latency test')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-[11px] font-mono text-slate-600 dark:text-slate-300 transition">Ping latency</button>
                <button type="button" onclick="setPreset('Berapa 1024 dikali 8? Beri angka saja.')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-[11px] font-mono text-slate-600 dark:text-slate-300 transition">Kalkulasi Cepat</button>
                <button type="button" onclick="setPreset('Sebutkan 3 tips optimasi jaringan fiber optik FTTH.')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-[11px] font-mono text-slate-600 dark:text-slate-300 transition">Tips FTTH</button>
            </div>

            <!-- Ping Output Console -->
            <div id="pingResultCard" class="hidden mt-4 bg-slate-950 rounded-xl p-4 border border-slate-800 font-mono text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <span id="pingStatusBadge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            200 OK
                        </span>
                        <span id="pingAccountBadge" class="text-slate-400">Akun #1</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <span>Latensi: <strong id="pingLatency" class="text-cyan-400">0 ms</strong></span>
                        <span>Token Input: <strong id="pingPromptTokens" class="text-indigo-400">0</strong></span>
                        <span>Token Output: <strong id="pingCandTokens" class="text-emerald-400">0</strong></span>
                    </div>
                </div>
                <div class="text-slate-300 leading-relaxed whitespace-pre-wrap" id="pingReplyText">
                    Hasil respon AI akan tampil di sini...
                </div>
            </div>
        </div>
    </div>

    <!-- Info & Setup Guide Card -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-950 rounded-2xl border border-slate-800 p-6 text-white relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                    ARSITEKTUR MULTI-KEY IMS2
                </div>
                <h3 class="text-base font-bold text-white">Cara Menambah atau Mengganti Akun Gemini</h3>
                <p class="text-xs text-slate-400 max-w-2xl leading-relaxed">
                    Sistem IMS2 mendukung 1 hingga banyak akun Google AI Studio sekaligus secara bersamaan. Jika salah satu akun mencapai batas kuota harian (HTTP 429), sistem secara otomatis mengalihkan permintaan ke akun berikutnya tanpa gangguan bagi pengguna.
                </p>
            </div>
            <div class="bg-slate-900/90 rounded-xl p-3 border border-slate-800 font-mono text-[11px] text-cyan-300 whitespace-nowrap">
                GEMINI_API_KEY=key1,key2,key3,key4...
            </div>
        </div>
    </div>
</div>

<script>
function setPreset(text) {
    document.getElementById('pingPromptInput').value = text;
}

function testSingleKey(keyIndex) {
    document.getElementById('pingKeySelect').value = keyIndex;
    executePing();
}

async function executePing() {
    const keyIndex = document.getElementById('pingKeySelect').value;
    const prompt = document.getElementById('pingPromptInput').value.trim();
    if (!prompt) return;

    const btn = document.getElementById('btnSendPing');
    const spinner = document.getElementById('pingSpinner');
    const btnText = document.getElementById('pingBtnText');
    const resultCard = document.getElementById('pingResultCard');
    const replyText = document.getElementById('pingReplyText');
    const statusBadge = document.getElementById('pingStatusBadge');
    const accountBadge = document.getElementById('pingAccountBadge');
    const latencyEl = document.getElementById('pingLatency');
    const pTokensEl = document.getElementById('pingPromptTokens');
    const cTokensEl = document.getElementById('pingCandTokens');

    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.innerText = 'Mengirim...';

    try {
        const response = await fetch("{{ route('admin.ai-monitor.ping') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                key_index: keyIndex,
                prompt: prompt
            })
        });

        const data = await response.json();
        resultCard.classList.remove('hidden');

        if (response.ok && data.success) {
            statusBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
            statusBadge.innerText = '200 OK SUCCESS';
            accountBadge.innerText = 'Akun #' + data.key_index;
            latencyEl.innerText = data.latency_ms + ' ms';
            pTokensEl.innerText = data.tokens?.prompt || 0;
            cTokensEl.innerText = data.tokens?.candidates || 0;
            replyText.innerText = data.reply || '(Respon kosong)';
            setTimeout(() => { window.location.reload(); }, 1500);
        } else {
            statusBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30';
            statusBadge.innerText = 'ERROR ' + (data.status_code || 500);
            accountBadge.innerText = 'Akun #' + (data.key_index || keyIndex);
            latencyEl.innerText = (data.latency_ms || 0) + ' ms';
            pTokensEl.innerText = '0';
            cTokensEl.innerText = '0';
            replyText.innerText = 'Gagal menghubungi Gemini API:\n' + (data.error || data.message || 'Unknown error');
        }
    } catch (err) {
        resultCard.classList.remove('hidden');
        statusBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30';
        statusBadge.innerText = 'NETWORK ERROR';
        replyText.innerText = 'Kesalahan jaringan: ' + err.message;
    } finally {
        btn.disabled = false;
        spinner.classList.add('hidden');
        btnText.innerText = 'Kirim Tes Ping';
    }
}

async function resetMetrics() {
    if (!confirm('Apakah Anda yakin ingin mereset cache metrik kuota dan token AI hari ini?')) return;
    try {
        const res = await fetch("{{ route('admin.ai-monitor.reset') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        });
        const data = await res.json();
        alert(data.message || 'Cache berhasil direset.');
        window.location.reload();
    } catch (e) {
        alert('Gagal mereset cache: ' + e.message);
    }
}
</script>
@endsection
