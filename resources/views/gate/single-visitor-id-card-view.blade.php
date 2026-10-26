@extends('layouts.app')

@section('title', 'Visitor Pass — ' . $visitor->pass_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12" x-data="{
  orientation: '{{ $orientation }}',
  checkoutModal: false
}">

  {{-- Top Navigation & Action Bar --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3">
      <a href="{{ route('gate.visitor-id-card-management') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Visitor Pass Studio</span>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">
            Pass #{{ $visitor->pass_number }}
          </span>
          @if(empty($visitor->out_time))
            <span class="badge-green text-xs">Active Inside</span>
          @else
            <span class="badge-slate text-xs">Checked Out</span>
          @endif
        </div>
        <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $visitor->visitor_name }}</h1>
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-2 flex-wrap">
      {{-- Switch Orientation --}}
      <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
        <button type="button" @click="orientation = 'landscape'" :class="orientation === 'landscape' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg transition">
          Landscape
        </button>
        <button type="button" @click="orientation = 'portrait'" :class="orientation === 'portrait' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg transition">
          Portrait
        </button>
      </div>

      {{-- Download CR80 PDF --}}
      <a :href="'{{ route('gate.visitor-id-card.pdf', $visitor->id) }}?orientation=' + orientation + '&format=cr80'"
         target="_blank"
         class="btn btn-primary btn-sm flex items-center gap-2 bg-[#b91c1c] hover:bg-[#991b1b] text-white font-bold px-4 py-2 rounded-xl shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Download CR80 Badge (PDF)</span>
      </a>

      {{-- Thermal Slip --}}
      <a href="{{ route('gate.pass', ['id' => $visitor->id]) }}"
         target="_blank"
         class="btn btn-secondary btn-sm font-bold">
        Thermal Slip
      </a>

      <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm font-bold flex items-center gap-1.5">
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print</span>
      </button>
    </div>
  </div>

  {{-- Preview Area & Metadata --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    {{-- Card Preview Stage --}}
    <div class="lg:col-span-8 bg-slate-900/5 p-6 sm:p-10 rounded-3xl border border-slate-200 flex flex-col items-center justify-center min-h-[420px] relative overflow-hidden">
      
      <div class="absolute top-3 left-4 text-[10px] font-mono text-slate-400 font-bold tracking-wider">
        VISITOR CREDENTIAL SPEC • CR80 (85.60 × 53.98 mm)
      </div>

      {{-- LANDSCAPE VISITOR PASS --}}
      <div x-show="orientation === 'landscape'" style="display:none" class="w-full max-w-[500px]">
        <div class="w-full aspect-[85.6/53.98] bg-white rounded-2xl shadow-2xl border-2 border-[#b91c1c] overflow-hidden flex flex-col justify-between relative transition-all duration-300">
          
          {{-- Security Band --}}
          <div class="h-6 bg-[#b91c1c] text-white text-[10px] font-black uppercase tracking-widest text-center flex items-center justify-center shadow-xs">
            ★ OFFICIAL CAMPUS VISITOR PASS ★
          </div>

          {{-- Header --}}
          <div class="h-12 bg-[#1e293b] text-white px-3 py-1 flex items-center justify-between border-b border-amber-500">
            <div class="flex items-center gap-2">
              <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-8 h-8 object-contain drop-shadow-xs" alt="Crest">
              <div>
                <h2 class="text-xs font-black tracking-tight uppercase leading-tight">Erode Public School</h2>
                <p class="text-[8px] text-amber-300 font-bold uppercase">SECURITY CLEARANCE PASS</p>
              </div>
            </div>
            <div class="text-right">
              <span class="bg-[#b91c1c] text-white font-mono font-bold text-[9px] px-2 py-0.5 rounded">
                {{ $visitor->pass_number }}
              </span>
            </div>
          </div>

          {{-- Body --}}
          <div class="flex-1 p-3 flex items-center gap-3">
            <div class="shrink-0 text-center">
              @if(!empty($visitor->visitor_photo))
                <img src="{{ asset('storage/' . $visitor->visitor_photo) }}" class="w-18 h-22 rounded-xl object-cover ring-2 ring-[#b91c1c] shadow-sm mx-auto" alt="Photo">
              @else
                <div class="w-18 h-22 rounded-xl bg-rose-50 border border-rose-300 flex items-center justify-center text-rose-600 font-black text-xs mx-auto">
                  VISITOR
                </div>
              @endif
            </div>

            <div class="flex-1 min-w-0 space-y-1">
              <h3 class="text-sm font-extrabold text-slate-900 uppercase truncate leading-tight">
                {{ $visitor->visitor_name }}
              </h3>
              @if($visitor->company_name)
                <p class="text-[9.5px] font-bold text-slate-500">{{ $visitor->company_name }}</p>
              @endif

              <div class="space-y-0.5 text-[10px] pt-0.5">
                <div>
                  <span class="text-slate-400 font-bold uppercase text-[8.5px]">Meeting:</span>
                  <span class="font-bold text-slate-800">{{ $visitor->whom_to_meet ?? 'School Office' }}</span>
                </div>
                <div>
                  <span class="text-slate-400 font-bold uppercase text-[8.5px]">Purpose:</span>
                  <span class="font-semibold text-slate-700 truncate block">{{ Str::limit($visitor->purpose, 35) }}</span>
                </div>
                <div class="flex items-center gap-3 text-[9px] font-mono text-slate-500 pt-0.5">
                  <span>In: <strong class="text-slate-800">{{ $visitor->in_time ? \Carbon\Carbon::parse($visitor->in_time)->format('h:i A') : '—' }}</strong></span>
                  <span class="text-rose-600">Valid: <strong>{{ $visitor->expected_exit_time ? \Carbon\Carbon::parse($visitor->expected_exit_time)->format('h:i A') : 'Same Day' }}</strong></span>
                </div>
              </div>
            </div>

            @if($qrCodeSvg)
            <div class="shrink-0 text-center pl-1">
              <div class="w-15 h-15 bg-white p-0.5 rounded border border-slate-200 shadow-2xs">
                {!! $qrCodeSvg !!}
              </div>
              <span class="text-[7px] font-mono text-slate-400 uppercase font-bold block mt-0.5">Gate Check</span>
            </div>
            @endif
          </div>

          {{-- Footer --}}
          <div class="h-6 bg-slate-50 border-t border-slate-200 px-3 flex items-center justify-between text-[8.5px] text-slate-500 font-mono">
            <span>Badge #: <strong class="text-slate-700">{{ $card->card_number }}</strong></span>
            <span class="text-rose-700 font-bold uppercase">Security Officer Signature</span>
          </div>
        </div>
      </div>

      {{-- PORTRAIT VISITOR PASS --}}
      <div x-show="orientation === 'portrait'" style="display:none" class="w-full max-w-[340px]">
        <div class="w-full aspect-[53.98/85.6] bg-white rounded-2xl shadow-2xl border-2 border-[#b91c1c] overflow-hidden flex flex-col justify-between text-center relative transition-all duration-300">
          
          <div class="bg-[#b91c1c] text-white py-1 px-2 text-[9px] font-black uppercase tracking-wider">
            ★ VISITOR GATE PASS ★
          </div>

          <div class="bg-[#1e293b] text-white p-2.5 border-b border-amber-500">
            <img src="{{ asset('images/school-logo-transparent.png') }}" class="w-8 h-8 object-contain mx-auto drop-shadow-xs" alt="Crest">
            <h2 class="text-xs font-black tracking-tight uppercase mt-0.5 leading-tight">Erode Public School</h2>
          </div>

          <div class="px-4 pt-2 space-y-1.5">
            @if(!empty($visitor->visitor_photo))
              <img src="{{ asset('storage/' . $visitor->visitor_photo) }}" class="w-20 h-24 rounded-2xl object-cover ring-2 ring-[#b91c1c] shadow-md mx-auto" alt="Photo">
            @else
              <div class="w-20 h-24 rounded-2xl bg-rose-50 border border-rose-300 flex items-center justify-center text-rose-600 font-black text-xs mx-auto">
                VISITOR
              </div>
            @endif

            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase truncate leading-tight">
                {{ $visitor->visitor_name }}
              </h3>
              <span class="inline-block bg-rose-100 text-rose-800 font-mono text-[9px] font-bold px-2 py-0.5 rounded mt-0.5">
                {{ $visitor->pass_number }}
              </span>
            </div>

            <div class="text-left text-[9.5px] space-y-1 bg-slate-50 p-2 rounded-xl border border-slate-100">
              <div>
                <span class="text-slate-400 font-bold uppercase text-[8px]">Whom to Meet:</span>
                <span class="font-bold text-slate-800 block truncate">{{ $visitor->whom_to_meet ?? 'School Office' }}</span>
              </div>
              <div>
                <span class="text-slate-400 font-bold uppercase text-[8px]">Purpose:</span>
                <span class="font-semibold text-slate-700 block truncate">{{ $visitor->purpose }}</span>
              </div>
              <div class="flex justify-between pt-0.5 text-slate-500 font-mono text-[8.5px]">
                <span>In: {{ $visitor->in_time ? \Carbon\Carbon::parse($visitor->in_time)->format('h:i A') : '—' }}</span>
                <span class="text-rose-700 font-bold">Exp: {{ $visitor->expected_exit_time ? \Carbon\Carbon::parse($visitor->expected_exit_time)->format('h:i A') : 'Same Day' }}</span>
              </div>
            </div>
          </div>

          <div class="bg-slate-50 border-t border-slate-200 p-2 flex items-center justify-between text-left">
            @if($qrCodeSvg)
              <div class="w-10 h-10 bg-white p-0.5 rounded border border-slate-200">
                {!! $qrCodeSvg !!}
              </div>
            @endif
            <div class="text-[8px] font-mono text-slate-500 pl-2">
              <div><strong>{{ $card->card_number }}</strong></div>
              <div class="text-rose-700 font-bold uppercase">Security Clearance</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- Metadata & Actions Sidebar --}}
    <div class="lg:col-span-4 space-y-5">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Pass Security Parameters</h3>
        
        <div class="space-y-2 text-xs">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Pass Number:</span>
            <span class="font-mono font-bold text-rose-700">{{ $visitor->pass_number }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Category:</span>
            <span class="font-semibold capitalize text-slate-800">{{ str_replace('_', ' ', $visitor->category) }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Entry Time:</span>
            <span class="font-mono text-slate-800">{{ $visitor->in_time ? \Carbon\Carbon::parse($visitor->in_time)->format('d/m/Y h:i A') : '—' }}</span>
          </div>

          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Valid Until:</span>
            <span class="font-mono text-rose-700 font-bold">{{ $visitor->expected_exit_time ? \Carbon\Carbon::parse($visitor->expected_exit_time)->format('h:i A (Same Day)') : 'Same Day' }}</span>
          </div>

          @if($visitor->out_time)
          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-slate-500">Exit Time:</span>
            <span class="font-mono text-emerald-700 font-bold">{{ \Carbon\Carbon::parse($visitor->out_time)->format('d/m/Y h:i A') }}</span>
          </div>
          @endif
        </div>

        @if(empty($visitor->out_time))
          <form action="{{ route('gate.checkout', $visitor->id) }}" method="POST" class="pt-2">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-primary btn-sm w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2">
              Mark Pass Returned &amp; Check Out
            </button>
          </form>
        @endif
      </div>

      {{-- QR Verification --}}
      <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 space-y-2.5">
        <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <span>Gate Scanner Verification</span>
        </div>
        <p class="text-[11px] text-emerald-800 leading-relaxed">
          Security personnel scanning this badge will see visitor validity, host, and check-in status.
        </p>
        <a href="{{ $verifyUrl ?? ($card ? $card->getQrVerificationUrl() : '#') }}" target="_blank" class="btn btn-secondary btn-sm w-full font-bold text-emerald-800 border-emerald-300 hover:bg-emerald-100 flex items-center justify-center gap-1.5 text-xs">
          <span>Test Verification</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
      </div>
    </div>

  </div>

</div>
@endsection
