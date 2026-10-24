<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Pemberitahuan Resmi - PT Media Solusi Network' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f1f5f9;
            padding: 40px 0;
        }
        .main-card {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 620px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #091a28 0%, #0f2d4a 100%);
            padding: 36px 30px 30px;
            text-align: center;
        }
        .logo-box {
            display: inline-block;
            background-color: #ffffff;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            line-height: 52px;
            text-align: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .brand-name {
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .brand-sub {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .badge-pill {
            display: inline-block;
            background-color: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 9999px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 14px;
        }
        .body-content {
            padding: 36px 32px 28px;
            line-height: 1.7;
            font-size: 14.5px;
            color: #334155;
        }
        .body-content p {
            margin: 0 0 16px;
        }
        .body-content strong {
            color: #0f172a;
        }
        .customer-greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
        }
        .highlight-box {
            background-color: #f8fafc;
            border-left: 4px solid #0284c7;
            border-radius: 0 10px 10px 0;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 14px;
        }
        .btn-portal {
            display: inline-block;
            background-color: #0284c7;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 10px;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
        }
        .divider {
            border: 0;
            height: 1px;
            background-color: #e2e8f0;
            margin: 28px 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer-text {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
            margin: 0 0 8px;
        }
        .footer-links a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            margin: 0 8px;
        }
        .footer-copy {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <!-- Header -->
            <div class="header">
                <div class="logo-box">
                    <img src="https://ptmsn.co.id/images/logo/logo-icon.png" alt="MSN Logo" width="36" height="36" style="vertical-align: middle; margin-top: 8px;">
                </div>
                <h1 class="brand-name">My<span style="color: #38bdf8;">MSN</span></h1>
                <div class="brand-sub">{{ config('company.name', 'PT MEDIA SOLUSI NETWORK') }}</div>
                @if(!empty($badgeText))
                    <div class="badge-pill">{{ $badgeText }}</div>
                @endif
            </div>

            <!-- Body -->
            <div class="body-content">
                @if(!empty($recipientName))
                    <div class="customer-greeting">
                        Halo, <span>{{ $recipientName }}</span>
                        @if(!empty($nomorInternet))
                            <span style="font-weight: normal; font-size: 13px; color: #64748b;">(#{{ $nomorInternet }})</span>
                        @endif
                    </div>
                @endif

                <!-- Message Content (Supports formatted HTML paragraphs) -->
                {!! $htmlBody !!}

                @if(!empty($actionUrl) && !empty($actionText))
                    <div style="text-align: center; margin: 28px 0 10px;">
                        <a href="{{ $actionUrl }}" class="btn-portal" target="_blank">{{ $actionText }}</a>
                    </div>
                @endif

                <div class="divider"></div>

                <p style="margin: 0; font-size: 13px; color: #64748b;">
                    Jika ada pertanyaan lebih lanjut, silakan hubungi Customer Support kami melalui WhatsApp 
                    <strong style="color: #0f172a;">{{ config('company.phone', '+62 851-8835-8385') }}</strong> atau melalui Portal Pelanggan.
                </p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p class="footer-text">
                    Email ini dikirimkan secara resmi oleh <strong>{{ config('company.name', 'PT Media Solusi Network') }}</strong>.<br>
                    {{ config('company.address', 'Bandung, Jawa Barat, Indonesia') }}
                </p>
                <div class="footer-links">
                    <a href="{{ config('company.portal_url', 'https://portal.ptmsn.co.id') }}" target="_blank">Portal Pelanggan</a> &bull;
                    <a href="{{ config('company.website', 'https://ptmsn.co.id') }}" target="_blank">Website Resmi</a> &bull;
                    <a href="https://wa.me/{{ config('company.billing_whatsapp', '6285188358385') }}" target="_blank">WhatsApp CS</a>
                </div>
                <div class="footer-copy">
                    &copy; {{ date('Y') }} PT Media Solusi Network. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
