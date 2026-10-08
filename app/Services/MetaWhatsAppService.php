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

    /**
     * Fetch All Registered & Approved Message Templates from Meta WABA Account
     */
    public function getMessageTemplates(): array
    {
        if (empty($this->token)) {
            return [
                'success' => false,
                'message' => 'META_WA_TOKEN belum diatur di file .env'
            ];
        }

        $wabaId = $this->businessAccountId;
        if (empty($wabaId)) {
            return [
                'success' => false,
                'message' => 'META_WA_BUSINESS_ACCOUNT_ID belum diatur di file .env'
            ];
        }

        try {
            $url = "{$this->baseUrl}/{$wabaId}/message_templates";
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->get($url, [
                    'limit' => 100,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'templates' => $data['data'] ?? [],
                    'paging' => $data['paging'] ?? null,
                ];
            }

            $error = $response->json()['error'] ?? [];
            return [
                'success' => false,
                'message' => $error['message'] ?? 'Gagal mengambil daftar template dari Meta API (' . $response->status() . ')',
                'error' => $error
            ];
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp getMessageTemplates exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync Message Templates from Meta into tb_broadcast_wa_template Database
     */
    public function syncTemplatesToDatabase(): array
    {
        $res = $this->getMessageTemplates();
        if (!$res['success']) {
            return $res;
        }

        $templates = $res['templates'] ?? [];
        $syncedCount = 0;
        $syncedNames = [];

        foreach ($templates as $tpl) {
            $name = $tpl['name'] ?? '';
            $status = strtoupper($tpl['status'] ?? 'APPROVED');
            $language = $tpl['language'] ?? 'id';
            $category = strtolower($tpl['category'] ?? 'utility');

            if (empty($name)) continue;

            // Extract body text & parameters
            $bodyText = '';
            if (!empty($tpl['components'])) {
                foreach ($tpl['components'] as $comp) {
                    if (($comp['type'] ?? '') === 'BODY') {
                        $bodyText = $comp['text'] ?? '';
                    }
                }
            }

            if (empty($bodyText)) {
                $bodyText = "Template Meta WhatsApp: {$name}";
            }

            // Extract parameter count (e.g. {{1}}, {{2}})
            preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $matches);
            $paramCount = !empty($matches[1]) ? count(array_unique($matches[1])) : 0;
            
            // Format nice human-readable name
            $humanName = ucwords(str_replace('_', ' ', $name));

            // Default mapping based on template name
            $defaultParamsMap = [];
            if ($name === 'tagihan_bulanan') {
                $defaultParamsMap = ['periode', 'bulan_jatuh_tempo', 'bulan_suspend'];
            } elseif (str_contains($name, 'tagihan') || str_contains($name, 'invoice')) {
                $defaultParamsMap = ['periode', 'bulan_jatuh_tempo', 'bulan_suspend', 'nominal', 'nomor_internet'];
            } elseif (str_contains($name, 'report') || str_contains($name, 'work')) {
                $defaultParamsMap = ['nama', 'nomor_internet', 'alamat', 'paket'];
            } else {
                for ($i = 1; $i <= max($paramCount, 1); $i++) {
                    $defaultParamsMap[] = 'param_' . $i;
                }
            }

            // Slice params to match actual parameter count in template if any
            if ($paramCount > 0 && count($defaultParamsMap) > $paramCount) {
                $defaultParamsMap = array_slice($defaultParamsMap, 0, $paramCount);
            }

            // Check if exists in tb_broadcast_wa_template
            $existing = \Illuminate\Support\Facades\DB::table('tb_broadcast_wa_template')
                ->where('meta_template_name', $name)
                ->first();

            if ($existing) {
                \Illuminate\Support\Facades\DB::table('tb_broadcast_wa_template')
                    ->where('id', $existing->id)
                    ->update([
                        'nama_template'   => $existing->nama_template ?: $humanName,
                        'meta_language'   => $language,
                        'meta_params_map' => json_encode($defaultParamsMap),
                        'pesan'           => $bodyText,
                        'kategori'        => $category,
                        'updated_at'      => now(),
                    ]);
            } else {
                \Illuminate\Support\Facades\DB::table('tb_broadcast_wa_template')->insert([
                    'nama_template'      => $humanName . ' (' . strtoupper($language) . ')',
                    'meta_template_name' => $name,
                    'meta_language'      => $language,
                    'meta_params_map'    => json_encode($defaultParamsMap),
                    'subjek'             => $humanName,
                    'kategori'           => $category,
                    'pesan'              => $bodyText,
                    'is_default'         => ($name === 'tagihan_bulanan' || $syncedCount === 0 ? 1 : 0),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            $syncedCount++;
            $syncedNames[] = $name;
        }

        // Hapus template lama dari database yang sudah tidak ada di Meta
        if (!empty($syncedNames)) {
            \Illuminate\Support\Facades\DB::table('tb_broadcast_wa_template')
                ->whereNotIn('meta_template_name', $syncedNames)
                ->delete();
        }

        return [
            'success' => true,
            'message' => "Berhasil mengambil {$syncedCount} template resmi dari Meta WhatsApp (" . implode(', ', $syncedNames) . ")!",
            'count' => $syncedCount,
            'templates' => $syncedNames,
        ];
    }
}
