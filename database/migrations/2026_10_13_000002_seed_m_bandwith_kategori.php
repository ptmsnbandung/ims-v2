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
        if (Schema::hasTable('m_bandwith_kategori')) {
            $hasCreatedAt = Schema::hasColumn('m_bandwith_kategori', 'created_at');
            $hasUpdatedAt = Schema::hasColumn('m_bandwith_kategori', 'updated_at');

            $categories = [
                ['kode_kategori_bandwith' => 'KB09212', 'nama_kategori_bandwith' => 'UP TO NEW', 'alias_nama_kategori' => 'UP TO NEW', 'biaya_reg' => 100000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KB1682', 'nama_kategori_bandwith' => 'SMALL OFFICE HOME OFFICE', 'alias_nama_kategori' => 'UPTO SOHO', 'biaya_reg' => 500000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KB22285', 'nama_kategori_bandwith' => 'CORPORATE', 'alias_nama_kategori' => 'CORPORATE', 'biaya_reg' => 1500000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KB58163', 'nama_kategori_bandwith' => 'LASTMILE', 'alias_nama_kategori' => 'LASTMILE', 'biaya_reg' => 1500000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KB69771', 'nama_kategori_bandwith' => 'BROADBAND NEW', 'alias_nama_kategori' => 'UPTO BROADBAND', 'biaya_reg' => 300000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KB69779', 'nama_kategori_bandwith' => 'BROADBAND', 'alias_nama_kategori' => 'UPTO BROADBAND', 'biaya_reg' => 300000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KBBOD1', 'nama_kategori_bandwith' => 'BANDWITDH ON DEMAND', 'alias_nama_kategori' => 'BOD', 'biaya_reg' => 500000, 'disable' => 0],
                ['kode_kategori_bandwith' => 'KBFRE02', 'nama_kategori_bandwith' => 'BROADBAND FREE', 'alias_nama_kategori' => 'BF', 'biaya_reg' => 0, 'disable' => 0],
            ];

            foreach ($categories as $cat) {
                $payload = $cat;
                if ($hasCreatedAt) {
                    $payload['created_at'] = now();
                }
                if ($hasUpdatedAt) {
                    $payload['updated_at'] = now();
                }

                DB::table('m_bandwith_kategori')->updateOrInsert(
                    ['kode_kategori_bandwith' => $cat['kode_kategori_bandwith']],
                    $payload
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
