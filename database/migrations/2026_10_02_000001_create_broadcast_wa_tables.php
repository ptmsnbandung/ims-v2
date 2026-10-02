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
        if (!Schema::hasTable('tb_broadcast_wa_template')) {
            Schema::create('tb_broadcast_wa_template', function (Blueprint $table) {
                $table->id();
                $table->string('nama_template', 150);
                $table->string('subjek', 255)->nullable();
                $table->string('kategori', 50)->default('custom'); // jatuh_tempo, pengumuman, promo, custom
                $table->text('pesan');
                $table->tinyInteger('is_default')->default(0);
                $table->timestamps();
            });

            // Insert Default Templates
            DB::table('tb_broadcast_wa_template')->insert([
                [
                    'nama_template' => 'Peringatan Jatuh Tempo Tagihan',
                    'subjek'        => 'Pengingat Tagihan Internet IMS',
                    'kategori'      => 'jatuh_tempo',
                    'pesan'         => "Halo Pelanggan Yth. *{nama}*,\n\nKami menginformasikan bahwa tagihan layanan internet IMS Anda untuk periode *{periode}* sejumlah *{nominal}* akan memasuki jatuh tempo pada *{jatuh_tempo}*.\n\nNomor Internet: *{nomor_internet}*\nPaket: *{paket}*\n\nSilakan melakukan pembayaran melalui link resmi berikut:\n{link_pembayaran}\n\nAbaikan pesan ini jika Anda sudah melakukan pembayaran.\nTerima kasih atas kepercayaan Anda menggunakan layanan IMS.",
                    'is_default'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'nama_template' => 'Pengumuman Pemeliharaan Jaringan',
                    'subjek'        => 'Maintenance Network IMS',
                    'kategori'      => 'pengumuman',
                    'pesan'         => "Pemberitahuan Pemeliharaan Jaringan IMS 🔧\n\nKepada Pelanggan Yth. *{nama}*,\n\nDisampaikan bahwa akan dilakukan perbaikan/pemeliharaan jaringan internet di area *{alamat}* pada tanggal *{jatuh_tempo}*.\n\nSelama proses pemeliharaan berlangsung, akses internet mungkin mengalami penyesuaian atau disrupsi sementara. Tim teknis kami akan bekerja secepat mungkin agar layanan kembali optimal.\n\nMohon maaf atas ketidaknyamanan ini. Terima kasih atas pengertian Anda.",
                    'is_default'    => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'nama_template' => 'Peringatan Isolir Layanan (Tunggakan)',
                    'subjek'        => 'Peringatan Isolir Internet IMS',
                    'kategori'      => 'jatuh_tempo',
                    'pesan'         => "Pemberitahuan Layanan Internet IMS ⚠️\n\nHalo *{nama}* (ID: *{nomor_internet}*),\n\nDiberitahukan bahwa tagihan internet Anda periode *{periode}* sebesar *{nominal}* telah melewati tanggal jatuh tempo (*{jatuh_tempo}*).\n\nUntuk menghindari pembatasan/isolir layanan secara otomatis, mohon segera melakukan pelunasan tagihan melalui link berikut:\n{link_pembayaran}\n\nBila ada kendala pembayaran, silakan hubungi tim Support IMS. Terima kasih.",
                    'is_default'    => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'nama_template' => 'Pengumuman Informasi Umum / Custom',
                    'subjek'        => 'Pengumuman IMS',
                    'kategori'      => 'custom',
                    'pesan'         => "Halo *{nama}*,\n\n[Tuliskan pesan pengumuman atau informasi khusus di sini]\n\nTerima kasih,\nIMS Management",
                    'is_default'    => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
            ]);
        }

        if (!Schema::hasTable('tb_broadcast_wa_log')) {
            Schema::create('tb_broadcast_wa_log', function (Blueprint $table) {
                $table->id();
                $table->string('kode_broadcast', 50)->index();
                $table->enum('jenis', ['single', 'massal'])->default('single');
                $table->string('kode_pengguna', 50)->nullable();
                $table->string('nama_pengirim', 100)->nullable();
                $table->string('nomor_internet', 50)->nullable()->index();
                $table->string('nama_penerima', 150)->nullable();
                $table->string('nomor_hp', 30)->nullable();
                $table->text('pesan_terkirim');
                $table->string('kategori', 50)->default('custom');
                $table->string('status_kirim', 30)->default('sent');
                $table->string('metode_kirim', 30)->default('wa_web'); // wa_web, wa_gateway
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_broadcast_wa_log');
        Schema::dropIfExists('tb_broadcast_wa_template');
    }
};
