@extends('layouts.app')
@section('title', 'Visitor Management Analytics & Report')
@section('content')
<div class="space-y-6">

  {{-- Top Navigation & Actions --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
        <a href="{{ route('gate.index') }}" class="hover:text-indigo-600 transition">Gate Management</a>
        <span>/</span>
        <span class="text-slate-800">Visitor Analytics Report</span>
      </div>
      <h1 class="page-title text-xl font-bold text-slate-900 tracking-tight">Visitor Analytics &amp; Log Register</h1>
      <p class="page-subtitle text-xs text-slate-500">Comprehensive gate traffic breakdown, dwell times, and downloadable audit trail</p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('gate.index') }}" class="btn-secondary text-xs sm:text-sm font-semibold">
        ← Gate Dashboard
      </a>
      <a href="{{ route('gate.report', array_merge(request()->query(), ['export'=>'csv'])) }}" class="btn-secondary text-xs sm:text-sm font-semibold flex items-center gap-1.5 bg-white hover:border-emerald-300 hover:text-emerald-700 shadow-xs">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </a>
      <button onclick="window.print()" class="btn-primary text-xs sm:text-sm font-bold flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print</span>
      </button>
    </div>
  </div>

  {{-- Filter Toolbar --}}
  <form method="GET" action="{{ route('gate.report') }}" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 no-print items-end">
    <div>
      <label class="label text-xs">From Date</label>
      <input type="date" name="from" value="{{ $from }}" class="input w-full text-xs">
    </div>

    <div>
      <label class="label text-xs">To Date</label>
      <input type="date" name="to" value="{{ $to }}" class="input w-full text-xs">
    </div>

    <div>
      <label class="label text-xs">Category</label>
      <select name="category" class="select w-full text-xs">
        <option value="">All Categories</option>
        @foreach($categories as $k => $label)
        <option value="{{ $k }}" @selected(request('category') === $k)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="label text-xs">Department</label>
      <select name="department" class="select w-full text-xs">
        <option value="">All Departments</option>
        @foreach($departmentsList as $d)
        <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary text-xs w-full py-2.5 font-bold">
        Generate Report
      </button>
      @if(request()->hasAny(['category', 'department', 'from', 'to']))
      <a href="{{ route('gate.report') }}" class="btn-secondary text-xs px-3 py-2.5" title="Reset">✕</a>
      @endif
    </div>
  </form>

  {{-- Summary KPI Cards --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Recorded Visits</span>
      <p class="text-2xl font-black text-indigo-600 mt-1">{{ $totalIn }}</p>
      <p class="text-[11px] text-slate-400 mt-0.5">Across selected period</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Checked Out / Completed</span>
      <p class="text-2xl font-black text-emerald-600 mt-1">{{ $totalOut }}</p>
      <p class="text-[11px] text-slate-400 mt-0.5">Departed campus</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Still Inside</span>
      <p class="text-2xl font-black text-rose-600 mt-1">{{ $stillInside }}</p>
      <p class="text-[11px] text-slate-400 mt-0.5">Active inside premises</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg. Stay Duration</span>
      <p class="text-2xl font-black text-slate-900 mt-1">{{ $avgDuration }} <span class="text-sm font-semibold text-slate-500">mins</span></p>
      <p class="text-[11px] text-slate-400 mt-0.5">Average dwell time</p>
    </div>
  </div>

  {{-- Category Breakdown Badges --}}
  @if(count($byCategory) > 0)
  <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2">
    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Visits by Category</span>
    <div class="flex flex-wrap gap-2 pt-1">
      @foreach($byCategory as $catKey => $count)
      <div class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center gap-2">
        <span class="font-semibold text-slate-700">{{ ucfirst(str_replace('_', ' ', $catKey)) }}</span>
        <span class="font-bold bg-indigo-100 text-indigo-800 px-1.5 py-0.2 rounded-full text-[11px]">{{ $count }}</span>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Report Data Table --}}
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
      <h2 class="font-bold text-slate-800 text-sm">Visitor Log Register ({{ count($visitors) }} Records)</h2>
      <span class="text-xs text-slate-500 font-mono">{{ $from }} to {{ $to }}</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
          <tr>
            <th class="py-3 px-3">#</th>
            <th class="py-3 px-3">Pass #</th>
            <th class="py-3 px-3">Visitor Name &amp; Contact</th>
            <th class="py-3 px-3">Category</th>
            <th class="py-3 px-3">Host / Department</th>
            <th class="py-3 px-3">Purpose</th>
            <th class="py-3 px-3">In Time</th>
            <th class="py-3 px-3">Out Time</th>
            <th class="py-3 px-3">Duration</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($visitors as $i => $v)
          <tr class="hover:bg-slate-50/70 transition">
            <td class="py-2.5 px-3 text-slate-400 font-mono">{{ $i + 1 }}</td>
            <td class="py-2.5 px-3 font-mono font-bold text-indigo-700">
              <a href="{{ route('gate.show', $v->id) }}" class="hover:underline">{{ $v->pass_number }}</a>
            </td>
            <td class="py-2.5 px-3">
              <span class="font-bold text-slate-900 block">{{ $v->visitor_name }}</span>
              <span class="text-[11px] text-slate-500 font-mono">{{ $v->visitor_phone ?? '—' }}</span>
            </td>
            <td class="py-2.5 px-3">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $v->category_badge_classes['bg'] }}">
                {{ $v->category_label }}
              </span>
            </td>
            <td class="py-2.5 px-3">
              <span class="font-semibold text-slate-800 block">{{ $v->whom_to_meet ?? '—' }}</span>
              <span class="text-[10px] text-slate-500">{{ $v->department ?? 'General' }}</span>
            </td>
            <td class="py-2.5 px-3 text-slate-600 max-w-xs truncate" title="{{ $v->purpose }}">
              {{ $v->purpose }}
            </td>
            <td class="py-2.5 px-3 font-mono text-slate-700">
              {{ $v->in_time ? $v->in_time->format('d/m H:i') : 'Pending' }}
            </td>
            <td class="py-2.5 px-3 font-mono text-slate-700">
              {{ $v->out_time ? $v->out_time->format('H:i') : '—' }}
            </td>
            <td class="py-2.5 px-3 font-mono">
              @if($v->in_time && $v->out_time)
                <span class="text-slate-800">{{ $v->in_time->diffInMinutes($v->out_time) }} mins</span>
              @elseif($v->isInside())
                <span class="text-rose-600 font-bold">Inside</span>
              @else
                <span class="text-slate-400">—</span>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="py-8 text-center text-slate-400 font-medium">
              No visitor records found for the selected period and filters.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
