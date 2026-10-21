@extends('layouts.app')
@section('title', 'Staff Timetable & Teaching Work Management')

@section('content')
<div class="space-y-6" x-data="staffTimetableApp()" @keydown.escape.window="closeAllModals()">

  {{-- Page Header --}}
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="page-title text-2xl font-black text-slate-800 tracking-tight">Staff Timetable & Teaching Work Management</h1>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
          Faculty Hub
        </span>
      </div>
      <p class="page-subtitle text-slate-500 text-sm mt-0.5">
        Individual faculty schedules dynamically derived from student timetables, free period management, extra classes, and academic tracking.
      </p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      {{-- My Timetable Shortcut --}}
      @if($authEmployeeId)
        <a href="{{ route('staff-timetable.my') }}"
           class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs font-bold text-slate-700 border-slate-300 hover:bg-slate-50">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span>My Timetable</span>
        </a>
      @endif

      {{-- Analytics Dashboard --}}
      @if($isElevated)
        <a href="{{ route('staff-timetable.analytics') }}"
           class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs font-bold text-slate-700 border-slate-300 hover:bg-slate-50">
          <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <span>Workload Analytics</span>
        </a>
      @endif

      {{-- Schedule Extra Class Button --}}
      <button type="button" @click="openExtraClassModal()"
              class="btn btn-primary btn-sm inline-flex items-center gap-1.5 shadow-xs bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Schedule Extra Class</span>
      </button>

      {{-- Assign Staff Duty (Admin only) --}}
      @if($isElevated)
        <button type="button" @click="openDutyModal()"
                class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs font-bold text-amber-800 border-amber-300 bg-amber-50 hover:bg-amber-100">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <span>Assign Duty</span>
        </button>
      @endif

      {{-- Print PDF --}}
      @if($selectedStaff)
        <a href="{{ route('staff-timetable.pdf', ['teacherId' => $selectedStaff->id, 'date' => $targetDate->toDateString()]) }}"
           target="_blank"
           class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs font-bold text-slate-700 border-slate-300 hover:bg-slate-50">
          <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          <span>Print / PDF</span>
        </a>
      @endif
    </div>
  </div>

  {{-- Notification Alerts --}}
  @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between shadow-2xs">
      <div class="flex items-center gap-2 font-medium">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">&times;</button>
    </div>
  @endif

  @if(session('error'))
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between shadow-2xs">
      <div class="flex items-center gap-2 font-medium">
        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('error') }}</span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">&times;</button>
    </div>
  @endif

  {{-- Search and Filter Hub (Card) --}}
  <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
    <form method="GET" action="{{ route('staff-timetable.index') }}" class="space-y-4">
      <input type="hidden" name="view" value="{{ $viewMode }}">

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Staff Member Selector / Search --}}
        <div class="lg:col-span-2">
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
            Select Teaching Staff Member
          </label>
          <div class="relative">
            <select name="staff_id" class="w-full h-10 px-3 pr-8 rounded-lg border border-slate-300 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white" onchange="this.form.submit()">
              <option value="">-- Choose Faculty Member --</option>
              @foreach($teachingStaffList as $t)
                <option value="{{ $t->id }}" @selected(optional($selectedStaff)->id == $t->id)>
                  {{ $t->first_name }} {{ $t->last_name }} ({{ $t->employee_code }}) — {{ $t->department?->name ?? 'Faculty' }}
                  @if($authEmployeeId == $t->id) ★ (You) @endif
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Date Picker --}}
        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
            Working Date
          </label>
          <input type="date" name="date" value="{{ $targetDate->toDateString() }}"
                 class="w-full h-10 px-3 rounded-lg border border-slate-300 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                 onchange="this.form.submit()">
        </div>

        {{-- Department Filter --}}
        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
            Department
          </label>
          <select name="department_id" class="w-full h-10 px-3 rounded-lg border border-slate-300 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- Secondary Advanced Filters Bar --}}
      <div class="pt-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3 flex-wrap flex-1">
          {{-- Search by keyword --}}
          <div class="relative min-w-[200px] flex-1 max-w-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, code, subject..."
                   class="w-full h-9 pl-8 pr-3 rounded-md border border-slate-300 text-xs text-slate-700 placeholder-slate-400 focus:ring-1 focus:ring-indigo-500">
            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>

          {{-- Class Filter --}}
          <select name="class_id" class="h-9 px-2.5 rounded-md border border-slate-300 text-xs font-medium text-slate-700 bg-white" onchange="this.form.submit()">
            <option value="">All Classes</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->display_name ?: $c->name }}</option>
            @endforeach
          </select>

          {{-- Subject Filter --}}
          <select name="subject_id" class="h-9 px-2.5 rounded-md border border-slate-300 text-xs font-medium text-slate-700 bg-white" onchange="this.form.submit()">
            <option value="">All Subjects</option>
            @foreach($subjects as $sub)
              <option value="{{ $sub->id }}" @selected(request('subject_id') == $sub->id)>{{ $sub->name }} ({{ $sub->code }})</option>
            @endforeach
          </select>

          <button type="submit" class="h-9 px-4 rounded-md bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
            Filter Results
          </button>

          @if(request()->anyFilled(['search', 'department_id', 'class_id', 'subject_id']))
            <a href="{{ route('staff-timetable.index', ['staff_id' => optional($selectedStaff)->id, 'date' => $targetDate->toDateString()]) }}"
               class="text-xs font-bold text-rose-600 hover:text-rose-700 underline">
              Reset Filters
            </a>
          @endif
        </div>

        {{-- View Switcher Tab (Daily / Weekly / Calendar) --}}
        <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
          <a href="{{ route('staff-timetable.index', array_merge(request()->all(), ['view' => 'daily'])) }}"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewMode === 'daily' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            📅 Daily View
          </a>
          <a href="{{ route('staff-timetable.index', array_merge(request()->all(), ['view' => 'weekly'])) }}"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewMode === 'weekly' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            📊 Weekly Matrix
          </a>
          <a href="{{ route('staff-timetable.index', array_merge(request()->all(), ['view' => 'calendar'])) }}"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewMode === 'calendar' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            📆 Academic Tasks
          </a>
        </div>
      </div>
    </form>
  </div>

  @if(!$selectedStaff)
    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </div>
      <h3 class="text-base font-bold text-slate-800">No Teaching Staff Selected</h3>
      <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Please choose a faculty member from the dropdown above to view their individual timetable schedule and academic tasks.</p>
    </div>
  @else

    {{-- Teacher Overview Profile Card --}}
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl text-white p-6 shadow-md relative overflow-hidden">
      <div class="absolute right-0 top-0 translate-x-8 -translate-y-8 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex items-start justify-between flex-wrap gap-6 relative z-10">
        {{-- Profile details --}}
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-2xl bg-indigo-600/30 border border-indigo-400/30 flex items-center justify-center text-xl font-black text-indigo-200 shadow-inner">
            {{ strtoupper(substr($selectedStaff->first_name, 0, 1) . substr($selectedStaff->last_name, 0, 1)) }}
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h2 class="text-xl font-black tracking-tight text-white">
                {{ $selectedStaff->first_name }} {{ $selectedStaff->last_name }}
              </h2>
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                ID: {{ $selectedStaff->employee_code }}
              </span>
              @if($selectedStaff->class_teacher_badge)
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/30 text-emerald-200 border border-emerald-400/30">
                  {{ $selectedStaff->class_teacher_badge }}
                </span>
              @endif
            </div>

            <p class="text-xs text-indigo-200/80 mt-1">
              {{ $selectedStaff->designation?->name ?? 'Senior Faculty' }} &bull; Department of {{ $selectedStaff->department?->name ?? 'Academics' }}
              &bull; Official Email: {{ $selectedStaff->official_email ?? $selectedStaff->personal_email ?? 'staff@schoolerp.in' }}
            </p>

            <div class="flex items-center gap-2 text-xs text-indigo-300 mt-2 font-medium">
              <span class="inline-flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Active Duty
              </span>
              <span>&bull;</span>
              <span>Date: {{ $targetDate->format('l, d F Y') }}</span>
              <span>&bull;</span>
              <span>Week: {{ $weeklyMatrix['week_label'] ?? '' }}</span>
            </div>
          </div>
        </div>

        {{-- Metrics Chips --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center min-w-[100px]">
            <p class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider">Today's Classes</p>
            <p class="text-2xl font-black text-white mt-0.5">{{ $stats['teaching_periods_today'] }}</p>
            <p class="text-[10px] text-slate-400">Periods</p>
          </div>
          <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center min-w-[100px]">
            <p class="text-[11px] font-bold text-emerald-300 uppercase tracking-wider">Free Periods</p>
            <p class="text-2xl font-black text-emerald-400 mt-0.5">{{ $stats['free_periods_today'] }}</p>
            <p class="text-[10px] text-emerald-200/60">Available</p>
          </div>
          <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center min-w-[100px]">
            <p class="text-[11px] font-bold text-purple-300 uppercase tracking-wider">Extra / Subs</p>
            <p class="text-2xl font-black text-purple-400 mt-0.5">{{ $stats['extra_classes_today'] + $stats['substitutions_today'] }}</p>
            <p class="text-[10px] text-purple-200/60">Duties</p>
          </div>
          <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center min-w-[100px]">
            <p class="text-[11px] font-bold text-amber-300 uppercase tracking-wider">Weekly Load</p>
            <p class="text-2xl font-black text-amber-400 mt-0.5">{{ $stats['weekly_hours'] }}</p>
            <p class="text-[10px] text-amber-200/60">Hours / Wk</p>
          </div>
        </div>
      </div>
    </div>

    {{-- VIEW 1: DAILY TIMETABLE VIEW --}}
    @if($viewMode === 'daily')
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
            <span>📅 Day Schedule for {{ $targetDate->format('l, d M Y') }}</span>
            @if($daySchedule['is_off_day'] ?? false)
              <span class="px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                Non-Working Day: {{ $daySchedule['off_reason'] ?? 'Holiday' }}
              </span>
            @endif
          </h3>

          <div class="text-xs font-bold text-slate-500">
            Institutional Standard: 8 Teaching Periods &bull; 45 Mins Each &bull; 3 Scheduled Breaks
          </div>
        </div>

        @if($daySchedule['is_off_day'] ?? false)
          <div class="bg-rose-50/50 border border-rose-200 rounded-2xl p-8 text-center text-rose-900">
            <span class="text-3xl">🏖️</span>
            <h4 class="text-base font-bold mt-2">{{ $daySchedule['off_reason'] }}</h4>
            <p class="text-xs text-rose-600 mt-1">No regular classes are scheduled for this date. Staff duties or special remedial classes can be allocated if required.</p>
          </div>
        @else
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($daySchedule['periods'] as $pNum => $period)
              <div class="bg-white rounded-2xl border p-4.5 transition-all hover:shadow-md flex flex-col justify-between relative group
                          {{ $period['period_type'] === 'free_period' ? 'border-emerald-200 bg-emerald-50/10' : ($period['period_type'] === 'extra_class' ? 'border-purple-200 bg-purple-50/10' : ($period['period_type'] === 'substitution' ? 'border-amber-200 bg-amber-50/10' : 'border-slate-200')) }}">

                {{-- Period Header --}}
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                      <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $period['dot_color'] }};"></span>
                      {{ $period['period_name'] }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold border {{ $period['badge_class'] }}">
                      {{ $period['period_label'] }}
                    </span>
                  </div>

                  {{-- Time range --}}
                  <div class="text-xs font-bold text-slate-500 flex items-center gap-1 mb-3">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $period['time_range'] }}</span>
                    <span class="text-[10px] text-slate-400">({{ $period['duration_mins'] }}m)</span>
                  </div>

                  {{-- Content Details --}}
                  @if($period['subject_name'])
                    <div class="space-y-1 mb-3">
                      <p class="text-sm font-black text-slate-800 leading-tight">
                        {{ $period['subject_name'] }}
                        @if($period['subject_code'])
                          <span class="text-xs font-normal text-slate-500">({{ $period['subject_code'] }})</span>
                        @endif
                      </p>
                      <p class="text-xs font-bold text-indigo-700">
                        Class: {{ $period['class_name'] }} - Section {{ $period['section_name'] }}
                      </p>
                      <p class="text-xs text-slate-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Venue: {{ $period['room'] }}
                      </p>
                    </div>
                  @elseif($period['period_type'] === 'free_period')
                    <div class="space-y-1.5 mb-3">
                      @if($period['planned_task'])
                        <div class="p-2.5 rounded-xl bg-cyan-50 border border-cyan-200">
                          <p class="text-xs font-black text-cyan-900">{{ $period['planned_task']['activity_label'] }}</p>
                          <p class="text-xs text-cyan-800 mt-0.5 font-medium">{{ $period['planned_task']['title'] }}</p>
                          @if($period['planned_task']['notes'])
                            <p class="text-[11px] text-cyan-600 mt-1 italic">{{ $period['planned_task']['notes'] }}</p>
                          @endif
                        </div>
                      @else
                        <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100 text-emerald-800 text-xs">
                          <p class="font-bold">✨ Unallocated Working Period</p>
                          <p class="text-[11px] text-emerald-600 mt-0.5">Available for lesson prep, correction, mentoring or substitution duty.</p>
                        </div>
                      @endif
                    </div>
                  @else
                    <div class="text-xs text-slate-600 mb-3 p-2 rounded-lg bg-slate-50 border border-slate-100">
                      {{ $period['details'] }}
                    </div>
                  @endif
                </div>

                {{-- Period Action Footer --}}
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                  @if($period['can_report'])
                    @if($period['has_report'])
                      <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Report Filed ({{ $period['report']['present_count'] }} Pres)
                      </span>
                    @else
                      <button type="button"
                              @click="openReportModal({{ $period['timetable_id'] ?: 'null' }}, {{ $period['class_id'] }}, {{ $period['section_id'] }}, {{ $period['subject_id'] }}, {{ $period['period_number'] }}, '{{ $period['class_name'] }}', '{{ $period['section_name'] }}', '{{ $period['subject_name'] }}')"
                              class="w-full py-1.5 px-3 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition border border-indigo-200 flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Submit Teaching Report
                      </button>
                    @endif
                  @elseif($period['period_type'] === 'free_period')
                    @if($period['planned_task'])
                      <form method="POST" action="{{ route('staff-timetable.free-task.delete', $period['planned_task']['id']) }}" class="w-full flex items-center justify-between">
                        @csrf
                        @method('DELETE')
                        <span class="text-[11px] font-bold text-cyan-700">Reserved Slot</span>
                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-bold underline" onclick="return confirm('Clear this reserved free-period activity?')">
                          Clear Task
                        </button>
                      </form>
                    @else
                      <button type="button"
                              @click="openFreeTaskModal({{ $pNum }}, '{{ $targetDate->toDateString() }}')"
                              class="w-full py-1.5 px-3 rounded-lg text-xs font-bold bg-emerald-100/70 text-emerald-800 hover:bg-emerald-200/70 transition border border-emerald-300 flex items-center justify-center gap-1.5">
                        <span>📝</span>
                        <span>Book Activity / Task</span>
                      </button>
                    @endif
                  @else
                    <span class="text-xs text-slate-400 font-medium italic">Administrative / Special Slot</span>
                  @endif
                </div>

              </div>
            @endforeach
          </div>

          {{-- Scheduled Institutional Breaks Bar --}}
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center justify-between flex-wrap gap-3">
            <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
              <span>☕</span> Institutional Break Schedule:
            </span>
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-600 flex-wrap">
              <span>Morning Refreshment: 10:45 – 11:00 AM (15m)</span>
              <span>&bull;</span>
              <span>Lunch Break: 12:30 – 01:15 PM (45m)</span>
              <span>&bull;</span>
              <span>Afternoon Dispersal: 02:45 – 03:00 PM (15m)</span>
            </div>
            <span class="text-[11px] text-slate-400 italic">Excluded from teacher free-period metrics</span>
          </div>
        @endif
      </div>

    {{-- VIEW 2: WEEKLY TIMETABLE MATRIX --}}
    @elseif($viewMode === 'weekly')
      <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <h3 class="text-base font-extrabold text-slate-800">
              📊 Weekly Timetable Grid: {{ $weeklyMatrix['week_label'] ?? '' }}
            </h3>
            <p class="text-xs text-slate-500">Six working days (Monday to Saturday) cross-referenced across 8 standard period slots.</p>
          </div>

          <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
            <span>Weekly Teaching Periods: <strong class="text-indigo-600">{{ $weeklyMatrix['summary']['weekly_teaching_periods'] ?? 0 }}</strong></span>
            <span>&bull;</span>
            <span>Teaching Hours: <strong class="text-indigo-600">{{ $weeklyMatrix['summary']['weekly_teaching_hours'] ?? 0 }} hrs</strong></span>
            <span>&bull;</span>
            <span>Free Slots: <strong class="text-emerald-600">{{ $weeklyMatrix['summary']['weekly_free_periods'] ?? 0 }}</strong></span>
          </div>
        </div>

        {{-- Weekly Grid Matrix Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-900 text-white border-b border-slate-800">
                  <th class="p-3.5 font-black uppercase tracking-wider text-[11px] sticky left-0 bg-slate-900 z-10 w-28">
                    Day / Timing
                  </th>
                  @foreach(StaffTimetableService::getStandardPeriodsConfig() as $pNum => $slot)
                    <th class="p-3 font-extrabold text-center border-l border-slate-800 min-w-[125px]">
                      <div>{{ $slot['name'] }}</div>
                      <div class="text-[10px] font-normal text-slate-400 mt-0.5">
                        {{ Carbon\Carbon::parse($slot['start_time'])->format('h:i A') }} - {{ Carbon\Carbon::parse($slot['end_time'])->format('h:i A') }}
                      </div>
                    </th>
                    {{-- Insert visual break column headers --}}
                    @if($pNum == 2)
                      <th class="p-2 bg-slate-950/80 text-[10px] text-amber-300 text-center font-bold border-l border-slate-800 w-12 writing-mode-vertical">
                        Break
                      </th>
                    @elseif($pNum == 4)
                      <th class="p-2 bg-slate-950/80 text-[10px] text-amber-300 text-center font-bold border-l border-slate-800 w-14">
                        Lunch
                      </th>
                    @elseif($pNum == 6)
                      <th class="p-2 bg-slate-950/80 text-[10px] text-amber-300 text-center font-bold border-l border-slate-800 w-12">
                        Break
                      </th>
                    @endif
                  @endforeach
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                @foreach($weeklyMatrix['days'] ?? [] as $dayIdx => $dayData)
                  @php
                    $isCurrentDay = ($dayData['date'] === today()->toDateString());
                  @endphp
                  <tr class="transition hover:bg-slate-50/80 {{ $isCurrentDay ? 'bg-indigo-50/20' : '' }}">
                    {{-- Day Label Cell --}}
                    <td class="p-3.5 font-black text-slate-800 sticky left-0 bg-white z-10 border-r border-slate-200 {{ $isCurrentDay ? '!bg-indigo-50/60' : '' }}">
                      <div class="flex items-center gap-1.5">
                        @if($isCurrentDay)
                          <span class="w-2 h-2 rounded-full bg-indigo-600 animate-ping"></span>
                        @endif
                        <span class="text-xs uppercase tracking-tight">{{ $dayData['day_name'] }}</span>
                      </div>
                      <div class="text-[10px] font-medium text-slate-500 mt-0.5">
                        {{ Carbon\Carbon::parse($dayData['date'])->format('d M') }}
                      </div>
                    </td>

                    {{-- Period Cells --}}
                    @if($dayData['is_off_day'] ?? false)
                      <td colspan="11" class="p-4 text-center text-xs font-bold text-rose-600 bg-rose-50/30">
                        {{ $dayData['off_reason'] }}
                      </td>
                    @else
                      @foreach(StaffTimetableService::getStandardPeriodsConfig() as $pNum => $slot)
                        @php
                          $cell = $dayData['periods'][$pNum] ?? null;
                        @endphp
                        <td class="p-2 border-l border-slate-200 align-top">
                          @if($cell && $cell['period_type'] === 'regular_class')
                            <div class="p-2 rounded-xl bg-blue-50/70 border border-blue-200/80 hover:border-blue-300 transition">
                              <p class="font-extrabold text-blue-900 truncate leading-tight">{{ $cell['subject_name'] }}</p>
                              <p class="text-[11px] font-bold text-indigo-700 mt-0.5">{{ $cell['class_name'] }}-{{ $cell['section_name'] }}</p>
                              <div class="flex items-center justify-between text-[10px] text-slate-500 mt-1">
                                <span>{{ $cell['room'] }}</span>
                                @if($cell['has_report'])
                                  <span class="text-emerald-600 font-bold" title="Report filed">✓ Logged</span>
                                @endif
                              </div>
                            </div>
                          @elseif($cell && $cell['period_type'] === 'extra_class')
                            <div class="p-2 rounded-xl bg-purple-50 border border-purple-200">
                              <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-purple-200 text-purple-800 uppercase">Extra</span>
                              <p class="font-bold text-purple-900 truncate leading-tight mt-1">{{ $cell['subject_name'] }}</p>
                              <p class="text-[10px] text-purple-700">{{ $cell['class_name'] }}-{{ $cell['section_name'] }}</p>
                            </div>
                          @elseif($cell && $cell['period_type'] === 'substitution')
                            <div class="p-2 rounded-xl bg-amber-50 border border-amber-200">
                              <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-200 text-amber-900 uppercase">Sub</span>
                              <p class="font-bold text-amber-900 truncate leading-tight mt-1">{{ $cell['subject_name'] }}</p>
                              <p class="text-[10px] text-amber-800">{{ $cell['class_name'] }}-{{ $cell['section_name'] }}</p>
                            </div>
                          @elseif($cell && in_array($cell['period_type'], ['exam_duty', 'meeting', 'remedial', 'practical_lab']))
                            <div class="p-2 rounded-xl bg-orange-50 border border-orange-200">
                              <p class="font-bold text-orange-900 truncate leading-tight">{{ $cell['period_label'] }}</p>
                              <p class="text-[10px] text-orange-700 truncate mt-0.5">{{ $cell['room'] }}</p>
                            </div>
                          @else
                            {{-- Free Period Cell --}}
                            <div class="p-2 rounded-xl bg-emerald-50/40 border border-dashed border-emerald-200 text-center hover:bg-emerald-50 transition">
                              @if($cell && $cell['planned_task'])
                                <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-cyan-100 text-cyan-800">Task</span>
                                <p class="text-[10px] font-bold text-cyan-900 truncate mt-1">{{ $cell['planned_task']['activity_label'] }}</p>
                              @else
                                <span class="text-[10px] font-bold text-emerald-700">Free</span>
                                <button type="button"
                                        @click="openFreeTaskModal({{ $pNum }}, '{{ $dayData['date'] }}')"
                                        class="mt-1 text-[10px] text-emerald-600 hover:text-emerald-800 underline block w-full">
                                  + Book
                                </button>
                              @endif
                            </div>
                          @endif
                        </td>

                        {{-- Spacer break indicators matching header --}}
                        @if($pNum == 2 || $pNum == 4 || $pNum == 6)
                          <td class="bg-slate-100/60 border-l border-slate-200 text-center text-[10px] text-slate-400 font-bold">
                            &bull;
                          </td>
                        @endif
                      @endforeach
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

    {{-- VIEW 3: ACADEMIC TASKS, TESTS, HOMEWORK & REPORTS HUB --}}
    @elseif($viewMode === 'calendar')
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Column 1: Test & Examination Reminders --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <span>📝</span> Test Reminders
              </h3>
              <p class="text-xs text-slate-500">Upcoming exams & evaluation deadlines</p>
            </div>
            <button type="button" @click="openTestReminderModal()"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
              + New Test
            </button>
          </div>

          <div class="space-y-3">
            @forelse($upcomingTests as $test)
              <div class="p-3.5 rounded-xl border border-slate-200 hover:border-indigo-300 transition bg-slate-50/50">
                <div class="flex items-center justify-between">
                  <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-100 text-indigo-800 uppercase">
                    {{ str_replace('_', ' ', $test->test_type) }}
                  </span>
                  <span class="text-xs font-bold text-slate-600">{{ $test->test_date->format('d M Y') }}</span>
                </div>
                <h4 class="text-sm font-extrabold text-slate-800 mt-1.5">{{ $test->test_title }}</h4>
                <p class="text-xs text-slate-600 mt-0.5">
                  Class: <strong>{{ $test->class?->name }}-{{ $test->section?->name }}</strong> &bull; Subject: <strong>{{ $test->subject?->name }}</strong>
                </p>
                @if($test->paper_submission_deadline || $test->evaluation_deadline)
                  <div class="text-[11px] text-amber-700 mt-2 font-medium bg-amber-50 p-2 rounded-lg border border-amber-200">
                    @if($test->paper_submission_deadline)
                      <div>Paper Deadline: {{ $test->paper_submission_deadline->format('d M') }}</div>
                    @endif
                    @if($test->evaluation_deadline)
                      <div>Evaluation Due: {{ $test->evaluation_deadline->format('d M') }}</div>
                    @endif
                  </div>
                @endif
                <div class="mt-2.5 pt-2 border-t border-slate-200/80 flex items-center justify-end">
                  <form method="POST" action="{{ route('staff-timetable.test-reminder.delete', $test->id) }}" onsubmit="return confirm('Delete this test reminder?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-bold underline">Delete</button>
                  </form>
                </div>
              </div>
            @empty
              <div class="text-center py-8 text-slate-400 text-xs">
                No upcoming test reminders scheduled.
              </div>
            @endforelse
          </div>
        </div>

        {{-- Column 2: Recent Homework / Assignment Allocations --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <span>📚</span> Assignments & Homework
              </h3>
              <p class="text-xs text-slate-500">Allocated to assigned classes</p>
            </div>
            <a href="{{ route('classes.index') }}" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200">
              Manage
            </a>
          </div>

          <div class="space-y-3">
            @forelse($recentHomework as $hw)
              <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-indigo-700">{{ $hw->class?->name }} - {{ $hw->section?->name }}</span>
                  <span class="text-[11px] text-slate-500">Due: {{ $hw->due_date?->format('d M Y') }}</span>
                </div>
                <h4 class="text-sm font-bold text-slate-800 mt-1">{{ $hw->title }}</h4>
                <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $hw->description }}</p>
                <div class="mt-2 text-[11px] font-semibold text-slate-600">
                  Subject: {{ $hw->subject?->name }}
                </div>
              </div>
            @empty
              <div class="text-center py-8 text-slate-400 text-xs">
                No recent homework assignments found.
              </div>
            @endforelse
          </div>
        </div>

        {{-- Column 3: Period Teaching Reports Log --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <span>📋</span> Teaching Reports
              </h3>
              <p class="text-xs text-slate-500">Curriculum coverage & attendance</p>
            </div>
            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
              {{ $pendingReports->count() }} Reports
            </span>
          </div>

          <div class="space-y-3">
            @forelse($pendingReports as $rep)
              <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-extrabold text-slate-800">{{ Carbon\Carbon::parse($rep->date)->format('d M Y') }}</span>
                  <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded">
                    {{ $rep->student_present_count }} Present / {{ $rep->student_absent_count }} Absent
                  </span>
                </div>
                <p class="text-xs font-bold text-indigo-700 mt-1">
                  Class {{ $rep->class?->name }}-{{ $rep->section?->name }} &bull; {{ $rep->subject?->name }}
                </p>
                <p class="text-xs text-slate-600 mt-1 italic line-clamp-2">
                  "{{ $rep->description }}"
                </p>
              </div>
            @empty
              <div class="text-center py-8 text-slate-400 text-xs">
                No teaching reports filed yet.
              </div>
            @endforelse
          </div>
        </div>

      </div>
    @endif

  @endif

  {{-- ======================================================== --}}
  {{-- MODAL 1: SCHEDULE EXTRA CLASS MODAL                      --}}
  {{-- ======================================================== --}}
  <div x-show="showExtraClassModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeAllModals()"></div>

    <div class="min-h-full flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative z-10 space-y-4" @click.stop>
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-800">Schedule Extra Class</h3>
            <p class="text-xs text-slate-500">Checks teacher, class, and room availability automatically.</p>
          </div>
          <button type="button" @click="closeAllModals()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('staff-timetable.extra-class.store') }}" class="space-y-4">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ optional($selectedStaff)->id }}">

          <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl text-xs text-indigo-900 flex items-center justify-between font-medium">
            <span>Faculty Member: <strong>{{ optional($selectedStaff)->first_name }} {{ optional($selectedStaff)->last_name }}</strong></span>
            <span class="text-indigo-600">ID: {{ optional($selectedStaff)->employee_code }}</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Target Date *</label>
              <input type="date" name="date" x-model="extraClassForm.date" @change="checkConflicts()" required
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Period Slot *</label>
              <select name="period_number" x-model="extraClassForm.period_number" @change="checkConflicts()" required
                      class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                @foreach(StaffTimetableService::getStandardPeriodsConfig() as $pNum => $slot)
                  <option value="{{ $pNum }}">{{ $slot['name'] }} ({{ $slot['start_time'] }}-{{ $slot['end_time'] }})</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Class *</label>
              <select name="class_id" x-model="extraClassForm.class_id" @change="checkConflicts()" required
                      class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                <option value="">-- Choose Class --</option>
                @foreach($classes as $c)
                  <option value="{{ $c->id }}">{{ $c->display_name ?: $c->name }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Section *</label>
              <select name="section_id" x-model="extraClassForm.section_id" @change="checkConflicts()" required
                      class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                <option value="">-- Choose Section --</option>
                @foreach($classes as $c)
                  @foreach($c->sections as $sec)
                    <option value="{{ $sec->id }}">Class {{ $c->name }} - Sec {{ $sec->name }}</option>
                  @endforeach
                @endforeach
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Subject *</label>
              <select name="subject_id" required class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                <option value="">-- Choose Subject --</option>
                @foreach($subjects as $sub)
                  <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code }})</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Classroom / Venue</label>
              <input type="text" name="room" x-model="extraClassForm.room" @change="checkConflicts()" placeholder="e.g. Room 102 or Lab"
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Extra Class</label>
            <input type="text" name="reason" placeholder="e.g. Syllabus coverage, Revision, Remedial doubt clearing"
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500">
          </div>

          {{-- Conflict Alert Box --}}
          <div x-show="conflictStatus.checked" class="p-3 rounded-xl text-xs"
               :class="conflictStatus.hasConflict ? 'bg-rose-50 border border-rose-200 text-rose-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-800'">
            <div class="font-bold flex items-center gap-1.5" x-text="conflictStatus.hasConflict ? '⚠️ Schedule Conflict Detected' : '✓ Slot Available'"></div>
            <template x-for="err in conflictStatus.conflicts" :key="err">
              <p class="text-[11px] mt-1 text-rose-700" x-text="err"></p>
            </template>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="closeAllModals()" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100">
              Cancel
            </button>
            <button type="submit" :disabled="conflictStatus.hasConflict"
                    class="px-5 py-2 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition disabled:opacity-50">
              Confirm & Schedule
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ======================================================== --}}
  {{-- MODAL 2: BOOK FREE PERIOD TASK MODAL                     --}}
  {{-- ======================================================== --}}
  <div x-show="showFreeTaskModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeAllModals()"></div>

    <div class="min-h-full flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative z-10 space-y-4" @click.stop>
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-800">Reserve Free Period Activity</h3>
            <p class="text-xs text-slate-500">Plan constructive academic work during this available period.</p>
          </div>
          <button type="button" @click="closeAllModals()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('staff-timetable.free-task.store') }}" class="space-y-4">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ optional($selectedStaff)->id }}">
          <input type="hidden" name="date" x-model="freeTaskForm.date">
          <input type="hidden" name="period_number" x-model="freeTaskForm.period_number">

          <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 font-medium">
            Slot: <strong x-text="'Period ' + freeTaskForm.period_number"></strong> on <strong x-text="freeTaskForm.date"></strong>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Permitted Activity Type *</label>
            <select name="activity_type" required class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
              <option value="lesson_preparation">Lesson Preparation & Planning</option>
              <option value="assignment_correction">Assignment / Notebook Correction</option>
              <option value="student_mentoring">Individual Student Mentoring</option>
              <option value="remedial_planning">Remedial Support Planning</option>
              <option value="qp_preparation">Question Paper Preparation</option>
              <option value="teaching_report">Teaching Report Completion</option>
              <option value="academic_meeting">Academic / Department Meeting</option>
              <option value="library_lab_prep">Library / Laboratory Preparation</option>
              <option value="administrative_work">Administrative Documentation</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Task Title / Objective *</label>
            <input type="text" name="title" required placeholder="e.g. Correct Class 9 Unit Test papers"
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Notes / Remarks</label>
            <textarea name="notes" rows="2" placeholder="Optional details..."
                      class="w-full p-2.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500"></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="closeAllModals()" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition">
              Reserve Free Period
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ======================================================== --}}
  {{-- MODAL 3: CREATE TEST REMINDER MODAL                      --}}
  {{-- ======================================================== --}}
  <div x-show="showTestReminderModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeAllModals()"></div>

    <div class="min-h-full flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative z-10 space-y-4" @click.stop>
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-800">Schedule Test / Exam Reminder</h3>
            <p class="text-xs text-slate-500">Set exam schedule and deadlines for paper submission & evaluation.</p>
          </div>
          <button type="button" @click="closeAllModals()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('staff-timetable.test-reminder.store') }}" class="space-y-4">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ optional($selectedStaff)->id }}">

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Test Title *</label>
            <input type="text" name="test_title" required placeholder="e.g. Unit Test 2 — Coordinate Geometry"
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Test Type *</label>
              <select name="test_type" required class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                <option value="class_test">Class Test (Daily/Weekly)</option>
                <option value="unit_test">Unit Test</option>
                <option value="model_exam">Model Examination</option>
                <option value="term_exam">Term / Semester Exam</option>
                <option value="revision_test">Revision Test</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Test Date *</label>
              <input type="date" name="test_date" required value="{{ today()->addDays(3)->toDateString() }}"
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Class *</label>
              <select name="class_id" required class="w-full h-9 px-2 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                @foreach($classes as $c)
                  <option value="{{ $c->id }}">{{ $c->display_name ?: $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Section *</label>
              <select name="section_id" required class="w-full h-9 px-2 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                @foreach($classes as $c)
                  @foreach($c->sections as $sec)
                    <option value="{{ $sec->id }}">Class {{ $c->name }}-{{ $sec->name }}</option>
                  @endforeach
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Subject *</label>
              <select name="subject_id" required class="w-full h-9 px-2 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
                @foreach($subjects as $sub)
                  <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Max Marks</label>
              <input type="number" name="max_marks" value="25" min="5" max="200"
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">QP Submission Due</label>
              <input type="date" name="paper_submission_deadline"
                     class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Evaluation Due</label>
              <input type="date" name="evaluation_deadline"
                     class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Syllabus / Topics Covered</label>
            <input type="text" name="syllabus_details" placeholder="e.g. Chapter 4 and 5, Exercises 4.1 to 4.3"
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs">
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="closeAllModals()" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">
              Create Test Reminder
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ======================================================== --}}
  {{-- MODAL 4: ASSIGN STAFF DUTY MODAL (Admin Only)            --}}
  {{-- ======================================================== --}}
  @if($isElevated)
  <div x-show="showDutyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeAllModals()"></div>

    <div class="min-h-full flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative z-10 space-y-4" @click.stop>
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-800">Assign Staff Duty</h3>
            <p class="text-xs text-slate-500">Allocate exam invigilation, staff meeting, or lab duty.</p>
          </div>
          <button type="button" @click="closeAllModals()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('staff-timetable.duty.store') }}" class="space-y-4">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ optional($selectedStaff)->id }}">

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Duty Classification *</label>
            <select name="duty_type" required class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-indigo-500">
              <option value="exam_duty">Examination Invigilation Duty</option>
              <option value="meeting">Staff / Academic Meeting</option>
              <option value="remedial">Remedial Teaching Session</option>
              <option value="practical_lab">Practical / Laboratory Duty</option>
              <option value="activity_period">Activity / Assembly Period</option>
              <option value="leave_unavailable">Leave / Unavailable Slot</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Duty Title / Event *</label>
            <input type="text" name="title" required placeholder="e.g. Midterm Physics Hall Invigilation"
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Date *</label>
              <input type="date" name="date" required value="{{ $targetDate->toDateString() }}"
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Period Slot *</label>
              <select name="period_number" required class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-semibold">
                @foreach(StaffTimetableService::getStandardPeriodsConfig() as $pNum => $slot)
                  <option value="{{ $pNum }}">{{ $slot['name'] }} ({{ $slot['start_time'] }}-{{ $slot['end_time'] }})</option>
                @endforeach
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Venue / Room</label>
            <input type="text" name="room" placeholder="e.g. Auditorium Hall 1 or Physics Lab"
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs">
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="closeAllModals()" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white transition">
              Assign Duty
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif

  {{-- ======================================================== --}}
  {{-- MODAL 5: TEACHING REPORT MODAL                           --}}
  {{-- ======================================================== --}}
  <div x-show="showReportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeAllModals()"></div>

    <div class="min-h-full flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative z-10 space-y-4" @click.stop>
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-800">Submit Period Teaching Report</h3>
            <p class="text-xs text-slate-500">Record classroom syllabus coverage, learning outcomes, and attendance.</p>
          </div>
          <button type="button" @click="closeAllModals()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitTeachingReport()" class="space-y-4">
          <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl text-xs text-indigo-900 space-y-1">
            <div class="font-bold text-sm" x-text="reportForm.subject_name"></div>
            <div>Class: <strong x-text="reportForm.class_name + ' - Section ' + reportForm.section_name"></strong> &bull; Period <strong x-text="reportForm.period_number"></strong></div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Students Present *</label>
              <input type="number" x-model="reportForm.present_count" min="0" required
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-emerald-700 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Students Absent *</label>
              <input type="number" x-model="reportForm.absent_count" min="0" required
                     class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-rose-700 focus:ring-1 focus:ring-indigo-500">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Topics Covered / Teaching Description *</label>
            <textarea x-model="reportForm.description" rows="4" required placeholder="Detail the syllabus covered, teaching methodology, student participation, and homework assigned..."
                      class="w-full p-3 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500"></textarea>
          </div>

          <div x-show="reportError" class="p-3 bg-rose-50 text-rose-800 rounded-xl text-xs font-medium" x-text="reportError"></div>
          <div x-show="reportSuccess" class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-medium" x-text="reportSuccess"></div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="closeAllModals()" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100">
              Close
            </button>
            <button type="submit" :disabled="reportSaving"
                    class="px-5 py-2 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition disabled:opacity-50">
              <span x-text="reportSaving ? 'Saving...' : 'Save Teaching Report'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

