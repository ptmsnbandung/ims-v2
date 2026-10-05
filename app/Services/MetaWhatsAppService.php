<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppService
{
    protected ?string $token;
    protected ?string $phoneNumberId;
    protected ?string $businessAccountId;
    protected string $apiVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->token = config('services.meta_whatsapp.token') ?? env('META_WA_TOKEN');
        $this->phoneNumberId = config('services.meta_whatsapp.phone_number_id') ?? env('META_WA_PHONE_NUMBER_ID');
        $this->businessAccountId = config('services.meta_whatsapp.business_account_id') ?? env('META_WA_BUSINESS_ACCOUNT_ID');
        $this->apiVersion = config('services.meta_whatsapp.api_version') ?? env('META_WA_API_VERSION', 'v21.0');
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Check if Meta WhatsApp API credentials are fully configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->phoneNumberId);
    }

    /**
     * Test connection to Meta WhatsApp API / Fetch Phone Number Details
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Kredensial Meta WhatsApp belum diatur di .env (META_WA_TOKEN & META_WA_PHONE_NUMBER_ID dibutuhkan).'
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get("{$this->baseUrl}/{$this->phoneNumberId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Koneksi Meta WhatsApp Cloud API Berhasil!',
                    'data' => [
                        'verified_name' => $data['verified_name'] ?? 'IMS WhatsApp Business',
                        'display_phone_number' => $data['display_phone_number'] ?? '-',
                        'quality_rating' => $data['quality_rating'] ?? 'GREEN',
                        'status' => $data['code_verification_status'] ?? 'VERIFIED',
                        'phone_number_id' => $this->phoneNumberId,
                    ]
                ];
            }

            $error = $response->json()['error'] ?? [];
            return [
                'success' => false,
                'message' => $error['message'] ?? 'Gagal menghubungi Meta WhatsApp Cloud API (' . $response->status() . ')',
                'error' => $error
            ];
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp testConnection error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format clean phone number for WhatsApp (e.g. 0812... -> 62812...)
     */
    public function formatPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }

    /**
     * Send Official Meta WhatsApp Template Message
     * 
     * @param string $to Recipient phone number (e.g. 628123456789)
     * @param string $templateName Name of approved template on Meta Dashboard
     * @param string $languageCode Language code (e.g. 'id' or 'en_US')
     * @param array $bodyParameters Array of strings for {{1}}, {{2}}, etc.
     * @param array $headerParameters Optional header parameters (image, text, document)
     * @param array $buttonParameters Optional quick reply / url button parameters
     * @return array
     */
    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $languageCode = 'id',
        array $bodyParameters = [],
        array $headerParameters = [],
        array $buttonParameters = []
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Kredensial Meta WhatsApp API belum lengkap di file .env'
            ];
        }

        $cleanTo = $this->formatPhone($to);
        if (empty($cleanTo)) {
            return [
                'success' => false,
                'message' => 'Nomor HP tujuan tidak valid atau kosong'
            ];
        }

        $components = [];

        // Header Component
        if (!empty($headerParameters)) {
            $components[] = [
                'type' => 'header',
                'parameters' => $headerParameters
            ];
        }

        // Body Component ({{1}}, {{2}}, {{3}}, ...)
        if (!empty($bodyParameters)) {
            $params = [];
            foreach ($bodyParameters as $val) {
                $params[] = [
                    'type' => 'text',
                    'text' => (string) $val
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $params
            ];
        }

        // Button Component
        if (!empty($buttonParameters)) {
            foreach ($buttonParameters as $btn) {
                $components[] = $btn;
            }
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ],
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        try {
            $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post($url, $payload);

            if ($response->successful()) {
                $resData = $response->json();
                $messageId = $resData['messages'][0]['id'] ?? null;
                return [
                    'success' => true,
                    'message' => 'Pesan template Meta WhatsApp berhasil dikirim!',
                    'message_id' => $messageId,
                    'raw' => $resData
                ];
            }

            $errorData = $response->json()['error'] ?? [];
            $errorMessage = $errorData['message'] ?? ('Error pengiriman Meta (' . $response->status() . ')');
            
            Log::error('Meta WhatsApp sendTemplateMessage Failed: ' . json_encode($errorData));

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $errorData['code'] ?? null,
                'error_subcode' => $errorData['error_subcode'] ?? null,
                'raw' => $errorData
            ];
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp sendTemplateMessage exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send Free-form Text Message (Only valid within 24h customer service window)
     */
    public function sendTextMessage(string $to, string $message, bool $previewUrl = false): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Kredensial Meta WhatsApp API belum lengkap di file .env'
            ];
        }

        $cleanTo = $this->formatPhone($to);
        if (empty($cleanTo)) {
            return [
                'success' => false,
                'message' => 'Nomor HP tujuan tidak valid'
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanTo,
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $message
            ]
        ];

        try {
            $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post($url, $payload);

            if ($response->successful()) {
                $resData = $response->json();
                return [
                    'success' => true,
                    'message' => 'Pesan teks berhasil dikirim!',
                    'message_id' => $resData['messages'][0]['id'] ?? null,
                    'raw' => $resData
                ];
            }

            $errorData = $response->json()['error'] ?? [];
            return [
                'success' => false,
                'message' => $errorData['message'] ?? ('Gagal kirim pesan teks (' . $response->status() . ')'),
                'raw' => $errorData
            ];
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp sendTextMessage exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ];
        }
    }
}
