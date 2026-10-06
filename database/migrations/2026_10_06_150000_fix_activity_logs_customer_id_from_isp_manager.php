<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Fix activity_logs data yang diimport dari database isp_manager.
     *
     * Masalah:
     *   - customer_id menyimpan angka ID internal (FK ke customers.id di isp_manager)
     *   - user_id menyimpan angka ID internal (FK ke users.id di isp_manager)
     *
     * Solusi:
     *   - Baca tabel trx_batchjob_register untuk cari nomor_internet berdasarkan pppoe_username
     *     yang ada di description/router_response pada activity_logs
     *   - Atau: baca dari import_customers_to_imsv2 mapping jika ada
     *   - Update customer_id ke nomor_internet yang benar
     */
    public function up(): void
    {
        // Ambil semua activity_logs yang customer_id-nya masih berupa angka murni
        $numericLogs = DB::table('activity_logs')
            ->whereRaw("customer_id REGEXP '^[0-9]+$'")
            ->where('customer_id', '<', '100000') // Angka kecil = ID internal, bukan nomor_internet (biasanya 7+ digit)
            ->get(['id', 'customer_id', 'user_id', 'description', 'router_response', 'action']);

        if ($numericLogs->isEmpty()) {
            echo "Tidak ada data activity_logs yang perlu diperbaiki.\n";
            return;
        }

        echo "Ditemukan {$numericLogs->count()} baris yang perlu diperbaiki...\n";

        $updated = 0;
        $failed = 0;
        $notFound = 0;

        foreach ($numericLogs as $log) {
            // Coba ekstrak nomor internet dari description atau router_response
            $nomorInternet = $this->extractNomorInternet($log);

            if ($nomorInternet) {
                DB::table('activity_logs')
                    ->where('id', $log->id)
                    ->update(['customer_id' => $nomorInternet]);
                $updated++;
            } else {
                $notFound++;
            }
        }

        // Perbaiki user_id: ganti angka kecil dengan nama user dari tb_pengguna / tb_m_karyawan
        $this->fixUserIds();

        echo "Selesai. Updated: {$updated}, Tidak ditemukan: {$notFound}, Gagal: {$failed}\n";
    }

    /**
     * Ekstrak nomor_internet dari deskripsi atau respons router log
     */
    private function extractNomorInternet(object $log): ?string
    {
        $text = ($log->description ?? '') . ' ' . ($log->router_response ?? '');

        // Coba cari pppoe_username dari description, lalu cari di trx_batchjob_register
        // Contoh deskripsi: "User '1110425' berhasil diaktifkan" atau "User 12110724 diaktifkan"
        if (preg_match("/[Uu]ser\s*['\"]?([A-Za-z0-9]+)['\"]?\s*(berhasil|diaktif|didisable|dihapus)/u", $text, $m)) {
            $pppoeUser = trim($m[1]);
            if ($pppoeUser) {
                $customer = DB::table('trx_batchjob_register')
                    ->where('pppoe_username', $pppoeUser)
                    ->orWhere('ont_us', $pppoeUser)
                    ->first(['nomor_internet']);
                if ($customer) {
                    return (string) $customer->nomor_internet;
                }
            }
        }

        // Coba cari pola nomor internet (biasanya angka 4-8 digit) langsung dari description
        // Contoh: "ONU gpon-onu_1/1/7:23" -> tidak langsung ke nomor internet
        // Coba dari PPPoE username di router_response
        if (preg_match("/['\"]([A-Za-z0-9]{5,12})['\"].*berhasil/u", $text, $m)) {
            $pppoeUser = trim($m[1]);
            if ($pppoeUser && !is_numeric($pppoeUser)) {
                $customer = DB::table('trx_batchjob_register')
                    ->where('pppoe_username', $pppoeUser)
                    ->orWhere('ont_us', $pppoeUser)
                    ->first(['nomor_internet']);
                if ($customer) {
                    return (string) $customer->nomor_internet;
                }
            }
        }

        return null;
    }

    /**
     * Perbaiki user_id yang masih berupa angka (ID internal isp_manager)
     */
    private function fixUserIds(): void
    {
        // Ambil mapping dari tb_pengguna atau users berdasarkan urutan ID
        // Dalam isp_manager, users table menyimpan nama admin
        $userMapping = [
            '1'  => 'admin',
            '2'  => 'Nur Rashif',
            '3'  => 'Amelia Agustina',
            '4'  => 'Ridwan',
            '5'  => 'admin',
            '6'  => 'admin',
            '7'  => 'admin',
            '8'  => 'admin',
            '9'  => 'admin',
            '10' => 'admin',
            '11' => 'admin',
            '12' => 'admin',
        ];

        // Coba ambil nama dari tb_pengguna / tb_m_karyawan jika ada mapping ke kode_pengguna
        $karyawans = DB::table('tb_m_karyawan')->get(['kode_karyawan', 'nama_karyawan'])->keyBy('kode_karyawan');

        foreach ($userMapping as $idLama => $namaDefault) {
            // Cari nama yang sesuai dari tb_pengguna
            $pengguna = DB::table('tb_pengguna')->where('kode_pengguna', $idLama)->first(['kode_karyawan']);
            $namaFinal = $namaDefault;

            if ($pengguna && $karyawans->has($pengguna->kode_karyawan)) {
                $namaFinal = $karyawans->get($pengguna->kode_karyawan)->nama_karyawan;
            }

            DB::table('activity_logs')
                ->where('user_id', (string) $idLama)
                ->whereRaw("user_id REGEXP '^[0-9]+$'")
                ->where('user_id', '<', '100')
                ->update(['user_id' => $namaFinal]);
        }
    }

    public function down(): void
    {
        // Tidak bisa di-reverse otomatis karena data asli sudah hilang
    }
};
