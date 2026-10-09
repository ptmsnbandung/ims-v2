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
        if (!Schema::hasTable('payment_confirmations')) {
            Schema::create('payment_confirmations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('customer_id')->index();
                $table->string('kode_billing_layanan')->index();
                $table->string('customer_name')->nullable();
                $table->string('destination_bank')->nullable();
                $table->string('proof_file');
                $table->text('notes')->nullable();
                $table->string('status')->default('pending');
                $table->text('admin_notes')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_confirmations');
    }
};
