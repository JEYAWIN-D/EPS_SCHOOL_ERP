@extends('layouts.admin')

@section('title', 'Classes & Timetables')

@section('content')
@php
  $authTeacherId = $authEmployeeId ?? \App\Http\Controllers\Admin\PeriodReportController::resolveAuthEmployeeId();
@endphp
<div class="space-y-6" x-data="classesDashboard()" x-init="init()">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
    <div>
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">
          Classes & Timetables
        </h1>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Academic Year {{ $currentYear->name ?? '2025-2026' }}
        </span>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Manage class sections, faculty assignments, subject handling teachers, and live daily period schedules.
      </p>
    </div>

    {{-- Top Right Actions --}}
    <div class="flex items-center gap-2.5 self-end sm:self-auto flex-shrink-0">
      @can('view academics')
      <a href="{{ route('academics.sections.manage') }}"
         class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition cursor-pointer"
         title="Manage and create class sections">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span>Manage Sections</span>
      </a>
      @endcan

      @if(\App\Http\Controllers\Admin\ClassesController::canManageTimetable(auth()->user()))
      <button @click="showHolidayModal = true; loadDeclaredHolidays()"
              class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-900 border border-indigo-200 shadow-2xs transition cursor-pointer"
              title="Declare a school holiday for a specific date">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Declare Holiday</span>
      </button>
      @endif

      <button @click="showTimeSimulator = !showTimeSimulator"
              class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold border transition cursor-pointer"
              :class="showTimeSimulator || simulatedTime !== null
                ? 'bg-amber-500 hover:bg-amber-600 text-white border-amber-600 shadow-sm'
                : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200 shadow-2xs'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="simulatedTime !== null ? 'Simulating: ' + displayClock : 'Time Simulator'"></span>
      </button>

      @if(\App\Http\Controllers\Admin\ClassesController::canManageTimetable(auth()->user()))
      <button @click="openEditTimetableModal()"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow-md transition cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span>Edit Timetable</span>
      </button>
      @else
      <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 shadow-2xs" title="You have view-only access to timetables">
        <span>👁️</span>
        <span>View-Only Timetable</span>
      </span>
      @endif
    </div>
  </div>

  {{-- Interactive Time Simulator Panel (Collapsible) --}}
  <div x-show="showTimeSimulator"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 translate-y-0"
       x-transition:leave-end="opacity-0 -translate-y-2"
       class="bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-indigo-500/10 border border-amber-300/80 rounded-2xl p-4 shadow-sm"
       style="display:none;">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Live Time Simulator</h4>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                  :class="simulatedTime === null ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-200 text-amber-900 border border-amber-300'"
                  x-text="simulatedTime === null ? 'REAL SYSTEM CLOCK (' + liveTimeString + ')' : 'SIMULATING: ' + displayClock + (simulatedDayOfWeek === 6 ? ' (Saturday)' : '')">
            </span>
          </div>
          <p class="text-[11px] text-slate-600 mt-0.5">Click any period preset below to simulate live schedule transitions across all 48 class sections.</p>
        </div>
      </div>

      {{-- Presets --}}
      <div class="flex flex-wrap items-center gap-1.5">
        <button @click="resetToLiveTime()"
                class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition shadow-sm cursor-pointer flex items-center gap-1.5"
                :class="simulatedTime === null ? 'bg-emerald-600 text-white ring-2 ring-emerald-400' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'">
          <span class="inline-block w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Live Time
        </button>
        <button @click="setTime('09:30', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '09:30' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          09:30 AM (Period I)
        </button>
        <button @click="setTime('10:10', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '10:10' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          10:10 AM (Period II)
        </button>
        <button @click="setTime('10:35', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '10:35' ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300'">
          10:35 AM (Interval)
        </button>
        <button @click="setTime('11:00', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '11:00' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          11:00 AM (Period III)
        </button>
        <button @click="setTime('11:40', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '11:40' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          11:40 AM (Period IV)
        </button>
        <button @click="setTime('12:20', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '12:20' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          12:20 PM (Period V)
        </button>
        <button @click="setTime('13:00', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '13:00' ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300'">
          01:00 PM (Lunch Break)
        </button>
        <button @click="setTime('13:40', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '13:40' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          01:40 PM (Period VI)
        </button>
        <button @click="setTime('14:05', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '14:05' ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300'">
          02:05 PM (Interval)
        </button>
        <button @click="setTime('14:30', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '14:30' ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-white text-slate-700 hover:bg-blue-50 border border-slate-200'">
          02:30 PM (Period VII)
        </button>
        <button @click="setTime('14:55', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '14:55' ? 'bg-purple-600 text-white font-black shadow-sm' : 'bg-purple-50 text-purple-900 hover:bg-purple-100 border border-purple-300'">
          02:55 PM (Diary)
        </button>
        <button @click="setTime('15:15', 1)"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="simulatedTime === '15:15' ? 'bg-slate-700 text-white font-black shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300'">
          03:15 PM (Dispersed)
        </button>
        <button @click="setTime('10:00', 6)"
                class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition shadow-sm cursor-pointer"
                :class="simulatedTime === '10:00' && simulatedDayOfWeek === 6 ? 'bg-emerald-700 text-white font-black ring-2 ring-emerald-400' : 'bg-emerald-600 text-white hover:bg-emerald-700'">
          🏆 Saturday
        </button>
      </div>
    </div>
  </div>

  {{-- ── 1. Top KPI Summary Cards (Styled exactly like user screenshot) ── --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

    {{-- Card 1: Total Standards --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-blue-glow">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
      </div>
      <div class="mt-4">
        <div class="text-3xl font-extrabold text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">12</div>
        <div class="text-xs text-slate-500 font-medium mt-0.5">Total Standards (1st–12th)</div>
      </div>
      <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500 font-medium">1st – 12th Standard</span>
        <span class="text-blue-600 font-bold">Active ✓</span>
      </div>
    </div>

    {{-- Card 2: Total Sections --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
      </div>
      <div class="mt-4">
        <div class="text-3xl font-extrabold text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $totalSections }}</div>
        <div class="text-xs text-slate-500 font-medium mt-0.5">Active Sections (ABCD)</div>
      </div>
      <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500 font-medium">Sections A, B, C, D</span>
        <span class="text-emerald-600 font-bold">4 per std ✓</span>
      </div>
    </div>

    {{-- Card 3: Total Enrolled Students --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">Capacity</span>
      </div>
      <div class="mt-4">
        <div class="text-3xl font-extrabold text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ number_format($totalStudents) }}</div>
        <div class="text-xs text-slate-500 font-medium mt-0.5">Enrolled Students</div>
      </div>
      <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-purple-600 font-semibold hover:text-purple-700 cursor-pointer">
        <a href="{{ route('students.index') }}">Student Directory →</a>
      </div>
    </div>

    {{-- Card 4: Campus Period Live Status --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-cyan-600 text-white flex items-center justify-center shadow-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-ping"></span>
          <span x-text="currentPeriod.badge_title">Live Now</span>
        </span>
      </div>
      <div class="mt-4">
        <div class="text-xl font-extrabold text-slate-900 tracking-tight leading-tight truncate" x-text="currentPeriod.title">Period In Session</div>
        <div class="text-xs text-slate-500 font-medium mt-1 flex items-center gap-1.5">
          <span x-text="currentPeriod.timing">09:15 AM – 10:00 AM</span>
          <span class="opacity-70">•</span>
          <span class="text-cyan-700 font-semibold" x-text="currentPeriod.remaining_text">45m left</span>
        </div>
      </div>
      <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-medium">
        <span class="text-slate-500" x-text="currentPeriod.day_type">Regular Day</span>
        <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded-lg font-mono font-bold" x-text="displayClock"></span>
      </div>
    </div>

    {{-- Card 5: Timetable Coverage / Saturday Tracker --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Full Week</span>
      </div>
      <div class="mt-4">
        <div class="text-3xl font-extrabold text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">100%</div>
        <div class="text-xs text-slate-500 font-medium mt-0.5">Coverage (Mon–Sat)</div>
      </div>
      <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500 font-medium">All periods assigned</span>
        <span class="text-emerald-600 font-bold">Mon–Sat ✓</span>
      </div>
    </div>

  </div>

  {{-- ── 2. Filter Bar & Search (Search at Top, Rectangle Cards in Line Below) ── --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
    
    {{-- Search Input at Top with Clear Icon Spacing --}}
    <div class="relative w-full flex items-center">
      <div class="absolute flex items-center pointer-events-none text-slate-400 z-10" style="left: 20px;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>
      <input type="text"
             x-model="searchQuery"
             placeholder="Search standard, section, subject, or class teacher..."
             style="padding-left: 52px !important; padding-right: 40px !important;"
             class="w-full h-11 bg-slate-50 hover:bg-slate-100/60 focus:bg-white border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition shadow-2xs">
      <template x-if="searchQuery">
        <button @click="searchQuery = ''" class="absolute right-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </template>
    </div>

    {{-- Grade Category Cards in a Line Below --}}
    <div class="flex items-center gap-2.5 overflow-x-auto pb-1 no-scrollbar flex-wrap">
      <button @click="filterCategory = 'all'"
              :class="filterCategory === 'all'
                ? 'bg-blue-600 text-white font-bold shadow-xs'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200'"
              class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-shrink-0">
        All Standards (1st–12th)
      </button>

      <button @click="filterCategory = 'primary'"
              :class="filterCategory === 'primary'
                ? 'bg-blue-600 text-white font-bold shadow-xs'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200'"
              class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-shrink-0">
        Primary (1st–5th)
      </button>

      <button @click="filterCategory = 'middle'"
              :class="filterCategory === 'middle'
                ? 'bg-blue-600 text-white font-bold shadow-xs'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200'"
              class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-shrink-0">
        Middle School (6th–8th)
      </button>

      <button @click="filterCategory = 'secondary'"
              :class="filterCategory === 'secondary'
                ? 'bg-blue-600 text-white font-bold shadow-xs'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200'"
              class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 flex-shrink-0">
        <span>Secondary (9th & 10th)</span>
        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
      </button>

      <button @click="filterCategory = 'higher-secondary'"
              :class="filterCategory === 'higher-secondary'
                ? 'bg-blue-600 text-white font-bold shadow-xs'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200'"
              class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 flex-shrink-0">
        <span>Higher Secondary (11th & 12th)</span>
        <span class="w-2 h-2 rounded-full bg-purple-400"></span>
      </button>

      <button @click="filterCategory = 'pre-primary'"
              :class="filterCategory === 'pre-primary'
                ? 'bg-blue-600 text-white font-bold shadow-xs'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200'"
              class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition cursor-pointer flex-shrink-0">
        Pre-Primary (KG)
      </button>
    </div>

  </div>

@push('head')
<style>
.class-badge-primary { background: linear-gradient(135deg, #059669, #0d9488) !important; color: #ffffff !important; }
.class-badge-middle { background: linear-gradient(135deg, #4f46e5, #2563eb) !important; color: #ffffff !important; }
.class-badge-secondary { background: linear-gradient(135deg, #0284c7, #1d4ed8) !important; color: #ffffff !important; }
.class-badge-higher { background: linear-gradient(135deg, #7c3aed, #4338ca) !important; color: #ffffff !important; }
.class-badge-pre { background: linear-gradient(135deg, #ea580c, #d97706) !important; color: #ffffff !important; }

.live-card-active { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important; color: #ffffff !important; border: 1px solid rgba(99, 102, 241, 0.5) !important; }
.live-card-saturday { background: linear-gradient(135deg, #064e3b 0%, #042f2e 100%) !important; color: #ffffff !important; border: 1px solid rgba(16, 185, 129, 0.5) !important; }
.live-card-break { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important; color: #ffffff !important; border: 1px solid rgba(148, 163, 184, 0.3) !important; }
.live-card-idle { background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important; color: #ffffff !important; border: 1px solid #475569 !important; }
</style>
@endpush

  {{-- ── 3. Ultra-Premium Class Cards Grid (1st to 12th Standard) ── --}}
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

    <template x-for="cls in filteredClasses" :key="cls.id">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden group hover:border-blue-400"
           x-data="{
             selectedSectionName: 'A',
             get currentSection() {
               return cls.sections.find(s => s.name === this.selectedSectionName) || cls.sections[0] || {};
             },
             get livePeriodForSection() {
               return $data.getLivePeriodForSection(cls, this.currentSection);
             }
           }">

        {{-- Card Header --}}
        <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-blue-50/40">
          <div class="flex items-start justify-between gap-3">
            {{-- Class Badge & Title --}}
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-extrabold text-white text-base shadow-md transition duration-300 group-hover:scale-105 flex-shrink-0"
                   :class="cls.numeric_value >= 11 ? 'class-badge-higher' :
                          (cls.numeric_value >= 9 ? 'class-badge-secondary' :
                          (cls.numeric_value >= 6 ? 'class-badge-middle' :
                          (cls.numeric_value >= 1 ? 'class-badge-primary' : 'class-badge-pre')))">
                <span class="text-white font-black text-base" x-text="cls.name || cls.numeric_value"></span>
              </div>
              <div>
                <h3 class="text-base font-extrabold text-slate-900 group-hover:text-blue-600 transition tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;"
                    x-text="cls.display_name"></h3>
                <div class="flex items-center gap-2 mt-0.5">
                  <span class="text-[11px] font-semibold text-slate-500" x-text="'Standard ' + cls.name"></span>
                  <span class="text-slate-300">•</span>
                  <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md"
                        :class="cls.category === 'higher-secondary' ? 'bg-purple-100 text-purple-800' :
                               (cls.category === 'secondary' ? 'bg-blue-100 text-blue-800' :
                               (cls.category === 'middle' ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800'))"
                        x-text="cls.category.replace('-', ' ')">
                  </span>
                </div>
              </div>
            </div>

            {{-- Students Count --}}
            <div class="text-right">
              <span class="text-xs font-extrabold text-slate-900" x-text="cls.total_students + ' Students'"></span>
              <p class="text-[10px] text-slate-400">4 Sections (A–D)</p>
            </div>
          </div>

          {{-- Section Selection Cards (A, B, C, D) --}}
          <div class="mt-3.5 pt-3 border-t border-slate-100/90 flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5">
              <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mr-1">Section:</span>
              <template x-for="sec in (cls.sections || [{name:'A'},{name:'B'},{name:'C'},{name:'D'}])" :key="sec.id || sec.name">
                <button @click="selectedSectionName = sec.name"
                        type="button"
                        class="w-8 h-8 rounded-xl font-extrabold text-xs transition-all duration-200 flex items-center justify-center border shadow-sm cursor-pointer"
                        :class="selectedSectionName === sec.name
                          ? 'bg-blue-600 text-white border-blue-600 shadow-blue-500/30 ring-2 ring-blue-400 scale-105 font-black'
                          : 'bg-slate-50 text-slate-700 border-slate-200/90 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700'">
                  <span x-text="sec.name"></span>
                </button>
              </template>
            </div>
            
            {{-- Selected Section Student Count Badge --}}
            <div class="text-right">
              <span class="text-[11px] font-extrabold text-blue-700 bg-blue-50/90 px-2.5 py-1 rounded-xl border border-blue-200/60 inline-flex items-center gap-1.5 shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                <span x-text="'Sec ' + currentSection.name + ': ' + (currentSection.student_count || 40) + ' Students'"></span>
              </span>
            </div>
          </div>

          {{-- Class Teacher Line --}}
          <div class="mt-2.5 pt-2.5 border-t border-slate-100/80 flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              Class Teacher (Sec <span x-text="currentSection.name"></span>):
            </span>
            <span class="font-extrabold text-slate-900 bg-slate-100/80 px-2 py-0.5 rounded-md" x-text="currentSection.class_teacher || 'Senior Faculty'"></span>
          </div>
        </div>

        {{-- Card Body --}}
        <div class="p-5 space-y-4">

          {{-- Live Period Glow Box --}}
          <div class="rounded-2xl p-4 transition-all duration-300 shadow-md relative overflow-hidden"
               :class="livePeriodForSection.is_active_period
                 ? (livePeriodForSection.is_saturday ? 'live-card-saturday' : 'live-card-active')
                 : (livePeriodForSection.is_break ? 'live-card-break' : 'live-card-idle')">

            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                        :class="livePeriodForSection.is_active_period ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                        :class="livePeriodForSection.is_active_period ? 'bg-emerald-500' : 'bg-slate-500'"></span>
                </span>
                <span class="text-[11px] font-extrabold uppercase tracking-wider"
                      :style="livePeriodForSection.is_active_period ? 'color: #34d399 !important;' : 'color: #94a3b8 !important;'"
                      x-text="livePeriodForSection.badge_text">LIVE NOW</span>
              </div>
              <span class="text-[11px] font-mono font-bold" style="color: #e2e8f0 !important;" x-text="livePeriodForSection.timing">09:15 AM – 10:00 AM</span>
            </div>

            <div class="mt-2.5">
              <h4 class="text-lg font-black tracking-tight truncate" style="color: #ffffff !important;" x-text="livePeriodForSection.subject_name">Mathematics</h4>
              <div class="flex items-center justify-between text-xs mt-1" style="color: #cbd5e1 !important;">
                <span class="truncate font-medium" x-text="livePeriodForSection.teacher_name">Faculty In-charge</span>
                <span class="font-mono text-[11px] px-2 py-0.5 rounded font-bold" style="background: rgba(255,255,255,0.18); color: #ffffff !important;" x-text="currentSection.room">Room 1-A</span>
              </div>
            </div>

            {{-- Progress Bar - only for live periods --}}
            <template x-if="livePeriodForSection.is_active_period && livePeriodForSection.progress_percent !== undefined">
              <div class="mt-3">
                <div class="w-full rounded-full h-1.5 overflow-hidden" style="background: rgba(255,255,255,0.25);">
                  <div class="h-1.5 rounded-full transition-all duration-500"
                       style="background: linear-gradient(90deg, #34d399, #38bdf8);"
                       :style="'width: ' + livePeriodForSection.progress_percent + '%'"></div>
                </div>
                <div class="flex justify-between text-[10px] mt-1 font-mono" style="color: #cbd5e1 !important;">
                  <span x-text="livePeriodForSection.remaining_mins + ' mins remaining'"></span>
                  <span x-text="livePeriodForSection.progress_percent + '% elapsed'"></span>
                </div>
              </div>
            </template>

            {{-- Upcoming Period Indicator (Without 'Next:' label) --}}
            <template x-if="livePeriodForSection.next_period_text">
              <div class="mt-2.5 pt-2.5 text-[11px] flex items-center gap-1.5 font-medium" style="border-top: 1px solid rgba(255,255,255,0.15); color: #cbd5e1 !important;">
                <svg class="w-3.5 h-3.5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                <span class="truncate font-semibold text-white" x-text="livePeriodForSection.next_period_text"></span>
              </div>
            </template>
          </div>

          {{-- Today's 8-Period Preview --}}
          <div>
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 mb-1.5">
              <span>Today's Schedule (Periods 1 to 8):</span>
              <span class="text-blue-600 font-semibold" x-text="simulatedDayOfWeek === 6 ? 'Saturday' : 'Regular'"></span>
            </div>
            <div class="grid grid-cols-4 sm:grid-cols-8 gap-1.5">
              <template x-for="slot in getTodayPeriods(cls, currentSection)" :key="slot.period">
                <div class="p-1.5 rounded-xl text-center border transition"
                     :class="slot.is_current
                       ? 'bg-blue-600 text-white border-blue-700 shadow-sm ring-2 ring-blue-400 ring-offset-1 font-extrabold'
                       : 'bg-slate-50 text-slate-700 border-slate-200 font-semibold'"
                     :title="slot.full_title">
                  <div class="text-[9px] font-bold opacity-80" x-text="slot.period_label || ('P' + slot.period)"></div>
                  <div class="text-[10px] truncate mt-0.5" x-text="slot.abbr"></div>
                </div>
              </template>
            </div>
          </div>
        </div>

        {{-- Card Footer: Single Primary Action 'View Timetable' --}}
        <div class="p-4 bg-slate-50/80 border-t border-slate-100 flex items-center">
          <button @click="openTimetableModal(cls, currentSection)"
                  class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-extrabold bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow-md transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            View Timetable
          </button>
        </div>
      </div>
    </template>
  </div>

  {{-- Floating Toast Notification Popper --}}
  <template x-teleport="body">
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
         class="fixed top-6 right-6 z-[999999] flex items-center gap-3 px-4 py-3 rounded-2xl shadow-2xl border border-slate-700/80 bg-slate-900 text-white"
         style="display: none;">
      <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
           :class="toast.type === 'error' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'">
        <template x-if="toast.type === 'error'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </template>
        <template x-if="toast.type !== 'error'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </template>
      </div>
      <div>
        <p class="text-xs font-extrabold tracking-wide" x-text="toast.message"></p>
      </div>
      <button @click="toast.show = false" class="text-slate-400 hover:text-white ml-2 text-xs font-bold transition cursor-pointer">✕</button>
    </div>
  </template>

  {{-- Declare Holiday Modal --}}
  <template x-teleport="body">
    <div x-show="showHolidayModal"
         @keydown.window.escape="showHolidayModal = false"
         @click.self="showHolidayModal = false"
         class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
         style="display:none; background:rgba(15, 23, 42, 0.65);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

      <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh]">
        {{-- Header --}}
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 via-blue-600 to-indigo-700 text-white flex items-center justify-between shadow-md">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center font-bold text-lg border border-white/20">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <h3 class="font-extrabold text-base tracking-tight text-white">Declare School Holiday</h3>
              <p class="text-xs text-indigo-100 mt-0.5">Automatically sets Student & Staff attendance on the declared date</p>
            </div>
          </div>
          <button @click="showHolidayModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
            ✕
          </button>
        </div>

        {{-- Body --}}
        <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-slate-50/50">
          {{-- Form Card --}}
          <form @submit.prevent="submitDeclareHoliday()" class="bg-white border border-slate-200/90 rounded-2xl p-5 space-y-4 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h4 class="text-xs font-black uppercase tracking-wider text-indigo-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                <span>Declare New Holiday</span>
              </h4>
              <span class="text-[10px] font-bold text-slate-400">All Class Sections & Staff</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Holiday Date *</label>
                <input type="date" x-model="holidayForm.date" required
                       class="input input-sm w-full bg-slate-50 border-slate-200 focus:bg-white focus:border-indigo-500 rounded-xl font-bold text-xs transition">
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Holiday Name / Title *</label>
                <input type="text" x-model="holidayForm.name" placeholder="e.g. Local Festival, Rain Holiday" required
                       class="input input-sm w-full bg-slate-50 border-slate-200 focus:bg-white focus:border-indigo-500 rounded-xl font-medium text-xs transition">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Holiday Type</label>
                <select x-model="holidayForm.type" class="select select-sm w-full bg-slate-50 border-slate-200 focus:bg-white focus:border-indigo-500 rounded-xl text-xs font-semibold">
                  <option value="school">School Holiday / Festival</option>
                  <option value="national">National Holiday</option>
                  <option value="state">State / Regional Holiday</option>
                  <option value="optional">Optional / Special Holiday</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Remarks / Notes (Optional)</label>
                <input type="text" x-model="holidayForm.description" placeholder="Optional remarks..."
                       class="input input-sm w-full bg-slate-50 border-slate-200 focus:bg-white focus:border-indigo-500 rounded-xl text-xs">
              </div>
            </div>

            <div class="pt-2">
              <button type="submit" :disabled="isSavingHoliday"
                      class="w-full btn bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold py-2.5 rounded-xl shadow-sm transition cursor-pointer flex items-center justify-center gap-2 text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span x-text="isSavingHoliday ? 'Saving Holiday...' : 'Declare Holiday Now'"></span>
              </button>
            </div>
          </form>

          {{-- Declared Holidays List --}}
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-black uppercase tracking-wider text-slate-600">Active Declared Holidays</h4>
              <span class="text-[11px] font-extrabold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200/60" x-text="declaredHolidays.length + ' Recorded'"></span>
            </div>

            <template x-if="declaredHolidays.length === 0">
              <div class="text-center py-8 text-slate-400 text-xs italic bg-white rounded-2xl border border-slate-200 shadow-2xs">
                No school holidays declared yet. Fill the form above to add a holiday date.
              </div>
            </template>

            <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
              <template x-for="h in declaredHolidays" :key="h.id">
                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-indigo-300 transition">
                  <div class="flex items-center gap-3">
                    <div class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-900 font-mono font-extrabold text-xs text-center border border-indigo-200/80">
                      <span x-text="h.formatted_date"></span>
                    </div>
                    <div>
                      <h5 class="font-bold text-xs text-slate-900" x-text="h.name"></h5>
                      <p class="text-[10px] text-slate-400 font-medium capitalize" x-text="(h.type || 'Holiday').replace('_', ' ') + (h.description ? ' • ' + h.description : '')"></p>
                    </div>
                  </div>
                  <button type="button" @click="removeHoliday(h.id)" class="text-xs text-rose-600 hover:text-rose-800 font-bold px-3 py-1.5 rounded-xl hover:bg-rose-50 border border-rose-200 transition cursor-pointer">
                    Remove
                  </button>
                </div>
              </template>
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between gap-3">
          <button type="button" @click="showHolidayModal = false"
                  class="px-5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs transition cursor-pointer">
            Close
          </button>
          <button type="button" @click="saveAndCloseHoliday()" :disabled="isSavingHoliday"
                  class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-sm hover:shadow-md transition cursor-pointer flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span x-text="isSavingHoliday ? 'Saving...' : 'Save & Close'"></span>
          </button>
        </div>
      </div>
    </div>
  </template>

  <template x-teleport="body">
    <div x-show="modalOpen"
         @keydown.window.escape="modalOpen = false"
         @click.self="modalOpen = false"
         class="fixed inset-0 z-[9999] flex items-center justify-center"
         style="display:none; padding:28px 20px; box-sizing:border-box; background:rgba(15, 23, 42, 0.65);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-98"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-98">

      {{-- Modal panel --}}
      <div class="bg-white"
           style="max-width:1200px; max-height:calc(100vh - 56px); min-height:0; overflow:hidden; display:flex; flex-direction:column; width:100%; border-radius:20px; box-shadow:0 25px 60px -15px rgba(0,0,0,0.3), 0 0 0 1px rgba(0,0,0,0.06);">

        {{-- ── 1. CLEAN MODERN LIGHT HEADER ── --}}
        <div class="px-6 py-3.5 flex-shrink-0 flex items-center justify-between gap-4 bg-white border-b border-slate-200/80">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 font-black text-sm tracking-wide"
                 x-text="modalClass?.name || 'Class'">
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight"
                    x-text="modalClass?.display_name || ''"></h3>
                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 uppercase tracking-wide">
                  Weekly Timetable
                </span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5 font-medium">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                7 Periods + Diary &nbsp;•&nbsp; 09:10 AM – 03:00 PM
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            @can('edit academics')
            {{-- Declare Holiday shortcut (Admin Only) --}}
            <button @click="showHolidayModal = true; loadDeclaredHolidays()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300/80 shadow-2xs transition cursor-pointer"
                    title="Declare a school holiday for a specific date">
              <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span>Declare Holiday</span>
            </button>
            @endcan
            {{-- PDF --}}
            <a :href="'{{ url('classes') }}/' + (modalClass?.id || '') + '/timetable/pdf?section_id=' + (modalSection?.id || '')"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-xs transition">
              <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              PDF
            </a>
            {{-- ✕ Close Button --}}
            <button @click="modalOpen = false"
                    class="w-8 h-8 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition shadow-md hover:scale-105 cursor-pointer"
                    title="Close (Esc)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        {{-- ── 2. SECTION & TEACHER BAR (CLEAN LIGHT THEME) ── --}}
        <div class="px-6 py-2.5 flex-shrink-0 flex items-center justify-between flex-wrap gap-3 bg-slate-50/90 border-b border-slate-200/80">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Section</span>
            <template x-for="sec in (modalClass?.sections || [])" :key="sec.id">
              <button @click="modalSection = sec"
                      class="px-3.5 py-1 rounded-lg text-xs font-bold transition-all"
                      :class="modalSection?.id === sec.id
                        ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/25'
                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100 hover:text-slate-900'">
                <span x-text="'Sec ' + sec.name"></span>
              </button>
            </template>
          </div>
          <div class="text-xs text-slate-600 flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-slate-400 font-medium">Class Teacher:</span>
            <strong class="text-slate-800 font-bold" x-text="modalSection?.class_teacher"></strong>
            <span class="text-slate-300">•</span>
            <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 font-mono text-[11px] font-semibold border border-blue-200/60" x-text="modalSection?.room"></span>
          </div>
        </div>

        {{-- ── 3. DAY TABS BAR ── --}}
        <div class="px-6 py-2 flex-shrink-0 flex items-center gap-1.5 flex-wrap bg-white border-b border-slate-200">
          <button @click="modalDay = 0"
                  :class="modalDay === 0 ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                  class="px-3.5 py-1 rounded-full text-xs font-semibold transition">All Week</button>
          <template x-for="[num, label] in [[1,'Mon'],[2,'Tue'],[3,'Wed'],[4,'Thu'],[5,'Fri']]" :key="num">
            <button @click="modalDay = num"
                    :class="modalDay === num ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                    class="px-3.5 py-1 rounded-full text-xs font-semibold transition"
                    x-text="label"></button>
          </template>
          <button @click="modalDay = 6"
                  :class="modalDay === 6 ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100'"
                  class="px-3.5 py-1 rounded-full text-xs font-semibold transition flex items-center gap-1.5">
            Saturday <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded-full" :class="modalDay === 6 ? 'bg-emerald-700 text-white' : 'bg-emerald-200 text-emerald-900'">CLUBS</span>
          </button>
        </div>

        {{-- ── 4. FIXED PERIOD HEADERS (DEDICATED ROW - NO OVERLAPPING!) ── --}}
        <div class="px-6 pt-3 pb-2 bg-slate-50/80 border-b border-slate-200/80 flex-shrink-0">
          <div style="min-width: 900px; display: grid; grid-template-columns: 92px repeat(8, minmax(0, 1fr)); gap: 10px; align-items: center;">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 text-center">
              Day
            </div>
            <template x-for="[p, times, label] in [
              [1, '09:10–09:50', 'Period I'],
              [2, '09:50–10:30', 'Period II'],
              [3, '10:40–11:20', 'Period III'],
              [4, '11:20–12:00', 'Period IV'],
              [5, '12:00–12:40', 'Period V'],
              [6, '01:20–02:00', 'Period VI'],
              [7, '02:10–02:50', 'Period VII'],
              [8, '02:50–03:00', 'Diary']
            ]" :key="p">
              <div class="bg-white border border-slate-200/90 rounded-xl py-2 px-1 text-center shadow-xs flex flex-col items-center justify-center">
                <span class="text-xs font-black text-blue-600 tracking-tight" x-text="label"></span>
                <span class="text-[9.5px] text-slate-400 font-medium tracking-tight mt-0.5" x-text="times"></span>
              </div>
            </template>
          </div>
        </div>

        {{-- ── 5. SCROLLABLE TIMETABLE CARDS (SPACIOUS & CLEAN) ── --}}
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/30" style="min-height:0;">
          <div style="min-width: 900px; display: flex; flex-direction: column; gap: 10px;">
            <template x-for="d in getModalDays()" :key="d.dayNum">
              <div style="display: grid; grid-template-columns: 92px repeat(8, minmax(0, 1fr)); gap: 10px; align-items: stretch;">
                
                {{-- Day Title Card --}}
                <div class="rounded-xl border flex flex-col items-center justify-center text-center p-2.5 shadow-xs"
                     style="height: 86px;"
                     :class="isModalDayHoliday(d.dayNum) ? 'bg-amber-50 border-amber-300 text-amber-900' : (d.dayNum === 6 ? 'bg-emerald-50/90 border-emerald-200 text-emerald-950' : 'bg-white border-slate-200 text-slate-800')">
                  <span class="font-extrabold text-xs tracking-tight uppercase" x-text="d.name"></span>
                  <template x-if="isModalDayHoliday(d.dayNum)">
                    <span class="text-[8.5px] font-black bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full mt-1.5 tracking-wider uppercase">HOLIDAY</span>
                  </template>
                  <template x-if="!isModalDayHoliday(d.dayNum) && d.dayNum === 6">
                    <span class="text-[8.5px] font-black bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded-full mt-1.5 tracking-wider uppercase">CLUBS</span>
                  </template>
                </div>

                {{-- Full-row Holiday Banner if Day is Holiday in View Modal --}}
                <template x-if="isModalDayHoliday(d.dayNum)">
                  <div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 px-6 py-3 flex items-center justify-between shadow-xs"
                       style="grid-column: 2 / span 8; height: 86px; min-width: 0;">
                    <div class="flex items-center gap-3.5">
                      <div class="w-10 h-10 rounded-xl bg-amber-200/90 text-amber-900 flex items-center justify-center font-bold text-xl shadow-xs flex-shrink-0">
                        🏖️
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <h4 class="font-extrabold text-sm text-amber-950" x-text="d.name + ' — School Holiday'"></h4>
                          <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-200/70 text-amber-900 uppercase tracking-wider">No Classes</span>
                        </div>
                        <p class="text-xs text-amber-800/80 mt-0.5 font-medium">Entire day marked as holiday. All 8 academic periods suspended.</p>
                      </div>
                    </div>
                  </div>
                </template>

                {{-- 8 Period Cards when not holiday --}}
                <template x-if="!isModalDayHoliday(d.dayNum)">
                  <template x-for="p in [1,2,3,4,5,6,7,8]" :key="p">
                    <div class="rounded-xl border transition-all p-3 flex flex-col justify-between overflow-hidden"
                         style="height: 86px; max-height: 86px;"
                         :class="isMyPeriod(getPeriodEntry(d.dayNum, p))
                           ? 'bg-blue-50/70 hover:bg-blue-100/70 border-blue-400 ring-2 ring-blue-400/40 shadow-xs cursor-pointer'
                           : (d.dayNum === 6
                             ? 'bg-emerald-50/40 hover:bg-emerald-50 border-emerald-200 cursor-default'
                             : (getPeriodEntry(d.dayNum, p)?.subject_name
                                 ? 'bg-white hover:bg-slate-50/80 border-slate-200/90 shadow-xs cursor-default'
                                 : (canManageTimetable
                                     ? 'bg-slate-50/80 hover:bg-blue-50/80 border-dashed border-slate-300 hover:border-blue-400 items-center justify-center cursor-pointer transition shadow-2xs'
                                     : 'bg-slate-100/40 border-dashed border-slate-200 items-center justify-center cursor-default')))"
                         @click="isMyPeriod(getPeriodEntry(d.dayNum, p))
                           ? openTeacherPeriodReportModal(getPeriodEntry(d.dayNum, p), d.name)
                           : (!getPeriodEntry(d.dayNum, p)?.subject_name && canManageTimetable
                               ? onFreePeriodClick(d.dayNum, p)
                               : null)">

                      <template x-if="getPeriodEntry(d.dayNum, p)?.subject_name">
                        <div class="flex flex-col justify-between h-full w-full">
                          <div>
                            <div class="flex items-center justify-between gap-1">
                              <h4 class="font-extrabold text-xs leading-snug truncate"
                                  :class="d.dayNum === 6 ? 'text-emerald-950' : 'text-slate-900'"
                                  x-text="getPeriodEntry(d.dayNum, p)?.subject_name"
                                  :title="getPeriodEntry(d.dayNum, p)?.subject_name"></h4>
                              <template x-if="isMyPeriod(getPeriodEntry(d.dayNum, p))">
                                <span class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.2 rounded-full bg-blue-600 text-white shrink-0 shadow-2xs">
                                  Your Period
                                </span>
                              </template>
                            </div>
                          </div>
                          <div class="mt-auto pt-1 flex items-center justify-between">
                            <p class="text-[10.5px] truncate font-medium"
                                :class="isMyPeriod(getPeriodEntry(d.dayNum, p)) ? 'text-blue-900 font-bold' : 'text-slate-500'"
                                x-text="getPeriodEntry(d.dayNum, p)?.teacher_name || 'Faculty'"
                                :title="getPeriodEntry(d.dayNum, p)?.teacher_name"></p>
                            <template x-if="getPeriodEntry(d.dayNum, p)?.period_type === 'substitution'">
                               <span class="text-[8.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 flex-shrink-0">
                                 🔄 Sub
                               </span>
                            </template>
                            <template x-if="isMyPeriod(getPeriodEntry(d.dayNum, p))">
                              <span class="text-[9px] font-bold text-blue-700 underline shrink-0">
                                Log Report &rarr;
                              </span>
                            </template>
                          </div>
                        </div>
                      </template>

                      <template x-if="!getPeriodEntry(d.dayNum, p)?.subject_name">
                        <div class="text-center space-y-0.5">
                          <span class="text-[11px] font-semibold transition"
                                :class="canManageTimetable ? 'text-blue-600 hover:text-blue-800' : 'text-slate-400 italic'"
                                x-text="canManageTimetable ? '+ Assign Period' : 'Free Period'"></span>
                          <template x-if="canManageTimetable">
                            <p class="text-[9px] text-slate-400">Click to assign staff</p>
                          </template>
                        </div>
                      </template>
                    </div>
                  </template>
                </template>

              </div>
            </template>
          </div>
        </div>

        {{-- ── 6. FOOTER ── --}}
        <div class="px-6 py-2.5 flex-shrink-0 flex items-center justify-between bg-white border-t border-slate-200">
          <p class="text-[11px] text-slate-500 font-medium">🔔 &nbsp;Period I 09:10 · Interval 10:30 · Lunch 12:40 · Interval 02:00 · Diary 02:50 · Dispersal 03:00 PM</p>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">DASA EduERP</p>
        </div>

      </div>
    </div>
  </template>

  {{-- ── 5. Full Interactive "Edit Timetable" Modal (Teleported to Body) ── --}}
  <template x-teleport="body">
    <div x-show="editModalOpen"
         @keydown.window.escape="confirmCloseEditModal()"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-5"
         style="display:none; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(4px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

      {{-- Modal Panel --}}
      <div class="bg-white flex flex-col w-full max-w-6xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
           style="max-height: calc(100vh - 40px); min-height: 0;"
           @click.stop>

        {{-- ── Modal Header ── --}}
        <div class="px-6 py-3.5 flex-shrink-0 flex items-center justify-between gap-4 bg-white border-b border-slate-200">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-sm font-bold text-base">
              ✏️
            </div>
            <div class="flex items-center gap-2.5">
              <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Timetable</h3>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200/70 uppercase tracking-wider">
                Live Editor
              </span>
            </div>
          </div>

          <button type="button" @click="confirmCloseEditModal()"
                  class="w-8 h-8 rounded-full bg-slate-100 hover:bg-red-500 hover:text-white text-slate-500 flex items-center justify-center transition shadow-xs cursor-pointer"
                  title="Close (Esc)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        {{-- ── Controls Bar: Class & Section, Day, Holiday Action ── --}}
        <div class="px-6 py-3 bg-slate-50/90 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3 flex-shrink-0">
          <div class="flex items-center gap-3 flex-wrap">
            {{-- Class & Section Dropdown (Searchable Picker) --}}
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold text-slate-700">Class & Section:</label>
              <div class="relative" x-data="{ openDropdown: false, classFilter: '' }" @click.outside="openDropdown = false">
                <button type="button" @click="openDropdown = !openDropdown"
                        class="h-9 px-3 min-w-[240px] bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 shadow-xs flex items-center justify-between gap-2 hover:border-blue-400 transition cursor-pointer">
                  <span class="truncate font-bold" x-text="getSelectedClassSectionLabel()"></span>
                  <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openDropdown"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute left-0 top-10 z-[100] w-72 bg-white border border-slate-200 rounded-xl shadow-xl p-2"
                     style="display:none;">
                  <input type="text" x-model="classFilter" placeholder="Search class or section..."
                         class="w-full px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg mb-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden"
                         @click.stop>
                  <div class="max-h-60 overflow-y-auto space-y-1">
                    <template x-for="opt in getFilteredClassSectionOptions(classFilter)" :key="opt.key">
                      <button type="button"
                              @click="onClassSectionPicked(opt.classId, opt.sectionId); openDropdown = false;"
                              class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-50 hover:text-blue-700 flex items-center justify-between transition cursor-pointer"
                              :class="editSelectedClassId === opt.classId && editSelectedSectionId === opt.sectionId ? 'bg-blue-100 text-blue-800 font-bold' : 'text-slate-700'">
                        <span x-text="opt.label"></span>
                        <span class="text-[10px] text-slate-400" x-text="opt.teacher ? opt.teacher.split(' ')[0] : ''"></span>
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            {{-- Day Dropdown Selector --}}
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold text-slate-700">Day:</label>
              <select :value="editDay"
                      @change="onDayChangePicked(parseInt($event.target.value, 10))"
                      class="h-9 pl-3 pr-8 min-w-[125px] bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 shadow-xs cursor-pointer focus:ring-2 focus:ring-blue-500">
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thursday</option>
                <option value="5">Friday</option>
                <option value="6">Saturday</option>
              </select>
            </div>

            {{-- Mark as Holiday / Undo Holiday Button --}}
            <div>
              <template x-if="!isDayHoliday(editDay)">
                <button type="button" @click="toggleDayHoliday(editDay)"
                        class="h-9 inline-flex items-center gap-1.5 px-3.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition shadow-xs cursor-pointer">
                  <span>🏖️</span>
                  <span>Mark as Holiday</span>
                </button>
              </template>
              <template x-if="isDayHoliday(editDay)">
                <button type="button" @click="toggleDayHoliday(editDay)"
                        class="h-9 inline-flex items-center gap-1.5 px-3.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-xs cursor-pointer">
                  <span>↩️</span>
                  <span>Undo Holiday (Restore Classes)</span>
                </button>
              </template>
            </div>
          </div>

          {{-- Active Section Info Card --}}
          <div class="text-right">
            <div class="text-xs font-extrabold text-blue-700 bg-blue-50 px-3 py-1 rounded-lg border border-blue-200/60 inline-flex items-center gap-1.5"
                 x-text="getSelectedClassSectionLabel()"></div>
            <div class="text-[11px] text-slate-500 mt-0.5 font-medium">
              Class Teacher: <strong class="text-slate-700" x-text="editSection?.class_teacher || 'Not Assigned'"></strong>
              &nbsp;•&nbsp; Room: <strong class="text-slate-700" x-text="editSection?.room || ('Room ' + (editClass?.name || '1') + '-' + (editSection?.name || 'A'))"></strong>
            </div>
          </div>
        </div>

        {{-- ── Quick Day Tabs Bar (Monday to Saturday) ── --}}
        <div class="px-6 py-2 bg-white border-b border-slate-200 flex items-center justify-between flex-wrap gap-2 flex-shrink-0">
          <div class="flex items-center gap-1.5 flex-wrap">
            <template x-for="[num, label] in [[1,'Monday'],[2,'Tuesday'],[3,'Wednesday'],[4,'Thursday'],[5,'Friday'],[6,'Saturday']]" :key="num">
              <button type="button" @click="onDayChangePicked(num)"
                      :class="editDay === num
                        ? (isDayHoliday(num) ? 'bg-amber-600 text-white font-bold shadow-sm' : 'bg-blue-600 text-white font-bold shadow-sm')
                        : (isDayHoliday(num) ? 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')"
                      class="px-3.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer flex items-center gap-1">
                <span x-text="label"></span>
                <span x-show="isDayHoliday(num)" class="text-[10px]">🏖️</span>
              </button>
            </template>
          </div>
          <div class="text-[11px] text-slate-400 font-medium">
            💡 Timetable timings follow client schedule: Period I (09:10) to Period VII (02:50) + Diary (02:50–03:00 PM).
          </div>
        </div>

        {{-- ── Scrollable Timetable Content Area (Internal Scroll) ── --}}
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/40" style="min-height:0;">

          {{-- 1. Loading State --}}
          <div x-show="editTimetableLoading" class="flex flex-col items-center justify-center py-20 text-center space-y-3">
            <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm font-extrabold text-slate-800">Loading timetable...</p>
            <p class="text-xs text-slate-400" x-text="'Fetching schedule for ' + getSelectedClassSectionLabel()"></p>
          </div>

          {{-- 2. Error State --}}
          <div x-show="!editTimetableLoading && editTimetableError" class="rounded-2xl border border-rose-200 bg-rose-50/80 p-8 text-center space-y-3 my-4">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto text-xl font-bold">⚠️</div>
            <h4 class="text-sm font-extrabold text-rose-900">Unable to load timetable. Please try again.</h4>
            <p class="text-xs text-rose-700" x-text="editTimetableError"></p>
            <button type="button" @click="loadTimetableForActiveSelection()"
                    class="mt-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
              🔄 Retry
            </button>
          </div>

          {{-- 3. Holiday State --}}
          <div x-show="!editTimetableLoading && !editTimetableError && isDayHoliday(editDay)"
               class="rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 p-8 text-center space-y-3 my-4 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-amber-200/90 text-amber-900 flex items-center justify-center mx-auto text-3xl shadow-xs">
              🏖️
            </div>
            <div>
              <h4 class="font-extrabold text-base text-amber-950" x-text="getDayName(editDay) + ' — School Holiday'"></h4>
              <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-extrabold bg-amber-200 text-amber-900 uppercase tracking-wider mt-1">No Academic Classes</span>
            </div>
            <p class="text-xs text-amber-800/80 max-w-md mx-auto font-medium">
              Entire day is marked as a school holiday. Regular period assignments and timetable editing are suspended for this day.
            </p>
            <div class="pt-2">
              <button type="button" @click="toggleDayHoliday(editDay)"
                      class="px-5 py-2.5 rounded-xl text-xs font-bold bg-white text-amber-950 border border-amber-300 hover:bg-amber-100 shadow-xs transition cursor-pointer">
                <span>↩️</span> Undo Holiday (Restore Classes)
              </button>
            </div>
          </div>

          {{-- 4. Empty State --}}
          <div x-show="!editTimetableLoading && !editTimetableError && !isDayHoliday(editDay) && activeDayPeriods.length === 0"
               class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 space-y-3 my-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl font-bold">📅</div>
            <h4 class="text-sm font-extrabold text-slate-700">No timetable configured for this class and day.</h4>
            <p class="text-xs text-slate-400">Initialize standard client period timings (Period I–VII + Diary) to begin editing.</p>
            <button type="button" @click="initializeDefaultDayPeriods()"
                    class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
              + Initialize Standard Schedule
            </button>
          </div>

          {{-- 5. Dynamic Period Cards (Period 1 to Period 7 + Diary) --}}
          <div x-show="!editTimetableLoading && !editTimetableError && !isDayHoliday(editDay) && activeDayPeriods.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <template x-for="(period, idx) in activeDayPeriods" :key="period.period_number">
                <div class="rounded-2xl p-4 shadow-xs transition-all flex flex-col justify-between space-y-3 min-w-0 overflow-hidden"
                     :class="hasConsecutiveTeacherConflict(idx) ? 'bg-rose-50/30 border border-rose-400 ring-2 ring-rose-200 shadow-rose-100' : 'bg-white border border-slate-200 hover:border-blue-300 hover:shadow-md'">
                  
                  {{-- Period Card Header --}}
                  <div>
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2 mb-3">
                      <div>
                        <span class="text-xs font-black tracking-tight"
                              :class="period.period_number === 8 ? 'text-purple-600' : 'text-blue-600'"
                              x-text="period.period_label"></span>
                        <div class="text-[10px] text-slate-400 font-semibold mt-0.5" x-text="period.timing"></div>
                      </div>
                      
                      {{-- Period Type Selector (Small pill button) --}}
                      <select x-model="period.period_type" @change="onPeriodTypeChange(period); markUnsaved()"
                              class="text-[10px] font-bold px-2 py-0.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 cursor-pointer focus:ring-1 focus:ring-blue-500">
                        <option value="class">📚 Class</option>
                        <option value="activity">🎨 Activity</option>
                        <option value="practical">🔬 Practical</option>
                        <option value="free">⚡ Free</option>
                      </select>
                    </div>

                    {{-- Subject Selection (Condition-adaptive) --}}
                    <div class="space-y-1 mb-2.5 min-w-0">
                      <div class="flex items-center justify-between">
                        <label class="block text-[11px] font-bold text-slate-700">Subject:</label>
                        <span class="text-[9px] font-bold text-blue-600 uppercase tracking-wider" x-show="period.period_type === 'class'">Academic</span>
                        <span class="text-[9px] font-bold text-purple-600 uppercase tracking-wider" x-show="period.period_type === 'activity'">Activity</span>
                        <span class="text-[9px] font-bold text-cyan-600 uppercase tracking-wider" x-show="period.period_type === 'practical' || period.period_type === 'lab'">Practical</span>
                        <span class="text-[9px] font-bold text-amber-600 uppercase tracking-wider" x-show="period.period_type === 'free'">Free Study</span>
                      </div>

                      {{-- 1. Academic Theory & Languages Selection --}}
                      <select x-show="period.period_type === 'class'" x-model.number="period.subject_id" @change="markUnsaved()"
                              class="w-full min-w-0 max-w-full h-9 px-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:bg-white transition cursor-pointer truncate">
                        <option value="">-- Select Academic Subject --</option>
                        <optgroup label="📚 Core Academic & Languages">
                          @foreach($theorySubjects as $subj)
                            <option value="{{ $subj->id }}">{{ $subj->name }}{{ $subj->code ? ' (' . $subj->code . ')' : '' }}</option>
                          @endforeach
                        </optgroup>
                        @if($allSubjects->reject(fn($s) => in_array($s->type, ['theory', 'language']))->isNotEmpty())
                          <optgroup label="── All Other Subjects ──">
                            @foreach($allSubjects->reject(fn($s) => in_array($s->type, ['theory', 'language'])) as $subj)
                              <option value="{{ $subj->id }}">{{ $subj->name }}{{ $subj->code ? ' (' . $subj->code . ')' : '' }}</option>
                            @endforeach
                          </optgroup>
                        @endif
                      </select>

                      {{-- 2. Activity / Co-Curricular Selection --}}
                      <select x-show="period.period_type === 'activity'" x-model.number="period.subject_id" @change="markUnsaved()"
                              class="w-full min-w-0 max-w-full h-9 px-2.5 bg-purple-50/50 border border-purple-200 rounded-xl text-xs font-semibold text-purple-950 focus:ring-2 focus:ring-purple-500 focus:bg-white transition cursor-pointer truncate">
                        <option value="">-- Select Activity Subject --</option>
                        <optgroup label="🎨 Co-Curricular, Sports & Wellness">
                          @foreach($activitySubjects as $subj)
                            <option value="{{ $subj->id }}">{{ $subj->name }}{{ $subj->code ? ' (' . $subj->code . ')' : '' }}</option>
                          @endforeach
                        </optgroup>
                        @if($allSubjects->reject(fn($s) => $s->type === 'activity' || $s->is_coscholastic)->isNotEmpty())
                          <optgroup label="── All Other Subjects ──">
                            @foreach($allSubjects->reject(fn($s) => $s->type === 'activity' || $s->is_coscholastic) as $subj)
                              <option value="{{ $subj->id }}">{{ $subj->name }}{{ $subj->code ? ' (' . $subj->code . ')' : '' }}</option>
                            @endforeach
                          </optgroup>
                        @endif
                      </select>

                      {{-- 3. Practical / Computer Lab Selection --}}
                      <select x-show="period.period_type === 'practical' || period.period_type === 'lab'" x-model.number="period.subject_id" @change="markUnsaved()"
                              class="w-full min-w-0 max-w-full h-9 px-2.5 bg-cyan-50/50 border border-cyan-200 rounded-xl text-xs font-semibold text-cyan-950 focus:ring-2 focus:ring-cyan-500 focus:bg-white transition cursor-pointer truncate">
                        <option value="">-- Select Practical / Lab Subject --</option>
                        <optgroup label="🔬 Practical & Computer Labs">
                          @foreach($practicalSubjects as $subj)
                            <option value="{{ $subj->id }}">{{ $subj->name }}{{ $subj->code ? ' (' . $subj->code . ')' : '' }}</option>
                          @endforeach
                        </optgroup>
                        @if($allSubjects->reject(fn($s) => $s->type === 'practical' || stripos($s->name, 'computer') !== false || stripos($s->name, 'lab') !== false)->isNotEmpty())
                          <optgroup label="── All Other Subjects ──">
                            @foreach($allSubjects->reject(fn($s) => $s->type === 'practical' || stripos($s->name, 'computer') !== false || stripos($s->name, 'lab') !== false) as $subj)
                              <option value="{{ $subj->id }}">{{ $subj->name }}{{ $subj->code ? ' (' . $subj->code . ')' : '' }}</option>
                            @endforeach
                          </optgroup>
                        @endif
                      </select>

                      {{-- 4. Free Period / Self-Study Display --}}
                      <div x-show="period.period_type === 'free'"
                           class="h-9 px-3 flex items-center justify-between bg-amber-50 text-amber-800 text-xs font-bold rounded-xl border border-amber-200">
                        <div class="flex items-center gap-1.5">
                          <span>⚡ Free Period / Self Study</span>
                        </div>
                        <span class="text-[10px] bg-amber-200/60 text-amber-900 px-1.5 py-0.5 rounded font-mono uppercase tracking-wider">No Subject</span>
                      </div>
                    </div>

                    {{-- Teacher Selection (Condition-adaptive) --}}
                    <div class="space-y-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <label class="block text-[11px] font-bold text-slate-700">Teacher:</label>
                        <span class="text-[9px] font-bold text-blue-600 uppercase tracking-wider" x-show="period.period_type === 'class'">Faculty</span>
                        <span class="text-[9px] font-bold text-purple-600 uppercase tracking-wider" x-show="period.period_type === 'activity'">Coach</span>
                        <span class="text-[9px] font-bold text-cyan-600 uppercase tracking-wider" x-show="period.period_type === 'practical' || period.period_type === 'lab'">Lab Incharge</span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider" x-show="period.period_type === 'free'">Unassigned</span>
                      </div>

                      {{-- 1. Academic Teaching Faculty --}}
                      <select x-show="period.period_type === 'class'" x-model.number="period.teacher_id" @change="markUnsaved()"
                              :class="hasConsecutiveTeacherConflict(idx) ? 'border-rose-400 bg-rose-50/70 text-rose-900 focus:ring-rose-500' : 'border-slate-300 bg-slate-50 text-slate-900 focus:ring-blue-500'"
                              class="w-full min-w-0 max-w-full h-9 px-2.5 border rounded-xl text-xs font-semibold focus:ring-2 focus:bg-white transition cursor-pointer truncate">
                        <option value="">-- Select Teacher --</option>
                        <optgroup label="👩‍🏫 Academic Teaching Faculty">
                          @foreach($academicTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}{{ $t->designation ? ' (' . $t->designation . ')' : '' }}</option>
                          @endforeach
                        </optgroup>
                        @if($allTeachers->diff($academicTeachers)->isNotEmpty())
                          <optgroup label="── Other Staff ──">
                            @foreach($allTeachers->diff($academicTeachers) as $t)
                              <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}{{ $t->designation ? ' (' . $t->designation . ')' : '' }}</option>
                            @endforeach
                          </optgroup>
                        @endif
                      </select>

                      {{-- 2. Activity / Sports Coaches --}}
                      <select x-show="period.period_type === 'activity'" x-model.number="period.teacher_id" @change="markUnsaved()"
                              :class="hasConsecutiveTeacherConflict(idx) ? 'border-rose-400 bg-rose-50/70 text-rose-900 focus:ring-rose-500' : 'border-purple-200 bg-purple-50/50 text-purple-950 focus:ring-purple-500'"
                              class="w-full min-w-0 max-w-full h-9 px-2.5 border rounded-xl text-xs font-semibold focus:ring-2 focus:bg-white transition cursor-pointer truncate">
                        <option value="">-- Select Activity Coach --</option>
                        <optgroup label="🏃 PET, Sports & Activity Coaches">
                          @foreach($activityTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->designation ?: 'Coach' }})</option>
                          @endforeach
                        </optgroup>
                        @if($allTeachers->diff($activityTeachers)->isNotEmpty())
                          <optgroup label="── All Other Teachers ──">
                            @foreach($allTeachers->diff($activityTeachers) as $t)
                              <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}{{ $t->designation ? ' (' . $t->designation . ')' : '' }}</option>
                            @endforeach
                          </optgroup>
                        @endif
                      </select>

                      {{-- 3. Lab / Computer Faculty --}}
                      <select x-show="period.period_type === 'practical' || period.period_type === 'lab'" x-model.number="period.teacher_id" @change="markUnsaved()"
                              :class="hasConsecutiveTeacherConflict(idx) ? 'border-rose-400 bg-rose-50/70 text-rose-900 focus:ring-rose-500' : 'border-cyan-200 bg-cyan-50/50 text-cyan-950 focus:ring-cyan-500 focus:bg-white transition cursor-pointer'"
                              class="w-full min-w-0 max-w-full h-9 px-2.5 border rounded-xl text-xs font-semibold focus:ring-2 focus:bg-white transition cursor-pointer truncate">
                        <option value="">-- Select Lab Faculty --</option>
                        <optgroup label="💻 Lab Incharges & Computer Faculty">
                          @foreach($labTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->designation ?: 'Lab Incharge' }})</option>
                          @endforeach
                        </optgroup>
                        @if($allTeachers->diff($labTeachers)->isNotEmpty())
                          <optgroup label="── All Other Teachers ──">
                            @foreach($allTeachers->diff($labTeachers) as $t)
                              <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}{{ $t->designation ? ' (' . $t->designation . ')' : '' }}</option>
                            @endforeach
                          </optgroup>
                        @endif
                      </select>

                      {{-- 4. Free Period Teacher Display --}}
                      <div x-show="period.period_type === 'free'"
                           class="h-9 px-3 flex items-center justify-between bg-slate-100 text-slate-500 text-xs font-semibold rounded-xl border border-slate-200">
                        <span>✨ No Teacher Required</span>
                        <span class="text-[10px] bg-slate-200/80 text-slate-600 px-1.5 py-0.5 rounded font-mono uppercase tracking-wider">Unassigned</span>
                      </div>

                      {{-- Consecutive Conflict Alert --}}
                      <template x-if="hasConsecutiveTeacherConflict(idx) && period.period_type !== 'free'">
                        <div class="mt-1.5 p-2 rounded-xl bg-rose-50 border border-rose-200 text-[11px] text-rose-700 font-semibold flex items-start gap-1.5 leading-snug">
                          <span class="flex-shrink-0 text-xs">⚠️</span>
                          <span>This teacher is already assigned to the adjacent period. A teacher cannot be assigned to consecutive periods.</span>
                        </div>
                      </template>
                    </div>
                  </div>

                  {{-- Optional Room Note & Quick Location Chips --}}
                  <div class="pt-2 border-t border-slate-100 space-y-1.5">
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                      <span x-text="'Slot ' + period.period_number"></span>
                      <input type="text" x-model="period.room" @input="markUnsaved()" placeholder="Room"
                             class="w-28 px-2 py-0.5 text-[10px] font-semibold border border-slate-200 rounded-lg bg-slate-50 text-right focus:bg-white focus:border-blue-400">
                    </div>

                    {{-- Quick Location Chips based on period type --}}
                    <div x-show="period.period_type === 'activity'" class="flex flex-wrap items-center gap-1 pt-0.5">
                      <span class="text-[9px] text-purple-600 font-bold uppercase tracking-wider">Quick:</span>
                      <button type="button" @click="period.room = 'Ground'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 cursor-pointer">🏟️ Ground</button>
                      <button type="button" @click="period.room = 'Yoga Hall'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 cursor-pointer">🧘 Yoga</button>
                      <button type="button" @click="period.room = 'Art Room'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 cursor-pointer">🎨 Art</button>
                      <button type="button" @click="period.room = 'Music Studio'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 cursor-pointer">🎵 Music</button>
                      <button type="button" @click="period.room = 'Library'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 cursor-pointer">📚 Library</button>
                    </div>

                    <div x-show="period.period_type === 'practical' || period.period_type === 'lab'" class="flex flex-wrap items-center gap-1 pt-0.5">
                      <span class="text-[9px] text-cyan-600 font-bold uppercase tracking-wider">Quick:</span>
                      <button type="button" @click="period.room = 'Computer Lab'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200/60 cursor-pointer">💻 Comp Lab</button>
                      <button type="button" @click="period.room = 'Science Lab'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200/60 cursor-pointer">🧪 Sci Lab</button>
                      <button type="button" @click="period.room = 'STEM / Robotics Lab'; markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200/60 cursor-pointer">🤖 STEM</button>
                    </div>

                    <div x-show="period.period_type === 'class'" class="flex flex-wrap items-center gap-1 pt-0.5">
                      <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Quick:</span>
                      <button type="button" @click="period.room = editSection?.room || ('Room ' + (editClass?.name || '1') + '-' + (editSection?.name || 'A')); markUnsaved()" class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200 cursor-pointer">🏫 Classroom</button>
                    </div>
                  </div>

                </div>
              </template>
            </div>
          </div>

        </div>

        {{-- ── Footer with Cancel & Save Timetable Actions ── --}}
        <div class="px-6 py-3.5 flex-shrink-0 flex items-center justify-between bg-white border-t border-slate-200">
          <div>
            <template x-if="hasAnyConsecutiveConflict()">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-300">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <span>Consecutive teacher assignment detected. Please select another teacher.</span>
              </span>
            </template>
            <template x-if="!hasAnyConsecutiveConflict() && hasUnsavedChanges">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                <span>Unsaved changes on <strong x-text="getDayName(editDay)"></strong></span>
              </span>
            </template>
            <template x-if="!hasAnyConsecutiveConflict() && !hasUnsavedChanges">
              <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                <span class="text-emerald-500 font-bold">✓</span>
                <span>All changes saved for this schedule</span>
              </span>
            </template>
          </div>

          <div class="flex items-center gap-3">
            <button type="button" @click="confirmCloseEditModal()"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
              Cancel
            </button>
            <button type="button" @click="saveActiveDayTimetable()"
                    :disabled="isSavingTimetable || hasAnyConsecutiveConflict()"
                    :class="hasAnyConsecutiveConflict() ? 'opacity-50 cursor-not-allowed bg-slate-400 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg'"
                    class="px-5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer">
              <span x-show="isSavingTimetable" class="animate-spin">🌀</span>
              <span x-show="!isSavingTimetable">💾</span>
              <span x-text="isSavingTimetable ? 'Saving Timetable...' : 'Save Timetable'"></span>
            </button>
          </div>
        </div>

      </div>

    </div>
  </template>

  {{-- ── Unsaved Changes Confirmation Dialog ── --}}
  <template x-teleport="body">
    <div x-show="discardConfirmModalOpen"
         class="fixed inset-0 z-[10020] flex items-center justify-center p-4"
         style="display:none; background:rgba(15, 23, 42, 0.75);"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
      <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full p-6 text-center space-y-4"
           @click.stop>
        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto text-2xl font-bold">
          ⚠️
        </div>
        <div>
          <h4 class="text-base font-extrabold text-slate-900">Unsaved Changes</h4>
          <p class="text-xs text-slate-500 mt-1">
            You have unsaved timetable changes. Do you want to discard them?
          </p>
        </div>
        <div class="flex items-center justify-center gap-3 pt-2">
          <button type="button" @click="discardConfirmModalOpen = false; pendingSwitchAction = null;"
                  class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">
            Keep Editing
          </button>
          <button type="button" @click="proceedDiscardChanges()"
                  class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition cursor-pointer">
            Discard Changes
          </button>
        </div>
      </div>
    </div>
  </template>

  {{-- ── Teacher Period Reporting Modal (Teleported to Body) ── --}}
  <template x-teleport="body">
    <div x-show="teacherReportModalOpen"
         @keydown.window.escape="teacherReportModalOpen = false"
         class="fixed inset-0 z-[10030] flex items-center justify-center p-3 sm:p-5"
         style="display:none; background:rgba(15, 23, 42, 0.75); backdrop-filter:blur(4px);"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

      <div class="bg-white flex flex-col w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
           @click.stop>

        {{-- Modal Header --}}
        <div class="px-6 py-4 shrink-0 flex items-center justify-between bg-slate-900 text-white">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white text-base font-bold shadow-xs">
              📝
            </div>
            <div>
              <h3 class="text-base font-extrabold tracking-tight">Period Class Report</h3>
              <p class="text-[11px] text-blue-200 mt-0.5" x-text="teacherReportSlot ? ('Period ' + teacherReportSlot.period_number + ' • ' + (teacherReportSlot.class_name ? 'Class ' + teacherReportSlot.class_name : '') + ' Sec ' + (teacherReportSlot.section_name || '')) : ''"></p>
            </div>
          </div>
          <button type="button" @click="teacherReportModalOpen = false"
                  class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-600 hover:text-white text-slate-300 flex items-center justify-center transition cursor-pointer">
            &times;
          </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 overflow-y-auto space-y-4 text-xs">

          {{-- Period Details Context Pill --}}
          <div class="p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm" x-text="teacherReportSlot?.subject_name || 'Subject'"></span>
              <span class="font-mono text-xs text-blue-700 font-bold" x-text="teacherReportSlot?.start_time && teacherReportSlot?.end_time ? (teacherReportSlot.start_time + ' - ' + teacherReportSlot.end_time) : ''"></span>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-600 flex-wrap gap-2">
              <span x-text="'Faculty: ' + (teacherReportSlot?.teacher_name || 'Assigned Teacher')"></span>
              <span x-text="'Day: ' + (teacherReportSlot?.day_name || teacherReportForm.day || '')"></span>
              <span x-show="teacherReportSlot?.room" x-text="'Room: ' + teacherReportSlot?.room"></span>
            </div>
          </div>

          {{-- Date Picker --}}
          <div>
            <label class="block font-bold text-slate-700 mb-1">Date *</label>
            <input type="date" x-model="teacherReportForm.date" @change="fetchTeacherReportForDate()"
                   class="w-full h-9 px-3 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
          </div>

          {{-- Student Strength & Attendance Counts --}}
          <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <div class="flex items-center justify-between">
              <label class="font-bold text-slate-800">Attendance Counts</label>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700"
                    x-text="'Section Strength: ' + (teacherReportTotalStudents || 0) + ' Students'"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Present Count *</label>
                <input type="number" min="0" x-model.number="teacherReportForm.present_count"
                       class="w-full h-9 px-3 border rounded-xl text-xs font-bold text-emerald-950 focus:ring-2 focus:ring-emerald-500"
                       :class="isTeacherAttendanceExceeded() ? 'border-rose-400 bg-rose-50/50' : 'border-slate-300 bg-white'">
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Absent Count *</label>
                <input type="number" min="0" x-model.number="teacherReportForm.absent_count"
                       class="w-full h-9 px-3 border rounded-xl text-xs font-bold text-rose-950 focus:ring-2 focus:ring-rose-500"
                       :class="isTeacherAttendanceExceeded() ? 'border-rose-400 bg-rose-50/50' : 'border-slate-300 bg-white'">
              </div>
            </div>

            {{-- Total vs Strength Validation Feedback --}}
            <div class="flex items-center justify-between text-[11px] pt-1">
              <span class="text-slate-500 font-medium">
                Total Counted: <strong x-text="(parseInt(teacherReportForm.present_count || 0) + parseInt(teacherReportForm.absent_count || 0))"></strong>
              </span>
              <template x-if="isTeacherAttendanceExceeded()">
                <span class="font-bold text-rose-600 flex items-center gap-1">
                  <span>⚠️</span>
                  <span>Exceeds section strength!</span>
                </span>
              </template>
              <template x-if="!isTeacherAttendanceExceeded() && (parseInt(teacherReportForm.present_count || 0) + parseInt(teacherReportForm.absent_count || 0)) > 0">
                <span class="font-semibold text-emerald-600 flex items-center gap-1">
                  <span>✓</span>
                  <span>Valid attendance</span>
                </span>
              </template>
            </div>
          </div>

          {{-- Description / Topics Covered --}}
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Description / What Was Taught *
            </label>
            <p class="text-[10px] text-slate-400 mb-1.5">Enter details of the lessons, chapters covered, exercises, or class activities completed during this period.</p>
            <textarea rows="4" x-model="teacherReportForm.description"
                      placeholder="Example: Completed Chapter 3 and conducted the worksheet activity."
                      class="w-full p-3 border border-slate-300 rounded-xl text-xs leading-relaxed focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
          </div>

          {{-- Error message banner if any --}}
          <div x-show="teacherReportError" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2" style="display:none;">
            <span>⚠️</span>
            <span x-text="teacherReportError"></span>
          </div>

          {{-- Notice if updating existing record --}}
          <div x-show="teacherReportExistingId" class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-[11px] font-semibold flex items-center gap-2" style="display:none;">
            <span>ℹ️</span>
            <span>A previously saved report for this date was loaded. Saving will update the existing record (no duplicates).</span>
          </div>

        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0">
          <button type="button" @click="teacherReportModalOpen = false"
                  class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition cursor-pointer">
            Cancel
          </button>
          <button type="button" @click="submitTeacherPeriodReport()"
                  :disabled="teacherReportSubmitting || isTeacherAttendanceExceeded()"
                  class="px-5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="teacherReportSubmitting" class="animate-spin">🌀</span>
            <span x-show="!teacherReportSubmitting">💾</span>
            <span x-text="teacherReportSubmitting ? 'Saving...' : (teacherReportExistingId ? 'Update Report' : 'Save Report')"></span>
          </button>
        </div>

      </div>
    </div>
  </template>

</div>

@push('head')
<script>
function classesDashboard() {
  return {
    classesData: @json($classesData),
    allSubjects: @json($allSubjects),
    allTeachers: @json($allTeachers),
    activitySubjectIds: @json($activitySubjects->pluck('id')->values()),
    practicalSubjectIds: @json($practicalSubjects->pluck('id')->values()),
    theorySubjectIds: @json($theorySubjects->pluck('id')->values()),
    filterCategory: 'all',
    searchQuery: '',

    // Time Simulator State
    showTimeSimulator: false,
    simulatedTime: null,
    simulatedDayOfWeek: null,
    displayClock: '',
    liveTimeString: '',

    
    // Toast Notification Popper State
    toast: {
      show: false,
      message: '',
      type: 'success'
    },
    showToast(msg, type = 'success') {
      this.toast.message = msg;
      this.toast.type = type;
      this.toast.show = true;
      setTimeout(() => {
        this.toast.show = false;
      }, 3500);
    },

    // Declare Holiday Modal State
    showHolidayModal: false,
    declaredHolidays: [],
    holidayForm: {
      date: new Date(Date.now() + 86400000).toISOString().split('T')[0],
      name: '',
      type: 'school',
      description: ''
    },
    isSavingHoliday: false,

    async loadDeclaredHolidays() {
      try {
        const res = await fetch('{{ route("classes.timetable.declared-holidays") }}');
        const data = await res.json();
        if (data.success) {
          this.declaredHolidays = data.holidays;
        }
      } catch (e) {
        console.error('Error loading declared holidays:', e);
      }
    },

    async submitDeclareHoliday() {
      if (!this.holidayForm.date || !this.holidayForm.name || !this.holidayForm.name.trim()) {
        this.showToast('Please select a Date and enter a Holiday Name.', 'error');
        return false;
      }
      this.isSavingHoliday = true;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("classes.timetable.declare-holiday") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.holidayForm)
        });
        const data = await res.json();
        if (data.success) {
          this.showToast(data.message, 'success');
          this.holidayForm.name = '';
          this.holidayForm.description = '';
          await this.loadDeclaredHolidays();
          return true;
        } else {
          this.showToast(data.message || 'Failed to declare holiday', 'error');
          return false;
        }
      } catch (e) {
        console.error('Declare holiday error:', e);
        this.showToast('An error occurred while declaring holiday', 'error');
        return false;
      } finally {
        this.isSavingHoliday = false;
      }
    },

    async saveAndCloseHoliday() {
      if (this.holidayForm.name && this.holidayForm.name.trim()) {
        const ok = await this.submitDeclareHoliday();
        if (!ok) return;
      }
      this.showHolidayModal = false;
    },

    async removeHoliday(id) {
      if (!confirm('Are you sure you want to remove this declared holiday?')) return;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ url("classes/timetable/delete-holiday") }}/' + id, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          }
        });
        const data = await res.json();
        if (data.success) {
          this.showToast(data.message, 'success');
          await this.loadDeclaredHolidays();
        }
      } catch (e) {
        console.error('Delete holiday error:', e);
      }
    },

    // View Timetable Modal State
    modalOpen: false,
    modalClass: null,
    modalSection: null,
    modalDay: 0,

    canManageTimetable: {{ \App\Http\Controllers\Admin\ClassesController::canManageTimetable(auth()->user()) ? 'true' : 'false' }},
    authTeacherId: {{ $authEmployeeId ?? ($authTeacherId ?? 'null') }},

    onFreePeriodClick(dayNum, periodNum) {
      if (this.canManageTimetable) {
        const cls = this.modalClass;
        const sec = this.modalSection;
        this.modalOpen = false;
        this.openEditTimetableModal(cls, sec, dayNum);
        this.showToast(`Opened editor for Day ${dayNum}, Period ${periodNum}. Select subject and teacher to assign.`, 'info');
      }
    },

    // Teacher Period Reporting Modal State
    teacherReportModalOpen: false,
    teacherReportSlot: null,
    teacherReportTotalStudents: 0,
    teacherReportExistingId: null,
    teacherReportSubmitting: false,
    teacherReportError: '',
    teacherReportForm: {
      timetable_id: null,
      class_id: null,
      section_id: null,
      subject_id: null,
      period_number: null,
      date: new Date().toISOString().split('T')[0],
      day: '',
      present_count: 0,
      absent_count: 0,
      description: ''
    },
    isMyPeriod(entry) {
      return this.authTeacherId && entry && entry.teacher_id && parseInt(entry.teacher_id) === parseInt(this.authTeacherId);
    },
    async openTeacherPeriodReportModal(entry, dayName) {
      if (!entry || !entry.id) return;
      this.teacherReportError = '';
      this.teacherReportExistingId = null;
      this.teacherReportSlot = null;
      this.teacherReportTotalStudents = 0;
      this.teacherReportForm.date = new Date().toISOString().split('T')[0];
      this.teacherReportForm.present_count = 0;
      this.teacherReportForm.absent_count = 0;
      this.teacherReportForm.description = '';

      try {
        const res = await fetch(`{{ url('academics/timetable/period-report') }}/${entry.id}?date=${this.teacherReportForm.date}`);
        const data = await res.json();

        if (!data.success) {
          alert(data.message || 'Unable to open period report for this slot.');
          return;
        }

        this.teacherReportSlot = data.timetable;
        this.teacherReportTotalStudents = data.total_students || 0;

        this.teacherReportForm.timetable_id = data.timetable.id;
        this.teacherReportForm.class_id = data.timetable.class_id;
        this.teacherReportForm.section_id = data.timetable.section_id;
        this.teacherReportForm.subject_id = data.timetable.subject_id;
        this.teacherReportForm.period_number = data.timetable.period_number;
        this.teacherReportForm.day = data.timetable.day_name || dayName || '';

        if (data.report) {
          this.teacherReportExistingId = data.report.id;
          this.teacherReportForm.present_count = data.report.present_count ?? 0;
          this.teacherReportForm.absent_count = data.report.absent_count ?? 0;
          this.teacherReportForm.description = data.report.description ?? '';
        } else if (this.teacherReportTotalStudents > 0) {
          this.teacherReportForm.present_count = this.teacherReportTotalStudents;
          this.teacherReportForm.absent_count = 0;
        }

        this.teacherReportModalOpen = true;
      } catch (err) {
        console.error('Fetch report error:', err);
        alert('An error occurred while loading period details: ' + err.message);
      }
    },
    async fetchTeacherReportForDate() {
      if (!this.teacherReportSlot || !this.teacherReportForm.date) return;
      try {
        const res = await fetch(`{{ url('academics/timetable/period-report') }}/${this.teacherReportSlot.id}?date=${this.teacherReportForm.date}`);
        const data = await res.json();
        if (data.success && data.report) {
          this.teacherReportExistingId = data.report.id;
          this.teacherReportForm.present_count = data.report.present_count ?? 0;
          this.teacherReportForm.absent_count = data.report.absent_count ?? 0;
          this.teacherReportForm.description = data.report.description ?? '';
        } else {
          this.teacherReportExistingId = null;
        }
      } catch (e) {
        console.error('Error fetching date report:', e);
      }
    },
    isTeacherAttendanceExceeded() {
      if (!this.teacherReportTotalStudents || this.teacherReportTotalStudents <= 0) return false;
      const p = parseInt(this.teacherReportForm.present_count || 0, 10);
      const a = parseInt(this.teacherReportForm.absent_count || 0, 10);
      return (p + a) > this.teacherReportTotalStudents;
    },
    async submitTeacherPeriodReport() {
      this.teacherReportError = '';
      if (this.teacherReportForm.present_count === null || this.teacherReportForm.present_count < 0) {
        this.teacherReportError = 'Present count must be 0 or greater.';
        return;
      }
      if (this.teacherReportForm.absent_count === null || this.teacherReportForm.absent_count < 0) {
        this.teacherReportError = 'Absent count must be 0 or greater.';
        return;
      }
      if (!this.teacherReportForm.description || !this.teacherReportForm.description.trim()) {
        this.teacherReportError = 'Please enter a description of what was covered during the period.';
        return;
      }
      if (this.isTeacherAttendanceExceeded()) {
        this.teacherReportError = 'Present count + Absent count cannot exceed total section strength.';
        return;
      }

      this.teacherReportSubmitting = true;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("academics.timetable.period-report.save") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.teacherReportForm)
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
          this.teacherReportError = data.message || 'Failed to save period report.';
          return;
        }
        this.teacherReportModalOpen = false;
        this.showToast(data.message || 'Period report saved successfully!', 'success');
      } catch (err) {
        console.error('Submit report error:', err);
        this.teacherReportError = 'Network error while saving report: ' + err.message;
      } finally {
        this.teacherReportSubmitting = false;
      }
    },

    // Edit Timetable Modal State
    editModalOpen: false,
    editSelectedClassId: null,
    editSelectedSectionId: null,
    editClass: null,
    editSection: null,
    editDay: 1,
    holidayDays: {},
    editTimetableLoading: false,
    editTimetableError: null,
    isSavingTimetable: false,
    hasUnsavedChanges: false,
    discardConfirmModalOpen: false,
    pendingSwitchAction: null,
    activeDayPeriods: [],

    currentPeriod: {
      title: 'Period I In Session',
      badge_title: 'Live Now',
      timing: '09:10 AM – 09:50 AM',
      remaining_text: '40m remaining',
      day_type: 'Regular Academic Day',
      period_number: 1,
      is_live: true
    },

    init() {
      this.updateClock();
      setInterval(() => {
        this.updateClock();
      }, 5000);

      // Initialize edit defaults with 1st standard or first class
      if (this.classesData.length > 0) {
        const firstCls = this.classesData.find(c => c.numeric_value === 1) || this.classesData[0];
        this.editSelectedClassId = firstCls.id;
        this.editClass = firstCls;
        if (firstCls.sections.length > 0) {
          const firstSec = firstCls.sections.find(s => s.name === 'A') || firstCls.sections[0];
          this.editSelectedSectionId = firstSec.id;
          this.editSection = firstSec;
        }
      }
    },

    updateClock() {
      const now = new Date();
      let hours = now.getHours();
      let minutes = now.getMinutes();
      let ampm = hours >= 12 ? 'PM' : 'AM';
      let h12 = hours % 12 || 12;
      this.liveTimeString = `${h12}:${minutes < 10 ? '0' + minutes : minutes} ${ampm}`;

      let currentH = hours;
      let currentM = minutes;
      let dayOfWeek = now.getDay() === 0 ? 7 : now.getDay(); // 1=Mon, 6=Sat, 7=Sun

      if (this.simulatedTime) {
        const parts = this.simulatedTime.split(':');
        currentH = parseInt(parts[0], 10);
        currentM = parseInt(parts[1], 10);
        if (this.simulatedDayOfWeek !== null) {
          dayOfWeek = this.simulatedDayOfWeek;
        }
      }

      let simAmpm = currentH >= 12 ? 'PM' : 'AM';
      let simH12 = currentH % 12 || 12;
      this.displayClock = `${simH12}:${currentM < 10 ? '0' + currentM : currentM} ${simAmpm}`;

      this.evaluateLivePeriod(currentH, currentM, dayOfWeek);
    },

    setTime(timeStr, dayOfWeek) {
      this.simulatedTime = timeStr;
      this.simulatedDayOfWeek = dayOfWeek;
      this.updateClock();
    },

    resetToLiveTime() {
      this.simulatedTime = null;
      this.simulatedDayOfWeek = null;
      this.updateClock();
    },

    evaluateLivePeriod(hours, minutes, dayOfWeek) {
      const timeInMins = hours * 60 + minutes;
      const isSat = (dayOfWeek === 6);
      const isSun = (dayOfWeek === 7);

      if (isSun) {
        this.currentPeriod = {
          title: 'Sunday — Campus Holiday',
          badge_title: 'Weekend',
          timing: 'Campus Closed',
          remaining_text: 'Classes resume Monday 09:10 AM',
          day_type: 'Sunday Holiday',
          period_number: 0,
          is_live: false,
          is_holiday: true
        };
        return;
      }

      const dayLabel = isSat ? 'Saturday Extracurricular' : 'Regular Schedule';

      if (timeInMins < 550) {
        this.currentPeriod = {
          title: 'School Starts at 09:10 AM',
          badge_title: 'Pre-Session',
          timing: 'Starts in ' + (550 - timeInMins) + ' mins',
          remaining_text: 'Period I begins at 09:10 AM',
          day_type: dayLabel,
          period_number: 0,
          is_live: false
        };
      } else if (timeInMins < 590) {
        this.setPeriodState(1, 'Period I', '09:10 AM – 09:50 AM', 550, 590, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 630) {
        this.setPeriodState(2, 'Period II', '09:50 AM – 10:30 AM', 590, 630, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 640) {
        this.currentPeriod = {
          title: 'Morning Interval Break',
          badge_title: '☕ Interval',
          timing: '10:30 AM – 10:40 AM',
          remaining_text: (640 - timeInMins) + 'm left',
          day_type: dayLabel,
          period_number: 0,
          is_live: true,
          is_break: true,
          next_period_num: 3
        };
      } else if (timeInMins < 680) {
        this.setPeriodState(3, 'Period III', '10:40 AM – 11:20 AM', 640, 680, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 720) {
        this.setPeriodState(4, 'Period IV', '11:20 AM – 12:00 PM', 680, 720, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 760) {
        this.setPeriodState(5, 'Period V', '12:00 PM – 12:40 PM', 720, 760, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 800) {
        this.currentPeriod = {
          title: '🍱 Lunch Break',
          badge_title: 'Lunch',
          timing: '12:40 PM – 01:20 PM',
          remaining_text: (800 - timeInMins) + 'm left',
          day_type: dayLabel,
          period_number: 0,
          is_live: true,
          is_break: true,
          next_period_num: 6
        };
      } else if (timeInMins < 840) {
        this.setPeriodState(6, 'Period VI', '01:20 PM – 02:00 PM', 800, 840, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 850) {
        this.currentPeriod = {
          title: 'Afternoon Refreshment Break',
          badge_title: 'Interval',
          timing: '02:00 PM – 02:10 PM',
          remaining_text: (850 - timeInMins) + 'm left',
          day_type: dayLabel,
          period_number: 0,
          is_live: true,
          is_break: true,
          next_period_num: 7
        };
      } else if (timeInMins < 890) {
        this.setPeriodState(7, 'Period VII', '02:10 PM – 02:50 PM', 850, 890, timeInMins, dayLabel, isSat);
      } else if (timeInMins < 900) {
        this.setPeriodState(8, 'Diary', '02:50 PM – 03:00 PM', 890, 900, timeInMins, dayLabel, isSat);
      } else {
        this.currentPeriod = {
          title: 'School Dispersed for the Day',
          badge_title: 'Dismissed',
          timing: 'Ended at 03:00 PM',
          remaining_text: 'Campus re-opens 09:10 AM tomorrow',
          day_type: dayLabel,
          period_number: 0,
          is_live: false
        };
      }
    },

    setPeriodState(periodNum, title, timing, startMin, endMin, curMin, dayLabel, isSat) {
      const elapsed = curMin - startMin;
      const total = endMin - startMin;
      const remaining = endMin - curMin;
      const percent = Math.round((elapsed / total) * 100);

      this.currentPeriod = {
        title: title + ' In Session',
        badge_title: 'Live Now',
        timing: timing,
        remaining_text: remaining + 'm left',
        day_type: dayLabel,
        period_number: periodNum,
        elapsed_mins: elapsed,
        remaining_mins: remaining,
        progress_percent: percent,
        is_live: true,
        is_saturday: isSat
      };
    },

    getLivePeriodForSection(cls, sec) {
      const pNum = this.currentPeriod.period_number;
      const day = this.simulatedDayOfWeek !== null ? this.simulatedDayOfWeek : (new Date().getDay() === 0 ? 7 : new Date().getDay());

      if (day === 7) {
        return {
          is_active_period: false,
          badge_text: 'Weekend Holiday',
          subject_name: 'Sunday Campus Holiday',
          teacher_name: 'No sessions scheduled',
          timing: 'Resumes Monday 09:10 AM'
        };
      }

      if (pNum === 0) {
        const nextText = this.currentPeriod.next_period_num 
          ? ((this.currentPeriod.next_period_num === 8 ? 'Diary' : ('Period ' + this.currentPeriod.next_period_num)) + ' begins next')
          : null;
        return {
          is_active_period: false,
          is_break: this.currentPeriod.is_break || false,
          badge_text: this.currentPeriod.badge_title || 'Campus Interval',
          subject_name: this.currentPeriod.title,
          teacher_name: 'Supervised Interval',
          timing: this.currentPeriod.timing,
          next_period_text: nextText
        };
      }

      const weekSchedule = sec.weekly_schedule || {};
      const dayEntries = weekSchedule[day] || weekSchedule[String(day)] || weekSchedule[1] || weekSchedule['1'] || [];
      const entry = dayEntries.find(e => parseInt(e.period, 10) === pNum) || dayEntries[pNum - 1] || null;

      // Extract real ongoing subject name & teacher name
      let subjectName = entry?.subject_name;
      let teacherName = entry?.teacher_name || sec.class_teacher || 'Faculty';

      if (!subjectName || subjectName === 'Class in Session' || subjectName === 'Study Period') {
        const todaySlots = this.getTodayPeriods(cls, sec);
        const currentSlot = todaySlots.find(s => s.period === pNum);
        if (currentSlot && currentSlot.full_title) {
          subjectName = currentSlot.full_title.split(' (')[0];
        } else {
          const defaultSubjects = ['Tamil', 'English', 'Mathematics', 'Science', 'Social Science', 'Computer Science', 'Hindi', 'Diary'];
          subjectName = defaultSubjects[(pNum - 1) % defaultSubjects.length];
        }
      }

      const timing = (entry && entry.start_time && entry.end_time)
        ? (entry.start_time + ' – ' + entry.end_time)
        : this.currentPeriod.timing;

      let nextPeriodText = null;
      if (pNum < 8) {
        const nextSlot = dayEntries.find(e => parseInt(e.period, 10) === pNum + 1) || dayEntries[pNum] || null;
        const nextSubject = nextSlot?.subject_name || (this.getTodayPeriods(cls, sec)[pNum]?.full_title?.split(' (')[0]) || (pNum === 7 ? 'Diary' : ('Period ' + (pNum + 1)));
        nextPeriodText = (pNum === 7 ? 'Diary' : ('Period ' + (pNum + 1))) + ': ' + nextSubject;
      } else {
        nextPeriodText = 'End of Day (03:00 PM)';
      }

      return {
        is_active_period: true,
        is_saturday: day === 6,
        badge_text: pNum === 8 ? 'LIVE NOW • DIARY' : ('LIVE NOW • PERIOD ' + pNum),
        subject_name: subjectName,
        teacher_name: teacherName,
        timing: timing,
        progress_percent: this.currentPeriod.progress_percent || 45,
        remaining_mins: this.currentPeriod.remaining_mins || 20,
        next_period_text: nextPeriodText
      };
    },

    getTodayPeriods(cls, sec) {
      const day = this.simulatedDayOfWeek !== null ? this.simulatedDayOfWeek : (new Date().getDay() === 0 ? 7 : new Date().getDay());
      const weekSchedule = sec.weekly_schedule || {};
      const dayEntries = weekSchedule[day] || weekSchedule[String(day)] || weekSchedule[1] || weekSchedule['1'] || [];

      const currentPeriodNum = this.currentPeriod.period_number;

      return [1, 2, 3, 4, 5, 6, 7, 8].map(p => {
        const entry = dayEntries.find(e => parseInt(e.period, 10) === p) || dayEntries[p - 1];
        let name = entry ? entry.subject_name : (p === 8 ? 'Diary' : 'Period ' + p);
        let abbr = name.split(' ')[0].substring(0, 4);
        if (abbr.length < 3) abbr = name.substring(0, 4);

        return {
          period: p,
          period_label: p === 8 ? 'Diary' : ('P' + p),
          full_title: name + ' (' + (entry ? entry.teacher_name : '') + ')',
          abbr: abbr,
          is_current: (p === currentPeriodNum && day !== 7)
        };
      });
    },

    // ── View Timetable Modal Methods ──
    async openTimetableModal(cls, sec) {
      this.modalClass = cls;
      this.modalSection = sec || (cls.sections && cls.sections[0]) || null;
      this.modalDay = 0;
      this.modalOpen = true;

      // Always fetch freshest timetable data from backend
      if (this.modalClass && this.modalSection) {
        try {
          const res = await fetch(`/classes/${this.modalClass.id}/timetable-data?section_id=${this.modalSection.id}`);
          const data = await res.json();
          if (data && data.timetable) {
            this.modalSection.weekly_schedule = data.timetable;
          }
          if (data && data.holiday_days) {
            this.modalSection.holiday_days = data.holiday_days;
          }
        } catch (err) {
          console.warn('Using local cache for timetable:', err);
        }
      }
    },

    isModalDayHoliday(dayNum) {
      if (!this.modalSection) return false;
      if (this.modalSection.holiday_days && this.modalSection.holiday_days[dayNum]) {
        return true;
      }
      const weekSchedule = this.modalSection.weekly_schedule || {};
      const dayEntries = weekSchedule[dayNum] || weekSchedule[String(dayNum)] || [];
      return dayEntries.length > 0 && dayEntries.every(e => e.subject_type === 'holiday' || e.period_type === 'holiday');
    },

    getModalDays() {
      const allDays = [
        { dayNum: 1, name: 'Monday' },
        { dayNum: 2, name: 'Tuesday' },
        { dayNum: 3, name: 'Wednesday' },
        { dayNum: 4, name: 'Thursday' },
        { dayNum: 5, name: 'Friday' },
        { dayNum: 6, name: 'Saturday' }
      ];

      if (this.modalDay === 0) {
        return allDays;
      }
      return allDays.filter(d => d.dayNum === this.modalDay);
    },

    getPeriodEntry(dayNum, periodNum) {
      if (!this.modalSection) return null;
      const weekSchedule = this.modalSection.weekly_schedule || {};
      const dayEntries = weekSchedule[dayNum] || weekSchedule[String(dayNum)] || [];
      return dayEntries.find(e => parseInt(e.period, 10) === periodNum) || dayEntries[periodNum - 1] || null;
    },

    // ── Edit Timetable Modal Methods ──

    getDayName(d) {
      const names = { 1: 'Monday', 2: 'Tuesday', 3: 'Wednesday', 4: 'Thursday', 5: 'Friday', 6: 'Saturday' };
      return names[d] || 'Monday';
    },

    markUnsaved() {
      this.hasUnsavedChanges = true;
    },

    confirmCloseEditModal() {
      if (this.hasUnsavedChanges) {
        this.pendingSwitchAction = () => {
          this.editModalOpen = false;
        };
        this.discardConfirmModalOpen = true;
      } else {
        this.editModalOpen = false;
      }
    },

    proceedDiscardChanges() {
      this.hasUnsavedChanges = false;
      this.discardConfirmModalOpen = false;
      if (typeof this.pendingSwitchAction === 'function') {
        const action = this.pendingSwitchAction;
        this.pendingSwitchAction = null;
        action();
      }
    },

    openEditTimetableModal(cls, sec, day = 1) {
      const targetClass = cls || (this.classesData.find(c => c.id === this.editSelectedClassId) || this.classesData.find(c => c.numeric_value === 1) || this.classesData[0]);
      if (!targetClass) return;

      this.editClass = targetClass;
      this.editSelectedClassId = targetClass.id;

      const targetSection = sec || (targetClass.sections && targetClass.sections.find(s => s.id === this.editSelectedSectionId)) || (targetClass.sections && targetClass.sections.find(s => s.name === 'A')) || (targetClass.sections && targetClass.sections[0]) || null;
      this.editSection = targetSection;
      this.editSelectedSectionId = targetSection ? targetSection.id : null;

      this.editDay = (day >= 1 && day <= 6) ? day : 1;
      this.hasUnsavedChanges = false;
      this.pendingSwitchAction = null;
      this.editModalOpen = true;

      this.loadTimetableForActiveSelection();
    },

    getSelectedClassSectionLabel() {
      if (!this.editClass || !this.editSection) return 'Select Class & Section';
      const cName = this.editClass.display_name || this.editClass.name;
      return `${cName} – Section ${this.editSection.name}`;
    },

    getFilteredClassSectionOptions(filter) {
      const options = [];
      (this.classesData || []).forEach(cls => {
        (cls.sections || []).forEach(sec => {
          const label = `${cls.display_name || cls.name} – Section ${sec.name}`;
          options.push({
            key: `${cls.id}-${sec.id}`,
            classId: cls.id,
            sectionId: sec.id,
            label: label,
            teacher: sec.class_teacher || ''
          });
        });
      });
      if (!filter || !filter.trim()) return options;
      const f = filter.toLowerCase().trim();
      return options.filter(opt => opt.label.toLowerCase().includes(f) || (opt.teacher && opt.teacher.toLowerCase().includes(f)));
    },

    onClassSectionPicked(classId, sectionId) {
      if (this.editSelectedClassId === classId && this.editSelectedSectionId === sectionId) return;

      if (this.hasUnsavedChanges) {
        this.pendingSwitchAction = () => {
          this.executeClassSectionSwitch(classId, sectionId);
        };
        this.discardConfirmModalOpen = true;
        return;
      }
      this.executeClassSectionSwitch(classId, sectionId);
    },

    executeClassSectionSwitch(classId, sectionId) {
      const cls = this.classesData.find(c => c.id === classId);
      if (cls) {
        this.editClass = cls;
        this.editSelectedClassId = cls.id;
        const sec = (cls.sections || []).find(s => s.id === sectionId) || (cls.sections && cls.sections[0]) || null;
        this.editSection = sec;
        this.editSelectedSectionId = sec ? sec.id : null;
        this.hasUnsavedChanges = false;
        this.loadTimetableForActiveSelection();
      }
    },

    onDayChangePicked(dayNum) {
      if (this.editDay === dayNum) return;

      if (this.hasUnsavedChanges) {
        this.pendingSwitchAction = () => {
          this.executeDayChange(dayNum);
        };
        this.discardConfirmModalOpen = true;
        return;
      }
      this.executeDayChange(dayNum);
    },

    executeDayChange(dayNum) {
      this.editDay = dayNum;
      this.hasUnsavedChanges = false;
      this.loadActiveDayPeriods();
    },

    async loadTimetableForActiveSelection() {
      if (!this.editClass || !this.editSection) {
        this.activeDayPeriods = [];
        return;
      }

      this.editTimetableLoading = true;
      this.editTimetableError = null;

      try {
        const res = await fetch(`/classes/${this.editClass.id}/timetable-data?section_id=${this.editSection.id}`);
        if (!res.ok) {
          throw new Error(`Server returned error (${res.status})`);
        }
        const data = await res.json();

        if (data && data.timetable) {
          this.editSection.weekly_schedule = data.timetable;
        } else if (!this.editSection.weekly_schedule) {
          this.editSection.weekly_schedule = {};
        }

        if (data && data.holiday_days) {
          this.holidayDays = Object.assign({}, data.holiday_days);
        }

        this.syncCurrentEditToClassesData();
        this.loadActiveDayPeriods();
      } catch (err) {
        console.error('Failed to load timetable:', err);
        if (this.editSection && this.editSection.weekly_schedule) {
          this.loadActiveDayPeriods();
        } else {
          this.editTimetableError = err.message || 'Unable to fetch timetable from server.';
        }
      } finally {
        this.editTimetableLoading = false;
      }
    },

    loadActiveDayPeriods() {
      if (!this.editSection) {
        this.activeDayPeriods = [];
        return;
      }

      const weekSchedule = this.editSection.weekly_schedule || {};
      const dayEntries = weekSchedule[this.editDay] || weekSchedule[String(this.editDay)] || [];

      const periods = [];
      for (let p = 1; p <= 8; p++) {
        const entry = dayEntries.find(e => parseInt(e.period, 10) === p) || null;
        const timing = this.getClientPeriodTiming(p);
        const label = this.getClientPeriodLabel(p);

        let slotType = entry?.period_type || entry?.subject_type || (p === 8 ? 'class' : 'class');
        if (slotType === 'lab') slotType = 'practical';

        periods.push({
          period_number: p,
          period_label: label,
          timing: timing,
          period_type: slotType,
          subject_id: entry?.subject_id ? parseInt(entry.subject_id, 10) : (entry?.subject_name ? this.findSubjectIdByName(entry.subject_name) : null),
          teacher_id: entry?.teacher_id ? parseInt(entry.teacher_id, 10) : (entry?.teacher_name ? this.findTeacherIdByName(entry.teacher_name) : null),
          room: entry?.room || this.editSection.room || `Room ${this.editClass.name || '1'}-${this.editSection.name || 'A'}`,
          notes: entry?.notes || ''
        });
      }

      this.activeDayPeriods = periods;
      this.hasUnsavedChanges = false;
    },

    onPeriodTypeChange(period) {
      if (!period) return;
      if (period.period_type === 'free') {
        period.subject_id = null;
        period.teacher_id = null;
        period.room = 'Self Study';
      } else if (period.period_type === 'activity') {
        const isAct = this.activitySubjectIds.includes(Number(period.subject_id));
        if (!isAct) {
          period.subject_id = null;
        }
        if (!period.room || period.room.startsWith('Room')) {
          period.room = 'Ground';
        }
      } else if (period.period_type === 'practical' || period.period_type === 'lab') {
        const isPrac = this.practicalSubjectIds.includes(Number(period.subject_id));
        if (!isPrac) {
          period.subject_id = null;
        }
        if (!period.room || period.room.startsWith('Room')) {
          period.room = 'Computer Lab';
        }
      } else if (period.period_type === 'class') {
        if (period.room === 'Ground' || period.room === 'Self Study' || period.room === 'Computer Lab') {
          period.room = this.editSection?.room || `Room ${this.editClass?.name || '1'}-${this.editSection?.name || 'A'}`;
        }
      }
    },

    initializeDefaultDayPeriods() {
      const periods = [];
      for (let p = 1; p <= 8; p++) {
        periods.push({
          period_number: p,
          period_label: this.getClientPeriodLabel(p),
          timing: this.getClientPeriodTiming(p),
          period_type: 'class',
          subject_id: null,
          teacher_id: null,
          room: this.editSection?.room || `Room ${this.editClass?.name || '1'}-${this.editSection?.name || 'A'}`,
          notes: ''
        });
      }
      this.activeDayPeriods = periods;
      this.markUnsaved();
    },

    getClientPeriodLabel(p) {
      if (p === 8) return 'Diary';
      const roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII'];
      return 'Period ' + (roman[p - 1] || p);
    },

    getClientPeriodTiming(p) {
      const timings = {
        1: '09:10 AM – 09:50 AM',
        2: '09:50 AM – 10:30 AM',
        3: '10:40 AM – 11:20 AM',
        4: '11:20 AM – 12:00 PM',
        5: '12:00 PM – 12:40 PM',
        6: '01:20 PM – 02:00 PM',
        7: '02:10 PM – 02:50 PM',
        8: '02:50 PM – 03:00 PM'
      };
      return timings[p] || '09:10 AM – 03:00 PM';
    },

    findSubjectIdByName(name) {
      if (!name || !this.allSubjects) return null;
      const lower = name.toLowerCase().trim();
      const match = this.allSubjects.find(s =>
        s.name.toLowerCase().trim() === lower ||
        (s.code && s.code.toLowerCase().trim() === lower)
      );
      return match ? match.id : null;
    },

    findTeacherIdByName(name) {
      if (!name || !this.allTeachers) return null;
      const lower = name.toLowerCase().trim();
      const match = this.allTeachers.find(t => {
        const full = `${t.first_name} ${t.last_name || ''}`.toLowerCase().trim();
        return full === lower || t.first_name.toLowerCase().trim() === lower;
      });
      return match ? match.id : null;
    },

    isDayHoliday(dayNum) {
      if (this.holidayDays[dayNum] !== undefined) {
        return Boolean(this.holidayDays[dayNum]);
      }
      if (this.editSection && this.editSection.weekly_schedule) {
        const dayEntries = this.editSection.weekly_schedule[dayNum] || this.editSection.weekly_schedule[String(dayNum)] || [];
        if (dayEntries.length > 0 && dayEntries.every(e => e.subject_type === 'holiday' || e.period_type === 'holiday')) {
          return true;
        }
      }
      return false;
    },

    async toggleDayHoliday(dayNum) {
      if (!this.editClass || !this.editSection) return;
      const current = this.isDayHoliday(dayNum);
      const newStatus = !current;

      this.holidayDays[dayNum] = newStatus;

      // Backend sync
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("classes.timetable.toggle-holiday") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            class_id: this.editClass.id,
            section_id: this.editSection.id,
            day_of_week: dayNum,
            is_holiday: newStatus
          })
        });

        const data = await res.json();
        if (data.success) {
          this.showToast(data.message || (newStatus ? 'Marked day as Holiday.' : 'Holiday removed.'), 'success');
        } else {
          this.holidayDays[dayNum] = current; // revert
          this.showToast(data.message || 'Failed to update holiday status.', 'error');
        }
      } catch (e) {
        this.holidayDays[dayNum] = current; // revert
        console.error('Holiday toggle sync error:', e);
        this.showToast('Failed to update holiday status: ' + e.message, 'error');
      }
    },

    hasConsecutiveTeacherConflict(idx) {
      if (!this.activeDayPeriods || !this.activeDayPeriods[idx]) return false;
      const current = this.activeDayPeriods[idx];
      const teacherId = current.teacher_id;
      const periodType = current.period_type || 'class';

      if (!teacherId || periodType === 'free' || periodType === 'holiday') {
        return false;
      }

      // Check immediately previous slot
      if (idx > 0) {
        const prev = this.activeDayPeriods[idx - 1];
        const prevType = prev.period_type || 'class';
        if (prev.teacher_id && prev.teacher_id === teacherId && prevType !== 'free' && prevType !== 'holiday') {
          return true;
        }
      }

      // Check immediately next slot
      if (idx < this.activeDayPeriods.length - 1) {
        const next = this.activeDayPeriods[idx + 1];
        const nextType = next.period_type || 'class';
        if (next.teacher_id && next.teacher_id === teacherId && nextType !== 'free' && nextType !== 'holiday') {
          return true;
        }
      }

      return false;
    },

    hasAnyConsecutiveConflict() {
      if (!this.activeDayPeriods || this.activeDayPeriods.length === 0) return false;
      for (let i = 0; i < this.activeDayPeriods.length; i++) {
        if (this.hasConsecutiveTeacherConflict(i)) {
          return true;
        }
      }
      return false;
    },

    async saveActiveDayTimetable(forceConflict = false) {
      if (!this.editClass || !this.editSection) return;

      if (this.hasAnyConsecutiveConflict()) {
        this.showToast('This teacher is already assigned to the adjacent period. A teacher cannot be assigned to consecutive periods.', 'error');
        return;
      }

      this.isSavingTimetable = true;

      try {
        const payload = {
          class_id: this.editClass.id,
          section_id: this.editSection.id,
          day_of_week: this.editDay,
          force_conflict: forceConflict,
          periods: this.activeDayPeriods.map(p => ({
            period_number: p.period_number,
            period_type: p.period_type || 'class',
            subject_id: p.subject_id || null,
            teacher_id: p.teacher_id || null,
            room: p.room || '',
            notes: p.notes || ''
          }))
        };

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("classes.timetable.save-day") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (res.status === 422 && data.consecutive_conflict) {
          this.isSavingTimetable = false;
          this.showToast(data.message || 'This teacher is already assigned to the adjacent period. A teacher cannot be assigned to consecutive periods.', 'error');
          return;
        }

        if (res.status === 422 && data.conflict) {
          this.isSavingTimetable = false;
          if (confirm(`${data.message}\n\nDo you want to override and assign this teacher anyway?`)) {
            await this.saveActiveDayTimetable(true);
          }
          return;
        }

        if (data.success) {
          if (!this.editSection.weekly_schedule) {
            this.editSection.weekly_schedule = {};
          }
          this.editSection.weekly_schedule[this.editDay] = data.day_periods || [];

          this.syncCurrentEditToClassesData();
          this.loadActiveDayPeriods(); // Re-sync active periods
          this.hasUnsavedChanges = false;
          this.showToast(data.message || 'Timetable saved successfully!', 'success');
        } else {
          this.showToast(data.message || 'Failed to save timetable.', 'error');
        }
      } catch (err) {
        console.error('Save timetable error:', err);
        this.showToast('An error occurred while saving timetable: ' + err.message, 'error');
      } finally {
        this.isSavingTimetable = false;
      }
    },

    syncCurrentEditToClassesData() {
      if (this.editClass && this.editSection) {
        const cls = this.classesData.find(c => c.id === this.editClass.id);
        if (cls) {
          const sec = cls.sections.find(s => s.id === this.editSection.id);
          if (sec) {
            sec.weekly_schedule = JSON.parse(JSON.stringify(this.editSection.weekly_schedule || {}));
          }
        }
      }
    },

    get filteredClasses() {
      return this.classesData.filter(c => {
        if (this.filterCategory === 'all') {
          if (c.numeric_value < 1 || c.numeric_value > 12) {
            return false;
          }
        } else if (this.filterCategory !== 'all') {
          if (c.category !== this.filterCategory) {
            return false;
          }
        }

        if (this.searchQuery.trim()) {
          const q = this.searchQuery.toLowerCase();
          const matchName = (c.name || '').toLowerCase().includes(q);
          const matchDisplayName = (c.display_name || '').toLowerCase().includes(q);
          const matchCategory = (c.category || '').toLowerCase().includes(q);
          const matchSection = (c.sections || []).some(s =>
            (s.stream_name || '').toLowerCase().includes(q) ||
            (s.class_teacher || '').toLowerCase().includes(q)
          );
          return matchName || matchDisplayName || matchCategory || matchSection;
        }

        return true;
      });
    }
  };
}
</script>
@endpush
@endsection

