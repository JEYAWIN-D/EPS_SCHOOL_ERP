@extends('layouts.app')
@section('title', 'Transport Reports & Audits')
@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="page-title">Transport Reports &amp; Audit Logs</h1>
      <p class="page-subtitle">Capacity occupancy analysis, route schedules, boarding manifests, and assignment audit histories</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('transport.reports.export', array_merge(request()->query(), ['type' => $reportType, 'format' => 'excel'])) }}"
         class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export Report (.xlsx)
      </a>
    </div>
  </div>

  {{-- Unified Sub-Navigation Tabs --}}
  @include('transport.partials.nav')

  {{-- Report Type Pill Selector --}}
  <div class="flex items-center gap-2 overflow-x-auto pb-1">
    @php
      $reportTypes = [
        'vehicle_manifest'     => ['label' => 'Vehicle Occupancy & Manifest', 'icon' => '🚐'],
        'route_schedule'      => ['label' => 'Route Corridor Schedules',    'icon' => '🛣️'],
        'stop_manifest'       => ['label' => 'Bus Stop Boarding Lists',      'icon' => '📍'],
        'unallocated_students' => ['label' => 'Unallocated Students (No Bus)', 'icon' => '🚶'],
        'audit_history'       => ['label' => 'Assignment Audit Trail',       'icon' => '📜'],
      ];
    @endphp

    @foreach($reportTypes as $typeKey => $meta)
      <a href="{{ route('transport.reports', ['type' => $typeKey]) }}"
         class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $reportType === $typeKey ? 'bg-[#8C2826] text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}">
        <span>{{ $meta['icon'] }}</span>
        <span>{{ $meta['label'] }}</span>
      </a>
    @endforeach
  </div>

  {{-- Report Content Area --}}
  @if($reportType === 'vehicle_manifest')
    {{-- 1. Vehicle Capacity & Manifest Report --}}
    <div class="card p-5 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Vehicle Seating Occupancy &amp; Passenger Manifest</h3>
          <p class="text-xs text-slate-400">Total active fleet occupancy breakdown and seat availability</p>
        </div>
      </div>

      <div class="table-wrap">
        <table class="w-full">
          <thead>
            <tr>
              <th class="th">Vehicle Reg</th>
              <th class="th">Type</th>
              <th class="th">Linked Route</th>
              <th class="th">Driver &amp; Mobile</th>
              <th class="th">Seating Cap</th>
              <th class="th">Assigned</th>
              <th class="th">Remaining</th>
              <th class="th">Occupancy %</th>
              <th class="th">Status</th>
              <th class="th text-right">Manifest</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $v)
              @php
                $cap = $v->seating_capacity ?: $v->capacity ?: 0;
                $assigned = $v->active_students_count ?? 0;
                $rem = max(0, $cap - $assigned);
                $isOver = $cap > 0 && ($assigned > $cap);
              @endphp
              <tr class="tr">
                <td class="td font-mono font-bold text-xs text-slate-900">{{ $v->vehicle_number }}</td>
                <td class="td text-xs">{{ ucfirst($v->vehicle_type ?? 'Van') }}</td>
                <td class="td text-xs font-semibold text-slate-800">{{ $v->route?->route_name ?? '—' }}</td>
                <td class="td text-xs">
                  <span class="block font-medium">{{ $v->driver_name ?: '—' }}</span>
                  <span class="text-[10px] text-slate-400 font-mono">{{ $v->driver_mobile }}</span>
                </td>
                <td class="td font-mono text-xs font-bold">{{ $cap }}</td>
                <td class="td font-mono text-xs font-bold {{ $isOver ? 'text-red-700' : 'text-slate-800' }}">{{ $assigned }}</td>
                <td class="td font-mono text-xs font-bold {{ $isOver ? 'text-red-700' : 'text-emerald-700' }}">{{ $rem }}</td>
                <td class="td font-mono text-xs font-semibold">
                  <div class="flex items-center gap-2">
                    <span class="w-10">{{ $v->occupancy_pct }}%</span>
                    <div class="w-16 bg-slate-100 h-1.5 rounded-full overflow-hidden">
                      <div class="h-full {{ $isOver ? 'bg-red-500' : ($v->occupancy_pct >= 90 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $v->occupancy_pct) }}%"></div>
                    </div>
                  </div>
                </td>
                <td class="td whitespace-nowrap">
                  @if($isOver)
                    <span class="badge-red">Overcapacity</span>
                  @elseif($v->occupancy_pct >= 90)
                    <span class="badge-amber">Near Full</span>
                  @else
                    <span class="badge-green">Available</span>
                  @endif
                </td>
                <td class="td text-right">
                  <a href="{{ route('transport.students', ['vehicle_id' => $v->id]) }}"
                     class="btn btn-secondary btn-sm text-xs py-1 px-2.5">
                    View Manifest &rarr;
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="10" class="td text-center py-8 text-slate-400">No vehicle data available.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  @elseif($reportType === 'route_schedule')
    {{-- 2. Route Corridor Schedules --}}
    <div class="space-y-4">
      @foreach($data as $r)
        <div class="card p-5 space-y-3">
          <div class="flex items-start justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-slate-900 text-sm">{{ $r->route_name }}</h3>
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                  {{ $r->vehicle?->vehicle_number ?? 'No vehicle assigned' }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">Corridor: {{ $r->from_location }} &rarr; {{ $r->to_location }} &bull; Distance: {{ $r->distance_km }} km &bull; Departure: {{ $r->departure_time }}</p>
            </div>
            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ $r->active_students_count }} enrolled passengers
            </span>
          </div>

          <div class="table-wrap">
            <table class="w-full text-xs">
              <thead>
                <tr>
                  <th class="th">Seq</th>
                  <th class="th">Stop Name</th>
                  <th class="th">Landmark</th>
                  <th class="th">Pickup Time</th>
                  <th class="th">Drop Time</th>
                  <th class="th">Distance</th>
                  <th class="th">Fare</th>
                </tr>
              </thead>
              <tbody>
                @foreach($r->stops as $st)
                  <tr class="tr">
                    <td class="td font-mono font-bold text-slate-500">#{{ $st->stop_order }}</td>
                    <td class="td font-bold text-slate-800">{{ $st->name }}</td>
                    <td class="td text-slate-500">{{ $st->landmark ?: '—' }}</td>
                    <td class="td font-mono font-semibold text-emerald-700">{{ $st->pickup_time ? \Carbon\Carbon::parse($st->pickup_time)->format('h:i A') : '07:30 AM' }}</td>
                    <td class="td font-mono font-semibold text-blue-700">{{ $st->drop_time ? \Carbon\Carbon::parse($st->drop_time)->format('h:i A') : '04:30 PM' }}</td>
                    <td class="td font-mono">{{ $st->distance_km ? $st->distance_km . ' km' : '—' }}</td>
                    <td class="td font-mono font-bold">₹{{ number_format($st->fare ?? 0) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endforeach
    </div>

  @elseif($reportType === 'unallocated_students')
    {{-- 4. Students Without Transport --}}
    <div class="card p-5 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Active Students Without Transport Allocation</h3>
          <p class="text-xs text-slate-400">Day scholars travelling via own conveyance or awaiting bus seat allocation</p>
        </div>
        <span class="text-xs font-mono font-bold px-2.5 py-1 rounded bg-slate-100 text-slate-700">{{ $data->total() }} students</span>
      </div>

      <div class="table-wrap">
        <table class="w-full">
          <thead>
            <tr>
              <th class="th">Student ID</th>
              <th class="th">Admission #</th>
              <th class="th">Student Name</th>
              <th class="th">Class &amp; Section</th>
              <th class="th">Parent Phone</th>
              <th class="th text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $st)
              <tr class="tr">
                <td class="td font-mono text-xs text-slate-500">#{{ $st->id }}</td>
                <td class="td font-mono text-xs font-bold text-slate-900">{{ $st->admission_number }}</td>
                <td class="td font-bold text-slate-800 text-sm">{{ $st->full_name }}</td>
                <td class="td text-xs font-semibold text-slate-700">
                  {{ $st->currentEnrollment?->class?->name ?? '—' }}
                  @if($st->currentEnrollment?->section) ({{ $st->currentEnrollment->section->name }}) @endif
                </td>
                <td class="td font-mono text-xs text-slate-600">{{ $st->emergency_contact ?: ($st->father_phone ?: ($st->mobile ?: '—')) }}</td>
                <td class="td text-right">
                  <a href="{{ route('transport.students', ['search' => $st->admission_number]) }}"
                     class="btn btn-secondary btn-sm text-xs py-1 px-2.5">
                    Allocate Bus &rarr;
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="td text-center py-8 text-slate-400">All active students have transport allocated.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($data->hasPages())
        <div>{{ $data->links() }}</div>
      @endif
    </div>

  @elseif($reportType === 'audit_history')
    {{-- 5. Transport Assignment History --}}
    <div class="card p-5 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Transport Assignment Audit History</h3>
          <p class="text-xs text-slate-400">Complete historical timeline of route changes, student migrations, and discontinuations</p>
        </div>
      </div>

      <div class="table-wrap">
        <table class="w-full">
          <thead>
            <tr>
              <th class="th">Date</th>
              <th class="th">Student</th>
              <th class="th">Action Type</th>
              <th class="th">Previous Transit</th>
              <th class="th">New Transit</th>
              <th class="th">Remarks</th>
              <th class="th">Changed By</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data as $hist)
              <tr class="tr">
                <td class="td font-mono text-xs text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($hist->effective_date)->format('d M Y') }}</td>
                <td class="td font-bold text-xs text-slate-900">{{ $hist->student?->full_name ?? ('#' . $hist->student_id) }}</td>
                <td class="td whitespace-nowrap">
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $hist->change_type === 'assigned' ? 'bg-emerald-50 text-emerald-700' : ($hist->change_type === 'discontinued' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700') }}">
                    {{ ucfirst($hist->change_type) }}
                  </span>
                </td>
                <td class="td text-xs text-slate-500">
                  @if($hist->oldRoute)
                    <span>{{ $hist->oldRoute->route_name }}</span>
                    @if($hist->oldStop) <span class="text-slate-400">(&bull; {{ $hist->oldStop->name }})</span> @endif
                  @else
                    <span class="text-slate-300 italic">—</span>
                  @endif
                </td>
                <td class="td text-xs font-semibold text-slate-800">
                  @if($hist->newRoute)
                    <span>{{ $hist->newRoute->route_name }}</span>
                    @if($hist->newStop) <span class="text-indigo-600">(&bull; {{ $hist->newStop->name }})</span> @endif
                  @else
                    <span class="text-slate-300 italic">—</span>
                  @endif
                </td>
                <td class="td text-xs text-slate-600 max-w-xs truncate" title="{{ $hist->remarks }}">{{ $hist->remarks ?: '—' }}</td>
                <td class="td text-xs text-slate-400 font-mono">{{ $hist->changedByUser?->name ?? 'Admin' }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="td text-center py-8 text-slate-400">No audit history entries recorded yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($data->hasPages())
        <div>{{ $data->links() }}</div>
      @endif
    </div>
  @endif

</div>
@endsection
