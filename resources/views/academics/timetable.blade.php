@extends('layouts.app')
@section('title', 'Class Timetable')

@section('content')
@php
  $user = auth()->user();
  $canEdit = \App\Http\Controllers\Admin\ClassesController::canManageTimetable($user);
  $isTeacherUser = !$canEdit && ($authEmployeeId || ($user && $user->hasRole('teacher')));
@endphp

<div class="space-y-6" x-data="timetableManager()" @keydown.escape.window="closeAllModals()">

  {{-- Top Navigation & Action Header --}}
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1 class="page-title">Class Timetable</h1>
      <p class="page-subtitle">View complete weekly schedules, assigned handling teachers, and log classroom period reports</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      @if($authEmployeeId)
      <button type="button" @click="showPreviousReports = !showPreviousReports"
              class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-2xs font-bold text-slate-700">
        <span>📋</span>
        <span x-text="showPreviousReports ? 'Hide Saved Reports' : 'My Saved Reports (' + previousReportsList.length + ')'"></span>
      </button>
      @endif

      @if(request('class_id'))
        <a href="{{ route('academics.timetable.pdf', request()->only(['class_id','section_id'])) }}" target="_blank" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 shadow-2xs">
          <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Print PDF</span>
        </a>
      @endif

      @if($canEdit)
        <a href="{{ route('classes.index') }}" class="btn btn-primary btn-sm">
          Master Timetable Editor
        </a>
      @endif
    </div>
  </div>

  {{-- Class & Section Filter Form --}}
  <div class="card p-4 shadow-xs">
    <form method="GET" class="flex items-end gap-3 flex-wrap">
      <div class="min-w-[180px]">
        <label class="label font-bold text-slate-700 mb-1">Select Class</label>
        <select name="class_id" class="select w-full" onchange="this.form.submit()">
          <option value="">-- Choose Class --</option>
          @foreach($classes as $c)
            @php $isAssignedClass = $teacherClassIds->contains($c->id); @endphp
            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
              {{ $c->display_name ?: 'Class ' . $c->name }}
              @if($isAssignedClass && $isTeacherUser) (My Class) @endif
            </option>
          @endforeach
        </select>
      </div>

      <div class="min-w-[180px]">
        <label class="label font-bold text-slate-700 mb-1">Select Section</label>
        <select name="section_id" class="select w-full" onchange="this.form.submit()">
          <option value="">-- All Sections --</option>
          @foreach($sections as $s)
            <option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>
              Section {{ $s->name }}
            </option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">View Timetable</button>
    </form>
  </div>

  {{-- Previously Saved Period Reports History Drawer (Teacher Scoped) --}}
  @if($authEmployeeId)
  <div x-show="showPreviousReports"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       class="card p-5 space-y-4 shadow-sm border border-indigo-100 bg-indigo-50/20"
       style="display: none;">
    <div class="flex items-center justify-between border-b border-indigo-100 pb-3">
      <div>
        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
          <span>📋</span>
          <span>My Previously Saved Period Reports</span>
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">History of classroom reports logged for periods assigned to your teaching account</p>
      </div>
      <button type="button" @click="refreshPreviousReports()"
              class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition flex items-center gap-1 shadow-2xs">
        <span>🔄</span>
        <span>Refresh</span>
      </button>
    </div>

    <template x-if="previousReportsList.length === 0">
      <div class="py-6 text-center text-slate-400 text-xs">
        No period reports logged yet. Click on any of your assigned periods in the timetable grid below to submit your first report.
      </div>
    </template>

    <template x-if="previousReportsList.length > 0">
      <div class="overflow-x-auto bg-white rounded-xl border border-slate-200 shadow-2xs">
        <table class="w-full text-left border-collapse text-xs min-w-[700px]">
          <thead>
            <tr class="bg-slate-100/90 border-b border-slate-200 font-bold text-slate-700">
              <th class="py-2.5 px-3">Date &amp; Day</th>
              <th class="py-2.5 px-3">Class &amp; Section</th>
              <th class="py-2.5 px-3">Period</th>
              <th class="py-2.5 px-3">Subject</th>
              <th class="py-2.5 px-3 text-center">Attendance</th>
              <th class="py-2.5 px-3">Description / Topics Covered</th>
              <th class="py-2.5 px-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <template x-for="rep in previousReportsList" :key="rep.id">
              <tr class="hover:bg-slate-50/75 transition">
                <td class="py-2.5 px-3 font-semibold text-slate-800 whitespace-nowrap">
                  <span x-text="rep.formatted_date || rep.date"></span>
                  <span class="text-[10px] text-slate-400 block" x-text="rep.day || ''"></span>
                </td>
                <td class="py-2.5 px-3 font-medium text-slate-700" x-text="'Class ' + (rep.class_name || '') + ' – Sec ' + (rep.section_name || '')"></td>
                <td class="py-2.5 px-3 font-bold text-indigo-700" x-text="'Period ' + rep.period_number"></td>
                <td class="py-2.5 px-3 font-semibold text-slate-800" x-text="rep.subject_name"></td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                    P: <span x-text="rep.present_count"></span>
                  </span>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 ml-1">
                    A: <span x-text="rep.absent_count"></span>
                  </span>
                </td>
                <td class="py-2.5 px-3 text-slate-600 max-w-xs truncate" :title="rep.description" x-text="rep.description"></td>
                <td class="py-2.5 px-3 text-right">
                  <button type="button" @click="loadExistingReportForEdit(rep)"
                          class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">
                    Edit / View
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>
  </div>
  @endif

  {{-- Timetable Grid Display --}}
  @if(count($timetable))

  {{-- Informative Role-Based Status Banner --}}
  <div class="flex items-center justify-between text-xs px-4 py-2.5 rounded-xl border flex-wrap gap-2 shadow-2xs {{ $canEdit ? 'bg-slate-50 border-slate-200 text-slate-600' : ($isTeacherUser ? 'bg-blue-50/70 border-blue-200 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-600') }}">
    <div class="flex items-center gap-2">
      @if($canEdit)
        <span class="font-semibold">💡 Click on any period box to edit its subject, assigned handling teacher, or timings.</span>
      @elseif($isTeacherUser)
        <span class="text-blue-600 font-bold">👨‍🏫 Faculty Timetable View:</span>
        <span>All periods are visible for complete schedule context. Periods assigned to you are highlighted with <strong class="text-blue-800">Your Period</strong>. Click your period to log or view Period Reports. Other teachers' periods remain view-only.</span>
      @else
        <span class="font-semibold text-slate-700">👁️ View-Only Timetable:</span>
        <span>Modification controls are restricted to authorized Timetable Management.</span>
      @endif
    </div>
    <span class="text-indigo-600 font-bold shrink-0">8 Periods Daily • 09:10 AM – 03:00 PM</span>
  </div>

  {{-- The Weekly Schedule Table --}}
  <div class="card overflow-x-auto shadow-sm p-0">
    @php
      $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
      $periods = range(1, $timetable->flatten()->max('period_number') ?: 8);
      $periodTimingsMap = [
        1 => '09:10–09:50',
        2 => '09:50–10:30',
        3 => '10:40–11:20',
        4 => '11:20–12:00',
        5 => '12:00–12:40',
        6 => '01:20–02:00',
        7 => '02:10–02:50',
        8 => '02:50–03:00',
      ];
    @endphp

    <table class="min-w-full text-sm border-collapse">
      <thead>
        <tr class="bg-slate-100/90 border-b border-slate-200">
          <th class="border-r border-slate-200 px-4 py-3 text-slate-700 font-bold w-28 text-left uppercase text-xs tracking-wider">Day</th>
          @foreach($periods as $p)
            <th class="border-r border-slate-200 px-3 py-2.5 text-slate-800 font-bold text-center last:border-r-0">
              <div class="text-xs font-black">{{ $p === 8 ? 'Diary' : ('Period ' . $p) }}</div>
              <div class="text-[9.5px] font-mono text-slate-400 font-normal mt-0.5">{{ $periodTimingsMap[$p] ?? '' }}</div>
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach($days as $day)
        @php
          $dayMap = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];
          $dayNum = $dayMap[$day] ?? null;
          $daySlots = $timetable[$day] ?? ($dayNum ? ($timetable[$dayNum] ?? collect()) : collect());
          $isSat = ($day === 'Saturday');
        @endphp
        <tr class="hover:bg-slate-50/70 transition {{ $isSat ? 'bg-emerald-50/20' : '' }}">
          <td class="border-r border-slate-200 px-4 py-3.5 font-bold text-slate-800 bg-slate-50/60 align-top">
            <div class="flex items-center gap-1.5">
              <span>{{ $day }}</span>
              @if($isSat)
                <span class="px-1.5 py-0.2 rounded text-[8.5px] font-extrabold bg-emerald-100 text-emerald-800">CLUBS</span>
              @endif
            </div>
          </td>

          @foreach($periods as $p)
          @php
            $slot = $daySlots->firstWhere('period_number', $p);
            $type = $slot?->period_type ?? 'class';
            $slotTeacherId = $slot?->teacher_id ?? $slot?->employee_id;
            $slotTeacherName = $slot?->teacher?->full_name ?: ($slot?->teacher?->first_name ? $slot->teacher->first_name . ' ' . $slot->teacher->last_name : null);
            $isMySlot = $isTeacherUser && $slot && $authEmployeeId && ((int)$slotTeacherId === (int)$authEmployeeId);
            $isInteractive = $canEdit || $isMySlot;
          @endphp

          <td class="border-r border-slate-200 px-2.5 py-2.5 text-center transition align-top last:border-r-0
            {{ $isInteractive ? 'cursor-pointer hover:shadow-xs' : 'cursor-default' }}
            {{ $isMySlot ? 'bg-blue-50/50 hover:bg-blue-100/60 ring-2 ring-blue-400/40' : '' }}
            {{ $type === 'break' ? 'bg-amber-50/60' : ($type === 'lunch' ? 'bg-emerald-50/60' : ($type === 'free' ? 'bg-slate-50/60' : '')) }}"
            @if($canEdit)
              @click="openEditor('{{ $day }}', {{ $p }}, '{{ request('class_id') }}', '{{ request('section_id') }}', {{ $slot ? "'{$slot->subject_id}'" : 'null' }}, {{ $slotTeacherId ? "'{$slotTeacherId}'" : 'null' }}, '{{ $slot?->period_type ?? 'class' }}', '{{ $slot?->start_time ?? '' }}', '{{ $slot?->end_time ?? '' }}')"
            @elseif($isMySlot && $slot)
              @click="openPeriodReportModal({{ $slot->id }})"
            @endif>

            @if($slot)
              @if(in_array($type, ['break','lunch','free']))
                <span class="text-xs font-bold uppercase tracking-wider {{ $type === 'break' ? 'text-amber-700' : ($type === 'lunch' ? 'text-emerald-700' : 'text-slate-400') }}">
                  {{ ucfirst($type) }}
                </span>
                @if($slot->start_time && $slot->end_time)
                  <span class="block text-[10px] text-slate-400 mt-0.5 font-mono">{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i') }}–{{ \Carbon\Carbon::parse($slot->end_time)->format('h:i') }}</span>
                @endif
              @else
                {{-- Subject Name --}}
                <div class="font-bold text-slate-900 text-xs tracking-tight leading-snug">
                  {{ $slot->subject?->name ?? 'Academic Class' }}
                </div>

                {{-- Teacher Display Badge --}}
                <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-semibold max-w-[145px] truncate {{ $isMySlot ? 'bg-blue-100 text-blue-900 font-bold border border-blue-200' : 'bg-slate-100 border border-slate-200/80 text-slate-700' }}"
                     title="{{ $slotTeacherName ?: 'Teacher not specified' }}">
                  <svg class="w-3 h-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span class="truncate">{{ $slotTeacherName ?: 'Faculty In-charge' }}</span>
                </div>

                {{-- Clickable Assigned Period Badge --}}
                @if($isMySlot)
                <div class="mt-1.5 flex items-center justify-center">
                  <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-600 text-white shadow-2xs">
                    <span>📝</span>
                    <span>Your Period &bull; Report</span>
                  </span>
                </div>
                @endif

                @if($slot->room)
                  <div class="mt-1 text-[9.5px] font-mono text-slate-400">
                    <span>{{ $slot->room }}</span>
                  </div>
                @endif
              @endif
            @else
              @if($canEdit)
                <span class="text-slate-300 hover:text-indigo-600 text-xs font-medium">+ Add</span>
              @else
                <span class="text-slate-300 text-xs">—</span>
              @endif
            @endif
          </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($canEdit)
  {{-- Timetable Management Master Edit Modal --}}
  <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="close">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm" @click.stop>
      <div class="flex items-center justify-between p-4 border-b">
        <h3 class="font-semibold text-slate-700" x-text="'Edit ' + day + ' Period ' + period"></h3>
        <button @click="close" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
      </div>
      <form method="POST" action="{{ route('academics.timetable.save') }}" class="p-4 space-y-3">
        @csrf
        <input type="hidden" name="class_id" :value="classId">
        <input type="hidden" name="entries[0][class_id]" :value="classId">
        <input type="hidden" name="entries[0][section_id]" :value="sectionId">
        <input type="hidden" name="entries[0][day_of_week]" :value="day">
        <input type="hidden" name="entries[0][period_number]" :value="period">
        <div>
          <label class="label">Period Type</label>
          <select name="entries[0][period_type]" class="select w-full" x-model="periodType">
            <option value="class">📚 Class Session</option>
            <option value="activity">🎨 Co-Curricular / Activity</option>
            <option value="practical">🔬 Practical / Lab Session</option>
            <option value="free">⚡ Free / Study Period</option>
            <option value="break">☕ Interval / Break</option>
            <option value="lunch">🍱 Lunch Break</option>
          </select>
        </div>
        <div x-show="periodType !== 'free' && periodType !== 'break' && periodType !== 'lunch'">
          <div class="flex items-center justify-between mb-1">
            <label class="label mb-0">Subject *</label>
            <span class="text-[10px] font-bold text-blue-600 uppercase" x-show="periodType === 'class'">Academic</span>
            <span class="text-[10px] font-bold text-purple-600 uppercase" x-show="periodType === 'activity'">Activity</span>
            <span class="text-[10px] font-bold text-cyan-600 uppercase" x-show="periodType === 'practical'">Practical</span>
          </div>

          {{-- Class Subject Selection --}}
          <select x-show="periodType === 'class'" name="entries[0][subject_id]" class="select w-full" x-model="subjectId">
            <option value="">— Select Academic Subject —</option>
            <optgroup label="📚 Core Academic & Languages">
              @foreach($theorySubjects as $sub)
              <option value="{{ $sub->id }}">{{ $sub->name }}</option>
              @endforeach
            </optgroup>
            @if($subjects->reject(fn($s) => in_array($s->type, ['theory', 'language']))->isNotEmpty())
              <optgroup label="── All Other Subjects ──">
                @foreach($subjects->reject(fn($s) => in_array($s->type, ['theory', 'language'])) as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
              </optgroup>
            @endif
          </select>

          {{-- Activity Subject Selection --}}
          <select x-show="periodType === 'activity'" name="entries[0][subject_id]" class="select w-full" x-model="subjectId">
            <option value="">— Select Activity / Co-Curricular —</option>
            <optgroup label="🎨 Co-Curricular, Sports & Wellness">
              @foreach($activitySubjects as $sub)
              <option value="{{ $sub->id }}">{{ $sub->name }}</option>
              @endforeach
            </optgroup>
            @if($subjects->reject(fn($s) => $s->type === 'activity' || $s->is_coscholastic)->isNotEmpty())
              <optgroup label="── All Other Subjects ──">
                @foreach($subjects->reject(fn($s) => $s->type === 'activity' || $s->is_coscholastic) as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
              </optgroup>
            @endif
          </select>

          {{-- Practical Subject Selection --}}
          <select x-show="periodType === 'practical'" name="entries[0][subject_id]" class="select w-full" x-model="subjectId">
            <option value="">— Select Practical / Lab Subject —</option>
            <optgroup label="🔬 Practical & Computer Labs">
              @foreach($practicalSubjects as $sub)
              <option value="{{ $sub->id }}">{{ $sub->name }}</option>
              @endforeach
            </optgroup>
            @if($subjects->reject(fn($s) => $s->type === 'practical' || stripos($s->name, 'computer') !== false || stripos($s->name, 'lab') !== false)->isNotEmpty())
              <optgroup label="── All Other Subjects ──">
                @foreach($subjects->reject(fn($s) => $s->type === 'practical' || stripos($s->name, 'computer') !== false || stripos($s->name, 'lab') !== false) as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
              </optgroup>
            @endif
          </select>
        </div>

        <div x-show="periodType !== 'free' && periodType !== 'break' && periodType !== 'lunch'">
          <div class="flex items-center justify-between mb-1">
            <label class="label mb-0">Handling Teacher *</label>
            <span class="text-[10px] font-bold text-blue-600 uppercase" x-show="periodType === 'class'">Faculty</span>
            <span class="text-[10px] font-bold text-purple-600 uppercase" x-show="periodType === 'activity'">Coach</span>
            <span class="text-[10px] font-bold text-cyan-600 uppercase" x-show="periodType === 'practical'">Lab Incharge</span>
          </div>

          {{-- Class Teacher --}}
          <select x-show="periodType === 'class'" name="entries[0][teacher_id]" class="select w-full" x-model="teacherId">
            <option value="">— Select Handling Teacher —</option>
            @foreach($teachers as $t)
            <option value="{{ $t->id }}">{{ $t->full_name ?: $t->first_name . ' ' . $t->last_name }} ({{ $t->employee_code ?: 'EMP-' . $t->id }})</option>
            @endforeach
          </select>

          {{-- Activity Teacher --}}
          <select x-show="periodType === 'activity'" name="entries[0][teacher_id]" class="select w-full" x-model="teacherId">
            <option value="">— Select Activity / Sports Coach —</option>
            <optgroup label="🏃 PET, Sports & Activity Coaches">
              @foreach($activityTeachers as $t)
              <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->designation ?: 'Coach' }})</option>
              @endforeach
            </optgroup>
            @if($teachers->diff($activityTeachers)->isNotEmpty())
              <optgroup label="── Other Teachers ──">
                @foreach($teachers->diff($activityTeachers) as $t)
                <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}</option>
                @endforeach
              </optgroup>
            @endif
          </select>

          {{-- Practical Teacher --}}
          <select x-show="periodType === 'practical'" name="entries[0][teacher_id]" class="select w-full" x-model="teacherId">
            <option value="">— Select Lab / Computer Faculty —</option>
            <optgroup label="💻 Lab Incharges & Computer Faculty">
              @foreach($labTeachers as $t)
              <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->designation ?: 'Lab Incharge' }})</option>
              @endforeach
            </optgroup>
            @if($teachers->diff($labTeachers)->isNotEmpty())
              <optgroup label="── Other Teachers ──">
                @foreach($teachers->diff($labTeachers) as $t)
                <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}</option>
                @endforeach
              </optgroup>
            @endif
          </select>
        </div>

        <div x-show="periodType === 'free'" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs font-semibold text-amber-800 flex items-center justify-between">
          <span>⚡ Free / Self-Study Period</span>
          <span class="text-[10px] bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded uppercase font-bold">No Teacher Required</span>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Start Time</label>
            <input type="time" name="entries[0][start_time]" class="input w-full" x-model="startTime">
          </div>
          <div>
            <label class="label">End Time</label>
            <input type="time" name="entries[0][end_time]" class="input w-full" x-model="endTime">
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button type="submit" class="btn btn-primary flex-1">Save Period</button>
          <button type="button" @click="close" class="btn btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endif

  @else
  <div class="card text-center py-12 text-slate-400 space-y-2">
    <div class="text-3xl">📅</div>
    <p class="font-semibold text-slate-600">Select a class and section to view timetable.</p>
    <p class="text-xs text-slate-400">Choose from the dropdown filters above to inspect the complete schedule.</p>
  </div>
  @endif

  {{-- Teacher Period Reporting Modal (Teleported to Body) --}}
  <template x-teleport="body">
    <div x-show="reportModalOpen"
         @keydown.window.escape="reportModalOpen = false"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-5"
         style="display:none; background:rgba(15, 23, 42, 0.75); backdrop-filter:blur(4px);"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

      <div class="bg-white flex flex-col w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
           @click.stop>

        {{-- Modal Header --}}
        <div class="px-6 py-4 shrink-0 flex items-center justify-between bg-slate-900 text-white">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white text-base font-bold shadow-xs">
              📝
            </div>
            <div>
              <h3 class="text-base font-extrabold tracking-tight">Period Class Report</h3>
              <p class="text-[11px] text-blue-200 mt-0.5" x-text="activeSlot ? ('Period ' + activeSlot.period_number + ' • ' + (activeSlot.class_name ? 'Class ' + activeSlot.class_name : '') + ' Sec ' + (activeSlot.section_name || '')) : ''"></p>
            </div>
          </div>
          <button type="button" @click="reportModalOpen = false"
                  class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-600 hover:text-white text-slate-300 flex items-center justify-center transition cursor-pointer">
            &times;
          </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 overflow-y-auto space-y-4 text-xs">

          {{-- Period Details Context Pill --}}
          <div class="p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm" x-text="activeSlot?.subject_name || 'Subject'"></span>
              <span class="font-mono text-xs text-blue-700 font-bold" x-text="activeSlot?.start_time && activeSlot?.end_time ? (activeSlot.start_time + ' - ' + activeSlot.end_time) : ''"></span>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-600 flex-wrap gap-2">
              <span x-text="'Faculty: ' + (activeSlot?.teacher_name || 'Assigned Teacher')"></span>
              <span x-text="'Day: ' + (activeSlot?.day_name || '')"></span>
              <span x-show="activeSlot?.room" x-text="'Room: ' + activeSlot?.room"></span>
            </div>
          </div>

          {{-- Date Picker --}}
          <div>
            <label class="block font-bold text-slate-700 mb-1">Date *</label>
            <input type="date" x-model="reportForm.date" @change="fetchExistingReportForDate()"
                   class="w-full h-9 px-3 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
          </div>

          {{-- Student Strength & Attendance Counts --}}
          <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <div class="flex items-center justify-between">
              <label class="font-bold text-slate-800">Attendance Counts</label>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700"
                    x-text="'Section Strength: ' + (activeSlotTotalStudents || 0) + ' Students'"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Present Count *</label>
                <input type="number" min="0" x-model.number="reportForm.present_count"
                       class="w-full h-9 px-3 border rounded-xl text-xs font-bold text-emerald-950 focus:ring-2 focus:ring-emerald-500"
                       :class="isAttendanceExceeded() ? 'border-rose-400 bg-rose-50/50' : 'border-slate-300 bg-white'">
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Absent Count *</label>
                <input type="number" min="0" x-model.number="reportForm.absent_count"
                       class="w-full h-9 px-3 border rounded-xl text-xs font-bold text-rose-950 focus:ring-2 focus:ring-rose-500"
                       :class="isAttendanceExceeded() ? 'border-rose-400 bg-rose-50/50' : 'border-slate-300 bg-white'">
              </div>
            </div>

            {{-- Total vs Strength Validation Feedback --}}
            <div class="flex items-center justify-between text-[11px] pt-1">
              <span class="text-slate-500 font-medium">
                Total Counted: <strong x-text="(parseInt(reportForm.present_count || 0) + parseInt(reportForm.absent_count || 0))"></strong>
              </span>
              <template x-if="isAttendanceExceeded()">
                <span class="font-bold text-rose-600 flex items-center gap-1">
                  <span>⚠️</span>
                  <span>Exceeds section strength!</span>
                </span>
              </template>
              <template x-if="!isAttendanceExceeded() && (parseInt(reportForm.present_count || 0) + parseInt(reportForm.absent_count || 0)) > 0">
                <span class="font-semibold text-emerald-600 flex items-center gap-1">
                  <span>✓</span>
                  <span>Valid attendance</span>
                </span>
              </template>
            </div>
          </div>

          {{-- Description / Topics Covered --}}
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Description / What Was Taught *
            </label>
            <p class="text-[10px] text-slate-400 mb-1.5">Enter details of the lessons, chapters covered, exercises, or class activities completed during this period.</p>
            <textarea rows="4" x-model="reportForm.description"
                      placeholder="Example: Completed Chapter 3 and conducted the worksheet activity."
                      class="w-full p-3 border border-slate-300 rounded-xl text-xs leading-relaxed focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
          </div>

          {{-- Error message banner if any --}}
          <div x-show="errorMessage" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2" style="display:none;">
            <span>⚠️</span>
            <span x-text="errorMessage"></span>
          </div>

          {{-- Notice if updating existing record --}}
          <div x-show="existingReportId" class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-[11px] font-semibold flex items-center gap-2" style="display:none;">
            <span>ℹ️</span>
            <span>A previously saved report for this date was loaded. Saving will update the existing record (no duplicates).</span>
          </div>

        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0">
          <button type="button" @click="reportModalOpen = false"
                  class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition cursor-pointer">
            Cancel
          </button>
          <button type="button" @click="submitPeriodReport()"
                  :disabled="isSubmitting || isAttendanceExceeded()"
                  class="px-5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="isSubmitting" class="animate-spin">🌀</span>
            <span x-show="!isSubmitting">💾</span>
            <span x-text="isSubmitting ? 'Saving...' : (existingReportId ? 'Update Report' : 'Save Report')"></span>
          </button>
        </div>

      </div>
    </div>
  </template>

