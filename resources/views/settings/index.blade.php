@extends('layouts.app')
@section('title', 'School Settings & Theme Customization')

@section('content')
<div x-data="settingsPage()" class="space-y-6">

  {{-- Page Header --}}
  <div class="flex items-center justify-between flex-wrap gap-4 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white shadow-xs">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2.5 flex-wrap">
          <h1 class="text-xl font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">School Settings &amp; Appearance</h1>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
            Global Configuration
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Manage school profile, institutional credentials, and centralized ERP theme styling</p>
      </div>
    </div>

    {{-- Reset Theme Quick Action --}}
    <form method="POST" action="{{ route('settings.reset-theme') }}"
          onsubmit="return confirm('Are you sure you want to reset all theme and appearance settings back to defaults?');">
      @csrf
      <button type="submit" class="btn-sm btn-secondary inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-red-600 hover:border-red-200 transition">
        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span>Reset to Default Theme</span>
      </button>
    </form>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert-danger">
      <ul class="list-disc pl-4 space-y-1">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Category Tabs --}}
  <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-1">
    <button type="button" @click="activeTab = 'appearance'"
            class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
            :class="activeTab === 'appearance' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
      <span>Appearance &amp; Theme</span>
    </button>
    <button type="button" @click="activeTab = 'general'"
            class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
            :class="activeTab === 'general' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      <span>General Information</span>
    </button>
    <button type="button" @click="activeTab = 'branding'"
            class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
            :class="activeTab === 'branding' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      <span>Banner, Logos &amp; Seals</span>
    </button>
    <button type="button" @click="activeTab = 'regional'"
            class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
            :class="activeTab === 'regional' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Regional &amp; Academic</span>
    </button>
    <a href="{{ route('settings.books-notes.index') }}"
       class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap text-[#8C2826] bg-[#FFF5F5] hover:bg-[#FEE2E2] border border-[#FECACA]">
      <svg class="w-4 h-4 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
      <span>Book, Note &amp; Uniform Distribution &rarr;</span>
    </a>
  </div>

  {{-- Main Form Container --}}
  <form method="POST" action="{{ route('settings.save') }}" class="space-y-6">
    @csrf

    {{-- Hidden fields to preserve other tabs values when submitting --}}
    <input type="hidden" name="school_name" value="{{ old('school_name', $school?->school_name ?? 'DASA EduERP') }}" :value="formData.school_name">
    <input type="hidden" name="school_code" value="{{ old('school_code', $school?->school_code) }}" :value="formData.school_code">
    <input type="hidden" name="affiliation_no" value="{{ old('affiliation_no', $school?->affiliation_no) }}" :value="formData.affiliation_no">
    <input type="hidden" name="board" value="{{ old('board', $school?->board ?? 'CBSE') }}" :value="formData.board">
    <input type="hidden" name="medium" value="{{ old('medium', $school?->medium ?? 'English') }}" :value="formData.medium">
    <input type="hidden" name="address" value="{{ old('address', $school?->address) }}" :value="formData.address">
    <input type="hidden" name="city" value="{{ old('city', $school?->city) }}" :value="formData.city">
    <input type="hidden" name="state" value="{{ old('state', $school?->state) }}" :value="formData.state">
    <input type="hidden" name="pincode" value="{{ old('pincode', $school?->pincode) }}" :value="formData.pincode">
    <input type="hidden" name="phone" value="{{ old('phone', $school?->phone) }}" :value="formData.phone">
    <input type="hidden" name="email" value="{{ old('email', $school?->email) }}" :value="formData.email">
    <input type="hidden" name="website" value="{{ old('website', $school?->website) }}" :value="formData.website">
    <input type="hidden" name="principal_name" value="{{ old('principal_name', $school?->principal_name) }}" :value="formData.principal_name">
    <input type="hidden" name="gstin" value="{{ old('gstin', $school?->gstin) }}" :value="formData.gstin">
    <input type="hidden" name="pan" value="{{ old('pan', $school?->pan) }}" :value="formData.pan">
    <input type="hidden" name="currency_symbol" value="{{ old('currency_symbol', $school?->currency_symbol ?? '₹') }}" :value="formData.currency_symbol">
    <input type="hidden" name="date_format" value="{{ old('date_format', $school?->date_format ?? 'd/m/Y') }}" :value="formData.date_format">
    <input type="hidden" name="timezone" value="{{ old('timezone', $school?->timezone ?? 'Asia/Kolkata') }}" :value="formData.timezone">

    {{-- Theme Inputs (controlled by Alpine) --}}
    <input type="hidden" name="primary_color" :value="theme.primaryColor">
    <input type="hidden" name="font_size" :value="theme.fontSize">
    <input type="hidden" name="font_family" :value="theme.fontFamily">
    <input type="hidden" name="ui_density" :value="theme.uiDensity">
    <input type="hidden" name="sidebar_preference" :value="theme.sidebarPref">

    {{-- ═══════════════════════════════════════════════════════════
         TAB 1: APPEARANCE & THEME (PRIMARY FOCUS)
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'appearance'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Controls (2 cols) --}}
        <div class="lg:col-span-2 space-y-6">

          {{-- 1. Primary Theme Color --}}
          <div class="card p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 class="font-bold text-slate-800 text-sm" style="font-family:'Plus Jakarta Sans',sans-serif;">Primary Theme Color</h3>
                <p class="text-xs text-slate-400">Controls brand accents, buttons, active menu states, and highlights across the entire ERP</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold px-2 py-1 bg-slate-100 rounded text-slate-700 uppercase" x-text="theme.primaryColor"></span>
                <div class="w-6 h-6 rounded-md border border-slate-300 shadow-2xs" :style="'background:' + theme.primaryColor"></div>
              </div>
            </div>

            {{-- Color Presets --}}
            <div>
              <label class="label text-xs">Theme Color Options</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1.5">
                @foreach($colorPresets as $key => $preset)
                <button type="button"
                        @click="selectPreset('{{ $preset['hex'] }}')"
                        class="p-3.5 rounded-xl border flex items-center gap-3 transition-all cursor-pointer text-left"
                        :class="theme.primaryColor.toUpperCase() === '{{ strtoupper($preset['hex']) }}' ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/25 shadow-xs' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'">
                  <span class="w-6 h-6 rounded-full flex-shrink-0 shadow-2xs border border-black/10" style="background: {{ $preset['hex'] }}"></span>
                  <div class="overflow-hidden flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                      <p class="text-xs font-bold text-slate-800 truncate leading-tight">{{ $preset['label'] }}</p>
                      @if(!empty($preset['is_default']))
                        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 bg-rose-100 text-rose-800 rounded">Default</span>
                      @endif
                    </div>
                    <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $preset['hex'] }}</p>
                  </div>
                </button>
                @endforeach
              </div>
            </div>
          </div>

          {{-- 2. Global Font Size --}}
          <div class="card p-6 space-y-4">
            <div class="pb-3 border-b border-slate-100">
              <h3 class="font-bold text-slate-800 text-sm" style="font-family:'Plus Jakarta Sans',sans-serif;">Global Font Size</h3>
              <p class="text-xs text-slate-400">Scales the overall typography and reading size across all modules</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              @foreach($fontSizeOptions as $fsKey => $fsOpt)
              <label class="rounded-xl border p-3 cursor-pointer flex flex-col justify-between transition-all"
                     :class="theme.fontSize === '{{ $fsKey }}' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-800">{{ $fsOpt['label'] }}</span>
                  <input type="radio" name="font_size_radio" value="{{ $fsKey }}" x-model="theme.fontSize" class="text-indigo-600 focus:ring-indigo-500">
                </div>
                <div class="mt-2">
                  <span class="text-xs text-slate-500 font-mono">{{ $fsOpt['size'] }}</span>
                  <p class="text-[10px] text-slate-400 mt-1 leading-snug">{{ $fsOpt['desc'] }}</p>
                </div>
              </label>
              @endforeach
            </div>
          </div>

          {{-- 3. Typography & Font Family --}}
          <div class="card p-6 space-y-4">
            <div class="pb-3 border-b border-slate-100">
              <h3 class="font-bold text-slate-800 text-sm" style="font-family:'Plus Jakarta Sans',sans-serif;">Font Family</h3>
              <p class="text-xs text-slate-400">Choose the typeface used for navigation, headings, and data tables</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              @foreach($fontFamilyOptions as $ffKey => $ffOpt)
              <label class="rounded-xl border p-3 cursor-pointer flex items-center justify-between transition-all"
                     :class="theme.fontFamily === '{{ $ffKey }}' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'">
                <div>
                  <span class="text-xs font-bold text-slate-800" style="font-family: {{ $ffOpt['css'] }}">{{ $ffOpt['label'] }}</span>
                  <p class="text-[10px] text-slate-400 mt-0.5">Sample: The quick brown fox jumps</p>
                </div>
                <input type="radio" name="font_family_radio" value="{{ $ffKey }}" x-model="theme.fontFamily" class="text-indigo-600 focus:ring-indigo-500">
              </label>
              @endforeach
            </div>
          </div>

          {{-- 4. UI Density & Sidebar Preference --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

            {{-- Density --}}
            <div class="card p-5 space-y-3">
              <div>
                <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Interface Density</h4>
                <p class="text-[11px] text-slate-400 mt-0.5">Adjust padding and vertical compacting for data views</p>
              </div>
              <div class="space-y-2">
                @foreach($densityOptions as $dKey => $dOpt)
                <label class="rounded-xl border p-2.5 cursor-pointer flex items-center justify-between transition-all"
                       :class="theme.uiDensity === '{{ $dKey }}' ? 'border-indigo-600 bg-indigo-50/40' : 'border-slate-200 hover:bg-slate-50'">
                  <div>
                    <span class="text-xs font-bold text-slate-800">{{ $dOpt['label'] }}</span>
                    <p class="text-[10.5px] text-slate-400">{{ $dOpt['desc'] }}</p>
                  </div>
                  <input type="radio" name="density_radio" value="{{ $dKey }}" x-model="theme.uiDensity" class="text-indigo-600">
                </label>
                @endforeach
              </div>
            </div>

            {{-- Sidebar Preference --}}
            <div class="card p-5 space-y-3">
              <div>
                <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Sidebar Preference</h4>
                <p class="text-[11px] text-slate-400 mt-0.5">Initial sidebar behavior upon opening ERP on desktop</p>
              </div>
              <div class="space-y-2">
                @foreach($sidebarOptions as $sKey => $sOpt)
                <label class="rounded-xl border p-2.5 cursor-pointer flex items-center justify-between transition-all"
                       :class="theme.sidebarPref === '{{ $sKey }}' ? 'border-indigo-600 bg-indigo-50/40' : 'border-slate-200 hover:bg-slate-50'">
                  <div>
                    <span class="text-xs font-bold text-slate-800">{{ $sOpt['label'] }}</span>
                    <p class="text-[10.5px] text-slate-400">{{ $sOpt['desc'] }}</p>
                  </div>
                  <input type="radio" name="sidebar_pref_radio" value="{{ $sKey }}" x-model="theme.sidebarPref" class="text-indigo-600">
                </label>
                @endforeach
              </div>
            </div>

          </div>

        </div>

        {{-- Right: Live Interactive Preview Card (1 col) --}}
        <div class="lg:col-span-1 space-y-4">
          <div class="card p-5 sticky top-6 space-y-4 border-2 border-indigo-100 shadow-md">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Live Theme Preview</h3>
              </div>
              <span class="text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">Reactive</span>
            </div>

            {{-- Dynamic Preview Box --}}
            <div class="bg-slate-50/80 border border-slate-200/80 rounded-xl p-4 space-y-4 transition-all"
                 :style="'font-family:' + getPreviewFontFamily() + '; font-size:' + getPreviewFontSize() + ';'">

              {{-- Heading & Subtitle --}}
              <div>
                <p class="font-bold text-slate-800 tracking-tight" style="font-size: 1.25em;">Student Academic Overview</p>
                <p class="text-slate-500 text-xs mt-0.5">Sample live ERP data preview</p>
              </div>

              {{-- Primary Button Preview --}}
              <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Button Preview</label>
                <div class="flex items-center gap-2 flex-wrap">
                  <button type="button"
                          class="font-semibold text-white px-3.5 py-1.5 rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-default text-xs"
                          :style="'background-color: ' + (theme.primaryColor || '#8C2826') + ' !important; color: #ffffff !important;'"
                          style="background-color: {{ $school?->primary_color ?: '#8C2826' }}; color: #ffffff;">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Primary Action</span>
                  </button>
                  <button type="button" class="btn-secondary btn-sm text-xs cursor-default">Cancel</button>
                </div>
              </div>

              {{-- Active Menu Preview --}}
              <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Active Menu Item</label>
                <div class="bg-slate-900 rounded-lg p-1.5">
                  <div class="flex items-center gap-2 px-3 py-2 rounded-md text-white font-medium text-xs"
                       :style="'background:' + hexToRgba(theme.primaryColor, 0.3) + '; border-left: 3px solid ' + theme.primaryColor">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="'color:' + theme.primaryColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard (Active)</span>
                  </div>
                </div>
              </div>

              {{-- Badge & Selected Tag --}}
              <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Badges &amp; Highlights</label>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                        :style="'background:' + hexToRgba(theme.primaryColor, 0.12) + '; color:' + theme.primaryColor">
                    Enrolled (Active)
                  </span>
                  <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600">
                    <span class="w-2 h-2 rounded-full" :style="'background:' + theme.primaryColor"></span>
                    Selected
                  </span>
                </div>
              </div>

              {{-- Sample Input Form --}}
              <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Input Focus State</label>
                <input type="text" value="Sample field entry..." readonly
                       class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 outline-none"
                       :style="'border-color:' + theme.primaryColor + '; box-shadow: 0 0 0 2px ' + hexToRgba(theme.primaryColor, 0.15)">
              </div>

              {{-- Table Row Sample --}}
              <div class="border border-slate-200 rounded-lg overflow-hidden bg-white text-xs">
                <div class="bg-slate-100/80 px-2.5 py-1.5 font-bold text-slate-600 border-b border-slate-200 flex justify-between">
                  <span>Student</span>
                  <span>Status</span>
                </div>
                <div class="px-2.5 py-1.5 flex justify-between items-center text-slate-700">
                  <span class="font-medium">Aarav Sharma (Class X-A)</span>
                  <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                        :style="'background:' + hexToRgba(theme.primaryColor, 0.15) + '; color:' + theme.primaryColor">
                    Present
                  </span>
                </div>
              </div>

            </div>

            <div class="pt-2 text-center">
              <button type="submit"
                      class="w-full py-2.5 px-4 rounded-xl font-bold text-sm text-white shadow-md hover:opacity-95 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer"
                      :style="'background-color: ' + (theme.primaryColor || '#8C2826') + ' !important; border: 1px solid ' + (theme.primaryColor || '#8C2826') + ' !important; color: #ffffff !important;'"
                      style="background-color: {{ $school?->primary_color ?: '#8C2826' }}; border: 1px solid {{ $school?->primary_color ?: '#8C2826' }}; color: #ffffff;">
                <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Save &amp; Apply Globally</span>
              </button>
              <p class="text-[11px] text-slate-400 mt-2">Changes apply across all ERP modules after saving.</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TAB 2: GENERAL INFORMATION
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'general'" class="card p-6 space-y-5">
      <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h2 class="font-bold text-slate-800 text-sm" style="font-family:'Plus Jakarta Sans',sans-serif;">School Profile &amp; Contact Details</h2>
          <p class="text-xs text-slate-400">Institutional registration, address, and contact information</p>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save &amp; Apply Globally</button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="label">School Name <span class="text-red-500">*</span></label>
          <input type="text" x-model="formData.school_name" class="input" required>
        </div>
        <div>
          <label class="label">School Code</label>
          <input type="text" x-model="formData.school_code" class="input">
        </div>
        <div>
          <label class="label">Affiliation No.</label>
          <input type="text" x-model="formData.affiliation_no" class="input">
        </div>
        <div>
          <label class="label">Board</label>
          <select x-model="formData.board" class="select">
            @foreach(['CBSE','ICSE','State Board','IB','NIOS'] as $b)
              <option value="{{ $b }}">{{ $b }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Medium of Instruction</label>
          <input type="text" x-model="formData.medium" class="input">
        </div>
        <div>
          <label class="label">Principal Name</label>
          <input type="text" x-model="formData.principal_name" class="input">
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
          <label class="label">Campus Address</label>
          <textarea x-model="formData.address" class="input h-16 text-sm py-2"></textarea>
        </div>
        <div>
          <label class="label">City</label>
          <input type="text" x-model="formData.city" class="input">
        </div>
        <div>
          <label class="label">State</label>
          <input type="text" x-model="formData.state" class="input">
        </div>
        <div>
          <label class="label">Pincode</label>
          <input type="text" x-model="formData.pincode" class="input" maxlength="10">
        </div>
        <div>
          <label class="label">Official Phone</label>
          <input type="text" x-model="formData.phone" class="input">
        </div>
        <div>
          <label class="label">Official Email</label>
          <input type="email" x-model="formData.email" class="input">
        </div>
        <div>
          <label class="label">Website</label>
          <input type="text" x-model="formData.website" class="input">
        </div>
        <div>
          <label class="label">GSTIN</label>
          <input type="text" x-model="formData.gstin" class="input" maxlength="20">
        </div>
        <div>
          <label class="label">PAN</label>
          <input type="text" x-model="formData.pan" class="input" maxlength="20">
        </div>
      </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TAB 3: BANNER, LOGOS & INSTITUTIONAL SEALS
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'branding'" class="space-y-6">

      {{-- ─────────────────────────────────────────────────────────────
           1. PAGE HEADER: BRAND ASSETS
      ───────────────────────────────────────────────────────────── --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-200/80">
        <div>
          <h2 class="text-lg font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Brand Assets</h2>
          <p class="text-xs text-slate-500 mt-0.5">Manage school logos, institutional seals and official signatures used across the ERP.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> PNG Only
          </span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
            Max 2 MB
          </span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
            8 Asset Slots
          </span>
        </div>
      </div>

      {{-- ─────────────────────────────────────────────────────────────
           2. SCHOOL BRANDING (2 HORIZONTAL SLOTS)
      ───────────────────────────────────────────────────────────── --}}
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">School Branding</h3>
          <span class="text-xs text-slate-400 font-medium">2 Slots</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-stretch">

          {{-- Logo Slot 1: Primary School Logo --}}
          <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs hover:border-emerald-300 transition flex flex-col justify-between h-full space-y-3">
            {{-- Top Header --}}
            <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-1.5">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-white uppercase tracking-wider">Slot 1</span>
                  <h4 class="font-bold text-slate-800 text-xs" style="font-family:'Plus Jakarta Sans',sans-serif;">Primary School Logo</h4>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Main navbar, official report headers &amp; formal stationery</p>
              </div>
              @if($school?->hasLogo(1))
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                </span>
              @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200 shrink-0">
                  Empty
                </span>
              @endif
            </div>

            {{-- Middle Preview & Details --}}
            <div class="flex items-center gap-3 py-1">
              <div class="w-20 h-20 rounded-lg border border-slate-200/80 bg-slate-50 shrink-0 flex items-center justify-center p-2"
                   style="background: repeating-conic-gradient(#f8fafc 0% 25%, #ffffff 0% 50%) 50% / 12px 12px;">
                @if($school?->hasLogo(1))
                  <img src="{{ $school->getLogoUrl(1) }}" alt="Primary School Logo" class="max-h-full max-w-full object-contain drop-shadow-2xs">
                @else
                  <img src="{{ asset('images/school-logo.png') }}" alt="Default School Logo" class="max-h-full max-w-full object-contain opacity-60">
                @endif
              </div>

              <div class="flex-1 min-w-0 flex flex-col justify-center">
                <span class="text-[11px] text-slate-400 font-medium">Asset Name</span>
                @if($school?->hasLogo(1))
                  <span class="text-xs font-mono font-medium text-slate-700 truncate block mt-0.5" title="{{ basename($school->logo) }}">
                    {{ basename($school->logo) }}
                  </span>
                  <span class="text-[10.5px] text-emerald-600 font-medium mt-0.5">Custom PNG Active</span>
                  <div class="mt-1">
                    <button type="submit" form="delete-logo-1-form" class="text-[11px] font-medium text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1 cursor-pointer">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      <span>Remove</span>
                    </button>
                  </div>
                @else
                  <span class="text-xs font-mono font-medium text-slate-500 truncate block mt-0.5">school-logo.png</span>
                  <span class="text-[10.5px] text-slate-400 mt-0.5">System Default Asset</span>
                @endif
              </div>
            </div>

            {{-- Bottom Upload/Replace & Helper --}}
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <div class="flex items-center gap-2">
                <input type="file" name="logo" form="upload-logo-1-form" accept="image/png" onchange="validatePngFile(this, 2)" required
                       class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                <button type="submit" form="upload-logo-1-form"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shrink-0 inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                  <span>{{ $school?->hasLogo(1) ? 'Replace' : 'Upload' }}</span>
                </button>
              </div>

              <div class="flex items-center justify-between text-[10.5px] text-slate-400 pt-1">
                <span>Global Helper:</span>
                <code class="font-mono text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded cursor-pointer select-all" title="Click to copy">SchoolSetting::logoUrl(1)</code>
              </div>
            </div>
          </div>

          {{-- Logo Slot 2: Secondary Logo / Crest --}}
          <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs hover:border-emerald-300 transition flex flex-col justify-between h-full space-y-3">
            {{-- Top Header --}}
            <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-1.5">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-white uppercase tracking-wider">Slot 2</span>
                  <h4 class="font-bold text-slate-800 text-xs" style="font-family:'Plus Jakarta Sans',sans-serif;">Secondary Logo / Crest &amp; Emblem</h4>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Student ID cards, hall tickets, certificates &amp; compact icons</p>
              </div>
              @if($school?->hasLogo(2))
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                </span>
              @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200 shrink-0">
                  Empty
                </span>
              @endif
            </div>

            {{-- Middle Preview & Details --}}
            <div class="flex items-center gap-3 py-1">
              <div class="w-20 h-20 rounded-lg border border-slate-200/80 bg-slate-50 shrink-0 flex items-center justify-center p-2"
                   style="background: repeating-conic-gradient(#f8fafc 0% 25%, #ffffff 0% 50%) 50% / 12px 12px;">
                @if($school?->hasLogo(2))
                  <img src="{{ $school->getLogoUrl(2) }}" alt="Secondary Logo / Crest" class="max-h-full max-w-full object-contain drop-shadow-2xs">
                @else
                  <img src="{{ asset('images/school-crest.png') }}" alt="Default School Crest" class="max-h-full max-w-full object-contain opacity-60">
                @endif
              </div>

              <div class="flex-1 min-w-0 flex flex-col justify-center">
                <span class="text-[11px] text-slate-400 font-medium">Asset Name</span>
                @if($school?->hasLogo(2))
                  <span class="text-xs font-mono font-medium text-slate-700 truncate block mt-0.5" title="{{ basename($school->logo_2) }}">
                    {{ basename($school->logo_2) }}
                  </span>
                  <span class="text-[10.5px] text-emerald-600 font-medium mt-0.5">Custom PNG Active</span>
                  <div class="mt-1">
                    <button type="submit" form="delete-logo-2-form" class="text-[11px] font-medium text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1 cursor-pointer">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      <span>Remove</span>
                    </button>
                  </div>
                @else
                  <span class="text-xs font-mono font-medium text-slate-500 truncate block mt-0.5">school-crest.png</span>
                  <span class="text-[10.5px] text-slate-400 mt-0.5">System Default Asset</span>
                @endif
              </div>
            </div>

            {{-- Bottom Upload/Replace & Helper --}}
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <div class="flex items-center gap-2">
                <input type="file" name="logo" form="upload-logo-2-form" accept="image/png" onchange="validatePngFile(this, 2)" required
                       class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                <button type="submit" form="upload-logo-2-form"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shrink-0 inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                  <span>{{ $school?->hasLogo(2) ? 'Replace' : 'Upload' }}</span>
                </button>
              </div>

              <div class="flex items-center justify-between text-[10.5px] text-slate-400 pt-1">
                <span>Global Helper:</span>
                <code class="font-mono text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded cursor-pointer select-all" title="Click to copy">SchoolSetting::logoUrl(2)</code>
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- ─────────────────────────────────────────────────────────────
           3. OFFICIAL SEALS (6 COMPACT SLOTS IN 3-COLUMN GRID)
      ───────────────────────────────────────────────────────────── --}}
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Official Seals</h3>
            <p class="text-xs text-slate-400">Institutional stamps and departmental seals for fee receipts, certificates, and official marksheets.</p>
          </div>
          <span class="text-xs text-slate-400 font-medium">6 Slots</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 items-stretch">
          @foreach($schoolSealsList as $slot => $seal)
          <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs hover:border-emerald-300 transition flex flex-col justify-between h-full space-y-3">

            {{-- Slot Header --}}
            <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
              <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-white uppercase tracking-wider">
                  Seal #{{ $slot }}
                </span>
                @if($slot === 1)
                  <span class="px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                    Primary
                  </span>
                @endif
              </div>

              @if($seal['has_file'])
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                </span>
              @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200 shrink-0">
                  Empty
                </span>
              @endif
            </div>

            {{-- Customizable Seal Title / Label --}}
            <div class="space-y-1">
              <label class="block text-[11px] font-semibold text-slate-600 truncate">
                Seal Title / Department
              </label>
              <div class="flex items-center gap-1.5">
                <input type="text" name="label" form="save-seal-label-{{ $slot }}-form" value="{{ $seal['label'] }}"
                       class="input input-sm text-xs font-medium text-slate-800 flex-1 h-8 rounded-lg"
                       placeholder="{{ $seal['default_label'] }}" maxlength="100">
                <button type="submit" form="save-seal-label-{{ $slot }}-form"
                        class="h-8 px-2.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition shrink-0 inline-flex items-center gap-1 cursor-pointer"
                        title="Save title for this seal slot">
                  <span>Save</span>
                </button>
              </div>
            </div>

            {{-- Fixed-Height Checkerboard Seal Preview --}}
            <div class="rounded-lg border border-slate-200/80 overflow-hidden flex flex-col items-center justify-center p-2.5 h-24 text-center bg-slate-50/50"
                 style="background: repeating-conic-gradient(#f8fafc 0% 25%, #ffffff 0% 50%) 50% / 12px 12px;">
              @if($seal['has_file'])
                <img src="{{ $seal['url'] }}" alt="Seal {{ $slot }}" class="max-h-14 max-w-[85%] object-contain drop-shadow-2xs">
                <span class="mt-1 text-[10px] font-mono text-slate-600 bg-white/90 px-1.5 py-0.5 rounded border border-slate-200 truncate max-w-[90%] block">
                  {{ basename($seal['file_name']) }}
                </span>
              @else
                <div class="space-y-0.5 text-slate-400">
                  <svg class="w-5 h-5 mx-auto text-slate-300 stroke-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
                  </svg>
                  <p class="text-[11px] font-medium text-slate-400">No seal uploaded</p>
                </div>
              @endif
            </div>

            {{-- File Input & Actions --}}
            <div class="space-y-1.5 pt-1">
              <div class="flex items-center justify-between text-[11px] h-4">
                <span class="text-slate-400 text-[10.5px]">Upload PNG (Max 2MB):</span>
                @if($seal['has_file'])
                  <button type="submit" form="delete-seal-{{ $slot }}-form" class="font-semibold text-rose-600 hover:text-rose-700 hover:underline cursor-pointer">
                    Remove
                  </button>
                @endif
              </div>

              <div class="flex items-center gap-1.5">
                <input type="file" name="seal" form="upload-seal-{{ $slot }}-form" accept="image/png" onchange="validatePngFile(this, 2)" required
                       class="block w-full text-xs text-slate-500 file:mr-1.5 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                <button type="submit" form="upload-seal-{{ $slot }}-form"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shrink-0 shadow-2xs cursor-pointer">
                  <span>{{ $seal['has_file'] ? 'Replace' : 'Upload' }}</span>
                </button>
              </div>
            </div>

            {{-- Code reference badge --}}
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10.5px]">
              <span class="text-slate-400">Fetch Code:</span>
              <code class="font-mono text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded cursor-pointer select-all hover:bg-emerald-100 transition"
                    title="Click to copy">SchoolSetting::sealUrl({{ $slot }})</code>
            </div>

          </div>
          @endforeach
        </div>
      </div>

      {{-- ─────────────────────────────────────────────────────────────
           4. AUTHORIZED SIGNATURE (HORIZONTAL CARD)
      ───────────────────────────────────────────────────────────── --}}
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Authorized Signature</h3>
          <span class="text-xs text-slate-400 font-medium">Principal Signature Slot</span>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs hover:border-emerald-300 transition">
          <div class="flex flex-col md:flex-row items-center justify-between gap-5">
            {{-- Signature Preview on Left --}}
            <div class="w-32 h-20 rounded-lg border border-slate-200/80 bg-slate-50 shrink-0 flex items-center justify-center p-2"
                 style="background: repeating-conic-gradient(#f8fafc 0% 25%, #ffffff 0% 50%) 50% / 12px 12px;">
              @if($school?->principal_signature)
                <img src="{{ Storage::disk('public')->url($school->principal_signature) }}" alt="Principal Signature" class="max-h-full max-w-full object-contain">
              @else
                <div class="text-center text-slate-400 space-y-0.5">
                  <svg class="w-6 h-6 mx-auto stroke-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                  </svg>
                  <span class="text-[10.5px] font-medium text-slate-400 block">Empty</span>
                </div>
              @endif
            </div>

            {{-- Title & Short Description in Center --}}
            <div class="flex-1 min-w-0 text-center md:text-left space-y-1">
              <div class="flex items-center justify-center md:justify-start gap-2">
                <h4 class="font-bold text-slate-800 text-xs sm:text-sm" style="font-family:'Plus Jakarta Sans',sans-serif;">Principal Signature</h4>
                @if($school?->principal_signature)
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                @else
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200 shrink-0">
                    Empty
                  </span>
                @endif
              </div>
              <p class="text-xs text-slate-500">Authorized official signature for automated report generation, transfer certificates, and marksheets.</p>
              <div class="flex items-center justify-center md:justify-start gap-3 text-[11px] text-slate-400">
                <span>PNG or JPG format &bull; Max 1 MB</span>
                @if($school?->principal_signature)
                  <span>&bull;</span>
                  <button type="submit" form="delete-signature-form" class="font-semibold text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1 cursor-pointer">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Remove Signature</span>
                  </button>
                @endif
              </div>
            </div>

            {{-- Upload / Replace on Right --}}
            <div class="w-full md:w-auto md:min-w-[280px] flex items-center gap-2">
              <input type="file" name="signature" form="upload-signature-form" accept="image/png,image/jpeg"
                     class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
              <button type="submit" form="upload-signature-form"
                      class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shrink-0 shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                <span>{{ $school?->principal_signature ? 'Replace' : 'Upload' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TAB 4: REGIONAL & ACADEMIC SETTINGS
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'regional'" class="card p-6 space-y-5">
      <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h2 class="font-bold text-slate-800 text-sm" style="font-family:'Plus Jakarta Sans',sans-serif;">Regional &amp; Academic Formatting</h2>
          <p class="text-xs text-slate-400">Localization options including currency symbol, date display, and timezone</p>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save &amp; Apply Globally</button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="label">Currency Symbol</label>
          <input type="text" x-model="formData.currency_symbol" class="input font-semibold" maxlength="5">
          <p class="text-[11px] text-slate-400 mt-1">e.g. ₹, $, €, £</p>
        </div>
        <div>
          <label class="label">Date Format</label>
          <select x-model="formData.date_format" class="select">
            <option value="d/m/Y">DD/MM/YYYY (e.g. 19/09/2026)</option>
            <option value="d-m-Y">DD-MM-YYYY (e.g. 19-09-2026)</option>
            <option value="Y-m-d">YYYY-MM-DD (e.g. 2026-09-19)</option>
            <option value="M d, Y">Month Day, Year (e.g. Sep 19, 2026)</option>
          </select>
        </div>
        <div>
          <label class="label">System Timezone</label>
          <input type="text" x-model="formData.timezone" class="input font-mono text-xs">
          <p class="text-[11px] text-slate-400 mt-1">Default: Asia/Kolkata</p>
        </div>
      </div>
    </div>

  </form>

  {{-- Auxiliary Forms for Upload/Delete Logos (Slots 1 & 2) --}}
  <form id="upload-logo-1-form" method="POST" action="{{ route('settings.upload-logo') }}" enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="hidden" name="slot" value="1">
  </form>
  <form id="delete-logo-1-form" method="POST" action="{{ route('settings.delete-logo', 1) }}" class="hidden"
        onsubmit="return confirm('Are you sure you want to remove Primary School Logo?');">
    @csrf @method('DELETE')
  </form>

  <form id="upload-logo-2-form" method="POST" action="{{ route('settings.upload-logo') }}" enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="hidden" name="slot" value="2">
  </form>
  <form id="delete-logo-2-form" method="POST" action="{{ route('settings.delete-logo', 2) }}" class="hidden"
        onsubmit="return confirm('Are you sure you want to remove Secondary Logo / Crest?');">
    @csrf @method('DELETE')
  </form>

  {{-- Auxiliary Forms for Upload/Delete/Label Seals (Slots 1 through 6) --}}
  @for($s = 1; $s <= 6; $s++)
    <form id="upload-seal-{{ $s }}-form" method="POST" action="{{ route('settings.upload-seal') }}" enctype="multipart/form-data" class="hidden">
      @csrf
      <input type="hidden" name="slot" value="{{ $s }}">
    </form>
    <form id="delete-seal-{{ $s }}-form" method="POST" action="{{ route('settings.delete-seal', $s) }}" class="hidden"
          onsubmit="return confirm('Are you sure you want to remove Seal #{{ $s }}?');">
      @csrf @method('DELETE')
    </form>
    <form id="save-seal-label-{{ $s }}-form" method="POST" action="{{ route('settings.save-seal-label') }}" class="hidden">
      @csrf
      <input type="hidden" name="slot" value="{{ $s }}">
    </form>
  @endfor

  {{-- Auxiliary Forms for Upload/Delete Signature & Stamp --}}
  <form id="upload-signature-form" method="POST" action="{{ route('settings.upload-signature') }}" enctype="multipart/form-data" class="hidden">
    @csrf
  </form>
  <form id="delete-signature-form" method="POST" action="{{ route('settings.delete-signature') }}" class="hidden"
        onsubmit="return confirm('Remove signature?');">
    @csrf @method('DELETE')
  </form>
  <form id="upload-stamp-form" method="POST" action="{{ route('settings.upload-stamp') }}" enctype="multipart/form-data" class="hidden">
    @csrf
  </form>
  <form id="delete-stamp-form" method="POST" action="{{ route('settings.delete-stamp') }}" class="hidden"
        onsubmit="return confirm('Remove stamp?');">
    @csrf @method('DELETE')
  </form>

</div>

@push('scripts')
<script>
function settingsPage() {
  return {
    activeTab: '{{ request('tab', session('active_tab', 'appearance')) }}',
    theme: {
      primaryColor: '{{ old('primary_color', in_array(strtoupper((string)($school?->primary_color)), ['#8C2826', '#2563EB', '#731E1C']) ? $school->primary_color : '#8C2826') }}',
      fontSize:     '{{ old('font_size', $school?->font_size ?? 'default') }}',
      fontFamily:   '{{ old('font_family', $school?->font_family ?? 'default') }}',
      uiDensity:    '{{ old('ui_density', $school?->ui_density ?? 'comfortable') }}',
      sidebarPref:  '{{ old('sidebar_preference', $school?->sidebar_preference ?? 'expanded') }}',
    },
    formData: {
      school_name:     '{{ addslashes(old('school_name', $school?->school_name ?? 'DASA EduERP')) }}',
      school_code:     '{{ addslashes(old('school_code', $school?->school_code ?? '')) }}',
      affiliation_no:  '{{ addslashes(old('affiliation_no', $school?->affiliation_no ?? '')) }}',
      board:           '{{ addslashes(old('board', $school?->board ?? 'CBSE')) }}',
      medium:          '{{ addslashes(old('medium', $school?->medium ?? 'English')) }}',
      principal_name:  '{{ addslashes(old('principal_name', $school?->principal_name ?? '')) }}',
      address:         '{{ addslashes(old('address', $school?->address ?? '')) }}',
      city:            '{{ addslashes(old('city', $school?->city ?? '')) }}',
      state:           '{{ addslashes(old('state', $school?->state ?? '')) }}',
      pincode:         '{{ addslashes(old('pincode', $school?->pincode ?? '')) }}',
      phone:           '{{ addslashes(old('phone', $school?->phone ?? '')) }}',
      email:           '{{ addslashes(old('email', $school?->email ?? '')) }}',
      website:         '{{ addslashes(old('website', $school?->website ?? '')) }}',
      gstin:           '{{ addslashes(old('gstin', $school?->gstin ?? '')) }}',
      pan:             '{{ addslashes(old('pan', $school?->pan ?? '')) }}',
      currency_symbol: '{{ addslashes(old('currency_symbol', $school?->currency_symbol ?? '₹')) }}',
      date_format:     '{{ addslashes(old('date_format', $school?->date_format ?? 'd/m/Y')) }}',
      timezone:        '{{ addslashes(old('timezone', $school?->timezone ?? 'Asia/Kolkata')) }}',
    },

    selectPreset(hex) {
      this.theme.primaryColor = hex;
    },

    getPreviewFontSize() {
      const map = {
        'small':   '12px',
        'default': '13.5px',
        'large':   '15px',
        'xlarge':  '16.5px'
      };
      return map[this.theme.fontSize] || '13.5px';
    },

    getPreviewFontFamily() {
      const map = {
        'default':      "'Inter', sans-serif",
        'poppins':      "'Poppins', sans-serif",
        'plus-jakarta': "'Plus Jakarta Sans', sans-serif",
        'system':       "system-ui, -apple-system, sans-serif"
      };
      return map[this.theme.fontFamily] || "'Inter', sans-serif";
    },

    hexToRgba(hex, alpha = 1) {
      if (!hex || !hex.startsWith('#') || hex.length < 7) {
        return `rgba(79, 70, 229, ${alpha})`;
      }
      const r = parseInt(hex.substring(1, 3), 16) || 0;
      const g = parseInt(hex.substring(3, 5), 16) || 0;
      const b = parseInt(hex.substring(5, 7), 16) || 0;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
  };
}

function validatePngFile(input, maxMb = 2) {
  if (!input.files || !input.files[0]) return true;
  const file = input.files[0];
  const isPng = file.type === 'image/png' || file.name.toLowerCase().endsWith('.png');
  if (!isPng) {
    alert('Strict Policy: Only PNG format (.png) is permitted for school logos and institutional seals. Please select a valid PNG image file.');
    input.value = '';
    return false;
  }
  if (file.size > maxMb * 1024 * 1024) {
    alert(`File is too large! Maximum allowed file size is ${maxMb}MB. Selected file is ${(file.size / (1024 * 1024)).toFixed(2)}MB.`);
    input.value = '';
    return false;
  }
  return true;
}
</script>
@endpush
@endsection
