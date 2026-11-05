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
    public static function generateMonthlyInvoices($bulan, $tahun, string $userCreator = 'System Auto Billing', bool $activeOnly = true): array
    {
        $bulan = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (string) $tahun;
        $namaBulanShort = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->format('M');
        $periodeTagihan = "{$namaBulanShort} {$tahun}";

        $bulanInt = (int) $bulan;
        $tahunInt = (int) $tahun;

        // Bulan dan tahun sebelumnya (yang wajib sudah tercatat di last_month_billing & last_year_billing)
        if ($bulanInt === 1) {
            $prevMonthInt = 12;
            $prevYearInt = $tahunInt - 1;
        } else {
            $prevMonthInt = $bulanInt - 1;
            $prevYearInt = $tahunInt;
        }

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
            // Ambil pelanggan: hanya aktif ('20') jika $activeOnly = true, atau sertakan suspend jika false
            $allowedStatus = $activeOnly ? ['20'] : ['20', '21', '21.1', '22'];

            $pelanggans = DB::table('view_batchjob')
                ->whereIn('status_reg', $allowedStatus)
                ->where('hide', '0')
                ->whereNotNull('nomor_internet')
                ->where('nomor_internet', '!=', '')
                ->get()
                ->unique('nomor_internet');

            $createdCount = 0;
            $skippedCount = 0;
            $now = Carbon::now()->toDateTimeString();

            foreach ($pelanggans as $p) {
                // 1. Filter: Bukan Broadband Free (BF)
                $kodeKategori = strtoupper(trim($p->kode_kategori_bandwith ?? ''));
                $aliasKategori = strtoupper(trim($p->alias_nama_kategori ?? ''));
                $namaKategori = strtoupper(trim($p->nama_kategori_bandwith ?? ''));
                if ($kodeKategori === 'BF' || $aliasKategori === 'BF' || str_contains($namaKategori, 'BROADBAND FREE')) {
                    $skippedCount++;
                    continue;
                }

                // 2. Filter: Tagihan Tidak Boleh Rp 0 (bukan pelanggan gratisan)
                $hargaBandwith = (float) ($p->harga_bandwith ?? $p->nominal_bandwith ?? 0);
                $potongan = (float) ($p->potongan ?? 0);
                $totalLayanan = max(0, $hargaBandwith - $potongan);
                if ($totalLayanan <= 0) {
                    $skippedCount++;
                    continue;
                }

                // 3. Filter: Sequential Billing - last_month_billing & last_year_billing wajib tepat bulan sebelumnya
                // Contoh: untuk invoice bulan 11 tahun 2026, last_month_billing harus 10 dan last_year_billing harus 2026
                $custLastMonth = (int) ($p->last_month_billing ?? 0);
                $custLastYear = (int) ($p->last_year_billing ?? 0);

                if ($custLastYear <= 1970 || $custLastMonth <= 0) {
                    $hasPriorBilling = DB::table('trx_billing_layanan')
                        ->where('nomor_internet', $p->nomor_internet)
                        ->where('hide', 0)
                        ->exists();
                    if (!$hasPriorBilling) {
                        $custLastMonth = $prevMonthInt;
                        $custLastYear = $prevYearInt;
                    }
                }

                if ($custLastMonth !== $prevMonthInt || $custLastYear !== $prevYearInt) {
                    $skippedCount++;
                    continue;
                }

                $kodeBilling = "INV/{$p->nomor_internet}/{$bulan}/{$tahun}";

                // Cek apakah invoice periode ini sudah pernah digenerate
                $exists = DB::table('trx_billing_layanan')
                    ->where('kode_billing_layanan', $kodeBilling)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

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

                // Update riwayat last_month_billing dan last_year_billing di trx_batchjob_register
                if (Schema::hasTable('trx_batchjob_register')) {
                    DB::table('trx_batchjob_register')
                        ->where('nomor_internet', $p->nomor_internet)
                        ->update([
                            'last_month_billing' => $bulan,
                            'last_year_billing' => $tahun,
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

    /**
     * Generate & Publish First Monthly Invoice untuk Pelanggan Baru yang Selesai Aktivasi.
     * Jika aktif di pertengahan bulan, otomatis menerapkan prorate di invoice perdana tersebut.
     * Riwayat last_month_billing & last_year_billing disesuaikan dengan periode aktif.
     */
    public static function generateFirstActivationInvoice(string $nomorInternet, $activationDate = null, string $userCreator = 'System Aktivasi'): array
    {
        if (!Schema::hasTable('trx_billing_layanan')) {
            return ['success' => false, 'message' => 'Tabel trx_billing_layanan tidak ditemukan.'];
        }

        // 1. Validasi: Hanya untuk pelanggan baru (belum memiliki riwayat invoice bulanan)
        $hasPriorBilling = DB::table('trx_billing_layanan')
            ->where('nomor_internet', $nomorInternet)
            ->where('hide', '!=', '1')
            ->exists();

        if ($hasPriorBilling) {
            return [
                'success' => true,
                'message' => 'Pelanggan sudah memiliki riwayat invoice sebelumnya (bukan pelanggan baru).',
                'invoice' => null,
            ];
        }

        // 2. Tentukan tanggal & periode aktivasi
        if ($activationDate instanceof Carbon) {
            $actDate = $activationDate->copy()->setTimezone('Asia/Jakarta');
        } elseif (is_string($activationDate) && !empty($activationDate)) {
            try {
                $actDate = Carbon::parse($activationDate, 'Asia/Jakarta');
            } catch (\Throwable $th) {
                $actDate = Carbon::now('Asia/Jakarta');
            }
        } else {
            $actDate = Carbon::now('Asia/Jakarta');
        }

        $bulan = $actDate->format('m');
        $tahun = $actDate->format('Y');
        $currentDay = (int) $actDate->format('d');
        $totalDays = (int) $actDate->daysInMonth;
        $namaBulanShort = $actDate->format('M');
        $periodeTagihan = "{$namaBulanShort} {$tahun}";
        $kodeBilling = "INV/{$nomorInternet}/{$bulan}/{$tahun}";

        // 3. Ambil data pelanggan dan paket bandwith
        $custTable = Schema::hasTable('trx_batchjob_register') ? 'trx_batchjob_register' : 'view_batchjob';
        $customer = DB::table("{$custTable} as r")
            ->leftJoin('m_bandwith as bw', 'r.kode_bandwith', '=', 'bw.kode_bandwith')
            ->leftJoin('m_bandwith_kategori as bk', 'bw.kode_kategori_bandwith', '=', 'bk.kode_kategori_bandwith')
            ->where('r.nomor_internet', $nomorInternet)
            ->select(
                'r.nomor_internet',
                'r.nama_pelanggan',
                'r.kode_bandwith',
                'r.potongan',
                'r.potongan_note',
                'r.ppn',
                'r.ppn_nom',
                'bw.nama_bandwith',
                'bw.nominal_bandwith',
                'bw.harga_bandwith',
                'bw.kode_kategori_bandwith',
                'bk.nama_kategori_bandwith'
            )
            ->first();

        if (!$customer) {
            return ['success' => false, 'message' => "Pelanggan {$nomorInternet} tidak ditemukan."];
        }

        // Abaikan jika paket Broadband Free
        $kodeKategori = strtoupper(trim($customer->kode_kategori_bandwith ?? ''));
        $namaKategori = strtoupper(trim($customer->nama_kategori_bandwith ?? ''));
        if ($kodeKategori === 'BF' || str_contains($namaKategori, 'BROADBAND FREE')) {
            if (Schema::hasTable('trx_batchjob_register')) {
                DB::table('trx_batchjob_register')
                    ->where('nomor_internet', $nomorInternet)
                    ->update([
                        'last_month_billing' => $bulan,
                        'last_year_billing' => $tahun,
                    ]);
            }
            return ['success' => true, 'message' => 'Paket Broadband Free, invoice Rp 0 dilewati.', 'invoice' => null];
        }

        $hargaNormal = (float) ($customer->harga_bandwith ?? 0);
        if ($hargaNormal <= 0) {
            $hargaNormal = (float) ($customer->nominal_bandwith ?? 0);
        }

        // 4. Hitung Prorate jika aktif di pertengahan bulan (tanggal > 1)
        $isProrate = false;
        $activeDays = $totalDays;
        if ($currentDay > 1) {
            $isProrate = true;
            $activeDays = max(1, $totalDays - $currentDay + 1);
            $totalLayanan = round(($activeDays / $totalDays) * $hargaNormal);
            $potongan = max(0, $hargaNormal - $totalLayanan);
            $descPotongan = "Prorate Aktivasi: Aktif tgl {$currentDay}-{$totalDays} ({$activeDays}/{$totalDays} hari)";
        } else {
            $customerDiscount = (float) ($customer->potongan ?? 0);
            $totalLayanan = max(0, $hargaNormal - $customerDiscount);
            $potongan = $customerDiscount;
            $descPotongan = $customer->potongan_note ?? '-';
        }

        $nowStr = Carbon::now('Asia/Jakarta')->toDateTimeString();

        // 5. Coba generate Snap Token Midtrans bila service tersedia
        $paymentPost = '-';
        $paymentRespondPost = null;
        $expiry = null;
        if (class_exists(\App\Services\MidtransService::class)) {
            try {
                $contactInfo = DB::table('m_pelanggan')->where('nik_penduduk', function ($q) use ($nomorInternet) {
                    $q->select('nik_penduduk')->from('trx_batchjob_register')->where('nomor_internet', $nomorInternet)->limit(1);
                })->first();

                $midtrans = app(\App\Services\MidtransService::class)->generateSnapLink($kodeBilling, $totalLayanan, [
                    'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                    'email' => $contactInfo->email ?? 'billing@ims-router.net',
                    'nomor_hp' => $contactInfo->nomor_hp ?? '08123456789',
                ]);
                if (!empty($midtrans['payment_respond_post'])) {
                    $paymentPost = $midtrans['payment_post'] ?? '-';
                    $paymentRespondPost = $midtrans['payment_respond_post'];
                    $expiry = $midtrans['expiry'] ?? null;
                }
            } catch (\Throwable $th) {}
        }

        // 6. Simpan Invoice Langsung Publish (Status 13)
        DB::beginTransaction();
        try {
            DB::table('trx_billing_layanan')->insert([
                'kode_billing_layanan' => $kodeBilling,
                'nomor_internet' => $nomorInternet,
                'kode_bandwith' => $customer->kode_bandwith,
                'nominal_bandwith' => $customer->nominal_bandwith ?? '0',
                'bulan_tagihan' => $bulan,
                'tahun_tagihan' => $tahun,
                'periode_tagihan' => $periodeTagihan,
                'potongan' => (string) $potongan,
                'desc_potongan' => $descPotongan,
                'ppn' => $customer->ppn_nom ?? '0.11',
                'tax' => $customer->ppn ?? '2',
                'voucher' => '-',
                'total_layanan' => (string) $totalLayanan,
                'notif_mail' => '0',
                'notif_wa' => '0',
                'status_bill_lay' => '13', // Langsung Publish (Terbit Tagihan)
                'denda' => null,
                'invoice_file' => null,
                'payment_type' => '1',
                'payment_post' => $paymentPost,
                'payment_respond_post' => $paymentRespondPost,
                'payment_publish' => $nowStr,
                'expiry' => $expiry,
                'note_adjusment' => $isProrate ? "Prorate aktivasi pelanggan baru ({$activeDays}/{$totalDays} hari)" : "Invoice aktivasi pelanggan baru",
                'date_create' => $nowStr,
                'user_create' => $userCreator,
                'date_update' => $nowStr,
                'user_update' => $userCreator,
                'hide' => '0',
                'islock' => null,
            ]);

            if (Schema::hasTable('trx_billing_layanan_detail')) {
                $komponenNama = 'LAYANAN ' . ($customer->nama_kategori_bandwith ?? ($customer->nama_bandwith ?? 'INTERNET')) . ' ' . ($customer->nominal_bandwith ?? '') . ' Mbps';
                if ($isProrate) {
                    $komponenNama .= " (Prorate {$activeDays}/{$totalDays} Hari)";
                }
                DB::table('trx_billing_layanan_detail')->insert([
                    'kode_billing_lay_detail' => "D-{$nomorInternet}-{$bulan}/{$tahun}-T11",
                    'kode_billing_layanan' => $kodeBilling,
                    'kode_item' => 'T11',
                    'komponen' => $komponenNama,
                    'qty' => 1,
                    'biaya' => (string) $totalLayanan,
                    'date_create' => $nowStr,
                    'user_create' => $userCreator,
                    'hide' => null,
                ]);
            }

            if (Schema::hasTable('trx_billing_layanan_log')) {
                DB::table('trx_billing_layanan_log')->insert([
                    'kode_billing_lay_log' => "LOG-{$nomorInternet}-{$bulan}/{$tahun}-" . time(),
                    'kode_billing_layanan' => $kodeBilling,
                    'status_bill_lay' => '13',
                    'note_billing_lay' => "Invoice perdana otomatis diterbitkan (Published) saat aktivasi oleh {$userCreator}" . ($isProrate ? " [Prorate Rp " . number_format($totalLayanan, 0, ',', '.') . "]" : ""),
                    'date_create' => $nowStr,
                    'user_create' => $userCreator,
                    'hide' => '0',
                ]);
            }

            // 7. Update last_month_billing, last_year_billing, dan prorate di trx_batchjob_register
            if (Schema::hasTable('trx_batchjob_register')) {
                $updateCustomer = [
                    'last_month_billing' => $bulan,
                    'last_year_billing' => $tahun,
                ];
                if (Schema::hasColumn('trx_batchjob_register', 'prorate')) {
                    $updateCustomer['prorate'] = $isProrate ? '1' : '0';
                }
                DB::table('trx_batchjob_register')
                    ->where('nomor_internet', $nomorInternet)
                    ->update($updateCustomer);
            }

            DB::commit();

            Log::info("First Invoice Generated & Published for {$nomorInternet}: {$kodeBilling} (Prorate: " . ($isProrate ? "Yes, Rp {$totalLayanan}" : 'No') . ") by {$userCreator}");

            return [
                'success' => true,
                'message' => "Invoice perdana {$kodeBilling} berhasil diterbitkan (Published)" . ($isProrate ? " dengan Prorate Rp " . number_format($totalLayanan, 0, ',', '.') . " ({$activeDays}/{$totalDays} hari)" : ""),
                'invoice' => $kodeBilling,
                'nominal' => $totalLayanan,
                'is_prorate' => $isProrate,
                'active_days' => $activeDays,
                'total_days' => $totalDays,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to generate first activation invoice for {$nomorInternet}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal generate invoice aktivasi: ' . $e->getMessage(),
            ];
        }
    }
}
