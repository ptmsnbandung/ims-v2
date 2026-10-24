<?php

namespace App\Jobs;

use App\Mail\CustomBroadcastMail;
use App\Services\HostingerMailService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SendCustomMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $recipientEmail;
    public string $emailSubject;
    public string $htmlBody;
    public ?string $recipientName;
    public ?string $nomorInternet;
    public ?string $badgeText;
    public ?string $actionUrl;
    public ?string $actionText;
    public ?string $senderUser;
    public ?int $logId;

    /**
     * Jumlah percobaan maksimal jika terjadi kendala jaringan
     */
    public int $tries = 3;

    /**
     * Delay retry dalam detik (20s, 60s, 180s)
     */
    public array $backoff = [20, 60, 180];

    public function __construct(
        string $recipientEmail,
        string $emailSubject,
        string $htmlBody,
        ?string $recipientName = null,
        ?string $nomorInternet = null,
        ?string $badgeText = 'PEMBERITAHUAN RESMI',
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $senderUser = 'ADMIN',
        ?int $logId = null
    ) {
        $this->recipientEmail = trim($recipientEmail);
        $this->emailSubject   = trim($emailSubject);
        $this->htmlBody       = $htmlBody;
        $this->recipientName  = $recipientName;
        $this->nomorInternet  = $nomorInternet;
        $this->badgeText      = $badgeText;
        $this->actionUrl      = $actionUrl;
        $this->actionText     = $actionText;
        $this->senderUser     = $senderUser;
        $this->logId          = $logId;
    }

    public function handle(): void
    {
        if (empty($this->recipientEmail) || !filter_var($this->recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning("SendCustomMailJob: Alamat email tidak valid: '{$this->recipientEmail}'");
            $this->updateLogStatus('failed', "Format alamat email tujuan tidak valid ({$this->recipientEmail}).");
            return;
        }

        // Render template email HTML lengkap
        $fullHtml = view('emails.custom_template', [
            'subject'       => $this->emailSubject,
            'htmlBody'      => $this->htmlBody,
            'recipientName' => $this->recipientName,
            'nomorInternet' => $this->nomorInternet,
            'badgeText'     => $this->badgeText,
            'actionUrl'     => $this->actionUrl,
            'actionText'    => $this->actionText,
        ])->render();

        try {
            $hostingerService = app(HostingerMailService::class);

            if ($hostingerService->isConfigured()) {
                try {
                    $hostingerService->send($this->recipientEmail, $this->emailSubject, $fullHtml);
                    Log::info("SendCustomMailJob: Berhasil mengirim custom email via Hostinger API ke {$this->recipientEmail} ({$this->recipientName}).");
                } catch (\Throwable $apiEx) {
                    // Jika Hostinger API gagal (misal rate limit / gangguan API) dan SMTP dikonfigurasi, coba fallback via SMTP
                    if (!empty(config('mail.mailers.smtp.host')) && !empty(config('mail.mailers.smtp.username'))) {
                        Log::warning("SendCustomMailJob: Hostinger API gagal ({$apiEx->getMessage()}), mencoba fallback SMTP ke {$this->recipientEmail}...");
                        $this->sendViaSmtpWithRetry();
                        Log::info("SendCustomMailJob: Berhasil mengirim email via fallback SMTP ke {$this->recipientEmail}.");
                    } else {
                        throw $apiEx;
                    }
                }
            } else {
                $this->sendViaSmtpWithRetry();
                Log::info("SendCustomMailJob: Berhasil mengirim custom email via SMTP ke {$this->recipientEmail}.");
            }

            $this->updateLogStatus('sent', null);
        } catch (\Throwable $e) {
            $friendlyErr = $this->formatHumanError($e->getMessage());
            Log::error("SendCustomMailJob Gagal ke {$this->recipientEmail}: {$friendlyErr}");
            $this->updateLogStatus('failed', $friendlyErr);

            // Jika berjalan di background queue (bukan sync), lempar ulang agar mekanisme retry/backoff berjalan
            if ($this->job && $this->job->getConnectionName() !== 'sync') {
                throw $e;
            }
        }
    }

    /**
     * Kirim email via SMTP dengan reset koneksi (purge) otomatis jika socket terputus / rate-limit sesaat
     */
    protected function sendViaSmtpWithRetry(): void
    {
        try {
            Mail::purge('smtp');
        } catch (\Throwable $e) {}

        try {
            Mail::to($this->recipientEmail)->send(new CustomBroadcastMail(
                $this->emailSubject,
                $this->htmlBody,
                $this->recipientName,
                $this->nomorInternet,
                $this->badgeText,
                $this->actionUrl,
                $this->actionText
            ));
        } catch (\Throwable $firstEx) {
            // Tunggu 2 detik lalu coba sekali lagi dengan koneksi SMTP baru
            sleep(2);
            try {
                Mail::purge('smtp');
            } catch (\Throwable $e) {}

            Mail::to($this->recipientEmail)->send(new CustomBroadcastMail(
                $this->emailSubject,
                $this->htmlBody,
                $this->recipientName,
                $this->nomorInternet,
                $this->badgeText,
                $this->actionUrl,
                $this->actionText
            ));
        }
    }

    /**
     * Terjemahkan pesan error teknis SMTP / API menjadi penjelasan Bahasa Indonesia yang jelas
     */
    protected function formatHumanError(string $rawError): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $rawError));
        $lower = strtolower($clean);

        if (str_contains($lower, '429') || str_contains($lower, 'rate limit') || str_contains($lower, 'too many')) {
            return "Batas kecepatan kirim (Rate Limit Server Email): Terlalu banyak email dikirim dalam waktu bersamaan. ({$clean})";
        }
        if (str_contains($lower, '550') || str_contains($lower, 'user unknown') || str_contains($lower, 'address rejected') || str_contains($lower, 'does not exist')) {
            return "Alamat email tujuan tidak ditemukan atau ditolak oleh server penerima (Bounce 550). ({$clean})";
        }
        if (str_contains($lower, '554') || str_contains($lower, 'spam') || str_contains($lower, 'blocked')) {
            return "Pesan ditolak oleh filter anti-spam atau kuota pengiriman terlampaui (554). ({$clean})";
        }
        if (str_contains($lower, '421') || str_contains($lower, '451') || str_contains($lower, 'broken pipe') || str_contains($lower, 'connection reset')) {
            return "Koneksi server email ditutup sementara karena antrean padat (421/451). Silakan klik Kirim Ulang. ({$clean})";
        }
        if (str_contains($lower, 'timed out') || str_contains($lower, 'could not be established') || str_contains($lower, 'connection refused')) {
            return "Koneksi ke server email Hostinger terputus atau waktu habis (Connection Timeout). ({$clean})";
        }
        if (str_contains($lower, '535') || str_contains($lower, 'authentication') || str_contains($lower, 'unauthorized') || str_contains($lower, '401')) {
            return "Autentikasi server email gagal (Token API / Password SMTP tidak sesuai). ({$clean})";
        }

        return $clean !== '' ? $clean : 'Gagal mengirim email karena kendala respons server.';
    }

    /**
     * Dipanggil otomatis oleh Laravel Queue jika seluruh percobaan (tries) habis
     */
    public function failed(\Throwable $e): void
    {
        $friendlyErr = $this->formatHumanError($e->getMessage());
        $this->updateLogStatus('failed', $friendlyErr);
    }

    /**
     * Update log pengiriman di tb_broadcast_email_log jika tabel tersedia
     */
    protected function updateLogStatus(string $status, ?string $errorMessage = null): void
    {
        try {
            if ($this->logId && Schema::hasTable('tb_broadcast_email_log')) {
                DB::table('tb_broadcast_email_log')
                    ->where('id', $this->logId)
                    ->update([
                        'status'        => $status,
                        'error_message' => $errorMessage,
                        'updated_at'    => Carbon::now()->toDateTimeString(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal update status tb_broadcast_email_log: " . $e->getMessage());
        }
    }
}

