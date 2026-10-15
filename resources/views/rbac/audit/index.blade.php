@extends('layouts.app')

@section('title', 'RBAC & Security Audit Trail — RBAC')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Access Control</span>
        <span>/</span>
        <span class="text-indigo-600">Audit History</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Security & RBAC Audit Trail</h1>
      <p class="text-sm text-slate-500 mt-0.5">Immutable record of authentication, permission updates, role modifications, and authorization attempts.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="{{ route('rbac.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
        &larr; User Directory
      </a>
    </div>
  </div>

  {{-- Filter Bar --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
    <form method="GET" action="{{ route('rbac.audit.index') }}" class="flex flex-col md:flex-row gap-3">
      <div class="w-full md:w-64">
        <select name="action" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
          <option value="">All Security Events</option>
          @foreach($availableActions as $act)
            <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
          @endforeach
        </select>
      </div>
      <div class="w-full md:w-44">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg">
      </div>
      <div class="w-full md:w-44">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg">
      </div>
      <div class="flex items-center gap-2">
        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-white transition">Filter Logs</button>
        <a href="{{ route('rbac.audit.index') }}" class="px-3 py-2 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition">Reset</a>
      </div>
    </form>
  </div>

  {{-- Audit Logs Table --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-[11.5px] font-bold text-slate-500 uppercase tracking-wider">
            <th class="py-3 px-4 w-44">Timestamp</th>
            <th class="py-3 px-4 w-44">Actor</th>
            <th class="py-3 px-4 w-44">Event / Action</th>
            <th class="py-3 px-4 w-36">IP Address</th>
            <th class="py-3 px-4">Change Payload / Context</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($logs as $log)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}
              </td>
              <td class="py-3 px-4">
                @if($log->actor_name)
                  <span class="font-bold text-slate-900 block">{{ $log->actor_name }}</span>
                  <span class="text-[11px] text-slate-400">{{ $log->actor_email }}</span>
                @else
                  <span class="text-slate-400 italic">System / Unauthenticated</span>
                @endif
              </td>
              <td class="py-3 px-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10.5px] font-bold
                  {{ str_contains($log->action, 'DELETED') || str_contains($log->action, 'DENIED') ? 'bg-rose-100 text-rose-800 border border-rose-200' : 
                    (str_contains($log->action, 'CREATED') ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 
                    (str_contains($log->action, 'PASSWORD') ? 'bg-amber-100 text-amber-800 border border-amber-200' : 
                    'bg-indigo-50 text-indigo-700 border border-indigo-200')) }}">
                  {{ $log->action }}
                </span>
                @if($log->model_type)
                  <span class="text-[10px] text-slate-400 block mt-0.5 font-mono">{{ $log->model_type }} #{{ $log->model_id }}</span>
                @endif
              </td>
              <td class="py-3 px-4 font-mono text-slate-500">
                {{ $log->ip_address }}
              </td>
              <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                <div class="max-w-xl truncate" title="{{ $log->new_values ?? $log->old_values ?? '' }}">
                  {{ $log->new_values ?? $log->old_values ?? 'No payload recorded' }}
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="py-12 text-center text-slate-400">
                <p class="font-medium text-slate-600">No security audit logs found</p>
                <p class="text-xs text-slate-400 mt-1">Actions performed across the RBAC module will appear here.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($logs->hasPages())
      <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
        {{ $logs->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
