@extends('layouts.app')

@section('title', 'Add New Staff Member — Master Record')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
  category: '{{ old('employee_type', 'teaching') }}',
  validateForm(e) {
    const form = e.target;
    if (!form.checkValidity()) {
      e.preventDefault();
      const firstInvalid = form.querySelector(':invalid');
      if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus();
      }
      return false;
    }
    // Check TO >= FROM in previous employment
    const fromDate = document.getElementById('previous_from_date')?.value;
    const toDate = document.getElementById('previous_to_date')?.value;
    if (fromDate && toDate && toDate < fromDate) {
      e.preventDefault();
      alert('Previous Employment TO date cannot be earlier than FROM date.');
      document.getElementById('previous_to_date')?.focus();
      return false;
    }
    // Check marks range 0-100
    const checkMarks = ['academic_percentage', 'professional_percentage', 'other_qual_1_percentage', 'other_qual_2_percentage'];
    for (let id of checkMarks) {
      const el = document.getElementById(id);
      if (el && el.value !== '') {
        const val = parseFloat(el.value);
        if (isNaN(val) || val < 0 || val > 100) {
          e.preventDefault();
          alert('Percentage marks must be between 0 and 100.');
          el.focus();
          return false;
        }
      }
    }
  }
}">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div class="flex items-center gap-3">
      <a href="{{ route('hr.employees') }}" class="btn-icon" title="Back to Staff Directory">
        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <h1 class="page-title text-xl font-bold text-slate-900">Add New Staff Member</h1>
        <p class="text-xs text-slate-500 mt-0.5">Complete all required sections as per the Staff Master Data specification.</p>
      </div>
    </div>
  </div>

  {{-- Error Banner --}}
  @if (isset($errors) && $errors->any())
  <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
    <svg class="w-5 h-5 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div class="flex-1">
      <h4 class="font-bold text-rose-900">Staff Creation Blocked — Validation Errors ({{ $errors->count() }})</h4>
      <p class="text-xs text-rose-700 mt-0.5 mb-2">A staff record cannot be created until all required master-data fields are completed and valid.</p>
      <ul class="list-disc list-inside text-xs text-rose-800 space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('hr.employees.store') }}" enctype="multipart/form-data" @submit="validateForm($event)" class="space-y-6">
    @csrf

    {{-- STAFF CATEGORY / ROLE SELECTOR (ERP Requirement) --}}
    <div class="card p-5 border-2 border-indigo-100 bg-gradient-to-r from-indigo-50/40 via-white to-purple-50/40">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            Staff Role Category <span class="text-rose-500 font-bold">*</span>
          </h3>
          <p class="text-xs text-slate-500 mt-0.5">Select the category to allocate appropriate portal permissions and tab placement.</p>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <label @click="category = 'teaching'"
               :class="category === 'teaching' ? 'border-indigo-600 bg-indigo-50/80 ring-2 ring-indigo-200' : 'border-slate-200 bg-white hover:border-indigo-300'"
               class="cursor-pointer p-3 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="teaching" x-model="category" class="sr-only">
          <span class="text-xs font-bold text-slate-800">Teaching Staff</span>
          <span class="text-[10px] text-slate-500 mt-0.5">Teachers &amp; Faculty</span>
        </label>

        <label @click="category = 'non_teaching'"
               :class="category === 'non_teaching' ? 'border-purple-600 bg-purple-50/80 ring-2 ring-purple-200' : 'border-slate-200 bg-white hover:border-purple-300'"
               class="cursor-pointer p-3 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="non_teaching" x-model="category" class="sr-only">
          <span class="text-xs font-bold text-slate-800">Non-Teaching</span>
          <span class="text-[10px] text-slate-500 mt-0.5">Admin &amp; Accounts</span>
        </label>

        <label @click="category = 'driver'"
               :class="category === 'driver' ? 'border-amber-600 bg-amber-50/80 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-amber-300'"
               class="cursor-pointer p-3 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="driver" x-model="category" class="sr-only">
          <span class="text-xs font-bold text-slate-800">Driver</span>
          <span class="text-[10px] text-slate-500 mt-0.5">Transport Crew</span>
        </label>

        <label @click="category = 'cleaner'"
               :class="category === 'cleaner' ? 'border-teal-600 bg-teal-50/80 ring-2 ring-teal-200' : 'border-slate-200 bg-white hover:border-teal-300'"
               class="cursor-pointer p-3 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="cleaner" x-model="category" class="sr-only">
          <span class="text-xs font-bold text-slate-800">Cleaner</span>
          <span class="text-[10px] text-slate-500 mt-0.5">Housekeeping</span>
        </label>

        <label @click="category = 'nanny'"
               :class="category === 'nanny' ? 'border-rose-600 bg-rose-50/80 ring-2 ring-rose-200' : 'border-slate-200 bg-white hover:border-rose-300'"
               class="cursor-pointer p-3 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="nanny" x-model="category" class="sr-only">
          <span class="text-xs font-bold text-slate-800">Nanny (Naani)</span>
          <span class="text-[10px] text-slate-500 mt-0.5">Pre-Primary Care</span>
        </label>
      </div>

      {{-- Category Specific Conditional Blocks --}}
      <div x-show="category === 'driver'" class="mt-4 pt-4 border-t border-amber-200 grid grid-cols-1 sm:grid-cols-3 gap-3" style="display:none">
        <div>
          <label class="label text-xs">Driving License Number</label>
          <input type="text" name="license_number" value="{{ old('license_number') }}" class="input text-xs uppercase" placeholder="e.g. DL-042019881234">
        </div>
        <div>
          <label class="label text-xs">License Expiry Date</label>
          <input type="date" name="license_expiry" value="{{ old('license_expiry') }}" class="input text-xs">
        </div>
        <div>
          <label class="label text-xs">Assigned Vehicle / Route</label>
          <input type="text" name="assigned_vehicle" value="{{ old('assigned_vehicle') }}" class="input text-xs" placeholder="e.g. Bus No. 3">
        </div>
      </div>

      <div x-show="category === 'nanny' || category === 'cleaner'" class="mt-4 pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3" style="display:none">
        <div>
          <label class="label text-xs">Assigned Section / Block</label>
          <input type="text" name="assigned_block" value="{{ old('assigned_block') }}" class="input text-xs" placeholder="e.g. KG Block / Admin Floor">
        </div>
        <div>
          <label class="label text-xs">Shift Timing</label>
          <input type="text" name="shift_timing" value="{{ old('shift_timing') }}" class="input text-xs" placeholder="e.g. 7:30 AM - 3:30 PM">
        </div>
      </div>
    </div>

    {{-- SECTION A — PERSONAL DETAILS --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">A</span>
          SECTION A — PERSONAL DETAILS
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">All fields in this section are mandatory</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
          <label class="label text-xs font-semibold" for="name">
            NAME <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="name" name="name" value="{{ old('name', (old('first_name') ? trim(old('first_name').' '.old('last_name')) : '')) }}" required
                 class="input @error('name') border-rose-500 ring-2 ring-rose-200 @enderror @error('first_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full name of staff member (e.g. Rajesh Sharma)">
          @error('name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          @error('first_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="dob">
            DOB <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="dob" name="dob" value="{{ old('dob') }}" required max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                 class="input @error('dob') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('dob')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="gender">
            M/F <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="gender" name="gender" required class="select @error('gender') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Gender --</option>
            <option value="male" @selected(old('gender')==='male')>Male</option>
            <option value="female" @selected(old('gender')==='female')>Female</option>
            <option value="other" @selected(old('gender')==='other')>Other</option>
          </select>
          @error('gender')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="mother_tongue">
            MOTHER TONGUE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="mother_tongue" name="mother_tongue" value="{{ old('mother_tongue') }}" required
                 class="input @error('mother_tongue') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter mother tongue">
          @error('mother_tongue')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="nationality">
            NATIONALITY <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="nationality" name="nationality" value="{{ old('nationality') }}" required
                 class="input @error('nationality') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter nationality">
          @error('nationality')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="religion">
            RELIGION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="religion" name="religion" value="{{ old('religion') }}" required
                 class="input @error('religion') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter religion">
          @error('religion')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="caste">
            CASTE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="caste" name="caste" value="{{ old('caste') }}" required
                 class="input @error('caste') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter caste">
          @error('caste')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="marital_status">
            MARITAL STATUS <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="marital_status" name="marital_status" required class="select @error('marital_status') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Status --</option>
            <option value="Single" @selected(old('marital_status')==='Single')>Single</option>
            <option value="Married" @selected(old('marital_status')==='Married')>Married</option>
          </select>
          @error('marital_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="number_of_children">
            NUMBER OF CHILDREN <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="number_of_children" name="number_of_children" value="{{ old('number_of_children', '0') }}" required min="0" max="20"
                 class="input @error('number_of_children') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('number_of_children')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="children_male">
            MALE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_male" name="children_male" value="{{ old('children_male', '0') }}" required min="0" max="20"
                 class="input @error('children_male') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_male')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="children_female">
            FEMALE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_female" name="children_female" value="{{ old('children_female', '0') }}" required min="0" max="20"
                 class="input @error('children_female') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_female')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="photo">
            Staff Profile Photo (Optional)
          </label>
          <input type="file" id="photo" name="photo" accept="image/*" class="input text-xs @error('photo') border-rose-500 ring-2 ring-rose-200 @enderror">
          <p class="text-[11px] text-slate-400 mt-1">Accepts JPG, PNG up to 2MB.</p>
          @error('photo')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="address">
            ADDRESS <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="address" name="address" rows="2" required
                    class="input @error('address') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full residential postal address with door no, street, area, city, pin code">{{ old('address') }}</textarea>
          @error('address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION B — EMPLOYMENT DETAILS --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">B</span>
          SECTION B — EMPLOYMENT DETAILS
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Job assignment &amp; appointment details</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="employee_code">
            EMPLOYEE ID <span class="text-slate-400 font-normal">(Auto-generated or custom EPSS format)</span>
          </label>
          <input type="text" id="employee_code" name="employee_code" value="{{ old('employee_code', $generatedEmployeeCode ?? '') }}"
                 pattern="^EPSS\d{4,}$"
                 class="input font-mono font-bold text-indigo-700 bg-indigo-50/30 @error('employee_code') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. EPSS0001">
          @error('employee_code')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="designation">
            DESIGNATION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="designation" name="designation" value="{{ old('designation') }}" required list="designation-suggestions"
                 class="input @error('designation') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Mathematics PGT / Primary Teacher">
          <datalist id="designation-suggestions">
            @if(isset($designations))
              @foreach($designations as $d)
                <option value="{{ $d->name }}"></option>
              @endforeach
            @endif
          </datalist>
          @error('designation')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="department">
            Department <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="department" name="department" value="{{ old('department') }}" list="department-suggestions"
                 class="input @error('department') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Science / Transport / Administration">
          <datalist id="department-suggestions">
            @if(isset($departments))
              @foreach($departments as $dept)
                <option value="{{ $dept->name }}"></option>
              @endforeach
            @endif
          </datalist>
          @error('department')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="joining_date">
            DOJ (Date of Joining) <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="joining_date" name="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}" required
                 class="input @error('joining_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('joining_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="first_appointment_date">
            DATE OF FIRST APPOINTMENT AS TEACHER <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="first_appointment_date" name="first_appointment_date" value="{{ old('first_appointment_date') }}" required
                 class="input @error('first_appointment_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('first_appointment_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="current_employment_status">
            CURRENT EMPLOYMENT STATUS <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="current_employment_status" name="current_employment_status" required
                  class="select @error('current_employment_status') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Status --</option>
            <option value="Permanent" @selected(old('current_employment_status')==='Permanent')>Permanent</option>
            <option value="Temporary" @selected(old('current_employment_status')==='Temporary')>Temporary</option>
          </select>
          @error('current_employment_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="basic_salary">
            Basic Monthly Salary (₹) <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="number" id="basic_salary" name="basic_salary" value="{{ old('basic_salary') }}" min="0" step="0.01"
                 class="input @error('basic_salary') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 35000">
          @error('basic_salary')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION C — IDENTITY / GOVERNMENT DETAILS --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">C</span>
          SECTION C — IDENTITY / GOVERNMENT DETAILS
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Statutory identifiers &amp; tax numbers</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="aadhaar_no">
            AADHAR NO <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no') }}" required maxlength="14"
                 class="input font-mono @error('aadhaar_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="12-digit number (e.g. 123456789012)">
          @error('aadhaar_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="pan_no">
            PAN NO. <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="pan_no" name="pan_no" value="{{ old('pan_no') }}" required maxlength="10"
                 class="input font-mono uppercase @error('pan_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10 characters (e.g. ABCDE1234F)">
          @error('pan_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="epf_no">
            EPF NO. <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="epf_no" name="epf_no" value="{{ old('epf_no') }}" required
                 class="input font-mono @error('epf_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. TN/MAS/1234567/890">
          @error('epf_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION D — CONTACT DETAILS --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">D</span>
          SECTION D — CONTACT DETAILS
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Employee contact &amp; emergency contact person</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="official_email">
            EMAIL ID <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="email" id="official_email" name="official_email" value="{{ old('official_email') }}" required
                 class="input @error('official_email') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="staff@schoolerp.in">
          @error('official_email')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="mobile">
            CONTACT NO. <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" required maxlength="10"
                 class="input font-mono @error('mobile') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10-digit primary mobile">
          @error('mobile')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">Emergency Contact Person Information</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="label text-xs font-semibold" for="name_of_contact_person">
              NAME OF THE CONTACT PERSON <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="name_of_contact_person" name="name_of_contact_person" value="{{ old('name_of_contact_person') }}" required
                   class="input @error('name_of_contact_person') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full name of contact person">
            @error('name_of_contact_person')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="contact_relationship">
              RELATIONSHIP <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="contact_relationship" name="contact_relationship" value="{{ old('contact_relationship') }}" required
                   class="input @error('contact_relationship') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Spouse, Father, Mother, Brother">
            @error('contact_relationship')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="contact_no">
              CONTACT NO <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no') }}" required maxlength="10"
                   class="input font-mono @error('contact_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10-digit mobile number">
            @error('contact_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div class="sm:col-span-3">
            <label class="label text-xs font-semibold" for="contact_address">
              CONTACT ADDRESS <span class="text-rose-500 font-bold">*</span>
            </label>
            <textarea id="contact_address" name="contact_address" rows="2" required
                      class="input @error('contact_address') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Address of the emergency contact person">{{ old('contact_address') }}</textarea>
            @error('contact_address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>
    </div>

    {{-- SECTION E — PREVIOUS EMPLOYMENT DETAILS --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">E</span>
          SECTION E — PREVIOUS EMPLOYMENT DETAILS
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Prior institutional experience</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="previous_designation">
            PREVIOUS POST HELD <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="previous_designation" name="previous_designation" value="{{ old('previous_designation') }}" required
                 class="input @error('previous_designation') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Senior Science Teacher / Assistant Professor">
          @error('previous_designation')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="previous_school">
            NAME &amp; ADDRESS OF THE INSTITUTION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="previous_school" name="previous_school" value="{{ old('previous_school') }}" required
                 class="input @error('previous_school') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. St. Joseph Higher Secondary School, Salem">
          @error('previous_school')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="previous_from_date">
            FROM <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="previous_from_date" name="previous_from_date" value="{{ old('previous_from_date') }}" required
                 class="input @error('previous_from_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('previous_from_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="previous_to_date">
            TO <span class="text-rose-500 font-bold">*</span> <span class="text-slate-400 font-normal">(Cannot be earlier than FROM)</span>
          </label>
          <input type="date" id="previous_to_date" name="previous_to_date" value="{{ old('previous_to_date') }}" required
                 class="input @error('previous_to_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('previous_to_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION F — ACADEMIC QUALIFICATION --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">F</span>
          SECTION F — ACADEMIC QUALIFICATION
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">B.Sc, M.Sc, B.A, M.A, etc.</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="academic_qualification">
            ACADEMIC QUALIFICATION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_qualification" name="academic_qualification" value="{{ old('academic_qualification') }}" required
                 class="input @error('academic_qualification') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. M.Sc. Physics">
          @error('academic_qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_university">
            UNIVERSITY <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_university" name="academic_university" value="{{ old('academic_university') }}" required
                 class="input @error('academic_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. University of Madras">
          @error('academic_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_medium">
            MEDIUM <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_medium" name="academic_medium" value="{{ old('academic_medium', 'English') }}" required
                 class="input @error('academic_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
          @error('academic_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_institution">
            NAME OF THE INSTITUTION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_institution" name="academic_institution" value="{{ old('academic_institution') }}" required
                 class="input @error('academic_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Loyola College, Chennai">
          @error('academic_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_percentage">
            %MARKS <span class="text-rose-500 font-bold">*</span> <span class="text-slate-400 font-normal">(0 - 100)</span>
          </label>
          <input type="number" id="academic_percentage" name="academic_percentage" value="{{ old('academic_percentage') }}" required min="0" max="100" step="0.01"
                 class="input font-mono @error('academic_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 78.50">
          @error('academic_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_year_of_passing">
            YEAR OF PASSING <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="academic_year_of_passing" name="academic_year_of_passing" value="{{ old('academic_year_of_passing') }}" required min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('academic_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 2018">
          @error('academic_year_of_passing')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION G — PROFESSIONAL QUALIFICATION --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">G</span>
          SECTION G — PROFESSIONAL QUALIFICATION
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">B.Ed., M.Ed., D.El.Ed., NTT, etc.</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="professional_qualification">
            PROFESSIONAL QUALIFICATION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_qualification" name="professional_qualification" value="{{ old('professional_qualification') }}" required
                 class="input @error('professional_qualification') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. B.Ed. Physical Science">
          @error('professional_qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_university">
            UNIVERSITY <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_university" name="professional_university" value="{{ old('professional_university') }}" required
                 class="input @error('professional_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. TNTEU">
          @error('professional_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_medium">
            MEDIUM <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_medium" name="professional_medium" value="{{ old('professional_medium', 'English') }}" required
                 class="input @error('professional_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
          @error('professional_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_institution">
            NAME OF INSTITUTION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_institution" name="professional_institution" value="{{ old('professional_institution') }}" required
                 class="input @error('professional_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Institute of Teacher Education">
          @error('professional_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_percentage">
            %MARKS <span class="text-rose-500 font-bold">*</span> <span class="text-slate-400 font-normal">(0 - 100)</span>
          </label>
          <input type="number" id="professional_percentage" name="professional_percentage" value="{{ old('professional_percentage') }}" required min="0" max="100" step="0.01"
                 class="input font-mono @error('professional_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 82.00">
          @error('professional_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_year_of_passing">
            YEAR OF PASSING <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="professional_year_of_passing" name="professional_year_of_passing" value="{{ old('professional_year_of_passing') }}" required min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('professional_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 2020">
          @error('professional_year_of_passing')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION H — OTHER QUALIFICATION 1 --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">H</span>
          SECTION H — OTHER QUALIFICATION 1
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Distinct additional qualification (e.g. PGDCA, Diplomas)</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_name">
            ANY OTHER QUALIFICATION
          </label>
          <input type="text" id="other_qual_1_name" name="other_qual_1_name" value="{{ old('other_qual_1_name') }}"
                 class="input @error('other_qual_1_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. PGDCA / Diploma in School Mgmt">
          @error('other_qual_1_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_university">
            UNIVERSITY
          </label>
          <input type="text" id="other_qual_1_university" name="other_qual_1_university" value="{{ old('other_qual_1_university') }}"
                 class="input @error('other_qual_1_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Board / University">
          @error('other_qual_1_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_medium">
            MEDIUM
          </label>
          <input type="text" id="other_qual_1_medium" name="other_qual_1_medium" value="{{ old('other_qual_1_medium') }}"
                 class="input @error('other_qual_1_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
          @error('other_qual_1_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_institution">
            NAME OF INSTITUTION
          </label>
          <input type="text" id="other_qual_1_institution" name="other_qual_1_institution" value="{{ old('other_qual_1_institution') }}"
                 class="input @error('other_qual_1_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Institution name">
          @error('other_qual_1_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_percentage">
            %MARKS
          </label>
          <input type="number" id="other_qual_1_percentage" name="other_qual_1_percentage" value="{{ old('other_qual_1_percentage') }}" min="0" max="100" step="0.01"
                 class="input font-mono @error('other_qual_1_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 75.00">
          @error('other_qual_1_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_year_of_passing">
            YEAR OF PASSING
          </label>
          <input type="number" id="other_qual_1_year_of_passing" name="other_qual_1_year_of_passing" value="{{ old('other_qual_1_year_of_passing') }}" min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('other_qual_1_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 2019">
          @error('other_qual_1_year_of_passing')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION I — OTHER QUALIFICATION 2 --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">I</span>
          SECTION I — OTHER QUALIFICATION 2
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Distinct fourth qualification (e.g. CTET, TET, NET, Specialization)</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_name">
            ANY OTHER QUALIFICATION
          </label>
          <input type="text" id="other_qual_2_name" name="other_qual_2_name" value="{{ old('other_qual_2_name') }}"
                 class="input @error('other_qual_2_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. CTET Paper I &amp; II">
          @error('other_qual_2_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_university">
            UNIVERSITY
          </label>
          <input type="text" id="other_qual_2_university" name="other_qual_2_university" value="{{ old('other_qual_2_university') }}"
                 class="input @error('other_qual_2_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Board / Examination Agency">
          @error('other_qual_2_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_medium">
            MEDIUM
          </label>
          <input type="text" id="other_qual_2_medium" name="other_qual_2_medium" value="{{ old('other_qual_2_medium') }}"
                 class="input @error('other_qual_2_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
          @error('other_qual_2_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_institution">
            NAME OF INSTITUTION
          </label>
          <input type="text" id="other_qual_2_institution" name="other_qual_2_institution" value="{{ old('other_qual_2_institution') }}"
                 class="input @error('other_qual_2_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Institution / Exam Center">
          @error('other_qual_2_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_percentage">
            %MARKS
          </label>
          <input type="number" id="other_qual_2_percentage" name="other_qual_2_percentage" value="{{ old('other_qual_2_percentage') }}" min="0" max="100" step="0.01"
                 class="input font-mono @error('other_qual_2_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 70.00">
          @error('other_qual_2_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_year_of_passing">
            YEAR OF PASSING
          </label>
          <input type="number" id="other_qual_2_year_of_passing" name="other_qual_2_year_of_passing" value="{{ old('other_qual_2_year_of_passing') }}" min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('other_qual_2_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 2021">
          @error('other_qual_2_year_of_passing')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION J — ADDITIONAL QUALIFICATION / TRAINING --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-slate-800 text-white text-xs font-bold flex items-center justify-center">J</span>
          SECTION J — ADDITIONAL QUALIFICATION / TRAINING
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Post-recruitment credentials and pre-recruitment workshops</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="additional_qual_after_recruitment">
            ADDITIONAL QUALIFICATION OBTAINED AFTER RECRUITMENT <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="additional_qual_after_recruitment" name="additional_qual_after_recruitment" rows="3" required
                    class="input @error('additional_qual_after_recruitment') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="Enter any degree, diploma, certificate, or specialization obtained after recruitment">{{ old('additional_qual_after_recruitment') }}</textarea>
          @error('additional_qual_after_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="training_attended_before_recruitment">
            TRAINING PROGRAMME ATTENDED BEFORE RECRUITMENT <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="training_attended_before_recruitment" name="training_attended_before_recruitment" rows="3" required
                    class="input @error('training_attended_before_recruitment') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="List CBSE capacity-building programmes, pedagogy workshops, or subject orientation trainings attended">{{ old('training_attended_before_recruitment') }}</textarea>
          @error('training_attended_before_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SUBMIT ACTIONS --}}
    <div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
      <div class="text-xs text-slate-500 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>All master data fields are tracked. Data will be saved as <strong>Pending Approval</strong>.</span>
      </div>
      <div class="flex items-center gap-3">
        <a href="{{ route('hr.employees') }}" class="btn btn-secondary px-5">Cancel</a>
        <button type="submit" class="btn btn-primary px-7 shadow-lg shadow-indigo-100 flex items-center gap-2 font-bold">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Save &amp; Add Staff Member
        </button>
      </div>
    </div>

  </form>
</div>
@endsection
