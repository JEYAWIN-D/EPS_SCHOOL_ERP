@extends('layouts.app')
@section('title','Teacher Subject Allocation & Teaching Levels')
@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Academics</span>
        <span>/</span>
        <span class="text-indigo-600">Teaching Assignments</span>
      </div>
      <h1 class="page-title">Teacher Subject &amp; Level Allocation</h1>
      <p class="text-sm text-slate-500 mt-1">Allocate teachers to subjects, configure teaching level eligibility, and enforce workload limits.</p>
    </div>
    <a href="{{ route('academics.allocation.history') }}" class="btn btn-secondary btn-sm">View History →</a>
  </div>

  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
      <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
      <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- Teaching Level Overview Badges --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    @foreach($teachingLevels as $lvl)
      <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-xs flex items-center justify-between">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Level {{ $lvl->id }}</span>
          <p class="text-sm font-bold text-slate-800">{{ $lvl->name }}</p>
          <p class="text-[11px] text-slate-400">{{ $lvl->code === 'kg' ? 'Activity-based' : 'Period-based' }}</p>
        </div>
        <span class="w-2.5 h-2.5 rounded-full {{ $lvl->code === 'kg' ? 'bg-amber-400' : ($lvl->code === 'primary' ? 'bg-emerald-400' : ($lvl->code === 'secondary' ? 'bg-sky-400' : 'bg-purple-400')) }}"></span>
      </div>
    @endforeach
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Assign Form --}}
    <form method="POST" action="{{ route('academics.allocation.save') }}" class="card space-y-4">
      @csrf
      <h3 class="font-semibold text-slate-800 pb-2 border-b border-slate-100 flex items-center justify-between">
        <span>Allocate Subject &amp; Class</span>
        <span class="text-xs text-indigo-600 font-medium">Eligible levels validated</span>
      </h3>

      <div>
        <label class="label">Teacher <span class="text-red-500">*</span></label>
        <select name="employee_id" class="select" required>
          <option value="">Select teacher</option>
          @foreach($teachers as $t)
            @php
              $approvedLevels = $t->eligibleTeachingLevels->pluck('name')->join(', ');
            @endphp
            <option value="{{ $t->id }}">
              {{ $t->full_name }} ({{ $t->employee_code }}) {{ $approvedLevels ? '— [' . $approvedLevels . ']' : '' }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="label">Subject <span class="text-red-500">*</span></label>
        <select name="subject_id" class="select" required>
          <option value="">Select subject</option>
          @foreach($subjects as $s)
            <option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->type ?? 'theory') }})</option>
          @endforeach
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Class <span class="text-red-500">*</span></label>
          <select name="class_id" class="select" required>
            <option value="">Select class</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}">
                {{ $c->name }} {{ $c->teachingLevel ? '— ' . $c->teachingLevel->name : '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Section</label>
          <select name="section_id" class="select">
            <option value="">All sections</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-500 space-y-1">
        <p class="font-semibold text-slate-700 flex items-center gap-1.5">
          <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Academic Validation Rules:
        </p>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-500">
          <li>Teachers must be approved for the academic teaching level of the class.</li>
          <li>System enforces maximum weekly periods workload limits if configured.</li>
          <li>Timetable slots automatically pull verified allocations.</li>
        </ul>
      </div>

      <button type="submit" class="btn btn-primary">Save Allocation</button>
    </form>

    {{-- Current Allocations Table --}}
    <div class="card space-y-3">
      <h3 class="font-semibold text-slate-800 pb-2 border-b border-slate-100 flex items-center justify-between">
        <span>Current Active Allocations</span>
        <span class="text-xs font-bold text-slate-400 font-mono">{{ $allocations->total() }} Records</span>
      </h3>

      {{-- Filters --}}
      <form method="GET" class="flex gap-2 flex-wrap">
        <select name="academic_year_id" class="select text-xs py-1.5 w-36" onchange="this.form.submit()">
          <option value="">All Years</option>
          @foreach($academicYears as $y)
            <option value="{{ $y->id }}" @selected($filterYearId==$y->id)>{{ $y->name }}</option>
          @endforeach
        </select>
        <select name="teacher_id" class="select text-xs py-1.5 w-44" onchange="this.form.submit()">
          <option value="">All Teachers</option>
          @foreach($teachers as $t)
            <option value="{{ $t->id }}" @selected(request('teacher_id')==$t->id)>{{ $t->full_name }}</option>
          @endforeach
        </select>
        <select name="class_id" class="select text-xs py-1.5 w-32" onchange="this.form.submit()">
          <option value="">All Classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected(request('class_id')==$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </form>

      <div class="overflow-y-auto max-h-96 divide-y divide-slate-100">
        @forelse($allocations as $a)
          <div class="flex items-start justify-between py-2.5">
            <div>
              <div class="flex items-center gap-1.5">
                <p class="text-sm font-bold text-slate-900">{{ $a->employee?->full_name }}</p>
                @if($a->class?->teachingLevel)
                  <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $a->class->teachingLevel->name }}
                  </span>
                @endif
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                <span class="font-semibold text-slate-700">{{ $a->subject?->name }}</span> &bull; 
                {{ $a->class?->name }} {{ $a->section ? 'Section ' . $a->section->name : '(All Sections)' }}
                @if($a->academicYear)
                  <span class="ml-1 text-indigo-600 font-mono">[{{ $a->academicYear->name }}]</span>
                @endif
              </p>
            </div>
            <form method="POST" action="{{ route('academics.allocation.delete', $a->id) }}" onsubmit="return confirm('Remove this subject allocation?');">
              @csrf @method('DELETE')
              <button type="submit" class="text-red-500 hover:text-red-700 text-xs ml-3 font-semibold shrink-0">Remove</button>
            </form>
          </div>
        @empty
          <p class="text-slate-400 text-sm text-center py-6">No allocations for the selected filters.</p>
        @endforelse
      </div>
      @if($allocations->hasPages())
        <div class="text-xs mt-2">{{ $allocations->links() }}</div>
      @endif
    </div>

  </div>

  {{-- Section 14: Teacher Level Eligibility & Workload Configuration --}}
  <div class="card p-5 border border-slate-200 space-y-4">
    <div>
      <h3 class="text-base font-bold text-slate-900">Teaching Level Eligibility &amp; Workload Configuration (Section 14)</h3>
      <p class="text-xs text-slate-500 mt-0.5">Configure which teaching levels (KG, Primary, Secondary, Higher Secondary) each teacher is eligible to instruct, and set maximum weekly periods.</p>
    </div>

    <form method="POST" action="{{ route('academics.teacher-eligibility.save') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
      @csrf

      <div>
        <label class="label">Teacher <span class="text-red-500">*</span></label>
        <select name="employee_id" class="select" required>
          <option value="">Select teacher to configure</option>
          @foreach($teachers as $t)
            <option value="{{ $t->id }}">
              {{ $t->full_name }} ({{ $t->employee_code }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="label">Approved Teaching Levels <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-2 gap-2 mt-1">
          @foreach($teachingLevels as $lvl)
            <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 cursor-pointer hover:bg-slate-100">
              <input type="checkbox" name="teaching_levels[]" value="{{ $lvl->id }}" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
              <span>{{ $lvl->name }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div>
        <label class="label">Max Weekly Periods</label>
        <input type="number" name="max_weekly_periods" min="1" max="60" placeholder="e.g. 28 periods" class="input">
      </div>

      <div class="md:col-span-4 flex justify-end">
        <button type="submit" class="btn btn-secondary text-xs">
          Save Teacher Level Eligibility &amp; Workload
        </button>
      </div>
    </form>
  </div>

</div>
@endsection
