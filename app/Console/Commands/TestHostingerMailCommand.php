<?php

namespace App\Console\Commands;

use App\Services\HostingerMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestHostingerMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-hostinger {email : Alamat email penerima tujuan} {kode_billing? : Kode billing opsional untuk tes invoice}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email tes langsung ke alamat email tujuan via Hostinger Mail REST API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $to = trim($this->argument('email'));
        $kodeBilling = $this->argument('kode_billing') ? trim($this->argument('kode_billing')) : null;

        $this->info("==================================================");
        $this->info("    PENGUJIAN HOSTINGER MAIL REST API");
        $this->info("==================================================");
        $this->line("Penerima : {$to}");

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Alamat email '{$to}' tidak valid!");
            return 1;
        }

        $token = config('services.hostinger_mail.token') ?: env('HOSTINGER_MAIL_TOKEN');
        if (empty($token)) {
            $this->error("HOSTINGER_MAIL_TOKEN belum disetel di file .env atau config cache!");
            $this->line("Silakan tambahkan: HOSTINGER_MAIL_TOKEN=... di .env lalu jalankan 'php artisan config:clear'");
            return 1;
        }

        $maskedToken = substr($token, 0, 6) . '...' . substr($token, -4);
        $this->line("Token API: {$maskedToken}");

        // Jika ada kode billing, ambil data aslinya
        $billing = null;
        $customer = null;

        if ($kodeBilling) {
            $this->line("Kode Inv : {$kodeBilling}");
            $billing = DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $kodeBilling)
                ->orWhere('kode_billing_layanan', str_replace('/', '-', $kodeBilling))
                ->orWhere('kode_billing_layanan', str_replace('-', '/', $kodeBilling))
                ->first();

            if ($billing) {
                $nomorInternet = $billing->nomor_internet;
                if ($nomorInternet) {
                    if (Schema::hasTable('view_batchjob')) {
                        try {
                            $customer = DB::table('view_batchjob')->where('nomor_internet', $nomorInternet)->first();
                        } catch (\Throwable $e) {}
                    }
                    if (!$customer && Schema::hasTable('trx_batchjob_register')) {
                        $q = DB::table('trx_batchjob_register as r')->where('r.nomor_internet', $nomorInternet);
                        if (Schema::hasTable('m_pelanggan')) {
                            $q->leftJoin('m_pelanggan as p', 'r.nik_penduduk', '=', 'p.nik_penduduk');
                        }
                        if (Schema::hasTable('m_bandwith')) {
                            $q->leftJoin('m_bandwith as b', 'r.kode_bandwith', '=', 'b.kode_bandwith');
                        }
                        $customer = $q->select(
                            'r.*',
                            DB::raw('COALESCE(r.nama_pelanggan, p.nama_penduduk) as nama_pelanggan'),
                            'p.email',
                            'p.nomor_hp',
                            'b.nama_bandwith'
                        )->first();
                    }
                }
            } else {
                $this->warn("Data billing '{$kodeBilling}' tidak ditemukan di tabel trx_billing_layanan, menggunakan data dummy tes.");
            }
        }

        if (!$billing) {
            // Data dummy tes
            $billing = (object) [
                'kode_billing_layanan' => $kodeBilling ?: 'INV/TEST/' . date('YmdHis'),
                'nomor_internet' => '100001',
                'period' => date('F Y'),
                'amount_paid' => '150000',
                'total_layanan' => '150000',
                'merchant_type' => 'Bank Mandiri',
                'payment_paid' => date('Y-m-d H:i:s'),
            ];
            $customer = (object) [
                'nama_pelanggan' => 'Pelanggan Uji Coba',
                'nomor_internet' => '100001',
                'email' => $to,
                'nama_bandwith' => 'Corporate Broadband 50 Mbps',
            ];
        }

        $subject = "[TEST] Bukti Pembayaran Tagihan Internet - {$billing->kode_billing_layanan} - PT Media Solusi Network";

        $this->line("Subjek   : {$subject}");
        $this->line("Merender template HTML emails.payment_success...");

        try {
            $html = view('emails.payment_success', [
                'billing' => $billing,
                'customer' => $customer,
                'paymentData' => [
                    'metode_bayar' => $billing->merchant_type ?? 'Transfer Bank',
                    'nominal' => $billing->amount_paid ?? 150000,
                    'catatan' => 'Uji Coba Pengiriman Manual via Hostinger Mail API',
                ],
            ])->render();
        } catch (\Throwable $e) {
            $this->error("Gagal merender template email: " . $e->getMessage());
            return 1;
        }

        $this->line("Mengirim request ke https://api.mail.hostinger.com/v1/emails...");

        try {
            $service = app(HostingerMailService::class);
            $result = $service->send($to, $subject, $html);

            if ($result) {
                $this->newLine();
                $this->info("==================================================");
                $this->info("  BERHASIL! Email telah diterima oleh Hostinger.");
                $this->info("  Silakan cek inbox/spam di: {$to}");
                $this->info("==================================================");
                return 0;
            } else {
                $this->error("Hostinger Mail API menolak pengiriman email. Cek log untuk detail.");
                return 1;
            }
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("==================================================");
            $this->error("  GAGAL MENGIRIM EMAIL KE HOSTINGER API!");
            $this->error("  Pesan Error: " . $e->getMessage());
            $this->error("==================================================");
            return 1;
        }
    }
}
