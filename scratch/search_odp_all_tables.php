<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SEARCH TABLES WITH ODP IN NAME OR COLUMNS ===\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    foreach ($t as $k => $tableName) {
        $cols = DB::select("DESCRIBE `$tableName`");
        $foundCols = [];
        foreach ($cols as $col) {
            if (stripos($col->Field, 'odp') !== false) {
                $foundCols[] = $col->Field;
            }
        }
        if (!empty($foundCols) || stripos($tableName, 'odp') !== false) {
            echo "Table: $tableName | Columns: " . implode(', ', $foundCols) . "\n";
            $count = DB::table($tableName)->count();
            echo "  Row count: $count\n";
            if ($count > 0 && $count <= 10) {
                print_r(DB::table($tableName)->get()->toArray());
            }
        }
    }
}
