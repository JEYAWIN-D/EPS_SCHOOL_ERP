@extends('layouts.app')

@section('title', 'Create User Account — RBAC')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

  {{-- Page Header --}}
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <a href="{{ route('rbac.users.index') }}" class="hover:text-indigo-600 transition">User Management</a>
        <span>/</span>
        <span class="text-indigo-600">New Account</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create User Account</h1>
      <p class="text-sm text-slate-500 mt-0.5">Provision an institutional user with role-based access, boundaries, and credentials.</p>
    </div>
    <a href="{{ route('rbac.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs">
      &larr; Back to Users
    </a>
  </div>

  {{-- Form Card --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <form method="POST" action="{{ route('rbac.users.store') }}" class="p-6 space-y-6" x-data="{ selectedScope: 'none' }">
      @csrf

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
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 1. Basic Account Credentials
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Full Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username (Optional)</label>
            <input type="text" name="username" value="{{ old('username') }}" placeholder="e.g. jdoe_admin" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
            <span class="text-[11px] text-slate-400">Letters, numbers, dashes, and underscores only</span>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Address <span class="text-rose-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="john.doe@schoolerp.in" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mobile Number</label>
            <input type="text" name="mobile" value="{{ old('mobile') }}" placeholder="+91 9876543210" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Password <span class="text-rose-500">*</span></label>
            <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Confirm Password <span class="text-rose-500">*</span></label>
            <input type="password" name="password_confirmation" required minlength="8" placeholder="Confirm password" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          </div>
        </div>
      </div>

      {{-- Section 2: Role Assignment & Staff Linkage --}}
      <div>
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 2. Role & Institutional Linkage
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Assign Primary Role <span class="text-rose-500">*</span></label>
            <select name="role" required class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
              <option value="">-- Select Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                  {{ ucwords(str_replace('_', ' ', $role->name)) }}
                </option>
              @endforeach
            </select>
            <span class="text-[11px] text-slate-400">Controls default module and action permissions</span>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Link Employee Profile (Optional)</label>
            <select name="employee_id" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
              <option value="">-- No Linked Employee --</option>
              @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                  {{ $emp->name }} ({{ $emp->employee_code ?? 'EMP#' . $emp->id }})
                </option>
              @endforeach
            </select>
            <span class="text-[11px] text-slate-400">Links user to institutional staff profile and payroll</span>
          </div>
        </div>
      </div>

      {{-- Section 3: Data-Level Access Scope --}}
      <div>
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

          {{-- Conditional Scope Value Inputs --}}
          <div x-show="selectedScope === 'class_id'" style="display: none;">
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Select Allowed Class</label>
            <select name="scope_value" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
              @foreach($classes as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          <div x-show="selectedScope === 'section_id'" style="display: none;">
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Select Allowed Section</label>
            <select name="scope_value" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
              @foreach($sections as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      {{-- Section 4: Security Preferences --}}
      <div>
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 4. Security & Status Settings
        </h2>
        <div class="space-y-3">
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
            <div>
              <span class="text-sm font-semibold text-slate-800">Account Active</span>
              <p class="text-xs text-slate-400">User will be permitted to log in immediately upon creation.</p>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="must_change_password" value="1" checked class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
            <div>
              <span class="text-sm font-semibold text-slate-800">Force Password Change</span>
              <p class="text-xs text-slate-400">Requires the user to establish a new password upon first successful login.</p>
            </div>
          </label>
        </div>
      </div>

      {{-- Form Actions --}}
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('rbac.users.index') }}" class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition">Cancel</a>
        <button type="submit" class="px-6 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
          Create User Account
        </button>
      </div>

    </form>
  </div>

</div>
@endsection
