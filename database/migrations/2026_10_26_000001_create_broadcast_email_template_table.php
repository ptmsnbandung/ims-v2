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
        if (!Schema::hasTable('tb_broadcast_email_template')) {
            Schema::create('tb_broadcast_email_template', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';
                $table->id();
                $table->string('slug', 100)->nullable()->index();
                $table->string('name', 150);
                $table->string('badge', 100)->nullable();
                $table->string('subject', 255);
                $table->longText('body');
                $table->string('action_text', 100)->nullable();
                $table->string('action_url', 255)->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });

            // Seed default presets
            $portalUrl = config('company.portal_url', 'https://portal.ptmsn.co.id');
            $billingWa = config('company.billing_whatsapp', '6285188358385');
            $now = now();

            $defaults = [
                [
                    'slug'        => 'jatuh_tempo',
                    'name'        => 'Pengingat Tagihan Jatuh Tempo (Belum Lunas)',
                    'badge'       => 'PENGINGAT TAGIHAN',
                    'subject'     => 'Pengingat Pembayaran Tagihan Internet Periode {periode} - PT Media Solusi Network',
                    'body'        => "Halo {nama_pelanggan},\n\nKami mengingatkan bahwa tagihan layanan internet Anda #{nomor_internet} ({paket}) untuk periode {periode} sebesar {nominal} telah terbit dan memasuki masa jatuh tempo.\n\nAgar koneksi internet Anda tetap aktif tanpa kendala (terhindar dari isolir otomatis), mohon segera melakukan pembayaran melalui Portal Pelanggan MyMSN.\n\nAbaikan pesan ini apabila Anda telah melakukan pembayaran. Terima kasih atas kerja sama Anda.",
                    'action_text' => 'Bayar Tagihan di Portal',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'suspend',
                    'name'        => 'Pemberitahuan Layanan Terisolir / Suspend',
                    'badge'       => 'STATUS LAYANAN ISOLIR',
                    'subject'     => 'Pemberitahuan Nonaktif Sementara (Suspend) Layanan Internet #{nomor_internet}',
                    'body'        => "Kepada Yth. {nama_pelanggan},\n\nKami menginformasikan bahwa layanan internet Anda #{nomor_internet} ({paket}) saat ini sedang dinonaktifkan sementara (Suspend / Isolir) dikarenakan adanya tagihan periode {periode} sebesar {nominal} yang melewati batas waktu pembayaran.\n\nUntuk mengaktifkan kembali koneksi internet Anda secara otomatis, silakan selesaikan pembayaran tagihan melalui Portal Pelanggan atau hubungi tim Admin Billing kami.",
                    'action_text' => 'Cek & Bayar Tagihan Sekarang',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'lunas',
                    'name'        => 'Ucapan Terima Kasih Pembayaran Lunas',
                    'badge'       => 'KONFIRMASI PEMBAYARAN',
                    'subject'     => 'Terima Kasih! Pembayaran Tagihan Periode {periode} Telah Kami Terima',
                    'body'        => "Halo {nama_pelanggan},\n\nTerima kasih! Pembayaran tagihan layanan internet Anda #{nomor_internet} ({paket}) untuk periode {periode} sebesar {nominal} telah tercatat LUNAS di sistem kami.\n\nAnda dapat mengunduh bukti kwitansi / invoice resmi kapan saja melalui Portal Pelanggan MyMSN.\n\nSelamat menikmati layanan internet dari PT Media Solusi Network.",
                    'action_text' => 'Lihat Kwitansi di Portal',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'maintenance',
                    'name'        => 'Pemberitahuan Pemeliharaan Jaringan (Maintenance)',
                    'badge'       => 'PEMELIHARAAN JARINGAN',
                    'subject'     => 'Pemberitahuan Pemeliharaan Jaringan Berkala - PT Media Solusi Network',
                    'body'        => "Kepada Pelanggan Yang Terhormat,\n\nDalam rangka meningkatkan kualitas dan keandalan layanan internet, kami akan melakukan pemeliharaan jaringan berkala (scheduled maintenance).\n\nSelama proses ini berlangsung, kemungkinan akan terjadi penurunan kualitas atau pemutusan koneksi internet sesaat. Kami memohon maaf yang sebesar-besarnya atas ketidaknyamanan yang ditimbulkan.\n\nTerima kasih atas pengertian dan kerja sama Anda.",
                    'action_text' => 'Cek Status Jaringan di Portal',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'gangguan',
                    'name'        => 'Informasi Gangguan Jaringan (Incident)',
                    'badge'       => 'GANGGUAN JARINGAN',
                    'subject'     => 'Informasi Penanganan Gangguan Jaringan - PT Media Solusi Network',
                    'body'        => "Kepada Pelanggan Yang Terhormat,\n\nKami menginformasikan bahwa saat ini sedang terjadi kendala teknis pada jalur utama jaringan internet di wilayah Anda, yang berdampak pada koneksi internet #{nomor_internet}.\n\nTim teknis kami sedang berada di lokasi untuk melakukan penanganan dan perbaikan sesegera mungkin. Estimasi perbaikan akan kami informasikan lebih lanjut.\n\nMohon maaf atas ketidaknyamanan yang terjadi.",
                    'action_text' => 'Hubungi WhatsApp Support',
                    'action_url'  => 'https://wa.me/' . $billingWa,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'portal_feature',
                    'name'        => 'Pemberitahuan Fitur Baru Portal Pelanggan',
                    'badge'       => 'FITUR TERBARU',
                    'subject'     => 'Kini Lebih Mudah Kelola Tagihan & Tiket di MyMSN Portal',
                    'body'        => "Halo {nama_pelanggan},\n\nKami dengan senang hati memperkenalkan fitur terbaru pada Portal Pelanggan MyMSN! Kini Anda dapat mengecek status tagihan bulanan, melihat riwayat pembayaran, mendownload invoice resmi, serta mengajukan tiket bantuan kendala dengan lebih cepat dan mudah langsung dari gadget Anda.\n\nSilakan login ke portal menggunakan nomor internet #{nomor_internet} Anda.",
                    'action_text' => 'Buka MyMSN Portal Sekarang',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'apology_reactivation',
                    'name'        => 'Aktivasi Jaringan & Permohonan Maaf Pembayaran',
                    'badge'       => 'PEMBERITAHUAN RESMI',
                    'subject'     => 'Pemberitahuan Resmi - PT Media Solusi Network',
                    'body'        => "👋 Halo Bapak/Ibu {nama_pelanggan},\n\nKabar baik, layanan internet untuk ID Pelanggan {nomor_internet} saat ini sudah AKTIF KEMBALI.\n\nKami memohon maaf yang sebesar-besarnya atas keterlambatan aktivasi jaringan setelah pembayaran melalui Midtrans. Terjadi kendala sinkronisasi sistem sesaat pada router aktivasi kami. 🙏\n\nSaat ini perbaikan sistem telah kami selesaikan sehingga untuk pembayaran berikutnya layanan Anda akan langsung aktif secara otomatis tanpa kendala.\n\nTerima kasih banyak atas kesabaran dan pengertian Anda.",
                    'action_text' => 'Kunjungi Portal Pelanggan',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'promo',
                    'name'        => 'Penawaran Khusus / Promo Upgrade Kecepatan',
                    'badge'       => 'PENAWARAN SPESIAL',
                    'subject'     => 'Penawaran Spesial Upgrade Kecepatan Internet untuk Anda!',
                    'body'        => "Halo {nama_pelanggan},\n\nTerima kasih telah setia menggunakan layanan internet {paket} dari PT Media Solusi Network.\n\nSebagai bentuk apresiasi kami, nikmati penawaran khusus upgrade kecepatan internet dengan harga spesial untuk mendukung kebutuhan streaming, kerja, dan aktivitas keluarga tanpa hambatan.\n\nHubungi tim customer service kami untuk informasi detail promo ini.",
                    'action_text' => 'Klaim Promo via WhatsApp',
                    'action_url'  => 'https://wa.me/' . $billingWa,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'slug'        => 'custom',
                    'name'        => 'Pesan Bebas / Custom',
                    'badge'       => 'PEMBERITAHUAN RESMI',
                    'subject'     => 'Pemberitahuan Resmi - PT Media Solusi Network',
                    'body'        => "Kepada Yth. {nama_pelanggan},\n\n(Tulis pesan pengumuman atau informasi Anda di sini...)\n\nTerima kasih atas perhatian dan kerja sama Anda.",
                    'action_text' => 'Kunjungi Portal Pelanggan',
                    'action_url'  => $portalUrl,
                    'is_system'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
            ];

            DB::table('tb_broadcast_email_template')->insert($defaults);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_broadcast_email_template');
    }
};
