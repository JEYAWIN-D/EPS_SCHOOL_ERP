@extends('layouts.app')
@section('title', 'Server Error — 500')
@section('content')
<div class="flex items-center justify-center min-h-[70vh]">
  <div class="text-center max-w-lg p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
    <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center mx-auto text-rose-600 shadow-2xs">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
    </div>

    <div>
      <h1 class="text-3xl font-black text-slate-900 tracking-tight">System Encountered an Issue</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        {{ $message ?? 'An unexpected error occurred while processing this request.' }}
      </p>
    </div>

    @if(!empty($correlationId))
    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-center">
      <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Error Reference Code</span>
      <span class="font-mono font-bold text-sm text-indigo-600">{{ $correlationId }}</span>
      <p class="text-[10px] text-slate-400 mt-0.5">Please share this reference code with IT Support if the problem persists.</p>
    </div>
    @endif

    <div class="flex items-center justify-center gap-3 pt-2">
      <button type="button" onclick="window.location.reload()" class="btn btn-secondary text-xs font-bold px-4 py-2">
        Try Refreshing
      </button>
      <a href="{{ route('dashboard') }}" class="btn btn-primary text-xs font-bold px-4 py-2 bg-[#892528] hover:bg-[#721f22] text-white">
        Return to Dashboard
      </a>
    </div>
  </div>
</div>
@endsection

