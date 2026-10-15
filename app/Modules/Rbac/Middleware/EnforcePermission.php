<?php

namespace App\Modules\Rbac\Middleware;

use App\Modules\Rbac\Services\PermissionResolverService;
use App\Modules\Rbac\Services\RbacAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforcePermission
{
    protected PermissionResolverService $resolver;

    public function __construct(PermissionResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        // 1. Must be authenticated
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        }

        // 2. Must be active
        if (!$user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been deactivated. Please contact your administrator.'], 403);
            }
            return redirect()->route('login')->withErrors(['email' => 'Your account is deactivated. Contact the School Administration.']);
        }

        // 3. Super Admin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // 4. Verify any of the required permissions
        $granted = false;
        foreach ($permissions as $permission) {
            if ($this->resolver->hasPermission($user, $permission)) {
                $granted = true;
                break;
            }
        }

        if (!$granted) {
            // Log authorization rejection
            RbacAuditService::log(
                action: 'ACCESS_DENIED',
                modelType: 'Permission',
                modelId: null,
                oldValues: ['required_permissions' => $permissions, 'route' => $request->path()],
                actor: $user
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. You do not possess the required permission.',
                    'required' => $permissions,
                ], 403);
            }

            abort(403, 'Unauthorized access: You do not have sufficient permissions to access this feature.');
        }

        return $next($request);
    }
}
