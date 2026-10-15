<?php

namespace App\Modules\Rbac\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RbacAuditController extends Controller
{
    /**
     * View RBAC security & audit logs.
     */
    public function index(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('view audit logs')) {
            abort(403, 'Unauthorized to view security logs.');
        }

        $query = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('audit_logs.*', 'users.name as actor_name', 'users.email as actor_email')
            ->where(function ($q) {
                $q->where('audit_logs.module', 'RBAC')
                  ->orWhere('audit_logs.action', 'ilike', '%ROLE%')
                  ->orWhere('audit_logs.action', 'ilike', '%USER%')
                  ->orWhere('audit_logs.action', 'ilike', '%PERMISSION%')
                  ->orWhere('audit_logs.action', 'ACCESS_DENIED');
            })
            ->latest('audit_logs.created_at');

        if ($action = $request->input('action')) {
            $query->where('audit_logs.action', $action);
        }

        if ($userFilter = $request->input('user_id')) {
            $query->where('audit_logs.user_id', $userFilter);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('audit_logs.created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('audit_logs.created_at', '<=', $to);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Get distinct actions for dropdown filter
        $availableActions = DB::table('audit_logs')
            ->where('module', 'RBAC')
            ->distinct()
            ->pluck('action');

        return view('rbac.audit.index', compact('logs', 'availableActions'));
    }
}
