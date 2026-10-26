@extends('layouts.app')

@section('title', 'ID Card Audit Trail & Lifecycle History — Erode Public School')

@section('content')
<div class="space-y-6 pb-12">

  {{-- ── 1. Top Header Banner ── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-2xl bg-indigo-900 text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl font-black text-slate-900 tracking-tight">ID Card Audit Trail &amp; Reprint History</h1>
          <span class="badge-blue text-[11px] font-semibold">Security &amp; Compliance</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Cryptographic log of all identity card generation, reprints, replacements, and revocations for students, staff, and visitors.
        </p>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('students.id-card-management') }}" class="btn btn-secondary btn-sm font-bold">
        Student Cards
      </a>
      <a href="{{ route('hr.staff-id-card-management') }}" class="btn btn-secondary btn-sm font-bold">
        Staff Cards
      </a>
      <a href="{{ route('gate.visitor-id-card-management') }}" class="btn btn-secondary btn-sm font-bold">
        Visitor Passes
      </a>
    </div>
  </div>

  {{-- ── 2. Filters ── --}}
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
    <form method="GET" action="{{ route('id-cards.audit-logs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      
      {{-- Card Type --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Card Type</label>
        <select name="card_type" onchange="this.form.submit()" class="form-input text-xs w-full font-semibold">
          <option value="">All Card Types</option>
          <option value="student" {{ $cardType === 'student' ? 'selected' : '' }}>Student Cards</option>
          <option value="staff" {{ $cardType === 'staff' ? 'selected' : '' }}>Staff Cards</option>
          <option value="visitor" {{ $cardType === 'visitor' ? 'selected' : '' }}>Visitor Passes</option>
        </select>
      </div>

      {{-- Action Filter --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Lifecycle Action</label>
        <select name="action" onchange="this.form.submit()" class="form-input text-xs w-full font-semibold">
          <option value="">All Actions</option>
          <option value="created" {{ $action === 'created' ? 'selected' : '' }}>Created / Generated</option>
          <option value="reprinted" {{ $action === 'reprinted' ? 'selected' : '' }}>Reprinted</option>
          <option value="replaced" {{ $action === 'replaced' ? 'selected' : '' }}>Replaced (Lost/Damaged)</option>
          <option value="revoked" {{ $action === 'revoked' ? 'selected' : '' }}>Revoked (Invalidated)</option>
        </select>
      </div>

      {{-- Search --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Search Reason / Card #</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="Card #, reason, notes..." class="form-input text-xs w-full">
      </div>

      {{-- Action buttons --}}
      <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary btn-sm flex-1 font-bold">Apply</button>
        <a href="{{ route('id-cards.audit-logs') }}" class="btn btn-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>

  {{-- ── 3. Audit Log Table ── --}}
  <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
            <th class="p-3.5">Timestamp</th>
            <th class="p-3.5">Card Number</th>
            <th class="p-3.5">Category</th>
            <th class="p-3.5">Action</th>
            <th class="p-3.5">Reason &amp; Remarks</th>
            <th class="p-3.5">Performed By</th>
            <th class="p-3.5">IP Address</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          @forelse($logs as $log)
            <tr class="hover:bg-slate-50/70 transition-colors">
              <td class="p-3.5 font-mono text-[11px] text-slate-500">
                {{ $log->timestamp->timezone('Asia/Kolkata')->format('d/m/Y h:i A') }}
              </td>

              <td class="p-3.5 font-mono font-bold text-slate-900">
                {{ $log->card?->card_number ?? '—' }}
              </td>

              <td class="p-3.5 uppercase font-bold text-[10px]">
                <span class="px-2 py-0.5 rounded-md {{ $log->card?->card_type === 'student' ? 'bg-indigo-50 text-indigo-700' : ($log->card?->card_type === 'staff' ? 'bg-slate-100 text-slate-700' : 'bg-rose-50 text-rose-700') }}">
                  {{ $log->card?->card_type ?? 'Card' }}
                </span>
              </td>

              <td class="p-3.5">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10.5px] font-bold uppercase {{ $log->action === 'created' ? 'bg-emerald-100 text-emerald-800' : ($log->action === 'reprinted' ? 'bg-blue-100 text-blue-800' : ($log->action === 'replaced' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')) }}">
                  {{ $log->action }}
                </span>
              </td>

              <td class="p-3.5 max-w-[280px] text-slate-600">
                {{ $log->reason ?? '—' }}
              </td>

              <td class="p-3.5 font-semibold text-slate-800">
                {{ $log->performer?->name ?? 'System Administrator' }}
              </td>

              <td class="p-3.5 font-mono text-slate-400 text-[10.5px]">
                {{ $log->ip_address ?? '127.0.0.1' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-400 italic">
                No audit log records match the search filter.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($logs->hasPages())
      <div class="p-4 border-t border-slate-100">
        {{ $logs->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
