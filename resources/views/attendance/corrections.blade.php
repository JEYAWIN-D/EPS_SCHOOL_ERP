@extends('layouts.app')

@section('title', 'Attendance Corrections & Approvals')

@section('content')
<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Attendance</span>
        <span>/</span>
        <span class="text-indigo-600">Corrections Workflow</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Attendance Corrections &amp; Approvals</h1>
      <p class="text-sm text-slate-500 mt-1">Audit trail and administrative authorization for attendance status adjustments.</p>
    </div>
  </div>

  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
      <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
      <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  <!-- Filters -->
  <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
    <form method="GET" action="{{ route('attendance.corrections.index') }}" class="flex flex-wrap items-center gap-3">
      <div>
        <select name="status" class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20">
          <option value="">All Statuses</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Review</option>
          <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
          <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>
      <div>
        <select name="type" class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20">
          <option value="">All Types</option>
          <option value="student" {{ request('type') === 'student' ? 'selected' : '' }}>Student Attendance</option>
          <option value="staff" {{ request('type') === 'staff' ? 'selected' : '' }}>Staff Attendance</option>
        </select>
      </div>
      <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-white transition">Filter</button>
      <a href="{{ route('attendance.corrections.index') }}" class="px-3 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition">Reset</a>
    </form>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            <th class="py-3 px-4">Type &amp; Subject</th>
            <th class="py-3 px-4">Change Requested</th>
            <th class="py-3 px-4">Reason / Justification</th>
            <th class="py-3 px-4">Requested By &amp; Date</th>
            <th class="py-3 px-4">Approval Status</th>
            <th class="py-3 px-4">Reviewed By</th>
            @if($isElevated)
              <th class="py-3 px-4 text-right">Actions</th>
            @endif
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($corrections as $corr)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4">
                @if($corr->attendance_type === 'student')
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 mb-1">
                    Student Attendance
                  </span>
                  <div class="font-bold text-slate-900">{{ $corr->student?->full_name ?? 'Student #' . $corr->student_id }}</div>
                  <div class="text-xs text-slate-400">Adm: {{ $corr->student?->admission_no ?? '—' }}</div>
                @else
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 mb-1">
                    Staff Attendance
                  </span>
                  <div class="font-bold text-slate-900">{{ $corr->employee?->full_name ?? 'Staff #' . $corr->employee_id }}</div>
                  <div class="text-xs text-slate-400">Emp: {{ $corr->employee?->employee_code ?? '—' }}</div>
                @endif
              </td>
              <td class="py-3 px-4 text-xs">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold uppercase text-[10px]">{{ $corr->old_status }}</span>
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                  <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold uppercase text-[10px]">{{ $corr->new_status }}</span>
                </div>
              </td>
              <td class="py-3 px-4 text-xs text-slate-700 max-w-xs">
                <p class="truncate" title="{{ $corr->reason }}">{{ $corr->reason }}</p>
                @if($corr->notes)
                  <p class="text-[11px] text-slate-400 mt-1 italic">Note: {{ $corr->notes }}</p>
                @endif
              </td>
              <td class="py-3 px-4 text-xs text-slate-500">
                <div class="font-semibold text-slate-800">{{ $corr->requester?->name ?? 'User #' . $corr->requested_by }}</div>
                <div class="text-[11px] text-slate-400">{{ $corr->created_at ? $corr->created_at->format('d M Y, h:i A') : '—' }}</div>
              </td>
              <td class="py-3 px-4">
                @if($corr->status === 'approved')
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Approved
                  </span>
                @elseif($corr->status === 'rejected')
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Rejected
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending Approval
                  </span>
                @endif
              </td>
              <td class="py-3 px-4 text-xs text-slate-500">
                @if($corr->approver)
                  <div class="font-semibold text-slate-800">{{ $corr->approver->name }}</div>
                  <div class="text-[11px] text-slate-400">{{ $corr->approved_at ? $corr->approved_at->format('d M Y') : '—' }}</div>
                @else
                  <span class="text-slate-400 italic">—</span>
                @endif
              </td>
              @if($isElevated)
                <td class="py-3 px-4 text-right">
                  @if($corr->status === 'pending')
                    <div class="inline-flex items-center gap-1.5">
                      <form method="POST" action="{{ route('attendance.corrections.review', $corr->id) }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-xs" onclick="return confirm('Approve this attendance status change?');">
                          Approve
                        </button>
                      </form>
                      <form method="POST" action="{{ route('attendance.corrections.review', $corr->id) }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition shadow-xs" onclick="return confirm('Reject this correction request?');">
                          Reject
                        </button>
                      </form>
                    </div>
                  @else
                    <span class="text-xs text-slate-400 italic">Resolved</span>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isElevated ? 7 : 6 }}" class="py-12 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="font-medium text-slate-600">No attendance correction requests</p>
                <p class="text-xs text-slate-400 mt-1">All attendance records are verified and aligned.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($corrections->hasPages())
      <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
        {{ $corrections->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
