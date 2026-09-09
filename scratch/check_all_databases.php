<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dbs = DB::select('SHOW DATABASES');
print_r($dbs);

foreach ($dbs as $db) {
    $dbName = $db->Database;
    if (in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    $tables = DB::select("SHOW TABLES FROM `$dbName`");
    echo "\n=== DATABASE: $dbName (" . count($tables) . " tables) ===\n";
    foreach ($tables as $t) {
        foreach ($t as $k => $tbl) {
            if (stripos($tbl, 'odp') !== false) {
                echo "  Table with 'odp': $tbl in $dbName\n";
                $rows = DB::select("SELECT * FROM `$dbName`.`$tbl` LIMIT 10");
                print_r($rows);
            }
        }
    }
}
