<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) && $title ? ($title . ' | IMSv2 | PT MEDIA SOLUSI NETWORK') : 'IMSv2 | PT MEDIA SOLUSI NETWORK' }}</title>

    <!-- Compact Density Base Scale (~80% Browser Zoom Scale) -->
    <style>
        html {
            font-size: 13px !important;
        }
        @media (max-width: 639.98px) {
            html {
                font-size: 14.5px !important;
            }
        }
    </style>

    <!-- Anti-flicker Theme Initialization (Default to Light Mode) -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }

        /* Responsive helpers for Desktop Table vs Mobile Cards */
        @media (min-width: 768px) {
            .ims-mobile-only {
                display: none !important;
            }
            .ims-desktop-only {
                display: block !important;
            }
        }
        @media (max-width: 767.98px) {
            .ims-desktop-only {
                display: none !important;
            }
            .ims-mobile-only {
                display: block !important;
            }
        }

        /* Custom scrollbar for sidebar */
        .ims-sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .ims-sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .ims-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.2);
            border-radius: 4px;
        }
        .ims-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.4);
        }

        /* Sidebar Styling & Animation */
        .ims-sidebar {
            width: 16rem;
            background-color: #061d28 !important;
            border-right: 1px solid #0d2a38 !important;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .ims-sidebar.collapsed {
            width: 4.75rem !important;
        }

        @media (max-width: 1023.98px) {
            .ims-sidebar {
                transform: translateX(-100%);
                width: 16rem !important;
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                height: 100dvh !important;
                z-index: 60 !important;
            }
            .ims-sidebar.mobile-open {
                transform: translateX(0) !important;
            }
            html, body {
                min-height: 100% !important;
                overflow-x: hidden !important;
            }
            .ims-layout-root {
                min-height: 100vh !important;
                min-height: 100dvh !important;
                height: auto !important;
                overflow: visible !important;
            }
            .ims-main-scroll {
                height: auto !important;
                overflow: visible !important;
            }
        }

        @media (min-width: 1024px) {
            html, body {
                height: 100% !important;
                overflow: hidden !important;
            }
            .ims-layout-root {
                height: 100vh !important;
                max-height: 100vh !important;
                overflow: hidden !important;
            }
            .ims-sidebar {
                position: sticky !important;
                top: 0 !important;
                height: 100vh !important;
                max-height: 100vh !important;
                flex-shrink: 0 !important;
                align-self: flex-start !important;
                z-index: 40 !important;
            }
            .ims-main-scroll {
                height: 100vh !important;
                max-height: 100vh !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
            }
        }

        /* Nav Item Styles */
        .ims-nav-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.65rem 0.85rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #CBD5E1 !important;
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
            position: relative;
        }
        .ims-nav-item svg {
            color: #94A3B8 !important;
            transition: color 0.15s ease;
        }
        .ims-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #FFFFFF !important;
        }
        .ims-nav-item:hover svg,
        .ims-nav-item:hover .ims-nav-arrow {
            color: #FFFFFF !important;
        }
        .ims-nav-item.active {
            background: linear-gradient(135deg, #0891b2 0%, #0284c7 100%) !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 18px rgba(8, 145, 178, 0.35) !important;
        }
        .ims-nav-item.active span,
        .ims-nav-item.active svg,
        .ims-nav-item.active .ims-nav-arrow {
            color: #FFFFFF !important;
        }

        /* Sidebar Section Header & Submenus */
        .ims-sidebar .ims-section-header {
            color: #38BDF8 !important;
            font-weight: 700 !important;
        }
        .ims-sidebar .ims-section-title {
            color: #38BDF8 !important;
            font-weight: 700 !important;
        }
        .ims-sidebar .ims-submenu a {
            color: #CBD5E1 !important;
            font-weight: 500;
        }
        .ims-sidebar .ims-submenu a:hover {
            color: #FFFFFF !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }
        .ims-sidebar .ims-submenu a.active {
            background: linear-gradient(135deg, #0891b2 0%, #0284c7 100%) !important;
            color: #FFFFFF !important;
            font-weight: 600 !important;
        }
        .ims-sidebar .ims-logo-text span:last-child {
            color: #94A3B8 !important;
        }

        /* Collapsed Mode Adjustments */
        .ims-sidebar.collapsed .ims-nav-item {
            justify-content: center;
            padding: 0.65rem 0;
            width: 2.75rem;
            height: 2.75rem;
            margin: 0 auto;
        }
        .ims-sidebar.collapsed .ims-nav-text,
        .ims-sidebar.collapsed .ims-nav-arrow,
        .ims-sidebar.collapsed .ims-logo-text,
        .ims-sidebar.collapsed .ims-section-title,
        .ims-sidebar.collapsed .ims-submenu,
        .ims-sidebar.collapsed .ims-footer-full {
            display: none !important;
        }
        .ims-sidebar.collapsed .ims-section-header {
            justify-content: center !important;
            padding: 0.4rem 0 !important;
        }
        .ims-sidebar.collapsed .ims-footer-mini {
            display: block !important;
        }
        .ims-footer-mini {
            display: none;
        }

        /* Floating Tooltip in Collapsed Mode */
        .ims-nav-wrapper {
            position: relative;
        }
        .ims-tooltip {
            display: none;
            position: absolute;
            left: calc(100% + 0.75rem);
            top: 50%;
            transform: translateY(-50%);
            background-color: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #FFFFFF;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7);
            white-space: nowrap;
            z-index: 99999;
            pointer-events: none;
        }
        .ims-sidebar.collapsed .ims-nav-wrapper:hover .ims-tooltip {
            display: block;
        }
        .ims-sidebar.collapsed .ims-has-flyout:hover .ims-tooltip {
            display: none !important;
        }
        .ims-flyout-portal {
            position: fixed !important;
            z-index: 9999999 !important;
            background-color: #061d28 !important;
            border: 1px solid #0d2a38 !important;
        }

        /* ==========================================================
           DUAL THEME HARMONIZATION: LIGHT & DARK MODE
           ========================================================== */
        
        /* Light Mode High Contrast */
        html:not(.dark) body {
            background-color: #F8FAFC;
            color: #0F172A;
        }

        html:not(.dark) input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]),
        html:not(.dark) select,
        html:not(.dark) textarea {
            background-color: #FFFFFF;
            border-color: #CBD5E1;
            color: #0F172A;
        }

        html:not(.dark) input::placeholder,
        html:not(.dark) textarea::placeholder {
            color: #94A3B8;
        }
        
        /* Dark Mode High Contrast */
        html.dark body {
            background-color: #071520;
            color: #F1F5F9;
        }

        html.dark main {
            background-color: #071520;
        }

        /* Dark Mode Form Inputs & Selects */
        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]),
        html.dark select,
        html.dark textarea {
            background-color: #071926;
            border-color: #1c4969;
            color: #F1F5F9;
        }

        html.dark input::placeholder,
        html.dark textarea::placeholder {
            color: #64748B;
        }

        /* Status Badge Utilities - Bold for maximum legibility */
        span[class*="bg-amber-"],
        span[class*="bg-emerald-"],
        span[class*="bg-cyan-"],
        span[class*="bg-sky-"],
        span[class*="bg-blue-"],
        span[class*="bg-purple-"],
        span[class*="bg-rose-"] {
            font-weight: 600;
        }

        /* Primary Action Buttons Keep Bright White Text */
        .btn-primary-theme,
        button[type="submit"].bg-blue-600,
        button[type="submit"].bg-cyan-600,
        button[type="submit"].bg-[#0891b2],
        button[type="submit"].bg-gradient-to-r,
        a.bg-blue-600,
        a.bg-cyan-600,
        a.bg-teal-600,
        a.bg-[#0891b2],
        a.bg-gradient-to-r {
            color: #FFFFFF !important;
        }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-blue-600 selection:text-white bg-[#F8FAFC] dark:bg-[#071520] text-slate-800 dark:text-slate-100 transition-colors duration-200"
      x-data="{ 
          isDarkMode: localStorage.getItem('theme') === 'dark',
          toggleTheme() {
              this.isDarkMode = !this.isDarkMode;
              if (this.isDarkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('theme', 'light');
              }
          },
          sidebarCollapsed: false,
          mobileSidebarOpen: false,
          activeFlyout: null,
          flyoutTop: 0,
          flyoutTitle: '',
          flyoutItems: [],
          flyoutTimeout: null,
          toggleSidebar() {
              if (window.innerWidth < 1024) {
                  this.mobileSidebarOpen = !this.mobileSidebarOpen;
              } else {
                  this.sidebarCollapsed = !this.sidebarCollapsed;
                  if (!this.sidebarCollapsed) {
                      this.activeFlyout = null;
                  }
              }
          },
          openFlyout(el, title, items) {
              if (!this.sidebarCollapsed || window.innerWidth < 1024) return;
              if (this.flyoutTimeout) {
                  clearTimeout(this.flyoutTimeout);
                  this.flyoutTimeout = null;
              }
              const rect = el.getBoundingClientRect();
              const maxTop = window.innerHeight - 280;
              this.flyoutTop = Math.max(12, Math.min(rect.top - 6, maxTop));
              this.flyoutTitle = title;
              this.flyoutItems = items;
              this.activeFlyout = title;
          },
          closeFlyoutWithDelay() {
              if (this.flyoutTimeout) clearTimeout(this.flyoutTimeout);
              this.flyoutTimeout = setTimeout(() => {
                  this.activeFlyout = null;
              }, 180);
          },
          cancelFlyoutClose() {
              if (this.flyoutTimeout) {
                  clearTimeout(this.flyoutTimeout);
                  this.flyoutTimeout = null;
              }
          }
      }">
    <div class="min-h-full h-screen flex flex-col overflow-hidden ims-layout-root">
        <div class="flex-1 flex overflow-hidden min-h-0 h-full">
            <!-- Mobile Sidebar Backdrop -->
            <div x-show="mobileSidebarOpen"
                 x-cloak
                 @click="mobileSidebarOpen = false"
                 class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm lg:hidden transition-opacity"></div>

            <!-- Sidebar (Deep Oceanic Navy Charcoal & Cyan Highlights) -->
            <aside :class="{ 'collapsed': sidebarCollapsed, 'mobile-open': mobileSidebarOpen }"
                   class="ims-sidebar fixed inset-y-0 left-0 z-[60] lg:z-40 bg-[#061d28] border-r border-[#0d2a38] flex flex-col lg:sticky lg:top-0 lg:h-screen shrink-0 overflow-visible">
                
                <!-- Sidebar Header / Logo -->
                <div class="h-16 px-3.5 flex items-center justify-between border-b border-[#0d2a38] shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden" title="IMS-V2 Management">
                        <div class="w-9 h-9 rounded-xl bg-[#0c2f42] border border-cyan-400/30 p-1 shadow-md shadow-cyan-500/20 flex items-center justify-center flex-shrink-0">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="IMS Logo" class="w-full h-full object-contain">
                        </div>
                        <div class="ims-logo-text whitespace-nowrap">
                            <span class="font-bold text-white tracking-tight text-base">IMS<span class="text-blue-500 font-extrabold">-V2</span></span>
                            <span class="block text-[9.5px] text-[#94A3B8] tracking-normal font-medium leading-tight">Integrated Management System</span>
                        </div>
                    </a>

                    <!-- Mobile Close Button -->
                    <button type="button" 
                            @click="mobileSidebarOpen = false"
                            class="lg:hidden p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition cursor-pointer"
                            title="Tutup Menu">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Sidebar Navigation Menu -->
                <nav class="ims-sidebar-nav flex-1 min-h-0 px-2.5 py-3 space-y-1.5 overflow-y-auto overflow-x-visible"
                     x-data="{
                         permintaanOpen: {{ request()->routeIs('teknik.permintaan.*') ? 'true' : 'false' }}
                     }">
                    
                    <!-- 1. Dashboard (All Users) -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('dashboard') }}"
                           class="ims-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           title="Dashboard">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#94A3B8]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                            <span class="ims-nav-text">Dashboard</span>
                        </a>
                        <div class="ims-tooltip">Dashboard</div>
                    </div>

                    @if(auth()->user()?->hasRole(['teknik', 'direktur', 'admin']))
                    <!-- ============================================== -->
                    <!-- TEKNIK SECTION                                 -->
                    <!-- ============================================== -->
                    <div class="pt-2.5 pb-1">
                        <div class="ims-section-header px-3 text-[10px] font-bold text-[#38BDF8] uppercase tracking-wider flex items-center justify-between">
                            <span class="ims-section-title">Modul Teknik</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- 1. Data Pelanggan -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('teknik.pelanggan') }}"
                           class="ims-nav-item {{ request()->routeIs('teknik.pelanggan*') ? 'active' : '' }}"
                           title="Data Pelanggan">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.pelanggan*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span class="ims-nav-text">Data Pelanggan</span>
                        </a>
                        <div class="ims-tooltip">Data Pelanggan</div>
                    </div>

                    @if(!auth()->user() || !auth()->user()->isNoc())
                        <!-- 2. Pendaftaran -->
                        <div class="ims-nav-wrapper">
                            <a href="{{ route('teknik.pendaftaran') }}"
                               class="ims-nav-item {{ request()->routeIs('teknik.pendaftaran*') ? 'active' : '' }}"
                               title="Pendaftaran / Registrasi">
                                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.pendaftaran*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                <span class="ims-nav-text">Pendaftaran</span>
                            </a>
                            <div class="ims-tooltip">Pendaftaran</div>
                        </div>
                    @endif

                    <!-- 3. Tiket -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('teknik.tiket') }}"
                           class="ims-nav-item {{ request()->routeIs('teknik.tiket*') ? 'active' : '' }}"
                           title="Tiket & Permintaan">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.tiket*') ? 'text-white' : 'text-[#94A3B8]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                            </svg>
                            <span class="ims-nav-text">Tiket</span>
                        </a>
                        <div class="ims-tooltip">Tiket & Permintaan</div>
                    </div>

                    <!-- 5. Permintaan -->
                    <div class="ims-nav-wrapper ims-has-flyout"
                         @mouseenter="openFlyout($el, 'Permintaan Layanan', [
                             @if(auth()->user()?->hasRole(['noc', 'direktur']))
                             { label: 'Aktivasi Jaringan', url: '{{ route('noc.aktivasi') }}', active: {{ request()->routeIs('noc.aktivasi*') ? 'true' : 'false' }} },
                             @endif
                             { label: 'UP / Downgrade', url: '{{ route('teknik.permintaan.up-downgrade') }}', active: {{ request()->routeIs('teknik.permintaan.up-downgrade*') ? 'true' : 'false' }} },
                             { label: 'Terminasi', url: '{{ route('teknik.permintaan.terminasi') }}', active: {{ request()->routeIs('teknik.permintaan.terminasi*') ? 'true' : 'false' }} },
                             { label: 'Suspend', url: '{{ route('teknik.permintaan.suspend') }}', active: {{ request()->routeIs('teknik.permintaan.suspend*') ? 'true' : 'false' }} }
                         ])"
                         @mouseleave="closeFlyoutWithDelay()">
                        <button type="button"
                                @click="sidebarCollapsed ? (sidebarCollapsed = false, permintaanOpen = true) : (permintaanOpen = !permintaanOpen)"
                                class="w-full ims-nav-item {{ request()->routeIs('teknik.permintaan.*', 'noc.aktivasi*') ? 'active' : '' }} justify-between"
                                title="Permintaan">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.permintaan.*', 'noc.aktivasi*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                </svg>
                                <span class="ims-nav-text">Permintaan</span>
                            </span>
                            <svg class="ims-nav-arrow w-4 h-4 transition-transform duration-200"
                                 :class="permintaanOpen ? 'rotate-180 text-white' : 'text-[#9CA3AF]'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div class="ims-tooltip">Permintaan Layanan</div>

                        <!-- Expanded Accordion -->
                        <div x-show="permintaanOpen"
                             x-cloak
                             x-collapse
                             class="ims-submenu mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            
                            @if(auth()->user()?->hasRole(['noc', 'direktur']))
                            <a href="{{ route('noc.aktivasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.aktivasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.aktivasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Aktivasi Jaringan</span>
                            </a>
                            @endif

                            <a href="{{ route('teknik.permintaan.up-downgrade') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.up-downgrade') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.up-downgrade') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>UP/Downgrade</span>
                            </a>

                            <a href="{{ route('teknik.permintaan.terminasi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.terminasi') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.terminasi') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Terminasi</span>
                            </a>

                            <a href="{{ route('teknik.permintaan.suspend') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.suspend') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.suspend') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Suspend</span>
                            </a>
                        </div>
                    </div>

                    <!-- 6. Cek Coverage ODP -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('teknik.coverage') }}"
                           class="ims-nav-item {{ request()->routeIs('teknik.coverage*') ? 'active' : '' }}"
                           title="Cek Coverage ODP">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.coverage*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <span class="ims-nav-text">Cek Coverage</span>
                        </a>
                        <div class="ims-tooltip">Cek Coverage ODP</div>
                    </div>
                    @endif

                    @if(auth()->user()?->hasRole(['noc', 'direktur', 'admin']))
                    <!-- ============================================== -->
                    <!-- NOC SECTION                                    -->
                    <!-- ============================================== -->
                    <div class="pt-2.5 pb-1">
                        <div class="ims-section-header px-3 text-[10px] font-bold text-[#9CA3AF] uppercase tracking-wider flex items-center justify-between">
                            <span class="ims-section-title">Modul NOC</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- 1. Data Pelanggan -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('teknik.pelanggan') }}"
                           class="ims-nav-item {{ request()->routeIs('teknik.pelanggan*') ? 'active' : '' }}"
                           title="Data Pelanggan">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.pelanggan*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span class="ims-nav-text">Data Pelanggan</span>
                        </a>
                        <div class="ims-tooltip">Data Pelanggan</div>
                    </div>

                    <!-- 2. Tiket -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('teknik.tiket') }}"
                           class="ims-nav-item {{ request()->routeIs('teknik.tiket*') ? 'active' : '' }}"
                           title="Tiket & Permintaan">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('teknik.tiket*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                            </svg>
                            <span class="ims-nav-text">Tiket</span>
                        </a>
                        <div class="ims-tooltip">Tiket & Permintaan</div>
                    </div>

                    <!-- 3. Permintaan NOC Dropdown -->
                    <div class="ims-nav-wrapper ims-has-flyout"
                         x-data="{ permintaanNocOpen: {{ request()->routeIs('noc.aktivasi*', 'noc.suspend*', 'noc.terminasi*', 'teknik.permintaan.*') ? 'true' : 'false' }} }"
                         @mouseenter="openFlyout($el, 'Permintaan NOC', [
                             { label: 'Aktivasi Jaringan', url: '{{ route('noc.aktivasi') }}', active: {{ request()->routeIs('noc.aktivasi*') ? 'true' : 'false' }} },
                             { label: 'UP / Downgrade', url: '{{ route('teknik.permintaan.up-downgrade') }}', active: {{ request()->routeIs('teknik.permintaan.up-downgrade*') ? 'true' : 'false' }} },
                             { label: 'Suspend (Isolir)', url: '{{ route('noc.suspend') }}', active: {{ request()->routeIs('noc.suspend*') ? 'true' : 'false' }} },
                             { label: 'Terminasi', url: '{{ route('noc.terminasi') }}', active: {{ request()->routeIs('noc.terminasi*') ? 'true' : 'false' }} }
                         ])"
                         @mouseleave="closeFlyoutWithDelay()">
                        <button type="button"
                                @click="sidebarCollapsed ? (sidebarCollapsed = false, permintaanNocOpen = true) : (permintaanNocOpen = !permintaanNocOpen)"
                                class="w-full ims-nav-item {{ request()->routeIs('noc.aktivasi*', 'noc.suspend*', 'noc.terminasi*', 'teknik.permintaan.*') ? 'active' : '' }} justify-between"
                                title="Permintaan NOC">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.aktivasi*', 'noc.suspend*', 'noc.terminasi*', 'teknik.permintaan.*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                </svg>
                                <span class="ims-nav-text">Permintaan</span>
                            </span>
                            <svg class="ims-nav-arrow w-4 h-4 transition-transform duration-200"
                                 :class="permintaanNocOpen ? 'rotate-180 text-white' : 'text-[#9CA3AF]'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div class="ims-tooltip">Permintaan NOC</div>

                        <!-- Submenu -->
                        <div x-show="permintaanNocOpen"
                             x-cloak
                             x-collapse
                             class="ims-submenu mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            <a href="{{ route('noc.aktivasi') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.aktivasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.aktivasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Aktivasi Jaringan</span>
                            </a>
                            <a href="{{ route('teknik.permintaan.up-downgrade') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('teknik.permintaan.up-downgrade*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('teknik.permintaan.up-downgrade*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>UP / Downgrade</span>
                            </a>
                            <a href="{{ route('noc.suspend') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.suspend*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.suspend*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Suspend (Isolir)</span>
                            </a>
                            <a href="{{ route('noc.terminasi') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.terminasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.terminasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Terminasi</span>
                            </a>
                        </div>
                    </div>

                    <!-- 4. NOC Dashboard -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('noc.dashboard') }}"
                           class="ims-nav-item {{ request()->routeIs('noc.dashboard*') ? 'active' : '' }}"
                           title="NOC Command Dashboard">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.dashboard*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                            </svg>
                            <span class="ims-nav-text">NOC Command</span>
                        </a>
                        <div class="ims-tooltip">NOC Command Dashboard</div>
                    </div>

                    <!-- 5. Menu OLT (Dropdown Pilihan OLT dari Master OLT) -->
                    <div class="ims-nav-wrapper ims-has-flyout"
                         x-data="{ oltMenuOpen: {{ request()->routeIs('noc.network-olt*') ? 'true' : 'false' }} }"
                         @mouseenter="openFlyout($el, 'Menu OLT', [
                             @if(isset($sidebarOlts))
                                  @foreach($sidebarOlts as $sOlt)
                                  @php
                                      $sOltId = is_array($sOlt) ? ($sOlt['olt_id'] ?? '') : ($sOlt->olt_id ?? '');
                                      $sOltNama = is_array($sOlt) ? ($sOlt['nama_olt'] ?? '') : ($sOlt->nama_olt ?? '');
                                  @endphp
                                  { label: 'OLT {{ $sOltId }} - {{ strtoupper($sOltNama) }}', url: '{{ route('noc.network-olt', ['olt_id' => $sOltId]) }}', active: {{ request()->routeIs('noc.network-olt*') && ((string)request()->route('olt_id', request('olt_id', 1)) === (string)$sOltId) ? 'true' : 'false' }} },
                                  @endforeach
                             @endif
                         ])"
                         @mouseleave="closeFlyoutWithDelay()">
                        <button type="button"
                                @click="sidebarCollapsed ? (sidebarCollapsed = false, oltMenuOpen = true) : (oltMenuOpen = !oltMenuOpen)"
                                class="w-full ims-nav-item {{ request()->routeIs('noc.network-olt*') ? 'active' : '' }} justify-between"
                                title="Menu OLT">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.network-olt*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a2.25 2.25 0 0 1 1.8-.85h8.9a2.25 2.25 0 0 1 1.8.85l2.1 3.15a4.5 4.5 0 0 1 .9 2.7" />
                                </svg>
                                <span class="ims-nav-text">OLT</span>
                            </span>
                            <svg class="ims-nav-arrow w-4 h-4 transition-transform duration-200"
                                 :class="oltMenuOpen ? 'rotate-180 text-white' : 'text-[#9CA3AF]'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div class="ims-tooltip">Daftar OLT</div>

                        <!-- Submenu Pilihan OLT -->
                        <div x-show="oltMenuOpen"
                             x-cloak
                             x-collapse
                             class="ims-submenu mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            @if(isset($sidebarOlts) && (is_countable($sidebarOlts) ? count($sidebarOlts) > 0 : !empty($sidebarOlts)))
                                @foreach($sidebarOlts as $sOlt)
                                    @php
                                        $sOltId = is_array($sOlt) ? ($sOlt['olt_id'] ?? '') : ($sOlt->olt_id ?? '');
                                        $sOltNama = is_array($sOlt) ? ($sOlt['nama_olt'] ?? '') : ($sOlt->nama_olt ?? '');
                                        $isCurrentOlt = request()->routeIs('noc.network-olt*') && ((string)request()->route('olt_id', request('olt_id', 1)) === (string)$sOltId);
                                    @endphp
                                    <a href="{{ route('noc.network-olt', ['olt_id' => $sOltId]) }}" 
                                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ $isCurrentOlt ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                        <span class="w-1.5 h-1.5 rounded-full border {{ $isCurrentOlt ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                        <span class="uppercase">OLT {{ $sOltId }} - {{ $sOltNama }}</span>
                                    </a>
                                @endforeach
                            @else
                                <span class="block px-3 py-2 text-[11px] text-slate-500 italic">Belum ada data OLT</span>
                            @endif
                        </div>
                    </div>

                    <!-- 6. Infrastruktur Dropdown -->
                    <div class="ims-nav-wrapper ims-has-flyout"
                         x-data="{ infraOpen: {{ request()->routeIs('noc.olt', 'noc.olt.create', 'noc.olt.edit', 'noc.gpon*', 'noc.pop*', 'noc.wilayah*', 'noc.router*', 'noc.activity-log*') ? 'true' : 'false' }} }"
                         @mouseenter="openFlyout($el, 'Infrastruktur Jaringan', [
                             { label: 'Topologi & GPON Port', url: '{{ route('noc.gpon') }}', active: {{ request()->routeIs('noc.gpon*') ? 'true' : 'false' }} },
                             { label: 'OLT & Master Node', url: '{{ route('noc.olt') }}', active: {{ request()->routeIs('noc.olt', 'noc.olt.create', 'noc.olt.edit') ? 'true' : 'false' }} },
                             { label: 'Router MikroTik', url: '{{ route('noc.router') }}', active: {{ request()->routeIs('noc.router*') ? 'true' : 'false' }} },
                             { label: 'Activity Log Router', url: '{{ route('noc.activity-log') }}', active: {{ request()->routeIs('noc.activity-log*') ? 'true' : 'false' }} },
                             { label: 'POP (Point of Presence)', url: '{{ route('noc.pop') }}', active: {{ request()->routeIs('noc.pop*') ? 'true' : 'false' }} },
                             { label: 'Wilayah Perangkat', url: '{{ route('noc.wilayah') }}', active: {{ request()->routeIs('noc.wilayah*') ? 'true' : 'false' }} }
                         ])"
                         @mouseleave="closeFlyoutWithDelay()">
                        <button type="button"
                                @click="sidebarCollapsed ? (sidebarCollapsed = false, infraOpen = true) : (infraOpen = !infraOpen)"
                                class="w-full ims-nav-item {{ request()->routeIs('noc.olt', 'noc.olt.create', 'noc.olt.edit', 'noc.gpon*', 'noc.pop*', 'noc.wilayah*', 'noc.router*', 'noc.activity-log*') ? 'active' : '' }} justify-between"
                                title="Infrastruktur">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.olt', 'noc.olt.create', 'noc.olt.edit', 'noc.gpon*', 'noc.pop*', 'noc.wilayah*', 'noc.router*', 'noc.activity-log*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                                </svg>
                                <span class="ims-nav-text">Infrastruktur</span>
                            </span>
                            <svg class="ims-nav-arrow w-4 h-4 transition-transform duration-200"
                                 :class="infraOpen ? 'rotate-180 text-white' : 'text-[#9CA3AF]'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div class="ims-tooltip">Infrastruktur Jaringan</div>

                        <!-- Submenu -->
                        <div x-show="infraOpen"
                             x-cloak
                             x-collapse
                             class="ims-submenu mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            <a href="{{ route('noc.gpon') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.gpon*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.gpon*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Topologi & GPON Port</span>
                            </a>
                            <a href="{{ route('noc.olt') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.olt*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.olt*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>OLT & Master Node</span>
                            </a>
                            <a href="{{ route('noc.router') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.router*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.router*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Router MikroTik</span>
                            </a>
                            <a href="{{ route('noc.activity-log') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.activity-log*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.activity-log*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Activity Log Router</span>
                            </a>
                            <a href="{{ route('noc.pop') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.pop*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.pop*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>POP (Point of Presence)</span>
                            </a>
                            <a href="{{ route('noc.wilayah') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('noc.wilayah*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('noc.wilayah*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Wilayah Perangkat</span>
                            </a>
                        </div>
                    </div>

                    <!-- 7. Inventaris Perangkat -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('noc.perangkat') }}"
                           class="ims-nav-item {{ request()->routeIs('noc.perangkat*') ? 'active' : '' }}"
                           title="Inventaris Perangkat">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('noc.perangkat*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            <span class="ims-nav-text">Inventaris Perangkat</span>
                        </a>
                        <div class="ims-tooltip">Inventaris Perangkat</div>
                    </div>
                    @endif

                    @if(auth()->user()?->hasRole(['finance', 'direktur', 'admin']))
                    <!-- ============================================== -->
                    <!-- FINANCE SECTION                                -->
                    <!-- ============================================== -->
                    <div class="pt-2.5 pb-1">
                        <div class="ims-section-header px-3 text-[10px] font-bold text-[#9CA3AF] uppercase tracking-wider flex items-center justify-between">
                            <span class="ims-section-title">Modul Finance</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- Data Pelanggan (Finance) -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('finance.pelanggan') }}"
                           class="ims-nav-item {{ request()->routeIs('finance.pelanggan*') ? 'active' : '' }}"
                           title="Data Pelanggan">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('finance.pelanggan*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span class="ims-nav-text">Data Pelanggan</span>
                        </a>
                        <div class="ims-tooltip">Data Pelanggan</div>
                    </div>

                    <!-- Billing Layanan -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('finance.billing-layanan') }}"
                           class="ims-nav-item {{ request()->routeIs('finance.billing-layanan*') ? 'active' : '' }}"
                           title="Billing Layanan">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('finance.billing-layanan*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v10.5m0-10.5h6.75a.75.75 0 0 1 .75.75v.75m0 0v8.25m0-8.25h12.75a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H2.25M6 9h.008v.008H6V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H6v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <span class="ims-nav-text">Billing Layanan</span>
                        </a>
                        <div class="ims-tooltip">Billing Layanan</div>
                    </div>

                    <!-- Billing Registrasi -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('finance.billing-registrasi') }}"
                           class="ims-nav-item {{ request()->routeIs('finance.billing-registrasi*') ? 'active' : '' }}"
                           title="Billing Registrasi">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('finance.billing-registrasi*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span class="ims-nav-text">Billing Registrasi</span>
                        </a>
                        <div class="ims-tooltip">Billing Registrasi</div>
                    </div>

                    <!-- Permintaan Finance Dropdown -->
                    <div class="ims-nav-wrapper ims-has-flyout"
                         x-data="{ permintaanFinanceOpen: {{ request()->routeIs('finance.permintaan.*') ? 'true' : 'false' }} }"
                         @mouseenter="openFlyout($el, 'Permintaan ke NOC', [
                             { label: 'UP / Downgrade', url: '{{ route('finance.permintaan.up-downgrade') }}', active: {{ request()->routeIs('finance.permintaan.up-downgrade*') ? 'true' : 'false' }} },
                             { label: 'Suspend', url: '{{ route('finance.permintaan.suspend') }}', active: {{ request()->routeIs('finance.permintaan.suspend*') ? 'true' : 'false' }} },
                             { label: 'Terminasi', url: '{{ route('finance.permintaan.terminasi') }}', active: {{ request()->routeIs('finance.permintaan.terminasi*') ? 'true' : 'false' }} }
                         ])"
                         @mouseleave="closeFlyoutWithDelay()">
                        <button type="button"
                                @click="sidebarCollapsed ? (sidebarCollapsed = false, permintaanFinanceOpen = true) : (permintaanFinanceOpen = !permintaanFinanceOpen)"
                                class="w-full ims-nav-item {{ request()->routeIs('finance.permintaan.*') ? 'active' : '' }} justify-between"
                                title="Permintaan ke NOC">
                            <span class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('finance.permintaan.*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                                <span class="ims-nav-text">Permintaan ke NOC</span>
                            </span>
                            <svg class="ims-nav-arrow w-4 h-4 transition-transform duration-200"
                                 :class="permintaanFinanceOpen ? 'rotate-180 text-white' : 'text-[#9CA3AF]'"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div class="ims-tooltip">Permintaan ke NOC</div>

                        <!-- Submenu -->
                        <div x-show="permintaanFinanceOpen"
                             x-cloak
                             x-collapse
                             class="ims-submenu mt-1.5 ml-4 pl-3.5 border-l border-slate-800 space-y-1">
                            <a href="{{ route('finance.permintaan.up-downgrade') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('finance.permintaan.up-downgrade*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('finance.permintaan.up-downgrade*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>UP / Downgrade</span>
                            </a>
                            <a href="{{ route('finance.permintaan.suspend') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('finance.permintaan.suspend*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('finance.permintaan.suspend*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Suspend</span>
                            </a>
                            <a href="{{ route('finance.permintaan.terminasi') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs transition {{ request()->routeIs('finance.permintaan.terminasi*') ? 'text-blue-400 font-semibold bg-blue-500/10' : 'text-[#9CA3AF] hover:text-white hover:bg-white/5' }}">
                                <span class="w-1.5 h-1.5 rounded-full border {{ request()->routeIs('finance.permintaan.terminasi*') ? 'border-blue-400 bg-blue-400' : 'border-slate-600' }}"></span>
                                <span>Terminasi</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()?->hasRole(['admin', 'direktur']))
                    <!-- ============================================== -->
                    <!-- MASTER ADMIN SECTION                           -->
                    <!-- ============================================== -->
                    <div class="pt-2.5 pb-1">
                        <div class="ims-section-header px-3 text-[10px] font-bold text-[#9CA3AF] uppercase tracking-wider flex items-center justify-between">
                            <span class="ims-section-title">Master Admin</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        </div>
                    </div>

                    <!-- 1. Manajemen User -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('admin.users') }}"
                           class="ims-nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
                           title="Manajemen User">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.users*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.765l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span class="ims-nav-text">Manajemen User</span>
                        </a>
                        <div class="ims-tooltip">Manajemen User</div>
                    </div>

                    <!-- 2. Master Paket Internet -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('admin.paket') }}"
                           class="ims-nav-item {{ request()->routeIs('admin.paket*') ? 'active' : '' }}"
                           title="Master Paket Internet">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.paket*') ? 'text-white' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                            </svg>
                            <span class="ims-nav-text">Master Paket</span>
                        </a>
                        <div class="ims-tooltip">Master Paket Internet</div>
                    </div>

                    <!-- 3. Broadcast WhatsApp -->
                    <div class="ims-nav-wrapper">
                        <a href="{{ route('admin.broadcast') }}"
                           class="ims-nav-item {{ request()->routeIs('admin.broadcast*') ? 'active' : '' }}"
                           title="Broadcast WhatsApp">
                            <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.broadcast*') ? 'text-emerald-400' : 'text-[#9CA3AF]' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                            <span class="ims-nav-text flex items-center justify-between w-full">
                                <span>Broadcast WA</span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">NEW</span>
                            </span>
                        </a>
                        <div class="ims-tooltip">Broadcast WhatsApp (Jatuh Tempo & Pengumuman)</div>
                    </div>
                    @endif

                    <!-- Logout / Keluar -->
                    <div class="pt-2">
                        <div class="ims-nav-wrapper">
                            <form action="{{ route('logout') }}" method="POST" onsubmit="localStorage.removeItem('theme');">
                                @csrf
                                <button type="submit"
                                        class="w-full ims-nav-item hover:bg-rose-500/10 hover:text-rose-400 text-[#9CA3AF] cursor-pointer"
                                        title="Keluar dari Sistem">
                                    <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                    </svg>
                                    <span class="ims-nav-text">Keluar</span>
                                </button>
                            </form>
                            <div class="ims-tooltip bg-[#0F172A] text-rose-400 border border-rose-900/50">Keluar</div>
                        </div>
                    </div>
                </nav>

                <!-- Sidebar Footer Info -->
                <div class="p-3 border-t border-[#0d2a38] text-center overflow-hidden shrink-0">
                    <div class="ims-footer-full whitespace-nowrap">
                        <span class="text-[11px] text-blue-400/90 font-medium">IMS-v2</span>
                    </div>
                    <div class="ims-footer-mini text-[10px] text-[#9CA3AF] font-mono font-bold">
                        v2
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto ims-main-scroll">
                <!-- Top Navbar (Dual Light & Dark Mode) -->
                <header class="min-h-14 sm:min-h-16 shrink-0 bg-white dark:bg-slate-900 border-b border-slate-200/90 dark:border-slate-800 flex items-center justify-between px-3 sm:px-6 lg:px-8 sticky top-0 z-50 py-2 shadow-xs transition-colors duration-200">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <!-- Sidebar Toggle Button in Navbar (Tombol Hamburger) -->
                        <button @click="toggleSidebar()" 
                                :title="sidebarCollapsed ? 'Buka Sidebar Penuh' : 'Tutup Sidebar ke Mode Ikon'"
                                class="p-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center justify-center flex-shrink-0 cursor-pointer">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            <h1 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-tight truncate">@yield('page_title', 'Dashboard')</h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Integrated Management System &middot; Portal</p>
                        </div>
                    </div>

                    <!-- Right Side Navbar -->
                    <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                        


                        <!-- Theme Toggle Button (Light / Dark Mode) -->
                        <button @click="toggleTheme()" 
                                type="button"
                                :title="isDarkMode ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                                class="p-2 text-slate-600 dark:text-amber-400 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center justify-center flex-shrink-0 cursor-pointer shadow-xs group"
                                aria-label="Toggle Theme">
                            <!-- Sun Icon (Active in Dark Mode) -->
                            <svg x-show="isDarkMode" x-cloak class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                            <!-- Moon Icon (Active in Light Mode) -->
                            <svg x-show="!isDarkMode" class="w-4 h-4 text-slate-600 group-hover:-rotate-12 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </button>

                        <!-- Status Gateway & Jam Realtime WIB (Hidden on small mobile screens to keep navbar tidy) -->
                        <div class="hidden lg:flex flex-col items-end gap-0.5"
                             x-data="{
                                 timeWib: '',
                                 dateWib: '',
                                 updateClock() {
                                     const now = new Date();
                                     
                                     // Format Waktu WIB (Asia/Jakarta)
                                     const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                                         timeZone: 'Asia/Jakarta',
                                         hour12: false,
                                         hour: '2-digit',
                                         minute: '2-digit',
                                         second: '2-digit',
                                     });

                                     // Format Tanggal
                                     const dateFormatter = new Intl.DateTimeFormat('id-ID', {
                                         timeZone: 'Asia/Jakarta',
                                         weekday: 'short',
                                         day: 'numeric',
                                         month: 'short',
                                         year: 'numeric',
                                     });

                                     this.timeWib = timeFormatter.format(now).replace(/\./g, ':') + ' WIB';
                                     this.dateWib = dateFormatter.format(now);
                                 }
                             }"
                             x-init="updateClock(); setInterval(() => updateClock(), 1000)">
                            
                            <!-- 1. Status Gateway -->
                            <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px]">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-emerald-700 dark:text-emerald-400 font-semibold">Online</span>
                            </div>

                            <!-- 2. Jam Realtime WIB -->
                            <div class="flex items-center gap-1 text-[11px] text-slate-600 dark:text-slate-400 font-medium tracking-wide">
                                <svg class="w-3.5 h-3.5 text-[#0891b2] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span class="font-mono text-slate-900 dark:text-slate-100 font-bold" x-text="timeWib">--:--:-- WIB</span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 hidden xl:inline" x-text="'&middot; ' + dateWib"></span>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    type="button"
                                    class="flex items-center gap-2.5 p-1 sm:p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition cursor-pointer">
                                @if(auth()->user()?->foto_url)
                                    <div class="w-8 h-8 rounded-lg overflow-hidden ring-1 ring-slate-200 dark:ring-slate-700 flex-shrink-0 shadow-xs bg-slate-100 dark:bg-slate-800">
                                        <img src="{{ auth()->user()->foto_url }}" alt="{{ auth()->user()->nama }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-full h-full hidden items-center justify-center font-bold text-xs text-white bg-gradient-to-tr from-[#05404f] to-[#0891b2]">
                                            {{ strtoupper(substr(auth()->user()?->nama ?? 'U', 0, 1)) }}
                                        </div>
                                    </div>
                                @else
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#05404f] to-[#0891b2] flex items-center justify-center font-bold text-xs text-white flex-shrink-0 shadow-xs">
                                        {{ strtoupper(substr(auth()->user()?->nama ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                                <div class="text-left hidden md:block">
                                    <span class="block text-xs font-bold text-slate-900 dark:text-white leading-tight truncate max-w-[140px]">{{ auth()->user()?->nama }}</span>
                                    <span class="block text-[10px] text-[#0891b2] dark:text-cyan-400 font-semibold">{{ auth()->user()?->nama_level }}</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-cloak
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute right-0 mt-2 w-64 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl shadow-slate-900/10 dark:shadow-black/60 p-2 z-50">
                                
                                <!-- User Header Card -->
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center gap-3 mb-1.5">
                                    @if(auth()->user()?->foto_url)
                                        <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0 ring-2 ring-white dark:ring-slate-700 shadow-xs bg-slate-100 dark:bg-slate-800">
                                            <img src="{{ auth()->user()->foto_url }}" alt="{{ auth()->user()->nama }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="w-full h-full hidden items-center justify-center font-bold text-sm text-white bg-gradient-to-tr from-[#05404f] to-[#0891b2]">
                                                {{ strtoupper(substr(auth()->user()?->nama ?? 'U', 0, 1)) }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#05404f] to-[#0891b2] flex items-center justify-center font-bold text-sm text-white flex-shrink-0 shadow-xs">
                                            {{ strtoupper(substr(auth()->user()?->nama ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-xs text-slate-900 dark:text-white truncate" title="{{ auth()->user()?->nama }}">{{ auth()->user()?->nama }}</p>
                                        <p class="text-slate-500 dark:text-slate-400 font-mono text-[11px] truncate" title="{{ auth()->user()?->username }}">{{ auth()->user()?->username }}</p>
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-blue-100/70 dark:bg-blue-900/40 text-blue-700 dark:text-cyan-300">
                                                {{ auth()->user()?->nama_level }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons List -->
                                <div class="space-y-1">
                                    <!-- 1. Profil Saya -->
                                    <a href="{{ route('profile') }}"
                                       @click="open = false"
                                       class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-950/40 hover:text-blue-600 dark:hover:text-cyan-400 flex items-center justify-between group transition cursor-pointer {{ request()->routeIs('profile*') ? 'bg-blue-50 dark:bg-blue-950/40 font-bold text-blue-600 dark:text-cyan-400' : '' }}">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-600 dark:text-cyan-400 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </div>
                                            <span>Profil Saya</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-500 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </a>

                                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                                    <!-- 2. Keluar -->
                                    <form action="{{ route('logout') }}" method="POST" onsubmit="localStorage.removeItem('theme');">
                                        @csrf
                                        <button type="submit"
                                                class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center justify-between group transition cursor-pointer">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                                    </svg>
                                                </div>
                                                <span>Keluar</span>
                                            </div>
                                            <svg class="w-3.5 h-3.5 text-rose-300 dark:text-rose-500/70 group-hover:text-rose-500 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content (Dual Light & Dark Mode Container) -->
                <main class="flex-1 p-3.5 sm:p-6 lg:p-8 bg-[#F8FAFC] dark:bg-[#071520] transition-colors duration-200">
                    <!-- Flash Message -->
                    <!-- Flash Message -->
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" class="mb-5 p-3.5 sm:p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-sm backdrop-blur-sm transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400 font-bold">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="font-medium leading-relaxed">{{ session('success') }}</span>
                            </div>
                            <button @click="show = false" type="button" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 text-lg font-bold p-1 leading-none shrink-0 transition cursor-pointer">&times;</button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div x-data="{ show: true }" x-show="show" class="mb-5 p-3.5 sm:p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-sm backdrop-blur-sm transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0 text-amber-600 dark:text-amber-400 font-bold">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="font-medium leading-relaxed">{{ session('warning') }}</span>
                            </div>
                            <button @click="show = false" type="button" class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 text-lg font-bold p-1 leading-none shrink-0 transition cursor-pointer">&times;</button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div x-data="{ show: true }" x-show="show" class="mb-5 p-3.5 sm:p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-700 dark:text-blue-300 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-sm backdrop-blur-sm transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/20 flex items-center justify-center shrink-0 text-blue-600 dark:text-blue-400 font-bold">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="font-medium leading-relaxed">{{ session('info') }}</span>
                            </div>
                            <button @click="show = false" type="button" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 text-lg font-bold p-1 leading-none shrink-0 transition cursor-pointer">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" class="mb-5 p-3.5 sm:p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-sm backdrop-blur-sm transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-rose-500/20 flex items-center justify-center shrink-0 text-rose-600 dark:text-rose-400 font-bold">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <span class="font-medium leading-relaxed">{{ session('error') }}</span>
                            </div>
                            <button @click="show = false" type="button" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-200 text-lg font-bold p-1 leading-none shrink-0 transition cursor-pointer">&times;</button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div x-data="{ show: true }" x-show="show" class="mb-5 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm shadow-sm backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-2 font-bold mb-1.5">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>Perhatian: Gagal memproses permintaan</span>
                                </div>
                                <button @click="show = false" type="button" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-200 text-lg font-bold p-1 leading-none transition cursor-pointer">&times;</button>
                            </div>
                            <ul class="list-disc list-inside space-y-1 text-xs text-rose-600 dark:text-rose-400 ml-1">
                                @foreach($errors->all() as $errorItem)
                                    <li>{{ $errorItem }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>

                <!-- Bottom Footer (Dual Light & Dark Mode) -->
                <footer class="mt-auto shrink-0 px-6 py-3.5 border-t border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 gap-2 transition-colors duration-200">
                    <div>
                        &copy; {{ date('Y') }} Media Solusi Network
                    </div>
                    <div>
                        IMS-v2 &middot; v3.0.1
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Collapsed Sidebar Floating Flyout Submenu Portal (Root of Body) -->
    <div x-show="sidebarCollapsed && activeFlyout"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-x-1"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-1"
         @mouseenter="cancelFlyoutClose()"
         @mouseleave="closeFlyoutWithDelay()"
         class="ims-flyout-portal w-56 rounded-2xl bg-[#061d28] border border-[#0d2a38] shadow-2xl shadow-black p-2.5 space-y-1"
         :style="`top: ${flyoutTop}px; left: 4.85rem; position: fixed !important; z-index: 9999999 !important;`">
        
        <!-- Flyout Header / Title -->
        <div class="px-3 py-1.5 mb-1 border-b border-[#0d2a38] flex items-center justify-between">
            <span class="text-xs font-bold text-white tracking-wide" x-text="flyoutTitle"></span>
            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
        </div>

        <!-- Flyout Menu Items -->
        <div class="space-y-0.5">
            <template x-for="(item, idx) in flyoutItems" :key="idx">
                <a :href="item.url"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs transition duration-150"
                   :class="item.active ? 'bg-gradient-to-r from-[#0891b2] to-[#0284c7] text-white font-semibold shadow-md shadow-cyan-900/40' : 'text-slate-300 hover:text-white hover:bg-white/5 font-medium'">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                          :class="item.active ? 'bg-white shadow-xs shadow-white' : 'bg-slate-500'"></span>
                    <span class="truncate" x-text="item.label"></span>
                </a>
            </template>
        </div>
    </div>

    <!-- Toast Notification Portal Container (Fixed Top Right) -->
    <div x-data="{
             toasts: [],
             addToast(detail) {
                 const id = detail.id || ('t_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4));
                 const toast = { ...detail, id };
                 this.toasts.unshift(toast);
                 if (this.toasts.length > 5) this.toasts.pop();
                 setTimeout(() => this.removeToast(id), 8000);
             },
             removeToast(id) {
                 this.toasts = this.toasts.filter(t => t.id !== id);
             }
         }"
         @ims-new-toast.window="addToast($event.detail)"
         class="fixed top-5 right-5 z-[999999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0">
        
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto w-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-2xl shadow-black/15 flex items-start gap-3 transition-all duration-300 transform translate-y-0"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90">
                
                <!-- Toast Icon -->
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     :class="{
                         'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30': t.type === 'pendaftaran' || t.type === 'pembayaran',
                         'bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/30': t.type === 'instalasi',
                         'bg-violet-500/15 text-violet-600 dark:text-violet-400 border border-violet-500/30': t.type === 'request_invoice',
                         'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30': t.type === 'tiket',
                         'bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30': t.type === 'updown',
                         'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30': t.type === 'suspend',
                         'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30': t.type === 'terminasi',
                         'bg-teal-500/15 text-teal-600 dark:text-teal-400 border border-teal-500/30': !t.type
                     }">
                    <template x-if="t.type === 'instalasi'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'pendaftaran'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'pembayaran'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0-8.25h16.5m0 0v8.25m0-8.25h1.5a.75.75 0 0 1 .75.75v.75m-2.25 0h2.25m-2.25 0a60.07 60.07 0 0 0-15.797 2.101c-.727.198-1.453-.342-1.453-1.096V8.25m2.25 0H21M9 12.75a3 3 0 1 0 6 0 3 3 0 0 0-6 0Z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'request_invoice'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'tiket'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'updown'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'suspend'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'terminasi'">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </template>
                    <template x-if="!t.type">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </template>
                </div>

                <!-- Toast Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="t.title"></h5>
                        <button type="button" @click="removeToast(t.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold leading-none cursor-pointer">&times;</button>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-snug" x-text="t.message"></p>
                    <template x-if="t.url">
                        <a :href="t.url" class="inline-flex items-center gap-1 text-[11px] font-bold text-teal-600 dark:text-teal-400 hover:underline mt-1.5">
                            <span>Buka Halaman</span>
                            <span>&rarr;</span>
                        </a>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- IMS Web Speech Voice Engine & Notification Poller -->
    <script>
        window.ImsVoice = {
            soundEnabled: localStorage.getItem('ims_voice_sound_enabled') !== 'false',
            lastCheck: Math.max(
                parseInt(localStorage.getItem('ims_last_notif_poll_ts') || '0', 10),
                Math.floor(Date.now() / 1000) - 60
            ),
            chimeAudio: new Audio('{{ asset("assets/sound/anoun.mp3") }}'),
            audioUnlocked: false,
            voicesReady: false,
            queue: [],
            isProcessingQueue: false,

            getPlayedIds() {
                try {
                    const stored = localStorage.getItem('ims_played_notifications');
                    return stored ? JSON.parse(stored) : [];
                } catch(e) {
                    return [];
                }
            },

            markAsPlayed(id) {
                if (!id) return;
                try {
                    const list = this.getPlayedIds();
                    if (!list.includes(id)) {
                        list.push(id);
                        if (list.length > 200) list.shift();
                        localStorage.setItem('ims_played_notifications', JSON.stringify(list));
                    }
                } catch(e) {}
            },

            hasPlayed(id) {
                if (!id) return false;
                return this.getPlayedIds().includes(id);
            },

            init() {
                // Ensure voices are loaded in browser
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.onvoiceschanged = () => {
                        this.voicesReady = true;
                    };
                }

                // Unlock audio autoplay on first user interaction
                const unlock = () => {
                    if (!this.audioUnlocked) {
                        this.chimeAudio.muted = true;
                        this.chimeAudio.play().then(() => {
                            this.chimeAudio.pause();
                            this.chimeAudio.currentTime = 0;
                            this.chimeAudio.muted = false;
                            this.audioUnlocked = true;
                        }).catch(() => {});
                        document.removeEventListener('click', unlock);
                        document.removeEventListener('keydown', unlock);
                    }
                };
                document.addEventListener('click', unlock, { once: true });
                document.addEventListener('keydown', unlock, { once: true });

                // Initial poll on load, then poll continuously every 15 seconds (including when tab is in background)
                setTimeout(() => this.pollNotifications(), 1500);
                setInterval(() => {
                    this.pollNotifications();
                }, 15000);
            },

            testSound() {
                this.enqueueNotification({
                    type: 'test',
                    title: 'Uji Coba Suara IMS',
                    message: 'Sistem notifikasi suara Web Speech API & Chime berfungsi dengan baik.',
                    speech_text: 'Tes notifikasi suara IMS berhasil. Selamat bertugas!',
                });
            },

            enqueueNotification(item) {
                this.queue.push(item);
                this.processQueue();
            },

            async processQueue() {
                if (this.isProcessingQueue || this.queue.length === 0) return;
                this.isProcessingQueue = true;
                const item = this.queue.shift();

                try {
                    // Trigger Toast Banner in UI
                    window.dispatchEvent(new CustomEvent('ims-new-toast', { detail: item }));

                    if (this.soundEnabled) {
                        await this.playChimeAndSpeak(item);
                    }
                } catch (e) {
                    console.warn('Notification play error:', e);
                } finally {
                    setTimeout(() => {
                        this.isProcessingQueue = false;
                        this.processQueue();
                    }, 500);
                }
            },

            playChimeAndSpeak(item) {
                return new Promise((resolve) => {
                    const text = item.speech_text || item.title;
                    if (!text) {
                        resolve();
                        return;
                    }

                    let resolved = false;
                    const done = () => {
                        if (!resolved) {
                            resolved = true;
                            resolve();
                        }
                    };

                    const speakText = () => {
                        if (!('speechSynthesis' in window)) {
                            done();
                            return;
                        }
                        try {
                            const utterance = new SpeechSynthesisUtterance(text);
                            utterance.lang = 'id-ID';
                            utterance.rate = 1.0;
                            utterance.pitch = 1.0;

                            const voices = window.speechSynthesis.getVoices();
                            const idVoice = voices.find(v => v.lang === 'id-ID' || v.lang === 'id_ID' || (v.lang && v.lang.toLowerCase().startsWith('id')));
                            if (idVoice) utterance.voice = idVoice;

                            utterance.onend = () => done();
                            utterance.onerror = () => done();

                            // Safety timeout in case utterance onend never fires
                            setTimeout(() => done(), 7000);

                            window.speechSynthesis.speak(utterance);
                        } catch(e) {
                            done();
                        }
                    };

                    // Try playing Chime MP3 first
                    this.chimeAudio.currentTime = 0;
                    this.chimeAudio.play().then(() => {
                        setTimeout(speakText, 550);
                    }).catch(() => {
                        speakText();
                    });
                });
            },

            async pollNotifications() {
                try {
                    const res = await fetch(`{{ route('api.notifications.poll') }}?since=${this.lastCheck}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.lastCheck = data.timestamp;
                        localStorage.setItem('ims_last_notif_poll_ts', String(data.timestamp));

                        if (Array.isArray(data.notifications) && data.notifications.length > 0) {
                            data.notifications.forEach((notif) => {
                                const notifId = notif.id;
                                if (notifId && !this.hasPlayed(notifId)) {
                                    // Mark immediately to prevent duplicate runs across tabs
                                    this.markAsPlayed(notifId);
                                    this.enqueueNotification(notif);
                                }
                            });
                        }
                    }
                } catch (e) {
                    // silent fail on network blips
                }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            window.ImsVoice.init();
        });
    </script>

    <!-- SweetAlert2 Global Notification -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            @if(session('success'))
                Toast.fire({
                    icon: 'success',
                    title: {!! json_encode(session('success')) !!}
                });
            @endif

            @if(session('warning'))
                Toast.fire({
                    icon: 'warning',
                    title: {!! json_encode(session('warning')) !!}
                });
            @endif

            @if(session('info'))
                Toast.fire({
                    icon: 'info',
                    title: {!! json_encode(session('info')) !!}
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: {!! json_encode(session('error')) !!},
                    confirmButtonColor: '#ef4444'
                });
            @endif
        });
    </script>

    <!-- Ultra-Fast Intelligent Link Prefetching Engine (Instant Page Transitions) -->
    <script>
        (function() {
            const prefetched = new Set();
            function prefetch(url) {
                if (!url || prefetched.has(url)) return;
                try {
                    const u = new URL(url, window.location.href);
                    // Only prefetch internal links on the same origin and GET navigation
                    if (u.origin !== window.location.origin) return;
                    if (u.pathname.includes('/logout') || u.pathname.includes('/delete') || u.pathname.includes('/export')) return;
                    
                    prefetched.add(url);
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = url;
                    link.as = 'document';
                    document.head.appendChild(link);
                } catch(e) {}
            }

            document.addEventListener('mouseover', function(e) {
                const a = e.target.closest('a');
                if (a && a.href && !a.hasAttribute('download') && a.target !== '_blank') {
                    prefetch(a.href);
                }
            }, { passive: true });

    <!-- Global Debounced Auto-Search & Focus Restoration Engine -->
    <script>
        (function() {
            // 1. Restore input focus and cursor position after debounced auto-reload
            document.addEventListener('DOMContentLoaded', function() {
                const savedFocus = sessionStorage.getItem('ims_auto_search_focus');
                if (savedFocus) {
                    sessionStorage.removeItem('ims_auto_search_focus');
                    try {
                        const data = JSON.parse(savedFocus);
                        const input = document.querySelector(data.selector);
                        if (input) {
                            input.focus();
                            const pos = (typeof data.cursor === 'number' && data.cursor <= input.value.length) ? data.cursor : input.value.length;
                            input.setSelectionRange(pos, pos);
                        }
                    } catch (e) {}
                }
            });

            // 2. Global Debounced Auto-Submit for filter search inputs
            let autoSearchTimer = null;
            document.addEventListener('input', function(e) {
                const target = e.target;
                if (!target || target.tagName !== 'INPUT') return;

                const inputType = (target.getAttribute('type') || 'text').toLowerCase();
                if (inputType !== 'text' && inputType !== 'search') return;

                const form = target.form;
                if (!form || (form.method || '').toUpperCase() !== 'GET') return;

                // Ignore modals, date pickers, or explicitly excluded inputs
                if (target.hasAttribute('data-no-auto-search') || target.closest('[role="dialog"]') || target.closest('.modal')) return;

                clearTimeout(autoSearchTimer);
                autoSearchTimer = setTimeout(function() {
                    const selector = target.id ? '#' + target.id : (target.name ? `input[name="${target.name}"]` : null);
                    if (selector) {
                        sessionStorage.setItem('ims_auto_search_focus', JSON.stringify({
                            selector: selector,
                            cursor: target.selectionStart ?? target.value.length
                        }));
                    }
                    form.submit();
                }, 450);
            });

            // 3. Keep focus position if user submits via Enter
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const target = e.target;
                    if (target && target.tagName === 'INPUT' && target.form && (target.form.method || '').toUpperCase() === 'GET') {
                        const selector = target.id ? '#' + target.id : (target.name ? `input[name="${target.name}"]` : null);
                        if (selector) {
                            sessionStorage.setItem('ims_auto_search_focus', JSON.stringify({
                                selector: selector,
                                cursor: target.selectionStart ?? target.value.length
                            }));
                        }
                    }
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
