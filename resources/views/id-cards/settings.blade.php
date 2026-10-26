@extends('layouts.app')

@section('title', 'Institutional ID Card Settings & Templates — Erode Public School')

@section('content')
<div class="space-y-6 pb-12" x-data="{
  cardType: '{{ $cardType }}',
  orientation: '{{ $orientation }}',
  primaryColor: '{{ $sampleConfig['primary_color'] ?? '#892528' }}',
  secondaryColor: '{{ $sampleConfig['secondary_color'] ?? '#3F4358' }}',
  accentGold: '{{ $sampleConfig['accent_gold'] ?? '#C59B27' }}',
  bgColor: '{{ $sampleConfig['bg_color'] ?? '#FFFFFF' }}',
  textColor: '{{ $sampleConfig['text_color'] ?? '#0F172A' }}',
  schoolName: '{{ addslashes($sampleConfig['school_name'] ?? 'Erode Public School') }}',
  affiliationText: '{{ addslashes($sampleConfig['affiliation_text'] ?? 'Affiliated to CBSE, New Delhi Aff No.1931585') }}',
  motto: '{{ addslashes($sampleConfig['motto'] ?? "TOMORROW'S INSIDE") }}',
  showBloodGroup: {{ !empty($sampleConfig['show_blood_group']) ? 'true' : 'false' }},
  showDob: {{ !empty($sampleConfig['show_dob']) ? 'true' : 'false' }},
  showAddress: {{ !empty($sampleConfig['show_address']) ? 'true' : 'false' }},
  showParentPhone: {{ !empty($sampleConfig['show_parent_phone']) ? 'true' : 'false' }},
  showEmergencyPhone: {{ !empty($sampleConfig['show_emergency_phone']) ? 'true' : 'false' }},
  showSignature: {{ !empty($sampleConfig['show_signature']) ? 'true' : 'false' }},
  showQr: {{ !empty($sampleConfig['show_qr']) ? 'true' : 'false' }},
  showBarcode: {{ !empty($sampleConfig['show_barcode']) ? 'true' : 'false' }}
}">

  {{-- ── 1. Top Header Banner ── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-2xl bg-[#892528] text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl font-black text-slate-900 tracking-tight">ID Card Settings &amp; Template Studio</h1>
          <span class="badge-blue text-[11px] font-semibold">Institutional Branding</span>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#892528]/10 text-[#892528] border border-[#892528]/20">
            v{{ $selectedTemplate?->version ?? 1 }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Centrally configure brand colors, typography, field privacy visibilities, and CR80 card dimensions for Students, Staff, and Visitors.
        </p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('id-cards.audit-logs') }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-bold">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Audit History
      </a>
    </div>
  </div>

  {{-- ── 2. Card Type & Orientation Selector Tabs ── --}}
  <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Card Category:</span>
      <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
        <a href="{{ route('id-cards.settings', ['card_type' => 'student', 'orientation' => $orientation]) }}"
           class="px-4 py-1.5 rounded-lg transition {{ $cardType === 'student' ? 'bg-[#892528] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          Student Cards
        </a>
        <a href="{{ route('id-cards.settings', ['card_type' => 'staff', 'orientation' => $orientation]) }}"
           class="px-4 py-1.5 rounded-lg transition {{ $cardType === 'staff' ? 'bg-[#1e293b] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          Staff / Faculty
        </a>
        <a href="{{ route('id-cards.settings', ['card_type' => 'visitor', 'orientation' => $orientation]) }}"
           class="px-4 py-1.5 rounded-lg transition {{ $cardType === 'visitor' ? 'bg-[#b91c1c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          Visitor Passes
        </a>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Orientation:</span>
      <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
        <a href="{{ route('id-cards.settings', ['card_type' => $cardType, 'orientation' => 'landscape']) }}"
           class="px-3.5 py-1.5 rounded-lg transition {{ $orientation === 'landscape' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
          Landscape (Horizontal)
        </a>
        <a href="{{ route('id-cards.settings', ['card_type' => $cardType, 'orientation' => 'portrait']) }}"
           class="px-3.5 py-1.5 rounded-lg transition {{ $orientation === 'portrait' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
          Portrait (Vertical)
        </a>
      </div>
    </div>
  </div>

  {{-- ── 3. Main Studio Grid: Form (Left 7 cols) & Live Preview (Right 5 cols) ── --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    {{-- Form Controls --}}
    <div class="lg:col-span-7 bg-white p-6 rounded-3xl border border-slate-200/90 shadow-2xs space-y-6">
      <form action="{{ route('id-cards.settings.save') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="template_id" value="{{ $selectedTemplate->id }}">

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Template Name</label>
          <input type="text" name="template_name" value="{{ $selectedTemplate->template_name }}" required class="form-input text-xs w-full font-bold">
        </div>

        {{-- Brand Colors --}}
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Institutional Color Palette</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Primary Color</label>
              <div class="flex items-center gap-2">
                <input type="color" name="primary_color" x-model="primaryColor" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-300">
                <input type="text" x-model="primaryColor" class="form-input text-xs font-mono w-24">
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Secondary Accent</label>
              <div class="flex items-center gap-2">
                <input type="color" name="secondary_color" x-model="secondaryColor" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-300">
                <input type="text" x-model="secondaryColor" class="form-input text-xs font-mono w-24">
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Gold Highlight</label>
              <div class="flex items-center gap-2">
                <input type="color" name="accent_gold" x-model="accentGold" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-300">
                <input type="text" x-model="accentGold" class="form-input text-xs font-mono w-24">
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Card Background</label>
              <div class="flex items-center gap-2">
                <input type="color" name="bg_color" x-model="bgColor" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-300">
                <input type="text" x-model="bgColor" class="form-input text-xs font-mono w-24">
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Primary Text</label>
              <div class="flex items-center gap-2">
                <input type="color" name="text_color" x-model="textColor" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-300">
                <input type="text" x-model="textColor" class="form-input text-xs font-mono w-24">
              </div>
            </div>
          </div>
        </div>

        {{-- School Information --}}
        <div class="space-y-3 pt-2 border-t border-slate-100">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Institutional Details (CR80 Header &amp; Footer)</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">School Name</label>
              <input type="text" name="school_name" x-model="schoolName" required class="form-input text-xs w-full">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">CBSE Affiliation Text</label>
              <input type="text" name="affiliation_text" x-model="affiliationText" class="form-input text-xs w-full">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Motto</label>
              <input type="text" name="motto" x-model="motto" class="form-input text-xs w-full">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Campus Phone</label>
              <input type="text" name="school_phone" value="{{ $sampleConfig['school_phone'] ?? '93677 54654' }}" class="form-input text-xs w-full">
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-bold text-slate-700 mb-1">Campus Address</label>
              <input type="text" name="school_address" value="{{ $sampleConfig['school_address'] ?? '420, Vakkil Thottam, Manickam Palayam, Erode, Tamil Nadu – 638004' }}" class="form-input text-xs w-full">
            </div>
          </div>
        </div>

        {{-- Field Visibility & Privacy Settings --}}
        <div class="space-y-3 pt-2 border-t border-slate-100">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Card Field Visibility &amp; Privacy Rules</h3>
            <span class="text-[11px] text-slate-500">Configure sensitive data display</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" name="show_blood_group" value="1" x-model="showBloodGroup" class="rounded text-[#892528] focus:ring-[#892528]">
              <div>
                <span class="font-bold text-slate-800 block">Show Blood Group</span>
                <span class="text-[10px] text-slate-400">Prints student/staff blood type</span>
              </div>
            </label>

            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" name="show_dob" value="1" x-model="showDob" class="rounded text-[#892528] focus:ring-[#892528]">
              <div>
                <span class="font-bold text-slate-800 block">Show Date of Birth</span>
                <span class="text-[10px] text-slate-400">Displays birth date on card</span>
              </div>
            </label>

            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" name="show_parent_phone" value="1" x-model="showParentPhone" class="rounded text-[#892528] focus:ring-[#892528]">
              <div>
                <span class="font-bold text-slate-800 block">Show Parent / Guardian Phone</span>
                <span class="text-[10px] text-slate-400">Emergency mobile contact</span>
              </div>
            </label>

            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" name="show_address" value="1" x-model="showAddress" class="rounded text-[#892528] focus:ring-[#892528]">
              <div>
                <span class="font-bold text-slate-800 block">Show Residential Address</span>
                <span class="text-[10px] text-slate-400">Prints residential city/address</span>
              </div>
            </label>

            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" name="show_signature" value="1" x-model="showSignature" class="rounded text-[#892528] focus:ring-[#892528]">
              <div>
                <span class="font-bold text-slate-800 block">Show Authorized Signature</span>
                <span class="text-[10px] text-slate-400">Principal / Registrar signature line</span>
              </div>
            </label>

            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" name="show_qr" value="1" x-model="showQr" class="rounded text-[#892528] focus:ring-[#892528]">
              <div>
                <span class="font-bold text-slate-800 block">Show Secure Verification QR</span>
                <span class="text-[10px] text-slate-400">Opaque verification code</span>
              </div>
            </label>
          </div>
        </div>

        {{-- Terms & Conditions --}}
        <div class="space-y-1.5 pt-2 border-t border-slate-100">
          <label class="block text-xs font-bold text-slate-700">Back Card Terms &amp; Instructions</label>
          <textarea name="terms_text" rows="3" class="form-input text-xs w-full font-mono">{{ $sampleConfig['terms_text'] ?? '' }}</textarea>
        </div>

        {{-- Submit Button --}}
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
          <div class="text-[11px] text-slate-500">
            Last modified {{ $selectedTemplate->updated_at?->diffForHumans() ?? 'recently' }}
          </div>
          <button type="submit" class="btn btn-primary btn-sm bg-[#892528] hover:bg-[#721f22] text-white font-black px-6 py-2.5 rounded-xl shadow-md">
            Save &amp; Publish Template Changes
          </button>
        </div>
      </form>
    </div>

    {{-- Live Template Visualizer (Right 5 cols) --}}
    <div class="lg:col-span-5 space-y-4">
      <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-2xs space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Live Card Mockup</h3>
          <span class="text-[10px] font-mono text-slate-400 font-bold">Standard CR80</span>
        </div>

        {{-- Reactive Mockup Card --}}
        <div class="bg-slate-100 p-4 rounded-2xl flex items-center justify-center min-h-[300px]">
          
          <div :style="'background-color: ' + bgColor + '; border-color: ' + secondaryColor"
               :class="orientation === 'landscape' ? 'w-full aspect-[85.6/53.98]' : 'w-64 aspect-[53.98/85.6]'"
               class="rounded-xl shadow-xl border overflow-hidden flex flex-col justify-between transition-all duration-200">
            
            {{-- Header --}}
            <div :style="'background: linear-gradient(135deg, ' + primaryColor + ' 0%, #450a0a 100%); border-bottom: 2px solid ' + accentGold"
                 class="px-3 py-2 text-white flex items-center justify-between shadow-sm">
              <div class="flex items-center gap-2">
                <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-8 h-8 object-contain shrink-0 drop-shadow-md" alt="Crest">
                <div class="min-w-0">
                  <p class="text-[11px] font-black uppercase tracking-wider truncate leading-tight font-sans" x-text="schoolName"></p>
                  <p class="text-[7.5px] text-amber-300 font-bold uppercase truncate font-mono" x-text="affiliationText"></p>
                </div>
              </div>
              <div class="bg-black/30 border border-emerald-500/40 rounded-full px-2 py-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[7.5px] font-mono font-bold text-emerald-300">2026-27</span>
              </div>
            </div>

            {{-- Mock Cyber Body: Big Photo & Personal Info --}}
            <div class="p-3 flex items-center gap-3.5 flex-1 relative bg-gradient-to-b from-slate-900 to-[#0B0F17] text-white">
              
              {{-- Large Commanding Photo --}}
              <div class="relative shrink-0 text-center">
                <div class="w-20 h-26 rounded-xl bg-slate-950 border-2 border-amber-400/80 shadow-xl flex flex-col items-center justify-center text-slate-400 overflow-hidden">
                  <svg class="w-7 h-7 text-slate-500 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span class="text-[7.5px] font-mono font-bold uppercase text-slate-400">PHOTO</span>
                </div>
                <div class="absolute -bottom-2 inset-x-0 mx-auto w-max bg-amber-500 text-slate-950 font-mono text-[8px] font-black px-1.5 py-0.2 rounded-full shadow-md uppercase">
                  ROLL: 08
                </div>
              </div>

              {{-- Personal Details --}}
              <div class="flex-1 min-w-0 text-[9px] space-y-1">
                <div>
                  <p class="font-black uppercase text-xs truncate tracking-wide text-white">
                    DEEVESH DHARSHAN
                  </p>
                  <span class="text-[7.5px] font-mono text-slate-400 uppercase tracking-widest block">STUDENT IDENTITY</span>
                </div>

                {{-- Standard & Section + Academic Year --}}
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded text-[8.5px] font-mono font-black text-amber-300">
                    STD: II • SEC: A
                  </span>
                  <span class="bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded text-[8px] font-mono text-slate-300">
                    YEAR: 2026-27 <span class="text-emerald-400 font-bold">(PRESENT)</span>
                  </span>
                </div>

                {{-- Individual Contact & Address --}}
                <div class="space-y-0.5 text-[8.5px] pt-0.5">
                  <p class="text-slate-300 flex items-center gap-1 font-mono">
                    <span class="text-slate-400 font-bold uppercase text-[7.5px]">PHONE:</span>
                    <strong class="text-white">98420 00008</strong>
                  </p>
                  <p class="text-slate-300 truncate text-[8px]" title="420, Vakkil Thottam, Erode">
                    <span class="text-slate-400 font-bold uppercase text-[7.5px]">ADDR:</span>
                    420, Vakkil Thottam, Erode
                  </p>
                  <template x-if="showBloodGroup">
                    <p class="text-slate-300 text-[8px]"><span class="text-slate-400 font-bold uppercase text-[7.5px]">BLOOD:</span> <strong class="text-rose-400 font-mono">B +ve</strong></p>
                  </template>
                </div>
              </div>
            </div>

            {{-- High-Tech Linear Barcode Footer Bay --}}
            <div class="bg-white px-3 py-1 border-t border-slate-700 flex items-center justify-between text-slate-900 shadow-inner">
              <div class="flex items-center gap-2">
                <div class="w-32 h-5.5 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 24" width="100%" height="22" preserveAspectRatio="none">
                    <rect x="0" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="6" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="11" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="18" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="23" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="29" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="36" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="42" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="48" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="54" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="61" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="68" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="73" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="80" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="85" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="91" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="98" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="104" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="110" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="115" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="122" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="128" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="133" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="140" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="145" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="151" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="158" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="164" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="170" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="175" y="0" width="4" height="24" fill="#0f172a"/>
                    <rect x="182" y="0" width="3" height="24" fill="#0f172a"/>
                    <rect x="188" y="0" width="2" height="24" fill="#0f172a"/>
                    <rect x="194" y="0" width="4" height="24" fill="#0f172a"/>
                  </svg>
                </div>
                <span class="text-[7.5px] font-mono font-bold text-slate-600 uppercase">* EPS-R08-2026 *</span>
              </div>
              <span class="text-[7.5px] font-mono font-black text-[#892528] uppercase tracking-wider">OFFICIAL ACCESS</span>
            </div>
          </div>

        </div>

        <p class="text-[11px] text-slate-500 text-center">
          Colors and visibility toggles reflect instantly in the card generator and PDF output.
        </p>
      </div>
    </div>

  </div>

</div>
@endsection