{{-- Alpine.js State Manager --}}
<script>
function staffTimetableApp() {
  return {
    showExtraClassModal: false,
    showFreeTaskModal: false,
    showTestReminderModal: false,
    showDutyModal: false,
    showReportModal: false,

    extraClassForm: {
      date: '{{ $targetDate->toDateString() }}',
      period_number: 1,
      class_id: '',
      section_id: '',
      room: '',
    },

    conflictStatus: {
      checked: false,
      hasConflict: false,
      conflicts: [],
    },

    freeTaskForm: {
      date: '{{ $targetDate->toDateString() }}',
      period_number: 1,
    },

    reportForm: {
      timetable_id: null,
      class_id: null,
      section_id: null,
      subject_id: null,
      period_number: null,
      class_name: '',
      section_name: '',
      subject_name: '',
      present_count: 35,
      absent_count: 2,
      description: '',
      date: '{{ $targetDate->toDateString() }}',
    },
    reportSaving: false,
    reportError: '',
    reportSuccess: '',

    openExtraClassModal() {
      this.showExtraClassModal = true;
    },

    openFreeTaskModal(periodNum, date) {
      this.freeTaskForm.period_number = periodNum;
      this.freeTaskForm.date = date;
      this.showFreeTaskModal = true;
    },

    openTestReminderModal() {
      this.showTestReminderModal = true;
    },

    openDutyModal() {
      this.showDutyModal = true;
    },

    openReportModal(timetableId, classId, sectionId, subjectId, periodNum, className, sectionName, subjectName) {
      this.reportForm.timetable_id = timetableId;
      this.reportForm.class_id = classId;
      this.reportForm.section_id = sectionId;
      this.reportForm.subject_id = subjectId;
      this.reportForm.period_number = periodNum;
      this.reportForm.class_name = className;
      this.reportForm.section_name = sectionName;
      this.reportForm.subject_name = subjectName;
      this.reportForm.description = '';
      this.reportError = '';
      this.reportSuccess = '';
      this.showReportModal = true;
    },

    closeAllModals() {
      this.showExtraClassModal = false;
      this.showFreeTaskModal = false;
      this.showTestReminderModal = false;
      this.showDutyModal = false;
      this.showReportModal = false;
    },

    async checkConflicts() {
      if (!this.extraClassForm.date || !this.extraClassForm.period_number) return;

      try {
        const params = new URLSearchParams({
          teacher_id: '{{ optional($selectedStaff)->id }}',
          date: this.extraClassForm.date,
          period_number: this.extraClassForm.period_number,
          class_id: this.extraClassForm.class_id,
          section_id: this.extraClassForm.section_id,
          room: this.extraClassForm.room,
        });

        const res = await fetch(`{{ route('staff-timetable.api.conflict-check') }}?${params}`);
        const data = await res.json();
        this.conflictStatus.checked = true;
        this.conflictStatus.hasConflict = data.has_conflict;
        this.conflictStatus.conflicts = data.conflicts || [];
      } catch (e) {
        console.error('Conflict check error', e);
      }
    },

    async submitTeachingReport() {
      this.reportSaving = true;
      this.reportError = '';
      this.reportSuccess = '';

      try {
        const payload = {
          timetable_id: this.reportForm.timetable_id,
          class_id: this.reportForm.class_id,
          section_id: this.reportForm.section_id,
          subject_id: this.reportForm.subject_id,
          period_number: this.reportForm.period_number,
          present_count: this.reportForm.present_count,
          absent_count: this.reportForm.absent_count,
          description: this.reportForm.description,
          date: this.reportForm.date,
          _token: '{{ csrf_token() }}',
        };

        const res = await fetch(`{{ route('timetable.period-report.save') }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
          },
          body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (res.ok && data.success) {
          this.reportSuccess = 'Teaching report successfully saved!';
          setTimeout(() => {
            window.location.reload();
          }, 800);
        } else {
          this.reportError = data.message || 'Validation error while submitting teaching report.';
        }
      } catch (err) {
        this.reportError = 'Network error while submitting report. Please try again.';
      } finally {
        this.reportSaving = false;
      }
    }
  };
}
</script>
@endsection
