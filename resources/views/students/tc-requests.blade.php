@extends('layouts.app')
@section('title', 'TC Request Workflow')
@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="page-title">Transfer Certificate Requests</h1>
    <button x-data @click="$dispatch('open-modal','new-tc-request')" class="btn btn-primary btn-sm">+ New TC Request</button>
  </div>

  {{-- Filter --}}
  <form method="GET" class="card-flat py-3">
    <div class="flex gap-3">
      <select name="status" class="select w-40">
        <option value="">All Status</option>
        @foreach(['pending'=>'Pending','hod_approved'=>'HOD Approved','principal_approved'=>'Principal Approved','issued'=>'Issued','rejected'=>'Rejected'] as $v=>$l)
          <option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    </div>
  </form>

  <div class="card overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-100"><tr>
        @foreach(['Student','Class','Requested By','Reason','Status','Actions'] as $h)
        <th class="text-left px-4 py-3 text-slate-500 font-medium text-xs uppercase tracking-wide">{{ $h }}</th>
        @endforeach
      </tr></thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($requests as $r)
        @php
          $statusColors = [
            'pending'             => 'badge-amber',
            'hod_approved'        => 'badge-blue',
            'principal_approved'  => 'badge-purple',
            'issued'              => 'badge-green',
            'rejected'            => 'badge-red',
          ];
          $statusLabels = [
            'pending'             => 'Pending',
            'hod_approved'        => 'HOD Approved',
            'principal_approved'  => 'Principal Approved',
            'issued'              => 'Issued',
            'rejected'            => 'Rejected',
          ];
        @endphp
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 font-medium text-slate-800">
            {{ $r->student?->full_name }}
            <div class="text-xs text-slate-400">{{ $r->student?->admission_number }}</div>
          </td>
          <td class="px-4 py-3 text-xs text-slate-500">
            {{ $r->student?->currentEnrollment?->class?->name ?? '—' }}
          </td>
          <td class="px-4 py-3 text-xs text-slate-500">
            {{ ucfirst($r->requested_by_type) }}
            @if($r->requestedByUser) — {{ $r->requestedByUser?->name }}@endif
            <div class="text-slate-400">{{ $r->created_at?->format('d M Y') }}</div>
          </td>
          <td class="px-4 py-3 text-slate-600 text-sm max-w-xs">{{ $r->reason ?? '—' }}</td>
          <td class="px-4 py-3">
            <span class="{{ $statusColors[$r->status] ?? 'badge-slate' }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
          </td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1">
              @if($r->status === 'pending')
                <form method="POST" action="{{ route('students.tc-requests.approve', $r->id) }}" class="inline">
                  @csrf <input type="hidden" name="stage" value="hod">
                  <button class="btn btn-primary btn-xs">HOD Approve</button>
                </form>
              @elseif($r->status === 'hod_approved')
                <form method="POST" action="{{ route('students.tc-requests.approve', $r->id) }}" class="inline">
                  @csrf <input type="hidden" name="stage" value="principal">
                  <button class="btn btn-primary btn-xs">Principal Approve</button>
                </form>
              @elseif($r->status === 'principal_approved')
                @php
                  $dupIssued = \App\Models\TcRequest::where('student_id', $r->student_id)
                    ->where('status', 'issued')->where('id', '!=', $r->id)->exists();
                @endphp
                <form method="POST" action="{{ route('students.tc-requests.approve', $r->id) }}" class="inline"
                  x-data="{dup:{{ $dupIssued ? 'true' : 'false' }}}">
                  @csrf <input type="hidden" name="stage" value="issue">
                  <div x-show="dup" class="text-xs text-red-500 mb-1">⚠ TC already issued. Tick to override:</div>
                  <label x-show="dup" class="flex items-center gap-1 text-xs mb-1">
                    <input type="checkbox" name="force_issue" value="1"> Override — issue duplicate TC
                  </label>
                  <button class="btn btn-green btn-xs">Issue TC</button>
                </form>
              @endif
              @if(in_array($r->status, ['pending','hod_approved']))
              <form method="POST" action="{{ route('students.tc-requests.reject', $r->id) }}" class="inline"
                x-data="{r:''}" @submit.prevent="if(r.trim()){$el.querySelector('[name=reason]').value=r;$el.submit()}else{alert('Enter rejection reason')}">
                @csrf
                <input type="hidden" name="reason">
                <input type="text" x-model="r" placeholder="Reason..." class="input text-xs py-1 w-32">
                <button type="submit" class="btn btn-red btn-xs ml-1">Reject</button>
              </form>
              @endif
              @if($r->status === 'issued')
              <a href="{{ route('students.tc.form', $r->student_id) }}" class="btn btn-secondary btn-xs">View / Print TC</a>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No TC requests found.</td></tr>
        @endforelse
      </tbody>
    </table>
    @if($requests->hasPages())
    <div class="px-4 pb-3 text-sm">{{ $requests->links() }}</div>
    @endif
  </div>
</div>

@push('modals')
<div x-data="{show:false}" @open-modal.window="if($event.detail==='new-tc-request') show=true"
  x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="show=false">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <h2 class="font-semibold text-slate-800">New TC Request</h2>
      <button @click="show=false" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
    </div>
    <form method="POST" action="{{ route('students.tc-requests.store') }}" class="px-6 py-5 space-y-4">
      @csrf
      <div>
        <label class="label">Student Admission No / Name</label>
        <input type="text" name="student_search" class="input w-full" placeholder="Search student..."
          x-data="{}" @input.debounce="/* live search hook */">
        <input type="hidden" name="student_id" id="tc_student_id">
        <p class="text-xs text-slate-400 mt-1">Enter admission number directly in the field below if search is unavailable.</p>
      </div>
      <div>
        <label class="label">Student ID (direct)</label>
        <input type="number" name="student_id" class="input w-full" placeholder="Student ID">
      </div>
      <div>
        <label class="label">Reason for TC</label>
        <textarea name="reason" rows="3" class="input w-full" placeholder="Reason for leaving..."></textarea>
      </div>
      <div class="flex gap-3 justify-end">
        <button type="button" @click="show=false" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
      </div>
    </form>
  </div>
</div>
@endpush
@endsection
