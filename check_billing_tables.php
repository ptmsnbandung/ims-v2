<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$financeUser = Pengguna::whereHas('level', function($q) {
    $q->where('nama_level', 'LIKE', '%finance%');
})->first() ?: Pengguna::first();

Auth::setUser($financeUser);

// Try dispatching request with slashes in kode_billing
$req = Request::create('/finance/api/billing-layanan/INV/1010222/01/2023', 'GET');
$req->setUserResolver(fn() => $financeUser);
$res = $app->handle($req);

echo "Status Code for INV/1010222/01/2023: " . $res->getStatusCode() . "\n";
echo "Response: " . substr($res->getContent(), 0, 100) . "\n";

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $arr = (array)$t;
    $tbl = reset($arr);
    if (str_contains($tbl, 'billing') || str_contains($tbl, 'reg')) {
        echo "Table: $tbl\n";
    }
}




















