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
    protected $signature = 'unsuspend:process-requests {nomor_internet?}';

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
        $targetNumber = trim((string)$this->argument('nomor_internet'));
        if ($targetNumber !== '') {
            $this->info("Menjalankan unsuspend langsung untuk nomor internet: {$targetNumber}...");
            $res = $provisioning->activateCustomer($targetNumber, 'Manual CLI', 'Eksekusi unsuspend via artisan', true);
            $status = ($res['success'] ?? false) ? 'BERHASIL' : 'GAGAL';
            $this->line("- [{$status}] {$targetNumber}: " . ($res['summary'] ?? ''));
            return ($res['success'] ?? false) ? 0 : 1;
        }

        $this->info('Memeriksa antrean permohonan Req. Unsuspend dan pelanggan suspend yang sudah lunas...');

        try {
            $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            $this->line("<comment>Database terhubung:</comment> {$dbName}");

            $vCount = \Illuminate\Support\Facades\DB::table('view_suspend')
                ->where(function($q) {
                    $q->where('status_suspend', '18')
                      ->orWhere('status_suspend', 18)
                      ->orWhere('status_suspend', '1018')
                      ->orWhere('status_suspend', 'like', '%18%');
                })->count();
            $this->line("Data status 18 di view_suspend: {$vCount}");

            $tCount = \Illuminate\Support\Facades\DB::table('trx_suspend')
                ->where(function($q) {
                    $q->where('status_suspend', '18')
                      ->orWhere('status_suspend', 18)
                      ->orWhere('status_suspend', '1018')
                      ->orWhere('status_suspend', 'like', '%18%');
                })->count();
            $this->line("Data status 18 di trx_suspend: {$tCount}");
        } catch (\Throwable $diagErr) {
            $this->warn("Diagnostik antrean info: " . $diagErr->getMessage());
        }

        $result = $provisioning->processAutoUnsuspendQueue();

        if ($result['processed'] > 0) {
            $this->info("Berhasil memproses {$result['processed']} pelanggan untuk auto-unsuspend:");
            foreach ($result['results'] as $nomorInternet => $info) {
                $status = ($info['success'] ?? false) ? 'BERHASIL' : 'GAGAL';
                $this->line("- [{$status}] {$nomorInternet}: " . ($info['summary'] ?? ''));
            }
        } else {
            if (!empty($result['error'])) {
                $this->error("Gagal memproses antrean auto-unsuspend: " . $result['error']);
            } else {
                $this->info('Tidak ada antrean Req. Unsuspend atau pelanggan suspend lunas yang pending.');
            }
        }

        return 0;
    }
}
