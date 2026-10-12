<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enterprise Authorization Middleware:
 * Ensures non-staff users (e.g. students and parents) cannot access internal
 * administrative ERP routes or records.
 */
class EnsureStaffUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If the user has a student or parent role and lacks any staff/administrative role,
        // prevent access to internal administrative routes.
        $isPurePortalUser = $user->hasAnyRole(['parent', 'student']) && 
            !$user->hasAnyRole([
                'super_admin', 'admin', 'owner', 'principal', 'vice_principal',
                'accountant', 'teacher', 'class_teacher', 'subject_teacher', 'hod',
                'librarian', 'transport_manager', 'warden', 'hr_manager',
                'receptionist', 'inventory_manager', 'event_coordinator',
                'alumni_coordinator', 'itadmin'
            ]);

        if ($isPurePortalUser) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. Administrative access is restricted to authorized school staff.'
                ], 403);
            }

            // Redirect parents and students to their designated portal dashboard
            if ($user->hasRole('parent')) {
                return redirect()->route('portal.parent.dashboard')->with('error', 'Access to school administrative functions is restricted.');
            }

            if ($user->hasRole('student')) {
                return redirect()->route('portal.student.dashboard')->with('error', 'Access to school administrative functions is restricted.');
            }

            abort(403, 'Unauthorized access to school administration.');
        }

        return $next($request);
    }
}
