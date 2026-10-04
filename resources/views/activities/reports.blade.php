@extends('layouts.admin')

@section('title', 'Activity Hub Analytics & Audit Logs')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Activity Reports</span>
@endsection

@section('content')
<div class="space-y-6 pb-12">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Activity Analytics & Compliance Audit Trail</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Comprehensive audit trail of activity creation, approvals, reassignments, completions, and acknowledgements.</p>
    </div>
  </div>

  {{-- Metrics Cards --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
      <p class="text-xs text-slate-400 font-medium">Total Notices</p>
      <p class="text-2xl font-black text-slate-800 mt-1">{{ $noticeCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
      <p class="text-xs text-indigo-600 font-medium">Total Events</p>
      <p class="text-2xl font-black text-indigo-700 mt-1">{{ $eventCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
      <p class="text-xs text-purple-600 font-medium">Total Meetings</p>
      <p class="text-2xl font-black text-purple-700 mt-1">{{ $meetingCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
      <p class="text-xs text-emerald-600 font-medium">Task Completion Rate</p>
      <p class="text-2xl font-black text-emerald-700 mt-1">{{ $taskCompletionRate }}%</p>
    </div>
  </div>

  {{-- Audit Trail Filter Bar --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('activities.reports') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('entity_type') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        All Activities
      </a>
      <a href="{{ route('activities.reports', ['entity_type' => 'notice']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('entity_type') === 'notice' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Notices
      </a>
      <a href="{{ route('activities.reports', ['entity_type' => 'event']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('entity_type') === 'event' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Events
      </a>
      <a href="{{ route('activities.reports', ['entity_type' => 'meeting']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('entity_type') === 'meeting' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Meetings
      </a>
      <a href="{{ route('activities.reports', ['entity_type' => 'task']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('entity_type') === 'task' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Tasks
      </a>
    </div>
  </div>

  {{-- Audit Trail Log Table --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-800">Immutable Activity History</h3>
      <span class="text-xs text-slate-400">Total Entries: {{ $auditLogs->total() }}</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200/70 text-slate-500 uppercase font-semibold text-[10px] tracking-wider">
            <th class="py-3 px-4">Timestamp</th>
            <th class="py-3 px-4">Operator / User</th>
            <th class="py-3 px-4">Action</th>
            <th class="py-3 px-4">Entity</th>
            <th class="py-3 px-4">Details / Metadata</th>
            <th class="py-3 px-4">IP Address</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($auditLogs as $log)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                {{ $log->created_at->format('d M Y, h:i:s A') }}
              </td>
              <td class="py-3 px-4 font-semibold text-slate-800 whitespace-nowrap">
                {{ $log->user?->name ?? 'System Process' }}
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                  {{ in_array($log->action, ['approved', 'completed', 'published']) ? 'bg-emerald-50 text-emerald-700' : ($log->action === 'rejected' || $log->action === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700') }}">
                  {{ $log->action }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium capitalize text-slate-700">
                {{ $log->entity_type }} #{{ $log->entity_id }}
              </td>
              <td class="py-3 px-4 text-slate-500 max-w-xs truncate">
                @if($log->payload)
                  {{ json_encode($log->payload) }}
                @else
                  —
                @endif
              </td>
              <td class="py-3 px-4 font-mono text-[11px] text-slate-400">
                {{ $log->ip_address ?? '—' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-8 text-center text-slate-400">No activity audit logs recorded yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-4">
    {{ $auditLogs->links() }}
  </div>

</div>
@endsection
