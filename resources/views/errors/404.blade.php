@extends('layouts.app')
@section('title', 'Page Not Found — 404')
@section('content')
<div class="flex items-center justify-center min-h-[70vh]">
  <div class="text-center max-w-md p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
    <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center mx-auto text-amber-600 shadow-2xs">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>

    <div>
      <h1 class="text-3xl font-black text-slate-900 tracking-tight">404 — Record Not Found</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        The requested record or page could not be located or has been moved.
      </p>
    </div>

    <div class="flex items-center justify-center gap-3 pt-2">
      <a href="javascript:history.back()" class="btn btn-secondary text-xs font-bold px-4 py-2">
        Go Back
      </a>
      <a href="{{ route('dashboard') }}" class="btn btn-primary text-xs font-bold px-4 py-2 bg-[#892528] hover:bg-[#721f22] text-white">
        Dashboard
      </a>
    </div>
  </div>
</div>
@endsection
