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
        // 1. Tabel TERPISAH: routers (MikroTik RouterOS Gateway)
        if (!Schema::hasTable('routers')) {
            Schema::create('routers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('Nama Router, misal: Router Core Kayuagung');
                $table->string('host')->comment('IP Address / Hostname MikroTik');
                $table->integer('port')->default(18735)->comment('Port API RouterOS');
                $table->string('username')->comment('User API MikroTik');
                $table->string('password')->comment('Password API MikroTik');
                $table->boolean('is_active')->default(true)->comment('1 = Aktif, 0 = Nonaktif');
                $table->string('kota')->nullable()->comment('Wilayah / Lokasi Router');
                $table->timestamps();
            });
        }

        // 2. Tabel activity_logs (Audit Trail Eksekusi MikroTik & OLT)
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->string('user_id', 100)->nullable()->comment('User Pelaksana');
                $table->string('customer_id', 100)->comment('Nomor Internet Pelanggan');
                $table->string('action', 255)->comment('activate / suspend / kick / reboot_onu');
                $table->string('old_status', 255)->nullable();
                $table->string('new_status', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('router_response', 255)->nullable();
                $table->boolean('router_success')->default(false);
                $table->timestamps();

                $table->index('user_id');
                $table->index('customer_id');
                $table->index('action');
                $table->index('created_at');
            });
        }

        // 3. Tabel req_suspend_selections (Multi-Select Checkbox Persistence)
        if (!Schema::hasTable('req_suspend_selections')) {
            Schema::create('req_suspend_selections', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_internet', 255)->unique();
                $table->boolean('is_selected')->default(false);
                $table->timestamps();

                $table->index('is_selected');
            });
        }

        // 4. Tabel req_terminasi (Workflow & Bukti Penarikan Perangkat)
        if (!Schema::hasTable('req_terminasi')) {
            Schema::create('req_terminasi', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_internet', 255);
                $table->string('status_terminasi', 255)->default('11');
                $table->date('tanggal_collecting')->nullable();
                $table->string('team_collecting', 255)->nullable();
                $table->string('jam_collecting', 255)->nullable();
                $table->text('keterangan_collecting')->nullable();
                $table->date('tanggal_berhasil_collect')->nullable();
                $table->string('jam_berhasil_collect', 255)->nullable();
                $table->string('foto_bukti_collecting', 255)->nullable();
                $table->text('keterangan_report')->nullable();
                $table->date('tanggal_schedule_baru')->nullable();
                $table->string('jam_schedule_baru', 255)->nullable();
                $table->string('team_collecting_baru', 255)->nullable();
                $table->text('alasan_reschedule')->nullable();
                $table->date('tanggal_terminasi')->nullable();
                $table->timestamps();

                $table->index('nomor_internet');
                $table->index('status_terminasi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('req_terminasi');
        Schema::dropIfExists('req_suspend_selections');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('routers');
    }
};
