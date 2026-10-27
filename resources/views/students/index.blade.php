@extends('layouts.app')

@section('title', 'Students — Management & Hierarchy')

@section('content')
<div class="space-y-6" x-data="{
  activeView: '{{ (request('view') === 'hierarchy' || (request()->has('view_hierarchy') && !request()->hasAny(['section', 'section_id', 'search', 'page']))) ? 'hierarchy' : 'roster' }}',
  quickView: false,
  student: {},
  expandedClass: {{ request('class_id') ? (int)request('class_id') : 'null' }},
  selectedClassId: '{{ request('class_id', '') }}',
  classSectionMap: @json($classSectionMap),
  toggleExpand(id) { this.expandedClass = this.expandedClass === id ? null : id; },
  openQuickView(s) { this.student = s; this.quickView = true; },
  availableSections() {
    if (!this.selectedClassId || !this.classSectionMap[this.selectedClassId]) {
      return [];
    }
    return this.classSectionMap[this.selectedClassId];
  }
}">

  {{-- Page Header with Breadcrumbs & Module Navigation --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-rose-800">
          <span>Student Module</span>
          <span>&rsaquo;</span>
          <span class="text-slate-500">{{ $selectedYear?->name ?? 'All Academic Sessions' }}</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Student Management</h1>
        <p class="text-sm text-slate-500 mt-0.5">
          Academic Year &rarr; Class &rarr; Section &rarr; Student Records hierarchy with comprehensive student profiling.
        </p>
      </div>

      {{-- Action Buttons & Module Navigation --}}
      <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('students.create') }}" class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-xs font-bold bg-rose-900 hover:bg-rose-800 text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          New Admission
        </a>
        <a href="{{ route('students.section-allotment.index', array_filter(['class_id' => request('class_id'), 'academic_year_id' => $selectedYearId])) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-medium hover:border-slate-400">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Section Allotment
        </a>
        <a href="{{ route('students.house-allotment.index', array_filter(['academic_year_id' => $selectedYearId])) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-medium hover:border-slate-400">
          <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          House Allotment
        </a>
        <a href="{{ route('students.roll-numbers.index', array_filter(['class_id' => request('class_id'), 'academic_year_id' => $selectedYearId])) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-medium hover:border-slate-400">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
          Roll Numbers
        </a>
        <a href="{{ route('students.id-cards') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-medium hover:border-slate-400">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
          ID Cards
        </a>
        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
        <a href="{{ route('students.export', request()->query()) }}" class="btn btn-ghost btn-sm text-slate-600 hover:text-slate-900 border border-slate-200">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Export
        </a>
      </div>
    </div>

    {{-- Academic Year Selector Quick Switcher with Live Student Counts --}}
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3 text-xs">
      <div class="flex items-center gap-2">
        <span class="font-bold text-slate-600">Academic Session:</span>
        <form method="GET" action="{{ route('students.index') }}" class="inline">
          @foreach(request()->except('academic_year_id') as $k => $v)
            @if(is_array($v))
              @foreach($v as $subV)<input type="hidden" name="{{ $k }}[]" value="{{ $subV }}">@endforeach
            @else
              <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
          @endforeach
          <select name="academic_year_id" onchange="this.form.submit()" class="select select-sm text-xs font-bold py-1 px-2.5 rounded-lg border-slate-300 bg-slate-50 text-slate-800 shadow-2xs">
            @foreach($academicYears as $ay)
              <option value="{{ $ay->id }}" @selected($selectedYearId == $ay->id && request('academic_year_id') !== 'all')>
                {{ $ay->name }} {{ $ay->is_current ? '(Current Session)' : '' }} &mdash; {{ number_format($ay->student_count) }} Students
              </option>
            @endforeach
            <option value="all" @selected(request('academic_year_id') === 'all')>
              All Academic Sessions &mdash; {{ number_format($allStudentsCount) }} Total Students
            </option>
          </select>
        </form>
      </div>

      <div class="flex items-center gap-4 text-slate-500 font-medium">
        <span>Session Strength: <strong class="text-slate-800 font-bold">{{ number_format($metrics['total']) }}</strong></span>
        <span>&bull;</span>
        <span>Active: <strong class="text-emerald-700 font-bold">{{ number_format($metrics['active']) }}</strong></span>
        <span>&bull;</span>
        <span>Classes: <strong class="text-indigo-700 font-bold">{{ $metrics['classes_count'] }}</strong></span>
        <span>&bull;</span>
        <span>Sections: <strong class="text-indigo-700 font-bold">{{ $metrics['sections_count'] }}</strong></span>
      </div>
    </div>
  </div>

  {{-- Dynamic 12 Summary Metrics Cards --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
    {{-- 1. Total Students --}}
    <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Total Enrolled</span>
        <span class="p-1 rounded-md bg-slate-100 text-slate-600">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-slate-900">{{ number_format($metrics['total']) }}</div>
      <div class="text-[11px] text-slate-400 mt-0.5">{{ $selectedYear?->name ?? 'All Sessions' }}</div>
    </div>

    {{-- 2. Active Students --}}
    <div class="bg-white border border-emerald-200/80 rounded-xl p-3.5 shadow-2xs bg-emerald-50/20">
      <div class="flex items-center justify-between text-emerald-700 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Active Students</span>
        <span class="p-1 rounded-md bg-emerald-100 text-emerald-700">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-emerald-800">{{ number_format($metrics['active']) }}</div>
      <div class="text-[11px] text-emerald-600 mt-0.5">{{ $metrics['total'] > 0 ? round(($metrics['active'] / $metrics['total']) * 100, 1) : 0 }}% active status</div>
    </div>

    {{-- 3. Newly Admitted --}}
    <div class="bg-white border border-sky-200/80 rounded-xl p-3.5 shadow-2xs bg-sky-50/20">
      <div class="flex items-center justify-between text-sky-700 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">New Admitted</span>
        <span class="p-1 rounded-md bg-sky-100 text-sky-700">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-sky-800">{{ number_format($metrics['newly_admitted']) }}</div>
      <div class="text-[11px] text-sky-600 mt-0.5">Past 60 days</div>
    </div>

    {{-- 4. Students by Gender --}}
    <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Gender Ratio</span>
        <span class="p-1 rounded-md bg-indigo-50 text-indigo-600">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </span>
      </div>
      <div class="flex items-baseline gap-2">
        <span class="text-lg font-bold text-blue-700">{{ $metrics['male'] }} <span class="text-[10px] font-semibold text-blue-500 uppercase">Boys</span></span>
        <span class="text-slate-300">/</span>
        <span class="text-lg font-bold text-pink-700">{{ $metrics['female'] }} <span class="text-[10px] font-semibold text-pink-500 uppercase">Girls</span></span>
      </div>
      <div class="text-[11px] text-slate-400 mt-0.5">
        {{ $metrics['total'] > 0 ? round(($metrics['male'] / $metrics['total']) * 100) : 0 }}% M &bull; {{ $metrics['total'] > 0 ? round(($metrics['female'] / $metrics['total']) * 100) : 0 }}% F
      </div>
    </div>

    {{-- 5. Without Section Allotment --}}
    <a href="{{ route('students.index', array_merge(request()->query(), ['missing_data' => 'without_section'])) }}"
       class="bg-white border {{ $metrics['without_section'] > 0 ? 'border-amber-300 bg-amber-50/30 ring-1 ring-amber-200' : 'border-slate-200/80' }} rounded-xl p-3.5 shadow-2xs hover:shadow-xs transition-shadow block">
      <div class="flex items-center justify-between text-amber-700 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">No Section</span>
        <span class="p-1 rounded-md bg-amber-100 text-amber-800">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black {{ $metrics['without_section'] > 0 ? 'text-amber-800' : 'text-slate-900' }}">
        {{ number_format($metrics['without_section']) }}
      </div>
      <div class="text-[11px] text-amber-700 mt-0.5 flex items-center gap-1">
        <span>Awaiting allotment</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </div>
    </a>

    {{-- 6. Without Roll Numbers --}}
    <a href="{{ route('students.index', array_merge(request()->query(), ['missing_data' => 'without_roll'])) }}"
       class="bg-white border {{ $metrics['without_roll'] > 0 ? 'border-orange-300 bg-orange-50/30 ring-1 ring-orange-200' : 'border-slate-200/80' }} rounded-xl p-3.5 shadow-2xs hover:shadow-xs transition-shadow block">
      <div class="flex items-center justify-between text-orange-700 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">No Roll No.</span>
        <span class="p-1 rounded-md bg-orange-100 text-orange-800">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black {{ $metrics['without_roll'] > 0 ? 'text-orange-800' : 'text-slate-900' }}">
        {{ number_format($metrics['without_roll']) }}
      </div>
      <div class="text-[11px] text-orange-700 mt-0.5 flex items-center gap-1">
        <span>Needs numbering</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </div>
    </a>

    {{-- 7. Without House Allotment --}}
    <a href="{{ route('students.index', array_merge(request()->query(), ['missing_data' => 'without_house'])) }}"
       class="bg-white border {{ $metrics['without_house'] > 0 ? 'border-purple-300 bg-purple-50/30 ring-1 ring-purple-200' : 'border-slate-200/80' }} rounded-xl p-3.5 shadow-2xs hover:shadow-xs transition-shadow block">
      <div class="flex items-center justify-between text-purple-700 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">No House</span>
        <span class="p-1 rounded-md bg-purple-100 text-purple-800">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black {{ $metrics['without_house'] > 0 ? 'text-purple-800' : 'text-slate-900' }}">
        {{ number_format($metrics['without_house']) }}
      </div>
      <div class="text-[11px] text-purple-700 mt-0.5 flex items-center gap-1">
        <span>Unassigned house</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </div>
    </a>

    {{-- 8. Incomplete Profiles --}}
    <a href="{{ route('students.index', array_merge(request()->query(), ['missing_data' => 'incomplete_profile'])) }}"
       class="bg-white border {{ $metrics['incomplete_profile'] > 0 ? 'border-rose-300 bg-rose-50/30 ring-1 ring-rose-200' : 'border-slate-200/80' }} rounded-xl p-3.5 shadow-2xs hover:shadow-xs transition-shadow block">
      <div class="flex items-center justify-between text-rose-700 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Incomplete</span>
        <span class="p-1 rounded-md bg-rose-100 text-rose-800">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black {{ $metrics['incomplete_profile'] > 0 ? 'text-rose-800' : 'text-slate-900' }}">
        {{ number_format($metrics['incomplete_profile']) }}
      </div>
      <div class="text-[11px] text-rose-700 mt-0.5 flex items-center gap-1">
        <span>Missing info/photo</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </div>
    </a>

    {{-- 9. Transferred Students --}}
    <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Transferred</span>
        <span class="p-1 rounded-md bg-slate-100 text-slate-600">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-slate-800">{{ number_format($metrics['transferred']) }}</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Section/Class changes</div>
    </div>

    {{-- 10. TC Issued / Left --}}
    <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">TC Issued / Left</span>
        <span class="p-1 rounded-md bg-slate-100 text-slate-600">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-slate-800">{{ number_format($metrics['tc_issued']) }}</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Archived records</div>
    </div>

    {{-- 11. Class Coverage --}}
    <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Standards</span>
        <span class="p-1 rounded-md bg-indigo-50 text-indigo-700">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-indigo-900">{{ $metrics['classes_count'] }}</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Pre-KG to 12th</div>
    </div>

    {{-- 12. Total Sections --}}
    <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-2xs">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider">Sections</span>
        <span class="p-1 rounded-md bg-indigo-50 text-indigo-700">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-indigo-900">{{ $metrics['sections_count'] }}</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Active Class Sections</div>
    </div>
  </div>

  {{-- Clean View Navigation Switcher (Tabbed Architecture) --}}
  <div class="bg-white border border-slate-200/80 rounded-2xl p-2 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-xl">
      {{-- Tab 1: Student Records & Roster --}}
      <button type="button"
              @click="activeView = 'roster'"
              :class="activeView === 'roster' ? 'bg-white text-rose-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-4 py-2 rounded-lg text-xs sm:text-sm transition-all flex items-center gap-2">
        <svg class="w-4 h-4 text-rose-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Student Records &amp; Directory</span>
        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-900">
          {{ number_format($students->total()) }}
        </span>
      </button>

      {{-- Tab 2: Class & Section Hierarchy Overview --}}
      <button type="button"
              @click="activeView = 'hierarchy'"
              :class="activeView === 'hierarchy' ? 'bg-white text-rose-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-4 py-2 rounded-lg text-xs sm:text-sm transition-all flex items-center gap-2">
        <svg class="w-4 h-4 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span>Class &amp; Section Hierarchy Overview</span>
        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
          {{ $metrics['classes_count'] }} Classes &bull; {{ $metrics['sections_count'] }} Sections
        </span>
      </button>
    </div>

    <div class="text-xs text-slate-500 px-3 flex items-center gap-2">
      <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
      <span>Session: <strong class="text-slate-800">{{ $selectedYear?->name ?? 'All Sessions' }}</strong></span>
    </div>
  </div>

  {{-- VIEW 1: STUDENT RECORDS & DIRECTORY (Default Active View) --}}
  <div x-show="activeView === 'roster'" class="space-y-4">

    {{-- Interactive Standard / Grade Selector (Pre-KG to 12th) --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-black tracking-wider text-rose-950 uppercase flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-rose-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Select Standard / Grade
        </span>
        @if(request('class_id'))
          <a href="{{ route('students.index', request()->except(['class_id', 'section', 'section_id', 'page'])) }}" class="text-xs font-bold text-rose-800 hover:text-rose-900 underline">
            Show All Standards
          </a>
        @endif
      </div>

      {{-- Grade Pills Pre-KG to 12th in exact school sequence --}}
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
        <a href="{{ route('students.index', request()->except(['class_id', 'section', 'section_id', 'page'])) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 transition-colors {{ !request('class_id') ? 'bg-rose-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
          All Standards ({{ $metrics['total'] }})
        </a>

        @foreach($classOverview as $cls)
          <a href="{{ route('students.index', array_merge(request()->except(['class_id', 'section', 'section_id', 'page']), ['class_id' => $cls->id])) }}"
             class="px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 transition-colors flex items-center gap-1.5 {{ request('class_id') == $cls->id ? 'bg-rose-900 text-white shadow-2xs ring-2 ring-rose-900/30' : ($cls->total_students > 0 ? 'bg-slate-100 text-slate-800 hover:bg-slate-200' : 'bg-slate-50 text-slate-400 hover:bg-slate-100') }}">
            <span>{{ $cls->name }}</span>
            @if($cls->total_students > 0)
              <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ request('class_id') == $cls->id ? 'bg-rose-800 text-rose-100' : 'bg-slate-200 text-slate-700' }}">
                {{ $cls->total_students }}
              </span>
            @endif
          </a>
        @endforeach
      </div>

      {{-- Section Sub-Pills (Shown when a Standard is selected) --}}
      @if(request('class_id'))
        @php
          $activeClsData = $classOverview->firstWhere('id', request('class_id'));
        @endphp
        @if($activeClsData && $activeClsData->sections->isNotEmpty())
          <div class="pt-2 border-t border-slate-100 flex items-center gap-2 overflow-x-auto text-xs">
            <span class="font-bold text-slate-500 shrink-0">Sections in Class {{ $activeClsData->name }}:</span>

            <a href="{{ route('students.index', request()->except(['section', 'section_id', 'page'])) }}"
               class="px-2.5 py-1 rounded-md font-bold shrink-0 transition-colors {{ (!request('section') && !request('section_id')) ? 'bg-indigo-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
              All Sections ({{ $activeClsData->total_students }})
            </a>

            @foreach($activeClsData->sections as $sec)
              @php
                $isSecActive = (request('section_id') == $sec->id) ||
                               (request('section') && strtoupper(trim(preg_replace('/^section\s*/i', '', request('section')))) === strtoupper(trim($sec->name)));
              @endphp
              <a href="{{ route('students.index', array_merge(request()->except(['section', 'section_id', 'page']), ['section_id' => $sec->id, 'section' => $sec->name])) }}"
                 class="px-2.5 py-1 rounded-md font-bold shrink-0 transition-colors flex items-center gap-1.5 {{ $isSecActive ? 'bg-indigo-700 text-white shadow-2xs ring-2 ring-indigo-700/30' : ($sec->total > 0 ? 'bg-slate-100 text-slate-800 hover:bg-slate-200' : 'bg-slate-50 text-slate-400 hover:bg-slate-100') }}">
                <span>Section {{ $sec->name }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $isSecActive ? 'bg-indigo-800 text-indigo-100' : ($sec->total > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500') }}">
                  {{ $sec->total }}
                </span>
              </a>
            @endforeach
          </div>
        @endif
      @endif
    </div>

    {{-- Comprehensive Search & Filters Form --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs">
      <form method="GET" action="{{ route('students.index') }}" class="space-y-4">
        @if($selectedYearId)
          <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
        @elseif(request('academic_year_id') === 'all')
          <input type="hidden" name="academic_year_id" value="all">
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
          {{-- Search Input --}}
          <div class="col-span-1 sm:col-span-2 relative">
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Search Student</label>
            <div class="relative">
              <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </div>
              <input type="text" name="search" value="{{ request('search') }}"
                     placeholder="Name, admission no, roll no, mobile…"
                     class="input w-full pl-9 pr-8 text-sm">
              @if(request('search'))
                <a href="{{ route('students.index', request()->except('search')) }}"
                   class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
              @endif
            </div>
          </div>

          {{-- Class Filter --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Class / Standard</label>
            <select name="class_id" x-model="selectedClassId" class="select w-full text-sm">
              <option value="">All Classes</option>
              @foreach($classes as $cls)
                @php
                  $clsStat = $classOverview->firstWhere('id', $cls->id);
                @endphp
                <option value="{{ $cls->id }}" @selected(request('class_id') == $cls->id)>
                  {{ $cls->name }} {{ $clsStat && $clsStat->total_students > 0 ? '('.$clsStat->total_students.')' : '' }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Section Filter (Dynamic Options with real student counts) --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Section</label>
            <select name="section" class="select w-full text-sm">
              <option value="">All Sections</option>
              <template x-if="availableSections().length > 0">
                <template x-for="s in availableSections()" :key="s.id">
                  <option :value="s.name"
                          :selected="'{{ strtoupper(trim(preg_replace('/^section\s*/i', '', request('section', '')))) }}' === s.name.toUpperCase() || '{{ request('section_id') }}' == s.id"
                          x-text="'Section ' + s.name + ' (' + s.total + ' Students)'">
                  </option>
                </template>
              </template>
              <template x-if="availableSections().length === 0">
                <template x-for="name in ['A', 'B', 'C', 'D']" :key="name">
                  <option :value="name"
                          :selected="'{{ strtoupper(trim(preg_replace('/^section\s*/i', '', request('section', '')))) }}' === name"
                          x-text="'Section ' + name">
                  </option>
                </template>
              </template>
            </select>
          </div>

          {{-- House Filter --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Student House</label>
            <select name="house_id" class="select w-full text-sm">
              <option value="">All Houses</option>
              @foreach($houses as $h)
                <option value="{{ $h->id }}" @selected(request('house_id') == $h->id)>{{ $h->name }}</option>
              @endforeach
              <option value="unallotted" @selected(request('house_id') === 'unallotted')>&mdash; Unallotted &mdash;</option>
            </select>
          </div>

          {{-- Gender Filter --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Gender</label>
            <select name="gender" class="select w-full text-sm">
              <option value="">All Genders</option>
              <option value="male" @selected(request('gender') === 'male')>Boys (Male)</option>
              <option value="female" @selected(request('gender') === 'female')>Girls (Female)</option>
              <option value="other" @selected(request('gender') === 'other')>Other</option>
            </select>
          </div>
        </div>

        {{-- Second Row Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
          {{-- Student Status --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Enrollment Status</label>
            <select name="status" class="select w-full text-sm">
              <option value="active"      @selected(request('status', 'active') === 'active')>Active Only</option>
              <option value="inactive"    @selected(request('status') === 'inactive')>Inactive</option>
              <option value="left"        @selected(request('status') === 'left')>Left / Discontinued</option>
              <option value="transferred" @selected(request('status') === 'transferred')>Transferred</option>
              <option value="all"         @selected(request('status') === 'all')>All Statuses</option>
            </select>
          </div>

          {{-- Missing Data / Quick Filter --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Missing Info Filter</label>
            <select name="missing_data" class="select w-full text-sm">
              <option value="">None (All Records)</option>
              <option value="without_roll" @selected(request('missing_data') === 'without_roll')>Students Without Roll No.</option>
              <option value="without_section" @selected(request('missing_data') === 'without_section')>Students Without Section</option>
              <option value="without_house" @selected(request('missing_data') === 'without_house')>Students Without House</option>
              <option value="without_photo" @selected(request('missing_data') === 'without_photo')>Students Without Photo</option>
              <option value="incomplete_profile" @selected(request('missing_data') === 'incomplete_profile')>Incomplete Profiles</option>
            </select>
          </div>

          {{-- Sort Order --}}
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Sort By</label>
            <select name="sort" class="select w-full text-sm">
              <option value="latest"    @selected(request('sort', 'latest') === 'latest')>Latest Admission First</option>
              <option value="oldest"    @selected(request('sort') === 'oldest')>Oldest First</option>
              <option value="name_asc"  @selected(request('sort') === 'name_asc')>Student Name (A &rarr; Z)</option>
              <option value="name_desc" @selected(request('sort') === 'name_desc')>Student Name (Z &rarr; A)</option>
              <option value="roll_asc"  @selected(request('sort') === 'roll_asc')>Roll Number Ascending</option>
              <option value="adm_asc"   @selected(request('sort') === 'adm_asc')>Admission No. Ascending</option>
            </select>
          </div>

          {{-- Action Buttons --}}
          <div class="flex items-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-1 font-bold bg-rose-900 hover:bg-rose-800 text-white">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
              Apply Filters
            </button>
            @if(request()->hasAny(['search', 'class_id', 'section', 'section_id', 'house_id', 'gender', 'missing_data', 'sort']) || (request('status') && request('status') !== 'active'))
              <a href="{{ route('students.index', array_filter(['academic_year_id' => $selectedYearId])) }}" class="btn btn-ghost btn-sm text-slate-500 border border-slate-200 hover:bg-slate-50">
                Clear
              </a>
            @endif
          </div>
        </div>
      </form>
    </div>

    {{-- Active Filters Notification Bar --}}
    @if(request('class_id') || request('section') || request('section_id') || request('house_id') || request('missing_data') || request('search'))
      @php
        $filterCls = $classes->firstWhere('id', request('class_id'));
        $filterHouse = $houses->firstWhere('id', request('house_id'));
        $secParam = request('section') ? strtoupper(trim(preg_replace('/^section\s*/i', '', request('section')))) : null;
        if (!$secParam && request('section_id')) {
          $foundSec = \App\Models\Section::find(request('section_id'));
          $secParam = $foundSec ? $foundSec->name : null;
        }
      @endphp
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs">
        <div class="flex items-center gap-2 text-indigo-950 font-medium flex-wrap">
          <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Active Filters:</span>
          @if(request('search'))
            <span class="inline-flex items-center gap-1 bg-white border border-indigo-200 text-indigo-900 px-2 py-0.5 rounded-md">
              <span>Search: "{{ request('search') }}"</span>
              <a href="{{ route('students.index', request()->except('search')) }}" class="hover:text-rose-700">&times;</a>
            </span>
          @endif
          @if($filterCls)
            <span class="inline-flex items-center gap-1 bg-white border border-indigo-200 text-indigo-900 px-2 py-0.5 rounded-md font-bold">
              <span>Class: {{ $filterCls->name }}</span>
              <a href="{{ route('students.index', request()->except(['class_id', 'section', 'section_id'])) }}" class="hover:text-rose-700">&times;</a>
            </span>
          @endif
          @if($secParam)
            <span class="inline-flex items-center gap-1 bg-white border border-indigo-200 text-indigo-900 px-2 py-0.5 rounded-md font-bold">
              <span>Section: {{ $secParam }}</span>
              <a href="{{ route('students.index', request()->except(['section', 'section_id'])) }}" class="hover:text-rose-700">&times;</a>
            </span>
          @endif
          @if($filterHouse)
            <span class="inline-flex items-center gap-1 bg-white border border-indigo-200 text-indigo-900 px-2 py-0.5 rounded-md font-bold">
              <span>House: {{ $filterHouse->name }}</span>
              <a href="{{ route('students.index', request()->except('house_id')) }}" class="hover:text-rose-700">&times;</a>
            </span>
          @endif
          @if(request('missing_data'))
            <span class="inline-flex items-center gap-1 bg-amber-100/90 border border-amber-300 text-amber-900 px-2 py-0.5 rounded-md font-bold">
              <span>{{ ucwords(str_replace('_', ' ', request('missing_data'))) }}</span>
              <a href="{{ route('students.index', request()->except('missing_data')) }}" class="hover:text-rose-700">&times;</a>
            </span>
          @endif
          <a href="{{ route('students.index', array_filter(['academic_year_id' => $selectedYearId])) }}" class="ml-2 text-indigo-700 hover:text-indigo-950 underline font-semibold">
            Clear All
          </a>
        </div>
        <div class="flex items-center gap-3">
          @if(request('class_id'))
            <a href="{{ route('students.roll-numbers.index', ['class_id' => request('class_id'), 'academic_year_id' => $selectedYearId]) }}"
               class="font-bold text-indigo-700 hover:text-indigo-900 underline underline-offset-2 shrink-0">
              Regenerate Roll Numbers for this Class &rarr;
            </a>
          @endif
        </div>
      </div>
    @endif

    {{-- Professional 15-Column Student Roster Table --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
      <div class="px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-slate-800 text-sm">Student Records</h3>
          <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-black">
            {{ $students->total() }}
          </span>
        </div>
        <div class="text-xs text-slate-400">
          Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }}
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
              <th class="py-3 px-3 w-12 text-center">Photo</th>
              <th class="py-3 px-3">Admission No.</th>
              <th class="py-3 px-3">Roll No.</th>
              <th class="py-3 px-4">Student Name</th>
              <th class="py-3 px-3">Class</th>
              <th class="py-3 px-3">Section</th>
              <th class="py-3 px-3">Gender</th>
              <th class="py-3 px-3">DOB</th>
              <th class="py-3 px-3">Parent / Guardian</th>
              <th class="py-3 px-3">Parent Contact</th>
              <th class="py-3 px-3">Adm. Date</th>
              <th class="py-3 px-3">Status</th>
              <th class="py-3 px-3">House</th>
              <th class="py-3 px-3 text-center">Profile</th>
              <th class="py-3 px-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            @forelse($students as $student)
              @php
                // Use enrollment matching selected academic year or fallback to latest
                $enrollment = $student->enrollments->first() ?? $student->currentEnrollment;
                $classObj = $enrollment?->class;
                $sectionObj = $enrollment?->section;
                $houseObj = $enrollment?->houseGroup;
                $houseColor = $houseObj?->color ?? '#64748B';
                $completionPct = $student->profile_completion_percentage;
                $qvData = json_encode([
                  'id'            => $student->id,
                  'name'          => $student->full_name,
                  'admission_no'  => $student->admission_number,
                  'class'         => $classObj?->name ?? '—',
                  'section'       => $sectionObj?->name ?? '—',
                  'roll'          => $enrollment?->roll_number ?? '—',
                  'dob'           => $student->dob?->format('d M Y') ?? '—',
                  'gender'        => ucfirst($student->gender ?? '—'),
                  'blood_group'   => $student->blood_group ?? '—',
                  'father_name'   => $student->father_name ?? '—',
                  'father_mobile' => $student->father_mobile ?? '—',
                  'mother_name'   => $student->mother_name ?? '—',
                  'mobile'        => $student->mobile ?? '—',
                  'house'         => $houseObj?->name ?? ($enrollment?->house ?: 'Unallotted'),
                  'status'        => ucfirst($student->status),
                  'initials'      => strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)),
                  'photo'         => $student->photo ? asset('storage/'.$student->photo) : null,
                  'url_view'      => route('students.show', $student->id),
                  'url_edit'      => route('students.edit', $student->id),
                  'url_tc'        => route('students.tc.form', $student->id),
                ]);
              @endphp
              <tr class="hover:bg-slate-50/60 transition-colors group cursor-pointer" @click="openQuickView({{ $qvData }})">
                {{-- 1. Photo --}}
                <td class="py-2.5 px-3 text-center" @click.stop>
                  <div class="w-8 h-8 rounded-full overflow-hidden mx-auto ring-1 ring-slate-200 bg-slate-100 flex items-center justify-center">
                    @if($student->photo)
                      <img src="{{ asset('storage/'.$student->photo) }}" class="w-full h-full object-cover" alt="{{ $student->full_name }}">
                    @else
                      <span class="text-[10px] font-black text-rose-900">
                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                      </span>
                    @endif
                  </div>
                </td>

                {{-- 2. Admission Number --}}
                <td class="py-2.5 px-3 font-mono font-bold text-rose-900 whitespace-nowrap">
                  <a href="{{ route('students.show', $student->id) }}" class="hover:underline" @click.stop>
                    {{ $student->admission_number }}
                  </a>
                </td>

                {{-- 3. Roll Number --}}
                <td class="py-2.5 px-3 whitespace-nowrap">
                  @if(!empty($enrollment?->roll_number))
                    <span class="font-mono font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200">
                      {{ $enrollment->roll_number }}
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 font-semibold text-[11px] text-orange-700 bg-orange-50 border border-orange-200 px-1.5 py-0.5 rounded">
                      Unassigned
                    </span>
                  @endif
                </td>

                {{-- 4. Student Name --}}
                <td class="py-2.5 px-4 font-semibold text-slate-900 whitespace-nowrap">
                  <a href="{{ route('students.show', $student->id) }}" class="hover:text-rose-900 font-bold" @click.stop>
                    {{ $student->full_name }}
                  </a>
                </td>

                {{-- 5. Class --}}
                <td class="py-2.5 px-3 font-bold text-slate-800 whitespace-nowrap">
                  {{ $classObj?->name ?? '—' }}
                </td>

                {{-- 6. Section --}}
                <td class="py-2.5 px-3 whitespace-nowrap">
                  @if($sectionObj)
                    <span class="px-2 py-0.5 rounded font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                      Section {{ $sectionObj->name }}
                    </span>
                  @else
                    <span class="px-2 py-0.5 rounded font-semibold text-amber-700 bg-amber-50 border border-amber-200">
                      None
                    </span>
                  @endif
                </td>

                {{-- 7. Gender --}}
                <td class="py-2.5 px-3 whitespace-nowrap">
                  @if(strtolower($student->gender) === 'male')
                    <span class="font-bold text-blue-700">Boy</span>
                  @elseif(strtolower($student->gender) === 'female')
                    <span class="font-bold text-pink-700">Girl</span>
                  @else
                    <span class="text-slate-500">{{ ucfirst($student->gender ?? '—') }}</span>
                  @endif
                </td>

                {{-- 8. Date of Birth --}}
                <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap">
                  {{ $student->dob ? $student->dob->format('d/m/Y') : '—' }}
                </td>

                {{-- 9. Parent / Guardian --}}
                <td class="py-2.5 px-3 text-slate-700 whitespace-nowrap truncate max-w-[140px]">
                  {{ $student->father_name ?: ($student->mother_name ?: ($student->guardian_name ?: '—')) }}
                </td>

                {{-- 10. Parent Contact Number --}}
                <td class="py-2.5 px-3 font-mono text-slate-600 whitespace-nowrap" @click.stop>
                  @php $phone = $student->father_mobile ?: ($student->mother_mobile ?: $student->mobile); @endphp
                  @if($phone)
                    <a href="tel:{{ $phone }}" class="text-indigo-600 hover:underline">
                      {{ $phone }}
                    </a>
                  @else
                    <span class="text-slate-300">—</span>
                  @endif
                </td>

                {{-- 11. Admission Date --}}
                <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">
                  {{ $student->admission_date ? $student->admission_date->format('d/m/Y') : '—' }}
                </td>

                {{-- 12. Student Status --}}
                <td class="py-2.5 px-3 whitespace-nowrap">
                  @php
                    $stStatus = strtolower($student->status);
                  @endphp
                  @if($stStatus === 'active')
                    <span class="inline-flex items-center gap-1 font-bold text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                    </span>
                  @elseif($stStatus === 'transferred')
                    <span class="inline-flex items-center gap-1 font-bold text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                      Transferred
                    </span>
                  @elseif(in_array($stStatus, ['left', 'tc_issued']))
                    <span class="inline-flex items-center gap-1 font-bold text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                      TC Issued
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 font-bold text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                      {{ ucfirst($stStatus) }}
                    </span>
                  @endif
                </td>

                {{-- 13. House Name --}}
                <td class="py-2.5 px-3 whitespace-nowrap">
                  @php
                    $hName = $houseObj?->name ?: ($enrollment?->house ?: null);
                  @endphp
                  @if($hName)
                    <span class="inline-flex items-center gap-1.5 font-bold text-[11px] px-2 py-0.5 rounded-md border"
                          style="color: {{ $houseColor }}; border-color: {{ $houseColor }}40; background-color: {{ $houseColor }}12;">
                      <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $houseColor }};"></span>
                      {{ $hName }}
                    </span>
                  @else
                    <span class="text-slate-400 italic">Unallotted</span>
                  @endif
                </td>

                {{-- 14. Profile Completion --}}
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <div class="inline-flex items-center gap-1.5">
                    <div class="w-12 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                      <div class="h-1.5 rounded-full {{ $completionPct == 100 ? 'bg-emerald-500' : ($completionPct >= 70 ? 'bg-indigo-500' : 'bg-rose-500') }}" style="width: {{ $completionPct }}%"></div>
                    </div>
                    <span class="text-[10px] font-bold {{ $completionPct == 100 ? 'text-emerald-700' : 'text-slate-600' }}">{{ $completionPct }}%</span>
                  </div>
                </td>

                {{-- 15. Actions --}}
                <td class="py-2.5 px-3 text-right whitespace-nowrap" @click.stop>
                  <div class="flex items-center justify-end gap-1">
                    <a href="{{ route('students.show', $student->id) }}"
                       class="p-1 rounded-md text-slate-500 hover:text-rose-900 hover:bg-slate-100 transition-colors"
                       title="View Full Student Profile">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                    <a href="{{ route('students.edit', $student->id) }}"
                       class="p-1 rounded-md text-slate-500 hover:text-indigo-600 hover:bg-slate-100 transition-colors"
                       title="Edit Student">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </a>
                    <a href="{{ route('students.print-profile', $student->id) }}" target="_blank"
                       class="p-1 rounded-md text-slate-500 hover:text-emerald-700 hover:bg-slate-100 transition-colors"
                       title="Print Student Profile Record">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>
                    <a href="{{ route('students.id-card.single', $student->id) }}"
                       class="p-1 rounded-md text-slate-500 hover:text-amber-600 hover:bg-slate-100 transition-colors"
                       title="Student ID Card">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="15" class="py-12 px-4 text-center">
                  <div class="max-w-lg mx-auto">
                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <p class="font-bold text-slate-700 text-sm">No student records found</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or search criteria.</p>

                    {{-- Smart Zero-Results Helper Banner --}}
                    @if(request('class_id'))
                      @php
                        $zeroCls = $classOverview->firstWhere('id', request('class_id'));
                        $activeSections = $zeroCls ? $zeroCls->sections->filter(fn($s) => $s->total > 0) : collect();
                      @endphp
                      <div class="mt-4 p-4 rounded-xl bg-amber-50/80 border border-amber-200 text-left text-xs space-y-2">
                        <div class="flex items-start gap-2">
                          <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          <div class="space-y-1">
                            <p class="font-bold text-amber-950">
                              Class {{ $zeroCls?->name }} currently has {{ $zeroCls?->total_students ?? 0 }} enrolled students in Session {{ $selectedYear?->name ?? 'selected' }}.
                            </p>
                            @if(request('section') || request('section_id'))
                              <p class="text-amber-800">
                                You filtered by <strong>Section {{ strtoupper(trim(preg_replace('/^section\s*/i', '', request('section', '')))) ?: 'Selected' }}</strong>, which currently has 0 students in this session.
                              </p>
                            @endif

                            @if($activeSections->isNotEmpty())
                              <div class="pt-1 text-slate-700">
                                <span>In this session, students are enrolled in:</span>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                  @foreach($activeSections as $as)
                                    <a href="{{ route('students.index', array_merge(request()->except(['page']), ['class_id' => $zeroCls->id, 'section_id' => $as->id, 'section' => $as->name])) }}"
                                       class="inline-flex items-center gap-1 font-bold text-xs bg-rose-900 text-white px-2.5 py-1 rounded-md hover:bg-rose-800 shadow-2xs">
                                      <span>View Section {{ $as->name }}</span>
                                      <span class="bg-rose-800 text-rose-100 text-[10px] px-1.5 py-0.2 rounded-full font-black">{{ $as->total }} students</span>
                                    </a>
                                  @endforeach
                                </div>
                              </div>
                            @endif

                            <div class="pt-2 border-t border-amber-200/60 flex items-center gap-3">
                              <a href="{{ route('students.index', ['academic_year_id' => 2, 'class_id' => request('class_id')]) }}"
                                 class="font-bold text-indigo-700 hover:text-indigo-900 underline">
                                Switch to 2025-2026 session records &rarr;
                              </a>
                              <a href="{{ route('students.index', request()->except(['section', 'section_id', 'page'])) }}"
                                 class="font-semibold text-slate-600 hover:text-slate-900 underline">
                                Show all sections for Class {{ $zeroCls?->name }}
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endif
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination Bar --}}
      @if($students->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
          {{ $students->links() }}
        </div>
      @endif
    </div>

  </div>

  {{-- VIEW 2: CLASS & SECTION HIERARCHY OVERVIEW (Clean Structured Spec Table) --}}
  <div x-show="activeView === 'hierarchy'" style="display: none;" class="space-y-4">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-200/80 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span>Class &amp; Section Hierarchy Overview</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Structured overview organized by Standard &rarr; Section with student strength, teacher assignment, and seat capacity.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button type="button" @click="expandedClass = 'all'" class="text-xs font-semibold text-rose-900 hover:text-rose-800 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-2xs hover:bg-slate-50">
            Expand All Classes
          </button>
          <button type="button" @click="expandedClass = null" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-2xs hover:bg-slate-50">
            Collapse All
          </button>
        </div>
      </div>

      {{-- Structured Table matching School ERP Specification --}}
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
              <th class="py-3 px-4 w-12 text-center">#</th>
              <th class="py-3 px-4">Standard / Class</th>
              <th class="py-3 px-4 text-center">Sections</th>
              <th class="py-3 px-4 text-center">Enrolled Students</th>
              <th class="py-3 px-4 text-center">Boys</th>
              <th class="py-3 px-4 text-center">Girls</th>
              <th class="py-3 px-4 text-center">Awaiting Section</th>
              <th class="py-3 px-4 text-center">Without Roll No.</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($classOverview as $cls)
              <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer select-none"
                  @click="toggleExpand({{ $cls->id }})">
                <td class="py-3 px-4 text-center font-bold text-slate-400">
                  {{ $loop->iteration }}
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-rose-900/10 text-rose-900 flex items-center justify-center font-black text-xs shrink-0 ring-1 ring-rose-900/20">
                      {{ $cls->name }}
                    </span>
                    <div>
                      <strong class="text-slate-900 text-sm">Class {{ $cls->name }}</strong>
                      <span class="text-slate-400 text-xs ml-1">({{ $cls->display_name }})</span>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 text-center font-semibold text-slate-700">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200">
                    {{ $cls->total_sections }} {{ Str::plural('Section', $cls->total_sections) }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center font-black text-sm text-slate-900">
                  {{ number_format($cls->total_students) }}
                </td>
                <td class="py-3 px-4 text-center font-bold text-blue-700">
                  {{ number_format($cls->male) }}
                </td>
                <td class="py-3 px-4 text-center font-bold text-pink-700">
                  {{ number_format($cls->female) }}
                </td>
                <td class="py-3 px-4 text-center">
                  @if($cls->unassigned > 0)
                    <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-bold border border-amber-200">
                      {{ $cls->unassigned }}
                    </span>
                  @else
                    <span class="text-slate-300">0</span>
                  @endif
                </td>
                <td class="py-3 px-4 text-center">
                  @if($cls->without_roll > 0)
                    <span class="px-2 py-0.5 rounded-md bg-orange-50 text-orange-800 font-bold border border-orange-200">
                      {{ $cls->without_roll }}
                    </span>
                  @else
                    <span class="text-slate-300">0</span>
                  @endif
                </td>
                <td class="py-3 px-4 text-right" @click.stop>
                  <div class="flex items-center justify-end gap-2">
                    <button type="button"
                            @click="activeView = 'roster'; selectedClassId = '{{ $cls->id }}'; window.location = '{{ route('students.index', array_filter(['academic_year_id' => $selectedYearId, 'class_id' => $cls->id])) }}'"
                            class="btn btn-ghost btn-xs text-rose-800 hover:text-rose-900 font-bold border border-slate-200">
                      Filter Roster
                    </button>
                    <button type="button" @click="toggleExpand({{ $cls->id }})" class="p-1 text-slate-400 hover:text-slate-700 rounded">
                      <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': expandedClass === {{ $cls->id }} || expandedClass === 'all' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                  </div>
                </td>
              </tr>

              {{-- Nested Section Cards (Expanded beneath class row) --}}
              <tr x-show="expandedClass === {{ $cls->id }} || expandedClass === 'all'" x-collapse>
                <td colspan="9" class="p-0 bg-slate-50/90 border-b border-slate-200">
                  <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                      <span class="text-xs font-black uppercase tracking-wider text-slate-600">
                        Sections in Class {{ $cls->name }} ({{ $cls->total_sections }} Total)
                      </span>
                      <a href="{{ route('students.section-allotment.index', array_filter(['academic_year_id' => $selectedYearId, 'class_id' => $cls->id])) }}"
                         class="text-xs font-bold text-indigo-700 hover:text-indigo-900 underline">
                        Manage Section Allotments &rarr;
                      </a>
                    </div>

                    @if($cls->sections->isEmpty())
                      <p class="text-xs text-slate-400 py-2">No sections created for this class.</p>
                    @else
                      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($cls->sections as $sec)
                          <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs hover:border-slate-300 transition-colors">
                            <div class="flex items-center justify-between gap-2">
                              <span class="text-sm font-black text-slate-900">Section {{ $sec->name }}</span>
                              <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $sec->total > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                {{ $sec->total }} / {{ $sec->capacity }} seats
                              </span>
                            </div>

                            <div class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                              <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                              <span class="truncate">Teacher: <strong class="text-slate-700">{{ $sec->teacher_name }}</strong></span>
                            </div>

                            {{-- Seat Progress Bar --}}
                            @php
                              $fillPct = $sec->capacity > 0 ? min(100, round(($sec->total / $sec->capacity) * 100)) : 0;
                            @endphp
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2.5 overflow-hidden">
                              <div class="h-1.5 rounded-full {{ $fillPct >= 90 ? 'bg-rose-600' : ($fillPct >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $fillPct }}%"></div>
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                              <span class="text-slate-500 font-medium">{{ $sec->male }} M &bull; {{ $sec->female }} F</span>
                              <a href="{{ route('students.index', array_filter(['academic_year_id' => $selectedYearId, 'class_id' => $cls->id, 'section_id' => $sec->id, 'section' => $sec->name])) }}"
                                 class="font-bold text-rose-900 hover:text-rose-800 flex items-center gap-0.5">
                                <span>View Students</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                              </a>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="p-8 text-center text-slate-500">
                  No classes configured.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Quick View Drawer (Alpine.js) --}}
  <div x-show="quickView"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 translate-x-full"
       x-transition:enter-end="opacity-100 translate-x-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 translate-x-0"
       x-transition:leave-end="opacity-0 translate-x-full"
       class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl border-l border-slate-200 p-6 flex flex-col justify-between overflow-y-auto"
       style="display: none;"
       @click.outside="quickView = false">
    <div>
      <div class="flex items-center justify-between pb-4 border-b border-slate-100">
        <h3 class="font-black text-slate-900 text-base">Quick Profile Preview</h3>
        <button type="button" @click="quickView = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="text-center py-5">
        <div class="w-20 h-20 rounded-full mx-auto overflow-hidden ring-4 ring-rose-900/10 bg-slate-100 flex items-center justify-center text-rose-900 font-black text-xl mb-3">
          <template x-if="student.photo">
            <img :src="student.photo" class="w-full h-full object-cover">
          </template>
          <template x-if="!student.photo">
            <span x-text="student.initials"></span>
          </template>
        </div>
        <h4 class="font-black text-lg text-slate-900" x-text="student.name"></h4>
        <p class="text-xs text-rose-900 font-mono font-bold mt-0.5" x-text="'Admission No: ' + student.admission_no"></p>
        <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" x-text="student.status"></span>
      </div>

      <div class="space-y-3 border-t border-slate-100 pt-4 text-xs">
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Class &amp; Section:</span>
          <strong class="text-slate-800" x-text="student.class + ' - ' + student.section"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Roll Number:</span>
          <strong class="font-mono text-slate-800" x-text="student.roll"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">House:</span>
          <strong class="text-slate-800" x-text="student.house"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Gender:</span>
          <strong class="text-slate-800" x-text="student.gender"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Date of Birth:</span>
          <strong class="text-slate-800" x-text="student.dob"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Blood Group:</span>
          <strong class="text-slate-800" x-text="student.blood_group"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Father's Name:</span>
          <strong class="text-slate-800" x-text="student.father_name"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Father's Mobile:</span>
          <strong class="font-mono text-indigo-600" x-text="student.father_mobile"></strong>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
          <span class="text-slate-400">Mother's Name:</span>
          <strong class="text-slate-800" x-text="student.mother_name"></strong>
        </div>
      </div>
    </div>

    <div class="pt-6 border-t border-slate-100 flex items-center gap-2">
      <a :href="student.url_view" class="btn btn-primary flex-1 btn-sm text-center font-bold bg-rose-900 hover:bg-rose-800 text-white">
        Open Full Profile
      </a>
      <a :href="student.url_edit" class="btn btn-secondary btn-sm text-center border-slate-300">
        Edit
      </a>
    </div>
  </div>

</div>
@endsection
