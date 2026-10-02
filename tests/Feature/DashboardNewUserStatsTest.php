<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DashboardNewUserStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        // Setup necessary tables for Dashboard
        Schema::dropIfExists('trx_batchjob_register');
        Schema::dropIfExists('view_batchjob');
        Schema::dropIfExists('tb_pengguna');
        Schema::dropIfExists('tb_m_level_pengguna');
        Schema::dropIfExists('tb_m_karyawan');
        Schema::dropIfExists('trx_billing_layanan');

        Schema::create('tb_m_level_pengguna', function (Blueprint $table) {
            $table->string('kode_level', 50)->primary();
            $table->string('nama_level', 100);
        });

        Schema::create('tb_pengguna', function (Blueprint $table) {
            $table->string('kode_pengguna', 50)->primary();
            $table->string('nama_pengguna', 100);
            $table->string('username', 100)->unique();
            $table->string('password', 255);
            $table->string('kode_level', 50)->nullable();
            $table->string('kode_karyawan', 50)->nullable();
            $table->integer('status_pengguna')->default(1);
            $table->string('remember_token', 100)->nullable();
            $table->string('date_create', 50)->nullable();
        });

        Schema::create('trx_batchjob_register', function (Blueprint $table) {
            $table->id('id_batchjob');
            $table->string('nomor_internet', 50)->nullable();
            $table->string('nama_pelanggan', 150)->nullable();
            $table->string('status_reg', 20)->default('11');
            $table->string('kode_bandwith', 50)->nullable();
            $table->string('nama_kategori_bandwith', 100)->nullable();
            $table->string('nominal_bandwith', 50)->nullable();
            $table->dateTime('date_create')->nullable();
        });

        DB::table('tb_m_level_pengguna')->insert([
            ['kode_level' => 'LV01', 'nama_level' => 'ADMIN'],
            ['kode_level' => 'LV02', 'nama_level' => 'DIREKTUR'],
            ['kode_level' => 'LV03', 'nama_level' => 'TEKNIK'],
            ['kode_level' => 'LV04', 'nama_level' => 'NOC'],
            ['kode_level' => 'LV05', 'nama_level' => 'FINANCE'],
        ]);

        DB::table('tb_pengguna')->insert([
            'kode_pengguna' => 'USR01',
            'nama_pengguna' => 'Admin Test',
            'username' => 'admin@test.com',
            'password' => bcrypt('secret'),
            'kode_level' => 'LV01',
            'status_pengguna' => 1,
            'date_create' => '2026-10-01 08:00:00',
        ]);
    }

    public function test_dashboard_shows_new_users_stats_and_filter(): void
    {
        $this->withoutExceptionHandling();
        $admin = Pengguna::where('username', 'admin@test.com')->first();

        // Insert sample registered users for October 2026
        DB::table('trx_batchjob_register')->insert([
            [
                'nomor_internet' => '10000126',
                'nama_pelanggan' => 'Pelanggan Baru 1',
                'status_reg' => '20', // Aktif
                'kode_bandwith' => 'BW30',
                'nama_kategori_bandwith' => 'HOME',
                'nominal_bandwith' => '30',
                'date_create' => '2026-10-02 10:00:00',
            ],
            [
                'nomor_internet' => '10000226',
                'nama_pelanggan' => 'Pelanggan Baru 2',
                'status_reg' => '18', // Antrean aktivasi
                'kode_bandwith' => 'BW50',
                'nama_kategori_bandwith' => 'BUSINESS',
                'nominal_bandwith' => '50',
                'date_create' => '2026-10-03 11:00:00',
            ],
            [
                'nomor_internet' => '10000326',
                'nama_pelanggan' => 'Pelanggan Lama Sept',
                'status_reg' => '20',
                'kode_bandwith' => 'BW30',
                'nama_kategori_bandwith' => 'HOME',
                'nominal_bandwith' => '30',
                'date_create' => '2026-09-15 09:00:00',
            ],
        ]);

        // Filter October 2026
        $response = $this->actingAs($admin)->get('/dashboard?bulan=10&tahun=2026');

        $response->assertStatus(200);
        $response->assertSee('Statistik User', false);
        $response->assertSee('Oktober 2026', false);
        $response->assertSee('Pelanggan Baru 1', false);
        $response->assertSee('Pelanggan Baru 2', false);
        $response->assertDontSee('Pelanggan Lama Sept');
    }
}
