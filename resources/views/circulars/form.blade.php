@extends('layouts.app')
@section('title', isset($circular) ? 'Edit Circular — ' . ($circular->reference_no ?? $circular->title) : 'Issue Official Circular / Order')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12" x-data="circularFormHandler()">

  {{-- Header with Breadcrumbs & Action --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
        <a href="{{ route('circulars.index') }}" class="hover:text-maroon-700 transition">Circulars &amp; Orders</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">{{ isset($circular) ? 'Edit' : 'New Directive' }}</span>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-maroon-800 to-amber-600 flex items-center justify-center text-white shadow-md shadow-maroon-900/10">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
          <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
            {{ isset($circular) ? 'Edit Official Circular / Order' : 'Issue Official Circular or Order' }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-500">
            Publish official directives, assign reference numbers, upload signed orders, and track acknowledgements.
          </p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('circulars.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to List
      </a>
    </div>
  </div>

  {{-- Validation Errors Display --}}
  @if($errors->any())
    <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm shadow-xs">
      <div class="flex items-center gap-2 font-bold mb-2">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Please resolve the following errors:</span>
      </div>
      <ul class="list-disc list-inside space-y-1 pl-4 text-xs">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Form Card --}}
  <form method="POST"
        action="{{ isset($circular) ? route('circulars.update', $circular->id) : route('circulars.store') }}"
        enctype="multipart/form-data"
        @submit="syncContent"
        class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-8">
    @csrf
    @if(isset($circular)) @method('PUT') @endif

    {{-- Section 1: Classification & Reference Number --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-maroon-50 text-maroon-700 flex items-center justify-center text-xs font-bold">1</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Directive Header &amp; Numbering</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Directive Subject / Title <span class="text-red-500">*</span>
          </label>
          <input type="text" name="title"
                 value="{{ old('title', $circular->title ?? '') }}"
                 required
                 placeholder="e.g., Mandatory Guidelines for Term 1 Board Examinations 2026"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden transition shadow-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Official Reference No
          </label>
          <input type="text" name="reference_no"
                 value="{{ old('reference_no', $circular->reference_no ?? ($suggestedRef ?? '')) }}"
                 placeholder="e.g., EPS/CIR/2026/015"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono font-bold text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden transition shadow-xs">
          <p class="text-[11px] text-slate-400 mt-1">Leave blank to auto-generate serial number.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Document Type <span class="text-red-500">*</span>
          </label>
          <select name="notice_type" required
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 bg-white focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
            <option value="circular" @selected(old('notice_type', $circular->notice_type ?? 'circular') === 'circular')>Official Circular (General / Academic)</option>
            <option value="order" @selected(old('notice_type', $circular->notice_type ?? '') === 'order')>Official Order / Executive Order</option>
            <option value="directive" @selected(old('notice_type', $circular->notice_type ?? '') === 'directive')>Administrative Directive / Staff Deputation</option>
            <option value="memo" @selected(old('notice_type', $circular->notice_type ?? '') === 'memo')>Executive Office Memo</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Order Category
          </label>
          <input type="text" name="order_category"
                 value="{{ old('order_category', $circular->order_category ?? '') }}"
                 placeholder="e.g., Examination, Government Order (G.O.), Holiday, Fee Structure"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
        </div>
      </div>
    </div>

    {{-- Section 2: Authority, Signatory & Dates --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-xs font-bold">2</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Issuing Authority &amp; Schedule</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Issuing Authority
          </label>
          <input type="text" name="issuing_authority"
                 value="{{ old('issuing_authority', $circular->issuing_authority ?? 'Office of the Principal') }}"
                 placeholder="e.g., Office of the Principal / Management"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Signatory Name
          </label>
          <input type="text" name="signed_by_name"
                 value="{{ old('signed_by_name', $circular->signed_by_name ?? auth()->user()->name) }}"
                 placeholder="e.g., Dr. R. Ramanathan"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Signatory Designation
          </label>
          <input type="text" name="signatory_designation"
                 value="{{ old('signatory_designation', $circular->signatory_designation ?? 'Principal / Authorized Officer') }}"
                 placeholder="e.g., Principal / Correspondent"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Issue / Effective Date <span class="text-red-500">*</span>
          </label>
          <input type="date" name="publish_date"
                 value="{{ old('publish_date', isset($circular) && $circular->publish_date ? $circular->publish_date->toDateString() : today()->toDateString()) }}"
                 required
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Expiry / Validity Date (Optional)
          </label>
          <input type="date" name="expiry_date"
                 value="{{ old('expiry_date', isset($circular) && $circular->expiry_date ? $circular->expiry_date->toDateString() : '') }}"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
        </div>
      </div>
    </div>

    {{-- Section 3: Target Audience & Priority --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs font-bold">3</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Target Audience &amp; Urgency</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Priority / Urgency Level <span class="text-red-500">*</span>
          </label>
          <select name="priority" required
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 bg-white focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
            <option value="normal" @selected(old('priority', $circular->priority ?? 'normal') === 'normal')>Routine / Normal</option>
            <option value="high" @selected(old('priority', $circular->priority ?? '') === 'high')>Important / High Priority</option>
            <option value="urgent" @selected(old('priority', $circular->priority ?? '') === 'urgent')>Urgent (Immediate Effect)</option>
            <option value="low" @selected(old('priority', $circular->priority ?? '') === 'low')>Informational / Low</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Recipient Audience <span class="text-red-500">*</span>
          </label>
          <select name="target_audience" required x-model="targetAudience"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 bg-white focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs">
            <option value="all">🌍 All School Community (Everyone)</option>
            <option value="staff">👨‍🏫 Staff & Faculty Only (Directives / Memos)</option>
            <option value="students">🎓 Students Only</option>
            <option value="parents">👨‍👩‍👧 Parents Only</option>
            <option value="class">🏫 Specific Class / Grade</option>
          </select>
        </div>
      </div>

      <div x-show="targetAudience === 'class'" x-transition class="pt-2">
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Class / Grade</label>
        <select name="target_class_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Select Target Class...</option>
          @foreach($classes as $cls)
            <option value="{{ $cls->id }}" @selected(old('target_class_id', $circular->target_class_id ?? '') == $cls->id)>{{ $cls->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- Section 4: Directive Body / Content --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center text-xs font-bold">4</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Directive Content &amp; Instructions</h3>
      </div>

      <div>
        {{-- Toolbar --}}
        <div class="flex items-center gap-1 p-1.5 bg-slate-50 border border-slate-200 border-b-0 rounded-t-xl text-slate-600 text-xs">
          <button type="button" @click="fmt('bold')" class="px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 font-bold" title="Bold">B</button>
          <button type="button" @click="fmt('italic')" class="px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 italic" title="Italic">I</button>
          <button type="button" @click="fmt('underline')" class="px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 underline" title="Underline">U</button>
          <div class="w-px h-4 bg-slate-300 mx-1"></div>
          <button type="button" @click="fmt('insertUnorderedList')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900" title="Bullet List">• List</button>
          <button type="button" @click="fmt('insertOrderedList')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900" title="Numbered List">1. List</button>
          <div class="w-px h-4 bg-slate-300 mx-1"></div>
          <button type="button" @click="fmt('removeFormat')" class="px-2 py-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-white" title="Clear">Clear</button>
        </div>

        {{-- Visual Editor Box --}}
        <div id="content-editor"
             contenteditable="true"
             class="w-full min-h-[160px] p-4 bg-white border border-slate-200 rounded-b-xl text-sm text-slate-800 leading-relaxed focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs"
             style="white-space: pre-wrap;">{!! old('content', $circular->content ?? '') !!}</div>

        <textarea name="content" id="content-hidden" class="hidden">{{ old('content', $circular->content ?? '') }}</textarea>
      </div>
    </div>

    {{-- Section 5: Official Signed Document / PDF Attachment --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center text-xs font-bold">5</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Signed Document / PDF Attachment</h3>
      </div>

      <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 hover:bg-slate-50 transition">
        <div class="flex flex-col sm:flex-row items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 flex-shrink-0">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
          </div>

          <div class="flex-1 text-center sm:text-left">
            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                   class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-maroon-50 file:text-maroon-700 hover:file:bg-maroon-100 cursor-pointer">
            <p class="text-[11px] text-slate-500 mt-1">Upload scanned copy with signature &amp; stamp. PDF, DOCX, JPG up to 10MB.</p>
            @if(isset($circular) && $circular->attachment)
              <div class="mt-2 text-xs font-semibold text-indigo-700">
                Current File: <a href="{{ Storage::url($circular->attachment) }}" target="_blank" class="underline">View Attached Document</a>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Section 6: Distribution & Compliance Settings --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center text-xs font-bold">6</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Compliance &amp; Pinning Options</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Card: Publish Immediately --}}
        <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 cursor-pointer select-none">
          <input type="checkbox" name="is_published" value="1"
                 @checked(old('is_published', $circular->is_published ?? true))
                 class="w-4 h-4 mt-0.5 rounded-md border-slate-300 text-maroon-700 focus:ring-maroon-600">
          <div>
            <span class="block text-xs font-bold text-slate-900">Publish Immediately</span>
            <span class="block text-[11px] text-slate-500 mt-0.5">
              Make visible to recipient audience right away.
            </span>
          </div>
        </label>

        {{-- Card: Require Acknowledgement --}}
        <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 cursor-pointer select-none">
          <input type="checkbox" name="requires_acknowledgement" value="1"
                 @checked(old('requires_acknowledgement', $circular->requires_acknowledgement ?? true))
                 class="w-4 h-4 mt-0.5 rounded-md border-slate-300 text-maroon-700 focus:ring-maroon-600">
          <div>
            <span class="block text-xs font-bold text-slate-900">Require Acknowledgement</span>
            <span class="block text-[11px] text-slate-500 mt-0.5">
              Recipients must confirm read receipt. Generates compliance log.
            </span>
          </div>
        </label>

        {{-- Card: Pin to Top --}}
        <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 cursor-pointer select-none">
          <input type="checkbox" name="is_pinned" value="1"
                 @checked(old('is_pinned', $circular->is_pinned ?? false))
                 class="w-4 h-4 mt-0.5 rounded-md border-slate-300 text-amber-600 focus:ring-amber-500">
          <div>
            <span class="block text-xs font-bold text-slate-900">Pin to Top</span>
            <span class="block text-[11px] text-slate-500 mt-0.5">
              Keep this order highlighted at top of all directives.
            </span>
          </div>
        </label>
      </div>
    </div>

    {{-- Bottom Actions --}}
    <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
      <a href="{{ route('circulars.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold text-center transition">
        Cancel
      </a>

      <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-900 hover:from-maroon-900 hover:to-black text-white text-xs font-bold shadow-md shadow-maroon-900/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ isset($circular) ? 'Update Directive' : 'Issue & Broadcast Directive' }}</span>
      </button>
    </div>

  </form>
</div>

<script>
function circularFormHandler() {
  return {
    targetAudience: @json(old('target_audience', $circular->target_audience ?? 'all')),
    fmt(cmd) {
      document.execCommand(cmd, false, null);
      document.getElementById('content-editor').focus();
    },
    syncContent() {
      document.getElementById('content-hidden').value = document.getElementById('content-editor').innerHTML;
    }
  }
}
</script>
@endsection
