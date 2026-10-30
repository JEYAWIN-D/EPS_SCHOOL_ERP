@extends('layouts.app')

@section('title', 'Official Fees Structure — Erode Public School (' . ($academicYear?->name ?? '2026–2027') . ')')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-16" x-data="{
  feeViewMode: 'card',
  classList: {{ json_encode($classes->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
  standardFees: {{ json_encode($standardFees) }},
  selectedIndex: 0,
  get currentClass() {
    return this.classList[this.selectedIndex] || { id: '', name: 'Pre-KG' };
  },
  get currentFee() {
    return this.standardFees[this.currentClass.id] || {
      term1_fee: 21000, term2_fee: 10000, term3_fee: 10000, material_fee: 0,
      admission_fee: 2500, total_basic: 41000, tier: 'Pre-KG'
    };
  },
  prevClass() {
    if (this.selectedIndex > 0) this.selectedIndex--;
  },
  nextClass() {
    if (this.selectedIndex < this.classList.length - 1) this.selectedIndex++;
  },
  formatMoney(num) {
    return '₹' + Number(num || 0).toLocaleString('en-IN');
  }
}">

  {{-- ── 1. Clean & Airy Page Header ────────────────────────────────────────── --}}
  <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs relative overflow-hidden print:hidden">
    {{-- Subtle decorative ambient glows --}}
    <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-rose-50/60 blur-3xl pointer-events-none"></div>
    <div class="absolute right-1/3 -bottom-12 w-48 h-48 rounded-full bg-amber-50/40 blur-2xl pointer-events-none"></div>

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
      <div class="flex items-start sm:items-center gap-4">
        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white p-2 shadow-xs flex items-center justify-center shrink-0 border border-slate-200/90 ring-4 ring-slate-50">
          <img src="{{ asset('images/school-seal-badge.png') }}" alt="Erode Public School" class="w-full h-full object-contain">
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-rose-50 text-[#8C2826] border border-rose-200/80">
              <span class="w-1.5 h-1.5 rounded-full bg-[#8C2826]"></span>
              Erode Public School
            </span>
            <span class="text-xs font-semibold text-slate-500">&bull; Academic Year {{ $academicYear?->name ?? '2026–2027' }}</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Official Schedule
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Official Fees Structure &amp; Installments
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 font-medium">
            Authorized 3-term academic fee schedule and curriculum fee components for Academic Year {{ $academicYear?->name ?? '2026–2027' }}.
          </p>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap shrink-0">
        <a href="{{ route('admissions.index') }}"
           class="px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
          <svg class="w-3.5 h-3.5 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          <span>Back to Admissions</span>
        </a>

        {{-- Official Forms Menu --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
          <button type="button" @click="open = !open"
                  class="px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-2xs">
            <svg class="w-4 h-4 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Official Forms</span>
            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>

          <div x-show="open" x-cloak
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="transform opacity-0 scale-95"
               x-transition:enter-end="transform opacity-100 scale-100"
               class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 py-2 z-50 divide-y divide-slate-100">
            <div class="px-3.5 py-2">
              <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Printable PDF Forms</p>
            </div>
            <div class="py-1">
              <a href="{{ route('admissions.print-form', ['form' => 'admission']) }}" target="_blank"
                 class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-rose-50 hover:text-[#8C2826] transition">
                <span class="w-7 h-7 rounded-lg bg-rose-50 text-[#8C2826] flex items-center justify-center shrink-0 border border-rose-100 font-bold text-[10px]">P1</span>
                <div>
                  <p class="font-bold text-slate-800">General Admission Form</p>
                  <p class="text-[10px] text-slate-400">Pre-KG to Class XII application</p>
                </div>
              </a>
              <a href="{{ route('admissions.print-form', ['form' => 'grade11']) }}" target="_blank"
                 class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-rose-50 hover:text-[#8C2826] transition">
                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-800 flex items-center justify-center shrink-0 border border-amber-100 font-bold text-[10px]">P2</span>
                <div>
                  <p class="font-bold text-slate-800">Grade XI Stream Form</p>
                  <p class="text-[10px] text-slate-400">Integrated / CBSE stream choice</p>
                </div>
              </a>
            </div>
          </div>
        </div>

        {{-- Primary Action: New Admission --}}
        <a href="{{ route('admissions.create') }}"
           class="px-4 py-2.5 rounded-xl bg-[#8C2826] hover:bg-[#731E1C] text-white font-black text-xs sm:text-sm flex items-center gap-2 shadow-sm transition hover:shadow-md cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
          </svg>
          <span>New Admission</span>
        </a>
      </div>
    </div>
  </div>

  {{-- ── 2. Modern Interactive Standard / Grade Rail ────────────── --}}
  <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-4 print:hidden">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <div class="flex items-center gap-2.5">
        <span class="w-7 h-7 rounded-lg bg-rose-50 text-[#8C2826] flex items-center justify-center font-bold text-xs border border-rose-200/60">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </span>
        <div>
          <span class="text-xs font-black text-slate-900 uppercase tracking-wider block">Select Standard / Grade</span>
          <span class="text-[11px] text-slate-500 font-medium">Click any class chip to inspect 3-term installments and print schedule</span>
        </div>
      </div>

      {{-- Standard counter and Quick Arrows --}}
      <div class="flex items-center gap-3">
        <span class="text-xs font-semibold text-slate-500">
          Standard <span class="font-extrabold text-slate-900" x-text="selectedIndex + 1"></span> of <span class="font-bold text-slate-700" x-text="classList.length"></span>
        </span>
        <div class="flex items-center gap-1">
          <button type="button" @click="prevClass()" :disabled="selectedIndex === 0"
                  class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed text-slate-700 flex items-center justify-center transition cursor-pointer" title="Previous Class">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <button type="button" @click="nextClass()" :disabled="selectedIndex === classList.length - 1"
                  class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed text-slate-700 flex items-center justify-center transition cursor-pointer" title="Next Class">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
    </div>

    {{-- Modern Interactive Class Cards Track --}}
    <div class="flex items-center gap-2.5 overflow-x-auto pb-2 pt-1 scrollbar-thin snap-x">
      <template x-for="(cls, idx) in classList" :key="cls.id">
        <button type="button" @click="selectedIndex = idx"
                class="px-4 py-2.5 rounded-2xl text-left whitespace-nowrap transition-all duration-150 cursor-pointer snap-start flex flex-col justify-between shrink-0 border"
                :class="selectedIndex === idx
                  ? 'bg-[#8C2826] text-white border-[#731E1C] shadow-sm ring-2 ring-[#8C2826]/30 font-extrabold'
                  : 'bg-white hover:bg-rose-50/50 text-slate-700 border-slate-200 hover:border-rose-200 font-bold'">
          <div class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full" :class="selectedIndex === idx ? 'bg-amber-300' : 'bg-slate-300'"></span>
            <span class="text-xs" x-text="cls.name"></span>
          </div>
          <span class="text-[10px] font-mono mt-1"
                :class="selectedIndex === idx ? 'text-rose-100 font-bold' : 'text-slate-400 font-medium'"
                x-text="standardFees[cls.id] ? formatMoney(standardFees[cls.id].total_basic) : '—'">
          </span>
        </button>
      </template>
    </div>
  </div>

  {{-- ── 3. Selected Class Executive Overview Card ───────────── --}}
  <div class="bg-gradient-to-br from-white via-rose-50/30 to-slate-50/80 rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm relative overflow-hidden print:hidden">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
      {{-- Left Info Block --}}
      <div class="space-y-3 max-w-xl">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-100 text-[#8C2826] border border-rose-200"
                x-text="currentFee.tier ? currentFee.tier + ' Fee Schedule' : 'Official Schedule'">
          </span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>3-Term Installments</span>
          </span>
        </div>

        <div>
          <div class="flex items-baseline gap-3">
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight" x-text="currentClass.name"></h2>
            <span class="text-xs text-slate-400 font-semibold" x-text="'(Class ID: ' + currentClass.id + ')'"></span>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 font-medium mt-1">
            Official academic fee schedule for Academic Year 2026–2027. Approved by Management &amp; Accounts.
          </p>
        </div>

        {{-- Quick Actions --}}
        <div class="flex items-center gap-2.5 pt-1">
          <a :href="'/admissions/fee-structure/' + currentClass.id + '/print?print=true'" target="_blank"
             class="px-4 py-2.5 rounded-xl bg-[#8C2826] hover:bg-[#731E1C] text-white font-bold text-xs flex items-center gap-2 shadow-xs transition hover:shadow-md cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Fee Structure</span>
          </a>

          <a :href="'{{ route('admissions.create') }}?class_id=' + currentClass.id"
             class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition shadow-2xs">
            <svg class="w-3.5 h-3.5 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Admit to this Class</span>
          </a>
        </div>
      </div>

      {{-- Right Financial KPI Box --}}
      <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-6 sm:p-7 shadow-md border border-slate-700/80 min-w-[280px] sm:min-w-[320px] flex flex-col justify-between">
        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-widest text-amber-300">Total Annual Academic Fee</span>
            <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase bg-white/10 text-slate-300">AY 2026–27</span>
          </div>
          <p class="text-3xl sm:text-4xl font-black text-white font-mono tracking-tight mt-1" x-text="formatMoney(currentFee.total_basic)"></p>
          <p class="text-xs text-slate-300 font-medium">per annum &bull; across 3 scheduled terms</p>
        </div>

        <div class="pt-4 mt-4 border-t border-white/10 flex items-center justify-between text-xs text-slate-300">
          <span x-text="currentFee.material_fee > 0 ? 'Includes Material Fee' : '+ ₹2,500 One-time Adm. Fee'"></span>
          <span class="text-amber-300 font-bold font-mono" x-text="currentFee.material_fee > 0 ? formatMoney(currentFee.material_fee) : '₹2,500'"></span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── 4. Detailed 3-Term Installment Breakdown ─────────────── --}}
  <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-6 print:hidden">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-rose-50 text-[#8C2826] border border-rose-200/80 flex items-center justify-center font-bold shadow-2xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <h3 class="font-black text-slate-900 text-base">3-Term Academic Installment Breakdown</h3>
          <p class="text-xs text-slate-500 font-medium">Class: <span class="font-extrabold text-[#8C2826]" x-text="currentClass.name"></span> &bull; Scheduled deadlines &amp; fee components</p>
        </div>
      </div>

      {{-- View Mode Toggle --}}
      <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-bold">
        <button type="button" @click="feeViewMode = 'card'"
                :class="feeViewMode === 'card' ? 'bg-white text-[#8C2826] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          <span>Cards View</span>
        </button>
        <button type="button" @click="feeViewMode = 'table'"
                :class="feeViewMode === 'table' ? 'bg-white text-[#8C2826] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>All 16 Standards Table</span>
        </button>
      </div>
    </div>

    {{-- Card View Grid --}}
    <div x-show="feeViewMode === 'card'" class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Term 1 --}}
        <div class="bg-gradient-to-b from-slate-50 to-white p-5 rounded-2xl border border-slate-200/90 hover:border-rose-300 hover:shadow-xs transition space-y-3">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-rose-50 text-[#8C2826] border border-rose-200">Term I</span>
            <span class="text-[11px] font-bold text-slate-500 font-mono">April</span>
          </div>
          <div>
            <p class="text-2xl sm:text-3xl font-black text-slate-900 font-mono" x-text="formatMoney(currentFee.term1_fee)"></p>
            <p class="text-[11px] font-bold text-[#8C2826] mt-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span>Due: 01.04.2026</span>
            </p>
          </div>
          <p class="text-[11px] text-slate-500 pt-2 border-t border-slate-100">Session admission, books kit &amp; Q1 academics.</p>
        </div>

        {{-- Term 2 --}}
        <div class="bg-gradient-to-b from-slate-50 to-white p-5 rounded-2xl border border-slate-200/90 hover:border-rose-300 hover:shadow-xs transition space-y-3">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-rose-50 text-[#8C2826] border border-rose-200">Term II</span>
            <span class="text-[11px] font-bold text-slate-500 font-mono">August</span>
          </div>
          <div>
            <p class="text-2xl sm:text-3xl font-black text-slate-900 font-mono" x-text="formatMoney(currentFee.term2_fee)"></p>
            <p class="text-[11px] font-bold text-[#8C2826] mt-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span>Due: 05.08.2026</span>
            </p>
          </div>
          <p class="text-[11px] text-slate-500 pt-2 border-t border-slate-100">Mid-term tuition, laboratory &amp; assessments.</p>
        </div>

        {{-- Term 3 --}}
        <div class="bg-gradient-to-b from-slate-50 to-white p-5 rounded-2xl border border-slate-200/90 hover:border-rose-300 hover:shadow-xs transition space-y-3">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-rose-50 text-[#8C2826] border border-rose-200">Term III</span>
            <span class="text-[11px] font-bold text-slate-500 font-mono">December</span>
          </div>
          <div>
            <p class="text-2xl sm:text-3xl font-black text-slate-900 font-mono" x-text="formatMoney(currentFee.term3_fee)"></p>
            <p class="text-[11px] font-bold text-[#8C2826] mt-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span>Due: 05.12.2026</span>
            </p>
          </div>
          <p class="text-[11px] text-slate-500 pt-2 border-t border-slate-100">Final term academics, examination &amp; evaluation.</p>
        </div>

        {{-- Special/Material/Admission Fee --}}
        <div class="bg-gradient-to-b from-slate-50 to-white p-5 rounded-2xl border border-slate-200/90 hover:border-rose-300 hover:shadow-xs transition space-y-3">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase"
                  :class="currentFee.material_fee > 0 ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                  x-text="currentFee.material_fee > 0 ? 'Material Fee' : 'New Adm. Fee'">
            </span>
            <span class="text-[11px] font-bold text-slate-400 font-mono" x-text="currentFee.material_fee > 0 ? 'Grade X' : 'One-time'"></span>
          </div>
          <div>
            <p class="text-2xl sm:text-3xl font-black text-slate-900 font-mono"
               x-text="currentFee.material_fee > 0 ? formatMoney(currentFee.material_fee) : formatMoney(currentFee.admission_fee)"></p>
            <p class="text-[11px] font-bold text-slate-500 mt-1"
               x-text="currentFee.material_fee > 0 ? 'Due: 10.01.2026' : '₹2,500 extra for new students'"></p>
          </div>
          <p class="text-[11px] text-slate-500 pt-2 border-t border-slate-100"
             x-text="currentFee.material_fee > 0 ? 'Mandatory Grade X board study material fee.' : 'One-time institutional processing fee.'"></p>
        </div>
      </div>

      {{-- Integrated Coaching Card (Grade XI & XII) --}}
      <div x-show="currentFee.has_integrated"
           class="p-6 rounded-3xl bg-gradient-to-r from-amber-50/70 via-white to-rose-50/70 border border-amber-200/90 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center font-bold text-sm border border-amber-200">
              <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </span>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">Grade XI / XII Integrated NEET &amp; JEE Coaching</h4>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#8C2826] text-white">Integrated Stream</span>
              </div>
              <p class="text-xs text-slate-500 font-medium">Includes CBSE syllabus + intensive daily competitive medical &amp; engineering foundation coaching.</p>
            </div>
          </div>
          <div class="text-right">
            <span class="text-xs text-slate-400 block font-bold uppercase">Total Integrated Fee</span>
            <span class="text-xl sm:text-2xl font-black font-mono text-[#8C2826]" x-text="formatMoney(currentFee.integrated_fee || 105000)"></span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2 border-t border-amber-200/50">
          <div class="bg-white p-3.5 rounded-xl border border-amber-200/60 flex items-center justify-between">
            <span class="text-slate-500 font-bold uppercase text-[10px]">Term I (April)</span>
            <span class="font-mono font-black text-slate-900" x-text="formatMoney(currentFee.integrated_term1 || 52500)"></span>
          </div>
          <div class="bg-white p-3.5 rounded-xl border border-amber-200/60 flex items-center justify-between">
            <span class="text-slate-500 font-bold uppercase text-[10px]">Term II (August)</span>
            <span class="font-mono font-black text-slate-900" x-text="formatMoney(currentFee.integrated_term2 || 32500)"></span>
          </div>
          <div class="bg-white p-3.5 rounded-xl border border-amber-200/60 flex items-center justify-between">
            <span class="text-slate-500 font-bold uppercase text-[10px]">Term III (December)</span>
            <span class="font-mono font-black text-slate-900" x-text="formatMoney(currentFee.integrated_term3 || 20000)"></span>
          </div>
        </div>
      </div>

      {{-- Verified Total Summary Bar --}}
      <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-2xs">
            ✓
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">Total Standard Annual Academic Fee (2026–2027)</h4>
            <p class="text-xs text-slate-500 font-medium">Consolidated sum of April (Term I) + August (Term II) + December (Term III)</p>
          </div>
        </div>

        <div class="text-right">
          <p class="text-2xl sm:text-3xl font-black text-[#8C2826] font-mono" x-text="formatMoney(currentFee.total_basic)"></p>
        </div>
      </div>
    </div>

    {{-- Table View (All 16 Standards Comparison Matrix) --}}
    <div x-show="feeViewMode === 'table'" class="overflow-x-auto rounded-2xl border border-slate-200/90 shadow-2xs">
      <table class="w-full text-xs text-left">
        <thead class="bg-slate-50 text-slate-700 font-extrabold uppercase tracking-wider text-[10px] border-b border-slate-200">
          <tr>
            <th class="py-3.5 px-4">Standard / Grade</th>
            <th class="py-3.5 px-4 text-right">April (Term I)</th>
            <th class="py-3.5 px-4 text-right">August (Term II)</th>
            <th class="py-3.5 px-4 text-right">December (Term III)</th>
            <th class="py-3.5 px-4 text-right">Material / Adm</th>
            <th class="py-3.5 px-4 text-right font-black">Total Annual Fee</th>
            <th class="py-3.5 px-4 text-center">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          <template x-for="(cls, idx) in classList" :key="cls.id">
            <tr class="hover:bg-rose-50/40 transition cursor-pointer"
                :class="selectedIndex === idx ? 'bg-rose-50/70 font-bold' : ''"
                @click="selectedIndex = idx; feeViewMode = 'card'">
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full" :class="selectedIndex === idx ? 'bg-[#8C2826]' : 'bg-transparent'"></span>
                  <span class="font-bold text-slate-900" x-text="cls.name"></span>
                  <span class="text-[10px] text-slate-400 font-normal" x-text="standardFees[cls.id]?.tier ? '(' + standardFees[cls.id].tier + ')' : ''"></span>
                </div>
              </td>
              <td class="py-3.5 px-4 font-mono text-slate-700 text-right" x-text="formatMoney(standardFees[cls.id]?.term1_fee)"></td>
              <td class="py-3.5 px-4 font-mono text-slate-700 text-right" x-text="formatMoney(standardFees[cls.id]?.term2_fee)"></td>
              <td class="py-3.5 px-4 font-mono text-slate-700 text-right" x-text="formatMoney(standardFees[cls.id]?.term3_fee)"></td>
              <td class="py-3.5 px-4 font-mono text-slate-500 text-right" x-text="standardFees[cls.id]?.material_fee > 0 ? formatMoney(standardFees[cls.id]?.material_fee) + ' (Material)' : '₹2,500 (New Adm)'"></td>
              <td class="py-3.5 px-4 font-mono text-right font-black text-[#8C2826] text-sm" x-text="formatMoney(standardFees[cls.id]?.total_basic)"></td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-3 py-1 rounded-xl text-xs font-bold transition inline-block"
                      :class="selectedIndex === idx ? 'bg-[#8C2826] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                  Select &rarr;
                </span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── 5. Due Dates & Policy Guidelines ─────────────────────── --}}
  <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-4 print:hidden">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-xl bg-rose-50 text-[#8C2826] border border-rose-200 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </span>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Official Fee Due Dates &amp; Compliance Guidelines</h3>
          <p class="text-xs text-slate-500">Scheduled installment deadlines and statutory school policies</p>
        </div>
      </div>
      <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold self-start sm:self-auto">
        3-Term / Annual Schedule
      </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/70 space-y-1">
        <div class="text-[11px] font-extrabold uppercase tracking-wider text-[#8C2826]">Term I Installment</div>
        <div class="text-lg font-black text-slate-900">01.04.2026</div>
        <div class="text-[11px] text-slate-500">Commencement of the academic year. Includes annual admission kit &amp; Q1 tuition.</div>
      </div>

      <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/70 space-y-1">
        <div class="text-[11px] font-extrabold uppercase tracking-wider text-[#8C2826]">Term II Installment</div>
        <div class="text-lg font-black text-slate-900">05.08.2026</div>
        <div class="text-[11px] text-slate-500">Mid-term tuition, laboratory, and periodic test assessments.</div>
      </div>

      <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/70 space-y-1">
        <div class="text-[11px] font-extrabold uppercase tracking-wider text-[#8C2826]">Term III Installment</div>
        <div class="text-lg font-black text-slate-900">05.12.2026</div>
        <div class="text-[11px] text-slate-500">Final term tuition, annual exams, and promotion verification.</div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 text-xs">
      <div class="flex items-start gap-2.5 bg-amber-50/70 p-3.5 rounded-xl border border-amber-200/80">
        <svg class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <span class="font-bold text-amber-950">One-Time Admission Fee:</span>
          <span class="text-slate-700"> ₹2,500 extra one-time registration fee for newly enrolled students (Pre-KG to Class IX &amp; XI).</span>
        </div>
      </div>

      <div class="flex items-start gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200/70">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <span class="font-bold text-slate-800">Payment Policy:</span>
          <span class="text-slate-600"> Payable strictly on a 3-term or annual basis via NetBanking, UPI, Demand Draft or Front Office Cash.</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── FORMAL PRINT FORMAT FOR FEES STRUCTURE (ERODE PUBLIC SCHOOL) ── --}}
  <div class="hidden print:block print-fee-document space-y-6 bg-white text-slate-900 font-sans p-4">
    <div class="flex items-center justify-between border-b-2 border-[#8C2826] pb-4">
      <div class="flex items-center gap-3">
        <img src="{{ asset('images/school-seal-badge.png') }}" class="w-16 h-16 object-contain">
        <div>
          <h1 class="text-2xl font-black text-[#8C2826] tracking-wider uppercase">ERODE PUBLIC SCHOOL</h1>
          <p class="text-xs font-bold text-slate-600 uppercase tracking-widest mt-0.5">Affiliated to CBSE, New Delhi &bull; Official Fee Structure</p>
          <p class="text-xs text-slate-500 font-medium">Academic Year: {{ $academicYear?->name ?? '2026–2027' }}</p>
        </div>
      </div>
      <div class="text-right">
        <div class="inline-block px-4 py-1.5 bg-rose-50 text-[#8C2826] font-black text-base rounded-xl border border-rose-200 uppercase">
          Standard: <span x-text="currentClass.name"></span>
        </div>
        <p class="text-xs text-slate-500 font-mono mt-1">Date: {{ date('d-m-Y') }}</p>
      </div>
    </div>

    <div class="space-y-3">
      <h2 class="text-sm font-extrabold text-[#8C2826] uppercase tracking-wider border-b border-slate-300 pb-1.5">Academic Fee Schedule Breakdown</h2>
      <table class="w-full text-xs text-left border-collapse border border-slate-300">
        <thead>
          <tr class="bg-rose-50/80 text-slate-900 border-b border-slate-300 font-bold uppercase text-[10px]">
            <th class="py-2.5 px-4 border border-slate-300">Term / Component</th>
            <th class="py-2.5 px-4 border border-slate-300">Due Date / Details</th>
            <th class="py-2.5 px-4 text-right border border-slate-300">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 font-medium">
          <tr>
            <td class="py-2.5 px-4 border border-slate-300 font-bold text-slate-900">April (Term I)</td>
            <td class="py-2.5 px-4 border border-slate-300 text-slate-600">Due Date: 01.04.2026</td>
            <td class="py-2.5 px-4 border border-slate-300 text-right font-mono font-bold text-slate-900" x-text="formatMoney(currentFee.term1_fee)"></td>
          </tr>
          <tr>
            <td class="py-2.5 px-4 border border-slate-300 font-bold text-slate-900">August (Term II)</td>
            <td class="py-2.5 px-4 border border-slate-300 text-slate-600">Due Date: 05.08.2026</td>
            <td class="py-2.5 px-4 border border-slate-300 text-right font-mono font-bold text-slate-900" x-text="formatMoney(currentFee.term2_fee)"></td>
          </tr>
          <tr>
            <td class="py-2.5 px-4 border border-slate-300 font-bold text-slate-900">December (Term III)</td>
            <td class="py-2.5 px-4 border border-slate-300 text-slate-600">Due Date: 05.12.2026</td>
            <td class="py-2.5 px-4 border border-slate-300 text-right font-mono font-bold text-slate-900" x-text="formatMoney(currentFee.term3_fee)"></td>
          </tr>
          <tr x-show="currentFee.material_fee > 0">
            <td class="py-2.5 px-4 border border-slate-300 font-bold text-slate-900">Material Fee (Grade X)</td>
            <td class="py-2.5 px-4 border border-slate-300 text-slate-600">Due Date: 10.01.2026</td>
            <td class="py-2.5 px-4 border border-slate-300 text-right font-mono font-bold text-slate-900" x-text="formatMoney(currentFee.material_fee)"></td>
          </tr>
          <tr class="bg-rose-50/80 font-bold">
            <td colspan="2" class="py-3 px-4 border border-slate-300 text-[#8C2826] uppercase text-xs">Total Annual Academic Fee</td>
            <td class="py-3 px-4 border border-slate-300 text-right font-mono text-sm text-[#8C2826]" x-text="formatMoney(currentFee.total_basic)"></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="grid grid-cols-2 gap-12 pt-16 text-xs">
      <div>
        <p class="font-bold text-slate-800">Accounts &amp; Admissions Officer</p>
        <div class="h-12 border-b border-slate-400 border-dashed"></div>
        <p class="text-[10px] text-slate-500 mt-1">Erode Public School</p>
      </div>
      <div class="text-right">
        <p class="font-bold text-slate-800">Authorized Signatory</p>
        <div class="h-12 border-b border-slate-400 border-dashed"></div>
        <p class="text-[10px] text-slate-500 mt-1">School Seal / Principal Signature</p>
      </div>
    </div>
  </div>

</div>

{{-- Print Styling --}}
<style>
  @media print {
    @page {
      size: A4 portrait;
      margin: 12mm 15mm;
    }
    body * {
      visibility: hidden;
    }
    .print-fee-document, .print-fee-document * {
      visibility: visible;
    }
    .print-fee-document {
      position: absolute;
      left: 0;
      top: 0;
      width: 100% !important;
      max-width: 100% !important;
    }
    .print\:hidden {
      display: none !important;
    }
  }
</style>
@endsection
