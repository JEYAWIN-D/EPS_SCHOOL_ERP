@extends('layouts.app')
@section('title', 'Van Details — ' . $vehicle->vehicle_number)
@section('content')
@php
  $vanCodes = [5 => 'VAN I', 6 => 'VAN II', 7 => 'VAN III', 8 => 'VAN IV', 9 => 'VAN V'];
  $currentVanCode = $vanCodes[$vehicle->id] ?? null;
@endphp
<div class="space-y-6">
  {{-- Header with Breadcrumbs & Actions --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
        <a href="{{ route('transport.index') }}" class="hover:text-indigo-600">Transport</a>
        <span>/</span>
        <a href="{{ route('transport.vehicles') }}" class="hover:text-indigo-600">Vehicles</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">{{ $vehicle->vehicle_number }}</span>
      </div>
      <div class="flex items-center gap-3">
        <h1 class="page-title text-2xl font-extrabold tracking-tight text-slate-900">{{ $vehicle->vehicle_number }}</h1>
        @if($currentVanCode)
          <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-xs">
            {{ $currentVanCode }}
          </span>
        @endif
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $vehicle->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
          {{ $vehicle->is_active ? 'Active' : 'Inactive' }}
        </span>
        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
          {{ ucfirst($vehicle->vehicle_type ?? 'Van') }}
        </span>
      </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      @if($currentVanCode)
        <a href="{{ route('transport.roster', ['van' => $currentVanCode]) }}" class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-sm font-bold">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Passenger Manifest ({{ $vehicle->allotments->count() }})
        </a>
      @endif
      <a href="{{ route('transport.vehicle.edit', $vehicle->id) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Van
      </a>
      <a href="{{ route('transport.tracking') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-emerald-700">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
        Live GPS
      </a>
      <form method="POST" action="{{ route('transport.vehicle.destroy', $vehicle->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.');">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 flex items-center gap-1.5">
          <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>

  @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert-warning">
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Quick Capacity & Route KPI Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="card p-4 border-l-4 border-l-indigo-600">
      <div class="text-xs text-slate-500 font-bold uppercase">Seating Capacity</div>
      <div class="text-2xl font-extrabold text-slate-800 mt-1">{{ $vehicle->effective_capacity }} Seats</div>
      <div class="text-xs text-slate-500 mt-0.5">Total capacity configured</div>
    </div>
    <div class="card p-4 border-l-4 border-l-blue-600">
      <div class="text-xs text-slate-500 font-bold uppercase">Occupied Seats</div>
      <div class="text-2xl font-extrabold text-blue-700 mt-1">{{ $vehicle->occupied_seats }} Students</div>
      <div class="text-xs text-slate-500 mt-0.5">Active transport allotments</div>
    </div>
    <div class="card p-4 border-l-4 {{ $vehicle->available_seats > 0 ? 'border-l-emerald-600' : 'border-l-red-600' }}">
      <div class="text-xs text-slate-500 font-bold uppercase">Available Seats</div>
      <div class="text-2xl font-extrabold {{ $vehicle->available_seats > 0 ? 'text-emerald-700' : 'text-red-700' }} mt-1">
        {{ $vehicle->available_seats }} Seats
      </div>
      <div class="text-xs {{ $vehicle->available_seats > 0 ? 'text-emerald-600' : 'text-red-600' }} mt-0.5 font-semibold">
        {{ $vehicle->available_seats > 0 ? 'Vacant for new admissions' : 'Full / No seats vacant' }}
      </div>
    </div>
    <div class="card p-4 border-l-4 border-l-purple-600">
      <div class="text-xs text-slate-500 font-bold uppercase">Assigned Route</div>
      <div class="text-lg font-bold text-purple-900 mt-1 truncate" title="{{ $vehicle->route?->route_name ?? 'Unassigned' }}">
        {{ $vehicle->route?->route_name ?? 'Not Assigned' }}
      </div>
      <div class="text-xs text-slate-500 mt-0.5">
        {{ $vehicle->route ? $vehicle->route->stops->count() . ' stops (' . $vehicle->route->distance_km . ' km)' : 'Assign in Edit Van' }}
      </div>
    </div>
  </div>

  {{-- Main Grid: 2 Columns --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left 2 cols: Vehicle Specs, Driver, Attendant, and Route Schedule --}}
    <div class="lg:col-span-2 space-y-6">
      
      {{-- Card 1: Van & Vehicle Details --}}
      <div class="card p-5 space-y-4">
        <h2 class="text-base font-extrabold text-slate-800 flex items-center justify-between">
          <span>Vehicle Specifications &amp; Registration</span>
          <span class="text-xs font-mono bg-slate-100 text-slate-700 px-2 py-0.5 rounded">{{ $vehicle->vehicle_number }}</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <span class="text-slate-400 font-semibold block uppercase text-[10px]">Vehicle Number</span>
            <span class="font-bold text-slate-800 text-sm font-mono">{{ $vehicle->vehicle_number }}</span>
          </div>
          <div>
            <span class="text-slate-400 font-semibold block uppercase text-[10px]">Registration No</span>
            <span class="font-bold text-slate-800 text-sm font-mono">{{ $vehicle->registration_number ?? $vehicle->vehicle_number }}</span>
          </div>
          <div>
            <span class="text-slate-400 font-semibold block uppercase text-[10px]">Vehicle Type</span>
            <span class="font-semibold text-slate-800 text-sm">{{ ucfirst($vehicle->vehicle_type ?? 'Van') }}</span>
          </div>
          <div>
            <span class="text-slate-400 font-semibold block uppercase text-[10px]">Make &amp; Model</span>
            <span class="font-semibold text-slate-800 text-sm">{{ $vehicle->make }} {{ $vehicle->model }}</span>
          </div>
          <div>
            <span class="text-slate-400 font-semibold block uppercase text-[10px]">Total Seats</span>
            <span class="font-bold text-slate-800 text-sm">{{ $vehicle->effective_capacity }} Seats</span>
          </div>
          <div>
            <span class="text-slate-400 font-semibold block uppercase text-[10px]">GPS Status</span>
            <span class="font-semibold text-emerald-700 text-sm flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              {{ ucfirst($vehicle->gps_status ?? 'Online') }}
            </span>
          </div>
        </div>
      </div>

      {{-- Card 2: Driver & Nanny / Attendant Details --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Driver Details --}}
        <div class="card p-5 space-y-3 border-t-4 border-t-amber-500">
          <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-sm text-slate-800 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              Assigned Driver
            </h3>
            <span class="badge-amber text-[10px]">Driver</span>
          </div>
          <div class="space-y-2 text-xs">
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Driver Name</span>
              <span class="font-bold text-slate-900 text-sm">{{ $vehicle->driver_name ?? 'Not Assigned' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Mobile Number</span>
              @if($vehicle->driver_mobile)
                <a href="tel:{{ $vehicle->driver_mobile }}" class="font-semibold text-indigo-600 hover:underline flex items-center gap-1">
                  📞 {{ $vehicle->driver_mobile }}
                </a>
              @else
                <span class="text-slate-400">—</span>
              @endif
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Driving Licence #</span>
              <span class="font-mono text-slate-700">{{ $vehicle->driver_license ?? '—' }}</span>
              @if($vehicle->driver_license_expiry)
                <span class="text-slate-400 text-[11px] block">Exp: {{ \Carbon\Carbon::parse($vehicle->driver_license_expiry)->format('d M Y') }}</span>
              @endif
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Address</span>
              <span class="text-slate-600">{{ $vehicle->driver_address ?? '—' }}</span>
            </div>
          </div>
        </div>

        {{-- Attendant / Nanny Details --}}
        <div class="card p-5 space-y-3 border-t-4 border-t-pink-500">
          <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-sm text-slate-800 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              Assigned Nanny / Attendant
            </h3>
            <span class="badge-pink text-[10px] bg-pink-50 text-pink-700 border border-pink-200">Nanny / Attendant</span>
          </div>
          <div class="space-y-2 text-xs">
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Attendant Name</span>
              <span class="font-bold text-slate-900 text-sm">{{ $vehicle->attendant_name ?? 'Not Assigned' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Mobile Number</span>
              @if($vehicle->attendant_mobile)
                <a href="tel:{{ $vehicle->attendant_mobile }}" class="font-semibold text-indigo-600 hover:underline flex items-center gap-1">
                  📞 {{ $vehicle->attendant_mobile }}
                </a>
              @else
                <span class="text-slate-400">—</span>
              @endif
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Aadhaar / ID Card</span>
              <span class="font-mono text-slate-700">{{ $vehicle->attendant_aadhaar ?? '—' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Address</span>
              <span class="text-slate-600">{{ $vehicle->attendant_address ?? '—' }}</span>
            </div>
          </div>
        </div>
      </div>

      {{-- Card 3: Route & Schedule Stops Sequence --}}
      <div class="card p-5 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <h3 class="font-extrabold text-sm text-slate-800">
              Assigned Route: {{ $vehicle->route?->route_name ?? 'No Route Attached' }}
            </h3>
            @if($vehicle->route)
              <p class="text-xs text-slate-500 mt-0.5">
                From <span class="font-semibold text-slate-700">{{ $vehicle->route->from_location }}</span> to <span class="font-semibold text-slate-700">{{ $vehicle->route->to_location }}</span> &bull; Total Distance: {{ $vehicle->route->distance_km }} km
              </p>
            @endif
          </div>
          @if($vehicle->route)
            <a href="{{ route('transport.stops', ['route_id' => $vehicle->route->id]) }}" class="btn-xs text-indigo-600 bg-indigo-50 border border-indigo-200">
              Manage Stops
            </a>
          @endif
        </div>

        @if($vehicle->route && $vehicle->route->stops->count())
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead class="bg-slate-50 border-b text-slate-500 uppercase tracking-wider text-[10px]">
                <tr>
                  <th class="px-3 py-2 text-left">#</th>
                  <th class="px-3 py-2 text-left">Stop Name</th>
                  <th class="px-3 py-2 text-right">Distance</th>
                  <th class="px-3 py-2 text-center">Morning Pickup</th>
                  <th class="px-3 py-2 text-center">Evening Drop</th>
                  <th class="px-3 py-2 text-right">Transport Fee</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($vehicle->route->stops as $stop)
                  <tr class="hover:bg-slate-50/70">
                    <td class="px-3 py-2.5 font-bold text-slate-400">{{ $stop->stop_order }}</td>
                    <td class="px-3 py-2.5 font-bold text-slate-800">
                      {{ $stop->name }}
                      @if($stop->landmark && $stop->landmark !== $stop->name)
                        <span class="text-slate-400 font-normal block text-[11px]">{{ $stop->landmark }}</span>
                      @endif
                    </td>
                    <td class="px-3 py-2.5 text-right font-mono text-slate-600">{{ $stop->distance_km }} km</td>
                    <td class="px-3 py-2.5 text-center font-mono text-emerald-700">{{ $stop->pickup_time ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-center font-mono text-blue-700">{{ $stop->drop_time ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-right font-extrabold text-[#8C2826] tabular-nums">
                      ₹{{ number_format($stop->fare, 0) }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="py-8 text-center text-slate-400 text-xs">
            No stops configured for this route yet.
          </div>
        @endif
      </div>

      {{-- Card 4: Assigned Students Allocation List --}}
      <div class="card p-5 space-y-4 shadow-sm border border-slate-200">
        <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-slate-100">
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
              <span>Assigned Students ({{ $vehicle->allotments->count() }} Allocated)</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">All passengers currently registered to {{ $vehicle->vehicle_number }} with stops and fare</p>
          </div>
          <div class="flex items-center gap-2">
            @if($currentVanCode)
              <a href="{{ route('transport.roster', ['van' => $currentVanCode]) }}" class="btn-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 font-bold px-2.5 py-1 rounded inline-flex items-center gap-1">
                <span>View Van Roster</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </a>
            @endif
            <a href="{{ route('transport.allotment', ['route_id' => $vehicle->route_id]) }}" class="btn-xs text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-300 font-semibold px-2.5 py-1 rounded">
              + Allocate More
            </a>
          </div>
        </div>

        @if($vehicle->allotments->count())
          {{-- Search Filter --}}
          <div class="relative">
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="vanStudentSearch" placeholder="Filter assigned students by name, stop, or admission #..." class="input pl-8 py-1.5 text-xs w-full bg-slate-50 border-slate-200 focus:bg-white" onkeyup="filterVehicleStudents()">
          </div>

          <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-xs" id="vehicleStudentsTable">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[10px] font-bold">
                <tr>
                  <th class="px-3 py-2 text-center w-8">#</th>
                  <th class="px-3 py-2 text-left">Student Details</th>
                  <th class="px-3 py-2 text-center">Class</th>
                  <th class="px-3 py-2 text-left">Boarding Stop</th>
                  <th class="px-3 py-2 text-center">Morning Pickup</th>
                  <th class="px-3 py-2 text-center">Evening Drop</th>
                  <th class="px-3 py-2 text-right">Transport Fee</th>
                  <th class="px-3 py-2 text-center">Status</th>
                  <th class="px-3 py-2 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($vehicle->allotments as $idx => $allotment)
                  @php
                    $st = $allotment->student;
                    $clsName = $allotment->enrollment?->class?->name ?? $st?->admitted_class ?? '—';
                    $secName = $allotment->enrollment?->section?->name;
                  @endphp
                  <tr class="hover:bg-amber-50/30 transition-colors student-row">
                    <td class="px-3 py-2.5 text-center text-slate-400 font-bold text-[11px]">{{ $idx + 1 }}</td>
                    <td class="px-3 py-2.5">
                      @if($st)
                        <a href="{{ route('students.show', $st->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 block text-xs student-name">
                          {{ $st->full_name }}
                        </a>
                        <span class="text-slate-400 text-[10px] font-mono student-adm">{{ $st->admission_number ?? $st->admission_no }}</span>
                      @else
                        <span class="text-slate-400 italic">Unlinked Student</span>
                      @endif
                    </td>
                    <td class="px-3 py-2.5 text-center">
                      <span class="inline-block px-2 py-0.5 rounded font-black text-[10px] bg-slate-100 text-slate-800 border border-slate-200">
                        {{ $clsName }}{{ $secName ? ' - ' . $secName : '' }}
                      </span>
                    </td>
                    <td class="px-3 py-2.5">
                      <span class="font-semibold text-slate-800 block text-xs student-stop">
                        {{ $allotment->stop?->name ?? $allotment->pickup_stop ?? $allotment->boarding_stop ?? '—' }}
                      </span>
                      @if($allotment->stop?->landmark)
                        <span class="text-[10px] text-slate-400 block">{{ $allotment->stop->landmark }}</span>
                      @endif
                    </td>
                    <td class="px-3 py-2.5 text-center">
                      @if($allotment->pickup_time)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                          {{ \Carbon\Carbon::parse($allotment->pickup_time)->format('h:i A') }}
                        </span>
                      @else
                        <span class="text-slate-300 font-mono text-xs">—</span>
                      @endif
                    </td>
                    <td class="px-3 py-2.5 text-center">
                      @if($allotment->drop_time)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-blue-50 text-blue-700 border border-blue-200">
                          {{ \Carbon\Carbon::parse($allotment->drop_time)->format('h:i A') }}
                        </span>
                      @else
                        <span class="text-slate-300 font-mono text-xs">—</span>
                      @endif
                    </td>
                    <td class="px-3 py-2.5 text-right">
                      <span class="font-extrabold text-[#8C2826] text-xs tabular-nums">
                        ₹{{ number_format($allotment->fee ?? 0, 0) }}
                      </span>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                      </span>
                    </td>
                    <td class="px-3 py-2.5 text-right">
                      <form method="POST" action="{{ route('transport.allotment.delete', $allotment->id) }}" class="inline" onsubmit="return confirm('Remove student from transport allotment?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1 rounded transition text-xs font-semibold" title="Remove student from van">
                          Remove
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <script>
            function filterVehicleStudents() {
              const q = document.getElementById('vanStudentSearch').value.toLowerCase().trim();
              const rows = document.querySelectorAll('#vehicleStudentsTable tbody tr.student-row');
              rows.forEach(r => {
                const name = r.querySelector('.student-name')?.textContent.toLowerCase() || '';
                const adm = r.querySelector('.student-adm')?.textContent.toLowerCase() || '';
                const stop = r.querySelector('.student-stop')?.textContent.toLowerCase() || '';
                if (!q || name.includes(q) || adm.includes(q) || stop.includes(q)) {
                  r.style.display = '';
                } else {
                  r.style.display = 'none';
                }
              });
            }
          </script>
        @else
          <div class="py-10 text-center text-slate-400 text-xs">
            No students currently assigned to this van.
          </div>
        @endif
      </div>

    </div>

    {{-- Right 1 col: Vehicle Document Manager --}}
    <div class="space-y-6">
      
      {{-- Document Upload Card --}}
      <div class="card p-5 space-y-4 border-t-4 border-t-indigo-600">
        <h3 class="font-extrabold text-sm text-slate-800 flex items-center justify-between">
          <span>Upload Vehicle Document</span>
          <span class="text-xs text-slate-400 font-normal">Max 10MB</span>
        </h3>

        <form method="POST" action="{{ route('transport.vehicle.documents.store', $vehicle->id) }}" enctype="multipart/form-data" class="space-y-3 text-xs">
          @csrf
          <div>
            <label class="label text-[11px]">Document Title *</label>
            <input type="text" name="title" required placeholder="e.g. Registration Certificate (RC)" class="input text-xs" value="{{ old('title') }}">
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="label text-[11px]">Document Type *</label>
              <select name="document_type" required class="select text-xs">
                <option value="rc">Registration (RC)</option>
                <option value="insurance">Insurance Policy</option>
                <option value="fitness">Fitness Certificate</option>
                <option value="permit">Route Permit</option>
                <option value="puc">PUC / Emission</option>
                <option value="tax">Road Tax</option>
                <option value="driver_license">Driver License</option>
                <option value="attendant_id">Attendant ID Proof</option>
                <option value="other">Other Document</option>
              </select>
            </div>
            <div>
              <label class="label text-[11px]">Expiry Date</label>
              <input type="date" name="expiry_date" class="input text-xs" value="{{ old('expiry_date') }}">
            </div>
          </div>

          <div>
            <label class="label text-[11px]">Select File (PDF / Images / Docs) *</label>
            <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" class="input text-xs file:mr-3 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
          </div>

          <div>
            <label class="label text-[11px]">Notes / Remarks (Optional)</label>
            <textarea name="notes" rows="2" placeholder="Policy number, issuing authority, etc." class="input text-xs">{{ old('notes') }}</textarea>
          </div>

          <button type="submit" class="btn btn-primary w-full text-xs py-2">
            Upload Document
          </button>
        </form>
      </div>

      {{-- Uploaded Documents List Card --}}
      <div class="card p-5 space-y-4">
        <h3 class="font-extrabold text-sm text-slate-800 flex items-center justify-between">
          <span>Uploaded Documents</span>
          <span class="text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-mono">{{ $vehicle->documents->count() }}</span>
        </h3>

        @if($vehicle->documents->count())
          <div class="space-y-3">
            @foreach($vehicle->documents as $doc)
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1">
                    <span class="font-bold text-slate-800 block text-xs">{{ $doc->title }}</span>
                    <span class="text-[10px] text-slate-500 uppercase font-semibold">{{ strtoupper($doc->document_type) }}</span>
                  </div>
                  @if($doc->expiry_date)
                    @if($doc->is_expired)
                      <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">Expired</span>
                    @elseif($doc->is_expiring_soon)
                      <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">Due Soon</span>
                    @else
                      <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700">Valid</span>
                    @endif
                  @endif
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-200">
                  <span>
                    @if($doc->expiry_date)
                      Exp: {{ $doc->expiry_date->format('d M Y') }}
                    @else
                      No expiry set
                    @endif
                  </span>
                  <span>{{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</span>
                </div>

                @if($doc->notes)
                  <p class="text-[11px] text-slate-600 bg-white p-1.5 rounded border border-slate-100">
                    {{ $doc->notes }}
                  </p>
                @endif

                <div class="flex items-center justify-end gap-2 pt-1">
                  <a href="{{ route('transport.documents.view', $doc->id) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]">
                    Preview
                  </a>
                  <span class="text-slate-300">&bull;</span>
                  <a href="{{ route('transport.documents.download', $doc->id) }}" class="text-slate-700 hover:text-slate-900 font-semibold text-[11px]">
                    Download
                  </a>
                  <span class="text-slate-300">&bull;</span>
                  <form method="POST" action="{{ route('transport.documents.destroy', $doc->id) }}" class="inline" onsubmit="return confirm('Delete document {{ $doc->title }}?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold text-[11px]">
                      Delete
                    </button>
                  </form>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="py-6 text-center text-slate-400 text-xs">
            No documents uploaded yet for this vehicle.<br>
            Upload RC, Insurance, or Fitness above.
          </div>
        @endif
      </div>

      {{-- Document Expiry Dates Quick Summary --}}
      <div class="card p-5 space-y-3 text-xs">
        <h4 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Statutory Expiries</h4>
        <div class="space-y-2 divide-y divide-slate-100">
          <div class="flex justify-between py-1">
            <span class="text-slate-500">Fitness Certificate</span>
            <span class="font-semibold {{ $vehicle->fitness_expiry && $vehicle->fitness_expiry->isPast() ? 'text-red-600 font-bold' : 'text-slate-800' }}">
              {{ $vehicle->fitness_expiry ? $vehicle->fitness_expiry->format('d M Y') : '—' }}
            </span>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-500">Insurance Policy</span>
            <span class="font-semibold {{ $vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast() ? 'text-red-600 font-bold' : 'text-slate-800' }}">
              {{ $vehicle->insurance_expiry ? $vehicle->insurance_expiry->format('d M Y') : '—' }}
            </span>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-500">Transport Permit</span>
            <span class="font-semibold {{ $vehicle->permit_expiry && $vehicle->permit_expiry->isPast() ? 'text-red-600 font-bold' : 'text-slate-800' }}">
              {{ $vehicle->permit_expiry ? $vehicle->permit_expiry->format('d M Y') : '—' }}
            </span>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-500">PUC / Pollution</span>
            <span class="font-semibold {{ $vehicle->puc_expiry && $vehicle->puc_expiry->isPast() ? 'text-red-600 font-bold' : 'text-slate-800' }}">
              {{ $vehicle->puc_expiry ? $vehicle->puc_expiry->format('d M Y') : '—' }}
            </span>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-500">Road Tax</span>
            <span class="font-semibold {{ $vehicle->tax_expiry && $vehicle->tax_expiry->isPast() ? 'text-red-600 font-bold' : 'text-slate-800' }}">
              {{ $vehicle->tax_expiry ? $vehicle->tax_expiry->format('d M Y') : '—' }}
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
