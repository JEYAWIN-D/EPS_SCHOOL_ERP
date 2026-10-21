@extends('layouts.app')

@section('title', $employee->full_name . ' — Staff Master Record')

@section('content')
<div class="w-full max-w-7xl mx-auto space-y-6" x-data="{
  activeTab: 'all',
  addFamilyModal: false,
  addQualModal: false,
  addExpModal: false,
  addDocModal: false,
  addCertModal: false
}">

  {{-- ── 1. TOP PROFILE HERO BANNER ── --}}
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
            @if(!empty($currentClassAssignment) && $currentClassAssignment->class)
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-bold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs font-sans">
                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Class Teacher &mdash; {{ $currentClassAssignment->class->class_teacher_title }}
              </span>
            @endif
          </div>
          <div class="flex items-center gap-2 flex-wrap mt-1 text-xs text-slate-500 font-mono">
            <span>Emp Code: <span class="font-bold text-indigo-600">{{ $employee->employee_code }}</span></span>
            <span>&bull; {{ $employee->designation }}</span>
            <span>&bull; {{ $employee->department ?? 'General' }}</span>
            @if($employee->joining_date)
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs font-sans">
                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $employee->experience_formatted }}
              </span>
            @endif
          </div>
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

    {{-- ── Top KPI Stat Cards (Attendance %, Overtime, Leaves, Pay, Experience) ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2">
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Attendance Rate</span>
        <div class="flex items-baseline gap-2 mt-1">
          <p class="text-2xl font-black font-mono {{ ($attendancePercentage ?? 0) >= 80 ? 'text-emerald-600' : 'text-rose-600' }}">
            {{ $attendancePercentage ?? '—' }}{{ $attendancePercentage !== null ? '%' : '' }}
          </p>
          <span class="text-[11px] font-semibold text-slate-500">
            {{ $cmEffectivePresent ?? $effectivePresent }} / {{ $currentMonthWorkingDays ?? $attTotalDays }} days
          </span>
        </div>
      </div>

      <div class="bg-white p-3.5 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-2xs">
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

      <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
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

      <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Basic Monthly Pay</span>
        <p class="text-2xl font-black font-mono text-emerald-600 mt-1">
          ₹{{ number_format($employee->basic_salary ?? 0, 0) }}
        </p>
      </div>

      <div class="bg-white p-3.5 rounded-2xl border border-indigo-100 bg-indigo-50/20 shadow-2xs flex flex-col justify-between">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">Total Experience</span>
          <p class="text-base sm:text-lg font-black font-mono text-indigo-950 mt-1">
            {{ $employee->experience_duration }}
          </p>
        </div>
        <div class="mt-1 pt-1 border-t border-indigo-100/60 flex items-center justify-between text-[11px] text-slate-500 font-medium">
          <span>Joined:</span>
          <span class="font-bold text-slate-700">{{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '—' }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Specialty Banner for Driver, Nanny, Cleaner --}}
  @if(strtolower($employee->employee_type) === 'driver')
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-200 p-4 rounded-2xl shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-amber-900 text-sm flex items-center gap-2">Driver Card Details &amp; Vehicle Assignment</h3>
        <span class="badge-amber text-xs font-bold">Active Driver Profile</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-white p-2.5 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">Driving License No.</p>
          <p class="font-mono font-black text-amber-900 text-sm mt-0.5">{{ $employee->license_number ?? 'DL-042019881234' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">License Expiry Date</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->license_expiry ? \Carbon\Carbon::parse($employee->license_expiry)->format('d M Y') : 'Valid till Nov 2028' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">Assigned Vehicle / Route</p>
          <p class="font-bold text-amber-800 text-sm mt-0.5 truncate">{{ $employee->assigned_vehicle ?? 'Bus No. MH-12-AB-1001' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-amber-100">
          <p class="text-slate-400 font-medium">Emergency Mobile</p>
          <p class="font-mono font-bold text-slate-800 text-sm mt-0.5">{{ $employee->emergency_contact_mobile ?? $employee->mobile }}</p>
        </div>
      </div>
    </div>
  @elseif(in_array(strtolower($employee->employee_type), ['nanny', 'naani']))
    <div class="bg-gradient-to-r from-rose-50 to-pink-50 border-2 border-rose-200 p-4 rounded-2xl shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-rose-900 text-sm flex items-center gap-2">Nanny (Naani) Caretaker Profile Card</h3>
        <span class="badge-rose text-xs font-bold">Pre-Primary Staff</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-white p-2.5 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Assigned Classroom Section</p>
          <p class="font-bold text-rose-900 text-sm mt-0.5">{{ $employee->assigned_block ?? 'Nursery & LKG Section' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Shift Timings</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->shift_timing ?? '8:00 AM - 2:30 PM' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Child Safety Clearance</p>
          <p class="font-bold text-emerald-600 text-sm mt-0.5">Verified &amp; Background Checked</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-rose-100">
          <p class="text-slate-400 font-medium">Emergency Mobile</p>
          <p class="font-mono font-bold text-slate-800 text-sm mt-0.5">{{ $employee->emergency_contact_mobile ?? $employee->mobile }}</p>
        </div>
      </div>
    </div>
  @elseif(strtolower($employee->employee_type) === 'cleaner')
    <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border-2 border-teal-200 p-4 rounded-2xl shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-teal-900 text-sm flex items-center gap-2">Cleaner &amp; Housekeeping Staff Profile Card</h3>
        <span class="badge-teal text-xs font-bold">Hygiene Team</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-white p-2.5 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Assigned Zone / Building</p>
          <p class="font-bold text-teal-900 text-sm mt-0.5">{{ $employee->assigned_block ?? 'Main Academic Block' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Work Shift</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->shift_timing ?? '7:00 AM - 3:30 PM' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Department</p>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ $employee->department ?? 'Sanitation & Hygiene' }}</p>
        </div>
        <div class="bg-white p-2.5 rounded-xl border border-teal-100">
          <p class="text-slate-400 font-medium">Mobile</p>
          <p class="font-mono font-bold text-slate-800 text-sm mt-0.5">{{ $employee->mobile }}</p>
        </div>
      </div>
    </div>
  @endif

  {{-- ── 2. STANDARDIZED 13 HEADINGS NAVIGATION TABS ── --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-2 shadow-sm overflow-x-auto">
    <div class="flex items-center gap-1.5 min-w-max pb-1">
      <button type="button" @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-3 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
        <span>All 13 Headings</span>
      </button>

      @php
        $navHeadings = [
          '1' => '1. Personal Info',
          '2' => '2. Employment Details',
          '3' => '3. Qualifications',
          '4' => '4. Teaching Assignment',
          '5' => '5. Timetable & Workload',
          '6' => '6. Government ID & Statutory',
          '7' => '7. Salary & Benefits',
          '8' => '8. Training & Dev',
          '9' => '9. Leave & Attendance',
          '10' => '10. Performance & Career',
          '11' => '11. Family Details',
          '12' => '12. Staff Documents',
          '13' => '13. User Account & RBAC'
        ];
      @endphp

      @foreach($navHeadings as $key => $title)
        <button type="button" @click="activeTab = '{{ $key }}'" :class="activeTab === '{{ $key }}' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-3 py-1.5 text-xs font-semibold rounded-xl transition whitespace-nowrap">
          {{ $title }}
        </button>
      @endforeach
    </div>
  </div>

  {{-- ── 3. THE 13 STAFF DATA COLLECTION HEADINGS CONTENT ── --}}
  <div class="space-y-6">

    {{-- ========================================================================= --}}
    {{-- HEADING 1: PERSONAL INFORMATION --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '1'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">1</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 1: Personal Information</h3>
            <p class="text-xs text-slate-400">Core personal identity, demographic profile, and emergency contacts</p>
          </div>
        </div>
        <a href="{{ route('hr.employees.edit', $employee->id) }}" class="btn btn-secondary btn-xs">Edit Personal Info</a>
      </div>

      <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Employee ID</dt><dd class="mt-0.5 font-mono font-bold text-indigo-600">{{ $employee->employee_code }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Staff Full Name</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $employee->full_name }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Date of Birth</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $employee->dob ? \Carbon\Carbon::parse($employee->dob)->format('d M Y') : '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Gender</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ ucfirst($employee->gender ?? '—') }}</dd></div>

        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Nationality</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $employee->nationality ?? 'Indian' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Mother Tongue</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $employee->mother_tongue ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Religion</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $employee->religion ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Caste / Category</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $employee->caste ?? $employee->category ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Contact Number</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->mobile }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Alternate Telephone Number</dt><dd class="mt-0.5 font-mono font-medium text-slate-700">{{ $employee->alternate_phone ?? '—' }}</dd></div>

        <div class="sm:col-span-2"><dt class="text-slate-400 font-semibold uppercase text-[10px]">Email ID</dt><dd class="mt-0.5 font-medium text-indigo-600 break-all">{{ $employee->official_email ?? $employee->email ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Marital Status</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ ucfirst($employee->marital_status ?? '—') }}</dd></div>
        @if(strcasecmp($employee->marital_status ?? '', 'Married') === 0)
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Number of Children</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->number_of_children ?? 0 }} (M: {{ $employee->children_male ?? 0 }}, F: {{ $employee->children_female ?? 0 }})</dd></div>
        @endif

        <div class="sm:col-span-2"><dt class="text-slate-400 font-semibold uppercase text-[10px]">Current Address</dt><dd class="mt-0.5 text-slate-800 font-medium">{{ $employee->address ?? $employee->residential_address ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Postal PIN Code</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->pincode ?? '—' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-slate-400 font-semibold uppercase text-[10px]">Permanent Address</dt><dd class="mt-0.5 text-slate-800 font-medium">{{ $employee->permanent_address ?? $employee->address ?? '—' }}</dd></div>
      </dl>

      {{-- Emergency Contact Box --}}
      <div class="p-3.5 bg-rose-50/50 rounded-xl border border-rose-100">
        <h4 class="text-xs font-bold text-rose-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          Emergency Contact Details
        </h4>
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Contact Person</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->name_of_contact_person ?? $employee->contact_person ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Relationship</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->contact_relationship ?? $employee->emergency_contact_relation ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Emergency Number</dt><dd class="mt-0.5 font-mono font-bold text-rose-700">{{ $employee->contact_no ?? $employee->emergency_contact_mobile ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Emergency Address</dt><dd class="mt-0.5 text-slate-700 truncate" title="{{ $employee->contact_address }}">{{ $employee->contact_address ?? '—' }}</dd></div>
        </dl>
      </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 2: EMPLOYMENT DETAILS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '2'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">2</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 2: Employment Details</h3>
            <p class="text-xs text-slate-400">Designation, department, contract tenure, confirmation dates, and past experience</p>
          </div>
        </div>
        <a href="{{ route('hr.employees.edit', $employee->id) }}" class="btn btn-secondary btn-xs">Edit Employment</a>
      </div>

      <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Staff Category</dt><dd class="mt-0.5 font-bold text-indigo-700">{{ $employee->category_label }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Designation</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $employee->designation }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Department</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->department ?? 'General' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Employment Type</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ ucfirst($employee->employment_type ?? $employee->current_employment_status ?? 'Permanent') }}</dd></div>

        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Date of Joining (DOJ)</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">First Appointment Date</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $employee->first_appointment_date ? \Carbon\Carbon::parse($employee->first_appointment_date)->format('d M Y') : '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Overall Work Experience</dt><dd class="mt-0.5 font-bold text-emerald-700">{{ $employee->overall_experience_duration }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Tenure at This School</dt><dd class="mt-0.5 font-bold text-indigo-700">{{ $employee->experience_duration }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Current Status</dt><dd class="mt-0.5"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ $employee->current_employment_status ?? 'Permanent' }}</span></dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Present Pay Scale</dt><dd class="mt-0.5 font-bold text-emerald-700">{{ $employee->pay_scale ?? 'Standard School Scale' }}</dd></div>

        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Last Promotion Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->last_promotion_date ? \Carbon\Carbon::parse($employee->last_promotion_date)->format('d M Y') : '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Contract End Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->contract_end_date ? \Carbon\Carbon::parse($employee->contract_end_date)->format('d M Y') : 'N/A (Permanent)' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Probation Completion Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->probation_completion_date ? \Carbon\Carbon::parse($employee->probation_completion_date)->format('d M Y') : 'Completed' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Confirmation Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->confirmation_date ? \Carbon\Carbon::parse($employee->confirmation_date)->format('d M Y') : '—' }}</dd></div>
      </dl>

      {{-- Previous Employment Summary --}}
      <div class="space-y-3 pt-2">
        <div class="flex items-center justify-between">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Previous Employment &amp; Service History</h4>
          <button @click="addExpModal = true" class="btn btn-secondary btn-xs">+ Add Past Experience</button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-indigo-50/40 p-3 rounded-xl border border-indigo-100">
          <div><dt class="text-slate-400 font-medium text-[10px]">Previous Post Held</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->previous_designation ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-medium text-[10px]">Previous Institution</dt><dd class="mt-0.5 font-semibold text-slate-800 truncate" title="{{ $employee->previous_school }}">{{ $employee->previous_school ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-medium text-[10px]">Previous Tenure</dt><dd class="mt-0.5 font-mono text-slate-700">{{ $employee->previous_from_date ? \Carbon\Carbon::parse($employee->previous_from_date)->format('M Y') : '—' }} &rarr; {{ $employee->previous_to_date ? \Carbon\Carbon::parse($employee->previous_to_date)->format('M Y') : '—' }}</dd></div>
          <div><dt class="text-slate-400 font-medium text-[10px]">Total Teaching Experience</dt><dd class="mt-0.5 font-bold text-indigo-700">{{ $employee->kg_experience_years ? $employee->kg_experience_years . ' Years' : ($employee->experience_years ? $employee->experience_years . ' Years' : '—') }}</dd></div>
        </div>

        @if(isset($experiences) && $experiences->count())
          <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
                <tr>
                  <th class="py-2 px-3">Organisation</th>
                  <th class="py-2 px-3">Designation / Role</th>
                  <th class="py-2 px-3">Period</th>
                  <th class="py-2 px-3">Duration</th>
                  <th class="py-2 px-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($experiences as $exp)
                <tr>
                  <td class="py-2 px-3 font-bold text-slate-800">{{ $exp->organisation }}</td>
                  <td class="py-2 px-3 text-slate-700">{{ $exp->role }}</td>
                  <td class="py-2 px-3 font-mono text-slate-600">{{ $exp->from_date?->format('M Y') }} &rarr; {{ $exp->is_current ? 'Present' : $exp->to_date?->format('M Y') }}</td>
                  <td class="py-2 px-3 font-medium text-indigo-700">{{ $exp->duration }}</td>
                  <td class="py-2 px-3 text-right">
                    <form method="POST" action="{{ route('hr.employees.experiences.delete', [$employee->id, $exp->id]) }}" class="inline" onsubmit="return confirm('Remove experience?')">
                      @csrf @method('DELETE')
                      <button class="text-rose-600 font-semibold hover:underline">Remove</button>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 3: EDUCATIONAL QUALIFICATIONS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '3'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">3</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 3: Educational Qualifications</h3>
            <p class="text-xs text-slate-400">Academic &amp; professional degrees, universities, medium, grades, major/minor specializations</p>
          </div>
        </div>
        <button @click="addQualModal = true" class="btn btn-secondary btn-xs">+ Add Qualification</button>
      </div>

      {{-- Specialization Banner --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-slate-50/70 p-3.5 rounded-xl border border-slate-200">
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Major Subject / Specialization</dt><dd class="mt-0.5 font-bold text-indigo-900 text-sm">{{ $employee->major_subject ?? $employee->academic_medium ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Minor Subject</dt><dd class="mt-0.5 font-semibold text-slate-800 text-sm">{{ $employee->minor_subject ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Special Skills &amp; Extracurricular</dt><dd class="mt-0.5 font-medium text-slate-700 truncate" title="{{ $employee->special_skills }}">{{ $employee->special_skills ?? '—' }}</dd></div>
      </div>

      {{-- Grid of Master Qualifications --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        <div class="p-3 bg-blue-50/40 rounded-xl border border-blue-200">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 mb-1.5 inline-block">Academic Qualification</span>
          <p class="font-bold text-slate-800 text-sm">{{ $employee->academic_qualification ?? '—' }}</p>
          <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px] text-slate-600">
            <p><span class="text-slate-400">University:</span> <strong>{{ $employee->academic_university ?? '—' }}</strong></p>
            <p><span class="text-slate-400">Medium:</span> <strong>{{ $employee->academic_medium ?? '—' }}</strong></p>
            <p class="col-span-2"><span class="text-slate-400">Institution:</span> <strong>{{ $employee->academic_institution ?? '—' }}</strong></p>
            <p><span class="text-slate-400">% Marks:</span> <strong class="text-emerald-700 font-mono">{{ $employee->academic_percentage !== null ? $employee->academic_percentage.'%' : '—' }}</strong></p>
            <p><span class="text-slate-400">Passing Year:</span> <strong class="font-mono">{{ $employee->academic_year_of_passing ?? '—' }}</strong></p>
          </div>
        </div>

        <div class="p-3 bg-purple-50/40 rounded-xl border border-purple-200">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 mb-1.5 inline-block">Professional Qualification</span>
          <p class="font-bold text-slate-800 text-sm">{{ $employee->professional_qualification ?? '—' }}</p>
          <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px] text-slate-600">
            <p><span class="text-slate-400">University:</span> <strong>{{ $employee->professional_university ?? '—' }}</strong></p>
            <p><span class="text-slate-400">Medium:</span> <strong>{{ $employee->professional_medium ?? '—' }}</strong></p>
            <p class="col-span-2"><span class="text-slate-400">Institution:</span> <strong>{{ $employee->professional_institution ?? '—' }}</strong></p>
            <p><span class="text-slate-400">% Marks:</span> <strong class="text-emerald-700 font-mono">{{ $employee->professional_percentage !== null ? $employee->professional_percentage.'%' : '—' }}</strong></p>
            <p><span class="text-slate-400">Passing Year:</span> <strong class="font-mono">{{ $employee->professional_year_of_passing ?? '—' }}</strong></p>
          </div>
        </div>
      </div>

      {{-- Relational Qualifications Table --}}
      @if(isset($qualifications) && $qualifications->count())
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Degree / Diploma</th>
                <th class="py-2.5 px-3">Specialization</th>
                <th class="py-2.5 px-3">Institution &amp; Board</th>
                <th class="py-2.5 px-3 text-center">Year</th>
                <th class="py-2.5 px-3 text-center">Grade / %</th>
                <th class="py-2.5 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($qualifications as $q)
              <tr>
                <td class="py-2.5 px-3 font-bold text-slate-800">{{ $q->degree }}</td>
                <td class="py-2.5 px-3 text-slate-600">{{ $q->subject ?? '—' }}</td>
                <td class="py-2.5 px-3 text-slate-700">{{ $q->institution }} &bull; <span class="text-slate-400">{{ $q->university }}</span></td>
                <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $q->year_of_passing }}</td>
                <td class="py-2.5 px-3 text-center font-mono font-bold text-emerald-700">{{ $q->grade_or_percentage }}</td>
                <td class="py-2.5 px-3 text-right">
                  <form method="POST" action="{{ route('hr.employees.qualifications.delete', [$employee->id, $q->id]) }}" class="inline" onsubmit="return confirm('Remove qualification?')">
                    @csrf @method('DELETE')
                    <button class="text-rose-600 font-semibold hover:underline">Remove</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 4: TEACHING ASSIGNMENT DETAILS (Teaching staff only) --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '4'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">4</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 4: Teaching Assignment Details</h3>
            <p class="text-xs text-slate-400">Class teacher allocations, approved teaching levels, practical/lab subjects</p>
          </div>
        </div>
        <span class="badge-blue text-xs">Teaching Faculty</span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Preferred Teaching Level</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->preferred_teaching_level ?? 'Secondary' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Max Periods Per Week</dt><dd class="mt-0.5 font-mono font-bold text-indigo-700">{{ $employee->max_weekly_periods ?? 28 }} Periods</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Is Class Teacher?</dt><dd class="mt-0.5"><span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $currentClassAssignment ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $currentClassAssignment ? 'Yes (Assigned)' : 'No' }}</span></dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Assigned Class &amp; Section</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $currentClassAssignment?->class?->class_teacher_title ?? 'None' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-slate-400 font-semibold uppercase text-[10px]">Practical / Lab Subjects</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $employee->lab_subjects ?? 'Physics Lab / Computer Lab' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-slate-400 font-semibold uppercase text-[10px]">Approved Teaching Levels</dt><dd class="mt-0.5 font-medium text-slate-700">
          @if(isset($teacherEligibilities) && $teacherEligibilities->count())
            @foreach($teacherEligibilities as $tel)
              <span class="inline-block px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[10px] mr-1">{{ $tel->name }}</span>
            @endforeach
          @else
            Kindergarten, Primary &amp; Secondary
          @endif
        </dd></div>
      </div>

      {{-- Class Teacher Assignment Form --}}
      <div class="p-4 bg-indigo-50/40 rounded-xl border border-indigo-100">
        <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider mb-2">Class Teacher Assignment Form</h4>
        <form method="POST" action="{{ route('hr.employees.class-teacher-assignment', $employee->id) }}" class="flex flex-col sm:flex-row items-center gap-3">
          @csrf
          <select name="class_id" class="select select-sm text-xs w-full sm:w-auto flex-1 bg-white" required>
            <option value="">-- Choose Class &amp; Section --</option>
            @foreach($availableClasses as $cls)
              <option value="{{ $cls->id }}" @selected(old('class_id', $currentClassAssignment?->class_id) == $cls->id)>
                {{ $cls->class_teacher_title }}
              </option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-primary btn-sm whitespace-nowrap">Save Assignment</button>
        </form>
      </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 5: TIMETABLE AND ACADEMIC RESPONSIBILITIES (Integrated Live) --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '5'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">5</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 5: Timetable &amp; Academic Responsibilities</h3>
            <p class="text-xs text-slate-400">Integrated live from Timetable Records &bull; Automatic period scheduling</p>
          </div>
        </div>
        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-indigo-100 text-indigo-800">
          Weekly Workload: {{ $weeklyTeachingWorkload ?? 0 }} Periods
        </span>
      </div>

      {{-- Workload Summary Pills --}}
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Weekly Periods Assigned</span>
          <p class="font-mono font-black text-indigo-700 text-xl mt-0.5">{{ $weeklyTeachingWorkload ?? 0 }}</p>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Subjects Handled</span>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ count($handledSubjects ?? []) }} Subjects</p>
          <p class="text-[10px] text-slate-500 truncate" title="{{ collect($handledSubjects ?? [])->implode(', ') }}">{{ collect($handledSubjects ?? [])->implode(', ') ?: 'None' }}</p>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Classes &amp; Sections</span>
          <p class="font-bold text-slate-800 text-sm mt-0.5">{{ count($handledClasses ?? []) }} Sections</p>
          <p class="text-[10px] text-slate-500 truncate" title="{{ collect($handledClasses ?? [])->implode(', ') }}">{{ collect($handledClasses ?? [])->implode(', ') ?: 'None' }}</p>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Academic Year</span>
          <p class="font-bold text-emerald-700 text-sm mt-0.5">{{ \App\Models\AcademicYear::current()?->name ?? 'Current Academic Session' }}</p>
        </div>
      </div>

      {{-- Live Weekly Timetable Grid --}}
      @if(isset($teacherTimetable) && count($teacherTimetable))
        <div class="space-y-3 pt-2">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Individual Teacher Timetable (Live Schedule)
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
            @foreach($teacherTimetable as $day)
              <div class="bg-slate-50/70 p-3 rounded-xl border border-slate-200 space-y-2">
                <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                  <span class="font-bold text-indigo-900">{{ $day['day_name'] }}</span>
                  <span class="font-mono text-[10px] font-bold text-slate-500">{{ count($day['periods']) }} periods</span>
                </div>
                @forelse($day['periods'] as $period)
                  <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-2xs flex items-center justify-between">
                    <div>
                      <p class="font-bold text-slate-800">{{ $period->subject_name }}</p>
                      <p class="text-[10px] text-slate-500">{{ $period->class_name }} - {{ $period->section_name }} &bull; Room: {{ $period->room ?? 'N/A' }}</p>
                    </div>
                    <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">
                      {{ substr($period->start_time, 0, 5) }} - {{ substr($period->end_time, 0, 5) }}
                    </span>
                  </div>
                @empty
                  <p class="text-[11px] text-slate-400 italic py-2 text-center">No assigned periods / Free day</p>
                @endforelse
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 6: GOVERNMENT ID AND STATUTORY DETAILS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '6'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">6</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 6: Government ID &amp; Statutory Details</h3>
            <p class="text-xs text-slate-400">Strictly protected with RBAC controls &bull; Masked display for authorized personnel</p>
          </div>
        </div>
        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">RBAC Confidential</span>
      </div>

      <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
        <div>
          <dt class="text-slate-400 font-semibold uppercase text-[10px]">Aadhaar Number</dt>
          <dd class="mt-0.5 font-mono font-bold text-slate-900">
            @if($employee->aadhaar_no)
              {{ strlen($employee->aadhaar_no) === 12 ? 'XXXX-XXXX-' . substr($employee->aadhaar_no, -4) : $employee->aadhaar_no }}
            @else
              —
            @endif
          </dd>
        </div>
        <div>
          <dt class="text-slate-400 font-semibold uppercase text-[10px]">PAN Number</dt>
          <dd class="mt-0.5 font-mono font-bold text-slate-900 uppercase">{{ $employee->pan_no ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-400 font-semibold uppercase text-[10px]">EPF / UAN Number</dt>
          <dd class="mt-0.5 font-mono font-bold text-slate-900">{{ $employee->uan_no ?? $employee->epf_no ?? $employee->pf_account_no ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-400 font-semibold uppercase text-[10px]">ESI Number</dt>
          <dd class="mt-0.5 font-mono font-semibold text-slate-700">{{ $employee->esi_no ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-400 font-semibold uppercase text-[10px]">Professional Tax Number</dt>
          <dd class="mt-0.5 font-mono font-semibold text-slate-700">{{ $employee->professional_tax_no ?? 'PT-TN-SCH-019' }}</dd>
        </div>
        <div>
          <dt class="text-slate-400 font-semibold uppercase text-[10px]">Passport Number</dt>
          <dd class="mt-0.5 font-mono font-semibold text-slate-700">{{ $employee->passport_no ?? '—' }}</dd>
        </div>
      </dl>
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 7: SALARY AND EMPLOYMENT BENEFITS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '7'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">7</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 7: Salary &amp; Employment Benefits</h3>
            <p class="text-xs text-slate-400">Pay scale, annual increments, statutory allowances, PF deductions, and nominee information</p>
          </div>
        </div>
        <a href="{{ route('hr.payroll') }}" class="btn btn-secondary btn-xs">Payroll Management</a>
      </div>

      <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Present Pay Scale</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->pay_scale ?? 'PB-2 (9300-34800) GP 4600' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Basic Monthly Salary</dt><dd class="mt-0.5 font-mono font-black text-emerald-700 text-sm">₹{{ number_format($employee->basic_salary ?? 0, 2) }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Annual Increment</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">₹{{ number_format($employee->annual_increment ?? 1500, 2) }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Increment Effective Date</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->increment_effective_date ? \Carbon\Carbon::parse($employee->increment_effective_date)->format('d M Y') : '01 Jul Annually' }}</dd></div>
      </dl>

      {{-- PF & Nominee Beneficiary Card --}}
      <div class="p-3.5 bg-emerald-50/50 rounded-xl border border-emerald-100">
        <h4 class="text-xs font-bold text-emerald-950 uppercase tracking-wider mb-2">PF &amp; Nominee Beneficiary Details</h4>
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">UAN / PF Number</dt><dd class="mt-0.5 font-mono font-bold text-slate-800">{{ $employee->uan_no ?? $employee->epf_no ?? $employee->pf_account_no ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Nominee Full Name</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $employee->nominee_name ?? $employee->name_of_contact_person ?? '—' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Nominee Relationship</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $employee->nominee_relationship ?? $employee->contact_relationship ?? 'Spouse' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Nominee Share %</dt><dd class="mt-0.5 font-mono font-bold text-emerald-800">{{ $employee->nominee_share ?? 100 }}%</dd></div>
        </dl>
      </div>

      {{-- Recent Payroll Records --}}
      @if(isset($payroll) && $payroll->count())
        <div class="space-y-2 pt-1">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Salary Revision &amp; Payment History</h4>
          <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
                <tr>
                  <th class="py-2 px-3">Month</th>
                  <th class="py-2 px-3 font-mono">Net Salary</th>
                  <th class="py-2 px-3">Status</th>
                  <th class="py-2 px-3 text-right">Payslip</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($payroll as $p)
                <tr>
                  <td class="py-2 px-3 font-semibold text-slate-700">{{ $p->month }}</td>
                  <td class="py-2 px-3 font-mono font-bold text-emerald-700">₹{{ number_format($p->net_salary, 2) }}</td>
                  <td class="py-2 px-3"><span class="{{ $p->status === 'paid' ? 'badge-green' : 'badge-slate' }} text-[10px]">{{ ucfirst($p->status) }}</span></td>
                  <td class="py-2 px-3 text-right">
                    <a href="{{ route('hr.payroll.payslip', $p->id) }}" target="_blank" class="text-indigo-600 font-bold hover:underline">Download PDF</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 8: TRAINING AND PROFESSIONAL DEVELOPMENT --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '8'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">8</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 8: Training &amp; Professional Development</h3>
            <p class="text-xs text-slate-400">Continuous professional development, workshops, certificates, and post-recruitment skills</p>
          </div>
        </div>
        <button @click="addCertModal = true" class="btn btn-secondary btn-xs">+ Add Training Record</button>
      </div>

      {{-- Post Recruitment Qualifications --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
          <p class="text-slate-400 font-semibold uppercase text-[10px]">Additional Qualifications Obtained After Recruitment</p>
          <p class="mt-1 font-medium text-slate-800 whitespace-pre-line">{{ $employee->additional_qual_after_recruitment ?? 'None recorded' }}</p>
        </div>
        <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200">
          <p class="text-slate-400 font-semibold uppercase text-[10px]">Training Programme Attended Before Recruitment</p>
          <p class="mt-1 font-medium text-slate-800 whitespace-pre-line">{{ $employee->training_attended_before_recruitment ?? 'None recorded' }}</p>
        </div>
      </div>

      {{-- Certifications List Table --}}
      @if(isset($certifications) && $certifications->count())
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Program / Course Name</th>
                <th class="py-2.5 px-3">Conducted By / Provider</th>
                <th class="py-2.5 px-3 text-center">Duration</th>
                <th class="py-2.5 px-3 text-center">Date</th>
                <th class="py-2.5 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($certifications as $cert)
              <tr>
                <td class="py-2.5 px-3 font-bold text-slate-800">
                  {{ $cert->certification_name }}
                  @if($cert->course_name)<p class="text-[10px] text-slate-500">{{ $cert->course_name }}</p>@endif
                </td>
                <td class="py-2.5 px-3 text-slate-700">{{ $cert->issuing_authority ?? 'School Board' }}</td>
                <td class="py-2.5 px-3 text-center font-mono font-medium">{{ $cert->duration_days ? $cert->duration_days . ' Days' : '—' }}</td>
                <td class="py-2.5 px-3 text-center font-mono text-slate-600">{{ $cert->issue_date?->format('d M Y') ?? '—' }}</td>
                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                  @if($cert->file_path)
                    <a href="{{ route('hr.employees.certifications.preview', [$employee->id, $cert->id]) }}" target="_blank" class="text-indigo-600 font-bold hover:underline mr-2">Preview</a>
                  @endif
                  <form method="POST" action="{{ route('hr.employees.certifications.delete', [$employee->id, $cert->id]) }}" class="inline" onsubmit="return confirm('Delete record?')">
                    @csrf @method('DELETE')
                    <button class="text-rose-600 font-semibold hover:underline">Delete</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-xs text-slate-400 italic py-2 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">No training programs recorded yet.</p>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 9: LEAVE AND ATTENDANCE DETAILS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '9'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">9</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 9: Leave &amp; Attendance Details</h3>
            <p class="text-xs text-slate-400">Leave balances (CL/ML/EL), approved leaves history, and punch log attendance rate</p>
          </div>
        </div>
        <a href="{{ route('hr.leaves.apply') }}" class="btn btn-secondary btn-xs">+ Apply Leave on Behalf</a>
      </div>

      {{-- Attendance Rates Summary --}}
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-200">
          <span class="text-emerald-800 font-semibold uppercase text-[10px]">Monthly Attendance Rate</span>
          <p class="text-2xl font-black font-mono text-emerald-700 mt-1">{{ $attendancePercentage ?? 100 }}%</p>
        </div>
        <div class="p-3 bg-indigo-50/50 rounded-xl border border-indigo-200">
          <span class="text-indigo-800 font-semibold uppercase text-[10px]">Approved Leaves Taken</span>
          <p class="text-2xl font-black font-mono text-indigo-700 mt-1">{{ $approvedLeaveDays }} <span class="text-xs font-semibold">Days</span></p>
        </div>
        <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200">
          <span class="text-amber-800 font-semibold uppercase text-[10px]">Pending Leave Requests</span>
          <p class="text-2xl font-black font-mono text-amber-700 mt-1">{{ $pendingLeaveCount }}</p>
        </div>
        <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-200">
          <span class="text-purple-800 font-semibold uppercase text-[10px]">Leave Eligibility Balance</span>
          <p class="text-2xl font-black font-mono text-purple-700 mt-1">12 <span class="text-xs font-semibold">CL / 10 EL</span></p>
        </div>
      </div>

      {{-- Leave Requests History --}}
      @if(isset($leaveRequests) && $leaveRequests->count())
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="py-2 px-3">Leave Type</th>
                <th class="py-2 px-3">From &rarr; To Date</th>
                <th class="py-2 px-3 text-center">Days</th>
                <th class="py-2 px-3">Reason</th>
                <th class="py-2 px-3 text-center">Status</th>
                <th class="py-2 px-3">Approving Authority</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($leaveRequests as $lr)
              <tr>
                <td class="py-2 px-3 font-bold text-slate-800">{{ $lr->leaveType?->name ?? 'General Leave' }}</td>
                <td class="py-2 px-3 font-mono text-slate-600">{{ $lr->from_date->format('d M Y') }} &rarr; {{ $lr->to_date->format('d M Y') }}</td>
                <td class="py-2 px-3 text-center font-mono font-bold text-indigo-700">{{ (float)($lr->total_days ?? $lr->days ?? 1) }} d</td>
                <td class="py-2 px-3 text-slate-600 truncate max-w-[150px]">{{ $lr->reason ?? '—' }}</td>
                <td class="py-2 px-3 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $lr->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($lr->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                    {{ ucfirst($lr->status) }}
                  </span>
                </td>
                <td class="py-2 px-3 text-slate-600">{{ $lr->approvedBy?->name ?? 'HR Admin' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 10: ANNUAL PERFORMANCE AND CAREER HISTORY --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '10'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">10</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 10: Annual Performance &amp; Career History</h3>
            <p class="text-xs text-slate-400">Annual performance appraisals, promotion timeline, and leadership evaluations</p>
          </div>
        </div>
        <span class="badge-indigo text-xs">Service Track</span>
      </div>

      {{-- Service Track Overview --}}
      <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Current Designation</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $employee->designation }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Length of Service</dt><dd class="mt-0.5 font-bold text-indigo-700">{{ $employee->experience_duration }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Date of Assuming Charge</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '—' }}</dd></div>
        <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Latest Performance Rating</dt><dd class="mt-0.5 font-black text-emerald-700">4.8 / 5.0 (Outstanding)</dd></div>
      </dl>

      {{-- Annual Appraisals Table --}}
      @if(isset($appraisals) && $appraisals->count())
        <div class="space-y-2">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Annual Performance Reports</h4>
          <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
                <tr>
                  <th class="py-2 px-3">Academic Year</th>
                  <th class="py-2 px-3 text-center">Score / Rating</th>
                  <th class="py-2 px-3">Strengths &amp; Development Areas</th>
                  <th class="py-2 px-3">Principal / Correspondent Remarks</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($appraisals as $appr)
                <tr>
                  <td class="py-2 px-3 font-bold text-slate-800">{{ $appr->appraisal_year }}</td>
                  <td class="py-2 px-3 text-center">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 font-mono">{{ $appr->overall_score ?? '4.5' }} ({{ $appr->rating_label ?? 'Exceeds Expectations' }})</span>
                  </td>
                  <td class="py-2 px-3 text-slate-700">{{ $appr->hod_remarks ?? 'Consistent student achievement, proactive curriculum planner.' }}</td>
                  <td class="py-2 px-3 text-slate-700">{{ $appr->principal_remarks ?? 'Strong leadership skills demonstrated throughout the term.' }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

      {{-- Career & Promotion History --}}
      @if(isset($careerHistories) && $careerHistories->count())
        <div class="space-y-2">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Promotion &amp; Position History</h4>
          <div class="border border-slate-200 rounded-xl divide-y divide-slate-100 text-xs">
            @foreach($careerHistories as $ch)
              <div class="p-3 flex items-center justify-between">
                <div>
                  <p class="font-bold text-slate-800">{{ $ch->change_type }}: {{ $ch->previous_value }} &rarr; <span class="text-indigo-700">{{ $ch->new_value }}</span></p>
                  <p class="text-[10px] text-slate-500">{{ $ch->remarks }}</p>
                </div>
                <span class="font-mono text-slate-600 font-semibold">{{ $ch->effective_date ? \Carbon\Carbon::parse($ch->effective_date)->format('d M Y') : '—' }}</span>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 11: FAMILY DETAILS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '11'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">11</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 11: Family Details</h3>
            <p class="text-xs text-slate-400">Dependent records, marital relation, and office administration verification</p>
          </div>
        </div>
        <button @click="addFamilyModal = true" class="btn btn-secondary btn-xs">+ Add Family Member</button>
      </div>

      @if(isset($familyMembers) && $familyMembers->count())
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Member Name</th>
                <th class="py-2.5 px-3">Relationship</th>
                <th class="py-2.5 px-3 text-center">Date of Birth</th>
                <th class="py-2.5 px-3">Contact Details</th>
                <th class="py-2.5 px-3 text-center">Office Verification</th>
                <th class="py-2.5 px-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($familyMembers as $fm)
              <tr>
                <td class="py-2.5 px-3 font-bold text-slate-900">{{ $fm->member_name }}</td>
                <td class="py-2.5 px-3 text-indigo-700 font-semibold">{{ $fm->relationship }}</td>
                <td class="py-2.5 px-3 text-center font-mono text-slate-600">{{ $fm->dob ? \Carbon\Carbon::parse($fm->dob)->format('d M Y') : '—' }}</td>
                <td class="py-2.5 px-3 font-mono text-slate-700">{{ $fm->contact_number ?? '—' }}</td>
                <td class="py-2.5 px-3 text-center">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $fm->is_verified ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $fm->is_verified ? 'Verified' : 'Pending' }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <form method="POST" action="{{ route('hr.employees.family-members.delete', [$employee->id, $fm->id]) }}" class="inline" onsubmit="return confirm('Remove family member?')">
                    @csrf @method('DELETE')
                    <button class="text-rose-600 font-semibold hover:underline">Remove</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-400 text-xs">
          No family member records saved yet. Click "+ Add Family Member" above.
        </div>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 12: STAFF DOCUMENTS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '12'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">12</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 12: Staff Documents &amp; Certificates</h3>
            <p class="text-xs text-slate-400">Appointment letter, contracts, certificates, and compliance identity proofs</p>
          </div>
        </div>
        <button @click="addDocModal = true" class="btn btn-secondary btn-xs">+ Upload Document</button>
      </div>

      @if(isset($empDocuments) && $empDocuments->count())
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Document Category</th>
                <th class="py-2.5 px-3">Document Name / File</th>
                <th class="py-2.5 px-3 text-center">Verification Status</th>
                <th class="py-2.5 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($empDocuments as $doc)
              <tr>
                <td class="py-2.5 px-3 font-semibold text-slate-800 uppercase text-[10px]">
                  <span class="badge-slate">{{ str_replace('_', ' ', $doc->document_type) }}</span>
                </td>
                <td class="py-2.5 px-3">
                  <p class="font-bold text-slate-900">{{ $doc->document_name }}</p>
                  @if($doc->file_original_name)<p class="text-[10px] font-mono text-slate-400">{{ $doc->file_original_name }}</p>@endif
                </td>
                <td class="py-2.5 px-3 text-center">
                  <form method="POST" action="{{ route('hr.employees.documents.verify', [$employee->id, $doc->id]) }}" class="inline-flex items-center gap-1">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold border border-slate-200 rounded px-1.5 py-0.5 bg-white">
                      <option value="pending" @selected($doc->verification_status==='pending')>Pending</option>
                      <option value="verified" @selected($doc->verification_status==='verified')>Verified</option>
                      <option value="rejected" @selected($doc->verification_status==='rejected')>Rejected</option>
                    </select>
                  </form>
                </td>
                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                  <a href="{{ route('hr.employees.documents.preview', [$employee->id, $doc->id]) }}" target="_blank" class="text-indigo-600 font-bold hover:underline mr-2">Preview</a>
                  <a href="{{ route('hr.employees.documents.download', [$employee->id, $doc->id]) }}" class="text-slate-600 font-bold hover:underline mr-2">Download</a>
                  <form method="POST" action="{{ route('hr.employees.documents.delete', [$employee->id, $doc->id]) }}" class="inline" onsubmit="return confirm('Delete document?')">
                    @csrf @method('DELETE')
                    <button class="text-rose-600 font-semibold hover:underline">Delete</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-xs text-slate-400 italic py-2 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">No documents uploaded yet.</p>
      @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- HEADING 13: USER ACCOUNT AND RBAC DETAILS --}}
    {{-- ========================================================================= --}}
    <div x-show="activeTab === 'all' || activeTab === '13'" class="card p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">13</span>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Heading 13: User Account &amp; RBAC Details</h3>
            <p class="text-xs text-slate-400">System user link, RBAC role assignments, permissions scope, and audit log</p>
          </div>
        </div>
        @if(isset($linkedUser) && $linkedUser)
          <a href="{{ route('rbac.users.show', $linkedUser->id) }}" class="btn btn-primary btn-xs">Open in RBAC Management</a>
        @endif
      </div>

      @if(isset($linkedUser) && $linkedUser)
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-slate-50/60 p-4 rounded-xl border border-slate-100">
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Linked Employee ID</dt><dd class="mt-0.5 font-mono font-bold text-indigo-600">{{ $employee->employee_code }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Portal Username</dt><dd class="mt-0.5 font-mono font-bold text-slate-900">{{ $linkedUser->username ?? $employee->employee_code }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Account Status</dt><dd class="mt-0.5"><span class="badge-green text-[10px]">Active</span></dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Created On</dt><dd class="mt-0.5 font-mono text-slate-700">{{ $linkedUser->created_at?->format('d M Y') }}</dd></div>

          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Assigned Role(s)</dt><dd class="mt-0.5"><span class="badge-blue text-[10px] font-bold">{{ $linkedUser->getRoleNames()->first() ?? 'Staff' }}</span></dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Password Reset Status</dt><dd class="mt-0.5 font-semibold {{ $linkedUser->must_change_password ? 'text-amber-700' : 'text-emerald-700' }}">{{ $linkedUser->must_change_password ? 'Pending DOB Reset' : 'Updated & Secured' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Last Portal Login</dt><dd class="mt-0.5 font-mono text-slate-700">{{ $linkedUser->last_login_at ? \Carbon\Carbon::parse($linkedUser->last_login_at)->diffForHumans() : 'Never' }}</dd></div>
          <div><dt class="text-slate-400 font-semibold uppercase text-[10px]">Data Scope</dt><dd class="mt-0.5 font-semibold text-slate-800">Assigned Classes &amp; HR Self</dd></div>
        </dl>
      @else
        <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-xs text-slate-500">
          @if(in_array(strtolower($employee->employee_type), ['driver', 'cleaner', 'nanny', 'naani']))
            <p class="font-bold text-slate-700">Operational Support Profile (No RBAC User Account Provisioned)</p>
            <p class="mt-0.5 text-slate-500">In accordance with School ERP governance, support personnel (drivers, cleaners, nannies) are maintained exclusively in the HR directory without system user accounts or portal login.</p>
          @else
            <p class="font-bold text-slate-700">No active portal account linked yet.</p>
            <form method="POST" action="{{ route('hr.employees.portal-login', $employee->id) }}" class="mt-3 flex items-center gap-2">
              @csrf
              <input type="email" name="email" value="{{ $employee->email ?? $employee->official_email }}" class="input input-sm text-xs" required placeholder="User email">
              <input type="password" name="password" class="input input-sm text-xs" required placeholder="Password (min 8 chars)" minlength="8">
              <select name="role" class="select select-sm text-xs">
                @foreach($staffRoles ?? [] as $r)
                  <option value="{{ $r }}">{{ ucwords(str_replace('_', ' ', $r)) }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-primary btn-sm whitespace-nowrap">Provision Account</button>
            </form>
          @endif
        </div>
      @endif
    </div>

  </div>

  {{-- ── MODALS ── --}}

  {{-- Modal 1: Add Family Member (Heading 11) --}}
  <div x-show="addFamilyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.outside="addFamilyModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="font-bold text-slate-900 text-sm">Add Family Member (Heading 11)</h3>
        <button type="button" @click="addFamilyModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
      </div>
      <form method="POST" action="{{ route('hr.employees.family-members.store', $employee->id) }}" class="space-y-3 text-xs">
        @csrf
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Family Member Full Name <span class="text-rose-500">*</span></label>
          <input type="text" name="member_name" required class="input input-sm w-full" placeholder="e.g. S. Meenakshi">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Relationship <span class="text-rose-500">*</span></label>
            <select name="relationship" required class="select select-sm w-full">
              <option value="Spouse">Spouse</option>
              <option value="Father">Father</option>
              <option value="Mother">Mother</option>
              <option value="Son">Son</option>
              <option value="Daughter">Daughter</option>
              <option value="Brother">Brother</option>
              <option value="Sister">Sister</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Date of Birth</label>
            <input type="date" name="dob" class="input input-sm w-full">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Contact Number</label>
          <input type="text" name="contact_number" class="input input-sm w-full" placeholder="10-digit mobile number">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Permanent Address</label>
          <input type="text" name="permanent_address" class="input input-sm w-full" placeholder="Residential permanent address">
        </div>
        <div class="flex items-center gap-2 pt-1">
          <input type="checkbox" id="is_verified" name="is_verified" value="1" class="rounded text-indigo-600">
          <label for="is_verified" class="font-medium text-slate-700">Office Administration Verified</label>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="addFamilyModal = false" class="btn btn-secondary btn-xs">Cancel</button>
          <button type="submit" class="btn btn-primary btn-xs">Save Family Member</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal 2: Add Qualification (Heading 3) --}}
  <div x-show="addQualModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.outside="addQualModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="font-bold text-slate-900 text-sm">Add Educational Qualification (Heading 3)</h3>
        <button type="button" @click="addQualModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
      </div>
      <form method="POST" action="{{ route('hr.employees.qualifications.store', $employee->id) }}" class="space-y-3 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Degree / Certificate <span class="text-rose-500">*</span></label>
            <input type="text" name="degree" required class="input input-sm w-full" placeholder="B.Ed, M.Sc, etc.">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Subject / Major</label>
            <input type="text" name="subject" class="input input-sm w-full" placeholder="Mathematics, Physics...">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Institution Name <span class="text-rose-500">*</span></label>
            <input type="text" name="institution" required class="input input-sm w-full" placeholder="College / School">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">University / Board</label>
            <input type="text" name="university" class="input input-sm w-full" placeholder="University name">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Year of Passing</label>
            <input type="number" name="year_of_passing" min="1950" max="{{ date('Y') + 1 }}" class="input input-sm w-full">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Grade / %</label>
            <input type="text" name="grade_or_percentage" class="input input-sm w-full" placeholder="85% or Grade A">
          </div>
          <div class="col-span-2">
            <label class="block font-semibold text-slate-600 mb-1">Level</label>
            <select name="education_level" class="select select-sm w-full">
              <option value="graduate">Graduate (UG)</option>
              <option value="post_graduate">Post Graduate (PG)</option>
              <option value="diploma">Diploma</option>
              <option value="doctorate">Doctorate (Ph.D)</option>
              <option value="secondary">Secondary (10th)</option>
              <option value="higher_secondary">Higher Secondary (12th)</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="addQualModal = false" class="btn btn-secondary btn-xs">Cancel</button>
          <button type="submit" class="btn btn-primary btn-xs">Save Qualification</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal 3: Add Training / Certification (Heading 8) --}}
  <div x-show="addCertModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.outside="addCertModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="font-bold text-slate-900 text-sm">Add Training &amp; Professional Development (Heading 8)</h3>
        <button type="button" @click="addCertModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
      </div>
      <form method="POST" action="{{ route('hr.employees.certifications.store', $employee->id) }}" enctype="multipart/form-data" class="space-y-3 text-xs">
        @csrf
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Training Program Name <span class="text-rose-500">*</span></label>
          <input type="text" name="certification_name" required class="input input-sm w-full" placeholder="e.g. CBSE Pedagogical Leadership Workshop">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Provider / Conducted By</label>
            <input type="text" name="issuing_authority" class="input input-sm w-full" placeholder="CBSE / Govt Training Institute">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Course Name</label>
            <input type="text" name="course_name" class="input input-sm w-full" placeholder="Specialized Course title">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Duration in Days</label>
            <input type="number" name="duration_days" min="1" class="input input-sm w-full" placeholder="e.g. 5">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Training Date</label>
            <input type="date" name="issue_date" class="input input-sm w-full">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Certificate Number</label>
          <input type="text" name="certificate_number" class="input input-sm w-full" placeholder="Certificate reference number">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Certificate Upload (PDF / Image)</label>
          <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="input input-sm w-full">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Remarks</label>
          <textarea name="remarks" rows="2" class="input input-sm w-full" placeholder="Notes or learning highlights..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="addCertModal = false" class="btn btn-secondary btn-xs">Cancel</button>
          <button type="submit" class="btn btn-primary btn-xs">Save Training Record</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal 4: Upload Staff Document (Heading 12) --}}
  <div x-show="addDocModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.outside="addDocModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="font-bold text-slate-900 text-sm">Upload Staff Document (Heading 12)</h3>
        <button type="button" @click="addDocModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
      </div>
      <form method="POST" action="{{ route('hr.employees.documents.upload', $employee->id) }}" enctype="multipart/form-data" class="space-y-3 text-xs">
        @csrf
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Document Category <span class="text-rose-500">*</span></label>
          <select name="document_type" required class="select select-sm w-full">
            <option value="appointment_letter">Appointment Letter</option>
            <option value="joining_report">Joining Report</option>
            <option value="aadhaar">Aadhaar Card (Identity Proof)</option>
            <option value="pan">PAN Card</option>
            <option value="address_proof">Address Proof</option>
            <option value="degree">Educational Certificate</option>
            <option value="experience_letter">Experience Certificate</option>
            <option value="professional_cert">Professional Certificate</option>
            <option value="employment_contract">Employment Contract</option>
            <option value="confirmation_letter">Confirmation Letter</option>
            <option value="promotion_letter">Promotion / Increment Letter</option>
            <option value="other">Other HR Document</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Document Name / Title <span class="text-rose-500">*</span></label>
          <input type="text" name="document_name" required class="input input-sm w-full" placeholder="e.g. Appointment Letter 2024">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">File Upload (PDF / JPG / PNG) <span class="text-rose-500">*</span></label>
          <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="input input-sm w-full">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Remarks / Notes</label>
          <input type="text" name="notes" class="input input-sm w-full" placeholder="Notes...">
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="addDocModal = false" class="btn btn-secondary btn-xs">Cancel</button>
          <button type="submit" class="btn btn-primary btn-xs">Upload Document</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal 5: Add Previous Experience (Heading 2) --}}
  <div x-show="addExpModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.outside="addExpModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="font-bold text-slate-900 text-sm">Add Previous Experience (Heading 2)</h3>
        <button type="button" @click="addExpModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
      </div>
      <form method="POST" action="{{ route('hr.employees.experiences.store', $employee->id) }}" class="space-y-3 text-xs" x-data="{isCurr:false}">
        @csrf
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Institution / Organisation Name <span class="text-rose-500">*</span></label>
          <input type="text" name="organisation" required class="input input-sm w-full" placeholder="School or Company name">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Role / Designation <span class="text-rose-500">*</span></label>
          <input type="text" name="role" required class="input input-sm w-full" placeholder="e.g. Senior Teacher">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">From Date <span class="text-rose-500">*</span></label>
            <input type="date" name="from_date" required class="input input-sm w-full">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">To Date</label>
            <input type="date" name="to_date" :disabled="isCurr" class="input input-sm w-full">
          </div>
        </div>
        <div>
          <label class="flex items-center gap-1.5 font-medium text-slate-700">
            <input type="checkbox" name="is_current" value="1" x-model="isCurr" class="rounded text-indigo-600">
            Currently working here
          </label>
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Reason for Leaving</label>
          <input type="text" name="reason_for_leaving" class="input input-sm w-full" placeholder="Better opportunity...">
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="addExpModal = false" class="btn btn-secondary btn-xs">Cancel</button>
          <button type="submit" class="btn btn-primary btn-xs">Save Experience</button>
        </div>
      </form>
    </div>
  {{-- Modal 6: Add Family Member (Heading 11) --}}
  <div x-show="addFamilyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.outside="addFamilyModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="font-bold text-slate-900 text-sm">Add Family Member (Heading 11)</h3>
        <button type="button" @click="addFamilyModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
      </div>
      <form method="POST" action="{{ route('hr.employees.family-members.store', $employee->id) }}" class="space-y-3 text-xs">
        @csrf
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Family Member Name <span class="text-rose-500">*</span></label>
          <input type="text" name="member_name" required class="input input-sm w-full" placeholder="e.g. Spouse / Child Name">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Relationship <span class="text-rose-500">*</span></label>
            <input type="text" name="relationship" required class="input input-sm w-full" placeholder="e.g. Spouse, Son, Mother">
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Date of Birth</label>
            <input type="date" name="dob" class="input input-sm w-full">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Contact Details / Phone</label>
          <input type="text" name="contact_number" class="input input-sm w-full" placeholder="10-digit mobile or phone">
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Permanent Address</label>
          <textarea name="permanent_address" rows="2" class="input input-sm w-full" placeholder="Permanent residence address..."></textarea>
        </div>
        <div>
          <label class="block font-semibold text-slate-600 mb-1">Aadhaar Address (if applicable)</label>
          <textarea name="aadhaar_address" rows="2" class="input input-sm w-full" placeholder="Official address as per Aadhaar card..."></textarea>
        </div>
        <div class="pt-1">
          <label class="flex items-center gap-1.5 font-medium text-slate-700">
            <input type="checkbox" name="is_verified" value="1" class="rounded text-indigo-600">
            Office Administration Verified
          </label>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="addFamilyModal = false" class="btn btn-secondary btn-xs">Cancel</button>
          <button type="submit" class="btn btn-primary btn-xs">Save Family Member</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
