@extends('layouts.app')

@section('title', 'User Management — RBAC')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Access Control</span>
        <span>/</span>
        <span class="text-indigo-600">User Management</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System & User Accounts</h1>
      <p class="text-sm text-slate-500 mt-1">Manage institutional users, role assignments, data access boundaries, and account credentials.</p>
    </div>
    <div class="flex items-center gap-2.5 flex-wrap">
      <a href="{{ route('rbac.users.export') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-xs transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export CSV
      </a>
      <a href="{{ route('rbac.matrix.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-xs transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Permission Matrix
      </a>
      <a href="{{ route('rbac.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add New User
      </a>
    </div>
  </div>

  {{-- Metrics & Account Target Redundancy Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Accounts</span>
        <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </span>
      </div>
      <p class="text-2xl font-black text-slate-900 mt-2">{{ $users->total() }}</p>
      <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
        </span>
        <span>&bull;</span>
        <span>Filtered view</span>
      </div>
    </div>

    {{-- Super Admin Redundancy Card --}}
    @php
      $saActual = $roleCounts['super_admin'] ?? 0;
      $saTarget = $accountTargets['super_admin']->target_count ?? 2;
    @endphp
    <div class="bg-white rounded-xl border {{ $saActual < $saTarget ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' }} p-4 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Super Admins</span>
        <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $saActual >= $saTarget ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-800' }}">
          Target: {{ $saTarget }}
        </span>
      </div>
      <p class="text-2xl font-black text-slate-900 mt-2">{{ $saActual }}</p>
      <p class="text-xs {{ $saActual < $saTarget ? 'text-amber-700 font-semibold' : 'text-slate-500' }} mt-2">
        {{ $saActual < $saTarget ? '⚠️ 1 account deficit below target' : '✓ Full redundancy target met' }}
      </p>
    </div>

    {{-- Admin Target Card --}}
    @php
      $admActual = $roleCounts['admin'] ?? 0;
      $admTarget = $accountTargets['admin']->target_count ?? 2;
    @endphp
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Admins</span>
        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-700">
          Target: {{ $admTarget }}
        </span>
      </div>
      <p class="text-2xl font-black text-slate-900 mt-2">{{ $admActual }}</p>
      <p class="text-xs text-slate-500 mt-2">
        {{ $admActual >= $admTarget ? '✓ Target fulfilled' : 'Target: ' . $admTarget . ' accounts' }}
      </p>
    </div>

    {{-- Security Audit & Status --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Configured Roles</span>
        <a href="{{ route('rbac.roles.index') }}" class="text-xs text-indigo-600 font-semibold hover:underline">Manage &rarr;</a>
      </div>
      <p class="text-2xl font-black text-slate-900 mt-2">{{ $allRoles->count() }}</p>
      <p class="text-xs text-slate-500 mt-2">
        <span>Across 22 distinct ERP modules</span>
      </p>
    </div>
  </div>

  {{-- Search & Filters --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
    <form method="GET" action="{{ route('rbac.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
      <div class="lg:col-span-2 relative">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Employee ID, name, email..." class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
      </div>
      <div>
        <select name="category" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          <option value="">All Categories</option>
          <option value="teaching" {{ request('category') === 'teaching' ? 'selected' : '' }}>Teaching Staff</option>
          <option value="non_teaching" {{ request('category') === 'non_teaching' ? 'selected' : '' }}>Non-Teaching Staff</option>
          <option value="system" {{ request('category') === 'system' ? 'selected' : '' }}>System Users</option>
        </select>
      </div>
      <div>
        <select name="department_id" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          <option value="">All Departments</option>
          @foreach($departments ?? [] as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
              {{ $dept->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <select name="role" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          <option value="">All Roles</option>
          @foreach($allRoles as $role)
            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
              {{ ucwords(str_replace('_', ' ', $role->name)) }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <select name="status" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          <option value="">All Statuses</option>
          <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active Only</option>
          <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive Only</option>
        </select>
      </div>
      <div class="lg:col-span-6 flex items-center justify-between pt-1 border-t border-slate-100">
        <div class="flex items-center gap-2">
          <select name="permission_status" class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-600 focus:outline-hidden">
            <option value="">All Authorization States</option>
            <option value="assigned" {{ request('permission_status') === 'assigned' ? 'selected' : '' }}>Roles Assigned</option>
            <option value="no_role" {{ request('permission_status') === 'no_role' ? 'selected' : '' }}>Pending Role Assignment</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-white transition">Apply Filters</button>
          <a href="{{ route('rbac.users.index') }}" class="px-3 py-2 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition">Reset</a>
        </div>
      </div>
    </form>
  </div>

  {{-- Users Table --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            <th class="py-3 px-4">Staff Member &amp; ID</th>
            <th class="py-3 px-4">Category &amp; Department</th>
            <th class="py-3 px-4">Contact</th>
            <th class="py-3 px-4">Assigned Role(s)</th>
            <th class="py-3 px-4">Account Status</th>
            <th class="py-3 px-4">Permission State</th>
            <th class="py-3 px-4">Created / Last Login</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($users as $user)
            @php
              $primaryRole = $user->roles->first()?->name ?? 'None';
              $isSuperAdmin = $user->isSuperAdmin();
              $emp = $user->employee;
              $empCode = $emp?->employee_code ?? ($user->username && str_starts_with($user->username, 'EMP') ? $user->username : ($user->employee_id ? 'EMP-#'.$user->employee_id : null));
              $empType = $emp?->employee_type ?? ($user->hasRole('teacher') ? 'teaching' : ($emp ? 'non_teaching' : 'system'));
            @endphp
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 {{ $isSuperAdmin ? 'bg-purple-100 text-purple-700 ring-2 ring-purple-300' : ($empType === 'teaching' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <a href="{{ route('rbac.users.show', $user->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition truncate block">
                        {{ $user->name }}
                      </a>
                      @if($empCode)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                          {{ $empCode }}
                        </span>
                      @endif
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5">
                      <span>ID: #{{ $user->id }}</span>
                      @if($user->username)
                        <span>&bull;</span>
                        <span class="font-mono text-slate-600">@ {{ $user->username }}</span>
                      @endif
                      @if($user->must_change_password)
                        <span>&bull;</span>
                        <span class="text-amber-600 font-semibold text-[10px] bg-amber-50 px-1 rounded">Temp Password</span>
                      @endif
                    </div>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 text-xs">
                @if($empType === 'teaching')
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Teaching Staff
                  </span>
                @elseif($empType === 'non_teaching')
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Non-Teaching Staff
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 mb-1">
                    System Account
                  </span>
                @endif
                <div class="font-semibold text-slate-800 truncate max-w-[160px]">
                  {{ $emp?->designation?->name ?? $emp?->designation ?? 'Staff Member' }}
                </div>
                <div class="text-slate-400 text-[11px] truncate max-w-[160px]">
                  {{ $emp?->department?->name ?? $emp?->department ?? 'General' }}
                </div>
              </td>
              <td class="py-3 px-4 text-xs">
                <div class="text-slate-800 font-medium">{{ $user->email }}</div>
                <div class="text-slate-400 mt-0.5">{{ $user->mobile ?? '—' }}</div>
              </td>
              <td class="py-3 px-4">
                @if($user->roles->isEmpty())
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                    <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Pending Role
                  </span>
                @else
                  <div class="flex flex-wrap gap-1 max-w-[180px]">
                    @foreach($user->roles as $role)
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold
                        {{ $role->name === 'super_admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 
                          ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                          ($role->name === 'principal' || $role->name === 'vice_principal' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 
                          ($role->name === 'teacher' || $role->name === 'class_teacher' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 
                          'bg-slate-100 text-slate-700 border border-slate-200'))) }}">
                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                      </span>
                    @endforeach
                  </div>
                @endif
              </td>
              <td class="py-3 px-4">
                @if($user->is_active)
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                  </span>
                @endif
              </td>
              <td class="py-3 px-4 text-xs">
                @if($isSuperAdmin)
                  <span class="inline-flex items-center gap-1 font-semibold text-purple-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Unrestricted
                  </span>
                @elseif($user->roles->isEmpty())
                  <span class="text-rose-600 font-medium text-[11px]">No access</span>
                @elseif($user->dataScopes->isNotEmpty())
                  <span class="text-indigo-600 font-medium text-[11px] flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Scoped ({{ $user->dataScopes->count() }})
                  </span>
                @else
                  <span class="text-slate-500 italic text-[11px]">Role Defaults</span>
                @endif
              </td>
              <td class="py-3 px-4 text-xs text-slate-500">
                <div class="font-medium text-slate-700">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</div>
                @if($user->last_login_at)
                  <div class="text-[11px] text-slate-400">Login: {{ $user->last_login_at->format('d M H:i') }}</div>
                @else
                  <div class="text-[11px] text-amber-600 font-medium">Never logged in</div>
                @endif
              </td>
              <td class="py-3 px-4 text-right">
                <div class="inline-flex items-center gap-1 justify-end">
                  {{-- View Profile & Permissions --}}
                  <a href="{{ route('rbac.users.show', $user->id) }}" class="btn-icon p-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition" title="View Permission Summary & Audit Logs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </a>

                  {{-- Assign / Edit Roles --}}
                  <a href="{{ route('rbac.users.edit', $user->id) }}" class="btn-icon p-1.5 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-slate-100 transition" title="Assign / Edit Roles & Scopes">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </a>

                  {{-- Toggle Status --}}
                  <form method="POST" action="{{ route('rbac.users.toggle-status', $user->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} {{ $user->name }}?');">
                    @csrf
                    <button type="submit" class="btn-icon p-1.5 rounded-lg {{ $user->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition" title="{{ $user->is_active ? 'Deactivate / Suspend Account' : 'Activate Account' }}">
                      @if($user->is_active)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                      @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      @endif
                    </button>
                  </form>

                  {{-- Soft Delete (Only if not self) --}}
                  @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('rbac.users.destroy', $user->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to soft-delete user {{ $user->name }}? School audit records will remain preserved.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn-icon p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition" title="Soft Delete Account">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-12 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <p class="font-medium text-slate-600">No user accounts found</p>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or role filters.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
      <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
        {{ $users->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
