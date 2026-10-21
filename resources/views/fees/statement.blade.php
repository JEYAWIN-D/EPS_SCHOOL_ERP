@extends('layouts.app')
@section('title', 'Student Fee Statement — ' . $student->full_name)

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-20">

  {{-- Top Navigation & Action Controls --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
    <div class="flex items-center gap-3">
      <a href="{{ route('students.show', $student->id) }}" 
         class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-xs hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-all"
         title="Back to Student Profile">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Student Accounting / Statements</h2>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Student Fee Statement</h1>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button onclick="window.print()" 
              class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition flex items-center gap-2 cursor-pointer">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print Statement</span>
      </button>

      {{-- Prominent "Payment Received" Button --}}
      <a href="{{ route('fees.collect', ['student_id' => $student->id]) }}" 
         class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span>Payment Received</span>
      </a>
    </div>
  </div>

  {{-- Printable Statement Document Container --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-10 space-y-8" id="statement-print-area">
    
    {{-- Official School Header Banner --}}
    <div class="border-b-2 border-slate-900 pb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-4 text-center sm:text-left">
        <div class="w-16 h-16 rounded-2xl bg-[#8C2826] text-white flex items-center justify-center font-black text-2xl shadow-sm shrink-0">
          EPS
        </div>
        <div>
          <h2 class="text-xl font-black text-slate-900 tracking-tight">ERODE PUBLIC SCHOOL</h2>
          <p class="text-xs text-slate-500 font-medium">Affiliated to CBSE, New Delhi &bull; Affiliation No: 1931585</p>
          <p class="text-[11px] text-slate-400 mt-0.5">Chennimalai Road, Erode, Tamil Nadu 638051 &bull; Phone: +91 94433 12345</p>
        </div>
      </div>
      <div class="text-center sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
        <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-slate-900 text-white">
          OFFICIAL FEE STATEMENT
        </span>
        <p class="text-[11px] text-slate-400 font-mono mt-1">Generated: {{ now()->format('d M Y, h:i A') }}</p>
      </div>
    </div>

    {{-- Student Demographics Card --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Student Name</span>
        <p class="font-extrabold text-slate-900 text-sm mt-0.5">{{ $student->full_name }}</p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Admission Number</span>
        <p class="font-bold font-mono text-indigo-700 text-sm mt-0.5">{{ $student->admission_no ?? $student->admission_number }}</p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Class &amp; Section</span>
        <p class="font-bold text-slate-800 text-sm mt-0.5">
          {{ $student->currentEnrollment?->class?->name ?? 'Unassigned' }}
          @if($student->currentEnrollment?->section) &bull; Sec {{ $student->currentEnrollment->section->name }} @endif
        </p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Roll Number</span>
        <p class="font-bold font-mono text-slate-800 text-sm mt-0.5">{{ $student->roll_number ?? $student->currentEnrollment?->roll_number ?? '—' }}</p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Parent / Guardian</span>
        <p class="font-bold text-slate-800 mt-0.5">{{ $student->father_name ?? $student->guardian_name ?? '—' }}</p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Parent Mobile</span>
        <p class="font-bold font-mono text-slate-800 mt-0.5">{{ $student->father_mobile ?? $student->mobile ?? '—' }}</p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Academic Year</span>
        <p class="font-bold text-slate-800 mt-0.5">{{ $student->currentEnrollment?->academicYear?->name ?? $currentYear?->name ?? '2026-2027' }}</p>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Payment Plan</span>
        <p class="font-bold text-indigo-700 uppercase mt-0.5">{{ str_replace('_', ' ', $student->payment_terms ?? 'Single Payment') }}</p>
      </div>
    </div>

    {{-- Five Key Metric Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Fees Payable</span>
        <p class="text-xl font-black font-mono text-slate-900 mt-1">₹{{ number_format($totalFeesPayable, 2) }}</p>
      </div>
      <div class="p-4 rounded-xl bg-emerald-50/80 border border-emerald-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Total Amount Paid</span>
        <p class="text-xl font-black font-mono text-emerald-700 mt-1">₹{{ number_format($totalPaid, 2) }}</p>
      </div>
      <div class="p-4 rounded-xl bg-blue-50/80 border border-blue-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-800">Amount Adjusted</span>
        <p class="text-xl font-black font-mono text-blue-700 mt-1">₹{{ number_format($totalAdjusted, 2) }}</p>
      </div>
      <div class="p-4 rounded-xl bg-purple-50/80 border border-purple-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800">Excess / Advance</span>
        <p class="text-xl font-black font-mono text-purple-700 mt-1">₹{{ number_format($excessAmount, 2) }}</p>
      </div>
      <div class="p-4 rounded-xl bg-rose-50/80 border border-rose-200/80 col-span-2 sm:col-span-1">
        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-800">Outstanding Balance</span>
        <p class="text-xl font-black font-mono text-rose-600 mt-1">₹{{ number_format($outstandingBalance, 2) }}</p>
      </div>
    </div>

    {{-- SECTION 1: TERM-WISE FEE SCHEDULE & REMAINING AMOUNT PAYABLE --}}
    <div class="space-y-3">
      <div class="flex items-center justify-between border-b border-slate-200 pb-2">
        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
          <span>📅</span> Term-Wise Fee Schedule &amp; Remaining Amount Payable
        </h3>
        <span class="text-xs font-bold text-slate-500">
          Status: <span class="capitalize {{ $outstandingBalance <= 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $student->payment_status ?? 'Pending' }}</span>
        </span>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-xs text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
              <th class="py-3 px-4">Term</th>
              <th class="py-3 px-4">Due Date</th>
              <th class="py-3 px-4 text-right">Scheduled Fee</th>
              <th class="py-3 px-4 text-right text-emerald-700">Paid / Adjusted</th>
              <th class="py-3 px-4 text-right text-rose-700">Remaining Amount Payable</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4">Payment Details</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse($terms as $term)
              @php
                $tAmt = (float)($term['amount'] ?? 0);
                $tPaid = (float)($term['paid'] ?? 0);
                $tPending = (float)($term['pending'] ?? 0);
                $tStatus = $term['status'] ?? ($tPending <= 0 ? 'paid' : ($tPaid > 0 ? 'partially_paid' : 'pending'));
                $statusBadge = match($tStatus) {
                  'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                  'partially_paid' => 'bg-amber-100 text-amber-800 border-amber-200',
                  default => 'bg-slate-100 text-slate-600 border-slate-200'
                };
              @endphp
              <tr class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-4 font-bold text-slate-900">{{ $term['name'] ?? ('Term ' . ($term['term_number'] ?? $loop->iteration)) }}</td>
                <td class="py-3 px-4 font-mono text-slate-600">
                  {{ !empty($term['due_date']) ? \Carbon\Carbon::parse($term['due_date'])->format('d-m-Y') : '—' }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">₹{{ number_format($tAmt, 2) }}</td>
                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">₹{{ number_format($tPaid, 2) }}</td>
                <td class="py-3 px-4 text-right font-mono font-black {{ $tPending <= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                  ₹{{ number_format($tPending, 2) }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border {{ $statusBadge }}">
                    {{ str_replace('_', ' ', $tStatus) }}
                  </span>
                </td>
                <td class="py-3 px-4 text-slate-600">
                  @if($tPaid > 0 && !empty($term['payment_mode']))
                    <span class="font-bold text-slate-800">{{ $term['payment_mode'] }}</span>
                    @if(!empty($term['payment_date']))
                      <span class="text-[10px] text-slate-400 font-mono block">{{ \Carbon\Carbon::parse($term['payment_date'])->format('d-m-Y') }}</span>
                    @endif
                  @else
                    <span class="text-slate-400">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="py-4 px-4 text-center text-slate-400 italic">No term schedule recorded for this student.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr class="bg-slate-50 font-black text-slate-900 border-t-2 border-slate-200">
              <td class="py-3 px-4" colspan="2">TOTALS</td>
              <td class="py-3 px-4 text-right font-mono">₹{{ number_format($totalFeesPayable, 2) }}</td>
              <td class="py-3 px-4 text-right font-mono text-emerald-700">₹{{ number_format($totalAdjusted, 2) }}</td>
              <td class="py-3 px-4 text-right font-mono text-rose-700">₹{{ number_format($outstandingBalance, 2) }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2 py-0.5 rounded text-[10px] uppercase {{ $outstandingBalance <= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                  {{ $outstandingBalance <= 0 ? 'FULLY PAID' : 'PENDING' }}
                </span>
              </td>
              <td class="py-3 px-4 text-[10px] text-slate-500 font-normal">All dues reconciled</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- SECTION 2: PAYMENT HISTORY & RECEIPT RECONCILIATION --}}
    <div class="space-y-3">
      <div class="flex items-center justify-between border-b border-slate-200 pb-2">
        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
          <span>🧾</span> Payment History &amp; Receipts
        </h3>
        <span class="text-xs font-bold text-slate-500">{{ $allPayments->count() }} Payments Recorded</span>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-xs text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
              <th class="py-3 px-4">Receipt #</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Description / Terms Satisfied</th>
              <th class="py-3 px-4 text-right">Amount Paid</th>
              <th class="py-3 px-4 text-right text-blue-700">Amount Adjusted</th>
              <th class="py-3 px-4 text-right text-purple-700">Excess Amount</th>
              <th class="py-3 px-4">Mode / Account</th>
              <th class="py-3 px-4">Collected By</th>
              <th class="py-3 px-4 text-center print:hidden">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse($allPayments as $p)
              <tr class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-4 font-mono font-bold text-indigo-700">
                  <a href="{{ route('fees.receipt', $p->id) }}" class="hover:underline">
                    {{ $p->receipt_number }}
                  </a>
                </td>
                <td class="py-3 px-4 font-mono text-slate-700">
                  {{ \Carbon\Carbon::parse($p->payment_date)->format('d-m-Y') }}
                </td>
                <td class="py-3 px-4 text-slate-800">
                  <span class="font-bold">{{ $p->term_name ?? ($p->term_number ? 'Term ' . $p->term_number : 'Fee Payment') }}</span>
                  @if($p->remarks)
                    <span class="text-[10px] text-slate-500 block truncate max-w-xs">{{ $p->remarks }}</span>
                  @endif
                </td>
                <td class="py-3 px-4 text-right font-mono font-black text-slate-900">
                  ₹{{ number_format($p->total_paid, 2) }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-bold text-blue-700">
                  ₹{{ number_format($p->amount_adjusted > 0 ? $p->amount_adjusted : $p->total_paid, 2) }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-bold text-purple-700">
                  ₹{{ number_format($p->excess_amount ?? 0, 2) }}
                </td>
                <td class="py-3 px-4 text-slate-700">
                  <span class="font-bold capitalize">{{ str_replace('_', ' ', $p->payment_mode) }}</span>
                  @if($p->transaction_id)
                    <span class="text-[10px] text-slate-400 font-mono block">Ref: {{ $p->transaction_id }}</span>
                  @elseif($p->cheque_number)
                    <span class="text-[10px] text-slate-400 font-mono block">Chq: {{ $p->cheque_number }}</span>
                  @endif
                </td>
                <td class="py-3 px-4 text-slate-600">
                  {{ $p->collectedBy?->name ?? 'Administrator' }}
                </td>
                <td class="py-3 px-4 text-center print:hidden">
                  <a href="{{ route('fees.receipt', $p->id) }}" 
                     class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold transition">
                    Receipt &rarr;
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="py-6 px-4 text-center text-slate-400 italic">No payments have been recorded for this student yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Official Institutional Sign-Off Section --}}
    <div class="pt-8 border-t border-slate-300 grid grid-cols-2 gap-12 text-xs">
      <div>
        <p class="font-bold text-slate-800">Accounts Department Sign &amp; Date</p>
        <div class="h-12 border-b border-slate-400 border-dashed"></div>
        <p class="text-[10px] text-slate-400 mt-1">Verified &amp; Reconciled with School General Ledger</p>
      </div>
      <div class="text-right">
        <p class="font-bold text-slate-800">Principal / Authorized Signatory</p>
        <div class="h-12 border-b border-slate-400 border-dashed"></div>
        <p class="text-[10px] text-slate-400 mt-1">Official Institutional Seal</p>
      </div>
    </div>

  </div>
</div>
@endsection
