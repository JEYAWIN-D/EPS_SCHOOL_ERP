@extends('layouts.app')

@section('title', $student->full_name)

@section('content')
<div class="max-w-[1400px] mx-auto space-y-6 pb-16">

  {{-- Top Action Bar --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <a href="{{ route('students.index') }}" class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-xs hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-all">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Student Profile</h2>
        <h1 class="text-xl font-bold text-slate-900">{{ $student->full_name }}</h1>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('public.student.documents.print-card', $student->document_token) }}" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs text-indigo-700 bg-indigo-50/80 border-indigo-200 hover:bg-indigo-100" title="Print Document Submission QR Slip">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        QR Slip
      </a>
      <a href="{{ route('students.visitor-card', $student->id) }}" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs text-purple-700 bg-purple-50/80 border-purple-200 hover:bg-purple-100" title="Official Parent & Guardian Campus Visitor Escort Card">
        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
        Visitor Pass
      </a>
      @if($student->sibling_group_id)
        <a href="{{ route('students.siblings', $student->id) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          Siblings
        </a>
      @endif

      @if($student->status === 'active')
        <a href="{{ route('students.id-card.single', $student->id) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs bg-[#892528]/10 text-[#892528] border-[#892528]/30 hover:bg-[#892528]/20 font-bold" title="Open Student ID Card Studio">
          <svg class="w-4 h-4 text-[#892528]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
          ID Card Studio
        </a>
      @else
        <button type="button" disabled class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs opacity-60 cursor-not-allowed bg-slate-100 text-slate-400" title="ID Card is locked until admission is fully approved and confirmed">
          <i class="fas fa-lock text-xs text-amber-500"></i>
          ID Card (Locked)
        </button>
      @endif

      <a href="{{ route('fees.student-statement', ['student_id' => $student->id]) }}" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs text-slate-700 hover:text-indigo-600">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Fee Statement
      </a>

      <a href="{{ route('fees.collect', ['student_id' => $student->id]) }}" class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-xs bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Payment Received
      </a>

      @if($student->status === 'active')
        <a href="{{ route('students.tc.form', $student->id) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs">
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          TC
        </a>
      @else
        <button type="button" disabled class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-xs opacity-60 cursor-not-allowed bg-slate-100 text-slate-400" title="TC is locked until student admission is fully approved and confirmed">
          <i class="fas fa-lock text-xs text-amber-500"></i>
          TC (Locked)
        </button>
      @endif

      @if($student->status === 'active')
        <button x-data @click="$dispatch('open-modal','mark-left-{{ $student->id }}')" class="btn btn-secondary btn-sm text-amber-600 hover:text-amber-700 shadow-xs">
          Mark as Left
        </button>
      @elseif($student->status === 'left')
        <span class="text-xs text-slate-500 font-medium px-3 py-1 bg-slate-100 rounded-lg">Left: {{ $student->leaving_date?->format('d M Y') }}</span>
      @endif
      <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Profile
      </a>
    </div>
  </div>

  {{-- ── 2-Tier Admission Approval Status Banner ──────────────────────── --}}
  @if($student->status === 'pending_principal')
    <div class="bg-gradient-to-r from-amber-500/10 via-amber-50 to-amber-100/40 border-2 border-amber-300 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
          <i class="fas fa-user-clock text-xl"></i>
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-base font-extrabold text-amber-950">Awaiting Principal Approval</h3>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-200 text-amber-900 border border-amber-300">Tier 1 Verification Pending</span>
          </div>
          <p class="text-xs text-amber-800/90 mt-1 max-w-2xl leading-relaxed">
            This student's admission has been submitted and is waiting for institutional review by the <strong>Principal</strong> (<code>principal@schoolerp.in</code>). ID Cards and Transfer Certificates remain locked until final admin confirmation.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0 w-full md:w-auto justify-end">
        @if(auth()->user()->hasAnyRole(['principal', 'super_admin', 'admin']) || auth()->user()->can('approve admissions'))
          <form action="{{ route('admissions.principal-approve', $student->id) }}" method="POST" onsubmit="return confirm('Approve this admission application as Principal?');">
            @csrf
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs shadow-sm hover:shadow transition flex items-center gap-2">
              <i class="fas fa-check-double"></i>
              Approve as Principal
            </button>
          </form>
        @endif
        <a href="{{ route('admissions.approvals') }}" class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-amber-50 text-amber-900 border border-amber-300 font-bold text-xs transition flex items-center gap-1.5">
          <i class="fas fa-tasks text-amber-600"></i>
          Approvals Desk
        </a>
      </div>
    </div>
  @elseif($student->status === 'principal_approved')
    <div class="bg-gradient-to-r from-blue-500/10 via-blue-50 to-indigo-100/40 border-2 border-blue-300 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
          <i class="fas fa-stamp text-xl"></i>
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-base font-extrabold text-blue-950">Principal Approved — Final Admin Confirmation Pending</h3>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-200 text-blue-900 border border-blue-300">Tier 2 Confirmation</span>
          </div>
          <p class="text-xs text-blue-800/90 mt-1 max-w-2xl leading-relaxed">
            Approved by <strong>{{ $student->principalApprover?->name ?? 'Principal' }}</strong> on {{ $student->principal_approved_at?->format('d M Y, h:i A') ?? 'Recent' }}.
            Admin confirmation will permanently activate the student register, enroll class records, and unlock ID card generation.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0 w-full md:w-auto justify-end">
        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']) || auth()->user()->can('approve admissions'))
          <form action="{{ route('admissions.admin-confirm', $student->id) }}" method="POST" onsubmit="return confirm('Confirm and finalize this student admission onto the active school register?');">
            @csrf
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs shadow-sm hover:shadow transition flex items-center gap-2">
              <i class="fas fa-user-check"></i>
              Confirm &amp; Finalize Admission
            </button>
          </form>
        @endif
        <a href="{{ route('admissions.approvals', ['tab' => 'admin']) }}" class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-blue-50 text-blue-900 border border-blue-300 font-bold text-xs transition flex items-center gap-1.5">
          <i class="fas fa-tasks text-blue-600"></i>
          Approvals Desk
        </a>
      </div>
    </div>
  @elseif($student->status === 'rejected')
    <div class="bg-gradient-to-r from-rose-500/10 via-rose-50 to-rose-100/40 border-2 border-rose-300 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
          <i class="fas fa-times-circle text-xl"></i>
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-base font-extrabold text-rose-950">Application Rejected</h3>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-200 text-rose-900 border border-rose-300">Admission Declined</span>
          </div>
          <p class="text-xs text-rose-800/90 mt-1 max-w-2xl leading-relaxed">
            Reason: <strong>{{ $student->rejection_reason ?? 'Not specified' }}</strong>
            @if($student->rejectedByUser)
              &bull; Rejected by {{ $student->rejectedByUser->name }} on {{ $student->rejected_at?->format('d M Y') }}
            @endif
          </p>
        </div>
      </div>
      <a href="{{ route('admissions.approvals', ['tab' => 'rejected']) }}" class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-rose-50 text-rose-900 border border-rose-300 font-bold text-xs transition flex items-center gap-1.5">
        View All Declined
      </a>
    </div>
  @endif

  {{-- Clean White Header Profile Card --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
      {{-- Avatar --}}
      <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shrink-0 overflow-hidden shadow-md ring-4 ring-slate-100 relative">
        @if($student->photo)
          <img src="{{ asset('storage/'.$student->photo) }}" class="w-full h-full object-cover" alt="{{ $student->full_name }}" onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
          <span class="hidden text-white text-3xl font-extrabold tracking-wider">{{ strtoupper(substr($student->first_name,0,1) . substr($student->last_name,0,1)) }}</span>
        @else
          <span class="text-white text-3xl font-extrabold tracking-wider">{{ strtoupper(substr($student->first_name,0,1) . substr($student->last_name,0,1)) }}</span>
        @endif
      </div>

      {{-- Profile Info --}}
      <div class="flex-1 text-center md:text-left space-y-2.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="flex items-center justify-center md:justify-start gap-3 flex-wrap">
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $student->full_name }}</h1>
              @php
                $statusBadgeClass = match($student->status) {
                  'active' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                  'pending_principal' => 'bg-amber-100 text-amber-800 border-amber-300',
                  'principal_approved' => 'bg-blue-100 text-blue-800 border-blue-300',
                  'rejected' => 'bg-rose-100 text-rose-800 border-rose-300',
                  default => 'bg-slate-100 text-slate-700 border-slate-300'
                };
                $statusDotClass = match($student->status) {
                  'active' => 'bg-emerald-500',
                  'pending_principal' => 'bg-amber-500 animate-ping',
                  'principal_approved' => 'bg-blue-500',
                  'rejected' => 'bg-rose-500',
                  default => 'bg-slate-500'
                };
                $statusLabel = match($student->status) {
                  'pending_principal' => 'Pending Principal Approval',
                  'principal_approved' => 'Principal Approved (Awaiting Admin)',
                  'active' => 'Active',
                  'rejected' => 'Rejected',
                  default => ucfirst($student->status)
                };
              @endphp
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border {{ $statusBadgeClass }}">
                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $statusDotClass }}"></span>
                {{ $statusLabel }}
              </span>
            </div>
            <p class="text-sm font-semibold font-mono text-indigo-600 mt-1">Admission No: <span class="bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 text-indigo-800 font-bold">{{ $student->admission_number }}</span></p>
          </div>
        </div>

        <p class="text-base text-slate-600 font-medium">
          <span class="font-bold text-slate-900">{{ $student->currentEnrollment?->class?->name ?? 'Unassigned' }}</span>
          @if($student->currentEnrollment?->section)
            &bull; <span class="text-slate-700 font-semibold">Section {{ $student->currentEnrollment->section->name }}</span>
          @endif
          @if($student->roll_number ?? $student->currentEnrollment?->roll_number)
            &bull; <span class="font-mono text-slate-900 font-bold">Roll No: {{ $student->roll_number ?? $student->currentEnrollment?->roll_number }}</span>
          @endif
        </p>

        {{-- Badges --}}
        <div class="flex items-center justify-center md:justify-start gap-2.5 flex-wrap pt-1">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            {{ str_replace('_',' ', ucfirst($student->student_type ?? 'Day Scholar')) }}
          </span>

          @if($student->emis_no)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            EMIS: {{ $student->emis_no }}
          </span>
          @endif

          @if($student->is_asp)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
            🕒 ASP Enrolled (₹{{ number_format($student->asp_fee ?? 0) }})
          </span>
          @endif

          @if($student->transport_route_id)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
            🚌 Bus: {{ $student->transportRoute?->name ?? 'Route #' . $student->transport_route_id }} @if($student->transportStop) ({{ $student->transportStop->name }}) @endif
          </span>
          @endif

          @if($student->concession_type && $student->concession_amount > 0)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
            🏷️ Concession: {{ ucwords(str_replace('_', ' ', $student->concession_type)) }} (-₹{{ number_format($student->concession_amount) }})
          </span>
          @endif

          @if($student->blood_group)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.428.583L2.428 17.428a2 2 0 000 2.828l1.428 1.428a2 2 0 002.828 0l1.428-1.428a2 2 0 00.583-1.428l-.477-2.387a6 6 0 01.517-3.86l.158-.318a6 6 0 00.517-3.86L9.12 5.6a2 2 0 01.583-1.428l1.428-1.428a2 2 0 012.828 0l1.428 1.428a2 2 0 010 2.828l-1.428 1.428a2 2 0 00-.583 1.428l.477 2.387a6 6 0 00-.517 3.86l-.158.318a6 6 0 01-.517 3.86l.477 2.387a2 2 0 001.428.583l1.428-1.428a2 2 0 000-2.828l-1.428-1.428z"/></svg>
            Blood Group: {{ $student->blood_group }}
          </span>
          @endif

          @if($student->currentEnrollment?->house)
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            House: {{ $student->currentEnrollment->house }}
          </span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Quick Info Statistic Cards (4 Columns) --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    {{-- Card 1: Attendance Rate --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex items-center gap-4 hover:shadow-md transition-all duration-200">
      <div class="w-12 h-12 rounded-xl {{ ($attPct ?? 0) >= 75 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600' }} border flex items-center justify-center shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Attendance Rate</p>
        <div class="flex items-baseline gap-1.5 mt-0.5">
          <p class="text-xl font-black {{ ($attPct ?? 0) >= 75 ? 'text-emerald-600' : 'text-rose-600' }}">
            {{ $attPct !== null ? $attPct . '%' : '—' }}
          </p>
          @if($attTotal > 0)
            <span class="text-[10px] text-slate-400 font-bold">({{ $attPresent }}/{{ $attTotal }}d)</span>
          @endif
        </div>
      </div>
    </div>

    {{-- Card 2: Fee Balance Due --}}
    <div id="stat-fee-card" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex items-center gap-4 hover:shadow-md transition-all duration-200">
      <div id="stat-fee-icon-container" class="w-12 h-12 rounded-xl {{ $feeBalance > 0 ? 'bg-rose-50 border-rose-100 text-rose-600' : 'bg-emerald-50 border-emerald-100 text-emerald-600' }} border flex items-center justify-center shrink-0 transition-colors duration-300">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fee Balance</p>
        <p id="stat-fee-balance-val" class="text-xl font-black {{ $feeBalance > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-0.5 transition-colors duration-300">
          {{ $feeBalance > 0 ? '₹' . number_format($feeBalance) : 'Fully Paid' }}
        </p>
      </div>
    </div>

    {{-- Card 3: Date of Birth & Age --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex items-center gap-4 hover:shadow-md transition-all duration-200">
      <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Date of Birth</p>
        <p class="text-base font-extrabold text-slate-900 mt-0.5">{{ $student->dob?->format('d M Y') ?? '—' }}</p>
      </div>
    </div>

    {{-- Card 4: Academic Year --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex items-center gap-4 hover:shadow-md transition-all duration-200">
      <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Academic Year</p>
        <p class="text-base font-extrabold text-slate-900 mt-0.5">{{ $student->currentEnrollment?->academicYear?->name ?? '2025-2026' }}</p>
      </div>
    </div>
  </div>

  {{-- ========================================================================= --}}
  {{-- 1. TOP SECTION: CORE STUDENT PROFILE & ACTION SIDEBAR (BALANCED 2:1)       --}}
  {{-- ========================================================================= --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

    {{-- Left Column (2 Columns Wide): Core Personal, Family, & Address Details --}}
    <div class="lg:col-span-2 space-y-6">

      {{-- Personal Information Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
          <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Personal Information</h3>
            <p class="text-xs text-slate-400">Complete demographic and personal details</p>
          </div>
        </div>

        {{-- 3 Columns Desktop Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">

          {{-- Date of Birth Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Date of Birth</span>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $student->dob?->format('d M Y') ?? '—' }}</p>
          </div>

          {{-- Gender Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Gender</span>
            </div>
            <p class="text-sm font-bold text-slate-900 capitalize">{{ $student->gender ?? '—' }}</p>
          </div>

          {{-- Blood Group Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-rose-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.428.583L2.428 17.428a2 2 0 000 2.828l1.428 1.428a2 2 0 002.828 0l1.428-1.428a2 2 0 00.583-1.428l-.477-2.387a6 6 0 01.517-3.86l.158-.318a6 6 0 00.517-3.86L9.12 5.6a2 2 0 01.583-1.428l1.428-1.428a2 2 0 012.828 0l1.428 1.428a2 2 0 010 2.828l-1.428 1.428a2 2 0 00-.583 1.428l.477 2.387a6 6 0 00-.517 3.86l-.158.318a6 6 0 01-.517 3.86l.477 2.387a2 2 0 001.428.583l1.428-1.428a2 2 0 000-2.828l-1.428-1.428z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Blood Group</span>
            </div>
            <div>
              @if($student->blood_group)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">{{ $student->blood_group }}</span>
              @else
                <span class="text-slate-400">—</span>
              @endif
            </div>
          </div>

          {{-- Category Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h10M7 17h10"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Category</span>
            </div>
            <p class="text-sm font-bold text-slate-900 uppercase">{{ $student->category ?? '—' }}</p>
          </div>

          {{-- Religion Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21l-8-18h16l-8 18z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Religion</span>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $student->religion ?? '—' }}</p>
          </div>

          {{-- Mother Tongue Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Mother Tongue</span>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $student->mother_tongue ?? '—' }}</p>
          </div>

          {{-- Uniform Dress Size Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <span class="text-sm">👕</span>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Dress Size</span>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $student->dress_size ?? '—' }}</p>
          </div>

          {{-- Shoe Size Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <span class="text-sm">👞</span>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Shoe Size</span>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $student->shoe_size ?? '—' }}</p>
          </div>

          {{-- Second Language Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <span class="text-sm">📖</span>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">2nd Language</span>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $student->second_language ?? '—' }}</p>
          </div>

          {{-- Aadhaar Number Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Aadhaar No.</span>
            </div>
            <p class="font-mono text-sm font-bold text-slate-900">{{ $student->aadhaar_no ? chunk_split($student->aadhaar_no, 4, ' ') : '—' }}</p>
          </div>

          {{-- Student Mobile Number Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Mobile</span>
            </div>
            <p class="font-mono text-sm font-bold text-slate-900">{{ $student->mobile ?? '—' }}</p>
          </div>

          {{-- Pincode Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pincode</span>
            </div>
            <p class="font-mono text-sm font-semibold text-slate-900">{{ $student->pincode ?? '—' }}</p>
          </div>

          {{-- PwD Status Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">PwD Status</span>
            </div>
            <p class="text-sm font-semibold text-slate-900 {{ $student->is_disabled ? 'text-amber-600 font-bold' : '' }}">
              {{ $student->is_disabled ? ($student->disability_description ?? 'Yes') : 'No' }}
            </p>
          </div>

          {{-- Email Address Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Email Address</span>
            </div>
            <p class="font-mono text-sm font-semibold text-slate-900 break-all">{{ $student->email ?? '—' }}</p>
          </div>

          {{-- EMIS / PEN Number Tile --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">EMIS / PEN Number</span>
            </div>
            <p class="font-mono text-sm font-extrabold text-indigo-700">{{ $student->emis_no ?: '—' }}</p>
          </div>

          {{-- Visible Identification Marks Tile (Full Width across 2 cols if on tablet/desktop) --}}
          <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-1 hover:border-indigo-300 transition-all shadow-2xs sm:col-span-2 md:col-span-3">
            <div class="flex items-center gap-1.5 text-slate-400">
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Visible Identification Marks</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-0.5">
              <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-bold block text-[10px] uppercase">Mark 1</span>
                <span class="font-semibold text-slate-800">{{ $student->identification_mark_1 ?: 'None specified' }}</span>
              </div>
              <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-bold block text-[10px] uppercase">Mark 2</span>
                <span class="font-semibold text-slate-800">{{ $student->identification_mark_2 ?: 'None specified' }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- Academic Background & Senior Secondary Stream Card --}}
      @if($student->stream_group || $student->previous_school_name || $student->previous_percentage || $student->year_of_passing)
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <span class="text-base">🎓</span>
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">Academic Specialization &amp; Previous Records</h3>
              <p class="text-xs text-slate-400">Senior secondary stream group, qualifying board exams, and previous school history</p>
            </div>
          </div>
          @if($student->stream_group)
            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
              Grade XI / XII Stream Allotted
            </span>
          @endif
        </div>

        @if($student->stream_group)
        <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50/70 to-slate-50 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="space-y-0.5">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 block">Allotted Stream / Group</span>
            <p class="text-sm font-extrabold text-blue-950">{{ $student->stream_group }}</p>
          </div>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-600 text-white self-start sm:self-auto shadow-2xs">CBSE Senior Secondary</span>
        </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/70 space-y-0.5">
            <span class="text-slate-400 font-bold block uppercase text-[10px]">Previous School</span>
            <span class="font-bold text-slate-900 text-sm block">{{ $student->previous_school_name ?: '—' }}</span>
          </div>

          <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/70 space-y-0.5">
            <span class="text-slate-400 font-bold block uppercase text-[10px]">Board Affiliation</span>
            <span class="font-bold text-slate-900 text-sm block">{{ $student->previous_school_board ?: 'CBSE' }}</span>
          </div>

          <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/70 space-y-0.5">
            <span class="text-slate-400 font-bold block uppercase text-[10px]">Marks / Percentage</span>
            <span class="font-mono font-bold text-blue-700 text-sm block">{{ $student->previous_percentage ? $student->previous_percentage . '%' : '—' }}</span>
          </div>

          <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/70 space-y-0.5">
            <span class="text-slate-400 font-bold block uppercase text-[10px]">Year of Passing</span>
            <span class="font-mono font-bold text-slate-900 text-sm block">{{ $student->year_of_passing ?: '—' }}</span>
          </div>
        </div>

        <div class="flex items-center gap-6 pt-1 text-xs font-semibold text-slate-700">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-md flex items-center justify-center text-xs font-bold {{ $student->is_tc_enclosed ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">
              {{ $student->is_tc_enclosed ? '✓' : '✗' }}
            </span>
            <span>Original TC {{ $student->is_tc_enclosed ? 'Enclosed' : 'Pending' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-md flex items-center justify-center text-xs font-bold {{ $student->is_qualified_promotion ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">
              {{ $student->is_qualified_promotion ? '✓' : '✗' }}
            </span>
            <span>Qualified for Promotion ({{ $student->is_qualified_promotion ?: 'Yes' }})</span>
          </div>
        </div>
      </div>
      @endif

      {{-- Parent & Guardian Information Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
          <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Parent &amp; Guardian Information</h3>
            <p class="text-xs text-slate-400">Parental contacts and family background</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">
          {{-- Father Details Card --}}
          <div class="bg-slate-50/90 p-5 rounded-2xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold shrink-0 overflow-hidden shadow-2xs">
                  @if($student->father_photo)
                    <img src="{{ asset('storage/'.$student->father_photo) }}" class="w-full h-full object-cover" alt="Father">
                  @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  @endif
                </div>
                <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Father Details</span>
              </div>
              @if($student->father_photo)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200">Photo Verified</span>
              @endif
            </div>
            <div class="space-y-2.5 text-xs">
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Father Name:</span>
                <span class="text-sm font-bold text-slate-900 block">{{ $student->father_name ?? '—' }}</span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Occupation:</span>
                <span class="font-semibold text-slate-800 block">{{ $student->father_occupation ?? '—' }}</span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Mobile Number:</span>
                <span class="font-mono text-sm font-bold text-slate-900 block">{{ $student->father_mobile ?? '—' }}</span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Email Address:</span>
                <span class="font-mono text-slate-900 font-medium break-all block">{{ $student->father_email ?? '—' }}</span>
              </div>
            </div>
          </div>

          {{-- Mother Details Card --}}
          <div class="bg-slate-50/90 p-5 rounded-2xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold shrink-0 overflow-hidden shadow-2xs">
                  @if($student->mother_photo)
                    <img src="{{ asset('storage/'.$student->mother_photo) }}" class="w-full h-full object-cover" alt="Mother">
                  @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  @endif
                </div>
                <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Mother Details</span>
              </div>
              @if($student->mother_photo)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200">Photo Verified</span>
              @endif
            </div>
            <div class="space-y-2.5 text-xs">
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Mother Name:</span>
                <span class="text-sm font-bold text-slate-900 block">{{ $student->mother_name ?? '—' }}</span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Occupation:</span>
                <span class="font-semibold text-slate-800 block">{{ $student->mother_occupation ?? '—' }}</span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Mobile Number:</span>
                <span class="font-mono text-sm font-bold text-slate-900 block">{{ $student->mother_mobile ?? '—' }}</span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                <span class="text-slate-400 font-medium block">Email Address:</span>
                <span class="font-mono text-slate-900 font-medium break-all block">{{ $student->mother_email ?? '—' }}</span>
              </div>
            </div>
          </div>

          {{-- Guardian Details Card --}}
          <div class="bg-slate-50/90 p-5 rounded-2xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold shrink-0 overflow-hidden shadow-2xs">
                  @if($student->guardian_photo)
                    <img src="{{ asset('storage/'.$student->guardian_photo) }}" class="w-full h-full object-cover" alt="Guardian">
                  @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                  @endif
                </div>
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Guardian Details</span>
              </div>
              @if($student->guardian_photo)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">Photo Verified</span>
              @endif
            </div>
            @if($student->guardian_name)
              <div class="space-y-2.5 text-xs">
                <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                  <span class="text-slate-400 font-medium block">Guardian Name:</span>
                  <span class="text-sm font-bold text-slate-900 block">{{ $student->guardian_name }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                  <span class="text-slate-400 font-medium block">Relationship:</span>
                  <span class="font-semibold text-slate-800 block capitalize">{{ $student->guardian_relation ?? 'Guardian' }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                  <span class="text-slate-400 font-medium block">Mobile Number:</span>
                  <span class="font-mono text-sm font-bold text-slate-900 block">{{ $student->guardian_mobile ?? '—' }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-slate-200/70 shadow-2xs space-y-0.5">
                  <span class="text-slate-400 font-medium block">Email Address:</span>
                  <span class="font-mono text-slate-900 font-medium break-all block">{{ $student->guardian_email ?? '—' }}</span>
                </div>
              </div>
            @else
              <div class="py-8 text-center text-slate-400 space-y-1 bg-white rounded-xl border border-slate-200/70 p-4">
                <p class="text-sm font-semibold text-slate-500">No Guardian Assigned</p>
                <p class="text-xs">Primary contact is managed by parents.</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Address Cards --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Residential Address Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
          <div class="flex items-center gap-2 text-slate-900 font-bold border-b border-slate-100 pb-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            </div>
            <h3>Residential Address</h3>
          </div>
          <p class="text-sm text-slate-700 leading-relaxed font-medium">
            {{ $student->residential_address ?? $student->address ?? 'No residential address recorded.' }}
          </p>
        </div>

        {{-- Permanent Address Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
          <div class="flex items-center gap-2 text-slate-900 font-bold border-b border-slate-100 pb-3">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <h3>Permanent Address</h3>
          </div>
          <p class="text-sm text-slate-700 leading-relaxed font-medium">
            {{ $student->permanent_address ?? $student->residential_address ?? $student->address ?? 'Same as residential address.' }}
          </p>
        </div>
      </div>

      {{-- Sibling Studying in School Card --}}
      @if($student->sibling_name || $student->sibling_admission_no)
      <div class="bg-indigo-50/70 rounded-2xl border border-indigo-200/80 p-5 space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 text-indigo-900 font-bold">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <span class="text-xs uppercase tracking-wider">Sibling Studying in School</span>
          </div>
          <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-indigo-200/80 text-indigo-900 uppercase">Verified Sibling</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-white p-3.5 rounded-xl border border-indigo-100">
          <div>
            <span class="text-slate-400 font-medium block">Sibling Name</span>
            <span class="font-bold text-slate-900">{{ $student->sibling_name ?? '—' }}</span>
          </div>
          <div>
            <span class="text-slate-400 font-medium block">Admission No.</span>
            <span class="font-mono font-bold text-indigo-700">{{ $student->sibling_admission_no ?? '—' }}</span>
          </div>
          <div>
            <span class="text-slate-400 font-medium block">Class / Section</span>
            <span class="font-bold text-slate-800">{{ $student->sibling_class ?? '—' }}</span>
          </div>
        </div>
      </div>
      @endif

      {{-- Concession Banner Card --}}
      @if($student->concession_type && $student->concession_amount > 0)
      <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 rounded-2xl border border-emerald-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl shadow-xs shrink-0">
            🏷️
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Approved Fee Concession</span>
              <span class="text-xs px-2.5 py-0.5 rounded-md font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                {{ ucwords(str_replace('_', ' ', $student->concession_type)) }}
              </span>
            </div>
            <p class="text-xs text-emerald-700 mt-1 font-medium">
              Approval Remarks: <span class="font-bold text-emerald-950">{{ $student->concession_remarks ?: 'Approved by School Management / Principal' }}</span>
            </p>
          </div>
        </div>
        <div class="bg-white px-5 py-3 rounded-xl border border-emerald-200 shadow-2xs text-left sm:text-right shrink-0">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Discount Deducted</span>
          <span class="text-xl font-black text-emerald-600 font-mono">-₹{{ number_format($student->concession_amount) }}</span>
        </div>
      </div>
      @endif

      {{-- Transport, After School Program (ASP) & Activities Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 border border-cyan-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">Transport &amp; Special Facilities</h3>
              <p class="text-xs text-slate-400">School bus route, after school program, and complementary activities</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          {{-- Transport Facility --}}
          <div class="p-4 rounded-2xl border {{ $student->transport_route_id ? 'border-cyan-200 bg-cyan-50/30' : 'border-slate-200/80 bg-slate-50/50' }} space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-cyan-900 uppercase tracking-wider flex items-center gap-1.5">
                🚌 School Bus
              </span>
              <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ $student->transport_route_id ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-200 text-slate-600' }}">
                {{ $student->transport_route_id ? 'Opted In' : 'Self Commute' }}
              </span>
            </div>
            @if($student->transport_route_id)
              <div class="space-y-1.5 text-xs bg-white p-3 rounded-xl border border-cyan-100">
                <div class="flex justify-between">
                  <span class="text-slate-400">Route:</span>
                  <span class="font-bold text-slate-900 truncate max-w-[130px]" title="{{ $student->transportRoute?->name }}">{{ $student->transportRoute?->name ?? 'Route #' . $student->transport_route_id }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Stopping Point:</span>
                  <span class="font-bold text-slate-800 truncate max-w-[130px]" title="{{ $student->transportStop?->name }}">{{ $student->transportStop?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Distance:</span>
                  <span class="font-mono font-bold text-slate-700">{{ $student->transport_distance_km ? $student->transport_distance_km . ' km' : ($student->transportStop?->distance_km ? $student->transportStop->distance_km . ' km' : '—') }}</span>
                </div>
                <div class="flex justify-between pt-1 border-t border-slate-100">
                  <span class="text-slate-500 font-bold">Annual Bus Fee:</span>
                  <span class="font-mono font-extrabold text-cyan-700">₹{{ number_format($student->transport_fee ?? 0) }}</span>
                </div>
              </div>
            @else
              <p class="text-xs text-slate-500 py-2">No school transportation requested. Student uses private commute.</p>
            @endif
          </div>

          {{-- ASP (After School Program) --}}
          <div class="p-4 rounded-2xl border {{ $student->is_asp ? 'border-amber-200 bg-amber-50/30' : 'border-slate-200/80 bg-slate-50/50' }} space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                🕒 ASP Program
              </span>
              <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ $student->is_asp ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600' }}">
                {{ $student->is_asp ? 'Enrolled' : 'Not Enrolled' }}
              </span>
            </div>
            @if($student->is_asp)
              <div class="space-y-1.5 text-xs bg-white p-3 rounded-xl border border-amber-100">
                <p class="text-[11px] text-slate-600">Extended supervised evening study, homework guidance &amp; enrichment.</p>
                <div class="flex justify-between pt-1 border-t border-slate-100">
                  <span class="text-slate-500 font-bold">ASP Program Fee:</span>
                  <span class="font-mono font-extrabold text-amber-700">₹{{ number_format($student->asp_fee ?? 0) }}</span>
                </div>
              </div>
            @else
              <p class="text-xs text-slate-500 py-2">Regular school hours. Not participating in evening ASP sessions.</p>
            @endif
          </div>

          {{-- Complimentary Extra Curricular Activity --}}
          <div class="p-4 rounded-2xl border border-indigo-100 bg-indigo-50/20 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                🎯 Complimentary ECA
              </span>
              <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                Free (Included)
              </span>
            </div>
            <div class="space-y-1.5 text-xs bg-white p-3 rounded-xl border border-indigo-100">
              <span class="text-slate-400 font-medium block">Selected Activity:</span>
              @php
                $ecaList = is_array($student->selected_eca)
                  ? $student->selected_eca
                  : (!empty($student->selected_eca) ? [$student->selected_eca] : []);
              @endphp
              @if(count($ecaList) > 0)
                <div class="flex flex-wrap gap-1.5 py-1">
                  @foreach($ecaList as $eca)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                      {{ ucwords(str_replace('_', ' ', $eca)) }}
                    </span>
                  @endforeach
                </div>
              @else
                <span class="text-sm font-bold text-slate-500 block">No specific club selected</span>
              @endif
              <p class="text-[10px] text-slate-500">School provides complimentary ECA activity per student without extra fees.</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- Right Column (1 Column Wide): Admission QR & Status Widgets --}}
    <div class="space-y-6">

      {{-- ── Document Submission QR Code Card ── --}}
      <div class="rounded-3xl p-6 shadow-xl border border-indigo-500/40 space-y-4 relative overflow-hidden text-white"
           style="background: linear-gradient(145deg, #1e1b4b 0%, #1e3a8a 55%, #0f172a 100%); color: #ffffff;"
           x-data="{
             copied: false,
             copyLink() {
               navigator.clipboard.writeText('{{ $qrUrl }}');
               this.copied = true;
               setTimeout(() => this.copied = false, 2500);
             }
           }">
        {{-- Ambient background glow --}}
        <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-blue-400/20 blur-2xl pointer-events-none"></div>

        <div class="flex items-center justify-between border-b border-indigo-400/30 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center text-white border border-white/20">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <div>
              <h3 class="font-extrabold text-white text-sm">Certificate Upload QR</h3>
              <p class="text-[10px] text-blue-200">Scan to upload student soft copies</p>
            </div>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-500/30 text-blue-100 border border-blue-400/40">
            {{ $studentDocuments->count() }} Uploaded
          </span>
        </div>

        {{-- QR Code Display Box --}}
        <div class="bg-white p-3 rounded-2xl shadow-inner text-center mx-auto max-w-[190px] flex items-center justify-center">
          <div class="w-36 h-36 flex items-center justify-center text-slate-900">
            {!! $qrSvg !!}
          </div>
        </div>

        <div class="text-center space-y-1">
          <p class="text-xs font-bold text-white">Scan with Smartphone Camera</p>
          <p class="text-[11px] text-indigo-200/90 leading-tight">
            Directs parents to official portal to upload Aadhaar, Birth Certificate, Community, PAN, etc.
          </p>
        </div>

        {{-- Action Buttons --}}
        <div class="space-y-2 pt-1">
          <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('public.student.documents.qr-download', $student->document_token) }}"
               style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); color: #ffffff;"
               class="py-2.5 px-3 rounded-xl hover:bg-white/25 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
              <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Download QR
            </a>

            <a href="{{ route('public.student.documents.print-card', $student->document_token) }}" target="_blank"
               style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); color: #ffffff;"
               class="py-2.5 px-3 rounded-xl hover:bg-white/25 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
              <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              Print Slip
            </a>
          </div>

          <div class="flex items-center gap-2">
            <button type="button" @click="copyLink()"
                    class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
              <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
              <svg x-show="copied" class="w-3.5 h-3.5 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <span x-text="copied ? 'Link Copied!' : 'Copy Parent Link'"></span>
            </button>

            <a href="{{ $qrUrl }}" target="_blank" title="Open Public Portal"
               class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition flex items-center justify-center shrink-0 border border-white/20">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
          </div>
        </div>
      </div>

      {{-- Attendance Widget Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <h3 class="font-bold text-slate-900 text-sm">Attendance Summary</h3>
          </div>
          @if($student->currentEnrollment)
            <a href="{{ route('attendance.mark', ['class_id' => $student->currentEnrollment->class_id, 'section_id' => $student->currentEnrollment->section_id]) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Mark →</a>
          @endif
        </div>

        @if($totalDaysConducted > 0)
          <div class="text-center py-1">
            <p class="text-4xl font-black tracking-tight {{ ($attPct ?? 0) >= 75 ? 'text-emerald-600' : 'text-rose-600' }}">
              {{ $attPct }}%
            </p>
            <p class="text-xs font-semibold text-slate-600 mt-1">
              <span class="font-mono font-bold text-slate-900">{{ $effectivePresent }}</span> days present out of <span class="font-mono font-bold text-slate-900">{{ $totalDaysConducted }}</span> conducted days
            </p>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Fixed Annual Target: <span class="font-mono font-semibold text-slate-600">{{ $fixedAnnualDays }} Days</span> (75% min: {{ $statutoryMinDaysRequired }}d)
            </p>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 {{ ($attPct ?? 0) >= 75 ? 'bg-emerald-500' : 'bg-rose-500' }}"
                 style="width: {{ min(100, $attPct ?? 0) }}%"></div>
          </div>

          {{-- Quick Status Breakdown (P, A, L, HD, LV) --}}
          <div class="grid grid-cols-3 gap-1.5 text-xs pt-1">
            <div class="bg-emerald-50 p-2 rounded-xl border border-emerald-100 text-center" title="P = Present (Full Day)">
              <span class="text-[10px] text-emerald-700 font-bold uppercase">Present (P)</span>
              <p class="font-bold text-emerald-900 font-mono">{{ $attPresent }} d</p>
            </div>
            <div class="bg-rose-50 p-2 rounded-xl border border-rose-100 text-center" title="A = Absent">
              <span class="text-[10px] text-rose-700 font-bold uppercase">Absent (A)</span>
              <p class="font-bold text-rose-900 font-mono">{{ $attAbsent }} d</p>
            </div>
            <div class="bg-amber-50 p-2 rounded-xl border border-amber-100 text-center" title="L = Late Arrival">
              <span class="text-[10px] text-amber-700 font-bold uppercase">Late (L)</span>
              <p class="font-bold text-amber-900 font-mono">{{ $attLate }} d</p>
            </div>
            <div class="bg-orange-50 p-2 rounded-xl border border-orange-100 text-center" title="HD = Half Day (0.5 credit)">
              <span class="text-[10px] text-orange-700 font-bold uppercase">Half Day (HD)</span>
              <p class="font-bold text-orange-900 font-mono">{{ $attHalfDay }} d</p>
            </div>
            <div class="bg-blue-50 p-2 rounded-xl border border-blue-100 text-center col-span-2" title="LV = Approved Leave">
              <span class="text-[10px] text-blue-700 font-bold uppercase">Approved Leave (LV)</span>
              <p class="font-bold text-blue-900 font-mono">{{ $attLeave }} d</p>
            </div>
          </div>

          @if(($attPct ?? 0) < 75)
          <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Attendance below statutory 75% threshold</span>
          </div>
          @endif
        @else
          <div class="py-6 text-center text-slate-400">
            <p class="text-sm font-semibold">No attendance records for this year.</p>
            <p class="text-[11px] text-slate-400 mt-1">Fixed Annual Target: {{ $fixedAnnualDays }} Working Days</p>
          </div>
        @endif
      </div>

      {{-- Fee Status Card --}}
      <div id="sidebar-fee-card" class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <h3 class="font-bold text-slate-900 text-sm">Fee Status</h3>
          </div>
          <a href="{{ route('fees.collect', ['student_id' => $student->id]) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Fee Desk →</a>
        </div>

        <div class="space-y-2 text-xs">
          <div class="flex justify-between items-center py-1">
            <span class="text-slate-500 font-medium">Total Billed:</span>
            <span id="fee-card-billed" class="font-mono font-bold text-slate-900">₹{{ number_format($feeCharged) }}</span>
          </div>
          <div class="flex justify-between items-center py-1">
            <span class="text-slate-500 font-medium">Total Paid:</span>
            <span id="fee-card-paid" class="font-mono font-bold text-emerald-600">₹{{ number_format($feePaid) }}</span>
          </div>
          <div class="border-t border-slate-100 pt-2 flex justify-between items-center">
            <span id="fee-card-status-label" class="font-bold {{ $feeBalance > 0 ? 'text-rose-600' : 'text-emerald-600' }} transition-colors duration-300">
              {{ $feeBalance > 0 ? 'Balance Due:' : 'Status:' }}
            </span>
            <span id="fee-card-balance-val" class="font-mono text-base font-extrabold {{ $feeBalance > 0 ? 'text-rose-600' : 'text-emerald-600' }} transition-colors duration-300">
              {{ $feeBalance > 0 ? '₹' . number_format($feeBalance) : 'Fully Paid' }}
            </span>
          </div>
        </div>

        <div id="fee-card-recent-section" class="{{ $recentPayments->count() ? '' : 'hidden' }} mt-3 pt-3 border-t border-slate-100 space-y-2">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Recent Payments</p>
          <div id="fee-card-recent-list" class="space-y-2">
            @foreach($recentPayments as $pay)
            <div class="flex justify-between items-center text-xs py-1">
              <span class="text-slate-500">{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</span>
              <span class="font-mono font-semibold text-emerald-600">₹{{ number_format($pay->total_paid ?? 0) }}</span>
            </div>
            @endforeach
          </div>
        </div>

        <a href="{{ route('fees.collect', ['student_id' => $student->id]) }}" class="mt-2 btn btn-primary btn-sm w-full text-center flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          FeePayment
        </a>
      </div>

      {{-- Enrollment History Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-200 p-6 space-y-3">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
          <h3 class="font-bold text-slate-900 text-sm">Enrollment History</h3>
        </div>

        <div class="space-y-2">
          @forelse($student->enrollments->sortByDesc('id') as $en)
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
              <div>
                <p class="font-bold text-slate-900">{{ $en->class?->name }} @if($en->section) &bull; Section {{ $en->section->name }} @endif</p>
                <p class="text-[11px] text-slate-500 font-medium">{{ $en->academicYear?->name }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $en->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                {{ ucfirst($en->status) }}
              </span>
            </div>
          @empty
            <p class="text-xs text-slate-400 text-center py-3">No enrollment history.</p>
          @endforelse
        </div>
      </div>

      {{-- Quick Action Links Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-200 p-6 space-y-3">
        <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3">Quick Navigation</h3>
        <div class="space-y-1 text-xs font-medium">
          <a href="{{ route('students.medical', $student->id) }}" class="flex items-center gap-2.5 p-2.5 rounded-xl hover:bg-slate-50 text-slate-700 transition">
            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Medical Records
          </a>
          <a href="{{ route('students.disciplinary', $student->id) }}" class="flex items-center gap-2.5 p-2.5 rounded-xl hover:bg-slate-50 text-slate-700 transition">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Disciplinary Records
          </a>
          <a href="{{ route('students.id-cards', ['student_id' => $student->id]) }}" class="flex items-center gap-2.5 p-2.5 rounded-xl hover:bg-slate-50 text-slate-700 transition">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
            Print ID Card
          </a>
          <a href="{{ route('students.tc.form', $student->id) }}" class="flex items-center gap-2.5 p-2.5 rounded-xl hover:bg-slate-50 text-slate-700 transition">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Transfer Certificate
          </a>
        </div>
      </div>

    </div>
  </div>

  {{-- ========================================================================= --}}
  {{-- 2. FULL WIDTH: STUDENT CERTIFICATES & SOFT COPIES GALLERY                --}}
  {{-- ========================================================================= --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h3 class="text-lg font-bold text-slate-900">Student Certificates &amp; Soft Copies</h3>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
              {{ $studentDocuments->count() }} of {{ count($documentCategories) }} Uploaded
            </span>
          </div>
          <p class="text-xs text-slate-400">Official verification status of Aadhaar, Birth Certificate, Community, PAN, TC &amp; marksheets</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ $qrUrl }}" target="_blank" class="btn btn-secondary btn-xs flex items-center gap-1.5 font-bold text-indigo-600">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          Upload Portal
        </a>
        <a href="{{ route('students.documents', $student->id) }}" class="btn btn-primary btn-xs flex items-center gap-1.5 font-bold">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Staff Upload
        </a>
      </div>
    </div>

    {{-- Physical Documents Checklist (Submitted at Admission Desk) --}}
    @php
      $officialChecklistItems = [
        'birth_certificate' => 'Birth Certificate (Original / Copy)',
        'transfer_certificate' => 'Transfer Certificate (TC)',
        'marksheet' => 'Previous Standard Marksheet',
        'aadhaar_card' => 'Student Aadhaar Card Copy',
        'community_certificate' => 'Community / Caste Certificate',
        'migration_certificate' => 'Migration Certificate',
        'emis_slip' => 'EMIS / PEN Slip',
      ];
      $submittedDocs = is_array($student->documents_submitted) ? $student->documents_submitted : [];
      $submittedCount = count(array_intersect(array_keys($officialChecklistItems), $submittedDocs));
    @endphp

    <div class="bg-slate-50/90 rounded-2xl border border-slate-200/80 p-5 space-y-3">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2">
          <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
            📋 Physical Hardcopy Documents Submitted to Admission Desk
          </span>
        </div>
        <span class="text-xs font-bold font-mono px-2.5 py-0.5 rounded-full {{ $submittedCount > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
          {{ $submittedCount }} of {{ count($officialChecklistItems) }} Verified Hardcopies
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 pt-1">
        @foreach($officialChecklistItems as $docKey => $docLabel)
          @php $hasSubmitted = in_array($docKey, $submittedDocs); @endphp
          <div class="p-2.5 rounded-xl border {{ $hasSubmitted ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' : 'bg-white border-slate-200 text-slate-500' }} flex items-center gap-2 text-xs">
            @if($hasSubmitted)
              <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <span class="font-bold text-emerald-950">{{ $docLabel }}</span>
            @else
              <span class="w-4 h-4 rounded-full border border-slate-300 text-slate-300 flex items-center justify-center text-[10px] shrink-0 font-bold">○</span>
              <span class="text-slate-400 font-medium">{{ $docLabel }}</span>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    {{-- Document Grid: 3 columns on lg for optimal space utilization across full width --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($documentCategories as $catKey => $catInfo)
        @php
          $doc = $studentDocuments->firstWhere('document_type', $catKey);
          $isUploaded = (bool) $doc;
        @endphp
        <div class="p-4 rounded-xl border {{ $doc?->status === 'verified' ? 'border-emerald-200 bg-emerald-50/20' : ($doc?->status === 'rejected' ? 'border-rose-200 bg-rose-50/20' : ($isUploaded ? 'border-indigo-100 bg-indigo-50/20' : 'border-slate-100 bg-slate-50/40')) }} flex flex-col justify-between space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="space-y-0.5">
              <h4 class="text-xs font-bold text-slate-900">{{ $catInfo['title'] }}</h4>
              <p class="text-[10px] text-slate-500">{{ $catInfo['subtitle'] }}</p>
            </div>

            @if($doc?->status === 'verified')
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">
                <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                Verified
              </span>
            @elseif($doc?->status === 'rejected')
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 shrink-0">
                Rejected
              </span>
            @elseif($isUploaded)
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">
                Pending Review
              </span>
            @else
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $catInfo['required'] ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-100 text-slate-500' }} shrink-0">
                Not Uploaded
              </span>
            @endif
          </div>

          @if($isUploaded)
            <div class="bg-white p-2.5 rounded-lg border border-slate-200/80 text-[11px] space-y-1.5">
              <div class="flex items-center justify-between gap-1">
                <span class="font-mono text-slate-700 truncate max-w-[170px]" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                <span class="text-[10px] text-slate-400">{{ $doc->created_at->format('d M Y') }}</span>
              </div>

              @if($doc->remarks)
                <p class="text-[10px] text-slate-500 italic bg-slate-50 p-1.5 rounded border border-slate-100">{{ $doc->remarks }}</p>
              @endif

              <div class="flex items-center justify-end gap-1.5 pt-1.5 border-t border-slate-100 flex-wrap">
                <a href="{{ route('public.student.documents.download', [$student->document_token, $doc->id]) }}" target="_blank"
                   class="px-2 py-0.5 rounded text-[10px] font-bold text-indigo-600 hover:bg-indigo-50 border border-indigo-200">
                  View Soft Copy
                </a>

                @if($doc->status !== 'verified')
                  <form method="POST" action="{{ route('students.documents.verify', $doc->id) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-2 py-0.5 rounded text-[10px] font-bold text-emerald-700 hover:bg-emerald-50 border border-emerald-200 cursor-pointer">
                      Approve
                    </button>
                  </form>
                @endif

                @if($doc->status !== 'rejected')
                  <button type="button"
                          @click="$dispatch('open-rejection-modal', { docId: {{ $doc->id }}, docType: '{{ $catInfo['title'] }}' })"
                          class="px-2 py-0.5 rounded text-[10px] font-bold text-rose-700 hover:bg-rose-50 border border-rose-200 cursor-pointer">
                    Reject
                  </button>
                @endif
              </div>
            </div>
          @else
            <div class="flex items-center justify-between pt-1">
              <span class="text-[10px] text-slate-400 font-medium">
                {{ $catInfo['required'] ? 'Mandatory Certificate' : 'Optional Document' }}
              </span>
              <a href="{{ route('public.student.documents', $student->document_token) }}" target="_blank" class="text-[10px] font-bold text-indigo-600 hover:underline">
                Upload &rarr;
              </a>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>

  {{-- ========================================================================= --}}
  {{-- 3. BALANCED 2-COLUMN SECTION: ACADEMIC CREDENTIALS & HEALTH / INCOME      --}}
  {{-- ========================================================================= --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    {{-- Col 1: Previous Academic History & Transfer Credentials --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4 h-full flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div>
            <h3 class="text-base font-extrabold text-slate-900">Previous Academic History &amp; Credentials</h3>
            <p class="text-xs text-slate-500 font-medium">Information from prior educational institution</p>
          </div>
        </div>

        @if($student->previous_school_name || $student->previous_school_board || $student->tc_number || $student->migration_certificate_number || $student->previous_percentage)
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-4">
            @if($student->previous_school_name)
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-0.5">
              <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Previous School</span>
              <span class="text-xs font-bold text-slate-900 block">{{ $student->previous_school_name }}</span>
            </div>
            @endif

            @if($student->previous_school_board)
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-0.5">
              <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Board / Council</span>
              <span class="text-xs font-semibold text-slate-800 block">{{ $student->previous_school_board }}</span>
            </div>
            @endif

            @if($student->previous_percentage)
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-0.5">
              <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Previous Marks %</span>
              <span class="text-xs font-extrabold text-indigo-600 font-mono block">{{ $student->previous_percentage }}%</span>
            </div>
            @endif

            @if($student->tc_number)
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-0.5">
              <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">TC Number & Date</span>
              <span class="text-xs font-mono font-bold text-slate-900 block">{{ $student->tc_number }} {{ $student->tc_date ? '(' . $student->tc_date->format('d M Y') . ')' : '' }}</span>
            </div>
            @endif

            @if($student->migration_certificate_number)
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-0.5 sm:col-span-2">
              <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Migration Cert. No & Date</span>
              <span class="text-xs font-mono font-bold text-slate-900 block">{{ $student->migration_certificate_number }} {{ $student->migration_certificate_date ? '(' . $student->migration_certificate_date->format('d M Y') . ')' : '' }}</span>
            </div>
            @endif
          </div>
        @else
          <div class="py-8 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200 mt-4">
            <p class="text-xs font-semibold">No prior school academic records on file.</p>
          </div>
        @endif
      </div>
    </div>

    {{-- Col 2: Health, Allergies & Annual Family Income --}}
    <div class="space-y-6">
      {{-- Annual Family Income Card --}}
      <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl text-white p-6 shadow-md flex items-center justify-between gap-6">
        <div class="space-y-1">
          <div class="flex items-center gap-2 text-emerald-100">
            <span class="text-xs font-bold uppercase tracking-wider">Annual Family Income</span>
          </div>
          <p class="text-2xl sm:text-3xl font-black tracking-tight mt-1">
            {{ $student->annual_family_income ? '₹' . number_format((float)$student->annual_family_income) : 'Not Disclosed' }}
          </p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
      </div>

      {{-- Medical & Allergies Information Card --}}
      <div class="bg-white rounded-2xl border border-rose-200/80 shadow-sm p-6 space-y-4">
        <div class="flex items-center gap-3 border-b border-rose-100 pb-3">
          <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center font-bold shrink-0 text-base">
            🩺
          </div>
          <div>
            <h3 class="text-sm font-extrabold text-slate-900">Medical Notes & Health Considerations</h3>
            <p class="text-[11px] text-rose-600 font-medium">Important health considerations for school staff</p>
          </div>
        </div>

        @if($student->allergies || $student->medical_conditions)
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            @if($student->allergies)
            <div class="bg-rose-50/50 p-3 rounded-xl border border-rose-200/60 space-y-0.5">
              <span class="text-rose-500 font-bold uppercase tracking-wider text-[10px] block">Known Allergies</span>
              <span class="text-xs font-semibold text-slate-900 block">{{ $student->allergies }}</span>
            </div>
            @endif

            @if($student->medical_conditions)
            <div class="bg-amber-50/50 p-3 rounded-xl border border-amber-200/60 space-y-0.5">
              <span class="text-amber-600 font-bold uppercase tracking-wider text-[10px] block">Medical Conditions</span>
              <span class="text-xs font-semibold text-slate-900 block">{{ $student->medical_conditions }}</span>
            </div>
            @endif
          </div>
        @else
          <div class="py-4 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
            <p class="text-xs font-semibold">No medical conditions or allergies recorded.</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ========================================================================= --}}
  {{-- 4. FULL WIDTH: STUDENT-WISE FEE STATEMENT & PAYMENT LEDGER                --}}
  {{-- ========================================================================= --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6" id="fee-statement-section">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-50 to-blue-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100 shadow-xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h3 class="text-lg font-bold text-slate-900">Student Fee Statement &amp; Ledger</h3>
            @if($student->payment_terms)
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                {{ str_replace('_', ' ', strtoupper($student->payment_terms)) }}
              </span>
            @endif
            @php
              $statusBadge = match($student->payment_status) {
                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'partially_paid' => 'bg-amber-50 text-amber-700 border-amber-200',
                default => 'bg-rose-50 text-rose-700 border-rose-200'
              };
            @endphp
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border {{ $statusBadge }}">
              {{ str_replace('_', ' ', $student->payment_status ?? 'Pending') }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-0.5">Real-time ledger with automated waterfall adjustment, term schedules &amp; transaction history</p>
        </div>
      </div>

      <div class="flex items-center gap-2.5 self-stretch sm:self-auto justify-end">
        <a href="{{ route('fees.student-statement', ['student_id' => $student->id]) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200 transition flex items-center gap-1.5 shadow-2xs">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Print Statement
        </a>

        <a href="{{ route('fees.collect', ['student_id' => $student->id]) }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
          Payment Received
        </a>
      </div>
    </div>

    {{-- The 5 Student-Wise Metric Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5">
      <div class="bg-gradient-to-br from-slate-50 to-slate-100/60 p-4 rounded-xl border border-slate-200">
        <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Total Fees Payable</p>
        <p class="text-xl font-black text-slate-900 font-mono mt-1">₹{{ number_format($totalFeesPayable, 2) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Annual / Course fee</p>
      </div>

      <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200">
        <p class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">Total Amount Paid</p>
        <p class="text-xl font-black text-emerald-700 font-mono mt-1">₹{{ number_format($totalPaid, 2) }}</p>
        <p class="text-[10px] text-emerald-600/80 mt-0.5">Gross collections</p>
      </div>

      <div class="bg-blue-50/70 p-4 rounded-xl border border-blue-200">
        <p class="text-[10px] font-extrabold text-blue-700 uppercase tracking-wider">Amount Adjusted</p>
        <p class="text-xl font-black text-blue-700 font-mono mt-1">₹{{ number_format($totalAdjusted, 2) }}</p>
        <p class="text-[10px] text-blue-600/80 mt-0.5">Applied against dues</p>
      </div>

      <div class="bg-purple-50/70 p-4 rounded-xl border border-purple-200">
        <p class="text-[10px] font-extrabold text-purple-700 uppercase tracking-wider">Excess Amount / Advance</p>
        <p class="text-xl font-black text-purple-700 font-mono mt-1">₹{{ number_format($excessAmount, 2) }}</p>
        <p class="text-[10px] text-purple-600/80 mt-0.5">Surplus credit balance</p>
      </div>

      <div class="bg-gradient-to-br from-amber-50/80 to-rose-50/60 p-4 rounded-xl border border-amber-200 col-span-2 sm:col-span-1">
        <p class="text-[10px] font-extrabold text-amber-800 uppercase tracking-wider">Outstanding Balance</p>
        <p class="text-xl font-black {{ $outstandingBalance > 0 ? 'text-amber-700' : 'text-emerald-700' }} font-mono mt-1">
          ₹{{ number_format($outstandingBalance, 2) }}
        </p>
        <p class="text-[10px] text-amber-600/80 mt-0.5">Net balance due</p>
      </div>
    </div>

    {{-- Term-Wise Schedule with Remaining Payable Breakdown --}}
    @if(!empty($student->admission_fee_terms) && is_array($student->admission_fee_terms))
    <div class="space-y-2.5 pt-2">
      <div class="flex items-center justify-between">
        <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Term-Wise Payment &amp; Adjustment Schedule
        </h4>
        <span class="text-[11px] text-slate-400">Excess payments automatically waterfall to future terms</span>
      </div>

      <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
        <table class="w-full text-xs text-left">
          <thead>
            <tr class="bg-slate-50/90 text-slate-600 border-b border-slate-200 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-3.5">Term Name</th>
              <th class="py-3 px-3.5 text-right">Term Amount</th>
              <th class="py-3 px-3.5 text-right text-emerald-700">Paid / Adjusted</th>
              <th class="py-3 px-3.5 text-right text-amber-700">Remaining Payable</th>
              <th class="py-3 px-3.5">Due Date</th>
              <th class="py-3 px-3.5 text-center">Status</th>
              <th class="py-3 px-3.5">Last Mode / Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @foreach($student->admission_fee_terms as $term)
              @php
                $tAmt = (float)($term['amount'] ?? 0);
                $tPaid = (float)($term['paid'] ?? 0);
                $tPending = (float)($term['pending'] ?? max(0, $tAmt - $tPaid));
                $tStatus = $term['status'] ?? ($tPending <= 0 ? 'paid' : ($tPaid > 0 ? 'partially_paid' : 'pending'));
                $tBadge = match($tStatus) {
                  'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                  'partially_paid' => 'bg-amber-100 text-amber-800 border-amber-200',
                  default => 'bg-rose-50 text-rose-700 border-rose-200',
                };
              @endphp
              <tr class="hover:bg-slate-50/60 transition {{ $tPending <= 0 ? 'bg-emerald-50/20' : '' }}">
                <td class="py-3 px-3.5 font-bold text-slate-900 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full {{ $tPending <= 0 ? 'bg-emerald-500' : ($tPaid > 0 ? 'bg-amber-500' : 'bg-slate-300') }}"></span>
                  {{ $term['name'] ?? ('Term ' . ($term['term_number'] ?? $loop->iteration)) }}
                </td>
                <td class="py-3 px-3.5 text-right font-mono font-bold text-slate-900">₹{{ number_format($tAmt, 2) }}</td>
                <td class="py-3 px-3.5 text-right font-mono font-bold text-emerald-600">₹{{ number_format($tPaid, 2) }}</td>
                <td class="py-3 px-3.5 text-right font-mono font-bold {{ $tPending > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                  ₹{{ number_format($tPending, 2) }}
                </td>
                <td class="py-3 px-3.5 font-mono text-slate-600">
                  {{ !empty($term['due_date']) ? \Carbon\Carbon::parse($term['due_date'])->format('d-m-Y') : '—' }}
                </td>
                <td class="py-3 px-3.5 text-center">
                  <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border {{ $tBadge }}">
                    {{ str_replace('_', ' ', $tStatus) }}
                  </span>
                </td>
                <td class="py-3 px-3.5 text-xs text-slate-600">
                  @if($tPaid > 0 && !empty($term['payment_mode']))
                    <span class="font-bold text-slate-800">{{ $term['payment_mode'] }}</span>
                    <span class="text-slate-400 font-mono text-[11px] block">
                      {{ !empty($term['payment_date']) ? \Carbon\Carbon::parse($term['payment_date'])->format('d-m-Y') : '' }}
                    </span>
                  @elseif($tPaid > 0)
                    <span class="text-emerald-700 font-medium">Waterfall Adjusted</span>
                  @else
                    <span class="text-slate-400">—</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- Payment History & Adjustment Ledger Table --}}
    <div class="space-y-2.5 pt-2">
      <div class="flex items-center justify-between">
        <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Payment History &amp; Adjustment Ledger
        </h4>
        <span class="text-[11px] text-slate-400">{{ $allFeePayments->count() }} transaction(s) recorded</span>
      </div>

      @if($allFeePayments->count() > 0)
      <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
        <table class="w-full text-xs text-left">
          <thead>
            <tr class="bg-slate-50/90 text-slate-600 border-b border-slate-200 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-3.5">Receipt #</th>
              <th class="py-3 px-3.5">Date</th>
              <th class="py-3 px-3.5">Particulars / Head</th>
              <th class="py-3 px-3.5 text-right text-emerald-700">Amount Paid</th>
              <th class="py-3 px-3.5 text-right text-blue-700">Adjusted</th>
              <th class="py-3 px-3.5 text-right text-purple-700">Excess Credit</th>
              <th class="py-3 px-3.5">Mode &amp; Ref</th>
              <th class="py-3 px-3.5">Collected By</th>
              <th class="py-3 px-3.5 text-center">Receipt</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @foreach($allFeePayments as $payment)
              @php
                $pAdj = (float)($payment->amount_adjusted ?? $payment->total_paid);
                $pExc = (float)($payment->excess_amount ?? 0);
              @endphp
              <tr class="hover:bg-slate-50/60 transition">
                <td class="py-3 px-3.5 font-mono font-bold text-slate-900">
                  <a href="{{ route('fees.receipt', $payment->id) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                    {{ $payment->receipt_number ?? ('#' . $payment->id) }}
                  </a>
                </td>
                <td class="py-3 px-3.5 font-mono text-slate-700">
                  {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '—' }}
                </td>
                <td class="py-3 px-3.5 text-slate-800 font-medium">
                  {{ $payment->feeHead->name ?? $payment->notes ?? 'Fee Collection' }}
                </td>
                <td class="py-3 px-3.5 text-right font-mono font-bold text-emerald-700">
                  ₹{{ number_format($payment->total_paid, 2) }}
                </td>
                <td class="py-3 px-3.5 text-right font-mono font-bold text-blue-700">
                  ₹{{ number_format($pAdj, 2) }}
                </td>
                <td class="py-3 px-3.5 text-right font-mono font-bold {{ $pExc > 0 ? 'text-purple-700' : 'text-slate-400' }}">
                  {{ $pExc > 0 ? '₹' . number_format($pExc, 2) : '—' }}
                </td>
                <td class="py-3 px-3.5 text-slate-600">
                  <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 font-mono text-[10px] font-bold uppercase text-slate-700">
                    {{ $payment->payment_mode ?? 'CASH' }}
                  </span>
                  @if($payment->reference_number)
                    <span class="text-slate-400 font-mono text-[10px] block mt-0.5">{{ $payment->reference_number }}</span>
                  @endif
                </td>
                <td class="py-3 px-3.5 text-slate-600 text-xs">
                  {{ $payment->collectedBy->name ?? 'System' }}
                </td>
                <td class="py-3 px-3.5 text-center">
                  <a href="{{ route('fees.receipt', $payment->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    View
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-200/80">
        <p class="text-xs text-slate-500 font-medium">No fee payments recorded yet for this student.</p>
        <div class="mt-3">
          <a href="{{ route('fees.collect', ['student_id' => $student->id]) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Payment Received
          </a>
        </div>
      </div>
      @endif
    </div>
  </div>

  {{-- ========================================================================= --}}
  {{-- 5. FULL WIDTH: ISSUED ACADEMIC INVENTORY KIT                              --}}
  {{-- ========================================================================= --}}
  @php
    $issuedKitItems = \App\Models\AdmissionInventoryIssue::with('item')
      ->where('student_id', $student->id)
      ->get();
  @endphp
  @if($issuedKitItems->isNotEmpty())
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-900">Issued Academic Inventory Kit</h3>
          <p class="text-xs text-slate-400">Warehouse inventory items issued upon admission</p>
        </div>
      </div>
      <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-blue-100 text-blue-800">
        {{ $issuedKitItems->count() }} Kit Items Issued
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-bold uppercase tracking-wider text-[10px]">
          <tr>
            <th class="py-3 px-4">Item Code / Name</th>
            <th class="py-3 px-4 text-center">Default Qty</th>
            <th class="py-3 px-4 text-center">Extra Qty</th>
            <th class="py-3 px-4 text-center">Total Issued</th>
            <th class="py-3 px-4 text-right">Unit Charge</th>
            <th class="py-3 px-4 text-right">Extra Charge Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @foreach($issuedKitItems as $issue)
          <tr class="hover:bg-slate-50/70 transition">
            <td class="py-3 px-4">
              <span class="font-bold text-slate-900 block">{{ $issue->item?->name ?? 'Inventory Item' }}</span>
              <span class="text-[10px] text-slate-400 font-mono">SKU: {{ $issue->item?->item_code }}</span>
            </td>
            <td class="py-3 px-4 text-center font-mono text-slate-700">{{ $issue->default_quantity }} {{ $issue->item?->unit }}</td>
            <td class="py-3 px-4 text-center font-mono font-bold text-blue-600">+{{ $issue->additional_quantity }}</td>
            <td class="py-3 px-4 text-center font-mono font-black text-slate-900">{{ $issue->total_quantity }} {{ $issue->item?->unit }}</td>
            <td class="py-3 px-4 text-right font-mono text-slate-700">₹{{ number_format($issue->unit_charge, 2) }}</td>
            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">₹{{ number_format($issue->additional_charge, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- Custom Kit Items & Textbooks --}}
  @if(!empty($student->custom_kit_items) && is_array($student->custom_kit_items) && count($student->custom_kit_items) > 0)
  <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6 sm:p-8 space-y-4">
    <div class="flex items-center justify-between border-b border-indigo-50 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
          <span>📚</span>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900">Custom Kit Items &amp; Textbooks</h3>
          <p class="text-xs text-slate-400">Specialized books, equipment &amp; kit items requested for this student</p>
        </div>
      </div>
      <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-indigo-100 text-indigo-800">
        {{ count($student->custom_kit_items) }} Custom Items
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-indigo-50/50 text-slate-600 border-b border-indigo-100 font-bold uppercase tracking-wider text-[10px]">
          <tr>
            <th class="py-3 px-4">Item / Book Name</th>
            <th class="py-3 px-4">Category</th>
            <th class="py-3 px-4">Size / Spec</th>
            <th class="py-3 px-4 text-center">Quantity</th>
            <th class="py-3 px-4 text-right">Unit Price</th>
            <th class="py-3 px-4 text-right">Total Price</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @foreach($student->custom_kit_items as $cItem)
          @php
            $qty = (int)($cItem['quantity'] ?? 1);
            $price = (float)($cItem['unit_price'] ?? 0);
          @endphp
          <tr class="hover:bg-slate-50/70 transition">
            <td class="py-3 px-4 font-bold text-slate-900">{{ $cItem['name'] ?? '—' }}</td>
            <td class="py-3 px-4 text-slate-600">{{ $cItem['category'] ?? 'Textbook' }}</td>
            <td class="py-3 px-4 text-slate-600 font-mono">{{ $cItem['specification'] ?? '—' }}</td>
            <td class="py-3 px-4 text-center font-mono font-bold">{{ $qty }}</td>
            <td class="py-3 px-4 text-right font-mono text-slate-700">₹{{ number_format($price, 2) }}</td>
            <td class="py-3 px-4 text-right font-mono font-bold text-indigo-700">₹{{ number_format($qty * $price, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- ========================================================================= --}}
  {{-- 6. FULL WIDTH: ATTENDANCE HISTORY & STATUTORY BREAKDOWN                   --}}
  {{-- ========================================================================= --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div>
          <h3 class="text-base font-extrabold text-slate-900">Attendance History &amp; Monthly Breakdown</h3>
          <p class="text-xs text-slate-400">Statutory 75% attendance compliance tracking for current academic year</p>
        </div>
      </div>
      @if($student->currentEnrollment)
        <a href="{{ route('attendance.mark', ['class_id' => $student->currentEnrollment->class_id, 'section_id' => $student->currentEnrollment->section_id]) }}"
           class="btn btn-secondary btn-xs font-bold text-indigo-600">
          Mark Section Attendance &rarr;
        </a>
      @endif
    </div>

    {{-- Monthly Breakdown Table --}}
    @if(isset($monthlyAttendance) && $monthlyAttendance->count())
    <div class="space-y-2">
      <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Monthly Attendance Summary</h4>
      <div class="overflow-x-auto border border-slate-200 rounded-xl">
        <table class="w-full text-xs text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase">
              <th class="py-2.5 px-3">Month</th>
              <th class="py-2.5 px-3 text-center">Conducted</th>
              <th class="py-2.5 px-3 text-center text-emerald-700">Present</th>
              <th class="py-2.5 px-3 text-center text-rose-700">Absent</th>
              <th class="py-2.5 px-3 text-center text-amber-700">Late</th>
              <th class="py-2.5 px-3 text-center text-orange-700">Half Day</th>
              <th class="py-2.5 px-3 text-center text-blue-700">Leave</th>
              <th class="py-2.5 px-3 text-right">Attendance %</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($monthlyAttendance as $m)
            <tr class="hover:bg-slate-50/60 font-medium">
              <td class="py-2 px-3 font-bold text-slate-800">{{ $m->month_name }}</td>
              <td class="py-2 px-3 text-center font-mono text-slate-600">{{ $m->total_days }}</td>
              <td class="py-2 px-3 text-center font-mono font-bold text-emerald-600">{{ $m->present_days }}</td>
              <td class="py-2 px-3 text-center font-mono font-bold text-rose-600">{{ $m->absent_days }}</td>
              <td class="py-2 px-3 text-center font-mono text-amber-600">{{ $m->late_days }}</td>
              <td class="py-2 px-3 text-center font-mono text-orange-600">{{ $m->half_day_days }}</td>
              <td class="py-2 px-3 text-center font-mono text-blue-600">{{ $m->leave_days }}</td>
              <td class="py-2 px-3 text-right font-mono font-bold {{ $m->pct >= 75 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $m->pct }}%
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- Recent Attendance Records Log --}}
    @if(isset($recentAttendanceRecords) && $recentAttendanceRecords->count())
    <div class="space-y-2 pt-2">
      <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Recent Attendance Records (Last 10 Days)</h4>
      <div class="overflow-x-auto border border-slate-200 rounded-xl">
        <table class="w-full text-xs text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase">
              <th class="py-2 px-3">Date</th>
              <th class="py-2 px-3 text-center">Status</th>
              <th class="py-2 px-3 text-center">Arrival</th>
              <th class="py-2 px-3">Reason / Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($recentAttendanceRecords as $rec)
            <tr class="hover:bg-slate-50/50">
              <td class="py-2 px-3 font-mono font-medium text-slate-900">
                {{ $rec->date->format('d M Y (D)') }}
              </td>
              <td class="py-2 px-3 text-center">
                @if($rec->status === 'present')
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Present</span>
                @elseif($rec->status === 'absent')
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Absent</span>
                @elseif($rec->status === 'late')
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Late</span>
                @elseif($rec->status === 'half_day')
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">Half Day</span>
                @elseif($rec->status === 'leave')
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">On Leave</span>
                @endif
              </td>
              <td class="py-2 px-3 text-center font-mono text-[11px] text-slate-600">
                {{ $rec->arrival_time ?? '—' }}
              </td>
              <td class="py-2 px-3 text-slate-500 truncate max-w-xs">
                {{ $rec->remark ?? '—' }}
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @else
    <div class="py-6 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
      <p class="text-xs font-semibold">No attendance entries recorded for this student yet.</p>
    </div>
    @endif
  </div>
</div>

{{-- Mark as Left Modal --}}
<div x-data="{open:false}" @open-modal.window="if($event.detail==='mark-left-{{ $student->id }}')open=true"
  x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display:none">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-100">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <h3 class="font-bold text-slate-900 text-base">Mark Student as Left</h3>
      <button @click="open=false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
    </div>

    <form method="POST" action="{{ route('students.mark-left', $student->id) }}" class="space-y-4">
      @csrf
      <div>
        <label class="label mb-1">Date of Leaving <span class="text-red-500">*</span></label>
        <input type="date" name="leaving_date" value="{{ date('Y-m-d') }}" class="input w-full" required>
      </div>
      <div>
        <label class="label mb-1">Reason for Leaving</label>
        <textarea name="leaving_reason" rows="3" class="input w-full" placeholder="e.g. TC issued, relocated..."></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" @click="open=false" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm bg-amber-600 hover:bg-amber-700 border-amber-600">Mark as Left</button>
      </div>
    </form>
  </div>
</div>

{{-- ── Admission Success & QR Code Modal ── --}}
@if(session('admission_success_qr') || request('admission_success'))
<div x-data="{ open: true, copied: false }"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm animate-fadeIn">
  <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 relative overflow-hidden"
       @click.away="open = false">

    {{-- Festive Top Header --}}
    <div class="text-center space-y-2">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-inner">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Admission Completed Successfully!</h3>
      <p class="text-xs text-slate-500 max-w-sm mx-auto">
        Student <strong class="text-slate-800">{{ $student->full_name }}</strong> has been registered with Admission No: <strong class="text-indigo-600">{{ $student->admission_number }}</strong>.
      </p>
    </div>

    {{-- Highlighted QR Box --}}
    <div class="bg-gradient-to-br from-indigo-900 to-blue-900 p-5 rounded-2xl text-white text-center space-y-3 shadow-lg">
      <div class="bg-white p-3 rounded-2xl mx-auto max-w-[170px] shadow-sm flex items-center justify-center">
        <div class="w-32 h-32 flex items-center justify-center text-slate-900">
          {!! $qrSvg !!}
        </div>
      </div>

      <div class="space-y-1">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Official Document Submission QR Code</p>
        <p class="text-[11px] text-slate-200 leading-snug">
          Give this QR slip to the parent. When scanned, they can take photos &amp; upload Aadhaar, Birth Certificate, Community Certificate, PAN, etc. directly.
        </p>
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
      <a href="{{ route('public.student.documents.print-card', $student->document_token) }}" target="_blank"
         class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print QR Pass
      </a>

      <a href="{{ route('public.student.documents.qr-download', $student->document_token) }}"
         class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-200 transition flex items-center justify-center gap-2 cursor-pointer">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Download QR Image
      </a>
    </div>

    {{-- Close & Continue Button --}}
    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
      <button type="button" @click="navigator.clipboard.writeText('{{ $qrUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
              class="text-xs font-semibold text-indigo-600 hover:underline flex items-center gap-1 cursor-pointer">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
        <span x-text="copied ? 'Link Copied to Clipboard!' : 'Copy WhatsApp Link'"></span>
      </button>

      <button type="button" @click="open = false"
              class="py-2 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
        Continue to Profile &rarr;
      </button>
    </div>

  </div>
</div>
@endif

{{-- Dynamic Fee Status Auto-Update on Back/Navigation without manual refresh --}}
<script>
(function() {
    const studentId = {{ $student->id }};
    const feeStatusUrl = "{{ route('students.fee-status', $student->id) }}";

    function updateFeeStatusUI(data) {
        if (!data || !data.success) return;

        // 1. Update Quick Info Card 2
        const statBalanceVal = document.getElementById('stat-fee-balance-val');
        const statIconContainer = document.getElementById('stat-fee-icon-container');
        if (statBalanceVal) {
            statBalanceVal.textContent = data.feeBalanceFormatted;
            if (data.feeBalance > 0) {
                statBalanceVal.classList.remove('text-emerald-600');
                statBalanceVal.classList.add('text-rose-600');
            } else {
                statBalanceVal.classList.remove('text-rose-600');
                statBalanceVal.classList.add('text-emerald-600');
            }
        }
        if (statIconContainer) {
            if (data.feeBalance > 0) {
                statIconContainer.className = 'w-12 h-12 rounded-xl bg-rose-50 border-rose-100 text-rose-600 border flex items-center justify-center shrink-0 transition-colors duration-300';
            } else {
                statIconContainer.className = 'w-12 h-12 rounded-xl bg-emerald-50 border-emerald-100 text-emerald-600 border flex items-center justify-center shrink-0 transition-colors duration-300';
            }
        }

        // 2. Update Sidebar Fee Status Card
        const billedEl = document.getElementById('fee-card-billed');
        const paidEl = document.getElementById('fee-card-paid');
        const statusLabelEl = document.getElementById('fee-card-status-label');
        const balanceValEl = document.getElementById('fee-card-balance-val');

        if (billedEl) billedEl.textContent = data.feeChargedFormatted;
        if (paidEl) paidEl.textContent = data.feePaidFormatted;

        if (statusLabelEl) {
            statusLabelEl.textContent = data.feeBalance > 0 ? 'Balance Due:' : 'Status:';
            if (data.feeBalance > 0) {
                statusLabelEl.classList.remove('text-emerald-600');
                statusLabelEl.classList.add('text-rose-600');
            } else {
                statusLabelEl.classList.remove('text-rose-600');
                statusLabelEl.classList.add('text-emerald-600');
            }
        }

        if (balanceValEl) {
            balanceValEl.textContent = data.feeBalanceFormatted;
            if (data.feeBalance > 0) {
                balanceValEl.classList.remove('text-emerald-600');
                balanceValEl.classList.add('text-rose-600');
            } else {
                balanceValEl.classList.remove('text-rose-600');
                balanceValEl.classList.add('text-emerald-600');
            }
        }

        // 3. Update Recent Payments List
        const recentSection = document.getElementById('fee-card-recent-section');
        const recentList = document.getElementById('fee-card-recent-list');
        if (recentSection && recentList && Array.isArray(data.recentPayments)) {
            if (data.recentPayments.length > 0) {
                recentSection.classList.remove('hidden');
                recentList.innerHTML = data.recentPayments.map(p => `
                    <div class="flex justify-between items-center text-xs py-1">
                        <span class="text-slate-500">${p.date}</span>
                        <span class="font-mono font-semibold text-emerald-600">${p.amount_formatted}</span>
                    </div>
                `).join('');
            } else {
                recentSection.classList.add('hidden');
            }
        }

        // 4. Subtle green pulse animation on cards to confirm updated state
        const statCard = document.getElementById('stat-fee-card');
        const sidebarCard = document.getElementById('sidebar-fee-card');
        [statCard, sidebarCard].forEach(card => {
            if (card) {
                card.classList.add('ring-2', 'ring-emerald-400', 'ring-offset-2');
                setTimeout(() => {
                    card.classList.remove('ring-2', 'ring-emerald-400', 'ring-offset-2');
                }, 1200);
            }
        });
    }

    let isFetching = false;
    function autoRefreshFeeStatus() {
        if (isFetching) return;
        isFetching = true;
        fetch(feeStatusUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
        .then(res => res.json())
        .then(data => {
            updateFeeStatusUI(data);
        })
        .catch(err => {
            console.warn('Could not auto-refresh fee status:', err);
        })
        .finally(() => {
            isFetching = false;
        });
    }

    // Trigger on 'pageshow' (handles browser back/forward and bfcache restoration!)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.getEntriesByType && window.performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
            autoRefreshFeeStatus();
        } else {
            const lastUpdated = localStorage.getItem('student_fee_updated_' + studentId);
            if (lastUpdated) {
                localStorage.removeItem('student_fee_updated_' + studentId);
                autoRefreshFeeStatus();
            }
        }
    });

    // Trigger when window regains focus or tab becomes visible
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            const lastUpdated = localStorage.getItem('student_fee_updated_' + studentId);
            if (lastUpdated) {
                localStorage.removeItem('student_fee_updated_' + studentId);
                autoRefreshFeeStatus();
            }
        }
    });

    // Listen for storage events across tabs/windows
    window.addEventListener('storage', function(e) {
        if (e.key === 'student_fee_updated_' + studentId) {
            localStorage.removeItem('student_fee_updated_' + studentId);
            autoRefreshFeeStatus();
        }
    });
})();
</script>
@endsection
