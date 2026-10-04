@extends('layouts.admin')

@section('title', 'Meetings Management')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Meetings</span>
@endsection

@section('content')
<div class="space-y-6 pb-12">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Staff & Department Meetings</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Convene academic reviews, HOD sessions, parent-teacher conferences, and record Minutes of Meeting (MoM).</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="{{ route('activities.meetings.create') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl shadow-md transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Schedule Meeting
      </a>
    </div>
  </div>

  {{-- Stats Bar --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-slate-400 font-medium">Total Meetings</p>
      <p class="text-2xl font-black text-slate-800 mt-1">{{ $meetings->total() }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-indigo-600 font-medium">Upcoming Scheduled</p>
      <p class="text-2xl font-black text-indigo-700 mt-1">{{ $upcomingCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-emerald-600 font-medium">Completed Meetings</p>
      <p class="text-2xl font-black text-emerald-700 mt-1">{{ $completedCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-slate-400 font-medium">Calendar Overlay</p>
      <a href="{{ route('activities.calendar') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 block mt-2">View in Calendar &rarr;</a>
    </div>
  </div>

  {{-- Meetings Table --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200/70 text-slate-500 uppercase font-semibold text-[10px] tracking-wider">
            <th class="py-3.5 px-4">Meeting Title & Agenda</th>
            <th class="py-3.5 px-4">Type</th>
            <th class="py-3.5 px-4">Date & Time</th>
            <th class="py-3.5 px-4">Venue / Link</th>
            <th class="py-3.5 px-4">Chairperson</th>
            <th class="py-3.5 px-4">Attendees</th>
            <th class="py-3.5 px-4">Status</th>
            <th class="py-3.5 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($meetings as $meeting)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3.5 px-4">
                <a href="{{ route('activities.meetings.show', $meeting->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition block text-sm">
                  {{ $meeting->title }}
                </a>
                <span class="text-slate-400 text-[11px] line-clamp-1 mt-0.5">{{ $meeting->agenda ?? 'No agenda specified.' }}</span>
              </td>
              <td class="py-3.5 px-4">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700">
                  {{ $meeting->meeting_type }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span class="font-semibold text-slate-700 block">{{ $meeting->start_time->format('d M Y') }}</span>
                <span class="text-[11px] text-slate-400">{{ $meeting->start_time->format('h:i A') }} - {{ $meeting->end_time->format('h:i A') }}</span>
              </td>
              <td class="py-3.5 px-4 text-slate-600">
                {{ $meeting->venue ?? ($meeting->meeting_link ? 'Online Link' : 'Campus') }}
              </td>
              <td class="py-3.5 px-4 text-slate-700 font-medium">
                {{ $meeting->chairperson?->name ?? 'Principal' }}
              </td>
              <td class="py-3.5 px-4 text-slate-500">
                {{ $meeting->attendees_count }} Invited
              </td>
              <td class="py-3.5 px-4">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                  {{ $meeting->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($meeting->status === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700') }}">
                  {{ $meeting->status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <a href="{{ route('activities.meetings.show', $meeting->id) }}" class="btn-xs rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 font-semibold px-2.5 py-1 text-xs">
                  Desk &rarr;
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-8 text-center text-slate-400">No meetings scheduled.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-4">
    {{ $meetings->links() }}
  </div>

</div>
@endsection
