@extends('layouts.app')
@section('title', 'Visitor Gate Pass - ' . $visitor->pass_number)

@push('head')
<style>
/* ==========================================================================
   80mm Thermal Roll Printer Visitor Gate Pass Stylesheet
   Standard 80mm Paper Roll • Printable Area ~72mm
   ========================================================================== */

/* Screen Preview Styling */
.thermal-receipt-preview {
  width: 72mm;
  min-height: 110mm;
  box-sizing: border-box;
  background: #ffffff;
  color: #000000;
  margin: 0 auto;
  padding: 4mm 3mm;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, monospace;
}

@media print {
  /* 1. COMPLETELY HIDE non-printable ERP elements: sidebar, headers, alerts, buttons */
  #sidebar,
  aside,
  header,
  nav,
  #toast-container,
  .alert-success,
  .alert-error,
  .no-print,
  .mobile-nav-speed,
  .printable-badge,
  button,
  a {
    display: none !important;
  }

  /* 2. Reset html & body to 80mm roll width, remove any browser default margins */
  html, body {
    width: 80mm !important;
    min-width: 80mm !important;
    max-width: 80mm !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #ffffff !important;
    color: #000000 !important;
    font-size: 11px !important;
    line-height: 1.3 !important;
    overflow: visible !important;
    height: auto !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, monospace !important;
  }

  /* 3. Strip layout wrappers (flex, h-screen, overflow-hidden) that cause clipping/blank pages */
  .flex,
  .h-screen,
  .overflow-hidden,
  .overflow-y-auto,
  main,
  .page-enter,
  .max-w-2xl {
    display: block !important;
    position: static !important;
    overflow: visible !important;
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* 4. Display ONLY the dedicated thermal roll pass */
  .thermal-pass-wrapper {
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
  }

  #thermal-roll-pass {
    display: block !important;
    visibility: visible !important;
    width: 72mm !important;
    max-width: 72mm !important;
    margin: 0 auto !important;
    padding: 2mm 1mm 4mm 1mm !important;
    box-sizing: border-box !important;
    background: #ffffff !important;
    color: #000000 !important;
    border: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    page-break-after: avoid !important;
  }

  #thermal-roll-pass * {
    visibility: visible !important;
    color: #000000 !important;
    box-shadow: none !important;
    text-shadow: none !important;
  }

  /* Thermal roll page size */
  @page {
    size: 80mm auto;
    margin: 0mm;
  }
}
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="{ passFormat: 'slip' }">

  {{-- Actions Header (No Print) --}}
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
        <button type="button" @click="passFormat = 'slip'" :class="passFormat === 'slip' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'" class="px-2.5 py-1.5 rounded-md transition flex items-center gap-1">
          <span>80mm Thermal Slip</span>
        </button>
        <button type="button" @click="passFormat = 'badge'" :class="passFormat === 'badge' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'" class="px-2.5 py-1.5 rounded-md transition flex items-center gap-1">
          <span>Color Badge</span>
        </button>
      </div>

      <button onclick="window.print()" class="btn-primary text-xs sm:text-sm font-bold flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print Pass (80mm)</span>
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

    $schoolName = $school->school_name ?? 'ERODE PUBLIC SCHOOL';
    $schoolSub = ($school?->board ? $school->board . ' Board' : 'Senior Secondary') . ($school?->affiliation_no ? ' • CBSE Affil. #' . $school->affiliation_no : '');

    // Mask ID proof for privacy
    $maskedIdProof = null;
    if (!empty($visitor->visitor_id_type)) {
        $idTypeStr = strtoupper(str_replace('_', ' ', $visitor->visitor_id_type));
        if (!empty($visitor->visitor_id_number)) {
            $cleaned = trim(preg_replace('/\s+/', '', $visitor->visitor_id_number));
            $maskedIdProof = $idTypeStr . ' (•••• ' . (strlen($cleaned) > 4 ? substr($cleaned, -4) : $cleaned) . ')';
        } else {
            $maskedIdProof = $idTypeStr . ' (Verified)';
        }
    }
  @endphp

  {{-- =========================================================================
       FORMAT 1: DEDICATED 80mm ROLL PRINTER PASS (Default for Thermal Output)
       Printable Width: ~72mm • Black & White • Compact • High-Legibility
       ========================================================================= --}}
  <div x-show="passFormat === 'slip'" class="thermal-pass-wrapper max-w-sm mx-auto">
    <div class="no-print mb-2 flex items-center justify-between text-xs text-slate-500 font-medium px-1">
      <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
        80mm Thermal Roll Receipt (~72mm Printable)
      </span>
      <span class="font-mono text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded border border-slate-200">
        Monochrome / Roll
      </span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 p-4 sm:p-5 shadow-md">
      <div id="thermal-roll-pass" class="thermal-receipt-preview">
        
        {{-- Receipt Header --}}
        <div style="text-align: center; margin-bottom: 4px;">
          @if($schoolLogo)
          <div style="margin-bottom: 3px;">
            <img src="{{ $schoolLogo }}" alt="School Logo" style="max-height: 36px; max-width: 110px; object-fit: contain; margin: 0 auto; display: block; filter: grayscale(100%) contrast(120%);">
          </div>
          @endif
          <h2 style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin: 0; line-height: 1.2; letter-spacing: 0.3px;">
            {{ $schoolName }}
          </h2>
          @if(!empty($schoolSub))
          <p style="font-size: 8.5px; color: #333333; margin: 2px 0 0 0; line-height: 1.1;">
            {{ $schoolSub }}
          </p>
          @endif
        </div>

        {{-- Top Divider --}}
        <div style="border-top: 1px dashed #000000; margin: 5px 0;"></div>

        {{-- Pass Title & Meta --}}
        <div style="text-align: center; margin-bottom: 5px;">
          <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
            VISITOR GATE PASS
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3px; font-size: 9.5px;">
            <span>Pass: <strong style="font-family: monospace; font-size: 10.5px;">{{ $visitor->pass_number }}</strong></span>
            <span>Date: <strong>{{ $visitor->visit_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong></span>
          </div>
        </div>

        {{-- Category Label Box --}}
        <div style="text-align: center; border: 1px solid #000000; padding: 2px 0; margin-bottom: 6px; font-weight: 800; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.5px;">
          {{ $visitor->category_label }}
        </div>

        {{-- Visitor Photo (if uploaded) & Basic Info --}}
        @if($visitor->visitor_photo && Storage::disk('public')->exists($visitor->visitor_photo))
        <div style="display: flex; gap: 8px; align-items: flex-start; margin-bottom: 6px;">
          <img src="{{ Storage::url($visitor->visitor_photo) }}" alt="Photo" style="width: 44px; height: 44px; object-fit: cover; border: 1px solid #000000; filter: grayscale(100%); flex-shrink: 0;">
          <div style="flex: 1; min-width: 0;">
            <div style="font-size: 12px; font-weight: 800; line-height: 1.2; word-break: break-word;">{{ $visitor->visitor_name }}</div>
            <div style="font-size: 9.5px; font-family: monospace; margin-top: 2px;">Phone: {{ $visitor->visitor_phone ?? '—' }}</div>
            <div style="font-size: 9px; margin-top: 1px;">Visitor Count: <strong>{{ $visitor->visitor_count }} Pax</strong></div>
          </div>
        </div>
        @else
        <div style="margin-bottom: 6px;">
          <div style="font-size: 12.5px; font-weight: 800; line-height: 1.2; word-break: break-word;">{{ $visitor->visitor_name }}</div>
          <div style="font-size: 9.5px; display: flex; justify-content: space-between; margin-top: 2px;">
            <span>Phone: <strong style="font-family: monospace;">{{ $visitor->visitor_phone ?? '—' }}</strong></span>
            <span>Visitors: <strong>{{ $visitor->visitor_count }} Pax</strong></span>
          </div>
        </div>
        @endif

        {{-- Divider --}}
        <div style="border-top: 1px dashed #000000; margin: 4px 0 5px 0;"></div>

        {{-- Visit Details Key-Value List --}}
        <div style="space-y: 2px;">
          
          {{-- Person to Meet --}}
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9.5px; margin-bottom: 2px;">
            <span style="font-weight: 700; width: 75px; flex-shrink: 0;">To Meet:</span>
            <span style="flex: 1; text-align: right; font-weight: 700; word-break: break-word;">
              {{ $visitor->whom_to_meet ?? ($visitor->hostEmployee ? $visitor->hostEmployee->full_name : 'School Office') }}
            </span>
          </div>

          {{-- Department (Only when available) --}}
          @if(!empty($visitor->department))
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9.5px; margin-bottom: 2px;">
            <span style="font-weight: 700; width: 75px; flex-shrink: 0;">Department:</span>
            <span style="flex: 1; text-align: right; word-break: break-word;">{{ $visitor->department }}</span>
          </div>
          @endif

          {{-- Purpose (Wrapped naturally without truncation) --}}
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9.5px; margin-bottom: 2px;">
            <span style="font-weight: 700; width: 75px; flex-shrink: 0;">Purpose:</span>
            <span style="flex: 1; text-align: right; word-break: break-word;">{{ $visitor->purpose }}</span>
          </div>

          {{-- Conditional: Student Link (Only when linked to student) --}}
          @if($visitor->student)
          <div style="border: 1px dashed #000000; padding: 3px 5px; margin: 4px 0;">
            <div style="font-size: 8.5px; font-weight: 800; text-transform: uppercase;">Linked Student:</div>
            <div style="font-size: 10.5px; font-weight: 800; word-break: break-word;">
              {{ $visitor->student->first_name }} {{ $visitor->student->last_name }}
            </div>
            <div style="font-size: 9px; display: flex; justify-content: space-between; margin-top: 1px;">
              <span>Adm #: <strong>{{ $visitor->student->admission_no }}</strong></span>
              @if(!empty($visitor->relationship_to_student))
              <span>Rel: <strong>{{ $visitor->relationship_to_student }}</strong></span>
              @endif
            </div>
          </div>
          @elseif($visitor->category === \App\Models\Visitor::CATEGORY_ADMISSION && !empty($visitor->child_name))
          <div style="border: 1px dashed #000000; padding: 3px 5px; margin: 4px 0;">
            <div style="font-size: 8.5px; font-weight: 800; text-transform: uppercase;">Admission Candidate:</div>
            <div style="font-size: 10px; font-weight: 800; word-break: break-word;">{{ $visitor->child_name }}</div>
            <div style="font-size: 9px;">Grade Applying: <strong>{{ $visitor->grade_applying_for ?? 'N/A' }}</strong></div>
          </div>
          @elseif($visitor->category === \App\Models\Visitor::CATEGORY_VENDOR && !empty($visitor->company_name))
          <div style="border: 1px dashed #000000; padding: 3px 5px; margin: 4px 0;">
            <div style="font-size: 8.5px; font-weight: 800; text-transform: uppercase;">Vendor / Agency:</div>
            <div style="font-size: 10px; font-weight: 800; word-break: break-word;">{{ $visitor->company_name }}</div>
            @if(!empty($visitor->work_order_number))
            <div style="font-size: 9px;">Work Order: <strong>{{ $visitor->work_order_number }}</strong></div>
            @endif
          </div>
          @elseif($visitor->category === \App\Models\Visitor::CATEGORY_INTERVIEW && !empty($visitor->job_role_applied))
          <div style="border: 1px dashed #000000; padding: 3px 5px; margin: 4px 0;">
            <div style="font-size: 8.5px; font-weight: 800; text-transform: uppercase;">Interview Role:</div>
            <div style="font-size: 10px; font-weight: 800; word-break: break-word;">{{ $visitor->job_role_applied }}</div>
            @if(!empty($visitor->candidate_ref_number))
            <div style="font-size: 9px;">Ref #: <strong>{{ $visitor->candidate_ref_number }}</strong></div>
            @endif
          </div>
          @endif

          {{-- Entry Timestamp --}}
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9.5px; margin-bottom: 2px;">
            <span style="font-weight: 700; width: 75px; flex-shrink: 0;">Entry Time:</span>
            <span style="flex: 1; text-align: right; font-family: monospace; font-weight: 700;">
              {{ $visitor->in_time ? $visitor->in_time->format('h:i A, d M') : now()->format('h:i A, d M') }}
            </span>
          </div>

          {{-- Actual Exit Time (if completed visit) --}}
          @if($visitor->out_time)
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9.5px; margin-bottom: 2px; font-weight: 800;">
            <span style="width: 75px; flex-shrink: 0;">Actual Exit:</span>
            <span style="flex: 1; text-align: right; font-family: monospace;">
              {{ $visitor->out_time->format('h:i A, d M') }} (Checked Out)
            </span>
          </div>
          @endif

          {{-- Vehicle Number (if available) --}}
          @if(!empty($visitor->vehicle_number))
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9.5px; margin-bottom: 2px;">
            <span style="font-weight: 700; width: 75px; flex-shrink: 0;">Vehicle No:</span>
            <span style="flex: 1; text-align: right; font-family: monospace; font-weight: 700; text-transform: uppercase;">
              {{ $visitor->vehicle_number }}
            </span>
          </div>
          @endif

          {{-- Masked ID Proof (if verified) --}}
          @if($maskedIdProof)
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 9px; margin-bottom: 2px;">
            <span style="font-weight: 700; width: 75px; flex-shrink: 0;">ID Proof:</span>
            <span style="flex: 1; text-align: right; font-family: monospace;">
              {{ $maskedIdProof }}
            </span>
          </div>
          @endif

        </div>

        {{-- Security Clearance Stamp --}}
        <div style="border-top: 1px dashed #000000; margin: 5px 0 4px 0;"></div>
        <div style="text-align: center; border: 1px solid #000000; padding: 3px 2px; margin-bottom: 4px;">
          <div style="font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">
            ★ ENTRY AUTHORIZED ★
          </div>
          <div style="font-size: 8px; font-weight: 600;">
            Gate Security Clearance Verified
          </div>
          <div style="font-size: 7.5px; font-family: monospace;">
            Ref: SEC-{{ substr(md5($visitor->pass_number . $visitor->id), 0, 8) }} &bull; {{ $visitor->visit_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
          </div>
        </div>

        {{-- Signatures Area --}}
        <div style="display: flex; justify-content: space-between; margin-top: 10px; margin-bottom: 4px; font-size: 8.5px;">
          <div style="text-align: center; width: 46%;">
            <div style="border-bottom: 1px dotted #000000; height: 16px;"></div>
            <div style="font-weight: 700; margin-top: 2px;">Security Gatekeeper</div>
          </div>
          <div style="text-align: center; width: 46%;">
            <div style="border-bottom: 1px dotted #000000; height: 16px;"></div>
            <div style="font-weight: 700; margin-top: 2px;">Visitor Signature</div>
          </div>
        </div>

        {{-- Footer Notice --}}
        <div style="border-top: 1px dashed #000000; margin: 4px 0;"></div>
        <div style="text-align: center; font-size: 7.5px; line-height: 1.3;">
          <p style="margin: 0; font-weight: 600;">Please wear this pass visibly while on campus.</p>
          <p style="margin: 1px 0 0 0; font-weight: 700;">Return pass at the gate when exiting.</p>
          <p style="margin: 2px 0 0 0; font-size: 7px; font-family: monospace; color: #444;">
            Printed: {{ now()->format('d/m/Y h:i A') }}
          </p>
        </div>

      </div>
    </div>
  </div>

  {{-- =========================================================================
       FORMAT 2: COLOR ID BADGE CARD (Screen Preview Only - Hidden in Print)
       ========================================================================= --}}
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
            {{ $schoolName }}
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
          @if($visitor->visitor_photo && Storage::disk('public')->exists($visitor->visitor_photo))
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
          <span class="font-bold text-slate-900 text-right">{{ $visitor->whom_to_meet ?? ($visitor->hostEmployee ? $visitor->hostEmployee->full_name : 'General / Reception') }}</span>
        </div>

        @if(!empty($visitor->department))
        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2">
          <span class="text-slate-500 font-medium">Department</span>
          <span class="font-semibold text-slate-800 text-right">{{ $visitor->department }}</span>
        </div>
        @endif

        <div class="flex justify-between items-start border-b border-slate-200/60 pb-2">
          <span class="text-slate-500 font-medium">Purpose</span>
          <span class="font-semibold text-slate-800 text-right max-w-[220px]">{{ $visitor->purpose }}</span>
        </div>

        {{-- Dynamic Category specific row --}}
        @if($visitor->student)
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
              Auth Code: SEC-{{ substr(md5($visitor->pass_number . $visitor->id), 0, 8) }} &bull; {{ $visitor->in_time?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('autoprint') === '1') {
    setTimeout(function () {
      window.print();
    }, 450);
  }
});
</script>
@endpush
@endsection
