<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DISTINCT INDEX_OLT IN TRX_BATCHJOB_REGISTER ===\n";
$indexOlts = DB::table('trx_batchjob_register')
    ->whereNotNull('index_olt')
    ->where('index_olt', '!=', '')
    ->select('index_olt', 'status_reg', DB::raw('count(*) as total'))
    ->groupBy('index_olt', 'status_reg')
    ->limit(30)
    ->get();

print_r($indexOlts->toArray());

echo "\n=== OCCUPIED INDEX_OLT (STATUS_REG != 23, 23.1) ===\n";
$occupied = DB::table('trx_batchjob_register')
    ->whereNotNull('index_olt')
    ->where('index_olt', '!=', '')
    ->whereNotIn('status_reg', ['23', '23.1', '15'])
    ->pluck('index_olt')
    ->unique();

print_r($occupied->toArray());
