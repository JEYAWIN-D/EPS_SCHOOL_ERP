@extends('layouts.app')
@section('title', 'Staff Approvals')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
    <div class="flex items-center gap-3.5">
      <a href="{{ route('hr.index') }}" class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition" title="Back to HR & Payroll">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-md shadow-indigo-100 flex-shrink-0" style="background: linear-gradient(135deg, #4F46E5 0%, #3730A3 100%); color: #FFFFFF;">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <div>
        <h1 class="page-title text-xl font-black text-slate-800" style="font-family:'Plus Jakarta Sans',sans-serif;">Staff Approvals</h1>
        <p class="text-xs font-semibold text-slate-500 mt-0.5">Staff registration register &bull; Review, approve or reject pending staff</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('hr.employees.create') }}" class="btn btn-primary btn-sm flex items-center gap-1.5 text-xs font-bold shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        Add New Staff
      </a>
    </div>
  </div>

  {{-- Filter Toolbar & Status Tabs --}}
  <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      {{-- Status Filter Tabs --}}
      <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
        @foreach([
          'pending'  => ['label' => 'Pending Approval', 'count' => $counts['pending'], 'active_class' => 'bg-indigo-600 text-white'],
          'approved' => ['label' => 'Active / Approved', 'count' => $counts['approved'], 'active_class' => 'bg-emerald-600 text-white'],
          'rejected' => ['label' => 'Rejected',           'count' => $counts['rejected'], 'active_class' => 'bg-rose-600 text-white'],
          'all'      => ['label' => 'All Staff',          'count' => $counts['all'],      'active_class' => 'bg-slate-800 text-white'],
        ] as $key => $tab)
          @php $isActive = ($status === $key); @endphp
          <a href="{{ route('hr.staff-approvals', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
             class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap {{ $isActive ? $tab['active_class'] . ' shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200' }}">
            <span>{{ $tab['label'] }}</span>
            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
              {{ $tab['count'] }}
            </span>
          </a>
        @endforeach
      </div>

      {{-- Search Input --}}
      <form method="GET" action="{{ route('hr.staff-approvals') }}" class="flex items-center gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="relative w-full sm:w-64">
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or ID..."
                 class="input input-sm w-full bg-slate-50 border-slate-200 text-xs rounded-xl focus:bg-white">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm text-xs font-bold">Search</button>
        @if(request('search'))
          <a href="{{ route('hr.staff-approvals', ['status' => $status]) }}" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
        @endif
      </form>
    </div>
  </div>

  {{-- Staff Table --}}
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-black uppercase tracking-wider text-slate-400">
            <th class="py-3 px-4">Staff Member</th>
            <th class="py-3 px-3">Staff ID</th>
            <th class="py-3 px-3">Category</th>
            <th class="py-3 px-3">Department</th>
            <th class="py-3 px-3">Designation</th>
            <th class="py-3 px-3">Joining Date</th>
            <th class="py-3 px-3 text-center">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          @forelse($employees as $emp)
            @php
              $isPending = ($emp->approval_status === 'pending' || $emp->status === 'pending');
              $isApproved = ($emp->approval_status === 'approved' || $emp->status === 'active');
              $isRejected = ($emp->approval_status === 'rejected' || $emp->status === 'rejected');
            @endphp
            <tr class="hover:bg-slate-50/60 transition-colors">
              {{-- Staff Name & Avatar --}}
              <td class="py-3.5 px-4 font-medium text-slate-900">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
                    @if($emp->photo)
                      <img src="{{ asset('storage/' . $emp->photo) }}" class="w-full h-full object-cover rounded-xl" alt="">
                    @else
                      {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                    @endif
                  </div>
                  <div>
                    <a href="{{ route('hr.employees.show', $emp->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                      {{ $emp->full_name }}
                    </a>
                    <p class="text-[11px] text-slate-400 font-mono">{{ $emp->mobile ?? $emp->official_email ?? 'No contact' }}</p>
                  </div>
                </div>
              </td>

              {{-- Staff ID --}}
              <td class="py-3.5 px-3 font-mono font-bold text-slate-700">
                {{ $emp->employee_code ?? 'EMP-' . $emp->id }}
              </td>

              {{-- Staff Category --}}
              <td class="py-3.5 px-3">
                <span class="{{ $emp->category_badge_class }} text-[10px] px-2 py-0.5 font-bold uppercase rounded-md">
                  {{ $emp->category_label }}
                </span>
              </td>

              {{-- Department --}}
              <td class="py-3.5 px-3 text-slate-600 font-medium">
                {{ $emp->department_name }}
              </td>

              {{-- Designation --}}
              <td class="py-3.5 px-3 text-slate-600 font-medium">
                {{ $emp->designation_name }}
              </td>

              {{-- Joining Date --}}
              <td class="py-3.5 px-3 text-slate-600 font-mono">
                {{ $emp->joining_date ? \Carbon\Carbon::parse($emp->joining_date)->format('d M Y') : '—' }}
              </td>

              {{-- Status --}}
              <td class="py-3.5 px-3 text-center">
                @if($isPending)
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Pending Approval
                  </span>
                @elseif($isRejected)
                  <div class="inline-flex flex-col items-center">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">
                      Rejected
                    </span>
                    @if($emp->rejection_reason)
                      <span class="text-[10px] text-rose-600 italic mt-0.5 max-w-[150px] truncate" title="{{ $emp->rejection_reason }}">
                        {{ $emp->rejection_reason }}
                      </span>
                    @endif
                  </div>
                @else
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    Active
                  </span>
                @endif
              </td>

              {{-- Actions --}}
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <a href="{{ route('hr.employees.show', $emp->id) }}" class="btn btn-secondary btn-xs font-bold" title="View Full Profile">
                    View Profile
                  </a>
                  @if($isPending)
                    <form method="POST" action="{{ route('hr.staff-approvals.approve', $emp->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to approve and activate {{ addslashes($emp->full_name) }}?');">
                      @csrf
                      <button type="submit" class="btn btn-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 flex items-center gap-1 shadow-xs" title="Approve Staff Member">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve
                      </button>
                    </form>
                    <form method="POST" action="{{ route('hr.staff-approvals.reject', $emp->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to reject {{ addslashes($emp->full_name) }}?');">
                      @csrf
                      <button type="submit" class="btn btn-xs font-bold text-white bg-rose-600 hover:bg-rose-700 flex items-center gap-1 shadow-xs" title="Reject Staff Member">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-12 text-center text-slate-400">
                <p class="text-sm font-semibold text-slate-500">
                  @if($status === 'pending')
                    No pending staff approvals.
                  @else
                    No staff records found.
                  @endif
                </p>
                <p class="text-xs text-slate-400 mt-1">All new staff registrations requiring review will appear here.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($employees->hasPages())
      <div class="p-4 border-t border-slate-100 bg-slate-50/50">
        {{ $employees->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
