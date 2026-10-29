@props([
  'defaultSize' => 'a4',
  'defaultOrientation' => 'portrait',
  'showSizes' => true,
])

<div x-data="documentPrintEngine('{{ $defaultSize }}', '{{ $defaultOrientation }}')" x-init="init()" class="no-print mb-4 bg-slate-900 text-white rounded-2xl p-3 sm:p-4 shadow-md flex flex-wrap items-center justify-between gap-3 border border-slate-800">
  <div class="flex items-center gap-3 flex-wrap">
    <div class="flex items-center gap-1.5 font-bold text-xs text-amber-400">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print Engine</span>
    </div>

    @if($showSizes)
    {{-- Paper Size --}}
    <div class="flex items-center gap-1.5 text-xs text-slate-300">
      <label for="print-size-select" class="font-medium text-[11px] text-slate-400">Paper:</label>
      <select id="print-size-select" x-model="paperSize" @change="applyClasses()" class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white outline-none cursor-pointer">
        <option value="a5">A5 (148 × 210 mm)</option>
        <option value="a4">A4 (210 × 297 mm)</option>
        <option value="a3">A3 (297 × 420 mm)</option>
        <option value="a2">A2 (420 × 594 mm)</option>
        <option value="a1">A1 (594 × 841 mm)</option>
        <option value="id-card">CR80 ID Card (54 × 86 mm)</option>
      </select>
    </div>

    {{-- Orientation --}}
    <div class="flex items-center gap-1 bg-slate-800 p-0.5 rounded-lg border border-slate-700 text-xs">
      <button type="button" @click="orientation = 'portrait'; applyClasses()" :class="orientation === 'portrait' ? 'bg-maroon-700 text-white font-bold' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer">
        Portrait
      </button>
      <button type="button" @click="orientation = 'landscape'; applyClasses()" :class="orientation === 'landscape' ? 'bg-maroon-700 text-white font-bold' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer">
        Landscape
      </button>
    </div>
    @endif
  </div>

  <div class="flex items-center gap-2">
    <button type="button" @click="window.print()" class="px-4 py-1.5 rounded-xl bg-gradient-to-r from-maroon-700 to-maroon-800 hover:from-maroon-800 hover:to-maroon-900 text-white text-xs font-extrabold shadow-sm inline-flex items-center gap-1.5 cursor-pointer transition">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print / PDF</span>
    </button>
  </div>
</div>

<script>
function documentPrintEngine(initialSize, initialOrientation) {
  return {
    paperSize: initialSize || 'a4',
    orientation: initialOrientation || 'portrait',
    init() {
      this.applyClasses();
    },
    applyClasses() {
      if (typeof window.setPrintPageSetup === 'function') {
        window.setPrintPageSetup(this.paperSize, this.orientation);
      } else {
        const size = (this.paperSize || 'a4').toUpperCase();
        const orientation = this.orientation || 'portrait';
        let style = document.getElementById('global-print-page-style');
        if (!style) {
          style = document.createElement('style');
          style.id = 'global-print-page-style';
          document.head.appendChild(style);
        }
        const m = this.paperSize === 'a5' ? '12mm 15mm 15mm 15mm' : (this.paperSize === 'id-card' ? '3mm' : '16mm 18mm 18mm 18mm');
        const s = this.paperSize === 'id-card' ? (orientation === 'landscape' ? '86mm 54mm' : '54mm 86mm') : `${size} ${orientation}`;
        style.textContent = `@media print { @page { size: ${s}; margin: ${m}; } }`;

        const allSizes = ['paper-a5', 'paper-a4', 'paper-a3', 'paper-a2', 'paper-a1', 'paper-id-card', 'print-id-card', 'portrait', 'landscape'];
        document.body.classList.remove(...allSizes);
        document.body.classList.add(`paper-${this.paperSize}`, orientation);
      }
    }
  };
}
</script>

