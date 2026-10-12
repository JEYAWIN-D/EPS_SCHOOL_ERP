@extends('layouts.app')
@section('title', 'Transport Routes')
@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Transport Routes</h1>
      <p class="text-xs text-slate-500 mt-1">Configure bus and van transit routes, assigned vehicles, stops, and annual/monthly fees.</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ request()->fullUrlWithQuery(['show_inactive' => $showInactive ? 0 : 1]) }}"
         class="btn btn-secondary btn-sm {{ $showInactive ? 'bg-slate-200' : '' }}">
        {{ $showInactive ? 'Hide Inactive' : 'Show Inactive' }}
      </a>
      <a href="{{ route('transport.stops') }}" class="btn btn-secondary btn-sm">All Stops</a>
      <a href="{{ route('transport.routes.create') }}" class="btn btn-primary btn-sm flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Route
      </a>
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

  <div class="table-wrap">
    <table class="w-full text-xs">
      <thead><tr>
        <th class="th">Route Name &amp; Code</th>
        <th class="th">Assigned Van / Bus</th>
        <th class="th">From &rarr; To</th>
        <th class="th">Stops &amp; Distance</th>
        <th class="th">Departure</th>
        <th class="th">Base Fee</th>
        <th class="th">Status</th>
        <th class="th text-right">Actions</th>
      </tr></thead>
      <tbody>
        @forelse($routes as $route)
          <tr class="tr {{ !$route->is_active ? 'opacity-60 bg-slate-50' : 'hover:bg-slate-50/70' }}">
            <td class="td">
              <span class="font-bold text-slate-900 block text-sm">{{ $route->route_name }}</span>
              @if($route->route_number)
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-bold">#{{ $route->route_number }}</span>
              @endif
            </td>
            <td class="td">
              @if($route->vehicle)
                <a href="{{ route('transport.vehicle.show', $route->vehicle->id) }}" class="font-mono font-bold text-indigo-600 hover:underline block">
                  {{ $route->vehicle->vehicle_number }}
                </a>
                <span class="text-[11px] text-slate-500">{{ $route->vehicle->make }} {{ $route->vehicle->model }}</span>
              @else
                <span class="text-slate-400 italic">No Vehicle</span>
              @endif
            </td>
            <td class="td">
              <span class="font-semibold text-slate-700 block">{{ $route->from_location }}</span>
              <span class="text-slate-400 text-[11px]">&darr; {{ $route->to_location }}</span>
            </td>
            <td class="td">
              <span class="font-bold text-slate-800">{{ $route->stops->count() }} Stops</span>
              <span class="text-slate-500 text-[11px] block">{{ $route->distance_km }} km</span>
            </td>
            <td class="td font-mono text-slate-700">
              {{ $route->departure_time ? \Carbon\Carbon::parse($route->departure_time)->format('h:i A') : '07:25 AM' }}
            </td>
            <td class="td font-extrabold text-[#8C2826] tabular-nums">
              ₹{{ number_format($route->fee, 0) }}
            </td>
            <td class="td">
              <span class="{{ $route->is_active ? 'badge-green' : 'badge-slate' }} text-[10px]">
                {{ $route->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="td text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1.5">
                <a href="{{ route('transport.stops', ['route_id' => $route->id]) }}" class="btn-xs text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200">
                  Stops ({{ $route->stops->count() }})
                </a>
                <a href="{{ route('transport.routes.edit', $route->id) }}" class="btn-xs text-slate-700 hover:text-indigo-600">
                  Edit
                </a>
                <form method="POST" action="{{ route('transport.routes.toggle', $route->id) }}" class="inline">
                  @csrf @method('PATCH')
                  <button type="submit"
                    class="btn-xs {{ $route->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}"
                    onclick="return confirm('{{ $route->is_active ? 'Deactivate' : 'Activate' }} route?')">
                    {{ $route->is_active ? 'Disable' : 'Enable' }}
                  </button>
                </form>
                <form method="POST" action="{{ route('transport.routes.destroy', $route->id) }}" class="inline" onsubmit="return confirm('Delete route {{ $route->route_name }}?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-xs text-rose-600 hover:text-rose-800">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="td text-center py-10 text-slate-400">No routes configured.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($routes->hasPages())
    <div class="text-sm mt-3">{{ $routes->links() }}</div>
  @endif
</div>
@endsection
