@extends('layouts.app')

@section('title', 'Edit Role — RBAC')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

  {{-- Page Header --}}
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <a href="{{ route('rbac.roles.index') }}" class="hover:text-indigo-600 transition">Role Management</a>
        <span>/</span>
        <span class="text-indigo-600">Edit Role</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Role: {{ ucwords(str_replace('_', ' ', $role->name)) }}</h1>
      <p class="text-sm text-slate-500 mt-0.5">Customize assigned module operations and operational parameters.</p>
    </div>
    <a href="{{ route('rbac.roles.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs">
      &larr; Back to Roles
    </a>
  </div>

  {{-- Edit Form --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <form method="POST" action="{{ route('rbac.roles.update', $role->id) }}" class="p-6 space-y-6">
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

      {{-- Section 1: Role Metadata --}}
      <div>
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 1. Role Identification
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Role Identifier</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" {{ $isSystemRole ? 'readonly' : 'required' }} class="w-full px-3 py-2 text-sm {{ $isSystemRole ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50' }} border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
            @if($isSystemRole)
              <span class="text-[11px] text-amber-600 font-medium">Built-in system role identifier is protected and cannot be altered.</span>
            @endif
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Assigned Active Accounts</label>
            <div class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-semibold">
              {{ $role->users()->count() }} users currently hold this role
            </div>
          </div>
        </div>
      </div>

      {{-- Section 2: Module Permissions Matrix --}}
      <div>
        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-4">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span> 2. Assigned Action Permissions
          </h2>
          <span class="text-xs text-slate-400">Select applicable operations across 22 modules</span>
        </div>

        <div class="space-y-4">
          @foreach($moduleCatalog as $modKey => $modData)
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50" x-data="{ selectAll(checked) { $el.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = checked); } }">
              <div class="flex items-center justify-between">
                <div>
                  <span class="font-bold text-slate-900 text-sm">{{ $modData['name'] }}</span>
                  <p class="text-xs text-slate-500 mt-0.5">{{ $modData['description'] }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <button type="button" @click="selectAll(true)" class="text-[11px] font-semibold text-indigo-600 hover:underline">Select All</button>
                  <span class="text-slate-300">|</span>
                  <button type="button" @click="selectAll(false)" class="text-[11px] font-semibold text-slate-500 hover:underline">Clear</button>
                </div>
              </div>

              {{-- Checkboxes for default actions --}}
              <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5 mt-3 pt-3 border-t border-slate-200/60">
                @foreach($modData['default_actions'] as $act)
                  @php
                    $permName = "{$modKey}:{$act}";
                    $legacyAction = "{$act} {$modKey}";
                    $legacyManage = "manage {$modKey}";
                    $isChecked = in_array($permName, $rolePermissions, true) || in_array($legacyAction, $rolePermissions, true) || in_array($legacyManage, $rolePermissions, true);
                  @endphp
                  <label class="flex items-center gap-2 text-xs font-medium text-slate-700 bg-white p-2 rounded-lg border border-slate-200 hover:border-indigo-300 cursor-pointer transition">
                    <input type="checkbox" name="permissions[]" value="{{ $permName }}" {{ $isChecked ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="capitalize">{{ str_replace('_', ' ', $act) }}</span>
                  </label>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Form Actions --}}
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('rbac.roles.index') }}" class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition">Cancel</a>
        <button type="submit" class="px-6 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
          Save Role Permissions
        </button>
      </div>

    </form>
  </div>

</div>
@endsection
