<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tb_broadcast_wa_log')) {
            DB::statement('ALTER TABLE tb_broadcast_wa_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        }
        if (Schema::hasTable('tb_broadcast_wa_template')) {
            DB::statement('ALTER TABLE tb_broadcast_wa_template CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tb_broadcast_wa_log')) {
            DB::statement('ALTER TABLE tb_broadcast_wa_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        if (Schema::hasTable('tb_broadcast_wa_template')) {
            DB::statement('ALTER TABLE tb_broadcast_wa_template CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }
};
