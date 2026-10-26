@extends('layouts.app')

@section('title', $employee->full_name . ' — Staff ID Card Studio')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-12" x-data="{
  orientation: '{{ $orientation }}',
  flipped: false,
  reprintModal: false,
  replaceModal: false,
  revokeModal: false,
  reprintReason: 'Staff card physical replacement',
  replaceReason: 'Reported lost / damaged',
  revokeReason: 'Separation / Relieved from service'
}">

  {{-- ── 1. Top Navigation & Action Bar ── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3">
      <a href="{{ route('hr.staff-id-card-management') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Staff ID Card Studio</span>
          @if($card)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $card->status_badge_class }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $card->isCardValid() ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
              {{ ucfirst(str_replace('_', ' ', $card->status)) }}
            </span>
          @else
            <span class="badge-red text-xs">Card Not Issued</span>
          @endif
        </div>
        <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $employee->full_name }}</h1>
      </div>
    </div>

    {{-- Controls --}}
    <div class="flex items-center gap-2 flex-wrap">
      {{-- Orientation --}}
      <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
        <button type="button" @click="orientation = 'landscape'" :class="orientation === 'landscape' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg transition">
          Landscape
        </button>
        <button type="button" @click="orientation = 'portrait'" :class="orientation === 'portrait' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg transition">
          Portrait
        </button>
      </div>

      {{-- Flip Button --}}
      <button type="button" @click="flipped = !flipped" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-bold">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span x-text="flipped ? 'Front Side' : 'Flip to Back'"></span>
      </button>

      {{-- Download PDF Button --}}
      @if($card)
      <a :href="'{{ route('hr.staff-id-cards.bulk-pdf') }}?staff_ids={{ $employee->id }}&orientation=' + orientation + '&side=both'"
         target="_blank"
         class="btn btn-primary btn-sm flex items-center gap-2 bg-[#892528] hover:bg-[#721f22] text-white font-bold px-4 py-2 rounded-xl shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span>Download PDF</span>
      </a>
      @endif

      <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm font-bold flex items-center gap-1.5">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print</span>
      </button>
    </div>
  </div>

  {{-- ── 2. Inactive Staff Warning & Protection Alert ── --}}
  @if(!$employee->is_active)
    <div class="bg-rose-50 border border-rose-300 rounded-2xl p-4 flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-2xs font-bold">!</div>
        <div class="text-xs text-rose-900 leading-relaxed">
          <p class="font-bold text-sm">Security Restriction: Inactive or Relieved Staff Member</p>
          <p class="text-rose-700 mt-0.5">
            This employee is currently marked as <strong>Inactive / Relieved</strong> in the HR master. Active identity cards cannot be issued to separated staff without authorized administrative approval.
          </p>
        </div>
      </div>
      @if(!$overrideInactive)
        <a href="{{ route('hr.staff-id-card.studio', ['id' => $employee->id, 'override_inactive' => 1]) }}" class="btn btn-secondary btn-sm shrink-0 font-bold text-rose-800 border-rose-300 bg-white hover:bg-rose-100">
          Authorize Issuance Override
        </a>
      @endif
    </div>
  @endif

  {{-- ── 3. Interactive Card Preview ── --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    <div class="lg:col-span-8 bg-slate-900/5 p-6 sm:p-10 rounded-3xl border border-slate-200 flex flex-col items-center justify-center min-h-[420px] relative overflow-hidden">
      
      <div class="absolute top-3 left-4 text-[10px] font-mono text-slate-400 font-bold tracking-wider">
        ISO CR80 FACULTY IDENTITY CARD • 85.60 × 53.98 mm
      </div>

      <div class="absolute top-3 right-4 text-[10px] font-mono text-slate-400 font-bold" x-text="flipped ? 'BACK SIDE' : 'FRONT SIDE'"></div>

      {{-- Staff Data Gathering --}}
      @php
        $empCode = $employee->employee_code ?: ('EMP-' . str_pad($employee->id, 3, '0', STR_PAD_LEFT));
        $deptName = $employee->department?->name ?? $employee->department ?? 'Faculty';
        $designation = $employee->designation ?? 'Academic Staff';
        $individualPhone = $employee->mobile ?: ($employee->alternate_phone ?: ($employee->emergency_contact_mobile ?: ($employee->contact_no ?: '98420 00008')));
        $individualAddress = $employee->residential_address ?: ($employee->permanent_address ?: ($employee->address ?: 'Erode, Tamil Nadu'));
        $barcodeVal = $barcodeVal ?? ('EPS-' . $empCode);
      @endphp

      {{-- LANDSCAPE STAFF PREVIEW --}}
      <div x-show="orientation === 'landscape'" style="display:none" class="w-full max-w-[530px]">
        {{-- FRONT SIDE LANDSCAPE --}}
        <div x-show="!flipped" class="w-full aspect-[85.6/53.98] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden flex flex-col justify-between relative transition-all duration-300 select-none">
          
          {{-- Subtle Cyber Microgrid --}}
          <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:12px_12px] opacity-25 pointer-events-none"></div>
          <div class="absolute -right-12 -top-12 w-44 h-44 bg-gradient-to-br from-[#892528]/30 via-amber-500/10 to-transparent rounded-full blur-2xl pointer-events-none"></div>

          {{-- Header --}}
          <div class="relative z-10 bg-gradient-to-r from-[#892528] via-[#6B1A1D] to-[#1E0507] px-4 py-2 border-b border-amber-500/40 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-2.5">
              <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-9 h-9 object-contain filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.6)]" alt="Crest">
              <div class="leading-none">
                <h2 class="text-xs sm:text-sm font-black tracking-wider uppercase text-white font-sans">Erode Public School</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span class="text-[8px] font-bold uppercase tracking-widest text-amber-300">Faculty &amp; Staff Credential</span>
                  <span class="text-[7px] text-amber-400/80">•</span>
                  <span class="text-[8px] font-mono text-amber-200 tracking-wider">CBSE Aff No. 1931585</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <div class="bg-black/40 backdrop-blur-xs border border-emerald-500/40 rounded-full px-2 py-0.5 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[8px] font-mono font-bold uppercase tracking-wider text-emerald-300">ACTIVE FACULTY</span>
              </div>
            </div>
          </div>

          {{-- Body: Large Photo & Executive Data Grid --}}
          <div class="relative z-10 flex-1 px-4 py-2.5 flex items-center gap-4">
            
            {{-- Big Commanding Photo --}}
            <div class="shrink-0 text-center">
              <div class="relative group">
                @if(!empty($employee->photo))
                  <img src="{{ asset('storage/' . $employee->photo) }}" class="w-28 h-36 rounded-xl object-cover ring-2 ring-amber-400/80 shadow-2xl shadow-black mx-auto" alt="Photo">
                @else
                  <div class="w-28 h-36 rounded-xl bg-slate-900/90 border-2 border-dashed border-slate-700 flex flex-col items-center justify-center text-slate-400 p-2 shadow-inner">
                    <svg class="w-8 h-8 text-slate-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-500">FACULTY PHOTO</span>
                  </div>
                @endif
                <div class="absolute -bottom-2 inset-x-0 mx-auto w-max bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-mono text-[9px] font-black px-2 py-0.5 rounded-full shadow-md uppercase tracking-wider">
                  {{ $empCode }}
                </div>
              </div>
            </div>

            {{-- Details --}}
            <div class="flex-1 min-w-0 space-y-1.5 pt-0.5">
              <div>
                <h3 class="text-base font-black uppercase text-white tracking-wide truncate leading-tight drop-shadow-sm">
                  {{ $employee->full_name }}
                </h3>
                <span class="text-[9px] font-mono uppercase text-amber-300 tracking-wider font-bold block">{{ $designation }}</span>
              </div>

              <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                <div class="bg-slate-800/90 border border-slate-700/80 px-2 py-0.5 rounded-md flex items-center gap-1.5 shadow-xs">
                  <span class="text-[9px] font-bold text-slate-400 uppercase">DEPT:</span>
                  <span class="text-[10px] font-black text-amber-300 font-mono">{{ $deptName }}</span>
                </div>

                <div class="bg-slate-800/90 border border-slate-700/80 px-2 py-0.5 rounded-md flex items-center gap-1 text-[9px] font-mono text-slate-300">
                  <span class="text-slate-400 font-bold">STATUS:</span>
                  <span class="text-[8px] bg-emerald-500/20 text-emerald-300 px-1.5 rounded font-bold uppercase">PRESENT</span>
                </div>
              </div>

              {{-- Individual Contact & Address --}}
              <div class="space-y-1 text-[10px] pt-1">
                <div class="flex items-baseline gap-1.5 text-slate-300">
                  <span class="text-[9px] font-mono font-bold uppercase text-slate-400 shrink-0">PHONE:</span>
                  <span class="font-mono font-bold text-white tracking-wide">{{ $individualPhone }}</span>
                </div>
                
                <div class="flex items-baseline gap-1.5 text-slate-300">
                  <span class="text-[9px] font-mono font-bold uppercase text-slate-400 shrink-0">ADDRESS:</span>
                  <span class="text-[9.5px] text-slate-300 truncate font-medium" title="{{ $individualAddress }}">
                    {{ Str::limit($individualAddress, 42) }}
                  </span>
                </div>

                <div class="flex items-center gap-3 text-[9px] text-slate-400 pt-0.5">
                  @if(!empty($employee->blood_group))
                    <span>BLOOD: <strong class="text-rose-400 font-bold">{{ $employee->blood_group }}</strong></span>
                  @endif
                  @if(!empty($employee->joining_date))
                    <span>JOINED: <strong class="text-slate-200 font-mono">{{ $employee->joining_date->format('d/m/Y') }}</strong></span>
                  @endif
                </div>
              </div>

            </div>

          </div>

          {{-- Sleek Code 128 Barcode Bay --}}
          <div class="relative z-10 bg-white px-4 py-1.5 border-t border-slate-700 flex items-center justify-between text-slate-900 shadow-inner">
            <div class="flex items-center gap-3">
              <div class="w-48 h-7 flex items-center overflow-hidden">
                {!! $barcodeSvg !!}
              </div>
              <span class="text-[8px] font-mono font-bold uppercase tracking-wider text-slate-600">
                * {{ $barcodeVal }} *
              </span>
            </div>

            <div class="text-right">
              <span class="text-[8px] font-mono font-black text-[#892528] uppercase tracking-wider block">ERODE PUBLIC SCHOOL</span>
              <span class="text-[7.5px] font-mono text-slate-500">FACULTY CREDENTIAL</span>
            </div>
          </div>

        </div>

        {{-- BACK SIDE LANDSCAPE --}}
        <div x-show="flipped" style="display:none" class="w-full aspect-[85.6/53.98] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden p-4 flex flex-col justify-between relative transition-all duration-300 select-none text-xs">
          
          <div class="-mx-4 -mt-4 h-9 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border-b border-slate-800 flex items-center px-4 justify-between">
            <span class="text-[8px] font-mono text-slate-500 uppercase tracking-widest">EPS FACULTY ACCESS IDENTIFIER</span>
            <span class="text-[8px] font-mono text-amber-400 font-bold">CR80 // SECURE</span>
          </div>

          <div class="space-y-2 pt-1 text-[10px]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
              <div>
                <span class="text-[8.5px] font-bold text-slate-400 uppercase block">CREDENTIAL HOLDER</span>
                <p class="font-black text-white text-xs uppercase">{{ $employee->full_name }}</p>
              </div>
              <div class="text-right font-mono">
                <span class="text-[8.5px] font-bold text-slate-400 uppercase block">EMPLOYEE ID</span>
                <p class="font-black text-amber-400">{{ $empCode }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[9.5px]">
              <div>
                <span class="text-[8px] font-bold text-slate-400 uppercase block">PERSONAL CONTACT</span>
                <p class="font-mono font-bold text-white">{{ $individualPhone }}</p>
              </div>
              <div>
                <span class="text-[8px] font-bold text-slate-400 uppercase block">RESIDENTIAL ADDRESS</span>
                <p class="text-slate-300 leading-tight">{{ Str::limit($individualAddress, 48) }}</p>
              </div>
            </div>
          </div>

          {{-- Institutional Return Authority --}}
          <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800 text-[8.5px] text-slate-400 space-y-0.5">
            <p class="text-[8px] font-bold uppercase tracking-wider text-amber-400">Institutional Property &bull; Return if found:</p>
            <p class="font-medium text-slate-200">Erode Public School, 420, Vakkil Thottam, Manickam Palayam, Erode, Tamil Nadu – 638004</p>
            <p class="font-mono text-slate-400">HR Helpline: <strong class="text-white">93677 54654</strong> &bull; Web: erodepublicschool.edu.in</p>
          </div>

        </div>
      </div>

      {{-- PORTRAIT STAFF PREVIEW --}}
      <div x-show="orientation === 'portrait'" style="display:none" class="w-full max-w-[340px]">
        {{-- FRONT SIDE PORTRAIT --}}
        <div x-show="!flipped" class="w-full aspect-[53.98/85.6] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden flex flex-col justify-between text-center relative transition-all duration-300 select-none">
          
          <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:12px_12px] opacity-25 pointer-events-none"></div>

          {{-- Header --}}
          <div class="relative z-10 bg-gradient-to-b from-[#892528] to-[#601518] text-white px-3 py-2.5 border-b border-amber-500/40">
            <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-10 h-10 object-contain mx-auto filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.6)]" alt="Crest">
            <h2 class="text-xs font-black tracking-wider uppercase mt-1 leading-tight">Erode Public School</h2>
            <p class="text-[8px] text-amber-300 font-bold uppercase tracking-widest mt-0.5">STAFF CREDENTIAL &bull; CBSE AFF NO. 1931585</p>
          </div>

          {{-- Photo & Identity Details --}}
          <div class="relative z-10 px-4 pt-2.5 space-y-2">
            
            <div class="relative inline-block mx-auto">
              @if(!empty($employee->photo))
                <img src="{{ asset('storage/' . $employee->photo) }}" class="w-32 h-40 rounded-2xl object-cover ring-2 ring-amber-400 shadow-2xl shadow-black mx-auto" alt="Photo">
              @else
                <div class="w-32 h-40 rounded-2xl bg-slate-900 border-2 border-dashed border-slate-700 flex flex-col items-center justify-center text-slate-400 p-2 shadow-inner mx-auto">
                  <svg class="w-10 h-10 text-slate-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-500">FACULTY PHOTO</span>
                </div>
              @endif
              
              <div class="absolute -bottom-2 inset-x-0 mx-auto w-max bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-mono text-[9px] font-black px-2.5 py-0.5 rounded-full shadow-md uppercase tracking-wider">
                {{ $empCode }}
              </div>
            </div>

            <div class="pt-1">
              <h3 class="text-sm font-black uppercase text-white tracking-wide truncate leading-tight drop-shadow-sm">
                {{ $employee->full_name }}
              </h3>
              <p class="text-[9px] font-mono text-amber-300 font-bold uppercase">{{ $designation }}</p>
            </div>

            <div class="bg-slate-900/90 p-2 rounded-xl border border-slate-800 text-left text-[9.5px] space-y-1">
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-bold uppercase text-[8.5px]">DEPT:</span>
                <span class="font-black text-amber-300 font-mono">{{ $deptName }}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-bold uppercase text-[8.5px]">STATUS:</span>
                <span class="font-mono text-emerald-400 font-bold text-[8px] uppercase">PRESENT / ACTIVE</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-bold uppercase text-[8.5px]">PHONE:</span>
                <span class="font-mono font-bold text-white">{{ $individualPhone }}</span>
              </div>

              <div class="pt-0.5 border-t border-slate-800/80">
                <span class="text-slate-400 font-bold uppercase text-[8px] block">ADDRESS:</span>
                <span class="text-[8.5px] text-slate-300 block truncate" title="{{ $individualAddress }}">{{ Str::limit($individualAddress, 36) }}</span>
              </div>
            </div>

          </div>

          {{-- Bottom Bar: Linear Code 128 Barcode Bay --}}
          <div class="relative z-10 bg-white p-2 border-t border-slate-700 text-slate-900 text-center shadow-inner">
            <div class="w-full h-7 flex items-center justify-center overflow-hidden">
              {!! $barcodeSvg !!}
            </div>
            <div class="flex items-center justify-between text-[7.5px] font-mono text-slate-600 px-1 mt-0.5">
              <span>* {{ $barcodeVal }} *</span>
              <span class="font-bold text-[#892528]">AUTHORIZED SIGNATURE</span>
            </div>
          </div>

        </div>

        {{-- BACK SIDE PORTRAIT --}}
        <div x-show="flipped" style="display:none" class="w-full aspect-[53.98/85.6] rounded-2xl shadow-2xl border-2 border-slate-800 bg-[#0B0F17] text-white overflow-hidden p-4 flex flex-col justify-between text-center relative transition-all duration-300 select-none">
          <div>
            <h4 class="text-xs font-black uppercase text-[#892528] tracking-wider border-b border-slate-800 pb-1.5">
              Erode Public School
            </h4>
            <div class="pt-3 text-[9.5px] text-slate-300 text-left leading-relaxed whitespace-pre-line">
              {{ $config['terms_text'] ?? "1. Official faculty card.\n2. Non-transferable.\n3. Return if found to HR office." }}
            </div>
          </div>

          <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800 text-[8.5px] text-slate-400 space-y-0.5">
            <p class="font-bold text-amber-400">Erode, Tamil Nadu – 638004</p>
            <p>Campus Phone: 93677 54654</p>
          </div>
        </div>
      </div>

    </div>

    {{-- Details & Actions Sidebar --}}
    <div class="lg:col-span-4 space-y-5">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Card Lifecycle &amp; Metadata</h3>
        
        <div class="space-y-2.5 text-xs">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Card Number:</span>
            <span class="font-mono font-bold text-slate-900">{{ $card?->card_number ?? 'Not Issued' }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Status:</span>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $card?->status_badge_class ?? 'bg-slate-100' }}">
              {{ ucfirst(str_replace('_', ' ', $card?->status ?? 'None')) }}
            </span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Reprint Count:</span>
            <span class="font-mono font-bold">{{ $card?->reprint_count ?? 0 }} times</span>
          </div>
        </div>

        @if($card)
        <div class="pt-2 grid grid-cols-2 gap-2 text-xs">
          <button type="button" @click="reprintModal = true" class="btn btn-secondary btn-sm font-bold flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Log Reprint
          </button>
          
          <button type="button" @click="replaceModal = true" class="btn btn-secondary btn-sm font-bold text-amber-700 border-amber-200 flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Replace Card
          </button>

          <button type="button" @click="revokeModal = true" class="btn btn-secondary btn-sm col-span-2 font-bold text-rose-700 border-rose-200 flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Revoke Card (Invalidate)
          </button>
        </div>
        @endif
      </div>

      {{-- Verification Box --}}
      @if($card)
      <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 space-y-2.5">
        <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <span>QR Verification Check</span>
        </div>
        <p class="text-[11px] text-emerald-800 leading-relaxed">
          Faculty QR code protects sensitive HR data such as salary and personal documents.
        </p>
        <a href="{{ $verifyUrl ?? ($card ? $card->getQrVerificationUrl() : '#') }}" target="_blank" class="btn btn-secondary btn-sm w-full font-bold text-emerald-800 border-emerald-300 hover:bg-emerald-100 flex items-center justify-center gap-1.5 text-xs">
          <span>Test Live Verification</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
      </div>
      @endif

      {{-- Audit Logs --}}
      @if($card)
      <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Card Audit Trail</h3>
        <div class="space-y-2 max-h-56 overflow-y-auto text-xs">
          @forelse($auditLogs as $log)
            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
              <div class="flex items-center justify-between font-bold text-[10px] uppercase">
                <span class="{{ $log->action === 'created' ? 'text-emerald-700' : 'text-blue-700' }}">{{ $log->action }}</span>
                <span class="text-slate-400 font-mono">{{ $log->timestamp->format('d/m/Y') }}</span>
              </div>
              @if($log->reason)
                <p class="text-[11px] text-slate-600 mt-0.5">{{ $log->reason }}</p>
              @endif
            </div>
          @empty
            <p class="text-slate-400 text-xs italic">No prior audit records.</p>
          @endforelse
        </div>
      </div>
      @endif

    </div>

  </div>

  {{-- Modals --}}
  @if($card)
  <div x-show="reprintModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <h3 class="font-bold text-slate-900 text-sm">Log Staff Card Reprint</h3>
      <form action="{{ route('id-cards.reprint', $card->id) }}" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reprint Reason</label>
          <input type="text" name="reason" x-model="reprintReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="reprintModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-[#892528] text-white font-bold">Confirm</button>
        </div>
      </form>
    </div>
  </div>

  <div x-show="replaceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <h3 class="font-bold text-amber-700 text-sm">Replace Lost / Damaged Card</h3>
      <form action="{{ route('id-cards.replace', $card->id) }}" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reason</label>
          <input type="text" name="reason" x-model="replaceReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="replaceModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-amber-600 hover:bg-amber-700 text-white font-bold">Issue Replacement</button>
        </div>
      </form>
    </div>
  </div>

  <div x-show="revokeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <h3 class="font-bold text-rose-700 text-sm">Revoke Staff Card</h3>
      <form action="{{ route('id-cards.revoke', $card->id) }}" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Revocation Reason</label>
          <input type="text" name="reason" x-model="revokeReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="revokeModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-rose-600 hover:bg-rose-700 text-white font-bold">Revoke</button>
        </div>
      </form>
    </div>
  </div>
  @endif

</div>
@endsection
