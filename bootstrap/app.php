<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__));

// On Vercel (serverless), the filesystem is read-only except for /tmp.
// Redirect Laravel's storage path to /tmp/storage so logs, views cache, etc. work.
if ($storagePath = env('LARAVEL_STORAGE_PATH')) {
    $app->useStoragePath($storagePath);
}

return $app
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'scope.user'         => \App\Http\Middleware\ScopeToUser::class,
            'portal.access'      => \App\Http\Middleware\CheckPortalAccess::class,
            'staff.access'       => \App\Http\Middleware\EnsureStaffUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. You do not have the required permission.'], 403);
            }
            return response()->view('errors.403', ['exception' => $e], 403);
        });
    })->create();
