@extends('layouts.app')

@section('title', 'Admission Application Received — ' . ($student->admission_no ?? 'EPSB0003'))

@section('content')
@php
  $totalFee   = (float) ($student->total_admission_fee ?? 0);
  $paidFee    = (float) ($student->admission_paid_amount ?? 0);
  $pendingFee = (float) ($student->admission_pending_amount ?? max(0, $totalFee - $paidFee));
  $paidPct    = $totalFee > 0 ? min(100, max(0, round(($paidFee / $totalFee) * 100))) : 100;

  $docPortalUrl = route('public.student.documents', ['token' => $student->document_token]);
  $waDocText    = urlencode("Dear Parent, Here is the secure link to upload documents for {$student->full_name} (Admission #{$student->admission_no}):\n" . $docPortalUrl . "\nYou can click the link from your phone camera to take photos of Birth Certificate, Aadhaar, TC, etc.");

  $status = $student->status ?? 'pending_principal';
  $isStage1Done = true;
  $isStage2Done = in_array($status, ['principal_approved', 'active']);
  $isStage2Active = ($status === 'pending_principal');
  $isStage3Done = ($status === 'active');
  $isStage3Active = ($status === 'principal_approved');

  $fatherMobileClean = preg_replace('/[^0-9]/', '', $student->father_mobile ?? $student->parent_mobile ?? '');
  $motherMobileClean = preg_replace('/[^0-9]/', '', $student->mother_mobile ?? '');
@endphp

