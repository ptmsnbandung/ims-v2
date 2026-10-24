<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran Tagihan Internet - PT Media Solusi Network</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-collapse: collapse;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #091322 0%, #0f172a 50%, #0e7490 100%);
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 12px 0 4px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            color: #93c5fd;
            font-size: 12px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 16px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .receipt-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .receipt-row {
            padding: 9px 0;
            border-bottom: 1px dashed #cbd5e1;
            font-size: 13.5px;
        }
        .receipt-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .receipt-row:first-child {
            padding-top: 0;
        }
        .receipt-label {
            color: #64748b;
            width: 42%;
            vertical-align: top;
        }
        .receipt-value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
            width: 58%;
        }
        .total-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin-top: 16px;
        }
        .total-label {
            font-size: 12px;
            color: #166534;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .total-amount {
            font-size: 24px;
            color: #15803d;
            font-weight: 800;
            margin-top: 4px;
        }
        .action-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn-portal {
            display: inline-block;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.35);
        }
        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            font-size: 12.5px;
            color: #0369a1;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 20px;
            text-align: center;
            font-size: 11.5px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .footer a {
            color: #0284c7;
            text-decoration: none;
        }
    </style>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #f1f5f9;">

    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <div class="container">
                    
                    <!-- Header -->
                    <div class="header">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center">
                                    <div style="display: inline-block; background-color: #ffffff; width: 50px; height: 50px; border-radius: 12px; line-height: 50px; text-align: center; margin-bottom: 8px;">
                                        <img src="https://ptmsn.net.id/images/logo/logo-icon.png" alt="MSN Logo" width="36" height="36" style="vertical-align: middle; margin-top: 7px;">
                                    </div>
                                    <h1>My<span style="color: #38bdf8;">MSN</span></h1>
                                    <p>{{ config('company.name', 'PT MEDIA SOLUSI NETWORK') }}</p>
                                    <div class="status-badge">&#10003; PEMBAYARAN BERHASIL (LUNAS)</div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        <div class="greeting">
                            Halo, <strong>{{ $customer->nama_pelanggan ?? $customer->nama_penduduk ?? $customer->name ?? 'Pelanggan Setia' }}</strong>
                        </div>
                        <div class="intro-text">
                            Terima kasih! Pembayaran tagihan internet Anda telah kami terima dan diverifikasi resmi (Lunas). Berikut adalah rincian kwitansi pembayaran Anda:
                        </div>

                        <!-- Receipt Details Card -->
                        <div class="receipt-card">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr class="receipt-row">
                                    <td class="receipt-label">No. Invoice / Tagihan</td>
                                    <td class="receipt-value" style="color: #0284c7;">{{ $billing->kode_billing_layanan ?? '-' }}</td>
                                </tr>
                                <tr class="receipt-row">
                                    <td class="receipt-label">ID Pelanggan (No. Internet)</td>
                                    <td class="receipt-value">{{ $customer->nomor_internet ?? $billing->nomor_internet ?? '-' }}</td>
                                </tr>
                                <tr class="receipt-row">
                                    <td class="receipt-label">Periode Tagihan</td>
                                    <td class="receipt-value">{{ $billing->period ?? '-' }}</td>
                                </tr>
                                <tr class="receipt-row">
                                    <td class="receipt-label">Paket Layanan</td>
                                    <td class="receipt-value">{{ $customer->nama_bandwith ?? 'Internet Broadband' }}</td>
                                </tr>
                                <tr class="receipt-row">
                                    <td class="receipt-label">Metode Pembayaran</td>
                                    <td class="receipt-value">
                                        {{ strtoupper(str_replace(['_', '-'], ' ', $billing->merchant_type ?: ($paymentData['metode_bayar'] ?? 'Transfer Bank'))) }}
                                    </td>
                                </tr>
                                <tr class="receipt-row">
                                    <td class="receipt-label">Waktu Pembayaran</td>
                                    <td class="receipt-value">
                                        {{ !empty($billing->payment_paid) ? \Carbon\Carbon::parse($billing->payment_paid)->translatedFormat('d F Y, H:i') . ' WIB' : now()->translatedFormat('d F Y, H:i') . ' WIB' }}
                                    </td>
                                </tr>
                            </table>

                            <!-- Total Box -->
                            <div class="total-box">
                                <div class="total-label">Total Pelunasan</div>
                                <div class="total-amount">
                                    Rp {{ number_format((float) ($billing->amount_paid ?: ($billing->total_layanan ?? $paymentData['nominal'] ?? 0)), 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="info-box">
                            <strong>Status Layanan:</strong> Layanan internet Anda telah aktif secara otomatis tanpa perlu konfirmasi manual. Jika sebelumnya mengalami isolir, koneksi akan kembali normal dalam hitungan menit.
                        </div>

                        <!-- CTA Button -->
                        <div class="action-container">
                            <a href="{{ config('company.portal_url', 'https://portal.ptmsn.co.id') }}/portal/billing" class="btn-portal" target="_blank">
                                Buka Portal Tagihan Saya
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer">
                        <p style="margin: 0 0 6px 0;">
                            Email ini dibuat secara otomatis oleh sistem penagihan <strong>{{ config('company.name', 'PT Media Solusi Network') }}</strong>.
                        </p>
                        <p style="margin: 0 0 10px 0;">
                            Butuh bantuan? Hubungi WhatsApp Layanan Pelanggan: 
                            <a href="https://wa.me/{{ config('company.billing_whatsapp', config('company.whatsapp', '6289696629955')) }}?text={{ urlencode('Halo Tim PT MSN, saya ingin bertanya tentang pembayaran tagihan ' . ($billing->kode_billing_layanan ?? '')) }}" target="_blank">
                                +{{ config('company.billing_whatsapp', config('company.whatsapp', '6289696629955')) }}
                            </a>
                        </p>
                        <p style="margin: 0; color: #cbd5e1; font-size: 10.5px;">
                            &copy; {{ date('Y') }} {{ config('company.name', 'PT Media Solusi Network') }}. All rights reserved.
                        </p>
                    </div>

                </div>
            </td>
        </tr>
    </table>

</body>
</html>
