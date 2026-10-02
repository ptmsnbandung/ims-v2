<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\BillingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class AutoGenerateMonthlyInvoicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        // Setup tables
        Schema::dropIfExists('trx_billing_layanan_log');
        Schema::dropIfExists('trx_billing_layanan_detail');
        Schema::dropIfExists('trx_billing_layanan');
        Schema::dropIfExists('view_batchjob');

        Schema::create('trx_billing_layanan', function (Blueprint $table) {
            $table->string('kode_billing_layanan')->primary();
            $table->string('nomor_internet')->nullable();
            $table->string('kode_bandwith')->nullable();
            $table->string('nominal_bandwith')->nullable();
            $table->string('bulan_tagihan')->nullable();
            $table->string('tahun_tagihan')->nullable();
            $table->string('periode_tagihan')->nullable();
            $table->string('potongan')->nullable();
            $table->string('desc_potongan')->nullable();
            $table->string('ppn')->nullable();
            $table->string('tax')->nullable();
            $table->string('voucher')->nullable();
            $table->string('total_layanan')->nullable();
            $table->string('notif_mail')->nullable();
            $table->string('notif_wa')->nullable();
            $table->string('status_bill_lay')->nullable();
            $table->string('denda')->nullable();
            $table->string('invoice_file')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_post')->nullable();
            $table->string('date_create')->nullable();
            $table->string('user_create')->nullable();
            $table->string('date_update')->nullable();
            $table->string('user_update')->nullable();
            $table->string('hide')->nullable();
            $table->string('islock')->nullable();
        });

        Schema::create('trx_billing_layanan_detail', function (Blueprint $table) {
            $table->id();
            $table->string('kode_billing_lay_detail')->nullable();
            $table->string('kode_billing_layanan')->nullable();
            $table->string('kode_item')->nullable();
            $table->string('komponen')->nullable();
            $table->integer('qty')->default(1);
            $table->string('biaya')->nullable();
            $table->string('date_create')->nullable();
            $table->string('user_create')->nullable();
            $table->string('hide')->nullable();
        });

        Schema::create('trx_billing_layanan_log', function (Blueprint $table) {
            $table->id();
            $table->string('kode_billing_lay_log')->nullable();
            $table->string('kode_billing_layanan')->nullable();
            $table->string('status_bill_lay')->nullable();
            $table->text('note_billing_lay')->nullable();
            $table->string('date_create')->nullable();
            $table->string('user_create')->nullable();
            $table->string('hide')->nullable();
        });

        Schema::create('view_batchjob', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_internet')->nullable();
            $table->string('nama_pelanggan')->nullable();
            $table->string('status_reg')->nullable();
            $table->string('hide')->default('0');
            $table->string('kode_bandwith')->nullable();
            $table->string('nominal_bandwith')->nullable();
            $table->string('nama_kategori_bandwith')->nullable();
            $table->decimal('harga_bandwith', 15, 2)->default(0);
            $table->decimal('potongan', 15, 2)->default(0);
            $table->string('potongan_note')->nullable();
            $table->string('ppn_nom')->nullable();
            $table->string('ppn')->nullable();
        });
    }

    public function test_it_generates_invoices_for_active_and_suspended_customers(): void
    {
        // 1. Insert customer samples
        DB::table('view_batchjob')->insert([
            [
                'nomor_internet' => '1001',
                'nama_pelanggan' => 'Pelanggan Aktif',
                'status_reg' => '20', // Aktif
                'hide' => '0',
                'kode_bandwith' => 'BW20',
                'nominal_bandwith' => '20',
                'nama_kategori_bandwith' => 'HOME',
                'harga_bandwith' => 200000,
                'potongan' => 0,
                'potongan_note' => '-',
                'ppn_nom' => '0.11',
                'ppn' => '2',
            ],
            [
                'nomor_internet' => '1002',
                'nama_pelanggan' => 'Pelanggan Suspend',
                'status_reg' => '21', // Suspend
                'hide' => '0',
                'kode_bandwith' => 'BW50',
                'nominal_bandwith' => '50',
                'nama_kategori_bandwith' => 'BIZ',
                'harga_bandwith' => 500000,
                'potongan' => 50000,
                'potongan_note' => 'Diskon Promo',
                'ppn_nom' => '0.11',
                'ppn' => '2',
            ],
            [
                'nomor_internet' => '1003',
                'nama_pelanggan' => 'Pelanggan Req Suspend',
                'status_reg' => '22', // Req Suspend
                'hide' => '0',
                'kode_bandwith' => 'BW10',
                'nominal_bandwith' => '10',
                'nama_kategori_bandwith' => 'HOME',
                'harga_bandwith' => 150000,
                'potongan' => 0,
                'potongan_note' => '-',
                'ppn_nom' => '0.11',
                'ppn' => '2',
            ],
            [
                'nomor_internet' => '1004',
                'nama_pelanggan' => 'Pelanggan Terminasi',
                'status_reg' => '23', // Terminasi -> TIDAK BOLEH DIBILLING
                'hide' => '0',
                'kode_bandwith' => 'BW10',
                'nominal_bandwith' => '10',
                'nama_kategori_bandwith' => 'HOME',
                'harga_bandwith' => 150000,
                'potongan' => 0,
                'potongan_note' => '-',
                'ppn_nom' => '0.11',
                'ppn' => '2',
            ],
            [
                'nomor_internet' => '1005',
                'nama_pelanggan' => 'Pelanggan Tersembunyi',
                'status_reg' => '20',
                'hide' => '1', // Hide = 1 -> TIDAK BOLEH DIBILLING
                'kode_bandwith' => 'BW10',
                'nominal_bandwith' => '10',
                'nama_kategori_bandwith' => 'HOME',
                'harga_bandwith' => 150000,
                'potongan' => 0,
                'potongan_note' => '-',
                'ppn_nom' => '0.11',
                'ppn' => '2',
            ],
        ]);

        // Run artisan command
        $exitCode = Artisan::call('finance:auto-generate-invoices', [
            '--bulan' => '10',
            '--tahun' => '2026',
            '--user' => 'Unit Test Runner',
        ]);

        $this->assertEquals(0, $exitCode);

        // Hanya 3 pelanggan (1001, 1002, 1003) yang harus terbit
        $invoices = DB::table('trx_billing_layanan')->get();
        $this->assertCount(3, $invoices);

        $inv1001 = DB::table('trx_billing_layanan')->where('nomor_internet', '1001')->first();
        $this->assertNotNull($inv1001);
        $this->assertEquals('INV/1001/10/2026', $inv1001->kode_billing_layanan);
        $this->assertEquals('200000', $inv1001->total_layanan);
        $this->assertEquals('12', $inv1001->status_bill_lay);

        $inv1002 = DB::table('trx_billing_layanan')->where('nomor_internet', '1002')->first();
        $this->assertNotNull($inv1002);
        $this->assertEquals('INV/1002/10/2026', $inv1002->kode_billing_layanan);
        // 500.000 - 50.000 = 450.000
        $this->assertEquals('450000', $inv1002->total_layanan);

        // Detail items must be 3
        $details = DB::table('trx_billing_layanan_detail')->get();
        $this->assertCount(3, $details);

        // Logs must be 3
        $logs = DB::table('trx_billing_layanan_log')->get();
        $this->assertCount(3, $logs);

        // Test idempotency / no duplicates on 2nd run
        $exitCode2 = Artisan::call('finance:auto-generate-invoices', [
            '--bulan' => '10',
            '--tahun' => '2026',
        ]);
        $this->assertEquals(0, $exitCode2);

        // Jumlah invoice tetap 3 (tidak ada duplikasi)
        $invoicesAfterSecondRun = DB::table('trx_billing_layanan')->get();
        $this->assertCount(3, $invoicesAfterSecondRun);
    }
}
