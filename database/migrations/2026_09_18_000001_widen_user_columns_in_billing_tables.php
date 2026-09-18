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
        try {
            if (Schema::hasTable('trx_billing_layanan')) {
                DB::statement("ALTER TABLE trx_billing_layanan MODIFY COLUMN user_create VARCHAR(100) NULL, MODIFY COLUMN user_update VARCHAR(100) NULL");
            }
        } catch (\Exception $e) {
            // Ignore if already modified
        }

        try {
            if (Schema::hasTable('trx_billing_registrasi')) {
                DB::statement("ALTER TABLE trx_billing_registrasi MODIFY COLUMN user_create VARCHAR(100) NULL, MODIFY COLUMN user_update VARCHAR(100) NULL");
            }
        } catch (\Exception $e) {
            // Ignore if already modified
        }

        try {
            if (Schema::hasTable('trx_billing_layanan_log')) {
                DB::statement("ALTER TABLE trx_billing_layanan_log MODIFY COLUMN user_create VARCHAR(100) NULL");
            }
        } catch (\Exception $e) {
            // Ignore if already modified
        }

        try {
            if (Schema::hasTable('trx_billing_registrasi_log')) {
                DB::statement("ALTER TABLE trx_billing_registrasi_log MODIFY COLUMN user_create VARCHAR(100) NULL");
            }
        } catch (\Exception $e) {
            // Ignore if already modified
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed
    }
};
