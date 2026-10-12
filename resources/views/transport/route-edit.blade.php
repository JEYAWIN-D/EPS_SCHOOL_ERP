@extends('layouts.app')
@section('title', 'Edit Transport Route')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
  <div class="flex items-center gap-4">
    <a href="{{ route('transport.routes') }}" class="btn-icon">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    </a>
    <div>
      <h1 class="page-title">Edit Route: {{ $route->route_name }}</h1>
      <p class="text-xs text-slate-500">Update route details, assigned vehicle, distance, and fare.</p>
    </div>
  </div>

  <div class="card p-6">
    <form method="POST" action="{{ route('transport.routes.update', $route->id) }}" class="space-y-5">
      @csrf
      @method('PUT')

      @if($errors->any())
        <div class="alert-danger">
          <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Route Name <span class="text-red-500">*</span></label>
          <input type="text" name="route_name" class="input" value="{{ old('route_name', $route->route_name) }}" required>
        </div>
        <div>
          <label class="label">Route Number / Code</label>
          <input type="text" name="route_code" class="input font-mono uppercase" value="{{ old('route_code', $route->route_number) }}">
        </div>
        <div>
          <label class="label">Assign Vehicle</label>
          <select name="vehicle_id" class="select">
            <option value="">No Vehicle Assigned</option>
            @foreach($vehicles as $vehicle)
              <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $route->vehicle_id) == $vehicle->id)>
                {{ $vehicle->vehicle_number }} — {{ $vehicle->driver_name ?? 'No Driver' }}
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Distance (km)</label>
          <input type="number" name="distance_km" class="input" value="{{ old('distance_km', $route->distance_km) }}" step="0.1" min="0">
        </div>
        <div>
          <label class="label">Start Point</label>
          <input type="text" name="start_point" class="input" value="{{ old('start_point', $route->from_location) }}">
        </div>
        <div>
          <label class="label">End Point</label>
          <input type="text" name="end_point" class="input" value="{{ old('end_point', $route->to_location) }}">
        </div>
        <div>
          <label class="label">Morning Pickup Time</label>
          <input type="time" name="start_time" class="input" value="{{ old('start_time', $route->departure_time ? \Carbon\Carbon::parse($route->departure_time)->format('H:i') : '') }}">
        </div>
        <div>
          <label class="label">Afternoon Drop Time</label>
          <input type="time" name="end_time" class="input" value="{{ old('end_time', $route->arrival_time ? \Carbon\Carbon::parse($route->arrival_time)->format('H:i') : '') }}">
        </div>
        <div>
          <label class="label">Annual Transport Fee (₹)</label>
          <input type="number" name="fare" class="input" value="{{ old('fare', $route->fee) }}" step="0.01" min="0">
        </div>
      </div>

      <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
        <button type="submit" class="btn btn-primary">Update Route</button>
        <a href="{{ route('transport.routes') }}" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
