@extends('layouts.app')

@section('title', $student->full_name . ' — Student ID Card Studio')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-12" x-data="{
  orientation: '{{ $orientation }}',
  flipped: false,
  reprintModal: false,
  replaceModal: false,
  revokeModal: false,
  reprintReason: 'Physical wear and tear replacement',
  replaceReason: 'Reported lost / damaged',
  revokeReason: 'Card revoked / TC issued'
}">

  {{-- ── 1. Top Navigation & Action Bar ── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3">
      <a href="{{ route('students.id-card-management') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Student ID Card Studio</span>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $card->status_badge_class }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $card->isCardValid() ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
            {{ ucfirst(str_replace('_', ' ', $card->status)) }}
          </span>
        </div>
        <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $student->full_name }}</h1>
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-2 flex-wrap">
      {{-- Switch Orientation --}}
      <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
        <button type="button" @click="orientation = 'landscape'" :class="orientation === 'landscape' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg transition">
          Landscape
        </button>
        <button type="button" @click="orientation = 'portrait'" :class="orientation === 'portrait' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg transition">
          Portrait
        </button>
      </div>

      {{-- Flip Button --}}
      <button type="button" @click="flipped = !flipped" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-bold">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span x-text="flipped ? 'View Front Side' : 'Flip to Back Side'"></span>
      </button>

      {{-- Download PDF Button --}}
      <a :href="'{{ route('students.id-cards.bulk-pdf') }}?student_ids={{ $student->id }}&orientation=' + orientation + '&side=both'"
         target="_blank"
         class="btn btn-primary btn-sm flex items-center gap-2 bg-[#892528] hover:bg-[#721f22] text-white font-bold px-4 py-2 rounded-xl shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Download PDF</span>
      </a>

      {{-- Browser Print --}}
      <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm font-bold flex items-center gap-1.5">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print</span>
      </button>
    </div>
  </div>

  {{-- ── 2. Missing Data Warnings (if any) ── --}}
  @php
    $missingWarnings = [];
    if (empty($student->photo)) {
        $missingWarnings[] = "Student photograph is missing. Please upload a clear passport-size photograph in the student profile.";
    }
    if (empty($student->blood_group)) {
        $missingWarnings[] = "Blood group is not recorded for this student.";
    }
    if (empty($student->father_mobile) && empty($student->mother_mobile) && empty($student->mobile)) {
        $missingWarnings[] = "Parent contact number is missing.";
    }
  @endphp

  @if(!empty($missingWarnings))
    <div class="bg-amber-50 border border-amber-200/90 rounded-2xl p-4 flex items-start gap-3">
      <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-2xs">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <div class="text-xs text-amber-900 leading-relaxed flex-1">
        <p class="font-bold">Missing Data Warning for Physical Card Printing</p>
        <ul class="list-disc list-inside mt-1 space-y-0.5 text-amber-800">
          @foreach($missingWarnings as $warn)
            <li>{{ $warn }}</li>
          @endforeach
        </ul>
      </div>
      <a href="{{ route('students.edit', $student->id) }}" class="btn btn-secondary btn-sm shrink-0 font-bold text-amber-800 border-amber-300">
        Update Profile
      </a>
    </div>
  @endif

  {{-- ── 3. Interactive ID Card Preview Canvas ── --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    {{-- Card Display Stage (8 cols) --}}
    <div class="lg:col-span-8 bg-slate-900/5 p-6 sm:p-10 rounded-3xl border border-slate-200 flex flex-col items-center justify-center min-h-[420px] relative overflow-hidden">
      
      {{-- Card Spec Watermark --}}
      <div class="absolute top-3 left-4 text-[10px] font-mono text-slate-400 font-bold tracking-wider">
        ISO/IEC 7810 ID-1 • CR80 (85.60 × 53.98 mm)
      </div>

      <div class="absolute top-3 right-4 text-[10px] font-mono text-slate-400 font-bold" x-text="flipped ? 'BACK SIDE' : 'FRONT SIDE'"></div>

      {{-- ─────────────────────────────────────────────────────────────
           LANDSCAPE CARD PREVIEW (CR80 RATIO: 85.60 x 53.98 mm)
           AEROSPACE / CYBER-EXECUTIVE BADGE STANDARD
           ───────────────────────────────────────────────────────────── --}}
      @php
        $rollNo = $student->roll_number ?: str_pad($student->id % 100 ?: 1, 2, '0', STR_PAD_LEFT);
        $stdName = $student->currentEnrollment?->class?->name ?? 'II';
        $secName = $student->currentEnrollment?->section?->name ?? 'A';
        $individualPhone = $student->father_mobile ?: ($student->mother_mobile ?: ($student->mobile ?: ($student->emergency_contact_mobile ?: '98420 00008')));
        $individualAddress = $student->residential_address ?: ($student->permanent_address ?: '420, Vakkil Thottam, Erode');
        $sessionYear = $currentYear?->name ?? '2026-2027';
      @endphp

      <div x-show="orientation === 'landscape'" style="display:none" class="w-full max-w-[530px]">
        
        {{-- FRONT SIDE --}}
        <div x-show="!flipped" class="w-full aspect-[85.6/53.98] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden flex flex-col justify-between relative transition-all duration-300 select-none">
          
          {{-- Subtle Cyber Microgrid & Holographic Accent Watermark --}}
          <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:12px_12px] opacity-25 pointer-events-none"></div>
          <div class="absolute -right-12 -top-12 w-44 h-44 bg-gradient-to-br from-[#892528]/30 via-amber-500/10 to-transparent rounded-full blur-2xl pointer-events-none"></div>

          {{-- ── Top Aerospace Header Bar ── --}}
          <div class="relative z-10 bg-gradient-to-r from-[#892528] via-[#6B1A1D] to-[#1E0507] px-4 py-2 border-b border-amber-500/40 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-2.5">
              <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-9 h-9 object-contain filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.6)]" alt="Crest">
              <div class="leading-none">
                <h2 class="text-xs sm:text-sm font-black tracking-wider uppercase text-white font-sans">Erode Public School</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span class="text-[8px] font-bold uppercase tracking-widest text-amber-300">Senior Secondary</span>
                  <span class="text-[7px] text-amber-400/80">•</span>
                  <span class="text-[8px] font-mono text-amber-200 tracking-wider">CBSE Aff No. 1931585</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <div class="bg-black/40 backdrop-blur-xs border border-emerald-500/40 rounded-full px-2 py-0.5 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[8px] font-mono font-bold uppercase tracking-wider text-emerald-300">VALID 26-27</span>
              </div>
            </div>
          </div>

          {{-- ── Body: Large Photo & Executive Data Grid ── --}}
          <div class="relative z-10 flex-1 px-4 py-2.5 flex items-center gap-4">
            
            {{-- Big Commanding Portrait Photo --}}
            <div class="shrink-0 text-center">
              <div class="relative group">
                @if(!empty($student->photo))
                  <img src="{{ asset('storage/' . $student->photo) }}" class="w-28 h-36 rounded-xl object-cover ring-2 ring-amber-400/80 shadow-2xl shadow-black mx-auto" alt="Photo">
                @else
                  <div class="w-28 h-36 rounded-xl bg-slate-900/90 border-2 border-dashed border-slate-700 flex flex-col items-center justify-center text-slate-400 p-2 shadow-inner">
                    <svg class="w-8 h-8 text-slate-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-500">PHOTO</span>
                  </div>
                @endif
                <div class="absolute -bottom-2 inset-x-0 mx-auto w-max bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-mono text-[9px] font-black px-2 py-0.5 rounded-full shadow-md uppercase tracking-wider">
                  ROLL NO: {{ $rollNo }}
                </div>
              </div>
            </div>

            {{-- Personal Data & High-Tech Metrics --}}
            <div class="flex-1 min-w-0 space-y-1.5 pt-0.5">
              
              {{-- Full Name --}}
              <div>
                <h3 class="text-base font-black uppercase text-white tracking-wide truncate leading-tight drop-shadow-sm">
                  {{ $student->full_name }}
                </h3>
                <span class="text-[9px] font-mono uppercase text-slate-400 tracking-wider">STUDENT ACCESS IDENTIFIER</span>
              </div>

              {{-- Standard & Section + Academic Year Badges --}}
              <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                <div class="bg-slate-800/90 border border-slate-700/80 px-2 py-0.5 rounded-md flex items-center gap-1.5 shadow-xs">
                  <span class="text-[9px] font-bold text-slate-400 uppercase">STD:</span>
                  <span class="text-[10px] font-black text-amber-300 font-mono">{{ $stdName }}</span>
                  <span class="text-slate-600">|</span>
                  <span class="text-[9px] font-bold text-slate-400 uppercase">SEC:</span>
                  <span class="text-[10px] font-black text-amber-300 font-mono">{{ $secName }}</span>
                </div>

                <div class="bg-slate-800/90 border border-slate-700/80 px-2 py-0.5 rounded-md flex items-center gap-1 text-[9px] font-mono text-slate-300">
                  <span class="text-slate-400 font-bold">YEAR:</span>
                  <span class="font-bold text-white">{{ $sessionYear }}</span>
                  <span class="text-[7.5px] bg-emerald-500/20 text-emerald-300 px-1 rounded font-bold uppercase">PRESENT</span>
                </div>
              </div>

              {{-- Individual Contact & Residential Address (from individual record) --}}
              <div class="space-y-1 text-[10px] pt-1">
                <div class="flex items-baseline gap-1.5 text-slate-300">
                  <span class="text-[9px] font-mono font-bold uppercase text-slate-400 shrink-0">PHONE:</span>
                  <span class="font-mono font-bold text-white tracking-wide">{{ $individualPhone }}</span>
                </div>
                
                <div class="flex items-baseline gap-1.5 text-slate-300">
                  <span class="text-[9px] font-mono font-bold uppercase text-slate-400 shrink-0">ADDRESS:</span>
                  <span class="text-[9.5px] text-slate-300 truncate font-medium" title="{{ $individualAddress }}">
                    {{ Str::limit($individualAddress, 40) }}
                  </span>
                </div>

                <div class="flex items-center gap-3 text-[9px] text-slate-400 pt-0.5">
                  @if(!empty($student->blood_group))
                    <span>BLOOD: <strong class="text-rose-400 font-bold">{{ $student->blood_group }}</strong></span>
                  @endif
                  @if(!empty($student->dob))
                    <span>DOB: <strong class="text-slate-200 font-mono">{{ $student->dob->format('d/m/Y') }}</strong></span>
                  @endif
                </div>
              </div>

            </div>

          </div>

          {{-- ── Bottom Bar: Sleek Code 128 Barcode Bay ── --}}
          <div class="relative z-10 bg-white px-4 py-1.5 border-t border-slate-700 flex items-center justify-between text-slate-900 shadow-inner">
            <div class="flex items-center gap-3">
              <div class="w-48 h-7 flex items-center overflow-hidden">
                {!! $barcodeSvg !!}
              </div>
              <span class="text-[8px] font-mono font-bold uppercase tracking-wider text-slate-600">
                * {{ $barcodeVal }} *
              </span>
            </div>

            <div class="text-right">
              <span class="text-[8px] font-mono font-black text-[#892528] uppercase tracking-wider block">ERODE PUBLIC SCHOOL</span>
              <span class="text-[7.5px] font-mono text-slate-500">OFFICIAL VERIFIED BADGE</span>
            </div>
          </div>

        </div>

        {{-- BACK SIDE LANDSCAPE --}}
        <div x-show="flipped" style="display:none" class="w-full aspect-[85.6/53.98] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden p-4 flex flex-col justify-between relative transition-all duration-300 select-none text-xs">
          
          {{-- Magnetic Stripe Simulation --}}
          <div class="-mx-4 -mt-4 h-9 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border-b border-slate-800 flex items-center px-4 justify-between">
            <span class="text-[8px] font-mono text-slate-500 uppercase tracking-widest">EPS SMART ACCESS IDENTIFIER</span>
            <span class="text-[8px] font-mono text-amber-400 font-bold">CR80 // SECURE</span>
          </div>

          <div class="space-y-2 pt-1 text-[10px]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
              <div>
                <span class="text-[8.5px] font-bold text-slate-400 uppercase block">STUDENT HOLDER</span>
                <p class="font-black text-white text-xs uppercase">{{ $student->full_name }}</p>
              </div>
              <div class="text-right font-mono">
                <span class="text-[8.5px] font-bold text-slate-400 uppercase block">ROLL NO</span>
                <p class="font-black text-amber-400">{{ $rollNo }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[9.5px]">
              <div>
                <span class="text-[8px] font-bold text-slate-400 uppercase block">PERSONAL EMERGENCY CONTACT</span>
                <p class="font-mono font-bold text-white">{{ $individualPhone }}</p>
              </div>
              <div>
                <span class="text-[8px] font-bold text-slate-400 uppercase block">RESIDENTIAL ADDRESS</span>
                <p class="text-slate-300 leading-tight">{{ Str::limit($individualAddress, 48) }}</p>
              </div>
            </div>
          </div>

          {{-- Institutional Campus Address & Return Authority --}}
          <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800 text-[8.5px] text-slate-400 space-y-0.5">
            <p class="text-[8px] font-bold uppercase tracking-wider text-amber-400">If found, please return to institutional campus:</p>
            <p class="font-medium text-slate-200">Erode Public School, 420, Vakkil Thottam, Manickam Palayam, Erode, Tamil Nadu – 638004</p>
            <p class="font-mono text-slate-400">Institutional Helpline: <strong class="text-white">93677 54654</strong> &bull; Web: erodepublicschool.edu.in</p>
          </div>

        </div>

      </div>

      {{-- ─────────────────────────────────────────────────────────────
           PORTRAIT CARD PREVIEW (CR80 RATIO: 53.98 x 85.6 mm)
           AEROSPACE / CYBER-EXECUTIVE VERTICAL BADGE
           ───────────────────────────────────────────────────────────── --}}
      <div x-show="orientation === 'portrait'" style="display:none" class="w-full max-w-[340px]">
        
        {{-- FRONT SIDE PORTRAIT --}}
        <div x-show="!flipped" class="w-full aspect-[53.98/85.6] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden flex flex-col justify-between text-center relative transition-all duration-300 select-none">
          
          {{-- Cyber Microgrid Background --}}
          <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:12px_12px] opacity-25 pointer-events-none"></div>

          {{-- Header --}}
          <div class="relative z-10 bg-gradient-to-b from-[#892528] to-[#601518] text-white px-3 py-2.5 border-b border-amber-500/40">
            <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-10 h-10 object-contain mx-auto filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.6)]" alt="Crest">
            <h2 class="text-xs font-black tracking-wider uppercase mt-1 leading-tight">Erode Public School</h2>
            <p class="text-[8px] text-amber-300 font-bold uppercase tracking-widest mt-0.5">CBSE &bull; Aff No. 1931585</p>
          </div>

          {{-- Photo & Identity Details --}}
          <div class="relative z-10 px-4 pt-2.5 space-y-2">
            
            {{-- Big Commanding Photo in Portrait --}}
            <div class="relative inline-block mx-auto">
              @if(!empty($student->photo))
                <img src="{{ asset('storage/' . $student->photo) }}" class="w-32 h-40 rounded-2xl object-cover ring-2 ring-amber-400 shadow-2xl shadow-black mx-auto" alt="Photo">
              @else
                <div class="w-32 h-40 rounded-2xl bg-slate-900 border-2 border-dashed border-slate-700 flex flex-col items-center justify-center text-slate-400 p-2 shadow-inner mx-auto">
                  <svg class="w-10 h-10 text-slate-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-500">PHOTO</span>
                </div>
              @endif
              
              <div class="absolute -bottom-2 inset-x-0 mx-auto w-max bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-mono text-[9px] font-black px-2.5 py-0.5 rounded-full shadow-md uppercase tracking-wider">
                ROLL NO: {{ $rollNo }}
              </div>
            </div>

            {{-- Full Name --}}
            <div class="pt-1">
              <h3 class="text-sm font-black uppercase text-white tracking-wide truncate leading-tight drop-shadow-sm">
                {{ $student->full_name }}
              </h3>
            </div>

            {{-- Standard, Section, Session Chips --}}
            <div class="bg-slate-900/90 p-2 rounded-xl border border-slate-800 text-left text-[9.5px] space-y-1">
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-bold uppercase text-[8.5px]">STD &amp; SEC:</span>
                <span class="font-black text-amber-300 font-mono">{{ $stdName }} - {{ $secName }}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-bold uppercase text-[8.5px]">ACADEMIC YEAR:</span>
                <span class="font-mono text-slate-200 font-bold">{{ $sessionYear }} <span class="text-[7.5px] text-emerald-400 uppercase font-black">(PRESENT)</span></span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-bold uppercase text-[8.5px]">PHONE:</span>
                <span class="font-mono font-bold text-white">{{ $individualPhone }}</span>
              </div>

              <div class="pt-0.5 border-t border-slate-800/80">
                <span class="text-slate-400 font-bold uppercase text-[8px] block">ADDRESS:</span>
                <span class="text-[8.5px] text-slate-300 block truncate" title="{{ $individualAddress }}">{{ Str::limit($individualAddress, 36) }}</span>
              </div>
            </div>

          </div>

          {{-- Bottom Bar: Linear Code 128 Barcode Bay --}}
          <div class="relative z-10 bg-white p-2 border-t border-slate-700 text-slate-900 text-center shadow-inner">
            <div class="w-full h-7 flex items-center justify-center overflow-hidden">
              {!! $barcodeSvg !!}
            </div>
            <span class="text-[7.5px] font-mono font-bold uppercase tracking-wider text-slate-600 block mt-0.5">
              * {{ $barcodeVal }} *
            </span>
          </div>

        </div>

        {{-- BACK SIDE PORTRAIT --}}
        <div x-show="flipped" style="display:none" class="w-full aspect-[53.98/85.6] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden p-4 flex flex-col justify-between text-center relative transition-all duration-300 select-none">
          
          <div class="-mx-4 -mt-4 h-8 bg-slate-900 border-b border-slate-800 flex items-center px-3 justify-between text-[8px] font-mono text-slate-400">
            <span>EPS ACCESS PASS</span>
            <span class="text-amber-400">ROLL: {{ $rollNo }}</span>
          </div>

          <div class="space-y-2 text-left text-[9px] pt-2">
            <p class="text-slate-400 font-bold uppercase text-[8px]">Student Identity:</p>
            <p class="font-black text-white text-xs uppercase">{{ $student->full_name }}</p>
            
            <p class="text-slate-400 font-bold uppercase text-[8px] pt-1">Parent Contact:</p>
            <p class="font-mono font-bold text-amber-300">{{ $individualPhone }}</p>

            <p class="text-slate-400 font-bold uppercase text-[8px] pt-1">Residential Address:</p>
            <p class="text-slate-300 leading-tight">{{ $individualAddress }}</p>
          </div>

          <div class="bg-slate-900 p-2.5 rounded-xl border border-slate-800 text-[8px] text-slate-400 space-y-0.5 text-left">
            <p class="font-bold uppercase tracking-wider text-amber-400 text-[7.5px]">If found, return to school campus:</p>
            <p class="text-slate-200">Erode Public School, 420, Vakkil Thottam, Erode – 638004</p>
            <p class="font-mono">Helpline: <strong class="text-white">93677 54654</strong></p>
          </div>

        </div>

      </div>

    </div>

    {{-- Card Details, Lifecycle & Audit Sidebar (4 cols) --}}
    <div class="lg:col-span-4 space-y-5">
      
      {{-- Card Lifecycle Card --}}
      <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Card Lifecycle &amp; Metadata</h3>
        
        <div class="space-y-2.5 text-xs">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Card Number:</span>
            <span class="font-mono font-bold text-slate-900">{{ $card->card_number }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Status:</span>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $card->status_badge_class }}">
              {{ ucfirst(str_replace('_', ' ', $card->status)) }}
            </span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Issue Date:</span>
            <span class="font-mono text-slate-800">{{ $card->issue_date?->format('d/m/Y') }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Valid Until:</span>
            <span class="font-mono text-slate-800">{{ $card->expiry_date?->format('d/m/Y') ?? 'Academic Session' }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Reprint Count:</span>
            <span class="font-mono font-bold {{ $card->reprint_count > 0 ? 'text-amber-700' : 'text-slate-800' }}">
              {{ $card->reprint_count }} {{ $card->reprint_count === 1 ? 'time' : 'times' }}
            </span>
          </div>

          @if($card->reprint_reason)
          <div class="pb-2 border-b border-slate-100">
            <span class="text-slate-400 block text-[10px] uppercase font-bold">Latest Reprint Reason:</span>
            <span class="text-slate-700 italic text-[11px]">{{ $card->reprint_reason }}</span>
          </div>
          @endif
        </div>

        {{-- Lifecycle Action Buttons --}}
        <div class="pt-2 grid grid-cols-2 gap-2 text-xs">
          <button type="button" @click="reprintModal = true" class="btn btn-secondary btn-sm font-bold flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Log Reprint
          </button>
          
          <button type="button" @click="replaceModal = true" class="btn btn-secondary btn-sm font-bold text-amber-700 border-amber-200 flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Replace Card
          </button>

          <button type="button" @click="revokeModal = true" class="btn btn-secondary btn-sm col-span-2 font-bold text-rose-700 border-rose-200 flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Revoke Card (Invalidate)
          </button>
        </div>
      </div>

      {{-- Verification Test Box --}}
      <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 space-y-2.5">
        <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <span>Official Institutional Verification</span>
        </div>
        <p class="text-[11px] text-emerald-800 leading-relaxed">
          The linear Code 128 barcode and verified credential identifier strictly map to the school's central live register.
        </p>
        <a href="{{ $verifyUrl ?? ($card ? $card->getQrVerificationUrl() : '#') }}" target="_blank" class="btn btn-secondary btn-sm w-full font-bold text-emerald-800 border-emerald-300 hover:bg-emerald-100 flex items-center justify-center gap-1.5 text-xs">
          <span>Test Live Verification Endpoint</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
      </div>

      {{-- Card Audit History List --}}
      <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Card Audit History</h3>
        
        <div class="space-y-2 max-h-56 overflow-y-auto text-xs">
          @forelse($auditLogs as $log)
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-0.5">
              <div class="flex items-center justify-between">
                <span class="font-bold uppercase tracking-wider text-[10px] {{ $log->action === 'created' ? 'text-emerald-700' : ($log->action === 'reprinted' ? 'text-blue-700' : 'text-amber-700') }}">
                  {{ $log->action }}
                </span>
                <span class="text-[10px] font-mono text-slate-400">{{ $log->timestamp->format('d M Y, h:i A') }}</span>
              </div>
              @if($log->reason)
                <p class="text-[11px] text-slate-600">{{ $log->reason }}</p>
              @endif
              <p class="text-[10px] text-slate-400">By: {{ $log->performer?->name ?? 'System' }}</p>
            </div>
          @empty
            <p class="text-slate-400 text-xs italic">No prior audit records.</p>
          @endforelse
        </div>
      </div>

    </div>

  </div>

  {{-- ── Reprint Modal ── --}}
  <div x-show="reprintModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-sm">Log ID Card Reprint</h3>
        <button type="button" @click="reprintModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <p class="text-xs text-slate-500">
        Log an official reprint for Card <strong class="text-slate-800 font-mono">{{ $card->card_number }}</strong>.
      </p>
      <form action="{{ route('id-cards.reprint', $card->id) }}" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reprint Reason</label>
          <input type="text" name="reason" x-model="reprintReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="reprintModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-[#892528] text-white font-bold">Confirm Reprint</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Replace Modal ── --}}
  <div x-show="replaceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-amber-700 text-sm">Replace Lost / Damaged Card</h3>
        <button type="button" @click="replaceModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <p class="text-xs text-slate-600">
        This action will <strong>deactivate &amp; replace</strong> card <strong class="text-slate-800 font-mono">{{ $card->card_number }}</strong>. The old physical card will fail verification.
      </p>
      <form action="{{ route('id-cards.replace', $card->id) }}" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Replacement</label>
          <input type="text" name="reason" x-model="replaceReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="replaceModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-amber-600 hover:bg-amber-700 text-white font-bold">Issue Replacement Card</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Revoke Modal ── --}}
  <div x-show="revokeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-rose-700 text-sm">Revoke Student Card</h3>
        <button type="button" @click="revokeModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <p class="text-xs text-slate-600">
        Revoking card <strong class="text-slate-800 font-mono">{{ $card->card_number }}</strong> will immediately invalidate it.
      </p>
      <form action="{{ route('id-cards.revoke', $card->id) }}" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Revocation Reason</label>
          <input type="text" name="reason" x-model="revokeReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="revokeModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-rose-600 hover:bg-rose-700 text-white font-bold">Revoke Card</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
