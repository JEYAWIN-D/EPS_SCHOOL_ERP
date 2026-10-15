@extends('layouts.app')

@section('title', 'Account Target Configurations — RBAC')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <a href="{{ route('rbac.roles.index') }}" class="hover:text-indigo-600 transition">Role Management</a>
        <span>/</span>
        <span class="text-indigo-600">Account Targets</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Account Configuration Targets</h1>
      <p class="text-sm text-slate-500 mt-0.5">Manage operational account quotas, staffing benchmarks, and failover redundancy targets.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="{{ route('rbac.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
        &larr; User Directory
      </a>
    </div>
  </div>

  {{-- Operational Policy Notice --}}
  <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex items-start gap-3">
    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div class="text-xs">
      <p class="font-bold text-blue-950">Architectural Note on Account Targets</p>
      <p class="mt-0.5 text-blue-800">Account targets represent institutional planning goals (e.g. target 2 Super Admins for high-availability redundancy). Modifying target values will never automatically create, remove, or deactivate existing accounts.</p>
    </div>
  </div>

  {{-- Targets Form & Table --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <form method="POST" action="{{ route('rbac.config.targets.update') }}">
      @csrf

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11.5px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3 px-4 min-w-[200px]">Role Designation</th>
              <th class="py-3 px-4 text-center w-36">Target Quota</th>
              <th class="py-3 px-4 text-center w-28">Active Now</th>
              <th class="py-3 px-4 text-center w-28">Inactive</th>
              <th class="py-3 px-4 text-center w-40">Variance Status</th>
              <th class="py-3 px-4">Operational Rationale & Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($metrics as $index => $m)
              <tr class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs flex-shrink-0 {{ $m['role']->name === 'super_admin' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' }}">
                      {{ strtoupper(substr($m['role']->name, 0, 2)) }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block">{{ ucwords(str_replace('_', ' ', $m['role']->name)) }}</span>
                      <span class="font-mono text-[11px] text-slate-400">slug: {{ $m['role']->name }}</span>
                    </div>
                  </div>
                  <input type="hidden" name="targets[{{ $index }}][role_name]" value="{{ $m['role']->name }}">
                </td>
                <td class="py-3 px-4 text-center">
                  <input type="number"
                         name="targets[{{ $index }}][target_count]"
                         value="{{ $m['target_count'] }}"
                         min="0"
                         max="200"
                         required
                         class="w-24 text-center px-2 py-1 text-sm font-bold bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="font-black text-slate-900 text-base">{{ $m['actual_count'] }}</span>
                </td>
                <td class="py-3 px-4 text-center text-xs text-slate-400">
                  {{ $m['inactive_count'] }}
                </td>
                <td class="py-3 px-4 text-center text-xs">
                  @if($m['variance'] === 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Optimal ({{ $m['target_count'] }})
                    </span>
                  @elseif($m['variance'] > 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold bg-blue-50 text-blue-700 border border-blue-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Surplus (+{{ $m['variance'] }})
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Below Target ({{ $m['variance'] }})
                    </span>
                  @endif
                </td>
                <td class="py-3 px-4">
                  <input type="text"
                         name="targets[{{ $index }}][notes]"
                         value="{{ $m['notes'] }}"
                         placeholder="Operational rationale..."
                         class="w-full px-3 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Form Footer --}}
      <div class="p-4 bg-slate-50/60 border-t border-slate-200 flex items-center justify-between">
        <p class="text-xs text-slate-500">All changes are audited and recorded in the system security log.</p>
        <button type="submit" class="px-6 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
          Save Target Configurations
        </button>
      </div>

    </form>
  </div>

</div>
@endsection
