@extends('layouts.app')

@section('title', 'Edit User Account — RBAC')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

  {{-- Page Header --}}
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <a href="{{ route('rbac.users.index') }}" class="hover:text-indigo-600 transition">User Management</a>
        <span>/</span>
        <span class="text-indigo-600">Edit User #{{ $user->id }}</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Account: {{ $user->name }}</h1>
      <p class="text-sm text-slate-500 mt-0.5">Modify profile details, role permissions, and record boundaries.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('rbac.users.show', $user->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition">
        View Summary &rarr;
      </a>
      <a href="{{ route('rbac.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs">
        &larr; Back
      </a>
    </div>
  </div>

  @if($user->isSuperAdmin())
    <div class="p-4 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 flex items-start gap-3">
      <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <div class="text-xs">
        <p class="font-bold text-purple-950">Super Administrator Account</p>
        <p class="mt-0.5 text-purple-800">This account possesses global, unrestricted system authority across all modules and submodules. Guard against accidental removal of the last active Super Admin.</p>
      </div>
    </div>
  @endif

  {{-- Edit Form --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <form method="POST" action="{{ route('rbac.users.update', $user->id) }}" class="p-6 space-y-6">
      @csrf
      @method('PUT')

      @if($errors->any())
        <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm">
          <p class="font-bold mb-1">Please correct the following errors:</p>
          <ul class="list-disc list-inside space-y-0.5 text-xs">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Section 1: Basic Identity --}}
      <div>
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 1. Account Identity
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Full Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username</label>
            <input type="text" name="username" value="{{ old('username', $user->username) }}" placeholder="e.g. jdoe_admin" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Address <span class="text-rose-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mobile Number</label>
            <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
        </div>
      </div>

      {{-- Section 2: Role & Employee --}}
      @php
        $currentRole = $user->roles->first()?->name;
      @endphp
      <div>
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 2. Role Assignment
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Primary Role <span class="text-rose-500">*</span></label>
            <select name="role" required class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
              @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ old('role', $currentRole) == $role->name ? 'selected' : '' }}>
                  {{ ucwords(str_replace('_', ' ', $role->name)) }}
                </option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Link Employee Profile</label>
            <select name="employee_id" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
              <option value="">-- No Linked Employee --</option>
              @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ old('employee_id', $user->employee_id) == $emp->id ? 'selected' : '' }}>
                  {{ $emp->name }} ({{ $emp->employee_code ?? 'EMP#' . $emp->id }})
                </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      {{-- Section 3: Data Scopes --}}
      @php
        $existingScope = $user->dataScopes->first();
        $existingScopeType = $existingScope?->scope_type ?? 'none';
        $existingScopeVal = $existingScope?->scope_value ?? '';
      @endphp
      <div x-data="{ selectedScope: '{{ $existingScopeType }}' }">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 3. Data Access Scope Restriction
        </h2>
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Scope Boundary Type</label>
            <select name="scope_type" x-model="selectedScope" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
              <option value="none">No Specific Boundary (Default Role Scope)</option>
              <option value="all">Universal (Unrestricted School-Wide Access)</option>
              <option value="class_id">Specific Class</option>
              <option value="section_id">Specific Section</option>
              <option value="department">Assigned Department</option>
              <option value="route_id">Assigned Transport Route</option>
              <option value="warehouse_id">Assigned Warehouse</option>
              <option value="assigned_students_only">Assigned Students Only</option>
            </select>
          </div>

          <div x-show="selectedScope === 'class_id'" style="display: none;">
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Select Allowed Class</label>
            <select name="scope_value" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
              @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ $existingScopeVal == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          <div x-show="selectedScope === 'section_id'" style="display: none;">
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Select Allowed Section</label>
            <select name="scope_value" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
              @foreach($sections as $s)
                <option value="{{ $s->id }}" {{ $existingScopeVal == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      {{-- Section 4: Account Status --}}
      <div>
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 4. Status
        </h2>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
          <div>
            <span class="text-sm font-semibold text-slate-800">Account Active</span>
            <p class="text-xs text-slate-400">If unchecked, this user will be blocked from logging into the ERP immediately.</p>
          </div>
        </label>
      </div>

      {{-- Actions --}}
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('rbac.users.index') }}" class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition">Cancel</a>
        <button type="submit" class="px-6 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
          Update User Account
        </button>
      </div>

    </form>
  </div>

  {{-- Quick Password Reset Card --}}
  <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-amber-500"></span> Secure Password Reset
    </h2>
    <form method="POST" action="{{ route('rbac.users.reset-password', $user->id) }}" class="space-y-4">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">New Password</label>
          <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Confirm New Password</label>
          <input type="password" name="password_confirmation" required minlength="8" placeholder="Confirm new password" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
        </div>
      </div>
      <div class="flex items-center justify-between pt-2">
        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-600 font-medium">
          <input type="checkbox" name="force_reset" value="1" checked class="w-4 h-4 text-indigo-600 rounded border-slate-300">
          Force password reset on next login & revoke existing sessions
        </label>
        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition">
          Reset Password
        </button>
      </div>
    </form>
  </div>

</div>
@endsection
