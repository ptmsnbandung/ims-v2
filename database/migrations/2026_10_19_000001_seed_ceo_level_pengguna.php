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
        if (Schema::hasTable('tb_m_level_pengguna')) {
            DB::table('tb_m_level_pengguna')->updateOrInsert(
                ['kode_level' => 'lv_ceo'],
                [
                    'level' => 11,
                    'nama_level' => 'CEO',
                    'desc_level' => 'Chief Executive Officer (Executive Monitoring - Read Only)',
                    'date_create' => now(),
                    'user_create' => 'system',
                    'user_update' => 'system',
                    'date_update' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tb_m_level_pengguna')) {
            DB::table('tb_m_level_pengguna')->where('kode_level', 'lv_ceo')->delete();
        }
    }
};
