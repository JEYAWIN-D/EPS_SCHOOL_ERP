<?php

namespace App\Modules\Rbac\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Rbac\Models\AccountTargetConfig;
use App\Modules\Rbac\Services\RbacAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AccountConfigController extends Controller
{
    /**
     * View account targets versus actual active accounts.
     */
    public function index()
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('manage settings')) {
            abort(403, 'Unauthorized to view account configurations.');
        }

        $roles = Role::orderBy('name')->get();
        $targets = AccountTargetConfig::all()->keyBy('role_name');

        // Compile metrics
        $metrics = [];
        foreach ($roles as $role) {
            $targetObj = $targets->get($role->name);
            $targetCount = $targetObj ? $targetObj->target_count : 1;
            $actualCount = User::role($role->name)->where('is_active', true)->count();
            $inactiveCount = User::role($role->name)->where('is_active', false)->count();

            $metrics[] = [
                'role' => $role,
                'target_obj' => $targetObj,
                'target_count' => $targetCount,
                'actual_count' => $actualCount,
                'inactive_count' => $inactiveCount,
                'variance' => $actualCount - $targetCount,
                'status' => $actualCount === $targetCount ? 'optimal' : ($actualCount > $targetCount ? 'surplus' : 'deficit'),
                'notes' => $targetObj?->notes ?? 'Standard operational role target',
            ];
        }

        return view('rbac.config.targets', compact('metrics'));
    }

    /**
     * Update target configurations.
     */
    public function update(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac')) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'targets' => ['required', 'array'],
            'targets.*.role_name' => ['required', 'string', 'exists:roles,name'],
            'targets.*.target_count' => ['required', 'integer', 'min:0', 'max:500'],
            'targets.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['targets'] as $item) {
            AccountTargetConfig::updateOrCreate(
                ['role_name' => $item['role_name']],
                [
                    'target_count' => $item['target_count'],
                    'notes' => $item['notes'] ?? null,
                    'updated_by' => $actor->id,
                ]
            );
        }

        RbacAuditService::log(
            action: 'ACCOUNT_TARGETS_UPDATED',
            modelType: 'AccountTargetConfig',
            modelId: null,
            newValues: $validated['targets'],
            actor: $actor
        );

        return back()->with('success', 'Account targets updated successfully. Note: Account limits are operational goals and do not alter existing user accounts.');
    }
}
