@extends('layouts.app')

@section('title', 'Student ID Card — ' . $student->full_name)

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" x-data="{ 
  viewStyle: '{{ $style ?? 'landscape' }}', 
  activeSide: '{{ $side ?? 'both' }}' 
}">

  {{-- ── Top Navigation & Action Bar ────────────────────────── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs print:hidden">
    <div class="flex items-center gap-3">
      <a href="{{ route('students.id-cards') }}" class="btn-icon w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="Back to All Student ID Cards">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="page-title text-xl font-black text-slate-900">Student ID Card Studio</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-rose-100 text-rose-900 border border-rose-300">
            Class {{ $classModel?->name ?? '—' }} {{ $enrollment?->section?->name ? '(' . $enrollment->section->name . ')' : '' }}
          </span>
          <span class="badge-blue text-[10px] font-bold">CR80 Format</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          <span class="font-bold text-slate-800">{{ $student->full_name }}</span> &bull; Adm No: {{ $student->admission_no ?: ($student->admission_number ?: 'EPS/' . $student->id) }} &bull; Roll No: {{ $student->roll_number ?? '—' }}
        </p>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
      {{-- Style Switcher --}}
      <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-xs font-bold shadow-2xs">
        <button type="button" @click="viewStyle = 'landscape'"
                :class="viewStyle === 'landscape' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
          </svg>
          <span>Landscape</span>
        </button>
        <button type="button" @click="viewStyle = 'portrait'"
                :class="viewStyle === 'portrait' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="5" y="2" width="14" height="20" rx="2"/>
          </svg>
          <span>Portrait</span>
        </button>
      </div>

      {{-- Side view toggles --}}
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

      {{-- PDF Download Button --}}
      <a :href="'{{ route('students.id-cards.pdf') }}?student_id={{ $student->id }}&style=' + viewStyle + '&side=' + activeSide"
         target="_blank"
         class="btn btn-primary btn-sm flex items-center gap-2 text-xs font-bold px-4 py-2 shadow-sm bg-rose-900 hover:bg-rose-800 text-white cursor-pointer"
         title="Download Single Student ID Card PDF">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span>Download PDF</span>
      </a>

      {{-- Print Button --}}
      <button onclick="window.print()" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold px-3 py-2 cursor-pointer" title="Print student ID card">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        <span>Print</span>
      </button>
    </div>
  </div>

  @php
    $parentPhone = $student->father_mobile ?: ($student->mother_mobile ?: ($student->guardian_mobile ?: ($student->mobile ?: '9876543210')));
    $fullAddress = $student->residential_address ?: ($student->permanent_address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051'));
  @endphp

  {{-- ── Single Student ID Card Previews ─────────────────────── --}}
  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <h2 class="text-base font-black text-slate-900">
        Live Card Preview: <span class="text-rose-900">{{ $student->full_name }}</span>
      </h2>
      <span class="text-xs font-bold text-slate-400">CR80 Specification (85.6mm &times; 54mm)</span>
    </div>

    {{-- ── LANDSCAPE MODE ─────────────────────────────────────── --}}
    <template x-if="viewStyle === 'landscape'">
      <div class="flex flex-col lg:flex-row items-center justify-center gap-8 py-4">

        {{-- FRONT SIDE (Landscape) --}}
        <div x-show="activeSide === 'both' || activeSide === 'front'" class="print-card-wrap flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between"
               style="width: 420px; height: 265px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Top Header Banner --}}
            <div class="h-14 px-4 bg-[#8C2826] text-white flex items-center justify-between border-b-2 border-amber-500">
              <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/school-logo.png') }}" class="w-8 h-8 rounded object-contain bg-white/10 p-0.5" alt="Logo">
                <div>
                  <h4 class="text-[11px] font-black uppercase tracking-wider text-white leading-tight">
                    {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
                  </h4>
                  <p class="text-[8.5px] text-amber-300 leading-tight">CBSE SENIOR SECONDARY &bull; ERODE</p>
                </div>
              </div>
              <span class="text-[8.5px] font-black uppercase px-2 py-0.5 rounded bg-white/20 text-white">
                STUDENT ID
              </span>
            </div>

            {{-- Card Body --}}
            <div class="px-4 py-2.5 flex items-center gap-4 flex-1">
              {{-- Student Photo --}}
              <div class="w-22 h-26 rounded-xl border-2 border-rose-900 overflow-hidden bg-slate-100 flex items-center justify-center shrink-0 shadow-sm">
                @if($student->photo && file_exists(public_path('storage/' . $student->photo)))
                  <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->full_name }}" class="w-full h-full object-cover">
                @else
                  <span class="text-2xl font-black text-rose-900 w-full h-full flex items-center justify-center bg-rose-50">
                    {{ strtoupper(substr($student->first_name ?? 'S', 0, 1) . substr($student->last_name ?? 'T', 0, 1)) }}
                  </span>
                @endif
              </div>

              {{-- Student Info --}}
              <div class="min-w-0 flex-1 space-y-1">
                <h3 class="text-sm font-black text-slate-900 uppercase truncate leading-tight">
                  {{ $student->full_name }}
                </h3>
                <p class="text-xs font-bold text-rose-900 truncate">
                  Class: {{ $classModel?->name ?? '—' }} {{ $enrollment?->section?->name ? ' - ' . $enrollment->section->name : '' }}
                </p>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px] space-y-0.5 font-semibold text-slate-700">
                  <div class="flex justify-between">
                    <span class="text-slate-400">Roll No:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $student->roll_number ?? '—' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">Adm No:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $student->admission_no ?: ($student->admission_number ?: 'EPS/' . $student->id) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">DOB:</span>
                    <span class="font-bold text-slate-900">{{ $student->dob ? $student->dob->format('d/m/Y') : '—' }}</span>
                  </div>
                </div>

                {{-- Principal Signature --}}
                <div class="flex items-center justify-between pt-1 text-[8.5px]">
                  <span class="text-slate-400 font-bold">Session: {{ $currentYear?->name ?? '2025-2026' }}</span>
                  <div class="text-right">
                    <img src="{{ asset('images/principal-signature.svg') }}" class="h-5 inline-block" alt="Sign">
                    <div class="text-[7px] text-slate-500 font-bold leading-none">Principal Signature</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-4 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">{{ $school->website ?? 'www.erodepublicschool.edu.in' }}</span>
              <span class="text-amber-400">FRONT SIDE</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Front Side Preview</span>
        </div>

        {{-- BACK SIDE (Landscape) --}}
        <div x-show="activeSide === 'both' || activeSide === 'back'" class="print-card-wrap flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between"
               style="width: 420px; height: 265px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Header --}}
            <div class="h-8 px-4 bg-slate-900 text-white flex items-center justify-between border-b-2 border-rose-900 text-[10px] font-bold">
              <span>{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }} — IDENTITY &amp; EMERGENCY</span>
              <span class="text-amber-400">BACK SIDE</span>
            </div>

            {{-- Body --}}
            <div class="p-3.5 flex gap-3.5 flex-1">
              <div class="flex-1 space-y-2">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px]">
                  <span class="text-[8px] font-bold uppercase text-slate-400 block">RESIDENTIAL ADDRESS</span>
                  <p class="font-semibold text-slate-800 leading-snug">
                    {{ Str::limit($fullAddress, 80) }}
                  </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px]">
                    <span class="text-[8px] font-bold uppercase text-slate-400 block">PARENT PHONE</span>
                    <span class="font-mono font-bold text-slate-900">+91 {{ $parentPhone }}</span>
                  </div>
                  <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px]">
                    <span class="text-[8px] font-bold uppercase text-slate-400 block">BLOOD GROUP</span>
                    <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-bold text-[9px] inline-block">
                      {{ $student->blood_group ?: 'O+' }}
                    </span>
                  </div>
                </div>

                <div class="bg-slate-100 p-2 rounded-lg text-[9px] text-slate-600 leading-tight border-l-2 border-rose-900">
                  If found, please return to <strong>Erode Public School</strong>. Helpline: +91 {{ $school->phone ?? '9876543210' }}
                </div>
              </div>

              {{-- QR Code --}}
              <div class="w-24 flex flex-col items-center justify-center border-l border-slate-200 pl-3">
                <div class="w-20 h-20 bg-white border border-slate-300 p-1 rounded-lg flex items-center justify-center">
                  @if(!empty($qrCodeSvg))
                    {!! $qrCodeSvg !!}
                  @else
                    <span class="text-[8px] text-slate-400 font-mono">[QR CODE]</span>
                  @endif
                </div>
                <span class="text-[8px] font-bold text-slate-500 mt-1.5">SCAN TO VERIFY</span>
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-4 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">Emergency Helpline: +91 {{ $school->phone ?? '9876543210' }}</span>
              <span class="text-amber-400">VALID: {{ $currentYear?->name ?? '2025-2026' }}</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Back Side Preview</span>
        </div>

      </div>
    </template>

    {{-- ── PORTRAIT MODE ──────────────────────────────────────── --}}
    <template x-if="viewStyle === 'portrait'">
      <div class="flex flex-col lg:flex-row items-center justify-center gap-8 py-4">

        {{-- Front Portrait --}}
        <div x-show="activeSide === 'both' || activeSide === 'front'" class="print-card-wrap flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between text-center"
               style="width: 240px; height: 380px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Header --}}
            <div class="p-3 bg-[#8C2826] text-white border-b-2 border-amber-500">
              <img src="{{ asset('images/school-logo.png') }}" class="w-7 h-7 mx-auto rounded object-contain mb-1 bg-white/10 p-0.5" alt="Logo">
              <h4 class="text-[9.5px] font-black uppercase tracking-wider text-white leading-tight">
                {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
              </h4>
              <p class="text-[7.5px] text-amber-300 leading-tight">CBSE AFFILIATED &bull; ERODE</p>
            </div>

            {{-- Body --}}
            <div class="p-3 flex-1 flex flex-col items-center justify-between">
              {{-- Photo --}}
              <div class="w-22 h-26 rounded-xl border-2 border-rose-900 overflow-hidden bg-slate-100 flex items-center justify-center shadow-xs">
                @if($student->photo && file_exists(public_path('storage/' . $student->photo)))
                  <img src="{{ asset('storage/' . $student->photo) }}" class="w-full h-full object-cover" alt="">
                @else
                  <span class="text-2xl font-black text-rose-900 w-full h-full flex items-center justify-center bg-rose-50">
                    {{ strtoupper(substr($student->first_name ?? 'S', 0, 1) . substr($student->last_name ?? 'T', 0, 1)) }}
                  </span>
                @endif
              </div>

              {{-- Info --}}
              <div class="w-full space-y-1">
                <h4 class="text-xs font-black text-slate-900 uppercase truncate">
                  {{ $student->full_name }}
                </h4>
                <p class="text-[10px] font-bold text-rose-900">
                  Class: {{ $classModel?->name ?? '—' }} {{ $enrollment?->section?->name ? ' - ' . $enrollment->section->name : '' }}
                </p>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-[9px] space-y-0.5 font-semibold text-slate-700 text-left">
                  <div class="flex justify-between">
                    <span class="text-slate-400">Roll No:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $student->roll_number ?? '—' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">Adm No:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $student->admission_no ?: ($student->admission_number ?: 'EPS/' . $student->id) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">DOB:</span>
                    <span class="font-bold text-slate-900">{{ $student->dob ? $student->dob->format('d/m/Y') : '—' }}</span>
                  </div>
                </div>
              </div>

              {{-- Signature --}}
              <div class="w-full pt-1.5 border-t border-slate-100 flex flex-col items-center">
                <img src="{{ asset('images/principal-signature.svg') }}" class="h-5 inline-block" alt="Sign">
                <span class="text-[7px] font-bold text-slate-600">Principal Signature</span>
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-3 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">{{ $currentYear?->name ?? '2025-26' }}</span>
              <span class="text-amber-400">FRONT</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Portrait Front</span>
        </div>

        {{-- Back Portrait --}}
        <div x-show="activeSide === 'both' || activeSide === 'back'" class="print-card-wrap flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between text-left"
               style="width: 240px; height: 380px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Header --}}
            <div class="p-2.5 bg-slate-900 text-white text-center border-b-2 border-rose-900">
              <h4 class="text-[9.5px] font-black uppercase tracking-wider text-white">ERODE PUBLIC SCHOOL</h4>
              <p class="text-[7.5px] text-amber-300">STUDENT IDENTITY &amp; EMERGENCY</p>
            </div>

            {{-- Body --}}
            <div class="p-3 flex-1 flex flex-col justify-between space-y-2">
              <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[9px]">
                <span class="text-[7.5px] font-bold uppercase text-slate-400 block">RESIDENTIAL ADDRESS</span>
                <p class="font-semibold text-slate-800 leading-snug">
                  {{ Str::limit($fullAddress, 80) }}
                </p>
              </div>

              <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[9px]">
                <span class="text-[7.5px] font-bold uppercase text-slate-400 block">PARENT PHONE NUMBER</span>
                <span class="font-mono font-bold text-slate-900">+91 {{ $parentPhone }}</span>
              </div>

              <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[9px] flex items-center justify-between">
                <span class="text-[7.5px] font-bold uppercase text-slate-400">BLOOD GROUP</span>
                <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-bold text-[9px]">
                  {{ $student->blood_group ?: 'O+' }}
                </span>
              </div>

              {{-- QR Code --}}
              <div class="text-center py-1">
                <div class="w-18 h-18 mx-auto bg-white border border-slate-300 p-1 rounded-lg flex items-center justify-center">
                  @if(!empty($qrCodeSvg))
                    {!! $qrCodeSvg !!}
                  @else
                    <span class="text-[8px] text-slate-400 font-mono">[QR CODE]</span>
                  @endif
                </div>
                <span class="text-[7.5px] font-bold text-slate-500 block mt-1">SCAN TO VERIFY</span>
              </div>

              <div class="bg-slate-100 p-1.5 rounded text-[7.5px] text-slate-600 leading-tight border-l-2 border-rose-900">
                If found, return to school office. Helpline: +91 {{ $school->phone ?? '9876543210' }}
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-3 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">VALID: {{ $currentYear?->name ?? '2025-2026' }}</span>
              <span class="text-amber-400">BACK</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Portrait Back</span>
        </div>

      </div>
    </template>

  </div>

</div>
@endsection
