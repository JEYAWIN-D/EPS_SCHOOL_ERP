@extends('layouts.app')

@section('title', 'Edit Staff Member — ' . $employee->full_name)

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
  category: '{{ old('employee_type', $employee->employee_type ?? 'teaching') }}',
  maritalStatus: '{{ old('marital_status', $employee->marital_status ?? 'Single') }}',
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
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-700">Status:</label>
            <select name="status" class="select text-xs font-bold py-1 px-2.5 h-8 bg-white border border-slate-300 rounded-lg">
              <option value="active" @selected(old('status', $employee->status ?? 'active') === 'active')>🟢 Active (Eligible for Timetables)</option>
              <option value="on_leave" @selected(old('status', $employee->status) === 'on_leave')>🟡 On Leave</option>
              <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>🔴 Inactive (Requires No Active Timetable)</option>
              <option value="relieved" @selected(old('status', $employee->status) === 'relieved')>⚪ Relieved (Requires No Active Timetable)</option>
              <option value="terminated" @selected(old('status', $employee->status) === 'terminated')>⚫ Terminated</option>
            </select>
          </div>
          <label for="is_active" class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-slate-200">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $employee->is_active)) class="w-4 h-4 text-indigo-600 rounded">
            <span>Active Record</span>
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

        {{-- RELIGION DROPDOWN --}}
        <div x-data="{
          selectedRel: '{{ in_array(old('religion', $employee->religion ?? 'Hindu'), ['Hindu', 'Christian', 'Muslim', 'Sikh', 'Jain', 'Buddhist', 'Parsi', 'Jewish']) ? old('religion', $employee->religion ?? 'Hindu') : (old('religion', $employee->religion) ? 'Other' : 'Hindu') }}',
          customRel: '{{ !in_array(old('religion', $employee->religion ?? 'Hindu'), ['Hindu', 'Christian', 'Muslim', 'Sikh', 'Jain', 'Buddhist', 'Parsi', 'Jewish', '']) ? old('religion', $employee->religion ?? '') : '' }}',
          get effectiveRel() { return this.selectedRel === 'Other' ? this.customRel : this.selectedRel; }
        }">
          <label class="label text-xs font-semibold" for="religion_select">
            RELIGION <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="religion_select" x-model="selectedRel"
                  class="select @error('religion') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Religion --</option>
            <option value="Hindu">Hindu</option>
            <option value="Christian">Christian</option>
            <option value="Muslim">Muslim</option>
            <option value="Sikh">Sikh</option>
            <option value="Jain">Jain</option>
            <option value="Buddhist">Buddhist</option>
            <option value="Parsi">Parsi</option>
            <option value="Jewish">Jewish</option>
            <option value="Other">Other (Custom Religion)</option>
          </select>
          <div x-show="selectedRel === 'Other'" class="mt-1.5" style="display:none">
            <input type="text" x-model="customRel" placeholder="Type custom religion..." class="input text-xs">
          </div>
          <input type="hidden" name="religion" :value="effectiveRel">
          @error('religion')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- CASTE DROPDOWN --}}
        <div x-data="{
          selectedCaste: '{{ in_array(old('caste', $employee->caste ?? $employee->category ?? 'General'), ['General', 'OBC', 'BC', 'BCM', 'MBC', 'DNC', 'SC', 'SCA', 'ST', 'EWS']) ? old('caste', $employee->caste ?? $employee->category ?? 'General') : (old('caste', $employee->caste ?? $employee->category) ? 'Other' : 'General') }}',
          customCaste: '{{ !in_array(old('caste', $employee->caste ?? $employee->category ?? 'General'), ['General', 'OBC', 'BC', 'BCM', 'MBC', 'DNC', 'SC', 'SCA', 'ST', 'EWS', '']) ? old('caste', $employee->caste ?? $employee->category ?? '') : '' }}',
          get effectiveCaste() { return this.selectedCaste === 'Other' ? this.customCaste : this.selectedCaste; }
        }">
          <label class="label text-xs font-semibold" for="caste_select">
            CASTE <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="caste_select" x-model="selectedCaste"
                  class="select @error('caste') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Caste / Category --</option>
            <option value="General">General (OC / Open)</option>
            <option value="OBC">OBC (Other Backward Class)</option>
            <option value="BC">BC (Backward Class)</option>
            <option value="BCM">BCM (Backward Class Muslim)</option>
            <option value="MBC">MBC (Most Backward Community)</option>
            <option value="DNC">DNC (De-notified Community)</option>
            <option value="SC">SC (Scheduled Caste)</option>
            <option value="SCA">SCA (Scheduled Caste Arunthathiyar)</option>
            <option value="ST">ST (Scheduled Tribe)</option>
            <option value="EWS">EWS (Economically Weaker Section)</option>
            <option value="Other">Other (Custom Caste)</option>
          </select>
          <div x-show="selectedCaste === 'Other'" class="mt-1.5" style="display:none">
            <input type="text" x-model="customCaste" placeholder="Type custom caste / category..." class="input text-xs">
          </div>
          <input type="hidden" name="caste" :value="effectiveCaste">
          @error('caste')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="marital_status">
            MARITAL STATUS <span class="text-rose-500 font-bold">*</span>
          </label>
          <select id="marital_status" name="marital_status" x-model="maritalStatus" required class="select @error('marital_status') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Status --</option>
            <option value="Single" @selected(old('marital_status', $employee->marital_status)==='Single')>Single</option>
            <option value="Married" @selected(old('marital_status', $employee->marital_status)==='Married')>Married</option>
          </select>
          <p class="text-[11px] text-slate-400 mt-1" x-show="maritalStatus !== 'Married'">Children count is not required for single staff.</p>
          @error('marital_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Children count fields: ONLY needed & visible for Married staff --}}
        <div x-show="maritalStatus === 'Married'" @if(old('marital_status', $employee->marital_status ?? 'Single') !== 'Married') style="display:none" @endif>
          <label class="label text-xs font-semibold" for="number_of_children">
            NUMBER OF CHILDREN <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="number_of_children" name="number_of_children"
                 value="{{ old('number_of_children', $employee->number_of_children ?? 0) }}"
                 :required="maritalStatus === 'Married'"
                 :disabled="maritalStatus !== 'Married'"
                 min="0" max="20"
                 class="input @error('number_of_children') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('number_of_children')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-show="maritalStatus === 'Married'" @if(old('marital_status', $employee->marital_status ?? 'Single') !== 'Married') style="display:none" @endif>
          <label class="label text-xs font-semibold" for="children_male">
            MALE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_male" name="children_male"
                 value="{{ old('children_male', $employee->children_male ?? 0) }}"
                 :required="maritalStatus === 'Married'"
                 :disabled="maritalStatus !== 'Married'"
                 min="0" max="20"
                 class="input @error('children_male') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_male')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-show="maritalStatus === 'Married'" @if(old('marital_status', $employee->marital_status ?? 'Single') !== 'Married') style="display:none" @endif>
          <label class="label text-xs font-semibold" for="children_female">
            FEMALE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_female" name="children_female"
                 value="{{ old('children_female', $employee->children_female ?? 0) }}"
                 :required="maritalStatus === 'Married'"
                 :disabled="maritalStatus !== 'Married'"
                 min="0" max="20"
                 class="input @error('children_female') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_female')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="photo">
            Staff Profile Photo
          </label>
          <div class="flex items-center gap-4">
            @if($employee->photo)
              <img src="{{ asset('storage/'.$employee->photo) }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200" alt="Current Photo" onerror="this.style.display='none';">
            @endif
            <div class="flex-1">
              <input type="file" id="photo" name="photo" accept="image/*" class="input text-xs @error('photo') border-rose-500 ring-2 ring-rose-200 @enderror">
              <p class="text-[11px] text-slate-400 mt-0.5">JPG, PNG up to 2MB. Leaves existing photo unchanged if not replaced.</p>
            </div>
          </div>
          @error('photo')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
          <label class="label text-xs font-semibold" for="address">
            CURRENT RESIDENTIAL ADDRESS <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="address" name="address" rows="2" required
                    class="w-full rounded-xl border border-slate-300 p-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 min-h-[72px] resize-y leading-relaxed @error('address') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="Door No, Building, Street Name, Area / Locality, City / Town">{{ old('address', $employee->address ?? $employee->residential_address) }}</textarea>
          @error('address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="pincode">
            POSTAL PIN CODE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="pincode" name="pincode" value="{{ old('pincode', $employee->pincode) }}" maxlength="6"
                 pattern="[1-9][0-9]{5}" required
                 @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 6)"
                 class="input font-mono font-bold text-slate-800 tracking-wider @error('pincode') border-rose-500 ring-2 ring-rose-200 @enderror"
                 placeholder="e.g. 638001">
          <span class="text-[10px] text-slate-400 mt-1 block">6-digit postal code</span>
          @error('pincode')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="permanent_address">
            PERMANENT ADDRESS <span class="text-slate-400 font-normal">(Optional, if different from current address)</span>
          </label>
          <textarea id="permanent_address" name="permanent_address" rows="2"
                    class="w-full rounded-xl border border-slate-300 p-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 min-h-[64px] resize-y leading-relaxed @error('permanent_address') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="Permanent address / native place (optional)">{{ old('permanent_address', $employee->permanent_address ?? $employee->address) }}</textarea>
          @error('permanent_address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
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

    {{-- SECTION B — EMPLOYMENT DETAILS --}}
    @php
      $standardDesignations = [
        'Leadership & Academic Administration' => [
          'Principal', 'Vice Principal', 'Headmistress / Headmaster', 'Academic Coordinator',
          'Examination Controller', 'Administrative Officer (AO)', 'HR Manager', 'Accountant',
          'Front Office / Receptionist', 'Office Assistant / Clerk'
        ],
        'Senior Secondary Faculty (PGT - Grades XI & XII)' => [
          'PGT Mathematics', 'PGT Physics', 'PGT Chemistry', 'PGT Biology',
          'PGT Computer Science', 'PGT English', 'PGT Commerce & Accountancy', 'PGT Economics',
          'Senior Subject Teacher', 'Senior Teacher'
        ],
        'Secondary & Middle School Faculty (TGT - Grades VI–X)' => [
          'TGT Mathematics', 'TGT Science', 'TGT English', 'TGT Social Science',
          'TGT Tamil', 'TGT Hindi', 'Junior Subject Teacher', 'Middle School Teacher'
        ],
        'Primary & Foundational Faculty (PRT & KG)' => [
          'PRT / Primary Teacher', 'Foundational Stage Teacher', 'Kindergarten / Nursery Teacher'
        ],
        'Specialized & Co-Curricular Faculty' => [
          'Physical Education Teacher (PET)', 'Librarian', 'Art & Craft Teacher',
          'Music & Dance Instructor', 'Computer Lab Instructor', 'Science Lab Assistant', 'Student Counselor'
        ],
        'Support & Operations Staff' => [
          'Bus Driver', 'Transport Attendant', 'Housekeeping Staff', 'Kindergarten Caretaker', 'Campus Security Guard'
        ]
      ];
      $flatDesigs = [];
      foreach ($standardDesignations as $grp => $items) {
        foreach ($items as $it) { $flatDesigs[] = $it; }
      }

      $standardDepartments = [
        'Academic Wings & Disciplines' => [
          'Academics', 'Mathematics', 'Sciences', 'English & Languages',
          'Tamil Department', 'Hindi Department', 'Social Sciences',
          'Computer Science & IT', 'Commerce & Economics', 'Physical Education & Sports',
          'Arts, Crafts & Performing Arts'
        ],
        'School Stages & Wings' => [
          'Kindergarten & Pre-Primary', 'Primary Wing (Grades I–V)',
          'Middle School (Grades VI–VIII)', 'Secondary & Senior Secondary (Grades IX–XII)'
        ],
        'Administration & Campus Operations' => [
          'School Administration', 'Human Resources', 'Accounts & Finance',
          'Examination Cell', 'Library & Information', 'Health & Student Counseling',
          'Transport & Logistics', 'Housekeeping & Maintenance', 'Security & Campus Safety', 'Hostel & Residential Wing'
        ]
      ];
      $flatDepts = [];
      foreach ($standardDepartments as $grp => $items) {
        foreach ($items as $it) { $flatDepts[] = $it; }
      }

      $standardPayScales = [
        'Level 1 (₹18,000–₹25,000) — Support & Operations Staff',
        'Level 2 (₹19,900–₹32,000) — Junior Clerical & Office Staff',
        'Level 4 (₹25,500–₹40,000) — Pre-Primary / KG / Assistant Faculty',
        'Level 6 (PB-2 ₹35,400–₹58,000 GP 4200) — PRT / Primary Teachers',
        'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600) — TGT / Secondary Teachers',
        'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800) — PGT / Senior Secondary Faculty',
        'Level 9 (PB-2 ₹53,100–₹98,000 GP 5400) — Senior Subject Faculty / Coordinator',
        'Level 10 (PB-3 ₹56,100–₹1,15,000 GP 5400) — Vice Principal / AO',
        'Level 12 (PB-3 ₹78,800–₹1,50,000 GP 7600) — Principal / Director',
        'Consolidated Monthly Package (Fixed / Contractual)'
      ];

      $currDesigVal = old('designation', $employee->designation);
      $desigIsStandard = in_array($currDesigVal, $flatDesigs);
      $currDeptVal = old('department', $employee->department ?? 'Academics');
      $deptIsStandard = in_array($currDeptVal, $flatDepts);
      $currPayVal = old('pay_scale', $employee->pay_scale ?? 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)');
      $payIsStandard = in_array($currPayVal, $standardPayScales);
    @endphp

    <div class="card p-6 space-y-6 border border-slate-200/80 shadow-sm bg-white rounded-2xl" x-data="{
      empStatus: '{{ old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type ?? 'Permanent') }}',
      firstAppt: '{{ old('first_appointment_date', $employee->first_appointment_date?->format('Y-m-d')) }}',
      doj: '{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}',
      selectedDesig: '{{ $desigIsStandard ? $currDesigVal : ($currDesigVal ? 'Other' : 'Senior Subject Teacher') }}',
      customDesig: '{{ !$desigIsStandard ? $currDesigVal : '' }}',
      selectedDept: '{{ $deptIsStandard ? $currDeptVal : ($currDeptVal ? 'Other' : 'Academics') }}',
      customDept: '{{ !$deptIsStandard ? $currDeptVal : '' }}',
      selectedPay: '{{ $payIsStandard ? $currPayVal : ($currPayVal ? 'Other' : 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)') }}',
      customPay: '{{ !$payIsStandard ? $currPayVal : '' }}',
      get effectiveDesig() { return this.selectedDesig === 'Other' ? this.customDesig : this.selectedDesig; },
      get effectiveDept() { return this.selectedDept === 'Other' ? this.customDept : this.selectedDept; },
      get effectivePay() { return this.selectedPay === 'Other' ? this.customPay : this.selectedPay; },
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
      },
      get calculatedOverallExp() {
        if (!this.firstAppt) return '';
        const start = new Date(this.firstAppt);
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
      },
      syncOverall() {
        if (!this.firstAppt) return;
        const start = new Date(this.firstAppt);
        const now = new Date();
        if (isNaN(start.getTime()) || start > now) return;
        let years = now.getFullYear() - start.getFullYear();
        let months = now.getMonth() - start.getMonth();
        if (now.getDate() < start.getDate()) months--;
        if (months < 0) { years--; months += 12; }
        if (years < 0) { years = 0; months = 0; }
        const yInp = document.getElementById('experience_years');
        const mInp = document.getElementById('experience_months');
        if (yInp) yInp.value = years;
        if (mInp) mInp.value = months;
      }
    }">
      {{-- Section Header --}}
      <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-bold text-sm flex items-center justify-center shadow-md shadow-indigo-100">
            B
          </div>
          <div>
            <h3 class="font-bold text-slate-800 text-base tracking-tight flex items-center gap-2">
              SECTION B — EMPLOYMENT DETAILS
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Job role, designation, department, service timeline &amp; compensation terms</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
            <span x-text="category === 'teaching' ? 'Teaching Faculty' : (category === 'non_teaching' ? 'Non-Teaching Staff' : 'Operational Staff')"></span>
          </span>
        </div>
      </div>

      {{-- SUB-CARD 1: Role, Designation & Department --}}
      <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/40 space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
          <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          1. Role, Designation &amp; Department
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
          {{-- EMPLOYEE ID --}}
          <div>
            <label class="label text-xs font-semibold" for="employee_code">
              EMPLOYEE ID <span class="text-rose-500 font-bold">*</span>
            </label>
            <div class="relative">
              <input type="text" id="employee_code" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required
                     class="input font-mono font-bold text-indigo-700 bg-indigo-50/50 pl-8 @error('employee_code') border-rose-500 ring-2 ring-rose-200 @enderror">
              <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">#</span>
            </div>
            @error('employee_code')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- DESIGNATION DROPDOWN --}}
          <div>
            <label class="label text-xs font-semibold" for="designation_select">
              DESIGNATION <span class="text-rose-500 font-bold">*</span>
            </label>
            <select id="designation_select" x-model="selectedDesig" required
                    class="select text-xs @error('designation') border-rose-500 ring-2 ring-rose-200 @enderror">
              <option value="">-- Select Standard Designation --</option>
              @foreach($standardDesignations as $grpName => $items)
                <optgroup label="{{ $grpName }}">
                  @foreach($items as $desigItem)
                    <option value="{{ $desigItem }}">{{ $desigItem }}</option>
                  @endforeach
                </optgroup>
              @endforeach
              <option value="Other">-- Other (Enter Custom Designation) --</option>
            </select>
            <div x-show="selectedDesig === 'Other'" class="mt-2" style="display:none">
              <input type="text" x-model="customDesig" placeholder="Type custom designation name..."
                     class="input text-xs font-medium border-indigo-300 bg-indigo-50/30">
            </div>
            <input type="hidden" name="designation" :value="effectiveDesig">
            @error('designation')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- DEPARTMENT DROPDOWN --}}
          <div>
            <label class="label text-xs font-semibold" for="department_select">
              Department <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <select id="department_select" x-model="selectedDept"
                    class="select text-xs @error('department') border-rose-500 ring-2 ring-rose-200 @enderror">
              <option value="">-- Select School Department --</option>
              @foreach($standardDepartments as $grpName => $items)
                <optgroup label="{{ $grpName }}">
                  @foreach($items as $deptItem)
                    <option value="{{ $deptItem }}">{{ $deptItem }}</option>
                  @endforeach
                </optgroup>
              @endforeach
              <option value="Other">-- Other (Enter Custom Department) --</option>
            </select>
            <div x-show="selectedDept === 'Other'" class="mt-2" style="display:none">
              <input type="text" x-model="customDept" placeholder="Type custom department name..."
                     class="input text-xs font-medium border-indigo-300 bg-indigo-50/30">
            </div>
            <input type="hidden" name="department" :value="effectiveDept">
            @error('department')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      {{-- SUB-CARD 2: Appointment Timeline & Work Experience --}}
      <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/40 space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          2. Appointment Timeline &amp; Work Experience
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
          {{-- DOJ --}}
          <div>
            <label class="label text-xs font-semibold" for="joining_date">
              DOJ (Date of Joining) <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="date" id="joining_date" name="joining_date" x-model="doj" required
                   class="input @error('joining_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            <p class="text-[11px] font-semibold text-indigo-600 mt-1.5 flex items-center gap-1" x-show="calculatedExp">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Tenure: <strong x-text="calculatedExp"></strong> (auto-calculated from DOJ)</span>
            </p>
            @error('joining_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- DATE OF FIRST APPOINTMENT AS TEACHER --}}
          <div x-show="category === 'teaching'">
            <label class="label text-xs font-semibold" for="first_appointment_date">
              DATE OF FIRST APPOINTMENT AS TEACHER <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="date" id="first_appointment_date" name="first_appointment_date" x-model="firstAppt"
                   @change="syncOverall()"
                   :required="category === 'teaching'" :disabled="category !== 'teaching'"
                   class="input @error('first_appointment_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            <p class="text-[11px] font-semibold text-emerald-600 mt-1.5 flex items-center gap-1" x-show="calculatedOverallExp">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Experience: <strong x-text="calculatedOverallExp"></strong> (from First Appointment)</span>
            </p>
            @error('first_appointment_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- OVERALL WORK EXPERIENCE --}}
          <div>
            <label class="label text-xs font-semibold" for="experience_years">
              OVERALL WORK EXPERIENCE <span class="text-slate-400 font-normal">(Years &amp; Months)</span>
            </label>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <input type="number" id="experience_years" name="experience_years" value="{{ old('experience_years', $employee->experience_years) }}" min="0" max="60"
                       class="input text-xs font-mono font-bold text-indigo-700 bg-indigo-50/30" placeholder="Years (e.g. 5)">
                <span class="text-[10px] text-slate-400 block mt-0.5">Total Years</span>
              </div>
              <div>
                <input type="number" id="experience_months" name="experience_months" value="{{ old('experience_months', $employee->experience_months ?? 0) }}" min="0" max="11"
                       class="input text-xs font-mono font-bold text-indigo-700 bg-indigo-50/30" placeholder="Months (0–11)">
                <span class="text-[10px] text-slate-400 block mt-0.5">Total Months</span>
              </div>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">Auto-populated from First Appointment Date or entered manually.</p>
            @error('experience_years')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      {{-- SUB-CARD 3: Contract Terms, Tenure & Milestones --}}
      <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/40 space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
          <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          3. Employment Contract, Tenure &amp; Service Milestones
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
          {{-- CURRENT EMPLOYMENT STATUS --}}
          <div>
            <label class="label text-xs font-semibold" for="current_employment_status">
              CURRENT EMPLOYMENT STATUS <span class="text-rose-500 font-bold">*</span>
            </label>
            <select id="current_employment_status" name="current_employment_status" x-model="empStatus" required
                    class="select text-xs font-medium @error('current_employment_status') border-rose-500 ring-2 ring-rose-200 @enderror">
              <option value="Permanent" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type ?? 'Permanent')==='Permanent')>Permanent (Confirmed Regular)</option>
              <option value="Probation" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Probation')>Probation (Assessment Period)</option>
              <option value="Contractual" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Contractual')>Contractual (Fixed-Term Contract)</option>
              <option value="Temporary" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Temporary')>Temporary / Ad-Hoc</option>
              <option value="Part-Time" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Part-Time')>Part-Time / Visiting Faculty</option>
              <option value="Other" @selected(old('current_employment_status', $employee->current_employment_status ?? $employee->employment_type)==='Other')>Other</option>
            </select>
            @error('current_employment_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- PROBATION DATES (Shown when status is Probation) --}}
          <div x-show="empStatus === 'Probation'" style="display:none">
            <label class="label text-xs font-semibold" for="probation_start_date">
              Probation Start Date <span class="text-amber-600 font-bold">*</span>
            </label>
            <input type="date" id="probation_start_date" name="probation_start_date" value="{{ old('probation_start_date', $employee->probation_start_date?->format('Y-m-d')) }}"
                   class="input border-amber-300 bg-amber-50/20 @error('probation_start_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('probation_start_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div x-show="empStatus === 'Probation'" style="display:none">
            <label class="label text-xs font-semibold" for="probation_end_date">
              Probation Completion Date <span class="text-amber-600 font-bold">*</span>
            </label>
            <input type="date" id="probation_end_date" name="probation_end_date" value="{{ old('probation_end_date', $employee->probation_end_date?->format('Y-m-d')) }}"
                   class="input border-amber-300 bg-amber-50/20 @error('probation_end_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('probation_end_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- CONTRACT END DATE (Shown for Contractual / Temporary) --}}
          <div x-show="empStatus === 'Contractual' || empStatus === 'Temporary'" style="display:none">
            <label class="label text-xs font-semibold" for="contract_end_date">
              Contract End Date <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="date" id="contract_end_date" name="contract_end_date" value="{{ old('contract_end_date', $employee->contract_end_date?->format('Y-m-d')) }}"
                   class="input @error('contract_end_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('contract_end_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- CONFIRMATION DATE --}}
          <div>
            <label class="label text-xs font-semibold" for="confirmation_date">
              Confirmation Date <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="date" id="confirmation_date" name="confirmation_date" value="{{ old('confirmation_date', $employee->confirmation_date?->format('Y-m-d')) }}"
                   class="input @error('confirmation_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('confirmation_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- LAST PROMOTION DATE --}}
          <div>
            <label class="label text-xs font-semibold" for="last_promotion_date">
              Last Promotion Date <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="date" id="last_promotion_date" name="last_promotion_date" value="{{ old('last_promotion_date', $employee->last_promotion_date?->format('Y-m-d')) }}"
                   class="input @error('last_promotion_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('last_promotion_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      {{-- SUB-CARD 4: Compensation & Hierarchy --}}
      <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/40 space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          4. Compensation &amp; Reporting Hierarchy
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
          {{-- PRESENT PAY SCALE DROPDOWN --}}
          <div>
            <label class="label text-xs font-semibold" for="pay_scale_select">
              Present Pay Scale <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <select id="pay_scale_select" x-model="selectedPay"
                    class="select text-xs @error('pay_scale') border-rose-500 ring-2 ring-rose-200 @enderror">
              <option value="">-- Select Standard Pay Scale --</option>
              @foreach($standardPayScales as $pScale)
                <option value="{{ $pScale }}">{{ $pScale }}</option>
              @endforeach
              <option value="Other">-- Other (Enter Custom Pay Scale) --</option>
            </select>
            <div x-show="selectedPay === 'Other'" class="mt-2" style="display:none">
              <input type="text" x-model="customPay" placeholder="Type custom pay scale (e.g. PB-2 9300-34800 GP 4600)..."
                     class="input text-xs font-medium border-indigo-300 bg-indigo-50/30">
            </div>
            <input type="hidden" name="pay_scale" :value="effectivePay">
            @error('pay_scale')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- BASIC MONTHLY SALARY --}}
          <div>
            <label class="label text-xs font-semibold" for="basic_salary">
              Basic Monthly Salary (₹) <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <div class="relative">
              <input type="number" id="basic_salary" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" min="0" step="0.01"
                     class="input pl-8 @error('basic_salary') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 35000">
              <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">₹</span>
            </div>
            @error('basic_salary')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- REPORTING MANAGER --}}
          <div>
            <label class="label text-xs font-semibold" for="manager_id">
              Reporting Manager <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <select id="manager_id" name="manager_id" class="select text-xs @error('manager_id') border-rose-500 ring-2 ring-rose-200 @enderror">
              <option value="">-- No Reporting Manager / Self --</option>
              @if(isset($managers))
                @foreach($managers as $mgr)
                  <option value="{{ $mgr->id }}" @selected(old('manager_id', $employee->manager_id) == $mgr->id)>
                    {{ $mgr->full_name }} ({{ $mgr->employee_code }} — {{ $mgr->designation }})
                  </option>
                @endforeach
              @endif
            </select>
            @error('manager_id')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>
    </div>

        <div x-show="category === 'teaching'">
          <label class="label text-xs font-semibold" for="lab_subjects">
            Practical / Lab Subjects <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="lab_subjects" name="lab_subjects" value="{{ old('lab_subjects', $employee->lab_subjects) }}"
                 class="input @error('lab_subjects') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Physics Lab">
          @error('lab_subjects')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION B.2 — TEACHING LEVEL ELIGIBILITY & ACADEMIC ALLOCATIONS --}}
    <div x-show="category === 'teaching'" class="card p-5 space-y-4 border-2 border-indigo-100 bg-gradient-to-r from-white via-indigo-50/20 to-white">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">TL</span>
          TEACHING LEVEL ELIGIBILITY &amp; WORKLOAD CONSTRAINTS
        </h3>
        <span class="text-[11px] text-indigo-600 font-semibold">Configurable Workload &amp; Combinations</span>
      </div>

      <div class="space-y-4">
        {{-- Teaching Levels Checkboxes --}}
        <div>
          <label class="label text-xs font-semibold mb-2 block">
            Approved &amp; Eligible Teaching Levels
            <span class="text-[11px] text-slate-500 font-normal ml-1">(Select levels this teacher is certified to teach)</span>
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if(isset($teachingLevels))
              @php
                $activeLvlIds = $employee->eligibleTeachingLevels->pluck('id')->toArray();
              @endphp
              @foreach($teachingLevels as $lvl)
                <label class="p-3 rounded-xl border border-slate-200 hover:border-indigo-400 bg-white cursor-pointer flex items-start gap-3 transition">
                  <input type="checkbox" name="teaching_levels[]" value="{{ $lvl->id }}" class="rounded text-indigo-600 focus:ring-indigo-500 mt-1"
                         @checked(in_array($lvl->id, old('teaching_levels', $activeLvlIds)))>
                  <div>
                    <span class="text-xs font-bold text-slate-900 block">{{ $lvl->name }}</span>
                    <span class="text-[10px] text-slate-500 leading-tight block mt-0.5">{{ $lvl->description }}</span>
                    <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold {{ $lvl->badge_class }}">
                      {{ ucfirst(str_replace('_', ' ', $lvl->schedule_type)) }}
                    </span>
                  </div>
                </label>
              @endforeach
            @endif
          </div>
        </div>

        {{-- Workload Constraints --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div>
            <label class="label text-xs font-semibold" for="max_daily_periods">
              Max Periods / Day (Daily Limit) <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="number" id="max_daily_periods" name="max_daily_periods" value="{{ old('max_daily_periods', $employee->max_daily_periods ?? 6) }}" min="1" max="10"
                   class="input font-bold text-indigo-700" placeholder="e.g. 6">
            <span class="text-[10px] text-slate-400 mt-0.5 block">Prevents teacher daily double booking and overload (Default: 6)</span>
          </div>
          <div>
            <label class="label text-xs font-semibold" for="max_weekly_periods">
              Max Periods / Week (Weekly Workload) <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="number" id="max_weekly_periods" name="max_weekly_periods" value="{{ old('max_weekly_periods', $employee->max_weekly_periods ?? 30) }}" min="1" max="50"
                   class="input font-bold text-indigo-700" placeholder="e.g. 30">
            <span class="text-[10px] text-slate-400 mt-0.5 block">Maximum teaching periods per week (Default: 30)</span>
          </div>
        </div>

        {{-- Availability & Preferred Working Days / Periods --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-2">
          {{-- Unavailable Working Days --}}
          <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
            <div>
              <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span>🗓️ Working Days Restriction</span>
              </span>
              <span class="text-[10px] text-slate-500 block">Mark days teacher is unavailable for timetable allocation</span>
            </div>
            <div>
              <label class="text-[11px] font-semibold text-rose-700 block mb-1">🚫 Unavailable Working Days:</label>
              @php
                $unDays = is_array($employee->unavailable_days) ? $employee->unavailable_days : [];
              @endphp
              <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                @foreach(['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat'] as $num => $day)
                  <label class="px-2 py-1 rounded-lg border border-slate-200 bg-white text-[11px] font-semibold text-slate-700 flex items-center gap-1 cursor-pointer hover:border-rose-300">
                    <input type="checkbox" name="unavailable_days[]" value="{{ $num }}" class="rounded text-rose-600 focus:ring-rose-500"
                           @checked(in_array((string)$num, old('unavailable_days', $unDays)))>
                    <span>{{ $day }}</span>
                  </label>
                @endforeach
              </div>
            </div>
          </div>

          {{-- Unavailable Periods --}}
          <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
            <div>
              <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span>⏰ Period Slot Restrictions</span>
              </span>
              <span class="text-[10px] text-slate-500 block">Mark periods where teacher is blocked from teaching</span>
            </div>
            <div>
              <label class="text-[11px] font-semibold text-rose-700 block mb-1">🚫 Unavailable Periods:</label>
              @php
                $unPeriods = is_array($employee->unavailable_periods) ? $employee->unavailable_periods : [];
              @endphp
              <div class="grid grid-cols-4 sm:grid-cols-8 gap-1.5">
                @for($p = 1; $p <= 8; $p++)
                  <label class="px-1.5 py-1 rounded-lg border border-slate-200 bg-white text-[10.5px] font-semibold text-slate-700 flex items-center justify-center gap-1 cursor-pointer hover:border-rose-300">
                    <input type="checkbox" name="unavailable_periods[]" value="{{ $p }}" class="rounded text-rose-600 focus:ring-rose-500"
                           @checked(in_array((int)$p, old('unavailable_periods', $unPeriods)))>
                    <span>P{{ $p }}</span>
                  </label>
                @endfor
              </div>
            </div>
          </div>
        </div>

        {{-- Multi-Class & Multi-Subject Combinations Matrix Builder --}}
        <div class="p-4 rounded-2xl bg-white border border-indigo-200/80 shadow-xs space-y-3"
             x-data="{
               allocations: [
                 @if($employee->teacherSubjectAllocations->isNotEmpty())
                   @foreach($employee->teacherSubjectAllocations as $alloc)
                     { class_id: '{{ $alloc->class_id }}', section_id: '{{ $alloc->section_id ?? '' }}', subject_id: '{{ $alloc->subject_id }}' },
                   @endforeach
                 @endif
               ],
               classesList: [
                 @if(isset($classes))
                   @foreach($classes as $c)
                     { id: {{ $c->id }}, name: '{{ $c->name }}', sections: [
                       @foreach($c->sections as $s)
                         { id: {{ $s->id }}, name: '{{ $s->name }}' },
                       @endforeach
                     ] },
                   @endforeach
                 @endif
               ],
               subjectsList: [
                 @if(isset($subjects))
                   @foreach($subjects as $s)
                     { id: {{ $s->id }}, name: '{{ $s->name }}', code: '{{ $s->code }}' },
                   @endforeach
                 @endif
               ],
               addAllocation() {
                 this.allocations.push({ class_id: '', section_id: '', subject_id: '' });
               },
               removeAllocation(index) {
                 this.allocations.splice(index, 1);
               },
               getSectionsForClass(classId) {
                 const c = this.classesList.find(item => String(item.id) === String(classId));
                 return c ? c.sections : [];
               }
             }">
          <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div>
              <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                <span>📚 Teaching Eligibility Matrix (Class &amp; Subject Combinations)</span>
              </span>
              <p class="text-[11px] text-slate-500 mt-0.5">Explicitly defines which subjects this teacher can teach in specific classes and sections.</p>
            </div>
            <button type="button" @click="addAllocation()"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition flex items-center gap-1.5 cursor-pointer">
              <span>➕ Add Subject Allocation</span>
            </button>
          </div>

          <template x-if="allocations.length === 0">
            <div class="py-4 text-center border-2 border-dashed border-slate-200 rounded-xl text-slate-400 text-xs">
              <span>No specific subject allocations configured yet. Click <strong>➕ Add Subject Allocation</strong> to configure specific teaching combinations.</span>
            </div>
          </template>

          <template x-if="allocations.length > 0">
            <div class="space-y-2">
              <template x-for="(alloc, idx) in allocations" :key="idx">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 items-center">
                  <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase">Class</label>
                    <select :name="'allocations[' + idx + '][class_id]'" x-model="alloc.class_id" required
                            class="select text-xs w-full h-8 px-2 mt-0.5">
                      <option value="">-- Select Class --</option>
                      <template x-for="c in classesList" :key="c.id">
                        <option :value="c.id" x-text="c.name" :selected="String(c.id) === String(alloc.class_id)"></option>
                      </template>
                    </select>
                  </div>

                  <div class="sm:col-span-3">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase">Section</label>
                    <select :name="'allocations[' + idx + '][section_id]'" x-model="alloc.section_id"
                            class="select text-xs w-full h-8 px-2 mt-0.5">
                      <option value="">All Sections</option>
                      <template x-for="s in getSectionsForClass(alloc.class_id)" :key="s.id">
                        <option :value="s.id" x-text="'Section ' + s.name" :selected="String(s.id) === String(alloc.section_id)"></option>
                      </template>
                    </select>
                  </div>

                  <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase">Subject</label>
                    <select :name="'allocations[' + idx + '][subject_id]'" x-model="alloc.subject_id" required
                            class="select text-xs w-full h-8 px-2 mt-0.5">
                      <option value="">-- Select Subject --</option>
                      <template x-for="sb in subjectsList" :key="sb.id">
                        <option :value="sb.id" x-text="sb.name + (sb.code ? ' (' + sb.code + ')' : '')" :selected="String(sb.id) === String(alloc.subject_id)"></option>
                      </template>
                    </select>
                  </div>

                  <div class="sm:col-span-1 flex items-end justify-center pt-3 sm:pt-0">
                    <button type="button" @click="removeAllocation(idx)"
                            class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition cursor-pointer" title="Remove Allocation">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>
              </template>
            </div>
          </template>
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

        <div>
          <label class="label text-xs font-semibold" for="uan_no">
            UAN Number <span class="text-slate-400 font-normal">(EPF UAN, Optional)</span>
          </label>
          <input type="text" id="uan_no" name="uan_no" value="{{ old('uan_no', $employee->uan_no) }}"
                 class="input font-mono @error('uan_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="12-digit UAN">
          @error('uan_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="esi_no">
            ESI Number <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="esi_no" name="esi_no" value="{{ old('esi_no', $employee->esi_no) }}"
                 class="input font-mono @error('esi_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="17-digit ESI Number">
          @error('esi_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_tax_no">
            Professional Tax Number <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="professional_tax_no" name="professional_tax_no" value="{{ old('professional_tax_no', $employee->professional_tax_no) }}"
                 class="input font-mono @error('professional_tax_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. PT-TN-2024-001">
          @error('professional_tax_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="passport_no">
            Passport Number <span class="text-slate-400 font-normal">(If applicable)</span>
          </label>
          <input type="text" id="passport_no" name="passport_no" value="{{ old('passport_no', $employee->passport_no) }}"
                 class="input font-mono uppercase @error('passport_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Z1234567">
          @error('passport_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
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

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

        <div>
          <label class="label text-xs font-semibold" for="alternate_phone">
            Alternate Telephone Number <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="tel" id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone', $employee->alternate_phone) }}" maxlength="10"
                 class="input font-mono @error('alternate_phone') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10-digit secondary telephone">
          @error('alternate_phone')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
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

        <div>
          <label class="label text-xs font-semibold" for="major_subject">
            Major Subject / Specialization <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="major_subject" name="major_subject" value="{{ old('major_subject', $employee->major_subject) }}"
                 class="input @error('major_subject') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Mathematics / Physics">
          @error('major_subject')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="minor_subject">
            Minor Subject <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="minor_subject" name="minor_subject" value="{{ old('minor_subject', $employee->minor_subject) }}"
                 class="input @error('minor_subject') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Statistics / Chemistry">
          @error('minor_subject')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs font-semibold" for="special_skills">
            Special Skills / Certifications &amp; Supporting Competencies <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="special_skills" name="special_skills" value="{{ old('special_skills', $employee->special_skills) }}"
                 class="input @error('special_skills') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Smart Board Certified, Vedic Math Trainer, Robotics, First Aid">
          @error('special_skills')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
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
    <div class="card p-6 space-y-5 bg-gradient-to-b from-white to-slate-50/50 border border-slate-200/80 shadow-sm rounded-2xl"
         x-data="{
           setQualPreset(val) {
             const el = document.getElementById('additional_qual_after_recruitment');
             if (el) {
               el.value = el.value && el.value !== 'None / Not Applicable' ? el.value + '; ' + val : val;
               el.dispatchEvent(new Event('input'));
             }
           },
           setTrainingPreset(val) {
             const el = document.getElementById('training_attended_before_recruitment');
             if (el) {
               el.value = el.value && el.value !== 'None / Not Applicable' ? el.value + '; ' + val : val;
               el.dispatchEvent(new Event('input'));
             }
           }
         }">
      <div class="border-b border-slate-200/70 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white text-xs font-black flex items-center justify-center shadow-md shadow-indigo-100">J</span>
          <div>
            <h3 class="font-bold text-slate-900 text-sm tracking-wide">
              SECTION J — ADDITIONAL QUALIFICATION &amp; TRAINING
            </h3>
            <p class="text-[11px] text-slate-500 font-medium">Post-recruitment credentials, CBSE capacity building, and specialized teacher workshops</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Optional • Non-blocking
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Column 1: Additional Qualification --}}
        <div class="bg-white rounded-xl p-4 border border-slate-200/90 shadow-sm space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div>
              <label class="block text-xs font-bold text-slate-800 uppercase tracking-wide" for="additional_qual_after_recruitment">
                Additional Qualification Obtained After Recruitment
              </label>
              <p class="text-[11px] text-slate-500 mt-0.5">Degrees, Diplomas, PGDCA, or specialized certifications acquired during service</p>
            </div>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">Optional</span>
          </div>

          {{-- Quick Presets --}}
          <div class="flex items-center flex-wrap gap-1.5 pt-1">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Quick Fill:</span>
            <button type="button" @click="setQualPreset('None / Not Applicable')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition">None / Not Applicable</button>
            <button type="button" @click="setQualPreset('PGDCA (Computer Applications)')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">PGDCA</button>
            <button type="button" @click="setQualPreset('M.Ed / M.Phil Degree')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">M.Ed / M.Phil</button>
            <button type="button" @click="setQualPreset('Special Education Diploma')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">Special Ed</button>
          </div>

          <textarea id="additional_qual_after_recruitment" name="additional_qual_after_recruitment" rows="4"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 p-3.5 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition min-h-[105px] resize-y leading-relaxed @error('additional_qual_after_recruitment') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="e.g. Completed PGDCA from Bharathiar University in 2023; Certified in Google for Education">{{ old('additional_qual_after_recruitment', $employee->additional_qual_after_recruitment ?? 'None / Not Applicable') }}</textarea>
          @error('additional_qual_after_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Column 2: Training Programme --}}
        <div class="bg-white rounded-xl p-4 border border-slate-200/90 shadow-sm space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div>
              <label class="block text-xs font-bold text-slate-800 uppercase tracking-wide" for="training_attended_before_recruitment">
                Training Programme Attended Before Recruitment
              </label>
              <p class="text-[11px] text-slate-500 mt-0.5">CBSE capacity-building programmes, pedagogy workshops, or orientation courses</p>
            </div>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">Optional</span>
          </div>

          {{-- Quick Presets --}}
          <div class="flex items-center flex-wrap gap-1.5 pt-1">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Quick Fill:</span>
            <button type="button" @click="setTrainingPreset('None / Not Applicable')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition">None / Not Applicable</button>
            <button type="button" @click="setTrainingPreset('CBSE Capacity Building Programme (CBP)')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">CBSE CBP</button>
            <button type="button" @click="setTrainingPreset('NEP 2020 Pedagogical Workshop')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">NEP 2020</button>
            <button type="button" @click="setTrainingPreset('FLN & Foundational Literacy Training')" class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">FLN</button>
          </div>

          <textarea id="training_attended_before_recruitment" name="training_attended_before_recruitment" rows="4"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 p-3.5 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition min-h-[105px] resize-y leading-relaxed @error('training_attended_before_recruitment') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="e.g. Attended 5-day CBSE Capacity Building Programme on Experiential Learning; DIKSHA NISHTHA 2.0 module certified">{{ old('training_attended_before_recruitment', $employee->training_attended_before_recruitment ?? 'None / Not Applicable') }}</textarea>
          @error('training_attended_before_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION K — SALARY INCREMENTS & NOMINEE BENEFICIARY DETAILS (Headings 7 & 11) --}}
    <div class="card p-5 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">K</span>
          SECTION K — SALARY INCREMENTS &amp; NOMINEE BENEFICIARY DETAILS
        </h3>
        <span class="text-[11px] text-slate-400 font-medium">Annual increment settings and statutory PF nominee info</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="annual_increment">
            Annual Increment Amount (₹) <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="number" id="annual_increment" name="annual_increment" value="{{ old('annual_increment', $employee->annual_increment) }}" min="0" step="0.01"
                 class="input font-mono @error('annual_increment') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 1500">
          @error('annual_increment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="increment_effective_date">
            Increment Effective Date <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="date" id="increment_effective_date" name="increment_effective_date" value="{{ old('increment_effective_date', $employee->increment_effective_date?->format('Y-m-d')) }}"
                 class="input @error('increment_effective_date') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('increment_effective_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="nominee_share">
            Nominee Contribution Share (%) <span class="text-slate-400 font-normal">(Default 100%)</span>
          </label>
          <input type="number" id="nominee_share" name="nominee_share" value="{{ old('nominee_share', $employee->nominee_share ?? 100) }}" min="1" max="100"
                 class="input font-mono @error('nominee_share') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="100">
          @error('nominee_share')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="nominee_name">
            Nominee Full Name <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="nominee_name" name="nominee_name" value="{{ old('nominee_name', $employee->nominee_name) }}"
                 class="input @error('nominee_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full name of beneficiary">
          @error('nominee_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="nominee_relationship">
            Nominee Relationship <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="nominee_relationship" name="nominee_relationship" value="{{ old('nominee_relationship', $employee->nominee_relationship) }}"
                 class="input @error('nominee_relationship') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Spouse, Son, Mother">
          @error('nominee_relationship')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
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
