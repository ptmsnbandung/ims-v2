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
        $tables = [
            'trx_batchjob_register' => ['user_create', 'user_update'],
            'm_pelanggan'           => ['user_create', 'user_update'],
            'trx_tiket_gangguan'    => ['user_create', 'user_update'],
            'trx_suspend'           => ['user_create', 'user_update'],
            'trx_terminasi'         => ['user_create', 'user_update'],
            'trx_batchjob_register_log' => ['user_create'],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR(100) NULL DEFAULT NULL");
                    } catch (\Throwable $e) {
                        // Abaikan jika sudah diubah atau error privilege
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
        // No down migration needed
    }
};
