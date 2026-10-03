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
        if (!Schema::hasTable('trx_billing_request')) {
            Schema::create('trx_billing_request', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_internet', 50)->index();
                $table->string('nama_pelanggan', 255)->nullable();
                $table->string('bulan_tagihan', 10);
                $table->string('tahun_tagihan', 10);
                $table->string('periode_tagihan', 50)->nullable();
                $table->string('layanan', 100)->nullable();
                $table->decimal('nominal', 15, 2)->default(0);
                $table->text('catatan_pelanggan')->nullable();
                $table->enum('status_request', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->string('kode_billing_layanan', 100)->nullable()->index();
                $table->string('approved_by', 100)->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->string('rejected_by', 100)->nullable();
                $table->dateTime('rejected_at')->nullable();
                $table->text('rejection_note')->nullable();
                $table->timestamps();

                $table->index(['bulan_tagihan', 'tahun_tagihan']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_billing_request');
    }
};
