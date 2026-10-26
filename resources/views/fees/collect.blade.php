@extends('layouts.app')
@section('title', 'Record Payment — Fee Desk')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-20">

  {{-- Top Navigation Bar --}}
  <div class="flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <a href="{{ $student ? route('students.show', $student->id) : route('students.index') }}" 
         class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-xs hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-all"
         title="Back to Student Profile">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Student Profile / Billing Desk</h2>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Record Fee Payment</h1>
      </div>
    </div>

    @if($student)
    <div class="flex items-center gap-2">
      <a href="{{ route('fees.student-statement', $student->id) }}" 
         class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-indigo-600 font-bold text-xs shadow-xs transition flex items-center gap-1.5"
         title="View Student Fee Statement">
        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Fee Statement</span>
      </a>
    </div>
    @endif
  </div>

  {{-- Alert Messages --}}
  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-xs">
      <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="font-medium">{{ session('success') }}</span>
    </div>
  @endif

  @if(isset($errors) && $errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1 shadow-xs">
      <div class="flex items-center gap-2 font-bold text-rose-900">
        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Please review the following errors:</span>
      </div>
      <ul class="list-disc list-inside space-y-1 pl-6 text-xs font-medium">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if($student)
  {{-- Student Information & Fee Statement Banner --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-extrabold text-xl shadow-sm shrink-0">
          {{ strtoupper(substr($student->first_name,0,1) . substr($student->last_name,0,1)) }}
        </div>
        <div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h2 class="text-lg font-bold text-slate-900">{{ $student->full_name }}</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $student->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
              {{ ucfirst($student->status) }}
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $student->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
              {{ str_replace('_', ' ', ucfirst($student->payment_status ?? 'Pending')) }}
            </span>
          </div>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Admission No: <span class="font-mono font-bold text-indigo-600">{{ $student->admission_no ?? $student->admission_number }}</span>
            &bull; Class: <span class="font-bold text-slate-800">{{ $student->currentEnrollment?->class?->name ?? 'Unassigned' }}</span>
            @if($student->currentEnrollment?->section)
              &bull; Sec: <span class="font-bold text-slate-800">{{ $student->currentEnrollment->section->name }}</span>
            @endif
            @if($student->payment_terms)
              &bull; Plan: <span class="font-bold text-indigo-700">{{ str_replace('_', ' ', strtoupper($student->payment_terms)) }}</span>
            @endif
          </p>
        </div>
      </div>
      <div class="text-left sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Academic Year</p>
        <p class="text-sm font-extrabold text-slate-800">{{ $student->currentEnrollment?->academicYear?->name ?? $currentYear?->name ?? '2026-2027' }}</p>
      </div>
    </div>

    {{-- Live Fee Statement Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-3 border-t border-slate-100">
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Fees Payable</span>
        <p class="text-base sm:text-lg font-black font-mono text-slate-900 mt-0.5">₹{{ number_format($totalFeesPayable ?? 0, 2) }}</p>
      </div>
      <div class="bg-emerald-50/80 p-3 rounded-xl border border-emerald-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Total Amount Paid</span>
        <p class="text-base sm:text-lg font-black font-mono text-emerald-700 mt-0.5">₹{{ number_format($totalPaidSoFar ?? 0, 2) }}</p>
      </div>
      <div class="bg-blue-50/80 p-3 rounded-xl border border-blue-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Amount Adjusted</span>
        <p class="text-base sm:text-lg font-black font-mono text-blue-700 mt-0.5">₹{{ number_format($totalAdjustedSoFar ?? 0, 2) }}</p>
      </div>
      <div class="bg-purple-50/80 p-3 rounded-xl border border-purple-200/80">
        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Excess / Advance</span>
        <p class="text-base sm:text-lg font-black font-mono text-purple-700 mt-0.5">₹{{ number_format($excessCreditSoFar ?? 0, 2) }}</p>
      </div>
      <div class="bg-rose-50/80 p-3 rounded-xl border border-rose-200/80 col-span-2 sm:col-span-1">
        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Outstanding Balance</span>
        <p class="text-base sm:text-lg font-black font-mono text-rose-700 mt-0.5">₹{{ number_format($outstandingBalance ?? 0, 2) }}</p>
      </div>
    </div>
  </div>

  {{-- Transport Transit Profile & Quick Manage Card (Requirement 8) --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5" x-data="{
    manageTransportOpen: false,
    routesData: {{ json_encode(($activeTransportRoutes ?? collect())->map(fn($r) => [
      'id' => $r->id,
      'name' => $r->route_name,
      'vehicle_id' => $r->vehicle_id,
      'vehicle_number' => $r->vehicle?->vehicle_number,
      'fee' => $r->fee,
      'stops' => $r->stops->map(fn($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'pickup_time' => $s->pickup_time ? \Carbon\Carbon::parse($s->pickup_time)->format('h:i A') : '07:30 AM',
        'drop_time' => $s->drop_time ? \Carbon\Carbon::parse($s->drop_time)->format('h:i A') : '04:30 PM',
        'fare' => $s->fare,
      ]),
    ])) }},
    selectedRouteId: '{{ $studentTransportAllotment?->route_id ?? "" }}',
    selectedStopId: '{{ $studentTransportAllotment?->stop_id ?? "" }}',
    selectedDropStopId: '{{ $studentTransportAllotment?->drop_stop_id ?? ($studentTransportAllotment?->stop_id ?? "") }}',
    pickupOverride: '{{ $studentTransportAllotment?->pickup_time_override ?? ($studentTransportAllotment?->effective_pickup_time ?? "") }}',
    dropOverride: '{{ $studentTransportAllotment?->drop_time_override ?? ($studentTransportAllotment?->effective_drop_time ?? "") }}',
    transportFee: {{ $studentTransportAllotment?->fee ?? 0 }},
    transportAction: 'assign_or_update',
    get currentRoute() {
      return this.routesData.find(r => r.id == this.selectedRouteId) || null;
    },
    get availableStops() {
      return this.currentRoute ? this.currentRoute.stops : [];
    },
    onRouteChange() {
      this.selectedStopId = '';
      this.selectedDropStopId = '';
      this.pickupOverride = '';
      this.dropOverride = '';
      if (this.currentRoute) {
        this.transportFee = this.currentRoute.fee || 0;
      }
    },
    onStopChange() {
      let stop = this.availableStops.find(s => s.id == this.selectedStopId);
      if (stop) {
        this.pickupOverride = stop.pickup_time || '07:30 AM';
        this.dropOverride = stop.drop_time || '04:30 PM';
        if (stop.fare) this.transportFee = stop.fare;
        if (!this.selectedDropStopId) this.selectedDropStopId = stop.id;
      }
    }
  }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-lg shrink-0">
          🚐
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-sm font-bold text-slate-900">Transport &amp; Transit Profile</h3>
            @if($studentTransportAllotment && $studentTransportAllotment->is_active)
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                Active Transit
              </span>
              <span class="font-mono text-xs font-bold text-amber-900 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                {{ $studentTransportAllotment->vehicle?->vehicle_number ?? 'Van Assigned' }}
              </span>
            @else
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                Own Conveyance / Day Scholar
              </span>
            @endif
          </div>

          @if($studentTransportAllotment && $studentTransportAllotment->is_active)
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-slate-600">
              <span>Route: <strong class="text-slate-800">{{ $studentTransportAllotment->route?->route_name ?? '—' }}</strong></span>
              <span>Pickup: <strong class="text-emerald-700">{{ $studentTransportAllotment->effective_pickup_stop_name }}</strong> ({{ $studentTransportAllotment->effective_pickup_time ?: '07:30 AM' }})</span>
              <span>Drop: <strong class="text-blue-700">{{ $studentTransportAllotment->effective_drop_stop_name }}</strong> ({{ $studentTransportAllotment->effective_drop_time ?: '04:30 PM' }})</span>
              <span>Fee: <strong class="text-slate-900 font-mono">₹{{ number_format($studentTransportAllotment->fee ?? 0) }}/yr</strong></span>
            </div>
          @else
            <p class="text-xs text-slate-500 mt-1">
              Student does not currently use school transport. You can allocate a bus/van or configure boarding stops directly from this fee desk.
            </p>
          @endif
        </div>
      </div>

      <div class="shrink-0 self-start sm:self-center">
        <button type="button" @click="manageTransportOpen = true"
                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
          <span>{{ ($studentTransportAllotment && $studentTransportAllotment->is_active) ? 'Manage Transport' : 'Enroll in Transport' }}</span>
        </button>
      </div>
    </div>

    {{-- Interactive Manage Transport Modal --}}
    <div x-show="manageTransportOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="manageTransportOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="manageTransportOpen = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div x-show="manageTransportOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
          <form method="POST" action="{{ route('fees.student.manage-transport', $student->id) }}">
            @csrf
            <div class="bg-white p-6 space-y-4">
              <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                  <h3 class="text-base font-bold text-slate-900">Manage School Transport Facility</h3>
                  <p class="text-xs text-slate-500">Student: <strong>{{ $student->full_name }}</strong> (Admn: {{ $student->admission_no ?? $student->admission_number }})</p>
                </div>
                <button type="button" @click="manageTransportOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
              </div>

              {{-- Action Picker Tabs --}}
              <div class="flex gap-2 p-1 bg-slate-100 rounded-xl">
                <button type="button" @click="transportAction = 'assign_or_update'"
                        :class="transportAction === 'assign_or_update' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                        class="flex-1 py-1.5 px-3 rounded-lg text-xs transition text-center">
                  Assign / Update Transit
                </button>
                @if($studentTransportAllotment && $studentTransportAllotment->is_active)
                  <button type="button" @click="transportAction = 'discontinue'"
                          :class="transportAction === 'discontinue' ? 'bg-rose-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                          class="flex-1 py-1.5 px-3 rounded-lg text-xs transition text-center">
                    Discontinue Transport
                  </button>
                @endif
              </div>
              <input type="hidden" name="transport_action" :value="transportAction">

              {{-- Section A: Assign or Update Form --}}
              <div x-show="transportAction === 'assign_or_update'" class="space-y-3.5">
                <div>
                  <label class="label">School Route <span class="text-red-500">*</span></label>
                  <select name="route_id" class="select" x-model="selectedRouteId" @change="onRouteChange()" :required="transportAction === 'assign_or_update'">
                    <option value="">Select bus/van route...</option>
                    <template x-for="r in routesData" :key="r.id">
                      <option :value="r.id" x-text="r.name + ' (' + (r.vehicle_number ? r.vehicle_number : 'No Vehicle') + ')'"></option>
                    </template>
                  </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="selectedRouteId">
                  <div>
                    <label class="label">Pickup Stop <span class="text-red-500">*</span></label>
                    <select name="stop_id" class="select" x-model="selectedStopId" @change="onStopChange()" :required="transportAction === 'assign_or_update'">
                      <option value="">Choose pickup stop...</option>
                      <template x-for="s in availableStops" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                      </template>
                    </select>
                  </div>

                  <div>
                    <label class="label">Drop Stop</label>
                    <select name="drop_stop_id" class="select" x-model="selectedDropStopId">
                      <option value="">Same as pickup stop</option>
                      <template x-for="s in availableStops" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                      </template>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="selectedStopId">
                  <div>
                    <label class="label">Student Pickup Time</label>
                    <input type="text" name="pickup_time_override" class="input font-mono" x-model="pickupOverride" placeholder="e.g. 07:30 AM">
                    <p class="text-[10px] text-slate-400 mt-0.5">Custom student boarding timing</p>
                  </div>

                  <div>
                    <label class="label">Student Drop Time</label>
                    <input type="text" name="drop_time_override" class="input font-mono" x-model="dropOverride" placeholder="e.g. 04:30 PM">
                    <p class="text-[10px] text-slate-400 mt-0.5">Custom student return timing</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="selectedRouteId">
                  <div>
                    <label class="label">Configured Annual Fee (₹)</label>
                    <input type="number" name="fee" class="input font-mono" x-model="transportFee" step="1" min="0">
                  </div>

                  <div>
                    <label class="label">Effective Start Date</label>
                    <input type="date" name="start_date" class="input font-mono" value="{{ today()->toDateString() }}">
                  </div>
                </div>

                <div>
                  <label class="label">Desk Remarks / Reason</label>
                  <input type="text" name="remarks" class="input" placeholder="e.g. Stop adjusted per parent request during fee payment">
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-500">
                  <strong>Fee Notice:</strong> Saving transport updates adjusts the student's transit profile and fee schedule without creating a paid transaction. Completed fee receipts and historical payment records remain unchanged.
                </div>
              </div>

              {{-- Section B: Discontinue Form --}}
              <div x-show="transportAction === 'discontinue'" class="space-y-3.5">
                <div class="p-4 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-900 space-y-2">
                  <p class="font-bold">⚠️ Confirm Transport Discontinuation</p>
                  <p>
                    Discontinuing school transport will safely deactivate the student's route schedule and free up vehicle capacity.
                    Previous payment receipts and historical audit transactions are strictly preserved.
                  </p>
                </div>

                <div>
                  <label class="label">Reason for Discontinuing <span class="text-red-500">*</span></label>
                  <input type="text" name="discontinue_reason" class="input" placeholder="e.g. Relocated / Shifted to private vehicle" value="Parent request at fee desk">
                </div>

                <div>
                  <label class="label">Effective End Date</label>
                  <input type="date" name="discontinue_date" class="input font-mono" value="{{ today()->toDateString() }}">
                </div>
              </div>

            </div>

            <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
              <button type="button" @click="manageTransportOpen = false" class="btn btn-secondary btn-sm">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm"
                      :class="transportAction === 'discontinue' ? 'bg-rose-600 hover:bg-rose-700 text-white' : ''"
                      x-text="transportAction === 'discontinue' ? 'Confirm Discontinue' : 'Save Transport Profile'">
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @if($dues->count())
  @php
    $duesMap = [];
    foreach($dues as $d) {
      $duesMap[$d->item_key] = [
        'balance'     => (float)$d->balance,
        'name'        => $d->name,
        'item_type'   => $d->item_type,
        'term_number' => $d->term_number ?? null,
      ];
    }
  @endphp

  {{-- Alpine JS Helper for Waterfall Allocation and Preset Management --}}
  <script>
  function feePaymentDesk(duesMap, termsSchedule, totalOutstanding, admissionFeeAmount) {
    return {
      duesMap: duesMap || {},
      termsSchedule: termsSchedule || [],
      totalOutstanding: parseFloat(totalOutstanding) || 0,
      admissionFeeAmount: parseFloat(admissionFeeAmount) || 0,
      
      activePreset: 'term_wise', // 'admission_only', 'term_wise', 'full_annual', 'custom'
      selectedItemKey: '',
      amount: {{ $dues->first()?->balance ?? 0 }},
      discount: 0,
      paymentType: 'single',
      singleMode: 'cash',
      paymentAccount: 'cash_box_1',
      submitting: false,
      splits: [
        { payment_mode: 'cash', payment_account: 'cash_box_1', amount: {{ $dues->first()?->balance ?? 0 }}, transaction_id: '' },
        { payment_mode: 'upi', payment_account: 'upi', amount: 0, transaction_id: '' }
      ],

      init() {
        const keys = Object.keys(this.duesMap);
        if (keys.length > 0) {
          // If first item is full_annual, choose it or the first pending term
          const termKey = keys.find(k => k.startsWith('term_')) || keys[0];
          this.selectedItemKey = termKey;
          this.handleFeeItemChange();
        }
      },

      handleModeChange() {
        if (this.singleMode === 'cash') {
          this.paymentAccount = 'cash_box_1';
        } else if (this.singleMode === 'upi' || this.singleMode === 'online') {
          this.paymentAccount = 'upi';
        } else {
          this.paymentAccount = 'bank';
        }
      },

      handleFeeItemChange() {
        const item = this.duesMap[this.selectedItemKey];
        if (item) {
          this.amount = parseFloat(item.balance) || 0;
          this.discount = 0;
          if (this.selectedItemKey === 'admission_only') {
            this.activePreset = 'admission_only';
          } else if (this.selectedItemKey === 'full_annual') {
            this.activePreset = 'full_annual';
          } else if (this.selectedItemKey.startsWith('term_')) {
            this.activePreset = 'term_wise';
          }
          this.syncSplitAmounts();
        }
      },

      selectPreset(type, termKey = null) {
        this.activePreset = type;
        if (type === 'admission_only') {
          this.selectedItemKey = 'admission_only' in this.duesMap ? 'admission_only' : Object.keys(this.duesMap)[0];
          this.amount = this.admissionFeeAmount > 0 ? this.admissionFeeAmount : (this.duesMap[this.selectedItemKey]?.balance || 2500);
        } else if (type === 'full_annual') {
          this.selectedItemKey = 'full_annual' in this.duesMap ? 'full_annual' : Object.keys(this.duesMap)[0];
          this.amount = this.totalOutstanding > 0 ? this.totalOutstanding : (this.duesMap[this.selectedItemKey]?.balance || 0);
        } else if (type === 'term_wise') {
          if (termKey && termKey in this.duesMap) {
            this.selectedItemKey = termKey;
            this.amount = this.duesMap[termKey].balance;
          } else {
            // Find first pending term
            const firstTermKey = Object.keys(this.duesMap).find(k => k.startsWith('term_'));
            if (firstTermKey) {
              this.selectedItemKey = firstTermKey;
              this.amount = this.duesMap[firstTermKey].balance;
            }
          }
        }
        this.discount = 0;
        this.syncSplitAmounts();
      },

      netPayable() {
        const amt = parseFloat(this.amount) || 0;
        const disc = parseFloat(this.discount) || 0;
        return Math.max(0, amt - disc);
      },

      // Real-time Waterfall Calculation Simulation
      get waterfallCalculation() {
        const payable = this.netPayable();
        let rem = payable;
        let totalAdjusted = 0;

        let targetTermNum = null;
        if (this.selectedItemKey && this.selectedItemKey.startsWith('term_')) {
          targetTermNum = parseInt(this.selectedItemKey.replace('term_', ''));
        }

        // Clone terms schedule
        let terms = JSON.parse(JSON.stringify(this.termsSchedule));
        if (terms.length === 0) {
          // Fallback single term
          terms = [{
            term_number: 1,
            name: 'Admission Fee Dues',
            amount: this.totalOutstanding,
            paid: 0,
            pending: this.totalOutstanding,
            status: 'pending'
          }];
        }

        let orderedTerms = [];
        if (targetTermNum !== null) {
          let matchIdx = terms.findIndex(t => t.term_number === targetTermNum);
          if (matchIdx !== -1) {
            orderedTerms.push(terms[matchIdx]);
            for (let i = matchIdx + 1; i < terms.length; i++) orderedTerms.push(terms[i]);
            for (let i = 0; i < matchIdx; i++) orderedTerms.push(terms[i]);
          } else {
            orderedTerms = terms;
          }
        } else {
          orderedTerms = terms;
        }

        let calculatedTerms = [];

        for (let t of orderedTerms) {
          const tPending = parseFloat(t.pending) || 0;
          const tPaid = parseFloat(t.paid) || 0;
          const tAmount = parseFloat(t.amount) || 0;

          if (tPending <= 0) {
            calculatedTerms.push({
              term_number: t.term_number,
              name: t.name,
              amount: tAmount,
              already_paid: tPaid,
              allocated_now: 0,
              remaining_due: 0,
              status_after: 'paid',
              is_cleared: true
            });
            continue;
          }

          const alloc = Math.min(rem, tPending);
          const newPaid = tPaid + alloc;
          const newPending = Math.max(0, tPending - alloc);
          const statusAfter = newPending <= 0 ? 'paid' : (newPaid > 0 ? 'partially_paid' : 'pending');

          calculatedTerms.push({
            term_number: t.term_number,
            name: t.name,
            amount: tAmount,
            already_paid: tPaid,
            allocated_now: alloc,
            remaining_due: newPending,
            status_after: statusAfter,
            is_cleared: newPending <= 0
          });

          totalAdjusted += alloc;
          rem = Math.max(0, rem - alloc);
        }

        // Sort back by term_number for clean display
        calculatedTerms.sort((a, b) => a.term_number - b.term_number);

        const excessSurplus = rem;
        const newOverallBalance = Math.max(0, this.totalOutstanding - totalAdjusted);

        return {
          payable: payable,
          totalAdjusted: totalAdjusted,
          excessSurplus: excessSurplus,
          newOverallBalance: newOverallBalance,
          terms: calculatedTerms,
          hasExcessOverTerm: (payable > (calculatedTerms.find(t => t.term_number === targetTermNum)?.already_paid ?? 0)) && (calculatedTerms.length > 1),
          hasSurplusExcess: excessSurplus > 0
        };
      },

      totalSplitAmount() {
        return this.splits.reduce((acc, curr) => acc + (parseFloat(curr.amount) || 0), 0);
      },

      splitDifference() {
        return this.netPayable() - this.totalSplitAmount();
      },

      isSplitValid() {
        if (this.splits.length < 2) return false;
        for (let s of this.splits) {
          if (!s.amount || parseFloat(s.amount) <= 0) return false;
        }
        return Math.abs(this.splitDifference()) < 0.01;
      },

      syncSplitAmounts() {
        const payable = this.netPayable();
        if (payable > 0 && this.splits.length >= 2) {
          if (!this.splits[0].amount || this.splits[0].amount === 0) {
            this.splits[0].amount = payable;
            this.splits[1].amount = 0;
          }
        }
      },

      addSplitRow() {
        this.splits.push({
          payment_mode: 'upi',
          payment_account: 'upi',
          amount: 0,
          transaction_id: ''
        });
      },

      removeSplitRow(index) {
        if (this.splits.length > 2) {
          this.splits.splice(index, 1);
        }
      },

      formatCurrency(val) {
        return Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      },

      handleSubmit(e) {
        const amt = parseFloat(this.amount) || 0;
        if (amt <= 0) {
          e.preventDefault();
          alert('Please enter a valid payment amount greater than 0.');
          return false;
        }

        if (this.paymentType === 'split') {
          if (!this.isSplitValid()) {
            e.preventDefault();
            alert('Split payment breakdown amounts must equal the net payable amount.');
            return false;
          }
        }

        if (this.submitting) {
          e.preventDefault();
          return false;
        }

        this.submitting = true;
        return true;
      }
    };
  }
  </script>

  {{-- Fee Payment Card & Form --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8"
       x-data="feePaymentDesk({{ json_encode($duesMap) }}, {{ json_encode($termsSchedule ?? []) }}, {{ $outstandingBalance ?? 0 }}, {{ $admissionFeeAmount ?? 2500 }})"
       x-init="init()">
    <form id="fee-collect-form" 
          method="POST" 
          action="{{ route('fees.collect.save') }}" 
          class="space-y-6" 
          novalidate 
          @submit="handleSubmit($event)">
      @csrf
      <input type="hidden" name="student_id" value="{{ $student->id }}">
      <input type="hidden" name="idempotency_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

      {{-- ── SECTION 1: FLEXIBLE PAYMENT OPTION PRESETS ────────────── --}}
      <div class="space-y-3 pb-2 border-b border-slate-100">
        <div class="flex items-center justify-between">
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
            Payment Options <span class="text-rose-500">*</span>
          </label>
          <span class="text-[11px] font-semibold text-slate-400">Choose a preset or enter any amount</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          {{-- Option A: Admission Fee Only --}}
          <button type="button" 
                  @click="selectPreset('admission_only')"
                  class="p-3.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="activePreset === 'admission_only' ? 'bg-indigo-50 border-indigo-400 ring-2 ring-indigo-200 text-indigo-950 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'">
            <div class="flex items-center justify-between w-full">
              <span class="text-xs font-extrabold flex items-center gap-1.5">
                <span>🎓</span> Admission Only
              </span>
              <span class="w-2 h-2 rounded-full" :class="activePreset === 'admission_only' ? 'bg-indigo-600' : 'bg-slate-300'"></span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Initial Kit &amp; Reg. fee</p>
            <p class="text-sm font-black font-mono text-indigo-700 mt-1.5">₹<span x-text="formatCurrency(admissionFeeAmount)"></span></p>
          </button>

          {{-- Option B: Term-Wise Payment --}}
          <button type="button" 
                  @click="selectPreset('term_wise')"
                  class="p-3.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="activePreset === 'term_wise' ? 'bg-indigo-50 border-indigo-400 ring-2 ring-indigo-200 text-indigo-950 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'">
            <div class="flex items-center justify-between w-full">
              <span class="text-xs font-extrabold flex items-center gap-1.5">
                <span>📅</span> Term-Wise
              </span>
              <span class="w-2 h-2 rounded-full" :class="activePreset === 'term_wise' ? 'bg-indigo-600' : 'bg-slate-300'"></span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Pay specific term dues</p>
            <p class="text-sm font-black font-mono text-slate-800 mt-1.5">Term Dues</p>
          </button>

          {{-- Option C: Full Annual Fee --}}
          <button type="button" 
                  @click="selectPreset('full_annual')"
                  class="p-3.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="activePreset === 'full_annual' ? 'bg-emerald-50 border-emerald-400 ring-2 ring-emerald-200 text-emerald-950 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'">
            <div class="flex items-center justify-between w-full">
              <span class="text-xs font-extrabold flex items-center gap-1.5 text-emerald-800">
                <span>🏆</span> Full Annual Fee
              </span>
              <span class="w-2 h-2 rounded-full" :class="activePreset === 'full_annual' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">100% of all annual dues</p>
            <p class="text-sm font-black font-mono text-emerald-700 mt-1.5">₹<span x-text="formatCurrency(totalOutstanding)"></span></p>
          </button>

          {{-- Option D: Custom Amount --}}
          <button type="button" 
                  @click="activePreset = 'custom'"
                  class="p-3.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="activePreset === 'custom' ? 'bg-amber-50 border-amber-400 ring-2 ring-amber-200 text-amber-950 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'">
            <div class="flex items-center justify-between w-full">
              <span class="text-xs font-extrabold flex items-center gap-1.5 text-amber-800">
                <span>✏️</span> Custom Amount
              </span>
              <span class="w-2 h-2 rounded-full" :class="activePreset === 'custom' ? 'bg-amber-600' : 'bg-slate-300'"></span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Any partial or excess sum</p>
            <p class="text-sm font-black font-mono text-amber-700 mt-1.5">Type Below</p>
          </button>
        </div>

        {{-- Sub-term buttons when Term-Wise is active --}}
        @if(!empty($termsSchedule) && count($termsSchedule) > 1)
        <div class="flex items-center gap-2 pt-2 flex-wrap" x-show="activePreset === 'term_wise'">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick Term Select:</span>
          @foreach($termsSchedule as $t)
            @if(($t['pending'] ?? 0) > 0)
              <button type="button" 
                      @click="selectPreset('term_wise', 'term_{{ $t['term_number'] }}')"
                      class="px-3 py-1 rounded-lg text-xs font-bold border transition flex items-center gap-1.5"
                      :class="selectedItemKey === 'term_{{ $t['term_number'] }}' ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'">
                <span>{{ $t['name'] }}</span>
                <span class="font-mono text-[11px]">₹{{ number_format($t['pending'], 2) }}</span>
              </button>
            @endif
          @endforeach
        </div>
        @endif
      </div>

      {{-- Fee Head / Target Term Selection Dropdown --}}
      <div>
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
          Target Term / Fee Head <span class="text-rose-500">*</span>
        </label>
        <select name="fee_item_id" 
                class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm font-semibold text-slate-900 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                x-model="selectedItemKey"
                @change="handleFeeItemChange()">
          @foreach($dues as $d)
            <option value="{{ $d->item_key }}" {{ old('fee_item_id') === $d->item_key ? 'selected' : '' }}>
              {{ $d->display_label }}
            </option>
          @endforeach
        </select>
        <p class="text-[11px] text-slate-400 mt-1 font-medium">Select the initial term to satisfy. Any excess amount received will automatically adjust against upcoming terms.</p>
      </div>

      {{-- Amount, Discount, Payment Date --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
            Payment Amount <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">₹</span>
            <input type="number" 
                   name="amount" 
                   x-model.number="amount"
                   @input="syncSplitAmounts()"
                   value="{{ $dues->first()?->balance ?? '' }}"
                   class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold font-mono text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" 
                   min="1" 
                   step="0.01">
          </div>
          <p class="text-[11px] text-slate-400 mt-1 font-medium">Enter amount paid today</p>
        </div>

        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
            Discount / Concession
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">₹</span>
            <input type="number" 
                   name="discount" 
                   x-model.number="discount"
                   @input="syncSplitAmounts()"
                   class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold font-mono text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" 
                   min="0" 
                   step="0.01" 
                   value="0">
          </div>
          <p class="text-[11px] text-slate-400 mt-1 font-medium">Waiver applied to this payment</p>
        </div>

        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
            Payment Date <span class="text-rose-500">*</span>
          </label>
          <input type="date" 
                 name="payment_date" 
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" 
                 value="{{ old('payment_date', today()->toDateString()) }}"
                 max="{{ today()->toDateString() }}">
          <p class="text-[11px] text-slate-400 mt-1 font-medium">Date payment was received</p>
        </div>
      </div>

      {{-- ── SECTION 2: LIVE WATERFALL ALLOCATION & EXCESS ADJUSTMENT PREVIEW ── --}}
      <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-50 to-indigo-50/30 border border-indigo-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-indigo-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
              ₹
            </div>
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Automatic Term-Wise Waterfall &amp; Excess Adjustment</h3>
              <p class="text-[11px] text-slate-500">Live preview showing how payment is distributed and excess adjusted across upcoming terms.</p>
            </div>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[11px] font-black font-mono bg-indigo-100 text-indigo-800">
            Net Payable: ₹<span x-text="formatCurrency(netPayable())"></span>
          </span>
        </div>

        {{-- Waterfall Terms Allocation Table --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200/90 bg-white shadow-2xs">
          <table class="w-full text-xs text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                <th class="py-2.5 px-3">Term Schedule</th>
                <th class="py-2.5 px-3 text-right">Term Amount</th>
                <th class="py-2.5 px-3 text-right">Already Paid</th>
                <th class="py-2.5 px-3 text-right text-indigo-700">Allocated Today</th>
                <th class="py-2.5 px-3 text-right text-rose-700">Remaining Due</th>
                <th class="py-2.5 px-3 text-center">Status After Payment</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <template x-for="t in waterfallCalculation.terms" :key="t.term_number">
                <tr class="hover:bg-slate-50/60 transition" :class="t.allocated_now > 0 ? 'bg-indigo-50/20' : ''">
                  <td class="py-2.5 px-3 font-bold text-slate-900 flex items-center gap-2">
                    <span x-text="t.name"></span>
                    <template x-if="t.allocated_now > 0">
                      <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-emerald-100 text-emerald-800">Adjusted</span>
                    </template>
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">
                    ₹<span x-text="formatCurrency(t.amount)"></span>
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                    ₹<span x-text="formatCurrency(t.already_paid)"></span>
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-extrabold text-indigo-600">
                    +₹<span x-text="formatCurrency(t.allocated_now)"></span>
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-bold"
                      :class="t.remaining_due <= 0 ? 'text-emerald-600' : 'text-rose-600'">
                    ₹<span x-text="formatCurrency(t.remaining_due)"></span>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border"
                          :class="t.remaining_due <= 0 ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : (t.already_paid + t.allocated_now > 0 ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200')"
                          x-text="t.remaining_due <= 0 ? 'Paid' : (t.already_paid + t.allocated_now > 0 ? 'Partially Paid' : 'Pending')">
                    </span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        {{-- Excess Adjustment Alert Banner --}}
        <template x-if="waterfallCalculation.hasSurplusExcess">
          <div class="p-3.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 text-xs flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center text-sm font-bold shrink-0">🎁</span>
            <div>
              <p class="font-extrabold text-purple-950">
                Excess Amount Received: ₹<span x-text="formatCurrency(waterfallCalculation.excessSurplus)"></span>
              </p>
              <p class="text-[11px] text-purple-800 mt-0.5">
                This payment exceeds all scheduled fee dues. The surplus ₹<span x-text="formatCurrency(waterfallCalculation.excessSurplus)"></span> will be automatically credited to the student's <strong>Advance Balance</strong> wallet.
              </p>
            </div>
          </div>
        </template>

        {{-- Summary Metrics Bar --}}
        <div class="grid grid-cols-3 gap-3 p-3 bg-white rounded-xl border border-slate-200 text-center">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Adjusted</span>
            <p class="text-base font-black font-mono text-indigo-700 mt-0.5">₹<span x-text="formatCurrency(waterfallCalculation.totalAdjusted)"></span></p>
          </div>
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Excess Credited</span>
            <p class="text-base font-black font-mono text-purple-700 mt-0.5">₹<span x-text="formatCurrency(waterfallCalculation.excessSurplus)"></span></p>
          </div>
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Remaining Balance</span>
            <p class="text-base font-black font-mono text-rose-600 mt-0.5">₹<span x-text="formatCurrency(waterfallCalculation.newOverallBalance)"></span></p>
          </div>
        </div>
      </div>

      {{-- ── SECTION 3: PAYMENT MODE (SINGLE VS SPLIT) ────────────── --}}
      <div class="space-y-4 pt-2">
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
          Payment Method <span class="text-rose-500">*</span>
        </label>

        {{-- Mode Selector Pills --}}
        <div class="flex items-center gap-4">
          <label class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border cursor-pointer transition select-none text-sm font-bold"
                 :class="paymentType === 'single' ? 'bg-indigo-50 border-indigo-300 text-indigo-900 shadow-xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
            <input type="radio" name="payment_type" value="single" x-model="paymentType" class="text-indigo-600 focus:ring-indigo-500">
            <span>Single Payment</span>
          </label>

          <label class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border cursor-pointer transition select-none text-sm font-bold"
                 :class="paymentType === 'split' ? 'bg-indigo-50 border-indigo-300 text-indigo-900 shadow-xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
            <input type="radio" name="payment_type" value="split" x-model="paymentType" class="text-indigo-600 focus:ring-indigo-500">
            <span>Split Payment</span>
          </label>
        </div>

        {{-- SINGLE PAYMENT SECTION --}}
        <div x-show="paymentType === 'single'" class="space-y-4 p-5 rounded-2xl bg-slate-50 border border-slate-200/80">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Method <span class="text-rose-500">*</span></label>
              <select name="payment_mode" 
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-900 bg-white focus:ring-2 focus:ring-indigo-500 outline-none" 
                      x-model="singleMode"
                      @change="handleModeChange()"
                      :disabled="paymentType !== 'single'">
                <option value="cash">Cash</option>
                <option value="upi">UPI / QR Code</option>
                <option value="card">Card / POS</option>
                <option value="bank_transfer">Bank Transfer / NEFT / RTGS</option>
                <option value="cheque">Cheque</option>
                <option value="dd">Demand Draft (DD)</option>
                <option value="online">Online Payment</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Deposit Account <span class="text-rose-500">*</span></label>
              <select name="payment_account" 
                      x-model="paymentAccount"
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-900 bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                      :disabled="paymentType !== 'single'">
                <option value="upi">🟣 UPI Account (Virtual / QR)</option>
                <option value="cash_box_1">🟢 Cash Box 1 (Front Office)</option>
                <option value="cash_box_2">🔵 Cash Box 2 (Accounts Office)</option>
                <option value="bank">🏦 School Bank Account</option>
              </select>
            </div>

            {{-- Reference / UTR for online/UPI/Card --}}
            <div class="sm:col-span-2" x-show="singleMode === 'upi' || singleMode === 'online' || singleMode === 'card' || singleMode === 'bank_transfer'">
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Transaction ID / UTR / Reference</label>
              <input type="text" 
                     name="transaction_id" 
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono text-slate-900 bg-white focus:ring-2 focus:ring-indigo-500 outline-none" 
                     placeholder="e.g. UTR123456789"
                     :disabled="paymentType !== 'single'">
            </div>
          </div>

          {{-- Cheque / DD fields --}}
          <div x-show="singleMode === 'cheque' || singleMode === 'dd'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2 border-t border-slate-200">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Cheque / DD Number</label>
              <input type="text" 
                     name="cheque_number" 
                     class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-mono text-slate-900 bg-white" 
                     placeholder="Number"
                     :disabled="paymentType !== 'single'">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Cheque Date</label>
              <input type="date" 
                     name="cheque_date" 
                     class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 bg-white"
                     :disabled="paymentType !== 'single'">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Bank Name</label>
              <input type="text" 
                     name="cheque_bank" 
                     class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 bg-white" 
                     placeholder="Bank"
                     :disabled="paymentType !== 'single'">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Branch</label>
              <input type="text" 
                     name="cheque_branch" 
                     class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 bg-white" 
                     placeholder="Branch"
                     :disabled="paymentType !== 'single'">
            </div>
          </div>
        </div>

        {{-- SPLIT PAYMENT SECTION --}}
        <div x-show="paymentType === 'split'" class="space-y-4 p-5 rounded-2xl bg-slate-50 border border-slate-200/80">
          <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div>
              <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Payment Breakdown</h4>
              <p class="text-[11px] text-slate-500">Divide the payable amount across multiple payment methods.</p>
            </div>
            <button type="button" 
                    @click="addSplitRow()" 
                    class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-xs transition">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Add Payment Method
            </button>
          </div>

          {{-- Split Rows Table --}}
          <div class="space-y-3">
            <template x-for="(split, index) in splits" :key="index">
              <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="w-full sm:w-1/4">
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Method</label>
                  <select :name="'splits[' + index + '][payment_mode]'" 
                          x-model="split.payment_mode" 
                          @change="split.payment_account = (split.payment_mode === 'cash' ? 'cash_box_1' : (split.payment_mode === 'upi' ? 'upi' : 'bank'))"
                          :disabled="paymentType !== 'split'"
                          class="w-full px-2.5 py-2 rounded-lg border border-slate-300 text-xs font-bold text-slate-800 bg-white">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI</option>
                    <option value="card">Card / POS</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="dd">DD</option>
                    <option value="online">Online</option>
                  </select>
                </div>

                <div class="w-full sm:w-1/4">
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Account</label>
                  <select :name="'splits[' + index + '][payment_account]'" 
                          x-model="split.payment_account" 
                          :disabled="paymentType !== 'split'"
                          class="w-full px-2 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800 bg-white">
                    <option value="cash_box_1">Cash Box 1</option>
                    <option value="cash_box_2">Cash Box 2</option>
                    <option value="upi">UPI Account</option>
                    <option value="bank">Bank Account</option>
                  </select>
                </div>

                <div class="w-full sm:w-1/4">
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Amount (₹)</label>
                  <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₹</span>
                    <input type="number" 
                           :name="'splits[' + index + '][amount]'" 
                           x-model.number="split.amount" 
                           :disabled="paymentType !== 'split'"
                           min="0.01" 
                           step="0.01" 
                           class="w-full pl-6 pr-3 py-2 rounded-lg border border-slate-300 text-xs font-bold font-mono text-slate-900">
                  </div>
                </div>

                <div class="w-full sm:w-1/4">
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Ref / UTR / Cheque</label>
                  <input type="text" 
                         :name="'splits[' + index + '][transaction_id]'" 
                         x-model="split.transaction_id" 
                         :disabled="paymentType !== 'split'"
                         placeholder="Optional Ref" 
                         class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-800">
                </div>

                <div class="sm:pt-5 shrink-0 flex justify-end">
                  <button type="button" 
                          @click="removeSplitRow(index)" 
                          :disabled="splits.length <= 2"
                          class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition disabled:opacity-30 disabled:cursor-not-allowed"
                          title="Remove payment method">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
            </template>
          </div>

          {{-- Split Breakdown Totals --}}
          <div class="mt-4 p-4 rounded-xl bg-white border border-slate-200/90 shadow-xs space-y-2">
            <div class="grid grid-cols-3 gap-3 text-center border-b border-slate-100 pb-3">
              <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Paid</p>
                <p class="text-base font-black font-mono text-indigo-700 mt-0.5">₹<span x-text="formatCurrency(totalSplitAmount())"></span></p>
              </div>
              <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Amount Due</p>
                <p class="text-base font-black font-mono text-slate-800 mt-0.5">₹<span x-text="formatCurrency(netPayable())"></span></p>
              </div>
              <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Difference</p>
                <p class="text-base font-black font-mono mt-0.5" 
                   :class="isSplitValid() ? 'text-emerald-600' : 'text-rose-600'">
                  ₹<span x-text="formatCurrency(Math.abs(splitDifference()))"></span>
                </p>
              </div>
            </div>

            <template x-if="!isSplitValid()">
              <div class="flex items-center gap-2 text-rose-600 text-xs font-bold pt-1">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Split breakdown does not match the payable amount.</span>
              </div>
            </template>
            <template x-if="isSplitValid()">
              <div class="flex items-center gap-2 text-emerald-600 text-xs font-bold pt-1">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Split breakdown perfectly matches the payable amount.</span>
              </div>
            </template>
          </div>
        </div>
      </div>

      {{-- Remarks --}}
      <div>
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">Remarks</label>
        <textarea name="remarks" 
                  rows="2" 
                  class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-none" 
                  placeholder="Optional payment notes, excess authorization, or bank comments..."></textarea>
      </div>

      {{-- ── SECTION 4: THE PAYMENT RECEIVED SUBMIT BUTTON ─────────── --}}
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-5 border-t border-slate-200">
        <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>All fees, term schedules, and excess balances update automatically upon recording.</span>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
          <a href="{{ route('students.show', $student->id) }}" 
             class="px-5 py-3 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-xs">
            Cancel
          </a>

          {{-- The Required "Payment Received" Button --}}
          <button type="submit" 
                  :disabled="submitting || (paymentType === 'split' && !isSplitValid())"
                  class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-extrabold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2.5 cursor-pointer">
            <template x-if="!submitting">
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Payment Received</span>
                <span class="font-mono bg-white/20 px-2 py-0.5 rounded text-xs">₹<span x-text="formatCurrency(amount)"></span></span>
              </span>
            </template>
            <template x-if="submitting">
              <span class="flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Recording Payment Received...</span>
              </span>
            </template>
          </button>
        </div>
      </div>
    </form>
  </div>
  @else
  <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-3">
    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h3 class="text-base font-bold text-slate-900">No Outstanding Dues</h3>
    <p class="text-sm text-slate-500">All fees for this student have been fully paid.</p>
    <div class="pt-2">
      <a href="{{ route('students.show', $student->id) }}" class="btn btn-secondary btn-sm">Return to Student Profile</a>
    </div>
  </div>
  @endif

  @else
  <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-4">
    <h3 class="text-lg font-bold text-slate-800">No Student Selected</h3>
    <p class="text-sm text-slate-500">Please navigate from a student's profile to record a payment.</p>
    <div>
      <a href="{{ route('students.index') }}" class="btn btn-primary btn-sm">Go to Students Directory</a>
    </div>
  </div>
  @endif

</div>
@endsection
