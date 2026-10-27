@extends('layouts.app')

@section('title', 'Student Section Allotment & Transfers')

@section('content')
<div class="space-y-6" x-data="{
  selectedUnassigned: [],
  bulkSectionId: '',
  overrideCapacity: false,
  activeTab: 'unassigned',
  showTransferModal: false,
  transferStudentId: null,
  transferStudentName: '',
  transferFromSection: '',
  transferToSectionId: '',
  transferReason: '',
  transferOverride: false,
  openTransfer(id, name, fromSec) {
    this.transferStudentId = id;
    this.transferStudentName = name;
    this.transferFromSection = fromSec;
    this.transferToSectionId = '';
    this.transferReason = '';
    this.transferOverride = false;
    this.showTransferModal = true;
  },
  toggleAllUnassigned(checked) {
    if (checked) {
      this.selectedUnassigned = Array.from(document.querySelectorAll('.unassigned-checkbox')).map(cb => cb.value);
    } else {
      this.selectedUnassigned = [];
    }
  }
}">

  {{-- Header & Navigation --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-rose-800">
          <a href="{{ route('students.index') }}" class="hover:underline">Student Module</a>
          <span>&rsaquo;</span>
          <span class="text-slate-500">Dedicated Section Allotment</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Section Allotment &amp; Transfer</h1>
        <p class="text-sm text-slate-500 mt-0.5">
          Manage class section capacity, assign newly admitted students, and execute safe inter-section transfers with audit tracking.
        </p>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('students.index', ['academic_year_id' => $selectedYearId, 'class_id' => $selectedClassId]) }}" class="btn btn-secondary btn-sm font-semibold">
          &larr; Back to Students
        </a>
        <form method="POST" action="{{ route('students.section-allotment.auto-balance') }}" onsubmit="return confirm('Distribute all unassigned students in this class evenly across active sections with gender balance?');" class="inline">
          @csrf
          <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
          <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
          <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1.5 font-bold bg-rose-900 hover:bg-rose-800 text-white shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Auto-Balance Class Sections
          </button>
        </form>
        <a href="{{ route('students.section-allotment.export', ['academic_year_id' => $selectedYearId, 'class_id' => $selectedClassId]) }}" class="btn btn-ghost btn-sm text-slate-600 border border-slate-200 hover:bg-slate-50">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Export
        </a>
      </div>
    </div>

    {{-- Academic Year and Class Selectors --}}
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-4 text-xs">
      <div class="flex items-center gap-4 flex-wrap">
        {{-- Academic Year Selector --}}
        <form method="GET" action="{{ route('students.section-allotment.index') }}" class="flex items-center gap-2">
          <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
          <span class="font-bold text-slate-600">Session:</span>
          <select name="academic_year_id" onchange="this.form.submit()" class="select select-sm text-xs font-semibold py-1 px-2.5 rounded-lg border-slate-300 bg-slate-50 text-slate-800">
            @foreach($academicYears as $ay)
              <option value="{{ $ay->id }}" @selected($selectedYearId == $ay->id)>
                {{ $ay->name }} {{ $ay->is_current ? '(Current)' : '' }}
              </option>
            @endforeach
          </select>
        </form>

        {{-- Class Selector --}}
        <form method="GET" action="{{ route('students.section-allotment.index') }}" class="flex items-center gap-2">
          <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
          <span class="font-bold text-slate-600">Select Standard / Class:</span>
          <select name="class_id" onchange="this.form.submit()" class="select select-sm text-xs font-black py-1 px-3 rounded-lg border-rose-300 bg-rose-50 text-rose-950 ring-1 ring-rose-200">
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>
                Class {{ $c->name }} ({{ $c->display_name }})
              </option>
            @endforeach
          </select>
        </form>
      </div>

      <div class="flex items-center gap-4 text-slate-500 font-medium">
        <span>Active Sections: <strong class="text-slate-800">{{ $sections->count() }}</strong></span>
        <span>&bull;</span>
        <span>Awaiting Section: <strong class="{{ $unassignedStudents->count() > 0 ? 'text-amber-700 font-bold' : 'text-slate-700' }}">{{ $unassignedStudents->count() }}</strong></span>
      </div>
    </div>
  </div>

  {{-- Section Capacity & Strength Overview Cards --}}
  <div>
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-rose-900"></span>
        Sections in Class {{ $selectedClass?->name }}
      </h3>
      <span class="text-xs text-slate-400">Total Sections: {{ $sections->count() }}</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @forelse($sectionStats as $stat)
        @php
          $sec = $stat->section;
          $pct = $stat->capacity > 0 ? min(100, round(($stat->total / $stat->capacity) * 100)) : 0;
        @endphp
        <div class="bg-white border {{ $stat->is_full ? 'border-rose-300 bg-rose-50/20' : 'border-slate-200/80' }} rounded-2xl p-4 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-base font-black text-slate-900">Section {{ $sec->name }}</span>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $stat->available > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
              {{ $stat->total }} / {{ $stat->capacity }} Seats
            </span>
          </div>

          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="truncate">Teacher: <strong class="text-slate-700">{{ $sec->classTeacher?->name ?? 'Not Assigned' }}</strong></span>
          </p>

          {{-- Capacity Progress Bar --}}
          <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden">
            <div class="h-2 rounded-full {{ $pct >= 90 ? 'bg-rose-600' : ($pct >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
          </div>

          <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 font-semibold">
              <span class="text-blue-700">{{ $stat->male }} Boys</span>
              <span class="text-slate-300">&bull;</span>
              <span class="text-pink-700">{{ $stat->female }} Girls</span>
            </div>

            <div class="text-slate-500 font-medium">
              @if($stat->available > 0)
                <span class="text-emerald-700 font-bold">{{ $stat->available }} seats left</span>
              @else
                <span class="text-rose-700 font-bold">Full</span>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-6 text-center text-slate-400">
          No sections configured for Class {{ $selectedClass?->name }}.
        </div>
      @endforelse
    </div>
  </div>

  {{-- Tabs: Unassigned Students (Awaiting Section) vs Enrolled Section Students --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="border-b border-slate-200 px-5 pt-3 bg-slate-50/70 flex items-center justify-between flex-wrap gap-2">
      <div class="flex items-center gap-1">
        <button type="button"
                @click="activeTab = 'unassigned'"
                class="px-4 py-2.5 font-bold text-xs rounded-t-xl transition-colors border-b-2 flex items-center gap-2"
                :class="activeTab === 'unassigned' ? 'bg-white border-rose-900 text-rose-900 shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800'">
          <span>Awaiting Section Allotment</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $unassignedStudents->count() > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600' }}">
            {{ $unassignedStudents->count() }}
          </span>
        </button>

        <button type="button"
                @click="activeTab = 'assigned'"
                class="px-4 py-2.5 font-bold text-xs rounded-t-xl transition-colors border-b-2 flex items-center gap-2"
                :class="activeTab === 'assigned' ? 'bg-white border-rose-900 text-rose-900 shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800'">
          <span>Enrolled Section Students</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700">
            {{ $assignedStudents->total() }}
          </span>
        </button>
      </div>

      <div class="pb-2 text-xs text-slate-400">
        Class {{ $selectedClass?->name }} &bull; Session {{ $selectedYear?->name }}
      </div>
    </div>

    {{-- TAB 1: Unassigned Students Table --}}
    <div x-show="activeTab === 'unassigned'">
      {{-- Bulk Action Bar for Unassigned Students --}}
      <div x-show="selectedUnassigned.length > 0"
           x-transition
           class="bg-rose-950 text-white px-5 py-3 border-b border-rose-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-rose-700 text-white flex items-center justify-center font-black text-xs" x-text="selectedUnassigned.length"></span>
          <span class="font-bold text-xs">Students selected for section assignment</span>
        </div>

        <form method="POST" action="{{ route('students.section-allotment.bulk-assign') }}" class="flex items-center gap-2 flex-wrap">
          @csrf
          <template x-for="id in selectedUnassigned" :key="id">
            <input type="hidden" name="student_ids[]" :value="id">
          </template>

          <select name="section_id" required class="select select-sm text-xs text-slate-900 bg-white font-semibold">
            <option value="">-- Choose Section --</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}">Section {{ $s->name }}</option>
            @endforeach
          </select>

          <label class="flex items-center gap-1.5 text-xs text-rose-200 cursor-pointer select-none">
            <input type="checkbox" name="override_capacity" value="1" class="rounded border-rose-400 text-rose-600 focus:ring-rose-500">
            <span>Override capacity</span>
          </label>

          <button type="submit" class="btn btn-sm bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-xs">
            Allot Selected
          </button>
        </form>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
              <th class="py-3 px-3 w-10 text-center">
                <input type="checkbox" @change="toggleAllUnassigned($event.target.checked)" class="rounded border-slate-300 text-rose-900 focus:ring-rose-900">
              </th>
              <th class="py-3 px-3">Admission No.</th>
              <th class="py-3 px-4">Student Name</th>
              <th class="py-3 px-3">Gender</th>
              <th class="py-3 px-3">Date of Birth</th>
              <th class="py-3 px-3">Parent Contact</th>
              <th class="py-3 px-4">Direct Assign To Section</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($unassignedStudents as $enr)
              @php $st = $enr->student; @endphp
              <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="py-2.5 px-3 text-center">
                  <input type="checkbox"
                         class="unassigned-checkbox rounded border-slate-300 text-rose-900 focus:ring-rose-900"
                         value="{{ $st->id }}"
                         x-model="selectedUnassigned">
                </td>
                <td class="py-2.5 px-3 font-mono font-bold text-rose-900">
                  <a href="{{ route('students.show', $st->id) }}" class="hover:underline">
                    {{ $st->admission_no }}
                  </a>
                </td>
                <td class="py-2.5 px-4 font-bold text-slate-900">
                  {{ $st->full_name }}
                </td>
                <td class="py-2.5 px-3">
                  @if(strtolower($st->gender) === 'male')
                    <span class="font-bold text-blue-700">Boy</span>
                  @elseif(strtolower($st->gender) === 'female')
                    <span class="font-bold text-pink-700">Girl</span>
                  @else
                    <span class="text-slate-500">—</span>
                  @endif
                </td>
                <td class="py-2.5 px-3 text-slate-600">
                  {{ $st->dob ? $st->dob->format('d/m/Y') : '—' }}
                </td>
                <td class="py-2.5 px-3 font-mono text-slate-600">
                  {{ $st->father_mobile ?: $st->mobile ?: '—' }}
                </td>
                <td class="py-2.5 px-4">
                  <form method="POST" action="{{ route('students.section-allotment.assign') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $st->id }}">
                    <select name="section_id" onchange="this.form.submit()" class="select select-sm text-xs py-1 px-2.5 rounded-lg border-slate-300 font-semibold">
                      <option value="">-- Assign Section --</option>
                      @foreach($sections as $s)
                        <option value="{{ $s->id }}">Section {{ $s->name }}</option>
                      @endforeach
                    </select>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="py-10 text-center text-slate-400">
                  <svg class="w-8 h-8 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <p class="font-bold text-slate-600">All students in this class have been allotted to sections!</p>
                  <p class="text-xs text-slate-400 mt-0.5">Switch to the "Enrolled Section Students" tab to view or transfer students.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- TAB 2: Enrolled Section Students Table (with Transfer button) --}}
    <div x-show="activeTab === 'assigned'">
      {{-- Section Filter Bar --}}
      <div class="px-5 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2 text-xs">
        <form method="GET" action="{{ route('students.section-allotment.index') }}" class="flex items-center gap-2">
          <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
          <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
          <span class="font-bold text-slate-600">Filter by Section:</span>
          <select name="section_id" onchange="this.form.submit()" class="select select-sm text-xs">
            <option value="">All Sections</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>Section {{ $s->name }}</option>
            @endforeach
          </select>
        </form>

        <span class="text-slate-400">Total Enrolled: {{ $assignedStudents->total() }}</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
              <th class="py-3 px-3">Roll No.</th>
              <th class="py-3 px-3">Admission No.</th>
              <th class="py-3 px-4">Student Name</th>
              <th class="py-3 px-3">Current Section</th>
              <th class="py-3 px-3">Gender</th>
              <th class="py-3 px-3">Parent Contact</th>
              <th class="py-3 px-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($assignedStudents as $enr)
              @php $st = $enr->student; @endphp
              <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="py-2.5 px-3 font-mono font-bold text-slate-700 whitespace-nowrap">
                  {{ $enr->roll_number ?: '—' }}
                </td>
                <td class="py-2.5 px-3 font-mono font-bold text-rose-900 whitespace-nowrap">
                  <a href="{{ route('students.show', $st->id) }}" class="hover:underline">
                    {{ $st->admission_no }}
                  </a>
                </td>
                <td class="py-2.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                  {{ $st->full_name }}
                </td>
                <td class="py-2.5 px-3 whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Section {{ $enr->section?->name }}
                  </span>
                </td>
                <td class="py-2.5 px-3 whitespace-nowrap">
                  @if(strtolower($st->gender) === 'male')
                    <span class="font-bold text-blue-700">Boy</span>
                  @elseif(strtolower($st->gender) === 'female')
                    <span class="font-bold text-pink-700">Girl</span>
                  @else
                    <span class="text-slate-500">—</span>
                  @endif
                </td>
                <td class="py-2.5 px-3 font-mono text-slate-600 whitespace-nowrap">
                  {{ $st->father_mobile ?: $st->mobile ?: '—' }}
                </td>
                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                  <button type="button"
                          @click="openTransfer({{ $st->id }}, '{{ addslashes($st->full_name) }}', '{{ $enr->section?->name }}')"
                          class="btn btn-secondary btn-xs font-bold text-rose-900 border-slate-300 hover:bg-slate-100">
                    Transfer Section &rarr;
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="py-8 text-center text-slate-400">
                  No enrolled students found matching the selected section filter.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($assignedStudents->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
          {{ $assignedStudents->links() }}
        </div>
      @endif
    </div>
  </div>

  {{-- Recent Section Allotment & Transfer Audit History --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
    <h3 class="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Section Transfer &amp; Allotment Audit History (Recent Activity)
    </h3>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px]">
            <th class="py-2 px-3">Date &amp; Time</th>
            <th class="py-2 px-3">Student</th>
            <th class="py-2 px-3">From</th>
            <th class="py-2 px-3">To (New Section)</th>
            <th class="py-2 px-3">Reason</th>
            <th class="py-2 px-3">Authorized Staff</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($recentHistory as $hist)
            <tr>
              <td class="py-2 px-3 text-slate-400 font-mono">{{ $hist->created_at->format('d M Y, h:i A') }}</td>
              <td class="py-2 px-3 font-bold text-slate-800">{{ $hist->student?->full_name ?? '—' }}</td>
              <td class="py-2 px-3 text-slate-500">Sec {{ $hist->from_value ?: 'Unassigned' }}</td>
              <td class="py-2 px-3 font-bold text-rose-900">Sec {{ $hist->to_value }}</td>
              <td class="py-2 px-3 text-slate-600">{{ $hist->reason ?: 'Standard assignment' }}</td>
              <td class="py-2 px-3 text-slate-700">{{ $hist->changedBy?->name ?? 'System' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-4 text-center text-slate-400">
                No recent section allotment or transfer history recorded yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Modal: Transfer Section with Mandatory Reason --}}
  <div x-show="showTransferModal"
       x-transition
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
       style="display: none;">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="showTransferModal = false">
      <h3 class="text-lg font-black text-slate-900">Transfer Student Section</h3>
      <p class="text-xs text-slate-500 mt-1">
        Transferring <strong class="text-slate-800" x-text="transferStudentName"></strong> from <span class="font-bold text-slate-700">Section <span x-text="transferFromSection"></span></span>.
      </p>

      <form method="POST" action="{{ route('students.section-allotment.transfer') }}" class="mt-4 space-y-4">
        @csrf
        <input type="hidden" name="student_id" :value="transferStudentId">

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Destination Section *</label>
          <select name="to_section_id" required class="select w-full text-xs font-semibold" x-model="transferToSectionId">
            <option value="">-- Choose New Section --</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}">Section {{ $s->name }} (Capacity: {{ $s->capacity ?? 40 }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Reason for Transfer *</label>
          <textarea name="reason" rows="3" required placeholder="Specify why the student is being transferred to a different section…" class="input w-full text-xs" x-model="transferReason"></textarea>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 space-y-1">
          <p class="font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Important Notice
          </p>
          <p class="text-[11px] text-amber-700">
            Transferring will update class teacher visibility and reset the student's roll number for re-assignment in the new section.
          </p>
          <label class="flex items-center gap-1.5 pt-1 text-slate-800 font-semibold cursor-pointer select-none">
            <input type="checkbox" name="override_capacity" value="1" x-model="transferOverride" class="rounded border-slate-300 text-rose-900 focus:ring-rose-900">
            <span>Override capacity if destination section is full</span>
          </label>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="showTransferModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm font-bold bg-rose-900 hover:bg-rose-800 text-white">Confirm Transfer</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
