@extends('layouts.app')
@section('title', 'Log Visitor Registration')
@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="visitorForm({{ json_encode($prefillCategory ?? 'parent') }})">

  {{-- Breadcrumb & Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
        <a href="{{ route('gate.index') }}" class="hover:text-indigo-600 transition">Gate Management</a>
        <span>/</span>
        <span class="text-slate-800">New Registration</span>
      </div>
      <h1 class="page-title text-xl font-bold text-slate-900 tracking-tight">Visitor Entry Registration</h1>
      <p class="page-subtitle text-xs text-slate-500">Record visitor details, auto-fetch parent details via student search, and generate instant gate pass</p>
    </div>

    <a href="{{ route('gate.index') }}" class="btn-secondary text-xs sm:text-sm font-semibold self-start sm:self-auto flex items-center gap-1.5">
      ← Back to Gate Log
    </a>
  </div>

  @if($errors->any())
  <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 text-sm shadow-xs">
    <div class="flex items-start gap-2.5">
      <div class="w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center flex-shrink-0 text-xs font-bold">!</div>
      <div class="space-y-1">
        <p class="font-bold">Please resolve the following before proceeding:</p>
        <ul class="list-disc pl-5 space-y-0.5 text-xs">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('gate.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    {{-- Category Selection Tabs --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs space-y-3">
      <div class="flex items-center justify-between">
        <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Select Visitor Category <span class="text-red-500">*</span></label>
        <span class="text-[11px] text-slate-400 font-medium">Auto-populates core details based on category &amp; search</span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
        
        {{-- Category 1: Parent --}}
        <label class="cursor-pointer">
          <input type="radio" name="category" value="parent" x-model="category" @change="onCategoryChange('parent')" class="sr-only">
          <div :class="category === 'parent' ? 'border-emerald-500 bg-emerald-50/70 ring-2 ring-emerald-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-slate-50/40'"
               class="p-3 rounded-xl border transition flex flex-col items-center text-center gap-1.5 h-full">
            <div :class="category === 'parent' ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-lg flex items-center justify-center transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <span class="text-xs font-bold text-slate-800">Parent / Guardian</span>
          </div>
        </label>

        {{-- Category 2: Admission Enquiry --}}
        <label class="cursor-pointer">
          <input type="radio" name="category" value="admission_enquiry" x-model="category" @change="onCategoryChange('admission_enquiry')" class="sr-only">
          <div :class="category === 'admission_enquiry' ? 'border-blue-500 bg-blue-50/70 ring-2 ring-blue-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-slate-50/40'"
               class="p-3 rounded-xl border transition flex flex-col items-center text-center gap-1.5 h-full">
            <div :class="category === 'admission_enquiry' ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-lg flex items-center justify-center transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <span class="text-xs font-bold text-slate-800">Admission Enquiry</span>
          </div>
        </label>

        {{-- Category 3: Vendor --}}
        <label class="cursor-pointer">
          <input type="radio" name="category" value="vendor" x-model="category" @change="onCategoryChange('vendor')" class="sr-only">
          <div :class="category === 'vendor' ? 'border-amber-500 bg-amber-50/70 ring-2 ring-amber-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-slate-50/40'"
               class="p-3 rounded-xl border transition flex flex-col items-center text-center gap-1.5 h-full">
            <div :class="category === 'vendor' ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-lg flex items-center justify-center transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <span class="text-xs font-bold text-slate-800">Vendor / Maint.</span>
          </div>
        </label>

        {{-- Category 4: Interview --}}
        <label class="cursor-pointer">
          <input type="radio" name="category" value="interview" x-model="category" @change="onCategoryChange('interview')" class="sr-only">
          <div :class="category === 'interview' ? 'border-purple-500 bg-purple-50/70 ring-2 ring-purple-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-slate-50/40'"
               class="p-3 rounded-xl border transition flex flex-col items-center text-center gap-1.5 h-full">
            <div :class="category === 'interview' ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-lg flex items-center justify-center transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-xs font-bold text-slate-800">Staff Interview</span>
          </div>
        </label>

        {{-- Category 5: Other --}}
        <label class="cursor-pointer">
          <input type="radio" name="category" value="other" x-model="category" @change="onCategoryChange('other')" class="sr-only">
          <div :class="category === 'other' ? 'border-slate-700 bg-slate-100 ring-2 ring-slate-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-slate-50/40'"
               class="p-3 rounded-xl border transition flex flex-col items-center text-center gap-1.5 h-full">
            <div :class="category === 'other' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-lg flex items-center justify-center transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </div>
            <span class="text-xs font-bold text-slate-800">Official Guest / Other</span>
          </div>
        </label>

      </div>
    </div>

    {{-- Dynamic Sub-Module Panel (Category-Specific Fields) --}}
    
    {{-- 1. Parent / Guardian Dynamic Sub-Module --}}
    <div x-show="category === 'parent'" class="bg-emerald-50/60 rounded-2xl border border-emerald-200 p-4 sm:p-5 space-y-4 transition">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
          <h2 class="text-sm font-bold text-emerald-950">Student Relationship &amp; Parent Verification</h2>
        </div>
        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Auto-fills Core Profile</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        
        {{-- Student Search Autocomplete --}}
        <div class="relative">
          <label class="label text-xs">Search Student (Name / Admission No / Roll No) <span class="text-emerald-700 font-semibold">*</span></label>
          <input type="text" x-model="studentQuery" @input.debounce.250ms="searchStudents" placeholder="Type student name, roll no, or admission no..." class="input w-full bg-white text-xs sm:text-sm font-medium">
          <input type="hidden" name="student_id" :value="selectedStudentId">

          {{-- Autocomplete Dropdown --}}
          <div x-show="studentResults.length > 0" @click.away="studentResults = []" class="absolute z-30 left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-slate-200 max-h-60 overflow-y-auto divide-y divide-slate-100">
            <template x-for="st in studentResults" :key="st.id">
              <div @click="selectStudent(st)" class="p-3 hover:bg-emerald-50 cursor-pointer transition flex items-center justify-between text-xs">
                <div>
                  <div class="flex items-center gap-2">
                    <p class="font-bold text-slate-900" x-text="st.name"></p>
                    <span class="px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 font-mono text-[10px]" x-text="'Adm: ' + st.admission_no"></span>
                    <span x-show="st.roll_number" class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-mono text-[10px]" x-text="'Roll: ' + st.roll_number"></span>
                  </div>
                  <p class="text-[11px] text-slate-500 mt-0.5" x-text="'Father: ' + (st.father_name || '—') + ' (' + (st.father_mobile || 'No phone') + ')'"></p>
                  <p x-show="st.mother_name" class="text-[10px] text-slate-400" x-text="'Mother: ' + st.mother_name + ' • Guardian: ' + (st.guardian_name || '—')"></p>
                </div>
                <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-lg">Select &amp; Autofill</span>
              </div>
            </template>
          </div>

          {{-- Selected Student Summary Badge --}}
          <div x-show="selectedStudentName" class="mt-2 p-3 bg-emerald-100/80 border border-emerald-300 rounded-xl flex items-center justify-between text-xs shadow-xs">
            <div class="space-y-0.5">
              <span class="font-bold text-emerald-950 text-xs" x-text="'✓ Linked Student: ' + selectedStudentName"></span>
              <span class="text-[11px] text-emerald-800 block" x-text="'Admission No: ' + selectedStudentAdm + (selectedStudentRoll ? ' • Roll No: ' + selectedStudentRoll : '')"></span>
            </div>
            <button type="button" @click="clearSelectedStudent" class="text-xs font-bold text-red-600 hover:text-red-800 bg-white px-2 py-1 rounded-md border border-red-200 shadow-2xs">Change</button>
          </div>
        </div>

        {{-- Relationship --}}
        <div>
          <label class="label text-xs">Relationship to Student <span class="text-emerald-700 font-semibold">*</span></label>
          <select name="relationship_to_student" x-model="relationship" @change="onRelationshipChange" class="select w-full bg-white text-xs sm:text-sm font-semibold text-slate-800">
            <option value="Father">Father</option>
            <option value="Mother">Mother</option>
            <option value="Guardian">Guardian</option>
            <option value="Grandparent">Grandparent</option>
            <option value="Sibling">Sibling (Brother / Sister)</option>
            <option value="Uncle / Aunt">Uncle / Aunt</option>
            <option value="Other Relative">Other Relative</option>
          </select>
        </div>
      </div>
    </div>

    {{-- 2. Admission Enquiry Dynamic Sub-Module --}}
    <div x-show="category === 'admission_enquiry'" class="bg-blue-50/60 rounded-2xl border border-blue-200 p-4 sm:p-5 space-y-4 transition">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
        <h2 class="text-sm font-bold text-blue-950">New Admission Enquiry Pipeline Routing</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs">Prospective Child Name <span class="text-blue-700 font-semibold">*</span></label>
          <input type="text" name="child_name" placeholder="e.g. Master Rohan" class="input w-full bg-white text-xs sm:text-sm">
        </div>

        <div>
          <label class="label text-xs">Grade / Class Applying For</label>
          <select name="grade_applying_for" class="select w-full bg-white text-xs sm:text-sm">
            <option value="">Select Grade</option>
            <option>Pre-KG</option><option>LKG</option><option>UKG</option>
            <option>Grade 1</option><option>Grade 2</option><option>Grade 3</option>
            <option>Grade 4</option><option>Grade 5</option><option>Grade 6</option>
            <option>Grade 7</option><option>Grade 8</option><option>Grade 9</option>
            <option>Grade 10</option><option>Grade 11</option><option>Grade 12</option>
          </select>
        </div>

        <div>
          <label class="label text-xs">Source / Reference</label>
          <select name="enquiry_source" class="select w-full bg-white text-xs sm:text-sm">
            <option value="Walk-in / Direct Visit">Walk-in / Direct Visit</option>
            <option value="Word of Mouth / Referral">Word of Mouth / Referral</option>
            <option value="Social Media (Instagram/Facebook)">Social Media</option>
            <option value="Website">Website</option>
            <option value="Newspaper / Banner Ad">Newspaper / Banner Ad</option>
            <option value="Existing Parent Reference">Existing Parent Reference</option>
          </select>
        </div>
      </div>

      <label class="flex items-center gap-2 text-xs font-semibold text-blue-900 cursor-pointer pt-1">
        <input type="checkbox" name="create_admission_lead" value="1" checked class="rounded text-blue-600 focus:ring-blue-500">
        <span>Automatically create a New Admission Lead &amp; schedule CRM Follow-up</span>
      </label>
    </div>

    {{-- 3. Vendor / Maintenance Dynamic Sub-Module --}}
    <div x-show="category === 'vendor'" class="bg-amber-50/60 rounded-2xl border border-amber-200 p-4 sm:p-5 space-y-4 transition">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
        <h2 class="text-sm font-bold text-amber-950">Vendor &amp; Facilities Maintenance Details</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label text-xs">Vendor / Agency Name <span class="text-amber-700 font-semibold">*</span></label>
          <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="e.g. Apex Electricals Pvt Ltd" class="input w-full bg-white text-xs sm:text-sm font-semibold">
        </div>

        <div>
          <label class="label text-xs">Maintenance Details</label>
          <input type="text" name="maintenance_details" value="{{ old('maintenance_details') }}" placeholder="e.g. Electrical wiring check / Equipment repair" class="input w-full bg-white text-xs sm:text-sm">
        </div>

        <div>
          <label class="label text-xs">Work Order Number / Service PO</label>
          <input type="text" name="work_order_number" value="{{ old('work_order_number') }}" placeholder="e.g. WO-2026-084" class="input w-full bg-white text-xs sm:text-sm font-mono">
        </div>

        <div class="sm:col-span-3">
          <label class="label text-xs">Tools &amp; Equipment Carried In</label>
          <input type="text" name="items_carried" value="{{ old('items_carried') }}" placeholder="e.g. 1x Toolbox, 1x Drill Machine (Bosch), 1x Multimeter" class="input w-full bg-white text-xs sm:text-sm">
        </div>
      </div>
    </div>

    {{-- 4. Staff Interview Dynamic Sub-Module --}}
    <div x-show="category === 'interview'" class="bg-purple-50/60 rounded-2xl border border-purple-200 p-4 sm:p-5 space-y-4 transition">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
        <h2 class="text-sm font-bold text-purple-950">HR / Recruitment Candidate Details</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label text-xs">Job Role Applied For <span class="text-purple-700 font-semibold">*</span></label>
          <input type="text" name="job_role_applied" placeholder="e.g. TGT Mathematics / Lab Assistant / Accountant" class="input w-full bg-white text-xs sm:text-sm">
        </div>

        <div>
          <label class="label text-xs">Candidate Reference / Application ID</label>
          <input type="text" name="candidate_ref_number" placeholder="e.g. HR-REC-2026-042" class="input w-full bg-white text-xs sm:text-sm font-mono">
        </div>
      </div>
    </div>

    {{-- Core Primary Visitor Details (Auto-Populated & Live Editable) --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-5">
      <div class="flex items-center justify-between border-b border-slate-100 pb-2">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">1. Core Visitor Profile</h2>
        <span class="text-xs font-semibold text-indigo-600" x-show="selectedStudentName">⚡ Auto-filled from Student Records</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        
        {{-- Full Name --}}
        <div>
          <label class="label text-xs">Visitor Full Name <span class="text-red-500">*</span></label>
          <input type="text" name="visitor_name" x-model="visitorName" required autofocus placeholder="e.g. Rajesh Kumar Sharma" class="input w-full text-xs sm:text-sm font-bold text-slate-900 bg-slate-50/50 focus:bg-white">
        </div>

        {{-- Phone Number --}}
        <div>
          <label class="label text-xs">Mobile Number <span class="text-red-500">*</span></label>
          <input type="tel" name="visitor_phone" x-model="visitorPhone" required placeholder="e.g. 9876543210" class="input w-full text-xs sm:text-sm font-mono font-bold text-slate-900 bg-slate-50/50 focus:bg-white">
        </div>

        {{-- Email --}}
        <div>
          <label class="label text-xs">Email Address (Optional)</label>
          <input type="email" name="visitor_email" x-model="visitorEmail" placeholder="e.g. rajesh@example.com" class="input w-full text-xs sm:text-sm bg-slate-50/50 focus:bg-white">
        </div>

        {{-- ID Proof Type --}}
        <div>
          <label class="label text-xs">Government ID Proof Type</label>
          <select name="visitor_id_type" class="select w-full text-xs sm:text-sm">
            <option value="">Select ID Type</option>
            <option value="Aadhaar Card">Aadhaar Card</option>
            <option value="Driving Licence">Driving Licence</option>
            <option value="Voter ID">Voter ID</option>
            <option value="PAN Card">PAN Card</option>
            <option value="Passport">Passport</option>
            <option value="Official Govt ID">Official Govt ID</option>
            <option value="Company / Staff ID">Company / Staff ID</option>
          </select>
        </div>

        {{-- ID Proof Number --}}
        <div>
          <label class="label text-xs">ID Proof Number</label>
          <input type="text" name="visitor_id_number" placeholder="e.g. XXXX-XXXX-1234 / DL Number" class="input w-full text-xs sm:text-sm font-mono">
        </div>

        {{-- ID Document Upload --}}
        <div>
          <label class="label text-xs">ID Document Scan / File</label>
          <input type="file" name="id_proof_file" accept=".jpg,.jpeg,.png,.pdf" class="file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 text-xs w-full">
        </div>

      </div>
    </div>

    {{-- Visit Purpose & Host Assignment --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">2. Visit Details &amp; Host Lookup</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        
        {{-- Purpose --}}
        <div class="sm:col-span-2 lg:col-span-3">
          <label class="label text-xs">Purpose of Visit <span class="text-red-500">*</span></label>
          <input type="text" name="purpose" required placeholder="e.g. Meeting Class Teacher regarding academic progress / Campus Tour" class="input w-full text-xs sm:text-sm">
        </div>

        {{-- Host Staff Lookup --}}
        <div>
          <label class="label text-xs">Person to Meet (Host Staff)</label>
          <select name="host_employee_id" @change="onHostChange($event)" class="select w-full text-xs sm:text-sm font-medium">
            <option value="">Select Staff / Teacher</option>
            @foreach($staff as $s)
            <option value="{{ $s->id }}" data-dept="{{ $s->department }}">
              {{ $s->first_name }} {{ $s->last_name }} ({{ $s->designation ?? 'Staff' }})
            </option>
            @endforeach
          </select>
        </div>

        {{-- Total Visitors (Self + Accompanying) --}}
        <div>
          <label class="label text-xs">Total Persons (Self + Accompanying)</label>
          <input type="number" name="visitor_count" value="1" min="1" max="50" class="input w-full text-xs sm:text-sm font-bold">
        </div>

        {{-- Vehicle Type --}}
        <div>
          <label class="label text-xs">Vehicle Type</label>
          <select name="vehicle_type" class="select w-full text-xs sm:text-sm">
            <option value="none">No Vehicle / Pedestrian</option>
            <option value="two_wheeler">Two-Wheeler (Bike / Scooter)</option>
            <option value="four_wheeler">Four-Wheeler (Car / SUV)</option>
            <option value="auto_taxi">Auto / Cab Taxi</option>
            <option value="truck_commercial">Commercial Truck / Van</option>
          </select>
        </div>

        {{-- Vehicle Registration Plate --}}
        <div>
          <label class="label text-xs">Vehicle Registration Plate</label>
          <input type="text" name="vehicle_number" placeholder="e.g. TN-33-AB-1234" class="input w-full text-xs sm:text-sm uppercase font-mono">
        </div>

        {{-- Remarks --}}
        <div class="sm:col-span-2 lg:col-span-3">
          <label class="label text-xs">Gatekeeper Remarks / Security Notes</label>
          <textarea name="remarks" rows="2" placeholder="Any additional notes or observations..." class="input w-full text-xs sm:text-sm"></textarea>
        </div>

      </div>
    </div>



    {{-- Form Submission Action Bar --}}
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-4 sm:p-5 text-white shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
        <h3 class="font-bold text-sm tracking-wide">Direct Check-In &amp; Gate Pass</h3>
      </div>

      <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer select-none">
          <input type="checkbox" name="auto_print" value="1" checked class="rounded border-slate-500 text-emerald-500 focus:ring-emerald-400">
          <span>Auto-print Pass</span>
        </label>

        <button type="submit" name="action_type" value="direct_checkin" class="btn-primary text-xs sm:text-sm font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-md shadow-emerald-500/20 px-5 py-2.5">
          ✓ Check In &amp; Generate Pass
        </button>
      </div>
    </div>

  </form>

</div>

@push('scripts')
<script>
function visitorForm(initialCategory) {
  return {
    category: initialCategory || 'parent',
    visitorName: '',
    visitorPhone: '',
    visitorEmail: '',
    relationship: 'Father',
    vendorCompany: '',
    currentStudent: null,
    selectedDepartment: '',
    studentQuery: '',
    studentResults: [],
    selectedStudentId: '',
    selectedStudentName: '',
    selectedStudentAdm: '',
    selectedStudentRoll: '',

    onCategoryChange(cat) {
      this.category = cat;
    },

    onHostChange(event) {
      const selected = event.target.options[event.target.selectedIndex];
      const dept = selected.getAttribute('data-dept');
      if (dept) {
        this.selectedDepartment = dept;
      }
    },

    onRelationshipChange() {
      if (this.currentStudent) {
        this.applyStudentDetails();
      }
    },

    async searchStudents() {
      if (this.studentQuery.length < 1) {
        this.studentResults = [];
        return;
      }
      try {
        const res = await fetch(`{{ route('gate.lookup.students') }}?q=${encodeURIComponent(this.studentQuery)}`);
        this.studentResults = await res.json();
      } catch (e) {
        console.error('Student lookup failed', e);
      }
    },

    selectStudent(student) {
      this.currentStudent = student;
      this.selectedStudentId = student.id;
      this.selectedStudentName = student.name;
      this.selectedStudentAdm = student.admission_no;
      this.selectedStudentRoll = student.roll_number || '';
      this.studentResults = [];
      this.studentQuery = '';

      // Auto-populate visitor profile details based on relationship
      this.applyStudentDetails();
    },

    applyStudentDetails() {
      if (!this.currentStudent) return;
      const st = this.currentStudent;
      const rel = (this.relationship || 'Father').toLowerCase();

      if (rel.includes('father')) {
        this.visitorName = st.father_name || st.name + "'s Father";
        this.visitorPhone = st.father_mobile || st.mobile || '';
        this.visitorEmail = st.father_email || '';
      } else if (rel.includes('mother')) {
        this.visitorName = st.mother_name || st.name + "'s Mother";
        this.visitorPhone = st.mother_mobile || st.mobile || '';
        this.visitorEmail = st.mother_email || '';
      } else if (rel.includes('guardian')) {
        this.visitorName = st.guardian_name || st.father_name || '';
        this.visitorPhone = st.guardian_mobile || st.father_mobile || st.mobile || '';
      } else {
        // Fallback for other relatives
        if (!this.visitorName) {
          this.visitorName = st.father_name || st.guardian_name || '';
        }
        if (!this.visitorPhone) {
          this.visitorPhone = st.father_mobile || st.mobile || '';
        }
      }
    },

    clearSelectedStudent() {
      this.currentStudent = null;
      this.selectedStudentId = '';
      this.selectedStudentName = '';
      this.selectedStudentAdm = '';
      this.selectedStudentRoll = '';
    }
  }
}
</script>
@endpush
@endsection
