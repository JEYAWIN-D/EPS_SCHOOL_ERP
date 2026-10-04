@extends('layouts.admin')

@section('title', 'Schedule Meeting')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.meetings.index') }}" class="hover:text-indigo-600">Meetings</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">New</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12" x-data="meetingForm()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Schedule New Meeting</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Convene staff meetings, department reviews, or parent-teacher conferences with conflict checks.</p>
    </div>
    <a href="{{ route('activities.meetings.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 transition">
      Cancel
    </a>
  </div>

  {{-- Conflict Warning Card --}}
  <div x-show="conflicts.length > 0" x-transition class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2" style="display:none">
    <div class="flex items-center gap-2">
      <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <h4 class="text-xs font-bold text-amber-900">Schedule Conflicts Detected:</h4>
    </div>
    <ul class="list-disc list-inside text-xs text-amber-800 pl-6 space-y-1">
      <template x-for="c in conflicts" :key="c.title">
        <li><strong x-text="c.title"></strong>: <span x-text="c.details"></span></li>
      </template>
    </ul>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
    <form @submit.prevent="submitMeeting" class="space-y-6">

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Meeting Title <span class="text-red-500">*</span></label>
        <input type="text" x-model="form.title" required placeholder="e.g., Weekly Academic Review & Curriculum Progress"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Meeting Type</label>
          <select x-model="form.meeting_type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="staff">Staff Meeting</option>
            <option value="hod">HOD / Department Heads</option>
            <option value="management">Management Council</option>
            <option value="ptm">Parent-Teacher Meeting (PTM)</option>
            <option value="departmental">Departmental Review</option>
            <option value="general">General Meeting</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Chairperson</label>
          <select x-model="form.chairperson_id" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="">Select Chairperson...</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Department (Optional)</label>
          <select x-model="form.department_id" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="">All / General</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Meeting Date <span class="text-red-500">*</span></label>
          <input type="date" x-model="date" @change="checkConflicts()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Start Time <span class="text-red-500">*</span></label>
          <input type="time" x-model="startTime" @change="checkConflicts()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">End Time <span class="text-red-500">*</span></label>
          <input type="time" x-model="endTime" @change="checkConflicts()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Venue / Room</label>
          <input type="text" x-model="form.venue" @input.debounce.500ms="checkConflicts()" placeholder="e.g., Conference Room B / Main Hall"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Online Video Link (If Virtual)</label>
          <input type="url" x-model="form.meeting_link" placeholder="https://meet.google.com/..."
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Agenda & Discussion Points</label>
        <textarea x-model="form.agenda" rows="4" placeholder="1. Review term exam schedule&#10;2. Textbook distribution updates&#10;3. Extra-curricular calendar"
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Invited Attendees <span class="text-red-500">*</span></label>
        <div class="max-h-48 overflow-y-auto p-3 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50/50">
          @foreach($users as $user)
            <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-white text-xs text-slate-700 cursor-pointer">
              <input type="checkbox" value="{{ $user->id }}" x-model="form.attendees" @change="checkConflicts()" class="w-4 h-4 text-indigo-600 rounded-sm border-slate-300">
              <span class="font-medium truncate">{{ $user->name }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('activities.meetings.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">Cancel</a>
        <button type="submit" :disabled="loading" class="btn-primary px-6 py-2.5 text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50">
          <span x-show="!loading">Send Invitations & Schedule</span>
          <span x-show="loading">Scheduling...</span>
        </button>
      </div>

    </form>
  </div>
</div>

<script>
function meetingForm() {
  return {
    loading: false,
    date: '{{ now()->addDay()->toDateString() }}',
    startTime: '10:00',
    endTime: '11:00',
    conflicts: [],
    form: {
      title: '',
      agenda: '',
      meeting_type: 'staff',
      chairperson_id: '{{ auth()->id() }}',
      department_id: '',
      venue: 'Conference Hall A',
      meeting_link: '',
      attendees: [{{ auth()->id() }}],
    },
    async checkConflicts() {
      if (!this.date || !this.startTime || !this.endTime) return;
      try {
        const start = `${this.date} ${this.startTime}`;
        const end   = `${this.date} ${this.endTime}`;
        const usersParam = this.form.attendees.map(u => `user_ids[]=${u}`).join('&');
        const res = await fetch(`{{ route('api.calendar.conflicts') }}?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}&venue=${encodeURIComponent(this.form.venue || '')}&${usersParam}`);
        const data = await res.json();
        if (data.status === 'success') {
          this.conflicts = data.data || [];
        }
      } catch (e) {}
    },
    async submitMeeting() {
      this.loading = true;
      try {
        const payload = {
          ...this.form,
          start_time: `${this.date} ${this.startTime}:00`,
          end_time: `${this.date} ${this.endTime}:00`,
        };
        const res = await fetch('{{ route('api.meetings.store') }}', {
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
          window.location.href = '{{ route('activities.meetings.index') }}';
        } else {
          alert(data.message || 'Error scheduling meeting');
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
