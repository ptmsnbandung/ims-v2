<?php

namespace App\Console\Commands;

use App\Services\Network\CustomerProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RollbackUnintendedUnsuspend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unsuspend:rollback-unintended {nomor_internet?* : Nomor internet spesifik jika ingin rollback manual} {--dry-run : Hanya cek daftar tanpa eksekusi perubahan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengembalikan pelanggan yang sempat ter-unsuspend otomatis kembali ke status Suspend (Opsi Ketat)';

    /**
     * Execute the console command.
     */
    public function handle(CustomerProvisioningService $provisioning): int
    {
        $explicitNumbers = array_filter((array)$this->argument('nomor_internet'));
        $isDryRun = (bool)$this->option('dry-run');

        $this->info("=== ROLLBACK PELANGGAN KE STATUS SUSPEND (OPSI KETAT) ===");
        if ($isDryRun) {
            $this->warn("[MODE DRY-RUN] Hanya menampilkan data tanpa mengubah database atau router.");
        }

        $candidates = [];

        if (!empty($explicitNumbers)) {
            $candidates = array_values(array_unique($explicitNumbers));
            $this->line("Menggunakan nomor internet yang ditentukan secara manual: " . implode(', ', $candidates));
        } else {
            $this->line("Mendeteksi pelanggan yang di-unsuspend otomatis oleh System (Auto) hari ini...");

            $today = date('Y-m-d');
            $autoNumbers = [];

            // 1. Deteksi dari trx_suspend_log
            if (Schema::hasTable('trx_suspend_log')) {
                try {
                    $fromLog = DB::table('trx_suspend_log')
                        ->where('user_create', 'System (Auto)')
                        ->where('date_create', '>=', $today . ' 00:00:00')
                        ->pluck('nomor_internet')
                        ->filter()
                        ->unique()
                        ->toArray();
                    $autoNumbers = array_merge($autoNumbers, $fromLog);
                } catch (\Throwable $th) {}
            }

            // 2. Deteksi dari activity_logs
            if (Schema::hasTable('activity_logs')) {
                try {
                    $fromActivity = DB::table('activity_logs')
                        ->where('user_id', 'System (Auto)')
                        ->where('action', 'activate')
                        ->where('created_at', '>=', $today . ' 00:00:00')
                        ->pluck('customer_id')
                        ->filter()
                        ->unique()
                        ->toArray();
                    $autoNumbers = array_merge($autoNumbers, $fromActivity);
                } catch (\Throwable $th) {}
            }

            // 3. Deteksi dari trx_batchjob_register jika ada yang diupdate System (Auto)
            if (Schema::hasTable('trx_batchjob_register')) {
                try {
                    $fromReg = DB::table('trx_batchjob_register')
                        ->where('user_update', 'System (Auto)')
                        ->where('date_update', '>=', $today . ' 00:00:00')
                        ->pluck('nomor_internet')
                        ->filter()
                        ->unique()
                        ->toArray();
                    $autoNumbers = array_merge($autoNumbers, $fromReg);
                } catch (\Throwable $th) {}
            }

            $autoNumbers = array_values(array_unique(array_filter($autoNumbers)));

            // Pengecualian: Pelanggan yang memang memiliki permohonan Req. Unsuspend (status 18) sah
            // Target customer valid: MS83395720200924
            $validReqUnsuspend = ['MS83395720200924'];

            foreach ($autoNumbers as $num) {
                if (in_array($num, $validReqUnsuspend, true)) {
                    $this->line("<comment>Melewati {$num}: Pelanggan valid Req. Unsuspend (status 18), tetap aktif.</comment>");
                    continue;
                }
                $candidates[] = $num;
            }
        }

        if (empty($candidates)) {
            $this->info("Tidak ditemukan pelanggan yang perlu di-rollback.");
            return 0;
        }

        $this->info("Ditemukan " . count($candidates) . " pelanggan untuk di-rollback ke status Suspend:");

        $rows = [];
        foreach ($candidates as $nomorInternet) {
            $cust = DB::table('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->first();
            $nama = $cust->nama_pelanggan ?? '-';
            $pppoe = $cust->ont_us ?? '-';
            $statusReg = $cust->status_reg ?? '-';

            $suspendRow = DB::table('trx_suspend')->where('nomor_internet', $nomorInternet)->orderBy('date_create', 'desc')->first();
            $statusSuspend = $suspendRow->status_suspend ?? '-';

            $rows[] = [
                'nomor_internet' => $nomorInternet,
                'nama' => $nama,
                'pppoe' => $pppoe,
                'status_reg' => $statusReg,
                'status_suspend' => $statusSuspend,
            ];
        }

        $this->table(['Nomor Internet', 'Nama', 'PPPoE', 'Status Reg Saat Ini', 'Status Suspend Saat Ini'], $rows);

        if ($isDryRun) {
            $this->warn("Dry-run selesai. Jalankan tanpa flag --dry-run untuk mengeksekusi rollback.");
            return 0;
        }

        $this->line("\nMemulai proses pengembalian status ke Suspend (MikroTik disable + Database status 21 & suspend 12)...");

        $successCount = 0;
        $failCount = 0;

        foreach ($candidates as $nomorInternet) {
            $this->line("Memproses {$nomorInternet}...");
            $res = $provisioning->suspendCustomer(
                $nomorInternet,
                'System (Rollback)',
                'Dikembalikan ke Suspend (Opsi Ketat: bukan permohonan Req. Unsuspend 18)',
                false,
                true // allowDbUpdateOnRouterFail agar database dipastikan kembali ke Suspend
            );

            // Pastikan trx_suspend status_suspend kembali menjadi 12 (Suspend Aktif)
            try {
                DB::table('trx_suspend')
                    ->where('nomor_internet', $nomorInternet)
                    ->where('status_suspend', '13')
                    ->orderBy('date_create', 'desc')
                    ->limit(1)
                    ->update([
                        'status_suspend' => '12',
                        'desc_suspend' => 'Dikembalikan ke status Suspend (Opsi Ketat)',
                        'date_update' => now('Asia/Jakarta')->toDateTimeString(),
                        'user_update' => 'System (Rollback)',
                    ]);
            } catch (\Throwable $th) {}

            if ($res['success'] ?? false) {
                $successCount++;
                $this->info(" [OK] {$nomorInternet}: " . ($res['summary'] ?? 'Berhasil dikembalikan ke status Suspend'));
            } else {
                $failCount++;
                $this->warn(" [WARN] {$nomorInternet}: " . ($res['summary'] ?? 'Periksa router'));
            }
        }

        $this->info("\nRollback selesai! Berhasil: {$successCount}, Perhatian: {$failCount}.");
        $this->info("HANYA pelanggan MS83395720200924 yang tetap aktif berstatus Unsuspend.");

        return 0;
    }
}
