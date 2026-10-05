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
        // 1. Tabel routers (MikroTik Routers)
        if (!Schema::hasTable('routers')) {
            Schema::create('routers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('host');
                $table->integer('port')->default(18735);
                $table->string('username');
                $table->string('password');
                $table->boolean('is_active')->default(true);
                $table->string('kota')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tabel customers (Router Provisioning Cache)
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('customer_id')->unique();
                $table->text('address');
                $table->string('kota')->nullable();
                $table->string('package');
                $table->enum('status', ['active', 'suspend', 'terminated'])->default('active');
                $table->boolean('is_selected')->default(false);
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('pppoe_username')->nullable()->unique();
                $table->string('pppoe_password')->nullable();
                $table->string('router_ip')->nullable();
                $table->string('mac_address')->nullable();
                $table->string('bandwidth_profile')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->boolean('is_synced')->default(false);
                $table->foreignId('router_id')->nullable()->constrained('routers')->onDelete('set null');
                $table->timestamps();

                $table->index('status');
                $table->index('customer_id');
                $table->index('pppoe_username');
            });
        }

        // 3. Tabel activity_logs (Audit Trail MikroTik & OLT)
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->string('user_id', 100)->nullable();
                $table->string('customer_id', 100);
                $table->string('action');
                $table->string('old_status')->nullable();
                $table->string('new_status')->nullable();
                $table->text('description')->nullable();
                $table->string('router_response')->nullable();
                $table->boolean('router_success')->default(false);
                $table->timestamps();

                $table->index('user_id');
                $table->index('customer_id');
                $table->index('action');
                $table->index('created_at');
            });
        }

        // 4. Tabel req_suspend_selections (Multi-Select Checkbox Persistence)
        if (!Schema::hasTable('req_suspend_selections')) {
            Schema::create('req_suspend_selections', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_internet')->unique();
                $table->boolean('is_selected')->default(false);
                $table->timestamps();

                $table->index('is_selected');
            });
        }

        // 5. Tabel req_terminasi (Workflow & Bukti Penarikan Terminasi)
        if (!Schema::hasTable('req_terminasi')) {
            Schema::create('req_terminasi', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_internet');
                $table->string('status_terminasi')->default('11');
                $table->date('tanggal_collecting')->nullable();
                $table->string('team_collecting')->nullable();
                $table->string('jam_collecting')->nullable();
                $table->text('keterangan_collecting')->nullable();
                $table->date('tanggal_berhasil_collect')->nullable();
                $table->string('jam_berhasil_collect')->nullable();
                $table->string('foto_bukti_collecting')->nullable();
                $table->text('keterangan_report')->nullable();
                $table->date('tanggal_schedule_baru')->nullable();
                $table->string('jam_schedule_baru')->nullable();
                $table->string('team_collecting_baru')->nullable();
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
        Schema::dropIfExists('customers');
        Schema::dropIfExists('routers');
    }
};
