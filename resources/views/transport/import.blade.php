@extends('layouts.app')
@section('title', 'Excel Import & Transport Data Migration')
@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="page-title">Excel Import &amp; Data Migration</h1>
      <p class="page-subtitle">Safely migrate bus stops, import student transit manifests, and create backup snapshots</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="POST" action="{{ route('transport.backup') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm inline-flex items-center gap-1.5 shadow-sm">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
          Create Backup Snapshot Now
        </button>
      </form>
    </div>
  </div>

  {{-- Unified Sub-Navigation Tabs --}}
  @include('transport.partials.nav')

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-2">
      <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  {{-- Import Summary Banner (when an import was just completed) --}}
  @if(session('import_summary'))
    @php $sum = session('import_summary'); @endphp
    <div class="rounded-2xl border {{ ($sum['failed'] ?? 0) > 0 ? 'bg-amber-50 border-amber-300' : 'bg-emerald-50 border-emerald-300' }} p-5 shadow-xs">
      <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b {{ ($sum['failed'] ?? 0) > 0 ? 'border-amber-200 text-amber-900' : 'border-emerald-200 text-emerald-900' }}">
        <div class="flex items-center gap-2">
          <span class="text-lg">📊</span>
          <h3 class="font-extrabold text-sm">Excel Import Summary: {{ $sum['file_name'] ?? 'File' }}</h3>
        </div>
        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-white border">{{ $sum['import_type'] ?? 'Import' }}</span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-4 text-center">
        <div class="bg-white p-3 rounded-xl border border-slate-200">
          <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Processed</span>
          <span class="text-lg font-extrabold text-slate-800 tabular-nums">{{ $sum['total_processed'] ?? 0 }}</span>
        </div>
        <div class="bg-white p-3 rounded-xl border border-emerald-200">
          <span class="text-[10px] uppercase font-bold text-emerald-600 block">Created</span>
          <span class="text-lg font-extrabold text-emerald-700 tabular-nums">{{ $sum['created'] ?? 0 }}</span>
        </div>
        <div class="bg-white p-3 rounded-xl border border-blue-200">
          <span class="text-[10px] uppercase font-bold text-blue-600 block">Updated / Synced</span>
          <span class="text-lg font-extrabold text-blue-700 tabular-nums">{{ $sum['updated'] ?? 0 }}</span>
        </div>
        <div class="bg-white p-3 rounded-xl border border-slate-200">
          <span class="text-[10px] uppercase font-bold text-slate-400 block">Duplicates / Skipped</span>
          <span class="text-lg font-extrabold text-slate-600 tabular-nums">{{ $sum['skipped'] ?? 0 }}</span>
        </div>
        <div class="bg-white p-3 rounded-xl border {{ ($sum['failed'] ?? 0) > 0 ? 'border-red-300 bg-red-50/50' : 'border-slate-200' }}">
          <span class="text-[10px] uppercase font-bold {{ ($sum['failed'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-400' }} block">Failed / Invalid</span>
          <span class="text-lg font-extrabold {{ ($sum['failed'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-400' }} tabular-nums">{{ $sum['failed'] ?? 0 }}</span>
        </div>
      </div>

      @if(!empty($sum['errors']))
        <div class="mt-4 p-3 bg-red-50 rounded-xl border border-red-200 text-xs text-red-800">
          <p class="font-bold mb-1">Row Validation Notices:</p>
          <ul class="list-disc list-inside space-y-0.5">
            @foreach(array_slice($sum['errors'], 0, 5) as $err)
              <li>{{ $err }}</li>
            @endforeach
            @if(count($sum['errors']) > 5)
              <li class="font-semibold">+ {{ count($sum['errors']) - 5 }} more issues flagged.</li>
            @endif
          </ul>
        </div>
      @endif
    </div>
  @endif

  {{-- Migration & Import Cards Grid --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- 1. Bus Stop Official Excel Import Card --}}
    <div class="card p-6 flex flex-col justify-between space-y-5">
      <div>
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <h2 class="text-base font-bold text-slate-900">Import Official Bus Stops</h2>
            <p class="text-xs text-slate-500">Source: TRANSPORT DETAILS.xlsx (Vans 1-5 official stops)</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed mb-4">
          Imports official stopping points, distances (KM), and annual fee schedules for all vans.
          Existing stops will be updated without duplicates, preserving assigned students.
        </p>

        <div class="bg-slate-50 rounded-xl p-3 text-xs text-slate-600 space-y-1 mb-4 border border-slate-200">
          <span class="font-bold text-slate-700 block">Expected columns in Excel:</span>
          <p class="font-mono text-[11px] text-slate-600">Van / Route &bull; Stop Name &bull; Distance (KM) &bull; Annual Fee &bull; Landmark</p>
        </div>
      </div>

      <form method="POST" action="{{ route('transport.import.stops') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
          <label class="label">Select Stops Spreadsheet (.xlsx, .xls, .csv)</label>
          <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                 class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-xl p-1">
        </div>

        <button type="submit" class="btn btn-primary w-full justify-center">
          Upload &amp; Import Bus Stops
        </button>
      </form>
    </div>

    {{-- 2. Student Transport Allocation Manifest Import Card --}}
    <div class="card p-6 flex flex-col justify-between space-y-5">
      <div>
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <h2 class="text-base font-bold text-slate-900">Import Student Transport Manifest</h2>
            <p class="text-xs text-slate-500">Source: TRANSPORT FULL DETAILS.xlsx (Multi-sheet Vans 1-5)</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed mb-4">
          Parses sheets <strong class="font-mono">VAN I</strong> to <strong class="font-mono">VAN V</strong>, matches students by Name/Class,
          assigns vehicles and boarding stops, and saves individual morning pickup and evening drop timings.
        </p>

        <div class="bg-slate-50 rounded-xl p-3 text-xs text-slate-600 space-y-1 mb-4 border border-slate-200">
          <span class="font-bold text-slate-700 block">Handled sheets:</span>
          <p class="font-mono text-[11px] text-slate-600">VAN I, VAN II, VAN III, VAN IV, VAN V (Morning &amp; Evening times)</p>
        </div>
      </div>

      <form method="POST" action="{{ route('transport.import.allocations') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
          <label class="label">Select Student Roster Spreadsheet (.xlsx, .xls)</label>
          <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                 class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-xl p-1">
        </div>

        <button type="submit" class="btn btn-primary bg-emerald-700 hover:bg-emerald-800 w-full justify-center">
          Upload &amp; Allocate Students
        </button>
      </form>
    </div>

  </div>

  {{-- Backups & Import Audit History --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Backups List --}}
    <div class="card p-5 space-y-3">
      <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <h3 class="font-bold text-slate-900 text-sm">Saved JSON Backups</h3>
        <span class="text-xs font-mono text-slate-400">{{ count($backups) }} snapshots</span>
      </div>

      <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
        @forelse($backups as $b)
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
            <div>
              <span class="font-mono font-bold text-slate-800 block truncate max-w-[170px]" title="{{ $b['name'] }}">{{ $b['name'] }}</span>
              <span class="text-[10px] text-slate-400">{{ $b['date'] }}</span>
            </div>
            <span class="font-mono text-slate-500 font-semibold">{{ $b['size'] }}</span>
          </div>
        @empty
          <p class="text-xs text-slate-400 py-4 text-center">No snapshot backups found. Click &ldquo;Create Backup Snapshot Now&rdquo; above.</p>
        @endforelse
      </div>
    </div>

    {{-- Import History Log --}}
    <div class="lg:col-span-2 card p-5 space-y-3">
      <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <h3 class="font-bold text-slate-900 text-sm">Recent Import Logs &amp; Audit Trail</h3>
        <span class="text-xs text-slate-400">Total: {{ $logs->count() }} records</span>
      </div>

      <div class="table-wrap">
        <table class="w-full">
          <thead>
            <tr>
              <th class="th">File Name</th>
              <th class="th">Type</th>
              <th class="th">Total</th>
              <th class="th">Created</th>
              <th class="th">Updated</th>
              <th class="th">Date</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $log)
              <tr class="tr">
                <td class="td font-mono text-xs font-bold text-slate-900">{{ $log->file_name }}</td>
                <td class="td whitespace-nowrap">
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $log->import_type === 'bus_stops' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ ucfirst(str_replace('_', ' ', $log->import_type)) }}
                  </span>
                </td>
                <td class="td font-mono text-xs">{{ $log->total_rows }}</td>
                <td class="td font-mono text-xs text-emerald-700 font-bold">{{ $log->created_count }}</td>
                <td class="td font-mono text-xs text-blue-700 font-bold">{{ $log->updated_count }}</td>
                <td class="td font-mono text-xs text-slate-400 whitespace-nowrap">{{ $log->created_at->format('d M, h:i A') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="td text-center py-6 text-slate-400">No import history recorded yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>
@endsection
