<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Berlangganan - {{ $customer->nomor_internet }} - {{ $customer->nama_pelanggan }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #000;
            font-size: 8.2pt;
            line-height: 1.25;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Screen Toolbar */
        .screen-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(51, 65, 85, 0.8);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }

        .toolbar-badge {
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }

        .btn-download {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }

        .btn-download:hover {
            background: linear-gradient(135deg, #047857, #059669);
            transform: translateY(-1px);
        }

        .btn-word {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
        }

        .btn-word:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            transform: translateY(-1px);
        }

        .btn-print {
            background: #0284c7;
            color: #fff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
        }

        .btn-print:hover {
            background: #0369a1;
            transform: translateY(-1px);
        }

        .btn-back {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-back:hover {
            background: #475569;
            color: #fff;
        }

        /* Dropdown Opsi Download */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 230px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
            padding: 6px;
            z-index: 10000;
            display: none;
            flex-direction: column;
            gap: 4px;
        }

        .dropdown-menu.show {
            display: flex;
            animation: fadeIn 0.15s ease-out;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            color: #f1f5f9;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.15s;
            cursor: pointer;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-item:hover {
            background: #334155;
            color: #38bdf8;
        }

        .dropdown-item-desc {
            display: block;
            font-size: 10px;
            color: #94a3b8;
            font-weight: 400;
        }

        .dropdown-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .page-container {
            padding-top: 75px;
            padding-bottom: 35px;
            display: flex;
            justify-content: center;
        }

        /* Paper Form A4 */
        .paper-sheet {
            width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            padding: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* 1. Header Kop Surat Asli */
        .kop-header-container {
            width: 100%;
            flex-shrink: 0;
            line-height: 0;
        }

        .kop-header-img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* 2. Body Form */
        .form-body {
            padding: 2px 14mm 4px 14mm;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-grow: 1;
        }

        .doc-title {
            text-align: center;
            font-size: 13.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 1px 0 3px 0;
            color: #000;
        }

        /* Table & Boxes */
        .section-banner {
            background-color: #000;
            color: #fff;
            font-weight: 700;
            font-size: 8pt;
            padding: 2.5px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-border-box {
            border: 1px solid #000;
            width: 100%;
        }

        .row-item {
            display: flex;
            border-bottom: 1px solid #000;
            font-size: 7.8pt;
        }

        .row-item:last-child {
            border-bottom: none;
        }

        .cell-label {
            width: 180px;
            padding: 2px 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-right: 1px solid #000;
            background: #fff;
            flex-shrink: 0;
        }

        .cell-value {
            padding: 2px 8px;
            flex-grow: 1;
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #111;
        }

        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .sub-col {
            display: flex;
            border-right: 1px solid #000;
        }

        .sub-col:last-child {
            border-right: none;
        }

        .sub-label {
            width: 125px;
            padding: 2px 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-right: 1px solid #000;
            flex-shrink: 0;
        }

        .sub-value {
            padding: 2px 8px;
            flex-grow: 1;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        /* Checkbox */
        .cb-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .cb-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .cb-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            font-size: 8pt;
            font-weight: 800;
            line-height: 1;
        }

        /* Statement & Signatures */
        .statement-text {
            font-size: 7.5pt;
            font-style: italic;
            text-align: center;
            margin: 3px 0 1px 0;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1px;
            margin-bottom: 2px;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 1px 12px;
        }

        .sig-role {
            font-weight: 700;
            font-size: 8pt;
            margin-bottom: 35px;
        }

        .sig-name {
            font-weight: 700;
            font-size: 7.8pt;
            text-decoration: underline;
        }

        .sig-sub {
            font-size: 6.8pt;
            color: #333;
            margin-top: 1px;
        }

        /* Provider & Terms Section */
        .provider-label {
            font-size: 7pt;
            font-style: italic;
            margin-bottom: 1px;
            color: #333;
        }

        .provider-box {
            border: 1px solid #000;
            display: grid;
            grid-template-columns: 1.45fr 1fr;
        }

        .terms-box {
            padding: 3px 7px;
            border-right: 1px solid #000;
            font-size: 6.8pt;
        }

        .terms-title {
            font-weight: 700;
            margin-bottom: 2px;
            font-size: 7.2pt;
        }

        .terms-list {
            padding-left: 13px;
            margin: 0;
            line-height: 1.25;
        }

        .terms-list li {
            margin-bottom: 1px;
        }

        .docs-box {
            padding: 3px 7px;
            font-size: 6.8pt;
        }

        .docs-title {
            font-weight: 700;
            font-size: 7.2pt;
            margin-bottom: 2px;
        }

        .docs-list {
            padding-left: 13px;
            margin-top: 2px;
            list-style-type: none;
        }

        .docs-list li {
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* 3. Footer Asli */
        .kop-footer-container {
            width: 100%;
            flex-shrink: 0;
            line-height: 0;
        }

        .kop-footer-img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                font-size: 7.8pt !important;
            }

            .screen-toolbar, .no-print {
                display: none !important;
            }

            .page-container {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .paper-sheet {
                width: 100% !important;
                min-height: 100vh !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }

            .kop-header-container, .kop-footer-container {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .section-banner {
                background-color: #000 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

@php
    $headerPath = public_path('assets/images/kop_header.png');
    $footerPath = public_path('assets/images/kop_footer.png');
    
    $headerBase64 = file_exists($headerPath) 
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerPath)) 
        : asset('assets/images/kop_header.png');
        
    $footerBase64 = file_exists($footerPath) 
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerPath)) 
        : asset('assets/images/kop_footer.png');
@endphp

    <!-- Top Floating Toolbar (Hidden on Print) -->
    <div class="screen-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-badge">FORMULIR BERLANGGANAN</span>
            <div>
                <strong style="font-size: 13px;">{{ $customer->nama_pelanggan }}</strong>
                <span style="color: #94a3b8; font-size: 12px; margin-left: 6px;">(No. Internet: {{ $customer->nomor_internet }})</span>
            </div>
        </div>

        <div class="toolbar-actions">
            <!-- Dropdown Opsi Download -->
            <div class="dropdown" id="downloadDropdown">
                <button type="button" onclick="toggleDownloadDropdown(event)" class="btn-action btn-download" title="Pilihan Format Unduh Dokumen">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Download / Unduh</span>
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div class="dropdown-menu" id="downloadMenu">
                    <!-- Option 1: PDF -->
                    <button type="button" onclick="downloadPDF()" class="dropdown-item">
                        <div class="dropdown-icon" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download PDF (.pdf)</div>
                            <span class="dropdown-item-desc">Format standar A4 siap cetak & legalisir</span>
                        </div>
                    </button>

                    <!-- Option 2: Word (DOC) -->
                    <button type="button" onclick="downloadWord()" class="dropdown-item">
                        <div class="dropdown-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download Word (.doc)</div>
                            <span class="dropdown-item-desc">Format Microsoft Word dapat diedit</span>
                        </div>
                    </button>

                    <!-- Option 3: Gambar (PNG) -->
                    <button type="button" onclick="downloadPNG()" class="dropdown-item">
                        <div class="dropdown-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download Gambar (.png)</div>
                            <span class="dropdown-item-desc">Resolusi tinggi untuk share ke WA</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Direct Quick Action PDF Button -->
            <button id="btn-quick-pdf" onclick="downloadPDF()" class="btn-action btn-download" style="padding: 7px 11px;" title="Unduh File PDF">
                <span>PDF</span>
            </button>

            <!-- Direct Quick Action Word Button -->
            <button id="btn-quick-word" onclick="downloadWord()" class="btn-action btn-word" style="padding: 7px 11px;" title="Unduh Dokumen Word .doc">
                <span>Word (.doc)</span>
            </button>

            <!-- Tombol Cetak / Print -->
            <button onclick="window.print()" class="btn-action btn-print" title="Cetak / Print Dokumen (Ctrl+P)">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Form</span>
            </button>

            <!-- Tombol Kembali -->
            <a href="javascript:history.back()" class="btn-action btn-back" title="Kembali ke Halaman Sebelumnya">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="page-container">
        <div class="paper-sheet" id="paper-sheet-container">
            
            <!-- 1. KOP SURAT ASLI (EKSTRAKSI ASLI DARI TEMPLATE MASTER) -->
            <div class="kop-header-container">
                <img src="{{ asset('assets/images/kop_header.png') }}" alt="Kop Surat PT Media Solusi Network" class="kop-header-img" id="kop-header-image">
            </div>

            <!-- 2. FORM BODY -->
            <div class="form-body">
                
                <!-- Document Title -->
                <div class="doc-title">
                    FORM BERLANGGANAN
                </div>

                <!-- Informasi Pelanggan Box -->
                <div class="table-border-box">
                    <div class="grid-2-col">
                        <div class="sub-col">
                            <div class="sub-label"><span>Nomor FB</span><span>:</span></div>
                            <div class="sub-value">FB-{{ $customer->nomor_internet }}</div>
                        </div>
                        <div class="sub-col">
                            <div class="sub-label"><span>Tanggal Registrasi</span><span>:</span></div>
                            <div class="sub-value">
                                {{ $customer->date_create ? \Carbon\Carbon::parse($customer->date_create)->translatedFormat('d F Y') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 1: DATA DIRI -->
                <div class="section-banner">
                    DATA DIRI
                </div>

                <div class="table-border-box">
                    <!-- Row 1: Nama Lengkap -->
                    <div class="row-item">
                        <div class="cell-label"><span>Nama Lengkap</span><span>:</span></div>
                        <div class="cell-value">{{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: '-') }}</div>
                    </div>

                    <!-- Row 2: Alamat Lengkap KTP -->
                    <div class="row-item">
                        <div class="cell-label"><span>Alamat Lengkap KTP</span><span>:</span></div>
                        <div class="cell-value">
                            @if(!empty($customer->alamat_k))
                                {{ $customer->alamat_k }}
                            @else
                                {{ $customer->alamat_ktp ?: '-' }}
                                @if(!empty($customer->rt_ktp) || !empty($customer->rw_ktp))
                                    , RT {{ $customer->rt_ktp ?: '00' }} / RW {{ $customer->rw_ktp ?: '00' }}
                                @endif
                                @if(!empty($customer->nama_kelurahan))
                                    , Kel. {{ $customer->nama_kelurahan }}
                                @endif
                                @if(!empty($customer->nama_kecamatan))
                                    , Kec. {{ $customer->nama_kecamatan }}
                                @endif
                                @if(!empty($customer->nama_kota))
                                    , {{ $customer->nama_kota }}
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Row 3: Nama Pemohon -->
                    <div class="row-item">
                        <div class="cell-label"><span>Nama Pemohon</span><span>:</span></div>
                        <div class="cell-value">{{ $customer->pic ?: ($customer->nama_pelanggan ?: ($customer->nama_penduduk ?: '-')) }}</div>
                    </div>

                    <!-- Row 4: No. KTP / SIM / Paspor -->
                    <div class="row-item">
                        <div class="cell-label"><span>No. KTP / SIM / Paspor</span><span>:</span></div>
                        <div class="cell-value">{{ $customer->nik_penduduk ?: '-' }}</div>
                    </div>

                    <!-- Row 5: Telepon / FAX & Contact Person -->
                    <div class="row-item">
                        <div class="grid-2-col" style="width: 100%;">
                            <div class="sub-col">
                                <div class="sub-label" style="width: 180px;"><span>Telepon / FAX</span><span>:</span></div>
                                <div class="sub-value">{{ $customer->nomor_hp_2 ?: '-' }}</div>
                            </div>
                            <div class="sub-col">
                                <div class="sub-label"><span>Contact Person</span><span>:</span></div>
                                <div class="sub-value">{{ $customer->pic ?: ($customer->nama_pelanggan ?: ($customer->nama_penduduk ?: '-')) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 6: HP & E-Mail -->
                    <div class="row-item">
                        <div class="grid-2-col" style="width: 100%;">
                            <div class="sub-col">
                                <div class="sub-label" style="width: 180px;"><span>HP</span><span>:</span></div>
                                <div class="sub-value">{{ $customer->nomor_hp ?: '-' }}</div>
                            </div>
                            <div class="sub-col">
                                <div class="sub-label"><span>E-Mail</span><span>:</span></div>
                                <div class="sub-value">{{ $customer->email ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: INFORMASI PEMASANGAN -->
                <div class="section-banner">
                    INFORMASI PEMASANGAN
                </div>

                <div class="table-border-box">
                    <!-- Row 1: Jenis Tempat Tinggal -->
                    <div class="row-item">
                        <div class="cell-label"><span>Jenis Tempat Tinggal</span><span>:</span></div>
                        <div class="cell-value">
                            @php
                                $jns = strtoupper($customer->jenis_bangunan ?? '');
                                $isRumah = str_contains($jns, 'RUMAH') || empty($jns);
                                $isKantor = str_contains($jns, 'KANTOR') || str_contains($jns, 'OFFICE');
                                $isLainnya = !$isRumah && !$isKantor && !empty($jns);
                            @endphp
                            <div class="cb-group">
                                <div class="cb-item">
                                    <span class="cb-box">{{ $isRumah ? '✓' : '' }}</span>
                                    <span>Rumah Tinggal</span>
                                </div>
                                <div class="cb-item">
                                    <span class="cb-box">{{ $isKantor ? '✓' : '' }}</span>
                                    <span>Kantor</span>
                                </div>
                                <div class="cb-item">
                                    <span class="cb-box">{{ $isLainnya ? '✓' : '' }}</span>
                                    <span>Lainnya{{ $isLainnya ? ' (' . $customer->jenis_bangunan . ')' : '/' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Alamat Penagihan -->
                    <div class="row-item">
                        <div class="cell-label"><span>Alamat Penagihan</span><span>:</span></div>
                        <div class="cell-value">
                            @if(!empty($customer->alamat_k))
                                {{ $customer->alamat_k }}
                            @elseif(!empty($customer->alamat_ktp))
                                {{ $customer->alamat_ktp }}
                                @if(!empty($customer->rt_ktp) || !empty($customer->rw_ktp))
                                    , RT {{ $customer->rt_ktp ?: '00' }} / RW {{ $customer->rw_ktp ?: '00' }}
                                @endif
                                @if(!empty($customer->nama_kelurahan))
                                    , Kel. {{ $customer->nama_kelurahan }}
                                @endif
                                @if(!empty($customer->nama_kecamatan))
                                    , Kec. {{ $customer->nama_kecamatan }}
                                @endif
                                @if(!empty($customer->nama_kota))
                                    , {{ $customer->nama_kota }}
                                @endif
                            @else
                                {{ $customer->alamat_pasang ?: '-' }}
                            @endif
                        </div>
                    </div>

                    <!-- Row 3: Alamat Pemasangan -->
                    <div class="row-item">
                        <div class="cell-label"><span>Alamat Pemasangan</span><span>:</span></div>
                        <div class="cell-value">
                            @if(!empty($customer->alamat_p))
                                {{ $customer->alamat_p }}
                            @else
                                {{ $customer->alamat_pasang ?: '-' }}
                                @if(!empty($customer->nomor_bangunan))
                                    No. {{ $customer->nomor_bangunan }}
                                @endif
                                @if(!empty($customer->rt_pasang) || !empty($customer->rw_pasang))
                                    , RT {{ $customer->rt_pasang ?: '00' }} / RW {{ $customer->rw_pasang ?: '00' }}
                                @endif
                                @if(!empty($customer->nama_kelurahan_pasang))
                                    , Kel. {{ $customer->nama_kelurahan_pasang }}
                                @endif
                                @if(!empty($customer->nama_kecamatan_pasang))
                                    , Kec. {{ $customer->nama_kecamatan_pasang }}
                                @endif
                                @if(!empty($customer->nama_kota_pasang))
                                    , {{ $customer->nama_kota_pasang }}
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Row 4: Nomor Hp Penagihan -->
                    <div class="row-item">
                        <div class="cell-label"><span>Nomor Hp Penagihan</span><span>:</span></div>
                        <div class="cell-value">{{ $customer->nomor_hp ?: '-' }}</div>
                    </div>

                    <!-- Row 5: Jenis Produk -->
                    <div class="row-item">
                        <div class="cell-label"><span>Jenis Produk</span><span>:</span></div>
                        <div class="cell-value">
                            ( {{ $customer->nama_kategori_bandwith ?: ($customer->alias_nama_kategori ?: ($customer->group_layanan ?: 'INTERNET BROADBAND')) }} )
                        </div>
                    </div>

                    <!-- Row 6: Kapasitas Layanan -->
                    <div class="row-item">
                        <div class="cell-label"><span>Kapasitas Layanan</span><span>:</span></div>
                        <div class="cell-value">
                            ( {{ $customer->nominal_bandwith ?: '10' }} Mbps )
                        </div>
                    </div>

                    <!-- Row 7: Biaya Layanan -->
                    <div class="row-item">
                        <div class="cell-label"><span>Biaya Layanan</span><span>:</span></div>
                        <div class="cell-value">
                            ( Rp {{ number_format($customer->harga_bandwith ?: 0, 0, ',', '.') }} )
                        </div>
                    </div>

                    <!-- Row 8: Biaya Registrasi -->
                    <div class="row-item">
                        <div class="cell-label"><span>Biaya Registrasi</span><span>:</span></div>
                        <div class="cell-value">
                            ( Rp {{ number_format($customer->biaya_reg ?: 0, 0, ',', '.') }} )
                        </div>
                    </div>

                    <!-- Row 9: Perangkat -->
                    <div class="row-item">
                        <div class="cell-label"><span>Perangkat</span><span>:</span></div>
                        <div class="cell-value" style="justify-content: space-between;">
                            <span>
                                @if(isset($perangkats) && $perangkats->count() > 0)
                                    {{ $perangkats->pluck('nama_barang')->join(', ') }}
                                @else
                                    ONT Standard FTTH
                                @endif
                            </span>
                            <span style="margin-left: 20px;">
                                SN : {{ $customer->note_request ?: ($customer->ont_us ?: '-') }}
                            </span>
                        </div>
                    </div>

                    <!-- Row Note: Termasuk PPN -->
                    <div class="row-item" style="background: #f8fafc;">
                        <div class="cell-value" style="font-size: 7.5pt; font-style: italic; color: #475569; padding: 1.5px 8px;">
                            ( Seluruh biaya di atas sudah termasuk PPN {{ !empty($customer->ppn_nom) ? (floatval($customer->ppn_nom) * 100) . '%' : '11%' }} )
                        </div>
                    </div>

                    <!-- Row 10: Kontrak -->
                    <div class="row-item">
                        <div class="cell-label"><span>Kontrak</span><span>:</span></div>
                        <div class="cell-value">Minimal 6 Bulan</div>
                    </div>

                    <!-- Row 11: Tanggal Jadwal Pemasangan -->
                    <div class="row-item">
                        <div class="cell-label"><span>Tanggal Jadwal Pemasangan</span><span>:</span></div>
                        <div class="cell-value">
                            @php
                                $tglPasang = $customer->instalasi_date_start 
                                    ?: ($customer->survey_date_start ?: $customer->date_create);
                            @endphp
                            {{ $tglPasang ? \Carbon\Carbon::parse($tglPasang)->translatedFormat('d F Y') : '-' }}
                        </div>
                    </div>
                </div>

                <!-- Statement -->
                <div class="statement-text">
                    Dengan formulir berlangganan ini informasi yang kami berikan adalah benar adanya, dan telah memenuhi ketentuan dan syarat berlangganan
                </div>

                <!-- Signatures -->
                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="sig-role">PT MSN</div>
                            <div class="sig-name">( {{ $customer->nama_sales ?: 'Sales PT MSN' }} )</div>
                            <div class="sig-sub">Nama dan Tanda Tangan</div>
                        </td>
                        <td>
                            <div class="sig-role">Pemohon</div>
                            <div class="sig-name">( {{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: 'Pelanggan') }} )</div>
                            <div class="sig-sub">Nama dan Tanda Tangan</div>
                        </td>
                    </tr>
                </table>

                <!-- Provider Section -->
                <div>
                    <div class="provider-label">Untuk diisi oleh Provider</div>
                    <div class="provider-box">
                        <!-- Left: Ketentuan Berlangganan -->
                        <div class="terms-box">
                            <div class="terms-title">Ketentuan berlangganan Layanan Internet</div>
                            <ol class="terms-list">
                                <li>Pembayaran Layanan Internet dilakukan <strong>AWAL BULAN</strong>.</li>
                                <li>Link Pembayaran Akan dikirim melalui WhatsApp ke nomor HP Penaggihan yang sudah di isi.</li>
                                <li>Berlangganan layanan internet <strong>Minimal 6 BULAN</strong>, Akan dikenakan Denda <strong>500Ribu</strong> Jika Berhenti Sebelum 6 Bulan.</li>
                                <li>Perangkat yang terpasang adalah <strong>MILIK PT.MSN</strong> dikembalikan jika sudah tidak berlangganan.</li>
                                <li>Kehilanggan perangkat dikenakan denda sebesar <strong>300Ribu</strong>.</li>
                                <li>PT. MSN hanya menyediakan kabel dari tiang terdekat ke rumah pelanggan <strong>MAX 300Meter</strong>, jika ada kelebihan kabel biaya permeter sebesar <strong>1000 Rupiah</strong>, Jika pelanggan ingin tetap melakukan pemasangan internet.</li>
                            </ol>
                        </div>

                        <!-- Right: Kelengkapan Dokumen -->
                        <div class="docs-box">
                            <div class="docs-title">Hanya Untuk kelengkapan Pelanggan Baru</div>
                            <div style="font-weight: 600; font-size: 7pt; margin-top: 2px;">Kelengkapan Dokumen :</div>
                            <ul class="docs-list">
                                <li>
                                    <span class="cb-box" style="width: 11px; height: 11px; font-size: 7.5pt;">✓</span>
                                    <span>Fotocopy KTP / NPWP</span>
                                </li>
                                <li>
                                    <span class="cb-box" style="width: 11px; height: 11px; font-size: 7.5pt;"></span>
                                    <span>Lainnya ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. FOOTER ASLI (EKSTRAKSI ASLI DARI TEMPLATE MASTER) -->
            <div class="kop-footer-container">
                <img src="{{ asset('assets/images/kop_footer.png') }}" alt="Footer PT Media Solusi Network" class="kop-footer-img" id="kop-footer-image">
            </div>

        </div>
    </div>

    <!-- html2pdf.js for direct client-side PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        const customerName = "{{ Str::slug($customer->nama_pelanggan ?: 'pelanggan', '_') }}";
        const nomorInternet = "{{ $customer->nomor_internet }}";
        const headerBase64 = @json($headerBase64);
        const footerBase64 = @json($footerBase64);

        // Toggle dropdown
        function toggleDownloadDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('downloadMenu');
            menu.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('downloadDropdown');
            const menu = document.getElementById('downloadMenu');
            if (dropdown && !dropdown.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // 1. Download PDF (html2pdf)
        function downloadPDF() {
            const menu = document.getElementById('downloadMenu');
            if (menu) menu.classList.remove('show');

            const element = document.getElementById('paper-sheet-container');
            const filename = `Form_Berlangganan_${nomorInternet}_${customerName}.pdf`;

            const btn = document.getElementById('btn-quick-pdf');
            const origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.innerHTML = `<span>Proses...</span>`;
                btn.disabled = true;
            }

            const opt = {
                margin:       0,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 3, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                if (btn) {
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }
            }).catch(err => {
                console.error("PDF generation error:", err);
                if (btn) {
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }
                window.print(); // Fallback to print dialog
            });
        }

        // 2. Download Microsoft Word (.doc)
        function downloadWord() {
            const menu = document.getElementById('downloadMenu');
            if (menu) menu.classList.remove('show');

            const btn = document.getElementById('btn-quick-word');
            const origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.innerHTML = `<span>Proses...</span>`;
                btn.disabled = true;
            }

            try {
                const element = document.getElementById('paper-sheet-container');
                const clone = element.cloneNode(true);

                // Replace image src with base64 for offline portability
                const headerImg = clone.querySelector('#kop-header-image');
                if (headerImg && headerBase64) headerImg.src = headerBase64;

                const footerImg = clone.querySelector('#kop-footer-image');
                if (footerImg && footerBase64) footerImg.src = footerBase64;

                const wordHtml = `
                    <html xmlns:o='urn:schemas-microsoft-com:office:office' 
                          xmlns:w='urn:schemas-microsoft-com:office:word' 
                          xmlns='http://www.w3.org/TR/REC-html40'>
                    <head>
                        <meta charset='utf-8'>
                        <title>Form Berlangganan - ${nomorInternet}</title>
                        <!--[if gte mso 9]>
                        <xml>
                            <w:WordDocument>
                                <w:View>Print</w:View>
                                <w:Zoom>100</w:Zoom>
                                <w:DoNotOptimizeForBrowser/>
                            </w:WordDocument>
                        </xml>
                        <![endif]-->
                        <style>
                            @page Section1 {
                                size: 210mm 297mm;
                                margin: 8mm 10mm 8mm 10mm;
                                mso-header-margin: 0mm;
                                mso-footer-margin: 0mm;
                                mso-paper-source: 0;
                            }
                            div.Section1 { page: Section1; }
                            body {
                                font-family: Arial, sans-serif;
                                font-size: 8pt;
                                line-height: 1.25;
                                color: #000;
                            }
                            table { border-collapse: collapse; width: 100%; }
                            .doc-title { text-align: center; font-size: 14pt; font-weight: bold; margin: 4px 0 8px 0; }
                            .section-banner { background-color: #000; color: #fff; font-weight: bold; font-size: 8pt; padding: 3px 6px; }
                            .table-border-box { border: 1px solid #000; width: 100%; }
                            .row-item { border-bottom: 1px solid #000; display: flex; font-size: 8pt; }
                            .cell-label { width: 180px; font-weight: bold; border-right: 1px solid #000; padding: 3px; }
                            .cell-value { padding: 3px 6px; }
                            .signature-table td { text-align: center; width: 50%; padding: 10px; }
                            .sig-role { font-weight: bold; margin-bottom: 45px; }
                            .sig-name { font-weight: bold; text-decoration: underline; }
                            .provider-box { border: 1px solid #000; }
                            .terms-box { padding: 4px; font-size: 7pt; }
                            .docs-box { padding: 4px; font-size: 7pt; }
                            img { max-width: 100%; height: auto; }
                        </style>
                    </head>
                    <body>
                        <div class="Section1">
                            ${clone.innerHTML}
                        </div>
                    </body>
                    </html>
                `;

                const blob = new Blob(['\ufeff' + wordHtml], {
                    type: 'application/msword;charset=utf-8'
                });

                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `Form_Berlangganan_${nomorInternet}_${customerName}.doc`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            } catch (err) {
                console.error("Word export error:", err);
            } finally {
                if (btn) {
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }
            }
        }

        // 3. Download PNG Image
        function downloadPNG() {
            const menu = document.getElementById('downloadMenu');
            if (menu) menu.classList.remove('show');

            const element = document.getElementById('paper-sheet-container');
            const filename = `Form_Berlangganan_${nomorInternet}_${customerName}.png`;

            if (typeof html2canvas === 'function') {
                html2canvas(element, { scale: 2.5, useCORS: true }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = filename;
                    link.href = canvas.toDataURL('image/png');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }).catch(err => {
                    console.error("PNG export error:", err);
                });
            } else {
                downloadPDF();
            }
        }

        // Auto trigger download based on query parameter (?download=pdf|word|png|1)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const dl = urlParams.get('download') || urlParams.get('format') || urlParams.get('action');
            
            if (dl === '1' || dl === 'pdf') {
                setTimeout(downloadPDF, 600);
            } else if (dl === 'word' || dl === 'doc') {
                setTimeout(downloadWord, 600);
            } else if (dl === 'png' || dl === 'image') {
                setTimeout(downloadPNG, 600);
            } else if (urlParams.get('print') === '1') {
                setTimeout(() => window.print(), 600);
            }
        });
    </script>

</body>
</html>

