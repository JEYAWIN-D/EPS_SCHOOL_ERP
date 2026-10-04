@extends('layouts.admin')

@section('title', $task->title . ' — Task Details')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.tasks.index') }}" class="hover:text-indigo-600">Tasks</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Details</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12" x-data="taskShow()">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('activities.tasks.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
      &larr; Back to Task Desk
    </a>

    <div class="flex items-center gap-2">
      @if($task->status !== 'completed' && $task->status !== 'submitted_for_review')
        <button @click="showUpdateModal = true" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition">
          + Post Progress Update
        </button>
        <button @click="submitForReview()" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
          Submit for Review
        </button>
      @endif

      @if($task->status === 'submitted_for_review')
        @canany(['manage tasks', 'approve tasks'])
          <button @click="reviewTask('approve')" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
            Approve & Close
          </button>
          <button @click="reviewTask('reopen')" class="px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs border border-amber-200 transition">
            Reopen with Notes
          </button>
        @endcanany
      @endif

      <button @click="showReassignModal = true" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition border border-slate-200">
        Reassign
      </button>
    </div>
  </div>

  {{-- Overdue Alert --}}
  @if($task->is_overdue)
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center gap-3">
      <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <div>
        <h4 class="text-xs font-bold text-rose-900">Task Deadline Exceeded</h4>
        <p class="text-xs text-rose-700">This task was scheduled to be completed by {{ $task->due_date->format('d M Y') }}.</p>
      </div>
    </div>
  @endif

  {{-- Main Task Details Card --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-6 sm:p-8">
      <div class="flex flex-wrap items-center gap-2.5 mb-3">
        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider
          {{ $task->priority === 'urgent' ? 'bg-rose-100 text-rose-800' : ($task->priority === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
          {{ $task->priority }} Priority
        </span>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold
          {{ $task->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($task->status === 'submitted_for_review' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
          Status: {{ ucfirst(str_replace('_', ' ', $task->status)) }}
        </span>
        @if($task->department)
          <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
            {{ $task->department->name }}
          </span>
        @endif
      </div>

      <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
        {{ $task->title }}
      </h1>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
        <div>
          <span class="text-slate-400 block font-medium">Assigned To</span>
          <span class="font-bold text-slate-800 mt-0.5 block">{{ $task->assignees->first()?->user?->name ?? 'None' }}</span>
        </div>
        <div>
          <span class="text-slate-400 block font-medium">Assigned By</span>
          <span class="font-bold text-slate-800 mt-0.5 block">{{ $task->creator?->name ?? 'Admin' }}</span>
        </div>
        <div>
          <span class="text-slate-400 block font-medium">Deadline / Due Date</span>
          <span class="font-bold text-slate-800 mt-0.5 block">{{ $task->due_date ? $task->due_date->format('d M Y') : 'Open' }}</span>
        </div>
      </div>

      <div class="mt-6 prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed whitespace-pre-line">
        {{ $task->description ?? 'No extra task description provided.' }}
      </div>
    </div>
  </div>

  {{-- Progress Updates Timeline --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-800">Progress History & Updates</h3>
      <button @click="showUpdateModal = true" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">+ Add Progress</button>
    </div>

    <div class="space-y-4">
      @forelse($task->updates as $up)
        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 space-y-1.5">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span class="font-bold text-slate-800">{{ $up->user?->name }}</span>
            <span>{{ $up->created_at->format('d M Y, h:i A') }}</span>
          </div>
          <p class="text-xs text-slate-700 leading-relaxed">{{ $up->comment }}</p>
          <div class="flex items-center gap-3 pt-1 text-[11px] text-slate-400">
            @if($up->progress_percentage !== null)
              <span class="font-bold text-indigo-600">{{ $up->progress_percentage }}% Completed</span>
            @endif
            @if($up->status_to)
              <span>&bull; Status changed to: <strong class="text-slate-600 capitalize">{{ str_replace('_', ' ', $up->status_to) }}</strong></span>
            @endif
            @if($up->evidence_path)
              <span>&bull; <a href="{{ $up->evidence_path }}" target="_blank" class="text-indigo-600 underline">View Evidence Link</a></span>
            @endif
          </div>
        </div>
      @empty
        <p class="text-xs text-slate-400 italic">No progress updates posted yet.</p>
      @endforelse
    </div>
  </div>

  {{-- Update Progress Modal --}}
  <div x-show="showUpdateModal" x-transition class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="showUpdateModal = false">
      <h3 class="text-base font-bold text-slate-900">Post Task Progress Update</h3>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Update Comment / Notes <span class="text-red-500">*</span></label>
        <textarea x-model="updateForm.comment" rows="3" required placeholder="Describe what you worked on, milestones achieved, or blockers..."
                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Progress (%)</label>
          <input type="number" min="0" max="100" x-model="updateForm.progress_percentage" placeholder="e.g. 50" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
          <select x-model="updateForm.status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="in_progress">In Progress</option>
            <option value="blocked">Blocked</option>
            <option value="accepted">Accepted</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Evidence / Deliverable Link (Optional)</label>
        <input type="text" x-model="updateForm.evidence_path" placeholder="URL or document link" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
      </div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button @click="showUpdateModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
        <button @click="submitUpdate()" class="btn-primary px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Post Update</button>
      </div>
    </div>
  </div>

  {{-- Reassign Modal --}}
  <div x-show="showReassignModal" x-transition class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="showReassignModal = false">
      <h3 class="text-base font-bold text-slate-900">Reassign Task</h3>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Assignee</label>
        <select x-model="reassignForm.new_assignee_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Select Staff...</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Reason for Reassignment</label>
        <textarea x-model="reassignForm.reason" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm"></textarea>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button @click="showReassignModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
        <button @click="submitReassign()" class="btn-primary px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Reassign</button>
      </div>
    </div>
  </div>

</div>

<script>
function taskShow() {
  return {
    showUpdateModal: false,
    showReassignModal: false,
    updateForm: { comment: '', progress_percentage: '', status: 'in_progress', evidence_path: '' },
    reassignForm: { new_assignee_id: '', reason: '' },
    async submitUpdate() {
      try {
        const res = await fetch('{{ route('api.tasks.updates', $task->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify(this.updateForm),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async submitForReview() {
      if (!confirm('Submit this task for administrative review and sign-off?')) return;
      try {
        const res = await fetch('{{ route('api.tasks.submit', $task->id) }}', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async reviewTask(action) {
      const notes = prompt(action === 'approve' ? 'Optional sign-off approval notes:' : 'Enter revision notes for reopening:');
      try {
        const res = await fetch('{{ route('api.tasks.review', $task->id) }}', {
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
    },
    async submitReassign() {
      try {
        const res = await fetch('{{ route('api.tasks.reassign', $task->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify(this.reassignForm),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    }
  }
}
</script>
@endsection
