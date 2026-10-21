@extends('layouts.app')

@section('title', 'Role Management — RBAC')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Access Control</span>
        <span>/</span>
        <span class="text-indigo-600">Role Management</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Institutional & Custom Roles</h1>
      <p class="text-sm text-slate-500 mt-1">Configure predefined operational roles, custom administrator positions, and target allocations.</p>
    </div>
    <div class="flex items-center gap-2.5 flex-wrap">
      <a href="{{ route('rbac.config.targets') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-xs transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Account Targets
      </a>
      <a href="{{ route('rbac.matrix.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-xs transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Permission Matrix
      </a>
      <a href="{{ route('rbac.roles.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Custom Role
      </a>
    </div>
  </div>

  {{-- Roles Table --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-[11.5px] font-bold text-slate-500 uppercase tracking-wider">
            <th class="py-3 px-4">Role Designation</th>
            <th class="py-3 px-4">Classification</th>
            <th class="py-3 px-4">Active Accounts / Target</th>
            <th class="py-3 px-4">Permissions</th>
            <th class="py-3 px-4">Redundancy Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($roles as $role)
            @php
              $isSystem = in_array($role->name, $systemRoles, true);
              $targetObj = $accountTargets->get($role->name);
              $targetCount = $targetObj ? $targetObj->target_count : 1;
              $activeCount = $role->users_count;
            @endphp
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs flex-shrink-0 {{ $role->name === 'super_admin' ? 'bg-purple-100 text-purple-700' : ($isSystem ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700') }}">
                    {{ strtoupper(substr($role->name, 0, 2)) }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                    <span class="font-mono text-[11px] text-slate-400">slug: {{ $role->name }}</span>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4">
                @if($isSystem)
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    Built-in System Role
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    Custom Configured Role
                  </span>
                @endif
              </td>
              <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-900">{{ $activeCount }}</span>
                  <span class="text-xs text-slate-400">/ target {{ $targetCount }}</span>
                </div>
                <div class="w-24 bg-slate-100 h-1.5 rounded-full overflow-hidden mt-1.5">
                  <div class="h-full {{ $activeCount >= $targetCount ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ min(100, $targetCount > 0 ? ($activeCount / $targetCount) * 100 : 100) }}%"></div>
                </div>
              </td>
              <td class="py-3 px-4">
                @if($role->name === 'super_admin')
                  <span class="font-bold text-purple-700 text-xs">Unrestricted (All)</span>
                @else
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700">
                    {{ $role->permissions_count }} Actions
                  </span>
                @endif
              </td>
              <td class="py-3 px-4 text-xs">
                @if($activeCount === $targetCount)
                  <span class="text-emerald-700 font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Optimal Target
                  </span>
                @elseif($activeCount > $targetCount)
                  <span class="text-blue-700 font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Surplus (+{{ $activeCount - $targetCount }})
                  </span>
                @else
                  <span class="text-amber-700 font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Below Target (-{{ $targetCount - $activeCount }})
                  </span>
                @endif
              </td>
              <td class="py-3 px-4 text-right">
                <div class="inline-flex items-center gap-1.5 justify-end">
                  {{-- Open in Matrix --}}
                  <a href="{{ route('rbac.matrix.index', ['role_id' => $role->id]) }}" class="btn-icon p-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition" title="Open in Permission Matrix">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  </a>

                  {{-- Edit --}}
                  <a href="{{ route('rbac.roles.edit', $role->id) }}" class="btn-icon p-1.5 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-slate-100 transition" title="Edit Permissions">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </a>

                  {{-- Duplicate --}}
                  <form method="POST" action="{{ route('rbac.roles.duplicate', $role->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-icon p-1.5 rounded-lg text-slate-600 hover:text-emerald-600 hover:bg-slate-100 transition" title="Duplicate Role Template">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    </button>
                  </form>

                  {{-- Delete (Custom Roles only) --}}
                  @if(!$isSystem)
                    <form method="POST" action="{{ route('rbac.roles.destroy', $role->id) }}" class="inline" onsubmit="return confirm('Delete custom role {{ $role->name }}? Any assigned users will need to be reassigned.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn-icon p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition" title="Delete Custom Role">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
