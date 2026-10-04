@extends('layouts.admin')

@section('title', 'Administrative Approval Inbox')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Approval Inbox</span>
@endsection

@section('content')
<div class="space-y-6 pb-12" x-data="approvalInbox()">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Administrative Approvals Desk</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Centralized review inbox for draft notices, proposed school events, and submitted task sign-offs.</p>
    </div>
  </div>

  {{-- Sections Grid --}}
  <div class="space-y-6">

    {{-- 1. Pending Notices --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="px-6 py-4 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
          <h3 class="text-sm font-bold text-slate-800">Pending Notices ({{ $pendingNotices->count() }})</h3>
        </div>
      </div>

      <div class="divide-y divide-slate-100">
        @forelse($pendingNotices as $notice)
          <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-800">{{ $notice->priority }}</span>
                <span class="text-xs text-slate-400">Target: {{ ucfirst($notice->target_audience) }}</span>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs text-slate-400">Submitted by: <strong class="text-slate-600">{{ $notice->createdBy?->name }}</strong></span>
              </div>
              <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $notice->title }}</h4>
              <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $notice->content }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button @click="decideNotice({{ $notice->id }}, 'approve')" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                Approve & Publish
              </button>
              <button @click="decideNotice({{ $notice->id }}, 'reject')" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 transition">
                Reject
              </button>
              <a href="{{ route('activities.notices.show', $notice->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                Review &rarr;
              </a>
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-slate-400 text-xs">
            No notices awaiting approval.
          </div>
        @endforelse
      </div>
    </div>

    {{-- 2. Pending Events --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="px-6 py-4 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
          <h3 class="text-sm font-bold text-slate-800">Pending School Events ({{ $pendingEvents->count() }})</h3>
        </div>
      </div>

      <div class="divide-y divide-slate-100">
        @forelse($pendingEvents as $event)
          <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-800">{{ $event->event_type }}</span>
                <span class="text-xs text-slate-400">Date: {{ $event->event_date->format('d M Y') }}</span>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs text-slate-400">Estimated Budget: ₹{{ number_format($event->budget_estimated, 2) }}</span>
              </div>
              <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $event->name }}</h4>
              <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $event->description ?? 'No extra description.' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button @click="decideEvent({{ $event->id }}, 'approve')" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                Approve Event
              </button>
              <button @click="decideEvent({{ $event->id }}, 'reject')" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 transition">
                Reject
              </button>
              <a href="{{ route('activities.events.show', $event->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                Details &rarr;
              </a>
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-slate-400 text-xs">
            No events awaiting approval.
          </div>
        @endforelse
      </div>
    </div>

    {{-- 3. Pending Task Reviews --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="px-6 py-4 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
          <h3 class="text-sm font-bold text-slate-800">Tasks Submitted for Completion Review ({{ $pendingTasks->count() }})</h3>
        </div>
      </div>

      <div class="divide-y divide-slate-100">
        @forelse($pendingTasks as $task)
          <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-100 text-indigo-800">{{ $task->priority }}</span>
                <span class="text-xs text-slate-400">Completed by: <strong class="text-slate-600">{{ $task->assignees->first()?->user?->name ?? 'Staff' }}</strong></span>
              </div>
              <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $task->title }}</h4>
              <p class="text-xs text-slate-500 mt-0.5">{{ $task->completion_notes ?? 'Submitted for review upon completion.' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button @click="decideTask({{ $task->id }}, 'approve')" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                Sign Off & Close
              </button>
              <button @click="decideTask({{ $task->id }}, 'reopen')" class="px-3.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs border border-amber-200 transition">
                Reopen
              </button>
              <a href="{{ route('activities.tasks.show', $task->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                Examine &rarr;
              </a>
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-slate-400 text-xs">
            No task completion reviews pending.
          </div>
        @endforelse
      </div>
    </div>

  </div>

</div>

<script>
function approvalInbox() {
  return {
    async decideNotice(id, action) {
      const reason = action === 'reject' ? prompt('Reason for rejection:') : null;
      if (action === 'reject' && !reason) return;
      try {
        const res = await fetch(`/api/notices/${id}/approve`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ action: action, reason: reason }),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async decideEvent(id, action) {
      const reason = action === 'reject' ? prompt('Reason for rejection:') : null;
      if (action === 'reject' && !reason) return;
      try {
        const res = await fetch(`/api/events/${id}/approve`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ action: action, reason: reason }),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async decideTask(id, action) {
      const notes = prompt(action === 'approve' ? 'Sign-off approval notes:' : 'Reopening revision instructions:');
      try {
        const res = await fetch(`/api/tasks/${id}/review`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ action: action, notes: notes }),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    }
  }
}
</script>
@endsection
