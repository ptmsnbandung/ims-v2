<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AutoSuspendUnpaidCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suspend:unpaid-customers {--user=System Auto Suspend : Nama pengaju suspend}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mengajukan request suspend ke NOC untuk pelanggan yang belum bayar langganan (Jatuh tempo tanggal 25 jam 06:00 pagi)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Auto Request Suspend for Unpaid Customers...');

        if (!Schema::hasTable('trx_billing_layanan') || !Schema::hasTable('trx_suspend')) {
            $this->error('Tabel trx_billing_layanan atau trx_suspend tidak ditemukan.');
            return 1;
        }

        $now = Carbon::now()->toDateTimeString();
        $today = Carbon::now()->format('Y-m-d');
        $userCreate = $this->option('user') ?: 'System Auto Suspend';

        // 1. Ambil nomor internet pelanggan yang memiliki tagihan belum lunas (status_bill_lay != '15')
        $unpaidInternetNumbers = DB::table('trx_billing_layanan')
            ->where('status_bill_lay', '!=', '15')
            ->whereNotNull('nomor_internet')
            ->where('nomor_internet', '!=', '')
            ->distinct()
            ->pluck('nomor_internet');

        if ($unpaidInternetNumbers->isEmpty()) {
            $this->info('Tidak ada pelanggan dengan tagihan menunggak/belum lunas.');
            return 0;
        }

        // 2. Ambil pelanggan yang sudah dalam antrean atau status suspend aktif untuk menghindari duplikasi
        // Status suspend: 11 (Req Suspend), 12 (Suspend/Isolir Aktif), 18 (Req Unsuspend)
        $existingSuspendNumbers = DB::table('trx_suspend')
            ->whereIn('status_suspend', ['11', '12', '18'])
            ->pluck('nomor_internet')
            ->toArray();

        $suspendCount = 0;
        $skippedCount = 0;

        foreach ($unpaidInternetNumbers as $nomorInternet) {
            // Jika pelanggan sudah punya status suspend aktif/pending, lewati
            if (in_array($nomorInternet, $existingSuspendNumbers)) {
                $skippedCount++;
                continue;
            }

            // Pastikan data pelanggan ada di batchjob / master pelanggan
            $customerExists = DB::table('view_batchjob')->where('nomor_internet', $nomorInternet)->exists();
            if (!$customerExists && Schema::hasTable('trx_batchjob_register')) {
                $customerExists = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->exists();
            }

            if (!$customerExists) {
                $skippedCount++;
                continue;
            }

            $kodeSuspend = $nomorInternet . '-' . rand(1000000, 9999999);

            DB::table('trx_suspend')->insert([
                'kode_suspend' => $kodeSuspend,
                'nomor_internet' => $nomorInternet,
                'suspend_start' => $today,
                'status_suspend' => '11', // 11: Request Suspend Baru ke NOC
                'desc_suspend' => 'Otomatis Request Suspend oleh System (Jatuh Tempo Tanggal 25 jam 06:00)',
                'date_create' => $now,
                'user_create' => $userCreate,
                'hide' => '0',
            ]);

            // Masukkan ke array agar loop selanjutnya tidak duplikat jika ada multiple unpaid billings
            $existingSuspendNumbers[] = $nomorInternet;
            $suspendCount++;
        }

        $logMsg = "Auto Suspend Completed: {$suspendCount} request suspend berhasil dibuat ke NOC. ({$skippedCount} pelanggan dilewati/sudah diproses).";
        $this->info($logMsg);
        Log::info($logMsg);

        return 0;
    }
}
