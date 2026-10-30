@extends('layouts.app')
@section('title', 'Access Denied')
@section('content')
<div class="flex items-center justify-center min-h-[70vh]">
  <div class="text-center max-w-md">
    <div class="w-20 h-20 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-6">
      <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    </div>
    <h1 class="text-4xl font-bold text-slate-800 mb-2">403</h1>
    <h2 class="text-xl font-semibold text-slate-700 mb-3">Access Denied</h2>
    <p class="text-slate-500 mb-2">You don't have permission to access this page.</p>
    <p class="text-sm text-slate-400 mb-8">
      Your current role:
      <span class="font-semibold text-slate-600 capitalize">
        {{ str_replace('_', ' ', auth()->user()?->getRoleNames()?->first() ?? 'Guest') }}
      </span>
    </p>
    <div class="flex items-center justify-center gap-3">
      <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
      <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </div>
    <p class="mt-6 text-xs text-slate-400">
      If you believe this is an error, please contact your system administrator.
    </p>
  </div>
</div>
@endsection
