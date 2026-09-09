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
        if (Schema::hasTable('m_odp')) {
            Schema::table('m_odp', function (Blueprint $table) {
                if (!Schema::hasColumn('m_odp', 'latitude')) {
                    $table->decimal('latitude', 11, 8)->nullable()->after('capacity_odp');
                }
                if (!Schema::hasColumn('m_odp', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                }
                if (!Schema::hasColumn('m_odp', 'used_ports')) {
                    $table->integer('used_ports')->default(0)->after('longitude');
                }
                if (!Schema::hasColumn('m_odp', 'status')) {
                    $table->string('status', 20)->default('active')->after('used_ports');
                }
            });

            // Isi sample koordinat awal untuk ODP yang masih bernilai null
            $initialCoordinates = [
                'ODP1' => ['lat' => -6.93698800, 'lng' => 107.59045120, 'used' => 4],
                'ODP2' => ['lat' => -6.93812000, 'lng' => 107.59150000, 'used' => 6],
                'ODP3' => ['lat' => -6.93550000, 'lng' => 107.58920000, 'used' => 2],
                'ODP4' => ['lat' => -6.93920000, 'lng' => 107.59310000, 'used' => 16], // Full
                'ODP5' => ['lat' => -6.93410000, 'lng' => 107.58800000, 'used' => 1],
            ];

            foreach ($initialCoordinates as $kode => $geo) {
                DB::table('m_odp')->where('kode_odp', $kode)->whereNull('latitude')->update([
                    'latitude' => $geo['lat'],
                    'longitude' => $geo['lng'],
                    'used_ports' => $geo['used'],
                ]);
            }

            // Jika masih ada ODP lain yang belum ada koordinat, berikan koordinat tersebar di Bandung Raya
            $odpsWithoutCoords = DB::table('m_odp')->whereNull('latitude')->get();
            $baseLat = -6.936000;
            $baseLng = 107.590000;
            $i = 0;
            foreach ($odpsWithoutCoords as $odp) {
                $offsetLat = ($i % 5) * 0.0022 - 0.0044;
                $offsetLng = (floor($i / 5) % 5) * 0.0025 - 0.0050;
                DB::table('m_odp')->where('kode_odp', $odp->kode_odp)->update([
                    'latitude' => $baseLat + $offsetLat,
                    'longitude' => $baseLng + $offsetLng,
                    'used_ports' => rand(1, 12),
                ]);
                $i++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('m_odp')) {
            Schema::table('m_odp', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('m_odp', 'latitude')) $columnsToDrop[] = 'latitude';
                if (Schema::hasColumn('m_odp', 'longitude')) $columnsToDrop[] = 'longitude';
                if (Schema::hasColumn('m_odp', 'used_ports')) $columnsToDrop[] = 'used_ports';
                if (Schema::hasColumn('m_odp', 'status')) $columnsToDrop[] = 'status';
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
