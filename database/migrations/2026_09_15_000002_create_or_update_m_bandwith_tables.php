<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table m_bandwith_kategori
        if (!Schema::hasTable('m_bandwith_kategori')) {
            Schema::create('m_bandwith_kategori', function (Blueprint $table) {
                $table->string('kode_kategori_bandwith', 50)->primary();
                $table->string('nama_kategori_bandwith', 100);
                $table->string('alias_nama_kategori', 100)->nullable();
                $table->decimal('biaya_reg', 15, 2)->default(0);
                $table->tinyInteger('disable')->default(0);
                $table->timestamps();
            });
        }

        // 2. Table m_bandwith
        if (!Schema::hasTable('m_bandwith')) {
            Schema::create('m_bandwith', function (Blueprint $table) {
                $table->string('kode_bandwith', 50)->primary();
                $table->string('nama_bandwith', 150)->nullable();
                $table->string('kode_kategori_bandwith', 50)->nullable();
                $table->integer('nominal_bandwith')->default(0);
                $table->decimal('harga_bandwith', 15, 2)->default(0);
                $table->string('peruntukan_bangunan', 255)->nullable();
                $table->string('kategori_bangunan', 100)->nullable();
                $table->tinyInteger('disable')->default(0);
                $table->char('hide', 1)->default('0');
                $table->dateTime('date_create')->nullable();
                $table->string('user_create', 100)->nullable();
                $table->dateTime('date_update')->nullable();
                $table->string('user_update', 100)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('m_bandwith', function (Blueprint $table) {
                if (!Schema::hasColumn('m_bandwith', 'nama_bandwith')) {
                    $table->string('nama_bandwith', 150)->nullable()->after('kode_bandwith');
                }
                if (!Schema::hasColumn('m_bandwith', 'peruntukan_bangunan')) {
                    $table->string('peruntukan_bangunan', 255)->nullable()->after('harga_bandwith');
                }
                if (!Schema::hasColumn('m_bandwith', 'kategori_bangunan')) {
                    $table->string('kategori_bangunan', 100)->nullable()->after('peruntukan_bangunan');
                }
                if (!Schema::hasColumn('m_bandwith', 'disable')) {
                    $table->tinyInteger('disable')->default(0)->after('kategori_bangunan');
                }
                if (!Schema::hasColumn('m_bandwith', 'hide')) {
                    $table->char('hide', 1)->default('0')->after('disable');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No drop for safe reverse
    }
};
