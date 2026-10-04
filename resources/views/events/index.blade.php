@extends('layouts.app')
@section('title', 'Events & Calendar')
@section('content')
<div class="space-y-6 pb-12">

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

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="page-title text-2xl font-extrabold text-slate-900 tracking-tight">Events &amp; School Activities</h1>
      <p class="page-subtitle text-xs sm:text-sm text-slate-500 mt-1">
        Manage present, upcoming and completed functions, track participant RSVPs and schedules.
      </p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('circulars.index') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Circulars &amp; Orders
      </a>
      <a href="{{ route('events.calendar') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-xs">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Calendar View
      </a>
      <a href="{{ route('events.create') }}" class="btn btn-primary inline-flex items-center gap-1.5 shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Event
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm shadow-xs flex items-center justify-between">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  {{-- KPI Cards --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <a href="{{ route('events.index') }}" class="card hover:border-slate-300 transition group">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-slate-800">{{ $totalEvents }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Total Events</p>
    </a>

    <a href="{{ route('events.index', ['status' => 'today']) }}" class="card hover:border-amber-300 transition group {{ request('status') === 'today' ? 'ring-2 ring-amber-500' : '' }}">
      <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-500 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-amber-600">{{ $todayEvents }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Present / Today</p>
    </a>

    <a href="{{ route('events.index', ['status' => 'upcoming']) }}" class="card hover:border-emerald-300 transition group {{ request('status') === 'upcoming' ? 'ring-2 ring-emerald-500' : '' }}">
      <div class="stat-icon bg-gradient-to-br from-green-500 to-emerald-600 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-emerald-600">{{ $upcomingCount }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Upcoming</p>
    </a>

    <a href="{{ route('events.index', ['status' => 'completed']) }}" class="card hover:border-purple-300 transition group {{ in_array(request('status'), ['completed','past']) ? 'ring-2 ring-purple-500' : '' }}">
      <div class="stat-icon bg-gradient-to-br from-violet-500 to-purple-600 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-purple-700">{{ $completedCount ?? 0 }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Completed</p>
    </a>
  </div>

  {{-- Status Tabs --}}
  <div class="flex items-center justify-between flex-wrap gap-3 pt-2 border-b border-slate-200">
    <div class="flex items-center gap-2 flex-wrap -mb-px">
      @php
        $currentStatus = request('status', '');
        $tabs = [
          ''          => ['label' => 'All Events',        'count' => $totalEvents],
          'today'     => ['label' => 'Present / Today',   'count' => $todayEvents],
          'upcoming'  => ['label' => 'Upcoming',          'count' => $upcomingCount],
          'completed' => ['label' => 'Completed / Past',  'count' => $completedCount ?? 0],
          'draft'     => ['label' => 'Drafts',            'count' => null],
        ];
      @endphp

      @foreach($tabs as $val => $tab)
        @php
          $isActive = ($currentStatus === $val) || ($val === 'completed' && $currentStatus === 'past');
        @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => $val ?: null]) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition select-none {{ $isActive ? 'border-maroon-700 text-maroon-800 bg-maroon-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
          <span>{{ $tab['label'] }}</span>
          @if($tab['count'] !== null)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $isActive ? 'bg-maroon-700 text-white' : 'bg-slate-100 text-slate-600' }}">
              {{ $tab['count'] }}
            </span>
          @endif
        </a>
      @endforeach
    </div>
  </div>

  {{-- Filters Bar --}}
  <form method="GET" action="{{ route('events.index') }}" class="card flex flex-wrap gap-4 items-end bg-slate-50/50 p-4 rounded-xl border border-slate-200">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    
    <div class="flex-1 min-w-[200px]">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Search Events</label>
      <input type="text" name="search" value="{{ request('search') }}" class="input w-full" placeholder="Search by name, description, venue…">
    </div>

    <div class="w-44">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Event Type</label>
      <select name="type" class="select w-full">
        <option value="">All Types</option>
        @foreach(['academic'=>'Academic','cultural'=>'Cultural','sports'=>'Sports','holiday'=>'Holiday','meeting'=>'Meeting','other'=>'Other'] as $t => $label)
          <option value="{{ $t }}" @selected(request('type') === $t)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="w-36">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From</label>
      <input type="date" name="from" value="{{ request('from') }}" class="input w-full">
    </div>

    <div class="w-36">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">To</label>
      <input type="date" name="to" value="{{ request('to') }}" class="input w-full">
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary btn-sm px-4">Filter</button>
      <a href="{{ route('events.index') }}" class="btn-sm btn-secondary">Reset</a>
    </div>
  </form>

  {{-- Events Grid --}}
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse($events as $event)
      @php
        $typeColors = [
          'academic' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
          'cultural' => 'bg-pink-100 text-pink-800 border-pink-200',
          'sports'   => 'bg-emerald-100 text-emerald-800 border-emerald-200',
          'holiday'  => 'bg-amber-100 text-amber-800 border-amber-200',
          'meeting'  => 'bg-blue-100 text-blue-800 border-blue-200',
          'other'    => 'bg-slate-100 text-slate-700 border-slate-200',
        ];
        $isToday = $event->event_date->isToday();
        $isPast = $event->event_date->isPast() && !$isToday;
        $user = auth()->user();
        $canDelete = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']);
      @endphp

      <div class="card hover:shadow-md transition flex flex-col justify-between border {{ $isToday ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200/90' }} rounded-2xl p-5 bg-white space-y-4">
        <div>
          @if($event->banner_image)
            <div class="w-full h-36 rounded-xl overflow-hidden mb-3 border border-slate-100 shadow-2xs">
              <img src="{{ Storage::url($event->banner_image) }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
            </div>
          @endif

          <div class="flex items-start justify-between gap-2">
            <h3 class="font-bold text-slate-900 leading-snug line-clamp-2 text-base">
              <a href="{{ route('events.show', $event->id) }}" class="hover:text-maroon-700 transition">
                {{ $event->name }}
              </a>
            </h3>
            <span class="text-[11px] px-2.5 py-0.5 rounded-full font-bold border flex-shrink-0 {{ $typeColors[$event->event_type] ?? 'bg-slate-100 text-slate-600' }}">
              {{ ucfirst($event->event_type) }}
            </span>
          </div>

          <div class="mt-2.5 flex items-center gap-2 text-xs font-semibold text-slate-600">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>{{ $event->event_date->format('d M Y') }}</span>
            @if($event->start_time)
              <span class="text-slate-300">&bull;</span>
              <span>{{ substr($event->start_time, 0, 5) }}</span>
            @endif
            @if($isToday)
              <span class="px-1.5 py-0.5 rounded-md bg-amber-500 text-white font-extrabold text-[10px] tracking-wide uppercase">Today</span>
            @elseif($isPast)
              <span class="px-1.5 py-0.5 rounded-md bg-slate-200 text-slate-600 font-extrabold text-[10px] tracking-wide uppercase">Completed</span>
            @endif
          </div>

          @if($event->venue)
            <div class="mt-1.5 flex items-center gap-2 text-xs text-slate-500">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <span class="truncate">{{ $event->venue }}</span>
            </div>
          @endif

          @if($event->description)
            <p class="text-xs text-slate-500 mt-2.5 line-clamp-2 leading-relaxed">
              {{ strip_tags($event->description) }}
            </p>
          @endif
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
          {{-- Status / RSVP Badges --}}
          <div class="flex items-center gap-1.5 flex-wrap">
            @if($event->is_published)
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Published</span>
            @else
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Draft</span>
            @endif

            @if($event->allow_rsvp)
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">RSVP</span>
            @endif
          </div>

          {{-- Action Buttons: View, Edit, Delete --}}
          <div class="flex items-center gap-1.5">
            <a href="{{ route('events.show', $event->id) }}"
               class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition shadow-2xs">
              View
            </a>
            
            <a href="{{ route('events.edit', $event->id) }}"
               class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition shadow-2xs">
              Edit
            </a>

            @if($canDelete)
              <form method="POST"
                    action="{{ route('events.destroy', $event->id) }}"
                    onsubmit="return confirm('Are you sure you want to permanently delete event \'{{ addslashes($event->name) }}\'? This cannot be undone.')"
                    class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition shadow-2xs inline-flex items-center gap-1"
                        title="Delete Event">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  Delete
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="col-span-full card text-center py-16 bg-white rounded-2xl border border-slate-200">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <p class="font-bold text-slate-700">No events found in this category.</p>
        <p class="text-xs text-slate-400 mt-1">Try switching tabs or schedule a new event.</p>
        <div class="mt-4">
          <a href="{{ route('events.create') }}" class="btn-primary btn-sm inline-flex items-center gap-1.5">
            + Schedule Event
          </a>
        </div>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  <div class="pt-4">
    {{ $events->withQueryString()->links() }}
  </div>
</div>
@endsection
