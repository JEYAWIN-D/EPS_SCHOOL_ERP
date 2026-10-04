@extends('layouts.app')
@section('title', 'Event & Circular Calendar')

@section('content')
@php
  $user = auth()->user();
  $canCreate = $user && ($user->hasAnyRole(['super_admin', 'admin', 'principal', 'vice_principal', 'correspondent', 'correspondant']) || $user->can('create events') || $user->can('manage events'));
  $canEdit = $user && ($user->hasAnyRole(['super_admin', 'admin', 'principal', 'vice_principal', 'correspondent', 'correspondant', 'teacher']) || $user->can('edit events') || $user->can('manage events'));

  $prevMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->subMonth();
  $nextMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->addMonth();
  $currentMonthCarbon = \Carbon\Carbon::createFromDate($year, $month, 1);
  $monthName = $currentMonthCarbon->format('F Y');

  $typeColors = [
    'academic' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'cultural' => 'bg-pink-100 text-pink-800 border-pink-200',
    'sports'   => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'holiday'  => 'bg-amber-100 text-amber-800 border-amber-200',
    'meeting'  => 'bg-blue-100 text-blue-800 border-blue-200',
    'other'    => 'bg-slate-100 text-slate-700 border-slate-200',
  ];

  $totalMonthEvents = $events->flatten()->count();
  $totalMonthCirculars = $circulars->flatten()->count();
  $totalMonthOrders = $orders->flatten()->count();
@endphp

