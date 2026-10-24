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
            $this->updateLogStatus('failed', 'Alamat email tidak valid.');
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
                $hostingerService->send($this->recipientEmail, $this->emailSubject, $fullHtml);
                Log::info("SendCustomMailJob: Berhasil mengirim custom email via Hostinger API ke {$this->recipientEmail} ({$this->recipientName}).");
            } else {
                Mail::to($this->recipientEmail)->send(new CustomBroadcastMail(
                    $this->emailSubject,
                    $this->htmlBody,
                    $this->recipientName,
                    $this->nomorInternet,
                    $this->badgeText,
                    $this->actionUrl,
                    $this->actionText
                ));
                Log::info("SendCustomMailJob: Berhasil mengirim custom email via SMTP ke {$this->recipientEmail}.");
            }

            $this->updateLogStatus('sent', null);
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
            Log::error("SendCustomMailJob Gagal ke {$this->recipientEmail}: {$errMsg}");
            $this->updateLogStatus('failed', $errMsg);
            throw $e;
        }
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
