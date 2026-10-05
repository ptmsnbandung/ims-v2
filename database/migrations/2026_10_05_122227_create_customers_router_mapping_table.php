<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom router_id ke tabel `trx_batchjob_register` untuk mapping langsung ke master router MikroTik.
     */
    public function up(): void
    {
        // 1. Pastikan tabel routers ada
        if (!Schema::hasTable('routers')) {
            Schema::create('routers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('host');
                $table->integer('port')->default(18735);
                $table->string('username');
                $table->text('password');
                $table->boolean('is_active')->default(true);
                $table->string('kota', 100)->nullable();
                $table->timestamps();
            });
        }

        // 2. Tambahkan kolom router_id ke trx_batchjob_register
        if (Schema::hasTable('trx_batchjob_register')) {
            Schema::table('trx_batchjob_register', function (Blueprint $table) {
                if (!Schema::hasColumn('trx_batchjob_register', 'router_id')) {
                    $table->unsignedBigInteger('router_id')->nullable()->after('olt');
                    $table->foreign('router_id')->references('id')->on('routers')->onDelete('set null');
                    $table->index('router_id');
                }
            });
        }

        // 3. Drop tabel customers jika pernah terbuat karena tidak diperlukan
        Schema::dropIfExists('customers');
    }

    public function down(): void
    {
        if (Schema::hasTable('trx_batchjob_register')) {
            Schema::table('trx_batchjob_register', function (Blueprint $table) {
                if (Schema::hasColumn('trx_batchjob_register', 'router_id')) {
                    $table->dropForeign(['router_id']);
                    $table->dropColumn('router_id');
                }
            });
        }
    }
};
