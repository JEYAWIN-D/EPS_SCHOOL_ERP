@extends('layouts.admin')

@section('title', 'Schedule School Event')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.events.index') }}" class="hover:text-indigo-600">Events</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">New</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12" x-data="eventForm()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Create School Event</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Appoint in-charge staff, assign venue, budget, and notify participants.</p>
    </div>
    <a href="{{ route('activities.events.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 transition">
      Cancel
    </a>
  </div>

  {{-- Conflict Warning Card --}}
  <div x-show="conflicts.length > 0" x-transition class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2" style="display:none">
    <div class="flex items-center gap-2">
      <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <h4 class="text-xs font-bold text-amber-900">Potential Schedule Warnings Detected:</h4>
    </div>
    <ul class="list-disc list-inside text-xs text-amber-800 pl-6 space-y-1">
      <template x-for="c in conflicts" :key="c.title">
        <li><strong x-text="c.title"></strong>: <span x-text="c.details"></span></li>
      </template>
    </ul>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
    <form @submit.prevent="submitEvent" class="space-y-6">

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Event Title <span class="text-red-500">*</span></label>
        <input type="text" x-model="form.name" required placeholder="e.g., Annual Inter-School Science Fair 2026"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Event Type</label>
          <select x-model="form.event_type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="academic">Academic & Science</option>
            <option value="sports">Sports & Athletics</option>
            <option value="cultural">Cultural & Arts</option>
            <option value="holiday">Special Celebration</option>
            <option value="meeting">General Assembly</option>
            <option value="other">Other Activity</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Category Tag</label>
          <input type="text" x-model="form.category" placeholder="e.g., Championship, Workshop, Field Trip"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Event Date <span class="text-red-500">*</span></label>
          <input type="date" x-model="form.event_date" @change="checkConflicts()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Start Time</label>
          <input type="time" x-model="form.start_time" @change="checkConflicts()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">End Time</label>
          <input type="time" x-model="form.end_time" @change="checkConflicts()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Venue / Location</label>
          <input type="text" x-model="form.venue" @input.debounce.500ms="checkConflicts()" placeholder="e.g., Main Athletics Ground / Auditorium"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">In-Charge Staff Member</label>
          <select x-model="form.in_charge_employee_id" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="">Select In-Charge Staff...</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->designation?->title ?? 'Staff' }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Estimated Budget (₹)</label>
          <input type="number" step="0.01" x-model="form.budget_estimated" placeholder="0.00" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Target Audience</label>
          <select x-model="form.audience" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="all">Entire School</option>
            <option value="students">Students Only</option>
            <option value="staff">Staff Only</option>
            <option value="parents">Parents & Students</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Description & Notes</label>
        <textarea x-model="form.description" rows="4" placeholder="Event schedule, competition rules, participant criteria..."
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('activities.events.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">Cancel</a>
        <button type="submit" :disabled="loading" class="btn-primary px-6 py-2.5 text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50">
          <span x-show="!loading">Schedule Event & Publish</span>
          <span x-show="loading">Saving...</span>
        </button>
      </div>

    </form>
  </div>
</div>

<script>
function eventForm() {
  return {
    loading: false,
    conflicts: [],
    form: {
      name: '',
      event_type: 'academic',
      category: '',
      event_date: '{{ now()->addDays(7)->toDateString() }}',
      start_time: '09:00',
      end_time: '13:00',
      venue: '',
      description: '',
      audience: 'all',
      budget_estimated: 0,
      in_charge_employee_id: '',
    },
    async checkConflicts() {
      if (!this.form.event_date || !this.form.start_time || !this.form.end_time) return;
      try {
        const start = `${this.form.event_date} ${this.form.start_time}`;
        const end   = `${this.form.event_date} ${this.form.end_time}`;
        const res = await fetch(`{{ route('api.calendar.conflicts') }}?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}&venue=${encodeURIComponent(this.form.venue || '')}`);
        const data = await res.json();
        if (data.status === 'success') {
          this.conflicts = data.data || [];
        }
      } catch (e) {}
    },
    async submitEvent() {
      this.loading = true;
      try {
        const payload = { ...this.form };
        if (this.form.in_charge_employee_id) {
          payload.staff = [{ employee_id: this.form.in_charge_employee_id, role: 'in_charge', duties: 'Event In-Charge' }];
        }
        const res = await fetch('{{ route('api.events.store') }}', {
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
          window.location.href = '{{ route('activities.events.index') }}';
        } else {
          alert(data.message || 'Error scheduling event');
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
