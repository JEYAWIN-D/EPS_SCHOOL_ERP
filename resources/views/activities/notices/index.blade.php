@extends('layouts.admin')

@section('title', 'Notice Board & Announcements')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Notice Board</span>
@endsection

@section('content')
<div class="space-y-6 pb-12">

  {{-- Header with action --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Notice Board & Circulars</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage official announcements, academic circulars, urgent alerts, and acknowledgement tracking.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="{{ route('activities.notices.create') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl shadow-md transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Notice
      </a>
    </div>
  </div>

  {{-- Filter bar --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('activities.notices.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('priority') && !request('status') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
        All Notices ({{ $totalNotices }})
      </a>
      <a href="{{ route('activities.notices.index', ['priority' => 'urgent']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('priority') === 'urgent' ? 'bg-red-50 text-red-700 border border-red-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Urgent Alerts ({{ $urgentNotices }})
      </a>
      <a href="{{ route('activities.notices.index', ['status' => 'pending_approval']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'pending_approval' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'text-slate-600 hover:bg-slate-50' }}">
        Pending Approval ({{ $pendingCount }})
      </a>
    </div>

    {{-- Search Form --}}
    <form method="GET" action="{{ route('activities.notices.index') }}" class="flex items-center gap-2">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search notices..." class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden w-48 sm:w-64">
      <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
        Filter
      </button>
    </form>
  </div>

  {{-- Notices Grid --}}
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($notices as $notice)
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col justify-between overflow-hidden">
        <div class="p-5">
          <div class="flex items-center justify-between gap-2 mb-2.5">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
              {{ $notice->priority === 'urgent' ? 'bg-rose-100 text-rose-800 border border-rose-200' : ($notice->priority === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
              {{ $notice->priority }} Priority
            </span>
            <span class="text-[11px] text-slate-400 font-medium">
              {{ $notice->publish_date->format('d M Y') }}
            </span>
          </div>

          <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
            <a href="{{ route('activities.notices.show', $notice->id) }}" class="hover:text-indigo-600 transition">
              {{ $notice->title }}
            </a>
          </h3>

          <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">
            {{ $notice->content }}
          </p>

          <div class="flex flex-wrap items-center gap-2 mt-4 text-[11px]">
            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium capitalize">
              Target: {{ $notice->target_audience }}
            </span>
            @if($notice->requires_acknowledgement)
              <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium">
                Ack Required
              </span>
            @endif
            @if($notice->status === 'pending_approval')
              <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium">
                Awaiting Approval
              </span>
            @endif
          </div>
        </div>

        <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
          <div class="flex items-center gap-3 text-slate-400">
            <span title="Acknowledgements">✓ {{ $notice->acknowledgements_count }} Acks</span>
            <span>&bull;</span>
            <span title="Author">{{ $notice->createdBy?->name ?? 'Admin' }}</span>
          </div>
          <a href="{{ route('activities.notices.show', $notice->id) }}" class="font-bold text-indigo-600 hover:text-indigo-800 transition">
            View Notice &rarr;
          </a>
        </div>
      </div>
    @empty
      <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <p class="text-sm text-slate-500">No notices found matching the criteria.</p>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  <div class="mt-4">
    {{ $notices->links() }}
  </div>

</div>
@endsection
