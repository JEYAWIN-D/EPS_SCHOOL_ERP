@extends('layouts.app')

@section('title', 'Class Data Management — Class Teacher Portal')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Academics</span>
        <span>/</span>
        <span class="text-indigo-600">Class Management</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Class Data &amp; Student Operations</h1>
      <p class="text-sm text-slate-500 mt-1">Dedicated management portal for assigned class teacher responsibilities.</p>
    </div>

    @if($class)
      <div class="flex items-center gap-2.5 flex-wrap">
        <a href="{{ route('attendance.mark', ['class_id' => $class->id, 'section_id' => $sectionId]) }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
          Mark Class Attendance
        </a>
        <a href="{{ route('academics.timetable', ['class_id' => $class->id, 'section_id' => $sectionId]) }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-xs transition">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Full Timetable
        </a>
      </div>
    @endif
  </div>

  @if($isElevated)
    {{-- Administrative Class Switcher --}}
    <div class="bg-indigo-50/60 border border-indigo-200/80 rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2 text-xs font-semibold text-indigo-900">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Administrative View: Select class and section to inspect class teacher operations.</span>
      </div>
      <form method="GET" action="{{ route('academics.my-class') }}" class="flex items-center gap-2">
        <select name="class_id" onchange="this.form.submit()" class="px-2.5 py-1.5 text-xs bg-white border border-indigo-300 rounded-lg font-medium text-slate-700">
          @foreach($allClasses as $c)
            <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
          @endforeach
        </select>
      </form>
    </div>
  @endif

  @if(!$class)
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <h2 class="text-lg font-bold text-slate-900">No Active Class Teacher Assignment Found</h2>
      <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
        Your staff profile is not currently assigned as a Class Teacher for any active class and section. Contact the school academic coordinator or administrator to assign class responsibilities.
      </p>
    </div>
  @else

    {{-- Class Banner & Quick Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      
      {{-- Class Overview Card --}}
      <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Assigned Class</span>
          @if($class->teachingLevel)
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">
              {{ $class->teachingLevel->name }}
            </span>
          @endif
        </div>
        <p class="text-2xl font-black text-slate-900 mt-2">
          {{ $class->name }} {{ $section ? '— ' . $section->name : '' }}
        </p>
        <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
          <span class="font-semibold text-indigo-600">{{ $students->count() }} enrolled students</span>
        </div>
      </div>

      {{-- Attendance Today --}}
      <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Today's Attendance</span>
          <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $todayAttendance['is_marked'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
            {{ $todayAttendance['is_marked'] ? 'Marked' : 'Pending' }}
          </span>
        </div>
        <div class="flex items-baseline gap-2 mt-2">
          <span class="text-2xl font-black text-emerald-600">{{ $todayAttendance['present'] }}</span>
          <span class="text-xs text-slate-400 font-medium">Present / {{ $students->count() }} Total</span>
        </div>
        <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
          <span class="text-rose-600 font-semibold">{{ $todayAttendance['absent'] }} Absent</span>
          <span>&bull;</span>
          <span class="text-amber-600 font-semibold">{{ $todayAttendance['late'] }} Late</span>
        </div>
      </div>

      {{-- Schedule Periods --}}
      <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Class Schedule</span>
          <span class="p-1 rounded bg-slate-100 text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </span>
        </div>
        @php
          $isoDay = (int) now()->dayOfWeekIso;
          $todayPeriods = $timetable->get($isoDay) ?? collect();
        @endphp
        <p class="text-2xl font-black text-slate-900 mt-2">{{ $todayPeriods->count() }} Periods</p>
        <p class="text-xs text-slate-500 mt-2">Scheduled for today ({{ now()->format('l') }})</p>
      </div>

      {{-- Recent Homework Tasks --}}
      <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Homework Active</span>
          <span class="p-1 rounded bg-slate-100 text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </span>
        </div>
        <p class="text-2xl font-black text-slate-900 mt-2">{{ $recentHomework->count() }}</p>
        <p class="text-xs text-slate-500 mt-2">Assignments tracked</p>
      </div>

    </div>

    {{-- Main Roster & Timetable Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      {{-- Students Roster (2 columns) --}}
      <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Student Roster</h2>
            <p class="text-xs text-slate-500">Active students in {{ $class->name }} {{ $section?->name }}</p>
          </div>
          <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">
            {{ $students->count() }} Students
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <th class="py-3 px-4">Roll</th>
                <th class="py-3 px-4">Student</th>
                <th class="py-3 px-4">Adm No</th>
                <th class="py-3 px-4">Parent Mobile</th>
                <th class="py-3 px-4">Today's Attendance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse($students as $st)
                <tr class="hover:bg-slate-50/70 transition">
                  <td class="py-3 px-4 font-mono font-bold text-slate-700 text-xs">
                    {{ $st->roll ?? '—' }}
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-900">{{ $st->full_name }}</div>
                    <div class="text-[11px] text-slate-400">{{ ucfirst($st->gender ?? 'Student') }}</div>
                  </td>
                  <td class="py-3 px-4 font-mono text-xs text-slate-600">
                    {{ $st->admission_no }}
                  </td>
                  <td class="py-3 px-4 text-xs text-slate-600 font-mono">
                    {{ $st->father_mobile ?? $st->mobile ?? '—' }}
                  </td>
                  <td class="py-3 px-4">
                    @if($st->today_status === 'present')
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                        Present
                      </span>
                    @elseif($st->today_status === 'absent')
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">
                        Absent
                      </span>
                    @elseif($st->today_status === 'late')
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">
                        Late
                      </span>
                    @elseif($st->today_status === 'leave')
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">
                        On Leave
                      </span>
                    @else
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500">
                        Not Marked
                      </span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-400">
                    No active students enrolled in this class.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Right Column: Today's Schedule & Homework --}}
      <div class="space-y-6">

        {{-- Today's Periods Card --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Today's Class Periods</h3>
            <span class="text-xs font-semibold text-indigo-600">{{ now()->format('l') }}</span>
          </div>

          <div class="divide-y divide-slate-100 mt-2">
            @forelse($todayPeriods as $period)
              <div class="py-2.5 flex items-center justify-between text-xs">
                <div>
                  <span class="font-bold text-slate-800">{{ $period->subject?->name ?? 'Free Period' }}</span>
                  <div class="text-[11px] text-slate-400">
                    {{ $period->teacher?->full_name ?? 'Unassigned' }}
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-mono font-semibold text-slate-600">P{{ $period->period_number }}</span>
                  <div class="text-[10px] text-slate-400">{{ $period->start_time ? substr($period->start_time, 0, 5) : '' }}</div>
                </div>
              </div>
            @empty
              <p class="py-4 text-center text-xs text-slate-400 italic">No periods scheduled for today.</p>
            @endforelse
          </div>
        </div>

        {{-- Recent Homework Card --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Class Assignments</h3>
            <span class="text-[11px] text-slate-400">Recent</span>
          </div>

          <div class="divide-y divide-slate-100 mt-2">
            @forelse($recentHomework as $hw)
              <div class="py-2.5 text-xs">
                <div class="flex items-center justify-between font-semibold text-slate-800">
                  <span>{{ $hw->subject?->name ?? 'Subject' }}</span>
                  <span class="text-[10px] text-slate-400 font-normal">Due: {{ $hw->due_date ? $hw->due_date->format('d M') : '—' }}</span>
                </div>
                <p class="text-slate-600 mt-0.5 line-clamp-2 text-[11px]">{{ $hw->description ?? $hw->title }}</p>
              </div>
            @empty
              <p class="py-4 text-center text-xs text-slate-400 italic">No active homework assignments.</p>
            @endforelse
          </div>
        </div>

      </div>

    </div>

  @endif

</div>
@endsection
