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
        // Pastikan m_bandwith_kategori memiliki collation utf8mb4_general_ci jika trx_batchjob_register utf8mb4_general_ci
        try {
            DB::statement("ALTER TABLE m_bandwith_kategori CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        } catch (\Throwable $e) {
            // Ignore if foreign key or not needed
        }

        $sql = "CREATE OR REPLACE VIEW view_batchjob AS
SELECT 
    r.nomor_internet,
    r.nik_penduduk,
    COALESCE(NULLIF(r.nama_pelanggan, ''), p.nama_penduduk, 'Tanpa Nama') AS nama_pelanggan,
    r.rt_pasang,
    r.rw_pasang,
    r.nomor_bangunan,
    r.alamat_pasang,
    r.kode_wilayah_kelurahan_pasang,
    r.jenis_bangunan,
    r.lon_lat,
    r.loc_maps,
    r.note_request,
    COALESCE(r.pppoe_username, p.pppoe_username) AS pppoe_username,
    COALESCE(r.pppoe_password, p.pppoe_password) AS pppoe_password,
    r.kode_bandwith,
    r.kode_pop,
    r.ont_us,
    r.ont_ps,
    r.status_reg,
    r.media_akses,
    r.ppn,
    r.ppn_nom,
    r.potongan,
    r.potongan_note,
    r.last_month_billing,
    r.last_year_billing,
    r.periode_billing,
    r.jns_notif,
    r.is_termin,
    r.is_suspend,
    r.count_suspend,
    r.is_denda,
    r.islock,
    r.prorate,
    r.date_create,
    r.user_create,
    r.date_update,
    r.user_update,
    r.hide,
    r.mitra,
    r.group_layanan,
    r.nama_sales,
    r.olt,
    r.router_id,
    r.index_olt,
    r.scan_dokumen_survey,
    r.scan_dokumen_instalasi,
    r.scan_dokumen_aktivasi,
    r.is_login,
    p.nama_penduduk,
    p.email,
    p.nomor_hp,
    p.nomor_hp_2,
    p.alamat_ktp AS alamat_p,
    p.kode_wilayah_kelurahan_ktp,
    b.nama_bandwith,
    b.kode_kategori_bandwith,
    b.nominal_bandwith,
    b.harga_bandwith,
    k.nama_kategori_bandwith,
    k.alias_nama_kategori
FROM trx_batchjob_register r
LEFT JOIN m_pelanggan p ON r.nik_penduduk = p.nik_penduduk
LEFT JOIN m_bandwith b ON r.kode_bandwith = b.kode_bandwith
LEFT JOIN m_bandwith_kategori k ON b.kode_kategori_bandwith = k.kode_kategori_bandwith";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_batchjob");
    }
};
