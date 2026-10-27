@extends('layouts.app')

@section('title', 'Section & Roll Number Generator')

@section('content')
<div class="space-y-5" x-data="{
  confirmModal: false,
  confirmAgreed: false,
  selectedClassId: '{{ $selectedClassId }}',
  selectedSectionId: '{{ $selectedSectionId }}',
  selectedYearId: '{{ $selectedYearId }}',
  prefixStyle: '{{ request('prefix_style', 'standard') }}'
}">

  {{-- ── Top Navigation & Title ───────────────────────────────────────── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100">
    <div>
      <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium mb-1">
        <a href="{{ route('students.index') }}" class="hover:text-slate-700 transition">Students</a>
        <span>/</span>
        <span class="text-slate-600">Roll Number Generator</span>
      </nav>
      <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
        Alphabetical Roll Number Generator
      </h1>
      <p class="page-subtitle text-xs text-slate-500 mt-0.5">
        Automatically sorts students alphabetically (A to Z) per section and issues sequential roll numbers.
      </p>
    </div>

    <div class="flex items-center gap-2 shrink-0 flex-wrap">
      <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm">
        &larr; Back to Students
      </a>
      <a href="{{ route('students.section-allotment.index', array_filter(['class_id' => $selectedClassId, 'academic_year_id' => $selectedYearId])) }}" class="btn btn-secondary btn-sm">
        Section Allotment
      </a>
      <a href="{{ route('students.house-allotment.index', array_filter(['academic_year_id' => $selectedYearId])) }}" class="btn btn-secondary btn-sm">
        House Allotment
      </a>
      <a href="{{ route('students.roll-numbers.print', array_filter(['class_id' => $selectedClassId, 'section_id' => $selectedSectionId, 'academic_year_id' => $selectedYearId])) }}" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-1 font-semibold">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print Roster
      </a>
      <a href="{{ route('students.roll-numbers.export', array_filter(['class_id' => $selectedClassId, 'section_id' => $selectedSectionId, 'academic_year_id' => $selectedYearId])) }}" class="btn btn-ghost btn-sm text-slate-600 border border-slate-200 hover:bg-slate-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export CSV
      </a>
    </div>
  </div>

  {{-- ── How It Works (Simple, Plain-English Guide) ──────────────────── --}}
  <div class="bg-gradient-to-r from-slate-50 to-indigo-50/40 border border-slate-200/80 rounded-2xl p-4 text-xs text-slate-600 space-y-2">
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#8C2826] text-white text-[10px] font-bold">?</span>
      <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">How Roll Number Generation Works:</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-0.5 text-[11.5px] leading-relaxed">
      <div class="p-2.5 rounded-xl bg-white border border-slate-200/70 shadow-2xs space-y-1">
        <p class="font-bold text-slate-800">1. Separate Sorting per Section</p>
        <p class="text-slate-500">Class 6-A, 6-B, and 6-C are each sorted in their own independent alphabetical order starting at <strong>001</strong> (6A001.., 6B001..).</p>
      </div>
      <div class="p-2.5 rounded-xl bg-white border border-slate-200/70 shadow-2xs space-y-1">
        <p class="font-bold text-slate-800">2. Permanent Admission No. Protected</p>
        <p class="text-slate-500">The student's permanent school admission number is <strong>never modified</strong>. Only the section roll number is updated.</p>
      </div>
      <div class="p-2.5 rounded-xl bg-white border border-slate-200/70 shadow-2xs space-y-1">
        <p class="font-bold text-slate-800">3. Identical Names Rule</p>
        <p class="text-slate-500">If two students share the same name, the older student (by Date of Birth) is placed first.</p>
      </div>
    </div>
  </div>

  {{-- ── Class & Section Selector Card ────────────────────────────────── --}}
  <div class="card p-4 sm:p-5 space-y-3">
    <form method="GET" action="{{ route('students.roll-numbers.index') }}">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        {{-- Academic Year --}}
        <div>
          <label class="label text-[11px] text-slate-600 font-semibold mb-1 block">Academic Year</label>
          <select name="academic_year_id" class="select text-xs w-full">
            @foreach($academicYears as $yr)
              <option value="{{ $yr->id }}" @selected($selectedYearId == $yr->id)>
                {{ $yr->name }} {{ $yr->id === $currentYear?->id ? '(Current Year)' : '' }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Class --}}
        <div>
          <label class="label text-[11px] text-slate-600 font-semibold mb-1 block">Class <span class="text-rose-500">*</span></label>
          <select name="class_id" class="select text-xs w-full" onchange="this.form.submit()">
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>
                Class {{ $c->name }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Section --}}
        <div>
          <label class="label text-[11px] text-slate-600 font-semibold mb-1 block">Section</label>
          <select name="section_id" class="select text-xs w-full">
            <option value="">All Sections (Separate 6-A, 6-B, 6-C...)</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}" @selected($selectedSectionId == $s->id)>
                Section {{ $s->name }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Roll Number Style & Submit --}}
        <div class="flex items-center gap-2">
          <div class="flex-1">
            <label class="label text-[11px] text-slate-600 font-semibold mb-1 block">Roll Number Style</label>
            <select name="prefix_style" class="select text-xs w-full" x-model="prefixStyle">
              <option value="standard">Numeric (6A001)</option>
              <option value="roman">Roman (VIA001)</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm shrink-0 self-end">
            Load Students
          </button>
        </div>
      </div>
    </form>
  </div>

  {{-- ── Preview Results & Action Bar ─────────────────────────────────── --}}
  @if($initialPreview && $initialPreview['total_students'] > 0)
    <div class="space-y-4">

      {{-- Action Bar --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <span class="font-bold text-slate-900 text-sm">Class {{ $initialPreview['class_name'] }}</span>
          <span class="text-slate-300">&bull;</span>
          <span class="text-slate-600 font-semibold">{{ $initialPreview['total_students'] }} Students</span>
          <span class="text-slate-300">&bull;</span>
          <span class="text-slate-500">{{ count($initialPreview['sections']) }} Section(s)</span>
          @if($initialPreview['total_changed'] > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-amber-50 text-amber-800 border border-amber-200 text-[11px] ml-1">
              {{ $initialPreview['total_changed'] }} will update to A–Z order
            </span>
          @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] ml-1">
              ✓ All already in correct order
            </span>
          @endif
        </div>

        <button type="button"
                @click="confirmModal = true"
                class="btn btn-primary btn-sm inline-flex items-center gap-1.5 shrink-0 shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Apply Alphabetical Roll Numbers</span>
        </button>
      </div>

      {{-- Section Tables --}}
      @foreach($initialPreview['sections'] as $sec)
        <div class="card overflow-hidden border border-slate-200/90 shadow-2xs space-y-0">
          <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-[#8C2826]"></span>
              <h3 class="text-xs font-bold text-slate-900">
                Class {{ $initialPreview['class_name'] }} &mdash; Section {{ $sec['section_name'] }}
              </h3>
              <span class="text-xs text-slate-500">({{ $sec['student_count'] }} students)</span>
              <span class="text-[11px] font-mono text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                Sequence: {{ $sec['class_code'] }}{{ $sec['section_code'] }}001..
              </span>
            </div>
            <div class="text-[11px] text-slate-500">
              @if($sec['changed_count'] > 0)
                <span class="font-bold text-amber-700">{{ $sec['changed_count'] }} changing position</span>
              @else
                <span class="text-emerald-700 font-medium">Already alphabetical</span>
              @endif
            </div>
          </div>

          <div class="table-wrap">
            <table class="w-full text-xs">
              <thead>
                <tr>
                  <th class="th w-12 text-center">Position</th>
                  <th class="th">Student Name (A to Z)</th>
                  <th class="th">Permanent Adm. No.</th>
                  <th class="th">Date of Birth</th>
                  <th class="th">Current Roll</th>
                  <th class="th">Proposed New Roll</th>
                  <th class="th text-right">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($sec['students'] as $st)
                  <tr class="tr {{ $st['is_changed'] ? 'bg-amber-50/20' : '' }}">
                    <td class="td text-center font-mono font-bold text-slate-500">
                      {{ $st['sequence'] }}
                    </td>
                    <td class="td">
                      <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[9px] text-slate-700 shrink-0 border border-slate-200 overflow-hidden">
                          @if($st['photo'])
                            <img src="{{ asset('storage/' . $st['photo']) }}" class="w-full h-full object-cover">
                          @else
                            {{ strtoupper(substr($st['first_name'] ?: 'S', 0, 1) . substr($st['last_name'] ?: '', 0, 1)) }}
                          @endif
                        </div>
                        <span class="font-bold text-slate-800 text-xs">{{ $st['full_name'] }}</span>
                      </div>
                    </td>
                    <td class="td">
                      <div class="flex items-center gap-1.5">
                        <span class="font-mono text-xs font-semibold text-slate-700">
                          {{ $st['admission_no'] }}
                        </span>
                        <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded font-medium">
                          Permanent
                        </span>
                      </div>
                    </td>
                    <td class="td text-slate-500">
                      {{ $st['dob'] }}
                    </td>
                    <td class="td font-mono text-slate-400">
                      {{ $st['current_roll'] }}
                    </td>
                    <td class="td">
                      <span class="font-mono font-bold text-xs {{ $st['is_changed'] ? 'text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-slate-700' }}">
                        {{ $st['proposed_roll'] }}
                      </span>
                    </td>
                    <td class="td text-right">
                      @if($st['is_changed'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                          Updates to #{{ $st['sequence'] }}
                        </span>
                      @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium text-slate-400">
                          Keeps #{{ $st['sequence'] }}
                        </span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endforeach

    </div>
  @elseif($initialPreview && $initialPreview['total_students'] === 0)
    <div class="card p-8 text-center text-slate-400 space-y-1.5">
      <p class="text-sm font-bold text-slate-700">No active students found in Class {{ $initialPreview['class_name'] }} for Academic Year {{ $initialPreview['academic_year'] }}.</p>
      <p class="text-xs text-slate-500">Please choose a different class (e.g. Class I or Class VI) or academic year above.</p>
    </div>
  @else
    <div class="card p-8 text-center text-slate-400 space-y-1.5">
      <p class="text-sm font-bold text-slate-700">Select a Class and Section above and click "Load Students".</p>
      <p class="text-xs text-slate-500">Students will be previewed in strict alphabetical order before saving.</p>
    </div>
  @endif

  {{-- ── Confirmation Modal ───────────────────────────────────────────── --}}
  @if($initialPreview && $initialPreview['total_students'] > 0)
  <div x-show="confirmModal"
       x-cloak
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-2xs"
       x-transition:enter="transition ease-out duration-150"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-100"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-5 sm:p-6 space-y-4"
         @click.away="confirmModal = false">

      <div>
        <h3 class="text-base font-bold text-slate-900">Confirm Alphabetical Roll Numbers</h3>
        <p class="text-xs text-slate-500 mt-0.5">
          Class {{ $initialPreview['class_name'] }} &bull; {{ $initialPreview['academic_year'] }}
        </p>
      </div>

      <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 space-y-2">
        <div class="flex items-center justify-between">
          <span>Total Students:</span>
          <strong class="font-bold text-slate-800">{{ $initialPreview['total_students'] }} students</strong>
        </div>
        <div class="flex items-center justify-between">
          <span>Re-ordered to A–Z:</span>
          <strong class="font-bold text-amber-700">{{ $initialPreview['total_changed'] }} students</strong>
        </div>
        <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
          <span>Permanent Admission Numbers:</span>
          <strong class="font-bold text-emerald-700">100% Protected (Never Modified)</strong>
        </div>
      </div>

      <form method="POST" action="{{ route('students.roll-numbers.generate') }}" class="space-y-3.5">
        @csrf
        <input type="hidden" name="class_id" value="{{ $initialPreview['class_id'] }}">
        <input type="hidden" name="section_id" value="{{ $initialPreview['section_id'] }}">
        <input type="hidden" name="academic_year_id" value="{{ $initialPreview['academic_year_id'] }}">
        <input type="hidden" name="prefix_style" value="{{ $initialPreview['prefix_style'] }}">
        <input type="hidden" name="start_seq" value="{{ $initialPreview['start_seq'] }}">
        <input type="hidden" name="confirm" value="1">

        <div class="flex items-start gap-2 pt-1">
          <input type="checkbox" id="confirm_agree" x-model="confirmAgreed" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
          <label for="confirm_agree" class="text-xs text-slate-600 select-none">
            I confirm updating the roll numbers for this section in strict alphabetical order.
          </label>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="confirmModal = false" class="btn btn-secondary btn-sm">
            Cancel
          </button>
          <button type="submit"
                  :disabled="!confirmAgreed"
                  class="btn btn-primary btn-sm disabled:opacity-50 disabled:cursor-not-allowed">
            Save &amp; Apply Roll Numbers
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

</div>
@endsection
