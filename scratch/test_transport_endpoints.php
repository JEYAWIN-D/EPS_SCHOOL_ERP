<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=================================================================\n";
echo "TESTING TRANSPORT BLADE RENDERING & HTTP ENDPOINTS\n";
echo "=================================================================\n\n";

$admin = User::where('email', 'admin@schoolerp.in')->first() ?: User::find(1);
Auth::login($admin);

$endpoints = [
    'Dashboard'           => '/transport',
    'Routes List'         => '/transport/routes',
    'Van I Route Details' => '/transport/routes/4',
    'Van V Route Details' => '/transport/routes/8',
    'Stops List'          => '/transport/stops',
    'Stops Missing Timing'=> '/transport/stops?timing=missing',
    'Students Allocation' => '/transport/students',
];

$allPassed = true;

foreach ($endpoints as $name => $uri) {
    echo "Testing {$name} ({$uri})... ";
    try {
        $request = Illuminate\Http\Request::create($uri, 'GET');
        $response = $app->handle($request);
        $status = $response->getStatusCode();
        if ($status === 200) {
            echo "PASS (HTTP 200, length: " . strlen($response->getContent()) . " bytes)\n";
        } else {
            echo "FAIL (HTTP {$status})\n";
            $allPassed = false;
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
        $allPassed = false;
    }
}

echo "\n" . ($allPassed ? "🎉 ALL ENDPOINTS RENDERED PERFECTLY!" : "❌ SOME ENDPOINTS ENCOUNTERED ERRORS") . "\n";
exit($allPassed ? 0 : 1);
