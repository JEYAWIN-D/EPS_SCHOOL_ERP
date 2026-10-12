@extends('layouts.app')
@section('title', 'Van & Fleet Management')
@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1 class="page-title">Van &amp; Fleet Management</h1>
      <p class="text-xs text-slate-500 mt-1">Manage school vans, seating capacities, vehicle documents, drivers, and attendants.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('transport.routes') }}" class="btn btn-secondary btn-sm">Manage Routes</a>
      <a href="{{ route('transport.documents') }}" class="btn btn-secondary btn-sm">All Documents</a>
      <a href="{{ route('transport.vehicles.create') }}" class="btn btn-primary btn-sm flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Vehicle
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

  @php $overdueCount = $vehicles->filter(fn($v) => $v->next_service_overdue)->count(); @endphp
  @if($overdueCount > 0)
    <div class="alert-warning">
      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <strong>{{ $overdueCount }} vehicle{{ $overdueCount > 1 ? 's' : '' }}</strong> have overdue scheduled service.
    </div>
  @endif

  {{-- Search Bar --}}
  <div class="card p-4">
    <form method="GET" action="{{ route('transport.vehicles') }}" class="flex items-center gap-3">
      <div class="relative flex-1">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by vehicle #, driver name, nanny/attendant, or model..." class="input pl-9 text-xs">
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Search</button>
      @if(request('search'))
        <a href="{{ route('transport.vehicles') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
      @endif
    </form>
  </div>

  <div class="table-wrap">
    <table class="w-full text-xs">
      <thead><tr>
        <th class="th">Van / Vehicle</th>
        <th class="th">Make &amp; Model</th>
        <th class="th">Assigned Route</th>
        <th class="th">Seating Capacity</th>
        <th class="th">Driver Details</th>
        <th class="th">Nanny / Attendant</th>
        <th class="th">Documents</th>
        <th class="th">Status</th>
        <th class="th text-right">Actions</th>
      </tr></thead>
      <tbody>
        @forelse($vehicles as $v)
          <tr class="tr hover:bg-slate-50/70">
            <td class="td">
              <a href="{{ route('transport.vehicle.show', $v->id) }}" class="font-mono font-bold text-indigo-600 hover:underline block text-sm">
                {{ $v->vehicle_number }}
              </a>
              <span class="text-[11px] text-slate-500 font-medium">{{ ucfirst($v->vehicle_type ?? 'Van') }}</span>
            </td>
            <td class="td">
              <span class="font-semibold text-slate-800 block">{{ $v->make }}</span>
              <span class="text-slate-500 text-[11px]">{{ $v->model }}</span>
            </td>
            <td class="td">
              @if($v->route)
                <span class="font-bold text-slate-800 block truncate max-w-[200px]" title="{{ $v->route->route_name }}">
                  {{ $v->route->route_name }}
                </span>
                <span class="text-[11px] text-slate-500">{{ $v->route->stops->count() }} stops &bull; {{ $v->route->distance_km }} km</span>
              @else
                <span class="text-slate-400 italic">Unassigned</span>
              @endif
            </td>
            <td class="td">
              <div class="space-y-0.5">
                <span class="font-bold text-slate-900 block">{{ $v->effective_capacity }} Seats</span>
                <div class="flex items-center gap-1.5">
                  <span class="text-[11px] text-slate-500">{{ $v->allotments->count() }} allocated</span>
                  @if($v->available_seats > 0)
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      {{ $v->available_seats }} vacant
                    </span>
                  @else
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                      Full
                    </span>
                  @endif
                </div>
              </div>
            </td>
            <td class="td">
              @if($v->driver_name)
                <span class="font-semibold text-slate-800 block">{{ $v->driver_name }}</span>
                @if($v->driver_mobile)
                  <a href="tel:{{ $v->driver_mobile }}" class="text-[11px] text-indigo-600 hover:underline">📞 {{ $v->driver_mobile }}</a>
                @endif
              @else
                <span class="text-slate-400 italic">No driver</span>
              @endif
            </td>
            <td class="td">
              @if($v->attendant_name)
                <span class="font-semibold text-slate-800 block">{{ $v->attendant_name }}</span>
                @if($v->attendant_mobile)
                  <a href="tel:{{ $v->attendant_mobile }}" class="text-[11px] text-indigo-600 hover:underline">📞 {{ $v->attendant_mobile }}</a>
                @endif
              @else
                <span class="text-slate-400 italic">No attendant</span>
              @endif
            </td>
            <td class="td">
              <a href="{{ route('transport.vehicle.show', $v->id) }}" class="inline-flex items-center gap-1 text-slate-700 hover:text-indigo-600 font-medium">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>{{ $v->documents->count() }} file{{ $v->documents->count() == 1 ? '' : 's' }}</span>
              </a>
            </td>
            <td class="td">
              <span class="{{ $v->is_active ? 'badge-green' : 'badge-red' }} text-[10px]">
                {{ $v->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="td text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1.5">
                <a href="{{ route('transport.vehicle.show', $v->id) }}" class="btn-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                  View Van
                </a>
                <a href="{{ route('transport.vehicle.edit', $v->id) }}" class="btn-xs text-slate-600 hover:text-indigo-600">
                  Edit
                </a>
                <form method="POST" action="{{ route('transport.vehicle.destroy', $v->id) }}" class="inline" onsubmit="return confirm('Delete vehicle {{ $v->vehicle_number }}?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-xs text-rose-600 hover:text-rose-800">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="td text-center py-10 text-slate-400">No vehicles matching your query.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($vehicles->hasPages())
    <div class="text-sm mt-3">{{ $vehicles->links() }}</div>
  @endif
</div>
@endsection
