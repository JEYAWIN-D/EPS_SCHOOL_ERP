@extends('layouts.app')

@section('title', 'Students')

@section('content')
<div class="space-y-5" x-data="{
  quickView: false,
  student: {},
  openQuickView(s) { this.student = s; this.quickView = true; }
}">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="page-title">Students</h1>
      <p class="page-subtitle">
        {{ $currentYear?->name }}
        <span class="text-slate-400 mx-1.5">&middot;</span>
        <span class="font-semibold text-slate-700">{{ $students->total() }}</span>
        {{ $students->total() === 1 ? 'student' : 'students' }}
      </p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('students.roll-numbers.index', array_filter(['class_id' => request('class_id')])) }}" class="btn btn-secondary btn-sm" title="Generate alphabetical roll numbers per section, allocate sections, or promote for next academic year">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
        Generate Section &amp; Roll No.
      </a>
      <a href="{{ route('students.directory') }}" class="btn btn-secondary btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        Directory
      </a>
      <a href="{{ route('students.tc-register') }}" class="btn btn-secondary btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        TC Register
      </a>
      <a href="{{ route('students.export', request()->query()) }}" class="btn btn-secondary btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export
      </a>
      <a href="{{ route('students.import') }}" class="btn btn-secondary btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12"/></svg>
        Import
      </a>
    </div>
  </div>

  {{-- Filter Bar --}}
  <form method="GET" action="{{ route('students.index') }}">
    <div class="filter-bar">
      {{-- Search --}}
      <div class="relative flex-1 min-w-48">
        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Name, admission no, roll no…"
               class="input w-full"
               style="padding-left: 2.25rem; padding-right: {{ request('search') ? '2rem' : '0.75rem' }}">
        @if(request('search'))
          <a href="{{ route('students.index', request()->except('search')) }}"
             class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
        @endif
      </div>

      <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>

      <select name="class_id" class="select flex-1 min-w-[120px] sm:flex-none sm:w-36">
        <option value="">All Classes</option>
        @foreach($classes as $cls)
          <option value="{{ $cls->id }}" @selected(request('class_id') == $cls->id)>{{ $cls->name }}</option>
        @endforeach
      </select>

      <select name="section" class="select flex-1 min-w-[110px] sm:flex-none sm:w-32">
        <option value="">All Sections</option>
        @foreach($sectionNames as $secName)
          <option value="{{ $secName }}" @selected(strtoupper(trim(preg_replace('/^section\s*/i', '', request('section', '')))) === strtoupper($secName))>Section {{ $secName }}</option>
        @endforeach
      </select>

      <select name="status" class="select flex-1 min-w-[110px] sm:flex-none sm:w-32">
        <option value="active"      @selected(request('status', 'active') === 'active')>Active</option>
        <option value="inactive"    @selected(request('status') === 'inactive')>Inactive</option>
        <option value="left"        @selected(request('status') === 'left')>Left</option>
        <option value="transferred" @selected(request('status') === 'transferred')>Transferred</option>
        <option value="all"         @selected(request('status') === 'all')>All Status</option>
      </select>

      <select name="sort" class="select flex-1 min-w-[130px] sm:flex-none sm:w-40">
        <option value="latest"    @selected(request('sort', 'latest') === 'latest')>Latest First</option>
        <option value="oldest"    @selected(request('sort') === 'oldest')>Oldest First</option>
        <option value="name_asc"  @selected(request('sort') === 'name_asc')>Name A–Z</option>
        <option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z–A</option>
        <option value="roll_asc"  @selected(request('sort') === 'roll_asc')>By Roll No.</option>
        <option value="adm_asc"   @selected(request('sort') === 'adm_asc')>By Adm. No.</option>
      </select>

      <div class="flex items-center gap-2 w-full sm:w-auto mt-1 sm:mt-0">
        <button type="submit" class="btn btn-primary btn-sm flex-1 sm:flex-none">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
          Filter
        </button>

        @if(request()->hasAny(['search','class_id','section','sort']) || (request('status') && request('status') !== 'active'))
          <a href="{{ route('students.index') }}" class="btn btn-ghost btn-sm text-slate-500">
            Clear
          </a>
        @endif
      </div>
    </div>
  </form>

  @if(request('class_id') || request('section'))
    @php
      $filterCls = $classes->firstWhere('id', request('class_id'));
      $cleanFilterSec = request('section') ? strtoupper(trim(preg_replace('/^section\s*/i', '', request('section')))) : null;
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-2.5 rounded-2xl bg-indigo-50/70 border border-indigo-200/80 text-xs">
      <div class="flex items-center gap-2 text-indigo-950 font-medium">
        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>
          Active Filter:
          <strong class="text-indigo-900 font-bold">{{ $filterCls ? 'Class ' . $filterCls->name : 'All Classes' }}</strong>
          @if($cleanFilterSec) &bull; <strong class="text-indigo-900 font-bold">Section {{ $cleanFilterSec }}</strong>@endif
        </span>
      </div>
      <a href="{{ route('students.roll-numbers.index', array_filter(['class_id' => request('class_id'), 'auto_preview' => 1])) }}"
         class="inline-flex items-center gap-1.5 font-bold text-indigo-700 hover:text-indigo-900 underline underline-offset-2 shrink-0">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
        <span>Preview &amp; Regenerate Roll Numbers for this Section</span>
      </a>
    </div>
  @endif

  {{-- Table --}}
  <div class="table-wrap">
    <table class="w-full">
      <thead>
        <tr>
          <th class="th">Adm. No.</th>
          <th class="th">Student</th>
          <th class="th">Class / Section</th>
          <th class="th">Roll No.</th>
          <th class="th">Father</th>
          <th class="th">Contact</th>
          <th class="th">Status</th>
          <th class="th text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($students as $student)
          @php
            $qv = json_encode([
              'id'            => $student->id,
              'name'          => $student->full_name,
              'admission_no'  => $student->admission_number,
              'class'         => $student->currentEnrollment?->class?->name ?? '—',
              'section'       => $student->currentEnrollment?->section?->name ?? '—',
              'roll'          => $student->currentEnrollment?->roll_number ?? '—',
              'dob'           => $student->dob?->format('d M Y') ?? '—',
              'gender'        => ucfirst($student->gender ?? '—'),
              'blood_group'   => $student->blood_group ?? '—',
              'father_name'   => $student->father_name ?? '—',
              'father_mobile' => $student->father_mobile ?? '—',
              'mother_name'   => $student->mother_name ?? '—',
              'mobile'        => $student->mobile ?? '—',
              'status'        => ucfirst($student->status),
              'initials'      => strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)),
              'photo'         => $student->photo ? asset('storage/'.$student->photo) : null,
              'url_view'      => route('students.show', $student->id),
              'url_edit'      => route('students.edit', $student->id),
              'url_tc'        => route('students.tc.form', $student->id),
            ]);
          @endphp
          <tr class="tr cursor-pointer" @click="openQuickView({{ $qv }})">
            <td class="td">
              <span class="font-mono text-xs font-semibold text-indigo-600">{{ $student->admission_number }}</span>
            </td>
            <td class="td">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-indigo-50 flex items-center justify-center flex-shrink-0 overflow-hidden ring-1 ring-slate-200">
                  @if($student->photo)
                    <img src="{{ asset('storage/'.$student->photo) }}" class="w-full h-full object-cover">
                  @else
                    <span class="text-indigo-600 text-[10px] font-bold">{{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}</span>
                  @endif
                </div>
                <div>
                  <p class="font-semibold text-slate-800 text-sm leading-tight">{{ $student->full_name }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">{{ $student->dob?->format('d M Y') }}</p>
                </div>
              </div>
            </td>
            <td class="td">
              @if($student->currentEnrollment)
                <p class="font-semibold text-slate-800 text-sm leading-tight">{{ $student->currentEnrollment->class?->name }}</p>
                <p class="text-xs text-slate-400">{{ $student->currentEnrollment->section?->name }}</p>
              @else
                <span class="text-slate-300">—</span>
              @endif
            </td>
            <td class="td font-mono text-sm text-slate-600">{{ $student->currentEnrollment?->roll_number ?? '—' }}</td>
            <td class="td text-slate-700">{{ $student->father_name ?: '—' }}</td>
            <td class="td font-mono text-sm text-slate-600">{{ $student->father_mobile ?? $student->mobile ?? '—' }}</td>
            <td class="td">
              <span class="{{ $student->status === 'active' ? 'badge-green' : ($student->status === 'left' ? 'badge-rose' : 'badge-slate') }}">
                {{ ucfirst($student->status) }}
              </span>
            </td>
            <td class="td text-right" @click.stop>
              <div class="flex items-center justify-end gap-0.5">
                <a href="{{ route('students.show', $student->id) }}" class="btn-icon" title="View student">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </a>
                <a href="{{ route('students.edit', $student->id) }}" class="btn-icon" title="Edit student">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <a href="{{ route('students.tc.form', $student->id) }}" class="btn-icon" title="Transfer certificate">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="td">
              <div class="py-14 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <p class="text-sm font-semibold text-slate-700 mb-1">No students found</p>
                <p class="text-xs text-slate-400">Try adjusting your search or filters.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($students->hasPages())
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
      <span>
        Showing <span class="font-semibold text-slate-700">{{ $students->firstItem() }}</span>–<span class="font-semibold text-slate-700">{{ $students->lastItem() }}</span>
        of <span class="font-semibold text-slate-700">{{ $students->total() }}</span> students
      </span>
      <div>{{ $students->links() }}</div>
    </div>
  @endif

  {{-- Quick View Panel --}}
  <div x-show="quickView"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-[2px]"
       @click="quickView=false" style="display:none">
  </div>
  <div x-show="quickView"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full"
       class="fixed right-0 top-0 bottom-0 z-50 w-full sm:w-80 bg-white shadow-2xl flex flex-col overflow-y-auto border-l border-slate-200"
       style="display:none" @click.stop>

    {{-- Panel Header --}}
    <div class="bg-slate-900 p-5 text-white relative flex-shrink-0">
      <button @click="quickView=false"
              class="absolute top-4 right-4 w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <div class="flex items-center gap-3.5">
        <div class="w-14 h-14 rounded-xl bg-white/10 flex items-center justify-center overflow-hidden flex-shrink-0 ring-1 ring-white/20">
          <template x-if="student.photo">
            <img :src="student.photo" class="w-full h-full object-cover">
          </template>
          <template x-if="!student.photo">
            <span class="text-xl font-bold text-white" x-text="student.initials"></span>
          </template>
        </div>
        <div class="min-w-0">
          <p class="font-bold text-base leading-tight truncate" x-text="student.name"></p>
          <p class="text-slate-400 text-xs font-mono mt-0.5" x-text="student.admission_no"></p>
          <span class="inline-block mt-1.5 px-2 py-0.5 bg-white/10 border border-white/20 rounded-full text-[11px] font-semibold"
                x-text="student.status"></span>
        </div>
      </div>
    </div>

    {{-- Panel Body --}}
    <div class="p-5 space-y-5 flex-1">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Academic Info</p>
        <dl class="space-y-2">
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Class</dt>
            <dd class="text-xs font-semibold text-slate-800" x-text="student.class + ' – ' + student.section"></dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Roll No.</dt>
            <dd class="text-xs font-mono text-slate-800" x-text="student.roll"></dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Date of Birth</dt>
            <dd class="text-xs text-slate-800" x-text="student.dob"></dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Gender</dt>
            <dd class="text-xs text-slate-800" x-text="student.gender"></dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Blood Group</dt>
            <dd class="text-xs font-bold text-red-600" x-text="student.blood_group"></dd>
          </div>
        </dl>
      </div>

      <div class="border-t border-slate-100 pt-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Parent / Guardian</p>
        <dl class="space-y-2">
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Father</dt>
            <dd class="text-xs font-semibold text-slate-800 text-right" x-text="student.father_name"></dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Mobile</dt>
            <dd class="text-xs font-mono text-indigo-600" x-text="student.father_mobile || student.mobile || '—'"></dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-xs text-slate-500">Mother</dt>
            <dd class="text-xs text-slate-800" x-text="student.mother_name"></dd>
          </div>
        </dl>
      </div>

      <div class="border-t border-slate-100 pt-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Actions</p>
        <div class="grid grid-cols-3 gap-2">
          <a :href="student.url_view" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-center transition-colors group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span class="text-[11px] font-semibold text-slate-600 group-hover:text-indigo-700">View</span>
          </a>
          <a :href="student.url_edit" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 hover:bg-amber-50 border border-slate-200 hover:border-amber-200 text-center transition-colors group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-amber-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span class="text-[11px] font-semibold text-slate-600 group-hover:text-amber-700">Edit</span>
          </a>
          <a :href="student.url_tc" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-slate-50 hover:bg-green-50 border border-slate-200 hover:border-green-200 text-center transition-colors group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-green-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-[11px] font-semibold text-slate-600 group-hover:text-green-700">TC</span>
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
