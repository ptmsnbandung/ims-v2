<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== COLUMNS OF TRX_BATCHJOB_REGISTER ===\n";
print_r(Schema::getColumnListing('trx_batchjob_register'));

echo "\n=== M_OLT ===\n";
print_r(DB::table('m_olt')->get()->toArray());

echo "\n=== M_POP ===\n";
print_r(DB::table('m_pop')->get(['kode_pop', 'nama_pop', 'kode_w', 'kode_olt', 'ip_pop'])->toArray());

echo "\n=== HOW ARE CUSTOMERS DISTRIBUTED BY KODE_POP & KODE_OLT & WILAYAH? ===\n";
$dist = DB::table('trx_batchjob_register')
    ->select('kode_pop', DB::raw('count(*) as total'))
    ->groupBy('kode_pop')
    ->get();
print_r($dist->toArray());

echo "\n=== SAMPLE TRX_BATCHJOB_REGISTER RECORDS ===\n";
print_r(DB::table('trx_batchjob_register')
    ->whereNotNull('index_olt')
    ->where('index_olt', '!=', '')
    ->limit(5)
    ->get(['nomor_internet', 'nama_pelanggan', 'kode_pop', 'index_olt', 'kode_w', 'media_akses'])
    ->toArray());
