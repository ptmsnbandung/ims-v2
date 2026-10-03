<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $noInvoice }} - {{ $customerName }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Times New Roman & Inter -->
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
            font-family: 'Times New Roman', Times, serif, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
            background-color: #0f172a;
            color: #000;
            font-size: 9.5pt;
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
            font-family: 'Inter', sans-serif;
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
            font-family: 'Inter', sans-serif;
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

        /* 1. Header Kop Surat */
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
            padding: 4px 15mm 4px 15mm;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-grow: 1;
        }

        .doc-title-container {
            text-align: center;
            margin: 2px 0 6px 0;
            position: relative;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 0.5px;
            color: #000;
            display: inline-block;
            border-bottom: 1.5px solid #000;
            padding-bottom: 1px;
        }

        .tagihan-badge {
            display: inline-block;
            border: 1px solid #333;
            padding: 1px 8px;
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 4px;
            background: #f8fafc;
            border-radius: 3px;
        }

        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 9pt;
        }

        .info-table td {
            vertical-align: top;
            padding: 1px 4px;
        }

        .info-customer {
            width: 48%;
            padding-right: 12px;
        }

        .info-meta {
            width: 52%;
        }

        .meta-row {
            display: flex;
            margin-bottom: 2px;
        }

        .meta-label {
            width: 130px;
            flex-shrink: 0;
        }

        .meta-sep {
            margin: 0 4px;
        }

        .meta-value {
            font-weight: 500;
        }

        /* Billing Main Table */
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 6px;
            font-size: 9pt;
        }

        .bill-table th, .bill-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .bill-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
        }

        .bill-table .spacer-row td {
            height: 28px;
            border-top: none;
            border-bottom: none;
        }

        .terbilang-cell {
            font-style: italic;
            font-size: 8.5pt;
            vertical-align: top;
            background: #fafafa;
        }

        /* Signatures & Payment Block */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 4px;
            font-size: 9pt;
        }

        .sig-table td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 4px;
        }

        .sig-col-header {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            background-color: #f8fafc;
        }

        .sig-col-content {
            height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            padding: 4px 2px;
            position: relative;
        }

        .stamp-img {
            max-height: 68px;
            max-width: 140px;
            object-fit: contain;
            margin: 0 auto;
        }

        .pay-link-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 4px;
        }

        .pay-link-btn {
            display: inline-block;
            font-size: 7.5pt;
            color: #0284c7;
            text-decoration: underline;
            word-break: break-all;
            max-width: 150px;
            text-align: center;
        }

        /* Perforated Divider */
        .perforated-divider {
            margin: 6px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            color: #475569;
            font-size: 8pt;
            user-select: none;
        }

        .perforated-line {
            flex-grow: 1;
            border-top: 1.5px dashed #64748b;
        }

        /* Slip Pembayaran */
        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 3px;
        }

        .slip-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 4px;
        }

        .slip-table td {
            vertical-align: top;
            padding: 1.5px 2px;
        }

        .slip-sig-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-top: 2px;
            margin-bottom: 4px;
        }

        .slip-sig-table td {
            text-align: center;
            width: 50%;
            vertical-align: top;
        }

        .notes-box {
            font-family: 'Verdana', sans-serif;
            font-size: 6.8pt;
            line-height: 1.25;
            color: #1e293b;
            margin-top: 2px;
            padding-top: 2px;
            border-top: 0.5px solid #cbd5e1;
        }

        .notes-box strong {
            font-weight: bold;
        }

        /* 3. Footer Kop Surat */
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

        @page {
            size: A4 portrait;
            margin: 0;
        }

        /* Print Media Styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .screen-toolbar {
                display: none !important;
            }

            .page-container {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .paper-sheet {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                min-height: 100vh !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
            }

            .kop-header-container, .kop-footer-container {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- SCREEN TOOLBAR -->
    <div class="screen-toolbar">
        <div class="toolbar-info">
            <span class="toolbar-badge">INVOICE DOCX</span>
            <span style="font-size: 13px; font-weight: 600;">No. Tagihan: {{ $noInvoice }}</span>
            <span style="font-size: 12px; color: #94a3b8;">&bull;</span>
            <span style="font-size: 12px; color: #cbd5e1;">{{ $customerName }} ({{ $nomorInternet }})</span>
        </div>

        <div class="toolbar-actions">
            <!-- Dropdown Format Unduh -->
            <div class="dropdown" id="downloadDropdown">
                <button type="button" onclick="toggleDownloadDropdown(event)" class="btn-action btn-download" title="Pilihan Format Unduh Dokumen">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Unduh Dokumen</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div class="dropdown-menu" id="downloadMenu">
                    <!-- Option 1: PDF -->
                    <button type="button" onclick="downloadPDF()" class="dropdown-item">
                        <div class="dropdown-icon" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download PDF (.pdf)</div>
                            <span class="dropdown-item-desc">Format dokumen resmi siap cetak</span>
                        </div>
                    </button>

                    <!-- Option 2: Word (.doc) -->
                    <button type="button" onclick="downloadWord()" class="dropdown-item">
                        <div class="dropdown-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download Word (.doc)</div>
                            <span class="dropdown-item-desc">Editable Microsoft Word template</span>
                        </div>
                    </button>

                    <!-- Option 3: Gambar PNG -->
                    <button type="button" onclick="downloadImage()" class="dropdown-item">
                        <div class="dropdown-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download Gambar (.png)</div>
                            <span class="dropdown-item-desc">Gambar resolusi tinggi HD</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Direct Quick Action PDF Button -->
            <button id="btn-quick-pdf" onclick="downloadPDF()" class="btn-action btn-download" style="padding: 7px 11px;" title="Unduh File PDF">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>PDF</span>
            </button>

            <!-- Direct Quick Action Word Button -->
            <button id="btn-quick-word" onclick="downloadWord()" class="btn-action btn-word" style="padding: 7px 11px;" title="Unduh Dokumen Word .doc">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span>Word</span>
            </button>

            <button onclick="window.print()" class="btn-action btn-print" title="Cetak / Print Dokumen (Ctrl+P)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Form</span>
            </button>

            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('finance.billing-layanan') }}" class="btn-action btn-back" title="Kembali">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
                <span>Tutup</span>
            </a>
        </div>
    </div>

    <!-- MAIN PAGE CONTAINER -->
    <div class="page-container">
        <div class="paper-sheet" id="paper-sheet-container">
            
            <!-- 1. KOP SURAT ASLI (HEADER BANNER) -->
            <div class="kop-header-container">
                <img src="{{ $headerBase64 }}" alt="Kop Surat PT Media Solusi Network" class="kop-header-img" id="kop-header-image">
            </div>

            <!-- 2. BODY INVOICE -->
            <div class="form-body">
                
                <!-- TITLE -->
                <div class="doc-title-container">
                    <div class="doc-title">INVOICE</div>
                </div>

                <!-- TAGIHAN BADGE -->
                <div>
                    <span class="tagihan-badge">Tagihan</span>
                </div>

                <!-- CUSTOMER & INVOICE META TABLE -->
                <table class="info-table">
                    <tr>
                        <td class="info-customer">
                            <div style="font-weight: bold; font-size: 10pt; text-transform: uppercase;">{{ $customerName }}</div>
                            <div style="font-size: 9pt; color: #1e293b; margin-top: 2px; line-height: 1.3;">
                                {{ $alamat }}
                            </div>
                        </td>
                        <td class="info-meta">
                            <div style="font-weight: bold; font-size: 9.5pt; margin-bottom: 2px;">PT MEDIA SOLUSI NETWORK</div>
                            <table style="width: 100%; font-size: 9pt; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 125px; padding: 1px 0;">No Tagihan</td>
                                    <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                                    <td style="font-weight: bold; padding: 1px 0;">{{ $noInvoice }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Nomor Pelanggan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="font-weight: bold; padding: 1px 0;">{{ $nomorInternet }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Periode Pemakaian</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $periodeTagihan }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Jatuh Tempo</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $jatuhTempo }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- MAIN BILL TABLE -->
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th style="width: 6%;">No</th>
                            <th style="width: 54%;">Layanan</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 25%;">Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($items->isNotEmpty())
                            @foreach($items as $idx => $item)
                            <tr>
                                <td style="text-align: center;">{{ $idx + 1 }}</td>
                                <td>{{ $item->komponen ?? $namaLayanan }}</td>
                                <td style="text-align: center;">{{ $item->qty ?? 1 }}</td>
                                <td style="text-align: right;">Rp {{ number_format((float) ($item->biaya ?? $subtotal), 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td style="text-align: center;">1</td>
                                <td>{{ $namaLayanan }}</td>
                                <td style="text-align: center;">1</td>
                                <td style="text-align: right;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        <!-- Spacer row matching docx layout -->
                        <tr class="spacer-row">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- Terbilang & Potongan -->
                        <tr>
                            <td colspan="2" rowspan="2" class="terbilang-cell">
                                <strong>Terbilang :</strong> <em>{{ $terbilangText }}</em>
                            </td>
                            <td style="text-align: right; font-weight: 500;">POTONGAN</td>
                            <td style="text-align: right;">
                                {{ $potongan > 0 ? 'Rp ' . number_format($potongan, 0, ',', '.') : '-' }}
                            </td>
                        </tr>

                        <!-- PPN -->
                        <tr>
                            <td style="text-align: right; font-weight: 500;">PPN</td>
                            <td style="text-align: right;">
                                {{ $ppn > 0 ? 'Rp ' . number_format($ppn, 0, ',', '.') : '-' }}
                            </td>
                        </tr>

                        <!-- Total Tagihan -->
                        <tr style="background-color: #f8fafc; font-weight: bold;">
                            <td colspan="3" style="text-align: center; font-size: 9.5pt; padding: 5px;">
                                TAGIHAN BULAN INI
                            </td>
                            <td style="text-align: right; font-size: 10pt; padding: 5px;">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- SIGNATURES & PAYMENT METHOD BLOCK (3 COLUMNS MATCHING DOCX) -->
                <table class="sig-table">
                    <thead>
                        <tr>
                            <th style="width: 32%;" class="sig-col-header">Pembayaran</th>
                            <th style="width: 36%;" class="sig-col-header">Mengetahui</th>
                            <th style="width: 32%;" class="sig-col-header">Pelanggan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <!-- Column 1: Pembayaran & Link / QR -->
                            <td>
                                <div class="sig-col-content pay-link-box">
                                    <div style="font-size: 8pt; font-weight: bold; color: #1e293b;">LINK PEMBAYARAN</div>
                                    <a href="{{ $paymentUrl }}" target="_blank" class="pay-link-btn">
                                        {{ Str::limit($paymentUrl, 38) }}
                                    </a>
                                    <div style="font-size: 7pt; color: #64748b;">(Klik / Salin Link untuk Pembayaran)</div>
                                </div>
                            </td>

                            <!-- Column 2: Mengetahui (Stempel & TTD Keuangan Ida Mayasari) -->
                            <td>
                                <div class="sig-col-content">
                                    @if($stampBase64)
                                        <img src="{{ $stampBase64 }}" alt="Stempel Keuangan PT MSN" class="stamp-img">
                                    @else
                                        <div style="height: 50px;"></div>
                                    @endif
                                    <div>
                                        <div style="font-weight: bold; font-size: 9pt; border-bottom: 1px solid #000; display: inline-block; padding-bottom: 1px;">Ida Mayasari</div>
                                        <div style="font-size: 8pt; color: #334155;">Keuangan</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 3: Pelanggan (TTD & Nama) -->
                            <td>
                                <div class="sig-col-content">
                                    <div style="height: 50px;"></div>
                                    <div>
                                        <div style="font-weight: bold; font-size: 9pt; border-bottom: 1px solid #000; display: inline-block; min-width: 120px; padding-bottom: 1px;">&nbsp;</div>
                                        <div style="font-size: 8pt; color: #334155;">TTD &amp; Nama</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- PERFORATED DIVIDER / GUNTING -->
                <div class="perforated-divider">
                    <span style="font-size: 11pt;">✂</span>
                    <div class="perforated-line"></div>
                </div>

                <!-- SLIP PEMBAYARAN -->
                <div class="slip-header">
                    <span>PT. MEDIA SOLUSI NETWORK</span>
                    <span>SLIP PEMBAYARAN</span>
                </div>

                <table class="slip-table">
                    <tr>
                        <td style="width: 50%;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 110px; padding: 1px 0;">Nomor Tagihan</td>
                                    <td style="width: 8px; text-align: center; padding: 1px 0;">:</td>
                                    <td style="font-weight: bold; padding: 1px 0;">{{ $noInvoice }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Nomor Pelanggan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="font-weight: bold; padding: 1px 0;">{{ $nomorInternet }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Nama Pelanggan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $customerName }}</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width: 50%; padding-left: 10px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 120px; padding: 1px 0;">Periode Pemakaian</td>
                                    <td style="width: 8px; text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $periodeTagihan }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Jatuh Tempo</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $jatuhTempo }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Jumlah Tagihan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="font-weight: bold; padding: 1px 0;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- SLIP SIGNATURES -->
                <table class="slip-sig-table">
                    <tr>
                        <td style="padding-bottom: 25px;">Petugas</td>
                        <td style="padding-bottom: 25px;">Pelanggan</td>
                    </tr>
                    <tr>
                        <td>
                            <span style="border-top: 1px dotted #64748b; padding-top: 2px; display: inline-block; min-width: 110px;">TTD &amp; Nama</span>
                        </td>
                        <td>
                            <span style="border-top: 1px dotted #64748b; padding-top: 2px; display: inline-block; min-width: 110px;">TTD &amp; Nama</span>
                        </td>
                    </tr>
                </table>

                <!-- CATATAN RESMI -->
                <div class="notes-box">
                    <div style="font-weight: bold; margin-bottom: 1px;">Catatan :</div>
                    <ol style="margin-left: 14px; padding-left: 0;">
                        <li style="margin-bottom: 1px;">
                            Apabila pelanggan belum melakukan pembayaran sampai dengan jatuh tempo (Maksimal Tanggal 20 setiap bulan), maka akan dilakukan pemutusan koneksi sementara terhitung mulai pukul 24.00 pada tanggal akhir periode sebelumnya.
                        </li>
                        <li>
                            Untuk pelanggan yang melakukan pembayaran melalui <strong>Transfer Bank</strong>, mohon memberikan konfirmasi via Whatsapp ke nomor <strong>085220137627</strong> dengan mencantumkan bukti pembayaran.
                        </li>
                    </ol>
                </div>

            </div>

            <!-- 3. FOOTER KOP SURAT (FOOTER BANNER) -->
            <div class="kop-footer-container">
                <img src="{{ $footerBase64 }}" alt="Footer PT Media Solusi Network" class="kop-footer-img" id="kop-footer-image">
            </div>

        </div>
    </div>

    <!-- html2pdf.js for client-side high fidelity PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        const customerName = "{{ Str::slug($customerName ?: 'pelanggan', '_') }}";
        const nomorInternet = "{{ $nomorInternet }}";
        const noInvoice = "{{ Str::slug($noInvoice ?: 'inv', '_') }}";
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
            const filename = `Invoice_${noInvoice}_${nomorInternet}_${customerName}.pdf`;

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
                const sheet = document.getElementById('paper-sheet-container');
                const clone = sheet.cloneNode(true);

                const headerImg = clone.querySelector('#kop-header-image');
                if (headerImg && headerBase64) {
                    headerImg.setAttribute('src', headerBase64);
                }

                const footerImg = clone.querySelector('#kop-footer-image');
                if (footerImg && footerBase64) {
                    footerImg.setAttribute('src', footerBase64);
                }

                const docContent = `
                    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                    <head>
                        <meta charset="utf-8">
                        <title>Invoice - ${noInvoice}</title>
                        <style>
                            @page {
                                size: 210mm 297mm;
                                margin: 0mm 0mm 0mm 0mm;
                                mso-page-orientation: portrait;
                            }
                            body {
                                font-family: 'Times New Roman', serif;
                                font-size: 9.5pt;
                                margin: 0;
                                padding: 0;
                            }
                            table { border-collapse: collapse; }
                            td, th { padding: 4px; }
                        </style>
                    </head>
                    <body>
                        ${clone.outerHTML}
                    </body>
                    </html>
                `;

                const blob = new Blob(['\ufeff', docContent], { type: 'application/msword' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Invoice_${noInvoice}_${nomorInternet}_${customerName}.doc`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (err) {
                console.error("Word export error:", err);
                alert("Gagal mengunduh format Word. Silakan gunakan format PDF atau Cetak.");
            } finally {
                if (btn) {
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }
            }
        }

        // 3. Download Image HD (.png)
        function downloadImage() {
            const menu = document.getElementById('downloadMenu');
            if (menu) menu.classList.remove('show');

            const element = document.getElementById('paper-sheet-container');
            if (typeof html2pdf !== 'undefined' && html2pdf.Worker) {
                const opt = {
                    margin: 0,
                    html2canvas: { scale: 2.5, useCORS: true }
                };
                html2pdf().set(opt).from(element).toImg().outputImg('img').then(img => {
                    const a = document.createElement('a');
                    a.href = img.src;
                    a.download = `Invoice_${noInvoice}_${nomorInternet}_${customerName}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            } else {
                downloadPDF();
            }
        }

        // Auto trigger download based on query parameter (?download=pdf|word|png|1)
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const dl = urlParams.get('download');
            if (dl === '1' || dl === 'pdf') {
                setTimeout(downloadPDF, 600);
            } else if (dl === 'word' || dl === 'doc') {
                setTimeout(downloadWord, 600);
            } else if (dl === 'png' || dl === 'image') {
                setTimeout(downloadImage, 600);
            }
        });
    </script>
</body>
</html>
