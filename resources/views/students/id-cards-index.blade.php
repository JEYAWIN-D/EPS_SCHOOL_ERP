@extends('layouts.app')

@section('title', 'Student ID Card Management — Erode Public School')

@section('content')
<div class="space-y-6" x-data="{
  selectedIds: [],
  selectAll: false,
  orientation: '{{ $orientation }}',
  side: '{{ $side }}',
  reprintModal: false,
  replaceModal: false,
  revokeModal: false,
  activeCardId: null,
  activeCardNumber: '',
  actionReason: '',

  toggleSelectAll(event) {
    if (event.target.checked) {
      this.selectedIds = Array.from(document.querySelectorAll('.student-checkbox')).map(cb => cb.value);
    } else {
      this.selectedIds = [];
    }
  },

  openReprint(cardId, cardNum) {
    this.activeCardId = cardId;
    this.activeCardNumber = cardNum;
    this.actionReason = 'Card replacement due to physical wear/tear';
    this.reprintModal = true;
  },

  openReplace(cardId, cardNum) {
    this.activeCardId = cardId;
    this.activeCardNumber = cardNum;
    this.actionReason = 'Card reported lost / damaged';
    this.replaceModal = true;
  },

  openRevoke(cardId, cardNum) {
    this.activeCardId = cardId;
    this.activeCardNumber = cardNum;
    this.actionReason = 'Student withdrew or card cancelled';
    this.revokeModal = true;
  }
}">

  {{-- ── 1. Top Header Banner ── --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs">
    <div class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-2xl bg-[#892528] text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl font-black text-slate-900 tracking-tight">Student ID Card Management</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#892528]/10 text-[#892528] border border-[#892528]/20">
            CR80 Standard (85.60 × 53.98 mm)
          </span>
          <span class="badge-blue text-[11px] font-semibold">
            {{ $currentYear?->name ?? date('Y') }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Class-wise &amp; individual generation, landscape &amp; portrait orientations, privacy visibility controls, and A4 bulk PDF printing.
        </p>
      </div>
    </div>

    {{-- Top Action Buttons --}}
    <div class="flex items-center gap-2.5 flex-wrap">
      <a href="{{ route('id-cards.settings', ['card_type' => 'student', 'orientation' => $orientation]) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Template Settings
      </a>

      <a href="{{ route('id-cards.audit-logs', ['card_type' => 'student']) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5 text-xs font-bold">
        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Reprint &amp; Audit Logs
      </a>

      {{-- Download Selected / Entire Class PDF --}}
      <a :href="'{{ route('students.id-cards.bulk-pdf') }}?class_id={{ $classFilter }}&section_id={{ $sectionFilter }}&orientation=' + orientation + '&side=' + side + (selectedIds.length > 0 ? '&student_ids=' + selectedIds.join(',') : '')"
         target="_blank"
         class="btn btn-primary btn-sm flex items-center gap-2 text-xs font-black shadow-md bg-[#892528] hover:bg-[#721f22] text-white px-4 py-2 rounded-xl transition cursor-pointer">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span x-text="selectedIds.length > 0 ? ('Download ' + selectedIds.length + ' Selected Cards (PDF)') : 'Download Entire Class Cards (PDF)'"></span>
      </a>
    </div>
  </div>

  {{-- ── 2. KPI Metrics Bar ── --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Eligible</p>
      <p class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ $allEnrollments->count() }}</p>
      <p class="text-[11px] text-slate-500 mt-0.5">Students in active enrollments</p>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Active Cards Issued</p>
      <p class="text-2xl font-black text-emerald-700 mt-1 font-mono">{{ $totalActiveCards }}</p>
      <p class="text-[11px] text-emerald-600/80 mt-0.5">Verified active physical cards</p>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Pending Generation</p>
      <p class="text-2xl font-black text-amber-700 mt-1 font-mono">{{ $totalPendingCards }}</p>
      <p class="text-[11px] text-amber-600/80 mt-0.5">Ready for first issue</p>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Missing Photographs</p>
      <p class="text-2xl font-black text-rose-700 mt-1 font-mono">{{ $missingPhotosCount }}</p>
      <p class="text-[11px] text-rose-600/80 mt-0.5">Photo upload required</p>
    </div>
  </div>

  {{-- ── 3. Filters & Studio Controls ── --}}
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
    <form method="GET" action="{{ route('students.id-card-management') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      
      {{-- Class Filter --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Class / Standard</label>
        <select name="class_id" onchange="this.form.submit()" class="form-input text-xs w-full font-semibold">
          <option value="">All Classes (Whole School)</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ $classFilter == $c->id ? 'selected' : '' }}>
              Class {{ $c->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Section Filter --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Section</label>
        <select name="section_id" onchange="this.form.submit()" class="form-input text-xs w-full font-semibold" {{ $sections->isEmpty() ? 'disabled' : '' }}>
          <option value="">All Sections</option>
          @foreach($sections as $sec)
            <option value="{{ $sec->id }}" {{ $sectionFilter == $sec->id ? 'selected' : '' }}>
              Section {{ $sec->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Card Status Filter --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Card Status</label>
        <select name="card_status" onchange="this.form.submit()" class="form-input text-xs w-full font-semibold">
          <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Records</option>
          <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Cards Only</option>
          <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending Generation</option>
          <option value="missing_photo" {{ $statusFilter === 'missing_photo' ? 'selected' : '' }}>Missing Photo Alert</option>
        </select>
      </div>

      {{-- Search --}}
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Search Student</label>
        <div class="relative">
          <input type="text" name="search" value="{{ $search }}" placeholder="Name, adm no, roll no..." class="form-input text-xs w-full pl-8 font-medium">
          <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
      </div>

      {{-- Submit & Reset --}}
      <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary btn-sm flex-1 font-bold">Apply Filters</button>
        <a href="{{ route('students.id-card-management') }}" class="btn btn-secondary btn-sm" title="Clear Filters">Reset</a>
      </div>

      <input type="hidden" name="orientation" :value="orientation">
      <input type="hidden" name="side" :value="side">
    </form>

    {{-- Layout Switcher Row --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
      <div class="flex items-center gap-2">
        <span class="font-bold text-slate-600">Card Orientation:</span>
        <div class="inline-flex p-1 bg-slate-100 rounded-xl">
          <button type="button" @click="orientation = 'landscape'" :class="orientation === 'landscape' ? 'bg-white shadow-xs text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1 rounded-lg transition text-xs">
            Horizontal (Landscape)
          </button>
          <button type="button" @click="orientation = 'portrait'" :class="orientation === 'portrait' ? 'bg-white shadow-xs text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1 rounded-lg transition text-xs">
            Vertical (Portrait)
          </button>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span class="font-bold text-slate-600">Print Sides:</span>
        <div class="inline-flex p-1 bg-slate-100 rounded-xl">
          <button type="button" @click="side = 'both'" :class="side === 'both' ? 'bg-white shadow-xs text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1 rounded-lg transition text-xs">
            Duplex (Front &amp; Back)
          </button>
          <button type="button" @click="side = 'front'" :class="side === 'front' ? 'bg-white shadow-xs text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1 rounded-lg transition text-xs">
            Front Only
          </button>
        </div>
      </div>

      <div class="text-slate-500 font-medium">
        Showing <span class="font-bold text-slate-800">{{ $filteredEnrollments->count() }}</span> of {{ $allEnrollments->count() }} students
      </div>
    </div>
  </div>

  {{-- ── 4. Student Records Table ── --}}
  <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
            <th class="p-3.5 w-10 text-center">
              <input type="checkbox" @change="toggleSelectAll($event)" class="rounded border-slate-300 text-[#892528] focus:ring-[#892528]">
            </th>
            <th class="p-3.5 w-14 text-center">Photo</th>
            <th class="p-3.5">Student Details</th>
            <th class="p-3.5">Admission / Roll</th>
            <th class="p-3.5">Class &amp; Section</th>
            <th class="p-3.5">ID Card Status</th>
            <th class="p-3.5">Card Number</th>
            <th class="p-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          @forelse($filteredEnrollments as $enrollment)
            @php
              $student = $enrollment->student;
              $card = $enrollment->_card;
              $hasPhoto = !empty($student->photo);
            @endphp
            <tr class="hover:bg-slate-50/70 transition-colors">
              {{-- Checkbox --}}
              <td class="p-3.5 text-center">
                <input type="checkbox" value="{{ $student->id }}" x-model="selectedIds" class="student-checkbox rounded border-slate-300 text-[#892528] focus:ring-[#892528]">
              </td>

              {{-- Photo --}}
              <td class="p-3.5 text-center">
                @if($hasPhoto)
                  <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->full_name }}" class="w-10 h-10 rounded-xl object-cover ring-1 ring-slate-200 mx-auto shadow-2xs">
                @else
                  <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto text-[10px] font-bold" title="Missing Photo Warning">
                    NO PIC
                  </div>
                @endif
              </td>

              {{-- Student Name --}}
              <td class="p-3.5">
                <div class="font-bold text-slate-900 text-sm">
                  {{ $student->full_name }}
                </div>
                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                  @if($student->blood_group)
                    <span class="text-rose-600 font-bold">Blood: {{ $student->blood_group }}</span>
                    <span>&bull;</span>
                  @endif
                  <span>Ph: {{ $student->father_mobile ?? $student->mother_mobile ?? $student->mobile ?? 'N/A' }}</span>
                </div>
              </td>

              {{-- Admission & Roll --}}
              <td class="p-3.5 font-mono">
                <div class="font-bold text-indigo-700">{{ $student->admission_number ?: $student->admission_no }}</div>
                @if($student->roll_number)
                  <div class="text-[10px] text-slate-500">Roll: {{ $student->roll_number }}</div>
                @endif
              </td>

              {{-- Class & Sec --}}
              <td class="p-3.5 font-semibold">
                Class {{ $enrollment->class?->name ?? '—' }}
                @if($enrollment->section)
                  <span class="text-slate-500">({{ $enrollment->section->name }})</span>
                @endif
              </td>

              {{-- Card Status --}}
              <td class="p-3.5">
                @if($card)
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $card->status_badge_class }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $card->isCardValid() ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    {{ ucfirst(str_replace('_', ' ', $card->status)) }}
                  </span>
                  @if($card->reprint_count > 0)
                    <span class="text-[10px] font-mono text-slate-500 block mt-0.5">
                      Reprinted (x{{ $card->reprint_count }})
                    </span>
                  @endif
                @else
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    Not Generated
                  </span>
                @endif
              </td>

              {{-- Card Number --}}
              <td class="p-3.5 font-mono text-[11px]">
                @if($card)
                  <span class="font-bold text-slate-800">{{ $card->card_number }}</span>
                  <div class="text-[10px] text-slate-400">Val: {{ $card->expiry_date?->format('M Y') ?? 'Academic' }}</div>
                @else
                  <span class="text-slate-400 italic">—</span>
                @endif
              </td>

              {{-- Actions --}}
              <td class="p-3.5 text-right space-x-1">
                {{-- Preview & Studio --}}
                <a href="{{ route('students.id-card.studio', ['id' => $student->id, 'orientation' => $orientation, 'side' => $side]) }}"
                   class="btn btn-secondary btn-sm font-bold text-indigo-700 hover:text-indigo-900"
                   title="Open ID Card Studio &amp; Preview">
                  Preview
                </a>

                {{-- Direct PDF download --}}
                <a href="{{ route('students.id-cards.bulk-pdf') }}?student_ids={{ $student->id }}&orientation={{ $orientation }}&side={{ $side }}"
                   target="_blank"
                   class="btn btn-secondary btn-sm font-bold"
                   title="Download Individual PDF">
                  PDF
                </a>

                {{-- Card Lifecycle Dropdown --}}
                @if($card)
                <div x-data="{ open: false }" class="relative inline-block text-left">
                  <button type="button" @click="open = !open" class="btn btn-secondary btn-sm px-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                  </button>
                  <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-slate-200 z-50 py-1 text-left">
                    <button type="button" @click="open = false; openReprint({{ $card->id }}, '{{ $card->card_number }}')" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 font-semibold flex items-center gap-2">
                      <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                      Reprint Card
                    </button>
                    <button type="button" @click="open = false; openReplace({{ $card->id }}, '{{ $card->card_number }}')" class="w-full text-left px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 font-semibold flex items-center gap-2">
                      <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                      Replace (Lost/Damaged)
                    </button>
                    <button type="button" @click="open = false; openRevoke({{ $card->id }}, '{{ $card->card_number }}')" class="w-full text-left px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-50 font-semibold flex items-center gap-2">
                      <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                      Revoke Card
                    </button>
                  </div>
                </div>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="p-8 text-center text-slate-500">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-2">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <p class="font-bold text-slate-800">No students match the chosen criteria</p>
                <p class="text-xs text-slate-400 mt-0.5">Try resetting the class or status filters</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── 5. Reprint Modal ── --}}
  <div x-show="reprintModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-sm">Log ID Card Reprint</h3>
        <button type="button" @click="reprintModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <p class="text-xs text-slate-500">
        Log an official reprint for Card <strong class="text-slate-800 font-mono" x-text="activeCardNumber"></strong>. This will increment the reprint counter and record an audit log.
      </p>
      <form :action="'{{ url('id-cards') }}/' + activeCardId + '/reprint'" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reprint Reason</label>
          <input type="text" name="reason" x-model="actionReason" required class="form-input text-xs w-full">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="reprintModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-[#892528] text-white font-bold">Confirm Reprint</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── 6. Replace Modal ── --}}
  <div x-show="replaceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-sm text-amber-700">Replace Lost / Damaged Card</h3>
        <button type="button" @click="replaceModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <p class="text-xs text-slate-600">
        This action will <strong>deactivate &amp; replace</strong> card <strong class="text-slate-800 font-mono" x-text="activeCardNumber"></strong>. If the old physical card is scanned at verification gates, it will show as <em>REPLACED / DEACTIVATED</em>. A brand new active card with a new verification token will be issued.
      </p>
      <form :action="'{{ url('id-cards') }}/' + activeCardId + '/replace'" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Replacement</label>
          <input type="text" name="reason" x-model="actionReason" required class="form-input text-xs w-full" placeholder="e.g. Lost during transit / physically broken">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="replaceModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-amber-600 hover:bg-amber-700 text-white font-bold">Issue Replacement Card</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── 7. Revoke Modal ── --}}
  <div x-show="revokeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-4 shadow-xl border border-slate-200">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-rose-700 text-sm">Revoke Student Card</h3>
        <button type="button" @click="revokeModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <p class="text-xs text-slate-600">
        Revoking card <strong class="text-slate-800 font-mono" x-text="activeCardNumber"></strong> will immediately disable it. Verification scans will alert security personnel that this card has been revoked.
      </p>
      <form :action="'{{ url('id-cards') }}/' + activeCardId + '/revoke'" method="POST" class="space-y-3">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Revocation Reason</label>
          <input type="text" name="reason" x-model="actionReason" required class="form-input text-xs w-full" placeholder="e.g. TC issued / admission cancelled">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="revokeModal = false" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm bg-rose-600 hover:bg-rose-700 text-white font-bold">Revoke Card</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
