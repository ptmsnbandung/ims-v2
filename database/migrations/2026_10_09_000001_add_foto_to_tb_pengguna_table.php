<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tb_pengguna') && !Schema::hasColumn('tb_pengguna', 'foto')) {
            Schema::table('tb_pengguna', function (Blueprint $table) {
                $table->string('foto', 255)->nullable()->after('username');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tb_pengguna') && Schema::hasColumn('tb_pengguna', 'foto')) {
            Schema::table('tb_pengguna', function (Blueprint $table) {
                $table->dropColumn('foto');
            });
        }
    }
};
