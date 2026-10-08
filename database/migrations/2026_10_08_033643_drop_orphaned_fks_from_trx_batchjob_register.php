<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $invalidFks = [
            'fk_trx_batchjob_register_m_status_registrasi_1',
            'fk_trx_batchjob_register_m_pop_1',
            'fk_trx_batchjob_register_m_status_hide_1',
        ];

        foreach ($invalidFks as $fk) {
            $exists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trx_batchjob_register' AND CONSTRAINT_NAME = ?", [$fk]);
            if (!empty($exists)) {
                try {
                    DB::statement("ALTER TABLE `trx_batchjob_register` DROP FOREIGN KEY `{$fk}`");
                } catch (\Throwable $e) {
                    // Ignore if already dropped
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
