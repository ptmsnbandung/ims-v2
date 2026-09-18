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
        if (Schema::hasTable('trx_billing_layanan') && Schema::hasColumn('trx_billing_layanan', 'payment_type')) {
            try {
                DB::statement("ALTER TABLE `trx_billing_layanan` MODIFY COLUMN `payment_type` VARCHAR(2) DEFAULT '1'");
            } catch (\Throwable $e) {
                // Ignore if DB dialect differs
            }
        }

        if (Schema::hasTable('trx_billing_registrasi') && Schema::hasColumn('trx_billing_registrasi', 'payment_type')) {
            try {
                DB::statement("ALTER TABLE `trx_billing_registrasi` MODIFY COLUMN `payment_type` VARCHAR(2) DEFAULT '1'");
            } catch (\Throwable $e) {
                // Ignore if DB dialect differs
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('trx_billing_layanan') && Schema::hasColumn('trx_billing_layanan', 'payment_type')) {
            try {
                DB::statement("ALTER TABLE `trx_billing_layanan` MODIFY COLUMN `payment_type` VARCHAR(2) DEFAULT NULL");
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        if (Schema::hasTable('trx_billing_registrasi') && Schema::hasColumn('trx_billing_registrasi', 'payment_type')) {
            try {
                DB::statement("ALTER TABLE `trx_billing_registrasi` MODIFY COLUMN `payment_type` VARCHAR(2) DEFAULT NULL");
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
