<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ALL ROWS IN m_odp ===\n";
$mOdps = DB::table('m_odp')->get();
foreach ($mOdps as $odp) {
    echo json_encode($odp, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== DISTINCT ODP NAMES IN trx_batchjob_register ===\n";
if (Schema::hasTable('trx_batchjob_register')) {
    $cols = Schema::getColumnListing('trx_batchjob_register');
    $odpCol = null;
    foreach (['odp', 'kode_odp', 'nama_odp', 'name_odp'] as $c) {
        if (in_array($c, $cols)) {
            $odpCol = $c;
            break;
        }
    }
    if ($odpCol) {
        $distinct = DB::table('trx_batchjob_register')->whereNotNull($odpCol)->where($odpCol, '!=', '')->distinct()->pluck($odpCol);
        echo "Found column: $odpCol. Count: " . count($distinct) . "\n";
        print_r($distinct->take(20)->toArray());
    } else {
        echo "No obvious ODP column in trx_batchjob_register. Columns:\n";
        print_r($cols);
    }
}

echo "\n=== ODP IN gis_elements ===\n";
if (Schema::hasTable('gis_elements')) {
    $gisOdps = DB::table('gis_elements')->where('element_type', 'odp')->orWhere('name', 'like', '%ODP%')->get();
    echo "Count: " . count($gisOdps) . "\n";
    foreach ($gisOdps->take(10) as $g) {
        echo "- ID: {$g->id}, Name: {$g->name}, Lat: {$g->latitude}, Lng: {$g->longitude}\n";
    }
}
