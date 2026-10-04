@extends('layouts.app')
@section('title', 'Gate & Visitor Management')
@section('content')
<div class="space-y-6" x-data="visitorDashboard()">

  {{-- Top Action Header --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2.5">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
          <h1 class="page-title text-xl font-bold text-slate-900 tracking-tight">Gate &amp; Visitor Management</h1>
          <p class="page-subtitle text-xs text-slate-500">Real-time gate security, photo visitor passes, dynamic approvals &amp; instant scan-to-exit</p>
        </div>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
      <a href="{{ route('gate.scanner') }}" class="btn-secondary text-xs sm:text-sm font-semibold flex items-center gap-2 bg-white border border-slate-200 hover:border-indigo-400 hover:text-indigo-600 shadow-sm transition">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        <span>Fast QR Scanner</span>
      </a>

      <a href="{{ route('gate.report') }}" class="btn-secondary text-xs sm:text-sm font-semibold flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 shadow-sm transition">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Reports</span>
      </a>

      <a href="{{ route('gate.create') }}" class="btn-primary text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-sm shadow-blue-500/30">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>+ Log New Visitor</span>
      </a>
    </div>
  </div>

  {{-- Session Alerts --}}
  @if(session('success'))
  <div class="rounded-xl border border-emerald-200 bg-emerald-50/90 backdrop-blur p-4 flex items-center gap-3 text-emerald-800 text-sm font-medium shadow-sm">
    <div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center flex-shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <span>{{ session('success') }}</span>
  </div>
  @endif

  @if($errors->any())
  <div class="rounded-xl border border-red-200 bg-red-50/90 backdrop-blur p-4 flex items-start gap-3 text-red-800 text-sm shadow-sm">
    <div class="w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center flex-shrink-0 mt-0.5">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <div class="space-y-1">
      @foreach($errors->all() as $e)<p class="font-medium">{{ $e }}</p>@endforeach
    </div>
  </div>
  @endif

  {{-- Security Overdue Alert if outpasses pending --}}
  @if($pendingOutpasses > 0)
  <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between gap-3 shadow-sm">
    <div class="flex items-center gap-3">
      <span class="flex h-3 w-3 relative">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
      </span>
      <p class="text-sm font-semibold text-amber-900">
        {{ $pendingOutpasses }} student outpass(es) overdue — student has not returned within authorized duration.
      </p>
    </div>
    <a href="{{ route('gate.outpass') }}" class="text-xs font-bold text-amber-800 underline hover:text-amber-950">View Outpasses →</a>
  </div>
  @endif

  {{-- Overstay Visitors Alert --}}
  @if($overstayCount > 0)
  <div class="rounded-xl border border-rose-200 bg-rose-50/90 p-4 flex items-center justify-between gap-3 shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center flex-shrink-0 animate-pulse">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="text-sm font-semibold text-rose-900">
        {{ $overstayCount }} visitor(s) have exceeded their expected exit time and are still marked inside campus!
      </p>
    </div>
    <a href="{{ route('gate.index', ['tab' => 'overstayed']) }}" class="text-xs font-bold text-rose-800 underline hover:text-rose-950">View Overstayed Visitors →</a>
  </div>
  @endif

  {{-- KPI Cards --}}
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
    
    {{-- Card 1: Active Inside --}}
    <a href="{{ route('gate.index', ['tab' => 'active']) }}" class="relative overflow-hidden rounded-2xl p-4 sm:p-5 border transition-all duration-200 group {{ $tab === 'active' ? 'bg-gradient-to-br from-indigo-50 to-blue-50/80 border-indigo-300 ring-2 ring-indigo-500/20 shadow-md' : 'bg-white border-slate-200/80 hover:border-indigo-200 hover:shadow-sm' }}">
      <div class="flex items-center justify-between mb-2 sm:mb-3">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inside Campus</span>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-sm shadow-indigo-500/30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </div>
      </div>
      <div class="flex items-baseline gap-2">
        <p class="text-2xl sm:text-3xl font-black text-indigo-600 tracking-tight">{{ $insideCount }}</p>
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Live
        </span>
      </div>
      <p class="text-xs text-slate-500 mt-1">Currently on premises</p>
    </a>

    {{-- Card 2: Today's Total --}}
    <a href="{{ route('gate.index', ['tab' => 'today']) }}" class="relative overflow-hidden rounded-2xl p-4 sm:p-5 border transition-all duration-200 group {{ $tab === 'today' ? 'bg-gradient-to-br from-emerald-50 to-teal-50/80 border-emerald-300 ring-2 ring-emerald-500/20 shadow-md' : 'bg-white border-slate-200/80 hover:border-emerald-200 hover:shadow-sm' }}">
      <div class="flex items-center justify-between mb-2 sm:mb-3">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Visits</span>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-sm shadow-emerald-500/30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
      </div>
      <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $todayTotal }}</p>
      <p class="text-xs text-slate-500 mt-1">{{ today()->format('d M Y') }}</p>
    </a>

    {{-- Card 3: Pending Approvals --}}
    <a href="{{ route('gate.index', ['tab' => 'pending']) }}" class="relative overflow-hidden rounded-2xl p-4 sm:p-5 border transition-all duration-200 group {{ $tab === 'pending' ? 'bg-gradient-to-br from-amber-50 to-orange-50/80 border-amber-300 ring-2 ring-amber-500/20 shadow-md' : 'bg-white border-slate-200/80 hover:border-amber-200 hover:shadow-sm' }}">
      <div class="flex items-center justify-between mb-2 sm:mb-3">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Approvals</span>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-sm shadow-amber-500/30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
      </div>
      <div class="flex items-baseline gap-2">
        <p class="text-2xl sm:text-3xl font-black {{ $pendingCount > 0 ? 'text-amber-600' : 'text-slate-800' }} tracking-tight">{{ $pendingCount }}</p>
        @if($myPendingCount > 0)
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
          {{ $myPendingCount }} for you
        </span>
        @endif
      </div>
      <p class="text-xs text-slate-500 mt-1">Awaiting Host action</p>
    </a>

    {{-- Card 4: Blacklist Watch --}}
    <a href="{{ route('gate.blacklist') }}" class="relative overflow-hidden rounded-2xl p-4 sm:p-5 border transition-all duration-200 group bg-white border-slate-200/80 hover:border-red-200 hover:shadow-sm">
      <div class="flex items-center justify-between mb-2 sm:mb-3">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Blacklist Watch</span>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center text-white shadow-sm shadow-rose-500/30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </div>
      </div>
      <p class="text-2xl sm:text-3xl font-black text-rose-600 tracking-tight">{{ $blacklistCount }}</p>
      <p class="text-xs text-slate-500 mt-1">Blocked individuals</p>
    </a>

    {{-- Card 5: Student Outpasses --}}
    <a href="{{ route('gate.outpass') }}" class="col-span-2 lg:col-span-1 relative overflow-hidden rounded-2xl p-4 sm:p-5 border transition-all duration-200 group bg-white border-slate-200/80 hover:border-purple-200 hover:shadow-sm">
      <div class="flex items-center justify-between mb-2 sm:mb-3">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Student Outpasses</span>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-sm shadow-purple-500/30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
      </div>
      <p class="text-2xl sm:text-3xl font-black text-purple-700 tracking-tight">{{ $pendingOutpasses }}</p>
      <p class="text-xs text-slate-500 mt-1">Overdue outpass check</p>
    </a>
  </div>

  {{-- Quick Category Shortcuts --}}
  <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
    @foreach([
      ['Parent / Guardian',    'parent',             'emerald', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
      ['Admission Enquiry',    'admission_enquiry',  'blue',    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
      ['Vendor / Maintenance', 'vendor',             'amber',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'],
      ['Staff Interview',      'interview',          'purple',  '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
      ['Official Guest / Other','other',              'slate',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>'],
    ] as [$label, $catSlug, $color, $iconSvg])
    <a href="{{ route('gate.create', ['category' => $catSlug]) }}" class="group bg-white rounded-xl p-3 border border-slate-200/80 hover:border-{{ $color }}-300 hover:bg-{{ $color }}-50/30 transition shadow-xs flex items-center gap-2.5">
      <div class="w-7 h-7 rounded-lg bg-{{ $color }}-100 text-{{ $color }}-700 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $iconSvg !!}</svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-semibold text-slate-800 truncate group-hover:text-{{ $color }}-700">{{ $label }}</p>
        <span class="text-[10px] text-slate-400 font-medium">+ New</span>
      </div>
    </a>
    @endforeach
  </div>

  {{-- Main Table & Filter Card --}}
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    
    {{-- Navigation Tabs --}}
    <div class="border-b border-slate-200 bg-slate-50/70 px-4 sm:px-6 pt-3 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-1 sm:gap-2 overflow-x-auto pb-px">
        
        <a href="{{ route('gate.index', ['tab' => 'active']) }}" class="px-3.5 py-2.5 rounded-t-xl text-xs sm:text-sm font-semibold transition border-b-2 flex items-center gap-2 {{ $tab === 'active' ? 'border-indigo-600 text-indigo-600 bg-white shadow-xs' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Inside Now ({{ $insideCount }})
        </a>

        <a href="{{ route('gate.index', ['tab' => 'pending']) }}" class="px-3.5 py-2.5 rounded-t-xl text-xs sm:text-sm font-semibold transition border-b-2 flex items-center gap-2 {{ $tab === 'pending' ? 'border-amber-600 text-amber-700 bg-white shadow-xs' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
          <span>Pending Approvals</span>
          @if($pendingCount > 0)
          <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900">{{ $pendingCount }}</span>
          @endif
        </a>

        @if($myPendingCount > 0)
        <a href="{{ route('gate.index', ['tab' => 'my_approvals']) }}" class="px-3.5 py-2.5 rounded-t-xl text-xs sm:text-sm font-semibold transition border-b-2 flex items-center gap-2 {{ $tab === 'my_approvals' ? 'border-purple-600 text-purple-700 bg-white shadow-xs' : 'border-transparent text-purple-700 bg-purple-50/50 hover:bg-purple-100/50' }}">
          <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span>For My Approval ({{ $myPendingCount }})</span>
        </a>
        @endif

        <a href="{{ route('gate.index', ['tab' => 'today']) }}" class="px-3.5 py-2.5 rounded-t-xl text-xs sm:text-sm font-semibold transition border-b-2 {{ $tab === 'today' ? 'border-blue-600 text-blue-600 bg-white shadow-xs' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
          Today's Log ({{ $todayTotal }})
        </a>

        <a href="{{ route('gate.index', ['tab' => 'overstayed']) }}" class="px-3.5 py-2.5 rounded-t-xl text-xs sm:text-sm font-semibold transition border-b-2 flex items-center gap-1.5 {{ $tab === 'overstayed' ? 'border-rose-600 text-rose-700 bg-white shadow-xs' : 'border-transparent text-slate-600 hover:text-rose-700' }}">
          <span>Overstay Alerts</span>
          @if($overstayCount > 0)
          <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-rose-200 text-rose-900">{{ $overstayCount }}</span>
          @endif
        </a>

        <a href="{{ route('gate.index', ['tab' => 'all']) }}" class="px-3.5 py-2.5 rounded-t-xl text-xs sm:text-sm font-semibold transition border-b-2 {{ $tab === 'all' ? 'border-slate-800 text-slate-900 bg-white shadow-xs' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
          All Visitor Records
        </a>
      </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('gate.index') }}" class="p-4 bg-slate-50/40 border-b border-slate-200 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <input type="hidden" name="tab" value="{{ $tab }}">

      {{-- Search --}}
      <div class="lg:col-span-2 relative">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, pass #, student, company..." class="input w-full pl-9 text-xs sm:text-sm">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      {{-- Category Filter --}}
      <div>
        <select name="category" class="select w-full text-xs sm:text-sm">
          <option value="">All Categories</option>
          @foreach($categories as $key => $name)
          <option value="{{ $key }}" @selected(request('category') === $key)>{{ $name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Department Filter --}}
      <div>
        <select name="department" class="select w-full text-xs sm:text-sm">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
          <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
          @endforeach
        </select>
      </div>

      {{-- Date & Submit --}}
      <div class="flex items-center gap-2">
        <input type="date" name="date" value="{{ request('date') }}" class="input w-full text-xs">
        <button type="submit" class="btn-primary text-xs px-3 py-2 flex items-center gap-1 font-semibold">
          <span>Filter</span>
        </button>
        @if(request()->hasAny(['search', 'category', 'department', 'date']))
        <a href="{{ route('gate.index', ['tab' => $tab]) }}" class="btn-secondary text-xs px-2.5 py-2 text-slate-500 hover:text-slate-800" title="Reset Filters">✕</a>
        @endif
      </div>
    </form>

    {{-- Visitors Table --}}
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs sm:text-sm">
        <thead class="bg-slate-50/90 text-slate-600 font-semibold border-b border-slate-200">
          <tr>
            <th class="py-3 px-4">Visitor &amp; Pass #</th>
            <th class="py-3 px-4">Category &amp; Details</th>
            <th class="py-3 px-4">Host / Department</th>
            <th class="py-3 px-4">Entry / Duration</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($visitors as $v)
          @php
            $catBadge = $v->category_badge_classes;
            $statusBadge = $v->status_badge_classes;
            $isOverstay = $v->isOverstayed();
          @endphp
          <tr class="hover:bg-slate-50/70 transition-colors {{ $isOverstay ? 'bg-rose-50/30' : '' }}">
            
            {{-- Column 1: Visitor Identity --}}
            <td class="py-3.5 px-4 align-top">
              <div class="flex items-start gap-3">
                <div class="relative flex-shrink-0">
                  @if($v->visitor_photo)
                  <img src="{{ Storage::url($v->visitor_photo) }}" alt="{{ $v->visitor_name }}" class="w-10 h-10 rounded-full object-cover border border-slate-200 shadow-xs">
                  @else
                  <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-600 text-xs shadow-xs">
                    {{ strtoupper(substr($v->visitor_name, 0, 2)) }}
                  </div>
                  @endif
                  @if($v->visitor_count > 1)
                  <span class="absolute -bottom-1 -right-1 bg-slate-800 text-white text-[9px] font-bold px-1 rounded-full shadow-xs" title="Total {{ $v->visitor_count }} persons">
                    +{{ $v->visitor_count - 1 }}
                  </span>
                  @endif
                </div>

                <div class="space-y-0.5">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <a href="{{ route('gate.show', $v->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition">{{ $v->visitor_name }}</a>
                  </div>
                  <p class="text-xs text-slate-500 font-mono flex items-center gap-1">
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $v->visitor_phone ?? 'No phone' }}
                  </p>
                  <span class="inline-block font-mono text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200/60 px-1.5 py-0.2 rounded">
                    {{ $v->pass_number }}
                  </span>
                </div>
              </div>
            </td>

            {{-- Column 2: Category & Dynamic ERP Link --}}
            <td class="py-3.5 px-4 align-top space-y-1">
              <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold border {{ $catBadge['bg'] }}">
                {{ $v->category_label }}
              </span>

              {{-- Dynamic ERP Entity Details --}}
              @if($v->category === \App\Models\Visitor::CATEGORY_PARENT && $v->student)
              <div class="text-xs text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-200/60">
                <p class="font-semibold text-slate-800">{{ $v->relationship_to_student ? ucfirst($v->relationship_to_student) . ' of: ' : 'Student: ' }}<span class="text-indigo-600">{{ $v->student->first_name }} {{ $v->student->last_name }}</span></p>
                <p class="text-[11px] text-slate-500 font-mono">Adm: {{ $v->student->admission_no }}</p>
              </div>
              @elseif($v->category === \App\Models\Visitor::CATEGORY_ADMISSION)
              <div class="text-xs text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-200/60">
                <p class="font-semibold text-slate-800">Child: {{ $v->child_name ?? '—' }}</p>
                @if($v->grade_applying_for)<p class="text-[11px] text-slate-500">Grade: {{ $v->grade_applying_for }}</p>@endif
                @if($v->enquiry_id)<span class="text-[10px] text-blue-600 font-bold">✓ CRM Lead Created</span>@endif
              </div>
              @elseif($v->category === \App\Models\Visitor::CATEGORY_VENDOR)
              <div class="text-xs text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-200/60">
                <p class="font-semibold text-slate-800">{{ $v->company_name ?? 'Vendor' }}</p>
                @if($v->work_order_number)<p class="text-[11px] font-mono text-slate-500">WO: {{ $v->work_order_number }}</p>@endif
              </div>
              @elseif($v->category === \App\Models\Visitor::CATEGORY_INTERVIEW)
              <div class="text-xs text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-200/60">
                <p class="font-semibold text-slate-800">Role: {{ $v->job_role_applied ?? 'Candidate' }}</p>
                @if($v->candidate_ref_number)<p class="text-[11px] font-mono text-slate-500">Ref: {{ $v->candidate_ref_number }}</p>@endif
              </div>
              @else
              <p class="text-xs text-slate-600 italic truncate max-w-xs">{{ $v->purpose }}</p>
              @endif
            </td>

            {{-- Column 3: Host / Department --}}
            <td class="py-3.5 px-4 align-top space-y-0.5">
              <p class="font-semibold text-slate-800 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ $v->whom_to_meet ?? 'Front Desk / Security' }}
              </p>
              @if($v->department)
              <span class="inline-block text-[11px] font-medium text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                {{ $v->department }}
              </span>
              @endif
            </td>

            {{-- Column 4: Timing & Duration --}}
            <td class="py-3.5 px-4 align-top space-y-1">
              <div class="text-xs">
                @if($v->in_time)
                <p class="text-slate-800 font-medium">In: {{ $v->in_time->format('h:i A') }}</p>
                <p class="text-[11px] text-slate-400 font-mono">{{ $v->visit_date?->format('d M') }}</p>
                @else
                <p class="text-amber-700 italic">Not Checked In</p>
                @endif
              </div>

              @if($v->out_time)
              <p class="text-xs text-slate-500 font-mono">Out: {{ $v->out_time->format('h:i A') }}</p>
              @elseif($isOverstay)
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 animate-pulse">
                Overstayed (Exp: {{ $v->expected_exit_time?->format('h:i A') }})
              </span>
              @elseif($v->expected_exit_time)
              <p class="text-[11px] text-slate-400">Exp Out: {{ $v->expected_exit_time->format('h:i A') }}</p>
              @endif
            </td>

            {{-- Column 5: Status --}}
            <td class="py-3.5 px-4 align-top">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadge['bg'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                {{ $statusBadge['text'] }}
              </span>
              @if($v->status === \App\Models\Visitor::STATUS_REJECTED && $v->rejection_reason)
              <p class="text-[11px] text-red-600 mt-1 max-w-xs truncate" title="{{ $v->rejection_reason }}">Reason: {{ $v->rejection_reason }}</p>
              @endif
            </td>

            {{-- Column 6: Quick Actions --}}
            <td class="py-3.5 px-4 align-top text-right space-y-1">
              
              {{-- Pending Approval Actions --}}
              @if($v->status === \App\Models\Visitor::STATUS_PENDING)
              <div class="flex items-center justify-end gap-1.5">
                <form method="POST" action="{{ route('gate.approve', $v->id) }}">
                  @csrf
                  <button type="submit" class="btn-primary text-xs px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 shadow-xs" title="Approve Entry">
                    ✓ Approve
                  </button>
                </form>
                <button type="button" @click="openRejectModal({{ $v->id }}, '{{ addslashes($v->visitor_name) }}')" class="btn-secondary text-xs px-2 py-1 text-red-600 hover:bg-red-50 hover:border-red-300" title="Reject Request">
                  ✕ Reject
                </button>
              </div>
              @endif

              {{-- Active Inside Actions --}}
              @if($v->isInside())
              <div class="flex items-center justify-end gap-1.5">
                <a href="{{ route('gate.pass', $v->id) }}" class="btn-secondary text-xs px-2.5 py-1 flex items-center gap-1 font-medium bg-white hover:border-indigo-300 hover:text-indigo-600" title="View & Print Badge">
                  🖨 Pass
                </a>
                <form method="POST" action="{{ route('gate.checkout', $v->id) }}" onsubmit="return confirm('Check out visitor {{ $v->visitor_name }} now?')">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn-secondary text-xs px-2.5 py-1 font-semibold text-slate-700 hover:bg-slate-100" title="Check out visitor">
                    Exit →
                  </button>
                </form>
              </div>
              @endif

              {{-- Standard View Link --}}
              <div class="text-right">
                <a href="{{ route('gate.show', $v->id) }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                  Details →
                </a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-12 text-center">
              <div class="max-w-sm mx-auto space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-700">No visitor records found</h3>
                <p class="text-xs text-slate-500">There are no visitors matching your selected filters or tab.</p>
                <a href="{{ route('gate.create') }}" class="btn-primary text-xs inline-flex items-center gap-1.5">
                  + Log First Visitor
                </a>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($visitors->hasPages())
    <div class="p-4 border-t border-slate-200 bg-slate-50/50">
      {{ $visitors->links() }}
    </div>
    @endif
  </div>

  {{-- Rejection Modal --}}
  <div x-show="rejectModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div @click.away="rejectModalOpen = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-lg">Reject Visit Request</h3>
        <button @click="rejectModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
      </div>
      <p class="text-xs text-slate-600">
        Please state the reason for rejecting <strong x-text="rejectVisitorName"></strong>'s visit. The gatekeeper will be notified.
      </p>

      <form :action="'{{ url('gate') }}/' + rejectVisitorId + '/reject'" method="POST" class="space-y-4">
        @csrf
        <div>
          <label class="label text-xs">Rejection Reason <span class="text-red-500">*</span></label>
          <textarea name="rejection_reason" rows="3" class="input text-xs w-full" required placeholder="e.g. Host is in scheduled board meeting / Prior appointment required"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2">
          <button type="button" @click="rejectModalOpen = false" class="btn-secondary text-xs">Cancel</button>
          <button type="submit" class="btn-primary bg-red-600 hover:bg-red-700 text-xs font-semibold">Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>

</div>

@push('scripts')
<script>
function visitorDashboard() {
  return {
    rejectModalOpen: false,
    rejectVisitorId: null,
    rejectVisitorName: '',
    openRejectModal(id, name) {
      this.rejectVisitorId = id;
      this.rejectVisitorName = name;
      this.rejectModalOpen = true;
    }
  }
}
</script>
@endpush
@endsection
