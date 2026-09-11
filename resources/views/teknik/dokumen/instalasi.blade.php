<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Tugas Instalasi - {{ $customer->nomor_internet }} - {{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: 'Pelanggan') }}</title>
    
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
            font-size: 9.5pt;
            line-height: 1.5;
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
            background: linear-gradient(135deg, #0d9488, #0284c7);
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
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s;
            cursor: pointer;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-item:hover {
            background: rgba(51, 65, 85, 0.8);
            color: #fff;
        }

        .dropdown-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dropdown-item-desc {
            font-size: 10px;
            color: #94a3b8;
            display: block;
        }

        /* Document Canvas container */
        .page-container {
            display: flex;
            justify-content: center;
            padding: 76px 20px 40px;
            min-height: 100vh;
        }

        /* Paper sheet A4 (210mm x 297mm) */
        .paper-sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border-radius: 2px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* 1. Header Asli */
        .kop-header-container {
            width: 100%;
            line-height: 0;
            flex-shrink: 0;
        }

        .kop-header-img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* 2. Isi Dokumen Surat Tugas */
        .instalasi-body {
            padding: 10px 48px 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .instalasi-title-block {
            text-align: center;
            margin-bottom: 22px;
            margin-top: 5px;
        }

        .instalasi-title {
            font-size: 15pt;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #000;
        }

        .instalasi-nomor {
            font-size: 10pt;
            margin-top: 4px;
            color: #111;
            font-weight: 500;
        }

        .instalasi-intro {
            text-align: justify;
            margin-bottom: 18px;
            line-height: 1.65;
            color: #111;
        }

        /* Details List / Table */
        .instalasi-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9.5pt;
        }

        .instalasi-details-table td {
            padding: 3.5px 0;
            vertical-align: top;
            color: #111;
        }

        .col-label {
            width: 150px;
            font-weight: 500;
        }

        .col-colon {
            width: 20px;
            text-align: center;
            font-weight: 500;
        }

        .col-val {
            font-weight: 500;
            text-transform: uppercase;
        }

        .instalasi-closing {
            margin-bottom: 22px;
            line-height: 1.6;
            color: #111;
        }

        .instalasi-regards {
            margin-bottom: 28px;
            font-weight: 500;
            color: #111;
        }

        /* Signatures */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 15px;
        }

        .sig-role {
            font-size: 10pt;
            font-weight: 400;
            margin-bottom: 75px;
            color: #000;
        }

        .sig-name {
            font-size: 10pt;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #000;
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
                font-size: 9.5pt !important;
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

    // Tanggal Instalasi
    $tglInstalasi = $instalasi->instalasi_date_start 
        ?? ($customer->instalasi_date_start ?? ($customer->date_create ?? now()->format('Y-m-d')));
    $formattedTglInstalasi = \Carbon\Carbon::parse($tglInstalasi)->translatedFormat('d F Y');
    $bulanSurat = date('m', strtotime($tglInstalasi));
    $tahunSurat = date('Y', strtotime($tglInstalasi));

    // Nomor Surat
    $nomorSurat = $instalasi->kode_instalasi 
        ?? ($customer->nomor_internet ? $customer->nomor_internet . '/MSN-NOC/' . $bulanSurat . '/' . $tahunSurat : '/MSN-NOC/' . $bulanSurat . '/' . $tahunSurat);

    // Tim Teknisi Instalasi (kat_team = 11)
    $teamNames = [];
    if (isset($teamInstalasi) && $teamInstalasi->count() > 0) {
        $teamNames = $teamInstalasi->pluck('nama_karyawan')->toArray();
    } elseif (!empty($instalasi->instalasi_team)) {
        $teamNames = array_map('trim', explode(',', $instalasi->instalasi_team));
    }
    $teknisiString = !empty($teamNames) ? implode(', ', $teamNames) : 'Tim Teknisi Instalasi PT MSN';

    // Alamat Lengkap
    $alamatLengkap = '';
    if (!empty($customer->alamat_p)) {
        $alamatLengkap = $customer->alamat_p;
    } elseif (!empty($customer->alamat_pasang)) {
        $alamatLengkap = $customer->alamat_pasang;
        if (!empty($customer->nomor_bangunan)) {
            $alamatLengkap .= ' NO. ' . $customer->nomor_bangunan;
        }
        if (!empty($customer->rt_pasang) || !empty($customer->rw_pasang)) {
            $alamatLengkap .= ', RT' . ($customer->rt_pasang ?: '000') . '/RW' . ($customer->rw_pasang ?: '000');
        }
        if (!empty($customer->nama_kelurahan_pasang)) {
            $alamatLengkap .= ', KEL. ' . $customer->nama_kelurahan_pasang;
        }
        if (!empty($customer->nama_kecamatan_pasang)) {
            $alamatLengkap .= ', KEC. ' . $customer->nama_kecamatan_pasang;
        }
        if (!empty($customer->nama_kota_pasang)) {
            $alamatLengkap .= ', ' . $customer->nama_kota_pasang;
        }
    } elseif (!empty($customer->alamat_ktp)) {
        $alamatLengkap = $customer->alamat_ktp;
        if (!empty($customer->rt_ktp) || !empty($customer->rw_ktp)) {
            $alamatLengkap .= ', RT' . ($customer->rt_ktp ?: '000') . '/RW' . ($customer->rw_ktp ?: '000');
        }
        if (!empty($customer->nama_kelurahan)) {
            $alamatLengkap .= ', KEL. ' . $customer->nama_kelurahan;
        }
        if (!empty($customer->nama_kecamatan)) {
            $alamatLengkap .= ', KEC. ' . $customer->nama_kecamatan;
        }
        if (!empty($customer->nama_kota)) {
            $alamatLengkap .= ', ' . $customer->nama_kota;
        }
    } else {
        $alamatLengkap = '-';
    }

    // Waktu Pengerjaan
    $waktuPengerjaan = $instalasi->instalasi_time 
        ?? ($customer->instalasi_time ?? '15:00-18:00WIB');

    // Detail Pekerjaan / Catatan Instalasi
    $detailPekerjaan = $instalasi->instalasi_note 
        ?? ($customer->note_request ?? 'penarikan kabel fo 1 core dan pemasangan onu');

    // Penanggung Jawab (Kosong / '-' sesuai template master Instalasi)
    $penanggungJawab = '-';
@endphp

    <!-- Top Floating Toolbar (Hidden on Print) -->
    <div class="screen-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-badge">SURAT TUGAS INSTALASI</span>
            <div>
                <strong style="font-size: 13px;">{{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: 'Pelanggan') }}</strong>
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
                            <span class="dropdown-item-desc">Format standar A4 siap cetak & tanda tangan</span>
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
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Download Gambar (.png)</div>
                            <span class="dropdown-item-desc">Resolusi tinggi untuk share ke tim teknisi</span>
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
                <span>Cetak Surat</span>
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
            
            <!-- 1. KOP SURAT ASLI (EKSTRAKSI DARI TEMPLATE MASTER PT MSN) -->
            <div class="kop-header-container">
                <img src="{{ asset('assets/images/kop_header.png') }}" alt="Kop Surat PT Media Solusi Network" class="kop-header-img" id="kop-header-image">
            </div>

            <!-- 2. FORM BODY -->
            <div class="instalasi-body">
                
                <!-- Title & Nomor -->
                <div class="instalasi-title-block">
                    <div class="instalasi-title">SURAT TUGAS INSTALASI</div>
                    <div class="instalasi-nomor">Nomor : &nbsp;&nbsp;<strong>{{ $nomorSurat }}</strong></div>
                </div>

                <!-- Paragraph 1: Penugasan -->
                <p class="instalasi-intro">
                    Dengan surat ini pada tanggal, <strong>{{ $formattedTglInstalasi }}</strong> PT Media Solusi Network menugaskan tim teknisi, <strong>{{ $teknisiString }}</strong>. Untuk melakukan pemasangan jaringan internet ke pada pelanggan baru dengan rincian sebagai berikut :
                </p>

                <!-- Details Table -->
                <table class="instalasi-details-table">
                    <tr>
                        <td class="col-label">Nama Pelanggan</td>
                        <td class="col-colon">:</td>
                        <td class="col-val">{{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Alamat</td>
                        <td class="col-colon">:</td>
                        <td class="col-val">{{ $alamatLengkap }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Nomor Telepone</td>
                        <td class="col-colon">:</td>
                        <td class="col-val">{{ $customer->nomor_hp ?: ($customer->nomor_hp_2 ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Layanan</td>
                        <td class="col-colon">:</td>
                        <td class="col-val">
                            {{ $customer->nama_kategori_bandwith ?: ($customer->alias_nama_kategori ?: ($customer->group_layanan ?: 'UP TO NEW')) }}, {{ $customer->nominal_bandwith ?: '15' }} Mbps
                        </td>
                    </tr>
                    <tr>
                        <td class="col-label">Waktu Pengerjaan</td>
                        <td class="col-colon">:</td>
                        <td class="col-val" style="text-transform: none;">{{ $waktuPengerjaan }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Detail Pekerjaan</td>
                        <td class="col-colon">:</td>
                        <td class="col-val" style="text-transform: none;">{{ $detailPekerjaan }}</td>
                    </tr>
                </table>

                <!-- Paragraph 2: Penutup -->
                <p class="instalasi-closing">
                    Demikian surat tugas ini dibuat dan dapat dipertanggung jawabkan dan dapat digunakan sebagaimana mestinya, terimakasih .
                </p>

                <p class="instalasi-regards">
                    Hormat Kami PT Media Solusi Network, Bandung.
                </p>

                <!-- Signatures -->
                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="sig-role">Penanggung Jawab</div>
                            <div class="sig-name">{{ $penanggungJawab }}</div>
                        </td>
                        <td>
                            <div class="sig-role">Pelanggan</div>
                            <div class="sig-name">{{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: 'Pelanggan') }}</div>
                        </td>
                    </tr>
                </table>

            </div>

            <!-- 3. FOOTER ASLI PT MEDIA SOLUSI NETWORK -->
            <div class="kop-footer-container">
                <img src="{{ asset('assets/images/kop_footer.png') }}" alt="Footer PT Media Solusi Network" class="kop-footer-img" id="kop-footer-image">
            </div>

        </div>
    </div>

    <!-- html2pdf.js & html2canvas CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        // Toggle dropdown
        function toggleDownloadDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('downloadMenu');
            menu.classList.toggle('show');
        }

        // Close dropdown when clicked outside
        window.addEventListener('click', function(e) {
            const menu = document.getElementById('downloadMenu');
            if (menu && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });

        // 1. Download as High-Resolution PDF
        function downloadPDF() {
            const element = document.getElementById('paper-sheet-container');
            const filename = 'Surat_Tugas_Instalasi_{{ $customer->nomor_internet }}_{{ preg_replace("/[^a-zA-Z0-9]/", "_", $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: "Pelanggan")) }}.pdf';
            
            // Show loading state on buttons
            const quickBtn = document.getElementById('btn-quick-pdf');
            const originalText = quickBtn ? quickBtn.innerHTML : '';
            if (quickBtn) quickBtn.innerHTML = '<span>Membuat PDF...</span>';

            const opt = {
                margin:       0,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2.5, 
                    useCORS: true,
                    letterRendering: true,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                if (quickBtn) quickBtn.innerHTML = originalText;
            }).catch(err => {
                console.error('PDF error:', err);
                if (quickBtn) quickBtn.innerHTML = originalText;
                window.print();
            });
        }

        // 2. Download as Editable Microsoft Word (.doc) with Base64 embedded Kop Surat
        function downloadWord() {
            const filename = 'Surat_Tugas_Instalasi_{{ $customer->nomor_internet }}_{{ preg_replace("/[^a-zA-Z0-9]/", "_", $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: "Pelanggan")) }}.doc';
            
            const headerImg = "{{ $headerBase64 }}";
            const footerImg = "{{ $footerBase64 }}";

            const wordContent = `
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head>
                    <meta charset="utf-8">
                    <title>Surat Tugas Instalasi</title>
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
                        @page {
                            size: 210mm 297mm;
                            margin: 0;
                        }
                        body {
                            font-family: 'Inter', Arial, sans-serif;
                            font-size: 10pt;
                            line-height: 1.5;
                            color: #000;
                            margin: 0;
                            padding: 0;
                        }
                        .word-container {
                            width: 100%;
                            max-width: 210mm;
                            margin: 0 auto;
                        }
                        .header-img {
                            width: 100%;
                            height: auto;
                            display: block;
                        }
                        .body-content {
                            padding: 10px 48px 20px;
                        }
                        .title-block {
                            text-align: center;
                            margin-bottom: 22px;
                            margin-top: 5px;
                        }
                        .title {
                            font-size: 15pt;
                            font-weight: bold;
                            text-transform: uppercase;
                        }
                        .nomor {
                            font-size: 10pt;
                            margin-top: 4px;
                        }
                        .intro {
                            text-align: justify;
                            margin-bottom: 18px;
                            line-height: 1.65;
                        }
                        .details-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                            font-size: 10pt;
                        }
                        .details-table td {
                            padding: 3.5px 0;
                            vertical-align: top;
                        }
                        .sig-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 15px;
                        }
                        .sig-table td {
                            width: 50%;
                            text-align: center;
                            vertical-align: top;
                        }
                        .sig-role {
                            margin-bottom: 75px;
                            font-weight: normal;
                        }
                        .sig-name {
                            font-weight: normal;
                            text-transform: uppercase;
                        }
                        .footer-img {
                            width: 100%;
                            height: auto;
                            display: block;
                        }
                    </style>
                </head>
                <body>
                    <div class="word-container">
                        <img src="${headerImg}" class="header-img" alt="Header MSN">
                        <div class="body-content">
                            <div class="title-block">
                                <div class="title">SURAT TUGAS INSTALASI</div>
                                <div class="nomor">Nomor : &nbsp;&nbsp;<strong>{{ $nomorSurat }}</strong></div>
                            </div>
                            <p class="intro">
                                Dengan surat ini pada tanggal, <strong>{{ $formattedTglInstalasi }}</strong> PT Media Solusi Network menugaskan tim teknisi, <strong>{{ $teknisiString }}</strong>. Untuk melakukan pemasangan jaringan internet ke pada pelanggan baru dengan rincian sebagai berikut :
                            </p>
                            <table class="details-table">
                                <tr>
                                    <td style="width: 150px; font-weight: normal;">Nama Pelanggan</td>
                                    <td style="width: 20px; text-align: center;">:</td>
                                    <td style="text-transform: uppercase; font-weight: normal;">{{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: '-') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: normal;">Alamat</td>
                                    <td style="text-align: center;">:</td>
                                    <td style="text-transform: uppercase; font-weight: normal;">{{ $alamatLengkap }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: normal;">Nomor Telepone</td>
                                    <td style="text-align: center;">:</td>
                                    <td style="font-weight: normal;">{{ $customer->nomor_hp ?: ($customer->nomor_hp_2 ?: '-') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: normal;">Layanan</td>
                                    <td style="text-align: center;">:</td>
                                    <td style="font-weight: normal;">
                                        {{ $customer->nama_kategori_bandwith ?: ($customer->alias_nama_kategori ?: ($customer->group_layanan ?: 'UP TO NEW')) }}, {{ $customer->nominal_bandwith ?: '15' }} Mbps
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: normal;">Waktu Pengerjaan</td>
                                    <td style="text-align: center;">:</td>
                                    <td style="font-weight: normal;">{{ $waktuPengerjaan }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: normal;">Detail Pekerjaan</td>
                                    <td style="text-align: center;">:</td>
                                    <td style="font-weight: normal;">{{ $detailPekerjaan }}</td>
                                </tr>
                            </table>
                            <p style="margin-bottom: 22px; line-height: 1.6;">
                                Demikian surat tugas ini dibuat dan dapat dipertanggung jawabkan dan dapat digunakan sebagaimana mestinya, terimakasih .
                            </p>
                            <p style="margin-bottom: 28px; font-weight: normal;">
                                Hormat Kami PT Media Solusi Network, Bandung.
                            </p>
                            <table class="sig-table">
                                <tr>
                                    <td>
                                        <div class="sig-role">Penanggung Jawab</div>
                                        <div class="sig-name">{{ $penanggungJawab }}</div>
                                    </td>
                                    <td>
                                        <div class="sig-role">Pelanggan</div>
                                        <div class="sig-name">{{ $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: 'Pelanggan') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <img src="${footerImg}" class="footer-img" alt="Footer MSN">
                    </div>
                </body>
                </html>
            `;

            const blob = new Blob(['\ufeff', wordContent], {
                type: 'application/msword'
            });

            const url = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url);
        }

        // 3. Download as PNG Image
        function downloadPNG() {
            const element = document.getElementById('paper-sheet-container');
            const filename = 'Surat_Tugas_Instalasi_{{ $customer->nomor_internet }}_{{ preg_replace("/[^a-zA-Z0-9]/", "_", $customer->nama_pelanggan ?: ($customer->nama_penduduk ?: "Pelanggan")) }}.png';

            html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = filename;
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        // Check URL Query Parameters for direct download action
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const downloadType = urlParams.get('download');
            
            if (downloadType === 'pdf') {
                setTimeout(() => downloadPDF(), 600);
            } else if (downloadType === 'word' || downloadType === 'doc') {
                setTimeout(() => downloadWord(), 400);
            } else if (downloadType === 'png') {
                setTimeout(() => downloadPNG(), 600);
            } else if (urlParams.has('print')) {
                setTimeout(() => window.print(), 500);
            }
        });
    </script>
</body>
</html>
