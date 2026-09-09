<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VIEW_BILLING_REG SAMPLE ===\n";
$sampleReg = DB::table('view_billing_reg')->first();
if ($sampleReg) {
    print_r($sampleReg);
} else {
    echo "Empty view_billing_reg\n";
}

echo "\n=== TRX_BILLING_REGISTRASI SAMPLE ===\n";
$sampleBillingReg = DB::table('trx_billing_registrasi')->first();
if ($sampleBillingReg) {
    print_r($sampleBillingReg);
} else {
    echo "Empty trx_billing_registrasi\n";
}

echo "\n=== TRX_BILLING_REGISTRASI_DETAIL SAMPLE ===\n";
$sampleBillingRegDet = DB::table('trx_billing_registrasi_detail')->first();
if ($sampleBillingRegDet) {
    print_r($sampleBillingRegDet);
} else {
    echo "Empty trx_billing_registrasi_detail\n";
}
