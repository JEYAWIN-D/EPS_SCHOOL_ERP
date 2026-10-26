@extends('layouts.app')
@section('title', 'Bus Stop Management')
@section('content')
<div class="space-y-6" x-data="{
  addModalOpen: false,
  deleteModalOpen: false,
  deleteStopId: null,
  deleteStopName: '',
  deleteStudentCount: 0,
  openDeleteModal(id, name, count) {
    this.deleteStopId = id;
    this.deleteStopName = name;
    this.deleteStudentCount = count;
    this.deleteModalOpen = true;
  }
}">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="page-title">Bus Stop Management</h1>
      <p class="page-subtitle">Configure pickup &amp; drop stops, sequences, and scheduled arrival timings</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('transport.import') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        Import Excel Stops
      </a>
      <button type="button" @click="addModalOpen = true" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Bus Stop
      </button>
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

  {{-- Quick Stats Row --}}
  <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
    <div class="card p-3.5">
      <p class="text-xs text-slate-500 font-semibold">Total Stops</p>
      <p class="stat-number text-xl mt-1">{{ $stats['total_stops'] ?? 0 }}</p>
    </div>
    <div class="card p-3.5">
      <p class="text-xs text-slate-500 font-semibold">Active Stops</p>
      <p class="stat-number text-xl mt-1 text-emerald-700">{{ $stats['active_stops'] ?? 0 }}</p>
    </div>
    <div class="card p-3.5">
      <p class="text-xs text-slate-500 font-semibold">Boarding Students</p>
      <p class="stat-number text-xl mt-1 text-indigo-700">{{ $stats['total_assigned'] ?? 0 }}</p>
    </div>
    <a href="{{ route('transport.stops', ['timing' => 'missing']) }}" class="card p-3.5 hover:shadow-card-md transition {{ request('timing') === 'missing' ? 'ring-2 ring-amber-500 bg-amber-50/50' : '' }}">
      <p class="text-xs text-amber-700 font-semibold">Pending Timings</p>
      <p class="stat-number text-xl mt-1 text-amber-800">{{ $stats['unconfigured_timings'] ?? 0 }}</p>
    </a>
    <div class="card p-3.5">
      <p class="text-xs text-slate-500 font-semibold">Inactive Stops</p>
      <p class="stat-number text-xl mt-1 text-slate-400">{{ $stats['inactive_stops'] ?? 0 }}</p>
    </div>
  </div>

  {{-- Filter Bar --}}
  <div class="card-flat p-4">
    <form method="GET" action="{{ route('transport.stops') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
      {{-- Search --}}
      <div class="relative lg:col-span-2">
        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search stop name, landmark, van..."
               class="input w-full pl-9">
      </div>

      {{-- Filter by Route --}}
      <div>
        <select name="route_id" class="select w-full">
          <option value="">All Routes</option>
          @foreach($routes as $r)
            <option value="{{ $r->id }}" {{ request('route_id') == $r->id ? 'selected' : '' }}>{{ $r->route_name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Filter by Timing --}}
      <div>
        <select name="timing" class="select w-full">
          <option value="">All Timings</option>
          <option value="missing" {{ request('timing') === 'missing' ? 'selected' : '' }}>Pending Timings (Not Set)</option>
          <option value="configured" {{ request('timing') === 'configured' ? 'selected' : '' }}>Timings Configured</option>
        </select>
      </div>

      {{-- Filter by Vehicle --}}
      <div>
        <select name="vehicle_id" class="select w-full">
          <option value="">All Vehicles</option>
          @foreach($vehicles as $v)
            <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }}</option>
          @endforeach
        </select>
      </div>

      {{-- Status & Actions --}}
      <div class="flex items-center gap-2">
        <select name="status" class="select flex-1">
          <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
          <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Status</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm px-3.5">Filter</button>
        @if(request()->hasAny(['search', 'route_id', 'vehicle_id', 'status', 'timing']))
          <a href="{{ route('transport.stops') }}" class="btn btn-ghost btn-sm text-slate-500">Reset</a>
        @endif
      </div>
    </form>
  </div>

  {{-- Bus Stops Table --}}
  <div class="table-wrap">
    <table class="w-full">
      <thead>
        <tr>
          <th class="th">Seq</th>
          <th class="th">Stop Name &amp; Landmark</th>
          <th class="th">Assigned Route</th>
          <th class="th">Vehicle / Van</th>
          <th class="th">Scheduled Pickup</th>
          <th class="th">Scheduled Drop</th>
          <th class="th">Dist &amp; Fare</th>
          <th class="th">Boarding</th>
          <th class="th">Status</th>
          <th class="th text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($stops as $stop)
          <tr class="tr {{ !$stop->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
            <td class="td font-mono text-xs font-bold text-slate-500">#{{ $stop->stop_order }}</td>

            {{-- Stop Name & Landmark --}}
            <td class="td">
              <span class="font-bold text-slate-900 text-sm block leading-tight">{{ $stop->name }}</span>
              @if($stop->landmark)
                <span class="text-xs text-slate-400 font-medium inline-flex items-center gap-1 mt-0.5">
                  📍 {{ $stop->landmark }}
                </span>
              @endif
            </td>

            {{-- Assigned Route --}}
            <td class="td">
              <span class="text-xs font-semibold text-slate-800">{{ $stop->route?->route_name ?? '—' }}</span>
              @if($stop->route?->route_number)
                <span class="text-[10px] text-slate-400 font-mono block">#{{ $stop->route->route_number }}</span>
              @endif
            </td>

            {{-- Vehicle --}}
            <td class="td whitespace-nowrap">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                🚐 {{ $stop->effective_van_number }}
              </span>
            </td>

            {{-- Pickup Time --}}
            <td class="td font-mono text-xs whitespace-nowrap">
              @if($stop->pickup_time)
                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 font-semibold">
                  {{ \Carbon\Carbon::parse($stop->pickup_time)->format('h:i A') }}
                </span>
              @else
                <span class="badge-slate font-medium text-[11px] text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">
                  Not Set
                </span>
              @endif
            </td>

            {{-- Drop Time --}}
            <td class="td font-mono text-xs whitespace-nowrap">
              @if($stop->drop_time)
                <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 font-semibold">
                  {{ \Carbon\Carbon::parse($stop->drop_time)->format('h:i A') }}
                </span>
              @else
                <span class="badge-slate font-medium text-[11px] text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">
                  Not Set
                </span>
              @endif
            </td>

            {{-- Distance & Fare --}}
            <td class="td whitespace-nowrap">
              <span class="text-xs font-bold text-slate-800 font-mono block">
                {{ $stop->fare ? '₹' . number_format($stop->fare, 0) : '—' }}
              </span>
              <span class="text-[11px] text-slate-400 font-mono">
                {{ $stop->distance_km ? number_format((float)$stop->distance_km, 1) . ' km' : '—' }}
              </span>
            </td>

            {{-- Boarding Students --}}
            <td class="td whitespace-nowrap">
              <a href="{{ route('transport.students', ['stop_id' => $stop->id]) }}"
                 class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold {{ $stop->active_students_count > 0 ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'bg-slate-100 text-slate-400' }}">
                <span>{{ $stop->active_students_count ?? 0 }}</span>
                <span class="text-[10px] font-normal">students</span>
              </a>
            </td>

            {{-- Status --}}
            <td class="td whitespace-nowrap">
              <span class="{{ $stop->is_active ? 'badge-green' : 'badge-slate' }}">
                {{ $stop->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>

            {{-- Actions --}}
            <td class="td text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                {{-- Edit --}}
                <a href="{{ route('transport.stops.edit', $stop->id) }}" class="btn-icon text-slate-600 hover:bg-slate-100" title="Edit Stop">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </a>

                {{-- Toggle Status --}}
                <form method="POST" action="{{ route('transport.stops.toggle', $stop->id) }}" class="inline m-0 p-0">
                  @csrf @method('PATCH')
                  <button type="submit"
                          class="btn-icon {{ $stop->is_active ? 'text-amber-500 hover:bg-amber-50' : 'text-green-600 hover:bg-green-50' }}"
                          title="{{ $stop->is_active ? 'Deactivate Stop' : 'Activate Stop' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </button>
                </form>

                {{-- Safe Delete --}}
                <button type="button"
                        @click="openDeleteModal({{ $stop->id }}, '{{ addslashes($stop->name) }}', {{ $stop->active_students_count ?? 0 }})"
                        class="btn-icon text-red-500 hover:bg-red-50"
                        title="Delete / Deactivate Stop">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="td text-center py-12 text-slate-400">
              <p class="font-bold text-slate-700 mb-1">No bus stops found matching criteria</p>
              <p class="text-xs">Add a new stop or adjust search filters.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($stops->hasPages())
    <div class="mt-4">{{ $stops->links() }}</div>
  @endif

  {{-- Add Bus Stop Modal --}}
  <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="addModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="addModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="addModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form method="POST" action="{{ route('transport.stops.store') }}">
          @csrf
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 class="text-base font-bold text-slate-900">Add New Bus Stop</h3>
              <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="sm:col-span-2">
                <label class="label">Route <span class="text-red-500">*</span></label>
                <select name="route_id" class="select" required>
                  <option value="">Select Route</option>
                  @foreach($routes as $route)
                    <option value="{{ $route->id }}" {{ (old('route_id') ?? request('route_id')) == $route->id ? 'selected' : '' }}>{{ $route->route_name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="label">Stop Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="input" placeholder="e.g. Surampatti Four Roads" value="{{ old('name') }}" required>
              </div>

              <div>
                <label class="label">Assigned Vehicle</label>
                <select name="vehicle_id" class="select">
                  <option value="">Auto-assign from Route</option>
                  @foreach($vehicles as $v)
                    <option value="{{ $v->id }}">{{ $v->vehicle_number }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="label">Stop Sequence # <span class="text-red-500">*</span></label>
                <input type="number" name="stop_order" class="input" value="{{ old('stop_order', 1) }}" min="1" required>
              </div>

              <div>
                <label class="label">Pickup Time <span class="text-xs text-slate-400">(Optional)</span></label>
                <input type="time" name="pickup_time" class="input" value="{{ old('pickup_time') }}" placeholder="Optional">
                <p class="text-[10px] text-slate-400 mt-1">Leave blank for "Not Set"</p>
              </div>

              <div>
                <label class="label">Drop Time <span class="text-xs text-slate-400">(Optional)</span></label>
                <input type="time" name="drop_time" class="input" value="{{ old('drop_time') }}" placeholder="Optional">
                <p class="text-[10px] text-slate-400 mt-1">Leave blank for "Not Set"</p>
              </div>

              <div>
                <label class="label">Distance (km) <span class="text-red-500">*</span></label>
                <input type="number" name="distance_km" class="input" step="0.1" min="0" placeholder="e.g. 8.5" value="{{ old('distance_km') }}" required>
              </div>

              <div>
                <label class="label">Annual Transport Fee (₹) <span class="text-red-500">*</span></label>
                <input type="number" name="fare" class="input" step="1" min="0" placeholder="e.g. 15000" value="{{ old('fare') }}" required>
              </div>

              <div class="sm:col-span-2">
                <label class="label">Landmark / Location Note</label>
                <input type="text" name="landmark" class="input" placeholder="e.g. Near HP Petrol Bunk" value="{{ old('landmark') }}">
              </div>

              <div>
                <label class="label">Pickup Instructions</label>
                <input type="text" name="pickup_instructions" class="input" placeholder="e.g. Stand near entrance" value="{{ old('pickup_instructions') }}">
              </div>

              <div>
                <label class="label">Drop Instructions</label>
                <input type="text" name="drop_instructions" class="input" placeholder="e.g. Hand over to parent" value="{{ old('drop_instructions') }}">
              </div>

              <div class="sm:col-span-2">
                <label class="label">Remarks</label>
                <input type="text" name="remarks" class="input" placeholder="Internal transport remarks" value="{{ old('remarks') }}">
              </div>
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="addModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Bus Stop</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Safe Delete / Deactivate Stop Modal --}}
  <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="deleteModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="deleteModalOpen = false"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div x-show="deleteModalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
        <form :action="'{{ url('transport/stops') }}/' + deleteStopId" method="POST">
          @csrf @method('DELETE')
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900">Remove or Deactivate Stop</h3>
                <p class="text-xs text-slate-500">Stop: <span class="font-bold text-slate-800" x-text="deleteStopName"></span></p>
              </div>
            </div>

            <template x-if="deleteStudentCount > 0">
              <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-2">
                <p class="font-bold">⚠️ <span x-text="deleteStudentCount"></span> Active Students Board Here</p>
                <p>To prevent loss of student transit profiles, you can reassign them to another stop or safely deactivate this stop.</p>
                <div>
                  <label class="label text-amber-950">Reassign Students To (Optional)</label>
                  <select name="reassign_to_stop_id" class="select text-xs">
                    <option value="">Do not reassign (Deactivate stop safely)</option>
                    @foreach($stops as $targetStop)
                      <option value="{{ $targetStop->id }}">{{ $targetStop->name }} ({{ $targetStop->route?->route_name }})</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </template>

            <template x-if="deleteStudentCount === 0">
              <p class="text-xs text-slate-600">No students are currently allotted to this stop. It is safe to permanently remove.</p>
            </template>
          </div>

          <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <button type="button" @click="deleteModalOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm bg-red-600 hover:bg-red-700 text-white">Confirm Removal / Deactivation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection
