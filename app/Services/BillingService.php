<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BillingService
{
    /**
     * Batch / Auto Generate Monthly Invoices untuk seluruh pelanggan aktif & suspend.
     *
     * @param string|int $bulan Bulan tagihan (01-12)
     * @param string|int $tahun Tahun tagihan (YYYY)
     * @param string $userCreator Nama user atau sistem pembuat
     * @return array
     */
    public static function generateMonthlyInvoices($bulan, $tahun, string $userCreator = 'System Auto Billing'): array
    {
        $bulan = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (string) $tahun;
        $namaBulanShort = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->format('M');
        $periodeTagihan = "{$namaBulanShort} {$tahun}";

        if (!Schema::hasTable('trx_billing_layanan')) {
            return [
                'success' => false,
                'message' => 'Tabel trx_billing_layanan tidak ditemukan di database.',
                'created' => 0,
                'skipped' => 0,
                'periode' => $periodeTagihan,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ];
        }

        DB::beginTransaction();
        try {
            // Ambil semua pelanggan aktif dan suspend (20: Aktif, 21 & 21.1: Suspend, 22: Req Suspend)
            $pelanggans = DB::table('view_batchjob')
                ->whereIn('status_reg', ['20', '21', '21.1', '22'])
                ->where('hide', '0')
                ->whereNotNull('nomor_internet')
                ->where('nomor_internet', '!=', '')
                ->get()
                ->unique('nomor_internet');

            $createdCount = 0;
            $skippedCount = 0;
            $now = Carbon::now()->toDateTimeString();

            foreach ($pelanggans as $p) {
                $kodeBilling = "INV/{$p->nomor_internet}/{$bulan}/{$tahun}";

                // Cek apakah invoice periode ini sudah pernah digenerate
                $exists = DB::table('trx_billing_layanan')
                    ->where('kode_billing_layanan', $kodeBilling)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $hargaBandwith = (float) ($p->harga_bandwith ?? 0);
                $potongan = (float) ($p->potongan ?? 0);
                $totalLayanan = max(0, $hargaBandwith - $potongan);

                // Buat record di trx_billing_layanan
                DB::table('trx_billing_layanan')->insert([
                    'kode_billing_layanan' => $kodeBilling,
                    'nomor_internet' => $p->nomor_internet,
                    'kode_bandwith' => $p->kode_bandwith,
                    'nominal_bandwith' => $p->nominal_bandwith ?? '0',
                    'bulan_tagihan' => $bulan,
                    'tahun_tagihan' => $tahun,
                    'periode_tagihan' => $periodeTagihan,
                    'potongan' => (string) $potongan,
                    'desc_potongan' => $p->potongan_note ?? '-',
                    'ppn' => $p->ppn_nom ?? '0.11',
                    'tax' => $p->ppn ?? '2',
                    'voucher' => '-',
                    'total_layanan' => (string) $totalLayanan,
                    'notif_mail' => '0',
                    'notif_wa' => '0',
                    'status_bill_lay' => '12', // Generating... Auto Publish
                    'denda' => null,
                    'invoice_file' => null,
                    'payment_type' => '1', // Default Midtrans
                    'payment_post' => '-',
                    'date_create' => $now,
                    'user_create' => $userCreator,
                    'date_update' => $now,
                    'user_update' => $userCreator,
                    'hide' => '0',
                    'islock' => null,
                ]);

                // Buat detail item layanan
                if (Schema::hasTable('trx_billing_layanan_detail')) {
                    DB::table('trx_billing_layanan_detail')->insert([
                        'kode_billing_lay_detail' => "D-{$p->nomor_internet}-{$bulan}/{$tahun}-T11",
                        'kode_billing_layanan' => $kodeBilling,
                        'kode_item' => 'T11',
                        'komponen' => 'LAYANAN ' . ($p->nama_kategori_bandwith ?? 'INTERNET') . ' ' . ($p->nominal_bandwith ?? '') . ' Mbps',
                        'qty' => 1,
                        'biaya' => (string) $hargaBandwith,
                        'date_create' => $now,
                        'user_create' => $userCreator,
                        'hide' => null,
                    ]);
                }

                // Buat log transaksi
                if (Schema::hasTable('trx_billing_layanan_log')) {
                    DB::table('trx_billing_layanan_log')->insert([
                        'kode_billing_lay_log' => "LOG-{$p->nomor_internet}-{$bulan}/{$tahun}-" . time() . "-{$createdCount}",
                        'kode_billing_layanan' => $kodeBilling,
                        'status_bill_lay' => '12',
                        'note_billing_lay' => "Batch Invoice Generated by {$userCreator}",
                        'date_create' => $now,
                        'user_create' => $userCreator,
                        'hide' => '0',
                    ]);
                }

                $createdCount++;
            }

            DB::commit();

            Log::info("Monthly Invoices Generated for {$periodeTagihan} by {$userCreator}: {$createdCount} created, {$skippedCount} skipped.");

            return [
                'success' => true,
                'message' => "Berhasil men-generate {$createdCount} invoice untuk periode {$periodeTagihan}. ({$skippedCount} invoice sudah ada sebelumnya).",
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'periode' => $periodeTagihan,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to generate monthly invoices for {$periodeTagihan}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal men-generate invoice: ' . $e->getMessage(),
                'created' => 0,
                'skipped' => 0,
                'periode' => $periodeTagihan,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ];
        }
    }
}
