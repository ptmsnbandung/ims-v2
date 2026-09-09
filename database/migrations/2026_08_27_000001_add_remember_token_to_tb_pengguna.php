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
        if (Schema::hasTable('tb_pengguna') && !Schema::hasColumn('tb_pengguna', 'remember_token')) {
            Schema::table('tb_pengguna', function (Blueprint $table) {
                $table->rememberToken()->after('user_update');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tb_pengguna') && Schema::hasColumn('tb_pengguna', 'remember_token')) {
            Schema::table('tb_pengguna', function (Blueprint $table) {
                $table->dropRememberToken();
            });
        }
    }
};
