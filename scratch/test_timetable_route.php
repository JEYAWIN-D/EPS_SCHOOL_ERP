<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::where('username', 'EPSS9790')->firstOrFail();
echo "Found User #{$user->id} ({$user->name}), Employee ID: {$user->employee_id}\n";

$request = Illuminate\Http\Request::create('/academics/timetable', 'GET');
$request->setUserResolver(fn() => $user);
Auth::setUser($user);

$controller = $app->make(App\Http\Controllers\Admin\AcademicController::class);
$response = $controller->timetable($request);

echo "View name: " . $response->name() . "\n";

// Render view to verify Blade compilation & data bindings
$html = $response->render();
echo "Rendered HTML length: " . strlen($html) . " bytes\n";
echo "SUCCESS! /academics/timetable compiled and rendered completely!\n";
