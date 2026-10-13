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
        if (!Schema::hasTable('noc_intentional_downtimes')) {
            Schema::create('noc_intentional_downtimes', function (Blueprint $table) {
                $table->id();
                $table->string('device_key', 190)->index(); // e.g., 'onu_gpon-onu_1/1/7:20', 'olt_O1', 'pon_O1_1/1/7', 'tiket_11120260917348'
                $table->string('device_type', 50)->default('onu'); // 'onu', 'pon', 'olt', 'tiket'
                $table->string('device_name', 255)->nullable();
                $table->string('olt_kode', 50)->nullable();
                $table->string('pon_index', 50)->nullable();
                $table->string('onu_id', 50)->nullable();
                $table->string('interface_name', 100)->nullable();
                $table->string('nomor_internet', 100)->nullable();
                $table->text('reason')->nullable();
                $table->string('marked_by', 100)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->dateTime('marked_at')->nullable();
                $table->dateTime('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noc_intentional_downtimes');
    }
};
