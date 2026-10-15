@extends('layouts.app')
@section('title', 'Warden Management')
@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Warden Management</h1>
      <p class="page-subtitle">Assign and manage hostel wardens</p>
    </div>
    <a href="{{ route('hostel.index') }}" class="btn btn-secondary">Back</a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Assign Warden Form --}}
    <div class="card">
      <h2 class="font-semibold text-slate-800 mb-4">Assign Warden</h2>
      <form method="POST" action="{{ route('hostel.wardens.assign') }}" class="space-y-4">
        @csrf
        @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
        <div>
          <label class="label">Hostel <span class="text-red-500">*</span></label>
          <select name="hostel_id" class="select" required>
            <option value="">Select Hostel</option>
            @foreach($hostels as $hostel)
            <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Warden Name <span class="text-red-500">*</span></label>
          <input type="hidden" name="warden_id" id="warden_id_input">
          <select name="warden_name" class="select" required onchange="document.getElementById('warden_id_input').value=this.options[this.selectedIndex].dataset.id||''; document.getElementById('warden_mobile_input').value=this.options[this.selectedIndex].dataset.mobile||'';">
            <option value="">Select Employee</option>
            @foreach($employees as $emp)
            <option value="{{ $emp->first_name }} {{ $emp->last_name }}" data-id="{{ $emp->id }}" data-mobile="{{ $emp->phone ?? '' }}">
              {{ $emp->first_name }} {{ $emp->last_name }} {{ $emp->designation ? '('.$emp->designation.')' : '' }}
            </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Warden Mobile</label>
          <input type="text" name="warden_mobile" id="warden_mobile_input" class="input" placeholder="Phone number">
        </div>
        <button type="submit" class="btn btn-primary">Assign Warden</button>
      </form>
    </div>

    {{-- Current Warden List --}}
    <div class="space-y-3">
      <h2 class="font-semibold text-slate-800">Current Wardens</h2>
      @foreach($hostels as $hostel)
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-slate-800">{{ $hostel->name }}</h3>
            <p class="text-xs text-slate-400 capitalize">{{ $hostel->type }} hostel</p>
          </div>
          <div class="text-right">
            @if($hostel->warden_name)
            @if($hostel->warden_id)
            <a href="{{ route('hr.employees.show', $hostel->warden_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $hostel->warden_name }}</a>
            @else
            <p class="font-medium text-slate-700">{{ $hostel->warden_name }}</p>
            @endif
            <p class="text-xs text-slate-400">{{ $hostel->warden_mobile ?? 'No phone' }}</p>
            <form method="POST" action="{{ route('hostel.wardens.remove', $hostel->id) }}" class="mt-2">
              @csrf @method('DELETE')
              <button type="submit" class="text-xs text-red-500 hover:underline" onclick="return confirm('Remove warden?')">Remove</button>
            </form>
            @else
            <span class="text-slate-400 text-sm italic">No warden assigned</span>
            @endif
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
