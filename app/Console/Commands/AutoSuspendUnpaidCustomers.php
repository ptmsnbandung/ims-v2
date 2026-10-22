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
    protected $signature = 'suspend:unpaid-customers {--user=System Auto Suspend : Nama pengaju suspend} {--dry-run : Simulasi tanpa menyimpan ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mengajukan request suspend ke NOC untuk pelanggan yang belum bayar langganan (Jatuh tempo tanggal 25 jam 06:00 pagi)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $this->info('Starting Auto Request Suspend for Unpaid Customers...' . ($isDryRun ? ' [DRY RUN]' : ''));

        if (!Schema::hasTable('trx_billing_layanan') || !Schema::hasTable('trx_suspend')) {
            $this->error('Tabel trx_billing_layanan atau trx_suspend tidak ditemukan.');
            return 1;
        }

        $now = Carbon::now()->toDateTimeString();
        $today = Carbon::now()->format('Y-m-d');
        $userCreate = $this->option('user') ?: 'System Auto Suspend';

        $customerTable = Schema::hasTable('trx_batchjob_register') ? 'trx_batchjob_register' : 'view_batchjob';

        // 1. Ambil nomor internet pelanggan AKTIF (status_reg = '20') yang memiliki tagihan belum lunas (status_bill_lay != '15')
        $unpaidQuery = DB::table('trx_billing_layanan as b')
            ->join("{$customerTable} as r", 'b.nomor_internet', '=', 'r.nomor_internet')
            ->where('b.status_bill_lay', '!=', '15')
            ->where(function ($q) {
                $q->whereNull('b.hide')->orWhere('b.hide', '0');
            })
            ->where('r.status_reg', '20') // Hanya pelanggan yang status layanannya aktif
            ->where(function ($q) {
                $q->whereNull('r.hide')->orWhere('r.hide', '0');
            })
            ->whereNotNull('b.nomor_internet')
            ->where('b.nomor_internet', '!=', '')
            ->distinct()
            ->pluck('b.nomor_internet');

        if ($unpaidQuery->isEmpty()) {
            $this->info('Tidak ada pelanggan aktif dengan tagihan menunggak/belum lunas.');
            return 0;
        }

        // 2. Ambil pelanggan yang sudah dalam antrean atau status suspend aktif untuk menghindari duplikasi
        // Status suspend: 11 (Req Suspend), 12 (Suspend/Isolir Aktif), 18 (Req Unsuspend)
        $existingSuspendNumbers = DB::table('trx_suspend')
            ->whereIn('status_suspend', ['11', '12', '18'])
            ->where(function ($q) {
                $q->whereNull('hide')->orWhere('hide', '0');
            })
            ->pluck('nomor_internet')
            ->unique()
            ->toArray();

        $existingLookup = array_flip($existingSuspendNumbers);
        $candidates = [];
        $skippedCount = 0;

        foreach ($unpaidQuery as $nomorInternet) {
            $nomorInternet = trim($nomorInternet);
            if (empty($nomorInternet)) {
                continue;
            }

            // Lewati jika sudah ada di antrean suspend atau sedang terisolir
            if (isset($existingLookup[$nomorInternet])) {
                $skippedCount++;
                continue;
            }

            $candidates[] = $nomorInternet;
            $existingLookup[$nomorInternet] = true; // Tandai agar tidak ganda di loop ini
        }

        $totalCandidates = count($candidates);
        if ($totalCandidates === 0) {
            $this->info("Semua ({$skippedCount}) pelanggan menunggak sudah memiliki status/antrean suspend aktif. Tidak ada request baru yang dibuat.");
            return 0;
        }

        $this->info("Ditemukan {$totalCandidates} pelanggan aktif yang menunggak dan siap diajukan suspend ke NOC. ({$skippedCount} dilewati).");

        if ($isDryRun) {
            $this->warn("[DRY RUN] Simulasi selesai. {$totalCandidates} data suspend TIDAK dimasukkan ke database.");
            return 0;
        }

        // 3. Batch insert dengan chunk 100 record per query untuk performa tinggi dan efisiensi memori
        $chunks = array_chunk($candidates, 100);
        $insertedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($chunks as $chunk) {
                $insertRows = [];
                foreach ($chunk as $nomorInternet) {
                    $kodeSuspend = $nomorInternet . '-' . mt_rand(1000000, 9999999);
                    $insertRows[] = [
                        'kode_suspend'   => $kodeSuspend,
                        'nomor_internet' => $nomorInternet,
                        'suspend_start'  => $today,
                        'status_suspend' => '11', // 11: Request Suspend Baru ke NOC
                        'desc_suspend'   => 'Otomatis Request Suspend oleh System (Jatuh Tempo Tanggal 25 jam 06:00)',
                        'date_create'    => $now,
                        'user_create'    => mb_substr($userCreate, 0, 50),
                        'hide'           => '0',
                    ];
                }

                DB::table('trx_suspend')->insert($insertRows);
                $insertedCount += count($insertRows);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal melakukan batch insert auto suspend: " . $e->getMessage());
            Log::error("Auto Suspend Batch Insert Error: " . $e->getMessage(), ['exception' => $e]);
            return 1;
        }

        $logMsg = "Auto Suspend Completed: {$insertedCount} request suspend berhasil dibuat ke NOC. ({$skippedCount} pelanggan dilewati/sudah diproses).";
        $this->info($logMsg);
        Log::info($logMsg);

        return 0;
    }
}
