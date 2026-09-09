<?php

require 'c:/laragon/www/ims_router/vendor/autoload.php';
$app = require_once 'c:/laragon/www/ims_router/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$initialReq = Illuminate\Http\Request::create('/', 'GET');
$app->instance('request', $initialReq);

$user = App\Models\User::first();
if ($user) {
    Illuminate\Support\Facades\Auth::login($user);
}

use App\Http\Controllers\Teknik\TeknikController;
use Illuminate\Http\Request;

echo "=== TESTING PENDAFTARAN VIEW RENDERING ===\n";

$controller = $app->make(TeknikController::class);
$req = Request::create('/teknik/pendaftaran', 'GET');
$req->setUserResolver(fn() => $user);
$app->instance('request', $req);

$view = $controller->pendaftaran($req);
$html = $view->render();

// Check if x-data has the function call
$hasCorrectXData = strpos($html, 'x-data="pendaftaranWorkflowComponent()"') !== false;
// Check if script tag exists
$hasScriptTag = strpos($html, 'function pendaftaranWorkflowComponent()') !== false;
// Check if raw JS leaked into root div attribute
$hasLeakedJsInDiv = strpos($html, '<div class="space-y-6"\n     x-data="{') !== false;

echo "Checks:\n";
echo "- Has x-data=\"pendaftaranWorkflowComponent()\": " . ($hasCorrectXData ? "PASS" : "FAIL") . "\n";
echo "- Has function pendaftaranWorkflowComponent() in script: " . ($hasScriptTag ? "PASS" : "FAIL") . "\n";
echo "- No raw JS leaked in x-data attribute: " . (!$hasLeakedJsInDiv ? "PASS" : "FAIL") . "\n";

// Let's also check if the table / tabs / stats render cleanly
$hasRegistrationTitle = strpos($html, 'Registration') !== false;
$hasTable = strpos($html, 'Antrean Pelanggan') !== false || strpos($html, 'table') !== false;

echo "- Has Registration title: " . ($hasRegistrationTitle ? "PASS" : "FAIL") . "\n";
echo "- Has Table: " . ($hasTable ? "PASS" : "FAIL") . "\n";

echo "=== FINISHED ===\n";
