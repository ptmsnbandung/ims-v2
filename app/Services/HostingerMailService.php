<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HostingerMailService
{
    protected string $baseUrl = 'https://api.mail.hostinger.com';
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
     * Dapatkan Mailbox Resource ID dari Hostinger API (GET /api/v1/me)
     *
     * @return string
     * @throws Exception
     */
    public function getMailboxResourceId(): string
    {
        if (!$this->isConfigured()) {
            throw new Exception("Hostinger Mail API: HOSTINGER_MAIL_TOKEN belum disetel di file .env");
        }

        $cacheKey = 'hostinger_mailbox_resource_id_' . md5($this->token);

        return Cache::remember($cacheKey, 86400, function () {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v1/me");

            if (!$response->successful()) {
                $status = $response->status();
                $body = $response->body();
                throw new Exception("Hostinger Mail API Gagal mendapatkan informasi akun [HTTP {$status}]: {$body}");
            }

            $json = $response->json();
            $mailboxes = $json['data']['mailboxes'] ?? [];

            if (empty($mailboxes)) {
                throw new Exception("Hostinger Mail API: Tidak ada mailbox yang ditemukan untuk token API ini.");
            }

            // Cari mailbox yang cocok dengan alamat 'from' (misal: info@ptmsn.co.id)
            $matched = null;
            $fromAddress = strtolower(trim($this->from));
            foreach ($mailboxes as $mb) {
                if (strtolower(trim($mb['address'] ?? '')) === $fromAddress) {
                    $matched = $mb['resourceId'];
                    break;
                }
            }

            // Jika tidak ada yang cocok persis, gunakan mailbox pertama
            if (!$matched && !empty($mailboxes[0]['resourceId'])) {
                $matched = $mailboxes[0]['resourceId'];
            }

            if (!$matched) {
                throw new Exception("Hostinger Mail API: Gagal mendeteksi Resource ID dari mailbox.");
            }

            return $matched;
        });
    }

    /**
     * Kirim email menggunakan Hostinger Mail REST API
     * POST /api/v1/mailboxes/{mailboxResourceId}/send
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

        $mailboxResourceId = $this->getMailboxResourceId();
        $companyName = config('company.name', 'PT Media Solusi Network');

        $payload = [
            'to'          => [$cleanTo],
            'displayName' => $companyName,
            'subject'     => $subject,
            'html'        => $htmlContent,
            'text'        => $plainText ?: strip_tags($htmlContent),
        ];

        try {
            $response = Http::withToken($this->token)
                ->timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/v1/mailboxes/{$mailboxResourceId}/send", $payload);

            $status = $response->status();

            // Status 200, 201, 202, atau 204 No Content menandakan pesan berhasil terkirim
            if ($response->successful() || in_array($status, [200, 201, 202, 204])) {
                Log::info("Hostinger Mail API: Email berhasil dikirim ke {$cleanTo} via mailbox {$mailboxResourceId} (HTTP {$status})");
                return true;
            }

            $errMsg = "Hostinger Mail API Error [HTTP {$status}]: " . $response->body();
            Log::error($errMsg);
            throw new Exception($errMsg);
        } catch (\Throwable $e) {
            Log::error("Hostinger Mail API Exception ke {$cleanTo}: " . $e->getMessage());
            throw $e;
        }
    }
}
