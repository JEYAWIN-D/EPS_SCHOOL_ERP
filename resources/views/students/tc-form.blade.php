@extends('layouts.app')

@section('title', 'Transfer Certificate (TC) — ' . $student->full_name)

@section('content')
<div class="max-w-5xl mx-auto space-y-5 pb-24">

  {{-- ── Page Header & Direct Action Bar ─────────────────────────────── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
    <div>
      <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
        <a href="{{ route('students.index') }}" class="hover:text-slate-700 transition">Students</a>
        <span>/</span>
        <a href="{{ route('students.show', $student->id) }}" class="hover:text-slate-700 transition font-medium text-slate-600">{{ strtoupper($student->full_name) }}</a>
        <span>/</span>
        <span class="text-slate-900 font-semibold">Transfer Certificate</span>
      </div>
      <div class="flex items-center gap-2.5">
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Issue Transfer Certificate</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
          Active Student
        </span>
      </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <a href="{{ route('students.show', $student->id) }}" class="btn btn-secondary btn-sm">
        Back to Profile
      </a>
      <button type="submit" form="tc_generate_form" class="btn btn-primary btn-sm inline-flex items-center gap-1.5 font-bold bg-[#8C2826] hover:bg-[#731E1C] border-[#8C2826] text-white shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Generate &amp; Print TC</span>
      </button>
    </div>
  </div>

  {{-- ── Student Information 4-Card Summary Grid ────────────────────── --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-2xs">
      <span class="text-[11px] font-medium text-slate-400 block">Student Name</span>
      <span class="text-xs font-bold text-slate-900 block truncate mt-0.5">{{ strtoupper($student->full_name) }}</span>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-2xs">
      <span class="text-[11px] font-medium text-slate-400 block">Admission Number</span>
      <span class="text-xs font-bold font-mono text-slate-900 block mt-0.5">{{ $student->admission_no ?? '—' }}</span>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-2xs">
      <span class="text-[11px] font-medium text-slate-400 block">Class &amp; Section</span>
      <span class="text-xs font-bold text-slate-900 block mt-0.5">{{ $enrollment?->class?->name ?? '—' }}@if($enrollment?->section) (Sec {{ $enrollment->section->name }})@endif</span>
    </div>
    <div class="bg-rose-50/40 rounded-xl border border-rose-200/80 p-3 shadow-2xs">
      <span class="text-[11px] font-medium text-rose-500 block">TC Serial Number</span>
      <span class="text-xs font-bold font-mono text-[#8C2826] block mt-0.5">{{ $defaults['tc_number'] }}</span>
    </div>
  </div>

  {{-- ── Main Comprehensive TC Form ──────────────────────────────────── --}}
  <form id="tc_generate_form" action="{{ route('students.tc', $student->id) }}" method="POST" target="_blank" class="space-y-5">
    @csrf

    {{-- ── 1. Certificate & Student Particulars ────────────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
      <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">1. Certificate &amp; Student Particulars</h2>
        <span class="text-[11px] text-slate-400 font-mono">Part 1 / 4</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Pupil Full Name (in Block Letters) <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="full_name" value="{{ old('full_name', $defaults['full_name']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs uppercase font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-slate-50/40">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Permanent Admission Number <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="admission_no" value="{{ old('admission_no', $defaults['admission_no']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-slate-50/40">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            TC Number <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="tc_number" value="{{ old('tc_number', $defaults['tc_number']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-rose-200 text-xs font-mono font-bold text-[#8C2826] focus:outline-none focus:ring-2 focus:ring-rose-100 focus:border-[#8C2826] transition bg-rose-50/20">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Date of Issue <span class="text-rose-500">*</span>
          </label>
          <input type="date" name="tc_date" value="{{ old('tc_date', $defaults['tc_date']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Date of Application
          </label>
          <input type="date" name="application_date" value="{{ old('application_date', $defaults['application_date']) }}"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            EMIS / PEN / STS Number
          </label>
          <input type="text" name="emis_no" value="{{ old('emis_no', $defaults['emis_no']) }}" placeholder="e.g. 23456788575755"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Aadhaar Card Number
          </label>
          <input type="text" name="aadhaar_no" value="{{ old('aadhaar_no', $defaults['aadhaar_no']) }}" placeholder="XXXX-XXXX-XXXX"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Nationality <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="nationality" value="{{ old('nationality', $defaults['nationality']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Place of Birth (District &amp; State)
          </label>
          <input type="text" name="place_of_birth" value="{{ old('place_of_birth', $defaults['place_of_birth']) }}" placeholder="e.g. Erode, Tamil Nadu"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>
      </div>
    </div>

    {{-- ── 2. Parents & Community Particulars ──────────────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
      <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">2. Parents &amp; Social Category</h2>
        <span class="text-[11px] text-slate-400 font-mono">Part 2 / 4</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Father's / Guardian's Name <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="father_name" value="{{ old('father_name', $defaults['father_name']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Mother's Name <span class="text-rose-500">*</span>
            @if(empty($defaults['mother_name']))
              <span class="text-[10px] text-amber-600 font-normal ml-1">(Required on TC)</span>
            @endif
          </label>
          <input type="text" name="mother_name" value="{{ old('mother_name', $defaults['mother_name']) }}" required
                 placeholder="Enter Mother's Name"
                 class="w-full h-9 px-3 rounded-lg border text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition {{ empty($defaults['mother_name']) ? 'border-amber-300 bg-amber-50/20' : 'border-slate-300' }}">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Social Category
          </label>
          <select name="category" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-white">
            <option value="General" @selected(strtoupper($defaults['category']) === 'GENERAL')>General</option>
            <option value="OBC" @selected(strtoupper($defaults['category']) === 'OBC')>OBC (Other Backward Classes)</option>
            <option value="SC" @selected(strtoupper($defaults['category']) === 'SC')>SC (Scheduled Caste)</option>
            <option value="ST" @selected(strtoupper($defaults['category']) === 'ST')>ST (Scheduled Tribe)</option>
            <option value="MBC" @selected(strtoupper($defaults['category']) === 'MBC')>MBC (Most Backward Class)</option>
            <option value="EWS" @selected(strtoupper($defaults['category']) === 'EWS')>EWS (Economically Weaker Section)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Religion
          </label>
          <input type="text" name="religion" value="{{ old('religion', $defaults['religion']) }}" placeholder="e.g. Christian, Hindu, Muslim"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Community / Caste
          </label>
          <input type="text" name="caste" value="{{ old('caste', $defaults['caste']) }}" placeholder="e.g. BC / Kongu Vellalar"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>
      </div>
    </div>

    {{-- ── 3. Birth Records & Admission History ────────────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
      <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">3. Birth Records &amp; Enrollment History</h2>
        <span class="text-[11px] text-slate-400 font-mono">Part 3 / 4</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Date of Birth (in figures) <span class="text-rose-500">*</span>
          </label>
          <input type="date" id="dob_picker" name="dob" value="{{ old('dob', $defaults['dob']) }}" required
                 onchange="generateDobWords(this.value)"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div class="sm:col-span-2">
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">
              Date of Birth (in words) <span class="text-rose-500">*</span>
            </label>
            <button type="button" onclick="generateDobWords(document.getElementById('dob_picker').value)" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 transition">
              &circlearrowright; Auto-convert
            </button>
          </div>
          <input type="text" id="dob_words_input" name="dob_in_words" value="{{ old('dob_in_words', $defaults['dob_in_words']) }}" required
                 placeholder="e.g. Fifteenth July Two Thousand Nine"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium italic text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-slate-50/40">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Date of First Admission in School <span class="text-rose-500">*</span>
          </label>
          <input type="date" name="admission_date" value="{{ old('admission_date', $defaults['admission_date']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Class in which First Admitted
          </label>
          <input type="text" name="admitted_class" value="{{ old('admitted_class', $defaults['admitted_class']) }}" placeholder="e.g. Class I or Class IX"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Class in which Pupil Last Studied <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="last_class" value="{{ old('last_class', $defaults['last_class']) }}" required
                 placeholder="e.g. Class IX - Section A"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>
      </div>
    </div>

    {{-- ── 4. Academics, Attendance, Leaving & Conduct ──────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
      <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">4. Academic Record, Attendance &amp; Leaving Particulars</h2>
        <span class="text-[11px] text-slate-400 font-mono">Part 4 / 4</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Annual Examination Result <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="exam_result" value="{{ old('exam_result', $defaults['exam_result']) }}" required
                 placeholder="e.g. Passed IX Annual Examination"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Whether Failed (in same class)
          </label>
          <select name="whether_failed" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-white">
            <option value="No" @selected($defaults['whether_failed'] === 'No')>No</option>
            <option value="Once" @selected($defaults['whether_failed'] === 'Once')>Once</option>
            <option value="Twice" @selected($defaults['whether_failed'] === 'Twice')>Twice</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Qualified for Promotion <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="qualified_promotion" value="{{ old('qualified_promotion', $defaults['qualified_promotion']) }}" required
                 placeholder="e.g. Yes, Qualified for promotion to next higher class"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-indigo-50/20">
        </div>

        {{-- Subjects Studied --}}
        <div class="sm:col-span-2 lg:col-span-3">
          <div class="flex flex-wrap items-center justify-between gap-1 mb-1">
            <label class="block text-xs font-semibold text-slate-700">
              Subjects Studied <span class="text-rose-500">*</span>
            </label>
            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
              <span class="text-slate-400">Presets:</span>
              <button type="button" onclick="setSubjects('1. English  2. Mathematics  3. Science  4. Social Science  5. Tamil / Hindi')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 transition font-medium text-slate-700">High School</button>
              <button type="button" onclick="setSubjects('1. English Core  2. Mathematics  3. Physics  4. Chemistry  5. Computer Science')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 transition font-medium text-slate-700">H.Sec Science</button>
              <button type="button" onclick="setSubjects('1. English Core  2. Accountancy  3. Business Studies  4. Economics  5. Applied Mathematics')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 transition font-medium text-slate-700">H.Sec Commerce</button>
              <button type="button" onclick="setSubjects('1. English  2. Mathematics  3. Environmental Studies  4. Social Science  5. Second Language')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 transition font-medium text-slate-700">Primary</button>
            </div>
          </div>
          <input type="text" id="subjects_input" name="subjects" value="{{ old('subjects', $defaults['subjects']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Month up to which Dues Paid <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="dues_cleared" value="{{ old('dues_cleared', $defaults['dues_cleared']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Total Working Days in Session
          </label>
          <input type="number" name="total_working_days" value="{{ old('total_working_days', $defaults['total_working_days']) }}" min="1"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Working Days Pupil Attended
          </label>
          <input type="number" name="days_present" value="{{ old('days_present', $defaults['days_present']) }}" min="0"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Fee Concession Availed
          </label>
          <input type="text" name="fee_concession" value="{{ old('fee_concession', $defaults['fee_concession']) }}"
                 placeholder="e.g. No"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            NCC Cadet / Scout / Guide
          </label>
          <input type="text" name="ncc_scout" value="{{ old('ncc_scout', $defaults['ncc_scout']) }}" placeholder="e.g. N/A"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            General Conduct &amp; Character <span class="text-rose-500">*</span>
          </label>
          <select name="conduct" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-white">
            <option value="Good" @selected($defaults['conduct'] === 'Good')>Good</option>
            <option value="Exemplary" @selected($defaults['conduct'] === 'Exemplary')>Exemplary</option>
            <option value="Very Good" @selected($defaults['conduct'] === 'Very Good')>Very Good</option>
            <option value="Satisfactory" @selected($defaults['conduct'] === 'Satisfactory')>Satisfactory</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Games &amp; Extra-curricular Activities
          </label>
          @php
            $valGames = old('games_activities', $defaults['games_activities'] ?? '');
            if (is_array($valGames)) {
              $valGames = implode(', ', array_map(fn($i) => ucwords(str_replace('_', ' ', trim((string)$i))), array_filter($valGames)));
            }
          @endphp
          <input type="text" name="games_activities" value="{{ $valGames }}"
                 placeholder="e.g. Western Dance, Band, Athletics"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Date of Leaving School <span class="text-rose-500">*</span>
          </label>
          <input type="date" name="leaving_date" value="{{ old('leaving_date', $defaults['leaving_date']) }}" required
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Reason for Leaving the School <span class="text-rose-500">*</span>
          </label>
          <select name="reason" id="reason_selector" onchange="toggleCustomReason(this.value)" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition bg-white">
            <option value="Parent's Request" @selected($defaults['reason'] === "Parent's Request")>Parent's Request</option>
            <option value="Higher Studies" @selected($defaults['reason'] === "Higher Studies")>Higher Studies</option>
            <option value="Transfer to another school" @selected($defaults['reason'] === "Transfer to another school")>Transfer to another school</option>
            <option value="Family relocation" @selected($defaults['reason'] === "Family relocation")>Family relocation</option>
            <option value="Completion of course" @selected($defaults['reason'] === "Completion of course")>Completion of course</option>
            <option value="Health reasons" @selected($defaults['reason'] === "Health reasons")>Health reasons</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div id="custom_reason_wrapper" class="sm:col-span-2 hidden">
          <label class="block text-xs font-semibold text-indigo-700 mb-1">Specify Custom Reason</label>
          <input type="text" id="custom_reason_input" name="custom_reason" placeholder="Type reason here..."
                 class="w-full h-9 px-3 rounded-lg border border-indigo-300 bg-indigo-50/20 text-xs font-medium text-slate-800">
        </div>

        <div class="sm:col-span-2 lg:col-span-3">
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Any Other Remarks
          </label>
          <input type="text" name="remarks" value="{{ old('remarks', $defaults['remarks']) }}"
                 class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
        </div>
      </div>
    </div>

    {{-- ── Bottom Action Strip ─────────────────────────────────────────── --}}
    <div class="p-4 rounded-xl bg-white border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
      <label class="flex items-center gap-2.5 text-xs text-slate-700 cursor-pointer select-none">
        <input type="checkbox" id="save_to_profile" name="save_to_profile" value="1" checked
               class="rounded border-slate-300 text-[#8C2826] focus:ring-rose-500 cursor-pointer">
        <span>Save updated particulars back to Student Profile in Database</span>
      </label>

      <div class="flex items-center gap-2 self-end sm:self-center">
        <a href="{{ route('students.show', $student->id) }}" class="btn btn-secondary btn-sm">
          Cancel
        </a>
        <button type="submit" class="btn btn-primary btn-sm px-4 inline-flex items-center gap-1.5 font-bold bg-[#8C2826] hover:bg-[#731E1C] border-[#8C2826] text-white shadow-xs">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          <span>Generate &amp; Print TC</span>
        </button>
      </div>
    </div>

  </form>

</div>

<script>
function toggleCustomReason(val) {
  const wrapper = document.getElementById('custom_reason_wrapper');
  const input = document.getElementById('custom_reason_input');
  if (val === 'Other') {
    wrapper.classList.remove('hidden');
    input.focus();
  } else {
    wrapper.classList.add('hidden');
    input.value = '';
  }
}

function setSubjects(val) {
  document.getElementById('subjects_input').value = val;
}

function generateDobWords(dateStr) {
  if (!dateStr) return;
  const parts = dateStr.split('-');
  if (parts.length !== 3) return;

  const year = parseInt(parts[0], 10);
  const month = parseInt(parts[1], 10);
  const day = parseInt(parts[2], 10);

  const days = [
    '', 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth',
    'Eleventh', 'Twelfth', 'Thirteenth', 'Fourteenth', 'Fifteenth', 'Sixteenth', 'Seventeenth', 'Eighteenth', 'Nineteenth', 'Twentieth',
    'Twenty-First', 'Twenty-Second', 'Twenty-Third', 'Twenty-Fourth', 'Twenty-Fifth', 'Twenty-Sixth', 'Twenty-Seventh', 'Twenty-Eighth', 'Twenty-Ninth', 'Thirtieth',
    'Thirty-First'
  ];

  const months = [
    '', 'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
  const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

  function numToWords(n) {
    if (n < 20) return ones[n];
    if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
    if (n < 1000) return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + numToWords(n % 100) : '');
    if (n < 1000000) return numToWords(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + numToWords(n % 1000) : '');
    return n.toString();
  }

  const dayWord = days[day] || day;
  const monthWord = months[month] || '';
  const yearWord = numToWords(year);

  const fullWords = (dayWord + ' ' + monthWord + ' ' + yearWord).trim();
  document.getElementById('dob_words_input').value = fullWords;
}
</script>
@endsection
