<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ID Card Verification — Erode Public School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .school-crest-font { font-family: 'Cinzel', serif; }
  </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col justify-between text-slate-800 antialiased p-4 sm:p-6">

  <div class="max-w-lg w-full mx-auto my-auto space-y-6">

    {{-- School Brand Card --}}
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden">
      
      {{-- Institutional Header --}}
      <div class="bg-[#892528] px-6 py-5 text-white text-center relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
        <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>
        
        <div class="flex items-center justify-center gap-3 mb-2">
          <img src="{{ asset('images/school-logo-transparent.png') }}" alt="Erode Public School Crest" class="w-14 h-14 object-contain filter drop-shadow-md">
        </div>
        <h1 class="text-xl sm:text-2xl font-black tracking-tight uppercase leading-snug">
          Erode Public School
        </h1>
        <p class="text-amber-300 text-xs font-bold tracking-widest uppercase mt-0.5">
          Senior Secondary &bull; Affiliated to CBSE
        </p>
        <p class="text-white/80 text-[11px] font-mono mt-1">
          Institutional Identity Verification Portal
        </p>
      </div>

      {{-- Verification Status Banner --}}
      <div class="px-6 py-4 border-b border-slate-100 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-black tracking-wider uppercase border {{ $badgeClass }} shadow-2xs">
          @if($isValid)
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          @else
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          @endif
          <span>{{ $statusText }}</span>
        </div>

        @if(!empty($reason))
          <p class="text-xs text-red-600 font-semibold mt-2 px-4">
            {{ $reason }}
          </p>
        @endif
      </div>

      {{-- Card Details (Strictly Non-Sensitive Public Information) --}}
      @if($card)
      <div class="p-6 space-y-4">
        
        {{-- Profile Snippet --}}
        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/60">
          @php
            $photo = $snapshot['photo'] ?? null;
          @endphp
          @if($photo)
            <img src="{{ asset('storage/' . $photo) }}" alt="{{ $snapshot['name'] ?? 'Photo' }}" class="w-16 h-16 rounded-xl object-cover ring-2 ring-slate-200 shadow-sm shrink-0">
          @else
            <div class="w-16 h-16 rounded-xl bg-slate-200 text-slate-500 font-black text-xl flex items-center justify-center shrink-0">
              {{ strtoupper(substr($snapshot['name'] ?? 'ID', 0, 2)) }}
            </div>
          @endif

          <div class="min-w-0 flex-1">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">
              {{ ucfirst($card->card_type) }} Identity
            </span>
            <h2 class="text-lg font-bold text-slate-900 truncate">
              {{ $snapshot['name'] ?? $snapshot['visitor_name'] ?? 'Authorized Person' }}
            </h2>
            <p class="text-xs text-[#892528] font-mono font-bold mt-0.5">
              {{ $card->card_number }}
            </p>
          </div>
        </div>

        {{-- Verification Metadata Grid --}}
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Identity Number</p>
            <p class="font-bold text-slate-800 mt-0.5 font-mono">
              {{ $snapshot['admission_no'] ?? $snapshot['employee_code'] ?? $snapshot['pass_number'] ?? 'N/A' }}
            </p>
          </div>

          <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">
              @if($card->card_type === 'student') Class &amp; Section
              @elseif($card->card_type === 'staff') Designation &amp; Dept
              @else Purpose of Visit
              @endif
            </p>
            <p class="font-bold text-slate-800 mt-0.5 truncate">
              @if($card->card_type === 'student')
                {{ $snapshot['class_name'] ?? '' }} {{ !empty($snapshot['section_name']) ? '(' . $snapshot['section_name'] . ')' : '' }}
              @elseif($card->card_type === 'staff')
                {{ $snapshot['designation'] ?? 'Staff' }} &bull; {{ $snapshot['department'] ?? 'General' }}
              @else
                {{ $snapshot['purpose'] ?? 'Campus Visit' }}
              @endif
            </p>
          </div>

          <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Issue Date</p>
            <p class="font-semibold text-slate-700 mt-0.5 font-mono">
              {{ $card->issue_date?->format('d M Y') ?? 'N/A' }}
            </p>
          </div>

          <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Valid Until</p>
            <p class="font-semibold text-slate-700 mt-0.5 font-mono">
              {{ $card->expiry_date?->format('d M Y') ?? 'Academic Session' }}
            </p>
          </div>
        </div>

        {{-- Verification Seal & Assurance --}}
        <div class="bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-3.5 flex items-start gap-3">
          <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
          </div>
          <div class="text-xs text-emerald-900 leading-relaxed">
            <p class="font-bold">Institutional Cryptographic Check Passed</p>
            <p class="text-[11px] text-emerald-700 mt-0.5">
              Verified directly against Erode Public School database records. For campus security emergencies, call <span class="font-mono font-bold">93677 54654</span>.
            </p>
          </div>
        </div>

      </div>
      @endif

      {{-- Footer --}}
      <div class="bg-slate-50 px-6 py-3.5 text-center border-t border-slate-200/70 text-[11px] text-slate-500 font-medium">
        420, Vakkil Thottam, Manickam Palayam, Erode, Tamil Nadu – 638004
      </div>

    </div>

    {{-- System Timestamp --}}
    <p class="text-center text-[10.5px] text-slate-400 font-mono">
      Verified at {{ now()->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }} IST
    </p>

  </div>

</body>
</html>