<div class="max-w-5xl mx-auto space-y-6 pb-12 print:p-0 print:m-0 print:max-w-none">

  {{-- ── Breadcrumb & Top Bar ─────────────────────────────────────────── --}}
  <div class="space-y-3 print:hidden">
    <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
      <a href="{{ route('admissions.index') }}" class="hover:text-slate-800 transition">Admissions</a>
      <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      <span class="text-slate-700 font-semibold">Application Reference</span>
      <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      <span class="text-[#8C2826] font-bold">{{ $student->admission_no }}</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <a href="{{ route('admissions.index') }}"
           title="Back to Admissions List"
           class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white hover:bg-slate-100 text-slate-700 transition border border-slate-200/80 shadow-xs shrink-0 group">
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Admission Application Received</h1>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Logged Successfully
            </span>
          </div>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-slate-500 font-medium">
            <span>Application Reference: <strong class="text-slate-800 font-bold font-mono">{{ $student->admission_no }}</strong></span>
            <span class="text-slate-300">&bull;</span>
            <span>Class: <strong class="text-slate-800 font-bold">{{ $student->currentEnrollment?->class?->name ?? '—' }}</strong> ({{ $student->currentEnrollment?->section?->name ?? 'A' }})</span>
            <span class="text-slate-300">&bull;</span>
            <span>Submitted: <strong class="text-slate-800 font-semibold">{{ $student->created_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</strong></span>
          </div>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center gap-2 shrink-0">
        <button onclick="window.print()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200/90 shadow-2xs transition hover:border-slate-300">
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          <span>Print Slip</span>
        </button>

        <a href="{{ route('admissions.approvals') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200/90 shadow-2xs transition hover:border-slate-300">
          <svg class="w-4 h-4 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <span>Approval Queue</span>
        </a>

        <a href="{{ route('admissions.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-[#8C2826] to-[#A13431] hover:from-[#731E1C] hover:to-[#8C2826] text-white font-bold text-xs shadow-sm shadow-[#8C2826]/25 transition hover:shadow-md">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          <span>New Admission</span>
        </a>
      </div>
    </div>
  </div>

  {{-- ── 3-Stage Approval Progress Tracker (Executive Design) ─────────── --}}
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4 print:hidden">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1 border-b border-slate-100">
      <div class="flex items-center gap-2.5">
        <span class="w-2.5 h-2.5 rounded-full bg-[#8C2826] shadow-sm shadow-[#8C2826]/40"></span>
        <h2 class="text-xs font-black text-slate-900 uppercase tracking-wider">Admission Approval Pipeline</h2>
      </div>
      <div>
        @if($isStage3Done)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            Admission Finalized &amp; Active
          </span>
        @elseif($isStage2Done)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
            Stage 3 of 3: Admin Final Confirmation
          </span>
        @else
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
            Stage 2 of 3: Principal Review Pending
          </span>
        @endif
      </div>
    </div>

    {{-- Stepper Flow Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pt-1">

      {{-- Step 1: Admin Desk Submission --}}
      <div class="relative p-4 rounded-2xl bg-gradient-to-br from-emerald-50/90 to-emerald-50/40 border border-emerald-200/90 flex items-start gap-3.5 transition">
        <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-sm shadow-emerald-600/30">
          <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="space-y-1 min-w-0 flex-1">
          <div class="flex items-center justify-between gap-1">
            <p class="text-xs font-black text-emerald-950">1. Admin Desk Submission</p>
            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full">DONE</span>
          </div>
          <p class="text-[11.5px] text-emerald-800/90 leading-tight">Data, photos &amp; fees logged</p>
          <p class="text-[10.5px] font-semibold text-emerald-700 flex items-center gap-1 pt-0.5">
            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Completed {{ $student->created_at?->format('d M, h:i A') ?? now()->format('d M, h:i A') }}
          </p>
        </div>
      </div>

      {{-- Step 2: Principal Review --}}
      @if($isStage2Done)
        {{-- Completed --}}
        <div class="relative p-4 rounded-2xl bg-gradient-to-br from-emerald-50/90 to-emerald-50/40 border border-emerald-200/90 flex items-start gap-3.5 transition">
          <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-sm shadow-emerald-600/30">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div class="space-y-1 min-w-0 flex-1">
            <div class="flex items-center justify-between gap-1">
              <p class="text-xs font-black text-emerald-950">2. Principal Review</p>
              <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full">APPROVED</span>
            </div>
            <p class="text-[11.5px] text-emerald-800/90 leading-tight">Endorsement complete</p>
            <p class="text-[10.5px] font-semibold text-emerald-700 flex items-center gap-1 pt-0.5">
              <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Signed {{ $student->principal_approved_at?->format('d M, h:i A') ?? 'Confirmed' }}
            </p>
          </div>
        </div>
      @else
        {{-- Pending Stage 2 (Current) --}}
        <div class="relative p-4 rounded-2xl bg-gradient-to-br from-[#FFF5F5] via-amber-50/30 to-white border-2 border-[#8C2826]/30 flex items-start gap-3.5 shadow-sm overflow-hidden">
          <div class="absolute top-0 right-0 w-24 h-24 bg-[#8C2826]/5 rounded-full blur-xl pointer-events-none"></div>
          <div class="w-9 h-9 rounded-xl bg-[#8C2826] text-white flex items-center justify-center font-black text-sm shrink-0 shadow-sm shadow-[#8C2826]/30 relative">
            <svg class="w-4.5 h-4.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="space-y-1 min-w-0 flex-1 relative">
            <div class="flex items-center justify-between gap-1">
              <p class="text-xs font-black text-[#5C1210]">2. Principal Review</p>
              <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-[#8C2826] bg-[#8C2826]/10 px-2 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-[#8C2826] animate-ping"></span>
                ACTIVE STEP
              </span>
            </div>
            <p class="text-[11.5px] text-[#8C2826] font-medium leading-tight">Pending Principal endorsement</p>
            <div class="pt-0.5">
              <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-[#731E1C] bg-[#FFF5F5] px-2 py-0.5 rounded-md border border-[#FECACA]/60">
                <svg class="w-3 h-3 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Sent to principal@schoolerp.in
              </span>
            </div>
          </div>
        </div>
      @endif

      {{-- Step 3: Admin Final Confirmation --}}
      @if($isStage3Done)
        {{-- Completed --}}
        <div class="relative p-4 rounded-2xl bg-gradient-to-br from-emerald-50/90 to-emerald-50/40 border border-emerald-200/90 flex items-start gap-3.5 transition">
          <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-sm shadow-emerald-600/30">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div class="space-y-1 min-w-0 flex-1">
            <div class="flex items-center justify-between gap-1">
              <p class="text-xs font-black text-emerald-950">3. Admin Final Confirmation</p>
              <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full">ACTIVE</span>
            </div>
            <p class="text-[11.5px] text-emerald-800/90 leading-tight">Student ID Card &amp; TC active</p>
            <p class="text-[10.5px] font-semibold text-emerald-700 flex items-center gap-1 pt-0.5">
              <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Enrollment fully activated
            </p>
          </div>
        </div>
      @elseif($isStage3Active)
        {{-- Step 3 is ready to be acted on --}}
        <div class="relative p-4 rounded-2xl bg-indigo-50/60 border-2 border-indigo-300 flex items-start gap-3.5 shadow-sm">
          <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-sm shadow-indigo-600/30">
            3
          </div>
          <div class="space-y-1 min-w-0 flex-1">
            <div class="flex items-center justify-between gap-1">
              <p class="text-xs font-black text-indigo-950">3. Admin Final Confirmation</p>
              <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">READY</span>
            </div>
            <p class="text-[11.5px] text-indigo-800 leading-tight">Ready for final activation</p>
            <p class="text-[10.5px] font-semibold text-indigo-700 pt-0.5">Awaiting admin final seal</p>
          </div>
        </div>
      @else
        {{-- Step 3 Locked --}}
        <div class="relative p-4 rounded-2xl bg-slate-50/80 border border-slate-200 flex items-start gap-3.5 opacity-75">
          <div class="w-9 h-9 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm shrink-0">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div class="space-y-1 min-w-0 flex-1">
            <div class="flex items-center justify-between gap-1">
              <p class="text-xs font-black text-slate-700">3. Admin Final Confirmation</p>
              <span class="text-[10px] font-semibold text-slate-400 bg-slate-200/60 px-2 py-0.5 rounded-full">LOCKED</span>
            </div>
            <p class="text-[11.5px] text-slate-500 leading-tight">Activates ID Card, TC &amp; Directory</p>
            <p class="text-[10.5px] text-slate-400 pt-0.5 flex items-center gap-1">
              <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Unlocks once Step 2 is approved
            </p>
          </div>
        </div>
      @endif

    </div>
  </div>

  {{-- ── 2 Columns: Applicant Dossier + Home QR Document Slip ──────────── --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    {{-- Left: Student & Parent Dossier (7 cols) --}}
    <div class="lg:col-span-7 space-y-6">

      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 space-y-6">

        {{-- Student Profile Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-5 pb-5 border-b border-slate-100">
          {{-- Avatar --}}
          <div class="relative w-20 h-20 sm:w-22 sm:h-22 rounded-2xl bg-slate-100 border-2 border-slate-200/80 shadow-2xs overflow-hidden shrink-0 group">
            @if($student->photo)
              <img src="{{ asset('storage/' . $student->photo) }}" class="w-full h-full object-cover transition duration-300 group-hover:scale-105" alt="{{ $student->full_name }}">
            @else
              <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-[#FFF5F5] to-[#FFEBEB] text-[#8C2826] font-black text-2xl">
                {{ strtoupper(substr($student->first_name ?: 'S', 0, 1)) }}
              </div>
            @endif
            <div class="absolute bottom-1 right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full" title="Student active in pipeline"></div>
          </div>

          {{-- Name & Badges --}}
          <div class="min-w-0 flex-1 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight capitalize truncate">
                {{ ucwords(strtolower($student->full_name)) }}
              </h2>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA]">
                Class {{ $student->currentEnrollment?->class?->name ?? '—' }} ({{ $student->currentEnrollment?->section?->name ?? 'A' }})
              </span>
            </div>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
              <span class="inline-flex items-center gap-1 font-mono font-bold text-[#8C2826] bg-[#8C2826]/5 px-2 py-0.5 rounded-md">
                <svg class="w-3.5 h-3.5 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                {{ $student->admission_no }}
              </span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-600 font-medium">Roll: <strong class="text-slate-800">{{ $student->roll_number ?? 'Auto-Allocated' }}</strong></span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-600 font-medium">Gender: <strong class="text-slate-800">{{ ucfirst($student->gender ?? '—') }}</strong></span>
              @if($student->dob)
                <span class="text-slate-300">•</span>
                <span class="text-slate-600 font-medium">DOB: <strong class="text-slate-800">{{ $student->dob->format('d M Y') }}</strong></span>
              @endif
            </div>
          </div>
        </div>

        {{-- Parents & Guardian Contacts --}}
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              Parent &amp; Guardian Contacts
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            {{-- Father --}}
            <div class="p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/80 hover:border-slate-300 transition flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200/80 overflow-hidden shrink-0 flex items-center justify-center text-blue-600 shadow-2xs">
                @if($student->father_photo)
                  <img src="{{ asset('storage/' . $student->father_photo) }}" class="w-full h-full object-cover" alt="Father">
                @else
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                @endif
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-[10px] uppercase font-black text-blue-700 tracking-wider block">Father</span>
                <p class="font-bold text-slate-800 text-xs truncate capitalize">{{ $student->father_name ?? $student->parent_name ?? '—' }}</p>
                <p class="text-slate-500 font-medium text-[11px] font-mono mt-0.5">{{ $student->father_mobile ?? $student->parent_mobile ?? '—' }}</p>

                @if($fatherMobileClean)
                  <div class="flex items-center gap-2 mt-2 pt-1 border-t border-slate-200/60">
                    <a href="tel:{{ $fatherMobileClean }}"
                       class="inline-flex items-center gap-1 text-[10.5px] font-bold text-slate-600 hover:text-blue-600 transition">
                      <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                      Call
                    </a>
                    <span class="text-slate-300">•</span>
                    <a href="https://wa.me/91{{ $fatherMobileClean }}" target="_blank"
                       class="inline-flex items-center gap-1 text-[10.5px] font-bold text-emerald-600 hover:text-emerald-700 transition">
                      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                      WhatsApp
                    </a>
                  </div>
                @endif
              </div>
            </div>

            {{-- Mother --}}
            <div class="p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/80 hover:border-slate-300 transition flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200/80 overflow-hidden shrink-0 flex items-center justify-center text-rose-500 shadow-2xs">
                @if($student->mother_photo)
                  <img src="{{ asset('storage/' . $student->mother_photo) }}" class="w-full h-full object-cover" alt="Mother">
                @else
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                @endif
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-[10px] uppercase font-black text-rose-700 tracking-wider block">Mother</span>
                <p class="font-bold text-slate-800 text-xs truncate capitalize">{{ $student->mother_name ?? '—' }}</p>
                <p class="text-slate-500 font-medium text-[11px] font-mono mt-0.5">{{ $student->mother_mobile ?? '—' }}</p>

                @if($motherMobileClean)
                  <div class="flex items-center gap-2 mt-2 pt-1 border-t border-slate-200/60">
                    <a href="tel:{{ $motherMobileClean }}"
                       class="inline-flex items-center gap-1 text-[10.5px] font-bold text-slate-600 hover:text-rose-600 transition">
                      <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                      Call
                    </a>
                    <span class="text-slate-300">•</span>
                    <a href="https://wa.me/91{{ $motherMobileClean }}" target="_blank"
                       class="inline-flex items-center gap-1 text-[10.5px] font-bold text-emerald-600 hover:text-emerald-700 transition">
                      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                      WhatsApp
                    </a>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- ── Elevated Fee Summary Card (Modern & Clean) ────────────── --}}
        <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-slate-850 to-slate-900 text-white p-5 shadow-sm border border-slate-800 space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-amber-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              </div>
              <div>
                <span class="text-xs font-bold text-white tracking-wide block">Admission Fee Status</span>
                <span class="text-[10px] text-slate-400">Mode: {{ ucfirst(str_replace('_', ' ', $student->payment_mode ?? 'Offline Desk')) }}</span>
              </div>
            </div>

            @if($pendingFee <= 0)
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                Fully Paid
              </span>
            @elseif($paidFee > 0)
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                Partially Paid ({{ $paidPct }}%)
              </span>
            @else
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                Payment Pending
              </span>
            @endif
          </div>

          {{-- 3 Key Financial Metrics --}}
          <div class="grid grid-cols-3 gap-2 pt-1 border-t border-white/10">
            <div class="space-y-0.5">
              <span class="text-[10.5px] text-slate-400 uppercase font-semibold tracking-wider block">Total Billed</span>
              <p class="font-extrabold text-sm sm:text-base text-white tabular-nums">
                ₹{{ number_format($totalFee, 2) }}
              </p>
            </div>

            <div class="space-y-0.5 border-l border-white/10 pl-3">
              <span class="text-[10.5px] text-emerald-400 uppercase font-semibold tracking-wider block">Collected</span>
              <p class="font-extrabold text-sm sm:text-base text-emerald-400 tabular-nums">
                ₹{{ number_format($paidFee, 2) }}
              </p>
            </div>

            <div class="space-y-0.5 border-l border-white/10 pl-3">
              <span class="text-[10.5px] text-amber-300 uppercase font-semibold tracking-wider block">Remaining Due</span>
              <p class="font-extrabold text-sm sm:text-base {{ $pendingFee > 0 ? 'text-rose-300' : 'text-emerald-400' }} tabular-nums">
                ₹{{ number_format($pendingFee, 2) }}
              </p>
            </div>
          </div>

          {{-- Progress Bar --}}
          <div class="space-y-1.5 pt-1">
            <div class="flex justify-between items-center text-[10px] font-semibold text-slate-400">
              <span>Collection Progress</span>
              <span class="text-white">{{ $paidPct }}% Collected</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden p-0.5 border border-white/5">
              <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500" style="width: {{ $paidPct }}%"></div>
            </div>
          </div>
        </div>

        {{-- Action Links --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
          <a href="{{ route('students.visitor-card', $student->id) }}"
             class="py-2.5 px-4 rounded-xl bg-[#FFF5F5] hover:bg-[#FFEBEB] text-[#8C2826] font-bold text-xs border border-[#FECACA] shadow-2xs transition flex items-center justify-center gap-2 group">
            <svg class="w-4 h-4 text-[#8C2826] transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span>Parent Visitor Card</span>
          </a>

          <a href="{{ route('students.show', $student->id) }}"
             class="py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-200 shadow-2xs transition flex items-center justify-center gap-2 group">
            <svg class="w-4 h-4 text-slate-600 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>View Student Record</span>
          </a>
        </div>

      </div>

    </div>

    {{-- Right: Parent Take-Home Document Upload Slip (5 cols) ─────────── --}}
    <div class="lg:col-span-5 space-y-4">
      <div class="relative bg-gradient-to-br from-slate-950 via-[#181024] to-[#0f172a] text-white p-6 sm:p-7 rounded-3xl shadow-xl border border-indigo-900/40 space-y-5 overflow-hidden">
        {{-- Subtle ambient glow --}}
        <div class="absolute -top-12 -right-12 w-44 h-44 bg-indigo-500/15 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-44 h-44 bg-[#8C2826]/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative text-center space-y-2">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] font-black uppercase tracking-wider bg-amber-400 text-slate-950 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Parent Take-Home Slip
          </div>

          <h3 class="text-xl font-black tracking-tight text-white">
            Scan QR Code &amp; Upload Documents
          </h3>

          <p class="text-xs text-indigo-150 leading-relaxed text-slate-300 max-w-sm mx-auto">
            Give this slip to the parent. They can scan the QR code with their mobile phone camera at home anytime to upload pending certificates!
          </p>
        </div>

        {{-- QR Code Viewfinder Card --}}
        <div class="relative bg-white p-5 rounded-2xl shadow-xl mx-auto w-fit flex flex-col items-center justify-center text-slate-900 group">
          {{-- Corner Viewfinder Accents --}}
          <div class="absolute top-2 left-2 w-3.5 h-3.5 border-t-2 border-l-2 border-indigo-600 rounded-tl-sm pointer-events-none"></div>
          <div class="absolute top-2 right-2 w-3.5 h-3.5 border-t-2 border-r-2 border-indigo-600 rounded-tr-sm pointer-events-none"></div>
          <div class="absolute bottom-2 left-2 w-3.5 h-3.5 border-b-2 border-l-2 border-indigo-600 rounded-bl-sm pointer-events-none"></div>
          <div class="absolute bottom-2 right-2 w-3.5 h-3.5 border-b-2 border-r-2 border-indigo-600 rounded-br-sm pointer-events-none"></div>

          <div class="w-44 h-44 flex items-center justify-center p-1 bg-white">
            {!! $docUploadQrSvg !!}
          </div>

          <div class="mt-2 text-center">
            <p class="text-[10px] font-black tracking-widest text-indigo-900 uppercase flex items-center justify-center gap-1">
              <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Scan with Mobile Camera
            </p>
            <p class="text-[9.5px] font-medium text-slate-400">No App Download Required</p>
          </div>
        </div>

        {{-- 3 Step Parent Guide --}}
        <div class="grid grid-cols-3 gap-2 py-2 px-1 text-center border-t border-b border-white/10 text-[10.5px]">
          <div class="space-y-0.5">
            <span class="w-5 h-5 rounded-full bg-white/10 text-white font-bold inline-flex items-center justify-center text-[10px] mb-1">1</span>
            <p class="font-bold text-slate-200">Scan QR</p>
            <p class="text-[9px] text-slate-400 leading-tight">Using phone camera</p>
          </div>
          <div class="space-y-0.5">
            <span class="w-5 h-5 rounded-full bg-white/10 text-white font-bold inline-flex items-center justify-center text-[10px] mb-1">2</span>
            <p class="font-bold text-slate-200">Snap Photos</p>
            <p class="text-[9px] text-slate-400 leading-tight">Birth cert, TC, etc.</p>
          </div>
          <div class="space-y-0.5">
            <span class="w-5 h-5 rounded-full bg-white/10 text-white font-bold inline-flex items-center justify-center text-[10px] mb-1">3</span>
            <p class="font-bold text-slate-200">Auto-Synced</p>
            <p class="text-[9px] text-slate-400 leading-tight">Linked to admission</p>
          </div>
        </div>

        {{-- WhatsApp & Actions --}}
        <div class="space-y-2.5 pt-1" x-data="{ copied: false }">

          {{-- WhatsApp Link --}}
          <a href="https://api.whatsapp.com/send?text={{ $waDocText }}" target="_blank"
             class="w-full py-2.5 px-4 rounded-xl bg-[#25D366] hover:bg-[#20be5a] text-white font-bold text-xs shadow-md shadow-[#25D366]/20 transition flex items-center justify-center gap-2 cursor-pointer group">
            <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            <span>Send Upload Link via WhatsApp</span>
          </a>

          {{-- Copy Upload Link with Interactive Toast --}}
          <button @click="navigator.clipboard.writeText('{{ $docPortalUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
                  class="w-full py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold transition border border-white/15 cursor-pointer flex items-center justify-center gap-1.5">
            <template x-if="!copied">
              <span class="inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                <span>Copy Upload Link</span>
              </span>
            </template>
            <template x-if="copied">
              <span class="inline-flex items-center gap-1.5 text-emerald-400 font-bold">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Link Copied to Clipboard!</span>
              </span>
            </template>
          </button>
        </div>

        <div class="text-center pt-1">
          <p class="text-[10px] text-slate-400 flex items-center justify-center gap-1">
            <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            256-Bit Encrypted Secure Student Upload Portal
          </p>
        </div>

      </div>
    </div>

  </div>

  {{-- ── Official Print Layout (Only triggers when window.print() is called) ── --}}
  <div class="hidden print:block font-sans text-slate-900 bg-white p-6 max-w-2xl mx-auto border border-slate-300 rounded-xl space-y-6">
    <div class="flex items-center justify-between pb-4 border-b-2 border-slate-800">
      <div>
        <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Erode Public School (CBSE)</h2>
        <p class="text-xs text-slate-600 font-medium">Admission Acknowledgement &amp; Parent Document Pass</p>
        <p class="text-[10px] text-slate-500 font-mono mt-0.5">Reference: {{ $student->admission_no }} &bull; Date: {{ now()->format('d M Y') }}</p>
      </div>
      <div class="text-right">
        <span class="inline-block border border-slate-900 px-3 py-1 font-mono font-bold text-sm">
          {{ $student->admission_no }}
        </span>
      </div>
    </div>

    {{-- Student & Parent Print Block --}}
    <div class="grid grid-cols-2 gap-4 text-xs">
      <div class="space-y-1">
        <p class="text-[10px] uppercase font-bold text-slate-500">Student Details</p>
        <p class="font-black text-sm text-slate-900">{{ $student->full_name }}</p>
        <p><span class="text-slate-500">Class:</span> <strong>{{ $student->currentEnrollment?->class?->name ?? '—' }} ({{ $student->currentEnrollment?->section?->name ?? 'A' }})</strong></p>
        <p><span class="text-slate-500">Roll No:</span> <strong>{{ $student->roll_number ?? 'Auto-Allocated' }}</strong></p>
        <p><span class="text-slate-500">Gender:</span> <strong>{{ ucfirst($student->gender ?? '—') }}</strong></p>
      </div>
      <div class="space-y-1">
        <p class="text-[10px] uppercase font-bold text-slate-500">Parents &amp; Contact</p>
        <p><span class="text-slate-500">Father:</span> <strong>{{ $student->father_name ?? $student->parent_name ?? '—' }}</strong> ({{ $student->father_mobile ?? $student->parent_mobile ?? '—' }})</p>
        <p><span class="text-slate-500">Mother:</span> <strong>{{ $student->mother_name ?? '—' }}</strong> ({{ $student->mother_mobile ?? '—' }})</p>
        <p><span class="text-slate-500">Status:</span> <strong>{{ ucfirst(str_replace('_', ' ', $student->status ?? 'Submitted')) }}</strong></p>
      </div>
    </div>

    {{-- Fee Print Table --}}
    <div class="border border-slate-300 rounded-lg overflow-hidden text-xs">
      <div class="bg-slate-100 font-bold px-3 py-2 border-b border-slate-300 flex justify-between">
        <span>Fee Component</span>
        <span>Amount (INR)</span>
      </div>
      <div class="p-3 space-y-1.5">
        <div class="flex justify-between">
          <span class="text-slate-600">Total Admission Fee Billed:</span>
          <span class="font-bold">₹{{ number_format($totalFee, 2) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-emerald-700 font-medium">Initial Amount Collected:</span>
          <span class="font-bold text-emerald-700">₹{{ number_format($paidFee, 2) }}</span>
        </div>
        <div class="flex justify-between pt-1 border-t border-slate-200">
          <span class="text-slate-800 font-bold">Remaining Balance Due:</span>
          <span class="font-bold text-rose-700">₹{{ number_format($pendingFee, 2) }}</span>
        </div>
      </div>
    </div>

    {{-- QR Pass Print Strip --}}
    <div class="border-2 border-dashed border-slate-400 p-4 rounded-xl flex items-center gap-5">
      <div class="w-28 h-28 shrink-0 flex items-center justify-center p-1 border border-slate-200">
        {!! $docUploadQrSvg !!}
      </div>
      <div class="space-y-1 text-xs">
        <p class="font-black text-sm text-slate-900 uppercase">Parent Document Upload Pass</p>
        <p class="text-slate-600 leading-snug">
          Please scan this QR code using any smartphone camera at home to upload pending certificates (Birth Certificate, Aadhaar, TC, Community Certificate).
        </p>
        <p class="text-[10px] font-mono text-slate-500 pt-1">
          Direct Link: {{ $docPortalUrl }}
        </p>
      </div>
    </div>

    {{-- Signature footer --}}
    <div class="flex justify-between pt-8 text-xs text-slate-600">
      <div class="text-center w-36 border-t border-slate-400 pt-1">
        <p class="font-semibold">Parent's Signature</p>
      </div>
      <div class="text-center w-36 border-t border-slate-400 pt-1">
        <p class="font-semibold">Admission Desk</p>
      </div>
      <div class="text-center w-36 border-t border-slate-400 pt-1">
        <p class="font-semibold">Principal / Authorised</p>
      </div>
    </div>
  </div>

</div>
@endsection
