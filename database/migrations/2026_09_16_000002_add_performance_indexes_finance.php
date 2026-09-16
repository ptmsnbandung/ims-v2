<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. trx_billing_layanan indexes
        if (Schema::hasTable('trx_billing_layanan')) {
            Schema::table('trx_billing_layanan', function (Blueprint $table) {
                // Check and add indexes safely
                $this->addIndexSafely('trx_billing_layanan', ['bulan_tagihan', 'tahun_tagihan'], 'idx_tbl_periode');
                $this->addIndexSafely('trx_billing_layanan', ['status_bill_lay'], 'idx_tbl_status');
                $this->addIndexSafely('trx_billing_layanan', ['nomor_internet'], 'idx_tbl_nomor_internet');
                $this->addIndexSafely('trx_billing_layanan', ['payment_type'], 'idx_tbl_payment_type');
            });
        }

        // 2. trx_billing_registrasi indexes
        if (Schema::hasTable('trx_billing_registrasi')) {
            Schema::table('trx_billing_registrasi', function (Blueprint $table) {
                $this->addIndexSafely('trx_billing_registrasi', ['status_bill_reg'], 'idx_tbr_status');
                $this->addIndexSafely('trx_billing_registrasi', ['nomor_internet'], 'idx_tbr_nomor_internet');
                $this->addIndexSafely('trx_billing_registrasi', ['payment_type'], 'idx_tbr_payment_type');
            });
        }

        // 3. trx_ubah_layanan indexes
        if (Schema::hasTable('trx_ubah_layanan')) {
            Schema::table('trx_ubah_layanan', function (Blueprint $table) {
                $this->addIndexSafely('trx_ubah_layanan', ['status_ubah_layanan'], 'idx_tul_status');
                $this->addIndexSafely('trx_ubah_layanan', ['nomor_internet'], 'idx_tul_nomor_internet');
            });
        }

        // 4. trx_suspend indexes
        if (Schema::hasTable('trx_suspend')) {
            Schema::table('trx_suspend', function (Blueprint $table) {
                $this->addIndexSafely('trx_suspend', ['status_suspend'], 'idx_ts_status');
                $this->addIndexSafely('trx_suspend', ['nomor_internet'], 'idx_ts_nomor_internet');
            });
        }

        // 5. trx_terminasi indexes
        if (Schema::hasTable('trx_terminasi')) {
            Schema::table('trx_terminasi', function (Blueprint $table) {
                $this->addIndexSafely('trx_terminasi', ['status_terminasi'], 'idx_tt_status');
                $this->addIndexSafely('trx_terminasi', ['nomor_internet'], 'idx_tt_nomor_internet');
            });
        }
    }

    /**
     * Helper to safely add indexes without throwing errors if they already exist
     */
    protected function addIndexSafely(string $table, array $columns, string $indexName): void
    {
        try {
            // Check if all columns exist
            foreach ($columns as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    return;
                }
            }

            // In MySQL, check if index exists
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                if (!empty($exists)) {
                    return;
                }
            }

            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe reverse
    }
};
