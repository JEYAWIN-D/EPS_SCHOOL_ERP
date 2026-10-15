<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "--- 1. Testing Login for EPSS9790 with Password '15091998' ---\n";
$user = User::where('username', 'EPSS9790')->first();
if (!$user) {
    echo "User EPSS9790 not found!\n";
    exit(1);
}

echo "Found User: {$user->id} ({$user->name})\n";
echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
echo "Must Change Password: " . ($user->must_change_password ? 'true' : 'false') . "\n";

$request = Request::create('/login', 'POST', [
    'email' => 'EPSS9790',
    'password' => '15091998',
]);

$controller = $app->make(App\Http\Controllers\Auth\LoginController::class);
$response = $controller->login($request);

echo "Login response status: " . $response->getStatusCode() . "\n";
echo "Redirect URL: " . $response->getTargetUrl() . "\n";
if (session('errors')) {
    echo "Session errors: " . json_encode(session('errors')->all()) . "\n";
}

echo "\n--- 2. Checking Authenticated User and Permissions ---\n";
$authUser = Auth::user();
if ($authUser) {
    echo "Auth::user() authenticated: {$authUser->username} (ID: {$authUser->id})\n";
    echo "Role: " . $authUser->getRoleNames()->first() . "\n";
    echo "Can view dashboard? " . ($authUser->can('view dashboard') ? 'YES' : 'NO') . "\n";
    echo "Can view students? " . ($authUser->can('view students') ? 'YES' : 'NO') . "\n";
    echo "Can view academics? " . ($authUser->can('view academics') ? 'YES' : 'NO') . "\n";
    echo "Can view attendance? " . ($authUser->can('view attendance') ? 'YES' : 'NO') . "\n";
    echo "Can mark attendance? " . ($authUser->can('mark attendance') ? 'YES' : 'NO') . "\n";
    echo "Can view examinations? " . ($authUser->can('view examinations') ? 'YES' : 'NO') . "\n";
    echo "Can view employees? " . ($authUser->can('view employees') ? 'YES' : 'NO') . "\n";
    echo "Can manage rbac? " . ($authUser->can('manage rbac') ? 'YES' : 'NO') . "\n";
} else {
    echo "Auth::user() is NULL! Login failed.\n";
}

echo "\n--- 3. Testing Middleware on Protected Route (GET /dashboard) ---\n";
$dashRequest = Request::create('/dashboard', 'GET');
$dashRequest->setUserResolver(fn() => $authUser);

$ensureStaff = new App\Http\Middleware\EnsureStaffUser();
$mwResponse = $ensureStaff->handle($dashRequest, function($req) {
    return response('Dashboard Access Granted', 200);
});

echo "EnsureStaffUser Middleware status: " . $mwResponse->getStatusCode() . "\n";
if ($mwResponse->isRedirect()) {
    echo "Redirected to: " . $mwResponse->getTargetUrl() . "\n";
} else {
    echo "Content: " . $mwResponse->getContent() . "\n";
}

