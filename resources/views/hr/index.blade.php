@extends('layouts.app')
@section('title', 'HR & Payroll')

@section('content')
<div class="space-y-6">

  {{-- ── 1. Page Header ────────────────────────────────────────── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-md shadow-indigo-100 flex-shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
      </div>
      <div>
        <h1 class="page-title text-xl font-black text-slate-800" style="font-family:'Plus Jakarta Sans',sans-serif;">HR & Payroll</h1>
        <p class="text-xs font-semibold text-slate-500 mt-0.5">Staff & Attendance Management &bull; {{ \Carbon\Carbon::parse($today ?? now())->format('l, d F Y') }}</p>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('hr.id-card-studio') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold text-rose-900 bg-rose-50 border-rose-200 hover:bg-rose-100 shadow-2xs">
        <svg class="w-4 h-4 text-rose-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
        Staff ID Cards
      </a>
      <a href="{{ route('hr.employees') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
        </svg>
        Staff Directory
      </a>
      <a href="{{ route('hr.employees.create') }}" class="btn btn-primary btn-sm flex items-center gap-1.5 text-xs font-bold shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
        </svg>
        Add Staff
      </a>
    </div>
  </div>

  {{-- ── 2. Live Summary Metrics (Main Dashboard Style) ── --}}
  <div class="space-y-3">
    <div class="flex items-center justify-between pb-1 flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <h2 class="text-sm font-bold text-slate-700 tracking-tight flex items-center gap-2" style="font-family:'Plus Jakarta Sans',sans-serif;">
          <span>Key Performance Metrics</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/70">
            9 active
          </span>
        </h2>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">

      {{-- Card 1: Total Staff --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Active</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-slate-800 tracking-tight text-xl font-bold">{{ $totalStaff }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Total Staff">Total Staff</p>
          </div>
        </div>

        <a href="{{ route('hr.employees') }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View staff &rarr;</span>
        </a>
      </div>

      {{-- Card 2: Present Today --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Today</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-emerald-600 tracking-tight text-xl font-bold">
              {{ $presentToday }}<span class="text-xs font-semibold text-slate-400 font-sans ml-1">/ {{ $totalStaff }}</span>
            </p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Present Today">Present Today</p>
          </div>
        </div>

        <a href="{{ route('hr.attendance.view', ['date' => $today, 'status' => 'present']) }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View attendance &rarr;</span>
        </a>
      </div>

      {{-- Card 3: Absent Today --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Today</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-rose-600 tracking-tight text-xl font-bold">{{ $absentToday }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Absent Today">Absent Today</p>
          </div>
        </div>

        <a href="{{ route('hr.attendance.absent', ['date' => $today]) }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View absent &rarr;</span>
        </a>
      </div>

      {{-- Card 4: On Duty --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Today</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-sky-600 tracking-tight text-xl font-bold">{{ $onDutyToday }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="On Duty">On Duty</p>
          </div>
        </div>

        <a href="{{ route('hr.attendance.on-duty', ['date' => $today]) }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View On Duty &rarr;</span>
        </a>
      </div>

      {{-- Card 5: Paid Off --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Today</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-purple-600 tracking-tight text-xl font-bold">{{ $paidOffToday }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Paid Off">Paid Off</p>
          </div>
        </div>

        <a href="{{ route('hr.attendance.paid-off', ['date' => $today]) }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View Paid Off &rarr;</span>
        </a>
      </div>

      {{-- Card 6: Permission --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Today</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-amber-600 tracking-tight text-xl font-bold">{{ $permissionToday }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Permission">Permission</p>
          </div>
        </div>

        <a href="{{ route('hr.attendance.permission', ['date' => $today]) }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View Permission &rarr;</span>
        </a>
      </div>

      {{-- Card 7: On Leave Today --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Today</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-orange-600 tracking-tight text-xl font-bold">{{ $onLeaveToday }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="On Leave Today">On Leave Today</p>
          </div>
        </div>

        <a href="{{ route('hr.leave-approvals') }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>Review leaves &rarr;</span>
        </a>
      </div>

      {{-- Card 8: Staff Approvals --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Action</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-indigo-600 tracking-tight text-xl font-bold">{{ $pendingStaffApprovals }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Staff Approvals">Staff Approvals</p>
          </div>
        </div>

        <a href="{{ route('hr.staff-approvals') }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View approvals &rarr;</span>
        </a>
      </div>

      {{-- Card 9: Leave Approvals --}}
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Action</span>
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-teal-600 tracking-tight text-xl font-bold">{{ $pendingLeaves }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="Leave Approvals">Leave Approvals</p>
          </div>
        </div>

        <a href="{{ route('hr.leave-approvals') }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
          <span>View requests &rarr;</span>
        </a>
      </div>

    </div>
  </div>

  {{-- ── 3. Primary Quick Action Cards (6 Main Actions) ───────────── --}}
  <style>
    .action-icon-box {
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .card-theme-indigo .action-icon-box { background-color: #EEF2FF !important; border: 1.5px solid #C7D2FE !important; color: #4338CA !important; }
    .card-theme-indigo:hover .action-icon-box, .card-theme-indigo:focus .action-icon-box, .card-theme-indigo:active .action-icon-box { background-color: #4F46E5 !important; border-color: #4F46E5 !important; color: #FFFFFF !important; }
    .card-theme-indigo .action-icon-box svg { color: inherit !important; }

    .card-theme-amber .action-icon-box { background-color: #FEF3C7 !important; border: 1.5px solid #FDE68A !important; color: #B45309 !important; }
    .card-theme-amber:hover .action-icon-box, .card-theme-amber:focus .action-icon-box, .card-theme-amber:active .action-icon-box { background-color: #D97706 !important; border-color: #D97706 !important; color: #FFFFFF !important; }
    .card-theme-amber .action-icon-box svg { color: inherit !important; }

    .card-theme-emerald .action-icon-box { background-color: #ECFDF5 !important; border: 1.5px solid #A7F3D0 !important; color: #047857 !important; }
    .card-theme-emerald:hover .action-icon-box, .card-theme-emerald:focus .action-icon-box, .card-theme-emerald:active .action-icon-box { background-color: #059669 !important; border-color: #059669 !important; color: #FFFFFF !important; }
    .card-theme-emerald .action-icon-box svg { color: inherit !important; }

    .card-theme-blue .action-icon-box { background-color: #EFF6FF !important; border: 1.5px solid #BFDBFE !important; color: #1D4ED8 !important; }
    .card-theme-blue:hover .action-icon-box, .card-theme-blue:focus .action-icon-box, .card-theme-blue:active .action-icon-box { background-color: #2563EB !important; border-color: #2563EB !important; color: #FFFFFF !important; }
    .card-theme-blue .action-icon-box svg { color: inherit !important; }

    .card-theme-purple .action-icon-box { background-color: #FAF5FF !important; border: 1.5px solid #E9D5FF !important; color: #7E22CE !important; }
    .card-theme-purple:hover .action-icon-box, .card-theme-purple:focus .action-icon-box, .card-theme-purple:active .action-icon-box { background-color: #9333EA !important; border-color: #9333EA !important; color: #FFFFFF !important; }
    .card-theme-purple .action-icon-box svg { color: inherit !important; }

    .card-theme-rose .action-icon-box { background-color: #FFF1F2 !important; border: 1.5px solid #FECDD3 !important; color: #BE123C !important; }
    .card-theme-rose:hover .action-icon-box, .card-theme-rose:focus .action-icon-box, .card-theme-rose:active .action-icon-box { background-color: #E11D48 !important; border-color: #E11D48 !important; color: #FFFFFF !important; }
    .card-theme-rose .action-icon-box svg { color: inherit !important; }
  </style>

  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Quick Actions</h2>
      <span class="text-xs text-slate-400">Quick school staff management actions</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

      {{-- Action 1: Staff Approval --}}
      <a href="{{ route('hr.staff-approvals') }}" class="card-theme-indigo group bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-indigo-400 hover:shadow-md transition-all duration-200 flex items-start gap-4">
        <div class="action-icon-box w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 shadow-2xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: currentColor;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <h3 class="font-bold text-slate-900 text-sm group-hover:text-indigo-600 transition-colors">Staff Approval</h3>
            @if($pendingStaffApprovals > 0)
              <span class="badge-indigo text-[10px] font-extrabold px-2 py-0.5 rounded-full">{{ $pendingStaffApprovals }} Pending</span>
            @endif
          </div>
          <p class="text-xs text-slate-500 mt-1">Review newly added staff</p>
          <span class="inline-flex items-center gap-1 text-xs text-indigo-600 font-semibold mt-2.5 group-hover:translate-x-0.5 transition-transform">
            <span>Open approvals</span> &rarr;
          </span>
        </div>
      </a>

      {{-- Action 2: Leave Approval --}}
      @if(config('hr_features.leave_approval', false))
        <a href="{{ route('hr.leave-approvals') }}" class="card-theme-amber group bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-amber-400 hover:shadow-md transition-all duration-200 flex items-start gap-4">
          <div class="action-icon-box w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 shadow-2xs">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: currentColor;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h3 class="font-bold text-slate-900 text-sm group-hover:text-amber-600 transition-colors">Leave Approval</h3>
              @if($pendingLeaves > 0)
                <span class="badge-amber text-[10px] font-extrabold px-2 py-0.5 rounded-full">{{ $pendingLeaves }} Pending</span>
              @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">Review pending leave requests</p>
            <span class="inline-flex items-center gap-1 text-xs text-amber-600 font-semibold mt-2.5 group-hover:translate-x-0.5 transition-transform">
              <span>Review leaves</span> &rarr;
            </span>
          </div>
        </a>
      @endif

      {{-- Action 3: Mark Attendance --}}
      <a href="{{ route('hr.attendance.mark') }}" class="card-theme-emerald group bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-md transition-all duration-200 flex items-start gap-4">
        <div class="action-icon-box w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 shadow-2xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: currentColor;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <h3 class="font-bold text-slate-900 text-sm group-hover:text-emerald-600 transition-colors">Mark Attendance</h3>
            <span class="badge-green text-[10px] font-extrabold px-2 py-0.5 rounded-full">Today</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">Record today's staff attendance</p>
          <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-semibold mt-2.5 group-hover:translate-x-0.5 transition-transform">
            <span>Record attendance</span> &rarr;
          </span>
        </div>
      </a>

      {{-- Action 4: View Attendance --}}
      <a href="{{ route('hr.attendance.view') }}" class="card-theme-blue group bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-blue-400 hover:shadow-md transition-all duration-200 flex items-start gap-4">
        <div class="action-icon-box w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 shadow-2xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: currentColor;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <h3 class="font-bold text-slate-900 text-sm group-hover:text-blue-600 transition-colors">View Attendance</h3>
            <span class="badge-blue text-[10px] font-extrabold px-2 py-0.5 rounded-full">Register</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">View staff attendance</p>
          <span class="inline-flex items-center gap-1 text-xs text-blue-600 font-semibold mt-2.5 group-hover:translate-x-0.5 transition-transform">
            <span>View attendance by role</span> &rarr;
          </span>
        </div>
      </a>

      {{-- Action 5: Reports --}}
      @if(config('hr_features.reports', false))
        <a href="{{ route('hr.reports') }}" class="card-theme-purple group bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-purple-400 hover:shadow-md transition-all duration-200 flex items-start gap-4">
          <div class="action-icon-box w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 shadow-2xs">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: currentColor;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h3 class="font-bold text-slate-900 text-sm group-hover:text-purple-600 transition-colors">Reports</h3>
              <span class="badge-purple text-[10px] font-extrabold px-2 py-0.5 rounded-full">Analytics</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Generate attendance reports</p>
            <span class="inline-flex items-center gap-1 text-xs text-purple-600 font-semibold mt-2.5 group-hover:translate-x-0.5 transition-transform">
              <span>Open reports</span> &rarr;
            </span>
          </div>
        </a>
      @endif

      {{-- Action 6: Events --}}
      @if(config('hr_features.events', false))
        <a href="{{ route('hr.events') }}" class="card-theme-rose group bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-rose-400 hover:shadow-md transition-all duration-200 flex items-start gap-4">
          <div class="action-icon-box w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 shadow-2xs">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: currentColor;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h3 class="font-bold text-slate-900 text-sm group-hover:text-rose-600 transition-colors">Events</h3>
              @if(count($upcomingEvents) > 0)
                <span class="badge-rose text-[10px] font-extrabold px-2 py-0.5 rounded-full">{{ count($upcomingEvents) }} Upcoming</span>
              @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">Manage staff birthdays and events</p>
            <span class="inline-flex items-center gap-1 text-xs text-rose-600 font-semibold mt-2.5 group-hover:translate-x-0.5 transition-transform">
              <span>View celebrations</span> &rarr;
            </span>
          </div>
        </a>
      @endif

    </div>
  </div>

  {{-- ── 4. Two-Column Row: Today's Attendance by Category & Upcoming Events ── --}}
  <div class="grid grid-cols-1 {{ config('hr_features.events', false) ? 'lg:grid-cols-12' : '' }} gap-6">

    {{-- Column A: Today's Attendance Grouped by Staff Category --}}
    <div class="{{ config('hr_features.events', false) ? 'lg:col-span-7' : 'w-full' }} bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h2 class="font-extrabold text-sm text-slate-900">Today's Attendance by Category</h2>
          <p class="text-xs text-slate-400 mt-0.5">Live category-wise attendance distribution for {{ \Carbon\Carbon::parse($today ?? now())->format('d M Y') }}</p>
        </div>
        <a href="{{ route('hr.attendance.mark') }}" class="btn btn-secondary btn-xs font-bold text-indigo-600 hover:text-indigo-700">
          Mark Attendance &rarr;
        </a>
      </div>

      <div class="space-y-3.5 pt-1">
        @forelse($categoriesAttendance as $cat)
          <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-colors">
            <div class="flex items-center justify-between gap-2 mb-2">
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-800">{{ $cat['category'] }}</span>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold">
                  {{ $cat['total'] }} staff
                </span>
              </div>
              <div class="text-right">
                <span class="text-xs font-black font-mono {{ $cat['present'] > 0 ? 'text-emerald-600' : 'text-slate-600' }}">
                  {{ $cat['present'] }} / {{ $cat['total'] }} Present
                </span>
                <span class="text-[11px] font-bold text-slate-400 ml-1.5">({{ $cat['pct'] }}%)</span>
              </div>
            </div>

            {{-- Progress Bar --}}
            <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden flex">
              <div class="h-2 rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $cat['pct'] }}%"></div>
            </div>

            {{-- Sub Details --}}
            <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2 font-medium">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                Present: <strong class="text-slate-700">{{ $cat['present'] }}</strong>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                Absent: <strong class="text-slate-700">{{ $cat['absent'] }}</strong>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                On Leave: <strong class="text-slate-700">{{ $cat['on_leave'] }}</strong>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-300 inline-block"></span>
                Not Marked: <strong class="text-slate-600">{{ $cat['not_marked'] }}</strong>
              </span>
            </div>
          </div>
        @empty
          <div class="py-8 text-center text-slate-400 text-xs font-medium">
            No attendance marked for today.
          </div>
        @endforelse
      </div>
    </div>

    {{-- Column B: Upcoming Staff Events (Shown only when events feature is enabled) --}}
    @if(config('hr_features.events', false))
      <div class="lg:col-span-5 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-base">🎉</span>
              <h2 class="font-extrabold text-sm text-slate-900">Upcoming Staff Events</h2>
            </div>
            <a href="{{ route('hr.events') }}" class="text-xs text-rose-600 hover:text-rose-700 font-bold hover:underline">
              Manage &rarr;
            </a>
          </div>

          <div class="space-y-2.5 pt-2">
            @forelse($upcomingEvents as $ev)
              <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-rose-50/30 transition-colors flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 {{ $ev['badge_class'] ?? 'bg-indigo-50 text-indigo-700 border-indigo-200' }}">
                    @if($ev['type'] === 'birthday') 🎂
                    @elseif($ev['type'] === 'wedding') 💍
                    @elseif($ev['type'] === 'wedding_anniversary') 💐
                    @elseif($ev['type'] === 'joining_anniversary') 🏆
                    @else 🎈
                    @endif
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-xs text-slate-900 truncate">{{ $ev['staff_name'] }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ $ev['title'] }}</p>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <span class="text-xs font-bold font-mono text-slate-700 block">{{ $ev['date_label'] }}</span>
                  <span class="text-[10px] font-semibold text-slate-400">
                    @if($ev['days_left'] == 0)
                      <span class="text-emerald-600 font-extrabold">Today!</span>
                    @elseif($ev['days_left'] == 1)
                      Tomorrow
                    @else
                      in {{ $ev['days_left'] }} days
                    @endif
                  </span>
                </div>
              </div>
            @empty
              <div class="py-12 text-center text-slate-400 space-y-1">
                <p class="text-2xl">🗓️</p>
                <p class="text-xs font-semibold text-slate-500">No upcoming staff events.</p>
                <p class="text-[11px] text-slate-400">Add birthdays, anniversaries, or celebrations.</p>
                <a href="{{ route('hr.events') }}" class="btn btn-secondary btn-xs mt-2 font-bold inline-block">
                  + Add Event
                </a>
              </div>
            @endforelse
          </div>
        </div>

        {{-- Recent Joiners Footer --}}
        @if($recentHires->count() > 0)
          <div class="border-t border-slate-100 pt-3 mt-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Recent Joiners (Last 30 Days)</span>
              <a href="{{ route('hr.employees') }}" class="text-[11px] text-indigo-600 hover:underline font-bold">All &rarr;</a>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
              @foreach($recentHires as $hire)
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-100 px-2.5 py-1.5 rounded-xl shrink-0">
                  <div class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 font-bold text-[10px] flex items-center justify-center">
                    {{ strtoupper(substr($hire->first_name, 0, 1)) }}
                  </div>
                  <div class="text-[11px]">
                    <p class="font-bold text-slate-800 leading-tight">{{ $hire->first_name }} {{ $hire->last_name }}</p>
                    <p class="text-[10px] text-slate-400 leading-tight">{{ $hire->category_label }}</p>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

      </div>
    @endif
  </div>

</div>
@endsection
