@extends('layouts.app')
@section('title', 'Transport Routes')
@section('content')
<div class="space-y-5" x-data="{
  retireModalOpen: false,
  retireRouteId: null,
  retireRouteName: '',
  retireStudentCount: 0,
  openRetireModal(id, name, count) {
    this.retireRouteId = id;
    this.retireRouteName = name;
    this.retireStudentCount = count;
    this.retireModalOpen = true;
  }
}">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="page-title">Transport Routes &amp; Corridors</h1>
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
      <a href="{{ route('transport.stops') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
        Manage Bus Stops
      </a>
      <a href="{{ route('transport.routes.create') }}" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Route
      </a>
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
          <th class="th">Route Details</th>
          <th class="th">Assigned Vehicle</th>
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
              <a href="{{ route('transport.routes.show', $route->id) }}" class="font-bold text-slate-800 text-sm leading-tight hover:text-indigo-600 transition block">
                {{ $route->route_name }}
              </a>
              <div class="flex items-center gap-2 mt-1 text-xs">
                @if($route->route_number)
                  <span class="font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">#{{ $route->route_number }}</span>
                @endif
                <a href="{{ route('transport.students', ['route_id' => $route->id]) }}"
                   class="inline-flex items-center gap-1 font-semibold {{ $route->allotments_count > 0 ? 'text-indigo-600 hover:underline' : 'text-slate-400' }}">
                  <span>{{ $route->allotments_count }} enrolled</span>
                </a>
              </div>
            </td>

            {{-- 2. Vehicle --}}
            <td class="td">
              @if($route->vehicle)
                <a href="{{ route('transport.vehicle.show', $route->vehicle->id) }}" class="font-mono text-xs font-bold text-indigo-700 hover:underline block">
                  {{ $route->vehicle->vehicle_number }}
                </a>
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ $route->vehicle->make }} {{ $route->vehicle->model }}
                  @if($route->vehicle->effective_capacity)
                    &bull; {{ $route->vehicle->effective_capacity }} seats
                  @endif
                </p>
              @else
                <span class="text-slate-400 text-xs italic">Unassigned</span>
              @endif
            </td>

            {{-- 3. Corridor (From -> To) --}}
            <td class="td">
              <div class="flex items-center gap-1.5 text-xs">
                <span class="font-semibold text-slate-700">{{ $route->from_location ?: 'School Campus' }}</span>
                <span class="text-slate-400">&rarr;</span>
                <span class="font-semibold text-slate-600">{{ $route->to_location ?: 'Terminal' }}</span>
              </div>
            </td>

            {{-- 4. Stops & Dist --}}
            <td class="td whitespace-nowrap">
              <a href="{{ route('transport.routes.show', $route->id) }}"
                 class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                <span>{{ $route->stops->count() }} stops</span>
              </a>
              <p class="text-xs text-slate-400 mt-1 font-mono font-medium">{{ number_format((float)$route->distance_km, 1) }} km</p>
            </td>

            {{-- 5. Departure --}}
            <td class="td font-mono text-xs text-slate-600 whitespace-nowrap">
              {{ $route->departure_time ? \Carbon\Carbon::parse($route->departure_time)->format('h:i A') : '07:25 AM' }}
            </td>

            {{-- 6. Annual Fee --}}
            <td class="td font-bold text-slate-800 text-xs tabular-nums whitespace-nowrap">
              ₹{{ number_format($route->fee, 0) }}
            </td>

            {{-- 7. Status --}}
            <td class="td whitespace-nowrap">
              <span class="{{ $route->is_active ? 'badge-green' : 'badge-slate' }}">
                {{ $route->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>

            {{-- 8. Actions --}}
            <td class="td text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1.5">
                {{-- View Details shortcut --}}
                <a href="{{ route('transport.routes.show', $route->id) }}"
                   class="btn-icon text-indigo-600 hover:bg-indigo-50"
                   title="View Sequential Stops &amp; Route Details ({{ $route->stops->count() }} stops)">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </a>

                {{-- Stops shortcut --}}
                <a href="{{ route('transport.stops', ['route_id' => $route->id]) }}"
                   class="btn-icon text-indigo-600 hover:bg-indigo-50"
                   title="Manage Stops in Grid ({{ $route->stops->count() }})">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </a>

                {{-- Edit Icon Button --}}
                <a href="{{ route('transport.routes.edit', $route->id) }}"
                   class="btn-icon text-slate-600 hover:bg-slate-100"
                   title="Edit Route">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>

                {{-- Toggle Status Button --}}
                <form method="POST" action="{{ route('transport.routes.toggle', $route->id) }}" class="inline m-0 p-0">
                  @csrf @method('PATCH')
                  <button type="submit"
                          class="btn-icon {{ $route->is_active ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50' }}"
                          title="{{ $route->is_active ? 'Deactivate Route' : 'Activate Route' }}"
                          onclick="return confirm('{{ $route->is_active ? 'Deactivate' : 'Activate' }} route {{ $route->route_name }}?')">
                    @if($route->is_active)
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                  </button>
                </form>

                {{-- Retire / Safe Delete Action --}}
                @if($route->allotments_count > 0)
                  <button type="button"
                          @click="openRetireModal({{ $route->id }}, '{{ addslashes($route->route_name) }}', {{ $route->allotments_count }})"
                          class="btn-icon text-amber-600 hover:bg-amber-50"
                          title="Safely Retire Route ({{ $route->allotments_count }} active students)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                  </button>
                @else
                  <form method="POST" action="{{ route('transport.routes.destroy', $route->id) }}" class="inline m-0 p-0" onsubmit="return confirm('Delete unused route {{ $route->route_name }}?');">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50"
                            title="Delete Route">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </form>
                @endif
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

  {{-- Retire / Safe Deactivate Route Modal --}}
  <div x-show="retireModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="retireModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="retireModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="retireModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form :action="'{{ url('transport/routes') }}/' + retireRouteId + '/retire'" method="POST">
          @csrf
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900">Retire or Deactivate Route</h3>
                <p class="text-xs text-slate-500">Safely manage routes with active student allocations</p>
              </div>
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900">
              <p class="font-bold">Route: <span x-text="retireRouteName"></span></p>
              <p class="mt-1">
                This route currently has <strong x-text="retireStudentCount"></strong> active students assigned to it.
                Deleting this route directly is blocked to prevent breaking student schedules and fee records.
              </p>
            </div>

            <div>
              <label class="label">Reassign Active Students To (Optional)</label>
              <select name="target_route_id" class="select">
                <option value="">Do not reassign (Safely deactivate route only)</option>
                @foreach($routes as $target)
                  <option value="{{ $target->id }}">{{ $target->route_name }} ({{ $target->vehicle?->vehicle_number ?? 'No vehicle' }})</option>
                @endforeach
              </select>
              <p class="text-[11px] text-slate-500 mt-1">If selected, all active students will be migrated with full audit logs to the new route.</p>
            </div>

            <div>
              <label class="label">Reason for Retirement</label>
              <input type="text" name="reason" class="input" placeholder="e.g. Replaced by official bus stop schedule" value="Replaced by new transport schedule">
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="retireModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm bg-amber-600 hover:bg-amber-700 text-white">Safely Retire Route</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection
