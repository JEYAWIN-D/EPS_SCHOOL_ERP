@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

{{-- ═══════════════════════════════════════════════════════════════
     MANAGEMENT / ADMIN DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@if(($dashboardType ?? 'management') === 'management')
  @php
    $activeBriefWidgets     = $activeBriefWidgets ?? [];
    $showStudentOverview    = in_array('student_overview', $activeBriefWidgets);
    $showStudentDemographics= in_array('student_demographics', $activeBriefWidgets);
    $showClassDistribution  = in_array('class_distribution', $activeBriefWidgets);
    $showSectionDistribution= in_array('section_distribution', $activeBriefWidgets);
    $showAttendanceOverview = in_array('attendance_overview', $activeBriefWidgets);
    $showFeeOverview        = in_array('fee_overview', $activeBriefWidgets);
    $showAdmissionsOverview = in_array('admissions_overview', $activeBriefWidgets);
    $showUpcomingEvents     = in_array('upcoming_events', $activeBriefWidgets) || in_array('events_upcoming', $activeBriefWidgets);
    $showNotices            = in_array('school_notices', $activeBriefWidgets) || in_array('notices_active', $activeBriefWidgets);
    $showCirculars          = in_array('circulars_orders', $activeBriefWidgets) || in_array('circulars_active', $activeBriefWidgets);
    $showBirthdayWishes     = in_array('birthday_wishes', $activeBriefWidgets);
    $showRevenueAnalytics   = in_array('revenue_analytics', $activeBriefWidgets);
    $activeEventNoticeCols  = ($showUpcomingEvents ? 1 : 0) + ($showNotices ? 1 : 0) + ($showCirculars ? 1 : 0);
  @endphp

  <div x-data="customWidgets()" x-init="init()" class="space-y-6">

    {{-- Executive Dashboard Header --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white shadow-xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-xl font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Executive Dashboard</h1>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/80">
              <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
              {{ $academicYear ?? '2025-2026' }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
            <span>Welcome back, <strong class="text-slate-700 font-semibold">{{ auth()->user()->name }}</strong></span>
            <span class="text-slate-300">&bull;</span>
            <span class="inline-flex items-center gap-1 text-slate-400">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              {{ now()->format('l, d M Y') }}
            </span>
          </p>
        </div>
      </div>

      <div class="flex gap-2 flex-wrap items-center">
        <a href="{{ route('reports.general-register') }}" class="btn-sm btn-secondary inline-flex items-center gap-1.5 text-xs font-semibold shadow-2xs hover:bg-slate-50 transition">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          General Register
        </a>
        <a href="{{ route('reports.attendance-register') }}" class="btn-sm btn-secondary inline-flex items-center gap-1.5 text-xs font-semibold shadow-2xs hover:bg-slate-50 transition">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Attendance Register
        </a>
        <a href="{{ route('reports.fee-collection-register') }}" class="btn-sm btn-secondary inline-flex items-center gap-1.5 text-xs font-semibold shadow-2xs hover:bg-slate-50 transition">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          Fee Register
        </a>
      </div>
    </div>

    {{-- Dashboard Customization Toolbar (Single entry point) --}}
    <div class="flex items-center justify-between pb-1 flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <h2 class="text-sm font-bold text-slate-700 tracking-tight flex items-center gap-2" style="font-family:'Plus Jakarta Sans',sans-serif;">
          <span>Instant Pulse Metrics</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/70">
            {{ $singleValueWidgets->count() }} active
          </span>
        </h2>
      </div>

      @if($canManageWidgets ?? false)
      <div>
        <button type="button"
                @click="openSettings()"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 border border-slate-200 hover:border-emerald-300 shadow-2xs hover:shadow-xs transition-all cursor-pointer">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
          </svg>
          <span>Dashboard Settings</span>
        </button>
      </div>
      @endif
    </div>

    {{-- Dashboard Top Tier: Small Summary Cards (Title + One Overall Value Only) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4">
      @foreach($singleValueWidgets as $widget)
      @php
        $catalogItem = $sourceCatalog[$widget->source] ?? ($singleValueCatalog[$widget->source] ?? []);
        $svgPath = $catalogItem['default_icon'] ?? 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';
      @endphp
      <div class="card p-4 relative group flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all duration-200 min-h-[140px]">
        {{-- Card Header & Body --}}
        <div>
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br {{ $widget->icon_color }} flex items-center justify-center text-white shadow-xs">
              <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $svgPath }}"/>
              </svg>
            </div>
            <div class="flex items-center gap-1">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">{{ $widget->badge_text ?? 'Active' }}</span>
              @if($canManageWidgets ?? false)
              <button type="button"
                      @click="removeKpiDirect('{{ $widget->source }}')"
                      class="opacity-0 group-hover:opacity-100 transition-opacity w-5 h-5 rounded-full flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-slate-100 cursor-pointer"
                      title="Remove KPI from dashboard">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
              @endif
            </div>
          </div>

          <div class="mt-2.5">
            <p class="stat-number text-slate-800 tracking-tight text-xl font-bold">{{ $widget->live_display }}</p>
            <p class="text-xs text-slate-500 mt-0.5 font-medium truncate" title="{{ $widget->name }}">{{ $widget->name }}</p>
          </div>
        </div>

        {{-- Card Link --}}
        @if(!empty($widget->link_url))
          <a href="{{ $widget->link_url }}" class="mt-2 pt-1.5 text-xs text-indigo-600 font-medium hover:text-indigo-800 inline-flex items-center gap-1 group/link transition-colors">
            <span>{{ $widget->link_label }}</span>
          </a>
        @else
          <div class="mt-2"></div>
        @endif
      </div>
      @endforeach
    </div>

    {{-- ── ADD / EDIT SLIDE-OVER PANEL ──────────────────────────── --}}
    <div x-show="panelOpen" x-cloak
         class="fixed inset-0 z-50 flex"
         @keydown.escape.window="panelOpen = false">

      {{-- Backdrop --}}
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="panelOpen = false"></div>

      {{-- Panel --}}
      <div class="relative ml-auto w-full max-w-md bg-white shadow-2xl flex flex-col h-full"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">

        {{-- Panel header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
          <h3 class="font-semibold text-slate-800" x-text="editingId ? 'Edit Dashboard Card' : 'Add Dashboard Card'"></h3>
          <button type="button" @click="panelOpen = false" class="btn-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        {{-- Panel body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
          {{-- Data Source Selection --}}
          <div>
            <label class="label">Data Source <span class="text-red-500">*</span></label>
            <select x-model="form.source" class="select" @change="onSourceChange()">
              @foreach($sourceGroups as $group => $sources)
              <optgroup label="{{ $group }}">
                @foreach($sources as $srcKey => $srcLabel)
                @php
                  $isAlreadyAdded = in_array($srcKey, $existingSources ?? []) && $srcKey !== 'student_count';
                @endphp
                <option value="{{ $srcKey }}" :disabled="isSourceDisabled('{{ $srcKey }}')">
                  {{ $srcLabel }}{{ $isAlreadyAdded ? ' (Already added)' : '' }}
                </option>
                @endforeach
              </optgroup>
              @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1" x-text="sourceDescriptions[form.source] || ''"></p>
          </div>

          {{-- Card Name --}}
          <div>
            <label class="label">Card Name <span class="text-red-500">*</span></label>
            <input type="text" x-model="form.name" class="input" placeholder="e.g. Total Students, Hostellers, Fee Today"
                   @input="resetPreview()">
            <p class="text-xs text-slate-400 mt-1">Customize the title displayed on this card.</p>
          </div>

          {{-- Icon Colour --}}
          <div>
            <label class="label">Card Gradient Colour</label>
            <div class="flex gap-2 flex-wrap">
              @foreach($colorOptions as $value => $label)
              <button type="button"
                      @click="form.icon_color = '{{ $value }}'"
                      :class="form.icon_color === '{{ $value }}' ? 'ring-2 ring-offset-2 ring-indigo-500 scale-105' : 'opacity-80 hover:opacity-100'"
                      class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $value }} flex-shrink-0 transition-transform"
                      title="{{ $label }}">
              </button>
              @endforeach
            </div>
          </div>

          {{-- Student Filters (only for student_count source) --}}
          <div x-show="hasStudentFilters" class="space-y-4 pt-3 border-t border-slate-100">
            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Filters (Same as Student Listing)</p>

            {{-- Class --}}
            <div>
              <label class="label">Class</label>
              <select x-model="form.filter_class_id" class="select" @change="resetPreview()">
                <option value="">All Classes</option>
                @foreach($classes as $cls)
                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                @endforeach
              </select>
            </div>

            {{-- Section --}}
            <div>
              <label class="label">Section</label>
              <select x-model="form.filter_section" class="select" @change="resetPreview()">
                <option value="">All Sections</option>
                @foreach($sections as $sec)
                <option value="{{ $sec }}">Section {{ $sec }}</option>
                @endforeach
              </select>
            </div>

            {{-- Gender --}}
            <div>
              <label class="label">Gender</label>
              <select x-model="form.filter_gender" class="select" @change="resetPreview()">
                <option value="">All Genders</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>

            {{-- Student Type --}}
            <div>
              <label class="label">Student Type</label>
              <select x-model="form.filter_student_type" class="select" @change="resetPreview()">
                <option value="">All Types</option>
                <option value="day_scholar">Day Scholar</option>
                <option value="hosteller">Hosteller</option>
                <option value="day_boarder">Day Boarder</option>
              </select>
            </div>

            {{-- Status --}}
            <div>
              <label class="label">Status</label>
              <select x-model="form.filter_status" class="select" @change="resetPreview()">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="transferred">Transferred</option>
                <option value="left">Left</option>
                <option value="alumni">Alumni</option>
                <option value="all">All</option>
              </select>
            </div>
          </div>

          {{-- Non-student source info --}}
          <div x-show="!hasStudentFilters" class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs text-slate-500">
            <p class="font-medium text-slate-700">Live System Metric</p>
            <p class="mt-0.5">This card automatically pulls and calculates data directly from the system database.</p>
          </div>

          {{-- Live Preview --}}
          <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Live Preview</span>
              <button type="button" @click="previewCount()"
                      :disabled="previewing"
                      class="btn-sm btn-secondary text-xs"
                      :class="previewing ? 'opacity-60' : ''">
                <span x-show="!previewing">▶ Test Preview</span>
                <span x-show="previewing">Loading…</span>
              </button>
            </div>
            <div x-show="previewResult !== null" class="mt-3 text-center py-2">
              <p class="text-3xl font-bold text-indigo-600" x-text="previewResult"></p>
              <p class="text-xs text-slate-400 mt-1" x-text="previewDescription"></p>
              <p class="text-xs text-indigo-500 mt-2" x-show="previewUrl">
                <a :href="previewUrl" target="_blank" class="hover:underline">Preview target link →</a>
              </p>
            </div>
            <p x-show="previewResult === null && !previewing" class="text-xs text-slate-400 mt-2">
              Click <strong>Test Preview</strong> to calculate and preview live data for this card.
            </p>
          </div>
        </div>

        {{-- Panel footer --}}
        <div class="px-6 py-4 border-t border-slate-100 flex items-center gap-3">
          <button type="button" @click="saveWidget()"
                  :disabled="saving || !form.name"
                  class="btn btn-primary flex-1"
                  :class="saving || !form.name ? 'opacity-60 cursor-not-allowed' : ''">
            <span x-show="!saving" x-text="editingId ? 'Save Changes' : 'Create Card'"></span>
            <span x-show="saving">Saving…</span>
          </button>
          <button type="button" @click="panelOpen = false" class="btn btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    {{-- ── DASHBOARD SETTINGS MODAL (TWO SEPARATE TABS) ──────────────────────── --}}
    <div x-show="settingsOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
         style="overscroll-behavior: contain;"
         @wheel.self.prevent
         @touchmove.self.prevent
         @keydown.escape.window="closeSettings()">

      {{-- Backdrop --}}
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
           @click="closeSettings()"
           @wheel.prevent
           @touchmove.prevent></div>

      {{-- Modal Content --}}
      <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden border border-slate-100"
           style="overscroll-behavior: contain;"
           @click.stop
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95">

        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-white flex-shrink-0"
             @wheel.stop
             @touchmove.stop>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shadow-2xs">
              <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
              </svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Dashboard Customization</h3>
              <p class="text-xs text-slate-500">Configure visible instant pulse metrics and deep-dive analytics blocks.</p>
            </div>
          </div>
          <button type="button" @click="closeSettings()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer" title="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex items-center px-6 border-b border-slate-200 bg-slate-50/75 flex-shrink-0"
             @wheel.stop
             @touchmove.stop>
          <button type="button"
                  @click="settingsTab = 'kpi'"
                  :class="settingsTab === 'kpi'
                    ? 'border-emerald-600 text-emerald-700 font-bold bg-white -mb-px'
                    : 'border-transparent text-slate-500 hover:text-slate-700 font-medium hover:bg-slate-100/60'"
                  class="px-5 py-3 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer rounded-t-lg">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Instant Pulse Metrics</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="settingsTab === 'kpi' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/70 text-slate-600'"
                  x-text="selectedKpis.length + ' active'">
            </span>
          </button>

          <button type="button"
                  @click="settingsTab = 'detailed'"
                  :class="settingsTab === 'detailed'
                    ? 'border-emerald-600 text-emerald-700 font-bold bg-white -mb-px'
                    : 'border-transparent text-slate-500 hover:text-slate-700 font-medium hover:bg-slate-100/60'"
                  class="px-5 py-3 border-b-2 text-xs flex items-center gap-2 transition-all cursor-pointer rounded-t-lg">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Deep-Dive Analytics Blocks</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="settingsTab === 'detailed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/70 text-slate-600'"
                  x-text="selectedDetailed.length + ' active'">
            </span>
          </button>
        </div>

        {{-- Scrollable Modal Body --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-5"
             style="overscroll-behavior: contain; -webkit-overflow-scrolling: touch;"
             @wheel.stop
             @touchmove.stop>

          {{-- ── TAB 1: INSTANT PULSE METRICS ── --}}
          <div x-show="settingsTab === 'kpi'" class="space-y-5">
            <div class="flex items-center justify-between pb-1 border-b border-slate-100">
              <div>
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Instant Pulse Metrics (Single-Value Cards)</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Toggle cards to show or hide single-value summary metrics at the top of your dashboard.</p>
              </div>
            </div>

            {{-- Grouped Vertical Table/List for KPIs --}}
            <div class="space-y-4">
              @foreach($singleValueGroups ?? [] as $grpName => $grpItems)
              @php
                $itemKeys = array_keys($grpItems);
                $jsKeys = json_encode($itemKeys);
              @endphp
              <div class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-2xs">
                {{-- Category Header with 3-State Select All Toggle --}}
                <div class="px-4 py-2.5 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between">
                  <span class="text-[11px] font-bold tracking-wider text-slate-700 uppercase flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>{{ $grpName }}</span>
                  </span>
                  <div class="flex items-center gap-3">
                    <span class="text-[11px] font-semibold text-slate-500"
                          x-text="countKpisActive({{ $jsKeys }}) + ' of {{ count($grpItems) }} active'">
                    </span>

                    {{-- 3-State Category Toggle Switch --}}
                    <button type="button"
                            role="switch"
                            :aria-checked="getKpiCategoryState({{ $jsKeys }}) === 'on'"
                            @click="toggleKpiCategory({{ $jsKeys }})"
                            :title="getKpiCategoryState({{ $jsKeys }}) === 'on' ? 'Deselect all in {{ $grpName }}' : 'Select all in {{ $grpName }}'"
                            :class="{
                              'bg-emerald-600 focus:ring-emerald-500': getKpiCategoryState({{ $jsKeys }}) === 'on',
                              'bg-emerald-500/80 focus:ring-emerald-400': getKpiCategoryState({{ $jsKeys }}) === 'partial',
                              'bg-slate-300 focus:ring-slate-400': getKpiCategoryState({{ $jsKeys }}) === 'off'
                            }"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-offset-2 items-center">
                      <span :style="{
                              'transform': getKpiCategoryState({{ $jsKeys }}) === 'on' ? 'translateX(20px)' : (getKpiCategoryState({{ $jsKeys }}) === 'partial' ? 'translateX(10px)' : 'translateX(0px)')
                            }"
                            style="transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);"
                            class="pointer-events-none flex items-center justify-center h-5 w-5 rounded-full bg-white shadow-md ring-0">
                        <span x-show="getKpiCategoryState({{ $jsKeys }}) === 'partial'" class="w-2.5 h-0.5 bg-emerald-700 rounded-full"></span>
                      </span>
                    </button>
                  </div>
                </div>

                {{-- Rows --}}
                <div class="divide-y divide-slate-100">
                  @foreach($grpItems as $kpiKey => $kpiConfig)
                  <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50/60 transition-colors">
                    <div class="pr-4 flex-1">
                      <p class="text-xs font-semibold text-slate-800">{{ $kpiConfig['label'] }}</p>
                      @if(!empty($kpiConfig['description']))
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{{ $kpiConfig['description'] }}</p>
                      @endif
                    </div>

                    {{-- iOS-style Toggle Button with Guaranteed Physical Translation --}}
                    <button type="button"
                            role="switch"
                            :aria-checked="isKpiSelected('{{ $kpiKey }}')"
                            @click="toggleKpiSwitch('{{ $kpiKey }}')"
                            :class="isKpiSelected('{{ $kpiKey }}') ? 'bg-emerald-600 focus:ring-emerald-500' : 'bg-slate-300 focus:ring-slate-400'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-offset-2 items-center">
                      <span :style="isKpiSelected('{{ $kpiKey }}') ? 'transform: translateX(20px);' : 'transform: translateX(0px);'"
                            style="transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);"
                            class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-md ring-0">
                      </span>
                    </button>
                  </div>
                  @endforeach
                </div>
              </div>
              @endforeach
            </div>
          </div>

          {{-- ── TAB 2: DEEP-DIVE ANALYTICS BLOCKS ── --}}
          <div x-show="settingsTab === 'detailed'" class="space-y-5">
            <div class="flex items-center justify-between pb-1 border-b border-slate-100">
              <div>
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Deep-Dive Analytics Blocks (Detailed Widgets)</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Toggle multi-item breakdown widgets, charts, and detailed lists displayed below the summary metrics.</p>
              </div>
            </div>

            {{-- Grouped Vertical Table/List for Detailed Widgets --}}
            <div class="space-y-4">
              @foreach($briefInfoGroups ?? [] as $grpName => $grpItems)
              @php
                $itemKeys = array_keys($grpItems);
                $jsKeys = json_encode($itemKeys);
              @endphp
              <div class="rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-2xs">
                {{-- Category Header with 3-State Select All Toggle --}}
                <div class="px-4 py-2.5 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between">
                  <span class="text-[11px] font-bold tracking-wider text-slate-700 uppercase flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>{{ $grpName }}</span>
                  </span>
                  <div class="flex items-center gap-3">
                    <span class="text-[11px] font-semibold text-slate-500"
                          x-text="countDetailedActive({{ $jsKeys }}) + ' of {{ count($grpItems) }} active'">
                    </span>

                    {{-- 3-State Category Toggle Switch --}}
                    <button type="button"
                            role="switch"
                            :aria-checked="getDetailedCategoryState({{ $jsKeys }}) === 'on'"
                            @click="toggleDetailedCategory({{ $jsKeys }})"
                            :title="getDetailedCategoryState({{ $jsKeys }}) === 'on' ? 'Deselect all in {{ $grpName }}' : 'Select all in {{ $grpName }}'"
                            :class="{
                              'bg-emerald-600 focus:ring-emerald-500': getDetailedCategoryState({{ $jsKeys }}) === 'on',
                              'bg-emerald-500/80 focus:ring-emerald-400': getDetailedCategoryState({{ $jsKeys }}) === 'partial',
                              'bg-slate-300 focus:ring-slate-400': getDetailedCategoryState({{ $jsKeys }}) === 'off'
                            }"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-offset-2 items-center">
                      <span :style="{
                              'transform': getDetailedCategoryState({{ $jsKeys }}) === 'on' ? 'translateX(20px)' : (getDetailedCategoryState({{ $jsKeys }}) === 'partial' ? 'translateX(10px)' : 'translateX(0px)')
                            }"
                            style="transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);"
                            class="pointer-events-none flex items-center justify-center h-5 w-5 rounded-full bg-white shadow-md ring-0">
                        <span x-show="getDetailedCategoryState({{ $jsKeys }}) === 'partial'" class="w-2.5 h-0.5 bg-emerald-700 rounded-full"></span>
                      </span>
                    </button>
                  </div>
                </div>

                {{-- Rows --}}
                <div class="divide-y divide-slate-100">
                  @foreach($grpItems as $detKey => $detConfig)
                  <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50/60 transition-colors">
                    <div class="pr-4 flex-1">
                      <p class="text-xs font-semibold text-slate-800">{{ $detConfig['label'] }}</p>
                      @if(!empty($detConfig['description']))
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{{ $detConfig['description'] }}</p>
                      @endif
                    </div>

                    {{-- iOS-style Toggle Button with Guaranteed Physical Translation --}}
                    <button type="button"
                            role="switch"
                            :aria-checked="isDetailedSelected('{{ $detKey }}')"
                            @click="toggleDetailedSwitch('{{ $detKey }}')"
                            :class="isDetailedSelected('{{ $detKey }}') ? 'bg-emerald-600 focus:ring-emerald-500' : 'bg-slate-300 focus:ring-slate-400'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-offset-2 items-center">
                      <span :style="isDetailedSelected('{{ $detKey }}') ? 'transform: translateX(20px);' : 'transform: translateX(0px);'"
                            style="transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);"
                            class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-md ring-0">
                      </span>
                    </button>
                  </div>
                  @endforeach
                </div>
              </div>
              @endforeach
            </div>
          </div>

        </div>

        {{-- Fixed Footer: Left Notice & Right Cancel / Apply --}}
        <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50/90 flex items-center justify-between flex-wrap gap-3 flex-shrink-0"
             @wheel.stop
             @touchmove.stop>
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>These settings only affect how information is displayed on your dashboard.</span>
          </div>
          <div class="flex items-center gap-2.5 ml-auto">
            <button type="button"
                    @click="cancelSettings()"
                    :class="savingSettings ? 'border-rose-300 text-rose-700 bg-rose-50/70 hover:bg-rose-100 hover:border-rose-400' : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-50'"
                    class="px-4 py-2 text-xs font-semibold rounded-lg border shadow-2xs transition-colors cursor-pointer inline-flex items-center gap-1.5">
              <svg x-show="savingSettings" class="w-3.5 h-3.5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span x-show="!savingSettings">Cancel</span>
              <span x-show="savingSettings">Cancel Apply</span>
            </button>
            <button type="button"
                    @click="applySettings()"
                    :disabled="savingSettings"
                    class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors cursor-pointer inline-flex items-center gap-1.5 disabled:opacity-75 disabled:cursor-not-allowed">
              <span x-show="!savingSettings">Apply to Dashboard</span>
              <span x-show="savingSettings" class="inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                Applying…
              </span>
            </button>
          </div>
        </div>

      </div>
    </div>

    {{-- ── DELETE / HIDE CONFIRMATION MODAL ──────────────────────────── --}}
    <div x-show="deleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="deleteModal = false">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="deleteModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md space-y-4 border border-slate-100"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95">
        <div class="flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-slate-800 text-base" style="font-family:'Plus Jakarta Sans',sans-serif;">Remove from Dashboard?</h3>
            <p class="text-xs text-slate-500 mt-0.5">Card / Section: <strong class="text-slate-700" x-text="deleteWidgetName"></strong></p>
          </div>
        </div>

        <div class="bg-slate-50 rounded-xl p-3 text-xs text-slate-600 border border-slate-100">
          <p class="font-medium text-slate-700 flex items-center gap-1.5 mb-1">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Zero Data Loss Guarantee
          </p>
          <p>This will safely hide this item from your dashboard view. <strong>No student records, employee data, fee transactions, or institutional records are deleted.</strong></p>
          <p class="mt-1 text-slate-400">You can restore or re-add this card anytime from the <strong>+ Add Card</strong> catalog.</p>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="deleteModal = false" class="btn btn-secondary flex-1">Keep Item</button>
          <form :action="`/dashboard/widgets/${deleteWidgetId}`" method="POST" class="flex-1">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger w-full inline-flex items-center justify-center gap-1.5">
              <span>Remove</span>
            </button>
          </form>
        </div>
      </div>
    </div>

  {{-- ═══════════════════════════════════════════════════════════════
       AREA 2 — DEEP-DIVE ANALYTICS BLOCKS
       ═══════════════════════════════════════════════════════════════ --}}
  <div class="pt-4 border-t border-slate-200/80 space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <div class="flex items-center gap-2">
        <h2 class="text-sm font-bold text-slate-700 tracking-tight flex items-center gap-2" style="font-family:'Plus Jakarta Sans',sans-serif;">
          <span>Deep-Dive Analytics Blocks</span>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
            {{ count($activeBriefWidgets) }} active
          </span>
        </h2>
      </div>
    </div>

    @if(empty($activeBriefWidgets))
    <div class="p-8 text-center rounded-2xl bg-white border border-dashed border-slate-200">
      <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 mx-auto flex items-center justify-center mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
        </svg>
      </div>
      <h3 class="text-sm font-bold text-slate-700">No Deep-Dive Analytics Blocks Selected</h3>
      <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">Select detailed breakdowns, charts, schedules, and attendance tables to display below your summary metrics.</p>
      @if($canManageWidgets ?? false)
      <button type="button" @click="openSettings('detailed')" class="mt-4 px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer">
        Configure Deep-Dive Blocks
      </button>
      @endif
    </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

      {{-- 1. Student Overview --}}
      @if($showStudentOverview)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Student Overview</h3>
                <p class="text-[11px] text-slate-400">Demographic composition &amp; class distribution</p>
              </div>
            </div>
            <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100/80 transition-colors">
              <span>Manage Students &rarr;</span>
            </a>
          </div>

          {{-- Demographic Breakdown Pills --}}
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-3.5">
            <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-100/80">
              <p class="text-[10.5px] font-semibold text-blue-700">Boys</p>
              <p class="text-lg font-bold text-slate-800 mt-0.5">{{ number_format($studentDemographics['boys'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ ($studentDemographics['total'] ?? 0) > 0 ? round(($studentDemographics['boys'] / $studentDemographics['total']) * 100, 1) : 0 }}% of total</p>
            </div>
            <div class="p-2.5 rounded-xl bg-pink-50/70 border border-pink-100/80">
              <p class="text-[10.5px] font-semibold text-pink-700">Girls</p>
              <p class="text-lg font-bold text-slate-800 mt-0.5">{{ number_format($studentDemographics['girls'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ ($studentDemographics['total'] ?? 0) > 0 ? round(($studentDemographics['girls'] / $studentDemographics['total']) * 100, 1) : 0 }}% of total</p>
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100/80">
              <p class="text-[10.5px] font-semibold text-emerald-700">Day Scholars</p>
              <p class="text-lg font-bold text-slate-800 mt-0.5">{{ number_format($studentDemographics['day_scholars'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Commuters</p>
            </div>
            <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-100/80">
              <p class="text-[10.5px] font-semibold text-purple-700">Hostellers</p>
              <p class="text-lg font-bold text-slate-800 mt-0.5">{{ number_format($studentDemographics['hostellers'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Boarding</p>
            </div>
          </div>

          {{-- Class strength distribution list/grid --}}
          @if(isset($classStrength) && $classStrength->isNotEmpty())
          <div class="pt-3 border-t border-slate-100">
            <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Class Strength Distribution</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-[140px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
              @foreach($classStrength as $cs)
              <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                <span class="font-medium text-slate-700 truncate mr-1">{{ $cs->class_name }}</span>
                <span class="font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded text-[11px]">{{ $cs->student_count }}</span>
              </div>
              @endforeach
            </div>
          </div>
          @else
          <div class="pt-3 border-t border-slate-100 text-center py-3 text-xs text-slate-400">
            No class enrollment data available.
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- 2. Student Demographic Breakdown --}}
      @if($showStudentDemographics)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Student Demographic Breakdown</h3>
                <p class="text-[11px] text-slate-400">Gender distribution &amp; residential boarding status</p>
              </div>
            </div>
            <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100/80 transition-colors">
              <span>View Students &rarr;</span>
            </a>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-4">
            <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-100/80">
              <p class="text-[11px] font-semibold text-blue-700">Boys</p>
              <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($studentDemographics['boys'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ ($studentDemographics['total'] ?? 0) > 0 ? round(($studentDemographics['boys'] / $studentDemographics['total']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 rounded-xl bg-pink-50/70 border border-pink-100/80">
              <p class="text-[11px] font-semibold text-pink-700">Girls</p>
              <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($studentDemographics['girls'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ ($studentDemographics['total'] ?? 0) > 0 ? round(($studentDemographics['girls'] / $studentDemographics['total']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100/80">
              <p class="text-[11px] font-semibold text-emerald-700">Day Scholars</p>
              <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($studentDemographics['day_scholars'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Commuters</p>
            </div>
            <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-100/80">
              <p class="text-[11px] font-semibold text-purple-700">Hostellers</p>
              <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($studentDemographics['hostellers'] ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Boarding</p>
            </div>
          </div>

          {{-- Balanced demographic ratio visual bar --}}
          @php
            $tot = $studentDemographics['total'] ?? 0;
            $boysPct = $tot > 0 ? round(($studentDemographics['boys'] / $tot) * 100, 1) : 50;
            $girlsPct = $tot > 0 ? (100 - $boysPct) : 50;
          @endphp
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
            <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
              <span class="text-blue-700">Boys: {{ $boysPct }}%</span>
              <span class="text-slate-500 font-normal">Gender Balance</span>
              <span class="text-pink-700">Girls: {{ $girlsPct }}%</span>
            </div>
            <div class="w-full h-2.5 rounded-full bg-slate-200 overflow-hidden flex">
              <div class="h-full bg-blue-500" style="width: {{ $boysPct }}%"></div>
              <div class="h-full bg-pink-500" style="width: {{ $girlsPct }}%"></div>
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- 3. Class-wise Student Distribution --}}
      @if($showClassDistribution)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Class-wise Student Distribution</h3>
                <p class="text-[11px] text-slate-400">Total active students per class</p>
              </div>
            </div>
            <a href="{{ route('classes.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100/80 transition-colors">
              <span>Manage Classes &rarr;</span>
            </a>
          </div>
          @if(isset($classStrength) && $classStrength->isNotEmpty())
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-[190px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            @foreach($classStrength as $cs)
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs hover:bg-indigo-50/30 transition-colors">
              <span class="font-medium text-slate-700 truncate mr-1">{{ $cs->class_name }}</span>
              <span class="font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-lg text-[11px]">{{ $cs->student_count }}</span>
            </div>
            @endforeach
          </div>
          @else
          <div class="p-6 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
            <p class="text-xs font-semibold text-slate-600">No active class enrollments found</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Classes and student enrollments will appear here once configured.</p>
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- 4. Section Distribution --}}
      @if($showSectionDistribution)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Section Distribution</h3>
                <p class="text-[11px] text-slate-400">Total active students per section</p>
              </div>
            </div>
            <a href="{{ route('classes.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-sky-600 bg-sky-50 hover:bg-sky-100/80 transition-colors">
              <span>View Sections &rarr;</span>
            </a>
          </div>
          @if(isset($sectionDistribution) && $sectionDistribution->isNotEmpty())
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-[190px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            @foreach($sectionDistribution as $sec)
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs hover:bg-sky-50/30 transition-colors">
              <span class="font-medium text-slate-700 truncate mr-1">{{ $sec->section_name }}</span>
              <span class="font-bold text-sky-600 bg-sky-50 border border-sky-100 px-2 py-0.5 rounded-lg text-[11px]">{{ $sec->student_count }}</span>
            </div>
            @endforeach
          </div>
          @else
          <div class="p-6 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
            <p class="text-xs font-semibold text-slate-600">No active section allocations found</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Section assignments will appear here once allocated to classes.</p>
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- 5. Attendance Overview --}}
      @if($showAttendanceOverview)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Attendance Overview</h3>
                <p class="text-[11px] text-slate-400">Today's student attendance &amp; staff status</p>
              </div>
            </div>
            <a href="{{ route('reports.attendance-register') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100/80 transition-colors">
              <span>Register &rarr;</span>
            </a>
          </div>

          <div class="space-y-3">
            {{-- Students Attendance Breakdown --}}
            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Student Attendance</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($todayAttStats['percentage'] ?? 0) >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                  {{ $todayAttStats['percentage'] !== null ? $todayAttStats['percentage'] . '%' : 'Pending' }}
                </span>
              </div>
              <div class="grid grid-cols-4 gap-2 text-center">
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">Present</p>
                  <p class="text-sm font-bold text-emerald-600 mt-0.5">{{ $todayAttStats['present'] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">Absent</p>
                  <p class="text-sm font-bold text-rose-600 mt-0.5">{{ $todayAttStats['absent'] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">Half Day</p>
                  <p class="text-sm font-bold text-amber-600 mt-0.5">{{ $todayAttStats['half_day'] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">Late</p>
                  <p class="text-sm font-bold text-indigo-600 mt-0.5">{{ $todayAttStats['late'] ?? 0 }}</p>
                </div>
              </div>
            </div>

            {{-- Staff Attendance Breakdown --}}
            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Staff Attendance</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                  {{ $staffSummary['total'] ?? 0 }} Total Staff
                </span>
              </div>
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">Present</p>
                  <p class="text-sm font-bold text-emerald-600 mt-0.5">{{ $staffSummary['present_today'] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">On Leave</p>
                  <p class="text-sm font-bold text-amber-600 mt-0.5">{{ $staffSummary['on_leave_today'] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-lg bg-white border border-slate-100">
                  <p class="text-[10px] text-slate-400">Teaching</p>
                  <p class="text-sm font-bold text-indigo-600 mt-0.5">{{ $staffSummary['teaching'] ?? 0 }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- 6. Fee Overview --}}
      @if($showFeeOverview)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Fee Overview</h3>
                <p class="text-[11px] text-slate-400">Collection progress &amp; outstanding dues</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <a href="{{ route('reports.fee-collection-register') }}" class="btn-sm btn-secondary text-xs">Register</a>
              <a href="{{ route('fees.index') }}" class="btn-sm btn-primary text-xs">Fee Billing &rarr;</a>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3.5">
            <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100 flex flex-col justify-between">
              <span class="text-[10.5px] font-bold text-emerald-800 uppercase tracking-wider">Total Realized</span>
              <div class="mt-2">
                <p class="text-lg font-black text-slate-800">₹{{ number_format($feeSummary['collected_total'] ?? $totalIncome ?? 0, 2) }}</p>
                <p class="text-[10px] text-slate-500 mt-0.5">Cumulative collections</p>
              </div>
            </div>
            <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100 flex flex-col justify-between">
              <span class="text-[10.5px] font-bold text-blue-800 uppercase tracking-wider">This Month</span>
              <div class="mt-2">
                <p class="text-lg font-black text-slate-800">₹{{ number_format($feeSummary['collected_month'] ?? 0, 2) }}</p>
                <p class="text-[10px] text-slate-500 mt-0.5">{{ now()->format('F Y') }}</p>
              </div>
            </div>
            <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100 flex flex-col justify-between">
              <span class="text-[10.5px] font-bold text-amber-800 uppercase tracking-wider">Outstanding Dues</span>
              <div class="mt-2">
                <p class="text-lg font-black text-slate-800">₹{{ number_format($pendingFees ?? 0, 2) }}</p>
                <p class="text-[10px] text-slate-500 mt-0.5">Unpaid dues</p>
              </div>
            </div>
          </div>

          {{-- Collection realization progress bar --}}
          @php
            $realized = (float)($feeSummary['collected_total'] ?? $totalIncome ?? 0);
            $pending = (float)($pendingFees ?? 0);
            $feeTot = $realized + $pending;
            $feeRate = $feeTot > 0 ? round(($realized / $feeTot) * 100, 1) : 0;
          @endphp
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
            <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
              <span class="text-emerald-700">Realization Rate: {{ $feeRate }}%</span>
              <span class="text-slate-400 text-[11px]">Total Billed: ₹{{ number_format($feeTot, 2) }}</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $feeRate }}%"></div>
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- 7. Admissions Overview --}}
      @if($showAdmissionsOverview)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Admissions Overview</h3>
                <p class="text-[11px] text-slate-400">Enquiries pipeline &amp; applicant review</p>
              </div>
            </div>
            <a href="{{ route('admissions.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100/80 transition-colors">
              <span>View Admissions &rarr;</span>
            </a>
          </div>

          <div class="space-y-3">
            {{-- Pipeline summary links --}}
            <div class="grid grid-cols-3 gap-2">
              <a href="{{ route('admissions.index', ['status' => 'pending']) }}" class="p-2.5 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all flex flex-col justify-between group">
                <span class="text-[10.5px] font-semibold text-slate-500 group-hover:text-blue-600">Pending Enquiries</span>
                <span class="text-base font-bold text-blue-700 mt-1">{{ $pendingAlerts['admissions'] ?? 0 }}</span>
              </a>
              <a href="{{ Route::has('hr.leaves') ? route('hr.leaves') : (Route::has('attendance.leave') ? route('attendance.leave') : url('/hr/leaves')) }}" class="p-2.5 rounded-xl border border-slate-100 hover:border-purple-200 hover:bg-purple-50/30 transition-all flex flex-col justify-between group">
                <span class="text-[10.5px] font-semibold text-slate-500 group-hover:text-purple-600">Leave Requests</span>
                <span class="text-base font-bold text-purple-700 mt-1">{{ $pendingAlerts['leaves'] ?? 0 }}</span>
              </a>
              <a href="{{ Route::has('students.tc-requests') ? route('students.tc-requests') : url('/students/tc-requests') }}" class="p-2.5 rounded-xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50/30 transition-all flex flex-col justify-between group">
                <span class="text-[10.5px] font-semibold text-slate-500 group-hover:text-emerald-600">TC Requests</span>
                <span class="text-base font-bold text-emerald-700 mt-1">{{ $pendingAlerts['tc'] ?? 0 }}</span>
              </a>
            </div>

            {{-- Recent admissions list --}}
            <div class="pt-2 border-t border-slate-100">
              <p class="text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2">Recent Applicants</p>
              <div class="space-y-2 max-h-[140px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
                @forelse($recentAdmissions ?? [] as $adm)
                <div class="p-2 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-slate-50/60 transition-all flex items-center justify-between gap-3 group">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                      {{ strtoupper(substr($adm->student_name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs font-bold text-slate-800 truncate group-hover:text-indigo-600 transition-colors">{{ $adm->student_name ?? 'Applicant' }}</p>
                      <p class="text-[10px] text-slate-400 truncate">{{ $adm->phone ?? '' }} &bull; Class {{ $adm->class_applied ?? '—' }}</p>
                    </div>
                  </div>
                  <div class="text-right flex-shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ ($adm->status ?? '') === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                      {{ ucfirst($adm->status ?? 'pending') }}
                    </span>
                  </div>
                </div>
                @empty
                <div class="p-4 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl">
                  No recent admission applications
                </div>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- 8. Upcoming Events --}}
      @if($showUpcomingEvents)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Upcoming Events</h3>
                <p class="text-[11px] text-slate-400">Scheduled campus activities &amp; milestones</p>
              </div>
            </div>
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100/80 transition-colors">
              <span>View All &rarr;</span>
            </a>
          </div>

          <div class="space-y-2 max-h-[190px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            @forelse($upcomingEvents ?? [] as $ev)
            @php
              $eDate = !empty($ev->event_date) ? \Carbon\Carbon::parse($ev->event_date) : null;
            @endphp
            <div class="p-2.5 rounded-xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50/20 transition-all flex items-center gap-3 group">
              <div class="flex-shrink-0 w-10 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-center">
                <p class="text-xs font-bold leading-tight">{{ $eDate ? $eDate->format('d') : '—' }}</p>
                <p class="text-[9px] uppercase font-semibold text-emerald-600">{{ $eDate ? $eDate->format('M') : '' }}</p>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-slate-800 truncate group-hover:text-emerald-700 transition-colors">{{ $ev->name }}</p>
                <p class="text-[10.5px] text-slate-400 truncate">{{ $ev->venue ?? 'Campus' }} &bull; {{ $eDate ? $eDate->diffForHumans() : '' }}</p>
              </div>
            </div>
            @empty
            <div class="p-6 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
              <svg class="w-8 h-8 text-slate-300 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <p class="text-xs font-semibold text-slate-600">No upcoming events scheduled</p>
              <p class="text-[11px] text-slate-400 mt-0.5">Check back later or schedule an event from the Events module.</p>
            </div>
            @endforelse
          </div>
        </div>
      </div>
      @endif

      {{-- 9. School Notices --}}
      @if($showNotices)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">School Notices</h3>
                <p class="text-[11px] text-slate-400">Announcements for students, teachers &amp; parents</p>
              </div>
            </div>
            <a href="{{ Route::has('academics.notices') ? route('academics.notices') : (Route::has('notices.index') ? route('notices.index') : url('/academics/notices')) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100/80 transition-colors">
              <span>View All &rarr;</span>
            </a>
          </div>

          <div class="space-y-2 max-h-[190px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            @forelse($recentNotices ?? [] as $nt)
            @php
              $nRaw = $nt->publish_date ?? ($nt->created_at ?? null);
              $nDate = !empty($nRaw) ? \Carbon\Carbon::parse($nRaw) : null;
            @endphp
            <div class="p-2.5 rounded-xl border border-slate-100 hover:border-amber-200 hover:bg-amber-50/20 transition-all flex items-center justify-between gap-3 group">
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-slate-800 truncate group-hover:text-amber-700 transition-colors">{{ $nt->title }}</p>
                <p class="text-[10.5px] text-slate-400 truncate">{{ $nt->audience ?? ($nt->notice_type ?? 'General') }} &bull; {{ $nDate ? $nDate->format('d M Y') : '' }}</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 flex-shrink-0">
                Notice
              </span>
            </div>
            @empty
            <div class="p-6 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
              <svg class="w-8 h-8 text-slate-300 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
              <p class="text-xs font-semibold text-slate-600">No active school notices</p>
              <p class="text-[11px] text-slate-400 mt-0.5">Publish notices from the Academics Notice Board.</p>
            </div>
            @endforelse
          </div>
        </div>
      </div>
      @endif

      {{-- 10. Circulars & Orders --}}
      @if($showCirculars)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Circulars &amp; Orders</h3>
                <p class="text-[11px] text-slate-400">Official administration circulars &amp; directives</p>
              </div>
            </div>
            <a href="{{ Route::has('academics.notices') ? route('academics.notices') : (Route::has('circulars.index') ? route('circulars.index') : url('/academics/notices')) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100/80 transition-colors">
              <span>View All &rarr;</span>
            </a>
          </div>

          <div class="space-y-2 max-h-[190px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            @forelse($recentCirculars ?? [] as $circ)
            @php
              $cRaw = $circ->publish_date ?? ($circ->created_at ?? null);
              $cDate = !empty($cRaw) ? \Carbon\Carbon::parse($cRaw) : null;
            @endphp
            <div class="p-2.5 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/20 transition-all flex items-center justify-between gap-3 group">
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-slate-800 truncate group-hover:text-blue-700 transition-colors">{{ $circ->title }}</p>
                <p class="text-[10.5px] text-slate-400 truncate">Ref: {{ $circ->circular_no ?? 'CIR' }} &bull; {{ $cDate ? $cDate->format('d M Y') : '' }}</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800 flex-shrink-0">
                Official
              </span>
            </div>
            @empty
            <div class="p-6 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
              <svg class="w-8 h-8 text-slate-300 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <p class="text-xs font-semibold text-slate-600">No recent circulars published</p>
              <p class="text-[11px] text-slate-400 mt-0.5">Official circulars will appear here when issued.</p>
            </div>
            @endforelse
          </div>
        </div>
      </div>
      @endif

      {{-- 11. Birthday Wishes --}}
      @if($showBirthdayWishes)
      <div class="card p-5 border border-pink-200/80 bg-gradient-to-br from-pink-50/40 via-white to-amber-50/30 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-pink-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Birthday Wishes Today</h3>
                <p class="text-[11px] text-slate-400">Celebrating birthdays on {{ now()->format('d F') }}</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-pink-100 text-pink-700">
              {{ count($todayBirthdays ?? []) }} {{ Str::plural('Celebrant', count($todayBirthdays ?? [])) }}
            </span>
          </div>

          @if(isset($todayBirthdays) && count($todayBirthdays) > 0)
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[190px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            @foreach($todayBirthdays as $person)
            @php
              $pName   = data_get($person, 'name', 'Name');
              $pSub    = data_get($person, 'subtitle', data_get($person, 'role_detail', data_get($person, 'type_label', 'Member')));
              $pType   = data_get($person, 'type_label', data_get($person, 'type', 'Student'));
              $isStaff = strtolower(data_get($person, 'type', '')) === 'staff';
            @endphp
            <div class="p-2.5 rounded-xl bg-white border border-pink-100/90 shadow-2xs hover:shadow-xs transition-all flex items-center gap-3 group">
              <div class="w-9 h-9 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 text-white font-bold flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                {{ strtoupper(substr($pName, 0, 1)) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-slate-800 truncate group-hover:text-pink-600 transition-colors">{{ $pName }}</p>
                <p class="text-[10px] text-slate-500 truncate">{{ $pSub }}</p>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold {{ $isStaff ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} mt-0.5">
                  {{ $pType }}
                </span>
              </div>
              <span class="text-base flex-shrink-0" title="Happy Birthday!">&#127881;</span>
            </div>
            @endforeach
          </div>
          @else
          <div class="p-6 text-center rounded-xl bg-white/80 border border-dashed border-pink-200">
            <div class="w-10 h-10 rounded-full bg-pink-50 text-pink-500 mx-auto flex items-center justify-center mb-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-xs font-semibold text-slate-700">No birthdays today</p>
            <p class="text-[11px] text-slate-400 mt-0.5">No students or staff have a birthday on {{ now()->format('d F') }}. Check back tomorrow!</p>
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- 12. Revenue & Expenses Analytics --}}
      @if($showRevenueAnalytics)
      <div class="card p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between h-full">
        <div>
          <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-xs">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Revenue &amp; Expense Analytics</h3>
                <p class="text-[11px] text-slate-400">Financial performance across last 6 months</p>
              </div>
            </div>
            <a href="{{ Route::has('accounts.index') ? route('accounts.index') : (Route::has('fees.index') ? route('fees.index') : url('/accounts')) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-teal-600 bg-teal-50 hover:bg-teal-100/80 transition-colors">
              <span>Finance Reports</span>
              <span>&rarr;</span>
            </a>
          </div>

          {{-- Financial KPI cards --}}
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-3">
            <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100/80">
              <p class="text-[10.5px] font-semibold text-emerald-700">Total Income</p>
              <p class="text-lg font-bold text-slate-800 mt-0.5">₹{{ number_format($totalIncome ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Collections</p>
            </div>
            <div class="p-3 rounded-xl bg-rose-50/70 border border-rose-100/80">
              <p class="text-[10.5px] font-semibold text-rose-700">Total Expenses</p>
              <p class="text-lg font-bold text-slate-800 mt-0.5">₹{{ number_format($totalExpenses ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Operations</p>
            </div>
            <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-100/80">
              <p class="text-[10.5px] font-semibold text-indigo-700">Net Operating</p>
              <p class="text-lg font-bold {{ ($netOperating ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }} mt-0.5">₹{{ number_format($netOperating ?? 0) }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ ($netOperating ?? 0) >= 0 ? 'Surplus' : 'Deficit' }}</p>
            </div>
          </div>

          {{-- 6-Month breakdown table/grid --}}
          @if(isset($revenueAnalytics) && $revenueAnalytics->isNotEmpty())
          <div class="overflow-x-auto rounded-xl border border-slate-100 max-h-[140px] overflow-y-auto pr-1.5 [scrollbar-width:thin]">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 font-medium sticky top-0">
                <tr>
                  <th class="py-2 px-3">Month</th>
                  <th class="py-2 px-3 text-right">Income</th>
                  <th class="py-2 px-3 text-right">Expenses</th>
                  <th class="py-2 px-3 text-right">Net Margin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                @foreach($revenueAnalytics as $row)
                @php
                  $rMonth   = data_get($row, 'short_label', data_get($row, 'month_label', data_get($row, 'month', 'Month')));
                  $rIncome  = (float) data_get($row, 'income', 0);
                  $rExpense = (float) data_get($row, 'expense', data_get($row, 'expenses', 0));
                  $rNet     = $rIncome - $rExpense;
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                  <td class="py-1.5 px-3 font-bold text-slate-800">{{ $rMonth }}</td>
                  <td class="py-1.5 px-3 text-right text-emerald-600 font-semibold">₹{{ number_format($rIncome) }}</td>
                  <td class="py-1.5 px-3 text-right text-rose-600 font-semibold">₹{{ number_format($rExpense) }}</td>
                  <td class="py-1.5 px-3 text-right font-bold {{ $rNet >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    ₹{{ number_format($rNet) }}
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @else
          <div class="p-6 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
            <p class="text-xs font-semibold text-slate-600">No financial transaction history available</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Revenue and expense entries will appear here once recorded in Accounts.</p>
          </div>
          @endif
        </div>
      </div>
      @endif

    </div>
    @endif

  </div>

  </div>

{{-- ═══════════════════════════════════════════════════════════════
     TEACHER DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'teaching')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">My Dashboard</h1>
      <p class="page-subtitle">Welcome back, {{ auth()->user()->name }} &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="flex gap-2 flex-wrap items-center">
      <span class="badge-blue text-sm px-3 py-1">{{ $currentYear?->name ?? '—' }}</span>
      <a href="{{ route('academics.timetable') }}" class="btn-sm btn-secondary inline-flex items-center gap-1.5 shadow-2xs font-semibold">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>View Timetable</span>
      </a>
      <a href="{{ route('attendance.mark') }}" class="btn-sm btn-primary">Mark Attendance</a>
    </div>
  </div>

  {{-- Quick stats --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      </div>
      <p class="stat-number">{{ $myClasses->pluck('class_name')->unique()->count() }}</p>
      <p class="text-xs text-slate-500 mt-1">My Classes</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-green-500 to-emerald-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <p class="stat-number">{{ $myScheduleToday->count() }}</p>
      <p class="text-xs text-slate-500 mt-1">Classes Today</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      </div>
      <p class="stat-number {{ $myAttendancePending > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ $myAttendancePending }}</p>
      <p class="text-xs text-slate-500 mt-1">Attendance Pending</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-violet-500 to-purple-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
      </div>
      <p class="stat-number">{{ $pendingHomework }}</p>
      <p class="text-xs text-slate-500 mt-1">Active Homework</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Today's schedule --}}
    <div class="card lg:col-span-2">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-slate-800">Today's Schedule — {{ now()->format('l') }}</h3>
        <a href="{{ route('academics.timetable') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1 transition">
          <span>View Full Timetable</span>
          <span>&rarr;</span>
        </a>
      </div>
      @if($myScheduleToday->isEmpty())
        <div class="text-center py-10 text-slate-400">
          <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <p class="text-sm">No classes scheduled for today</p>
        </div>
      @else
        <div class="space-y-2">
          @foreach($myScheduleToday as $period)
          <div class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100">
            <div class="flex-shrink-0 text-center w-20">
              <p class="text-xs font-bold text-blue-700">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }}</p>
              <p class="text-xs text-blue-400">{{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</p>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-slate-800 text-sm">{{ $period->subject_name ?? 'Period' }}</p>
              <p class="text-xs text-slate-500">{{ $period->class_name }}</p>
            </div>
          </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Quick actions + notices --}}
    <div class="space-y-4">
      <div class="card">
        <h3 class="font-semibold text-slate-800 mb-3">Quick Actions</h3>
        <div class="space-y-2">
          <a href="{{ route('academics.timetable') }}" class="flex items-center gap-2.5 p-2.5 rounded-lg hover:bg-blue-50 transition group text-sm">
            <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <span class="font-medium text-slate-700">View Timetable</span>
          </a>
          <a href="{{ route('attendance.mark') }}" class="flex items-center gap-2.5 p-2.5 rounded-lg hover:bg-green-50 transition group text-sm">
            <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center group-hover:bg-green-200 transition flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
            </div>
            <span class="font-medium text-slate-700">Mark Attendance</span>
          </a>
          <a href="{{ route('examinations.index') }}" class="flex items-center gap-2.5 p-2.5 rounded-lg hover:bg-amber-50 transition group text-sm">
            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center group-hover:bg-amber-200 transition flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <span class="font-medium text-slate-700">Examinations</span>
          </a>
          <a href="{{ route('lms.index') }}" class="flex items-center gap-2.5 p-2.5 rounded-lg hover:bg-purple-50 transition group text-sm">
            <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13"/></svg>
            </div>
            <span class="font-medium text-slate-700">LMS / Lessons</span>
          </a>
        </div>
      </div>
      @if($recentNotices->isNotEmpty())
      <div class="card">
        <h3 class="font-semibold text-slate-800 mb-3">📢 Notices</h3>
        @foreach($recentNotices as $n)
        <div class="py-2 border-b border-slate-100 last:border-0">
          <p class="text-sm text-slate-700 line-clamp-2">{{ $n->title }}</p>
          <p class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($n->publish_date)->format('d M') }}</p>
        </div>
        @endforeach
      </div>
      @endif
    </div>
  </div>

  {{-- Upcoming exams --}}
  @if($upcomingExams->isNotEmpty())
  <div class="card">
    <h3 class="font-semibold text-slate-800 mb-4">Upcoming Examinations</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100">
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Exam</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Class</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">In</th>
        </tr></thead>
        <tbody>
          @foreach($upcomingExams as $ex)
          <tr class="border-b border-slate-50">
            <td class="py-2.5 font-medium text-slate-700">{{ $ex->title }}</td>
            <td class="py-2.5 text-slate-500">{{ $ex->class_name }}</td>
            <td class="py-2.5 text-slate-500">{{ $ex->exam_date->format('d M Y') }}</td>
            <td class="py-2.5"><span class="badge-blue text-xs">{{ $ex->exam_date->diffForHumans() }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

{{-- ═══════════════════════════════════════════════════════════════
     FINANCE / ACCOUNTANT DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'finance')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Finance Dashboard</h1>
      <p class="page-subtitle">Fee collection & outstanding overview &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <span class="badge-blue text-sm px-3 py-1">{{ $stats['academic_year'] }}</span>
      <a href="{{ route('fees.collect') }}" class="btn-sm btn-primary">Collect Fee</a>
      <a href="{{ route('reports.fee-collection-register') }}" class="btn-sm btn-secondary">Fee Register</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-green-500 to-emerald-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="stat-number text-green-600">₹{{ number_format($todayCollection) }}</p>
      <p class="text-xs text-slate-500 mt-1">Collected Today</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <p class="stat-number text-blue-600">₹{{ number_format($monthCollection) }}</p>
      <p class="text-xs text-slate-500 mt-1">This Month</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-rose-500 to-red-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <p class="stat-number text-rose-600">₹{{ number_format($outstanding) }}</p>
      <p class="text-xs text-slate-500 mt-1">Outstanding</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Payment mode breakdown --}}
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Payment Modes — This Month</h3>
      @if($paymentModes->isNotEmpty())
        @php $modeTotal = $paymentModes->sum('total') ?: 1; @endphp
        <div class="space-y-3">
          @foreach($paymentModes as $mode)
          @php $pct = round($mode->total / $modeTotal * 100); @endphp
          <div>
            <div class="flex justify-between text-xs text-slate-600 mb-1">
              <span class="capitalize font-medium">{{ str_replace('_',' ',$mode->payment_mode ?? 'Other') }}</span>
              <span>₹{{ number_format($mode->total) }} ({{ $pct }}%)</span>
            </div>
            <div class="bg-slate-100 rounded-full h-2">
              <div class="bg-indigo-500 h-2 rounded-full" style="width:{{ $pct }}%"></div>
            </div>
          </div>
          @endforeach
        </div>
      @else
        <p class="text-slate-400 text-sm">No payments this month yet.</p>
      @endif
    </div>

    {{-- Recent payments --}}
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Recent Payments</h3>
      <div class="space-y-2 max-h-72 overflow-y-auto">
        @forelse($recentPayments as $p)
        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-700 truncate">{{ $p->first_name }} {{ $p->last_name }}</p>
            <p class="text-xs text-slate-400">#{{ $p->receipt_number }} &middot; {{ \Carbon\Carbon::parse($p->payment_date)->format('d M') }} &middot; {{ ucfirst($p->payment_mode) }}</p>
          </div>
          <span class="text-sm font-bold text-green-600 ml-2 flex-shrink-0">₹{{ number_format($p->amount) }}</span>
        </div>
        @empty
        <p class="text-slate-400 text-sm">No recent payments.</p>
        @endforelse
      </div>
    </div>
  </div>

  @if($collectionTrend->isNotEmpty())
  <div class="card">
    <h3 class="font-semibold text-slate-800 mb-4">Collection Trend (Last 6 Months)</h3>
    @php $maxVal = $collectionTrend->max('total') ?: 1; @endphp
    <div class="flex items-end gap-3 h-36">
      @foreach($collectionTrend as $row)
      @php $pct = round($row->total / $maxVal * 100); @endphp
      <div class="flex-1 flex flex-col items-center gap-1">
        <span class="text-xs text-slate-500">₹{{ number_format($row->total/1000,1) }}k</span>
        <div class="w-full bg-emerald-500 rounded-t" style="height:{{ max($pct,4) }}%"></div>
        <span class="text-xs text-slate-400">{{ \Carbon\Carbon::createFromFormat('Y-m',$row->month)->format('M y') }}</span>
      </div>
      @endforeach
    </div>
  </div>
  @endif

{{-- ═══════════════════════════════════════════════════════════════
     HR DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'hr')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">HR Dashboard</h1>
      <p class="page-subtitle">Staff management overview &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('hr.employees') }}" class="btn-sm btn-primary">All Employees</a>
      <a href="{{ route('hr.leaves') }}" class="btn-sm btn-secondary">Leave Requests</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-teal-500 to-cyan-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <p class="stat-number">{{ $totalStaff }}</p>
      <p class="text-xs text-slate-500 mt-1">Total Staff</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-green-500 to-emerald-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="stat-number text-green-600">{{ $staffPresent }}</p>
      <p class="text-xs text-slate-500 mt-1">Present Today</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      </div>
      <p class="stat-number {{ $pendingLeaves > 0 ? 'text-amber-600' : '' }}">{{ $pendingLeaves }}</p>
      <p class="text-xs text-slate-500 mt-1">Pending Leave Requests</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Department Strength</h3>
      @if($departmentStrength->isNotEmpty())
        @php $maxDept = $departmentStrength->max('count') ?: 1; @endphp
        <div class="space-y-2">
          @foreach($departmentStrength as $dept)
          <div class="flex items-center gap-2">
            <div class="w-28 text-xs text-slate-600 truncate flex-shrink-0">{{ $dept->department ?? 'Unassigned' }}</div>
            <div class="flex-1 bg-slate-100 rounded-full h-2.5">
              <div class="bg-teal-500 h-2.5 rounded-full" style="width:{{ round($dept->count/$maxDept*100) }}%"></div>
            </div>
            <div class="w-6 text-xs text-right text-slate-600 font-medium flex-shrink-0">{{ $dept->count }}</div>
          </div>
          @endforeach
        </div>
      @else
        <p class="text-slate-400 text-sm">No department data.</p>
      @endif
    </div>
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Recent Joinings</h3>
      @forelse($recentJoinings as $emp)
      <div class="flex items-center gap-3 py-2 border-b border-slate-100 last:border-0">
        <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-600 flex-shrink-0">{{ strtoupper(substr($emp->name,0,1)) }}</div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-slate-700 truncate">{{ $emp->name }}</p>
          <p class="text-xs text-slate-400">{{ $emp->designation ?? '—' }} &middot; {{ \Carbon\Carbon::parse($emp->joining_date)->format('d M Y') }}</p>
        </div>
      </div>
      @empty
      <p class="text-slate-400 text-sm">No recent joinings.</p>
      @endforelse
    </div>
  </div>

{{-- ═══════════════════════════════════════════════════════════════
     LIBRARY DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'library')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Library Dashboard</h1>
      <p class="page-subtitle">Books & issue management &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('library.issue') }}" class="btn-sm btn-primary">Issue Book</a>
      <a href="{{ route('library.index') }}" class="btn-sm btn-secondary">All Books</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-pink-500 to-rose-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
      </div>
      <p class="stat-number">{{ $totalBooks }}</p>
      <p class="text-xs text-slate-500 mt-1">Total Books</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      </div>
      <p class="stat-number">{{ $issuedBooks }}</p>
      <p class="text-xs text-slate-500 mt-1">Currently Issued</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-rose-500 to-red-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="stat-number text-rose-600">{{ $overdueBooks }}</p>
      <p class="text-xs text-slate-500 mt-1">Overdue Returns</p>
    </div>
  </div>

  <div class="card">
    <h3 class="font-semibold text-slate-800 mb-4">Recent Issues</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100">
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Book</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Issued</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Due</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
        </tr></thead>
        <tbody>
          @forelse($recentIssues as $issue)
          <tr class="border-b border-slate-50">
            <td class="py-2.5 font-medium text-slate-700 max-w-xs truncate">{{ $issue->title }}</td>
            <td class="py-2.5 text-slate-500">{{ $issue->first_name ?? '—' }} {{ $issue->last_name ?? '' }}</td>
            <td class="py-2.5 text-slate-500">{{ \Carbon\Carbon::parse($issue->issue_date)->format('d M') }}</td>
            <td class="py-2.5 text-slate-500">{{ \Carbon\Carbon::parse($issue->due_date)->format('d M') }}</td>
            <td class="py-2.5">
              @if($issue->return_date)
                <span class="badge-green text-xs">Returned</span>
              @elseif(\Carbon\Carbon::parse($issue->due_date)->isPast())
                <span class="badge-red text-xs">Overdue</span>
              @else
                <span class="badge-blue text-xs">Issued</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="py-6 text-center text-slate-400 text-sm">No recent issues.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

{{-- ═══════════════════════════════════════════════════════════════
     TRANSPORT DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'transport')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Transport Dashboard</h1>
      <p class="page-subtitle">Vehicles & routes overview &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <a href="{{ route('transport.index') }}" class="btn-sm btn-secondary">Manage Transport</a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-indigo-500 to-blue-700 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <p class="stat-number">{{ $totalVehicles }}</p>
      <p class="text-xs text-slate-500 mt-1">Active Vehicles</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-green-500 to-emerald-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4"/></svg>
      </div>
      <p class="stat-number">{{ $activeRoutes }}</p>
      <p class="text-xs text-slate-500 mt-1">Active Routes</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-sky-500 to-blue-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1"/></svg>
      </div>
      <p class="stat-number">{{ $studentsOnBus }}</p>
      <p class="text-xs text-slate-500 mt-1">Students Using Bus</p>
    </div>
  </div>

  @if($recentFuelLogs->isNotEmpty())
  <div class="card">
    <h3 class="font-semibold text-slate-800 mb-4">Recent Fuel Logs</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100">
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Vehicle</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Litres</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Odometer</th>
        </tr></thead>
        <tbody>
          @foreach($recentFuelLogs as $log)
          <tr class="border-b border-slate-50">
            <td class="py-2.5 font-medium text-slate-700">{{ $log->registration_number }}</td>
            <td class="py-2.5 text-slate-500">{{ \Carbon\Carbon::parse($log->date)->format('d M Y') }}</td>
            <td class="py-2.5 text-slate-600">{{ $log->litres }}L</td>
            <td class="py-2.5 text-slate-600">₹{{ number_format($log->amount) }}</td>
            <td class="py-2.5 text-slate-500">{{ number_format($log->odometer) }} km</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

{{-- ═══════════════════════════════════════════════════════════════
     HOSTEL DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'hostel')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Hostel Dashboard</h1>
      <p class="page-subtitle">Room occupancy & outpass overview &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <a href="{{ route('hostel.index') }}" class="btn-sm btn-secondary">Manage Hostel</a>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="card text-center">
      <p class="stat-number">{{ $totalRooms }}</p>
      <p class="text-xs text-slate-500 mt-1">Total Rooms</p>
    </div>
    <div class="card text-center">
      <p class="stat-number text-green-600">{{ $occupiedRooms }}</p>
      <p class="text-xs text-slate-500 mt-1">Occupied</p>
    </div>
    <div class="card text-center">
      <p class="stat-number text-blue-600">{{ $totalResidents }}</p>
      <p class="text-xs text-slate-500 mt-1">Residents</p>
    </div>
    <div class="card text-center">
      <p class="stat-number {{ $pendingOutpass > 0 ? 'text-amber-600' : '' }}">{{ $pendingOutpass }}</p>
      <p class="text-xs text-slate-500 mt-1">Pending Outpass</p>
    </div>
  </div>

  @if($recentOutpass->isNotEmpty())
  <div class="card">
    <h3 class="font-semibold text-slate-800 mb-4">Recent Outpass Requests</h3>
    <div class="space-y-2">
      @foreach($recentOutpass as $op)
      <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
        <div class="min-w-0">
          <p class="text-sm font-medium text-slate-700">{{ $op->first_name }} {{ $op->last_name }}</p>
          <p class="text-xs text-slate-400">{{ $op->reason }} &middot; {{ \Carbon\Carbon::parse($op->from_date)->format('d M') }} – {{ \Carbon\Carbon::parse($op->to_date)->format('d M Y') }}</p>
        </div>
        <span class="ml-2 flex-shrink-0 text-xs px-2 py-0.5 rounded-full font-medium
          {{ $op->status === 'approved' ? 'bg-green-100 text-green-700' : ($op->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
          {{ ucfirst($op->status) }}
        </span>
      </div>
      @endforeach
    </div>
  </div>
  @endif

{{-- ═══════════════════════════════════════════════════════════════
     ADMISSIONS DASHBOARD
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'admissions')

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Admissions Dashboard</h1>
      <p class="page-subtitle">Enquiries & applications &mdash; {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('admissions.create') }}" class="btn-sm btn-primary">New Enquiry</a>
      <a href="{{ route('admissions.index') }}" class="btn-sm btn-secondary">All Enquiries</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      </div>
      <p class="stat-number">{{ $totalEnquiries }}</p>
      <p class="text-xs text-slate-500 mt-1">Total Enquiries</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="stat-number text-amber-600">{{ $pendingFollowups }}</p>
      <p class="text-xs text-slate-500 mt-1">Pending Followups</p>
    </div>
    <div class="card text-center">
      <div class="stat-icon bg-gradient-to-br from-green-500 to-emerald-600 mx-auto mb-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1"/></svg>
      </div>
      <p class="stat-number text-green-600">{{ $newAdmissionsMonth }}</p>
      <p class="text-xs text-slate-500 mt-1">Admitted This Month</p>
    </div>
  </div>

  <div class="card">
    <h3 class="font-semibold text-slate-800 mb-4">Recent Enquiries</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100">
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Class</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Phone</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
          <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
        </tr></thead>
        <tbody>
          @forelse($recentEnquiries as $enq)
          <tr class="border-b border-slate-50">
            <td class="py-2.5 font-medium text-slate-700">{{ $enq->student_name }}</td>
            <td class="py-2.5 text-slate-500">{{ $enq->class_applied ?? '—' }}</td>
            <td class="py-2.5 text-slate-500">{{ $enq->phone }}</td>
            <td class="py-2.5 text-slate-500">{{ \Carbon\Carbon::parse($enq->created_at)->format('d M Y') }}</td>
            <td class="py-2.5">
              <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize
                {{ $enq->status === 'converted' ? 'bg-green-100 text-green-700' : ($enq->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                {{ $enq->status }}
              </span>
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="py-6 text-center text-slate-400 text-sm">No recent enquiries.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

{{-- ═══════════════════════════════════════════════════════════════
     OPERATIONS DASHBOARD (inventory / events / alumni)
══════════════════════════════════════════════════════════════════ --}}
@elseif($dashboardType === 'operations')

  @if(($subType ?? 'general') === 'inventory')
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="page-title">Inventory Dashboard</h1>
        <p class="page-subtitle">Stock & movements overview &mdash; {{ now()->format('l, d M Y') }}</p>
      </div>
      <a href="{{ route('inventory.index') }}" class="btn-sm btn-secondary">Manage Inventory</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="card text-center">
        <p class="stat-number">{{ $totalItems ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Total Items</p>
      </div>
      <div class="card text-center">
        <p class="stat-number text-rose-600">{{ $lowStockItems ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Low Stock Items</p>
      </div>
    </div>
    @if(isset($recentMovements) && $recentMovements->isNotEmpty())
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Recent Movements</h3>
      <div class="space-y-2">
        @foreach($recentMovements as $mov)
        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
          <p class="text-sm font-medium text-slate-700">{{ $mov->name }}</p>
          <div class="flex items-center gap-2">
            <span class="text-xs {{ $mov->type === 'in' ? 'text-green-600' : 'text-rose-600' }} font-medium">
              {{ $mov->type === 'in' ? '+' : '-' }}{{ $mov->quantity }}
            </span>
            <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($mov->created_at)->format('d M') }}</span>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

  @elseif(($subType ?? '') === 'events')
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="page-title">Events Dashboard</h1>
        <p class="page-subtitle">Upcoming events &mdash; {{ now()->format('l, d M Y') }}</p>
      </div>
      <a href="{{ route('events.index') }}" class="btn-sm btn-secondary">Manage Events</a>
    </div>
    <div class="card">
      <p class="stat-number mb-1">{{ $totalEvents ?? 0 }}</p>
      <p class="text-xs text-slate-500">Total Events</p>
    </div>
    @if(isset($upcomingEvents) && $upcomingEvents->isNotEmpty())
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Upcoming Events</h3>
      <div class="space-y-3">
        @foreach($upcomingEvents as $ev)
        <div class="flex items-center gap-4 p-3 rounded-xl bg-slate-50">
          <div class="flex-shrink-0 w-12 text-center">
            <p class="text-lg font-bold text-indigo-600">{{ $ev->event_date->format('d') }}</p>
            <p class="text-xs text-slate-400">{{ $ev->event_date->format('M') }}</p>
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-slate-700 text-sm">{{ $ev->name }}</p>
            <p class="text-xs text-slate-400">{{ $ev->event_date->diffForHumans() }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

  @elseif(($subType ?? '') === 'alumni')
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="page-title">Alumni Dashboard</h1>
        <p class="page-subtitle">Alumni network &mdash; {{ now()->format('l, d M Y') }}</p>
      </div>
      <a href="{{ route('alumni.index') }}" class="btn-sm btn-secondary">Manage Alumni</a>
    </div>
    <div class="card">
      <p class="stat-number mb-1">{{ $totalAlumni ?? 0 }}</p>
      <p class="text-xs text-slate-500">Total Alumni</p>
    </div>
    @if(isset($recentAlumni) && $recentAlumni->isNotEmpty())
    <div class="card">
      <h3 class="font-semibold text-slate-800 mb-4">Recent Alumni</h3>
      <div class="space-y-2">
        @foreach($recentAlumni as $al)
        <div class="flex items-center gap-3 py-2 border-b border-slate-100 last:border-0">
          <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold text-amber-600 flex-shrink-0">{{ strtoupper(substr($al->name,0,1)) }}</div>
          <div>
            <p class="text-sm font-medium text-slate-700">{{ $al->name }}</p>
            <p class="text-xs text-slate-400">Batch {{ $al->graduation_year }} &middot; {{ $al->current_occupation ?? '—' }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif
  @else
    <h1 class="page-title">Operations Dashboard</h1>
    <p class="page-subtitle">Welcome, {{ auth()->user()->name }}</p>
  @endif

@endif

</div>
@endsection

@push('scripts')
<script>
(() => {
  const chartEl = document.getElementById('attendanceChart');
  if (chartEl && typeof Chart !== 'undefined') {
    new Chart(chartEl, {
      type: 'line',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Today'],
        datasets: [{
          label: 'Attendance %',
          data: [92, 88, 95, 91, 87, 93, 0],
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59,130,246,0.08)',
          borderWidth: 2.5,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#3B82F6',
          pointRadius: 4,
          pointHoverRadius: 6,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: '#fff', titleColor: '#0f172a', bodyColor: '#64748b', borderColor: '#e2e8f0', borderWidth: 1 }
        },
        scales: {
          y: { min: 70, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
          x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
        }
      }
    });
  }
})();
</script>

<script>
/**
 * Alpine.js component: customWidgets()
 *
 * Manages the Dashboard Customization on the management dashboard:
 * - Tab 1: Key Performance Metrics (Single Value KPIs grouped by module)
 * - Tab 2: Detailed Information Widgets (Multi-item breakdown widgets)
 * - Strict duplicate prevention across both tiers
 * - Direct card removal and settings-based removal
 * - Persistence via POST /dashboard/widgets/apply-settings
 * - Cancel restores previous configuration
 */
function customWidgets() {
  const singleValueCatalog = @json($singleValueCatalog ?? []);
  const singleValueGroups  = @json($singleValueGroups ?? []);
  const briefInfoCatalog   = @json($briefInfoCatalog ?? []);
  const briefInfoGroups    = @json($briefInfoGroups ?? []);
  const activeKpiKeys      = @json($activeKpiKeys ?? []);
  const activeDetailedKeys = @json($activeDetailedKeys ?? []);
  const rawCatalog         = @json($sourceCatalog ?? []);

  const detailedList = Object.entries(briefInfoCatalog).map(([key, item]) => ({
    key: key,
    label: item.label || key,
    category: 'detailed',
    description: item.description || '',
    icon: item.default_icon || '',
    color: item.default_color || 'from-indigo-500 to-blue-600',
  }));

  return {
    singleValueCatalog,
    singleValueGroups,
    briefInfoCatalog,
    briefInfoGroups,
    detailedList,
    settingsOpen: false,
    settingsTab: 'kpi', // 'kpi' or 'detailed'

    // Separate state for KPI and Detailed widgets
    selectedKpis: [],
    initialKpis: [],
    selectedDetailed: [],
    initialDetailed: [],
    savingSettings: false,
    applyAbortController: null,

    // Slide-over custom filtered card support
    panelOpen: false,
    editingId: null,
    saving: false,
    deleteModal: false,
    deleteWidgetId: null,
    deleteWidgetName: '',
    isDefault: false,

    form: {
      name: 'Student Count',
      source: 'student_count',
      icon_color: 'from-blue-500 to-indigo-600',
      filter_class_id: '',
      filter_section: '',
      filter_gender: '',
      filter_student_type: '',
      filter_status: 'active',
    },

    previewing: false,
    previewResult: null,
    previewDescription: '',
    previewUrl: null,

    get hasStudentFilters() {
      return this.form.source === 'student_count';
    },

    toggleScrollLock(lock) {
      const mainEl = document.querySelector('main');
      if (lock) {
        if (mainEl) {
          this.savedMainScrollTop = mainEl.scrollTop;
          mainEl.style.overflow = 'hidden';
          mainEl.style.touchAction = 'none';
        }
        document.body.style.overflow = 'hidden';
        document.body.style.touchAction = 'none';
        document.documentElement.style.overflow = 'hidden';
      } else {
        if (mainEl) {
          mainEl.style.overflow = '';
          mainEl.style.touchAction = '';
          if (this.savedMainScrollTop !== undefined) {
            mainEl.scrollTop = this.savedMainScrollTop;
          }
        }
        document.body.style.overflow = '';
        document.body.style.touchAction = '';
        document.documentElement.style.overflow = '';
      }
    },

    init() {
      this.selectedKpis = Array.isArray(activeKpiKeys) ? [...activeKpiKeys] : [];
      this.initialKpis  = [...this.selectedKpis];

      this.selectedDetailed = Array.isArray(activeDetailedKeys) ? [...activeDetailedKeys] : [];
      this.initialDetailed  = [...this.selectedDetailed];

      this.$watch('settingsOpen', val => {
        this.toggleScrollLock(val);
      });
    },

    openSettings(tab = 'kpi') {
      this.settingsTab      = tab;
      this.initialKpis      = [...this.selectedKpis];
      this.initialDetailed  = [...this.selectedDetailed];
      this.settingsOpen     = true;
      this.toggleScrollLock(true);
    },

    openAddKpi() {
      this.openSettings('kpi');
    },

    cancelSettings() {
      // If an apply operation is in flight, immediately stop it
      if (this.applyAbortController) {
        try {
          this.applyAbortController.abort();
        } catch(e) {}
        this.applyAbortController = null;
      }
      this.savingSettings = false;
      // Immediately restore the previous dashboard configuration
      this.selectedKpis     = [...this.initialKpis];
      this.selectedDetailed = [...this.initialDetailed];
      this.settingsOpen     = false;
      this.toggleScrollLock(false);
    },

    closeSettings() {
      // The X (close) button only closes the panel and preserves previously saved settings
      if (this.applyAbortController) {
        try {
          this.applyAbortController.abort();
        } catch(e) {}
        this.applyAbortController = null;
      }
      this.savingSettings = false;
      this.selectedKpis     = [...this.initialKpis];
      this.selectedDetailed = [...this.initialDetailed];
      this.settingsOpen     = false;
      this.toggleScrollLock(false);
    },

    // ── KPI Methods ──
    isKpiSelected(key) {
      return this.selectedKpis.includes(key);
    },

    toggleKpi(key) {
      if (this.isKpiSelected(key)) return;
      this.selectedKpis.push(key);
    },

    toggleKpiSwitch(key) {
      const idx = this.selectedKpis.indexOf(key);
      if (idx > -1) {
        this.selectedKpis.splice(idx, 1);
      } else {
        this.selectedKpis.push(key);
      }
    },

    getKpiCategoryState(keys) {
      if (!Array.isArray(keys) || keys.length === 0) return 'off';
      const activeCount = keys.filter(k => this.selectedKpis.includes(k)).length;
      if (activeCount === 0) return 'off';
      if (activeCount === keys.length) return 'on';
      return 'partial';
    },

    toggleKpiCategory(keys) {
      if (!Array.isArray(keys) || keys.length === 0) return;
      const state = this.getKpiCategoryState(keys);
      if (state === 'on') {
        this.selectedKpis = this.selectedKpis.filter(k => !keys.includes(k));
      } else {
        keys.forEach(k => {
          if (!this.selectedKpis.includes(k)) {
            this.selectedKpis.push(k);
          }
        });
      }
    },

    removeKpi(key) {
      const idx = this.selectedKpis.indexOf(key);
      if (idx > -1) {
        this.selectedKpis.splice(idx, 1);
      }
    },

    countKpisActive(keys) {
      if (!Array.isArray(keys)) return 0;
      return keys.filter(k => this.selectedKpis.includes(k)).length;
    },

    getKpiLabel(key) {
      if (singleValueCatalog[key]) return singleValueCatalog[key].label;
      if (rawCatalog[key]) return rawCatalog[key].label;
      return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    async removeKpiDirect(key) {
      this.removeKpi(key);
      await this.applySettings();
    },

    // ── Detailed Methods ──
    isDetailedSelected(key) {
      return this.selectedDetailed.includes(key);
    },

    toggleDetailed(key) {
      if (this.isDetailedSelected(key)) return;
      this.selectedDetailed.push(key);
    },

    toggleDetailedSwitch(key) {
      const idx = this.selectedDetailed.indexOf(key);
      if (idx > -1) {
        this.selectedDetailed.splice(idx, 1);
      } else {
        this.selectedDetailed.push(key);
      }
    },

    getDetailedCategoryState(keys) {
      if (!Array.isArray(keys) || keys.length === 0) return 'off';
      const activeCount = keys.filter(k => this.selectedDetailed.includes(k)).length;
      if (activeCount === 0) return 'off';
      if (activeCount === keys.length) return 'on';
      return 'partial';
    },

    toggleDetailedCategory(keys) {
      if (!Array.isArray(keys) || keys.length === 0) return;
      const state = this.getDetailedCategoryState(keys);
      if (state === 'on') {
        this.selectedDetailed = this.selectedDetailed.filter(k => !keys.includes(k));
      } else {
        keys.forEach(k => {
          if (!this.selectedDetailed.includes(k)) {
            this.selectedDetailed.push(k);
          }
        });
      }
    },

    removeDetailed(key) {
      const idx = this.selectedDetailed.indexOf(key);
      if (idx > -1) {
        this.selectedDetailed.splice(idx, 1);
      }
    },

    countDetailedActive(keys) {
      if (!Array.isArray(keys)) return 0;
      return keys.filter(k => this.selectedDetailed.includes(k)).length;
    },

    getDetailedLabel(key) {
      if (briefInfoCatalog[key]) return briefInfoCatalog[key].label;
      if (rawCatalog[key]) return rawCatalog[key].label;
      return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    async applySettings() {
      if (this.savingSettings) return;
      this.savingSettings = true;
      this.applyAbortController = new AbortController();

      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch('{{ route("dashboard.widgets.apply-settings") }}', {
          method: 'POST',
          signal: this.applyAbortController.signal,
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            kpi_widgets: this.selectedKpis,
            detailed_widgets: this.selectedDetailed
          })
        });

        const data = await resp.json();
        if (data.ok) {
          this.initialKpis     = [...this.selectedKpis];
          this.initialDetailed = [...this.selectedDetailed];
          this.toggleScrollLock(false);
          window.location.reload();
        } else {
          alert(data.error || 'Failed to update dashboard settings.');
          this.savingSettings = false;
          if (this.settingsOpen) this.toggleScrollLock(true);
        }
      } catch (err) {
        if (err.name === 'AbortError') {
          // Operation was cleanly cancelled by user via Cancel button
          return;
        }
        console.error('Apply settings fallback:', err);
        // Fallback: standard form submit
        const formEl = document.createElement('form');
        formEl.method = 'POST';
        formEl.action = '{{ route("dashboard.widgets.apply-settings") }}';
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        formEl.appendChild(csrf);

        this.selectedKpis.forEach(src => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'kpi_widgets[]';
          inp.value = src;
          formEl.appendChild(inp);
        });

        this.selectedDetailed.forEach(src => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'detailed_widgets[]';
          inp.value = src;
          formEl.appendChild(inp);
        });

        document.body.appendChild(formEl);
        formEl.submit();
      }
    },

    openAdd(sourceKey = 'student_count') {
      this.editingId = null;
      const catItem = rawCatalog[sourceKey] || {};
      this.form = {
        name: catItem.label || 'Student Count',
        source: sourceKey,
        icon_color: catItem.default_color || 'from-blue-500 to-indigo-600',
        filter_class_id: '',
        filter_section: '',
        filter_gender: '',
        filter_student_type: '',
        filter_status: 'active',
      };
      this.resetPreview();
      this.panelOpen = true;
    },

    openEdit(id, name, source, iconColor, filters) {
      this.editingId = id;
      filters = filters || {};
      this.form = {
        name: name,
        source: source || 'student_count',
        icon_color: iconColor || 'from-blue-500 to-indigo-600',
        filter_class_id: filters.class_id || '',
        filter_section: filters.section || '',
        filter_gender: filters.gender || '',
        filter_student_type: filters.student_type || '',
        filter_status: filters.status || 'active',
      };
      this.resetPreview();
      this.panelOpen = true;
    },

    onSourceChange() {
      const catItem = rawCatalog[this.form.source];
      if (catItem && catItem.label) {
        this.form.name = catItem.label;
      }
      this.resetPreview();
    },

    resetPreview() {
      this.previewResult = null;
      this.previewUrl = null;
      this.previewDescription = '';
    },

    async previewCount() {
      this.previewing = true;
      this.previewResult = null;
      this.previewUrl = null;
      try {
        const params = new URLSearchParams();
        params.set('source', this.form.source || 'student_count');
        if (this.hasStudentFilters) {
          if (this.form.filter_class_id) params.set('filter_class_id', this.form.filter_class_id);
          if (this.form.filter_section) params.set('filter_section', this.form.filter_section);
          if (this.form.filter_gender) params.set('filter_gender', this.form.filter_gender);
          if (this.form.filter_student_type) params.set('filter_student_type', this.form.filter_student_type);
          params.set('filter_status', this.form.filter_status || 'active');
        }

        const res = await fetch(`/dashboard/widgets/preview?${params.toString()}`);
        const data = await res.json();
        this.previewResult = data.display !== undefined ? data.display : data.count;
        this.previewUrl = data.url;
        this.previewDescription = `Calculated value for "${this.form.name || 'Card'}"`;
      } catch (e) {
        this.previewResult = 'Error';
      } finally {
        this.previewing = false;
      }
    },

    confirmDelete(id, name, isDefault) {
      this.deleteWidgetId = id;
      this.deleteWidgetName = name;
      this.isDefault = !!isDefault;
      this.deleteModal = true;
    },
  };
}
</script>
@endpush