<div class="space-y-6 pb-12" x-data="calendarApp()">

  {{-- Top Navigation Switcher: Events vs Circulars --}}
  <div class="flex items-center gap-3 p-1.5 bg-slate-200/70 rounded-2xl w-fit border border-slate-300/60 shadow-2xs">
    <a href="{{ route('events.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-white text-maroon-900 shadow-xs transition flex items-center gap-2 border border-slate-200/80">
      <svg class="w-4 h-4 text-maroon-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      School Events
    </a>
    <a href="{{ route('circulars.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Circulars &amp; Official Orders
    </a>
  </div>

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
        <a href="{{ route('events.index') }}" class="hover:text-maroon-700 transition">Events</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Calendar Overview</span>
      </div>
      <h1 class="page-title text-2xl font-extrabold text-slate-900 tracking-tight">Institutional Calendar</h1>
      <p class="page-subtitle text-xs sm:text-sm text-slate-500 mt-0.5">
        Monthly schedule of school events, official circulars, administrative orders, and academic meetings.
      </p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('events.index') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        Events List
      </a>
      <a href="{{ route('circulars.index') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Circulars List
      </a>
      @if($canCreate)
        <a href="{{ route('events.create') }}" class="btn btn-primary inline-flex items-center gap-1.5 shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Schedule Event
        </a>
      @endif
    </div>
  </div>

  {{-- Month & Filter Navigation Bar --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    {{-- Month Navigator --}}
    <div class="flex items-center gap-3">
      <a href="{{ route('events.calendar', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}"
         class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span>{{ $prevMonth->format('M Y') }}</span>
      </a>

      <div class="flex items-center gap-2">
        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">{{ $monthName }}</h2>
        @if($month != now()->month || $year != now()->year)
          <a href="{{ route('events.calendar', ['month' => now()->month, 'year' => now()->year]) }}"
             class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-maroon-50 text-maroon-700 border border-maroon-200 hover:bg-maroon-100 transition shadow-2xs">
            Current Month
          </a>
        @endif
      </div>

      <a href="{{ route('events.calendar', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}"
         class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
        <span>{{ $nextMonth->format('M Y') }}</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>

    {{-- Filter View Switcher (Client-side interactive pills) --}}
    <div class="flex items-center gap-2 flex-wrap">
      <button type="button" @click="filterType = 'all'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition"
              :class="filterType === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
        All Records ({{ $totalMonthEvents + $totalMonthCirculars + $totalMonthOrders }})
      </button>
      <button type="button" @click="filterType = 'events'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
              :class="filterType === 'events' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'">
        <span class="w-2 h-2 rounded-full bg-indigo-500" :class="filterType === 'events' ? 'bg-white' : ''"></span>
        Events ({{ $totalMonthEvents }})
      </button>
      <button type="button" @click="filterType = 'circulars'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
              :class="filterType === 'circulars' ? 'bg-sky-600 text-white shadow-xs' : 'bg-sky-50 text-sky-700 hover:bg-sky-100'">
        <span class="w-2 h-2 rounded-full bg-sky-500" :class="filterType === 'circulars' ? 'bg-white' : ''"></span>
        Circulars ({{ $totalMonthCirculars }})
      </button>
      <button type="button" @click="filterType = 'orders'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
              :class="filterType === 'orders' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 text-purple-700 hover:bg-purple-100'">
        <span class="w-2 h-2 rounded-full bg-purple-500" :class="filterType === 'orders' ? 'bg-white' : ''"></span>
        Official Orders ({{ $totalMonthOrders }})
      </button>
    </div>
  </div>

  {{-- Calendar Card --}}
  <div class="card p-4 sm:p-6 bg-white rounded-2xl border border-slate-200/90 shadow-sm">

    {{-- Weekday Column Headers --}}
    <div class="grid grid-cols-7 gap-2 mb-2">
      @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $dow)
        <div class="text-center py-2 text-xs font-extrabold uppercase tracking-wider {{ in_array($dow, ['Sunday', 'Saturday']) ? 'text-rose-600/80 bg-rose-50/40' : 'text-slate-600 bg-slate-50' }} rounded-xl">
          <span class="hidden md:inline">{{ $dow }}</span>
          <span class="md:hidden">{{ substr($dow, 0, 3) }}</span>
        </div>
      @endforeach
    </div>

    {{-- Calendar Days Grid --}}
    <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
      {{-- Leading Empty Cells --}}
      @for($i = 0; $i < $startDow; $i++)
        <div class="min-h-[110px] sm:min-h-[130px] rounded-xl bg-slate-50/50 border border-dashed border-slate-200/60 p-2"></div>
      @endfor

      {{-- Days of Month --}}
      @foreach($days as $day)
        @php
          $isToday = (now()->day === $day && now()->month === $month && now()->year === $year);
          $carbonDate = \Carbon\Carbon::createFromDate($year, $month, $day);
          $isWeekend = $carbonDate->isWeekend();
          $dayEvents = $events->get($day, collect());
          $dayCirculars = $circulars->get($day, collect());
          $dayOrders = $orders->get($day, collect());
          $totalDayItems = $dayEvents->count() + $dayCirculars->count() + $dayOrders->count();

          // Prepare day payload for modal
          $dayPayload = [
            'date_str'  => $carbonDate->format('l, d F Y'),
            'date_iso'  => $carbonDate->toDateString(),
            'events'    => $dayEvents->map(fn($e) => [
              'id'          => $e->id,
              'name'        => $e->name,
              'type'        => $e->event_type,
              'venue'       => $e->venue,
              'audience'    => ucfirst($e->audience ?? 'All'),
              'time'        => $e->start_time ? substr($e->start_time, 0, 5) . ($e->end_time ? ' - ' . substr($e->end_time, 0, 5) : '') : 'All Day',
              'description' => strip_tags($e->description ?? ''),
              'is_assigned' => $e->is_assigned,
              'staff_count' => $e->staff->count(),
              'staff_names' => $e->staff->map(fn($s) => $s->employee?->full_name)->filter()->implode(', '),
              'view_url'    => route('events.show', $e->id),
              'edit_url'    => route('events.edit', $e->id),
            ])->values(),
            'circulars' => $dayCirculars->map(fn($c) => [
              'id'          => $c->id,
              'title'       => $c->title,
              'reference_no'=> $c->reference_no,
              'authority'   => $c->issuing_authority,
              'audience'    => $c->target_audience,
              'priority'    => $c->priority,
              'has_file'    => (bool)$c->attachment,
              'view_url'    => route('circulars.show', $c->id),
              'edit_url'    => route('circulars.edit', $c->id),
            ])->values(),
            'orders'    => $dayOrders->map(fn($o) => [
              'id'          => $o->id,
              'title'       => $o->title,
              'reference_no'=> $o->reference_no,
              'authority'   => $o->issuing_authority,
              'priority'    => $o->priority,
              'audience'    => $o->target_audience,
              'has_file'    => (bool)$o->attachment,
              'view_url'    => route('circulars.show', $o->id),
              'edit_url'    => route('circulars.edit', $o->id),
            ])->values(),
          ];
        @endphp

        <div @click="openDayModal(@js($dayPayload))"
             class="group min-h-[110px] sm:min-h-[130px] rounded-xl border p-2 flex flex-col justify-between transition cursor-pointer select-none {{ $isToday ? 'bg-indigo-50/50 border-indigo-300 ring-2 ring-indigo-400/40 shadow-xs' : ($isWeekend ? 'bg-slate-50/40 border-slate-200 hover:border-slate-300 hover:bg-slate-50' : 'bg-white border-slate-200/90 hover:border-slate-300 hover:shadow-2xs') }}">
          
          {{-- Day Header --}}
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-black {{ $isToday ? 'w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center -ml-0.5' : ($isWeekend ? 'text-rose-500' : 'text-slate-700') }}">
              {{ $day }}
            </span>

            @if($totalDayItems > 0)
              <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded-full">
                {{ $totalDayItems }}
              </span>
            @endif
          </div>

          {{-- Day Items Snippets --}}
          <div class="space-y-1 flex-1 overflow-hidden">

            {{-- School Events --}}
            <template x-if="filterType === 'all' || filterType === 'events'">
              <div class="space-y-1">
                @foreach($dayEvents->take(2) as $ev)
                  <div @click.stop="openItemModal('event', @js([
                        'id'          => $ev->id,
                        'name'        => $ev->name,
                        'type'        => $ev->event_type,
                        'date'        => $ev->event_date->format('l, d M Y'),
                        'time'        => $ev->start_time ? substr($ev->start_time, 0, 5) . ($ev->end_time ? ' - ' . substr($ev->end_time, 0, 5) : '') : 'All Day',
                        'venue'       => $ev->venue,
                        'audience'    => ucfirst($ev->audience),
                        'description' => strip_tags($ev->description ?? ''),
                        'is_assigned' => $ev->is_assigned,
                        'staff_names' => $ev->staff->map(fn($s) => $s->employee?->full_name)->filter()->implode(', '),
                        'view_url'    => route('events.show', $ev->id),
                        'edit_url'    => route('events.edit', $ev->id),
                       ]))"
                       class="text-[11px] font-semibold px-1.5 py-1 rounded-lg border truncate transition flex items-center gap-1 shadow-2xs hover:brightness-95 {{ $typeColors[$ev->event_type] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}"
                       title="{{ $ev->name }}">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $ev->is_assigned ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                    <span class="truncate">{{ $ev->name }}</span>
                  </div>
                @endforeach
              </div>
            </template>

            {{-- Circulars --}}
            <template x-if="filterType === 'all' || filterType === 'circulars'">
              <div class="space-y-1">
                @foreach($dayCirculars->take(1) as $c)
                  <div @click.stop="openItemModal('circular', @js([
                        'id'          => $c->id,
                        'title'       => $c->title,
                        'type'        => 'Circular',
                        'reference_no'=> $c->reference_no,
                        'date'        => $c->publish_date ? $c->publish_date->format('d M Y') : $c->created_at->format('d M Y'),
                        'authority'   => $c->issuing_authority,
                        'signatory'   => $c->signed_by_name,
                        'audience'    => ucfirst($c->target_audience),
                        'priority'    => ucfirst($c->priority),
                        'description' => strip_tags($c->content ?? ''),
                        'has_file'    => (bool)$c->attachment,
                        'view_url'    => route('circulars.show', $c->id),
                        'edit_url'    => route('circulars.edit', $c->id),
                       ]))"
                       class="text-[11px] font-semibold px-1.5 py-1 rounded-lg border truncate transition flex items-center gap-1 shadow-2xs hover:brightness-95 bg-sky-50 text-sky-800 border-sky-200"
                       title="Circular: {{ $c->title }}">
                    <span class="text-[10px]">📜</span>
                    <span class="truncate">{{ $c->title }}</span>
                  </div>
                @endforeach
              </div>
            </template>

            {{-- Official Orders --}}
            <template x-if="filterType === 'all' || filterType === 'orders'">
              <div class="space-y-1">
                @foreach($dayOrders->take(1) as $ord)
                  <div @click.stop="openItemModal('order', @js([
                        'id'          => $ord->id,
                        'title'       => $ord->title,
                        'type'        => 'Official Order',
                        'reference_no'=> $ord->reference_no,
                        'date'        => $ord->publish_date ? $ord->publish_date->format('d M Y') : $ord->created_at->format('d M Y'),
                        'authority'   => $ord->issuing_authority,
                        'signatory'   => $ord->signed_by_name,
                        'audience'    => ucfirst($ord->target_audience),
                        'priority'    => ucfirst($ord->priority),
                        'description' => strip_tags($ord->content ?? ''),
                        'has_file'    => (bool)$ord->attachment,
                        'view_url'    => route('circulars.show', $ord->id),
                        'edit_url'    => route('circulars.edit', $ord->id),
                       ]))"
                       class="text-[11px] font-semibold px-1.5 py-1 rounded-lg border truncate transition flex items-center gap-1 shadow-2xs hover:brightness-95 bg-purple-50 text-purple-800 border-purple-200"
                       title="Order: {{ $ord->title }}">
                    <span class="text-[10px]">⚖️</span>
                    <span class="truncate">{{ $ord->title }}</span>
                  </div>
                @endforeach
              </div>
            </template>

            {{-- More count indicator --}}
            @if($totalDayItems > 3)
              <div class="text-[10px] font-bold text-slate-500 pt-0.5 hover:text-maroon-700">
                +{{ $totalDayItems - 3 }} more &rarr;
              </div>
            @endif

          </div>

          {{-- Quick add on hover --}}
          @if($canCreate)
            <div class="opacity-0 group-hover:opacity-100 transition-opacity pt-1 flex justify-end">
              <span class="text-[10px] font-bold text-maroon-700 bg-maroon-50 px-1.5 py-0.5 rounded-md hover:bg-maroon-100">
                View Date &rarr;
              </span>
            </div>
          @endif
        </div>
      @endforeach
    </div>

    {{-- Comprehensive Calendar Legend --}}
    <div class="mt-6 pt-5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4 text-xs">
      <div class="flex items-center gap-4 flex-wrap">
        <span class="font-bold text-slate-700">Event Types:</span>
        @foreach(['academic' => 'Academic', 'cultural' => 'Cultural', 'sports' => 'Sports', 'holiday' => 'Holiday', 'meeting' => 'Meeting', 'other' => 'Other'] as $k => $label)
          <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-md border flex-shrink-0 {{ $typeColors[$k] }}"></span>
            <span class="text-slate-600 font-medium">{{ $label }}</span>
          </span>
        @endforeach
      </div>

      <div class="flex items-center gap-4 flex-wrap">
        <span class="font-bold text-slate-700">Directives:</span>
        <span class="inline-flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-md bg-sky-100 text-sky-800 border border-sky-300"></span>
          <span class="text-slate-600 font-medium">Circular</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-md bg-purple-100 text-purple-800 border border-purple-300"></span>
          <span class="text-slate-600 font-medium">Official Order</span>
        </span>
        <span class="inline-flex items-center gap-1.5 pl-2 border-l border-slate-200">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span class="text-slate-600 font-medium">Staff Assigned</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-amber-400"></span>
          <span class="text-slate-600 font-medium">Pending Assignment</span>
        </span>
      </div>
    </div>

  </div>

  {{-- Modal 1: Date Summary Drawer / Modal (When date cell is clicked) --}}
  <template x-teleport="body">
    <div x-show="showDayModal"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs overscroll-contain"
         @keydown.escape.window="closeDayModal()"
         @wheel.self.prevent
         @touchmove.self.prevent>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full flex flex-col overflow-hidden animate-fade-in"
           style="max-height: min(520px, 80vh);"
           @click.away="closeDayModal()"
           @click.stop>
        {{-- Modal Header --}}
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 flex-shrink-0">
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Scheduled Records</span>
            <h3 class="text-base sm:text-lg font-black text-slate-900" x-text="activeDayData.date_str"></h3>
          </div>
          <button type="button" @click="closeDayModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition" title="Close (Esc)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        {{-- Modal Body: ONLY THIS SCROLLS --}}
        <div class="p-4 sm:p-5 flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-4">
          {{-- Empty state for selected date --}}
          <template x-if="activeDayData.events.length === 0 && activeDayData.circulars.length === 0 && activeDayData.orders.length === 0">
            <div class="py-8 text-center space-y-2">
              <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <p class="font-bold text-slate-700 text-sm">No scheduled events or circulars</p>
              <p class="text-xs text-slate-400">No institutional activities recorded for this date.</p>
            </div>
          </template>

          {{-- School Events in this date --}}
          <template x-if="activeDayData.events.length > 0">
            <div class="space-y-2">
              <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">School Events (<span x-text="activeDayData.events.length"></span>)</h4>
              <template x-for="ev in activeDayData.events" :key="ev.id">
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition space-y-2 shadow-2xs">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <h5 class="font-bold text-slate-900 text-sm" x-text="ev.name"></h5>
                      <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                        <span class="font-medium" x-text="ev.time"></span>
                        <template x-if="ev.venue">
                          <span>&bull; <span x-text="ev.venue"></span></span>
                        </template>
                      </div>
                    </div>
                    {{-- Assignment Badge --}}
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0"
                          :class="ev.is_assigned ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                      <span x-text="ev.is_assigned ? '✓ Assigned' : '⏳ Pending Assignment'"></span>
                    </span>
                  </div>

                  <template x-if="ev.staff_names">
                    <p class="text-[11px] text-slate-600">
                      <strong class="text-slate-500">In-Charge:</strong> <span x-text="ev.staff_names"></span>
                    </p>
                  </template>

                  <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                    <a :href="ev.view_url" class="btn-xs btn-secondary">View Details</a>
                    @if($canEdit)
                      <a :href="ev.edit_url" class="btn-xs btn-primary">Edit Event</a>
                    @endif
                  </div>
                </div>
              </template>
            </div>
          </template>

          {{-- Circulars & Orders in this date --}}
          <template x-if="activeDayData.circulars.length > 0 || activeDayData.orders.length > 0">
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Circulars &amp; Orders (<span x-text="activeDayData.circulars.length + activeDayData.orders.length"></span>)</h4>
              
              {{-- Circulars --}}
              <template x-for="c in activeDayData.circulars" :key="'c-' + c.id">
                <div class="p-3 rounded-xl border border-sky-200 bg-sky-50/40 space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold bg-white text-sky-800 border border-sky-200" x-text="c.reference_no || 'EPS/CIR/' + c.id"></span>
                      <h5 class="font-bold text-slate-900 text-sm mt-1" x-text="c.title"></h5>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-sky-100 text-sky-800 border border-sky-300 flex-shrink-0">Circular</span>
                  </div>
                  <div class="flex items-center justify-end gap-2 pt-1">
                    <a :href="c.view_url" class="btn-xs btn-secondary">Read Circular</a>
                    @if($canEdit)
                      <a :href="c.edit_url" class="btn-xs btn-primary">Edit</a>
                    @endif
                  </div>
                </div>
              </template>

              {{-- Orders --}}
              <template x-for="o in activeDayData.orders" :key="'o-' + o.id">
                <div class="p-3 rounded-xl border border-purple-200 bg-purple-50/40 space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold bg-white text-purple-800 border border-purple-200" x-text="o.reference_no || 'EPS/ORD/' + o.id"></span>
                      <h5 class="font-bold text-slate-900 text-sm mt-1" x-text="o.title"></h5>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-purple-100 text-purple-800 border border-purple-300 flex-shrink-0">Official Order</span>
                  </div>
                  <div class="flex items-center justify-end gap-2 pt-1">
                    <a :href="o.view_url" class="btn-xs btn-secondary">Read Order</a>
                    @if($canEdit)
                      <a :href="o.edit_url" class="btn-xs btn-primary">Edit</a>
                    @endif
                  </div>
                </div>
              </template>
            </div>
          </template>
        </div>

        {{-- Modal Footer --}}
        <div class="p-3 sm:p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between flex-shrink-0">
          @if($canCreate)
            <a :href="'{{ route('events.create') }}?date=' + activeDayData.date_iso" class="btn-sm btn-primary inline-flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              + Add Event on this Date
            </a>
          @else
            <span></span>
          @endif
          <button type="button" @click="closeDayModal()" class="btn-sm btn-secondary">Close</button>
        </div>
      </div>
    </div>
  </template>

  {{-- Modal 2: Single Item Quick Details & Edit Modal --}}
  <template x-teleport="body">
    <div x-show="showItemModal"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs overscroll-contain"
         @keydown.escape.window="closeItemModal()"
         @wheel.self.prevent
         @touchmove.self.prevent>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full flex flex-col overflow-hidden animate-fade-in"
           style="max-height: min(520px, 80vh);"
           @click.away="closeItemModal()"
           @click.stop>
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 flex-shrink-0">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="activeItem.kind === 'event' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : (activeItem.kind === 'order' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-sky-100 text-sky-800 border border-sky-200')"
                  x-text="activeItem.kind === 'event' ? ('Event: ' + (activeItem.type || 'Activity')) : activeItem.type"></span>
          </div>
          <button type="button" @click="closeItemModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition" title="Close (Esc)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="p-5 flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-4">
          <div>
            <template x-if="activeItem.reference_no">
              <span class="font-mono text-xs font-bold text-slate-500 block mb-1" x-text="activeItem.reference_no"></span>
            </template>
            <h3 class="text-base sm:text-lg font-black text-slate-900 leading-snug break-words" x-text="activeItem.name || activeItem.title"></h3>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
            <div>
              <p class="text-slate-400 font-medium">Scheduled Date</p>
              <p class="font-bold text-slate-800 mt-0.5" x-text="activeItem.date"></p>
            </div>
            <template x-if="activeItem.kind === 'event'">
              <div>
                <p class="text-slate-400 font-medium">Timing</p>
                <p class="font-bold text-slate-800 mt-0.5" x-text="activeItem.time"></p>
              </div>
            </template>
            <template x-if="activeItem.venue">
              <div class="col-span-2">
                <p class="text-slate-400 font-medium">Venue</p>
                <p class="font-bold text-slate-800 mt-0.5" x-text="activeItem.venue"></p>
              </div>
            </template>
            <template x-if="activeItem.authority">
              <div class="col-span-2">
                <p class="text-slate-400 font-medium">Issuing Authority</p>
                <p class="font-bold text-slate-800 mt-0.5" x-text="activeItem.authority + (activeItem.signatory ? ' (' + activeItem.signatory + ')' : '')"></p>
              </div>
            </template>
          </div>

          {{-- Assignment Status for Events --}}
          <template x-if="activeItem.kind === 'event'">
            <div class="p-3 rounded-xl border"
                 :class="activeItem.is_assigned ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50'">
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold" :class="activeItem.is_assigned ? 'text-emerald-800' : 'text-amber-800'">
                  <span x-text="activeItem.is_assigned ? '✓ Staff Assigned' : '⏳ Pending Assignment'"></span>
                </span>
                <template x-if="!activeItem.is_assigned">
                  <a :href="activeItem.edit_url + '#assignment-section'" class="font-bold text-maroon-700 hover:underline">Assign Now &rarr;</a>
                </template>
              </div>
              <template x-if="activeItem.staff_names">
                <p class="text-xs text-slate-700 mt-1 font-medium" x-text="'In-Charge: ' + activeItem.staff_names"></p>
              </template>
            </div>
          </template>

          <template x-if="activeItem.description">
            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed break-words" x-text="activeItem.description"></p>
          </template>
        </div>

        {{-- Modal Actions --}}
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between flex-shrink-0">
          <a :href="activeItem.view_url" class="btn-sm btn-secondary">
            View Full Details
          </a>
          <div class="flex items-center gap-2">
            @if($canEdit)
              <a :href="activeItem.edit_url" class="btn-sm btn-primary inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Edit Record
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </template>

</div>

<script>
function calendarApp() {
  return {
    filterType: 'all',
    showDayModal: false,
    showItemModal: false,
    _savedBodyOverflow: '',
    _savedMainOverflow: '',
    _savedMainScrollTop: 0,
    activeDayData: {
      date_str: '',
      date_iso: '',
      events: [],
      circulars: [],
      orders: []
    },
    activeItem: {
      kind: '',
      type: '',
      name: '',
      title: '',
      date: '',
      time: '',
      venue: '',
      description: '',
      view_url: '',
      edit_url: '',
      is_assigned: false,
      staff_names: '',
      authority: '',
      signatory: '',
      reference_no: ''
    },
    init() {
      // Watch modal open states and lock/unlock background scroll
      this.$watch('showDayModal', () => this.syncScrollLock());
      this.$watch('showItemModal', () => this.syncScrollLock());

      // Cleanup on page navigation
      window.addEventListener('beforeunload', () => {
        this.unlockScroll();
      });
    },
    syncScrollLock() {
      if (this.showDayModal || this.showItemModal) {
        this.lockScroll();
      } else {
        this.unlockScroll();
      }
    },
    lockScroll() {
      const mainEl = document.querySelector('main');
      if (mainEl && mainEl.style.overflow !== 'hidden') {
        this._savedMainOverflow = mainEl.style.overflow;
        this._savedMainScrollTop = mainEl.scrollTop;
        mainEl.style.overflow = 'hidden';
      }
      if (document.body.style.overflow !== 'hidden') {
        this._savedBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
      }
    },
    unlockScroll() {
      const mainEl = document.querySelector('main');
      if (mainEl) {
        mainEl.style.overflow = this._savedMainOverflow || '';
        if (typeof this._savedMainScrollTop === 'number') {
          mainEl.scrollTop = this._savedMainScrollTop;
        }
      }
      document.body.style.overflow = this._savedBodyOverflow || '';
    },
    openDayModal(dayData) {
      this.activeDayData = dayData;
      this.showDayModal = true;
    },
    closeDayModal() {
      this.showDayModal = false;
      this.syncScrollLock();
    },
    openItemModal(kind, itemData) {
      this.activeItem = Object.assign({ kind }, itemData);
      this.showItemModal = true;
    },
    closeItemModal() {
      this.showItemModal = false;
      this.syncScrollLock();
    }
  };
}
</script>
@endsection
