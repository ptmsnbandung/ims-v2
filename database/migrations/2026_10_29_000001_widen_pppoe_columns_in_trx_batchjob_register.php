<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perlebar kolom kredensial dan catatan di trx_batchjob_register
        if (Schema::hasTable('trx_batchjob_register')) {
            $columnsToWiden = [
                'ont_us' => 'VARCHAR(100) NULL DEFAULT NULL',
                'ont_ps' => 'VARCHAR(100) NULL DEFAULT NULL',
                'note_request' => 'VARCHAR(255) NULL DEFAULT NULL',
                'pppoe_username' => 'VARCHAR(100) NULL DEFAULT NULL',
                'pppoe_password' => 'VARCHAR(100) NULL DEFAULT NULL',
            ];

            foreach ($columnsToWiden as $col => $definition) {
                if (Schema::hasColumn('trx_batchjob_register', $col)) {
                    try {
                        DB::statement("ALTER TABLE `trx_batchjob_register` MODIFY COLUMN `{$col}` {$definition}");
                    } catch (\Throwable $e) {
                        // Abaikan jika sudah sesuai atau permission terbatas
                    }
                }
            }
        }

        // 2. Pastikan kolom default aman di trx_instalasi (jika tabel ada)
        if (Schema::hasTable('trx_instalasi')) {
            $instalasiColumns = [
                'aktivasi_note' => 'TEXT NULL DEFAULT NULL',
                'aktivasi_note_finish' => 'TEXT NULL DEFAULT NULL',
                'kode_instalasi' => 'VARCHAR(100) NULL DEFAULT NULL',
                'hide' => 'VARCHAR(10) NULL DEFAULT "0"',
                'user_create' => 'VARCHAR(100) NULL DEFAULT NULL',
                'user_update' => 'VARCHAR(100) NULL DEFAULT NULL',
            ];

            foreach ($instalasiColumns as $col => $definition) {
                if (Schema::hasColumn('trx_instalasi', $col)) {
                    try {
                        DB::statement("ALTER TABLE `trx_instalasi` MODIFY COLUMN `{$col}` {$definition}");
                    } catch (\Throwable $e) {
                        // Abaikan jika sudah sesuai
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive migration
    }
};
