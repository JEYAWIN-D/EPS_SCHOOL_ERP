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

        // 1. Safe TokenMismatch (CSRF Expiry) Handling
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch or session expired. Please refresh the page.',
                ], 419);
            }
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Your session expired. Please resubmit the form.');
        });

        // 2. Eloquent Model Not Found
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Requested record could not be found.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        // 3. Database Query Exception Handling (Mask internal credentials & schema in production)
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            $correlationId = 'EPS-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
            \Illuminate\Support\Facades\Log::error("Database QueryException [{$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'sql'            => $e->getSql(),
                'url'            => $request->fullUrl(),
                'user_id'        => \Illuminate\Support\Facades\Auth::id(),
            ]);

            if (config('app.debug')) {
                return null; // Fall through to standard debug display in local development
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status'         => 'error',
                    'message'        => 'A database error occurred while processing your request. Please contact support with this reference.',
                    'correlation_id' => $correlationId,
                ], 500);
            }

            return response()->view('errors.500', [
                'correlationId' => $correlationId,
                'message'       => 'A database error occurred while processing your request.',
            ], 500);
        });

        // 4. General Production 500 Exception with Correlation ID
        $exceptions->render(function (\Throwable $e, $request) {
            // Let core exceptions (redirects, validation, auth, 404, 403) pass through to standard handlers
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return null;
            }

            $correlationId = 'EPS-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
            \Illuminate\Support\Facades\Log::error("Unhandled Server Exception [{$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'file'           => $e->getFile() . ':' . $e->getLine(),
                'url'            => $request->fullUrl(),
                'user_id'        => \Illuminate\Support\Facades\Auth::id(),
            ]);

            if (config('app.debug')) {
                return null; // Fall through to Ignition / debug trace
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status'         => 'error',
                    'message'        => 'An internal server error occurred. Please contact IT support with this reference.',
                    'correlation_id' => $correlationId,
                ], 500);
            }

            return response()->view('errors.500', [
                'correlationId' => $correlationId,
                'message'       => 'An unexpected internal error occurred.',
            ], 500);
        });
    })->create();
