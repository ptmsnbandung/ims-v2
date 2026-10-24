<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $billing;
    public $customer;
    public array $paymentData;

    /**
     * Create a new message instance.
     */
    public function __construct($billing, $customer = null, array $paymentData = [])
    {
        $this->billing = $billing;
        $this->customer = $customer;
        $this->paymentData = $paymentData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $invoiceNumber = $this->billing->kode_billing_layanan ?? 'INVOICE';
        $companyName = config('company.name', 'PT Media Solusi Network');

        return $this->subject("[LUNAS] Bukti Pembayaran Tagihan Internet - {$invoiceNumber} - {$companyName}")
                    ->view('emails.payment_success');
    }
}
