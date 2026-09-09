@extends('layouts.app')

@section('content')
    @php /** @var \Illuminate\Pagination\LengthAwarePaginator $rows */ @endphp

    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('tiket') }}" class="hover:text-blue-600 transition-colors">IMS</a>
        <span class="mx-2 text-gray-300">></span>
        <span class="text-gray-700 font-medium">Coverage</span>
    </nav>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-6 uppercase tracking-wide">Tiket Pengecekan Tikor Jaringan</h2>

        <div class="flex flex-wrap items-center gap-4 mb-6">
            <div class="relative">
                <select class="appearance-none bg-white border-b-2 border-gray-200 focus:border-cyan-400 text-gray-700 py-2 pl-3 pr-8 text-sm font-semibold uppercase tracking-wide outline-none transition-colors cursor-pointer">
                    @foreach (['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'] as $i => $m)
                        <option {{ $i === 6 ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500"><i class="fa-solid fa-chevron-down text-xs"></i></div>
            </div>
            <div class="relative">
                <select class="appearance-none bg-white border-b-2 border-gray-200 focus:border-cyan-400 text-gray-700 py-2 pl-3 pr-8 text-sm font-semibold uppercase tracking-wide outline-none transition-colors cursor-pointer">
                    @foreach ([2024,2025,2026,2027] as $y)<option {{ $y === 2026 ? 'selected' : '' }}>{{ $y }}</option>@endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500"><i class="fa-solid fa-chevron-down text-xs"></i></div>
            </div>
            <div class="relative">
                <select class="appearance-none bg-white border-b-2 border-gray-200 focus:border-cyan-400 text-gray-700 py-2 pl-3 pr-8 text-sm font-semibold uppercase tracking-wide outline-none transition-colors cursor-pointer">
                    <option selected>ANTRIAN</option><option>PROSES</option><option>SELESAI</option><option>DITUTUP</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500"><i class="fa-solid fa-chevron-down text-xs"></i></div>
            </div>
            <div class="relative">
                <select class="appearance-none bg-white border-b-2 border-gray-200 focus:border-cyan-400 text-gray-700 py-2 pl-3 pr-8 text-sm font-semibold uppercase tracking-wide outline-none transition-colors cursor-pointer">
                    <option selected>SEMUA GROUP</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500"><i class="fa-solid fa-chevron-down text-xs"></i></div>
            </div>
            <div class="flex items-center gap-2">
                <button class="bg-cyan-400 hover:bg-cyan-500 text-white px-5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-md shadow-cyan-200/50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"><i class="fa-solid fa-rotate"></i>Find</button>
                <button class="bg-pink-400 hover:bg-pink-500 text-white px-5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-md shadow-pink-200/50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"><i class="fa-solid fa-rotate"></i>Reset</button>
                <button class="bg-amber-400 hover:bg-amber-500 text-white px-5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-md shadow-amber-200/50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"><i class="fa-solid fa-file-export"></i>Export</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700 w-44">Tiket</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700 w-64">Kontak</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Lokasi</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700 w-28">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr class="group odd:bg-white even:bg-slate-50/40 hover:bg-blue-50/40 border-b border-gray-100 transition-colors duration-150">
                            <td class="relative py-4 px-4 align-top">
                                <span class="absolute left-0 top-0 h-full w-1 bg-amber-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
                                <p class="font-bold text-blue-600 text-sm break-all">{{ $r->id_message }}</p>
                                <p class="text-[11px] text-gray-400 mt-1">{{ $r->date_create }}</p>
                            </td>
                            <td class="py-4 px-4 align-top">
                                <p class="text-sm font-bold text-gray-800">{{ $r->pushname ?: '-' }}</p>
                                @if($r->nomor_hp)<p class="text-xs text-gray-600 mt-0.5"><i class="fa-solid fa-phone text-[10px] text-gray-400 mr-1"></i>{{ $r->nomor_hp }}</p>@endif
                                @if($r->group_wa)<p class="text-xs text-gray-400 mt-0.5"><i class="fa-brands fa-whatsapp text-[10px] mr-1"></i>{{ $r->group_wa }}</p>@endif
                            </td>
                            <td class="py-4 px-4 align-top">
                                @if($r->latitude || $r->longitude)
                                    <p class="text-xs font-mono text-gray-700">{{ $r->latitude }}, {{ $r->longitude }}</p>
                                @endif
                                @if($r->jarak)<p class="text-xs text-gray-500 mt-1">Jarak : <span class="font-semibold text-gray-700">{{ $r->jarak }}</span></p>@endif
                                @if($r->note)<p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $r->note }}</p>@endif
                            </td>
                            <td class="py-4 px-4 align-top">
                                @if($r->status)
                                    <span class="inline-block bg-violet-100 text-violet-600 text-[11px] font-semibold px-2.5 py-1 rounded">{{ $r->status }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200/60 flex items-center justify-center mb-4 shadow-inner">
                                        <i class="fa-solid fa-map-location-dot text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-500">Tidak ada permintaan coverage</p>
                                    <p class="text-xs text-gray-400 mt-1">Data terisi otomatis dari <span class="font-mono text-gray-500">trx_coverage_area</span> begitu tersedia.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->total())
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-4 gap-3">
                <div class="text-sm text-gray-500">Showing {{ $rows->firstItem() }} to {{ $rows->lastItem() }} of {{ $rows->total() }} entries</div>
                @include('partials.pagination', ['rows' => $rows])
            </div>
        @endif
    </div>
@endsection