@extends('layouts.app')

@section('title', 'Edit Staff Member — ' . $employee->full_name)

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
  category: '{{ old('employee_type', $employee->employee_type ?? 'teaching') }}',
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
    const fromDate = document.getElementById('previous_from_date')?.value;
    const toDate = document.getElementById('previous_to_date')?.value;
    if (fromDate && toDate && toDate < fromDate) {
      e.preventDefault();
      alert('Previous Employment TO date cannot be earlier than FROM date.');
      document.getElementById('previous_to_date')?.focus();
      return false;
    }
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
      <a href="{{ route('hr.employees.show', $employee->id) }}" class="btn-icon" title="Back to Employee Profile">
        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </a>
      <div>
        <div class="flex items-center gap-2">
          <h1 class="page-title text-xl font-bold text-slate-900">Edit Staff Member — {{ $employee->full_name }}</h1>
          <span class="px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
            Emp Code: {{ $employee->employee_code }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Update staff master record fields in accordance with the Excel Master Data specification.</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <span class="text-xs px-2.5 py-1 rounded-md font-bold {{ $employee->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
        {{ $employee->is_active ? 'Active' : 'Inactive' }}
      </span>
      <span class="text-xs px-2.5 py-1 rounded-md font-bold uppercase bg-slate-100 text-slate-700">
        {{ $employee->approval_status ?? 'pending' }}
      </span>
    </div>
  </div>

  {{-- Legacy Data Missing Notice --}}
  @php
    $missingLegacyFields = [];
    if (!$employee->mother_tongue) $missingLegacyFields[] = 'Mother Tongue';
    if (!$employee->caste) $missingLegacyFields[] = 'Caste';
    if (!$employee->marital_status) $missingLegacyFields[] = 'Marital Status';
    if (!$employee->first_appointment_date) $missingLegacyFields[] = 'Date of First Appointment as Teacher';
    if (!$employee->academic_qualification) $missingLegacyFields[] = 'Academic Qualification';
    if (!$employee->professional_qualification) $missingLegacyFields[] = 'Professional Qualification';
  @endphp
  @if(count($missingLegacyFields) > 0)
  <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3 shadow-sm">
    <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
      <h4 class="font-bold text-amber-900">Legacy Record Notice</h4>
      <p class="mt-0.5">This staff member was created prior to the new Master Data fields. The following required fields need to be completed when saving updates: <strong>{{ implode(', ', $missingLegacyFields) }}</strong>.</p>
    </div>
  </div>
  @endif

  {{-- Error Alert --}}
  @if (isset($errors) && $errors->any())
  <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
    <svg class="w-5 h-5 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div class="flex-1">
      <h4 class="font-bold text-rose-900">Please correct the validation errors below ({{ $errors->count() }})</h4>
      <ul class="list-disc list-inside text-xs text-rose-800 space-y-1 mt-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('hr.employees.update', $employee->id) }}" enctype="multipart/form-data" @submit="validateForm($event)" class="space-y-6">
    @csrf
    @method('PUT')

    {{-- STAFF CATEGORY / ROLE SELECTOR --}}
    <div class="card p-5 border-2 border-indigo-100 bg-gradient-to-r from-indigo-50/40 via-white to-purple-50/40">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">0</span>
            Staff Role Category <span class="text-rose-500 font-bold">*</span>
          </h3>
          <p class="text-xs text-slate-500 mt-0.5">Selected category determines tab placement and access.</p>
        </div>
        <div class="flex items-center gap-2">
          <label for="is_active" class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-slate-200">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $employee->is_active)) class="w-4 h-4 text-indigo-600 rounded">
            <span>Active Employee</span>
          </label>
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
          <input type="text" name="license_number" value="{{ old('license_number', $employee->license_number) }}" class="input text-xs uppercase" placeholder="e.g. DL-042019881234">
        </div>
        <div>
          <label class="label text-xs">License Expiry Date</label>
          <input type="date" name="license_expiry" value="{{ old('license_expiry', $employee->license_expiry?->format('Y-m-d')) }}" class="input text-xs">
        </div>
        <div>
          <label class="label text-xs">Assigned Vehicle / Route</label>
          <input type="text" name="assigned_vehicle" value="{{ old('assigned_vehicle', $employee->assigned_vehicle) }}" class="input text-xs" placeholder="e.g. Bus No. 3">
        </div>
      </div>

      <div x-show="category === 'nanny' || category === 'cleaner'" class="mt-4 pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3" style="display:none">
        <div>
          <label class="label text-xs">Assigned Section / Block</label>
          <input type="text" name="assigned_block" value="{{ old('assigned_block', $employee->assigned_block) }}" class="input text-xs" placeholder="e.g. KG Block / Admin Floor">
        </div>
        <div>
          <label class="label text-xs">Shift Timing</label>
          <input type="text" name="shift_timing" value="{{ old('shift_timing', $employee->shift_timing) }}" class="input text-xs" placeholder="e.g. 7:30 AM - 3:30 PM">
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
          <input type="text" id="name" name="name" value="{{ old('name', $employee->full_name) }}" required
                 class="input @error('name') border-rose-500 ring-2 ring-rose-200 @enderror @error('first_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full name of staff member">
          @error('name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          @error('first_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="dob">
            DOB <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="dob" name="dob" value="{{ old('dob', $employee->dob?->format('Y-m-d')) }}" required max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                 class="input @error('dob') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('dob')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="gender">
            M/F <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="gender" name="gender" required class="select @error('gender') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Gender --</option>
            <option value="male" @selected(old('gender', strtolower($employee->gender ?? ''))==='male')>Male</option>
            <option value="female" @selected(old('gender', strtolower($employee->gender ?? ''))==='female')>Female</option>
            <option value="other" @selected(old('gender', strtolower($employee->gender ?? ''))==='other')>Other</option>
          </select>
          @error('gender')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="mother_tongue">
            MOTHER TONGUE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="mother_tongue" name="mother_tongue" value="{{ old('mother_tongue', $employee->mother_tongue) }}" required
                 class="input @error('mother_tongue') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter mother tongue">
          @error('mother_tongue')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="nationality">
            NATIONALITY <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="nationality" name="nationality" value="{{ old('nationality', $employee->nationality) }}" required
                 class="input @error('nationality') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter nationality">
          @error('nationality')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="religion">
            RELIGION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="religion" name="religion" value="{{ old('religion', $employee->religion) }}" required
                 class="input @error('religion') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter religion">
          @error('religion')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="caste">
            CASTE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="caste" name="caste" value="{{ old('caste', $employee->caste ?? $employee->category) }}" required
                 class="input @error('caste') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter caste">
          @error('caste')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="marital_status">
            MARITAL STATUS <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="marital_status" name="marital_status" required class="select @error('marital_status') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Status --</option>
            <option value="Single" @selected(old('marital_status', $employee->marital_status)==='Single')>Single</option>
            <option value="Married" @selected(old('marital_status', $employee->marital_status)==='Married')>Married</option>
          </select>
          @error('marital_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="number_of_children">
            NUMBER OF CHILDREN <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="number_of_children" name="number_of_children" value="{{ old('number_of_children', $employee->number_of_children ?? 0) }}" required min="0" max="20"
                 class="input @error('number_of_children') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('number_of_children')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="children_male">
            MALE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_male" name="children_male" value="{{ old('children_male', $employee->children_male ?? 0) }}" required min="0" max="20"
                 class="input @error('children_male') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_male')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="children_female">
            FEMALE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_female" name="children_female" value="{{ old('children_female', $employee->children_female ?? 0) }}" required min="0" max="20"
                 class="input @error('children_female') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_female')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="photo">
            Staff Profile Photo
          </label>
          <div class="flex items-center gap-4">
            @if($employee->photo)
              <img src="{{ asset('storage/'.$employee->photo) }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200" alt="Current Photo">
            @endif
            <div class="flex-1">
              <input type="file" id="photo" name="photo" accept="image/*" class="input text-xs @error('photo') border-rose-500 ring-2 ring-rose-200 @enderror">
              <p class="text-[11px] text-slate-400 mt-0.5">JPG, PNG up to 2MB. Leaves existing photo unchanged if not replaced.</p>
            </div>
          </div>
          @error('photo')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="address">
            ADDRESS <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="address" name="address" rows="2" required
                    class="input @error('address') border-rose-500 ring-2 ring-rose-200 @enderror">{{ old('address', $employee->address ?? $employee->residential_address) }}</textarea>
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
            EMPLOYEE ID <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="employee_code" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required
                 class="input font-mono font-bold text-indigo-700 bg-indigo-50/30 @error('employee_code') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('employee_code')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="designation">
            DESIGNATION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="designation" name="designation" value="{{ old('designation', $employee->designation) }}" required list="designation-suggestions"
                 class="input @error('designation') border-rose-500 ring-2 ring-rose-200 @enderror">
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
          <input type="text" id="department" name="department" value="{{ old('department', $employee->department) }}" list="department-suggestions"
                 class="input @error('department') border-rose-500 ring-2 ring-rose-200 @enderror">
          <datalist id="department-suggestions">
            @if(isset($departments))
              @foreach($departments as $dept)
                <option value="{{ $dept->name }}"></option>
              @endforeach
            @endif
          </datalist>
          @error('department')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-data="{
          doj: '{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}',
          get calculatedExp() {
            if (!this.doj) return '';
            const start = new Date(this.doj);
            const now = new Date();
            if (isNaN(start.getTime()) || start > now) return '0 Years 0 Months';
            let years = now.getFullYear() - start.getFullYear();
            let months = now.getMonth() - start.getMonth();
            if (now.getDate() < start.getDate()) months--;
            if (months < 0) { years--; months += 12; }
            if (years < 0) { years = 0; months = 0; }
            const yText = years === 1 ? 'Year' : 'Years';
            const mText = months === 1 ? 'Month' : 'Months';
            return `${years} ${yText} ${months} ${mText}`;
          }
        }">
          <label class="label text-xs font-semibold" for="joining_date">
            DOJ (Date of Joining) <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="joining_date" name="joining_date" x-model="doj" required
                 class="input @error('joining_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          <p class="text-[11px] font-semibold text-indigo-600 mt-1 flex items-center gap-1" x-show="calculatedExp">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Experience: <strong x-text="calculatedExp"></strong> (auto-calculated from DOJ)</span>
          </p>
          @error('joining_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="first_appointment_date">
            DATE OF FIRST APPOINTMENT AS TEACHER <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="first_appointment_date" name="first_appointment_date" value="{{ old('first_appointment_date', $employee->first_appointment_date?->format('Y-m-d')) }}" required
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
            <option value="Permanent" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Permanent')>Permanent</option>
            <option value="Temporary" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Temporary')>Temporary</option>
          </select>
          @error('current_employment_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="basic_salary">
            Basic Monthly Salary (₹) <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="number" id="basic_salary" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" min="0" step="0.01"
                 class="input @error('basic_salary') border-rose-500 ring-2 ring-rose-200 @enderror">
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
          <input type="text" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no', $employee->aadhaar_no) }}" required maxlength="14"
                 class="input font-mono @error('aadhaar_no') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('aadhaar_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="pan_no">
            PAN NO. <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="pan_no" name="pan_no" value="{{ old('pan_no', $employee->pan_no) }}" required maxlength="10"
                 class="input font-mono uppercase @error('pan_no') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('pan_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="epf_no">
            EPF NO. <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="epf_no" name="epf_no" value="{{ old('epf_no', $employee->epf_no ?? $employee->pf_account_no) }}" required
                 class="input font-mono @error('epf_no') border-rose-500 ring-2 ring-rose-200 @enderror">
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
          <input type="email" id="official_email" name="official_email" value="{{ old('official_email', $employee->official_email ?? $employee->email) }}" required
                 class="input @error('official_email') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('official_email')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="mobile">
            CONTACT NO. <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="tel" id="mobile" name="mobile" value="{{ old('mobile', $employee->mobile) }}" required maxlength="10"
                 class="input font-mono @error('mobile') border-rose-500 ring-2 ring-rose-200 @enderror">
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
            <input type="text" id="name_of_contact_person" name="name_of_contact_person" value="{{ old('name_of_contact_person', $employee->name_of_contact_person ?? $employee->emergency_contact_name) }}" required
                   class="input @error('name_of_contact_person') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('name_of_contact_person')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="contact_relationship">
              RELATIONSHIP <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="contact_relationship" name="contact_relationship" value="{{ old('contact_relationship', $employee->contact_relationship ?? 'Spouse') }}" required
                   class="input @error('contact_relationship') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('contact_relationship')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="contact_no">
              CONTACT NO <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no', $employee->contact_no ?? $employee->emergency_contact_mobile) }}" required maxlength="10"
                   class="input font-mono @error('contact_no') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('contact_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div class="sm:col-span-3">
            <label class="label text-xs font-semibold" for="contact_address">
              CONTACT ADDRESS <span class="text-rose-500 font-bold">*</span>
            </label>
            <textarea id="contact_address" name="contact_address" rows="2" required
                      class="input @error('contact_address') border-rose-500 ring-2 ring-rose-200 @enderror">{{ old('contact_address', $employee->contact_address ?? $employee->address ?? $employee->residential_address) }}</textarea>
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
          <input type="text" id="previous_designation" name="previous_designation" value="{{ old('previous_designation', $employee->previous_designation) }}" required
                 class="input @error('previous_designation') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('previous_designation')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="previous_school">
            NAME &amp; ADDRESS OF THE INSTITUTION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="previous_school" name="previous_school" value="{{ old('previous_school', $employee->previous_school) }}" required
                 class="input @error('previous_school') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('previous_school')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="previous_from_date">
            FROM <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="previous_from_date" name="previous_from_date" value="{{ old('previous_from_date', $employee->previous_from_date?->format('Y-m-d')) }}" required
                 class="input @error('previous_from_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('previous_from_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="previous_to_date">
            TO <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="previous_to_date" name="previous_to_date" value="{{ old('previous_to_date', $employee->previous_to_date?->format('Y-m-d')) }}" required
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
          <input type="text" id="academic_qualification" name="academic_qualification" value="{{ old('academic_qualification', $employee->academic_qualification ?? $employee->qualification) }}" required
                 class="input @error('academic_qualification') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('academic_qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_university">
            UNIVERSITY <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_university" name="academic_university" value="{{ old('academic_university', $employee->academic_university) }}" required
                 class="input @error('academic_university') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('academic_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_medium">
            MEDIUM <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_medium" name="academic_medium" value="{{ old('academic_medium', $employee->academic_medium ?? 'English') }}" required
                 class="input @error('academic_medium') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('academic_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_institution">
            NAME OF THE INSTITUTION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="academic_institution" name="academic_institution" value="{{ old('academic_institution', $employee->academic_institution) }}" required
                 class="input @error('academic_institution') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('academic_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_percentage">
            %MARKS <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="academic_percentage" name="academic_percentage" value="{{ old('academic_percentage', $employee->academic_percentage) }}" required min="0" max="100" step="0.01"
                 class="input font-mono @error('academic_percentage') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('academic_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="academic_year_of_passing">
            YEAR OF PASSING <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="academic_year_of_passing" name="academic_year_of_passing" value="{{ old('academic_year_of_passing', $employee->academic_year_of_passing) }}" required min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('academic_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror">
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
          <input type="text" id="professional_qualification" name="professional_qualification" value="{{ old('professional_qualification', $employee->professional_qualification) }}" required
                 class="input @error('professional_qualification') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('professional_qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_university">
            UNIVERSITY <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_university" name="professional_university" value="{{ old('professional_university', $employee->professional_university) }}" required
                 class="input @error('professional_university') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('professional_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_medium">
            MEDIUM <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_medium" name="professional_medium" value="{{ old('professional_medium', $employee->professional_medium ?? 'English') }}" required
                 class="input @error('professional_medium') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('professional_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_institution">
            NAME OF INSTITUTION <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="professional_institution" name="professional_institution" value="{{ old('professional_institution', $employee->professional_institution) }}" required
                 class="input @error('professional_institution') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('professional_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_percentage">
            %MARKS <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="professional_percentage" name="professional_percentage" value="{{ old('professional_percentage', $employee->professional_percentage) }}" required min="0" max="100" step="0.01"
                 class="input font-mono @error('professional_percentage') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('professional_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_year_of_passing">
            YEAR OF PASSING <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="professional_year_of_passing" name="professional_year_of_passing" value="{{ old('professional_year_of_passing', $employee->professional_year_of_passing) }}" required min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('professional_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror">
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
          <input type="text" id="other_qual_1_name" name="other_qual_1_name" value="{{ old('other_qual_1_name', $employee->other_qual_1_name) }}"
                 class="input @error('other_qual_1_name') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_1_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_university">
            UNIVERSITY
          </label>
          <input type="text" id="other_qual_1_university" name="other_qual_1_university" value="{{ old('other_qual_1_university', $employee->other_qual_1_university) }}"
                 class="input @error('other_qual_1_university') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_1_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_medium">
            MEDIUM
          </label>
          <input type="text" id="other_qual_1_medium" name="other_qual_1_medium" value="{{ old('other_qual_1_medium', $employee->other_qual_1_medium) }}"
                 class="input @error('other_qual_1_medium') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_1_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_institution">
            NAME OF INSTITUTION
          </label>
          <input type="text" id="other_qual_1_institution" name="other_qual_1_institution" value="{{ old('other_qual_1_institution', $employee->other_qual_1_institution) }}"
                 class="input @error('other_qual_1_institution') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_1_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_percentage">
            %MARKS
          </label>
          <input type="number" id="other_qual_1_percentage" name="other_qual_1_percentage" value="{{ old('other_qual_1_percentage', $employee->other_qual_1_percentage) }}" min="0" max="100" step="0.01"
                 class="input font-mono @error('other_qual_1_percentage') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_1_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_1_year_of_passing">
            YEAR OF PASSING
          </label>
          <input type="number" id="other_qual_1_year_of_passing" name="other_qual_1_year_of_passing" value="{{ old('other_qual_1_year_of_passing', $employee->other_qual_1_year_of_passing) }}" min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('other_qual_1_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror">
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
          <input type="text" id="other_qual_2_name" name="other_qual_2_name" value="{{ old('other_qual_2_name', $employee->other_qual_2_name) }}"
                 class="input @error('other_qual_2_name') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_2_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_university">
            UNIVERSITY
          </label>
          <input type="text" id="other_qual_2_university" name="other_qual_2_university" value="{{ old('other_qual_2_university', $employee->other_qual_2_university) }}"
                 class="input @error('other_qual_2_university') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_2_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_medium">
            MEDIUM
          </label>
          <input type="text" id="other_qual_2_medium" name="other_qual_2_medium" value="{{ old('other_qual_2_medium', $employee->other_qual_2_medium) }}"
                 class="input @error('other_qual_2_medium') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_2_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_institution">
            NAME OF INSTITUTION
          </label>
          <input type="text" id="other_qual_2_institution" name="other_qual_2_institution" value="{{ old('other_qual_2_institution', $employee->other_qual_2_institution) }}"
                 class="input @error('other_qual_2_institution') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_2_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_percentage">
            %MARKS
          </label>
          <input type="number" id="other_qual_2_percentage" name="other_qual_2_percentage" value="{{ old('other_qual_2_percentage', $employee->other_qual_2_percentage) }}" min="0" max="100" step="0.01"
                 class="input font-mono @error('other_qual_2_percentage') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('other_qual_2_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="other_qual_2_year_of_passing">
            YEAR OF PASSING
          </label>
          <input type="number" id="other_qual_2_year_of_passing" name="other_qual_2_year_of_passing" value="{{ old('other_qual_2_year_of_passing', $employee->other_qual_2_year_of_passing) }}" min="1960" max="{{ date('Y') + 1 }}"
                 class="input font-mono @error('other_qual_2_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror">
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
                    class="input @error('additional_qual_after_recruitment') border-rose-500 ring-2 ring-rose-200 @enderror">{{ old('additional_qual_after_recruitment', $employee->additional_qual_after_recruitment) }}</textarea>
          @error('additional_qual_after_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="training_attended_before_recruitment">
            TRAINING PROGRAMME ATTENDED BEFORE RECRUITMENT <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="training_attended_before_recruitment" name="training_attended_before_recruitment" rows="3" required
                    class="input @error('training_attended_before_recruitment') border-rose-500 ring-2 ring-rose-200 @enderror">{{ old('training_attended_before_recruitment', $employee->training_attended_before_recruitment) }}</textarea>
          @error('training_attended_before_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SUBMIT ACTIONS --}}
    <div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
      <div class="text-xs text-slate-500 flex items-center gap-2">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>All master data fields are tracked and synced.</span>
      </div>
      <div class="flex items-center gap-3">
        <a href="{{ route('hr.employees.show', $employee->id) }}" class="btn btn-secondary px-5">Cancel</a>
        <button type="submit" class="btn btn-primary px-7 shadow-lg shadow-indigo-100 flex items-center gap-2 font-bold">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Save Changes
        </button>
      </div>
    </div>

  </form>
</div>
@endsection
