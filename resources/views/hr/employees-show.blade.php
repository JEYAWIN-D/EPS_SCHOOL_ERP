@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
<div class="w-full max-w-7xl mx-auto space-y-6">
  <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        @if($employee->photo)
          <img src="{{ asset('storage/'.$employee->photo) }}" alt="{{ $employee->full_name }}" class="w-16 h-16 rounded-2xl object-cover ring-2 ring-slate-100 shadow-md">
        @else
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-600 to-indigo-700 flex items-center justify-center flex-shrink-0 shadow-md text-white font-black text-xl">
            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
          </div>
        @endif

        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="page-title text-xl font-bold text-slate-900">{{ $employee->full_name }}</h1>
            <span class="{{ $employee->category_badge_class }} text-xs px-2.5 py-0.5 font-bold uppercase tracking-wider rounded-md">
              {{ $employee->category_label }}
            </span>
            <span class="{{ $employee->is_active ? 'badge-green' : 'badge-red' }}">
              {{ $employee->is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <p class="text-xs text-slate-500 font-mono mt-1">
            Emp Code: <span class="font-bold text-indigo-600">{{ $employee->employee_code }}</span> &bull; {{ $employee->designation }} &bull; {{ $employee->department ?? 'General' }}
          </p>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <a href="tel:{{ $employee->mobile }}" class="btn bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 btn-sm font-bold flex items-center gap-1.5" title="Call Mobile">
          <span>Call</span> {{ $employee->mobile }}
        </a>
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $employee->mobile) }}" target="_blank" class="btn bg-emerald-600 hover:bg-emerald-700 text-white btn-sm font-bold flex items-center gap-1.5" title="Send WhatsApp Message">
          <span>WhatsApp</span>
        </a>
        <a href="{{ route('hr.employees.id-card', $employee->id) }}" class="btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 btn-sm font-bold flex items-center gap-1.5" title="Generate & Download ID Card">
          <span>Staff ID Card</span>
        </a>
        <a href="{{ route('hr.employees.edit', $employee->id) }}" class="btn btn-primary btn-sm">
          Edit Profile
        </a>

        <div x-data="{ open: false }" class="relative inline-block text-left">
          <button type="button" @click="open = !open" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-semibold">
            <span>Service Docs</span>
            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="absolute right-0 mt-1.5 bg-white shadow-xl rounded-xl border border-slate-200 z-50 min-w-[220px] py-1 text-left">
            <a href="{{ route('hr.employees.id-card', $employee->id) }}" class="block px-4 py-2.5 text-xs font-bold text-indigo-600 hover:bg-indigo-50">
              Staff ID Card (Front &amp; Back)
            </a>
            <a href="{{ route('hr.employees.appointment-letter', $employee->id) }}" target="_blank" class="block px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
              Appointment Letter (PDF)
            </a>
            <a href="{{ route('hr.employees.experience-certificate', $employee->id) }}" target="_blank" class="block px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
              Experience Certificate (PDF)
            </a>
          </div>
        </div>
      </div>
    </div>
  {{-- ── Top KPI Stat Cards (Attendance %, Overtime Extra Duty, Leaves, Salary, Tenure) ── --}}
  <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
    {{-- Attendance Rate --}}
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
      <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Attendance Rate</span>
      <div class="flex items-baseline gap-2 mt-1">
        <p class="text-2xl font-black font-mono {{ ($attendancePercentage ?? 0) >= 80 ? 'text-emerald-600' : 'text-rose-600' }}">
          {{ $attendancePercentage ?? '—' }}{{ $attendancePercentage !== null ? '%' : '' }}
        </p>
        <span class="text-[11px] font-semibold text-slate-500">
          {{ $cmEffectivePresent ?? $effectivePresent }} / {{ $currentMonthWorkingDays ?? $attTotalDays }} working days
        </span>
      </div>
    </div>

    {{-- Sunday/Holiday Overtime (Extra Pay) --}}
    <div class="bg-white p-4 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-2xs">
      <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Overtime (Extra Duty)</span>
      <div class="flex items-baseline gap-2 mt-1">
        <p class="text-2xl font-black font-mono text-amber-700">
          {{ $attOvertime ?? 0 }} <span class="text-xs font-semibold text-amber-800">Days</span>
        </p>
        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-200">
          {{ $attOvertimeDuration ?? '0m' }}
        </span>
      </div>
    </div>

    {{-- Leaves Taken --}}
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
      <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Leaves Taken</span>
      <div class="flex items-baseline gap-2 mt-1">
        <p class="text-2xl font-black font-mono text-indigo-600">
          {{ $approvedLeaveDays }} <span class="text-xs font-semibold text-slate-500">Days</span>
        </p>
        @if($pendingLeaveCount > 0)
          <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">
            {{ $pendingLeaveCount }} pending
          </span>
        @endif
      </div>
    </div>

    {{-- Monthly Salary --}}
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
      <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Basic Monthly Pay</span>
      <p class="text-2xl font-black font-mono text-emerald-600 mt-1">
        ₹{{ number_format($employee->basic_salary ?? 0, 0) }}
      </p>
    </div>

    {{-- Date of Joining --}}
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
      <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Joining Date</span>
      <p class="text-lg font-bold text-slate-800 mt-1">
        {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}
      </p>
    </div>
  </div>

  {{-- Category-Specific Specialty Banner --}}
  @if(strtolower($employee->employee_type) === 'driver')
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-200 p-5 rounded-2xl shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-amber-900 text-sm flex items-center gap-2">
          Driver Card Details &amp; Vehicle Assignment
        </h3>
        <span class="badge-amber text-xs font-bold">Active Driver Profile</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
        <div class="bg-white p-3 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">Driving License No.</p>
          <p class="font-mono font-black text-amber-900 text-sm mt-0.5">{{ $employee->license_number ?? 'DL-042019881234' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">License Expiry Date</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->license_expiry ? \Carbon\Carbon::parse($employee->license_expiry)->format('d M Y') : 'Valid till Nov 2028' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">Assigned Vehicle / Route</p>
          <p class="font-bold text-amber-800 text-sm mt-0.5 truncate">{{ $employee->assigned_vehicle ?? 'Bus No. MH-12-AB-1001 (Route 1)' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">Emergency Mobile</p>
          <p class="font-mono font-bold text-slate-800 text-sm mt-0.5">{{ $employee->emergency_contact_mobile ?? $employee->mobile }}</p>
        </div>
      </div>
    </div>

  @elseif(in_array(strtolower($employee->employee_type), ['nanny', 'naani']))
    <div class="bg-gradient-to-r from-rose-50 to-pink-50 border-2 border-rose-200 p-5 rounded-2xl shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-rose-900 text-sm flex items-center gap-2">
          Nanny (Naani) Caretaker Profile Card
        </h3>
        <span class="badge-rose text-xs font-bold">Pre-Primary Staff</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
        <div class="bg-white p-3 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Assigned Classroom Section</p>
          <p class="font-bold text-rose-900 text-sm mt-0.5">{{ $employee->assigned_block ?? 'Nursery & LKG Section' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Shift Timings</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->shift_timing ?? 'School Hours (8:00 AM - 2:30 PM)' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Child Safety Clearance</p>
          <p class="font-bold text-emerald-600 text-sm mt-0.5">Verified &amp; Background Checked</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Emergency Mobile</p>
          <p class="font-mono font-bold text-slate-800 text-sm mt-0.5">{{ $employee->emergency_contact_mobile ?? $employee->mobile }}</p>
        </div>
      </div>
    </div>

  @elseif(strtolower($employee->employee_type) === 'cleaner')
    <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border-2 border-teal-200 p-5 rounded-2xl shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-teal-900 text-sm flex items-center gap-2">
          Cleaner &amp; Housekeeping Staff Profile Card
        </h3>
        <span class="badge-teal text-xs font-bold">Hygiene Team</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
        <div class="bg-white p-3 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Assigned Zone / Building</p>
          <p class="font-bold text-teal-900 text-sm mt-0.5">{{ $employee->assigned_block ?? 'Main Administrative Building' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Work Shift</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->shift_timing ?? 'Morning Shift (7:00 AM - 3:30 PM)' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Department</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->department ?? 'Sanitation & Hygiene' }}</p>
        </div>
        <div class="bg-white p-3 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Mobile</p>
          <p class="font-mono font-bold text-slate-800 text-sm mt-0.5">{{ $employee->mobile }}</p>
        </div>
      </div>
    </div>
  @endif

  {{-- ── Staff Attendance & Monthly Summary (Full Landscape Layout) ── --}}
  <div class="card p-5 sm:p-6 lg:p-7 space-y-6 bg-white rounded-2xl border border-slate-200 shadow-sm">

    {{-- 1. Section Header --}}
    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
      <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 shadow-2xs">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <div>
        <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight" style="font-family:'Plus Jakarta Sans',sans-serif;">
          Staff Attendance &amp; Monthly Summary
        </h2>
        <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">
          Monthly attendance rates and recent biometric/RFID punch logs
        </p>
      </div>
    </div>

    {{-- 2. Attendance Summary / KPI Cards (Horizontal Landscape Grid) --}}
    <div class="space-y-3">
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        {{-- Card 1: Working Days --}}
        <div class="bg-slate-50/80 p-3 sm:p-3.5 rounded-xl border border-slate-200 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Working Days</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-slate-800">
              {{ $currentMonthWorkingDays ?? $attTotalDays }}
            </p>
            <span class="text-[10px] text-slate-500 font-medium">Current Month</span>
          </div>
        </div>

        {{-- Card 2: Present --}}
        <div class="bg-emerald-50/60 p-3 sm:p-3.5 rounded-xl border border-emerald-200/80 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Present</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-emerald-600">
              {{ $cmPresent ?? $attPresent }} <span class="text-xs font-semibold text-emerald-700">d</span>
            </p>
            <span class="text-[10px] text-emerald-700 font-medium">Attended full</span>
          </div>
        </div>

        {{-- Card 3: Absent --}}
        <div class="bg-rose-50/60 p-3 sm:p-3.5 rounded-xl border border-rose-200/80 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-rose-800">Absent</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-rose-600">
              {{ $attAbsent }} <span class="text-xs font-semibold text-rose-700">d</span>
            </p>
            <span class="text-[10px] text-rose-700 font-medium">Unexcused</span>
          </div>
        </div>

        {{-- Card 4: Half Day --}}
        <div class="bg-orange-50/60 p-3 sm:p-3.5 rounded-xl border border-orange-200/80 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-orange-800">Half Day</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-orange-600">
              {{ $attHalfDay }} <span class="text-xs font-semibold text-orange-700">d</span>
            </p>
            <span class="text-[10px] text-orange-700 font-medium">0.5 Day credit</span>
          </div>
        </div>

        {{-- Card 5: Late --}}
        <div class="bg-amber-50/60 p-3 sm:p-3.5 rounded-xl border border-amber-200/80 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Late Check-in</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-amber-700">
              {{ $attLate }} <span class="text-xs font-semibold text-amber-800">d</span>
            </p>
            <span class="text-[10px] text-amber-700 font-medium">Delayed punch</span>
          </div>
        </div>

        {{-- Card 6: Permission --}}
        <div class="bg-purple-50/60 p-3 sm:p-3.5 rounded-xl border border-purple-200/80 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800">Permission</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-purple-700">
              {{ $attPermission ?? 0 }} <span class="text-xs font-semibold text-purple-800">d</span>
            </p>
            <span class="text-[10px] text-purple-700 font-medium">Official pass</span>
          </div>
        </div>

        {{-- Card 7: Overtime --}}
        <div class="bg-amber-50/60 p-3 sm:p-3.5 rounded-xl border border-amber-300 flex flex-col justify-between min-h-[96px] shadow-2xs">
          <span class="text-[10px] font-bold uppercase tracking-wider text-amber-900">Overtime</span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono text-amber-800">
              {{ $attOvertime }} <span class="text-xs font-semibold text-amber-900">d</span>
            </p>
            <span class="text-[10px] font-mono font-bold text-amber-900 truncate" title="{{ $attOvertimeDuration ?? '0m' }}">
              {{ $attOvertimeDuration ?? '0m' }} extra
            </span>
          </div>
        </div>

        {{-- Card 8: Attendance Rate % --}}
        <div class="p-3 sm:p-3.5 rounded-xl border flex flex-col justify-between min-h-[96px] shadow-2xs {{ ($attendancePercentage ?? 0) >= 80 ? 'bg-emerald-50/80 border-emerald-300 text-emerald-900' : 'bg-rose-50/80 border-rose-300 text-rose-900' }}">
          <span class="text-[10px] font-bold uppercase tracking-wider {{ ($attendancePercentage ?? 0) >= 80 ? 'text-emerald-800' : 'text-rose-800' }}">
            Attendance %
          </span>
          <div class="mt-1">
            <p class="text-xl sm:text-2xl font-black font-mono {{ ($attendancePercentage ?? 0) >= 80 ? 'text-emerald-600' : 'text-rose-600' }}">
              {{ $attendancePercentage ?? '0.00' }}%
            </p>
            <span class="text-[10px] font-semibold {{ ($attendancePercentage ?? 0) >= 80 ? 'text-emerald-700' : 'text-rose-700' }}">
              {{ ($attendancePercentage ?? 0) >= 80 ? 'Target met' : 'Below target' }}
            </span>
          </div>
        </div>
      </div>

      {{-- Progress bar & summary text --}}
      @if(($attTotalDays ?? 0) > 0)
      <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
        <div class="flex items-center gap-2">
          <span class="font-bold text-slate-700">Cumulative Recorded Progress:</span>
          <span class="text-slate-600 font-mono">
            <strong class="text-slate-900">{{ $cmEffectivePresent ?? $effectivePresent }}</strong> effective days present out of
            <strong class="text-slate-900">{{ $currentMonthWorkingDays ?? $attTotalDays }}</strong> working days
          </span>
        </div>
        <div class="flex items-center gap-3 sm:w-64">
          <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 {{ ($attendancePercentage ?? 0) >= 80 ? 'bg-emerald-500' : 'bg-rose-500' }}"
                 style="width: {{ min(100, (float)($attendancePercentage ?? 0)) }}%"></div>
          </div>
          <span class="font-mono font-bold text-xs {{ ($attendancePercentage ?? 0) >= 80 ? 'text-emerald-700' : 'text-rose-700' }} shrink-0">
            {{ $attendancePercentage }}%
          </span>
        </div>
      </div>
      @endif
    </div>

    {{-- 3. Monthly Breakdown Table (Full Width) --}}
    <div class="space-y-3 pt-2 border-t border-slate-100">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
          Monthly Breakdown (Month-Wise Attendance &amp; Overtime)
        </h3>
        @if(isset($monthlyAttendance) && $monthlyAttendance->count())
          <span class="text-[11px] font-semibold text-slate-400">
            Showing last {{ $monthlyAttendance->count() }} calendar months
          </span>
        @endif
      </div>

      @if(isset($monthlyAttendance) && $monthlyAttendance->count())
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
          <table class="w-full text-xs text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/90 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <th class="py-3 px-4 whitespace-nowrap">Month</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Working Days</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Present / Attended</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Half-Day</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Sunday / Holiday Overtime</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Absent</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">On Leave</th>
                <th class="py-3 px-4 text-right whitespace-nowrap">Attendance Rate</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              @foreach($monthlyAttendance as $m)
              <tr class="hover:bg-slate-50/75 transition-colors">
                <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $m->month_name }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center font-mono font-medium text-slate-700 whitespace-nowrap">
                  {{ $m->working_days }}
                </td>
                <td class="py-3 px-4 text-center font-mono font-bold text-emerald-600 whitespace-nowrap">
                  {{ $m->present_count }} d
                </td>
                <td class="py-3 px-4 text-center font-mono text-orange-600 whitespace-nowrap">
                  {{ $m->half_day_count }} d
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  @if($m->overtime_count > 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 font-bold font-mono text-[11px]">
                      <span>{{ $m->overtime_count }} d</span>
                      <span class="text-amber-700">({{ $m->overtime_duration }})</span>
                    </span>
                  @else
                    <span class="text-slate-400 font-mono">—</span>
                  @endif
                </td>
                <td class="py-3 px-4 text-center font-mono font-bold text-rose-600 whitespace-nowrap">
                  {{ $m->absent_count }} d
                </td>
                <td class="py-3 px-4 text-center font-mono font-medium text-blue-600 whitespace-nowrap">
                  {{ $m->leave_count }} d
                </td>
                <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold">
                  <span class="inline-block px-2.5 py-1 rounded-full text-xs font-extrabold {{ $m->percentage >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ $m->percentage }}%
                  </span>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-200 text-slate-400">
          <p class="text-xs font-semibold">No monthly attendance records archived yet for this employee.</p>
        </div>
      @endif
    </div>

    {{-- 4. Recent Card Punch Logs (Last 30 Days) (Full Width) --}}
    <div class="space-y-3 pt-2 border-t border-slate-100">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
          Recent Card Punch Logs (Last 30 Days)
        </h3>
        @if(isset($recentAttendance) && $recentAttendance->count())
          <span class="text-[11px] font-semibold text-slate-400">
            {{ $recentAttendance->count() }} punches recorded
          </span>
        @endif
      </div>

      @if(isset($recentAttendance) && $recentAttendance->count())
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white max-h-96 overflow-y-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-xs z-10 shadow-2xs">
              <tr class="border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <th class="py-3 px-4 whitespace-nowrap">Date</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">In-Time</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Out-Time</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Duration</th>
                <th class="py-3 px-4 whitespace-nowrap">Remarks / Notes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              @foreach($recentAttendance as $rec)
              @php
                $inT = $rec->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : '—';
                $outT = $rec->check_out ? \Carbon\Carbon::parse($rec->check_out)->format('h:i A') : '—';
                $dur = ($rec->check_in && $rec->check_out) ? round(\Carbon\Carbon::parse($rec->check_out)->diffInMinutes(\Carbon\Carbon::parse($rec->check_in)) / 60, 1) . 'h' : '—';
              @endphp
              <tr class="hover:bg-slate-50/75 transition-colors">
                <td class="py-3 px-4 font-mono font-medium text-slate-900 whitespace-nowrap">
                  {{ $rec->date->format('d M Y (D)') }}
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  @if($rec->status === 'present')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                      Present
                    </span>
                  @elseif($rec->status === 'permission')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                      Permission
                    </span>
                  @elseif($rec->status === 'late')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                      Late
                    </span>
                  @elseif($rec->status === 'half_day')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">
                      Half-Day
                    </span>
                  @elseif($rec->status === 'overtime')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                      Overtime
                    </span>
                  @elseif($rec->status === 'absent')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                      Absent
                    </span>
                  @elseif($rec->status === 'holiday')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                      Holiday
                    </span>
                  @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                      {{ ucfirst($rec->status) }}
                    </span>
                  @endif
                </td>
                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-700 whitespace-nowrap">
                  {{ $inT }}
                </td>
                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-700 whitespace-nowrap">
                  {{ $outT }}
                </td>
                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800 whitespace-nowrap">
                  {{ $dur }}
                </td>
                <td class="py-3 px-4 text-slate-600 text-[11px]">
                  {{ $rec->remarks ?? '—' }}
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-200 text-slate-400">
          <p class="text-xs font-semibold">No biometric/card punch logs recorded in the last 30 days.</p>
        </div>
      @endif
    </div>

  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
      <div class="card space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-800 text-base">Staff Master Record (Excel Reference)</h3>
            <p class="text-xs text-slate-400">Complete school HR master-data across all standardized sections</p>
          </div>
          <a href="{{ route('hr.employees.edit', $employee->id) }}" class="btn btn-secondary btn-xs font-semibold">
            Edit Details
          </a>
        </div>

        {{-- SECTION A: PERSONAL DETAILS --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Section A — Personal Details
          </h4>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-xs bg-slate-50/60 p-3.5 rounded-xl border border-slate-100">
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Name</dt><dd class="mt-0.5 font-bold text-slate-800 text-sm">{{ $employee->full_name }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Date of Birth (DOB)</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->dob ? \Carbon\Carbon::parse($employee->dob)->format('d M Y') : '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Gender (M/F)</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ ucfirst($employee->gender ?? '—') }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Mother Tongue</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->mother_tongue ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Nationality</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->nationality ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Religion</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->religion ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Caste</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->caste ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Marital Status</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ ucfirst($employee->marital_status ?? '—') }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Highest Qualification</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->qualification ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Number of Children</dt><dd class="mt-0.5 font-bold font-mono text-slate-800">{{ $employee->number_of_children ?? 0 }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Male Children</dt><dd class="mt-0.5 font-mono text-slate-700">{{ $employee->children_male ?? 0 }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Female Children</dt><dd class="mt-0.5 font-mono text-slate-700">{{ $employee->children_female ?? 0 }}</dd></div>
            <div class="col-span-2 sm:col-span-3 pt-1 border-t border-slate-200/60"><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Address</dt><dd class="mt-0.5 text-slate-800 font-medium">{{ $employee->address ?? $employee->residential_address ?? '—' }}</dd></div>
          </dl>
        </div>

        {{-- SECTION B: EMPLOYMENT DETAILS --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Section B — Employment Details
          </h4>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-xs bg-slate-50/60 p-3.5 rounded-xl border border-slate-100">
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Employee ID</dt><dd class="mt-0.5 font-mono font-bold text-indigo-600">{{ $employee->employee_code }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Designation</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->designation }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Department</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->department ?? 'General' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Date of Joining (DOJ)</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">1st Teacher Appointment</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->first_appointment_date ? \Carbon\Carbon::parse($employee->first_appointment_date)->format('d M Y') : '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Employment Status</dt><dd class="mt-0.5"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ $employee->current_employment_status ?? ($employee->is_active ? 'Active' : 'Inactive') }}</span></dd></div>
          </dl>
        </div>

        {{-- SECTION C: IDENTITY / GOVERNMENT DETAILS --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Section C — Identity / Government Details
          </h4>
          <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 text-xs bg-slate-50/60 p-3.5 rounded-xl border border-slate-100">
            <div>
              <dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Aadhaar No</dt>
              <dd class="mt-0.5 font-mono font-bold text-slate-800">
                @if($employee->aadhaar_no)
                  {{ strlen($employee->aadhaar_no) === 12 ? 'XXXX-XXXX-' . substr($employee->aadhaar_no, -4) : $employee->aadhaar_no }}
                @else
                  —
                @endif
              </dd>
            </div>
            <div>
              <dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">PAN No.</dt>
              <dd class="mt-0.5 font-mono font-bold text-slate-800 uppercase">{{ $employee->pan_no ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">EPF No.</dt>
              <dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->epf_no ?? $employee->pf_account_no ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        {{-- SECTION D: CONTACT DETAILS --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Section D — Contact Details
          </h4>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-xs bg-slate-50/60 p-3.5 rounded-xl border border-slate-100">
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Email ID</dt><dd class="mt-0.5 font-medium text-indigo-600 break-all">{{ $employee->official_email ?? $employee->email ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Contact No.</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->mobile }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Contact Person</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->contact_person ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Name of the Contact Person</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->name_of_contact_person ?? $employee->emergency_contact_name ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Relationship</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->contact_relationship ?? $employee->emergency_contact_relation ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Contact Person Mobile</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->contact_no ?? $employee->emergency_contact_mobile ?? '—' }}</dd></div>
            <div class="col-span-2 sm:col-span-3 pt-1 border-t border-slate-200/60"><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Contact Address</dt><dd class="mt-0.5 text-slate-800 font-medium">{{ $employee->contact_address ?? '—' }}</dd></div>
          </dl>
        </div>

        {{-- SECTION E: PREVIOUS EMPLOYMENT DETAILS --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Section E — Previous Employment Details
          </h4>
          <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-3 text-xs bg-slate-50/60 p-3.5 rounded-xl border border-slate-100">
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Previous Post Held</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->previous_designation ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">Institution Name &amp; Address</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $employee->previous_school ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">From Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->previous_from_date ? \Carbon\Carbon::parse($employee->previous_from_date)->format('d M Y') : '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium uppercase tracking-wide text-[10px]">To Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->previous_to_date ? \Carbon\Carbon::parse($employee->previous_to_date)->format('d M Y') : '—' }}</dd></div>
          </dl>
        </div>

        {{-- SECTIONS F, G, H, I: QUALIFICATION MASTER SECTIONS --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Sections F, G, H, I — Qualification Master Headings
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
            {{-- Academic --}}
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 mb-1.5">Section F: Academic Qualification</span>
              <p class="font-bold text-slate-800 text-sm">{{ $employee->academic_qualification ?? '—' }}</p>
              <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px] text-slate-600">
                <p><span class="text-slate-400">University:</span> <strong class="text-slate-700">{{ $employee->academic_university ?? '—' }}</strong></p>
                <p><span class="text-slate-400">Medium:</span> <strong class="text-slate-700">{{ $employee->academic_medium ?? '—' }}</strong></p>
                <p class="col-span-2"><span class="text-slate-400">Institution:</span> <strong class="text-slate-700">{{ $employee->academic_institution ?? '—' }}</strong></p>
                <p><span class="text-slate-400">% Marks:</span> <strong class="text-emerald-700 font-mono">{{ $employee->academic_percentage !== null ? $employee->academic_percentage.'%' : '—' }}</strong></p>
                <p><span class="text-slate-400">Year of Passing:</span> <strong class="text-slate-700 font-mono">{{ $employee->academic_year_of_passing ?? '—' }}</strong></p>
              </div>
            </div>

            {{-- Professional --}}
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 mb-1.5">Section G: Professional Qualification</span>
              <p class="font-bold text-slate-800 text-sm">{{ $employee->professional_qualification ?? '—' }}</p>
              <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px] text-slate-600">
                <p><span class="text-slate-400">University:</span> <strong class="text-slate-700">{{ $employee->professional_university ?? '—' }}</strong></p>
                <p><span class="text-slate-400">Medium:</span> <strong class="text-slate-700">{{ $employee->professional_medium ?? '—' }}</strong></p>
                <p class="col-span-2"><span class="text-slate-400">Institution:</span> <strong class="text-slate-700">{{ $employee->professional_institution ?? '—' }}</strong></p>
                <p><span class="text-slate-400">% Marks:</span> <strong class="text-emerald-700 font-mono">{{ $employee->professional_percentage !== null ? $employee->professional_percentage.'%' : '—' }}</strong></p>
                <p><span class="text-slate-400">Year of Passing:</span> <strong class="text-slate-700 font-mono">{{ $employee->professional_year_of_passing ?? '—' }}</strong></p>
              </div>
            </div>

            {{-- Other 1 --}}
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 mb-1.5">Section H: Other Qualification 1</span>
              <p class="font-bold text-slate-800 text-sm">{{ $employee->other_qual_1_name ?? '—' }}</p>
              <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px] text-slate-600">
                <p><span class="text-slate-400">University:</span> <strong class="text-slate-700">{{ $employee->other_qual_1_university ?? '—' }}</strong></p>
                <p><span class="text-slate-400">Medium:</span> <strong class="text-slate-700">{{ $employee->other_qual_1_medium ?? '—' }}</strong></p>
                <p class="col-span-2"><span class="text-slate-400">Institution:</span> <strong class="text-slate-700">{{ $employee->other_qual_1_institution ?? '—' }}</strong></p>
                <p><span class="text-slate-400">% Marks:</span> <strong class="text-emerald-700 font-mono">{{ $employee->other_qual_1_percentage !== null ? $employee->other_qual_1_percentage.'%' : '—' }}</strong></p>
                <p><span class="text-slate-400">Year of Passing:</span> <strong class="text-slate-700 font-mono">{{ $employee->other_qual_1_year_of_passing ?? '—' }}</strong></p>
              </div>
            </div>

            {{-- Other 2 --}}
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-teal-100 text-teal-800 mb-1.5">Section I: Other Qualification 2</span>
              <p class="font-bold text-slate-800 text-sm">{{ $employee->other_qual_2_name ?? '—' }}</p>
              <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px] text-slate-600">
                <p><span class="text-slate-400">University:</span> <strong class="text-slate-700">{{ $employee->other_qual_2_university ?? '—' }}</strong></p>
                <p><span class="text-slate-400">Medium:</span> <strong class="text-slate-700">{{ $employee->other_qual_2_medium ?? '—' }}</strong></p>
                <p class="col-span-2"><span class="text-slate-400">Institution:</span> <strong class="text-slate-700">{{ $employee->other_qual_2_institution ?? '—' }}</strong></p>
                <p><span class="text-slate-400">% Marks:</span> <strong class="text-emerald-700 font-mono">{{ $employee->other_qual_2_percentage !== null ? $employee->other_qual_2_percentage.'%' : '—' }}</strong></p>
                <p><span class="text-slate-400">Year of Passing:</span> <strong class="text-slate-700 font-mono">{{ $employee->other_qual_2_year_of_passing ?? '—' }}</strong></p>
              </div>
            </div>
          </div>
        </div>

        {{-- SECTION J: ADDITIONAL QUALIFICATION / TRAINING --}}
        <div>
          <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Section J — Additional Qualification / Training
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
              <p class="text-slate-400 font-semibold uppercase text-[10px]">Additional Qualification Obtained After Recruitment</p>
              <p class="mt-1 font-medium text-slate-800 whitespace-pre-line">{{ $employee->additional_qual_after_recruitment ?? '—' }}</p>
            </div>
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
              <p class="text-slate-400 font-semibold uppercase text-[10px]">Training Programme Attended Before Recruitment</p>
              <p class="mt-1 font-medium text-slate-800 whitespace-pre-line">{{ $employee->training_attended_before_recruitment ?? '—' }}</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Qualifications --}}
      <div class="card mt-6" x-data="{ addQual: false, editQual: null }">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="font-semibold text-slate-700">Educational Qualifications</h3>
            <p class="text-xs text-slate-400">Synced to Profile & ID Card: <strong class="text-indigo-600 font-medium">{{ $employee->qualification ?? 'None' }}</strong></p>
          </div>
          <button @click="addQual = !addQual; editQual = null" class="btn btn-secondary btn-xs">+ Add Qualification</button>
        </div>

        {{-- Add Qualification Form --}}
        <div x-show="addQual" x-transition class="mb-4 p-4 bg-indigo-50/50 rounded-xl border border-indigo-100">
          <form method="POST" action="{{ route('hr.employees.qualifications.store', $employee->id) }}" class="grid grid-cols-2 gap-3">
            @csrf
            <div><label class="label text-xs">Degree / Certificate <span class="text-red-500">*</span></label>
              <input type="text" name="degree" class="input text-xs" required placeholder="B.Ed, M.Sc, B.Tech, Ph.D etc."></div>
            <div><label class="label text-xs">Subject / Specialisation</label>
              <input type="text" name="subject" class="input text-xs" placeholder="Mathematics, Physics, English..."></div>
            <div><label class="label text-xs">Institution <span class="text-red-500">*</span></label>
              <input type="text" name="institution" class="input text-xs" required placeholder="College / Institute name"></div>
            <div><label class="label text-xs">University / Board</label>
              <input type="text" name="university" class="input text-xs" placeholder="University name"></div>
            <div><label class="label text-xs">Year of Passing</label>
              <input type="number" name="year_of_passing" class="input text-xs" min="1950" max="{{ date('Y') }}" placeholder="{{ date('Y') }}"></div>
            <div><label class="label text-xs">Grade / Percentage</label>
              <input type="text" name="grade_or_percentage" class="input text-xs" placeholder="e.g. 85% / Distinction / Grade A"></div>
            <div class="col-span-2"><label class="label text-xs">Education Level <span class="text-red-500">*</span></label>
              <select name="education_level" class="select text-xs" required>
                @foreach(['secondary'=>'Secondary (10th)','higher_secondary'=>'Higher Secondary (12th)','diploma'=>'Diploma',
                  'graduate'=>'Graduate (UG)','post_graduate'=>'Post Graduate (PG)','doctorate'=>'Doctorate (Ph.D)','other'=>'Other'] as $v=>$l)
                <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
              </select></div>
            <div class="col-span-2 flex justify-end gap-2 pt-2 border-t border-indigo-100">
              <button type="button" @click="addQual = false" class="btn btn-secondary btn-xs">Cancel</button>
              <button type="submit" class="btn btn-primary btn-xs">Save & Sync</button>
            </div>
          </form>
        </div>

        @if($qualifications->count())
        <div class="space-y-2.5">
          @foreach($qualifications as $q)
          <div class="p-3 bg-white rounded-xl border border-slate-200 hover:border-indigo-200 transition" x-data="{ openEdit: false }">
            <div class="flex items-center justify-between gap-2">
              <div>
                <p class="font-bold text-slate-800 text-sm flex items-center gap-2">
                  <span>{{ $q->degree }}</span>
                  @if($q->subject)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">{{ $q->subject }}</span>
                  @endif
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                  <span class="font-medium text-slate-700">{{ $q->institution }}</span>
                  @if($q->university) &bull; {{ $q->university }} @endif
                  @if($q->year_of_passing) &bull; <span class="font-mono text-slate-600 font-bold">{{ $q->year_of_passing }}</span> @endif
                  @if($q->grade_or_percentage) &bull; <span class="text-emerald-700 font-semibold">{{ $q->grade_or_percentage }}</span> @endif
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="openEdit = !openEdit" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2 py-1 rounded bg-indigo-50 hover:bg-indigo-100 transition">
                  Edit
                </button>
                <form method="POST" action="{{ route('hr.employees.qualifications.delete', [$employee->id, $q->id]) }}" class="inline"
                  onsubmit="return confirm('Remove {{ $q->degree }} from qualifications?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="text-xs text-rose-500 hover:text-rose-700 font-semibold px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition">
                    Remove
                  </button>
                </form>
              </div>
            </div>

            {{-- Inline Edit Form --}}
            <div x-show="openEdit" x-transition class="mt-3 pt-3 border-t border-slate-100">
              <form method="POST" action="{{ route('hr.employees.qualifications.update', [$employee->id, $q->id]) }}" class="grid grid-cols-2 gap-3">
                @csrf @method('PUT')
                <div><label class="label text-[11px]">Degree <span class="text-red-500">*</span></label>
                  <input type="text" name="degree" value="{{ $q->degree }}" class="input text-xs" required></div>
                <div><label class="label text-[11px]">Subject / Specialisation</label>
                  <input type="text" name="subject" value="{{ $q->subject }}" class="input text-xs"></div>
                <div><label class="label text-[11px]">Institution <span class="text-red-500">*</span></label>
                  <input type="text" name="institution" value="{{ $q->institution }}" class="input text-xs" required></div>
                <div><label class="label text-[11px]">University / Board</label>
                  <input type="text" name="university" value="{{ $q->university }}" class="input text-xs"></div>
                <div><label class="label text-[11px]">Year of Passing</label>
                  <input type="number" name="year_of_passing" value="{{ $q->year_of_passing }}" class="input text-xs" min="1950" max="{{ date('Y') }}"></div>
                <div><label class="label text-[11px]">Grade / Percentage</label>
                  <input type="text" name="grade_or_percentage" value="{{ $q->grade_or_percentage }}" class="input text-xs"></div>
                <div class="col-span-2"><label class="label text-[11px]">Level <span class="text-red-500">*</span></label>
                  <select name="education_level" class="select text-xs" required>
                    @foreach(['secondary'=>'Secondary (10th)','higher_secondary'=>'Higher Secondary (12th)','diploma'=>'Diploma',
                      'graduate'=>'Graduate (UG)','post_graduate'=>'Post Graduate (PG)','doctorate'=>'Doctorate (Ph.D)','other'=>'Other'] as $v=>$l)
                    <option value="{{ $v }}" @selected($q->education_level === $v)>{{ $l }}</option>
                    @endforeach
                  </select></div>
                <div class="col-span-2 flex justify-end gap-2">
                  <button type="button" @click="openEdit = false" class="btn btn-secondary btn-xs">Cancel</button>
                  <button type="submit" class="btn btn-primary btn-xs">Update & Sync</button>
                </div>
              </form>
            </div>
          </div>
          @endforeach
        </div>
        @else
        <p class="text-xs text-slate-400 py-3 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">No educational qualifications added yet. Click "+ Add Qualification" above.</p>
        @endif
      </div>

      {{-- Previous Experience --}}
      <div class="card mt-6" x-data="{addExp:false}">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-700">Previous Experience</h3>
          <button @click="addExp=!addExp" class="btn btn-secondary btn-xs">+ Add</button>
        </div>
        <div x-show="addExp" x-transition class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
          <form method="POST" action="{{ route('hr.employees.experiences.store', $employee->id) }}"
            class="grid grid-cols-2 gap-3" x-data="{current:false}">
            @csrf
            <div><label class="label text-xs">Organisation <span class="text-red-500">*</span></label>
              <input type="text" name="organisation" class="input" required></div>
            <div><label class="label text-xs">Role / Designation <span class="text-red-500">*</span></label>
              <input type="text" name="role" class="input" required></div>
            <div><label class="label text-xs">From Date <span class="text-red-500">*</span></label>
              <input type="date" name="from_date" class="input" required></div>
            <div><label class="label text-xs">To Date</label>
              <input type="date" name="to_date" class="input" :disabled="current" :class="current ? 'opacity-50' : ''">
              <label class="flex items-center gap-1 mt-1 text-xs text-slate-500">
                <input type="checkbox" name="is_current" value="1" x-model="current"> Currently working here
              </label></div>
            <div><label class="label text-xs">Reason for Leaving</label>
              <input type="text" name="reason_for_leaving" class="input" placeholder="Relocation, Better opportunity..."></div>
            <div><label class="label text-xs">Reference Contact</label>
              <input type="text" name="reference_contact" class="input" placeholder="Name / Mobile"></div>
            <div class="col-span-2"><label class="label text-xs">Key Responsibilities</label>
              <textarea name="responsibilities" rows="2" class="input" placeholder="Brief description..."></textarea></div>
            <div class="col-span-2 flex justify-end gap-2">
              <button type="button" @click="addExp=false" class="btn btn-secondary btn-sm">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
          </form>
        </div>
        @if($experiences->count())
        <div class="space-y-3">
          @foreach($experiences as $exp)
          <div class="border border-slate-100 rounded-lg p-3">
            <div class="flex items-start justify-between">
              <div>
                <p class="font-medium text-slate-800 text-sm">{{ $exp->role }} <span class="text-slate-400">@</span> {{ $exp->organisation }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ $exp->from_date?->format('M Y') }} — {{ $exp->is_current ? 'Present' : $exp->to_date?->format('M Y') }}
                  <span class="ml-2 font-medium text-slate-500">{{ $exp->duration }}</span>
                  @if($exp->reason_for_leaving) · {{ $exp->reason_for_leaving }}@endif
                </p>
                @if($exp->responsibilities)<p class="text-xs text-slate-500 mt-1">{{ $exp->responsibilities }}</p>@endif
              </div>
              <form method="POST" action="{{ route('hr.employees.experiences.delete', [$employee->id, $exp->id]) }}" class="inline"
                onsubmit="return confirm('Remove this experience?')">
                @csrf @method('DELETE')
                <button class="text-xs text-red-400 hover:text-red-600 ml-2">Remove</button>
              </form>
            </div>
          </div>
          @endforeach
        </div>
        @else
        <p class="text-sm text-slate-400">No previous experience added yet.</p>
        @endif
      </div>
    </div>
      {{-- Certifications & Training --}}
      <div class="card mt-6" x-data="{addCert:false}">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-700">Certifications & Training</h3>
          <button @click="addCert=!addCert" class="btn btn-secondary btn-xs">+ Add</button>
        </div>
        <div x-show="addCert" x-transition class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
          <form method="POST" action="{{ route('hr.employees.certifications.store', $employee->id) }}" enctype="multipart/form-data" class="grid grid-cols-2 gap-3">
            @csrf
            <div class="col-span-2"><label class="label text-xs">Certification / Training Name <span class="text-red-500">*</span></label>
              <input type="text" name="certification_name" class="input" required placeholder="e.g. TET, CTET, First Aid, Fire Safety..."></div>
            <div><label class="label text-xs">Issuing Authority</label>
              <input type="text" name="issuing_authority" class="input" placeholder="CBSE, Government, etc."></div>
            <div><label class="label text-xs">Certificate Number</label>
              <input type="text" name="certificate_number" class="input" placeholder="e.g. CERT-98765"></div>
            <div class="col-span-2"><label class="label text-xs">Issue Date</label>
              <input type="date" name="issue_date" class="input"></div>
            <div class="col-span-2"><label class="label text-xs">Upload Certificate File (PDF/image, optional)</label>
              <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="input"></div>
            <div class="col-span-2 flex justify-end gap-2">
              <button type="button" @click="addCert=false" class="btn btn-secondary btn-sm">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
          </form>
        </div>
        @if($certifications->count())
        <div class="space-y-3">
          @foreach($certifications as $cert)
          <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-slate-50/70 rounded-xl border border-slate-100 gap-3">
            <div>
              <p class="font-bold text-slate-800 text-sm">{{ $cert->certification_name }}</p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ $cert->issuing_authority ?? 'Authority N/A' }}
                @if($cert->issue_date) · Issued: {{ $cert->issue_date->format('d M Y') }}@endif
                @if($cert->certificate_number) · No: {{ $cert->certificate_number }}@endif
              </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              @if($cert->file_path)
                <a href="{{ route('hr.employees.certifications.preview', [$employee->id, $cert->id]) }}" target="_blank" class="btn-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold flex items-center gap-1">
                  Preview
                </a>
                <a href="{{ route('hr.employees.certifications.download', [$employee->id, $cert->id]) }}" class="btn-xs bg-slate-200 text-slate-700 hover:bg-slate-300 font-bold flex items-center gap-1">
                  Download
                </a>
              @endif
              <form method="POST" action="{{ route('hr.employees.certifications.delete', [$employee->id, $cert->id]) }}" class="inline"
                onsubmit="return confirm('Delete this certification?')">
                @csrf @method('DELETE')
                <button class="btn-xs bg-red-50 text-red-600 hover:bg-red-100 font-semibold">Delete</button>
              </form>
            </div>
          </div>
          @endforeach
        </div>
        @else
        <p class="text-sm text-slate-400">No certifications added yet.</p>
        @endif
      </div>

      {{-- Employee Documents --}}
      <div class="card mt-6" x-data="{addDoc:false}">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-700">Employee Documents</h3>
          <button @click="addDoc=!addDoc" class="btn btn-secondary btn-xs">+ Upload</button>
        </div>
        <div x-show="addDoc" x-transition class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
          <form method="POST" action="{{ route('hr.employees.documents.upload', $employee->id) }}" enctype="multipart/form-data" class="grid grid-cols-2 gap-3">
            @csrf
            <div><label class="label text-xs">Document Type <span class="text-red-500">*</span></label>
              <select name="document_type" class="select" required>
                @foreach(['aadhaar'=>'Aadhaar Card','pan'=>'PAN Card','degree'=>'Degree Certificate',
                  'experience_letter'=>'Experience Letter','police_verification'=>'Police Verification',
                  'photo'=>'Passport Photo','marksheet'=>'Marksheet','other'=>'Other'] as $v=>$l)
                <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
              </select></div>
            <div><label class="label text-xs">Document Name / Description <span class="text-red-500">*</span></label>
              <input type="text" name="document_name" class="input" required placeholder="e.g. Aadhaar – XXXX 4567"></div>
            <div class="col-span-2"><label class="label text-xs">File <span class="text-red-500">*</span></label>
              <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="input" required></div>
            <div class="col-span-2"><label class="label text-xs">Notes (optional)</label>
              <input type="text" name="notes" class="input" placeholder="Any remarks..."></div>
            <div class="col-span-2 flex justify-end gap-2">
              <button type="button" @click="addDoc=false" class="btn btn-secondary btn-sm">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Upload</button>
            </div>
          </form>
        </div>
        @if($empDocuments->count())
        <div class="overflow-x-auto border border-slate-200 rounded-xl mt-2">
          <table class="w-full text-xs text-left min-w-[550px]">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
              <tr>
                <th class="px-3 py-2.5">Type</th>
                <th class="px-3 py-2.5">Document</th>
                <th class="px-3 py-2.5">Status</th>
                <th class="px-3 py-2.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($empDocuments as $doc)
              <tr class="hover:bg-slate-50/50">
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <span class="badge-slate text-[10px] font-semibold uppercase">{{ str_replace('_',' ',$doc->document_type) }}</span>
                </td>
                <td class="px-3 py-2.5">
                  <p class="font-bold text-slate-800">{{ $doc->document_name }}</p>
                  @if($doc->file_original_name)<p class="text-[10px] text-slate-400 font-mono">{{ $doc->file_original_name }}</p>@endif
                </td>
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <form method="POST" action="{{ route('hr.employees.documents.verify', [$employee->id, $doc->id]) }}" class="inline-flex items-center gap-1">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold border border-slate-200 rounded px-1.5 py-0.5 bg-white">
                      <option value="pending"  @selected($doc->verification_status==='pending')>Pending</option>
                      <option value="verified" @selected($doc->verification_status==='verified')>Verified</option>
                      <option value="rejected" @selected($doc->verification_status==='rejected')>Rejected</option>
                    </select>
                  </form>
                </td>
                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <a href="{{ route('hr.employees.documents.preview', [$employee->id, $doc->id]) }}" target="_blank" class="btn-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold flex items-center gap-1" title="Preview Document">
                      Preview
                    </a>
                    <a href="{{ route('hr.employees.documents.download', [$employee->id, $doc->id]) }}" class="btn-xs bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold flex items-center gap-1" title="Download Document">
                      Download
                    </a>
                    <form method="POST" action="{{ route('hr.employees.documents.delete', [$employee->id, $doc->id]) }}" class="inline"
                      onsubmit="return confirm('Delete this document?')">
                      @csrf @method('DELETE')
                      <button class="btn-xs bg-red-50 text-red-600 hover:bg-red-100 font-semibold" title="Delete Document">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <p class="text-sm text-slate-400">No documents uploaded yet.</p>
        @endif
      </div>



      {{-- ── Staff Leave History & Applications Tracker Card ──── --}}
      <div class="card mt-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Leave History &amp; Taken Details</h3>
              <p class="text-xs text-slate-400">Total approved leaves taken: <strong class="text-indigo-600 font-bold">{{ $approvedLeaveDays }} Days</strong></p>
            </div>
          </div>
          <a href="{{ route('hr.leaves.apply') }}" class="btn btn-secondary btn-xs font-bold text-indigo-600">
            + Apply Leave
          </a>
        </div>

        @if(isset($leaveRequests) && $leaveRequests->count())
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase">
                <th class="py-2.5 px-3">Leave Type</th>
                <th class="py-2.5 px-3">Date Range</th>
                <th class="py-2.5 px-3 text-center">Days</th>
                <th class="py-2.5 px-3">Reason</th>
                <th class="py-2.5 px-3 text-center">Status</th>
                <th class="py-2.5 px-3">Approved By / Note</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($leaveRequests as $lr)
              <tr class="hover:bg-slate-50/50">
                <td class="py-2.5 px-3 font-bold text-slate-800">
                  <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px]">
                    {{ $lr->leaveType?->name ?? 'General Leave' }}
                  </span>
                </td>
                <td class="py-2.5 px-3 font-mono text-slate-700 whitespace-nowrap">
                  {{ $lr->from_date->format('d M Y') }} &rarr; {{ $lr->to_date->format('d M Y') }}
                </td>
                <td class="py-2.5 px-3 text-center font-mono font-bold text-indigo-700">
                  {{ (float)($lr->total_days ?? $lr->days ?? 1) }} d
                </td>
                <td class="py-2.5 px-3 text-slate-600 max-w-[160px] truncate" title="{{ $lr->reason }}">
                  {{ $lr->reason ?? '—' }}
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  @if($lr->status === 'approved')
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Approved</span>
                  @elseif($lr->status === 'pending')
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Pending</span>
                  @elseif($lr->status === 'rejected')
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Rejected</span>
                  @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">{{ ucfirst($lr->status) }}</span>
                  @endif
                </td>
                <td class="py-2.5 px-3 text-[11px] text-slate-500">
                  @if($lr->approvedBy)
                    <span class="font-medium text-slate-700">{{ $lr->approvedBy->name }}</span>
                    @if($lr->approved_at) &bull; <span class="text-slate-400 font-mono text-[10px]">{{ \Carbon\Carbon::parse($lr->approved_at)->format('d M Y') }}</span> @endif
                  @else
                    <span class="text-slate-300">—</span>
                  @endif
                  @if($lr->approval_note)
                    <p class="text-[10px] text-slate-400 italic mt-0.5 truncate">{{ $lr->approval_note }}</p>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
          <p class="text-xs text-slate-400 italic py-2">No leave applications recorded for this employee.</p>
        @endif
      </div>
    </div>

    <div class="space-y-6">

      {{-- Portal Login & Role --}}
      <div class="card mb-6" x-data="{ createLogin: false, changeRole: false }">
        <h3 class="font-semibold text-slate-700 mb-3">Portal Login & Role</h3>
        @if(isset($linkedUser) && $linkedUser)
          <div class="flex items-center gap-2 mb-3">
            <span class="badge-green text-xs">Account Active</span>
            <span class="text-sm text-slate-500">{{ $linkedUser->email }}</span>
          </div>
          <div class="mb-3">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Current Role</span>
            <div class="mt-1">
              <span class="badge-blue text-xs capitalize">{{ $linkedUser->getRoleNames()->first() ?? 'No role assigned' }}</span>
            </div>
          </div>
          <button @click="changeRole = !changeRole" class="btn btn-secondary btn-xs w-full">Change Role</button>
          <div x-show="changeRole" x-transition class="mt-3">
            <form method="POST" action="{{ route('hr.employees.assign-role', $employee->id) }}">
              @csrf
              <select name="role" class="select text-sm mb-2">
                @foreach($staffRoles ?? [] as $r)
                  <option value="{{ $r }}" @selected($linkedUser->hasRole($r))>{{ ucwords(str_replace('_', ' ', $r)) }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-primary btn-xs w-full">Save Role</button>
            </form>
          </div>
        @else
          <p class="text-sm text-slate-400 mb-3">No portal login yet.</p>
          <button @click="createLogin = !createLogin" class="btn btn-primary btn-xs w-full">+ Create Login</button>
          <div x-show="createLogin" x-transition class="mt-3">
            <form method="POST" action="{{ route('hr.employees.portal-login', $employee->id) }}" class="space-y-2">
              @csrf
              <input type="email" name="email" class="input text-sm" placeholder="Email address"
                     value="{{ $employee->email }}" required>
              <input type="password" name="password" class="input text-sm" placeholder="Password (min 8 chars)" required minlength="8">
              <select name="role" class="select text-sm">
                @foreach($staffRoles ?? [] as $r)
                  <option value="{{ $r }}">{{ ucwords(str_replace('_', ' ', $r)) }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-primary btn-xs w-full">Create Account</button>
            </form>
          </div>
        @endif
      </div>

      <div class="card">
        <h3 class="font-semibold text-slate-700 mb-3">Recent Payroll</h3>
        @forelse($payroll as $p)
          <div class="flex justify-between items-center text-sm py-2 border-b border-slate-100 last:border-0">
            <span class="text-slate-600">{{ $p->month }}</span>
            <span class="font-semibold text-slate-800">₹{{ number_format($p->net_salary, 2) }}</span>
            <span class="{{ $p->status === 'paid' ? 'badge-green' : 'badge-slate' }} text-xs">{{ ucfirst($p->status) }}</span>
          </div>
        @empty
          <p class="text-sm text-slate-400">No payroll records.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
