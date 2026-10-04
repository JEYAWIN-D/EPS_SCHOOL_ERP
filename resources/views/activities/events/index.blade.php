@extends('layouts.admin')

@section('title', 'School Events Management')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Events</span>
@endsection

@section('content')
<div class="space-y-6 pb-12">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Events & Activities</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage school functions, sports meets, cultural exhibitions, and in-charge staff appointments.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="{{ route('activities.events.create') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl shadow-md transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Event
      </a>
    </div>
  </div>

  {{-- Stats Bar --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-slate-400 font-medium">Total Events</p>
      <p class="text-2xl font-black text-slate-800 mt-1">{{ $totalEvents }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-emerald-600 font-medium">Upcoming Events</p>
      <p class="text-2xl font-black text-emerald-700 mt-1">{{ $upcomingEvents }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-indigo-600 font-medium">This Month</p>
      <p class="text-2xl font-black text-indigo-700 mt-1">{{ $thisMonthCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-slate-400 font-medium">Calendar Sync</p>
      <a href="{{ route('activities.calendar') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 block mt-2">Open Calendar &rarr;</a>
    </div>
  </div>

  {{-- Events Grid --}}
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($events as $event)
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col justify-between overflow-hidden">
        <div class="p-5">
          <div class="flex items-center justify-between gap-2 mb-2.5">
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
              {{ $event->event_type === 'sports' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($event->event_type === 'academic' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-pink-50 text-pink-700 border border-pink-200') }}">
              {{ $event->event_type }}
            </span>
            <span class="text-xs text-slate-500 font-semibold flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              {{ $event->event_date->format('d M Y') }}
            </span>
          </div>

          <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
            <a href="{{ route('activities.events.show', $event->id) }}" class="hover:text-indigo-600 transition">
              {{ $event->name }}
            </a>
          </h3>

          <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed">
            {{ $event->description ?? 'No detailed description provided.' }}
          </p>

          <div class="mt-4 space-y-1.5 text-xs text-slate-600">
            <div class="flex items-center gap-2">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <span class="truncate">{{ $event->venue ?? 'School Ground' }}</span>
            </div>
            @if($event->staff->count() > 0)
              <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="truncate">In-Charge: {{ $event->staff->first()->employee?->full_name ?? 'Assigned Staff' }}</span>
              </div>
            @endif
          </div>
        </div>

        <div class="px-5 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
          <span class="text-slate-400">{{ $event->allow_rsvp ? $event->rsvps_count . ' RSVPs' : 'Open Attendance' }}</span>
          <div class="flex items-center gap-3">
            <a href="{{ route('activities.events.show', $event->id) }}" class="font-bold text-indigo-600 hover:text-indigo-800 transition">
              Manage &rarr;
            </a>
            @if(auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']))
              <form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Permanently delete \'{{ addslashes($event->name) }}\'?')" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold transition" title="Delete Event">
                  Delete
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <p class="text-sm text-slate-500">No events found.</p>
      </div>
    @endforelse
  </div>

  <div class="mt-4">
    {{ $events->links() }}
  </div>

</div>
@endsection
