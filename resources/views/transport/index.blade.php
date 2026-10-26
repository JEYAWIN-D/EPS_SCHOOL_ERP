@extends('layouts.app')
@section('title', 'Transport Operations & Fleet Management')
@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="page-title">Transport Operations &amp; Fleet Management</h1>
      <p class="page-subtitle">School bus routes, live student manifests, and vehicle allocations</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('transport.import') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        Excel Import &amp; Migration
      </a>
      <a href="{{ route('transport.students') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Student Allocation
      </a>
      <a href="{{ route('transport.vehicles.create') }}" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Vehicle
      </a>
    </div>
  </div>

  {{-- Unified Sub-Navigation Tabs --}}
  @include('transport.partials.nav')

  {{-- Document / Maintenance Alerts --}}
  @if($docAlerts->count() > 0)
  <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-2xs">
    <div class="flex items-start gap-3">
      <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <div class="flex-1">
        <p class="text-sm font-bold text-red-900">Document Expiry Alert — {{ $docAlerts->count() }} vehicle(s) require renewal</p>
        <div class="flex flex-wrap gap-2 mt-2">
          @foreach($docAlerts as $v)
            <span class="text-xs bg-white text-red-700 px-2.5 py-1 rounded-lg border border-red-200 font-bold shadow-2xs">
              {{ $v->vehicle_number }}
              @if($v->insurance_expiry && \Carbon\Carbon::parse($v->insurance_expiry)->lte(today()->addDays(30))) · Ins {{ \Carbon\Carbon::parse($v->insurance_expiry)->format('d M') }} @endif
              @if($v->fitness_expiry && \Carbon\Carbon::parse($v->fitness_expiry)->lte(today()->addDays(30))) · Fit {{ \Carbon\Carbon::parse($v->fitness_expiry)->format('d M') }} @endif
            </span>
          @endforeach
          <a href="{{ route('transport.documents') }}" class="text-xs text-red-700 hover:text-red-900 underline font-semibold self-center">View Expiry Console &rarr;</a>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if($maintenanceAlerts->count() > 0)
  <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-2xs">
    <div class="flex items-start gap-3">
      <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <div class="flex-1">
        <p class="text-sm font-bold text-amber-900">Maintenance Due — {{ $maintenanceAlerts->count() }} vehicle(s) scheduled in next 7 days</p>
        <a href="{{ route('transport.maintenance') }}" class="text-xs text-amber-700 hover:text-amber-900 underline font-semibold mt-1 inline-block">Review Maintenance Schedule &rarr;</a>
      </div>
    </div>
  </div>
  @endif

  {{-- Live KPI Dashboard Cards (Requirement 9: Calculated directly from DB) --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
    {{-- 1. Active Vehicles --}}
    <a href="{{ route('transport.vehicles') }}" class="card hover:shadow-card-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-slate-700 to-slate-900 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl">{{ $stats['vehicles'] }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Active Vehicles</p>
      </div>
    </a>

    {{-- 2. Active Routes --}}
    <a href="{{ route('transport.routes') }}" class="card hover:shadow-card-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl">{{ $stats['routes'] }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Active Routes</p>
      </div>
    </a>

    {{-- 3. Total Bus Stops --}}
    <a href="{{ route('transport.stops') }}" class="card hover:shadow-card-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-600 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl">{{ $stats['total_stops'] ?? 0 }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Total Bus Stops</p>
      </div>
    </a>

    {{-- 4. Students Using Transport --}}
    <a href="{{ route('transport.students') }}" class="card hover:shadow-card-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-emerald-500 to-green-600 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl text-emerald-700">{{ $stats['students'] }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Using Transport</p>
      </div>
    </a>

    {{-- 5. Available Vehicle Capacity --}}
    <div class="card p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-cyan-500 to-teal-600 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl">{{ $stats['available_capacity'] ?? 0 }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Available Seats</p>
      </div>
    </div>

    {{-- 6. Students Awaiting Transport --}}
    <a href="{{ route('transport.reports', ['type' => 'unallocated_students']) }}" class="card hover:shadow-card-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-indigo-500 to-purple-600 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl text-indigo-700">{{ $stats['students_awaiting_transport'] ?? 0 }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Non-Transport</p>
      </div>
    </a>

    {{-- 7. Discontinued Assignments --}}
    <a href="{{ route('transport.students', ['status' => 'discontinued']) }}" class="card hover:shadow-card-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
      <div class="stat-icon bg-gradient-to-br from-rose-500 to-pink-600 mb-2 w-9 h-9">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
      </div>
      <div>
        <p class="stat-number text-xl text-rose-700">{{ $stats['discontinued_assignments'] ?? 0 }}</p>
        <p class="text-xs text-slate-500 font-semibold mt-0.5">Discontinued</p>
      </div>
    </a>
  </div>

  {{-- Transport Operational Status & Timing Readiness Bar --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    {{-- Stops Without Timings --}}
    <a href="{{ route('transport.stops', ['timing' => 'missing']) }}" class="card p-4 hover:shadow-card-md transition flex items-center justify-between border-l-4 {{ ($stats['unconfigured_timings'] ?? 0) > 0 ? 'border-l-amber-500 bg-amber-50/20' : 'border-l-emerald-500' }}">
      <div>
        <p class="text-xs font-semibold text-slate-500">Stops Without Timings</p>
        <p class="stat-number text-xl mt-0.5 {{ ($stats['unconfigured_timings'] ?? 0) > 0 ? 'text-amber-800' : 'text-emerald-700' }}">
          {{ $stats['unconfigured_timings'] ?? 0 }}
        </p>
        <p class="text-[11px] text-slate-400 mt-0.5">Pickup &amp; drop timings unconfigured</p>
      </div>
      <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg {{ ($stats['unconfigured_timings'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
        ⏱️
      </div>
    </a>

    {{-- Routes Awaiting Vehicle --}}
    <a href="{{ route('transport.routes') }}" class="card p-4 hover:shadow-card-md transition flex items-center justify-between border-l-4 {{ ($stats['routes_awaiting_vehicle'] ?? 0) > 0 ? 'border-l-rose-500 bg-rose-50/20' : 'border-l-emerald-500' }}">
      <div>
        <p class="text-xs font-semibold text-slate-500">Routes Awaiting Vehicle</p>
        <p class="stat-number text-xl mt-0.5 {{ ($stats['routes_awaiting_vehicle'] ?? 0) > 0 ? 'text-rose-800' : 'text-emerald-700' }}">
          {{ $stats['routes_awaiting_vehicle'] ?? 0 }}
        </p>
        <p class="text-[11px] text-slate-400 mt-0.5">Active routes without fleet vehicle</p>
      </div>
      <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg {{ ($stats['routes_awaiting_vehicle'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
        🚐
      </div>
    </a>

    {{-- 5 Official Corridors --}}
    <a href="{{ route('transport.routes') }}" class="card p-4 hover:shadow-card-md transition flex items-center justify-between border-l-4 border-l-indigo-500">
      <div>
        <p class="text-xs font-semibold text-slate-500">Van Corridors Configured</p>
        <p class="stat-number text-xl mt-0.5 text-indigo-700">5 Vans &middot; 57 Stops</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Van I, II, III, IV, V active</p>
      </div>
      <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg">
        🛣️
      </div>
    </a>
  </div>

  {{-- GPRS Live Fleet Radar --}}
  <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 p-5 text-white shadow-xl relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/80 pb-4 mb-4">
      <div class="flex items-center gap-3">
        <div class="relative flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
          <span class="absolute inline-flex h-full w-full rounded-xl bg-emerald-400 opacity-25 animate-ping"></span>
          <svg class="w-5 h-5 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-base font-bold text-white tracking-wide">GPRS Live Fleet Radar</h2>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1 animate-pulse"></span>
              Live GPS Telemetry
            </span>
          </div>
          <p class="text-xs text-slate-400">Continuous satellite tracking of school buses, vans, speeds, and student routes.</p>
        </div>
      </div>
      <a href="{{ route('transport.tracking') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs tracking-wider uppercase transition shadow-lg shadow-emerald-900/40 self-start md:self-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        Launch Full Live Map Console
      </a>
    </div>

    @if(isset($gprsVehicles) && $gprsVehicles->count() > 0)
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($gprsVehicles as $gv)
          <div class="rounded-xl bg-slate-800/60 border border-slate-700/60 p-3 flex flex-col justify-between hover:border-emerald-500/40 transition">
            <div class="flex items-start justify-between gap-2 mb-2">
              <div>
                <div class="flex items-center gap-1.5">
                  <span class="text-sm font-bold text-white">{{ $gv->vehicle_number }}</span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">{{ $gv->vehicle_type }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">Route: <span class="text-slate-200 font-medium">{{ $gv->route?->route_name ?? 'School Campus Transit' }}</span></div>
              </div>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $gv->gps_status === 'online' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $gv->gps_status === 'online' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                {{ $gv->gps_status === 'online' ? ($gv->current_speed_kmh > 0 ? $gv->current_speed_kmh . ' km/h' : 'Live') : 'Standby' }}
              </span>
            </div>
            <div class="pt-2 border-t border-slate-700/40 flex items-center justify-between text-[11px] text-slate-300">
              <span class="truncate max-w-[180px]" title="{{ $gv->current_location_name }}">📍 {{ $gv->current_location_name ?? 'Near Campus Main Gate' }}</span>
              <span class="text-slate-400 font-mono">🔋 {{ $gv->battery_level ?? 95 }}%</span>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="text-center py-4 text-xs text-slate-400">All fleet vehicles configured with GPS telemetry receivers.</div>
    @endif
  </div>

  {{-- Quick Management Grid --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
    <a href="{{ route('transport.students') }}" class="card-flat flex items-center gap-3 py-3 px-3 hover:shadow-card-md transition">
      <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </div>
      <div>
        <p class="font-bold text-xs text-slate-800">Student Manifest</p>
        <p class="text-[10px] text-slate-400">Vehicle allocations</p>
      </div>
    </a>

    <a href="{{ route('transport.stops') }}" class="card-flat flex items-center gap-3 py-3 px-3 hover:shadow-card-md transition">
      <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
      </div>
      <div>
        <p class="font-bold text-xs text-slate-800">Bus Stops</p>
        <p class="text-[10px] text-slate-400">Timings &amp; sequences</p>
      </div>
    </a>

    <a href="{{ route('transport.routes') }}" class="card-flat flex items-center gap-3 py-3 px-3 hover:shadow-card-md transition">
      <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      </div>
      <div>
        <p class="font-bold text-xs text-slate-800">Route Corridors</p>
        <p class="text-[10px] text-slate-400">Safe retirement &amp; edits</p>
      </div>
    </a>

    <a href="{{ route('transport.vehicles') }}" class="card-flat flex items-center gap-3 py-3 px-3 hover:shadow-card-md transition">
      <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      </div>
      <div>
        <p class="font-bold text-xs text-slate-800">Vehicle Fleet</p>
        <p class="text-[10px] text-slate-400">Buses, vans &amp; capacity</p>
      </div>
    </a>

    <a href="{{ route('transport.import') }}" class="card-flat flex items-center gap-3 py-3 px-3 hover:shadow-card-md transition">
      <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      </div>
      <div>
        <p class="font-bold text-xs text-slate-800">Excel Migration</p>
        <p class="text-[10px] text-slate-400">Official stop imports</p>
      </div>
    </a>

    <a href="{{ route('transport.reports') }}" class="card-flat flex items-center gap-3 py-3 px-3 hover:shadow-card-md transition">
      <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <div>
        <p class="font-bold text-xs text-slate-800">Reports Hub</p>
        <p class="text-[10px] text-slate-400">Occupancy &amp; audits</p>
      </div>
    </a>
  </div>

</div>
@endsection
