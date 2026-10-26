@extends('layouts.app')
@section('title', 'Vehicle-wise Student Allocation')
@section('content')
<div class="space-y-6" x-data="{
  allotModalOpen: false,
  discontinueModalOpen: false,
  discontinueAllotmentId: null,
  discontinueStudentName: '',
  routesData: {{ json_encode($routes->map(fn($r) => [
    'id' => $r->id,
    'name' => $r->route_name,
    'vehicle_id' => $r->vehicle_id,
    'vehicle_number' => $r->vehicle?->vehicle_number,
    'fee' => $r->fee,
    'stops' => $r->stops->map(fn($s) => [
      'id' => $s->id,
      'name' => $s->name,
      'pickup_time' => $s->pickup_time ? \Carbon\Carbon::parse($s->pickup_time)->format('h:i A') : '07:30 AM',
      'drop_time' => $s->drop_time ? \Carbon\Carbon::parse($s->drop_time)->format('h:i A') : '04:30 PM',
      'fare' => $s->fare,
    ]),
  ])) }},
  selectedRouteId: '',
  selectedStopId: '',
  selectedDropStopId: '',
  pickupOverride: '',
  dropOverride: '',
  allotFee: 0,
  get currentRouteObj() {
    return this.routesData.find(r => r.id == this.selectedRouteId) || null;
  },
  get availableStops() {
    return this.currentRouteObj ? this.currentRouteObj.stops : [];
  },
  onRouteChanged() {
    this.selectedStopId = '';
    this.selectedDropStopId = '';
    this.pickupOverride = '';
    this.dropOverride = '';
    if (this.currentRouteObj) {
      this.allotFee = this.currentRouteObj.fee || 0;
    }
  },
  onStopChanged() {
    let stop = this.availableStops.find(s => s.id == this.selectedStopId);
    if (stop) {
      this.pickupOverride = stop.pickup_time || '07:30 AM';
      this.dropOverride = stop.drop_time || '04:30 PM';
      if (stop.fare) this.allotFee = stop.fare;
      if (!this.selectedDropStopId) this.selectedDropStopId = stop.id;
    }
  },
  openDiscontinueModal(id, name) {
    this.discontinueAllotmentId = id;
    this.discontinueStudentName = name;
    this.discontinueModalOpen = true;
  }
}">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="page-title">Vehicle-wise Student Allocation</h1>
      <p class="page-subtitle">Inspect passenger manifests by bus/van, manage individual pickup/drop timings, and track seating capacity</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('transport.students.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
         class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export Excel
      </a>
      <a href="{{ route('transport.students.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
         class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        Export PDF
      </a>
      <button type="button" @click="allotModalOpen = true" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Allocate Student
      </button>
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
  @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-800">
      <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Vehicle Fleet Quick Selector Carousel / Cards --}}
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Filter by Vehicle Fleet</h2>
      @if($overcapacityVehicles > 0)
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
          ⚠️ {{ $overcapacityVehicles }} vehicle(s) overcapacity
        </span>
      @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      {{-- All Vehicles Pill --}}
      <a href="{{ route('transport.students', request()->except('vehicle_id', 'page')) }}"
         class="p-3.5 rounded-xl border transition flex flex-col justify-between {{ !request('vehicle_id') ? 'bg-indigo-50 border-indigo-300 ring-2 ring-indigo-200' : 'bg-white border-slate-200 hover:border-slate-300' }}">
        <div class="flex items-center justify-between">
          <span class="text-xs font-extrabold text-slate-800">All Fleet</span>
          <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-bold bg-slate-100 text-slate-600">{{ $totalVehicles }} units</span>
        </div>
        <div class="mt-2 text-right">
          <p class="text-xs font-semibold text-slate-500">Total Enrolled</p>
          <p class="text-lg font-extrabold text-indigo-700 tabular-nums">{{ $totalActiveStudents }}</p>
        </div>
      </a>

      @foreach($vehicles as $v)
        @php
          $vCap = (int)($v->seating_capacity ?: $v->capacity ?: 0);
          $vCount = \App\Models\TransportAllotment::where('vehicle_id', $v->id)->where('is_active', true)->count();
          $isOver = $vCap > 0 && ($vCount > $vCap);
          $pct = $vCap > 0 ? min(100, round(($vCount / $vCap) * 100)) : 0;
          $isSelected = request('vehicle_id') == $v->id;
        @endphp
        <a href="{{ route('transport.students', array_merge(request()->query(), ['vehicle_id' => $v->id, 'page' => 1])) }}"
           class="p-3.5 rounded-xl border transition flex flex-col justify-between {{ $isSelected ? 'bg-indigo-50 border-indigo-400 ring-2 ring-indigo-200' : ($isOver ? 'bg-red-50/50 border-red-200 hover:border-red-300' : 'bg-white border-slate-200 hover:border-slate-300') }}">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-mono font-extrabold text-slate-900 truncate" title="{{ $v->vehicle_number }}">{{ $v->vehicle_number }}</span>
              @if($isOver)
                <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-red-100 text-red-700 animate-pulse">OVER</span>
              @else
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">{{ ucfirst($v->vehicle_type ?? 'Van') }}</span>
              @endif
            </div>
            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $v->driver_name ?: 'Driver TBD' }}</p>
          </div>

          <div class="mt-2.5 space-y-1">
            <div class="flex items-center justify-between text-[11px] font-mono">
              <span class="font-bold {{ $isOver ? 'text-red-700' : 'text-slate-700' }}">{{ $vCount }} / {{ $vCap }}</span>
              <span class="text-slate-400">{{ $pct }}%</span>
            </div>
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all {{ $isOver ? 'bg-red-500' : ($pct >= 90 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  </div>

  {{-- Selected Vehicle Focus Banner (if vehicle filtered) --}}
  @if($selectedVehicle)
    <div class="p-4 rounded-2xl border {{ $isOverCapacity ? 'bg-red-50 border-red-300 text-red-900' : 'bg-indigo-50/70 border-indigo-200 text-indigo-950' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl {{ $isOverCapacity ? 'bg-red-200 text-red-800' : 'bg-indigo-200 text-indigo-800' }} flex items-center justify-center font-bold text-lg flex-shrink-0">
          🚐
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-extrabold">{{ $selectedVehicle->vehicle_number }}</h3>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200">{{ ucfirst($selectedVehicle->vehicle_type ?? 'Bus') }}</span>
            @if($isOverCapacity)
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-600 text-white animate-pulse">Over Seating Capacity by {{ $vehicleAllottedCount - $vehicleCapacity }}</span>
            @endif
          </div>
          <p class="text-xs text-slate-600 mt-0.5">
            Driver: <strong>{{ $selectedVehicle->driver_name ?: 'Unassigned' }}</strong> ({{ $selectedVehicle->driver_mobile ?: '—' }}) &bull;
            Route: <strong>{{ $selectedVehicle->route?->route_name ?? 'Not linked to route' }}</strong>
          </p>
        </div>
      </div>
      <div class="flex items-center gap-4 text-xs font-mono">
        <div>
          <span class="text-[10px] uppercase font-bold text-slate-400 block">Capacity</span>
          <span class="font-extrabold text-sm">{{ $vehicleCapacity }} seats</span>
        </div>
        <div>
          <span class="text-[10px] uppercase font-bold text-slate-400 block">Occupied</span>
          <span class="font-extrabold text-sm {{ $isOverCapacity ? 'text-red-600' : 'text-emerald-700' }}">{{ $vehicleAllottedCount }}</span>
        </div>
        <div>
          <span class="text-[10px] uppercase font-bold text-slate-400 block">Remaining</span>
          <span class="font-extrabold text-sm {{ $isOverCapacity ? 'text-red-600' : 'text-slate-800' }}">{{ max(0, $vehicleCapacity - $vehicleAllottedCount) }}</span>
        </div>
      </div>
    </div>
  @endif

  {{-- Filters & Search Bar --}}
  <div class="card-flat p-4">
    <form method="GET" action="{{ route('transport.students') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
      @if(request('vehicle_id'))
        <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
      @endif

      {{-- Search --}}
      <div class="relative lg:col-span-2">
        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search student name, admission #, roll..."
               class="input w-full pl-9">
      </div>

      {{-- Route Filter --}}
      <div>
        <select name="route_id" class="select w-full">
          <option value="">All Routes</option>
          @foreach($routes as $r)
            <option value="{{ $r->id }}" {{ request('route_id') == $r->id ? 'selected' : '' }}>{{ $r->route_name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Stop Filter --}}
      <div>
        <select name="stop_id" class="select w-full">
          <option value="">All Bus Stops</option>
          @foreach($stops as $s)
            <option value="{{ $s->id }}" {{ request('stop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Class Filter --}}
      <div>
        <select name="class_id" class="select w-full">
          <option value="">All Classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Status & Submit --}}
      <div class="flex items-center gap-2">
        <select name="status" class="select flex-1">
          <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="discontinued" {{ request('status') === 'discontinued' ? 'selected' : '' }}>Discontinued</option>
          <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Status</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm px-3.5">Filter</button>
        @if(request()->hasAny(['search', 'route_id', 'stop_id', 'class_id', 'vehicle_id', 'status']))
          <a href="{{ route('transport.students') }}" class="btn btn-ghost btn-sm text-slate-500">Reset</a>
        @endif
      </div>
    </form>
  </div>

  {{-- Student Allocations Table --}}
  <div class="table-wrap">
    <table class="w-full">
      <thead>
        <tr>
          <th class="th">Student</th>
          <th class="th">Class &amp; Section</th>
          <th class="th">Vehicle</th>
          <th class="th">Route</th>
          <th class="th">Pickup Stop &amp; Time</th>
          <th class="th">Drop Stop &amp; Time</th>
          <th class="th">Annual Fee</th>
          <th class="th">Status</th>
          <th class="th text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($allotments as $a)
          <tr class="tr {{ !$a->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
            {{-- Student --}}
            <td class="td">
              <span class="font-bold text-slate-900 text-sm block leading-tight">{{ $a->student?->full_name ?? '—' }}</span>
              <div class="flex items-center gap-1.5 mt-0.5 text-xs text-slate-400 font-mono">
                <span>Admn: <strong>{{ $a->student?->admission_number ?? '—' }}</strong></span>
                @if($a->student?->roll_number)
                  <span class="text-slate-300">&bull;</span>
                  <span>Roll: {{ $a->student->roll_number }}</span>
                @endif
              </div>
            </td>

            {{-- Class & Section --}}
            <td class="td whitespace-nowrap">
              <span class="text-xs font-semibold text-slate-800">
                {{ $a->enrollment?->class?->name ?? '—' }}
                @if($a->enrollment?->section)
                  ({{ $a->enrollment->section->name }})
                @endif
              </span>
            </td>

            {{-- Vehicle --}}
            <td class="td whitespace-nowrap">
              @if($a->vehicle)
                <span class="inline-flex items-center gap-1 font-mono text-xs font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-200">
                  🚐 {{ $a->vehicle->vehicle_number }}
                </span>
              @else
                <span class="text-slate-400 text-xs italic">Unassigned</span>
              @endif
            </td>

            {{-- Route --}}
            <td class="td">
              <span class="text-xs font-medium text-slate-700 block max-w-xs truncate" title="{{ $a->route?->route_name }}">
                {{ $a->route?->route_name ?? '—' }}
              </span>
            </td>

            {{-- Pickup Stop & Time --}}
            <td class="td whitespace-nowrap">
              <span class="text-xs font-bold text-slate-800 block">{{ $a->effective_pickup_stop_name }}</span>
              <div class="flex items-center gap-1 text-[11px] font-mono mt-0.5">
                <span class="text-emerald-700 font-semibold">{{ $a->effective_pickup_time ?: '07:30 AM' }}</span>
                @if($a->pickup_time_override)
                  <span class="text-[9px] font-bold px-1 rounded bg-amber-100 text-amber-800 border border-amber-200" title="Custom individual student override">OVERRIDE</span>
                @endif
              </div>
            </td>

            {{-- Drop Stop & Time --}}
            <td class="td whitespace-nowrap">
              <span class="text-xs font-bold text-slate-800 block">{{ $a->effective_drop_stop_name }}</span>
              <div class="flex items-center gap-1 text-[11px] font-mono mt-0.5">
                <span class="text-blue-700 font-semibold">{{ $a->effective_drop_time ?: '04:30 PM' }}</span>
                @if($a->drop_time_override)
                  <span class="text-[9px] font-bold px-1 rounded bg-amber-100 text-amber-800 border border-amber-200" title="Custom individual student override">OVERRIDE</span>
                @endif
              </div>
            </td>

            {{-- Annual Fee --}}
            <td class="td font-mono font-bold text-xs tabular-nums text-slate-900 whitespace-nowrap">
              ₹{{ number_format($a->fee ?? 0, 0) }}
            </td>

            {{-- Status --}}
            <td class="td whitespace-nowrap">
              <span class="{{ $a->is_active ? 'badge-green' : 'badge-slate' }}">
                {{ $a->is_active ? 'Active' : 'Discontinued' }}
              </span>
            </td>

            {{-- Actions --}}
            <td class="td text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                @if($a->is_active)
                  <button type="button"
                          @click="openDiscontinueModal({{ $a->id }}, '{{ addslashes($a->student?->full_name ?? 'Student') }}')"
                          class="btn-icon text-rose-500 hover:bg-rose-50"
                          title="Discontinue Transport">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="td text-center py-12 text-slate-400">
              <p class="font-bold text-slate-700 mb-1">No student transport records found</p>
              <p class="text-xs">Adjust filter criteria or click &ldquo;Allocate Student&rdquo;.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($allotments->hasPages())
    <div class="mt-4">{{ $allotments->links() }}</div>
  @endif

  {{-- Allocate Student Transport Modal --}}
  <div x-show="allotModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="allotModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="allotModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="allotModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form method="POST" action="{{ route('transport.students.allot') }}">
          @csrf
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 class="text-base font-bold text-slate-900">Allocate Student Transport</h3>
              <button type="button" @click="allotModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div>
              <label class="label">Student <span class="text-red-500">*</span></label>
              <select name="student_id" class="select" required>
                <option value="">Select student...</option>
                @foreach(\App\Models\Student::where('status', 'active')->orderBy('first_name')->limit(300)->get() as $st)
                  <option value="{{ $st->id }}">{{ $st->full_name }} (Admn: {{ $st->admission_number }})</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="label">Transit Route <span class="text-red-500">*</span></label>
              <select name="route_id" class="select" x-model="selectedRouteId" @change="onRouteChanged()" required>
                <option value="">Choose route...</option>
                <template x-for="r in routesData" :key="r.id">
                  <option :value="r.id" x-text="r.name + ' (' + (r.vehicle_number ? r.vehicle_number : 'No vehicle') + ')'"></option>
                </template>
              </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="selectedRouteId">
              <div>
                <label class="label">Pickup Stop <span class="text-red-500">*</span></label>
                <select name="stop_id" class="select" x-model="selectedStopId" @change="onStopChanged()" required>
                  <option value="">Choose pickup point...</option>
                  <template x-for="s in availableStops" :key="s.id">
                    <option :value="s.id" x-text="s.name"></option>
                  </template>
                </select>
              </div>

              <div>
                <label class="label">Drop Stop</label>
                <select name="drop_stop_id" class="select" x-model="selectedDropStopId">
                  <option value="">Same as pickup stop</option>
                  <template x-for="s in availableStops" :key="s.id">
                    <option :value="s.id" x-text="s.name"></option>
                  </template>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="selectedStopId">
              <div>
                <label class="label">Student Pickup Timing Override</label>
                <input type="text" name="pickup_time_override" class="input font-mono" x-model="pickupOverride" placeholder="e.g. 07:25 AM">
                <p class="text-[10px] text-slate-400 mt-0.5">Customize individual student boarding time</p>
              </div>

              <div>
                <label class="label">Student Drop Timing Override</label>
                <input type="text" name="drop_time_override" class="input font-mono" x-model="dropOverride" placeholder="e.g. 04:35 PM">
                <p class="text-[10px] text-slate-400 mt-0.5">Customize individual student return time</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="selectedRouteId">
              <div>
                <label class="label">Applicable Annual Fee (₹)</label>
                <input type="number" name="fee" class="input font-mono" x-model="allotFee" step="1" min="0">
              </div>

              <div>
                <label class="label">Effective Start Date</label>
                <input type="date" name="start_date" class="input font-mono" value="{{ today()->toDateString() }}">
              </div>
            </div>

            <div>
              <label class="label">Remarks</label>
              <input type="text" name="remarks" class="input" placeholder="e.g. Boarding near temple gate">
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="allotModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Confirm Allocation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Discontinue Transport Modal --}}
  <div x-show="discontinueModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="discontinueModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="discontinueModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="discontinueModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
        <form :action="'{{ url('transport/students') }}/' + discontinueAllotmentId + '/discontinue'" method="POST">
          @csrf
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900">Discontinue School Transport</h3>
                <p class="text-xs text-slate-500">Student: <span class="font-bold text-slate-800" x-text="discontinueStudentName"></span></p>
              </div>
            </div>

            <p class="text-xs text-slate-600">
              Discontinuing will deactivate future route schedules for this student.
              All historical fee records, receipts, and past attendance remain preserved.
            </p>

            <div>
              <label class="label">Discontinue Reason</label>
              <input type="text" name="reason" class="input" placeholder="e.g. Switched to own vehicle / Parent request" value="Parent request" required>
            </div>

            <div>
              <label class="label">Effective End Date</label>
              <input type="date" name="end_date" class="input font-mono" value="{{ today()->toDateString() }}">
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="discontinueModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm bg-rose-600 hover:bg-rose-700 text-white">Confirm Discontinuation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection
