@extends('layouts.app')
@section('title', 'Edit Vehicle — ' . $vehicle->vehicle_number)
@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
        <a href="{{ route('transport.vehicles') }}" class="hover:text-indigo-600">Vehicles</a>
        <span>/</span>
        <a href="{{ route('transport.vehicle.show', $vehicle->id) }}" class="hover:text-indigo-600 font-mono">{{ $vehicle->vehicle_number }}</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Edit</span>
      </div>
      <h1 class="page-title text-2xl">Edit Vehicle: {{ $vehicle->vehicle_number }}</h1>
    </div>
    <a href="{{ route('transport.vehicle.show', $vehicle->id) }}" class="btn btn-secondary btn-sm">
      Back to Van Profile
    </a>
  </div>

  @if($errors->any())
    <div class="alert-warning">
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('transport.vehicle.update', $vehicle->id) }}" class="card p-6 space-y-6">
    @csrf @method('PUT')

    {{-- Section 1: Vehicle Specifications --}}
    <div class="space-y-4">
      <h3 class="font-extrabold text-sm text-slate-900 border-b border-slate-100 pb-2">
        1. Vehicle Details &amp; Registration
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Vehicle Number *</label>
          <input type="text" name="vehicle_number" required class="input font-mono uppercase" value="{{ old('vehicle_number', $vehicle->vehicle_number) }}" placeholder="e.g. TN-33-AX-1001">
        </div>
        <div>
          <label class="label">Registration Number</label>
          <input type="text" name="registration_number" class="input font-mono uppercase" value="{{ old('registration_number', $vehicle->registration_number ?? $vehicle->vehicle_number) }}" placeholder="e.g. TN33AX1001">
        </div>
        <div>
          <label class="label">Vehicle Type *</label>
          <select name="vehicle_type" required class="select">
            @foreach(['van'=>'School Van', 'bus'=>'School Bus', 'mini_bus'=>'Mini Bus', 'car'=>'Staff Car', 'auto'=>'Auto'] as $k => $label)
              <option value="{{ $k }}" @selected(old('vehicle_type', $vehicle->vehicle_type ?? 'van') === $k)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Make / Manufacturer</label>
          <input type="text" name="make" class="input" value="{{ old('make', $vehicle->make) }}" placeholder="e.g. Force Motors, Tata, Ashok Leyland">
        </div>
        <div>
          <label class="label">Model / Variant</label>
          <input type="text" name="model" class="input" value="{{ old('model', $vehicle->model) }}" placeholder="e.g. Cruiser (24 Seater), Starbus">
        </div>
        <div>
          <label class="label">Seating Capacity *</label>
          <input type="number" name="seating_capacity" required min="1" max="100" class="input" value="{{ old('seating_capacity', $vehicle->effective_capacity) }}">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Assigned Route</label>
          <select name="route_id" class="select">
            <option value="">-- No Route Assigned --</option>
            @foreach($routes as $r)
              <option value="{{ $r->id }}" @selected(old('route_id', $vehicle->route_id) == $r->id)>
                {{ $r->route_name }} ({{ $r->distance_km }} km)
              </option>
            @endforeach
          </select>
        </div>
        <div class="flex items-center gap-3 pt-6">
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vehicle->is_active)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
            <span class="text-xs font-semibold text-slate-800">Vehicle is Active &amp; Operational</span>
          </label>
        </div>
      </div>
    </div>

    {{-- Section 2: Driver Details --}}
    <div class="space-y-4 pt-4 border-t border-slate-200">
      <h3 class="font-extrabold text-sm text-slate-900 border-b border-slate-100 pb-2">
        2. Driver Information
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Driver Name</label>
          <input type="text" name="driver_name" class="input" value="{{ old('driver_name', $vehicle->driver_name) }}" placeholder="e.g. P. Shanmugam">
        </div>
        <div>
          <label class="label">Driver Contact Number</label>
          <input type="text" name="driver_mobile" class="input" value="{{ old('driver_mobile', $vehicle->driver_mobile) }}" placeholder="e.g. 9842711001">
        </div>
        <div>
          <label class="label">Driving Licence Number</label>
          <input type="text" name="driver_license" class="input font-mono" value="{{ old('driver_license', $vehicle->driver_license) }}" placeholder="e.g. TN-33-2015-0012341">
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Licence Expiry Date</label>
          <input type="date" name="driver_license_expiry" class="input" value="{{ old('driver_license_expiry', $vehicle->driver_license_expiry ? $vehicle->driver_license_expiry->format('Y-m-d') : '') }}">
        </div>
        <div>
          <label class="label">Driver Residential Address</label>
          <input type="text" name="driver_address" class="input" value="{{ old('driver_address', $vehicle->driver_address) }}" placeholder="e.g. Solar, Erode - 638002">
        </div>
      </div>
    </div>

    {{-- Section 3: Nanny / Attendant Details --}}
    <div class="space-y-4 pt-4 border-t border-slate-200">
      <h3 class="font-extrabold text-sm text-slate-900 border-b border-slate-100 pb-2">
        3. Nanny / Attendant Information
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Attendant Name</label>
          <input type="text" name="attendant_name" class="input" value="{{ old('attendant_name', $vehicle->attendant_name) }}" placeholder="e.g. S. Lakshmi">
        </div>
        <div>
          <label class="label">Attendant Contact Number</label>
          <input type="text" name="attendant_mobile" class="input" value="{{ old('attendant_mobile', $vehicle->attendant_mobile) }}" placeholder="e.g. 9842712001">
        </div>
        <div>
          <label class="label">Aadhaar / ID Card Number</label>
          <input type="text" name="attendant_aadhaar" class="input font-mono" value="{{ old('attendant_aadhaar', $vehicle->attendant_aadhaar) }}" placeholder="e.g. 784512349001">
        </div>
      </div>
      <div>
        <label class="label">Attendant Residential Address</label>
        <input type="text" name="attendant_address" class="input" value="{{ old('attendant_address', $vehicle->attendant_address) }}" placeholder="e.g. Surampatti, Erode - 638009">
      </div>
    </div>

    {{-- Section 4: Document Expiry Dates --}}
    <div class="space-y-4 pt-4 border-t border-slate-200">
      <h3 class="font-extrabold text-sm text-slate-900 border-b border-slate-100 pb-2">
        4. Statutory Document Expiry Dates
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Fitness Certificate Expiry</label>
          <input type="date" name="fitness_expiry" class="input" value="{{ old('fitness_expiry', $vehicle->fitness_expiry ? $vehicle->fitness_expiry->format('Y-m-d') : '') }}">
        </div>
        <div>
          <label class="label">Insurance Policy Expiry</label>
          <input type="date" name="insurance_expiry" class="input" value="{{ old('insurance_expiry', $vehicle->insurance_expiry ? $vehicle->insurance_expiry->format('Y-m-d') : '') }}">
        </div>
        <div>
          <label class="label">Transport Permit Expiry</label>
          <input type="date" name="permit_expiry" class="input" value="{{ old('permit_expiry', $vehicle->permit_expiry ? $vehicle->permit_expiry->format('Y-m-d') : '') }}">
        </div>
        <div>
          <label class="label">PUC / Emission Expiry</label>
          <input type="date" name="puc_expiry" class="input" value="{{ old('puc_expiry', $vehicle->puc_expiry ? $vehicle->puc_expiry->format('Y-m-d') : '') }}">
        </div>
        <div>
          <label class="label">Road Tax Expiry</label>
          <input type="date" name="tax_expiry" class="input" value="{{ old('tax_expiry', $vehicle->tax_expiry ? $vehicle->tax_expiry->format('Y-m-d') : '') }}">
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a href="{{ route('transport.vehicle.show', $vehicle->id) }}" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
