@extends('layouts.app')
@section('title', 'Staff Workload & Academic Analytics')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="page-title text-2xl font-black text-slate-800 tracking-tight">Staff Workload & Academic Analytics</h1>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
          Faculty Utilization
        </span>
      </div>
      <p class="page-subtitle text-slate-500 text-sm mt-0.5">
        Institutional oversight of faculty teaching hours, free periods, substitution allocation, and teaching report compliance.
      </p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('staff-timetable.index') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs font-bold text-slate-700 border-slate-300">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Timetable</span>
      </a>

      <button type="button" onclick="window.print()" class="btn btn-primary btn-sm inline-flex items-center gap-1.5 shadow-xs bg-slate-800 hover:bg-slate-900 text-white font-bold">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print Analytics</span>
      </button>
    </div>
  </div>

  {{-- Filter Bar --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
    <form method="GET" action="{{ route('staff-timetable.analytics') }}" class="flex items-end gap-3 flex-wrap">
      <div class="min-w-[200px]">
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Department</label>
        <select name="department_id" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800 bg-white" onchange="this.form.submit()">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept->id }}" @selected($departmentId == $dept->id)>{{ $dept->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="min-w-[200px]">
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Academic Year</label>
        <select name="academic_year_id" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800 bg-white" onchange="this.form.submit()">
          @foreach($academicYears as $ay)
            <option value="{{ $ay->id }}" @selected(request('academic_year_id') == $ay->id)>{{ $ay->name ?? 'Current Academic Year' }}</option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="h-9 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition">
        Apply Filter
      </button>

      @if($departmentId || request('academic_year_id'))
        <a href="{{ route('staff-timetable.analytics') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 underline self-center">
          Reset
        </a>
      @endif
    </form>
  </div>

  {{-- Summary KPI Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-xs">
      <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Teaching Faculty</p>
      <p class="text-3xl font-black text-slate-800 mt-1">{{ $analytics['total_teachers'] }}</p>
      <p class="text-[11px] text-slate-400 mt-0.5">Active Instructors</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-xs">
      <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Weekly Periods</p>
      <p class="text-3xl font-black text-indigo-700 mt-1">{{ $analytics['total_school_periods'] }}</p>
      <p class="text-[11px] text-indigo-400 mt-0.5">School-wide Periods / Wk</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-xs">
      <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Free Period Slots</p>
      <p class="text-3xl font-black text-emerald-600 mt-1">{{ $analytics['total_school_free'] }}</p>
      <p class="text-[11px] text-emerald-400 mt-0.5">Capacity for Substitution</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-xs">
      <p class="text-xs font-bold text-purple-600 uppercase tracking-wider">Extra Classes</p>
      <p class="text-3xl font-black text-purple-700 mt-1">{{ $analytics['total_school_extra'] }}</p>
      <p class="text-[11px] text-purple-400 mt-0.5">Conducted this Month</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4.5 shadow-xs">
      <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Average Utilization</p>
      <p class="text-3xl font-black text-amber-600 mt-1">{{ $analytics['avg_utilization'] }}%</p>
      <p class="text-[11px] text-amber-400 mt-0.5">Target: 75% – 90%</p>
    </div>
  </div>

  {{-- Workload Distribution Table --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Faculty Teaching Load & Reports Compliance</h3>
      <span class="text-xs text-slate-500 font-medium">Standard baseline: 30 periods/week (22.5 hours)</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50 text-slate-600 border-b border-slate-200 font-extrabold uppercase text-[10.5px] tracking-wider">
            <th class="p-3.5">Staff Member</th>
            <th class="p-3.5">Department</th>
            <th class="p-3.5 text-center">Periods / Wk</th>
            <th class="p-3.5 text-center">Teaching Hours</th>
            <th class="p-3.5 text-center">Free Slots</th>
            <th class="p-3.5 text-center">Extra / Subs</th>
            <th class="p-3.5 text-center">Reports Filed</th>
            <th class="p-3.5">Utilization %</th>
            <th class="p-3.5 text-center">Status</th>
            <th class="p-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($analytics['teacher_rows'] as $row)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="p-3.5 font-bold text-slate-800">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-black text-xs">
                    {{ strtoupper(substr($row['teacher']->first_name, 0, 1)) }}
                  </div>
                  <div>
                    <a href="{{ route('staff-timetable.index', ['staff_id' => $row['teacher']->id]) }}" class="hover:text-indigo-600 transition">
                      {{ $row['teacher']->first_name }} {{ $row['teacher']->last_name }}
                    </a>
                    <div class="text-[10px] text-slate-400 font-normal">ID: {{ $row['teacher']->employee_code }}</div>
                  </div>
                </div>
              </td>
              <td class="p-3.5 text-slate-600">
                <div>{{ $row['teacher']->department?->name ?? 'General' }}</div>
                <div class="text-[10px] text-slate-400">{{ $row['teacher']->designation?->name ?? 'Faculty' }}</div>
              </td>
              <td class="p-3.5 text-center font-extrabold text-slate-800 text-sm">
                {{ $row['regular_periods'] }}
              </td>
              <td class="p-3.5 text-center font-semibold text-slate-700">
                {{ $row['teaching_hours'] }} hrs
              </td>
              <td class="p-3.5 text-center font-bold text-emerald-700">
                {{ $row['free_periods'] }}
              </td>
              <td class="p-3.5 text-center font-medium text-purple-700">
                {{ $row['extra_classes'] }} / {{ $row['substitutions'] }}
              </td>
              <td class="p-3.5 text-center font-bold text-blue-700">
                {{ $row['reports_submitted'] }}
              </td>
              <td class="p-3.5 w-40">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="h-2 rounded-full {{ $row['utilization_pct'] > 100 ? 'bg-rose-500' : ($row['utilization_pct'] < 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                         style="width: {{ min(100, $row['utilization_pct']) }}%;"></div>
                  </div>
                  <span class="text-[11px] font-bold text-slate-700">{{ $row['utilization_pct'] }}%</span>
                </div>
              </td>
              <td class="p-3.5 text-center">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold
                  {{ $row['status'] === 'Overloaded' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($row['status'] === 'Under-utilized' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                  {{ $row['status'] }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <a href="{{ route('staff-timetable.index', ['staff_id' => $row['teacher']->id]) }}"
                   class="px-2.5 py-1 rounded text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 border border-indigo-200 transition inline-block">
                  View Timetable
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="p-8 text-center text-slate-400">
                No teaching staff records found matching the filter criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
