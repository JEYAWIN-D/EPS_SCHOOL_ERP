@extends('layouts.app')

@section('title', 'Admit Student')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="admitForm()" x-init="init()">

  <nav class="text-sm text-slate-400 flex items-center gap-1.5 mb-1">
    <a href="{{ route('students.index') }}" class="hover:text-slate-600">Students</a>
    <span>/</span>
    <span class="text-slate-600">Admit New Student</span>
  </nav>
  <div class="flex items-center gap-4">
    <a href="{{ route('students.index') }}" class="btn-icon">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    </a>
    <div>
      <h1 class="page-title">Admit New Student</h1>
      <p class="page-subtitle">{{ $academicYear?->name }}</p>
    </div>
  </div>

  <form method="POST" action="{{ route('students.store') }}" class="space-y-6">
    @csrf

    {{-- Personal Info --}}
    <div class="card">
      <h3 class="font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100">Personal Information</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">First Name <span class="text-red-500">*</span></label>
          <input type="text" name="first_name" value="{{ old('first_name') }}" class="input @error('first_name') input-error @enderror">
          @error('first_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="label">Middle Name</label>
          <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="input">
        </div>
        <div>
          <label class="label">Last Name <span class="text-red-500">*</span></label>
          <input type="text" name="last_name" value="{{ old('last_name') }}" class="input @error('last_name') input-error @enderror">
          @error('last_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="label">Date of Birth <span class="text-red-500">*</span></label>
          <input type="date" name="dob" value="{{ old('dob') }}" class="input @error('dob') input-error @enderror">
        </div>
        <div>
          <label class="label">Gender <span class="text-red-500">*</span></label>
          <select name="gender" class="select @error('gender') input-error @enderror">
            <option value="">Select</option>
            <option value="male"   @selected(old('gender') === 'male')>Male</option>
            <option value="female" @selected(old('gender') === 'female')>Female</option>
            <option value="other"  @selected(old('gender') === 'other')>Other</option>
          </select>
        </div>
        <div>
          <label class="label">Student Type <span class="text-red-500">*</span></label>
          <select name="student_type" class="select">
            <option value="day_scholar" @selected(old('student_type','day_scholar') === 'day_scholar')>Day Scholar</option>
            <option value="hosteller"   @selected(old('student_type') === 'hosteller')>Hosteller</option>
            <option value="day_boarder" @selected(old('student_type') === 'day_boarder')>Day Boarder</option>
          </select>
        </div>
        <div>
          <label class="label">Blood Group</label>
          <select name="blood_group" class="select">
            <option value="">Select</option>
            @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
              <option value="{{ $bg }}" @selected(old('blood_group') === $bg)>{{ $bg }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Category</label>
          <select name="category" class="select">
            <option value="">Select</option>
            <option value="general"  @selected(old('category') === 'general')>General</option>
            <option value="obc"      @selected(old('category') === 'obc')>OBC</option>
            <option value="sc"       @selected(old('category') === 'sc')>SC</option>
            <option value="st"       @selected(old('category') === 'st')>ST</option>
            <option value="ews"      @selected(old('category') === 'ews')>EWS</option>
            <option value="minority" @selected(old('category') === 'minority')>Minority</option>
          </select>
        </div>
        <div>
          <label class="label">Religion</label>
          <input type="text" name="religion" value="{{ old('religion') }}" class="input" placeholder="e.g. Hindu">
        </div>
        <div>
          <label class="label">Mother Tongue</label>
          <input type="text" name="mother_tongue" value="{{ old('mother_tongue') }}" class="input">
        </div>
        <div>
          <label class="label">Aadhaar Number</label>
          <input type="text" name="aadhaar_number" value="{{ old('aadhaar_number') }}" class="input" placeholder="12-digit">
        </div>
        <div>
          <label class="label">Mobile</label>
          <input type="tel" name="mobile" value="{{ old('mobile') }}" class="input">
        </div>
        <div>
          <label class="label">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" class="input">
        </div>
      </div>

      {{-- Address --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="label">Residential Address</label>
          <textarea name="residential_address" rows="2" class="input" placeholder="Current residential address" x-model="residentialAddress">{{ old('residential_address') }}</textarea>
        </div>
        <div>
          <label class="label flex items-center justify-between">
            <span>Permanent Address</span>
            <button type="button" @click="copyAddress()" class="text-xs text-blue-600 hover:underline font-normal">Same as residential</button>
          </label>
          <textarea name="permanent_address" rows="2" class="input" x-model="permanentAddress" placeholder="Permanent address">{{ old('permanent_address') }}</textarea>
        </div>
        <div>
          <label class="label">Pincode</label>
          <input type="text" name="pincode" value="{{ old('pincode') }}" class="input w-36" placeholder="6-digit">
        </div>
      </div>

      {{-- Disability --}}
      <div class="mt-4 space-y-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="is_disabled" value="1" x-model="isDisabled" @change="" class="rounded" @checked(old('is_disabled'))>
          <span class="text-sm text-slate-700">Person with Disability (PwD)</span>
        </label>
        <div x-show="isDisabled" x-transition>
          <label class="label">Disability Description</label>
          <input type="text" name="disability_description" value="{{ old('disability_description') }}" class="input" placeholder="Brief description">
        </div>
      </div>
    </div>

    {{-- Parent Info --}}
    <div class="card">
      <h3 class="font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100">Parent / Guardian Information</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Father's Name <span class="text-red-500">*</span></label>
          <input type="text" name="father_name" value="{{ old('father_name') }}" class="input @error('father_name') input-error @enderror">
        </div>
        <div>
          <label class="label">Father's Mobile</label>
          <input type="tel" name="father_mobile" value="{{ old('father_mobile') }}" class="input">
        </div>
        <div>
          <label class="label">Father's Occupation</label>
          <input type="text" name="father_occupation" value="{{ old('father_occupation') }}" class="input">
        </div>
        <div>
          <label class="label">Father's Email</label>
          <input type="email" name="father_email" value="{{ old('father_email') }}" class="input">
        </div>
        <div>
          <label class="label">Mother's Name <span class="text-red-500">*</span></label>
          <input type="text" name="mother_name" value="{{ old('mother_name') }}" class="input @error('mother_name') input-error @enderror">
        </div>
        <div>
          <label class="label">Mother's Mobile</label>
          <input type="tel" name="mother_mobile" value="{{ old('mother_mobile') }}" class="input">
        </div>
        <div>
          <label class="label">Mother's Occupation</label>
          <input type="text" name="mother_occupation" value="{{ old('mother_occupation') }}" class="input">
        </div>
        <div>
          <label class="label">Annual Family Income (₹)</label>
          <input type="number" name="annual_family_income" value="{{ old('annual_family_income') }}" class="input" placeholder="0" min="0" step="1000">
        </div>
        <div>
          <label class="label">Guardian Name</label>
          <input type="text" name="guardian_name" value="{{ old('guardian_name') }}" class="input">
        </div>
        <div>
          <label class="label">Guardian Mobile</label>
          <input type="tel" name="guardian_mobile" value="{{ old('guardian_mobile') }}" class="input">
        </div>
      </div>
    </div>

    {{-- Enrollment --}}
    <div class="card">
      <h3 class="font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100">Class Enrollment</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="label">Class <span class="text-red-500">*</span></label>
          <select name="class_id" id="class_select" required
                  class="select @error('class_id') input-error @enderror"
                  x-model="selectedClassId"
                  @change="onClassChange($event.target.value)"
                  onchange="if(window.admitFormInstance) { window.admitFormInstance.onClassChange(this.value); } const srvErr = document.getElementById('class_id_server_err'); if(srvErr) srvErr.style.display='none';">
            <option value="">Select class</option>
            @foreach($classes as $cls)
              <option value="{{ $cls->id }}" @selected(old('class_id', request('class_id')) == $cls->id)>{{ $cls->name }}</option>
            @endforeach
          </select>
          @error('class_id')
            <p id="class_id_server_err" class="field-error" x-show="!selectedClassId">{{ $message }}</p>
          @enderror
        </div>
        <div>
          <label class="label">Section</label>
          <select name="section_id" class="select" id="section_select">
            <option value="">Select class first</option>
          </select>
        </div>
        <div>
          <label class="label">Roll Number</label>
          <input type="text" name="roll_number" value="{{ old('roll_number') }}" class="input" placeholder="Auto or manual">
        </div>
        <div>
          <label class="label">House</label>
          @if(isset($houses) && $houses->count() > 0)
            <select name="house" class="select">
              <option value="">Select house (optional)</option>
              @foreach($houses as $h)
                <option value="{{ $h->name }}" @selected(old('house') === $h->name || old('house') === explode(' ', $h->name)[0])>{{ $h->name }}</option>
              @endforeach
            </select>
          @else
            <input type="text" name="house" value="{{ old('house') }}" class="input" placeholder="e.g. Red, Blue">
          @endif
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3 justify-end">
      <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Admit Student
      </button>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
function admitForm() {
  return {
    residentialAddress: @json(old('residential_address', '')),
    permanentAddress: @json(old('permanent_address', '')),
    isDisabled: {{ old('is_disabled') ? 'true' : 'false' }},
    selectedClassId: @json((string) old('class_id', request('class_id', ''))),
    selectedSectionId: @json((string) old('section_id', request('section_id', ''))),
    copyAddress() {
      this.permanentAddress = this.residentialAddress;
    },
    init() {
      window.admitFormInstance = this;
      if (this.selectedClassId) {
        this.loadSections(this.selectedClassId, this.selectedSectionId);
      }
    },
    onClassChange(classId) {
      this.selectedClassId = classId;
      const srvErr = document.getElementById('class_id_server_err');
      if (srvErr) srvErr.style.display = 'none';
      this.loadSections(classId, '');
    },
    async loadSections(classId, preselectedSection = '') {
      const sel = document.getElementById('section_select');
      if (!sel) return;
      if (!classId) {
        sel.innerHTML = '<option value="">Select class first</option>';
        return;
      }
      sel.innerHTML = '<option value="">Loading sections…</option>';
      try {
        const res = await fetch(`/api/sections?class_id=${encodeURIComponent(classId)}`, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        if (!res.ok) throw new Error('API returned ' + res.status);
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
          sel.innerHTML = '<option value="">No sections found</option>';
          return;
        }
        let html = '<option value="">Select section</option>';
        data.forEach(s => {
          const isSel = (preselectedSection && String(preselectedSection) === String(s.id)) ? ' selected' : '';
          html += `<option value="${s.id}"${isSel}>Section ${s.name}</option>`;
        });
        sel.innerHTML = html;
        if (preselectedSection) {
          sel.value = preselectedSection;
        }
      } catch (err) {
        console.error('Failed to load sections:', err);
        sel.innerHTML = '<option value="">No sections found</option>';
      }
    }
  }
}
</script>
@endpush
