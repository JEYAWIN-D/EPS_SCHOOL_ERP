@extends('layouts.app')
@section('title', ($circular->reference_no ?? 'Circular') . ' — ' . $circular->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-16">

  {{-- Top Navigation & Action Buttons (Hidden when printing) --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
    <div class="flex items-center gap-2 text-xs font-bold">
      <a href="{{ route('circulars.index') }}" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-maroon-800 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Circulars &amp; Orders
      </a>
      <span class="text-slate-300">/</span>
      <a href="{{ route('events.calendar') }}" class="text-slate-500 hover:text-maroon-700 transition">
        Calendar View
      </a>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      {{-- Print Action --}}
      <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print Letterhead
      </button>

      {{-- Share Button --}}
      <button onclick="shareDirective()" class="px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
        Share
      </button>

      @php
        $user = auth()->user();
        $canManage = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']);
      @endphp

      @if($canManage)
        <a href="{{ route('circulars.edit', $circular->id) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-bold transition shadow-2xs inline-flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
          Edit
        </a>

        <form method="POST" action="{{ route('circulars.destroy', $circular->id) }}" onsubmit="return confirm('Permanently delete circular [{{ addslashes($circular->reference_no ?? $circular->title) }}]?')" class="inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete
          </button>
        </form>
      @endif
    </div>
  </div>

  {{-- Status Alerts --}}
  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm shadow-xs flex items-center gap-2 print:hidden">
      <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  {{-- 📜 OFFICIAL LETTERHEAD CONTAINER --}}
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 sm:p-12 space-y-8 print:border-0 print:shadow-none print:p-0">

    {{-- School Header / Letterhead --}}
    <div class="text-center border-b-2 border-maroon-900/90 pb-6 space-y-1.5">
      <div class="flex items-center justify-center gap-3.5">
        <div class="w-14 h-14 rounded-2xl bg-maroon-900 flex items-center justify-center text-white font-extrabold text-2xl shadow-sm tracking-tight border border-amber-500/30">
          EPS
        </div>
        <div class="text-left">
          <h2 class="text-2xl sm:text-3xl font-black text-maroon-900 tracking-tight uppercase">Erode Public School</h2>
          <p class="text-[11px] sm:text-xs text-slate-600 font-bold tracking-wider uppercase">Senior Secondary &bull; Affiliated to CBSE, New Delhi &bull; Affiliation No: 1930482 &bull; School Code: 55412</p>
        </div>
      </div>
      <p class="text-xs text-slate-500 pt-1">Chennimalai Road, Erode, Tamil Nadu 638051 &bull; Tel: +91 424 225 8000 &bull; circulars@erodepublicschool.edu.in &bull; www.erodepublicschool.edu.in</p>
    </div>

    {{-- Reference Bar & Date --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4 text-xs">
      <div class="flex items-center gap-2">
        <span class="text-slate-400 font-bold uppercase tracking-wider">Ref No:</span>
        <span class="font-mono font-black text-slate-900 text-sm bg-slate-100 px-3 py-1 rounded-lg border border-slate-200 shadow-2xs">
          {{ $circular->reference_no ?? 'EPS/CIR/' . $circular->id }}
        </span>
        <button type="button" onclick="navigator.clipboard.writeText('{{ $circular->reference_no ?? '' }}'); alert('Reference number copied!');"
                class="text-[11px] text-slate-400 hover:text-slate-700 print:hidden" title="Copy Reference Number">
          📋
        </button>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-slate-400 font-bold uppercase tracking-wider">Date of Issue:</span>
        <span class="font-bold text-slate-900 ml-1">
          {{ $circular->publish_date ? $circular->publish_date->format('F d, Y') : $circular->created_at->format('F d, Y') }}
        </span>
      </div>
    </div>

    {{-- Directive Subject & Header Banner --}}
    <div class="space-y-3">
      <div class="flex items-center gap-2 flex-wrap">
        @php
          $isOrder = in_array($circular->notice_type, ['order','directive','memo']) || !empty($circular->order_category);
        @endphp

        <span class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $isOrder ? 'bg-purple-100 text-purple-900 border border-purple-200' : 'bg-sky-100 text-sky-900 border border-sky-200' }}">
          {{ $isOrder ? 'OFFICIAL EXECUTIVE ORDER' : 'OFFICIAL CIRCULAR' }}
        </span>

        @if($circular->priority === 'urgent')
          <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-rose-100 text-rose-700 border border-rose-200 animate-pulse">
            IMMEDIATE ATTENTION REQUIRED
          </span>
        @elseif($circular->priority === 'high')
          <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200">
            HIGH PRIORITY
          </span>
        @endif

        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-50 text-slate-600 border border-slate-200">
          Distribution: <strong class="text-slate-800 uppercase">{{ $circular->target_audience }}</strong>
        </span>

        @if($circular->order_category)
          <span class="px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
            Category: {{ $circular->order_category }}
          </span>
        @endif
      </div>

      <div class="pt-2">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-snug tracking-tight">
          Sub: <span class="underline decoration-maroon-800/40 decoration-2 underline-offset-4">{{ $circular->title }}</span>
        </h1>
      </div>
    </div>

    {{-- Directive Content Body --}}
    <div class="prose text-sm text-slate-800 max-w-none leading-relaxed space-y-4 pt-2">
      {!! $circular->content !!}
    </div>

    {{-- Official Signatory & Seal Block --}}
    <div class="pt-8 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">
      <div class="space-y-1.5 text-xs text-slate-500">
        <p><strong>Issuing Authority:</strong> {{ $circular->issuing_authority ?? 'Office of the Principal' }}</p>
        <p><strong>Copy to:</strong> Correspondent / Management &bull; Academic Coordinators &bull; School Notice Board</p>
        <p class="text-[11px] text-slate-400">Institutional record generated via Erode Public School ERP.</p>
      </div>

      <div class="text-right space-y-1.5 flex flex-col items-end">
        <div class="w-28 h-16 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 flex items-center justify-center text-center text-[10px] font-bold text-slate-400 mb-1">
          [ OFFICIAL SEAL ]
        </div>
        <p class="font-extrabold text-slate-900 text-sm tracking-tight">{{ $circular->signed_by_name ?? 'Dr. R. Ramanathan' }}</p>
        <p class="text-xs font-semibold text-slate-600">{{ $circular->signatory_designation ?? 'Principal / Head of Institution' }}</p>
        <p class="text-[11px] text-slate-400 font-medium">Erode Public School</p>
      </div>
    </div>

  </div>

  {{-- Attached Document Download Box --}}
  @if($circular->attachment)
  <div class="card p-6 rounded-2xl bg-white border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
      </div>
      <div>
        <h4 class="font-bold text-slate-900 text-sm">Official Scanned Document / Attachment</h4>
        <p class="text-xs text-slate-500">Download the signed official order or circular file with stamps and seals.</p>
      </div>
    </div>

    <a href="{{ Storage::url($circular->attachment) }}" target="_blank"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold transition shadow-xs">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
      Download Attachment
    </a>
  </div>
  @endif

  {{-- Receipt Acknowledgement Box --}}
  @if($circular->requires_acknowledgement)
  <div class="card p-6 rounded-2xl bg-white border border-slate-200 space-y-4 print:hidden shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-sm">Receipt Acknowledgement</h3>
        <p class="text-xs text-slate-500">Official confirmation that you have read, understood, and accepted this directive.</p>
      </div>

      <div>
        @if($myAcknowledgement)
          <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            You acknowledged receipt on {{ $myAcknowledgement->acknowledged_at->format('d M Y, h:i A') }}
          </span>
        @else
          <form method="POST" action="{{ route('circulars.acknowledge', $circular->id) }}">
            @csrf
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-maroon-800 to-amber-700 hover:from-maroon-900 hover:to-amber-800 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              I Acknowledge Receipt of this Directive
            </button>
          </form>
        @endif
      </div>
    </div>

    {{-- Acknowledgement Log for Administrators --}}
    @if($canManage && $circular->acknowledgements->count() > 0)
    <div class="pt-4 border-t border-slate-100">
      <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
        Compliance Audit Log ({{ $circular->acknowledgements->count() }} Recipients Acknowledged)
      </h4>
      <div class="overflow-x-auto max-h-48 rounded-xl border border-slate-100">
        <table class="w-full text-xs text-left">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
            <tr>
              <th class="p-2.5">Recipient</th>
              <th class="p-2.5">Role</th>
              <th class="p-2.5">Acknowledged At</th>
              <th class="p-2.5">IP Address</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($circular->acknowledgements as $ack)
              <tr>
                <td class="p-2.5 font-bold text-slate-800">{{ $ack->user?->name ?? 'User #' . $ack->user_id }}</td>
                <td class="p-2.5 text-slate-500">{{ $ack->user?->roles->pluck('name')->join(', ') ?? 'Recipient' }}</td>
                <td class="p-2.5 text-slate-600">{{ $ack->acknowledged_at->format('d M Y, H:i') }}</td>
                <td class="p-2.5 font-mono text-[11px] text-slate-400">{{ $ack->ip_address ?? 'Local' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
  </div>
  @endif

</div>

<script>
function shareDirective() {
  const title = @json($circular->title);
  const ref = @json($circular->reference_no ?? 'EPS/CIR/' . $circular->id);
  const url = window.location.href;
  const shareText = `*OFFICIAL DIRECTIVE - ERODE PUBLIC SCHOOL*\n*Ref*: ${ref}\n*Subject*: ${title}\n\nView official letterhead and attachment:\n${url}`;

  if (navigator.share) {
    navigator.share({
      title: title,
      text: shareText,
      url: url,
    }).catch(() => {});
  } else {
    const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
    window.open(waUrl, '_blank');
  }
}
</script>
@endsection
