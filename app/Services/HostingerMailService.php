<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HostingerMailService
{
    protected string $token;
    protected string $from;

    public function __construct(?string $token = null, ?string $from = null)
    {
        $this->token = $token ?: (string) (config('services.hostinger_mail.token') ?: env('HOSTINGER_MAIL_TOKEN', ''));
        $this->from = $from ?: (string) (config('services.hostinger_mail.from') ?: env('HOSTINGER_MAIL_FROM', 'info@ptmsn.co.id'));
    }

    /**
     * Cek apakah konfigurasi Token API sudah terpasang
     */
    public function isConfigured(): bool
    {
        return !empty(trim($this->token));
    }

    /**
     * Kirim email menggunakan Hostinger Mail REST API (HTTPS POST /v1/emails)
     *
     * @param string $to Alamat email penerima
     * @param string $subject Judul email
     * @param string $htmlContent Konten format HTML
     * @param string|null $plainText Konten teks biasa opsional
     * @return bool
     * @throws Exception
     */
    public function send(string $to, string $subject, string $htmlContent, ?string $plainText = null): bool
    {
        $cleanTo = trim($to);
        if (empty($cleanTo) || !filter_var($cleanTo, FILTER_VALIDATE_EMAIL)) {
            Log::warning("Hostinger Mail API: Format alamat email penerima tidak valid ({$cleanTo}).");
            return false;
        }

        if (!$this->isConfigured()) {
            throw new Exception("Hostinger Mail API: HOSTINGER_MAIL_TOKEN belum disetel di file .env");
        }

        $payload = [
            'to'      => $cleanTo,
            'subject' => $subject,
            'html'    => $htmlContent,
            'body'    => $plainText ?: strip_tags($htmlContent),
        ];

        try {
            $response = Http::withToken($this->token)
                ->timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post('https://api.mail.hostinger.com/v1/emails', $payload);

            $status = $response->status();

            // Status 200, 201, atau 202 (Accepted for delivery) menandakan sukses
            if ($response->successful() || in_array($status, [200, 201, 202])) {
                Log::info("Hostinger Mail API: Email berhasil dikirim ke {$cleanTo} - Subjek: '{$subject}' (HTTP {$status})");
                return true;
            }

            $errMsg = "Hostinger Mail API Error [HTTP {$status}]: " . $response->body();
            Log::error($errMsg);
            throw new Exception($errMsg);
        } catch (\Throwable $e) {
            Log::error("Hostinger Mail API Request Exception ke {$cleanTo}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
