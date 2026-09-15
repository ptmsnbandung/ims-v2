<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('m_olt')) {
            Schema::table('m_olt', function (Blueprint $table) {
                if (!Schema::hasColumn('m_olt', 'hostname')) {
                    $table->string('hostname', 100)->nullable()->after('kode_olt');
                }
                if (!Schema::hasColumn('m_olt', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('name_olt');
                }
                if (!Schema::hasColumn('m_olt', 'brand')) {
                    $table->string('brand', 100)->nullable()->after('ip_address'); // Vendor
                }
                if (!Schema::hasColumn('m_olt', 'model')) {
                    $table->string('model', 100)->nullable()->after('brand'); // Model
                }
                if (!Schema::hasColumn('m_olt', 'kode_pop')) {
                    $table->string('kode_pop', 50)->nullable()->after('model');
                }
                if (!Schema::hasColumn('m_olt', 'protocol')) {
                    $table->string('protocol', 10)->default('telnet')->after('kode_pop');
                }
                if (!Schema::hasColumn('m_olt', 'port')) {
                    $table->integer('port')->default(23)->after('protocol');
                }
                if (!Schema::hasColumn('m_olt', 'username')) {
                    $table->string('username', 100)->nullable()->after('port');
                }
                if (!Schema::hasColumn('m_olt', 'password')) {
                    $table->text('password')->nullable()->after('username');
                }
                if (!Schema::hasColumn('m_olt', 'enable_password')) {
                    $table->text('enable_password')->nullable()->after('password');
                }
                if (!Schema::hasColumn('m_olt', 'snmp_port')) {
                    $table->integer('snmp_port')->default(161)->after('enable_password');
                }
                if (!Schema::hasColumn('m_olt', 'snmp_version')) {
                    $table->string('snmp_version', 10)->default('v2c')->after('snmp_port');
                }
                if (!Schema::hasColumn('m_olt', 'snmp_community')) {
                    $table->string('snmp_community', 50)->default('public')->after('snmp_version');
                }
                if (!Schema::hasColumn('m_olt', 'is_active')) {
                    $table->tinyInteger('is_active')->default(1)->after('snmp_community');
                }
                if (!Schema::hasColumn('m_olt', 'last_sync_at')) {
                    $table->timestamp('last_sync_at')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('m_olt', 'last_status')) {
                    $table->string('last_status', 20)->default('online')->after('last_sync_at');
                }
            });

            // Update sample data OLT jika sudah ada O1, O2, O3 agar langsung tampak cantik di tabel
            $sampleOlts = [
                'O1' => [
                    'hostname' => 'olt-msn-01',
                    'ip_address' => '10.10.10.1',
                    'brand' => 'ZTE',
                    'model' => 'C320',
                    'kode_pop' => 'pop73117',
                    'capacity_olt' => 8,
                    'name_olt' => 'OLT MSN',
                    'kode_olt' => 'OLT-MSN',
                ],
                'O2' => [
                    'hostname' => 'olt-bagong-01',
                    'ip_address' => '10.10.10.2',
                    'brand' => 'Huawei',
                    'model' => 'MA5608T',
                    'kode_pop' => 'pop9348',
                    'capacity_olt' => 8,
                    'name_olt' => 'OLT Bagong',
                    'kode_olt' => 'OLT-BAGONG',
                ],
                'O3' => [
                    'hostname' => 'olt-soreang-01',
                    'ip_address' => '10.10.10.3',
                    'brand' => 'HSGQ',
                    'model' => 'G008',
                    'kode_pop' => 'pop26685',
                    'capacity_olt' => 4,
                    'name_olt' => 'OLT Soreang',
                    'kode_olt' => 'OLT-SOREANG',
                ],
            ];

            foreach ($sampleOlts as $oldCode => $data) {
                DB::table('m_olt')->where('kode_olt', $oldCode)->update([
                    'hostname' => $data['hostname'],
                    'ip_address' => $data['ip_address'],
                    'brand' => $data['brand'],
                    'model' => $data['model'],
                    'kode_pop' => $data['kode_pop'],
                    'capacity_olt' => $data['capacity_olt'],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('m_olt')) {
            Schema::table('m_olt', function (Blueprint $table) {
                $cols = ['hostname', 'ip_address', 'brand', 'model', 'kode_pop', 'protocol', 'port', 'username', 'password', 'enable_password', 'snmp_port', 'snmp_version', 'snmp_community', 'is_active', 'last_sync_at', 'last_status'];
                $drop = [];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('m_olt', $c)) {
                        $drop[] = $c;
                    }
                }
                if (!empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
};
