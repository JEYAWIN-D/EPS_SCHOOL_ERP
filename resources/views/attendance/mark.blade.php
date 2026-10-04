@extends('layouts.app')

@section('title', 'Mark Attendance — ' . ($selectedClass?->name ?? 'Class') . ' ' . ($selectedSection?->name ? 'Section ' . $selectedSection->name : ''))

@section('content')
<div class="space-y-6 max-w-6xl mx-auto" x-data="{
  searchQuery: '',
  statuses: {
    @foreach($students as $s)
      '{{ $s->id }}': '{{ $existing[$s->id]?->status ?? ($isHoliday ? "holiday" : "present") }}',
    @endforeach
  },
  remarks: {
    @foreach($students as $s)
      '{{ $s->id }}': '{{ addslashes($existing[$s->id]?->remark ?? "") }}',
    @endforeach
  },
  arrivalTimes: {
    @foreach($students as $s)
      '{{ $s->id }}': '{{ $existing[$s->id]?->arrival_time ?? "08:30" }}',
    @endforeach
  },

  counts: {
    present: 0,
    absent: 0,
    late: 0,
    half_day: 0,
    leave: 0,
    holiday: 0,
    total: {{ $students->count() }},
    rate: 0
  },
  recalc() {
    let p = 0, a = 0, l = 0, h = 0, lv = 0, hol = 0;
    const vals = Object.values(this.statuses);
    for (let i = 0; i < vals.length; i++) {
      const s = vals[i];
      if (s === 'present') p++;
      else if (s === 'absent') a++;
      else if (s === 'late') l++;
      else if (s === 'half_day') h++;
      else if (s === 'leave') lv++;
      else if (s === 'holiday') hol++;
    }
    const tot = vals.length;
    let rate = 0;
    if (tot > 0) {
      if ({{ $isHoliday ? 'true' : 'false' }} && p === 0) rate = 100;
      else rate = Math.round(((p + l + (h * 0.5)) / tot) * 100);
    }
    this.counts = { present: p, absent: a, late: l, half_day: h, leave: lv, holiday: hol, total: tot, rate: rate };
  },
  init() {
    this.recalc();
  },
  setStatus(id, status) {
    this.statuses[id] = status;
    this.recalc();
  },
  markAll(status) {
    for (const id in this.statuses) {
      this.statuses[id] = status;
    }
    this.recalc();
  },
  invertStatus() {
    for (const id in this.statuses) {
      this.statuses[id] = this.statuses[id] === 'present' ? 'absent' : 'present';
    }
    this.recalc();
  },
  getPresentCount() { return this.counts.present; },
  getAbsentCount() { return this.counts.absent; },
  getLateCount() { return this.counts.late; },
  getHalfDayCount() { return this.counts.half_day; },
  getLeaveCount() { return this.counts.leave; },
  getHolidayCount() { return this.counts.holiday; },
  getTotalCount() { return this.counts.total; },
  getRate() { return this.counts.rate; }
}">

  {{-- ── Top Navigation & Selector Bar ────────────────────────── --}}
  <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4 print:hidden">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <a href="{{ route('attendance.index') }}" class="btn-icon w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="Back to Attendance Overview">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="page-title text-xl font-black text-slate-900">Manual Section Attendance</h1>
            @if($selectedClass)
              <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                Class {{ $selectedClass->name }} @if($selectedSection)&bull; Sec {{ $selectedSection->name }}@endif
              </span>
            @endif
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Mark daily attendance for enrolled students. Section-wise real-time sync with parent alert triggers.
          </p>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center gap-2 flex-wrap">
        <button onclick="window.print()" type="button" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Print Register
        </button>
        <a href="{{ route('attendance.report', ['class_id' => $classId]) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Monthly Report
        </a>
      </div>
    </div>

    {{-- Class & Section Picker Form --}}
    <form method="GET" action="{{ route('attendance.mark') }}" class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Class / Standard</label>
          <select name="class_id" onchange="this.form.submit()" class="select select-sm text-xs font-bold bg-slate-50 border-slate-200 rounded-xl min-w-[140px]">
            @foreach($classes as $cls)
              <option value="{{ $cls->id }}" @selected($classId == $cls->id)>Class {{ $cls->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Section</label>
          @if($sections->count())
            <select name="section_id" onchange="this.form.submit()" class="select select-sm text-xs font-bold bg-slate-50 border-slate-200 rounded-xl min-w-[120px]">
              @foreach($sections->sortBy('name') as $sec)
                <option value="{{ $sec->id }}" @selected($sectionId == $sec->id)>Section {{ $sec->name }}</option>
              @endforeach
            </select>
          @else
            <span class="text-xs text-slate-400 italic py-1 block">No sections created</span>
          @endif
        </div>

        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Attendance Date</label>
          <input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}"
                 onchange="this.form.submit()" class="input text-xs font-mono font-bold bg-slate-50 border-slate-200 rounded-xl py-1.5 w-36">
        </div>
      </div>

      {{-- Section Pill Shortcut Tabs (Sorted A to D) --}}
      @if($sections->count() > 1)
      <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
        @foreach($sections->sortBy('name') as $sec)
          <a href="{{ route('attendance.mark', ['class_id' => $classId, 'section_id' => $sec->id, 'date' => $date]) }}"
             class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $sectionId == $sec->id ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            Sec {{ $sec->name }}
          </a>
        @endforeach
      </div>
      @endif
    </form>
  </div>

  @if($students->count())
  <form method="POST" action="{{ route('attendance.save') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="class_id" value="{{ $classId }}">
    <input type="hidden" name="section_id" value="{{ $sectionId }}">
    <input type="hidden" name="date" value="{{ $date }}">

    @php
      $cutoff   = \App\Models\SchoolSetting::get('attendance_cutoff_time', '12:00');
      $isPastCutoff = ($date === today()->toDateString()) && (now()->format('H:i') > $cutoff);
    @endphp

    {{-- ── Real-Time Status Counters Ribbon ───────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Enrolled</span>
        <p class="text-xl font-black font-mono text-slate-900 mt-0.5" x-text="getTotalCount()">{{ $students->count() }}</p>
      </div>

      <div class="bg-emerald-50/80 p-3.5 rounded-2xl border border-emerald-200 shadow-xs text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Present (P)</span>
        <p class="text-xl font-black font-mono text-emerald-700 mt-0.5" x-text="getPresentCount()">0</p>
      </div>

      <div class="bg-rose-50/80 p-3.5 rounded-2xl border border-rose-200 shadow-xs text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Absent (A)</span>
        <p class="text-xl font-black font-mono text-rose-700 mt-0.5" x-text="getAbsentCount()">0</p>
      </div>

      <div class="bg-amber-50/80 p-3.5 rounded-2xl border border-amber-200 shadow-xs text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Late (L)</span>
        <p class="text-xl font-black font-mono text-amber-700 mt-0.5" x-text="getLateCount()">0</p>
      </div>

      <div class="bg-blue-50/80 p-3.5 rounded-2xl border border-blue-200 shadow-xs text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Leave (LV)</span>
        <p class="text-xl font-black font-mono text-blue-700 mt-0.5" x-text="getLeaveCount()">0</p>
      </div>

      <div class="bg-indigo-900 text-white p-3.5 rounded-2xl shadow-xs text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-200">Attendance Rate</span>
        <p class="text-xl font-black font-mono text-emerald-400 mt-0.5" x-text="getRate() + '%'">0%</p>
      </div>
    </div>

    @if($isHoliday)
    <div class="bg-indigo-50/90 border border-indigo-200/80 rounded-2xl p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-100 border border-indigo-200/80 text-indigo-700 flex items-center justify-center font-bold shrink-0">
          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h4 class="font-extrabold text-sm text-indigo-950 capitalize">{{ $holidayName }}</h4>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-600 text-white shadow-2xs uppercase tracking-wider">Holiday Off</span>
          </div>
          <p class="text-xs text-indigo-800/80 mt-0.5 font-medium">Declared school holiday. Student attendance is locked for this date.</p>
        </div>
      </div>
      <a href="{{ route('classes.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-indigo-100/60 text-indigo-700 border border-indigo-200/90 shadow-2xs transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Manage Holidays
      </a>
    </div>
    @endif

    {{-- ── Quick Bulk Actions & Search Filter Toolbar ─────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
      @if($isHoliday)
        <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <span>Attendance Locked (Holiday Off)</span>
        </div>
      @else
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-widest mr-1">Bulk Mark:</span>
          
          {{-- All Present --}}
          <button type="button" @click="markAll('present')"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-extrabold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/90 shadow-2xs transition active:scale-95 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>All Present</span>
          </button>

          {{-- All Absent --}}
          <button type="button" @click="markAll('absent')"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-extrabold bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200/90 shadow-2xs transition active:scale-95 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>All Absent</span>
          </button>

          {{-- All Holiday --}}
          <button type="button" @click="markAll('holiday')"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-extrabold bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200/90 shadow-2xs transition active:scale-95 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>All Holiday</span>
          </button>

          {{-- Invert Selection --}}
          <button type="button" @click="invertStatus()"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-extrabold bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition active:scale-95 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            <span>Invert Selection</span>
          </button>
        </div>
      @endif

      {{-- Instant Student Search --}}
      <div class="w-full sm:w-64 relative flex items-center">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <input type="text" x-model="searchQuery" placeholder="Search by name or roll number..."
               class="input text-xs pl-9 py-2 w-full bg-slate-50 border-slate-200 rounded-xl focus:bg-white transition-colors">
      </div>
    </div>

    {{-- Status Code Meaning & Guide Banner --}}
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 flex items-center justify-between gap-3 flex-wrap text-xs shadow-2xs">
      <span class="font-bold text-slate-800 flex items-center gap-1.5">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Status Code Guide:</span>
      </span>
      <div class="flex items-center gap-2 flex-wrap text-[11px] font-bold">
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200" title="Full-Day Attendance">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> <b>P</b> = Present
        </span>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-rose-100 text-rose-800 border border-rose-200" title="Did not attend school">
          <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> <b>A</b> = Absent
        </span>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-amber-100 text-amber-800 border border-amber-200" title="Arrived after morning start time">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> <b>L</b> = Late
        </span>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-orange-100 text-orange-800 border border-orange-200" title="Attended one half of the day (0.5 day credit)">
          <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> <b>HD</b> = Half Day
        </span>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 border border-blue-200" title="Approved Medical / Family Leave">
          <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> <b>LV</b> = Leave
        </span>
      </div>
    </div>

    {{-- ── Student Attendance Marking Table (Desktop & Mobile) ──── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4 w-16 text-center">Roll</th>
              <th class="py-3 px-4">Student Details</th>
              <th class="py-3 px-4 w-28 text-center">Avg Rate</th>
              <th class="py-3 px-4 text-center w-72">Status (P · A · L · HD · LV)</th>
              <th class="py-3 px-4 w-32">Arrival Time</th>
              <th class="py-3 px-4">Reason / Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            @foreach($students as $student)
            <tr class="hover:bg-slate-50/60 transition-colors"
                x-show="!searchQuery || '{{ strtolower($student->full_name) }}'.includes(searchQuery.toLowerCase()) || '{{ $student->enrollment?->roll_number ?? $student->admission_number }}'.includes(searchQuery)"
                :class="{
                  'bg-emerald-50/30': statuses['{{ $student->id }}'] === 'present',
                  'bg-rose-50/40': statuses['{{ $student->id }}'] === 'absent',
                  'bg-amber-50/40': statuses['{{ $student->id }}'] === 'late',
                  'bg-orange-50/40': statuses['{{ $student->id }}'] === 'half_day',
                  'bg-blue-50/40': statuses['{{ $student->id }}'] === 'leave'
                }">

              {{-- Roll Number --}}
              <td class="py-3 px-4 font-mono font-bold text-slate-800 text-center">
                {{ $student->enrollment?->roll_number ?? '—' }}
              </td>

              {{-- Student Details --}}
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center font-bold text-indigo-700 shrink-0">
                    @if($student->photo)
                      <img src="{{ asset('storage/' . $student->photo) }}" class="w-full h-full object-cover" alt="">
                    @else
                      {{ strtoupper(substr($student->first_name,0,1) . substr($student->last_name,0,1)) }}
                    @endif
                  </div>
                  <div>
                    <a href="{{ route('students.show', $student->id) }}" target="_blank" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                      {{ $student->full_name }}
                    </a>
                    <p class="text-[11px] text-slate-400 font-mono">Adm: {{ $student->admission_number }} &bull; Ph: +91 {{ $student->father_mobile ?? $student->mobile ?? '—' }}</p>
                  </div>
                </div>
              </td>

              {{-- Historical Avg Rate --}}
              <td class="py-3 px-4 text-center">
                @if($student->hist_pct !== null)
                  <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $student->hist_pct >= 75 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ $student->hist_pct }}%
                  </span>
                @else
                  <span class="text-slate-300 text-[10px]">—</span>
                @endif
              </td>

              {{-- Status Radio Toggle Buttons --}}
              <td class="py-3 px-4 text-center">
                @if($isHoliday)
                  <input type="hidden" name="attendance[{{ $student->id }}]" value="holiday">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200/80 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>HOLIDAY OFF</span>
                  </span>
                @else
                  <input type="hidden" name="attendance[{{ $student->id }}]" value="{{ $existing[$student->id]?->status ?? 'present' }}" :value="statuses['{{ $student->id }}']">
                  <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-0.5 text-xs font-bold gap-0.5">
                    {{-- Present --}}
                    <button type="button" @click="setStatus('{{ $student->id }}', 'present')"
                            title="P = Present (Full Day)"
                            :class="statuses['{{ $student->id }}'] === 'present' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg transition text-xs cursor-pointer">
                      P
                    </button>

                    {{-- Absent --}}
                    <button type="button" @click="setStatus('{{ $student->id }}', 'absent')"
                            title="A = Absent"
                            :class="statuses['{{ $student->id }}'] === 'absent' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg transition text-xs cursor-pointer">
                      A
                    </button>

                    {{-- Late --}}
                    <button type="button" @click="setStatus('{{ $student->id }}', 'late')"
                            title="L = Late Arrival"
                            :class="statuses['{{ $student->id }}'] === 'late' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg transition text-xs cursor-pointer">
                      L
                    </button>

                    {{-- Half Day --}}
                    <button type="button" @click="setStatus('{{ $student->id }}', 'half_day')"
                            title="HD = Half Day (0.5 Day)"
                            :class="statuses['{{ $student->id }}'] === 'half_day' ? 'bg-orange-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2 py-1 rounded-lg transition text-xs cursor-pointer">
                      HD
                    </button>

                    {{-- Leave --}}
                    <button type="button" @click="setStatus('{{ $student->id }}', 'leave')"
                            title="LV = Approved Leave"
                            :class="statuses['{{ $student->id }}'] === 'leave' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2 py-1 rounded-lg transition text-xs cursor-pointer">
                      LV
                    </button>

                    {{-- Holiday --}}
                    <button type="button" @click="setStatus('{{ $student->id }}', 'holiday')"
                            title="H = Holiday Off"
                            :class="statuses['{{ $student->id }}'] === 'holiday' ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2 py-1 rounded-lg transition text-xs cursor-pointer">
                      H
                    </button>
                  </div>
                @endif
              </td>

              {{-- Arrival Time --}}
              <td class="py-3 px-4">
                @if($isHoliday)
                  <span class="text-xs font-mono font-medium text-slate-400 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 block text-center cursor-not-allowed">
                    —
                  </span>
                @else
                  <input type="time" name="arrival_time[{{ $student->id }}]"
                         value="{{ $existing[$student->id]?->arrival_time ?? '08:30' }}"
                         x-model="arrivalTimes['{{ $student->id }}']"
                         class="input text-xs py-1 px-2 w-28 bg-white border-slate-200 rounded-lg">
                @endif
              </td>

              {{-- Remarks / Reason --}}
              <td class="py-3 px-4">
                @if($isHoliday)
                  <span class="text-xs italic text-slate-400 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 block cursor-not-allowed">
                    Declared School Holiday Off
                  </span>
                @else
                  <input type="text" name="remarks[{{ $student->id }}]"
                         value="{{ $existing[$student->id]?->remark ?? '' }}"
                         x-model="remarks['{{ $student->id }}']"
                         placeholder="e.g. Fever, Leave note..."
                         class="input text-xs py-1 px-2 w-full bg-white border-slate-200 rounded-lg"
                         :class="{ 'border-rose-300': statuses['{{ $student->id }}'] === 'absent' }">
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- Cutoff Override Warning --}}
    @if($isPastCutoff && !$isHoliday)
    <div class="alert-warning p-4 rounded-2xl flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span class="text-xs font-semibold text-amber-900">Attendance cutoff time ({{ $cutoff }}) has passed for today.</span>
      </div>
      <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-amber-300 shadow-2xs">
        <input type="checkbox" name="override_cutoff" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-0">
        <span class="text-xs font-bold text-slate-800">Authorize Cutoff Override</span>
      </label>
    </div>
    @endif

    {{-- ── Save Action Footer & Parent Alert Notification Toggle ─── --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="notify_absent" value="1" {{ $isHoliday ? 'disabled' : 'checked' }} class="w-4 h-4 rounded text-indigo-600 focus:ring-0">
        <div>
          <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
            <span>📱 Dispatch SMS & WhatsApp Alert to Absentee Parents</span>
          </span>
          <span class="text-[11px] text-slate-400 block">Sends automatic instant absence notice to registered parent phone numbers</span>
        </div>
      </label>

      @if($isHoliday)
      <div class="flex items-center gap-3">
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary text-xs font-bold px-4 py-2">
          Back
        </a>
        <button type="button" disabled class="btn bg-slate-100 text-slate-400 border border-slate-300 text-xs font-bold px-6 py-2 rounded-xl cursor-not-allowed flex items-center gap-2">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Locked (Declared Holiday)
        </button>
      </div>
      @else
      <div class="flex items-center gap-3">
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary text-xs font-bold px-4 py-2">
          Cancel
        </a>
        <button type="submit" class="btn btn-primary text-xs font-bold px-6 py-2 shadow-sm cursor-pointer flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Save Section Attendance
        </button>
      </div>
      @endif
    </div>

  </form>
  @else
  <div class="card text-center py-16 bg-white rounded-2xl border border-slate-200">
    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-2xl mb-3">📋</div>
    <h3 class="text-base font-bold text-slate-800">No Students Found</h3>
    <p class="text-xs text-slate-500 mt-1">No active student enrollments in Class {{ $selectedClass?->name ?? 'selected' }} {{ $selectedSection?->name ? 'Section ' . $selectedSection->name : '' }}.</p>
  </div>
  @endif

</div>
@endsection
