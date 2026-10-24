<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $htmlBody;
    public ?string $recipientName;
    public ?string $nomorInternet;
    public ?string $badgeText;
    public ?string $actionUrl;
    public ?string $actionText;

    public function __construct(
        string $emailSubject,
        string $htmlBody,
        ?string $recipientName = null,
        ?string $nomorInternet = null,
        ?string $badgeText = 'PEMBERITAHUAN RESMI',
        ?string $actionUrl = null,
        ?string $actionText = null
    ) {
        $this->emailSubject  = $emailSubject;
        $this->htmlBody      = $htmlBody;
        $this->recipientName = $recipientName;
        $this->nomorInternet = $nomorInternet;
        $this->badgeText     = $badgeText;
        $this->actionUrl     = $actionUrl;
        $this->actionText    = $actionText;
    }

    public function build()
    {
        return $this->subject($this->emailSubject)
                    ->view('emails.custom_template', [
                        'subject'       => $this->emailSubject,
                        'htmlBody'      => $this->htmlBody,
                        'recipientName' => $this->recipientName,
                        'nomorInternet' => $this->nomorInternet,
                        'badgeText'     => $this->badgeText,
                        'actionUrl'     => $this->actionUrl,
                        'actionText'    => $this->actionText,
                    ]);
    }
}

