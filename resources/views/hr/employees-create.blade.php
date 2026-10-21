@extends('layouts.app')

@section('title', 'Add New Staff Member — Master Record')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
  category: '{{ old('employee_type', 'teaching') }}',
  maritalStatus: '{{ old('marital_status', 'Single') }}',
  isSubmitting: false,
  validateForm(e) {
    const isSupport = ['driver', 'cleaner', 'nanny'].includes(this.category);
    const form = e.target;

    // 1. Mobile Number Check (10 digits)
    const mobileEl = document.getElementById('mobile');
    if (mobileEl && mobileEl.value) {
      const cleanMob = mobileEl.value.replace(/[\s\-]/g, '');
      if (!/^[6-9]\d{9}$/.test(cleanMob)) {
        e.preventDefault();
        alert('Primary Contact No. (Mobile) must be a valid 10-digit Indian phone number starting with 6, 7, 8, or 9.');
        mobileEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        mobileEl.focus();
        return false;
      }
    }

    // 2. Emergency Contact Phone (10 digits)
    const contactNoEl = document.getElementById('contact_no');
    if (contactNoEl && contactNoEl.value) {
      const cleanContact = contactNoEl.value.replace(/[\s\-]/g, '');
      if (!/^\d{10}$/.test(cleanContact)) {
        e.preventDefault();
        alert('Emergency Contact Person Phone must be exactly 10 digits.');
        contactNoEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        contactNoEl.focus();
        return false;
      }
    }

    // 3. Alternate Phone (10 digits if provided & distinct from primary)
    const altPhoneEl = document.getElementById('alternate_phone');
    if (altPhoneEl && altPhoneEl.value) {
      const cleanAlt = altPhoneEl.value.replace(/[\s\-]/g, '');
      if (!/^\d{10}$/.test(cleanAlt)) {
        e.preventDefault();
        alert('Alternate Telephone must be exactly 10 digits if entered.');
        altPhoneEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        altPhoneEl.focus();
        return false;
      }
      if (mobileEl && cleanAlt === mobileEl.value.replace(/[\s\-]/g, '')) {
        e.preventDefault();
        alert('Alternate telephone number cannot be identical to the primary contact number. Please provide a distinct phone number.');
        altPhoneEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        altPhoneEl.focus();
        return false;
      }
    }

    // 4. Postal PIN Code (6 digits required)
    const pinEl = document.getElementById('pincode');
    if (!isSupport && pinEl && !pinEl.value.trim()) {
      e.preventDefault();
      alert('Postal PIN Code is required.');
      pinEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      pinEl.focus();
      return false;
    }
    if (pinEl && pinEl.value) {
      const cleanPin = pinEl.value.trim();
      if (!/^[1-9][0-9]{5}$/.test(cleanPin)) {
        e.preventDefault();
        alert('Postal PIN Code must be a valid 6-digit Indian PIN code (e.g. 638001).');
        pinEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        pinEl.focus();
        return false;
      }
    }

    // 5. Official Email Check
    const emailEl = document.getElementById('official_email');
    if (emailEl && emailEl.value) {
      const cleanEmail = emailEl.value.trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleanEmail)) {
        e.preventDefault();
        alert('Please enter a valid Official Email ID (e.g. staff@schoolerp.in).');
        emailEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        emailEl.focus();
        return false;
      }
    }

    // 6. Aadhaar Number Check (12 digits)
    const aadharEl = document.getElementById('aadhaar_no');
    if (!isSupport && aadharEl && aadharEl.value) {
      const cleanAadhaar = aadharEl.value.replace(/[\s\-]/g, '');
      if (!/^\d{12}$/.test(cleanAadhaar)) {
        e.preventDefault();
        alert('AADHAR NO must be exactly 12 numeric digits.');
        aadharEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        aadharEl.focus();
        return false;
      }
    }

    // 7. PAN Card Check (10 characters: 5 letters, 4 digits, 1 letter)
    const panEl = document.getElementById('pan_no');
    if (!isSupport && panEl && panEl.value) {
      const cleanPan = panEl.value.trim().toUpperCase();
      if (!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(cleanPan)) {
        e.preventDefault();
        alert('PAN NO. must follow the standard 10-character format: 5 letters, 4 numbers, 1 letter (e.g. ABCDE1234F).');
        panEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        panEl.focus();
        return false;
      }
    }

    // 8. Class Teacher In-Charge validation (requires both Class and Section)
    const isCtEl = document.getElementById('is_class_teacher');
    if (isCtEl && isCtEl.checked) {
      const clsEl = document.getElementById('assigned_class_id');
      const secEl = document.getElementById('assigned_section_id');
      if (!clsEl || !clsEl.value || !secEl || !secEl.value) {
        e.preventDefault();
        alert('To assign staff as Class Teacher In-Charge, please select both a Class and a Section.');
        if (!clsEl?.value) {
          clsEl?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          clsEl?.focus();
        } else {
          secEl?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          secEl?.focus();
        }
        return false;
      }
    }

    // 9. Married Staff children count check
    if (this.maritalStatus === 'Married') {
      const numChildren = parseInt(document.getElementById('number_of_children')?.value || '0', 10);
      const maleChildren = parseInt(document.getElementById('children_male')?.value || '0', 10);
      const femaleChildren = parseInt(document.getElementById('children_female')?.value || '0', 10);
      if (maleChildren + femaleChildren !== numChildren) {
        e.preventDefault();
        alert(`The sum of Male (${maleChildren}) and Female (${femaleChildren}) children must equal the Total Number of Children (${numChildren}).`);
        document.getElementById('number_of_children')?.focus();
        return false;
      }
    }

    if (!form.checkValidity()) {
      e.preventDefault();
      const firstInvalid = form.querySelector(':invalid');
      if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus();
      }
      return false;
    }

    if (!isSupport) {
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

    this.isSubmitting = true;
    return true;
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

  {{-- Flash Session Success Banner --}}
  @if (session('success'))
  <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
    <svg class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div class="flex-1">
      <h4 class="font-bold text-emerald-900">Success</h4>
      <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</p>
    </div>
  </div>
  @endif

  {{-- Flash Session Error Banner --}}
  @if (session('error'))
  <div class="p-4 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 text-sm flex items-start gap-3 shadow-md" role="alert">
    <svg class="w-5 h-5 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div class="flex-1">
      <h4 class="font-bold text-rose-900 text-sm">Action Required: Could Not Save Staff Record</h4>
      <p class="text-xs text-rose-800 mt-1 leading-relaxed">{{ session('error') }}</p>
    </div>
  </div>
  @endif

  {{-- Validation Errors Banner --}}
  @if (isset($errors) && $errors->any())
  <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
    <svg class="w-5 h-5 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div class="flex-1">
      <h4 class="font-bold text-rose-900">Staff Creation Blocked — Please Correct {{ $errors->count() }} Error(s)</h4>
      <p class="text-xs text-rose-700 mt-0.5 mb-2">Review the highlighted fields below to continue:</p>
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

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        {{-- Card 1: Teaching Staff --}}
        <label @click="category = 'teaching'"
               :class="category === 'teaching' ? 'border-indigo-600 bg-indigo-50/90 ring-2 ring-indigo-200 shadow-xs' : 'border-slate-200 bg-white hover:border-indigo-300'"
               class="cursor-pointer p-3.5 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="teaching" x-model="category" class="sr-only">
          <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold mb-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
          </div>
          <span class="text-xs font-bold text-slate-800">Teaching Staff</span>
          <span class="text-[11px] text-slate-500 mt-0.5">Faculty &amp; Teachers</span>
          <span class="mt-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-700">RBAC Enabled</span>
        </label>

        {{-- Card 2: Non-Teaching Staff --}}
        <label @click="category = 'non_teaching'"
               :class="category === 'non_teaching' ? 'border-purple-600 bg-purple-50/90 ring-2 ring-purple-200 shadow-xs' : 'border-slate-200 bg-white hover:border-purple-300'"
               class="cursor-pointer p-3.5 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="non_teaching" x-model="category" class="sr-only">
          <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold mb-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <span class="text-xs font-bold text-slate-800">Non-Teaching</span>
          <span class="text-[11px] text-slate-500 mt-0.5">Admin &amp; Accounts</span>
          <span class="mt-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700">RBAC Enabled</span>
        </label>

        {{-- Card 3: Drivers --}}
        <label @click="category = 'driver'"
               :class="category === 'driver' ? 'border-amber-600 bg-amber-50/90 ring-2 ring-amber-200 shadow-xs' : 'border-slate-200 bg-white hover:border-amber-300'"
               class="cursor-pointer p-3.5 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="driver" x-model="category" class="sr-only">
          <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold mb-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-4 4h4m1 4H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z"/></svg>
          </div>
          <span class="text-xs font-bold text-slate-800">Drivers</span>
          <span class="text-[11px] text-slate-500 mt-0.5">Bus &amp; Van Drivers</span>
          <span class="mt-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600">No Portal / RBAC</span>
        </label>

        {{-- Card 4: Cleaners --}}
        <label @click="category = 'cleaner'"
               :class="category === 'cleaner' ? 'border-teal-600 bg-teal-50/90 ring-2 ring-teal-200 shadow-xs' : 'border-slate-200 bg-white hover:border-teal-300'"
               class="cursor-pointer p-3.5 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="cleaner" x-model="category" class="sr-only">
          <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-bold mb-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
          </div>
          <span class="text-xs font-bold text-slate-800">Cleaners</span>
          <span class="text-[11px] text-slate-500 mt-0.5">Housekeeping</span>
          <span class="mt-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600">No Portal / RBAC</span>
        </label>

        {{-- Card 5: Nannies (Naanis) --}}
        <label @click="category = 'nanny'"
               :class="category === 'nanny' ? 'border-rose-600 bg-rose-50/90 ring-2 ring-rose-200 shadow-xs' : 'border-slate-200 bg-white hover:border-rose-300'"
               class="cursor-pointer p-3.5 rounded-xl border text-center transition-all duration-150 flex flex-col items-center">
          <input type="radio" name="employee_type" value="nanny" x-model="category" class="sr-only">
          <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold mb-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
          </div>
          <span class="text-xs font-bold text-slate-800">Nannies (Naanis)</span>
          <span class="text-[11px] text-slate-500 mt-0.5">Pre-Primary Care</span>
          <span class="mt-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600">No Portal / RBAC</span>
        </label>
      </div>

      {{-- Notice 1: Automatic RBAC Provisioning Notice (Teaching & Non-Teaching) --}}
      <div x-show="category === 'teaching' || category === 'non_teaching'" class="mt-4 p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 text-xs flex items-start gap-2.5">
        <svg class="w-4 h-4 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <div class="space-y-0.5">
          <p class="font-bold text-blue-900">Automatic User Account Creation &amp; RBAC Synchronization</p>
          <p class="text-blue-700">Upon saving this onboarding form, a user account will be automatically created with <strong>Username = Employee ID</strong> and a secure temporary password derived from Date of Birth (<strong>DDMMYYYY</strong>). It will appear immediately in the RBAC User Management screen for fine-grained role and module permission assignment.</p>
        </div>
      </div>

      {{-- Notice 2: Operational Support Staff Notice (Drivers, Cleaners, Nannies) --}}
      <div x-show="['driver', 'cleaner', 'nanny'].includes(category)" class="mt-4 p-3.5 bg-amber-50/80 border border-amber-200 rounded-xl text-amber-900 text-xs flex items-start gap-2.5">
        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="space-y-0.5">
          <p class="font-bold text-amber-900">Operational Support Personnel — Excluded from RBAC &amp; Portal Accounts</p>
          <p class="text-amber-800">Drivers, cleaners, and nannies are operational/support staff records. No system login user accounts or RBAC role permissions will be created. The staff profile is maintained strictly for HR directory, payroll, attendance, and campus operations.</p>
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
            DOB <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" id="dob" name="dob" value="{{ old('dob') }}" :required="category === 'teaching' || category === 'non_teaching'" max="{{ date('Y-m-d', strtotime('-18 years')) }}"
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
            MOTHER TONGUE <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="mother_tongue" name="mother_tongue" value="{{ old('mother_tongue', 'Tamil') }}" :required="category === 'teaching' || category === 'non_teaching'"
                 class="input @error('mother_tongue') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter mother tongue">
          @error('mother_tongue')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="nationality">
            NATIONALITY <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="nationality" name="nationality" value="{{ old('nationality', 'Indian') }}" :required="category === 'teaching' || category === 'non_teaching'"
                 class="input @error('nationality') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Enter nationality">
          @error('nationality')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- RELIGION DROPDOWN --}}
        <div x-data="{
          selectedRel: '{{ in_array(old('religion', 'Hindu'), ['Hindu', 'Christian', 'Muslim', 'Sikh', 'Jain', 'Buddhist', 'Parsi', 'Jewish']) ? old('religion', 'Hindu') : (old('religion') ? 'Other' : 'Hindu') }}',
          customRel: '{{ !in_array(old('religion', 'Hindu'), ['Hindu', 'Christian', 'Muslim', 'Sikh', 'Jain', 'Buddhist', 'Parsi', 'Jewish', '']) ? old('religion') : '' }}',
          get effectiveRel() { return this.selectedRel === 'Other' ? this.customRel : this.selectedRel; }
        }">
          <label class="label text-xs font-semibold" for="religion_select">
            RELIGION <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
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
          selectedCaste: '{{ in_array(old('caste', 'General'), ['General', 'OBC', 'BC', 'BCM', 'MBC', 'DNC', 'SC', 'SCA', 'ST', 'EWS']) ? old('caste', 'General') : (old('caste') ? 'Other' : 'General') }}',
          customCaste: '{{ !in_array(old('caste', 'General'), ['General', 'OBC', 'BC', 'BCM', 'MBC', 'DNC', 'SC', 'SCA', 'ST', 'EWS', '']) ? old('caste') : '' }}',
          get effectiveCaste() { return this.selectedCaste === 'Other' ? this.customCaste : this.selectedCaste; }
        }">
          <label class="label text-xs font-semibold" for="caste_select">
            CASTE <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
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
            MARITAL STATUS <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <select id="marital_status" name="marital_status" x-model="maritalStatus" :required="category === 'teaching' || category === 'non_teaching'" class="select @error('marital_status') border-rose-500 ring-2 ring-rose-200 @enderror">
            <option value="">-- Select Status --</option>
            <option value="Single" @selected(old('marital_status')==='Single' || !old('marital_status'))>Single</option>
            <option value="Married" @selected(old('marital_status')==='Married')>Married</option>
          </select>
          <p class="text-[11px] text-slate-400 mt-1" x-show="maritalStatus !== 'Married'">Children count is not required for single staff.</p>
          @error('marital_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Children count fields: ONLY needed & visible for Married staff --}}
        <div x-show="maritalStatus === 'Married'" @if(old('marital_status', 'Single') !== 'Married') style="display:none" @endif>
          <label class="label text-xs font-semibold" for="number_of_children">
            NUMBER OF CHILDREN <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="number_of_children" name="number_of_children" value="{{ old('number_of_children', '0') }}"
                 :required="(category === 'teaching' || category === 'non_teaching') && maritalStatus === 'Married'"
                 :disabled="maritalStatus !== 'Married'"
                 min="0" max="20"
                 class="input @error('number_of_children') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('number_of_children')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-show="maritalStatus === 'Married'" @if(old('marital_status', 'Single') !== 'Married') style="display:none" @endif>
          <label class="label text-xs font-semibold" for="children_male">
            MALE <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_male" name="children_male" value="{{ old('children_male', '0') }}"
                 :required="(category === 'teaching' || category === 'non_teaching') && maritalStatus === 'Married'"
                 :disabled="maritalStatus !== 'Married'"
                 min="0" max="20"
                 class="input @error('children_male') border-rose-500 ring-2 ring-rose-200 @enderror">
          @error('children_male')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-show="maritalStatus === 'Married'" @if(old('marital_status', 'Single') !== 'Married') style="display:none" @endif>
          <label class="label text-xs font-semibold" for="children_female">
            FEMALE <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="number" id="children_female" name="children_female" value="{{ old('children_female', '0') }}"
                 :required="(category === 'teaching' || category === 'non_teaching') && maritalStatus === 'Married'"
                 :disabled="maritalStatus !== 'Married'"
                 min="0" max="20"
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

        <div class="sm:col-span-2">
          <label class="label text-xs font-semibold" for="address">
            CURRENT RESIDENTIAL ADDRESS <span class="text-rose-500 font-bold">*</span>
          </label>
          <textarea id="address" name="address" rows="2" required
                    class="w-full rounded-xl border border-slate-300 p-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 min-h-[72px] resize-y leading-relaxed @error('address') border-rose-500 ring-2 ring-rose-200 @enderror"
                    placeholder="Door No, Building, Street Name, Area / Locality, City / Town">{{ old('address') }}</textarea>
          @error('address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="pincode">
            POSTAL PIN CODE <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="pincode" name="pincode" value="{{ old('pincode') }}" maxlength="6"
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
                    placeholder="Permanent address / native place (optional)">{{ old('permanent_address') }}</textarea>
          @error('permanent_address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION B — EMPLOYMENT DETAILS --}}
    <div class="card p-5 space-y-4">
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

      $currDesigVal = old('designation', 'Senior Subject Teacher');
      $desigIsStandard = in_array($currDesigVal, $flatDesigs);
      $currDeptVal = old('department', 'Academics');
      $deptIsStandard = in_array($currDeptVal, $flatDepts);
      $currPayVal = old('pay_scale', 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)');
      $payIsStandard = in_array($currPayVal, $standardPayScales);
    @endphp

    <div class="card p-6 space-y-6 border border-slate-200/80 shadow-sm bg-white rounded-2xl" x-data="{
      empStatus: '{{ old('current_employment_status', 'Permanent') }}',
      firstAppt: '{{ old('first_appointment_date') }}',
      doj: '{{ old('joining_date', date('Y-m-d')) }}',
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
              EMPLOYEE ID <span class="text-slate-400 font-normal">(Auto-generated or custom format)</span>
            </label>
            <div class="relative">
              <input type="text" id="employee_code" name="employee_code" value="{{ old('employee_code', $generatedEmployeeCode ?? '') }}"
                     pattern="^[A-Za-z0-9\-_]{3,50}$"
                     class="input font-mono font-bold text-indigo-700 bg-indigo-50/50 pl-8 @error('employee_code') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. EPSS0001">
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
                <input type="number" id="experience_years" name="experience_years" value="{{ old('experience_years') }}" min="0" max="60"
                       class="input text-xs font-mono font-bold text-indigo-700 bg-indigo-50/30" placeholder="Years (e.g. 5)">
                <span class="text-[10px] text-slate-400 block mt-0.5">Total Years</span>
              </div>
              <div>
                <input type="number" id="experience_months" name="experience_months" value="{{ old('experience_months', 0) }}" min="0" max="11"
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
              <option value="Permanent" @selected(old('current_employment_status', 'Permanent')==='Permanent')>Permanent (Confirmed Regular)</option>
              <option value="Probation" @selected(old('current_employment_status')==='Probation')>Probation (Assessment Period)</option>
              <option value="Contractual" @selected(old('current_employment_status')==='Contractual')>Contractual (Fixed-Term Contract)</option>
              <option value="Temporary" @selected(old('current_employment_status')==='Temporary')>Temporary / Ad-Hoc</option>
              <option value="Part-Time" @selected(old('current_employment_status')==='Part-Time')>Part-Time / Visiting Faculty</option>
              <option value="Other" @selected(old('current_employment_status')==='Other')>Other</option>
            </select>
            @error('current_employment_status')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- PROBATION DATES (Shown when status is Probation) --}}
          <div x-show="empStatus === 'Probation'" style="display:none">
            <label class="label text-xs font-semibold" for="probation_start_date">
              Probation Start Date <span class="text-amber-600 font-bold">*</span>
            </label>
            <input type="date" id="probation_start_date" name="probation_start_date" value="{{ old('probation_start_date') }}"
                   class="input border-amber-300 bg-amber-50/20 @error('probation_start_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('probation_start_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div x-show="empStatus === 'Probation'" style="display:none">
            <label class="label text-xs font-semibold" for="probation_end_date">
              Probation Completion Date <span class="text-amber-600 font-bold">*</span>
            </label>
            <input type="date" id="probation_end_date" name="probation_end_date" value="{{ old('probation_end_date') }}"
                   class="input border-amber-300 bg-amber-50/20 @error('probation_end_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('probation_end_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- CONTRACT END DATE (Shown for Contractual / Temporary) --}}
          <div x-show="empStatus === 'Contractual' || empStatus === 'Temporary'" style="display:none">
            <label class="label text-xs font-semibold" for="contract_end_date">
              Contract End Date <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="date" id="contract_end_date" name="contract_end_date" value="{{ old('contract_end_date') }}"
                   class="input @error('contract_end_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('contract_end_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- CONFIRMATION DATE --}}
          <div>
            <label class="label text-xs font-semibold" for="confirmation_date">
              Confirmation Date <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="date" id="confirmation_date" name="confirmation_date" value="{{ old('confirmation_date') }}"
                   class="input @error('confirmation_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('confirmation_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- LAST PROMOTION DATE --}}
          <div>
            <label class="label text-xs font-semibold" for="last_promotion_date">
              Last Promotion Date <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="date" id="last_promotion_date" name="last_promotion_date" value="{{ old('last_promotion_date') }}"
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
              <input type="number" id="basic_salary" name="basic_salary" value="{{ old('basic_salary') }}" min="0" step="0.01"
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
                  <option value="{{ $mgr->id }}" @selected(old('manager_id') == $mgr->id)>
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

    {{-- SECTION B.1 — OPERATIONAL & SUPPORT DETAILS (Drivers, Cleaners, Nannies) --}}
    <div x-show="['driver', 'cleaner', 'nanny'].includes(category)" class="card p-5 space-y-4 border-2 border-amber-200 bg-amber-50/20">
      <div class="border-b border-amber-200/60 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-amber-600 text-white text-xs font-bold flex items-center justify-center">S</span>
          SECTION B.1 — OPERATIONAL &amp; CAMPUS ASSIGNMENT DETAILS
        </h3>
        <span class="text-[11px] text-amber-700 font-semibold">Duty allocation, shift timing &amp; operational parameters</span>
      </div>

      {{-- Driver Specific Fields --}}
      <div x-show="category === 'driver'" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="label text-xs font-semibold" for="license_number">
              DRIVING LICENSE NO.
            </label>
            <input type="text" id="license_number" name="license_number" value="{{ old('license_number') }}" :disabled="category !== 'driver'"
                   class="input uppercase font-mono @error('license_number') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. TN28 20180001234">
            @error('license_number')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="license_expiry">
              LICENSE VALIDITY / EXPIRY DATE
            </label>
            <input type="date" id="license_expiry" name="license_expiry" value="{{ old('license_expiry') }}" :disabled="category !== 'driver'"
                   class="input @error('license_expiry') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('license_expiry')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="assigned_vehicle">
              ASSIGNED VEHICLE / BUS ROUTE
            </label>
            <select id="assigned_vehicle" name="assigned_vehicle" :disabled="category !== 'driver'" class="select @error('assigned_vehicle') border-rose-500 ring-2 ring-rose-200 @enderror">
              <option value="">-- Select Assigned Vehicle --</option>
              @if(isset($vehicles))
                @foreach($vehicles as $veh)
                  <option value="{{ $veh->vehicle_number }}" @selected(old('assigned_vehicle') == $veh->vehicle_number)>
                    {{ $veh->vehicle_number }} ({{ ucfirst($veh->vehicle_type ?? 'Bus') }} - {{ $veh->model ?? '' }})
                  </option>
                @endforeach
              @endif
            </select>
            @error('assigned_vehicle')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      {{-- Cleaner & Nanny Specific Fields --}}
      <div x-show="category === 'cleaner' || category === 'nanny'" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label text-xs font-semibold" for="assigned_block">
              ASSIGNED CAMPUS BLOCK / ZONE / WING
            </label>
            <input type="text" id="assigned_block" name="assigned_block" value="{{ old('assigned_block') }}" :disabled="!['cleaner', 'nanny'].includes(category)"
                   class="input @error('assigned_block') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Primary Wing Block-A, Kindergarten Floor">
            @error('assigned_block')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="qualification">
              GENERAL QUALIFICATION / EDUCATION LEVEL
            </label>
            <input type="text" id="qualification" name="qualification" value="{{ old('qualification') }}" :disabled="!['cleaner', 'nanny'].includes(category)"
                   class="input @error('qualification') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 8th Pass / 10th Standard / Matriculation">
            @error('qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      {{-- Common Shift Timing for All Support Staff --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1 border-t border-amber-200/40">
        <div>
          <label class="label text-xs font-semibold" for="shift_timing">
            DUTY SHIFT / TIMINGS
          </label>
          <input type="text" id="shift_timing" name="shift_timing" value="{{ old('shift_timing', '07:30 AM - 04:30 PM') }}" :disabled="!['driver', 'cleaner', 'nanny'].includes(category)"
                 class="input @error('shift_timing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Morning Shift (07:00 AM - 03:30 PM)">
          @error('shift_timing')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    {{-- SECTION B.2 — TEACHING LEVEL ELIGIBILITY & ACADEMIC ALLOCATIONS (Section 14) --}}
    <div x-show="category === 'teaching'" class="card p-5 space-y-4 border-2 border-indigo-100 bg-gradient-to-r from-white via-indigo-50/20 to-white">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
          <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">TL</span>
          TEACHING LEVEL ELIGIBILITY &amp; ACADEMIC ALLOCATION
        </h3>
        <span class="text-[11px] text-indigo-600 font-semibold">Configurable Level &amp; Subject Mappings</span>
      </div>

      <div class="space-y-4">
        {{-- Teaching Levels Checkboxes --}}
        <div>
          <label class="label text-xs font-semibold mb-2 block">
            Approved &amp; Eligible Teaching Levels <span class="text-rose-500 font-bold">*</span>
            <span class="text-[11px] text-slate-500 font-normal ml-1">(Select one or multiple levels this teacher is qualified to handle)</span>
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if(isset($teachingLevels))
              @foreach($teachingLevels as $lvl)
                <label class="p-3 rounded-xl border border-slate-200 hover:border-indigo-400 bg-white cursor-pointer flex items-start gap-3 transition">
                  <input type="checkbox" name="teaching_levels[]" value="{{ $lvl->id }}" class="rounded text-indigo-600 focus:ring-indigo-500 mt-1"
                         @checked(is_array(old('teaching_levels')) && in_array($lvl->id, old('teaching_levels')))>
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
          @error('teaching_levels')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Workload Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div>
            <label class="label text-xs font-semibold" for="kg_experience_years">
              KG / Early Childhood Exp (Years)
            </label>
            <input type="number" id="kg_experience_years" name="kg_experience_years" value="{{ old('kg_experience_years', 0) }}" min="0" max="40"
                   class="input" placeholder="e.g. 2">
          </div>
          <div>
            <label class="label text-xs font-semibold" for="max_weekly_periods">
              Max Periods / Week (Workload)
            </label>
            <input type="number" id="max_weekly_periods" name="max_weekly_periods" value="{{ old('max_weekly_periods', 30) }}" min="1" max="50"
                   class="input font-bold text-indigo-700" placeholder="e.g. 30">
          </div>
        </div>

        {{-- Subject Expertise & Heading 4 Additions --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div>
            <label class="label text-xs font-semibold" for="preferred_teaching_level">
              Preferred Teaching Level <span class="text-slate-400 font-normal">(Kindergarten / Foundation / Secondary / Higher Secondary)</span>
            </label>
            <input type="text" id="preferred_teaching_level" name="preferred_teaching_level" value="{{ old('preferred_teaching_level') }}"
                   class="input text-xs" placeholder="e.g. Higher Secondary / Secondary">
          </div>
          <div>
            <label class="label text-xs font-semibold" for="lab_subjects">
              Practical / Lab Subjects <span class="text-slate-400 font-normal">(If applicable)</span>
            </label>
            <input type="text" id="lab_subjects" name="lab_subjects" value="{{ old('lab_subjects') }}"
                   class="input text-xs" placeholder="e.g. Physics Practical, Computer Science Lab">
          </div>
          <div>
            <label class="label text-xs font-semibold" for="secondary_subject_expertise">
              Secondary Level Subject Expertise (Classes 6–10)
            </label>
            <input type="text" id="secondary_subject_expertise" name="secondary_subject_expertise" value="{{ old('secondary_subject_expertise') }}"
                   class="input text-xs" placeholder="e.g. Mathematics, Physical Science">
          </div>
          <div>
            <label class="label text-xs font-semibold" for="higher_sec_subject_expertise">
              Higher Secondary Subject Expertise (Classes 11–12)
            </label>
            <input type="text" id="higher_sec_subject_expertise" name="higher_sec_subject_expertise" value="{{ old('higher_sec_subject_expertise') }}"
                   class="input text-xs" placeholder="e.g. Pure Mathematics, Physics (Science Group)">
          </div>
        </div>

        {{-- Initial Class, Section & Subject Allocations (Optional at onboarding) --}}
        <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-50 via-indigo-50/30 to-purple-50/20 border border-indigo-100 space-y-4 shadow-xs"
             x-data="{
               selectedClass: '{{ old('assigned_class_id', '') }}',
               selectedSection: '{{ old('assigned_section_id', '') }}',
               isClassTeacher: {{ old('is_class_teacher') ? 'true' : 'false' }},
               sectionsByClass: {
                 @if(isset($classes))
                   @foreach($classes as $c)
                     '{{ $c->id }}': [
                       @foreach($c->sections as $s)
                         { id: '{{ $s->id }}', name: '{{ $s->name }}' },
                       @endforeach
                     ],
                   @endforeach
                 @endif
               },
               get availableSections() {
                 if (!this.selectedClass) return [];
                 return this.sectionsByClass[this.selectedClass] || [];
               },
               updateClass() {
                 const exists = this.availableSections.some(s => String(s.id) === String(this.selectedSection));
                 if (!exists) {
                   this.selectedSection = '';
                 }
               }
             }">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100/70 pb-3">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
              <span class="text-xs font-bold text-slate-900 tracking-wide uppercase">Initial Academic Allocation (Optional)</span>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Teaching Staff Only</span>
            </div>
            <span class="text-[11px] text-slate-500">Can also be allocated or modified anytime from Academic Management</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="label text-xs font-semibold" for="assigned_class_id">
                Assigned Class <span class="text-slate-400 font-normal">(Optional)</span>
              </label>
              <select id="assigned_class_id" name="assigned_class_id" x-model="selectedClass" @change="updateClass()" class="select text-xs">
                <option value="">-- No Initial Class --</option>
                @if(isset($classes))
                  @foreach($classes as $cls)
                    @php
                      $cName = $cls->name;
                      $label = (str_starts_with($cName, 'Class') || in_array($cName, ['Pre-KG', 'LKG', 'UKG'])) ? $cName : 'Class ' . $cName;
                      $levelName = $cls->teachingLevel?->name ?? 'Standard';
                    @endphp
                    <option value="{{ $cls->id }}" @selected(old('assigned_class_id') == $cls->id)>
                      {{ $label }} ({{ $levelName }})
                    </option>
                  @endforeach
                @endif
              </select>
              <p class="text-[10px] text-slate-400 mt-1">Select class to filter active sections</p>
            </div>

            <div>
              <label class="label text-xs font-semibold" for="assigned_section_id">
                Assigned Section <span class="text-slate-400 font-normal">(Dynamic by Class)</span>
              </label>
              <select id="assigned_section_id" name="assigned_section_id" x-model="selectedSection" :disabled="!selectedClass" class="select text-xs">
                <option value="" x-text="selectedClass ? '-- All / Default Section --' : '-- Select Class First --'"></option>
                <template x-for="sec in availableSections" :key="sec.id">
                  <option :value="sec.id" x-text="'Section ' + sec.name" :selected="String(sec.id) === String(selectedSection)"></option>
                </template>
              </select>
              <p class="text-[10px] text-slate-400 mt-1" x-show="selectedClass && availableSections.length === 0">No active sections configured for this class</p>
              <p class="text-[10px] text-indigo-600 font-medium mt-1" x-show="selectedClass && availableSections.length > 0">
                <span x-text="availableSections.length"></span> section(s) available for selected class
              </p>
            </div>

            <div>
              <label class="label text-xs font-semibold" for="assigned_subject_id">
                Initial Handling Subject <span class="text-slate-400 font-normal">(Optional)</span>
              </label>
              <select id="assigned_subject_id" name="assigned_subject_id" class="select text-xs">
                <option value="">-- No Initial Subject --</option>
                @if(isset($subjects))
                  @foreach($subjects as $sbj)
                    <option value="{{ $sbj->id }}" @selected(old('assigned_subject_id') == $sbj->id)>
                      {{ $sbj->name }} ({{ $sbj->code }})
                    </option>
                  @endforeach
                @endif
              </select>
              <p class="text-[10px] text-slate-400 mt-1">Directly maps in Academic Year timetable</p>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <label class="flex items-start gap-2.5 cursor-pointer">
              <input type="checkbox" id="is_class_teacher" name="is_class_teacher" value="1"
                     x-model="isClassTeacher"
                     @checked(old('is_class_teacher'))
                     class="rounded text-indigo-600 focus:ring-indigo-500 mt-0.5">
              <div>
                <span class="text-xs font-bold text-slate-800">
                  Assign as Class Teacher In-Charge for the selected Class &amp; Section
                </span>
                <p class="text-[11px] text-slate-500 font-normal">
                  Grants student attendance marking, class record management, and student conduct reporting scoped to this class.
                </p>
              </div>
            </label>
            <div x-show="isClassTeacher && (!selectedClass || !selectedSection)" class="text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-lg shrink-0">
              ⚠️ Requires Class &amp; Section selection
            </div>
          </div>
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
            AADHAR NO <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no') }}" :required="category === 'teaching' || category === 'non_teaching'" maxlength="12"
                 @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 12)"
                 class="input font-mono font-bold tracking-wider @error('aadhaar_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="12 numeric digits (e.g. 123456789012)">
          <span class="text-[10px] text-slate-400 mt-1 block">12-digit Indian Aadhaar UIDAI number</span>
          @error('aadhaar_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="pan_no">
            PAN NO. <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="pan_no" name="pan_no" value="{{ old('pan_no') }}" :required="category === 'teaching' || category === 'non_teaching'" maxlength="10"
                 @input="$event.target.value = $event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10)"
                 class="input font-mono font-bold uppercase tracking-wider @error('pan_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. ABCDE1234F">
          <span class="text-[10px] text-slate-400 mt-1 block">10-character PAN (5 letters, 4 numbers, 1 letter)</span>
          @error('pan_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="epf_no">
            EPF NO. <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="text" id="epf_no" name="epf_no" value="{{ old('epf_no') }}" :required="category === 'teaching' || category === 'non_teaching'"
                 class="input font-mono @error('epf_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. TN/MAS/1234567/890">
          @error('epf_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="uan_no">
            UAN Number <span class="text-slate-400 font-normal">(EPF UAN, Optional)</span>
          </label>
          <input type="text" id="uan_no" name="uan_no" value="{{ old('uan_no') }}" maxlength="12"
                 @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 12)"
                 class="input font-mono @error('uan_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="12-digit Universal Account Number">
          @error('uan_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="esi_no">
            ESI Number <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="esi_no" name="esi_no" value="{{ old('esi_no') }}"
                 class="input font-mono @error('esi_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="17-digit ESI Insurance Number">
          @error('esi_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="professional_tax_no">
            Professional Tax Number <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="text" id="professional_tax_no" name="professional_tax_no" value="{{ old('professional_tax_no') }}"
                 class="input font-mono @error('professional_tax_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. PT-TN-2024-001">
          @error('professional_tax_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="passport_no">
            Passport Number <span class="text-slate-400 font-normal">(If applicable)</span>
          </label>
          <input type="text" id="passport_no" name="passport_no" value="{{ old('passport_no') }}"
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

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label text-xs font-semibold" for="official_email">
            EMAIL ID <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
          </label>
          <input type="email" id="official_email" name="official_email" value="{{ old('official_email') }}" :required="category === 'teaching' || category === 'non_teaching'"
                 class="input @error('official_email') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="staff@schoolerp.in">
          <span class="text-[10px] text-slate-400 mt-1 block">Official login &amp; communication address</span>
          @error('official_email')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="mobile">
            CONTACT NO. (MOBILE) <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" required maxlength="10"
                 @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 10)"
                 class="input font-mono font-bold tracking-wider @error('mobile') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10-digit primary mobile">
          <span class="text-[10px] text-slate-400 mt-1 block">10 digits starting with 6, 7, 8, or 9</span>
          @error('mobile')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="label text-xs font-semibold" for="alternate_phone">
            Alternate Telephone Number <span class="text-slate-400 font-normal">(Optional)</span>
          </label>
          <input type="tel" id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone') }}" maxlength="10"
                 @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 10)"
                 class="input font-mono @error('alternate_phone') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10-digit secondary telephone">
          <span class="text-[10px] text-slate-400 mt-1 block">Optional secondary contact</span>
          @error('alternate_phone')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">Emergency Contact Person Information</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="label text-xs font-semibold" for="name_of_contact_person">
              NAME OF THE CONTACT PERSON <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="name_of_contact_person" name="name_of_contact_person" value="{{ old('name_of_contact_person') }}" :required="category === 'teaching' || category === 'non_teaching'"
                   class="input @error('name_of_contact_person') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full name of contact person">
            @error('name_of_contact_person')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="contact_relationship">
              RELATIONSHIP <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="contact_relationship" name="contact_relationship" value="{{ old('contact_relationship') }}" :required="category === 'teaching' || category === 'non_teaching'"
                   class="input @error('contact_relationship') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Spouse, Father, Mother, Brother">
            @error('contact_relationship')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="contact_no">
              EMERGENCY CONTACT NO <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
            </label>
            <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no') }}" :required="category === 'teaching' || category === 'non_teaching'" maxlength="10"
                   @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 10)"
                   class="input font-mono font-bold tracking-wider @error('contact_no') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="10-digit mobile number">
            <span class="text-[10px] text-slate-400 mt-1 block">10-digit contact number</span>
            @error('contact_no')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div class="sm:col-span-3">
            <label class="label text-xs font-semibold" for="contact_address">
              CONTACT ADDRESS <span x-show="category === 'teaching' || category === 'non_teaching'" class="text-rose-500 font-bold">*</span>
            </label>
            <textarea id="contact_address" name="contact_address" rows="2" :required="category === 'teaching' || category === 'non_teaching'"
                      class="w-full rounded-xl border border-slate-300 p-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 min-h-[64px] resize-y @error('contact_address') border-rose-500 ring-2 ring-rose-200 @enderror"
                      placeholder="Address of the emergency contact person">{{ old('contact_address') }}</textarea>
            @error('contact_address')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>
    </div>

    {{-- SECTIONS E TO J — ACADEMIC, PROFESSIONAL & CAREER QUALIFICATIONS (Teaching & Non-Teaching Only) --}}
    <div x-show="category === 'teaching' || category === 'non_teaching'" class="space-y-6">
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
            <input type="text" id="previous_designation" name="previous_designation" value="{{ old('previous_designation') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('previous_designation') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Senior Science Teacher / Assistant Professor">
            @error('previous_designation')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="previous_school">
              NAME &amp; ADDRESS OF THE INSTITUTION <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="previous_school" name="previous_school" value="{{ old('previous_school') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('previous_school') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. St. Joseph Higher Secondary School, Salem">
            @error('previous_school')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="previous_from_date">
              FROM <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="date" id="previous_from_date" name="previous_from_date" value="{{ old('previous_from_date') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('previous_from_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('previous_from_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="previous_to_date">
              TO <span class="text-rose-500 font-bold">*</span> <span class="text-slate-400 font-normal">(Cannot be earlier than FROM)</span>
            </label>
            <input type="date" id="previous_to_date" name="previous_to_date" value="{{ old('previous_to_date') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
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
            <input type="text" id="academic_qualification" name="academic_qualification" value="{{ old('academic_qualification') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('academic_qualification') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. M.Sc. Physics">
            @error('academic_qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="academic_university">
              UNIVERSITY <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="academic_university" name="academic_university" value="{{ old('academic_university') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('academic_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. University of Madras">
            @error('academic_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="academic_medium">
              MEDIUM <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="academic_medium" name="academic_medium" value="{{ old('academic_medium', 'English') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('academic_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
            @error('academic_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="academic_institution">
              NAME OF THE INSTITUTION <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="academic_institution" name="academic_institution" value="{{ old('academic_institution') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('academic_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Loyola College, Chennai">
            @error('academic_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="academic_percentage">
              %MARKS <span class="text-rose-500 font-bold">*</span> <span class="text-slate-400 font-normal">(0 - 100)</span>
            </label>
            <input type="number" id="academic_percentage" name="academic_percentage" value="{{ old('academic_percentage') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)" min="0" max="100" step="0.01"
                   class="input font-mono @error('academic_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 78.50">
            @error('academic_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="academic_year_of_passing">
              YEAR OF PASSING <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="number" id="academic_year_of_passing" name="academic_year_of_passing" value="{{ old('academic_year_of_passing') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)" min="1960" max="{{ date('Y') + 1 }}"
                   class="input font-mono @error('academic_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 2018">
            @error('academic_year_of_passing')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="major_subject">
              Major Subject / Specialization <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" id="major_subject" name="major_subject" value="{{ old('major_subject') }}"
                   class="input @error('major_subject') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Mathematics / Physics">
            @error('major_subject')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="minor_subject">
              Minor Subject <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" id="minor_subject" name="minor_subject" value="{{ old('minor_subject') }}"
                   class="input @error('minor_subject') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Statistics / Chemistry">
            @error('minor_subject')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div class="sm:col-span-3">
            <label class="label text-xs font-semibold" for="special_skills">
              Special Skills &amp; Extracurricular <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" id="special_skills" name="special_skills" value="{{ old('special_skills') }}"
                   class="input @error('special_skills') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Debate Coach, Robotics, Vedic Math, Sports Mentorship">
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
            <input type="text" id="professional_qualification" name="professional_qualification" value="{{ old('professional_qualification') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('professional_qualification') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. B.Ed. Physical Science">
            @error('professional_qualification')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="professional_university">
              UNIVERSITY <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="professional_university" name="professional_university" value="{{ old('professional_university') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('professional_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. TNTEU">
            @error('professional_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="professional_medium">
              MEDIUM <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="professional_medium" name="professional_medium" value="{{ old('professional_medium', 'English') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('professional_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
            @error('professional_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="professional_institution">
              NAME OF INSTITUTION <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="text" id="professional_institution" name="professional_institution" value="{{ old('professional_institution') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('professional_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Institute of Teacher Education">
            @error('professional_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="professional_percentage">
              %MARKS <span class="text-rose-500 font-bold">*</span> <span class="text-slate-400 font-normal">(0 - 100)</span>
            </label>
            <input type="number" id="professional_percentage" name="professional_percentage" value="{{ old('professional_percentage') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)" min="0" max="100" step="0.01"
                   class="input font-mono @error('professional_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 82.00">
            @error('professional_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="professional_year_of_passing">
              YEAR OF PASSING <span class="text-rose-500 font-bold">*</span>
            </label>
            <input type="number" id="professional_year_of_passing" name="professional_year_of_passing" value="{{ old('professional_year_of_passing') }}"
                   :required="category === 'teaching' || category === 'non_teaching'" :disabled="['driver', 'cleaner', 'nanny'].includes(category)" min="1960" max="{{ date('Y') + 1 }}"
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
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_1_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. PGDCA / Diploma in School Mgmt">
            @error('other_qual_1_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_1_university">
              UNIVERSITY
            </label>
            <input type="text" id="other_qual_1_university" name="other_qual_1_university" value="{{ old('other_qual_1_university') }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_1_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Board / University">
            @error('other_qual_1_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_1_medium">
              MEDIUM
            </label>
            <input type="text" id="other_qual_1_medium" name="other_qual_1_medium" value="{{ old('other_qual_1_medium') }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_1_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
            @error('other_qual_1_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_1_institution">
              NAME OF INSTITUTION
            </label>
            <input type="text" id="other_qual_1_institution" name="other_qual_1_institution" value="{{ old('other_qual_1_institution') }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_1_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Institution name">
            @error('other_qual_1_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_1_percentage">
              %MARKS
            </label>
            <input type="number" id="other_qual_1_percentage" name="other_qual_1_percentage" value="{{ old('other_qual_1_percentage') }}" min="0" max="100" step="0.01"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input font-mono @error('other_qual_1_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 75.00">
            @error('other_qual_1_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_1_year_of_passing">
              YEAR OF PASSING
            </label>
            <input type="number" id="other_qual_1_year_of_passing" name="other_qual_1_year_of_passing" value="{{ old('other_qual_1_year_of_passing') }}" min="1960" max="{{ date('Y') + 1 }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
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
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_2_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. CTET Paper I &amp; II">
            @error('other_qual_2_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_2_university">
              UNIVERSITY
            </label>
            <input type="text" id="other_qual_2_university" name="other_qual_2_university" value="{{ old('other_qual_2_university') }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_2_university') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Board / Examination Agency">
            @error('other_qual_2_university')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_2_medium">
              MEDIUM
            </label>
            <input type="text" id="other_qual_2_medium" name="other_qual_2_medium" value="{{ old('other_qual_2_medium') }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_2_medium') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. English">
            @error('other_qual_2_medium')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_2_institution">
              NAME OF INSTITUTION
            </label>
            <input type="text" id="other_qual_2_institution" name="other_qual_2_institution" value="{{ old('other_qual_2_institution') }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input @error('other_qual_2_institution') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Institution / Exam Center">
            @error('other_qual_2_institution')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_2_percentage">
              %MARKS
            </label>
            <input type="number" id="other_qual_2_percentage" name="other_qual_2_percentage" value="{{ old('other_qual_2_percentage') }}" min="0" max="100" step="0.01"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input font-mono @error('other_qual_2_percentage') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 70.00">
            @error('other_qual_2_percentage')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="other_qual_2_year_of_passing">
              YEAR OF PASSING
            </label>
            <input type="number" id="other_qual_2_year_of_passing" name="other_qual_2_year_of_passing" value="{{ old('other_qual_2_year_of_passing') }}" min="1960" max="{{ date('Y') + 1 }}"
                   :disabled="['driver', 'cleaner', 'nanny'].includes(category)"
                   class="input font-mono @error('other_qual_2_year_of_passing') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 2021">
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
              Optional • Non-blocking for New Onboarding
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
                      placeholder="e.g. Completed PGDCA from Bharathiar University in 2023; Certified in Google for Education">{{ old('additional_qual_after_recruitment', 'None / Not Applicable') }}</textarea>
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
                      placeholder="e.g. Attended 5-day CBSE Capacity Building Programme on Experiential Learning; DIKSHA NISHTHA 2.0 module certified">{{ old('training_attended_before_recruitment', 'None / Not Applicable') }}</textarea>
            @error('training_attended_before_recruitment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
        </div>
      </div>

      {{-- SECTION K — SALARY INCREMENTS & NOMINEE BENEFICIARY DETAILS (Heading 7 & 11) --}}
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
            <input type="number" id="annual_increment" name="annual_increment" value="{{ old('annual_increment') }}" min="0" step="0.01"
                   class="input font-mono @error('annual_increment') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. 1500">
            @error('annual_increment')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="increment_effective_date">
              Increment Effective Date <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="date" id="increment_effective_date" name="increment_effective_date" value="{{ old('increment_effective_date') }}"
                   class="input @error('increment_effective_date') border-rose-500 ring-2 ring-rose-200 @enderror">
            @error('increment_effective_date')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="nominee_share">
              Nominee Contribution Share (%) <span class="text-slate-400 font-normal">(Default 100%)</span>
            </label>
            <input type="number" id="nominee_share" name="nominee_share" value="{{ old('nominee_share', 100) }}" min="1" max="100"
                   class="input font-mono @error('nominee_share') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="100">
            @error('nominee_share')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="nominee_name">
              Nominee Full Name <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" id="nominee_name" name="nominee_name" value="{{ old('nominee_name') }}"
                   class="input @error('nominee_name') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="Full name of beneficiary">
            @error('nominee_name')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>

          <div>
            <label class="label text-xs font-semibold" for="nominee_relationship">
              Nominee Relationship <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" id="nominee_relationship" name="nominee_relationship" value="{{ old('nominee_relationship') }}"
                   class="input @error('nominee_relationship') border-rose-500 ring-2 ring-rose-200 @enderror" placeholder="e.g. Spouse, Son, Mother">
            @error('nominee_relationship')<p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>@enderror
          </div>
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
