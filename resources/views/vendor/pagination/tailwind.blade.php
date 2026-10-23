@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-between gap-3 flex-wrap">
        {{-- Mobile View: Ringkas & Bersih --}}
        <div class="flex items-center justify-between w-full sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-800/50 cursor-not-allowed select-none border border-slate-200/60 dark:border-slate-800">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition active:scale-95">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 font-mono">
                Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition active:scale-95">
                    <span>Berikutnya</span>
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-800/50 cursor-not-allowed select-none border border-slate-200/60 dark:border-slate-800">
                    <span>Berikutnya</span>
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            @endif
        </div>

        {{-- Desktop & Tablet View --}}
        <div class="hidden sm:flex sm:items-center sm:justify-between w-full gap-3">
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Menampilkan
                    <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ $paginator->firstItem() ?? 0 }}</span>
                    sampai
                    <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ $paginator->lastItem() ?? 0 }}</span>
                    dari
                    <span class="font-bold text-[#0891b2] dark:text-cyan-400 font-mono">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            <div>
                <ul class="inline-flex items-center gap-1 text-xs font-semibold">
                    {{-- Tombol Previous --}}
                    @if ($paginator->onFirstPage())
                        <li>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 dark:text-slate-700 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-800 cursor-not-allowed select-none" aria-disabled="true" aria-label="Sebelumnya">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                </svg>
                            </span>
                        </li>
                    @else
                        <li>
                            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition active:scale-95" aria-label="Sebelumnya">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                </svg>
                            </a>
                        </li>
                    @endif

                    {{-- Page Number Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li>
                                <span class="inline-flex items-center justify-center w-8 h-8 text-slate-400 dark:text-slate-500 font-mono select-none">{{ $element }}</span>
                            </li>
                        @endif

                        {{-- Array of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li>
                                        <span aria-current="page" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white font-bold bg-gradient-to-r from-[#0891b2] to-[#0284c7] shadow-sm font-mono border border-cyan-400/30 select-none">
                                            {{ $page }}
                                        </span>
                                    </li>
                                @else
                                    <li>
                                        <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-cyan-50 dark:hover:bg-slate-700 hover:text-[#0891b2] dark:hover:text-cyan-400 border border-slate-200 dark:border-slate-700 shadow-2xs transition active:scale-95 font-mono">
                                            {{ $page }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Tombol Next --}}
                    @if ($paginator->hasMorePages())
                        <li>
                            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition active:scale-95" aria-label="Berikutnya">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </li>
                    @else
                        <li>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 dark:text-slate-700 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-800 cursor-not-allowed select-none" aria-disabled="true" aria-label="Berikutnya">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
