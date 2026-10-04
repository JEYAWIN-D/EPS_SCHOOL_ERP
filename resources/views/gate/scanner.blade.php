@extends('layouts.app')
@section('title', 'Fast QR Gate Scanner & Instant Exit')
@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="qrScanner()">

  {{-- Top Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-2.5">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
      </div>
      <div>
        <h1 class="page-title text-xl font-bold text-slate-900 tracking-tight">1-Second QR Scan-To-Exit</h1>
        <p class="page-subtitle text-xs text-slate-500">Scan visitor gate pass badges or thermal barcodes for instant automated checkout</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        {{ $activeInsideCount }} Visitors Inside
      </span>
      <a href="{{ route('gate.index') }}" class="btn-secondary text-xs sm:text-sm font-semibold">
        ← Gate Dashboard
      </a>
    </div>
  </div>

  {{-- Scanner Main Box --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    {{-- Left Card: Scan Input / Gun Scanner / Camera --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-5">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">Scan Visitor Pass</h2>
        <span class="text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">Fast Exit Mode</span>
      </div>

      {{-- Barcode / Handheld Scanner Input (Auto-Focused) --}}
      <form @submit.prevent="processScan" class="space-y-3">
        <div>
          <label class="label text-xs font-bold text-slate-700">Handheld Barcode / QR Gun Scanner</label>
          <div class="relative">
            <input type="text" x-model="scanInput" x-ref="scanField" placeholder="Point scanner here or type Pass #..." autofocus class="input w-full pl-9 pr-24 font-mono font-bold text-slate-900 text-sm tracking-wide bg-slate-50 focus:bg-white">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <button type="submit" :disabled="loading" class="btn-primary absolute right-1 top-1 bottom-1 text-xs px-3 py-1 font-bold">
              <span x-show="!loading">Check Out</span>
              <span x-show="loading">...</span>
            </button>
          </div>
          <p class="text-[10px] text-slate-400 mt-1">Accepts Pass Number (e.g. VP-20261002-0001) or QR Scan Token.</p>
        </div>
      </form>

      {{-- Camera QR Scanner Box --}}
      <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-700">Webcam / Device Camera</span>
          <button type="button" @click="toggleCamera" class="btn-secondary text-[11px] px-2 py-1 font-semibold">
            <span x-text="cameraActive ? 'Stop Camera' : 'Start Camera Scanner'"></span>
          </button>
        </div>

        <div x-show="cameraActive" class="relative rounded-xl overflow-hidden bg-black aspect-video flex items-center justify-center">
          <video x-ref="scannerVideo" class="w-full h-full object-cover" playsinline autoplay></video>
          <div class="absolute inset-8 border-2 border-indigo-400 rounded-xl pointer-events-none animate-pulse"></div>
        </div>
      </div>
    </div>

    {{-- Right Card: Live Scan Result & Feedback --}}
    <div class="space-y-4">
      
      {{-- Waiting State --}}
      <div x-show="!lastResult" class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center space-y-3 shadow-xs h-full flex flex-col items-center justify-center">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        </div>
        <div>
          <h3 class="font-bold text-slate-800 text-sm">Ready to Scan</h3>
          <p class="text-xs text-slate-500 max-w-xs mx-auto mt-1">Scan any visitor pass at the gate. The system will immediately record checkout time and verify credentials.</p>
        </div>
      </div>

      {{-- Success Card --}}
      <div x-show="lastResult && lastResult.success" class="bg-emerald-50 rounded-2xl border-2 border-emerald-400 p-6 shadow-md space-y-4 animate-fade-in" style="display: none;">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 text-xl font-bold">
            ✓
          </div>
          <div>
            <h3 class="font-black text-emerald-950 text-base" x-text="lastResult ? lastResult.message : ''"></h3>
            <p class="text-xs text-emerald-800 font-semibold" x-text="lastResult && lastResult.duration ? 'Total Stay Duration: ' + lastResult.duration : ''"></p>
          </div>
        </div>

        <template x-if="lastResult && lastResult.visitor">
          <div class="bg-white rounded-xl p-4 border border-emerald-200 text-xs space-y-2">
            <div class="flex justify-between">
              <span class="text-slate-500">Visitor:</span>
              <span class="font-bold text-slate-900" x-text="lastResult.visitor.visitor_name"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Pass Number:</span>
              <span class="font-mono font-bold text-indigo-700" x-text="lastResult.visitor.pass_number"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Host Met:</span>
              <span class="font-semibold text-slate-800" x-text="lastResult.visitor.whom_to_meet || '—'"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Exit Time:</span>
              <span class="font-mono font-bold text-emerald-700" x-text="lastResult.out_time_display || 'Just now'"></span>
            </div>
          </div>
        </template>
      </div>

      {{-- Error / Warning Card --}}
      <div x-show="lastResult && !lastResult.success" class="bg-rose-50 rounded-2xl border-2 border-rose-400 p-6 shadow-md space-y-3 animate-fade-in" style="display: none;">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-rose-500 text-white flex items-center justify-center flex-shrink-0 text-xl font-bold">
            ✕
          </div>
          <div>
            <h3 class="font-black text-rose-950 text-base">Checkout Alert</h3>
            <p class="text-xs text-rose-800" x-text="lastResult ? lastResult.message : ''"></p>
          </div>
        </div>
      </div>

    </div>

  </div>

  {{-- Recent Checkouts Feed --}}
  <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">Recent Departures Today</h2>
      <span class="text-xs text-slate-500">Live gate log</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3">Pass #</th>
            <th class="py-2.5 px-3">Visitor Name</th>
            <th class="py-2.5 px-3">Category</th>
            <th class="py-2.5 px-3">Host</th>
            <th class="py-2.5 px-3">In Time</th>
            <th class="py-2.5 px-3">Out Time</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($recentCheckouts as $rc)
          <tr class="hover:bg-slate-50 transition">
            <td class="py-2.5 px-3 font-mono font-bold text-indigo-700">{{ $rc->pass_number }}</td>
            <td class="py-2.5 px-3 font-bold text-slate-900">{{ $rc->visitor_name }}</td>
            <td class="py-2.5 px-3">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $rc->category_badge_classes['bg'] }}">
                {{ $rc->category_label }}
              </span>
            </td>
            <td class="py-2.5 px-3 text-slate-700">{{ $rc->whom_to_meet ?? '—' }}</td>
            <td class="py-2.5 px-3 font-mono text-slate-500">{{ $rc->in_time?->format('h:i A') }}</td>
            <td class="py-2.5 px-3 font-mono font-bold text-emerald-700">{{ $rc->out_time?->format('h:i A') }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-6 text-center text-slate-400">No checkouts recorded yet today.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

@push('scripts')
<script>
function qrScanner() {
  return {
    scanInput: '',
    loading: false,
    lastResult: null,
    cameraActive: false,
    stream: null,

    async processScan() {
      const code = this.scanInput.trim();
      if (!code) return;
      this.loading = true;
      this.lastResult = null;

      try {
        const res = await fetch('{{ route('gate.scan-checkout') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ code: code }),
        });

        const data = await res.json();
        this.lastResult = data;
        this.scanInput = '';
        this.$refs.scanField.focus();

        // Optional audio beep feedback
        if (data.success) {
          this.playBeep(880, 150);
        } else {
          this.playBeep(300, 300);
        }
      } catch (e) {
        this.lastResult = { success: false, message: 'Network error communicating with gate server.' };
      } finally {
        this.loading = false;
      }
    },

    playBeep(freq, dur) {
      try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        osc.start();
        setTimeout(() => osc.stop(), dur);
      } catch (e) {}
    },

    async toggleCamera() {
      if (this.cameraActive) {
        if (this.stream) this.stream.getTracks().forEach(t => t.stop());
        this.cameraActive = false;
      } else {
        try {
          this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
          this.$refs.scannerVideo.srcObject = this.stream;
          this.cameraActive = true;
        } catch (e) {
          alert('Camera error: ' + e.message);
        }
      }
    }
  }
}
</script>
@endpush
@endsection
