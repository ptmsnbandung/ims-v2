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
        if (!Schema::hasTable('reboot_onu_queues')) {
            Schema::create('reboot_onu_queues', function (Blueprint $table) {
                $table->id();
                $table->string('batch_id', 64)->index();
                $table->string('nomor_internet', 64)->index();
                $table->string('nama_pelanggan')->nullable();
                $table->string('index_olt', 64)->nullable();
                $table->string('kode_olt', 64)->nullable();
                $table->string('action_type', 32)->default('suspend_reboot');
                $table->string('status', 32)->default('pending')->index(); // pending, processing, success, failed
                $table->text('response_message')->nullable();
                $table->string('operator', 100)->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reboot_onu_queues');
    }
};
