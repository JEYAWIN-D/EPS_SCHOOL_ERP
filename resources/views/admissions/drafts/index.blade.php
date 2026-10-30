@extends('layouts.app')

@section('title', 'Admission Drafts & Auto-Saved Applications — Erode Public School')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-16">

  {{-- ── Breadcrumbs & Back Nav ────────────────────────────────────────── --}}
  <div class="flex items-center justify-between gap-4">
    <nav class="text-xs font-semibold text-slate-400 flex items-center gap-2">
      <a href="{{ route('admissions.index') }}" class="hover:text-slate-700 transition">Admissions</a>
      <span>/</span>
      <span class="text-slate-800">Auto-Saved Drafts</span>
    </nav>
    <a href="{{ route('admissions.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition bg-white px-3 py-1.5 rounded-xl border border-slate-200/80 shadow-2xs">
      &larr; Back to Enquiries
    </a>
  </div>

  {{-- ── Header Banner ──────────────────────────────────────────────────── --}}
  <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs relative overflow-hidden">
    <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-rose-50/70 blur-3xl pointer-events-none"></div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-200/80 text-[#8C2826] flex items-center justify-center shrink-0 shadow-xs">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-rose-50 text-[#8C2826] border border-rose-200">
              Auto-Save &amp; Recovery
            </span>
            <span class="text-xs font-semibold text-slate-400">&bull; Safe Persistence</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">
            Admission Application Drafts
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Partially filled admission forms automatically captured during data entry. Resume anytime without losing progress.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5 shrink-0">
        <a href="{{ route('admissions.create') }}"
           class="px-4 py-2.5 rounded-xl bg-[#8C2826] hover:bg-[#721f22] text-white text-xs font-black shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
          </svg>
          <span>Start New Admission</span>
        </a>
      </div>
    </div>
  </div>

  {{-- ── KPI Cards Ribbon ───────────────────────────────────────────────── --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
      <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Total Saved Drafts</span>
      <p class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 tabular-nums">{{ number_format($stats['total']) }}</p>
      <span class="text-[11px] text-slate-500 font-medium block mt-0.5">Server persisted applications</span>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
      <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-600 block">In Progress</span>
      <p class="text-2xl sm:text-3xl font-black text-amber-700 mt-1 tabular-nums">{{ number_format($stats['in_progress']) }}</p>
      <span class="text-[11px] text-slate-500 font-medium block mt-0.5">Drafts being edited</span>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
      <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 block">Ready to Submit</span>
      <p class="text-2xl sm:text-3xl font-black text-emerald-700 mt-1 tabular-nums">{{ number_format($stats['ready']) }}</p>
      <span class="text-[11px] text-slate-500 font-medium block mt-0.5">&ge; 90% information entered</span>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
      <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-600 block">Submission Retries</span>
      <p class="text-2xl sm:text-3xl font-black text-rose-700 mt-1 tabular-nums">{{ number_format($stats['failed']) }}</p>
      <span class="text-[11px] text-slate-500 font-medium block mt-0.5">Forms safely preserved on failure</span>
    </div>
  </div>

  {{-- ── Filter Controls & Search Form ──────────────────────────────────── --}}
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
    <form method="GET" action="{{ route('admissions.drafts.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl overflow-x-auto shrink-0">
        <a href="{{ route('admissions.drafts.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $status === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          All ({{ $stats['total'] }})
        </a>
        <a href="{{ route('admissions.drafts.index', array_merge(request()->except('status', 'page'), ['status' => 'in_progress'])) }}"
           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $status === 'in_progress' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          In Progress ({{ $stats['in_progress'] }})
        </a>
        <a href="{{ route('admissions.drafts.index', array_merge(request()->except('status', 'page'), ['status' => 'ready_for_submission'])) }}"
           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $status === 'ready_for_submission' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          Ready to Submit ({{ $stats['ready'] }})
        </a>
        <a href="{{ route('admissions.drafts.index', array_merge(request()->except('status', 'page'), ['status' => 'submission_failed'])) }}"
           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $status === 'submission_failed' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          Failed Submissions ({{ $stats['failed'] }})
        </a>
        <a href="{{ route('admissions.drafts.index', array_merge(request()->except('status', 'page'), ['status' => 'submitted'])) }}"
           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $status === 'submitted' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
          Enrolled / Submitted ({{ $stats['submitted'] }})
        </a>
      </div>

      <div class="flex items-center gap-3 w-full md:w-auto">
        <select name="class_id" onchange="this.form.submit()"
                class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-slate-50 focus:border-[#8C2826] focus:bg-white transition">
          <option value="">All Standards</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected($classId == $c->id)>Class {{ $c->name }}</option>
          @endforeach
        </select>

        <div class="relative flex-1 md:w-64">
          <input type="text" name="search" value="{{ $search }}" placeholder="Search student, parent, draft #"
                 class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:border-[#8C2826] focus:bg-white transition bg-slate-50">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>

        @if($search || $classId || $status !== 'all')
          <a href="{{ route('admissions.drafts.index') }}" class="px-2.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition shrink-0" title="Clear Filters">
            &times;
          </a>
        @endif
      </div>
    </form>
  </div>

  {{-- ── Drafts Data Table ──────────────────────────────────────────────── --}}
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-bold uppercase tracking-wider text-[11px]">
          <tr>
            <th class="py-4 px-6">Draft Ref #</th>
            <th class="py-4 px-6">Student Details</th>
            <th class="py-4 px-6">Applying Standard</th>
            <th class="py-4 px-6">Parent &amp; Phone</th>
            <th class="py-4 px-6">Progress</th>
            <th class="py-4 px-6">Last Auto-Saved</th>
            <th class="py-4 px-6 text-center">Status</th>
            <th class="py-4 px-6 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($drafts as $draft)
            <tr class="hover:bg-slate-50/60 transition group">
              {{-- Draft Number --}}
              <td class="py-4 px-6 whitespace-nowrap">
                <span class="font-mono font-bold text-[#8C2826] text-xs">
                  {{ $draft->draft_number }}
                </span>
                @if($draft->enquiry)
                  <span class="block text-[10px] text-slate-400 font-mono mt-0.5">
                    From Enq #{{ $draft->enquiry->enquiry_number }}
                  </span>
                @endif
              </td>

              {{-- Student Name --}}
              <td class="py-4 px-6 font-bold text-slate-900 text-sm">
                {{ $draft->student_name ?: 'Unnamed Applicant' }}
                @if($draft->creator)
                  <span class="block text-[10px] text-slate-400 font-normal">
                    Staff: {{ $draft->creator->name }}
                  </span>
                @endif
              </td>

              {{-- Class --}}
              <td class="py-4 px-6 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-[#8C2826] border border-rose-200 text-xs font-semibold">
                  Class {{ $draft->classModel?->name ?? '—' }}
                </span>
              </td>

              {{-- Parent --}}
              <td class="py-4 px-6 whitespace-nowrap">
                <p class="font-bold text-slate-800">{{ $draft->parent_name ?: '—' }}</p>
                <p class="font-mono text-xs text-slate-500 mt-0.5">{{ $draft->parent_mobile ?: '—' }}</p>
              </td>

              {{-- Completion Percentage --}}
              <td class="py-4 px-6 whitespace-nowrap">
                <div class="w-36 space-y-1">
                  <div class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-slate-700">{{ $draft->completion_percentage }}%</span>
                    <span class="text-[10px] text-slate-400">Step {{ $draft->current_step }}/5</span>
                  </div>
                  <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300 {{ $draft->completion_percentage >= 90 ? 'bg-emerald-500' : ($draft->completion_percentage >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}"
                         style="width: {{ max(4, $draft->completion_percentage) }}%"></div>
                  </div>
                </div>
              </td>

              {{-- Last Saved At --}}
              <td class="py-4 px-6 whitespace-nowrap">
                <p class="font-semibold text-slate-800">
                  {{ $draft->last_saved_at ? $draft->last_saved_at->format('d M Y, h:i A') : '—' }}
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">
                  {{ $draft->last_saved_at ? $draft->last_saved_at->diffForHumans() : '' }}
                </p>
              </td>

              {{-- Status --}}
              <td class="py-4 px-6 text-center whitespace-nowrap">
                @php
                  $statusClasses = match($draft->status) {
                    'ready_for_submission' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'in_progress'          => 'bg-amber-50 text-amber-700 border-amber-200',
                    'submission_failed'    => 'bg-rose-50 text-rose-700 border-rose-200',
                    'submitted'            => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    default                => 'bg-slate-100 text-slate-700 border-slate-200',
                  };
                  $statusLabel = match($draft->status) {
                    'ready_for_submission' => 'Ready to Submit',
                    'in_progress'          => 'In Progress',
                    'submission_failed'    => 'Submission Failed',
                    'submitted'            => 'Enrolled',
                    default                => ucfirst(str_replace('_', ' ', $draft->status)),
                  };
                @endphp
                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $statusClasses }}">
                  {{ $statusLabel }}
                </span>
                @if($draft->submission_error)
                  <span class="block text-[9px] text-rose-500 font-mono mt-1 max-w-[140px] truncate" title="{{ $draft->submission_error }}">
                    {{ $draft->submission_error }}
                  </span>
                @endif
              </td>

              {{-- Actions --}}
              <td class="py-4 px-6 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  @if($draft->status !== 'submitted')
                    <a href="{{ route('admissions.create', ['draft_id' => $draft->id, 'enquiry_id' => $draft->enquiry_id]) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-[#8C2826] hover:bg-[#721f22] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                      <span>Resume</span>
                    </a>

                    <form method="POST" action="{{ route('admissions.draft.delete', $draft->id) }}"
                          onsubmit="return confirm('Are you sure you want to discard this draft? All progress will be removed.')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Discard Draft">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    </form>
                  @else
                    @if($draft->convertedStudent)
                      <a href="{{ route('students.show', $draft->convertedStudent->id) }}"
                         class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                        View Student &rarr;
                      </a>
                    @else
                      <span class="text-xs text-slate-400">Completed</span>
                    @endif
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-16 text-center">
                <div class="max-w-sm mx-auto space-y-3">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <h3 class="text-sm font-bold text-slate-800">No Admission Drafts Found</h3>
                  <p class="text-xs text-slate-400">
                    Drafts are automatically saved whenever staff begins entering an admission form.
                  </p>
                  <a href="{{ route('admissions.create') }}" class="btn btn-secondary btn-sm text-xs font-bold inline-flex items-center gap-1.5 mt-2">
                    Start New Admission
                  </a>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($drafts->hasPages())
      <div class="p-4 border-t border-slate-100">
        {{ $drafts->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
