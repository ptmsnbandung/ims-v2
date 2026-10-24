<?php

namespace App\Jobs;

use App\Mail\PaymentSuccessMail;
use App\Services\HostingerMailService;
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

class SendPaymentSuccessMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $kodeBilling;
    public array $paymentData;

    /**
     * Jumlah percobaan maksimal jika terjadi kegagalan jaringan
     */
    public int $tries = 3;

    /**
     * Delay retry dalam detik (30 detik, 2 menit, 5 menit)
     */
    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(string $kodeBilling, array $paymentData = [])
    {
        $this->kodeBilling = trim($kodeBilling);
        $this->paymentData = $paymentData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->kodeBilling)) {
            Log::warning("SendPaymentSuccessMailJob: Kode billing kosong, job dibatalkan.");
            return;
        }

        // 1. Cari data billing layanan
        $billing = DB::table('trx_billing_layanan')
            ->where('kode_billing_layanan', $this->kodeBilling)
            ->orWhere('kode_billing_layanan', str_replace('/', '-', $this->kodeBilling))
            ->orWhere('kode_billing_layanan', str_replace('-', '/', $this->kodeBilling))
            ->first();

        // Jika tidak ditemukan di billing layanan, cari di billing registrasi (pasang baru)
        if (!$billing && Schema::hasTable('trx_billing_registrasi')) {
            $billing = DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $this->kodeBilling)
                ->orWhere('kode_billing_registrasi', str_replace('/', '-', $this->kodeBilling))
                ->orWhere('kode_billing_registrasi', str_replace('-', '/', $this->kodeBilling))
                ->first();

            if ($billing) {
                $billing->kode_billing_layanan = $billing->kode_billing_registrasi;
                $billing->period = 'Tagihan Registrasi / Pasang Baru';
                $billing->total_layanan = $billing->total_registrasi ?? 0;
            }
        }

        if (!$billing) {
            Log::warning("SendPaymentSuccessMailJob: Data tagihan tidak ditemukan untuk kode {$this->kodeBilling}.");
            return;
        }

        // 2. Ambil data pelanggan berdasarkan nomor internet
        $customer = null;
        $nomorInternet = $billing->nomor_internet ?? null;

        if ($nomorInternet) {
            // Cek view_batchjob terlebih dahulu jika tersedia
            if (Schema::hasTable('view_batchjob')) {
                try {
                    $customer = DB::table('view_batchjob')
                        ->where('nomor_internet', $nomorInternet)
                        ->first();
                } catch (\Throwable $e) {
                    $customer = null;
                }
            }

            // Fallback ke tabel trx_batchjob_register join m_pelanggan
            if (!$customer && Schema::hasTable('trx_batchjob_register')) {
                $query = DB::table('trx_batchjob_register as r')
                    ->where('r.nomor_internet', $nomorInternet);

                if (Schema::hasTable('m_pelanggan')) {
                    $query->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk');
                }
                if (Schema::hasTable('m_bandwith')) {
                    $query->leftJoin('m_bandwith as b', 'r.kode_bandwith', '=', 'b.kode_bandwith');
                }

                $customer = $query->select(
                    'r.*',
                    DB::raw('COALESCE(r.nama_pelanggan, p.nama_penduduk) as nama_pelanggan'),
                    'p.email',
                    'p.nomor_hp',
                    'b.nama_bandwith'
                )->first();
            }
        }

        // Jika email masih belum ketemu, coba cari langsung ke m_pelanggan via NIK jika ada
        if ($customer && empty($customer->email) && !empty($customer->nik_penduduk) && Schema::hasTable('m_pelanggan')) {
            $emailPelanggan = DB::table('m_pelanggan')->where('nik_penduduk', $customer->nik_penduduk)->value('email');
            if ($emailPelanggan) {
                $customer->email = $emailPelanggan;
            }
        }

        // Cek apakah alamat email pelanggan valid
        $recipientEmail = trim((string)($customer?->email ?? ''));
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::info("SendPaymentSuccessMailJob: Dilewati karena pelanggan #{$nomorInternet} (" . ($customer?->nama_pelanggan ?? 'Tanpa Nama') . ") belum memiliki email valid di sistem.");
            return;
        }

        // 3. Render Template Email & Tentukan Subjek
        $invoiceNumber = $billing->kode_billing_layanan ?? 'INVOICE';
        $companyName = config('company.name', 'PT Media Solusi Network');
        $subject = "[LUNAS] Bukti Pembayaran Tagihan Internet - {$invoiceNumber} - {$companyName}";

        $htmlContent = view('emails.payment_success', [
            'billing'     => $billing,
            'customer'    => $customer,
            'paymentData' => $this->paymentData,
        ])->render();

        // 4. Kirim Email (Utamakan Hostinger Mail REST API, fallback ke SMTP jika token belum disetel)
        try {
            $hostingerService = app(HostingerMailService::class);

            if ($hostingerService->isConfigured()) {
                $hostingerService->send($recipientEmail, $subject, $htmlContent);
                Log::info("SendPaymentSuccessMailJob: Berhasil mengirim bukti pembayaran via Hostinger Mail API ke {$recipientEmail} untuk invoice {$this->kodeBilling}.");
            } else {
                Mail::to($recipientEmail)->send(new PaymentSuccessMail($billing, $customer, $this->paymentData));
                Log::info("SendPaymentSuccessMailJob: Berhasil mengirim bukti pembayaran via SMTP ke {$recipientEmail} untuk invoice {$this->kodeBilling}.");
            }
        } catch (\Throwable $e) {
            Log::error("SendPaymentSuccessMailJob Gagal untuk invoice {$this->kodeBilling} ({$recipientEmail}): " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw agar Laravel Queue me-retry job sesuai konfigurasi backoff
            throw $e;
        }
    }
}
