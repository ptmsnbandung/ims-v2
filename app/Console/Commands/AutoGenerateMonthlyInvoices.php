<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BillingService;
use Carbon\Carbon;

class AutoGenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:auto-generate-invoices 
                            {--bulan= : Bulan tagihan (01-12), default bulan berjalan}
                            {--tahun= : Tahun tagihan (YYYY), default tahun berjalan}
                            {--user=System Auto Billing : Nama penginisiasi generator}
                            {--include-suspend : Sertakan juga pelanggan berstatus suspend}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis men-generate invoice bulanan massal untuk seluruh pelanggan aktif saja (Setiap tanggal 1 jam 08:00 pagi)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bulan = $this->option('bulan') ?: Carbon::now('Asia/Jakarta')->format('m');
        $tahun = $this->option('tahun') ?: Carbon::now('Asia/Jakarta')->format('Y');
        $user = $this->option('user') ?: 'System Auto Billing';
        $includeSuspend = (bool) $this->option('include-suspend');

        $this->info("Memulai proses Auto Generate Invoice untuk periode {$bulan}/{$tahun} oleh {$user}" . ($includeSuspend ? " (termasuk suspend)" : " (hanya pelanggan aktif)") . "...");

        $result = BillingService::generateMonthlyInvoices($bulan, $tahun, $user, !$includeSuspend);

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        } else {
            $this->error($result['message']);
            return 1;
        }
    }
}
