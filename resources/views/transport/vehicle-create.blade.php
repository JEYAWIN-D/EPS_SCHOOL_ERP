@extends('layouts.app')
@section('title', 'Add New Vehicle / Van')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="{{ route('transport.vehicles') }}" class="btn-icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <h1 class="page-title text-2xl">Add New Vehicle / Van</h1>
    </div>
    <a href="{{ route('transport.vehicles') }}" class="btn btn-secondary btn-sm">Cancel</a>
  </div>

  @if($errors->any())
    <div class="alert-danger">
      <ul class="list-disc list-inside text-sm">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('transport.vehicles.store') }}" class="card p-6 space-y-6">
    @csrf

    {{-- Section 1: Vehicle Specifications --}}
    <div class="space-y-4">
      <h3 class="font-extrabold text-sm text-slate-900 border-b border-slate-100 pb-2">
        1. Vehicle Details &amp; Registration
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Vehicle Number <span class="text-red-500">*</span></label>
          <input type="text" name="vehicle_number" required class="input font-mono uppercase" value="{{ old('vehicle_number') }}" placeholder="e.g. TN-33-AX-1006">
        </div>
        <div>
          <label class="label">Registration Number</label>
          <input type="text" name="registration_number" class="input font-mono uppercase" value="{{ old('registration_number') }}" placeholder="e.g. TN33AX1006">
        </div>
        <div>
          <label class="label">Vehicle Type <span class="text-red-500">*</span></label>
          <select name="vehicle_type" required class="select">
            @foreach(['van'=>'School Van', 'bus'=>'School Bus', 'mini_bus'=>'Mini Bus', 'car'=>'Staff Car', 'auto'=>'Auto'] as $k => $label)
              <option value="{{ $k }}" @selected(old('vehicle_type', 'van') === $k)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Make / Brand</label>
          <input type="text" name="make" class="input" value="{{ old('make', 'Force Motors') }}" placeholder="e.g. Force Motors, Tata">
        </div>
        <div>
          <label class="label">Model</label>
          <input type="text" name="model" class="input" value="{{ old('model', 'Cruiser (24 Seater)') }}" placeholder="e.g. Cruiser (24 Seater)">
        </div>
        <div>
          <label class="label">Seating Capacity <span class="text-red-500">*</span></label>
          <input type="number" name="seating_capacity" required min="1" max="100" class="input" value="{{ old('seating_capacity', 24) }}">
        </div>
      </div>

      <div>
        <label class="label">Assign Route</label>
        <select name="route_id" class="select">
          <option value="">-- No Route (Assign Later) --</option>
          @foreach($routes as $route)
            <option value="{{ $route->id }}" @selected(old('route_id') == $route->id)>
              {{ $route->route_name }} ({{ $route->distance_km }} km)
            </option>
          @endforeach
        </select>
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
          <input type="text" name="driver_name" class="input" value="{{ old('driver_name') }}" placeholder="e.g. P. Shanmugam">
        </div>
        <div>
          <label class="label">Driver Contact Number</label>
          <input type="text" name="driver_mobile" class="input" value="{{ old('driver_mobile') }}" placeholder="e.g. 9842711001">
        </div>
        <div>
          <label class="label">Driving Licence Number</label>
          <input type="text" name="driver_license" class="input font-mono" value="{{ old('driver_license') }}" placeholder="e.g. TN-33-2015-0012341">
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Licence Expiry Date</label>
          <input type="date" name="driver_license_expiry" class="input" value="{{ old('driver_license_expiry') }}">
        </div>
        <div>
          <label class="label">Driver Residential Address</label>
          <input type="text" name="driver_address" class="input" value="{{ old('driver_address') }}" placeholder="e.g. Solar, Erode - 638002">
        </div>
      </div>
    </div>

    {{-- Section 3: Attendant / Nanny Details --}}
    <div class="space-y-4 pt-4 border-t border-slate-200">
      <h3 class="font-extrabold text-sm text-slate-900 border-b border-slate-100 pb-2">
        3. Nanny / Attendant Information
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Attendant Name</label>
          <input type="text" name="attendant_name" class="input" value="{{ old('attendant_name') }}" placeholder="e.g. S. Lakshmi">
        </div>
        <div>
          <label class="label">Attendant Contact Number</label>
          <input type="text" name="attendant_mobile" class="input" value="{{ old('attendant_mobile') }}" placeholder="e.g. 9842712001">
        </div>
        <div>
          <label class="label">Aadhaar / ID Card Number</label>
          <input type="text" name="attendant_aadhaar" class="input font-mono" value="{{ old('attendant_aadhaar') }}" placeholder="e.g. 784512349001">
        </div>
      </div>
      <div>
        <label class="label">Attendant Residential Address</label>
        <input type="text" name="attendant_address" class="input" value="{{ old('attendant_address') }}" placeholder="e.g. Surampatti, Erode - 638009">
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
          <input type="date" name="fitness_expiry" class="input" value="{{ old('fitness_expiry') }}">
        </div>
        <div>
          <label class="label">Insurance Policy Expiry</label>
          <input type="date" name="insurance_expiry" class="input" value="{{ old('insurance_expiry') }}">
        </div>
        <div>
          <label class="label">Transport Permit Expiry</label>
          <input type="date" name="permit_expiry" class="input" value="{{ old('permit_expiry') }}">
        </div>
        <div>
          <label class="label">PUC / Emission Expiry</label>
          <input type="date" name="puc_expiry" class="input" value="{{ old('puc_expiry') }}">
        </div>
        <div>
          <label class="label">Road Tax Expiry</label>
          <input type="date" name="tax_expiry" class="input" value="{{ old('tax_expiry') }}">
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
      <button type="submit" class="btn btn-primary">Create Vehicle</button>
      <a href="{{ route('transport.vehicles') }}" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
