<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
$tableNames = array_map(function($t) { return array_values((array)$t)[0]; }, $tables);
if (Schema::hasTable('view_aktivasi')) {
    print_r(Schema::getColumnListing('view_aktivasi'));
    print_r(DB::table('view_aktivasi')->first());
}







