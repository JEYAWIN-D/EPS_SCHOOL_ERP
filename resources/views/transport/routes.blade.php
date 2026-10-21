@extends('layouts.app')
@section('title', 'Transport Routes')
@section('content')
<div class="space-y-5">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="page-title">Transport Routes</h1>
      <p class="page-subtitle">
        Transit operations
        <span class="text-slate-400 mx-1.5">&middot;</span>
        <span class="font-semibold text-slate-700">{{ $routes->total() }}</span> {{ $routes->total() === 1 ? 'route' : 'routes' }}
      </p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ request()->fullUrlWithQuery(['show_inactive' => $showInactive ? 0 : 1]) }}"
         class="btn btn-secondary btn-sm {{ $showInactive ? 'bg-slate-200' : '' }}">
        {{ $showInactive ? 'Hide Inactive' : 'Show Inactive' }}
      </a>
      <a href="{{ route('transport.stops') }}" class="btn btn-secondary btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
        All Stops
      </a>
      <a href="{{ route('transport.routes.create') }}" class="btn btn-primary btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Route
      </a>
    </div>
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="alert-success">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif
  @if($errors->any())
    <div class="alert-error">
      <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Filter Bar --}}
  <form method="GET" action="{{ route('transport.routes') }}">
    <div class="filter-bar">
      {{-- Search --}}
      <div class="relative flex-1 min-w-48">
        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search route name, vehicle, or stop…"
               class="input w-full"
               style="padding-left: 2.25rem; padding-right: {{ request('search') ? '2rem' : '0.75rem' }}">
        @if(request('search'))
          <a href="{{ route('transport.routes') }}"
             class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
        @endif
      </div>

      @if($showInactive)
        <input type="hidden" name="show_inactive" value="1">
      @endif

      <button type="submit" class="btn btn-primary btn-sm">
        Filter
      </button>

      @if(request('search'))
        <a href="{{ route('transport.routes', request()->except('search')) }}" class="btn btn-ghost btn-sm text-slate-500">
          Clear
        </a>
      @endif
    </div>
  </form>

  {{-- Routes Table --}}
  <div class="table-wrap">
    <table class="w-full">
      <thead>
        <tr>
          <th class="th">Route</th>
          <th class="th">Vehicle</th>
          <th class="th">Corridor (From &rarr; To)</th>
          <th class="th">Stops &amp; Dist</th>
          <th class="th">Departure</th>
          <th class="th">Annual Fee</th>
          <th class="th">Status</th>
          <th class="th text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($routes as $route)
          <tr class="tr {{ !$route->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">

            {{-- 1. Route --}}
            <td class="td">
              <p class="font-semibold text-slate-800 text-sm leading-tight">{{ $route->route_name }}</p>
              <p class="text-xs text-slate-400 mt-0.5 font-mono">
                @if($route->route_number)
                  <span class="font-semibold text-slate-600">#{{ $route->route_number }}</span>
                @endif
                @if($route->allotments_count > 0)
                  <span class="text-slate-300 mx-1">&bull;</span>
                  <span>{{ $route->allotments_count }} enrolled</span>
                @endif
              </p>
            </td>

            {{-- 2. Vehicle --}}
            <td class="td">
              @if($route->vehicle)
                <a href="{{ route('transport.vehicle.show', $route->vehicle->id) }}" class="font-mono text-xs font-semibold text-indigo-600 hover:underline block">
                  {{ $route->vehicle->vehicle_number }}
                </a>
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ $route->vehicle->make }} {{ $route->vehicle->model }}
                  @if($route->vehicle->effective_capacity)
                    &bull; {{ $route->vehicle->effective_capacity }} seats
                  @endif
                </p>
              @else
                <span class="text-slate-300 text-xs italic">Unassigned</span>
              @endif
            </td>

            {{-- 3. Corridor (From -> To) --}}
            <td class="td">
              <div class="flex items-center gap-1.5 text-xs">
                <span class="font-medium text-slate-700">{{ $route->from_location }}</span>
                <span class="text-slate-400">&rarr;</span>
                <span class="text-slate-600">{{ $route->to_location }}</span>
              </div>
            </td>

            {{-- 4. Stops & Dist --}}
            <td class="td whitespace-nowrap">
              <p class="text-xs font-semibold text-slate-700">{{ $route->stops->count() }} stops</p>
              <p class="text-xs text-slate-400 mt-0.5">{{ number_format((float)$route->distance_km, 1) }} km</p>
            </td>

            {{-- 5. Departure --}}
            <td class="td font-mono text-xs text-slate-600 whitespace-nowrap">
              {{ $route->departure_time ? \Carbon\Carbon::parse($route->departure_time)->format('h:i A') : '07:25 AM' }}
            </td>

            {{-- 6. Annual Fee --}}
            <td class="td font-semibold text-slate-800 text-xs tabular-nums whitespace-nowrap">
              ₹{{ number_format($route->fee, 0) }}
            </td>

            {{-- 7. Status --}}
            <td class="td whitespace-nowrap">
              <span class="{{ $route->is_active ? 'badge-green' : 'badge-slate' }}">
                {{ $route->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>

            {{-- 8. Actions (Compact Icon Action Buttons with Tooltips) --}}
            <td class="td text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                {{-- Stops badge with count --}}
                <a href="{{ route('transport.stops', ['route_id' => $route->id]) }}"
                   class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition"
                   title="Manage Stops ({{ $route->stops->count() }})">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                  <span>{{ $route->stops->count() }}</span>
                </a>

                {{-- Edit Icon Button --}}
                <a href="{{ route('transport.routes.edit', $route->id) }}"
                   class="btn-icon"
                   title="Edit Route">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>

                {{-- Toggle Status Button --}}
                <form method="POST" action="{{ route('transport.routes.toggle', $route->id) }}" class="inline m-0 p-0">
                  @csrf @method('PATCH')
                  <button type="submit"
                          class="btn-icon {{ $route->is_active ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50' }}"
                          title="{{ $route->is_active ? 'Disable Route' : 'Enable Route' }}"
                          onclick="return confirm('{{ $route->is_active ? 'Deactivate' : 'Activate' }} route {{ $route->route_name }}?')">
                    @if($route->is_active)
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                  </button>
                </form>

                {{-- Delete Button --}}
                <form method="POST" action="{{ route('transport.routes.destroy', $route->id) }}" class="inline m-0 p-0" onsubmit="return confirm('Delete route {{ $route->route_name }}?');">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50"
                          title="Delete Route">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </form>
              </div>
            </td>

          </tr>
        @empty
          <tr>
            <td colspan="8" class="td">
              <div class="py-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                </div>
                <p class="text-sm font-semibold text-slate-700 mb-1">No transport routes found</p>
                <p class="text-xs text-slate-400">Click &ldquo;Add Route&rdquo; to configure a new transit corridor.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($routes->hasPages())
    <div class="text-sm mt-3">
      {{ $routes->links() }}
    </div>
  @endif

</div>
@endsection
