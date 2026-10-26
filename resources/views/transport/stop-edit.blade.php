@extends('layouts.app')
@section('title', 'Edit Stop')
@section('content')
<div class="max-w-lg mx-auto space-y-6">
  <div class="flex items-center gap-4">
    <a href="{{ route('transport.stops') }}" class="btn-icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></a>
    <h1 class="page-title">Edit Stop: {{ $stop->name }}</h1>
  </div>

  <div class="card">
    <form method="POST" action="{{ route('transport.stops.update', $stop->id) }}" class="space-y-4">
      @csrf @method('PUT')
      <div>
        <label class="label">Route</label>
        <select name="route_id" class="select">
          @foreach($routes as $route)
          <option value="{{ $route->id }}" {{ $stop->route_id == $route->id ? 'selected' : '' }}>{{ $route->route_name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="label">Van / Vehicle Assigned</label>
        <select name="vehicle_id" class="select">
          <option value="">Auto-assign from Route ({{ $stop->route?->vehicle?->vehicle_number ?: 'Default' }})</option>
          @foreach($vehicles as $v)
          <option value="{{ $v->id }}" {{ (old('vehicle_id', $stop->vehicle_id) == $v->id) ? 'selected' : '' }}>
            {{ $v->vehicle_number }} ({{ ucfirst($v->vehicle_type ?? 'Van') }} • {{ $v->driver_name ?: 'Driver TBD' }})
          </option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="label">Stop Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $stop->name) }}" required>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Stop Order</label>
          <input type="number" name="stop_order" class="input" value="{{ old('stop_order', $stop->stop_order) }}" min="1" required>
        </div>
        <div>
          <label class="label">Distance from School (km)</label>
          <input type="number" name="distance_km" class="input" value="{{ old('distance_km', $stop->distance_km) }}" step="0.1" min="0">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div><label class="label">Pickup Time</label><input type="time" name="pickup_time" class="input" value="{{ $stop->pickup_time }}"></div>
        <div><label class="label">Drop Time</label><input type="time" name="drop_time" class="input" value="{{ $stop->drop_time }}"></div>
      </div>
      <div>
        <label class="label">Annual Transport Fare (₹)</label>
        <input type="number" name="fare" class="input" value="{{ old('fare', $stop->fare) }}" step="0.01" min="0">
      </div>
      <div>
        <label class="label">Landmark</label>
        <input type="text" name="landmark" class="input" value="{{ old('landmark', $stop->landmark) }}">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Pickup Instructions</label>
          <input type="text" name="pickup_instructions" class="input" value="{{ old('pickup_instructions', $stop->pickup_instructions) }}" placeholder="e.g. Stand near entrance">
        </div>
        <div>
          <label class="label">Drop Instructions</label>
          <input type="text" name="drop_instructions" class="input" value="{{ old('drop_instructions', $stop->drop_instructions) }}" placeholder="e.g. Hand over to parent">
        </div>
      </div>
      <div>
        <label class="label">Remarks</label>
        <input type="text" name="remarks" class="input" value="{{ old('remarks', $stop->remarks) }}" placeholder="Internal transport remarks">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Update Stop</button>
        <a href="{{ route('transport.stops') }}" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
