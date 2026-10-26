@php
  $currentRoute = request()->route()?->getName() ?? '';
@endphp

<div class="space-y-4 mb-6">
  {{-- Header with Title & Quick Actions --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mb-1">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Home</a>
        <span>/</span>
        <a href="{{ route('transport.index') }}" class="hover:text-slate-600">Administration</a>
        <span>/</span>
        <span class="text-indigo-600 font-bold">Transport Management</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
        <span>{{ $title ?? 'Transport Management' }}</span>
        @if(isset($badge))
          <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
            {{ $badge }}
          </span>
        @endif
      </h1>
      <p class="text-xs text-slate-500 mt-0.5">
        {{ $subtitle ?? 'Fleet management, bus stops, transit corridors, pickup & drop schedules, and student passenger manifests' }}
      </p>
    </div>

    {{-- Universal Quick Action Buttons --}}
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('transport.import') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-2xs">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        <span>Excel Import</span>
      </a>

      <a href="{{ route('transport.stops') }}#add-stop" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-2xs">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
        <span>Add Bus Stop</span>
      </a>

      <a href="{{ route('transport.routes.create') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 shadow-2xs">
        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <span>Add Route</span>
      </a>

      <a href="{{ route('transport.vehicles.create') }}" class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-xs">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Vehicle</span>
      </a>
    </div>
  </div>

  {{-- Navigation Tabs Bar --}}
  <div class="border-b border-slate-200/80 bg-white rounded-2xl p-1.5 shadow-xs border flex items-center gap-1 overflow-x-auto text-xs font-bold scrollbar-none">
    {{-- 1. Dashboard --}}
    <a href="{{ route('transport.index') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ $currentRoute === 'transport.index' ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
      <span>Dashboard</span>
    </a>

    {{-- 2. Vehicles --}}
    <a href="{{ route('transport.vehicles') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ str_starts_with($currentRoute, 'transport.vehicle') ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/></svg>
      <span>Vehicles</span>
    </a>

    {{-- 3. Routes --}}
    <a href="{{ route('transport.routes') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ str_starts_with($currentRoute, 'transport.routes') ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      <span>Routes</span>
    </a>

    {{-- 4. Bus Stops --}}
    <a href="{{ route('transport.stops') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ str_starts_with($currentRoute, 'transport.stops') ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
      <span>Bus Stops</span>
    </a>

    {{-- 5. Student Allocation / Vehicle Manifest --}}
    <a href="{{ route('transport.students') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ in_array($currentRoute, ['transport.students', 'transport.allotment', 'transport.roster']) ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      <span>Student Allocation</span>
    </a>

    {{-- 6. Excel Import & Data Migration --}}
    <a href="{{ route('transport.import') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ str_starts_with($currentRoute, 'transport.import') ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
      <span>Excel Import &amp; Migration</span>
    </a>

    {{-- 7. Reports --}}
    <a href="{{ route('transport.reports') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ str_starts_with($currentRoute, 'transport.reports') || str_starts_with($currentRoute, 'transport.vehicle-utilisation') ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span>Reports</span>
    </a>

    {{-- 8. Bus Attendance --}}
    <a href="{{ route('transport.bus-attendance') }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 shrink-0 {{ str_starts_with($currentRoute, 'transport.bus-attendance') ? 'bg-indigo-600 text-white shadow-xs font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      <span>Bus Attendance</span>
    </a>
  </div>
</div>
