@extends('layouts.app')
@section('title', 'Visitor Gate Pass - ' . $visitor->pass_number)
@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="{ passFormat: 'badge' }">

  {{-- Actions Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print">
    <div class="flex items-center gap-2">
      <a href="{{ route('gate.index') }}" class="btn-secondary text-xs sm:text-sm font-semibold flex items-center gap-1.5">
        ← Gate Dashboard
      </a>
      <a href="{{ route('gate.show', $visitor->id) }}" class="btn-secondary text-xs sm:text-sm font-semibold">
        Full Details
      </a>
    </div>

    <div class="flex items-center gap-2">
      {{-- Format Switcher --}}
      <div class="bg-slate-200/80 p-0.5 rounded-lg flex items-center text-xs font-semibold">
        <button type="button" @click="passFormat = 'badge'" :class="passFormat === 'badge' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'" class="px-2.5 py-1.5 rounded-md transition">
          ID Badge
        </button>
        <button type="button" @click="passFormat = 'slip'" :class="passFormat === 'slip' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'" class="px-2.5 py-1.5 rounded-md transition">
          Thermal Slip
        </button>
      </div>

      <button onclick="window.print()" class="btn-primary text-xs sm:text-sm font-bold flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print Pass</span>
      </button>
    </div>
  </div>

  @if(session('success'))
  <div class="no-print rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 text-sm font-medium flex items-center gap-2.5 shadow-xs">
    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    <span>{{ session('success') }}</span>
  </div>
  @endif

  @php
    $catBadge = $visitor->category_badge_classes;
    $isApprovedOrDirect = in_array($visitor->status, [\App\Models\Visitor::STATUS_CHECKED_IN, \App\Models\Visitor::STATUS_APPROVED, \App\Models\Visitor::STATUS_CHECKED_OUT]);
    
    // Determine School Logo
    $schoolLogo = null;
    if (!empty($school->logo) && \Illuminate\Support\Facades\Storage::disk('public')->exists($school->logo)) {
        $schoolLogo = \Illuminate\Support\Facades\Storage::url($school->logo);
    } elseif (file_exists(public_path('images/school-logo-transparent.png'))) {
        $schoolLogo = asset('images/school-logo-transparent.png');
    } elseif (file_exists(public_path('images/school-crest-hd.png'))) {
        $schoolLogo = asset('images/school-crest-hd.png');
    } elseif (file_exists(public_path('images/school-logo.png'))) {
        $schoolLogo = asset('images/school-logo.png');
    }

    // Determine School Seal
    $schoolSeal = null;
    if (!empty($school->seal_1) && \Illuminate\Support\Facades\Storage::disk('public')->exists($school->seal_1)) {
        $schoolSeal = \Illuminate\Support\Facades\Storage::url($school->seal_1);
    } elseif (!empty($school->school_stamp) && \Illuminate\Support\Facades\Storage::disk('public')->exists($school->school_stamp)) {
        $schoolSeal = \Illuminate\Support\Facades\Storage::url($school->school_stamp);
    } elseif (file_exists(public_path('images/school-seal-badge.png'))) {
        $schoolSeal = asset('images/school-seal-badge.png');
    }
  @endphp

  {{-- FORMAT 1: Modern Color-Coded ID Badge Card with School Logo & Seal --}}
  <div x-show="passFormat === 'badge'" class="printable-badge bg-white rounded-3xl border-2 {{ $catBadge['border'] }} shadow-xl overflow-hidden max-w-md mx-auto">
    
    {{-- Header Band with Official School Logo --}}
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 relative">
      <div class="flex items-center gap-3.5">
        @if($schoolLogo)
        <div class="w-12 h-12 rounded-xl bg-white p-1 flex items-center justify-center flex-shrink-0 shadow-md">
          <img src="{{ $schoolLogo }}" alt="School Logo" class="max-w-full max-h-full object-contain">
        </div>
        @else
        <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-lg flex-shrink-0 shadow-md">
          EPS
        </div>
        @endif

        <div class="min-w-0 flex-1">
          <h2 class="text-sm sm:text-base font-extrabold tracking-tight text-white uppercase truncate">
            {{ $school->school_name ?? 'DASA English Primary & Secondary School' }}
          </h2>
          <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-indigo-300 mt-0.5">
            <span>OFFICIAL VISITOR PASS</span>
            <span class="font-mono text-[10px] text-slate-300">{{ $visitor->visit_date?->format('d M Y') ?? now()->format('d M Y') }}</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Category Color Banner --}}
    <div class="py-1.5 px-4 text-center text-xs font-black uppercase tracking-wider {{ $catBadge['badge'] }} shadow-inner">
      {{ $visitor->category_label }}
    </div>

    {{-- Badge Body --}}
    <div class="p-6 space-y-5 relative">
      
      {{-- Visitor Photo & Pass Number --}}
      <div class="flex items-center gap-4">
        <div class="relative flex-shrink-0">
          @if($visitor->visitor_photo)
          <img src="{{ Storage::url($visitor->visitor_photo) }}" alt="{{ $visitor->visitor_name }}" class="w-24 h-24 rounded-2xl object-cover border-2 border-slate-200 shadow-md">
          @else
          <div class="w-24 h-24 rounded-2xl bg-slate-100 border-2 border-slate-200 flex items-center justify-center text-3xl font-black text-slate-600 shadow-inner">
            {{ strtoupper(substr($visitor->visitor_name, 0, 2)) }}
          </div>
          @endif

          <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase">
            {{ $visitor->visitor_count }} Pax
          </span>
        </div>

        <div class="space-y-1 min-w-0">
          <span class="text-[10px] font-mono font-bold text-slate-500 block uppercase tracking-wider">Pass Number</span>
          <span class="font-mono text-base font-black text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200 inline-block shadow-2xs">
            {{ $visitor->pass_number }}
          </span>
          <h3 class="text-lg font-black text-slate-900 truncate leading-tight">{{ $visitor->visitor_name }}</h3>
          <p class="text-xs text-slate-600 font-mono font-bold">{{ $visitor->visitor_phone ?? 'No phone' }}</p>
        </div>
      </div>

      {{-- Details Grid --}}
      <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-2.5 text-xs">
        
        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2">
          <span class="text-slate-500 font-medium">Person to Meet</span>
          <span class="font-bold text-slate-900 text-right">{{ $visitor->whom_to_meet ?? 'General / Reception' }}</span>
        </div>

        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2">
          <span class="text-slate-500 font-medium">Department</span>
          <span class="font-semibold text-slate-800 text-right">{{ $visitor->department ?? 'General' }}</span>
        </div>

        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2">
          <span class="text-slate-500 font-medium">Purpose</span>
          <span class="font-semibold text-slate-800 text-right max-w-[220px]">{{ $visitor->purpose }}</span>
        </div>

        {{-- Dynamic Category specific row --}}
        @if($visitor->category === \App\Models\Visitor::CATEGORY_PARENT && $visitor->student)
        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2 bg-emerald-50/70 -mx-2 px-2 py-1.5 rounded-lg border border-emerald-200">
          <span class="text-emerald-800 font-bold">Student Link</span>
          <span class="font-bold text-emerald-950 text-right">{{ $visitor->student->first_name }} {{ $visitor->student->last_name }} ({{ $visitor->student->admission_no }})</span>
        </div>
        @elseif($visitor->category === \App\Models\Visitor::CATEGORY_ADMISSION && $visitor->child_name)
        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2 bg-blue-50/70 -mx-2 px-2 py-1.5 rounded-lg border border-blue-200">
          <span class="text-blue-800 font-bold">Admission Child</span>
          <span class="font-bold text-blue-950 text-right">{{ $visitor->child_name }} (Grade: {{ $visitor->grade_applying_for ?? 'N/A' }})</span>
        </div>
        @elseif($visitor->category === \App\Models\Visitor::CATEGORY_VENDOR && $visitor->company_name)
        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2 bg-amber-50/70 -mx-2 px-2 py-1.5 rounded-lg border border-amber-200">
          <span class="text-amber-800 font-bold">Agency / Tools</span>
          <span class="font-bold text-amber-950 text-right">{{ $visitor->company_name }}</span>
        </div>
        @endif

        <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
          <span class="text-slate-500 font-medium">Entry Timestamp</span>
          <span class="font-mono font-bold text-slate-900">{{ $visitor->in_time ? $visitor->in_time->format('h:i A, d M Y') : 'Direct Entry Clearance' }}</span>
        </div>

        @if($visitor->vehicle_number)
        <div class="flex justify-between items-center">
          <span class="text-slate-500 font-medium">Vehicle Reg.</span>
          <span class="font-mono font-bold text-slate-800 uppercase">{{ $visitor->vehicle_number }}</span>
        </div>
        @endif

      </div>

      {{-- Automatically Generated Official School Seal on Approval / Direct Entry --}}
      @if($isApprovedOrDirect)
      <div class="py-2 flex items-center justify-center">
        <div class="relative flex items-center gap-3 p-3 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border-2 border-emerald-500/80 rounded-2xl shadow-sm w-full">
          
          {{-- Circular Seal Graphic --}}
          <div class="w-16 h-16 rounded-full border-2 border-emerald-600 bg-white p-1 flex items-center justify-center flex-shrink-0 shadow-inner rotate-[-6deg]">
            @if($schoolSeal)
            <img src="{{ $schoolSeal }}" alt="Official School Seal" class="w-full h-full object-contain">
            @else
            <div class="w-full h-full rounded-full border border-dashed border-emerald-500 flex flex-col items-center justify-center text-center p-0.5">
              <span class="text-[7px] font-black text-emerald-800 uppercase leading-none">EPS SCHOOL</span>
              <span class="text-[6px] font-bold text-emerald-600 uppercase">SEAL</span>
              <span class="text-[8px] font-black text-emerald-700">★ ★ ★</span>
            </div>
            @endif
          </div>

          {{-- Seal Verification Metadata --}}
          <div class="space-y-0.5 min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
              <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <h4 class="text-xs font-black text-emerald-950 uppercase tracking-wider">OFFICIALLY AUTHORIZED ENTRY</h4>
            </div>
            <p class="text-[11px] font-bold text-emerald-800">
              Verified by Gate Security &amp; Host Clearance
            </p>
            <p class="text-[10px] text-emerald-700 font-mono">
              Auth Code: SEC-{{ substr(md5($visitor->pass_number . $visitor->id), 0, 8) }} • {{ $visitor->in_time?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
            </p>
          </div>
        </div>
      </div>
      @endif

      {{-- Signatures & Security Stamp Footer --}}
      <div class="pt-4 border-t border-dashed border-slate-200 grid grid-cols-2 gap-4 text-center text-[10px] text-slate-500">
        <div>
          <div class="h-8 border-b border-slate-300 flex items-end justify-center pb-1">
            <span class="font-mono text-[9px] text-slate-400">Verified at Gate 1</span>
          </div>
          <p class="mt-1 font-bold text-slate-700">Security Gatekeeper</p>
        </div>
        <div>
          <div class="h-8 border-b border-slate-300 flex items-end justify-center pb-1">
            <span class="font-mono text-[9px] text-slate-400">{{ $visitor->whom_to_meet ? 'Host Clearance' : 'Office Admin' }}</span>
          </div>
          <p class="mt-1 font-bold text-slate-700">Host / Principal Office</p>
        </div>
      </div>

    </div>

    {{-- Bottom Notice --}}
    <div class="bg-slate-100 p-3 text-center text-[10px] text-slate-600 border-t border-slate-200 font-medium">
      Please wear this pass visibly at all times while on school premises. Hand over at exit gate upon departure.
    </div>
  </div>

  {{-- FORMAT 2: 80mm POS Thermal Slip Format with School Logo & Seal --}}
  <div x-show="passFormat === 'slip'" class="printable-thermal bg-white rounded-xl border border-slate-300 p-5 max-w-xs mx-auto shadow-md font-mono text-xs text-slate-900 space-y-3">
    
    {{-- Header with School Name & Logo --}}
    <div class="text-center space-y-1 border-b border-dashed border-slate-400 pb-3">
      @if($schoolLogo)
      <img src="{{ $schoolLogo }}" alt="School Logo" class="w-10 h-10 object-contain mx-auto mb-1">
      @endif
      <h2 class="font-black text-sm uppercase">{{ $school->school_name ?? 'DASA ENGLISH SCHOOL' }}</h2>
      <p class="text-[10px] uppercase font-bold text-slate-600">Visitor Gate Pass</p>
      <p class="text-[10px] text-slate-500">{{ now()->format('d-M-Y H:i:s') }}</p>
    </div>

    <div class="text-center py-1 bg-slate-100 font-bold uppercase text-[11px] border border-slate-300 rounded">
      {{ $visitor->category_label }}
    </div>

    <div class="space-y-1 text-[11px] border-b border-dashed border-slate-400 pb-3">
      <div class="flex justify-between">
        <span>Pass #:</span>
        <span class="font-bold">{{ $visitor->pass_number }}</span>
      </div>
      <div class="flex justify-between">
        <span>Visitor:</span>
        <span class="font-bold truncate max-w-[130px]">{{ $visitor->visitor_name }}</span>
      </div>
      <div class="flex justify-between">
        <span>Phone:</span>
        <span>{{ $visitor->visitor_phone ?? '—' }}</span>
      </div>
      <div class="flex justify-between">
        <span>Persons:</span>
        <span>{{ $visitor->visitor_count }}</span>
      </div>
      <div class="flex justify-between">
        <span>Host:</span>
        <span class="font-bold truncate max-w-[130px]">{{ $visitor->whom_to_meet ?? '—' }}</span>
      </div>
      <div class="flex justify-between">
        <span>Dept:</span>
        <span>{{ $visitor->department ?? '—' }}</span>
      </div>
      <div class="flex justify-between">
        <span>Purpose:</span>
        <span class="truncate max-w-[130px]">{{ $visitor->purpose }}</span>
      </div>
      <div class="flex justify-between">
        <span>In Time:</span>
        <span class="font-bold">{{ $visitor->in_time?->format('H:i') ?? '—' }}</span>
      </div>
      @if($visitor->vehicle_number)
      <div class="flex justify-between">
        <span>Vehicle:</span>
        <span>{{ $visitor->vehicle_number }}</span>
      </div>
      @endif
    </div>

    {{-- Thermal Seal Stamp Text --}}
    @if($isApprovedOrDirect)
    <div class="text-center p-2 border border-slate-400 rounded-lg space-y-0.5">
      <p class="font-bold text-[10px] uppercase">*** ENTRY AUTHORIZED &amp; VERIFIED ***</p>
      <p class="text-[9px] text-slate-600">OFFICIAL GATE CLEARANCE</p>
    </div>
    @endif

    <div class="text-center text-[9px] text-slate-500 pt-1">
      Return pass at gate during exit
    </div>
  </div>

  {{-- On-Page Action Bar (No Print) --}}
  <div class="no-print bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
    <div class="text-xs">
      <span class="text-slate-500">Current Status:</span>
      <span class="font-bold text-slate-800 uppercase ml-1">{{ $visitor->status }}</span>
      @if($visitor->isInside())
      <span class="text-emerald-600 font-semibold ml-2">• Currently Inside Campus</span>
      @endif
    </div>

    @if($visitor->isInside())
    <form method="POST" action="{{ route('gate.checkout', $visitor->id) }}">
      @csrf @method('PATCH')
      <button type="submit" class="btn-primary text-xs font-bold bg-slate-900 hover:bg-slate-800">
        ✓ Check Out Visitor Now
      </button>
    </form>
    @else
    <span class="text-xs font-semibold text-slate-500">
      Checked out at {{ $visitor->out_time?->format('h:i A') }}
    </span>
    @endif
  </div>

</div>

{{-- Dedicated Print Stylesheet --}}
@push('styles')
<style>
@media print {
  body * {
    visibility: hidden;
  }
  .no-print, nav, header, aside {
    display: none !important;
  }
  .printable-badge, .printable-badge *,
  .printable-thermal, .printable-thermal * {
    visibility: visible;
  }
  .printable-badge, .printable-thermal {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    box-shadow: none !important;
  }
}
</style>
@endpush
@endsection
