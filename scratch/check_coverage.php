<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== M_ODP COLUMNS ===\n";
print_r(DB::select("DESCRIBE m_odp"));

echo "=== SAMPLE M_ODP ROWS ===\n";
print_r(DB::table('m_odp')->limit(3)->get());
