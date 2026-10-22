<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutoSuspendUnpaidCustomersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        // Setup mock tables in SQLite memory
        Schema::dropIfExists('trx_suspend');
        Schema::dropIfExists('trx_billing_layanan');
        Schema::dropIfExists('trx_batchjob_register');

        Schema::create('trx_batchjob_register', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_internet')->nullable()->index();
            $table->string('status_reg')->nullable();
            $table->string('nama_pelanggan')->nullable();
            $table->string('hide')->default('0');
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('trx_billing_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_billing_layanan')->nullable();
            $table->string('nomor_internet')->nullable()->index();
            $table->string('status_bill_lay')->nullable();
            $table->decimal('total_layanan', 15, 2)->default(0);
            $table->string('hide')->default('0');
            $table->dateTime('date_create')->nullable();
        });

        Schema::create('trx_suspend', function (Blueprint $table) {
            $table->id();
            $table->string('kode_suspend', 64)->unique();
            $table->string('nomor_internet', 64)->index();
            $table->date('suspend_start')->nullable();
            $table->date('suspend_end')->nullable();
            $table->string('status_suspend', 20)->default('11')->index();
            $table->text('desc_suspend')->nullable();
            $table->text('note_suspend')->nullable();
            $table->dateTime('date_create')->nullable();
            $table->string('user_create', 255)->nullable();
            $table->dateTime('date_update')->nullable();
            $table->string('user_update', 255)->nullable();
            $table->char('hide', 1)->default('0');
            $table->timestamps();
        });
    }

    public function test_it_creates_suspend_requests_for_active_unpaid_customers(): void
    {
        // 1. Setup pelanggan aktif dengan tagihan belum lunas
        $nomorActive = 'TEST-ACTIVE-' . rand(1000, 9999);
        DB::table('trx_batchjob_register')->insert([
            'nomor_internet' => $nomorActive,
            'status_reg'     => '20', // Aktif
            'nama_pelanggan' => 'Pelanggan Test Aktif',
            'hide'           => '0',
            'date_create'    => now(),
        ]);

        DB::table('trx_billing_layanan')->insert([
            'kode_billing_layanan' => 'INV/' . $nomorActive . '/10/2026',
            'nomor_internet'       => $nomorActive,
            'status_bill_lay'      => '12', // Belum bayar
            'total_layanan'        => 200000,
            'hide'                 => '0',
            'date_create'          => now(),
        ]);

        // 2. Setup pelanggan terminated (status_reg = 23) dengan tagihan belum lunas
        $nomorTerminated = 'TEST-TERM-' . rand(1000, 9999);
        DB::table('trx_batchjob_register')->insert([
            'nomor_internet' => $nomorTerminated,
            'status_reg'     => '23', // Terminated
            'nama_pelanggan' => 'Pelanggan Test Terminated',
            'hide'           => '0',
            'date_create'    => now(),
        ]);

        DB::table('trx_billing_layanan')->insert([
            'kode_billing_layanan' => 'INV/' . $nomorTerminated . '/10/2026',
            'nomor_internet'       => $nomorTerminated,
            'status_bill_lay'      => '12', // Belum bayar
            'total_layanan'        => 200000,
            'hide'                 => '0',
            'date_create'          => now(),
        ]);

        // 3. Setup pelanggan aktif dengan tagihan SUDAH LUNAS (status_bill_lay = 15)
        $nomorPaid = 'TEST-PAID-' . rand(1000, 9999);
        DB::table('trx_batchjob_register')->insert([
            'nomor_internet' => $nomorPaid,
            'status_reg'     => '20', // Aktif
            'nama_pelanggan' => 'Pelanggan Test Lunas',
            'hide'           => '0',
            'date_create'    => now(),
        ]);

        DB::table('trx_billing_layanan')->insert([
            'kode_billing_layanan' => 'INV/' . $nomorPaid . '/10/2026',
            'nomor_internet'       => $nomorPaid,
            'status_bill_lay'      => '15', // Lunas
            'total_layanan'        => 200000,
            'hide'                 => '0',
            'date_create'          => now(),
        ]);

        // 4. Jalankan command suspend
        $exitCode = Artisan::call('suspend:unpaid-customers');
        $this->assertEquals(0, $exitCode);

        // 5. Verifikasi pelanggan aktif yang belum bayar masuk ke trx_suspend
        $this->assertDatabaseHas('trx_suspend', [
            'nomor_internet' => $nomorActive,
            'status_suspend' => '11',
        ]);

        // 6. Verifikasi pelanggan terminated TIDAK dimasukkan ke trx_suspend
        $this->assertDatabaseMissing('trx_suspend', [
            'nomor_internet' => $nomorTerminated,
        ]);

        // 7. Verifikasi pelanggan lunas TIDAK dimasukkan ke trx_suspend
        $this->assertDatabaseMissing('trx_suspend', [
            'nomor_internet' => $nomorPaid,
        ]);

        // 8. Test Idempotency: Jalankan lagi, pastikan tidak ada duplikasi
        Artisan::call('suspend:unpaid-customers');
        $count = DB::table('trx_suspend')->where('nomor_internet', $nomorActive)->count();
        $this->assertEquals(1, $count);
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $nomorActive = 'TEST-DRY-' . rand(1000, 9999);
        DB::table('trx_batchjob_register')->insert([
            'nomor_internet' => $nomorActive,
            'status_reg'     => '20',
            'nama_pelanggan' => 'Pelanggan Dry Run',
            'hide'           => '0',
            'date_create'    => now(),
        ]);

        DB::table('trx_billing_layanan')->insert([
            'kode_billing_layanan' => 'INV/' . $nomorActive . '/10/2026',
            'nomor_internet'       => $nomorActive,
            'status_bill_lay'      => '12',
            'total_layanan'        => 150000,
            'hide'                 => '0',
            'date_create'          => now(),
        ]);

        $exitCode = Artisan::call('suspend:unpaid-customers', ['--dry-run' => true]);
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseMissing('trx_suspend', [
            'nomor_internet' => $nomorActive,
        ]);
    }
}
