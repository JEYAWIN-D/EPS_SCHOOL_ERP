@extends('layouts.admin')

@section('title', $meeting->title . ' — Meeting Desk')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.meetings.index') }}" class="hover:text-indigo-600">Meetings</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Desk</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12" x-data="meetingDesk()">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('activities.meetings.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
      &larr; Back to Meetings List
    </a>

    <div class="flex items-center gap-2">
      @if($meeting->status !== 'completed' && $meeting->status !== 'cancelled')
        <button @click="showMoMModal = true" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition">
          Record Minutes of Meeting (MoM)
        </button>
      @endif
      <button @click="showActionItemModal = true" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition border border-slate-200">
        + Add Action Item Task
      </button>
    </div>
  </div>

  {{-- Meeting Header Card --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-6 sm:p-8">
      <div class="flex flex-wrap items-center gap-2.5 mb-3">
        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800">
          {{ $meeting->meeting_type }} Meeting
        </span>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold
          {{ $meeting->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
          {{ ucfirst($meeting->status) }}
        </span>
      </div>

      <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
        {{ $meeting->title }}
      </h1>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
        <div>
          <span class="text-slate-400 block font-medium">Date & Time</span>
          <span class="font-bold text-slate-800 mt-0.5 block">
            {{ $meeting->start_time->format('d M Y') }} &bull; {{ $meeting->start_time->format('h:i A') }} - {{ $meeting->end_time->format('h:i A') }}
          </span>
        </div>
        <div>
          <span class="text-slate-400 block font-medium">Venue / Room</span>
          <span class="font-bold text-slate-800 mt-0.5 block">{{ $meeting->venue ?? 'Campus' }}</span>
        </div>
        <div>
          <span class="text-slate-400 block font-medium">Chairperson</span>
          <span class="font-bold text-slate-800 mt-0.5 block">{{ $meeting->chairperson?->name ?? 'Principal' }}</span>
        </div>
      </div>

      @if($meeting->agenda)
        <div class="mt-5">
          <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Agenda</h4>
          <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50 p-3.5 rounded-xl border border-slate-100">{{ $meeting->agenda }}</p>
        </div>
      @endif
    </div>

    {{-- RSVP Bar --}}
    @if($myRsvp)
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div>
          <span class="font-bold text-slate-700">Your RSVP Status:</span>
          <span class="font-bold uppercase px-2 py-0.5 rounded text-[10px] ml-1
            {{ $myRsvp->rsvp_status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : ($myRsvp->rsvp_status === 'declined' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
            {{ $myRsvp->rsvp_status }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="updateRsvp('accepted')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition">Accept</button>
          <button @click="updateRsvp('tentative')" class="px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold transition">Tentative</button>
          <button @click="updateRsvp('declined')" class="px-3 py-1.5 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-800 font-bold transition">Decline</button>
        </div>
      </div>
    @endif
  </div>

  {{-- Attendees & Attendance Table --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-800">Invited Attendees & Attendance</h3>
      <span class="text-xs text-slate-400">{{ $meeting->attendees->count() }} Members</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200/70 text-slate-500 uppercase font-semibold text-[10px] tracking-wider">
            <th class="py-3 px-4">Attendee Name</th>
            <th class="py-3 px-4">RSVP Status</th>
            <th class="py-3 px-4">Attendance</th>
            <th class="py-3 px-4 text-right">Mark</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($meeting->attendees as $att)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4 font-semibold text-slate-800">{{ $att->user?->name }}</td>
              <td class="py-3 px-4 capitalize">{{ $att->rsvp_status }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                  {{ $att->attendance_status === 'present' ? 'bg-emerald-100 text-emerald-800' : ($att->attendance_status === 'absent' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600') }}">
                  {{ $att->attendance_status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <div class="inline-flex gap-1">
                  <button @click="markAttendance({{ $att->user_id }}, 'present')" class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px]">Present</button>
                  <button @click="markAttendance({{ $att->user_id }}, 'absent')" class="px-2 py-0.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[10px]">Absent</button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Minutes of Meeting (MoM) Display --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
    <h3 class="text-sm font-bold text-slate-800 mb-3">Minutes of Meeting (MoM)</h3>
    @forelse($meeting->minutes as $min)
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 mb-3 space-y-2">
        <div class="flex items-center justify-between text-xs text-slate-400">
          <span>Recorded by: <strong class="text-slate-700">{{ $min->recorder?->name }}</strong></span>
          <span>{{ $min->created_at->format('d M Y, h:i A') }}</span>
        </div>
        <p class="text-xs text-slate-800 leading-relaxed whitespace-pre-line">{{ $min->summary }}</p>
        @if($min->key_decisions)
          <div class="pt-2 border-t border-slate-200 text-xs">
            <span class="font-bold text-slate-700">Key Decisions:</span>
            <p class="text-slate-600 mt-0.5">{{ $min->key_decisions }}</p>
          </div>
        @endif
      </div>
    @empty
      <p class="text-xs text-slate-400 italic">No meeting minutes recorded yet.</p>
    @endforelse
  </div>

  {{-- Action Items & Converted Tasks --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-800">Action Items (Converted into Tasks)</h3>
      <button @click="showActionItemModal = true" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">+ New Action Item</button>
    </div>

    <div class="space-y-2">
      @forelse($meeting->tasks as $t)
        <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between gap-3 text-xs">
          <div>
            <span class="font-bold text-slate-800">{{ $t->title }}</span>
            <span class="text-slate-400 block text-[11px]">Assigned to: {{ $t->assignees->first()?->user?->name ?? 'Staff' }} | Status: {{ $t->status }}</span>
          </div>
          <a href="{{ route('activities.tasks.show', $t->id) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">View Task &rarr;</a>
        </div>
      @empty
        <p class="text-xs text-slate-400 italic">No action item tasks generated from this meeting yet.</p>
      @endforelse
    </div>
  </div>

  {{-- MoM Record Modal --}}
  <div x-show="showMoMModal" x-transition class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="showMoMModal = false">
      <h3 class="text-base font-bold text-slate-900">Record Minutes of Meeting (MoM)</h3>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Meeting Discussion Summary <span class="text-red-500">*</span></label>
        <textarea x-model="momForm.summary" rows="4" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Key Resolutions & Decisions</label>
        <textarea x-model="momForm.key_decisions" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button @click="showMoMModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
        <button @click="submitMoM()" class="btn-primary px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Save MoM & Mark Complete</button>
      </div>
    </div>
  </div>

  {{-- Action Item Modal --}}
  <div x-show="showActionItemModal" x-transition class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="showActionItemModal = false">
      <h3 class="text-base font-bold text-slate-900">Create Action Item (Auto-Convert to Task)</h3>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Action Item Title <span class="text-red-500">*</span></label>
        <input type="text" x-model="actionItem.title" placeholder="e.g., Update physics lab chemical safety stock" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Assignee <span class="text-red-500">*</span></label>
        <select x-model="actionItem.assignee_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Select Assignee...</option>
          @foreach($staffUsers as $su)
            <option value="{{ $su->id }}">{{ $su->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Due Date</label>
          <input type="date" x-model="actionItem.due_date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Priority</label>
          <select x-model="actionItem.priority" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button @click="showActionItemModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
        <button @click="submitActionItem()" class="btn-primary px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Convert to Task & Assign</button>
      </div>
    </div>
  </div>

</div>

<script>
function meetingDesk() {
  return {
    showMoMModal: false,
    showActionItemModal: false,
    momForm: { summary: '', key_decisions: '' },
    actionItem: { title: '', assignee_id: '', due_date: '{{ now()->addDays(5)->toDateString() }}', priority: 'normal' },
    async updateRsvp(status) {
      try {
        const res = await fetch('{{ route('api.meetings.rsvp', $meeting->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ status: status }),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async markAttendance(userId, status) {
      try {
        const res = await fetch('{{ route('api.meetings.attendance', $meeting->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ attendance: [{ user_id: userId, status: status }] }),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async submitMoM() {
      try {
        const res = await fetch('{{ route('api.meetings.minutes', $meeting->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify(this.momForm),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    },
    async submitActionItem() {
      try {
        const res = await fetch('{{ route('api.meetings.action-items', $meeting->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ action_items: [this.actionItem] }),
        });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
      } catch (e) { alert(e.message); }
    }
  }
}
</script>
@endsection
