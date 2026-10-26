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

        // Cek jika view_batchjob sudah ada di database aktif, JANGAN timpa view asli
        try {
            $viewExists = DB::select("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'view_batchjob'");
            if (!empty($viewExists)) {
                return;
            }
        } catch (\Throwable $e) {
            // Lanjutkan jika information_schema tidak bisa diakses
        }

        $sql = "CREATE OR REPLACE VIEW `view_batchjob` AS 
SELECT 
  `br`.`nomor_internet` AS `nomor_internet`, 
  `br`.`status_reg` AS `status_reg`, 
  `sr`.`desc_registrasi` AS `desc_registrasi`, 
  `br`.`nik_penduduk` AS `nik_penduduk`, 
  `br`.`nama_pelanggan` AS `nama_pelanggan`, 
  `p`.`nama_penduduk` AS `nama_penduduk`, 
  `p`.`nomor_hp` AS `nomor_hp`, 
  `p`.`nomor_hp_2` AS `nomor_hp_2`, 
  `p`.`email` AS `email`, 
  `p`.`jenis_kelamin` AS `jenis_kelamin`, 
  `p`.`tanggal_lahir` AS `tanggal_lahir`, 
  `p`.`pic` AS `pic`, 
  `p`.`rt_ktp` AS `rt_ktp`, 
  `p`.`rw_ktp` AS `rw_ktp`, 
  `p`.`alamat_ktp` AS `alamat_ktp`, 
  concat(`p`.`alamat_ktp`,', RT',`p`.`rt_ktp`,'/RW',`p`.`rw_ktp`,', KEL. ',`p`.`nama_kelurahan`,', KEC. ',`p`.`nama_kecamatan`,', ',`p`.`nama_kota`,', ',`p`.`nama_provinsi`) AS `alamat_k`, 
  `p`.`kode_wilayah_kelurahan_ktp` AS `kode_wilayah_kelurahan_ktp`, 
  `p`.`nama_kelurahan` AS `nama_kelurahan`, 
  `p`.`nama_kecamatan` AS `nama_kecamatan`, 
  `p`.`nama_kota` AS `nama_kota`, 
  `p`.`nama_provinsi` AS `nama_provinsi`, 
  `b`.`kode_kategori_bandwith` AS `kode_kategori_bandwith`, 
  `b`.`nama_kategori_bandwith` AS `nama_kategori_bandwith`, 
  `b`.`alias_nama_kategori` AS `alias_nama_kategori`, 
  `b`.`biaya_reg` AS `biaya_reg`, 
  `b`.`kode_bandwith` AS `kode_bandwith`, 
  `b`.`nominal_bandwith` AS `nominal_bandwith`, 
  `b`.`harga_bandwith` AS `harga_bandwith`, 
  `br`.`jenis_bangunan` AS `jenis_bangunan`, 
  `br`.`rt_pasang` AS `rt_pasang`, 
  `br`.`rw_pasang` AS `rw_pasang`, 
  `br`.`alamat_pasang` AS `alamat_pasang`, 
  concat(`br`.`alamat_pasang`,' NO. ',`br`.`nomor_bangunan`,', RT',`br`.`rt_pasang`,'/RW',`br`.`rw_pasang`,', KEL. ',`w`.`nama_kelurahan`,', KEC. ',`w`.`nama_kecamatan`,', ',`w`.`nama_kota`,', ',`w`.`nama_provinsi`) AS `alamat_p`, 
  `br`.`kode_wilayah_kelurahan_pasang` AS `kode_wilayah_kelurahan_pasang`, 
  `br`.`loc_maps` AS `loc_maps`, 
  `br`.`nomor_bangunan` AS `nomor_bangunan`, 
  `br`.`lon_lat` AS `lon_lat`, 
  `w`.`nama_kelurahan` AS `nama_kelurahan_pasang`, 
  `w`.`nama_kecamatan` AS `nama_kecamatan_pasang`, 
  `w`.`nama_kota` AS `nama_kota_pasang`, 
  `w`.`kode_wilayah_kota` AS `kode_wilayah_kota_pasang`, 
  `w`.`nama_provinsi` AS `nama_provinsi_pasang`, 
  `br`.`note_request` AS `note_request`, 
  `br`.`ppn` AS `ppn`, 
  `br`.`ppn_nom` AS `ppn_nom`, 
  `br`.`potongan` AS `potongan`, 
  `br`.`potongan_note` AS `potongan_note`, 
  `br`.`last_month_billing` AS `last_month_billing`, 
  `br`.`last_year_billing` AS `last_year_billing`, 
  `br`.`periode_billing` AS `periode_billing`, 
  `br`.`jns_notif` AS `jns_notif`, 
  `br`.`is_denda` AS `is_denda`, 
  `br`.`is_suspend` AS `is_suspend`, 
  `br`.`count_suspend` AS `count_suspend`, 
  `br`.`is_termin` AS `is_termin`, 
  `i`.`kode_instalasi` AS `kode_instalasi`, 
  `i`.`verifikasi_date` AS `verifikasi_date`, 
  `i`.`verifikasi_note` AS `verifikasi_note`, 
  `i`.`survey_date_start` AS `survey_date_start`, 
  `i`.`survey_time` AS `survey_time`, 
  `i`.`survey_team` AS `survey_team`, 
  `i`.`survey_note` AS `survey_note`, 
  `i`.`survey_date_finish` AS `survey_date_finish`, 
  `i`.`survey_note_finish` AS `survey_note_finish`, 
  `i`.`doc_survey` AS `doc_survey`, 
  `i`.`instalasi_date_start` AS `instalasi_date_start`, 
  `i`.`instalasi_time` AS `instalasi_time`, 
  `i`.`instalasi_team` AS `instalasi_team`, 
  `i`.`instalasi_note` AS `instalasi_note`, 
  `i`.`instalasi_date_finish` AS `instalasi_date_finish`, 
  `i`.`instalasi_note_finish` AS `instalasi_note_finish`, 
  `i`.`doc_instalasi` AS `doc_instalasi`, 
  `i`.`aktivasi_date_start` AS `aktivasi_date_start`, 
  `i`.`aktivasi_time` AS `aktivasi_time`, 
  `i`.`aktivasi_team` AS `aktivasi_team`, 
  `i`.`aktivasi_note` AS `aktivasi_note`, 
  `i`.`aktivasi_date_finish` AS `aktivasi_date_finish`, 
  `i`.`aktivasi_note_finish` AS `aktivasi_note_finish`, 
  `i`.`doc_aktivasi` AS `doc_aktivasi`, 
  `i`.`doc_berlangganan` AS `doc_berlangganan`, 
  `i`.`foto_rumah` AS `foto_rumah`, 
  `i`.`foto_ktp` AS `foto_ktp`, 
  `i`.`foto_peta` AS `foto_peta`, 
  `br`.`kode_pop` AS `kode_pop`, 
  `po`.`nama_pop` AS `nama_pop`, 
  `po`.`desc_pop` AS `desc_pop`, 
  `br`.`ont_us` AS `ont_us`, 
  `br`.`ont_ps` AS `ont_ps`, 
  `br`.`media_akses` AS `media_akses`, 
  `br`.`index_olt` AS `index_olt`, 
  `br`.`group_layanan` AS `group_layanan`, 
  `br`.`nama_sales` AS `nama_sales`, 
  `br`.`islock` AS `islock`, 
  `br`.`prorate` AS `prorate`, 
  `br`.`hide` AS `hide`, 
  `br`.`user_create` AS `user_create`, 
  `br`.`date_create` AS `date_create`, 
  `br`.`date_update` AS `date_update`, 
  `br`.`user_update` AS `user_update`, 
  `h`.`desc_hide` AS `desc_hide` 
FROM (((((((`trx_batchjob_register` `br` 
  left join `m_status_registrasi` `sr` on((`br`.`status_reg` = `sr`.`status_reg`))) 
  left join `trx_instalasi` `i` on((`br`.`nomor_internet` = `i`.`nomor_internet`))) 
  left join `view_bandwith` `b` on((`br`.`kode_bandwith` = `b`.`kode_bandwith`))) 
  left join `m_wilayah` `w` on((`br`.`kode_wilayah_kelurahan_pasang` = `w`.`kode_wilayah_kelurahan`))) 
  left join `view_pelanggan` `p` on((`br`.`nik_penduduk` = `p`.`nik_penduduk`))) 
  left join `m_status_hide` `h` on((`br`.`hide` = `h`.`hide`))) 
  left join `m_pop` `po` on((`po`.`kode_pop` = `br`.`kode_pop`)))";

        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("view_batchjob creation note: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