</div>

@push('scripts')
<script>
function timetableManager() {
  return {
    // ── Master Timetable Editor State (for Admins) ──
    open: false,
    day: '',
    period: 0,
    classId: '',
    sectionId: '',
    subjectId: '',
    teacherId: '',
    periodType: 'class',
    startTime: '',
    endTime: '',

    openEditor(day, period, classId, sectionId, subjectId, teacherId, periodType, startTime, endTime) {
      this.day = day;
      this.period = period;
      this.classId = classId;
      this.sectionId = sectionId;
      this.subjectId = subjectId || '';
      this.teacherId = teacherId || '';
      this.periodType = periodType || 'class';
      this.startTime = startTime;
      this.endTime = endTime;
      this.open = true;
    },
    close() {
      this.open = false;
    },

    // ── Teacher Period Reporting State ──
    showPreviousReports: false,
    previousReportsList: @json($formattedPreviousReports ?? []),

    reportModalOpen: false,
    activeSlot: null,
    activeSlotTotalStudents: 0,
    existingReportId: null,
    isSubmitting: false,
    errorMessage: '',

    reportForm: {
      timetable_id: null,
      class_id: null,
      section_id: null,
      subject_id: null,
      period_number: null,
      date: new Date().toISOString().split('T')[0],
      day: '',
      present_count: 0,
      absent_count: 0,
      description: ''
    },

    isAttendanceExceeded() {
      if (!this.activeSlotTotalStudents || this.activeSlotTotalStudents <= 0) return false;
      const p = parseInt(this.reportForm.present_count || 0, 10);
      const a = parseInt(this.reportForm.absent_count || 0, 10);
      return (p + a) > this.activeSlotTotalStudents;
    },

    closeAllModals() {
      this.open = false;
      this.reportModalOpen = false;
    },

    async openPeriodReportModal(timetableId) {
      this.errorMessage = '';
      this.existingReportId = null;
      this.activeSlot = null;
      this.activeSlotTotalStudents = 0;
      this.reportForm.date = new Date().toISOString().split('T')[0];
      this.reportForm.present_count = 0;
      this.reportForm.absent_count = 0;
      this.reportForm.description = '';

      try {
        const res = await fetch(`{{ url('academics/timetable/period-report') }}/${timetableId}?date=${this.reportForm.date}`);
        const data = await res.json();

        if (!data.success) {
          alert(data.message || 'Unable to open period report for this slot.');
          return;
        }

        this.activeSlot = data.timetable;
        this.activeSlotTotalStudents = data.total_students || 0;

        this.reportForm.timetable_id = data.timetable.id;
        this.reportForm.class_id = data.timetable.class_id;
        this.reportForm.section_id = data.timetable.section_id;
        this.reportForm.subject_id = data.timetable.subject_id;
        this.reportForm.period_number = data.timetable.period_number;
        this.reportForm.day = data.timetable.day_name || '';

        if (data.report) {
          this.existingReportId = data.report.id;
          this.reportForm.present_count = data.report.present_count ?? 0;
          this.reportForm.absent_count = data.report.absent_count ?? 0;
          this.reportForm.description = data.report.description ?? '';
        } else if (this.activeSlotTotalStudents > 0) {
          this.reportForm.present_count = this.activeSlotTotalStudents;
          this.reportForm.absent_count = 0;
        }

        this.reportModalOpen = true;
      } catch (err) {
        console.error('Fetch report error:', err);
        alert('An error occurred while loading period details: ' + err.message);
      }
    },

    async fetchExistingReportForDate() {
      if (!this.activeSlot || !this.reportForm.date) return;
      try {
        const res = await fetch(`{{ url('academics/timetable/period-report') }}/${this.activeSlot.id}?date=${this.reportForm.date}`);
        const data = await res.json();
        if (data.success && data.report) {
          this.existingReportId = data.report.id;
          this.reportForm.present_count = data.report.present_count ?? 0;
          this.reportForm.absent_count = data.report.absent_count ?? 0;
          this.reportForm.description = data.report.description ?? '';
        } else {
          this.existingReportId = null;
        }
      } catch (e) {
        console.error('Error fetching date report:', e);
      }
    },

    loadExistingReportForEdit(rep) {
      this.reportForm.timetable_id = rep.timetable_id;
      this.reportForm.class_id = rep.class_id;
      this.reportForm.section_id = rep.section_id;
      this.reportForm.subject_id = rep.subject_id;
      this.reportForm.period_number = rep.period_number;
      this.reportForm.date = rep.date;
      this.reportForm.day = rep.day || '';
      this.reportForm.present_count = rep.present_count ?? 0;
      this.reportForm.absent_count = rep.absent_count ?? 0;
      this.reportForm.description = rep.description ?? '';
      this.existingReportId = rep.id;
      this.errorMessage = '';

      this.activeSlot = {
        id: rep.timetable_id,
        period_number: rep.period_number,
        day_name: rep.day || '',
        class_name: rep.class_name,
        section_name: rep.section_name,
        subject_name: rep.subject_name,
        teacher_name: 'Assigned Faculty',
      };

      this.reportModalOpen = true;
    },

    async submitPeriodReport() {
      this.errorMessage = '';

      if (this.reportForm.present_count === null || this.reportForm.present_count < 0) {
        this.errorMessage = 'Present count must be 0 or greater.';
        return;
      }
      if (this.reportForm.absent_count === null || this.reportForm.absent_count < 0) {
        this.errorMessage = 'Absent count must be 0 or greater.';
        return;
      }
      if (!this.reportForm.description || !this.reportForm.description.trim()) {
        this.errorMessage = 'Please enter a description of what was covered during the period.';
        return;
      }
      if (this.isAttendanceExceeded()) {
        this.errorMessage = 'Present count + Absent count cannot exceed total section strength.';
        return;
      }

      this.isSubmitting = true;

      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('{{ route("academics.timetable.period-report.save") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.reportForm)
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
          this.errorMessage = data.message || 'Failed to save period report.';
          return;
        }

        if (data.report) {
          const idx = this.previousReportsList.findIndex(r => r.id === data.report.id);
          if (idx >= 0) {
            this.previousReportsList[idx] = data.report;
          } else {
            this.previousReportsList.unshift(data.report);
          }
        }

        this.reportModalOpen = false;
        alert(data.message || 'Period report saved successfully!');
      } catch (err) {
        console.error('Submit report error:', err);
        this.errorMessage = 'Network error while saving report: ' + err.message;
      } finally {
        this.isSubmitting = false;
      }
    },

    async refreshPreviousReports() {
      try {
        const res = await fetch('{{ route("academics.timetable.period-reports.list") }}');
        const data = await res.json();
        if (data.success && data.reports) {
          this.previousReportsList = data.reports;
        }
      } catch (e) {
        console.error('Error refreshing reports:', e);
      }
    }
  };
}
</script>
@endpush
@endsection
