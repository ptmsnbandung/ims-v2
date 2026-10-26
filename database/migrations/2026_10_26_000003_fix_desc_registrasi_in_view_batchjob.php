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
        $rCols = Schema::hasTable('trx_batchjob_register') ? Schema::getColumnListing('trx_batchjob_register') : [];
        $pCols = Schema::hasTable('m_pelanggan') ? Schema::getColumnListing('m_pelanggan') : [];
        $hasSr = Schema::hasTable('m_status_registrasi');

        $hasR = fn($col) => in_array($col, $rCols, true);
        $hasP = fn($col) => in_array($col, $pCols, true);

        // PPPoE Username & Password
        if ($hasR('pppoe_username') && $hasP('pppoe_username')) {
            $pppoeUserSql = "COALESCE(r.pppoe_username, p.pppoe_username) AS pppoe_username";
        } elseif ($hasR('pppoe_username')) {
            $pppoeUserSql = "r.pppoe_username AS pppoe_username";
        } elseif ($hasP('pppoe_username')) {
            $pppoeUserSql = "p.pppoe_username AS pppoe_username";
        } else {
            $pppoeUserSql = "NULL AS pppoe_username";
        }

        if ($hasR('pppoe_password') && $hasP('pppoe_password')) {
            $pppoePassSql = "COALESCE(r.pppoe_password, p.pppoe_password) AS pppoe_password";
        } elseif ($hasR('pppoe_password')) {
            $pppoePassSql = "r.pppoe_password AS pppoe_password";
        } elseif ($hasP('pppoe_password')) {
            $pppoePassSql = "p.pppoe_password AS pppoe_password";
        } else {
            $pppoePassSql = "NULL AS pppoe_password";
        }

        // Nama Pelanggan
        if ($hasR('nama_pelanggan')) {
            $namaPelangganSql = "COALESCE(NULLIF(r.nama_pelanggan, ''), p.nama_penduduk, 'Tanpa Nama') AS nama_pelanggan";
        } else {
            $namaPelangganSql = "COALESCE(p.nama_penduduk, 'Tanpa Nama') AS nama_pelanggan";
        }

        // Deskripsi Status Registrasi (Human readable name)
        $descRegistrasiSql = "COALESCE(" . ($hasSr ? "sr.desc_registrasi, " : "") . "CASE r.status_reg 
            WHEN '20' THEN 'Aktif'
            WHEN '21' THEN 'Suspend'
            WHEN '21.1' THEN 'Req. Suspend'
            WHEN '23' THEN 'Terminasi'
            WHEN '23.1' THEN 'Req. Terminasi'
            WHEN '11' THEN 'Menunggu Verifikasi'
            WHEN '11.1' THEN 'Belum Valid'
            WHEN '12' THEN 'Data Input'
            WHEN '13' THEN 'Jadwal Survey Terbit'
            WHEN '13.1' THEN 'Reschedule Survey'
            WHEN '14' THEN 'Tidak Tercover'
            WHEN '15' THEN 'Batal Pasang'
            WHEN '16' THEN 'Selesai Survey'
            WHEN '17' THEN 'Jadwal Instalasi Terbit'
            WHEN '17.1' THEN 'Reschedule Instalasi'
            WHEN '18' THEN 'Selesai Instalasi'
            WHEN '19' THEN 'Jadwal Aktivasi Terbit'
            WHEN '19.1' THEN 'Reschedule Aktivasi'
            WHEN '22' THEN 'Re-update'
            ELSE CONCAT('Status #', r.status_reg)
        END) AS desc_registrasi";

        $selectFields = [
            $hasR('nomor_internet') ? 'r.nomor_internet' : 'NULL AS nomor_internet',
            $hasR('nik_penduduk') ? 'r.nik_penduduk' : 'NULL AS nik_penduduk',
            $namaPelangganSql,
            $hasR('rt_pasang') ? 'r.rt_pasang' : 'NULL AS rt_pasang',
            $hasR('rw_pasang') ? 'r.rw_pasang' : 'NULL AS rw_pasang',
            $hasR('nomor_bangunan') ? 'r.nomor_bangunan' : 'NULL AS nomor_bangunan',
            $hasR('alamat_pasang') ? 'r.alamat_pasang' : 'NULL AS alamat_pasang',
            $hasR('kode_wilayah_kelurahan_pasang') ? 'r.kode_wilayah_kelurahan_pasang' : 'NULL AS kode_wilayah_kelurahan_pasang',
            $hasR('jenis_bangunan') ? 'r.jenis_bangunan' : 'NULL AS jenis_bangunan',
            $hasR('lon_lat') ? 'r.lon_lat' : 'NULL AS lon_lat',
            $hasR('loc_maps') ? 'r.loc_maps' : 'NULL AS loc_maps',
            $hasR('note_request') ? 'r.note_request' : 'NULL AS note_request',
            $pppoeUserSql,
            $pppoePassSql,
            $hasR('kode_bandwith') ? 'r.kode_bandwith' : 'NULL AS kode_bandwith',
            $hasR('kode_pop') ? 'r.kode_pop' : 'NULL AS kode_pop',
            $hasR('ont_us') ? 'r.ont_us' : 'NULL AS ont_us',
            $hasR('ont_ps') ? 'r.ont_ps' : 'NULL AS ont_ps',
            $hasR('status_reg') ? 'r.status_reg' : 'NULL AS status_reg',
            $hasR('media_akses') ? 'r.media_akses' : 'NULL AS media_akses',
            $hasR('ppn') ? 'r.ppn' : 'NULL AS ppn',
            $hasR('ppn_nom') ? 'r.ppn_nom' : 'NULL AS ppn_nom',
            $hasR('potongan') ? 'r.potongan' : 'NULL AS potongan',
            $hasR('potongan_note') ? 'r.potongan_note' : 'NULL AS potongan_note',
            $hasR('last_month_billing') ? 'r.last_month_billing' : 'NULL AS last_month_billing',
            $hasR('last_year_billing') ? 'r.last_year_billing' : 'NULL AS last_year_billing',
            $hasR('periode_billing') ? 'r.periode_billing' : 'NULL AS periode_billing',
            $hasR('jns_notif') ? 'r.jns_notif' : 'NULL AS jns_notif',
            $hasR('is_termin') ? 'r.is_termin' : 'NULL AS is_termin',
            $hasR('is_suspend') ? 'r.is_suspend' : 'NULL AS is_suspend',
            $hasR('count_suspend') ? 'r.count_suspend' : 'NULL AS count_suspend',
            $hasR('is_denda') ? 'r.is_denda' : 'NULL AS is_denda',
            $hasR('islock') ? 'r.islock' : 'NULL AS islock',
            $hasR('prorate') ? 'r.prorate' : 'NULL AS prorate',
            $hasR('date_create') ? 'r.date_create' : 'NULL AS date_create',
            $hasR('user_create') ? 'r.user_create' : 'NULL AS user_create',
            $hasR('date_update') ? 'r.date_update' : 'NULL AS date_update',
            $hasR('user_update') ? 'r.user_update' : 'NULL AS user_update',
            $hasR('hide') ? 'r.hide' : 'NULL AS hide',
            $hasR('mitra') ? 'r.mitra' : 'NULL AS mitra',
            $hasR('group_layanan') ? 'r.group_layanan' : 'NULL AS group_layanan',
            $hasR('nama_sales') ? 'r.nama_sales' : 'NULL AS nama_sales',
            $hasR('olt') ? 'r.olt' : 'NULL AS olt',
            $hasR('router_id') ? 'r.router_id' : 'NULL AS router_id',
            $hasR('index_olt') ? 'r.index_olt' : 'NULL AS index_olt',
            $hasR('scan_dokumen_survey') ? 'r.scan_dokumen_survey' : 'NULL AS scan_dokumen_survey',
            $hasR('scan_dokumen_instalasi') ? 'r.scan_dokumen_instalasi' : 'NULL AS scan_dokumen_instalasi',
            $hasR('scan_dokumen_aktivasi') ? 'r.scan_dokumen_aktivasi' : 'NULL AS scan_dokumen_aktivasi',
            $hasR('is_login') ? 'r.is_login' : 'NULL AS is_login',
            'p.nama_penduduk',
            'p.email',
            'p.nomor_hp',
            'p.nomor_hp_2',
            $hasP('jenis_kelamin') ? 'p.jenis_kelamin' : ($hasR('jenis_kelamin') ? 'r.jenis_kelamin' : 'NULL AS jenis_kelamin'),
            $hasP('pic') ? 'p.pic' : ($hasR('pic') ? 'r.pic' : 'NULL AS pic'),
            $hasP('alamat_ktp') ? 'p.alamat_ktp' : ($hasR('alamat_ktp') ? 'r.alamat_ktp' : 'NULL AS alamat_ktp'),
            $hasP('alamat_ktp') ? 'p.alamat_ktp AS alamat_k' : ($hasR('alamat_ktp') ? 'r.alamat_ktp AS alamat_k' : 'NULL AS alamat_k'),
            'p.alamat_ktp AS alamat_p',
            'p.kode_wilayah_kelurahan_ktp',
            $hasR('nama_kota_pasang') ? 'r.nama_kota_pasang' : 'NULL AS nama_kota_pasang',
            $descRegistrasiSql,
            $hasR('aktivasi_date_finish') ? 'r.aktivasi_date_finish' : 'NULL AS aktivasi_date_finish',
            'b.nama_bandwith',
            'b.kode_kategori_bandwith',
            'b.nominal_bandwith',
            'b.harga_bandwith',
            'k.nama_kategori_bandwith',
            'k.alias_nama_kategori',
        ];

        $selectClause = implode(",\n    ", $selectFields);
        $srJoin = $hasSr ? "LEFT JOIN m_status_registrasi sr ON r.status_reg = sr.status_reg" : "";

        $sql = "CREATE OR REPLACE VIEW view_batchjob AS
SELECT 
    {$selectClause}
FROM trx_batchjob_register r
LEFT JOIN m_pelanggan p ON r.nik_penduduk = p.nik_penduduk
LEFT JOIN m_bandwith b ON r.kode_bandwith = b.kode_bandwith
LEFT JOIN m_bandwith_kategori k ON b.kode_kategori_bandwith = k.kode_kategori_bandwith
{$srJoin}";

        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("view_batchjob desc_registrasi fix note: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
