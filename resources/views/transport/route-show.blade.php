@extends('layouts.app')
@section('title', $route->route_name . ' — Route Details & Bus Stops')
@section('content')
<div class="space-y-6" x-data="{
  addModalOpen: false,
  editModalOpen: false,
  deleteModalOpen: false,
  activeStop: {},
  deleteStopId: null,
  deleteStopName: '',
  deleteStudentCount: 0,
  openEditModal(stop) {
    this.activeStop = { ...stop };
    this.editModalOpen = true;
  },
  openDeleteModal(id, name, count) {
    this.deleteStopId = id;
    this.deleteStopName = name;
    this.deleteStudentCount = count;
    this.deleteModalOpen = true;
  }
}">

  {{-- Breadcrumb & Navigation Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
        <a href="{{ route('transport.index') }}" class="hover:text-indigo-600 transition">Transport</a>
        <span>&rsaquo;</span>
        <a href="{{ route('transport.routes') }}" class="hover:text-indigo-600 transition">Routes</a>
        <span>&rsaquo;</span>
        <span class="text-slate-800 font-semibold">{{ $route->route_name }}</span>
      </nav>
      <div class="flex items-center gap-3">
        <h1 class="page-title text-2xl font-black text-slate-900 tracking-tight">{{ $route->route_name }}</h1>
        @if($route->route_number)
          <span class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-full">
            #{{ $route->route_number }}
          </span>
        @endif
        <span class="{{ $route->is_active ? 'badge-green' : 'badge-slate' }}">
          {{ $route->is_active ? 'Active Corridor' : 'Inactive' }}
        </span>
      </div>
      <p class="page-subtitle text-xs text-slate-500 mt-1">
        {{ $route->from_location ?: 'Source' }} &rarr; {{ $route->to_location ?: 'Destination' }}
        <span class="text-slate-400 mx-1.5">&middot;</span>
        <span class="font-semibold text-slate-700">{{ $stats['total_stops'] }}</span> Bus Stops Configured
      </p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('transport.routes') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Routes
      </a>
      <a href="{{ route('transport.students', ['route_id' => $route->id]) }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Enrolled Students ({{ $stats['total_students'] }})
      </a>
      <a href="{{ route('transport.routes.edit', $route->id) }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Route
      </a>
      <button type="button" @click="addModalOpen = true" class="btn btn-primary btn-sm inline-flex items-center gap-1.5 shadow-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        + Add Bus Stop
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

  {{-- Route Summary Banner Card --}}
  <div class="card p-6 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-xl relative overflow-hidden">
    <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative z-10">
      {{-- 1. Assigned Vehicle & Driver --}}
      <div class="space-y-2 border-b md:border-b-0 md:border-r border-slate-800/80 pb-4 md:pb-0 md:pr-4">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Fleet Van</p>
        @if($route->vehicle)
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-amber-400 font-bold text-lg">
              🚐
            </div>
            <div>
              <a href="{{ route('transport.vehicle.show', $route->vehicle->id) }}" class="font-mono text-base font-bold text-amber-300 hover:underline block">
                {{ $route->vehicle->vehicle_number }}
              </a>
              <p class="text-xs text-slate-300">
                {{ $route->vehicle->make }} {{ $route->vehicle->model }} &middot; {{ $stats['vehicle_capacity'] }} seats
              </p>
            </div>
          </div>
          <div class="text-xs text-slate-400 pt-1">
            <span class="text-slate-300 font-medium">Driver:</span> {{ $route->vehicle->driver_name ?: 'Not Assigned' }}
            @if($route->vehicle->driver_mobile)
              &middot; <a href="tel:{{ $route->vehicle->driver_mobile }}" class="text-indigo-300 hover:underline">{{ $route->vehicle->driver_mobile }}</a>
            @endif
          </div>
        @else
          <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-xs text-slate-300">
            ⚠️ No vehicle currently assigned.
            <a href="{{ route('transport.routes.edit', $route->id) }}" class="text-amber-300 hover:underline block mt-1 font-semibold">Assign Vehicle &rarr;</a>
          </div>
        @endif
      </div>

      {{-- 2. Bus Stops Count --}}
      <div class="flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-800/80 pb-4 md:pb-0 md:pr-4">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Bus Stops</p>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="text-3xl font-black text-white">{{ $stats['total_stops'] }}</span>
            <span class="text-xs text-slate-400">stops on corridor</span>
          </div>
        </div>
        <div class="text-xs text-slate-400 mt-2">
          Seq 1 &rarr; Seq {{ $stats['total_stops'] }} (Sequential Route)
        </div>
      </div>

      {{-- 3. Corridor Distance & Fees --}}
      <div class="flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-800/80 pb-4 md:pb-0 md:pr-4">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Corridor Distance &amp; Base Fare</p>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="text-3xl font-black text-indigo-300 font-mono">{{ number_format($stats['total_distance_km'], 1) }}</span>
            <span class="text-xs text-slate-400">KM Total</span>
          </div>
        </div>
        <div class="text-xs text-slate-400 mt-2">
          Base Fee: <span class="text-white font-bold">₹{{ number_format($route->fee, 0) }}</span> / year
        </div>
      </div>

      {{-- 4. Student Enrollment & Timings Status --}}
      <div class="flex flex-col justify-between">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Seating &amp; Timing Status</p>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="text-3xl font-black text-emerald-400">{{ $stats['total_students'] }}</span>
            <span class="text-xs text-slate-400">/ {{ $stats['vehicle_capacity'] }} seats occupied</span>
          </div>
        </div>
        <div class="mt-2">
          @if($stats['unconfigured_timings'] > 0)
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
              ⏱️ {{ $stats['unconfigured_timings'] }} stops timing pending
            </span>
          @else
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
              ✓ All timings scheduled
            </span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Bus Stops Table Card --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Sequential Bus Stops ({{ $stops->count() }})</h2>
        <p class="text-xs text-slate-500">Pick-up and drop locations ordered sequentially for {{ $route->route_name }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" @click="addModalOpen = true" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Bus Stop
        </button>
      </div>
    </div>

    <div class="table-wrap">
      <table class="w-full">
        <thead>
          <tr>
            <th class="th w-16">Stop #</th>
            <th class="th">Bus Stop Location</th>
            <th class="th">Distance</th>
            <th class="th">Annual Fare</th>
            <th class="th">Scheduled Pickup</th>
            <th class="th">Scheduled Drop</th>
            <th class="th">Enrolled Students</th>
            <th class="th">Status</th>
            <th class="th text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($stops as $stop)
            <tr class="tr {{ !$stop->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
              {{-- 1. Sequence Order --}}
              <td class="td">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-mono text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                  {{ $stop->stop_order }}
                </span>
              </td>

              {{-- 2. Stop Location & Landmark --}}
              <td class="td">
                <span class="font-bold text-slate-900 text-sm block leading-tight">{{ $stop->name }}</span>
                @if($stop->landmark)
                  <span class="text-xs text-slate-400 font-medium inline-flex items-center gap-1 mt-0.5">
                    📍 {{ $stop->landmark }}
                  </span>
                @endif
                @if($stop->pickup_instructions || $stop->drop_instructions)
                  <div class="mt-1 flex flex-wrap gap-2 text-[11px] text-slate-500">
                    @if($stop->pickup_instructions)
                      <span class="bg-slate-100 px-1.5 py-0.5 rounded">Pickup note: {{ $stop->pickup_instructions }}</span>
                    @endif
                    @if($stop->drop_instructions)
                      <span class="bg-slate-100 px-1.5 py-0.5 rounded">Drop note: {{ $stop->drop_instructions }}</span>
                    @endif
                  </div>
                @endif
              </td>

              {{-- 3. Distance in KM --}}
              <td class="td font-mono text-xs font-semibold text-slate-700 whitespace-nowrap">
                {{ number_format((float)$stop->distance_km, 1) }} KM
              </td>

              {{-- 4. Annual Fee --}}
              <td class="td font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                ₹{{ number_format($stop->fare, 0) }}
              </td>

              {{-- 5. Scheduled Pickup Time --}}
              <td class="td font-mono text-xs whitespace-nowrap">
                @if($stop->pickup_time)
                  <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 font-semibold">
                    {{ \Carbon\Carbon::parse($stop->pickup_time)->format('h:i A') }}
                  </span>
                @else
                  <span class="badge-slate font-medium text-[11px] text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">
                    Not Set
                  </span>
                @endif
              </td>

              {{-- 6. Scheduled Drop Time --}}
              <td class="td font-mono text-xs whitespace-nowrap">
                @if($stop->drop_time)
                  <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 font-semibold">
                    {{ \Carbon\Carbon::parse($stop->drop_time)->format('h:i A') }}
                  </span>
                @else
                  <span class="badge-slate font-medium text-[11px] text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">
                    Not Set
                  </span>
                @endif
              </td>

              {{-- 7. Boarding Students --}}
              <td class="td whitespace-nowrap">
                <a href="{{ route('transport.students', ['stop_id' => $stop->id]) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold {{ $stop->active_students_count > 0 ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200' : 'bg-slate-100 text-slate-400' }}">
                  <span>{{ $stop->active_students_count ?? 0 }}</span>
                  <span class="text-[10px] font-normal">students</span>
                </a>
              </td>

              {{-- 8. Status --}}
              <td class="td whitespace-nowrap">
                <span class="{{ $stop->is_active ? 'badge-green' : 'badge-slate' }}">
                  {{ $stop->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              {{-- 9. Actions --}}
              <td class="td text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  {{-- Edit Stop Trigger --}}
                  <button type="button"
                          @click="openEditModal({
                            id: {{ $stop->id }},
                            name: '{{ addslashes($stop->name) }}',
                            stop_order: {{ $stop->stop_order }},
                            distance_km: '{{ $stop->distance_km }}',
                            fare: '{{ $stop->fare }}',
                            pickup_time: '{{ $stop->pickup_time ? \Carbon\Carbon::parse($stop->pickup_time)->format('H:i') : '' }}',
                            drop_time: '{{ $stop->drop_time ? \Carbon\Carbon::parse($stop->drop_time)->format('H:i') : '' }}',
                            landmark: '{{ addslashes($stop->landmark ?? '') }}',
                            pickup_instructions: '{{ addslashes($stop->pickup_instructions ?? '') }}',
                            drop_instructions: '{{ addslashes($stop->drop_instructions ?? '') }}',
                            remarks: '{{ addslashes($stop->remarks ?? '') }}',
                            is_active: {{ $stop->is_active ? 'true' : 'false' }}
                          })"
                          class="btn-icon text-slate-600 hover:bg-slate-100"
                          title="Edit Stop &amp; Timings">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                  </button>

                  {{-- Toggle Status --}}
                  <form method="POST" action="{{ route('transport.stops.toggle', $stop->id) }}" class="inline m-0 p-0">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="btn-icon {{ $stop->is_active ? 'text-amber-500 hover:bg-amber-50' : 'text-green-600 hover:bg-green-50' }}"
                            title="{{ $stop->is_active ? 'Deactivate Stop' : 'Activate Stop' }}">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                  </form>

                  {{-- Safe Delete --}}
                  <button type="button"
                          @click="openDeleteModal({{ $stop->id }}, '{{ addslashes($stop->name) }}', {{ $stop->active_students_count ?? 0 }})"
                          class="btn-icon text-red-500 hover:bg-red-50"
                          title="Delete or Reassign Stop">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="td text-center py-8 text-slate-400">
                <p class="font-bold text-slate-700 mb-1">No bus stops configured on this route</p>
                <p class="text-xs">Click "+ Add Bus Stop" to configure pickup and drop locations.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Add Bus Stop Modal --}}
  <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="addModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="addModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="addModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form method="POST" action="{{ route('transport.stops.store') }}">
          @csrf
          <input type="hidden" name="route_id" value="{{ $route->id }}">
          @if($route->vehicle_id)
            <input type="hidden" name="vehicle_id" value="{{ $route->vehicle_id }}">
          @endif

          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 class="text-base font-bold text-slate-900">Add Bus Stop to {{ $route->route_name }}</h3>
                <p class="text-xs text-slate-500">Configure stop location, distance, fare, and optional timing</p>
              </div>
              <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="sm:col-span-2">
                <label class="label">Stop Name / Location <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="input" placeholder="e.g. KOLLAMPALAYAM" required>
              </div>

              <div>
                <label class="label">Stop Sequence # <span class="text-red-500">*</span></label>
                <input type="number" name="stop_order" class="input" value="{{ $stops->max('stop_order') + 1 }}" min="1" required>
              </div>

              <div>
                <label class="label">Distance from School (KM) <span class="text-red-500">*</span></label>
                <input type="number" name="distance_km" class="input" step="0.1" min="0" placeholder="e.g. 5.0" required>
              </div>

              <div class="sm:col-span-2">
                <label class="label">Annual Transport Fee (₹) <span class="text-red-500">*</span></label>
                <input type="number" name="fare" class="input" step="1" min="0" placeholder="e.g. 12000" required>
              </div>

              <div>
                <label class="label">Scheduled Pickup Time <span class="text-xs text-slate-400">(Optional)</span></label>
                <input type="time" name="pickup_time" class="input" placeholder="Optional">
                <p class="text-[10px] text-slate-400 mt-1">Leave blank if not yet scheduled</p>
              </div>

              <div>
                <label class="label">Scheduled Drop Time <span class="text-xs text-slate-400">(Optional)</span></label>
                <input type="time" name="drop_time" class="input" placeholder="Optional">
                <p class="text-[10px] text-slate-400 mt-1">Leave blank if not yet scheduled</p>
              </div>

              <div class="sm:col-span-2">
                <label class="label">Landmark / Waiting Area</label>
                <input type="text" name="landmark" class="input" placeholder="e.g. Near Bus Stand Arch">
              </div>

              <div>
                <label class="label">Pickup Instructions</label>
                <input type="text" name="pickup_instructions" class="input" placeholder="e.g. Stand near gate">
              </div>

              <div>
                <label class="label">Drop Instructions</label>
                <input type="text" name="drop_instructions" class="input" placeholder="e.g. Parent must receive">
              </div>
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="addModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Add Bus Stop</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Edit Bus Stop Modal --}}
  <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="editModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="editModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form :action="'{{ url('transport/stops') }}/' + activeStop.id" method="POST">
          @csrf @method('PUT')
          <input type="hidden" name="route_id" value="{{ $route->id }}">
          @if($route->vehicle_id)
            <input type="hidden" name="vehicle_id" value="{{ $route->vehicle_id }}">
          @endif

          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 class="text-base font-bold text-slate-900">Edit Bus Stop: <span x-text="activeStop.name"></span></h3>
                <p class="text-xs text-slate-500">Update sequence, distance, fee, or scheduled timings</p>
              </div>
              <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="sm:col-span-2">
                <label class="label">Stop Name / Location <span class="text-red-500">*</span></label>
                <input type="text" name="name" x-model="activeStop.name" class="input" required>
              </div>

              <div>
                <label class="label">Stop Sequence # <span class="text-red-500">*</span></label>
                <input type="number" name="stop_order" x-model="activeStop.stop_order" class="input" min="1" required>
              </div>

              <div>
                <label class="label">Distance from School (KM) <span class="text-red-500">*</span></label>
                <input type="number" name="distance_km" x-model="activeStop.distance_km" class="input" step="0.1" min="0" required>
              </div>

              <div class="sm:col-span-2">
                <label class="label">Annual Transport Fee (₹) <span class="text-red-500">*</span></label>
                <input type="number" name="fare" x-model="activeStop.fare" class="input" step="1" min="0" required>
              </div>

              <div>
                <label class="label">Scheduled Pickup Time <span class="text-xs text-slate-400">(Optional)</span></label>
                <input type="time" name="pickup_time" x-model="activeStop.pickup_time" class="input">
                <p class="text-[10px] text-slate-400 mt-1">Clear to display "Not Set"</p>
              </div>

              <div>
                <label class="label">Scheduled Drop Time <span class="text-xs text-slate-400">(Optional)</span></label>
                <input type="time" name="drop_time" x-model="activeStop.drop_time" class="input">
                <p class="text-[10px] text-slate-400 mt-1">Clear to display "Not Set"</p>
              </div>

              <div class="sm:col-span-2">
                <label class="label">Landmark / Waiting Area</label>
                <input type="text" name="landmark" x-model="activeStop.landmark" class="input">
              </div>

              <div>
                <label class="label">Pickup Instructions</label>
                <input type="text" name="pickup_instructions" x-model="activeStop.pickup_instructions" class="input">
              </div>

              <div>
                <label class="label">Drop Instructions</label>
                <input type="text" name="drop_instructions" x-model="activeStop.drop_instructions" class="input">
              </div>

              <div class="sm:col-span-2">
                <label class="label">Remarks</label>
                <input type="text" name="remarks" x-model="activeStop.remarks" class="input">
              </div>
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="editModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Update Bus Stop</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Safe Delete / Deactivate Stop Modal --}}
  <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="deleteModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="deleteModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="deleteModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
        <form :action="'{{ url('transport/stops') }}/' + deleteStopId" method="POST">
          @csrf @method('DELETE')
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900">Remove Bus Stop</h3>
                <p class="text-xs text-slate-500">Stop: <span class="font-bold text-slate-800" x-text="deleteStopName"></span></p>
              </div>
            </div>

            <template x-if="deleteStudentCount > 0">
              <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-2">
                <p class="font-bold">⚠️ <span x-text="deleteStudentCount"></span> Active Students Board Here</p>
                <p>To preserve student transport records, you can reassign them to another stop or safely deactivate this stop.</p>
                <div>
                  <label class="label text-amber-950">Reassign Students To (Optional)</label>
                  <select name="reassign_to_stop_id" class="select text-xs">
                    <option value="">Do not reassign (Deactivate stop safely)</option>
                    @foreach($stops as $altStop)
                      <option value="{{ $altStop->id }}">{{ $altStop->name }} (Stop #{{ $altStop->stop_order }})</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </template>

            <template x-if="deleteStudentCount === 0">
              <p class="text-xs text-slate-600">No students are currently allotted to this stop. It is safe to remove.</p>
            </template>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="deleteModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm bg-red-600 hover:bg-red-700 text-white">Confirm Removal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection
