<?php

namespace App\Console\Commands;

use App\Services\Network\CustomerProvisioningService;
use Illuminate\Console\Command;

class AutoProcessReqUnsuspend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unsuspend:process-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mengeksekusi buka isolir (unsuspend) ke MikroTik dan OLT untuk permohonan status 18 tanpa perlu menunggu approve manual';

    /**
     * Execute the console command.
     */
    public function handle(CustomerProvisioningService $provisioning): int
    {
        $this->info('Memeriksa antrean permohonan Req. Unsuspend dan pelanggan suspend yang sudah lunas...');

        $result = $provisioning->processAutoUnsuspendQueue();

        if ($result['processed'] > 0) {
            $this->info("Berhasil memproses {$result['processed']} pelanggan untuk auto-unsuspend:");
            foreach ($result['results'] as $nomorInternet => $info) {
                $status = ($info['success'] ?? false) ? 'BERHASIL' : 'GAGAL';
                $this->line("- [{$status}] {$nomorInternet}: " . ($info['summary'] ?? ''));
            }
        } else {
            $this->info('Tidak ada antrean Req. Unsuspend atau pelanggan suspend lunas yang pending.');
        }

        return 0;
    }
}
