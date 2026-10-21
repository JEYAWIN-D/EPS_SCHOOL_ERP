@extends('layouts.app')
@section('title','Student Promotion')
@section('content')
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="page-title">Student Promotion &amp; Rollover</h1>
      <p class="page-subtitle">Promote students across academic years with independent class &amp; section list generation.</p>
    </div>
    <a href="{{ route('students.roll-numbers.index') }}" class="btn btn-secondary btn-sm text-indigo-700 bg-indigo-50 border-indigo-200">
      <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
      <span>Roll Number Generator</span>
    </a>
  </div>
  <div class="grid grid-cols-3 gap-4">
    <div class="card text-center py-5"><p class="text-3xl font-bold text-green-600">{{ $stats['promoted'] ?? 0 }}</p><p class="text-sm text-slate-500 mt-1">Promoted</p></div>
    <div class="card text-center py-5"><p class="text-3xl font-bold text-amber-600">{{ $stats['detained'] ?? 0 }}</p><p class="text-sm text-slate-500 mt-1">Detained</p></div>
    <div class="card text-center py-5"><p class="text-3xl font-bold text-slate-600">{{ $stats['pending'] ?? 0 }}</p><p class="text-sm text-slate-500 mt-1">Pending</p></div>
  </div>
  <div class="card">
    <h3 class="font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100">Bulk Promotion</h3>
    <form method="POST" action="{{ route('students.promotions.bulk') }}" class="space-y-4" x-data="{confirm:false}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><label class="label">From Class <span class="text-red-500">*</span></label>
          <select name="from_class_id" class="select" required>
            <option value="">Select</option>
            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select>
        </div>
        <div><label class="label">To Class (next year) <span class="text-red-500">*</span></label>
          <select name="to_class_id" class="select" required>
            <option value="">Select target class</option>
            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select>
        </div>
        <div><label class="label">Target Academic Year</label>
          <select name="to_academic_year_id" class="select">
            @foreach($academicYears as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
          </select>
        </div>
      </div>
      <div class="flex items-center gap-2 pt-1">
        <input type="checkbox" id="bulk_generate_rolls" name="generate_roll_numbers" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        <label for="bulk_generate_rolls" class="text-xs text-slate-700 font-medium">
          Generate independent alphabetical section roll numbers for the promoted class in the target academic year.
        </label>
      </div>
      <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-700">
        ⚠️ This will promote ALL active students of the selected class. Individual overrides can be done from each student profile. Permanent admission numbers are protected.
      </div>
      <button type="submit" class="btn btn-primary" @click.prevent="confirm=true" x-show="!confirm">Promote All Students</button>
      <div x-show="confirm" class="flex items-center gap-3">
        <span class="text-sm text-slate-600">Are you sure? This cannot be undone.</span>
        <button type="submit" class="btn btn-primary">Yes, Promote</button>
        <button type="button" @click="confirm=false" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
  {{-- Selective Promotion (exam-based / manual) --}}
  <div class="card">
    <h3 class="font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100">Selective Promotion</h3>
    @if($preselectIds)
        <div class="alert-info mb-4 text-sm">
            {{ count($preselectIds) }} student(s) pre-selected from exam eligibility check.
        </div>
    @endif
    <form method="GET" class="flex flex-wrap gap-4 items-end mb-5">
        <div>
            <label class="label">From Class</label>
            <select name="class_id" class="select">
                <option value="">Select to load students</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @if(request('preselect_ids'))
            <input type="hidden" name="preselect_ids" value="{{ request('preselect_ids') }}">
        @endif
        <button type="submit" class="btn btn-secondary">Load Students</button>
        <a href="{{ route('examinations.promotion-eligibility') }}" class="btn btn-secondary text-sm">Check Eligibility by Exam</a>
    </form>

    @if($fromClassStudents->isNotEmpty())
    <form method="POST" action="{{ route('students.promotions.process') }}" x-data="{confirm:false}">
        @csrf
        <input type="hidden" name="from_class_id" value="{{ request('class_id') }}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">To Class <span class="text-rose-500">*</span></label>
            <select name="to_class_id" class="select" required>
                <option value="">Select target class</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
          </div>
          <div>
            <label class="label">Target Academic Year</label>
            <select name="to_academic_year_id" class="select">
                @foreach($academicYears as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
            </select>
          </div>
        </div>
        <div class="flex items-center gap-2 mb-4">
          <input type="checkbox" id="selective_generate_rolls" name="generate_roll_numbers" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
          <label for="selective_generate_rolls" class="text-xs text-slate-700 font-medium">
            Generate independent alphabetical section roll numbers for promoted students in the target class.
          </label>
        </div>
        <div class="mb-4 flex gap-3">
            <button type="button" onclick="document.querySelectorAll('.promo-cb').forEach(c=>c.checked=true)" class="btn btn-secondary text-xs">Select All</button>
            <button type="button" onclick="document.querySelectorAll('.promo-cb').forEach(c=>c.checked=false)" class="btn btn-secondary text-xs">Deselect All</button>
        </div>
        <div class="table-wrap mb-4">
            <table class="w-full">
                <thead><tr>
                    <th class="th w-10"><input type="checkbox" onclick="document.querySelectorAll('.promo-cb').forEach(c=>c.checked=this.checked)"></th>
                    <th class="th">Student</th>
                    <th class="th">Admission No.</th>
                </tr></thead>
                <tbody>
                    @foreach($fromClassStudents as $enrollment)
                    <tr class="tr">
                        <td class="td"><input type="checkbox" name="student_ids[]" value="{{ $enrollment->student_id }}" class="promo-cb"
                            {{ in_array($enrollment->student_id, $preselectIds) ? 'checked' : '' }}></td>
                        <td class="td font-medium">{{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}</td>
                        <td class="td text-slate-500">{{ $enrollment->student?->admission_number }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div x-show="!confirm">
            <button type="button" @click="confirm=true" class="btn btn-primary">Promote Selected</button>
        </div>
        <div x-show="confirm" class="flex items-center gap-3">
            <span class="text-sm text-slate-600">Promote selected students?</span>
            <button type="submit" class="btn btn-primary">Confirm</button>
            <button type="button" @click="confirm=false" class="btn btn-secondary">Cancel</button>
        </div>
    </form>
    @elseif(request('class_id'))
        <p class="text-slate-400 text-sm">No active enrollments found for this class.</p>
    @endif
  </div>

  <div class="card overflow-hidden">
    <h3 class="font-semibold text-slate-700 px-4 pt-4 pb-2">Promotion History</h3>
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b"><tr>
        @foreach(['Student','From Class','To Class','From Year','To Year','Status','Date'] as $h)
        <th class="text-left px-4 py-3 text-slate-500 text-xs uppercase font-medium tracking-wide">{{ $h }}</th>
        @endforeach
      </tr></thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($promotions as $p)
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-2 font-medium text-slate-800">{{ $p->student?->full_name }}</td>
          <td class="px-4 py-2 text-slate-500">{{ $p->fromClass?->name }}</td>
          <td class="px-4 py-2 text-slate-500">{{ $p->toClass?->name ?? '—' }}</td>
          <td class="px-4 py-2 text-slate-400 text-xs">{{ $p->fromAcademicYear?->name ?? '—' }}</td>
          <td class="px-4 py-2 text-slate-400 text-xs">{{ $p->toAcademicYear?->name ?? '—' }}</td>
          <td class="px-4 py-2"><span class="badge-{{ $p->status === 'promoted' ? 'green' : ($p->status === 'detained' ? 'amber' : 'slate') }} capitalize">{{ $p->status }}</span></td>
          <td class="px-4 py-2 text-slate-400 text-xs">{{ $p->promoted_at?->format('d M Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No promotion records.</td></tr>
        @endforelse
      </tbody>
    </table>
    @if($promotions->hasPages())<div class="px-4 pb-3">{{ $promotions->links() }}</div>@endif
  </div>
</div>
@endsection
