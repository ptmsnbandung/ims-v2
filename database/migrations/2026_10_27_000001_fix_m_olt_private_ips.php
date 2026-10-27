<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('m_olt')) {
            $publicHost = env('OLT_HOST', '103.161.206.214');
            
            // Perbaiki IP privat seeder (10.10.10.x) agar mengarah ke host publik OLT yang dapat diakses dari server cloud/hosting
            DB::table('m_olt')
                ->where('ip_address', 'like', '10.10.10.%')
                ->orWhere('ip_address', 'like', '192.168.%')
                ->update([
                    'ip_address' => $publicHost,
                ]);

            // Pastikan mapping port OLT sesuai NAT Forwarding publik
            DB::table('m_olt')->where('kode_olt', 'O1')->update(['port' => 45523]);
            DB::table('m_olt')->where('kode_olt', 'O2')->update(['port' => 45525]);
            DB::table('m_olt')->where('kode_olt', 'O3')->update(['port' => 45524]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed as restoring unreachable private IPs is not desirable
    }
};
