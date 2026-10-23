@extends('layouts.app')
@section('title', 'Transport Roster & Van Manifest')

@section('content')
<div class="space-y-6" x-data="{
    showAddModal: false,
    showEditModal: false,
    editData: {
        id: '',
        van_number: 'VAN I',
        student_name: '',
        class_name: '',
        area: '',
        amount: '',
        morning_time: '',
        evening_time: ''
    },
    openEdit(item) {
        this.editData = { ...item };
        this.showEditModal = true;
    }
}">

  {{-- ── Top Navigation & Page Title ────────────────────────── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200">
    <div>
      <div class="flex items-center gap-2">
        <a href="{{ route('transport.index') }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Back to Transport Management">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
          Transport Passenger Manifest & Van Details
        </h1>
      </div>
      <p class="text-sm text-slate-500 mt-1 ml-8">
        Student details neatly arranged from PDF into Van 1, Van 2, Van 3, Van 4, and Van 5.
      </p>
    </div>

    <div class="flex items-center flex-wrap gap-2.5">
      <a href="{{ route('transport.roster.pdf', ['van' => $selectedVan]) }}" target="_blank"
         class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 rounded-xl shadow-2xs transition">
        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span>Export Official PDF</span>
      </a>

      <a href="{{ route('transport.roster.excel', ['van' => $selectedVan]) }}"
         class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 rounded-xl shadow-2xs transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span>Export Excel</span>
      </a>

      <button @click="showAddModal = true"
              type="button"
              class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>+ Add Passenger</span>
      </button>
    </div>
  </div>

  {{-- ── Flash Notifications ─────────────────────────────────── --}}
  @if(session('success'))
  <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
    <div class="flex items-center gap-2.5">
      <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span class="font-medium">{{ session('success') }}</span>
    </div>
  </div>
  @endif

  @if(isset($errors) && $errors->any())
  <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
    <div class="font-semibold mb-1">Please correct the following errors:</div>
    <ul class="list-disc list-inside text-xs space-y-0.5">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  {{-- ── KPI Quick Metrics Strip ─────────────────────────────── --}}
  <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
      <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Students</p>
        <h3 class="text-xl font-extrabold text-slate-900">{{ number_format($totalPassengers) }}</h3>
        <p class="text-[11px] text-indigo-600 font-semibold mt-0.5">From PDF Manifest</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
      <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Vans</p>
        <h3 class="text-xl font-extrabold text-slate-900">5 Vans</h3>
        <p class="text-[11px] text-slate-500 mt-0.5">Van 1, Van 2, Van 3, Van 4, Van 5</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
      <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Monthly Transport Fee</p>
        <h3 class="text-xl font-extrabold text-emerald-700">₹{{ number_format($totalBilling) }}</h3>
        <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Total scheduled fare</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
      <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Trip Modes</p>
        <h3 class="text-xl font-extrabold text-slate-900">{{ $bothTripsCount }} Both</h3>
        <p class="text-[11px] text-amber-600 font-semibold mt-0.5">{{ $dropOnlyCount }} Evening Drop-only</p>
      </div>
    </div>
  </div>

  {{-- ── Interactive Van Selector Tabs ───────────────────────── --}}
  <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs">
    <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
      {{-- All Vans Tab --}}
      <a href="{{ route('transport.roster', array_merge(request()->except('van'), ['van' => 'all'])) }}"
         class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $selectedVan === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200/70' }}">
        <div class="flex items-center gap-1.5">
          <svg class="w-4 h-4 {{ $selectedVan === 'all' ? 'text-white' : 'text-indigo-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
          </svg>
          <span>All Fleet Vans</span>
        </div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $selectedVan === 'all' ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-700' }}">
          {{ $totalPassengers }}
        </span>
      </a>

      {{-- Specific Van Tabs (Van 1, Van 2, Van 3, Van 4, Van 5) --}}
      @foreach($vansList as $key => $vMeta)
      @php
        $count = $vanCounts->get($key)?->count ?? 0;
        $isActive = ($selectedVan === $key);
      @endphp
      <a href="{{ route('transport.roster', array_merge(request()->except('van'), ['van' => $key])) }}"
         class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $isActive ? 'bg-slate-900 text-white shadow-md' : 'bg-slate-50 text-slate-800 hover:bg-slate-100 border border-slate-200/70' }}">
        <span>{{ $vMeta['name'] }}</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $isActive ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700' }}">
          {{ $count }}
        </span>
      </a>
      @endforeach
    </div>
  </div>

  {{-- ── Van Header & Info (when specific Van is selected) ── --}}
  @if($activeVanMeta)
  <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2.5 flex-wrap">
          <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wide">
            {{ $activeVanMeta['name'] }}
          </span>
          <h2 class="text-lg font-extrabold text-slate-900">
            Student Transport Details (from PDF)
          </h2>
          <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
            {{ $passengers->count() }} Students
          </span>
        </div>
        <div class="flex items-center gap-3 mt-2 text-xs text-slate-600 flex-wrap">
          <span class="inline-flex items-center gap-1 font-medium">
            <span class="text-slate-400">Driver:</span>
            <strong class="text-slate-800">{{ $activeVanMeta['driver'] }}</strong>
            <a href="tel:{{ str_replace(' ', '', $activeVanMeta['mobile']) }}" class="text-indigo-600 hover:underline font-mono">({{ $activeVanMeta['mobile'] }})</a>
          </span>
          <span class="text-slate-300">•</span>
          <span class="inline-flex items-center gap-1 font-medium">
            <span class="text-slate-400">Transit Attendant:</span>
            <strong class="text-slate-800">{{ $activeVanMeta['attendant'] }}</strong>
            <a href="tel:{{ str_replace(' ', '', $activeVanMeta['attendant_mobile']) }}" class="text-indigo-600 hover:underline font-mono">({{ $activeVanMeta['attendant_mobile'] }})</a>
          </span>
        </div>
      </div>

      <div class="flex items-center gap-4 shrink-0 sm:border-l sm:border-slate-100 sm:pl-4">
        <div>
          <p class="text-[10px] uppercase font-bold text-slate-400">Total Monthly Fare</p>
          <p class="text-xl font-black text-emerald-700">₹{{ number_format($passengers->sum('amount'), 0) }}</p>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- ── Search & Filter Controls ────────────────────────────── --}}
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
    <form method="GET" action="{{ route('transport.roster') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
      <input type="hidden" name="van" value="{{ $selectedVan }}">

      {{-- Search bar with live instant filtering --}}
      <div class="sm:col-span-6 relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input type="text"
               id="livePassengerSearch"
               name="search"
               value="{{ $search }}"
               placeholder="Search by student name, class, area/address..."
               class="w-full pl-10 pr-4 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"
               onkeyup="filterPassengersTable()">
      </div>

      {{-- Trip Type Filter --}}
      <div class="sm:col-span-4">
        <select name="trip" onchange="this.form.submit()" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-slate-50/50">
          <option value="all" {{ $tripFilter === 'all' ? 'selected' : '' }}>All Trip Types (Both & Drop Only)</option>
          <option value="both" {{ $tripFilter === 'both' ? 'selected' : '' }}>Both Morning & Evening Trips</option>
          <option value="drop_only" {{ $tripFilter === 'drop_only' ? 'selected' : '' }}>Evening Drop Only (EVE)</option>
        </select>
      </div>

      {{-- Submit & Reset --}}
      <div class="sm:col-span-2 flex items-center gap-2">
        <button type="submit" class="flex-1 py-2 px-3 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition shadow-2xs">
          Filter
        </button>
        @if($search !== '' || $tripFilter !== 'all' || $selectedVan !== 'all')
        <a href="{{ route('transport.roster') }}" class="py-2 px-3 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
          Reset
        </a>
        @endif
      </div>
    </form>
  </div>

  {{-- ── Master Roster Table (PDF Details Layout) ───────────── --}}
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
      <div class="flex items-center gap-3">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide">
          @if($selectedVan === 'all')
            All Vans (171 Students from PDF)
          @else
            {{ $vansList[$selectedVan]['name'] ?? $selectedVan }} — {{ $passengers->count() }} Students
          @endif
        </h2>
        <span class="text-xs px-2.5 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold rounded-full">
          Total Amount: ₹{{ number_format($passengers->sum('amount')) }}
        </span>
      </div>
      <div class="text-xs text-slate-400">
        Showing {{ $passengers->count() }} of {{ $totalPassengers }} total passengers
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse" id="rosterMasterTable">
        <thead>
          <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            <th class="py-3.5 px-4 w-12 text-center">#</th>
            <th class="py-3.5 px-4">Student Name</th>
            <th class="py-3.5 px-3 text-center">Class</th>
            <th class="py-3.5 px-4">Area / Address</th>
            <th class="py-3.5 px-3 text-center">Morning Pickup</th>
            <th class="py-3.5 px-3 text-center">Evening Drop</th>
            <th class="py-3.5 px-4 text-right">Amount (₹)</th>
            @if($selectedVan === 'all')
              <th class="py-3.5 px-4 text-center">Van</th>
            @endif
            <th class="py-3.5 px-4 text-center w-20">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          @forelse($passengers as $p)
          @php
            $isDropOnly = in_array(strtolower(trim((string)$p->morning_time)), ['eve', 'evening', '-', '']);
          @endphp
          <tr class="hover:bg-slate-50/70 transition passenger-row">
            {{-- Serial Number --}}
            <td class="py-3 px-4 text-center font-semibold text-slate-400">
              {{ $p->serial_no }}
            </td>

            {{-- Student Name --}}
            <td class="py-3 px-4 font-semibold text-slate-900">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center shrink-0 border border-slate-200">
                  {{ substr($p->student_name, 0, 1) }}
                </div>
                <div>
                  <span class="text-slate-800 font-semibold">{{ $p->student_name }}</span>
                  @if($p->student)
                    <span class="inline-block ml-1 px-1.5 py-0.2 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded">
                      Enrolled #{{ $p->student->admission_number }}
                    </span>
                  @endif
                </div>
              </div>
            </td>

            {{-- Class --}}
            <td class="py-3 px-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200/60">
                {{ $p->class_name }}
              </span>
            </td>

            {{-- Area / Stop --}}
            <td class="py-3 px-4">
              <div class="flex items-center gap-1.5 text-slate-700">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="font-medium">{{ $p->area }}</span>
              </div>
            </td>

            {{-- Morning Time --}}
            <td class="py-3 px-3 whitespace-nowrap">
              @if($isDropOnly)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                  <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Drop Only
                </span>
              @else
                <span class="inline-flex items-center gap-1 font-semibold text-slate-700">
                  <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  {{ $p->morning_time }} AM
                </span>
              @endif
            </td>

            {{-- Evening Drop Time --}}
            <td class="py-3 px-3 whitespace-nowrap">
              <span class="inline-flex items-center gap-1 font-semibold text-indigo-900">
                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                {{ $p->evening_time }} PM
              </span>
            </td>

            {{-- Monthly Fare --}}
            <td class="py-3 px-4 text-right font-bold text-emerald-700 whitespace-nowrap">
              ₹{{ number_format($p->amount, 0) }}
            </td>

            {{-- Assigned Van (shown when All Vans is selected) --}}
            @if($selectedVan === 'all')
            <td class="py-3 px-4 text-center">
              @php
                $vanLabelMap = [
                  'VAN I'   => 'Van 1',
                  'VAN II'  => 'Van 2',
                  'VAN III' => 'Van 3',
                  'VAN IV'  => 'Van 4',
                  'VAN V'   => 'Van 5',
                ];
                $vanName = $vanLabelMap[$p->van_number] ?? $p->van_number;
              @endphp
              <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-slate-100 text-slate-800 border border-slate-200">
                {{ $vanName }}
              </span>
            </td>
            @endif

            {{-- Actions --}}
            <td class="py-3 px-4 text-center">
              <div class="inline-flex items-center gap-1">
                {{-- Edit Button --}}
                <button type="button"
                        @click="openEdit({
                            id: '{{ $p->id }}',
                            van_number: '{{ $p->van_number }}',
                            student_name: '{{ addslashes($p->student_name) }}',
                            class_name: '{{ addslashes($p->class_name) }}',
                            area: '{{ addslashes($p->area) }}',
                            amount: '{{ $p->amount }}',
                            morning_time: '{{ $p->morning_time }}',
                            evening_time: '{{ $p->evening_time }}'
                        })"
                        class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                        title="Edit Passenger">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                {{-- Delete Form --}}
                <form action="{{ route('transport.roster.passenger.delete', $p->id) }}" method="POST" onsubmit="return confirm('Remove passenger {{ addslashes($p->student_name) }} from the roster?');" class="inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Passenger">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="py-12 text-center text-slate-400">
              <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="font-semibold text-slate-600">No passengers found matching criteria</p>
              <p class="text-xs text-slate-400 mt-1">Try resetting the filter or searching for another student name or stop.</p>
            </td>
          </tr>
          @endforelse
        </tbody>

        {{-- Table Summary Footer --}}
        @if($passengers->count() > 0)
        <tfoot>
          <tr class="bg-slate-50 border-t-2 border-slate-200 text-xs font-bold text-slate-700">
            <td colspan="3" class="py-3 px-4">
              Total Listed: {{ $passengers->count() }} Students
            </td>
            <td colspan="3" class="py-3 px-4 text-slate-500">
              Average Fare: ₹{{ number_format($passengers->avg('amount'), 1) }}/mo
            </td>
            <td class="py-3 px-4 text-right text-emerald-700 font-extrabold">
              ₹{{ number_format($passengers->sum('amount')) }}
            </td>
            <td colspan="{{ $selectedVan === 'all' ? 3 : 2 }}" class="py-3 px-4 text-slate-400 font-normal">
              Monthly Manifest Total
            </td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>

  <script>
    function filterPassengersTable() {
      const q = document.getElementById('livePassengerSearch').value.toLowerCase().trim();
      const rows = document.querySelectorAll('#rosterMasterTable tbody tr.passenger-row');
      rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        if (!q || text.includes(q)) {
          r.style.display = '';
        } else {
          r.style.display = 'none';
        }
      });
    }
  </script>

  {{-- ── Add Passenger Modal ─────────────────────────────────── --}}
  <div x-show="showAddModal"
       x-cloak
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100" @click.away="showAddModal = false">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800">Add Passenger to Roster</h3>
        <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form action="{{ route('transport.roster.passenger.store') }}" method="POST" class="mt-4 space-y-4">
        @csrf

        {{-- Van Selector --}}
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Van <span class="text-rose-500">*</span></label>
          <select name="van_number" required class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            <option value="VAN I">Van 1</option>
            <option value="VAN II">Van 2</option>
            <option value="VAN III">Van 3</option>
            <option value="VAN IV">Van 4</option>
            <option value="VAN V">Van 5</option>
          </select>
        </div>

        {{-- Student Name & Class --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Student Name <span class="text-rose-500">*</span></label>
            <input type="text" name="student_name" required placeholder="e.g. DEVARASU" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Class / Standard <span class="text-rose-500">*</span></label>
            <input type="text" name="class_name" required placeholder="e.g. V, UKG, LKG, III" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        {{-- Boarding Area & Monthly Fee --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Boarding Area / Stop <span class="text-rose-500">*</span></label>
            <input type="text" name="area" required placeholder="e.g. RN PUDUR" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Fare (₹) <span class="text-rose-500">*</span></label>
            <input type="number" step="0.01" name="amount" required placeholder="e.g. 750" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        {{-- Pickup & Drop Timings --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Morning Pickup Time</label>
            <input type="text" name="morning_time" placeholder="e.g. 7.15 or eve" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            <span class="text-[10px] text-slate-400">Enter 'eve' for evening drop only</span>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Evening Drop Time</label>
            <input type="text" name="evening_time" placeholder="e.g. 5.35" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
          <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition">
            Save Passenger
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Edit Passenger Modal ────────────────────────────────── --}}
  <div x-show="showEditModal"
       x-cloak
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100" @click.away="showEditModal = false">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800">Edit Passenger Details</h3>
        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form :action="'{{ url('transport/roster/passenger') }}/' + editData.id" method="POST" class="mt-4 space-y-4">
        @csrf
        @method('PUT')

        {{-- Van Selector --}}
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Van <span class="text-rose-500">*</span></label>
          <select name="van_number" x-model="editData.van_number" required class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            <option value="VAN I">Van 1</option>
            <option value="VAN II">Van 2</option>
            <option value="VAN III">Van 3</option>
            <option value="VAN IV">Van 4</option>
            <option value="VAN V">Van 5</option>
          </select>
        </div>

        {{-- Student Name & Class --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Student Name <span class="text-rose-500">*</span></label>
            <input type="text" name="student_name" x-model="editData.student_name" required class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Class / Standard <span class="text-rose-500">*</span></label>
            <input type="text" name="class_name" x-model="editData.class_name" required class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        {{-- Boarding Area & Monthly Fee --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Boarding Area / Stop <span class="text-rose-500">*</span></label>
            <input type="text" name="area" x-model="editData.area" required class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Fare (₹) <span class="text-rose-500">*</span></label>
            <input type="number" step="0.01" name="amount" x-model="editData.amount" required class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        {{-- Pickup & Drop Timings --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Morning Pickup Time</label>
            <input type="text" name="morning_time" x-model="editData.morning_time" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            <span class="text-[10px] text-slate-400">Enter 'eve' for evening drop only</span>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Evening Drop Time</label>
            <input type="text" name="evening_time" x-model="editData.evening_time" class="w-full py-2 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
          <button type="button" @click="showEditModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition">
            Update Passenger
          </button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
