@extends('layouts.admin')

@section('title', 'My Day — Activity Cockpit')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">My Day</span>
@endsection

@section('content')
<div class="space-y-6 pb-12" x-data="{ activeTab: 'schedule' }">

  {{-- Hero Header --}}
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-indigo-900 text-white p-6 sm:p-8 shadow-xl">
    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-blue-100 text-xs font-semibold backdrop-blur-md mb-2">
          <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
          <span>{{ now()->format('l, d F Y') }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Good day, {{ auth()->user()->name }}!</h1>
        <p class="text-blue-100 text-sm mt-1 max-w-xl">Welcome to your personal activity cockpit. Track today's meetings, assigned deadlines, school events, and announcements in one place.</p>
      </div>
      <div class="flex items-center gap-2.5 flex-shrink-0">
        <a href="{{ route('activities.calendar') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-semibold backdrop-blur-sm transition border border-white/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Unified Calendar
        </a>
        <a href="{{ route('activities.tasks.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-indigo-700 hover:bg-slate-50 text-xs font-bold shadow-md transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          New Task
        </a>
      </div>
    </div>

    {{-- Quick KPI Metric Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-6 pt-5 border-t border-white/15">
      <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
        <p class="text-xs text-blue-200">Today's Meetings</p>
        <p class="text-xl font-black mt-0.5">{{ $meetings->count() }}</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
        <p class="text-xs text-blue-200">School Events</p>
        <p class="text-xl font-black mt-0.5">{{ $events->count() }}</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
        <p class="text-xs text-blue-200">Pending Tasks</p>
        <p class="text-xl font-black mt-0.5">{{ $pendingTasks->count() }}</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
        <p class="text-xs text-red-200">Overdue Tasks</p>
        <p class="text-xl font-black text-rose-300 mt-0.5">{{ $overdueTasks->count() }}</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs col-span-2 sm:col-span-1">
        <p class="text-xs text-blue-200">Unread Notices</p>
        <p class="text-xl font-black text-amber-300 mt-0.5">{{ $unreadNotices->count() }}</p>
      </div>
    </div>
  </div>

  {{-- Navigation Tabs --}}
  <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
    <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Today's Schedule ({{ $meetings->count() + $events->count() }})</span>
    </button>
    <button @click="activeTab = 'tasks'" :class="activeTab === 'tasks' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      <span>My Tasks ({{ $pendingTasks->count() + $overdueTasks->count() }})</span>
      @if($overdueTasks->count() > 0)
        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-red-100 text-red-700 font-bold">{{ $overdueTasks->count() }}</span>
      @endif
    </button>
    <button @click="activeTab = 'notices'" :class="activeTab === 'notices' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
      <span>Notices & Circulars</span>
      @if($unreadNotices->count() > 0)
        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold">{{ $unreadNotices->count() }} new</span>
      @endif
    </button>
  </div>

  {{-- TAB 1: SCHEDULE (Meetings & Events) --}}
  <div x-show="activeTab === 'schedule'" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      {{-- Meetings Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-800">Today's Meetings</h2>
              <p class="text-xs text-slate-400">Convened or attending</p>
            </div>
          </div>
          <a href="{{ route('activities.meetings.create') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">+ Schedule</a>
        </div>

        @forelse($meetings as $meeting)
          <div class="p-3.5 mb-2.5 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-slate-50/70 transition flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 uppercase tracking-wider">{{ $meeting->meeting_type }}</span>
                <span class="text-xs text-slate-500 font-medium">{{ $meeting->start_time->format('h:i A') }} - {{ $meeting->end_time->format('h:i A') }}</span>
              </div>
              <h3 class="text-sm font-semibold text-slate-800 mt-1 truncate">{{ $meeting->title }}</h3>
              <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ $meeting->venue ?? 'Online / Campus' }}</span>
              </p>
            </div>
            <a href="{{ route('activities.meetings.show', $meeting->id) }}" class="btn-xs rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 font-semibold px-2.5 py-1 text-xs">
              View
            </a>
          </div>
        @empty
          <div class="text-center py-8">
            <p class="text-xs text-slate-400">No meetings scheduled for today.</p>
          </div>
        @endforelse
      </div>

      {{-- Events Card --}}
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-800">Today's School Events</h2>
              <p class="text-xs text-slate-400">Functions, sports & competitions</p>
            </div>
          </div>
          <a href="{{ route('activities.events.create') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">+ Add Event</a>
        </div>

        @forelse($events as $event)
          <div class="p-3.5 mb-2.5 rounded-xl border border-slate-100 hover:border-emerald-200 hover:bg-slate-50/70 transition flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 uppercase tracking-wider">{{ $event->event_type }}</span>
                <span class="text-xs text-slate-500 font-medium">{{ $event->start_time ? date('h:i A', strtotime($event->start_time)) : 'Full Day' }}</span>
              </div>
              <h3 class="text-sm font-semibold text-slate-800 mt-1 truncate">{{ $event->name }}</h3>
              <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ $event->venue ?? 'School Campus' }}</span>
              </p>
            </div>
            <a href="{{ route('activities.events.show', $event->id) }}" class="btn-xs rounded-lg bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600 text-slate-700 font-semibold px-2.5 py-1 text-xs">
              Details
            </a>
          </div>
        @empty
          <div class="text-center py-8">
            <p class="text-xs text-slate-400">No school events happening today.</p>
          </div>
        @endforelse
      </div>

    </div>
  </div>

  {{-- TAB 2: TASKS --}}
  <div x-show="activeTab === 'tasks'" class="space-y-4" style="display:none">
    {{-- Overdue Tasks Warning Banner --}}
    @if($overdueTasks->count() > 0)
      <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
        <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
          <h4 class="text-xs font-bold text-rose-900">You have {{ $overdueTasks->count() }} overdue task(s) requiring immediate attention!</h4>
          <p class="text-xs text-rose-700 mt-0.5">Please update progress or submit work for administrative sign-off.</p>
        </div>
      </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs divide-y divide-slate-100">
      @forelse($overdueTasks->concat($pendingTasks) as $task)
        <div class="p-4 flex items-center justify-between gap-4 hover:bg-slate-50/70 transition">
          <div class="flex items-start gap-3 min-w-0">
            <div class="mt-1">
              @if($task->is_overdue)
                <span class="w-3 h-3 rounded-full bg-rose-500 inline-block ring-4 ring-rose-100" title="Overdue"></span>
              @elseif($task->priority === 'urgent')
                <span class="w-3 h-3 rounded-full bg-amber-500 inline-block ring-4 ring-amber-100" title="Urgent"></span>
              @else
                <span class="w-3 h-3 rounded-full bg-indigo-500 inline-block ring-4 ring-indigo-100" title="Normal"></span>
              @endif
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800 truncate">{{ $task->title }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                  {{ $task->priority === 'urgent' ? 'bg-red-50 text-red-700' : ($task->priority === 'high' ? 'bg-orange-50 text-orange-700' : 'bg-slate-100 text-slate-600') }}">
                  {{ $task->priority }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 capitalize">
                  {{ str_replace('_', ' ', $task->status) }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $task->description ?? 'No description provided.' }}</p>
              <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-1">
                <span>Due: {{ $task->due_date ? $task->due_date->format('d M Y') : 'No deadline' }}</span>
                <span>&bull;</span>
                <span>Assigned by: {{ $task->creator?->name }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('activities.tasks.show', $task->id) }}" class="btn-sm rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold px-3 py-1.5 text-xs transition">
              Update Progress
            </a>
          </div>
        </div>
      @empty
        <div class="text-center py-12">
          <p class="text-xs text-slate-400">All tasks completed! You're completely up to date.</p>
        </div>
      @endforelse
    </div>
  </div>

  {{-- TAB 3: NOTICES --}}
  <div x-show="activeTab === 'notices'" class="space-y-4" style="display:none">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      @forelse($unreadNotices as $notice)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between hover:shadow-md transition">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                {{ $notice->priority === 'urgent' ? 'bg-red-100 text-red-800' : ($notice->priority === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                {{ $notice->priority }} Priority
              </span>
              <span class="text-xs text-slate-400">{{ $notice->publish_date->format('d M Y') }}</span>
            </div>
            <h3 class="text-sm font-bold text-slate-900 leading-snug">{{ $notice->title }}</h3>
            <p class="text-xs text-slate-600 mt-2 line-clamp-3">{{ $notice->content }}</p>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-400">From: {{ $notice->createdBy?->name }}</span>
            <a href="{{ route('activities.notices.show', $notice->id) }}" class="btn-xs rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-3 py-1.5 text-xs transition">
              Acknowledge & Read
            </a>
          </div>
        </div>
      @empty
        <div class="col-span-2 text-center py-12 bg-white rounded-2xl border border-slate-200">
          <p class="text-xs text-slate-400">No unacknowledged notices for you.</p>
        </div>
      @endforelse
    </div>
  </div>

</div>
@endsection
