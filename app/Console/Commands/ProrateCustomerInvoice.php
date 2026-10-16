<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ProrateCustomerInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:prorate-invoice 
                            {target : Nomor Internet (contoh: 12110224) atau Kode Invoice (contoh: INV/12110224/10/2026)}
                            {--tgl= : Tanggal mulai aktif (1-31, default: tanggal hari ini)}
                            {--bulan= : Bulan tagihan (01-12, default: bulan berjalan)}
                            {--tahun= : Tahun tagihan (YYYY, default: tahun berjalan)}
                            {--user=Admin : Nama pengubah}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyesuaikan invoice bulan berjalan menjadi Prorate berdasarkan sisa hari aktif setelah unsuspend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $target = trim($this->argument('target'));
        $now = Carbon::now('Asia/Jakarta');
        $bulan = str_pad((string)($this->option('bulan') ?: $now->format('m')), 2, '0', STR_PAD_LEFT);
        $tahun = (string)($this->option('tahun') ?: $now->format('Y'));
        $currentDay = (int)($this->option('tgl') ?: $now->format('d'));
        $user = $this->option('user') ?: 'Admin';

        // Cari invoice target
        if (str_contains($target, 'INV/')) {
            $inv = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $target)->first();
        } else {
            $inv = DB::table('trx_billing_layanan')
                ->where('nomor_internet', $target)
                ->where('bulan_tagihan', $bulan)
                ->where('tahun_tagihan', $tahun)
                ->where('hide', '!=', '1')
                ->first();
        }

        if (!$inv) {
            $this->error("Invoice untuk '{$target}' pada periode {$bulan}/{$tahun} tidak ditemukan.");
            return 1;
        }

        $totalDays = (int)Carbon::createFromDate((int)$tahun, (int)$bulan, 1)->daysInMonth;
        if ($currentDay > $totalDays || $currentDay < 1) {
            $this->error("Tanggal aktif ({$currentDay}) tidak valid untuk bulan {$bulan} (1-{$totalDays}).");
            return 1;
        }

        $activeDays = max(1, $totalDays - $currentDay + 1);

        // Cari harga normal paket
        $normalPrice = (float)($inv->total_layanan ?? 0) + (float)($inv->potongan ?? 0);
        if ($normalPrice <= 0) {
            $custTable = Schema::hasTable('view_batchjob') ? 'view_batchjob' : 'trx_batchjob_register';
            $custObj = DB::table($custTable)->where('nomor_internet', $inv->nomor_internet)->first();
            $normalPrice = (float)($custObj->harga_bandwith ?? 225000);
        }

        $prorateAmount = round(($activeDays / $totalDays) * $normalPrice);
        $prorateDiscount = max(0, $normalPrice - $prorateAmount);

        DB::table('trx_billing_layanan')
            ->where('kode_billing_layanan', $inv->kode_billing_layanan)
            ->update([
                'potongan' => (string) $prorateDiscount,
                'desc_potongan' => "Prorate Suspend: Aktif tgl {$currentDay}-{$totalDays} ({$activeDays}/{$totalDays} hari)",
                'total_layanan' => (string) $prorateAmount,
                'note_adjusment' => "Prorate disesuaikan via CLI oleh {$user}",
                'date_update' => $now->toDateTimeString(),
                'user_update' => $user,
            ]);

        if (Schema::hasTable('trx_billing_layanan_log')) {
            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $inv->kode_billing_layanan,
                'status_bill_lay' => $inv->status_bill_lay,
                'note_billing_lay' => "Penyesuaian Prorate: {$inv->kode_billing_layanan} disesuaikan menjadi Rp " . number_format($prorateAmount, 0, ',', '.') . " ({$activeDays}/{$totalDays} hari aktif) oleh {$user}",
                'date_create' => $now->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);
        }

        $this->info("Berhasil! Invoice {$inv->kode_billing_layanan} telah disesuaikan menjadi Prorate:");
        $this->table(
            ['Parameter', 'Rincian'],
            [
                ['Kode Invoice', $inv->kode_billing_layanan],
                ['Nomor Internet', $inv->nomor_internet],
                ['Harga Normal Paket', 'Rp ' . number_format($normalPrice, 0, ',', '.')],
                ['Hari Aktif Layanan', "{$activeDays} hari (tgl {$currentDay} s/d {$totalDays} {$bulan}/{$tahun})"],
                ['Potongan Prorate', 'Rp ' . number_format($prorateDiscount, 0, ',', '.')],
                ['Total Tagihan Baru', 'Rp ' . number_format($prorateAmount, 0, ',', '.')],
            ]
        );

        return 0;
    }
}
