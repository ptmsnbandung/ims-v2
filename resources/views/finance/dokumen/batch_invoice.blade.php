<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Massal Invoice ({{ count($invoicesData) }} Dokumen)</title>
    
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
            font-family: Arial, Helvetica, sans-serif, 'Inter';
            background-color: #0f172a;
            color: #000;
            font-size: 8.5pt;
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

        .btn-print {
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #fff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
        }

        .btn-print:hover {
            background: linear-gradient(135deg, #0369a1, #1d4ed8);
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

        .pages-wrapper {
            padding-top: 75px;
            padding-bottom: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
        }

        /* Paper Form A4 */
        .paper-sheet {
            width: 210mm;
            height: 297mm;
            min-height: 297mm;
            max-height: 297mm;
            background: #ffffff;
            padding: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
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
            padding: 4px 14mm 4px 14mm;
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex-grow: 1;
            font-family: Arial, Helvetica, sans-serif, 'Inter';
        }

        .doc-title-container {
            text-align: center;
            margin: 0 0 6px 0;
        }

        .doc-title-wrapper {
            display: inline-block;
            text-align: center;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 0.5px;
            color: #000;
            border-bottom: 2px solid #000;
            padding-bottom: 0px;
            line-height: 1.1;
        }

        .doc-subtitle {
            font-size: 7.5pt;
            font-weight: bold;
            font-style: italic;
            text-align: right;
            margin-top: 1px;
            letter-spacing: 0.5px;
        }

        /* Info Box */
        .info-box {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8.5pt;
        }

        .info-box td {
            vertical-align: top;
            padding: 5px 6px;
        }

        .info-customer {
            width: 50%;
            border-right: 1px solid #000;
        }

        .info-company {
            width: 50%;
            padding-left: 8px;
        }

        .cust-name-header {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            border-bottom: 1.5px solid #000;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }

        .cust-address-text {
            font-size: 8pt;
            line-height: 1.25;
            text-transform: uppercase;
            color: #000;
        }

        .company-header-text {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        /* Billing Main Table */
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 6px;
            font-size: 8.5pt;
        }

        .bill-table th, .bill-table td {
            border: 1px solid #000;
            padding: 3px 5px;
        }

        .bill-table th {
            background-color: #000000;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
            padding: 3px 4px;
        }

        .terbilang-cell {
            font-size: 8pt;
            vertical-align: top;
            padding: 4px 6px;
            color: #000;
        }

        .tagihan-cyan-bar {
            background-color: #00C0F3 !important;
            color: #000000 !important;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
            padding: 4px 6px;
            letter-spacing: 0.3px;
        }

        /* Signatures & Payment Block */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 6px;
            font-size: 8.5pt;
        }

        .sig-table th, .sig-table td {
            border: 1px solid #000;
        }

        .sig-table th {
            background-color: #000000;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
            padding: 3px 4px;
        }

        .sig-cell-content {
            min-height: 65px;
            padding: 8px 4px 6px 4px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Cut Divider Line */
        .cut-line {
            border-top: 1.5px dashed #000000;
            margin: 8px 0 6px 0;
            width: 100%;
        }

        /* Slip Pembayaran */
        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 3px;
        }

        .slip-box {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8pt;
        }

        .slip-box td {
            vertical-align: top;
            padding: 3px 6px;
        }

        .slip-left {
            width: 50%;
            border-right: 1px solid #000;
        }

        .slip-right {
            width: 50%;
        }

        .slip-sig-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .slip-sig-table td {
            text-align: center;
            width: 50%;
            vertical-align: top;
        }

        .notes-box {
            font-family: Arial, Helvetica, sans-serif;
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
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .screen-toolbar {
                display: none !important;
            }

            .pages-wrapper {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                gap: 0 !important;
            }

            .paper-sheet {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .paper-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .kop-header-container, .kop-footer-container {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN TOOLBAR -->
    <div class="screen-toolbar">
        <div class="toolbar-info">
            <span class="toolbar-badge">CETAK MASSAL INVOICE</span>
            <span style="font-size: 13px; font-weight: 600;">Total: {{ count($invoicesData) }} Dokumen Siap Cetak</span>
            @if(!empty($bulan) || !empty($tahun))
            <span style="font-size: 12px; color: #94a3b8;">&bull;</span>
            <span style="font-size: 12px; color: #cbd5e1;">Periode: {{ $bulan ? date('F', mktime(0,0,0,(int)$bulan,1)) : '' }} {{ $tahun ?? '' }}</span>
            @endif
        </div>

        <div class="toolbar-actions">
            <button onclick="window.print()" class="btn-action btn-print" title="Cetak Semua Dokumen Invoice (Ctrl+P)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Semua ({{ count($invoicesData) }})</span>
            </button>

            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('finance.billing-layanan') }}" class="btn-action btn-back" title="Kembali">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
                <span>Tutup</span>
            </a>
        </div>
    </div>

    <!-- MAIN PAGES CONTAINER -->
    <div class="pages-wrapper">
        @foreach($invoicesData as $inv)
        <div class="paper-sheet">
            
            <!-- 1. KOP SURAT (HEADER BANNER) -->
            <div class="kop-header-container">
                <img src="{{ $headerBase64 }}" alt="Kop Surat PT Media Solusi Network" class="kop-header-img">
            </div>

            <!-- 2. BODY INVOICE -->
            <div class="form-body">
                
                <!-- TITLE -->
                <div class="doc-title-container">
                    <div class="doc-title-wrapper">
                        <div class="doc-title">INVOICE</div>
                        <div class="doc-subtitle">TAGIHAN</div>
                    </div>
                </div>

                <!-- CUSTOMER & INVOICE META BOX -->
                <table class="info-box">
                    <tr>
                        <td class="info-customer">
                            <div class="cust-name-header">{{ $inv['customerName'] }}</div>
                            <div class="cust-address-text">
                                {{ $inv['alamat'] }}
                            </div>
                        </td>
                        <td class="info-company">
                            <div class="company-header-text">PT MEDIA SOLUSI NETWORK</div>
                            <table style="width: 100%; font-size: 8.5pt; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 115px; padding: 1px 0;">No tagihan</td>
                                    <td style="width: 8px; text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['noInvoice'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Nomor Pelanggan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['nomorInternet'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Periode Pemakaian</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['periodeTagihan'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Jatuh Tempo</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['jatuhTempo'] }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- MAIN BILL TABLE -->
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 63%;">Layanan</th>
                            <th style="width: 12%;">Qty</th>
                            <th style="width: 20%;">Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">1</td>
                            <td>{{ strtoupper($inv['namaLayanan']) }}</td>
                            <td style="text-align: center;">1</td>
                            <td style="text-align: right;">Rp {{ number_format($inv['subtotal'], 0, ',', '.') }},00</td>
                        </tr>

                        <!-- Terbilang & Potongan -->
                        <tr>
                            <td colspan="2" rowspan="2" class="terbilang-cell">
                                <strong>Terbilang :</strong> {{ $inv['terbilangText'] }}
                            </td>
                            <td style="text-align: right; font-weight: normal;">POTONGAN</td>
                            <td style="text-align: right;">
                                {{ $inv['potongan'] > 0 ? 'Rp ' . number_format($inv['potongan'], 0, ',', '.') . ',00' : '0' }}
                            </td>
                        </tr>

                        <!-- PPN -->
                        <tr>
                            <td style="text-align: right; font-weight: normal;">PPN</td>
                            <td style="text-align: right;">
                                <div style="line-height: 1.1;">
                                    <div>Rp {{ number_format($inv['ppn'] > 0 ? $inv['ppn'] : ($inv['total'] * 0.1), 0, ',', '.') }},00</div>
                                    <div style="font-size: 7.5pt; color: #333;">(include)</div>
                                </div>
                            </td>
                        </tr>

                        <!-- Total Tagihan (Cyan highlight) -->
                        <tr>
                            <td colspan="3" class="tagihan-cyan-bar">
                                TAGIHAN BULAN INI
                            </td>
                            <td style="text-align: right; font-size: 9pt; font-weight: bold; background-color: #ffffff; padding: 4px 6px;">
                                Rp {{ number_format($inv['total'], 0, ',', '.') }},00
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- SIGNATURES & PAYMENT METHOD BLOCK -->
                <table class="sig-table">
                    <thead>
                        <tr>
                            <th style="width: 32%;">Pembayaran</th>
                            <th style="width: 36%;">Mengetahui</th>
                            <th style="width: 32%;">Pelanggan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <!-- Column 1: Pembayaran & Link Klik Disini -->
                            <td style="vertical-align: middle;">
                                <div class="sig-cell-content" style="min-height: 85px; justify-content: center;">
                                    <div style="font-size: 8.5pt; font-weight: bold; margin-bottom: 6px;">LINK PEMBAYARAN :</div>
                                    <div>
                                        <a href="{{ $inv['paymentUrl'] }}" target="_blank" style="color: #0000ff; text-decoration: underline; font-weight: bold; font-size: 9pt;">
                                            Klik Disini
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 2: Mengetahui (Ida Mayasari - Keuangan) -->
                            <td style="vertical-align: bottom;">
                                <div class="sig-cell-content" style="min-height: 85px; justify-content: flex-end; padding-bottom: 4px;">
                                    <div style="display: inline-block; min-width: 150px; text-align: center;">
                                        <div style="font-weight: bold; font-size: 9pt;">Ida Mayasari</div>
                                        <div style="border-top: 1px solid #000; margin-top: 2px; padding-top: 2px; font-size: 8.5pt;">Keuangan</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 3: Pelanggan (Customer Name) -->
                            <td style="vertical-align: bottom;">
                                <div class="sig-cell-content" style="min-height: 85px; justify-content: flex-end; padding-bottom: 4px;">
                                    <div style="display: inline-block; min-width: 150px; text-align: center;">
                                        <div style="font-weight: bold; font-size: 9pt; text-transform: uppercase;">
                                            {{ $inv['customerName'] }}
                                        </div>
                                        <div style="border-top: 1px solid #000; margin-top: 2px; padding-top: 2px; font-size: 8.5pt; color: transparent;">-</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- CUT DIVIDER LINE -->
                <div class="cut-line"></div>

                <!-- SLIP PEMBAYARAN -->
                <div class="slip-header">
                    <span>PT. MEDIA SOLUSI NETWORK</span>
                    <span>SLIP PEMBAYARAN</span>
                </div>

                <table class="slip-box">
                    <tr>
                        <td class="slip-left">
                            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                                <tr>
                                    <td style="width: 95px; padding: 1px 0;">Nomor Tagihan</td>
                                    <td style="width: 8px; text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['noInvoice'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Nomor Pelanggan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['nomorInternet'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Nama Pelanggan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0; text-transform: uppercase;">{{ $inv['customerName'] }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="slip-right">
                            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                                <tr>
                                    <td style="width: 95px; padding: 1px 0;">Periode Tagihan</td>
                                    <td style="width: 8px; text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['periodeTagihan'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Jatuh Tempo</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0;">{{ $inv['jatuhTempo'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 1px 0;">Jumlah Tagihan</td>
                                    <td style="text-align: center; padding: 1px 0;">:</td>
                                    <td style="padding: 1px 0; font-weight: bold;">Rp {{ number_format($inv['total'], 0, ',', '.') }},00</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- SLIP SIGNATURES (ENLARGED FOR COMFORTABLE SIGNING) -->
                <table class="slip-sig-table" style="margin-top: 6px; margin-bottom: 6px;">
                    <tr>
                        <td style="padding-bottom: 50px; font-size: 9pt; font-weight: 500;">Petugas</td>
                        <td style="padding-bottom: 50px; font-size: 9pt; font-weight: 500;">Pelanggan</td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 8.5pt; display: inline-block; min-width: 150px; border-top: 1px solid #94a3b8; padding-top: 2px;">TTD / Nama</span>
                        </td>
                        <td>
                            <span style="font-size: 8.5pt; font-weight: bold; text-transform: uppercase; display: inline-block; min-width: 150px; border-top: 1px solid #94a3b8; padding-top: 2px;">{{ $inv['customerName'] }}</span>
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
                <img src="{{ $footerBase64 }}" alt="Footer PT Media Solusi Network" class="kop-footer-img">
            </div>

        </div>
        @endforeach
    </div>

</body>
</html>
