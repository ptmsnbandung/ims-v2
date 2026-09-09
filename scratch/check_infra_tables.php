<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== M_ODP ALL FIELDS ===\n";
print_r(DB::table('m_odp')->get()->toArray());

echo "=== M_PON ===\n";
if (Schema::hasTable('m_pon')) {
    print_r(DB::table('m_pon')->get()->toArray());
}

echo "=== M_OLT ===\n";
if (Schema::hasTable('m_olt')) {
    print_r(DB::table('m_olt')->get()->toArray());
}

echo "=== M_POP ===\n";
if (Schema::hasTable('m_pop')) {
    print_r(DB::table('m_pop')->get()->toArray());
}
