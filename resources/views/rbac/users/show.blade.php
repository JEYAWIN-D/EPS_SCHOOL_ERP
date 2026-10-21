@extends('layouts.app')

@section('title', 'User Permission Summary — RBAC')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <a href="{{ route('rbac.users.index') }}" class="hover:text-indigo-600 transition">User Management</a>
        <span>/</span>
        <span class="text-indigo-600">User Profile & Permissions</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $user->name }}</h1>
      <p class="text-sm text-slate-500 mt-0.5">Summary of effective authorization, record boundaries, and audit history.</p>
    </div>
    <div class="flex items-center gap-2.5 flex-wrap">
      {{-- Revoke Sessions Form --}}
      <form method="POST" action="{{ route('rbac.users.revoke-sessions', $user->id) }}" onsubmit="return confirm('Revoke all active web sessions for {{ $user->name }}? The user will be required to log in again.');">
        @csrf
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition shadow-xs">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Revoke Sessions
        </button>
      </form>

      {{-- Edit Account Link --}}
      <a href="{{ route('rbac.users.edit', $user->id) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Account
      </a>

      <a href="{{ route('rbac.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs">
        &larr; Users List
      </a>
    </div>
  </div>

  {{-- Account Summary Header Card --}}
  <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center font-black text-xl flex-shrink-0 {{ $user->isSuperAdmin() ? 'bg-purple-100 text-purple-700 ring-4 ring-purple-50' : 'bg-slate-100 text-slate-700' }}">
          {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
            @if($user->is_active)
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
              </span>
            @else
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Deactivated
              </span>
            @endif
          </div>
          <div class="flex items-center gap-4 text-xs text-slate-500 mt-1 flex-wrap">
            <span>Email: <strong class="text-slate-800">{{ $user->email }}</strong></span>
            <span>&bull;</span>
            <span>Mobile: <strong class="text-slate-800">{{ $user->mobile ?? '—' }}</strong></span>
            <span>&bull;</span>
            <span>Username: <strong class="text-slate-800">{{ $user->username ?? '—' }}</strong></span>
            @if($user->employee)
              <span>&bull;</span>
              <span>Employee Code: <strong class="text-indigo-600">{{ $user->employee->employee_code }}</strong></span>
            @endif
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        @foreach($user->roles as $role)
          <span class="px-3 py-1.5 rounded-lg text-xs font-bold
            {{ $role->name === 'super_admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
            Role: {{ ucwords(str_replace('_', ' ', $role->name)) }}
          </span>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Two Column Grid: Data Scopes & Field Restrictions --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Data Scopes Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Record-Level Access Scope
        </h3>
        <span class="text-xs text-slate-400">Backend Enforced</span>
      </div>

      @if($user->isSuperAdmin())
        <div class="p-3 rounded-lg bg-purple-50 text-purple-800 text-xs">
          <strong>Universal Scope:</strong> Super Admin has unconstrained record visibility across all schools, academic years, classes, and departments.
        </div>
      @elseif($user->dataScopes->isEmpty())
        <div class="p-3 rounded-lg bg-slate-50 text-slate-600 text-xs">
          <strong>Default Role Scope:</strong> Standard institutional boundary associated with user's primary designation.
        </div>
      @else
        <div class="space-y-2">
          @foreach($user->dataScopes as $scope)
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
              <div>
                <span class="font-bold text-slate-700 uppercase">{{ str_replace('_', ' ', $scope->scope_type) }}</span>
                <p class="text-slate-500 mt-0.5">Value: <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-800">{{ $scope->scope_value ?? 'All' }}</code></p>
              </div>
              <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold">Active</span>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Field-Level Permissions Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Field-Level Masking & Restrictions
        </h3>
        <span class="text-xs text-slate-400">PII / Financial Shield</span>
      </div>

      <div class="space-y-2 text-xs">
        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
          <span class="font-medium text-slate-700">Staff Salary & Payroll</span>
          <span class="font-bold {{ $user->isSuperAdmin() ? 'text-emerald-700' : 'text-amber-700' }}">
            {{ $user->getEffectiveFieldPermission('employees', 'salary') }}
          </span>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
          <span class="font-medium text-slate-700">Bank Account Details</span>
          <span class="font-bold {{ $user->isSuperAdmin() ? 'text-emerald-700' : 'text-amber-700' }}">
            {{ $user->getEffectiveFieldPermission('employees', 'bank_account_no') }}
          </span>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
          <span class="font-medium text-slate-700">Parent Mobile Numbers</span>
          <span class="font-bold {{ $user->isSuperAdmin() ? 'text-emerald-700' : 'text-emerald-700' }}">
            {{ $user->getEffectiveFieldPermission('students', 'father_mobile') }}
          </span>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
          <span class="font-medium text-slate-700">Aadhaar & Identity Numbers</span>
          <span class="font-bold {{ $user->isSuperAdmin() ? 'text-emerald-700' : 'text-amber-700' }}">
            {{ $user->getEffectiveFieldPermission('students', 'aadhaar_number') }}
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- Effective Permission Matrix Summary --}}
  <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
      <div>
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Effective Module Permissions Matrix</h3>
        <p class="text-xs text-slate-500 mt-0.5">Calculated from role assignments and direct system grants.</p>
      </div>
      <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700">
        {{ count($effectivePermissions) }} Total Permissions
      </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($moduleCatalog as $modKey => $modData)
        @php
          $matchedActions = [];
          foreach($modData['default_actions'] as $act) {
            $permString = "{$modKey}:{$act}";
            $legacyString = "{$act} {$modKey}";
            $legacyManage = "manage {$modKey}";
            if ($user->isSuperAdmin() || in_array($permString, $effectivePermissions) || in_array($legacyString, $effectivePermissions) || in_array($legacyManage, $effectivePermissions)) {
              $matchedActions[] = $act;
            }
          }
        @endphp
        <div class="p-3.5 rounded-xl border {{ count($matchedActions) > 0 ? 'border-slate-200 bg-white' : 'border-slate-100 bg-slate-50/50 opacity-60' }}">
          <div class="flex items-center justify-between mb-2">
            <span class="font-bold text-slate-800 text-xs truncate">{{ $modData['name'] }}</span>
            <span class="text-[11px] font-bold {{ count($matchedActions) > 0 ? 'text-indigo-600' : 'text-slate-400' }}">
              {{ count($matchedActions) }}/{{ count($modData['default_actions']) }}
            </span>
          </div>
          <div class="flex flex-wrap gap-1">
            @forelse($matchedActions as $act)
              <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                {{ $act }}
              </span>
            @empty
              <span class="text-[11px] text-slate-400 italic">No access</span>
            @endforelse
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Security & Access Audit History --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Account Security & Audit Trail</h3>
      <span class="text-xs text-slate-500">Recent 25 events</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[11px]">
          <tr>
            <th class="py-2.5 px-4">Date / Time</th>
            <th class="py-2.5 px-4">Action</th>
            <th class="py-2.5 px-4">IP Address</th>
            <th class="py-2.5 px-4">Details</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($auditLogs as $log)
            <tr class="hover:bg-slate-50/50 transition">
              <td class="py-2.5 px-4 font-mono text-slate-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}</td>
              <td class="py-2.5 px-4">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold
                  {{ str_contains($log->action, 'DELETED') || str_contains($log->action, 'DENIED') ? 'bg-rose-100 text-rose-800' : 
                    (str_contains($log->action, 'CREATED') ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800') }}">
                  {{ $log->action }}
                </span>
              </td>
              <td class="py-2.5 px-4 font-mono text-slate-500">{{ $log->ip_address }}</td>
              <td class="py-2.5 px-4 text-slate-600 font-mono text-[11px] truncate max-w-xs">{{ $log->new_values ?? $log->old_values ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="py-6 text-center text-slate-400">No security audit records logged for this account yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
