@extends('layouts.app')
@section('title', 'Visitor Profile - ' . $visitor->visitor_name)
@section('content')
<div class="max-w-4xl mx-auto space-y-6">

  {{-- Top Navigation & Action Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
      <a href="{{ route('gate.index') }}" class="hover:text-indigo-600 transition">Gate Management</a>
      <span>/</span>
      <span class="text-slate-800">{{ $visitor->pass_number }}</span>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('gate.pass', $visitor->id) }}" class="btn-primary text-xs sm:text-sm font-semibold flex items-center gap-1.5 shadow-sm">
        🖨 Print Gate Pass
      </a>
      <a href="{{ route('gate.index') }}" class="btn-secondary text-xs sm:text-sm font-semibold">
        ← Back to Gate Log
      </a>
    </div>
  </div>

  @if(session('success'))
  <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 text-sm font-medium flex items-center gap-2.5 shadow-xs">
    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    <span>{{ session('success') }}</span>
  </div>
  @endif

  @php
    $catBadge = $visitor->category_badge_classes;
    $statusBadge = $visitor->status_badge_classes;
  @endphp

  {{-- Main Identity Hero Card --}}
  <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs relative overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
      
      <div class="flex items-center gap-5">
        {{-- Photo --}}
        <div class="relative flex-shrink-0">
          @if($visitor->visitor_photo)
          <img src="{{ Storage::url($visitor->visitor_photo) }}" alt="{{ $visitor->visitor_name }}" class="w-20 h-20 rounded-2xl object-cover border-2 border-slate-200 shadow-md">
          @else
          <div class="w-20 h-20 rounded-2xl bg-slate-100 border-2 border-slate-200 flex items-center justify-center text-2xl font-black text-slate-600">
            {{ strtoupper(substr($visitor->visitor_name, 0, 2)) }}
          </div>
          @endif

          <span class="absolute -bottom-1 -right-1 bg-slate-900 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">
            {{ $visitor->visitor_count }} Pax
          </span>
        </div>

        {{-- Info --}}
        <div class="space-y-1">
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl font-black text-slate-900">{{ $visitor->visitor_name }}</h1>
            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold border {{ $catBadge['bg'] }}">
              {{ $visitor->category_label }}
            </span>
          </div>

          <p class="text-xs text-slate-500 font-mono flex items-center gap-2">
            <span>📞 {{ $visitor->visitor_phone ?? 'No phone' }}</span>
            @if($visitor->visitor_email)
            <span>✉ {{ $visitor->visitor_email }}</span>
            @endif
          </p>

          <div class="flex items-center gap-2 pt-1">
            <span class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
              Pass #: {{ $visitor->pass_number }}
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusBadge['bg'] }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
              {{ $statusBadge['text'] }}
            </span>
          </div>
        </div>
      </div>

      {{-- Top Workflow Actions --}}
      <div class="flex flex-col items-end gap-2 w-full sm:w-auto">
        @if($visitor->status === \App\Models\Visitor::STATUS_PENDING)
        <div class="flex items-center gap-2">
          <form method="POST" action="{{ route('gate.approve', $visitor->id) }}">
            @csrf
            <button type="submit" class="btn-primary text-xs px-4 py-2 bg-emerald-600 hover:bg-emerald-700 font-bold shadow-sm">
              ✓ Approve Entry
            </button>
          </form>
          <form method="POST" action="{{ route('gate.reject', $visitor->id) }}" onsubmit="return confirm('Are you sure you want to reject this visit?')">
            @csrf
            <input type="hidden" name="rejection_reason" value="Host rejected visit request at reception.">
            <button type="submit" class="btn-secondary text-xs px-3 py-2 text-red-600 hover:bg-red-50 font-bold">
              ✕ Reject
            </button>
          </form>
        </div>
        @elseif($visitor->isInside())
        <form method="POST" action="{{ route('gate.checkout', $visitor->id) }}">
          @csrf @method('PATCH')
          <button type="submit" class="btn-primary text-xs px-4 py-2 bg-slate-900 hover:bg-slate-800 font-bold shadow-sm">
            ✓ Check Out Visitor
          </button>
        </form>
        @endif
      </div>

    </div>
  </div>

  {{-- Details Grid --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Left 2 Columns: Visit & Category Data --}}
    <div class="lg:col-span-2 space-y-6">
      
      {{-- Visit Specifications --}}
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">Visit Specifications</h2>

        <div class="grid grid-cols-2 gap-4 text-xs">
          <div>
            <span class="text-slate-400 block font-medium">Purpose of Visit</span>
            <p class="font-bold text-slate-800 mt-0.5">{{ $visitor->purpose }}</p>
          </div>

          <div>
            <span class="text-slate-400 block font-medium">Department</span>
            <p class="font-bold text-slate-800 mt-0.5">{{ $visitor->department ?? 'General' }}</p>
          </div>

          <div>
            <span class="text-slate-400 block font-medium">Person to Meet (Host)</span>
            <p class="font-bold text-slate-800 mt-0.5">{{ $visitor->whom_to_meet ?? '—' }}</p>
          </div>

          <div>
            <span class="text-slate-400 block font-medium">Total Visitors</span>
            <p class="font-bold text-slate-800 mt-0.5">{{ $visitor->visitor_count }} Person(s)</p>
          </div>

          @if($visitor->remarks)
          <div class="col-span-2 bg-slate-50 p-3 rounded-xl border border-slate-200/60">
            <span class="text-slate-400 block font-medium">Gatekeeper Remarks</span>
            <p class="text-slate-700 mt-0.5">{{ $visitor->remarks }}</p>
          </div>
          @endif

          @if($visitor->items_carried)
          <div class="col-span-2 bg-amber-50/60 p-3 rounded-xl border border-amber-200">
            <span class="text-amber-800 block font-bold">Tools / Items Carried In</span>
            <p class="text-amber-900 mt-0.5">{{ $visitor->items_carried }}</p>
          </div>
          @endif
        </div>
      </div>

      {{-- Dynamic ERP Module Link Card --}}
      @if($visitor->category === \App\Models\Visitor::CATEGORY_PARENT && $visitor->student)
      <div class="bg-emerald-50/70 rounded-2xl border border-emerald-200 p-5 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-900">Linked Student Details</h2>
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div>
            <span class="text-emerald-700 block">Student Name:</span>
            <p class="font-bold text-emerald-950">{{ $visitor->student->first_name }} {{ $visitor->student->last_name }}</p>
          </div>
          <div>
            <span class="text-emerald-700 block">Admission Number:</span>
            <p class="font-mono font-bold text-emerald-950">{{ $visitor->student->admission_no }}</p>
          </div>
          <div>
            <span class="text-emerald-700 block">Relationship:</span>
            <p class="font-bold text-emerald-950">{{ $visitor->relationship_to_student ?? 'Guardian' }}</p>
          </div>
          <div>
            <span class="text-emerald-700 block">Student Emergency Mobile:</span>
            <p class="font-mono text-emerald-950">{{ $visitor->student->mobile ?? '—' }}</p>
          </div>
        </div>
      </div>
      @elseif($visitor->category === \App\Models\Visitor::CATEGORY_ADMISSION)
      <div class="bg-blue-50/70 rounded-2xl border border-blue-200 p-5 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-blue-900">Admission CRM Lead Link</h2>
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div>
            <span class="text-blue-700 block">Prospective Child:</span>
            <p class="font-bold text-blue-950">{{ $visitor->child_name ?? '—' }}</p>
          </div>
          <div>
            <span class="text-blue-700 block">Grade Applying For:</span>
            <p class="font-bold text-blue-950">{{ $visitor->grade_applying_for ?? '—' }}</p>
          </div>
          <div>
            <span class="text-blue-700 block">Enquiry Source:</span>
            <p class="font-bold text-blue-950">{{ $visitor->enquiry_source ?? 'Walk-in' }}</p>
          </div>
          @if($visitor->enquiry_id)
          <div>
            <span class="text-blue-700 block">CRM Lead Record:</span>
            <a href="{{ route('admissions.enquiries.show', $visitor->enquiry_id) }}" class="font-bold text-blue-600 underline">View Enquiry #{{ $visitor->enquiry_id }} →</a>
          </div>
          @endif
        </div>
      </div>
      @elseif($visitor->category === \App\Models\Visitor::CATEGORY_VENDOR)
      <div class="bg-amber-50/70 rounded-2xl border border-amber-200 p-5 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-amber-900">Vendor &amp; Maintenance Details</h2>
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div>
            <span class="text-amber-700 block">Agency Name:</span>
            <p class="font-bold text-amber-950">{{ $visitor->company_name ?? '—' }}</p>
          </div>
          <div>
            <span class="text-amber-700 block">Work Order Number:</span>
            <p class="font-mono font-bold text-amber-950">{{ $visitor->work_order_number ?? '—' }}</p>
          </div>
        </div>
      </div>
      @elseif($visitor->category === \App\Models\Visitor::CATEGORY_INTERVIEW)
      <div class="bg-purple-50/70 rounded-2xl border border-purple-200 p-5 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-purple-900">HR Recruitment Details</h2>
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div>
            <span class="text-purple-700 block">Role Applied For:</span>
            <p class="font-bold text-purple-950">{{ $visitor->job_role_applied ?? '—' }}</p>
          </div>
          <div>
            <span class="text-purple-700 block">Candidate Reference ID:</span>
            <p class="font-mono font-bold text-purple-950">{{ $visitor->candidate_ref_number ?? '—' }}</p>
          </div>
        </div>
      </div>
      @endif

      {{-- ID Proof Verification --}}
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">Government ID Verification</h2>
        <div class="grid grid-cols-2 gap-4 text-xs">
          <div>
            <span class="text-slate-400 block font-medium">ID Proof Document</span>
            <p class="font-bold text-slate-800 mt-0.5">{{ $visitor->visitor_id_type ?? 'Not Provided' }}</p>
          </div>
          <div>
            <span class="text-slate-400 block font-medium">ID Number</span>
            <p class="font-mono font-bold text-slate-800 mt-0.5">{{ $visitor->visitor_id_number ?? '—' }}</p>
          </div>

          @if($visitor->id_proof_document)
          <div class="col-span-2 pt-2">
            <span class="text-slate-400 block font-medium mb-1">Attached Document File</span>
            <a href="{{ Storage::url($visitor->id_proof_document) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold border border-indigo-200 hover:bg-indigo-100 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              View Scanned ID Proof Document ↗
            </a>
          </div>
          @endif
        </div>
      </div>

    </div>

    {{-- Right 1 Column: Timings & Timeline --}}
    <div class="space-y-6">
      
      {{-- Timing & Duration Box --}}
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">Visit Timings &amp; Duration</h2>

        <div class="space-y-3 text-xs">
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Visit Date:</span>
            <span class="font-bold text-slate-800">{{ $visitor->visit_date?->format('d M Y') }}</span>
          </div>

          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Check-In Time:</span>
            <span class="font-mono font-bold text-indigo-600">{{ $visitor->in_time ? $visitor->in_time->format('h:i A') : 'Pending' }}</span>
          </div>

          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Expected Exit:</span>
            <span class="font-mono font-semibold text-slate-700">{{ $visitor->expected_exit_time ? $visitor->expected_exit_time->format('h:i A') : 'Not specified' }}</span>
          </div>

          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Actual Exit Time:</span>
            <span class="font-mono font-bold {{ $visitor->out_time ? 'text-emerald-700' : 'text-slate-400' }}">
              {{ $visitor->out_time ? $visitor->out_time->format('h:i A') : 'Inside Premises' }}
            </span>
          </div>

          @if($visitor->in_time && $visitor->out_time)
          @php
            $diffMins = $visitor->in_time->diffInMinutes($visitor->out_time);
            $hrs = floor($diffMins / 60);
            $mins = $diffMins % 60;
          @endphp
          <div class="flex justify-between bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 font-bold">
            <span class="text-slate-600">Total Duration:</span>
            <span class="text-indigo-700">{{ $hrs > 0 ? "{$hrs}h " : "" }}{{ $mins }} mins</span>
          </div>
          @endif
        </div>
      </div>

      {{-- Vehicle Details Box --}}
      @if($visitor->vehicle_number)
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">Vehicle Information</h2>
        <div class="text-xs space-y-1.5">
          <div class="flex justify-between">
            <span class="text-slate-500">Type:</span>
            <span class="font-semibold text-slate-800">{{ ucfirst(str_replace('_', ' ', $visitor->vehicle_type)) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Plate Number:</span>
            <span class="font-mono font-bold text-slate-900 uppercase">{{ $visitor->vehicle_number }}</span>
          </div>
        </div>
      </div>
      @endif

      {{-- Audit & Security Details --}}
      <div class="bg-slate-50 rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-2.5 text-[11px] text-slate-500">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-200 pb-1.5">Security Audit Log</h2>
        <p>Logged By: <strong class="text-slate-700">{{ $visitor->loggedBy?->name ?? 'System' }}</strong></p>
        <p>Record Created: {{ $visitor->created_at?->format('d M Y, h:i A') }}</p>
        @if($visitor->approved_by)
        <p>Approved By: <strong class="text-emerald-700">{{ $visitor->approver?->name ?? 'Host' }}</strong> at {{ $visitor->approved_at?->format('h:i A') }}</p>
        @endif
        @if($visitor->rejected_by)
        <p class="text-red-700">Rejected By: <strong>{{ $visitor->rejecter?->name ?? 'Staff' }}</strong> ({{ $visitor->rejection_reason }})</p>
        @endif
      </div>

    </div>

  </div>

</div>
@endsection
