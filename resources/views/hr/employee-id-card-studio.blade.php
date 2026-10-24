@extends('layouts.app')

@section('title', 'Staff ID Card Studio — Teaching, Non-Teaching, Drivers, Nannies, Cleaners')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ 
  viewStyle: '{{ $style ?? 'landscape' }}', 
  activeSide: '{{ $side ?? 'both' }}' 
}">

  {{-- ── Top Navigation & Category Filter Tabs ────────────────── --}}
  <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <a href="{{ route('hr.employees') }}" class="btn-icon w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="Back to Employee Directory">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="page-title text-xl font-black text-slate-900">Staff ID Card Studio</h1>
            @if($employee && isset($employee->theme_meta))
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $employee->theme_meta['badge_class'] }}">
                {{ $employee->theme_meta['theme_label'] }}
              </span>
            @endif
            <span class="badge-blue text-[10px] font-bold">5 Staff Categories</span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Teaching, Non-Teaching, Drivers, Nannies &amp; Cleaners &bull; Switch Landscape/Portrait &amp; Download Front &amp; Back PDF
          </p>
        </div>
      </div>

      {{-- Action Controls & Switchers --}}
      <div class="flex flex-wrap items-center gap-2.5">
        {{-- Style Switcher --}}
        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-xs font-bold shadow-2xs">
          <button type="button" @click="viewStyle = 'landscape'"
                  :class="viewStyle === 'landscape' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                  class="px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="5" width="20" height="14" rx="2"/>
            </svg>
            <span>Landscape</span>
          </button>
          <button type="button" @click="viewStyle = 'portrait'"
                  :class="viewStyle === 'portrait' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                  class="px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="5" y="2" width="14" height="20" rx="2"/>
            </svg>
            <span>Portrait</span>
          </button>
        </div>

        {{-- Side Switcher --}}
        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-xs font-bold shadow-2xs">
          <button type="button" @click="activeSide = 'both'"
                  :class="activeSide === 'both' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                  class="px-2.5 py-1 rounded-lg transition cursor-pointer">
            Both Sides
          </button>
          <button type="button" @click="activeSide = 'front'"
                  :class="activeSide === 'front' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                  class="px-2.5 py-1 rounded-lg transition cursor-pointer">
            Front Only
          </button>
          <button type="button" @click="activeSide = 'back'"
                  :class="activeSide === 'back' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                  class="px-2.5 py-1 rounded-lg transition cursor-pointer">
            Back Only
          </button>
        </div>

        {{-- Bulk PDF Download Button --}}
        <a :href="'{{ route('hr.id-cards.download') }}?category={{ $categoryFilter }}&department_id={{ $deptFilter }}&search={{ urlencode($search ?? '') }}&style=' + viewStyle + '&side=' + activeSide"
           target="_blank"
           class="btn btn-primary btn-sm flex items-center gap-2 text-xs font-black shadow-md bg-rose-900 hover:bg-rose-800 text-white px-3.5 py-2 rounded-xl transition cursor-pointer">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          <span>Download PDF ({{ $filteredEmployees->count() }})</span>
        </a>
      </div>
    </div>

    {{-- ── 5 Category Filter Tabs (Requested by User) ───────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
      <div class="flex flex-wrap items-center gap-1.5">
        {{-- All Staff --}}
        <a href="{{ route('hr.id-card-studio', ['category' => 'all', 'department_id' => $deptFilter]) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $categoryFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
          All Staff ({{ $counts['all'] ?? 0 }})
        </a>

        {{-- 1. Teaching --}}
        <a href="{{ route('hr.id-card-studio', ['category' => 'teaching', 'department_id' => $deptFilter]) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $categoryFilter === 'teaching' ? 'bg-rose-900 text-white shadow-xs' : 'bg-rose-50 text-rose-900 hover:bg-rose-100 border border-rose-200' }}">
          <span>🎓</span>
          <span>Teaching ({{ $counts['teaching'] ?? 0 }})</span>
        </a>

        {{-- 2. Non-Teaching --}}
        <a href="{{ route('hr.id-card-studio', ['category' => 'non_teaching', 'department_id' => $deptFilter]) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $categoryFilter === 'non_teaching' ? 'bg-teal-800 text-white shadow-xs' : 'bg-teal-50 text-teal-900 hover:bg-teal-100 border border-teal-200' }}">
          <span>🏢</span>
          <span>Non-Teaching ({{ $counts['non_teaching'] ?? 0 }})</span>
        </a>

        {{-- 3. Drivers --}}
        <a href="{{ route('hr.id-card-studio', ['category' => 'driver', 'department_id' => $deptFilter]) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $categoryFilter === 'driver' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200' }}">
          <span>🚌</span>
          <span>Drivers ({{ $counts['driver'] ?? 0 }})</span>
        </a>

        {{-- 4. Nannies / Caretakers --}}
        <a href="{{ route('hr.id-card-studio', ['category' => 'nanny', 'department_id' => $deptFilter]) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $categoryFilter === 'nanny' ? 'bg-pink-700 text-white shadow-xs' : 'bg-pink-50 text-pink-900 hover:bg-pink-100 border border-pink-200' }}">
          <span>👶</span>
          <span>Nannies ({{ $counts['nanny'] ?? 0 }})</span>
        </a>

        {{-- 5. Cleaners / Housekeeping --}}
        <a href="{{ route('hr.id-card-studio', ['category' => 'cleaner', 'department_id' => $deptFilter]) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $categoryFilter === 'cleaner' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 border border-emerald-200' }}">
          <span>🧹</span>
          <span>Cleaners ({{ $counts['cleaner'] ?? 0 }})</span>
        </a>
      </div>

      {{-- Department & Staff Selector Form --}}
      <form method="GET" action="{{ route('hr.id-card-studio') }}" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="category" value="{{ $categoryFilter }}">
        <input type="hidden" name="style" :value="viewStyle">
        <input type="hidden" name="side" :value="activeSide">

        <select name="department_id" onchange="this.form.submit()" class="select-sm text-xs bg-slate-50 border-slate-200 rounded-lg">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept->id }}" @selected($deptFilter == $dept->id)>{{ $dept->name }}</option>
          @endforeach
        </select>

        <select name="employee_id" onchange="this.form.submit()" class="select-sm text-xs bg-slate-50 border-slate-200 rounded-lg max-w-[200px]">
          @foreach($filteredEmployees as $emp)
            <option value="{{ $emp->id }}" @selected($employee && $employee->id === $emp->id)>
              {{ $emp->full_name }} ({{ $emp->employee_code }})
            </option>
          @endforeach
        </select>
      </form>
    </div>
  </div>

  {{-- ── Employee Photo Upload Box ────────────────────────────── --}}
  @if($employee)
  <div class="bg-gradient-to-r from-rose-50 via-amber-50 to-emerald-50 border border-slate-200 p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-11 h-11 rounded-2xl bg-rose-900 text-white flex items-center justify-center font-bold text-lg shadow-md shrink-0">
        📷
      </div>
      <div>
        <h4 class="text-xs font-bold text-slate-900">Upload Photo for {{ $employee->full_name }} ({{ $employee->employee_code }})</h4>
        <p class="text-[11px] text-slate-600">Upload a crisp passport-size photo to instantly display on their ID card and printable PDF.</p>
      </div>
    </div>

    <form action="{{ route('hr.employees.update-photo', $employee->id) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 w-full sm:w-auto">
      @csrf
      <input type="file" name="photo" accept="image/*" class="input text-xs bg-white py-1 text-slate-700 w-full sm:w-60 border-slate-300" required>
      <button type="submit" class="btn btn-secondary btn-xs font-bold px-3.5 py-1.5 whitespace-nowrap bg-white hover:bg-slate-50 border-slate-300 cursor-pointer">
        Upload Photo
      </button>
    </form>
  </div>
  @endif

  {{-- ── SELECTED EMPLOYEE HERO CARD (Landscape / Portrait) ────── --}}
  @if($employee)
  @php
    $theme = $employee->theme_meta ?? [
      'category_key' => 'teaching',
      'theme_label'  => 'TEACHING FACULTY',
      'header_color' => '#8C2826',
      'accent_color' => '#8C2826',
    ];
    $headerBg = $theme['header_color'] ?? '#8C2826';
    $accentColor = $theme['accent_color'] ?? '#8C2826';
    $themeLabel = $theme['theme_label'] ?? 'STAFF';

    $phone = $employee->emergency_contact_mobile ?: ($employee->mobile ?: '9876543210');
    $fullAddress = $employee->residential_address ?: ($employee->permanent_address ?: ($employee->address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051')));
  @endphp

  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
          <span>Active Staff Preview: {{ $employee->full_name }}</span>
          <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" style="background: {{ $headerBg }};">
            {{ $themeLabel }}
          </span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
          {{ $employee->designation }} &bull; Department: {{ $employee->department?->name ?? 'Campus Block' }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a :href="'{{ route('hr.id-cards.download') }}?ids={{ $employee->id }}&style=' + viewStyle + '&side=' + activeSide"
           target="_blank"
           class="btn btn-secondary btn-sm text-xs font-bold">
          Download This Card (PDF)
        </a>
      </div>
    </div>

    {{-- ── Landscape Preview Container ────────────────────────── --}}
    <template x-if="viewStyle === 'landscape'">
      <div class="flex flex-col lg:flex-row items-center justify-center gap-8 py-4">

        {{-- Front Landscape --}}
        <div x-show="activeSide === 'both' || activeSide === 'front'" class="flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between"
               style="width: 420px; height: 265px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Top Banner --}}
            <div class="h-14 px-4 text-white flex items-center justify-between border-b-2 border-amber-500"
                 style="background: {{ $headerBg }};">
              <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/school-logo.png') }}" class="w-8 h-8 rounded object-contain bg-white/10 p-0.5" alt="Logo">
                <div>
                  <h4 class="text-[11px] font-black uppercase tracking-wider text-white leading-tight">
                    {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
                  </h4>
                  <p class="text-[8.5px] text-amber-300 leading-tight">CBSE AFFILIATED SENIOR SECONDARY &bull; ERODE</p>
                </div>
              </div>
              <span class="text-[8.5px] font-black uppercase px-2 py-0.5 rounded bg-white/20 text-white">
                {{ $themeLabel }}
              </span>
            </div>

            {{-- Body --}}
            <div class="px-4 py-2.5 flex items-center gap-4 flex-1">
              {{-- Photo --}}
              <div class="w-22 h-26 rounded-xl border-2 overflow-hidden bg-slate-100 flex items-center justify-center shrink-0 shadow-sm"
                   style="border-color: {{ $accentColor }};">
                @if($employee->photo && file_exists(public_path('storage/' . $employee->photo)))
                  <img src="{{ asset('storage/' . $employee->photo) }}" class="w-full h-full object-cover" alt="">
                @else
                  <span class="text-2xl font-black text-white w-full h-full flex items-center justify-center"
                        style="background: {{ $accentColor }};">
                    {{ strtoupper(substr($employee->first_name ?? 'S', 0, 1) . substr($employee->last_name ?? 'T', 0, 1)) }}
                  </span>
                @endif
              </div>

              {{-- Info --}}
              <div class="min-w-0 flex-1 space-y-1">
                <h3 class="text-sm font-black text-slate-900 uppercase truncate leading-tight">
                  {{ $employee->full_name }}
                </h3>
                <p class="text-xs font-bold truncate" style="color: {{ $accentColor }};">
                  {{ $employee->designation ?? $themeLabel }} &bull; {{ $employee->department?->name ?? 'Campus' }}
                </p>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px] space-y-0.5 font-semibold text-slate-700">
                  <div class="flex justify-between">
                    <span class="text-slate-400">Employee ID:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $employee->employee_code ?? 'EMP-' . $employee->id }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">DOB:</span>
                    <span class="font-bold text-slate-900">{{ $employee->dob ? $employee->dob->format('d/m/Y') : '15/08/1990' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">Date Joined:</span>
                    <span class="font-bold text-slate-900">{{ $employee->joining_date ? $employee->joining_date->format('d/m/Y') : '—' }}</span>
                  </div>
                </div>

                {{-- Principal Signature --}}
                <div class="flex items-center justify-between pt-1 text-[8.5px]">
                  <span class="text-slate-400 font-bold">Session: 2025-2026</span>
                  <div class="text-right">
                    <img src="{{ asset('images/principal-signature.svg') }}" class="h-5 inline-block" alt="Sign">
                    <div class="text-[7px] text-slate-500 font-bold leading-none">Principal Signature</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-4 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">{{ $school->website ?? 'www.erodepublicschool.edu.in' }}</span>
              <span class="text-amber-400">FRONT SIDE</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Front Side Preview</span>
        </div>

        {{-- Back Landscape --}}
        <div x-show="activeSide === 'both' || activeSide === 'back'" class="flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between"
               style="width: 420px; height: 265px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Header --}}
            <div class="h-8 px-4 bg-slate-900 text-white flex items-center justify-between border-b-2 border-rose-900 text-[10px] font-bold">
              <span>{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }} — STAFF VERIFICATION</span>
              <span class="text-amber-400">BACK SIDE</span>
            </div>

            {{-- Body --}}
            <div class="p-3.5 flex gap-3.5 flex-1">
              <div class="flex-1 space-y-2">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px]">
                  <span class="text-[8px] font-bold uppercase text-slate-400 block">RESIDENTIAL ADDRESS</span>
                  <p class="font-semibold text-slate-800 leading-snug">
                    {{ Str::limit($fullAddress, 80) }}
                  </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px]">
                    <span class="text-[8px] font-bold uppercase text-slate-400 block">EMERGENCY PHONE</span>
                    <span class="font-mono font-bold text-slate-900">+91 {{ $phone }}</span>
                  </div>
                  <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[10px]">
                    <span class="text-[8px] font-bold uppercase text-slate-400 block">BLOOD GROUP</span>
                    <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-bold text-[9px] inline-block">
                      {{ $employee->blood_group ?: 'B+' }}
                    </span>
                  </div>
                </div>

                <div class="bg-slate-100 p-2 rounded-lg text-[9px] text-slate-600 leading-tight border-l-2 border-rose-900">
                  Authorized card of <strong>Erode Public School</strong>. If found, return to School HR Office. Helpline: +91 {{ $school->phone ?? '9876543210' }}
                </div>
              </div>

              {{-- QR Code --}}
              <div class="w-24 flex flex-col items-center justify-center border-l border-slate-200 pl-3">
                <div class="w-20 h-20 bg-white border border-slate-300 p-1 rounded-lg flex items-center justify-center">
                  @if(!empty($employee->_qrCodeSvg))
                    {!! $employee->_qrCodeSvg !!}
                  @else
                    <span class="text-[8px] text-slate-400 font-mono">[QR CODE]</span>
                  @endif
                </div>
                <span class="text-[8px] font-bold text-slate-500 mt-1.5">STAFF VERIFY</span>
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-4 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">Staff Helpline: +91 {{ $school->phone ?? '9876543210' }}</span>
              <span class="text-amber-400">VALID: 2026-2027</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Back Side Preview</span>
        </div>

      </div>
    </template>

    {{-- ── Portrait Preview Container ─────────────────────────── --}}
    <template x-if="viewStyle === 'portrait'">
      <div class="flex flex-col lg:flex-row items-center justify-center gap-8 py-4">

        {{-- Front Portrait --}}
        <div x-show="activeSide === 'both' || activeSide === 'front'" class="flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between text-center"
               style="width: 240px; height: 380px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Header --}}
            <div class="p-3 text-white border-b-2 border-amber-500" style="background: {{ $headerBg }};">
              <img src="{{ asset('images/school-logo.png') }}" class="w-7 h-7 mx-auto rounded object-contain mb-1 bg-white/10 p-0.5" alt="Logo">
              <h4 class="text-[9.5px] font-black uppercase tracking-wider text-white leading-tight">
                {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
              </h4>
              <p class="text-[7.5px] text-amber-300 leading-tight">{{ $themeLabel }} &bull; ERODE</p>
            </div>

            {{-- Body --}}
            <div class="p-3 flex-1 flex flex-col items-center justify-between">
              {{-- Photo --}}
              <div class="w-22 h-26 rounded-xl border-2 overflow-hidden bg-slate-100 flex items-center justify-center shadow-xs"
                   style="border-color: {{ $accentColor }};">
                @if($employee->photo && file_exists(public_path('storage/' . $employee->photo)))
                  <img src="{{ asset('storage/' . $employee->photo) }}" class="w-full h-full object-cover" alt="">
                @else
                  <span class="text-2xl font-black text-white w-full h-full flex items-center justify-center"
                        style="background: {{ $accentColor }};">
                    {{ strtoupper(substr($employee->first_name ?? 'S', 0, 1) . substr($employee->last_name ?? 'T', 0, 1)) }}
                  </span>
                @endif
              </div>

              {{-- Info --}}
              <div class="w-full space-y-1">
                <h4 class="text-xs font-black text-slate-900 uppercase truncate">
                  {{ $employee->full_name }}
                </h4>
                <p class="text-[10px] font-bold truncate" style="color: {{ $accentColor }};">
                  {{ $employee->designation ?? $themeLabel }} &bull; {{ $employee->department?->name ?? 'Staff' }}
                </p>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-[9px] space-y-0.5 font-semibold text-slate-700 text-left">
                  <div class="flex justify-between">
                    <span class="text-slate-400">Emp No:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $employee->employee_code ?? 'EMP-' . $employee->id }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">DOB:</span>
                    <span class="font-bold text-slate-900">{{ $employee->dob ? $employee->dob->format('d/m/Y') : '15/08/1990' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">Joined:</span>
                    <span class="font-bold text-slate-900">{{ $employee->joining_date ? $employee->joining_date->format('d/m/Y') : '—' }}</span>
                  </div>
                </div>
              </div>

              {{-- Signature --}}
              <div class="w-full pt-1.5 border-t border-slate-100 flex flex-col items-center">
                <img src="{{ asset('images/principal-signature.svg') }}" class="h-5 inline-block" alt="Sign">
                <span class="text-[7px] font-bold text-slate-600">Principal Signature</span>
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-3 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">2026-2027</span>
              <span class="text-amber-400">FRONT</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Portrait Front</span>
        </div>

        {{-- Back Portrait --}}
        <div x-show="activeSide === 'both' || activeSide === 'back'" class="flex flex-col items-center">
          <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300 overflow-hidden flex flex-col justify-between text-left"
               style="width: 240px; height: 380px; font-family: 'Segoe UI', Tahoma, sans-serif;">
            
            {{-- Header --}}
            <div class="p-2.5 bg-slate-900 text-white text-center border-b-2 border-rose-900">
              <h4 class="text-[9.5px] font-black uppercase tracking-wider text-white">ERODE PUBLIC SCHOOL</h4>
              <p class="text-[7.5px] text-amber-300">STAFF IDENTITY &amp; EMERGENCY</p>
            </div>

            {{-- Body --}}
            <div class="p-3 flex-1 flex flex-col justify-between space-y-2">
              <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[9px]">
                <span class="text-[7.5px] font-bold uppercase text-slate-400 block">RESIDENTIAL ADDRESS</span>
                <p class="font-semibold text-slate-800 leading-snug">
                  {{ Str::limit($fullAddress, 80) }}
                </p>
              </div>

              <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[9px]">
                <span class="text-[7.5px] font-bold uppercase text-slate-400 block">EMERGENCY PHONE NUMBER</span>
                <span class="font-mono font-bold text-slate-900">+91 {{ $phone }}</span>
              </div>

              <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-[9px] flex items-center justify-between">
                <span class="text-[7.5px] font-bold uppercase text-slate-400">BLOOD GROUP</span>
                <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-bold text-[9px]">
                  {{ $employee->blood_group ?: 'B+' }}
                </span>
              </div>

              {{-- QR Code --}}
              <div class="text-center py-1">
                <div class="w-18 h-18 mx-auto bg-white border border-slate-300 p-1 rounded-lg flex items-center justify-center">
                  @if(!empty($employee->_qrCodeSvg))
                    {!! $employee->_qrCodeSvg !!}
                  @else
                    <span class="text-[8px] text-slate-400 font-mono">[QR CODE]</span>
                  @endif
                </div>
                <span class="text-[7.5px] font-bold text-slate-500 block mt-1">STAFF VERIFY</span>
              </div>

              <div class="bg-slate-100 p-1.5 rounded text-[7.5px] text-slate-600 leading-tight border-l-2 border-rose-900">
                If found, return to School HR Office. Helpline: +91 {{ $school->phone ?? '9876543210' }}
              </div>
            </div>

            {{-- Footer --}}
            <div class="h-6 px-3 bg-slate-900 text-white flex items-center justify-between text-[8px] font-bold">
              <span class="text-slate-400">VALID: 2026-2027</span>
              <span class="text-amber-400">BACK</span>
            </div>
          </div>
          <span class="text-xs font-black text-slate-500 uppercase mt-2">Portrait Back</span>
        </div>

      </div>
    </template>
  </div>
  @endif

  {{-- ── ALL STAFF ID CARDS GALLERY (Showing All Employees in Category) ── --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-slate-800 text-base">
        All Staff ID Cards in Category: 
        <span class="capitalize text-rose-900">{{ str_replace('_', ' ', $categoryFilter) }} ({{ $filteredEmployees->count() }})</span>
      </h3>
      <span class="text-xs text-slate-500">Click any staff member to select as active inspector</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      @forelse($filteredEmployees as $emp)
      @php
        $empTheme = $emp->theme_meta ?? [
          'header_color' => '#8C2826',
          'accent_color' => '#8C2826',
          'theme_label'  => 'STAFF',
          'icon'         => '👤',
        ];
        $empHeaderBg = $empTheme['header_color'] ?? '#8C2826';
        $empAccent = $empTheme['accent_color'] ?? '#8C2826';
      @endphp
      <a href="{{ route('hr.id-card-studio', ['category' => $categoryFilter, 'department_id' => $deptFilter, 'employee_id' => $emp->id]) }}"
         class="group card p-0 border transition-all duration-200 overflow-hidden flex flex-col justify-between block bg-white hover:shadow-lg {{ $employee && $employee->id === $emp->id ? 'ring-2 ring-rose-900 border-rose-900' : 'border-slate-200' }}">

        {{-- Card Header --}}
        <div class="p-3 text-white flex items-center justify-between" style="background: {{ $empHeaderBg }};">
          <div>
            <p class="text-[9px] font-black uppercase tracking-wider leading-none text-white drop-shadow-2xs">
              {{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}
            </p>
            <span class="inline-block mt-1 text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-white/20 text-white">
              {{ $empTheme['icon'] ?? '👤' }} {{ $empTheme['theme_label'] }}
            </span>
          </div>
          <span class="text-[9px] font-mono font-bold bg-black/25 px-2 py-0.5 rounded-full text-white">
            {{ $emp->employee_code ?? 'EMP-' . $emp->id }}
          </span>
        </div>

        {{-- Card Body --}}
        <div class="p-3.5 flex items-center gap-3">
          <div class="shrink-0">
            <div class="w-14 h-16 rounded-xl bg-slate-50 border overflow-hidden flex items-center justify-center shadow-2xs"
                 style="border-color: {{ $empAccent }}40;">
              @if($emp->photo && file_exists(public_path('storage/' . $emp->photo)))
                <img src="{{ asset('storage/' . $emp->photo) }}" alt="{{ $emp->full_name }}" class="w-full h-full object-cover">
              @else
                <div class="w-full h-full flex items-center justify-center font-black text-sm text-white"
                     style="background: {{ $empAccent }};">
                  {{ strtoupper(substr($emp->first_name ?? 'S', 0, 1) . substr($emp->last_name ?? 'T', 0, 1)) }}
                </div>
              @endif
            </div>
          </div>

          <div class="min-w-0 flex-1 space-y-1">
            <p class="font-black text-sm text-slate-900 truncate group-hover:text-rose-900 transition-colors">
              {{ $emp->full_name }}
            </p>
            <p class="text-xs font-bold text-slate-700 truncate" style="color: {{ $empAccent }};">
              {{ $emp->designation ?? $empTheme['theme_label'] }}
            </p>
            <p class="text-[10px] text-slate-500 font-medium truncate">
              {{ $emp->department?->name ?? 'Campus Operations' }}
            </p>
            <div class="flex items-center gap-2 text-[10px] text-slate-500 font-medium pt-0.5">
              @if($emp->blood_group)
                <span class="text-rose-600 font-bold bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">
                  {{ $emp->blood_group }}
                </span>
              @endif
              <span class="font-mono text-slate-600">+91 {{ $emp->mobile ?? '9876543210' }}</span>
            </div>
          </div>
        </div>

        {{-- Card Footer --}}
        <div class="px-3 py-1.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[9px] font-bold text-slate-400">
          <span>Valid: 2026-2027</span>
          <span class="text-rose-900 group-hover:underline">Inspect &rarr;</span>
        </div>
      </a>
      @empty
      <div class="col-span-full text-center py-12 bg-white rounded-2xl border border-dashed border-slate-200">
        <p class="font-bold text-slate-700">No employees found in {{ str_replace('_', ' ', $categoryFilter) }}.</p>
      </div>
      @endforelse
    </div>
  </div>

</div>
@endsection
