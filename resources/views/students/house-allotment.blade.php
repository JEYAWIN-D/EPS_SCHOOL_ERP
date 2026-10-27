@extends('layouts.app')

@section('title', 'Student House Allotment')

@section('content')
<div class="space-y-6" x-data="{
  selectedStudents: [],
  bulkHouseId: '',
  showRemoveModal: false,
  removeStudentId: null,
  removeStudentName: '',
  removeReason: '',
  showHouseModal: false,
  houseForm: { id: null, name: '', code: '', color: '#8C2826', captain_student_id: '', vice_captain_student_id: '', teacher_id: '', capacity: '', description: '' },
  editHouse(h) {
    this.houseForm = {
      id: h.id,
      name: h.name,
      code: h.code,
      color: h.color,
      captain_student_id: h.captain_student_id || '',
      vice_captain_student_id: h.vice_captain_student_id || '',
      teacher_id: h.teacher_id || '',
      capacity: h.capacity || '',
      description: h.description || ''
    };
    this.showHouseModal = true;
  },
  resetHouseForm() {
    this.houseForm = { id: null, name: '', code: '', color: '#8C2826', captain_student_id: '', vice_captain_student_id: '', teacher_id: '', capacity: '', description: '' };
    this.showHouseModal = true;
  },
  confirmRemove(id, name) {
    this.removeStudentId = id;
    this.removeStudentName = name;
    this.removeReason = '';
    this.showRemoveModal = true;
  },
  toggleAll(checked) {
    if (checked) {
      this.selectedStudents = Array.from(document.querySelectorAll('.student-checkbox')).map(cb => cb.value);
    } else {
      this.selectedStudents = [];
    }
  }
}">

  {{-- Header & Sub-navigation --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-rose-800">
          <a href="{{ route('students.index') }}" class="hover:underline">Student Module</a>
          <span>&rsaquo;</span>
          <span class="text-slate-500">Dedicated House Allotment</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Student House Allotment</h1>
        <p class="text-sm text-slate-500 mt-0.5">
          Manage school houses, assign students individually or in bulk, and balance house distributions.
        </p>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('students.index', ['academic_year_id' => $selectedYearId]) }}" class="btn btn-secondary btn-sm font-semibold">
          &larr; Back to Students
        </a>
        <button type="button" @click="resetHouseForm()" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-semibold text-slate-700">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
          Manage / Add House
        </button>
        <form method="POST" action="{{ route('students.house-allotment.auto-balance') }}" onsubmit="return confirm('Automatically distribute unallotted students evenly among all active houses with gender balance?');" class="inline">
          @csrf
          <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
          @if(request('class_id'))
            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
          @endif
          <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1.5 font-bold bg-rose-900 hover:bg-rose-800 text-white shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Auto-Balance All Unallotted
          </button>
        </form>
        <a href="{{ route('students.house-allotment.export', request()->query()) }}" class="btn btn-ghost btn-sm text-slate-600 border border-slate-200 hover:bg-slate-50">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Export
        </a>
      </div>
    </div>

    {{-- Academic Year Quick Selector --}}
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3 text-xs">
      <div class="flex items-center gap-2">
        <span class="font-bold text-slate-600">Academic Year:</span>
        <form method="GET" action="{{ route('students.house-allotment.index') }}" class="inline">
          @foreach(request()->except('academic_year_id') as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
          <select name="academic_year_id" onchange="this.form.submit()" class="select select-sm text-xs font-semibold py-1 px-2.5 rounded-lg border-slate-300 bg-slate-50 text-slate-800">
            @foreach($academicYears as $ay)
              <option value="{{ $ay->id }}" @selected($selectedYearId == $ay->id)>
                {{ $ay->name }} {{ $ay->is_current ? '(Current)' : '' }}
              </option>
            @endforeach
          </select>
        </form>
      </div>

      <div class="text-xs text-slate-500">
        Total Houses: <strong class="text-slate-800">{{ $houses->count() }}</strong> &bull;
        Unallotted Students: <strong class="{{ $unallottedCount > 0 ? 'text-amber-700 font-bold' : 'text-slate-700' }}">{{ $unallottedCount }}</strong>
      </div>
    </div>
  </div>

  {{-- House Overview Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    @foreach($houseStats as $stat)
      @php
        $h = $stat->house;
        $color = $h->color ?: '#8C2826';
      @endphp
      <div class="bg-white border rounded-2xl p-4 shadow-xs relative overflow-hidden flex flex-col justify-between"
           style="border-color: {{ $color }}40;">
        <div class="absolute top-0 left-0 right-0 h-1.5" style="background-color: {{ $color }};"></div>

        <div>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xs font-black uppercase tracking-wider" style="color: {{ $color }};">
              {{ $h->code }}
            </span>
            <button type="button" @click="editHouse({{ json_encode($h) }})" class="text-slate-400 hover:text-slate-600 p-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </button>
          </div>

          <h3 class="font-black text-slate-900 text-lg mt-0.5">{{ $h->name }}</h3>

          <div class="text-3xl font-black text-slate-900 mt-2">
            {{ number_format($stat->total) }}
            <span class="text-xs font-bold text-slate-400">students</span>
          </div>

          <div class="flex items-center gap-2 mt-2 text-xs font-bold">
            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700">{{ $stat->male }} Boys</span>
            <span class="px-2 py-0.5 rounded bg-pink-50 text-pink-700">{{ $stat->female }} Girls</span>
          </div>

          <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 space-y-1">
            <div class="truncate">
              Teacher: <strong class="text-slate-700">{{ $h->teacher?->name ?? 'Not Assigned' }}</strong>
            </div>
            <div class="truncate">
              Captain: <strong class="text-slate-700">{{ $h->captain?->full_name ?? 'Not Assigned' }}</strong>
            </div>
          </div>
        </div>

        <div class="mt-3 pt-2">
          <a href="{{ route('students.house-allotment.index', ['house_filter' => $h->id, 'academic_year_id' => $selectedYearId]) }}"
             class="btn btn-ghost btn-xs w-full text-center font-bold border border-slate-200 hover:bg-slate-50"
             style="color: {{ $color }};">
            Filter This House &rarr;
          </a>
        </div>
      </div>
    @endforeach

    {{-- Unallotted Students Card --}}
    <div class="bg-white border {{ $unallottedCount > 0 ? 'border-amber-300 bg-amber-50/20 ring-1 ring-amber-200' : 'border-slate-200' }} rounded-2xl p-4 shadow-xs flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between text-amber-700">
          <span class="text-xs font-black uppercase tracking-wider">Unallotted</span>
          <span class="p-1 rounded-md bg-amber-100 text-amber-800">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </span>
        </div>
        <h3 class="font-black text-slate-900 text-lg mt-0.5">Awaiting House</h3>
        <div class="text-3xl font-black {{ $unallottedCount > 0 ? 'text-amber-800' : 'text-slate-900' }} mt-2">
          {{ number_format($unallottedCount) }}
          <span class="text-xs font-bold text-slate-400">students</span>
        </div>
        <p class="text-xs text-slate-500 mt-2">
          Students currently enrolled in the session who do not have an active house assignment.
        </p>
      </div>

      <div class="mt-4">
        <a href="{{ route('students.house-allotment.index', ['house_filter' => 'unallotted', 'academic_year_id' => $selectedYearId]) }}"
           class="btn btn-secondary btn-xs w-full text-center font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border-amber-300">
          View Unallotted &rarr;
        </a>
      </div>
    </div>
  </div>

  {{-- Filters Bar --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
    <form method="GET" action="{{ route('students.house-allotment.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
      <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">

      {{-- Search --}}
      <div class="col-span-1 sm:col-span-2">
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Search Student</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, admission no, roll no…" class="input w-full text-sm">
      </div>

      {{-- Class --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Class</label>
        <select name="class_id" class="select w-full text-sm">
          <option value="">All Classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- House Filter --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">House</label>
        <select name="house_filter" class="select w-full text-sm">
          <option value="">All Houses</option>
          @foreach($houses as $h)
            <option value="{{ $h->id }}" @selected(request('house_filter') == $h->id)>{{ $h->name }}</option>
          @endforeach
          <option value="unallotted" @selected(request('house_filter') === 'unallotted')>&mdash; Unallotted &mdash;</option>
        </select>
      </div>

      {{-- Gender --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Gender</label>
        <select name="gender" class="select w-full text-sm">
          <option value="">All Genders</option>
          <option value="male" @selected(request('gender') === 'male')>Boys</option>
          <option value="female" @selected(request('gender') === 'female')>Girls</option>
        </select>
      </div>

      {{-- Buttons --}}
      <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-1 font-bold bg-rose-900 hover:bg-rose-800 text-white">
          Filter
        </button>
        @if(request()->hasAny(['search', 'class_id', 'house_filter', 'gender']))
          <a href="{{ route('students.house-allotment.index', ['academic_year_id' => $selectedYearId]) }}" class="btn btn-ghost btn-sm text-slate-500 border border-slate-200">
            Clear
          </a>
        @endif
      </div>
    </form>
  </div>

  {{-- Bulk Action Bar (when students are selected) --}}
  <div x-show="selectedStudents.length > 0"
       x-transition
       class="bg-rose-950 text-white px-5 py-3.5 rounded-2xl shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-3 sticky top-4 z-30">
    <div class="flex items-center gap-3">
      <span class="w-7 h-7 rounded-full bg-rose-800 text-white flex items-center justify-center font-black text-xs" x-text="selectedStudents.length"></span>
      <div>
        <h4 class="font-bold text-sm">Students Selected</h4>
        <p class="text-xs text-rose-200">Choose a destination house for bulk allotment.</p>
      </div>
    </div>

    <form method="POST" action="{{ route('students.house-allotment.bulk-assign') }}" class="flex items-center gap-2">
      @csrf
      <template x-for="id in selectedStudents" :key="id">
        <input type="hidden" name="student_ids[]" :value="id">
      </template>

      <select name="house_id" required class="select select-sm text-xs text-slate-900 bg-white font-semibold">
        <option value="">-- Choose House --</option>
        @foreach($houses as $h)
          <option value="{{ $h->id }}">{{ $h->name }}</option>
        @endforeach
      </select>

      <button type="submit" class="btn btn-sm bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-xs">
        Apply Bulk Allotment
      </button>

      <button type="button" @click="selectedStudents = []" class="btn btn-ghost btn-sm text-rose-200 hover:text-white">
        Cancel
      </button>
    </form>
  </div>

  {{-- Students Roster Table --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
      <h3 class="font-bold text-slate-800 text-sm">House Allotment Roster</h3>
      <span class="text-xs text-slate-400">Total: {{ $students->total() }}</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
            <th class="py-3 px-3 w-10 text-center">
              <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded border-slate-300 text-rose-900 focus:ring-rose-900">
            </th>
            <th class="py-3 px-3">Admission No.</th>
            <th class="py-3 px-3">Roll No.</th>
            <th class="py-3 px-4">Student Name</th>
            <th class="py-3 px-3">Class &amp; Section</th>
            <th class="py-3 px-3">Gender</th>
            <th class="py-3 px-3">Current House</th>
            <th class="py-3 px-4">Assign / Change House</th>
            <th class="py-3 px-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($students as $enr)
            @php
              $st = $enr->student;
              $hObj = $enr->houseGroup;
              $hName = $hObj?->name ?: ($enr->house ?: null);
              $color = $hObj?->color ?? '#64748B';
            @endphp
            <tr class="hover:bg-slate-50/60 transition-colors">
              <td class="py-2.5 px-3 text-center">
                <input type="checkbox"
                       class="student-checkbox rounded border-slate-300 text-rose-900 focus:ring-rose-900"
                       value="{{ $st->id }}"
                       x-model="selectedStudents">
              </td>

              <td class="py-2.5 px-3 font-mono font-bold text-rose-900 whitespace-nowrap">
                <a href="{{ route('students.show', $st->id) }}" class="hover:underline">
                  {{ $st->admission_no }}
                </a>
              </td>

              <td class="py-2.5 px-3 font-mono text-slate-700 whitespace-nowrap">
                {{ $enr->roll_number ?: '—' }}
              </td>

              <td class="py-2.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                {{ $st->full_name }}
              </td>

              <td class="py-2.5 px-3 font-semibold text-slate-800 whitespace-nowrap">
                {{ $enr->class?->name ?? '—' }} &bull; Sec {{ $enr->section?->name ?? 'None' }}
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

              <td class="py-2.5 px-3 whitespace-nowrap">
                @if($hName)
                  <span class="inline-flex items-center gap-1.5 font-bold text-[11px] px-2 py-0.5 rounded-md border"
                        style="color: {{ $color }}; border-color: {{ $color }}40; background-color: {{ $color }}12;">
                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $color }};"></span>
                    {{ $hName }}
                  </span>
                @else
                  <span class="text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded text-[11px] font-semibold">
                    Unallotted
                  </span>
                @endif
              </td>

              <td class="py-2.5 px-4 whitespace-nowrap">
                <form method="POST" action="{{ route('students.house-allotment.assign') }}" class="flex items-center gap-1.5">
                  @csrf
                  <input type="hidden" name="student_id" value="{{ $st->id }}">
                  <select name="house_id" onchange="this.form.submit()" class="select select-sm text-xs py-1 px-2 rounded-lg border-slate-300">
                    <option value="">-- Choose House --</option>
                    @foreach($houses as $h)
                      <option value="{{ $h->id }}" @selected($enr->house_id == $h->id || strtoupper($enr->house ?? '') == strtoupper($h->code))>
                        {{ $h->name }}
                      </option>
                    @endforeach
                  </select>
                </form>
              </td>

              <td class="py-2.5 px-3 text-right whitespace-nowrap">
                @if($hName)
                  <button type="button"
                          @click="confirmRemove({{ $st->id }}, '{{ addslashes($st->full_name) }}')"
                          class="btn btn-ghost btn-xs text-rose-700 hover:text-rose-900 hover:bg-rose-50"
                          title="Remove House Allotment with Mandatory Reason">
                    Remove
                  </button>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-8 text-center text-slate-400">
                No students found matching your criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($students->hasPages())
      <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
        {{ $students->links() }}
      </div>
    @endif
  </div>

  {{-- Audit History Log (House Allotments) --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
    <h3 class="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      House Allotment Audit History (Recent Activity)
    </h3>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px]">
            <th class="py-2 px-3">Date &amp; Time</th>
            <th class="py-2 px-3">Student</th>
            <th class="py-2 px-3">From</th>
            <th class="py-2 px-3">To (New House)</th>
            <th class="py-2 px-3">Reason</th>
            <th class="py-2 px-3">Authorized Staff</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($recentHistory as $hist)
            <tr>
              <td class="py-2 px-3 text-slate-400 font-mono">{{ $hist->created_at->format('d M Y, h:i A') }}</td>
              <td class="py-2 px-3 font-bold text-slate-800">{{ $hist->student?->full_name ?? '—' }}</td>
              <td class="py-2 px-3 text-slate-500">{{ $hist->from_value ?: 'Unallotted' }}</td>
              <td class="py-2 px-3 font-bold text-rose-900">{{ $hist->to_value }}</td>
              <td class="py-2 px-3 text-slate-600">{{ $hist->reason ?: 'Standard assignment' }}</td>
              <td class="py-2 px-3 text-slate-700">{{ $hist->changedBy?->name ?? 'System' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-4 text-center text-slate-400">
                No recent house allotment history recorded yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Modal: Remove House Allotment with Mandatory Reason --}}
  <div x-show="showRemoveModal"
       x-transition
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
       style="display: none;">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="showRemoveModal = false">
      <h3 class="text-lg font-black text-slate-900">Remove House Allotment</h3>
      <p class="text-xs text-slate-500 mt-1">
        You are removing the house allotment for <strong class="text-slate-800" x-text="removeStudentName"></strong>.
        A mandatory reason is required for audit logs.
      </p>

      <form method="POST" action="{{ route('students.house-allotment.remove') }}" class="mt-4 space-y-4">
        @csrf
        <input type="hidden" name="student_id" :value="removeStudentId">

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Reason for Removal *</label>
          <textarea name="reason" rows="3" required placeholder="Specify why the student is being removed from the house…" class="input w-full text-xs" x-model="removeReason"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" @click="showRemoveModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm font-bold bg-rose-600 hover:bg-rose-700 text-white">Confirm Removal</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal: House Master Configuration (Add / Edit House) --}}
  <div x-show="showHouseModal"
       x-transition
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
       style="display: none;">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200" @click.outside="showHouseModal = false">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-lg font-black text-slate-900" x-text="houseForm.id ? 'Edit House Master' : 'Create New House'"></h3>
        <button type="button" @click="showHouseModal = false" class="text-slate-400 hover:text-slate-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form method="POST" action="{{ route('students.house-allotment.manage') }}" class="mt-4 space-y-3.5">
        @csrf
        <input type="hidden" name="id" :value="houseForm.id">

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">House Name *</label>
            <input type="text" name="name" required placeholder="e.g. Ruby" class="input w-full text-xs" x-model="houseForm.name">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">House Code *</label>
            <input type="text" name="code" required placeholder="e.g. RED" class="input w-full text-xs uppercase" x-model="houseForm.code">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">House Color Hex *</label>
            <div class="flex items-center gap-2">
              <input type="color" name="color" class="w-8 h-8 rounded border border-slate-300 cursor-pointer p-0" x-model="houseForm.color">
              <input type="text" name="color_text" class="input flex-1 text-xs font-mono" x-model="houseForm.color">
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Max Capacity (Optional)</label>
            <input type="number" name="capacity" min="1" placeholder="Unlimited" class="input w-full text-xs" x-model="houseForm.capacity">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Teacher In-Charge</label>
          <select name="teacher_id" class="select w-full text-xs" x-model="houseForm.teacher_id">
            <option value="">-- Select Teacher --</option>
            @foreach($staffUsers as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Description / Motto</label>
          <textarea name="description" rows="2" placeholder="Symbolizes courage, passion, etc." class="input w-full text-xs" x-model="houseForm.description"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <button type="button" @click="showHouseModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm font-bold bg-rose-900 hover:bg-rose-800 text-white">Save House</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
