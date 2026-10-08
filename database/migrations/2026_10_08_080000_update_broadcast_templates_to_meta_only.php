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
        if (Schema::hasTable('tb_broadcast_wa_template')) {
            Schema::table('tb_broadcast_wa_template', function (Blueprint $table) {
                if (!Schema::hasColumn('tb_broadcast_wa_template', 'meta_template_name')) {
                    $table->string('meta_template_name', 150)->nullable()->after('nama_template');
                }
                if (!Schema::hasColumn('tb_broadcast_wa_template', 'meta_language')) {
                    $table->string('meta_language', 20)->default('id')->after('meta_template_name');
                }
                if (!Schema::hasColumn('tb_broadcast_wa_template', 'meta_params_map')) {
                    $table->text('meta_params_map')->nullable()->after('meta_language');
                }
            });

            // Hapus template dummy lama yang tidak terdaftar di Meta
            DB::table('tb_broadcast_wa_template')
                ->whereIn('meta_template_name', [
                    'pengingat_jatuh_tempo_v1', 
                    'pengumuman_maintenance', 
                    'peringatan_isolir_layanan', 
                    'pengumuman_umum'
                ])
                ->orWhereNull('meta_template_name')
                ->orWhere('meta_template_name', '')
                ->delete();

            // Pastikan template resmi tagihan_bulanan ada
            $hasTagihanBulanan = DB::table('tb_broadcast_wa_template')
                ->where('meta_template_name', 'tagihan_bulanan')
                ->exists();

            if (!$hasTagihanBulanan) {
                DB::table('tb_broadcast_wa_template')->insert([
                    'nama_template'      => 'Tagihan Bulanan Resmi (Meta)',
                    'meta_template_name' => 'tagihan_bulanan',
                    'meta_language'      => 'id',
                    'meta_params_map'    => json_encode(['nama', 'periode', 'nominal', 'jatuh_tempo', 'nomor_internet', 'paket', 'link_pembayaran']),
                    'subjek'             => 'Tagihan Bulanan Internet IMS',
                    'kategori'           => 'utility',
                    'pesan'              => "Halo, Bapak/Ibu *{nama}*,\n\nKami menginformasikan tagihan layanan internet IMS Anda periode *{periode}* sebesar *{nominal}* dengan batas jatuh tempo pada *{jatuh_tempo}*.\n\nNomor Internet: *{nomor_internet}*\nPaket: *{paket}*\n\nSilakan lakukan pembayaran melalui link resmi berikut:\n{link_pembayaran}\n\nTerima kasih atas kerja samanya.",
                    'is_default'         => 1,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            // Pastikan template resmi work_report ada
            $hasWorkReport = DB::table('tb_broadcast_wa_template')
                ->where('meta_template_name', 'work_report')
                ->exists();

            if (!$hasWorkReport) {
                DB::table('tb_broadcast_wa_template')->insert([
                    'nama_template'      => 'Work Report / Assignment (Meta)',
                    'meta_template_name' => 'work_report',
                    'meta_language'      => 'en',
                    'meta_params_map'    => json_encode(['nama', 'nomor_internet', 'alamat', 'paket']),
                    'subjek'             => 'Work Report Assignment',
                    'kategori'           => 'utility',
                    'pesan'              => "🛠 ASSIGNMENT WORK REPORT\n\nCustomer: *{nama}*\nID: *{nomor_internet}*\nAddress: *{alamat}*\nPackage: *{paket}*\n\nPlease process immediately.",
                    'is_default'         => 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        if (Schema::hasTable('tb_broadcast_wa_log')) {
            Schema::table('tb_broadcast_wa_log', function (Blueprint $table) {
                if (!Schema::hasColumn('tb_broadcast_wa_log', 'meta_message_id')) {
                    $table->string('meta_message_id', 100)->nullable()->after('metode_kirim')->index();
                }
                if (!Schema::hasColumn('tb_broadcast_wa_log', 'meta_error_message')) {
                    $table->text('meta_error_message')->nullable()->after('meta_message_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
