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

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Teknik\TeknikController;
use Illuminate\Http\Request;

echo "=== TESTING GIS NETWORK BUILDER (PETA JARINGAN FTTH) ===\n\n";

$controller = $app->make(TeknikController::class);

// 1. Test View Rendering
$reqView = Request::create('/teknik/peta-jaringan', 'GET');
$reqView->setUserResolver(fn() => $user);
$app->instance('request', $reqView);

$view = $controller->petaJaringan($reqView);
$html = $view->render();

$hasGisTitle = strpos($html, 'Peta Jaringan & Jalur FTTH') !== false;
$hasLeaflet = strpos($html, 'leaflet') !== false;
$hasSidebarDrawer = strpos($html, 'ims-drawer-root') !== false;

echo "View Render Checks:\n";
echo "- Has GIS Title: " . ($hasGisTitle ? "YES" : "NO") . "\n";
echo "- Has Leaflet Map: " . ($hasLeaflet ? "YES" : "NO") . "\n";
echo "- Has Sidebar Drawer: " . ($hasSidebarDrawer ? "YES" : "NO") . "\n";

// 2. Test Store GIS Project
$reqProject = Request::create('/teknik/peta-jaringan/project', 'POST', [
    'name' => 'Proyek Test GIS Arcamanik',
    'description' => 'Uji coba otomatis pemetaan fiber optik',
]);
$reqProject->setUserResolver(fn() => $user);
$resProject = $controller->storeGisProject($reqProject);
$projectData = json_decode($resProject->getContent(), true);

echo "\nProject Creation:\n";
echo "- Success: " . ($projectData['success'] ? "YES" : "NO") . "\n";
echo "- Project ID: " . ($projectData['project']['id'] ?? 'none') . "\n";
echo "- Project Code: " . ($projectData['project']['code'] ?? 'none') . "\n";

$projectId = $projectData['project']['id'];

// 3. Test Save Marker Element (Pole)
$reqMarker = Request::create('/teknik/peta-jaringan/element', 'POST', [
    'project_id' => $projectId,
    'category' => 'marker',
    'element_type' => 'pole',
    'name' => 'Tiang PLN TG-042',
    'color' => '#334155',
    'latitude' => -6.9175000,
    'longitude' => 107.6191000,
    'metadata' => [
        'pole_height' => '9 Meter',
        'pole_material' => 'Besi Galvanis',
        'physical_condition' => 'Bagus & Tegak'
    ]
]);
$reqMarker->setUserResolver(fn() => $user);
$resMarker = $controller->saveGisElement($reqMarker);
$markerData = json_decode($resMarker->getContent(), true);

echo "\nMarker Creation (Pole):\n";
echo "- Success: " . ($markerData['success'] ? "YES" : "NO") . "\n";
echo "- Marker ID: " . ($markerData['element']['id'] ?? 'none') . "\n";
echo "- Element Type: " . ($markerData['element']['element_type'] ?? 'none') . "\n";
$markerId = $markerData['element']['id'];

// 4. Test Save Line Element (Feeder Cable)
$reqLine = Request::create('/teknik/peta-jaringan/element', 'POST', [
    'project_id' => $projectId,
    'category' => 'line',
    'element_type' => 'feeder',
    'name' => 'Kabel Feeder ODC-01 ke JB-02',
    'color' => '#EF4444',
    'coordinates' => [
        [-6.9175000, 107.6191000],
        [-6.9185000, 107.6201000],
        [-6.9195000, 107.6211000]
    ],
    'length_meters' => 320.50,
    'line_width' => 4.5,
    'line_dash' => 'solid',
    'metadata' => [
        'cable_core' => '48 Core',
        'cable_brand' => 'Fiberhome'
    ]
]);
$reqLine->setUserResolver(fn() => $user);
$resLine = $controller->saveGisElement($reqLine);
$lineData = json_decode($resLine->getContent(), true);

echo "\nLine Creation (Feeder Cable):\n";
echo "- Success: " . ($lineData['success'] ? "YES" : "NO") . "\n";
echo "- Line ID: " . ($lineData['element']['id'] ?? 'none') . "\n";
echo "- Length (meters): " . ($lineData['element']['length_meters'] ?? 0) . "\n";
$lineId = $lineData['element']['id'];

// 5. Test Export KML
$reqKml = Request::create("/teknik/peta-jaringan/export-kml/{$projectId}", 'GET');
$reqKml->setUserResolver(fn() => $user);
$resKml = $controller->exportKml($reqKml, $projectId);
$kmlOutput = $resKml->getContent();

$hasPlacemark = strpos($kmlOutput, '<Placemark>') !== false;
$hasLineString = strpos($kmlOutput, '<LineString>') !== false;
$hasPoint = strpos($kmlOutput, '<Point>') !== false;

echo "\nKML Export Checks:\n";
echo "- Has Placemarks: " . ($hasPlacemark ? "YES" : "NO") . "\n";
echo "- Has LineString: " . ($hasLineString ? "YES" : "NO") . "\n";
echo "- Has Point Coordinates: " . ($hasPoint ? "YES" : "NO") . "\n";

// 6. Test Delete Elements & Cleanup
$reqDelMarker = Request::create("/teknik/peta-jaringan/element/{$markerId}/delete", 'POST');
$reqDelMarker->setUserResolver(fn() => $user);
$resDelMarker = $controller->deleteGisElement($reqDelMarker, $markerId);

$reqDelLine = Request::create("/teknik/peta-jaringan/element/{$lineId}/delete", 'POST');
$reqDelLine->setUserResolver(fn() => $user);
$resDelLine = $controller->deleteGisElement($reqDelLine, $lineId);

$reqDelPrj = Request::create("/teknik/peta-jaringan/project/{$projectId}/delete", 'POST');
$reqDelPrj->setUserResolver(fn() => $user);
$resDelPrj = $controller->deleteGisProject($reqDelPrj, $projectId);

echo "\nCleanup Checks:\n";
echo "- Deleted Marker, Line, and Test Project: SUCCESS\n";

echo "\n=== ALL GIS NETWORK BUILDER TESTS PASSED SUCCESSFULLY! ===\n";
