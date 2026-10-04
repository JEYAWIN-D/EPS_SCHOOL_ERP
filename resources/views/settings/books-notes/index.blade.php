@extends('layouts.app')
@section('title', 'Book, Note & Uniform Distribution — Settings')

@section('content')
<div x-data="booksNotesPage()" x-init="init()" class="space-y-6">

  {{-- ── Page Title & Action Bar ───────────────────────────────────────────── --}}
  <div class="flex items-center justify-between flex-wrap gap-4 bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs">
    <div class="flex items-center gap-3.5 sm:gap-4">
      <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-[#8C2826] to-[#681917] flex items-center justify-center text-white shadow-md shadow-[#8C2826]/20 shrink-0">
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2.5 flex-wrap">
          <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">Book, Note &amp; Uniform Distribution</h1>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA]">
            <span class="w-1.5 h-1.5 rounded-full bg-[#8C2826]"></span>
            Session 2026–27
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5 font-medium">Standard-wise prescribed textbooks, notebooks, school uniforms, SKUs, and Senior Secondary stream group checklists</p>
      </div>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a href="{{ route('settings.index') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-bold text-xs inline-flex items-center gap-1.5 transition cursor-pointer shadow-2xs">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Settings</span>
      </a>
      <button type="button" @click="openCreateModal()" class="px-4 py-2 rounded-xl bg-[#8C2826] hover:bg-[#731E1C] text-white font-extrabold text-xs flex items-center gap-1.5 shadow-sm shadow-[#8C2826]/20 transition cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        <span>Add Book, Note &amp; Uniform</span>
      </button>
    </div>
  </div>

  {{-- ── Main Navigation Tabs (Master Checklists vs Distribution Tracker vs Fee & Issuance Desk) ───── --}}
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div class="bg-slate-100/90 p-1 rounded-2xl inline-flex items-center gap-1 border border-slate-200/80 shadow-2xs">
      {{-- Tab 1: Standard Master Checklists --}}
      <button type="button" @click="switchMainTab('checklists')"
              :class="activeMainTab === 'checklists' ? 'bg-white text-[#8C2826] shadow-xs font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold border border-transparent hover:bg-white/50'"
              class="px-4 py-2.5 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all cursor-pointer">
        <svg class="w-4 h-4" :class="activeMainTab === 'checklists' ? 'text-[#8C2826]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <span>Standard Master Checklists</span>
        <span :class="activeMainTab === 'checklists' ? 'bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA]' : 'bg-slate-200/80 text-slate-600'"
              class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
              x-text="cards.length + ' Classes'"></span>
      </button>

      {{-- Tab 2: Student Distribution & Remaining Tracker --}}
      <button type="button" @click="switchMainTab('distribution')"
              :class="activeMainTab === 'distribution' ? 'bg-white text-[#8C2826] shadow-xs font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold border border-transparent hover:bg-white/50'"
              class="px-4 py-2.5 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all cursor-pointer relative">
        <svg class="w-4 h-4" :class="activeMainTab === 'distribution' ? 'text-[#8C2826]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
        <span>Student Distribution &amp; Tracker</span>
        <span x-show="distSummary.students_with_remaining > 0"
              class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-2xs"
              x-text="distSummary.students_with_remaining + ' Pending'">
        </span>
      </button>

      {{-- Tab 3: Quick Fee Check & Student Issuance Desk --}}
      <button type="button" @click="switchMainTab('desk')"
              :class="activeMainTab === 'desk' ? 'bg-white text-[#8C2826] shadow-xs font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold border border-transparent hover:bg-white/50'"
              class="px-4 py-2.5 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all cursor-pointer relative">
        <svg class="w-4 h-4" :class="activeMainTab === 'desk' ? 'text-[#8C2826]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <span>Fee Check &amp; Issuance Desk</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-600 text-white shadow-2xs">
          ⚡ Quick Desk
        </span>
      </button>
    </div>

    {{-- Live status indicator --}}
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200/90 shadow-2xs text-xs font-semibold text-slate-600">
      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
      <span>Physical Issuance &amp; Delivery Tracking</span>
    </div>
  </div>

  {{-- ── Notification Alert ────────────────────────────────────────────────── --}}
  <div x-show="alert.show" x-cloak
       :class="alert.type === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-rose-50 text-rose-900 border-rose-200'"
       class="p-4 rounded-xl border flex items-center justify-between text-xs font-bold transition shadow-xs">
    <div class="flex items-center gap-2.5">
      <svg x-show="alert.type === 'success'" class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <svg x-show="alert.type === 'error'" class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <span x-text="alert.message"></span>
    </div>
    <button @click="alert.show = false" class="text-slate-400 hover:text-slate-600 font-black text-sm">&times;</button>
  </div>

  {{-- ── TAB 1: STANDARD MASTER CHECKLISTS ─────────────────────────────────── --}}
  <div x-show="activeMainTab === 'checklists'" class="space-y-6">

  {{-- ── Filter & Search Toolbar ──────────────────────────────────────────── --}}
  <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3.5">
    
    {{-- Top Row: Academic Year on Left & Global Inventory Badges on Right --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      
      {{-- Academic Year Selector --}}
      <div class="flex items-center gap-2.5">
        <label class="text-xs font-bold text-slate-500 whitespace-nowrap uppercase tracking-wider flex items-center gap-1.5">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <span>Academic Year:</span>
        </label>
        <div class="relative">
          <select x-model="selectedYearId" @change="loadCards()"
                  class="pl-3.5 pr-8 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-slate-50/70 hover:bg-slate-100/70 focus:border-[#8C2826] focus:ring-1 focus:ring-[#8C2826] transition cursor-pointer appearance-none">
            @foreach($academicYears as $year)
              <option value="{{ $year->id }}" {{ $year->id == $currentYear?->id ? 'selected' : '' }}>
                {{ $year->name }} {{ $year->is_current ? '— (Current Active)' : '' }}
              </option>
            @endforeach
          </select>
          <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>
      </div>

      {{-- Global Counters (Right Aligned on Top Row) --}}
      <div class="flex items-center gap-2 flex-wrap text-xs self-start sm:self-auto">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-900 border border-amber-200/80 font-bold">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
          <span>Books:</span>
          <span class="font-extrabold tabular-nums" x-text="summary.totalBooks"></span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-50 text-indigo-900 border border-indigo-200/80 font-bold">
          <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
          <span>Notes:</span>
          <span class="font-extrabold tabular-nums" x-text="summary.totalNotes"></span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 font-black">
          <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          <span class="tabular-nums" x-text="summary.totalUnits + ' Total Units'"></span>
        </div>
      </div>

    </div>

    {{-- Bottom Row: Category Stage Filters on Left & Search Input on Right --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pt-3 border-t border-slate-100">
      
      {{-- Stage Filter Pills --}}
      <div class="flex items-center gap-1.5 flex-wrap overflow-x-auto pb-0.5">
        <button type="button" @click="selectedCategory = 'ALL'"
                :class="selectedCategory === 'ALL' ? 'bg-[#8C2826] text-white shadow-xs font-bold' : 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900 font-semibold'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer whitespace-nowrap">
          All Classes (<span x-text="cards.length"></span>)
        </button>
        <button type="button" @click="selectedCategory = 'KINDERGARTEN'"
                :class="selectedCategory === 'KINDERGARTEN' ? 'bg-[#8C2826] text-white shadow-xs font-bold' : 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900 font-semibold'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer whitespace-nowrap">
          Kindergarten (Pre-KG – UKG)
        </button>
        <button type="button" @click="selectedCategory = 'PRIMARY'"
                :class="selectedCategory === 'PRIMARY' ? 'bg-[#8C2826] text-white shadow-xs font-bold' : 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900 font-semibold'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer whitespace-nowrap">
          Primary (Class I – V)
        </button>
        <button type="button" @click="selectedCategory = 'MIDDLE_HIGH'"
                :class="selectedCategory === 'MIDDLE_HIGH' ? 'bg-[#8C2826] text-white shadow-xs font-bold' : 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900 font-semibold'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer whitespace-nowrap">
          Middle &amp; High (Class VI – X)
        </button>
        <button type="button" @click="selectedCategory = 'SENIOR_SEC'"
                :class="selectedCategory === 'SENIOR_SEC' ? 'bg-[#8C2826] text-white shadow-xs font-bold' : 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900 font-semibold'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer whitespace-nowrap">
          Senior Secondary (XI – XII)
        </button>
      </div>

      {{-- Search bar --}}
      <div class="relative w-full lg:w-72 shrink-0">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" x-model="searchQuery" @input.debounce.300ms="loadCards()"
               placeholder="Search standard, title, SKU..."
               class="w-full pl-9 pr-7 py-1.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:border-[#8C2826] focus:ring-1 focus:ring-[#8C2826] bg-slate-50/50">
        <button x-show="searchQuery" @click="searchQuery = ''; loadCards()" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs font-bold">
          &times;
        </button>
      </div>

    </div>
  </div>

  {{-- ── Loading Spinner State ─────────────────────────────────────────────── --}}
  <div x-show="cardsLoading" class="py-16 text-center space-y-3">
    <div class="w-10 h-10 border-4 border-[#8C2826] border-t-transparent rounded-full animate-spin mx-auto"></div>
    <p class="text-xs font-bold text-slate-500">Loading standard cards...</p>
  </div>

  {{-- ── Class Cards Grid (One Card Per Class) ─────────────────────────────── --}}
  <div x-show="!cardsLoading" class="space-y-6">

    <div x-show="filteredCards.length === 0" class="py-16 text-center bg-white rounded-2xl border border-slate-200 p-8 space-y-3">
      <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto font-black text-lg">?</div>
      <h3 class="text-sm font-bold text-slate-800">No classes matched your search or category filter</h3>
      <p class="text-xs text-slate-400">Try clearing the search query or selecting "All Classes".</p>
      <button type="button" @click="searchQuery = ''; selectedCategory = 'ALL'; loadCards()" class="btn-xs btn-secondary inline-block">Reset Filters</button>
    </div>

    <div :class="cardGridClass">
      <template x-for="card in filteredCards" :key="card.class_id">
        <div @click="touchCard(card)"
             class="group bg-white rounded-2xl border border-slate-200/90 hover:border-[#8C2826]/40 p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between cursor-pointer relative overflow-hidden"
             :class="card.is_senior_secondary ? 'bg-gradient-to-b from-purple-50/20 via-white to-white' : ''">

          <div class="space-y-4">
            {{-- 1. Card Header: Avatar, Name, Stage & Total Badge --}}
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-xs tracking-tight shrink-0 transition-colors"
                     :class="card.is_senior_secondary 
                       ? 'bg-purple-100 text-purple-900 border border-purple-200' 
                       : (card.class_name.toUpperCase().includes('KG') || card.class_name.toUpperCase().includes('PRE')
                          ? 'bg-rose-50 text-rose-800 border border-rose-200' 
                          : 'bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA]')"
                     x-text="getClassBadge(card.class_name)">
                </div>
                <div class="min-w-0">
                  <h3 class="text-base font-black text-slate-900 tracking-tight truncate group-hover:text-[#8C2826] transition-colors"
                      x-text="card.class_name.toUpperCase().startsWith('CLASS') || card.class_name.toUpperCase().includes('KG') ? card.class_name : 'Class ' + card.class_name">
                  </h3>
                  <span class="text-xs text-slate-400 font-medium block truncate mt-0.5" x-text="getClassStage(card.class_name, card.is_senior_secondary)"></span>
                </div>
              </div>

              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black tabular-nums border shrink-0"
                    :class="card.total_items > 0 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'">
                <span class="w-1.5 h-1.5 rounded-full" :class="card.total_items > 0 ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                <span x-text="card.total_items > 0 ? card.grand_total_qty + ' Units' : '0 Units'"></span>
              </span>
            </div>

            {{-- 2. Card Content: For Standard Classes --}}
            <template x-if="!card.is_senior_secondary">
              <div class="space-y-3.5">
                {{-- Clean Minimalist Stats Bar (Single Cohesive Panel) --}}
                <div class="grid grid-cols-3 divide-x divide-slate-100 bg-slate-50/80 border border-slate-200/60 rounded-xl py-2.5 px-1 text-center">
                  <div class="px-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Books</span>
                    <div class="text-base font-black text-slate-900 tabular-nums leading-tight mt-0.5" x-text="card.books_count"></div>
                    <span class="text-[10px] text-slate-500 font-medium block mt-0.5" x-text="card.books_qty + ' units'"></span>
                  </div>

                  <div class="px-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Notes</span>
                    <div class="text-base font-black text-slate-900 tabular-nums leading-tight mt-0.5" x-text="card.notes_count"></div>
                    <span class="text-[10px] text-slate-500 font-medium block mt-0.5" x-text="card.notes_qty + ' units'"></span>
                  </div>

                  <div class="px-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Uniform</span>
                    <div class="text-base font-black text-slate-900 tabular-nums leading-tight mt-0.5" x-text="card.uniforms_count || 0"></div>
                    <span class="text-[10px] text-slate-500 font-medium block mt-0.5 truncate" x-text="(card.uniforms_boys_qty || 0) + 'B / ' + (card.uniforms_girls_qty || 0) + 'G'"></span>
                  </div>
                </div>

                {{-- Clean Inventory Progress Strip --}}
                <div class="space-y-1.5" x-show="card.grand_total_qty > 0">
                  <div class="flex items-center justify-between text-[11px] text-slate-500 font-medium">
                    <span>Prescribed Allocation</span>
                    <span class="font-bold text-slate-700 tabular-nums" x-text="card.total_items + ' Items Prescribed'"></span>
                  </div>
                  <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden flex">
                    <div class="bg-amber-400 h-full transition-all duration-300" :style="'width: ' + ((card.books_qty / (card.grand_total_qty || 1)) * 100) + '%'" title="Textbooks"></div>
                    <div class="bg-indigo-400 h-full transition-all duration-300 ml-0.5" :style="'width: ' + ((card.notes_qty / (card.grand_total_qty || 1)) * 100) + '%'" title="Notebooks"></div>
                    <div class="bg-teal-400 h-full transition-all duration-300 ml-0.5" :style="'width: ' + ((card.uniforms_boys_qty / (card.grand_total_qty || 1)) * 100) + '%'" title="Uniforms"></div>
                  </div>
                  <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold pt-0.5">
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Books (<strong class="text-slate-600 font-bold" x-text="card.books_qty"></strong>)</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>Notes (<strong class="text-slate-600 font-bold" x-text="card.notes_qty"></strong>)</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-teal-400"></span>Uniform (<strong class="text-slate-600 font-bold" x-text="card.uniforms_boys_qty"></strong>)</span>
                  </div>
                </div>

                {{-- Empty Placeholder --}}
                <div x-show="card.total_items === 0" class="py-5 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-slate-400 text-xs font-medium">
                  <span>No curriculum items configured</span>
                </div>
              </div>
            </template>

            {{-- 3. Card Content: For Senior Secondary (Grade XI & XII) --}}
            <template x-if="card.is_senior_secondary">
              <div class="space-y-3">
                {{-- Clean Uniform Badge --}}
                <div class="px-3 py-2 rounded-xl bg-teal-50/70 border border-teal-100 flex items-center justify-between text-xs">
                  <span class="font-bold text-teal-900 flex items-center gap-1.5">
                    <span>👔</span>
                    <span>School Uniform</span>
                  </span>
                  <span class="font-extrabold text-teal-800 text-[11px]" x-text="(card.uniforms_count || 0) + ' items • ' + (card.uniforms_boys_qty || 0) + ' Units'"></span>
                </div>

                {{-- Stream List --}}
                <div class="space-y-1.5">
                  <div class="text-[10px] font-bold text-purple-900 uppercase tracking-wider">Streams Breakdown:</div>
                  <template x-for="grp in card.groups" :key="grp.group_id">
                    <div class="p-2.5 rounded-xl bg-purple-50/40 border border-purple-100 hover:bg-purple-50 transition-colors flex items-center justify-between gap-2">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="text-sm shrink-0" x-text="grp.group_code === 'BIO' ? '🧬' : (grp.group_code === 'CS' ? '💻' : '📊')"></span>
                        <span class="font-bold text-purple-950 text-xs truncate" x-text="grp.group_name"></span>
                      </div>
                      <span class="text-[11px] font-bold text-purple-700 shrink-0 tabular-nums" x-text="grp.grand_total_qty + ' Units'"></span>
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </div>

          {{-- 4. Sleek Card Footer Action Bar --}}
          <div class="pt-3.5 mt-4 border-t border-slate-100 flex items-center justify-between gap-2 text-xs">
            <button type="button" @click="touchCard(card)"
                    class="flex-1 py-2 px-3 rounded-xl bg-slate-50 hover:bg-[#8C2826] text-slate-700 hover:text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-all duration-150 border border-slate-200/70 hover:border-transparent cursor-pointer group/btn">
              <span>View Checklist</span>
              <svg class="w-3.5 h-3.5 transition-transform group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
            <button type="button" @click.stop="openCreateModal(card.class_id, card.is_senior_secondary ? (card.groups[0]?.group_id || '') : '')"
                    title="Add Item"
                    class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-[#8C2826] text-slate-500 hover:text-white border border-slate-200/70 flex items-center justify-center transition cursor-pointer shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            </button>
          </div>

        </div>
      </template>
    </div>
  </div>
  </div>{{-- Close Tab 1: Standard Master Checklists --}}

  {{-- ── TAB 2: STUDENT DISTRIBUTION & REMAINING TRACKER ──────────────────── --}}
  <div x-show="activeMainTab === 'distribution'" x-cloak class="space-y-6">

    {{-- ── Executive Distribution KPI Cards ──────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
      
      {{-- Card 1: Students Needing Remaining Items --}}
      <div class="bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-200/80 rounded-3xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
        <div class="flex items-center justify-between">
          <span class="text-xs font-black uppercase tracking-wider text-amber-900">Needs Remaining</span>
          <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-900 flex items-center justify-center font-black text-sm">⏳</span>
        </div>
        <div>
          <div class="text-3xl font-black text-amber-950 tracking-tight" x-text="distSummary.students_with_remaining"></div>
          <p class="text-[11px] text-amber-800/80 font-medium mt-0.5">Students waiting for balance items</p>
        </div>
        <div class="pt-2 border-t border-amber-200/60 flex items-center justify-between text-[11px] font-bold text-amber-900">
          <span>Action Required</span>
          <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-900" x-text="distSummary.students_with_remaining > 0 ? 'Pending Delivery' : 'All Clear'"></span>
        </div>
      </div>

      {{-- Card 2: Fully Issued Students --}}
      <div class="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-200/80 rounded-3xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
        <div class="flex items-center justify-between">
          <span class="text-xs font-black uppercase tracking-wider text-emerald-900">Fully Issued</span>
          <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-900 flex items-center justify-center font-black text-sm">✅</span>
        </div>
        <div>
          <div class="text-3xl font-black text-emerald-950 tracking-tight" x-text="distSummary.students_fully_issued"></div>
          <p class="text-[11px] text-emerald-800/80 font-medium mt-0.5">All books, notes &amp; uniforms received</p>
        </div>
        <div class="pt-2 border-t border-emerald-200/60 flex items-center justify-between text-[11px] font-bold text-emerald-900">
          <span>Completed</span>
          <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900" x-text="distSummary.total_students > 0 ? (Math.round((distSummary.students_fully_issued / distSummary.total_students) * 100) + '% of enrolled') : '0%'"></span>
        </div>
      </div>

      {{-- Card 3: Remaining Textbooks Units --}}
      <div class="bg-gradient-to-br from-sky-500/10 via-sky-500/5 to-white border border-sky-200/80 rounded-3xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
        <div class="flex items-center justify-between">
          <span class="text-xs font-black uppercase tracking-wider text-sky-900">Remaining Books</span>
          <span class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-900 flex items-center justify-center font-black text-sm">📚</span>
        </div>
        <div>
          <div class="text-3xl font-black text-sky-950 tracking-tight" x-text="distSummary.remaining_books_units"></div>
          <p class="text-[11px] text-sky-800/80 font-medium mt-0.5">Book units pending delivery</p>
        </div>
        <div class="pt-2 border-t border-sky-200/60 flex items-center justify-between text-[11px] font-bold text-sky-900">
          <span>Balance Stock</span>
          <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-900" x-text="distSummary.remaining_books_units + ' Units'"></span>
        </div>
      </div>

      {{-- Card 4: Remaining Notebooks Units --}}
      <div class="bg-gradient-to-br from-purple-500/10 via-purple-500/5 to-white border border-purple-200/80 rounded-3xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
        <div class="flex items-center justify-between">
          <span class="text-xs font-black uppercase tracking-wider text-purple-900">Remaining Notes</span>
          <span class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-900 flex items-center justify-center font-black text-sm">📝</span>
        </div>
        <div>
          <div class="text-3xl font-black text-purple-950 tracking-tight" x-text="distSummary.remaining_notes_units"></div>
          <p class="text-[11px] text-purple-800/80 font-medium mt-0.5">Notebook units pending delivery</p>
        </div>
        <div class="pt-2 border-t border-purple-200/60 flex items-center justify-between text-[11px] font-bold text-purple-900">
          <span>Balance Notebooks</span>
          <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-900" x-text="distSummary.remaining_notes_units + ' Units'"></span>
        </div>
      </div>

      {{-- Card 5: Remaining Uniform Units --}}
      <div class="bg-gradient-to-br from-teal-500/10 via-teal-500/5 to-white border border-teal-200/80 rounded-3xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
        <div class="flex items-center justify-between">
          <span class="text-xs font-black uppercase tracking-wider text-teal-900">Remaining Uniforms</span>
          <span class="w-8 h-8 rounded-xl bg-teal-500/20 text-teal-900 flex items-center justify-center font-black text-sm">👔</span>
        </div>
        <div>
          <div class="text-3xl font-black text-teal-950 tracking-tight" x-text="distSummary.remaining_uniforms_units || 0"></div>
          <p class="text-[11px] text-teal-800/80 font-medium mt-0.5">Uniform articles pending delivery</p>
        </div>
        <div class="pt-2 border-t border-teal-200/60 flex items-center justify-between text-[11px] font-bold text-teal-900">
          <span>Balance Uniforms</span>
          <span class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-900" x-text="(distSummary.remaining_uniforms_units || 0) + ' Units'"></span>
        </div>
      </div>

    </div>

    {{-- ── Distribution Filter Toolbar ───────────────────────────────────────── --}}
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs space-y-4">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

        {{-- Class & Status Filter Dropdowns --}}
        <div class="flex items-center gap-3 flex-wrap">
          
          {{-- Academic Year Selector --}}
          <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-500 whitespace-nowrap uppercase tracking-wider flex items-center gap-1.5">
              <span>Year:</span>
            </label>
            <div class="relative">
              <select x-model="selectedYearId" @change="loadDistribution(1)"
                      class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-slate-50/70 hover:bg-slate-100/70 focus:border-[#8C2826] focus:ring-1 focus:ring-[#8C2826] transition cursor-pointer appearance-none">
                @foreach($academicYears as $year)
                  <option value="{{ $year->id }}">
                    {{ $year->name }} {{ $year->is_current ? '— (Active)' : '' }}
                  </option>
                @endforeach
              </select>
              <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </div>
          </div>

          {{-- Class Selector --}}
          <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-500 whitespace-nowrap uppercase tracking-wider">
              <span>Standard:</span>
            </label>
            <div class="relative">
              <select x-model="distClassId" @change="onDistFilterChange()"
                      class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-slate-50/70 hover:bg-slate-100/70 focus:border-[#8C2826] focus:ring-1 focus:ring-[#8C2826] transition cursor-pointer appearance-none">
                <option value="">All Standards / Classes</option>
                @foreach($classes as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
              <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </div>
          </div>

          {{-- Quick Status Filter Segmented Switcher --}}
          <div class="flex items-center gap-1 bg-slate-100/80 p-1 rounded-xl">
            <button type="button" @click="distStatus = 'pending'; onDistFilterChange()"
                    :class="distStatus === 'pending' ? 'bg-amber-500 text-white shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
              <span>⏳ Needs Remaining</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                    :class="distStatus === 'pending' ? 'bg-white/30 text-white' : 'bg-slate-200 text-slate-700'"
                    x-text="distSummary.students_with_remaining"></span>
            </button>
            <button type="button" @click="distStatus = 'all'; onDistFilterChange()"
                    :class="distStatus === 'all' ? 'bg-slate-900 text-white shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
              <span>All Enrolled</span>
            </button>
            <button type="button" @click="distStatus = 'fully_issued'; onDistFilterChange()"
                    :class="distStatus === 'fully_issued' ? 'bg-emerald-600 text-white shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
              <span>✅ Fully Issued</span>
            </button>
            <button type="button" @click="distStatus = 'partially_issued'; onDistFilterChange()"
                    :class="distStatus === 'partially_issued' ? 'bg-indigo-600 text-white shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
              <span>⚠️ Partial</span>
            </button>
          </div>

        </div>

        {{-- Search Input --}}
        <div class="relative flex-1 max-w-sm">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input type="text" x-model="distSearch" @input.debounce.300ms="onDistFilterChange()"
                 placeholder="Search student by name, admission no, roll no..."
                 class="w-full pl-10 pr-8 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:border-[#8C2826] focus:ring-[#8C2826] bg-slate-50/50">
          <button x-show="distSearch" @click="distSearch = ''; onDistFilterChange()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 text-xs font-bold">
            &times;
          </button>
        </div>

      </div>

      {{-- Action / Info Strip --}}
      <div class="flex items-center justify-between flex-wrap gap-3 pt-3 border-t border-slate-100 text-xs">
        <div class="flex items-center gap-2 text-slate-600 font-semibold">
          <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Showing students according to their current prescribed curriculum checklist.</span>
        </div>
        <div class="flex items-center gap-3">
          <button type="button" @click="loadDistribution(distPage)"
                  class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 font-bold text-xs text-slate-700 flex items-center gap-1.5 transition cursor-pointer">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span>Refresh</span>
          </button>
        </div>
      </div>
    </div>

    {{-- ── Loading Spinner State ─────────────────────────────────────────────── --}}
    <div x-show="distLoading" class="py-16 text-center space-y-3 bg-white rounded-3xl border border-slate-200/90 shadow-xs">
      <div class="w-10 h-10 border-4 border-[#8C2826] border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs font-bold text-slate-500">Loading student distribution records...</p>
    </div>

    {{-- ── Empty State ───────────────────────────────────────────────────────── --}}
    <div x-show="!distLoading && distStudents.length === 0" class="py-16 text-center bg-white rounded-3xl border border-slate-200/90 p-8 space-y-4 shadow-xs">
      <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-2xl font-black">
        ✓
      </div>
      <div>
        <h3 class="text-sm font-bold text-slate-800">No students found matching your criteria</h3>
        <p class="text-xs text-slate-500 mt-1" x-text="distStatus === 'pending' ? 'Great news! All enrolled students have either received their items or no students match the selected filter.' : 'No student records exist for this class or search term.'"></p>
      </div>
      <div class="flex items-center justify-center gap-3">
        <button type="button" @click="distStatus = 'all'; distClassId = ''; distSearch = ''; onDistFilterChange()"
                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition cursor-pointer">
          View All Enrolled Students
        </button>
      </div>
    </div>

    {{-- ── Student Distribution Table ────────────────────────────────────────── --}}
    <div x-show="!distLoading && distStudents.length > 0" class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
      
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase tracking-wider text-slate-500">
              <th class="py-3.5 px-5">Student Information</th>
              <th class="py-3.5 px-4">Standard &amp; Section</th>
              <th class="py-3.5 px-4">Payment Status</th>
              <th class="py-3.5 px-4">Distribution Status</th>
              <th class="py-3.5 px-4">Textbooks</th>
              <th class="py-3.5 px-4">Notebooks</th>
              <th class="py-3.5 px-4">Uniforms</th>
              <th class="py-3.5 px-4 min-w-[200px]">Pending Items Preview</th>
              <th class="py-3.5 px-5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <template x-for="st in distStudents" :key="st.student_id">
              <tr class="hover:bg-slate-50/70 transition-colors">
                
                {{-- Student Information --}}
                <td class="py-3.5 px-5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-xs shrink-0 shadow-2xs"
                         :class="st.total_remaining === 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-900 border border-amber-200'"
                         x-text="(st.student_name || 'S').slice(0, 2).toUpperCase()">
                    </div>
                    <div>
                      <div class="font-black text-slate-900 text-xs tracking-tight" x-text="st.student_name"></div>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded border border-slate-200" x-text="st.admission_no"></span>
                        <template x-if="st.roll_no">
                          <span class="text-[10px] text-slate-400" x-text="'Roll: ' + st.roll_no"></span>
                        </template>
                        <template x-if="st.gender">
                          <span class="text-[9px] font-black px-1.5 py-0.2 rounded border uppercase tracking-wider"
                                :class="(st.gender || '').toLowerCase() === 'male' || st.gender === 'BOYS' ? 'bg-sky-50 text-sky-800 border-sky-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
                                x-text="(st.gender || '').toLowerCase() === 'male' || st.gender === 'BOYS' ? '♂ Boy' : '♀ Girl'"></span>
                        </template>
                      </div>
                    </div>
                  </div>
                </td>

                {{-- Class & Section --}}
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800" x-text="st.class_name + (st.section_name ? ' — ' + st.section_name : '')"></div>
                  <template x-if="st.stream_group">
                    <span class="inline-block mt-0.5 text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200" x-text="st.stream_group"></span>
                  </template>
                </td>

                {{-- Payment Status Badge --}}
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black border whitespace-nowrap shadow-2xs"
                        :class="st.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : (st.payment_status === 'partially_paid' || st.payment_status === 'partial' ? 'bg-indigo-50 text-indigo-800 border-indigo-300' : 'bg-amber-50 text-amber-900 border-amber-300')">
                    <span class="w-1.5 h-1.5 rounded-full" :class="st.payment_status === 'paid' ? 'bg-emerald-500' : (st.payment_status === 'partially_paid' || st.payment_status === 'partial' ? 'bg-indigo-500' : 'bg-amber-500')"></span>
                    <span x-text="st.payment_status_label || (st.payment_status === 'paid' ? 'Paid' : 'Pending Payment')"></span>
                  </span>
                </td>

                {{-- Distribution Status Badge --}}
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black border whitespace-nowrap shadow-2xs"
                        :class="st.total_remaining === 0 ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : (st.total_issued > 0 ? 'bg-sky-50 text-sky-800 border-sky-300' : 'bg-amber-50 text-amber-900 border-amber-300')">
                    <span class="w-1.5 h-1.5 rounded-full" :class="st.total_remaining === 0 ? 'bg-emerald-500' : (st.total_issued > 0 ? 'bg-sky-500' : 'bg-amber-500')"></span>
                    <span x-text="st.distribution_status || (st.total_remaining === 0 ? 'Distributed' : (st.total_issued > 0 ? 'Partially Distributed' : 'Pending Distribution'))"></span>
                  </span>
                </td>

                {{-- Textbooks Progress --}}
                <td class="py-3.5 px-4">
                  <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                      <span class="text-slate-700"><span x-text="st.books_issued"></span> / <span x-text="st.books_prescribed"></span> Books</span>
                      <span :class="st.books_remaining === 0 ? 'text-emerald-700' : 'text-amber-700'"
                            x-text="st.books_remaining === 0 ? 'Done' : (st.books_remaining + ' Left')"></span>
                    </div>
                    <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full rounded-full transition-all duration-300"
                           :class="st.books_remaining === 0 ? 'bg-emerald-500' : 'bg-amber-500'"
                           :style="'width: ' + (st.books_prescribed > 0 ? Math.min(100, (st.books_issued / st.books_prescribed) * 100) : 100) + '%'"></div>
                    </div>
                  </div>
                </td>

                {{-- Notebooks Progress --}}
                <td class="py-3.5 px-4">
                  <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                      <span class="text-slate-700"><span x-text="st.notes_issued"></span> / <span x-text="st.notes_prescribed"></span> Notes</span>
                      <span :class="st.notes_remaining === 0 ? 'text-emerald-700' : 'text-indigo-700'"
                            x-text="st.notes_remaining === 0 ? 'Done' : (st.notes_remaining + ' Left')"></span>
                    </div>
                    <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full rounded-full transition-all duration-300"
                           :class="st.notes_remaining === 0 ? 'bg-emerald-500' : 'bg-indigo-500'"
                           :style="'width: ' + (st.notes_prescribed > 0 ? Math.min(100, (st.notes_issued / st.notes_prescribed) * 100) : 100) + '%'"></div>
                    </div>
                  </div>
                </td>

                {{-- Uniforms Progress --}}
                <td class="py-3.5 px-4">
                  <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                      <span class="text-slate-700"><span x-text="st.uniforms_issued || 0"></span> / <span x-text="st.uniforms_prescribed || 0"></span> Uniforms</span>
                      <span :class="(st.uniforms_remaining || 0) === 0 ? 'text-emerald-700' : 'text-teal-700'"
                            x-text="(st.uniforms_remaining || 0) === 0 ? 'Done' : ((st.uniforms_remaining || 0) + ' Left')"></span>
                    </div>
                    <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full rounded-full transition-all duration-300"
                           :class="(st.uniforms_remaining || 0) === 0 ? 'bg-emerald-500' : 'bg-teal-500'"
                           :style="'width: ' + ((st.uniforms_prescribed || 0) > 0 ? Math.min(100, ((st.uniforms_issued || 0) / (st.uniforms_prescribed || 1)) * 100) : 100) + '%'"></div>
                    </div>
                  </div>
                </td>

                {{-- Pending Items Preview --}}
                <td class="py-3.5 px-4">
                  <template x-if="st.total_remaining === 0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                      <span>All Prescribed Items Issued</span>
                    </span>
                  </template>

                  <template x-if="st.total_remaining > 0">
                    <div class="space-y-1.5">
                      <div class="flex items-center gap-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-950 border border-amber-300"
                              x-text="st.total_remaining + ' Items Pending'"></span>
                      </div>
                      <div class="flex flex-wrap gap-1 max-w-xs">
                        <template x-for="p in st.pending_preview" :key="p.name">
                          <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 truncate max-w-[160px]"
                                :title="p.name"
                                x-text="p.name + ' (' + p.rem + ')'"></span>
                        </template>
                        <span x-show="st.pending_count > 3" class="text-[10px] font-bold text-slate-400 self-center"
                              x-text="'+' + (st.pending_count - 3) + ' more'"></span>
                      </div>
                    </div>
                  </template>
                </td>

                {{-- Actions --}}
                <td class="py-3.5 px-5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    
                    {{-- Open Checklist & Issue Modal --}}
                    <button type="button" @click="openStudentModal(st.student_id)"
                            class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-black text-xs inline-flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                      <span>Checklist &amp; Issue</span>
                    </button>

                    {{-- Issue All Remaining Quick Button --}}
                    <button type="button" @click="issueAllRemaining(st.student_id)"
                            :disabled="st.total_remaining === 0"
                            :title="st.total_remaining === 0 ? 'All items already issued' : 'Issue all remaining items at once'"
                            class="p-2 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-black text-xs transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                      ⚡
                    </button>

                    {{-- Print Slip --}}
                    <button type="button" @click="openPrintSlip(st.student_id)"
                            title="Print Distribution Slip"
                            class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 transition cursor-pointer">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </button>

                  </div>
                </td>

              </tr>
            </template>
          </tbody>
        </table>
      </div>

      {{-- Table Pagination Footer --}}
      <div class="p-4 px-6 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-bold text-slate-600">
        <div>
          Showing 
          <span class="text-slate-900" x-text="distStudents.length > 0 ? ((distPagination.current_page - 1) * distPagination.per_page + 1) : 0"></span>
          to
          <span class="text-slate-900" x-text="Math.min(distPagination.current_page * distPagination.per_page, distPagination.total)"></span>
          of 
          <span class="text-slate-900" x-text="distPagination.total"></span> 
          students
        </div>

        <div class="flex items-center gap-2">
          <button type="button" @click="loadDistributionStudents(distPagination.current_page - 1)"
                  :disabled="distPagination.current_page <= 1"
                  class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 font-bold transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            &larr; Previous
          </button>
          <span class="px-3 py-1.5 rounded-xl bg-slate-200/60 text-slate-800 text-xs font-black">
            Page <span x-text="distPagination.current_page"></span> of <span x-text="distPagination.last_page"></span>
          </span>
          <button type="button" @click="loadDistributionStudents(distPagination.current_page + 1)"
                  :disabled="distPagination.current_page >= distPagination.last_page"
                  class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 font-bold transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            Next &rarr;
          </button>
        </div>
      </div>

    </div>

  </div>{{-- Close Tab 2: Student Distribution Tracker --}}

  {{-- ── TAB 3: QUICK FEE CHECK & STUDENT BOOK ISSUANCE DESK ────────────────── --}}
  <div x-show="activeMainTab === 'desk'" x-cloak class="space-y-4">

    {{-- Sleek Search & Quick Student Selector Bar --}}
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
      <div class="flex flex-col md:flex-row md:items-center gap-3">
        
        {{-- Search Input with Autocomplete --}}
        <div class="relative flex-1">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
            <input type="text"
                   x-ref="deskSearchInput"
                   x-model="deskQuery"
                   @input.debounce.250ms="searchDeskStudents()"
                   @focus="if(deskStudents.length === 0) searchDeskStudents(); deskDropdownOpen = true"
                   placeholder="Search student by name, admission no (e.g. ADM26-0612), or roll no..."
                   class="w-full pl-10 pr-24 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold text-slate-900 focus:border-[#8C2826] focus:ring-1 focus:ring-[#8C2826] bg-slate-50/60 hover:bg-white focus:bg-white transition-all">

            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center gap-1.5">
              <div x-show="deskLoading" class="w-4 h-4 border-2 border-[#8C2826] border-t-transparent rounded-full animate-spin"></div>
              <button x-show="deskQuery" @click="deskQuery = ''; searchDeskStudents()" type="button" class="text-slate-400 hover:text-slate-700 font-black text-sm p-1 rounded-lg">
                &times;
              </button>
              <button type="button" @click="searchDeskStudents()" class="px-3 py-1 rounded-lg bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition cursor-pointer">
                Search
              </button>
            </div>
          </div>

          {{-- Autocomplete Dropdown --}}
          <div x-show="deskDropdownOpen && deskStudents.length > 0"
               @click.outside="deskDropdownOpen = false"
               x-transition:enter="ease-out duration-150"
               x-transition:enter-start="opacity-0 translate-y-1"
               x-transition:enter-end="opacity-100 translate-y-0"
               class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-30 max-h-72 overflow-y-auto divide-y divide-slate-100">
            <div class="px-3 py-2 bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
              <span>Matching Students (<span x-text="deskStudents.length"></span>)</span>
              <span class="text-[10px]">Select student to view fee &amp; books</span>
            </div>
            <template x-for="st in deskStudents" :key="st.id">
              <div @click="selectDeskStudent(st)"
                   class="px-3.5 py-2.5 hover:bg-slate-50 cursor-pointer transition flex items-center justify-between gap-3 group">
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-[10px] shrink-0"
                       :class="st.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : (st.payment_status === 'partially_paid' || st.payment_status === 'partial' ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800')"
                       x-text="(st.name || 'S').slice(0, 2).toUpperCase()">
                  </div>
                  <div class="min-w-0">
                    <div class="font-bold text-xs text-slate-900 group-hover:text-[#8C2826] truncate" x-text="st.name"></div>
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                      <span class="font-mono text-slate-600" x-text="st.admission_no"></span>
                      <span>&bull;</span>
                      <span class="text-slate-600" x-text="st.class_name + (st.section_name ? '-' + st.section_name : '')"></span>
                      <template x-if="st.roll_no">
                        <span x-text="'&bull; Roll ' + st.roll_no"></span>
                      </template>
                    </div>
                  </div>
                </div>
                <div class="shrink-0 text-right">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="st.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (st.payment_status === 'partially_paid' || st.payment_status === 'partial' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-rose-50 text-rose-700 border border-rose-200')"
                        x-text="st.payment_status === 'paid' ? 'Paid Full' : (st.payment_status === 'partially_paid' || st.payment_status === 'partial' ? 'Partial Due' : 'Unpaid')">
                  </span>
                </div>
              </div>
            </template>
          </div>
        </div>

        {{-- Quick Picks Chips --}}
        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Quick:</span>
          <button type="button" @click="selectDeskStudentById(627)"
                  class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition cursor-pointer flex items-center gap-1">
            <span>✓ Aarav Sharma (LKG • Paid)</span>
          </button>
          <button type="button" @click="selectDeskStudentById(642)"
                  class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 text-xs font-bold transition cursor-pointer flex items-center gap-1">
            <span>⚠️ Kavin C. (Class II • Due)</span>
          </button>
          <button type="button" @click="selectDeskStudentById(522)"
                  class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 text-xs font-bold transition cursor-pointer flex items-center gap-1">
            <span>🛑 Aarav Dar (Class XI • Unpaid)</span>
          </button>
        </div>

      </div>
    </div>

    {{-- State A: Compact Empty Search State --}}
    <div x-show="!deskActiveStudent && !deskStudentLoading"
         class="bg-white border border-slate-200/90 rounded-2xl p-8 sm:p-12 text-center shadow-xs">
      <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center mx-auto text-slate-400 text-xl font-bold mb-3">
        🔍
      </div>
      <h3 class="text-sm sm:text-base font-bold text-slate-800">Search Student to Verify Fee &amp; Issue Books</h3>
      <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Type student name or admission number in the search bar above to verify fees and mark class notebooks as issued.</p>
    </div>

    {{-- State B: Loading Spinner --}}
    <div x-show="deskStudentLoading" class="py-16 text-center space-y-2 bg-white rounded-2xl border border-slate-200/90 shadow-xs">
      <div class="w-8 h-8 border-3 border-[#8C2826] border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs font-bold text-slate-500">Loading student details &amp; checklist...</p>
    </div>

    {{-- State C: Student Workspace (UNIFIED SINGLE-ROW CONSOLE) --}}
    <div x-show="deskActiveStudent && !deskStudentLoading" class="space-y-4">

      {{-- 1. UNIFIED STUDENT IDENTITY & FEE BAR --}}
      <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          
          {{-- Student Profile --}}
          <div class="flex items-center gap-3.5 min-w-0">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-sm text-white shrink-0 shadow-xs"
                 :class="deskStudentData?.payment_status === 'paid' || deskStudentData?.is_fee_cleared ? 'bg-emerald-600' : ((deskStudentData?.payment_status === 'partially_paid' || deskStudentData?.payment_status === 'partial') ? 'bg-amber-600' : 'bg-rose-600')"
                 x-text="(deskStudentData?.name || 'S').slice(0, 2).toUpperCase()">
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight" x-text="deskStudentData?.name"></h2>
                <span class="font-mono text-[11px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200" x-text="deskStudentData?.admission_no"></span>
                <span class="text-[11px] font-black text-[#8C2826] bg-[#FFF5F5] px-2 py-0.5 rounded border border-[#FECACA]" x-text="'Class ' + deskStudentData?.class_name + (deskStudentData?.section_name ? ' — ' + deskStudentData?.section_name : '')"></span>
                <template x-if="deskStudentData?.gender">
                  <span class="text-[10px] font-bold px-1.5 py-0.2 rounded border uppercase tracking-wider"
                        :class="(deskStudentData?.gender || '').toLowerCase() === 'male' || deskStudentData?.gender === 'BOYS' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                        x-text="(deskStudentData?.gender || '').toLowerCase() === 'male' || deskStudentData?.gender === 'BOYS' ? 'Boy' : 'Girl'"></span>
                </template>
              </div>
              <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-3">
                <template x-if="deskStudentData?.roll_no">
                  <span>Roll: <strong class="text-slate-700 font-semibold" x-text="deskStudentData?.roll_no"></strong></span>
                </template>
                <template x-if="deskStudentData?.mobile">
                  <span>Contact: <strong class="text-slate-700 font-semibold" x-text="deskStudentData?.mobile"></strong></span>
                </template>
              </div>
            </div>
          </div>

          {{-- Fee Clearance Badge & Short Financial Stats --}}
          <div class="flex items-center gap-3 flex-wrap">
            
            {{-- Status: Paid --}}
            <template x-if="deskStudentData?.payment_status === 'paid' || deskStudentData?.is_fee_cleared">
              <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-300">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-black shrink-0">✓</span>
                <div>
                  <div class="text-xs font-black text-emerald-900 leading-tight">Fee Cleared (Paid Full)</div>
                  <div class="text-[11px] font-semibold text-emerald-700 mt-0.5">
                    Paid: <strong x-text="formatCurrency(deskStudentData?.admission_paid_amount || deskStudentData?.total_admission_fee)"></strong> • Due: ₹0.00
                    <template x-if="deskStudentData?.payment_mode">
                      <span class="text-emerald-600 font-normal"> (<span x-text="deskStudentData?.payment_mode"></span>)</span>
                    </template>
                  </div>
                </div>
              </div>
            </template>

            {{-- Status: Partial --}}
            <template x-if="(deskStudentData?.payment_status === 'partially_paid' || deskStudentData?.payment_status === 'partial') && !deskStudentData?.is_fee_cleared">
              <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-300">
                <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-black shrink-0">⚠️</span>
                <div>
                  <div class="text-xs font-black text-amber-900 leading-tight">Fee Partial Payment</div>
                  <div class="text-[11px] font-semibold text-amber-800 mt-0.5">
                    Paid: <span x-text="formatCurrency(deskStudentData?.admission_paid_amount)"></span> • Due: <strong class="text-amber-950" x-text="formatCurrency(deskStudentData?.admission_pending_amount)"></strong>
                  </div>
                </div>
              </div>
            </template>

            {{-- Status: Unpaid --}}
            <template x-if="deskStudentData?.payment_status === 'pending' && !deskStudentData?.is_fee_cleared">
              <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-rose-50 border border-rose-300">
                <span class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs font-black shrink-0">🛑</span>
                <div>
                  <div class="text-xs font-black text-rose-900 leading-tight">Fee Payment Pending</div>
                  <div class="text-[11px] font-semibold text-rose-800 mt-0.5">
                    Due: <strong class="text-rose-950" x-text="formatCurrency(deskStudentData?.admission_pending_amount || deskStudentData?.total_admission_fee)"></strong>
                  </div>
                </div>
              </div>
            </template>

            {{-- Action buttons --}}
            <div class="flex items-center gap-2">
              <button type="button" @click="openPrintSlip(deskStudentData?.id)"
                      title="Print Delivery Slip"
                      class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 font-bold text-xs text-slate-700 inline-flex items-center gap-1.5 transition cursor-pointer shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Slip</span>
              </button>
              <button type="button" @click="clearDeskStudent()"
                      class="px-2.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 font-bold text-xs text-slate-500 hover:text-slate-800 transition cursor-pointer shadow-2xs">
                &times; Change
              </button>
            </div>

          </div>

        </div>
      </div>

      {{-- 2. CURRICULUM ITEMS & ISSUANCE DESK --}}
      <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
        
        {{-- Toolbar: Tabs on Left, Fast Actions on Right --}}
        <div class="p-3 sm:p-4 border-b border-slate-200/80 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
          
          {{-- Category Tabs --}}
          <div class="inline-flex items-center gap-1 bg-slate-200/70 p-1 rounded-xl">
            <button type="button" @click="deskCategoryTab = 'NOTE'"
                    :class="deskCategoryTab === 'NOTE' ? 'bg-white text-indigo-700 font-extrabold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
              <span>📝 Notebooks</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                    :class="deskCategoryTab === 'NOTE' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-300 text-slate-700'"
                    x-text="deskStudentNotes.length"></span>
            </button>
            <button type="button" @click="deskCategoryTab = 'BOOK'"
                    :class="deskCategoryTab === 'BOOK' ? 'bg-white text-amber-700 font-extrabold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
              <span>📚 Books</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                    :class="deskCategoryTab === 'BOOK' ? 'bg-amber-100 text-amber-800' : 'bg-slate-300 text-slate-700'"
                    x-text="deskStudentBooks.length"></span>
            </button>
            <button type="button" @click="deskCategoryTab = 'UNIFORM'"
                    :class="deskCategoryTab === 'UNIFORM' ? 'bg-teal-700 font-extrabold shadow-2xs text-white' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
              <span>👔 Uniforms</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                    :class="deskCategoryTab === 'UNIFORM' ? 'bg-teal-900/30 text-white' : 'bg-slate-300 text-slate-700'"
                    x-text="deskStudentUniforms.length"></span>
            </button>
            <button type="button" @click="deskCategoryTab = 'ALL'"
                    :class="deskCategoryTab === 'ALL' ? 'bg-white text-slate-900 font-extrabold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
              <span>All</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                    :class="deskCategoryTab === 'ALL' ? 'bg-slate-200 text-slate-800' : 'bg-slate-300 text-slate-700'"
                    x-text="deskStudentBooks.length + deskStudentNotes.length + deskStudentUniforms.length"></span>
            </button>
          </div>

          {{-- Quick Stats & Batch Issue Buttons --}}
          <div class="flex items-center gap-2.5 flex-wrap">
            <div class="text-xs text-slate-500 font-semibold mr-1">
              Issued: <strong class="text-emerald-700" x-text="deskStudentStats?.totalIssued || 0"></strong> / <span x-text="deskStudentStats?.totalPrescribed || 0"></span>
              &bull; Pending: <strong class="text-amber-700" x-text="deskStudentStats?.totalRemaining || 0"></strong>
            </div>

            <button type="button" @click="issueDeskRemaining('NOTE')"
                    :disabled="(deskStudentStats?.notesRemaining || 0) === 0"
                    class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs inline-flex items-center gap-1 shadow-2xs transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
              <span>⚡ Issue All Notes (<span x-text="deskStudentStats?.notesRemaining || 0"></span>)</span>
            </button>

            <button type="button" @click="issueDeskRemaining()"
                    :disabled="(deskStudentStats?.totalRemaining || 0) === 0"
                    class="px-3 py-1.5 rounded-xl bg-[#8C2826] hover:bg-[#731E1C] text-white font-bold text-xs inline-flex items-center gap-1 shadow-2xs transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
              <span>⚡ Issue All (<span x-text="deskStudentStats?.totalRemaining || 0"></span>)</span>
            </button>
          </div>

        </div>

        {{-- Clean Checklist Items Table / List --}}
        <div class="divide-y divide-slate-100">
          
          {{-- Computed Current Display List --}}
          <template x-for="item in (deskCategoryTab === 'NOTE' ? deskStudentNotes : (deskCategoryTab === 'BOOK' ? deskStudentBooks : (deskCategoryTab === 'UNIFORM' ? deskStudentUniforms : [...deskStudentNotes, ...deskStudentBooks, ...deskStudentUniforms])))" :key="item.id">
            <div class="p-3.5 sm:px-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 transition"
                 :class="item.is_issued ? 'bg-emerald-50/20' : ''">
              
              {{-- Left: Item Type Icon, Title & SKU --}}
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black shrink-0"
                     :class="item.item_type === 'NOTE' ? 'bg-indigo-100 text-indigo-700' : (item.item_type === 'BOOK' ? 'bg-amber-100 text-amber-800' : 'bg-teal-100 text-teal-800')">
                  <span x-text="item.item_type === 'NOTE' ? '📝' : (item.item_type === 'BOOK' ? '📚' : '👔')"></span>
                </div>
                <div class="min-w-0">
                  <div class="font-bold text-xs sm:text-sm text-slate-900 truncate" x-text="item.item_name"></div>
                  <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                    <template x-if="item.sku">
                      <span class="font-mono text-slate-600 bg-slate-100 px-1.5 py-0.2 rounded" x-text="item.sku"></span>
                    </template>
                    <span class="capitalize" x-text="item.item_type === 'NOTE' ? 'Notebook' : (item.item_type === 'BOOK' ? 'Textbook' : 'Uniform')"></span>
                    <span>&bull;</span>
                    <span>Prescribed: <strong class="text-slate-700 font-semibold" x-text="item.quantity + ' Unit' + (item.quantity > 1 ? 's' : '')"></strong></span>
                  </div>
                </div>
              </div>

              {{-- Right: Stepper, Status & One-Click Mark Button --}}
              <div class="flex items-center gap-3 self-end sm:self-center shrink-0">
                
                {{-- Compact Stepper --}}
                <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
                  <button type="button" @click="changeDeskItemQty(item, -1)"
                          :disabled="item.issued_quantity <= 0"
                          class="w-6 h-6 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 text-xs flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                    -
                  </button>
                  <input type="number" min="0" :max="item.quantity"
                         x-model.number="item.issued_quantity"
                         @change="saveDeskItemIssuance(item, item.issued_quantity)"
                         class="w-8 text-center py-0.5 text-xs font-bold text-slate-900 border-0 focus:ring-0">
                  <button type="button" @click="changeDeskItemQty(item, 1)"
                          :disabled="item.issued_quantity >= item.quantity"
                          class="w-6 h-6 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 text-xs flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                    +
                  </button>
                </div>

                {{-- Status Tag --}}
                <div class="w-16 text-center">
                  <template x-if="item.is_issued">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                      <span>✓ Issued</span>
                    </span>
                  </template>
                  <template x-if="!item.is_issued">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                      <span>Pending</span>
                    </span>
                  </template>
                </div>

                {{-- Manual Action Button --}}
                <div>
                  <template x-if="!item.is_issued">
                    <button type="button" @click="saveDeskItemIssuance(item, item.quantity)"
                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      <span>Mark Issued</span>
                    </button>
                  </template>
                  <template x-if="item.is_issued">
                    <button type="button" @click="saveDeskItemIssuance(item, 0)"
                            title="Click to unmark"
                            class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-300 font-bold text-xs inline-flex items-center gap-1 transition cursor-pointer group">
                      <span class="group-hover:hidden text-emerald-700">✓ Done</span>
                      <span class="hidden group-hover:inline">↩ Unmark</span>
                    </button>
                  </template>
                </div>

              </div>

            </div>
          </template>

          {{-- Empty list message if no items in category --}}
          <div x-show="(deskCategoryTab === 'NOTE' ? deskStudentNotes : (deskCategoryTab === 'BOOK' ? deskStudentBooks : (deskCategoryTab === 'UNIFORM' ? deskStudentUniforms : [...deskStudentNotes, ...deskStudentBooks, ...deskStudentUniforms]))).length === 0"
               class="p-8 text-center text-xs font-semibold text-slate-400">
            No items in this category for this standard.
          </div>

        </div>

      </div>

    </div>

  </div>{{-- Close Tab 3: Quick Fee Check & Student Issuance Desk --}}

  {{-- ── Standard Detailed View Modal (When touching a Class Card) ──────────── --}}
  <div x-show="isDetailModalOpen" x-cloak
       class="fixed inset-0 z-50 overflow-y-auto"
       aria-labelledby="modal-title" role="dialog" aria-modal="true">
    
    {{-- Dark Backdrop with Blur --}}
    <div x-show="isDetailModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeDetailModal()"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"></div>

    {{-- Centering wrapper --}}
    <div class="flex min-h-full items-center justify-center p-3 sm:p-6 text-center">
      
      {{-- The Popup Card --}}
      <div x-show="isDetailModalOpen"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 scale-95 translate-y-4"
           x-transition:enter-end="opacity-100 scale-100 translate-y-0"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 scale-100 translate-y-0"
           x-transition:leave-end="opacity-0 scale-95 translate-y-4"
           @click.stop
           class="relative transform rounded-3xl bg-white text-left shadow-2xl border border-slate-200/90 transition-all w-full max-w-5xl lg:max-w-6xl xl:max-w-7xl flex flex-col max-h-[88vh] overflow-hidden my-auto">
        
        {{-- Sticky Header --}}
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-white/95 backdrop-blur-md sticky top-0 z-20 shrink-0 space-y-3">
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center font-black text-sm tracking-tight shrink-0 shadow-2xs"
                   :class="activeCard?.is_senior_secondary ? 'bg-purple-100 text-purple-900 border border-purple-200' : 'bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA]'"
                   x-text="activeCard ? getClassBadge(activeCard.class_name) : ''">
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight"
                      x-text="activeCard ? (activeCard.class_name.toUpperCase().startsWith('CLASS') || activeCard.class_name.toUpperCase().includes('KG') ? activeCard.class_name : 'Class ' + activeCard.class_name) : ''"></h2>
                  <span class="text-xs font-bold text-[#8C2826] bg-[#FFF5F5] px-2.5 py-0.5 rounded-full border border-[#FECACA]" x-text="selectedYearName"></span>
                  <template x-if="activeCard?.is_senior_secondary">
                    <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200">Senior Secondary</span>
                  </template>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-0.5 truncate" x-text="activeCard ? getClassStage(activeCard.class_name, activeCard.is_senior_secondary) + ' • Curriculum & Prescribed Inventory' : ''"></p>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span x-text="(detailStats.totalQty || 0) + ' Units Total'"></span>
              </span>
              <button type="button" @click="openCreateModal(activeCard?.class_id, activeGroupTab)"
                      class="px-3.5 py-1.5 rounded-xl bg-[#8C2826] hover:bg-[#731E1C] text-white font-bold text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">Add Item</span>
              </button>
              <button type="button" @click="closeDetailModal()"
                      title="Close modal"
                      class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 border border-slate-200 flex items-center justify-center font-bold text-lg cursor-pointer transition">
                &times;
              </button>
            </div>
          </div>

          {{-- Senior Secondary Group Tabs --}}
          <template x-if="activeCard?.is_senior_secondary">
            <div class="pt-2.5 pb-1 border-t border-purple-100/90 flex items-center justify-between gap-3 flex-wrap">
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#8C2826] animate-pulse"></span>
                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Select Stream:</span>
              </div>
              <div class="flex items-center gap-2 flex-wrap">
                <template x-for="grp in activeCard.groups" :key="grp.group_id">
                  <button type="button" @click="selectGroupTab(grp.group_id)"
                          :class="String(activeGroupTab) === String(grp.group_id)
                            ? 'bg-[#8C2826] text-white shadow-md ring-2 ring-[#8C2826]/30 font-black border-[#8C2826]' 
                            : 'bg-white text-slate-700 hover:text-slate-900 border border-slate-200/90 hover:bg-slate-50 font-bold shadow-2xs'"
                          :style="String(activeGroupTab) === String(grp.group_id) ? 'background-color: #8C2826 !important; color: #ffffff !important;' : ''"
                          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 cursor-pointer select-none">
                    <span class="text-xs" x-text="grp.group_code === 'BIO' ? '🧬' : (grp.group_code === 'CS' ? '💻' : '📊')"></span>
                    <span x-text="grp.group_name"></span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold tabular-nums"
                          :class="String(activeGroupTab) === String(grp.group_id)
                            ? 'bg-white/25 text-white font-extrabold' 
                            : 'bg-slate-100 text-slate-600'"
                          :style="String(activeGroupTab) === String(grp.group_id) ? 'background-color: rgba(255,255,255,0.25) !important; color: #ffffff !important;' : ''"
                          x-text="grp.grand_total_qty + ' Units'">
                    </span>
                    <template x-if="String(activeGroupTab) === String(grp.group_id)">
                      <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </template>
                  </button>
                </template>
              </div>
            </div>
          </template>

          {{-- Controls Row: Filter Tabs, Search & View toggle --}}
          <div class="pt-2 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2.5">
            {{-- Filter Tabs --}}
            <div class="flex items-center gap-1.5 flex-wrap">
              <button type="button" @click="detailTypeFilter = 'ALL'"
                      :class="detailTypeFilter === 'ALL' ? 'bg-slate-900 text-white shadow-xs font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'"
                      class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                All (<span x-text="detailItems.length"></span>)
              </button>
              <button type="button" @click="detailTypeFilter = 'BOOK'"
                      :class="detailTypeFilter === 'BOOK' ? 'bg-amber-600 text-white shadow-xs font-black' : 'bg-amber-50 text-amber-900 border border-amber-200/80 hover:bg-amber-100 font-bold'"
                      class="px-3 py-1 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                <span>📚 Books</span>
                <span>(<span x-text="detailStats.booksCount"></span>)</span>
              </button>
              <button type="button" @click="detailTypeFilter = 'NOTE'"
                      :class="detailTypeFilter === 'NOTE' ? 'bg-indigo-600 text-white shadow-xs font-black' : 'bg-indigo-50 text-indigo-900 border border-indigo-200/80 hover:bg-indigo-100 font-bold'"
                      class="px-3 py-1 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                <span>📝 Notebooks</span>
                <span>(<span x-text="detailStats.notesCount"></span>)</span>
              </button>
              <button type="button" @click="detailTypeFilter = 'UNIFORM'"
                      :class="detailTypeFilter === 'UNIFORM' ? 'bg-teal-600 text-white shadow-xs font-black' : 'bg-teal-50 text-teal-900 border border-teal-200/80 hover:bg-teal-100 font-bold'"
                      class="px-3 py-1 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                <span>👔 Uniforms</span>
                <span>(<span x-text="activeCard?.uniforms_count || 0"></span>)</span>
              </button>
            </div>

            {{-- Search & View Toggle --}}
            <div class="flex items-center gap-2 flex-1 justify-end max-w-sm">
              <div class="inline-flex p-0.5 rounded-xl bg-slate-100 border border-slate-200 text-xs shrink-0">
                <button type="button" @click="detailViewMode = 'checklist'"
                        :class="detailViewMode === 'checklist' ? 'bg-white text-slate-900 shadow-2xs font-black' : 'text-slate-500 font-medium'"
                        class="px-2 py-0.5 rounded-lg transition cursor-pointer">
                  Cards
                </button>
                <button type="button" @click="detailViewMode = 'table'"
                        :class="detailViewMode === 'table' ? 'bg-white text-slate-900 shadow-2xs font-black' : 'text-slate-500 font-medium'"
                        class="px-2 py-0.5 rounded-lg transition cursor-pointer">
                  Table
                </button>
              </div>

              <div class="flex-1 min-w-[140px]">
                <input type="text" x-model="detailSearchQuery"
                       placeholder="Search title, SKU..."
                       class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 bg-slate-50/70 focus:bg-white focus:border-[#8C2826] focus:ring-[#8C2826] shadow-2xs">
              </div>
            </div>
          </div>
        </div>

        {{-- Scrollable Modal Body --}}
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 min-h-0 bg-slate-50/40">
          
          {{-- Loading in Modal --}}
          <div x-show="detailLoading" class="py-16 text-center text-xs text-slate-400 font-bold space-y-2">
            <div class="w-8 h-8 border-3 border-[#8C2826] border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p>Loading standard curriculum items &amp; SKUs...</p>
          </div>

          {{-- Empty Notice --}}
          <div x-show="!detailLoading && filteredDetailItems.length === 0" class="py-16 text-center rounded-3xl bg-white border border-dashed border-slate-200 space-y-2 shadow-2xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">📚</div>
            <p class="text-sm font-bold text-slate-700">No books or notebooks found for this criteria.</p>
            <p class="text-xs text-slate-400">Click "+ Add Item" to register curriculum items.</p>
          </div>

          {{-- 🎨 VIEW MODE 1: Visual Cards Checklist (3 Columns: Books, Notes & Uniforms) --}}
          <div x-show="!detailLoading && filteredDetailItems.length > 0 && detailViewMode === 'checklist'"
               class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            {{-- Books Column --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden flex flex-col"
                 x-show="detailTypeFilter === 'ALL' || detailTypeFilter === 'BOOK'">
              <div class="p-3.5 px-4 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-b border-amber-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black shadow-xs text-xs">
                    📚
                  </div>
                  <div>
                    <h4 class="text-xs font-black text-amber-950 uppercase tracking-wider">Prescribed Textbooks</h4>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100/90 text-amber-900 border border-amber-200 tabular-nums"
                        x-text="detailBooksList.length + ' Titles'"></span>
                  <button type="button" @click="openCreateModal(activeCard?.class_id, activeGroupTab, 'BOOK')"
                          title="Add Textbook"
                          class="px-2 py-0.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] flex items-center gap-1 transition cursor-pointer">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add</span>
                  </button>
                </div>
              </div>

              <div class="p-3.5 space-y-2.5 divide-y divide-slate-100 flex-1 overflow-y-auto max-h-[500px]">
                <template x-for="item in detailBooksList" :key="item.id">
                  <div class="pt-2.5 first:pt-0 group flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors">
                    <div class="flex items-start gap-2.5 min-w-0 flex-1">
                      <button type="button" @click="toggleItemStatus(item)"
                              title="Toggle active status"
                              class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 shadow-2xs font-bold text-xs transition cursor-pointer mt-0.5"
                              :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-300' : 'bg-slate-100 text-slate-400 border border-slate-200'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                      </button>
                      
                      <div class="min-w-0 flex-1">
                        <h5 class="text-xs font-bold text-slate-900 group-hover:text-[#8C2826] transition-colors leading-snug break-words" x-text="item.item_name"></h5>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                          <span class="font-mono text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200/80 tracking-tight" x-text="item.sku || 'N/A'"></span>
                          <template x-if="item.group">
                            <span class="text-[9px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded border border-purple-200" x-text="item.group.name"></span>
                          </template>
                          <span class="text-[10px] font-medium" :class="item.status === 'active' ? 'text-emerald-600 font-semibold' : 'text-slate-400'">
                            • <span x-text="item.status === 'active' ? 'Active' : 'Inactive'"></span>
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="shrink-0 flex items-center gap-2">
                      <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-amber-50 text-amber-900 border border-amber-200/90 tabular-nums">
                        <span class="text-[10px] font-bold text-amber-600 mr-1">Qty:</span>
                        <span x-text="item.quantity"></span>
                      </span>

                      <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                        <button type="button" @click="openEditModal(item)"
                                title="Edit item"
                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-[#8C2826] hover:text-white text-slate-600 flex items-center justify-center transition cursor-pointer">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" @click="deleteItem(item)"
                                title="Delete item"
                                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition cursor-pointer">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
                <div x-show="detailBooksList.length === 0" class="py-6 text-center text-xs text-slate-400">
                  No textbooks prescribed.
                </div>
              </div>
            </div>

            {{-- Notebooks Column --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden flex flex-col"
                 x-show="detailTypeFilter === 'ALL' || detailTypeFilter === 'NOTE'">
              <div class="p-3.5 px-4 bg-gradient-to-r from-indigo-500/10 via-indigo-500/5 to-transparent border-b border-indigo-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black shadow-xs text-xs">
                    📝
                  </div>
                  <div>
                    <h4 class="text-xs font-black text-indigo-950 uppercase tracking-wider">Notebooks &amp; Workbooks</h4>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-indigo-100/90 text-indigo-900 border border-indigo-200 tabular-nums"
                        x-text="detailNotesList.length + ' Items'"></span>
                  <button type="button" @click="openCreateModal(activeCard?.class_id, activeGroupTab, 'NOTE')"
                          title="Add Notebook"
                          class="px-2 py-0.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] flex items-center gap-1 transition cursor-pointer">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add</span>
                  </button>
                </div>
              </div>

              <div class="p-3.5 space-y-2.5 divide-y divide-slate-100 flex-1 overflow-y-auto max-h-[500px]">
                <template x-for="item in detailNotesList" :key="item.id">
                  <div class="pt-2.5 first:pt-0 group flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors">
                    <div class="flex items-start gap-2.5 min-w-0 flex-1">
                      <button type="button" @click="toggleItemStatus(item)"
                              title="Toggle active status"
                              class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 shadow-2xs font-bold text-xs transition cursor-pointer mt-0.5"
                              :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-300' : 'bg-slate-100 text-slate-400 border border-slate-200'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                      </button>

                      <div class="min-w-0 flex-1">
                        <h5 class="text-xs font-bold text-slate-900 group-hover:text-indigo-900 transition-colors leading-snug break-words" x-text="item.item_name"></h5>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                          <span class="font-mono text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200/80 tracking-tight" x-text="item.sku || 'N/A'"></span>
                          <template x-if="item.group">
                            <span class="text-[9px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded border border-purple-200" x-text="item.group.name"></span>
                          </template>
                          <span class="text-[10px] font-medium" :class="item.status === 'active' ? 'text-emerald-600 font-semibold' : 'text-slate-400'">
                            • <span x-text="item.status === 'active' ? 'Active' : 'Inactive'"></span>
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="shrink-0 flex items-center gap-2">
                      <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-indigo-50 text-indigo-900 border border-indigo-200/90 tabular-nums">
                        <span class="text-[10px] font-bold text-indigo-600 mr-1">Qty:</span>
                        <span x-text="item.quantity"></span>
                      </span>

                      <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                        <button type="button" @click="openEditModal(item)"
                                title="Edit item"
                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-[#8C2826] hover:text-white text-slate-600 flex items-center justify-center transition cursor-pointer">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" @click="deleteItem(item)"
                                title="Delete item"
                                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition cursor-pointer">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
                <div x-show="detailNotesList.length === 0" class="py-6 text-center text-xs text-slate-400">
                  No notebooks prescribed.
                </div>
              </div>
            </div>

            {{-- Uniforms Column in Modal (Live Uniform CRUD & Rules) --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden flex flex-col"
                 x-show="detailTypeFilter === 'ALL' || detailTypeFilter === 'UNIFORM'">
              <div class="p-3.5 px-4 bg-gradient-to-r from-teal-500/10 via-teal-500/5 to-transparent border-b border-teal-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-xl bg-teal-600 text-white flex items-center justify-center font-black shadow-xs text-xs">
                    👔
                  </div>
                  <div>
                    <h4 class="text-xs font-black text-teal-950 uppercase tracking-wider">Prescribed School Uniform</h4>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-teal-100/90 text-teal-900 border border-teal-200 tabular-nums"
                        x-text="detailUniformsList.length + ' Items'"></span>
                  <button type="button" @click="openCreateModal(activeCard?.class_id, activeGroupTab, 'UNIFORM', 'ALL')"
                          title="Add Uniform Item"
                          class="px-2 py-0.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-[10px] flex items-center gap-1 transition cursor-pointer">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add</span>
                  </button>
                </div>
              </div>

              {{-- Uniform Item Cards with Live CRUD Actions --}}
              <div class="p-3.5 space-y-2.5 divide-y divide-slate-100 flex-1 overflow-y-auto max-h-[500px]">
                <template x-for="item in detailUniformsList" :key="item.id">
                  <div class="pt-2.5 first:pt-0 group flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors">
                    <div class="flex items-start gap-2.5 min-w-0 flex-1">
                      <button type="button" @click="toggleItemStatus(item)"
                              title="Toggle active status"
                              class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 shadow-2xs font-bold text-xs transition cursor-pointer mt-0.5"
                              :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-300' : 'bg-slate-100 text-slate-400 border border-slate-200'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                      </button>

                      <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                          <h5 class="text-xs font-bold text-slate-900 group-hover:text-teal-900 transition-colors leading-snug break-words" x-text="item.item_name"></h5>
                          <template x-if="item.gender">
                            <span class="text-[9px] font-black px-1.5 py-0.2 rounded-md border"
                                  :class="item.gender === 'BOYS' ? 'bg-sky-50 text-sky-800 border-sky-200' : (item.gender === 'GIRLS' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-teal-50 text-teal-800 border-teal-200')"
                                  x-text="item.gender === 'BOYS' ? '👦 Boys' : (item.gender === 'GIRLS' ? '👧 Girls' : '🚻 Unisex')">
                            </span>
                          </template>
                        </div>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                          <span class="font-mono text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200/80 tracking-tight" x-text="item.sku || 'N/A'"></span>
                          <span class="text-[10px] font-medium" :class="item.status === 'active' ? 'text-emerald-600 font-semibold' : 'text-slate-400'">
                            • <span x-text="item.status === 'active' ? 'Active' : 'Inactive'"></span>
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="shrink-0 flex items-center gap-2">
                      <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-teal-50 text-teal-950 border border-teal-200/90 tabular-nums">
                        <span class="text-[10px] font-bold text-teal-600 mr-1">Qty:</span>
                        <span x-text="item.quantity"></span>
                      </span>

                      <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                        <button type="button" @click="openEditModal(item)"
                                title="Edit uniform item"
                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-[#8C2826] hover:text-white text-slate-600 flex items-center justify-center transition cursor-pointer">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" @click="deleteItem(item)"
                                title="Delete uniform item"
                                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition cursor-pointer">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>

                <div x-show="detailUniformsList.length === 0" class="py-6 text-center text-xs text-slate-400 space-y-2">
                  <p>No uniform items found.</p>
                  <button type="button" @click="openCreateModal(activeCard?.class_id, activeGroupTab, 'UNIFORM', 'ALL')"
                          class="px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold border border-teal-200 transition cursor-pointer">
                    + Add Uniform Item
                  </button>
                </div>
              </div>

              {{-- Statutory Rules Reference Strip --}}
              <div class="mt-auto p-3 bg-teal-50/40 border-t border-teal-100 text-[11px] text-teal-900">
                <div class="flex items-center justify-between font-black text-[10px] uppercase tracking-wider text-teal-800 mb-1">
                  <span>Prescribed Statutory Rule</span>
                  <span x-text="(activeCard?.uniforms_boys_qty || 0) + 'B / ' + (activeCard?.uniforms_girls_qty || 0) + 'G Units'"></span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-600 font-medium">
                  <div class="bg-white/80 p-2 rounded-lg border border-teal-100">
                    <span class="font-bold text-sky-800 block">👦 Boys</span>
                    <span class="text-slate-600" x-text="activeCard?.numeric_value <= 0 ? 'Green T-Shirt (2), White T-Shirt (1), Shorts (3)' : (activeCard?.numeric_value <= 3 ? 'White T-Shirt (2), Shorts (3), White Shirt (1), Sports T-Shirt (1)' : 'White T-Shirt (2), White Shirt (1), Pant (3), Sports T-Shirt (1)')"></span>
                  </div>
                  <div class="bg-white/80 p-2 rounded-lg border border-teal-100">
                    <span class="font-bold text-rose-800 block">👧 Girls</span>
                    <span class="text-slate-600" x-text="activeCard?.numeric_value <= 0 ? 'Green T-Shirt (2), White T-Shirt (1), Skirt (3)' : 'White T-Shirt (2), Skirt (3), White Shirt (1), Sports T-Shirt (1)'"></span>
                  </div>
                </div>
              </div>

            </div>

          </div>

          {{-- 📋 VIEW MODE 2: Inventory Table Grid --}}
          <div x-show="!detailLoading && filteredDetailItems.length > 0 && detailViewMode === 'table'"
               class="rounded-2xl border border-slate-200/90 overflow-hidden shadow-2xs bg-white">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-wider">
                    <th class="py-3 px-4 w-12 text-center">#</th>
                    <th class="py-3 px-4 w-40">SKU Code</th>
                    <th class="py-3 px-3 w-20">Type</th>
                    <th class="py-3 px-4">Item Name / Title</th>
                    <th class="py-3 px-4 text-center w-24">Quantity</th>
                    <th class="py-3 px-4 text-center w-24">Status</th>
                    <th class="py-3 px-4 text-right w-24">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <template x-for="(item, idx) in filteredDetailItems" :key="item.id">
                    <tr class="hover:bg-slate-50/80 transition-colors">
                      <td class="py-2.5 px-4 text-center font-bold text-slate-400 tabular-nums" x-text="idx + 1"></td>
                      <td class="py-2.5 px-4 font-mono font-bold">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-black border"
                              :class="item.item_type === 'BOOK' ? 'bg-amber-50/70 text-amber-900 border-amber-200/80' : (item.item_type === 'NOTE' ? 'bg-indigo-50/70 text-indigo-900 border-indigo-200/80' : 'bg-teal-50/70 text-teal-900 border-teal-200/80')"
                              x-text="item.sku || 'N/A'"></span>
                      </td>
                      <td class="py-2.5 px-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                              :class="item.item_type === 'BOOK' ? 'bg-amber-100 text-amber-800' : (item.item_type === 'NOTE' ? 'bg-indigo-100 text-indigo-800' : 'bg-teal-100 text-teal-800')"
                              x-text="item.item_type"></span>
                      </td>
                      <td class="py-2.5 px-4 font-bold text-slate-900">
                        <div class="flex items-center gap-1.5 flex-wrap">
                          <span class="break-words" x-text="item.item_name"></span>
                          <template x-if="item.gender">
                            <span class="text-[9px] font-black px-1.5 py-0.2 rounded border"
                                  :class="item.gender === 'BOYS' ? 'bg-sky-50 text-sky-800 border-sky-200' : (item.gender === 'GIRLS' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-teal-50 text-teal-800 border-teal-200')"
                                  x-text="item.gender === 'BOYS' ? '👦 Boys' : (item.gender === 'GIRLS' ? '👧 Girls' : '🚻 Unisex')">
                            </span>
                          </template>
                        </div>
                        <template x-if="item.group">
                          <span class="text-[10px] font-semibold text-purple-700 block mt-0.5" x-text="'Stream: ' + item.group.name"></span>
                        </template>
                      </td>
                      <td class="py-2.5 px-4 text-center font-black tabular-nums text-slate-800" x-text="item.quantity + ' Units'"></td>
                      <td class="py-2.5 px-4 text-center">
                        <button type="button" @click="toggleItemStatus(item)"
                                :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border transition cursor-pointer">
                          <span class="w-1.5 h-1.5 rounded-full" :class="item.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                          <span x-text="item.status === 'active' ? 'Active' : 'Inactive'"></span>
                        </button>
                      </td>
                      <td class="py-2.5 px-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                          <button type="button" @click="openEditModal(item)" title="Edit"
                                  class="w-7 h-7 rounded-lg bg-slate-50 hover:bg-slate-200 text-slate-600 border border-slate-200 flex items-center justify-center transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                          </button>
                          <button type="button" @click="deleteItem(item)" title="Delete"
                                  class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        {{-- Sticky Modal Footer --}}
        <div class="p-3.5 px-6 bg-white border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 shrink-0 sticky bottom-0 z-20">
          <div>
            Showing <strong class="text-slate-800 font-extrabold" x-text="filteredDetailItems.length"></strong> prescribed items for this standard
          </div>
          <button type="button" @click="closeDetailModal()" class="px-5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold transition cursor-pointer shadow-2xs">
            Close
          </button>
        </div>

      </div>
    </div>
  </div>

  {{-- ── Add / Edit Book & Note Modal (CRUD) ────────────────────────────────── --}}
  <div x-show="isFormModalOpen" x-cloak
       class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
    
    <div x-show="isFormModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeFormModal()"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center">
      <div x-show="isFormModalOpen"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 scale-95 translate-y-4"
           x-transition:enter-end="opacity-100 scale-100 translate-y-0"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 scale-100 translate-y-0"
           x-transition:leave-end="opacity-0 scale-95 translate-y-4"
           @click.stop
           class="relative transform rounded-3xl bg-white text-left shadow-2xl border border-slate-200/90 transition-all w-full max-w-lg my-auto overflow-hidden">
      
      {{-- Modal Title --}}
      <div class="p-6 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA] flex items-center justify-center font-black">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
          </div>
          <div>
            <h3 class="text-base font-extrabold text-slate-900 tracking-tight" x-text="formMode === 'create' ? 'Add Item (Book / Note / Uniform)' : 'Edit Item'"></h3>
            <p class="text-xs text-slate-500 font-medium">Standard-wise checklist requirement &amp; uniform configuration</p>
          </div>
        </div>
        <button type="button" @click="closeFormModal()" class="w-8 h-8 rounded-xl bg-white hover:bg-slate-200 text-slate-500 hover:text-slate-800 border border-slate-200 flex items-center justify-center font-bold cursor-pointer transition">
          &times;
        </button>
      </div>

      {{-- Form Body --}}
      <form @submit.prevent="submitForm()" class="p-6 space-y-4 text-xs">
        
        {{-- Form Validation Error Banner --}}
        <div x-show="formError" class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold" x-text="formError"></div>

        {{-- Academic Year (Disabled / Pre-selected) --}}
        <div>
          <label class="block font-bold text-slate-700 mb-1">Academic Year</label>
          <select x-model="form.academic_year_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-slate-50">
            @foreach($academicYears as $year)
              <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_current ? '— (Current Active)' : '' }}</option>
            @endforeach
          </select>
        </div>

        {{-- Class / Standard Selector --}}
        <div>
          <label class="block font-bold text-slate-700 mb-1">Class / Standard <span class="text-rose-500">*</span></label>
          <select x-model="form.class_id" @change="onFormClassChange()" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white focus:border-[#8C2826] focus:ring-[#8C2826]">
            <option value="">-- Select Class --</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}">Class {{ $c->name }} ({{ $c->display_name }})</option>
            @endforeach
          </select>
        </div>

        {{-- Academic Group (Only for XI / XII) --}}
        <div x-show="isFormSeniorSecondary" class="p-3.5 rounded-2xl bg-purple-50/60 border border-purple-200 space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block font-black text-purple-950">Senior Secondary Group <span class="text-rose-500">*</span></label>
            <span class="text-[10px] font-bold text-purple-700">Required for Grade XI &amp; XII</span>
          </div>
          <select x-model="form.group_id" :required="isFormSeniorSecondary" class="w-full px-3 py-2 rounded-xl border border-purple-300 text-xs font-bold text-purple-950 bg-white focus:border-purple-600 focus:ring-purple-600">
            <option value="">-- Select Academic Group --</option>
            @foreach($groups as $grp)
              <option value="{{ $grp->id }}">{{ $grp->name }}</option>
            @endforeach
          </select>
          <p class="text-[10px] text-purple-800">Prescribed checklist will be isolated strictly to students of this selected stream group.</p>
        </div>

        {{-- Item Type Toggle --}}
        <div>
          <label class="block font-bold text-slate-700 mb-1.5">Item Type <span class="text-rose-500">*</span></label>
          <div class="grid grid-cols-3 gap-2 sm:gap-2.5">
            <label class="p-2.5 rounded-xl border flex items-center justify-center gap-1.5 cursor-pointer font-extrabold text-[11px] sm:text-xs transition text-center"
                   :class="form.item_type === 'BOOK' ? 'border-[#8C2826] bg-[#FFF5F5] text-[#8C2826] ring-2 ring-[#8C2826]/20' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'">
              <input type="radio" x-model="form.item_type" value="BOOK" class="sr-only">
              <span>📚</span>
              <span>TEXTBOOK</span>
            </label>
            <label class="p-2.5 rounded-xl border flex items-center justify-center gap-1.5 cursor-pointer font-extrabold text-[11px] sm:text-xs transition text-center"
                   :class="form.item_type === 'NOTE' ? 'border-indigo-600 bg-indigo-50 text-indigo-800 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'">
              <input type="radio" x-model="form.item_type" value="NOTE" class="sr-only">
              <span>📝</span>
              <span>NOTEBOOK</span>
            </label>
            <label class="p-2.5 rounded-xl border flex items-center justify-center gap-1.5 cursor-pointer font-extrabold text-[11px] sm:text-xs transition text-center"
                   :class="form.item_type === 'UNIFORM' ? 'border-teal-600 bg-teal-50 text-teal-800 ring-2 ring-teal-500/20' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'">
              <input type="radio" x-model="form.item_type" value="UNIFORM" class="sr-only">
              <span>👔</span>
              <span>UNIFORM</span>
            </label>
          </div>
        </div>

        {{-- Applicable Gender Selector (When Uniform is selected) --}}
        <div x-show="form.item_type === 'UNIFORM'" x-transition class="p-3 rounded-2xl bg-teal-50/70 border border-teal-200 space-y-2">
          <div class="flex items-center justify-between">
            <label class="block font-black text-teal-950 text-xs">Applicable Gender <span class="text-rose-500">*</span></label>
            <span class="text-[10px] font-bold text-teal-700">School Uniform Allocation Rule</span>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <label class="p-2 rounded-xl border flex items-center justify-center gap-1.5 cursor-pointer font-bold text-xs transition"
                   :class="form.gender === 'BOYS' ? 'border-sky-500 bg-sky-100 text-sky-950 font-black ring-1 ring-sky-400' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
              <input type="radio" x-model="form.gender" value="BOYS" class="sr-only">
              <span>👦</span>
              <span>Boys Only</span>
            </label>
            <label class="p-2 rounded-xl border flex items-center justify-center gap-1.5 cursor-pointer font-bold text-xs transition"
                   :class="form.gender === 'GIRLS' ? 'border-rose-500 bg-rose-100 text-rose-950 font-black ring-1 ring-rose-400' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
              <input type="radio" x-model="form.gender" value="GIRLS" class="sr-only">
              <span>👧</span>
              <span>Girls Only</span>
            </label>
            <label class="p-2 rounded-xl border flex items-center justify-center gap-1.5 cursor-pointer font-bold text-xs transition"
                   :class="form.gender === 'ALL' || !form.gender ? 'border-teal-500 bg-teal-100 text-teal-950 font-black ring-1 ring-teal-400' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
              <input type="radio" x-model="form.gender" value="ALL" class="sr-only">
              <span>🚻</span>
              <span>Both / Unisex</span>
            </label>
          </div>
        </div>

        {{-- SKU & Item Name Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="sm:col-span-1">
            <label class="block font-bold text-slate-700 mb-1">SKU / Code</label>
            <input type="text" x-model="form.sku"
                   :placeholder="form.item_type === 'UNIFORM' ? 'e.g. EPS-VI-B-UNF-001' : (form.item_type === 'NOTE' ? 'e.g. EPS-VI-NT-001' : 'e.g. EPS-VI-BK-001')"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 font-mono text-xs font-bold text-slate-800 bg-slate-50/70 focus:border-[#8C2826] focus:ring-[#8C2826]">
            <span class="text-[10px] text-slate-400">Auto-filled if blank</span>
          </div>

          <div class="sm:col-span-2">
            <label class="block font-bold text-slate-700 mb-1">Item Title / Name <span class="text-rose-500">*</span></label>
            <input type="text" x-model="form.item_name" required
                   :placeholder="form.item_type === 'UNIFORM' ? 'e.g. Green T-Shirt / Skirt / Pant' : (form.item_type === 'NOTE' ? 'e.g. 192 Pages Four Line Note' : 'e.g. NCERT English - Poorvi')"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-white focus:border-[#8C2826] focus:ring-[#8C2826]">
          </div>
        </div>

        {{-- Quantity, Order & Status --}}
        <div class="grid grid-cols-3 gap-3 pt-1">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Quantity <span class="text-rose-500">*</span></label>
            <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden">
              <button type="button" @click="form.quantity = Math.max(1, Number(form.quantity||1) - 1)" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700">-</button>
              <input type="number" min="1" x-model.number="form.quantity" required
                     class="w-full text-center py-1.5 text-xs font-extrabold text-slate-800 border-0 focus:ring-0">
              <button type="button" @click="form.quantity = Number(form.quantity||1) + 1" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700">+</button>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Display Order</label>
            <input type="number" min="0" x-model.number="form.display_order"
                   class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 text-center">
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Status</label>
            <select x-model="form.status" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        {{-- Modal Actions --}}
        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" @click="closeFormModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition cursor-pointer">
            Cancel
          </button>
          <button type="submit" :disabled="formSubmitting"
                  class="px-5 py-2 rounded-xl bg-[#8C2826] hover:bg-[#731E1C] text-white font-black shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
            <span x-show="formSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span x-text="formMode === 'create' ? 'Save Item' : 'Update Item'"></span>
          </button>
        </div>

      </form>
      </div>
    </div>
  </div>

  {{-- ── Student Distribution & Issuance Modal ──────────────────────────────── --}}
  <div x-show="isStudentModalOpen" x-cloak
       class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    
    <div x-show="isStudentModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeStudentModal()"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-5 text-center">
      <div x-show="isStudentModalOpen"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 scale-95 translate-y-4"
           x-transition:enter-end="opacity-100 scale-100 translate-y-0"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 scale-100 translate-y-0"
           x-transition:leave-end="opacity-0 scale-95 translate-y-4"
           @click.stop
           class="relative transform rounded-3xl bg-white text-left shadow-2xl border border-slate-200/90 transition-all w-full max-w-4xl my-auto overflow-hidden flex flex-col max-h-[88vh]">
      
      {{-- Modal Header --}}
      <div class="p-6 border-b border-slate-100 bg-slate-50/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-base shrink-0 shadow-xs"
               :class="(activeStudentStats?.totalRemaining || 0) === 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-900 border border-amber-200'"
               x-text="(activeStudentData?.name || 'ST').slice(0, 2).toUpperCase()">
          </div>
          <div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <h2 class="text-xl font-black text-slate-900 tracking-tight" x-text="activeStudentData?.name || 'Student Checklist'"></h2>
              <span class="font-mono text-xs font-bold text-slate-600 bg-white px-2.5 py-0.5 rounded-full border border-slate-200" x-text="activeStudentData?.admission_no"></span>
              <span class="text-xs font-bold text-[#8C2826] bg-[#FFF5F5] px-2.5 py-0.5 rounded-full border border-[#FECACA]" x-text="activeStudentData?.class_name + (activeStudentData?.section_name ? ' — ' + activeStudentData?.section_name : '')"></span>
              <template x-if="activeStudentData?.gender">
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border"
                      :class="(activeStudentData?.gender || '').toLowerCase() === 'male' || activeStudentData?.gender === 'BOYS' ? 'bg-sky-50 text-sky-800 border-sky-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
                      x-text="(activeStudentData?.gender || '').toLowerCase() === 'male' || activeStudentData?.gender === 'BOYS' ? '♂ Boy' : '♀ Girl'"></span>
              </template>
              <span class="text-xs font-black px-2.5 py-0.5 rounded-full border shadow-2xs"
                    :class="activeStudentData?.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : (activeStudentData?.payment_status === 'partially_paid' || activeStudentData?.payment_status === 'partial' ? 'bg-indigo-100 text-indigo-800 border-indigo-300' : 'bg-amber-100 text-amber-900 border-amber-300')"
                    x-text="'Payment: ' + (activeStudentData?.payment_status_label || (activeStudentData?.payment_status === 'paid' ? 'Paid' : 'Pending Payment'))"></span>
              <span class="text-xs font-black px-2.5 py-0.5 rounded-full border shadow-2xs"
                    :class="(activeStudentStats?.totalRemaining || 0) === 0 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ((activeStudentStats?.totalIssued || 0) > 0 ? 'bg-sky-100 text-sky-800 border-sky-300' : 'bg-amber-100 text-amber-900 border-amber-300')"
                    x-text="(activeStudentStats?.totalRemaining || 0) === 0 ? 'Distributed' : ((activeStudentStats?.totalIssued || 0) > 0 ? 'Partially Distributed' : 'Pending Distribution')"></span>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Physical distribution checklist: mark received books, notebooks, and uniforms or record partial handovers</p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
          {{-- Fast Action: Issue All --}}
          <button type="button" @click="issueAllRemaining(activeStudentData?.id)"
                  :disabled="(activeStudentStats?.totalRemaining || 0) === 0"
                  class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            <span>⚡ Issue All Remaining</span>
          </button>
          
          {{-- Print Slip --}}
          <button type="button" @click="openPrintSlip(activeStudentData?.id)"
                  class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200 flex items-center gap-1.5 transition cursor-pointer">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Slip</span>
          </button>

          <button type="button" @click="closeStudentModal()" class="w-9 h-9 rounded-xl bg-white hover:bg-slate-200 text-slate-500 hover:text-slate-800 border border-slate-200 flex items-center justify-center font-bold text-lg cursor-pointer transition">
            &times;
          </button>
        </div>
      </div>

      {{-- Fee Verification Banner inside Modal --}}
      <template x-if="activeStudentData?.payment_status === 'paid' || activeStudentData?.is_fee_cleared">
        <div class="px-6 py-2.5 bg-emerald-50 border-b border-emerald-200 flex items-center justify-between text-xs font-bold text-emerald-900 flex-wrap gap-2">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-black">✓</span>
            <span>Fee Status: <strong>Fully Paid</strong> (<span x-text="formatCurrency(activeStudentData?.admission_paid_amount || activeStudentData?.total_admission_fee)"></span>) — Authorized for Book &amp; Uniform Delivery</span>
          </div>
          <span class="text-[11px] font-extrabold text-emerald-700 bg-white/80 px-2 py-0.5 rounded border border-emerald-300" x-text="'Mode: ' + (activeStudentData?.payment_mode || 'Cash')"></span>
        </div>
      </template>
      <template x-if="activeStudentData?.payment_status === 'partially_paid' || activeStudentData?.payment_status === 'partial'">
        <div class="px-6 py-2.5 bg-amber-50 border-b border-amber-200 flex items-center justify-between text-xs font-bold text-amber-900 flex-wrap gap-2">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-black">⚠️</span>
            <span>Fee Status: <strong>Partially Paid</strong> — Paid: <span x-text="formatCurrency(activeStudentData?.admission_paid_amount)"></span> • <strong class="text-amber-950">Pending Due: <span x-text="formatCurrency(activeStudentData?.admission_pending_amount)"></span></strong></span>
          </div>
          <span class="text-[11px] font-extrabold text-amber-800 bg-white/80 px-2 py-0.5 rounded border border-amber-300">Partial Balance</span>
        </div>
      </template>
      <template x-if="activeStudentData?.payment_status === 'pending' && !activeStudentData?.is_fee_cleared">
        <div class="px-6 py-2.5 bg-rose-50 border-b border-rose-200 flex items-center justify-between text-xs font-bold text-rose-900 flex-wrap gap-2">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-[10px] font-black">🛑</span>
            <span>Fee Status: <strong>Unpaid / Pending</strong> — Clearance Required (Total Due: <span x-text="formatCurrency(activeStudentData?.total_admission_fee)"></span>)</span>
          </div>
          <span class="text-[11px] font-extrabold text-rose-800 bg-white/80 px-2 py-0.5 rounded border border-rose-300">Fees Due</span>
        </div>
      </template>

      {{-- Modal KPI Ribbon --}}
      <div class="px-6 py-3 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between flex-wrap gap-4 text-xs font-bold">
        <div class="flex items-center gap-3">
          <span class="px-3 py-1 rounded-xl bg-slate-200/70 text-slate-800">
            Prescribed: <strong x-text="activeStudentStats?.totalPrescribed || 0"></strong> Units
          </span>
          <span class="px-3 py-1 rounded-xl bg-emerald-100 text-emerald-900 border border-emerald-200">
            Delivered: <strong x-text="activeStudentStats?.totalIssued || 0"></strong> Units
          </span>
          <span class="px-3 py-1 rounded-xl"
                :class="(activeStudentStats?.totalRemaining || 0) === 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-900 border border-rose-300'">
            Pending: <strong x-text="activeStudentStats?.totalRemaining || 0"></strong> Units
          </span>
        </div>

        <template x-if="(activeStudentStats?.totalRemaining || 0) === 0">
          <span class="text-xs font-black text-emerald-700 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Student has received all prescribed books, notes &amp; uniforms!</span>
          </span>
        </template>
      </div>

      {{-- Modal Body: 3-Column Checklist with Live Handover Steppers --}}
      <div class="p-6 overflow-y-auto flex-1 space-y-6">
        
        {{-- Loading Spinner --}}
        <div x-show="studentModalLoading" class="py-16 text-center space-y-3">
          <div class="w-8 h-8 border-3 border-[#8C2826] border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p class="text-xs font-bold text-slate-500">Retrieving student checklist...</p>
        </div>

        <div x-show="!studentModalLoading" class="grid grid-cols-1 md:grid-cols-3 gap-5">

          {{-- Column 1: Textbooks --}}
          <div class="space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-amber-200">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs font-bold">📚</span>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Prescribed Textbooks</h3>
              </div>
              <span class="text-xs font-black text-amber-900" x-text="activeStudentBooks.length + ' Titles'"></span>
            </div>

            <div class="space-y-2.5">
              <template x-for="item in activeStudentBooks" :key="item.id">
                <div class="p-3.5 rounded-2xl border transition-all"
                     :class="item.is_issued ? 'bg-emerald-50/40 border-emerald-200' : 'bg-white border-slate-200 hover:border-amber-300 shadow-2xs'">
                  
                  <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                      <div class="font-extrabold text-xs text-slate-900 leading-snug" x-text="item.item_name"></div>
                      <div class="flex items-center gap-2">
                        <template x-if="item.sku">
                          <span class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded" x-text="item.sku"></span>
                        </template>
                        <span class="text-[11px] text-slate-500 font-medium">Req: <strong class="text-slate-800" x-text="item.quantity"></strong></span>
                      </div>
                    </div>

                    {{-- Status Badge --}}
                    <div>
                      <template x-if="item.is_issued">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">✓ Received</span>
                      </template>
                      <template x-if="!item.is_issued">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-200"
                              x-text="'Due: ' + (item.quantity - item.issued_quantity)"></span>
                      </template>
                    </div>
                  </div>

                  {{-- Stepper & Live Issuance Control --}}
                  <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <span class="text-[11px] font-bold text-slate-500">Issued:</span>
                      <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs">
                        <button type="button" @click="changeItemQty(item, -1)"
                                :disabled="item.issued_quantity <= 0"
                                class="w-7 h-7 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                          -
                        </button>
                        <input type="number" min="0" :max="item.quantity"
                               x-model.number="item.issued_quantity"
                               @change="saveItemIssuance(item, item.issued_quantity)"
                               class="w-10 text-center py-1 text-xs font-black text-slate-900 border-0 focus:ring-0">
                        <button type="button" @click="changeItemQty(item, 1)"
                                :disabled="item.issued_quantity >= item.quantity"
                                class="w-7 h-7 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                          +
                        </button>
                      </div>
                      <span class="text-[10px] text-slate-400 font-medium">/ <span x-text="item.quantity"></span></span>
                    </div>

                    <button type="button" @click="markItemFullyIssued(item)"
                            x-show="!item.is_issued"
                            class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-[10px] font-black transition cursor-pointer">
                      Mark Full (All <span x-text="item.quantity"></span>)
                    </button>
                  </div>

                </div>
              </template>
            </div>
          </div>

          {{-- Column 2: Notebooks --}}
          <div class="space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-indigo-200">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">📝</span>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Prescribed Notebooks</h3>
              </div>
              <span class="text-xs font-black text-indigo-900" x-text="activeStudentNotes.length + ' Types'"></span>
            </div>

            <div class="space-y-2.5">
              <template x-for="item in activeStudentNotes" :key="item.id">
                <div class="p-3.5 rounded-2xl border transition-all"
                     :class="item.is_issued ? 'bg-emerald-50/40 border-emerald-200' : 'bg-white border-slate-200 hover:border-indigo-300 shadow-2xs'">
                  
                  <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                      <div class="font-extrabold text-xs text-slate-900 leading-snug" x-text="item.item_name"></div>
                      <div class="flex items-center gap-2">
                        <template x-if="item.sku">
                          <span class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded" x-text="item.sku"></span>
                        </template>
                        <span class="text-[11px] text-slate-500 font-medium">Req: <strong class="text-slate-800" x-text="item.quantity"></strong></span>
                      </div>
                    </div>

                    {{-- Status Badge --}}
                    <div>
                      <template x-if="item.is_issued">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">✓ Received</span>
                      </template>
                      <template x-if="!item.is_issued">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-900 border border-indigo-200"
                              x-text="'Due: ' + (item.quantity - item.issued_quantity)"></span>
                      </template>
                    </div>
                  </div>

                  {{-- Stepper & Live Issuance Control --}}
                  <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <span class="text-[11px] font-bold text-slate-500">Issued:</span>
                      <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs">
                        <button type="button" @click="changeItemQty(item, -1)"
                                :disabled="item.issued_quantity <= 0"
                                class="w-7 h-7 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                          -
                        </button>
                        <input type="number" min="0" :max="item.quantity"
                               x-model.number="item.issued_quantity"
                               @change="saveItemIssuance(item, item.issued_quantity)"
                               class="w-10 text-center py-1 text-xs font-black text-slate-900 border-0 focus:ring-0">
                        <button type="button" @click="changeItemQty(item, 1)"
                                :disabled="item.issued_quantity >= item.quantity"
                                class="w-7 h-7 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                          +
                        </button>
                      </div>
                      <span class="text-[10px] text-slate-400 font-medium">/ <span x-text="item.quantity"></span></span>
                    </div>

                    <button type="button" @click="markItemFullyIssued(item)"
                            x-show="!item.is_issued"
                            class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 text-[10px] font-black transition cursor-pointer">
                      Mark Full (All <span x-text="item.quantity"></span>)
                    </button>
                  </div>

                </div>
              </template>
            </div>
          </div>

          {{-- Column 3: Prescribed Uniforms --}}
          <div class="space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-teal-200">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-teal-600 text-white flex items-center justify-center text-xs font-bold">👔</span>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Prescribed Uniforms</h3>
              </div>
              <span class="text-xs font-black text-teal-900" x-text="(activeStudentUniforms || []).length + ' Items'"></span>
            </div>

            <div class="space-y-2.5">
              <template x-for="item in (activeStudentUniforms || [])" :key="item.id">
                <div class="p-3.5 rounded-2xl border transition-all"
                     :class="item.is_issued ? 'bg-emerald-50/40 border-emerald-200' : 'bg-white border-slate-200 hover:border-teal-300 shadow-2xs'">
                  
                  <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                      <div class="font-extrabold text-xs text-slate-900 leading-snug" x-text="item.item_name"></div>
                      <div class="flex items-center gap-2">
                        <template x-if="item.sku">
                          <span class="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded" x-text="item.sku"></span>
                        </template>
                        <span class="text-[11px] text-slate-500 font-medium">Req: <strong class="text-slate-800" x-text="item.quantity"></strong></span>
                      </div>
                    </div>

                    {{-- Status Badge --}}
                    <div>
                      <template x-if="item.is_issued">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">✓ Received</span>
                      </template>
                      <template x-if="!item.is_issued">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-teal-100 text-teal-900 border border-teal-200"
                              x-text="'Due: ' + (item.quantity - item.issued_quantity)"></span>
                      </template>
                    </div>
                  </div>

                  {{-- Stepper & Live Issuance Control --}}
                  <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <span class="text-[11px] font-bold text-slate-500">Issued:</span>
                      <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs">
                        <button type="button" @click="changeItemQty(item, -1)"
                                :disabled="item.issued_quantity <= 0"
                                class="w-7 h-7 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                          -
                        </button>
                        <input type="number" min="0" :max="item.quantity"
                               x-model.number="item.issued_quantity"
                               @change="saveItemIssuance(item, item.issued_quantity)"
                               class="w-10 text-center py-1 text-xs font-black text-slate-900 border-0 focus:ring-0">
                        <button type="button" @click="changeItemQty(item, 1)"
                                :disabled="item.issued_quantity >= item.quantity"
                                class="w-7 h-7 bg-slate-50 hover:bg-slate-200 font-black text-slate-700 flex items-center justify-center transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                          +
                        </button>
                      </div>
                      <span class="text-[10px] text-slate-400 font-medium">/ <span x-text="item.quantity"></span></span>
                    </div>

                    <button type="button" @click="markItemFullyIssued(item)"
                            x-show="!item.is_issued"
                            class="px-2.5 py-1 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 text-[10px] font-black transition cursor-pointer">
                      Mark Full (All <span x-text="item.quantity"></span>)
                    </button>
                  </div>

                </div>
              </template>
              <div x-show="!activeStudentUniforms || activeStudentUniforms.length === 0" class="py-6 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                No uniform items allocated
              </div>
            </div>
          </div>

        </div>

      </div>

      {{-- Modal Footer --}}
      <div class="p-4 px-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-4">
        <button type="button" @click="openPrintSlip(activeStudentData?.id)"
                class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center gap-2 transition cursor-pointer">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          <span>Print Official Delivery Slip</span>
        </button>

        <button type="button" @click="closeStudentModal()"
                class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-black text-xs transition cursor-pointer">
          Done / Close
        </button>
      </div>

      </div>
    </div>
  </div>

</div>

{{-- ── Alpine.js Controller Logic ────────────────────────────────────────── --}}
<script>
function booksNotesPage() {
  return {
    academicYears: @json($academicYears),
    classesList: @json($classes),
    groupsList: @json($groups),
    selectedYearId: '{{ $currentYear?->id ?? "" }}',
    searchQuery: '',
    selectedCategory: 'ALL',
    cardsLoading: true,
    cards: [],
    summary: {
      totalClasses: 0,
      totalTitles: 0,
      totalBooks: 0,
      totalNotes: 0,
      totalUnits: 0,
    },
    alert: { show: false, message: '', type: 'success' },

    // Main Navigation Tab: 'checklists' | 'distribution'
    activeMainTab: 'checklists',

    // Distribution Tracker State
    distLoading: false,
    distClassId: '',
    distStatus: 'pending',
    distSearch: '',
    distPage: 1,
    distSummary: {
      total_students: 0,
      students_fully_issued: 0,
      students_with_remaining: 0,
      remaining_books_units: 0,
      remaining_notes_units: 0,
      total_remaining_units: 0,
      total_issued_units: 0,
    },
    distStudents: [],
    distPagination: {
      total: 0,
      per_page: 25,
      current_page: 1,
      last_page: 1,
    },

    // Quick Fee Check & Issuance Desk State
    deskQuery: '',
    deskStudents: [],
    deskLoading: false,
    deskDropdownOpen: false,
    deskActiveStudent: null,
    deskStudentData: null,
    deskStudentBooks: [],
    deskStudentNotes: [],
    deskStudentUniforms: [],
    deskStudentStats: null,
    deskStudentLoading: false,
    deskFeeOverride: false,
    deskCategoryTab: 'NOTE',

    // Student Checklist Modal State
    isStudentModalOpen: false,
    studentModalLoading: false,
    activeStudentData: null,
    activeStudentBooks: [],
    activeStudentNotes: [],
    activeStudentUniforms: [],
    activeStudentStats: null,

    // Detail Modal State (When touching a card)
    isDetailModalOpen: false,
    activeCard: null,
    activeGroupTab: '',
    detailLoading: false,
    detailViewMode: 'checklist', // 'checklist' | 'table'
    detailItems: [],
    detailTypeFilter: 'ALL',
    detailSearchQuery: '',

    // Add / Edit Form Modal State (CRUD)
    isFormModalOpen: false,
    formMode: 'create',
    formSubmitting: false,
    formError: '',
    form: {
      id: null,
      academic_year_id: '',
      class_id: '',
      group_id: '',
      item_type: 'BOOK',
      gender: 'ALL',
      sku: '',
      item_name: '',
      quantity: 1,
      display_order: 1,
      status: 'active',
    },

    init() {
      if (!this.selectedYearId && this.academicYears.length > 0) {
        this.selectedYearId = this.academicYears[0].id;
      }
      this.loadCards();
      this.loadDistributionSummary();
    },

    get selectedYearName() {
      const yr = this.academicYears.find(y => y.id == this.selectedYearId);
      return yr ? yr.name : '2026-2027';
    },

    getClassBadge(name) {
      if (!name) return 'CL';
      const clean = name.trim().toUpperCase();
      if (clean === 'PRE-KG' || clean === 'PRE KG') return 'PKG';
      if (clean === 'LKG' || clean === 'JUNIOR KG') return 'LKG';
      if (clean === 'UKG' || clean === 'SENIOR KG') return 'UKG';
      return clean.replace(/[^A-Z0-9]/g, '').slice(0, 3) || 'CL';
    },

    getClassStage(name, isSenior) {
      if (isSenior) return 'Senior Secondary';
      const nm = (name || '').toUpperCase();
      if (nm.includes('KG') || nm.includes('PRE')) return 'Kindergarten';
      if (['I', 'II', 'III', 'IV', 'V', '1', '2', '3', '4', '5'].some(g => nm === g || nm === 'CLASS ' + g)) return 'Primary Stage';
      return 'Middle & High School';
    },

    get cardGridClass() {
      const count = this.filteredCards.length;
      if (count === 1) return 'grid grid-cols-1 max-w-xl mx-auto md:mx-0 gap-6';
      if (count === 2) return 'grid grid-cols-1 md:grid-cols-2 gap-6';
      if (count === 3) return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';
      if (count === 4) return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6';
      return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
    },

    get isFormSeniorSecondary() {
      if (!this.form.class_id) return false;
      const cls = this.classesList.find(c => c.id == this.form.class_id);
      if (!cls) return false;
      const num = Number(cls.numeric_value || 0);
      if (num === 11 || num === 12) return true;
      const nm = (cls.name || '').toUpperCase().trim();
      return ['XI', 'XII', '11', '12', 'GRADE XI', 'GRADE XII', 'CLASS XI', 'CLASS XII'].includes(nm);
    },

    get filteredCards() {
      if (this.selectedCategory === 'ALL') {
        return this.cards;
      }
      return this.cards.filter(c => {
        const num = Number(c.numeric_value || 0);
        const name = (c.class_name || '').toUpperCase();
        if (this.selectedCategory === 'KINDERGARTEN') {
          return num === 0 || name.includes('KG') || name.includes('PRE');
        }
        if (this.selectedCategory === 'PRIMARY') {
          return (num >= 1 && num <= 5) || ['I', 'II', 'III', 'IV', 'V'].includes(name);
        }
        if (this.selectedCategory === 'MIDDLE_HIGH') {
          return (num >= 6 && num <= 10) || ['VI', 'VII', 'VIII', 'IX', 'X'].includes(name);
        }
        if (this.selectedCategory === 'SENIOR_SEC') {
          return c.is_senior_secondary || num === 11 || num === 12 || ['XI', 'XII'].includes(name);
        }
        return true;
      });
    },

    get activeGroupInfo() {
      if (!this.activeCard || !this.activeCard.is_senior_secondary || !this.activeGroupTab) {
        return null;
      }
      return this.activeCard.groups.find(g => String(g.group_id) === String(this.activeGroupTab)) || null;
    },

    get detailStats() {
      const books = this.detailItems.filter(i => i.item_type === 'BOOK');
      const notes = this.detailItems.filter(i => i.item_type === 'NOTE');
      const uniforms = this.detailItems.filter(i => i.item_type === 'UNIFORM');
      const booksQty = books.reduce((acc, cur) => acc + Number(cur.quantity || 0), 0);
      const notesQty = notes.reduce((acc, cur) => acc + Number(cur.quantity || 0), 0);
      const uniformsQty = uniforms.reduce((acc, cur) => acc + Number(cur.quantity || 0), 0);
      return {
        booksCount: books.length,
        booksQty: booksQty,
        notesCount: notes.length,
        notesQty: notesQty,
        uniformsCount: uniforms.length,
        uniformsQty: uniformsQty,
        totalQty: booksQty + notesQty + uniformsQty,
      };
    },

    get filteredDetailItems() {
      let list = this.detailItems;
      if (this.detailTypeFilter === 'BOOK') {
        list = list.filter(i => i.item_type === 'BOOK');
      } else if (this.detailTypeFilter === 'NOTE') {
        list = list.filter(i => i.item_type === 'NOTE');
      } else if (this.detailTypeFilter === 'UNIFORM') {
        list = list.filter(i => i.item_type === 'UNIFORM');
      }
      if (this.detailSearchQuery) {
        const q = this.detailSearchQuery.toLowerCase().trim();
        list = list.filter(i => 
          (i.item_name && i.item_name.toLowerCase().includes(q)) ||
          (i.sku && i.sku.toLowerCase().includes(q))
        );
      }
      return list;
    },

    get detailBooksList() {
      return this.filteredDetailItems.filter(i => i.item_type === 'BOOK');
    },

    get detailNotesList() {
      return this.filteredDetailItems.filter(i => i.item_type === 'NOTE');
    },

    get detailUniformsList() {
      return this.filteredDetailItems.filter(i => i.item_type === 'UNIFORM');
    },

    // ── Load All Class Cards ──────────────────────────────────────────────
    async loadCards() {
      this.cardsLoading = true;
      try {
        const params = new URLSearchParams({
          academic_year_id: this.selectedYearId,
        });
        if (this.searchQuery) {
          params.append('search', this.searchQuery);
        }
        const res = await fetch(`/api/books-notes/cards?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
          this.cards = data.cards || [];
          this.summary = data.summary || {
            totalClasses: 0,
            totalTitles: 0,
            totalBooks: 0,
            totalNotes: 0,
            totalUnits: 0,
          };
        }
      } catch (e) {
        this.showAlert('Failed to load class cards: ' + e.message, 'error');
      } finally {
        this.cardsLoading = false;
      }
    },

    // ── Touch Card: Open Detailed Standard View ───────────────────────────
    async touchCard(card) {
      this.activeCard = card;
      this.detailTypeFilter = 'ALL';
      this.detailSearchQuery = '';
      const groups = Array.isArray(card.groups) ? card.groups : (card.groups ? Object.values(card.groups) : []);
      if (card.is_senior_secondary && groups.length > 0) {
        this.activeGroupTab = String(groups[0].group_id);
      } else {
        this.activeGroupTab = '';
      }
      this.isDetailModalOpen = true;
      await this.loadClassDetailItems(card.class_id, this.activeGroupTab);
    },

    async selectGroupTab(groupId) {
      this.activeGroupTab = String(groupId);
      if (this.activeCard) {
        await this.loadClassDetailItems(this.activeCard.class_id, this.activeGroupTab);
      }
    },

    async loadClassDetailItems(classId, groupId) {
      this.detailLoading = true;
      try {
        const params = new URLSearchParams({
          academic_year_id: this.selectedYearId,
          class_id: classId,
        });
        if (groupId) {
          params.append('group_id', groupId);
        } else {
          params.append('without_group', '1');
        }
        const res = await fetch(`/api/books-notes?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
          this.detailItems = data.data || [];
        }
      } catch (e) {
        this.showAlert('Failed to load checklist details: ' + e.message, 'error');
      } finally {
        this.detailLoading = false;
      }
    },

    closeDetailModal() {
      this.isDetailModalOpen = false;
      this.activeCard = null;
      this.detailItems = [];
    },

    // ── CRUD: Add / Edit Item ─────────────────────────────────────────────
    openCreateModal(prefillClassId = '', prefillGroupId = '', prefillItemType = 'BOOK', prefillGender = 'ALL') {
      this.formMode = 'create';
      this.formError = '';
      this.form = {
        id: null,
        academic_year_id: this.selectedYearId,
        class_id: prefillClassId || (this.classesList[0]?.id || ''),
        group_id: prefillGroupId || (this.groupsList[0]?.id || ''),
        item_type: prefillItemType || 'BOOK',
        gender: prefillGender || 'ALL',
        sku: '',
        item_name: '',
        quantity: 1,
        display_order: (this.detailItems.length + 1),
        status: 'active',
      };
      this.isFormModalOpen = true;
    },

    openEditModal(item) {
      this.formMode = 'edit';
      this.formError = '';
      this.form = {
        id: item.id,
        academic_year_id: item.academic_year_id,
        class_id: item.class_id,
        group_id: item.group_id || '',
        item_type: item.item_type,
        gender: item.gender || 'ALL',
        sku: item.sku || '',
        item_name: item.item_name,
        quantity: item.quantity,
        display_order: item.display_order,
        status: item.status,
      };
      this.isFormModalOpen = true;
    },

    closeFormModal() {
      this.isFormModalOpen = false;
      this.formError = '';
    },

    onFormClassChange() {
      if (!this.isFormSeniorSecondary) {
        this.form.group_id = '';
      } else if (!this.form.group_id && this.groupsList.length > 0) {
        this.form.group_id = this.groupsList[0].id;
      }
    },

    async submitForm() {
      this.formSubmitting = true;
      this.formError = '';
      try {
        const url = this.formMode === 'create' ? '/api/books-notes' : `/api/books-notes/${this.form.id}`;
        const method = this.formMode === 'create' ? 'POST' : 'PUT';

        const payload = {
          academic_year_id: this.form.academic_year_id,
          class_id: this.form.class_id,
          group_id: this.isFormSeniorSecondary ? this.form.group_id : null,
          item_type: this.form.item_type,
          gender: this.form.item_type === 'UNIFORM' ? (this.form.gender || 'ALL') : null,
          sku: this.form.sku,
          item_name: this.form.item_name,
          quantity: this.form.quantity,
          display_order: this.form.display_order,
          status: this.form.status,
        };

        const res = await fetch(url, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!res.ok || !data.success) {
          if (data.errors) {
            const firstErr = Object.values(data.errors)[0];
            this.formError = Array.isArray(firstErr) ? firstErr[0] : firstErr;
          } else {
            this.formError = data.message || 'Failed to save item.';
          }
          return;
        }

        this.showAlert(data.message, 'success');
        this.closeFormModal();
        await this.loadCards();

        // Refresh detail view if open
        if (this.isDetailModalOpen && this.activeCard) {
          await this.loadClassDetailItems(this.activeCard.class_id, this.activeGroupTab);
        }
      } catch (e) {
        this.formError = e.message || 'Network error occurred.';
      } finally {
        this.formSubmitting = false;
      }
    },

    // ── CRUD: Status Toggle ───────────────────────────────────────────────
    async toggleItemStatus(item) {
      try {
        const res = await fetch(`/api/books-notes/${item.id}/status`, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
        });
        const data = await res.json();
        if (data.success) {
          item.status = data.data.status;
          this.showAlert(data.message, 'success');
          await this.loadCards();
        } else {
          this.showAlert(data.message || 'Status toggle failed.', 'error');
        }
      } catch (e) {
        this.showAlert(e.message, 'error');
      }
    },

    // ── CRUD: Delete / Deactivate Item ────────────────────────────────────
    async deleteItem(item) {
      if (!confirm(`Are you sure you want to delete / deactivate '${item.item_name}'?\n\nIf this item has already been referenced in student admissions, it will be safely deactivated to preserve historical records.`)) {
        return;
      }

      try {
        const res = await fetch(`/api/books-notes/${item.id}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
        });
        const data = await res.json();
        if (data.success) {
          this.showAlert(data.message, 'success');
          if (this.isDetailModalOpen && this.activeCard) {
            await this.loadClassDetailItems(this.activeCard.class_id, this.activeGroupTab);
          }
          await this.loadCards();
        } else {
          this.showAlert(data.message || 'Failed to delete item.', 'error');
        }
      } catch (e) {
        this.showAlert(e.message, 'error');
      }
    },

    // ── Distribution & Issuance Tracker Methods ─────────────────────────
    switchMainTab(tab) {
      this.activeMainTab = tab;
      if (tab === 'distribution' && this.distStudents.length === 0) {
        this.loadDistribution(1);
      }
      if (tab === 'desk') {
        if (this.deskStudents.length === 0) {
          this.searchDeskStudents();
        }
        this.$nextTick(() => {
          this.$refs.deskSearchInput?.focus();
        });
      }
    },

    onDistFilterChange() {
      this.distPage = 1;
      this.loadDistribution(1);
    },

    async loadDistribution(page = 1) {
      this.distPage = page;
      await Promise.all([
        this.loadDistributionSummary(),
        this.loadDistributionStudents(page)
      ]);
    },

    async loadDistributionSummary() {
      try {
        const params = new URLSearchParams({
          academic_year_id: this.selectedYearId,
        });
        if (this.distClassId) {
          params.append('class_id', this.distClassId);
        }
        const res = await fetch(`/api/books-notes/distribution/summary?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
          this.distSummary = data.summary;
        }
      } catch (e) {
        console.error('Failed to load distribution summary:', e);
      }
    },

    async loadDistributionStudents(page = 1) {
      this.distLoading = true;
      try {
        const params = new URLSearchParams({
          academic_year_id: this.selectedYearId,
          status: this.distStatus,
          page: page,
          per_page: 25,
        });
        if (this.distClassId) {
          params.append('class_id', this.distClassId);
        }
        if (this.distSearch) {
          params.append('search', this.distSearch);
        }
        const res = await fetch(`/api/books-notes/distribution/students?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
          this.distStudents = data.students || [];
          this.distPagination = data.pagination || {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
          };
        }
      } catch (e) {
        this.showAlert('Failed to load students: ' + e.message, 'error');
      } finally {
        this.distLoading = false;
      }
    },

    async openStudentModal(studentId) {
      this.isStudentModalOpen = true;
      this.studentModalLoading = true;
      try {
        const res = await fetch(`/api/books-notes/distribution/student/${studentId}?academic_year_id=${this.selectedYearId}`);
        const data = await res.json();
        if (data.success) {
          this.activeStudentData = data.data.student;
          this.activeStudentBooks = data.data.books || [];
          this.activeStudentNotes = data.data.notes || [];
          this.activeStudentUniforms = data.data.uniforms || [];
          
          const bRem = this.activeStudentBooks.reduce((acc, b) => acc + Math.max(0, Number(b.quantity || 0) - Number(b.issued_quantity || 0)), 0);
          const nRem = this.activeStudentNotes.reduce((acc, n) => acc + Math.max(0, Number(n.quantity || 0) - Number(n.issued_quantity || 0)), 0);
          const uRem = this.activeStudentUniforms.reduce((acc, u) => acc + Math.max(0, Number(u.quantity || 0) - Number(u.issued_quantity || 0)), 0);
          const totPres = data.data.summary?.total_prescribed ?? (
            this.activeStudentBooks.reduce((a, b) => a + Number(b.quantity || 0), 0) +
            this.activeStudentNotes.reduce((a, n) => a + Number(n.quantity || 0), 0) +
            this.activeStudentUniforms.reduce((a, u) => a + Number(u.quantity || 0), 0)
          );
          const totIssued = data.data.summary?.total_issued ?? (
            this.activeStudentBooks.reduce((a, b) => a + Number(b.issued_quantity || 0), 0) +
            this.activeStudentNotes.reduce((a, n) => a + Number(n.issued_quantity || 0), 0) +
            this.activeStudentUniforms.reduce((a, u) => a + Number(u.issued_quantity || 0), 0)
          );

          this.activeStudentStats = {
            totalPrescribed: totPres,
            totalIssued: totIssued,
            totalRemaining: bRem + nRem + uRem,
            booksRemaining: bRem,
            notesRemaining: nRem,
            uniformsRemaining: uRem,
          };
        } else {
          this.showAlert(data.message || 'Failed to load student checklist', 'error');
          this.closeStudentModal();
        }
      } catch (e) {
        this.showAlert('Error loading student checklist: ' + e.message, 'error');
        this.closeStudentModal();
      } finally {
        this.studentModalLoading = false;
      }
    },

    closeStudentModal() {
      this.isStudentModalOpen = false;
      this.activeStudentData = null;
      this.activeStudentBooks = [];
      this.activeStudentNotes = [];
      this.activeStudentUniforms = [];
      this.activeStudentStats = null;
    },

    changeItemQty(item, delta) {
      const cur = Number(item.issued_quantity || 0);
      const target = Math.max(0, Math.min(Number(item.quantity || 1), cur + delta));
      this.saveItemIssuance(item, target);
    },

    markItemFullyIssued(item) {
      this.saveItemIssuance(item, Number(item.quantity || 1));
    },

    async saveItemIssuance(item, newQty, remarks = null) {
      const prevQty = item.issued_quantity;
      item.issued_quantity = Number(newQty);
      item.is_issued = (item.issued_quantity >= item.quantity);
      item.remaining_quantity = Math.max(0, item.quantity - item.issued_quantity);

      // Recalculate stats immediately
      let bRem = 0, nRem = 0, uRem = 0, totIssued = 0, totPres = 0;
      this.activeStudentBooks.forEach(b => {
        totPres += Number(b.quantity || 0);
        totIssued += Number(b.issued_quantity || 0);
        bRem += Math.max(0, Number(b.quantity || 0) - Number(b.issued_quantity || 0));
      });
      this.activeStudentNotes.forEach(n => {
        totPres += Number(n.quantity || 0);
        totIssued += Number(n.issued_quantity || 0);
        nRem += Math.max(0, Number(n.quantity || 0) - Number(n.issued_quantity || 0));
      });
      this.activeStudentUniforms.forEach(u => {
        totPres += Number(u.quantity || 0);
        totIssued += Number(u.issued_quantity || 0);
        uRem += Math.max(0, Number(u.quantity || 0) - Number(u.issued_quantity || 0));
      });
      const totRem = bRem + nRem + uRem;

      this.activeStudentStats = {
        totalPrescribed: totPres,
        totalIssued: totIssued,
        totalRemaining: totRem,
        booksRemaining: bRem,
        notesRemaining: nRem,
        uniformsRemaining: uRem,
      };

      // Also update student in distStudents array if present
      if (this.activeStudentData) {
        const row = this.distStudents.find(s => s.student_id === this.activeStudentData.id);
        if (row) {
          row.total_issued = totIssued;
          row.total_remaining = totRem;
          row.books_issued = this.activeStudentBooks.reduce((acc, b) => acc + Number(b.issued_quantity || 0), 0);
          row.books_remaining = bRem;
          row.notes_issued = this.activeStudentNotes.reduce((acc, n) => acc + Number(n.issued_quantity || 0), 0);
          row.notes_remaining = nRem;
          row.uniforms_issued = this.activeStudentUniforms.reduce((acc, u) => acc + Number(u.issued_quantity || 0), 0);
          row.uniforms_remaining = uRem;
          row.distribution_status = (totRem === 0) ? 'Distributed' : (totIssued > 0 ? 'Partially Distributed' : 'Pending Distribution');
          row.status = (totRem === 0) ? 'fully_issued' : (totIssued > 0 ? 'partially_issued' : 'pending');
        }
      }

      try {
        const res = await fetch('/api/books-notes/distribution/issue-item', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            item_id: item.id,
            issued_quantity: newQty,
            remarks: remarks,
          }),
        });
        const data = await res.json();
        if (!data.success) {
          item.issued_quantity = prevQty;
          this.showAlert(data.message || 'Failed to update item issuance', 'error');
        } else {
          this.loadDistributionSummary();
        }
      } catch (e) {
        item.issued_quantity = prevQty;
        this.showAlert('Error saving issuance: ' + e.message, 'error');
      }
    },

    async issueAllRemaining(studentId) {
      if (!studentId) return;
      if (!confirm('Are you sure you want to mark all remaining books and notebooks as issued for this student?')) {
        return;
      }

      try {
        const res = await fetch(`/api/books-notes/distribution/issue-all/${studentId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
        });
        const data = await res.json();
        if (data.success) {
          this.showAlert(data.message, 'success');
          if (this.isStudentModalOpen && this.activeStudentData?.id === studentId) {
            await this.openStudentModal(studentId);
          }
          await this.loadDistribution(this.distPage);
        } else {
          this.showAlert(data.message || 'Failed to issue all items', 'error');
        }
      } catch (e) {
        this.showAlert('Error: ' + e.message, 'error');
      }
    },

    openPrintSlip(studentId) {
      if (!studentId) return;
      window.open(`/settings/books-notes/slip/${studentId}?academic_year_id=${this.selectedYearId}`, '_blank');
    },

    showAlert(message, type = 'success') {
      this.alert = { show: true, message, type };
      setTimeout(() => {
        this.alert.show = false;
      }, 4500);
    },

    // ── Quick Fee Check & Issuance Desk Methods ──────────────────────
    async searchDeskStudents() {
      this.deskLoading = true;
      try {
        const q = encodeURIComponent(this.deskQuery || '');
        const res = await fetch(`/api/books-notes/distribution/search-students?query=${q}&academic_year_id=${this.selectedYearId}`);
        const data = await res.json();
        if (data.success) {
          this.deskStudents = data.students || [];
          this.deskDropdownOpen = true;
        }
      } catch (e) {
        console.error('Error searching students:', e);
      } finally {
        this.deskLoading = false;
      }
    },

    async selectDeskStudent(student) {
      if (!student) return;
      this.deskActiveStudent = student;
      this.deskDropdownOpen = false;
      this.deskQuery = student.name;
      this.deskStudentLoading = true;
      this.deskFeeOverride = false;

      try {
        const res = await fetch(`/api/books-notes/distribution/student/${student.id}?academic_year_id=${this.selectedYearId}`);
        const data = await res.json();
        if (data.success) {
          this.deskStudentData = data.data.student;
          this.deskStudentBooks = data.data.books || [];
          this.deskStudentNotes = data.data.notes || [];
          this.deskStudentUniforms = data.data.uniforms || [];

          this.recalculateDeskStats(data.data.summary);
        } else {
          this.showAlert(data.message || 'Failed to load student checklist', 'error');
        }
      } catch (e) {
        this.showAlert('Error loading student checklist: ' + e.message, 'error');
      } finally {
        this.deskStudentLoading = false;
      }
    },

    async selectDeskStudentById(studentId) {
      this.deskStudentLoading = true;
      this.deskDropdownOpen = false;
      this.deskFeeOverride = false;
      try {
        const res = await fetch(`/api/books-notes/distribution/student/${studentId}?academic_year_id=${this.selectedYearId}`);
        const data = await res.json();
        if (data.success) {
          this.deskActiveStudent = data.data.student;
          this.deskStudentData = data.data.student;
          this.deskStudentBooks = data.data.books || [];
          this.deskStudentNotes = data.data.notes || [];
          this.deskStudentUniforms = data.data.uniforms || [];
          this.deskQuery = data.data.student.name;
          this.recalculateDeskStats(data.data.summary);
        } else {
          this.showAlert(data.message || 'Failed to load student checklist', 'error');
        }
      } catch (e) {
        this.showAlert('Error loading student: ' + e.message, 'error');
      } finally {
        this.deskStudentLoading = false;
      }
    },

    clearDeskStudent() {
      this.deskActiveStudent = null;
      this.deskStudentData = null;
      this.deskStudentBooks = [];
      this.deskStudentNotes = [];
      this.deskStudentUniforms = [];
      this.deskStudentStats = null;
      this.deskQuery = '';
      this.deskFeeOverride = false;
      this.searchDeskStudents();
      this.$nextTick(() => {
        this.$refs.deskSearchInput?.focus();
      });
    },

    recalculateDeskStats(fallbackSummary = null) {
      const bRem = this.deskStudentBooks.reduce((acc, b) => acc + Math.max(0, Number(b.quantity || 0) - Number(b.issued_quantity || 0)), 0);
      const nRem = this.deskStudentNotes.reduce((acc, n) => acc + Math.max(0, Number(n.quantity || 0) - Number(n.issued_quantity || 0)), 0);
      const uRem = this.deskStudentUniforms.reduce((acc, u) => acc + Math.max(0, Number(u.quantity || 0) - Number(u.issued_quantity || 0)), 0);

      const totPres = fallbackSummary?.total_prescribed ?? (
        this.deskStudentBooks.reduce((a, b) => a + Number(b.quantity || 0), 0) +
        this.deskStudentNotes.reduce((a, n) => a + Number(n.quantity || 0), 0) +
        this.deskStudentUniforms.reduce((a, u) => a + Number(u.quantity || 0), 0)
      );
      const totIssued = fallbackSummary?.total_issued ?? (
        this.deskStudentBooks.reduce((a, b) => a + Number(b.issued_quantity || 0), 0) +
        this.deskStudentNotes.reduce((a, n) => a + Number(n.issued_quantity || 0), 0) +
        this.deskStudentUniforms.reduce((a, u) => a + Number(u.issued_quantity || 0), 0)
      );

      this.deskStudentStats = {
        totalPrescribed: totPres,
        totalIssued: totIssued,
        totalRemaining: bRem + nRem + uRem,
        booksRemaining: bRem,
        notesRemaining: nRem,
        uniformsRemaining: uRem,
      };
    },

    changeDeskItemQty(item, delta) {
      const cur = Number(item.issued_quantity || 0);
      const target = Math.max(0, Math.min(Number(item.quantity || 1), cur + delta));
      this.saveDeskItemIssuance(item, target);
    },

    async saveDeskItemIssuance(item, newQty, remarks = null) {
      const prevQty = item.issued_quantity;
      item.issued_quantity = Number(newQty);
      item.is_issued = (item.issued_quantity >= item.quantity);
      item.remaining_quantity = Math.max(0, item.quantity - item.issued_quantity);

      this.recalculateDeskStats();

      try {
        const res = await fetch('/api/books-notes/distribution/issue-item', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            item_id: item.id,
            issued_quantity: newQty,
            remarks: remarks,
          }),
        });
        const data = await res.json();
        if (!data.success) {
          item.issued_quantity = prevQty;
          item.is_issued = (item.issued_quantity >= item.quantity);
          item.remaining_quantity = Math.max(0, item.quantity - item.issued_quantity);
          this.recalculateDeskStats();
          this.showAlert(data.message || 'Failed to update item issuance', 'error');
        } else {
          this.showAlert('Issuance status updated.', 'success');
          this.loadDistributionSummary();
        }
      } catch (e) {
        item.issued_quantity = prevQty;
        item.is_issued = (item.issued_quantity >= item.quantity);
        item.remaining_quantity = Math.max(0, item.quantity - item.issued_quantity);
        this.recalculateDeskStats();
        this.showAlert('Error saving issuance: ' + e.message, 'error');
      }
    },

    async issueDeskRemaining(itemType = null) {
      if (!this.deskActiveStudent?.id) return;
      const label = itemType ? (itemType.toLowerCase() === 'note' ? 'notebooks' : itemType.toLowerCase() + 's') : 'all remaining prescribed items';
      if (!confirm(`Are you sure you want to mark ${label} as issued for ${this.deskActiveStudent.name}?`)) {
        return;
      }

      try {
        const res = await fetch(`/api/books-notes/distribution/issue-all/${this.deskActiveStudent.id}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            item_type: itemType,
          }),
        });
        const data = await res.json();
        if (data.success) {
          this.showAlert(data.message, 'success');
          // Reload student details
          await this.selectDeskStudent(this.deskActiveStudent);
          this.loadDistributionSummary();
          await this.loadDistribution(this.distPage);
        } else {
          this.showAlert(data.message || 'Failed to issue items', 'error');
        }
      } catch (e) {
        this.showAlert('Error: ' + e.message, 'error');
      }
    },

    formatCurrency(val) {
      const num = Number(val || 0);
      return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  };
}
</script>
@endsection
