@extends('layouts.app')
@section('title','Receipt — ' . $payment->receipt_number)
@section('content')
<div class="max-w-xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="{{ $payment->student ? route('students.show', $payment->student->id) : route('fees.index') }}" 
         class="btn-icon" 
         title="Back to Student Profile">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <h1 class="page-title">Fee Receipt</h1>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('fees.student-statement', $payment->student_id) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Fee Statement</span>
      </a>
      <a href="{{ route('fees.receipt.duplicate', $payment->id) }}" class="btn btn-secondary btn-sm" target="_blank">Duplicate PDF</a>
      <button onclick="window.print()" class="btn btn-primary btn-sm">Print</button>
    </div>
  </div>

  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-xs">
      <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span class="font-semibold">{{ session('success') }}</span>
    </div>
  @endif

  <div class="card p-8" id="receipt-print">
    <div class="text-center border-b-2 border-slate-800 pb-4 mb-5">
      <h2 class="text-xl font-extrabold text-slate-800">FEE RECEIPT</h2>
      <p class="text-xs text-slate-500">DASA EduERP</p>
    </div>
    <div class="flex justify-between text-sm mb-4">
      <span class="text-slate-500">Receipt No:</span><span class="font-mono font-bold text-blue-600">{{ $payment->receipt_number }}</span>
    </div>
    <table class="w-full text-sm border-collapse">
      @php
        $feeHeadTitle = $payment->feeHead?->name ?? $payment->term_name ?? ($payment->term_number ? 'Term ' . $payment->term_number : 'Fee Payment');
        $isSplit = $payment->payment_mode === 'split' || ($payment->splits && $payment->splits->count() > 0);
        $rows = [
          ['Student', $payment->student?->full_name],
          ['Admission No.', $payment->student?->admission_no ?? $payment->student?->admission_number],
          ['Class', $payment->student?->currentEnrollment?->class?->name],
          ['Fee Head / Term', $feeHeadTitle],
          ['Payment Date', \Carbon\Carbon::parse($payment->payment_date)->format('d M Y')],
          ['Payment Mode', $isSplit ? 'Split Payment' : ucfirst($payment->payment_mode)],
        ];
        if (!$isSplit && $payment->transaction_id) {
          $rows[] = ['Transaction ID / UTR', $payment->transaction_id];
        }
        if (!$isSplit && $payment->cheque_number) {
          $rows[] = ['Cheque No.', $payment->cheque_number . ($payment->cheque_bank ? ' (' . $payment->cheque_bank . ')' : '')];
        }
        $rows[] = ['Gross Amount', '₹' . number_format($payment->amount, 2)];
        if ($payment->discount > 0) {
          $rows[] = ['Discount', '- ₹' . number_format($payment->discount, 2)];
        }
        $rows[] = ['Total Paid', '₹' . number_format($payment->total_paid, 2)];
        if ((float)($payment->amount_adjusted ?? 0) > 0) {
          $rows[] = ['Amount Adjusted', '₹' . number_format($payment->amount_adjusted, 2)];
        }
        if ((float)($payment->excess_amount ?? 0) > 0) {
          $rows[] = ['Excess Amount Received', '₹' . number_format($payment->excess_amount, 2) . ' (Credited to Advance Wallet)'];
        }
        if ($payment->remarks) {
          $rows[] = ['Adjustment Details', $payment->remarks];
        }
        $rows[] = ['Collected By', $payment->collectedBy?->name ?? 'Administrator'];
      @endphp
      @foreach($rows as [$l, $v])
        <tr class="border-b border-slate-100">
          <td class="py-2.5 text-slate-500 pr-4 w-2/5 font-medium">{{ $l }}</td>
          <td class="py-2.5 font-bold text-slate-800 {{ $l === 'Total Paid' ? 'text-indigo-600 font-black font-mono text-base' : '' }}">{{ $v ?? '—' }}</td>
        </tr>
      @endforeach
    </table>

    {{-- Split Payment Breakdown --}}
    @if($isSplit && $payment->splits && $payment->splits->count() > 0)
      <div class="mt-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2.5">Split Payment Breakdown</p>
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
              <th class="py-1.5 text-left">Method</th>
              <th class="py-1.5 text-left">Reference / Notes</th>
              <th class="py-1.5 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @foreach($payment->splits as $sp)
              <tr>
                <td class="py-2 font-bold text-slate-800">{{ ucfirst(str_replace('_', ' ', $sp->payment_mode)) }}</td>
                <td class="py-2 text-slate-500 font-mono">{{ $sp->transaction_id ?? $sp->cheque_number ?? '—' }}</td>
                <td class="py-2 text-right font-bold font-mono text-slate-900">₹{{ number_format($sp->amount, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="border-t border-slate-200 font-bold text-slate-900">
              <td class="pt-2" colspan="2">Total Split Amount</td>
              <td class="pt-2 text-right font-mono font-black text-indigo-600">₹{{ number_format($payment->total_paid, 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    @endif

    <div class="mt-8 text-right">
      <div class="border-t border-slate-400 inline-block pt-2 text-xs text-slate-500">Signature / Seal</div>
    </div>
  </div>
</div>

@if($payment->student_id)
<script>
  // Signal fee update to Student Profile
  try {
    localStorage.setItem('student_fee_updated_{{ $payment->student_id }}', Date.now().toString());
  } catch(e) {}

  // If user clicks browser back button from this receipt, return directly to Student Profile
  if (window.history && window.history.pushState) {
    window.history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function() {
      window.location.href = "{{ route('students.show', $payment->student_id) }}";
    });
  }
</script>
@endif
@endsection
