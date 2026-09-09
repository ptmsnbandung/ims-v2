@extends('layouts.app', ['title' => 'Tiket - IMS Router'])

@section('page_title', 'Tiket')

@section('content')
<div class="space-y-6">
    <!-- Header with Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-2">
        <h2 class="text-xl font-bold text-white tracking-tight">Tiket</h2>
        <nav class="flex items-center gap-1.5 text-xs text-slate-400">
            <span class="hover:text-slate-200 transition">IMS</span>
            <span class="text-slate-600">&gt;</span>
            <span class="text-blue-400 font-medium">Tiket</span>
        </nav>
    </div>

    <!-- Ticket Category Cards Grid (Sesuai Referensi) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
        
        <!-- 1. Gangguan Layanan (Blue Card) -->
        <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Gangguan Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['gangguan'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Users / Headset Group Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2. Ubah Password (Pink / Coral Red Card) -->
        <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-500/20 hover:shadow-xl hover:shadow-rose-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Ubah Password
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['ubah_password'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Clock / Time Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 3. Cek Coverage Area (Amber / Yellow Gold Card) -->
        <a href="{{ route('teknik.coverage') }}"
           class="block relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-lg shadow-amber-500/20 hover:shadow-xl hover:shadow-amber-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Cek Coverage Area
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['coverage'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Target Map Marker Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- 4. Terminasi (Blue Card) -->
        <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Terminasi
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['terminasi'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Users Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 5. Suspend Layanan (Pink / Coral Red Card) -->
        <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-500/20 hover:shadow-xl hover:shadow-rose-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Suspend Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['suspend'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Clock Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 6. Pemasangan Baru (Amber / Yellow Gold Card) -->
        <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-lg shadow-amber-500/20 hover:shadow-xl hover:shadow-amber-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Pemasangan Baru
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['pemasangan_baru'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Wallet / Map Card Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75-10.5h19.5a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H3a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 7. Ubah Layanan (Cyan Card) -->
        <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-cyan-500 to-teal-500 text-white shadow-lg shadow-cyan-500/20 hover:shadow-xl hover:shadow-cyan-500/30 hover:scale-[1.01] transition-all duration-200 cursor-pointer group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold tracking-tight text-white group-hover:translate-x-0.5 transition-transform">
                        Ubah Layanan
                    </h3>
                    <p class="mt-1.5 text-xs sm:text-sm font-medium text-white/80">
                        {{ $counts['ubah_layanan'] ?? 0 }} Tiket
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center flex-shrink-0 text-white shadow-inner group-hover:scale-110 transition-transform">
                    <!-- Speedometer / Gauge Icon -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a5 5 0 0 1-5.84 7.38v-4.8m5.84-2.58a5 5 0 0 0-7.38-5.84l3.4 3.4" />
                    </svg>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
