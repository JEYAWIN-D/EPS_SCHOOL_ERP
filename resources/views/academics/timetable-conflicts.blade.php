@extends('layouts.app')
@section('title', 'Timetable Conflict Detection & Resolution Tool — Erode Public School')
@section('content')
<div class="space-y-6" x-data="timetableConflictManager()">

  {{-- Page Header --}}
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-rose-200">
          ⚠️
        </span>
        <h1 class="page-title text-xl font-extrabold text-slate-900 tracking-tight">Timetable Collision Detection &amp; Resolution</h1>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Identifies all simultaneous faculty double-bookings and provides one-click resolution (convert to free study hall or reassign to qualified available staff).
      </p>
    </div>

    <div class="flex items-center gap-3">
      {{-- Academic Year Selector --}}
      <form method="GET" action="{{ route('academics.timetable.conflicts') }}" class="flex items-center gap-2">
        <label for="ay_select" class="text-xs font-bold text-slate-600">Year:</label>
        <select id="ay_select" name="academic_year_id" onchange="this.form.submit()"
                class="select text-xs font-semibold py-1.5 px-3 bg-white border border-slate-300 rounded-xl shadow-xs">
          @foreach($academicYears as $ay)
            <option value="{{ $ay->id }}" @selected($ay->id == $selectedYearId)>
              {{ $ay->name ?? ($ay->start_date . ' – ' . $ay->end_date) }} {{ $ay->is_current ? '(Current)' : '' }}
            </option>
          @endforeach
        </select>
      </form>

      <a href="{{ route('classes.index') }}" class="btn btn-secondary text-xs">
        🏫 Class Timetables
      </a>
      <button type="button" onclick="window.location.reload()" class="btn btn-primary text-xs flex items-center gap-1.5">
        <span>🔄</span> Re-scan Collisions
      </button>
    </div>
  </div>

  {{-- Metrics Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="card p-4 border border-rose-200 bg-rose-50/50 flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-2xl font-bold shrink-0">
        🚫
      </div>
      <div>
        <span class="text-2xl font-black text-rose-900" x-text="conflictList.length"></span>
        <span class="text-xs font-bold text-rose-700 block mt-0.5">Simultaneous Collisions</span>
      </div>
    </div>

    <div class="card p-4 border border-amber-200 bg-amber-50/50 flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl font-bold shrink-0">
        👨‍🏫
      </div>
      <div>
        <span class="text-2xl font-black text-amber-900" x-text="getUniqueTeachersCount()"></span>
        <span class="text-xs font-bold text-amber-700 block mt-0.5">Conflicted Faculty</span>
      </div>
    </div>

    <div class="card p-4 border border-emerald-200 bg-emerald-50/50 flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-bold shrink-0">
        🛡️
      </div>
      <div>
        <span class="text-2xl font-black text-emerald-900" x-text="resolvedCount"></span>
        <span class="text-xs font-bold text-emerald-700 block mt-0.5">Slots Resolved This Session</span>
      </div>
    </div>
  </div>

  {{-- Zero Conflicts Notice --}}
  <template x-if="conflictList.length === 0">
    <div class="card bg-emerald-50 border border-emerald-200 p-8 text-center space-y-3">
      <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-bold shadow-sm">
        ✓
      </div>
      <div>
        <h3 class="text-lg font-extrabold text-emerald-900">Zero Timetable Collisions Detected!</h3>
        <p class="text-xs text-emerald-700 mt-1 max-w-md mx-auto">
          Every teacher assignment in this academic year is strictly unique for every day and period. No faculty member is scheduled to teach multiple classes simultaneously.
        </p>
      </div>
      <div class="pt-2">
        <a href="{{ route('classes.index') }}" class="btn btn-secondary text-xs">Return to Class Timetables</a>
      </div>
    </div>
  </template>

  {{-- Conflicts Table & Interactive Resolution --}}
  <template x-if="conflictList.length > 0">
    <div class="space-y-4">
      <div class="alert-danger flex items-center justify-between p-4 rounded-xl bg-rose-50 border border-rose-300 text-rose-900">
        <div class="flex items-center gap-2.5">
          <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <span class="text-xs font-semibold">
            <strong x-text="conflictList.length"></strong> timetable conflict group(s) require administrative resolution. Use the quick actions below to resolve each colliding slot.
          </span>
        </div>
      </div>

      <div class="space-y-4">
        <template x-for="(conflict, cIndex) in conflictList" :key="conflict.key">
          <div class="card p-5 border-2 border-rose-200 bg-white rounded-2xl shadow-sm space-y-4">
            {{-- Conflict Card Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-rose-100 pb-3">
              <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 text-xs font-mono font-bold">
                  Group #<span x-text="cIndex + 1"></span>
                </span>
                <div>
                  <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span x-text="conflict.teacher_name"></span>
                    <span class="text-[11px] font-mono text-slate-500 font-normal" x-text="'(' + conflict.teacher_code + ')'"></span>
                  </h3>
                  <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                    <span class="font-bold text-indigo-700" x-text="conflict.day_name"></span>
                    <span>•</span>
                    <span class="font-bold text-slate-700" x-text="'Period ' + conflict.period_number"></span>
                    <span class="text-[11px] text-slate-400" x-text="'(' + conflict.start_time.substring(0, 5) + ' – ' + conflict.end_time.substring(0, 5) + ')'"></span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                  <span x-text="conflict.conflicting_entries.length"></span> Classes Colliding Simultaneously
                </span>
              </div>
            </div>

            {{-- Colliding Slots Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <template x-for="(entry, eIndex) in conflict.conflicting_entries" :key="entry.timetable_id">
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-50 flex flex-col justify-between gap-3">
                  <div class="space-y-1">
                    <div class="flex items-center justify-between">
                      <span class="text-xs font-extrabold text-slate-900" x-text="'Class ' + entry.class_name + '-' + entry.section_name"></span>
                      <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold" x-text="entry.subject_name"></span>
                    </div>
                    <div class="text-[11px] text-slate-500 flex items-center gap-2">
                      <span>🏫 <span x-text="entry.room || 'Classroom'"></span></span>
                      <span>•</span>
                      <span class="font-mono text-[10px] text-slate-400" x-text="'ID #' + entry.timetable_id"></span>
                    </div>
                  </div>

                  <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    {{-- Convert to Free Period --}}
                    <button type="button" @click="resolveAsFreePeriod(entry, conflict, cIndex, eIndex)"
                            :disabled="isResolving"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition flex items-center gap-1 cursor-pointer">
                      <span>✨ Free Period</span>
                    </button>

                    {{-- Reassign Teacher --}}
                    <button type="button" @click="openReassignModal(entry, conflict)"
                            :disabled="isResolving"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition flex items-center gap-1 cursor-pointer">
                      <span>🔄 Reassign</span>
                    </button>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </template>
      </div>
    </div>
  </template>

  {{-- Reassign Faculty Modal --}}
  <template x-teleport="body">
    <div x-show="reassignModal.show"
         class="fixed inset-0 z-[10050] flex items-center justify-center p-4"
         style="display:none; background:rgba(15, 23, 42, 0.85); backdrop-filter:blur(5px);"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
      <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 space-y-4"
           @click.stop>
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-bold">
              🔄
            </div>
            <div>
              <h4 class="text-sm font-extrabold text-slate-900">Reassign Conflicted Period</h4>
              <p class="text-xs text-slate-500" x-text="reassignModal.entry ? ('Class ' + reassignModal.entry.class_name + '-' + reassignModal.entry.section_name + ' • ' + reassignModal.entry.subject_name) : ''"></p>
            </div>
          </div>
          <button type="button" @click="reassignModal.show = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <div class="space-y-3">
          <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-200 text-xs text-blue-900">
            <span>Select a qualified faculty member who has <strong>NO existing timetable assignments</strong> during this period and has not exceeded their daily teaching quota.</span>
          </div>

          <div>
            <label class="label text-xs font-semibold mb-1 block">Available Faculty:</label>
            <div x-show="reassignModal.loading" class="text-xs text-slate-500 py-3 text-center">
              <span>🌀 Scanning teacher availability &amp; workload...</span>
            </div>
            <div x-show="!reassignModal.loading">
              <select x-model.number="reassignModal.newTeacherId" class="select text-xs w-full">
                <option value="">-- Select Available Replacement Faculty --</option>
                <optgroup label="✅ Certified &amp; Available Teachers">
                  <template x-for="st in reassignModal.availableStaff" :key="st.id">
                    <option :value="st.id" x-text="st.name + ' (' + (st.designation || 'Faculty') + ') • Today: ' + st.current_daily_periods + '/' + st.max_daily_periods"></option>
                  </template>
                </optgroup>
                <template x-if="reassignModal.blockedStaff && reassignModal.blockedStaff.length > 0">
                  <optgroup label="🚫 Unavailable / Conflicted">
                    <template x-for="st in reassignModal.blockedStaff" :key="st.id">
                      <option :value="st.id" disabled class="text-rose-400 bg-slate-100" x-text="'⛔ ' + st.name + ' — ' + st.blocked_reason"></option>
                    </template>
                  </optgroup>
                </template>
              </select>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <button type="button" @click="reassignModal.show = false"
                  class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
            Cancel
          </button>
          <button type="button" @click="submitReassign()"
                  :disabled="!reassignModal.newTeacherId || reassignModal.submitting"
                  class="px-5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="reassignModal.submitting" class="animate-spin">🌀</span>
            <span x-text="reassignModal.submitting ? 'Reassigning...' : 'Confirm Reassignment'"></span>
          </button>
        </div>
      </div>
    </div>
  </template>

</div>

<script>
function timetableConflictManager() {
  return {
    conflictList: @json($conflicts),
    resolvedCount: 0,
    isResolving: false,
    reassignModal: {
      show: false,
      loading: false,
      submitting: false,
      entry: null,
      conflict: null,
      availableStaff: [],
      blockedStaff: [],
      newTeacherId: null
    },

    getUniqueTeachersCount() {
      const ids = new Set();
      this.conflictList.forEach(c => ids.add(c.teacher_id));
      return ids.size;
    },

    async resolveAsFreePeriod(entry, conflict, cIndex, eIndex) {
      if (!confirm(`Convert Class ${entry.class_name}-${entry.section_name} Period ${conflict.period_number} to Free Period / Study Hall? This will safely remove the teacher double-booking.`)) {
        return;
      }

      this.isResolving = true;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("classes.timetable.resolve-conflict") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            timetable_id: entry.timetable_id,
            resolution: 'free_period'
          })
        });

        const data = await res.json();
        if (data.success) {
          this.removeResolvedEntry(conflict, cIndex, eIndex);
          this.resolvedCount++;
          window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Slot converted to Free Period.', type: 'success' } }));
        } else {
          alert(data.message || 'Failed to resolve conflict.');
        }
      } catch (err) {
        console.error('Resolve error:', err);
        alert('Network error while resolving conflict.');
      } finally {
        this.isResolving = false;
      }
    },

    async openReassignModal(entry, conflict) {
      this.reassignModal.entry = entry;
      this.reassignModal.conflict = conflict;
      this.reassignModal.newTeacherId = null;
      this.reassignModal.availableStaff = [];
      this.reassignModal.blockedStaff = [];
      this.reassignModal.loading = true;
      this.reassignModal.show = true;

      try {
        const url = `{{ route('classes.timetable.eligible-staff') }}?class_id=${entry.class_id}&section_id=${entry.section_id || ''}&subject_id=${entry.subject_id || ''}&day_of_week=${conflict.day_of_week}&period_number=${conflict.period_number}`;
        const res = await fetch(url, {
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
          this.reassignModal.availableStaff = data.available_staff || [];
          this.reassignModal.blockedStaff = data.blocked_staff || [];
        }
      } catch (err) {
        console.error('Error loading eligible staff:', err);
      } finally {
        this.reassignModal.loading = false;
      }
    },

    async submitReassign() {
      if (!this.reassignModal.newTeacherId || !this.reassignModal.entry) return;

      this.reassignModal.submitting = true;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("classes.timetable.resolve-conflict") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            timetable_id: this.reassignModal.entry.timetable_id,
            resolution: 'reassign',
            new_teacher_id: this.reassignModal.newTeacherId
          })
        });

        const data = await res.json();
        if (data.success) {
          // Remove from local list
          const cIndex = this.conflictList.indexOf(this.reassignModal.conflict);
          if (cIndex !== -1) {
            const eIndex = this.reassignModal.conflict.conflicting_entries.indexOf(this.reassignModal.entry);
            this.removeResolvedEntry(this.reassignModal.conflict, cIndex, eIndex);
          }
          this.resolvedCount++;
          this.reassignModal.show = false;
          window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Teacher reassigned successfully.', type: 'success' } }));
        } else {
          alert(data.message || 'Failed to reassign teacher.');
        }
      } catch (err) {
        console.error('Reassign error:', err);
        alert('Network error while reassigning teacher.');
      } finally {
        this.reassignModal.submitting = false;
      }
    },

    removeResolvedEntry(conflict, cIndex, eIndex) {
      if (eIndex !== -1) {
        conflict.conflicting_entries.splice(eIndex, 1);
      }
      // If only 1 entry remains in the group, there's no collision anymore!
      if (conflict.conflicting_entries.length <= 1) {
        this.conflictList.splice(cIndex, 1);
      }
    }
  };
}
</script>
@endsection
