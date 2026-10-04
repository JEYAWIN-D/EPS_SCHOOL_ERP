@extends('layouts.admin')

@section('title', 'Task Management Desk')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Task Desk</span>
@endsection

@section('content')
<div class="space-y-6 pb-12">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Tasks & Departmental Assignments</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Track assignments, follow-up actions, progress updates, review submissions, and overdue conditions.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="{{ route('activities.tasks.create') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl shadow-md transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Task
      </a>
    </div>
  </div>

  {{-- Stats Bar --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-slate-400 font-medium">Total Tasks</p>
      <p class="text-2xl font-black text-slate-800 mt-1">{{ $totalTasks }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-indigo-600 font-medium">Assigned to Me</p>
      <p class="text-2xl font-black text-indigo-700 mt-1">{{ $myPendingTasks }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-rose-600 font-medium">Overdue Condition</p>
      <p class="text-2xl font-black text-rose-600 mt-1">{{ $overdueCount }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
      <p class="text-xs text-amber-600 font-medium">Awaiting Review</p>
      <p class="text-2xl font-black text-amber-600 mt-1">{{ $inReviewCount }}</p>
    </div>
  </div>

  {{-- Filters Bar --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('activities.tasks.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('filter') && !request('status') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        All Tasks
      </a>
      <a href="{{ route('activities.tasks.index', ['filter' => 'assigned_to_me']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('filter') === 'assigned_to_me' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Assigned to Me
      </a>
      <a href="{{ route('activities.tasks.index', ['status' => 'overdue']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'overdue' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Overdue
      </a>
      <a href="{{ route('activities.tasks.index', ['status' => 'submitted_for_review']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'submitted_for_review' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Under Review
      </a>
      <a href="{{ route('activities.tasks.index', ['status' => 'completed']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Completed
      </a>
    </div>

    <form method="GET" action="{{ route('activities.tasks.index') }}" class="flex items-center gap-2">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tasks..." class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden w-48 sm:w-64">
      <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
        Filter
      </button>
    </form>
  </div>

  {{-- Tasks List --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs divide-y divide-slate-100">
    @forelse($tasks as $task)
      <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/70 transition">
        <div class="flex items-start gap-3.5 min-w-0">
          <div class="mt-1 flex-shrink-0">
            @if($task->is_overdue)
              <span class="w-3.5 h-3.5 rounded-full bg-rose-500 inline-block ring-4 ring-rose-100" title="Overdue Condition"></span>
            @elseif($task->status === 'completed')
              <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 inline-block ring-4 ring-emerald-100" title="Completed"></span>
            @elseif($task->priority === 'urgent')
              <span class="w-3.5 h-3.5 rounded-full bg-amber-500 inline-block ring-4 ring-amber-100" title="Urgent"></span>
            @else
              <span class="w-3.5 h-3.5 rounded-full bg-indigo-500 inline-block ring-4 ring-indigo-100"></span>
            @endif
          </div>

          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <a href="{{ route('activities.tasks.show', $task->id) }}" class="text-sm font-bold text-slate-900 hover:text-indigo-600 transition">
                {{ $task->title }}
              </a>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                {{ $task->priority === 'urgent' ? 'bg-red-50 text-red-700' : ($task->priority === 'high' ? 'bg-orange-50 text-orange-700' : 'bg-slate-100 text-slate-600') }}">
                {{ $task->priority }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-medium capitalize
                {{ $task->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($task->status === 'submitted_for_review' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
                {{ str_replace('_', ' ', $task->status) }}
              </span>
              @if($task->is_overdue)
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                  Overdue
                </span>
              @endif
            </div>

            <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $task->description ?? 'No extra description.' }}</p>

            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-400 mt-2">
              <span>Assignee: <strong class="text-slate-700">{{ $task->assignees->first()?->user?->name ?? 'Unassigned' }}</strong></span>
              <span>&bull;</span>
              <span>Due: <strong class="text-slate-700">{{ $task->due_date ? $task->due_date->format('d M Y') : 'Open' }}</strong></span>
              @if($task->department)
                <span>&bull;</span>
                <span>Dept: <strong class="text-slate-700">{{ $task->department->name }}</strong></span>
              @endif
              <span>&bull;</span>
              <span>Updates: {{ $task->updates_count }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-center">
          <a href="{{ route('activities.tasks.show', $task->id) }}" class="btn-sm rounded-xl bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 font-semibold px-3 py-1.5 text-xs transition">
            Details & Updates &rarr;
          </a>
        </div>
      </div>
    @empty
      <div class="p-12 text-center text-slate-400 text-xs">
        No tasks matching criteria.
      </div>
    @endforelse
  </div>

  <div class="mt-4">
    {{ $tasks->links() }}
  </div>

</div>
@endsection
