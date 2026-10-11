@extends('layouts.app')
@section('title', 'Teacher Timetable & Period Reporting')

@section('content')
<div class="space-y-6" x-data="teacherPeriodReporting()">

    {{-- Top Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="page-title">Teacher Timetable</h1>
            <p class="page-subtitle">View weekly teaching schedule and log period attendance &amp; class activity reports</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('academics.timetable') }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Class Timetable
            </a>
            @if(\App\Http\Controllers\Admin\ClassesController::canManageTimetable(auth()->user()))
            <a href="{{ route('classes.index') }}" class="btn btn-primary btn-sm">
                Master Timetable
            </a>
            @endif
        </div>
    </div>

    {{-- Elevated Users: Teacher Selector (Admins/Principal only) --}}
    @if(!$isTeacherUser)
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[240px] max-w-md">
                <label class="label font-bold text-slate-700">Select Teacher to View</label>
                <select name="teacher_id" class="select w-full" onchange="this.form.submit()">
                    <option value="">-- Choose Teacher --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected($teacher && $teacher->id == $t->id)>
                            {{ $t->first_name }} {{ $t->last_name }} ({{ $t->employee_code ?: 'EMP-' . $t->id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">View Schedule</button>
        </form>
    </div>
    @endif

    {{-- Active Teacher Profile Banner --}}
    @if($teacher)
    <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-white text-2xl font-black shadow-inner">
                    {{ strtoupper(substr($teacher->first_name, 0, 1)) }}{{ strtoupper(substr($teacher->last_name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl font-extrabold text-white tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">
                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                        </h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                            {{ $teacher->employee_code ?: 'EMP-' . $teacher->id }}
                        </span>
                        @if($isTeacherUser)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            Logged-in Faculty
                        </span>
                        @endif
                    </div>
                    <p class="text-xs text-indigo-200/90 mt-1">
                        {{ $teacher->designation ?: 'Teaching Faculty' }} &bull; Department: {{ $teacher->department ?: 'Academic Instruction' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-left md:text-right">
                    <p class="text-[11px] uppercase tracking-wider text-indigo-300 font-semibold">Weekly Load</p>
                    <p class="text-2xl font-black text-white mt-0.5">{{ $timetable->flatten()->count() }} <span class="text-xs font-normal text-indigo-200">Periods / Week</span></p>
                </div>
                <button type="button" @click="showPreviousReports = !showPreviousReports"
                        class="px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-xs font-bold text-white transition flex items-center gap-2 cursor-pointer shadow-xs">
                    <span>📋</span>
                    <span x-text="showPreviousReports ? 'Hide Saved Reports' : 'View Saved Reports (' + previousReportsCount + ')'"></span>
                </button>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-white/10 text-xs text-indigo-200/80 flex items-center gap-2">
            <span>💡</span>
            <span>Click any assigned period slot below to log or edit the <strong>Period Report</strong> (Attendance: Presents / Absentees + Description of what was taught).</span>
        </div>
    </div>

    {{-- Period Schedule View --}}
    @if($timetable->isEmpty())
        <div class="card p-12 text-center text-slate-400 space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">📅</div>
            <p class="text-sm font-semibold text-slate-600">No timetable periods assigned to this teacher yet.</p>
            <p class="text-xs text-slate-400">Timetable entries assigned in Master Timetable will appear here automatically.</p>
        </div>
    @else
        @php
            $daysMap = [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday'
            ];
        @endphp

        <div class="space-y-6">
            @foreach($daysMap as $dayNum => $dayName)
                @php $periods = $timetable[$dayNum] ?? collect(); @endphp
                @if($periods->isNotEmpty())
                <div class="card p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <h3 class="font-bold text-slate-900 text-sm tracking-tight">{{ $dayName }}</h3>
                        </div>
                        <span class="text-xs font-semibold text-slate-500">{{ $periods->count() }} {{ Str::plural('Period', $periods->count()) }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                        @foreach($periods as $slot)
                        @php
                            $pStart = $slot->start_time ? \Carbon\Carbon::parse($slot->start_time)->format('h:i A') : '';
                            $pEnd   = $slot->end_time ? \Carbon\Carbon::parse($slot->end_time)->format('h:i A') : '';
                            $isSat  = ($dayNum === 6);
                        @endphp
                        <div @click="openPeriodReportModal({{ $slot->id }})"
                             class="rounded-xl p-3.5 border transition cursor-pointer group hover:shadow-md hover:-translate-y-0.5 {{ $isSat ? 'bg-emerald-50/40 border-emerald-200 hover:border-emerald-400' : 'bg-slate-50/60 border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/30' }}">
                            
                            <div class="flex items-center justify-between gap-1 mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider {{ $isSat ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                    Period {{ $slot->period_number }}
                                </span>
                                @if($slot->room)
                                <span class="text-[10px] font-mono text-slate-500 font-semibold">{{ $slot->room }}</span>
                                @endif
                            </div>

                            <h4 class="font-bold text-xs text-slate-900 leading-snug group-hover:text-indigo-600 transition truncate" title="{{ $slot->subject?->name ?? 'Subject' }}">
                                {{ $slot->subject?->name ?? 'Subject Session' }}
                            </h4>

                            <div class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                <span>{{ $slot->class?->name ? ('Class ' . $slot->class->name) : 'Class' }}</span>
                                <span>&bull;</span>
                                <span>Section {{ $slot->section?->name ?? 'A' }}</span>
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
                                <span class="font-mono">{{ $pStart }} – {{ $pEnd }}</span>
                                <span class="text-indigo-600 font-bold group-hover:underline flex items-center gap-0.5">
                                    <span>Log Report</span>
                                    <span>&rarr;</span>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Previously Saved Reports Section --}}
    <div x-show="showPreviousReports"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="card p-5 space-y-4 shadow-sm"
         style="display: none;">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Previously Saved Period Reports</h3>
                <p class="text-xs text-slate-500">History of classroom reports submitted for periods handled by {{ $teacher->first_name }} {{ $teacher->last_name }}</p>
            </div>
            <button type="button" @click="refreshPreviousReports()"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                <span>🔄</span>
                <span>Refresh</span>
            </button>
        </div>

        <template x-if="previousReportsList.length === 0">
            <div class="py-8 text-center text-slate-400 text-xs">
                No period reports recorded yet. Click on any assigned period above to submit your first report.
            </div>
        </template>

        <template x-if="previousReportsList.length > 0">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-100/80 border-b border-slate-200 font-bold text-slate-700">
                            <th class="py-2.5 px-3">Date</th>
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
                                <td class="py-2.5 px-3 font-semibold text-slate-800 whitespace-nowrap" x-text="rep.formatted_date || rep.date"></td>
                                <td class="py-2.5 px-3 font-medium text-slate-700" x-text="'Class ' + rep.class_name + ' – Sec ' + rep.section_name"></td>
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

    {{-- Period Reporting Modal (Teleported to Body) --}}
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
                <div class="px-6 py-4 flex-shrink-0 flex items-center justify-between bg-slate-900 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white text-base font-bold shadow-xs">
                            📝
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight">Period Class Report</h3>
                            <p class="text-[11px] text-indigo-200 mt-0.5" x-text="activeSlot ? ('Period ' + activeSlot.period_number + ' • ' + (activeSlot.class_name ? 'Class ' + activeSlot.class_name : '') + ' Sec ' + activeSlot.section_name) : ''"></p>
                        </div>
                    </div>
                    <button type="button" @click="reportModalOpen = false"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-600 hover:text-white text-slate-300 flex items-center justify-center transition cursor-pointer">
                        &times;
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 overflow-y-auto space-y-4 text-xs">

                    {{-- Period Context Pill --}}
                    <div class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900" x-text="activeSlot?.subject_name || 'Subject'"></span>
                            <span class="font-mono text-[11px] text-slate-500" x-text="activeSlot?.start_time && activeSlot?.end_time ? (activeSlot.start_time + ' - ' + activeSlot.end_time) : ''"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-600">
                            <span x-text="'Faculty: ' + (activeSlot?.teacher_name || 'Assigned Teacher')"></span>
                            <span x-show="activeSlot?.room" x-text="'Room: ' + activeSlot?.room"></span>
                        </div>
                    </div>

                    {{-- Date Picker --}}
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Date *</label>
                        <input type="date" x-model="reportForm.date" @change="fetchExistingReportForDate()"
                               class="w-full h-9 px-3 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
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
                            Description / Topics Covered *
                        </label>
                        <p class="text-[10px] text-slate-400 mb-1.5">Enter details of the lessons, chapters covered, exercises, or class activities completed during this period.</p>
                        <textarea rows="4" x-model="reportForm.description"
                                  placeholder="Example: Completed Chapter 3 - Parts of Plants. Students completed the worksheet and quiz."
                                  class="w-full p-3 border border-slate-300 rounded-xl text-xs leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"></textarea>
                    </div>

                    {{-- Error message banner if any --}}
                    <div x-show="errorMessage" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2" style="display:none;">
                        <span>⚠️</span>
                        <span x-text="errorMessage"></span>
                    </div>

                    {{-- Notice if updating existing record --}}
                    <div x-show="existingReportId" class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-[11px] font-semibold flex items-center gap-2" style="display:none;">
                        <span>ℹ️</span>
                        <span>An existing report for this period date was loaded. Saving will update the existing record (no duplicates).</span>
                    </div>

                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5 flex-shrink-0">
                    <button type="button" @click="reportModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" @click="submitPeriodReport()"
                            :disabled="isSubmitting || isAttendanceExceeded()"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
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
function teacherPeriodReporting() {
    return {
        showPreviousReports: false,
        previousReportsList: @json($previousReports->map(fn($r) => [
            'id'             => $r->id,
            'timetable_id'   => $r->timetable_id,
            'date'           => $r->date ? $r->date->format('Y-m-d') : '',
            'formatted_date' => $r->date ? $r->date->format('d M Y') : '',
            'class_id'       => $r->class_id,
            'class_name'     => $r->class?->name ?? '',
            'section_id'     => $r->section_id,
            'section_name'   => $r->section?->name ?? '',
            'subject_id'     => $r->subject_id,
            'subject_name'   => $r->subject?->name ?? 'Subject',
            'period_number'  => $r->period_number,
            'present_count'  => $r->student_present_count,
            'absent_count'   => $r->student_absent_count,
            'description'    => $r->description,
        ])),
        get previousReportsCount() {
            return this.previousReportsList.length;
        },

        // Modal State
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

                if (data.report) {
                    this.existingReportId = data.report.id;
                    this.reportForm.present_count = data.report.present_count ?? 0;
                    this.reportForm.absent_count = data.report.absent_count ?? 0;
                    this.reportForm.description = data.report.description ?? '';
                } else if (this.activeSlotTotalStudents > 0) {
                    // Pre-fill present count with full student strength
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
            this.reportForm.present_count = rep.present_count ?? 0;
            this.reportForm.absent_count = rep.absent_count ?? 0;
            this.reportForm.description = rep.description ?? '';
            this.existingReportId = rep.id;
            this.errorMessage = '';

            this.activeSlot = {
                id: rep.timetable_id,
                period_number: rep.period_number,
                class_name: rep.class_name,
                section_name: rep.section_name,
                subject_name: rep.subject_name,
                teacher_name: '{{ $teacher->first_name }} {{ $teacher->last_name }}',
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

                // Add or update locally in previousReportsList
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
                console.error('Error refreshing previous reports:', e);
            }
        }
    };
}
</script>
@endpush
@endsection
