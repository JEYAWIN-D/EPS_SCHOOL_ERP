@extends('layouts.admin')

@section('title', 'Assign New Task')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.tasks.index') }}" class="hover:text-indigo-600">Tasks</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">New</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12" x-data="taskForm()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Create & Assign Task</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Delegate responsibilities, set deadlines, and track completion progress.</p>
    </div>
    <a href="{{ route('activities.tasks.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 transition">
      Cancel
    </a>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
    <form @submit.prevent="submitTask" class="space-y-6">

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Task Title <span class="text-red-500">*</span></label>
        <input type="text" x-model="form.title" required placeholder="e.g., Prepare Mathematics Sample Paper Blueprint for Term 2"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Priority</label>
          <select x-model="form.priority" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Department</label>
          <select x-model="form.department_id" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="">General / None</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Estimated Hours</label>
          <input type="number" step="0.5" x-model="form.estimated_hours" placeholder="e.g., 8.0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Start Date</label>
          <input type="date" x-model="form.start_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Due Date / Deadline <span class="text-red-500">*</span></label>
          <input type="date" x-model="form.due_date" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Primary Assignee <span class="text-red-500">*</span></label>
        <select x-model="form.assignee_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Select Staff Member...</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Task Description & Deliverables</label>
        <textarea x-model="form.description" rows="5" placeholder="Specify requirements, deliverables, guidelines, or standards expected..."
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('activities.tasks.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">Cancel</a>
        <button type="submit" :disabled="loading" class="btn-primary px-6 py-2.5 text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50">
          <span x-show="!loading">Assign Task</span>
          <span x-show="loading">Assigning...</span>
        </button>
      </div>

    </form>
  </div>
</div>

<script>
function taskForm() {
  return {
    loading: false,
    form: {
      title: '',
      description: '',
      priority: 'normal',
      department_id: '',
      start_date: '{{ now()->toDateString() }}',
      due_date: '{{ now()->addDays(7)->toDateString() }}',
      estimated_hours: '',
      assignee_id: '',
    },
    async submitTask() {
      this.loading = true;
      try {
        const payload = {
          ...this.form,
          assignees: [this.form.assignee_id],
        };
        const res = await fetch('{{ route('api.tasks.store') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.status === 'success') {
          window.location.href = '{{ route('activities.tasks.index') }}';
        } else {
          alert(data.message || 'Error creating task');
        }
      } catch (err) {
        alert('Failed: ' + err.message);
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
@endsection
