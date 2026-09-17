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
        if (!Schema::hasTable('trx_tiket_gangguan')) {
            Schema::create('trx_tiket_gangguan', function (Blueprint $table) {
                $table->string('tiket', 100)->primary();
                $table->string('kode_trx_tiket', 50)->nullable()->index();
                $table->string('nomor_internet', 50)->index();
                $table->string('nama_pelanggan', 150)->nullable();
                $table->string('kat_tiket', 20)->default('11')->comment('11: Gangguan Layanan, 12: Ubah Password, 13: Gangguan Fisik/Kabel, 14: Lain-lain');
                $table->string('status', 20)->default('11')->comment('11: Request, 12: On Schedule/Diproses, 13: Success/Selesai, 14: Canceled/Dibatalkan');
                $table->text('keluhan')->nullable();
                $table->string('prioritas', 20)->default('Normal');
                $table->string('team_teknisi', 100)->nullable();
                $table->date('date_schedule')->nullable();
                $table->string('time_schedule', 50)->nullable();
                $table->text('solusi')->nullable();
                $table->dateTime('date_create')->nullable();
                $table->string('user_create', 100)->nullable();
                $table->dateTime('date_update')->nullable();
                $table->string('user_update', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down: do not drop if table was already existing from external schema
    }
};
