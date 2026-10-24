@extends('layouts.app')

@section('title', 'Student ID Cards — Standard & Section Studio')

@section('content')
<div class="space-y-6" x-data="{ 
  viewStyle: '{{ $style ?? 'landscape' }}', 
  activeSide: '{{ $side ?? 'both' }}',
  flipState: {}
}">

  {{-- ── Top Header & Main Action Bar ──────────────────────────── --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-rose-900 text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
        🪪
      </div>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="page-title text-xl font-black text-slate-900">Student ID Card Studio</h1>
          @if($targetClass)
            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-900 border border-rose-300">
              Class {{ $targetClass->name }} {{ $targetSection ? '(' . $targetSection->name . ')' : '' }}
            </span>
          @else
            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-800 border border-slate-200">
              All Standards
            </span>
          @endif
          <span class="badge-blue text-[10px] font-bold">CR80 Compliant</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Standard &amp; Section-wise preview, Landscape/Portrait switch, and bulk Front &amp; Back PDF generation for {{ $currentYear?->name ?? '2025-2026' }}
        </p>
      </div>
    </div>

    {{-- Download & Template Buttons --}}
    <div class="flex items-center gap-2.5 flex-wrap">
      <a href="{{ route('students.id-card-template') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
        </svg>
        Template Settings
      </a>

      {{-- Dynamic PDF Download Button linked to selected style & side --}}
      <a :href="'{{ route('students.id-cards.pdf') }}?class_id={{ $classFilter }}&section_id={{ $sectionFilter }}&wing={{ $wingFilter }}&search={{ urlencode($search ?? '') }}&style=' + viewStyle + '&side=' + activeSide"
         target="_blank"
         class="btn btn-primary btn-sm flex items-center gap-2 text-xs font-black shadow-md bg-rose-900 hover:bg-rose-800 text-white px-4 py-2 rounded-xl transition cursor-pointer">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span>Download Entire Class ID Cards (PDF)</span>
        <span class="bg-white/20 text-white px-1.5 py-0.2 rounded-full text-[10px] font-mono">
          {{ $filteredEnrollments->count() }}
        </span>
      </a>
    </div>
  </div>

  {{-- ── Filter Controls: Standard, Section, Search & Switchers ───── --}}
  <div class="card p-4 space-y-4 bg-white border border-slate-200">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

      {{-- Class / Section / Search Form --}}
      <form method="GET" action="{{ route('students.id-cards') }}" class="flex flex-wrap items-center gap-2.5">
        <input type="hidden" name="wing" value="{{ $wingFilter }}">
        <input type="hidden" name="style" :value="viewStyle">
        <input type="hidden" name="side" :value="activeSide">

        {{-- Standard / Class Selector --}}
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Standard / Class</label>
          <select name="class_id" onchange="this.form.submit()" class="select-sm text-xs font-semibold bg-slate-50 border-slate-200 rounded-xl">
            <option value="">All Standards (Classes)</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected($classFilter == $c->id)>Class {{ $c->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Section Selector --}}
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Section</label>
          <select name="section_id" onchange="this.form.submit()" class="select-sm text-xs font-semibold bg-slate-50 border-slate-200 rounded-xl">
            <option value="">All Sections</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}" @selected($sectionFilter == $s->id)>Section {{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Search Input --}}
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Student</label>
          <div class="relative">
            <input type="text" name="search" value="{{ $search }}" placeholder="Name, Roll No, Adm No..." 
                   class="input-sm text-xs bg-slate-50 border-slate-200 rounded-xl pl-8 pr-3 py-1.5 w-48 sm:w-56">
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
        </div>

        <div class="pt-4 flex items-center gap-1.5">
          <button type="submit" class="btn btn-secondary btn-sm text-xs font-bold px-3 py-1.5">Filter</button>
          @if($classFilter || $sectionFilter || $search || ($wingFilter && $wingFilter !== 'all'))
            <a href="{{ route('students.id-cards') }}" class="btn btn-ghost btn-xs text-rose-600 font-bold">Clear</a>
          @endif
        </div>
      </form>

      {{-- ── Style & Side Switchers ── --}}
      <div class="flex flex-wrap items-center gap-3">
        {{-- Style Switcher: Landscape vs Portrait --}}
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">ID Card Style</label>
          <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-xs font-bold shadow-2xs">
            <button type="button" @click="viewStyle = 'landscape'"
                    :class="viewStyle === 'landscape' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-1 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
              </svg>
              <span>Landscape</span>
            </button>
            <button type="button" @click="viewStyle = 'portrait'"
                    :class="viewStyle === 'portrait' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-1 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="5" y="2" width="14" height="20" rx="2"/>
              </svg>
              <span>Portrait</span>
            </button>
          </div>
        </div>

        {{-- Side Switcher: Both Sides vs Front vs Back --}}
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Preview &amp; PDF Side</label>
          <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-xs font-bold shadow-2xs">
            <button type="button" @click="activeSide = 'both'"
                    :class="activeSide === 'both' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-2.5 py-1 rounded-lg transition cursor-pointer">
              Both Sides
            </button>
            <button type="button" @click="activeSide = 'front'"
                    :class="activeSide === 'front' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-2.5 py-1 rounded-lg transition cursor-pointer">
              Front Only
            </button>
            <button type="button" @click="activeSide = 'back'"
                    :class="activeSide === 'back' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-2.5 py-1 rounded-lg transition cursor-pointer">
              Back Only
            </button>
          </div>
        </div>
      </div>

    </div>

    {{-- Wing Quick Tabs --}}
    <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-1.5">
      <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1">Wings:</span>
      <a href="{{ route('students.id-cards', ['wing' => 'all', 'class_id' => $classFilter, 'section_id' => $sectionFilter]) }}"
         class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $wingFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
        All Wings ({{ $wingCounts['all'] ?? 0 }})
      </a>
      <a href="{{ route('students.id-cards', ['wing' => 'kg', 'class_id' => $classFilter, 'section_id' => $sectionFilter]) }}"
         class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $wingFilter === 'kg' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
        KG ({{ $wingCounts['kg'] ?? 0 }})
      </a>
      <a href="{{ route('students.id-cards', ['wing' => 'primary', 'class_id' => $classFilter, 'section_id' => $sectionFilter]) }}"
         class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $wingFilter === 'primary' ? 'bg-emerald-700 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
        Primary ({{ $wingCounts['primary'] ?? 0 }})
      </a>
      <a href="{{ route('students.id-cards', ['wing' => 'middle', 'class_id' => $classFilter, 'section_id' => $sectionFilter]) }}"
         class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $wingFilter === 'middle' ? 'bg-cyan-700 text-white' : 'bg-cyan-50 text-cyan-800 hover:bg-cyan-100' }}">
        Middle ({{ $wingCounts['middle'] ?? 0 }})
      </a>
      <a href="{{ route('students.id-cards', ['wing' => 'higher_secondary', 'class_id' => $classFilter, 'section_id' => $sectionFilter]) }}"
         class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $wingFilter === 'higher_secondary' ? 'bg-indigo-700 text-white' : 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100' }}">
        Higher Sec ({{ $wingCounts['higher_secondary'] ?? 0 }})
      </a>
      <a href="{{ route('students.id-cards', ['wing' => 'senior_secondary', 'class_id' => $classFilter, 'section_id' => $sectionFilter]) }}"
         class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $wingFilter === 'senior_secondary' ? 'bg-purple-700 text-white' : 'bg-purple-50 text-purple-800 hover:bg-purple-100' }}">
        Senior Sec ({{ $wingCounts['senior_secondary'] ?? 0 }})
      </a>
    </div>
  </div>

  {{-- ── Student ID Cards Interactive Display ─────────────────── --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-slate-800 text-sm">
        Displaying {{ $filteredEnrollments->count() }} Student ID Cards
        @if($targetClass)
          <span class="text-rose-900">&bull; Standard {{ $targetClass->name }} {{ $targetSection ? 'Section ' . $targetSection->name : '' }}</span>
        @endif
      </h3>
      <div class="flex items-center gap-3 text-xs text-slate-500">
        <span class="flex items-center gap-1">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
          Front Side
        </span>
        <span class="flex items-center gap-1">
          <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>
          Back Side
        </span>
      </div>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
      @forelse($filteredEnrollments as $enrollment)
      @php
        $s = $enrollment->student;
        $cls = $enrollment->class;
        $sec = $enrollment->section;
        $parentPhone = $s->father_mobile ?: ($s->mother_mobile ?: ($s->guardian_mobile ?: ($s->mobile ?: '9876543210')));
        $fullAddress = $s->residential_address ?: ($s->permanent_address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051'));
      @endphp
      @if($s)
      <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80 hover:shadow-md transition-all flex flex-col justify-between">

        {{-- ── LANDSCAPE VIEW ───────────────────────────────── --}}
        <template x-if="viewStyle === 'landscape'">
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 py-2">
            
            {{-- FRONT SIDE LANDSCAPE (350x215) --}}
            <div x-show="activeSide === 'both' || activeSide === 'front'"
                 class="relative bg-white rounded-xl shadow-sm border border-slate-300 overflow-hidden flex flex-col justify-between shrink-0"
                 style="width: 320px; height: 195px; font-family: 'Segoe UI', Tahoma, sans-serif;">
              
              {{-- Top Header --}}
              <div class="h-11 px-3 bg-[#8C2826] text-white flex items-center justify-between border-b-2 border-amber-500">
                <div class="flex items-center gap-2">
                  <img src="{{ asset('images/school-logo.png') }}" class="w-7 h-7 rounded-sm object-contain" alt="Logo">
                  <div>
                    <h4 class="text-[9px] font-black uppercase tracking-wider text-white leading-tight">
                      {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
                    </h4>
                    <p class="text-[7px] text-amber-300 leading-tight">CBSE SENIOR SECONDARY &bull; ERODE</p>
                  </div>
                </div>
                <span class="text-[7px] font-black uppercase px-1.5 py-0.5 rounded bg-white/20 text-white">
                  STUDENT ID
                </span>
              </div>

              {{-- Body --}}
              <div class="px-3 py-1.5 flex items-center gap-3 flex-1">
                {{-- Photo --}}
                <div class="w-16 h-20 rounded-md border-2 border-rose-900 overflow-hidden bg-slate-100 flex items-center justify-center shrink-0 shadow-xs">
                  @if($s->photo && file_exists(public_path('storage/' . $s->photo)))
                    <img src="{{ asset('storage/' . $s->photo) }}" class="w-full h-full object-cover" alt="">
                  @else
                    <span class="text-lg font-black text-rose-900">
                      {{ strtoupper(substr($s->first_name ?? 'S', 0, 1) . substr($s->last_name ?? 'T', 0, 1)) }}
                    </span>
                  @endif
                </div>

                {{-- Info --}}
                <div class="min-w-0 flex-1 space-y-0.5">
                  <h4 class="text-xs font-black text-slate-900 uppercase truncate leading-tight">
                    {{ $s->full_name }}
                  </h4>
                  <p class="text-[10px] font-extrabold text-rose-900">
                    Class: {{ $cls?->name ?? '—' }} {{ $sec?->name ? ' - ' . $sec->name : '' }}
                  </p>

                  <div class="bg-slate-50 border border-slate-200 rounded p-1 text-[8px] space-y-0.5 font-semibold text-slate-700">
                    <div class="flex justify-between">
                      <span class="text-slate-400">Roll No:</span>
                      <span class="font-bold text-slate-900">{{ $s->roll_number ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">Adm No:</span>
                      <span class="font-bold text-slate-900">{{ $s->admission_no ?: ($s->admission_number ?: 'EPS/' . $s->id) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">DOB:</span>
                      <span class="font-bold text-slate-900">{{ $s->dob ? $s->dob->format('d/m/Y') : '—' }}</span>
                    </div>
                  </div>

                  {{-- Principal Sign --}}
                  <div class="flex items-center justify-between pt-0.5 text-[7px]">
                    <span class="text-slate-400 font-bold">Session: {{ $currentYear?->name ?? '2025-26' }}</span>
                    <div class="text-right">
                      <img src="{{ asset('images/principal-signature.svg') }}" class="h-4 inline-block" alt="Sign">
                      <div class="text-[6px] text-slate-500 font-bold leading-none">Principal Sign</div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Footer --}}
              <div class="h-5 px-3 bg-slate-900 text-white flex items-center justify-between text-[7px] font-bold">
                <span class="text-slate-400">{{ $school->website ?? 'www.erodepublicschool.edu.in' }}</span>
                <span class="text-amber-400">FRONT SIDE</span>
              </div>
            </div>

            {{-- BACK SIDE LANDSCAPE (320x195) --}}
            <div x-show="activeSide === 'both' || activeSide === 'back'"
                 class="relative bg-white rounded-xl shadow-sm border border-slate-300 overflow-hidden flex flex-col justify-between shrink-0"
                 style="width: 320px; height: 195px; font-family: 'Segoe UI', Tahoma, sans-serif;">
              
              {{-- Header --}}
              <div class="h-6 px-3 bg-slate-900 text-white flex items-center justify-between border-b-2 border-rose-900 text-[8px] font-bold">
                <span>IDENTITY &amp; EMERGENCY VERIFICATION</span>
                <span class="text-amber-400">BACK SIDE</span>
              </div>

              {{-- Body --}}
              <div class="p-2.5 flex gap-2.5 flex-1">
                <div class="flex-1 space-y-1.5">
                  <div class="bg-slate-50 border border-slate-200 rounded p-1.5 text-[8px]">
                    <span class="text-[7px] font-bold uppercase text-slate-400 block">RESIDENTIAL ADDRESS</span>
                    <p class="font-semibold text-slate-800 leading-tight truncate">
                      {{ Str::limit($fullAddress, 65) }}
                    </p>
                  </div>

                  <div class="grid grid-cols-2 gap-1.5">
                    <div class="bg-slate-50 border border-slate-200 rounded p-1 text-[8px]">
                      <span class="text-[7px] font-bold uppercase text-slate-400 block">PARENT PHONE</span>
                      <span class="font-mono font-bold text-slate-900">+91 {{ $parentPhone }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded p-1 text-[8px]">
                      <span class="text-[7px] font-bold uppercase text-slate-400 block">BLOOD GROUP</span>
                      <span class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-bold text-[8px] inline-block">
                        {{ $s->blood_group ?: 'O+' }}
                      </span>
                    </div>
                  </div>

                  <div class="bg-slate-100 p-1 rounded text-[7px] text-slate-600 leading-tight border-l-2 border-rose-900">
                    If found, return to Erode Public School. Helpline: +91 {{ $school->phone ?? '9876543210' }}
                  </div>
                </div>

                {{-- QR Code --}}
                <div class="w-16 flex flex-col items-center justify-center border-l border-slate-200 pl-2">
                  <div class="w-14 h-14 bg-white border border-slate-300 p-0.5 rounded flex items-center justify-center">
                    @if(!empty($s->_qrCodeSvg))
                      {!! $s->_qrCodeSvg !!}
                    @else
                      <span class="text-[6px] text-slate-400 font-mono">[QR]</span>
                    @endif
                  </div>
                  <span class="text-[6px] font-bold text-slate-500 mt-1">SCAN VERIFY</span>
                </div>
              </div>

              {{-- Footer --}}
              <div class="h-5 px-3 bg-slate-900 text-white flex items-center justify-between text-[7px] font-bold">
                <span class="text-slate-400">Helpline: +91 {{ $school->phone ?? '9876543210' }}</span>
                <span class="text-amber-400">VALID: {{ $currentYear?->name ?? '2025-2026' }}</span>
              </div>
            </div>

          </div>
        </template>

        {{-- ── PORTRAIT VIEW ────────────────────────────────── --}}
        <template x-if="viewStyle === 'portrait'">
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 py-2">
            
            {{-- FRONT SIDE PORTRAIT (200x310) --}}
            <div x-show="activeSide === 'both' || activeSide === 'front'"
                 class="relative bg-white rounded-xl shadow-sm border border-slate-300 overflow-hidden flex flex-col justify-between shrink-0 text-center"
                 style="width: 200px; height: 310px; font-family: 'Segoe UI', Tahoma, sans-serif;">
              
              {{-- Header --}}
              <div class="p-2 bg-[#8C2826] text-white border-b-2 border-amber-500">
                <img src="{{ asset('images/school-logo.png') }}" class="w-6 h-6 mx-auto rounded-sm object-contain mb-0.5" alt="Logo">
                <h4 class="text-[8px] font-black uppercase tracking-wider text-white leading-tight">
                  {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
                </h4>
                <p class="text-[6.5px] text-amber-300 leading-tight">CBSE AFFILIATED &bull; ERODE</p>
              </div>

              {{-- Body --}}
              <div class="p-2.5 flex-1 flex flex-col items-center justify-between">
                {{-- Photo --}}
                <div class="w-18 h-22 rounded-md border-2 border-rose-900 overflow-hidden bg-slate-100 flex items-center justify-center shadow-xs">
                  @if($s->photo && file_exists(public_path('storage/' . $s->photo)))
                    <img src="{{ asset('storage/' . $s->photo) }}" class="w-full h-full object-cover" alt="">
                  @else
                    <span class="text-xl font-black text-rose-900">
                      {{ strtoupper(substr($s->first_name ?? 'S', 0, 1) . substr($s->last_name ?? 'T', 0, 1)) }}
                    </span>
                  @endif
                </div>

                {{-- Name & Details --}}
                <div class="w-full space-y-1">
                  <h4 class="text-xs font-black text-slate-900 uppercase truncate">
                    {{ $s->full_name }}
                  </h4>
                  <p class="text-[10px] font-bold text-rose-900">
                    Class: {{ $cls?->name ?? '—' }} {{ $sec?->name ? ' - ' . $sec->name : '' }}
                  </p>

                  <div class="bg-slate-50 border border-slate-200 rounded p-1 text-[8px] space-y-0.5 font-semibold text-slate-700 text-left">
                    <div class="flex justify-between">
                      <span class="text-slate-400">Roll No:</span>
                      <span class="font-bold text-slate-900">{{ $s->roll_number ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">Adm No:</span>
                      <span class="font-bold text-slate-900">{{ $s->admission_no ?: ($s->admission_number ?: 'EPS/' . $s->id) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">DOB:</span>
                      <span class="font-bold text-slate-900">{{ $s->dob ? $s->dob->format('d/m/Y') : '—' }}</span>
                    </div>
                  </div>
                </div>

                {{-- Signature --}}
                <div class="w-full pt-1 border-t border-slate-100 flex flex-col items-center">
                  <img src="{{ asset('images/principal-signature.svg') }}" class="h-4 inline-block" alt="Sign">
                  <span class="text-[6.5px] font-bold text-slate-600">Principal Signature</span>
                </div>
              </div>

              {{-- Footer --}}
              <div class="h-5 px-2 bg-slate-900 text-white flex items-center justify-between text-[7px] font-bold">
                <span class="text-slate-400">{{ $currentYear?->name ?? '2025-26' }}</span>
                <span class="text-amber-400">FRONT</span>
              </div>
            </div>

            {{-- BACK SIDE PORTRAIT (200x310) --}}
            <div x-show="activeSide === 'both' || activeSide === 'back'"
                 class="relative bg-white rounded-xl shadow-sm border border-slate-300 overflow-hidden flex flex-col justify-between shrink-0 text-left"
                 style="width: 200px; height: 310px; font-family: 'Segoe UI', Tahoma, sans-serif;">
              
              {{-- Header --}}
              <div class="p-1.5 bg-slate-900 text-white text-center border-b-2 border-rose-900">
                <h4 class="text-[8px] font-black uppercase tracking-wider text-white">ERODE PUBLIC SCHOOL</h4>
                <p class="text-[6.5px] text-amber-300">STUDENT IDENTITY &amp; EMERGENCY</p>
              </div>

              {{-- Body --}}
              <div class="p-2.5 flex-1 flex flex-col justify-between space-y-1.5">
                <div class="bg-slate-50 border border-slate-200 rounded p-1.5 text-[8px]">
                  <span class="text-[7px] font-bold uppercase text-slate-400 block">RESIDENTIAL ADDRESS</span>
                  <p class="font-semibold text-slate-800 leading-snug">
                    {{ Str::limit($fullAddress, 70) }}
                  </p>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded p-1.5 text-[8px]">
                  <span class="text-[7px] font-bold uppercase text-slate-400 block">PARENT PHONE NUMBER</span>
                  <span class="font-mono font-bold text-slate-900">+91 {{ $parentPhone }}</span>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded p-1.5 text-[8px] flex items-center justify-between">
                  <span class="text-[7px] font-bold uppercase text-slate-400">BLOOD GROUP</span>
                  <span class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-bold text-[8px]">
                    {{ $s->blood_group ?: 'O+' }}
                  </span>
                </div>

                {{-- QR Code --}}
                <div class="text-center py-0.5">
                  <div class="w-14 h-14 mx-auto bg-white border border-slate-300 p-0.5 rounded flex items-center justify-center">
                    @if(!empty($s->_qrCodeSvg))
                      {!! $s->_qrCodeSvg !!}
                    @else
                      <span class="text-[6px] text-slate-400 font-mono">[QR CODE]</span>
                    @endif
                  </div>
                  <span class="text-[6px] font-bold text-slate-500 block mt-0.5">SCAN TO VERIFY</span>
                </div>

                <div class="bg-slate-100 p-1 rounded text-[6.5px] text-slate-600 leading-tight border-l-2 border-rose-900">
                  If found, return to school office. Helpline: +91 {{ $school->phone ?? '9876543210' }}
                </div>
              </div>

              {{-- Footer --}}
              <div class="h-5 px-2 bg-slate-900 text-white flex items-center justify-between text-[7px] font-bold">
                <span class="text-slate-400">VALID: {{ $currentYear?->name ?? '2025-2026' }}</span>
                <span class="text-amber-400">BACK</span>
              </div>
            </div>

          </div>
        </template>

        {{-- Card Action Bar --}}
        <div class="mt-3 pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs">
          <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700">Class {{ $cls?->name ?? '—' }} {{ $sec?->name ? '(' . $sec->name . ')' : '' }}</span>
            <span class="text-slate-300">&bull;</span>
            <span class="font-mono text-slate-500">Roll: {{ $s->roll_number ?? '—' }}</span>
          </div>

          <div class="flex items-center gap-2">
            <a :href="'{{ route('students.id-cards.pdf') }}?student_id={{ $s->id }}&style=' + viewStyle + '&side=' + activeSide"
               target="_blank"
               class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-900 font-bold text-[11px] border border-rose-200 transition">
              PDF
            </a>
            <a href="{{ route('students.id-card.single', $s->id) }}"
               class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
              Single Studio &rarr;
            </a>
          </div>
        </div>

      </div>
      @endif
      @empty
      <div class="col-span-full text-center py-16 bg-white rounded-2xl border border-dashed border-slate-200">
        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-3xl mb-3">
          🪪
        </div>
        <p class="font-bold text-slate-700 text-base">No Student ID Cards Found</p>
        <p class="text-xs text-slate-400 mt-1">Try selecting another class or section filter from above.</p>
      </div>
      @endforelse
    </div>
  </div>

</div>
@endsection
