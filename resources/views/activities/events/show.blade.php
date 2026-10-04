@extends('layouts.admin')

@section('title', $event->name . ' — Event Details')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.events.index') }}" class="hover:text-indigo-600">Events</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Details</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12" x-data="eventShow()">

  {{-- Top Navigation & Actions --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('activities.events.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
      &larr; Back to Events List
    </a>

    <div class="flex items-center gap-2">
      @if($event->status !== 'completed' && $event->status !== 'cancelled')
        <button @click="showCompleteModal = true" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
          Mark Completed & Reconcile Expenses
        </button>
      @endif

      @if(auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']))
        <form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Permanently delete \'{{ addslashes($event->name) }}\'? This action cannot be undone.')" class="inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 font-bold text-xs shadow-xs transition inline-flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete Event
          </button>
        </form>
      @endif
    </div>
  </div>

  {{-- Event Main Banner Card --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-6 sm:p-8">
      <div class="flex flex-wrap items-center gap-2.5 mb-3">
        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider
          {{ $event->event_type === 'sports' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($event->event_type === 'academic' ? 'bg-indigo-100 text-indigo-800' : 'bg-pink-100 text-pink-800') }}">
          {{ $event->event_type }}
        </span>
        @if($event->category)
          <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
            {{ $event->category }}
          </span>
        @endif
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold
          {{ $event->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
          Status: {{ ucfirst($event->status) }}
        </span>
      </div>

      <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
        {{ $event->name }}
      </h1>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
        <div>
          <span class="text-slate-400 block font-medium">Date & Time</span>
          <span class="font-bold text-slate-800 mt-0.5 block">
            {{ $event->event_date->format('d M Y') }}
            @if($event->start_time)
              &bull; {{ date('h:i A', strtotime($event->start_time)) }} - {{ date('h:i A', strtotime($event->end_time)) }}
            @endif
          </span>
        </div>
        <div>
          <span class="text-slate-400 block font-medium">Venue / Ground</span>
          <span class="font-bold text-slate-800 mt-0.5 block">{{ $event->venue ?? 'School Campus' }}</span>
        </div>
        <div>
          <span class="text-slate-400 block font-medium">Budget Reconciliation</span>
          <span class="font-bold text-slate-800 mt-0.5 block">
            ₹{{ number_format($event->budget_actual, 2) }} / ₹{{ number_format($event->budget_estimated, 2) }}
          </span>
        </div>
      </div>

      <div class="mt-6 prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed whitespace-pre-line">
        {{ $event->description ?? 'No extra event notes provided.' }}
      </div>
    </div>
  </div>

  {{-- In-Charge Staff and Coordinators --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
    <h3 class="text-sm font-bold text-slate-800 mb-4">Responsible & In-Charge Staff Members</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      @forelse($event->staff as $st)
        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm flex-shrink-0">
            {{ substr($st->employee?->first_name ?? 'S', 0, 1) }}
          </div>
          <div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
              {{ $st->role === 'in_charge' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
              {{ str_replace('_', ' ', $st->role) }}
            </span>
            <p class="text-xs font-bold text-slate-800 mt-1">{{ $st->employee?->full_name }}</p>
            <p class="text-[11px] text-slate-500">{{ $st->duties ?? 'Coordinator' }}</p>
          </div>
        </div>
      @empty
        <div class="col-span-full text-center py-6 text-slate-400 text-xs">
          No specific in-charge staff appointed.
        </div>
      @endforelse
    </div>
  </div>

  {{-- Post-Event Completion Modal --}}
  <div x-show="showCompleteModal" x-transition class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="showCompleteModal = false">
      <h3 class="text-base font-bold text-slate-900">Mark Event Completed & Enter Expenses</h3>
      <p class="text-xs text-slate-500">Record final verified expenses and post-event completion summary.</p>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Actual Expenses Incurred (₹)</label>
        <input type="number" step="0.01" x-model="completeForm.budget_actual" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Post-Event Summary & Outcome Report</label>
        <textarea x-model="completeForm.post_event_report" rows="4" placeholder="Summary of event execution, student participation, awards, and feedback..."
                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm leading-relaxed"></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button @click="showCompleteModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
        <button @click="submitCompletion()" :disabled="completing" class="btn-primary px-5 py-2 rounded-xl text-xs font-bold shadow-xs">
          <span x-show="!completing">Save & Finalize</span>
          <span x-show="completing">Saving...</span>
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function eventShow() {
  return {
    showCompleteModal: false,
    completing: false,
    completeForm: {
      budget_actual: '{{ $event->budget_actual }}',
      post_event_report: '{{ addslashes($event->post_event_report ?? '') }}',
    },
    async submitCompletion() {
      this.completing = true;
      try {
        const res = await fetch('{{ route('api.events.complete', $event->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify(this.completeForm),
        });
        const data = await res.json();
        if (data.status === 'success') {
          window.location.reload();
        } else {
          alert(data.message || 'Error completing event');
        }
      } catch (err) {
        alert('Failed: ' + err.message);
      } finally {
        this.completing = false;
      }
    }
  }
}
</script>
@endsection
