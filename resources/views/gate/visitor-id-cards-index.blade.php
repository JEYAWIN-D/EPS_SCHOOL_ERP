@extends('layouts.app')

@section('title', 'Visitor ID Card Management — Erode Public School')

@section('content')
<div class="space-y-6" x-data="{
  orientation: '{{ $orientation }}',
  checkoutModal: false,
  activeVisitorId: null,
  activeVisitorName: '',

  openCheckout(id, name) {
    this.activeVisitorId = id;
    this.activeVisitorName = name;
    this.checkoutModal = true;
  }
}">

  {{-- ── 1. Top Header Banner ── --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-2xl bg-[#b91c1c] text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl font-black text-slate-900 tracking-tight">Visitor ID Card Management</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">
            ★ TEMPORARY PASS PROTOCOL ★
          </span>
          <span class="badge-blue text-[11px] font-semibold">Security Gate</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Issue temporary credentials, webcam photo capture, auto-calculated expiry, and instant thermal / CR80 visitor pass printing.
        </p>
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-2.5 flex-wrap">
      <a href="{{ route('gate.create') }}" class="btn btn-primary btn-sm flex items-center gap-2 bg-[#b91c1c] hover:bg-[#991b1b] text-white font-bold px-4 py-2 rounded-xl shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Register New Visitor</span>
      </a>

      <a href="{{ route('id-cards.settings', ['card_type' => 'visitor', 'orientation' => $orientation]) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Pass Settings
      </a>
    </div>
  </div>

  {{-- ── 2. KPI Metrics Bar ── --}}
  <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Currently Inside Campus</p>
      <p class="text-2xl font-black text-emerald-700 mt-1 font-mono">{{ $activeInsideCount }}</p>
      <p class="text-[11px] text-emerald-600/80 mt-0.5">Active visitor passes today</p>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Visits Today</p>
      <p class="text-2xl font-black text-slate-800 mt-1 font-mono">{{ $todayTotal }}</p>
      <p class="text-[11px] text-slate-500 mt-0.5">Logged at security gate</p>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Overstayed Alert</p>
      <p class="text-2xl font-black text-rose-700 mt-1 font-mono">{{ $overstayCount }}</p>
      <p class="text-[11px] text-rose-600/80 mt-0.5">Exceeded expected exit time</p>
    </div>
  </div>

  {{-- ── 3. Tabs & Search Bar ── --}}
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
      
      {{-- Tabs --}}
      <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold w-full sm:w-auto">
        <a href="{{ route('gate.visitor-id-card-management', ['tab' => 'active', 'orientation' => $orientation]) }}"
           class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'active' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}">
          Active Inside ({{ $activeInsideCount }})
        </a>
        <a href="{{ route('gate.visitor-id-card-management', ['tab' => 'today', 'orientation' => $orientation]) }}"
           class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'today' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}">
          Today ({{ $todayTotal }})
        </a>
        <a href="{{ route('gate.visitor-id-card-management', ['tab' => 'overstayed', 'orientation' => $orientation]) }}"
           class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'overstayed' ? 'bg-white shadow-xs text-rose-700' : 'text-slate-500 hover:text-slate-800' }}">
          Overstayed ({{ $overstayCount }})
        </a>
        <a href="{{ route('gate.visitor-id-card-management', ['tab' => 'all', 'orientation' => $orientation]) }}"
           class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'all' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}">
          All History
        </a>
      </div>

      {{-- Search --}}
      <form method="GET" action="{{ route('gate.visitor-id-card-management') }}" class="w-full sm:w-72">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="orientation" :value="orientation">
        <div class="relative">
          <input type="text" name="search" value="{{ $search }}" placeholder="Pass #, visitor name, host..." class="form-input text-xs w-full pl-8 font-medium">
          <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
      </form>
    </div>
  </div>

  {{-- ── 4. Visitor Passes Table ── --}}
  <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
            <th class="p-3.5 w-14 text-center">Photo</th>
            <th class="p-3.5">Pass Number</th>
            <th class="p-3.5">Visitor Information</th>
            <th class="p-3.5">Person to Meet &amp; Dept</th>
            <th class="p-3.5">Purpose</th>
            <th class="p-3.5">Entry &amp; Expiry</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5 text-right">Pass Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          @forelse($visitors as $vis)
            @php
              $isOverstayed = empty($vis->out_time) && $vis->expected_exit_time && \Carbon\Carbon::parse($vis->expected_exit_time)->lt(now());
            @endphp
            <tr class="hover:bg-slate-50/70 transition-colors {{ $isOverstayed ? 'bg-rose-50/40' : '' }}">
              {{-- Photo --}}
              <td class="p-3.5 text-center">
                @if(!empty($vis->visitor_photo))
                  <img src="{{ asset('storage/' . $vis->visitor_photo) }}" alt="{{ $vis->visitor_name }}" class="w-10 h-10 rounded-xl object-cover ring-1 ring-slate-200 mx-auto shadow-2xs">
                @else
                  <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center mx-auto text-[10px] font-bold">
                    VIS
                  </div>
                @endif
              </td>

              {{-- Pass Number --}}
              <td class="p-3.5 font-mono">
                <span class="font-extrabold text-[#b91c1c] text-sm">{{ $vis->pass_number }}</span>
                <span class="block text-[10px] text-slate-400 capitalize">{{ str_replace('_', ' ', $vis->category) }}</span>
              </td>

              {{-- Visitor Info --}}
              <td class="p-3.5">
                <div class="font-bold text-slate-900 text-sm">{{ $vis->visitor_name }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                  @if($vis->company_name)
                    <strong class="text-slate-700">{{ $vis->company_name }}</strong> &bull;
                  @endif
                  {{ $vis->visitor_phone ?? 'No Phone' }}
                </div>
              </td>

              {{-- Whom to Meet --}}
              <td class="p-3.5">
                <div class="font-bold text-slate-800">{{ $vis->whom_to_meet ?? $vis->hostEmployee?->full_name ?? 'School Office' }}</div>
                @if($vis->department)
                  <div class="text-[10px] text-slate-400">{{ $vis->department }}</div>
                @endif
              </td>

              {{-- Purpose --}}
              <td class="p-3.5 text-slate-600 max-w-[200px] truncate" title="{{ $vis->purpose }}">
                {{ $vis->purpose }}
              </td>

              {{-- Timestamps --}}
              <td class="p-3.5 text-[11px] font-mono">
                <div>In: <strong>{{ $vis->in_time ? \Carbon\Carbon::parse($vis->in_time)->format('h:i A') : '—' }}</strong></div>
                @if($vis->expected_exit_time)
                  <div class="{{ $isOverstayed ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                    Exp: {{ \Carbon\Carbon::parse($vis->expected_exit_time)->format('h:i A') }}
                    @if($isOverstayed)
                      <span class="text-[10px] uppercase font-bold text-rose-700 bg-rose-100 px-1 rounded ml-1">LATE</span>
                    @endif
                  </div>
                @endif
              </td>

              {{-- Status --}}
              <td class="p-3.5">
                @if($vis->out_time)
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                    Checked Out
                  </span>
                @elseif($isOverstayed)
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                    Overstayed
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Inside Campus
                  </span>
                @endif
              </td>

              {{-- Actions --}}
              <td class="p-3.5 text-right space-x-1">
                {{-- Preview & Studio --}}
                <a href="{{ route('gate.visitor-id-card.studio', ['id' => $vis->id, 'orientation' => $orientation]) }}"
                   class="btn btn-secondary btn-sm font-bold text-indigo-700 hover:text-indigo-900"
                   title="Preview ID Badge">
                  Badge Studio
                </a>

                {{-- Print CR80 PDF --}}
                <a href="{{ route('gate.visitor-id-card.pdf', ['id' => $vis->id, 'orientation' => $orientation]) }}"
                   target="_blank"
                   class="btn btn-secondary btn-sm font-bold bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100"
                   title="Print CR80 Identity Card">
                  CR80 Pass
                </a>

                {{-- Thermal Pass --}}
                <a href="{{ route('gate.pass', ['id' => $vis->id]) }}"
                   target="_blank"
                   class="btn btn-secondary btn-sm font-bold"
                   title="80mm Thermal Receipt Slip">
                  Thermal
                </a>

                {{-- Check Out Button --}}
                @if(empty($vis->out_time))
                  <button type="button" @click="openCheckout({{ $vis->id }}, '{{ addslashes($vis->visitor_name) }}')"
                          class="btn btn-secondary btn-sm font-bold text-emerald-700 border-emerald-200 hover:bg-emerald-50"
                          title="Record Exit Time">
                    Check Out
                  </button>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="p-8 text-center text-slate-500">
                <p class="font-bold text-slate-800">No visitor records found</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($visitors->hasPages())
      <div class="p-4 border-t border-slate-100">
        {{ $visitors->links() }}
      </div>
    @endif
  </div>

  {{-- Check Out Modal --}}
  <div x-show="checkoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <h3 class="font-bold text-slate-900 text-sm">Record Visitor Exit</h3>
      <p class="text-xs text-slate-600">
        Confirm exit for visitor <strong class="text-slate-800" x-text="activeVisitorName"></strong>? This will record the checkout timestamp and mark the temporary pass as returned.
      </p>
      <form :action="'{{ url('gate') }}/' + activeVisitorId + '/checkout'" method="POST" class="flex justify-end gap-2 pt-2">
        @csrf
        @method('PATCH')
        <button type="button" @click="checkoutModal = false" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm bg-emerald-600 hover:bg-emerald-700 text-white font-bold">Confirm Exit</button>
      </form>
    </div>
  </div>

</div>
@endsection
