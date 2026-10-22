<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('trx_suspend')) {
            Schema::create('trx_suspend', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->string('kode_suspend', 50)->primary();
                $table->string('nomor_internet', 50)->nullable()->index();
                $table->date('suspend_start')->nullable();
                $table->date('suspend_end')->nullable();
                $table->string('status_suspend', 10)->nullable()->default('11')->index();
                $table->text('desc_suspend')->nullable();
                $table->text('desc_suspend_cancel')->nullable();
                $table->dateTime('date_create')->nullable();
                $table->string('user_create', 50)->nullable();
                $table->dateTime('date_update')->nullable();
                $table->string('user_update', 50)->nullable();
                $table->string('hide', 1)->nullable()->default('0');

                $table->index(['status_suspend', 'nomor_internet'], 'idx_suspend_status_nomor');
            });
        }

        // Cek apakah view_suspend sudah ada di database aktif
        $viewExists = DB::select("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'view_suspend'");

        if (empty($viewExists)) {
            $viewSql = "CREATE OR REPLACE VIEW view_suspend AS
SELECT 
    s.kode_suspend,
    s.nomor_internet,
    s.suspend_start,
    s.suspend_end,
    s.status_suspend,
    s.desc_suspend,
    s.desc_suspend_cancel,
    s.date_create,
    s.user_create,
    s.date_update,
    s.user_update,
    s.hide,
    COALESCE(NULLIF(r.nama_pelanggan, ''), p.nama_penduduk, 'Pelanggan IMS') AS nama_pelanggan,
    COALESCE(r.alamat_pasang, p.alamat_ktp, '-') AS alamat_p,
    COALESCE(k.nama_kategori_bandwith, 'BROADBAND') AS nama_kategori_bandwith,
    COALESCE(b.nominal_bandwith, '-') AS nominal_bandwith
FROM trx_suspend s
LEFT JOIN trx_batchjob_register r ON s.nomor_internet = r.nomor_internet
LEFT JOIN m_pelanggan p ON r.nik_penduduk = p.nik_penduduk
LEFT JOIN m_bandwith b ON r.kode_bandwith = b.kode_bandwith
LEFT JOIN m_bandwith_kategori k ON b.kode_kategori_bandwith = k.kode_kategori_bandwith";

            DB::statement($viewSql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Jangan drop jika di production view sudah ada sebelumnya
    }
};
